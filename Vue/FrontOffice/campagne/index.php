<?php
/**
 * Vue/FrontOffice/campagne/index.php
 * Rôle : MARQUE — gestion de ses campagnes + génération IA
 * Enhanced with: Translation, Dark/Light Mode, Pagination
 */

require_once __DIR__ . '/../../../Controleur/campagneC.php';
require_once __DIR__ . '/../../../Controleur/produitC.php';
require_once __DIR__ . '/../../../Modele/campagne.php';
require_once __DIR__ . '/../layout/session_bridge.php';
$currentBrandUser = cre8_front_require_user('marque');


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
$id_marque = (int) ($currentBrandUser['id'] ?? 0);

$message     = '';
$messageType = '';
$editCampagne = null;
$iaResult    = null;
$iaError     = '';

// ── AJAX : toggle archive ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'archive') {
    $campagneC->toggleArchive(intval($_POST['id']));
    echo json_encode(['ok' => true]); exit;
}
// ── AJAX : lier produit ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'lier_produit') {
    $idC  = intval($_POST['idCampagne']);
    $idP  = intval($_POST['idProduit']);
    $camp = $campagneC->recupererCampagne($idC);
    if ($camp && (int)$camp['idMarque'] === $id_marque) {
        $campagneC->ajouterProduitCampagne($idC, $idP);
        echo json_encode(['ok' => true]);
    } else {
        echo json_encode(['ok' => false, 'error' => 'Non autorisé']);
    }
    exit;
}
// ── AJAX : retirer produit ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'retirer_produit') {
    $idC  = intval($_POST['idCampagne']);
    $idP  = intval($_POST['idProduit']);
    $camp = $campagneC->recupererCampagne($idC);
    if ($camp && (int)$camp['idMarque'] === $id_marque) {
        $campagneC->retirerProduitCampagne($idC, $idP);
        echo json_encode(['ok' => true]);
    } else {
        echo json_encode(['ok' => false, 'error' => 'Non autorisé']);
    }
    exit;
}
// ── AJAX : produits d'une campagne ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax_produits'])) {
    $idC  = intval($_GET['ajax_produits']);
    $camp = $campagneC->recupererCampagne($idC);
    if (!$camp || (int)$camp['idMarque'] !== $id_marque) {
        echo json_encode(['lies' => [], 'dispos' => []]); exit;
    }
    echo json_encode([
        'lies'   => $produitC->getProduitsByCampagne($idC),
        'dispos' => $produitC->getProduitsDisponiblesPourCampagne($idC, $id_marque),
    ]); exit;
}

// ── IA : GÉNÉRATION CAMPAGNE ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'ia_generer') {
    $produit = trim($_POST['ia_produit'] ?? '');
    $cible   = trim($_POST['ia_cible'] ?? '');
    $budget  = floatval($_POST['ia_budget'] ?? 0);
    if ($produit && $cible && $budget > 0) {
        $iaResult = $campagneC->genererCampagneIA($produit, $cible, $budget);
       if (!$iaResult) {
    $iaError = "L'IA n'a pas pu générer la campagne. Vérifiez les logs PHP (error_log).";
}
    } else {
        $iaError = "Remplissez tous les champs IA.";
    }
}

// ── ADD ───────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'ajouter') {
    $titre     = trim($_POST['titre'] ?? '');
    $budget    = str_replace(',', '.', $_POST['budget'] ?? '');
    $dateDebut = trim($_POST['dateDebut'] ?? '');
    $dateFin   = trim($_POST['dateFin'] ?? '');
    $errors    = [];
    if (strlen($titre) < 2 || strlen($titre) > 100) $errors[] = "Titre requis (2-100 car.).";
    if (preg_match('/<[^>]+>/', $titre))             $errors[] = "Pas de HTML dans le titre.";
    if (!is_numeric($budget) || floatval($budget) < 0) $errors[] = "Budget invalide.";
    if ($dateDebut && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateDebut)) $errors[] = "Date début : AAAA-MM-JJ.";
    if ($dateFin   && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFin))   $errors[] = "Date fin : AAAA-MM-JJ.";
    if ($dateDebut && $dateFin && $dateFin < $dateDebut) $errors[] = "La date de fin doit être après le début.";
    if (empty($errors)) {
        $campagne = new Campagne(null, htmlspecialchars($titre), trim($_POST['description'] ?? ''),
            $dateDebut ?: null, $dateFin ?: null, floatval($budget),
            $_POST['statut'] ?? 'brouillon', $id_marque,
            trim($_POST['objectif'] ?? ''), 0);
        $campagneC->ajouterCampagne($campagne);
        $message = "Campagne ajoutée avec succès !"; $messageType = "success";
    } else {
        $message = implode(' | ', $errors); $messageType = "error";
    }
}

// ── UPDATE ────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'modifier') {
    $titre  = trim($_POST['titre'] ?? '');
    $budget = str_replace(',', '.', $_POST['budget'] ?? '');
    $dateDebut = trim($_POST['dateDebut'] ?? '');
    $dateFin   = trim($_POST['dateFin'] ?? '');
    $errors = [];
    if (strlen($titre) < 2) $errors[] = "Titre requis (min. 2 car.).";
    if (!is_numeric($budget) || floatval($budget) < 0) $errors[] = "Budget invalide.";
    if ($dateDebut && $dateFin && $dateFin < $dateDebut) $errors[] = "La date de fin doit être après le début.";
    if (empty($errors)) {
        $campagne = new Campagne(null, htmlspecialchars($titre), trim($_POST['description'] ?? ''),
            $dateDebut ?: null, $dateFin ?: null, floatval($budget),
            $_POST['statut'] ?? 'brouillon', $id_marque,
            trim($_POST['objectif'] ?? ''), intval($_POST['estArchive'] ?? 0));
        $campagneC->modifierCampagne($campagne, intval($_POST['id']));
        $message = "Campagne modifiée !"; $messageType = "success";
    } else {
        $message = implode(' | ', $errors); $messageType = "error";
    }
}

// ── DELETE ─────────────────────────────────────────────────────────────────────
if (isset($_GET['supprimer'])) {
    $campagneC->supprimerCampagne(intval($_GET['supprimer']));
    $message = "Campagne supprimée."; $messageType = "delete";
}

// ── EDIT ──────────────────────────────────────────────────────────────────────
if (isset($_GET['modifier'])) {
    $editCampagne = $campagneC->recupererCampagne(intval($_GET['modifier']));
}

// ── DATA ──────────────────────────────────────────────────────────────────────
$campagnes         = $campagneC->afficherCampagnes($id_marque);
$campagnesArchives = $campagneC->afficherCampagnesArchives($id_marque);
$statuts           = $campagneC->getStatuts();

$totalActives  = count($campagnes);
$totalArchives = count($campagnesArchives);
$budgetTotal   = array_sum(array_column($campagnes, 'budget'));
$nbActives     = count(array_filter($campagnes, fn($c) => $c['statut'] === 'active'));
$nbBrouillons  = count(array_filter($campagnes, fn($c) => $c['statut'] === 'brouillon'));
$nbTerminees   = count(array_filter($campagnes, fn($c) => $c['statut'] === 'terminee'));

function statutLabel($s) { return match($s) { 'active'=>'✅ Active', 'terminee'=>'🏁 Terminée', 'annulee'=>'❌ Annulée', default=>'📝 Brouillon' }; }
function statutColor($s) { return match($s) { 'active'=>'#0ea370', 'terminee'=>'#3b82f6', 'annulee'=>'#f43f5e', default=>'#f59e0b' }; }
function statutBg($s)    { return match($s) { 'active'=>'#edfaf5', 'terminee'=>'#eff6ff', 'annulee'=>'#fff1f3', default=>'#fffbeb' }; }

$frontActive = 'campaigns';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<?php require_once __DIR__ . '/../layout/front-theme-bootstrap.php'; ?>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mes Campagnes — Cre8Connect</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:wght@700;800;900&display=swap" rel="stylesheet">
<style>
:root{
    --primary:#5b4fff;--primary-hover:#4438e0;--primary-light:#ece9ff;
    --primary-glow:rgba(91,79,255,0.15);--primary-border:rgba(91,79,255,0.2);
    --text-main:#0f0e1a;--text-sub:#6b6f80;--text-dim:#a0a4b2;
    --border:#ebebf2;--bg:#f6f6fc;--white:#ffffff;
    --danger:#f43f5e;--danger-light:#fff1f3;--danger-border:rgba(244,63,94,0.2);
    --success:#0ea370;--success-light:#edfaf5;--success-border:rgba(14,163,112,0.2);
    --warning:#f59e0b;--warning-light:#fffbeb;
    --card-shadow:0 1px 3px rgba(15,14,26,0.06),0 4px 16px rgba(91,79,255,0.06);
    --card-shadow-hover:0 8px 32px rgba(91,79,255,0.14);
    --radius:14px;--radius-sm:8px;--nav-h:66px;
}

