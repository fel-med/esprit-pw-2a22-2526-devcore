<?php
if (!isset($messages)) {
    $messages = [];
}
$total_signales = count($messages);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages signalés - Admin Cre8Connect</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --bg-base: #0b0e1a; --bg-surface: #0f1117; --bg-elevated: #161b2e; --bg-hover: #1e2235;
            --border: #1e2235; --border-soft: #161b2e;
            --text-main: #e8eaf0; --text-soft: #7b82a0; --text-muted: #3d4260;
            --primary: #7c6eff; --primary-dim: rgba(124,110,255,.15);
            --danger: #f43f5e; --danger-dim: rgba(244,63,94,.15);
            --success: #22c55e; --radius: 12px; --radius-lg: 16px;
            --kpi-1-from: #6c47ff; --kpi-1-to: #9b6dff;
            --kpi-2-from: #e8305a; --kpi-2-to: #ff6b9d;
            --kpi-3-from: #22c55e; --kpi-3-to: #4ade80;
        }
        body { font-family: 'Inter', sans-serif; background: var(--bg-base); color: var(--text-main); display: flex; min-height: 100vh; }
        .sidebar { width: 220px; background: var(--bg-surface); border-right: 1px solid var(--border); position: fixed; height: 100vh; overflow-y: auto; }
        .sidebar-logo { padding: 18px 20px; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 10px; }
        .logo-img { width: 32px; height: 32px; border-radius: 8px; object-fit: cover; }
        .logo-text { font-size: 1rem; font-weight: 800; color: var(--text-main); }
        .sidebar-section-label { padding: 16px 20px 6px; font-size: 0.65rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em; }
        .sidebar-nav { padding: 8px 0 20px; }
        .nav-item { display: flex; align-items: center; gap: 10px; padding: 9px 20px; color: var(--text-soft); text-decoration: none; font-size: 0.82rem; font-weight: 500; border-left: 3px solid transparent; transition: all 0.18s; }
        .nav-item:hover { background: var(--bg-hover); color: var(--text-main); }
        .nav-item.active { background: var(--primary-dim); color: var(--primary); border-left-color: var(--primary); }
        .nav-icon { width: 22px; height: 22px; border-radius: 6px; flex-shrink: 0; display: inline-flex; align-items: center; justify-content: center; font-size: 0.75rem; }
        .main-wrap { margin-left: 220px; flex: 1; }
        .topbar { background: var(--bg-surface); border-bottom: 1px solid var(--border); padding: 0 24px; height: 56px; display: flex; align-items: center; justify-content: space-between; }
        .breadcrumb { font-size: 0.78rem; color: var(--text-soft); }
        .breadcrumb span { color: var(--text-main); font-weight: 600; }
        .topbar-actions { display: flex; align-items: center; gap: 10px; }
        .topbar-icon-btn { width: 34px; height: 34px; border-radius: 8px; background: var(--bg-elevated); border: 1px solid var(--border); color: var(--text-soft); display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 0.9rem; }
        .topbar-avatar { width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, #7c6eff, #a855f7); display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700; color: #fff; cursor: pointer; }
        .topbar-hamburger { background: none; border: none; color: var(--text-soft); font-size: 1.1rem; cursor: pointer; padding: 4px 8px; border-radius: 6px; transition: all 0.18s; }
        .topbar-hamburger:hover { background: var(--bg-hover); color: var(--text-main); }
        .topbar-search { display: flex; align-items: center; gap: 8px; background: var(--bg-elevated); border: 1px solid var(--border); border-radius: 8px; padding: 7px 14px; width: 340px; }
        .topbar-search svg { color: var(--text-muted); flex-shrink: 0; }
        .topbar-search input { background: none; border: none; outline: none; color: var(--text-main); font-size: 0.82rem; width: 100%; font-family: 'Inter', sans-serif; }
        .topbar-search input::placeholder { color: var(--text-muted); }
        .topbar-user { display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 4px 8px; border-radius: 8px; transition: background 0.18s; }
        .topbar-user:hover { background: var(--bg-hover); }
        .topbar-username { font-size: 0.82rem; font-weight: 600; color: var(--text-main); }
        .content { padding: 24px; }
        .page-header { margin-bottom: 24px; }
        .page-header h1 { font-size: 1.4rem; font-weight: 700; letter-spacing: -0.3px; }
        .page-header p { font-size: 0.78rem; color: var(--text-soft); margin-top: 4px; }
        .kpi-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
        .kpi-card { background: var(--bg-elevated); border: 1px solid var(--border); border-left: 3px solid var(--primary); border-radius: var(--radius-lg); padding: 20px; position: relative; overflow: hidden; transition: transform 0.2s, box-shadow 0.2s; }
        .kpi-card:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,0.3); border-color: var(--primary); }
        .kpi-card:nth-child(1) { border-left-color: #7c6eff; }
        .kpi-card:nth-child(2) { border-left-color: #f43f5e; }
        .kpi-card:nth-child(3) { border-left-color: #22c55e; }
        .kpi-value { font-size: 1.8rem; font-weight: 700; color: var(--text-main); font-family: 'Inter', monospace; line-height: 1; margin-top: 8px; }
        .kpi-label { font-size: 0.68rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-soft); margin-top: 8px; }
        .message-card { background: var(--bg-elevated); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 20px; margin-bottom: 16px; }
        .message-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid var(--border); }
        .message-forum { font-size: 0.8rem; color: var(--primary); }
        .message-author { display: flex; align-items: center; gap: 12px; margin-bottom: 8px; font-size: 0.82rem; }
        .message-date { font-size: 0.7rem; color: var(--text-soft); }
        .message-content { background: var(--bg-base); padding: 16px; border-radius: 12px; line-height: 1.6; margin-top: 12px; font-size: 0.85rem; }
        .btn-delete { background: var(--danger-dim); color: var(--danger); border: none; padding: 8px 16px; border-radius: 8px; cursor: pointer; font-size: 0.75rem; font-weight: 600; transition: all 0.18s; }
        .btn-delete:hover { background: var(--danger); color: white; }
        .empty-state { text-align: center; padding: 60px; background: var(--bg-elevated); border-radius: var(--radius-lg); border: 1px solid var(--border); }
        .empty-state-icon { font-size: 3rem; margin-bottom: 16px; }
        @media (max-width: 768px) { .sidebar { display: none; } .main-wrap { margin-left: 0; } .kpi-grid { grid-template-columns: 1fr; } }
        /* ========= ADD THESE DYNAMIC STATS ENHANCEMENTS AT THE BOTTOM OF YOUR EXISTING CSS ========= */
/* Keep ALL your original CSS above, then add these rules at the end */

/* KPI Cards - Dynamic Enhancements */
.kpi-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.3);
    border-color: var(--primary);
}
.kpi-card::before {
    transition: height 0.2s;
}
.kpi-card:hover::before {
    height: 4px;
}
.kpi-value {
    transition: all 0.2s;
}
.kpi-card:hover .kpi-value {
    text-shadow: 0 0 6px rgba(88,166,255,0.5);
}

