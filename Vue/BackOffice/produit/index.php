<?php
require_once '../../../Controleur/produitC.php';
require_once '../../../Modele/produit.php';

$produitC = new ProduitC();
$message = '';
$messageType = '';
$baseUrl = '/projet/Esprit-PW-2A22-2526-Devcore';

// DELETE SINGLE
if (isset($_GET['delete'])) {
    $produitC->supprimerProduit($_GET['delete']);
    header('Location: index.php?deleted=1');
    exit;
}

// DELETE MULTIPLE
if (isset($_POST['action_masse']) && $_POST['action_masse'] === 'supprimer_selection') {
    $ids = $_POST['selected_ids'] ?? [];
    foreach ($ids as $id) {
        $produitC->supprimerProduit(intval($id));
    }
    header('Location: index.php?deleted_masse=' . count($ids));
    exit;
}

// ADD
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $nomImage = $produitC->gererUploadImage($_FILES['image'] ?? null);
    $produit = new Produit(null, trim($_POST['nom']), trim($_POST['description']), trim($_POST['caracteristiques']), floatval($_POST['prix']), 1, $nomImage);
    $produitC->ajouterProduit($produit);
    header('Location: index.php?added=1');
    exit;
}

// UPDATE
if (isset($_POST['action']) && $_POST['action'] === 'update') {
    $ancienProduit = $produitC->recupererProduit(intval($_POST['id']));
    $nomImage = $produitC->gererUploadImage($_FILES['image'] ?? null, $ancienProduit['image'] ?? null);
    $produit = new Produit(null, trim($_POST['nom']), trim($_POST['description']), trim($_POST['caracteristiques']), floatval($_POST['prix']), null, $nomImage);
    $produitC->modifierProduit($produit, intval($_POST['id']));
    header('Location: index.php?updated=1');
    exit;
}

// INLINE PRICE UPDATE
if (isset($_POST['action']) && $_POST['action'] === 'update_prix') {
    $ancienProduit = $produitC->recupererProduit(intval($_POST['id']));
    if ($ancienProduit) {
        $produit = new Produit(null, $ancienProduit['nomProduit'], $ancienProduit['description'], $ancienProduit['caracteristiques'], floatval($_POST['prix']), null, $ancienProduit['image']);
        $produitC->modifierProduit($produit, intval($_POST['id']));
    }
    header('Location: index.php?updated=1');
    exit;
}

if (isset($_GET['added']))        { $message = "Produit ajouté avec succès.";                                        $messageType = "success"; }
if (isset($_GET['updated']))      { $message = "Produit mis à jour avec succès.";                                    $messageType = "info"; }
if (isset($_GET['deleted']))      { $message = "Produit supprimé avec succès.";                                      $messageType = "danger"; }
if (isset($_GET['deleted_masse'])) { $message = $_GET['deleted_masse'] . " produit(s) supprimé(s) avec succès.";   $messageType = "danger"; }

$liste = $produitC->afficherProduits();
$produitUpdate = null;
if (isset($_GET['edit'])) {
    $produitUpdate = $produitC->recupererProduit(intval($_GET['edit']));
}

// Stats
$totalProduits   = count($liste);
$allPrix         = array_column($liste, 'prix');
$prixMoyen       = $totalProduits > 0 ? array_sum($allPrix) / $totalProduits : 0;
$prixMax         = $totalProduits > 0 ? max($allPrix) : 0;
$valeurCatalogue = array_sum($allPrix);
$sanImage        = count(array_filter($liste, fn($p) => empty($p['image'])));
$produitPlusCher = $totalProduits > 0 ? $liste[array_search($prixMax, $allPrix)] : null;

