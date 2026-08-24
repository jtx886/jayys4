<?php
// 管理后台 - 播放源管理
$page_title = '播放源管理';
$active_menu = 'sources';
require_once __DIR__ . '/includes/header.php';

$db = Database::getInstance();
$sources = $db->fetchAll('SELECT * FROM play_sources ORDER BY sort_order ASC, id DESC');
?>

<div class="admin-card">
    <div class="admin-card-title">
        <span><i class="icon icon-play"></i> 播放源列表（共 <?= count($sources) ?> 个）</span>
        <button class="btn btn-primary btn-sm" onclick="openSourceModal()"><i class="icon icon-plus"></i> 添加播放源</button>
    </div>

    <div class="overflow-x-auto">
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>名称</th>
                <th>类型</th>
                <th>API地址</th>
                <th>排序</th>
                <th>状态</th>
                <th>备注</th>
                <th>更新时间</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sources as $s): ?>
                <tr id="src-<?= intval($s['id']) ?>">
                    <td>#<?= intval($s['id']) ?></td>
                    <td style="font-weight:600;"><?= e($s['name']) ?></td>
                    <td><span class="badge badge-blue"><?= ($s['type'] === 'parse') ? '解析型' : '综合型' ?></span></td>
                    <td style="max-width:260px;">
                        <div style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?= e($s['api_url']) ?>"><?= e($s['api_url']) ?></div>
                    </td>
                    <td><?= intval($s['sort_order']) ?></td>
                    <td>
                        <?= intval($s['status']) === 1 ? '<span class="badge badge-green">启用</span>' : '<span class="badge badge-gray">禁用</span>' ?>
                    </td>
                    <td style="color:var(--text-light);font-size:13px;max-width:160px;"><?= e($s['remark']) ?: '—' ?></td>
                    <td style="color:var(--text-light);font-size:12px;"><?= e($s['updated_at']) ?></td>
                    <td>
                        <div class="table-actions">
                            <button class="action-btn" title="编辑" onclick='openSourceModal(<?= json_encode($s) ?>)'><i class="icon icon-edit"></i></button>
                            <button class="action-btn danger" title="删除" onclick="deleteSource(<?= intval($s['id']) ?>, '<?= e($s['name']) ?>')"><i class="icon icon-trash"></i></button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($sources)): ?>
                <tr><td colspan="9" class="text-center" style="padding:40px;color:var(--text-light);">暂无播放源，点击右上角添加</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<!-- 播放源编辑弹窗 -->
<div class="modal-overlay" id="srcModal" style="display:none;">
    <div class="modal" style="max-width:560px;">
        <div class="modal-header">
            <h3 id="srcModalTitle">添加播放源</h3>
            <div class="modal-close" onclick="document.getElementById('srcModal').style.display='none'"><i class="icon icon-close"></i></div>
        </div>
        <form onsubmit="return saveSource(event)">
            <input type="hidden" id="srcId" value="0">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">名称 *</label>
                        <input type="text" id="srcName" class="form-input" required placeholder="如：主播放源">
                    </div>
                    <div class="form-group">
                        <label class="form-label">类型</label>
                        <select id="srcType" class="form-select">
                            <option value="general">综合型（搜索/详情）</option>
                            <option value="parse">解析型（URL解析）</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">API地址 *</label>
                    <input type="url" id="srcUrl" class="form-input" required placeholder="https://api.example.com/api.php">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">排序</label>
                        <input type="number" id="srcSort" class="form-input" value="0" min="0" max="999">
                    </div>
                    <div class="form-group">
                        <label class="form-label">状态</label>
                        <select id="srcStatus" class="form-select">
                            <option value="1" selected>启用</option>
                            <option value="0">禁用</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">备注</label>
                    <input type="text" id="srcRemark" class="form-input" placeholder="可选：播放源说明">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('srcModal').style.display='none'">取消</button>
                <button type="submit" class="btn btn-primary">保存</button>
            </div>
        </form>
    </div>
</div>

<script>
function openSourceModal(src) {
    document.getElementById('srcModal').style.display = 'flex';
    if (src) {
        document.getElementById('srcModalTitle').textContent = '编辑播放源';
        document.getElementById('srcId').value = src.id;
        document.getElementById('srcName').value = src.name;
        document.getElementById('srcType').value = src.type;
        document.getElementById('srcUrl').value = src.api_url;
        document.getElementById('srcSort').value = src.sort_order;
        document.getElementById('srcStatus').value = src.status;
        document.getElementById('srcRemark').value = src.remark || '';
    } else {
        document.getElementById('srcModalTitle').textContent = '添加播放源';
        document.getElementById('srcId').value = 0;
        document.getElementById('srcName').value = '';
        document.getElementById('srcType').value = 'general';
        document.getElementById('srcUrl').value = '';
        document.getElementById('srcSort').value = 0;
        document.getElementById('srcStatus').value = 1;
        document.getElementById('srcRemark').value = '';
    }
}

function saveSource(e) {
    e.preventDefault();
    var fd = new FormData();
    fd.append('action', 'save_source');
    fd.append('id', document.getElementById('srcId').value);
    fd.append('name', document.getElementById('srcName').value);
    fd.append('api_url', document.getElementById('srcUrl').value);
    fd.append('type', document.getElementById('srcType').value);
    fd.append('sort_order', document.getElementById('srcSort').value);
    fd.append('status', document.getElementById('srcStatus').value);
    fd.append('remark', document.getElementById('srcRemark').value);
    fetch('/api/admin_api.php', {method:'POST', body:fd})
        .then(r=>r.json())
        .then(res=>{ alert(res.message); if (res.code===0) location.reload(); });
    return false;
}

function deleteSource(id, name) {
    if (!confirm('确定删除播放源【' + name + '】？')) return;
    var fd = new FormData();
    fd.append('action', 'delete_source');
    fd.append('id', id);
    fetch('/api/admin_api.php', {method:'POST', body:fd})
        .then(r=>r.json())
        .then(res=>{ alert(res.message); if (res.code===0) location.reload(); });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
