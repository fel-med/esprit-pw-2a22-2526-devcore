<?php
require_once __DIR__ . '/../layout/early-theme.php';
require_once '../../../Controleur/session_helper.php';
cc_start_session();
cc_require_admin('../../FrontOffice/utilisateur/login.php');
require_once '../../../Controleur/postC.php';

$postC = new PostC();
$pageTitle = 'All Posts';
$currentPage = 'posts';

$posts = $postC->listPosts();
$stats = $postC->getAdminStats();

$totalPosts    = (int)($stats['totalPosts']    ?? count($posts));
$totalViews    = (int)($stats['totalViews']    ?? 0);
$totalLikes    = (int)($stats['totalLikes']    ?? 0);
$totalDislikes = (int)($stats['totalDislikes'] ?? 0);
$approvalPct   = ($totalLikes + $totalDislikes) > 0
    ? round($totalLikes / ($totalLikes + $totalDislikes) * 100) : 0;
$avgViews = $totalPosts > 0 ? round($totalViews / $totalPosts) : 0;

$postSearch = trim((string)($_GET['q'] ?? ''));
$mediaFilter = trim((string)($_GET['media'] ?? ''));
$sortBy = trim((string)($_GET['sort'] ?? 'newest'));

$filteredPosts = array_values(array_filter($posts, function ($post) use ($postSearch, $mediaFilter) {
    if ($postSearch !== '') {
        $haystack = strtolower(
            (string)($post['subject'] ?? '') . ' ' .
            (string)($post['textContent'] ?? '') . ' ' .
            (string)($post['creatorName'] ?? '') . ' ' .
            (string)($post['id'] ?? '')
        );
        if (strpos($haystack, strtolower($postSearch)) === false) {
            return false;
        }
    }

    if ($mediaFilter !== '') {
        $hasImage = !empty($post['imageContent']);
        $hasVideo = !empty($post['VideoContent']);
        if ($mediaFilter === 'image' && !$hasImage) return false;
        if ($mediaFilter === 'video' && !$hasVideo) return false;
        if ($mediaFilter === 'text' && ($hasImage || $hasVideo)) return false;
    }

    return true;
}));

usort($filteredPosts, function ($a, $b) use ($sortBy) {
    if ($sortBy === 'views') {
        return (int)($b['numberOfView'] ?? 0) <=> (int)($a['numberOfView'] ?? 0);
    }
    if ($sortBy === 'likes') {
        return (int)($b['numberOfLike'] ?? 0) <=> (int)($a['numberOfLike'] ?? 0);
    }
    if ($sortBy === 'dislikes') {
        return (int)($b['numberOfDislike'] ?? 0) <=> (int)($a['numberOfDislike'] ?? 0);
    }
    return strtotime((string)($b['creationDate'] ?? '')) <=> strtotime((string)($a['creationDate'] ?? ''));
});

$perPageOptions = [5, 10, 25, 50];
$perPage = (int)($_GET['per_page'] ?? 10);
if (!in_array($perPage, $perPageOptions, true)) {
    $perPage = 10;
}
$totalFiltered = count($filteredPosts);
$totalPages = max(1, (int)ceil($totalFiltered / $perPage));
$page = max(1, min($totalPages, (int)($_GET['page'] ?? 1)));
$offset = ($page - 1) * $perPage;
$visiblePosts = array_slice($filteredPosts, $offset, $perPage);

function community_post_asset_version($path) {
    return is_file($path) ? '?v=' . urlencode((string) filemtime($path)) : '';
}

function community_page_items($current, $total) {
    if ($total <= 7) {
        return range(1, $total);
    }

    $pages = [1, $total, $current, $current - 1, $current + 1];
    $pages = array_values(array_unique(array_filter($pages, fn($p) => $p >= 1 && $p <= $total)));
    sort($pages);

    $result = [];
    $previous = null;
    foreach ($pages as $p) {
        if ($previous !== null && $p - $previous > 1) {
            $result[] = '…';
        }
        $result[] = $p;
        $previous = $p;
    }
    return $result;
}

