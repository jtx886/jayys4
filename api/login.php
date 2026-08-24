<?php
// API: 登录
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(null, 405, 'Method Not Allowed');
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$redirect = $_POST['redirect'] ?? '/';

if (empty($email) || empty($password)) {
    json_response(null, 1, '请填写完整的登录信息');
}

$result = attempt_login($email, $password);
if ($result['success']) {
    json_response(['redirect' => $redirect], 0, '登录成功');
}
json_response(null, 1, $result['message']);
