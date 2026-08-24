<?php
// 通用函数库

require_once __DIR__ . '/../config/database.php';

/**
 * 安全输出（防XSS）
 */
function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * 重定向
 */
function redirect($url, $message = '') {
    if ($message) {
        $_SESSION['flash_message'] = $message;
    }
    header('Location: ' . $url);
    exit;
}

/**
 * 获取并清除flash消息
 */
function flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $msg = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $msg;
    }
    return '';
}

/**
 * 生成随机字符串
 */
function str_random($length = 32) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $str = '';
    for ($i = 0; $i < $length; $i++) {
        $str .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    return $str;
}

/**
 * 生成6位数字验证码
 */
function generate_code() {
    return str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * 密码哈希
 */
function password_hash_compat($password) {
    if (function_exists('password_hash')) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    return md5($password . 'jay_video_salt_2024');
}

/**
 * 密码验证
 */
function password_verify_compat($password, $hash) {
    if (function_exists('password_verify')) {
        return password_verify($password, $hash);
    }
    return md5($password . 'jay_video_salt_2024') === $hash;
}

/**
 * 获取当前完整URL
 */
function current_url() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * 获取客户端IP
 */
function get_client_ip() {
    $keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            return explode(',', $_SERVER[$key])[0];
        }
    }
    return '0.0.0.0';
}

/**
 * 检查邮箱格式
 */
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * 时间格式化（友好显示）
 */
function time_ago($datetime) {
    $timestamp = is_numeric($datetime) ? $datetime : strtotime($datetime);
    $diff = time() - $timestamp;
    if ($diff < 60) return $diff . '秒前';
    if ($diff < 3600) return floor($diff / 60) . '分钟前';
    if ($diff < 86400) return floor($diff / 3600) . '小时前';
    if ($diff < 2592000) return floor($diff / 86400) . '天前';
    return date('Y-m-d', $timestamp);
}

/**
 * JSON响应
 */
function json_response($data, $code = 0, $message = 'success') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'code' => $code,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 获取当前登录用户
 */
function current_user() {
    if (empty($_SESSION['user_id'])) return null;
    try {
        $db = Database::getInstance();
        return $db->fetchOne('SELECT * FROM users WHERE id = ?', [$_SESSION['user_id']]);
    } catch (Exception $e) {
        return null;
    }
}

/**
 * 检查是否管理员
 */
function is_admin() {
    try {
        $user = current_user();
        return $user && $user['role'] === 'admin';
    } catch (Exception $e) {
        return false;
    }
}

/**
 * 检查用户是否被封禁
 */
function is_banned($user) {
    if (!$user) return false;
    if (empty($user['banned_until'])) return false;
    try {
        $banTime = strtotime($user['banned_until']);
        return $banTime > time();
    } catch (Exception $e) {
        return false;
    }
}

/**
 * 获取封禁信息
 */
function ban_info($user) {
    if (!is_banned($user)) return null;
    return [
        'reason' => $user['ban_reason'],
        'until' => $user['banned_until']
    ];
}

/**
 * 获取网站设置
 */
function get_site_setting($key, $default = null) {
    try {
        $db = Database::getInstance();
        $row = $db->fetchOne('SELECT setting_value FROM site_settings WHERE setting_key = ?', [$key]);
        if ($row) {
            $val = $row['setting_value'];
            $json = json_decode($val, true);
            return json_last_error() === JSON_ERROR_NONE ? $json : $val;
        }
    } catch (Exception $e) {}
    return $default;
}

/**
 * 设置网站配置
 */
function set_site_setting($key, $value) {
    try {
        $db = Database::getInstance();
        $val = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
        $exists = $db->fetchOne('SELECT id FROM site_settings WHERE setting_key = ?', [$key]);
        if ($exists) {
            return $db->update('site_settings', ['setting_value' => $val], 'setting_key = ?', [$key]);
        } else {
            return $db->insert('site_settings', ['setting_key' => $key, 'setting_value' => $val, 'updated_at' => date('Y-m-d H:i:s')]);
        }
    } catch (Exception $e) {
        return false;
    }
}

/**
 * 获取主题色
 */
