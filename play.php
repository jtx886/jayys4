<?php
// 播放页 - 要求登录才能访问
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth.php';

// 未登录强制跳转
if (empty($_SESSION['user_id'])) {
    redirect('/login.php?redirect=' . urlencode(current_url()), '需要登录才可以观看哦，如没有账号请注册！');
}
$user = current_user();
if (!$user) { session_destroy(); redirect('/login.php', '请重新登录'); }
if (is_banned($user)) {
    $info = ban_info($user);
    session_destroy();
    redirect('/login.php', '账号已被封禁：' . $info['reason']);
}

$mediaType = $_GET['type'] ?? 'movie';
$mediaId = intval($_GET['id'] ?? 0);
$season = intval($_GET['season'] ?? 1);
$episode = intval($_GET['episode'] ?? 1);
$audio = $_GET['audio'] ?? 'original';
$sourceUrl = isset($_GET['src']) ? urldecode($_GET['src']) : '';
$epTitle = isset($_GET['title']) ? urldecode($_GET['title']) : '';

// 获取基础信息
if ($mediaType === 'tv' || $mediaType === 'anime' || $mediaType === 'variety') {
    $detail = tmdb_request('/tv/' . $mediaId);
    $seasonData = tmdb_request('/tv/' . $mediaId . '/season/' . $season);
} else {
    $detail = tmdb_request('/movie/' . $mediaId);
    $seasonData = null;
}
$title = $detail['title'] ?? $detail['name'] ?? '未知';
$posterPath = $detail['poster_path'] ?? '';

$page_title = ($epTitle ? $epTitle . ' - ' : '') . $title;
require_once __DIR__ . '/includes/header.php';

// 如果没有传直接播放源，尝试搜索
if (empty($sourceUrl)) {
    $keyword = $title;
    if (!empty($detail['release_date'])) $keyword .= ' ' . substr($detail['release_date'], 0, 4);
    if ($episode > 1) $keyword .= ' ' . $episode;
    $sources = search_video_source($keyword);
    foreach ($sources as $src) {
        if (!empty($src['vod_play_url'])) {
            $lines = explode('#', $src['vod_play_url']);
            foreach ($lines as $line) {
                if (strpos($line, '$') !== false) {
                    list($n, $u) = explode('$', $line, 2);
                    $sourceUrl = trim($u);
                    if (empty($epTitle)) $epTitle = trim($n);
                    break 2;
                }
            }
        }
    }
}

// 组装播放器url
$playerSrc = PLAYER_URL . urlencode($sourceUrl);

// 获取播放源列表（后台配置的）
$playSources = get_play_sources();

// 构建记录ID
$episodeId = $season . 'S' . $episode . 'E' . 'A' . $audio;
?>

