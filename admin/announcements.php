<?php
// 管理后台 - 公告管理
$page_title = '公告管理';
$active_menu = 'announcements';
require_once __DIR__ . '/includes/header.php';

$db = Database::getInstance();
$list = $db->fetchAll(
    'SELECT a.*, u.username as creator FROM announcements a LEFT JOIN users u ON u.id = a.created_by ORDER BY a.id DESC'
);
?>

<div class="admin-card">
    <div class="admin-card-title">
        <span><i class="icon icon-bullhorn"></i> 公告列表（共 <?= count($list) ?> 条）</span>
        <button class="btn btn-primary btn-sm" onclick="openAnnModal()"><i class="icon icon-plus"></i> 发布新公告</button>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:20px;">
        <?php foreach ($list as $a): ?>
            <div class="admin-card" style="margin:0;padding:0;overflow:hidden;">
                <div style="padding:16px 20px;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;display:flex;align-items:center;justify-content:space-between;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <i class="icon icon-bullhorn" style="color:#fff;"></i>
                        <div style="font-weight:700;"><?= e($a['title']) ?></div>
                        <?php if (intval($a['status']) === 1): ?>
                            <span class="badge badge-green" style="background:rgba(255,255,255,0.2);color:#fff;border:1px solid rgba(255,255,255,0.3);">发布中</span>
                        <?php else: ?>
                            <span class="badge badge-gray" style="background:rgba(255,255,255,0.15);color:rgba(255,255,255,0.8);border:1px solid rgba(255,255,255,0.2);">已下架</span>
                        <?php endif; ?>
                    </div>
                    <div style="display:flex;gap:6px;">
                        <button class="action-btn" style="background:rgba(255,255,255,0.15);color:#fff;"
                                onclick='openAnnModal(<?= json_encode($a) ?>)' title="编辑"><i class="icon icon-edit"></i></button>
                        <button class="action-btn" style="background:rgba(231,76,60,0.8);color:#fff;"
                                onclick="deleteAnn(<?= intval($a['id']) ?>,'<?= e($a['title']) ?>')" title="删除"><i class="icon icon-trash"></i></button>
                    </div>
                </div>
                <div style="padding:18px 20px;line-height:1.7;max-height:200px;overflow-y:auto;">
                    <?= $a['content'] ?>
                </div>
                <div style="padding:10px 20px;background:var(--bg-gray);display:flex;justify-content:space-between;color:var(--text-light);font-size:12px;">
                    <span>发布人：<?= e($a['creator'] ?? '系统') ?></span>
                    <span>更新时间：<?= e($a['updated_at']) ?></span>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($list)): ?>
            <div style="grid-column:1/-1;padding:40px;text-align:center;color:var(--text-light);background:#fff;border-radius:var(--radius-lg);box-shadow:var(--shadow-sm);">
                暂无公告，点击右上角发布新公告
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- 公告编辑弹窗 -->
<div class="modal-overlay" id="annModal" style="display:none;">
    <div class="modal" style="max-width:640px;">
        <div class="modal-header">
            <h3 id="annModalTitle">发布新公告</h3>
            <div class="modal-close" onclick="document.getElementById('annModal').style.display='none'"><i class="icon icon-close"></i></div>
        </div>
        <form onsubmit="return saveAnn(event)">
            <input type="hidden" id="annId" value="0">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">公告标题 *</label>
                    <input type="text" id="annTitle" class="form-input" placeholder="如：欢迎来到Jay影视" required maxlength="100">
                </div>
                <div class="form-group">
                    <label class="form-label">公告内容 *（支持HTML标签）</label>
                    <textarea id="annContent" class="form-textarea" placeholder="在此输入公告内容，可使用HTML标签如 <p> <h3> <b>等" style="min-height:200px;" required></textarea>
                    <div class="form-hint">提示：公告样式为弹窗形式，仅在首页显示，用户可勾选"不再提示此公告"。有新公告会再次显示。</div>
                </div>
                <div class="form-group">
                    <label class="form-label">状态</label>
                    <select id="annStatus" class="form-select">
                        <option value="1">立即发布（所有人可见）</option>
                        <option value="0">下架（暂不显示）</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('annModal').style.display='none'">取消</button>
                <button type="submit" class="btn btn-primary">发布公告</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAnnModal(a) {
    document.getElementById('annModal').style.display = 'flex';
    if (a) {
        document.getElementById('annModalTitle').textContent = '编辑公告';
        document.getElementById('annId').value = a.id;
        document.getElementById('annTitle').value = a.title;
        document.getElementById('annContent').value = a.content;
        document.getElementById('annStatus').value = a.status;
    } else {
        document.getElementById('annModalTitle').textContent = '发布新公告';
        document.getElementById('annId').value = 0;
        document.getElementById('annTitle').value = '';
        document.getElementById('annContent').value = '<p>欢迎使用Jay影视！</p>';
        document.getElementById('annStatus').value = 1;
    }
}
function saveAnn(e) {
    e.preventDefault();
    var fd = new FormData();
    fd.append('action', 'save_announcement');
    fd.append('id', document.getElementById('annId').value);
    fd.append('title', document.getElementById('annTitle').value);
    fd.append('content', document.getElementById('annContent').value);
    fd.append('status', document.getElementById('annStatus').value);
    fetch('/api/admin_api.php', {method:'POST', body:fd})
        .then(r=>r.json())
        .then(res=>{ alert(res.message); if (res.code===0) location.reload(); });
    return false;
}
function deleteAnn(id, title) {
    if (!confirm('确定删除公告【' + title + '】？此操作不可撤销。')) return;
    var fd = new FormData();
    fd.append('action', 'delete_announcement');
    fd.append('id', id);
    fetch('/api/admin_api.php', {method:'POST', body:fd})
        .then(r=>r.json())
        .then(res=>{ alert(res.message); if (res.code===0) location.reload(); });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