// Export CSV
if (isset($_GET['export_csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="produits_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Nom', 'Description', 'Caractéristiques', 'Prix (DT)', 'Image']);
    foreach ($liste as $p) {
        fputcsv($out, [$p['idProduit'], $p['nomProduit'], $p['description'], $p['caracteristiques'], $p['prix'], $p['image'] ?? '']);
    }
    fclose($out);
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — Gestion Produits | Cre8Connect</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg-base:      #0f1117;
            --bg-surface:   #161b27;
            --bg-card:      #1c2235;
            --bg-card-alt:  #212840;
            --bg-input:     #111827;
            --border:       rgba(255,255,255,0.07);
            --border-focus: rgba(139,92,246,0.5);
            --text-primary: #e2e8f0;
            --text-muted:   #7c8ba1;
            --text-dim:     #4a5568;
            --accent:       #8b5cf6;
            --accent-soft:  rgba(139,92,246,0.12);
            --accent-hover: #7c3aed;
            --success:      #10b981;
            --success-soft: rgba(16,185,129,0.12);
            --danger:       #ef4444;
            --danger-soft:  rgba(239,68,68,0.10);
            --warning:      #f59e0b;
            --warning-soft: rgba(245,158,11,0.12);
            --info:         #3b82f6;
            --info-soft:    rgba(59,130,246,0.12);
            --radius-sm:    6px;
            --radius-md:    10px;
            --radius-lg:    14px;
            --sidebar-w:    240px;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--bg-base);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            font-size: 14px;
            line-height: 1.5;
        }

        /* ── SIDEBAR ── */
        .sidebar {
            width: var(--sidebar-w);
            background: var(--bg-surface);
            border-right: 1px solid var(--border);
            display: flex; flex-direction: column;
            position: fixed; top: 0; left: 0; bottom: 0; z-index: 100;
        }
        .sidebar-logo { padding: 18px 20px; border-bottom: 1px solid var(--border); }
        .sidebar-logo .brand { display: flex; align-items: center; gap: 10px; text-decoration: none; }
        .logo-img { width: 34px; height: 34px; object-fit: contain; border-radius: var(--radius-sm); flex-shrink: 0; }
        .logo-text { font-size: 15px; font-weight: 700; color: var(--text-primary); }
        .logo-badge { font-size: 9px; font-weight: 600; letter-spacing: .05em; color: var(--accent); background: var(--accent-soft); padding: 2px 6px; border-radius: 20px; margin-top: 1px; }
        .sidebar-nav { flex: 1; padding: 12px 10px; overflow-y: auto; }
        .nav-section-label { font-size: 10px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--text-dim); padding: 10px 10px 6px; }
        .nav-item { display: flex; align-items: center; gap: 10px; padding: 8px 10px; border-radius: var(--radius-sm); color: var(--text-muted); text-decoration: none; font-size: 13px; font-weight: 450; transition: background .15s, color .15s; cursor: pointer; margin-bottom: 2px; }
        .nav-item:hover { background: rgba(255,255,255,0.04); color: var(--text-primary); }
        .nav-item.active { background: var(--accent-soft); color: var(--accent); font-weight: 500; }
        .nav-icon { width: 18px; height: 18px; opacity: .8; flex-shrink: 0; }
        .sidebar-footer { padding: 12px; border-top: 1px solid var(--border); }
        .admin-card { display: flex; align-items: center; gap: 10px; padding: 8px 10px; border-radius: var(--radius-sm); background: var(--bg-card); }
        .admin-avatar { width: 30px; height: 30px; border-radius: 50%; background: var(--accent); display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 600; color: #fff; flex-shrink: 0; }
        .admin-name { font-size: 12px; font-weight: 500; }
        .admin-role { font-size: 11px; color: var(--text-muted); }

        /* ── TOPBAR ── */
        .topbar {
            position: fixed; top: 0;
            left: var(--sidebar-w); right: 0; height: 58px;
            background: var(--bg-surface); border-bottom: 1px solid var(--border);
            display: flex; align-items: center; padding: 0 28px; gap: 16px; z-index: 90;
        }
        .topbar-breadcrumb { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--text-muted); }
        .topbar-breadcrumb .sep { opacity: .4; }
        .topbar-breadcrumb .current { color: var(--text-primary); font-weight: 500; }
        .topbar-actions { margin-left: auto; display: flex; align-items: center; gap: 10px; }

        .btn-add { display: flex; align-items: center; gap: 7px; background: var(--accent); color: #fff; border: none; padding: 7px 14px; border-radius: var(--radius-sm); font-size: 13px; font-weight: 500; cursor: pointer; text-decoration: none; transition: background .15s; }
        .btn-add:hover { background: var(--accent-hover); }

        .btn-export { display: flex; align-items: center; gap: 6px; background: var(--success-soft); color: var(--success); border: 1px solid rgba(16,185,129,.2); padding: 7px 12px; border-radius: var(--radius-sm); font-size: 12px; font-weight: 500; cursor: pointer; text-decoration: none; transition: background .15s; }
        .btn-export:hover { background: rgba(16,185,129,.2); }

        .search-wrap { position: relative; }
        .search-input { background: var(--bg-input); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 7px 12px 7px 34px; color: var(--text-primary); font-size: 13px; width: 220px; transition: border-color .2s; outline: none; }
        .search-input::placeholder { color: var(--text-dim); }
        .search-input:focus { border-color: var(--border-focus); }
        .search-icon { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--text-dim); pointer-events: none; width: 15px; height: 15px; }

        /* ── MAIN ── */
        .main { margin-left: var(--sidebar-w); padding-top: 58px; flex: 1; min-height: 100vh; }
        .content { padding: 28px 28px 60px; }

        .page-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
        .page-title { font-size: 20px; font-weight: 600; color: var(--text-primary); }
        .page-subtitle { font-size: 13px; color: var(--text-muted); margin-top: 3px; }

        /* ── ALERT ── */
        .alert { display: flex; align-items: center; gap: 10px; padding: 11px 16px; border-radius: var(--radius-md); font-size: 13px; margin-bottom: 20px; font-weight: 450; }
        .alert-success { background: var(--success-soft); color: var(--success); border: 1px solid rgba(16,185,129,.2); }
        .alert-info    { background: var(--info-soft);    color: var(--info);    border: 1px solid rgba(59,130,246,.2); }
        .alert-danger  { background: var(--danger-soft);  color: var(--danger);  border: 1px solid rgba(239,68,68,.2); }

        /* ── KPI STRIP ── */
        .kpi-strip { display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px; margin-bottom: 24px; }
        .kpi-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 14px 16px; display: flex; flex-direction: column; gap: 4px; position: relative; overflow: hidden; }
        .kpi-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px; }
        .kpi-card.kpi-accent::before  { background: var(--accent); }
        .kpi-card.kpi-info::before    { background: var(--info); }
        .kpi-card.kpi-success::before { background: var(--success); }
        .kpi-card.kpi-warning::before { background: var(--warning); }
        .kpi-card.kpi-danger::before  { background: var(--danger); }
        .kpi-label { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; color: var(--text-muted); }
        .kpi-value { font-size: 22px; font-weight: 600; color: var(--text-primary); line-height: 1.1; }
        .kpi-sub { font-size: 11px; color: var(--text-muted); }

        /* ── HIGHLIGHT CARD ── */
        .highlight-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 16px 20px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .highlight-img { width: 54px; height: 54px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--border); flex-shrink: 0; }
        .highlight-img-empty { width: 54px; height: 54px; background: var(--bg-card-alt); border: 1px dashed var(--border); border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; font-size: 22px; color: var(--text-dim); flex-shrink: 0; }
        .highlight-info { flex: 1; }
        .highlight-label { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: .07em; color: var(--warning); margin-bottom: 3px; }
        .highlight-name { font-size: 15px; font-weight: 600; color: var(--text-primary); }
        .highlight-sub { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
        .highlight-badge { font-size: 15px; font-weight: 700; color: var(--warning); background: var(--warning-soft); padding: 6px 14px; border-radius: var(--radius-sm); white-space: nowrap; }

        /* ── FILTER BAR ── */
        .filter-bar {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 14px 18px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }
        .filter-group { display: flex; flex-direction: column; gap: 5px; }
        .filter-label { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; color: var(--text-dim); }
        .filter-select { background: var(--bg-input); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 6px 10px; color: var(--text-primary); font-size: 12px; outline: none; cursor: pointer; transition: border-color .2s; }
        .filter-select:focus { border-color: var(--border-focus); }
        .price-range-wrap { display: flex; align-items: center; gap: 8px; }
        .price-input { background: var(--bg-input); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 6px 8px; color: var(--text-primary); font-size: 12px; width: 80px; outline: none; transition: border-color .2s; }
        .price-input:focus { border-color: var(--border-focus); }
        .price-sep { color: var(--text-dim); font-size: 12px; }
        .btn-filter { background: var(--accent-soft); color: var(--accent); border: 1px solid rgba(139,92,246,.2); border-radius: var(--radius-sm); padding: 6px 12px; font-size: 12px; font-weight: 500; cursor: pointer; transition: background .15s; }
        .btn-filter:hover { background: rgba(139,92,246,.2); }
        .btn-reset-filter { background: transparent; color: var(--text-muted); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 6px 10px; font-size: 12px; cursor: pointer; transition: all .15s; }
        .btn-reset-filter:hover { color: var(--text-primary); border-color: rgba(255,255,255,.15); }
        .image-filter-row { display: flex; gap: 6px; }
        .chip { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 500; cursor: pointer; border: 1px solid var(--border); background: transparent; color: var(--text-muted); transition: all .15s; }
        .chip:hover { border-color: var(--accent); color: var(--accent); }
        .chip.active { background: var(--accent-soft); border-color: rgba(139,92,246,.3); color: var(--accent); }

        /* ── BULK BAR ── */
        .bulk-bar {
            background: var(--accent-soft);
            border: 1px solid rgba(139,92,246,.2);
            border-radius: var(--radius-md);
            padding: 10px 16px;
            display: none;
            align-items: center;
            gap: 14px;
            margin-bottom: 12px;
            font-size: 13px;
        }
        .bulk-bar.visible { display: flex; }
        .bulk-count { color: var(--accent); font-weight: 500; }
        .btn-bulk-delete { display: flex; align-items: center; gap: 5px; background: var(--danger-soft); color: var(--danger); border: 1px solid rgba(239,68,68,.2); border-radius: var(--radius-sm); padding: 5px 12px; font-size: 12px; font-weight: 500; cursor: pointer; margin-left: auto; }
        .btn-bulk-delete:hover { background: rgba(239,68,68,.18); }
        .btn-bulk-cancel { background: transparent; color: var(--text-muted); border: none; font-size: 12px; cursor: pointer; padding: 5px 8px; }
        .btn-bulk-cancel:hover { color: var(--text-primary); }

        /* ── FORM PANEL ── */
        .form-panel { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-lg); margin-bottom: 24px; overflow: hidden; }
        .form-panel-header { display: flex; align-items: center; gap: 10px; padding: 14px 20px; border-bottom: 1px solid var(--border); }
        .form-panel-title { font-size: 14px; font-weight: 600; }
        .form-panel-badge { font-size: 11px; font-weight: 500; padding: 2px 8px; border-radius: 20px; }
        .badge-add  { background: var(--success-soft); color: var(--success); }
        .badge-edit { background: var(--warning-soft); color: var(--warning); }
        .form-body { padding: 20px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .form-col-full { grid-column: 1 / -1; }
        .form-group { display: flex; flex-direction: column; gap: 5px; }
        .form-label { font-size: 12px; font-weight: 500; color: var(--text-muted); }
        .form-control { background: var(--bg-input); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 9px 12px; color: var(--text-primary); font-size: 13px; font-family: inherit; transition: border-color .2s; width: 100%; outline: none; }
        .form-control::placeholder { color: var(--text-dim); }
        .form-control:focus { border-color: var(--border-focus); }
        textarea.form-control { resize: vertical; min-height: 80px; }
        .upload-admin { border: 1.5px dashed rgba(139,92,246,0.3); border-radius: var(--radius-sm); background: var(--bg-input); padding: 16px; cursor: pointer; transition: border-color .2s, background .2s; position: relative; text-align: center; }
        .upload-admin:hover { border-color: var(--accent); background: var(--accent-soft); }
        .upload-admin input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }
        .upload-admin-text { font-size: 12px; color: var(--text-muted); }
        .upload-admin-text strong { color: var(--accent); }
        .current-image-preview { display: flex; align-items: center; gap: 12px; padding: 8px 10px; background: rgba(139,92,246,0.08); border: 1px solid rgba(139,92,246,0.2); border-radius: var(--radius-sm); margin-bottom: 8px; }
        .current-image-preview img { width: 48px; height: 48px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--border); }
        .current-image-preview span { font-size: 12px; color: var(--text-muted); }
        .form-actions { display: flex; gap: 10px; margin-top: 6px; }
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 18px; border-radius: var(--radius-sm); font-size: 13px; font-weight: 500; cursor: pointer; border: none; text-decoration: none; transition: .15s; font-family: inherit; }
        .btn-primary { background: var(--accent); color: #fff; }
        .btn-primary:hover { background: var(--accent-hover); }
        .btn-ghost { background: transparent; color: var(--text-muted); border: 1px solid var(--border); }
        .btn-ghost:hover { background: rgba(255,255,255,.04); color: var(--text-primary); }

        /* ── TABLE PANEL ── */
        .table-panel { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-lg); overflow: hidden; }
        .table-panel-header { display: flex; align-items: center; padding: 14px 20px; border-bottom: 1px solid var(--border); gap: 12px; flex-wrap: wrap; }
        .table-panel-title { font-size: 14px; font-weight: 600; flex: 1; }
        .count-badge { font-size: 11px; font-weight: 600; background: var(--accent-soft); color: var(--accent); padding: 2px 9px; border-radius: 20px; }
        .per-page-select { background: var(--bg-input); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 4px 8px; color: var(--text-muted); font-size: 12px; outline: none; cursor: pointer; }

        table { width: 100%; border-collapse: collapse; }
        thead th { background: var(--bg-card-alt); padding: 10px 16px; text-align: left; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; color: var(--text-muted); border-bottom: 1px solid var(--border); cursor: pointer; user-select: none; white-space: nowrap; }
        thead th:hover { color: var(--text-primary); }
        thead th.sort-asc::after  { content: ' ↑'; color: var(--accent); }
        thead th.sort-desc::after { content: ' ↓'; color: var(--accent); }
        thead th.no-sort { cursor: default; }
        tbody tr { border-bottom: 1px solid var(--border); transition: background .12s; }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: rgba(255,255,255,.025); }
        tbody tr.selected { background: rgba(139,92,246,.07); }
        tbody td { padding: 11px 16px; font-size: 13px; color: var(--text-primary); vertical-align: middle; }

        .td-check input[type="checkbox"] { accent-color: var(--accent); width: 14px; height: 14px; cursor: pointer; }
        .td-id { color: var(--text-dim); font-size: 12px; font-family: monospace; }
        .td-name { font-weight: 500; }
        .td-desc { color: var(--text-muted); max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .td-carac { color: var(--text-dim); font-size: 12px; max-width: 130px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

        .prix-badge { display: inline-flex; align-items: center; background: var(--success-soft); color: var(--success); padding: 3px 9px; border-radius: 20px; font-size: 12px; font-weight: 600; }

        /* Inline price edit */
        .prix-inline-form { display: flex; align-items: center; gap: 5px; }
        .prix-inline-input { background: var(--bg-input); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 4px 8px; color: var(--text-primary); font-size: 12px; width: 80px; outline: none; }
        .prix-inline-input:focus { border-color: var(--border-focus); }
        .btn-inline-save { background: var(--success-soft); color: var(--success); border: none; border-radius: var(--radius-sm); padding: 4px 8px; font-size: 11px; cursor: pointer; }
        .btn-inline-cancel { background: transparent; color: var(--text-muted); border: none; font-size: 11px; cursor: pointer; padding: 4px 6px; }

        .td-img img { width: 42px; height: 42px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--border); cursor: pointer; transition: opacity .15s; }
        .td-img img:hover { opacity: .75; }
        .td-img-empty { width: 42px; height: 42px; background: var(--bg-card-alt); border: 1px dashed var(--border); border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; font-size: 16px; color: var(--text-dim); }

        .img-badge { display: inline-flex; align-items: center; gap: 3px; font-size: 10px; font-weight: 500; padding: 2px 6px; border-radius: 4px; }
        .img-ok   { background: var(--success-soft); color: var(--success); }
        .img-miss { background: var(--danger-soft);  color: var(--danger); }

        .action-group { display: flex; align-items: center; gap: 5px; }
        .btn-action { display: inline-flex; align-items: center; gap: 4px; padding: 4px 9px; border-radius: var(--radius-sm); font-size: 11px; font-weight: 500; cursor: pointer; text-decoration: none; border: 1px solid transparent; transition: .15s; }
        .btn-view   { background: rgba(139,92,246,.1); color: var(--accent); border-color: rgba(139,92,246,.2); }
        .btn-view:hover { background: rgba(139,92,246,.2); }
        .btn-edit-a { background: var(--info-soft); color: var(--info); border-color: rgba(59,130,246,.2); }
        .btn-edit-a:hover { background: rgba(59,130,246,.2); }
        .btn-delete { background: var(--danger-soft); color: var(--danger); border-color: rgba(239,68,68,.2); }
        .btn-delete:hover { background: rgba(239,68,68,.18); }

        /* ── PAGINATION ── */
        .pagination { display: flex; align-items: center; justify-content: space-between; padding: 12px 20px; border-top: 1px solid var(--border); }
        .pagination-info { font-size: 12px; color: var(--text-muted); }
        .pagination-buttons { display: flex; gap: 4px; }
        .page-btn { background: var(--bg-card-alt); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 5px 10px; font-size: 12px; color: var(--text-muted); cursor: pointer; transition: all .15s; min-width: 30px; text-align: center; }
        .page-btn:hover { background: rgba(255,255,255,.06); color: var(--text-primary); }
        .page-btn.active { background: var(--accent); border-color: var(--accent); color: #fff; }
        .page-btn:disabled { opacity: .4; cursor: not-allowed; }

        /* ── EMPTY STATE ── */
        .empty-state { text-align: center; padding: 52px 20px; color: var(--text-muted); }
        .empty-icon { font-size: 36px; margin-bottom: 12px; opacity: .4; }
        .empty-text { font-size: 14px; font-weight: 500; margin-bottom: 6px; }
        .empty-hint { font-size: 13px; color: var(--text-dim); }

        /* ── MODALS ── */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.65); z-index: 200; align-items: center; justify-content: center; padding: 20px; }
        .modal-overlay.open { display: flex; }

        /* Delete modal */
        .modal-box { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 28px; width: 380px; max-width: 100%; }
        .modal-title { font-size: 16px; font-weight: 600; margin-bottom: 8px; }
        .modal-text  { font-size: 13px; color: var(--text-muted); margin-bottom: 22px; }
        .modal-actions { display: flex; gap: 10px; justify-content: flex-end; }

        /* Preview modal */
        .preview-modal { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-lg); width: 700px; max-width: 100%; overflow: hidden; max-height: 90vh; overflow-y: auto; }
        .preview-modal-img { width: 100%; height: 280px; object-fit: cover; display: block; }
        .preview-modal-img-empty { width: 100%; height: 200px; background: var(--bg-card-alt); display: flex; align-items: center; justify-content: center; font-size: 60px; color: var(--text-dim); }
        .preview-modal-body { padding: 24px 28px 28px; }
        .preview-modal-title { font-size: 20px; font-weight: 600; margin-bottom: 6px; }
        .preview-modal-price { display: inline-flex; align-items: center; background: var(--success-soft); color: var(--success); padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: 600; margin-bottom: 16px; }
        .preview-section-label { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: .07em; color: var(--text-dim); margin-bottom: 5px; margin-top: 14px; }
        .preview-text { font-size: 13px; color: var(--text-muted); line-height: 1.65; }
        .preview-carac { font-size: 13px; color: var(--accent); background: var(--accent-soft); border-radius: var(--radius-sm); padding: 8px 12px; }
        .preview-meta-row { display: flex; align-items: center; gap: 10px; margin-top: 18px; padding-top: 14px; border-top: 1px solid var(--border); flex-wrap: wrap; }
        .preview-close-btn { margin-left: auto; display: flex; align-items: center; gap: 6px; background: var(--danger-soft); color: var(--danger); border: 1px solid rgba(239,68,68,.2); border-radius: var(--radius-sm); padding: 6px 14px; font-size: 12px; font-weight: 500; cursor: pointer; }

        @media (max-width: 900px) {
            .kpi-strip { grid-template-columns: repeat(2,1fr); }
            .form-grid  { grid-template-columns: 1fr; }
            .sidebar { display: none; }
            .topbar, .main { left: 0; margin-left: 0; }
        }
    </style>
