<?php
// API: 反馈 + 回复 + 点赞
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';

$user = current_user();
$db = Database::getInstance();

// ============ 点赞 ============
if (isset($_GET['like']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$user) json_response(null, 401, '请先登录');
    $fid = intval($_POST['feedback_id'] ?? 0);
    $exists = $db->fetchOne('SELECT id FROM feedback_likes WHERE feedback_id = ? AND user_id = ?', [$fid, $user['id']]);
    if ($exists) {
        $db->delete('feedback_likes', 'id = ?', [$exists['id']]);
        json_response(['liked' => false], 0, '已取消点赞');
    }
    $db->insert('feedback_likes', [
        'feedback_id' => $fid,
        'user_id' => $user['id'],
        'created_at' => date('Y-m-d H:i:s')
    ]);
    json_response(['liked' => true], 0, '已点赞');
}

// ============ 提交回复 ============
if (isset($_GET['reply']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$user) json_response(null, 401, '请先登录');
    $fid = intval($_POST['feedback_id'] ?? 0);
    $content = trim($_POST['content'] ?? '');
    if ($fid <= 0 || mb_strlen($content) < 2) json_response(null, 1, '请输入回复内容（至少2字）');

    $id = $db->insert('feedback_replies', [
        'feedback_id' => $fid,
        'user_id' => $user['id'],
        'content' => $content,
        'is_admin' => $user['role'] === 'admin' ? 1 : 0,
        'created_at' => date('Y-m-d H:i:s')
    ]);

    // 管理员回复：给反馈用户发邮件
    if ($user['role'] === 'admin') {
        $fb = $db->fetchOne('SELECT f.title, u.email FROM feedbacks f JOIN users u ON u.id = f.user_id WHERE f.id = ?', [$fid]);
        if ($fb && !empty($fb['email'])) {
            require_once __DIR__ . '/../includes/mailer.php';
            Mailer::send($fb['email'], '【' . SITE_NAME . '】您的反馈有新回复', Mailer::feedbackReply($fb['title'], $content));
        }
        $db->update('feedbacks', ['status' => 'replied', 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$fid]);
    }

    json_response([
        'id' => $id,
        'username' => $user['username'],
        'avatar' => $user['avatar'] ?? '',
        'is_admin' => $user['role'] === 'admin',
        'content' => $content,
        'created_at' => date('Y-m-d H:i:s')
    ], 0, '回复成功');
}

// ============ 提交反馈 ============
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$user) json_response(null, 401, '请先登录');
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $category = $_POST['category'] ?? 'other';

    if (mb_strlen($title) < 4) json_response(null, 1, '标题至少4个字');
    if (mb_strlen($content) < 10) json_response(null, 1, '内容至少10个字');

    $id = $db->insert('feedbacks', [
        'user_id' => $user['id'],
        'title' => $title,
        'content' => $content,
        'category' => $category,
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    json_response(['id' => $id], 0, '提交成功');
}

// ============ GET 列表 ============
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$total = $db->fetchOne('SELECT COUNT(*) as c FROM feedbacks')['c'];
$rows = $db->fetchAll(
    'SELECT f.*, u.username, u.avatar, u.role, (SELECT COUNT(*) FROM feedback_replies r WHERE r.feedback_id = f.id) as reply_count,
     (SELECT COUNT(*) FROM feedback_likes l WHERE l.feedback_id = f.id) as like_count,
     (SELECT COUNT(*) FROM feedback_likes l WHERE l.feedback_id = f.id AND l.user_id = ?) as liked
     FROM feedbacks f JOIN users u ON u.id = f.user_id ORDER BY f.id DESC LIMIT ? OFFSET ?',
    [$user ? $user['id'] : 0, $perPage, $offset]
);

foreach ($rows as &$r) {
    // 取回复：管理员回复置顶，然后其他
    $replies = $db->fetchAll(
        'SELECT r.*, u.username, u.avatar, u.role FROM feedback_replies r JOIN users u ON u.id = r.user_id WHERE r.feedback_id = ? ORDER BY r.is_admin DESC, r.id ASC',
        [$r['id']]
    );
    $r['replies'] = $replies;
    $r['liked'] = !empty($r['liked']);
}
unset($r);

json_response([
    'list' => $rows,
    'total' => intval($total),
    'page' => $page,
    'per_page' => $perPage,
    'total_pages' => ceil($total / $perPage)
]);
