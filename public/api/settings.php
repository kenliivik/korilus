<?php
// Admin only (Haldus → Liitumine). GET: whether anyone may join, and the accounts, newest first.
// POST {action: "signup", open}: the switch. {action: "delete_user", id}: removes a (spam) account and its data.
require __DIR__ . '/auth.php';

require_admin();
$in = input();
$me = (int) current_user()['id'];

switch ($in['action'] ?? '') {
    case '':
        break;
    case 'signup':
        save_setting('signupOpen', !empty($in['open']));
        break;
    case 'delete_user':
        $u = q('SELECT id, email, is_admin FROM users WHERE id = ?', [(int) ($in['id'] ?? 0)])->fetch();
        if (!$u || (int) $u['id'] === $me || $u['is_admin']) reply_error('not this one');
        delete_user((int) $u['id'], $u['email']);
        break;
    default:
        reply_error('action');
}

$count = fn($sql, $args = []) => (int) q($sql, $args)->fetchColumn();
reply([
    'signupOpen' => (bool) setting('signupOpen'),
    'configOpen' => (bool) config('signup_open'), // config.php opens it too, and only Ken can change that
    'users' => $count('SELECT COUNT(*) FROM users'),
    'newToday' => $count('SELECT COUNT(*) FROM users WHERE created_at > ?', [time() - 86400]),
    'newest' => array_map(fn($u) => ['id' => (int) $u['id'], 'created' => (int) $u['created_at'], 'isAdmin' => (bool) $u['is_admin'],
        'invited' => (bool) $u['invited']] + $u, q('SELECT u.id, u.name, u.email, u.created_at, u.is_admin,
        EXISTS(SELECT 1 FROM contacts c WHERE c.user_id = u.id) invited FROM users u ORDER BY u.id DESC LIMIT 30')->fetchAll()),
]);
