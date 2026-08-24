<?php
// 首页
$page_title = '首页';
require_once __DIR__ . '/includes/header.php';

// 获取各分类热门数据
$movies = tmdb_request('/movie/popular', ['page' => 1]) ?: ['results' => []];
$tvShows = tmdb_request('/tv/popular', ['page' => 1]) ?: ['results' => []];
$topRated = tmdb_request('/movie/top_rated', ['page' => 1]) ?: ['results' => []];
$upcoming = tmdb_request('/movie/now_playing', ['page' => 1]) ?: ['results' => []];

function render_card($item, $type = 'movie') {
    $id = $item['id'] ?? 0;
    $title = $item['title'] ?? $item['name'] ?? '未知';
    $poster = tmdb_image($item['poster_path'] ?? '', 'w342');
    $rating = number_format($item['vote_average'] ?? 0, 1);
    $year = '';
    if (!empty($item['release_date'])) $year = substr($item['release_date'], 0, 4);
    if (!empty($item['first_air_date'])) $year = substr($item['first_air_date'], 0, 4);
    $user = current_user();
    $favorited = false;
    if ($user) {
        try {
            if (Database::isConnected()) {
                $db = Database::getInstance();
                $fav = $db->fetchOne(
                    'SELECT id FROM favorites WHERE user_id = ? AND media_type = ? AND media_id = ?',
                    [$user['id'], $type, $id]
                );
                $favorited = !!$fav;
            }
        } catch (Exception $e) {
            $favorited = false;
        }
    }
    $link = '/detail.php?type=' . $type . '&id=' . $id;
    $favIcon = $favorited ? 'icon-heart active' : 'icon-heart';
    ?>
    <a href="<?= $link ?>" class="media-card" onclick="event.preventDefault();location.href='<?= $link ?>'">
      <div class="media-poster">
        <?php if ($poster): ?>
          <img src="<?= e($poster) ?>" alt="<?= e($title) ?>" loading="lazy">
        <?php else: ?>
          <div class="media-poster-placeholder">
            <i class="icon icon-<?= $type === 'tv' ? 'tv' : 'film' ?>"></i>
          </div>
        <?php endif; ?>
        <?php if ($rating > 0): ?>
          <div class="media-rating">
            <i class="icon icon-star"></i>
            <span><?= $rating ?></span>
          </div>
        <?php endif; ?>
        <div class="media-fav-btn <?= $favorited ? 'active' : '' ?>"
             onclick="event.preventDefault();event.stopPropagation();toggleFav(this,<?= $id ?>,'<?= $type ?>','<?= e($title) ?>','<?= e($item['poster_path'] ?? '') ?>','<?= $year ?>')">
          <i class="icon <?= $favIcon ?>"></i>
        </div>
      </div>
      <div class="media-info">
        <div class="media-title"><?= e($title) ?></div>
        <div class="media-meta">
          <span><?= e($year) ?: '—' ?></span>
          <span><i class="icon icon-play"></i> 播放</span>
        </div>
      </div>
    </a>
<?php }
?>

<!-- Hero区 -->
<section class="hero">
  <div class="hero-content">
    <h1>欢迎来到 Jay影视</h1>
    <p>海量高清影视资源，免费在线观看。电影、电视剧、动漫、综艺一应俱全。</p>
    <form action="/search.php" method="GET" class="hero-search">
      <input type="text" name="q" placeholder="搜索你想看的电影、电视剧、动漫...">
      <button type="submit">搜 索</button>
    </form>
  </div>
</section>

<!-- 分类标签 -->
<div class="category-bar">
  <div class="category-inner">
    <a href="/search.php?type=all" class="category-tab active">全部</a>
    <a href="/search.php?type=movie" class="category-tab">电影</a>
    <a href="/search.php?type=tv" class="category-tab">电视剧</a>
    <a href="/search.php?type=anime" class="category-tab">动漫</a>
    <a href="/search.php?type=variety" class="category-tab">综艺</a>
    <a href="/search.php?type=movie&sort=top" class="category-tab">高分电影</a>
    <a href="/search.php?type=tv&sort=top" class="category-tab">高分剧集</a>
    <a href="/search.php?type=movie&sort=new" class="category-tab">最新上映</a>
  </div>
</div>

<!-- 正在热播 -->
<section class="section">
  <div class="section-header">
    <h2 class="section-title">正在热播</h2>
    <a href="/search.php?type=movie&sort=hot" class="section-more">查看更多 <i class="icon icon-arrow-right" style="width:14px;height:14px;"></i></a>
  </div>
  <div class="media-grid">
    <?php foreach (array_slice($upcoming['results'] ?? [], 0, 12) as $item): ?>
      <?php render_card($item, 'movie'); ?>
    <?php endforeach; ?>
    <?php if (empty($upcoming['results'])): ?>
      <div class="media-grid" style="grid-template-columns:1fr;">
        <div class="empty-state">
          <div class="empty-icon"><i class="icon icon-film"></i></div>
          <p>暂无数据。请先在 <code>/config/config.php</code> 中配置 TMDB API Key</p>
          <p style="margin-top:10px;"><a href="/install.php" class="btn btn-primary btn-sm">运行安装向导</a></p>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- 热门电影 -->
<section class="section" style="background:var(--bg-gray);">
  <div class="section-header">
    <h2 class="section-title">热门电影</h2>
    <a href="/search.php?type=movie" class="section-more">查看更多 <i class="icon icon-arrow-right" style="width:14px;height:14px;"></i></a>
  </div>
  <div class="media-grid">
    <?php foreach (array_slice($movies['results'] ?? [], 0, 12) as $item): ?>
      <?php render_card($item, 'movie'); ?>
    <?php endforeach; ?>
  </div>
</section>

<!-- 热门电视剧 -->
<section class="section">
  <div class="section-header">
    <h2 class="section-title">热门电视剧</h2>
    <a href="/search.php?type=tv" class="section-more">查看更多 <i class="icon icon-arrow-right" style="width:14px;height:14px;"></i></a>
  </div>
  <div class="media-grid">
    <?php foreach (array_slice($tvShows['results'] ?? [], 0, 12) as $item): ?>
      <?php render_card($item, 'tv'); ?>
    <?php endforeach; ?>
  </div>
</section>

<!-- 高分佳作 -->
<section class="section" style="background:var(--bg-gray);">
  <div class="section-header">
    <h2 class="section-title">高分佳作</h2>
    <a href="/search.php?type=all&sort=top" class="section-more">查看更多 <i class="icon icon-arrow-right" style="width:14px;height:14px;"></i></a>
  </div>
  <div class="media-grid">
    <?php foreach (array_slice($topRated['results'] ?? [], 0, 12) as $item): ?>
      <?php render_card($item, 'movie'); ?>
    <?php endforeach; ?>
  </div>
</section>

<script>
function toggleFav(el, id, type, title, poster, year) {
  <?php if (current_user()): ?>
  fetch('/api/favorite.php', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: 'media_id='+id+'&media_type='+type+'&title='+encodeURIComponent(title)+'&poster='+encodeURIComponent(poster)+'&year='+encodeURIComponent(year)
  }).then(r=>r.json()).then(res=>{
    if(res.code===0){
      el.classList.toggle('active');
      var icon = el.querySelector('.icon-heart, .icon');
      if(icon){
        icon.classList.toggle('active');
      }
    } else alert(res.message);
  });
  <?php else: ?>
  location.href='/login.php?redirect='+encodeURIComponent(location.href);
  <?php endif; ?>
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
