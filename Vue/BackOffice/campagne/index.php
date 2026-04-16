<?php
require_once '../../../Controleur/campagneC.php';
require_once '../../../Modele/campagne.php';

$campagneC   = new CampagneC();
$message     = '';
$messageType = '';
$baseUrl     = '/projet/Esprit-PW-2A22-2526-Devcore';

// ── SUPPRESSION ───────────────────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $campagneC->supprimerCampagne(intval($_GET['delete']));
    header('Location: index.php?deleted=1');
    exit;
}

// ── SUPPRESSION MASSE ─────────────────────────────────────────────────────────
if (isset($_POST['action_masse']) && $_POST['action_masse'] === 'supprimer_selection') {
    $ids = $_POST['selected_ids'] ?? [];
    foreach ($ids as $id) $campagneC->supprimerCampagne(intval($id));
    header('Location: index.php?deleted_masse=' . count($ids));
    exit;
}

// ── AJAX : toggle archive ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'archive') {
    $campagneC->toggleArchive(intval($_POST['id']));
    echo json_encode(['ok' => true]);
    exit;
}

// ── AJAX : changer statut ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'statut') {
    $campagneC->changerStatut(intval($_POST['id']), $_POST['statut'] ?? '');
    echo json_encode(['ok' => true]);
    exit;
}

// ── AJOUTER ───────────────────────────────────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $titre  = trim($_POST['titre'] ?? '');
    $budget = $_POST['budget'] ?? '';
    if (strlen($titre) < 2 || !is_numeric($budget) || floatval($budget) < 0) {
        $message     = "Invalid input: title required (min 2 chars), budget must be ≥ 0.";
        $messageType = "error";
    } else {
        $campagne = new Campagne(
            null,
            $titre,
            trim($_POST['description'] ?? ''),
            !empty($_POST['dateDebut'])  ? $_POST['dateDebut']  : null,
            !empty($_POST['dateFin'])    ? $_POST['dateFin']    : null,
            floatval($budget),
            $_POST['statut'] ?? 'brouillon',
            null,
            trim($_POST['objectif'] ?? ''),
            0
        );
        $campagneC->ajouterCampagne($campagne);
        header('Location: index.php?added=1');
        exit;
    }
}

// ── MODIFIER ──────────────────────────────────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'update') {
    $titre  = trim($_POST['titre'] ?? '');
    $budget = $_POST['budget'] ?? '';
    if (strlen($titre) < 2 || !is_numeric($budget) || floatval($budget) < 0) {
        $message     = "Invalid input: title required (min 2 chars), budget must be ≥ 0.";
        $messageType = "error";
    } else {
        $campagne = new Campagne(
            null,
            $titre,
            trim($_POST['description'] ?? ''),
            !empty($_POST['dateDebut'])  ? $_POST['dateDebut']  : null,
            !empty($_POST['dateFin'])    ? $_POST['dateFin']    : null,
            floatval($budget),
            $_POST['statut'] ?? 'brouillon',
            null,
            trim($_POST['objectif'] ?? ''),
            intval($_POST['estArchive'] ?? 0)
        );
        $campagneC->modifierCampagne($campagne, intval($_POST['id']));
        header('Location: index.php?updated=1');
        exit;
    }
}

// ── MESSAGES GET ──────────────────────────────────────────────────────────────
if (isset($_GET['added']))         { $message = "Campaign added successfully.";                                             $messageType = "success"; }
if (isset($_GET['updated']))       { $message = "Campaign updated successfully.";                                           $messageType = "info"; }
if (isset($_GET['deleted']))       { $message = "Campaign deleted successfully.";                                           $messageType = "danger"; }
if (isset($_GET['deleted_masse'])) { $message = $_GET['deleted_masse'] . " campaign(s) deleted successfully.";             $messageType = "danger"; }

// ── DONNÉES ───────────────────────────────────────────────────────────────────
$liste         = $campagneC->afficherCampagnes();
$listeArchives = $campagneC->afficherCampagnesArchives();
$statuts       = $campagneC->getStatuts();

$campagneUpdate = null;
if (isset($_GET['edit'])) {
    $campagneUpdate = $campagneC->recupererCampagne(intval($_GET['edit']));
}

// ── KPIs ──────────────────────────────────────────────────────────────────────
$totalCampagnes  = count($liste);
$totalArchives   = count($listeArchives);
$budgets         = array_column($liste, 'budget');
$budgetTotal     = array_sum($budgets);
$budgetMoyen     = $totalCampagnes > 0 ? $budgetTotal / $totalCampagnes : 0;
$nbActives       = count(array_filter($liste, fn($c) => $c['statut'] === 'active'));
$nbBrouillons    = count(array_filter($liste, fn($c) => $c['statut'] === 'brouillon'));
$nbTerminees     = count(array_filter($liste, fn($c) => $c['statut'] === 'terminee'));
$nbAnnulees      = count(array_filter($liste, fn($c) => $c['statut'] === 'annulee'));

// ── CSV EXPORT ────────────────────────────────────────────────────────────────
if (isset($_GET['export_csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="campaigns_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Title', 'Description', 'Start Date', 'End Date', 'Budget (€)', 'Status', 'Brand', 'Objective', 'Archived']);
    foreach ($liste as $c) {
        fputcsv($out, [
            $c['idCampagne'], $c['titreCampagne'], $c['description'],
            $c['dateDebut'], $c['dateFin'], $c['budget'], $c['statut'],
            $c['nomMarque'] ?? '', $c['objectif'] ?? '',
            !empty($c['estArchive']) ? 'Yes' : 'No',
        ]);
    }
    fclose($out);
    exit;
}

