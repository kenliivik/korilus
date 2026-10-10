<?php
// Run once a day by cron: php ~/htdocs/korilus/api/send.php
// Tells each phone when a species it follows starts ripening or reaches its peak (once per season).
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

require __DIR__ . '/store.php';
require __DIR__ . '/webpush.php';

$vapid = vapid_key(VAPID_FILE);
$month = (int) date('n') - 1;
$messages = [
    'a' => ['start', '%s hakkab valmima', 'Hooaeg algab. Vaata, kus sa seda varem leidsid.'],
    't' => ['peak', '%s on küps!', 'Praegu on parim aeg korjata.'],
];

$subs = load_subs();
foreach ($subs as $key => &$s) {
    foreach ($s['reminders'] as $r) {
        $phase = $r['season'][$month];
        $tag = date('Y') . "-$phase";
        if (!isset($messages[$phase]) || !$s['notify'][$messages[$phase][0]] || ($s['sent'][$r['id']] ?? '') === $tag) continue;

        [, $title, $body] = $messages[$phase];
        $status = send_push($s['subscription'], json_encode([
            'title' => sprintf($title, $r['name']), 'body' => $body, 'url' => "/mari/{$r['id']}",
        ], JSON_UNESCAPED_UNICODE), $vapid);

        if ($status === 404 || $status === 410) { unset($subs[$key]); continue 2; }
        if ($status >= 200 && $status < 300) $s['sent'][$r['id']] = $tag;
        echo date('c') . " {$r['id']} $status\n";
    }
}
unset($s);
save_subs($subs);
