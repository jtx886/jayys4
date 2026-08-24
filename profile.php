<?php
// 个人中心
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth.php';

// 未登录跳转
if (empty($_SESSION['user_id'])) redirect('/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
$user = current_user();
if (!$user) { session_destroy(); redirect('/login.php'); }

$page_title = '个人中心';
require_once __DIR__ . '/includes/header.php';

$tab = $_GET['tab'] ?? 'profile'; // profile / favorites / history / avatar

$favTotal = 0;
$hisTotal = 0;
$fbTotal = 0;
$db = null;
try {
    if (Database::isConnected()) {
        $db = Database::getInstance();
    }
} catch (Exception $e) {
    $db = null;
}

// ============ 处理头像上传 ============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar']) && $tab === 'avatar' && $db) {
    $file = $_FILES['avatar'];
    if ($file['error'] === 0 && $file['size'] < 5 * 1024 * 1024) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
            $fileName = $user['id'] . '_' . time() . '.' . $ext;
            $target = AVATAR_DIR . '/' . $fileName;
            if (move_uploaded_file($file['tmp_name'], $target)) {
                // 兼容InfinityFree可能的路径问题，存相对URL
                $url = '/uploads/avatars/' . $fileName;
                try {
                    $db->update('users', ['avatar' => $url, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);
                    $user = current_user(); // 刷新
                    $ok = true;
                } catch (Exception $e) {}
            }
        }
    }
}

// ============ 取数据 ============
if ($db) {
    try {
        $favTotal = $db->fetchOne('SELECT COUNT(*) as c FROM favorites WHERE user_id = ?', [$user['id']])['c'];
        $hisTotal = $db->fetchOne('SELECT COUNT(*) as c FROM watch_history WHERE user_id = ?', [$user['id']])['c'];
        $fbTotal = $db->fetchOne('SELECT COUNT(*) as c FROM feedbacks WHERE user_id = ?', [$user['id']])['c'];
    } catch (Exception $e) {
        $favTotal = $hisTotal = $fbTotal = 0;
    }
}
?>

