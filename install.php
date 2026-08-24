<?php
/**
 * Jay影视 数据库安装脚本
 * 首次访问此文件将自动创建所有数据库表和默认管理员账号
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$messages = [];
$pdo = null;
try {
    if (Database::isConnected()) {
        $db = Database::getInstance();
        $pdo = $db->getConnection();
    } else {
        $messages[] = '✗ 数据库连接失败，请检查 /config/config.php 中的数据库配置';
        if (Database::getConnectionError()) {
            $messages[] = '  错误详情: ' . Database::getConnectionError();
        }
    }
} catch (Exception $e) {
    $messages[] = '✗ 数据库连接失败: ' . $e->getMessage();
    $pdo = null;
}


if ($pdo) {

// 1. 创建数据库（如不存在）
try {
    $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` DEFAULT CHARACTER SET ' . DB_CHARSET . ' COLLATE ' . DB_CHARSET . '_unicode_ci');
    $pdo->exec('USE `' . DB_NAME . '`');
    $messages[] = '✓ 数据库检查完成';
} catch (PDOException $e) {
    $messages[] = '✗ 数据库创建失败: ' . $e->getMessage();
}

// 2. 数据表
$sqlStatements = [

// 用户表
"CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(191) NOT NULL,
  `username` VARCHAR(50) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `avatar` VARCHAR(255) DEFAULT '',
  `role` ENUM('admin','user') NOT NULL DEFAULT 'user',
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `banned_until` DATETIME DEFAULT NULL,
  `ban_reason` VARCHAR(255) DEFAULT '',
  `last_login_at` DATETIME DEFAULT NULL,
  `last_login_ip` VARCHAR(45) DEFAULT '',
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_email` (`email`),
  UNIQUE KEY `uk_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET,

// 邮箱验证码表
"CREATE TABLE IF NOT EXISTS `verification_codes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(191) NOT NULL,
  `code` VARCHAR(10) NOT NULL,
  `type` VARCHAR(20) NOT NULL DEFAULT 'register',
  `expires_at` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_email_type` (`email`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET,

// 播放源表
"CREATE TABLE IF NOT EXISTS `play_sources` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `api_url` VARCHAR(500) NOT NULL,
  `type` ENUM('general','parse') NOT NULL DEFAULT 'general',
  `sort_order` INT NOT NULL DEFAULT 0,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `remark` VARCHAR(255) DEFAULT '',
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET,

// 收藏表
"CREATE TABLE IF NOT EXISTS `favorites` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `media_type` VARCHAR(20) NOT NULL DEFAULT 'movie',
  `media_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `poster_path` VARCHAR(255) DEFAULT '',
  `year` VARCHAR(20) DEFAULT '',
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_media` (`user_id`,`media_type`,`media_id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET,

// 观看历史表
"CREATE TABLE IF NOT EXISTS `watch_history` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `media_type` VARCHAR(20) NOT NULL DEFAULT 'movie',
  `media_id` INT NOT NULL,
  `episode_id` VARCHAR(100) DEFAULT '',
  `title` VARCHAR(255) NOT NULL,
  `poster_path` VARCHAR(255) DEFAULT '',
  `season_number` INT DEFAULT 0,
  `episode_number` INT DEFAULT 0,
  `watched_seconds` INT NOT NULL DEFAULT 0,
  `total_seconds` INT NOT NULL DEFAULT 0,
  `last_position` INT NOT NULL DEFAULT 0,
  `source_url` VARCHAR(500) DEFAULT '',
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_user_media` (`user_id`,`media_type`,`media_id`)
) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET,

// 公告表
"CREATE TABLE IF NOT EXISTS `announcements` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `content` TEXT NOT NULL,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET,

// 公告已读（不再提示）
"CREATE TABLE IF NOT EXISTS `announcement_dismissals` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `announcement_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `dismissed_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ann_user` (`announcement_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET,

// 反馈表
"CREATE TABLE IF NOT EXISTS `feedbacks` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `content` TEXT NOT NULL,
  `category` VARCHAR(50) NOT NULL DEFAULT 'other',
  `status` ENUM('pending','replied','resolved','closed') NOT NULL DEFAULT 'pending',
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET,

// 反馈回复表
"CREATE TABLE IF NOT EXISTS `feedback_replies` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `feedback_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `content` TEXT NOT NULL,
  `is_admin` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_feedback` (`feedback_id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET,

// 反馈点赞表
"CREATE TABLE IF NOT EXISTS `feedback_likes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `feedback_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_feedback_user` (`feedback_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET,

// 网站设置表
"CREATE TABLE IF NOT EXISTS `site_settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET,

// 用户会话表（可选，用于封禁通知追踪）
"CREATE TABLE IF NOT EXISTS `ban_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `operator_id` INT UNSIGNED NOT NULL,
  `reason` VARCHAR(255) NOT NULL,
  `banned_at` DATETIME NOT NULL,
  `banned_until` DATETIME NOT NULL,
  `email_sent` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET
];

foreach ($sqlStatements as $sql) {
    try {
        $pdo->exec($sql);
    } catch (PDOException $e) {
        $messages[] = '✗ 建表失败: ' . $e->getMessage();
    }
}
$messages[] = '✓ 数据表创建完成';

// 3. 默认管理员账号：杰同学 / 101113
try {
    $admin = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $admin->execute(['杰同学', 'admin@jay.com']);
    if (!$admin->fetch()) {
        $hash = password_hash_compat('101113');
        $stmt = $pdo->prepare("INSERT INTO users (email, username, password, role, status, created_at, updated_at) VALUES (?, ?, ?, 'admin', 'active', NOW(), NOW())");
        $stmt->execute(['admin@jay.com', '杰同学', $hash]);
        $messages[] = '✓ 默认管理员账号创建成功：杰同学 / 101113';
    } else {
        $messages[] = '✓ 管理员账号已存在';
    }
} catch (PDOException $e) {
    $messages[] = '✗ 管理员账号创建失败: ' . $e->getMessage();
}

// 4. 默认播放源
try {
    $cnt = $pdo->query("SELECT COUNT(*) FROM play_sources")->fetchColumn();
    if ($cnt == 0) {
        $stmt = $pdo->prepare("INSERT INTO play_sources (name, api_url, type, sort_order, status, remark, created_at, updated_at) VALUES (?, ?, 'general', 0, 1, ?, NOW(), NOW())");
        $stmt->execute(['主播放源', SOURCE_API, '默认API接口']);
        $messages[] = '✓ 默认播放源已添加';
    }
} catch (PDOException $e) {
    $messages[] = '✗ 默认播放源添加失败: ' . $e->getMessage();
}

// 5. 默认主题设置
try {
    $check = $pdo->prepare("SELECT id FROM site_settings WHERE setting_key = 'theme_primary_color'");
    $check->execute();
    if (!$check->fetch()) {
        $pdo->query("INSERT INTO site_settings (setting_key, setting_value, updated_at) VALUES ('theme_primary_color', '#01B4E4', NOW())");
        $pdo->query("INSERT INTO site_settings (setting_key, setting_value, updated_at) VALUES ('theme_secondary_color', '#032541', NOW())");
        $messages[] = '✓ 默认主题设置完成';
    }
} catch (PDOException $e) {
    $messages[] = '✗ 主题设置失败: ' . $e->getMessage();
}

// 默认公告
try {
    $cnt = $pdo->query("SELECT COUNT(*) FROM announcements")->fetchColumn();
    if ($cnt == 0) {
        $stmt = $pdo->prepare("INSERT INTO announcements (title, content, status, created_by, created_at, updated_at) VALUES (?, ?, 1, 1, NOW(), NOW())");
        $stmt->execute(['欢迎来到Jay影视', '<p>欢迎使用Jay影视，本站是一个免费的在线影视平台。</p><p>如果遇到问题，请在反馈区留言，感谢支持！</p>']);
        $messages[] = '✓ 默认公告已添加';
    }
} catch (PDOException $e) {}

} // 结束 if ($pdo) 块

// 缓存目录
@mkdir(__DIR__ . '/cache', 0755, true);
@mkdir(__DIR__ . '/cache/tmdb', 0755, true);

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>Jay影视 - 安装向导</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: -apple-system, "PingFang SC", "Microsoft YaHei", sans-serif; background: linear-gradient(135deg, #032541 0%, #01B4E4 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
.card { background: #fff; border-radius: 16px; padding: 40px; max-width: 600px; width: 100%; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
.logo { width: 80px; height: 80px; margin: 0 auto 20px; background: linear-gradient(135deg, #01B4E4, #032541); border-radius: 20px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 40px; font-weight: bold; }
h1 { text-align: center; color: #032541; font-size: 28px; margin-bottom: 8px; }
.subtitle { text-align: center; color: #888; margin-bottom: 30px; }
.msgs { background: #f7f9fc; border-radius: 10px; padding: 20px; margin-bottom: 24px; max-height: 300px; overflow-y: auto; }
.msg { padding: 6px 0; color: #333; font-size: 14px; }
.btn { display: block; width: 100%; padding: 14px; background: linear-gradient(135deg, #01B4E4, #032541); color: #fff; border: none; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; text-decoration: none; text-align: center; transition: transform 0.2s; }
.btn:hover { transform: translateY(-2px); }
.warning { background: #fff7e6; border: 1px solid #ffd591; color: #d46b08; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; line-height: 1.6; }
</style>
</head>
<body>
<div class="card">
    <div class="logo">J</div>
    <h1>Jay影视 安装完成</h1>
    <p class="subtitle">数据库初始化成功</p>
    <?php if (empty(TMDB_API_KEY)): ?>
    <div class="warning">
        ⚠️ 检测到尚未配置 TMDB API Key，请在 <code>/config/config.php</code> 中填入您的 TMDB API Key 后再使用首页搜索功能。
        <br>申请地址：<a href="https://www.themoviedb.org/settings/api" target="_blank">https://www.themoviedb.org/settings/api</a>
    </div>
    <?php endif; ?>
    <div class="msgs">
        <?php foreach ($messages as $m): ?>
            <div class="msg"><?= $m ?></div>
        <?php endforeach; ?>
    </div>
    <a href="/" class="btn">进入网站首页 →</a>
    <a href="/admin/login.php" class="btn" style="margin-top:12px;background:linear-gradient(135deg,#e74c3c,#c0392b);">进入管理后台 →</a>
</div>
</body>
</html>
