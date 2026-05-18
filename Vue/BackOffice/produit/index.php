<?php
require_once __DIR__ . '/../layout/early-theme.php';
require_once '../../../Controleur/produitC.php';
require_once '../../../Modele/produit.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Keep the shared BackOffice header username meaningful on this page.
// The shared header reads $_SESSION['user']['nom'], $_SESSION['utilisateur']['nom'] or $_SESSION['nom'].
// Some login/session shapes use username/name/email instead, so normalize only when the name is missing.
if (empty($_SESSION['user']['nom']) && empty($_SESSION['utilisateur']['nom']) && empty($_SESSION['nom'])) {
    $candidateAdminName = $_SESSION['username']
        ?? $_SESSION['login']
        ?? $_SESSION['email']
        ?? $_SESSION['user']['username']
        ?? $_SESSION['user']['name']
        ?? $_SESSION['user']['prenom']
        ?? $_SESSION['utilisateur']['username']
        ?? $_SESSION['utilisateur']['name']
        ?? $_SESSION['utilisateur']['prenom']
        ?? null;

    if (empty($candidateAdminName) && (!empty($_SESSION['user']['prenom']) || !empty($_SESSION['user']['nom']))) {
        $candidateAdminName = trim(($_SESSION['user']['prenom'] ?? '') . ' ' . ($_SESSION['user']['nom'] ?? ''));
    }
    if (empty($candidateAdminName) && (!empty($_SESSION['utilisateur']['prenom']) || !empty($_SESSION['utilisateur']['nom']))) {
        $candidateAdminName = trim(($_SESSION['utilisateur']['prenom'] ?? '') . ' ' . ($_SESSION['utilisateur']['nom'] ?? ''));
    }

    $candidateAdminName = trim((string) $candidateAdminName);
    $_SESSION['nom'] = $candidateAdminName !== '' ? $candidateAdminName : 'Admin';
}

$produitC = new ProduitC();
$message = '';
$messageType = '';
$cre8SelfPath = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');
$cre8VuePos = strpos($cre8SelfPath, '/Vue/');
$baseUrl = $cre8VuePos !== false ? substr($cre8SelfPath, 0, $cre8VuePos) : '';
if (!function_exists('cre8_product_image_url')) {
    function cre8_product_image_url($filename) {
        global $baseUrl;
        $filename = trim((string) $filename);
        if ($filename === '') return '';
        return $baseUrl . '/Vue/public/produits/' . rawurlencode(basename($filename));
    }
}
define('DEVISE', '€');

// ── DELETE ───────────────────────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $produitC->supprimerProduit($_GET['delete']);
    header('Location: index.php?deleted=1');
    exit;
}
// ── AJAX : toggle pin ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='epingle') {
    $produitC->toggleEpingle(intval($_POST['id']));
    echo json_encode(['ok'=>true]); exit;
}
// ── AJAX : toggle archive ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='archive') {
    $produitC->toggleArchive(intval($_POST['id']));
    echo json_encode(['ok'=>true]); exit;
}
// ── ADD ───────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='add') {
    $nom   = trim($_POST['nom']??'');
    $prix  = str_replace(',','.',$_POST['prix']??'');
    $errors = [];
    if (strlen($nom)<2) $errors[] = "Name required (min 2 chars).";
    if (!is_numeric($prix)||floatval($prix)<0) $errors[] = "Price must be >= 0.";
    if (preg_match('/<[^>]+>/',$nom)) $errors[] = "No HTML in product name.";
    if (empty($errors)) {
        $nomImage = $produitC->gererUploadImage($_FILES['image']??null);
        $produit  = new Produit(null,htmlspecialchars($nom, ENT_QUOTES, 'UTF-8'),trim($_POST['description']??''),
            trim($_POST['caracteristiques']??''),floatval($prix),1,$nomImage,
            trim($_POST['categorie']??''),0,0,0,
            !empty($_POST['dateDisponibilite'])?$_POST['dateDisponibilite']:null,
            trim($_POST['noteInterne']??''));
        $produitC->ajouterProduit($produit);
        header('Location: index.php?added=1'); exit;
    } else {
        $message = implode(' | ',$errors); $messageType = 'error';
    }
}
// ── UPDATE ────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='update') {
    $nom  = trim($_POST['nom']??'');
    $prix = str_replace(',','.',$_POST['prix']??'');
    $errors = [];
    if (strlen($nom)<2) $errors[] = "Name required (min 2 chars).";
    if (!is_numeric($prix)||floatval($prix)<0) $errors[] = "Price must be >= 0.";
    if (empty($errors)) {
        $ancien   = $produitC->recupererProduit(intval($_POST['id']));
        $nomImage = $produitC->gererUploadImage($_FILES['image']??null,$ancien['image']??null);
        $produit  = new Produit(null,htmlspecialchars($nom, ENT_QUOTES, 'UTF-8'),trim($_POST['description']??''),
            trim($_POST['caracteristiques']??''),floatval($prix),null,$nomImage,
            trim($_POST['categorie']??''),intval($_POST['estArchive']??0),intval($_POST['estEpingle']??0),
            0,!empty($_POST['dateDisponibilite'])?$_POST['dateDisponibilite']:null,
            trim($_POST['noteInterne']??''));
        $produitC->modifierProduit($produit,intval($_POST['id']));
        header('Location: index.php?updated=1'); exit;
    } else {
        $message = implode(' | ',$errors); $messageType = 'error';
    }
}

if (isset($_GET['added']))   { $message="Product added successfully.";  $messageType="success"; }
if (isset($_GET['updated'])) { $message="Product updated successfully."; $messageType="info"; }
if (isset($_GET['deleted'])) { $message="Product deleted.";             $messageType="danger"; }

$liste            = $produitC->afficherProduits();
$listeArchives    = $produitC->afficherProduitsArchives();
$categoriesDispos = $produitC->getCategories();
$produitUpdate    = isset($_GET['edit']) ? $produitC->recupererProduit(intval($_GET['edit'])) : null;

$totalProduits  = count($liste);
$totalArchives  = count($listeArchives);
$allPrix        = array_column($liste,'prix');
$prixMoyen      = $totalProduits>0 ? array_sum($allPrix)/$totalProduits : 0;
$prixMax        = $totalProduits>0 ? max($allPrix) : 0;
$valeurCatalogue= array_sum($allPrix);
$sanImage       = count(array_filter($liste,fn($p)=>empty($p['image'])));
$nbEpingles     = count(array_filter($liste,fn($p)=>!empty($p['estEpingle'])));

// Budget by category for chart
$catBudgets = [];
foreach ($liste as $p) {
    $cat = $p['categorie'] ?: 'Uncategorized';
    if (!isset($catBudgets[$cat])) $catBudgets[$cat] = 0;
    $catBudgets[$cat]++;
}
arsort($catBudgets);
$top5cats = array_slice($catBudgets, 0, 5, true);

