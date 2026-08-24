<?php
// 管理后台 - 反馈管理
$page_title = '反馈管理';
$active_menu = 'feedback';
require_once __DIR__ . '/includes/header.php';

$db = Database::getInstance();
$status = $_GET['status'] ?? 'all';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$where = []; $params = [];
if ($status !== 'all') { $where[] = 'f.status = ?'; $params[] = $status; }
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$total = intval($db->fetchOne("SELECT COUNT(*) c FROM feedbacks f $whereSql", $params)['c']);
$list = $db->fetchAll(
    "SELECT f.*, u.username, u.avatar, u.email, u.role FROM feedbacks f JOIN users u ON u.id = f.user_id
     $whereSql ORDER BY f.id DESC LIMIT $perPage OFFSET $offset",
    $params
);
$stMap = ['pending'=>['待处理','badge-yellow'],'replied'=>['已回复','badge-blue'],'resolved'=>['已解决','badge-green'],'closed'=>['已关闭','badge-gray']];
$catMap = ['bug'=>'Bug','suggest'=>'建议','content'=>'内容','play'=>'播放','account'=>'账号','other'=>'其他'];
?>

<div class="admin-card">
    <div class="admin-card-title">
        <span><i class="icon icon-message"></i> 反馈列表（共 <?= $total ?> 条）</span>
        <div class="filter-bar" style="margin:0;">
            <a href="?status=all" class="btn <?= ($status==='all')?'btn-primary':'btn-outline' ?> btn-sm">全部</a>
            <a href="?status=pending" class="btn <?= ($status==='pending')?'btn-primary':'btn-outline' ?> btn-sm">待处理</a>
            <a href="?status=replied" class="btn <?= ($status==='replied')?'btn-primary':'btn-outline' ?> btn-sm">已回复</a>
            <a href="?status=resolved" class="btn <?= ($status==='resolved')?'btn-primary':'btn-outline' ?> btn-sm">已解决</a>
        </div>
    </div>

    <?php foreach ($list as $f): ?>
        <?php
            $replies = $db->fetchAll(
                'SELECT r.*, u.username, u.avatar, u.role FROM feedback_replies r JOIN users u ON u.id = r.user_id
                 WHERE r.feedback_id = ? ORDER BY r.is_admin DESC, r.id ASC',
                 [$f['id']]
            );
            $normalReplies = array_filter($replies, function($r){return !$r['is_admin'];});
            $adminReplies = array_filter($replies, function($r){return $r['is_admin'];});
            $allReplies = array_merge($adminReplies, $normalReplies);
            $moreThan3 = count($allReplies) > 3;
            $displayReplies = $moreThan3 ? array_slice($allReplies, 0, 3) : $allReplies;
            $st = $stMap[$f['status']] ?? ['未知','badge-gray'];
        ?>
        <div class="admin-card" style="padding:0;overflow:hidden;margin:0 0 20px;box-shadow:none;border:1px solid var(--border);" id="fb-<?= intval($f['id']) ?>">
            <div style="padding:18px 22px;background:var(--bg-gray);display:flex;align-items:flex-start;justify-content:space-between;gap:20px;flex-wrap:wrap;">
                <div style="display:flex;gap:14px;flex:1;">
                    <div class="feedback-avatar" style="width:46px;height:46px;">
                        <?php if (!empty($f['avatar'])): ?><img src="<?= e($f['avatar']) ?>"><?php else: ?><?= mb_substr($f['username'],0,1) ?><?php endif; ?>
                    </div>
                    <div style="flex:1;">
                        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:6px;">
                            <span style="font-weight:700;"><?= e($f['username']) ?></span>
                            <?php if ($f['role'] === 'admin'): ?><span class="dev-badge"></span><?php endif; ?>
                            <span class="badge badge-blue"><?= $catMap[$f['category']] ?? $f['category'] ?></span>
                            <span class="badge <?= $st[1] ?>"><?= $st[0] ?></span>
                            <span style="font-size:12px;color:var(--text-light);">#<?= intval($f['id']) ?> · <?= e($f['created_at']) ?></span>
                        </div>
                        <h4 style="margin-bottom:6px;font-size:16px;"><?= e($f['title']) ?></h4>
                        <div style="color:var(--text-secondary);line-height:1.7;white-space:pre-wrap;"><?= e($f['content']) ?></div>
                    </div>
                </div>
                <div style="display:flex;gap:8px;align-items:center;flex-shrink:0;">
                    <select class="form-select" style="width:auto;padding:6px 10px;" onchange="updateFbStatus(<?= intval($f['id']) ?>, this.value)">
                        <option value="pending" <?= ($f['status']==='pending')?'selected':'' ?>>待处理</option>
                        <option value="replied" <?= ($f['status']==='replied')?'selected':'' ?>>已回复</option>
                        <option value="resolved" <?= ($f['status']==='resolved')?'selected':'' ?>>已解决</option>
                        <option value="closed" <?= ($f['status']==='closed')?'selected':'' ?>>已关闭</option>
                    </select>
                </div>
            </div>

            <!-- 回复区 -->
            <?php if (!empty($allReplies)): ?>
                <div class="replies-wrapper" style="margin:0;border-radius:0;background:#fff;">
                    <?php foreach ($displayReplies as $r): ?>
                        <?php
                            $rClass = $r['is_admin'] ? ' admin-reply' : '';
                        ?>
                        <div class="reply-item<?= $rClass ?>">
                            <div class="reply-avatar">
                                <?php if (!empty($r['avatar'])): ?><img src="<?= e($r['avatar']) ?>" style="width:100%;height:100%;object-fit:cover;"><?php else: ?><?= mb_substr($r['username'],0,1) ?><?php endif; ?>
                            </div>
                            <div class="reply-body">
                                <div class="reply-author">
                                    <?= e($r['username']) ?>
                                    <?php if ($r['is_admin'] || $r['role'] === 'admin'): ?><span class="dev-badge"></span><?php endif; ?>
                                    <span class="feedback-time"><?= e($r['created_at']) ?></span>
                                </div>
                                <div class="reply-content"><?= e($r['content']) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($moreThan3): ?>
                        <div class="replies-collapsed" id="collapsed-<?= intval($f['id']) ?>">
                            <?php foreach (array_slice($allReplies, 3) as $r): ?>
                                <div class="reply-item<?= ($r['is_admin'])?' admin-reply':'' ?>">
                                    <div class="reply-avatar">
                                        <?php if (!empty($r['avatar'])): ?><img src="<?= e($r['avatar']) ?>" style="width:100%;height:100%;object-fit:cover;"><?php else: ?><?= mb_substr($r['username'],0,1) ?><?php endif; ?>
                                    </div>
                                    <div class="reply-body">
                                        <div class="reply-author">
                                            <?= e($r['username']) ?>
                                            <?php if ($r['is_admin']): ?><span class="dev-badge"></span><?php endif; ?>
                                            <span class="feedback-time"><?= e($r['created_at']) ?></span>
                                        </div>
                                        <div class="reply-content"><?= e($r['content']) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="replies-toggle" onclick="document.getElementById('collapsed-<?= intval($f['id']) ?>').classList.toggle('open');this.textContent=(document.getElementById('collapsed-<?= intval($f['id']) ?>').classList.contains('open'))?'收起回复':'展开全部回复（<?= count($allReplies) ?>条）';">
                            展开全部回复（<?= count($allReplies) ?>条）
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- 管理员回复表单 -->
            <div style="padding:16px 22px;border-top:1px dashed var(--border);background:#fafbfc;">
                <form onsubmit="return replyFb(<?= intval($f['id']) ?>, event)">
                    <div style="display:flex;gap:10px;">
                        <input type="text" class="form-input" id="replyInput-<?= intval($f['id']) ?>" placeholder="以管理员身份回复该反馈（将发送邮件通知用户）" required>
                        <button type="submit" class="btn btn-primary" style="flex-shrink:0;"><i class="icon icon-mail"></i> 回复</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endforeach; ?>

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

<script>
function updateFbStatus(id, st) {
    var fd = new FormData();
    fd.append('action', 'update_feedback_status');
    fd.append('id', id);
    fd.append('status', st);
    fetch('/api/admin_api.php', {method:'POST', body:fd}).then(r=>r.json()).then(res=>{/*toast*/});
}
function replyFb(id, e) {
    e.preventDefault();
    var input = document.getElementById('replyInput-' + id);
    if (!input.value.trim()) return false;
    var fd = new FormData();
    fd.append('action', 'reply_feedback');
    fd.append('id', id);
    fd.append('content', input.value);
    fetch('/api/admin_api.php', {method:'POST', body:fd})
        .then(r=>r.json())
        .then(res=>{
            alert(res.message);
            if (res.code === 0) location.reload();
        });
    return false;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
