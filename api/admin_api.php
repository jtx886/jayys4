<?php
// 管理后台综合API
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';

$admin = current_user();
if (!$admin || $admin['role'] !== 'admin') {
    json_response(null, 403, '无权访问');
}
$db = Database::getInstance();
$action = $_REQUEST['action'] ?? '';

// ===== 角色检查 =====
if ($action === 'check_role') {
    json_response(['is_admin' => true, 'user' => ['username'=>$admin['username'],'email'=>$admin['email']]]);
}

// ===== 封禁用户 =====
if ($action === 'ban_user' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = intval($_POST['user_id'] ?? 0);
    $reason = trim($_POST['reason'] ?? '违反平台规定');
    $until = trim($_POST['until'] ?? '');  // Y-m-d H:i:s 或 duration
    $duration = intval($_POST['duration'] ?? 0); // 小时

    if ($userId <= 0 || $userId == $admin['id']) json_response(null, 1, '参数错误');

    $u = $db->fetchOne('SELECT * FROM users WHERE id = ?', [$userId]);
    if (!$u) json_response(null, 1, '用户不存在');

    if ($duration > 0) {
        $untilTime = date('Y-m-d H:i:s', time() + $duration * 3600);
    } elseif (!empty($until)) {
        $untilTime = date('Y-m-d H:i:s', strtotime($until));
    } else {
        $untilTime = date('Y-m-d H:i:s', time() + 24 * 3600);
    }

    $now = date('Y-m-d H:i:s');
    $db->update('users', [
        'banned_until' => $untilTime,
        'ban_reason' => $reason,
        'updated_at' => $now
    ], 'id = ?', [$userId]);

    // 写封禁日志
    $db->insert('ban_logs', [
        'user_id' => $userId,
        'operator_id' => $admin['id'],
        'reason' => $reason,
        'banned_at' => $now,
        'banned_until' => $untilTime,
        'email_sent' => 0
    ]);

    // 发邮件通知
    $emailSent = false;
    if (!empty($u['email'])) {
        $sent = Mailer::send($u['email'], '【' . SITE_NAME . '】账号封禁通知',
            Mailer::banNotification($reason, $now, $untilTime));
        if ($sent) $emailSent = true;
    }
    if ($emailSent) {
        $db->update('ban_logs', ['email_sent' => 1], 'user_id = ? AND banned_at = ?', [$userId, $now]);
    }

    json_response(['until' => $untilTime, 'email_sent' => $emailSent], 0, '封禁成功');
}

// ===== 解封用户 =====
if ($action === 'unban_user' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = intval($_POST['user_id'] ?? 0);
    if ($userId <= 0) json_response(null, 1, '参数错误');
    $db->update('users', [
        'banned_until' => null,
        'ban_reason' => '',
        'updated_at' => date('Y-m-d H:i:s')
    ], 'id = ?', [$userId]);
    json_response(null, 0, '已解封');
}

// ===== 保存/更新播放源 =====
if ($action === 'save_source' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $data = [
        'name' => trim($_POST['name'] ?? ''),
        'api_url' => trim($_POST['api_url'] ?? ''),
        'type' => $_POST['type'] ?? 'general',
        'sort_order' => intval($_POST['sort_order'] ?? 0),
        'status' => intval($_POST['status'] ?? 1),
        'remark' => trim($_POST['remark'] ?? ''),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    if (empty($data['name']) || empty($data['api_url'])) json_response(null, 1, '名称和API地址不能为空');
    if ($id > 0) {
        $db->update('play_sources', $data, 'id = ?', [$id]);
    } else {
        $data['created_at'] = date('Y-m-d H:i:s');
        $id = $db->insert('play_sources', $data);
    }
    json_response(['id' => $id], 0, '保存成功');
}

// ===== 删除播放源 =====
if ($action === 'delete_source' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) json_response(null, 1, '参数错误');
    $db->delete('play_sources', 'id = ?', [$id]);
    json_response(null, 0, '删除成功');
}