function get_theme_colors() {
    return [
        'primary' => get_site_setting('theme_primary_color', DEFAULT_PRIMARY_COLOR),
        'secondary' => get_site_setting('theme_secondary_color', DEFAULT_SECONDARY_COLOR)
    ];
}

/**
 * TMDB API请求封装（带缓存）
 * 优先使用Bearer Token认证，失败时回退API Key
 */
function tmdb_request($endpoint, $params = []) {
    if (empty(TMDB_API_KEY) && !defined('TMDB_BEARER_TOKEN') || (defined('TMDB_BEARER_TOKEN') && empty(TMDB_BEARER_TOKEN))) {
        return null;
    }

    if (!isset($params['language'])) $params['language'] = TMDB_LANG;
    if (!isset($params['region'])) $params['region'] = TMDB_REGION;

    $cacheKey = md5($endpoint . '?' . http_build_query($params));
    $cacheDir = dirname(__DIR__) . '/cache/tmdb';
    $cacheFile = $cacheDir . '/' . $cacheKey . '.json';

    // 简单文件缓存 24小时
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 86400) {
        $cached = @file_get_contents($cacheFile);
        if ($cached) return json_decode($cached, true);
    }

    // ===== 优先使用 Bearer Token 认证方式 =====
    $useBearer = defined('TMDB_BEARER_TOKEN') && !empty(TMDB_BEARER_TOKEN);
    if ($useBearer) {
        $url = TMDB_BASE_URL . $endpoint . '?' . http_build_query($params);
    } else {
        $params['api_key'] = TMDB_API_KEY;
        $url = TMDB_BASE_URL . $endpoint . '?' . http_build_query($params);
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    // 设置浏览器UA避免被拦截
    curl_setopt($ch, CURLOPT_USERAGENT, 'JayVideo/1.0 (TMDB API Client)');
    curl_setopt($ch, CURLOPT_ACCEPT_ENCODING, 'gzip, deflate');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    if ($useBearer) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Authorization: Bearer ' . TMDB_BEARER_TOKEN,
        ]);
    }

    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    // Bearer失败，回退使用API Key方式
    if ($useBearer && ($httpCode !== 200 || !$result) && !empty(TMDB_API_KEY)) {
        $params['api_key'] = TMDB_API_KEY;
        $url2 = TMDB_BASE_URL . $endpoint . '?' . http_build_query($params);

        $ch2 = curl_init();
        curl_setopt($ch2, CURLOPT_URL, $url2);
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch2, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch2, CURLOPT_USERAGENT, 'JayVideo/1.0 (TMDB API Client)');
        curl_setopt($ch2, CURLOPT_FOLLOWLOCATION, true);
        $result = curl_exec($ch2);
        curl_close($ch2);
    }

    if ($result) {
        if (!is_dir($cacheDir)) @mkdir($cacheDir, 0755, true);
        @file_put_contents($cacheFile, $result);
        return json_decode($result, true);
    }
    return null;
}

/**
 * TMDB图片URL
 */
function tmdb_image($path, $size = 'w500') {
    if (empty($path)) return '';
    return TMDB_IMAGE_BASE . '/' . $size . $path;
}

/**
 * 获取播放源列表（从数据库，可后台管理）
 */
function get_play_sources() {
    try {
        $db = Database::getInstance();
        return $db->fetchAll('SELECT * FROM play_sources WHERE status = 1 ORDER BY sort_order ASC, id ASC');
    } catch (Exception $e) {
        return [];
    }
}

/**
 * 通过API搜索匹配播放源
 */
function search_video_source($keyword, $type = 'all') {
    $url = SOURCE_API . '?ac=detail&wd=' . urlencode($keyword);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $result = curl_exec($ch);
    curl_close($ch);
    if (!$result) return [];
    $data = json_decode($result, true);
    return !empty($data['list']) ? $data['list'] : [];
}

/**
 * 分页计算
 */
function paginate($total, $page, $perPage) {
    $totalPages = ceil($total / $perPage);
    return [
        'total' => $total,
        'per_page' => $perPage,
        'current_page' => $page,
        'total_pages' => $totalPages,
        'offset' => ($page - 1) * $perPage
    ];
}
