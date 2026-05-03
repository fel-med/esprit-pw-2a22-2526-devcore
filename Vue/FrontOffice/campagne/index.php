<?php
require_once __DIR__ . '/../../../Controleur/campagneC.php';
require_once __DIR__ . '/../../../Controleur/produitC.php';
require_once __DIR__ . '/../../../Modele/campagne.php';

session_start();
$campagneC = new CampagneC();
$produitC  = new ProduitC();
$baseUrl   = '/projet/Esprit-PW-2A22-2526-Devcore';
$id_marque = $_SESSION['user_id'] ?? 1;

$message     = '';
$messageType = '';
$editCampagne = null;

// ── AJAX : toggle archive ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'archive') {
    $campagneC->toggleArchive(intval($_POST['id']));
    echo json_encode(['ok' => true]); exit;
}
// ── AJAX : lier produit ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'lier_produit') {
    $idC = intval($_POST['idCampagne']);
    $idP = intval($_POST['idProduit']);
    $camp = $campagneC->recupererCampagne($idC);
    if ($camp && (int)$camp['idMarque'] === $id_marque) {
        $campagneC->ajouterProduitCampagne($idC, $idP);
        echo json_encode(['ok' => true]);
    } else {
        echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    }
    exit;
}
// ── AJAX : retirer produit ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'retirer_produit') {
    $idC = intval($_POST['idCampagne']);
    $idP = intval($_POST['idProduit']);
    $camp = $campagneC->recupererCampagne($idC);
    if ($camp && (int)$camp['idMarque'] === $id_marque) {
        $campagneC->retirerProduitCampagne($idC, $idP);
        echo json_encode(['ok' => true]);
    } else {
        echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
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

// ── ADD ───────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'ajouter') {
    $titre     = trim($_POST['titre']??'');
    $budget    = str_replace(',','.',$_POST['budget']??'');
    $dateDebut = trim($_POST['dateDebut']??'');
    $dateFin   = trim($_POST['dateFin']??'');
    $errors = [];
    if (strlen($titre) < 2 || strlen($titre) > 100) $errors[] = "Title required (2–100 chars).";
    if (preg_match('/<[^>]+>/', $titre)) $errors[] = "No HTML allowed in title.";
    if (!is_numeric($budget) || floatval($budget) < 0) $errors[] = "Budget must be a positive number.";
    if ($dateDebut && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateDebut)) $errors[] = "Start date: YYYY-MM-DD.";
    if ($dateFin   && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFin))   $errors[] = "End date: YYYY-MM-DD.";
    if ($dateDebut && $dateFin && $dateFin < $dateDebut) $errors[] = "End date must be after start date.";
    if (empty($errors)) {
        $campagne = new Campagne(null, htmlspecialchars($titre), trim($_POST['description']??''),
            $dateDebut ?: null, $dateFin ?: null,
            floatval($budget), $_POST['statut']??'brouillon', $id_marque,
            trim($_POST['objectif']??''), 0);
        $campagneC->ajouterCampagne($campagne);
        $message = "Campaign added successfully!"; $messageType = "success";
    } else {
        $message = implode(' | ', $errors); $messageType = "error";
    }
}
// ── UPDATE ────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'modifier') {
    $titre  = trim($_POST['titre']??'');
    $budget = str_replace(',','.',$_POST['budget']??'');
    $dateDebut = trim($_POST['dateDebut']??'');
    $dateFin   = trim($_POST['dateFin']??'');
    $errors = [];
    if (strlen($titre) < 2) $errors[] = "Title required (min 2 chars).";
    if (!is_numeric($budget) || floatval($budget) < 0) $errors[] = "Budget must be >= 0.";
    if ($dateDebut && $dateFin && $dateFin < $dateDebut) $errors[] = "End date must be after start date.";
    if (empty($errors)) {
        $campagne = new Campagne(null, htmlspecialchars($titre), trim($_POST['description']??''),
            $dateDebut ?: null, $dateFin ?: null,
            floatval($budget), $_POST['statut']??'brouillon', $id_marque,
            trim($_POST['objectif']??''), intval($_POST['estArchive']??0));
        $campagneC->modifierCampagne($campagne, intval($_POST['id']));
        $message = "Campaign updated!"; $messageType = "success";
    } else {
        $message = implode(' | ', $errors); $messageType = "error";
    }
}
// ── DELETE ─────────────────────────────────────────────────────────────────────
if (isset($_GET['supprimer'])) {
    $campagneC->supprimerCampagne(intval($_GET['supprimer']));
    $message = "Campaign deleted."; $messageType = "delete";
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
$budgets       = array_column($campagnes, 'budget');
$budgetTotal   = array_sum($budgets);
$nbActives     = count(array_filter($campagnes, fn($c) => $c['statut'] === 'active'));
$nbBrouillons  = count(array_filter($campagnes, fn($c) => $c['statut'] === 'brouillon'));
$nbTerminees   = count(array_filter($campagnes, fn($c) => $c['statut'] === 'terminee'));

function statutLabel($s){ return match($s){'active'=>'✅ Active','terminee'=>'🏁 Ended','annulee'=>'❌ Cancelled',default=>'📝 Draft'}; }
function statutColor($s){ return match($s){'active'=>'#0ea370','terminee'=>'#3b82f6','annulee'=>'#f43f5e',default=>'#f59e0b'}; }
function statutBg($s){    return match($s){'active'=>'#edfaf5','terminee'=>'#eff6ff','annulee'=>'#fff1f3',default=>'#fffbeb'}; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Campaigns — Cre8Connect</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:ital,wght@0,700;0,800;1,700&display=swap" rel="stylesheet">
<style>
:root{
    --primary:#5b4fff;--primary-hover:#4a3ee8;--primary-light:#eeecff;
    --primary-glow:rgba(91,79,255,0.18);--primary-border:rgba(91,79,255,0.2);
    --text-main:#0f0e1a;--text-sub:#6b6f80;--text-dim:#a0a4b2;
    --border:#ebebf2;--bg:#f6f6fc;--white:#ffffff;
    --danger:#f43f5e;--danger-light:#fff1f3;--danger-border:rgba(244,63,94,0.2);
    --success:#0ea370;--success-light:#edfaf5;--success-border:rgba(14,163,112,0.2);
    --warning:#f59e0b;--warning-light:#fffbeb;--warning-border:rgba(245,158,11,0.2);
    --info:#3b82f6;--info-light:#eff6ff;
    --card-shadow:0 1px 3px rgba(15,14,26,0.06),0 4px 16px rgba(91,79,255,0.06);
    --card-shadow-hover:0 8px 32px rgba(91,79,255,0.14);
    --radius:14px;--radius-sm:8px;--nav-h:66px;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text-main);min-height:100vh;}

/* NAV */
nav{background:var(--white);border-bottom:1px solid var(--border);padding:0 48px;height:var(--nav-h);display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:200;box-shadow:0 2px 12px rgba(15,14,26,0.04);}
.nav-logo{display:flex;align-items:center;gap:10px;text-decoration:none;}
.nav-logo img{width:36px;height:36px;object-fit:contain;border-radius:9px;}
.nav-logo-text{font-family:'Fraunces',serif;font-size:19px;font-weight:800;color:var(--primary);letter-spacing:-0.5px;}
.nav-links{display:flex;gap:6px;list-style:none;}
.nav-links a{text-decoration:none;color:var(--text-sub);font-size:13.5px;font-weight:600;padding:6px 14px;border-radius:8px;transition:background .18s,color .18s;}
.nav-links a:hover,.nav-links a.active{background:var(--primary-light);color:var(--primary);}
.nav-right{display:flex;align-items:center;gap:12px;}
.nav-badge{background:var(--primary);color:#fff;border-radius:20px;padding:5px 14px;font-size:12px;font-weight:700;}
.nav-avatar{width:36px;height:36px;border-radius:50%;background:var(--primary-light);color:var(--primary);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:14px;cursor:pointer;border:2px solid var(--primary-border);}

/* TOAST */
#toastContainer{position:fixed;top:76px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:8px;pointer-events:none;}
.toast{display:flex;align-items:center;gap:10px;background:var(--white);border:1.5px solid var(--border);border-radius:var(--radius-sm);padding:12px 16px;font-size:13px;min-width:240px;max-width:340px;box-shadow:0 8px 24px rgba(15,14,26,.12);pointer-events:all;animation:toastIn .3s ease;}
.toast.hide{opacity:0;transform:translateX(20px);transition:opacity .4s,transform .4s;}
@keyframes toastIn{from{opacity:0;transform:translateX(20px);}to{opacity:1;transform:translateX(0);}}
.toast-success{border-color:var(--success-border);}
.toast-error{border-color:var(--danger-border);}
.toast-delete{border-color:var(--danger-border);}
.toast-close{margin-left:auto;background:none;border:none;color:var(--text-dim);cursor:pointer;font-size:14px;}

/* PAGE */
.page-wrapper{max-width:1160px;margin:0 auto;padding:40px 24px 80px;}
.page-header{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:32px;gap:20px;flex-wrap:wrap;}
.page-header-left h1{font-family:'Fraunces',serif;font-size:30px;font-weight:800;letter-spacing:-0.8px;line-height:1.1;}
.page-header-left p{color:var(--text-sub);font-size:14px;margin-top:5px;}
.page-header-actions{display:flex;gap:10px;align-items:center;flex-wrap:wrap;}
.btn-add{display:inline-flex;align-items:center;gap:8px;background:var(--primary);color:#fff;border:none;border-radius:var(--radius-sm);padding:11px 20px;font-size:13.5px;font-weight:700;font-family:'DM Sans',sans-serif;cursor:pointer;text-decoration:none;transition:background .2s,transform .15s;box-shadow:0 3px 12px var(--primary-glow);}
.btn-add:hover{background:var(--primary-hover);transform:translateY(-1px);}

/* FLASH */
.flash{padding:13px 18px;border-radius:var(--radius-sm);font-size:14px;font-weight:600;margin-bottom:24px;display:flex;align-items:center;gap:10px;animation:slideDown .3s ease;}
@keyframes slideDown{from{opacity:0;transform:translateY(-10px);}to{opacity:1;transform:translateY(0);}}
.flash.success{background:var(--success-light);color:#065f46;border:1px solid var(--success-border);}
.flash.error{background:var(--danger-light);color:#9f1239;border:1px solid var(--danger-border);}
.flash.delete{background:var(--danger-light);color:#9f1239;border:1px solid var(--danger-border);}

/* KPI */
.kpi-strip{display:grid;grid-template-columns:repeat(5,1fr);gap:14px;margin-bottom:32px;}
.kpi-card{background:var(--white);border:1px solid var(--border);border-radius:var(--radius);padding:18px 20px;position:relative;overflow:hidden;transition:box-shadow .2s;cursor:default;}
.kpi-card:hover{box-shadow:var(--card-shadow-hover);}
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
.kpi-card:nth-child(4) .kpi-icon{background:var(--info-light);}
.kpi-card:nth-child(5) .kpi-icon{background:#f1f5f9;}
.kpi-value{font-family:'Fraunces',serif;font-size:24px;font-weight:800;color:var(--text-main);letter-spacing:-0.5px;line-height:1;}
.kpi-label{font-size:11px;font-weight:600;color:var(--text-sub);margin-top:4px;text-transform:uppercase;letter-spacing:.05em;}

/* PROGRESS BAR (budget used) */
.budget-progress-wrap{margin-top:6px;}
.budget-progress-bar{height:4px;background:var(--border);border-radius:10px;overflow:hidden;}
.budget-progress-fill{height:100%;background:linear-gradient(90deg,var(--primary),#a78bfa);border-radius:10px;transition:width .6s ease;}
.budget-progress-label{font-size:10px;color:var(--text-dim);margin-top:3px;}

/* SECTION HEAD */
.section-head{display:flex;align-items:center;gap:10px;margin-bottom:14px;flex-wrap:wrap;}
.section-head h2{font-family:'Fraunces',serif;font-size:17px;font-weight:800;}
.count-pill{background:var(--primary-light);color:var(--primary);border-radius:20px;padding:3px 11px;font-size:12px;font-weight:700;}
.tools-bar{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:18px;}
.search-wrap{position:relative;}
.search-wrap svg{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--text-dim);pointer-events:none;width:14px;height:14px;}
.search-input{background:var(--white);border:1.5px solid var(--border);border-radius:var(--radius-sm);padding:8px 12px 8px 32px;font-size:13px;font-family:'DM Sans',sans-serif;color:var(--text-main);width:220px;outline:none;transition:border-color .2s,box-shadow .2s;}
.search-input:focus{border-color:var(--primary);box-shadow:0 0 0 3px var(--primary-glow);}
.filter-select{background:var(--white);border:1.5px solid var(--border);border-radius:var(--radius-sm);padding:8px 12px;font-size:13px;font-family:'DM Sans',sans-serif;outline:none;cursor:pointer;color:var(--text-main);}

/* CAMP GRID */
.camp-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:18px;margin-bottom:32px;}
.camp-card{background:var(--white);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;box-shadow:var(--card-shadow);display:flex;flex-direction:column;transition:transform .22s,box-shadow .22s,border-color .22s;position:relative;}
.camp-card:hover{transform:translateY(-3px);box-shadow:var(--card-shadow-hover);border-color:var(--primary-border);}
.camp-card-header{padding:18px 18px 0;display:flex;align-items:flex-start;justify-content:space-between;gap:8px;}
.camp-card-title{font-family:'Fraunces',serif;font-size:16px;font-weight:800;color:var(--text-main);line-height:1.25;flex:1;}
.camp-badge{display:inline-flex;align-items:center;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;flex-shrink:0;}
.camp-card-body{padding:12px 18px 16px;flex:1;display:flex;flex-direction:column;gap:8px;}
.camp-desc{font-size:13px;color:var(--text-sub);line-height:1.65;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;}
.camp-obj{font-size:12px;background:var(--primary-light);color:var(--primary);border-radius:7px;padding:6px 10px;font-weight:600;}
.camp-meta{display:flex;flex-direction:column;gap:4px;font-size:12px;color:var(--text-sub);}
.camp-budget{font-family:'Fraunces',serif;font-size:22px;font-weight:800;color:var(--primary);margin-top:4px;}

/* COUNTDOWN INLINE */
.camp-countdown{display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;border-radius:20px;padding:3px 10px;margin-top:4px;}
.camp-countdown.active{background:var(--success-light);color:var(--success);}
.camp-countdown.ending{background:var(--warning-light);color:#92400e;}
.camp-countdown.ended{background:var(--danger-light);color:var(--danger);}
.camp-countdown.draft{background:#f1f5f9;color:#64748b;}

/* PRODUCTS BADGE */
.camp-products-badge{display:inline-flex;align-items:center;gap:5px;background:var(--success-light);color:var(--success);border:1px solid var(--success-border);border-radius:20px;padding:4px 12px;font-size:12px;font-weight:700;cursor:pointer;transition:background .18s;border:none;font-family:'DM Sans',sans-serif;}
.camp-products-badge:hover{background:#d1fae5;}
.camp-products-badge.zero{background:var(--bg);color:var(--text-dim);border:1px solid var(--border);}

/* ACTIONS */
.camp-actions{display:flex;gap:8px;padding:14px 18px;border-top:1px solid var(--border);flex-wrap:wrap;}
.btn-camp-edit{flex:1;padding:8px;background:var(--primary-light);color:var(--primary);border:none;border-radius:7px;font-size:12px;font-weight:700;font-family:'DM Sans',sans-serif;cursor:pointer;text-align:center;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;gap:5px;transition:background .18s;}
.btn-camp-edit:hover{background:#ddddf8;}
.btn-camp-del{flex:0 0 auto;padding:8px 10px;background:var(--danger-light);color:var(--danger);border:none;border-radius:7px;font-size:12px;cursor:pointer;font-family:'DM Sans',sans-serif;display:inline-flex;align-items:center;transition:background .18s;}
.btn-camp-dup{flex:0 0 auto;padding:8px 10px;background:#f0f2f8;color:var(--text-sub);border:none;border-radius:7px;font-size:12px;cursor:pointer;font-family:'DM Sans',sans-serif;transition:background .18s;}
.btn-camp-dup:hover{background:#e2e6f0;}

/* EMPTY */
.empty-state{text-align:center;padding:60px 20px;background:var(--white);border:1.5px dashed var(--border);border-radius:var(--radius);}
.empty-icon{font-size:48px;margin-bottom:14px;}
.empty-state h3{font-family:'Fraunces',serif;font-size:18px;font-weight:800;margin-bottom:6px;}
.empty-state p{font-size:13.5px;color:var(--text-sub);margin-bottom:20px;}

/* FORM */
.form-layout{margin-top:30px;}
.form-card{background:var(--white);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;box-shadow:var(--card-shadow);}
.form-card.edit-mode{border-color:var(--primary-border);box-shadow:0 0 0 3px var(--primary-glow);}
.form-card-header{padding:22px 26px 0;margin-bottom:6px;}
.form-card-header h2{font-family:'Fraunces',serif;font-size:19px;font-weight:800;}
.form-card-header p{font-size:13px;color:var(--text-sub);margin-top:3px;}
.edit-banner{display:flex;align-items:center;justify-content:space-between;background:var(--primary-light);border:1px solid var(--primary-border);border-radius:var(--radius-sm);padding:9px 14px;margin:14px 26px;font-size:13px;}
.edit-banner a{color:var(--danger);text-decoration:none;font-weight:700;font-size:12px;}
.form-inner{padding:14px 26px 28px;}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px;}
.form-grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:18px;}
.form-group{display:flex;flex-direction:column;gap:4px;margin-bottom:14px;}
label{font-size:12.5px;font-weight:700;color:var(--text-sub);}
.form-input{width:100%;padding:10px 13px;border:1.5px solid var(--border);border-radius:var(--radius-sm);font-size:13.5px;color:var(--text-main);background:#fafafa;transition:border-color .2s,box-shadow .2s;outline:none;font-family:'DM Sans',sans-serif;}
.form-input:focus{border-color:var(--primary);box-shadow:0 0 0 3px var(--primary-glow);background:var(--white);}
.form-input.is-invalid{border-color:var(--danger) !important;box-shadow:0 0 0 3px rgba(244,63,94,.1);}
.form-input.is-valid{border-color:var(--success);}
textarea.form-input{resize:vertical;min-height:90px;}
.input-with-prefix{display:flex;align-items:center;border:1.5px solid var(--border);border-radius:var(--radius-sm);background:#fafafa;overflow:hidden;transition:border-color .2s;}
.input-with-prefix:focus-within{border-color:var(--primary);box-shadow:0 0 0 3px var(--primary-glow);}
.input-with-prefix.is-invalid{border-color:var(--danger);}
.prefix{padding:0 11px;font-size:13px;color:var(--text-dim);border-right:1.5px solid var(--border);background:var(--white);min-height:42px;display:flex;align-items:center;}
.input-with-prefix .form-input{border:none;background:transparent;flex:1;}
.field-error{font-size:11.5px;color:var(--danger);font-weight:600;display:none;margin-top:3px;align-items:center;gap:4px;}
.field-error.visible{display:flex;}
.field-error::before{content:'⚠';}
.char-counter-label{font-size:10px;color:var(--text-dim);float:right;font-weight:400;}
.char-counter-label.warn{color:var(--warning);}
.date-coherence{font-size:11.5px;color:var(--warning);display:none;margin-top:3px;}
.date-coherence.visible{display:flex;align-items:center;gap:4px;}
.form-actions{display:flex;gap:10px;align-items:center;padding-top:18px;border-top:1px solid var(--border);}
.btn-submit{background:var(--primary);color:#fff;border:none;border-radius:var(--radius-sm);padding:11px 26px;font-size:14px;font-weight:700;cursor:pointer;font-family:'DM Sans',sans-serif;box-shadow:0 3px 10px var(--primary-glow);transition:background .2s;}
.btn-submit:hover{background:var(--primary-hover);}
.btn-submit:disabled{opacity:.5;cursor:not-allowed;}
.btn-cancel{background:var(--bg);color:var(--text-sub);border:1.5px solid var(--border);border-radius:var(--radius-sm);padding:10px 18px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;font-family:'DM Sans',sans-serif;}

/* DRAWER */
.drawer-overlay{position:fixed;inset:0;background:rgba(15,14,26,.45);z-index:200;display:none;align-items:flex-end;justify-content:center;}
.drawer-overlay.open{display:flex;}
.drawer{width:100%;max-width:680px;background:var(--white);border-radius:var(--radius) var(--radius) 0 0;padding:28px 32px 36px;box-shadow:0 -8px 40px rgba(15,14,26,.15);animation:slideUp .24s ease;max-height:85vh;overflow-y:auto;display:flex;flex-direction:column;gap:20px;}
@keyframes slideUp{from{transform:translateY(40px);opacity:0;}to{transform:translateY(0);opacity:1;}}
.drawer-header{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;}
.drawer-title{font-family:'Fraunces',serif;font-size:20px;font-weight:800;}
.drawer-sub{font-size:13px;color:var(--text-sub);margin-top:3px;}
.drawer-close{background:var(--bg);border:1.5px solid var(--border);border-radius:50%;width:34px;height:34px;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:16px;color:var(--text-sub);transition:all .2s;}
.drawer-close:hover{background:var(--danger-light);color:var(--danger);}
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
.drawer-loader{text-align:center;padding:18px;color:var(--text-sub);font-size:13px;}

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

@media(max-width:700px){nav{padding:0 18px;}.page-wrapper{padding:24px 14px 60px;}.form-grid{grid-template-columns:1fr;}.form-grid-3{grid-template-columns:1fr;}.kpi-strip{grid-template-columns:repeat(2,1fr);}}
</style>
</head>
<body>
<div id="toastContainer"></div>

<nav>
    <a href="<?= $baseUrl ?>" class="nav-logo">
        <img src="<?= $baseUrl ?>/Vue/public/images/logo.png" alt="">
        <span class="nav-logo-text">Cre8Connect</span>
    </a>
    <ul class="nav-links">
        <li><a href="#">Dashboard</a></li>
        <li><a href="#">Offers</a></li>
        <li><a href="#" class="active">Campaigns</a></li>
        <li><a href="#">Products</a></li>
        <li><a href="#">Contracts</a></li>
    </ul>
    <div class="nav-right">
        <span class="nav-badge">Brand</span>
        <div class="nav-avatar">M</div>
    </div>
</nav>

<div class="page-wrapper">

    <div class="page-header">
        <div class="page-header-left">
            <h1>⚡ My Campaigns</h1>
            <p>Manage your campaigns and their linked products.</p>
        </div>
        <div class="page-header-actions">
            <a href="#formAnchor" class="btn-add">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                New Campaign
            </a>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="flash <?= $messageType ?>" id="flashMsg">
        <?= $messageType==='success'?'✅':'🗑' ?> <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <!-- KPI -->
    <div class="kpi-strip">
        <div class="kpi-card"><div class="kpi-icon">⚡</div><div class="kpi-value"><?= $totalActives ?></div><div class="kpi-label">Total Campaigns</div></div>
        <div class="kpi-card"><div class="kpi-icon">✅</div><div class="kpi-value"><?= $nbActives ?></div><div class="kpi-label">Active Now</div></div>
        <div class="kpi-card"><div class="kpi-icon">📝</div><div class="kpi-value"><?= $nbBrouillons ?></div><div class="kpi-label">Drafts</div></div>
        <div class="kpi-card"><div class="kpi-icon">🏁</div><div class="kpi-value"><?= $nbTerminees ?></div><div class="kpi-label">Ended</div></div>
        <div class="kpi-card">
            <div class="kpi-icon">💰</div>
            <div class="kpi-value"><?= number_format($budgetTotal,0,',',' ') ?> €</div>
            <div class="kpi-label">Total Budget</div>
            <?php if ($budgetTotal > 0): ?>
            <div class="budget-progress-wrap">
                <div class="budget-progress-bar">
                    <div class="budget-progress-fill" style="width:<?= min(100, round($nbActives / max(1,$totalActives) * 100)) ?>%"></div>
                </div>
                <div class="budget-progress-label"><?= round($nbActives / max(1,$totalActives) * 100) ?>% campaigns active</div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- FILTER -->
    <div class="section-head">
        <h2>📋 All Campaigns</h2>
        <span class="count-pill" id="visibleCount"><?= $totalActives ?></span>
    </div>
    <div class="tools-bar">
        <div class="search-wrap">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
            <input type="text" class="search-input" id="searchInput" placeholder="Search campaigns…">
        </div>
        <select class="filter-select" id="filterStatut" onchange="filterCampagnes()">
            <option value="">All statuses</option>
            <?php foreach ($statuts as $s): ?><option value="<?= $s ?>"><?= ucfirst($s) ?></option><?php endforeach; ?>
        </select>
        <select class="filter-select" id="sortSelect" onchange="sortCampagnes()">
            <option value="">Sort by…</option>
            <option value="titre">Name A→Z</option>
            <option value="budget_desc">Budget ↓</option>
            <option value="budget_asc">Budget ↑</option>
            <option value="date">Start date</option>
        </select>
    </div>

    <!-- CAMPAIGN CARDS -->
    <?php if (empty($campagnes)): ?>
    <div class="empty-state">
        <div class="empty-icon">⚡</div>
        <h3>No campaigns yet</h3>
        <p>Launch your first campaign to start collaborating with creators.</p>
        <a href="#formAnchor" class="btn-add">➕ Create my first campaign</a>
    </div>
    <?php else: ?>
    <div class="camp-grid" id="campGrid">
        <?php foreach ($campagnes as $c):
            $nbProd = $campagneC->compterProduitsCampagne($c['idCampagne']);
            $today = date('Y-m-d');
            $fin = $c['dateFin']??'';
            $deb = $c['dateDebut']??'';
        ?>
        <div class="camp-card"
             data-titre="<?= strtolower(htmlspecialchars($c['titreCampagne'])) ?>"
             data-statut="<?= htmlspecialchars($c['statut']) ?>"
             data-budget="<?= (float)$c['budget'] ?>"
             data-date="<?= $deb ?>">
            <div class="camp-card-header">
                <div class="camp-card-title"><?= htmlspecialchars($c['titreCampagne']) ?></div>
                <span class="camp-badge" style="background:<?= statutBg($c['statut']) ?>;color:<?= statutColor($c['statut']) ?>"><?= statutLabel($c['statut']) ?></span>
            </div>
            <div class="camp-card-body">
                <div class="camp-desc"><?= htmlspecialchars($c['description']) ?></div>
                <?php if (!empty($c['objectif'])): ?>
                <div class="camp-obj">🎯 <?= htmlspecialchars($c['objectif']) ?></div>
                <?php endif; ?>
                <div class="camp-meta">
                    <div>📅 <?= $deb?:'—' ?> → 🏁 <?= $fin?:'—' ?></div>
                </div>
                <div class="camp-budget"><?= number_format((float)$c['budget'],2,',',' ') ?> €</div>
                <!-- COUNTDOWN -->
                <?php if ($c['statut'] === 'active' && $fin): ?>
                <div class="camp-countdown <?= $fin < $today ? 'ended' : (strtotime($fin)-time() < 7*86400 ? 'ending' : 'active') ?>"
                     data-fin="<?= $fin ?>">
                    <?php if ($fin < $today): ?>⚠ Campaign expired
                    <?php else: ?>⏳ <span class="countdown-text" data-fin="<?= $fin ?>">…</span><?php endif; ?>
                </div>
                <?php elseif ($c['statut'] === 'brouillon'): ?>
                <div class="camp-countdown draft">📝 Not published yet</div>
                <?php endif; ?>
                <!-- PRODUCTS BADGE -->
                <div>
                    <button class="camp-products-badge <?= $nbProd===0?'zero':'' ?>"
                            onclick="openDrawer(<?= $c['idCampagne'] ?>,'<?= htmlspecialchars(addslashes($c['titreCampagne'])) ?>')">
                        📦 <?= $nbProd ?> product<?= $nbProd!==1?'s':'' ?>
                        <svg width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    </button>
                </div>
            </div>
            <div class="camp-actions">
                <a href="?modifier=<?= $c['idCampagne'] ?>#formAnchor" class="btn-camp-edit">✏️ Edit</a>
                <button class="btn-camp-dup" onclick="duplicateCampagne(<?= $c['idCampagne'] ?>)" title="Duplicate">📋</button>
                <button class="btn-camp-del" onclick="openDeleteModal(<?= $c['idCampagne'] ?>,'<?= htmlspecialchars(addslashes($c['titreCampagne'])) ?>')">🗑</button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ─── FORM ─────────────────────────────────────────────────────────── -->
    <div class="form-layout" id="formAnchor">
        <div class="form-card <?= $editCampagne?'edit-mode':'' ?>">
            <div class="form-card-header">
                <h2><?= $editCampagne?'✏️ Edit Campaign':'➕ New Campaign' ?></h2>
                <p><?= $editCampagne?'Update your campaign details.':'Fill in the details to launch a new campaign.' ?></p>
            </div>
            <?php if ($editCampagne): ?>
            <div class="edit-banner">
                <span>Editing: <strong><?= htmlspecialchars($editCampagne['titreCampagne']) ?></strong></span>
                <a href="index.php">✕ Cancel</a>
            </div>
            <?php endif; ?>
            <div class="form-inner">
                <form method="POST" id="campagneForm" novalidate>
                    <input type="hidden" name="action" value="<?= $editCampagne?'modifier':'ajouter' ?>">
                    <?php if ($editCampagne): ?>
                    <input type="hidden" name="id" value="<?= $editCampagne['idCampagne'] ?>">
                    <input type="hidden" name="estArchive" value="<?= intval($editCampagne['estArchive']??0) ?>">
                    <?php endif; ?>

                    <div class="form-grid">
                        <!-- TITLE -->
                        <div class="form-group">
                            <label>Campaign Title * <span class="char-counter-label" id="ctrTitre">0/100</span></label>
                            <input type="text" name="titre" id="fTitre" class="form-input" maxlength="100"
                                   value="<?= $editCampagne?htmlspecialchars($editCampagne['titreCampagne']):'' ?>"
                                   placeholder="e.g. Summer Collab 2025">
                            <div class="field-error" id="errTitre">Title required (2–100 chars, no HTML).</div>
                        </div>
                        <!-- STATUS -->
                        <div class="form-group">
                            <label>Status</label>
                            <select name="statut" class="form-input">
                                <?php foreach ($statuts as $s): ?>
                                <option value="<?= $s ?>" <?= ($editCampagne&&$editCampagne['statut']===$s)?'selected':'' ?>><?= ucfirst($s) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- DESCRIPTION -->
                    <div class="form-group">
                        <label>Description * <span class="char-counter-label" id="ctrDesc">0/600</span></label>
                        <textarea name="description" id="fDesc" class="form-input" maxlength="600"
                                  placeholder="Describe campaign goals, target audience…"><?= $editCampagne?htmlspecialchars($editCampagne['description']):'' ?></textarea>
                        <div class="field-error" id="errDesc">Description required (min 5 chars).</div>
                    </div>

                    <!-- OBJECTIVE -->
                    <div class="form-group">
                        <label>Objective <span class="char-counter-label" id="ctrObj">0/200</span></label>
                        <input type="text" name="objectif" id="fObj" class="form-input" maxlength="200"
                               placeholder="e.g. Reach 50K views, increase brand awareness"
                               value="<?= $editCampagne?htmlspecialchars($editCampagne['objectif']??''):'' ?>">
                    </div>

                    <div class="form-grid-3">
                        <!-- START DATE -->
                        <div class="form-group">
                            <label>Start Date</label>
                            <input type="text" name="dateDebut" id="fDateDebut" class="form-input" placeholder="YYYY-MM-DD"
                                   value="<?= $editCampagne?htmlspecialchars($editCampagne['dateDebut']??''):'' ?>">
                            <div class="field-error" id="errDateDebut">Use YYYY-MM-DD format.</div>
                        </div>
                        <!-- END DATE -->
                        <div class="form-group">
                            <label>End Date</label>
                            <input type="text" name="dateFin" id="fDateFin" class="form-input" placeholder="YYYY-MM-DD"
                                   value="<?= $editCampagne?htmlspecialchars($editCampagne['dateFin']??''):'' ?>">
                            <div class="field-error" id="errDateFin">Use YYYY-MM-DD format.</div>
                            <div class="date-coherence" id="errDateCoherence">⚠ End date must be after start date.</div>
                        </div>
                        <!-- BUDGET -->
                        <div class="form-group">
                            <label>Budget (€) *</label>
                            <div class="input-with-prefix" id="budgetWrapper">
                                <span class="prefix">€</span>
                                <input type="text" name="budget" id="fBudget" class="form-input"
                                       placeholder="0.00"
                                       value="<?= $editCampagne?htmlspecialchars($editCampagne['budget']??''):'' ?>">
                            </div>
                            <div class="field-error" id="errBudget">Valid budget required (e.g. 1500.00).</div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-submit" id="btnSubmit"><?= $editCampagne?'💾 Save Changes':'🚀 Launch Campaign' ?></button>
                        <?php if ($editCampagne): ?><a href="index.php" class="btn-cancel">Cancel</a><?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div><!-- /page-wrapper -->

<!-- DRAWER -->
<div class="drawer-overlay" id="drawerOverlay" onclick="closeDrawerOutside(event)">
    <div class="drawer" id="drawerBox">
        <div class="drawer-header">
            <div>
                <div class="drawer-title" id="drawerTitle">Campaign Products</div>
                <div class="drawer-sub">Add or remove linked products.</div>
            </div>
            <button class="drawer-close" onclick="closeDrawer()">✕</button>
        </div>
        <div id="drawerContent"><div class="drawer-loader">⏳ Loading…</div></div>
    </div>
</div>

<!-- DELETE MODAL -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal-box">
        <div class="modal-title">🗑 Delete Campaign?</div>
        <div class="modal-text" id="deleteModalText"></div>
        <div class="modal-actions">
            <button class="btn-modal-cancel" onclick="closeDeleteModal()">Cancel</button>
            <a href="#" class="btn-modal-del" id="deleteModalLink">Yes, delete</a>
        </div>
    </div>
</div>

<script>
const BASE_URL='<?= $baseUrl ?>';

/* ─── TOAST ──────────────────────────────────────────────────────── */
function showToast(msg,type='success',dur=4000){
    const icons={success:'✅',error:'❌',delete:'🗑️',info:'ℹ️',warning:'⚠️'};
    const c=document.getElementById('toastContainer');
    const t=document.createElement('div');
    t.className=`toast toast-${type}`;
    t.innerHTML=`<span>${icons[type]||'ℹ️'}</span><span style="flex:1">${msg}</span><button class="toast-close" onclick="this.closest('.toast').remove()">✕</button>`;
    c.appendChild(t);
    setTimeout(()=>{t.classList.add('hide');setTimeout(()=>t.remove(),450);},dur);
}
<?php if ($message): ?>
showToast(<?= json_encode($message) ?>,'<?= $messageType ?>');
<?php endif; ?>
const flashEl=document.getElementById('flashMsg');
if(flashEl) setTimeout(()=>{flashEl.style.transition='opacity .4s';flashEl.style.opacity='0';setTimeout(()=>flashEl.remove(),400);},3500);

/* ─── COUNTDOWN TIMERS ───────────────────────────────────────────── */
document.querySelectorAll('.countdown-text[data-fin]').forEach(el=>{
    const update=()=>{
        const diff=new Date(el.dataset.fin)-new Date();
        if(diff<=0){el.textContent='Expired';return;}
        const d=Math.floor(diff/86400000);
        const h=Math.floor((diff%86400000)/3600000);
        const m=Math.floor((diff%3600000)/60000);
        el.textContent=d>0?`${d}d ${h}h left`:`${h}h ${m}m left`;
    };
    update();setInterval(update,60000);
});

/* ─── VALIDATION JS ──────────────────────────────────────────────── */
function showFE(id,msg){const e=document.getElementById(id);if(!e)return;if(msg){e.textContent='⚠ '+msg;e.classList.add('visible');}else e.classList.remove('visible');}
function setFC(el,ok){if(!el)return;el.classList.toggle('is-invalid',!ok);el.classList.toggle('is-valid',ok);}
function isValidDate(v){
    if(!v)return true;
    if(!/^\d{4}-\d{2}-\d{2}$/.test(v))return false;
    const[y,m,d]=v.split('-').map(Number);
    const dt=new Date(y,m-1,d);
    return dt.getFullYear()===y&&dt.getMonth()===m-1&&dt.getDate()===d;
}
function isValidBudget(v){
    if(!v.trim())return false;
    const n=parseFloat(v.replace(',','.'));
    if(isNaN(n)||n<0)return false;
    const p=v.replace(',','.').split('.');
    return p[0].length<=8&&(!p[1]||p[1].length<=2);
}
function updateCounter(fId,cId){
    const f=document.getElementById(fId),c=document.getElementById(cId);
    if(!f||!c)return;
    c.textContent=f.value.length+'/'+f.maxLength;
    c.className='char-counter-label'+(f.value.length>f.maxLength*.85?' warn':'');
}

const fTitre=document.getElementById('fTitre');
const fDesc=document.getElementById('fDesc');
const fObj=document.getElementById('fObj');
const fBudget=document.getElementById('fBudget');
const fDateDebut=document.getElementById('fDateDebut');
const fDateFin=document.getElementById('fDateFin');

if(fTitre){
    fTitre.addEventListener('input',()=>{
        updateCounter('fTitre','ctrTitre');
        const v=fTitre.value.trim();
        const ok=v.length>=2&&v.length<=100&&!/<[^>]+>/.test(v);
        setFC(fTitre,ok);
        if(!ok) showFE('errTitre',v.length<2?'Minimum 2 characters required.':v.length>100?'Maximum 100 characters.':'HTML tags not allowed.');
        else showFE('errTitre','');
    });
}
if(fDesc){
    fDesc.addEventListener('input',()=>{
        updateCounter('fDesc','ctrDesc');
        const ok=fDesc.value.trim().length>=5;
        setFC(fDesc,ok);
        showFE('errDesc',ok?'':'Description must be at least 5 characters.');
    });
}
if(fObj) fObj.addEventListener('input',()=>updateCounter('fObj','ctrObj'));

if(fBudget){
    fBudget.addEventListener('keydown',e=>{
        const sys=['Backspace','Delete','Tab','Escape','Enter','ArrowLeft','ArrowRight','Home','End'];
        if(sys.includes(e.key)||e.ctrlKey||e.metaKey)return;
        if((e.key==='.'||e.key===',')&&!(fBudget.value.includes('.')||fBudget.value.includes(',')))return;
        if(!/^\d$/.test(e.key))e.preventDefault();
    });
    fBudget.addEventListener('input',()=>{
        const ok=isValidBudget(fBudget.value);
        setFC(fBudget,ok);
        document.getElementById('budgetWrapper')?.classList.toggle('is-invalid',!ok);
        if(!ok){
            const v=fBudget.value;
            if(!v.trim()) showFE('errBudget','Budget is required.');
            else if(parseFloat(v.replace(',','.'))<0) showFE('errBudget','Budget must be ≥ 0.');
            else showFE('errBudget','Format: 1500.00 (max 2 decimals).');
        } else showFE('errBudget','');
    });
}

function onDateKD(e){
    const sys=['Backspace','Delete','Tab','Escape','Enter','ArrowLeft','ArrowRight','Home','End'];
    if(sys.includes(e.key)||e.ctrlKey||e.metaKey||e.key==='-')return;
    if(!/^\d$/.test(e.key))e.preventDefault();
}
function onDateInput(el,errId){
    let v=el.value.replace(/[^\d]/g,'');
    if(v.length>4)v=v.slice(0,4)+'-'+v.slice(4);
    if(v.length>7)v=v.slice(0,7)+'-'+v.slice(7);
    el.value=v.slice(0,10);
    const ok=isValidDate(el.value);
    setFC(el,ok||!el.value);
    showFE(errId,ok||!el.value?'':'Invalid date. Use YYYY-MM-DD (e.g. 2025-12-31).');
    checkDateCoherence();
}
function checkDateCoherence(){
    const d1=fDateDebut?.value?.trim(),d2=fDateFin?.value?.trim();
    const msg=document.getElementById('errDateCoherence');
    if(msg&&d1&&d2&&isValidDate(d1)&&isValidDate(d2)&&d2<d1){
        msg.classList.add('visible');setFC(fDateFin,false);
    } else msg?.classList.remove('visible');
}
if(fDateDebut){fDateDebut.addEventListener('keydown',onDateKD);fDateDebut.addEventListener('input',()=>onDateInput(fDateDebut,'errDateDebut'));}
if(fDateFin){fDateFin.addEventListener('keydown',onDateKD);fDateFin.addEventListener('input',()=>onDateInput(fDateFin,'errDateFin'));}

// Init counters
['fTitre','fDesc','fObj'].forEach(id=>{
    const el=document.getElementById(id);
    if(el){const map={fTitre:'ctrTitre',fDesc:'ctrDesc',fObj:'ctrObj'};updateCounter(id,map[id]);}
});

document.getElementById('campagneForm').addEventListener('submit',function(e){
    let ok=true;
    const titre=fTitre?.value?.trim()??'';
    const desc=fDesc?.value?.trim()??'';
    const budget=fBudget?.value??'';
    const d1=fDateDebut?.value?.trim();
    const d2=fDateFin?.value?.trim();

    if(titre.length<2||/<[^>]+>/.test(titre)){setFC(fTitre,false);showFE('errTitre','Title: min 2 chars, no HTML.');ok=false;}
    if(desc.length<5){setFC(fDesc,false);showFE('errDesc','Description required (min 5 chars).');ok=false;}
    if(!isValidBudget(budget)){setFC(fBudget,false);showFE('errBudget','Valid budget required.');document.getElementById('budgetWrapper')?.classList.add('is-invalid');ok=false;}
    if(d1&&!isValidDate(d1)){setFC(fDateDebut,false);showFE('errDateDebut','Invalid date format.');ok=false;}
    if(d2&&!isValidDate(d2)){setFC(fDateFin,false);showFE('errDateFin','Invalid date format.');ok=false;}
    if(d1&&d2&&d2<d1){document.getElementById('errDateCoherence')?.classList.add('visible');ok=false;}
    if(!ok){
        e.preventDefault();
        showToast('Please fix the form errors.','error');
        document.querySelector('.is-invalid')?.scrollIntoView({behavior:'smooth',block:'center'});
    }
});

/* ─── FILTER + SORT ──────────────────────────────────────────────── */
document.getElementById('searchInput').addEventListener('input',filterCampagnes);
function filterCampagnes(){
    const q=document.getElementById('searchInput').value.toLowerCase();
    const s=document.getElementById('filterStatut').value;
    const cards=document.querySelectorAll('#campGrid .camp-card');
    let v=0;
    cards.forEach(c=>{
        const mQ=!q||(c.dataset.titre||'').includes(q);
        const mS=!s||c.dataset.statut===s;
        c.style.display=mQ&&mS?'':'none';
        if(mQ&&mS)v++;
    });
    const el=document.getElementById('visibleCount');
    if(el)el.textContent=v;
}
function sortCampagnes(){
    const mode=document.getElementById('sortSelect').value;
    const grid=document.getElementById('campGrid');
    if(!grid||!mode)return;
    const cards=Array.from(grid.querySelectorAll('.camp-card'));
    cards.sort((a,b)=>{
        if(mode==='titre')return(a.dataset.titre||'').localeCompare(b.dataset.titre||'');
        if(mode==='budget_asc')return parseFloat(a.dataset.budget)-parseFloat(b.dataset.budget);
        if(mode==='budget_desc')return parseFloat(b.dataset.budget)-parseFloat(a.dataset.budget);
        if(mode==='date')return(a.dataset.date||'').localeCompare(b.dataset.date||'');
        return 0;
    });
    cards.forEach(c=>grid.appendChild(c));
}

/* ─── DUPLICATE CAMPAIGN ─────────────────────────────────────────── */
function duplicateCampagne(id){
    const card=document.querySelector(`.camp-card[data-titre]`);
    // Fill the add form with current campaign data (pre-fill)
    const titre=document.querySelector(`.camp-card:has(button[onclick*="${id}"]) .camp-card-title`)?.textContent;
    if(fTitre&&titre){fTitre.value='[Copy] '+titre;updateCounter('fTitre','ctrTitre');}
    document.getElementById('formAnchor').scrollIntoView({behavior:'smooth',block:'start'});
    showToast('Campaign data pre-filled. Edit and submit to create a copy.','info');
}

/* ─── DELETE MODAL ───────────────────────────────────────────────── */
function openDeleteModal(id,titre){
    document.getElementById('deleteModalText').textContent=`Delete "${titre}"? This cannot be undone.`;
    document.getElementById('deleteModalLink').href='index.php?supprimer='+id;
    document.getElementById('deleteModal').classList.add('open');
    document.body.style.overflow='hidden';
}
function closeDeleteModal(){document.getElementById('deleteModal').classList.remove('open');document.body.style.overflow='';}
document.getElementById('deleteModal').addEventListener('click',e=>{if(e.target.id==='deleteModal')closeDeleteModal();});

/* ─── PRODUCTS DRAWER ────────────────────────────────────────────── */
let currentCampId=null;
function openDrawer(id,titre){
    currentCampId=id;
    document.getElementById('drawerTitle').textContent='📦 Products — '+titre;
    document.getElementById('drawerContent').innerHTML='<div class="drawer-loader">⏳ Loading…</div>';
    document.getElementById('drawerOverlay').classList.add('open');
    document.body.style.overflow='hidden';
    loadDrawerProducts(id);
}
function closeDrawer(){document.getElementById('drawerOverlay').classList.remove('open');document.body.style.overflow='';}
function closeDrawerOutside(e){if(e.target.id==='drawerOverlay')closeDrawer();}
function loadDrawerProducts(id){
    fetch('index.php?ajax_produits='+id).then(r=>r.json()).then(d=>renderDrawer(id,d.lies,d.dispos));
}
function renderDrawer(id,lies,dispos){
    const content=document.getElementById('drawerContent');
    let html='';
    html+=`<div><div class="drawer-section-label">Linked products (${lies.length})</div><div class="drawer-prod-list">`;
    if(!lies.length) html+='<div class="drawer-empty">No products linked yet.</div>';
    else lies.forEach(p=>{
        const img=p.image?`<img src="${BASE_URL}/Vue/public/produits/${p.image}" alt="">`:'📦';
        html+=`<div class="drawer-prod-item"><div class="drawer-prod-thumb">${img}</div><div class="drawer-prod-info"><div class="drawer-prod-name">${escHtml(p.nomProduit)}</div><div class="drawer-prod-meta">${p.categorie?escHtml(p.categorie):''}</div></div><span class="drawer-prod-price">${parseFloat(p.prix).toFixed(2)} €</span><button class="btn-drawer-remove" onclick="retirerProduit(${id},${p.idProduit})">✕ Remove</button></div>`;
    });
    html+='</div></div>';
    html+=`<div><div class="drawer-section-label">Add a product</div>`;
    if(!dispos.length) html+='<div class="drawer-empty">All products already linked.</div>';
    else{
        html+='<div class="drawer-add-row"><select class="drawer-select" id="drawerSelectProduit"><option value="">— Select a product —</option>';
        dispos.forEach(p=>{html+=`<option value="${p.idProduit}">${escHtml(p.nomProduit)} — ${parseFloat(p.prix).toFixed(2)} €</option>`;});
        html+=`</select><button class="btn-drawer-add" onclick="ajouterProduit(${id})">+ Link</button></div>`;
    }
    html+='</div>';
    content.innerHTML=html;
}
function ajouterProduit(id){
    const sel=document.getElementById('drawerSelectProduit');
    if(!sel?.value)return;
    fetch('index.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`action=lier_produit&idCampagne=${id}&idProduit=${sel.value}`})
        .then(r=>r.json()).then(d=>{if(d.ok){loadDrawerProducts(id);showToast('Product linked!','success');}});
}
function retirerProduit(id,idP){
    if(!confirm('Remove this product?'))return;
    fetch('index.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`action=retirer_produit&idCampagne=${id}&idProduit=${idP}`})
        .then(r=>r.json()).then(d=>{if(d.ok){loadDrawerProducts(id);showToast('Product removed.','warning');}});
}
function escHtml(s){const d=document.createElement('div');d.textContent=s||'';return d.innerHTML;}

document.addEventListener('keydown',e=>{if(e.key==='Escape'){closeDeleteModal();closeDrawer();}});
<?php if ($editCampagne): ?>
document.addEventListener('DOMContentLoaded',()=>document.getElementById('formAnchor').scrollIntoView({behavior:'smooth',block:'start'}));
<?php endif; ?>
</script>
</body>
</html>