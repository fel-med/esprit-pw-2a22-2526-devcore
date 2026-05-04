<?php
/**
 * Vue/BackOffice/campagne/index.php
 * Rôle : ADMIN — supervision de toutes les campagnes + analyse IA
 */

require_once __DIR__ . '/../../../Controleur/campagneC.php';
require_once __DIR__ . '/../../../Controleur/produitC.php';
require_once __DIR__ . '/../../../Modele/campagne.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$campagneC = new CampagneC();
$produitC  = new ProduitC();
$baseUrl   = '/projet/Esprit-PW-2A22-2526-Devcore';

$message     = '';
$messageType = '';
$iaResult    = null;
$iaError     = '';

// ── SUPPRESSION ───────────────────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $campagneC->supprimerCampagne(intval($_GET['delete']));
    header('Location: index.php?deleted=1'); exit;
}

// ── AJAX : toggle archive ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'archive') {
    $campagneC->toggleArchive(intval($_POST['id']));
    echo json_encode(['ok' => true]); exit;
}

// ── AJAX : changer statut ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'statut') {
    $campagneC->changerStatut(intval($_POST['id']), $_POST['statut'] ?? '');
    echo json_encode(['ok' => true]); exit;
}

// ── AJAX : produits d'une campagne ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax_produits'])) {
    $idC  = intval($_GET['ajax_produits']);
    echo json_encode([
        'lies'   => $produitC->getProduitsByCampagne($idC),
        'dispos' => $produitC->getProduitsDisponiblesPourCampagne($idC),
    ]); exit;
}

// ── AJAX : lier / retirer produit ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'lier_produit') {
    $campagneC->ajouterProduitCampagne(intval($_POST['idCampagne']), intval($_POST['idProduit']));
    echo json_encode(['ok' => true]); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'retirer_produit') {
    $campagneC->retirerProduitCampagne(intval($_POST['idCampagne']), intval($_POST['idProduit']));
    echo json_encode(['ok' => true]); exit;
}

// ── IA : ANALYSE CAMPAGNE (ADMIN) ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'ia_analyser') {
    $id   = intval($_POST['id_campagne'] ?? 0);
    $camp = $campagneC->recupererCampagne($id);
    if ($camp) {
        $iaResult = $campagneC->analyserCampagneIA(
            $camp['titreCampagne'],
            $camp['description'] ?? '',
            floatval($camp['budget']),
            $camp['statut']
        );
        if (!$iaResult) $iaError = "Erreur IA. Réessayez.";
    } else {
        $iaError = "Campagne introuvable.";
    }
}

// ── AJOUTER / MODIFIER (admin peut aussi créer) ───────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $titre  = trim($_POST['titre'] ?? '');
    $budget = str_replace(',', '.', $_POST['budget'] ?? '');
    $errors = [];
    if (strlen($titre) < 2) $errors[] = "Titre requis (min 2 car.).";
    if (!is_numeric($budget) || floatval($budget) < 0) $errors[] = "Budget invalide.";
    if (empty($errors)) {
        $campagne = new Campagne(null, htmlspecialchars($titre), trim($_POST['description'] ?? ''),
            trim($_POST['dateDebut'] ?? '') ?: null, trim($_POST['dateFin'] ?? '') ?: null,
            floatval($budget), $_POST['statut'] ?? 'brouillon', null,
            trim($_POST['objectif'] ?? ''), 0);
        $campagneC->ajouterCampagne($campagne);
        header('Location: index.php?added=1'); exit;
    } else { $message = implode(' | ', $errors); $messageType = "error"; }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    $titre  = trim($_POST['titre'] ?? '');
    $budget = str_replace(',', '.', $_POST['budget'] ?? '');
    $errors = [];
    if (strlen($titre) < 2) $errors[] = "Titre requis (min 2 car.).";
    if (!is_numeric($budget) || floatval($budget) < 0) $errors[] = "Budget invalide.";
    if (empty($errors)) {
        $campagne = new Campagne(null, htmlspecialchars($titre), trim($_POST['description'] ?? ''),
            trim($_POST['dateDebut'] ?? '') ?: null, trim($_POST['dateFin'] ?? '') ?: null,
            floatval($budget), $_POST['statut'] ?? 'brouillon', null,
            trim($_POST['objectif'] ?? ''), intval($_POST['estArchive'] ?? 0));
        $campagneC->modifierCampagne($campagne, intval($_POST['id']));
        header('Location: index.php?updated=1'); exit;
    } else { $message = implode(' | ', $errors); $messageType = "error"; }
}

if (isset($_GET['added']))   { $message = "Campagne ajoutée.";   $messageType = "success"; }
if (isset($_GET['updated'])) { $message = "Campagne modifiée.";  $messageType = "info"; }
if (isset($_GET['deleted'])) { $message = "Campagne supprimée."; $messageType = "danger"; }

// ── DONNÉES ───────────────────────────────────────────────────────────────────
$liste         = $campagneC->afficherCampagnes();
$listeArchives = $campagneC->afficherCampagnesArchives();
$toutesCampagnes = $campagneC->afficherToutesCampagnes();
$statuts       = $campagneC->getStatuts();

