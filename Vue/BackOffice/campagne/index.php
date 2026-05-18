<?php
/**
 * Vue/BackOffice/campagne/index.php
 * Rôle : ADMIN — supervision de toutes les campagnes + analyse IA
 */

require_once __DIR__ . '/../layout/early-theme.php';
require_once __DIR__ . '/../../../Controleur/campagneC.php';
require_once __DIR__ . '/../../../Controleur/produitC.php';
require_once __DIR__ . '/../../../Modele/campagne.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$campagneC = new CampagneC();
$produitC  = new ProduitC();
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

$message     = '';
$messageType = '';
$iaResult    = null;
$iaError     = '';

if (isset($_GET['delete'])) {
    $campagneC->supprimerCampagne(intval($_GET['delete']));
    header('Location: index.php?deleted=1'); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'archive') {
    $campagneC->toggleArchive(intval($_POST['id']));
    echo json_encode(['ok' => true]); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'statut') {
    $campagneC->changerStatut(intval($_POST['id']), $_POST['statut'] ?? '');
    echo json_encode(['ok' => true]); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax_produits'])) {
    $idC = intval($_GET['ajax_produits']);
    echo json_encode([
        'lies'   => $produitC->getProduitsByCampagne($idC),
        'dispos' => $produitC->getProduitsDisponiblesPourCampagne($idC),
    ]); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'lier_produit') {
    $campagneC->ajouterProduitCampagne(intval($_POST['idCampagne']), intval($_POST['idProduit']));
    echo json_encode(['ok' => true]); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'retirer_produit') {
    $campagneC->retirerProduitCampagne(intval($_POST['idCampagne']), intval($_POST['idProduit']));
    echo json_encode(['ok' => true]); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'ia_analyser') {
    $id   = intval($_POST['id_campagne'] ?? 0);
    $camp = $campagneC->recupererCampagne($id);
    if ($camp) {
        $iaResult = $campagneC->analyserCampagneIA(
            $camp['titreCampagne'], $camp['description'] ?? '',
            floatval($camp['budget']), $camp['statut']
        );
        if (!$iaResult) $iaError = "Erreur IA. Réessayez.";
    } else { $iaError = "Campagne introuvable."; }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $titre  = trim($_POST['titre'] ?? '');
    $budget = str_replace(',', '.', $_POST['budget'] ?? '');
    $errors = [];
    if (strlen($titre) < 2) $errors[] = "Titre requis (min 2 car.).";
    if (!is_numeric($budget) || floatval($budget) < 0) $errors[] = "Budget invalide.";
    if (empty($errors)) {
        $campagne = new Campagne(null, htmlspecialchars($titre, ENT_QUOTES, 'UTF-8'), trim($_POST['description'] ?? ''),
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
        $campagne = new Campagne(null, htmlspecialchars($titre, ENT_QUOTES, 'UTF-8'), trim($_POST['description'] ?? ''),
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

$liste           = $campagneC->afficherCampagnes();
$listeArchives   = $campagneC->afficherCampagnesArchives();
$toutesCampagnes = $campagneC->afficherToutesCampagnes();
$statuts         = $campagneC->getStatuts();
$campagneUpdate  = null;
if (isset($_GET['edit'])) $campagneUpdate = $campagneC->recupererCampagne(intval($_GET['edit']));

$totalCampagnes = count($liste);
$totalArchives  = count($listeArchives);
$budgetTotal    = array_sum(array_column($liste, 'budget'));
$nbActives      = count(array_filter($liste, fn($c) => $c['statut'] === 'active'));
$nbBrouillons   = count(array_filter($liste, fn($c) => $c['statut'] === 'brouillon'));
$nbTerminees    = count(array_filter($liste, fn($c) => $c['statut'] === 'terminee'));

function statutLabel($s) { return match($s) { 'active'=>'✅ Active','terminee'=>'🏁 Terminée','annulee'=>'❌ Annulée',default=>'📝 Brouillon' }; }
function statutClass($s) { return match($s) { 'active'=>'badge-success','terminee'=>'badge-info','annulee'=>'badge-danger',default=>'badge-warning' }; }
function campagneAssetVersion($path) {
    return is_file($path) ? '?v=' . urlencode((string) filemtime($path)) : '';
}
if (!headers_sent()) header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<?php cre8_bo_early_theme_print_head_script(); ?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gestion Campagnes — Admin · Cre8Connect</title>
<link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@300;400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/backoffice.css<?= campagneAssetVersion(__DIR__ . '/../css/backoffice.css') ?>">
<link rel="stylesheet" href="../layout/back-layout.css<?= campagneAssetVersion(__DIR__ . '/../layout/back-layout.css') ?>">
<link rel="stylesheet" href="../utilisateur/assets/vendors/mdi/css/materialdesignicons.min.css<?= campagneAssetVersion(__DIR__ . '/../utilisateur/assets/vendors/mdi/css/materialdesignicons.min.css') ?>">
<link rel="stylesheet" href="../css/new_style_backoffice.css<?= campagneAssetVersion(__DIR__ . '/../css/new_style_backoffice.css') ?>">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<style>
/* ===== DESIGN SYSTEM — Violet & Rose ONLY ===== */
:root {
    --bg-base:      #13111a;
    --bg-surface:   #1a1625;
    --bg-card:      #211c2f;
    --bg-hover:     #2a2340;
    --border:       #2e2845;
    --border-light: #3d3560;

    --accent:       #a855f7;
    --accent-soft:  rgba(168,85,247,.18);
    --accent-hover: #c084fc;
    --rose:         #ec4899;
    --rose-soft:    rgba(236,72,153,.18);

    /* All semantic roles → violet or rose */
    --success:      #a855f7;
    --success-soft: rgba(168,85,247,.15);
    --warn:         #ec4899;
    --warn-soft:    rgba(236,72,153,.15);
    --danger:       #ec4899;
    --danger-soft:  rgba(236,72,153,.15);
    --info:         #c084fc;
    --info-soft:    rgba(192,132,252,.15);

    --text:         #f0eaff;
    --sub:          #9d8ec7;
    --muted:        #5c5280;

    --radius:       10px;
    --sidebar-w:    244px;
    --grad:         linear-gradient(135deg, #a855f7, #ec4899);
}

body.light-mode {
    --bg-base:      #fdf4ff;
    --bg-surface:   #ffffff;
    --bg-card:      #ffffff;
    --bg-hover:     #fce7f3;
    --border:       #f3c6e8;
    --border-light: #e879a4;
    --text:         #3b1e6e;
    --sub:          #9333ea;
    --muted:        #c084fc;
}

* { margin:0; padding:0; box-sizing:border-box; }
body {
    font-family: 'Source Sans Pro', sans-serif;
    background: var(--bg-base);
    color: var(--text);
    min-height: 100vh;
    font-size: 0.875rem;
    line-height: 1.5;
    transition: background .25s, color .25s;
}

/* ===== CAMPAIGN CONTENT WRAPPER ===== */
.campagne-admin {
    background: var(--bg-base);
    color: var(--text);
    width: 100%;
}

/* ===== CARDS ===== */
.card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    margin-bottom: 20px;
    transition: background .25s, border-color .25s;
}
.card-body  { padding: 20px; min-width: 0; }
.card-header {
    padding: 16px 20px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
}
.card-title { font-size: .95rem; font-weight: 700; margin:0; }
.card-meta  { font-size: .78rem; color: var(--muted); }

/* ===== KPI ===== */
.kpi-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:14px; margin-bottom:20px; }
.kpi-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 18px 16px;
    transition: background .25s, border-color .25s;
}
.kpi-label { font-size:.68rem; font-weight:700; letter-spacing:1.5px; text-transform:uppercase; color:var(--muted); }
.kpi-value { font-size:1.75rem; font-weight:800; line-height:1; margin-top:6px; }
.kpi-card.total  .kpi-value { color: var(--text); }
.kpi-card.active .kpi-value { color: var(--accent); }
.kpi-card.draft  .kpi-value { color: var(--rose); }
.kpi-card.ended  .kpi-value { color: var(--info); }
.kpi-card.budget .kpi-value { color: var(--accent); font-size:1.3rem; }
.kpi-card.arch   .kpi-value { color: var(--rose); }

/* ===== CHARTS ===== */
.charts-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:16px; margin-bottom:6px; }
.chart-inner { position:relative; height:180px; }
.chart-card-title { font-size:.7rem; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:var(--muted); margin-bottom:12px; }
.stats-toggle-btn {
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 5px 12px;
    font-size:.75rem;
    font-weight:700;
    cursor:pointer;
    color:var(--sub);
    font-family:inherit;
    transition: all .2s;
}
.stats-toggle-btn:hover { border-color:var(--accent); color:var(--accent); }

/* ===== IA PANEL ===== */
.ia-header { display:flex; align-items:center; gap:10px; margin-bottom:16px; }
.ia-header h2 { font-size:.95rem; font-weight:700; background:var(--grad); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }
.ia-form-row { display:flex; align-items:flex-end; gap:12px; flex-wrap:wrap; }
.ia-form-group { display:flex; flex-direction:column; gap:5px; flex:1; min-width:200px; }
.ia-form-group label { font-size:.75rem; font-weight:700; color:var(--sub); }

/* ===== FORM CONTROLS ===== */
.form-control {
    padding: 9px 12px;
    border: 1px solid var(--border);
    border-radius: 8px;
    font-family: inherit;
    font-size: .85rem;
    background: var(--bg-surface);
    color: var(--text);
    outline: none;
    transition: border-color .15s, background .25s;
    width: 100%;
}
.form-control:focus { border-color: var(--accent); }
textarea.form-control { resize:vertical; min-height:80px; }

/* ===== BUTTONS ===== */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 14px;
    border-radius: 8px;
    font-family: inherit;
    font-size: .8rem;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    border: 1px solid transparent;
    transition: all .15s;
}
.btn:hover { opacity:.85; }
.btn-sm { padding:5px 10px; font-size:.75rem; }
.btn-primary  { background:var(--grad); color:#fff; border:none; }
.btn-ia       { background:var(--grad); color:#fff; border:none; padding:9px 18px; }
.btn-edit     { background:var(--accent-soft); color:var(--accent); border-color:var(--accent); }
.btn-delete   { background:var(--rose-soft);   color:var(--rose);   border-color:var(--rose); }
.btn-archive  { background:var(--warn-soft);   color:var(--warn); }
.btn-restore  { background:var(--info-soft);   color:var(--info); }
.btn-export   { background:var(--bg-surface);  color:var(--sub); border:1px solid var(--border); }
.btn-cancel   { background:var(--bg-surface);  color:var(--sub); border:1px solid var(--border); }

/* ===== CONTROLS (lang, theme) ===== */
.btn-control {
    background: var(--bg-card);
    border: 1px solid var(--border);
    color: var(--text);
    padding: 6px 12px;
    border-radius: 8px;
    cursor: pointer;
    font-size: .8rem;
    font-family: inherit;
    font-weight: 600;
    transition: all .15s;
}
.btn-control:hover, .btn-control.active { background:var(--accent-soft); color:var(--accent); border-color:var(--accent); }

/* ===== TABLE ===== */
.table-responsive { overflow-x:auto; }
.table { width:100%; border-collapse:collapse; }
.table thead th {
    text-align:left;
    padding:11px 16px;
    font-size:.68rem;
    font-weight:700;
    letter-spacing:1.5px;
    text-transform:uppercase;
    color:var(--muted);
    border-bottom:1px solid var(--border);
    white-space:nowrap;
}
.table tbody tr { transition:background .1s; }
.table tbody tr:hover td { background:var(--bg-hover); }
.table td {
    padding:13px 16px;
    font-size:.85rem;
    border-bottom:1px solid var(--border);
    vertical-align:middle;
}
.table tbody tr:last-child td { border-bottom:none; }

/* ===== BADGES ===== */
.badge {
    display:inline-flex; align-items:center; gap:4px;
    padding:3px 10px; border-radius:20px;
    font-size:.7rem; font-weight:700; letter-spacing:.3px;
    text-transform:uppercase;
}
.badge-success { background:var(--success-soft); color:var(--success); }
.badge-warning { background:var(--warn-soft);    color:var(--warn); }
.badge-danger  { background:var(--danger-soft);  color:var(--danger); }
.badge-info    { background:var(--info-soft);     color:var(--info); }

/* ===== STATUS SELECT ===== */
.statut-select {
    background:var(--bg-surface);
    border:1px solid var(--border-light);
    color:var(--text);
    font-family:inherit;
    font-size:.8rem;
    border-radius:8px;
    padding:5px 10px;
    cursor:pointer;
}

/* ===== ALERT ===== */
.alert {
    display:flex; align-items:center; gap:9px;
    padding:12px 16px; border-radius:10px;
    font-size:.85rem; font-weight:600;
    margin-bottom:18px; border:1px solid transparent;
}
.alert-success { background:var(--success-soft); color:var(--success); }
.alert-info    { background:var(--info-soft);     color:var(--info); }
.alert-danger, .alert-error { background:var(--danger-soft); color:var(--danger); }

/* ===== TABS ===== */
.tabs { display:flex; gap:4px; margin-bottom:16px; border-bottom:1px solid var(--border); }
.tab-btn {
    background:none; border:none;
    padding:9px 16px; font-size:.85rem; font-weight:700;
    color:var(--sub); cursor:pointer;
    border-bottom:2px solid transparent; margin-bottom:-1px;
    font-family:inherit; transition:color .15s;
}
.tab-btn.active { color:var(--accent); border-bottom-color:var(--accent); }
.tab-panel { display:none; }
.tab-panel.active { display:block; }

/* ===== SEARCH / FILTERS ===== */
.search-bar { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:14px; align-items:center; }

/* ===== PAGINATION ===== */
.pagination { display:flex; align-items:center; justify-content:center; gap:5px; padding:14px 0 4px; flex-wrap:wrap; }
.page-btn {
    background:var(--bg-surface); border:1px solid var(--border);
    border-radius:6px; padding:5px 12px;
    font-size:.78rem; font-weight:700; cursor:pointer;
    color:var(--sub); font-family:inherit; transition:all .15s;
}
.page-btn:hover:not(:disabled) { background:var(--accent-soft); color:var(--accent); border-color:var(--accent); }
.page-btn:disabled { opacity:.35; cursor:not-allowed; }
.page-btn.current { background:var(--grad); color:#fff; border:none; }
.page-info { font-size:.75rem; color:var(--muted); font-weight:600; padding:0 6px; }

/* ===== IA RESULT ===== */
.ia-result {
    background: var(--bg-surface);
    border: 1.5px solid var(--accent-soft);
    border-radius: var(--radius);
    padding: 20px;
    margin-top: 16px;
    width: 100%;
    box-sizing: border-box;
    clear: both;
    overflow: hidden;
}
.ia-result-title {
    display: block;
    font-size: .95rem;
    font-weight: 800;
    color: var(--accent);
    margin: 0 0 14px;
    line-height: 1.4;
}
.ia-field {
    display: block;
    width: 100%;
    min-width: 0;
    margin: 0 0 16px;
    clear: both;
}
.ia-field:last-child { margin-bottom: 0; }
.ia-label {
    display: block;
    width: 100%;
    font-size: .72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--muted);
    margin: 0 0 8px;
    line-height: 1.35;
}
.ia-value {
    display: block;
    width: 100%;
    font-size: .9rem;
    line-height: 1.6;
    color: var(--sub);
    overflow-wrap: anywhere;
    word-break: normal;
}
.ia-value.big {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 1.15rem;
    font-weight: 800;
    color: var(--accent);
    padding: 6px 12px;
    border-radius: 999px;
    background: var(--accent-soft);
}
.pill-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    width: 100%;
    align-items: flex-start;
    align-content: flex-start;
    min-width: 0;
}
.pill {
    display: inline-flex;
    align-items: center;
    max-width: 100%;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: .78rem;
    font-weight: 700;
    line-height: 1.35;
    white-space: normal;
    overflow-wrap: anywhere;
}
.pill-g { background:var(--success-soft); color:var(--success); }
.pill-r { background:var(--danger-soft);  color:var(--danger); }
.pill-w { background:var(--warn-soft);    color:var(--warn); }
.pill-a { background:var(--accent-soft);  color:var(--accent); }
.ia-error { background:var(--danger-soft); color:var(--danger); border-radius:8px; padding:10px 14px; font-size:.85rem; font-weight:600; margin-top:12px; }
.spinner { width:16px; height:16px; border:2.5px solid var(--accent-soft); border-top-color:var(--accent); border-radius:50%; animation:spin .6s linear infinite; }
@keyframes spin { to { transform:rotate(360deg); } }
.ia-loading { display:none; align-items:center; gap:10px; padding:10px 0; color:var(--accent); font-weight:600; font-size:.85rem; }
.ia-loading.show { display:flex; }

/* ===== FORM SECTION ===== */
.edit-banner {
    display:flex; align-items:center; justify-content:space-between;
    background:var(--accent-soft); border:1px solid rgba(168,99,255,.2);
    border-radius:8px; padding:9px 14px; margin-bottom:16px; font-size:.85rem;
}
.edit-banner a { color:var(--rose); text-decoration:none; font-weight:700; font-size:.78rem; }
.form-grid   { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.form-grid-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:14px; }
.form-group  { display:flex; flex-direction:column; gap:4px; }
.form-group label { font-size:.75rem; font-weight:700; color:var(--sub); }
.form-section-title { font-size:.95rem; font-weight:700; margin-bottom:16px; background:var(--grad); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }
.form-actions { display:flex; gap:10px; margin-top:18px; padding-top:14px; border-top:1px solid var(--border); }

/* ===== PRODUCT PANEL (drawer) ===== */
.pp-overlay { position:fixed; inset:0; background:rgba(0,0,0,.65); z-index:200; display:none; align-items:flex-start; justify-content:flex-end; }
.pp-overlay.open { display:flex; }
.pp-panel { width:440px; max-width:96vw; height:100vh; background:var(--bg-card); border-left:1px solid var(--border); display:flex; flex-direction:column; overflow:hidden; animation:slideInR .22s ease; }
@keyframes slideInR { from{transform:translateX(40px);opacity:0} to{transform:translateX(0);opacity:1} }
.pp-header { padding:18px 20px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; }
.pp-title { font-size:.95rem; font-weight:700; }
.pp-close { background:var(--bg-surface); border:1px solid var(--border); border-radius:50%; width:28px; height:28px; display:flex; align-items:center; justify-content:center; cursor:pointer; color:var(--sub); font-size:14px; }
.pp-body { flex:1; overflow-y:auto; padding:16px 20px; display:flex; flex-direction:column; gap:18px; }
.pp-section-label { font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:var(--muted); margin-bottom:8px; }
.pp-list { display:flex; flex-direction:column; gap:8px; }
.pp-item { display:flex; align-items:center; gap:10px; background:var(--bg-surface); border:1px solid var(--border); border-radius:10px; padding:10px 12px; }
.pp-thumb { width:38px; height:38px; border-radius:6px; background:var(--bg-hover); flex-shrink:0; display:flex; align-items:center; justify-content:center; font-size:16px; overflow:hidden; }
.pp-thumb img { width:100%; height:100%; object-fit:cover; }
.pp-info { flex:1; min-width:0; }
.pp-name { font-size:.85rem; font-weight:700; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.pp-price { font-size:.78rem; color:var(--accent); font-weight:700; margin-top:2px; }
.btn-pp-remove { background:var(--rose-soft); color:var(--rose); border:none; border-radius:8px; padding:4px 10px; font-size:.72rem; font-weight:700; cursor:pointer; font-family:inherit; }
.pp-add-row { display:flex; gap:8px; }
.pp-select { flex:1; background:var(--bg-surface); border:1px solid var(--border); border-radius:10px; padding:8px 10px; font-size:.82rem; color:var(--text); font-family:inherit; outline:none; }
.btn-pp-add { background:var(--grad); color:#fff; border:none; border-radius:10px; padding:8px 14px; font-size:.82rem; font-weight:700; cursor:pointer; font-family:inherit; }
.pp-empty { text-align:center; padding:22px; color:var(--muted); font-size:.85rem; }

/* Prod badge */
.prod-count-badge { display:inline-flex; align-items:center; gap:4px; background:var(--accent-soft); color:var(--accent); border-radius:20px; padding:3px 10px; font-size:.72rem; font-weight:700; cursor:pointer; border:none; font-family:inherit; }

/* Camp meta */
.camp-title { font-weight:700; }
.camp-obj { font-size:.75rem; color:var(--muted); margin-top:2px; max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.col-budget { color:var(--accent); font-family:monospace; font-weight:600; }

/* ===== DELETE MODAL ===== */
.modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.65); z-index:300; display:none; align-items:center; justify-content:center; }
.modal-overlay.open { display:flex; }
.modal-box { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); padding:28px; width:400px; max-width:94vw; animation:popIn .22s ease; }
@keyframes popIn { from{opacity:0;transform:scale(.93)} to{opacity:1;transform:scale(1)} }
.modal-title { font-size:1rem; font-weight:700; margin-bottom:8px; }
.modal-text  { font-size:.875rem; color:var(--sub); margin-bottom:22px; line-height:1.6; }
.modal-actions { display:flex; gap:10px; justify-content:flex-end; }
.btn-modal-cancel  { background:var(--bg-surface); color:var(--sub); border:1px solid var(--border); border-radius:8px; padding:8px 16px; font-size:.85rem; cursor:pointer; font-family:inherit; }
.btn-modal-confirm { background:var(--rose); color:#fff; border:none; border-radius:8px; padding:8px 18px; font-size:.85rem; font-weight:700; cursor:pointer; font-family:inherit; }



/* ===== LANGUAGE SWITCHER TOOLBAR ===== */
.translation-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    margin: 0 0 18px;
}
.page-heading { display:flex; flex-direction:column; gap:4px; min-width:0; }
.page-title { font-size:1.35rem; font-weight:800; color:var(--text); margin:0; }
.lang-switcher,
.lang-switcher.bo-lang-switch {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem;
    border: 1px solid rgba(124, 92, 255, 0.35);
    border-radius: 999px;
    background: rgba(124, 92, 255, 0.08);
}
.lang-btn {
    border: 0;
    border-radius: 999px;
    padding: 0.4rem 0.7rem;
    background: transparent;
    color: inherit;
    font-family: inherit;
    font-size: 0.76rem;
    font-weight: 800;
    cursor: pointer;
    transition: background .16s ease, border-color .16s ease, color .16s ease;
}
.lang-btn:hover {
    color: var(--accent);
}
.lang-btn.active {
    background: #8b5cf6;
    color: #ffffff;
    border: 0;
}
.page-subtitle {
    margin: 0;
    font-size: 0.9rem;
    color: var(--sub);
    font-weight: 500;
}

@media (max-width:992px) {
    .campagne-admin .kpi-grid { grid-template-columns:repeat(2,1fr); }
    .campagne-admin .charts-grid { grid-template-columns:1fr; }
    .campagne-admin .form-grid, .campagne-admin .form-grid-3 { grid-template-columns:1fr; }
}

/* =========================================================
   CRE8 CAMPAIGN / CONTRACT SHELL FIX
   Remove iframe-like outer frame by unifying page canvas.
   ========================================================= */

body.cre8-admin-layout,
body.cre8-admin-layout .container-scroller,
body.cre8-admin-layout .page-body-wrapper,
body.cre8-admin-layout .main-panel,
body.cre8-admin-layout .content-wrapper {
  background: var(--bg-base) !important;
  background-color: var(--bg-base) !important;
}

body.cre8-admin-layout .campagne-admin,
body.cre8-admin-layout .contrat-admin {
  background: var(--bg-base) !important;
  background-color: var(--bg-base) !important;
}

html[data-theme="light"] body.cre8-admin-layout,
body.cre8-admin-layout.light-mode,
body.light-mode.cre8-admin-layout {
  --bg-base: #f6f7fb !important;
  background: #f6f7fb !important;
  background-color: #f6f7fb !important;
}

html[data-theme="light"] body.cre8-admin-layout .container-scroller,
html[data-theme="light"] body.cre8-admin-layout .page-body-wrapper,
html[data-theme="light"] body.cre8-admin-layout .main-panel,
html[data-theme="light"] body.cre8-admin-layout .content-wrapper,
html[data-theme="light"] body.cre8-admin-layout .campagne-admin,
html[data-theme="light"] body.cre8-admin-layout .contrat-admin,
body.cre8-admin-layout.light-mode .container-scroller,
body.cre8-admin-layout.light-mode .page-body-wrapper,
body.cre8-admin-layout.light-mode .main-panel,
body.cre8-admin-layout.light-mode .content-wrapper,
body.cre8-admin-layout.light-mode .campagne-admin,
body.cre8-admin-layout.light-mode .contrat-admin,
body.light-mode.cre8-admin-layout .container-scroller,
body.light-mode.cre8-admin-layout .page-body-wrapper,
body.light-mode.cre8-admin-layout .main-panel,
body.light-mode.cre8-admin-layout .content-wrapper,
body.light-mode.cre8-admin-layout .campagne-admin,
body.light-mode.cre8-admin-layout .contrat-admin {
  background: #f6f7fb !important;
  background-color: #f6f7fb !important;
}



/* === Cre8Connect shared chrome hard lock V3 ==============================
   Final override for BackOffice shell only.
   This fixes the small header/sidebar drift caused by module CSS loading
   after the shared layout (Collaboration + Business pages). It intentionally
   targets only the shared chrome, not the page content. */
body.cre8-admin-layout #sidebar,
body.cre8-admin-layout #sidebar .nav,
body.cre8-admin-layout #sidebar .nav .nav-link,
body.cre8-admin-layout #sidebar .nav .menu-title,
body.cre8-admin-layout #sidebar .nav .nav-category .nav-link,
body.cre8-admin-layout #sidebar .profile-name h5,
body.cre8-admin-layout #sidebar .profile-name span,
body.cre8-admin-layout .navbar.fixed-top,
body.cre8-admin-layout .navbar.fixed-top .navbar-menu-wrapper,
body.cre8-admin-layout .navbar.fixed-top .nav-link,
body.cre8-admin-layout .navbar.fixed-top .navbar-profile-name,
body.cre8-admin-layout .navbar.fixed-top .cre8-profile-name,
body.cre8-admin-layout .navbar.fixed-top .cre8-role-badge,
body.cre8-admin-layout .navbar.fixed-top .cre8-lang-btn,
body.cre8-admin-layout .navbar.fixed-top .cre8-workspace-search input {
  font-family: "Segoe UI", Arial, sans-serif !important;
}

body.cre8-admin-layout #sidebar .nav {
  padding: 0.85rem 0 1.2rem !important;
  gap: 0 !important;
}

