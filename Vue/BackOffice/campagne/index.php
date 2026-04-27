<?php
require_once '../../../Controleur/campagneC.php';
require_once '../../../Controleur/produitC.php';
require_once '../../../Modele/campagne.php';

$campagneC   = new CampagneC();
$produitC    = new ProduitC();
$message     = '';
$messageType = '';
$baseUrl     = '/projet/Esprit-PW-2A22-2526-Devcore';

// ── SUPPRESSION ───────────────────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $campagneC->supprimerCampagne(intval($_GET['delete']));
    header('Location: index.php?deleted=1');
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

// ── AJAX : ajouter produit à une campagne ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'lier_produit') {
    $campagneC->ajouterProduitCampagne(intval($_POST['idCampagne']), intval($_POST['idProduit']));
    echo json_encode(['ok' => true]);
    exit;
}

// ── AJAX : retirer produit d'une campagne ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'retirer_produit') {
    $campagneC->retirerProduitCampagne(intval($_POST['idCampagne']), intval($_POST['idProduit']));
    echo json_encode(['ok' => true]);
    exit;
}

// ── AJAX : produits d'une campagne ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax_produits'])) {
    $idC    = intval($_GET['ajax_produits']);
    $lies   = $produitC->getProduitsByCampagne($idC);
    $dispos = $produitC->getProduitsDisponiblesPourCampagne($idC);
    echo json_encode(['lies' => $lies, 'dispos' => $dispos]);
    exit;
}

// ── AJOUTER CAMPAGNE ──────────────────────────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $titre  = trim($_POST['titre'] ?? '');
    $budget = $_POST['budget'] ?? '';
    $dateDebut = trim($_POST['dateDebut'] ?? '');
    $dateFin   = trim($_POST['dateFin'] ?? '');

    $errors = [];
    if (strlen($titre) < 2) $errors[] = "Title is required (min 2 chars).";
    if (!is_numeric(str_replace(',', '.', $budget)) || floatval(str_replace(',', '.', $budget)) < 0) $errors[] = "Budget must be a number >= 0.";
    if ($dateDebut && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateDebut)) $errors[] = "Start date must be YYYY-MM-DD.";
    if ($dateFin && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFin)) $errors[] = "End date must be YYYY-MM-DD.";
    if ($dateDebut && $dateFin && $dateFin < $dateDebut) $errors[] = "End date must be after start date.";

    if (empty($errors)) {
        $campagne = new Campagne(
            null, htmlspecialchars($titre), trim($_POST['description'] ?? ''),
            $dateDebut ?: null, $dateFin ?: null,
            floatval(str_replace(',', '.', $budget)),
            $_POST['statut'] ?? 'brouillon', null,
            trim($_POST['objectif'] ?? ''), 0
        );
        $campagneC->ajouterCampagne($campagne);
        header('Location: index.php?added=1');
        exit;
    } else {
        $message = implode(' | ', $errors);
        $messageType = "error";
    }
}

// ── MODIFIER CAMPAGNE ─────────────────────────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'update') {
    $titre  = trim($_POST['titre'] ?? '');
    $budget = $_POST['budget'] ?? '';
    $dateDebut = trim($_POST['dateDebut'] ?? '');
    $dateFin   = trim($_POST['dateFin'] ?? '');

    $errors = [];
    if (strlen($titre) < 2) $errors[] = "Title is required (min 2 chars).";
    if (!is_numeric(str_replace(',', '.', $budget)) || floatval(str_replace(',', '.', $budget)) < 0) $errors[] = "Budget must be >= 0.";
    if ($dateDebut && $dateFin && $dateFin < $dateDebut) $errors[] = "End date must be after start date.";

    if (empty($errors)) {
        $campagne = new Campagne(
            null, htmlspecialchars($titre), trim($_POST['description'] ?? ''),
            $dateDebut ?: null, $dateFin ?: null,
            floatval(str_replace(',', '.', $budget)),
            $_POST['statut'] ?? 'brouillon', null,
            trim($_POST['objectif'] ?? ''), intval($_POST['estArchive'] ?? 0)
        );
        $campagneC->modifierCampagne($campagne, intval($_POST['id']));
        header('Location: index.php?updated=1');
        exit;
    } else {
        $message = implode(' | ', $errors);
        $messageType = "error";
    }
}

// ── MESSAGES GET ──────────────────────────────────────────────────────────────
if (isset($_GET['added']))   { $message = "Campaign added successfully.";   $messageType = "success"; }
if (isset($_GET['updated'])) { $message = "Campaign updated successfully."; $messageType = "info"; }
if (isset($_GET['deleted'])) { $message = "Campaign deleted.";              $messageType = "danger"; }

// ── DONNÉES ───────────────────────────────────────────────────────────────────
$liste         = $campagneC->afficherCampagnes();
$listeArchives = $campagneC->afficherCampagnesArchives();
$statuts       = $campagneC->getStatuts();
$toutesCampagnes = $campagneC->afficherToutesCampagnes();

$campagneUpdate = null;
if (isset($_GET['edit'])) {
    $campagneUpdate = $campagneC->recupererCampagne(intval($_GET['edit']));
}

// ── KPIs ──────────────────────────────────────────────────────────────────────
$totalCampagnes = count($liste);
$totalArchives  = count($listeArchives);
$budgets        = array_column($liste, 'budget');
$budgetTotal    = array_sum($budgets);
$budgetMoyen    = $totalCampagnes > 0 ? $budgetTotal / $totalCampagnes : 0;
$nbActives      = count(array_filter($liste, fn($c) => $c['statut'] === 'active'));
$nbBrouillons   = count(array_filter($liste, fn($c) => $c['statut'] === 'brouillon'));
$nbTerminees    = count(array_filter($liste, fn($c) => $c['statut'] === 'terminee'));
$nbAnnulees     = count(array_filter($liste, fn($c) => $c['statut'] === 'annulee'));

// ── CSV EXPORT ────────────────────────────────────────────────────────────────
if (isset($_GET['export_csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="campaigns_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID','Title','Description','Start Date','End Date','Budget (€)','Status','Brand','Objective','Archived','Products Count']);
    foreach ($liste as $c) {
        fputcsv($out, [
            $c['idCampagne'], $c['titreCampagne'], $c['description'],
            $c['dateDebut'], $c['dateFin'], $c['budget'], $c['statut'],
            $c['nomMarque'] ?? '', $c['objectif'] ?? '',
            !empty($c['estArchive']) ? 'Yes' : 'No',
            $campagneC->compterProduitsCampagne($c['idCampagne']),
        ]);
    }
    fclose($out);
    exit;
}

