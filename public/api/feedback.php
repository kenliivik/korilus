<?php
// POST {message, email?, website?}: anyone sends an idea or feedback. It is stored and emailed to the admins.
// Admin only: GET lists everything, POST {action: "done", id, done} marks one handled, {action: "delete", id} removes it.
require __DIR__ . '/session.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/mail.php';

$in = input();
$action = $in['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' || $action) {
    require_admin();
    if ($action === 'done') q('UPDATE feedback SET done = ? WHERE id = ?', [empty($in['done']) ? 0 : 1, (int) ($in['id'] ?? 0)]);
    elseif ($action === 'delete') q('DELETE FROM feedback WHERE id = ?', [(int) ($in['id'] ?? 0)]);
    elseif ($action) reply_error('action');
    $rows = q('SELECT f.id, f.message, f.email, f.created_at created, f.done, u.name FROM feedback f LEFT JOIN users u ON u.id = f.user_id ORDER BY f.id DESC LIMIT 500')->fetchAll();
    reply(['items' => array_map(fn($r) => ['id' => (int) $r['id'], 'done' => (bool) $r['done'], 'created' => (int) $r['created']] + $r, $rows)]);
}

// A bot fills the hidden "website" field; it gets the same answer as a person, but nothing is kept.
if (!empty($in['website'])) reply(['ok' => true]);

$message = trim(mb_substr((string) ($in['message'] ?? ''), 0, 4000));
if (mb_strlen($message) < 3) reply_error('message');
$email = trim((string) ($in['email'] ?? '')) === '' ? '' : (clean_email($in['email']) ?? reply_error('email'));
$user = current_user();
$email = $email ?: ($user['email'] ?? '');

// At most 5 an hour from one IP address and 50 a day in all, so a script can't fill the inbox.
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
if (q('SELECT COUNT(*) FROM feedback WHERE ip = ? AND created_at > ?', [$ip, time() - 3600])->fetchColumn() >= 5
    || q('SELECT COUNT(*) FROM feedback WHERE created_at > ?', [time() - 86400])->fetchColumn() >= 50) reply_error('too_many', 429);

// The IP address is only for the limit above, so it is forgotten after 30 days.
q("UPDATE feedback SET ip = '' WHERE ip <> '' AND created_at < ?", [time() - 30 * 86400]);
q('INSERT INTO feedback (message, email, user_id, ip, created_at) VALUES (?, ?, ?, ?, ?)', [$message, $email, $user['id'] ?? null, $ip, time()]);

// The copy goes to config 'feedback_to', else to every admin account. A failed email is fine: it is in /admin anyway.
$to = config('feedback_to') ? [config('feedback_to')] : q('SELECT email FROM users WHERE is_admin = 1')->fetchAll(PDO::FETCH_COLUMN);
$from = $user ? "{$user['name']} <$email>" : ($email ?: 'nimetu');
foreach ($to as $address) {
    send_mail($address, 'Korilus: uus tagasiside', "Saatja: $from\n\n$message\n\nKõik tagasiside: " . app_url() . "/admin/tagasiside\n", $email ?: null);
}
reply(['ok' => true]);
