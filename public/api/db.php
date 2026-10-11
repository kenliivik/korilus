<?php
// Database, JSON and group helpers for the login and sync endpoints. Not served (see .htaccess).
require_once __DIR__ . '/store.php';

function db() {
    static $pdo;
    if (!$pdo) {
        $c = config('db') ?? reply_error('not set up', 503); // until Ken adds db to config.php
        $pdo = new PDO($c['dsn'] ?? $c[0] ?? '', $c['user'] ?? $c[1] ?? null, $c['pass'] ?? $c[2] ?? null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

function q($sql, $args = []) {
    $st = db()->prepare($sql);
    $st->execute($args);
    return $st;
}

function reply($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    header('Cache-Control: no-store');
    exit(json_encode($data, JSON_UNESCAPED_UNICODE));
}

function reply_error($error, $code = 400) {
    reply(['error' => $error], $code);
}

// POST bodies must be JSON: a plain HTML form on another site can't send that, which (with SameSite cookies) stops CSRF.
function input() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return [];
    if (!str_starts_with($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) reply_error('json only', 415);
    $in = json_decode(file_get_contents('php://input', false, null, 0, 4_000_000), true);
    return is_array($in) ? $in : [];
}

function clean_email($email) {
    $email = strtolower(trim((string) $email));
    return strlen($email) <= 190 && filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
}

function now_ms() {
    return (int) floor(microtime(true) * 1000);
}

function app_url() {
    return rtrim(config('app_url') ?? 'https://' . ($_SERVER['SERVER_NAME'] ?? 'localhost'), '/');
}

// The user's groups with their members (and, for owners, the pending invites).
function user_groups($uid) {
    $groups = q('SELECT g.id, g.name, m.role FROM `groups` g JOIN group_members m ON m.group_id = g.id WHERE m.user_id = ? ORDER BY g.name', [$uid])->fetchAll();
    foreach ($groups as &$g) {
        $g['id'] = (int) $g['id'];
        $g['members'] = array_map(fn($m) => ['id' => (int) $m['id']] + $m, q(
            'SELECT u.id, u.name, u.email, m.role FROM group_members m JOIN users u ON u.id = m.user_id WHERE m.group_id = ? ORDER BY m.role DESC, u.name',
            [$g['id']])->fetchAll());
        $g['invites'] = $g['role'] === 'owner'
            ? q('SELECT email FROM invites WHERE group_id = ? ORDER BY email', [$g['id']])->fetchAll(PDO::FETCH_COLUMN) : [];
    }
    return $groups;
}

function add_member($gid, $uid, $role = 'member') {
    if (!q('SELECT 1 FROM group_members WHERE group_id = ? AND user_id = ?', [$gid, $uid])->fetchColumn()) {
        q('INSERT INTO group_members (group_id, user_id, role, created_at) VALUES (?, ?, ?, ?)', [$gid, $uid, $role, time()]);
    }
}

// Someone leaves or is removed: their spots in the group become private again (newer updated_at, so phones pick it up).
function remove_member($gid, $uid) {
    q('DELETE FROM group_members WHERE group_id = ? AND user_id = ?', [$gid, $uid]);
    q('UPDATE spots SET group_id = NULL, updated_at = GREATEST(updated_at + 1, ?) WHERE group_id = ? AND user_id = ?', [now_ms(), $gid, $uid]);
}

function delete_group($gid) {
    q('UPDATE spots SET group_id = NULL, updated_at = GREATEST(updated_at + 1, ?) WHERE group_id = ?', [now_ms(), $gid]);
    q('DELETE FROM group_members WHERE group_id = ?', [$gid]);
    q('DELETE FROM invites WHERE group_id = ?', [$gid]);
    q('DELETE FROM `groups` WHERE id = ?', [$gid]);
}
