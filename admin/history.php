<?php
// 管理后台 - 独立观看历史模块
$page_title = '观看历史';
$active_menu = 'history';
require_once __DIR__ . '/includes/header.php';

$db = Database::getInstance();
$userId = intval($_GET['user_id'] ?? 0);
$search = trim($_GET['search'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 30;
$offset = ($page - 1) * $perPage;

$where = []; $params = [];
if ($userId > 0) { $where[] = 'h.user_id = ?'; $params[] = $userId; }
if ($search) { $where[] = 'h.title LIKE ?'; $params[] = "%$search%"; }
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$total = intval($db->fetchOne("SELECT COUNT(*) c FROM watch_history h $whereSql", $params)['c']);
$list = $db->fetchAll(
    "SELECT h.*, u.username, u.email FROM watch_history h JOIN users u ON u.id = h.user_id
     $whereSql ORDER BY h.updated_at DESC LIMIT $perPage OFFSET $offset",
    $params
);

$userOptions = $db->fetchAll('SELECT id, username FROM users ORDER BY id DESC LIMIT 300');
?>

<div class="admin-card">
    <div class="admin-card-title">
        <span><i class="icon icon-history"></i> 观看历史记录（共 <?= $total ?> 条）</span>
    </div>

    <div class="filter-bar">
        <form method="get" style="display:flex;gap:12px;flex-wrap:wrap;">
            <select name="user_id" class="form-select" style="max-width:200px;">
                <option value="">全部用户</option>
                <?php foreach ($userOptions as $u): ?>
                    <option value="<?= intval($u['id']) ?>" <?= ($userId===$u['id'])?'selected':'' ?>><?= e($u['username']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="search" class="form-input" placeholder="搜索影视标题" value="<?= e($search) ?>" style="min-width:220px;">
            <button type="submit" class="btn btn-primary btn-sm"><i class="icon icon-search"></i> 筛选</button>
            <?php if ($userId || $search): ?>
                <a href="/admin/history.php" class="btn btn-outline btn-sm">重置</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="overflow-x-auto">
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>用户</th>
                <th>影视标题</th>
                <th>类型</th>
                <th>季/集</th>
                <th>观看时长</th>
                <th>播放位置</th>
                <th>最后观看</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($list as $h): ?>
                <tr>
                    <td style="color:var(--text-light);">#<?= intval($h['id']) ?></td>
                    <td>
                        <div class="table-user">
                            <div class="table-user-name" style="font-size:13px;">
                                <a href="/admin/users.php?search=<?= urlencode($h['username']) ?>" style="color:var(--primary);">
                                    <?= e($h['username']) ?>
                                </a>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div style="max-width:260px;">
                            <a href="/detail.php?type=<?= e($h['media_type']) ?>&id=<?= intval($h['media_id']) ?><?= !empty($h['season_number'])?'&season='.intval($h['season_number']):'' ?>"
                               target="_blank" style="font-weight:600;color:var(--primary);" title="<?= e($h['title']) ?>">
                                <?= e(mb_substr($h['title'], 0, 40)) ?>
                            </a>
                            <div style="font-size:11px;color:var(--text-light);">ID:<?= intval($h['media_id']) ?></div>
                        </div>
                    </td>
                    <td><span class="badge badge-blue"><?= ($h['media_type']==='movie')?'电影':'剧集' ?></span></td>
                    <td>
                        <?php if (!empty($h['season_number'])): ?>
                            S<?= intval($h['season_number']) ?>E<?= intval($h['episode_number']) ?>
                        <?php else: ?>
                            <span style="color:var(--text-light);">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        $m = floor(intval($h['watched_seconds'])/60);
                        $s = intval($h['watched_seconds'])%60;
                        $tot = intval($h['total_seconds']);
                        $pct = $tot > 0 ? min(100, round(intval($h['watched_seconds'])/$tot*100)) : 0;
                        ?>
                        <div style="font-size:12px;"><?= $m ?>分<?= $s ?>秒</div>
                        <?php if ($pct > 0): ?>
                            <div style="margin-top:4px;height:4px;background:var(--bg-gray);border-radius:4px;overflow:hidden;width:80px;">
                                <div style="height:100%;width:<?= $pct ?>%;background:var(--primary);"></div>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:12px;color:var(--text-secondary);">
                        <?= floor(intval($h['last_position'])/60) ?>分<?= intval($h['last_position'])%60 ?>秒
                    </td>
                    <td style="font-size:12px;color:var(--text-light);white-space:nowrap;"><?= time_ago($h['updated_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($list)): ?>
                <tr><td colspan="8" class="text-center" style="padding:40px;color:var(--text-light);">暂无匹配的观看历史</td></tr>
            <?php endif; ?>
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