$pageUrl = function ($targetPage) use ($perPage) {
    $params = $_GET;
    $params['page'] = $targetPage;
    $params['per_page'] = $perPage;
    return './index.php?' . http_build_query($params);
};

$postsJson = json_encode(array_map(fn($p) => [
    'id'       => $p['id'],
    'subject'  => $p['subject'],
    'creator'  => $p['creatorName'] ?? ('Creator #' . $p['idCreateur']),
    'views'    => (int)$p['numberOfView'],
    'likes'    => (int)$p['numberOfLike'],
    'dislikes' => (int)$p['numberOfDislike'],
    'hasImage' => !empty($p['imageContent']),
    'hasVideo' => !empty($p['VideoContent']),
    'image'    => $p['imageContent'] ?? '',
    'video'    => $p['VideoContent'] ?? '',
    'text'     => $p['textContent'] ?? '',
    'date'     => $p['creationDate'] ?? '',
], $posts), JSON_UNESCAPED_UNICODE);

if (!headers_sent()) header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php cre8_bo_early_theme_print_head_script(); ?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Posts Dashboard — Admin · Cre8Connect</title>
<link href="https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../css/backoffice.css<?= community_post_asset_version(__DIR__ . '/../css/backoffice.css') ?>">
<link rel="stylesheet" href="../layout/back-layout.css<?= community_post_asset_version(__DIR__ . '/../layout/back-layout.css') ?>">
<link rel="stylesheet" href="../utilisateur/assets/vendors/mdi/css/materialdesignicons.min.css<?= community_post_asset_version(__DIR__ . '/../utilisateur/assets/vendors/mdi/css/materialdesignicons.min.css') ?>">
<link rel="stylesheet" href="../css/new_style_backoffice.css<?= community_post_asset_version(__DIR__ . '/../css/new_style_backoffice.css') ?>">
<link rel="stylesheet" href="../community-center-admin.css<?= community_post_asset_version(__DIR__ . '/../community-center-admin.css') ?>">
<link rel="stylesheet" href="../unified-table-admin.css<?= community_post_asset_version(__DIR__ . '/../unified-table-admin.css') ?>">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<link rel="icon" type="image/png" sizes="16x16" href="../../public/images/favicon-16.png">
<link rel="icon" type="image/png" sizes="32x32" href="../../public/images/favicon-32.png">
<link rel="shortcut icon" type="image/png" href="../../public/images/favicon-32.png">
<link rel="apple-touch-icon" sizes="180x180" href="../../public/images/apple-touch-icon.png">
</head>
<body class="cre8-admin-layout"><?php cre8_bo_early_theme_print_body_script(); ?>

