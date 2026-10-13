<?php
// Copy to api/data/config.php on the server (never into git) and fill in. See README → Admin and Login.
return [
    // Printed by `php api/admin-password.php`, or shown on the /admin login screen while no password is set.
    'admin_password_hash' => '',
    // Login, groups and spot sync (leave out until the database exists).
    // 'db' => ['dsn' => 'mysql:host=127.0.0.1;dbname=DATABASE_NAME;charset=utf8mb4', 'user' => 'DB_USER', 'pass' => 'DB_PASSWORD'],
    // 'smtp' => ['host' => 'mail.veebimajutus.ee', 'port' => 465, 'user' => 'korilus@dsgnr.ee', 'pass' => 'MAILBOX_PASSWORD', 'from' => 'korilus@dsgnr.ee'],
    // 'app_url' => 'https://korilus.dsgnr.ee',
    // 'signup_open' => false,
    // Where feedback from Seaded → Tagasiside is emailed (default: every admin account).
    // 'feedback_to' => 'ken@dsgnr.ee',
];
