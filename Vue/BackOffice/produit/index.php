<?php
require_once '../../../Controleur/produitC.php';
require_once '../../../Modele/produit.php';

$produitC = new ProduitC();
$message = '';
$messageType = '';
$baseUrl = '/projet/Esprit-PW-2A22-2526-Devcore';
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
        $produit  = new Produit(null,htmlspecialchars($nom),trim($_POST['description']??''),
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
        $produit  = new Produit(null,htmlspecialchars($nom),trim($_POST['description']??''),
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin — Product Management | Cre8Connect</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
body{font-family:'Inter',system-ui,sans-serif;background:var(--bg-base);color:var(--text-primary);min-height:100vh;display:flex;}
/* SIDEBAR */
.sidebar{width:var(--sidebar-w);background:var(--bg-surface);border-right:1px solid var(--border);display:flex;flex-direction:column;position:fixed;top:0;left:0;bottom:0;z-index:100;}
.sidebar-logo{padding:18px 20px;border-bottom:1px solid var(--border);}
.sidebar-logo .brand{display:flex;align-items:center;gap:10px;text-decoration:none;}
.logo-img{width:34px;height:34px;object-fit:contain;border-radius:var(--radius-sm);}
.logo-text{font-size:15px;font-weight:700;color:var(--text-primary);}
.logo-badge{font-size:9px;font-weight:600;color:var(--accent);background:var(--accent-soft);padding:2px 6px;border-radius:20px;margin-top:1px;}
.sidebar-nav{flex:1;padding:12px 10px;overflow-y:auto;}
.nav-section-label{font-size:10px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:var(--text-dim);padding:10px 10px 6px;}
.nav-item{display:flex;align-items:center;gap:10px;padding:8px 10px;border-radius:var(--radius-sm);color:var(--text-muted);text-decoration:none;font-size:13px;font-weight:450;transition:background .15s,color .15s;cursor:pointer;margin-bottom:2px;}
.nav-item:hover{background:rgba(139,92,246,.06);color:var(--text-primary);}
.nav-item.active{background:var(--accent-soft);color:var(--accent);}
.nav-icon{width:18px;height:18px;opacity:.8;flex-shrink:0;}
.sidebar-footer{padding:12px;border-top:1px solid var(--border);}
.admin-card{display:flex;align-items:center;gap:10px;padding:8px 10px;border-radius:var(--radius-sm);background:var(--bg-card-alt);}
.admin-avatar{width:30px;height:30px;border-radius:50%;background:var(--accent);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:600;color:#fff;}
.admin-name{font-size:12px;font-weight:500;}
.admin-role{font-size:11px;color:var(--text-muted);}
/* TOPBAR */
.topbar{position:fixed;top:0;left:var(--sidebar-w);right:0;height:58px;background:var(--bg-surface);border-bottom:1px solid var(--border);display:flex;align-items:center;padding:0 28px;gap:16px;z-index:90;}
.topbar-breadcrumb{display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text-muted);}
.topbar-breadcrumb .sep{opacity:.4;}
.topbar-breadcrumb .current{color:var(--text-primary);font-weight:500;}
.topbar-actions{margin-left:auto;display:flex;align-items:center;gap:10px;}
.btn-add{display:flex;align-items:center;gap:7px;background:var(--accent);color:#fff;border:none;padding:7px 14px;border-radius:var(--radius-sm);font-size:13px;font-weight:500;cursor:pointer;text-decoration:none;font-family:inherit;}
.btn-add:hover{background:var(--accent-hover);}
.btn-export{display:flex;align-items:center;gap:6px;background:var(--success-soft);color:var(--success);border:1px solid rgba(16,185,129,.2);padding:7px 12px;border-radius:var(--radius-sm);font-size:12px;font-weight:500;cursor:pointer;text-decoration:none;font-family:inherit;}
.search-wrap{position:relative;}
.search-input{background:var(--bg-input);border:1px solid var(--border);border-radius:var(--radius-sm);padding:7px 12px 7px 34px;color:var(--text-primary);font-size:13px;width:220px;outline:none;transition:border-color .2s;font-family:inherit;}
.search-input:focus{border-color:var(--border-focus);}
.search-icon{position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-dim);pointer-events:none;width:15px;height:15px;}
/* MAIN */
.main{margin-left:var(--sidebar-w);padding-top:58px;flex:1;min-height:100vh;}
.content{padding:28px 28px 60px;}
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
.kpi-card{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);padding:14px 16px;position:relative;overflow:hidden;}
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
.chart-card{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);padding:20px;}
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
.filter-bar{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);padding:14px 18px;margin-bottom:16px;display:flex;align-items:flex-end;gap:16px;flex-wrap:wrap;}
.filter-group{display:flex;flex-direction:column;gap:5px;}
.filter-label{font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-dim);}
.filter-select{background:var(--bg-input);border:1px solid var(--border);border-radius:var(--radius-sm);padding:6px 10px;color:var(--text-primary);font-size:12px;outline:none;cursor:pointer;font-family:inherit;}
.price-range-wrap{display:flex;align-items:center;gap:8px;}
.price-input{background:var(--bg-input);border:1px solid var(--border);border-radius:var(--radius-sm);padding:6px 8px;color:var(--text-primary);font-size:12px;width:80px;outline:none;font-family:inherit;}
.price-input:focus{border-color:var(--border-focus);}
.price-input.is-invalid{border-color:var(--danger);}
.price-sep{color:var(--text-dim);font-size:12px;}
.btn-reset-filter{background:transparent;color:var(--text-muted);border:1px solid var(--border);border-radius:var(--radius-sm);padding:6px 10px;font-size:12px;cursor:pointer;font-family:inherit;}
/* TABLE */
.table-panel{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;}
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
.page-btn{background:var(--bg-card-alt);border:1px solid var(--border);border-radius:var(--radius-sm);padding:5px 10px;font-size:12px;color:var(--text-muted);cursor:pointer;min-width:30px;text-align:center;font-family:inherit;}
.page-btn.active{background:var(--accent);border-color:var(--accent);color:#fff;}
.page-btn:disabled{opacity:.4;cursor:not-allowed;}
/* FORM */
.form-panel{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);margin-bottom:24px;overflow:hidden;}
.form-panel-header{display:flex;align-items:center;gap:10px;padding:14px 20px;border-bottom:1px solid var(--border);}
.form-panel-title{font-size:14px;font-weight:600;}
.form-body{padding:20px;}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.form-col-full{grid-column:1/-1;}
.form-group{display:flex;flex-direction:column;gap:4px;}
.form-label{font-size:12px;font-weight:500;color:var(--text-muted);}
.form-control{background:var(--bg-input);border:1px solid var(--border);border-radius:var(--radius-sm);padding:9px 12px;color:var(--text-primary);font-size:13px;font-family:inherit;transition:border-color .2s;width:100%;outline:none;}
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
    .main{margin-left:0;padding-top:0;}
    .kpi-strip{grid-template-columns:repeat(4,1fr);}
}

