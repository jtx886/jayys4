<?php
// 管理后台 - 邮件通知
$page_title = '邮件通知';
$active_menu = 'mail';
require_once __DIR__ . '/includes/header.php';

$db = Database::getInstance();
$totalUsers = intval($db->fetchOne('SELECT COUNT(*) c FROM users')['c']);
$activeUsers = intval($db->fetchOne('SELECT COUNT(*) c FROM users WHERE banned_until IS NULL OR banned_until < NOW()')['c']);
?>

<div class="admin-card">
    <div class="admin-card-title">
        <span><i class="icon icon-mail"></i> 发送通知邮件</span>
        <span style="font-size:12px;color:var(--text-light);font-weight:400;">当前共有 <b><?= $totalUsers ?></b> 位注册用户，其中 <b style="color:var(--success);"><?= $activeUsers ?></b> 位正常可用</span>
    </div>

    <div style="display:grid;grid-template-columns:1fr;max-width:820px;gap:0;">
        <form onsubmit="return doSendMail(event)">
            <div class="form-group">
                <label class="form-label">收件人</label>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">
                    <label style="display:flex;align-items:center;gap:10px;padding:14px;border:2px solid var(--border);border-radius:var(--radius-md);cursor:pointer;transition:all 0.2s;" class="mail-tg">
                        <input type="radio" name="target" value="all" checked onchange="toggleMailTarget(this.value)">
                        <div>
                            <div style="font-weight:600;">📢 全站广播</div>
                            <div style="font-size:12px;color:var(--text-light);">发送给所有注册用户（<?= $totalUsers ?> 人）</div>
                        </div>
                    </label>
                    <label style="display:flex;align-items:center;gap:10px;padding:14px;border:2px solid var(--border);border-radius:var(--radius-md);cursor:pointer;transition:all 0.2s;" class="mail-tg">
                        <input type="radio" name="target" value="email" onchange="toggleMailTarget(this.value)">
                        <div>
                            <div style="font-weight:600;">📧 指定邮箱</div>
                            <div style="font-size:12px;color:var(--text-light);">输入单个邮箱地址发送</div>
                        </div>
                    </label>
                    <label style="display:flex;align-items:center;gap:10px;padding:14px;border:2px solid var(--border);border-radius:var(--radius-md);cursor:pointer;transition:all 0.2s;" class="mail-tg">
                        <input type="radio" name="target" value="user" onchange="toggleMailTarget(this.value)">
                        <div>
                            <div style="font-weight:600;">👤 指定用户</div>
                            <div style="font-size:12px;color:var(--text-light);">选择指定用户发送</div>
                        </div>
                    </label>
                </div>
            </div>

            <div class="form-group" id="targetEmailWrap" style="display:none;">
                <label class="form-label">邮箱地址</label>
                <input type="email" name="email" id="mailToEmail" class="form-input" placeholder="user@example.com">
            </div>

            <div class="form-group" id="targetUserWrap" style="display:none;">
                <label class="form-label">选择用户</label>
                <select name="user_id" id="mailToUser" class="form-select">
                    <option value="">-- 请选择用户 --</option>
                    <?php
                    $users = $db->fetchAll('SELECT id, username, email FROM users ORDER BY username ASC LIMIT 500');
                    foreach ($users as $u):
                    ?>
                        <option value="<?= intval($u['id']) ?>"><?= e($u['username']) ?> &lt;<?= e($u['email']) ?>&gt;</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">邮件主题 *</label>
                <input type="text" name="subject" id="mailSubject" class="form-input" placeholder="例如：系统升级通知" required maxlength="120">
            </div>

            <div class="form-group">
                <label class="form-label">邮件正文 *</label>
                <textarea name="content" id="mailContent" class="form-textarea" placeholder="请输入邮件内容，将自动套用精美HTML邮件模板" style="min-height:220px;" required></textarea>
                <div class="form-hint">支持换行，内容中可填写任意文本。邮件模板会自动加上Jay影视的品牌头部和底部。</div>
            </div>

            <div id="mailResult" style="display:none;" class="mb-16"></div>

            <button type="submit" class="btn btn-primary btn-lg" id="mailBtn">
                <i class="icon icon-mail" style="color:#fff;"></i> 发送邮件
            </button>
        </form>
    </div>