<section class="player-page">
    <div class="player-container">
        <?php $flash = flash_message(); ?>
        <?php if ($flash): ?>
            <div class="flash-message info" style="margin-bottom:20px;">
                <i class="icon icon-bell" style="color:inherit;"></i>
                <?= e($flash) ?>
            </div>
        <?php endif; ?>

        <!-- 播放器 -->
        <div class="player-wrapper">
            <?php if (!empty($sourceUrl)): ?>
                <iframe id="videoPlayer" src="<?= e($playerSrc) ?>" allowfullscreen allow="autoplay; encrypted-media" referrerpolicy="no-referrer"></iframe>
            <?php else: ?>
                <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#fff;background:linear-gradient(135deg,#111,#333);">
                    <div style="text-align:center;padding:40px;">
                        <div style="font-size:60px;margin-bottom:20px;opacity:0.4;">
                            <i class="icon icon-film" style="width:60px;height:60px;"></i>
                        </div>
                        <h3 style="margin-bottom:10px;">暂无可用播放源</h3>
                        <p style="opacity:0.6;margin-bottom:20px;">请稍后再试或切换其他影视</p>
                        <a href="/detail.php?type=<?= $mediaType ?>&id=<?= $mediaId ?>&season=<?= $season ?>" class="btn btn-primary btn-sm">返回详情页</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- 播放源选择 -->
        <?php if (!empty($playSources)): ?>
        <div class="source-selector">
            <?php foreach ($playSources as $idx => $src): ?>
                <a class="source-card <?= ($idx === 0) ? 'active' : '' ?>" href="?type=<?= $mediaType ?>&id=<?= $mediaId ?>&season=<?= $season ?>&episode=<?= $episode ?>&audio=<?= $audio ?><?= !empty($sourceUrl) ? '&src=' . urlencode($sourceUrl) : '' ?>">
                    <div class="source-name"><?= e($src['name']) ?></div>
                    <div class="source-type">线路 <?= $idx + 1 ?></div>
                </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- 信息区 -->
        <div class="play-info">
            <h2>
                <a href="/detail.php?type=<?= $mediaType ?>&id=<?= $mediaId ?><?= $season > 0 ? '&season=' . $season : '' ?>" style="color:inherit;">
                    <?= e($title) ?>
                </a>
                <?php if (!empty($epTitle)): ?>
                    <span style="font-weight:400;color:var(--text-light);font-size:16px;"> - <?= e($epTitle) ?></span>
                <?php endif; ?>
            </h2>
            <div style="color:var(--text-secondary);margin-bottom:12px;font-size:13px;">
                <?php if ($mediaType !== 'movie'): ?>
                    第 <?= $season ?> 季 · 第 <?= $episode ?> 集
                    <?php if ($audio === 'mandarin'): ?> · <span class="badge badge-green">普通话配音</span><?php else: ?> · <span class="badge badge-blue">原版配音</span><?php endif; ?>
                <?php endif; ?>
            </div>
            <div style="color:var(--text-secondary);line-height:1.7;margin-bottom:16px;">
                <?= e(mb_substr($detail['overview'] ?? '', 0, 200)) ?>...
            </div>
            <div class="play-actions">
                <a href="/detail.php?type=<?= $mediaType ?>&id=<?= $mediaId ?><?= $season > 0 ? '&season=' . $season : '' ?>" class="btn btn-outline">
                    <i class="icon icon-arrow-right" style="transform:rotate(180deg);"></i> 返回详情
                </a>
                <a href="/" class="btn btn-outline"><i class="icon icon-home"></i> 返回首页</a>
                <?php if ($mediaType !== 'movie' && $episode > 1): ?>
                    <a href="?type=<?= $mediaType ?>&id=<?= $mediaId ?>&season=<?= $season ?>&episode=<?= $episode - 1 ?>&audio=<?= $audio ?><?= !empty($sourceUrl) ? '&src=' . urlencode($sourceUrl) : '' ?>"
                       class="btn btn-outline">上一集</a>
                <?php endif; ?>
                <?php if ($mediaType !== 'movie'): ?>
                    <a href="?type=<?= $mediaType ?>&id=<?= $mediaId ?>&season=<?= $season ?>&episode=<?= $episode + 1 ?>&audio=<?= $audio ?><?= !empty($sourceUrl) ? '&src=' . urlencode($sourceUrl) : '' ?>"
                       class="btn btn-primary">下一集 →</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
// 记录观看历史（每30秒上报一次）
(function(){
    var started = false;
    var seconds = 0;
    function report() {
        var fd = new FormData();
        fd.append('action', 'update');
        fd.append('media_id', <?= $mediaId ?>);
        fd.append('media_type', '<?= $mediaType ?>');
        fd.append('episode_id', '<?= e($episodeId) ?>');
        fd.append('title', <?= json_encode($epTitle ? $epTitle . ' - ' . $title : $title) ?>);
        fd.append('poster', <?= json_encode($posterPath) ?>);
        fd.append('season_number', <?= $season ?>);
        fd.append('episode_number', <?= $episode ?>);
        fd.append('watched_seconds', seconds);
        fd.append('source_url', <?= json_encode($sourceUrl) ?>);
        fd.append('last_position', seconds);
        navigator.sendBeacon ? navigator.sendBeacon('/api/history.php', fd)
            : fetch('/api/history.php', {method:'POST', body:fd, keepalive:true});
    }
    setInterval(function(){
        seconds += 30;
        report();
    }, 30000);
    // 页面关闭前上报
    window.addEventListener('beforeunload', report);
    // 5秒后初始记录
    setTimeout(function(){ started = true; report(); }, 5000);
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
