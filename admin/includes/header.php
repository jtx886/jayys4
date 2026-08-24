<?php
// 管理后台 - 公共头部与侧边栏
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/auth.php';

// 后台登录页面单独处理
if (strpos($_SERVER['PHP_SELF'], '/admin/login.php') === false) {
    // 必须是管理员
    if (empty($_SESSION['user_id'])) { header('Location: /admin/login.php'); exit; }
    $adminUser = current_user();
    if (!$adminUser || $adminUser['role'] !== 'admin') {
        session_destroy();
        header('Location: /admin/login.php');
        exit;
    }
}

$pageTitle = $page_title ?? '管理后台';
$activeMenu = $active_menu ?? 'dashboard';
$theme = get_theme_colors();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?> - <?= SITE_NAME ?> 管理后台</title>
<link rel="stylesheet" href="/assets/css/main.css?ver=2024">
<link rel="stylesheet" href="/assets/css/icons.css?ver=2024">
<style>
  :root { --primary: <?= $theme['primary'] ?>; --primary-dark: <?= $theme['secondary'] ?>; --primary-gradient: linear-gradient(135deg, <?= $theme['primary'] ?>, <?= $theme['secondary'] ?>); }
</style>
</head>
<body>
<div class="admin-page">
<?php if (strpos($_SERVER['PHP_SELF'], '/admin/login.php') === false): ?>
  <!-- 侧边栏 -->
  <aside class="admin-sidebar" id="adminSidebar">
    <div class="admin-logo">
      <div class="admin-logo-icon">J</div>
      <div class="admin-logo-text">
        Jay影视
        <small>管理后台</small>
      </div>
    </div>
    <nav class="admin-menu">
      <div class="admin-menu-group">
        <div class="admin-menu-title">主菜单</div>
        <a href="/admin/" class="admin-menu-item <?= ($activeMenu === 'dashboard') ? 'active' : '' ?>">
          <i class="icon icon-dashboard"></i> 仪表盘
        </a>
      </div>
      <div class="admin-menu-group">
        <div class="admin-menu-title">用户</div>
        <a href="/admin/users.php" class="admin-menu-item <?= ($activeMenu === 'users') ? 'active' : '' ?>">
          <i class="icon icon-users"></i> 用户管理
        </a>
        <a href="/admin/history.php" class="admin-menu-item <?= ($activeMenu === 'history') ? 'active' : '' ?>">
          <i class="icon icon-history"></i> 观看历史
        </a>
        <a href="/admin/favorites.php" class="admin-menu-item <?= ($activeMenu === 'favorites') ? 'active' : '' ?>">
          <i class="icon icon-heart"></i> 用户收藏
        </a>
      </div>
      <div class="admin-menu-group">
        <div class="admin-menu-title">内容</div>
        <a href="/admin/sources.php" class="admin-menu-item <?= ($activeMenu === 'sources') ? 'active' : '' ?>">
          <i class="icon icon-play"></i> 播放源管理
        </a>
        <a href="/admin/announcements.php" class="admin-menu-item <?= ($activeMenu === 'announcements') ? 'active' : '' ?>">
          <i class="icon icon-bullhorn"></i> 公告管理
        </a>
        <a href="/admin/feedback.php" class="admin-menu-item <?= ($activeMenu === 'feedback') ? 'active' : '' ?>">
          <i class="icon icon-message"></i> 反馈管理
        </a>
      </div>
      <div class="admin-menu-group">
        <div class="admin-menu-title">系统</div>
        <a href="/admin/mail.php" class="admin-menu-item <?= ($activeMenu === 'mail') ? 'active' : '' ?>">
          <i class="icon icon-mail"></i> 邮件通知
        </a>
        <a href="/admin/theme.php" class="admin-menu-item <?= ($activeMenu === 'theme') ? 'active' : '' ?>">
          <i class="icon icon-palette"></i> 主题设置
        </a>
      </div>
      <div class="admin-menu-group" style="margin-top:auto;padding-top:16px;">
        <a href="/" class="admin-menu-item"><i class="icon icon-home"></i> 访问前台</a>
        <a href="/api/logout.php?redirect=/admin/login.php" class="admin-menu-item" style="color:#f87171;"><i class="icon icon-close"></i> 退出登录</a>
      </div>
    </nav>
  </aside>

  <!-- 主区 -->
  <div class="admin-main">
    <div class="admin-topbar">
      <button class="btn btn-outline btn-sm" onclick="document.getElementById('adminSidebar').classList.toggle('open')" style="display:none;" id="sidebarToggle">
        <i class="icon icon-menu"></i>
      </button>
      <div class="admin-topbar-title">
        <?= e($pageTitle) ?>
      </div>
      <div class="admin-topbar-user">
        <div class="table-user">
          <div class="table-avatar">
            <?php if (!empty($adminUser['avatar'])): ?>
              <img src="<?= e($adminUser['avatar']) ?>" alt="">
            <?php else: ?>
              <?= mb_substr($adminUser['username'], 0, 1) ?>
            <?php endif; ?>
          </div>
          <div>
            <div class="table-user-name" style="font-size:14px;">
              <?= e($adminUser['username']) ?>
              <span class="dev-badge"></span>
            </div>
            <div class="table-user-email" style="font-size:11px;"><?= e($adminUser['email']) ?></div>
          </div>
        </div>
      </div>
    </div>
    <div class="admin-content">
<?php endif; ?>
