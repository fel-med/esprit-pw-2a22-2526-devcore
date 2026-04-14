<?php
require_once '../../../Controleur/produitC.php';
require_once '../../../Modele/produit.php';

$produitC = new ProduitC();
$message = '';
$messageType = '';
$baseUrl = '/projet/Esprit-PW-2A22-2526-Devcore';

define('DEVISE', '€');

if (isset($_GET['delete'])) {
    $produitC->supprimerProduit($_GET['delete']);
    header('Location: index.php?deleted=1');
    exit;
}

if (isset($_POST['action_masse']) && $_POST['action_masse'] === 'supprimer_selection') {
    $ids = $_POST['selected_ids'] ?? [];
    foreach ($ids as $id) {
        $produitC->supprimerProduit(intval($id));
    }
    header('Location: index.php?deleted_masse=' . count($ids));
    exit;
}

// ── AJAX : toggle pin ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'epingle') {
    $produitC->toggleEpingle(intval($_POST['id']));
    echo json_encode(['ok' => true]);
    exit;
}

// ── AJAX : toggle archive ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'archive') {
    $produitC->toggleArchive(intval($_POST['id']));
    echo json_encode(['ok' => true]);
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'add') {
    if (empty(trim($_POST['nom'])) || !is_numeric($_POST['prix']) || floatval($_POST['prix']) < 0) {
        $message = "Invalid input: name required, price must be >=0";
        $messageType = "error";
    } else {
        $nomImage = $produitC->gererUploadImage($_FILES['image'] ?? null);
        $produit = new Produit(
            null,
            trim($_POST['nom']),
            trim($_POST['description']),
            trim($_POST['caracteristiques']),
            floatval($_POST['prix']),
            1,
            $nomImage,
            trim($_POST['categorie'] ?? ''),
            0, 0, 0,
            !empty($_POST['dateDisponibilite']) ? $_POST['dateDisponibilite'] : null,
            trim($_POST['noteInterne'] ?? '')
        );
        $produitC->ajouterProduit($produit);
        header('Location: index.php?added=1');
        exit;
    }
}

if (isset($_POST['action']) && $_POST['action'] === 'update') {
    if (empty(trim($_POST['nom'])) || !is_numeric($_POST['prix']) || floatval($_POST['prix']) < 0) {
        $message = "Invalid input: name required, price must be >=0";
        $messageType = "error";
    } else {
        $ancienProduit = $produitC->recupererProduit(intval($_POST['id']));
        $nomImage = $produitC->gererUploadImage($_FILES['image'] ?? null, $ancienProduit['image'] ?? null);
        $produit = new Produit(
            null,
            trim($_POST['nom']),
            trim($_POST['description']),
            trim($_POST['caracteristiques']),
            floatval($_POST['prix']),
            null,
            $nomImage,
            trim($_POST['categorie'] ?? ''),
            intval($_POST['estArchive'] ?? 0),
            intval($_POST['estEpingle'] ?? 0),
            0,
            !empty($_POST['dateDisponibilite']) ? $_POST['dateDisponibilite'] : null,
            trim($_POST['noteInterne'] ?? '')
        );
        $produitC->modifierProduit($produit, intval($_POST['id']));
        header('Location: index.php?updated=1');
        exit;
    }
}

if (isset($_POST['action']) && $_POST['action'] === 'update_prix') {
    if (!is_numeric($_POST['prix']) || floatval($_POST['prix']) < 0) {
        $message = "Invalid price: must be >=0";
        $messageType = "error";
    } else {
        $ancienProduit = $produitC->recupererProduit(intval($_POST['id']));
        if ($ancienProduit) {
            $produit = new Produit(null, $ancienProduit['nomProduit'], $ancienProduit['description'], $ancienProduit['caracteristiques'], floatval($_POST['prix']), null, $ancienProduit['image'], $ancienProduit['categorie'] ?? '', intval($ancienProduit['estArchive'] ?? 0), intval($ancienProduit['estEpingle'] ?? 0), 0, $ancienProduit['dateDisponibilite'] ?? null, $ancienProduit['noteInterne'] ?? '');
            $produitC->modifierProduit($produit, intval($_POST['id']));
        }
        header('Location: index.php?updated=1');
        exit;
    }
}

if (isset($_GET['added']))         { $message = "Product added successfully.";                                          $messageType = "success"; }
if (isset($_GET['updated']))       { $message = "Product updated successfully.";                                        $messageType = "info"; }
if (isset($_GET['deleted']))       { $message = "Product deleted successfully.";                                        $messageType = "danger"; }
if (isset($_GET['deleted_masse'])) { $message = $_GET['deleted_masse'] . " product(s) deleted successfully.";          $messageType = "danger"; }

$liste            = $produitC->afficherProduits();
$listeArchives    = $produitC->afficherProduitsArchives();
$categoriesDispos = $produitC->getCategories();

$produitUpdate = null;
if (isset($_GET['edit'])) {
    $produitUpdate = $produitC->recupererProduit(intval($_GET['edit']));
}

$totalProduits   = count($liste);
$totalArchives   = count($listeArchives);
$allPrix         = array_column($liste, 'prix');
$prixMoyen       = $totalProduits > 0 ? array_sum($allPrix) / $totalProduits : 0;
$prixMax         = $totalProduits > 0 ? max($allPrix) : 0;
$valeurCatalogue = array_sum($allPrix);
$sanImage        = count(array_filter($liste, fn($p) => empty($p['image'])));
$avecImage       = $totalProduits - $sanImage;
$prixMin         = $totalProduits > 0 ? min($allPrix) : 0;
$nbEpingles      = count(array_filter($liste, fn($p) => !empty($p['estEpingle'])));

$produitPlusCher = null;
if ($totalProduits > 0) {
    $tmp = $liste;
    usort($tmp, fn($a, $b) => $b['prix'] <=> $a['prix']);
    $produitPlusCher = $tmp[0];
    usort($liste, fn($a, $b) => $b['idProduit'] <=> $a['idProduit']);
}

if (isset($_GET['export_csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="products_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Name', 'Description', 'Characteristics', 'Category', 'Price (' . DEVISE . ')', 'Image', 'Brand ID', 'Pinned', 'Archived', 'Availability Date', 'Internal Note']);
    foreach ($liste as $p) {
        fputcsv($out, [
            $p['idProduit'],
            $p['nomProduit'],
            $p['description'],
            $p['caracteristiques'],
            $p['categorie'] ?? '',
            $p['prix'],
            $p['image'] ?? '',
            $p['nomMarque'] ?? ($p['idMarque'] ?? ''),
            !empty($p['estEpingle']) ? 'Yes' : 'No',
            !empty($p['estArchive']) ? 'Yes' : 'No',
            $p['dateDisponibilite'] ?? '',
            $p['noteInterne'] ?? '',
        ]);
    }
    fclose($out);
    exit;
}

