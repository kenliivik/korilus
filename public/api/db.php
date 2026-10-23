<?php
// Database, JSON and group helpers for the login and sync endpoints. Not served (see .htaccess).
require_once __DIR__ . '/store.php';

function db() {
    static $pdo;
    if (!$pdo) {
        $c = config('db') ?? reply_error('not set up', 503); // until Ken adds db to config.php
        $pdo = new PDO($c['dsn'] ?? '', $c['user'] ?? null, $c['pass'] ?? null, [
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

// The user's groups with their members (and, for owners, the pending invites). Members see each other's names, not their email addresses.
function user_groups($uid) {
    $groups = q('SELECT g.id, g.name, m.role FROM `groups` g JOIN group_members m ON m.group_id = g.id WHERE m.user_id = ? ORDER BY g.name', [$uid])->fetchAll();
    foreach ($groups as &$g) {
        $g['id'] = (int) $g['id'];
        $g['members'] = array_map(fn($m) => ['id' => (int) $m['id'], 'email' => (int) $m['id'] === (int) $uid ? $m['email'] : ''] + $m, q(
            'SELECT u.id, u.name, u.email, m.role FROM group_members m JOIN users u ON u.id = m.user_id WHERE m.group_id = ? ORDER BY m.role DESC, u.name',
            [$g['id']])->fetchAll());
        // An invited person with an account shows by name, so picking a co-member never reveals their email.
        $g['invites'] = $g['role'] === 'owner' ? array_map(fn($i) => ['id' => (int) $i['id'], 'userId' => $i['userId'] ? (int) $i['userId'] : null] + $i, q(
            'SELECT i.id, u.id userId, COALESCE(u.name, i.email) name FROM invites i LEFT JOIN users u ON u.email = i.email WHERE i.group_id = ? ORDER BY name',
            [$g['id']])->fetchAll()) : [];
    }
    return $groups;
}

// People the user can add to a group without typing an email: whoever they invited or were invited by, and the members of their groups.
function user_people($uid) {
    try {
        return array_map(fn($p) => ['id' => (int) $p['id'], 'name' => $p['name']], q(
            'SELECT id, name FROM users WHERE id <> ? AND (id IN (SELECT contact_id FROM contacts WHERE user_id = ?)
             OR id IN (SELECT b.user_id FROM group_members a JOIN group_members b ON b.group_id = a.group_id WHERE a.user_id = ?)) ORDER BY name',
            [$uid, $uid, $uid])->fetchAll());
    } catch (PDOException) {
        return []; // until migrate.php has made the contacts table
    }
}

// What the app shows a logged-in user: their groups, the group invitations waiting for them to accept, and the people they know.
function groups_reply($user) {
    return [
        'groups' => user_groups($user['id']),
        'people' => user_people($user['id']),
        'invitations' => array_map(fn($i) => ['groupId' => (int) $i['groupId']] + $i, q(
            'SELECT i.group_id groupId, g.name, COALESCE(u.name, \'\') `from` FROM invites i JOIN `groups` g ON g.id = i.group_id LEFT JOIN users u ON u.id = i.invited_by
             WHERE i.email = ? ORDER BY g.name', [$user['email']])->fetchAll()),
    ];
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
