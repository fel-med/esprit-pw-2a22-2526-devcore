<?php
require_once '../../../Controleur/postC.php';

$postC = new PostC();
$pageTitle = 'All Posts';
$currentPage = 'posts';

$posts = $postC->listPosts();
$stats = $postC->getAdminStats();

/* ── Stats globales ── */
$totalPosts    = (int)($stats['totalPosts']    ?? count($posts));
$totalViews    = (int)($stats['totalViews']    ?? 0);
$totalLikes    = (int)($stats['totalLikes']    ?? 0);
$totalDislikes = (int)($stats['totalDislikes'] ?? 0);
$approvalPct   = ($totalLikes + $totalDislikes) > 0
    ? round($totalLikes / ($totalLikes + $totalDislikes) * 100) : 0;
$avgViews = $totalPosts > 0 ? round($totalViews / $totalPosts) : 0;

/* ── JSON pour JS ── */
$postsJson = json_encode(array_map(fn($p) => [
    'subject'  => $p['subject'],
    'creator'  => $p['creatorName'] ?? ('Créateur #' . $p['idCreateur']),
    'views'    => (int)$p['numberOfView'],
    'likes'    => (int)$p['numberOfLike'],
    'dislikes' => (int)$p['numberOfDislike'],
    'hasImage' => !empty($p['imageContent']),
    'hasVideo' => !empty($p['VideoContent']),
], $posts), JSON_UNESCAPED_UNICODE);

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
                    <h3 class="mb-1">All Posts Dashboard</h3>
                    <p class="text-muted mb-0">Modération, statistiques et suivi de l'activité.</p>
                </div>
                <div class="d-flex gap-2 flex-wrap align-items-center">
                    <button class="theme-toggle-btn" id="themeToggle" onclick="toggleTheme()">
                        <i class="mdi mdi-weather-night" id="themeIcon"></i>
                        <span id="themeLabel">Mode clair</span>
                    </button>
                    <a href="../comment/index.php" class="btn btn-info">
                        <i class="mdi mdi-comment-multiple-outline"></i> Manage Comments
                    </a>
                    <a href="../../FrontOffice/post/index.php" class="btn btn-success">
                        <i class="mdi mdi-open-in-new"></i> Open Actuality
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
                <h6>Total Posts</h6>
                <h2><?= $totalPosts ?></h2>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 grid-margin stretch-card">
        <div class="card stat-card-mini">
            <div class="card-body">
                <h6>Total Vues</h6>
                <h2><?= number_format($totalViews, 0, ',', ' ') ?></h2>
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
    <div class="col-6 col-md-3 grid-margin stretch-card">
        <div class="card stat-card-mini">
            <div class="card-body">
                <h6>Moy. Vues / Post</h6>
                <h2><?= number_format($avgViews, 0, ',', ' ') ?></h2>
            </div>
        </div>
    </div>
</div>

<!-- ══ Ligne 1 : Pie engagement + Pie type contenu ══════════════ -->
<div class="row">
    <div class="col-md-6 grid-margin stretch-card">
        <div class="card chart-card">
            <div class="card-body">
                <p class="chart-title">Répartition de l'engagement</p>
                <p class="chart-sub">Likes · Dislikes · Vues sans réaction</p>
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
                <p class="chart-title">Type de contenu</p>
                <p class="chart-sub">Format des posts publiés</p>
                <div class="chart-legend" id="leg-media"></div>
                <div style="position:relative;height:240px;">
                    <canvas id="chartMedia"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══ Ligne 2 : Bar créateurs + Bar groupée popularité ═════════ -->
<div class="row">
    <div class="col-md-5 grid-margin stretch-card">
        <div class="card chart-card">
            <div class="card-body">
                <p class="chart-title">Activité des créateurs</p>
                <p class="chart-sub">Nombre de posts publiés</p>
                <div style="position:relative;height:260px;">
                    <canvas id="chartCreators"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-7 grid-margin stretch-card">
        <div class="card chart-card">
            <div class="card-body">
                <p class="chart-title">Performance des posts</p>
                <p class="chart-sub">Top 8 par vues</p>
                <div class="chart-legend">
                    <span><i style="background:#e91e8c"></i>Vues</span>
                    <span><i style="background:#c026d3"></i>Likes</span>
                    <span><i style="background:#7c3aed"></i>Dislikes</span>
                </div>
                <div style="position:relative;height:240px;">
                    <canvas id="chartPopularity"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══ Ligne 3 : PolarArea + Radar ══════════════════════════════ -->
