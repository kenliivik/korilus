<?php
// Adds someone who may log in (or updates them). The first admin is made this way:
//   php ~/htdocs/korilus/api/create-user.php ken@example.ee --admin --name=Ken
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/db.php';

$email = clean_email($argv[1] ?? '') ?? exit("Kasutus: php create-user.php e-post [--admin] [--name=Nimi]\n");
$admin = in_array('--admin', $argv, true) ? 1 : 0;
$name = '';
foreach ($argv as $a) if (str_starts_with($a, '--name=')) $name = mb_substr(substr($a, 7), 0, 60);

if (q('SELECT 1 FROM users WHERE email = ?', [$email])->fetchColumn()) {
    if ($admin) q('UPDATE users SET is_admin = 1 WHERE email = ?', [$email]);
    if ($name !== '') q('UPDATE users SET name = ? WHERE email = ?', [$name, $email]);
    echo "Uuendatud: $email" . ($admin ? ' (admin)' : '') . "\n";
} else {
    q('INSERT INTO users (email, name, is_admin, created_at) VALUES (?, ?, ?, ?)', [$email, $name ?: strstr($email, '@', true), $admin, time()]);
    echo "Lisatud: $email" . ($admin ? ' (admin)' : '') . "\n";
}