function statutLabel($s) { return match($s) { 'active'=>'✅ Active','terminee'=>'🏁 Ended','annulee'=>'❌ Cancelled',default=>'📝 Draft' }; }
function statutClass($s) { return match($s) { 'active'=>'badge-success','terminee'=>'badge-info','annulee'=>'badge-danger',default=>'badge-warning' }; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Campaign Management — Cre8Connect Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
:root {
    --bg-main:#0f1117; --bg-card:#171923; --bg-card-alt:#1e2130;
    --border:rgba(255,255,255,.07); --border-hover:rgba(255,255,255,.13);
    --text-primary:#f0f2f8; --text-muted:#8b92a5; --text-dim:#545d72;
    --accent:#7c6fff; --accent-soft:rgba(124,111,255,.12); --accent-border:rgba(124,111,255,.3);
    --success:#10b981; --success-soft:rgba(16,185,129,.12);
    --danger:#ef4444; --danger-soft:rgba(239,68,68,.12);
    --warning:#f59e0b; --warning-soft:rgba(245,158,11,.12);
    --info:#3b82f6; --info-soft:rgba(59,130,246,.12);
    --radius-sm:6px; --radius:10px; --radius-lg:16px;
    --sidebar-w:240px; --topbar-h:58px;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Inter',sans-serif;background:var(--bg-main);color:var(--text-primary);min-height:100vh;}

/* SIDEBAR */
.sidebar{position:fixed;top:0;left:0;width:var(--sidebar-w);height:100vh;background:var(--bg-card);border-right:1px solid var(--border);display:flex;flex-direction:column;z-index:100;overflow-y:auto;}
.sidebar-logo{padding:20px 18px 16px;border-bottom:1px solid var(--border);}
.brand{display:flex;align-items:center;gap:10px;text-decoration:none;}
.logo-img{width:32px;height:32px;border-radius:8px;object-fit:cover;}
.logo-text{font-size:14px;font-weight:700;color:var(--text-primary);}
.logo-badge{font-size:9px;font-weight:700;background:var(--accent-soft);color:var(--accent);border-radius:4px;padding:1px 5px;letter-spacing:.06em;}
.sidebar-nav{padding:14px 10px;flex:1;}
.nav-section-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.09em;color:var(--text-dim);padding:10px 8px 5px;}
.nav-item{display:flex;align-items:center;gap:9px;padding:8px 10px;border-radius:var(--radius-sm);text-decoration:none;color:var(--text-muted);font-size:13px;font-weight:500;transition:background .15s,color .15s;margin-bottom:2px;}
.nav-item:hover{background:var(--bg-card-alt);color:var(--text-primary);}
.nav-item.active{background:var(--accent-soft);color:var(--accent);}
.nav-icon{width:16px;height:16px;flex-shrink:0;}
.sidebar-footer{padding:14px 16px;border-top:1px solid var(--border);}
.admin-card{display:flex;align-items:center;gap:10px;}
.admin-avatar{width:32px;height:32px;border-radius:50%;background:var(--accent);color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;}
.admin-name{font-size:12px;font-weight:600;}
.admin-role{font-size:10px;color:var(--text-dim);}

/* TOPBAR */
.topbar{position:fixed;top:0;left:var(--sidebar-w);right:0;height:var(--topbar-h);background:var(--bg-card);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 22px;z-index:99;gap:12px;}
.topbar-breadcrumb{display:flex;align-items:center;gap:6px;font-size:13px;color:var(--text-muted);}
.topbar-breadcrumb .sep{color:var(--text-dim);}
.topbar-breadcrumb .current{color:var(--text-primary);font-weight:600;}
.topbar-actions{display:flex;align-items:center;gap:8px;}
.search-wrap{position:relative;}
.search-icon{position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-dim);width:13px;height:13px;pointer-events:none;}
.search-input{background:var(--bg-card-alt);border:1px solid var(--border);border-radius:var(--radius-sm);padding:7px 12px 7px 30px;font-size:12.5px;color:var(--text-primary);outline:none;width:200px;font-family:'Inter',sans-serif;transition:border-color .2s;}
.search-input:focus{border-color:var(--accent);}
.btn-add{display:inline-flex;align-items:center;gap:6px;background:var(--accent);color:#fff;border:none;border-radius:var(--radius-sm);padding:7px 14px;font-size:12.5px;font-weight:600;cursor:pointer;text-decoration:none;font-family:'Inter',sans-serif;}
.btn-add:hover{opacity:.88;}
.btn-export{display:inline-flex;align-items:center;gap:6px;background:var(--bg-card-alt);color:var(--text-muted);border:1px solid var(--border);border-radius:var(--radius-sm);padding:7px 13px;font-size:12px;font-weight:500;text-decoration:none;cursor:pointer;}
.btn-export:hover{color:var(--text-primary);}

/* LAYOUT */
.main{margin-left:var(--sidebar-w);padding-top:var(--topbar-h);min-height:100vh;}
.content{padding:28px 26px;}
.page-header{margin-bottom:22px;}
.page-title{font-size:20px;font-weight:700;letter-spacing:-.3px;}
.page-subtitle{font-size:13px;color:var(--text-muted);margin-top:3px;}

/* ── TOAST NOTIFICATIONS ── */
#toastContainer{position:fixed;top:72px;right:22px;z-index:9999;display:flex;flex-direction:column;gap:8px;pointer-events:none;}
.toast{display:flex;align-items:center;gap:10px;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:12px 16px;font-size:13px;font-weight:500;min-width:260px;max-width:360px;box-shadow:0 8px 24px rgba(0,0,0,.4);pointer-events:all;animation:toastIn .3s ease;transition:opacity .4s,transform .4s;}
.toast.hide{opacity:0;transform:translateX(20px);}
@keyframes toastIn{from{opacity:0;transform:translateX(20px);}to{opacity:1;transform:translateX(0);}}
.toast-success{border-color:rgba(16,185,129,.3);}
.toast-danger{border-color:rgba(239,68,68,.3);}
.toast-info{border-color:rgba(59,130,246,.3);}
.toast-error{border-color:rgba(239,68,68,.3);}
.toast-icon{font-size:15px;flex-shrink:0;}
.toast-close{margin-left:auto;background:none;border:none;color:var(--text-dim);cursor:pointer;font-size:14px;padding:0 0 0 8px;}

/* ALERT */
.alert{display:flex;align-items:center;gap:9px;padding:12px 16px;border-radius:var(--radius-sm);font-size:13px;font-weight:500;margin-bottom:18px;border:1px solid transparent;}
.alert-success{background:var(--success-soft);color:var(--success);border-color:rgba(16,185,129,.2);}
.alert-info{background:var(--info-soft);color:var(--info);border-color:rgba(59,130,246,.2);}
.alert-danger{background:var(--danger-soft);color:var(--danger);border-color:rgba(239,68,68,.2);}
.alert-error{background:var(--danger-soft);color:var(--danger);border-color:rgba(239,68,68,.2);}

/* KPI STRIP */
.kpi-strip{display:grid;grid-template-columns:repeat(6,1fr);gap:12px;margin-bottom:22px;}
.kpi-card{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:16px 18px;}
.kpi-label{font-size:11px;font-weight:600;color:var(--text-dim);text-transform:uppercase;letter-spacing:.07em;margin-bottom:6px;}
.kpi-value{font-size:22px;font-weight:700;}
.kpi-sub{font-size:11px;color:var(--text-muted);margin-top:3px;}
.kpi-accent{color:var(--accent);}
.kpi-success{color:var(--success);}
.kpi-warning{color:var(--warning);}
.kpi-danger{color:var(--danger);}
.kpi-info{color:var(--info);}

/* ── CHART SECTION ── */
.charts-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px;}
.chart-card{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:20px;}
.chart-card-title{font-size:13px;font-weight:600;color:var(--text-muted);margin-bottom:14px;display:flex;align-items:center;gap:7px;}
.chart-wrap{position:relative;height:200px;}

/* TABS */
.tabs{display:flex;gap:4px;margin-bottom:20px;border-bottom:1px solid var(--border);}
.tab-btn{background:none;border:none;padding:9px 16px;font-size:13px;font-weight:500;color:var(--text-muted);cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-1px;font-family:'Inter',sans-serif;transition:color .15s;}
.tab-btn.active{color:var(--accent);border-bottom-color:var(--accent);}
.tab-panel{display:none;}
.tab-panel.active{display:block;}