$campagneUpdate = null;
if (isset($_GET['edit'])) {
    $campagneUpdate = $campagneC->recupererCampagne(intval($_GET['edit']));
}

$totalCampagnes = count($liste);
$totalArchives  = count($listeArchives);
$budgetTotal    = array_sum(array_column($liste, 'budget'));
$nbActives      = count(array_filter($liste, fn($c) => $c['statut'] === 'active'));
$nbBrouillons   = count(array_filter($liste, fn($c) => $c['statut'] === 'brouillon'));
$nbTerminees    = count(array_filter($liste, fn($c) => $c['statut'] === 'terminee'));

function statutLabel($s) { return match($s) { 'active'=>'✅ Active','terminee'=>'🏁 Terminée','annulee'=>'❌ Annulée',default=>'📝 Brouillon' }; }
function statutClass($s) { return match($s) { 'active'=>'badge-success','terminee'=>'badge-info','annulee'=>'badge-danger',default=>'badge-warning' }; }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gestion Campagnes — Admin · Cre8Connect</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Syne:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
:root{
    --bg:#0d0f14;--surface:#141720;--card:#1a1e2a;--hover:#202535;--border:#2a2f42;
    --accent:#6c63ff;--accent-soft:rgba(108,99,255,.15);--accent-hover:#8b84ff;
    --success:#22c55e;--success-soft:rgba(34,197,94,.15);
    --warn:#f59e0b;--warn-soft:rgba(245,158,11,.15);
    --danger:#ef4444;--danger-soft:rgba(239,68,68,.15);
    --info:#3b82f6;--info-soft:rgba(59,130,246,.15);
    --text:#eef0f8;--sub:#9097b8;--muted:#5a6080;
    --radius:12px;
}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Syne',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;}

.layout{display:flex;min-height:100vh;}
.sidebar{width:240px;background:var(--surface);border-right:1px solid var(--border);padding:24px 0;position:sticky;top:0;height:100vh;display:flex;flex-direction:column;overflow-y:auto;}
.sidebar-logo{padding:0 24px 24px;font-size:1.3rem;font-weight:800;color:var(--accent);border-bottom:1px solid var(--border);}
.sidebar-logo span{color:var(--text);}
.sidebar-nav{padding:16px 12px;flex:1;}
.nav-label{font-size:.65rem;font-weight:700;letter-spacing:2px;color:var(--muted);text-transform:uppercase;padding:0 12px;margin:16px 0 6px;}
.nav-item{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;color:var(--sub);text-decoration:none;font-size:.88rem;font-weight:600;transition:all .15s;margin-bottom:2px;}
.nav-item:hover,.nav-item.active{background:var(--accent-soft);color:var(--accent-hover);}
.main{flex:1;display:flex;flex-direction:column;}
.topbar{background:var(--surface);border-bottom:1px solid var(--border);padding:16px 32px;display:flex;align-items:center;justify-content:space-between;}
.topbar-title{font-size:1.1rem;font-weight:700;}
.content{padding:32px;flex:1;}

/* KPI */
.kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:16px;margin-bottom:28px;}
.kpi-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:18px;}
.kpi-label{font-size:.7rem;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:var(--muted);}
.kpi-value{font-size:1.9rem;font-weight:800;line-height:1;margin-top:6px;}
.kpi-card.total .kpi-value{color:var(--text);}
.kpi-card.active .kpi-value{color:var(--success);}
.kpi-card.draft  .kpi-value{color:var(--warn);}
.kpi-card.ended  .kpi-value{color:var(--info);}
.kpi-card.budget .kpi-value{color:var(--accent);font-size:1.4rem;}
.kpi-card.arch   .kpi-value{color:var(--danger);}