// Shared categories list — same as brand FO for consistency
$categoriesDisponibles = ['Beauty & Care','Fashion & Accessories','Tech & Gadgets','Food & Nutrition','Sport & Fitness','Home & Decor','Travel','Wellness','Gaming','Kids'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — Product Management | Cre8Connect</title>
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
            --toggle-bg:       #1c2235;
            --toggle-border:   rgba(255,255,255,0.07);
            --toggle-icon-sun: rgba(255,255,255,0.3);
            --toggle-icon-moon:#8b5cf6;
            --shadow-card:     none;
        }

        [data-theme="light"] {
            --bg-base:      #f0f2f8;
            --bg-surface:   #ffffff;
            --bg-card:      #ffffff;
            --bg-card-alt:  #f5f6fa;
            --bg-input:     #f5f6fa;
            --border:       rgba(0,0,0,0.08);
            --border-focus: rgba(139,92,246,0.4);
            --text-primary: #1a1d2e;
            --text-muted:   #5a6070;
            --text-dim:     #9ca3af;
            --accent:       #7c3aed;
            --accent-soft:  rgba(124,58,237,0.08);
            --accent-hover: #6d28d9;
            --success:      #059669;
            --success-soft: rgba(5,150,105,0.08);
            --danger:       #dc2626;
            --danger-soft:  rgba(220,38,38,0.07);
            --warning:      #d97706;
            --warning-soft: rgba(217,119,6,0.09);
            --info:         #2563eb;
            --info-soft:    rgba(37,99,235,0.08);
            --toggle-bg:        #f0f2f8;
            --toggle-border:    rgba(0,0,0,0.10);
            --toggle-icon-sun:  #d97706;
            --toggle-icon-moon: rgba(0,0,0,0.25);
            --shadow-card:  0 1px 4px rgba(0,0,0,0.06);
        }

        body { font-family: 'Inter', system-ui, sans-serif; background: var(--bg-base); color: var(--text-primary); min-height: 100vh; display: flex; font-size: 14px; line-height: 1.5; transition: background .25s, color .25s; }

        /* THEME TOGGLE */
        .theme-toggle { display: flex; align-items: center; gap: 6px; background: var(--toggle-bg); border: 1px solid var(--toggle-border); border-radius: 20px; padding: 4px 10px; cursor: pointer; transition: background .2s, border-color .2s; user-select: none; }
        .theme-toggle:hover { border-color: var(--accent); }
        .theme-toggle .icon-sun, .theme-toggle .icon-moon { width: 14px; height: 14px; transition: color .2s; }
        .theme-toggle .icon-sun  { color: var(--toggle-icon-sun); }
        .theme-toggle .icon-moon { color: var(--toggle-icon-moon); }
        .theme-toggle .toggle-label { font-size: 12px; font-weight: 500; color: var(--text-muted); }
        .toggle-track-pill { width: 32px; height: 18px; background: var(--toggle-border); border-radius: 9px; position: relative; transition: background .2s; }
        .toggle-track-pill.active { background: var(--accent); }
        .toggle-knob { position: absolute; top: 2px; left: 2px; width: 14px; height: 14px; background: #fff; border-radius: 50%; transition: transform .2s; box-shadow: 0 1px 3px rgba(0,0,0,.25); }
        .toggle-knob.right { transform: translateX(14px); }

        /* SIDEBAR */
        .sidebar { width: var(--sidebar-w); background: var(--bg-surface); border-right: 1px solid var(--border); display: flex; flex-direction: column; position: fixed; top: 0; left: 0; bottom: 0; z-index: 100; transition: background .25s, border-color .25s; box-shadow: var(--shadow-card); }
        .sidebar-logo { padding: 18px 20px; border-bottom: 1px solid var(--border); }
        .sidebar-logo .brand { display: flex; align-items: center; gap: 10px; text-decoration: none; }
        .logo-img { width: 34px; height: 34px; object-fit: contain; border-radius: var(--radius-sm); flex-shrink: 0; }
        .logo-text { font-size: 15px; font-weight: 700; color: var(--text-primary); }
        .logo-badge { font-size: 9px; font-weight: 600; letter-spacing: .05em; color: var(--accent); background: var(--accent-soft); padding: 2px 6px; border-radius: 20px; margin-top: 1px; }
        .sidebar-nav { flex: 1; padding: 12px 10px; overflow-y: auto; }
        .nav-section-label { font-size: 10px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--text-dim); padding: 10px 10px 6px; }
        .nav-item { display: flex; align-items: center; gap: 10px; padding: 8px 10px; border-radius: var(--radius-sm); color: var(--text-muted); text-decoration: none; font-size: 13px; font-weight: 450; transition: background .15s, color .15s; cursor: pointer; margin-bottom: 2px; }
        .nav-item:hover { background: rgba(139,92,246,0.06); color: var(--text-primary); }
        .nav-item.active { background: var(--accent-soft); color: var(--accent); font-weight: 500; }
        .nav-icon { width: 18px; height: 18px; opacity: .8; flex-shrink: 0; }
        .sidebar-footer { padding: 12px; border-top: 1px solid var(--border); }
        .admin-card { display: flex; align-items: center; gap: 10px; padding: 8px 10px; border-radius: var(--radius-sm); background: var(--bg-card-alt); }
        .admin-avatar { width: 30px; height: 30px; border-radius: 50%; background: var(--accent); display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 600; color: #fff; flex-shrink: 0; }
        .admin-name { font-size: 12px; font-weight: 500; color: var(--text-primary); }
        .admin-role { font-size: 11px; color: var(--text-muted); }

        /* TOPBAR */
        .topbar { position: fixed; top: 0; left: var(--sidebar-w); right: 0; height: 58px; background: var(--bg-surface); border-bottom: 1px solid var(--border); display: flex; align-items: center; padding: 0 28px; gap: 16px; z-index: 90; transition: background .25s, border-color .25s; box-shadow: var(--shadow-card); }
        .topbar-breadcrumb { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--text-muted); }
        .topbar-breadcrumb .sep { opacity: .4; }
        .topbar-breadcrumb .current { color: var(--text-primary); font-weight: 500; }
        .topbar-actions { margin-left: auto; display: flex; align-items: center; gap: 10px; }
        .btn-add { display: flex; align-items: center; gap: 7px; background: var(--accent); color: #fff; border: none; padding: 7px 14px; border-radius: var(--radius-sm); font-size: 13px; font-weight: 500; cursor: pointer; text-decoration: none; transition: background .15s; }
        .btn-add:hover { background: var(--accent-hover); }
        .btn-export { display: flex; align-items: center; gap: 6px; background: var(--success-soft); color: var(--success); border: 1px solid rgba(16,185,129,.2); padding: 7px 12px; border-radius: var(--radius-sm); font-size: 12px; font-weight: 500; cursor: pointer; text-decoration: none; transition: background .15s; }
        .btn-export:hover { background: rgba(16,185,129,.2); }
        .btn-fo-link { display: flex; align-items: center; gap: 6px; background: var(--info-soft); color: var(--info); border: 1px solid rgba(59,130,246,.2); padding: 7px 12px; border-radius: var(--radius-sm); font-size: 12px; font-weight: 500; cursor: pointer; text-decoration: none; transition: background .15s; }
        .btn-fo-link:hover { background: rgba(59,130,246,.2); }
        .search-wrap { position: relative; }
        .search-input { background: var(--bg-input); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 7px 12px 7px 34px; color: var(--text-primary); font-size: 13px; width: 220px; transition: border-color .2s, background .25s; outline: none; }
        .search-input::placeholder { color: var(--text-dim); }
        .search-input:focus { border-color: var(--border-focus); }
        .search-icon { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--text-dim); pointer-events: none; width: 15px; height: 15px; }

        /* MAIN */
        .main { margin-left: var(--sidebar-w); padding-top: 58px; flex: 1; min-height: 100vh; }
        .content { padding: 28px 28px 60px; }
        .page-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
        .page-title { font-size: 20px; font-weight: 600; color: var(--text-primary); }
        .page-subtitle { font-size: 13px; color: var(--text-muted); margin-top: 3px; }

        /* ALERT */
        .alert { display: flex; align-items: center; gap: 10px; padding: 11px 16px; border-radius: var(--radius-md); font-size: 13px; margin-bottom: 20px; font-weight: 450; }
        .alert-success { background: var(--success-soft); color: var(--success); border: 1px solid rgba(16,185,129,.2); }
        .alert-info    { background: var(--info-soft);    color: var(--info);    border: 1px solid rgba(59,130,246,.2); }
        .alert-danger  { background: var(--danger-soft);  color: var(--danger);  border: 1px solid rgba(239,68,68,.2); }

        /* KPI STRIP */
        .kpi-strip { display: grid; grid-template-columns: repeat(7, 1fr); gap: 12px; margin-bottom: 24px; }
        .kpi-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 14px 16px; display: flex; flex-direction: column; gap: 4px; position: relative; overflow: hidden; box-shadow: var(--shadow-card); transition: background .25s, border-color .25s; }
        .kpi-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px; }
        .kpi-card.kpi-accent::before  { background: var(--accent); }
        .kpi-card.kpi-info::before    { background: var(--info); }
        .kpi-card.kpi-success::before { background: var(--success); }
        .kpi-card.kpi-warning::before { background: var(--warning); }
        .kpi-card.kpi-danger::before  { background: var(--danger); }
        .kpi-card.kpi-purple::before  { background: #a855f7; }
        .kpi-card.kpi-archive::before { background: #64748b; }
        .kpi-label { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; color: var(--text-muted); }
        .kpi-value { font-size: 22px; font-weight: 600; color: var(--text-primary); line-height: 1.1; }
        .kpi-sub { font-size: 11px; color: var(--text-muted); }

        /* HIGHLIGHT CARD */
        .highlight-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 16px 20px; margin-bottom: 24px; display: flex; align-items: center; gap: 16px; box-shadow: var(--shadow-card); transition: background .25s, border-color .25s; }
        .highlight-img { width: 54px; height: 54px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--border); flex-shrink: 0; }
        .highlight-img-empty { width: 54px; height: 54px; background: var(--bg-card-alt); border: 1px dashed var(--border); border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; font-size: 22px; color: var(--text-dim); flex-shrink: 0; }
        .highlight-info { flex: 1; }
        .highlight-label { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: .07em; color: var(--warning); margin-bottom: 3px; }
        .highlight-name { font-size: 15px; font-weight: 600; color: var(--text-primary); }
        .highlight-sub { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
        .highlight-badge { font-size: 15px; font-weight: 700; color: var(--warning); background: var(--warning-soft); padding: 6px 14px; border-radius: var(--radius-sm); white-space: nowrap; }
        .img-alert-banner { display: flex; align-items: center; gap: 12px; background: rgba(239,68,68,0.07); border: 1px solid rgba(239,68,68,.18); border-radius: var(--radius-md); padding: 11px 16px; margin-bottom: 20px; font-size: 13px; color: var(--danger); }
        .img-alert-banner svg { flex-shrink: 0; }
        .img-alert-btn { margin-left: auto; background: var(--danger-soft); color: var(--danger); border: 1px solid rgba(239,68,68,.2); border-radius: var(--radius-sm); padding: 4px 10px; font-size: 11px; font-weight: 500; cursor: pointer; }
        .img-alert-btn:hover { background: rgba(239,68,68,.18); }

        /* TABS */
        .tab-bar { display: flex; gap: 4px; margin-bottom: 16px; border-bottom: 1px solid var(--border); }
        .tab-btn { background: none; border: none; padding: 10px 18px; font-size: 13px; font-weight: 500; color: var(--text-muted); cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -1px; transition: color .18s, border-color .18s; font-family: 'Inter', sans-serif; display: flex; align-items: center; gap: 7px; }
        .tab-btn.active { color: var(--accent); border-bottom-color: var(--accent); }
        .tab-btn:hover { color: var(--text-primary); }
        .tab-pill { background: var(--accent-soft); color: var(--accent); border-radius: 20px; padding: 1px 8px; font-size: 11px; font-weight: 700; }
        .tab-pill.archive { background: rgba(100,116,139,0.15); color: #64748b; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }

        /* FILTER BAR */
        .filter-bar { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 14px 18px; margin-bottom: 16px; display: flex; align-items: flex-end; gap: 16px; flex-wrap: wrap; box-shadow: var(--shadow-card); transition: background .25s, border-color .25s; }
        .filter-group { display: flex; flex-direction: column; gap: 5px; }
        .filter-label { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; color: var(--text-dim); }
        .filter-select { background: var(--bg-input); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 6px 10px; color: var(--text-primary); font-size: 12px; outline: none; cursor: pointer; transition: border-color .2s, background .25s; }
        .filter-select:focus { border-color: var(--border-focus); }
        .price-range-wrap { display: flex; align-items: center; gap: 8px; }
        .price-input { background: var(--bg-input); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 6px 8px; color: var(--text-primary); font-size: 12px; width: 80px; outline: none; transition: border-color .2s, background .25s; }
        .price-input:focus { border-color: var(--border-focus); }
        .price-sep { color: var(--text-dim); font-size: 12px; }
        .btn-reset-filter { background: transparent; color: var(--text-muted); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 6px 10px; font-size: 12px; cursor: pointer; transition: all .15s; }
        .btn-reset-filter:hover { color: var(--text-primary); border-color: rgba(255,255,255,.15); }
        .image-filter-row { display: flex; gap: 6px; }
        .chip { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 500; cursor: pointer; border: 1px solid var(--border); background: transparent; color: var(--text-muted); transition: all .15s; }
        .chip:hover { border-color: var(--accent); color: var(--accent); }
        .chip.active { background: var(--accent-soft); border-color: rgba(139,92,246,.3); color: var(--accent); }

        /* CAT CHIPS for table filter */
        .cat-filter-row { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; margin-bottom: 12px; }
        .cat-label { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; color: var(--text-dim); margin-right: 4px; }

        /* BULK BAR */
        .bulk-bar { background: var(--accent-soft); border: 1px solid rgba(139,92,246,.2); border-radius: var(--radius-md); padding: 10px 16px; display: none; align-items: center; gap: 14px; margin-bottom: 12px; font-size: 13px; }
        .bulk-bar.visible { display: flex; }
        .bulk-count { color: var(--accent); font-weight: 500; }
        .btn-bulk-delete { display: flex; align-items: center; gap: 5px; background: var(--danger-soft); color: var(--danger); border: 1px solid rgba(239,68,68,.2); border-radius: var(--radius-sm); padding: 5px 12px; font-size: 12px; font-weight: 500; cursor: pointer; }
        .btn-bulk-delete:hover { background: rgba(239,68,68,.18); }
        .btn-bulk-cancel { background: transparent; color: var(--text-muted); border: none; font-size: 12px; cursor: pointer; padding: 5px 8px; }
        .btn-bulk-cancel:hover { color: var(--text-primary); }

        /* FORM PANEL */
        .form-panel { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-lg); margin-bottom: 24px; overflow: hidden; box-shadow: var(--shadow-card); transition: background .25s, border-color .25s; }
        .form-panel-header { display: flex; align-items: center; gap: 10px; padding: 14px 20px; border-bottom: 1px solid var(--border); }
        .form-panel-title { font-size: 14px; font-weight: 600; color: var(--text-primary); }
        .form-panel-badge { font-size: 11px; font-weight: 500; padding: 2px 8px; border-radius: 20px; }
        .badge-add  { background: var(--success-soft); color: var(--success); }
        .badge-edit { background: var(--warning-soft); color: var(--warning); }
        .form-body { padding: 20px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .form-col-full { grid-column: 1 / -1; }
        .form-col-third { grid-column: span 1; }
        .form-group { display: flex; flex-direction: column; gap: 5px; }
        .form-label { font-size: 12px; font-weight: 500; color: var(--text-muted); }
        .form-control { background: var(--bg-input); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 9px 12px; color: var(--text-primary); font-size: 13px; font-family: inherit; transition: border-color .2s, background .25s; width: 100%; outline: none; }
        .form-control::placeholder { color: var(--text-dim); }
        .form-control:focus { border-color: var(--border-focus); }
        textarea.form-control { resize: vertical; min-height: 80px; }
        select.form-control { cursor: pointer; }

        /* NOTE INTERNE */
        .note-interne-wrap { border: 1px dashed rgba(245,158,11,0.3); border-radius: var(--radius-sm); background: rgba(245,158,11,0.04); padding: 10px 12px; }
        .note-interne-header { font-size: 11px; font-weight: 600; color: var(--warning); margin-bottom: 6px; display: flex; align-items: center; gap: 5px; }
        textarea.note-interne-ctrl { background: transparent; border: none; outline: none; width: 100%; font-size: 13px; font-family: inherit; color: var(--text-primary); resize: vertical; min-height: 60px; }
        textarea.note-interne-ctrl::placeholder { color: var(--text-dim); }

        /* TOGGLE SWITCH for form */
        .toggle-row { display: flex; gap: 12px; flex-wrap: wrap; }
        .toggle-item { display: flex; align-items: center; gap: 10px; padding: 9px 12px; background: var(--bg-card-alt); border-radius: var(--radius-sm); border: 1px solid var(--border); flex: 1; min-width: 150px; }
        .toggle-item-label { font-size: 12px; font-weight: 500; color: var(--text-primary); cursor: pointer; }
        .toggle-item-desc { font-size: 11px; color: var(--text-muted); }
        .toggle-switch { position: relative; width: 36px; height: 20px; flex-shrink: 0; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .toggle-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background: var(--border); border-radius: 20px; transition: .2s; }
        .toggle-slider::before { content: ''; position: absolute; width: 14px; height: 14px; border-radius: 50%; background: white; left: 3px; bottom: 3px; transition: .2s; }
        .toggle-switch input:checked + .toggle-slider { background: var(--accent); }
        .toggle-switch input:checked + .toggle-slider::before { transform: translateX(16px); }
        .toggle-switch.pin input:checked + .toggle-slider { background: var(--warning); }
        .toggle-switch.archive input:checked + .toggle-slider { background: #64748b; }

        /* DISPO BADGE in form */
        .dispo-preview { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 600; border-radius: 20px; padding: 3px 10px; margin-top: 5px; }
        .dispo-preview.available { background: var(--success-soft); color: var(--success); }
        .dispo-preview.future    { background: var(--warning-soft); color: var(--warning); }
        .dispo-preview.nodate   { background: var(--bg-card-alt); color: var(--text-muted); }

        /* UPLOAD */
        .upload-admin { border: 1.5px dashed rgba(139,92,246,0.3); border-radius: var(--radius-sm); background: var(--bg-input); padding: 16px; cursor: pointer; transition: border-color .2s, background .2s; position: relative; text-align: center; }
        .upload-admin:hover { border-color: var(--accent); background: var(--accent-soft); }
        .upload-admin input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }
        .upload-admin-text { font-size: 12px; color: var(--text-muted); }
        .upload-admin-text strong { color: var(--accent); }
        .upload-preview-wrap { display: none; flex-direction: column; align-items: center; gap: 6px; }
        .upload-preview-img { width: 72px; height: 72px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--border); }
        .upload-preview-change { font-size: 11px; color: var(--accent); text-decoration: underline; cursor: pointer; }
        .current-image-preview { display: flex; align-items: center; gap: 12px; padding: 8px 10px; background: rgba(139,92,246,0.08); border: 1px solid rgba(139,92,246,0.2); border-radius: var(--radius-sm); margin-bottom: 8px; }
        .current-image-preview img { width: 48px; height: 48px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--border); }
        .current-image-preview span { font-size: 12px; color: var(--text-muted); }

        .form-actions { display: flex; gap: 10px; margin-top: 6px; }
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 18px; border-radius: var(--radius-sm); font-size: 13px; font-weight: 500; cursor: pointer; border: none; text-decoration: none; transition: .15s; font-family: inherit; }
        .btn-primary { background: var(--accent); color: #fff; }
        .btn-primary:hover { background: var(--accent-hover); }
        .btn-ghost { background: transparent; color: var(--text-muted); border: 1px solid var(--border); }
        .btn-ghost:hover { background: rgba(139,92,246,0.06); color: var(--text-primary); }
        .form-todo-note { display: flex; align-items: flex-start; gap: 8px; background: rgba(245,158,11,0.07); border: 1px solid rgba(245,158,11,.2); border-radius: var(--radius-sm); padding: 9px 12px; font-size: 12px; color: var(--warning); line-height: 1.5; }
        .form-todo-note svg { flex-shrink: 0; margin-top: 1px; }

        /* TABLE PANEL */
        .table-panel { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-card); transition: background .25s, border-color .25s; }
        .table-panel-header { display: flex; align-items: center; padding: 14px 20px; border-bottom: 1px solid var(--border); gap: 12px; flex-wrap: wrap; }
        .table-panel-title { font-size: 14px; font-weight: 600; flex: 1; color: var(--text-primary); }
        .count-badge { font-size: 11px; font-weight: 600; background: var(--accent-soft); color: var(--accent); padding: 2px 9px; border-radius: 20px; }
        .per-page-select { background: var(--bg-input); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 4px 8px; color: var(--text-muted); font-size: 12px; outline: none; cursor: pointer; transition: background .25s; }
        table { width: 100%; border-collapse: collapse; }
        thead th { background: var(--bg-card-alt); padding: 10px 16px; text-align: left; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; color: var(--text-muted); border-bottom: 1px solid var(--border); cursor: pointer; user-select: none; white-space: nowrap; transition: background .25s; }
        thead th:hover { color: var(--text-primary); }
        thead th.sort-asc::after  { content: ' ↑'; color: var(--accent); }
        thead th.sort-desc::after { content: ' ↓'; color: var(--accent); }
        thead th.no-sort { cursor: default; }
        tbody tr { border-bottom: 1px solid var(--border); transition: background .12s; }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: rgba(139,92,246,0.04); }
        tbody tr.selected { background: rgba(139,92,246,.07); }
        tbody td { padding: 11px 16px; font-size: 13px; color: var(--text-primary); vertical-align: middle; }
        .td-check input[type="checkbox"] { accent-color: var(--accent); width: 14px; height: 14px; cursor: pointer; }
        .td-id { color: var(--text-dim); font-size: 12px; font-family: monospace; }
        .td-name { font-weight: 500; }
        .td-desc { color: var(--text-muted); max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .td-carac { color: var(--text-dim); font-size: 12px; max-width: 100px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .td-marque { font-size: 12px; color: var(--accent); max-width: 100px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .td-marque-empty { font-size: 11px; color: var(--text-dim); font-style: italic; }
        .prix-badge { display: inline-flex; align-items: center; background: var(--success-soft); color: var(--success); padding: 3px 9px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .prix-inline-form { display: flex; align-items: center; gap: 5px; }
        .prix-inline-input { background: var(--bg-input); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 4px 8px; color: var(--text-primary); font-size: 12px; width: 80px; outline: none; }
        .prix-inline-input:focus { border-color: var(--border-focus); }
        .btn-inline-save { background: var(--success-soft); color: var(--success); border: none; border-radius: var(--radius-sm); padding: 4px 8px; font-size: 11px; cursor: pointer; }
        .btn-inline-cancel { background: transparent; color: var(--text-muted); border: none; font-size: 11px; cursor: pointer; padding: 4px 6px; }
        .td-img { position: relative; width: 46px; }
        .td-img-thumb { position: relative; display: inline-block; }
        .td-img-thumb img { width: 42px; height: 42px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--border); cursor: pointer; transition: opacity .15s; display: block; }
        .td-img-thumb img:hover { opacity: .8; border-color: var(--accent); }
        .td-img-status { position: absolute; bottom: -4px; right: -4px; width: 14px; height: 14px; border-radius: 50%; border: 2px solid var(--bg-card); display: flex; align-items: center; justify-content: center; font-size: 7px; font-weight: 700; }
        .td-img-status.ok   { background: var(--success); }
        .td-img-status.miss { background: var(--danger); }
        .td-img-empty { width: 42px; height: 42px; background: var(--bg-card-alt); border: 1px dashed var(--border); border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; font-size: 16px; color: var(--text-dim); }

        /* Status badges in table */
        .status-badge { display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .status-badge.pinned  { background: var(--warning-soft); color: var(--warning); }
        .status-badge.archived { background: rgba(100,116,139,0.15); color: #64748b; }
        .status-badge.available { background: var(--success-soft); color: var(--success); }
        .status-badge.future  { background: var(--warning-soft); color: var(--warning); }
        .status-badge.nodate  { background: var(--bg-card-alt); color: var(--text-dim); }
        .cat-badge { display: inline-flex; align-items: center; background: var(--accent-soft); color: var(--accent); padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 500; max-width: 110px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

        .action-group { display: flex; align-items: center; gap: 4px; flex-wrap: nowrap; }
        .btn-action { display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px; border-radius: var(--radius-sm); font-size: 11px; font-weight: 500; cursor: pointer; text-decoration: none; border: 1px solid transparent; transition: .15s; white-space: nowrap; }
        .btn-view    { background: rgba(139,92,246,.1); color: var(--accent);  border-color: rgba(139,92,246,.2); }
        .btn-view:hover { background: rgba(139,92,246,.2); }
        .btn-fo { background: var(--info-soft); color: var(--info); border-color: rgba(59,130,246,.2); }
        .btn-fo:hover { background: rgba(59,130,246,.2); }
        .btn-edit-a { background: var(--warning-soft); color: var(--warning); border-color: rgba(245,158,11,.2); }
        .btn-edit-a:hover { background: rgba(245,158,11,.2); }
        .btn-delete { background: var(--danger-soft); color: var(--danger); border-color: rgba(239,68,68,.2); }
        .btn-delete:hover { background: rgba(239,68,68,.18); }
        .btn-pin { background: var(--warning-soft); color: var(--warning); border-color: rgba(245,158,11,.2); }
        .btn-pin:hover { background: rgba(245,158,11,.2); }
        .btn-archive { background: rgba(100,116,139,0.12); color: #64748b; border-color: rgba(100,116,139,.2); }
        .btn-archive:hover { background: rgba(100,116,139,.22); }

        /* ARCHIVED TABLE */
        .archived-table tbody tr { opacity: 0.75; }
        .archived-table tbody tr:hover { opacity: 1; }
        .btn-restore { background: var(--success-soft); color: var(--success); border: 1px solid rgba(16,185,129,.2); }
        .btn-restore:hover { background: rgba(16,185,129,.2); }

        /* PAGINATION */
        .pagination { display: flex; align-items: center; justify-content: space-between; padding: 12px 20px; border-top: 1px solid var(--border); }
        .pagination-info { font-size: 12px; color: var(--text-muted); }
        .pagination-buttons { display: flex; gap: 4px; }
        .page-btn { background: var(--bg-card-alt); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 5px 10px; font-size: 12px; color: var(--text-muted); cursor: pointer; transition: all .15s; min-width: 30px; text-align: center; }
        .page-btn:hover { background: rgba(139,92,246,0.08); color: var(--text-primary); }
        .page-btn.active { background: var(--accent); border-color: var(--accent); color: #fff; }
        .page-btn:disabled { opacity: .4; cursor: not-allowed; }

        /* EMPTY STATE */
        .empty-state { text-align: center; padding: 52px 20px; color: var(--text-muted); }
        .empty-icon { font-size: 36px; margin-bottom: 12px; opacity: .4; }
        .empty-text { font-size: 14px; font-weight: 500; margin-bottom: 6px; }
        .empty-hint { font-size: 13px; color: var(--text-dim); }

        /* MODALS */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.65); z-index: 200; align-items: center; justify-content: center; padding: 20px; }
        .modal-overlay.open { display: flex; }
        .modal-box { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 28px; width: 380px; max-width: 100%; box-shadow: 0 20px 60px rgba(0,0,0,.3); }
        .modal-title { font-size: 16px; font-weight: 600; margin-bottom: 8px; color: var(--text-primary); }
        .modal-text  { font-size: 13px; color: var(--text-muted); margin-bottom: 22px; }
        .modal-actions { display: flex; gap: 10px; justify-content: flex-end; }
        .preview-modal { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-lg); width: 720px; max-width: 100%; overflow: hidden; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,.3); }
        .preview-modal-img { width: 100%; height: 280px; object-fit: cover; display: block; }
        .preview-modal-img-empty { width: 100%; height: 200px; background: var(--bg-card-alt); display: flex; align-items: center; justify-content: center; font-size: 60px; color: var(--text-dim); }
        .preview-modal-body { padding: 24px 28px 28px; }
        .preview-modal-title { font-size: 20px; font-weight: 600; margin-bottom: 6px; color: var(--text-primary); }
        .preview-modal-price { display: inline-flex; align-items: center; background: var(--success-soft); color: var(--success); padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: 600; margin-bottom: 16px; }
        .preview-section-label { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: .07em; color: var(--text-dim); margin-bottom: 5px; margin-top: 14px; }
        .preview-text { font-size: 13px; color: var(--text-muted); line-height: 1.65; }
        .preview-carac { font-size: 13px; color: var(--accent); background: var(--accent-soft); border-radius: var(--radius-sm); padding: 8px 12px; }
        .preview-meta-row { display: flex; align-items: center; gap: 10px; margin-top: 18px; padding-top: 14px; border-top: 1px solid var(--border); flex-wrap: wrap; }
        .preview-close-btn { margin-left: auto; display: flex; align-items: center; gap: 6px; background: var(--danger-soft); color: var(--danger); border: 1px solid rgba(239,68,68,.2); border-radius: var(--radius-sm); padding: 6px 14px; font-size: 12px; font-weight: 500; cursor: pointer; }
        .preview-fo-btn { display: flex; align-items: center; gap: 6px; background: var(--info-soft); color: var(--info); border: 1px solid rgba(59,130,246,.2); border-radius: var(--radius-sm); padding: 6px 14px; font-size: 12px; font-weight: 500; text-decoration: none; }
        .preview-fo-btn:hover { background: rgba(59,130,246,.2); }
        .preview-marque-badge { display: inline-flex; align-items: center; gap: 5px; background: var(--accent-soft); color: var(--accent); border: 1px solid rgba(139,92,246,.2); border-radius: 20px; padding: 3px 10px; font-size: 11px; font-weight: 600; }
        .preview-note { display: flex; align-items: flex-start; gap: 8px; background: rgba(245,158,11,0.06); border: 1px solid rgba(245,158,11,.2); border-radius: var(--radius-sm); padding: 9px 12px; font-size: 12px; color: var(--warning); margin-top: 10px; line-height: 1.5; }
        .preview-note .note-label { font-weight: 700; flex-shrink: 0; }
        .preview-dispo { display: inline-flex; align-items: center; gap: 5px; font-size: 12px; font-weight: 600; border-radius: 20px; padding: 3px 10px; margin-top: 8px; margin-bottom: 4px; }
        .preview-dispo.available { background: var(--success-soft); color: var(--success); }
        .preview-dispo.future    { background: var(--warning-soft); color: var(--warning); }

        @media (max-width: 1300px) { .kpi-strip { grid-template-columns: repeat(4, 1fr); } }
        @media (max-width: 1000px) { .kpi-strip { grid-template-columns: repeat(3, 1fr); } }
        @media (max-width: 900px) { .kpi-strip { grid-template-columns: repeat(2, 1fr); } .form-grid { grid-template-columns: 1fr; } .sidebar { display: none; } .topbar, .main { left: 0; margin-left: 0; } }
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
        <a class="nav-item" href="#"><svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>Home</a>
        <a class="nav-item" href="#"><svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>Users</a>
        <a class="nav-item" href="#"><svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>Complaints</a>
        <div class="nav-section-label" style="margin-top:8px">Modules</div>
        <a class="nav-item" href="#"><svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>Offers & Applications</a>
        <a class="nav-item" href="#"><svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>Events & Forums</a>
        <a class="nav-item" href="#"><svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>Campaigns & Contracts</a>
        <a class="nav-item active" href="index.php"><svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 10V11"/></svg>Products</a>
        <a class="nav-item" href="#"><svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>Posts & Comments</a>
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
        <span class="current">Products</span>
    </div>
    <div class="topbar-actions">
        <!-- THEME TOGGLE -->
        <button class="theme-toggle" id="themeToggleBtn" title="Switch theme" type="button">
            <svg class="icon-sun" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><path stroke-linecap="round" d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
            <div class="toggle-track-pill" id="togglePill"><div class="toggle-knob" id="toggleKnob"></div></div>
            <svg class="icon-moon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
            <span class="toggle-label" id="themeLabel">Dark</span>
        </button>
        <div class="search-wrap">
            <svg class="search-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
            <input type="text" class="search-input" id="searchInput">
        </div>
        <a href="<?= $baseUrl ?>/Vue/FrontOffice/produit/indexC.php" target="_blank" class="btn-fo-link" title="View catalog on creator side">
            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            View FO
        </a>
        <a href="?export_csv=1" class="btn-export">
            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Export CSV
        </a>
        <a href="?add=1" class="btn-add">
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            New Product
        </a>
    </div>
</div>

<!-- CONTENT -->
<main class="main">
<div class="content">

    <div class="page-header">
        <div>
            <div class="page-title">Product Management</div>
            <div class="page-subtitle">Supervise, add, edit and analyze all products on the platform.</div>
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

    <!-- KPI STRIP — 7 cards including pinned + archived -->
    <div class="kpi-strip">
        <div class="kpi-card kpi-accent">
            <div class="kpi-label">Total Products</div>
            <div class="kpi-value"><?= $totalProduits ?></div>
            <div class="kpi-sub">Active catalog</div>
        </div>
        <div class="kpi-card kpi-info">
            <div class="kpi-label">Avg. Price</div>
            <div class="kpi-value"><?= number_format($prixMoyen, 2) ?></div>
            <div class="kpi-sub"><?= DEVISE ?> — catalog average</div>
        </div>
        <div class="kpi-card kpi-success">
            <div class="kpi-label">Catalog Value</div>
            <div class="kpi-value"><?= number_format($valeurCatalogue, 0) ?></div>
            <div class="kpi-sub"><?= DEVISE ?> — cumulative total</div>
        </div>
        <div class="kpi-card kpi-warning">
            <div class="kpi-label">Highest Price</div>
            <div class="kpi-value"><?= number_format($prixMax, 2) ?></div>
            <div class="kpi-sub"><?= DEVISE ?> — most expensive</div>
        </div>
        <div class="kpi-card kpi-purple">
            <div class="kpi-label">With Image</div>
            <div class="kpi-value"><?= $avecImage ?></div>
            <div class="kpi-sub"><?= $totalProduits > 0 ? round($avecImage / $totalProduits * 100) : 0 ?>% of catalog</div>
        </div>
        <div class="kpi-card kpi-warning" style="--warning:#f59e0b;">
            <div class="kpi-label">Pinned</div>
            <div class="kpi-value"><?= $nbEpingles ?></div>
            <div class="kpi-sub">Highlighted in catalog</div>
        </div>
        <div class="kpi-card kpi-archive">
            <div class="kpi-label">Archived</div>
            <div class="kpi-value"><?= $totalArchives ?></div>
            <div class="kpi-sub">Hidden from catalog</div>
        </div>
    </div>

    <?php if ($sanImage > 0): ?>
    <div class="img-alert-banner">
        <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        <span><strong><?= $sanImage ?> product<?= $sanImage > 1 ? 's' : '' ?></strong> without an image — this may hurt presentation on the creator side.</span>
        <button class="img-alert-btn" id="btnFilterWithout">Filter</button>
    </div>
    <?php endif; ?>

    <!-- MOST EXPENSIVE PRODUCT -->
    <?php if ($produitPlusCher): ?>
    <div class="highlight-card">
        <?php if (!empty($produitPlusCher['image'])): ?>
            <img src="<?= $baseUrl ?>/Vue/public/produits/<?= htmlspecialchars($produitPlusCher['image']) ?>" alt="" class="highlight-img">
        <?php else: ?>
            <div class="highlight-img-empty">📦</div>
        <?php endif; ?>
        <div class="highlight-info">
            <div class="highlight-label">⭐ Most expensive product</div>
            <div class="highlight-name"><?= htmlspecialchars($produitPlusCher['nomProduit']) ?></div>
            <div class="highlight-sub"><?= htmlspecialchars(mb_substr($produitPlusCher['description'] ?? '', 0, 90)) ?>…</div>
        </div>
        <div class="highlight-badge"><?= number_format($prixMax, 2) ?> <?= DEVISE ?></div>
    </div>
    <?php endif; ?>

    <!-- FORM PANEL -->
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
            <span class="form-panel-title"><?= $produitUpdate ? 'Edit Product' : 'Add a Product' ?></span>
            <span class="form-panel-badge <?= $produitUpdate ? 'badge-edit' : 'badge-add' ?>"><?= $produitUpdate ? 'Editing' : 'New' ?></span>
        </div>
        <div class="form-body">
            <form method="POST" action="index.php" enctype="multipart/form-data" id="produitFormBO" novalidate>
                <input type="hidden" name="action" value="<?= $produitUpdate ? 'update' : 'add' ?>">
                <input type="hidden" name="id" value="<?= $produitUpdate['idProduit'] ?? '' ?>">
                <?php if ($produitUpdate): ?>
                <input type="hidden" name="estEpingle" value="<?= (int)($produitUpdate['estEpingle'] ?? 0) ?>" id="epingleHiddenBO">
                <input type="hidden" name="estArchive" value="<?= (int)($produitUpdate['estArchive'] ?? 0) ?>" id="archiveHiddenBO">
                <?php endif; ?>

                <div class="form-grid">
                    <!-- Row 1: Name + Price -->
                    <div class="form-group">
                        <label class="form-label">Product name *</label>
                        <input type="text" name="nom" class="form-control"
                               value="<?= htmlspecialchars($produitUpdate['nomProduit'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Price (<?= DEVISE ?>) *</label>
                        <input type="text" name="prix" class="form-control"
                               value="<?= htmlspecialchars($produitUpdate['prix'] ?? '') ?>">
                    </div>

                    <!-- Row 2: Category + Availability Date -->
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select name="categorie" class="form-control">
                            <option value="">— Select a category —</option>
                            <?php foreach ($categoriesDisponibles as $cat): ?>
                                <option value="<?= htmlspecialchars($cat) ?>"
                                    <?= ($produitUpdate && ($produitUpdate['categorie'] ?? '') === $cat) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat) ?>
                                </option>
                            <?php endforeach; ?>
                            <?php if ($produitUpdate && !empty($produitUpdate['categorie']) && !in_array($produitUpdate['categorie'], $categoriesDisponibles)): ?>
                                <option value="<?= htmlspecialchars($produitUpdate['categorie']) ?>" selected><?= htmlspecialchars($produitUpdate['categorie']) ?></option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Availability date <span style="font-size:10px;color:var(--text-dim);font-weight:400;">(optional)</span></label>
                        <input type="text" name="dateDisponibilite" class="form-control"
                               value="<?= htmlspecialchars($produitUpdate['dateDisponibilite'] ?? '') ?>">
                        <div class="dispo-preview nodate" id="dispoPreviewBO" style="margin-top:6px;">📅 Available now</div>
                    </div>

                    <!-- Row 3: Description -->
                    <div class="form-group form-col-full">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control"><?= htmlspecialchars($produitUpdate['description'] ?? '') ?></textarea>
                    </div>

                    <!-- Row 4: Characteristics -->
                    <div class="form-group form-col-full">
                        <label class="form-label">Characteristics / Tags <span style="font-size:10px;color:var(--text-dim);">(comma-separated)</span></label>
                        <textarea name="caracteristiques" class="form-control" style="min-height:60px"><?= htmlspecialchars($produitUpdate['caracteristiques'] ?? '') ?></textarea>
                    </div>

                    <!-- Row 5: Internal note -->
                    <div class="form-group form-col-full">
                        <label class="form-label">Internal note <span style="font-size:10px;color:var(--text-dim);">🔒 Admin only</span></label>
                        <div class="note-interne-wrap">
                            <div class="note-interne-header">🔒 Visible to admin team only</div>
                            <textarea name="noteInterne" class="note-interne-ctrl"><?= htmlspecialchars($produitUpdate['noteInterne'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <!-- Row 6: Pin + Archive toggles (edit mode only) -->
                    <?php if ($produitUpdate): ?>
                    <div class="form-group form-col-full">
                        <label class="form-label">Status flags</label>
                        <div class="toggle-row">
                            <div class="toggle-item">
                                <div>
                                    <div class="toggle-item-label">📌 Pin this product</div>
                                    <div class="toggle-item-desc">Shown at the top of the catalog</div>
                                </div>
                                <label class="toggle-switch pin">
                                    <input type="checkbox" id="boToggleEpingle"
                                           <?= !empty($produitUpdate['estEpingle']) ? 'checked' : '' ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            <div class="toggle-item">
                                <div>
                                    <div class="toggle-item-label">🗄️ Archive this product</div>
                                    <div class="toggle-item-desc">Hidden from the active catalog</div>
                                </div>
                                <label class="toggle-switch archive">
                                    <input type="checkbox" id="boToggleArchive"
                                           <?= !empty($produitUpdate['estArchive']) ? 'checked' : '' ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Row 7: Image -->
                    <div class="form-group form-col-full">
                        <label class="form-label">Product image</label>
                        <?php if ($produitUpdate && !empty($produitUpdate['image'])): ?>
                        <div class="current-image-preview">
                            <img src="<?= $baseUrl ?>/Vue/public/produits/<?= htmlspecialchars($produitUpdate['image']) ?>" alt="">
                            <span>Current image — leave empty to keep it.</span>
                        </div>
                        <?php endif; ?>
                        <div class="upload-admin" id="uploadZone">
                            <input type="file" name="image" id="fileInputAdmin">
                            <div class="upload-admin-text" id="uploadText">
                                <strong>Click to upload</strong> an image
                                <div style="font-size:11px;color:var(--text-dim);margin-top:4px">JPG, PNG, WEBP — 2 MB max</div>
                            </div>
                            <div class="upload-preview-wrap" id="uploadPreview">
                                <img src="" alt="" class="upload-preview-img" id="previewBeforeUpload">
                                <div class="upload-preview-change">Change image</div>
                            </div>
                        </div>
                    </div>

                    <?php if (!$produitUpdate): ?>
                    <div class="form-group form-col-full">
                        <div class="form-todo-note">
                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <span><strong>TODO dev:</strong> idMarque is hardcoded to <code style="background:rgba(245,158,11,.15);padding:1px 5px;border-radius:3px">1</code>. After merging the user module, replace with <code style="background:rgba(245,158,11,.15);padding:1px 5px;border-radius:3px">$_SESSION['userId']</code>.</span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="form-actions" style="margin-top:16px">
                    <button type="submit" class="btn btn-primary">
                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <?= $produitUpdate ? 'Save changes' : 'Add product' ?>
                    </button>
                    <a href="index.php" class="btn btn-ghost">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- FILTER BAR -->
    <div class="filter-bar">
        <div class="filter-group">
            <div class="filter-label">Sort by</div>
            <select class="filter-select" id="sortColSelect">
                <option value="">— Default —</option>
                <option value="nom-asc">Name A→Z</option>
                <option value="nom-desc">Name Z→A</option>
                <option value="prix-asc">Price ascending</option>
                <option value="prix-desc">Price descending</option>
                <option value="id-asc">ID ascending</option>
                <option value="id-desc">ID descending</option>
            </select>
        </div>
        <div class="filter-group">
            <div class="filter-label">Price range (<?= DEVISE ?>)</div>
            <div class="price-range-wrap">
                <input type="text" class="price-input" id="priceMin">
                <span class="price-sep">—</span>
                <input type="text" class="price-input" id="priceMax">
            </div>
        </div>
        <div class="filter-group">
            <div class="filter-label">Category</div>
            <select class="filter-select" id="catFilterSelect">
                <option value="">All categories</option>
                <?php foreach ($categoriesDispos as $cat): ?>
                    <option value="<?= htmlspecialchars(strtolower($cat)) ?>"><?= htmlspecialchars($cat) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-group">
            <div class="filter-label">Image</div>
            <div class="image-filter-row">
                <button class="chip active" id="chip-all">All</button>
                <button class="chip"        id="chip-with">With image</button>
                <button class="chip"        id="chip-without">No image</button>
            </div>
        </div>
        <div class="filter-group">
            <div class="filter-label">Status</div>
            <select class="filter-select" id="statusFilter">
                <option value="">All</option>
                <option value="pinned">Pinned only</option>
                <option value="available">Available now</option>
                <option value="future">Future availability</option>
            </select>
        </div>
        <button class="btn-reset-filter" id="btnResetFilter">Reset</button>
    </div>

    <!-- TABS -->
    <div class="tab-bar">
        <button class="tab-btn active" id="tab-actifs">
            Active <span class="tab-pill"><?= $totalProduits ?></span>
        </button>
        <button class="tab-btn" id="tab-archives">
            Archived <span class="tab-pill archive"><?= $totalArchives ?></span>
        </button>
    </div>

    <!-- TAB: ACTIVE PRODUCTS -->
    <div class="tab-content active" id="content-actifs">
        <!-- BULK BAR -->
        <div class="bulk-bar" id="bulkBar">
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="color:var(--accent)"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            <span class="bulk-count" id="bulkCount">0 product(s) selected</span>
            <form method="POST" action="index.php" id="bulkForm" style="margin-left:auto;display:flex;gap:8px;align-items:center">
                <input type="hidden" name="action_masse" value="supprimer_selection">
                <div id="bulkIdsContainer"></div>
                <button type="button" class="btn-bulk-delete" id="btnBulkDelete">
                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Delete selection
                </button>
                <button type="button" class="btn-bulk-cancel" id="btnBulkCancel">Cancel</button>
            </form>
        </div>

        <!-- ACTIVE TABLE -->
        <div class="table-panel">
            <div class="table-panel-header">
                <span class="table-panel-title">Product list</span>
                <span class="count-badge" id="visibleCount"><?= $totalProduits ?> entr<?= $totalProduits > 1 ? 'ies' : 'y' ?></span>
                <select class="per-page-select" id="perPageSelect">
                    <option value="10">10 / page</option>
                    <option value="25">25 / page</option>
                    <option value="50">50 / page</option>
                    <option value="999">Show all</option>
                </select>
            </div>
            <?php if (empty($liste)): ?>
            <div class="empty-state">
                <div class="empty-icon">📦</div>
                <div class="empty-text">No products in the catalog</div>
                <div class="empty-hint">Add your first product using the button above.</div>
            </div>
            <?php else: ?>
            <table id="produitsTable">
                <thead>
                    <tr>
                        <th class="no-sort" style="width:36px"><input type="checkbox" id="selectAll" style="accent-color:var(--accent);cursor:pointer;"></th>
                        <th data-col="id" class="sortable-header">#ID</th>
                        <th class="no-sort">Image</th>
                        <th data-col="nom" class="sortable-header">Name</th>
                        <th class="no-sort">Category</th>
                        <th class="no-sort">Description</th>
                        <th class="no-sort">Tags</th>
                        <th class="no-sort">Brand</th>
                        <th data-col="prix" class="sortable-header">Price (<?= DEVISE ?>)</th>
                        <th class="no-sort">Availability</th>
                        <th class="no-sort">Status</th>
                        <th class="no-sort">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php foreach ($liste as $p):
                        $isPinned  = !empty($p['estEpingle']);
                        $dispo     = $p['dateDisponibilite'] ?? null;
                        $dispoFuture = $dispo && strtotime($dispo) > time();
                    ?>
                    <tr data-id="<?= $p['idProduit'] ?>"
                        data-nom="<?= htmlspecialchars(strtolower($p['nomProduit'])) ?>"
                        data-desc="<?= htmlspecialchars(strtolower($p['description'] ?? '')) ?>"
                        data-prix="<?= (float)$p['prix'] ?>"
                        data-hasimg="<?= empty($p['image']) ? '0' : '1' ?>"
                        data-cat="<?= htmlspecialchars(strtolower($p['categorie'] ?? '')) ?>"
                        data-epingle="<?= (int)$isPinned ?>"
                        data-dispo="<?= htmlspecialchars($dispo ?? '') ?>">
                        <td class="td-check"><input type="checkbox" class="row-check" value="<?= $p['idProduit'] ?>"></td>
                        <td class="td-id">#<?= htmlspecialchars($p['idProduit']) ?></td>
                        <td class="td-img">
                            <div class="td-img-thumb">
                                <?php if (!empty($p['image'])): ?>
                                    <img src="<?= $baseUrl ?>/Vue/public/produits/<?= htmlspecialchars($p['image']) ?>" alt="" class="preview-thumb" data-id="<?= $p['idProduit'] ?>" title="Quick preview">
                                    <div class="td-img-status ok" title="Image present">✓</div>
                                <?php else: ?>
                                    <div class="td-img-empty">📦</div>
                                    <div class="td-img-status miss" title="Image missing">✕</div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="td-name"><?= htmlspecialchars($p['nomProduit']) ?></td>
                        <td>
                            <?php if (!empty($p['categorie'])): ?>
                                <span class="cat-badge"><?= htmlspecialchars($p['categorie']) ?></span>
                            <?php else: ?>
                                <span style="font-size:11px;color:var(--text-dim);">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="td-desc" title="<?= htmlspecialchars($p['description'] ?? '') ?>"><?= htmlspecialchars($p['description'] ?? '') ?></td>
                        <td class="td-carac" title="<?= htmlspecialchars($p['caracteristiques'] ?? '') ?>"><?= htmlspecialchars($p['caracteristiques'] ?? '') ?></td>
                        <td>
                            <?php if (!empty($p['nomMarque'])): ?>
                                <span class="td-marque"><?= htmlspecialchars($p['nomMarque']) ?></span>
                            <?php elseif (!empty($p['idMarque'])): ?>
                                <span class="td-marque" style="opacity:.6">ID #<?= (int)$p['idMarque'] ?></span>
                            <?php else: ?>
                                <span class="td-marque-empty">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="prix-badge prix-display" id="prixDisplay-<?= $p['idProduit'] ?>"
                                  data-id="<?= $p['idProduit'] ?>"
                                  style="cursor:pointer" title="Click to edit price">
                                <?= number_format((float)$p['prix'], 2) ?> <?= DEVISE ?>
                            </span>
                            <form method="POST" action="index.php" class="prix-inline-form" id="prixForm-<?= $p['idProduit'] ?>" style="display:none">
                                <input type="hidden" name="action" value="update_prix">
                                <input type="hidden" name="id" value="<?= $p['idProduit'] ?>">
                                <input type="text" name="prix" class="prix-inline-input" id="prixInput-<?= $p['idProduit'] ?>" value="<?= $p['prix'] ?>">
                                <button type="submit" class="btn-inline-save" title="Save">✓</button>
                                <button type="button" class="btn-inline-cancel" title="Cancel" data-id="<?= $p['idProduit'] ?>">✕</button>
                            </form>
                        </td>
                        <td>
                            <?php if ($dispo): ?>
                                <span class="status-badge <?= $dispoFuture ? 'future' : 'available' ?>">
                                    <?= $dispoFuture ? '⏳ ' . date('d/m/Y', strtotime($dispo)) : '✅ ' . date('d/m/Y', strtotime($dispo)) ?>
                                </span>
                            <?php else: ?>
                                <span class="status-badge nodate">📅 Now</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display:flex;flex-direction:column;gap:4px;">
                                <?php if ($isPinned): ?><span class="status-badge pinned">📌 Pinned</span><?php endif; ?>
                                <?php if (!empty($p['noteInterne'])): ?><span class="status-badge" style="background:rgba(245,158,11,0.1);color:var(--warning);">🔒 Note</span><?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div class="action-group">
                                <button type="button" class="btn-action btn-view" data-id="<?= $p['idProduit'] ?>" title="Admin preview">
                                    <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    View
                                </button>
                                <a href="<?= $baseUrl ?>/Vue/FrontOffice/produit/indexC.php?voir=<?= $p['idProduit'] ?>" target="_blank" class="btn-action btn-fo" title="View on creator side">
                                    <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    FO
                                </a>
                                <a href="?edit=<?= $p['idProduit'] ?>" class="btn-action btn-edit-a">
                                    <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    Edit
                                </a>
                                <button type="button" class="btn-action btn-pin" data-id="<?= $p['idProduit'] ?>" title="<?= $isPinned ? 'Unpin' : 'Pin' ?>">
                                    📌 <?= $isPinned ? 'Unpin' : 'Pin' ?>
                                </button>
                                <button type="button" class="btn-action btn-archive" data-id="<?= $p['idProduit'] ?>" title="Archive">
                                    🗄️ Archive
                                </button>
                                <button type="button" class="btn-action btn-delete" data-id="<?= $p['idProduit'] ?>" data-name="<?= htmlspecialchars($p['nomProduit'], ENT_QUOTES) ?>">
                                    <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="pagination" id="paginationBar">
                <div class="pagination-info" id="paginationInfo"></div>
                <div class="pagination-buttons" id="paginationButtons"></div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- TAB: ARCHIVED PRODUCTS -->
    <div class="tab-content" id="content-archives">
        <div class="table-panel">
            <div class="table-panel-header">
                <span class="table-panel-title">Archived products</span>
                <span class="count-badge" style="background:rgba(100,116,139,0.15);color:#64748b;"><?= $totalArchives ?> entr<?= $totalArchives > 1 ? 'ies' : 'y' ?></span>
            </div>
            <?php if (empty($listeArchives)): ?>
            <div class="empty-state">
                <div class="empty-icon">🗄️</div>
                <div class="empty-text">No archived products</div>
                <div class="empty-hint">Products you archive will appear here and can be restored at any time.</div>
            </div>
            <?php else: ?>
            <table class="archived-table">
                <thead>
                    <tr>
                        <th class="no-sort">Image</th>
                        <th class="no-sort">Name</th>
                        <th class="no-sort">Category</th>
                        <th class="no-sort">Brand</th>
                        <th class="no-sort">Price</th>
                        <th class="no-sort">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($listeArchives as $a): ?>
                    <tr>
                        <td class="td-img">
                            <div class="td-img-thumb">
                                <?php if (!empty($a['image'])): ?>
                                    <img src="<?= $baseUrl ?>/Vue/public/produits/<?= htmlspecialchars($a['image']) ?>" alt="">
                                <?php else: ?>
                                    <div class="td-img-empty">📦</div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="td-name" style="opacity:.7;"><?= htmlspecialchars($a['nomProduit']) ?></td>
                        <td>
                            <?php if (!empty($a['categorie'])): ?>
                                <span class="cat-badge" style="opacity:.7;"><?= htmlspecialchars($a['categorie']) ?></span>
                            <?php else: ?><span style="color:var(--text-dim);font-size:11px;">—</span><?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($a['idMarque'])): ?>
                                <span class="td-marque" style="opacity:.6;">ID #<?= (int)$a['idMarque'] ?></span>
                            <?php else: ?><span class="td-marque-empty">—</span><?php endif; ?>
                        </td>
                        <td><span class="prix-badge" style="opacity:.7;"><?= number_format((float)$a['prix'], 2) ?> <?= DEVISE ?></span></td>
                        <td>
                            <div class="action-group">
                                <button type="button" class="btn-action btn-restore" data-id="<?= $a['idProduit'] ?>" title="Restore to catalog">
                                    ♻️ Restore
                                </button>
                                <a href="?edit=<?= $a['idProduit'] ?>" class="btn-action btn-edit-a">Edit</a>
                                <button type="button" class="btn-action btn-delete" data-id="<?= $a['idProduit'] ?>" data-name="<?= htmlspecialchars($a['nomProduit'], ENT_QUOTES) ?>">
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

</div>
</main>

<!-- MODAL DELETE -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal-box">
        <div class="modal-title">Confirm deletion</div>
        <div class="modal-text" id="deleteModalText">Are you sure you want to delete this product? This action is irreversible.</div>
        <div class="modal-actions">
            <button class="btn btn-ghost" id="btnCancelDelete">Cancel</button>
            <a class="btn" id="confirmDeleteBtn" href="#" style="background:var(--danger);color:#fff;">Delete</a>
        </div>
    </div>
</div>

<!-- MODAL PREVIEW -->
<div class="modal-overlay" id="previewModal">
    <div class="preview-modal" id="previewBox">
        <div id="previewImgWrap"></div>
        <div class="preview-modal-body">
            <div id="previewTitle" class="preview-modal-title"></div>
            <div id="previewPrice" class="preview-modal-price"></div>
            <div id="previewMarqueBadge" style="margin-bottom:8px"></div>
            <div id="previewCatBadge" style="margin-bottom:8px"></div>
            <div id="previewDispo" style="margin-bottom:4px"></div>
            <div class="preview-section-label">Description</div>
            <div id="previewDesc" class="preview-text"></div>
            <div class="preview-section-label">Characteristics / Tags</div>
            <div id="previewCarac" class="preview-carac"></div>
            <div id="previewNoteWrap"></div>
            <div class="preview-meta-row">
                <span style="font-size:12px;color:var(--text-dim)">Product ID: <span id="previewId" style="color:var(--text-muted)"></span></span>
                <a href="#" id="previewFoBtn" target="_blank" class="preview-fo-btn">
                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    View on creator side
                </a>
                <button class="preview-close-btn" id="previewCloseBtn">
                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function validatePriceInput(el) {
    let v = parseFloat(el.value);
    if (!isNaN(v) && v < 0) {
        el.value = '0';
    }
}

// ── DYNAMIC FORM INITIALIZATION ──────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    // Set placeholders
    const fieldsWithPlaceholders = {
        'searchInput': 'Search a product…',
        'nom': 'e.g. Leather handbag',
        'prix': '0.00',
        'dateDisponibilite': 'YYYY-MM-DD (optional)',
        'description': 'Detailed product description…',
        'caracteristiques': 'Bio, Vegan, Premium, Made in France…',
        'noteInterne': 'Briefing notes, moderation remarks, follow-up actions…',
        'priceMin': 'Min',
        'priceMax': 'Max',
        'fileInputAdmin': ''
    };

    Object.entries(fieldsWithPlaceholders).forEach(([id, placeholder]) => {
        const el = document.getElementById(id);
        if (el && placeholder) el.placeholder = placeholder;
    });

    // ADD EVENT LISTENERS FOR FORM CONTROLS
    // searchInput
    const searchInput = document.getElementById('searchInput');
    if (searchInput) searchInput.addEventListener('input', filterTable);

    // Price filters
    const priceMin = document.getElementById('priceMin');
    const priceMax = document.getElementById('priceMax');
    if (priceMin) priceMin.addEventListener('input', function() {
        validatePriceInput(this);
        filterTable();
    });
    if (priceMax) priceMax.addEventListener('input', function() {
        validatePriceInput(this);
        filterTable();
    });

    // Sort, Category, Status filters
    const sortSelect = document.getElementById('sortColSelect');
    const catFilter = document.getElementById('catFilterSelect');
    const statusFilter = document.getElementById('statusFilter');
    const perPageSelect = document.getElementById('perPageSelect');

    if (sortSelect) sortSelect.addEventListener('change', applySortSelect);
    if (catFilter) catFilter.addEventListener('change', filterTable);
    if (statusFilter) statusFilter.addEventListener('change', filterTable);
    if (perPageSelect) perPageSelect.addEventListener('change', () => renderPage(1));

    // Checkbox controls
    const selectAll = document.getElementById('selectAll');
    if (selectAll) selectAll.addEventListener('change', function() {
        toggleSelectAll(this);
    });

    const rowChecks = document.querySelectorAll('.row-check');
    rowChecks.forEach(check => {
        check.addEventListener('change', updateBulkBar);
    });

    // Pin and Archive toggles
    const boToggleEpingle = document.getElementById('boToggleEpingle');
    const boToggleArchive = document.getElementById('boToggleArchive');

    if (boToggleEpingle) {
        boToggleEpingle.addEventListener('change', function() {
            document.getElementById('epingleHiddenBO').value = this.checked ? 1 : 0;
        });
    }

    if (boToggleArchive) {
        boToggleArchive.addEventListener('change', function() {
            document.getElementById('archiveHiddenBO').value = this.checked ? 1 : 0;
        });
    }

    // Inline price inputs for table
    document.querySelectorAll('[id^="prixInput-"]').forEach(input => {
        input.addEventListener('input', function() {
            validatePriceInput(this);
        });
    });

    // Inline price edit triggers
    document.querySelectorAll('.prix-display').forEach(span => {
        const id = span.dataset.id;
        if (!id) return;
        span.addEventListener('click', () => showInlineEdit(id));
    });

    // Table preview triggers
    document.querySelectorAll('.preview-thumb').forEach(img => {
        const id = img.dataset.id;
        if (!id) return;
        img.addEventListener('click', () => openPreview(id));
    });
    document.querySelectorAll('.btn-view').forEach(btn => {
        const id = btn.dataset.id;
        if (!id) return;
        btn.addEventListener('click', () => openPreview(id));
    });

    // Row actions
    document.querySelectorAll('.btn-pin').forEach(btn => {
        const id = btn.dataset.id;
        if (!id) return;
        btn.addEventListener('click', () => ajaxToggleEpingle(id));
    });
    document.querySelectorAll('.btn-archive').forEach(btn => {
        const id = btn.dataset.id;
        if (!id) return;
        btn.addEventListener('click', () => ajaxToggleArchive(id));
    });
    document.querySelectorAll('.btn-delete').forEach(btn => {
        const id = btn.dataset.id;
        const name = btn.dataset.name;
        if (!id || !name) return;
        btn.addEventListener('click', () => openDeleteModal(id, name));
    });
    document.querySelectorAll('.btn-restore').forEach(btn => {
        const id = btn.dataset.id;
        if (!id) return;
        btn.addEventListener('click', () => ajaxToggleArchive(id));
    });

    // Bulk actions
    const btnBulkDelete = document.getElementById('btnBulkDelete');
    const btnBulkCancel = document.getElementById('btnBulkCancel');
    const bulkForm = document.getElementById('bulkForm');
    if (btnBulkDelete && bulkForm) {
        btnBulkDelete.addEventListener('click', () => {
            if (confirmBulk()) bulkForm.submit();
        });
    }
    if (btnBulkCancel) {
        btnBulkCancel.addEventListener('click', clearSelection);
    }

    // Image filter chips
    ['all','with','without'].forEach(value => {
        const chip = document.getElementById('chip-' + value);
        if (!chip) return;
        chip.addEventListener('click', () => setImageFilter(value));
    });
    const btnResetFilter = document.getElementById('btnResetFilter');
    if (btnResetFilter) btnResetFilter.addEventListener('click', resetFilters);

    const produitFormBO = document.getElementById('produitFormBO');
    if (produitFormBO) {
        produitFormBO.addEventListener('submit', function(e) {
            if (!validateForm()) e.preventDefault();
        });
    }

    // Modals
    const previewModal = document.getElementById('previewModal');
    if (previewModal) previewModal.addEventListener('click', closePreviewOutside);
    const previewClose = document.getElementById('previewCloseBtn');
    if (previewClose) previewClose.addEventListener('click', closePreview);
    const btnCancelDelete = document.getElementById('btnCancelDelete');
    if (btnCancelDelete) btnCancelDelete.addEventListener('click', closeDeleteModal);
});

// ── THEME ────────────────────────────────────────────────────────
const STORAGE_KEY = 'cre8connect_admin_theme';
function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    const isDark = theme === 'dark';
    const pill = document.getElementById('togglePill');
    const knob = document.getElementById('toggleKnob');
    const label = document.getElementById('themeLabel');
    if (pill)  pill.classList.toggle('active', !isDark);
    if (knob)  knob.classList.toggle('right', !isDark);
    if (label) label.textContent = isDark ? 'Dark' : 'Light';
    localStorage.setItem(STORAGE_KEY, theme);
}
function toggleTheme() {
    const current = document.documentElement.getAttribute('data-theme') || 'dark';
    applyTheme(current === 'dark' ? 'light' : 'dark');
}
(function() { applyTheme(localStorage.getItem(STORAGE_KEY) || 'dark'); })();

// ── TABS ─────────────────────────────────────────────────────────
function switchTab(tab) {
    ['actifs','archives'].forEach(t => {
        document.getElementById('tab-' + t).classList.toggle('active', t === tab);
        document.getElementById('content-' + t).classList.toggle('active', t === tab);
    });
}

// ── DATA ─────────────────────────────────────────────────────────
const BASE_URL = '<?= $baseUrl ?>';
const DEVISE   = '<?= DEVISE ?>';
let imageFilter = 'all';
let currentSort = { col: null, dir: 'asc' };
let currentPage = 1;

// ── DISPO PREVIEW IN FORM ────────────────────────────────────────
function updateDispoPreviewBO(input) {
    const el  = document.getElementById('dispoPreviewBO');
    if (!el) return;
    const val = input.value;
    if (!val) {
        el.textContent = '📅 Available now';
        el.className = 'dispo-preview nodate';
    } else {
        const d = new Date(val);
        const formatted = d.toLocaleDateString('en-GB', {day:'2-digit', month:'2-digit', year:'numeric'});
        if (d > new Date()) {
            el.textContent = '⏳ Available from ' + formatted;
            el.className = 'dispo-preview future';
        } else {
            el.textContent = '✅ Available since ' + formatted;
            el.className = 'dispo-preview available';
        }
    }
}
// init on load if editing
document.addEventListener('DOMContentLoaded', () => {
    const dispoInput = document.querySelector('input[name="dateDisponibilite"]');
    if (dispoInput && dispoInput.value) updateDispoPreviewBO(dispoInput);
    renderPage(1);
});

// ── FILTER + SEARCH ───────────────────────────────────────────────
function filterTable() {
    const q       = document.getElementById('searchInput').value.toLowerCase().trim();
    const pMin    = parseFloat(document.getElementById('priceMin').value) || null;
    const pMax    = parseFloat(document.getElementById('priceMax').value) || null;
    const catSel  = document.getElementById('catFilterSelect').value.toLowerCase();
    const status  = document.getElementById('statusFilter').value;
    renderPage(1, q, pMin, pMax, catSel, status);
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
    document.getElementById('catFilterSelect').value = '';
    document.getElementById('statusFilter').value = '';
    setImageFilter('all');
    currentSort = { col: null, dir: 'asc' };
    document.querySelectorAll('thead th').forEach(th => th.classList.remove('sort-asc','sort-desc'));
    filterTable();
}

// ── SORT ──────────────────────────────────────────────────────────
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

// ── PAGINATION ────────────────────────────────────────────────────
function renderPage(page, q, pMin, pMax, catSel, status) {
    currentPage = page;
    if (q      === undefined) q      = document.getElementById('searchInput').value.toLowerCase().trim();
    if (pMin   === undefined) pMin   = parseFloat(document.getElementById('priceMin').value) || null;
    if (pMax   === undefined) pMax   = parseFloat(document.getElementById('priceMax').value) || null;
    if (catSel === undefined) catSel = document.getElementById('catFilterSelect').value.toLowerCase();
    if (status === undefined) status = document.getElementById('statusFilter').value;
    const perPage = parseInt(document.getElementById('perPageSelect').value);
    const allRows = Array.from(document.querySelectorAll('#tableBody tr'));

    const visible = allRows.filter(row => {
        const nom    = row.dataset.nom    || '';
        const desc   = row.dataset.desc   || '';
        const prix   = parseFloat(row.dataset.prix) || 0;
        const hasImg = row.dataset.hasimg;
        const cat    = row.dataset.cat    || '';
        const epingle = row.dataset.epingle === '1';
        const dispo   = row.dataset.dispo  || '';
        const dispoFuture = dispo && new Date(dispo) > new Date();

        const matchQ      = !q      || nom.includes(q) || desc.includes(q);
        const matchMin    = pMin === null || prix >= pMin;
        const matchMax    = pMax === null || prix <= pMax;
        const matchImg    = imageFilter === 'all' || (imageFilter === 'with' && hasImg === '1') || (imageFilter === 'without' && hasImg === '0');
        const matchCat    = !catSel || cat === catSel;
        const matchStatus = !status
            || (status === 'pinned'    && epingle)
            || (status === 'available' && (!dispo || !dispoFuture))
            || (status === 'future'    && dispoFuture);

        return matchQ && matchMin && matchMax && matchImg && matchCat && matchStatus;
    });

    const total = visible.length;
    const pages = perPage >= 999 ? 1 : Math.ceil(total / perPage);
    page = Math.min(page, pages || 1);
    const start = perPage >= 999 ? 0 : (page - 1) * perPage;
    const end   = perPage >= 999 ? total : Math.min(start + perPage, total);

    allRows.forEach(r => r.style.display = 'none');
    visible.forEach((r, i) => r.style.display = (i >= start && i < end) ? '' : 'none');

    const noun = total === 1 ? 'entry' : 'entries';
    document.getElementById('visibleCount').textContent = total + ' ' + noun;
    document.getElementById('paginationInfo').textContent =
        total === 0 ? 'No results' : `Showing ${start + 1}–${end} of ${total}`;

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

// ── BULK ──────────────────────────────────────────────────────────
function toggleSelectAll(cb) {
    document.querySelectorAll('.row-check').forEach(c => {
        if (c.closest('tr').style.display !== 'none') c.checked = cb.checked;
    });
    updateBulkBar();
}
function updateBulkBar() {
    const checked = Array.from(document.querySelectorAll('.row-check:checked'));
    document.getElementById('bulkCount').textContent = checked.length + ' product(s) selected';
    document.getElementById('bulkBar').classList.toggle('visible', checked.length > 0);
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
    return confirm('Delete ' + n + ' selected product(s)? This action is irreversible.');
}

// ── INLINE PRICE EDIT ────────────────────────────────────────────
function showInlineEdit(id) {
    document.getElementById('prixDisplay-' + id).style.display = 'none';
    document.getElementById('prixForm-' + id).style.display = 'flex';
    document.getElementById('prixInput-' + id).focus();
}
function hideInlineEdit(id) {
    document.getElementById('prixDisplay-' + id).style.display = '';
    document.getElementById('prixForm-' + id).style.display = 'none';
}

// ── UPLOAD PREVIEW ────────────────────────────────────────────────
const fileInput = document.getElementById('fileInputAdmin');
if (fileInput) {
    fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;
        if (file.size > 2 * 1024 * 1024) { alert('Image too large (max 2 MB).'); this.value = ''; return; }
        const reader = new FileReader();
        reader.onload = ev => {
            document.getElementById('uploadText').style.display = 'none';
            const wrap = document.getElementById('uploadPreview');
            wrap.style.display = 'flex';
            document.getElementById('previewBeforeUpload').src = ev.target.result;
        };
        reader.readAsDataURL(file);
    });
}

