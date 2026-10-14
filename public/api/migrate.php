<?php
// Creates the login and sync tables from schema.sql. Safe to run again. Run once after filling api/data/config.php:
//   php ~/htdocs/korilus/api/migrate.php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/db.php';

$sql = preg_replace('/--.*$/m', '', file_get_contents(__DIR__ . '/schema.sql'));
foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) db()->exec($statement);
echo "OK: tabelid on olemas.\n";

