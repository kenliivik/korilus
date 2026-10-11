<?php
// GET: who is logged in, with their groups.
// POST {action: "code", email}: emails a 6-digit code. {action: "verify", email, code}: logs in. {action: "logout"}.
require __DIR__ . '/session.php';
require __DIR__ . '/mail.php';

const CODE_MINUTES = 10;
const CODE_ATTEMPTS = 5;

$in = input();
$action = $in['action'] ?? '';
$hash = fn($email, $code) => hash('sha256', "$email:$code");

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $user = current_user();
    reply(['user' => $user ? public_user($user) : null, 'groups' => $user ? user_groups($user['id']) : []]);
}

if ($action === 'logout') {
    end_session();
    reply(['ok' => true]);
}

$email = clean_email($in['email'] ?? '') ?? reply_error('email');
q('DELETE FROM login_codes WHERE created_at < ?', [time() - 86400]);

if ($action === 'code') {
    // At most 5 codes an hour per address (one per 30 s) and 20 tries an hour per IP.
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $mail = q('SELECT COUNT(*) n, MAX(created_at) last FROM login_codes WHERE email = ? AND created_at > ?', [$email, time() - 3600])->fetch();
    $fromIp = q('SELECT COUNT(*) FROM login_codes WHERE ip = ? AND created_at > ?', [$ip, time() - 3600])->fetchColumn();
    if ($mail['n'] >= 5 || $mail['last'] > time() - 30 || $fromIp >= 20) reply_error('too_many', 429);

    $allowed = config('signup_open')
        || q('SELECT 1 FROM users WHERE email = ?', [$email])->fetchColumn()
        || q('SELECT 1 FROM invites WHERE email = ?', [$email])->fetchColumn();
    $code = sprintf('%06d', random_int(0, 999999));
    // A refused address is logged too (as an already expired row), so the IP limit also slows down guessing who is invited.
    q('INSERT INTO login_codes (email, code_hash, ip, created_at, expires_at) VALUES (?, ?, ?, ?, ?)',
        [$email, $allowed ? $hash($email, $code) : '-', $ip, time(), $allowed ? time() + CODE_MINUTES * 60 : 0]);
    if (!$allowed) reply_error('not_invited', 403);

    $sent = send_mail($email, "Korilus: sinu kood on $code",
        "Tere!\n\nSinu Koriluse sisselogimise kood on:\n\n    $code\n\nKood kehtib " . CODE_MINUTES . " minutit. "
        . "Kui sa ei proovinud sisse logida, võid selle kirja lihtsalt kustutada.\n\nKorilus\n" . app_url() . "\n");
    $sent ? reply(['ok' => true]) : reply_error('mail', 502);
}

if ($action === 'verify') {
    $row = q('SELECT * FROM login_codes WHERE email = ? AND expires_at > ? ORDER BY id DESC LIMIT 1', [$email, time()])->fetch();
    if (!$row || $row['attempts'] >= CODE_ATTEMPTS) reply_error('expired');
    if (!hash_equals($row['code_hash'], $hash($email, preg_replace('/\D/', '', (string) ($in['code'] ?? ''))))) {
        q('UPDATE login_codes SET attempts = attempts + 1 WHERE id = ?', [$row['id']]);
        reply_error('wrong_code');
    }
    q('DELETE FROM login_codes WHERE email = ?', [$email]);

    $user = q('SELECT * FROM users WHERE email = ?', [$email])->fetch();
    if (!$user) {
        q('INSERT INTO users (email, name, created_at) VALUES (?, ?, ?)', [$email, mb_substr(strstr($email, '@', true), 0, 60), time()]);
        $user = q('SELECT * FROM users WHERE email = ?', [$email])->fetch();
    }
    foreach (q('SELECT DISTINCT group_id FROM invites WHERE email = ? AND group_id IS NOT NULL', [$email])->fetchAll(PDO::FETCH_COLUMN) as $gid) {
        add_member($gid, $user['id']);
    }
    q('DELETE FROM invites WHERE email = ?', [$email]);
    start_session($user['id']);
    reply(['user' => public_user($user), 'groups' => user_groups($user['id'])]);
}

reply_error('action');