// ===== 保存公告 =====
if ($action === 'save_announcement' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $content = $_POST['content'] ?? '';
    $status = intval($_POST['status'] ?? 1);
    if (empty($title) || empty($content)) json_response(null, 1, '标题和内容不能为空');
    if ($id > 0) {
        $db->update('announcements', [
            'title' => $title,
            'content' => $content,
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$id]);
    } else {
        $id = $db->insert('announcements', [
            'title' => $title,
            'content' => $content,
            'status' => $status,
            'created_by' => $admin['id'],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
    json_response(['id' => $id], 0, '保存成功');
}

// ===== 删除公告 =====
if ($action === 'delete_announcement' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) json_response(null, 1, '参数错误');
    $db->delete('announcements', 'id = ?', [$id]);
    $db->delete('announcement_dismissals', 'announcement_id = ?', [$id]);
    json_response(null, 0, '删除成功');
}

// ===== 更新反馈状态 =====
if ($action === 'update_feedback_status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? 'pending';
    if (!in_array($status, ['pending','replied','resolved','closed'])) json_response(null, 1, '状态错误');
    $db->update('feedbacks', ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
    json_response(null, 0, '更新成功');
}

// ===== 回复反馈（管理员） =====
if ($action === 'reply_feedback' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $content = trim($_POST['content'] ?? '');
    if ($id <= 0 || mb_strlen($content) < 2) json_response(null, 1, '回复内容至少2字');
    $rid = $db->insert('feedback_replies', [
        'feedback_id' => $id,
        'user_id' => $admin['id'],
        'content' => $content,
        'is_admin' => 1,
        'created_at' => date('Y-m-d H:i:s')
    ]);
    $db->update('feedbacks', ['status' => 'replied', 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
    // 发邮件
    $fb = $db->fetchOne('SELECT f.title, u.email FROM feedbacks f JOIN users u ON u.id = f.user_id WHERE f.id = ?', [$id]);
    if ($fb && !empty($fb['email'])) {
        Mailer::send($fb['email'], '【' . SITE_NAME . '】您的反馈有新回复', Mailer::feedbackReply($fb['title'], $content));
    }
    json_response(['id' => $rid], 0, '回复成功');
}

// ===== 保存主题色 =====
if ($action === 'save_theme' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $primary = trim($_POST['primary'] ?? '');
    $secondary = trim($_POST['secondary'] ?? '');
    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $primary) || !preg_match('/^#[0-9a-fA-F]{6}$/', $secondary)) {
        json_response(null, 1, '颜色格式不正确，请使用#RRGGBB格式');
    }
    set_site_setting('theme_primary_color', $primary);
    set_site_setting('theme_secondary_color', $secondary);
    json_response(null, 0, '主题已保存');
}

// ===== 给用户/所有人发邮件 =====
if ($action === 'send_mail' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $target = $_POST['target'] ?? 'all';  // all / 指定用户email / 指定user_id
    $toEmail = trim($_POST['email'] ?? '');
    $userId = intval($_POST['user_id'] ?? 0);
    $subject = trim($_POST['subject'] ?? '');
    $content = $_POST['content'] ?? '';
    if (empty($subject) || empty($content)) json_response(null, 1, '主题和内容不能为空');

    $emails = [];
    if ($target === 'all') {
        $rows = $db->fetchAll('SELECT email FROM users WHERE email IS NOT NULL AND email != ""');
        foreach ($rows as $r) $emails[] = $r['email'];
    } elseif ($target === 'email') {
        if (is_valid_email($toEmail)) $emails[] = $toEmail;
    } elseif ($target === 'user') {
        $u = $db->fetchOne('SELECT email FROM users WHERE id = ?', [$userId]);
        if ($u && !empty($u['email'])) $emails[] = $u['email'];
    }
    if (empty($emails)) json_response(null, 1, '未找到收件人');
    $success = 0;
    $body = Mailer::adminNotification($content);
    foreach ($emails as $e) {
        if (Mailer::send($e, '【' . SITE_NAME . '】' . $subject, $body)) $success++;
    }
    json_response(['sent' => $success, 'total' => count($emails)], 0, '发送完成');
}

json_response(null, 404, 'Unknown action: ' . $action);