/* Stats Section - Dynamic */
.stats-section {
    transition: box-shadow 0.2s;
}
.stats-section:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

/* Chart Cards - Dynamic */
.chart-card {
    transition: all 0.2s;
    border: 1px solid transparent;
}
.chart-card:hover {
    border-color: var(--primary-dim);
    background: var(--bg-hover);
}

/* Progress Bars - Animated */
.progress-fill {
    transition: width 0.6s cubic-bezier(0.2, 0.9, 0.4, 1.1);
}
tr:hover .progress-fill {
    filter: brightness(1.05);
}

/* Rank Badges - Dynamic */
.rank-badge {
    transition: all 0.1s;
}
.rank-1 { 
    background: #fbbf24; 
    color: #0d1117; 
    box-shadow: 0 0 0 1px rgba(251,191,36,0.3); 
}
.top-table tr:hover .rank-badge {
    transform: scale(1.05);
}

/* Type Badges - Dynamic */
.type-badge {
    transition: 0.1s;
}
.type-badge:hover {
    filter: brightness(1.1);
    transform: scale(1.02);
}

/* Table Sorting - Enhanced */
th {
    transition: background 0.2s, color 0.1s;
}
th:hover {
    background: var(--bg-hover);
    color: var(--primary);
}
.sort-icon {
    transition: opacity 0.2s, transform 0.1s;
}

/* Buttons - Dynamic */
.btn-reset {
    transition: all 0.2s;
}
.btn-reset:hover {
    transform: translateY(-1px);
}
.action-btn {
    transition: all 0.15s;
}
.action-btn:hover {
    transform: translateY(-1px);
    filter: brightness(1.05);
}
.btn-primary-admin {
    transition: all 0.2s;
}
.btn-primary-admin:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(88,166,255,0.3);
}