/* IA PANEL */
.ia-panel{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:22px;margin-bottom:24px;}
.ia-panel-header{display:flex;align-items:center;gap:10px;margin-bottom:16px;}
.ia-panel-header h2{font-size:1rem;font-weight:700;color:var(--accent);}
.ia-form-row{display:flex;align-items:flex-end;gap:12px;flex-wrap:wrap;}
.ia-form-group{display:flex;flex-direction:column;gap:5px;flex:1;min-width:200px;}
.ia-form-group label{font-size:.78rem;font-weight:700;color:var(--sub);}
.ia-select{padding:9px 14px;border:1px solid var(--border);border-radius:10px;font-family:inherit;font-size:.85rem;background:var(--surface);color:var(--text);cursor:pointer;outline:none;}
.ia-select:focus{border-color:var(--accent);}
.btn-ia{display:inline-flex;align-items:center;gap:7px;padding:9px 18px;border-radius:10px;font-family:inherit;font-size:.85rem;font-weight:700;cursor:pointer;border:none;background:var(--accent);color:#fff;transition:opacity .15s;}
.btn-ia:hover{opacity:.9;}
.ia-result{background:var(--surface);border:1.5px solid rgba(108,99,255,.2);border-radius:var(--radius);padding:20px;margin-top:16px;}
.ia-result-title{font-size:.95rem;font-weight:700;color:var(--accent);margin-bottom:14px;}
.ia-field{margin-bottom:12px;}
.ia-label{font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--muted);margin-bottom:3px;}
.ia-value{font-size:.88rem;line-height:1.6;color:var(--sub);}
.ia-value.big{font-size:1.1rem;font-weight:700;color:var(--accent);}
.pill-list{display:flex;flex-wrap:wrap;gap:6px;}
.pill{padding:5px 12px;border-radius:20px;font-size:.78rem;font-weight:700;}
.pill-g{background:var(--success-soft);color:var(--success);}
.pill-r{background:var(--danger-soft);color:var(--danger);}
.pill-w{background:var(--warn-soft);color:var(--warn);}
.pill-a{background:var(--accent-soft);color:var(--accent);}
.ia-error{background:var(--danger-soft);color:var(--danger);border-radius:10px;padding:10px 14px;font-size:.85rem;font-weight:600;margin-top:12px;}
.spinner{width:16px;height:16px;border:2.5px solid var(--accent-soft);border-top-color:var(--accent);border-radius:50%;animation:spin .6s linear infinite;}
@keyframes spin{to{transform:rotate(360deg)}}
.ia-loading{display:none;align-items:center;gap:10px;padding:10px 0;color:var(--accent);font-weight:600;font-size:.85rem;}
.ia-loading.show{display:flex;}

/* PANEL */
.panel{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);}
.panel-header{padding:18px 22px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;}
.panel-title{font-size:1rem;font-weight:700;}
.panel-meta{font-size:.8rem;color:var(--muted);}
.table-wrap{overflow-x:auto;}
table{width:100%;border-collapse:collapse;}
th{text-align:left;padding:11px 16px;font-size:.7rem;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:var(--muted);border-bottom:1px solid var(--border);}
td{padding:13px 16px;font-size:.85rem;border-bottom:1px solid var(--border);vertical-align:middle;}
tr:last-child td{border-bottom:none;}
tr:hover td{background:var(--hover);}

/* BADGE */
.badge{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:20px;font-size:.72rem;font-weight:700;letter-spacing:.5px;}
.badge-success{background:var(--success-soft);color:var(--success);}
.badge-warning{background:var(--warn-soft);color:var(--warn);}
.badge-danger{background:var(--danger-soft);color:var(--danger);}
.badge-info{background:var(--info-soft);color:var(--info);}

/* BUTTONS */
.btn{display:inline-flex;align-items:center;gap:5px;padding:5px 11px;border-radius:8px;font-family:inherit;font-size:.78rem;font-weight:700;cursor:pointer;border:none;text-decoration:none;transition:opacity .15s;}
.btn:hover{opacity:.8;}
.btn-edit{background:var(--accent-soft);color:var(--accent);}
.btn-delete{background:var(--danger-soft);color:var(--danger);}
.btn-archive{background:var(--warn-soft);color:var(--warn);}

/* SELECT STATUT */
.statut-select{background:transparent;border:1px solid var(--border);color:var(--text);font-family:inherit;font-size:.8rem;border-radius:8px;padding:4px 8px;cursor:pointer;}