</head>
<body>

<!-- ════════════ SIDEBAR ════════════ -->
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
        <div class="nav-section-label">Tableau de bord</div>
        <a class="nav-item" href="#">
            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            Accueil
        </a>
        <a class="nav-item" href="#">
            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Utilisateurs
        </a>
        <a class="nav-item" href="#">
            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            Réclamations
        </a>
        <div class="nav-section-label" style="margin-top:8px">Modules</div>
        <a class="nav-item" href="#"><svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>Offres & Candidatures</a>
        <a class="nav-item" href="#"><svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>Évènements & Forums</a>
        <a class="nav-item" href="#"><svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>Campagnes & Contrats</a>
        <a class="nav-item active" href="index.php"><svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 10V11"/></svg>Produits</a>
        <a class="nav-item" href="#"><svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>Posts & Commentaires</a>
    </nav>
    <div class="sidebar-footer">
        <div class="admin-card">
            <div class="admin-avatar">A</div>
            <div>
                <div class="admin-name">Administrateur</div>
                <div class="admin-role">Super Admin</div>
            </div>
        </div>
    </div>
</aside>

<!-- ════════════ TOPBAR ════════════ -->
<div class="topbar">
    <div class="topbar-breadcrumb">
        <span>Cre8Connect</span><span class="sep">/</span>
        <span>Admin</span><span class="sep">/</span>
        <span class="current">Produits</span>
    </div>
    <div class="topbar-actions">
        <div class="search-wrap">
            <svg class="search-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
            <input type="text" class="search-input" placeholder="Rechercher un produit…" id="searchInput" oninput="filterTable()">
        </div>
        <a href="?export_csv=1" class="btn-export">
            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Export CSV
        </a>
        <a href="?add=1" class="btn-add">
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Nouveau produit
        </a>
    </div>
