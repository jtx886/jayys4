<?php
// Jay影视 - 主配置文件

// 数据库配置 (MySQL)
// InfinityFree部署时修改为主机提供商给的主机名、用户名、密码、库名
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'jay_video');
define('DB_USER', 'jay_user');
define('DB_PASS', 'Jtx101113@');
define('DB_CHARSET', 'utf8mb4');
// 可选：Unix Socket路径（本地测试环境root用户走socket，部署时留空即可）
define('DB_SOCKET', '');

// SMTP配置 - 163邮箱
define('SMTP_HOST', 'smtp.163.com');
define('SMTP_PORT', 465);
define('SMTP_USER', 'jtxnb886@163.com');
define('SMTP_PASS', 'FLLRDtadYAfGXp9Y');
define('SMTP_FROM', 'jtxnb886@163.com');
define('SMTP_FROM_NAME', 'Jay影视');

// TMDB API配置
define('TMDB_API_KEY', 'cb44223c5dee5676ed3a839f42ed27e3'); // 杰同学的TMDB API Key
define('TMDB_BEARER_TOKEN', 'eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJjYjQ0MjIzYzVkZWU1Njc2ZWQzYTM5ZjQyZWQyN2UzIiwiZXhwIjoxOTk3NzkyODA4LCJzdWIiOiI2OWFjYmVlYmM3NzExYTg5ZWI4ZjRmZTk5YTRjZjU2ZWNkYzFhMWMyZjc0NjI0In0.95UWM3wql05P9SnJf0Py9NNjMikjsXSNGX7a6i6t4qs'); // 杰同学的API读访问令牌
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
