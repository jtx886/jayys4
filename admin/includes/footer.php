<?php
// 管理后台公共页脚
if (strpos($_SERVER['PHP_SELF'], '/admin/login.php') === false): ?>
    </div><!-- admin-content -->
  </div><!-- admin-main -->
</div><!-- admin-page -->
<script>
  // 响应式：小屏显示侧边栏切换按钮
  if (window.innerWidth <= 991) {
    var btn = document.getElementById('sidebarToggle');
    if (btn) btn.style.display = 'inline-flex';
  }
  window.addEventListener('resize', function(){
    var btn = document.getElementById('sidebarToggle');
    if (!btn) return;
    if (window.innerWidth <= 991) btn.style.display = 'inline-flex';
    else { btn.style.display = 'none'; document.getElementById('adminSidebar').classList.remove('open'); }
  });
</script>
<?php endif; ?>
</body>
</html>
