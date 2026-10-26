<?php
// Shared paths and helpers. The data folder is never uploaded or served (see api/.htaccess and deploy.yml).

// Errors go to the server log, never into a reply (a database error could otherwise show the connection details).
ini_set('display_errors', '0');
ini_set('log_errors', '1');

const DATA_DIR = __DIR__ . '/data';
const SUBS_FILE = DATA_DIR . '/subscriptions.json';
const VAPID_FILE = DATA_DIR . '/vapid.pem';

if (!is_dir(DATA_DIR)) mkdir(DATA_DIR, 0700);

function load_subs() {
    return is_file(SUBS_FILE) ? json_decode(file_get_contents(SUBS_FILE), true) : [];
}

function save_subs($subs) {
    file_put_contents(SUBS_FILE, json_encode($subs, JSON_UNESCAPED_UNICODE), LOCK_EX);
}

// Server settings from data/config.php (see config.example.php), or [] when there is none yet.
function config($key = null) {
    static $config;
    $config ??= is_file(DATA_DIR . '/config.php') ? (include DATA_DIR . '/config.php') : [];
    return $key === null ? $config : ($config[$key] ?? null);
}

// Settings changed in /admin (Haldus → Liitumine), kept next to config.php: { signupOpen: bool }.
const SETTINGS_FILE = DATA_DIR . '/settings.json';

function setting($key) {
    static $settings;
    $settings ??= is_file(SETTINGS_FILE) ? (json_decode(file_get_contents(SETTINGS_FILE), true) ?: []) : [];
    return $settings[$key] ?? null;
}

function save_setting($key, $value) {
    $settings = is_file(SETTINGS_FILE) ? (json_decode(file_get_contents(SETTINGS_FILE), true) ?: []) : [];
    $settings[$key] = $value;
    file_put_contents(SETTINGS_FILE, json_encode($settings), LOCK_EX);
}

// Anyone may create an account (not only the invited) when the switch in /admin is on, or config.php says so.
function signup_open() {
    return (bool) (config('signup_open') || setting('signupOpen'));
}

// Berries and recipes edited in /admin. Version 0 means nothing has been saved yet,
// so the app keeps showing the berries bundled with it.
const CONTENT_FILE = DATA_DIR . '/content.json';
const BACKUP_DIR = DATA_DIR . '/backups';

function load_content() {
    return is_file(CONTENT_FILE) ? json_decode(file_get_contents(CONTENT_FILE), true)
        : ['version' => 0, 'berries' => [], 'recipes' => []];
}

// Every save also leaves a dated copy (the newest 200 are kept), so a bad edit can be undone by hand.
function save_content($content) {
    $json = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_dir(BACKUP_DIR)) mkdir(BACKUP_DIR, 0700);
    file_put_contents(BACKUP_DIR . '/content-' . date('Ymd-His') . "-v{$content['version']}.json", $json);
    array_map('unlink', array_slice(array_reverse(glob(BACKUP_DIR . '/content-*.json')), 200));
    file_put_contents(CONTENT_FILE, $json, LOCK_EX);
}
