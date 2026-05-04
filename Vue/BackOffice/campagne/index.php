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
<!-- ===== ADDED FEATURE: CHART.JS CDN (Statistics) ===== -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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

/* ===== ADDED FEATURE: LIGHT/DARK MODE ===== */
body.light-mode {
    --bg:#f4f5f9;--surface:#ffffff;--card:#ffffff;--hover:#f0f1f6;--border:#e2e4ed;
    --accent:#6c63ff;--accent-soft:rgba(108,99,255,.10);--accent-hover:#5b53e6;
    --success:#16a34a;--success-soft:rgba(22,163,74,.10);
    --warn:#d97706;--warn-soft:rgba(217,119,6,.10);
    --danger:#dc2626;--danger-soft:rgba(220,38,38,.10);
    --info:#2563eb;--info-soft:rgba(37,99,235,.10);
    --text:#12151f;--sub:#4b5280;--muted:#8892b0;
}
/* ===== END ADDED FEATURE ===== */

*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Syne',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;transition:background .25s,color .25s;}

.layout{display:flex;min-height:100vh;}
.sidebar{width:240px;background:var(--surface);border-right:1px solid var(--border);padding:24px 0;position:sticky;top:0;height:100vh;display:flex;flex-direction:column;overflow-y:auto;transition:background .25s,border-color .25s;}
.sidebar-logo{padding:0 24px 24px;font-size:1.3rem;font-weight:800;color:var(--accent);border-bottom:1px solid var(--border);}
.sidebar-logo span{color:var(--text);}
.sidebar-nav{padding:16px 12px;flex:1;}
.nav-label{font-size:.65rem;font-weight:700;letter-spacing:2px;color:var(--muted);text-transform:uppercase;padding:0 12px;margin:16px 0 6px;}
.nav-item{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;color:var(--sub);text-decoration:none;font-size:.88rem;font-weight:600;transition:all .15s;margin-bottom:2px;}
.nav-item:hover,.nav-item.active{background:var(--accent-soft);color:var(--accent-hover);}
.main{flex:1;display:flex;flex-direction:column;}
.topbar{background:var(--surface);border-bottom:1px solid var(--border);padding:16px 32px;display:flex;align-items:center;justify-content:space-between;transition:background .25s,border-color .25s;}
.topbar-title{font-size:1.1rem;font-weight:700;}
.content{padding:32px;flex:1;}

/* KPI */
.kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:16px;margin-bottom:28px;}
.kpi-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:18px;transition:background .25s,border-color .25s;}
.kpi-label{font-size:.7rem;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:var(--muted);}
.kpi-value{font-size:1.9rem;font-weight:800;line-height:1;margin-top:6px;}
.kpi-card.total .kpi-value{color:var(--text);}
.kpi-card.active .kpi-value{color:var(--success);}
.kpi-card.draft  .kpi-value{color:var(--warn);}
.kpi-card.ended  .kpi-value{color:var(--info);}
.kpi-card.budget .kpi-value{color:var(--accent);font-size:1.4rem;}
.kpi-card.arch   .kpi-value{color:var(--danger);}

