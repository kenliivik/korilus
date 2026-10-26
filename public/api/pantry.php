<?php
// Sahver sync, the same way as myrecipes.php but never shared. POST {pantry: [...]} with the entries changed on the phone
// (deleted ones as {id, updatedAt, deleted: true}); the newer copy wins. Answers with all of the user's entries.
require __DIR__ . '/session.php';

$user = require_user();
$uid = (int) $user['id'];
$in = input();
$count = (int) q('SELECT COUNT(*) FROM pantry WHERE user_id = ?', [$uid])->fetchColumn();

// Only the fields the app knows, as plain text and numbers.
function clean_jar($j) {
    $text = fn($v, $max) => is_string($v) && mb_strlen($v) <= $max;
    $id = fn($v) => $v === null || (is_string($v) && preg_match('/^[\w-]{1,80}$/', $v));
    if (!$text($j['title'] ?? null, 200) || trim($j['title']) === '' || !$text($j['note'] ?? '', 4000)) return null;
    if (!preg_match('/^\d{4}-\d\d-\d\d$/', $j['made'] ?? '') || !$id($j['recipeId'] ?? null) || !$id($j['spotId'] ?? null)) return null;
    $berries = $j['berries'] ?? [];
    if (!is_array($berries) || array_values($berries) !== $berries || count($berries) > 50 || array_filter($berries, fn($b) => !$text($b, 64))) return null;
    $jars = max(0, min(999, (int) ($j['jars'] ?? 0)));
    return ['title' => $j['title'], 'recipeId' => $j['recipeId'] ?? null, 'jars' => $jars, 'left' => max(0, min($jars, (int) ($j['left'] ?? $jars))),
        'made' => $j['made'], 'berries' => $berries, 'spotId' => $j['spotId'] ?? null, 'note' => $j['note'] ?? ''];
}

db()->beginTransaction();
foreach (array_slice($in['pantry'] ?? [], 0, 2000) as $j) {
    $id = $j['id'] ?? null;
    $at = (int) ($j['updatedAt'] ?? 0);
    if (!is_string($id) || !preg_match('/^[\w-]{1,64}$/', $id) || $at <= 0) continue;
    $row = q('SELECT user_id, updated_at FROM pantry WHERE id = ?', [$id])->fetch();
    if ($row ? (int) $row['user_id'] !== $uid || (int) $row['updated_at'] >= $at : $count >= 2000) continue; // someone else's, not newer, or too many
    $deleted = !empty($j['deleted']);
    $jar = $deleted ? [] : clean_jar($j);
    if ($jar === null) continue;
    $data = $deleted ? '{}' : json_encode($jar, JSON_UNESCAPED_UNICODE);
    $row
        ? q('UPDATE pantry SET data = ?, updated_at = ?, deleted = ? WHERE id = ?', [$data, $at, (int) $deleted, $id])
        : q('INSERT INTO pantry (id, user_id, data, updated_at, deleted) VALUES (?, ?, ?, ?, ?)', [$id, $uid, $data, $at, (int) $deleted]);
    $count += $row ? 0 : 1;
}
db()->commit();

reply([
    'pantry' => array_map(fn($r) => ['id' => $r['id'], 'updatedAt' => (int) $r['updated_at'], 'deleted' => (bool) $r['deleted'], 'mine' => true]
        + (json_decode($r['data'], true) ?: []), q('SELECT * FROM pantry WHERE user_id = ?', [$uid])->fetchAll()),
]);