<div class="row">
    <div class="col-md-5 grid-margin stretch-card">
        <div class="card chart-card">
            <div class="card-body">
                <p class="chart-title">Vues cumulées par créateur</p>
                <p class="chart-sub">PolarArea — audience totale</p>
                <div class="chart-legend" id="leg-polar"></div>
                <div style="position:relative;height:260px;">
                    <canvas id="chartPolar"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-7 grid-margin stretch-card">
        <div class="card chart-card">
            <div class="card-body">
                <p class="chart-title">Profil des top 5 posts</p>
                <p class="chart-sub">Radar — indicateurs normalisés sur 100</p>
                <div style="position:relative;height:260px;">
                    <canvas id="chartRadar"></canvas>
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

    const raw = <?= $postsJson ?>;

    const totalLikes    = raw.reduce((a, p) => a + p.likes,    0);
    const totalDislikes = raw.reduce((a, p) => a + p.dislikes, 0);
    const totalViews    = raw.reduce((a, p) => a + p.views,    0);
    const neutralViews  = Math.max(0, totalViews - totalLikes - totalDislikes);

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
        [`Neutres (${neutralViews.toLocaleString('fr-FR')})`,   P[5]],
    ]);
    new Chart(document.getElementById('chartEngagement'), {
        type: 'pie',
        data: {
            labels: ['Likes', 'Dislikes', 'Neutres'],
            datasets: [{ data: [totalLikes, totalDislikes, neutralViews],
                backgroundColor: [P[0], P[3], P[5]],
                borderWidth: 3, borderColor: 'transparent', hoverOffset: 10 }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });

    /* 2. Pie — type de contenu */
    const withImage = raw.filter(p => p.hasImage).length;
    const withVideo = raw.filter(p => p.hasVideo && !p.hasImage).length;
    const textOnly  = raw.filter(p => !p.hasImage && !p.hasVideo).length;
    buildLegend('leg-media', [
        [`Image (${withImage})`, P[0]],
        [`Vidéo (${withVideo})`, P[2]],
        [`Texte (${textOnly})`,  P[5]],
    ]);
    new Chart(document.getElementById('chartMedia'), {
        type: 'pie',
        data: {
            labels: ['Image', 'Vidéo', 'Texte seul'],
            datasets: [{ data: [withImage, withVideo, textOnly],
                backgroundColor: [P[0], P[2], P[5]],
                borderWidth: 3, borderColor: 'transparent', hoverOffset: 10 }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });

    /* 3. Bar — activité créateurs */
    const cmap = {};
    raw.forEach(p => {
        if (!cmap[p.creator]) cmap[p.creator] = { posts: 0, views: 0 };
        cmap[p.creator].posts++;
        cmap[p.creator].views += p.views;
    });
    const creators = Object.entries(cmap).sort((a,b) => b[1].posts - a[1].posts).slice(0, 6);
    new Chart(document.getElementById('chartCreators'), {
        type: 'bar',
        data: {
            labels: creators.map(c => c[0]),
            datasets: [{ label: 'Posts',
                data: creators.map(c => c[1].posts),
                backgroundColor: creators.map((_, i) => P[i % P.length]),
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

    /* 4. Bar groupée — performance top 8 */
    const top8 = [...raw].sort((a, b) => b.views - a.views).slice(0, 8);
    new Chart(document.getElementById('chartPopularity'), {
        type: 'bar',
        data: {
            labels: top8.map(p => p.subject.length > 14 ? p.subject.slice(0, 13) + '…' : p.subject),
            datasets: [
                { label: 'Vues',     data: top8.map(p => p.views),    backgroundColor: P[0], borderRadius: 5, borderSkipped: false },
                { label: 'Likes',    data: top8.map(p => p.likes),    backgroundColor: P[1], borderRadius: 5, borderSkipped: false },
                { label: 'Dislikes', data: top8.map(p => p.dislikes), backgroundColor: P[3], borderRadius: 5, borderSkipped: false }
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

    /* 5. PolarArea — vues par créateur */
    buildLegend('leg-polar', creators.map((c, i) => [
        `${c[0]} (${c[1].views.toLocaleString('fr-FR')})`, P[i % P.length]
    ]));
    new Chart(document.getElementById('chartPolar'), {
        type: 'polarArea',
        data: {
            labels: creators.map(c => c[0]),
            datasets: [{
                data: creators.map(c => c[1].views),
                backgroundColor: creators.map((_, i) => P[i % P.length] + 'cc'),
                borderColor:     creators.map((_, i) => P[i % P.length]),
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

    /* 6. Radar — profil top 5 */
    const top5 = [...raw].sort((a, b) => b.views - a.views).slice(0, 5);
    const maxV = Math.max(...top5.map(p => p.views))    || 1;
    const maxL = Math.max(...top5.map(p => p.likes))    || 1;
    const maxD = Math.max(...top5.map(p => p.dislikes)) || 1;
    new Chart(document.getElementById('chartRadar'), {
        type: 'radar',
        data: {
            labels: ['Vues', 'Likes', 'Taux engagement', 'Dislikes', 'Ratio likes'],
            datasets: top5.map((p, i) => ({
                label: p.subject.length > 16 ? p.subject.slice(0, 15) + '…' : p.subject,
                data: [
                    Math.round(p.views    / maxV * 100),
                    Math.round(p.likes    / maxL * 100),
                    Math.round((p.likes + p.dislikes) / Math.max(1, p.views) * 100),
                    Math.round(p.dislikes / maxD * 100),
                    Math.round(p.likes / Math.max(1, p.likes + p.dislikes) * 100)
                ],
                borderColor:          P[i % P.length],
                backgroundColor:      P[i % P.length] + '25',
                borderWidth: 2,
                pointBackgroundColor: P[i % P.length],
                pointRadius: 4
            }))
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: true, position: 'bottom',
                labels: { color: tickColor(), font: { size: 11 }, boxWidth: 11, padding: 12 } } },
            scales: { r: {
                min: 0, max: 100,
                ticks: { color: tickColor(), font: { size: 10 }, backdropColor: 'transparent', stepSize: 25 },
                grid: { color: gridColor() },
                pointLabels: { color: tickColor(), font: { size: 11 } }
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

<!-- ══ Tableau modération ════════════════════════════════════════ -->
<div class="row">
    <div class="col-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Posts — liste de modération</h4>
                <?php if (empty($posts)) : ?>
                    <div class="empty-state-admin">
                        <h5>Aucun post disponible</h5>
                        <p class="text-muted mb-0">Aucun créateur n'a encore publié de post.</p>
                    </div>
                <?php else : ?>
                    <div class="table-responsive">
                        <table class="table table-dark admin-table">
                            <thead>
                                <tr>
                                    <th>Média</th>
                                    <th>Créateur</th>
                                    <th>Sujet</th>
                                    <th>Contenu</th>
                                    <th>Date</th>
                                    <th>Vues</th>
                                    <th>Likes</th>
                                    <th>Dislikes</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($posts as $post) : ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($post['imageContent'])) : ?>
                                            <img src="../../public/<?= htmlspecialchars($post['imageContent']) ?>" alt="Post image" class="admin-media-thumb">
                                        <?php elseif (!empty($post['VideoContent'])) : ?>
                                            <video class="admin-video-thumb" muted><source src="../../public/<?= htmlspecialchars($post['VideoContent']) ?>"></video>
                                        <?php else : ?>
                                            <span class="badge badge-secondary">No media</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="post-creator-badge">
                                            <i class="mdi mdi-account-circle"></i>
                                            <?= htmlspecialchars($post['creatorName'] ?? ('Creator #' . $post['idCreateur'])) ?>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($post['subject']) ?></td>
                                    <td><div class="post-excerpt"><?= htmlspecialchars($post['textContent']) ?></div></td>
                                    <td><?= htmlspecialchars($post['creationDate']) ?></td>
                                    <td><?= (int)$post['numberOfView'] ?></td>
                                    <td><?= (int)$post['numberOfLike'] ?></td>
                                    <td><?= (int)$post['numberOfDislike'] ?></td>
                                    <td>
                                        <a href="./details.php?id=<?= urlencode($post['id']) ?>" class="admin-action-btn admin-view-btn">
                                            <i class="mdi mdi-eye-outline"></i> View
                                        </a>
                                        <a href="../comment/index.php?searchType=postId&keyword=<?= urlencode($post['id']) ?>" class="admin-action-btn admin-view-btn">
                                            <i class="mdi mdi-comment-outline"></i> Comments
                                        </a>
                                        <a href="./delete.php?id=<?= urlencode($post['id']) ?>" class="admin-action-btn admin-delete-btn js-admin-delete">
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