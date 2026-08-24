<?php
// Jay影视 - 主配置文件

// 数据库配置
define('DB_HOST', 'localhost');
define('DB_NAME', 'jay_video');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// SMTP配置 - 163邮箱
define('SMTP_HOST', 'smtp.163.com');
define('SMTP_PORT', 465);
define('SMTP_USER', 'jtxnb886@163.com');
define('SMTP_PASS', 'FLLRDtadYAfGXp9Y');
define('SMTP_FROM', 'jtxnb886@163.com');
define('SMTP_FROM_NAME', 'Jay影视');

// TMDB API配置
define('TMDB_API_KEY', ''); // 用户需要在此填入自己的TMDB API Key
define('TMDB_LANG', 'zh-CN');
define('TMDB_REGION', 'CN');
define('TMDB_BASE_URL', 'https://api.themoviedb.org/3');
define('TMDB_IMAGE_BASE', 'https://image.tmdb.org/t/p');

// 解析播放器
define('PLAYER_URL', 'https://svip.ffzyplay.com/?url=');

// 播放源API
define('SOURCE_API', 'https://api.yyzy-tv.vip/inc/apijson.php');

// 网站基础信息
define('SITE_NAME', 'Jay影视');
define('SITE_URL', 'http://localhost');

// 默认主题色
define('DEFAULT_PRIMARY_COLOR', '#01B4E4'); // TMDB蓝
define('DEFAULT_SECONDARY_COLOR', '#032541'); // TMDB深蓝

// Session
define('SESSION_NAME', 'jay_video_session');

// 上传目录
define('UPLOAD_DIR', dirname(__DIR__) . '/uploads');
define('AVATAR_DIR', UPLOAD_DIR . '/avatars');

// 时间设置
date_default_timezone_set('Asia/Shanghai');

// 错误显示（部署时可关闭）
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 确保上传目录存在
if (!file_exists(UPLOAD_DIR)) {
    @mkdir(UPLOAD_DIR, 0755, true);
}
if (!file_exists(AVATAR_DIR)) {
    @mkdir(AVATAR_DIR, 0755, true);
}
