<?php
// Admin access for /admin and its endpoints: a logged-in account with is_admin (see create-user.php --admin).
require_once __DIR__ . '/session.php';

function is_admin() {
    return config('db') && (bool) (current_user()['is_admin'] ?? false);
}

// Sends 401 and stops unless the request comes from a logged-in admin.
function require_admin() {
    is_admin() || reply_error('login required', 401);
}