// ── AJAX PIN / ARCHIVE ────────────────────────────────────────────
function ajaxToggleEpingle(id) {
    const fd = new FormData();
    fd.append('action', 'epingle');
    fd.append('id', id);
    fetch('index.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(() => location.reload());
}
function ajaxToggleArchive(id) {
    if (!confirm('Toggle archive status for this product?')) return;
    const fd = new FormData();
    fd.append('action', 'archive');
    fd.append('id', id);
    fetch('index.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(() => location.reload());
}

// ── MODAL DELETE ──────────────────────────────────────────────────
function openDeleteModal(id, name) {
    document.getElementById('deleteModalText').textContent =
        'Are you sure you want to delete the product "' + name + '"? This action is irreversible.';
    document.getElementById('confirmDeleteBtn').href = 'index.php?delete=' + id;
    document.getElementById('deleteModal').classList.add('open');
}
function closeDeleteModal() { document.getElementById('deleteModal').classList.remove('open'); }
document.getElementById('deleteModal').addEventListener('click', e => {
    if (e.target.id === 'deleteModal') closeDeleteModal();
});

// ── PREVIEW MODAL ─────────────────────────────────────────────────
const produitsMap = {};
<?php foreach ($liste as $p): ?>
produitsMap[<?= $p['idProduit'] ?>] = {
    id:     <?= $p['idProduit'] ?>,
    nom:    <?= json_encode($p['nomProduit']) ?>,
    desc:   <?= json_encode($p['description'] ?? '') ?>,
    carac:  <?= json_encode($p['caracteristiques'] ?? '') ?>,
    prix:   <?= (float)$p['prix'] ?>,
    img:    <?= json_encode(!empty($p['image']) ? $baseUrl . '/Vue/public/produits/' . $p['image'] : null) ?>,
    marque: <?= json_encode($p['nomMarque'] ?? ($p['idMarque'] ? 'ID #' . $p['idMarque'] : null)) ?>,
    cat:    <?= json_encode($p['categorie'] ?? '') ?>,
    dispo:  <?= json_encode($p['dateDisponibilite'] ?? '') ?>,
    note:   <?= json_encode($p['noteInterne'] ?? '') ?>,
};
<?php endforeach; ?>

function openPreview(id) {
    const p = produitsMap[id];
    if (!p) return;
    document.getElementById('previewImgWrap').innerHTML = p.img
        ? `<img src="${p.img}" alt="" class="preview-modal-img">`
        : `<div class="preview-modal-img-empty">📦</div>`;
    document.getElementById('previewTitle').textContent = p.nom;
    document.getElementById('previewPrice').textContent = p.prix.toFixed(2) + ' ' + DEVISE;

    const marqueBadge = document.getElementById('previewMarqueBadge');
    marqueBadge.innerHTML = p.marque
        ? `<span class="preview-marque-badge">🏢 ${p.marque}</span>`
        : '';

    const catBadge = document.getElementById('previewCatBadge');
    catBadge.innerHTML = p.cat
        ? `<span class="cat-badge" style="font-size:12px;padding:3px 10px;">📂 ${p.cat}</span>`
        : '';

    const dispoBadge = document.getElementById('previewDispo');
    if (p.dispo) {
        const d = new Date(p.dispo);
        const isFuture = d > new Date();
        const formatted = d.toLocaleDateString('en-GB', {day:'2-digit', month:'long', year:'numeric'});
        dispoBadge.innerHTML = `<span class="preview-dispo ${isFuture ? 'future' : 'available'}">${isFuture ? '⏳ Available from ' : '✅ Available since '}${formatted}</span>`;
    } else {
        dispoBadge.innerHTML = '<span class="preview-dispo" style="background:var(--bg-card-alt);color:var(--text-dim);">📅 Available now</span>';
    }

    document.getElementById('previewDesc').textContent  = p.desc  || '—';
    document.getElementById('previewCarac').textContent = p.carac || '—';

    const noteWrap = document.getElementById('previewNoteWrap');
    noteWrap.innerHTML = p.note
        ? `<div class="preview-note"><span class="note-label">🔒 Internal note:</span> ${p.note}</div>`
        : '';

    document.getElementById('previewId').textContent = '#' + p.id;
    document.getElementById('previewFoBtn').href = BASE_URL + '/Vue/FrontOffice/produit/indexC.php?voir=' + p.id;
    document.getElementById('previewModal').classList.add('open');
}
function closePreview() { document.getElementById('previewModal').classList.remove('open'); }
function closePreviewOutside(e) { if (e.target.id === 'previewModal') closePreview(); }

// ── MISC ──────────────────────────────────────────────────────────
function validateForm() {
    const nom = document.querySelector('input[name="nom"]').value.trim();
    const prix = parseFloat(document.querySelector('input[name="prix"]').value);
    let valid = true;
    if (!nom) {
        alert('Product name is required.');
        valid = false;
    }
    if (isNaN(prix) || prix < 0) {
        alert('Price must be a valid number >= 0.');
        valid = false;
    }
    return valid;
}
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