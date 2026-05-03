<?php
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

/* ── Stats globales ── */
$totalComments = (int)($stats['totalComments'] ?? 0);
$totalLikes    = (int)($stats['totalLikes']    ?? 0);
$totalDislikes = (int)($stats['totalDislikes'] ?? 0);
$approvalPct   = ($totalLikes + $totalDislikes) > 0
    ? round($totalLikes / ($totalLikes + $totalDislikes) * 100) : 0;

/* ── JSON pour JS ── */
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

require_once '../partials/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<style>
.theme-toggle-btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 7px 18px; border-radius: 999px;
    border: 1.5px solid rgba(233,30,140,.28);
    background: transparent; color: inherit;
    font-size: 13px; font-weight: 600; cursor: pointer;
    transition: background .2s;
}
.theme-toggle-btn:hover { background: rgba(233,30,140,.10); }
.stat-card-mini .card-body { min-height: 90px; }
.stat-card-mini h6 {
    font-size: 11px; font-weight: 600; letter-spacing: .06em;
    text-transform: uppercase; opacity: .5; margin-bottom: 8px;
}
.stat-card-mini h2 { font-size: 26px; font-weight: 700; margin: 0; }
.chart-card .card-body { padding: 1.4rem; }
.chart-title { font-size: 14px; font-weight: 700; margin-bottom: 2px; }
.chart-sub   { font-size: 12px; opacity: .45; margin-bottom: 14px; }
.chart-legend { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px; font-size: 12px; font-weight: 500; }
.chart-legend span { display: flex; align-items: center; gap: 6px; }
.chart-legend i { width: 11px; height: 11px; border-radius: 3px; display: inline-block; flex-shrink: 0; }