@media(max-width:1300px){.kpi-strip{grid-template-columns:repeat(4,1fr);}}
@media(max-width:1000px){.kpi-strip{grid-template-columns:repeat(3,1fr);}}
@media(max-width:900px){.kpi-strip{grid-template-columns:repeat(2,1fr);}.form-grid{grid-template-columns:1fr;}.sidebar{display:none;}.topbar,.main{left:0;margin-left:0;}}
</style>
</head>
<body>
<div id="toastContainer"></div>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="sidebar-logo">
        <a href="#" class="brand">
            <img src="<?= $baseUrl ?>/Vue/public/images/logo.png" alt="Logo" class="logo-img">
            <div><div class="logo-text">Cre8Connect</div><div class="logo-badge">ADMIN</div></div>
        </a>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section-label">Dashboard</div>
        <a class="nav-item" href="#"><svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>Home</a>
        <a class="nav-item" href="#"><svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>Users</a>
        <a class="nav-item" href="#"><svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>Campaigns</a>
        <a class="nav-item active" href="index.php"><svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 10V11"/></svg>Products</a>
        <a class="nav-item" href="#"><svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>Posts & Comments</a>
    </nav>
    <div class="sidebar-footer">
        <div class="admin-card">
            <div class="admin-avatar">A</div>
            <div><div class="admin-name">Administrator</div><div class="admin-role">Super Admin</div></div>
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
        <div class="search-wrap">
            <svg class="search-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
            <input type="text" class="search-input" id="searchInput" placeholder="Search products…">
        </div>
        <button class="btn-export" onclick="window.print()">
            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Print / PDF
        </button>
        <a href="?export_csv=1" class="btn-export" style="margin-left:0;">
            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            CSV
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

    <div style="margin-bottom:22px;">
        <div class="page-title">Product Management</div>
        <div class="page-subtitle">Supervise, add, edit and analyze all platform products.</div>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>" id="alertMsg"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <!-- KPI -->
    <div class="kpi-strip">
        <div class="kpi-card kpi-accent"><div class="kpi-label">Total Active</div><div class="kpi-value"><?= $totalProduits ?></div><div class="kpi-sub">products</div></div>
        <div class="kpi-card kpi-info"><div class="kpi-label">Avg. Price</div><div class="kpi-value"><?= number_format($prixMoyen,2) ?></div><div class="kpi-sub"><?= DEVISE ?></div></div>
        <div class="kpi-card kpi-success"><div class="kpi-label">Catalog Value</div><div class="kpi-value"><?= number_format($valeurCatalogue,0) ?></div><div class="kpi-sub"><?= DEVISE ?></div></div>
        <div class="kpi-card kpi-warning"><div class="kpi-label">Highest Price</div><div class="kpi-value"><?= number_format($prixMax,2) ?></div><div class="kpi-sub"><?= DEVISE ?></div></div>
        <div class="kpi-card kpi-purple"><div class="kpi-label">Missing Image</div><div class="kpi-value"><?= $sanImage ?></div><div class="kpi-sub">need image</div></div>
        <div class="kpi-card kpi-warning" style="--warning:#f59e0b"><div class="kpi-label">Pinned</div><div class="kpi-value"><?= $nbEpingles ?></div><div class="kpi-sub">featured</div></div>
        <div class="kpi-card kpi-archive"><div class="kpi-label">Archived</div><div class="kpi-value"><?= $totalArchives ?></div><div class="kpi-sub">hidden</div></div>
    </div>

    <!-- CHARTS -->
    <div class="charts-row">
        <div class="chart-card">
            <div class="chart-card-title">📊 Products by Category (Top 5)</div>
            <div class="chart-wrap"><canvas id="chartCategories"></canvas></div>
        </div>
        <div class="chart-card">
            <div class="chart-card-title">💰 Price Distribution</div>
            <div class="chart-wrap"><canvas id="chartPrices"></canvas></div>
        </div>
    </div>

    <!-- FORM -->
    <?php if (isset($_GET['add']) || $produitUpdate): ?>
    <div class="form-panel" id="formPanel">
        <div class="form-panel-header">
            <span style="font-size:14px;font-weight:600;"><?= $produitUpdate ? '✏️ Edit Product' : '➕ Add a Product' ?></span>
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
                        <label class="form-label">Product name * <span id="ctrNom" class="char-counter">0/80</span></label>
                        <input type="text" name="nom" id="fNom" class="form-control" maxlength="80"
                               value="<?= htmlspecialchars($produitUpdate['nomProduit']??'') ?>"
                               placeholder="e.g. Premium Moisturizer">
                        <div class="field-error-msg" id="errNom">Name required — min 2 characters, no HTML.</div>
                    </div>
                    <!-- PRICE -->
                    <div class="form-group">
                        <label class="form-label">Price (<?= DEVISE ?>) *</label>
                        <input type="text" name="prix" id="fPrix" class="form-control"
                               value="<?= htmlspecialchars($produitUpdate['prix']??'') ?>" placeholder="0.00" autocomplete="off">
                        <div class="field-error-msg" id="errPrix">Valid price required (e.g. 29.99, max 2 decimals).</div>
                    </div>
                    <!-- CATEGORY -->
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select name="categorie" class="form-control">
                            <option value="">— Select —</option>
                            <?php foreach ($categoriesDisponibles as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>" <?= ($produitUpdate&&($produitUpdate['categorie']??'')===$cat)?'selected':'' ?>><?= htmlspecialchars($cat) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- DATE DISPO -->
                    <div class="form-group">
                        <label class="form-label">Availability date <small style="color:var(--text-dim);font-weight:400">(optional — YYYY-MM-DD)</small></label>
                        <input type="text" name="dateDisponibilite" id="fDate" class="form-control"
                               value="<?= htmlspecialchars($produitUpdate['dateDisponibilite']??'') ?>" placeholder="YYYY-MM-DD">
                        <div class="field-error-msg" id="errDate">Invalid date. Use YYYY-MM-DD (e.g. 2025-12-31).</div>
                    </div>
                    <!-- DESCRIPTION -->
                    <div class="form-group form-col-full">
                        <label class="form-label">Description <span id="ctrDesc" class="char-counter">0/500</span></label>
                        <textarea name="description" id="fDesc" class="form-control" maxlength="500"><?= htmlspecialchars($produitUpdate['description']??'') ?></textarea>
                    </div>
                    <!-- TAGS -->
                    <div class="form-group form-col-full">
                        <label class="form-label">Characteristics / Tags <small style="color:var(--text-dim);font-weight:400">(comma-separated)</small> <span id="ctrTags" class="char-counter">0/300</span></label>
                        <input type="text" name="caracteristiques" id="fTags" class="form-control" maxlength="300"
                               value="<?= htmlspecialchars($produitUpdate['caracteristiques']??'') ?>"
                               placeholder="Bio, Premium, Vegan, Made in France…">
                        <div class="field-error-msg" id="errTags">Tags must not contain HTML.</div>
                    </div>
                    <!-- NOTE INTERNE -->
                    <div class="form-group form-col-full">
                        <label class="form-label">Internal note 🔒 Admin only</label>
                        <div class="note-interne-wrap">
                            <div class="note-interne-header">🔒 Visible to admin team only</div>
                            <textarea name="noteInterne" class="note-interne-ctrl"><?= htmlspecialchars($produitUpdate['noteInterne']??'') ?></textarea>
                        </div>
                    </div>
                    <!-- TOGGLES (edit only) -->
                    <?php if ($produitUpdate): ?>
                    <div class="form-group form-col-full">
                        <div class="toggle-row">
                            <div class="toggle-item">
                                <div><div style="font-size:12px;font-weight:500;">📌 Pin</div></div>
                                <label class="toggle-switch pin">
                                    <input type="checkbox" id="boToggleEpingle" <?= !empty($produitUpdate['estEpingle'])?'checked':'' ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            <div class="toggle-item">
                                <div><div style="font-size:12px;font-weight:500;">🗄️ Archive</div></div>
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
                        <label class="form-label">Image</label>
                        <?php if ($produitUpdate && !empty($produitUpdate['image'])): ?>
                        <div style="display:flex;align-items:center;gap:10px;background:var(--accent-soft);border-radius:var(--radius-sm);padding:8px 12px;margin-bottom:8px;">
                            <img src="<?= $baseUrl ?>/Vue/public/produits/<?= htmlspecialchars($produitUpdate['image']) ?>" style="width:48px;height:48px;object-fit:cover;border-radius:6px;">
                            <span style="font-size:12px;color:var(--text-muted);">Current image — upload to replace.</span>
                        </div>
                        <?php endif; ?>
                        <div class="upload-admin">
                            <input type="file" name="image" id="fileInputBO" accept="image/jpeg,image/png,image/webp">
                            <div class="upload-admin-text"><strong>Click to upload</strong> (JPG, PNG, WEBP — 2MB max)</div>
                        </div>
                        <div class="field-error-msg" id="errImage">File too large or invalid format (JPG, PNG, WEBP only, max 2MB).</div>
                        <img id="imgPreviewBO" class="upload-preview-img" src="" alt="">
                    </div>
                </div>

                <div class="form-actions-row">
                    <button type="submit" class="btn-primary" id="btnSubmitBO">
                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <?= $produitUpdate ? 'Save changes' : 'Add product' ?>
                    </button>
                    <a href="index.php" class="btn-ghost">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- FILTER BAR -->
    <div class="filter-bar">
        <div class="filter-group">
            <div class="filter-label">Sort by</div>
            <select class="filter-select" id="sortColSelect" onchange="applySortSelect()">
                <option value="">Default</option>
                <option value="nom-asc">Name A→Z</option>
                <option value="nom-desc">Name Z→A</option>
                <option value="prix-asc">Price ↑</option>
                <option value="prix-desc">Price ↓</option>
            </select>
        </div>
        <div class="filter-group">
            <div class="filter-label">Price (<?= DEVISE ?>)</div>
            <div class="price-range-wrap">
                <input type="text" class="price-input" id="priceMin" placeholder="Min">
                <span class="price-sep">–</span>
                <input type="text" class="price-input" id="priceMax" placeholder="Max">
            </div>
        </div>
        <div class="filter-group">
            <div class="filter-label">Category</div>
            <select class="filter-select" id="catFilterSelect" onchange="filterTable()">
                <option value="">All</option>
                <?php foreach ($categoriesDispos as $cat): ?>
                <option value="<?= htmlspecialchars(strtolower($cat)) ?>"><?= htmlspecialchars($cat) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn-reset-filter" onclick="resetFilters()">Reset</button>
    </div>

    <!-- TABS -->
    <div class="tab-bar">
        <button class="tab-btn active" id="tab-actifs" onclick="switchTab('actifs',this)">
            Active <span class="tab-pill"><?= $totalProduits ?></span>
        </button>
        <button class="tab-btn" id="tab-archives-btn" onclick="switchTab('archives',this)">
            Archived <span class="tab-pill archive"><?= $totalArchives ?></span>
        </button>
    </div>

    <!-- ACTIVE TAB -->
    <div class="tab-content active" id="content-actifs">
        <div class="table-panel">
            <div class="table-panel-header">
                <span class="table-panel-title">Product list</span>
                <span class="count-badge" id="visibleCount"><?= $totalProduits ?></span>
            </div>
            <?php if (empty($liste)): ?>
            <div class="empty-state"><div class="empty-icon">📦</div><p>No products found.</p></div>
            <?php else: ?>
            <table id="produitsTable">
                <thead>
                    <tr>
                        <th class="no-sort" style="width:46px">Img</th>
                        <th onclick="sortTable('id')" data-col="id">#ID</th>
                        <th onclick="sortTable('nom')" data-col="nom">Name</th>
                        <th class="no-sort">Category</th>
                        <th class="no-sort">Description</th>
                        <th onclick="sortTable('prix')" data-col="prix">Price</th>
                        <th class="no-sort">Status</th>
                        <th class="no-sort">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                <?php foreach ($liste as $p):
                    $isPinned = !empty($p['estEpingle']);
                    $dispo    = $p['dateDisponibilite']??null;
                    $dispoFut = $dispo && strtotime($dispo)>time();
                ?>
                <tr data-id="<?= $p['idProduit'] ?>"
                    data-nom="<?= htmlspecialchars(strtolower($p['nomProduit'])) ?>"
                    data-prix="<?= (float)$p['prix'] ?>"
                    data-cat="<?= htmlspecialchars(strtolower($p['categorie']??'')) ?>"
                    data-epingle="<?= (int)$isPinned ?>">
                    <td class="td-img"><div class="td-img-thumb">
                        <?php if (!empty($p['image'])): ?>
                            <img src="<?= $baseUrl ?>/Vue/public/produits/<?= htmlspecialchars($p['image']) ?>" alt="" onclick="openPreview(<?= $p['idProduit'] ?>)">
                            <div class="td-img-status ok">✓</div>
                        <?php else: ?>
                            <div class="td-img-empty">📦</div>
                            <div class="td-img-status miss">✕</div>
                        <?php endif; ?>
                    </div></td>
                    <td class="td-id">#<?= $p['idProduit'] ?></td>
                    <td class="td-name"><?= htmlspecialchars($p['nomProduit']) ?></td>
                    <td><?php if(!empty($p['categorie'])): ?><span class="cat-badge"><?= htmlspecialchars($p['categorie']) ?></span><?php endif; ?></td>
                    <td class="td-desc" title="<?= htmlspecialchars($p['description']??'') ?>"><?= htmlspecialchars($p['description']??'') ?></td>
                    <td><span class="prix-badge"><?= number_format((float)$p['prix'],2) ?> <?= DEVISE ?></span></td>
                    <td>
                        <?php if($isPinned): ?><span class="status-badge pinned">📌 Pinned</span><?php endif; ?>
                        <?php if($dispo): ?><span class="status-badge <?= $dispoFut?'future':'available' ?>"><?= $dispoFut?'⏳ Future':'✅ Live' ?></span><?php endif; ?>
                    </td>
                    <td>
                        <div class="action-group">
                            <button class="btn-action btn-view" onclick="openPreview(<?= $p['idProduit'] ?>)">👁 View</button>
                            <a href="?edit=<?= $p['idProduit'] ?>" class="btn-action btn-edit-a">✏️ Edit</a>
                            <button class="btn-action btn-pin" onclick="ajaxToggle('epingle',<?= $p['idProduit'] ?>,'<?= $isPinned?'Unpin':'Pin' ?>')"><?= $isPinned?'📌 Unpin':'📌 Pin' ?></button>
                            <button class="btn-action btn-archive" onclick="ajaxToggle('archive',<?= $p['idProduit'] ?>,'Archive')">🗄️</button>
                            <button class="btn-action btn-delete" onclick="openDeleteModal(<?= $p['idProduit'] ?>,'<?= htmlspecialchars(addslashes($p['nomProduit'])) ?>')">🗑</button>
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
                <span class="table-panel-title">Archived products</span>
                <span class="count-badge" style="background:rgba(100,116,139,.15);color:#64748b;"><?= $totalArchives ?></span>
            </div>
            <?php if (empty($listeArchives)): ?>
            <div class="empty-state"><div class="empty-icon">🗄️</div><p>No archived products.</p></div>
            <?php else: ?>
            <table>
                <thead><tr><th class="no-sort">Image</th><th class="no-sort">Name</th><th class="no-sort">Category</th><th class="no-sort">Price</th><th class="no-sort">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($listeArchives as $a): ?>
                <tr>
                    <td class="td-img"><div class="td-img-thumb"><?php if(!empty($a['image'])): ?><img src="<?= $baseUrl ?>/Vue/public/produits/<?= htmlspecialchars($a['image']) ?>" alt=""><?php else: ?><div class="td-img-empty">📦</div><?php endif; ?></div></td>
                    <td class="td-name" style="opacity:.7"><?= htmlspecialchars($a['nomProduit']) ?></td>
                    <td><?php if(!empty($a['categorie'])): ?><span class="cat-badge" style="opacity:.7"><?= htmlspecialchars($a['categorie']) ?></span><?php endif; ?></td>
                    <td><span class="prix-badge" style="opacity:.7"><?= number_format((float)$a['prix'],2) ?> <?= DEVISE ?></span></td>
                    <td>
                        <div class="action-group">
                            <button class="btn-action btn-restore" onclick="ajaxToggle('archive',<?= $a['idProduit'] ?>,'Restore')">♻️ Restore</button>
                            <a href="?edit=<?= $a['idProduit'] ?>" class="btn-action btn-edit-a">✏️ Edit</a>
                            <button class="btn-action btn-delete" onclick="openDeleteModal(<?= $a['idProduit'] ?>,'<?= htmlspecialchars(addslashes($a['nomProduit'])) ?>')">🗑</button>
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

<!-- PREVIEW MODAL -->
<div class="preview-modal-overlay" id="previewModal">
    <div class="preview-modal-box">
        <div id="previewImgWrap"></div>
        <div class="preview-modal-body">
            <div id="previewTitle" class="preview-modal-title"></div>
            <div id="previewPrice" style="font-size:20px;font-weight:700;color:var(--success);margin-bottom:10px;"></div>
            <div class="preview-section-label">Description</div>
            <div id="previewDesc" class="preview-text"></div>
            <div class="preview-section-label">Tags</div>
            <div id="previewTags" class="preview-text"></div>
            <div style="display:flex;align-items:center;margin-top:18px;padding-top:14px;border-top:1px solid var(--border);">
                <span style="font-size:12px;color:var(--text-dim)">Product ID: <span id="previewId"></span></span>
                <button class="preview-close-btn" onclick="closePreview()">✕ Close</button>
            </div>
        </div>
    </div>
</div>

<!-- DELETE MODAL -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal-box">
        <div class="modal-title">🗑 Confirm deletion</div>
        <div class="modal-text" id="deleteModalText"></div>
        <div class="modal-actions">
            <button class="btn-modal-cancel" id="btnCancelDelete">Cancel</button>
            <a href="#" class="btn-modal-confirm" id="confirmDeleteBtn">Delete</a>
        </div>
    </div>
</div>

<script>
const BASE_URL='<?= $baseUrl ?>';
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
(function initCharts(){
    const catLabels=<?= json_encode(array_keys($top5cats)) ?>;
    const catVals  =<?= json_encode(array_values($top5cats)) ?>;
    const baseColors=['rgba(139,92,246,.6)','rgba(16,185,129,.6)','rgba(245,158,11,.6)','rgba(59,130,246,.6)','rgba(239,68,68,.6)'];
    const opt={responsive:true,maintainAspectRatio:false,plugins:{legend:{labels:{color:'#7c8ba1',font:{size:11}}}}};
    new Chart(document.getElementById('chartCategories'),{type:'bar',data:{labels:catLabels,datasets:[{label:'Products',data:catVals,backgroundColor:baseColors,borderColor:baseColors.map(c=>c.replace('.6','.9')),borderWidth:2,borderRadius:6}]},options:{...opt,plugins:{legend:{display:false}},scales:{x:{ticks:{color:'#7c8ba1'},grid:{color:'rgba(255,255,255,.04)'}},y:{ticks:{color:'#7c8ba1'},grid:{color:'rgba(255,255,255,.04)'}}}}});
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
})();

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
    // Name
    if(fNom){const v=fNom.value.trim();if(v.length<2||/<[^>]+>/.test(v)){setFC(fNom,false);showFieldError('errNom','Min 2 chars, no HTML.');ok=false;}}
    // Price
    if(fPrix&&!isValidPrice(fPrix.value)){setFC(fPrix,false);showFieldError('errPrix','Valid price required.');ok=false;}
    // Date
    if(fDate&&fDate.value&&!isValidDate(fDate.value)){setFC(fDate,false);showFieldError('errDate','Invalid date format.');ok=false;}
    // Tags
    if(fTags&&/<[^>]+>/.test(fTags.value)){setFC(fTags,false);showFieldError('errTags','HTML not allowed in tags.');ok=false;}
    if(!ok){
        e.preventDefault();
        showToast('Please fix the form errors.','error');
        document.querySelector('.is-invalid')?.scrollIntoView({behavior:'smooth',block:'center'});
    }
});

