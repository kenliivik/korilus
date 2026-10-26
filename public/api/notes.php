<?php
// Recipe notes sync, the same way as spots.php. POST {notes: [{recipe, updatedAt, groupId, lines: {line: note}, general}]}
// with the notes changed on the phone; the newer copy wins. Answers with the user's own notes and the ones their groups share.
require __DIR__ . '/session.php';

$user = require_user();
$uid = (int) $user['id'];
$in = input();
$groups = array_map('intval', q('SELECT group_id FROM group_members WHERE user_id = ?', [$uid])->fetchAll(PDO::FETCH_COLUMN));
$count = (int) q('SELECT COUNT(*) FROM recipe_notes WHERE user_id = ?', [$uid])->fetchColumn();

// Others may see a shared note, so only plain text goes in: at most 100 lines of 1000 characters and a general note of 4000.
function clean_note($n) {
    $text = fn($v, $max) => is_string($v) && mb_strlen($v) <= $max;
    $lines = $n['lines'] ?? [];
    if (!is_array($lines) || count($lines) > 100 || !$text($n['general'] ?? '', 4000)) return null;
    foreach ($lines as $k => $v) if (!$text((string) $k, 500) || !$text($v, 1000)) return null;
    return ['lines' => (object) $lines, 'general' => $n['general'] ?? ''];
}

db()->beginTransaction();
foreach (array_slice($in['notes'] ?? [], 0, 2000) as $n) {
    $id = $n['recipe'] ?? null;
    $at = (int) ($n['updatedAt'] ?? 0);
    if (!is_string($id) || !preg_match('/^[\w-]{1,80}$/', $id) || $at <= 0 || !($note = clean_note($n))) continue;
    $old = q('SELECT updated_at FROM recipe_notes WHERE user_id = ? AND recipe_id = ?', [$uid, $id])->fetchColumn();
    if ($old !== false ? (int) $old >= $at : $count >= 2000) continue;
    $group = in_array((int) ($n['groupId'] ?? 0), $groups, true) ? (int) $n['groupId'] : null;
    $data = json_encode($note, JSON_UNESCAPED_UNICODE);
    $old === false
        ? q('INSERT INTO recipe_notes (user_id, recipe_id, group_id, data, updated_at) VALUES (?, ?, ?, ?, ?)', [$uid, $id, $group, $data, $at])
        : q('UPDATE recipe_notes SET group_id = ?, data = ?, updated_at = ? WHERE user_id = ? AND recipe_id = ?', [$group, $data, $at, $uid, $id]);
    $count += $old === false ? 1 : 0;
}
db()->commit();

$shared = $groups ? ' OR n.group_id IN (' . implode(',', $groups) . ')' : '';
$rows = q("SELECT n.*, u.name AS owner FROM recipe_notes n JOIN users u ON u.id = n.user_id WHERE n.user_id = ?$shared", [$uid])->fetchAll();

reply([
    'notes' => array_map(fn($r) => [
        'recipe' => $r['recipe_id'],
        'updatedAt' => (int) $r['updated_at'],
        'groupId' => $r['group_id'] === null ? null : (int) $r['group_id'],
        'mine' => (int) $r['user_id'] === $uid,
        'owner' => $r['owner'],
    ] + json_decode($r['data'], true), $rows),
]);
