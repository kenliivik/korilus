<?php
// Shared paths and helpers. The data folder is never uploaded or served (see api/.htaccess and deploy.yml).

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
