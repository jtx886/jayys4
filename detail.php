<?php
// 影视详情页
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';

$mediaType = $_GET['type'] ?? 'movie';
$mediaId = intval($_GET['id'] ?? 0);
$season = intval($_GET['season'] ?? 1);
$audio = $_GET['audio'] ?? 'original'; // original / mandarin

if ($mediaId <= 0) { header('Location: /'); exit; }

$isTV = ($mediaType === 'tv' || $mediaType === 'anime' || $mediaType === 'variety');

// TMDB数据
if ($isTV) {
    $detail = tmdb_request('/tv/' . $mediaId, ['append_to_response' => 'credits,videos,seasons,external_ids']);
    $seasonData = tmdb_request('/tv/' . $mediaId . '/season/' . $season, ['append_to_response' => 'credits,videos']);
} else {
    $detail = tmdb_request('/movie/' . $mediaId, ['append_to_response' => 'credits,videos,external_ids']);
    $seasonData = null;
}

if (!$detail) { $detail = []; }

$title = $detail['title'] ?? $detail['name'] ?? '详情';
$page_title = $title;
require_once __DIR__ . '/includes/header.php';

$user = current_user();
$favorited = false;
if ($user) {
    try {
        if (Database::isConnected()) {
            $db = Database::getInstance();
            $fav = $db->fetchOne('SELECT id FROM favorites WHERE user_id = ? AND media_type = ? AND media_id = ?', [$user['id'], $mediaType, $mediaId]);
            $favorited = !!$fav;
        }
    } catch (Exception $e) {
        $favorited = false;
    }
}

$backdrop = tmdb_image($detail['backdrop_path'] ?? '', 'original');
$poster = tmdb_image($detail['poster_path'] ?? '', 'w500');
$rating = number_format($detail['vote_average'] ?? 0, 1);
$year = '';
if (!empty($detail['release_date'])) $year = substr($detail['release_date'], 0, 4);
if (!empty($detail['first_air_date'])) $year = substr($detail['first_air_date'], 0, 4);

// 判断是否国产：production_countries 或 origin_country 含 CN
$isChinese = false;
$countries = $detail['production_countries'] ?? ($detail['origin_country'] ?? []);
if (is_array($countries)) {
    foreach ($countries as $c) {
        $code = is_array($c) ? ($c['iso_3166_1'] ?? '') : $c;
        if (in_array($code, ['CN', 'HK', 'TW'])) { $isChinese = true; break; }
    }
}
// 语言判断
$origLang = $detail['original_language'] ?? '';
if (in_array($origLang, ['zh', 'cmn', 'zh-CN'])) $isChinese = true;

$genres = $detail['genres'] ?? [];
$cast = array_slice($detail['credits']['cast'] ?? [], 0, 12);
$seasons = $detail['seasons'] ?? [];
$episodes = $seasonData['episodes'] ?? [];

// 通过关键词搜索播放源
$searchKeyword = $title;
if (!empty($year)) $searchKeyword .= ' ' . $year;
$sourcesMatch = search_video_source($searchKeyword);

// 原始配音/普通话配音分类
$playItemsOriginal = [];
$playItemsMandarin = [];
$playItems = [];

foreach ($sourcesMatch as $src) {
    $fromName = $src['vod_play_from'] ?? '';
    $isMandarinSource = (stripos($fromName, '国语') !== false || stripos($fromName, '普通话') !== false ||
                         stripos($fromName, '配音') !== false || stripos($fromName, '中字') !== false ||
                         stripos($fromName, '国语') !== false || strpos($fromName, '国') !== false);

    if (!empty($src['vod_play_url'])) {
        $lines = explode('#', $src['vod_play_url']);
        $extracted = [];
        foreach ($lines as $line) {
            if (strpos($line, '$') !== false) {
                list($epName, $epUrl) = explode('$', $line, 2);
                $epNameClean = trim($epName);
                $epUrlClean = trim($epUrl);
                if (!$epUrlClean) continue;
                $extracted[] = ['name' => $epNameClean, 'url' => $epUrlClean];
                // 同时依据集名判断
                $epIsMandarin = $isMandarinSource ||
                    stripos($epNameClean, '国语') !== false ||
                    stripos($epNameClean, '普通话') !== false ||
                    preg_match('/国[\s\-]?语|中[\s\-]?配|普[\s\-]?通[\s\-]?话/u', $epNameClean);
                if ($epIsMandarin) {
                    $playItemsMandarin[] = ['name' => $epNameClean, 'url' => $epUrlClean];
                } else {
                    $playItemsOriginal[] = ['name' => $epNameClean, 'url' => $epUrlClean];
                }
            }
        }
        $playItems = array_merge($playItems, $extracted);
    }
}

