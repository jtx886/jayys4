<?php
// 管理后台 - 仪表盘
$page_title = '仪表盘';
$active_menu = 'dashboard';
require_once __DIR__ . '/includes/header.php';

$db = Database::getInstance();

// 统计数据
$stats = [
    'users' => intval($db->fetchOne('SELECT COUNT(*) c FROM users WHERE role = "user"')['c']),
    'admins' => intval($db->fetchOne('SELECT COUNT(*) c FROM users WHERE role = "admin"')['c']),
    'banned' => intval($db->fetchOne('SELECT COUNT(*) c FROM users WHERE banned_until IS NOT NULL AND banned_until > NOW()')['c']),
    'today_new' => intval($db->fetchOne('SELECT COUNT(*) c FROM users WHERE DATE(created_at) = CURDATE()')['c']),
    'feedback' => intval($db->fetchOne('SELECT COUNT(*) c FROM feedbacks')['c']),
    'pending_fb' => intval($db->fetchOne('SELECT COUNT(*) c FROM feedbacks WHERE status = "pending"')['c']),
    'favorites' => intval($db->fetchOne('SELECT COUNT(*) c FROM favorites')['c']),
    'history' => intval($db->fetchOne('SELECT COUNT(*) c FROM watch_history')['c']),
    'sources' => intval($db->fetchOne('SELECT COUNT(*) c FROM play_sources')['c']),
    'announcements' => intval($db->fetchOne('SELECT COUNT(*) c FROM announcements')['c']),
];

// 最新注册用户
$newUsers = $db->fetchAll('SELECT id, username, email, avatar, created_at FROM users ORDER BY id DESC LIMIT 8');

// 最新反馈
$newFeedbacks = $db->fetchAll(
    'SELECT f.id, f.title, f.status, f.created_at, u.username, u.avatar
     FROM feedbacks f JOIN users u ON u.id = f.user_id ORDER BY f.id DESC LIMIT 6'
);

// 最新观看历史
$newHistory = $db->fetchAll(
    'SELECT h.*, u.username FROM watch_history h JOIN users u ON u.id = h.user_id ORDER BY h.updated_at DESC LIMIT 8'
);

// 最新收藏
$newFavorites = $db->fetchAll(
    'SELECT f.*, u.username FROM favorites f JOIN users u ON u.id = f.user_id ORDER BY f.id DESC LIMIT 8'
);
?>