<div class="container-scroller cre8-admin-page">
<?php
$backActive = 'posts';
require_once __DIR__ . '/../layout/sidebar.php';
?>
<div class="container-fluid page-body-wrapper cre8-admin-main">
<?php require_once __DIR__ . '/../layout/header.php'; ?>
    <div class="main-panel">
        <div class="content-wrapper community-center-shell">
            <section class="cc-page-head">
                <div>
                    <p class="cc-kicker">Community Center</p>
                    <h1>Posts Dashboard</h1>
                    <p>Moderate creator posts, inspect engagement, and jump quickly to linked comments.</p>
                </div>
            </section>

            <nav class="cc-entity-tabs" aria-label="Community Center sections">
                <a class="cc-entity-tab is-active" href="../post/index.php" aria-current="page">
                    <span class="cc-tab-icon"><i class="mdi mdi-format-list-bulleted"></i></span>
                    <span><strong>Posts</strong><small>Content moderation and performance</small></span>
                </a>
                <a class="cc-entity-tab" href="../comment/index.php">
                    <span class="cc-tab-icon"><i class="mdi mdi-comment-multiple-outline"></i></span>
                    <span><strong>Comments</strong><small>Replies, reactions, and links</small></span>
                </a>
            </nav>

            <section class="cc-statistics-panel" data-cc-stats>
                <div class="cc-section-head">
                    <div>
                        <h2>Post indicators</h2>
                        <p>Global content activity and engagement health.</p>
                    </div>
                    <button type="button" class="cc-secondary-btn" data-cc-stats-toggle data-label-hide="Hide statistics" data-label-show="Show statistics">Hide statistics</button>
                </div>

                <div class="cc-kpi-grid">
                    <article class="cc-kpi-card cc-kpi-purple">
                        <span>Total posts</span>
                        <strong><?= number_format($totalPosts, 0, ',', ' ') ?></strong>
                        <small>Creator publications</small>
                    </article>
                    <article class="cc-kpi-card cc-kpi-pink">
                        <span>Total views</span>
                        <strong><?= number_format($totalViews, 0, ',', ' ') ?></strong>
                        <small>All-time visibility</small>
                    </article>
                    <article class="cc-kpi-card cc-kpi-green">
                        <span>Approval rate</span>
                        <strong><?= $approvalPct ?>%</strong>
                        <small>Likes vs reactions</small>
                    </article>
                    <article class="cc-kpi-card cc-kpi-blue">
                        <span>Avg. views</span>
                        <strong><?= number_format($avgViews, 0, ',', ' ') ?></strong>
                        <small>Views per post</small>
                    </article>
                </div>

                <div class="cc-stats-body cc-charts-grid">
                    <article class="cc-chart-card">
                        <div class="cc-chart-head"><h3>Engagement Breakdown</h3><p>Likes, dislikes, and neutral posts.</p></div>
                        <div class="cc-chart-legend" id="leg-engagement"></div>
                        <div class="cc-chart-canvas"><canvas id="chartEngagement"></canvas></div>
                    </article>
                    <article class="cc-chart-card">
                        <div class="cc-chart-head"><h3>Media Mix</h3><p>Image, video, and text-only content.</p></div>
                        <div class="cc-chart-legend" id="leg-media"></div>
                        <div class="cc-chart-canvas"><canvas id="chartMedia"></canvas></div>
                    </article>
                    <article class="cc-chart-card">
                        <div class="cc-chart-head"><h3>Creator Activity</h3><p>Top creators by published posts.</p></div>
                        <div class="cc-chart-canvas"><canvas id="chartCreators"></canvas></div>
                    </article>
                    <article class="cc-chart-card cc-chart-wide">
                        <div class="cc-chart-head"><h3>Top Posts by Views</h3><p>Most visible posts in the community.</p></div>
                        <div class="cc-chart-canvas"><canvas id="chartTopViews"></canvas></div>
                    </article>
                    <article class="cc-chart-card">
                        <div class="cc-chart-head"><h3>Engagement Share</h3><p>Likes per top creator.</p></div>
                        <div class="cc-chart-legend" id="leg-polar"></div>
                        <div class="cc-chart-canvas"><canvas id="chartPolar"></canvas></div>
                    </article>
                    <article class="cc-chart-card">
                        <div class="cc-chart-head"><h3>Performance Radar</h3><p>Top post views, likes, and dislikes.</p></div>
                        <div class="cc-chart-canvas"><canvas id="chartRadar"></canvas></div>
                    </article>
                </div>
            </section>

            <section class="cc-filter-card">
                <div class="cc-filter-head">
                    <div>
                        <h2>Filter posts</h2>
                        <p>Search by subject, creator, ID, or content.</p>
                    </div>
                </div>
                <form method="GET" class="cc-filter-grid">
                    <label class="cc-filter-field">
                        <span>Keyword</span>
                        <input type="text" name="q" value="<?= htmlspecialchars($postSearch) ?>" placeholder="Search posts, creators, or content">
                    </label>
                    <label class="cc-filter-field">
                        <span>Media</span>
                        <select name="media">
                            <option value="">All media</option>
                            <option value="image" <?= $mediaFilter === 'image' ? 'selected' : '' ?>>Images</option>
                            <option value="video" <?= $mediaFilter === 'video' ? 'selected' : '' ?>>Videos</option>
                            <option value="text" <?= $mediaFilter === 'text' ? 'selected' : '' ?>>Text only</option>
                        </select>
                    </label>
                    <label class="cc-filter-field">
                        <span>Sort</span>
                        <select name="sort">
                            <option value="newest" <?= $sortBy === 'newest' ? 'selected' : '' ?>>Newest first</option>
                            <option value="views" <?= $sortBy === 'views' ? 'selected' : '' ?>>Most viewed</option>
                            <option value="likes" <?= $sortBy === 'likes' ? 'selected' : '' ?>>Most liked</option>
                            <option value="dislikes" <?= $sortBy === 'dislikes' ? 'selected' : '' ?>>Most disliked</option>
                        </select>
                    </label>
                    <label class="cc-filter-field">
                        <span>Rows</span>
                        <select name="per_page">
                            <?php foreach ($perPageOptions as $option) : ?>
                                <option value="<?= $option ?>" <?= $perPage === $option ? 'selected' : '' ?>><?= $option ?> per page</option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <div class="cc-filter-actions">
                        <button type="submit" class="cc-primary-btn"><i class="mdi mdi-magnify"></i> Apply</button>
                        <a href="./index.php" class="cc-secondary-btn">Reset</a>
                    </div>
                </form>
            </section>

            <section class="cc-table-card">
                <div class="cc-table-head">
                    <div>
                        <h2>Posts moderation list</h2>
                        <p><?= number_format($totalFiltered, 0, ',', ' ') ?> post<?= $totalFiltered === 1 ? '' : 's' ?> found.</p>
                    </div>
                </div>

                <div id="ccResultsRegion" class="cc-results-region">
                    <?php if (empty($visiblePosts)) : ?>
                        <div class="cc-empty-state">
                            <span><i class="mdi mdi-format-list-bulleted"></i></span>
                            <strong>No posts found</strong>
                            <p>Try changing the filters or reset the search.</p>
                        </div>
                    <?php else : ?>
                        <div class="cc-table-wrap">
                            <table class="cc-table cc-posts-table" id="postsTable">
                                <thead>
                                    <tr>
                                        <th>Media</th>
                                        <th>Creator</th>
                                        <th>Subject</th>
                                        <th>Content</th>
                                        <th>Date</th>
                                        <th>Views</th>
                                        <th>Likes</th>
                                        <th>Dislikes</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="postsTableBody">
                                <?php foreach ($visiblePosts as $post) : ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($post['imageContent'])) : ?>
                                                <img src="../../public/<?= htmlspecialchars($post['imageContent']) ?>" alt="Post image" class="cc-media-thumb">
                                            <?php elseif (!empty($post['VideoContent'])) : ?>
                                                <video class="cc-media-thumb cc-video-thumb" muted><source src="../../public/<?= htmlspecialchars($post['VideoContent']) ?>"></video>
                                            <?php else : ?>
                                                <span class="cc-badge cc-badge-muted">No media</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="cc-person-cell">
                                                <strong><?= htmlspecialchars($post['creatorName'] ?? ('Creator #' . $post['idCreateur'])) ?></strong>
                                                <span>#<?= htmlspecialchars((string)$post['idCreateur']) ?></span>
                                            </div>
                                        </td>
                                        <td><strong><?= htmlspecialchars((string)$post['subject']) ?></strong></td>
                                        <td><div class="cc-excerpt"><?= htmlspecialchars((string)$post['textContent']) ?></div></td>
                                        <td><span class="cc-date-cell"><?= htmlspecialchars((string)$post['creationDate']) ?></span></td>
                                        <td><?= number_format((int)$post['numberOfView'], 0, ',', ' ') ?></td>
                                        <td><?= number_format((int)$post['numberOfLike'], 0, ',', ' ') ?></td>
                                        <td><?= number_format((int)$post['numberOfDislike'], 0, ',', ' ') ?></td>
                                        <td>
                                            <div class="cc-actions-stack">
                                                <a href="./details.php?id=<?= urlencode($post['id']) ?>" class="cc-action-btn cc-action-primary">
                                                    <i class="mdi mdi-eye-outline"></i> View
                                                </a>
                                                <a href="../comment/index.php?searchType=postId&keyword=<?= urlencode($post['id']) ?>" class="cc-action-btn cc-action-muted">
                                                    <i class="mdi mdi-comment-outline"></i> Comments
                                                </a>
                                                <a href="./delete.php?id=<?= urlencode($post['id']) ?>" class="cc-action-btn cc-action-danger js-admin-delete">
                                                    <i class="mdi mdi-delete-outline"></i> Delete
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="cc-pagination">
                            <p>
                                Showing <?= $totalFiltered === 0 ? 0 : number_format($offset + 1, 0, ',', ' ') ?>–<?= number_format(min($offset + $perPage, $totalFiltered), 0, ',', ' ') ?>
                                of <?= number_format($totalFiltered, 0, ',', ' ') ?> posts · Page <?= $page ?> of <?= $totalPages ?>
                            </p>
                            <nav aria-label="Posts pagination">
                                <a class="cc-page-btn <?= $page <= 1 ? 'is-disabled' : '' ?>" href="<?= $page <= 1 ? '#' : htmlspecialchars($pageUrl($page - 1)) ?>">‹</a>
                                <?php foreach (community_page_items($page, $totalPages) as $item) : ?>
                                    <?php if ($item === '…') : ?>
                                        <span class="cc-page-ellipsis">…</span>
                                    <?php else : ?>
                                        <a class="cc-page-btn <?= $item === $page ? 'is-active' : '' ?>" href="<?= htmlspecialchars($pageUrl((int)$item)) ?>"><?= $item ?></a>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <a class="cc-page-btn <?= $page >= $totalPages ? 'is-disabled' : '' ?>" href="<?= $page >= $totalPages ? '#' : htmlspecialchars($pageUrl($page + 1)) ?>">›</a>
                            </nav>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>
