<?php
require_once __DIR__ . '/../layout/early-theme.php';
require_once '../../../Controleur/session_helper.php';
cc_start_session();
cc_require_admin('../../FrontOffice/utilisateur/login.php');
require_once '../../../Controleur/commentC.php';

$commentC = new CommentC();
$pageTitle = 'All Comments';
$currentPage = 'comments';

$searchType = trim($_GET['searchType'] ?? '');
$keyword    = trim($_GET['keyword']    ?? '');
$comments   = ($searchType !== '' || $keyword !== '')
    ? $commentC->searchCommentsAdmin($searchType, $keyword)
    : $commentC->listAllCommentsAdmin();

$stats = $commentC->getAdminStats();

$totalComments = (int)($stats['totalComments'] ?? 0);
$totalLikes    = (int)($stats['totalLikes']    ?? 0);
$totalDislikes = (int)($stats['totalDislikes'] ?? 0);
$approvalPct   = ($totalLikes + $totalDislikes) > 0
    ? round($totalLikes / ($totalLikes + $totalDislikes) * 100) : 0;

$perPageOptions = [5, 10, 25, 50];
$perPage = (int)($_GET['per_page'] ?? 10);
if (!in_array($perPage, $perPageOptions, true)) {
    $perPage = 10;
}
$totalFiltered = count($comments);
$totalPages = max(1, (int)ceil($totalFiltered / $perPage));
$page = max(1, min($totalPages, (int)($_GET['page'] ?? 1)));
$offset = ($page - 1) * $perPage;
$visibleComments = array_slice($comments, $offset, $perPage);

$commentsJson = json_encode(array_map(fn($c) => [
    'user'       => $c['userName'] ?? ('User #' . $c['idUser']),
    'type'       => $c['commentedItem'] ?? 'post',
    'likes'      => (int)($c['numberOfLike']    ?? 0),
    'dislikes'   => (int)($c['numberOfDislike'] ?? 0),
    'hasText'    => !empty(trim((string)($c['text']    ?? ''))),
    'hasSticker' => !empty($c['Sticker'] ?? $c['sticker'] ?? ''),
    'hasImage'   => !empty($c['image'] ?? ''),
    'text'       => mb_strimwidth((string)($c['text'] ?? ''), 0, 40, '…'),
], $comments), JSON_UNESCAPED_UNICODE);

function community_comment_asset_version($path) {
    return is_file($path) ? '?v=' . urlencode((string) filemtime($path)) : '';
}

