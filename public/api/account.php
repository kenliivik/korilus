<?php
// GET ?export: everything the server keeps about the user, as a JSON download ("Laadi alla minu andmed").
// POST {action: "name", name}: the name group members see. {action: "delete"}: deletes the account and its data on the server.
require __DIR__ . '/session.php';

$user = require_user();
$uid = (int) $user['id'];
$in = input();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Disposition: attachment; filename="korilus-minu-andmed.json"');
    reply([
        'exported' => date('c'),
        'user' => public_user($user) + ['created' => date('c', $user['created_at'])],
        'groups' => user_groups($uid),
        'spots' => array_map(fn($r) => ['id' => $r['id'], 'groupId' => $r['group_id'], 'updated' => date('c', intdiv($r['updated_at'], 1000))]
            + json_decode($r['data'], true), q('SELECT * FROM spots WHERE user_id = ? AND deleted = 0', [$uid])->fetchAll()),
        'invitesSent' => q('SELECT email, group_id groupId, FROM_UNIXTIME(created_at) created FROM invites WHERE invited_by = ?', [$uid])->fetchAll(),
    ]);
}

switch ($in['action'] ?? '') {
    case 'name':
        $name = trim(mb_substr((string) ($in['name'] ?? ''), 0, 60)) ?: reply_error('name');
        q('UPDATE users SET name = ? WHERE id = ?', [$name, $uid]);
        reply(['user' => public_user(['name' => $name] + $user)]);

    case 'delete':
        db()->beginTransaction();
        foreach (q("SELECT group_id FROM group_members WHERE user_id = ? AND role = 'owner'", [$uid])->fetchAll(PDO::FETCH_COLUMN) as $gid) delete_group($gid);
        q('DELETE FROM group_members WHERE user_id = ?', [$uid]);
        q('DELETE FROM spots WHERE user_id = ?', [$uid]);
        q('DELETE FROM invites WHERE invited_by = ? OR email = ?', [$uid, $user['email']]);
        q('DELETE FROM login_codes WHERE email = ?', [$user['email']]);
        q('DELETE FROM sessions WHERE user_id = ?', [$uid]);
        q('DELETE FROM users WHERE id = ?', [$uid]);
        db()->commit();
        session_cookie('', 1);
        reply(['ok' => true]);
}

reply_error('action');