// 根据当前选择的配音语言筛选
if (!$isChinese) {
    // 如果当前选普通话，且有普通话源，则用普通话源
    if ($audio === 'mandarin' && !empty($playItemsMandarin)) {
        $playItems = $playItemsMandarin;
    } elseif ($audio === 'original' && !empty($playItemsOriginal)) {
        $playItems = $playItemsOriginal;
    }
    // 否则保持默认所有线路
}

// 获取季封面映射（用TMDB episode still）
$episodeStills = [];
foreach ($episodes as $ep) {
    $episodeStills[intval($ep['episode_number'])] = tmdb_image($ep['still_path'] ?? '', 'w300');
}
?>

<!-- 详情Hero区 -->
<section class="detail-hero">
    <?php if ($backdrop): ?>
        <div class="detail-backdrop" style="background-image:url('<?= e($backdrop) ?>');"></div>
    <?php endif; ?>
    <div class="detail-hero-inner">
        <div class="detail-poster">
            <?php if ($poster): ?>
                <img src="<?= e($poster) ?>" alt="<?= e($title) ?>">
            <?php else: ?>
                <div style="width:100%;height:100%;background:var(--bg-gray);display:flex;align-items:center;justify-content:center;color:var(--text-light);font-size:60px;">
                    <i class="icon icon-<?= $isTV ? 'tv' : 'film' ?>"></i>
                </div>
            <?php endif; ?>
        </div>
        <div class="detail-info">
            <h1>
                <?= e($title) ?>
                <?php if ($year): ?>
                    <span style="font-weight:400;opacity:0.7;font-size:22px;">(<?= e($year) ?>)</span>
                <?php endif; ?>
            </h1>
            <?php if (!empty($detail['tagline'])): ?>
                <div class="detail-tagline">"<?= e($detail['tagline']) ?>"</div>
            <?php endif; ?>
            <div class="detail-meta">
                <?php if ($rating > 0): ?>
                    <div class="detail-rating-big">
                        <i class="icon icon-star"></i>
                        <span class="num"><?= $rating ?></span>
                        <span style="font-size:12px;opacity:0.7;">/ 10</span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($detail['runtime'])): ?>
                    <div class="detail-meta-item"><i class="icon icon-clock"></i> <?= intval($detail['runtime']) ?> 分钟</div>
                <?php endif; ?>
                <?php if (!empty($detail['number_of_episodes'])): ?>
                    <div class="detail-meta-item"><i class="icon icon-tv"></i> <?= intval($detail['number_of_episodes']) ?> 集</div>
                <?php endif; ?>
                <?php if (!empty($detail['status'])): ?>
                    <div class="detail-meta-item"><?= e($detail['status']) ?></div>
                <?php endif; ?>
            </div>
            <?php if (!empty($genres)): ?>
                <div class="detail-genres">
                    <?php foreach ($genres as $g): ?>
                        <span class="genre-tag"><?= e($g['name']) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <h3 class="detail-overview-title">剧情简介</h3>
            <div class="detail-overview"><?= e($detail['overview'] ?? '暂无简介') ?></div>
            <div class="detail-actions">
                <a href="/play.php?type=<?= $mediaType ?>&id=<?= $mediaId ?><?= $isTV ? '&season=' . $season : '' ?>"
                   class="btn btn-primary btn-lg">
                    <i class="icon icon-play" style="color:#fff;"></i> 立即播放
                </a>
                <button class="btn btn-outline btn-lg" style="background:rgba(255,255,255,0.08);border-color:rgba(255,255,255,0.15);color:#fff;"
                        onclick="toggleFav(<?= $mediaId ?>,'<?= $mediaType ?>','<?= e($title) ?>','<?= e($detail['poster_path'] ?? '') ?>','<?= $year ?>', this)">
                    <i class="icon icon-heart <?= $favorited ? 'active' : '' ?>" style="color:<?= $favorited ? '#e74c3c' : 'inherit' ?>;"></i>
                    <span id="favText"><?= $favorited ? '已收藏' : '收藏' ?></span>
                </button>
            </div>
        </div>
    </div>