</div>

<!-- ════════════ CONTENT ════════════ -->
<main class="main">
<div class="content">

    <div class="page-header">
        <div>
            <div class="page-title">Gestion des Produits</div>
            <div class="page-subtitle">Superviser, ajouter, modifier, et analyser tous les produits de la plateforme.</div>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>" id="alertMsg">
        <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <?php if ($messageType === 'success' || $messageType === 'info'): ?>
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            <?php else: ?>
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            <?php endif; ?>
        </svg>
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <!-- ── KPI STRIP ── -->
    <div class="kpi-strip">
        <div class="kpi-card kpi-accent">
            <div class="kpi-label">Total produits</div>
            <div class="kpi-value"><?= $totalProduits ?></div>
            <div class="kpi-sub">Catalogue actif</div>
        </div>
        <div class="kpi-card kpi-info">
            <div class="kpi-label">Prix moyen</div>
            <div class="kpi-value"><?= number_format($prixMoyen, 2) ?></div>
            <div class="kpi-sub">DT — moyenne</div>
        </div>
        <div class="kpi-card kpi-success">
            <div class="kpi-label">Valeur catalogue</div>
            <div class="kpi-value"><?= number_format($valeurCatalogue, 0) ?></div>
            <div class="kpi-sub">DT — total</div>
        </div>
        <div class="kpi-card kpi-warning">
            <div class="kpi-label">Prix maximum</div>
            <div class="kpi-value"><?= number_format($prixMax, 2) ?></div>
            <div class="kpi-sub">DT — plus élevé</div>
        </div>
        <div class="kpi-card kpi-danger">
            <div class="kpi-label">Sans image</div>
            <div class="kpi-value"><?= $sanImage ?></div>
            <div class="kpi-sub"><?= $totalProduits > 0 ? round($sanImage / $totalProduits * 100) : 0 ?>% du catalogue</div>
        </div>
    </div>

    <!-- ── PRODUIT LE PLUS CHER ── -->
    <?php if ($produitPlusCher): ?>
    <div class="highlight-card">
        <?php if (!empty($produitPlusCher['image'])): ?>
            <img src="<?= $baseUrl ?>/Vue/public/produits/<?= htmlspecialchars($produitPlusCher['image']) ?>"
                 alt="" class="highlight-img">
        <?php else: ?>
            <div class="highlight-img-empty">📦</div>
        <?php endif; ?>
        <div class="highlight-info">
            <div class="highlight-label">⭐ Produit le plus cher</div>
            <div class="highlight-name"><?= htmlspecialchars($produitPlusCher['nomProduit']) ?></div>
            <div class="highlight-sub"><?= htmlspecialchars(mb_substr($produitPlusCher['description'], 0, 90)) ?>…</div>
        </div>
        <div class="highlight-badge"><?= number_format($prixMax, 2) ?> DT</div>
    </div>
    <?php endif; ?>

    <!-- ── FORM PANEL ── -->
    <?php if (isset($_GET['add']) || $produitUpdate): ?>
    <div class="form-panel">
        <div class="form-panel-header">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="color:var(--text-muted)">
                <?php if ($produitUpdate): ?>
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                <?php else: ?>
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                <?php endif; ?>
            </svg>
            <span class="form-panel-title"><?= $produitUpdate ? 'Modifier le produit' : 'Ajouter un produit' ?></span>
            <span class="form-panel-badge <?= $produitUpdate ? 'badge-edit' : 'badge-add' ?>"><?= $produitUpdate ? 'Édition' : 'Nouveau' ?></span>
        </div>
        <div class="form-body">
            <form method="POST" action="index.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="<?= $produitUpdate ? 'update' : 'add' ?>">
                <input type="hidden" name="id" value="<?= $produitUpdate['idProduit'] ?? '' ?>">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Nom du produit *</label>
                        <input type="text" name="nom" class="form-control" placeholder="Ex : Sac à main en cuir" value="<?= htmlspecialchars($produitUpdate['nomProduit'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Prix (DT) *</label>
                        <input type="number" step="0.01" min="0" name="prix" class="form-control" placeholder="0.00" value="<?= htmlspecialchars($produitUpdate['prix'] ?? '') ?>" required>
                    </div>
                    <div class="form-group form-col-full">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" placeholder="Description détaillée…"><?= htmlspecialchars($produitUpdate['description'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group form-col-full">
                        <label class="form-label">Caractéristiques</label>
                        <textarea name="caracteristiques" class="form-control" style="min-height:64px" placeholder="Couleur, taille, matière…"><?= htmlspecialchars($produitUpdate['caracteristiques'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group form-col-full">
                        <label class="form-label">Image du produit</label>
                        <?php if ($produitUpdate && !empty($produitUpdate['image'])): ?>
                        <div class="current-image-preview">
                            <img src="<?= $baseUrl ?>/Vue/public/produits/<?= htmlspecialchars($produitUpdate['image']) ?>" alt="">
                            <span>Image actuelle — laissez vide pour conserver.</span>
                        </div>
                        <?php endif; ?>
                        <div class="upload-admin">
                            <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp" id="fileInputAdmin">
                            <div class="upload-admin-text"><strong>Cliquez pour importer</strong> une image</div>
                            <div style="font-size:11px;color:var(--text-dim);margin-top:4px">JPG, PNG, WEBP — 2 Mo max</div>
                        </div>
                    </div>
                </div>
                <div class="form-actions" style="margin-top:16px">
                    <button type="submit" class="btn btn-primary">
                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <?= $produitUpdate ? 'Enregistrer les modifications' : 'Ajouter le produit' ?>
                    </button>
                    <a href="index.php" class="btn btn-ghost">Annuler</a>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── FILTER BAR ── -->
    <div class="filter-bar">
        <div class="filter-group">
            <div class="filter-label">Trier par</div>
            <select class="filter-select" id="sortColSelect" onchange="applySortSelect()">
                <option value="">— Défaut —</option>
                <option value="nom-asc">Nom A→Z</option>
                <option value="nom-desc">Nom Z→A</option>
                <option value="prix-asc">Prix croissant</option>
                <option value="prix-desc">Prix décroissant</option>
                <option value="id-asc">ID croissant</option>
                <option value="id-desc">ID décroissant</option>
            </select>
        </div>
        <div class="filter-group">
            <div class="filter-label">Fourchette de prix (DT)</div>
            <div class="price-range-wrap">
                <input type="number" class="price-input" id="priceMin" placeholder="Min" min="0" oninput="filterTable()">
                <span class="price-sep">—</span>
                <input type="number" class="price-input" id="priceMax" placeholder="Max" min="0" oninput="filterTable()">
            </div>
        </div>
        <div class="filter-group">
            <div class="filter-label">Image</div>
            <div class="image-filter-row">
                <button class="chip active" id="chip-all" onclick="setImageFilter('all')">Tous</button>
                <button class="chip" id="chip-with" onclick="setImageFilter('with')">Avec image</button>
                <button class="chip" id="chip-without" onclick="setImageFilter('without')">Sans image</button>
            </div>
        </div>
        <button class="btn-reset-filter" onclick="resetFilters()" style="margin-top:14px">Réinitialiser</button>
    </div>

    <!-- ── BULK BAR ── -->
    <div class="bulk-bar" id="bulkBar">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="color:var(--accent)"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        <span class="bulk-count" id="bulkCount">0 produit(s) sélectionné(s)</span>
        <form method="POST" action="index.php" id="bulkForm" style="margin-left:auto;display:flex;gap:8px;align-items:center">
            <input type="hidden" name="action_masse" value="supprimer_selection">
            <div id="bulkIdsContainer"></div>
            <button type="submit" class="btn-bulk-delete" onclick="return confirmBulk()">
                <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                Supprimer la sélection
            </button>
            <button type="button" class="btn-bulk-cancel" onclick="clearSelection()">Annuler</button>
        </form>
    </div>

    <!-- ── TABLE PANEL ── -->
    <div class="table-panel">
        <div class="table-panel-header">
            <span class="table-panel-title">Liste des produits</span>
            <span class="count-badge" id="visibleCount"><?= $totalProduits ?> entrée<?= $totalProduits > 1 ? 's' : '' ?></span>
            <select class="per-page-select" id="perPageSelect" onchange="renderPage(1)">
                <option value="10">10 / page</option>
                <option value="25">25 / page</option>
                <option value="50">50 / page</option>
                <option value="999">Tout afficher</option>
            </select>
        </div>

        <?php if (empty($liste)): ?>
        <div class="empty-state">
            <div class="empty-icon">📦</div>
            <div class="empty-text">Aucun produit dans le catalogue</div>
            <div class="empty-hint">Ajoutez votre premier produit via le bouton ci-dessus.</div>
        </div>
        <?php else: ?>
        <table id="produitsTable">
            <thead>
                <tr>
                    <th class="no-sort" style="width:36px">
                        <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)" style="accent-color:var(--accent);cursor:pointer;">
                    </th>
                    <th onclick="sortTable('id')" data-col="id">#ID</th>
                    <th class="no-sort">Image</th>
                    <th onclick="sortTable('nom')" data-col="nom">Nom</th>
                    <th class="no-sort">Description</th>
                    <th class="no-sort">Caractéristiques</th>
                    <th onclick="sortTable('prix')" data-col="prix">Prix</th>
                    <th class="no-sort">Img.</th>
                    <th class="no-sort">Actions</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php foreach ($liste as $p): ?>
                <tr data-id="<?= $p['idProduit'] ?>"
                    data-nom="<?= htmlspecialchars(strtolower($p['nomProduit'])) ?>"
                    data-desc="<?= htmlspecialchars(strtolower($p['description'])) ?>"
                    data-prix="<?= (float)$p['prix'] ?>"
                    data-hasimg="<?= empty($p['image']) ? '0' : '1' ?>">
                    <td class="td-check">
                        <input type="checkbox" class="row-check" value="<?= $p['idProduit'] ?>" onchange="updateBulkBar()">
                    </td>
                    <td class="td-id">#<?= htmlspecialchars($p['idProduit']) ?></td>
                    <td class="td-img">
                        <?php if (!empty($p['image'])): ?>
                            <img src="<?= $baseUrl ?>/Vue/public/produits/<?= htmlspecialchars($p['image']) ?>"
                                 alt="" onclick="openPreview(<?= $p['idProduit'] ?>)" title="Aperçu rapide">
                        <?php else: ?>
                            <div class="td-img-empty">📦</div>
                        <?php endif; ?>
                    </td>
                    <td class="td-name"><?= htmlspecialchars($p['nomProduit']) ?></td>
                    <td class="td-desc" title="<?= htmlspecialchars($p['description']) ?>"><?= htmlspecialchars($p['description']) ?></td>
                    <td class="td-carac" title="<?= htmlspecialchars($p['caracteristiques']) ?>"><?= htmlspecialchars($p['caracteristiques']) ?></td>
                    <td>
                        <!-- Inline price edit -->
                        <span class="prix-badge" id="prixDisplay-<?= $p['idProduit'] ?>" style="cursor:pointer" title="Cliquer pour modifier" onclick="showInlineEdit(<?= $p['idProduit'] ?>, <?= $p['prix'] ?>)">
                            <?= number_format((float)$p['prix'], 2) ?> DT
                        </span>
                        <form method="POST" action="index.php" class="prix-inline-form" id="prixForm-<?= $p['idProduit'] ?>" style="display:none">
                            <input type="hidden" name="action" value="update_prix">
                            <input type="hidden" name="id" value="<?= $p['idProduit'] ?>">
                            <input type="number" name="prix" step="0.01" min="0" class="prix-inline-input" id="prixInput-<?= $p['idProduit'] ?>" value="<?= $p['prix'] ?>">
                            <button type="submit" class="btn-inline-save" title="Sauvegarder">✓</button>
                            <button type="button" class="btn-inline-cancel" title="Annuler" onclick="hideInlineEdit(<?= $p['idProduit'] ?>)">✕</button>
                        </form>
                    </td>
                    <td>
                        <?php if (!empty($p['image'])): ?>
                            <span class="img-badge img-ok">✓ OK</span>
                        <?php else: ?>
                            <span class="img-badge img-miss">✕ Manque</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="action-group">
                            <button type="button" class="btn-action btn-view" onclick="openPreview(<?= $p['idProduit'] ?>)" title="Aperçu">
                                <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                Voir
                            </button>
                            <a href="?edit=<?= $p['idProduit'] ?>" class="btn-action btn-edit-a">
                                <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                Modifier
                            </a>
                            <button type="button" class="btn-action btn-delete" onclick="openDeleteModal(<?= $p['idProduit'] ?>, '<?= htmlspecialchars($p['nomProduit'], ENT_QUOTES) ?>')">
                                <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                Supprimer
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <!-- PAGINATION -->
        <div class="pagination" id="paginationBar">
            <div class="pagination-info" id="paginationInfo"></div>
            <div class="pagination-buttons" id="paginationButtons"></div>
        </div>
        <?php endif; ?>
    </div>

</div>
</main>

<!-- ════════════ MODAL SUPPRESSION ════════════ -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal-box">
        <div class="modal-title">Confirmer la suppression</div>
        <div class="modal-text" id="deleteModalText">Voulez-vous vraiment supprimer ce produit ? Cette action est irréversible.</div>
        <div class="modal-actions">
            <button class="btn btn-ghost" onclick="closeDeleteModal()">Annuler</button>
            <a class="btn" id="confirmDeleteBtn" href="#" style="background:var(--danger);color:#fff;">Supprimer</a>
        </div>
    </div>
</div>

<!-- ════════════ MODAL APERÇU ════════════ -->
<div class="modal-overlay" id="previewModal" onclick="closePreviewOutside(event)">
    <div class="preview-modal" id="previewBox">
        <div id="previewImgWrap"></div>
        <div class="preview-modal-body">
            <div id="previewTitle" class="preview-modal-title"></div>
            <div id="previewPrice" class="preview-modal-price"></div>
            <div class="preview-section-label">Description</div>
            <div id="previewDesc" class="preview-text"></div>
            <div class="preview-section-label">Caractéristiques</div>
            <div id="previewCarac" class="preview-carac"></div>
            <div class="preview-meta-row">
                <span style="font-size:12px;color:var(--text-dim)">ID produit : <span id="previewId" style="color:var(--text-muted)"></span></span>
                <button class="preview-close-btn" onclick="closePreview()">
                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    Fermer
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// ═══════════════════════════════════
// DATA
// ═══════════════════════════════════
const PRODUITS = <?= json_encode(array_values($liste), JSON_UNESCAPED_UNICODE) ?>;
const BASE_URL = '<?= $baseUrl ?>';
let imageFilter = 'all';
let currentSort = { col: null, dir: 'asc' };
let currentPage = 1;
let filteredRows = [];

// ═══════════════════════════════════
// FILTER + SEARCH
// ═══════════════════════════════════
function filterTable() {
    const q      = document.getElementById('searchInput').value.toLowerCase().trim();
    const pMin   = parseFloat(document.getElementById('priceMin').value) || null;
    const pMax   = parseFloat(document.getElementById('priceMax').value) || null;
    const rows   = Array.from(document.querySelectorAll('#tableBody tr'));

    filteredRows = rows.filter(row => {
        const nom   = row.dataset.nom  || '';
        const desc  = row.dataset.desc || '';
        const prix  = parseFloat(row.dataset.prix) || 0;
        const hasImg = row.dataset.hasimg;

        const matchQ    = !q || nom.includes(q) || desc.includes(q);
        const matchMin  = pMin === null || prix >= pMin;
        const matchMax  = pMax === null || prix <= pMax;
        const matchImg  = imageFilter === 'all' ||
                          (imageFilter === 'with'    && hasImg === '1') ||
                          (imageFilter === 'without' && hasImg === '0');
        return matchQ && matchMin && matchMax && matchImg;
    });

    renderPage(1);
}

function setImageFilter(val) {
    imageFilter = val;
    ['all','with','without'].forEach(v => {
        document.getElementById('chip-' + v).classList.toggle('active', v === val);
    });
    filterTable();
}

function resetFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('priceMin').value = '';
    document.getElementById('priceMax').value = '';
    document.getElementById('sortColSelect').value = '';
    setImageFilter('all');
    currentSort = { col: null, dir: 'asc' };
    document.querySelectorAll('thead th').forEach(th => th.classList.remove('sort-asc','sort-desc'));
    filterTable();
}

