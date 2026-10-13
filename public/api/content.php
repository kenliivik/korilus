<?php
// GET: all berries and recipes (public). POST (admin): save, delete or reorder one collection's items.
// Body: {"collection": "berries"|"recipes", "action": "save"|"delete"|"reorder", "item"|"id"|"ids": ...}
require __DIR__ . '/auth.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $content = load_content();
    $etag = '"v' . $content['version'] . '"';
    header("ETag: $etag");
    if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) { http_response_code(304); exit; }
    exit(json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

require_admin();
// JSON only: a form on another page can't send it, so a visit elsewhere can't edit content with Ken's cookie.
if (!str_starts_with($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) { http_response_code(415); exit('{"error":"json only"}'); }

function fail($error) {
    http_response_code(400);
    exit(json_encode(['error' => $error]));
}

$slug = fn($s) => is_string($s) && preg_match('/^[a-z0-9][a-z0-9-]{0,47}$/', $s);
$text = fn($s, $max) => is_string($s) ? mb_substr(trim($s), 0, $max) : '';

// Only the fields the app knows, so a berry always works in the calendar and in push reminders.
function clean_berry($b, $text) {
    if (!$text($b['name'] ?? '', 40) || !preg_match('/^[.atl]{12}$/', $b['season'] ?? '')) fail('name and season are required');
    $berry = ['id' => $b['id'], 'name' => $text($b['name'], 40), 'latin' => $text($b['latin'] ?? '', 80), 'season' => $b['season'],
        'dates' => [], 'tips' => []];
    foreach (['Algus', 'Tipp', 'Lõpp'] as $k) if ($v = $text($b['dates'][$k] ?? '', 80)) $berry['dates'][$k] = $v;
    foreach (array_slice((array) ($b['tips'] ?? []), 0, 20) as $tip) if ($v = $text($tip, 300)) $berry['tips'][] = $v;
    if (preg_match('#^/uploads/[a-f0-9]+\.(webp|jpg)$#', $b['image'] ?? '')) $berry['image'] = $b['image'];
    if (!empty($b['hidden'])) $berry['hidden'] = true;
    return $berry;
}

$in = json_decode(file_get_contents('php://input', false, null, 0, 100000), true);
$collection = $in['collection'] ?? '';
if (!in_array($collection, ['berries', 'recipes'], true)) fail('unknown collection');

$content = load_content();
$items = $content[$collection] ?? [];
$ids = array_column($items, 'id');

switch ($in['action'] ?? '') {
    case 'save':
        $item = $in['item'] ?? null;
        if (!is_array($item) || !$slug($item['id'] ?? null)) fail('invalid id');
        // Recipes are shaped by their own editor; here they only need an id and a title.
        if ($collection === 'berries') $item = clean_berry($item, $text);
        elseif (!$text($item['title'] ?? '', 120)) fail('title is required');
        $i = array_search($item['id'], $ids, true);
        $i === false ? $items[] = $item : $items[$i] = $item;
        break;
    case 'delete':
        $items = array_values(array_filter($items, fn($it) => $it['id'] !== ($in['id'] ?? null)));
        break;
    case 'reorder':
        $order = array_flip(array_values(array_filter((array) ($in['ids'] ?? []), $slug)));
        usort($items, fn($a, $b) => ($order[$a['id']] ?? PHP_INT_MAX) <=> ($order[$b['id']] ?? PHP_INT_MAX));
        break;
    default:
        fail('unknown action');
}

$content[$collection] = $items;
$content['version']++;
save_content($content);
echo json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
