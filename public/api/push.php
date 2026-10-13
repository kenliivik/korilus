<?php
// GET: the server's public key for subscribing. POST: save a phone's subscription and reminders.
require __DIR__ . '/store.php';
require __DIR__ . '/webpush.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    exit(json_encode(['publicKey' => b64u(raw_public(vapid_key(VAPID_FILE)))]));
}

$in = json_decode(file_get_contents('php://input', false, null, 0, 20000), true);
$sub = $in['subscription'] ?? null;
$endpoint = $sub['endpoint'] ?? '';

// Only real browser push services, so the server never posts to arbitrary addresses.
$pushHosts = '#^https://(fcm\.googleapis\.com|[a-z0-9.-]+\.push\.apple\.com|updates\.push\.services\.mozilla\.com|[a-z0-9.-]+\.notify\.windows\.com)/#';
if (!preg_match($pushHosts, $endpoint) || empty($sub['keys']['p256dh']) || empty($sub['keys']['auth'])) {
    http_response_code(400);
    exit('{"error":"invalid subscription"}');
}

$reminders = array_values(array_filter(array_slice($in['reminders'] ?? [], 0, 50), fn($r) =>
    is_string($r['id'] ?? null) && is_string($r['name'] ?? null) && mb_strlen($r['name']) <= 40
    && preg_match('/^[.atl]{12}$/', $r['season'] ?? '')));

$subs = load_subs();
$key = sha1($endpoint);
// At most 10 phones per IP address, so one script can't use up the 1000 places.
$ip = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? '');
$fromIp = count(array_filter($subs, fn($s, $k) => $k !== $key && ($s['ip'] ?? '') === $ip, ARRAY_FILTER_USE_BOTH));
if (!$reminders) {
    unset($subs[$key]);
} elseif (isset($subs[$key]) || (count($subs) < 1000 && $fromIp < 10)) {
    $subs[$key] = [
        'subscription' => ['endpoint' => $endpoint, 'keys' => ['p256dh' => $sub['keys']['p256dh'], 'auth' => $sub['keys']['auth']]],
        'reminders' => array_map(fn($r) => ['id' => $r['id'], 'name' => $r['name'], 'season' => $r['season']], $reminders),
        'notify' => ['start' => !empty($in['notify']['start']), 'peak' => !empty($in['notify']['peak'])],
        'sent' => $subs[$key]['sent'] ?? [],
        'ip' => $ip,
    ];
}
save_subs($subs);
echo '{"ok":true}';