/* TABLE */
.table-wrap{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;}
.table-toolbar{display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:1px solid var(--border);gap:10px;flex-wrap:wrap;}
.table-title-row{display:flex;align-items:center;gap:10px;}
.table-title{font-size:14px;font-weight:600;}
.count-badge{background:var(--accent-soft);color:var(--accent);border-radius:20px;padding:2px 10px;font-size:11px;font-weight:700;}
.toolbar-actions{display:flex;align-items:center;gap:8px;}
.filter-select{background:var(--bg-card-alt);border:1px solid var(--border);border-radius:var(--radius-sm);padding:6px 10px;font-size:12px;color:var(--text-primary);cursor:pointer;font-family:'Inter',sans-serif;outline:none;}
table{width:100%;border-collapse:collapse;}
thead{background:var(--bg-card-alt);}
th{text-align:left;padding:11px 14px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-dim);white-space:nowrap;cursor:pointer;user-select:none;}
th:hover{color:var(--text-muted);}
th.sort-asc::after{content:' ↑';color:var(--accent);}
th.sort-desc::after{content:' ↓';color:var(--accent);}
td{padding:12px 14px;font-size:13px;border-top:1px solid var(--border);vertical-align:middle;}
tr:hover td{background:var(--bg-card-alt);}
.camp-title{font-weight:600;color:var(--text-primary);font-size:13.5px;}
.camp-obj{font-size:12px;color:var(--text-muted);margin-top:2px;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.col-budget{font-weight:600;color:var(--success);}
.col-dates{white-space:nowrap;font-size:12px;color:var(--text-muted);}
.date-alert{color:var(--danger);font-size:11px;font-weight:600;}

/* BADGE */
.badge{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:600;padding:3px 9px;border-radius:20px;}
.badge-success{background:var(--success-soft);color:var(--success);}
.badge-warning{background:var(--warning-soft);color:var(--warning);}
.badge-danger{background:var(--danger-soft);color:var(--danger);}
.badge-info{background:var(--info-soft);color:var(--info);}

/* PRODUCT COUNT BADGE */
.prod-count-badge{display:inline-flex;align-items:center;gap:4px;background:var(--accent-soft);color:var(--accent);border-radius:20px;padding:2px 8px;font-size:11px;font-weight:700;cursor:pointer;transition:background .15s;border:none;font-family:'Inter',sans-serif;}
.prod-count-badge:hover{background:rgba(124,111,255,.22);}

/* ACTION BUTTONS */
.btn-table{display:inline-flex;align-items:center;gap:4px;padding:5px 10px;border-radius:var(--radius-sm);font-size:11.5px;font-weight:600;cursor:pointer;border:none;font-family:'Inter',sans-serif;text-decoration:none;transition:opacity .15s;}
.btn-table:hover{opacity:.8;}
.btn-edit{background:var(--accent-soft);color:var(--accent);}
.btn-delete{background:var(--danger-soft);color:var(--danger);}
.btn-archive{background:var(--warning-soft);color:var(--warning);}
.btn-unarch{background:var(--info-soft);color:var(--info);}
.statut-select{background:transparent;border:1px solid var(--border);border-radius:var(--radius-sm);padding:4px 8px;font-size:11px;font-weight:600;cursor:pointer;font-family:'Inter',sans-serif;color:var(--text-muted);}

/* ── FORM ── */
.form-section{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:24px 26px;margin-top:22px;}
.form-section-title{font-size:15px;font-weight:600;margin-bottom:20px;}
.edit-banner{display:flex;align-items:center;justify-content:space-between;background:var(--accent-soft);border:1px solid var(--accent-border);border-radius:var(--radius-sm);padding:9px 14px;margin-bottom:18px;font-size:13px;}
.edit-banner a{color:var(--danger);text-decoration:none;font-weight:600;font-size:12px;}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px;}
.form-grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:18px;}
.form-group{display:flex;flex-direction:column;gap:4px;}
.form-group label{font-size:12px;font-weight:600;color:var(--text-muted);}
.form-input{background:var(--bg-card-alt);border:1px solid var(--border);border-radius:var(--radius-sm);padding:9px 12px;font-size:13px;color:var(--text-primary);font-family:'Inter',sans-serif;outline:none;transition:border-color .2s;width:100%;}
.form-input:focus{border-color:var(--accent);}
.form-input.is-invalid{border-color:var(--danger) !important;}
.form-input.is-valid{border-color:var(--success);}
textarea.form-input{resize:vertical;min-height:80px;}
.field-error{font-size:11px;color:var(--danger);display:none;margin-top:2px;align-items:center;gap:4px;}
.field-error.visible{display:flex;}
.field-error::before{content:'⚠';font-size:10px;}
.char-counter{font-size:10px;color:var(--text-dim);text-align:right;margin-top:2px;}
.char-counter.warn{color:var(--warning);}
.input-with-prefix{display:flex;align-items:center;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--bg-card-alt);overflow:hidden;transition:border-color .2s;}
.input-with-prefix:focus-within{border-color:var(--accent);}
.input-with-prefix.is-invalid{border-color:var(--danger);}
.prefix{padding:0 10px;font-size:12.5px;color:var(--text-dim);border-right:1px solid var(--border);background:var(--bg-main);height:100%;display:flex;align-items:center;min-height:38px;}
.input-with-prefix .form-input{border:none;background:transparent;flex:1;}
.date-coherence-msg{font-size:11px;color:var(--warning);display:none;margin-top:3px;align-items:center;gap:4px;}
.date-coherence-msg.visible{display:flex;}
.form-actions{display:flex;gap:10px;align-items:center;margin-top:22px;padding-top:18px;border-top:1px solid var(--border);}
.btn-submit{background:var(--accent);color:#fff;border:none;border-radius:var(--radius-sm);padding:9px 22px;font-size:13px;font-weight:600;cursor:pointer;font-family:'Inter',sans-serif;}
.btn-submit:disabled{opacity:.5;cursor:not-allowed;}
.btn-cancel-form{background:var(--bg-card-alt);color:var(--text-muted);border:1px solid var(--border);border-radius:var(--radius-sm);padding:9px 18px;font-size:13px;font-weight:500;cursor:pointer;text-decoration:none;font-family:'Inter',sans-serif;}

/* PRODUCTS PANEL */
.products-panel-overlay{position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:200;display:none;align-items:flex-start;justify-content:flex-end;}
.products-panel-overlay.open{display:flex;}
.products-panel{width:460px;max-width:96vw;height:100vh;background:var(--bg-card);border-left:1px solid var(--border);display:flex;flex-direction:column;overflow:hidden;animation:slideInRight .22s ease;}
@keyframes slideInRight{from{transform:translateX(40px);opacity:0;}to{transform:translateX(0);opacity:1;}}
.pp-header{padding:18px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;}
.pp-title{font-size:14px;font-weight:600;}
.pp-subtitle{font-size:12px;color:var(--text-muted);margin-top:2px;}
.pp-close{background:var(--bg-card-alt);border:1px solid var(--border);border-radius:50%;width:28px;height:28px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--text-muted);font-size:14px;}
.pp-body{flex:1;overflow-y:auto;padding:18px 20px;display:flex;flex-direction:column;gap:20px;}
.pp-section-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--text-dim);margin-bottom:10px;}
.pp-product-list{display:flex;flex-direction:column;gap:8px;}
.pp-product-item{display:flex;align-items:center;gap:10px;background:var(--bg-card-alt);border:1px solid var(--border);border-radius:var(--radius-sm);padding:10px 12px;}
.pp-product-thumb{width:38px;height:38px;border-radius:6px;object-fit:cover;background:var(--bg-main);flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:16px;overflow:hidden;}
.pp-product-thumb img{width:100%;height:100%;object-fit:cover;}
.pp-product-info{flex:1;min-width:0;}
.pp-product-name{font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.pp-product-meta{font-size:11px;color:var(--text-muted);margin-top:2px;}
.pp-price{font-size:12px;font-weight:700;color:var(--success);white-space:nowrap;}
.btn-pp-remove{background:var(--danger-soft);color:var(--danger);border:none;border-radius:var(--radius-sm);padding:4px 10px;font-size:11px;font-weight:600;cursor:pointer;font-family:'Inter',sans-serif;flex-shrink:0;}
.pp-add-row{display:flex;gap:8px;}
.pp-select{flex:1;background:var(--bg-card-alt);border:1px solid var(--border);border-radius:var(--radius-sm);padding:8px 10px;font-size:12.5px;color:var(--text-primary);font-family:'Inter',sans-serif;outline:none;}
.pp-select:focus{border-color:var(--accent);}
.btn-pp-add{background:var(--accent);color:#fff;border:none;border-radius:var(--radius-sm);padding:8px 14px;font-size:12px;font-weight:600;cursor:pointer;white-space:nowrap;font-family:'Inter',sans-serif;}
.pp-empty{text-align:center;padding:24px 10px;color:var(--text-dim);font-size:13px;}
.pp-loader{text-align:center;padding:20px;color:var(--text-muted);font-size:13px;}

/* CONFIRM MODAL */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:300;display:none;align-items:center;justify-content:center;}
.modal-overlay.open{display:flex;}
.modal-box{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);padding:28px 30px;width:400px;max-width:94vw;animation:modalPop .22s ease;}
@keyframes modalPop{from{opacity:0;transform:scale(.93);}to{opacity:1;transform:scale(1);}}
.modal-title{font-size:16px;font-weight:600;margin-bottom:8px;}
.modal-text{font-size:13px;color:var(--text-muted);margin-bottom:22px;line-height:1.6;}
.modal-actions{display:flex;gap:10px;justify-content:flex-end;}
.btn-modal-cancel{background:var(--bg-card-alt);color:var(--text-muted);border:1px solid var(--border);border-radius:var(--radius-sm);padding:8px 16px;font-size:13px;cursor:pointer;font-family:'Inter',sans-serif;}
.btn-modal-confirm{background:var(--danger);color:#fff;border:none;border-radius:var(--radius-sm);padding:8px 18px;font-size:13px;font-weight:600;cursor:pointer;font-family:'Inter',sans-serif;}

/* COUNTDOWN */
.countdown-badge{display:inline-flex;align-items:center;gap:5px;background:rgba(16,185,129,.1);color:var(--success);border-radius:20px;padding:2px 8px;font-size:10px;font-weight:700;margin-top:3px;}
.countdown-badge.ending{background:var(--warning-soft);color:var(--warning);}
.countdown-badge.ended{background:var(--danger-soft);color:var(--danger);}

/* BULK ACTIONS */
.bulk-bar{background:var(--accent-soft);border:1px solid var(--accent-border);border-radius:var(--radius-sm);padding:10px 16px;display:none;align-items:center;gap:12px;margin-bottom:12px;font-size:13px;}
.bulk-bar.visible{display:flex;}
.btn-bulk-delete{background:var(--danger-soft);color:var(--danger);border:1px solid rgba(239,68,68,.2);border-radius:var(--radius-sm);padding:5px 12px;font-size:12px;font-weight:500;cursor:pointer;font-family:'Inter',sans-serif;}

@media(max-width:1300px){.kpi-strip{grid-template-columns:repeat(3,1fr);}
.charts-row{grid-template-columns:1fr;}}
@media(max-width:900px){.sidebar{display:none;}.topbar,.main{left:0;margin-left:0;}.form-grid{grid-template-columns:1fr;}}
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
        <a class="nav-item" href="#"><svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>Complaints</a>
        <div class="nav-section-label" style="margin-top:8px">Modules</div>
        <a class="nav-item" href="#"><svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>Offers & Applications</a>
        <a class="nav-item" href="#"><svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>Events & Forums</a>
        <a class="nav-item active" href="index.php"><svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>Campaigns</a>
        <a class="nav-item" href="#"><svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 10V11"/></svg>Products</a>
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
        <span class="current">Campaigns</span>
    </div>
    <div class="topbar-actions">
        <div class="search-wrap">
            <svg class="search-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
            <input type="text" class="search-input" id="searchInput" placeholder="Search campaigns…">
        </div>
        <a href="?export_csv=1" class="btn-export">
            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Export CSV
        </a>
        <a href="#formAnchor" class="btn-add">
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            New Campaign
        </a>
    </div>
</div>

<!-- CONTENT -->
<main class="main">
<div class="content">

    <div class="page-header">
        <div class="page-title">Campaign Management</div>
        <div class="page-subtitle">Supervise all campaigns, manage products and track performance.</div>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>" id="alertMsg">
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <!-- KPI STRIP -->
    <div class="kpi-strip">
        <div class="kpi-card"><div class="kpi-label">Total Active</div><div class="kpi-value kpi-accent"><?= $totalCampagnes ?></div><div class="kpi-sub">live campaigns</div></div>
        <div class="kpi-card"><div class="kpi-label">Active Now</div><div class="kpi-value kpi-success"><?= $nbActives ?></div><div class="kpi-sub">running</div></div>
        <div class="kpi-card"><div class="kpi-label">Drafts</div><div class="kpi-value kpi-warning"><?= $nbBrouillons ?></div><div class="kpi-sub">not published</div></div>
        <div class="kpi-card"><div class="kpi-label">Ended</div><div class="kpi-value kpi-info"><?= $nbTerminees ?></div><div class="kpi-sub">completed</div></div>
        <div class="kpi-card"><div class="kpi-label">Total Budget</div><div class="kpi-value kpi-success"><?= number_format($budgetTotal,0,',',' ') ?> €</div><div class="kpi-sub">avg <?= number_format($budgetMoyen,0) ?> €/campaign</div></div>
        <div class="kpi-card"><div class="kpi-label">Archived</div><div class="kpi-value kpi-danger"><?= $totalArchives ?></div><div class="kpi-sub">hidden</div></div>
    </div>

    <!-- CHARTS ROW -->
    <div class="charts-row">
        <div class="chart-card">
            <div class="chart-card-title">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                Campaigns by Status
            </div>
            <div class="chart-wrap"><canvas id="chartStatuts"></canvas></div>
        </div>
        <div class="chart-card">
            <div class="chart-card-title">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                Budget by Status (€)
            </div>
            <div class="chart-wrap"><canvas id="chartBudgets"></canvas></div>
        </div>
    </div>

    <!-- TABS -->
    <div class="tabs">
        <button class="tab-btn active" onclick="switchTab('active',this)">Active (<?= $totalCampagnes ?>)</button>
        <button class="tab-btn" onclick="switchTab('archived',this)">Archived (<?= $totalArchives ?>)</button>
    </div>

    <!-- TAB: ACTIVE -->
    <div class="tab-panel active" id="tab-active">
        <div class="bulk-bar" id="bulkBar">
            <span id="bulkCount" style="color:var(--accent);font-weight:600;">0 selected</span>
            <button class="btn-bulk-delete" id="btnBulkDelete">🗑 Delete selected</button>
            <button class="btn-bulk-delete" style="background:var(--warning-soft);color:var(--warning);border-color:rgba(245,158,11,.2);" id="btnBulkArchive">📦 Archive selected</button>
            <button onclick="clearSelection()" style="background:none;border:none;color:var(--text-dim);cursor:pointer;font-size:12px;margin-left:auto;">✕ Cancel</button>
        </div>
        <div class="table-wrap">
            <div class="table-toolbar">
                <div class="table-title-row">
                    <div class="table-title">All Campaigns</div>
                    <div class="count-badge" id="visibleBadge"><?= $totalCampagnes ?></div>
                </div>
                <div class="toolbar-actions">
                    <select class="filter-select" id="filterStatut" onchange="filterTable()">
                        <option value="">All statuses</option>
                        <?php foreach ($statuts as $s): ?>
                        <option value="<?= $s ?>"><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <table id="campagneTable">
                <thead>
                    <tr>
                        <th class="no-sort" style="width:36px"><input type="checkbox" id="selectAll" style="accent-color:var(--accent);cursor:pointer;"></th>
                        <th onclick="sortTable('titre')">Title</th>
                        <th>Status</th>
                        <th onclick="sortTable('debut')">Dates</th>
                        <th onclick="sortTable('budget')">Budget</th>
                        <th>Brand</th>
                        <th>Products</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="campagneBody">
                <?php if (empty($liste)): ?>
                    <tr><td colspan="8" style="text-align:center;padding:32px;color:var(--text-dim);">No campaigns found.</td></tr>
                <?php else: ?>
                <?php foreach ($liste as $c):
                    $nbProd = $campagneC->compterProduitsCampagne($c['idCampagne']);
                    $today = date('Y-m-d');
                    $isExpiringSoon = $c['dateFin'] && $c['dateFin'] >= $today && $c['statut'] === 'active' && (strtotime($c['dateFin']) - time()) < 7*86400;
                    $isExpired = $c['dateFin'] && $c['dateFin'] < $today && $c['statut'] === 'active';
                ?>
                <tr data-statut="<?= htmlspecialchars($c['statut']) ?>"
                    data-titre="<?= strtolower(htmlspecialchars($c['titreCampagne'])) ?>"
                    data-budget="<?= (float)$c['budget'] ?>"
                    data-debut="<?= $c['dateDebut'] ?? '' ?>">
                    <td><input type="checkbox" class="row-check" value="<?= $c['idCampagne'] ?>" style="accent-color:var(--accent);cursor:pointer;"></td>
                    <td>
                        <div class="camp-title"><?= htmlspecialchars($c['titreCampagne']) ?></div>
                        <div class="camp-obj"><?= htmlspecialchars($c['objectif'] ?? '—') ?></div>
                        <?php if ($isExpiringSoon): ?>
                            <div class="countdown-badge ending" data-endfin="<?= $c['dateFin'] ?>">⏳ Ending soon</div>
                        <?php elseif ($isExpired): ?>
                            <div class="countdown-badge ended">⚠ Expired</div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <select class="statut-select" onchange="changeStatut(<?= $c['idCampagne'] ?>,this.value,this)">
                            <?php foreach ($statuts as $s): ?>
                            <option value="<?= $s ?>" <?= $c['statut']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td class="col-dates">
                        📅 <?= $c['dateDebut']??'—' ?><br>
                        🏁 <?php if ($c['dateFin'] && $c['dateFin'] < $today && $c['statut']==='active'): ?>
                            <span class="date-alert"><?= $c['dateFin'] ?> (expired!)</span>
                        <?php else: ?><?= $c['dateFin']??'—' ?><?php endif; ?>
                    </td>
                    <td class="col-budget"><?= number_format((float)$c['budget'],2,',',' ') ?> €</td>
                    <td style="font-size:12px;color:var(--text-muted)"><?= htmlspecialchars($c['nomMarque']??'—') ?></td>
                    <td>
                        <button class="prod-count-badge" onclick="openProductsPanel(<?= $c['idCampagne'] ?>,'<?= htmlspecialchars(addslashes($c['titreCampagne'])) ?>')">
                            📦 <?= $nbProd ?> product<?= $nbProd!==1?'s':'' ?>
                        </button>
                    </td>
                    <td>
                        <a href="?edit=<?= $c['idCampagne'] ?>#formAnchor" class="btn-table btn-edit">✏️ Edit</a>
                        <button class="btn-table btn-archive" onclick="toggleArchive(<?= $c['idCampagne'] ?>,'<?= htmlspecialchars(addslashes($c['titreCampagne'])) ?>')">📦</button>
                        <button class="btn-table btn-delete" onclick="confirmDelete(<?= $c['idCampagne'] ?>,'<?= htmlspecialchars(addslashes($c['titreCampagne'])) ?>')">🗑</button>
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
                <div class="table-title-row"><div class="table-title">Archived Campaigns</div><div class="count-badge"><?= $totalArchives ?></div></div>
            </div>
            <table>
                <thead><tr><th>Title</th><th>Status</th><th>Budget</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if (empty($listeArchives)): ?>
                    <tr><td colspan="4" style="text-align:center;padding:32px;color:var(--text-dim);">No archived campaigns.</td></tr>
                <?php else: ?>
                <?php foreach ($listeArchives as $c): ?>
                <tr>
                    <td><div class="camp-title" style="opacity:.7"><?= htmlspecialchars($c['titreCampagne']) ?></div></td>
                    <td><span class="badge <?= statutClass($c['statut']) ?>"><?= statutLabel($c['statut']) ?></span></td>
                    <td class="col-budget"><?= number_format((float)$c['budget'],2,',',' ') ?> €</td>
                    <td>
                        <button class="btn-table btn-unarch" onclick="toggleArchive(<?= $c['idCampagne'] ?>,'<?= htmlspecialchars(addslashes($c['titreCampagne'])) ?>')">🔁 Restore</button>
                        <button class="btn-table btn-delete" onclick="confirmDelete(<?= $c['idCampagne'] ?>,'<?= htmlspecialchars(addslashes($c['titreCampagne'])) ?>')">🗑</button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ─── FORM SECTION ─────────────────────────────────────────────────── -->
    <div class="form-section" id="formAnchor">
        <div class="form-section-title"><?= $campagneUpdate ? '✏️ Edit Campaign' : '➕ Add a Campaign' ?></div>
        <?php if ($campagneUpdate): ?>
        <div class="edit-banner">
            <span>Editing: <strong><?= htmlspecialchars($campagneUpdate['titreCampagne']) ?></strong></span>
            <a href="index.php">✕ Cancel</a>
        </div>
        <?php endif; ?>
        <form method="POST" action="index.php" id="campagneForm" novalidate>
            <input type="hidden" name="action" value="<?= $campagneUpdate ? 'update' : 'add' ?>">
            <?php if ($campagneUpdate): ?>
            <input type="hidden" name="id" value="<?= $campagneUpdate['idCampagne'] ?>">
            <input type="hidden" name="estArchive" value="<?= intval($campagneUpdate['estArchive']??0) ?>">
            <?php endif; ?>

            <div class="form-grid" style="margin-bottom:14px">
                <!-- TITRE -->
                <div class="form-group">
                    <label>Campaign Title * <span id="titreCounter" style="font-size:10px;color:var(--text-dim);float:right;font-weight:400;">0/100</span></label>
                    <input type="text" name="titre" id="fTitre" class="form-input" maxlength="100"
                           value="<?= $campagneUpdate ? htmlspecialchars($campagneUpdate['titreCampagne']) : '' ?>"
                           placeholder="e.g. Summer Collab 2025">
                    <div class="field-error" id="errTitre">Title required — min 2 characters, no HTML tags.</div>
                </div>
                <!-- STATUT -->
                <div class="form-group">
                    <label>Status</label>
                    <select name="statut" class="form-input">
                        <?php foreach ($statuts as $s): ?>
                        <option value="<?= $s ?>" <?= ($campagneUpdate && $campagneUpdate['statut']===$s)?'selected':'' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- DESCRIPTION -->
            <div class="form-group" style="margin-bottom:14px">
                <label>Description <span id="descCounter" style="font-size:10px;color:var(--text-dim);float:right;font-weight:400;">0/600</span></label>
                <textarea name="description" id="fDesc" class="form-input" maxlength="600"
                          placeholder="Describe the campaign goals, target creators…"><?= $campagneUpdate ? htmlspecialchars($campagneUpdate['description']) : '' ?></textarea>
            </div>

            <!-- OBJECTIF -->
            <div class="form-group" style="margin-bottom:14px">
                <label>Objective / Goal <span id="objCounter" style="font-size:10px;color:var(--text-dim);float:right;font-weight:400;">0/200</span></label>
                <input type="text" name="objectif" id="fObjectif" class="form-input" maxlength="200"
                       placeholder="e.g. Increase brand awareness, 100K views"
                       value="<?= $campagneUpdate ? htmlspecialchars($campagneUpdate['objectif']??'') : '' ?>">
            </div>

            <div class="form-grid-3" style="margin-bottom:14px">
                <!-- DATE DEBUT -->
                <div class="form-group">
                    <label>Start Date</label>
                    <input type="text" name="dateDebut" id="fDateDebut" class="form-input" placeholder="YYYY-MM-DD"
                           value="<?= $campagneUpdate ? htmlspecialchars($campagneUpdate['dateDebut']??'') : '' ?>">
                    <div class="field-error" id="errDateDebut">Invalid date format. Use YYYY-MM-DD.</div>
                </div>
                <!-- DATE FIN -->
                <div class="form-group">
                    <label>End Date</label>
                    <input type="text" name="dateFin" id="fDateFin" class="form-input" placeholder="YYYY-MM-DD"
                           value="<?= $campagneUpdate ? htmlspecialchars($campagneUpdate['dateFin']??'') : '' ?>">
                    <div class="field-error" id="errDateFin">Invalid date format. Use YYYY-MM-DD.</div>
                    <div class="date-coherence-msg" id="errDateCoherence">⚠ End date must be after start date.</div>
                </div>
                <!-- BUDGET -->
                <div class="form-group">
                    <label>Budget (€) *</label>
                    <div class="input-with-prefix" id="budgetWrapper">
                        <span class="prefix">€</span>
                        <input type="text" name="budget" id="fBudget" class="form-input"
                               placeholder="0.00"
                               value="<?= $campagneUpdate ? htmlspecialchars($campagneUpdate['budget']??'') : '' ?>">
                    </div>
                    <div class="field-error" id="errBudget">Budget must be a positive number (e.g. 1500.00).</div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-submit" id="btnSubmit"><?= $campagneUpdate ? '💾 Save Changes' : '✅ Add Campaign' ?></button>
                <?php if ($campagneUpdate): ?><a href="index.php" class="btn-cancel-form">Cancel</a><?php endif; ?>
            </div>
        </form>
    </div>

</div>
</main>

<!-- PRODUCTS PANEL -->
<div class="products-panel-overlay" id="productsPanelOverlay" onclick="closePanelOutside(event)">
    <div class="products-panel">
        <div class="pp-header">
            <div>
                <div class="pp-title" id="ppTitle">Campaign Products</div>
                <div class="pp-subtitle">Add or remove linked products</div>
            </div>
            <button class="pp-close" onclick="closeProductsPanel()">✕</button>
        </div>
        <div class="pp-body" id="ppBody"><div class="pp-loader">Loading…</div></div>
    </div>
</div>

<!-- CONFIRM MODAL -->
<div class="modal-overlay" id="confirmModal">
    <div class="modal-box">
        <div class="modal-title" id="modalTitle">Confirm action</div>
        <div class="modal-text" id="modalText"></div>
        <div class="modal-actions">
            <button class="btn-modal-cancel" onclick="closeModal()">Cancel</button>
            <a href="#" class="btn-modal-confirm" id="modalConfirmLink">Confirm</a>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?= $baseUrl ?>';

/* ════════════════════════════════════════════════
   TOAST SYSTEM
════════════════════════════════════════════════ */
function showToast(msg, type = 'info', duration = 4000) {
    const icons = { success:'✅', danger:'🗑️', info:'ℹ️', error:'⚠️', warning:'⚠️' };
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `<span class="toast-icon">${icons[type]||'ℹ️'}</span><span style="flex:1">${msg}</span><button class="toast-close" onclick="this.closest('.toast').remove()">✕</button>`;
    container.appendChild(toast);
    setTimeout(() => { toast.classList.add('hide'); setTimeout(() => toast.remove(), 450); }, duration);
}

// Show toast if PHP message exists
<?php if ($message): ?>
showToast(<?= json_encode($message) ?>, '<?= $messageType ?>');
<?php endif; ?>

// Auto-hide alert
const alertEl = document.getElementById('alertMsg');
if (alertEl) setTimeout(() => alertEl.style.display = 'none', 4500);

/* ════════════════════════════════════════════════
   CHARTS — Chart.js
════════════════════════════════════════════════ */
(function initCharts() {
    const chartColors = {
        active:   { bg:'rgba(16,185,129,.25)',  border:'#10b981' },
        brouillon:{ bg:'rgba(245,158,11,.25)',  border:'#f59e0b' },
        terminee: { bg:'rgba(59,130,246,.25)',  border:'#3b82f6' },
        annulee:  { bg:'rgba(239,68,68,.25)',   border:'#ef4444' },
    };

    const countData = {
        active:    <?= $nbActives ?>,
        brouillon: <?= $nbBrouillons ?>,
        terminee:  <?= $nbTerminees ?>,
        annulee:   <?= $nbAnnulees ?>,
    };

    // Budget by status
    const budgetByStatus = { active:0, brouillon:0, terminee:0, annulee:0 };
    <?php foreach ($liste as $c): ?>
    budgetByStatus['<?= $c['statut'] ?>'] += <?= (float)$c['budget'] ?>;
    <?php endforeach; ?>

    const labels = ['Active','Draft','Ended','Cancelled'];
    const keys   = ['active','brouillon','terminee','annulee'];
    const defaults = { responsive:true, maintainAspectRatio:false,
        plugins:{ legend:{ labels:{ color:'#8b92a5', font:{size:11} } } } };

    // Donut — statuts
    new Chart(document.getElementById('chartStatuts'), {
        type: 'doughnut',
        data: {
            labels,
            datasets:[{
                data: keys.map(k => countData[k]),
                backgroundColor: keys.map(k => chartColors[k].bg),
                borderColor:     keys.map(k => chartColors[k].border),
                borderWidth: 2,
            }]
        },
        options: { ...defaults, cutout:'65%' }
    });

    // Bar — budgets
    new Chart(document.getElementById('chartBudgets'), {
        type: 'bar',
        data: {
            labels,
            datasets:[{
                label: 'Budget (€)',
                data: keys.map(k => budgetByStatus[k]),
                backgroundColor: keys.map(k => chartColors[k].bg),
                borderColor:     keys.map(k => chartColors[k].border),
                borderWidth: 2,
                borderRadius: 6,
            }]
        },
        options: { ...defaults,
            plugins:{ legend:{ display:false } },
            scales:{
                x:{ ticks:{ color:'#8b92a5' }, grid:{ color:'rgba(255,255,255,.05)' } },
                y:{ ticks:{ color:'#8b92a5' }, grid:{ color:'rgba(255,255,255,.05)' } }
            }
        }
    });
})();

/* ════════════════════════════════════════════════
   COUNTDOWN BADGES
════════════════════════════════════════════════ */
document.querySelectorAll('.countdown-badge[data-endfin]').forEach(badge => {
    const fin = new Date(badge.dataset.endfin);
    const updateTimer = () => {
        const diff = fin - new Date();
        if (diff <= 0) { badge.textContent = '⚠ Expired'; badge.className = 'countdown-badge ended'; return; }
        const d = Math.floor(diff/86400000);
        const h = Math.floor((diff%86400000)/3600000);
        badge.textContent = `⏳ ${d}d ${h}h left`;
    };
    updateTimer();
    setInterval(updateTimer, 60000);
});

/* ════════════════════════════════════════════════
   VALIDATION JS — FORM
════════════════════════════════════════════════ */

// Helpers
function showError(id, msg) {
    const el = document.getElementById(id);
    if (!el) return;
    if (msg) { el.textContent = '⚠ ' + msg; el.classList.add('visible'); }
    else      { el.classList.remove('visible'); }
}
function setValid(el, ok) {
    if (!el) return;
    el.classList.toggle('is-invalid', !ok);
    el.classList.toggle('is-valid', ok);
}
function isValidDateStr(v) {
    if (!v) return true; // optional
    if (!/^\d{4}-\d{2}-\d{2}$/.test(v)) return false;
    const [y,m,d] = v.split('-').map(Number);
    const dt = new Date(y, m-1, d);
    return dt.getFullYear()===y && dt.getMonth()===m-1 && dt.getDate()===d;
}
function isValidBudget(v) {
    if (!v.trim()) return false;
    const n = parseFloat(v.replace(',','.'));
    if (isNaN(n) || n < 0) return false;
    const parts = v.replace(',','.').split('.');
    if (parts[0].length > 8) return false;
    if (parts[1] && parts[1].length > 2) return false;
    return true;
}
function stripHTML(v) { return /<[^>]+>/.test(v); }

// Live validation
const fTitre = document.getElementById('fTitre');
const fBudget = document.getElementById('fBudget');
const fDateDebut = document.getElementById('fDateDebut');
const fDateFin = document.getElementById('fDateFin');
const fDesc = document.getElementById('fDesc');
const fObjectif = document.getElementById('fObjectif');

function updateCounter(inputEl, counterId) {
    const counter = document.getElementById(counterId);
    if (!counter || !inputEl) return;
    const len = inputEl.value.length;
    const max = parseInt(inputEl.maxLength) || 999;
    counter.textContent = len + '/' + max;
    counter.className = len > max * 0.85 ? 'warn' : '';
}

fTitre && fTitre.addEventListener('input', () => {
    updateCounter(fTitre, 'titreCounter');
    const v = fTitre.value.trim();
    const ok = v.length >= 2 && v.length <= 100 && !stripHTML(v);
    setValid(fTitre, ok);
    if (!ok) {
        if (v.length < 2) showError('errTitre', 'Title must be at least 2 characters.');
        else if (v.length > 100) showError('errTitre', 'Title must not exceed 100 characters.');
        else showError('errTitre', 'Title must not contain HTML tags.');
    } else showError('errTitre', '');
});

fDesc && fDesc.addEventListener('input', () => updateCounter(fDesc, 'descCounter'));
fObjectif && fObjectif.addEventListener('input', () => updateCounter(fObjectif, 'objCounter'));

// Budget: block non-numeric keys
fBudget && fBudget.addEventListener('keydown', e => {
    const allowed = ['Backspace','Delete','Tab','Escape','Enter','ArrowLeft','ArrowRight','Home','End'];
    if (allowed.includes(e.key) || e.ctrlKey || e.metaKey) return;
    if (e.key === '.' || e.key === ',') {
        if (fBudget.value.includes('.') || fBudget.value.includes(',')) e.preventDefault();
        return;
    }
    if (!/^\d$/.test(e.key)) e.preventDefault();
});

fBudget && fBudget.addEventListener('input', () => {
    const v = fBudget.value;
    const ok = isValidBudget(v);
    setValid(fBudget, ok);
    const wrapper = document.getElementById('budgetWrapper');
    if (wrapper) wrapper.classList.toggle('is-invalid', !ok);
    if (!ok) {
        if (!v.trim()) showError('errBudget', 'Budget is required.');
        else if (parseFloat(v.replace(',','.')) < 0) showError('errBudget', 'Budget must be ≥ 0.');
        else if (v.replace(',','.').split('.')[0]?.length > 8) showError('errBudget', 'Integer part too long (max 8 digits).');
        else showError('errBudget', 'Use format: 1500.00 (max 2 decimals).');
    } else showError('errBudget', '');
});

// Date: auto-format YYYY-MM-DD as user types
function onDateKeydown(e) {
    const sys = ['Backspace','Delete','Tab','Escape','Enter','ArrowLeft','ArrowRight','Home','End'];
    if (sys.includes(e.key) || e.ctrlKey || e.metaKey) return;
    if (e.key === '-') return;
    if (!/^\d$/.test(e.key)) e.preventDefault();
}
function onDateInput(input, errId) {
    let v = input.value.replace(/[^\d]/g,'');
    if (v.length > 4)  v = v.slice(0,4)+'-'+v.slice(4);
    if (v.length > 7)  v = v.slice(0,7)+'-'+v.slice(7);
    if (v.length > 10) v = v.slice(0,10);
    input.value = v;
    const ok = isValidDateStr(v);
    setValid(input, ok || !v);
    showError(errId, ok || !v ? '' : 'Invalid date. Use YYYY-MM-DD (e.g. 2025-12-31).');
    checkDateCoherence();
}
function checkDateCoherence() {
    const d1 = fDateDebut?.value?.trim();
    const d2 = fDateFin?.value?.trim();
    const msg = document.getElementById('errDateCoherence');
    if (msg && d1 && d2 && isValidDateStr(d1) && isValidDateStr(d2) && d2 < d1) {
        msg.classList.add('visible');
        setValid(fDateFin, false);
    } else if (msg) {
        msg.classList.remove('visible');
    }
}

fDateDebut && fDateDebut.addEventListener('keydown', onDateKeydown);
fDateFin   && fDateFin.addEventListener('keydown', onDateKeydown);
fDateDebut && fDateDebut.addEventListener('input', () => onDateInput(fDateDebut, 'errDateDebut'));
fDateFin   && fDateFin.addEventListener('input',   () => onDateInput(fDateFin, 'errDateFin'));

// Initialize counters if editing
if (fTitre)   updateCounter(fTitre, 'titreCounter');
if (fDesc)    updateCounter(fDesc, 'descCounter');
if (fObjectif) updateCounter(fObjectif, 'objCounter');

// Form submit validation
document.getElementById('campagneForm').addEventListener('submit', function(e) {
    let ok = true;
    const titre  = fTitre?.value?.trim() ?? '';
    const budget = fBudget?.value ?? '';
    const d1 = fDateDebut?.value?.trim();
    const d2 = fDateFin?.value?.trim();

    if (titre.length < 2 || stripHTML(titre)) {
        showError('errTitre', titre.length < 2 ? 'Title must be at least 2 characters.' : 'No HTML allowed.');
        setValid(fTitre, false);
        ok = false;
    }
    if (!isValidBudget(budget)) {
        showError('errBudget', 'Valid budget required (e.g. 1500.00).');
        const wrapper = document.getElementById('budgetWrapper');
        if (wrapper) wrapper.classList.add('is-invalid');
        ok = false;
    }
    if (d1 && !isValidDateStr(d1)) {
        showError('errDateDebut', 'Invalid date format.');
        ok = false;
    }
    if (d2 && !isValidDateStr(d2)) {
        showError('errDateFin', 'Invalid date format.');
        ok = false;
    }
    if (d1 && d2 && d2 < d1) {
        document.getElementById('errDateCoherence')?.classList.add('visible');
        ok = false;
    }
    if (!ok) {
        e.preventDefault();
        showToast('Please fix the form errors before submitting.', 'error');
        const firstErr = document.querySelector('.is-invalid');
        if (firstErr) firstErr.scrollIntoView({ behavior:'smooth', block:'center' });
    }
});

/* ════════════════════════════════════════════════
   TABS
════════════════════════════════════════════════ */
function switchTab(name, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-'+name).classList.add('active');
    btn.classList.add('active');
}

/* ════════════════════════════════════════════════
   SEARCH + FILTER + SORT
════════════════════════════════════════════════ */
document.getElementById('searchInput').addEventListener('input', filterTable);
let sortCol = null, sortDir = 'asc';

function filterTable() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    const s = document.getElementById('filterStatut').value;
    let visible = 0;
    document.querySelectorAll('#campagneBody tr').forEach(row => {
        const matchQ = !q || (row.dataset.titre||'').includes(q);
        const matchS = !s || row.dataset.statut === s;
        row.style.display = matchQ && matchS ? '' : 'none';
        if (matchQ && matchS) visible++;
    });
    document.getElementById('visibleBadge').textContent = visible;
}

