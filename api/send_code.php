<?php
// API: 发送邮箱验证码
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(null, 405, 'Method Not Allowed');
}

$email = trim($_POST['email'] ?? '');
$type = $_POST['type'] ?? 'register';

if ($type === 'register') {
    // 注册前检查邮箱是否已存在
    $db = Database::getInstance();
    $exists = $db->fetchOne('SELECT id FROM users WHERE email = ?', [$email]);
    if ($exists) {
        json_response(null, 1, '该邮箱已注册，请直接登录');
    }
}

$result = send_verification_code($email, $type);
json_response(null, $result['success'] ? 0 : 1, $result['message']);