</div>

<!-- 最近封禁邮件记录 -->
<div class="admin-card" style="margin-top:24px;">
    <div class="admin-card-title">
        <span><i class="icon icon-ban"></i> 最近封禁日志（含邮件发送状态）</span>
    </div>
    <?php
    $logs = $db->fetchAll(
        'SELECT b.*, u.username as user_name, o.username as op_name FROM ban_logs b
         LEFT JOIN users u ON u.id = b.user_id LEFT JOIN users o ON o.id = b.operator_id
         ORDER BY b.id DESC LIMIT 12'
    );
    ?>
    <table class="data-table">
        <thead><tr><th>用户</th><th>操作人</th><th>原因</th><th>封禁时间</th><th>解除时间</th><th>邮件</th></tr></thead>
        <tbody>
            <?php foreach ($logs as $l): ?>
                <tr>
                    <td style="font-weight:600;"><?= e($l['user_name'] ?? '已删除#'.$l['user_id']) ?></td>
                    <td><?= e($l['op_name'] ?? '系统') ?><?= ($l['operator_id']==1)?' <span class="dev-badge"></span>':'' ?></td>
                    <td style="max-width:220px;"><?= e($l['reason']) ?></td>
                    <td style="font-size:12px;color:var(--text-light);"><?= e($l['banned_at']) ?></td>
                    <td style="font-size:12px;color:var(--danger);font-weight:600;"><?= e($l['banned_until']) ?></td>
                    <td>
                        <?= intval($l['email_sent']) === 1
                            ? '<span class="badge badge-green"><i class="icon icon-mail"></i> 已发送</span>'
                            : '<span class="badge badge-gray">未发送</span>' ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($logs)): ?>
                <tr><td colspan="6" class="text-center" style="padding:30px;color:var(--text-light);">暂无封禁记录</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
function toggleMailTarget(v) {
    document.querySelectorAll('.mail-tg').forEach(function(el){ el.style.borderColor = 'var(--border)'; el.style.background = ''; });
    var sel = document.querySelector('input[name="target"]:checked');
    if (sel && sel.closest('.mail-tg')) {
        sel.closest('.mail-tg').style.borderColor = 'var(--primary)';
        sel.closest('.mail-tg').style.background = '#e0f2fe';
    }
    document.getElementById('targetEmailWrap').style.display = (v === 'email') ? 'block' : 'none';
    document.getElementById('targetUserWrap').style.display = (v === 'user') ? 'block' : 'none';
}
toggleMailTarget('all');
document.querySelectorAll('input[name="target"]').forEach(function(r){ r.addEventListener('change', function(){toggleMailTarget(r.value);}); });

function doSendMail(e) {
    e.preventDefault();
    var btn = document.getElementById('mailBtn');
    btn.disabled = true; btn.textContent = '发送中...';
    var fd = new FormData(e.target);
    fd.append('action', 'send_mail');
    fetch('/api/admin_api.php', {method:'POST', body:fd})
        .then(r=>r.json())
        .then(res=>{
            btn.disabled = false;
            btn.innerHTML = '<i class="icon icon-mail" style="color:#fff;"></i> 发送邮件';
            var box = document.getElementById('mailResult');
            box.style.display = 'block';
            if (res.code === 0) {
                box.className = 'flash-message success';
                box.textContent = '✓ ' + res.message + '（成功：' + res.data.sent + ' / 总数：' + res.data.total + '）';
            } else {
                box.className = 'flash-message error';
                box.textContent = '✗ ' + res.message;
            }
        });
    return false;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