/* Canvas hover effect */
canvas {
    transition: opacity 0.2s;
}
canvas:hover {
    opacity: 0.95;
}

/* Table row transition */
tbody tr {
    transition: background 0.1s ease;
}
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-logo"><img src="<?= $BASE ?>/Vue/public/images/logo.png" class="logo-img"><span class="logo-text">Cre8Connect</span></div>
    <div class="sidebar-section-label">Navigation</div>
    <div class="sidebar-nav">
        <a href="<?= $BASE ?>/Controleur/evenementC.php?action=admin" class="nav-item"><span class="nav-icon" style="background:rgba(245,158,11,0.15);color:#f59e0b;">📅</span> Événements</a>
        <a href="<?= $BASE ?>/Controleur/forumC.php?action=admin" class="nav-item"><span class="nav-icon" style="background:rgba(124,110,255,0.15);color:#7c6eff;">💬</span> Forums</a>
        <a href="#" class="nav-item active"><span class="nav-icon" style="background:rgba(244,63,94,0.15);color:#f43f5e;">🚩</span> Messages signalés</a>
        <a href="<?= $BASE ?>/Controleur/forumC.php?action=statistiques" class="nav-item"><span class="nav-icon" style="background:rgba(34,197,94,0.15);color:#22c55e;">📊</span> Statistiques</a>
    </div>
</aside>

<div class="main-wrap">
    <header class="topbar">
        <div class="breadcrumb">Dashboard / Communauté / <span>Messages signalés</span></div>
        <div class="topbar-actions">
            <div class="topbar-user">
                <div class="topbar-avatar">AD</div>
                <span class="topbar-username">Utilisateur</span>
                <span style="color:var(--text-soft);font-size:0.7rem;">▾</span>
            </div>
        </div>
    </header>

    <div class="content">
        <div class="page-header"><h1>🚩 Messages signalés</h1><p>Modération des messages signalés par les utilisateurs</p></div>

        <div class="kpi-grid">
            <div class="kpi-card"><div class="kpi-value"><?= $total_signales ?></div><div class="kpi-label">Total signalés</div></div>
            <div class="kpi-card"><div class="kpi-value"><?= $total_signales ?></div><div class="kpi-label">En attente</div></div>
            <div class="kpi-card"><div class="kpi-value">0</div><div class="kpi-label">Supprimés</div></div>
        </div>

        <?php if (empty($messages)): ?>
            <div class="empty-state"><div class="empty-state-icon">✅</div><h3>Aucun message signalé</h3><p>Tous les messages sont conformes</p></div>
        <?php else: ?>
            <?php foreach ($messages as $msg): ?>
            <div class="message-card">
                <div class="message-header">
                    <div class="message-forum">📌 Forum : <?= htmlspecialchars($msg['titreForum']) ?></div>
                    <button class="btn-delete" onclick="supprimerMessage(<?= $msg['idMessage'] ?>)">🗑 Supprimer</button>
                </div>
                <div class="message-author">
                    <span>👤 <?= htmlspecialchars($msg['nom_utilisateur']) ?></span>
                    <span class="message-date">📅 <?= date('d/m/Y H:i', strtotime($msg['dateMessage'])) ?></span>
                </div>
                <div class="message-content"><?= nl2br(htmlspecialchars($msg['message'])) ?></div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
    function supprimerMessage(id) {
        if (confirm('Supprimer ce message signalé ?')) window.location.href = `<?= $BASE ?>/Controleur/forumC.php?action=supprimer_message&id=${id}`;
    }
    function initTheme() {
        const saved = localStorage.getItem('theme');
        if (saved === 'dark') { document.body.classList.add('dark-mode'); document.getElementById('themeToggle').textContent = '☀️'; }
        else { document.body.classList.remove('dark-mode'); document.getElementById('themeToggle').textContent = '🌙'; }
    }
    function toggleTheme() {
        if (document.body.classList.contains('dark-mode')) { document.body.classList.remove('dark-mode'); localStorage.setItem('theme', 'light'); document.getElementById('themeToggle').textContent = '🌙'; }
        else { document.body.classList.add('dark-mode'); localStorage.setItem('theme', 'dark'); document.getElementById('themeToggle').textContent = '☀️'; }
    }
    document.addEventListener('DOMContentLoaded', function() { initTheme(); document.getElementById('themeToggle').addEventListener('click', toggleTheme); });
</script>
</body>
</html>