// Init counters
if(fNom) updateCounter('fNom','ctrNom');
if(fDesc) updateCounter('fDesc','ctrDesc');
if(fTags) updateCounter('fTags','ctrTags');

/* ─── TABS ───────────────────────────────────────────────────────── */
function switchTab(name, btn) {
    ['actifs','archives'].forEach(t=>{
        document.getElementById('tab-'+t+'-btn')?.classList.toggle('active',t===name);
        document.getElementById('content-'+t)?.classList.toggle('active',t===name);
    });
    // fix for actifs btn
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
    document.getElementById('paginationInfo').textContent=total===0?'No results':`${start+1}–${end} of ${total}`;
    const btns=document.getElementById('paginationButtons');
    btns.innerHTML='';
    if(pages<=1) return;
    const addBtn=(lbl,p,dis,act)=>{const b=document.createElement('button');b.className='page-btn'+(act?' active':'');b.textContent=lbl;b.disabled=dis;b.onclick=()=>renderPage(p);btns.appendChild(b);};
    addBtn('←',page-1,page===1,false);
    for(let i=1;i<=pages;i++){
        if(i===1||i===pages||Math.abs(i-page)<=1) addBtn(i,i,false,i===page);
        else if(Math.abs(i-page)===2){const d=document.createElement('span');d.textContent='…';d.style.cssText='padding:5px 4px;color:var(--text-dim);font-size:12px;';btns.appendChild(d);}
    }
    addBtn('→',page+1,page===pages,false);
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
produitsMap[<?= $p['idProduit'] ?>]={id:<?= $p['idProduit'] ?>,nom:<?= json_encode($p['nomProduit']) ?>,desc:<?= json_encode($p['description']??'') ?>,tags:<?= json_encode($p['caracteristiques']??'') ?>,prix:<?= (float)$p['prix'] ?>,img:<?= json_encode(!empty($p['image'])?$baseUrl.'/Vue/public/produits/'.$p['image']:null) ?>};
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
    document.getElementById('deleteModalText').textContent=`Delete "${name}"? Irreversible.`;
    document.getElementById('confirmDeleteBtn').href='index.php?delete='+id;
    document.getElementById('deleteModal').classList.add('open');
}
function closeDeleteModal(){document.getElementById('deleteModal').classList.remove('open');}
document.getElementById('btnCancelDelete').addEventListener('click',closeDeleteModal);
document.getElementById('deleteModal').addEventListener('click',e=>{if(e.target.id==='deleteModal')closeDeleteModal();});

document.addEventListener('keydown',e=>{if(e.key==='Escape'){closeDeleteModal();closePreview();}});

// Init pagination
document.addEventListener('DOMContentLoaded',()=>renderPage(1));
</script>
</body>
</html>