// ═══════════════════════════════════
// SORT
// ═══════════════════════════════════
function sortTable(col) {
    if (currentSort.col === col) {
        currentSort.dir = currentSort.dir === 'asc' ? 'desc' : 'asc';
    } else {
        currentSort.col = col;
        currentSort.dir = 'asc';
    }
    document.querySelectorAll('thead th[data-col]').forEach(th => {
        th.classList.remove('sort-asc','sort-desc');
        if (th.dataset.col === col) th.classList.add('sort-' + currentSort.dir);
    });
    const rows = Array.from(document.querySelectorAll('#tableBody tr'));
    rows.sort((a, b) => {
        let va = a.dataset[col] || '';
        let vb = b.dataset[col] || '';
        if (col === 'prix' || col === 'id') { va = parseFloat(va); vb = parseFloat(vb); }
        const res = va > vb ? 1 : va < vb ? -1 : 0;
        return currentSort.dir === 'asc' ? res : -res;
    });
    const tbody = document.getElementById('tableBody');
    rows.forEach(r => tbody.appendChild(r));
    filterTable();
}

function applySortSelect() {
    const val = document.getElementById('sortColSelect').value;
    if (!val) return;
    const [col, dir] = val.split('-');
    currentSort = { col, dir };
    document.querySelectorAll('thead th[data-col]').forEach(th => {
        th.classList.remove('sort-asc','sort-desc');
        if (th.dataset.col === col) th.classList.add('sort-' + dir);
    });
    const rows = Array.from(document.querySelectorAll('#tableBody tr'));
    rows.sort((a, b) => {
        let va = a.dataset[col] || '';
        let vb = b.dataset[col] || '';
        if (col === 'prix' || col === 'id') { va = parseFloat(va); vb = parseFloat(vb); }
        const res = va > vb ? 1 : va < vb ? -1 : 0;
        return dir === 'asc' ? res : -res;
    });
    const tbody = document.getElementById('tableBody');
    rows.forEach(r => tbody.appendChild(r));
    filterTable();
}

