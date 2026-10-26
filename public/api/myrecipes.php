<?php
// Own recipes sync, the same way as spots.php. POST {recipes: [...]} with the recipes changed on the phone (deleted ones as
// {id, updatedAt, deleted: true}); the newer copy wins. Answers with the user's own recipes and the ones their groups share.
require __DIR__ . '/session.php';

$user = require_user();
$uid = (int) $user['id'];
$in = input();
$groups = array_map('intval', q('SELECT group_id FROM group_members WHERE user_id = ?', [$uid])->fetchAll(PDO::FETCH_COLUMN));
$count = (int) q('SELECT COUNT(*) FROM user_recipes WHERE user_id = ?', [$uid])->fetchColumn();
$fields = ['title' => 200, 'type' => 20, 'time' => 60, 'yield' => 60, 'tip' => 4000];

// Others may see a shared recipe, so it must be one their app can show, with plain text only.
function clean_recipe($r, $fields) {
    $text = fn($v, $max) => is_string($v) && mb_strlen($v) <= $max;
    $list = fn($v, $max) => is_array($v) && array_values($v) === $v && count($v) <= $max;
    if (!$text($r['title'] ?? null, 200) || trim($r['title']) === '') return null;
    foreach (array_diff_key($fields, ['title' => 1]) as $k => $max) if (isset($r[$k]) && !$text($r[$k], $max)) return null;
    if (!$list($r['berries'] ?? [], 50) || !$list($r['ingredients'] ?? [], 100) || !$list($r['steps'] ?? [], 100)) return null;
    foreach ($r['berries'] ?? [] as $b) if (!$text($b, 64)) return null;
    foreach ($r['steps'] ?? [] as $s) if (!$text($s, 2000)) return null;
    foreach ($r['ingredients'] ?? [] as $i) if (!is_array($i) || !$text($i['amount'] ?? '', 60) || !$text($i['item'] ?? null, 200)) return null;
    $out = array_intersect_key($r, $fields) + ['berries' => $r['berries'] ?? [], 'steps' => $r['steps'] ?? []];
    // When it was first shared with which group (milliseconds), for the group's activity line.
    foreach (['sharedAt', 'sharedTo'] as $k) if (is_int($r[$k] ?? null)) $out[$k] = $r[$k];
    $out['ingredients'] = array_map(fn($i) => ['amount' => $i['amount'] ?? '', 'item' => $i['item']], $r['ingredients'] ?? []);
    return $out;
}

db()->beginTransaction();
foreach (array_slice($in['recipes'] ?? [], 0, 1000) as $r) {
    $id = $r['id'] ?? null;
    $at = (int) ($r['updatedAt'] ?? 0);
    if (!is_string($id) || !preg_match('/^[\w-]{1,64}$/', $id) || $at <= 0) continue;
    $row = q('SELECT user_id, updated_at FROM user_recipes WHERE id = ?', [$id])->fetch();
    if ($row ? (int) $row['user_id'] !== $uid || (int) $row['updated_at'] >= $at : $count >= 1000) continue; // someone else's, not newer, or too many
    $deleted = !empty($r['deleted']);
    $recipe = $deleted ? [] : clean_recipe($r, $fields);
    if ($recipe === null) continue;
    $group = in_array((int) ($r['groupId'] ?? 0), $groups, true) ? (int) $r['groupId'] : null;
    $data = $deleted ? '{}' : json_encode($recipe, JSON_UNESCAPED_UNICODE);
    $row
        ? q('UPDATE user_recipes SET group_id = ?, data = ?, updated_at = ?, deleted = ? WHERE id = ?', [$group, $data, $at, (int) $deleted, $id])
        : q('INSERT INTO user_recipes (id, user_id, group_id, data, updated_at, deleted) VALUES (?, ?, ?, ?, ?, ?)', [$id, $uid, $group, $data, $at, (int) $deleted]);
    $count += $row ? 0 : 1;
}
db()->commit();

$shared = $groups ? ' OR (r.deleted = 0 AND r.group_id IN (' . implode(',', $groups) . '))' : '';
$rows = q("SELECT r.*, u.name AS owner FROM user_recipes r JOIN users u ON u.id = r.user_id WHERE r.user_id = ?$shared", [$uid])->fetchAll();

reply([
    'recipes' => array_map(fn($r) => [
        'id' => $r['id'],
        'updatedAt' => (int) $r['updated_at'],
        'groupId' => $r['group_id'] === null ? null : (int) $r['group_id'],
        'deleted' => (bool) $r['deleted'],
        'mine' => (int) $r['user_id'] === $uid,
        'owner' => $r['owner'],
    ] + (json_decode($r['data'], true) ?: []), $rows),
]);