function community_comment_page_items($current, $total) {
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

if (!headers_sent()) header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php cre8_bo_early_theme_print_head_script(); ?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Comments Dashboard — Admin · Cre8Connect</title>
<link href="https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../css/backoffice.css<?= community_comment_asset_version(__DIR__ . '/../css/backoffice.css') ?>">
<link rel="stylesheet" href="../layout/back-layout.css<?= community_comment_asset_version(__DIR__ . '/../layout/back-layout.css') ?>">
<link rel="stylesheet" href="../utilisateur/assets/vendors/mdi/css/materialdesignicons.min.css<?= community_comment_asset_version(__DIR__ . '/../utilisateur/assets/vendors/mdi/css/materialdesignicons.min.css') ?>">
<link rel="stylesheet" href="../css/new_style_backoffice.css<?= community_comment_asset_version(__DIR__ . '/../css/new_style_backoffice.css') ?>">
<link rel="stylesheet" href="../community-center-admin.css<?= community_comment_asset_version(__DIR__ . '/../community-center-admin.css') ?>">
<link rel="stylesheet" href="../unified-table-admin.css<?= community_comment_asset_version(__DIR__ . '/../unified-table-admin.css') ?>">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<link rel="icon" type="image/png" sizes="16x16" href="../../public/images/favicon-16.png">
<link rel="icon" type="image/png" sizes="32x32" href="../../public/images/favicon-32.png">
<link rel="shortcut icon" type="image/png" href="../../public/images/favicon-32.png">
<link rel="apple-touch-icon" sizes="180x180" href="../../public/images/apple-touch-icon.png">
</head>
<body class="cre8-admin-layout"><?php cre8_bo_early_theme_print_body_script(); ?>

<div class="container-scroller cre8-admin-page">
<?php
$backActive = 'comments';
require_once __DIR__ . '/../layout/sidebar.php';
?>
<div class="container-fluid page-body-wrapper cre8-admin-main">
<?php require_once __DIR__ . '/../layout/header.php'; ?>
    <div class="main-panel">
        <div class="content-wrapper community-center-shell">
            <section class="cc-page-head">
                <div>
                    <p class="cc-kicker">Community Center</p>
                    <h1>Comments Dashboard</h1>
                    <p>Search replies, inspect comment targets, and moderate conversation activity.</p>
                </div>
            </section>

            <nav class="cc-entity-tabs" aria-label="Community Center sections">
                <a class="cc-entity-tab" href="../post/index.php">
                    <span class="cc-tab-icon"><i class="mdi mdi-newspaper-variant-outline"></i></span>
                    <span><strong>Posts</strong><small>Content moderation and performance</small></span>
                </a>
                <a class="cc-entity-tab is-active" href="../comment/index.php" aria-current="page">
                    <span class="cc-tab-icon"><i class="mdi mdi-comment-multiple-outline"></i></span>
                    <span><strong>Comments</strong><small>Replies, reactions, and links</small></span>
                </a>
            </nav>

            <section class="cc-statistics-panel" data-cc-stats>
                <div class="cc-section-head">
                    <div>
                        <h2>Comment indicators</h2>
                        <p>Conversation volume, reactions, and format breakdown.</p>
                    </div>
                    <button type="button" class="cc-secondary-btn" data-cc-stats-toggle data-label-hide="Hide statistics" data-label-show="Show statistics">Hide statistics</button>
                </div>

                <div class="cc-kpi-grid">
                    <article class="cc-kpi-card cc-kpi-purple">
                        <span>Total comments</span>
                        <strong><?= number_format($totalComments, 0, ',', ' ') ?></strong>
                        <small>Comments and replies</small>
                    </article>
                    <article class="cc-kpi-card cc-kpi-pink">
                        <span>Total likes</span>
                        <strong><?= number_format($totalLikes, 0, ',', ' ') ?></strong>
                        <small>Positive reactions</small>
                    </article>
                    <article class="cc-kpi-card cc-kpi-yellow">
                        <span>Total dislikes</span>
                        <strong><?= number_format($totalDislikes, 0, ',', ' ') ?></strong>
                        <small>Negative reactions</small>
                    </article>
                    <article class="cc-kpi-card cc-kpi-green">
                        <span>Approval rate</span>
                        <strong><?= $approvalPct ?>%</strong>
                        <small>Likes vs reactions</small>
                    </article>
                </div>

                <div class="cc-stats-body cc-charts-grid">
                    <article class="cc-chart-card">
                        <div class="cc-chart-head"><h3>Engagement Breakdown</h3><p>Likes, dislikes, and neutral comments.</p></div>
                        <div class="cc-chart-legend" id="leg-engagement"></div>
                        <div class="cc-chart-canvas"><canvas id="chartEngagement"></canvas></div>
                    </article>
                    <article class="cc-chart-card">
                        <div class="cc-chart-head"><h3>Comment Target</h3><p>Comments on posts vs replies.</p></div>
                        <div class="cc-chart-legend" id="leg-target"></div>
                        <div class="cc-chart-canvas"><canvas id="chartTarget"></canvas></div>
                    </article>
                    <article class="cc-chart-card">
                        <div class="cc-chart-head"><h3>Comment Format</h3><p>Text, stickers, and images.</p></div>
                        <div class="cc-chart-legend" id="leg-format"></div>
                        <div class="cc-chart-canvas"><canvas id="chartFormat"></canvas></div>
                    </article>
                    <article class="cc-chart-card">
                        <div class="cc-chart-head"><h3>User Activity</h3><p>Top users by comment volume.</p></div>
                        <div class="cc-chart-canvas"><canvas id="chartUsers"></canvas></div>
                    </article>
                    <article class="cc-chart-card cc-chart-wide">
                        <div class="cc-chart-head"><h3>Top Comments by Likes</h3><p>Most liked comments and dislikes.</p></div>
                        <div class="cc-chart-canvas"><canvas id="chartTopComments"></canvas></div>
                    </article>
                    <article class="cc-chart-card">
                        <div class="cc-chart-head"><h3>Cumulative Likes by User</h3><p>Popularity distribution.</p></div>
                        <div class="cc-chart-legend" id="leg-polar"></div>
                        <div class="cc-chart-canvas"><canvas id="chartPolar"></canvas></div>
                    </article>
                </div>
            </section>

            <section class="cc-filter-card">
                <div class="cc-filter-head">
                    <div>
                        <h2>Filter comments</h2>
                        <p>Search by post ID, comment ID, creator name, or creator ID.</p>
                    </div>
                </div>
                <form method="GET" class="cc-filter-grid">
                    <label class="cc-filter-field">
                        <span>Search by</span>
                        <select name="searchType">
                            <option value="">All comments</option>
                            <option value="postId" <?= $searchType === 'postId' ? 'selected' : '' ?>>Post ID</option>
                            <option value="commentId" <?= $searchType === 'commentId' ? 'selected' : '' ?>>Comment ID (show replies)</option>
                            <option value="creator" <?= $searchType === 'creator' ? 'selected' : '' ?>>Creator</option>
                        </select>
                    </label>
                    <label class="cc-filter-field cc-filter-wide">
                        <span>Keyword</span>
                        <input type="text" name="keyword" value="<?= htmlspecialchars($keyword) ?>" placeholder="Enter post id, comment id, creator name, or creator id">
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
                        <button type="submit" class="cc-primary-btn"><i class="mdi mdi-magnify"></i> Search</button>
                        <a href="./index.php" class="cc-secondary-btn">Reset</a>
                    </div>
                </form>
            </section>

            <section class="cc-table-card">
                <div class="cc-table-head">
                    <div>
                        <h2>Comments moderation list</h2>
                        <p><?= number_format($totalFiltered, 0, ',', ' ') ?> comment<?= $totalFiltered === 1 ? '' : 's' ?> found.</p>
                    </div>
                </div>

                <div id="ccResultsRegion" class="cc-results-region">
                    <?php if ($searchType === 'postId' && $keyword !== '') : ?>
                        <div class="cc-alert">
                            Showing comments linked to post <strong>#<?= htmlspecialchars($keyword) ?></strong>.
                        </div>
                    <?php endif; ?>

                    <?php if (empty($visibleComments)) : ?>
                        <div class="cc-empty-state">
                            <span><i class="mdi mdi-comment-alert-outline"></i></span>
                            <strong>No comments found</strong>
                            <p>Try another search or reset the filters.</p>
                        </div>
                    <?php else : ?>
                        <div class="cc-table-wrap">
                            <table class="cc-table cc-comments-table" id="commentsTable">
                                <thead>
                                    <tr>
                                        <th>Comment ID</th>
                                        <th>Target</th>
                                        <th>Creator</th>
                                        <th>Text</th>
                                        <th>Sticker</th>
                                        <th>Image</th>
                                        <th>Likes</th>
                                        <th>Dislikes</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="commentsTableBody">
                                <?php foreach ($visibleComments as $comment) : ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string)$comment['id']) ?></td>
                                        <td>
                                            <div class="cc-person-cell">
                                                <strong><?= htmlspecialchars((string)$comment['commentedItem']) ?> #<?= htmlspecialchars((string)$comment['idCommentedElement']) ?></strong>
                                                <span><?= ($comment['commentedItem'] ?? '') === 'post' ? 'Post discussion' : 'Reply thread' ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="cc-person-cell">
                                                <strong><?= htmlspecialchars($comment['userName'] ?? ('User #' . $comment['idUser'])) ?></strong>
                                                <span>#<?= htmlspecialchars((string)$comment['idUser']) ?></span>
                                            </div>
                                        </td>
                                        <td><div class="cc-excerpt"><?= htmlspecialchars(mb_strimwidth((string)($comment['text'] ?? ''), 0, 120, '...')) ?></div></td>
                                        <td><?= !empty($comment['Sticker'] ?? $comment['sticker'] ?? '') ? htmlspecialchars($comment['Sticker'] ?? $comment['sticker']) : '<span class="cc-badge cc-badge-muted">None</span>' ?></td>
                                        <td>
                                            <?php if (!empty($comment['image'])) : ?>
                                                <img src="../../public/<?= htmlspecialchars($comment['image']) ?>" alt="comment image" class="cc-media-thumb">
                                            <?php else : ?>
                                                <span class="cc-badge cc-badge-muted">No image</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= number_format((int)($comment['numberOfLike'] ?? 0), 0, ',', ' ') ?></td>
                                        <td><?= number_format((int)($comment['numberOfDislike'] ?? 0), 0, ',', ' ') ?></td>
                                        <td>
                                            <div class="cc-actions-stack">
                                                <?php if (($comment['commentedItem'] ?? '') === 'post') : ?>
                                                    <a href="../post/details.php?id=<?= urlencode($comment['idCommentedElement']) ?>" class="cc-action-btn cc-action-primary">
                                                        <i class="mdi mdi-eye-outline"></i> Post
                                                    </a>
                                                <?php endif; ?>
                                                <a href="./index.php?searchType=commentId&keyword=<?= urlencode($comment['id']) ?>" class="cc-action-btn cc-action-muted">
                                                    <i class="mdi mdi-source-branch"></i> Replies
                                                </a>
                                                <a href="./delete.php?id=<?= urlencode($comment['id']) ?>&searchType=<?= urlencode($searchType) ?>&keyword=<?= urlencode($keyword) ?>" class="cc-action-btn cc-action-danger js-admin-delete">
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
                                of <?= number_format($totalFiltered, 0, ',', ' ') ?> comments · Page <?= $page ?> of <?= $totalPages ?>
                            </p>
                            <nav aria-label="Comments pagination">
                                <a class="cc-page-btn <?= $page <= 1 ? 'is-disabled' : '' ?>" href="<?= $page <= 1 ? '#' : htmlspecialchars($pageUrl($page - 1)) ?>">‹</a>
                                <?php foreach (community_comment_page_items($page, $totalPages) as $item) : ?>
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
    const raw = <?= $commentsJson ?>;

    function buildLegend(id, items) {
        const el = document.getElementById(id);
        if (!el) return;
        el.innerHTML = items.map(([label, color]) => `<span><i style="background:${color}"></i>${label}</span>`).join('');
    }

    function tickColor() {
        return document.documentElement.getAttribute('data-theme') === 'light' ? '#475569' : '#cbd5e1';
    }

    const totalLikes = raw.reduce((a, c) => a + c.likes, 0);
    const totalDislikes = raw.reduce((a, c) => a + c.dislikes, 0);
    const neutrals = Math.max(0, raw.length - raw.filter(c => c.likes > 0 || c.dislikes > 0).length);

    buildLegend('leg-engagement', [[`Likes (${totalLikes.toLocaleString()})`, P[0]], [`Dislikes (${totalDislikes.toLocaleString()})`, P[3]], [`Neutral (${neutrals.toLocaleString()})`, P[5]]]);
    new Chart(document.getElementById('chartEngagement'), {
        type: 'doughnut',
        data: { labels: ['Likes', 'Dislikes', 'Neutral'], datasets: [{ data: [totalLikes, totalDislikes, neutrals], backgroundColor: [P[0], P[3], P[5]], borderWidth: 0 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });

    const onPost = raw.filter(c => c.type === 'post').length;
    const onComment = raw.filter(c => c.type === 'comment').length;
    buildLegend('leg-target', [[`On post (${onPost})`, P[0]], [`Reply (${onComment})`, P[2]]]);
    new Chart(document.getElementById('chartTarget'), {
        type: 'pie',
        data: { labels: ['On post', 'Reply to a comment'], datasets: [{ data: [onPost, onComment], backgroundColor: [P[0], P[2]], borderWidth: 0 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });

    const withText = raw.filter(c => c.hasText).length;
    const withSticker = raw.filter(c => c.hasSticker && !c.hasText).length;
    const withImage = raw.filter(c => c.hasImage && !c.hasText && !c.hasSticker).length;
    buildLegend('leg-format', [[`Text (${withText})`, P[0]], [`Sticker (${withSticker})`, P[4]], [`Image (${withImage})`, P[2]]]);
    new Chart(document.getElementById('chartFormat'), {
        type: 'pie',
        data: { labels: ['Text', 'Sticker', 'Image'], datasets: [{ data: [withText, withSticker, withImage], backgroundColor: [P[0], P[4], P[2]], borderWidth: 0 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });

    const usersMap = {};
    raw.forEach(c => {
        if (!usersMap[c.user]) usersMap[c.user] = { comments: 0, likes: 0 };
        usersMap[c.user].comments++;
        usersMap[c.user].likes += c.likes;
    });
    const users = Object.entries(usersMap).sort((a, b) => b[1].comments - a[1].comments).slice(0, 6);

    new Chart(document.getElementById('chartUsers'), {
        type: 'bar',
        data: { labels: users.map(u => u[0]), datasets: [{ label: 'Comments', data: users.map(u => u[1].comments), backgroundColor: users.map((_, i) => P[i % P.length]), borderRadius: 7 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { ticks: { color: tickColor() }, grid: { display: false } }, y: { ticks: { color: tickColor() }, grid: { color: 'rgba(148,163,184,.14)' } } } }
    });

    const top8 = [...raw].sort((a, b) => b.likes - a.likes).slice(0, 8);
    new Chart(document.getElementById('chartTopComments'), {
        type: 'bar',
        data: { labels: top8.map(c => c.text || '(empty)'), datasets: [
            { label: 'Likes', data: top8.map(c => c.likes), backgroundColor: P[0], borderRadius: 5 },
            { label: 'Dislikes', data: top8.map(c => c.dislikes), backgroundColor: P[3], borderRadius: 5 }
        ] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { color: tickColor() } } }, scales: { x: { ticks: { color: tickColor(), maxRotation: 45 }, grid: { display: false } }, y: { ticks: { color: tickColor() }, grid: { color: 'rgba(148,163,184,.14)' } } } }
    });

    buildLegend('leg-polar', users.map((u, i) => [`${u[0]} (${u[1].likes.toLocaleString()} likes)`, P[i % P.length]]));
    new Chart(document.getElementById('chartPolar'), {
        type: 'polarArea',
        data: { labels: users.map(u => u[0]), datasets: [{ data: users.map(u => u[1].likes), backgroundColor: users.map((_, i) => P[i % P.length] + 'cc') }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { r: { ticks: { color: tickColor(), backdropColor: 'transparent' }, grid: { color: 'rgba(148,163,184,.14)' } } } }
    });
})();
</script>
<script src="../layout/back-layout.js<?= community_comment_asset_version(__DIR__ . '/../layout/back-layout.js') ?>"></script>
<script src="../community-center-admin.js<?= community_comment_asset_version(__DIR__ . '/../community-center-admin.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.js-admin-delete').forEach(function (button) {
        button.addEventListener('click', function (e) {
            if (!confirm('Are you sure you want to delete this comment?')) {
                e.preventDefault();
            }
        });
    });
});
</script>
</body>
</html>