// ── STATUT HELPERS ────────────────────────────────────────────────────────────
function statutLabel($s) {
    return match($s) {
        'active'    => '✅ Active',
        'terminee'  => '🏁 Ended',
        'annulee'   => '❌ Cancelled',
        default     => '📝 Draft',
    };
}
function statutClass($s) {
    return match($s) {
        'active'   => 'badge-success',
        'terminee' => 'badge-info',
        'annulee'  => 'badge-danger',
        default    => 'badge-warning',
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campaign Management — Cre8Connect Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ── DESIGN TOKENS ─────────────────────────────────────────── */
        :root {
            --bg-main:       #0f1117;
            --bg-card:       #171923;
            --bg-card-alt:   #1e2130;
            --border:        rgba(255,255,255,.07);
            --border-hover:  rgba(255,255,255,.13);
            --text-primary:  #f0f2f8;
            --text-muted:    #8b92a5;
            --text-dim:      #545d72;
            --accent:        #7c6fff;
            --accent-soft:   rgba(124,111,255,.12);
            --accent-border: rgba(124,111,255,.3);
            --success:       #10b981;
            --success-soft:  rgba(16,185,129,.12);
            --danger:        #ef4444;
            --danger-soft:   rgba(239,68,68,.12);
            --warning:       #f59e0b;
            --warning-soft:  rgba(245,158,11,.12);
            --info:          #3b82f6;
            --info-soft:     rgba(59,130,246,.12);
            --radius-sm:     6px;
            --radius:        10px;
            --radius-lg:     16px;
            --sidebar-w:     240px;
            --topbar-h:      58px;
        }
        .light-mode {
            --bg-main:    #f4f6fb;
            --bg-card:    #ffffff;
            --bg-card-alt:#f0f2f8;
            --border:     rgba(0,0,0,.08);
            --border-hover:rgba(0,0,0,.15);
            --text-primary:#111827;
            --text-muted:  #6b7280;
            --text-dim:    #9ca3af;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: var(--bg-main); color: var(--text-primary); min-height: 100vh; }

        /* ── SIDEBAR ── */
        .sidebar { position: fixed; top: 0; left: 0; width: var(--sidebar-w); height: 100vh; background: var(--bg-card); border-right: 1px solid var(--border); display: flex; flex-direction: column; z-index: 100; overflow-y: auto; }
        .sidebar-logo { padding: 20px 18px 16px; border-bottom: 1px solid var(--border); }
        .brand { display: flex; align-items: center; gap: 10px; text-decoration: none; }
        .logo-img { width: 32px; height: 32px; border-radius: 8px; object-fit: cover; }
        .logo-text { font-size: 14px; font-weight: 700; color: var(--text-primary); }
        .logo-badge { font-size: 9px; font-weight: 700; background: var(--accent-soft); color: var(--accent); border-radius: 4px; padding: 1px 5px; letter-spacing: .06em; }
        .sidebar-nav { padding: 14px 10px; flex: 1; }
        .nav-section-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .09em; color: var(--text-dim); padding: 10px 8px 5px; }
        .nav-item { display: flex; align-items: center; gap: 9px; padding: 8px 10px; border-radius: var(--radius-sm); text-decoration: none; color: var(--text-muted); font-size: 13px; font-weight: 500; transition: background .15s, color .15s; margin-bottom: 2px; }
        .nav-item:hover { background: var(--bg-card-alt); color: var(--text-primary); }
        .nav-item.active { background: var(--accent-soft); color: var(--accent); }
        .nav-icon { width: 16px; height: 16px; flex-shrink: 0; }
        .sidebar-footer { padding: 14px 16px; border-top: 1px solid var(--border); }
        .admin-card { display: flex; align-items: center; gap: 10px; }
        .admin-avatar { width: 32px; height: 32px; border-radius: 50%; background: var(--accent); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; flex-shrink: 0; }
        .admin-name { font-size: 12px; font-weight: 600; color: var(--text-primary); }
        .admin-role { font-size: 10px; color: var(--text-dim); }

        /* ── TOPBAR ── */
        .topbar { position: fixed; top: 0; left: var(--sidebar-w); right: 0; height: var(--topbar-h); background: var(--bg-card); border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; padding: 0 22px; z-index: 99; gap: 12px; }
        .topbar-breadcrumb { display: flex; align-items: center; gap: 6px; font-size: 13px; color: var(--text-muted); }
        .topbar-breadcrumb .sep { color: var(--text-dim); }
        .topbar-breadcrumb .current { color: var(--text-primary); font-weight: 600; }
        .topbar-actions { display: flex; align-items: center; gap: 8px; }
        .search-wrap { position: relative; }
        .search-icon { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--text-dim); width: 13px; height: 13px; pointer-events: none; }
        .search-input { background: var(--bg-card-alt); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 7px 12px 7px 30px; font-size: 12.5px; color: var(--text-primary); outline: none; width: 200px; font-family: 'Inter', sans-serif; }
        .search-input:focus { border-color: var(--accent); }
        .btn-add { display: inline-flex; align-items: center; gap: 6px; background: var(--accent); color: #fff; border: none; border-radius: var(--radius-sm); padding: 7px 14px; font-size: 12.5px; font-weight: 600; cursor: pointer; text-decoration: none; font-family: 'Inter', sans-serif; }
        .btn-add:hover { opacity: .88; }
        .btn-export { display: inline-flex; align-items: center; gap: 6px; background: var(--bg-card-alt); color: var(--text-muted); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 7px 13px; font-size: 12px; font-weight: 500; text-decoration: none; cursor: pointer; }
        .btn-export:hover { border-color: var(--border-hover); color: var(--text-primary); }
        .btn-fo-link { display: inline-flex; align-items: center; gap: 5px; background: var(--info-soft); color: var(--info); border: 1px solid rgba(59,130,246,.2); border-radius: var(--radius-sm); padding: 7px 12px; font-size: 12px; font-weight: 500; text-decoration: none; }
        .theme-toggle { display: flex; align-items: center; gap: 6px; background: var(--bg-card-alt); border: 1px solid var(--border); border-radius: 20px; padding: 5px 10px; cursor: pointer; font-size: 11px; color: var(--text-muted); }
        .toggle-track-pill { width: 28px; height: 14px; background: var(--accent); border-radius: 10px; position: relative; }
        .toggle-knob { position: absolute; top: 2px; right: 2px; width: 10px; height: 10px; border-radius: 50%; background: #fff; transition: right .2s; }
        .light-mode .toggle-knob { right: auto; left: 2px; }
        .icon-sun, .icon-moon { width: 13px; height: 13px; }
        .icon-sun { color: var(--warning); }
        .icon-moon { color: var(--accent); }

        /* ── LAYOUT ── */
        .main { margin-left: var(--sidebar-w); padding-top: var(--topbar-h); min-height: 100vh; }
        .content { padding: 28px 26px; }
        .page-header { margin-bottom: 22px; }
        .page-title { font-size: 20px; font-weight: 700; letter-spacing: -.3px; }
        .page-subtitle { font-size: 13px; color: var(--text-muted); margin-top: 3px; }

        /* ── ALERT ── */
        .alert { display: flex; align-items: center; gap: 9px; padding: 12px 16px; border-radius: var(--radius-sm); font-size: 13px; font-weight: 500; margin-bottom: 18px; border: 1px solid transparent; }
        .alert-success { background: var(--success-soft); color: var(--success); border-color: rgba(16,185,129,.2); }
        .alert-info    { background: var(--info-soft);    color: var(--info);    border-color: rgba(59,130,246,.2); }
        .alert-danger  { background: var(--danger-soft);  color: var(--danger);  border-color: rgba(239,68,68,.2); }
        .alert-error   { background: var(--danger-soft);  color: var(--danger);  border-color: rgba(239,68,68,.2); }
        .alert-warning { background: var(--warning-soft); color: var(--warning); border-color: rgba(245,158,11,.2); }

        /* ── KPI STRIP ── */
        .kpi-strip { display: grid; grid-template-columns: repeat(6, 1fr); gap: 12px; margin-bottom: 22px; }
        .kpi-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); padding: 16px 18px; }
        .kpi-label { font-size: 11px; font-weight: 600; color: var(--text-dim); text-transform: uppercase; letter-spacing: .07em; margin-bottom: 6px; }
        .kpi-value { font-size: 22px; font-weight: 700; }
        .kpi-sub { font-size: 11px; color: var(--text-muted); margin-top: 3px; }
        .kpi-accent { color: var(--accent); }
        .kpi-success { color: var(--success); }
        .kpi-warning { color: var(--warning); }
        .kpi-danger  { color: var(--danger); }
        .kpi-info    { color: var(--info); }

        /* ── TABS ── */
        .tabs { display: flex; gap: 4px; margin-bottom: 20px; border-bottom: 1px solid var(--border); }
        .tab-btn { background: none; border: none; padding: 9px 16px; font-size: 13px; font-weight: 500; color: var(--text-muted); cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -1px; font-family: 'Inter', sans-serif; transition: color .15s; }
        .tab-btn.active { color: var(--accent); border-bottom-color: var(--accent); }
        .tab-btn:hover { color: var(--text-primary); }
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        /* ── TABLE ── */
        .table-wrap { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; }
        .table-toolbar { display: flex; align-items: center; justify-content: space-between; padding: 14px 18px; border-bottom: 1px solid var(--border); gap: 10px; flex-wrap: wrap; }
        .table-title-row { display: flex; align-items: center; gap: 10px; }
        .table-title { font-size: 14px; font-weight: 600; }
        .count-badge { background: var(--accent-soft); color: var(--accent); border-radius: 20px; padding: 2px 10px; font-size: 11px; font-weight: 700; }
        .toolbar-actions { display: flex; align-items: center; gap: 8px; }
        .btn-bulk-del { display: none; align-items: center; gap: 5px; background: var(--danger-soft); color: var(--danger); border: 1px solid rgba(239,68,68,.2); border-radius: var(--radius-sm); padding: 6px 12px; font-size: 12px; font-weight: 600; cursor: pointer; font-family: 'Inter', sans-serif; }
        .btn-bulk-del.visible { display: inline-flex; }
        .filter-select { background: var(--bg-card-alt); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 6px 10px; font-size: 12px; color: var(--text-primary); cursor: pointer; font-family: 'Inter', sans-serif; outline: none; }
        table { width: 100%; border-collapse: collapse; }
        thead { background: var(--bg-card-alt); }
        th { text-align: left; padding: 11px 14px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; color: var(--text-dim); white-space: nowrap; }
        th.sort-col { cursor: pointer; user-select: none; }
        th.sort-col:hover { color: var(--text-muted); }
        td { padding: 12px 14px; font-size: 13px; border-top: 1px solid var(--border); vertical-align: middle; }
        tr:hover td { background: var(--bg-card-alt); }
        .col-check { width: 40px; }
        .col-title { min-width: 180px; }
        .col-dates { white-space: nowrap; font-size: 12px; color: var(--text-muted); }
        .col-budget { font-weight: 600; color: var(--success); }
        .col-actions { width: 160px; white-space: nowrap; }
        .camp-title { font-weight: 600; color: var(--text-primary); font-size: 13.5px; }
        .camp-brand { font-size: 11px; color: var(--text-muted); margin-top: 2px; }
        .camp-obj   { font-size: 12px; color: var(--text-muted); margin-top: 2px; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

        /* ── BADGE ── */
        .badge { display: inline-flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 600; padding: 3px 9px; border-radius: 20px; }
        .badge-success { background: var(--success-soft); color: var(--success); }
        .badge-warning { background: var(--warning-soft); color: var(--warning); }
        .badge-danger  { background: var(--danger-soft);  color: var(--danger); }
        .badge-info    { background: var(--info-soft);    color: var(--info); }

        /* ── TABLE ACTION BUTTONS ── */
        .btn-table { display: inline-flex; align-items: center; gap: 4px; padding: 5px 10px; border-radius: var(--radius-sm); font-size: 11.5px; font-weight: 600; cursor: pointer; border: none; font-family: 'Inter', sans-serif; text-decoration: none; transition: opacity .15s; }
        .btn-table:hover { opacity: .8; }
        .btn-edit    { background: var(--accent-soft);  color: var(--accent); }
        .btn-delete  { background: var(--danger-soft);  color: var(--danger); }
        .btn-archive { background: var(--warning-soft); color: var(--warning); }
        .btn-unarch  { background: var(--info-soft);    color: var(--info); }

        /* ── STATUS DROPDOWN ── */
        .statut-select { background: transparent; border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 4px 8px; font-size: 11px; font-weight: 600; cursor: pointer; font-family: 'Inter', sans-serif; color: var(--text-muted); }

        /* ── FORM SECTION ── */
        .form-section { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); padding: 24px 26px; margin-top: 22px; }
        .form-section-header { margin-bottom: 20px; }
        .form-section-title { font-size: 15px; font-weight: 600; }
        .form-section-sub   { font-size: 12.5px; color: var(--text-muted); margin-top: 3px; }
        .edit-banner { display: flex; align-items: center; justify-content: space-between; background: var(--accent-soft); border: 1px solid var(--accent-border); border-radius: var(--radius-sm); padding: 9px 14px; margin-bottom: 18px; font-size: 13px; }
        .edit-banner a { color: var(--danger); text-decoration: none; font-weight: 600; font-size: 12px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
        .form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 18px; }
        .form-full { grid-column: 1 / -1; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group label { font-size: 12px; font-weight: 600; color: var(--text-muted); }
        .form-input { background: var(--bg-card-alt); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 9px 12px; font-size: 13px; color: var(--text-primary); font-family: 'Inter', sans-serif; outline: none; transition: border-color .2s; width: 100%; }
        .form-input:focus { border-color: var(--accent); }
        .form-input.error-field { border-color: var(--danger); }
        textarea.form-input { resize: vertical; min-height: 80px; }
        .error-msg { font-size: 11.5px; color: var(--danger); display: none; margin-top: 2px; }
        .error-msg.visible { display: block; }
        .input-with-prefix { display: flex; align-items: center; border: 1px solid var(--border); border-radius: var(--radius-sm); background: var(--bg-card-alt); overflow: hidden; transition: border-color .2s; }
        .input-with-prefix:focus-within { border-color: var(--accent); }
        .prefix { padding: 0 10px; font-size: 12.5px; color: var(--text-dim); border-right: 1px solid var(--border); background: var(--bg-main); height: 100%; display: flex; align-items: center; min-height: 38px; }
        .input-with-prefix .form-input { border: none; background: transparent; flex: 1; }
        .input-with-prefix .form-input:focus { box-shadow: none; }
        .form-actions { display: flex; gap: 10px; align-items: center; margin-top: 22px; padding-top: 18px; border-top: 1px solid var(--border); }
        .btn-submit { background: var(--accent); color: #fff; border: none; border-radius: var(--radius-sm); padding: 9px 22px; font-size: 13px; font-weight: 600; cursor: pointer; font-family: 'Inter', sans-serif; }
        .btn-submit:hover { opacity: .88; }
        .btn-cancel-form { background: var(--bg-card-alt); color: var(--text-muted); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 9px 18px; font-size: 13px; font-weight: 500; cursor: pointer; text-decoration: none; font-family: 'Inter', sans-serif; }

        /* ── COMPLETION BAR ── */
        .completion-wrap { margin-bottom: 18px; }
        .completion-top { display: flex; justify-content: space-between; font-size: 12px; color: var(--text-muted); margin-bottom: 5px; }
        .completion-bar { height: 4px; background: var(--bg-card-alt); border-radius: 4px; overflow: hidden; }
        .completion-fill { height: 100%; background: var(--accent); border-radius: 4px; transition: width .3s; }
        .completion-hint { font-size: 11px; color: var(--text-dim); margin-top: 4px; }

        /* ── CONFIRM MODAL ── */
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.65); z-index: 200; display: none; align-items: center; justify-content: center; }
        .modal-overlay.open { display: flex; }
        .modal-box { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 28px 30px; width: 400px; max-width: 94vw; }
        .modal-title { font-size: 16px; font-weight: 600; margin-bottom: 8px; }
        .modal-text  { font-size: 13px; color: var(--text-muted); margin-bottom: 22px; }
        .modal-actions { display: flex; gap: 10px; justify-content: flex-end; }
        .btn-modal-cancel  { background: var(--bg-card-alt); color: var(--text-muted); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 8px 16px; font-size: 13px; cursor: pointer; font-family: 'Inter', sans-serif; }
        .btn-modal-confirm { background: var(--danger); color: #fff; border: none; border-radius: var(--radius-sm); padding: 8px 18px; font-size: 13px; font-weight: 600; cursor: pointer; font-family: 'Inter', sans-serif; }

        /* ── RESPONSIVE ── */
        @media (max-width: 1300px) { .kpi-strip { grid-template-columns: repeat(3, 1fr); } }
        @media (max-width: 900px)  { .kpi-strip { grid-template-columns: repeat(2, 1fr); } .form-grid { grid-template-columns: 1fr; } .sidebar { display: none; } .topbar, .main { left: 0; margin-left: 0; } }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="sidebar-logo">
        <a href="#" class="brand">
            <img src="<?= $baseUrl ?>/Vue/public/images/logo.png" alt="Logo" class="logo-img">
            <div>
                <div class="logo-text">Cre8Connect</div>
                <div class="logo-badge">ADMIN</div>
            </div>
        </a>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section-label">Dashboard</div>
        <a class="nav-item" href="#">
            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>Home
        </a>
        <a class="nav-item" href="#">
            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>Users
        </a>
        <a class="nav-item" href="#">
            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>Complaints
        </a>
        <div class="nav-section-label" style="margin-top:8px">Modules</div>
        <a class="nav-item" href="#">
            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>Offers & Applications
        </a>
        <a class="nav-item" href="#">
            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>Events & Forums
        </a>
        <a class="nav-item active" href="index.php">
            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>Campaigns
        </a>
        <a class="nav-item" href="#">
            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 10V11"/></svg>Products
        </a>
        <a class="nav-item" href="#">
            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>Posts & Comments
        </a>
    </nav>
    <div class="sidebar-footer">
        <div class="admin-card">
            <div class="admin-avatar">A</div>
            <div>
                <div class="admin-name">Administrator</div>
                <div class="admin-role">Super Admin</div>
            </div>
        </div>
    </div>
</aside>

<!-- TOPBAR -->
<div class="topbar">
    <div class="topbar-breadcrumb">
        <span>Cre8Connect</span><span class="sep">/</span>
        <span>Admin</span><span class="sep">/</span>
        <span class="current">Campaigns</span>
    </div>
    <div class="topbar-actions">
        <button class="theme-toggle" id="themeToggleBtn" type="button" title="Switch theme">
            <svg class="icon-sun" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><path stroke-linecap="round" d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
            <div class="toggle-track-pill" id="togglePill"><div class="toggle-knob" id="toggleKnob"></div></div>
            <svg class="icon-moon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
            <span class="toggle-label" id="themeLabel">Dark</span>
        </button>
        <div class="search-wrap">
            <svg class="search-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
            <input type="text" class="search-input" id="searchInput" placeholder="Search campaigns…">
        </div>
        <a href="<?= $baseUrl ?>/Vue/FrontOffice/campagne/index.php" target="_blank" class="btn-fo-link" title="View front office">
            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            View FO
        </a>
        <a href="?export_csv=1" class="btn-export">
            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Export CSV
        </a>
        <a href="?add=1" class="btn-add">
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            New Campaign
        </a>
    </div>
</div>

<!-- CONTENT -->
<main class="main">
<div class="content">

    <div class="page-header">
        <div>
            <div class="page-title">Campaign Management</div>
            <div class="page-subtitle">Supervise, add, edit and track all campaigns on the platform.</div>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>" id="alertMsg">
        <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <?php if (in_array($messageType, ['success','info'])): ?>
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            <?php else: ?>
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            <?php endif; ?>
        </svg>
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <!-- KPI STRIP -->
    <div class="kpi-strip">
        <div class="kpi-card">
            <div class="kpi-label">Total Active</div>
            <div class="kpi-value kpi-accent"><?= $totalCampagnes ?></div>
            <div class="kpi-sub">campaigns live</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Active Now</div>
            <div class="kpi-value kpi-success"><?= $nbActives ?></div>
            <div class="kpi-sub">running campaigns</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Drafts</div>
            <div class="kpi-value kpi-warning"><?= $nbBrouillons ?></div>
            <div class="kpi-sub">not yet published</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Ended</div>
            <div class="kpi-value kpi-info"><?= $nbTerminees ?></div>
            <div class="kpi-sub">completed</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Total Budget</div>
            <div class="kpi-value kpi-success"><?= number_format($budgetTotal, 0, ',', ' ') ?> €</div>
            <div class="kpi-sub">avg <?= number_format($budgetMoyen, 0) ?> €</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Archived</div>
            <div class="kpi-value kpi-danger"><?= $totalArchives ?></div>
            <div class="kpi-sub">hidden campaigns</div>
        </div>
    </div>

    <!-- TABS -->
    <div class="tabs">
        <button class="tab-btn active" onclick="switchTab('active', this)">Active (<?= $totalCampagnes ?>)</button>
        <button class="tab-btn" onclick="switchTab('archived', this)">Archived (<?= $totalArchives ?>)</button>
    </div>

    <!-- TAB: ACTIVE -->
    <div class="tab-panel active" id="tab-active">
        <div class="table-wrap">
            <div class="table-toolbar">
                <div class="table-title-row">
                    <div class="table-title">All Campaigns</div>
                    <div class="count-badge"><?= $totalCampagnes ?></div>
                </div>
                <div class="toolbar-actions">
                    <select class="filter-select" id="filterStatut" onchange="filterTable()">
                        <option value="">All statuses</option>
                        <?php foreach ($statuts as $s): ?>
                        <option value="<?= $s ?>"><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <form method="POST" id="formMasse">
                        <input type="hidden" name="action_masse" value="supprimer_selection">
                        <button type="button" class="btn-bulk-del" id="bulkDelBtn" onclick="bulkDelete()">
                            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Delete selected
                        </button>
                    </form>
                </div>
            </div>
            <table id="campagneTable">
                <thead>
                    <tr>
                        <th class="col-check"><input type="checkbox" id="selectAll" onclick="toggleSelectAll()"></th>
                        <th class="sort-col" onclick="sortTable(1)">Title ↕</th>
                        <th>Status</th>
                        <th class="sort-col" onclick="sortTable(3)">Dates ↕</th>
                        <th class="sort-col" onclick="sortTable(4)">Budget ↕</th>
                        <th>Brand</th>
                        <th>Objective</th>
                        <th class="col-actions">Actions</th>
                    </tr>
                </thead>
                <tbody id="campagneBody">
                <?php if (empty($liste)): ?>
                    <tr><td colspan="8" style="text-align:center;padding:32px;color:var(--text-dim);">No campaigns found. <a href="?add=1" style="color:var(--accent)">Add the first one →</a></td></tr>
                <?php else: ?>
                <?php foreach ($liste as $c): ?>
                <tr data-statut="<?= htmlspecialchars($c['statut']) ?>"
                    data-titre="<?= strtolower(htmlspecialchars($c['titreCampagne'])) ?>"
                    data-brand="<?= strtolower(htmlspecialchars($c['nomMarque'] ?? '')) ?>">
                    <td class="col-check"><input type="checkbox" class="row-check" value="<?= $c['idCampagne'] ?>" onchange="updateBulk()"></td>
                    <td class="col-title">
                        <div class="camp-title"><?= htmlspecialchars($c['titreCampagne']) ?></div>
                        <div class="camp-obj"><?= htmlspecialchars($c['objectif'] ?? '—') ?></div>
                    </td>
                    <td>
                        <select class="statut-select" onchange="changeStatut(<?= $c['idCampagne'] ?>, this.value)">
                            <?php foreach ($statuts as $s): ?>
                            <option value="<?= $s ?>" <?= $c['statut'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td class="col-dates">
                        📅 <?= $c['dateDebut'] ?? '—' ?><br>
                        🏁 <?= $c['dateFin']   ?? '—' ?>
                    </td>
                    <td class="col-budget"><?= number_format((float)$c['budget'], 2, ',', ' ') ?> €</td>
                    <td><span style="font-size:12px;color:var(--text-muted)"><?= htmlspecialchars($c['nomMarque'] ?? '—') ?></span></td>
                    <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:12px;color:var(--text-muted)"><?= htmlspecialchars($c['objectif'] ?? '—') ?></td>
                    <td class="col-actions">
                        <a href="?edit=<?= $c['idCampagne'] ?>#formAnchor" class="btn-table btn-edit">✏️ Edit</a>
                        <button class="btn-table btn-archive" onclick="toggleArchive(<?= $c['idCampagne'] ?>)">📦 Archive</button>
                        <button class="btn-table btn-delete" onclick="confirmDelete(<?= $c['idCampagne'] ?>, '<?= htmlspecialchars(addslashes($c['titreCampagne'])) ?>')">🗑</button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB: ARCHIVED -->
    <div class="tab-panel" id="tab-archived">
        <div class="table-wrap">
            <div class="table-toolbar">
                <div class="table-title-row">
                    <div class="table-title">Archived Campaigns</div>
                    <div class="count-badge"><?= $totalArchives ?></div>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Dates</th>
                        <th>Budget</th>
                        <th>Brand</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($listeArchives)): ?>
                    <tr><td colspan="6" style="text-align:center;padding:32px;color:var(--text-dim);">No archived campaigns.</td></tr>
                <?php else: ?>
                <?php foreach ($listeArchives as $c): ?>
                <tr>
                    <td>
                        <div class="camp-title" style="opacity:.7"><?= htmlspecialchars($c['titreCampagne']) ?></div>
                    </td>
                    <td><span class="badge <?= statutClass($c['statut']) ?>"><?= statutLabel($c['statut']) ?></span></td>
                    <td class="col-dates">📅 <?= $c['dateDebut'] ?? '—' ?> → <?= $c['dateFin'] ?? '—' ?></td>
                    <td class="col-budget"><?= number_format((float)$c['budget'], 2, ',', ' ') ?> €</td>
                    <td style="font-size:12px;color:var(--text-muted)"><?= htmlspecialchars($c['nomMarque'] ?? '—') ?></td>
                    <td>
                        <button class="btn-table btn-unarch" onclick="toggleArchive(<?= $c['idCampagne'] ?>)">🔁 Restore</button>
                        <button class="btn-table btn-delete" onclick="confirmDelete(<?= $c['idCampagne'] ?>, '<?= htmlspecialchars(addslashes($c['titreCampagne'])) ?>')">🗑 Delete</button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- FORM SECTION -->
    <div class="form-section" id="formAnchor">
        <div class="form-section-header">
            <div class="form-section-title"><?= $campagneUpdate ? '✏️ Edit Campaign' : '➕ Add a Campaign' ?></div>
            <div class="form-section-sub"><?= $campagneUpdate ? 'Update the fields below and save your changes.' : 'Fill in the campaign details to create it on the platform.' ?></div>
        </div>

        <?php if ($campagneUpdate): ?>
        <div class="edit-banner">
            <span>Editing: <strong><?= htmlspecialchars($campagneUpdate['titreCampagne']) ?></strong></span>
            <a href="index.php">✕ Cancel</a>
        </div>
        <?php endif; ?>

        <!-- Completion bar -->
        <div class="completion-wrap">
            <div class="completion-top">
                <span style="font-size:12px;color:var(--text-muted)">Form completeness</span>
                <span style="font-size:12px;font-weight:600;color:var(--accent)" id="completionPct">0%</span>
            </div>
            <div class="completion-bar"><div class="completion-fill" id="completionFill" style="width:0%"></div></div>
            <div class="completion-hint" id="completionHint" style="font-size:11px;color:var(--text-dim);margin-top:4px">Fill all fields to complete your campaign.</div>
        </div>

        <form method="POST" action="index.php" id="campagneForm" novalidate>
            <input type="hidden" name="action" value="<?= $campagneUpdate ? 'update' : 'add' ?>">
            <?php if ($campagneUpdate): ?>
            <input type="hidden" name="id" value="<?= $campagneUpdate['idCampagne'] ?>">
            <input type="hidden" name="estArchive" value="<?= intval($campagneUpdate['estArchive'] ?? 0) ?>">
            <?php endif; ?>

            <!-- Title + Status -->
            <div class="form-grid" style="margin-bottom:14px">
                <div class="form-group">
                    <label for="titre">Campaign Title * <span style="font-size:11px;color:var(--text-dim)" id="titreCounter">0 / 100</span></label>
                    <input type="text" id="titre" name="titre" class="form-input" maxlength="100"
                           value="<?= $campagneUpdate ? htmlspecialchars($campagneUpdate['titreCampagne']) : '' ?>">
                    <div class="error-msg" id="titreError">Title is required (min. 2 characters).</div>
                </div>
                <div class="form-group">
                    <label for="statut">Status</label>
                    <select id="statut" name="statut" class="form-input">
                        <?php foreach ($statuts as $s): ?>
                        <option value="<?= $s ?>" <?= ($campagneUpdate && $campagneUpdate['statut'] === $s) ? 'selected' : '' ?>>
                            <?= ucfirst($s) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Description -->
            <div class="form-group" style="margin-bottom:14px">
                <label for="description">Description * <span style="font-size:11px;color:var(--text-dim)" id="descCounter">0 / 600</span></label>
                <textarea id="description" name="description" class="form-input" maxlength="600"><?= $campagneUpdate ? htmlspecialchars($campagneUpdate['description']) : '' ?></textarea>
                <div class="error-msg" id="descError">Description is required (min. 10 characters).</div>
            </div>

            <!-- Objective -->
            <div class="form-group" style="margin-bottom:14px">
                <label for="objectif">Objective / Goal</label>
                <input type="text" id="objectif" name="objectif" class="form-input" maxlength="200"
                       value="<?= $campagneUpdate ? htmlspecialchars($campagneUpdate['objectif'] ?? '') : '' ?>">
            </div>

            <!-- Dates + Budget -->
            <div class="form-grid-3" style="margin-bottom:14px">
                <div class="form-group">
                    <label for="dateDebut">Start Date *</label>
                    <input type="text" id="dateDebut" name="dateDebut" class="form-input" placeholder="YYYY-MM-DD"
                           value="<?= $campagneUpdate ? htmlspecialchars($campagneUpdate['dateDebut'] ?? '') : '' ?>">
                    <div class="error-msg" id="dateDebutError">Enter a valid start date (YYYY-MM-DD).</div>
                </div>
                <div class="form-group">
                    <label for="dateFin">End Date *</label>
                    <input type="text" id="dateFin" name="dateFin" class="form-input" placeholder="YYYY-MM-DD"
                           value="<?= $campagneUpdate ? htmlspecialchars($campagneUpdate['dateFin'] ?? '') : '' ?>">
                    <div class="error-msg" id="dateFinError">End date must be after start date.</div>
                </div>
                <div class="form-group">
                    <label for="budget">Budget (€) *</label>
                    <div class="input-with-prefix">
                        <span class="prefix">€</span>
                        <input type="text" id="budget" name="budget" class="form-input" autocomplete="off"
                               value="<?= $campagneUpdate ? htmlspecialchars($campagneUpdate['budget'] ?? '') : '' ?>">
                    </div>
                    <div class="error-msg" id="budgetError">Budget is required (number ≥ 0, max 2 decimals).</div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-submit" id="submitBtn">
                    <?= $campagneUpdate ? '💾 Save Changes' : '✅ Add Campaign' ?>
                </button>
                <?php if ($campagneUpdate): ?>
                <a href="index.php" class="btn-cancel-form">Cancel</a>
                <?php endif; ?>
                <span style="font-size:12px;color:var(--text-dim);margin-left:auto">* Required fields</span>
            </div>
        </form>
    </div>

</div>
</main>

<!-- CONFIRM MODAL -->
<div class="modal-overlay" id="confirmModal">
    <div class="modal-box">
        <div class="modal-title">🗑 Confirm deletion</div>
        <div class="modal-text" id="modalText">Are you sure you want to delete this campaign? This action is irreversible.</div>
        <div class="modal-actions">
            <button class="btn-modal-cancel" onclick="closeModal()">Cancel</button>
            <a href="#" class="btn-modal-confirm" id="modalConfirmLink">Delete</a>
        </div>
    </div>
</div>

<script>
// ── THEME ──────────────────────────────────────────────────────────────────────
const body  = document.body;
const label = document.getElementById('themeLabel');
let isLight = localStorage.getItem('adminTheme') === 'light';
function applyTheme() {
    body.classList.toggle('light-mode', isLight);
    label.textContent = isLight ? 'Light' : 'Dark';
}
applyTheme();
document.getElementById('themeToggleBtn').addEventListener('click', () => {
    isLight = !isLight;
    localStorage.setItem('adminTheme', isLight ? 'light' : 'dark');
    applyTheme();
});

// ── ALERT AUTO-HIDE ────────────────────────────────────────────────────────────
const alertEl = document.getElementById('alertMsg');
if (alertEl) setTimeout(() => alertEl.style.display = 'none', 4500);

// ── TABS ───────────────────────────────────────────────────────────────────────
function switchTab(name, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    btn.classList.add('active');
}

// ── SEARCH / FILTER TABLE ──────────────────────────────────────────────────────
document.getElementById('searchInput').addEventListener('input', filterTable);
function filterTable() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    const s = document.getElementById('filterStatut').value;
    document.querySelectorAll('#campagneBody tr').forEach(row => {
        const titre = row.dataset.titre || '';
        const brand = row.dataset.brand || '';
        const statut = row.dataset.statut || '';
        const matchQ = !q || titre.includes(q) || brand.includes(q);
        const matchS = !s || statut === s;
        row.style.display = matchQ && matchS ? '' : 'none';
    });
}

// ── SORT TABLE ─────────────────────────────────────────────────────────────────
function sortTable(col) {
    const tbody = document.getElementById('campagneBody');
    const rows  = Array.from(tbody.querySelectorAll('tr'));
    let asc = tbody.dataset['sort' + col] !== 'asc';
    tbody.dataset['sort' + col] = asc ? 'asc' : 'desc';
    rows.sort((a, b) => {
        const av = a.cells[col]?.textContent.trim() || '';
        const bv = b.cells[col]?.textContent.trim() || '';
        if (col === 4) return asc ? parseFloat(av) - parseFloat(bv) : parseFloat(bv) - parseFloat(av);
        return asc ? av.localeCompare(bv) : bv.localeCompare(av);
    });
    rows.forEach(r => tbody.appendChild(r));
}

// ── CHECKBOX BULK ──────────────────────────────────────────────────────────────
function toggleSelectAll() {
    const all = document.getElementById('selectAll').checked;
    document.querySelectorAll('.row-check').forEach(cb => cb.checked = all);
    updateBulk();
}
function updateBulk() {
    const checked = document.querySelectorAll('.row-check:checked');
    const btn = document.getElementById('bulkDelBtn');
    btn.classList.toggle('visible', checked.length > 0);
}
function bulkDelete() {
    const ids = Array.from(document.querySelectorAll('.row-check:checked')).map(cb => cb.value);
    if (!ids.length) return;
    if (!confirm('Delete ' + ids.length + ' campaign(s)?')) return;
    const form = document.getElementById('formMasse');
    ids.forEach(id => {
        const inp = document.createElement('input');
        inp.type = 'hidden'; inp.name = 'selected_ids[]'; inp.value = id;
        form.appendChild(inp);
    });
    form.submit();
}

// ── AJAX ARCHIVE ───────────────────────────────────────────────────────────────
function toggleArchive(id) {
    fetch('index.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=archive&id=' + id
    }).then(() => location.reload());
}

