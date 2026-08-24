<?php
// API: 公告不再提示
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';

$user = current_user();
if (!$user) json_response(null, 401, '请先登录');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(null, 405, 'Method Not Allowed');

$id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
if ($id <= 0) json_response(null, 1, '参数错误');

$db = Database::getInstance();
try {
    $db->insert('announcement_dismissals', [
        'announcement_id' => $id,
        'user_id' => $user['id'],
        'dismissed_at' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {}
json_response(null, 0, '已标记不再提示');
