<?php
// 公共页脚
?>
<footer class="footer">
  <div class="footer-inner">
    <div class="footer-brand">
      <h3>Jay影视</h3>
      <p>Jay影视是一个免费的在线影视播放平台，聚合全网优质影视资源，为用户提供高清流畅的观影体验。本站所有内容均来源于互联网公开接口，仅供学习交流使用。</p>
    </div>
    <div class="footer-col">
      <h4>快速导航</h4>
      <a href="/">首页</a>
      <a href="/search.php?type=movie">电影</a>
      <a href="/search.php?type=tv">电视剧</a>
      <a href="/search.php?type=anime">动漫</a>
      <a href="/search.php?type=variety">综艺</a>
    </div>
    <div class="footer-col">
      <h4>用户中心</h4>
      <?php if (current_user()): ?>
        <a href="/profile.php">我的主页</a>
        <a href="/profile.php?tab=favorites">我的收藏</a>
        <a href="/profile.php?tab=history">观看历史</a>
      <?php else: ?>
        <a href="/login.php">登录</a>
        <a href="/register.php">注册账号</a>
      <?php endif; ?>
      <a href="/feedback.php">意见反馈</a>
    </div>
    <div class="footer-col">
      <h4>关于我们</h4>
      <a href="javascript:;">关于本站</a>
      <a href="javascript:;">免责声明</a>
      <a href="javascript:;">联系方式</a>
      <?php if (is_admin()): ?>
        <a href="/admin/">管理后台</a>
      <?php endif; ?>
    </div>
  </div>
  <div class="footer-bottom">
    © <?= date('Y') ?> <?= SITE_NAME ?>. All Rights Reserved. 仅供学习交流使用
  </div>
</footer>

<!-- 公告弹窗（仅首页显示） -->
<?php if (basename($_SERVER['PHP_SELF']) === 'index.php'): ?>
<?php
$announcements = [];
try {
  if (Database::isConnected()) {
    $db = Database::getInstance();
    $announcements = $db->fetchAll('SELECT * FROM announcements WHERE status = 1 ORDER BY id DESC LIMIT 5');
    if ($announcements && !empty($user)) {
      $dismissedIds = array_column($db->fetchAll(
        'SELECT announcement_id FROM announcement_dismissals WHERE user_id = ?',
        [$user['id']]
      ), 'announcement_id');
      $announcements = array_filter($announcements, function($a) use ($dismissedIds) {
        return !in_array($a['id'], $dismissedIds);
      });
    }
  }
} catch (Exception $e) {
  $announcements = [];
}
?>
<?php if (!empty($announcements)): ?>
<div class="announcement-overlay" id="announcementOverlay">
  <div class="announcement-modal">
    <div class="announcement-modal-header">
      <h3><i class="icon icon-bullhorn" style="color:#fff;"></i> 系统公告</h3>
      <div class="announcement-modal-close" onclick="closeAnnouncement()"><i class="icon icon-close"></i></div>
    </div>
    <div class="announcement-modal-body">
      <h3 style="margin-top:0;"><?= e($announcements[0]['title']) ?></h3>
      <div><?= $announcements[0]['content'] ?></div>
    </div>
    <div class="announcement-modal-footer">
      <label class="announcement-dismiss">
        <input type="checkbox" id="announcementDismiss"> 不再提示此公告
      </label>
      <button class="btn btn-primary btn-sm" onclick="closeAnnouncement()">我知道了</button>
    </div>
  </div>
</div>
<script>
function closeAnnouncement() {
  var overlay = document.getElementById('announcementOverlay');
  var dismiss = document.getElementById('announcementDismiss');
  if (dismiss && dismiss.checked) {
    fetch('/api/announcement_dismiss.php?id=<?= $announcements[0]['id'] ?>', {method:'POST'});
  }
  overlay.style.display = 'none';
}
</script>
<?php endif; ?>
<?php endif; ?>

</body>
</html>
