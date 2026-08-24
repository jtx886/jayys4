<?php
// 注册页面
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!empty($_SESSION['user_id'])) { header('Location: /'); exit; }
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
$page_title = '注册';
$theme = get_theme_colors();
$flash = flash_message();
$redirect = $_GET['redirect'] ?? '/';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>注册 - <?= SITE_NAME ?></title>
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
      <h2>加入Jay影视</h2>
      <p>创建账号，畅享海量高清影视资源</p>
    </div>

    <?php if ($flash): ?>
      <div class="flash-message info" style="text-align:center;"><?= e($flash) ?></div>
    <?php endif; ?>

    <form id="registerForm" onsubmit="handleRegister(event)">
      <input type="hidden" name="redirect" value="<?= e($redirect) ?>">

      <div class="form-group">
        <label class="form-label">邮箱</label>
        <input type="email" name="email" id="regEmail" class="form-input" placeholder="请输入您的邮箱" required autocomplete="email">
      </div>

      <div class="form-group">
        <label class="form-label">用户名</label>
        <input type="text" name="username" class="form-input" placeholder="2-20个字符" required minlength="2" maxlength="20" autocomplete="username">
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">密码</label>
          <input type="password" name="password" id="regPwd" class="form-input" placeholder="至少6位" required minlength="6" autocomplete="new-password">
        </div>
        <div class="form-group">
          <label class="form-label">确认密码</label>
          <input type="password" name="confirm_password" class="form-input" placeholder="再次输入密码" required minlength="6" autocomplete="new-password">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">邮箱验证码</label>
        <div class="input-group">
          <input type="text" name="code" class="form-input" placeholder="输入6位验证码" required maxlength="6" minlength="6" inputmode="numeric">
          <button type="button" class="btn btn-outline" id="codeBtn" onclick="sendCode()">获取验证码</button>
        </div>
        <div class="form-hint">验证码将发送至您的邮箱，10分钟内有效</div>
      </div>

      <div id="regError" style="display:none;" class="form-error"></div>

      <button type="submit" class="btn btn-primary btn-lg btn-block">创建账号</button>
    </form>

    <div class="auth-footer">
      已有账号？<a href="/login.php<?= !empty($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : '' ?>">立即登录 →</a>
      <div style="margin-top:12px;">
        <a href="/" style="color:var(--text-light);"><i class="icon icon-home" style="display:inline-block;"></i> 返回首页</a>
      </div>
    </div>
  </div>
</div>

<script>
var codeTimer = null;
function sendCode() {
  var email = document.getElementById('regEmail').value;
  if (!email || !/^\S+@\S+\.\S+$/.test(email)) {
    alert('请先输入正确的邮箱地址');
    return;
  }
  var btn = document.getElementById('codeBtn');
  btn.disabled = true;
  var t = 60;
  btn.textContent = t + 's 后重试';
  fetch('/api/send_code.php', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: 'email=' + encodeURIComponent(email) + '&type=register'
  }).then(r=>r.json()).then(res => {
    if (res.code !== 0) {
      alert(res.message);
      clearInterval(codeTimer);
      btn.disabled = false;
      btn.textContent = '获取验证码';
    }
  }).catch(()=>{});
  codeTimer = setInterval(() => {
    t--;
    if (t <= 0) {
      clearInterval(codeTimer);
      btn.disabled = false;
      btn.textContent = '获取验证码';
    } else {
      btn.textContent = t + 's 后重试';
    }
  }, 1000);
}

function handleRegister(e) {
  e.preventDefault();
  var pwd = document.getElementById('regPwd').value;
  var cPwd = e.target['confirm_password'].value;
  var err = document.getElementById('regError');
  if (pwd !== cPwd) {
    err.textContent = '两次输入的密码不一致';
    err.style.display = 'block';
    return;
  }
  err.style.display = 'none';
  var fd = new FormData(e.target);
  fetch('/api/register.php', {method:'POST', body: fd})
    .then(r=>r.json())
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
