<?php
// 公共头部 - TMDB风格导航栏
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
$theme = get_theme_colors();
$user = current_user();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<meta name="theme-color" content="<?= $theme['secondary'] ?>">
<title><?= isset($page_title) ? e($page_title) . ' - ' : '' ?><?= SITE_NAME ?></title>
<link rel="stylesheet" href="/assets/css/main.css?ver=2024">
<link rel="stylesheet" href="/assets/css/icons.css?ver=2024">
<style>
  :root { --primary: <?= $theme['primary'] ?>; --primary-dark: <?= $theme['secondary'] ?>; --primary-gradient: linear-gradient(135deg, <?= $theme['primary'] ?>, <?= $theme['secondary'] ?>); }
</style>
</head>
<body>

<nav class="navbar">
  <div class="navbar-inner">
    <button class="mobile-menu-btn" onclick="toggleMobileNav()"><i class="icon icon-menu"></i></button>
    <a href="/" class="nav-logo">
      <div class="nav-logo-icon">J</div>
      <span class="nav-logo-text">Jay影视</span>
    </a>

    <div class="nav-links">
      <a href="/" class="nav-link <?= ($current_page === 'index.php') ? 'active' : '' ?>">
        <i class="icon icon-home"></i> 首页
      </a>
      <a href="/search.php?type=movie" class="nav-link <?= (isset($_GET['type']) && $_GET['type'] === 'movie') ? 'active' : '' ?>">
        <i class="icon icon-film"></i> 电影
      </a>
      <a href="/search.php?type=tv" class="nav-link <?= (isset($_GET['type']) && $_GET['type'] === 'tv') ? 'active' : '' ?>">
        <i class="icon icon-tv"></i> 电视剧
      </a>
      <a href="/search.php?type=anime" class="nav-link <?= (isset($_GET['type']) && $_GET['type'] === 'anime') ? 'active' : '' ?>">
        <i class="icon icon-anime"></i> 动漫
      </a>
      <a href="/search.php?type=variety" class="nav-link <?= (isset($_GET['type']) && $_GET['type'] === 'variety') ? 'active' : '' ?>">
        <i class="icon icon-variety"></i> 综艺
      </a>
      <a href="/feedback.php" class="nav-link <?= ($current_page === 'feedback.php') ? 'active' : '' ?>">
        <i class="icon icon-message"></i> 反馈
      </a>
    </div>

    <div class="nav-search">
      <form action="/search.php" method="GET" class="nav-search-form">
        <input type="text" name="q" placeholder="搜索电影、电视剧、动漫..." value="<?= isset($_GET['q']) ? e($_GET['q']) : '' ?>">
        <button type="submit"><i class="icon icon-search"></i></button>
      </form>

      <div class="nav-user">
        <?php if ($user): ?>
          <div class="nav-user-menu">
            <div class="nav-user-avatar" title="<?= e($user['username']) ?>">
              <?php if (!empty($user['avatar'])): ?>
                <img src="<?= e($user['avatar']) ?>" alt="">
              <?php else: ?>
                <?= mb_substr($user['username'], 0, 1) ?>
              <?php endif; ?>
            </div>
            <div class="nav-user-dropdown">
              <div class="nav-dropdown-item">
                <div class="nav-user-avatar" style="width:40px;height:40px;">
                  <?php if (!empty($user['avatar'])): ?>
                    <img src="<?= e($user['avatar']) ?>" alt="">
                  <?php else: ?>
                    <?= mb_substr($user['username'], 0, 1) ?>
                  <?php endif; ?>
                </div>
                <div style="flex:1;">
                  <div style="font-weight:600;"><?= e($user['username']) ?><?= $user['role'] === 'admin' ? '<span class="dev-badge"></span>' : '' ?></div>
                  <div style="font-size:12px;color:var(--text-light);"><?= e($user['email']) ?></div>
                </div>
              </div>
              <div class="nav-dropdown-divider"></div>
              <a href="/profile.php" class="nav-dropdown-item"><i class="icon icon-user"></i> 我的主页</a>
              <a href="/profile.php?tab=favorites" class="nav-dropdown-item"><i class="icon icon-heart"></i> 我的收藏</a>
              <a href="/profile.php?tab=history" class="nav-dropdown-item"><i class="icon icon-history"></i> 观看历史</a>
              <?php if (is_admin()): ?>
                <div class="nav-dropdown-divider"></div>
                <a href="/admin/" class="nav-dropdown-item"><i class="icon icon-dashboard"></i> 管理后台</a>
              <?php endif; ?>
              <div class="nav-dropdown-divider"></div>
              <a href="/api/logout.php" class="nav-dropdown-item" style="color:var(--danger);"><i class="icon icon-close"></i> 退出登录</a>
            </div>
          </div>
        <?php else: ?>
          <a href="/login.php" class="btn btn-outline btn-sm" style="border-color:rgba(255,255,255,0.2);color:#fff;">登录</a>
          <a href="/register.php" class="btn btn-primary btn-sm">注册</a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="mobile-nav" id="mobileNav">
    <a href="/" class="mobile-nav-link">首页</a>
    <a href="/search.php?type=movie" class="mobile-nav-link">电影</a>
    <a href="/search.php?type=tv" class="mobile-nav-link">电视剧</a>
    <a href="/search.php?type=anime" class="mobile-nav-link">动漫</a>
    <a href="/search.php?type=variety" class="mobile-nav-link">综艺</a>
    <a href="/feedback.php" class="mobile-nav-link">反馈</a>
    <?php if ($user): ?>
      <a href="/profile.php" class="mobile-nav-link">我的主页</a>
      <?php if (is_admin()): ?>
        <a href="/admin/" class="mobile-nav-link">管理后台</a>
      <?php endif; ?>
      <a href="/api/logout.php" class="mobile-nav-link">退出登录</a>
    <?php else: ?>
      <a href="/login.php" class="mobile-nav-link">登录 / 注册</a>
    <?php endif; ?>
  </div>
</nav>

<script>
function toggleMobileNav() {
  document.getElementById('mobileNav').classList.toggle('open');
}
</script>