/* IA PANEL */
.ia-panel{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:22px;margin-bottom:24px;transition:background .25s,border-color .25s;}
.ia-panel-header{display:flex;align-items:center;gap:10px;margin-bottom:16px;}
.ia-panel-header h2{font-size:1rem;font-weight:700;color:var(--accent);}
.ia-form-row{display:flex;align-items:flex-end;gap:12px;flex-wrap:wrap;}
.ia-form-group{display:flex;flex-direction:column;gap:5px;flex:1;min-width:200px;}
.ia-form-group label{font-size:.78rem;font-weight:700;color:var(--sub);}
.ia-select{padding:9px 14px;border:1px solid var(--border);border-radius:10px;font-family:inherit;font-size:.85rem;background:var(--surface);color:var(--text);cursor:pointer;outline:none;transition:background .25s,border-color .25s,color .25s;}
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
.panel{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);transition:background .25s,border-color .25s;}
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
.statut-select{background:var(--surface);border:1px solid var(--border);color:var(--text);font-family:inherit;font-size:.8rem;border-radius:8px;padding:4px 8px;cursor:pointer;transition:background .25s,border-color .25s,color .25s;}

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
.form-section{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:22px;margin-top:22px;transition:background .25s,border-color .25s;}
.form-section-title{font-size:.95rem;font-weight:700;margin-bottom:18px;color:var(--accent);}
.edit-banner{display:flex;align-items:center;justify-content:space-between;background:var(--accent-soft);border:1px solid rgba(108,99,255,.2);border-radius:8px;padding:9px 14px;margin-bottom:16px;font-size:.85rem;}
.edit-banner a{color:var(--danger);text-decoration:none;font-weight:700;font-size:.78rem;}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
.form-grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;}
.form-group{display:flex;flex-direction:column;gap:4px;}
.form-group label{font-size:.78rem;font-weight:700;color:var(--sub);}
.form-input{padding:9px 12px;border:1px solid var(--border);border-radius:10px;font-family:inherit;font-size:.85rem;background:var(--surface);color:var(--text);outline:none;transition:border-color .15s,background .25s,color .25s;width:100%;}
.form-input:focus{border-color:var(--accent);}
textarea.form-input{resize:vertical;min-height:80px;}
.form-actions{display:flex;gap:10px;margin-top:20px;padding-top:16px;border-top:1px solid var(--border);}
.btn-submit{background:var(--accent);color:#fff;border:none;border-radius:10px;padding:9px 22px;font-size:.85rem;font-weight:700;cursor:pointer;font-family:inherit;}
.btn-cancel-form{background:var(--surface);color:var(--sub);border:1px solid var(--border);border-radius:10px;padding:9px 18px;font-size:.85rem;font-weight:500;cursor:pointer;text-decoration:none;font-family:inherit;}

/* EXPORT */
.btn-export{display:inline-flex;align-items:center;gap:6px;background:var(--surface);color:var(--sub);border:1px solid var(--border);border-radius:8px;padding:7px 13px;font-size:.78rem;font-weight:600;text-decoration:none;cursor:pointer;font-family:inherit;}

/* SEARCH */
.search-bar{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px;}
.search-input{padding:8px 12px;border:1px solid var(--border);border-radius:10px;font-family:inherit;font-size:.85rem;background:var(--surface);color:var(--text);outline:none;width:200px;transition:background .25s,border-color .25s,color .25s;}
.search-input:focus{border-color:var(--accent);}
.filter-select{padding:8px 12px;border:1px solid var(--border);border-radius:10px;font-family:inherit;font-size:.85rem;background:var(--surface);color:var(--text);cursor:pointer;outline:none;transition:background .25s,border-color .25s,color .25s;}

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

/* ===== ADDED FEATURE: LIGHT/DARK MODE TOGGLE BUTTON ===== */
.theme-toggle{background:var(--surface);border:1px solid var(--border);border-radius:20px;padding:6px 13px;font-size:.8rem;font-weight:700;cursor:pointer;color:var(--sub);font-family:inherit;display:inline-flex;align-items:center;gap:6px;transition:all .2s;}
.theme-toggle:hover{border-color:var(--accent);color:var(--accent);}
/* ===== END ADDED FEATURE ===== */

/* ===== ADDED FEATURE: LANGUAGE SWITCHER ===== */
.lang-switcher{display:inline-flex;gap:4px;}
.lang-btn{background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:5px 10px;font-size:.75rem;font-weight:700;cursor:pointer;color:var(--sub);font-family:inherit;transition:all .2s;}
.lang-btn:hover,.lang-btn.active{background:var(--accent-soft);color:var(--accent);border-color:var(--accent);}
/* ===== END ADDED FEATURE ===== */

/* ===== ADDED FEATURE: PAGINATION ===== */
.pagination{display:flex;align-items:center;justify-content:center;gap:6px;padding:16px 0 4px;}
.page-btn{background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:6px 12px;font-size:.8rem;font-weight:700;cursor:pointer;color:var(--sub);font-family:inherit;transition:all .15s;}
.page-btn:hover:not(:disabled){background:var(--accent-soft);color:var(--accent);border-color:var(--accent);}
.page-btn:disabled{opacity:.35;cursor:not-allowed;}
.page-btn.current{background:var(--accent);color:#fff;border-color:var(--accent);}
.page-info{font-size:.78rem;color:var(--muted);font-weight:600;padding:0 6px;}
.per-page-select{background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:5px 8px;font-size:.78rem;color:var(--text);font-family:inherit;cursor:pointer;outline:none;}
/* ===== END ADDED FEATURE ===== */

/* ===== ADDED FEATURE: STATISTICS SECTION ===== */
.stats-section{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:22px;margin-bottom:24px;transition:background .25s,border-color .25s;}
.stats-section-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:10px;}
.stats-section-title{font-size:1rem;font-weight:700;color:var(--accent);display:flex;align-items:center;gap:8px;}
.stats-charts-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:20px;}
.chart-card{background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:16px;transition:background .25s,border-color .25s;}
.chart-card-title{font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--muted);margin-bottom:12px;}
.chart-container{position:relative;height:180px;}
.stats-toggle-btn{background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:5px 12px;font-size:.75rem;font-weight:700;cursor:pointer;color:var(--sub);font-family:inherit;transition:all .2s;}
.stats-toggle-btn:hover{border-color:var(--accent);color:var(--accent);}
/* ===== END ADDED FEATURE ===== */
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
    <span class="topbar-title" data-i18n="pageTitle">⚡ Gestion des Campagnes</span>
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <!-- ===== ADDED FEATURE: LANGUAGE SWITCHER ===== -->
        <div class="lang-switcher">
            <button class="lang-btn active" id="langFR" onclick="setLang('fr')">🇫🇷 FR</button>
            <button class="lang-btn" id="langEN" onclick="setLang('en')">🇬🇧 EN</button>
        </div>
        <!-- ===== END ADDED FEATURE ===== -->

        <!-- ===== ADDED FEATURE: DARK/LIGHT MODE TOGGLE ===== -->
        <button class="theme-toggle" id="themeToggle" onclick="toggleTheme()">🌙 <span id="themeLabel">Mode clair</span></button>
        <!-- ===== END ADDED FEATURE ===== -->

        <a href="?export_csv=1" class="btn-export" data-i18n="exportCsv">📤 Export CSV</a>
        <span style="color:var(--sub);font-size:.85rem;" data-i18n="adminLabel">Admin</span>
    </div>
</header>

