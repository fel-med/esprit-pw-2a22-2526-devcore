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

/* ── Global stats ── */
$totalComments = (int)($stats['totalComments'] ?? 0);
$totalLikes    = (int)($stats['totalLikes']    ?? 0);
$totalDislikes = (int)($stats['totalDislikes'] ?? 0);
$approvalPct   = ($totalLikes + $totalDislikes) > 0
    ? round($totalLikes / ($totalLikes + $totalDislikes) * 100) : 0;

/* ── JSON for JS ── */
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

function commentAssetVersion($path) {
    return is_file($path) ? '?v=' . urlencode((string) filemtime($path)) : '';
}
if (!headers_sent()) header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Comments Dashboard — Admin · Cre8Connect</title>
<link href="https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../css/backoffice.css<?= commentAssetVersion(__DIR__ . '/../css/backoffice.css') ?>">
<link rel="stylesheet" href="../layout/back-layout.css<?= commentAssetVersion(__DIR__ . '/../layout/back-layout.css') ?>">
<link rel="stylesheet" href="../utilisateur/assets/vendors/mdi/css/materialdesignicons.min.css<?= commentAssetVersion(__DIR__ . '/../utilisateur/assets/vendors/mdi/css/materialdesignicons.min.css') ?>">
<link rel="stylesheet" href="../css/new_style_backoffice.css<?= commentAssetVersion(__DIR__ . '/../css/new_style_backoffice.css') ?>">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<style>
.comment-admin {
    --comment-bg-card: #16213e;
    --comment-bg-card-2: #0f3460;
    --comment-border: rgba(139, 92, 246, 0.22);
    --comment-border-light: rgba(255, 255, 255, 0.08);
    --comment-text: #e2e8f0;
    --comment-muted: #94a3b8;
    --comment-label: #a78bfa;
    --comment-primary: #8b5cf6;
    --comment-accent: #ec4899;
    --comment-accent-light: #f9a8d4;
    --comment-shadow: 0 4px 24px rgba(139, 92, 246, 0.12);
    color: var(--comment-text);
}
body.light-mode .comment-admin {
    --comment-bg-card: #ffffff;
    --comment-bg-card-2: #f8f5ff;
    --comment-border: rgba(139, 92, 246, 0.18);
    --comment-border-light: rgba(139, 92, 246, 0.10);
    --comment-text: #1e1b4b;
    --comment-muted: #6b7280;
    --comment-label: #7c3aed;
    --comment-shadow: 0 2px 16px rgba(139, 92, 246, 0.10);
}
.comment-admin .card {
    background: var(--comment-bg-card) !important;
    border: 1px solid var(--comment-border) !important;
    border-radius: 16px !important;
    box-shadow: var(--comment-shadow);
    color: var(--comment-text) !important;
}
.comment-admin .text-muted { color: var(--comment-muted) !important; }
.comment-admin .stat-card-mini .card-body { min-height: 90px; position:relative; overflow:hidden; }
.comment-admin .stat-card-mini .card-body::after { content:""; position:absolute; right:-20px; bottom:-20px; width:80px; height:80px; border-radius:50%; background:rgba(255,255,255,.12); }
.comment-admin .stat-card-mini h6 { font-size:11px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; opacity:.65; margin-bottom:8px; }
.comment-admin .stat-card-mini h2 { font-size:30px; font-weight:800; margin:0; }
.comment-admin .stat-card-custom1 { border-top:4px solid #8b5cf6 !important; }
.comment-admin .stat-card-custom2 { border-top:4px solid #ec4899 !important; }
.comment-admin .stat-card-custom3 { border-top:4px solid #98D882 !important; }
.comment-admin .stat-card-custom4 { border-top:4px solid #a855f7 !important; }
.comment-admin .chart-card .card-body { padding:1.4rem; }
.comment-admin .chart-title { font-size:14px; font-weight:700; margin-bottom:2px; color:var(--comment-text); }
.comment-admin .chart-sub { font-size:12px; color:var(--comment-muted); margin-bottom:14px; }
.comment-admin .chart-legend { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:14px; font-size:12px; font-weight:600; color:var(--comment-text); }
.comment-admin .chart-legend span { display:flex; align-items:center; gap:6px; }
.comment-admin .chart-legend i { width:11px; height:11px; border-radius:3px; display:inline-block; flex-shrink:0; }
.comment-admin .table-dark,
.comment-admin .admin-table { background-color:var(--comment-bg-card) !important; color:var(--comment-text) !important; border-color:var(--comment-border) !important; }
.comment-admin .table-dark thead th,
.comment-admin .admin-table thead th { background:var(--comment-bg-card-2) !important; color:var(--comment-label) !important; border-color:var(--comment-border) !important; font-weight:700; font-size:11px; letter-spacing:.06em; text-transform:uppercase; }
.comment-admin .table-dark td,
.comment-admin .table-dark th,
.comment-admin .admin-table td,
.comment-admin .admin-table th { border-color:var(--comment-border-light) !important; color:var(--comment-text) !important; vertical-align:middle; }
.comment-admin .form-control,
.comment-admin select.form-control { background:var(--comment-bg-card-2) !important; border:1px solid var(--comment-border) !important; border-radius:10px !important; color:var(--comment-text) !important; }
.comment-admin .form-control:focus { border-color:var(--comment-primary) !important; box-shadow:0 0 0 3px rgba(139,92,246,.15) !important; }
.comment-admin .form-label,
.comment-admin label { color:var(--comment-muted) !important; font-weight:700; font-size:12px; letter-spacing:.04em; text-transform:uppercase; }
.comment-admin .btn { border-radius:999px; font-weight:700; }
.comment-admin .btn-info { background:linear-gradient(135deg,#0ea5e9,#0284c7) !important; border-color:transparent !important; color:#fff !important; }
.comment-admin .btn-outline-light { border-color:var(--comment-border) !important; color:var(--comment-muted) !important; }
.comment-admin .admin-action-btn { display:inline-flex; align-items:center; gap:5px; border-radius:999px; padding:.45rem .9rem; font-weight:700; font-size:12px; text-decoration:none; margin-right:5px; margin-bottom:5px; transition:all .18s ease; }
.comment-admin .admin-action-btn:hover { transform:translateY(-2px); text-decoration:none; }
.comment-admin .admin-view-btn { background:rgba(139,92,246,.15); color:var(--comment-label) !important; border:1.5px solid rgba(139,92,246,.35); }
.comment-admin .admin-delete-btn { background:rgba(236,72,153,.12); color:var(--comment-accent-light) !important; border:1.5px solid rgba(236,72,153,.28); }
.comment-admin .badge-secondary { background:rgba(139,92,246,.18) !important; color:var(--comment-label) !important; border:1px solid var(--comment-border); }
.comment-admin .empty-state-admin { padding:60px 20px; text-align:center; border:2px dashed var(--comment-border); border-radius:20px; background:rgba(139,92,246,.03); }
#commentPaginationControls { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-top:18px; padding-top:14px; border-top:1px solid var(--comment-border); }
#commentPaginationControls .pag-info { font-size:13px; color:var(--comment-muted); }
.comment-admin .pag-buttons { display:flex; align-items:center; gap:4px; }
.comment-admin .pag-btn { display:inline-flex; align-items:center; justify-content:center; min-width:34px; height:34px; padding:0 8px; border-radius:8px; border:1.5px solid var(--comment-border); background:transparent; color:var(--comment-text); font-size:13px; font-weight:600; cursor:pointer; transition:background .15s,border-color .15s,color .15s; }
.comment-admin .pag-btn:hover:not(:disabled) { background:rgba(139,92,246,.12); }
.comment-admin .pag-btn.active { background:#e91e8c; border-color:#e91e8c; color:#fff; }
.comment-admin .pag-btn:disabled { opacity:.3; cursor:default; }
.comment-admin .pag-select { display:inline-flex; align-items:center; gap:6px; font-size:13px; color:var(--comment-muted); }
.comment-admin .pag-select select { background:var(--comment-bg-card-2); border:1.5px solid var(--comment-border); border-radius:7px; color:var(--comment-text); padding:4px 8px; font-size:13px; cursor:pointer; outline:none; }
.comment-admin .post-creator-badge { color:var(--comment-label); font-weight:700; }
</style>
<link rel="icon" type="image/png" sizes="32x32" href="../../public/images/logo.png">
<link rel="shortcut icon" type="image/png" href="../../public/images/logo.png">
<link rel="apple-touch-icon" href="../../public/images/logo.png">
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
    <div class="content-wrapper">
        <div class="comment-admin">

<!-- ══ Header ════════════════════════════════════════════════════ -->
<div class="row">
    <div class="col-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h3 class="mb-1">Comments Dashboard</h3>
                    <p class="text-muted mb-0">Search comments by post ID, comment ID to inspect replies, or by creator.</p>
                </div>
                <div class="d-flex gap-2 flex-wrap align-items-center">
                    <a href="../post/index.php" class="btn btn-outline-light">
                        <i class="mdi mdi-arrow-left"></i> Back to Posts
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══ Metric cards ══════════════════════════════════════════════ -->
<div class="row">
    <div class="col-6 col-md-3 grid-margin stretch-card">
        <div class="card stat-card-mini stat-card-custom1"><div class="card-body"><h6>Total Comments</h6><h2><?= $totalComments ?></h2></div></div>
    </div>
    <div class="col-6 col-md-3 grid-margin stretch-card">
        <div class="card stat-card-mini stat-card-custom2"><div class="card-body"><h6>Total Likes</h6><h2><?= number_format($totalLikes, 0, ',', ' ') ?></h2></div></div>
    </div>
    <div class="col-6 col-md-3 grid-margin stretch-card">
        <div class="card stat-card-mini stat-card-custom3"><div class="card-body"><h6>Total Dislikes</h6><h2><?= number_format($totalDislikes, 0, ',', ' ') ?></h2></div></div>
    </div>
    <div class="col-6 col-md-3 grid-margin stretch-card">
        <div class="card stat-card-mini stat-card-custom4"><div class="card-body"><h6>Approval Rate</h6><h2><?= $approvalPct ?>%</h2></div></div>
    </div>
</div>

<!-- ══ Row 1 : Engagement pie + Target pie ═══════════════════════ -->
<div class="row">
    <div class="col-md-6 grid-margin stretch-card">
        <div class="card chart-card"><div class="card-body"><p class="chart-title">Engagement Breakdown</p><p class="chart-sub">Likes · Dislikes · No reaction</p><div class="chart-legend" id="leg-engagement"></div><div style="position:relative;height:240px;"><canvas id="chartEngagement"></canvas></div></div></div>
    </div>
    <div class="col-md-6 grid-margin stretch-card">
        <div class="card chart-card"><div class="card-body"><p class="chart-title">Comment Target</p><p class="chart-sub">Comments on a post vs replies to a comment</p><div class="chart-legend" id="leg-target"></div><div style="position:relative;height:240px;"><canvas id="chartTarget"></canvas></div></div></div>
    </div>
</div>

<!-- ══ Row 2 : Format pie + User activity bar ════════════════════ -->
<div class="row">
    <div class="col-md-4 grid-margin stretch-card">
        <div class="card chart-card"><div class="card-body"><p class="chart-title">Comment Format</p><p class="chart-sub">Text · Sticker · Image</p><div class="chart-legend" id="leg-format"></div><div style="position:relative;height:240px;"><canvas id="chartFormat"></canvas></div></div></div>
    </div>
    <div class="col-md-8 grid-margin stretch-card">
        <div class="card chart-card"><div class="card-body"><p class="chart-title">User Activity</p><p class="chart-sub">Top 6 — number of comments published</p><div style="position:relative;height:240px;"><canvas id="chartUsers"></canvas></div></div></div>
    </div>
</div>

<!-- ══ Row 3 : Top comments grouped bar + PolarArea ══════════════ -->
<div class="row">
    <div class="col-md-7 grid-margin stretch-card">
        <div class="card chart-card"><div class="card-body"><p class="chart-title">Top Comments by Likes</p><p class="chart-sub">Top 8 — likes and dislikes</p><div class="chart-legend"><span><i style="background:#e91e8c"></i>Likes</span><span><i style="background:#7c3aed"></i>Dislikes</span></div><div style="position:relative;height:260px;"><canvas id="chartTopComments"></canvas></div></div></div>
    </div>
    <div class="col-md-5 grid-margin stretch-card">
        <div class="card chart-card"><div class="card-body"><p class="chart-title">Cumulative Likes by User</p><p class="chart-sub">PolarArea — comment popularity</p><div class="chart-legend" id="leg-polar"></div><div style="position:relative;height:260px;"><canvas id="chartPolar"></canvas></div></div></div>
    </div>
</div>

<script>
(function () {

    const P = ['#e91e8c', '#c026d3', '#9c27b0', '#7c3aed', '#f472b6', '#6a008a'];
    const raw = <?= $commentsJson ?>;

    const totalLikes    = raw.reduce((a, c) => a + c.likes,    0);
    const totalDislikes = raw.reduce((a, c) => a + c.dislikes,  0);
    const totalAll      = raw.length;
    const neutrals      = Math.max(0, totalAll - raw.filter(c => c.likes > 0 || c.dislikes > 0).length);

    function buildLegend(id, items) {
        const el = document.getElementById(id);
        if (!el) return;
        el.innerHTML = items.map(([label, color]) => `<span><i style="background:${color}"></i>${label}</span>`).join('');
    }
    function gridColor() { return document.body.classList.contains('light-mode') ? 'rgba(156,39,176,.12)' : 'rgba(233,30,140,.10)'; }
    function tickColor() { return document.body.classList.contains('light-mode') ? '#9c27b0' : '#c084fc'; }

    buildLegend('leg-engagement', [
        [`Likes (${totalLikes.toLocaleString('en-US')})`, P[0]],
        [`Dislikes (${totalDislikes.toLocaleString('en-US')})`, P[3]],
        [`Neutral (${neutrals.toLocaleString('en-US')})`, P[5]],
    ]);
    new Chart(document.getElementById('chartEngagement'), {
        type: 'pie',
        data: { labels: ['Likes', 'Dislikes', 'Neutral'], datasets: [{ data: [totalLikes, totalDislikes, neutrals], backgroundColor: [P[0], P[3], P[5]], borderWidth: 3, borderColor: 'transparent', hoverOffset: 10 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });

    const onPost = raw.filter(c => c.type === 'post').length;
    const onComment = raw.filter(c => c.type === 'comment').length;
    buildLegend('leg-target', [[`On post (${onPost})`, P[0]], [`Reply (${onComment})`, P[2]]]);
    new Chart(document.getElementById('chartTarget'), {
        type: 'pie',
        data: { labels: ['On post', 'Reply to a comment'], datasets: [{ data: [onPost, onComment], backgroundColor: [P[0], P[2]], borderWidth: 3, borderColor: 'transparent', hoverOffset: 10 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });

    const withText = raw.filter(c => c.hasText).length;
    const withSticker = raw.filter(c => c.hasSticker && !c.hasText).length;
    const withImage = raw.filter(c => c.hasImage && !c.hasText && !c.hasSticker).length;
    buildLegend('leg-format', [[`Text (${withText})`, P[0]], [`Sticker (${withSticker})`, P[4]], [`Image (${withImage})`, P[2]]]);
    new Chart(document.getElementById('chartFormat'), {
        type: 'pie',
        data: { labels: ['Text', 'Sticker', 'Image'], datasets: [{ data: [withText, withSticker, withImage], backgroundColor: [P[0], P[4], P[2]], borderWidth: 3, borderColor: 'transparent', hoverOffset: 10 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });

    const umap = {};
    raw.forEach(c => {
        if (!umap[c.user]) umap[c.user] = { comments: 0, likes: 0 };
        umap[c.user].comments++;
        umap[c.user].likes += c.likes;
    });
    const users = Object.entries(umap).sort((a,b) => b[1].comments - a[1].comments).slice(0, 6);
    new Chart(document.getElementById('chartUsers'), {
        type: 'bar',
        data: { labels: users.map(u => u[0]), datasets: [{ label: 'Comments', data: users.map(u => u[1].comments), backgroundColor: users.map((_, i) => P[i % P.length]), borderRadius: 7, borderSkipped: false }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { ticks: { color: tickColor(), font: { size: 12 } }, grid: { display: false } }, y: { ticks: { color: tickColor(), stepSize: 1 }, grid: { color: gridColor() }, beginAtZero: true } } }
    });

    const top8 = [...raw].sort((a,b) => b.likes - a.likes).slice(0, 8);
    new Chart(document.getElementById('chartTopComments'), {
        type: 'bar',
        data: { labels: top8.map(c => c.text || '(empty)'), datasets: [ { label: 'Likes', data: top8.map(c => c.likes), backgroundColor: P[0], borderRadius: 5, borderSkipped: false }, { label: 'Dislikes', data: top8.map(c => c.dislikes), backgroundColor: P[3], borderRadius: 5, borderSkipped: false } ] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { ticks: { color: tickColor(), font: { size: 11 }, autoSkip: false, maxRotation: 35 }, grid: { display: false } }, y: { ticks: { color: tickColor() }, grid: { color: gridColor() }, beginAtZero: true } } }
    });

    buildLegend('leg-polar', users.map((u, i) => [`${u[0]} (${u[1].likes.toLocaleString('en-US')} likes)`, P[i % P.length]]));
    new Chart(document.getElementById('chartPolar'), {
        type: 'polarArea',
        data: { labels: users.map(u => u[0]), datasets: [{ data: users.map(u => u[1].likes), backgroundColor: users.map((_, i) => P[i % P.length] + 'cc'), borderColor: users.map((_, i) => P[i % P.length]), borderWidth: 2 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { r: { ticks: { color: tickColor(), font: { size: 10 }, backdropColor: 'transparent' }, grid: { color: gridColor() } } } }
    });
})();
</script>

<!-- ══ Search + Table ════════════════════════════════════════════ -->
<div class="row">
    <div class="col-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <form method="GET" class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label">Search by</label>
                        <select name="searchType" class="form-control">
                            <option value="">All comments</option>
                            <option value="postId"    <?= $searchType === 'postId'    ? 'selected' : '' ?>>Post ID</option>
                            <option value="commentId" <?= $searchType === 'commentId' ? 'selected' : '' ?>>Comment ID (show replies)</option>
                            <option value="creator"   <?= $searchType === 'creator'   ? 'selected' : '' ?>>Creator</option>
                        </select>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label">Keyword</label>
                        <input type="text" name="keyword" class="form-control" value="<?= htmlspecialchars($keyword) ?>" placeholder="Enter post id, comment id, creator name, or creator id">
                    </div>
                    <div class="col-md-2 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-info w-100"><i class="mdi mdi-magnify"></i> Search</button>
                        <a href="./index.php" class="btn btn-outline-light">Reset</a>
                    </div>
                </form>

                <?php if ($searchType === 'postId' && $keyword !== '') : ?>
                    <div class="alert alert-info mb-4" role="alert" style="border:1px solid rgba(139,92,246,.25);background:rgba(243,240,255,.9);color:#4c1d95;">
                        Affichage des commentaires liés au post <strong>#<?= htmlspecialchars($keyword) ?></strong>.
                    </div>
                <?php endif; ?>

                <?php if (empty($comments)) : ?>
                    <div class="empty-state-admin">
                        <h5>No comments found</h5>
                        <p class="text-muted mb-0">Try another search or reset the filters.</p>
                    </div>
                <?php else : ?>
                    <div class="table-responsive">
                        <table class="table table-dark admin-table" id="commentsTable">
                            <thead>
                                <tr>
                                    <th>Comment ID</th>
                                    <th>Target Type</th>
                                    <th>Target ID</th>
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
                            <?php foreach ($comments as $comment) : ?>
                                <tr>
                                    <td><?= htmlspecialchars($comment['id']) ?></td>
                                    <td><?= htmlspecialchars($comment['commentedItem']) ?></td>
                                    <td><?= htmlspecialchars($comment['idCommentedElement']) ?></td>
                                    <td><?= htmlspecialchars($comment['userName'] ?? ('User #' . $comment['idUser'])) ?></td>
                                    <td><?= htmlspecialchars(mb_strimwidth((string)($comment['text'] ?? ''), 0, 120, '...')) ?></td>
                                    <td><?= htmlspecialchars($comment['Sticker'] ?? $comment['sticker'] ?? '') ?></td>
                                    <td>
                                        <?php if (!empty($comment['image'])) : ?>
                                            <img src="../../public/<?= htmlspecialchars($comment['image']) ?>" alt="comment image" style="width:64px;height:64px;object-fit:cover;border-radius:8px;">
                                        <?php else : ?>
                                            <span class="badge badge-secondary">No image</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= (int)($comment['numberOfLike']    ?? 0) ?></td>
                                    <td><?= (int)($comment['numberOfDislike'] ?? 0) ?></td>
                                    <td>
                                        <?php if (($comment['commentedItem'] ?? '') === 'post') : ?>
                                            <a href="../post/details.php?id=<?= urlencode($comment['idCommentedElement']) ?>" class="admin-action-btn admin-view-btn"><i class="mdi mdi-eye-outline"></i> Post</a>
                                        <?php endif; ?>
                                        <a href="./index.php?searchType=commentId&keyword=<?= urlencode($comment['id']) ?>" class="admin-action-btn admin-view-btn"><i class="mdi mdi-source-branch"></i> Replies</a>
                                        <a href="./delete.php?id=<?= urlencode($comment['id']) ?>&searchType=<?= urlencode($searchType) ?>&keyword=<?= urlencode($keyword) ?>" class="admin-action-btn admin-delete-btn js-admin-delete"><i class="mdi mdi-delete-outline"></i> Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div id="commentPaginationControls">
                        <span class="pag-info" id="commentPagInfo"></span>
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <div class="pag-select">
                                <label for="commentPerPage">Rows per page:</label>
                                <select id="commentPerPage"><option value="5">5</option><option value="10" selected>10</option><option value="25">25</option><option value="50">50</option></select>
                            </div>
                            <div class="pag-buttons" id="commentPagButtons"></div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const tbody = document.getElementById('commentsTableBody');
    if (!tbody) return;
    const allRows = Array.from(tbody.querySelectorAll('tr'));
    const info = document.getElementById('commentPagInfo');
    const buttons = document.getElementById('commentPagButtons');
    const perSel = document.getElementById('commentPerPage');
    let currentPage = 1;
    let perPage = parseInt(perSel.value, 10);
    function totalPages() { return Math.max(1, Math.ceil(allRows.length / perPage)); }
    function render() {
        const total = totalPages();
        const start = (currentPage - 1) * perPage;
        const end = Math.min(start + perPage, allRows.length);
        allRows.forEach((row, i) => { row.style.display = (i >= start && i < end) ? '' : 'none'; });
        info.textContent = `Showing ${allRows.length === 0 ? 0 : start + 1}–${end} of ${allRows.length} comments`;
        buttons.innerHTML = '';
        buttons.appendChild(makeBtn('‹', currentPage === 1, () => { currentPage--; render(); }));
        pageRange(currentPage, total).forEach(p => {
            if (p === '…') {
                const el = document.createElement('span');
                el.textContent = '…';
                el.style.cssText = 'padding:0 4px;opacity:.4;font-size:13px;';
                buttons.appendChild(el);
            } else {
                const btn = makeBtn(p, false, () => { currentPage = p; render(); });
                if (p === currentPage) btn.classList.add('active');
                buttons.appendChild(btn);
            }
        });
        buttons.appendChild(makeBtn('›', currentPage === total, () => { currentPage++; render(); }));
    }
    function makeBtn(label, disabled, onClick) {
        const btn = document.createElement('button');
        btn.className = 'pag-btn';
        btn.textContent = label;
        btn.disabled = disabled;
        btn.addEventListener('click', onClick);
        return btn;
    }
    function pageRange(current, total) {
        if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
        const pages = new Set([1, total, current, current - 1, current + 1]);
        const sorted = [...pages].filter(p => p >= 1 && p <= total).sort((a, b) => a - b);
        const result = [];
        sorted.forEach((p, i) => { if (i > 0 && p - sorted[i - 1] > 1) result.push('…'); result.push(p); });
        return result;
    }
    perSel.addEventListener('change', () => { perPage = parseInt(perSel.value, 10); currentPage = 1; render(); });
    render();
})();
</script>

        </div>
    </div>
    </div>
</div>
</div>
<script src="../layout/back-layout.js<?= commentAssetVersion(__DIR__ . '/../layout/back-layout.js') ?>"></script>
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
