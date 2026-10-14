<?php
// Two-way spot sync. POST {spots: [...]} with the spots changed on the phone (deleted ones as {id, updatedAt, deleted: true});
// the newer copy wins (last write wins on updatedAt). Both GET and POST answer with every spot the user may see:
// their own (deleted ones as tombstones, so their other phones drop them) and the ones shared with their groups.
require __DIR__ . '/session.php';

$user = require_user();
$uid = (int) $user['id'];
$in = input();
$groups = array_map('intval', q('SELECT group_id FROM group_members WHERE user_id = ?', [$uid])->fetchAll(PDO::FETCH_COLUMN));
// At most 10 MB and 20 000 spots (deleted ones included) per account. A spot with years of visits is a few kB.
[$used, $count] = array_map('intval', q('SELECT COALESCE(SUM(LENGTH(data)), 0), COUNT(*) FROM spots WHERE user_id = ?', [$uid])->fetch(PDO::FETCH_NUM));
$meta = ['id' => 0, 'updatedAt' => 0, 'groupId' => 0, 'deleted' => 0, 'mine' => 0, 'owner' => 0, 'shared' => 0];

// A spot can be shared with others, so it must be one their app can show: a name, visits with a date, and a real place on the map
// (or none, for spots saved before a place was required).
function showable($s) {
    $place = fn($v, $max) => $v === null || (is_numeric($v) && abs($v) <= $max);
    $visit = fn($v) => is_array($v) && is_string($v['date'] ?? null) && is_array($v['berries'] ?? []);
    return is_string($s['name'] ?? null) && mb_strlen($s['name']) <= 200
        && is_array($s['visits'] ?? null) && count(array_filter($s['visits'], $visit)) === count($s['visits'])
        && $place($s['lat'] ?? null, 90) && $place($s['lng'] ?? null, 180) && (($s['lat'] ?? null) === null) === (($s['lng'] ?? null) === null);
}

db()->beginTransaction();
foreach (array_slice($in['spots'] ?? [], 0, 2000) as $s) {
    $id = $s['id'] ?? null;
    $at = (int) ($s['updatedAt'] ?? 0);
    if (!is_string($id) || !preg_match('/^[\w-]{1,64}$/', $id) || $at <= 0) continue;
    $row = q('SELECT user_id, updated_at, LENGTH(data) size FROM spots WHERE id = ?', [$id])->fetch();
    if ($row && ((int) $row['user_id'] !== $uid || (int) $row['updated_at'] >= $at)) continue; // someone else's, or not newer

    $deleted = !empty($s['deleted']);
    if (!$deleted && !showable($s)) continue;
    if (!$deleted && isset($s['lat'])) [$s['lat'], $s['lng']] = [(float) $s['lat'], (float) $s['lng']];
    $group = in_array((int) ($s['groupId'] ?? 0), $groups, true) ? (int) $s['groupId'] : null;
    $data = $deleted ? '{}' : json_encode(array_diff_key($s, $meta), JSON_UNESCAPED_UNICODE);
    $grow = strlen($data) - (int) ($row['size'] ?? 0);
    if (strlen($data) > 200000 || ($grow > 0 && $used + $grow > 10_000_000) || (!$row && $count >= 20000)) continue;
    $used += $grow;
    $count += $row ? 0 : 1;
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
] + groups_reply($user));