<main class="content">

    <!-- ALERT -->
    <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>" id="alertMsg"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <!-- KPI -->
    <div class="kpi-grid">
        <div class="kpi-card total"><div class="kpi-label" data-i18n="kpiTotal">Total actives</div><div class="kpi-value"><?= $totalCampagnes ?></div></div>
        <div class="kpi-card active"><div class="kpi-label" data-i18n="kpiActive">Actives</div><div class="kpi-value"><?= $nbActives ?></div></div>
        <div class="kpi-card draft"><div class="kpi-label" data-i18n="kpiDraft">Brouillons</div><div class="kpi-value"><?= $nbBrouillons ?></div></div>
        <div class="kpi-card ended"><div class="kpi-label" data-i18n="kpiEnded">Terminées</div><div class="kpi-value"><?= $nbTerminees ?></div></div>
        <div class="kpi-card budget"><div class="kpi-label" data-i18n="kpiBudget">Budget total</div><div class="kpi-value"><?= number_format($budgetTotal, 0, ',', ' ') ?> €</div></div>
        <div class="kpi-card arch"><div class="kpi-label" data-i18n="kpiArchived">Archivées</div><div class="kpi-value"><?= $totalArchives ?></div></div>
    </div>

    <!-- ===== ADDED FEATURE: DYNAMIC STATISTICS SECTION (Admin BackOffice only) ===== -->
    <div class="stats-section" id="statsSection">
        <div class="stats-section-header">
            <div class="stats-section-title">📊 <span data-i18n="statsTitle">Statistiques dynamiques</span></div>
            <button class="stats-toggle-btn" onclick="toggleStats()" id="statsToggleBtn" data-i18n="statsHide">▲ Masquer</button>
        </div>
        <div id="statsBody">
            <div class="stats-charts-grid">
                <div class="chart-card">
                    <div class="chart-card-title" data-i18n="chartStatusTitle">Répartition par statut</div>
                    <div class="chart-container">
                        <canvas id="chartStatut"></canvas>
                    </div>
                </div>
                <div class="chart-card">
                    <div class="chart-card-title" data-i18n="chartActiveArchiveTitle">Actives vs Archivées</div>
                    <div class="chart-container">
                        <canvas id="chartActiveArchive"></canvas>
                    </div>
                </div>
                <div class="chart-card">
                    <div class="chart-card-title" data-i18n="chartBudgetTitle">Budget par statut (€)</div>
                    <div class="chart-container">
                        <canvas id="chartBudget"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- ===== END ADDED FEATURE ===== -->

    <!-- IA ANALYSE -->
    <div class="ia-panel">
        <div class="ia-panel-header">
            <span style="font-size:20px;">🧠</span>
            <h2 data-i18n="iaTitle">Analyser une campagne avec l'IA</h2>
        </div>
        <form method="POST" id="iaForm">
            <input type="hidden" name="action" value="ia_analyser">
            <div class="ia-form-row">
                <div class="ia-form-group">
                    <label data-i18n="iaSelectLabel">Sélectionner une campagne</label>
                    <select name="id_campagne" class="ia-select" required>
                        <option value="" data-i18n="iaSelectPlaceholder">— Choisir —</option>
                        <?php foreach ($toutesCampagnes as $c): ?>
                        <option value="<?= $c['idCampagne'] ?>" <?= (isset($_POST['id_campagne']) && intval($_POST['id_campagne']) === (int)$c['idCampagne']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['titreCampagne']) ?> (<?= $c['statut'] ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-ia" onclick="document.getElementById('iaLoading').classList.add('show')">
                    🧠 <span data-i18n="iaAnalyzeBtn">Analyser</span>
                </button>
            </div>
        </form>
        <div class="ia-loading" id="iaLoading"><div class="spinner"></div> <span data-i18n="iaLoading">Analyse IA en cours…</span></div>
        <?php if ($iaError): ?><div class="ia-error">⚠️ <?= htmlspecialchars($iaError) ?></div><?php endif; ?>
        <?php if ($iaResult): ?>
        <div class="ia-result">
            <div class="ia-result-title" data-i18n="iaResultTitle">📊 Résultat de l'analyse</div>
            <?php if (!empty($iaResult['score_qualite'])): ?><div class="ia-field"><div class="ia-label" data-i18n="iaScore">Score qualité</div><div class="ia-value big">⭐ <?= htmlspecialchars($iaResult['score_qualite']) ?> / 10</div></div><?php endif; ?>
            <?php if (!empty($iaResult['points_forts'])): ?><div class="ia-field"><div class="ia-label" data-i18n="iaStrengths">✅ Points forts</div><div class="pill-list"><?php foreach ($iaResult['points_forts'] as $p): ?><span class="pill pill-g"><?= htmlspecialchars($p) ?></span><?php endforeach; ?></div></div><?php endif; ?>
            <?php if (!empty($iaResult['points_faibles'])): ?><div class="ia-field"><div class="ia-label" data-i18n="iaWeaknesses">⚠️ Points faibles</div><div class="pill-list"><?php foreach ($iaResult['points_faibles'] as $p): ?><span class="pill pill-w"><?= htmlspecialchars($p) ?></span><?php endforeach; ?></div></div><?php endif; ?>
            <?php if (!empty($iaResult['risques'])): ?><div class="ia-field"><div class="ia-label" data-i18n="iaRisks">🚨 Risques</div><div class="pill-list"><?php foreach ($iaResult['risques'] as $r): ?><span class="pill pill-r"><?= htmlspecialchars($r) ?></span><?php endforeach; ?></div></div><?php endif; ?>
            <?php if (!empty($iaResult['recommandations'])): ?><div class="ia-field"><div class="ia-label" data-i18n="iaRecos">💡 Recommandations</div><div class="pill-list"><?php foreach ($iaResult['recommandations'] as $r): ?><span class="pill pill-a"><?= htmlspecialchars($r) ?></span><?php endforeach; ?></div></div><?php endif; ?>
            <?php if (!empty($iaResult['budget_adequat'])): ?><div class="ia-field"><div class="ia-label" data-i18n="iaBudget">💰 Budget</div><div class="ia-value"><?= htmlspecialchars($iaResult['budget_adequat']) ?></div></div><?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- TABS -->
    <div class="tabs">
        <button class="tab-btn active" onclick="switchTab('active',this)" data-i18n-template="tabActive" data-i18n-count="<?= $totalCampagnes ?>">Actives (<?= $totalCampagnes ?>)</button>
        <button class="tab-btn" onclick="switchTab('archived',this)" data-i18n-template="tabArchived" data-i18n-count="<?= $totalArchives ?>">Archivées (<?= $totalArchives ?>)</button>
    </div>

    <!-- TAB ACTIVE -->
    <div class="tab-panel active" id="tab-active">
        <div class="search-bar">
            <input type="text" id="searchInput" class="search-input" placeholder="Rechercher…" data-i18n-placeholder="searchPlaceholder">
            <select id="filterStatut" class="filter-select" onchange="filterAndPaginate()">
                <option value="" data-i18n="filterAll">Tous statuts</option>
                <?php foreach ($statuts as $s): ?><option value="<?= $s ?>"><?= ucfirst($s) ?></option><?php endforeach; ?>
            </select>
            <!-- ===== ADDED FEATURE: PAGINATION per-page selector ===== -->
            <select class="per-page-select" id="perPageSelect" onchange="changePerPage()">
                <option value="5">5 / page</option>
                <option value="10" selected>10 / page</option>
                <option value="20">20 / page</option>
                <option value="50">50 / page</option>
            </select>
            <!-- ===== END ADDED FEATURE ===== -->
        </div>
        <div class="panel">
            <div class="panel-header">
                <div class="panel-title" data-i18n="panelTitle">Toutes les campagnes</div>
                <div class="panel-meta" id="visibleBadge"><?= $totalCampagnes ?> <span data-i18n="campaignCount">campagne(s)</span></div>
            </div>
            <div class="table-wrap">
                <?php if (empty($liste)): ?>
                <div style="text-align:center;padding:40px;color:var(--muted);" data-i18n="noCampaign">Aucune campagne.</div>
                <?php else: ?>
                <table id="campTable">
                    <thead>
                        <tr>
                            <th data-i18n="colTitle">Titre</th>
                            <th data-i18n="colStatus">Statut</th>
                            <th data-i18n="colDates">Dates</th>
                            <th data-i18n="colBudget">Budget</th>
                            <th data-i18n="colBrand">Marque</th>
                            <th data-i18n="colProducts">Produits</th>
                            <th data-i18n="colActions">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="campBody">
                    <?php foreach ($liste as $c):
                        $nbProd = $campagneC->compterProduitsCampagne($c['idCampagne']);
                        $today  = date('Y-m-d');
                        $expired = $c['dateFin'] && $c['dateFin'] < $today && $c['statut'] === 'active';
                    ?>
                    <tr data-statut="<?= $c['statut'] ?>"
                        data-titre="<?= strtolower(htmlspecialchars($c['titreCampagne'])) ?>"
                        data-budget="<?= $c['budget'] ?>">
                        <td>
                            <div class="camp-title"><?= htmlspecialchars($c['titreCampagne']) ?></div>
                            <div class="camp-obj"><?= htmlspecialchars($c['objectif'] ?? '—') ?></div>
                            <?php if ($expired): ?><div style="font-size:.72rem;color:var(--danger);font-weight:700;margin-top:3px;" data-i18n="expired">⚠ Expirée</div><?php endif; ?>
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
                                📦 <?= $nbProd ?> <span data-i18n="productCount"><?= $nbProd !== 1 ? 'produits' : 'produit' ?></span>
                            </button>
                        </td>
                        <td>
                            <a href="?edit=<?= $c['idCampagne'] ?>#formAnchor" class="btn btn-edit" title="Modifier">✏️</a>
                            <button class="btn btn-archive" onclick="ajaxArchive(<?= $c['idCampagne'] ?>)" title="Archiver">📦</button>
                            <button class="btn btn-delete" onclick="confirmDelete(<?= $c['idCampagne'] ?>,'<?= htmlspecialchars(addslashes($c['titreCampagne'])) ?>')" title="Supprimer">🗑</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <!-- ===== ADDED FEATURE: PAGINATION CONTROLS ===== -->
                <div class="pagination" id="paginationControls"></div>
                <!-- ===== END ADDED FEATURE ===== -->
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- TAB ARCHIVÉES -->
    <div class="tab-panel" id="tab-archived">
        <div class="panel">
            <div class="panel-header">
                <div class="panel-title" data-i18n="panelArchivedTitle">Campagnes archivées</div>
                <div class="panel-meta"><?= $totalArchives ?></div>
            </div>
            <div class="table-wrap">
                <?php if (empty($listeArchives)): ?>
                <div style="text-align:center;padding:40px;color:var(--muted);" data-i18n="noArchived">Aucune campagne archivée.</div>
                <?php else: ?>
                <table>
                    <thead><tr>
                        <th data-i18n="colTitle">Titre</th>
                        <th data-i18n="colStatus">Statut</th>
                        <th data-i18n="colBudget">Budget</th>
                        <th data-i18n="colActions">Actions</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($listeArchives as $c): ?>
                    <tr>
                        <td class="camp-title" style="opacity:.7"><?= htmlspecialchars($c['titreCampagne']) ?></td>
                        <td><span class="badge <?= statutClass($c['statut']) ?>"><?= statutLabel($c['statut']) ?></span></td>
                        <td class="col-budget"><?= number_format((float)$c['budget'], 2, ',', ' ') ?> €</td>
                        <td>
                            <button class="btn btn-edit" onclick="ajaxArchive(<?= $c['idCampagne'] ?>)" style="background:var(--info-soft);color:var(--info);" data-i18n="restoreBtn">🔁 Restaurer</button>
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
        <div class="form-section-title"><?= $campagneUpdate ? '✏️ <span data-i18n="formEditTitle">Modifier la campagne</span>' : '➕ <span data-i18n="formAddTitle">Ajouter une campagne</span>' ?></div>
        <?php if ($campagneUpdate): ?>
        <div class="edit-banner">
            <span><span data-i18n="editBannerLabel">Modification :</span> <strong><?= htmlspecialchars($campagneUpdate['titreCampagne']) ?></strong></span>
            <a href="index.php" data-i18n="cancelEdit">✕ Annuler</a>
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
                    <label data-i18n="labelTitle">Titre *</label>
                    <input type="text" name="titre" class="form-input" maxlength="100" required
                           value="<?= $campagneUpdate ? htmlspecialchars($campagneUpdate['titreCampagne']) : '' ?>">
                </div>
                <div class="form-group">
                    <label data-i18n="labelStatus">Statut</label>
                    <select name="statut" class="form-input">
                        <?php foreach ($statuts as $s): ?>
                        <option value="<?= $s ?>" <?= ($campagneUpdate && $campagneUpdate['statut'] === $s) ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group" style="margin-bottom:14px;">
                <label data-i18n="labelDesc">Description</label>
                <textarea name="description" class="form-input"><?= $campagneUpdate ? htmlspecialchars($campagneUpdate['description']) : '' ?></textarea>
            </div>
            <div class="form-group" style="margin-bottom:14px;">
                <label data-i18n="labelObjective">Objectif</label>
                <input type="text" name="objectif" class="form-input" maxlength="200"
                       value="<?= $campagneUpdate ? htmlspecialchars($campagneUpdate['objectif'] ?? '') : '' ?>">
            </div>
            <div class="form-grid-3" style="margin-bottom:14px;">
                <div class="form-group">
                    <label data-i18n="labelStart">Date début</label>
                    <input type="text" name="dateDebut" class="form-input" placeholder="AAAA-MM-JJ"
                           value="<?= $campagneUpdate ? htmlspecialchars($campagneUpdate['dateDebut'] ?? '') : '' ?>">
                </div>
                <div class="form-group">
                    <label data-i18n="labelEnd">Date fin</label>
                    <input type="text" name="dateFin" class="form-input" placeholder="AAAA-MM-JJ"
                           value="<?= $campagneUpdate ? htmlspecialchars($campagneUpdate['dateFin'] ?? '') : '' ?>">
                </div>
                <div class="form-group">
                    <label data-i18n="labelBudget">Budget (€) *</label>
                    <input type="text" name="budget" class="form-input" required
                           value="<?= $campagneUpdate ? htmlspecialchars($campagneUpdate['budget'] ?? '') : '' ?>">
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-submit" data-i18n="<?= $campagneUpdate ? 'btnSave' : 'btnAdd' ?>"><?= $campagneUpdate ? '💾 Enregistrer' : '✅ Ajouter' ?></button>
                <?php if ($campagneUpdate): ?><a href="index.php" class="btn-cancel-form" data-i18n="btnCancel">Annuler</a><?php endif; ?>
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
        <div class="modal-title" id="modalTitle" data-i18n="modalTitle">Confirmer la suppression</div>
        <div class="modal-text" id="modalText"></div>
        <div class="modal-actions">
            <button class="btn-modal-cancel" onclick="closeModal()" data-i18n="modalCancel">Annuler</button>
            <a href="#" class="btn-modal-confirm" id="modalConfirmLink" data-i18n="modalConfirm">Supprimer</a>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?= $baseUrl ?>';

// ── ORIGINAL: Alert auto-hide ─────────────────────────────────────
const alertEl = document.getElementById('alertMsg');
if (alertEl) setTimeout(() => alertEl.style.display = 'none', 4500);

// ── ORIGINAL: TABS ────────────────────────────────────────────────
function switchTab(name, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-'+name).classList.add('active');
    btn.classList.add('active');
}

// ── ORIGINAL: AJAX ────────────────────────────────────────────────
function ajaxArchive(id) {
    fetch('index.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'action=archive&id='+id})
        .then(() => location.reload());
}
function changeStatut(id, statut) {
    fetch('index.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'action=statut&id='+id+'&statut='+encodeURIComponent(statut)});
}

// ── ORIGINAL: DELETE MODAL ────────────────────────────────────────
function confirmDelete(id, titre) {
    const t = currentLang === 'fr' ? `Supprimer "${titre}" ? Action irréversible.` : `Delete "${titre}"? This action is irreversible.`;
    document.getElementById('modalText').textContent = t;
    document.getElementById('modalConfirmLink').href = 'index.php?delete='+id;
    document.getElementById('confirmModal').classList.add('open');
}
function closeModal() { document.getElementById('confirmModal').classList.remove('open'); }
document.getElementById('confirmModal').addEventListener('click', e => { if (e.target.id === 'confirmModal') closeModal(); });

// ── ORIGINAL: PRODUCTS PANEL ──────────────────────────────────────
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

// ═══════════════════════════════════════════════════════════
// ===== ADDED FEATURE: TRANSLATION SYSTEM =====
// ═══════════════════════════════════════════════════════════
const translations = {
    fr: {
        pageTitle: '⚡ Gestion des Campagnes',
        exportCsv: '📤 Export CSV',
        adminLabel: 'Admin',
        kpiTotal: 'Total actives',
        kpiActive: 'Actives',
        kpiDraft: 'Brouillons',
        kpiEnded: 'Terminées',
        kpiBudget: 'Budget total',
        kpiArchived: 'Archivées',
        statsTitle: 'Statistiques dynamiques',
        statsHide: '▲ Masquer',
        statsShow: '▼ Afficher',
        chartStatusTitle: 'Répartition par statut',
        chartActiveArchiveTitle: 'Actives vs Archivées',
        chartBudgetTitle: 'Budget par statut (€)',
        iaTitle: "Analyser une campagne avec l'IA",
        iaSelectLabel: 'Sélectionner une campagne',
        iaSelectPlaceholder: '— Choisir —',
        iaAnalyzeBtn: 'Analyser',
        iaLoading: 'Analyse IA en cours…',
        iaResultTitle: "📊 Résultat de l'analyse",
        iaScore: 'Score qualité',
        iaStrengths: '✅ Points forts',
        iaWeaknesses: '⚠️ Points faibles',
        iaRisks: '🚨 Risques',
        iaRecos: '💡 Recommandations',
        iaBudget: '💰 Budget',
        tabActive: 'Actives',
        tabArchived: 'Archivées',
        filterAll: 'Tous statuts',
        searchPlaceholder: 'Rechercher…',
        panelTitle: 'Toutes les campagnes',
        campaignCount: 'campagne(s)',
        noCampaign: 'Aucune campagne.',
        noArchived: 'Aucune campagne archivée.',
        colTitle: 'Titre',
        colStatus: 'Statut',
        colDates: 'Dates',
        colBudget: 'Budget',
        colBrand: 'Marque',
        colProducts: 'Produits',
        colActions: 'Actions',
        expired: '⚠ Expirée',
        productCount: 'produit(s)',
        panelArchivedTitle: 'Campagnes archivées',
        restoreBtn: '🔁 Restaurer',
        formEditTitle: 'Modifier la campagne',
        formAddTitle: 'Ajouter une campagne',
        editBannerLabel: 'Modification :',
        cancelEdit: '✕ Annuler',
        labelTitle: 'Titre *',
        labelStatus: 'Statut',
        labelDesc: 'Description',
        labelObjective: 'Objectif',
        labelStart: 'Date début',
        labelEnd: 'Date fin',
        labelBudget: 'Budget (€) *',
        btnSave: '💾 Enregistrer',
        btnAdd: '✅ Ajouter',
        btnCancel: 'Annuler',
        modalTitle: 'Confirmer la suppression',
        modalCancel: 'Annuler',
        modalConfirm: 'Supprimer',
        themeLabel: 'Mode clair',
        themeLabelDark: 'Mode sombre',
        prevPage: '← Préc.',
        nextPage: 'Suiv. →',
        pageOf: 'Page',
        of: 'sur',
    },
    en: {
        pageTitle: '⚡ Campaign Management',
        exportCsv: '📤 Export CSV',
        adminLabel: 'Admin',
        kpiTotal: 'Total active',
        kpiActive: 'Active',
        kpiDraft: 'Drafts',
        kpiEnded: 'Ended',
        kpiBudget: 'Total budget',
        kpiArchived: 'Archived',
        statsTitle: 'Dynamic Statistics',
        statsHide: '▲ Hide',
        statsShow: '▼ Show',
        chartStatusTitle: 'Distribution by status',
        chartActiveArchiveTitle: 'Active vs Archived',
        chartBudgetTitle: 'Budget by status (€)',
        iaTitle: 'Analyze a campaign with AI',
        iaSelectLabel: 'Select a campaign',
        iaSelectPlaceholder: '— Choose —',
        iaAnalyzeBtn: 'Analyze',
        iaLoading: 'AI analysis in progress…',
        iaResultTitle: '📊 Analysis result',
        iaScore: 'Quality score',
        iaStrengths: '✅ Strengths',
        iaWeaknesses: '⚠️ Weaknesses',
        iaRisks: '🚨 Risks',
        iaRecos: '💡 Recommendations',
        iaBudget: '💰 Budget',
        tabActive: 'Active',
        tabArchived: 'Archived',
        filterAll: 'All statuses',
        searchPlaceholder: 'Search…',
        panelTitle: 'All campaigns',
        campaignCount: 'campaign(s)',
        noCampaign: 'No campaigns.',
        noArchived: 'No archived campaigns.',
        colTitle: 'Title',
        colStatus: 'Status',
        colDates: 'Dates',
        colBudget: 'Budget',
        colBrand: 'Brand',
        colProducts: 'Products',
        colActions: 'Actions',
        expired: '⚠ Expired',
        productCount: 'product(s)',
        panelArchivedTitle: 'Archived campaigns',
        restoreBtn: '🔁 Restore',
        formEditTitle: 'Edit campaign',
        formAddTitle: 'Add a campaign',
        editBannerLabel: 'Editing:',
        cancelEdit: '✕ Cancel',
        labelTitle: 'Title *',
        labelStatus: 'Status',
        labelDesc: 'Description',
        labelObjective: 'Objective',
        labelStart: 'Start date',
        labelEnd: 'End date',
        labelBudget: 'Budget (€) *',
        btnSave: '💾 Save',
        btnAdd: '✅ Add',
        btnCancel: 'Cancel',
        modalTitle: 'Confirm deletion',
        modalCancel: 'Cancel',
        modalConfirm: 'Delete',
        themeLabel: 'Light mode',
        themeLabelDark: 'Dark mode',
        prevPage: '← Prev',
        nextPage: 'Next →',
        pageOf: 'Page',
        of: 'of',
    }
};

let currentLang = localStorage.getItem('cre8_lang') || 'fr';

function setLang(lang) {
    currentLang = lang;
    localStorage.setItem('cre8_lang', lang);
    applyTranslations();
    document.getElementById('langFR').classList.toggle('active', lang === 'fr');
    document.getElementById('langEN').classList.toggle('active', lang === 'en');
    // Update tab labels with counts
    updateTabLabels();
    // Refresh pagination labels
    renderPagination();
}

function applyTranslations() {
    const T = translations[currentLang];
    // Elements with data-i18n
    document.querySelectorAll('[data-i18n]').forEach(el => {
        const key = el.getAttribute('data-i18n');
        if (T[key] !== undefined) el.textContent = T[key];
    });
    // Placeholder translations
    document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
        const key = el.getAttribute('data-i18n-placeholder');
        if (T[key] !== undefined) el.setAttribute('placeholder', T[key]);
    });
    // Theme label
    const isDark = !document.body.classList.contains('light-mode');
    document.getElementById('themeLabel').textContent = isDark ? T.themeLabel : T.themeLabelDark;
    // Stats toggle
    const statsVisible = document.getElementById('statsBody').style.display !== 'none';
    document.getElementById('statsToggleBtn').textContent = statsVisible ? T.statsHide : T.statsShow;
}

function updateTabLabels() {
    const T = translations[currentLang];
    document.querySelectorAll('[data-i18n-template]').forEach(el => {
        const key = el.getAttribute('data-i18n-template');
        const count = el.getAttribute('data-i18n-count');
        if (T[key] !== undefined) el.textContent = `${T[key]} (${count})`;
    });
}
// ===== END ADDED FEATURE: TRANSLATION SYSTEM =====

// ═══════════════════════════════════════════════════════════
// ===== ADDED FEATURE: LIGHT/DARK MODE TOGGLE =====
// ═══════════════════════════════════════════════════════════
function toggleTheme() {
    const isLight = document.body.classList.toggle('light-mode');
    localStorage.setItem('cre8_theme', isLight ? 'light' : 'dark');
    const T = translations[currentLang];
    document.getElementById('themeLabel').textContent = isLight ? T.themeLabelDark : T.themeLabel;
    document.getElementById('themeToggle').innerHTML = isLight
        ? '☀️ <span id="themeLabel">' + (T.themeLabelDark || 'Dark mode') + '</span>'
        : '🌙 <span id="themeLabel">' + (T.themeLabel || 'Light mode') + '</span>';
    // Redraw charts for new theme colors
    buildCharts();
}

function initTheme() {
    // BackOffice default = dark. Only switch if user explicitly chose light.
    const saved = localStorage.getItem('cre8_theme');
    if (saved === 'light') {
        document.body.classList.add('light-mode');
        const T = translations[currentLang];
        document.getElementById('themeToggle').innerHTML = '☀️ <span id="themeLabel">' + (T.themeLabelDark || 'Dark mode') + '</span>';
    }
}
// ===== END ADDED FEATURE =====

// ═══════════════════════════════════════════════════════════
// ===== ADDED FEATURE: PAGINATION =====
// ═══════════════════════════════════════════════════════════
let currentPage = 1;
let rowsPerPage  = 10;
let filteredRows = [];

function getAllRows() {
    return Array.from(document.querySelectorAll('#campBody tr'));
}

function filterAndPaginate() {
    const q = (document.getElementById('searchInput').value || '').toLowerCase();
    const s = document.getElementById('filterStatut').value;
    filteredRows = getAllRows().filter(row => {
        const mQ = !q || (row.dataset.titre || '').includes(q);
        const mS = !s || row.dataset.statut === s;
        return mQ && mS;
    });
    currentPage = 1;
    applyPagination();
}

function applyPagination() {
    const allRows = getAllRows();
    // Hide all first
    allRows.forEach(r => r.style.display = 'none');
    // Show only current page of filtered
    const start = (currentPage - 1) * rowsPerPage;
    const end   = start + rowsPerPage;
    filteredRows.slice(start, end).forEach(r => r.style.display = '');
    // Update badge
    const badge = document.getElementById('visibleBadge');
    if (badge) {
        const T = translations[currentLang];
        badge.innerHTML = filteredRows.length + ' <span data-i18n="campaignCount">' + (T.campaignCount || 'campagne(s)') + '</span>';
    }
    renderPagination();
}

function renderPagination() {
    const container = document.getElementById('paginationControls');
    if (!container) return;
    const T = translations[currentLang];
    const totalPages = Math.ceil(filteredRows.length / rowsPerPage) || 1;
    let html = '';
    // Prev
    html += `<button class="page-btn" onclick="goToPage(${currentPage-1})" ${currentPage <= 1 ? 'disabled' : ''}>${T.prevPage || '← Préc.'}</button>`;
    // Page numbers (show max 5 around current)
    const range = 2;
    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= currentPage - range && i <= currentPage + range)) {
            html += `<button class="page-btn${i === currentPage ? ' current' : ''}" onclick="goToPage(${i})">${i}</button>`;
        } else if (i === currentPage - range - 1 || i === currentPage + range + 1) {
            html += `<span class="page-info">…</span>`;
        }
    }
    // Next
    html += `<button class="page-btn" onclick="goToPage(${currentPage+1})" ${currentPage >= totalPages ? 'disabled' : ''}>${T.nextPage || 'Suiv. →'}</button>`;
    // Info
    html += `<span class="page-info">${T.pageOf || 'Page'} ${currentPage} ${T.of || 'sur'} ${totalPages}</span>`;
    container.innerHTML = html;
}

