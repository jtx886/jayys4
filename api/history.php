<?php
// API: 观看历史
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';

$user = current_user();
if (!$user) json_response(null, 401, '请先登录');

$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'update';

    if ($action === 'delete') {
        $ids = $_POST['ids'] ?? [];
        if (!empty($ids)) {
            if (is_array($ids)) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $db->delete('watch_history', 'user_id = ? AND id IN (' . $placeholders . ')', array_merge([$user['id']], $ids));
            } else {
                $db->delete('watch_history', 'user_id = ? AND id = ?', [$user['id'], $ids]);
            }
        } elseif (isset($_POST['all'])) {
            $db->delete('watch_history', 'user_id = ?', [$user['id']]);
        }
        json_response(null, 0, '删除成功');
    }

    // 更新历史
    $mediaId = intval($_POST['media_id'] ?? 0);
    $mediaType = $_POST['media_type'] ?? 'movie';
    $episodeId = $_POST['episode_id'] ?? '';
    $title = trim($_POST['title'] ?? '');
    $poster = trim($_POST['poster'] ?? '');
    $season = intval($_POST['season_number'] ?? 0);
    $episode = intval($_POST['episode_number'] ?? 0);
    $watched = intval($_POST['watched_seconds'] ?? 0);
    $total = intval($_POST['total_seconds'] ?? 0);
    $position = intval($_POST['last_position'] ?? 0);
    $sourceUrl = trim($_POST['source_url'] ?? '');

    if ($mediaId <= 0) json_response(null, 1, '参数错误');

    // 查找是否有该记录，按 media + episode
    $row = $db->fetchOne(
        'SELECT id FROM watch_history WHERE user_id = ? AND media_type = ? AND media_id = ? AND episode_id = ?',
        [$user['id'], $mediaType, $mediaId, $episodeId]
    );

    if ($row) {
        $db->update('watch_history', [
            'watched_seconds' => $watched,
            'total_seconds' => $total,
            'last_position' => $position,
            'source_url' => $sourceUrl,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$row['id']]);
        json_response(['id' => $row['id']], 0, '更新成功');
    } else {
        $id = $db->insert('watch_history', [
            'user_id' => $user['id'],
            'media_type' => $mediaType,
            'media_id' => $mediaId,
            'episode_id' => $episodeId,
            'title' => $title,
            'poster_path' => $poster,
            'season_number' => $season,
            'episode_number' => $episode,
            'watched_seconds' => $watched,
            'total_seconds' => $total,
            'last_position' => $position,
            'source_url' => $sourceUrl,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        json_response(['id' => $id], 0, '记录成功');
    }
}

// GET: 获取列表
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = intval($_GET['per_page'] ?? 24);
$offset = ($page - 1) * $perPage;
$total = $db->fetchOne('SELECT COUNT(*) as c FROM watch_history WHERE user_id = ?', [$user['id']])['c'];
$list = $db->fetchAll('SELECT * FROM watch_history WHERE user_id = ? ORDER BY updated_at DESC LIMIT ? OFFSET ?', [$user['id'], $perPage, $offset]);
json_response(['list' => $list, 'total' => intval($total), 'page' => $page, 'per_page' => $perPage]);
