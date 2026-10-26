<?php
// GET: the user's groups and invitations. POST, each answered with the same:
//   {action: "create", name} | {action: "invite", email, groupId?} (no groupId = invite to the app only)
//   {action: "invite", userId, groupId} (someone from the user's people, see user_people)
//   {action: "accept", groupId} | {action: "decline", groupId} (an invitation to the user)
//   {action: "uninvite", groupId, inviteId} | {action: "remove", groupId, userId} | {action: "leave", groupId} | {action: "delete", groupId}
require __DIR__ . '/session.php';
require __DIR__ . '/mail.php';

$user = require_user();
$uid = (int) $user['id'];
$in = input();
$gid = (int) ($in['groupId'] ?? 0);
$role = $gid ? q('SELECT role FROM group_members WHERE group_id = ? AND user_id = ?', [$gid, $uid])->fetchColumn() : null;
$ownerOnly = fn() => $role === 'owner' || reply_error('owner only', 403);

switch ($in['action'] ?? '') {
    case '':
        break;

    case 'create':
        $name = trim(mb_substr((string) ($in['name'] ?? ''), 0, 40)) ?: reply_error('name');
        if (q("SELECT COUNT(*) FROM group_members WHERE user_id = ? AND role = 'owner'", [$uid])->fetchColumn() >= 20) reply_error('too_many', 429);
        q('INSERT INTO `groups` (name, created_at) VALUES (?, ?)', [$name, time()]);
        add_member(db()->lastInsertId(), $uid, 'owner');
        break;

    case 'invite':
        // Nobody joins a group without accepting, and the answer is the same whether or not the address already has an account.
        $picked = (int) ($in['userId'] ?? 0);
        if ($picked && !in_array($picked, array_column(user_people($uid), 'id'), true)) reply_error('email');
        $email = ($picked ? q('SELECT email FROM users WHERE id = ?', [$picked])->fetchColumn() : clean_email($in['email'] ?? '')) ?: reply_error('email');
        if ($gid) $ownerOnly();
        $group = $gid ? q('SELECT name FROM `groups` WHERE id = ?', [$gid])->fetchColumn() : null;
        $who = $user['name'] ?: $user['email'];
        $invitee = q('SELECT id FROM users WHERE email = ?', [$email])->fetchColumn();
        if (($invitee && !$gid) || ($invitee && q('SELECT 1 FROM group_members WHERE group_id = ? AND user_id = ?', [$gid, $invitee])->fetchColumn())) break;
        // Only a new invitation sends an email. To resend, uninvite and invite again.
        if (q('SELECT 1 FROM invites WHERE email = ? AND group_id <=> ?', [$email, $gid ?: null])->fetchColumn()) break;
        // Open signup: someone who joined by themselves (nobody invited them, so they know no one yet) can't send invitations
        // to new addresses in their first 3 days, so a fresh account can't be used to mail strangers.
        if (!$picked && signup_open() && $user['created_at'] > time() - 3 * 86400
            && !q('SELECT 1 FROM contacts WHERE user_id = ?', [$uid])->fetchColumn()) reply_error('too_new', 403);
        if (!mail_budget_left()) reply_error('busy', 503);
        // The mail log keeps counting when an invite is withdrawn, so nobody can use invites to flood an inbox from Korilus's address:
        // at most 20 invitation emails a day from one account and 3 a day to one address.
        if (q('SELECT COUNT(*) FROM mail_log WHERE user_id = ? AND created_at > ?', [$uid, time() - 86400])->fetchColumn() >= 20
            || q('SELECT COUNT(*) FROM mail_log WHERE email = ? AND created_at > ?', [$email, time() - 86400])->fetchColumn() >= 3) reply_error('too_many', 429);
        q('DELETE FROM mail_log WHERE created_at < ?', [time() - 86400]);
        q('INSERT INTO mail_log (user_id, email, created_at) VALUES (?, ?, ?)', [$uid, $email, time()]);
        q('INSERT INTO invites (email, group_id, invited_by, created_at) VALUES (?, ?, ?, ?)', [$email, $gid ?: null, $uid, time()]);
        send_mail($email, $invitee ? "$who kutsus sind Koriluse gruppi „{$group}”" : "$who kutsus sind Korilusse", $invitee
            ? "Tere!\n\n$who kutsus sind Koriluse gruppi „{$group}”. Kui kutse vastu võtad, näed marjakohti, mida grupi liikmed seal jagavad, ja saad ka ise oma kohti jagada.\n\n"
                . "Kutse vastuvõtmiseks ava Korilus: " . app_url() . "/konto\n\nHead korjamist!\n"
            : "Tere!\n\n$who kutsus sind Korilusse"
                . ($group ? " ja oma gruppi „{$group}”" : '') . ". Korilus ütleb, millal marjad valmivad, ja hoiab meeles sinu marjakohad.\n\n"
                . "Ava " . app_url() . "/konto ja logi sisse selle e-posti aadressiga ($email). Saadame sulle koodi, millega sisse saad.\n\nHead korjamist!\n");
        break;

    case 'accept':
        if (q('SELECT 1 FROM invites WHERE email = ? AND group_id = ?', [$user['email'], $gid])->fetchColumn()) add_member($gid, $uid);
        // fall through: the invitation is used up either way
    case 'decline':
        q('DELETE FROM invites WHERE email = ? AND group_id = ?', [$user['email'], $gid]);
        break;

    case 'uninvite':
        $ownerOnly();
        q('DELETE FROM invites WHERE group_id = ? AND id = ?', [$gid, (int) ($in['inviteId'] ?? 0)]);
        break;

    case 'remove':
        $ownerOnly();
        $member = (int) ($in['userId'] ?? 0);
        if ($member !== $uid) remove_member($gid, $member);
        break;

    case 'leave':
        if ($role === 'owner') reply_error('owner_cannot_leave');
        if ($role) remove_member($gid, $uid);
        break;

    case 'delete':
        $ownerOnly();
        delete_group($gid);
        break;

    default:
        reply_error('action');
}

reply(groups_reply($user));
