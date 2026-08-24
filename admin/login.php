<?php
// 管理后台登录
$page_title = '登录';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';

// 已登录管理员直接跳转
if (!empty($_SESSION['user_id'])) {
    $u = current_user();
    if ($u && $u['role'] === 'admin') { header('Location: /admin/'); exit; }
}

$flash = flash_message();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>管理后台登录 - <?= SITE_NAME ?></title>
<link rel="stylesheet" href="/assets/css/main.css?ver=2024">
<link rel="stylesheet" href="/assets/css/icons.css?ver=2024">
</head>
<body>
<div class="auth-page" style="background:linear-gradient(135deg,#1a1f36,#032541,#1a1f36);">
  <div class="auth-card" style="max-width:420px;">
    <div class="auth-logo">
      <div class="auth-logo-icon" style="background:linear-gradient(135deg,#e74c3c,#c0392b);">J</div>
      <h2 style="color:#1a1f36;">管理后台</h2>
      <p><?= SITE_NAME ?> - Administrator</p>
    </div>

    <?php if ($flash): ?>
      <div class="flash-message error" style="text-align:center;"><?= e($flash) ?></div>
    <?php endif; ?>

    <form onsubmit="handleLogin(event)">
      <div class="form-group">
        <label class="form-label">管理员账号</label>
        <input type="text" name="email" class="form-input" placeholder="请输入管理员用户名或邮箱" required autofocus autocomplete="username">
      </div>
      <div class="form-group">
        <label class="form-label">密码</label>
        <input type="password" name="password" class="form-input" placeholder="请输入密码" required minlength="4" autocomplete="current-password">
      </div>
      <div id="loginErr" style="display:none;" class="form-error mb-16"></div>
      <button type="submit" class="btn btn-primary btn-lg btn-block" style="background:linear-gradient(135deg,#e74c3c,#c0392b);">登录后台</button>
    </form>
    <div class="auth-footer">
      <a href="/"><i class="icon icon-home" style="display:inline-block;"></i> 返回首页</a>
    </div>
  </div>
</div>
<script>
function handleLogin(e){
  e.preventDefault();
  var err = document.getElementById('loginErr');
  err.style.display = 'none';
  var fd = new FormData(e.target);
  // 用户名转邮箱兼容（管理员杰同学可能用用户名）
  var u = fd.get('email');
  if (u && !u.includes('@') && u === '杰同学') fd.set('email', 'admin@jay.com');
  fetch('/api/login.php', {method:'POST', body:fd})
    .then(r=>r.json())
    .then(res=>{
      if(res.code===0){
        // 检查角色
        fetch('/api/admin_api.php?action=check_role').then(r=>r.json()).then(r2=>{
          if(r2.code===0 && r2.data.is_admin){ location.href='/admin/'; }
          else { err.textContent='该账号不是管理员'; err.style.display='block'; }
        });
      }else{
        err.textContent = res.message; err.style.display='block';
      }
    });
}
</script>
</body>
</html>
