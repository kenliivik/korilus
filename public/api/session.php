<?php
// Login sessions: a random token in a long-lived HttpOnly cookie, kept hashed in the sessions table. Not served.
require_once __DIR__ . '/db.php';

const SESSION_COOKIE = 'korilus_session';
const SESSION_DAYS = 365;

function session_cookie($token, $expires) {
    setcookie(SESSION_COOKIE, $token, [
        'expires' => $expires, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax',
        'secure' => PHP_SAPI !== 'cli-server', // the local test server has no HTTPS
    ]);
}

function start_session($uid) {
    $token = bin2hex(random_bytes(32));
    $expires = time() + SESSION_DAYS * 86400;
    q('INSERT INTO sessions (token_hash, user_id, created_at, expires_at) VALUES (?, ?, ?, ?)', [hash('sha256', $token), $uid, time(), $expires]);
    session_cookie($token, $expires);
}

function end_session() {
    q('DELETE FROM sessions WHERE token_hash = ?', [hash('sha256', $_COOKIE[SESSION_COOKIE] ?? '')]);
    session_cookie('', 1);
}

function current_user() {
    static $user = false;
    if ($user !== false) return $user;
    $token = $_COOKIE[SESSION_COOKIE] ?? '';
    $row = strlen($token) === 64 ? q('SELECT u.*, s.expires_at FROM sessions s JOIN users u ON u.id = s.user_id WHERE s.token_hash = ? AND s.expires_at > ?',
        [hash('sha256', $token), time()])->fetch() : null;
    if (!$row) return $user = null;
    // Stay logged in while the app is used: renew the cookie at most once a month.
    if ($row['expires_at'] < time() + (SESSION_DAYS - 30) * 86400) {
        $expires = time() + SESSION_DAYS * 86400;
        q('UPDATE sessions SET expires_at = ? WHERE token_hash = ?', [$expires, hash('sha256', $token)]);
        session_cookie($token, $expires);
    }
    unset($row['expires_at']);
    return $user = $row;
}

function require_user() {
    return current_user() ?? reply_error('login', 401);
}

function public_user($u) {
    return ['id' => (int) $u['id'], 'email' => $u['email'], 'name' => $u['name'], 'isAdmin' => (bool) $u['is_admin']];
}