// ── AJAX STATUT ────────────────────────────────────────────────────────────────
function changeStatut(id, statut) {
    fetch('index.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=statut&id=' + id + '&statut=' + encodeURIComponent(statut)
    });
}

// ── CONFIRM DELETE MODAL ───────────────────────────────────────────────────────
function confirmDelete(id, titre) {
    document.getElementById('modalText').textContent = 'Delete campaign "' + titre + '"? This is irreversible.';
    document.getElementById('modalConfirmLink').href = 'index.php?delete=' + id;
    document.getElementById('confirmModal').classList.add('open');
}
function closeModal() {
    document.getElementById('confirmModal').classList.remove('open');
}
document.getElementById('confirmModal').addEventListener('click', e => {
    if (e.target === document.getElementById('confirmModal')) closeModal();
});

// ── FORM VALIDATION ────────────────────────────────────────────────────────────
document.getElementById('campagneForm').addEventListener('submit', function(e) {
    let valid = true;

    // Titre
    const titre = document.getElementById('titre').value.trim();
    const titreErr = document.getElementById('titreError');
    if (titre.length < 2) {
        titreErr.classList.add('visible');
        document.getElementById('titre').classList.add('error-field');
        valid = false;
    } else {
        titreErr.classList.remove('visible');
        document.getElementById('titre').classList.remove('error-field');
    }

    // Description
    const desc = document.getElementById('description').value.trim();
    const descErr = document.getElementById('descError');
    if (desc.length < 10) {
        descErr.classList.add('visible');
        document.getElementById('description').classList.add('error-field');
        valid = false;
    } else {
        descErr.classList.remove('visible');
        document.getElementById('description').classList.remove('error-field');
    }

    // Budget
    const budgetVal = document.getElementById('budget').value.trim().replace(',', '.');
    const budgetErr = document.getElementById('budgetError');
    const budgetNum = parseFloat(budgetVal);
    const budgetOk = budgetVal !== '' && !isNaN(budgetNum) && budgetNum >= 0 && /^\d+([.,]\d{1,2})?$/.test(document.getElementById('budget').value.trim());
    if (!budgetOk) {
        budgetErr.classList.add('visible');
        document.getElementById('budget').classList.add('error-field');
        valid = false;
    } else {
        budgetErr.classList.remove('visible');
        document.getElementById('budget').classList.remove('error-field');
        document.getElementById('budget').value = budgetVal;
    }

    // Dates
    const dateDebut = document.getElementById('dateDebut').value.trim();
    const dateFin   = document.getElementById('dateFin').value.trim();
    const reDate = /^\d{4}-\d{2}-\d{2}$/;
    const dateDebutErr = document.getElementById('dateDebutError');
    const dateFinErr   = document.getElementById('dateFinError');

    if (dateDebut && !reDate.test(dateDebut)) {
        dateDebutErr.classList.add('visible');
        document.getElementById('dateDebut').classList.add('error-field');
        valid = false;
    } else {
        dateDebutErr.classList.remove('visible');
        document.getElementById('dateDebut').classList.remove('error-field');
    }

    if (dateFin && !reDate.test(dateFin)) {
        dateFinErr.classList.add('visible');
        document.getElementById('dateFin').classList.add('error-field');
        valid = false;
    } else if (dateDebut && dateFin && dateFin < dateDebut) {
        dateFinErr.textContent = 'End date must be after start date.';
        dateFinErr.classList.add('visible');
        document.getElementById('dateFin').classList.add('error-field');
        valid = false;
    } else {
        dateFinErr.classList.remove('visible');
        document.getElementById('dateFin').classList.remove('error-field');
    }

    if (!valid) e.preventDefault();
});

