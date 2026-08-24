<?php
// API: 登出
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';

logout();
$redirect = $_GET['redirect'] ?? '/login.php';
header('Location: ' . $redirect);