body.cre8-admin-layout #sidebar .nav .nav-category {
  margin: 0.18rem 0 0.35rem !important;
}

body.cre8-admin-layout #sidebar .nav .nav-category .nav-link {
  min-height: auto !important;
  padding: 0.25rem 1.68rem 0.45rem !important;
  background: transparent !important;
  color: #e8eefc !important;
  font-size: 0.78rem !important;
  font-weight: 900 !important;
  line-height: 1.15 !important;
  letter-spacing: 0.045em !important;
  text-transform: uppercase !important;
  border-radius: 0 !important;
  box-shadow: none !important;
}

body.cre8-admin-layout #sidebar .nav .nav-item.menu-items {
  margin: 0.05rem 0 !important;
}

body.cre8-admin-layout #sidebar .nav .nav-item.menu-items > .nav-link {
  min-height: 3.2rem !important;
  height: 3.2rem !important;
  padding: 0.28rem 1rem 0.28rem 1.35rem !important;
  border-radius: 0 999px 999px 0 !important;
  display: flex !important;
  align-items: center !important;
  gap: 0.66rem !important;
  box-shadow: none !important;
}

body.cre8-admin-layout #sidebar .nav .nav-item.menu-items .menu-icon {
  width: 2.75rem !important;
  height: 2.75rem !important;
  min-width: 2.75rem !important;
  flex: 0 0 2.75rem !important;
  border-radius: 999px !important;
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  margin-right: 0 !important;
}