// ── CHAR COUNTERS ──────────────────────────────────────────────────────────────
function setupCounter(inputId, counterId, max) {
    const el = document.getElementById(inputId);
    const co = document.getElementById(counterId);
    if (!el || !co) return;
    const update = () => { co.textContent = el.value.length + ' / ' + max; };
    el.addEventListener('input', update);
    update();
}
setupCounter('titre', 'titreCounter', 100);
setupCounter('description', 'descCounter', 600);

// ── BUDGET: restrict input ─────────────────────────────────────────────────────
document.getElementById('budget').addEventListener('input', function() {
    let v = this.value.replace(/[^0-9.,]/g, '');
    const parts = v.split(/[.,]/);
    if (parts.length > 2) v = parts[0] + '.' + parts.slice(1).join('');
    if (parts[1] && parts[1].length > 2) v = parts[0] + '.' + parts[1].substring(0, 2);
    this.value = v;
});

// ── DATE MASK ──────────────────────────────────────────────────────────────────
function applyDateMask(inputId) {
    document.getElementById(inputId).addEventListener('input', function() {
        let v = this.value.replace(/[^0-9]/g, '');
        if (v.length > 4 && v.length <= 6) v = v.slice(0,4) + '-' + v.slice(4);
        else if (v.length > 6) v = v.slice(0,4) + '-' + v.slice(4,6) + '-' + v.slice(6,8);
        this.value = v;
    });
}
applyDateMask('dateDebut');
applyDateMask('dateFin');

// ── COMPLETION BAR ─────────────────────────────────────────────────────────────
function updateCompletion() {
    const fields = [
        document.getElementById('titre').value.trim(),
        document.getElementById('description').value.trim(),
        document.getElementById('budget').value.trim(),
        document.getElementById('dateDebut').value.trim(),
        document.getElementById('dateFin').value.trim(),
        document.getElementById('objectif').value.trim(),
    ];
    const filled = fields.filter(f => f.length > 0).length;
    const pct = Math.round((filled / fields.length) * 100);
    document.getElementById('completionFill').style.width = pct + '%';
    document.getElementById('completionPct').textContent = pct + '%';
    document.getElementById('completionHint').textContent = pct === 100 ? '✅ All fields filled! Your campaign is ready.' : 'Fill all fields to maximize visibility.';
}
['titre','description','budget','dateDebut','dateFin','objectif'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('input', updateCompletion);
});
updateCompletion();

// ── SCROLL TO FORM IF EDIT ──────────────────────────────────────────────────────
<?php if ($campagneUpdate): ?>
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('formAnchor').scrollIntoView({ behavior: 'smooth', block: 'start' });
});
<?php endif; ?>
</script>
</body>
</html>