// ═══════════════════════════════════
// PAGINATION
// ═══════════════════════════════════
function renderPage(page) {
    currentPage = page;
    const perPage = parseInt(document.getElementById('perPageSelect').value);
    const allRows = Array.from(document.querySelectorAll('#tableBody tr'));

    // Apply filter
    const q    = document.getElementById('searchInput').value.toLowerCase().trim();
    const pMin = parseFloat(document.getElementById('priceMin').value) || null;
    const pMax = parseFloat(document.getElementById('priceMax').value) || null;

    const visible = allRows.filter(row => {
        const nom  = row.dataset.nom  || '';
        const desc = row.dataset.desc || '';
        const prix = parseFloat(row.dataset.prix) || 0;
        const hasImg = row.dataset.hasimg;
        const matchQ   = !q || nom.includes(q) || desc.includes(q);
        const matchMin = pMin === null || prix >= pMin;
        const matchMax = pMax === null || prix <= pMax;
        const matchImg = imageFilter === 'all' ||
                         (imageFilter === 'with'    && hasImg === '1') ||
                         (imageFilter === 'without' && hasImg === '0');
        return matchQ && matchMin && matchMax && matchImg;
    });

    const total  = visible.length;
    const pages  = perPage >= 999 ? 1 : Math.ceil(total / perPage);
    page = Math.min(page, pages || 1);
    const start  = perPage >= 999 ? 0 : (page - 1) * perPage;
    const end    = perPage >= 999 ? total : Math.min(start + perPage, total);

    allRows.forEach(r => r.style.display = 'none');
    visible.forEach((r, i) => r.style.display = (i >= start && i < end) ? '' : 'none');

    // Count badge
    document.getElementById('visibleCount').textContent = total + ' entrée' + (total > 1 ? 's' : '');

    // Pagination info
    document.getElementById('paginationInfo').textContent =
        total === 0 ? 'Aucun résultat' :
        `Affichage ${start + 1}–${end} sur ${total}`;

    // Pagination buttons
    const btns = document.getElementById('paginationButtons');
    btns.innerHTML = '';
    if (pages <= 1) return;

    const addBtn = (label, p, disabled, active) => {
        const b = document.createElement('button');
        b.className = 'page-btn' + (active ? ' active' : '');
        b.textContent = label;
        b.disabled = disabled;
        b.onclick = () => renderPage(p);
        btns.appendChild(b);
    };

    addBtn('←', page - 1, page === 1, false);
    for (let i = 1; i <= pages; i++) {
        if (i === 1 || i === pages || Math.abs(i - page) <= 1) {
            addBtn(i, i, false, i === page);
        } else if (Math.abs(i - page) === 2) {
            const dots = document.createElement('span');
            dots.textContent = '…';
            dots.style.cssText = 'padding:5px 4px;color:var(--text-dim);font-size:12px;';
            btns.appendChild(dots);
        }
    }
    addBtn('→', page + 1, page === pages, false);
}

