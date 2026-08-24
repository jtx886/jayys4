<?php
// API: 注册
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(null, 405, 'Method Not Allowed');
}

$email = trim($_POST['email'] ?? '');
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';
$code = trim($_POST['code'] ?? '');
$redirect = $_POST['redirect'] ?? '/';

if ($password !== $confirmPassword) {
    json_response(null, 1, '两次输入的密码不一致');
}

$result = register_user($email, $username, $password, $code);
if ($result['success']) {
    json_response(['redirect' => $redirect], 0, '注册成功');
}
json_response(null, 1, $result['message']);
