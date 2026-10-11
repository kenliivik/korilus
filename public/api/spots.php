<?php
// Two-way spot sync. POST {spots: [...]} with the spots changed on the phone (deleted ones as {id, updatedAt, deleted: true});
// the newer copy wins (last write wins on updatedAt). Both GET and POST answer with every spot the user may see:
// their own (deleted ones as tombstones, so their other phones drop them) and the ones shared with their groups.
require __DIR__ . '/session.php';

$user = require_user();
$uid = (int) $user['id'];
$in = input();
$groups = array_map('intval', q('SELECT group_id FROM group_members WHERE user_id = ?', [$uid])->fetchAll(PDO::FETCH_COLUMN));
$meta = ['id' => 0, 'updatedAt' => 0, 'groupId' => 0, 'deleted' => 0, 'mine' => 0, 'owner' => 0, 'shared' => 0];

db()->beginTransaction();
foreach (array_slice($in['spots'] ?? [], 0, 2000) as $s) {
    $id = $s['id'] ?? null;
    $at = (int) ($s['updatedAt'] ?? 0);
    if (!is_string($id) || !preg_match('/^[\w-]{1,64}$/', $id) || $at <= 0) continue;
    $row = q('SELECT user_id, updated_at FROM spots WHERE id = ?', [$id])->fetch();
    if ($row && ((int) $row['user_id'] !== $uid || (int) $row['updated_at'] >= $at)) continue; // someone else's, or not newer

    $deleted = !empty($s['deleted']);
    $group = in_array((int) ($s['groupId'] ?? 0), $groups, true) ? (int) $s['groupId'] : null;
    $data = $deleted ? '{}' : json_encode(array_diff_key($s, $meta), JSON_UNESCAPED_UNICODE);
    if (strlen($data) > 200000) continue;
    $row
        ? q('UPDATE spots SET group_id = ?, data = ?, updated_at = ?, deleted = ? WHERE id = ?', [$group, $data, $at, (int) $deleted, $id])
        : q('INSERT INTO spots (id, user_id, group_id, data, updated_at, deleted) VALUES (?, ?, ?, ?, ?, ?)', [$id, $uid, $group, $data, $at, (int) $deleted]);
}
db()->commit();

$shared = $groups ? ' OR (s.deleted = 0 AND s.group_id IN (' . implode(',', $groups) . '))' : '';
$rows = q("SELECT s.*, u.name AS owner FROM spots s JOIN users u ON u.id = s.user_id WHERE s.user_id = ?$shared", [$uid])->fetchAll();

reply([
    'spots' => array_map(fn($r) => [
        'id' => $r['id'],
        'updatedAt' => (int) $r['updated_at'],
        'groupId' => $r['group_id'] === null ? null : (int) $r['group_id'],
        'deleted' => (bool) $r['deleted'],
        'mine' => (int) $r['user_id'] === $uid,
        'owner' => $r['owner'],
    ] + (json_decode($r['data'], true) ?: []), $rows),
    'groups' => user_groups($uid),
]);
