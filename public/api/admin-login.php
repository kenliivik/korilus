<?php
// GET: {"admin": bool}. POST {"password"}: log in. POST {"logout": true}: log out.
require __DIR__ . '/auth.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    exit(json_encode(['admin' => is_admin(), 'configured' => (bool) config('admin_password_hash')]));
}

$in = json_decode(file_get_contents('php://input', false, null, 0, 2000), true);

if (!empty($in['logout'])) {
    set_admin_cookie(false);
    exit('{"admin":false}');
}

$password = (string) ($in['password'] ?? '');
$hash = config('admin_password_hash');

// No password yet: hand back the hash of what was typed, for Ken to put into data/config.php.
// It only reveals the hash of the visitor's own input, so it is safe to show to anyone.
if (!$hash) {
    http_response_code(503);
    exit(json_encode(['error' => 'not configured', 'hash' => strlen($password) >= 8 ? password_hash($password, PASSWORD_DEFAULT) : null]));
}

// At most 10 wrong tries per IP address in 15 minutes.
$file = DATA_DIR . '/login-failures.json';
$fails = array_filter(array_map(fn($times) => array_filter($times, fn($t) => $t > time() - 900),
    is_file($file) ? json_decode(file_get_contents($file), true) : []));
$ip = $_SERVER['REMOTE_ADDR'] ?? '';

if (count($fails[$ip] ?? []) >= 10) {
    http_response_code(429);
    exit('{"error":"too many attempts"}');
}

if (password_verify($password, $hash)) {
    unset($fails[$ip]);
    set_admin_cookie(true);
    echo '{"admin":true}';
} else {
    $fails[$ip][] = time();
    sleep(1);
    http_response_code(401);
    echo '{"error":"wrong password"}';
}
file_put_contents($file, json_encode($fails), LOCK_EX);