/* ===== DARK MODE (match shared header: html[data-theme] + body sync) ===== */
html[data-theme="dark"],
body.dark-mode {
    --primary:#7c6fff;--primary-hover:#6357e8;--primary-light:#2a2660;
    --primary-glow:rgba(124,111,255,0.2);--primary-border:rgba(124,111,255,0.3);
    --text-main:#f0eeff;--text-sub:#a8a4c0;--text-dim:#6b6880;
    --border:#2e2b45;--bg:#12111e;--white:#1c1a2e;
    --danger:#f87191;--danger-light:#2a1520;--danger-border:rgba(248,113,145,0.25);
    --success:#34d399;--success-light:#0d2e22;--success-border:rgba(52,211,153,0.25);
    --warning:#fbbf24;--warning-light:#271d08;
    --card-shadow:0 1px 3px rgba(0,0,0,0.3),0 4px 16px rgba(0,0,0,0.2);
    --card-shadow-hover:0 8px 32px rgba(124,111,255,0.2);
}
/* ===== END DARK MODE VARIABLES ===== */

*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text-main);min-height:100vh;transition:background .3s,color .3s;}

/* NAV */
nav{background:var(--white);border-bottom:1px solid var(--border);padding:0 48px;height:var(--nav-h);display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:200;box-shadow:0 2px 12px rgba(15,14,26,0.04);transition:background .3s,border-color .3s;}
.nav-logo{font-family:'Fraunces',serif;font-size:20px;font-weight:800;color:var(--primary);text-decoration:none;}
.nav-links{display:flex;gap:6px;list-style:none;}
.nav-links a{text-decoration:none;color:var(--text-sub);font-size:13.5px;font-weight:600;padding:6px 14px;border-radius:8px;transition:all .18s;}
.nav-links a:hover,.nav-links a.active{background:var(--primary-light);color:var(--primary);}
.nav-right{display:flex;align-items:center;gap:12px;}
.nav-badge{background:var(--primary);color:#fff;border-radius:20px;padding:5px 14px;font-size:12px;font-weight:700;}
.nav-avatar{width:36px;height:36px;border-radius:50%;background:var(--primary-light);color:var(--primary);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:14px;border:2px solid var(--primary-border);}

/* ===== ADDED FEATURE: DARK MODE TOGGLE BUTTON ===== */
.theme-toggle {
    background: var(--bg);
    border: 1.5px solid var(--border);
    border-radius: 20px;
    padding: 5px 12px;
    font-size: 14px;
    cursor: pointer;
    color: var(--text-sub);
    font-family: 'DM Sans', sans-serif;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: all .2s;
    white-space: nowrap;
}
.theme-toggle:hover { border-color: var(--primary); color: var(--primary); }
/* ===== END DARK MODE TOGGLE ===== */

/* ===== FrontOffice EN | FR switch (scoped) ===== */
.campaign-front .page-header-right {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    flex-wrap: wrap;
    justify-content: flex-end;
}
.campaign-front .front-lang-switch {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem;
    border-radius: 999px;
    border: 1px solid rgba(139, 92, 246, 0.45);
    background: rgba(139, 92, 246, 0.08);
    flex-shrink: 0;
}
.campaign-front .front-lang-btn {
    border: 0;
    border-radius: 999px;
    background: transparent;
    color: var(--text-sub);
    font: inherit;
    font-size: 0.76rem;
    font-weight: 800;
    padding: 0.4rem 0.7rem;
    cursor: pointer;
    transition: background 0.15s, color 0.15s;
}
.campaign-front .front-lang-btn:hover { color: var(--primary); }
.campaign-front .front-lang-btn.is-active {
    background: #8b5cf6;
    color: #fff;
}
html[data-theme="dark"] .campaign-front .front-lang-btn:not(.is-active),
body.dark-mode .campaign-front .front-lang-btn:not(.is-active) {
    color: var(--text-sub);
}
/* ===== END LANGUAGE SWITCHER ===== */

/* PAGE */
.page-wrapper{max-width:1160px;margin:0 auto;padding:40px 24px 80px;}
.page-header{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:32px;gap:20px;flex-wrap:wrap;}
.page-header h1{font-family:'Fraunces',serif;font-size:30px;font-weight:800;letter-spacing:-0.8px;}
.page-header p{color:var(--text-sub);font-size:14px;margin-top:5px;}
.page-header-actions{display:flex;gap:10px;flex-wrap:wrap;}

/* BUTTONS */
.btn-primary{display:inline-flex;align-items:center;gap:8px;background:var(--primary);color:#fff;border:none;border-radius:var(--radius-sm);padding:11px 20px;font-size:13.5px;font-weight:700;font-family:'DM Sans',sans-serif;cursor:pointer;text-decoration:none;box-shadow:0 3px 12px var(--primary-glow);transition:all .2s;}
.btn-primary:hover{background:var(--primary-hover);transform:translateY(-1px);}
.btn-outline{display:inline-flex;align-items:center;gap:8px;background:var(--white);color:var(--text-sub);border:1.5px solid var(--border);border-radius:var(--radius-sm);padding:10px 16px;font-size:13px;font-weight:600;font-family:'DM Sans',sans-serif;cursor:pointer;text-decoration:none;transition:all .2s;}
.btn-outline:hover{border-color:var(--primary);color:var(--primary);}

/* FLASH */
.flash{padding:13px 18px;border-radius:var(--radius-sm);font-size:14px;font-weight:600;margin-bottom:24px;display:flex;align-items:center;gap:10px;}
.flash.success{background:var(--success-light);color:#065f46;border:1px solid var(--success-border);}
.flash.error{background:var(--danger-light);color:#9f1239;border:1px solid var(--danger-border);}
.flash.delete{background:var(--danger-light);color:#9f1239;border:1px solid var(--danger-border);}

/* KPI */
.kpi-strip{display:grid;grid-template-columns:repeat(5,1fr);gap:14px;margin-bottom:32px;}
.kpi-card{background:var(--white);border:1px solid var(--border);border-radius:var(--radius);padding:18px 20px;position:relative;overflow:hidden;transition:box-shadow .2s,background .3s,border-color .3s;}
.kpi-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;border-radius:var(--radius) var(--radius) 0 0;}
.kpi-card:nth-child(1)::before{background:linear-gradient(90deg,var(--primary),#a78bfa);}
.kpi-card:nth-child(2)::before{background:linear-gradient(90deg,#0ea370,#34d399);}
.kpi-card:nth-child(3)::before{background:linear-gradient(90deg,#f59e0b,#fbbf24);}
.kpi-card:nth-child(4)::before{background:linear-gradient(90deg,#3b82f6,#60a5fa);}
.kpi-card:nth-child(5)::before{background:linear-gradient(90deg,#64748b,#94a3b8);}
.kpi-icon{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;margin-bottom:10px;font-size:17px;}
.kpi-card:nth-child(1) .kpi-icon{background:var(--primary-light);}
.kpi-card:nth-child(2) .kpi-icon{background:var(--success-light);}
.kpi-card:nth-child(3) .kpi-icon{background:var(--warning-light);}
.kpi-card:nth-child(4) .kpi-icon{background:#eff6ff;}
.kpi-card:nth-child(5) .kpi-icon{background:#f1f5f9;}
.kpi-value{font-family:'Fraunces',serif;font-size:24px;font-weight:800;color:var(--text-main);line-height:1;}
.kpi-label{font-size:11px;font-weight:600;color:var(--text-sub);margin-top:4px;text-transform:uppercase;letter-spacing:.05em;}

/* IA PANEL */
.ia-panel{background:linear-gradient(135deg,var(--primary-light),var(--primary-light));border:1.5px solid var(--primary-border);border-radius:var(--radius);padding:24px 28px;margin-bottom:32px;transition:background .3s;}
.ia-panel-header{display:flex;align-items:center;gap:10px;margin-bottom:16px;}
.ia-panel-header h2{font-family:'Fraunces',serif;font-size:18px;font-weight:800;color:var(--primary);}
.ia-form-grid{display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:12px;align-items:end;}
.ia-form-group{display:flex;flex-direction:column;gap:4px;}
.ia-form-group label{font-size:12px;font-weight:700;color:var(--text-sub);}
.ia-form-group input{padding:9px 13px;border:1.5px solid var(--border);border-radius:var(--radius-sm);font-family:'DM Sans',sans-serif;font-size:13px;background:var(--white);color:var(--text-main);outline:none;transition:border-color .2s,background .3s;}
.ia-form-group input:focus{border-color:var(--primary);}
.btn-ia{display:inline-flex;align-items:center;gap:7px;background:linear-gradient(135deg,var(--primary),#7c3aed);color:#fff;border:none;border-radius:var(--radius-sm);padding:10px 18px;font-size:13px;font-weight:700;font-family:'DM Sans',sans-serif;cursor:pointer;white-space:nowrap;transition:all .2s;}
.btn-ia:hover{opacity:.9;transform:translateY(-1px);}
.ia-result{background:var(--white);border:1.5px solid var(--primary-border);border-radius:var(--radius-sm);padding:18px;margin-top:16px;transition:background .3s;}
.ia-result-title{font-size:13px;font-weight:700;color:var(--primary);margin-bottom:10px;}
.ia-field{margin-bottom:10px;}
.ia-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--text-dim);margin-bottom:3px;}
.ia-value{font-size:13px;line-height:1.6;color:var(--text-sub);}
.ia-value.big{font-size:15px;font-weight:800;color:var(--primary);}
.ia-error{background:var(--danger-light);color:var(--danger);border-radius:var(--radius-sm);padding:10px 14px;font-size:13px;font-weight:600;margin-top:12px;}
.ia-loading{display:none;align-items:center;gap:10px;padding:12px 0;color:var(--primary);font-weight:600;font-size:13px;}
.ia-loading.show{display:flex;}
.spinner{width:16px;height:16px;border:2.5px solid var(--primary-light);border-top-color:var(--primary);border-radius:50%;animation:spin .6s linear infinite;}
@keyframes spin{to{transform:rotate(360deg)}}

/* FILTERS */
.tools-bar{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:16px;}
.search-wrap{position:relative;}
.search-wrap svg{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--text-dim);pointer-events:none;width:14px;height:14px;}
.search-input{background:var(--white);border:1.5px solid var(--border);border-radius:var(--radius-sm);padding:8px 12px 8px 32px;font-size:13px;font-family:'DM Sans',sans-serif;width:200px;outline:none;transition:border-color .2s,background .3s;color:var(--text-main);}
.search-input:focus{border-color:var(--primary);}
.filter-select{background:var(--white);border:1.5px solid var(--border);border-radius:var(--radius-sm);padding:8px 12px;font-size:13px;font-family:'DM Sans',sans-serif;outline:none;cursor:pointer;color:var(--text-main);transition:background .3s;}
.count-pill{background:var(--primary-light);color:var(--primary);border-radius:20px;padding:3px 11px;font-size:12px;font-weight:700;}

/* CAMP CARDS */
.camp-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:18px;margin-bottom:24px;}
.camp-card{background:var(--white);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;box-shadow:var(--card-shadow);display:flex;flex-direction:column;transition:transform .22s,box-shadow .22s,border-color .22s,background .3s;position:relative;}
.camp-card:hover{transform:translateY(-3px);box-shadow:var(--card-shadow-hover);border-color:var(--primary-border);}
.camp-card-header{padding:18px 18px 0;display:flex;align-items:flex-start;justify-content:space-between;gap:8px;}
.camp-card-title{font-family:'Fraunces',serif;font-size:16px;font-weight:800;flex:1;}
.camp-badge{display:inline-flex;align-items:center;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;flex-shrink:0;}
.camp-card-body{padding:12px 18px 16px;flex:1;display:flex;flex-direction:column;gap:8px;}
.camp-desc{font-size:13px;color:var(--text-sub);line-height:1.65;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;}
.camp-obj{font-size:12px;background:var(--primary-light);color:var(--primary);border-radius:7px;padding:6px 10px;font-weight:600;}
.camp-meta{font-size:12px;color:var(--text-sub);}
.camp-budget{font-family:'Fraunces',serif;font-size:22px;font-weight:800;color:var(--primary);}
.camp-prod-badge{display:inline-flex;align-items:center;gap:5px;background:var(--success-light);color:var(--success);border-radius:20px;padding:4px 12px;font-size:12px;font-weight:700;cursor:pointer;border:none;font-family:'DM Sans',sans-serif;}
.camp-prod-badge.zero{background:var(--bg);color:var(--text-dim);border:1px solid var(--border);}
.camp-actions{display:flex;gap:8px;padding:14px 18px;border-top:1px solid var(--border);flex-wrap:wrap;}
.btn-edit-camp{flex:1;padding:8px;background:var(--primary-light);color:var(--primary);border:none;border-radius:7px;font-size:12px;font-weight:700;font-family:'DM Sans',sans-serif;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;gap:5px;transition:background .18s;}
.btn-edit-camp:hover{background:var(--primary-border);}
.btn-del-camp{flex:0 0 auto;padding:8px 10px;background:var(--danger-light);color:var(--danger);border:none;border-radius:7px;font-size:12px;cursor:pointer;font-family:'DM Sans',sans-serif;}
.btn-arch-camp{flex:0 0 auto;padding:8px 10px;background:var(--warning-light);color:#92400e;border:none;border-radius:7px;font-size:12px;cursor:pointer;font-family:'DM Sans',sans-serif;}

/* EMPTY STATE */
.empty-state{text-align:center;padding:60px 24px;background:var(--white);border-radius:var(--radius);border:1.5px dashed var(--border);}
.empty-state h3{font-family:'Fraunces',serif;font-size:18px;font-weight:800;margin-bottom:6px;}
.empty-state p{font-size:13.5px;color:var(--text-sub);margin-bottom:20px;}

/* FORM */
.form-card{background:var(--white);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;box-shadow:var(--card-shadow);transition:background .3s;}
.form-card.edit-mode{border:2px solid var(--primary);}
.form-card-header{padding:22px 26px 0;}
.form-card-header h2{font-family:'Fraunces',serif;font-size:19px;font-weight:800;}
.form-card-header p{font-size:13px;color:var(--text-sub);margin-top:3px;}
.edit-banner{display:flex;align-items:center;justify-content:space-between;background:var(--primary-light);border:1px solid var(--primary-border);border-radius:var(--radius-sm);padding:9px 14px;margin:14px 26px;font-size:13px;}
.edit-banner a{color:var(--danger);text-decoration:none;font-weight:700;font-size:12px;}
.form-inner{padding:14px 26px 28px;}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px;}
.form-grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:18px;}
.form-group{display:flex;flex-direction:column;gap:4px;margin-bottom:14px;}
.form-group label{font-size:12.5px;font-weight:700;color:var(--text-sub);}
.form-input{width:100%;padding:10px 13px;border:1.5px solid var(--border);border-radius:var(--radius-sm);font-size:13.5px;color:var(--text-main);background:var(--bg);transition:border-color .2s,background .3s;outline:none;font-family:'DM Sans',sans-serif;}
.form-input:focus{border-color:var(--primary);box-shadow:0 0 0 3px var(--primary-glow);}
.form-input.is-invalid{border-color:var(--danger) !important;}
textarea.form-input{resize:vertical;min-height:90px;}
.prefix-wrap{display:flex;align-items:center;border:1.5px solid var(--border);border-radius:var(--radius-sm);background:var(--bg);overflow:hidden;transition:border-color .2s;}
.prefix-wrap:focus-within{border-color:var(--primary);box-shadow:0 0 0 3px var(--primary-glow);}
.prefix{padding:0 11px;font-size:13px;color:var(--text-dim);border-right:1.5px solid var(--border);background:var(--white);min-height:42px;display:flex;align-items:center;}
.prefix-wrap .form-input{border:none;background:transparent;}
.field-error{font-size:11.5px;color:var(--danger);font-weight:600;display:none;margin-top:3px;}
.field-error.visible{display:flex;align-items:center;gap:4px;}
.form-actions{display:flex;gap:10px;align-items:center;padding-top:18px;border-top:1px solid var(--border);}
.btn-cancel-form{background:var(--bg);color:var(--text-sub);border:1.5px solid var(--border);border-radius:var(--radius-sm);padding:10px 18px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;font-family:'DM Sans',sans-serif;}

/* DRAWER */
.drawer-overlay{position:fixed;inset:0;background:rgba(15,14,26,.45);z-index:200;display:none;align-items:flex-end;justify-content:center;}
.drawer-overlay.open{display:flex;}
.drawer{width:100%;max-width:680px;background:var(--white);border-radius:var(--radius) var(--radius) 0 0;padding:28px 32px 36px;box-shadow:0 -8px 40px rgba(15,14,26,.15);animation:slideUp .24s ease;max-height:85vh;overflow-y:auto;display:flex;flex-direction:column;gap:20px;transition:background .3s;}
@keyframes slideUp{from{transform:translateY(40px);opacity:0;}to{transform:translateY(0);opacity:1;}}
.drawer-title{font-family:'Fraunces',serif;font-size:20px;font-weight:800;}
.drawer-sub{font-size:13px;color:var(--text-sub);margin-top:3px;}
.drawer-close{background:var(--bg);border:1.5px solid var(--border);border-radius:50%;width:34px;height:34px;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:16px;}
.drawer-section-label{font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-dim);margin-bottom:10px;}
.drawer-prod-list{display:flex;flex-direction:column;gap:8px;}
.drawer-prod-item{display:flex;align-items:center;gap:12px;background:var(--bg);border:1px solid var(--border);border-radius:var(--radius-sm);padding:12px 14px;}
.drawer-prod-thumb{width:42px;height:42px;border-radius:8px;object-fit:cover;background:var(--border);flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:18px;overflow:hidden;}
.drawer-prod-thumb img{width:100%;height:100%;object-fit:cover;}
.drawer-prod-info{flex:1;min-width:0;}
.drawer-prod-name{font-size:13.5px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.drawer-prod-meta{font-size:11px;color:var(--text-sub);margin-top:2px;}
.drawer-prod-price{font-family:'Fraunces',serif;font-size:14px;font-weight:800;color:var(--primary);white-space:nowrap;}
.btn-drawer-remove{background:var(--danger-light);color:var(--danger);border:1px solid var(--danger-border);border-radius:7px;padding:5px 12px;font-size:12px;font-weight:700;cursor:pointer;font-family:'DM Sans',sans-serif;flex-shrink:0;}
.drawer-add-row{display:flex;gap:10px;}
.drawer-select{flex:1;background:var(--bg);border:1.5px solid var(--border);border-radius:var(--radius-sm);padding:10px 12px;font-size:13px;font-family:'DM Sans',sans-serif;outline:none;color:var(--text-main);}
.drawer-select:focus{border-color:var(--primary);}
.btn-drawer-add{background:var(--primary);color:#fff;border:none;border-radius:var(--radius-sm);padding:10px 18px;font-size:13px;font-weight:700;cursor:pointer;font-family:'DM Sans',sans-serif;}
.drawer-empty{text-align:center;padding:20px;color:var(--text-dim);font-size:13px;}

/* DELETE MODAL */
.modal-overlay{position:fixed;inset:0;background:rgba(15,14,26,.55);z-index:300;display:none;align-items:center;justify-content:center;}
.modal-overlay.open{display:flex;}
.modal-box{background:var(--white);border-radius:var(--radius);padding:30px 32px;width:400px;max-width:94vw;box-shadow:0 20px 60px rgba(15,14,26,.18);animation:popIn .22s ease;transition:background .3s;}
@keyframes popIn{from{opacity:0;transform:scale(.93);}to{opacity:1;transform:scale(1);}}
.modal-title{font-family:'Fraunces',serif;font-size:18px;font-weight:800;margin-bottom:8px;}
.modal-text{font-size:13.5px;color:var(--text-sub);margin-bottom:24px;line-height:1.6;}
.modal-actions{display:flex;gap:10px;justify-content:flex-end;}
.btn-modal-cancel{background:var(--bg);color:var(--text-sub);border:1.5px solid var(--border);border-radius:var(--radius-sm);padding:9px 18px;font-size:13px;font-weight:600;cursor:pointer;font-family:'DM Sans',sans-serif;}
.btn-modal-del{background:var(--danger);color:#fff;border:none;border-radius:var(--radius-sm);padding:9px 20px;font-size:13px;font-weight:700;cursor:pointer;font-family:'DM Sans',sans-serif;}

/* SECTION HEAD */
.section-head{display:flex;align-items:center;gap:10px;margin-bottom:14px;}
.section-head h2{font-family:'Fraunces',serif;font-size:17px;font-weight:800;}

/* ===== ADDED FEATURE: PAGINATION ===== */
.pagination-bar {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    margin-bottom: 32px;
    flex-wrap: wrap;
}
.pagination-info {
    font-size: 13px;
    color: var(--text-sub);
    margin-right: 8px;
}
.page-btn {
    background: var(--white);
    border: 1.5px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 7px 13px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    color: var(--text-sub);
    font-family: 'DM Sans', sans-serif;
    transition: all .18s;
    min-width: 36px;
    text-align: center;
}
.page-btn:hover { border-color: var(--primary); color: var(--primary); }
.page-btn.active { background: var(--primary); color: #fff; border-color: var(--primary); }
.page-btn:disabled { opacity: .4; cursor: not-allowed; pointer-events: none; }
.per-page-select {
    background: var(--white);
    border: 1.5px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 7px 10px;
    font-size: 12px;
    font-family: 'DM Sans', sans-serif;
    cursor: pointer;
    color: var(--text-sub);
    outline: none;
}
/* ===== END PAGINATION ===== */
</style>

    <!-- Shared FrontOffice header assets -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../layout/front-header.css">
<link rel="icon" type="image/png" sizes="32x32" href="../../public/images/logo.png">
<link rel="shortcut icon" type="image/png" href="../../public/images/logo.png">
<link rel="apple-touch-icon" href="../../public/images/logo.png">
</head>
<body>
<?php require_once __DIR__ . '/../layout/header.php'; ?>

<div class="page-wrapper campaign-front">

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div>
            <h1>⚡ <span data-i18n="page_title">Mes Campagnes</span></h1>
            <p data-i18n="page_subtitle">Créez, gérez et analysez vos campagnes de collaboration.</p>
        </div>
        <div class="page-header-right">
            <div class="front-lang-switch" role="group" aria-label="Language">
                <button type="button" class="front-lang-btn" data-lang-choice="en" aria-pressed="false">EN</button>
                <button type="button" class="front-lang-btn" data-lang-choice="fr" aria-pressed="false">FR</button>
            </div>
            <div class="page-header-actions">
            <a href="#formAnchor" class="btn-primary">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                <span data-i18n="btn_new_campaign">Nouvelle campagne</span>
            </a>
            </div>
        </div>
    </div>

    <!-- FLASH -->
    <?php if ($message): ?>
    <div class="flash <?= $messageType ?>" id="flashMsg">
        <?= $messageType === 'success' ? '✅' : '⚠️' ?> <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <!-- KPI -->
    <div class="kpi-strip">
        <div class="kpi-card"><div class="kpi-icon">⚡</div><div class="kpi-value"><?= $totalActives ?></div><div class="kpi-label" data-i18n="kpi_total">Total</div></div>
        <div class="kpi-card"><div class="kpi-icon">✅</div><div class="kpi-value"><?= $nbActives ?></div><div class="kpi-label" data-i18n="kpi_active">Actives</div></div>
        <div class="kpi-card"><div class="kpi-icon">📝</div><div class="kpi-value"><?= $nbBrouillons ?></div><div class="kpi-label" data-i18n="kpi_draft">Brouillons</div></div>
        <div class="kpi-card"><div class="kpi-icon">🏁</div><div class="kpi-value"><?= $nbTerminees ?></div><div class="kpi-label" data-i18n="kpi_done">Terminées</div></div>
        <div class="kpi-card"><div class="kpi-icon">💰</div><div class="kpi-value"><?= number_format($budgetTotal, 0, ',', ' ') ?> €</div><div class="kpi-label" data-i18n="kpi_budget">Budget total</div></div>
    </div>

    <!-- IA PANEL -->
    <div class="ia-panel">
        <div class="ia-panel-header">
            <span style="font-size:22px;">🤖</span>
            <h2 data-i18n="ia_title">Générer une campagne avec l'IA</h2>
        </div>
        <form method="POST" id="iaForm">
            <input type="hidden" name="action" value="ia_generer">
            <div class="ia-form-grid">
                <div class="ia-form-group">
                    <label data-i18n="ia_product_label">Produit à promouvoir *</label>
                    <input type="text" name="ia_produit" data-i18n-placeholder="ia_product_placeholder" placeholder="Ex : Sneakers éco-responsables" value="<?= htmlspecialchars($_POST['ia_produit'] ?? '') ?>">
                </div>
                <div class="ia-form-group">
                    <label data-i18n="ia_audience_label">Audience cible *</label>
                    <input type="text" name="ia_cible" data-i18n-placeholder="ia_audience_placeholder" placeholder="Ex : 18-30 ans, mode durable" value="<?= htmlspecialchars($_POST['ia_cible'] ?? '') ?>">
                </div>
                <div class="ia-form-group">
                    <label data-i18n="ia_budget_label">Budget (€) *</label>
                    <input type="number" name="ia_budget" min="1" step="0.01" placeholder="5000" value="<?= htmlspecialchars($_POST['ia_budget'] ?? '') ?>">
                </div>
                <button type="submit" class="btn-ia" onclick="document.getElementById('iaLoading').classList.add('show')">
                    ✨ <span data-i18n="ia_generate_btn">Générer</span>
                </button>
            </div>
        </form>
        <div class="ia-loading" id="iaLoading"><div class="spinner"></div> <span data-i18n="ia_loading">L'IA génère votre campagne…</span></div>
        <?php if ($iaError): ?>
            <div class="ia-error">⚠️ <?= htmlspecialchars($iaError) ?></div>
        <?php endif; ?>
        <?php if ($iaResult): ?>
        <div class="ia-result">
            <div class="ia-result-title">🎯 <span data-i18n="ia_result_title">Campagne générée par l'IA</span></div>
            <?php if (!empty($iaResult['titre'])): ?><div class="ia-field"><div class="ia-label" data-i18n="label_title">Titre</div><div class="ia-value big"><?= htmlspecialchars($iaResult['titre']) ?></div></div><?php endif; ?>
            <?php if (!empty($iaResult['description'])): ?><div class="ia-field"><div class="ia-label" data-i18n="label_description">Description</div><div class="ia-value"><?= htmlspecialchars($iaResult['description']) ?></div></div><?php endif; ?>
            <?php if (!empty($iaResult['objectif'])): ?><div class="ia-field"><div class="ia-label" data-i18n="label_objective">Objectif</div><div class="ia-value"><?= htmlspecialchars($iaResult['objectif']) ?></div></div><?php endif; ?>
            <?php if (!empty($iaResult['type_contenu'])): ?><div class="ia-field"><div class="ia-label" data-i18n="label_content_type">Type de contenu recommandé</div><div class="ia-value"><?= htmlspecialchars($iaResult['type_contenu']) ?></div></div><?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- CAMPAGNES -->
    <div class="section-head">
        <h2>📋 <span data-i18n="section_my_campaigns">Mes campagnes</span></h2>
        <span class="count-pill" id="visibleCount"><?= $totalActives ?></span>
    </div>

    <div class="tools-bar">
        <div class="search-wrap">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
            <input type="text" id="searchInput" class="search-input" data-i18n-placeholder="search_placeholder" placeholder="Rechercher…">
        </div>
        <select id="filterStatut" class="filter-select" onchange="filterAndPaginate()">
            <option value="" data-i18n="filter_all_statuts">Tous statuts</option>
            <?php foreach ($statuts as $s): ?><option value="<?= $s ?>"><?= ucfirst($s) ?></option><?php endforeach; ?>
        </select>
        <select id="sortSelect" class="filter-select" onchange="sortCampagnes()">
            <option value="" data-i18n="sort_default">Trier par…</option>
            <option value="titre" data-i18n="sort_name">Nom A→Z</option>
            <option value="budget_desc" data-i18n="sort_budget_desc">Budget ↓</option>
            <option value="budget_asc" data-i18n="sort_budget_asc">Budget ↑</option>
        </select>
        <!-- ===== ADDED FEATURE: PER PAGE SELECT ===== -->
        <select id="perPageSelect" class="per-page-select" onchange="changePerPage()">
            <option value="6">6 / <span data-i18n="per_page">page</span></option>
            <option value="9" selected>9 / <span data-i18n="per_page">page</span></option>
            <option value="12">12 / <span data-i18n="per_page">page</span></option>
            <option value="999" data-i18n="show_all">Tout afficher</option>
        </select>
        <!-- ===== END PER PAGE SELECT ===== -->
    </div>

    <?php if (empty($campagnes)): ?>
    <div class="empty-state">
        <div style="font-size:48px;margin-bottom:14px;">⚡</div>
        <h3 data-i18n="empty_title">Aucune campagne pour l'instant</h3>
        <p data-i18n="empty_subtitle">Créez votre première campagne pour collaborer avec des créateurs.</p>
        <a href="#formAnchor" class="btn-primary">➕ <span data-i18n="btn_create_first">Créer ma première campagne</span></a>
    </div>
    <?php else: ?>
    <div class="camp-grid" id="campGrid">
        <?php foreach ($campagnes as $c):
            $nbProd = $campagneC->compterProduitsCampagne($c['idCampagne']);
        ?>
        <div class="camp-card"
             data-titre="<?= strtolower(htmlspecialchars($c['titreCampagne'])) ?>"
             data-statut="<?= htmlspecialchars($c['statut']) ?>"
             data-budget="<?= (float)$c['budget'] ?>">
            <div class="camp-card-header">
                <div class="camp-card-title"><?= htmlspecialchars($c['titreCampagne']) ?></div>
                <span class="camp-badge" style="background:<?= statutBg($c['statut']) ?>;color:<?= statutColor($c['statut']) ?>"><?= statutLabel($c['statut']) ?></span>
            </div>
            <div class="camp-card-body">
                <?php if ($c['description']): ?><div class="camp-desc"><?= htmlspecialchars($c['description']) ?></div><?php endif; ?>
                <?php if ($c['objectif']): ?><div class="camp-obj">🎯 <?= htmlspecialchars($c['objectif']) ?></div><?php endif; ?>
                <div class="camp-meta">📅 <?= $c['dateDebut'] ?? '—' ?> → 🏁 <?= $c['dateFin'] ?? '—' ?></div>
                <div class="camp-budget"><?= number_format((float)$c['budget'], 2, ',', ' ') ?> €</div>
                <div>
                    <button class="camp-prod-badge <?= $nbProd === 0 ? 'zero' : '' ?>"
                            onclick="openDrawer(<?= $c['idCampagne'] ?>, '<?= htmlspecialchars(addslashes($c['titreCampagne'])) ?>')">
                        📦 <?= $nbProd ?> <span data-i18n="products_badge">produit<?= $nbProd !== 1 ? 's' : '' ?></span> +
                    </button>
                </div>
            </div>
            <div class="camp-actions">
                <a href="?modifier=<?= $c['idCampagne'] ?>#formAnchor" class="btn-edit-camp">✏️ <span data-i18n="btn_edit">Modifier</span></a>
                <button class="btn-arch-camp" onclick="ajaxArchive(<?= $c['idCampagne'] ?>)" title="Archiver">📦</button>
                <button class="btn-del-camp" onclick="openDeleteModal(<?= $c['idCampagne'] ?>, '<?= htmlspecialchars(addslashes($c['titreCampagne'])) ?>')" title="Supprimer">🗑</button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ===== ADDED FEATURE: PAGINATION BAR ===== -->
    <div class="pagination-bar" id="paginationBar">
        <span class="pagination-info" id="paginationInfo"></span>
        <button class="page-btn" id="btnPrev" onclick="changePage(-1)">← <span data-i18n="prev">Préc.</span></button>
        <div id="pageNumbers" style="display:flex;gap:4px;"></div>
        <button class="page-btn" id="btnNext" onclick="changePage(1)"><span data-i18n="next">Suiv.</span> →</button>
    </div>
    <!-- ===== END PAGINATION ===== -->

    <?php endif; ?>

    <!-- FORM -->
    <div id="formAnchor" style="margin-top:40px;">
        <div class="form-card <?= $editCampagne ? 'edit-mode' : '' ?>">
            <div class="form-card-header">
                <h2><?= $editCampagne ? '✏️ <span data-i18n="form_edit_title">Modifier la campagne</span>' : '➕ <span data-i18n="form_add_title">Nouvelle campagne</span>' ?></h2>
                <p><?= $editCampagne ? '<span data-i18n="form_edit_subtitle">Modifiez les informations ci-dessous.</span>' : '<span data-i18n="form_add_subtitle">Remplissez les détails de votre nouvelle campagne.</span>' ?></p>
            </div>
            <?php if ($editCampagne): ?>
            <div class="edit-banner">
                <span><span data-i18n="editing_label">Modification</span> : <strong><?= htmlspecialchars($editCampagne['titreCampagne']) ?></strong></span>
                <a href="index.php">✕ <span data-i18n="btn_cancel">Annuler</span></a>
            </div>
            <?php endif; ?>
            <div class="form-inner">
                <form method="POST" id="campagneForm" novalidate>
                    <input type="hidden" name="action" value="<?= $editCampagne ? 'modifier' : 'ajouter' ?>">
                    <?php if ($editCampagne): ?>
                    <input type="hidden" name="id" value="<?= $editCampagne['idCampagne'] ?>">
                    <input type="hidden" name="estArchive" value="<?= intval($editCampagne['estArchive'] ?? 0) ?>">
                    <?php endif; ?>

                    <div class="form-grid">
                        <div class="form-group">
                            <label data-i18n="label_title">Titre *</label>
                            <input type="text" name="titre" id="fTitre" class="form-input" maxlength="100"
                                   value="<?= $editCampagne ? htmlspecialchars($editCampagne['titreCampagne']) : '' ?>"
                                   data-i18n-placeholder="form_title_placeholder" placeholder="Ex : Collab Été 2025">
                            <div class="field-error" id="errTitre" data-i18n="err_title">Titre requis (2-100 car., sans HTML)</div>
                        </div>
                        <div class="form-group">
                            <label data-i18n="label_status">Statut</label>
                            <select name="statut" class="form-input">
                                <?php foreach ($statuts as $s): ?>
                                <option value="<?= $s ?>" <?= ($editCampagne && $editCampagne['statut'] === $s) ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label data-i18n="label_description">Description</label>
                        <textarea name="description" class="form-input" data-i18n-placeholder="form_desc_placeholder" placeholder="Décrivez les objectifs, l'audience cible…"><?= $editCampagne ? htmlspecialchars($editCampagne['description']) : '' ?></textarea>
                    </div>

                    <div class="form-group">
                        <label data-i18n="label_objective">Objectif</label>
                        <input type="text" name="objectif" class="form-input" maxlength="200"
                               data-i18n-placeholder="form_obj_placeholder" placeholder="Ex : 50K vues, notoriété marque"
                               value="<?= $editCampagne ? htmlspecialchars($editCampagne['objectif'] ?? '') : '' ?>">
                    </div>

                    <div class="form-grid-3">
                        <div class="form-group">
                            <label data-i18n="label_start_date">Date début</label>
                            <input type="text" name="dateDebut" id="fDateDebut" class="form-input" placeholder="AAAA-MM-JJ"
                                   value="<?= $editCampagne ? htmlspecialchars($editCampagne['dateDebut'] ?? '') : '' ?>">
                            <div class="field-error" id="errDateDebut" data-i18n="err_date">Format AAAA-MM-JJ requis</div>
                        </div>
                        <div class="form-group">
                            <label data-i18n="label_end_date">Date fin</label>
                            <input type="text" name="dateFin" id="fDateFin" class="form-input" placeholder="AAAA-MM-JJ"
                                   value="<?= $editCampagne ? htmlspecialchars($editCampagne['dateFin'] ?? '') : '' ?>">
                            <div class="field-error" id="errDateFin" data-i18n="err_date">Format AAAA-MM-JJ requis</div>
                            <div class="field-error" id="errDateCoherence" style="color:#f59e0b;" data-i18n="err_date_order">⚠ La date de fin doit être après le début</div>
                        </div>
                        <div class="form-group">
                            <label data-i18n="label_budget">Budget (€) *</label>
                            <div class="prefix-wrap" id="budgetWrapper">
                                <span class="prefix">€</span>
                                <input type="text" name="budget" id="fBudget" class="form-input"
                                       placeholder="0.00"
                                       value="<?= $editCampagne ? htmlspecialchars($editCampagne['budget'] ?? '') : '' ?>">
                            </div>
                            <div class="field-error" id="errBudget" data-i18n="err_budget">Budget valide requis (≥ 0)</div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary"><?= $editCampagne ? '💾 <span data-i18n="btn_save">Enregistrer</span>' : '🚀 <span data-i18n="btn_create">Créer la campagne</span>' ?></button>
                        <?php if ($editCampagne): ?><a href="index.php" class="btn-cancel-form" data-i18n="btn_cancel">Annuler</a><?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div><!-- /page-wrapper -->

<!-- DRAWER PRODUITS -->
<div class="drawer-overlay" id="drawerOverlay" onclick="closeDrawerOutside(event)">
    <div class="drawer">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
            <div>
                <div class="drawer-title" id="drawerTitle">Produits de la campagne</div>
                <div class="drawer-sub" data-i18n="drawer_subtitle">Ajoutez ou retirez des produits liés.</div>
            </div>
            <button class="drawer-close" onclick="closeDrawer()">✕</button>
        </div>
        <div id="drawerContent"><div class="drawer-empty">⏳ <span data-i18n="loading">Chargement…</span></div></div>
    </div>
</div>

<!-- MODAL SUPPRESSION -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal-box">
        <div class="modal-title">🗑 <span data-i18n="modal_delete_title">Supprimer la campagne ?</span></div>
        <div class="modal-text" id="deleteModalText"></div>
        <div class="modal-actions">
            <button class="btn-modal-cancel" onclick="closeDeleteModal()" data-i18n="btn_cancel">Annuler</button>
            <a href="#" class="btn-modal-del" id="deleteModalLink" data-i18n="btn_delete_confirm">Supprimer</a>
        </div>
    </div>
</div>

<script>
const BASE_URL = <?= json_encode($baseUrl, JSON_UNESCAPED_SLASHES) ?>;

// ── FLASH ──────────────────────────────────────────────────────────
const flash = document.getElementById('flashMsg');
if (flash) setTimeout(() => { flash.style.opacity='0'; flash.style.transition='opacity .4s'; setTimeout(()=>flash.remove(),400); }, 4000);

// Theme: shared header uses ../layout/front-header.js (localStorage key cre8_theme, html data-theme).
// Do not use cre8_theme_fo or applyDark here — they conflicted with the header and broke dark mode.

// ===== ADDED FEATURE: TRANSLATION SYSTEM =====
function cre8FrontReadLang() {
    try {
        var k = localStorage.getItem('cre8_front_lang');
        if (k === 'fr' || k === 'en') return k;
        var legacy = localStorage.getItem('cre8_lang')
            || localStorage.getItem('cre8_lang_produit')
            || localStorage.getItem('cre8_lang_creator_produit')
            || localStorage.getItem('cc_lang');
        if (legacy === 'fr' || legacy === 'en') return legacy;
    } catch (e) {}
    return 'en';
}
function cre8FrontWriteLang(lang) {
    var safe = lang === 'fr' ? 'fr' : 'en';
    try { localStorage.setItem('cre8_front_lang', safe); } catch (e) {}
}

const translations = {
    fr: {
        nav_campagnes: 'Campagnes',
        nav_produits: 'Produits',
        nav_contrats: 'Contrats',
        role_marque: 'Marque',
        theme_dark: 'Mode sombre',
        theme_light: 'Mode clair',
        page_title: 'Mes Campagnes',
        page_subtitle: 'Créez, gérez et analysez vos campagnes de collaboration.',
        btn_new_campaign: 'Nouvelle campagne',
        kpi_total: 'Total',
        kpi_active: 'Actives',
        kpi_draft: 'Brouillons',
        kpi_done: 'Terminées',
        kpi_budget: 'Budget total',
        ia_title: "Générer une campagne avec l'IA",
        ia_product_label: 'Produit à promouvoir *',
        ia_product_placeholder: 'Ex : Sneakers éco-responsables',
        ia_audience_label: 'Audience cible *',
        ia_audience_placeholder: 'Ex : 18-30 ans, mode durable',
        ia_budget_label: 'Budget (€) *',
        ia_generate_btn: 'Générer',
        ia_loading: "L'IA génère votre campagne…",
        ia_result_title: "Campagne générée par l'IA",
        section_my_campaigns: 'Mes campagnes',
        search_placeholder: 'Rechercher…',
        filter_all_statuts: 'Tous statuts',
        sort_default: 'Trier par…',
        sort_name: 'Nom A→Z',
        sort_budget_desc: 'Budget ↓',
        sort_budget_asc: 'Budget ↑',
        per_page: 'page',
        show_all: 'Tout afficher',
        prev: 'Préc.',
        next: 'Suiv.',
        empty_title: 'Aucune campagne pour l\'instant',
        empty_subtitle: 'Créez votre première campagne pour collaborer avec des créateurs.',
        btn_create_first: 'Créer ma première campagne',
        btn_edit: 'Modifier',
        products_badge: 'produits',
        form_edit_title: 'Modifier la campagne',
        form_add_title: 'Nouvelle campagne',
        form_edit_subtitle: 'Modifiez les informations ci-dessous.',
        form_add_subtitle: 'Remplissez les détails de votre nouvelle campagne.',
        editing_label: 'Modification',
        btn_cancel: 'Annuler',
        label_title: 'Titre *',
        label_status: 'Statut',
        label_description: 'Description',
        label_objective: 'Objectif',
        label_start_date: 'Date début',
        label_end_date: 'Date fin',
        label_budget: 'Budget (€) *',
        label_content_type: 'Type de contenu recommandé',
        form_title_placeholder: 'Ex : Collab Été 2025',
        form_desc_placeholder: "Décrivez les objectifs, l'audience cible…",
        form_obj_placeholder: 'Ex : 50K vues, notoriété marque',
        err_title: 'Titre requis (2-100 car., sans HTML)',
        err_date: 'Format AAAA-MM-JJ requis',
        err_date_order: '⚠ La date de fin doit être après le début',
        err_budget: 'Budget valide requis (≥ 0)',
        btn_save: 'Enregistrer',
        btn_create: 'Créer la campagne',
        drawer_subtitle: 'Ajoutez ou retirez des produits liés.',
        loading: 'Chargement…',
        modal_delete_title: 'Supprimer la campagne ?',
        btn_delete_confirm: 'Supprimer',
        pagination_showing: 'Affichage',
        pagination_of: 'sur',
        pagination_campaigns: 'campagnes',
    },
    en: {
        nav_campagnes: 'Campaigns',
        nav_produits: 'Products',
        nav_contrats: 'Contracts',
        role_marque: 'Brand',
        theme_dark: 'Dark mode',
        theme_light: 'Light mode',
        page_title: 'My Campaigns',
        page_subtitle: 'Create, manage and analyze your collaboration campaigns.',
        btn_new_campaign: 'New campaign',
        kpi_total: 'Total',
        kpi_active: 'Active',
        kpi_draft: 'Drafts',
        kpi_done: 'Completed',
        kpi_budget: 'Total budget',
        ia_title: 'Generate a campaign with AI',
        ia_product_label: 'Product to promote *',
        ia_product_placeholder: 'E.g. Eco-friendly sneakers',
        ia_audience_label: 'Target audience *',
        ia_audience_placeholder: 'E.g. 18-30 y/o, sustainable fashion',
        ia_budget_label: 'Budget (€) *',
        ia_generate_btn: 'Generate',
        ia_loading: 'AI is generating your campaign…',
        ia_result_title: 'AI-generated campaign',
        section_my_campaigns: 'My campaigns',
        search_placeholder: 'Search…',
        filter_all_statuts: 'All statuses',
        sort_default: 'Sort by…',
        sort_name: 'Name A→Z',
        sort_budget_desc: 'Budget ↓',
        sort_budget_asc: 'Budget ↑',
        per_page: 'page',
        show_all: 'Show all',
        prev: 'Prev.',
        next: 'Next',
        empty_title: 'No campaigns yet',
        empty_subtitle: 'Create your first campaign to collaborate with creators.',
        btn_create_first: 'Create my first campaign',
        btn_edit: 'Edit',
        products_badge: 'products',
        form_edit_title: 'Edit campaign',
        form_add_title: 'New campaign',
        form_edit_subtitle: 'Update the information below.',
        form_add_subtitle: 'Fill in the details of your new campaign.',
        editing_label: 'Editing',
        btn_cancel: 'Cancel',
        label_title: 'Title *',
        label_status: 'Status',
        label_description: 'Description',
        label_objective: 'Objective',
        label_start_date: 'Start date',
        label_end_date: 'End date',
        label_budget: 'Budget (€) *',
        label_content_type: 'Recommended content type',
        form_title_placeholder: 'E.g. Summer Collab 2025',
        form_desc_placeholder: 'Describe the goals, target audience…',
        form_obj_placeholder: 'E.g. 50K views, brand awareness',
        err_title: 'Title required (2-100 chars, no HTML)',
        err_date: 'Format YYYY-MM-DD required',
        err_date_order: '⚠ End date must be after start date',
        err_budget: 'Valid budget required (≥ 0)',
        btn_save: 'Save',
        btn_create: 'Create campaign',
        drawer_subtitle: 'Add or remove linked products.',
        loading: 'Loading…',
        modal_delete_title: 'Delete this campaign?',
        btn_delete_confirm: 'Delete',
        pagination_showing: 'Showing',
        pagination_of: 'of',
        pagination_campaigns: 'campaigns',
    }
};

let currentLang = cre8FrontReadLang();

function applyTranslation(lang) {
    const safe = lang === 'fr' ? 'fr' : 'en';
    currentLang = safe;
    cre8FrontWriteLang(safe);
    const dict = translations[safe] || translations['fr'];

    document.querySelectorAll('[data-i18n]').forEach(el => {
        const key = el.getAttribute('data-i18n');
        if (dict[key]) el.textContent = dict[key];
    });

    document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
        const key = el.getAttribute('data-i18n-placeholder');
        if (dict[key]) el.placeholder = dict[key];
    });

    document.querySelectorAll('[data-i18n-title]').forEach(el => {
        const key = el.getAttribute('data-i18n-title');
        if (dict[key]) el.setAttribute('title', dict[key]);
    });

    document.querySelectorAll('[data-lang-choice]').forEach(btn => {
        const on = btn.getAttribute('data-lang-choice') === safe;
        btn.classList.toggle('is-active', on);
        btn.setAttribute('aria-pressed', on ? 'true' : 'false');
    });

    renderPaginationInfo();
}

function setLang(lang) { applyTranslation(lang); }

applyTranslation(currentLang);

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-lang-choice]').forEach(btn => {
        btn.addEventListener('click', function () {
            applyTranslation(btn.getAttribute('data-lang-choice'));
        });
    });
});
// ===== END TRANSLATION SYSTEM =====

// ===== ADDED FEATURE: PAGINATION =====
let currentPage = 1;
let perPage = 9;
let filteredCards = [];

function getAllCards() {
    return Array.from(document.querySelectorAll('#campGrid .camp-card'));
}

function filterAndPaginate() {
    const q = document.getElementById('searchInput')?.value.toLowerCase() || '';
    const s = document.getElementById('filterStatut')?.value || '';
    const all = getAllCards();
    filteredCards = all.filter(card => {
        const mQ = !q || (card.dataset.titre || '').includes(q);
        const mS = !s || card.dataset.statut === s;
        return mQ && mS;
    });
    const vc = document.getElementById('visibleCount');
    if (vc) vc.textContent = filteredCards.length;
    currentPage = 1;
    renderPage();
}

function renderPage() {
    const all = getAllCards();
    // Hide all
    all.forEach(c => c.style.display = 'none');

    if (!filteredCards.length) {
        renderPaginationBar(0, 0);
        renderPaginationInfo();
        return;
    }

    const totalPages = Math.ceil(filteredCards.length / perPage);
    if (currentPage > totalPages) currentPage = totalPages;
    if (currentPage < 1) currentPage = 1;

    const start = (currentPage - 1) * perPage;
    const end   = Math.min(start + perPage, filteredCards.length);

    filteredCards.forEach((card, i) => {
        card.style.display = (i >= start && i < end) ? '' : 'none';
    });

    renderPaginationBar(totalPages, currentPage);
    renderPaginationInfo(start + 1, end, filteredCards.length);
}

function renderPaginationBar(totalPages, cur) {
    const bar = document.getElementById('paginationBar');
    if (!bar) return;
    bar.style.display = totalPages <= 1 ? 'none' : 'flex';

    const btnPrev = document.getElementById('btnPrev');
    const btnNext = document.getElementById('btnNext');
    const pageNums = document.getElementById('pageNumbers');

    if (btnPrev) btnPrev.disabled = cur <= 1;
    if (btnNext) btnNext.disabled = cur >= totalPages;

    if (!pageNums) return;
    pageNums.innerHTML = '';
    // Show up to 5 page number buttons
    const startPage = Math.max(1, cur - 2);
    const endPage   = Math.min(totalPages, startPage + 4);
    for (let i = startPage; i <= endPage; i++) {
        const btn = document.createElement('button');
        btn.className = 'page-btn' + (i === cur ? ' active' : '');
        btn.textContent = i;
        btn.onclick = (function(p) { return function() { currentPage = p; renderPage(); }; })(i);
        pageNums.appendChild(btn);
    }
}

function renderPaginationInfo(from, to, total) {
    const info = document.getElementById('paginationInfo');
    if (!info) return;
    if (!from) { info.textContent = ''; return; }
    const dict = translations[currentLang] || translations['fr'];
    info.textContent = `${dict.pagination_showing || 'Affichage'} ${from}–${to} ${dict.pagination_of || 'sur'} ${total} ${dict.pagination_campaigns || 'campagnes'}`;
}

function changePage(delta) {
    currentPage += delta;
    renderPage();
}

function changePerPage() {
    const sel = document.getElementById('perPageSelect');
    perPage = parseInt(sel?.value) || 9;
    currentPage = 1;
    renderPage();
}

// Initialize pagination on load
document.addEventListener('DOMContentLoaded', function() {
    filteredCards = getAllCards();
    renderPage();
});
// ===== END PAGINATION =====

// ── FILTER + SORT (updated to work with pagination) ───────────────
document.getElementById('searchInput')?.addEventListener('input', filterAndPaginate);

function sortCampagnes() {
    const mode = document.getElementById('sortSelect')?.value;
    const grid = document.getElementById('campGrid');
    if (!grid || !mode) return;
    const cards = Array.from(grid.querySelectorAll('.camp-card'));
    cards.sort((a, b) => {
        if (mode === 'titre')       return (a.dataset.titre||'').localeCompare(b.dataset.titre||'');
        if (mode === 'budget_asc')  return parseFloat(a.dataset.budget) - parseFloat(b.dataset.budget);
        if (mode === 'budget_desc') return parseFloat(b.dataset.budget) - parseFloat(a.dataset.budget);
        return 0;
    });
    cards.forEach(c => grid.appendChild(c));
    filterAndPaginate(); // Re-apply filter + paginate after sort
}

// ── AJAX ARCHIVE ───────────────────────────────────────────────────
function ajaxArchive(id) {
    if (!confirm('Archiver cette campagne ?')) return;
    fetch('index.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'action=archive&id='+id })
        .then(r => r.json()).then(() => location.reload());
}

// ── DELETE MODAL ───────────────────────────────────────────────────
function openDeleteModal(id, titre) {
    document.getElementById('deleteModalText').textContent = `Supprimer "${titre}" ? Action irréversible.`;
    document.getElementById('deleteModalLink').href = 'index.php?supprimer='+id;
    document.getElementById('deleteModal').classList.add('open');
}
function closeDeleteModal() { document.getElementById('deleteModal').classList.remove('open'); }
document.getElementById('deleteModal').addEventListener('click', e => { if (e.target.id === 'deleteModal') closeDeleteModal(); });

// ── DRAWER ─────────────────────────────────────────────────────────
function openDrawer(id, titre) {
    document.getElementById('drawerTitle').textContent = '📦 Produits — ' + titre;
    document.getElementById('drawerContent').innerHTML = '<div class="drawer-empty">⏳ Chargement…</div>';
    document.getElementById('drawerOverlay').classList.add('open');
    loadDrawerProducts(id);
}
function closeDrawer() { document.getElementById('drawerOverlay').classList.remove('open'); }
function closeDrawerOutside(e) { if (e.target.id === 'drawerOverlay') closeDrawer(); }
function loadDrawerProducts(id) {
    fetch('index.php?ajax_produits=' + id).then(r => r.json()).then(d => renderDrawer(id, d.lies, d.dispos));
}
function renderDrawer(id, lies, dispos) {
    const content = document.getElementById('drawerContent');
    let html = '';
    html += `<div><div class="drawer-section-label">Produits liés (${lies.length})</div><div class="drawer-prod-list">`;
    if (!lies.length) html += '<div class="drawer-empty">Aucun produit lié.</div>';
    else lies.forEach(p => {
        const img = p.image ? `<img src="${BASE_URL}/Vue/public/produits/${encodeURIComponent(p.image)}" alt="">` : '📦';
        html += `<div class="drawer-prod-item"><div class="drawer-prod-thumb">${img}</div><div class="drawer-prod-info"><div class="drawer-prod-name">${esc(p.nomProduit)}</div><div class="drawer-prod-meta">${p.categorie ? esc(p.categorie) : ''}</div></div><span class="drawer-prod-price">${parseFloat(p.prix).toFixed(2)} €</span><button class="btn-drawer-remove" onclick="retirerProduit(${id},${p.idProduit})">✕</button></div>`;
    });
    html += '</div></div>';
    html += `<div><div class="drawer-section-label">Ajouter un produit</div>`;
    if (!dispos.length) html += '<div class="drawer-empty">Tous les produits sont déjà liés.</div>';
    else {
        html += '<div class="drawer-add-row"><select class="drawer-select" id="drawerSelectProduit"><option value="">— Sélectionner —</option>';
        dispos.forEach(p => { html += `<option value="${p.idProduit}">${esc(p.nomProduit)} — ${parseFloat(p.prix).toFixed(2)} €</option>`; });
        html += `</select><button class="btn-drawer-add" onclick="ajouterProduit(${id})">+ Lier</button></div>`;
    }
    html += '</div>';
    content.innerHTML = html;
}
function ajouterProduit(id) {
    const sel = document.getElementById('drawerSelectProduit');
    if (!sel?.value) return;
    fetch('index.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:`action=lier_produit&idCampagne=${id}&idProduit=${sel.value}`})
        .then(r => r.json()).then(d => { if (d.ok) { loadDrawerProducts(id); } });
}
function retirerProduit(id, idP) {
    if (!confirm('Retirer ce produit ?')) return;
    fetch('index.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:`action=retirer_produit&idCampagne=${id}&idProduit=${idP}`})
        .then(r => r.json()).then(d => { if (d.ok) loadDrawerProducts(id); });
}
function esc(s) { const d = document.createElement('div'); d.textContent = s||''; return d.innerHTML; }

// ── FORM VALIDATION ────────────────────────────────────────────────
function showFE(id, msg) { const e = document.getElementById(id); if (!e) return; if (msg) { e.textContent = '⚠ '+msg; e.classList.add('visible'); } else e.classList.remove('visible'); }
function setFC(el, ok) { if (!el) return; el.classList.toggle('is-invalid', !ok); }
function isValidDate(v) {
    if (!v) return true;
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
    return parts[0].length <= 8 && (!parts[1] || parts[1].length <= 2);
}

const fTitre = document.getElementById('fTitre');
const fBudget = document.getElementById('fBudget');
const fDateDebut = document.getElementById('fDateDebut');
const fDateFin = document.getElementById('fDateFin');

function onDateKD(e) {
    const sys = ['Backspace','Delete','Tab','Escape','Enter','ArrowLeft','ArrowRight','Home','End'];
    if (sys.includes(e.key) || e.ctrlKey || e.metaKey || e.key === '-') return;
    if (!/^\d$/.test(e.key)) e.preventDefault();
}
function onDateInput(el, errId) {
    let v = el.value.replace(/[^\d]/g, '');
    if (v.length > 4) v = v.slice(0,4)+'-'+v.slice(4);
    if (v.length > 7) v = v.slice(0,7)+'-'+v.slice(7);
    el.value = v.slice(0,10);
    const ok = isValidDate(el.value);
    setFC(el, ok || !el.value);
    showFE(errId, ok || !el.value ? '' : 'Format invalide. Utilisez AAAA-MM-JJ.');
    checkDateCoherence();
}
function checkDateCoherence() {
    const d1 = fDateDebut?.value?.trim(), d2 = fDateFin?.value?.trim();
    const msg = document.getElementById('errDateCoherence');
    if (msg && d1 && d2 && isValidDate(d1) && isValidDate(d2) && d2 < d1) {
        msg.classList.add('visible'); setFC(fDateFin, false);
    } else msg?.classList.remove('visible');
}

if (fTitre) {
    fTitre.addEventListener('input', () => {
        const v = fTitre.value.trim();
        const ok = v.length >= 2 && v.length <= 100 && !/<[^>]+>/.test(v);
        setFC(fTitre, ok);
        showFE('errTitre', ok ? '' : 'Titre requis (2-100 car., sans HTML).');
    });
}
if (fBudget) {
    fBudget.addEventListener('keydown', e => {
        const sys = ['Backspace','Delete','Tab','Escape','Enter','ArrowLeft','ArrowRight','Home','End'];
        if (sys.includes(e.key) || e.ctrlKey || e.metaKey) return;
        if ((e.key==='.'||e.key===',') && !(fBudget.value.includes('.')||fBudget.value.includes(','))) return;
        if (!/^\d$/.test(e.key)) e.preventDefault();
    });
    fBudget.addEventListener('input', () => {
        const ok = isValidBudget(fBudget.value);
        setFC(fBudget, ok);
        document.getElementById('budgetWrapper')?.classList.toggle('is-invalid', !ok);
        showFE('errBudget', ok ? '' : 'Budget valide requis (ex : 1500.00).');
    });
}
if (fDateDebut) { fDateDebut.addEventListener('keydown', onDateKD); fDateDebut.addEventListener('input', () => onDateInput(fDateDebut, 'errDateDebut')); }
if (fDateFin)   { fDateFin.addEventListener('keydown', onDateKD);   fDateFin.addEventListener('input', () => onDateInput(fDateFin, 'errDateFin')); }

document.getElementById('campagneForm')?.addEventListener('submit', function(e) {
    let ok = true;
    if (fTitre) { const v = fTitre.value.trim(); if (v.length < 2 || /<[^>]+>/.test(v)) { setFC(fTitre, false); showFE('errTitre','Titre requis (2-100 car., sans HTML).'); ok = false; } }
    if (fBudget && !isValidBudget(fBudget.value)) { setFC(fBudget, false); showFE('errBudget','Budget valide requis.'); ok = false; }
    if (!ok) { e.preventDefault(); document.querySelector('.is-invalid')?.scrollIntoView({behavior:'smooth',block:'center'}); }
});

document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeDeleteModal(); closeDrawer(); } });
<?php if ($editCampagne): ?>
document.addEventListener('DOMContentLoaded', () => document.getElementById('formAnchor').scrollIntoView({behavior:'smooth',block:'start'}));
<?php endif; ?>
</script>

<script src="../layout/front-header.js"></script>
</body>
</html>