function sortTable(col) {
    if (sortCol === col) sortDir = sortDir === 'asc' ? 'desc' : 'asc';
    else { sortCol = col; sortDir = 'asc'; }
    document.querySelectorAll('th[onclick]').forEach(th => th.classList.remove('sort-asc','sort-desc'));
    const th = document.querySelector(`th[onclick="sortTable('${col}')"]`);
    if (th) th.classList.add('sort-'+sortDir);
    const tbody = document.getElementById('campagneBody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    rows.sort((a,b) => {
        let va = a.dataset[col] || '';
        let vb = b.dataset[col] || '';
        if (col === 'budget') { va = parseFloat(va); vb = parseFloat(vb); }
        const res = va > vb ? 1 : va < vb ? -1 : 0;
        return sortDir === 'asc' ? res : -res;
    });
    rows.forEach(r => tbody.appendChild(r));
}

/* ════════════════════════════════════════════════
   BULK SELECTION
════════════════════════════════════════════════ */
document.getElementById('selectAll').addEventListener('change', function() {
    document.querySelectorAll('.row-check').forEach(c => {
        if (c.closest('tr').style.display !== 'none') c.checked = this.checked;
    });
    updateBulkBar();
});
document.addEventListener('change', e => {
    if (e.target.classList.contains('row-check')) updateBulkBar();
});
function updateBulkBar() {
    const checked = [...document.querySelectorAll('.row-check:checked')];
    const bar = document.getElementById('bulkBar');
    document.getElementById('bulkCount').textContent = checked.length + ' selected';
    bar.classList.toggle('visible', checked.length > 0);
}
function clearSelection() {
    document.querySelectorAll('.row-check, #selectAll').forEach(c => c.checked = false);
    document.getElementById('bulkBar').classList.remove('visible');
}
document.getElementById('btnBulkDelete').addEventListener('click', () => {
    const ids = [...document.querySelectorAll('.row-check:checked')].map(c => c.value);
    if (!ids.length) return;
    if (!confirm(`Delete ${ids.length} campaign(s)? This is irreversible.`)) return;
    const params = new URLSearchParams({ action_masse: 'supprimer_selection' });
    ids.forEach(id => params.append('selected_ids[]', id));
    fetch('index.php', { method:'POST', body:params, headers:{'Content-Type':'application/x-www-form-urlencoded'} })
        .then(() => { showToast(`${ids.length} campaign(s) deleted.`, 'danger'); setTimeout(() => location.reload(), 1200); });
});
document.getElementById('btnBulkArchive').addEventListener('click', async () => {
    const ids = [...document.querySelectorAll('.row-check:checked')].map(c => c.value);
    if (!ids.length) return;
    for (const id of ids) {
        await fetch('index.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'action=archive&id='+id });
    }
    showToast(`${ids.length} campaign(s) archived.`, 'warning');
    setTimeout(() => location.reload(), 1200);
});

/* ════════════════════════════════════════════════
   AJAX: archive + statut
════════════════════════════════════════════════ */
function toggleArchive(id, titre) {
    fetch('index.php', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=archive&id='+id})
        .then(() => { showToast(`"${titre}" archive status toggled.`, 'warning'); setTimeout(() => location.reload(), 1200); });
}
function changeStatut(id, statut, sel) {
    fetch('index.php', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=statut&id='+id+'&statut='+encodeURIComponent(statut)})
        .then(() => showToast(`Status updated to "${statut}".`, 'info'));
}

/* ════════════════════════════════════════════════
   DELETE MODAL
════════════════════════════════════════════════ */
function confirmDelete(id, titre) {
    document.getElementById('modalTitle').textContent = '🗑 Confirm deletion';
    document.getElementById('modalText').textContent = `Delete campaign "${titre}"? This action cannot be undone.`;
    document.getElementById('modalConfirmLink').href = 'index.php?delete='+id;
    document.getElementById('confirmModal').classList.add('open');
}
function closeModal() { document.getElementById('confirmModal').classList.remove('open'); }
document.getElementById('confirmModal').addEventListener('click', e => { if (e.target.id==='confirmModal') closeModal(); });

/* ════════════════════════════════════════════════
   PRODUCTS PANEL
════════════════════════════════════════════════ */
let currentCampagneId = null;
function openProductsPanel(idCampagne, titreCampagne) {
    currentCampagneId = idCampagne;
    document.getElementById('ppTitle').textContent = '📦 Products — '+titreCampagne;
    document.getElementById('ppBody').innerHTML = '<div class="pp-loader">⏳ Loading products…</div>';
    document.getElementById('productsPanelOverlay').classList.add('open');
    loadPanelProducts(idCampagne);
}
function closeProductsPanel() { document.getElementById('productsPanelOverlay').classList.remove('open'); }
function closePanelOutside(e) { if (e.target.id==='productsPanelOverlay') closeProductsPanel(); }
function loadPanelProducts(id) {
    fetch('index.php?ajax_produits='+id).then(r=>r.json()).then(d => renderPanel(id, d.lies, d.dispos));
}
function renderPanel(id, lies, dispos) {
    const body = document.getElementById('ppBody');
    let html = '';
    html += `<div><div class="pp-section-label">Linked products (${lies.length})</div><div class="pp-product-list">`;
    if (!lies.length) html += '<div class="pp-empty">No products linked yet.</div>';
    else lies.forEach(p => {
        const img = p.image ? `<img src="${BASE_URL}/Vue/public/produits/${p.image}" alt="">` : '📦';
        html += `<div class="pp-product-item">
            <div class="pp-product-thumb">${img}</div>
            <div class="pp-product-info"><div class="pp-product-name">${escHtml(p.nomProduit)}</div><div class="pp-product-meta">#${p.idProduit}${p.categorie?' · '+escHtml(p.categorie):''}</div></div>
            <span class="pp-price">${parseFloat(p.prix).toFixed(2)} €</span>
            <button class="btn-pp-remove" onclick="retirerProduit(${id},${p.idProduit})">✕</button>
        </div>`;
    });
    html += '</div></div>';
    html += `<div><div class="pp-section-label">Add a product</div>`;
    if (!dispos.length) html += '<div class="pp-empty">All active products already linked.</div>';
    else {
        html += '<div class="pp-add-row"><select class="pp-select" id="ppSelectProduit"><option value="">— Select —</option>';
        dispos.forEach(p => { html += `<option value="${p.idProduit}">${escHtml(p.nomProduit)} — ${parseFloat(p.prix).toFixed(2)} €</option>`; });
        html += `</select><button class="btn-pp-add" onclick="ajouterProduit(${id})">+ Link</button></div>`;
    }
    html += '</div>';
    body.innerHTML = html;
}
function ajouterProduit(id) {
    const sel = document.getElementById('ppSelectProduit');
    if (!sel?.value) return;
    fetch('index.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`action=lier_produit&idCampagne=${id}&idProduit=${sel.value}`})
        .then(r=>r.json()).then(d => { if(d.ok){ loadPanelProducts(id); showToast('Product linked!','success'); } });
}
function retirerProduit(id, idP) {
    if (!confirm('Remove this product?')) return;
    fetch('index.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`action=retirer_produit&idCampagne=${id}&idProduit=${idP}`})
        .then(r=>r.json()).then(d => { if(d.ok){ loadPanelProducts(id); showToast('Product removed.','warning'); } });
}
function escHtml(s) { const d=document.createElement('div'); d.textContent=s||''; return d.innerHTML; }

document.addEventListener('keydown', e => { if(e.key==='Escape'){ closeModal(); closeProductsPanel(); } });
</script>
</body>
</html>