</div>
</div>

<script>
(function () {
    const P = ['#9b5de0', '#e11d74', '#0090e7', '#55d36a', '#f59e0b', '#d78fee'];
    const raw = <?= $postsJson ?>;

    function buildLegend(id, items) {
        const el = document.getElementById(id);
        if (!el) return;
        el.innerHTML = items.map(([label, color]) => `<span><i style="background:${color}"></i>${label}</span>`).join('');
    }

    function tickColor() {
        return document.documentElement.getAttribute('data-theme') === 'light' ? '#475569' : '#cbd5e1';
    }

    const totalLikes = raw.reduce((a, p) => a + p.likes, 0);
    const totalDislikes = raw.reduce((a, p) => a + p.dislikes, 0);
    const neutral = Math.max(0, raw.length - raw.filter(p => p.likes > 0 || p.dislikes > 0).length);
    buildLegend('leg-engagement', [[`Likes (${totalLikes.toLocaleString()})`, P[0]], [`Dislikes (${totalDislikes.toLocaleString()})`, P[1]], [`Neutral (${neutral.toLocaleString()})`, P[2]]]);
    new Chart(document.getElementById('chartEngagement'), {
        type: 'doughnut',
        data: { labels: ['Likes', 'Dislikes', 'Neutral'], datasets: [{ data: [totalLikes, totalDislikes, neutral], backgroundColor: [P[0], P[1], P[2]], borderWidth: 0 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });

    const withImage = raw.filter(p => p.hasImage).length;
    const withVideo = raw.filter(p => p.hasVideo).length;
    const textOnly = Math.max(0, raw.length - withImage - withVideo);
    buildLegend('leg-media', [[`Images (${withImage})`, P[0]], [`Videos (${withVideo})`, P[2]], [`Text (${textOnly})`, P[4]]]);
    new Chart(document.getElementById('chartMedia'), {
        type: 'pie',
        data: { labels: ['Images', 'Videos', 'Text only'], datasets: [{ data: [withImage, withVideo, textOnly], backgroundColor: [P[0], P[2], P[4]], borderWidth: 0 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });

    const creatorMap = {};
    raw.forEach(p => {
        if (!creatorMap[p.creator]) creatorMap[p.creator] = { posts: 0, likes: 0 };
        creatorMap[p.creator].posts++;
        creatorMap[p.creator].likes += p.likes;
    });
    const creators = Object.entries(creatorMap).sort((a, b) => b[1].posts - a[1].posts).slice(0, 6);
    new Chart(document.getElementById('chartCreators'), {
        type: 'bar',
        data: { labels: creators.map(c => c[0]), datasets: [{ label: 'Posts', data: creators.map(c => c[1].posts), backgroundColor: creators.map((_, i) => P[i % P.length]), borderRadius: 7 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { ticks: { color: tickColor() }, grid: { display: false } }, y: { ticks: { color: tickColor() }, grid: { color: 'rgba(148,163,184,.14)' } } } }
    });

    const topViews = [...raw].sort((a, b) => b.views - a.views).slice(0, 8);
    new Chart(document.getElementById('chartTopViews'), {
        type: 'bar',
        data: { labels: topViews.map(p => p.subject || `Post #${p.id}`), datasets: [{ label: 'Views', data: topViews.map(p => p.views), backgroundColor: P[2], borderRadius: 7 }] },
        options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { ticks: { color: tickColor() }, grid: { color: 'rgba(148,163,184,.14)' } }, y: { ticks: { color: tickColor() }, grid: { display: false } } } }
    });

    buildLegend('leg-polar', creators.map((c, i) => [`${c[0]} (${c[1].likes.toLocaleString()} likes)`, P[i % P.length]]));
    new Chart(document.getElementById('chartPolar'), {
        type: 'polarArea',
        data: { labels: creators.map(c => c[0]), datasets: [{ data: creators.map(c => c[1].likes), backgroundColor: creators.map((_, i) => P[i % P.length] + 'cc') }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { r: { ticks: { color: tickColor(), backdropColor: 'transparent' }, grid: { color: 'rgba(148,163,184,.14)' } } } }
    });

    const radarTop = [...raw].sort((a, b) => (b.views + b.likes) - (a.views + a.likes)).slice(0, 5);
    new Chart(document.getElementById('chartRadar'), {
        type: 'radar',
        data: { labels: radarTop.map(p => p.subject || `#${p.id}`), datasets: [
            { label: 'Views', data: radarTop.map(p => p.views), borderColor: P[2], backgroundColor: P[2] + '33' },
            { label: 'Likes', data: radarTop.map(p => p.likes), borderColor: P[0], backgroundColor: P[0] + '22' },
            { label: 'Dislikes', data: radarTop.map(p => p.dislikes), borderColor: P[1], backgroundColor: P[1] + '22' }
        ] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { color: tickColor() } } }, scales: { r: { ticks: { color: tickColor(), backdropColor: 'transparent' }, grid: { color: 'rgba(148,163,184,.14)' }, pointLabels: { color: tickColor() } } } }
    });
})();
</script>
<script src="../layout/back-layout.js<?= community_post_asset_version(__DIR__ . '/../layout/back-layout.js') ?>"></script>
<script src="../community-center-admin.js<?= community_post_asset_version(__DIR__ . '/../community-center-admin.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.js-admin-delete').forEach(function (button) {
        button.addEventListener('click', function (e) {
            if (!confirm('Are you sure you want to delete this post?')) {
                e.preventDefault();
            }
        });
    });
    const videos = document.querySelectorAll('video');
    videos.forEach(function (videoEl) {
        videoEl.addEventListener('play', function () {
            videos.forEach(function (other) {
                if (other !== videoEl) other.pause();
            });
        });
    });
});
</script>
</body>
</html>
