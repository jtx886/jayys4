<?php
// 管理后台 - 用户管理
$page_title = '用户管理';
$active_menu = 'users';
require_once __DIR__ . '/includes/header.php';

$db = Database::getInstance();
$search = trim($_GET['search'] ?? '');
$role = $_GET['role'] ?? 'all';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];
if ($search) {
    $where[] = '(username LIKE ? OR email LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($role !== 'all') {
    $where[] = 'role = ?';
    $params[] = $role;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$total = intval($db->fetchOne("SELECT COUNT(*) c FROM users $whereSql", $params)['c']);
$users = $db->fetchAll("SELECT * FROM users $whereSql ORDER BY id DESC LIMIT $perPage OFFSET $offset", $params);

$stMap = ['pending'=>['待处理','badge-yellow'],'replied'=>['已回复','badge-blue'],'resolved'=>['已解决','badge-green'],'closed'=>['已关闭','badge-gray']];
?>

<div class="admin-card">
    <div class="admin-card-title">
        <span><i class="icon icon-users"></i> 用户列表（共 <?= $total ?> 人）</span>
    </div>

    <div class="filter-bar">
        <form method="get" style="display:flex;gap:12px;flex-wrap:wrap;">
            <select name="role" class="form-select" style="max-width:160px;">
                <option value="all" <?= ($role==='all')?'selected':'' ?>>全部角色</option>
                <option value="user" <?= ($role==='user')?'selected':'' ?>>普通用户</option>
                <option value="admin" <?= ($role==='admin')?'selected':'' ?>>管理员</option>
            </select>
            <input type="text" name="search" class="form-input" placeholder="搜索用户名或邮箱" value="<?= e($search) ?>" style="min-width:220px;">
            <button type="submit" class="btn btn-primary btn-sm"><i class="icon icon-search"></i> 搜索</button>
            <?php if ($search || $role !== 'all'): ?>
                <a href="/admin/users.php" class="btn btn-outline btn-sm">重置</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="overflow-x-auto">
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>用户</th>
                <th>邮箱</th>
                <th>角色</th>
                <th>状态</th>
                <th>注册时间</th>
                <th>上次登录</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <tr id="u-<?= intval($u['id']) ?>">
                    <td>#<?= intval($u['id']) ?></td>
                    <td>
                        <div class="table-user">
                            <div class="table-avatar">
                                <?php if (!empty($u['avatar'])): ?><img src="<?= e($u['avatar']) ?>"><?php else: ?><?= mb_substr($u['username'],0,1) ?><?php endif; ?>
                            </div>
                            <div>
                                <div class="table-user-name">
                                    <?= e($u['username']) ?>
                                    <?php if ($u['role'] === 'admin'): ?><span class="dev-badge"></span><?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td><?= e($u['email']) ?></td>
                    <td>
                        <?= ($u['role'] === 'admin') ? '<span class="badge badge-red">管理员</span>' : '<span class="badge badge-blue">普通用户</span>' ?>
                    </td>
                    <td>
                        <?php if (is_banned($u)): ?>
                            <span class="badge badge-red" title="解禁：<?= e($u['banned_until']) ?>">已封禁</span>
                        <?php else: ?>
                            <span class="badge badge-green">正常</span>
                        <?php endif; ?>
                    </td>
                    <td style="color:var(--text-light);font-size:13px;"><?= e($u['created_at']) ?></td>
                    <td style="color:var(--text-light);font-size:13px;">
                        <?= !empty($u['last_login_at']) ? time_ago($u['last_login_at']) : '—' ?>
                    </td>
                    <td>
                        <div class="table-actions">
                            <?php if (is_banned($u)): ?>
                                <button class="action-btn" title="解封" onclick="unbanUser(<?= intval($u['id']) ?>)"><i class="icon icon-ban"></i></button>
                            <?php else: ?>
                                <button class="action-btn danger" title="封禁用户" onclick="openBanModal(<?= intval($u['id']) ?>, '<?= e($u['username']) ?>', '<?= e($u['email']) ?>')">
                                    <i class="icon icon-ban"></i>
                                </button>
                            <?php endif; ?>
                            <button class="action-btn" title="发邮件" onclick="openMailModal('<?= e($u['email']) ?>','<?= e($u['username']) ?>')">
                                <i class="icon icon-mail"></i>
                            </button>
                            <a class="action-btn" title="查看历史" href="/admin/history.php?user_id=<?= intval($u['id']) ?>"><i class="icon icon-history"></i></a>
                            <a class="action-btn" title="查看收藏" href="/admin/favorites.php?user_id=<?= intval($u['id']) ?>"><i class="icon icon-heart"></i></a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>

    <?php if ($total > $perPage): ?>
    <div class="pagination mt-24">
        <?php
        $qs = $_GET;
        $tp = ceil($total / $perPage);
        $qs['page'] = $page - 1;
        ?>
        <a href="?<?= http_build_query($qs) ?>" class="page-btn <?= ($page<=1)?'disabled':'' ?>">上一页</a>
        <?php $s = max(1, $page-3); $e = min($tp, $s+6); $s = max(1, $e-6);
        for ($i=$s; $i<=$e; $i++): $qs['page'] = $i; ?>
            <a href="?<?= http_build_query($qs) ?>" class="page-btn <?= ($i===$page)?'active':'' ?>"><?= $i ?></a>
        <?php endfor; ?>
        <?php $qs['page'] = $page + 1; ?>
        <a href="?<?= http_build_query($qs) ?>" class="page-btn <?= ($page>=$tp)?'disabled':'' ?>">下一页</a>
    </div>
    <?php endif; ?>
</div>

<!-- 封禁弹窗 -->
<div class="modal-overlay" id="banModal" style="display:none;">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="icon icon-ban" style="color:var(--danger);"></i> 封禁用户</h3>
            <div class="modal-close" onclick="closeBanModal()"><i class="icon icon-close"></i></div>
        </div>
        <div class="modal-body">
            <div style="margin-bottom:14px;padding:12px;background:#fef2f2;border-radius:8px;border:1px solid #fee2e2;">
                <span id="banUserName" style="font-weight:600;color:var(--danger);"></span>
                <span style="color:var(--text-light);margin-left:6px;" id="banUserEmail"></span>
            </div>
            <div class="form-group">
                <label class="form-label">封禁时长</label>
                <select class="form-select" id="banDuration" onchange="toggleBanUntil(this.value)">
                    <option value="1">1 小时</option>
                    <option value="24" selected>1 天（24小时）</option>
                    <option value="168">7 天</option>
                    <option value="720">30 天</option>
                    <option value="2160">90 天</option>
                    <option value="custom">自定义时间</option>
                    <option value="87600">永久封禁（10年）</option>
                </select>
            </div>
            <div class="form-group" id="customBanUntilWrap" style="display:none;">
                <label class="form-label">解除时间</label>
                <input type="datetime-local" class="form-input" id="banUntil">
            </div>
            <div class="form-group">
                <label class="form-label">封禁原因（将发送给用户）</label>
                <textarea class="form-textarea" id="banReason" placeholder="请输入封禁原因..." style="min-height:80px;">违反平台规定</textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeBanModal()">取消</button>
            <button class="btn btn-danger" onclick="confirmBan()">确认封禁</button>
        </div>
    </div>
</div>

<!-- 邮件弹窗 -->
<div class="modal-overlay" id="mailModal" style="display:none;">
    <div class="modal" style="max-width:560px;">
        <div class="modal-header">
            <h3><i class="icon icon-mail" style="color:var(--primary);"></i> 发送邮件</h3>
            <div class="modal-close" onclick="document.getElementById('mailModal').style.display='none'"><i class="icon icon-close"></i></div>
        </div>
        <form onsubmit="return sendMail(event)">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">收件人</label>
                    <input type="email" id="mailTo" class="form-input" required readonly>
                    <div id="mailToName" style="font-size:12px;color:var(--text-light);margin-top:4px;"></div>
                </div>
                <div class="form-group">
                    <label class="form-label">邮件主题</label>
                    <input type="text" id="mailSubject" class="form-input" placeholder="请输入邮件主题" required>
                </div>
                <div class="form-group">
                    <label class="form-label">邮件正文</label>
                    <textarea id="mailContent" class="form-textarea" placeholder="请输入邮件内容（会自动加上邮件样式模板）" style="min-height:140px;" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('mailModal').style.display='none'">取消</button>
                <button type="submit" class="btn btn-primary">发送邮件</button>
            </div>
        </form>
    </div>
</div>

<script>
var curBanId = 0;
function openBanModal(id, name, email) {
    curBanId = id;
    document.getElementById('banUserName').textContent = name;
    document.getElementById('banUserEmail').textContent = '<' + email + '>';
    document.getElementById('banModal').style.display = 'flex';
}
function closeBanModal() { document.getElementById('banModal').style.display = 'none'; }
function toggleBanUntil(v) {
    document.getElementById('customBanUntilWrap').style.display = (v === 'custom') ? 'block' : 'none';
    if (v === 'custom') {
        var t = new Date(Date.now() + 24*3600*1000);
        document.getElementById('banUntil').value = t.toISOString().slice(0,16);
    }
}
function confirmBan() {
    if (!curBanId) return;
    var duration = parseInt(document.getElementById('banDuration').value);
    var reason = document.getElementById('banReason').value || '违反平台规定';
    var until = '';
    if (isNaN(duration) || duration === 0) {
        until = document.getElementById('banUntil').value || '';
        duration = 0;
    }
    var fd = new FormData();
    fd.append('action', 'ban_user');
    fd.append('user_id', curBanId);
    fd.append('duration', duration || 0);
    fd.append('until', until || '');
    fd.append('reason', reason);
    fetch('/api/admin_api.php', {method:'POST', body:fd})
        .then(r=>r.json())
        .then(res=>{
            alert(res.message + (res.data && res.data.email_sent ? '（邮件已通知用户）' : ''));
            if (res.code === 0) location.reload();
        });
}

function unbanUser(id) {
    if (!confirm('确定解封该用户？')) return;
    var fd = new FormData();
    fd.append('action', 'unban_user');
    fd.append('user_id', id);
    fetch('/api/admin_api.php', {method:'POST', body:fd})
        .then(r=>r.json()).then(res=>{alert(res.message); if(res.code===0)location.reload();});
}

function openMailModal(email, name) {
    document.getElementById('mailTo').value = email;
    document.getElementById('mailToName').textContent = '用户：' + name;
    document.getElementById('mailModal').style.display = 'flex';
}
function sendMail(e) {
    e.preventDefault();
    var fd = new FormData();
    fd.append('action', 'send_mail');
    fd.append('target', 'email');
    fd.append('email', document.getElementById('mailTo').value);
    fd.append('subject', document.getElementById('mailSubject').value);
    fd.append('content', document.getElementById('mailContent').value);
    fetch('/api/admin_api.php', {method:'POST', body:fd})
        .then(r=>r.json())
        .then(res=>{
            alert(res.message + '（成功 ' + (res.data?.sent||0) + '/' + (res.data?.total||0) + '）');
            if (res.code === 0) document.getElementById('mailModal').style.display = 'none';
        });
    return false;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