function goToPage(page) {
    const totalPages = Math.ceil(filteredRows.length / rowsPerPage) || 1;
    if (page < 1 || page > totalPages) return;
    currentPage = page;
    applyPagination();
}

function changePerPage() {
    rowsPerPage = parseInt(document.getElementById('perPageSelect').value) || 10;
    currentPage = 1;
    applyPagination();
}

// Hook search input to combined filter+paginate
document.addEventListener('DOMContentLoaded', () => {
    const si = document.getElementById('searchInput');
    if (si) si.addEventListener('input', filterAndPaginate);
    // Initialize
    filteredRows = getAllRows();
    applyPagination();
});
// ===== END ADDED FEATURE: PAGINATION =====

// ═══════════════════════════════════════════════════════════
// ===== ADDED FEATURE: DYNAMIC STATISTICS (Admin BackOffice) =====
// ═══════════════════════════════════════════════════════════

// Raw data injected from PHP for chart rendering
const campData = {
    active:    <?= $nbActives ?>,
    brouillon: <?= $nbBrouillons ?>,
    terminee:  <?= $nbTerminees ?>,
    annulee:   <?= count(array_filter($liste, fn($c) => $c['statut'] === 'annulee')) ?>,
    totalActive:   <?= $totalCampagnes ?>,
    totalArchived: <?= $totalArchives ?>,
    budgets: {
        active:    <?= array_sum(array_column(array_filter($liste, fn($c) => $c['statut'] === 'active'), 'budget')) ?>,
        brouillon: <?= array_sum(array_column(array_filter($liste, fn($c) => $c['statut'] === 'brouillon'), 'budget')) ?>,
        terminee:  <?= array_sum(array_column(array_filter($liste, fn($c) => $c['statut'] === 'terminee'), 'budget')) ?>,
        annulee:   <?= array_sum(array_column(array_filter($liste, fn($c) => $c['statut'] === 'annulee'), 'budget')) ?>,
    }
};