</section>

<?php if ($isTV && count($seasons) > 1): ?>
<!-- 季选择 -->
<div class="season-tabs">
    <?php foreach ($seasons as $s): ?>
        <?php if (intval($s['season_number']) > 0): ?>
            <a href="?type=<?= $mediaType ?>&id=<?= $mediaId ?>&season=<?= intval($s['season_number']) ?>&audio=<?= e($audio) ?>"
               class="season-tab <?= (intval($s['season_number']) === $season) ? 'active' : '' ?>">
                <?= e($s['name'] ?? ('第' . intval($s['season_number']) . '季')) ?>
            </a>
        <?php endif; ?>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!$isChinese): ?>
<!-- 配音选择（仅非国产） -->
<div class="audio-tabs">
    <a href="?type=<?= $mediaType ?>&id=<?= $mediaId ?><?= $isTV ? '&season=' . $season : '' ?>&audio=original"
       class="audio-tab <?= ($audio === 'original') ? 'active' : '' ?>">🎬 原版配音</a>
    <a href="?type=<?= $mediaType ?>&id=<?= $mediaId ?><?= $isTV ? '&season=' . $season : '' ?>&audio=mandarin"
       class="audio-tab <?= ($audio === 'mandarin') ? 'active' : '' ?>">🀄 普通话配音</a>
</div>
<?php endif; ?>

<?php if ($isTV && !empty($seasonData)): ?>
<!-- 季简介 -->
<section style="padding:20px 20px 0;max-width:var(--container);margin:0 auto;">
    <div style="background:#fff;border-radius:var(--radius-md);padding:20px;box-shadow:var(--shadow-sm);">
        <div style="font-size:18px;font-weight:700;margin-bottom:10px;">
            <?= e($seasonData['name'] ?? '本季') ?>
            <span style="font-size:13px;color:var(--text-light);font-weight:400;margin-left:10px;">
                <?= e($seasonData['air_date'] ?? '') ?> · <?= count($episodes) ?> 集
                <?php if (!empty($seasonData['vote_average']) && $seasonData['vote_average'] > 0): ?>
                    · <i class="icon icon-star" style="color:#fbbf24;"></i> <?= number_format($seasonData['vote_average'], 1) ?>
                <?php endif; ?>
            </span>
        </div>
        <div style="color:var(--text-secondary);line-height:1.7;"><?= e($seasonData['overview'] ?? '暂无简介') ?></div>
    </div>
</section>
<?php endif; ?>

<!-- 集数列表 -->
<?php if ($isTV && !empty($episodes)): ?>
<section class="episodes-wrapper" style="margin-top:24px;">
    <h3 class="episodes-title">全部剧集（共 <?= count($episodes) ?> 集）</h3>
    <div class="episode-list">
        <?php foreach ($episodes as $idx => $ep): ?>
            <?php
                $epNum = intval($ep['episode_number'] ?? ($idx + 1));
                $still = $episodeStills[$epNum] ?? tmdb_image($ep['still_path'] ?? '', 'w300');
                $epName = $ep['name'] ?? ('第' . $epNum . '集');
                $playUrl = '/play.php?type=' . $mediaType . '&id=' . $mediaId . '&season=' . $season . '&episode=' . $epNum . '&audio=' . $audio;
                // 匹配播放源
                $matchedSource = '';
                foreach ($playItems as $pi) {
                    if (strpos($pi['name'], (string)$epNum) !== false || strpos($pi['name'], '第' . $epNum) !== false) {
                        $matchedSource = $pi['url'];
                        break;
                    }
                }
            ?>
            <a class="episode-item" href="<?= $matchedSource ? $playUrl . '&src=' . urlencode($matchedSource) : 'javascript:alert(\'暂无播放源\');' ?>">
                <?php if ($still): ?>
                    <img class="episode-still" src="<?= e($still) ?>" alt="" loading="lazy">
                <?php else: ?>
                    <div class="episode-still" style="display:flex;align-items:center;justify-content:center;font-size:24px;color:var(--text-light);font-weight:700;">
                        <?= $epNum ?>
                    </div>
                <?php endif; ?>
                <div class="episode-info">
                    <div class="episode-index">第 <?= $epNum ?> 集</div>
                    <div class="episode-name"><?= e($epName) ?></div>
                    <div class="episode-desc"><?= e(mb_substr($ep['overview'] ?? '', 0, 60)) ?>...</div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php elseif (!$isTV): ?>
