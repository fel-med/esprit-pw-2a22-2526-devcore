<?php
// This file can work both directly AND through the controller

// Initialisation par défaut
$totalEvents = 0;
$totalInscrits = 0;
$pendingEvents = 0;
$activeEvents = 0;
$avgCapacity = 0;
$upcomingEvents = 0;
$kpi_total = 0;
$kpi_inscrits = 0;
$kpi_actifs = 0;
$kpi_upcoming = 0;
$kpi_taux = 0;
$topEvents = [];
$types = [];
$months_labels = [];
$events_data = [];
$participants_data = [];

// If accessed directly (for development/testing), fetch events from database
if (!isset($evenements)) {
    // Include config and fetch events directly
    require_once __DIR__ . '/../../../config.php';
    require_once __DIR__ . '/../../../Modele/evenement.php';
    
    try {
        $pdo = config::getConnexion();
        $stmt = $pdo->query("SELECT * FROM evenement ORDER BY idFormation DESC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Hydrate events
        $evenements = [];
        foreach ($rows as $row) {
            $evenements[] = new Evenement(
                (int)($row['idFormation'] ?? 0),
                $row['TitreFormation'] ?? '',
                $row['description'] ?? '',
                $row['type'] ?? '',
                $row['statut'] ?? '',
                $row['lieu'] ?? '',
                $row['DateFormation'] ?? '',
                (int)($row['capacite'] ?? 0),
                (int)($row['nb_inscrits'] ?? 0),
                (int)($row['Duree'] ?? 0),
                $row['created_at'] ?? '',
                $row['image'] ?? null,
                $row['adresse_complete'] ?? null
            );
        }
    } catch (Exception $e) {
        $evenements = [];
    }
}

// ========== CALCUL DES STATISTIQUES ==========
try {
    // Récupérer la connexion PDO
    require_once __DIR__ . '/../../../config.php';
    $pdo = config::getConnexion();
    
    $kpi_total = (int)$pdo->query("SELECT COUNT(*) FROM evenement")->fetchColumn();
    $kpi_inscrits = (int)$pdo->query("SELECT SUM(nb_inscrits) FROM evenement")->fetchColumn();
    $kpi_actifs = (int)$pdo->query("SELECT COUNT(*) FROM evenement WHERE statut = 'actif'")->fetchColumn();
    $kpi_upcoming = (int)$pdo->query("SELECT COUNT(*) FROM evenement WHERE DateFormation > CURDATE()")->fetchColumn();
    $avg = $pdo->query("SELECT AVG((nb_inscrits / capacite) * 100) FROM evenement WHERE capacite > 0")->fetchColumn();
    $kpi_taux = round($avg ?: 0);
    
    $pendingEvents = (int)$pdo->query("SELECT COUNT(*) FROM evenement WHERE statut = 'en_attente'")->fetchColumn();
    $activeEvents = $kpi_actifs;
    $totalEvents = $kpi_total;
    $totalInscrits = $kpi_inscrits ?: 0;
    $avgCapacity = $kpi_taux;
    $upcomingEvents = $kpi_upcoming;
    
    // Évolution par mois
    $months = [];
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $months[$month] = ['events' => 0, 'participants' => 0];
    }
    
    $stmtMonths = $pdo->query("
        SELECT DATE_FORMAT(DateFormation, '%Y-%m') as mois, 
               COUNT(*) as nb_events, 
               SUM(nb_inscrits) as nb_participants
        FROM evenement
        WHERE DateFormation > DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(DateFormation, '%Y-%m')
        ORDER BY mois ASC
    ");
    while ($row = $stmtMonths->fetch(PDO::FETCH_ASSOC)) {
        if (isset($months[$row['mois']])) {
            $months[$row['mois']]['events'] = (int)$row['nb_events'];
            $months[$row['mois']]['participants'] = (int)$row['nb_participants'];
        }
    }
    $months_labels = array_keys($months);
    $events_data = array_column($months, 'events');
    $participants_data = array_column($months, 'participants');
    
    // Top 5 événements
    $topEvents = [];
    $stmtTop = $pdo->query("
        SELECT TitreFormation as titre, type, nb_inscrits as participants, capacite,
               ROUND((nb_inscrits / capacite) * 100, 1) as taux
        FROM evenement
        WHERE capacite > 0
        ORDER BY nb_inscrits DESC
        LIMIT 5
    ");
    $topEvents = $stmtTop->fetchAll(PDO::FETCH_ASSOC);
    
    // Répartition par type
    $types = [];
    $stmtTypes = $pdo->query("
        SELECT type, COUNT(*) as count, SUM(nb_inscrits) as participants
        FROM evenement
        GROUP BY type
    ");
    while ($row = $stmtTypes->fetch(PDO::FETCH_ASSOC)) {
        $types[$row['type']] = ['count' => (int)$row['count'], 'participants' => (int)$row['participants']];
    }
    
} catch (Exception $e) {
    error_log("Erreur stats: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Gestion Événements – Admin Cre8Connect</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg-base:      #0b0e1a;
            --bg-surface:   #0f1117;
            --bg-elevated:  #161b2e;
            --bg-hover:     #1e2235;
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
            --radius:       12px;
            --radius-lg:    16px;
            --kpi-1-from: #6c47ff; --kpi-1-to: #9b6dff;
            --kpi-2-from: #e8305a; --kpi-2-to: #ff6b9d;
            --kpi-3-from: #22c55e; --kpi-3-to: #4ade80;
            --kpi-4-from: #7c3aed; --kpi-4-to: #a855f7;
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
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-reset:hover {
    background: var(--bg-hover);
    color: var(--text-main);
    border-color: var(--primary);
}

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-base);
            color: var(--text-main);
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
        }

        body.dark-mode {
            --bg-base: #0d1117;
            --bg-surface: #161b22;
        }

        .sidebar {
            width: 220px;
            flex-shrink: 0;
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

        .nav-item:hover { background: var(--bg-hover); color: var(--text-main); }
        .nav-item.active { background: var(--primary-dim); color: var(--primary); border-left-color: var(--primary); }

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

        .topbar-search {
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--bg-elevated);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 6px 12px;
        }

        .topbar-search input {
            background: none;
            border: none;
            outline: none;
            color: var(--text-main);
            width: 180px;
            font-size: 0.8rem;
        }

        .topbar-icon-btn {
            width: 34px; height: 34px;
            border-radius: 8px;
            background: var(--bg-elevated);
            border: 1px solid var(--border);
            color: var(--text-soft);
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; font-size: 0.9rem; transition: all 0.18s;
        }

        .topbar-icon-btn:hover { background: var(--bg-hover); color: var(--text-main); }

        .topbar-avatar {
            width: 32px; height: 32px; border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--purple));
            display: flex; align-items: center; justify-content: center;
            font-size: 0.75rem; font-weight: 700; color: #fff; cursor: pointer;
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
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .page-title h1 { font-size: 1.3rem; font-weight: 700; }
        .page-title p { font-size: 0.8rem; color: var(--text-soft); margin-top: 4px; }

        .btn-admin {
            padding: 8px 18px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
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

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .kpi-card {
            background: var(--bg-elevated);
            border: 1px solid var(--border);
            border-left: 3px solid var(--primary);
            border-radius: var(--radius-lg);
            padding: 20px;
            position: relative;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .kpi-card:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,0.3); }
        .kpi-card:nth-child(1) { border-left-color: #7c6eff; }
        .kpi-card:nth-child(2) { border-left-color: #f43f5e; }
        .kpi-card:nth-child(3) { border-left-color: #22c55e; }
        .kpi-card:nth-child(4) { border-left-color: #f59e0b; }

        .kpi-header { margin-bottom: 8px; }
        .kpi-label { font-size: 0.68rem; font-weight: 600; color: var(--text-soft); text-transform: uppercase; letter-spacing: 0.5px; }
        .kpi-value { font-size: 1.8rem; font-weight: 700; color: var(--text-main); font-family: 'Inter', monospace; line-height: 1; margin-top: 8px; }
        .kpi-delta { font-size: 0.72rem; margin-top: 6px; color: var(--text-soft); }
        .kpi-delta.up { color: var(--success); }
        .kpi-delta.down { color: var(--danger); }

        .stats-section {
            background: var(--bg-elevated);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            margin-bottom: 24px;
            overflow: hidden;
        }

        .stats-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .stats-header h3 { font-size: 0.9rem; }

        .charts-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            padding: 20px;
        }

        .chart-card {
            background: var(--bg-base);
            border-radius: var(--radius);
            padding: 16px;
            border: 1px solid var(--border);
        }

        .chart-title {
            font-size: 0.75rem;
            color: var(--text-soft);
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        canvas { max-height: 250px; width: 100% !important; }

        .top-table {
            padding: 0 20px 20px 20px;
        }

        .top-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .top-table th {
            text-align: left;
            padding: 10px 8px;
            color: var(--text-soft);
            font-size: 0.7rem;
            font-weight: 600;
            border-bottom: 1px solid var(--border);
        }

        .top-table td {
            padding: 10px 8px;
            font-size: 0.8rem;
            border-bottom: 1px solid var(--border-soft);
        }

        .rank-badge {
            display: inline-block;
            width: 24px;
            height: 24px;
            background: var(--bg-base);
            border-radius: 6px;
            text-align: center;
            line-height: 24px;
            font-size: 0.7rem;
            font-weight: 700;
        }

        .rank-1 { background: #fbbf24; color: #0d1117; }
        .rank-2 { background: #94a3b8; color: #0d1117; }
        .rank-3 { background: #cd7f32; color: #0d1117; }

        .type-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.65rem;
            font-weight: 600;
        }
        .type-formation { background: #23863620; color: #3fb950; }
        .type-webinaire { background: #d2992220; color: #d29922; }
        .type-meetup { background: #da363320; color: #f85149; }
        .type-atelier { background: #bc8cff20; color: #bc8cff; }

        /* Table principale */
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

        .search-input-wrapper input::placeholder {
            color: var(--text-muted);
        }

        .table-info {
            font-size: 0.75rem;
            color: var(--text-soft);
        }

        .table-info span {
            color: var(--primary);
            font-weight: 700;
        }

        .table-wrap {
            overflow-x: auto;
            margin-top: 24px;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            background: var(--bg-surface);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
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
            user-select: none;
            transition: background 0.2s;
        }

        th:hover {
            background: var(--bg-hover);
        }

        .sort-icon {
            display: inline-block;
            margin-left: 6px;
            font-size: 0.7rem;
            opacity: 0.5;
            transition: opacity 0.2s;
        }

        th:hover .sort-icon {
            opacity: 1;
        }

        .sort-icon.asc::after {
            content: '↑';
        }

        .sort-icon.desc::after {
            content: '↓';
        }

        .sort-icon.active {
            opacity: 1;
            color: var(--primary);
        }

        td {
            font-size: 0.8rem;
            color: var(--text-main);
        }

        tbody tr:hover {
            background: var(--bg-elevated);
        }

        .event-name {
            font-weight: 600;
            color: var(--text-main);
        }

        .progress-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 80px;
        }

        .progress-bar {
            flex: 1;
            height: 5px;
            background: var(--bg-elevated);
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--purple));
            border-radius: 10px;
            width: 0%;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .badge-active { background: var(--success-dim); color: var(--success); }
        .badge-en_attente { background: var(--warning-dim); color: var(--warning); }
        .badge-brouillon { background: var(--purple-dim); color: var(--purple); }

        .type-chip {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 0.7rem;
            background: var(--primary-dim);
            color: var(--primary);
            white-space: nowrap;
        }

        .action-btn {
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 0.7rem;
            cursor: pointer;
            border: none;
            margin-right: 5px;
            white-space: nowrap;
        }

        .action-edit { background: var(--primary-dim); color: var(--primary); }
        .action-delete { background: var(--danger-dim); color: var(--danger); }

        .alert-banner {
            background: var(--warning-dim);
            border: 1px solid rgba(210,153,34,0.3);
            border-radius: var(--radius);
            padding: 12px 16px;
            margin-bottom: 20px;
            color: var(--warning);
            font-size: 0.8rem;
        }

        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(4px);
            z-index: 200;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.open { display: flex; }

        .modal {
            background: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            width: 600px;
            max-width: 95%;
            max-height: 85vh;
            overflow-y: auto;
        }

        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            background: var(--bg-surface);
        }

        .modal-header h2 { font-size: 1.2rem; font-weight: 600; }

        .modal-close {
            background: none;
            border: none;
            color: var(--text-soft);
            font-size: 24px;
            cursor: pointer;
        }

        .modal-body { padding: 24px; }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .form-group { margin-bottom: 16px; }

        .form-label {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-soft);
            margin-bottom: 6px;
        }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            background: var(--bg-base);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text-main);
            font-size: 0.85rem;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 80px;
        }

        .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            position: sticky;
            bottom: 0;
            background: var(--bg-surface);
        }

        .image-preview {
            margin-top: 10px;
            max-width: 100px;
            border-radius: 8px;
            overflow: hidden;
        }

        .image-preview img { width: 100%; height: auto; }

        .current-image {
            margin-top: 10px;
            padding: 8px;
            background: var(--bg-elevated);
            border-radius: 8px;
            font-size: 0.75rem;
        }

        @media (max-width: 1024px) {
            .charts-grid { grid-template-columns: 1fr; }
            .kpi-grid { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-wrap { margin-left: 0; }
            .kpi-grid { grid-template-columns: 1fr; }
            .form-row { grid-template-columns: 1fr; }
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
    </style>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events - Cre8Connect</title>
    <link rel="stylesheet" href="../css/backoffice.css">
<link rel="icon" type="image/png" sizes="32x32" href="../../public/images/logo.png">
<link rel="shortcut icon" type="image/png" href="../../public/images/logo.png">
<link rel="apple-touch-icon" href="../../public/images/logo.png">
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-logo">
        <img src="<?= $BASE ?>/Vue/public/images/logo.png" alt="Logo" class="logo-img">
        <span class="logo-text">Cre8Connect</span>
    </div>
    <div class="sidebar-section-label">Navigation</div>
    <div class="sidebar-nav">
        <a href="<?= $BASE ?>/Controleur/evenementC.php?action=admin" class="nav-item active">
            <span class="nav-icon" style="background:rgba(245,158,11,0.15);color:#f59e0b;">📅</span> Événements
        </a>
        <a href="<?= $BASE ?>/Controleur/forumC.php?action=admin" class="nav-item">
            <span class="nav-icon" style="background:rgba(124,110,255,0.15);color:#7c6eff;">💬</span> Forums
        </a>
    </div>
</aside>

<div class="main-wrap">
    <header class="topbar">
        <div class="breadcrumb">Dashboard / Communauté / <span>Événements</span></div>
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
                <h1>Gestion des Événements</h1>
                <p>Supervision, modération et administration de tous les événements</p>
            </div>
            <div class="page-actions">
                <button class="btn-admin btn-primary-admin" onclick="openModal()">+ Nouvel événement</button>
            </div>
        </div>

        <?php if ($pendingEvents > 0): ?>
        <div class="alert-banner">⚠️ <strong><?= $pendingEvents ?> événement(s)</strong> en attente de validation</div>
        <?php endif; ?>

        <!-- KPI row -->
        <div class="kpi-grid">
            <div class="kpi-card"><div class="kpi-header"><span class="kpi-label">Total Événements</span></div><div class="kpi-value"><?= $kpi_total ?></div><div class="kpi-delta up">↑ <?= $kpi_actifs ?> actifs</div></div>
            <div class="kpi-card"><div class="kpi-header"><span class="kpi-label">Inscriptions</span></div><div class="kpi-value"><?= $kpi_inscrits ?></div><div class="kpi-delta up">↑ total participants</div></div>
            <div class="kpi-card"><div class="kpi-header"><span class="kpi-label">Taux remplissage</span></div><div class="kpi-value"><?= $kpi_taux ?>%</div><div class="kpi-delta <?= $kpi_taux > 50 ? 'up' : 'down' ?>">moyenne générale</div></div>
            <div class="kpi-card"><div class="kpi-header"><span class="kpi-label">À venir</span></div><div class="kpi-value"><?= $kpi_upcoming ?></div><div class="kpi-delta up">prochains événements</div></div>
        </div>

        <!-- SECTION STATISTIQUES -->
        <div class="stats-section">
            <div class="stats-header"><span>📊</span><h3>Tableau de bord analytique</h3></div>
            <div class="charts-grid">
                <div class="chart-card"><div class="chart-title">📈 Évolution des participants (6 mois)</div><canvas id="participantsChart"></canvas></div>
                <div class="chart-card"><div class="chart-title">📊 Événements par mois</div><canvas id="eventsChart"></canvas></div>
            </div>
            <div class="charts-grid">
                <div class="chart-card"><div class="chart-title">🥧 Répartition par type</div><canvas id="typeChart"></canvas></div>
                <div class="top-table">
                    <div class="chart-title" style="margin-bottom: 12px;">🏆 Top 5 événements</div>
                    <table>
                        <thead> <tr><th>#</th><th>Événement</th><th>Type</th><th>Participants</th><th>Taux</th></tr> </thead>
                        <tbody>
                            <?php foreach ($topEvents as $index => $event): ?>
                            <tr>
                                <td><span class="rank-badge <?= $index == 0 ? 'rank-1' : ($index == 1 ? 'rank-2' : ($index == 2 ? 'rank-3' : '')) ?>"><?= $index + 1 ?></span></td>
                                <td><strong><?= htmlspecialchars(substr($event['titre'], 0, 30)) ?>...</strong></td>
                                <td><span class="type-badge type-<?= $event['type'] ?>"><?= ucfirst($event['type']) ?></span></td>
                                <td><?= $event['participants'] ?> / <?= $event['capacite'] ?></td>
                                <td><span class="badge <?= $event['taux'] > 70 ? 'badge-active' : ($event['taux'] > 30 ? 'badge-en_attente' : 'badge-brouillon') ?>"><?= $event['taux'] ?>%</span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Toolbar de recherche -->
        <div class="table-toolbar">
            <div class="search-input-wrapper">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <input type="text" id="tableSearchInput" placeholder="Rechercher dans le tableau..." onkeyup="filterTable()">
            </div>
            <div class="table-info">
                <span id="tableCount"><?= count($evenements) ?></span> événements affichés
            </div>
            <button id="resetTableBtn" class="btn-reset" onclick="resetTable()">
        🔄 Réinitialiser
    </button>
        </div>

        <!-- Table principale -->
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th><input type="checkbox"/></th>
                        <th>Image</th>
                        <th onclick="sortTable(2)">Événement <span class="sort-icon" id="sort-icon-2"></span></th>
                        <th onclick="sortTable(3)">Type <span class="sort-icon" id="sort-icon-3"></span></th>
                        <th onclick="sortTable(4)">Statut <span class="sort-icon" id="sort-icon-4"></span></th>
                        <th onclick="sortTable(5)">Date <span class="sort-icon" id="sort-icon-5"></span></th>
                        <th onclick="sortTable(6)">Lieu <span class="sort-icon" id="sort-icon-6"></span></th>
                        <th onclick="sortTable(7)">Inscriptions <span class="sort-icon" id="sort-icon-7"></span></th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($evenements)): ?>
                        <tr><td colspan="9" style="text-align:center; padding:40px;">Aucun événement trouvé</td></tr>
                    <?php else: ?>
                        <?php foreach ($evenements as $event): ?>
                            <?php $percentage = ($event->getCapacite() > 0) ? ($event->getNbInscrits() / $event->getCapacite()) * 100 : 0; ?>
                            <tr>
                                <td><input type="checkbox"/></td>
                                <td><?php if ($event->getImage()): ?><img src="<?= $BASE ?>/<?= $event->getImage() ?>" style="width: 40px; height: 40px; object-fit: cover; border-radius: 8px;"><?php else: ?><div style="width: 40px; height: 40px; background: var(--bg-elevated); border-radius: 8px; display: flex; align-items: center; justify-content: center;">🎯</div><?php endif; ?></td>
                                <td class="event-name"><?= htmlspecialchars($event->getTitre()) ?></td>
                                <td><span class="type-chip"><?= ucfirst($event->getType()) ?></span></td>
                                <td><span class="badge badge-<?= $event->getStatut() ?>"><?= $event->getStatut() ?></span></td>
                                <td><?= date('d M Y', strtotime($event->getDateEvenement())) ?></td>
                                <td><?= htmlspecialchars($event->getLieu() ?: 'En ligne') ?></td>
                                <td><div class="progress-wrap"><div class="progress-bar"><div class="progress-fill" style="width: <?= min(100, $percentage) ?>%"></div></div><span><?= $event->getNbInscrits() ?>/<?= $event->getCapacite() ?></span></div></td>
                                <td><button class="action-btn action-edit" onclick="editEvent(<?= $event->getId() ?>)">✏ Éditer</button><button class="action-btn action-delete" onclick="deleteEvent(<?= $event->getId() ?>, '<?= htmlspecialchars(addslashes($event->getTitre())) ?>')">🗑 Suppr</button></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="modal-overlay" id="createModal">
    <div class="modal">
        <form method="POST" action="<?= $BASE ?>/Controleur/evenementC.php?action=create" enctype="multipart/form-data">
            <div class="modal-header"><h2>➕ Nouvel Événement</h2><button type="button" class="modal-close" onclick="closeModal()">✕</button></div>
            <div class="modal-body">
                <div class="form-group"><label class="form-label">Titre *</label><input type="text" name="titre" class="form-control" required/></div>
                <div class="form-row"><div class="form-group"><label class="form-label">Type *</label><select name="type" class="form-control"><option value="formation">Formation</option><option value="webinaire">Webinaire</option><option value="meetup">Meetup</option><option value="atelier">Atelier</option></select></div><div class="form-group"><label class="form-label">Statut *</label><select name="statut" class="form-control"><option value="brouillon">Brouillon</option><option value="en_attente">En attente</option><option value="actif">Actif</option></select></div></div>
                <div class="form-group"><label class="form-label">Description</label><textarea name="description" class="form-control"></textarea></div>
                <div class="form-row"><div class="form-group"><label class="form-label">Date *</label><input type="date" name="date_evenement" class="form-control" required/></div><div class="form-group"><label class="form-label">Durée (heures)</label><input type="number" name="duree" class="form-control"/></div></div>
                <div class="form-row"><div class="form-group"><label class="form-label">Lieu (ville)</label><input type="text" name="lieu" class="form-control"/></div><div class="form-group"><label class="form-label">Capacité</label><input type="number" name="capacite" class="form-control" value="50"/></div></div>
                <div class="form-group"><label class="form-label">📍 Adresse complète (carte)</label><input type="text" name="adresse_complete" class="form-control" placeholder="Ex: 45 Avenue Ahmed Tlili, Ariana"><small style="color: var(--text-muted); font-size: .7rem;">Entrez l'adresse pour afficher la carte</small></div>
                <div class="form-group"><label class="form-label">Affiche</label><input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/jpg,image/webp" onchange="previewImage(this, 'createPreview')"/><div id="createPreview" class="image-preview"></div><small>JPG, PNG, WebP (max 2MB)</small></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn-admin btn-ghost" onclick="closeModal()">Annuler</button><button type="submit" class="btn-admin btn-primary-admin">Créer</button></div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal">
        <form method="POST" id="editForm" enctype="multipart/form-data">
            <div class="modal-header"><h2>✏ Modifier</h2><button type="button" class="modal-close" onclick="closeEditModal()">✕</button></div>
            <div class="modal-body">
                <input type="hidden" name="id" id="edit_id"/>
                <div class="form-group"><label class="form-label">Titre</label><input type="text" name="titre" id="edit_titre" class="form-control" required/></div>
                <div class="form-row"><div class="form-group"><label class="form-label">Type</label><select name="type" id="edit_type" class="form-control"><option value="formation">Formation</option><option value="webinaire">Webinaire</option><option value="meetup">Meetup</option><option value="atelier">Atelier</option></select></div><div class="form-group"><label class="form-label">Statut</label><select name="statut" id="edit_statut" class="form-control"><option value="brouillon">Brouillon</option><option value="en_attente">En attente</option><option value="actif">Actif</option></select></div></div>
                <div class="form-group"><label class="form-label">Description</label><textarea name="description" id="edit_description" class="form-control"></textarea></div>
                <div class="form-row"><div class="form-group"><label class="form-label">Date</label><input type="date" name="date_evenement" id="edit_date" class="form-control" required/></div><div class="form-group"><label class="form-label">Durée</label><input type="number" name="duree" id="edit_duree" class="form-control"/></div></div>
                <div class="form-row"><div class="form-group"><label class="form-label">Lieu</label><input type="text" name="lieu" id="edit_lieu" class="form-control"/></div><div class="form-group"><label class="form-label">Capacité</label><input type="number" name="capacite" id="edit_capacite" class="form-control"/></div></div>
                <div class="form-group"><label class="form-label">📍 Adresse complète</label><input type="text" name="adresse_complete" id="edit_adresse" class="form-control"/></div>
                <div class="form-group"><label class="form-label">Affiche</label><input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/jpg,image/webp" onchange="previewImage(this, 'editPreview')"/><div id="editPreview" class="image-preview"></div><div id="currentImageInfo" class="current-image"></div><small>Laissez vide pour garder l'image actuelle</small></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn-admin btn-ghost" onclick="closeEditModal()">Annuler</button><button type="submit" class="btn-admin btn-primary-admin">Mettre à jour</button></div>
        </form>
    </div>
</div>

<script>
    let sortColumn = 2;
    let sortDirection = 'asc';
    let originalRows = [];

    // ========== SAUVEGARDE ET RÉINITIALISATION ==========
    function saveOriginalOrder() {
        const table = document.querySelector('.table-wrap table');
        if (!table) return;
        const tbody = table.querySelector('tbody');
        if (!tbody) return;
        const rows = Array.from(tbody.querySelectorAll('tr'));
        
        originalRows = [];
        rows.forEach(row => {
            originalRows.push(row.outerHTML);
        });
        console.log('Ordre original sauvegardé, ' + originalRows.length + ' lignes');
    }

    function resetTable() {
        const table = document.querySelector('.table-wrap table');
        if (!table) return;
        const tbody = table.querySelector('tbody');
        if (!tbody) return;
        
        if (originalRows.length === 0) {
            saveOriginalOrder();
            return;
        }
        
        tbody.innerHTML = '';
        originalRows.forEach(rowHtml => {
            tbody.insertAdjacentHTML('beforeend', rowHtml);
        });
        
        document.querySelectorAll('.sort-icon').forEach(icon => {
            icon.classList.remove('active', 'asc', 'desc');
            icon.textContent = '';
        });
        
        sortColumn = 2;
        sortDirection = 'asc';
        
        const searchInput = document.getElementById('tableSearchInput');
        if (searchInput) searchInput.value = '';
        
        const allRows = document.querySelectorAll('.table-wrap table tbody tr');
        document.getElementById('tableCount').textContent = allRows.length;
    }

    // ========== APERÇU IMAGE ==========
    function previewImage(input, previewId) {
        const preview = document.getElementById(previewId);
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = '<img src="' + e.target.result + '" style="max-width: 100px; border-radius: 8px;">';
            };
            reader.readAsDataURL(input.files[0]);
        } else {
            preview.innerHTML = '';
        }
    }

    // ========== MODALES ==========
    function openModal() { 
        document.getElementById('createModal').classList.add('open'); 
        document.getElementById('createPreview').innerHTML = '';
    }
    
    function closeModal() { 
        document.getElementById('createModal').classList.remove('open'); 
    }
    
    function closeEditModal() { 
        document.getElementById('editModal').classList.remove('open'); 
    }
    
    document.getElementById('createModal').addEventListener('click', function(e) { 
        if (e.target === this) closeModal(); 
    });
    
    document.getElementById('editModal').addEventListener('click', function(e) { 
        if (e.target === this) closeEditModal(); 
    });

    // ========== ÉDITION ==========
    function editEvent(id) {
        fetch('<?= $BASE ?>/Controleur/evenementC.php?action=get&id=' + id)
            .then(response => response.json())
            .then(data => {
                document.getElementById('edit_id').value = data.id;
                document.getElementById('edit_titre').value = data.titre;
                document.getElementById('edit_description').value = data.description;
                document.getElementById('edit_duree').value = data.duree;
                document.getElementById('edit_date').value = data.date_evenement;
                document.getElementById('edit_type').value = data.type;
                document.getElementById('edit_statut').value = data.statut;
                document.getElementById('edit_lieu').value = data.lieu;
                document.getElementById('edit_capacite').value = data.capacite;
                document.getElementById('edit_adresse').value = data.adresse_complete || '';
                
                if (data.image) {
                    document.getElementById('currentImageInfo').innerHTML = '<strong>Image actuelle:</strong><br><img src="<?= $BASE ?>/' + data.image + '" style="max-width: 100px; border-radius: 8px; margin-top: 5px;">';
                } else {
                    document.getElementById('currentImageInfo').innerHTML = '<strong>Aucune image</strong>';
                }
                
                document.getElementById('editForm').action = '<?= $BASE ?>/Controleur/evenementC.php?action=edit&id=' + id;
                document.getElementById('editModal').classList.add('open');
                document.getElementById('editPreview').innerHTML = '';
            })
            .catch(error => alert('Erreur lors du chargement'));
    }

    // ========== SUPPRESSION ==========
    function deleteEvent(id, titre) {
        if (confirm(`Supprimer "${titre}" ?`)) {
            window.location.href = '<?= $BASE ?>/Controleur/evenementC.php?action=delete&id=' + id;
        }
    }

    // ========== RECHERCHE ==========
    function filterTable() {
        const input = document.getElementById('tableSearchInput');
        const filter = input.value.toLowerCase();
        const rows = document.querySelectorAll('.table-wrap table tbody tr');
        let visibleCount = 0;
        
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            let found = false;
            cells.forEach((cell, index) => {
                if (index !== 0 && index !== 8) {
                    if (cell.textContent.toLowerCase().includes(filter)) {
                        found = true;
                    }
                }
            });
            if (found) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        document.getElementById('tableCount').textContent = visibleCount;
    }

    // ========== TRI ==========
    function sortTable(columnIndex) {
        const table = document.querySelector('.table-wrap table');
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        
        document.querySelectorAll('.sort-icon').forEach(icon => {
            icon.classList.remove('active', 'asc', 'desc');
            icon.textContent = '';
        });
        
        if (sortColumn === columnIndex) {
            sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            sortColumn = columnIndex;
            sortDirection = 'asc';
        }
        
        const currentIcon = document.getElementById(`sort-icon-${columnIndex}`);
        if (currentIcon) {
            currentIcon.classList.add('active', sortDirection);
            currentIcon.textContent = sortDirection === 'asc' ? '↑' : '↓';
        }
        
        rows.sort((a, b) => {
            let aValue = a.cells[columnIndex]?.textContent.trim() || '';
            let bValue = b.cells[columnIndex]?.textContent.trim() || '';
            
            if (columnIndex === 7) {
                const aMatch = aValue.match(/(\d+)\/(\d+)/);
                const bMatch = bValue.match(/(\d+)\/(\d+)/);
                if (aMatch && bMatch) {
                    aValue = parseInt(aMatch[1]);
                    bValue = parseInt(bMatch[1]);
                    return sortDirection === 'asc' ? aValue - bValue : bValue - aValue;
                }
            }
            
            if (columnIndex === 5) {
                const months = { 'Jan': 0, 'Feb': 1, 'Mar': 2, 'Apr': 3, 'May': 4, 'Jun': 5, 'Jul': 6, 'Aug': 7, 'Sep': 8, 'Oct': 9, 'Nov': 10, 'Dec': 11 };
                const partsA = aValue.split(' ');
                if (partsA.length === 3) {
                    aValue = new Date(parseInt(partsA[2]), months[partsA[1]], parseInt(partsA[0]));
                }
                const partsB = bValue.split(' ');
                if (partsB.length === 3) {
                    bValue = new Date(parseInt(partsB[2]), months[partsB[1]], parseInt(partsB[0]));
                }
                return sortDirection === 'asc' ? aValue - bValue : bValue - aValue;
            }
            
            aValue = aValue.toLowerCase();
            bValue = bValue.toLowerCase();
            if (aValue < bValue) return sortDirection === 'asc' ? -1 : 1;
            if (aValue > bValue) return sortDirection === 'asc' ? 1 : -1;
            return 0;
        });
        
        rows.forEach(row => tbody.appendChild(row));
    }

    // ========== THÈME CLAIR/SOMBRE ==========
    function initTheme() {
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            document.body.classList.add('dark-mode');
            const toggleBtn = document.getElementById('themeToggle');
            if (toggleBtn) toggleBtn.textContent = '☀️';
        } else {
            document.body.classList.remove('dark-mode');
            const toggleBtn = document.getElementById('themeToggle');
            if (toggleBtn) toggleBtn.textContent = '🌙';
        }
    }

    function toggleTheme() {
        if (document.body.classList.contains('dark-mode')) {
            document.body.classList.remove('dark-mode');
            localStorage.setItem('theme', 'light');
            document.getElementById('themeToggle').textContent = '🌙';
        } else {
            document.body.classList.add('dark-mode');
            localStorage.setItem('theme', 'dark');
            document.getElementById('themeToggle').textContent = '☀️';
        }
    }

    // ========== GRAPHIQUES ==========
    const participantsData = <?= json_encode($participants_data) ?>;
    const eventsData = <?= json_encode($events_data) ?>;
    const monthsLabels = <?= json_encode($months_labels) ?>;

    new Chart(document.getElementById('participantsChart'), {
        type: 'line',
        data: {
            labels: monthsLabels,
            datasets: [{
                label: 'Participants',
                data: participantsData,
                borderColor: '#58a6ff',
                backgroundColor: 'rgba(88,166,255,0.1)',
                tension: 0.3,
                fill: true,
                pointBackgroundColor: '#58a6ff'
            }]
        },
        options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { labels: { color: '#e6edf3' } } }, scales: { y: { ticks: { color: '#8b949e' }, grid: { color: '#30363d' } }, x: { ticks: { color: '#8b949e' }, grid: { color: '#30363d' } } } }
    });

    new Chart(document.getElementById('eventsChart'), {
        type: 'bar',
        data: {
            labels: monthsLabels,
            datasets: [{
                label: 'Événements',
                data: eventsData,
                backgroundColor: '#bc8cff',
                borderRadius: 8
            }]
        },
        options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { labels: { color: '#e6edf3' } } }, scales: { y: { ticks: { color: '#8b949e' }, grid: { color: '#30363d' } }, x: { ticks: { color: '#8b949e' }, grid: { color: '#30363d' } } } }
    });

    new Chart(document.getElementById('typeChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_keys($types)) ?> .map(t => t.charAt(0).toUpperCase() + t.slice(1)),
            datasets: [{
                data: <?= json_encode(array_column($types, 'count')) ?>,
                backgroundColor: ['#58a6ff', '#3fb950', '#d29922', '#f85149', '#bc8cff']
            }]
        },
        options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { labels: { color: '#e6edf3' } } } }
    });

    // ========== INITIALISATION ==========
    document.addEventListener('DOMContentLoaded', function() {
        initTheme();
        const toggleBtn = document.getElementById('themeToggle');
        if (toggleBtn) toggleBtn.addEventListener('click', toggleTheme);
        
        setTimeout(function() {
            saveOriginalOrder();
        }, 200);
    });
</script>

</body>
</html>