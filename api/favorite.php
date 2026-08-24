<?php
// API: 收藏切换
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';

$user = current_user();
if (!$user) json_response(null, 401, '请先登录');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mediaId = intval($_POST['media_id'] ?? 0);
    $mediaType = $_POST['media_type'] ?? 'movie';
    $title = trim($_POST['title'] ?? '');
    $poster = trim($_POST['poster'] ?? '');
    $year = trim($_POST['year'] ?? '');

    if ($mediaId <= 0) json_response(null, 1, '参数错误');

    $db = Database::getInstance();
    $exists = $db->fetchOne(
        'SELECT id FROM favorites WHERE user_id = ? AND media_type = ? AND media_id = ?',
        [$user['id'], $mediaType, $mediaId]
    );
    if ($exists) {
        $db->delete('favorites', 'id = ?', [$exists['id']]);
        json_response(['favorited' => false], 0, '已取消收藏');
    }
    $db->insert('favorites', [
        'user_id' => $user['id'],
        'media_type' => $mediaType,
        'media_id' => $mediaId,
        'title' => $title,
        'poster_path' => $poster,
        'year' => $year,
        'created_at' => date('Y-m-d H:i:s')
    ]);
    json_response(['favorited' => true], 0, '已添加收藏');
}

// GET: 获取收藏列表
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = intval($_GET['per_page'] ?? 24);
$offset = ($page - 1) * $perPage;
$db = Database::getInstance();
$total = $db->fetchOne('SELECT COUNT(*) as c FROM favorites WHERE user_id = ?', [$user['id']])['c'];
$list = $db->fetchAll('SELECT * FROM favorites WHERE user_id = ? ORDER BY id DESC LIMIT ? OFFSET ?', [$user['id'], $perPage, $offset]);
json_response(['list' => $list, 'total' => intval($total), 'page' => $page, 'per_page' => $perPage]);