/* CAMP TITLE */
.camp-title{font-weight:700;}
.camp-obj{font-size:.78rem;color:var(--muted);margin-top:2px;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.col-budget{color:var(--success);font-family:'DM Mono',monospace;font-weight:500;}

/* TABS */
.tabs{display:flex;gap:4px;margin-bottom:18px;border-bottom:1px solid var(--border);}
.tab-btn{background:none;border:none;padding:9px 16px;font-size:.85rem;font-weight:700;color:var(--sub);cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-1px;font-family:inherit;transition:color .15s;}
.tab-btn.active{color:var(--accent);border-bottom-color:var(--accent);}
.tab-panel{display:none;}.tab-panel.active{display:block;}

/* FORM */
.form-section{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:22px;margin-top:22px;}
.form-section-title{font-size:.95rem;font-weight:700;margin-bottom:18px;color:var(--accent);}
.edit-banner{display:flex;align-items:center;justify-content:space-between;background:var(--accent-soft);border:1px solid rgba(108,99,255,.2);border-radius:8px;padding:9px 14px;margin-bottom:16px;font-size:.85rem;}
.edit-banner a{color:var(--danger);text-decoration:none;font-weight:700;font-size:.78rem;}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
.form-grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;}
.form-group{display:flex;flex-direction:column;gap:4px;}
.form-group label{font-size:.78rem;font-weight:700;color:var(--sub);}
.form-input{padding:9px 12px;border:1px solid var(--border);border-radius:10px;font-family:inherit;font-size:.85rem;background:var(--surface);color:var(--text);outline:none;transition:border-color .15s;width:100%;}
.form-input:focus{border-color:var(--accent);}
textarea.form-input{resize:vertical;min-height:80px;}
.form-actions{display:flex;gap:10px;margin-top:20px;padding-top:16px;border-top:1px solid var(--border);}
.btn-submit{background:var(--accent);color:#fff;border:none;border-radius:10px;padding:9px 22px;font-size:.85rem;font-weight:700;cursor:pointer;font-family:inherit;}
.btn-cancel-form{background:var(--surface);color:var(--sub);border:1px solid var(--border);border-radius:10px;padding:9px 18px;font-size:.85rem;font-weight:500;cursor:pointer;text-decoration:none;font-family:inherit;}

/* EXPORT */
.btn-export{display:inline-flex;align-items:center;gap:6px;background:var(--surface);color:var(--sub);border:1px solid var(--border);border-radius:8px;padding:7px 13px;font-size:.78rem;font-weight:600;text-decoration:none;cursor:pointer;font-family:inherit;}

/* SEARCH */
.search-bar{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px;}
.search-input{padding:8px 12px;border:1px solid var(--border);border-radius:10px;font-family:inherit;font-size:.85rem;background:var(--surface);color:var(--text);outline:none;width:200px;}
.search-input:focus{border-color:var(--accent);}
.filter-select{padding:8px 12px;border:1px solid var(--border);border-radius:10px;font-family:inherit;font-size:.85rem;background:var(--surface);color:var(--text);cursor:pointer;outline:none;}

/* PRODUCTS PANEL */
.pp-overlay{position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:200;display:none;align-items:flex-start;justify-content:flex-end;}
.pp-overlay.open{display:flex;}
.pp-panel{width:440px;max-width:96vw;height:100vh;background:var(--card);border-left:1px solid var(--border);display:flex;flex-direction:column;overflow:hidden;animation:slideInR .22s ease;}
@keyframes slideInR{from{transform:translateX(40px);opacity:0;}to{transform:translateX(0);opacity:1;}}
.pp-header{padding:18px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;}
.pp-title{font-size:.95rem;font-weight:700;}
.pp-close{background:var(--surface);border:1px solid var(--border);border-radius:50%;width:28px;height:28px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--sub);font-size:14px;}
.pp-body{flex:1;overflow-y:auto;padding:16px 20px;display:flex;flex-direction:column;gap:18px;}
.pp-section-label{font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);margin-bottom:8px;}
.pp-list{display:flex;flex-direction:column;gap:8px;}
.pp-item{display:flex;align-items:center;gap:10px;background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:10px 12px;}
.pp-thumb{width:38px;height:38px;border-radius:6px;object-fit:cover;background:var(--hover);flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:16px;overflow:hidden;}
.pp-thumb img{width:100%;height:100%;object-fit:cover;}
.pp-info{flex:1;min-width:0;}
.pp-name{font-size:.85rem;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.pp-price{font-size:.78rem;color:var(--success);font-weight:700;margin-top:2px;}
.btn-pp-remove{background:var(--danger-soft);color:var(--danger);border:none;border-radius:8px;padding:4px 10px;font-size:.72rem;font-weight:700;cursor:pointer;font-family:inherit;flex-shrink:0;}
.pp-add-row{display:flex;gap:8px;}
.pp-select{flex:1;background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:8px 10px;font-size:.82rem;color:var(--text);font-family:inherit;outline:none;}
.btn-pp-add{background:var(--accent);color:#fff;border:none;border-radius:10px;padding:8px 14px;font-size:.82rem;font-weight:700;cursor:pointer;font-family:inherit;}
.pp-empty{text-align:center;padding:22px;color:var(--muted);font-size:.85rem;}

/* DELETE MODAL */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:300;display:none;align-items:center;justify-content:center;}
.modal-overlay.open{display:flex;}
.modal-box{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:28px;width:400px;max-width:94vw;animation:popIn .22s ease;}
@keyframes popIn{from{opacity:0;transform:scale(.93);}to{opacity:1;transform:scale(1);}}
.modal-title{font-size:1rem;font-weight:700;margin-bottom:8px;}
.modal-text{font-size:.875rem;color:var(--sub);margin-bottom:22px;line-height:1.6;}
.modal-actions{display:flex;gap:10px;justify-content:flex-end;}
.btn-modal-cancel{background:var(--surface);color:var(--sub);border:1px solid var(--border);border-radius:8px;padding:8px 16px;font-size:.85rem;cursor:pointer;font-family:inherit;}
.btn-modal-confirm{background:var(--danger);color:#fff;border:none;border-radius:8px;padding:8px 18px;font-size:.85rem;font-weight:700;cursor:pointer;font-family:inherit;}

/* PROD COUNT BADGE */
.prod-count-badge{display:inline-flex;align-items:center;gap:4px;background:var(--accent-soft);color:var(--accent);border-radius:20px;padding:2px 8px;font-size:.72rem;font-weight:700;cursor:pointer;border:none;font-family:inherit;}

/* ALERT */
.alert{display:flex;align-items:center;gap:9px;padding:12px 16px;border-radius:10px;font-size:.85rem;font-weight:600;margin-bottom:18px;border:1px solid transparent;}
.alert-success{background:var(--success-soft);color:var(--success);}
.alert-info{background:var(--info-soft);color:var(--info);}
.alert-danger{background:var(--danger-soft);color:var(--danger);}
.alert-error{background:var(--danger-soft);color:var(--danger);}
</style>
</head>
<body>
<div class="layout">

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="sidebar-logo">Cre8<span>Connect</span></div>
    <nav class="sidebar-nav">
        <div class="nav-label">Tableau de bord</div>
        <a href="#" class="nav-item">📊 Aperçu</a>
        <div class="nav-label">Modules</div>
        <a href="#" class="nav-item">👥 Utilisateurs</a>
        <a href="#" class="nav-item">📣 Offres</a>
        <a href="index.php" class="nav-item active">⚡ Campagnes</a>
        <a href="../produit/index.php" class="nav-item">📦 Produits</a>
        <a href="../contrat/index.php" class="nav-item">📄 Contrats</a>
        <a href="#" class="nav-item">📅 Événements</a>
        <a href="#" class="nav-item">📰 Posts</a>
        <a href="#" class="nav-item">🚩 Réclamations</a>
    </nav>
</aside>

<!-- MAIN -->
<div class="main">
<header class="topbar">
    <span class="topbar-title">⚡ Gestion des Campagnes</span>
    <div style="display:flex;align-items:center;gap:10px;">
        <a href="?export_csv=1" class="btn-export">📤 Export CSV</a>
        <span style="color:var(--sub);font-size:.85rem;">Admin</span>
    </div>
</header>

<main class="content">

    <!-- ALERT -->
    <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>" id="alertMsg"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <!-- KPI -->
    <div class="kpi-grid">
        <div class="kpi-card total"><div class="kpi-label">Total actives</div><div class="kpi-value"><?= $totalCampagnes ?></div></div>
        <div class="kpi-card active"><div class="kpi-label">Actives</div><div class="kpi-value"><?= $nbActives ?></div></div>
        <div class="kpi-card draft"><div class="kpi-label">Brouillons</div><div class="kpi-value"><?= $nbBrouillons ?></div></div>
        <div class="kpi-card ended"><div class="kpi-label">Terminées</div><div class="kpi-value"><?= $nbTerminees ?></div></div>
        <div class="kpi-card budget"><div class="kpi-label">Budget total</div><div class="kpi-value"><?= number_format($budgetTotal, 0, ',', ' ') ?> €</div></div>
        <div class="kpi-card arch"><div class="kpi-label">Archivées</div><div class="kpi-value"><?= $totalArchives ?></div></div>
    </div>

    <!-- IA ANALYSE -->
    <div class="ia-panel">
        <div class="ia-panel-header">
            <span style="font-size:20px;">🧠</span>
            <h2>Analyser une campagne avec l'IA</h2>
        </div>
        <form method="POST" id="iaForm">
            <input type="hidden" name="action" value="ia_analyser">
            <div class="ia-form-row">
                <div class="ia-form-group">
                    <label>Sélectionner une campagne</label>
                    <select name="id_campagne" class="ia-select" required>
                        <option value="">— Choisir —</option>
                        <?php foreach ($toutesCampagnes as $c): ?>
                        <option value="<?= $c['idCampagne'] ?>" <?= (isset($_POST['id_campagne']) && intval($_POST['id_campagne']) === (int)$c['idCampagne']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['titreCampagne']) ?> (<?= $c['statut'] ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-ia" onclick="document.getElementById('iaLoading').classList.add('show')">
                    🧠 Analyser
                </button>
            </div>
        </form>
        <div class="ia-loading" id="iaLoading"><div class="spinner"></div> Analyse IA en cours…</div>
        <?php if ($iaError): ?><div class="ia-error">⚠️ <?= htmlspecialchars($iaError) ?></div><?php endif; ?>
        <?php if ($iaResult): ?>
        <div class="ia-result">
            <div class="ia-result-title">📊 Résultat de l'analyse</div>
            <?php if (!empty($iaResult['score_qualite'])): ?><div class="ia-field"><div class="ia-label">Score qualité</div><div class="ia-value big">⭐ <?= htmlspecialchars($iaResult['score_qualite']) ?> / 10</div></div><?php endif; ?>
            <?php if (!empty($iaResult['points_forts'])): ?><div class="ia-field"><div class="ia-label">✅ Points forts</div><div class="pill-list"><?php foreach ($iaResult['points_forts'] as $p): ?><span class="pill pill-g"><?= htmlspecialchars($p) ?></span><?php endforeach; ?></div></div><?php endif; ?>
            <?php if (!empty($iaResult['points_faibles'])): ?><div class="ia-field"><div class="ia-label">⚠️ Points faibles</div><div class="pill-list"><?php foreach ($iaResult['points_faibles'] as $p): ?><span class="pill pill-w"><?= htmlspecialchars($p) ?></span><?php endforeach; ?></div></div><?php endif; ?>
            <?php if (!empty($iaResult['risques'])): ?><div class="ia-field"><div class="ia-label">🚨 Risques</div><div class="pill-list"><?php foreach ($iaResult['risques'] as $r): ?><span class="pill pill-r"><?= htmlspecialchars($r) ?></span><?php endforeach; ?></div></div><?php endif; ?>
            <?php if (!empty($iaResult['recommandations'])): ?><div class="ia-field"><div class="ia-label">💡 Recommandations</div><div class="pill-list"><?php foreach ($iaResult['recommandations'] as $r): ?><span class="pill pill-a"><?= htmlspecialchars($r) ?></span><?php endforeach; ?></div></div><?php endif; ?>
            <?php if (!empty($iaResult['budget_adequat'])): ?><div class="ia-field"><div class="ia-label">💰 Budget</div><div class="ia-value"><?= htmlspecialchars($iaResult['budget_adequat']) ?></div></div><?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- TABS -->
    <div class="tabs">
        <button class="tab-btn active" onclick="switchTab('active',this)">Actives (<?= $totalCampagnes ?>)</button>
        <button class="tab-btn" onclick="switchTab('archived',this)">Archivées (<?= $totalArchives ?>)</button>
    </div>

    <!-- TAB ACTIVE -->
    <div class="tab-panel active" id="tab-active">
        <div class="search-bar">
            <input type="text" id="searchInput" class="search-input" placeholder="Rechercher…">
            <select id="filterStatut" class="filter-select" onchange="filterTable()">
                <option value="">Tous statuts</option>
                <?php foreach ($statuts as $s): ?><option value="<?= $s ?>"><?= ucfirst($s) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="panel">
            <div class="panel-header">
                <div class="panel-title">Toutes les campagnes</div>
                <div class="panel-meta" id="visibleBadge"><?= $totalCampagnes ?> campagne(s)</div>
            </div>
            <div class="table-wrap">
                <?php if (empty($liste)): ?>
                <div style="text-align:center;padding:40px;color:var(--muted);">Aucune campagne.</div>
                <?php else: ?>
                <table id="campTable">
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Statut</th>
                            <th>Dates</th>
                            <th>Budget</th>
                            <th>Marque</th>
                            <th>Produits</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="campBody">
                    <?php foreach ($liste as $c):
                        $nbProd = $campagneC->compterProduitsCampagne($c['idCampagne']);
                        $today  = date('Y-m-d');
                        $expired = $c['dateFin'] && $c['dateFin'] < $today && $c['statut'] === 'active';
                    ?>
                    <tr data-statut="<?= $c['statut'] ?>"
                        data-titre="<?= strtolower(htmlspecialchars($c['titreCampagne'])) ?>">
                        <td>
                            <div class="camp-title"><?= htmlspecialchars($c['titreCampagne']) ?></div>
                            <div class="camp-obj"><?= htmlspecialchars($c['objectif'] ?? '—') ?></div>
                            <?php if ($expired): ?><div style="font-size:.72rem;color:var(--danger);font-weight:700;margin-top:3px;">⚠ Expirée</div><?php endif; ?>
                        </td>
                        <td>
                            <select class="statut-select" onchange="changeStatut(<?= $c['idCampagne'] ?>,this.value)">
                                <?php foreach ($statuts as $s): ?>
                                <option value="<?= $s ?>" <?= $c['statut'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td style="font-size:.78rem;color:var(--sub);">
                            📅 <?= $c['dateDebut'] ?? '—' ?><br>🏁 <?= $c['dateFin'] ?? '—' ?>
                        </td>
                        <td class="col-budget"><?= number_format((float)$c['budget'], 2, ',', ' ') ?> €</td>
                        <td style="font-size:.82rem;color:var(--sub);"><?= htmlspecialchars($c['nomMarque'] ?? '—') ?></td>
                        <td>
                            <button class="prod-count-badge" onclick="openPP(<?= $c['idCampagne'] ?>,'<?= htmlspecialchars(addslashes($c['titreCampagne'])) ?>')">
                                📦 <?= $nbProd ?> produit<?= $nbProd !== 1 ? 's' : '' ?>
                            </button>
                        </td>
                        <td>
                            <a href="?edit=<?= $c['idCampagne'] ?>#formAnchor" class="btn btn-edit">✏️</a>
                            <button class="btn btn-archive" onclick="ajaxArchive(<?= $c['idCampagne'] ?>)">📦</button>
                            <button class="btn btn-delete" onclick="confirmDelete(<?= $c['idCampagne'] ?>,'<?= htmlspecialchars(addslashes($c['titreCampagne'])) ?>')">🗑</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- TAB ARCHIVÉES -->
    <div class="tab-panel" id="tab-archived">
        <div class="panel">
            <div class="panel-header"><div class="panel-title">Campagnes archivées</div><div class="panel-meta"><?= $totalArchives ?></div></div>
            <div class="table-wrap">
                <?php if (empty($listeArchives)): ?>
                <div style="text-align:center;padding:40px;color:var(--muted);">Aucune campagne archivée.</div>
                <?php else: ?>
                <table>
                    <thead><tr><th>Titre</th><th>Statut</th><th>Budget</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($listeArchives as $c): ?>
                    <tr>
                        <td class="camp-title" style="opacity:.7"><?= htmlspecialchars($c['titreCampagne']) ?></td>
                        <td><span class="badge <?= statutClass($c['statut']) ?>"><?= statutLabel($c['statut']) ?></span></td>
                        <td class="col-budget"><?= number_format((float)$c['budget'], 2, ',', ' ') ?> €</td>
                        <td>
                            <button class="btn btn-edit" onclick="ajaxArchive(<?= $c['idCampagne'] ?>)" style="background:var(--info-soft);color:var(--info);">🔁 Restaurer</button>
                            <button class="btn btn-delete" onclick="confirmDelete(<?= $c['idCampagne'] ?>,'<?= htmlspecialchars(addslashes($c['titreCampagne'])) ?>')">🗑</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- FORM -->
    <div class="form-section" id="formAnchor">
        <div class="form-section-title"><?= $campagneUpdate ? '✏️ Modifier la campagne' : '➕ Ajouter une campagne' ?></div>
        <?php if ($campagneUpdate): ?>
        <div class="edit-banner">
            <span>Modification : <strong><?= htmlspecialchars($campagneUpdate['titreCampagne']) ?></strong></span>
            <a href="index.php">✕ Annuler</a>
        </div>
        <?php endif; ?>
        <form method="POST" action="index.php" id="campagneForm">
            <input type="hidden" name="action" value="<?= $campagneUpdate ? 'update' : 'add' ?>">
            <?php if ($campagneUpdate): ?>
            <input type="hidden" name="id" value="<?= $campagneUpdate['idCampagne'] ?>">
            <input type="hidden" name="estArchive" value="<?= intval($campagneUpdate['estArchive'] ?? 0) ?>">
            <?php endif; ?>
            <div class="form-grid" style="margin-bottom:14px;">
                <div class="form-group">
                    <label>Titre *</label>
                    <input type="text" name="titre" class="form-input" maxlength="100" required
                           value="<?= $campagneUpdate ? htmlspecialchars($campagneUpdate['titreCampagne']) : '' ?>">
                </div>
                <div class="form-group">
                    <label>Statut</label>
                    <select name="statut" class="form-input">
                        <?php foreach ($statuts as $s): ?>
                        <option value="<?= $s ?>" <?= ($campagneUpdate && $campagneUpdate['statut'] === $s) ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group" style="margin-bottom:14px;">
                <label>Description</label>
                <textarea name="description" class="form-input"><?= $campagneUpdate ? htmlspecialchars($campagneUpdate['description']) : '' ?></textarea>
            </div>
            <div class="form-group" style="margin-bottom:14px;">
                <label>Objectif</label>
                <input type="text" name="objectif" class="form-input" maxlength="200"
                       value="<?= $campagneUpdate ? htmlspecialchars($campagneUpdate['objectif'] ?? '') : '' ?>">
            </div>
            <div class="form-grid-3" style="margin-bottom:14px;">
                <div class="form-group">
                    <label>Date début</label>
                    <input type="text" name="dateDebut" class="form-input" placeholder="AAAA-MM-JJ"
                           value="<?= $campagneUpdate ? htmlspecialchars($campagneUpdate['dateDebut'] ?? '') : '' ?>">
                </div>
                <div class="form-group">
                    <label>Date fin</label>
                    <input type="text" name="dateFin" class="form-input" placeholder="AAAA-MM-JJ"
                           value="<?= $campagneUpdate ? htmlspecialchars($campagneUpdate['dateFin'] ?? '') : '' ?>">
                </div>
                <div class="form-group">
                    <label>Budget (€) *</label>
                    <input type="text" name="budget" class="form-input" required
                           value="<?= $campagneUpdate ? htmlspecialchars($campagneUpdate['budget'] ?? '') : '' ?>">
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-submit"><?= $campagneUpdate ? '💾 Enregistrer' : '✅ Ajouter' ?></button>
                <?php if ($campagneUpdate): ?><a href="index.php" class="btn-cancel-form">Annuler</a><?php endif; ?>
            </div>
        </form>
    </div>

</main>
</div>
</div>

<!-- PRODUCTS PANEL -->
<div class="pp-overlay" id="ppOverlay" onclick="closePPOutside(event)">
    <div class="pp-panel">
        <div class="pp-header">
            <div class="pp-title" id="ppTitle">Produits</div>
            <button class="pp-close" onclick="closePP()">✕</button>
        </div>
        <div class="pp-body" id="ppBody"><div class="pp-empty">⏳ Chargement…</div></div>
    </div>
</div>

<!-- DELETE MODAL -->
<div class="modal-overlay" id="confirmModal">
    <div class="modal-box">
        <div class="modal-title" id="modalTitle">Confirmer la suppression</div>
        <div class="modal-text" id="modalText"></div>
        <div class="modal-actions">
            <button class="btn-modal-cancel" onclick="closeModal()">Annuler</button>
            <a href="#" class="btn-modal-confirm" id="modalConfirmLink">Supprimer</a>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?= $baseUrl ?>';

const alertEl = document.getElementById('alertMsg');
if (alertEl) setTimeout(() => alertEl.style.display = 'none', 4500);

// ── TABS ──────────────────────────────────────────────────────────
function switchTab(name, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-'+name).classList.add('active');
    btn.classList.add('active');
}

// ── FILTER + SEARCH ────────────────────────────────────────────────
document.getElementById('searchInput').addEventListener('input', filterTable);
function filterTable() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    const s = document.getElementById('filterStatut').value;
    let v = 0;
    document.querySelectorAll('#campBody tr').forEach(row => {
        const mQ = !q || (row.dataset.titre||'').includes(q);
        const mS = !s || row.dataset.statut === s;
        row.style.display = mQ && mS ? '' : 'none';
        if (mQ && mS) v++;
    });
    document.getElementById('visibleBadge').textContent = v + ' campagne(s)';
}

// ── AJAX ───────────────────────────────────────────────────────────
function ajaxArchive(id) {
    fetch('index.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'action=archive&id='+id})
        .then(() => location.reload());
}
function changeStatut(id, statut) {
    fetch('index.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'action=statut&id='+id+'&statut='+encodeURIComponent(statut)});
}

// ── DELETE MODAL ───────────────────────────────────────────────────
function confirmDelete(id, titre) {
    document.getElementById('modalText').textContent = `Supprimer "${titre}" ? Action irréversible.`;
    document.getElementById('modalConfirmLink').href = 'index.php?delete='+id;
    document.getElementById('confirmModal').classList.add('open');
}
function closeModal() { document.getElementById('confirmModal').classList.remove('open'); }
document.getElementById('confirmModal').addEventListener('click', e => { if (e.target.id === 'confirmModal') closeModal(); });

// ── PRODUCTS PANEL ─────────────────────────────────────────────────
function openPP(id, titre) {
    document.getElementById('ppTitle').textContent = '📦 ' + titre;
    document.getElementById('ppBody').innerHTML = '<div class="pp-empty">⏳ Chargement…</div>';
    document.getElementById('ppOverlay').classList.add('open');
    loadPP(id);
}
function closePP() { document.getElementById('ppOverlay').classList.remove('open'); }
function closePPOutside(e) { if (e.target.id === 'ppOverlay') closePP(); }
function loadPP(id) {
    fetch('index.php?ajax_produits='+id).then(r=>r.json()).then(d => renderPP(id, d.lies, d.dispos));
}
function renderPP(id, lies, dispos) {
    let html = '';
    html += `<div><div class="pp-section-label">Produits liés (${lies.length})</div><div class="pp-list">`;
    if (!lies.length) html += '<div class="pp-empty">Aucun produit lié.</div>';
    else lies.forEach(p => {
        const img = p.image ? `<img src="${BASE_URL}/Vue/public/produits/${p.image}" alt="">` : '📦';
        html += `<div class="pp-item"><div class="pp-thumb">${img}</div><div class="pp-info"><div class="pp-name">${esc(p.nomProduit)}</div><div class="pp-price">${parseFloat(p.prix).toFixed(2)} €</div></div><button class="btn-pp-remove" onclick="retirerPP(${id},${p.idProduit})">✕</button></div>`;
    });
    html += '</div></div>';
    html += `<div><div class="pp-section-label">Ajouter</div>`;
    if (!dispos.length) html += '<div class="pp-empty">Tous les produits sont déjà liés.</div>';
    else {
        html += '<div class="pp-add-row"><select class="pp-select" id="ppSelect"><option value="">— Sélectionner —</option>';
        dispos.forEach(p => { html += `<option value="${p.idProduit}">${esc(p.nomProduit)} — ${parseFloat(p.prix).toFixed(2)} €</option>`; });
        html += `</select><button class="btn-pp-add" onclick="ajouterPP(${id})">+ Lier</button></div>`;
    }
    html += '</div>';
    document.getElementById('ppBody').innerHTML = html;
}
function ajouterPP(id) {
    const sel = document.getElementById('ppSelect');
    if (!sel?.value) return;
    fetch('index.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:`action=lier_produit&idCampagne=${id}&idProduit=${sel.value}`})
        .then(r=>r.json()).then(d => { if(d.ok) loadPP(id); });
}
function retirerPP(id, idP) {
    if (!confirm('Retirer ce produit ?')) return;
    fetch('index.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:`action=retirer_produit&idCampagne=${id}&idProduit=${idP}`})
        .then(r=>r.json()).then(d => { if(d.ok) loadPP(id); });
}
function esc(s) { const d = document.createElement('div'); d.textContent = s||''; return d.innerHTML; }

document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeModal(); closePP(); } });
<?php if ($campagneUpdate): ?>
document.addEventListener('DOMContentLoaded', () => document.getElementById('formAnchor').scrollIntoView({behavior:'smooth',block:'start'}));
<?php endif; ?>
</script>
</body>
</html>