<!-- 统计卡片 -->
<div class="dashboard-stats">
    <div class="stat-card">
        <div class="stat-label"><i class="icon icon-users"></i> 总用户数</div>
        <div class="stat-value"><?= $stats['users'] ?></div>
        <div class="stat-trend">↑ 今日新增 <?= $stats['today_new'] ?> 人</div>
    </div>
    <div class="stat-card success">
        <div class="stat-label"><i class="icon icon-message"></i> 总反馈数</div>
        <div class="stat-value"><?= $stats['feedback'] ?></div>
        <div class="stat-trend <?= $stats['pending_fb'] > 0 ? 'down' : '' ?>">
            <?= $stats['pending_fb'] > 0 ? '!' : '✓' ?> 待处理 <?= $stats['pending_fb'] ?> 条
        </div>
    </div>
    <div class="stat-card warning">
        <div class="stat-label"><i class="icon icon-heart"></i> 总收藏数</div>
        <div class="stat-value"><?= $stats['favorites'] ?></div>
        <div class="stat-trend">总观看历史 <?= $stats['history'] ?> 条</div>
    </div>
    <div class="stat-card danger">
        <div class="stat-label"><i class="icon icon-ban"></i> 封禁用户</div>
        <div class="stat-value"><?= $stats['banned'] ?></div>
        <div class="stat-trend down">管理员 <?= $stats['admins'] ?> 人 · 播放源 <?= $stats['sources'] ?> 个</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:24px;">
    <!-- 最新注册 -->
    <div class="admin-card">
        <div class="admin-card-title">
            <span><i class="icon icon-user"></i> 最新注册用户</span>
            <a href="/admin/users.php" class="btn btn-outline btn-sm">全部用户</a>
        </div>
        <table class="data-table">
            <thead><tr><th>用户</th><th>邮箱</th><th>注册时间</th></tr></thead>
            <tbody>
                <?php foreach ($newUsers as $u): ?>
                    <tr>
                        <td>
                            <div class="table-user">
                                <div class="table-avatar">
                                    <?php if (!empty($u['avatar'])): ?><img src="<?= e($u['avatar']) ?>"><?php else: ?><?= mb_substr($u['username'],0,1) ?><?php endif; ?>
                                </div>
                                <div class="table-user-name"><?= e($u['username']) ?></div>
                            </div>
                        </td>
                        <td><?= e($u['email']) ?></td>
                        <td style="color:var(--text-light);font-size:13px;"><?= e($u['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- 最新反馈 -->
    <div class="admin-card">
        <div class="admin-card-title">
            <span><i class="icon icon-message"></i> 最新反馈</span>
            <a href="/admin/feedback.php" class="btn btn-outline btn-sm">管理反馈</a>
        </div>
        <table class="data-table">
            <thead><tr><th>标题</th><th>用户</th><th>状态</th><th>时间</th></tr></thead>
            <tbody>
                <?php foreach ($newFeedbacks as $f):
                    $stMap = ['pending'=>['待处理','badge-yellow'],'replied'=>['已回复','badge-blue'],'resolved'=>['已解决','badge-green'],'closed'=>['已关闭','badge-gray']];
                    $s = $stMap[$f['status']] ?? ['未知','badge-gray'];
                ?>
                    <tr>
                        <td><a href="/admin/feedback.php#fb-<?= intval($f['id']) ?>" style="color:var(--primary);font-weight:500;"><?= e(mb_substr($f['title'],0,28)) ?></a></td>
                        <td><?= e($f['username']) ?></td>
                        <td><span class="badge <?= $s[1] ?>"><?= $s[0] ?></span></td>
                        <td style="color:var(--text-light);font-size:13px;"><?= time_ago($f['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:24px;margin-top:0;">
    <!-- 观看历史模块（独立） -->
    <div class="admin-card">
        <div class="admin-card-title">
            <span><i class="icon icon-history"></i> 观看历史</span>
            <div style="display:flex;gap:8px;">
                <select class="form-input form-select" style="width:160px;padding:6px 10px;" id="histUserSel" onchange="filterHistory(this.value)">
                    <option value="">全部用户</option>
                    <?php
                    $usersSel = $db->fetchAll('SELECT id, username FROM users ORDER BY id DESC LIMIT 50');
                    foreach ($usersSel as $us):
                    ?>
                        <option value="<?= intval($us['id']) ?>"><?= e($us['username']) ?></option>
                    <?php endforeach; ?>
                </select>
                <a href="/admin/history.php" class="btn btn-outline btn-sm">查看全部</a>
            </div>
        </div>
        <div id="histList" style="max-height:340px;overflow-y:auto;">
            <table class="data-table">
                <thead><tr><th>用户</th><th>标题</th><th>季/集</th><th>时长</th><th>更新</th></tr></thead>
                <tbody>
                    <?php foreach ($newHistory as $h): ?>
                        <tr>
                            <td style="font-size:13px;font-weight:500;"><?= e($h['username']) ?></td>
                            <td style="max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-size:13px;"><?= e($h['title']) ?></td>
                            <td style="font-size:12px;color:var(--text-light);">
                                <?php if (!empty($h['season_number'])): ?>S<?= intval($h['season_number']) ?>E<?= intval($h['episode_number']) ?><?php else: ?>电影<?php endif; ?>
                            </td>
                            <td style="font-size:12px;"><?= floor(intval($h['watched_seconds'])/60) ?>分</td>
                            <td style="font-size:12px;color:var(--text-light);"><?= time_ago($h['updated_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 用户收藏模块（独立） -->
    <div class="admin-card">
        <div class="admin-card-title">
            <span><i class="icon icon-heart"></i> 用户收藏</span>
            <div style="display:flex;gap:8px;">
                <select class="form-input form-select" style="width:160px;padding:6px 10px;" id="favUserSel" onchange="filterFav(this.value)">
                    <option value="">全部用户</option>
                    <?php foreach ($usersSel as $us): ?>
                        <option value="<?= intval($us['id']) ?>"><?= e($us['username']) ?></option>
                    <?php endforeach; ?>
                </select>
                <a href="/admin/favorites.php" class="btn btn-outline btn-sm">查看全部</a>
            </div>
        </div>
        <div id="favList" style="max-height:340px;overflow-y:auto;">
            <table class="data-table">
                <thead><tr><th>用户</th><th>影视</th><th>类型</th><th>年份</th><th>时间</th></tr></thead>
                <tbody>
                    <?php foreach ($newFavorites as $f): ?>
                        <tr>
                            <td style="font-size:13px;font-weight:500;"><?= e($f['username']) ?></td>
                            <td style="max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-size:13px;">
                                <a href="/detail.php?type=<?= e($f['media_type']) ?>&id=<?= intval($f['media_id']) ?>" target="_blank" style="color:var(--primary);">
                                    <?= e($f['title']) ?>
                                </a>
                            </td>
                            <td style="font-size:12px;"><span class="badge badge-blue"><?= $f['media_type'] === 'movie' ? '电影' : '剧集' ?></span></td>
                            <td style="font-size:12px;color:var(--text-light);"><?= e($f['year']) ?: '—' ?></td>
                            <td style="font-size:12px;color:var(--text-light);"><?= time_ago($f['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function filterHistory(uid) {
    if (!uid) location.reload();
    location.href = '/admin/history.php?user_id=' + uid;
}
function filterFav(uid) {
    if (!uid) location.reload();
    location.href = '/admin/favorites.php?user_id=' + uid;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
