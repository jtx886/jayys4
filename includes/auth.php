<?php
// 认证相关函数

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/../config/database.php';

/**
 * 要求登录
 */
function require_login($redirect = true) {
    if (empty($_SESSION['user_id'])) {
        if ($redirect) {
            redirect('/login.php?redirect=' . urlencode(current_url()), '需要登录才可以观看哦，如没有账号请注册！');
        }
        return false;
    }
    $user = current_user();
    if (!$user) {
        session_destroy();
        if ($redirect) {
            redirect('/login.php', '用户不存在，请重新登录');
        }
        return false;
    }
    if (is_banned($user)) {
        $info = ban_info($user);
        session_destroy();
        if ($redirect) {
            redirect('/login.php', '账号已被封禁，原因：' . $info['reason'] . '，解除时间：' . $info['until']);
        }
        return false;
    }
    return true;
}

/**
 * 要求管理员
 */
function require_admin() {
    if (!require_login(false)) {
        redirect('/admin/login.php');
    }
    if (!is_admin()) {
        redirect('/', '无权访问管理后台');
    }
}

/**
 * 尝试登录用户
 */
function attempt_login($email, $password) {
    $db = Database::getInstance();
    $user = $db->fetchOne('SELECT * FROM users WHERE email = ?', [$email]);
    if (!$user) return ['success' => false, 'message' => '邮箱或密码错误'];

    if (!password_verify_compat($password, $user['password'])) {
        return ['success' => false, 'message' => '邮箱或密码错误'];
    }

    if (is_banned($user)) {
        $info = ban_info($user);
        return ['success' => false, 'message' => '账号已被封禁，原因：' . $info['reason'] . '，解除时间：' . $info['until']];
    }

    $_SESSION['user_id'] = $user['id'];
    $db->update('users', [
        'last_login_at' => date('Y-m-d H:i:s'),
        'last_login_ip' => get_client_ip()
    ], 'id = ?', [$user['id']]);

    return ['success' => true, 'user' => $user];
}

/**
 * 发送邮箱验证码
 */
function send_verification_code($email, $type = 'register') {
    if (!is_valid_email($email)) {
        return ['success' => false, 'message' => '邮箱格式不正确'];
    }

    $db = Database::getInstance();

    // 检查是否频繁发送
    $recent = $db->fetchOne(
        'SELECT * FROM verification_codes WHERE email = ? AND type = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)',
        [$email, $type]
    );
    if ($recent) {
        return ['success' => false, 'message' => '发送过于频繁，请1分钟后再试'];
    }

    // 生成验证码
    $code = generate_code();
    $expireAt = date('Y-m-d H:i:s', time() + 600); // 10分钟

    // 删除旧的
    $db->delete('verification_codes', 'email = ? AND type = ?', [$email, $type]);

    // 保存新的
    $db->insert('verification_codes', [
        'email' => $email,
        'code' => $code,
        'type' => $type,
        'expires_at' => $expireAt,
        'created_at' => date('Y-m-d H:i:s')
    ]);

    // 发送邮件
    $subject = ($type === 'register' ? '【' . SITE_NAME . '】注册验证码' : '【' . SITE_NAME . '】邮箱验证码');
    $body = Mailer::verificationEmail($code);
    $sent = Mailer::send($email, $subject, $body);

    if (!$sent) {
        return ['success' => false, 'message' => '邮件发送失败，请稍后重试'];
    }

    return ['success' => true, 'message' => '验证码已发送，请查收邮箱（可能在垃圾箱）'];
}

/**
 * 验证验证码
 */
function verify_code($email, $code, $type = 'register') {
    $db = Database::getInstance();
    $record = $db->fetchOne(
        'SELECT * FROM verification_codes WHERE email = ? AND code = ? AND type = ? AND expires_at > NOW() ORDER BY id DESC LIMIT 1',
        [$email, $code, $type]
    );
    if (!$record) return false;
    // 使用后删除
    $db->delete('verification_codes', 'id = ?', [$record['id']]);
    return true;
}

/**
 * 注册用户
 */
function register_user($email, $username, $password, $code) {
    $db = Database::getInstance();

    // 验证
    if (!is_valid_email($email)) return ['success' => false, 'message' => '邮箱格式不正确'];
    if (mb_strlen($username) < 2 || mb_strlen($username) > 20) return ['success' => false, 'message' => '用户名长度为2-20字符'];
    if (strlen($password) < 6) return ['success' => false, 'message' => '密码至少6位'];

    // 验证码
    if (!verify_code($email, $code)) {
        return ['success' => false, 'message' => '验证码错误或已过期'];
    }

    // 检查邮箱是否已注册
    $exists = $db->fetchOne('SELECT id FROM users WHERE email = ?', [$email]);
    if ($exists) return ['success' => false, 'message' => '该邮箱已注册'];

    // 检查用户名
    $exists = $db->fetchOne('SELECT id FROM users WHERE username = ?', [$username]);
    if ($exists) return ['success' => false, 'message' => '用户名已被使用'];

    // 创建用户
    $userId = $db->insert('users', [
        'email' => $email,
        'username' => $username,
        'password' => password_hash_compat($password),
        'avatar' => '',
        'role' => 'user',
        'status' => 'active',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]);

    $_SESSION['user_id'] = $userId;
    return ['success' => true, 'user_id' => $userId];
}

/**
 * 登出
 */
function logout() {
    $_SESSION = [];
    session_destroy();
}
