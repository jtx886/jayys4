<?php
// 管理后台 - 独立用户收藏模块
$page_title = '用户收藏';
$active_menu = 'favorites';
require_once __DIR__ . '/includes/header.php';

$db = Database::getInstance();
$userId = intval($_GET['user_id'] ?? 0);
$type = $_GET['type'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 30;
$offset = ($page - 1) * $perPage;

$where = []; $params = [];
if ($userId > 0) { $where[] = 'f.user_id = ?'; $params[] = $userId; }
if ($type !== 'all') { $where[] = 'f.media_type = ?'; $params[] = $type; }
if ($search) { $where[] = 'f.title LIKE ?'; $params[] = "%$search%"; }
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$total = intval($db->fetchOne("SELECT COUNT(*) c FROM favorites f $whereSql", $params)['c']);
$list = $db->fetchAll(
    "SELECT f.*, u.username FROM favorites f JOIN users u ON u.id = f.user_id
     $whereSql ORDER BY f.id DESC LIMIT $perPage OFFSET $offset",
    $params
);
$userOptions = $db->fetchAll('SELECT id, username FROM users ORDER BY id DESC LIMIT 300');

// 统计
$stat = [
    'movie' => intval($db->fetchOne("SELECT COUNT(*) c FROM favorites f $whereSql AND f.media_type='movie'", $params)['c']),
    'tv' => intval($db->fetchOne("SELECT COUNT(*) c FROM favorites f $whereSql AND f.media_type IN ('tv','anime','variety')", $params)['c']),
];
?>

<div class="admin-card">
    <div class="admin-card-title">
        <span><i class="icon icon-heart"></i> 用户收藏列表（共 <?= $total ?> 条）</span>
        <div style="display:flex;gap:14px;font-size:13px;">
            <span class="badge badge-blue">电影收藏：<?= $stat['movie'] ?></span>
            <span class="badge badge-green">剧集收藏：<?= $stat['tv'] ?></span>
        </div>
    </div>

    <div class="filter-bar">
        <form method="get" style="display:flex;gap:12px;flex-wrap:wrap;">
            <select name="user_id" class="form-select" style="max-width:200px;">
                <option value="">全部用户</option>
                <?php foreach ($userOptions as $u): ?>
                    <option value="<?= intval($u['id']) ?>" <?= ($userId===$u['id'])?'selected':'' ?>><?= e($u['username']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="type" class="form-select" style="max-width:140px;">
                <option value="all" <?= ($type==='all')?'selected':'' ?>>全部类型</option>
                <option value="movie" <?= ($type==='movie')?'selected':'' ?>>电影</option>
                <option value="tv" <?= ($type==='tv')?'selected':'' ?>>电视剧</option>
                <option value="anime" <?= ($type==='anime')?'selected':'' ?>>动漫</option>
            </select>
            <input type="text" name="search" class="form-input" placeholder="搜索影视标题" value="<?= e($search) ?>" style="min-width:220px;">
            <button type="submit" class="btn btn-primary btn-sm"><i class="icon icon-search"></i> 筛选</button>
            <?php if ($userId || $type !== 'all' || $search): ?>
                <a href="/admin/favorites.php" class="btn btn-outline btn-sm">重置</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- 卡片视图 -->
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:16px;">
        <?php foreach ($list as $f): ?>
            <div class="admin-card" style="margin:0;padding:0;overflow:hidden;">
                <a href="/detail.php?type=<?= e($f['media_type']) ?>&id=<?= intval($f['media_id']) ?>" target="_blank" style="display:block;">
                    <div class="media-poster" style="aspect-ratio:16/9;position:relative;">
                        <?php
                        $p = tmdb_image($f['poster_path'], 'w500');
                        // 用 backdrop
                        ?>
                        <img src="<?= e($p) ?>" alt="<?= e($f['title']) ?>" style="width:100%;height:100%;object-fit:cover;" onerror="this.style.display='none';this.parentNode.style.background='linear-gradient(135deg,var(--primary),var(--primary-dark))';">
                        <div style="position:absolute;top:10px;right:10px;padding:4px 10px;background:rgba(0,0,0,0.7);color:#fff;border-radius:4px;font-size:11px;">
                            <?= $f['media_type']==='movie'?'电影':'剧集' ?>
                        </div>
                    </div>
                </a>
                <div style="padding:12px 14px;">
                    <div style="font-weight:700;margin-bottom:4px;font-size:14px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?= e($f['title']) ?>"><?= e($f['title']) ?></div>
                    <div style="display:flex;justify-content:space-between;align-items:center;font-size:12px;color:var(--text-light);">
                        <span>
                            用户 <a href="/admin/users.php?search=<?= urlencode($f['username']) ?>" style="color:var(--primary);"><?= e($f['username']) ?></a>
                        </span>
                        <span><?= e($f['year']) ?: '—' ?></span>
                    </div>
                    <div style="font-size:11px;color:var(--text-light);margin-top:4px;">收藏于 <?= time_ago($f['created_at']) ?></div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($list)): ?>
            <div style="grid-column:1/-1;padding:40px;text-align:center;color:var(--text-light);background:#fff;border-radius:var(--radius-lg);box-shadow:var(--shadow-sm);">
                暂无匹配的收藏记录
            </div>
        <?php endif; ?>
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