// Init
document.addEventListener('DOMContentLoaded', () => filterTable());

// ═══════════════════════════════════
// BULK SELECTION
// ═══════════════════════════════════
function toggleSelectAll(cb) {
    document.querySelectorAll('.row-check').forEach(c => {
        if (c.closest('tr').style.display !== 'none') c.checked = cb.checked;
    });
    updateBulkBar();
}

function updateBulkBar() {
    const checked = Array.from(document.querySelectorAll('.row-check:checked'));
    const bar = document.getElementById('bulkBar');
    document.getElementById('bulkCount').textContent = checked.length + ' produit(s) sélectionné(s)';
    bar.classList.toggle('visible', checked.length > 0);
    const container = document.getElementById('bulkIdsContainer');
    container.innerHTML = '';
    checked.forEach(c => {
        const inp = document.createElement('input');
        inp.type = 'hidden'; inp.name = 'selected_ids[]'; inp.value = c.value;
        container.appendChild(inp);
    });
    document.getElementById('selectAll').indeterminate =
        checked.length > 0 && checked.length < document.querySelectorAll('.row-check').length;
}

function clearSelection() {
    document.querySelectorAll('.row-check, #selectAll').forEach(c => c.checked = false);
    document.getElementById('selectAll').indeterminate = false;
    document.getElementById('bulkBar').classList.remove('visible');
}

