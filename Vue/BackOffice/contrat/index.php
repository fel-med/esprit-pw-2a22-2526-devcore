<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// ── TEMPORAIRE ──
$_SESSION['role'] = 'admin';
// ────────────────

require_once __DIR__ . '/../../../Controleur/contratC.php';

$controller = new ContratC();
$action     = $_GET['action'] ?? 'index';

if ($action === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    $controller->delete($id);
    header('Location: index.php?action=index');
    exit;
}

if ($action === 'updateStatut' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller->updateStatut((int)$_POST['id'], $_POST['statut'] ?? '');
    header('Location: index.php?action=index');
    exit;
}

$contrats = $controller->getAll();
$stats    = $controller->getStats();
if (!headers_sent()) header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Contrats — Admin · Cre8Connect</title>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        /* ===== DESIGN SYSTEM — Purple / Pink / Violet Theme ===== */
        :root {
            /* Deep dark backgrounds with purple tint */
            --bg-base:       #13111a;
            --bg-surface:    #1a1625;
            --bg-card:       #211c2f;
            --bg-hover:      #2a2340;
            --border:        #2e2845;
            --border-light:  #3d3560;

            /* Accent — violet/purple gradient */
            --accent:        #a855f7;
            --accent-2:      #ec4899;
            --accent-soft:   rgba(168,85,247,.18);
            --accent-hover:  #c084fc;

            /* Status */
            --success:       #a855f7;
            --success-soft:  rgba(168,85,247,.15);
            --warning:       #ec4899;
            --warning-soft:  rgba(236,72,153,.15);
            --danger:        #ec4899;
            --danger-soft:   rgba(236,72,153,.15);
            --purple:        #a855f7;
            --purple-soft:   rgba(168,85,247,.15);
            --pink:          #ec4899;
            --pink-soft:     rgba(236,72,153,.15);
            --teal:          #c084fc;
            --teal-soft:     rgba(192,132,252,.15);

            /* Text */
            --text-primary:  #f0eaff;
            --text-secondary:#9d8ec7;
            --text-muted:    #5c5280;

            /* Shape */
            --radius:        6px;
            --radius-lg:     10px;

            --sidebar-w:     244px;

            /* Gradient used across the UI */
            --grad:          linear-gradient(135deg, #a855f7, #ec4899);
        }

        /* ===== LIGHT MODE ===== */
        body.light-mode {
            --bg-base:       #faf5ff;
            --bg-surface:    #ffffff;
            --bg-card:       #ffffff;
            --bg-hover:      #f3e8ff;
            --border:        #e9d5ff;
            --border-light:  #d8b4fe;
            --text-primary:  #3b1e6e;
            --text-secondary:#7c3aed;
            --text-muted:    #a78bfa;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Source Sans Pro', sans-serif;
            background: var(--bg-base);
            color: var(--text-primary);
            font-size: 0.875rem;
            line-height: 1.5;
            transition: background 0.3s, color 0.3s;
        }

        /* ===== LAYOUT ===== */
        .container-scroller { display: flex; position: relative; min-height: 100vh; }

        /* ===== SIDEBAR — matches template .sidebar styles ===== */
        .sidebar {
            width: var(--sidebar-w);
            background: var(--bg-surface);
            min-height: 100vh;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            z-index: 100;
            border-right: 1px solid var(--border);
            overflow-y: auto;
        }

        .sidebar-brand-wrapper {
            background: var(--bg-surface);
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
        }
        .brand-logo {
            font-size: 1.25rem;
            font-weight: 700;
            background: var(--grad);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-decoration: none;
            letter-spacing: -0.3px;
        }
        .brand-logo span { background: none; -webkit-text-fill-color: var(--text-primary); }

        /* Template nav structure */
        .sidebar .nav { padding: 12px 0; list-style: none; }
        .sidebar .nav .nav-item { padding: 2px 0; }
        .sidebar .nav .nav-item .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 10px 24px;
            color: var(--text-secondary);
            font-size: 0.875rem;
            font-weight: 400;
            text-decoration: none;
            transition: all 0.15s;
            border-left: 3px solid transparent;
        }
        .sidebar .nav .nav-item .nav-link:hover,
        .sidebar .nav .nav-item.active .nav-link {
            background: var(--accent-soft);
            color: #ffffff;
            border-left-color: var(--accent);
        }
        .sidebar .nav .nav-item .nav-link .menu-icon {
            width: 28px; height: 28px;
            border-radius: 4px;
            display: flex; align-items: center; justify-content: center;
        }
        .sidebar .nav .nav-item .nav-link .menu-icon i { font-size: 0.875rem; }

        /* Colored icons — same as template nth-child pattern */
        .sidebar .nav .menu-items:nth-child(5n+1) .nav-link .menu-icon i { color: #a855f7; }
        .sidebar .nav .menu-items:nth-child(5n+2) .nav-link .menu-icon i { color: #ec4899; }
        .sidebar .nav .menu-items:nth-child(5n+3) .nav-link .menu-icon i { color: #a855f7; }
        .sidebar .nav .menu-items:nth-child(5n+4) .nav-link .menu-icon i { color: #c084fc; }
        .sidebar .nav .menu-items:nth-child(5n+5) .nav-link .menu-icon i { color: #ec4899; }

        .nav-category {
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: var(--text-muted);
            padding: 16px 24px 8px;
        }

        /* ===== PAGE BODY ===== */
        .page-body-wrapper {
            width: calc(100% - var(--sidebar-w));
            margin-left: var(--sidebar-w);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* ===== TOPBAR / NAVBAR ===== */
        .navbar {
            background: var(--bg-surface);
            border-bottom: 1px solid var(--border);
            border-top: 3px solid transparent;
            background-image: linear-gradient(var(--bg-surface), var(--bg-surface)),
                              var(--grad);
            background-origin: border-box;
            background-clip: padding-box, border-box;
            padding: 0 24px;
            height: 63px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 99;
        }
        .navbar-menu-wrapper { display: flex; align-items: center; gap: 16px; }

        /* Count badge — template style */
        .navbar-nav .nav-item .nav-link { color: var(--text-secondary); font-size: 0.875rem; }
        .count-indicator { position: relative; display: inline-flex; }
        .count-indicator .count {
            position: absolute;
            top: -2px; right: -4px;
            width: 16px; height: 16px;
            border-radius: 50%;
            background: var(--danger);
            color: #fff;
            font-size: 0.6rem;
            display: flex; align-items: center; justify-content: center;
            border: 2px solid var(--bg-surface);
        }

        .navbar-profile {
            display: flex; align-items: center; gap: 10px;
            cursor: pointer; font-size: 0.875rem; color: var(--text-secondary);
        }
        .navbar-profile .profile-pic {
            width: 36px; height: 36px;
            border-radius: 50%;
            background: var(--grad);
            display: flex; align-items: center; justify-content: center;
            color: #fff;
            font-size: 0.8rem;
            font-weight: 700;
        }

        .btn-icon {
            background: var(--bg-card);
            border: 1px solid var(--border);
            color: var(--text-primary);
            padding: 6px 12px;
            border-radius: var(--radius);
            cursor: pointer;
            font-size: 0.8rem;
            transition: background 0.15s;
        }
        .btn-icon:hover { background: var(--bg-hover); }

        /* ===== CONTENT ===== */
        .main-panel { flex: 1; }
        .content-wrapper {
            background: var(--bg-base);
            padding: 1.875rem 1.75rem;
            flex-grow: 1;
        }

        /* ===== GRID (template uses Bootstrap-like grid) ===== */
        .row { display: flex; flex-wrap: wrap; margin: 0 -12px; }
        .col { flex: 1; padding: 0 12px; }
        .col-4 { width: 33.3333%; padding: 0 12px; }
        .col-8 { width: 66.6666%; padding: 0 12px; }
        .col-3 { width: 25%; padding: 0 12px; }

        /* ===== CARDS — matches template .card ===== */
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            margin-bottom: 1.5rem;
        }
        .card-body { padding: 1.25rem; }
        .card-header {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: transparent;
        }
        .card-title {
            font-size: 0.9375rem;
            font-weight: 600;
            margin: 0;
        }
        .card-subtitle { font-size: 0.75rem; color: var(--text-secondary); }

        /* ===== KPI / STATS CARDS — template .card with icon ===== */
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.25rem; margin-bottom: 1.5rem; }
        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 1.25rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .stat-icon {
            width: 48px; height: 48px;
            border-radius: var(--radius);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }
        .stat-icon.blue   { background: var(--accent-soft);  color: var(--accent); }
        .stat-icon.green  { background: var(--teal-soft);    color: var(--teal); }
        .stat-icon.yellow { background: var(--warning-soft); color: var(--warning); }
        .stat-icon.purple { background: var(--pink-soft);    color: var(--pink); }
        .stat-value { font-size: 1.5rem; font-weight: 700; line-height: 1; }
        .stat-label { font-size: 0.75rem; color: var(--text-secondary); font-weight: 600; letter-spacing: 0.5px; text-transform: uppercase; margin-top: 4px; }

        /* ===== CHARTS ===== */
        .charts-row { display: grid; grid-template-columns: 2fr 1fr; gap: 1.25rem; margin-bottom: 1.5rem; }
        .chart-container { height: 260px; position: relative; }

        /* ===== FILTERS ===== */
        .filters-bar {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: flex-end;
        }
        .filter-group { display: flex; flex-direction: column; gap: 4px; }
        .filter-group label {
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .form-control {
            background: var(--bg-surface);
            border: 1px solid var(--border-light);
            color: var(--text-primary);
            padding: 7px 12px;
            border-radius: var(--radius);
            font-size: 0.8125rem;
            font-family: inherit;
            outline: none;
            transition: border-color 0.15s;
        }
        .form-control:focus { border-color: var(--accent); }

        /* ===== TABLE — matches template table styles ===== */
        .table-responsive { overflow-x: auto; }
        .table { width: 100%; border-collapse: collapse; }
        .table thead th {
            padding: 12px 16px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--text-secondary);
            border-bottom: 1px solid var(--border);
            cursor: pointer;
            white-space: nowrap;
        }
        .table thead th:hover { color: var(--accent); }
        .table tbody tr { transition: background 0.1s; }
        .table tbody tr:hover { background: var(--bg-hover); }
        .table td {
            padding: 13px 16px;
            font-size: 0.8125rem;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }
        .table tbody tr:last-child td { border-bottom: none; }

        /* ===== BADGES — matches template badge mixin ===== */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 100px;
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }
        .badge-warning  { background: var(--warning-soft);  color: var(--warning); }
        .badge-success  { background: var(--teal-soft);     color: var(--teal); }
        .badge-danger   { background: var(--pink-soft);     color: var(--pink); }
        .badge-purple   { background: var(--purple-soft);   color: var(--purple); }
        .badge-info     { background: var(--accent-soft);   color: var(--accent); }

        /* ===== BUTTONS — matches template .btn ===== */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: var(--radius);
            font-size: 0.8125rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            text-decoration: none;
            border: 1px solid transparent;
            transition: all 0.15s;
        }
        .btn-sm { padding: 5px 10px; font-size: 0.75rem; }
        .btn-danger {
            background: transparent;
            border-color: var(--danger);
            color: var(--danger);
        }
        .btn-danger:hover { background: var(--danger); color: #fff; }
        .btn-secondary {
            background: var(--bg-hover);
            border-color: var(--border-light);
            color: var(--text-primary);
        }

        /* Status select */
        .select-statut {
            background: var(--bg-surface);
            border: 1px solid var(--border-light);
            color: var(--text-primary);
            padding: 5px 10px;
            border-radius: var(--radius);
            font-size: 0.8rem;
            font-family: inherit;
            cursor: pointer;
        }

        /* Monospaced amount */
        .text-mono { font-family: 'Courier New', monospace; font-weight: 600; color: var(--accent); }

        /* ID text */
        .text-muted-id { color: var(--text-muted); font-family: monospace; font-size: 0.78rem; }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        .empty-state i { font-size: 2.5rem; margin-bottom: 12px; display: block; color: var(--text-muted); }

        /* ===== PAGINATION ===== */
        .pagination {
            padding: 14px 16px;
            display: flex;
            justify-content: center;
            gap: 4px;
            border-top: 1px solid var(--border);
        }
        .page-item .page-link {
            padding: 5px 12px;
            background: var(--bg-surface);
            border: 1px solid var(--border-light);
            color: var(--text-primary);
            cursor: pointer;
            border-radius: var(--radius);
            font-size: 0.8125rem;
            font-family: inherit;
            transition: all 0.15s;
        }
        .page-item .page-link:hover { background: var(--bg-hover); }
        .page-item.active .page-link { background: var(--grad); border-color: var(--accent); color: #fff; }

        /* ===== MODAL ===== */
        .modal-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,.7);
            z-index: 200;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.open { display: flex; }
        .modal-dialog {
            background: var(--bg-card);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-lg);
            padding: 28px;
            width: 100%;
            max-width: 440px;
        }
        .modal-header { font-size: 1rem; font-weight: 700; margin-bottom: 14px; }
        .modal-footer { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }

        /* Responsive */
        @media (max-width: 992px) {
            .sidebar { transform: translateX(-100%); }
            .page-body-wrapper { margin-left: 0; width: 100%; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .charts-row { grid-template-columns: 1fr; }
        }
    </style>
    <?php include __DIR__ . '/../includes/layout_head.php'; ?>
</head>
<body>
<div class="container-scroller">
<?php
$activeMenu = 'contrat';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="page-body-wrapper">
<?php include __DIR__ . '/../includes/topbar.php'; ?>

        <!-- CONTENT -->
        <div class="content-wrapper">

            <!-- CHARTS -->
            <div class="charts-row">
                <div class="card">
                    <div class="card-body">
                        <div class="card-title" style="margin-bottom:12px">Volume par statut</div>
                        <div class="chart-container">
                            <canvas id="contractsChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <div class="card-title" style="margin-bottom:12px">Actifs vs Inactifs</div>
                        <div class="chart-container">
                            <canvas id="statusPieChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPI STATS -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fas fa-file-contract"></i></div>
                    <div>
                        <div class="stat-value"><?= $stats['total'] ?? 0 ?></div>
                        <div class="stat-label" data-tr="stat_total">Total</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon yellow"><i class="fas fa-clock"></i></div>
                    <div>
                        <div class="stat-value"><?= $stats['en_attente'] ?? 0 ?></div>
                        <div class="stat-label" data-tr="stat_pending">En attente</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
                    <div>
                        <div class="stat-value"><?= $stats['signes'] ?? 0 ?></div>
                        <div class="stat-label" data-tr="stat_signed">Signés</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon purple"><i class="fas fa-euro-sign"></i></div>
                    <div>
                        <div class="stat-value"><?= number_format($stats['montant_total'] ?? 0, 0, ',', ' ') ?> €</div>
                        <div class="stat-label" data-tr="stat_value">Valeur totale</div>
                    </div>
                </div>
            </div>

            <!-- FILTERS -->
            <div class="filters-bar">
                <div class="filter-group">
                    <label data-tr="filter_search">Recherche</label>
                    <input type="text" id="searchInput" class="form-control" placeholder="Titre, marque, créateur..." style="width:220px">
                </div>
                <div class="filter-group">
                    <label data-tr="filter_status">Statut</label>
                    <select id="statusFilter" class="form-control">
                        <option value="all" data-tr="opt_all">Tous les statuts</option>
                        <option value="en_attente">En attente</option>
                        <option value="signe">Signé</option>
                        <option value="resilie">Résilié</option>
                        <option value="expire">Expiré</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label data-tr="filter_date">Date Min</label>
                    <input type="date" id="dateFilter" class="form-control">
                </div>
            </div>

            <!-- TABLE PANEL -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="fas fa-file-signature" style="color:var(--accent);margin-right:8px"></i>
                        <span data-tr="panel_title">Tous les contrats</span>
                    </h5>
                    <span class="card-subtitle">
                        <span id="visibleCount"><?= count($contrats) ?></span>
                        <span data-tr="panel_count"> contrat(s)</span>
                    </span>
                </div>

                <div class="table-responsive">
                    <?php if (empty($contrats)): ?>
                        <div class="empty-state">
                            <i class="fas fa-file-circle-xmark"></i>
                            Aucun contrat enregistré pour le moment.
                        </div>
                    <?php else: ?>
                    <table class="table" id="contratsTable">
                        <thead>
                            <tr>
                                <th onclick="sortTable(0)"># <i class="fas fa-sort"></i></th>
                                <th onclick="sortTable(1)" data-tr="th_title">Titre <i class="fas fa-sort"></i></th>
                                <th data-tr="th_brand">Marque</th>
                                <th data-tr="th_creator">Créateur</th>
                                <th onclick="sortTable(4)" data-tr="th_amount">Montant <i class="fas fa-sort"></i></th>
                                <th data-tr="th_period">Période</th>
                                <th data-tr="th_status">Statut</th>
                                <th data-tr="th_action">Action</th>
                            </tr>
                        </thead>
                        <tbody id="contratBody">
                        <?php foreach ($contrats as $c): ?>
                            <tr class="contrat-row"
                                data-status="<?= $c['statut'] ?>"
                                data-date="<?= $c['date_debut'] ?>"
                                data-brand="<?= htmlspecialchars($c['nomMarque'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                data-creator="<?= htmlspecialchars($c['nomCreateur'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <td><span class="text-muted-id">#<?= $c['id'] ?></span></td>
                                <td><strong><?= htmlspecialchars($c['titre'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                                <td><?= htmlspecialchars($c['nomMarque'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($c['nomCreateur'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="text-mono"><?= number_format($c['montant'], 2, ',', ' ') ?> €</span></td>
                                <td style="font-size:.8rem;color:var(--text-secondary)">
                                    <?= date('d/m/Y', strtotime($c['date_debut'])) ?>
                                    <span style="color:var(--text-muted)"> → <?= date('d/m/Y', strtotime($c['date_fin'])) ?></span>
                                </td>
                                <td>
                                    <form method="POST" action="index.php?action=updateStatut">
                                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                        <select name="statut" class="select-statut" onchange="this.form.submit()">
                                            <option value="en_attente" <?= $c['statut'] === 'en_attente' ? 'selected' : '' ?>>⏳ En attente</option>
                                            <option value="signe"      <?= $c['statut'] === 'signe'      ? 'selected' : '' ?>>✅ Signé</option>
                                            <option value="resilie"    <?= $c['statut'] === 'resilie'    ? 'selected' : '' ?>>❌ Résilié</option>
                                            <option value="expire"     <?= $c['statut'] === 'expire'     ? 'selected' : '' ?>>🕐 Expiré</option>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <button class="btn btn-danger btn-sm"
                                            onclick="confirmDelete(<?= $c['id'] ?>, '<?= htmlspecialchars(addslashes($c['titre']), ENT_QUOTES, 'UTF-8') ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div id="pagination" class="pagination"></div>
                    <?php endif; ?>
                </div>
            </div>
        </div><!-- /content-wrapper -->
</div><!-- /page-body-wrapper -->
</div><!-- /container-scroller -->

<!-- ===== MODAL SUPPRESSION ===== -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal-dialog">
        <div class="modal-header">
            <i class="fas fa-exclamation-triangle" style="color:var(--danger);margin-right:8px"></i>
            <span data-tr="mod_del_title">Confirmer la suppression</span>
        </div>
        <p style="color:var(--text-secondary);font-size:.875rem">
            <span data-tr="mod_del_text">Vous êtes sur le point de supprimer le contrat</span>
            <strong id="deleteTitle" style="color:var(--text-primary)"></strong>.
            <span data-tr="mod_del_warn">Cette action est irréversible.</span>
        </p>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal()" data-tr="btn_cancel">Annuler</button>
            <a id="deleteLink" href="#" class="btn btn-danger">
                <i class="fas fa-trash"></i> <span data-tr="btn_confirm">Supprimer</span>
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/layout_scripts.php'; ?>
<script>
// ===== TRANSLATION =====
const i18n = {
    fr: {
        nav_dashboard:"Tableau de bord", nav_overview:"Aperçu", nav_modules:"Modules",
        nav_users:"Utilisateurs", nav_offers:"Offres", nav_campaigns:"Campagnes", nav_products:"Produits",
        nav_contracts:"Contrats", nav_events:"Événements", nav_posts:"Posts", nav_reclamations:"Réclamations",
        title_main:"Gestion des Contrats", subtitle_main:"Supervision et modération",
        stat_total:"Total", stat_pending:"En attente", stat_signed:"Signés", stat_value:"Valeur totale",
        filter_search:"Recherche", filter_status:"Statut", filter_date:"Date Min", opt_all:"Tous les statuts",
        panel_title:"Tous les contrats", panel_count:" contrat(s)",
        th_title:"Titre", th_brand:"Marque", th_creator:"Créateur", th_amount:"Montant",
        th_period:"Période", th_status:"Statut", th_action:"Action",
        mod_del_title:"Confirmer la suppression", mod_del_text:"Vous allez supprimer",
        mod_del_warn:"Action irréversible.", btn_cancel:"Annuler", btn_confirm:"Supprimer"
    },
    en: {
        nav_dashboard:"Dashboard", nav_overview:"Overview", nav_modules:"Modules",
        nav_users:"Users", nav_offers:"Offers", nav_campaigns:"Campaigns", nav_products:"Products",
        nav_contracts:"Contracts", nav_events:"Events", nav_posts:"Posts", nav_reclamations:"Complaints",
        title_main:"Contract Management", subtitle_main:"Supervision and moderation",
        stat_total:"Total", stat_pending:"Pending", stat_signed:"Signed", stat_value:"Total Value",
        filter_search:"Search", filter_status:"Status", filter_date:"Min Date", opt_all:"All statuses",
        panel_title:"All Contracts", panel_count:" contract(s)",
        th_title:"Title", th_brand:"Brand", th_creator:"Creator", th_amount:"Amount",
        th_period:"Period", th_status:"Status", th_action:"Action",
        mod_del_title:"Confirm Deletion", mod_del_text:"You are about to delete",
        mod_del_warn:"This action is final.", btn_cancel:"Cancel", btn_confirm:"Delete"
    }
};
function translatePage(lang) {
    document.querySelectorAll('[data-tr]').forEach(el => {
        const key = el.getAttribute('data-tr');
        if (i18n[lang][key]) el.innerText = i18n[lang][key];
    });
}
document.getElementById('langSwitcher')?.addEventListener('change', e => translatePage(e.target.value));

// ===== THEME =====
const themeBtn = document.getElementById('themeToggle');
themeBtn?.addEventListener('click', () => {
    const theme = window.toggleBackOfficeTheme ? window.toggleBackOfficeTheme() : (document.body.classList.toggle('light-mode') ? 'light' : 'dark');
    const isLight = theme === 'light';
    themeBtn.innerText = isLight ? '☀️' : '🌙';
    initCharts();
});
if ((window.applyBackOfficeTheme ? window.applyBackOfficeTheme() : localStorage.getItem('theme')) === 'light') {
    document.body.classList.add('light-mode');
    if (themeBtn) themeBtn.innerText = '☀️';
}

// ===== FILTERS =====
const rows = Array.from(document.querySelectorAll('.contrat-row'));
function applyFilters() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const status = document.getElementById('statusFilter').value;
    const dateMin = document.getElementById('dateFilter').value;
    const filtered = rows.filter(row => {
        return row.innerText.toLowerCase().includes(search) &&
               (status === 'all' || row.dataset.status === status) &&
               (!dateMin || row.dataset.date >= dateMin);
    });
    displayRows(filtered);
}
['searchInput','statusFilter','dateFilter'].forEach(id => {
    document.getElementById(id)?.addEventListener('input', applyFilters);
});

// ===== SORT =====
function sortTable(n) {
    const tbody = document.getElementById('contratBody');
    const rowsArr = Array.from(tbody.querySelectorAll('tr'));
    let dir = tbody.dataset.sortDir === 'asc' ? 'desc' : 'asc';
    tbody.dataset.sortDir = dir;
    rowsArr.sort((a, b) => {
        const x = a.cells[n]?.innerText.toLowerCase() || '';
        const y = b.cells[n]?.innerText.toLowerCase() || '';
        return dir === 'asc' ? x.localeCompare(y) : y.localeCompare(x);
    });
    rowsArr.forEach(r => tbody.appendChild(r));
}

// ===== PAGINATION =====
const ITEMS_PER_PAGE = 5;
let currentRows = rows;

function displayRows(list) {
    currentRows = list;
    document.getElementById('visibleCount').innerText = list.length;
    const total = Math.ceil(list.length / ITEMS_PER_PAGE);
    renderPage(1);
    renderPagination(total);
}

function renderPage(page) {
    const start = (page - 1) * ITEMS_PER_PAGE;
    rows.forEach(r => r.style.display = 'none');
    currentRows.slice(start, start + ITEMS_PER_PAGE).forEach(r => r.style.display = '');
}

function renderPagination(total) {
    const c = document.getElementById('pagination');
    c.innerHTML = '';
    for (let i = 1; i <= total; i++) {
        const li = document.createElement('div');
        li.className = 'page-item' + (i === 1 ? ' active' : '');
        const btn = document.createElement('button');
        btn.className = 'page-link';
        btn.innerText = i;
        btn.onclick = () => {
            document.querySelectorAll('.page-item').forEach(x => x.classList.remove('active'));
            li.classList.add('active');
            renderPage(i);
        };
        li.appendChild(btn);
        c.appendChild(li);
    }
}

// ===== CHARTS =====
function initCharts() {
    Chart.getChart('contractsChart')?.destroy();
    Chart.getChart('statusPieChart')?.destroy();
    const chartTheme = window.getBackOfficeChartTheme ? window.getBackOfficeChartTheme() : { text:'#9d8ec7', grid:'#2e2845', accent:'#a855f7', rose:'#ec4899', info:'#c084fc' };
    const statusCounts = { en_attente: 0, signe: 0, resilie: 0, expire: 0 };
    rows.forEach(r => { if (statusCounts[r.dataset.status] !== undefined) statusCounts[r.dataset.status]++; });

    const chartDefaults = {
        color: chartTheme.text,
        plugins: { legend: { labels: { color: chartTheme.text } } }
    };

    new Chart(document.getElementById('contractsChart'), {
        type: 'bar',
        data: {
            labels: ['En attente', 'Signés', 'Résiliés', 'Expirés'],
            datasets: [{
                label: 'Contrats',
                data: [statusCounts.en_attente, statusCounts.signe, statusCounts.resilie, statusCounts.expire],
                backgroundColor: [chartTheme.rose, chartTheme.info, chartTheme.roseSoft, chartTheme.accent],
                borderRadius: 5
            }]
        },
        options: {
            maintainAspectRatio: false,
            ...chartDefaults,
            scales: {
                x: { ticks: { color: chartTheme.text }, grid: { color: chartTheme.grid } },
                y: { ticks: { color: chartTheme.text }, grid: { color: chartTheme.grid } }
            }
        }
    });

    new Chart(document.getElementById('statusPieChart'), {
        type: 'doughnut',
        data: {
            labels: ['Actifs', 'Inactifs'],
            datasets: [{
                data: [statusCounts.signe, statusCounts.resilie + statusCounts.expire + statusCounts.en_attente],
                backgroundColor: [chartTheme.accent, chartTheme.rose],
                borderColor: [chartTheme.accent, chartTheme.rose]
            }]
        },
        options: {
            maintainAspectRatio: false,
            ...chartDefaults
        }
    });
}

// ===== MODAL =====
function confirmDelete(id, titre) {
    document.getElementById('deleteTitle').textContent = '"' + titre + '"';
    document.getElementById('deleteLink').href = 'index.php?action=delete&id=' + id;
    document.getElementById('deleteModal').classList.add('open');
}
function closeModal() {
    document.getElementById('deleteModal').classList.remove('open');
}
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
window.addEventListener('cre8:themechange', initCharts);

// ===== INIT =====
window.onload = () => {
    displayRows(rows);
    initCharts();
};
</script>
</body>
</html>
