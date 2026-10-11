<?php
// Admin access for /admin: a user account with is_admin, or one password (config admin_password_hash). Logging in sets a signed
// HttpOnly cookie for 30 days. Changing the password logs every device out.
require_once __DIR__ . '/store.php';

const ADMIN_COOKIE = 'korilus_admin';

function admin_sig($expires) {
    return hash_hmac('sha256', "admin|$expires", (string) config('admin_password_hash'));
}

function is_admin() {
    [$expires, $sig] = explode('.', $_COOKIE[ADMIN_COOKIE] ?? '', 2) + ['', ''];
    return config('admin_password_hash') && (int) $expires > time() && hash_equals(admin_sig($expires), $sig);
}

function set_admin_cookie($on) {
    $expires = $on ? time() + 30 * 86400 : 1;
    setcookie(ADMIN_COOKIE, $on ? $expires . '.' . admin_sig($expires) : '', [
        'expires' => $expires, 'path' => '/api/', 'httponly' => true, 'samesite' => 'Strict',
        'secure' => !in_array($_SERVER['HTTPS'] ?? '', ['', 'off']),
    ]);
}

// Sends 401 and stops unless the request comes from the admin: the password login above, or a
// logged-in user with is_admin (php api/create-user.php email --admin) once the database is set up.
function require_admin() {
    if (is_admin()) return;
    if (config('db')) {
        require_once __DIR__ . '/session.php';
        if (current_user()['is_admin'] ?? false) return;
    }
    http_response_code(401);
    header('Content-Type: application/json');
    exit('{"error":"login required"}');
}