<section class="profile-page">
    <div class="profile-inner">
        <!-- 侧边栏 -->
        <aside class="profile-sidebar">
            <div class="profile-user-card">
                <label for="avatarInput" style="cursor:pointer;">
                    <div class="profile-avatar" title="点击修改头像">
                        <?php if (!empty($user['avatar'])): ?>
                            <img src="<?= e($user['avatar']) ?>" alt="">
                        <?php else: ?>
                            <?= mb_substr($user['username'], 0, 1) ?>
                        <?php endif; ?>
                        <div class="profile-avatar-edit">
                            <i class="icon icon-edit"></i> 换头像
                        </div>
                    </div>
                </label>
                <form id="avatarForm" action="/profile.php?tab=avatar" method="post" enctype="multipart/form-data" style="display:none;">
                    <input type="file" id="avatarInput" name="avatar" accept="image/*" onchange="document.getElementById('avatarForm').submit()">
                </form>
                <div class="profile-name">
                    <?= e($user['username']) ?>
                    <?php if (is_admin()): ?><span class="dev-badge"></span><?php endif; ?>
                </div>
                <div class="profile-email"><?= e($user['email']) ?></div>
            </div>

            <nav class="profile-menu">
                <a href="/profile.php?tab=profile" class="profile-menu-item <?= ($tab === 'profile') ? 'active' : '' ?>">
                    <i class="icon icon-user"></i> 基本资料
                </a>
                <a href="/profile.php?tab=favorites" class="profile-menu-item <?= ($tab === 'favorites') ? 'active' : '' ?>">
                    <i class="icon icon-heart"></i> 我的收藏 <span style="margin-left:auto;"><?= $favTotal ?></span>
                </a>
                <a href="/profile.php?tab=history" class="profile-menu-item <?= ($tab === 'history') ? 'active' : '' ?>">
                    <i class="icon icon-history"></i> 观看历史 <span style="margin-left:auto;"><?= $hisTotal ?></span>
                </a>
                <a href="/profile.php?tab=feedback" class="profile-menu-item <?= ($tab === 'feedback') ? 'active' : '' ?>">
                    <i class="icon icon-message"></i> 我的反馈 <span style="margin-left:auto;"><?= $fbTotal ?></span>
                </a>
                <a href="/profile.php?tab=avatar" class="profile-menu-item <?= ($tab === 'avatar') ? 'active' : '' ?>">
                    <i class="icon icon-edit"></i> 头像设置
                </a>
                <?php if (is_admin()): ?>
                    <a href="/admin/" class="profile-menu-item" style="color:var(--danger);">
                        <i class="icon icon-dashboard"></i> 管理后台
                    </a>
                <?php endif; ?>
                <a href="/api/logout.php" class="profile-menu-item" style="color:var(--danger);">
                    <i class="icon icon-close"></i> 退出登录
                </a>
            </nav>
        </aside>

        <!-- 内容区 -->
        <main class="profile-content">
            <?php if ($tab === 'profile'): ?>
                <h3>基本资料</h3>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;margin-bottom:28px;">
                    <div style="padding:20px;background:linear-gradient(135deg,#e0f2fe,#7dd3fc);border-radius:var(--radius-md);text-align:center;">
                        <div style="font-size:13px;color:#0369a1;margin-bottom:6px;"><i class="icon icon-film"></i> 收藏数量</div>
                        <div style="font-size:28px;font-weight:800;color:#0369a1;"><?= $favTotal ?></div>
                    </div>
                    <div style="padding:20px;background:linear-gradient(135deg,#dcfce7,#86efac);border-radius:var(--radius-md);text-align:center;">
                        <div style="font-size:13px;color:#166534;margin-bottom:6px;"><i class="icon icon-clock"></i> 观看历史</div>
                        <div style="font-size:28px;font-weight:800;color:#166534;"><?= $hisTotal ?></div>
                    </div>
                    <div style="padding:20px;background:linear-gradient(135deg,#fef3c7,#fcd34d);border-radius:var(--radius-md);text-align:center;">
                        <div style="font-size:13px;color:#92400e;margin-bottom:6px;"><i class="icon icon-message"></i> 提交反馈</div>
                        <div style="font-size:28px;font-weight:800;color:#92400e;"><?= $fbTotal ?></div>
                    </div>
                </div>
                <div class="admin-card" style="box-shadow:none;background:var(--bg-gray);padding:24px;">
                    <h4 style="margin-bottom:16px;">账号信息</h4>
                    <div style="display:grid;grid-template-columns:120px 1fr;gap:14px 20px;font-size:14px;">
                        <div style="color:var(--text-light);">用户名</div><div style="font-weight:600;"><?= e($user['username']) ?></div>
                        <div style="color:var(--text-light);">邮箱</div><div><?= e($user['email']) ?></div>
                        <div style="color:var(--text-light);">角色</div><div>
                            <?php if (is_admin()): ?><span class="badge badge-red">管理员</span><?php else: ?><span class="badge badge-blue">普通用户</span><?php endif; ?>
                        </div>
                        <div style="color:var(--text-light);">注册时间</div><div><?= e($user['created_at']) ?></div>
                        <div style="color:var(--text-light);">上次登录</div><div><?= e($user['last_login_at'] ?? '—') ?> (IP: <?= e($user['last_login_ip'] ?? '—') ?>)</div>
                        <div style="color:var(--text-light);">账号状态</div><div>
                            <?php if (is_banned($user)): ?>
                                <span class="badge badge-red">已封禁</span> 至 <?= e($user['banned_until']) ?>，原因：<?= e($user['ban_reason']) ?>
                            <?php else: ?>
                                <span class="badge badge-green">正常</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            <?php elseif ($tab === 'favorites'): ?>
                <h3>我的收藏（共 <?= $favTotal ?> 部）</h3>
                <?php
                $page = max(1, intval($_GET['page'] ?? 1));
                $perPage = 24;
                $offset = ($page - 1) * $perPage;
                $items = [];
                // 处理清空操作
                if ($db && isset($_GET['action']) && $_GET['action'] === 'clear_all' && $tab === 'favorites') {
                    try {
                        $db->delete('favorites', 'user_id = ?', [$user['id']]);
                        echo '<script>location.replace("/profile.php?tab=favorites");</script>';
                        exit;
                    } catch (Exception $e) {}
                }
                if ($db) {
                    try {
                        $items = $db->fetchAll('SELECT * FROM favorites WHERE user_id = ? ORDER BY id DESC LIMIT ? OFFSET ?', [$user['id'], $perPage, $offset]);
                    } catch (Exception $e) { $items = []; }
                }
                ?>
                <?php if (!empty($items)): ?>
                    <div style="margin-bottom:12px;text-align:right;">
                        <button class="btn btn-danger btn-sm" onclick="if(confirm('确定清空所有收藏吗？')){location.href='/profile.php?tab=favorites&action=clear_all';}">
                            <i class="icon icon-trash"></i> 清空全部
                        </button>
                    </div>
                    <div class="media-grid" style="max-width:100%;padding:0;">
                        <?php foreach ($items as $it): ?>
                            <div class="media-card">
                                <a href="/detail.php?type=<?= e($it['media_type']) ?>&id=<?= intval($it['media_id']) ?>">
                                    <div class="media-poster">
                                        <?php $p = tmdb_image($it['poster_path'], 'w342'); ?>
                                        <?php if ($p): ?>
                                            <img src="<?= e($p) ?>" alt="<?= e($it['title']) ?>" loading="lazy">
                                        <?php else: ?>
                                            <div class="media-poster-placeholder"><i class="icon icon-film"></i></div>
                                        <?php endif; ?>
                                        <button class="media-fav-btn active" style="z-index:2;"
                                                onclick="event.preventDefault();event.stopPropagation();removeFav(<?= intval($it['id']) ?>,this,<?= intval($it['media_id']) ?>,'<?= e($it['media_type']) ?>');">
                                            <i class="icon icon-heart active"></i>
                                        </button>
                                    </div>
                                </a>
                                <div class="media-info">
                                    <div class="media-title"><?= e($it['title']) ?></div>
                                    <div class="media-meta">
                                        <span><?= e($it['year']) ?: '—' ?></span>
                                        <span><?= time_ago($it['created_at']) ?> 收藏</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <!-- 分页 -->
                    <?php if ($favTotal > $perPage): ?>
                    <div class="pagination mt-24">
                        <?php $tp = ceil($favTotal / $perPage); for ($i=1; $i<=$tp; $i++): ?>
                            <a href="?tab=favorites&page=<?= $i ?>" class="page-btn <?= ($i === $page) ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon"><i class="icon icon-heart"></i></div>
                        <h3 style="margin-bottom:8px;font-size:18px;">暂无收藏</h3>
                        <p style="color:var(--text-light);">快去首页收藏喜欢的影视吧~</p>
                        <a href="/" class="btn btn-primary btn-sm mt-16">去首页逛逛</a>
                    </div>
                <?php endif; ?>

            <?php elseif ($tab === 'history'): ?>
                <h3>观看历史（共 <?= $hisTotal ?> 条）</h3>
                <?php
                $page = max(1, intval($_GET['page'] ?? 1));
                $perPage = 20;
                $offset = ($page - 1) * $perPage;
                $items = [];
                // 处理批量删除
                if ($db && isset($_GET['action']) && $_GET['action'] === 'delete' && !empty($_GET['ids'])) {
                    try {
                        $ids = explode(',', $_GET['ids']);
                        $db->delete('watch_history', 'user_id = ? AND id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')', array_merge([$user['id']], $ids));
                        echo '<script>location.replace("/profile.php?tab=history");</script>';
                        exit;
                    } catch (Exception $e) {}
                }
                if ($db && isset($_GET['action']) && $_GET['action'] === 'clear_all' && $tab === 'history') {
                    try {
                        $db->delete('watch_history', 'user_id = ?', [$user['id']]);
                        echo '<script>location.replace("/profile.php?tab=history");</script>';
                        exit;
                    } catch (Exception $e) {}
                }
                if ($db) {
                    try {
                        $items = $db->fetchAll('SELECT * FROM watch_history WHERE user_id = ? ORDER BY updated_at DESC LIMIT ? OFFSET ?', [$user['id'], $perPage, $offset]);
                    } catch (Exception $e) { $items = []; }
                }
                ?>
                <?php if (!empty($items)): ?>
                    <div style="margin-bottom:16px;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
                        <label style="font-size:13px;color:var(--text-secondary);">
                            <input type="checkbox" id="chkAll" onchange="toggleAll(this)"> 全选
                        </label>
                        <div style="display:flex;gap:8px;">
                            <button class="btn btn-outline btn-sm" onclick="delSelected()">
                                <i class="icon icon-trash"></i> 删除选中
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="if(confirm('确定清空全部观看历史吗？')){location.href='?tab=history&action=clear_all';}">
                                清空全部
                            </button>
                        </div>
                    </div>
                    <div class="media-grid" style="grid-template-columns:repeat(auto-fill,minmax(200px,1fr));max-width:100%;padding:0;">
                        <?php foreach ($items as $it): ?>
                            <div class="media-card" style="position:relative;" data-id="<?= intval($it['id']) ?>">
                                <label style="position:absolute;top:8px;right:8px;z-index:3;background:rgba(0,0,0,0.5);border-radius:4px;padding:4px 6px;" onclick="event.stopPropagation();">
                                    <input type="checkbox" class="hisChk" value="<?= intval($it['id']) ?>">
                                </label>
                                <a href="/detail.php?type=<?= e($it['media_type']) ?>&id=<?= intval($it['media_id']) ?><?= !empty($it['season_number']) ? '&season=' . intval($it['season_number']) : '' ?>">
                                    <div class="media-poster">
                                        <?php $p = tmdb_image($it['poster_path'], 'w342'); ?>
                                        <?php if ($p): ?>
                                            <img src="<?= e($p) ?>" alt="<?= e($it['title']) ?>" loading="lazy">
                                        <?php else: ?>
                                            <div class="media-poster-placeholder"><i class="icon icon-play"></i></div>
                                        <?php endif; ?>
                                        <?php if (!empty($it['watched_seconds'])): ?>
                                        <div style="position:absolute;left:8px;bottom:8px;background:rgba(0,0,0,0.7);color:#fff;font-size:11px;padding:3px 8px;border-radius:4px;">
                                            <i class="icon icon-clock" style="width:12px;height:12px;"></i>
                                            <?= floor(intval($it['watched_seconds'])/60) ?> 分钟
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </a>
                                <div class="media-info">
                                    <div class="media-title"><?= e($it['title']) ?></div>
                                    <div class="media-meta">
                                        <span>
                                            <?php if (!empty($it['season_number'])): ?>
                                                S<?= intval($it['season_number']) ?>E<?= intval($it['episode_number']) ?>
                                            <?php else: ?>
                                                电影
                                            <?php endif; ?>
                                        </span>
                                        <span><?= time_ago($it['updated_at']) ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($hisTotal > $perPage): ?>
                    <div class="pagination mt-24">
                        <?php $tp = ceil($hisTotal / $perPage); for ($i=1; $i<=$tp; $i++): ?>
                            <a href="?tab=history&page=<?= $i ?>" class="page-btn <?= ($i === $page) ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon"><i class="icon icon-history"></i></div>
                        <h3 style="margin-bottom:8px;font-size:18px;">暂无观看历史</h3>
                        <p style="color:var(--text-light);">开始您的观影旅程吧~</p>
                        <a href="/" class="btn btn-primary btn-sm mt-16">去首页看看</a>
                    </div>
                <?php endif; ?>

            <?php elseif ($tab === 'avatar'): ?>
                <h3>自定义头像</h3>
                <?php if (isset($ok) && $ok): ?>
                    <div class="flash-message success mb-16">✓ 头像上传成功！</div>
                <?php endif; ?>
                <div style="display:flex;gap:40px;align-items:center;flex-wrap:wrap;">
                    <div style="text-align:center;">
                        <div style="width:160px;height:160px;border-radius:50%;background:var(--primary-gradient);display:flex;align-items:center;justify-content:center;overflow:hidden;box-shadow:var(--shadow-md);margin:0 auto 16px;">
                            <?php if (!empty($user['avatar'])): ?>
                                <img src="<?= e($user['avatar']) ?>" style="width:100%;height:100%;object-fit:cover;">
                            <?php else: ?>
                                <span style="font-size:60px;color:#fff;font-weight:700;"><?= mb_substr($user['username'], 0, 1) ?></span>
                            <?php endif; ?>
                        </div>
                        <p style="color:var(--text-light);font-size:13px;">当前头像</p>
                    </div>
                    <div style="flex:1;min-width:260px;">
                        <p style="margin-bottom:12px;color:var(--text-secondary);">上传一张新头像（支持 JPG / PNG / GIF / WEBP，大小不超过 5MB）</p>
                        <form action="/profile.php?tab=avatar" method="post" enctype="multipart/form-data">
                            <div class="form-group">
                                <input type="file" name="avatar" accept="image/*" class="form-input" style="padding:10px;" required>
                            </div>
                            <button type="submit" class="btn btn-primary">上传新头像</button>
                        </form>
                    </div>
                </div>

            <?php elseif ($tab === 'feedback'): ?>
                <h3>我的反馈</h3>
                <?php
                $page = max(1, intval($_GET['page'] ?? 1));
                $perPage = 10;
                $offset = ($page - 1) * $perPage;
                $total = $fbTotal;
                $list = [];
                if ($db) {
                    try {
                        $list = $db->fetchAll('SELECT * FROM feedbacks WHERE user_id = ? ORDER BY id DESC LIMIT ? OFFSET ?', [$user['id'], $perPage, $offset]);
                    } catch (Exception $e) { $list = []; }
                }
                ?>
                <?php if (!empty($list)): ?>
                    <div class="admin-card" style="padding:0;overflow:hidden;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>标题</th>
                                    <th>分类</th>
                                    <th>状态</th>
                                    <th>提交时间</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($list as $fb): ?>
                                    <tr>
                                        <td>#<?= intval($fb['id']) ?></td>
                                        <td style="max-width:280px;"><?= e($fb['title']) ?></td>
                                        <td><?= e($fb['category']) ?></td>
                                        <td>
                                            <?php
                                            $stMap = ['pending'=>['待处理','badge-yellow'],'replied'=>['已回复','badge-blue'],'resolved'=>['已解决','badge-green'],'closed'=>['已关闭','badge-gray']];
                                            $s = $stMap[$fb['status']] ?? ['未知','badge-gray'];
                                            echo '<span class="badge ' . $s[1] . '">' . $s[0] . '</span>';
                                            ?>
                                        </td>
                                        <td><?= e($fb['created_at']) ?></td>
                                        <td><a class="btn btn-outline btn-sm" href="/feedback.php#fb-<?= intval($fb['id']) ?>">查看详情</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($total > $perPage): ?>
                    <div class="pagination mt-24">
                        <?php $tp = ceil($total / $perPage); for ($i=1; $i<=$tp; $i++): ?>
                            <a href="?tab=feedback&page=<?= $i ?>" class="page-btn <?= ($i === $page) ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon"><i class="icon icon-message"></i></div>
                        <h3 style="margin-bottom:8px;font-size:18px;">您还没有提交反馈</h3>
                        <a href="/feedback.php" class="btn btn-primary btn-sm mt-16">去提交反馈</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
</section>

<script>
function removeFav(domId, el, mediaId, type) {
    // 直接切换收藏（POST同个API会取消）
    fetch('/api/favorite.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'media_id='+mediaId+'&media_type='+type+'&title=&poster=&year='
    }).then(r=>r.json()).then(res=>{
        if(res.code===0 && !res.data.favorited){
            el.closest('.media-card').remove();
        }
    });
}
function toggleAll(chk) {
    document.querySelectorAll('.hisChk').forEach(c => c.checked = chk.checked);
}
function delSelected() {
    var ids = [];
    document.querySelectorAll('.hisChk:checked').forEach(c => ids.push(c.value));
    if (!ids.length) { alert('请先选择要删除的记录'); return; }
    if (!confirm('确定删除选中的 ' + ids.length + ' 条记录吗？')) return;
    location.href = '?tab=history&action=delete&ids=' + ids.join(',');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
