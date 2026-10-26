<?php
// Run once a day by cron: php ~/htdocs/korilus/api/send.php
// Tells each phone when a species it follows starts ripening or reaches its peak (once per season).
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

require __DIR__ . '/store.php';
require __DIR__ . '/webpush.php';

$vapid = vapid_key(VAPID_FILE);
$month = (int) date('n') - 1;
$messages = [
    'start' => ['%s hakkab valmima', 'Vaata, kust sa seda eelmisel korral leidsid.'],
    'peak' => ['%s: nüüd on parim aeg!', 'Marjad on valmis, mine metsa!'],
];
// With the user's own dates (from their harvest log): "starts" from 3 days before their usual first find, "best" from their usual
// best day, each for a week or so in case the phone was off. Without them, the general month from the calendar (a = start, t = peak).
$day = fn($mmdd) => (int) date('z', mktime(12, 0, 0, (int) substr($mmdd, 0, 2), (int) substr($mmdd, 3, 2), 2001));
$today = $day(date('m-d') === '02-29' ? '02-28' : date('m-d'));
$near = fn($mmdd, $before, $after) => $mmdd && $today >= $day($mmdd) - $before && $today <= $day($mmdd) + $after;

// The season and name as they are now in /admin; the copy each phone sent is only the fallback (built-in berries before any save).
$live = array_column(load_content()['berries'], null, 'id');

$subs = load_subs();
foreach ($subs as $key => &$s) {
    foreach ($s['reminders'] as $r) {
        if (!empty($live[$r['id']]['hidden'])) continue;
        $mine = $r['mine'] ?? null;
        $r = ($live[$r['id']] ?? []) + $r;
        $due = [
            'start' => $mine ? $near($mine['start'], 3, 10) : $r['season'][$month] === 'a',
            'peak' => $mine && $mine['peak'] ? $near($mine['peak'], 0, 7) : $r['season'][$month] === 't',
        ];
        foreach ($due as $phase => $now) {
            // One message per species, phase and year (the tags match the ones from before personal dates: a = start, t = peak).
            $tag = date('Y') . '-' . ($phase === 'start' ? 'a' : 't');
            if (!$now || !$s['notify'][$phase] || in_array($s['sent'][$r['id']] ?? '', [$tag, date('Y') . '-t'], true)) continue;

            [$title, $body] = $messages[$phase];
            if ($mine && $phase === 'start') $body = 'Sinu kohtades hakkab see tavaliselt paari päeva pärast valmima.';
            $status = send_push($s['subscription'], json_encode([
                'title' => sprintf($title, $r['name']), 'body' => $body, 'url' => "/mari/{$r['id']}",
            ], JSON_UNESCAPED_UNICODE), $vapid);

            if ($status === 404 || $status === 410) { unset($subs[$key]); continue 3; }
            if ($status >= 200 && $status < 300) $s['sent'][$r['id']] = $tag;
            echo date('c') . " {$r['id']} $phase $status\n";
            break; // at most one message per species a day
        }
    }
}
unset($s);
save_subs($subs);
