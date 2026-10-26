<?php
// GET: who is logged in, with their groups.
// POST {action: "code", email}: emails a 6-digit code. {action: "verify", email, code}: logs in. {action: "logout"}.
require __DIR__ . '/session.php';
require __DIR__ . '/mail.php';

const CODE_MINUTES = 10;
const CODE_ATTEMPTS = 5;
const NEW_PER_DAY = 30;

$in = input();
$action = $in['action'] ?? '';
$hash = fn($email, $code) => hash('sha256', "$email:$code");

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $user = current_user();
    reply(['user' => $user ? public_user($user) : null] + ($user ? groups_reply($user) : ['groups' => [], 'invitations' => []]));
}

if ($action === 'logout') {
    end_session();
    reply(['ok' => true]);
}

$email = clean_email($in['email'] ?? '') ?? reply_error('email');
q('DELETE FROM login_codes WHERE created_at < ?', [time() - 86400]);
// Invites nobody accepted are forgotten after 90 days, as the privacy note says.
q('DELETE FROM invites WHERE created_at < ?', [time() - 90 * 86400]);

if ($action === 'code') {
    // At most 5 codes an hour per address (one per 30 s) and 20 tries an hour per IP.
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $mail = q('SELECT COUNT(*) n, MAX(created_at) last FROM login_codes WHERE email = ? AND created_at > ?', [$email, time() - 3600])->fetch();
    $fromIp = q('SELECT COUNT(*) FROM login_codes WHERE ip = ? AND created_at > ?', [$ip, time() - 3600])->fetchColumn();
    if ($mail['n'] >= 5 || $mail['last'] > time() - 30 || $fromIp >= 20) reply_error('too_many', 429);

    $known = q('SELECT 1 FROM users WHERE email = ?', [$email])->fetchColumn() || q('SELECT 1 FROM invites WHERE email = ?', [$email])->fetchColumn();
    $allowed = $known || signup_open();
    // Open signup: at most 30 new accounts a day, so a script can't fill the database.
    if (!$known && $allowed && q('SELECT COUNT(*) FROM users WHERE created_at > ?', [time() - 86400])->fetchColumn() >= NEW_PER_DAY) reply_error('busy', 503);
    if ($allowed && !mail_budget_left()) reply_error('busy', 503);
    $code = sprintf('%06d', random_int(0, 999999));
    // A refused address is logged too (as an already expired row), so the IP limit also slows down guessing who is invited.
    q('INSERT INTO login_codes (email, code_hash, ip, created_at, expires_at) VALUES (?, ?, ?, ?, ?)',
        [$email, $allowed ? $hash($email, $code) : '-', $ip, time(), $allowed ? time() + CODE_MINUTES * 60 : 0]);
    if (!$allowed) reply_error('not_invited', 403);

    $sent = send_mail($email, "Sinu Koriluse kood: $code",
        "Tere!\n\nSinu kood Korilusse sisselogimiseks:\n\n    $code\n\nKood kehtib " . CODE_MINUTES . " minutit. "
        . "Kui sa ise sisse ei loginud, võid selle kirja kustutada.\n\nHead korjamist!\nKorilus\n" . app_url() . "\n");
    $sent ? reply(['ok' => true]) : reply_error('mail', 502);
}

if ($action === 'verify') {
    // At most 15 wrong codes a day per address, however many codes were asked for, so guessing one stays hopeless.
    if (q('SELECT SUM(attempts) FROM login_codes WHERE email = ?', [$email])->fetchColumn() >= 15) reply_error('too_many', 429);
    $row = q('SELECT * FROM login_codes WHERE email = ? AND expires_at > ? ORDER BY id DESC LIMIT 1', [$email, time()])->fetch();
    // The try is counted before the code is compared, in one query, so many guesses sent at once can't get past the limit.
    if (!$row || !q('UPDATE login_codes SET attempts = attempts + 1 WHERE id = ? AND attempts < ?', [$row['id'], CODE_ATTEMPTS])->rowCount()) reply_error('expired');
    if (!hash_equals($row['code_hash'], $hash($email, preg_replace('/\D/', '', (string) ($in['code'] ?? ''))))) reply_error('wrong_code');
    q('DELETE FROM login_codes WHERE email = ?', [$email]);

    $user = q('SELECT * FROM users WHERE email = ?', [$email])->fetch();
    if (!$user) {
        q('INSERT INTO users (email, name, created_at) VALUES (?, ?, ?)', [$email, mb_substr(strstr($email, '@', true), 0, 60), time()]);
        $user = q('SELECT * FROM users WHERE email = ?', [$email])->fetch();
    }
    // Whoever invited this person and they now know each other (see contacts in schema.sql).
    try {
        foreach (q('SELECT DISTINCT invited_by FROM invites WHERE email = ?', [$email])->fetchAll(PDO::FETCH_COLUMN) as $by) {
            q('INSERT IGNORE INTO contacts (user_id, contact_id, created_at) VALUES (?, ?, ?), (?, ?, ?)', [$by, $user['id'], time(), $user['id'], $by, time()]);
        }
    } catch (PDOException) {} // until migrate.php has made the contacts table
    // Invites to the app are used up; group invites wait until the person accepts them in the app.
    q('DELETE FROM invites WHERE email = ? AND group_id IS NULL', [$email]);
    q('DELETE FROM sessions WHERE expires_at < ?', [time()]);
    start_session($user['id']);
    reply(['user' => public_user($user)] + groups_reply($user));
}

reply_error('action');