body.cre8-admin-layout #sidebar .nav .nav-item.menu-items .menu-icon i {
  font-family: "Material Design Icons" !important;
  font-size: 1.05rem !important;
  line-height: 1 !important;
}

body.cre8-admin-layout #sidebar .nav .nav-item.menu-items .menu-title {
  color: inherit !important;
  font-size: 0.98rem !important;
  font-weight: 900 !important;
  line-height: 1.12 !important;
  letter-spacing: -0.01em !important;
  text-transform: none !important;
  white-space: nowrap !important;
}

body.cre8-admin-layout #sidebar .nav .nav-item.menu-items.active > .nav-link {
  background: #11131a !important;
  color: #ffffff !important;
}

body.cre8-admin-layout #sidebar .nav .nav-item.menu-items.active > .nav-link::before {
  content: "" !important;
  position: absolute !important;
  left: 0 !important;
  top: 0 !important;
  bottom: 0 !important;
  width: 3px !important;
  border-radius: 0 999px 999px 0 !important;
  background: #0090e7 !important;
}

body.cre8-admin-layout .navbar.fixed-top,
body.cre8-admin-layout .navbar.fixed-top .navbar-menu-wrapper {
  min-height: 4.45rem !important;
  height: 4.45rem !important;
}

body.cre8-admin-layout .navbar.fixed-top .navbar-profile-name,
body.cre8-admin-layout .navbar.fixed-top .cre8-profile-name,
body.cre8-admin-layout .navbar.fixed-top .nav-link,
body.cre8-admin-layout .navbar.fixed-top .cre8-lang-btn,
body.cre8-admin-layout .navbar.fixed-top .cre8-role-badge {
  font-size: 0.94rem !important;
  font-weight: 800 !important;
  line-height: 1.2 !important;
}