if (isset($_GET['export_csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="products_'.date('Y-m-d').'.csv"');
    $out = fopen('php://output','w');
    fputcsv($out,['ID','Name','Description','Tags','Category','Price (€)','Image','Pinned','Archived','Availability']);
    foreach ($liste as $p) {
        fputcsv($out,[$p['idProduit'],$p['nomProduit'],$p['description'],$p['caracteristiques'],
            $p['categorie']??'',$p['prix'],$p['image']??'',
            !empty($p['estEpingle'])?'Yes':'No',!empty($p['estArchive'])?'Yes':'No',$p['dateDisponibilite']??'']);
    }
    fclose($out); exit;
}

$categoriesDisponibles=['Beauty & Care','Fashion & Accessories','Tech & Gadgets','Food & Nutrition',
    'Sport & Fitness','Home & Decor','Travel','Wellness','Gaming','Kids'];
function produitAssetVersion($path) {
    return is_file($path) ? '?v=' . urlencode((string) filemtime($path)) : '';
}
if (!headers_sent()) header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php cre8_bo_early_theme_print_head_script(); ?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin — Product Management | Cre8Connect</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/backoffice.css<?= produitAssetVersion(__DIR__ . '/../css/backoffice.css') ?>">
<link rel="stylesheet" href="../layout/back-layout.css<?= produitAssetVersion(__DIR__ . '/../layout/back-layout.css') ?>">
<link rel="stylesheet" href="../utilisateur/assets/vendors/mdi/css/materialdesignicons.min.css<?= produitAssetVersion(__DIR__ . '/../utilisateur/assets/vendors/mdi/css/materialdesignicons.min.css') ?>">
<link rel="stylesheet" href="../css/new_style_backoffice.css<?= produitAssetVersion(__DIR__ . '/../css/new_style_backoffice.css') ?>">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
:root{
    --bg-base:#0f1117;--bg-surface:#161b27;--bg-card:#1c2235;--bg-card-alt:#212840;--bg-input:#111827;
    --border:rgba(255,255,255,0.07);--border-focus:rgba(139,92,246,0.5);
    --text-primary:#e2e8f0;--text-muted:#7c8ba1;--text-dim:#4a5568;
    --accent:#8b5cf6;--accent-soft:rgba(139,92,246,0.12);--accent-hover:#7c3aed;
    --success:#10b981;--success-soft:rgba(16,185,129,0.12);
    --danger:#ef4444;--danger-soft:rgba(239,68,68,0.10);
    --warning:#f59e0b;--warning-soft:rgba(245,158,11,0.12);
    --info:#3b82f6;--info-soft:rgba(59,130,246,0.12);
    --radius-sm:6px;--radius-md:10px;--radius-lg:14px;--sidebar-w:240px;
}

/* ===== ADDED FEATURE: LIGHT/DARK MODE CSS VARIABLES ===== */
body.light-mode {
    --bg-base:#f0f2f8;
    --bg-surface:#ffffff;
    --bg-card:#ffffff;
    --bg-card-alt:#f4f6fb;
    --bg-input:#f9fafb;
    --border:rgba(0,0,0,0.09);
    --border-focus:rgba(139,92,246,0.4);
    --text-primary:#12151f;
    --text-muted:#4b5280;
    --text-dim:#8892b0;
    --accent:#7c3aed;
    --accent-soft:rgba(124,58,237,0.10);
    --accent-hover:#6d28d9;
    --success:#059669;
    --success-soft:rgba(5,150,105,0.10);
    --danger:#dc2626;
    --danger-soft:rgba(220,38,38,0.08);
    --warning:#d97706;
    --warning-soft:rgba(217,119,6,0.10);
    --info:#2563eb;
    --info-soft:rgba(37,99,235,0.10);
}
/* ===== END ADDED FEATURE ===== */

body.cre8-admin-layout{font-family:'Inter',system-ui,sans-serif;background:var(--bg-base);color:var(--text-primary);transition:background .25s,color .25s;}
.produit-admin{background:var(--bg-base);color:var(--text-primary);width:100%;padding:28px 28px 60px;}
.btn-add{display:flex;align-items:center;gap:7px;background:var(--accent);color:#fff;border:none;padding:7px 14px;border-radius:var(--radius-sm);font-size:13px;font-weight:500;cursor:pointer;text-decoration:none;font-family:inherit;}
.btn-add:hover{background:var(--accent-hover);}
.btn-export{display:flex;align-items:center;gap:6px;background:var(--success-soft);color:var(--success);border:1px solid rgba(16,185,129,.2);padding:7px 12px;border-radius:var(--radius-sm);font-size:12px;font-weight:500;cursor:pointer;text-decoration:none;font-family:inherit;}
.search-wrap{position:relative;}
.search-input{background:var(--bg-input);border:1px solid var(--border);border-radius:var(--radius-sm);padding:7px 12px 7px 34px;color:var(--text-primary);font-size:13px;width:220px;outline:none;transition:border-color .2s,background .25s,color .25s;font-family:inherit;}
.search-input:focus{border-color:var(--border-focus);}
.search-icon{position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-dim);pointer-events:none;width:15px;height:15px;}
.page-title{font-size:20px;font-weight:600;}
.page-subtitle{font-size:13px;color:var(--text-muted);margin-top:3px;}
/* TOAST */
#toastContainer{position:fixed;top:68px;right:22px;z-index:9999;display:flex;flex-direction:column;gap:8px;pointer-events:none;}
.toast{display:flex;align-items:center;gap:10px;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-md);padding:12px 16px;font-size:13px;min-width:240px;max-width:340px;box-shadow:0 8px 24px rgba(0,0,0,.4);pointer-events:all;animation:toastIn .3s ease;}
.toast.hide{opacity:0;transform:translateX(20px);transition:opacity .4s,transform .4s;}
@keyframes toastIn{from{opacity:0;transform:translateX(20px);}to{opacity:1;transform:translateX(0);}}
.toast-success{border-color:rgba(16,185,129,.3);}
.toast-danger{border-color:rgba(239,68,68,.3);}
.toast-info{border-color:rgba(59,130,246,.3);}
.toast-close{margin-left:auto;background:none;border:none;color:var(--text-dim);cursor:pointer;font-size:14px;}
/* ALERT */
.alert{display:flex;align-items:center;gap:10px;padding:11px 16px;border-radius:var(--radius-md);font-size:13px;margin-bottom:20px;font-weight:450;}
.alert-success{background:var(--success-soft);color:var(--success);border:1px solid rgba(16,185,129,.2);}
.alert-info{background:var(--info-soft);color:var(--info);border:1px solid rgba(59,130,246,.2);}
.alert-danger{background:var(--danger-soft);color:var(--danger);border:1px solid rgba(239,68,68,.2);}
.alert-error{background:var(--danger-soft);color:var(--danger);border:1px solid rgba(239,68,68,.2);}
/* KPI */
.kpi-strip{display:grid;grid-template-columns:repeat(7,1fr);gap:12px;margin-bottom:24px;}
.kpi-card{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);padding:14px 16px;position:relative;overflow:hidden;transition:background .25s,border-color .25s;}
.kpi-card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;}
.kpi-accent::before{background:var(--accent);}
.kpi-success::before{background:var(--success);}
.kpi-warning::before{background:var(--warning);}
.kpi-danger::before{background:var(--danger);}
.kpi-info::before{background:var(--info);}
.kpi-purple::before{background:#a855f7;}
.kpi-archive::before{background:#64748b;}
.kpi-label{font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);}
.kpi-value{font-size:22px;font-weight:600;color:var(--text-primary);line-height:1.1;margin-top:4px;}
.kpi-sub{font-size:11px;color:var(--text-muted);margin-top:2px;}
/* CHARTS */
.charts-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px;}
.chart-card{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);padding:20px;transition:background .25s,border-color .25s;}
.chart-card-title{font-size:13px;font-weight:600;color:var(--text-muted);margin-bottom:14px;}
.chart-wrap{position:relative;height:200px;}
/* TABS */
.tab-bar{display:flex;gap:4px;margin-bottom:16px;border-bottom:1px solid var(--border);}
.tab-btn{background:none;border:none;padding:10px 18px;font-size:13px;font-weight:500;color:var(--text-muted);cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-1px;font-family:inherit;}
.tab-btn.active{color:var(--accent);border-bottom-color:var(--accent);}
.tab-pill{background:var(--accent-soft);color:var(--accent);border-radius:20px;padding:1px 8px;font-size:11px;font-weight:700;}
.tab-pill.archive{background:rgba(100,116,139,0.15);color:#64748b;}
.tab-content{display:none;}
.tab-content.active{display:block;}
/* FILTER BAR */
.filter-bar{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);padding:14px 18px;margin-bottom:16px;display:flex;align-items:flex-end;gap:16px;flex-wrap:wrap;transition:background .25s,border-color .25s;}
.filter-group{display:flex;flex-direction:column;gap:5px;}
.filter-label{font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-dim);}
.filter-select{background:var(--bg-input);border:1px solid var(--border);border-radius:var(--radius-sm);padding:6px 10px;color:var(--text-primary);font-size:12px;outline:none;cursor:pointer;font-family:inherit;transition:background .25s,color .25s,border-color .25s;}
.price-range-wrap{display:flex;align-items:center;gap:8px;}
.price-input{background:var(--bg-input);border:1px solid var(--border);border-radius:var(--radius-sm);padding:6px 8px;color:var(--text-primary);font-size:12px;width:80px;outline:none;font-family:inherit;transition:background .25s,color .25s,border-color .25s;}
.price-input:focus{border-color:var(--border-focus);}
.price-input.is-invalid{border-color:var(--danger);}
.price-sep{color:var(--text-dim);font-size:12px;}
.btn-reset-filter{background:transparent;color:var(--text-muted);border:1px solid var(--border);border-radius:var(--radius-sm);padding:6px 10px;font-size:12px;cursor:pointer;font-family:inherit;}
/* TABLE */
.table-panel{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;transition:background .25s,border-color .25s;}
.table-panel-header{display:flex;align-items:center;padding:14px 20px;border-bottom:1px solid var(--border);gap:12px;flex-wrap:wrap;}
.table-panel-title{font-size:14px;font-weight:600;flex:1;}
.count-badge{font-size:11px;font-weight:600;background:var(--accent-soft);color:var(--accent);padding:2px 9px;border-radius:20px;}
table{width:100%;border-collapse:collapse;}
thead th{background:var(--bg-card-alt);padding:10px 16px;text-align:left;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);border-bottom:1px solid var(--border);cursor:pointer;user-select:none;}
thead th.no-sort{cursor:default;}
thead th.sort-asc::after{content:' ↑';color:var(--accent);}
thead th.sort-desc::after{content:' ↓';color:var(--accent);}
tbody tr{border-bottom:1px solid var(--border);transition:background .12s;}
tbody tr:hover{background:rgba(139,92,246,.04);}
tbody td{padding:11px 16px;font-size:13px;vertical-align:middle;}
.td-id{color:var(--text-dim);font-size:12px;font-family:monospace;}
.td-name{font-weight:500;}
.td-desc{color:var(--text-muted);max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.prix-badge{display:inline-flex;background:var(--success-soft);color:var(--success);padding:3px 9px;border-radius:20px;font-size:12px;font-weight:600;}
.cat-badge{display:inline-flex;background:var(--accent-soft);color:var(--accent);padding:2px 8px;border-radius:20px;font-size:11px;font-weight:500;}
.action-group{display:flex;align-items:center;gap:4px;flex-wrap:nowrap;}
.btn-action{display:inline-flex;align-items:center;gap:4px;padding:4px 8px;border-radius:var(--radius-sm);font-size:11px;font-weight:500;cursor:pointer;text-decoration:none;border:1px solid transparent;transition:.15s;white-space:nowrap;font-family:inherit;}
.btn-view{background:rgba(139,92,246,.1);color:var(--accent);border-color:rgba(139,92,246,.2);}
.btn-edit-a{background:var(--warning-soft);color:var(--warning);border-color:rgba(245,158,11,.2);}
.btn-delete{background:var(--danger-soft);color:var(--danger);border-color:rgba(239,68,68,.2);}
.btn-pin{background:var(--warning-soft);color:var(--warning);border-color:rgba(245,158,11,.2);}
.btn-archive{background:rgba(100,116,139,0.12);color:#64748b;border-color:rgba(100,116,139,.2);}
.td-img{position:relative;width:46px;}
.td-img-thumb{position:relative;display:inline-block;}
.td-img-thumb img{width:42px;height:42px;object-fit:cover;border-radius:var(--radius-sm);border:1px solid var(--border);cursor:pointer;display:block;}
.td-img-empty{width:42px;height:42px;background:var(--bg-card-alt);border:1px dashed var(--border);border-radius:var(--radius-sm);display:flex;align-items:center;justify-content:center;font-size:16px;}
.td-img-status{position:absolute;bottom:-4px;right:-4px;width:14px;height:14px;border-radius:50%;border:2px solid var(--bg-card);display:flex;align-items:center;justify-content:center;font-size:7px;font-weight:700;}
.td-img-status.ok{background:var(--success);}
.td-img-status.miss{background:var(--danger);}
.status-badge{display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600;}
.status-badge.pinned{background:var(--warning-soft);color:var(--warning);}
.status-badge.available{background:var(--success-soft);color:var(--success);}
.status-badge.future{background:var(--warning-soft);color:var(--warning);}
/* ARCHIVED */
.btn-restore{background:var(--success-soft);color:var(--success);border:1px solid rgba(16,185,129,.2);font-family:inherit;}
/* PAGINATION */
.pagination{display:flex;align-items:center;justify-content:space-between;padding:12px 20px;border-top:1px solid var(--border);}
.pagination-info{font-size:12px;color:var(--text-muted);}
.pagination-buttons{display:flex;gap:4px;}
.page-btn{background:var(--bg-card-alt);border:1px solid var(--border);border-radius:var(--radius-sm);padding:5px 10px;font-size:12px;color:var(--text-muted);cursor:pointer;min-width:30px;text-align:center;font-family:inherit;transition:background .2s,color .2s,border-color .2s;}
.page-btn.active{background:var(--accent);border-color:var(--accent);color:#fff;}
.page-btn:disabled{opacity:.4;cursor:not-allowed;}
/* FORM */
.form-panel{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);margin-bottom:24px;overflow:hidden;transition:background .25s,border-color .25s;}
.form-panel-header{display:flex;align-items:center;gap:10px;padding:14px 20px;border-bottom:1px solid var(--border);}
.form-panel-title{font-size:14px;font-weight:600;}
.form-body{padding:20px;}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.form-col-full{grid-column:1/-1;}
.form-group{display:flex;flex-direction:column;gap:4px;}
.form-label{font-size:12px;font-weight:500;color:var(--text-muted);}
.form-control{background:var(--bg-input);border:1px solid var(--border);border-radius:var(--radius-sm);padding:9px 12px;color:var(--text-primary);font-size:13px;font-family:inherit;transition:border-color .2s,background .25s,color .25s;width:100%;outline:none;}
.form-control:focus{border-color:var(--border-focus);}
.form-control.is-invalid{border-color:var(--danger) !important;}
.form-control.is-valid{border-color:var(--success);}
textarea.form-control{resize:vertical;min-height:80px;}
.field-error-msg{font-size:11px;color:var(--danger);display:none;margin-top:2px;}
.field-error-msg.visible{display:flex;align-items:center;gap:4px;}
.field-error-msg::before{content:'⚠';}
.char-counter{font-size:10px;color:var(--text-dim);text-align:right;margin-top:1px;}
.char-counter.warn{color:var(--warning);}
/* NOTE INTERNE */
.note-interne-wrap{border:1px dashed rgba(245,158,11,0.3);border-radius:var(--radius-sm);background:rgba(245,158,11,0.04);padding:10px 12px;}
.note-interne-header{font-size:11px;font-weight:600;color:var(--warning);margin-bottom:6px;}
textarea.note-interne-ctrl{background:transparent;border:none;outline:none;width:100%;font-size:13px;font-family:inherit;color:var(--text-primary);resize:vertical;min-height:60px;}
/* TOGGLE */
.toggle-row{display:flex;gap:12px;flex-wrap:wrap;}
.toggle-item{display:flex;align-items:center;gap:10px;padding:9px 12px;background:var(--bg-card-alt);border-radius:var(--radius-sm);border:1px solid var(--border);flex:1;min-width:150px;}
.toggle-switch{position:relative;width:36px;height:20px;flex-shrink:0;}
.toggle-switch input{opacity:0;width:0;height:0;}
.toggle-slider{position:absolute;cursor:pointer;top:0;left:0;right:0;bottom:0;background:var(--border);border-radius:20px;transition:.2s;}
.toggle-slider::before{content:'';position:absolute;width:14px;height:14px;border-radius:50%;background:white;left:3px;bottom:3px;transition:.2s;}
.toggle-switch input:checked+.toggle-slider{background:var(--accent);}
.toggle-switch input:checked+.toggle-slider::before{transform:translateX(16px);}
.toggle-switch.pin input:checked+.toggle-slider{background:var(--warning);}
.toggle-switch.archive input:checked+.toggle-slider{background:#64748b;}
/* UPLOAD */
.upload-admin{border:1.5px dashed rgba(139,92,246,0.3);border-radius:var(--radius-sm);background:var(--bg-input);padding:16px;cursor:pointer;transition:border-color .2s;position:relative;text-align:center;}
.upload-admin:hover{border-color:var(--accent);}
.upload-admin input[type="file"]{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;}
.upload-admin-text{font-size:12px;color:var(--text-muted);}
.upload-admin-text strong{color:var(--accent);}
.upload-preview-img{width:72px;height:72px;object-fit:cover;border-radius:var(--radius-sm);border:1px solid var(--border);margin-top:8px;display:none;}
/* PREVIEW MODAL */
.preview-modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:300;align-items:center;justify-content:center;}
.preview-modal-overlay.open{display:flex;}
.preview-modal-box{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);width:640px;max-width:95vw;overflow:hidden;animation:popIn .22s ease;}
@keyframes popIn{from{opacity:0;transform:scale(.93);}to{opacity:1;transform:scale(1);}}
.preview-modal-img{width:100%;height:240px;object-fit:cover;display:block;}
.preview-modal-img-empty{width:100%;height:200px;background:var(--bg-card-alt);display:flex;align-items:center;justify-content:center;font-size:56px;}
.preview-modal-body{padding:22px 26px 26px;}
.preview-modal-title{font-size:20px;font-weight:600;margin-bottom:8px;}
.preview-section-label{font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:var(--text-dim);margin-bottom:5px;margin-top:12px;}
.preview-text{font-size:13px;color:var(--text-muted);line-height:1.65;}
.preview-close-btn{display:inline-flex;align-items:center;gap:5px;background:var(--danger-soft);color:var(--danger);border:1px solid rgba(239,68,68,.2);border-radius:var(--radius-sm);padding:6px 14px;font-size:12px;cursor:pointer;margin-left:auto;font-family:inherit;}
/* DELETE MODAL */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:400;align-items:center;justify-content:center;padding:20px;}
.modal-overlay.open{display:flex;}
.modal-box{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);padding:28px;width:380px;max-width:100%;animation:popIn .22s ease;}
.modal-title{font-size:16px;font-weight:600;margin-bottom:8px;}
.modal-text{font-size:13px;color:var(--text-muted);margin-bottom:22px;}
.modal-actions{display:flex;gap:10px;justify-content:flex-end;}
.btn-modal-cancel{background:var(--bg-card-alt);color:var(--text-muted);border:1px solid var(--border);border-radius:var(--radius-sm);padding:8px 16px;font-size:13px;cursor:pointer;font-family:inherit;}
.btn-modal-confirm{background:var(--danger);color:#fff;border:none;border-radius:var(--radius-sm);padding:8px 18px;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;}
/* FORM ACTIONS */
.form-actions-row{display:flex;gap:10px;margin-top:14px;}
.btn-primary{display:inline-flex;align-items:center;gap:6px;background:var(--accent);color:#fff;border:none;border-radius:var(--radius-sm);padding:9px 18px;font-size:13px;font-weight:500;cursor:pointer;font-family:inherit;}
.btn-primary:hover{background:var(--accent-hover);}
.btn-primary:disabled{opacity:.5;cursor:not-allowed;}
.btn-ghost{background:transparent;color:var(--text-muted);border:1px solid var(--border);border-radius:var(--radius-sm);padding:9px 16px;font-size:13px;cursor:pointer;text-decoration:none;font-family:inherit;}
/* EMPTY */
.empty-state{text-align:center;padding:52px 20px;color:var(--text-muted);}
.empty-icon{font-size:36px;margin-bottom:12px;}
/* PRINT AREA */
@media print{
    .sidebar,.topbar,.btn-add,.btn-export,.btn-action,.filter-bar,.tab-bar,.form-panel,.pagination,#toastContainer,#deleteModal,.modal-overlay{display:none!important;}
    .kpi-strip{grid-template-columns:repeat(4,1fr);}
}

@media(max-width:1300px){.kpi-strip{grid-template-columns:repeat(4,1fr);}}
@media(max-width:1000px){.kpi-strip{grid-template-columns:repeat(3,1fr);}}
@media(max-width:900px){.kpi-strip{grid-template-columns:repeat(2,1fr);}.form-grid{grid-template-columns:1fr;}}

/* ===== ADDED FEATURE: LANGUAGE SWITCHER STYLES ===== */
.translation-toolbar{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin:0 0 16px;}
.page-heading{display:flex;flex-direction:column;gap:3px;min-width:0;}
.lang-switcher,.lang-switcher.bo-lang-switch{display:inline-flex;align-items:center;gap:0.25rem;padding:0.25rem;border:1px solid rgba(124,92,255,.35);border-radius:999px;background:rgba(124,92,255,.08);flex:0 0 auto;}
.lang-btn{border:0;border-radius:999px;padding:0.4rem 0.7rem;background:transparent;color:inherit;font-family:inherit;font-size:0.76rem;font-weight:800;cursor:pointer;transition:background .16s ease,color .16s ease;}
.lang-btn:hover{color:var(--accent);}
.lang-btn.active{background:#8b5cf6;color:#fff;}
.page-subtitle{font-size:0.9rem;color:var(--text-muted);margin-top:4px;}
@media(max-width:760px){.translation-toolbar{align-items:flex-start;}.lang-switcher{margin-left:auto;}}
/* ===== END ADDED FEATURE ===== */

/* ===== ADDED FEATURE: THEME TOGGLE BUTTON STYLES ===== */
.theme-toggle-btn{display:inline-flex;align-items:center;gap:6px;background:var(--bg-card-alt);border:1px solid var(--border);border-radius:var(--radius-sm);padding:5px 11px;font-size:12px;font-weight:500;cursor:pointer;color:var(--text-muted);font-family:inherit;transition:all .2s;white-space:nowrap;}
.theme-toggle-btn:hover{border-color:var(--accent);color:var(--accent);}
/* ===== END ADDED FEATURE ===== */


/* Product header toolbar: keep language, search and actions in one clean responsive row. */
.bo-content-actions{
    display:flex !important;
    align-items:center !important;
    gap:10px !important;
    flex-wrap:wrap !important;
    margin-top:16px !important;
    max-width:100% !important;
}
.bo-content-actions .search-wrap{
    flex:1 1 260px !important;
    min-width:240px !important;
    max-width:360px !important;
}
.bo-content-actions .search-input{
    width:100% !important;
    height:38px !important;
}
.bo-content-actions .btn-export,
.bo-content-actions .btn-add{
    flex:0 0 auto !important;
    width:auto !important;
    min-width:0 !important;
    min-height:38px !important;
    justify-content:center !important;
    white-space:nowrap !important;
}
.bo-content-actions .btn-add{
    padding-left:16px !important;
    padding-right:16px !important;
}
@media(max-width:760px){
    .bo-content-actions{
        align-items:stretch !important;
    }
    .bo-content-actions .search-wrap,
    .bo-content-actions .btn-export,
    .bo-content-actions .btn-add{
        flex:1 1 100% !important;
        max-width:none !important;
    }
}

</style>
<link rel="stylesheet" href="../business-center-admin.css<?= produitAssetVersion(__DIR__ . '/../business-center-admin.css') ?>">
<link rel="stylesheet" href="../unified-table-admin.css<?= produitAssetVersion(__DIR__ . '/../unified-table-admin.css') ?>">
<link rel="icon" type="image/png" sizes="16x16" href="../../public/images/favicon-16.png">
<link rel="icon" type="image/png" sizes="32x32" href="../../public/images/favicon-32.png">
<link rel="shortcut icon" type="image/png" href="../../public/images/favicon-32.png">
<link rel="apple-touch-icon" sizes="180x180" href="../../public/images/apple-touch-icon.png">
</head>
<body class="cre8-admin-layout"><?php cre8_bo_early_theme_print_body_script(); ?>

<div id="toastContainer"></div>
<div class="container-scroller cre8-admin-page">
<?php
$backActive = 'products';
require_once __DIR__ . '/../layout/sidebar.php';
?>
<div class="container-fluid page-body-wrapper cre8-admin-main">
<?php require_once __DIR__ . '/../layout/header.php'; ?>
    <div class="main-panel">
    <div class="content-wrapper business-center-shell">
        <div class="produit-admin">


        <section class="bc-page-head">
            <div>
                <p class="bc-kicker">Business Center</p>
                <h1>Product administration</h1>
                <p>Supervise, add, edit and analyze all platform products.</p>
            </div>
            <div class="bc-page-actions">
                <button class="btn-export" onclick="window.print()"><i class="mdi mdi-printer"></i><span data-i18n="btnPrint">Print / PDF</span></button>
                <a href="?export_csv=1" class="btn-export"><i class="mdi mdi-download"></i> CSV</a>
                <a href="?add=1" class="btn-add"><i class="mdi mdi-plus"></i><span data-i18n="btnNewProduct">New Product</span></a>
            </div>
        </section>

        <nav class="bc-entity-tabs" aria-label="Business Center sections">
            <a class="bc-entity-tab" href="../campagne/index.php">
                <span class="bc-tab-icon"><i class="mdi mdi-bullhorn-outline"></i></span>
                <span><strong>Campaigns</strong><small>Campaign planning and moderation</small></span>
            </a>
            <a class="bc-entity-tab is-active" href="../produit/index.php" aria-current="page">
                <span class="bc-tab-icon"><i class="mdi mdi-package-variant-closed"></i></span>
                <span><strong>Products</strong><small>Catalog, images and product data</small></span>
            </a>
            <a class="bc-entity-tab" href="../contrat/index.php">
                <span class="bc-tab-icon"><i class="mdi mdi-file-document-edit-outline"></i></span>
                <span><strong>Contracts</strong><small>Contract status and value tracking</small></span>
            </a>
        </nav>
    <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>" id="alertMsg"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <!-- KPI -->
    <div class="kpi-strip">
        <div class="kpi-card kpi-accent"><div class="kpi-label" data-i18n="kpiTotalActive">Total Active</div><div class="kpi-value"><?= $totalProduits ?></div><div class="kpi-sub" data-i18n="kpiProductsSub">products</div></div>
        <div class="kpi-card kpi-info"><div class="kpi-label" data-i18n="kpiAvgPrice">Avg. Price</div><div class="kpi-value"><?= number_format($prixMoyen,2) ?></div><div class="kpi-sub"><?= DEVISE ?></div></div>
        <div class="kpi-card kpi-success"><div class="kpi-label" data-i18n="kpiCatalogValue">Catalog Value</div><div class="kpi-value"><?= number_format($valeurCatalogue,0) ?></div><div class="kpi-sub"><?= DEVISE ?></div></div>
        <div class="kpi-card kpi-warning"><div class="kpi-label" data-i18n="kpiHighestPrice">Highest Price</div><div class="kpi-value"><?= number_format($prixMax,2) ?></div><div class="kpi-sub"><?= DEVISE ?></div></div>
        <div class="kpi-card kpi-purple"><div class="kpi-label" data-i18n="kpiMissingImage">Missing Image</div><div class="kpi-value"><?= $sanImage ?></div><div class="kpi-sub" data-i18n="kpiNeedImage">need image</div></div>
        <div class="kpi-card kpi-warning" style="--warning:#f59e0b"><div class="kpi-label" data-i18n="kpiPinned">Pinned</div><div class="kpi-value"><?= $nbEpingles ?></div><div class="kpi-sub" data-i18n="kpiFeatured">featured</div></div>
        <div class="kpi-card kpi-archive"><div class="kpi-label" data-i18n="kpiArchived">Archived</div><div class="kpi-value"><?= $totalArchives ?></div><div class="kpi-sub" data-i18n="kpiHidden">hidden</div></div>
    </div>

    <!-- CHARTS -->
    <section class="bc-statistics-panel" data-bc-stats>
        <div class="bc-section-head">
            <div>
                <h2>Product statistics</h2>
                <p>Category distribution and price ranges.</p>
            </div>
            <button type="button" class="bc-secondary-btn" data-bc-stats-toggle data-label-hide="Hide statistics" data-label-show="Show statistics">Hide statistics</button>
        </div>
    <div class="charts-row bc-stats-body">
        <div class="chart-card">
            <div class="chart-card-title" data-i18n="chartCatTitle">📊 Products by Category (Top 5)</div>
            <div class="chart-wrap"><canvas id="chartCategories"></canvas></div>
        </div>
        <div class="chart-card">
            <div class="chart-card-title" data-i18n="chartPriceTitle">💰 Price Distribution</div>
            <div class="chart-wrap"><canvas id="chartPrices"></canvas></div>
        </div>
    </div>
    </section>

    <!-- FORM -->
    <?php if (isset($_GET['add']) || $produitUpdate): ?>
    <div class="form-panel" id="formPanel">
        <div class="form-panel-header">
            <span style="font-size:14px;font-weight:600;" data-i18n="<?= $produitUpdate ? 'formEditTitle' : 'formAddTitle' ?>"><?= $produitUpdate ? '✏️ Edit Product' : '➕ Add a Product' ?></span>
        </div>
        <div class="form-body">
            <form method="POST" action="index.php" enctype="multipart/form-data" id="produitFormBO" novalidate>
                <input type="hidden" name="action" value="<?= $produitUpdate ? 'update' : 'add' ?>">
                <input type="hidden" name="id" value="<?= $produitUpdate['idProduit'] ?? '' ?>">
                <?php if ($produitUpdate): ?>
                <input type="hidden" name="estEpingle" value="<?= (int)($produitUpdate['estEpingle']??0) ?>" id="epingleHiddenBO">
                <input type="hidden" name="estArchive" value="<?= (int)($produitUpdate['estArchive']??0) ?>" id="archiveHiddenBO">
                <?php endif; ?>

                <div class="form-grid">
                    <!-- NAME -->
                    <div class="form-group">
                        <label class="form-label"><span data-i18n="labelName">Product name</span> * <span id="ctrNom" class="char-counter">0/80</span></label>
                        <input type="text" name="nom" id="fNom" class="form-control" maxlength="80"
                               value="<?= htmlspecialchars($produitUpdate['nomProduit']??'', ENT_QUOTES, 'UTF-8') ?>"
                               placeholder="e.g. Premium Moisturizer">
                        <div class="field-error-msg" id="errNom" data-i18n="errName">Name required — min 2 characters, no HTML.</div>
                    </div>
                    <!-- PRICE -->
                    <div class="form-group">
                        <label class="form-label"><span data-i18n="labelPrice">Price</span> (<?= DEVISE ?>) *</label>
                        <input type="text" name="prix" id="fPrix" class="form-control"
                               value="<?= htmlspecialchars($produitUpdate['prix']??'', ENT_QUOTES, 'UTF-8') ?>" placeholder="0.00" autocomplete="off">
                        <div class="field-error-msg" id="errPrix" data-i18n="errPrice">Valid price required (e.g. 29.99, max 2 decimals).</div>
                    </div>
                    <!-- CATEGORY -->
                    <div class="form-group">
                        <label class="form-label" data-i18n="labelCategory">Category</label>
                        <select name="categorie" class="form-control">
                            <option value="" data-i18n="optSelectCategory">— Select —</option>
                            <?php foreach ($categoriesDisponibles as $cat): ?>
                            <option value="<?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>" <?= ($produitUpdate&&($produitUpdate['categorie']??'')===$cat)?'selected':'' ?>><?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- DATE DISPO -->
                    <div class="form-group">
                        <label class="form-label"><span data-i18n="labelAvailability">Availability date</span> <small style="color:var(--text-dim);font-weight:400" data-i18n="labelOptional">(optional — YYYY-MM-DD)</small></label>
                        <input type="text" name="dateDisponibilite" id="fDate" class="form-control"
                               value="<?= htmlspecialchars($produitUpdate['dateDisponibilite']??'', ENT_QUOTES, 'UTF-8') ?>" placeholder="YYYY-MM-DD">
                        <div class="field-error-msg" id="errDate" data-i18n="errDate">Invalid date. Use YYYY-MM-DD (e.g. 2025-12-31).</div>
                    </div>
                    <!-- DESCRIPTION -->
                    <div class="form-group form-col-full">
                        <label class="form-label"><span data-i18n="labelDescription">Description</span> <span id="ctrDesc" class="char-counter">0/500</span></label>
                        <textarea name="description" id="fDesc" class="form-control" maxlength="500"><?= htmlspecialchars($produitUpdate['description']??'', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                    <!-- TAGS -->
                    <div class="form-group form-col-full">
                        <label class="form-label"><span data-i18n="labelTags">Characteristics / Tags</span> <small style="color:var(--text-dim);font-weight:400" data-i18n="labelCommaSep">(comma-separated)</small> <span id="ctrTags" class="char-counter">0/300</span></label>
                        <input type="text" name="caracteristiques" id="fTags" class="form-control" maxlength="300"
                               value="<?= htmlspecialchars($produitUpdate['caracteristiques']??'', ENT_QUOTES, 'UTF-8') ?>"
                               placeholder="Bio, Premium, Vegan, Made in France…">
                        <div class="field-error-msg" id="errTags" data-i18n="errTags">Tags must not contain HTML.</div>
                    </div>
                    <!-- NOTE INTERNE -->
                    <div class="form-group form-col-full">
                        <label class="form-label"><span data-i18n="labelInternalNote">Internal note</span> 🔒 <span data-i18n="labelAdminOnly">Admin only</span></label>
                        <div class="note-interne-wrap">
                            <div class="note-interne-header" data-i18n="noteInterneBanner">🔒 Visible to admin team only</div>
                            <textarea name="noteInterne" class="note-interne-ctrl"><?= htmlspecialchars($produitUpdate['noteInterne']??'', ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                    </div>
                    <!-- TOGGLES (edit only) -->
                    <?php if ($produitUpdate): ?>
                    <div class="form-group form-col-full">
                        <div class="toggle-row">
                            <div class="toggle-item">
                                <div><div style="font-size:12px;font-weight:500;" data-i18n="togglePin">📌 Pin</div></div>
                                <label class="toggle-switch pin">
                                    <input type="checkbox" id="boToggleEpingle" <?= !empty($produitUpdate['estEpingle'])?'checked':'' ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            <div class="toggle-item">
                                <div><div style="font-size:12px;font-weight:500;" data-i18n="toggleArchive">🗄️ Archive</div></div>
                                <label class="toggle-switch archive">
                                    <input type="checkbox" id="boToggleArchive" <?= !empty($produitUpdate['estArchive'])?'checked':'' ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <!-- IMAGE -->
                    <div class="form-group form-col-full">
                        <label class="form-label" data-i18n="labelImage">Image</label>
                        <?php if ($produitUpdate && !empty($produitUpdate['image'])): ?>
                        <div style="display:flex;align-items:center;gap:10px;background:var(--accent-soft);border-radius:var(--radius-sm);padding:8px 12px;margin-bottom:8px;">
                            <img src="<?= htmlspecialchars(cre8_product_image_url($produitUpdate['image']), ENT_QUOTES, 'UTF-8') ?>" style="width:48px;height:48px;object-fit:cover;border-radius:6px;">
                            <span style="font-size:12px;color:var(--text-muted);" data-i18n="currentImageHint">Current image — upload to replace.</span>
                        </div>
                        <?php endif; ?>
                        <div class="upload-admin">
                            <input type="file" name="image" id="fileInputBO" accept="image/jpeg,image/png,image/webp">
                            <div class="upload-admin-text"><strong data-i18n="uploadClick">Click to upload</strong> <span data-i18n="uploadHint">(JPG, PNG, WEBP — 2MB max)</span></div>
                        </div>
                        <div class="field-error-msg" id="errImage" data-i18n="errImage">File too large or invalid format (JPG, PNG, WEBP only, max 2MB).</div>
                        <img id="imgPreviewBO" class="upload-preview-img" src="" alt="">
                    </div>
                </div>

                <div class="form-actions-row">
                    <button type="submit" class="btn-primary" id="btnSubmitBO">
                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <span data-i18n="<?= $produitUpdate ? 'btnSaveChanges' : 'btnAddProduct' ?>"><?= $produitUpdate ? 'Save changes' : 'Add product' ?></span>
                    </button>
                    <a href="index.php" class="btn-ghost" data-i18n="btnCancel">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- FILTER BAR -->
    <div class="filter-bar">
        <div class="filter-group">
            <div class="filter-label" data-i18n="filterSearch">Search</div>
            <input type="text" class="search-input" id="searchInput" data-i18n-placeholder="searchPlaceholder" placeholder="Search products...">
        </div>
        <div class="filter-group">
            <div class="filter-label" data-i18n="filterSortBy">Sort by</div>
            <select class="filter-select" id="sortColSelect" onchange="applySortSelect()">
                <option value="" data-i18n="filterDefault">Default</option>
                <option value="nom-asc" data-i18n="filterNameAZ">Name A→Z</option>
                <option value="nom-desc" data-i18n="filterNameZA">Name Z→A</option>
                <option value="prix-asc" data-i18n="filterPriceAsc">Price ↑</option>
                <option value="prix-desc" data-i18n="filterPriceDesc">Price ↓</option>
            </select>
        </div>
        <div class="filter-group">
            <div class="filter-label" data-i18n="filterPriceRange">Price (<?= DEVISE ?>)</div>
            <div class="price-range-wrap">
                <input type="text" class="price-input" id="priceMin" data-i18n-placeholder="filterMin" placeholder="Min">
                <span class="price-sep">–</span>
                <input type="text" class="price-input" id="priceMax" data-i18n-placeholder="filterMax" placeholder="Max">
            </div>
        </div>
        <div class="filter-group">
            <div class="filter-label" data-i18n="filterCategory">Category</div>
            <select class="filter-select" id="catFilterSelect" onchange="filterTable()">
                <option value="" data-i18n="filterAll">All</option>
                <?php foreach ($categoriesDispos as $cat): ?>
                <option value="<?= htmlspecialchars(strtolower($cat), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn-reset-filter" onclick="resetFilters()" data-i18n="filterReset">Reset</button>
    </div>

    <!-- TABS -->
    <div class="tab-bar">
        <button class="tab-btn active" id="tab-actifs" onclick="switchTab('actifs',this)">
            <span data-i18n="tabActive">Active</span> <span class="tab-pill"><?= $totalProduits ?></span>
        </button>
        <button class="tab-btn" id="tab-archives-btn" onclick="switchTab('archives',this)">
            <span data-i18n="tabArchived">Archived</span> <span class="tab-pill archive"><?= $totalArchives ?></span>
        </button>
    </div>

    <!-- ACTIVE TAB -->
    <div class="tab-content active" id="content-actifs">
        <div id="bcResultsRegion" class="bc-results-region">
        <div class="table-panel bc-table-card">
            <div class="table-panel-header">
                <span class="table-panel-title" data-i18n="tablePanelTitle">Product list</span>
                <span class="count-badge" id="visibleCount"><?= $totalProduits ?></span>
            </div>
            <?php if (empty($liste)): ?>
            <div class="empty-state"><div class="empty-icon">📦</div><p data-i18n="noProducts">No products found.</p></div>
            <?php else: ?>
            <table id="produitsTable" class="bc-table">
                <thead>
                    <tr>
                        <th class="no-sort" style="width:46px" data-i18n="colImg">Img</th>
                        <th onclick="sortTable('id')" data-col="id" data-i18n="colId">#ID</th>
                        <th onclick="sortTable('nom')" data-col="nom" data-i18n="colName">Name</th>
                        <th class="no-sort" data-i18n="colCategory">Category</th>
                        <th class="no-sort" data-i18n="colDescription">Description</th>
                        <th onclick="sortTable('prix')" data-col="prix" data-i18n="colPrice">Price</th>
                        <th class="no-sort" data-i18n="colStatus">Status</th>
                        <th class="no-sort" data-i18n="colActions">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                <?php foreach ($liste as $p):
                    $isPinned = !empty($p['estEpingle']);
                    $dispo    = $p['dateDisponibilite']??null;
                    $dispoFut = $dispo && strtotime($dispo)>time();
                ?>
                <tr data-id="<?= $p['idProduit'] ?>"
                    data-nom="<?= htmlspecialchars(strtolower($p['nomProduit']), ENT_QUOTES, 'UTF-8') ?>"
                    data-prix="<?= (float)$p['prix'] ?>"
                    data-cat="<?= htmlspecialchars(strtolower($p['categorie']??''), ENT_QUOTES, 'UTF-8') ?>"
                    data-epingle="<?= (int)$isPinned ?>">
                    <td class="td-img"><div class="td-img-thumb">
                        <?php if (!empty($p['image'])): ?>
                            <img src="<?= htmlspecialchars(cre8_product_image_url($p['image']), ENT_QUOTES, 'UTF-8') ?>" alt="" onclick="openPreview(<?= $p['idProduit'] ?>)">
                            <div class="td-img-status ok">✓</div>
                        <?php else: ?>
                            <div class="td-img-empty">📦</div>
                            <div class="td-img-status miss">✕</div>
                        <?php endif; ?>
                    </div></td>
                    <td class="td-id">#<?= $p['idProduit'] ?></td>
                    <td class="td-name"><?= htmlspecialchars($p['nomProduit'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?php if(!empty($p['categorie'])): ?><span class="cat-badge"><?= htmlspecialchars($p['categorie'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?></td>
                    <td class="td-desc" title="<?= htmlspecialchars($p['description']??'', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($p['description']??'', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="prix-badge"><?= number_format((float)$p['prix'],2) ?> <?= DEVISE ?></span></td>
                    <td>
                        <?php if($isPinned): ?><span class="status-badge pinned" data-i18n="statusPinned">📌 Pinned</span><?php endif; ?>
                        <?php if($dispo): ?><span class="status-badge <?= $dispoFut?'future':'available' ?>" data-i18n="<?= $dispoFut?'statusFuture':'statusLive' ?>"><?= $dispoFut?'⏳ Future':'✅ Live' ?></span><?php endif; ?>
                    </td>
                    <td>
                        <div class="action-group">
                            <button class="btn-action btn-view" onclick="openPreview(<?= $p['idProduit'] ?>)" data-i18n="btnView">👁 View</button>
                            <a href="?edit=<?= $p['idProduit'] ?>" class="btn-action btn-edit-a" data-i18n="btnEdit">✏️ Edit</a>
                            <button class="btn-action btn-pin" onclick="ajaxToggle('epingle',<?= $p['idProduit'] ?>,'<?= $isPinned?'Unpin':'Pin' ?>')" data-i18n="<?= $isPinned?'btnUnpin':'btnPin' ?>"><?= $isPinned?'📌 Unpin':'📌 Pin' ?></button>
                            <button class="btn-action btn-archive" onclick="ajaxToggle('archive',<?= $p['idProduit'] ?>,'Archive')" data-i18n="btnArchive">🗄️</button>
                            <button class="btn-action btn-delete" onclick="openDeleteModal(<?= $p['idProduit'] ?>,'<?= htmlspecialchars(addslashes($p['nomProduit']), ENT_QUOTES, 'UTF-8') ?>')" data-i18n="btnDelete">🗑</button>
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

    <!-- ARCHIVED TAB -->
    <div class="tab-content" id="content-archives">
        <div class="table-panel">
            <div class="table-panel-header">
                <span class="table-panel-title" data-i18n="archivedTitle">Archived products</span>
                <span class="count-badge" style="background:rgba(100,116,139,.15);color:#64748b;"><?= $totalArchives ?></span>
            </div>
            <?php if (empty($listeArchives)): ?>
            <div class="empty-state"><div class="empty-icon">🗄️</div><p data-i18n="noArchived">No archived products.</p></div>
            <?php else: ?>
            <table>
                <thead><tr>
                    <th class="no-sort" data-i18n="colImg">Image</th>
                    <th class="no-sort" data-i18n="colName">Name</th>
                    <th class="no-sort" data-i18n="colCategory">Category</th>
                    <th class="no-sort" data-i18n="colPrice">Price</th>
                    <th class="no-sort" data-i18n="colActions">Actions</th>
                </tr></thead>
                <tbody>
                <?php foreach ($listeArchives as $a): ?>
                <tr>
                    <td class="td-img"><div class="td-img-thumb"><?php if(!empty($a['image'])): ?><img src="<?= htmlspecialchars(cre8_product_image_url($a['image']), ENT_QUOTES, 'UTF-8') ?>" alt=""><?php else: ?><div class="td-img-empty">📦</div><?php endif; ?></div></td>
                    <td class="td-name" style="opacity:.7"><?= htmlspecialchars($a['nomProduit'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?php if(!empty($a['categorie'])): ?><span class="cat-badge" style="opacity:.7"><?= htmlspecialchars($a['categorie'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?></td>
                    <td><span class="prix-badge" style="opacity:.7"><?= number_format((float)$a['prix'],2) ?> <?= DEVISE ?></span></td>
                    <td>
                        <div class="action-group">
                            <button class="btn-action btn-restore" onclick="ajaxToggle('archive',<?= $a['idProduit'] ?>,'Restore')" data-i18n="btnRestore">♻️ Restore</button>
                            <a href="?edit=<?= $a['idProduit'] ?>" class="btn-action btn-edit-a" data-i18n="btnEdit">✏️ Edit</a>
                            <button class="btn-action btn-delete" onclick="openDeleteModal(<?= $a['idProduit'] ?>,'<?= htmlspecialchars(addslashes($a['nomProduit']), ENT_QUOTES, 'UTF-8') ?>')" data-i18n="btnDelete">🗑</button>
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
        </div><!-- /produit-admin -->
    </div><!-- /content-wrapper -->
    </div><!-- /main-panel -->
</div><!-- /page-body-wrapper -->
</div><!-- /container-scroller -->

<!-- PREVIEW MODAL -->
<div class="preview-modal-overlay" id="previewModal">
    <div class="preview-modal-box">
        <div id="previewImgWrap"></div>
        <div class="preview-modal-body">
            <div id="previewTitle" class="preview-modal-title"></div>
            <div id="previewPrice" style="font-size:20px;font-weight:700;color:var(--success);margin-bottom:10px;"></div>
            <div class="preview-section-label" data-i18n="previewLabelDesc">Description</div>
            <div id="previewDesc" class="preview-text"></div>
            <div class="preview-section-label" data-i18n="previewLabelTags">Tags</div>
            <div id="previewTags" class="preview-text"></div>
            <div style="display:flex;align-items:center;margin-top:18px;padding-top:14px;border-top:1px solid var(--border);">
                <span style="font-size:12px;color:var(--text-dim)"><span data-i18n="previewProductId">Product ID:</span> <span id="previewId"></span></span>
                <button class="preview-close-btn" onclick="closePreview()" data-i18n="btnClose">✕ Close</button>
            </div>
        </div>
    </div>
</div>

<!-- DELETE MODAL -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal-box">
        <div class="modal-title" data-i18n="deleteModalTitle">🗑 Confirm deletion</div>
        <div class="modal-text" id="deleteModalText"></div>
        <div class="modal-actions">
            <button class="btn-modal-cancel" id="btnCancelDelete" data-i18n="btnCancel">Cancel</button>
            <a href="#" class="btn-modal-confirm" id="confirmDeleteBtn" data-i18n="btnConfirmDelete">Delete</a>
        </div>
    </div>
</div>

<script src="../layout/back-layout.js<?= produitAssetVersion(__DIR__ . '/../layout/back-layout.js') ?>"></script>
<script>
const BASE_URL = <?= json_encode($baseUrl, JSON_UNESCAPED_SLASHES) ?>;
const DEVISE_JS='<?= DEVISE ?>';

/* ─── TOAST ──────────────────────────────────────────────────────── */
function showToast(msg, type='info', dur=4000) {
    const icons={success:'✅',danger:'🗑️',info:'ℹ️',error:'⚠️',warning:'⚠️'};
    const c=document.getElementById('toastContainer');
    const t=document.createElement('div');
    t.className=`toast toast-${type}`;
    t.innerHTML=`<span>${icons[type]||'ℹ️'}</span><span style="flex:1">${msg}</span><button class="toast-close" onclick="this.closest('.toast').remove()">✕</button>`;
    c.appendChild(t);
    setTimeout(()=>{t.classList.add('hide');setTimeout(()=>t.remove(),450);},dur);
}
<?php if ($message): ?>showToast(<?= json_encode($message) ?>,'<?= $messageType ?>');<?php endif; ?>
const alertEl=document.getElementById('alertMsg');
if(alertEl) setTimeout(()=>alertEl.style.display='none',4500);

/* ─── CHARTS ─────────────────────────────────────────────────────── */
let productCharts = [];
function initCharts(){
    productCharts.forEach(chart => chart.destroy());
    productCharts = [];
    Chart.getChart('chartCategories')?.destroy();
    Chart.getChart('chartPrices')?.destroy();
    const chartTheme = window.getBackOfficeChartTheme ? window.getBackOfficeChartTheme() : { text:'#7c8ba1', grid:'rgba(255,255,255,.04)', accent:'#8b5cf6', accentSoft:'rgba(139,92,246,.6)', rose:'#ef4444', roseSoft:'rgba(239,68,68,.6)' };
    const catLabels=<?= json_encode(array_keys($top5cats)) ?>;
    const catVals  =<?= json_encode(array_values($top5cats)) ?>;
    const baseColors=[chartTheme.accentSoft,'rgba(16,185,129,.45)','rgba(245,158,11,.45)','rgba(59,130,246,.45)',chartTheme.roseSoft];
    const borderColors=[chartTheme.accent,'#10b981','#f59e0b','#3b82f6',chartTheme.rose];
    const opt={responsive:true,maintainAspectRatio:false,plugins:{legend:{labels:{color:chartTheme.text,font:{size:11}}}}};
    productCharts.push(new Chart(document.getElementById('chartCategories'),{type:'bar',data:{labels:catLabels,datasets:[{label:'Products',data:catVals,backgroundColor:baseColors,borderColor:borderColors,borderWidth:2,borderRadius:6}]},options:{...opt,plugins:{legend:{display:false}},scales:{x:{ticks:{color:chartTheme.text},grid:{color:chartTheme.grid}},y:{ticks:{color:chartTheme.text},grid:{color:chartTheme.grid}}}}}));
    // Price distribution (ranges)
    const ranges=['0-25','25-50','50-100','100-250','250+'];
    const rangeCounts=[0,0,0,0,0];
    <?php foreach ($liste as $p): ?>
    (function(p){
        if(p<=25) rangeCounts[0]++;
        else if(p<=50) rangeCounts[1]++;
        else if(p<=100) rangeCounts[2]++;
        else if(p<=250) rangeCounts[3]++;
        else rangeCounts[4]++;
    })(<?= (float)$p['prix'] ?>);
    <?php endforeach; ?>
    new Chart(document.getElementById('chartPrices'),{type:'doughnut',data:{labels:ranges.map(r=>r+'€'),datasets:[{data:rangeCounts,backgroundColor:baseColors,borderColor:baseColors.map(c=>c.replace('.6','.9')),borderWidth:2}]},options:{...opt,cutout:'60%'}});
}
initCharts();
window.addEventListener('cre8:themechange', initCharts);

/* ─── FORM VALIDATION ────────────────────────────────────────────── */
function showFieldError(errId, msg) {
    const el=document.getElementById(errId);
    if(!el) return;
    if(msg){el.textContent='⚠ '+msg;el.classList.add('visible');}
    else el.classList.remove('visible');
}
function setFC(el, ok) {
    if(!el) return;
    el.classList.toggle('is-invalid',!ok);
    el.classList.toggle('is-valid', ok);
}
function updateCounter(id, counterId) {
    const f=document.getElementById(id), c=document.getElementById(counterId);
    if(!f||!c) return;
    c.textContent=f.value.length+'/'+f.maxLength;
    c.className='char-counter'+(f.value.length>f.maxLength*.85?' warn':'');
}
function isValidPrice(v) {
    if(!v.trim()) return false;
    const n=parseFloat(v.replace(',','.'));
    if(isNaN(n)||n<0) return false;
    const p=v.replace(',','.').split('.');
    return p[0].length<=8 && (!p[1]||p[1].length<=2);
}
function isValidDate(v) {
    if(!v) return true;
    if(!/^\d{4}-\d{2}-\d{2}$/.test(v)) return false;
    const [y,m,d]=v.split('-').map(Number);
    const dt=new Date(y,m-1,d);
    return dt.getFullYear()===y&&dt.getMonth()===m-1&&dt.getDate()===d;
}

const fNom=document.getElementById('fNom');
const fPrix=document.getElementById('fPrix');
const fDate=document.getElementById('fDate');
const fDesc=document.getElementById('fDesc');
const fTags=document.getElementById('fTags');

if(fNom) {
    fNom.addEventListener('input',()=>{
        updateCounter('fNom','ctrNom');
        const v=fNom.value.trim();
        const ok=v.length>=2&&!/<[^>]+>/.test(v);
        setFC(fNom,ok);
        showFieldError('errNom',ok?'':v.length<2?'Min 2 characters.':'HTML not allowed.');
    });
}
if(fPrix) {
    fPrix.addEventListener('keydown',e=>{
        const sys=['Backspace','Delete','Tab','Escape','Enter','ArrowLeft','ArrowRight','Home','End'];
        if(sys.includes(e.key)||e.ctrlKey||e.metaKey) return;
        if((e.key==='.'||e.key===',')&&!(fPrix.value.includes('.')||fPrix.value.includes(','))) return;
        if(!/^\d$/.test(e.key)) e.preventDefault();
    });
    fPrix.addEventListener('input',()=>{
        const ok=isValidPrice(fPrix.value);
        setFC(fPrix,ok);
        if(!ok){
            const v=fPrix.value;
            if(!v.trim()) showFieldError('errPrix','Price is required.');
            else if(parseFloat(v.replace(',','.'))<0) showFieldError('errPrix','Must be ≥ 0.');
            else showFieldError('errPrix','Use format: 29.99 (max 2 decimals, max 8 integer digits).');
        } else showFieldError('errPrix','');
    });
}
if(fDate) {
    fDate.addEventListener('keydown',e=>{
        const sys=['Backspace','Delete','Tab','Escape','Enter','ArrowLeft','ArrowRight','Home','End'];
        if(sys.includes(e.key)||e.ctrlKey||e.metaKey||e.key==='-') return;
        if(!/^\d$/.test(e.key)) e.preventDefault();
    });
    fDate.addEventListener('input',()=>{
        let v=fDate.value.replace(/[^\d]/g,'');
        if(v.length>4) v=v.slice(0,4)+'-'+v.slice(4);
        if(v.length>7) v=v.slice(0,7)+'-'+v.slice(7);
        fDate.value=v.slice(0,10);
        const ok=isValidDate(fDate.value);
        setFC(fDate,ok||!fDate.value);
        showFieldError('errDate',ok||!fDate.value?'':'Invalid date. Format: YYYY-MM-DD.');
    });
}
if(fDesc) fDesc.addEventListener('input',()=>updateCounter('fDesc','ctrDesc'));
if(fTags) fTags.addEventListener('input',()=>{
    updateCounter('fTags','ctrTags');
    const ok=!/<[^>]+>/.test(fTags.value);
    setFC(fTags,ok);
    showFieldError('errTags',ok?'':'HTML not allowed in tags.');
});

const fileInputBO=document.getElementById('fileInputBO');
if(fileInputBO) fileInputBO.addEventListener('change',function(){
    const f=this.files[0]; if(!f) return;
    const ok=f.size<=2*1024*1024&&['image/jpeg','image/png','image/webp'].includes(f.type);
    showFieldError('errImage',ok?'':'File too large or invalid format (JPG, PNG, WEBP, max 2MB).');
    if(ok){
        const r=new FileReader();
        r.onload=e=>{const img=document.getElementById('imgPreviewBO');img.src=e.target.result;img.style.display='block';};
        r.readAsDataURL(f);
    } else this.value='';
});

const boToggleEp=document.getElementById('boToggleEpingle');
const boToggleAr=document.getElementById('boToggleArchive');
if(boToggleEp) boToggleEp.addEventListener('change',function(){document.getElementById('epingleHiddenBO').value=this.checked?1:0;});
if(boToggleAr) boToggleAr.addEventListener('change',function(){document.getElementById('archiveHiddenBO').value=this.checked?1:0;});

const prodForm=document.getElementById('produitFormBO');
if(prodForm) prodForm.addEventListener('submit',function(e){
    let ok=true;
    if(fNom){const v=fNom.value.trim();if(v.length<2||/<[^>]+>/.test(v)){setFC(fNom,false);showFieldError('errNom','Min 2 chars, no HTML.');ok=false;}}
    if(fPrix&&!isValidPrice(fPrix.value)){setFC(fPrix,false);showFieldError('errPrix','Valid price required.');ok=false;}
    if(fDate&&fDate.value&&!isValidDate(fDate.value)){setFC(fDate,false);showFieldError('errDate','Invalid date format.');ok=false;}
    if(fTags&&/<[^>]+>/.test(fTags.value)){setFC(fTags,false);showFieldError('errTags','HTML not allowed in tags.');ok=false;}
    if(!ok){
        e.preventDefault();
        showToast('Please fix the form errors.','error');
        document.querySelector('.is-invalid')?.scrollIntoView({behavior:'smooth',block:'center'});
    }
});

if(fNom) updateCounter('fNom','ctrNom');
if(fDesc) updateCounter('fDesc','ctrDesc');
if(fTags) updateCounter('fTags','ctrTags');

/* ─── TABS ───────────────────────────────────────────────────────── */
function switchTab(name, btn) {
    ['actifs','archives'].forEach(t=>{
        document.getElementById('tab-'+t+'-btn')?.classList.toggle('active',t===name);
        document.getElementById('content-'+t)?.classList.toggle('active',t===name);
    });
    document.getElementById('tab-actifs')?.classList.toggle('active',name==='actifs');
    document.getElementById('tab-archives-btn')?.classList.toggle('active',name==='archives');
}

/* ─── FILTER + SEARCH ────────────────────────────────────────────── */
document.getElementById('searchInput').addEventListener('input',filterTable);
document.getElementById('priceMin').addEventListener('input',e=>{
    const v=parseFloat(e.target.value);
    e.target.classList.toggle('is-invalid',!isNaN(v)&&v<0);
    if(!isNaN(v)&&v<0) e.target.value='0';
    filterTable();
});
document.getElementById('priceMax').addEventListener('input',e=>{
    const v=parseFloat(e.target.value);
    e.target.classList.toggle('is-invalid',!isNaN(v)&&v<0);
    if(!isNaN(v)&&v<0) e.target.value='0';
    filterTable();
});

let currentPage=1;
function filterTable() {
    const q=document.getElementById('searchInput').value.toLowerCase().trim();
    const pMin=parseFloat(document.getElementById('priceMin').value)||null;
    const pMax=parseFloat(document.getElementById('priceMax').value)||null;
    const catSel=document.getElementById('catFilterSelect').value.toLowerCase();
    renderPage(1,q,pMin,pMax,catSel);
}

function renderPage(page,q,pMin,pMax,catSel) {
    currentPage=page;
    if(q===undefined) q=document.getElementById('searchInput').value.toLowerCase().trim();
    if(pMin===undefined) pMin=parseFloat(document.getElementById('priceMin').value)||null;
    if(pMax===undefined) pMax=parseFloat(document.getElementById('priceMax').value)||null;
    if(catSel===undefined) catSel=document.getElementById('catFilterSelect').value.toLowerCase();
    const perPage=25;
    const allRows=Array.from(document.querySelectorAll('#tableBody tr'));
    const visible=allRows.filter(row=>{
        const n=row.dataset.nom||'';
        const p=parseFloat(row.dataset.prix)||0;
        const c=row.dataset.cat||'';
        const mQ=!q||n.includes(q);
        const mP=(pMin===null||p>=pMin)&&(pMax===null||p<=pMax);
        const mC=!catSel||c===catSel;
        return mQ&&mP&&mC;
    });
    const total=visible.length;
    const pages=Math.ceil(total/perPage)||1;
    page=Math.min(page,pages);
    const start=(page-1)*perPage, end=Math.min(start+perPage,total);
    allRows.forEach(r=>r.style.display='none');
    visible.forEach((r,i)=>r.style.display=(i>=start&&i<end)?'':'none');
    document.getElementById('visibleCount').textContent=total;
    // ===== ADDED FEATURE: TRANSLATION-AWARE PAGINATION INFO =====
    const T = translations[currentLang] || translations['en'];
    document.getElementById('paginationInfo').textContent=total===0
        ? (T.noResults||'No results')
        : `${start+1}–${end} ${T.of||'of'} ${total}`;
    // ===== END ADDED FEATURE =====
    const btns=document.getElementById('paginationButtons');
    btns.innerHTML='';
    if(pages<=1) return;
    const addBtn=(lbl,p,dis,act)=>{const b=document.createElement('button');b.className='page-btn'+(act?' active':'');b.textContent=lbl;b.disabled=dis;b.onclick=()=>renderPage(p);btns.appendChild(b);};
    // ===== ADDED FEATURE: TRANSLATION-AWARE PREV/NEXT =====
    addBtn(T.prevPage||'←',page-1,page===1,false);
    // ===== END ADDED FEATURE =====
    for(let i=1;i<=pages;i++){
        if(i===1||i===pages||Math.abs(i-page)<=1) addBtn(i,i,false,i===page);
        else if(Math.abs(i-page)===2){const d=document.createElement('span');d.textContent='…';d.style.cssText='padding:5px 4px;color:var(--text-dim);font-size:12px;';btns.appendChild(d);}
    }
    // ===== ADDED FEATURE: TRANSLATION-AWARE PREV/NEXT =====
    addBtn(T.nextPage||'→',page+1,page===pages,false);
    // ===== END ADDED FEATURE =====
}

let sortCol=null,sortDir='asc';
function sortTable(col) {
    if(sortCol===col) sortDir=sortDir==='asc'?'desc':'asc';
    else{sortCol=col;sortDir='asc';}
    document.querySelectorAll('thead th[data-col]').forEach(th=>{th.classList.remove('sort-asc','sort-desc');if(th.dataset.col===col)th.classList.add('sort-'+sortDir);});
    const rows=Array.from(document.querySelectorAll('#tableBody tr'));
    rows.sort((a,b)=>{let va=a.dataset[col]||'',vb=b.dataset[col]||'';if(col==='prix'||col==='id'){va=parseFloat(va);vb=parseFloat(vb);}const r=va>vb?1:va<vb?-1:0;return sortDir==='asc'?r:-r;});
    document.getElementById('tableBody').append(...rows);
    renderPage(currentPage);
}
function applySortSelect() {
    const v=document.getElementById('sortColSelect').value;if(!v)return;
    const[col,dir]=v.split('-');sortCol=col;sortDir=dir;
    document.querySelectorAll('thead th[data-col]').forEach(th=>{th.classList.remove('sort-asc','sort-desc');if(th.dataset.col===col)th.classList.add('sort-'+dir);});
    const rows=Array.from(document.querySelectorAll('#tableBody tr'));
    rows.sort((a,b)=>{let va=a.dataset[col]||'',vb=b.dataset[col]||'';if(col==='prix')va=parseFloat(va),vb=parseFloat(vb);const r=va>vb?1:va<vb?-1:0;return dir==='asc'?r:-r;});
    document.getElementById('tableBody').append(...rows);
    renderPage(currentPage);
}
function resetFilters() {
    document.getElementById('searchInput').value='';
    document.getElementById('priceMin').value='';
    document.getElementById('priceMax').value='';
    document.getElementById('sortColSelect').value='';
    document.getElementById('catFilterSelect').value='';
    sortCol=null;
    document.querySelectorAll('thead th').forEach(th=>th.classList.remove('sort-asc','sort-desc'));
    filterTable();
}

/* ─── AJAX PIN / ARCHIVE ──────────────────────────────────────────── */
function ajaxToggle(action, id, label) {
    const fd=new FormData();fd.append('action',action);fd.append('id',id);
    fetch('index.php',{method:'POST',body:fd}).then(r=>r.json()).then(()=>{
        showToast(`${label} done!`,action==='archive'?'warning':'info');
        setTimeout(()=>location.reload(),1200);
    });
}

/* ─── PRODUCT PREVIEW ────────────────────────────────────────────── */
const produitsMap={};
<?php foreach ($liste as $p): ?>
produitsMap[<?= $p['idProduit'] ?>]={id:<?= $p['idProduit'] ?>,nom:<?= json_encode($p['nomProduit']) ?>,desc:<?= json_encode($p['description']??'') ?>,tags:<?= json_encode($p['caracteristiques']??'') ?>,prix:<?= (float)$p['prix'] ?>,img:<?= json_encode(!empty($p['image']) ? cre8_product_image_url($p['image']) : null) ?>};
<?php endforeach; ?>

function openPreview(id) {
    const p=produitsMap[id];if(!p)return;
    document.getElementById('previewImgWrap').innerHTML=p.img?`<img src="${p.img}" alt="" class="preview-modal-img">`:'<div class="preview-modal-img-empty">📦</div>';
    document.getElementById('previewTitle').textContent=p.nom;
    document.getElementById('previewPrice').textContent=p.prix.toFixed(2)+' '+DEVISE_JS;
    document.getElementById('previewDesc').textContent=p.desc||'—';
    document.getElementById('previewTags').textContent=p.tags||'—';
    document.getElementById('previewId').textContent='#'+p.id;
    document.getElementById('previewModal').classList.add('open');
}
function closePreview(){document.getElementById('previewModal').classList.remove('open');}
document.getElementById('previewModal').addEventListener('click',e=>{if(e.target.id==='previewModal')closePreview();});

/* ─── DELETE MODAL ───────────────────────────────────────────────── */
function openDeleteModal(id,name){
    const T=translations[currentLang]||translations['en'];
    document.getElementById('deleteModalText').textContent=`${T.deleteConfirmMsg||'Delete'} "${name}"? ${T.deleteIrreversible||'Irreversible.'}`;
    document.getElementById('confirmDeleteBtn').href='index.php?delete='+id;
    document.getElementById('deleteModal').classList.add('open');
}
function closeDeleteModal(){document.getElementById('deleteModal').classList.remove('open');}
document.getElementById('btnCancelDelete').addEventListener('click',closeDeleteModal);
document.getElementById('deleteModal').addEventListener('click',e=>{if(e.target.id==='deleteModal')closeDeleteModal();});

document.addEventListener('keydown',e=>{if(e.key==='Escape'){closeDeleteModal();closePreview();}});

// Init pagination on load
document.addEventListener('DOMContentLoaded',()=>renderPage(1));


// ═══════════════════════════════════════════════════════════════════
// ===== ADDED FEATURE: TRANSLATION SYSTEM =====
// ═══════════════════════════════════════════════════════════════════
const translations = {
    en: {
        // Page & Nav
        navDashboard: 'Dashboard',
        navOverview: 'Overview',
        navModules: 'Modules',
        navHome: 'Home',
        navUsers: 'Users',
        navOffers: 'Offers',
        navCampaigns: 'Campaigns',
        navProducts: 'Products',
        navContracts: 'Contracts',
        navEvents: 'Events',
        navPosts: 'Posts',
        navReclamations: 'Complaints',
        adminName: 'Administrator',
        adminRole: 'Super Admin',
        breadAdmin: 'Admin',
        breadProducts: 'Products',
        pageTitle: 'Product Management',
        pageSubtitle: 'Supervise, add, edit and analyze all platform products.',
        // Topbar buttons
        btnPrint: 'Print / PDF',
        btnNewProduct: 'New Product',
        searchPlaceholder: 'Search products…',
        // Theme toggle
        themeDarkLabel: 'Dark mode',
        themeLightLabel: 'Light mode',
        // KPI cards
        kpiTotalActive: 'Total Active',
        kpiProductsSub: 'products',
        kpiAvgPrice: 'Avg. Price',
        kpiCatalogValue: 'Catalog Value',
        kpiHighestPrice: 'Highest Price',
        kpiMissingImage: 'Missing Image',
        kpiNeedImage: 'need image',
        kpiPinned: 'Pinned',
        kpiFeatured: 'featured',
        kpiArchived: 'Archived',
        kpiHidden: 'hidden',
        // Charts
        chartCatTitle: '📊 Products by Category (Top 5)',
        chartPriceTitle: '💰 Price Distribution',
        // Form
        formAddTitle: '➕ Add a Product',
        formEditTitle: '✏️ Edit Product',
        labelName: 'Product name',
        labelPrice: 'Price',
        labelCategory: 'Category',
        optSelectCategory: '— Select —',
        labelAvailability: 'Availability date',
        labelOptional: '(optional — YYYY-MM-DD)',
        labelDescription: 'Description',
        labelTags: 'Characteristics / Tags',
        labelCommaSep: '(comma-separated)',
        labelInternalNote: 'Internal note',
        labelAdminOnly: 'Admin only',
        noteInterneBanner: '🔒 Visible to admin team only',
        togglePin: '📌 Pin',
        toggleArchive: '🗄️ Archive',
        labelImage: 'Image',
        currentImageHint: 'Current image — upload to replace.',
        uploadClick: 'Click to upload',
        uploadHint: '(JPG, PNG, WEBP — 2MB max)',
        btnSaveChanges: 'Save changes',
        btnAddProduct: 'Add product',
        btnCancel: 'Cancel',
        // Validation errors
        errName: 'Name required — min 2 characters, no HTML.',
        errPrice: 'Valid price required (e.g. 29.99, max 2 decimals).',
        errDate: 'Invalid date. Use YYYY-MM-DD (e.g. 2025-12-31).',
        errTags: 'Tags must not contain HTML.',
        errImage: 'File too large or invalid format (JPG, PNG, WEBP only, max 2MB).',
        // Filters
        filterSortBy: 'Sort by',
        filterDefault: 'Default',
        filterNameAZ: 'Name A→Z',
        filterNameZA: 'Name Z→A',
        filterPriceAsc: 'Price ↑',
        filterPriceDesc: 'Price ↓',
        filterPriceRange: 'Price (€)',
        filterMin: 'Min',
        filterMax: 'Max',
        filterCategory: 'Category',
        filterAll: 'All',
        filterReset: 'Reset',
        // Tabs
        tabActive: 'Active',
        tabArchived: 'Archived',
        // Table
        tablePanelTitle: 'Product list',
        noProducts: 'No products found.',
        colImg: 'Img',
        colId: '#ID',
        colName: 'Name',
        colCategory: 'Category',
        colDescription: 'Description',
        colPrice: 'Price',
        colStatus: 'Status',
        colActions: 'Actions',
        statusPinned: '📌 Pinned',
        statusFuture: '⏳ Future',
        statusLive: '✅ Live',
        // Buttons
        btnView: '👁 View',
        btnEdit: '✏️ Edit',
        btnPin: '📌 Pin',
        btnUnpin: '📌 Unpin',
        btnArchive: '🗄️',
        btnDelete: '🗑',
        // Archived tab
        archivedTitle: 'Archived products',
        noArchived: 'No archived products.',
        btnRestore: '♻️ Restore',
        // Pagination
        prevPage: '←',
        nextPage: '→',
        of: 'of',
        noResults: 'No results',
        // Preview modal
        previewLabelDesc: 'Description',
        previewLabelTags: 'Tags',
        previewProductId: 'Product ID:',
        btnClose: '✕ Close',
        // Delete modal
        deleteModalTitle: '🗑 Confirm deletion',
        deleteConfirmMsg: 'Delete',
        deleteIrreversible: 'This action is irreversible.',
        btnConfirmDelete: 'Delete',
    },
    fr: {
        // Page & Nav
        navDashboard: 'Tableau de bord',
        navOverview: 'Aperçu',
        navModules: 'Modules',
        navHome: 'Accueil',
        navUsers: 'Utilisateurs',
        navOffers: 'Offres',
        navCampaigns: 'Campagnes',
        navProducts: 'Produits',
        navContracts: 'Contrats',
        navEvents: 'Événements',
        navPosts: 'Posts',
        navReclamations: 'Réclamations',
        adminName: 'Administrateur',
        adminRole: 'Super Admin',
        breadAdmin: 'Admin',
        breadProducts: 'Produits',
        pageTitle: 'Gestion des Produits',
        pageSubtitle: 'Supervisez, ajoutez, modifiez et analysez tous les produits de la plateforme.',
        // Topbar buttons
        btnPrint: 'Imprimer / PDF',
        btnNewProduct: 'Nouveau Produit',
        searchPlaceholder: 'Rechercher des produits…',
        // Theme toggle
        themeDarkLabel: 'Mode sombre',
        themeLightLabel: 'Mode clair',
        // KPI cards
        kpiTotalActive: 'Total actifs',
        kpiProductsSub: 'produits',
        kpiAvgPrice: 'Prix moyen',
        kpiCatalogValue: 'Valeur catalogue',
        kpiHighestPrice: 'Prix le plus élevé',
        kpiMissingImage: 'Sans image',
        kpiNeedImage: 'à corriger',
        kpiPinned: 'Épinglés',
        kpiFeatured: 'mis en avant',
        kpiArchived: 'Archivés',
        kpiHidden: 'masqués',
        // Charts
        chartCatTitle: '📊 Produits par catégorie (Top 5)',
        chartPriceTitle: '💰 Répartition des prix',
        // Form
        formAddTitle: '➕ Ajouter un produit',
        formEditTitle: '✏️ Modifier le produit',
        labelName: 'Nom du produit',
        labelPrice: 'Prix',
        labelCategory: 'Catégorie',
        optSelectCategory: '— Sélectionner —',
        labelAvailability: 'Date de disponibilité',
        labelOptional: '(optionnel — AAAA-MM-JJ)',
        labelDescription: 'Description',
        labelTags: 'Caractéristiques / Tags',
        labelCommaSep: '(séparés par des virgules)',
        labelInternalNote: 'Note interne',
        labelAdminOnly: 'Admin seulement',
        noteInterneBanner: '🔒 Visible uniquement par l\'équipe admin',
        togglePin: '📌 Épingler',
        toggleArchive: '🗄️ Archiver',
        labelImage: 'Image',
        currentImageHint: 'Image actuelle — téléchargez pour remplacer.',
        uploadClick: 'Cliquez pour télécharger',
        uploadHint: '(JPG, PNG, WEBP — 2 Mo max)',
        btnSaveChanges: 'Enregistrer',
        btnAddProduct: 'Ajouter le produit',
        btnCancel: 'Annuler',
        // Validation errors
        errName: 'Nom requis — min 2 caractères, pas de HTML.',
        errPrice: 'Prix valide requis (ex. 29.99, max 2 décimales).',
        errDate: 'Date invalide. Utilisez AAAA-MM-JJ (ex. 2025-12-31).',
        errTags: 'Les tags ne doivent pas contenir de HTML.',
        errImage: 'Fichier trop grand ou format invalide (JPG, PNG, WEBP uniquement, max 2 Mo).',
        // Filters
        filterSortBy: 'Trier par',
        filterDefault: 'Par défaut',
        filterNameAZ: 'Nom A→Z',
        filterNameZA: 'Nom Z→A',
        filterPriceAsc: 'Prix ↑',
        filterPriceDesc: 'Prix ↓',
        filterPriceRange: 'Prix (€)',
        filterMin: 'Min',
        filterMax: 'Max',
        filterCategory: 'Catégorie',
        filterAll: 'Toutes',
        filterReset: 'Réinitialiser',
        // Tabs
        tabActive: 'Actifs',
        tabArchived: 'Archivés',
        // Table
        tablePanelTitle: 'Liste des produits',
        noProducts: 'Aucun produit trouvé.',
        colImg: 'Img',
        colId: '#ID',
        colName: 'Nom',
        colCategory: 'Catégorie',
        colDescription: 'Description',
        colPrice: 'Prix',
        colStatus: 'Statut',
        colActions: 'Actions',
        statusPinned: '📌 Épinglé',
        statusFuture: '⏳ À venir',
        statusLive: '✅ En ligne',
        // Buttons
        btnView: '👁 Voir',
        btnEdit: '✏️ Modifier',
        btnPin: '📌 Épingler',
        btnUnpin: '📌 Désépingler',
        btnArchive: '🗄️',
        btnDelete: '🗑',
        // Archived tab
        archivedTitle: 'Produits archivés',
        noArchived: 'Aucun produit archivé.',
        btnRestore: '♻️ Restaurer',
        // Pagination
        prevPage: '←',
        nextPage: '→',
        of: 'sur',
        noResults: 'Aucun résultat',
        // Preview modal
        previewLabelDesc: 'Description',
        previewLabelTags: 'Tags',
        previewProductId: 'ID Produit :',
        btnClose: '✕ Fermer',
        // Delete modal
        deleteModalTitle: '🗑 Confirmer la suppression',
        deleteConfirmMsg: 'Supprimer',
        deleteIrreversible: 'Cette action est irréversible.',
        btnConfirmDelete: 'Supprimer',
    }
};

function cre8BoReadLang() {
    if (window.cre8BackGetLang) return window.cre8BackGetLang();
    return localStorage.getItem('cre8_back_lang')
        || localStorage.getItem('cre8_bo_lang')
        || localStorage.getItem('cre8_lang')
        || localStorage.getItem('cre8_lang_produit')
        || localStorage.getItem('cre8_lang_campagne')
        || localStorage.getItem('cre8_lang_contrat')
        || 'fr';
}
function cre8BoWriteLang(lang) {
    localStorage.setItem('cre8_back_lang', lang);
    localStorage.setItem('cre8_bo_lang', lang);
    localStorage.setItem('cre8_lang', lang);
}

let currentLang = cre8BoReadLang();

function setLang(lang) {
    if (window.cre8BackSetLang && window.cre8BackGetLang && window.cre8BackGetLang() !== lang) {
        window.cre8BackSetLang(lang);
        return;
    }
    currentLang = lang;
    cre8BoWriteLang(lang);
    applyTranslations();
    document.getElementById('langEN')?.classList.toggle('active', lang === 'en');
    document.getElementById('langFR')?.classList.toggle('active', lang === 'fr');
    // Refresh pagination text
    renderPage(currentPage);
}

function applyTranslations() {
    const T = translations[currentLang] || translations['en'];

    // Text content elements
    document.querySelectorAll('[data-i18n]').forEach(el => {
        const key = el.getAttribute('data-i18n');
        if (T[key] !== undefined) el.textContent = T[key];
    });

    // Placeholder attributes
    document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
        const key = el.getAttribute('data-i18n-placeholder');
        if (T[key] !== undefined) el.setAttribute('placeholder', T[key]);
    });

    // Theme label (must stay in sync with current theme)
    syncThemeLabel();
}
// ===== END ADDED FEATURE: TRANSLATION SYSTEM =====


// ═══════════════════════════════════════════════════════════════════
// ===== ADDED FEATURE: LIGHT/DARK MODE TOGGLE =====
// ===== BackOffice default = DARK. Light only if user chose it. =====
// ═══════════════════════════════════════════════════════════════════
function syncThemeLabel() {
    const T = translations[currentLang] || translations['en'];
    const isLight = document.body.classList.contains('light-mode');
    const icon  = document.getElementById('themeIcon');
    const label = document.getElementById('themeLabel');

    // The shared BackOffice header uses MDI icon classes and back-layout.js controls them.
    // Do not inject emoji text here, otherwise the product page theme button looks different.
    if (icon) icon.textContent = '';

    if (label) label.textContent = isLight ? (T.themeDarkLabel || 'Dark mode') : (T.themeLightLabel || 'Light mode');
}

function toggleTheme() {
    window.toggleBackOfficeTheme ? window.toggleBackOfficeTheme() : document.body.classList.toggle('light-mode');
    syncThemeLabel();
    initCharts();
}

function initTheme() {
    if (window.applyBackOfficeTheme) window.applyBackOfficeTheme();
    syncThemeLabel();
}
// ===== END ADDED FEATURE: LIGHT/DARK MODE TOGGLE =====


// ═══════════════════════════════════════════════════════════════════
// INIT — boot all added features after DOM is ready
// ═══════════════════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', () => {
    // Init theme (dark default for BackOffice)
    initTheme();
    // Init language
    if (window.cre8BackRegisterTranslations) {
        window.cre8BackRegisterTranslations(translations);
    }
    currentLang = cre8BoReadLang();
    applyTranslations();
    document.getElementById('langEN')?.classList.toggle('active', currentLang === 'en');
    document.getElementById('langFR')?.classList.toggle('active', currentLang === 'fr');
});

window.addEventListener('cre8:languagechange', function(event) {
    currentLang = (event.detail && event.detail.lang) || cre8BoReadLang();
    applyTranslations();
    renderPage(currentPage);
});
</script>
<script src="../business-center-admin.js<?= produitAssetVersion(__DIR__ . '/../business-center-admin.js') ?>"></script>
</body>
</html>
