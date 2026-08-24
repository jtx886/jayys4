<?php
// 搜索页面
$page_title = '搜索';
require_once __DIR__ . '/includes/header.php';

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$type = isset($_GET['type']) ? $_GET['type'] : 'all';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

$results = [];
$totalPages = 1;

if (!empty($q)) {
  $tmdbType = ($type === 'tv') ? 'tv' : 'movie';
  $searchType = ($type === 'all') ? 'multi' : $tmdbType;
  if ($type === 'anime') { $searchType = 'multi'; }
  if ($type === 'variety') { $searchType = 'tv'; }
  $data = tmdb_request('/search/' . $searchType, ['query' => $q, 'page' => $page, 'include_adult' => 'false']);
  if ($data) {
    $results = $data['results'] ?? [];
    if ($type === 'anime') {
      // 简单过滤动画类型
      $results = array_filter($results, function($r) {
        if (isset($r['media_type']) && $r['media_type'] === 'person') return false;
        return true;
      });
    }
    if ($type === 'variety') {
      $results = array_filter($results, function($r) {
        $name = $r['name'] ?? '';
        return strpos($name, '综艺') !== false || strpos($name, '真人秀') !== false || strpos($name, '脱口秀') !== false;
      });
    }
    $totalPages = min(intval($data['total_pages'] ?? 1), 500);
  }
} else {
  // 浏览模式：根据类型取热门
  $endpoint = ($type === 'tv') ? '/tv/popular' : '/movie/popular';
  if ($type === 'anime') $endpoint = '/discover/tv';
  $params = ['page' => $page];
  if ($type === 'anime') {
    $params['with_genres'] = '16'; // Animation
    $params['with_original_language'] = 'ja';
  }
  if ($type === 'variety') {
    $endpoint = '/discover/tv';
    $params['with_genres'] = '10764'; // Reality
  }
  $sort = $_GET['sort'] ?? '';
  if ($sort === 'top') {
    $endpoint = ($type === 'tv' || $type === 'variety' || $type === 'anime') ? '/tv/top_rated' : '/movie/top_rated';
  }
  if ($sort === 'new') {
    $endpoint = '/movie/upcoming';
  }
  $data = tmdb_request($endpoint, $params);
  if ($data) {
    $results = $data['results'] ?? [];
    $totalPages = min(intval($data['total_pages'] ?? 1), 500);
  }
}

$typeLabels = [
  'all' => '全部',
  'movie' => '电影',
  'tv' => '电视剧',
  'anime' => '动漫',
  'variety' => '综艺'
];
$displayType = $type;
?>

<section class="search-page">
  <div class="search-header">
    <div class="search-title">
      <?php if (!empty($q)): ?>
        搜索结果："<?= e($q) ?>"
      <?php else: ?>
        浏览 <?= $typeLabels[$type] ?? '全部' ?>
      <?php endif; ?>
    </div>
    <div class="search-subtitle">共找到 <?= count($results) ?> 条结果</div>
    <div class="search-filters">
      <?php foreach ($typeLabels as $t => $l): ?>
        <a href="?type=<?= $t ?><?= !empty($q) ? '&q=' . urlencode($q) : '' ?><?= !empty($_GET['sort']) ? '&sort=' . e($_GET['sort']) : '' ?>"
           class="filter-btn <?= ($type === $t) ? 'active' : '' ?>"><?= $l ?></a>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="media-grid" style="max-width:var(--container);margin:0 auto;padding:0 20px;">
    <?php if (!empty($results)): ?>
      <?php foreach ($results as $item): ?>
        <?php
          $mType = $item['media_type'] ?? (($displayType === 'tv' || $displayType === 'anime' || $displayType === 'variety') ? 'tv' : 'movie');
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
                $fav = $db->fetchOne('SELECT id FROM favorites WHERE user_id = ? AND media_type = ? AND media_id = ?', [$user['id'], $mType, $id]);
                $favorited = !!$fav;
              }
            } catch (Exception $e) {
              $favorited = false;
            }
          }
          $link = '/detail.php?type=' . $mType . '&id=' . $id;
        ?>
        <a href="<?= $link ?>" class="media-card">
          <div class="media-poster">
            <?php if ($poster): ?>
              <img src="<?= e($poster) ?>" alt="<?= e($title) ?>" loading="lazy">
            <?php else: ?>
              <div class="media-poster-placeholder">
                <i class="icon icon-<?= $mType === 'tv' ? 'tv' : 'film' ?>"></i>
              </div>
            <?php endif; ?>
            <?php if ($rating > 0): ?>
              <div class="media-rating">
                <i class="icon icon-star"></i><span><?= $rating ?></span>
              </div>
            <?php endif; ?>
            <?php if (!empty($item['media_type']) && $item['media_type'] === 'person'): ?>
              <div style="position:absolute;bottom:10px;left:10px;background:rgba(0,0,0,0.7);color:#fff;padding:3px 8px;border-radius:4px;font-size:11px;">人物</div>
            <?php endif; ?>
          </div>
          <div class="media-info">
            <div class="media-title"><?= e($title) ?></div>
            <div class="media-meta">
              <span><?= e($year) ?: '—' ?></span>
              <?php if (!empty($item['media_type'])): ?>
                <span><?= $item['media_type'] === 'movie' ? '电影' : ($item['media_type'] === 'tv' ? '剧集' : '') ?></span>
              <?php endif; ?>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    <?php else: ?>
      <div style="grid-column:1/-1;">
        <div class="empty-state" style="background:#fff;border-radius:var(--radius-lg);box-shadow:var(--shadow-sm);padding:60px 20px;">
          <div class="empty-icon"><i class="icon icon-search"></i></div>
          <h3 style="margin-bottom:8px;font-size:18px;">未找到相关内容</h3>
          <p style="color:var(--text-light);">换个关键词试试？</p>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <?php if ($totalPages > 1): ?>
    <div class="pagination" style="max-width:var(--container);margin:40px auto 0;padding:0 20px;">
      <?php $qs = $_GET; $qs['page'] = $page - 1; ?>
      <a href="?<?= http_build_query($qs) ?>" class="page-btn <?= ($page <= 1) ? 'disabled' : '' ?>">上一页</a>
      <?php
        $start = max(1, $page - 2);
        $end = min($totalPages, $start + 4);
        $start = max(1, $end - 4);
        for ($i = $start; $i <= $end; $i++):
          $qs['page'] = $i;
      ?>
        <a href="?<?= http_build_query($qs) ?>" class="page-btn <?= ($i === $page) ? 'active' : '' ?>"><?= $i ?></a>
      <?php endfor; ?>
      <?php $qs['page'] = $page + 1; ?>
      <a href="?<?= http_build_query($qs) ?>" class="page-btn <?= ($page >= $totalPages) ? 'disabled' : '' ?>">下一页</a>
    </div>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
