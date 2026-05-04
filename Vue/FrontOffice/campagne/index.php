<?php
/**
 * Vue/FrontOffice/campagne/index.php
 * Rôle : MARQUE — gestion de ses campagnes + génération IA
 */

require_once __DIR__ . '/../../../Controleur/campagneC.php';
require_once __DIR__ . '/../../../Controleur/produitC.php';
require_once __DIR__ . '/../../../Modele/campagne.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$campagneC = new CampagneC();
$produitC  = new ProduitC();
$baseUrl   = '/projet/Esprit-PW-2A22-2526-Devcore';
$id_marque = $_SESSION['user_id'] ?? 1;

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
        if (!$iaResult) $iaError = "L'IA n'a pas pu générer la campagne. Réessayez.";
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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
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
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text-main);min-height:100vh;}

/* NAV */
nav{background:var(--white);border-bottom:1px solid var(--border);padding:0 48px;height:var(--nav-h);display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:200;box-shadow:0 2px 12px rgba(15,14,26,0.04);}
.nav-logo{font-family:'Fraunces',serif;font-size:20px;font-weight:800;color:var(--primary);text-decoration:none;}
.nav-links{display:flex;gap:6px;list-style:none;}
.nav-links a{text-decoration:none;color:var(--text-sub);font-size:13.5px;font-weight:600;padding:6px 14px;border-radius:8px;transition:all .18s;}
.nav-links a:hover,.nav-links a.active{background:var(--primary-light);color:var(--primary);}
.nav-right{display:flex;align-items:center;gap:12px;}
.nav-badge{background:var(--primary);color:#fff;border-radius:20px;padding:5px 14px;font-size:12px;font-weight:700;}
.nav-avatar{width:36px;height:36px;border-radius:50%;background:var(--primary-light);color:var(--primary);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:14px;border:2px solid var(--primary-border);}

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
.kpi-card{background:var(--white);border:1px solid var(--border);border-radius:var(--radius);padding:18px 20px;position:relative;overflow:hidden;transition:box-shadow .2s;}
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
.ia-panel{background:linear-gradient(135deg,#f0effb,#ece9ff);border:1.5px solid var(--primary-border);border-radius:var(--radius);padding:24px 28px;margin-bottom:32px;}
.ia-panel-header{display:flex;align-items:center;gap:10px;margin-bottom:16px;}
.ia-panel-header h2{font-family:'Fraunces',serif;font-size:18px;font-weight:800;color:var(--primary);}
.ia-form-grid{display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:12px;align-items:end;}
.ia-form-group{display:flex;flex-direction:column;gap:4px;}
.ia-form-group label{font-size:12px;font-weight:700;color:var(--text-sub);}
.ia-form-group input{padding:9px 13px;border:1.5px solid var(--border);border-radius:var(--radius-sm);font-family:'DM Sans',sans-serif;font-size:13px;background:var(--white);outline:none;transition:border-color .2s;}
.ia-form-group input:focus{border-color:var(--primary);}
.btn-ia{display:inline-flex;align-items:center;gap:7px;background:linear-gradient(135deg,var(--primary),#7c3aed);color:#fff;border:none;border-radius:var(--radius-sm);padding:10px 18px;font-size:13px;font-weight:700;font-family:'DM Sans',sans-serif;cursor:pointer;white-space:nowrap;transition:all .2s;}
.btn-ia:hover{opacity:.9;transform:translateY(-1px);}
.ia-result{background:var(--white);border:1.5px solid var(--primary-border);border-radius:var(--radius-sm);padding:18px;margin-top:16px;}
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
.search-input{background:var(--white);border:1.5px solid var(--border);border-radius:var(--radius-sm);padding:8px 12px 8px 32px;font-size:13px;font-family:'DM Sans',sans-serif;width:200px;outline:none;transition:border-color .2s;}
.search-input:focus{border-color:var(--primary);}
.filter-select{background:var(--white);border:1.5px solid var(--border);border-radius:var(--radius-sm);padding:8px 12px;font-size:13px;font-family:'DM Sans',sans-serif;outline:none;cursor:pointer;}
.count-pill{background:var(--primary-light);color:var(--primary);border-radius:20px;padding:3px 11px;font-size:12px;font-weight:700;}

/* CAMP CARDS */
.camp-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:18px;margin-bottom:32px;}
.camp-card{background:var(--white);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;box-shadow:var(--card-shadow);display:flex;flex-direction:column;transition:transform .22s,box-shadow .22s,border-color .22s;position:relative;}
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
.btn-edit-camp:hover{background:#ddddf8;}
.btn-del-camp{flex:0 0 auto;padding:8px 10px;background:var(--danger-light);color:var(--danger);border:none;border-radius:7px;font-size:12px;cursor:pointer;font-family:'DM Sans',sans-serif;}
.btn-arch-camp{flex:0 0 auto;padding:8px 10px;background:var(--warning-light);color:#92400e;border:none;border-radius:7px;font-size:12px;cursor:pointer;font-family:'DM Sans',sans-serif;}

/* EMPTY STATE */
.empty-state{text-align:center;padding:60px 24px;background:var(--white);border-radius:var(--radius);border:1.5px dashed var(--border);}
.empty-state h3{font-family:'Fraunces',serif;font-size:18px;font-weight:800;margin-bottom:6px;}
.empty-state p{font-size:13.5px;color:var(--text-sub);margin-bottom:20px;}

/* FORM */
.form-card{background:var(--white);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;box-shadow:var(--card-shadow);}
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
.form-input{width:100%;padding:10px 13px;border:1.5px solid var(--border);border-radius:var(--radius-sm);font-size:13.5px;color:var(--text-main);background:#fafafa;transition:border-color .2s;outline:none;font-family:'DM Sans',sans-serif;}
.form-input:focus{border-color:var(--primary);box-shadow:0 0 0 3px var(--primary-glow);}
.form-input.is-invalid{border-color:var(--danger) !important;}
textarea.form-input{resize:vertical;min-height:90px;}
.prefix-wrap{display:flex;align-items:center;border:1.5px solid var(--border);border-radius:var(--radius-sm);background:#fafafa;overflow:hidden;transition:border-color .2s;}
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
.drawer{width:100%;max-width:680px;background:var(--white);border-radius:var(--radius) var(--radius) 0 0;padding:28px 32px 36px;box-shadow:0 -8px 40px rgba(15,14,26,.15);animation:slideUp .24s ease;max-height:85vh;overflow-y:auto;display:flex;flex-direction:column;gap:20px;}
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
.drawer-select{flex:1;background:#fafafa;border:1.5px solid var(--border);border-radius:var(--radius-sm);padding:10px 12px;font-size:13px;font-family:'DM Sans',sans-serif;outline:none;}
.drawer-select:focus{border-color:var(--primary);}
.btn-drawer-add{background:var(--primary);color:#fff;border:none;border-radius:var(--radius-sm);padding:10px 18px;font-size:13px;font-weight:700;cursor:pointer;font-family:'DM Sans',sans-serif;}
.drawer-empty{text-align:center;padding:20px;color:var(--text-dim);font-size:13px;}

/* DELETE MODAL */
.modal-overlay{position:fixed;inset:0;background:rgba(15,14,26,.55);z-index:300;display:none;align-items:center;justify-content:center;}
.modal-overlay.open{display:flex;}
.modal-box{background:var(--white);border-radius:var(--radius);padding:30px 32px;width:400px;max-width:94vw;box-shadow:0 20px 60px rgba(15,14,26,.18);animation:popIn .22s ease;}
@keyframes popIn{from{opacity:0;transform:scale(.93);}to{opacity:1;transform:scale(1);}}
.modal-title{font-family:'Fraunces',serif;font-size:18px;font-weight:800;margin-bottom:8px;}
.modal-text{font-size:13.5px;color:var(--text-sub);margin-bottom:24px;line-height:1.6;}
.modal-actions{display:flex;gap:10px;justify-content:flex-end;}
.btn-modal-cancel{background:var(--bg);color:var(--text-sub);border:1.5px solid var(--border);border-radius:var(--radius-sm);padding:9px 18px;font-size:13px;font-weight:600;cursor:pointer;font-family:'DM Sans',sans-serif;}
.btn-modal-del{background:var(--danger);color:#fff;border:none;border-radius:var(--radius-sm);padding:9px 20px;font-size:13px;font-weight:700;cursor:pointer;font-family:'DM Sans',sans-serif;}

/* SECTION HEAD */
.section-head{display:flex;align-items:center;gap:10px;margin-bottom:14px;}
.section-head h2{font-family:'Fraunces',serif;font-size:17px;font-weight:800;}
</style>
</head>
<body>

<nav>
    <a href="#" class="nav-logo">Cre8Connect</a>
    <ul class="nav-links">
        <li><a href="#">Dashboard</a></li>
        <li><a href="#" class="active">Campagnes</a></li>
        <li><a href="../produit/index.php">Produits</a></li>
        <li><a href="../contrat/index.php">Contrats</a></li>
    </ul>
    <div class="nav-right">
        <span class="nav-badge">Marque</span>
        <div class="nav-avatar">M</div>
    </div>
</nav>

<div class="page-wrapper">

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div>
            <h1>⚡ Mes Campagnes</h1>
            <p>Créez, gérez et analysez vos campagnes de collaboration.</p>
        </div>
        <div class="page-header-actions">
            <a href="#formAnchor" class="btn-primary">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Nouvelle campagne
            </a>
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
        <div class="kpi-card"><div class="kpi-icon">⚡</div><div class="kpi-value"><?= $totalActives ?></div><div class="kpi-label">Total</div></div>
        <div class="kpi-card"><div class="kpi-icon">✅</div><div class="kpi-value"><?= $nbActives ?></div><div class="kpi-label">Actives</div></div>
        <div class="kpi-card"><div class="kpi-icon">📝</div><div class="kpi-value"><?= $nbBrouillons ?></div><div class="kpi-label">Brouillons</div></div>
        <div class="kpi-card"><div class="kpi-icon">🏁</div><div class="kpi-value"><?= $nbTerminees ?></div><div class="kpi-label">Terminées</div></div>
        <div class="kpi-card"><div class="kpi-icon">💰</div><div class="kpi-value"><?= number_format($budgetTotal, 0, ',', ' ') ?> €</div><div class="kpi-label">Budget total</div></div>
    </div>

    <!-- IA PANEL -->
    <div class="ia-panel">
        <div class="ia-panel-header">
            <span style="font-size:22px;">🤖</span>
            <h2>Générer une campagne avec l'IA</h2>
        </div>
        <form method="POST" id="iaForm">
            <input type="hidden" name="action" value="ia_generer">
            <div class="ia-form-grid">
                <div class="ia-form-group">
                    <label>Produit à promouvoir *</label>
                    <input type="text" name="ia_produit" placeholder="Ex : Sneakers éco-responsables" value="<?= htmlspecialchars($_POST['ia_produit'] ?? '') ?>">
                </div>
                <div class="ia-form-group">
                    <label>Audience cible *</label>
                    <input type="text" name="ia_cible" placeholder="Ex : 18-30 ans, mode durable" value="<?= htmlspecialchars($_POST['ia_cible'] ?? '') ?>">
                </div>
                <div class="ia-form-group">
                    <label>Budget (€) *</label>
                    <input type="number" name="ia_budget" min="1" step="0.01" placeholder="5000" value="<?= htmlspecialchars($_POST['ia_budget'] ?? '') ?>">
                </div>
                <button type="submit" class="btn-ia" onclick="document.getElementById('iaLoading').classList.add('show')">
                    ✨ Générer
                </button>
            </div>
        </form>
        <div class="ia-loading" id="iaLoading"><div class="spinner"></div> L'IA génère votre campagne…</div>
        <?php if ($iaError): ?>
            <div class="ia-error">⚠️ <?= htmlspecialchars($iaError) ?></div>
        <?php endif; ?>
        <?php if ($iaResult): ?>
        <div class="ia-result">
            <div class="ia-result-title">🎯 Campagne générée par l'IA</div>
            <?php if (!empty($iaResult['titre'])): ?><div class="ia-field"><div class="ia-label">Titre</div><div class="ia-value big"><?= htmlspecialchars($iaResult['titre']) ?></div></div><?php endif; ?>
            <?php if (!empty($iaResult['description'])): ?><div class="ia-field"><div class="ia-label">Description</div><div class="ia-value"><?= htmlspecialchars($iaResult['description']) ?></div></div><?php endif; ?>
            <?php if (!empty($iaResult['objectif'])): ?><div class="ia-field"><div class="ia-label">Objectif</div><div class="ia-value"><?= htmlspecialchars($iaResult['objectif']) ?></div></div><?php endif; ?>
            <?php if (!empty($iaResult['type_contenu'])): ?><div class="ia-field"><div class="ia-label">Type de contenu recommandé</div><div class="ia-value"><?= htmlspecialchars($iaResult['type_contenu']) ?></div></div><?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- CAMPAGNES -->
    <div class="section-head">
        <h2>📋 Mes campagnes</h2>
        <span class="count-pill" id="visibleCount"><?= $totalActives ?></span>
    </div>

    <div class="tools-bar">
        <div class="search-wrap">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
            <input type="text" id="searchInput" class="search-input" placeholder="Rechercher…">
        </div>
        <select id="filterStatut" class="filter-select" onchange="filterCampagnes()">
            <option value="">Tous statuts</option>
            <?php foreach ($statuts as $s): ?><option value="<?= $s ?>"><?= ucfirst($s) ?></option><?php endforeach; ?>
        </select>
        <select id="sortSelect" class="filter-select" onchange="sortCampagnes()">
            <option value="">Trier par…</option>
            <option value="titre">Nom A→Z</option>
            <option value="budget_desc">Budget ↓</option>
            <option value="budget_asc">Budget ↑</option>
        </select>
    </div>

    <?php if (empty($campagnes)): ?>
    <div class="empty-state">
        <div style="font-size:48px;margin-bottom:14px;">⚡</div>
        <h3>Aucune campagne pour l'instant</h3>
        <p>Créez votre première campagne pour collaborer avec des créateurs.</p>
        <a href="#formAnchor" class="btn-primary">➕ Créer ma première campagne</a>
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
                        📦 <?= $nbProd ?> produit<?= $nbProd !== 1 ? 's' : '' ?> +
                    </button>
                </div>
            </div>
            <div class="camp-actions">
                <a href="?modifier=<?= $c['idCampagne'] ?>#formAnchor" class="btn-edit-camp">✏️ Modifier</a>
                <button class="btn-arch-camp" onclick="ajaxArchive(<?= $c['idCampagne'] ?>)">📦</button>
                <button class="btn-del-camp" onclick="openDeleteModal(<?= $c['idCampagne'] ?>, '<?= htmlspecialchars(addslashes($c['titreCampagne'])) ?>')">🗑</button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- FORM -->
    <div id="formAnchor" style="margin-top:40px;">
        <div class="form-card <?= $editCampagne ? 'edit-mode' : '' ?>">
            <div class="form-card-header">
                <h2><?= $editCampagne ? '✏️ Modifier la campagne' : '➕ Nouvelle campagne' ?></h2>
                <p><?= $editCampagne ? 'Modifiez les informations ci-dessous.' : 'Remplissez les détails de votre nouvelle campagne.' ?></p>
            </div>
            <?php if ($editCampagne): ?>
            <div class="edit-banner">
                <span>Modification : <strong><?= htmlspecialchars($editCampagne['titreCampagne']) ?></strong></span>
                <a href="index.php">✕ Annuler</a>
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
                            <label>Titre *</label>
                            <input type="text" name="titre" id="fTitre" class="form-input" maxlength="100"
                                   value="<?= $editCampagne ? htmlspecialchars($editCampagne['titreCampagne']) : '' ?>"
                                   placeholder="Ex : Collab Été 2025">
                            <div class="field-error" id="errTitre">Titre requis (2-100 car., sans HTML)</div>
                        </div>
                        <div class="form-group">
                            <label>Statut</label>
                            <select name="statut" class="form-input">
                                <?php foreach ($statuts as $s): ?>
                                <option value="<?= $s ?>" <?= ($editCampagne && $editCampagne['statut'] === $s) ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-input" placeholder="Décrivez les objectifs, l'audience cible…"><?= $editCampagne ? htmlspecialchars($editCampagne['description']) : '' ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Objectif</label>
                        <input type="text" name="objectif" class="form-input" maxlength="200"
                               placeholder="Ex : 50K vues, notoriété marque"
                               value="<?= $editCampagne ? htmlspecialchars($editCampagne['objectif'] ?? '') : '' ?>">
                    </div>

                    <div class="form-grid-3">
                        <div class="form-group">
                            <label>Date début</label>
                            <input type="text" name="dateDebut" id="fDateDebut" class="form-input" placeholder="AAAA-MM-JJ"
                                   value="<?= $editCampagne ? htmlspecialchars($editCampagne['dateDebut'] ?? '') : '' ?>">
                            <div class="field-error" id="errDateDebut">Format AAAA-MM-JJ requis</div>
                        </div>
                        <div class="form-group">
                            <label>Date fin</label>
                            <input type="text" name="dateFin" id="fDateFin" class="form-input" placeholder="AAAA-MM-JJ"
                                   value="<?= $editCampagne ? htmlspecialchars($editCampagne['dateFin'] ?? '') : '' ?>">
                            <div class="field-error" id="errDateFin">Format AAAA-MM-JJ requis</div>
                            <div class="field-error" id="errDateCoherence" style="color:#f59e0b;">⚠ La date de fin doit être après le début</div>
                        </div>
                        <div class="form-group">
                            <label>Budget (€) *</label>
                            <div class="prefix-wrap" id="budgetWrapper">
                                <span class="prefix">€</span>
                                <input type="text" name="budget" id="fBudget" class="form-input"
                                       placeholder="0.00"
                                       value="<?= $editCampagne ? htmlspecialchars($editCampagne['budget'] ?? '') : '' ?>">
                            </div>
                            <div class="field-error" id="errBudget">Budget valide requis (≥ 0)</div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary"><?= $editCampagne ? '💾 Enregistrer' : '🚀 Créer la campagne' ?></button>
                        <?php if ($editCampagne): ?><a href="index.php" class="btn-cancel-form">Annuler</a><?php endif; ?>
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
                <div class="drawer-sub">Ajoutez ou retirez des produits liés.</div>
            </div>
            <button class="drawer-close" onclick="closeDrawer()">✕</button>
        </div>
        <div id="drawerContent"><div class="drawer-empty">⏳ Chargement…</div></div>
    </div>
</div>

<!-- MODAL SUPPRESSION -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal-box">
        <div class="modal-title">🗑 Supprimer la campagne ?</div>
        <div class="modal-text" id="deleteModalText"></div>
        <div class="modal-actions">
            <button class="btn-modal-cancel" onclick="closeDeleteModal()">Annuler</button>
            <a href="#" class="btn-modal-del" id="deleteModalLink">Supprimer</a>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?= $baseUrl ?>';

// ── FLASH ──────────────────────────────────────────────────────────
const flash = document.getElementById('flashMsg');
if (flash) setTimeout(() => { flash.style.opacity='0'; flash.style.transition='opacity .4s'; setTimeout(()=>flash.remove(),400); }, 4000);

// ── FILTER + SORT ──────────────────────────────────────────────────
document.getElementById('searchInput').addEventListener('input', filterCampagnes);
function filterCampagnes() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    const s = document.getElementById('filterStatut').value;
    const cards = document.querySelectorAll('#campGrid .camp-card');
    let v = 0;
    cards.forEach(card => {
        const mQ = !q || (card.dataset.titre||'').includes(q);
        const mS = !s || card.dataset.statut === s;
        card.style.display = mQ && mS ? '' : 'none';
        if (mQ && mS) v++;
    });
    const el = document.getElementById('visibleCount');
    if (el) el.textContent = v;
}
function sortCampagnes() {
    const mode = document.getElementById('sortSelect').value;
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
        const img = p.image ? `<img src="${BASE_URL}/Vue/public/produits/${p.image}" alt="">` : '📦';
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
</body>
</html>