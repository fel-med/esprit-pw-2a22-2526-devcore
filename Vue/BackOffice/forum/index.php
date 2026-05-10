<?php
// Vérifier si les variables sont définies
if (!isset($forums)) {
    $forums = [];
}
if (!isset($messages_signales)) {
    $messages_signales = [];
}
if (!isset($stats)) {
    $stats = [
        'total_forums' => 0,
        'total_messages' => 0,
        'total_participants' => 0,
        'forums_actifs' => 0,
        'messages_signales' => 0,
        'months_labels' => [],
        'messages_data' => [],
        'top_forums' => [],
        'top_contributeurs' => []
    ];
}

$total_forums = count($forums);
$total_messages = array_sum(array_column($forums, 'nb_messages'));
$total_participants = count(array_unique(array_column($forums, 'idUtilisateur')));
$forums_actifs = count(array_filter($forums, function($f) { return $f['est_actif'] == 1; }));
$total_signales = count($messages_signales);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Forums - Admin Cre8Connect</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg-base:      #0b0e1a;
            --bg-surface:   #0f1117;
            --bg-elevated:  #161b2e;
            --bg-hover:     #1e2235;
            --bg-card:      #131720;
            --border:       #1e2235;
            --border-soft:  #161b2e;
            --text-main:    #e8eaf0;
            --text-soft:    #7b82a0;
            --text-muted:   #3d4260;
            --primary:      #7c6eff;
            --primary-dim:  rgba(124,110,255,.15);
            --success:      #22c55e;
            --success-dim:  rgba(34,197,94,.15);
            --warning:      #f59e0b;
            --warning-dim:  rgba(245,158,11,.15);
            --danger:       #f43f5e;
            --danger-dim:   rgba(244,63,94,.15);
            --purple:       #a855f7;
            --purple-dim:   rgba(168,85,247,.15);
            --pink:         #ec4899;
            --radius:       12px;
            --radius-lg:    16px;
            /* KPI gradient colors */
            --kpi-1-from: #6c47ff; --kpi-1-to: #9b6dff;
            --kpi-2-from: #e8305a; --kpi-2-to: #ff6b9d;
            --kpi-3-from: #22c55e; --kpi-3-to: #4ade80;
            --kpi-4-from: #7c3aed; --kpi-4-to: #a855f7;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-base);
            color: var(--text-main);
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .sidebar {
            width: 220px;
            background: var(--bg-surface);
            border-right: 1px solid var(--border);
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
        }

        .sidebar-logo {
            padding: 18px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo-img {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            object-fit: cover;
        }

        .logo-text {
            font-size: 1rem;
            font-weight: 800;
            color: var(--text-main);
            letter-spacing: -0.3px;
        }

        .sidebar-section-label {
            padding: 16px 20px 6px;
            font-size: 0.65rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .sidebar-nav { padding: 8px 0 20px; }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 20px;
            color: var(--text-soft);
            text-decoration: none;
            transition: all 0.18s;
            font-size: 0.82rem;
            font-weight: 500;
            border-left: 3px solid transparent;
        }

        .nav-item:hover {
            background: var(--bg-hover);
            color: var(--text-main);
        }

        .nav-item.active {
            background: var(--primary-dim);
            color: var(--primary);
            border-left-color: var(--primary);
        }

        .nav-icon { width: 22px; height: 22px; border-radius: 6px; flex-shrink: 0; display: inline-flex; align-items: center; justify-content: center; font-size: 0.75rem; }

        .main-wrap {
            margin-left: 220px;
            flex: 1;
            min-width: 0;
        }

        .topbar {
            background: var(--bg-surface);
            border-bottom: 1px solid var(--border);
            padding: 0 24px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 40;
        }

        .topbar-left { display: flex; align-items: center; gap: 16px; }

        .topbar-search {
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--bg-elevated);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 6px 14px;
            width: 280px;
        }

        .topbar-search input {
            background: none;
            border: none;
            outline: none;
            color: var(--text-main);
            font-size: 0.8rem;
            width: 100%;
        }

        .topbar-search input::placeholder { color: var(--text-muted); }

        .breadcrumb {
            font-size: 0.78rem;
            color: var(--text-soft);
        }

        .breadcrumb span { color: var(--text-main); font-weight: 600; }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .topbar-icon-btn {
            width: 34px; height: 34px;
            border-radius: 8px;
            background: var(--bg-elevated);
            border: 1px solid var(--border);
            color: var(--text-soft);
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.18s;
        }

        .topbar-icon-btn:hover { background: var(--bg-hover); color: var(--text-main); }

        .topbar-avatar {
            width: 32px; height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--purple));
            display: flex; align-items: center; justify-content: center;
            font-size: 0.75rem; font-weight: 700; color: #fff;
            cursor: pointer;
        }

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

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .page-title h1 { font-size: 1.4rem; font-weight: 700; letter-spacing: -0.3px; }
        .page-title p { font-size: 0.78rem; color: var(--text-soft); margin-top: 4px; }

        .btn-admin {
            padding: 8px 18px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-primary-admin {
            background: linear-gradient(135deg, var(--primary), var(--purple));
            color: #fff;
            box-shadow: 0 2px 12px rgba(124,110,255,0.35);
        }

        .btn-primary-admin:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(124,110,255,0.45);
        }

        /* ── KPI CARDS ── */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .kpi-card { background: var(--bg-elevated); border: 1px solid var(--border); border-left: 3px solid var(--primary); border-radius: var(--radius-lg); padding: 20px; position: relative; overflow: hidden; transition: transform 0.2s, box-shadow 0.2s; }
        .kpi-card:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,0.3); border-color: var(--primary); }
        .kpi-card:nth-child(1) { border-left-color: #7c6eff; }
        .kpi-card:nth-child(2) { border-left-color: #f43f5e; }
        .kpi-card:nth-child(3) { border-left-color: #22c55e; }
        .kpi-card:nth-child(4) { border-left-color: #f59e0b; }
        .kpi-value { font-size: 1.8rem; font-weight: 700; color: var(--text-main); font-family: 'Inter', monospace; line-height: 1; margin-top: 8px; }
        .kpi-label { font-size: 0.68rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-soft); }
        .kpi-delta { font-size: 0.72rem; margin-top: 6px; color: var(--text-soft); }

        /* ── TABS ── */
        .tabs {
            display: flex;
            gap: 4px;
            margin-bottom: 24px;
            border-bottom: 1px solid var(--border);
        }

        .tab-btn {
            padding: 10px 18px;
            background: transparent;
            border: none;
            border-bottom: 2px solid transparent;
            margin-bottom: -1px;
            color: var(--text-soft);
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .tab-btn:hover { color: var(--text-main); }

        .tab-btn.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }

        .tab-content {
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Table */
        .table-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .search-input-wrapper {
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--bg-base);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 8px 12px;
            min-width: 250px;
        }

        .search-input-wrapper svg {
            color: var(--text-soft);
            flex-shrink: 0;
        }

        .search-input-wrapper input {
            background: none;
            border: none;
            outline: none;
            color: var(--text-main);
            font-size: 0.8rem;
            width: 100%;
        }

        .table-info {
            font-size: 0.75rem;
            color: var(--text-soft);
        }

        .table-info span {
            color: var(--primary);
            font-weight: 700;
        }

        .btn-reset {
            background: var(--bg-elevated);
            color: var(--text-soft);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 8px 16px;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-reset:hover {
            background: var(--bg-hover);
            color: var(--text-main);
            border-color: var(--primary);
        }

        .table-wrap {
            overflow-x: auto;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            background: var(--bg-surface);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            
        }

        th, td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid var(--border-soft);
            vertical-align: middle;
        }

        th {
            background: var(--bg-base);
            color: var(--text-soft);
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            cursor: pointer;
        }

        th:hover { background: var(--bg-hover); }

        .sort-icon {
            display: inline-block;
            margin-left: 6px;
            font-size: 0.7rem;
            opacity: 0.5;
        }

        .sort-icon.active { opacity: 1; color: var(--primary); }
        .sort-icon.asc::after { content: '↑'; }
        .sort-icon.desc::after { content: '↓'; }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .badge-active { background: var(--success-dim); color: var(--success); }

        .btn-view {
            background: var(--primary-dim);
            color: var(--primary);
            border: none;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 0.7rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-right: 5px;
        }

        .btn-delete {
            background: var(--danger-dim);
            color: var(--danger);
            border: none;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 0.7rem;
            cursor: pointer;
        }

        /* Messages signalés */
        .message-card {
            background: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 20px;
            margin-bottom: 16px;
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border);
        }

        .message-forum { font-size: 0.8rem; color: var(--primary); }
        .message-content { background: var(--bg-base); padding: 16px; border-radius: 12px; line-height: 1.6; margin-top: 12px; font-size: 0.85rem; }

        /* Stats */
        .charts-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 24px; }
        .chart-card { background: var(--bg-elevated); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 20px; }
        .chart-title { font-size: 0.8rem; font-weight: 600; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; color: var(--text-soft); }
        canvas { max-height: 250px; width: 100% !important; }
        .table-container { background: var(--bg-surface); border: 1px solid var(--border); border-radius: var(--radius-lg); overflow: auto; margin-bottom: 24px; }
        .table-header { padding: 16px 20px; border-bottom: 1px solid var(--border); font-weight: 600; }
        .rank-badge { display: inline-block; width: 24px; height: 24px; background: var(--bg-base); border-radius: 6px; text-align: center; line-height: 24px; font-size: 0.7rem; font-weight: 700; }
        .rank-1 { background: #fbbf24; color: #0d1117; }
        .rank-2 { background: #94a3b8; color: #0d1117; }
        .rank-3 { background: #cd7f32; color: #0d1117; }

        @media (max-width: 1024px) {
            .charts-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-wrap { margin-left: 0; }
            .kpi-grid { grid-template-columns: 1fr 1fr; }
            .tabs { flex-wrap: wrap; }
        }

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

.main-table {
    min-width: 900px;
}
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-logo">
        <img src="<?= $BASE ?>/Vue/public/images/logo.png" alt="Logo" class="logo-img">
        <span class="logo-text">Cre8Connect</span>
    </div>
    <div class="sidebar-section-label">Navigation</div>
    <div class="sidebar-nav">
        <a href="<?= $BASE ?>/Controleur/evenementC.php?action=admin" class="nav-item">
            <span class="nav-icon" style="background:rgba(245,158,11,0.15);color:#f59e0b;">📅</span> Événements
        </a>
        <a href="<?= $BASE ?>/Controleur/forumC.php?action=admin" class="nav-item active">
            <span class="nav-icon" style="background:rgba(124,110,255,0.15);color:#7c6eff;">💬</span> Forums
        </a>
    </div>
</aside>

<div class="main-wrap">
    <header class="topbar">
        <div class="breadcrumb">Dashboard / Communauté / <span>Forums</span></div>
        <div class="topbar-actions">
            <div class="topbar-user">
                <div class="topbar-avatar">AD</div>
                <span class="topbar-username">Utilisateur</span>
                <span style="color:var(--text-soft);font-size:0.7rem;">▾</span>
            </div>
        </div>
    </header>

    <div class="content">
        <div class="page-header">
            <div class="page-title">
                <h1>💬 Gestion des Forums</h1>
                <p>Supervision, modération et statistiques de la communauté</p>
            </div>
            <div>
                <a href="<?= $BASE ?>/Controleur/forumC.php?action=creer_forums_auto" class="btn-admin btn-primary-admin">🔄 Créer les forums du jour</a>
            </div>
        </div>

        <!-- Onglets -->
        <div class="tabs">
            <button class="tab-btn active" onclick="showTab('forums')">📋 Forums (<?= $total_forums ?>)</button>
            <button class="tab-btn" onclick="showTab('signales')">🚩 Messages signalés (<?= $total_signales ?>)</button>
            <button class="tab-btn" onclick="showTab('stats')">📊 Statistiques</button>
        </div>

        <!-- TAB 1: FORUMS -->
        <div id="tab-forums" class="tab-content active">
            <div class="kpi-grid">
                <div class="kpi-card"><div class="kpi-header"><span class="kpi-label">Total Forums</span></div><div class="kpi-value"><?= $total_forums ?></div><div class="kpi-delta">forums créés</div></div>
                <div class="kpi-card"><div class="kpi-header"><span class="kpi-label">Messages</span></div><div class="kpi-value"><?= $total_messages ?></div><div class="kpi-delta">total messages</div></div>
                <div class="kpi-card"><div class="kpi-header"><span class="kpi-label">Participants</span></div><div class="kpi-value"><?= $total_participants ?></div><div class="kpi-delta">membres actifs</div></div>
                <div class="kpi-card"><div class="kpi-header"><span class="kpi-label">Actifs</span></div><div class="kpi-value"><?= $forums_actifs ?></div><div class="kpi-delta">forums actifs</div></div>
            </div>

            <div class="table-toolbar">
                <div class="search-input-wrapper">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    <input type="text" id="tableSearchInput" placeholder="Rechercher..." onkeyup="filterTable()">
                </div>
                <div class="table-info"><span id="tableCount"><?= $total_forums ?></span> forums</div>
                <button class="btn-reset" onclick="resetTable()">🔄 Réinitialiser</button>
            </div>

            <div class="table-wrap">
    <table class="main-table">
                    <thead><tr><th onclick="sortTable(0)">ID <span class="sort-icon" id="sort-icon-0"></span></th><th onclick="sortTable(1)">Titre <span class="sort-icon" id="sort-icon-1"></span></th><th onclick="sortTable(2)">Événement <span class="sort-icon" id="sort-icon-2"></span></th><th onclick="sortTable(3)">Auteur <span class="sort-icon" id="sort-icon-3"></span></th><th onclick="sortTable(4)">Messages <span class="sort-icon" id="sort-icon-4"></span></th><th onclick="sortTable(5)">Date <span class="sort-icon" id="sort-icon-5"></span></th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php if (empty($forums)): ?>
                            <tr><td colspan="7" style="text-align:center; padding:40px;">Aucun forum trouvé</td></tr>
                        <?php else: ?>
                            <?php foreach ($forums as $forum): ?>
                                <tr data-id="<?= $forum['idForum'] ?>">
                                    <td><?= $forum['idForum'] ?></td>
                                    <td class="forum-title"><?= htmlspecialchars($forum['TitreForum'] ?? $forum['titreForum'] ?? 'Discussion') ?></td>
                                    <td><?= htmlspecialchars($forum['nom_evenement']) ?></td>
                                    <td><?= htmlspecialchars($forum['nom_utilisateur'] ?? 'Admin') ?></td>
                                    <td><span class="badge badge-active"><?= $forum['nb_messages'] ?? 0 ?> messages</span></td>
                                    <td><?= date('d/m/Y', strtotime($forum['dateCreation'])) ?></td>
                                    <td><a href="<?= $BASE ?>/Controleur/forumC.php?action=voir&id=<?= $forum['idForum'] ?>" class="btn-view">👁 Voir</a><button class="btn-delete" onclick="supprimerForum(<?= $forum['idForum'] ?>, '<?= htmlspecialchars(addslashes($forum['TitreForum'] ?? $forum['titreForum'])) ?>')">🗑 Suppr</button></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB 2: MESSAGES SIGNALÉS -->
        <div id="tab-signales" class="tab-content">
            <div class="kpi-grid">
                <div class="kpi-card"><div class="kpi-value"><?= $total_signales ?></div><div class="kpi-label">Total signalés</div></div>
                <div class="kpi-card"><div class="kpi-value"><?= $total_signales ?></div><div class="kpi-label">En attente</div></div>
                <div class="kpi-card"><div class="kpi-value">0</div><div class="kpi-label">Supprimés</div></div>
            </div>

            <?php if (empty($messages_signales)): ?>
                <div style="text-align:center; padding:60px; background:var(--bg-surface); border-radius:var(--radius-lg); border:1px solid var(--border);">
                    <div style="font-size:3rem; margin-bottom:16px;">✅</div>
                    <h3>Aucun message signalé</h3>
                    <p>Tous les messages sont conformes</p>
                </div>
            <?php else: ?>
                <?php foreach ($messages_signales as $msg): ?>
                <div class="message-card">
                    <div class="message-header">
                        <div class="message-forum">📌 Forum : <?= htmlspecialchars($msg['titreForum']) ?></div>
                        <button class="btn-delete" onclick="supprimerMessage(<?= $msg['idMessage'] ?>)">🗑 Supprimer</button>
                    </div>
                    <div style="margin-bottom:8px;">👤 <?= htmlspecialchars($msg['nom_utilisateur']) ?> • 📅 <?= date('d/m/Y H:i', strtotime($msg['dateMessage'])) ?></div>
                    <div class="message-content"><?= nl2br(htmlspecialchars($msg['message'])) ?></div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- TAB 3: STATISTIQUES -->
        <div id="tab-stats" class="tab-content">
            <div class="kpi-grid">
                <div class="kpi-card"><div class="kpi-value"><?= $stats['total_forums'] ?? 0 ?></div><div class="kpi-label">Total Forums</div></div>
                <div class="kpi-card"><div class="kpi-value"><?= $stats['total_messages'] ?? 0 ?></div><div class="kpi-label">Total Messages</div></div>
                <div class="kpi-card"><div class="kpi-value"><?= $stats['forums_actifs'] ?? 0 ?></div><div class="kpi-label">Forums Actifs (7j)</div></div>
                <div class="kpi-card"><div class="kpi-value"><?= $stats['messages_signales'] ?? 0 ?></div><div class="kpi-label">Messages Signalés</div></div>
            </div>

            <div class="charts-grid">
                <div class="chart-card">
                    <div class="chart-title">📈 Évolution des messages (6 mois)</div>
                    <canvas id="messagesChart"></canvas>
                </div>
                <div class="table-container">
                    <div class="table-header">🏆 Top 5 forums</div>
                    <div style="overflow-x: auto;">   <!-- ADD THIS WRAPPER -->
                    <table style="min-width: 400px;">  <!-- ADD min-width -->
                    <thead><tr><th>#</th><th>Forum</th><th>Messages</th><th>Activité</th></tr></thead>
                    <?php foreach (($stats['top_forums'] ?? []) as $index => $forum): ?>
                    <tr><td><span class="rank-badge <?= $index == 0 ? 'rank-1' : ($index == 1 ? 'rank-2' : ($index == 2 ? 'rank-3' : '')) ?>"><?= $index + 1 ?></span></td><td><strong><?= htmlspecialchars($forum['TitreForum'] ?? 'Forum') ?></strong></td><td><?= $forum['nb_messages'] ?? 0 ?></td><td><span class="badge badge-active"><?= ($forum['nb_messages'] ?? 0) > 10 ? '🔥 Très actif' : (($forum['nb_messages'] ?? 0) > 0 ? '💬 Actif' : '🕰️ Calme') ?></span></td></tr>
                    <?php endforeach; ?>
                    </tbody></table>
                </div>
            </div>

            
        </div>
        <div class="table-container">
                <div class="table-header">👥 Top contributeurs</div>
                <table><thead><tr><th>#</th><th>Contributeur</th><th>Messages</th><th>Impact</th></tr></thead><tbody>
                <?php foreach (($stats['top_contributeurs'] ?? []) as $index => $user): ?>
                <tr><td><span class="rank-badge <?= $index == 0 ? 'rank-1' : ($index == 1 ? 'rank-2' : ($index == 2 ? 'rank-3' : '')) ?>"><?= $index + 1 ?></span></td><td><strong><?= htmlspecialchars($user['nom'] ?? 'Anonyme') ?></strong></td><td><?= $user['nb_messages'] ?? 0 ?></td><td><span class="badge badge-active"><?= ($user['nb_messages'] ?? 0) > 20 ? '🏆 Expert' : (($user['nb_messages'] ?? 0) > 5 ? '📝 Actif' : '🌱 Débutant') ?></span></td></tr>
                <?php endforeach; ?>
                </tbody></table>
            </div>
    </div>
</div>

<script>
    let sortColumn = 0, sortDirection = 'asc', originalRows = [];

    function showTab(tabName) {
        document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.getElementById(`tab-${tabName}`).classList.add('active');
        event.target.classList.add('active');
    }

    function saveOriginalOrder() {
        const rows = document.querySelectorAll('#tab-forums .table-wrap table tbody tr');
        originalRows = Array.from(rows).map(r => r.outerHTML);
    }

    function resetTable() {
        const tbody = document.querySelector('#tab-forums .table-wrap table tbody');
        if (originalRows.length) tbody.innerHTML = originalRows.join('');
        document.querySelectorAll('#tab-forums .sort-icon').forEach(i => { i.classList.remove('active', 'asc', 'desc'); i.textContent = ''; });
        sortColumn = 0; sortDirection = 'asc';
        const search = document.getElementById('tableSearchInput');
        if (search) { search.value = ''; filterTable(); }
        else document.getElementById('tableCount').textContent = document.querySelectorAll('#tab-forums .table-wrap table tbody tr').length;
    }

    function filterTable() {
        const filter = document.getElementById('tableSearchInput').value.toLowerCase();
        const rows = document.querySelectorAll('#tab-forums .table-wrap table tbody tr');
        let visible = 0;
        rows.forEach(row => {
            if (row.innerText.toLowerCase().includes(filter)) { row.style.display = ''; visible++; }
            else row.style.display = 'none';
        });
        document.getElementById('tableCount').textContent = visible;
    }

    function sortTable(col) {
        const tbody = document.querySelector('#tab-forums .table-wrap table tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        document.querySelectorAll('#tab-forums .sort-icon').forEach(i => { i.classList.remove('active', 'asc', 'desc'); i.textContent = ''; });
        if (sortColumn === col) sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
        else { sortColumn = col; sortDirection = 'asc'; }
        const icon = document.getElementById(`sort-icon-${col}`);
        icon.classList.add('active', sortDirection);
        icon.textContent = sortDirection === 'asc' ? '↑' : '↓';
        rows.sort((a, b) => {
            let aVal = a.cells[col]?.innerText.trim() || '', bVal = b.cells[col]?.innerText.trim() || '';
            if (col === 0) { aVal = parseInt(aVal); bVal = parseInt(bVal); return sortDirection === 'asc' ? aVal - bVal : bVal - aVal; }
            if (col === 4) { const am = aVal.match(/(\d+)/); const bm = bVal.match(/(\d+)/); if (am && bm) { aVal = parseInt(am[0]); bVal = parseInt(bm[0]); return sortDirection === 'asc' ? aVal - bVal : bVal - aVal; } }
            if (col === 5) { const months = { Jan:0, Feb:1, Mar:2, Apr:3, May:4, Jun:5, Jul:6, Aug:7, Sep:8, Oct:9, Nov:10, Dec:11 }; const pa = aVal.split('/'); const pb = bVal.split('/'); if (pa.length===3 && pb.length===3) { aVal = new Date(pa[2], months[pa[1]], pa[0]); bVal = new Date(pb[2], months[pb[1]], pb[0]); return sortDirection === 'asc' ? aVal - bVal : bVal - aVal; } }
            aVal = aVal.toLowerCase(); bVal = bVal.toLowerCase();
            if (aVal < bVal) return sortDirection === 'asc' ? -1 : 1;
            if (aVal > bVal) return sortDirection === 'asc' ? 1 : -1;
            return 0;
        });
        rows.forEach(r => tbody.appendChild(r));
    }

    function supprimerForum(id, titre) {
        if (confirm(`Supprimer le forum "${titre}" ?`)) window.location.href = `<?= $BASE ?>/Controleur/forumC.php?action=supprimer_forum&id=${id}`;
    }

    function supprimerMessage(id) {
        if (confirm('Supprimer ce message signalé ?')) window.location.href = `<?= $BASE ?>/Controleur/forumC.php?action=supprimer_message&id=${id}`;
    }

    

    new Chart(document.getElementById('messagesChart'), {
        type: 'line',
        data: { labels: <?= json_encode($stats['months_labels'] ?? []) ?>, datasets: [{ label: 'Messages', data: <?= json_encode($stats['messages_data'] ?? []) ?>, borderColor: '#58a6ff', backgroundColor: 'rgba(88,166,255,0.1)', tension: 0.3, fill: true }] },
        options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { labels: { color: '#e6edf3' } } }, scales: { y: { ticks: { color: '#8b949e' }, grid: { color: '#30363d' } }, x: { ticks: { color: '#8b949e' }, grid: { color: '#30363d' } } } }
    });

    document.addEventListener('DOMContentLoaded', function() { initTheme(); document.getElementById('themeToggle').addEventListener('click', toggleTheme); setTimeout(saveOriginalOrder, 200); });
</script>
</body>
</html>
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../layout/early-theme.php';
require_once __DIR__ . '/../../../Controleur/condidatureC.php';

$candidatureController = new CondidatureC();
$sessionUser = $_SESSION['utilisateur'] ?? [];

if (!isset($sessionUser['id']) || (($sessionUser['role'] ?? '') !== 'admin')) {
    $defaultAdmin = $candidatureController->getDefaultUserByRole('admin');
    if ($defaultAdmin) {
        $_SESSION['utilisateur'] = [
            'id' => (int) $defaultAdmin['id'],
            'role' => 'admin',
            'nom' => $defaultAdmin['nom'],
            'email' => $defaultAdmin['email'],
        ];
        $sessionUser = $_SESSION['utilisateur'];
    }
}

$backActive = 'forum';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php cre8_bo_early_theme_print_head_script(); ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Back Office — Forum · Cre8Connect</title>
    <link rel="stylesheet" href="../css/backoffice.css">
    <link rel="stylesheet" href="../layout/back-layout.css?v=<?php echo htmlspecialchars((string) filemtime(__DIR__ . '/../layout/back-layout.css'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="../utilisateur/assets/vendors/mdi/css/materialdesignicons.min.css?v=<?php echo htmlspecialchars((string) (@filemtime(__DIR__ . '/../utilisateur/assets/vendors/mdi/css/materialdesignicons.min.css') ?: 0), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="../css/new_style_backoffice.css">
    <link rel="icon" type="image/png" sizes="32x32" href="../../public/images/logo.png">
    <link rel="shortcut icon" type="image/png" href="../../public/images/logo.png">
</head>
<body class="cre8-admin-layout"><?php cre8_bo_early_theme_print_body_script(); ?>
    <div class="container-scroller cre8-admin-page">
        <?php require_once dirname(__DIR__) . '/layout/sidebar.php'; ?>
        <main class="page-body-wrapper cre8-admin-main">
            <?php require_once dirname(__DIR__) . '/layout/header.php'; ?>
            <div class="main-panel">
                <div class="content-wrapper admin-shell">
                    <header class="admin-header card grid-margin">
                        <div class="admin-header-main card-body">
                            <div>
                                <h1>Forum</h1>
                                <p>Community forum administration will appear here when the module is connected.</p>
                            </div>
                        </div>
                    </header>
                    <div class="card grid-margin">
                        <div class="card-body">
                            <p class="text-muted mb-0">No forum data to display yet. This page is ready for future threads and moderation tools.</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script src="../layout/back-layout.js?v=<?php echo htmlspecialchars((string) filemtime(__DIR__ . '/../layout/back-layout.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
</body>
</html>
