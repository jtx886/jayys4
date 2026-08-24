<?php
// 登录页面
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';
if (!empty($_SESSION['user_id'])) { header('Location: /'); exit; }
$page_title = '登录';
$theme = get_theme_colors();
$flash = flash_message();
$redirect = $_GET['redirect'] ?? '/';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>登录 - <?= SITE_NAME ?></title>
<link rel="stylesheet" href="/assets/css/main.css?ver=2024">
<link rel="stylesheet" href="/assets/css/icons.css?ver=2024">
<style>
  :root { --primary: <?= $theme['primary'] ?>; --primary-dark: <?= $theme['secondary'] ?>; --primary-gradient: linear-gradient(135deg, <?= $theme['primary'] ?>, <?= $theme['secondary'] ?>); }
</style>
</head>
<body>
<div class="auth-page">
  <div class="auth-card">
    <div class="auth-logo">
      <div class="auth-logo-icon">J</div>
      <h2>Jay影视</h2>
      <p>欢迎回来，请登录您的账号</p>
    </div>

    <?php if ($flash): ?>
      <div class="flash-message info" style="text-align:center;">
        <i class="icon icon-bell" style="color:inherit;"></i>
        <?= e($flash) ?>
      </div>
    <?php endif; ?>

    <form id="loginForm" onsubmit="handleLogin(event)">
      <input type="hidden" name="redirect" value="<?= e($redirect) ?>">
      <div class="form-group">
        <label class="form-label">邮箱</label>
        <input type="email" name="email" class="form-input" placeholder="请输入注册邮箱" required autocomplete="email">
      </div>
      <div class="form-group">
        <label class="form-label">密码</label>
        <input type="password" name="password" class="form-input" placeholder="请输入密码" required minlength="6" autocomplete="current-password">
      </div>
      <div class="form-tip">
        💡 需要登录才可以观看哦，如没有账号请注册！
      </div>
      <div id="loginError" style="display:none;" class="form-error"></div>
      <button type="submit" class="btn btn-primary btn-lg btn-block">登 录</button>
    </form>

    <div class="auth-footer">
      还没有账号？<a href="/register.php<?= !empty($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : '' ?>">立即注册 →</a>
      <div style="margin-top:12px;">
        <a href="/" style="color:var(--text-light);"><i class="icon icon-home" style="display:inline-block;"></i> 返回首页</a>
      </div>
    </div>
  </div>
</div>

<script>
function handleLogin(e) {
  e.preventDefault();
  var fd = new FormData(e.target);
  var err = document.getElementById('loginError');
  err.style.display = 'none';
  fetch('/api/login.php', {method:'POST', body: fd})
    .then(r => r.json())
    .then(res => {
      if (res.code === 0) {
        location.href = res.data.redirect || '/';
      } else {
        err.textContent = res.message;
        err.style.display = 'block';
      }
    })
    .catch(() => {
      err.textContent = '网络错误，请稍后重试';
      err.style.display = 'block';
    });
}
</script>
</body>
</html>