html[data-theme="light"] body.cre8-admin-layout #sidebar .nav .nav-category .nav-link,
body.cre8-admin-layout.light-mode #sidebar .nav .nav-category .nav-link,
body.light-mode.cre8-admin-layout #sidebar .nav .nav-category .nav-link {
  color: #334155 !important;
}

html[data-theme="light"] body.cre8-admin-layout #sidebar .nav .nav-item.menu-items.active > .nav-link,
body.cre8-admin-layout.light-mode #sidebar .nav .nav-item.menu-items.active > .nav-link,
body.light-mode.cre8-admin-layout #sidebar .nav .nav-item.menu-items.active > .nav-link {
  background: #f8f9fa !important;
  color: #0f172a !important;
}
/* === /Cre8Connect shared chrome hard lock V3 ============================= */



/* Cre8 business sidebar vertical alignment micro-fix.
   These pages have local reset CSS, so keep the shared sidebar list
   visually aligned with the other BackOffice pages. */
body.cre8-admin-layout #sidebar .nav {
  padding-top: 1.08rem !important;
}
</style>
<link rel="stylesheet" href="../business-center-admin.css<?= campagneAssetVersion(__DIR__ . '/../business-center-admin.css') ?>">
<link rel="stylesheet" href="../unified-table-admin.css<?= campagneAssetVersion(__DIR__ . '/../unified-table-admin.css') ?>">
<link rel="icon" type="image/png" sizes="16x16" href="../../public/images/favicon-16.png">
<link rel="icon" type="image/png" sizes="32x32" href="../../public/images/favicon-32.png">
<link rel="shortcut icon" type="image/png" href="../../public/images/favicon-32.png">
<link rel="apple-touch-icon" sizes="180x180" href="../../public/images/apple-touch-icon.png">
</head>
<body class="cre8-admin-layout"><?php cre8_bo_early_theme_print_body_script(); ?>