<!-- 电影 - 直接显示播放入口 -->
<section class="episodes-wrapper" style="margin-top:24px;">
    <h3 class="episodes-title">播放</h3>
    <?php if (!empty($playItems)): ?>
        <div class="source-selector">
            <?php foreach (array_slice($playItems, 0, 6) as $idx => $pi): ?>
                <a class="source-card" href="/play.php?type=<?= $mediaType ?>&id=<?= $mediaId ?>&audio=<?= $audio ?>&src=<?= urlencode($pi['url']) ?>&title=<?= urlencode($pi['name']) ?>">
                    <div class="source-name"><i class="icon icon-play"></i> <?= e($pi['name']) ?></div>
                    <div class="source-type">线路 <?= $idx + 1 ?></div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state" style="background:#fff;border-radius:var(--radius-lg);box-shadow:var(--shadow-sm);">
            <div class="empty-icon"><i class="icon icon-play"></i></div>
            <h3 style="margin-bottom:8px;font-size:18px;">暂无匹配的播放源</h3>
            <p style="color:var(--text-light);">请稍后再试或尝试搜索其他内容</p>
            <a href="/play.php?type=<?= $mediaType ?>&id=<?= $mediaId ?>&audio=<?= $audio ?>&title=<?= urlencode($title) ?>"
               class="btn btn-primary btn-sm mt-16">
                尝试播放 <i class="icon icon-arrow-right" style="color:#fff;"></i>
            </a>
        </div>
    <?php endif; ?>
</section>
<?php endif; ?>

<!-- 演员表 -->
<?php if (!empty($cast)): ?>
<section class="cast-section">
    <div class="section-header" style="padding:0 20px;margin-bottom:24px;">
        <h2 class="section-title">演员表</h2>
    </div>
    <div class="cast-grid">
        <?php foreach ($cast as $c): ?>
            <div class="cast-card">
                <?php $p = tmdb_image($c['profile_path'] ?? '', 'w185'); ?>
                <?php if ($p): ?>
                    <img class="cast-avatar" src="<?= e($p) ?>" alt="<?= e($c['name']) ?>" loading="lazy">
                <?php else: ?>
                    <div class="cast-avatar" style="display:flex;align-items:center;justify-content:center;font-size:24px;color:#fff;">
                        <?= mb_substr($c['name'], 0, 1) ?>
                    </div>
                <?php endif; ?>
                <div class="cast-name"><?= e($c['name']) ?></div>
                <div class="cast-role"><?= e($c['character'] ?? '') ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<script>
function toggleFav(id, type, title, poster, year, el) {
    <?php if ($user): ?>
    fetch('/api/favorite.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'media_id='+id+'&media_type='+type+'&title='+encodeURIComponent(title)+'&poster='+encodeURIComponent(poster)+'&year='+encodeURIComponent(year)
    }).then(r=>r.json()).then(res=>{
        if(res.code===0){
            var icon = el.querySelector('.icon-heart');
            var text = document.getElementById('favText');
            if(res.data.favorited){
                icon.classList.add('active');
                icon.style.color = '#e74c3c';
                text.textContent = '已收藏';
            }else{
                icon.classList.remove('active');
                icon.style.color = '';
                text.textContent = '收藏';
            }
        }
    });
    <?php else: ?>
    location.href='/login.php?redirect='+encodeURIComponent(location.href);
    <?php endif; ?>
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
