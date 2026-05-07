<?php
require_once '../../../Controleur/postC.php';

$postC = new PostC();
$pageTitle = 'All Posts';
$currentPage = 'posts';

$posts = $postC->listPosts();
$stats = $postC->getAdminStats();

/* ── Global stats ── */
$totalPosts    = (int)($stats['totalPosts']    ?? count($posts));
$totalViews    = (int)($stats['totalViews']    ?? 0);
$totalLikes    = (int)($stats['totalLikes']    ?? 0);
$totalDislikes = (int)($stats['totalDislikes'] ?? 0);
$approvalPct   = ($totalLikes + $totalDislikes) > 0
    ? round($totalLikes / ($totalLikes + $totalDislikes) * 100) : 0;
$avgViews = $totalPosts > 0 ? round($totalViews / $totalPosts) : 0;

/* ── JSON for JS ── */
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

/* ── Pagination ── */
#paginationControls {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 18px;
    padding-top: 14px;
    border-top: 1px solid rgba(233,30,140,.12);
}
#paginationControls .pag-info {
    font-size: 13px;
    opacity: .55;
}
.pag-buttons {
    display: flex;
    align-items: center;
    gap: 4px;
}
.pag-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 34px;
    height: 34px;
    padding: 0 8px;
    border-radius: 8px;
    border: 1.5px solid rgba(233,30,140,.22);
    background: transparent;
    color: inherit;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: background .15s, border-color .15s, color .15s;
}
.pag-btn:hover:not(:disabled) {
    background: rgba(233,30,140,.12);
    border-color: rgba(233,30,140,.45);
}
.pag-btn.active {
    background: #e91e8c;
    border-color: #e91e8c;
    color: #fff;
}
.pag-btn:disabled {
    opacity: .3;
    cursor: default;
}
.pag-select {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
}
.pag-select select {
    background: transparent;
    border: 1.5px solid rgba(233,30,140,.22);
    border-radius: 7px;
    color: inherit;
    padding: 4px 8px;
    font-size: 13px;
    cursor: pointer;
    outline: none;
}
.pag-select select:focus {
    border-color: #e91e8c;
}

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
body.light-mode .pag-btn { color: #2d0045; }
body.light-mode .pag-select select { color: #2d0045; background: #fff; }
body.light-mode #paginationControls { border-top-color: rgba(233,30,140,.15); }
</style>

<!-- ══ Header ════════════════════════════════════════════════════ -->
<div class="row">
    <div class="col-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h3 class="mb-1">All Posts Dashboard</h3>
                    <p class="text-muted mb-0">Moderation, statistics and activity tracking.</p>
                </div>
                <div class="d-flex gap-2 flex-wrap align-items-center">
                    <button class="theme-toggle-btn" id="themeToggle" onclick="toggleTheme()">
                        <i class="mdi mdi-weather-night" id="themeIcon"></i>
                        <span id="themeLabel">Light mode</span>
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
                <h6>Total Views</h6>
                <h2><?= number_format($totalViews, 0, ',', ' ') ?></h2>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 grid-margin stretch-card">
        <div class="card stat-card-mini">
            <div class="card-body">
                <h6>Approval Rate</h6>
                <h2><?= $approvalPct ?>%</h2>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 grid-margin stretch-card">
        <div class="card stat-card-mini">
            <div class="card-body">
                <h6>Avg. Views / Post</h6>
                <h2><?= number_format($avgViews, 0, ',', ' ') ?></h2>
            </div>
        </div>
    </div>
</div>

<!-- ══ Row 1 : Engagement pie + Content type pie ═════════════════ -->
<div class="row">
    <div class="col-md-6 grid-margin stretch-card">
        <div class="card chart-card">
            <div class="card-body">
                <p class="chart-title">Engagement Breakdown</p>
                <p class="chart-sub">Likes · Dislikes · Views without reaction</p>
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
                <p class="chart-title">Content Type</p>
                <p class="chart-sub">Format of published posts</p>
                <div class="chart-legend" id="leg-media"></div>
                <div style="position:relative;height:240px;">
                    <canvas id="chartMedia"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══ Row 2 : Creator activity bar + Grouped popularity bar ═════ -->
<div class="row">
    <div class="col-md-5 grid-margin stretch-card">
        <div class="card chart-card">
            <div class="card-body">
                <p class="chart-title">Creator Activity</p>
                <p class="chart-sub">Number of published posts</p>
                <div style="position:relative;height:260px;">
                    <canvas id="chartCreators"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-7 grid-margin stretch-card">
        <div class="card chart-card">
            <div class="card-body">
                <p class="chart-title">Post Performance</p>
                <p class="chart-sub">Top 8 by views</p>
                <div class="chart-legend">
                    <span><i style="background:#e91e8c"></i>Views</span>
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

<!-- ══ Row 3 : PolarArea + Radar ══════════════════════════════════ -->
<div class="row">
    <div class="col-md-5 grid-margin stretch-card">
        <div class="card chart-card">
            <div class="card-body">
                <p class="chart-title">Cumulative Views by Creator</p>
                <p class="chart-sub">PolarArea — total audience</p>
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
                <p class="chart-title">Top 5 Posts Profile</p>
                <p class="chart-sub">Radar — indicators normalized to 100</p>
                <div style="position:relative;height:260px;">
                    <canvas id="chartRadar"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {

    const P = [
        '#e91e8c',
        '#c026d3',
        '#9c27b0',
        '#7c3aed',
        '#f472b6',
        '#6a008a',
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

    /* 1. Pie — engagement */
    buildLegend('leg-engagement', [
        [`Likes (${totalLikes.toLocaleString('en-US')})`,    P[0]],
        [`Dislikes (${totalDislikes.toLocaleString('en-US')})`, P[3]],
        [`Neutral (${neutralViews.toLocaleString('en-US')})`,   P[5]],
    ]);
    new Chart(document.getElementById('chartEngagement'), {
        type: 'pie',
        data: {
            labels: ['Likes', 'Dislikes', 'Neutral'],
            datasets: [{ data: [totalLikes, totalDislikes, neutralViews],
                backgroundColor: [P[0], P[3], P[5]],
                borderWidth: 3, borderColor: 'transparent', hoverOffset: 10 }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });

    /* 2. Pie — content type */
    const withImage = raw.filter(p => p.hasImage).length;
    const withVideo = raw.filter(p => p.hasVideo && !p.hasImage).length;
    const textOnly  = raw.filter(p => !p.hasImage && !p.hasVideo).length;
    buildLegend('leg-media', [
        [`Image (${withImage})`, P[0]],
        [`Video (${withVideo})`, P[2]],
        [`Text (${textOnly})`,   P[5]],
    ]);
    new Chart(document.getElementById('chartMedia'), {
        type: 'pie',
        data: {
            labels: ['Image', 'Video', 'Text only'],
            datasets: [{ data: [withImage, withVideo, textOnly],
                backgroundColor: [P[0], P[2], P[5]],
                borderWidth: 3, borderColor: 'transparent', hoverOffset: 10 }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });

    /* 3. Bar — creator activity */
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

    /* 4. Grouped bar — top 8 performance */
    const top8 = [...raw].sort((a, b) => b.views - a.views).slice(0, 8);
    new Chart(document.getElementById('chartPopularity'), {
        type: 'bar',
        data: {
            labels: top8.map(p => p.subject.length > 14 ? p.subject.slice(0, 13) + '…' : p.subject),
            datasets: [
                { label: 'Views',    data: top8.map(p => p.views),    backgroundColor: P[0], borderRadius: 5, borderSkipped: false },
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

    /* 5. PolarArea — views by creator */
    buildLegend('leg-polar', creators.map((c, i) => [
        `${c[0]} (${c[1].views.toLocaleString('en-US')})`, P[i % P.length]
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

    /* 6. Radar — top 5 profile */
    const top5 = [...raw].sort((a, b) => b.views - a.views).slice(0, 5);
    const maxV = Math.max(...top5.map(p => p.views))    || 1;
    const maxL = Math.max(...top5.map(p => p.likes))    || 1;
    const maxD = Math.max(...top5.map(p => p.dislikes)) || 1;
    new Chart(document.getElementById('chartRadar'), {
        type: 'radar',
        data: {
            labels: ['Views', 'Likes', 'Engagement Rate', 'Dislikes', 'Like Ratio'],
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
    label.textContent = isLight ? 'Dark mode' : 'Light mode';
    localStorage.setItem('adminTheme', isLight ? 'light' : 'dark');
}
(function () {
    if (localStorage.getItem('adminTheme') === 'light') {
        document.body.classList.add('light-mode');
        const icon  = document.getElementById('themeIcon');
        const label = document.getElementById('themeLabel');
        if (icon)  icon.className    = 'mdi mdi-weather-sunny';
        if (label) label.textContent = 'Dark mode';
    }
})();
</script>

<!-- ══ Moderation table ══════════════════════════════════════════ -->
<div class="row">
    <div class="col-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Posts — Moderation List</h4>
                <?php if (empty($posts)) : ?>
                    <div class="empty-state-admin">
                        <h5>No posts available</h5>
                        <p class="text-muted mb-0">No creator has published a post yet.</p>
                    </div>
                <?php else : ?>
                    <div class="table-responsive">
                        <table class="table table-dark admin-table" id="postsTable">
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

                    <!-- ══ Pagination controls ══ -->
                    <div id="paginationControls">
                        <span class="pag-info" id="pagInfo"></span>

                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <div class="pag-select">
                                <label for="pagPerPage">Rows per page:</label>
                                <select id="pagPerPage">
                                    <option value="5">5</option>
                                    <option value="10" selected>10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                </select>
                            </div>

                            <div class="pag-buttons" id="pagButtons"></div>
                        </div>
                    </div>

                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const tbody   = document.getElementById('postsTableBody');
    if (!tbody) return;

    const allRows = Array.from(tbody.querySelectorAll('tr'));
    const info    = document.getElementById('pagInfo');
    const buttons = document.getElementById('pagButtons');
    const perSel  = document.getElementById('pagPerPage');

    let currentPage = 1;
    let perPage     = parseInt(perSel.value, 10);

    function totalPages() {
        return Math.max(1, Math.ceil(allRows.length / perPage));
    }

    function render() {
        const total  = totalPages();
        const start  = (currentPage - 1) * perPage;
        const end    = Math.min(start + perPage, allRows.length);

        /* Show/hide rows */
        allRows.forEach((row, i) => {
            row.style.display = (i >= start && i < end) ? '' : 'none';
        });

        /* Info text */
        info.textContent = `Showing ${allRows.length === 0 ? 0 : start + 1}–${end} of ${allRows.length} posts`;

        /* Build page buttons */
        buttons.innerHTML = '';

        /* Prev */
        const prev = makeBtn('‹', currentPage === 1, () => { currentPage--; render(); });
        buttons.appendChild(prev);

        /* Page numbers — show at most 7 buttons with ellipsis */
        const pages = pageRange(currentPage, total);
        pages.forEach(p => {
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

        /* Next */
        const next = makeBtn('›', currentPage === total, () => { currentPage++; render(); });
        buttons.appendChild(next);
    }

    function makeBtn(label, disabled, onClick) {
        const btn = document.createElement('button');
        btn.className   = 'pag-btn';
        btn.textContent = label;
        btn.disabled    = disabled;
        btn.addEventListener('click', onClick);
        return btn;
    }

    /* Produces array like [1, 2, '…', 8, 9, 10] */
    function pageRange(current, total) {
        if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
        const pages = new Set([1, total, current, current - 1, current + 1]);
        const sorted = [...pages].filter(p => p >= 1 && p <= total).sort((a, b) => a - b);
        const result = [];
        sorted.forEach((p, i) => {
            if (i > 0 && p - sorted[i - 1] > 1) result.push('…');
            result.push(p);
        });
        return result;
    }

    perSel.addEventListener('change', () => {
        perPage     = parseInt(perSel.value, 10);
        currentPage = 1;
        render();
    });

    render();
})();
</script>

<?php require_once '../partials/footer.php'; ?>