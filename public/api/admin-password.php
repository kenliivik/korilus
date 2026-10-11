<?php
// Sets the /admin password. Run on the server over SSH: php ~/htdocs/korilus/api/admin-password.php
// Asks for the new password and saves its hash in data/config.php, keeping the other settings.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

require __DIR__ . '/store.php';

echo 'Uus admini parool (vähemalt 8 märki): ';
system('stty -echo 2>/dev/null');
$password = trim(fgets(STDIN));
system('stty echo 2>/dev/null');
echo "\n";
if (strlen($password) < 8) exit("Liiga lühike, midagi ei muudetud.\n");

$config = ['admin_password_hash' => password_hash($password, PASSWORD_DEFAULT)] + config();
file_put_contents(DATA_DIR . '/config.php', "<?php\nreturn " . var_export($config, true) . ";\n", LOCK_EX);
chmod(DATA_DIR . '/config.php', 0600);
echo "Salvestatud: " . DATA_DIR . "/config.php\n";