<div class="container-scroller cre8-admin-page">
<?php
$backActive = 'campaigns';
require_once __DIR__ . '/../layout/sidebar.php';
?>
<div class="container-fluid page-body-wrapper cre8-admin-main">
<?php require_once __DIR__ . '/../layout/header.php'; ?>
    <div class="main-panel">
    <div class="content-wrapper business-center-shell">
        <div class="campagne-admin">

        <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>" id="alertMsg">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php endif; ?>


        <section class="bc-page-head">
            <div>
                <p class="bc-kicker" data-i18n="businessKicker">Business Center</p>
                <h1 data-i18n="pageTitle">Campaign administration</h1>
                <p data-i18n="pageSubtitle">Create, analyze, archive and moderate brand campaigns.</p>
            </div>

        </section>

        <nav class="bc-entity-tabs" aria-label="Business Center sections">
            <a class="bc-entity-tab is-active" href="../campagne/index.php" aria-current="page">
                <span class="bc-tab-icon"><i class="mdi mdi-bullhorn-outline"></i></span>
                <span><strong data-i18n="businessTabCampaigns">Campaigns</strong><small data-i18n="businessSubCampaigns">Campaign planning and moderation</small></span>
            </a>
            <a class="bc-entity-tab " href="../produit/index.php" >
                <span class="bc-tab-icon"><i class="mdi mdi-package-variant-closed"></i></span>
                <span><strong data-i18n="businessTabProducts">Products</strong><small data-i18n="businessSubProducts">Catalog, images and product data</small></span>
            </a>
            <a class="bc-entity-tab " href="../contrat/index.php" >
                <span class="bc-tab-icon"><i class="mdi mdi-file-document-edit-outline"></i></span>
                <span><strong data-i18n="businessTabContracts">Contracts</strong><small data-i18n="businessSubContracts">Contract status and value tracking</small></span>
            </a>
        </nav>

        <!-- KPI -->
        <div class="kpi-grid">
            <div class="kpi-card total"><div class="kpi-label" data-i18n="kpiTotal">Total actives</div><div class="kpi-value"><?= $totalCampagnes ?></div></div>
            <div class="kpi-card active"><div class="kpi-label" data-i18n="kpiActive">Actives</div><div class="kpi-value"><?= $nbActives ?></div></div>
            <div class="kpi-card draft"><div class="kpi-label" data-i18n="kpiDraft">Brouillons</div><div class="kpi-value"><?= $nbBrouillons ?></div></div>
            <div class="kpi-card ended"><div class="kpi-label" data-i18n="kpiEnded">Terminées</div><div class="kpi-value"><?= $nbTerminees ?></div></div>
            <div class="kpi-card budget"><div class="kpi-label" data-i18n="kpiBudget">Budget total</div><div class="kpi-value"><?= number_format($budgetTotal,0,',',' ') ?> €</div></div>
            <div class="kpi-card arch"><div class="kpi-label" data-i18n="kpiArchived">Archivées</div><div class="kpi-value"><?= $totalArchives ?></div></div>
        </div>

        <!-- STATS CHARTS -->
        <section class="bc-statistics-panel" id="statsSection" data-bc-stats>
            <div class="bc-section-head">
                <div>
                    <h2>
                    <i class="fas fa-chart-bar"></i> <span data-i18n="statsTitle">Statistiques dynamiques</span></h2>
                    <p data-i18n="statsSubtitle">Campaign distribution, archive split and budget overview.</p>
                </div>
                <button type="button" class="bc-secondary-btn" data-bc-stats-toggle data-label-hide="Hide statistics" data-label-show="Show statistics" id="statsToggleBtn" data-i18n="statsHide">Hide statistics</button>
            </div>
            <div class="bc-stats-body" id="statsBody">
                <div class="charts-grid">
                    <div>
                        <div class="chart-card-title" data-i18n="chartStatusTitle">Répartition par statut</div>
                        <div class="chart-inner"><canvas id="chartStatut"></canvas></div>
                    </div>
                    <div>
                        <div class="chart-card-title" data-i18n="chartActiveArchiveTitle">Actives vs Archivées</div>
                        <div class="chart-inner"><canvas id="chartActiveArchive"></canvas></div>
                    </div>
                    <div>
                        <div class="chart-card-title" data-i18n="chartBudgetTitle">Budget par statut (€)</div>
                        <div class="chart-inner"><canvas id="chartBudget"></canvas></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- IA ANALYSE -->
        <div class="card">
            <div class="card-body">
                <div class="ia-header">
                    <span style="font-size:20px">🧠</span>
                    <h2 data-i18n="iaTitle">Analyser une campagne avec l'IA</h2>
                </div>
                <form method="POST" id="iaForm">
                    <input type="hidden" name="action" value="ia_analyser">
                    <div class="ia-form-row">
                        <div class="ia-form-group">
                            <label data-i18n="iaSelectLabel">Sélectionner une campagne</label>
                            <select name="id_campagne" class="form-control" required>
                                <option value="" data-i18n="iaSelectPlaceholder">— Choisir —</option>
                                <?php foreach ($toutesCampagnes as $c): ?>
                                <option value="<?= $c['idCampagne'] ?>" <?= (isset($_POST['id_campagne']) && intval($_POST['id_campagne']) === (int)$c['idCampagne']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['titreCampagne'], ENT_QUOTES, 'UTF-8') ?> (<?= $c['statut'] ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-ia"
                                onclick="document.getElementById('iaLoading').classList.add('show')">
                            🧠 <span data-i18n="iaAnalyzeBtn">Analyser</span>
                        </button>
                    </div>
                </form>
                <div class="ia-loading" id="iaLoading">
                    <div class="spinner"></div>
                    <span data-i18n="iaLoading">Analyse IA en cours…</span>
                </div>
                <?php if ($iaError): ?>
                <div class="ia-error">⚠️ <?= htmlspecialchars($iaError, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <?php if ($iaResult): ?>
                <div class="ia-result">
                    <div class="ia-result-title" data-i18n="iaResultTitle">📊 Résultat de l'analyse</div>
                    <?php if (!empty($iaResult['score_qualite'])): ?><div class="ia-field"><div class="ia-label" data-i18n="iaScore">Score qualité</div><div class="ia-value big">⭐ <?= htmlspecialchars($iaResult['score_qualite'], ENT_QUOTES, 'UTF-8') ?> / 10</div></div><?php endif; ?>
                    <?php if (!empty($iaResult['points_forts'])): ?><div class="ia-field"><div class="ia-label">✅ Points forts</div><div class="pill-list"><?php foreach ($iaResult['points_forts'] as $p): ?><span class="pill pill-g"><?= htmlspecialchars($p, ENT_QUOTES, 'UTF-8') ?></span><?php endforeach; ?></div></div><?php endif; ?>
                    <?php if (!empty($iaResult['points_faibles'])): ?><div class="ia-field"><div class="ia-label">⚠️ Points faibles</div><div class="pill-list"><?php foreach ($iaResult['points_faibles'] as $p): ?><span class="pill pill-w"><?= htmlspecialchars($p, ENT_QUOTES, 'UTF-8') ?></span><?php endforeach; ?></div></div><?php endif; ?>
                    <?php if (!empty($iaResult['risques'])): ?><div class="ia-field"><div class="ia-label">🚨 Risques</div><div class="pill-list"><?php foreach ($iaResult['risques'] as $r): ?><span class="pill pill-r"><?= htmlspecialchars($r, ENT_QUOTES, 'UTF-8') ?></span><?php endforeach; ?></div></div><?php endif; ?>
                    <?php if (!empty($iaResult['recommandations'])): ?><div class="ia-field"><div class="ia-label">💡 Recommandations</div><div class="pill-list"><?php foreach ($iaResult['recommandations'] as $r): ?><span class="pill pill-a"><?= htmlspecialchars($r, ENT_QUOTES, 'UTF-8') ?></span><?php endforeach; ?></div></div><?php endif; ?>
                    <?php if (!empty($iaResult['budget_adequat'])): ?><div class="ia-field"><div class="ia-label">💰 Budget</div><div class="ia-value"><?= htmlspecialchars($iaResult['budget_adequat'], ENT_QUOTES, 'UTF-8') ?></div></div><?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- TABS -->
        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('active',this)" data-i18n-template="tabActive" data-i18n-count="<?= $totalCampagnes ?>">Actives (<?= $totalCampagnes ?>)</button>
            <button class="tab-btn" onclick="switchTab('archived',this)" data-i18n-template="tabArchived" data-i18n-count="<?= $totalArchives ?>">Archivées (<?= $totalArchives ?>)</button>
        </div>

        <!-- TAB ACTIVE -->
        <div class="tab-panel active" id="tab-active">
            <div class="search-bar">
                <input type="text" id="searchInput" class="form-control" style="width:200px" placeholder="Rechercher…" data-i18n-placeholder="searchPlaceholder">
                <select id="filterStatut" class="form-control" style="width:160px" onchange="filterAndPaginate()">
                    <option value="" data-i18n="filterAll">Tous statuts</option>
                    <?php foreach ($statuts as $s): ?>
                    <option value="<?= $s ?>"><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
                <select class="form-control" style="width:120px" id="perPageSelect" onchange="changePerPage()">
                    <option value="5">5 / page</option>
                    <option value="10" selected>10 / page</option>
                    <option value="20">20 / page</option>
                    <option value="50">50 / page</option>
                </select>
            </div>

            <div id="bcResultsRegion" class="bc-results-region">
            <div class="card bc-table-card">
                <div class="card-header">
                    <div class="card-title" data-i18n="panelTitle">Toutes les campagnes</div>
                    <div class="card-meta" id="visibleBadge"><?= $totalCampagnes ?> <span data-i18n="campaignCount">campagne(s)</span></div>
                </div>
                <div class="table-responsive">
                    <?php if (empty($liste)): ?>
                    <div style="text-align:center;padding:40px;color:var(--muted)" data-i18n="noCampaign">Aucune campagne.</div>
                    <?php else: ?>
                    <table class="table bc-table" id="campTable">
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
                            $nbProd  = $campagneC->compterProduitsCampagne($c['idCampagne']);
                            $today   = date('Y-m-d');
                            $expired = $c['dateFin'] && $c['dateFin'] < $today && $c['statut'] === 'active';
                        ?>
                        <tr data-statut="<?= $c['statut'] ?>"
                            data-titre="<?= strtolower(htmlspecialchars($c['titreCampagne'], ENT_QUOTES, 'UTF-8')) ?>"
                            data-budget="<?= $c['budget'] ?>">
                            <td>
                                <div class="camp-title"><?= htmlspecialchars($c['titreCampagne'], ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="camp-obj"><?= htmlspecialchars($c['objectif'] ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
                                <?php if ($expired): ?><div style="font-size:.7rem;color:var(--rose);font-weight:700;margin-top:3px">⚠ Expirée</div><?php endif; ?>
                            </td>
                            <td>
                                <select class="statut-select" onchange="changeStatut(<?= $c['idCampagne'] ?>,this.value)">
                                    <?php foreach ($statuts as $s): ?>
                                    <option value="<?= $s ?>" <?= $c['statut'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td style="font-size:.78rem;color:var(--sub)">
                                📅 <?= $c['dateDebut'] ?? '—' ?><br>
                                🏁 <?= $c['dateFin'] ?? '—' ?>
                            </td>
                            <td class="col-budget"><?= number_format((float)$c['budget'],2,',',' ') ?> €</td>
                            <td style="font-size:.82rem;color:var(--sub)"><?= htmlspecialchars($c['nomMarque'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <button class="prod-count-badge" onclick="openPP(<?= $c['idCampagne'] ?>,'<?= htmlspecialchars(addslashes($c['titreCampagne']), ENT_QUOTES, 'UTF-8') ?>')">
                                    📦 <?= $nbProd ?>
                                </button>
                            </td>
                            <td style="white-space:nowrap">
                                <a href="?edit=<?= $c['idCampagne'] ?>#formAnchor" class="btn btn-edit btn-sm"><i class="fas fa-pen"></i></a>
                                <button class="btn btn-archive btn-sm" onclick="ajaxArchive(<?= $c['idCampagne'] ?>)"><i class="fas fa-box-archive"></i></button>
                                <button class="btn btn-delete btn-sm" onclick="confirmDelete(<?= $c['idCampagne'] ?>,'<?= htmlspecialchars(addslashes($c['titreCampagne']), ENT_QUOTES, 'UTF-8') ?>')"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="pagination" id="paginationControls"></div>
                    <?php endif; ?>
                </div>
            </div>
            </div>
        </div>

        <!-- TAB ARCHIVÉES -->
        <div class="tab-panel" id="tab-archived">
            <div class="card">
                <div class="card-header">
                    <div class="card-title" data-i18n="panelArchivedTitle">Campagnes archivées</div>
                    <div class="card-meta"><?= $totalArchives ?></div>
                </div>
                <div class="table-responsive">
                    <?php if (empty($listeArchives)): ?>
                    <div style="text-align:center;padding:40px;color:var(--muted)" data-i18n="noArchived">Aucune campagne archivée.</div>
                    <?php else: ?>
                    <table class="table">
                        <thead><tr>
                            <th data-i18n="colTitle">Titre</th>
                            <th data-i18n="colStatus">Statut</th>
                            <th data-i18n="colBudget">Budget</th>
                            <th data-i18n="colActions">Actions</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($listeArchives as $c): ?>
                        <tr>
                            <td class="camp-title" style="opacity:.75"><?= htmlspecialchars($c['titreCampagne'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><span class="badge <?= statutClass($c['statut']) ?>"><?= statutLabel($c['statut']) ?></span></td>
                            <td class="col-budget"><?= number_format((float)$c['budget'],2,',',' ') ?> €</td>
                            <td>
                                <button class="btn btn-restore btn-sm" onclick="ajaxArchive(<?= $c['idCampagne'] ?>)" data-i18n="restoreBtn">
                                    <i class="fas fa-rotate-left"></i> Restaurer
                                </button>
                                <button class="btn btn-delete btn-sm" onclick="confirmDelete(<?= $c['idCampagne'] ?>,'<?= htmlspecialchars(addslashes($c['titreCampagne']), ENT_QUOTES, 'UTF-8') ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- FORM AJOUTER / MODIFIER -->
        <div class="card" id="formAnchor">
            <div class="card-body">
                <div class="form-section-title">
                    <?= $campagneUpdate ? '✏️ Modifier la campagne' : '➕ Ajouter une campagne' ?>
                </div>
                <?php if ($campagneUpdate): ?>
                <div class="edit-banner">
                    <span>Modification : <strong><?= htmlspecialchars($campagneUpdate['titreCampagne'], ENT_QUOTES, 'UTF-8') ?></strong></span>
                    <a href="index.php">✕ Annuler</a>
                </div>
                <?php endif; ?>
                <form method="POST" action="index.php">
                    <input type="hidden" name="action" value="<?= $campagneUpdate ? 'update' : 'add' ?>">
                    <?php if ($campagneUpdate): ?>
                    <input type="hidden" name="id" value="<?= $campagneUpdate['idCampagne'] ?>">
                    <input type="hidden" name="estArchive" value="<?= intval($campagneUpdate['estArchive'] ?? 0) ?>">
                    <?php endif; ?>
                    <div class="form-grid" style="margin-bottom:14px">
                        <div class="form-group">
                            <label data-i18n="labelTitle">Titre *</label>
                            <input type="text" name="titre" class="form-control" maxlength="100" required
                                   value="<?= $campagneUpdate ? htmlspecialchars($campagneUpdate['titreCampagne'], ENT_QUOTES, 'UTF-8') : '' ?>">
                        </div>
                        <div class="form-group">
                            <label data-i18n="labelStatus">Statut</label>
                            <select name="statut" class="form-control">
                                <?php foreach ($statuts as $s): ?>
                                <option value="<?= $s ?>" <?= ($campagneUpdate && $campagneUpdate['statut'] === $s) ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom:14px">
                        <label data-i18n="labelDesc">Description</label>
                        <textarea name="description" class="form-control"><?= $campagneUpdate ? htmlspecialchars($campagneUpdate['description'], ENT_QUOTES, 'UTF-8') : '' ?></textarea>
                    </div>
                    <div class="form-group" style="margin-bottom:14px">
                        <label data-i18n="labelObjective">Objectif</label>
                        <input type="text" name="objectif" class="form-control" maxlength="200"
                               value="<?= $campagneUpdate ? htmlspecialchars($campagneUpdate['objectif'] ?? '', ENT_QUOTES, 'UTF-8') : '' ?>">
                    </div>
                    <div class="form-grid-3" style="margin-bottom:14px">
                        <div class="form-group">
                            <label data-i18n="labelStart">Date début</label>
                            <input type="text" name="dateDebut" class="form-control" placeholder="AAAA-MM-JJ"
                                   value="<?= $campagneUpdate ? htmlspecialchars($campagneUpdate['dateDebut'] ?? '', ENT_QUOTES, 'UTF-8') : '' ?>">
                        </div>
                        <div class="form-group">
                            <label data-i18n="labelEnd">Date fin</label>
                            <input type="text" name="dateFin" class="form-control" placeholder="AAAA-MM-JJ"
                                   value="<?= $campagneUpdate ? htmlspecialchars($campagneUpdate['dateFin'] ?? '', ENT_QUOTES, 'UTF-8') : '' ?>">
                        </div>
                        <div class="form-group">
                            <label data-i18n="labelBudget">Budget (€) *</label>
                            <input type="text" name="budget" class="form-control" required
                                   value="<?= $campagneUpdate ? htmlspecialchars($campagneUpdate['budget'] ?? '', ENT_QUOTES, 'UTF-8') : '' ?>">
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <?= $campagneUpdate ? '💾 Enregistrer' : '✅ Ajouter' ?>
                        </button>
                        <?php if ($campagneUpdate): ?>
                        <a href="index.php" class="btn btn-cancel">Annuler</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
        </div><!-- /campagne-admin -->
    </div><!-- /content-wrapper -->
    </div><!-- /main-panel -->
</div><!-- /page-body-wrapper -->
</div><!-- /container-scroller -->

<!-- PRODUCTS DRAWER -->
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
        <div class="modal-title" data-i18n="modalTitle">Confirmer la suppression</div>
        <div class="modal-text" id="modalText"></div>
        <div class="modal-actions">
            <button class="btn-modal-cancel" onclick="closeModal()" data-i18n="modalCancel">Annuler</button>
            <a href="#" class="btn-modal-confirm" id="modalConfirmLink" data-i18n="modalConfirm">Supprimer</a>
        </div>
    </div>
</div>

<script src="../layout/back-layout.js<?= campagneAssetVersion(__DIR__ . '/../layout/back-layout.js') ?>"></script>
<script>
const BASE_URL = <?= json_encode($baseUrl, JSON_UNESCAPED_SLASHES) ?>;

// Alert auto-hide
const alertEl = document.getElementById('alertMsg');
if (alertEl) setTimeout(() => alertEl.style.display = 'none', 4500);

// TABS
function switchTab(name, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    btn.classList.add('active');
}

// AJAX
function ajaxArchive(id) {
    fetch('index.php', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=archive&id='+id})
        .then(() => location.reload());
}
function changeStatut(id, statut) {
    fetch('index.php', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=statut&id='+id+'&statut='+encodeURIComponent(statut)});
}

// DELETE MODAL
function confirmDelete(id, titre) {
    const t = currentLang === 'fr' ? `Supprimer "${titre}" ? Action irréversible.` : `Delete "${titre}"? This is irreversible.`;
    document.getElementById('modalText').textContent = t;
    document.getElementById('modalConfirmLink').href = 'index.php?delete=' + id;
    document.getElementById('confirmModal').classList.add('open');
}
function closeModal() { document.getElementById('confirmModal').classList.remove('open'); }
document.getElementById('confirmModal').addEventListener('click', e => { if (e.target.id === 'confirmModal') closeModal(); });

// PRODUCTS PANEL
function openPP(id, titre) {
    document.getElementById('ppTitle').textContent = '📦 ' + titre;
    document.getElementById('ppBody').innerHTML = '<div class="pp-empty">⏳ Chargement…</div>';
    document.getElementById('ppOverlay').classList.add('open');
    loadPP(id);
}
function closePP() { document.getElementById('ppOverlay').classList.remove('open'); }
function closePPOutside(e) { if (e.target.id === 'ppOverlay') closePP(); }
function loadPP(id) {
    fetch('index.php?ajax_produits=' + id).then(r => r.json()).then(d => renderPP(id, d.lies, d.dispos));
}
function renderPP(id, lies, dispos) {
    let html = `<div><div class="pp-section-label">Produits liés (${lies.length})</div><div class="pp-list">`;
    if (!lies.length) html += '<div class="pp-empty">Aucun produit lié.</div>';
    else lies.forEach(p => {
        const img = p.image ? `<img src="${BASE_URL}/Vue/public/produits/${encodeURIComponent(p.image)}" alt="">` : '📦';
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
    fetch('index.php', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`action=lier_produit&idCampagne=${id}&idProduit=${sel.value}`})
        .then(r => r.json()).then(d => { if (d.ok) loadPP(id); });
}
function retirerPP(id, idP) {
    if (!confirm('Retirer ce produit ?')) return;
    fetch('index.php', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`action=retirer_produit&idCampagne=${id}&idProduit=${idP}`})
        .then(r => r.json()).then(d => { if (d.ok) loadPP(id); });
}
function esc(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeModal(); closePP(); } });
<?php if ($campagneUpdate): ?>
document.addEventListener('DOMContentLoaded', () => document.getElementById('formAnchor').scrollIntoView({behavior:'smooth',block:'start'}));
<?php endif; ?>

// ── Shared BO language key (persists across Campaigns / Products / Contracts) ──
function cre8BoReadLang() {
    if (window.cre8BackGetLang) return window.cre8BackGetLang();
    return localStorage.getItem('cre8_back_lang')
        || localStorage.getItem('cre8_bo_lang')
        || localStorage.getItem('cre8_lang')
        || localStorage.getItem('cre8_lang_campagne')
        || localStorage.getItem('cre8_lang_produit')
        || localStorage.getItem('cre8_lang_contrat')
        || 'fr';
}
function cre8BoWriteLang(lang) {
    localStorage.setItem('cre8_back_lang', lang);
    localStorage.setItem('cre8_bo_lang', lang);
    localStorage.setItem('cre8_lang', lang);
}

// ── TRANSLATIONS ──────────────────────────────────────────────────
const translations = {
    fr: {
        pageTitle:'Administration des campagnes',
        pageSubtitle:'Creer, analyser, archiver et moderer les campagnes des marques.',
        businessKicker:'Business Center',
        businessTabCampaigns:'Campagnes',
        businessSubCampaigns:'Planification et moderation des campagnes',
        businessTabProducts:'Produits',
        businessSubProducts:'Catalogue, images et donnees produits',
        businessTabContracts:'Contrats',
        businessSubContracts:'Statuts et valeur des contrats',
        statsSubtitle:'Repartition des campagnes, archives et vue budgetaire.',
        exportCsv:'Export CSV', adminLabel:'Admin',
        kpiTotal:'Total actives', kpiActive:'Actives', kpiDraft:'Brouillons', kpiEnded:'Terminées', kpiBudget:'Budget total', kpiArchived:'Archivées',
        statsTitle:'Statistiques dynamiques', statsHide:'▲ Masquer', statsShow:'▼ Afficher',
        chartStatusTitle:'Répartition par statut', chartActiveArchiveTitle:'Actives vs Archivées', chartBudgetTitle:'Budget par statut (€)',
        iaTitle:"Analyser une campagne avec l'IA", iaSelectLabel:'Sélectionner une campagne', iaSelectPlaceholder:'— Choisir —',
        iaAnalyzeBtn:'Analyser', iaLoading:'Analyse IA en cours…', iaResultTitle:"📊 Résultat de l'analyse", iaScore:'Score qualité',
        tabActive:'Actives', tabArchived:'Archivées', filterAll:'Tous statuts', searchPlaceholder:'Rechercher…',
        panelTitle:'Toutes les campagnes', campaignCount:'campagne(s)', noCampaign:'Aucune campagne.', noArchived:'Aucune campagne archivée.',
        colTitle:'Titre', colStatus:'Statut', colDates:'Dates', colBudget:'Budget', colBrand:'Marque', colProducts:'Produits', colActions:'Actions',
        panelArchivedTitle:'Campagnes archivées', restoreBtn:'🔁 Restaurer',
        labelTitle:'Titre *', labelStatus:'Statut', labelDesc:'Description', labelObjective:'Objectif',
        labelStart:'Date début', labelEnd:'Date fin', labelBudget:'Budget (€) *',
        modalTitle:'Confirmer la suppression', modalCancel:'Annuler', modalConfirm:'Supprimer',
        themeLabel:'Mode clair', themeLabelDark:'Mode sombre',
        prevPage:'← Préc.', nextPage:'Suiv. →', pageOf:'Page', of:'sur', chartCanceled:'Annulée', chartBudgetDataset:'Budget (€)',
        perPageSuffix:' par page',
    },
    en: {
        pageTitle:'Campaign administration',
        pageSubtitle:'Create, analyze, archive and moderate brand campaigns.',
        businessKicker:'Business Center',
        businessTabCampaigns:'Campaigns',
        businessSubCampaigns:'Campaign planning and moderation',
        businessTabProducts:'Products',
        businessSubProducts:'Catalog, images and product data',
        businessTabContracts:'Contracts',
        businessSubContracts:'Contract status and value tracking',
        statsSubtitle:'Campaign distribution, archive split and budget overview.',
        exportCsv:'Export CSV', adminLabel:'Admin',
        kpiTotal:'Total active', kpiActive:'Active', kpiDraft:'Drafts', kpiEnded:'Completed', kpiBudget:'Total budget', kpiArchived:'Archived',
        statsTitle:'Dynamic Statistics', statsHide:'▲ Hide', statsShow:'▼ Show',
        chartStatusTitle:'Distribution by status', chartActiveArchiveTitle:'Active vs Archived', chartBudgetTitle:'Budget by status (€)',
        iaTitle:'Analyze a campaign with AI', iaSelectLabel:'Select a campaign', iaSelectPlaceholder:'— Choose —',
        iaAnalyzeBtn:'Analyze', iaLoading:'AI analysis in progress…', iaResultTitle:'📊 Analysis result', iaScore:'Quality score',
        tabActive:'Active', tabArchived:'Archived', filterAll:'All statuses', searchPlaceholder:'Search…',
        panelTitle:'All campaigns', campaignCount:'campaign(s)', noCampaign:'No campaigns.', noArchived:'No archived campaigns.',
        colTitle:'Title', colStatus:'Status', colDates:'Dates', colBudget:'Budget', colBrand:'Brand', colProducts:'Products', colActions:'Actions',
        panelArchivedTitle:'Archived campaigns', restoreBtn:'🔁 Restore',
        labelTitle:'Title *', labelStatus:'Status', labelDesc:'Description', labelObjective:'Objective',
        labelStart:'Start date', labelEnd:'End date', labelBudget:'Budget (€) *',
        modalTitle:'Confirm deletion', modalCancel:'Cancel', modalConfirm:'Delete',
        themeLabel:'Light mode', themeLabelDark:'Dark mode',
        prevPage:'← Prev', nextPage:'Next →', pageOf:'Page', of:'of', chartCanceled:'Canceled', chartBudgetDataset:'Budget (€)',
        perPageSuffix:' / page',
    }
};
let currentLang = cre8BoReadLang();

function setLang(lang) {
    if (window.cre8BackSetLang && window.cre8BackGetLang && window.cre8BackGetLang() !== lang) {
        window.cre8BackSetLang(lang);
        return;
    }
    currentLang = lang;
    cre8BoWriteLang(lang);
    applyTranslations();
    document.getElementById('langFR')?.classList.toggle('active', lang === 'fr');
    document.getElementById('langEN')?.classList.toggle('active', lang === 'en');
    updateTabLabels();
    renderPagination();
    if (typeof buildCharts === 'function') buildCharts();
}
function applyTranslations() {
    const T = translations[currentLang];
    document.querySelectorAll('[data-i18n]').forEach(el => { if (T[el.getAttribute('data-i18n')]) el.textContent = T[el.getAttribute('data-i18n')]; });
    document.querySelectorAll('[data-i18n-placeholder]').forEach(el => { const k = el.getAttribute('data-i18n-placeholder'); if (T[k]) el.setAttribute('placeholder', T[k]); });
    if (window.cre8BackApplyTranslations) window.cre8BackApplyTranslations();
    const isDark = !document.body.classList.contains('light-mode');
    const themeLabel = document.getElementById('themeLabel');
    if (themeLabel) themeLabel.textContent = isDark ? T.themeLabel : T.themeLabelDark;
    const statsVisible = document.getElementById('statsBody').style.display !== 'none';
    document.getElementById('statsToggleBtn').textContent = statsVisible ? T.statsHide : T.statsShow;
    const ps = T.perPageSuffix || ' / page';
    document.querySelectorAll('#perPageSelect option').forEach(opt => {
        opt.textContent = opt.value + ps;
    });
}
function updateTabLabels() {
    const T = translations[currentLang];
    document.querySelectorAll('[data-i18n-template]').forEach(el => {
        const key = el.getAttribute('data-i18n-template');
        const count = el.getAttribute('data-i18n-count');
        if (T[key]) el.textContent = `${T[key]} (${count})`;
    });
}

// ── THEME ─────────────────────────────────────────────────────────
function toggleTheme() {
    const theme = window.toggleBackOfficeTheme ? window.toggleBackOfficeTheme() : (document.body.classList.toggle('light-mode') ? 'light' : 'dark');
    const isLight = theme === 'light';
    const T = translations[currentLang];
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        themeToggle.innerHTML = isLight
            ? `☀️ <span id="themeLabel">${T.themeLabelDark}</span>`
            : `🌙 <span id="themeLabel">${T.themeLabel}</span>`;
    }
    buildCharts();
}
function initTheme() {
    if ((window.applyBackOfficeTheme ? window.applyBackOfficeTheme() : localStorage.getItem('cre8_theme')) === 'light') {
        document.body.classList.add('light-mode');
        const T = translations[currentLang];
        const themeToggle = document.getElementById('themeToggle');
        if (themeToggle) themeToggle.innerHTML = `☀️ <span id="themeLabel">${T.themeLabelDark}</span>`;
    }
}

// ── PAGINATION ────────────────────────────────────────────────────
let currentPage  = 1;
let rowsPerPage  = 10;
let filteredRows = [];

function getAllRows() { return Array.from(document.querySelectorAll('#campBody tr')); }

function filterAndPaginate() {
    const q = (document.getElementById('searchInput').value || '').toLowerCase();
    const s = document.getElementById('filterStatut').value;
    filteredRows = getAllRows().filter(row => {
        return (!q || (row.dataset.titre || '').includes(q)) && (!s || row.dataset.statut === s);
    });
    currentPage = 1;
    applyPagination();
}

function applyPagination() {
    getAllRows().forEach(r => r.style.display = 'none');
    const start = (currentPage - 1) * rowsPerPage;
    filteredRows.slice(start, start + rowsPerPage).forEach(r => r.style.display = '');
    const T = translations[currentLang];
    const badge = document.getElementById('visibleBadge');
    if (badge) badge.innerHTML = filteredRows.length + ' <span data-i18n="campaignCount">' + (T.campaignCount || 'campagne(s)') + '</span>';
    renderPagination();
}

function renderPagination() {
    const container = document.getElementById('paginationControls');
    if (!container) return;
    const T = translations[currentLang];
    const totalPages = Math.ceil(filteredRows.length / rowsPerPage) || 1;
    let html = `<button class="page-btn" onclick="goToPage(${currentPage-1})" ${currentPage<=1?'disabled':''}>${T.prevPage}</button>`;
    const range = 2;
    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= currentPage-range && i <= currentPage+range))
            html += `<button class="page-btn${i===currentPage?' current':''}" onclick="goToPage(${i})">${i}</button>`;
        else if (i === currentPage-range-1 || i === currentPage+range+1)
            html += `<span class="page-info">…</span>`;
    }
    html += `<button class="page-btn" onclick="goToPage(${currentPage+1})" ${currentPage>=totalPages?'disabled':''}>${T.nextPage}</button>`;
    html += `<span class="page-info">${T.pageOf} ${currentPage} ${T.of} ${totalPages}</span>`;
    container.innerHTML = html;
}

function goToPage(page) {
    const totalPages = Math.ceil(filteredRows.length / rowsPerPage) || 1;
    if (page < 1 || page > totalPages) return;
    currentPage = page; applyPagination();
}
function changePerPage() {
    rowsPerPage = parseInt(document.getElementById('perPageSelect').value) || 10;
    currentPage = 1; applyPagination();
}

// ── CHARTS ────────────────────────────────────────────────────────
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

function buildCharts() {
    const T = translations[currentLang] || translations.fr;
    // Violet/rose palette — only 2 hues + light variants
    const violet  = '#a855f7';
    const rose    = '#ec4899';
    const vSoft   = 'rgba(168,85,247,.25)';
    const rSoft   = 'rgba(236,72,153,.25)';
    const vLight  = '#c084fc';
    const vLighter= '#d8b4fe';

    const gridColor = getComputedStyle(document.body).getPropertyValue('--border').trim() || '#2e2845';
    const subColor  = getComputedStyle(document.body).getPropertyValue('--sub').trim()    || '#9d8ec7';

    [chartStatut, chartActiveArchive, chartBudget].forEach(ch => { if (ch) ch.destroy(); });

    const base = {
        responsive:true, maintainAspectRatio:false,
        plugins: { legend: { labels: { color:subColor, font:{size:11}, boxWidth:12 } } }
    };

    // Doughnut — statuts
    chartStatut = new Chart(document.getElementById('chartStatut'), {
        type: 'doughnut',
        data: {
            labels: [T.kpiActive, T.kpiDraft, T.kpiEnded, T.chartCanceled],
            datasets: [{
                data: [campData.active, campData.brouillon, campData.terminee, campData.annulee],
                backgroundColor: [violet, rose, vLight, vLighter],
                borderColor: 'transparent', hoverOffset:6
            }]
        },
        options: { ...base, cutout:'65%' }
    });

    // Bar — actives vs archivées
    chartActiveArchive = new Chart(document.getElementById('chartActiveArchive'), {
        type: 'bar',
        data: {
            labels: [T.kpiActive, T.kpiArchived],
            datasets: [{
                data: [campData.totalActive, campData.totalArchived],
                backgroundColor: [vSoft, rSoft],
                borderColor: [violet, rose],
                borderWidth: 2, borderRadius: 6
            }]
        },
        options: {
            ...base,
            scales: {
                x: { ticks:{color:subColor}, grid:{color:gridColor} },
                y: { ticks:{color:subColor}, grid:{color:gridColor}, beginAtZero:true }
            },
            plugins: { ...base.plugins, legend:{display:false} }
        }
    });

    // Bar — budgets
    chartBudget = new Chart(document.getElementById('chartBudget'), {
        type: 'bar',
        data: {
            labels: [T.kpiActive, T.kpiDraft, T.kpiEnded, T.chartCanceled],
            datasets: [{
                label: T.chartBudgetDataset || 'Budget (€)',
                data: [campData.budgets.active, campData.budgets.brouillon, campData.budgets.terminee, campData.budgets.annulee],
                backgroundColor: [vSoft, rSoft, 'rgba(192,132,252,.25)', 'rgba(236,72,153,.15)'],
                borderColor: [violet, rose, vLight, vLighter],
                borderWidth: 2, borderRadius: 6
            }]
        },
        options: {
            ...base,
            scales: {
                x: { ticks:{color:subColor}, grid:{color:gridColor} },
                y: { ticks:{color:subColor, callback:v=>v.toLocaleString(currentLang === 'fr' ? 'fr-FR' : 'en-US')+' €'}, grid:{color:gridColor}, beginAtZero:true }
            },
            plugins: { ...base.plugins, legend:{display:false} }
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
    btn.textContent = statsVisible ? T.statsHide : T.statsShow;
}

// ── INIT ──────────────────────────────────────────────────────────
window.addEventListener('cre8:themechange', buildCharts);

document.addEventListener('DOMContentLoaded', () => {
    initTheme();
    if (window.cre8BackRegisterTranslations) {
        window.cre8BackRegisterTranslations(translations);
    }
    currentLang = cre8BoReadLang();
    applyTranslations();
    updateTabLabels();
    document.getElementById('langFR')?.classList.toggle('active', currentLang === 'fr');
    document.getElementById('langEN')?.classList.toggle('active', currentLang === 'en');
    filteredRows = getAllRows();
    applyPagination();
    buildCharts();
    // Search
    const si = document.getElementById('searchInput');
    if (si) si.addEventListener('input', filterAndPaginate);
});

window.addEventListener('cre8:languagechange', function(event) {
    currentLang = (event.detail && event.detail.lang) || cre8BoReadLang();
    applyTranslations();
    updateTabLabels();
    renderPagination();
    if (typeof buildCharts === 'function') buildCharts();
});
</script>
<script src="../business-center-admin.js<?= campagneAssetVersion(__DIR__ . '/../business-center-admin.js') ?>"></script>
</body>
</html>