let chartStatut = null, chartActiveArchive = null, chartBudget = null;

function getChartColors() {
    const style = getComputedStyle(document.body);
    return {
        text:    style.getPropertyValue('--text').trim()    || '#eef0f8',
        sub:     style.getPropertyValue('--sub').trim()     || '#9097b8',
        border:  style.getPropertyValue('--border').trim()  || '#2a2f42',
        success: style.getPropertyValue('--success').trim() || '#22c55e',
        warn:    style.getPropertyValue('--warn').trim()    || '#f59e0b',
        info:    style.getPropertyValue('--info').trim()    || '#3b82f6',
        danger:  style.getPropertyValue('--danger').trim()  || '#ef4444',
        accent:  style.getPropertyValue('--accent').trim()  || '#6c63ff',
    };
}

function buildCharts() {
    const C = getChartColors();
    const gridColor = C.border;
    const textColor = C.text;

    const baseOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: { color: textColor, font: { family: "'Syne', sans-serif", size: 11 }, boxWidth: 12 }
            }
        }
    };

    // Destroy old charts before recreating (handles theme switch)
    [chartStatut, chartActiveArchive, chartBudget].forEach(ch => { if (ch) ch.destroy(); });

    // Chart 1: Doughnut — répartition par statut
    chartStatut = new Chart(document.getElementById('chartStatut'), {
        type: 'doughnut',
        data: {
            labels: ['Active', 'Brouillon', 'Terminée', 'Annulée'],
            datasets: [{
                data: [campData.active, campData.brouillon, campData.terminee, campData.annulee],
                backgroundColor: [C.success, C.warn, C.info, C.danger],
                borderColor: 'transparent',
                hoverOffset: 6
            }]
        },
        options: {
            ...baseOptions,
            cutout: '65%',
        }
    });

    // Chart 2: Bar — actives vs archivées
    chartActiveArchive = new Chart(document.getElementById('chartActiveArchive'), {
        type: 'bar',
        data: {
            labels: ['Actives', 'Archivées'],
            datasets: [{
                label: 'Campagnes',
                data: [campData.totalActive, campData.totalArchived],
                backgroundColor: [C.accent + 'cc', C.danger + 'cc'],
                borderColor: [C.accent, C.danger],
                borderWidth: 1.5,
                borderRadius: 6,
            }]
        },
        options: {
            ...baseOptions,
            scales: {
                x: { ticks: { color: C.sub }, grid: { color: gridColor } },
                y: { ticks: { color: C.sub }, grid: { color: gridColor }, beginAtZero: true }
            },
            plugins: { ...baseOptions.plugins, legend: { display: false } }
        }
    });

    // Chart 3: Bar — budget par statut
    chartBudget = new Chart(document.getElementById('chartBudget'), {
        type: 'bar',
        data: {
            labels: ['Active', 'Brouillon', 'Terminée', 'Annulée'],
            datasets: [{
                label: 'Budget (€)',
                data: [
                    campData.budgets.active,
                    campData.budgets.brouillon,
                    campData.budgets.terminee,
                    campData.budgets.annulee
                ],
                backgroundColor: [C.success + 'bb', C.warn + 'bb', C.info + 'bb', C.danger + 'bb'],
                borderColor: [C.success, C.warn, C.info, C.danger],
                borderWidth: 1.5,
                borderRadius: 6,
            }]
        },
        options: {
            ...baseOptions,
            scales: {
                x: { ticks: { color: C.sub }, grid: { color: gridColor } },
                y: { ticks: { color: C.sub, callback: v => v.toLocaleString('fr-FR') + ' €' }, grid: { color: gridColor }, beginAtZero: true }
            },
            plugins: { ...baseOptions.plugins, legend: { display: false } }
        }
    });
}

let statsVisible = true;
function toggleStats() {
    const body = document.getElementById('statsBody');
    const btn  = document.getElementById('statsToggleBtn');
    const T = translations[currentLang];
    statsVisible = !statsVisible;
    body.style.display = statsVisible ? '' : 'none';
    btn.textContent = statsVisible ? (T.statsHide || '▲ Masquer') : (T.statsShow || '▼ Afficher');
}
// ===== END ADDED FEATURE: DYNAMIC STATISTICS =====

// ═══════════════════════════════════════════════════════════
// INIT — run all features on DOM ready
// ═══════════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', () => {
    // Theme (BackOffice default = dark)
    initTheme();
    // Language
    applyTranslations();
    updateTabLabels();
    document.getElementById('langFR').classList.toggle('active', currentLang === 'fr');
    document.getElementById('langEN').classList.toggle('active', currentLang === 'en');
    // Charts (after theme init so CSS vars are correct)
    buildCharts();
});
</script>
</body>
</html>