body.light-mode { background-color: #f9f0ff !important; color: #2d0045 !important; }
body.light-mode .card { background-color: #ffffff !important; border-color: rgba(233,30,140,.12) !important; color: #2d0045 !important; }
body.light-mode .chart-sub   { color: #9c27b0; }
body.light-mode .chart-title { color: #2d0045; }
body.light-mode .chart-legend { color: #2d0045; }
body.light-mode .stat-card-mini h2 { color: #2d0045; }
body.light-mode .text-muted { color: #9c27b0 !important; }
body.light-mode .table-dark { background-color: #fff !important; color: #2d0045 !important; }
body.light-mode .table-dark thead th { background: linear-gradient(90deg,#f3e8ff,#fce7f3) !important; color: #7c3aed !important; }
body.light-mode .table-dark td, body.light-mode .table-dark th { border-color: rgba(233,30,140,.10) !important; color: #2d0045 !important; }
body.light-mode .theme-toggle-btn { border-color: rgba(233,30,140,.22); color: #9c27b0; }
body.light-mode .post-creator-badge { color: #7c3aed; }
body.light-mode .admin-delete-btn { background: #fff0f8; }
</style>

<!-- ══ Entête ════════════════════════════════════════════════════ -->
<div class="row">
    <div class="col-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h3 class="mb-1">Comments Dashboard</h3>
                    <p class="text-muted mb-0">Search comments by post ID, comment ID to inspect replies, or by creator.</p>
                </div>
                <div class="d-flex gap-2 flex-wrap align-items-center">
                    <button class="theme-toggle-btn" id="themeToggle" onclick="toggleTheme()">
                        <i class="mdi mdi-weather-night" id="themeIcon"></i>
                        <span id="themeLabel">Mode clair</span>
                    </button>
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
        <div class="card stat-card-mini">
            <div class="card-body">
                <h6>Total Comments</h6>
                <h2><?= $totalComments ?></h2>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 grid-margin stretch-card">
        <div class="card stat-card-mini">
            <div class="card-body">
                <h6>Total Likes</h6>
                <h2><?= number_format($totalLikes, 0, ',', ' ') ?></h2>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 grid-margin stretch-card">
        <div class="card stat-card-mini">
            <div class="card-body">
                <h6>Total Dislikes</h6>
                <h2><?= number_format($totalDislikes, 0, ',', ' ') ?></h2>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 grid-margin stretch-card">
        <div class="card stat-card-mini">
            <div class="card-body">
                <h6>Approbation</h6>
                <h2><?= $approvalPct ?>%</h2>
            </div>
        </div>
    </div>
</div>

<!-- ══ Ligne 1 : Pie engagement + Pie cible ═════════════════════ -->
<div class="row">
    <div class="col-md-6 grid-margin stretch-card">
        <div class="card chart-card">
            <div class="card-body">
                <p class="chart-title">Répartition de l'engagement</p>
                <p class="chart-sub">Likes · Dislikes · Sans réaction</p>
                <div class="chart-legend" id="leg-engagement"></div>
                <div style="position:relative;height:240px;">
                    <canvas id="chartEngagement"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 grid-margin stretch-card">
        <div class="card chart-card">
            <div class="card-body">
                <p class="chart-title">Cible des commentaires</p>
                <p class="chart-sub">Commentaire sur post vs réponse à un commentaire</p>
                <div class="chart-legend" id="leg-target"></div>
                <div style="position:relative;height:240px;">
                    <canvas id="chartTarget"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══ Ligne 2 : Pie format + Bar utilisateurs ══════════════════ -->
<div class="row">
    <div class="col-md-4 grid-margin stretch-card">
        <div class="card chart-card">
            <div class="card-body">
                <p class="chart-title">Format des commentaires</p>
                <p class="chart-sub">Texte · Sticker · Image</p>
                <div class="chart-legend" id="leg-format"></div>
                <div style="position:relative;height:240px;">
                    <canvas id="chartFormat"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-8 grid-margin stretch-card">
        <div class="card chart-card">
            <div class="card-body">
                <p class="chart-title">Activité des utilisateurs</p>
                <p class="chart-sub">Top 6 — nombre de commentaires publiés</p>
                <div style="position:relative;height:240px;">
                    <canvas id="chartUsers"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══ Ligne 3 : Bar groupée top comments + PolarArea ══════════ -->
<div class="row">
    <div class="col-md-7 grid-margin stretch-card">
        <div class="card chart-card">
            <div class="card-body">
                <p class="chart-title">Top commentaires par likes</p>
                <p class="chart-sub">Top 8 — likes et dislikes</p>
                <div class="chart-legend">
                    <span><i style="background:#e91e8c"></i>Likes</span>
                    <span><i style="background:#7c3aed"></i>Dislikes</span>
                </div>
                <div style="position:relative;height:260px;">
                    <canvas id="chartTopComments"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-5 grid-margin stretch-card">
        <div class="card chart-card">
            <div class="card-body">
                <p class="chart-title">Likes cumulés par utilisateur</p>
                <p class="chart-sub">PolarArea — popularité des commentaires</p>
                <div class="chart-legend" id="leg-polar"></div>
                <div style="position:relative;height:260px;">
                    <canvas id="chartPolar"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {

    /*
     * Palette 100% rose / mauve — 6 tons bien distincts
     * tirés directement des variables CSS du thème admin
     */
    const P = [
        '#e91e8c',  /* rose vif       */
        '#c026d3',  /* fuchsia        */
        '#9c27b0',  /* mauve          */
        '#7c3aed',  /* violet         */
        '#f472b6',  /* rose doux      */
        '#6a008a',  /* mauve profond  */
    ];

    const raw = <?= $commentsJson ?>;

    const totalLikes    = raw.reduce((a, c) => a + c.likes,    0);
    const totalDislikes = raw.reduce((a, c) => a + c.dislikes, 0);
    const totalAll      = raw.length;
    const neutrals      = Math.max(0, totalAll - raw.filter(c => c.likes > 0 || c.dislikes > 0).length);

    function buildLegend(id, items) {
        const el = document.getElementById(id);
        if (!el) return;
        el.innerHTML = items.map(([label, color]) =>
            `<span><i style="background:${color}"></i>${label}</span>`
        ).join('');
    }
    function gridColor() {
        return document.body.classList.contains('light-mode')
            ? 'rgba(156,39,176,.12)' : 'rgba(233,30,140,.10)';
    }
    function tickColor() {
        return document.body.classList.contains('light-mode') ? '#9c27b0' : '#c084fc';
    }

    /* 1. Pie — engagement (rose vif = likes, violet = dislikes, mauve profond = neutres) */
    buildLegend('leg-engagement', [
        [`Likes (${totalLikes.toLocaleString('fr-FR')})`,      P[0]],
        [`Dislikes (${totalDislikes.toLocaleString('fr-FR')})`, P[3]],
        [`Neutres (${neutrals.toLocaleString('fr-FR')})`,       P[5]],
    ]);
    new Chart(document.getElementById('chartEngagement'), {
        type: 'pie',
        data: {
            labels: ['Likes', 'Dislikes', 'Neutres'],
            datasets: [{ data: [totalLikes, totalDislikes, neutrals],
                backgroundColor: [P[0], P[3], P[5]],
                borderWidth: 3, borderColor: 'transparent', hoverOffset: 10 }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });

    /* 2. Pie — cible */
    const onPost    = raw.filter(c => c.type === 'post').length;
    const onComment = raw.filter(c => c.type === 'comment').length;
    buildLegend('leg-target', [
        [`Sur post (${onPost})`,   P[0]],
        [`Réponse (${onComment})`, P[2]],
    ]);
    new Chart(document.getElementById('chartTarget'), {
        type: 'pie',
        data: {
            labels: ['Sur post', 'Réponse à un commentaire'],
            datasets: [{ data: [onPost, onComment],
                backgroundColor: [P[0], P[2]],
                borderWidth: 3, borderColor: 'transparent', hoverOffset: 10 }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });

    /* 3. Pie — format */
    const withText    = raw.filter(c => c.hasText).length;
    const withSticker = raw.filter(c => c.hasSticker && !c.hasText).length;
    const withImage   = raw.filter(c => c.hasImage  && !c.hasText && !c.hasSticker).length;
    buildLegend('leg-format', [
        [`Texte (${withText})`,      P[0]],
        [`Sticker (${withSticker})`, P[4]],
        [`Image (${withImage})`,     P[2]],
    ]);
    new Chart(document.getElementById('chartFormat'), {
        type: 'pie',
        data: {
            labels: ['Texte', 'Sticker', 'Image'],
            datasets: [{ data: [withText, withSticker, withImage],
                backgroundColor: [P[0], P[4], P[2]],
                borderWidth: 3, borderColor: 'transparent', hoverOffset: 10 }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });

    /* 4. Bar — activité utilisateurs (top 6) */
    const umap = {};
    raw.forEach(c => {
        if (!umap[c.user]) umap[c.user] = { comments: 0, likes: 0 };
        umap[c.user].comments++;
        umap[c.user].likes += c.likes;
    });
    const users = Object.entries(umap).sort((a,b) => b[1].comments - a[1].comments).slice(0, 6);
    new Chart(document.getElementById('chartUsers'), {
        type: 'bar',
        data: {
            labels: users.map(u => u[0]),
            datasets: [{ label: 'Commentaires',
                data: users.map(u => u[1].comments),
                backgroundColor: users.map((_, i) => P[i % P.length]),
                borderRadius: 7, borderSkipped: false }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { color: tickColor(), font: { size: 12 } }, grid: { display: false } },
                y: { ticks: { color: tickColor(), stepSize: 1 }, grid: { color: gridColor() }, beginAtZero: true }
            }
        }
    });

    /* 5. Bar groupée — top 8 commentaires par likes */
    const top8 = [...raw].sort((a,b) => b.likes - a.likes).slice(0, 8);
    new Chart(document.getElementById('chartTopComments'), {
        type: 'bar',
        data: {
            labels: top8.map(c => c.text || '(vide)'),
            datasets: [
                { label: 'Likes',    data: top8.map(c => c.likes),    backgroundColor: P[0], borderRadius: 5, borderSkipped: false },
                { label: 'Dislikes', data: top8.map(c => c.dislikes), backgroundColor: P[3], borderRadius: 5, borderSkipped: false }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { color: tickColor(), font: { size: 11 }, autoSkip: false, maxRotation: 35 }, grid: { display: false } },
                y: { ticks: { color: tickColor() }, grid: { color: gridColor() }, beginAtZero: true }
            }
        }
    });

    /* 6. PolarArea — likes cumulés par utilisateur */
    buildLegend('leg-polar', users.map((u, i) => [
        `${u[0]} (${u[1].likes.toLocaleString('fr-FR')} likes)`, P[i % P.length]
    ]));
    new Chart(document.getElementById('chartPolar'), {
        type: 'polarArea',
        data: {
            labels: users.map(u => u[0]),
            datasets: [{
                data: users.map(u => u[1].likes),
                backgroundColor: users.map((_, i) => P[i % P.length] + 'cc'),
                borderColor:     users.map((_, i) => P[i % P.length]),
                borderWidth: 2
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { r: {
                ticks: { color: tickColor(), font: { size: 10 }, backdropColor: 'transparent' },
                grid: { color: gridColor() }
            }}
        }
    });

})();
</script>

<script>
function toggleTheme() {
    const body  = document.body;
    const icon  = document.getElementById('themeIcon');
    const label = document.getElementById('themeLabel');
    const isLight = body.classList.toggle('light-mode');
    icon.className    = isLight ? 'mdi mdi-weather-sunny' : 'mdi mdi-weather-night';
    label.textContent = isLight ? 'Mode sombre' : 'Mode clair';
    localStorage.setItem('adminTheme', isLight ? 'light' : 'dark');
}
(function () {
    if (localStorage.getItem('adminTheme') === 'light') {
        document.body.classList.add('light-mode');
        const icon  = document.getElementById('themeIcon');
        const label = document.getElementById('themeLabel');
        if (icon)  icon.className    = 'mdi mdi-weather-sunny';
        if (label) label.textContent = 'Mode sombre';
    }
})();
</script>

<!-- ══ Recherche ═════════════════════════════════════════════════ -->
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
                        <input type="text" name="keyword" class="form-control"
                            value="<?= htmlspecialchars($keyword) ?>"
                            placeholder="Enter post id, comment id, creator name, or creator id">
                    </div>
                    <div class="col-md-2 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-info w-100"><i class="mdi mdi-magnify"></i> Search</button>
                        <a href="./index.php" class="btn btn-outline-light">Reset</a>
                    </div>
                </form>

                <?php if (empty($comments)) : ?>
                    <div class="empty-state-admin">
                        <h5>No comments found</h5>
                        <p class="text-muted mb-0">Try another search or reset the filters.</p>
                    </div>
                <?php else : ?>
                    <div class="table-responsive">
                        <table class="table table-dark admin-table">
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
                            <tbody>
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
                                            <img src="../../public/<?= htmlspecialchars($comment['image']) ?>"
                                                 alt="comment image"
                                                 style="width:64px;height:64px;object-fit:cover;border-radius:8px;">
                                        <?php else : ?>
                                            <span class="badge badge-secondary">No image</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= (int)($comment['numberOfLike']    ?? 0) ?></td>
                                    <td><?= (int)($comment['numberOfDislike'] ?? 0) ?></td>
                                    <td>
                                        <?php if (($comment['commentedItem'] ?? '') === 'post') : ?>
                                            <a href="../post/details.php?id=<?= urlencode($comment['idCommentedElement']) ?>"
                                               class="admin-action-btn admin-view-btn">
                                                <i class="mdi mdi-eye-outline"></i> Post
                                            </a>
                                        <?php endif; ?>
                                        <a href="./index.php?searchType=commentId&keyword=<?= urlencode($comment['id']) ?>"
                                           class="admin-action-btn admin-view-btn">
                                            <i class="mdi mdi-source-branch"></i> Replies
                                        </a>
                                        <a href="./delete.php?id=<?= urlencode($comment['id']) ?>&searchType=<?= urlencode($searchType) ?>&keyword=<?= urlencode($keyword) ?>"
                                           class="admin-action-btn admin-delete-btn js-admin-delete">
                                            <i class="mdi mdi-delete-outline"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../partials/footer.php'; ?>