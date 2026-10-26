<?php
// Visits group members log on a spot someone else shares with their group ("Käisin siin" on a shared spot).
// Each person keeps one row per spot with their own visits, so nobody overwrites anyone else's.
// POST {visits: [{spot, updatedAt, visits: [{date, berries, note, rating}]}]} with the rows changed on the phone; the newer copy wins.
// Answers with every row the user may see: their own, and others' on spots they own or that are shared with them,
// as long as the visitor is still in the spot's group.
require __DIR__ . '/session.php';

$user = require_user();
$uid = (int) $user['id'];
$in = input();
$groups = array_map('intval', q('SELECT group_id FROM group_members WHERE user_id = ?', [$uid])->fetchAll(PDO::FETCH_COLUMN));

// Others see these visits, so only plain values go in: at most 500 visits, each with a date, known-looking berry ids,
// a note of up to 2000 characters and 0-5 stars.
function clean_visits($list) {
    if (!is_array($list) || array_values($list) !== $list || count($list) > 500) return null;
    $out = [];
    foreach ($list as $v) {
        $berries = $v['berries'] ?? null;
        if (!is_array($v) || !is_string($v['date'] ?? null) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $v['date'])
            || !is_array($berries) || array_values($berries) !== $berries || count($berries) > 50
            || count(array_filter($berries, fn($b) => is_string($b) && preg_match('/^[\w-]{1,80}$/', $b))) !== count($berries)
            || !is_string($v['note'] ?? '') || mb_strlen($v['note'] ?? '') > 2000
            || !in_array($v['rating'] ?? 0, [0, 1, 2, 3, 4, 5], true)) return null;
        $out[] = ['date' => $v['date'], 'berries' => $berries, 'note' => $v['note'] ?? '', 'rating' => $v['rating'] ?? 0];
    }
    return $out;
}

db()->beginTransaction();
foreach (array_slice($in['visits'] ?? [], 0, 2000) as $row) {
    $spot = $row['spot'] ?? null;
    $at = (int) ($row['updatedAt'] ?? 0);
    if (!is_string($spot) || !preg_match('/^[\w-]{1,64}$/', $spot) || $at <= 0 || ($visits = clean_visits($row['visits'] ?? null)) === null) continue;
    // Only on someone else's spot that is shared with one of the user's groups.
    $s = q('SELECT user_id, group_id FROM spots WHERE id = ? AND deleted = 0', [$spot])->fetch();
    if (!$s || (int) $s['user_id'] === $uid || !in_array((int) $s['group_id'], $groups, true)) continue;
    $old = q('SELECT updated_at FROM spot_visits WHERE user_id = ? AND spot_id = ?', [$uid, $spot])->fetchColumn();
    if ($old !== false && (int) $old >= $at) continue;
    $data = json_encode(['visits' => $visits], JSON_UNESCAPED_UNICODE);
    $old === false
        ? q('INSERT INTO spot_visits (user_id, spot_id, data, updated_at) VALUES (?, ?, ?, ?)', [$uid, $spot, $data, $at])
        : q('UPDATE spot_visits SET data = ?, updated_at = ? WHERE user_id = ? AND spot_id = ?', [$data, $at, $uid, $spot]);
}
db()->commit();

$shared = $groups ? ' OR s.group_id IN (' . implode(',', $groups) . ')' : '';
$rows = q(
    "SELECT v.*, u.name FROM spot_visits v
     JOIN spots s ON s.id = v.spot_id AND s.deleted = 0
     JOIN group_members m ON m.group_id = s.group_id AND m.user_id = v.user_id
     JOIN users u ON u.id = v.user_id
     WHERE v.user_id = ? OR s.user_id = ?$shared",
    [$uid, $uid],
)->fetchAll();

reply([
    'visits' => array_map(fn($r) => [
        'spot' => $r['spot_id'],
        'updatedAt' => (int) $r['updated_at'],
        'mine' => (int) $r['user_id'] === $uid,
        'name' => $r['name'],
    ] + json_decode($r['data'], true), $rows),
]);