function confirmBulk() {
    const n = document.querySelectorAll('.row-check:checked').length;
    return confirm('Supprimer ' + n + ' produit(s) sélectionné(s) ? Cette action est irréversible.');
}

// ═══════════════════════════════════
// INLINE PRICE EDIT
// ═══════════════════════════════════
function showInlineEdit(id, prix) {
    document.getElementById('prixDisplay-' + id).style.display = 'none';
    document.getElementById('prixForm-' + id).style.display = 'flex';
    document.getElementById('prixInput-' + id).focus();
}
function hideInlineEdit(id) {
    document.getElementById('prixDisplay-' + id).style.display = '';
    document.getElementById('prixForm-' + id).style.display = 'none';
}

// ═══════════════════════════════════
// MODAL DELETE
// ═══════════════════════════════════
function openDeleteModal(id, name) {
    document.getElementById('deleteModalText').textContent =
        'Voulez-vous vraiment supprimer le produit "' + name + '" ? Cette action est irréversible.';
    document.getElementById('confirmDeleteBtn').href = 'index.php?delete=' + id;
    document.getElementById('deleteModal').classList.add('open');
}
function closeDeleteModal() { document.getElementById('deleteModal').classList.remove('open'); }
document.getElementById('deleteModal').addEventListener('click', e => { if (e.target.id === 'deleteModal') closeDeleteModal(); });

// ═══════════════════════════════════
// MODAL APERÇU
// ═══════════════════════════════════
const produitsMap = {};
<?= json_encode($liste) ?> && <?php echo 'true' ?>;
<?php foreach ($liste as $p): ?>
produitsMap[<?= $p['idProduit'] ?>] = {
    id:   <?= $p['idProduit'] ?>,
    nom:  <?= json_encode($p['nomProduit']) ?>,
    desc: <?= json_encode($p['description']) ?>,
    carac:<?= json_encode($p['caracteristiques']) ?>,
    prix: <?= (float)$p['prix'] ?>,
    img:  <?= json_encode(!empty($p['image']) ? $baseUrl . '/Vue/public/produits/' . $p['image'] : null) ?>
};
<?php endforeach; ?>

function openPreview(id) {
    const p = produitsMap[id];
    if (!p) return;
    document.getElementById('previewImgWrap').innerHTML = p.img
        ? `<img src="${p.img}" alt="" class="preview-modal-img">`
        : `<div class="preview-modal-img-empty">📦</div>`;
    document.getElementById('previewTitle').textContent  = p.nom;
    document.getElementById('previewPrice').textContent  = p.prix.toFixed(2) + ' DT';
    document.getElementById('previewDesc').textContent   = p.desc || '—';
    document.getElementById('previewCarac').textContent  = p.carac || '—';
    document.getElementById('previewId').textContent     = '#' + p.id;
    document.getElementById('previewModal').classList.add('open');
}
function closePreview() { document.getElementById('previewModal').classList.remove('open'); }
function closePreviewOutside(e) { if (e.target.id === 'previewModal') closePreview(); }

// ═══════════════════════════════════
// MISC
// ═══════════════════════════════════
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { closeDeleteModal(); closePreview(); }
});

setTimeout(() => {
    const a = document.getElementById('alertMsg');
    if (a) { a.style.transition = 'opacity .5s'; a.style.opacity = '0'; setTimeout(() => a.remove(), 500); }
}, 4000);
</script>
</body>
</html>