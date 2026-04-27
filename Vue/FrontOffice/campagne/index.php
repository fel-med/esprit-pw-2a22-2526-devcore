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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'archive') {
    $campagneC->toggleArchive(intval($_POST['id']));
    echo json_encode(['ok' => true]);
    exit;
}

// ── AJAX : lier produit ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'lier_produit') {
    $idC = intval($_POST['idCampagne']);
    $idP = intval($_POST['idProduit']);
    // Vérification ownership : la campagne appartient bien à la marque
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'retirer_produit') {
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
        echo json_encode(['lies' => [], 'dispos' => []]);
        exit;
    }
    $lies   = $produitC->getProduitsByCampagne($idC);
    $dispos = $produitC->getProduitsDisponiblesPourCampagne($idC, $id_marque);
    echo json_encode(['lies' => $lies, 'dispos' => $dispos]);
    exit;
}

// ── ADD ───────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter') {
    $campagne = new Campagne(
        null, trim($_POST['titre']), trim($_POST['description']),
        !empty($_POST['dateDebut']) ? $_POST['dateDebut'] : null,
        !empty($_POST['dateFin'])   ? $_POST['dateFin']   : null,
        floatval(str_replace(',', '.', $_POST['budget'])),
        $_POST['statut'] ?? 'brouillon', $id_marque,
        trim($_POST['objectif'] ?? ''), 0
    );
    $campagneC->ajouterCampagne($campagne);
    $message = "Campaign added successfully!";
    $messageType = "success";
}

// ── UPDATE ────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'modifier') {
    $campagne = new Campagne(
        null, trim($_POST['titre']), trim($_POST['description']),
        !empty($_POST['dateDebut']) ? $_POST['dateDebut'] : null,
        !empty($_POST['dateFin'])   ? $_POST['dateFin']   : null,
        floatval(str_replace(',', '.', $_POST['budget'])),
        $_POST['statut'] ?? 'brouillon', $id_marque,
        trim($_POST['objectif'] ?? ''), intval($_POST['estArchive'] ?? 0)
    );
    $campagneC->modifierCampagne($campagne, intval($_POST['id']));
    $message = "Campaign updated!";
    $messageType = "success";
}

// ── DELETE ─────────────────────────────────────────────────────────────────────
if (isset($_GET['supprimer'])) {
    $campagneC->supprimerCampagne(intval($_GET['supprimer']));
    $message = "Campaign deleted.";
    $messageType = "delete";
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

function statutLabel($s) {
    return match($s) { 'active'=>'✅ Active','terminee'=>'🏁 Ended','annulee'=>'❌ Cancelled',default=>'📝 Draft' };
}
function statutColor($s) {
    return match($s) { 'active'=>'#0ea370','terminee'=>'#3b82f6','annulee'=>'#f43f5e',default=>'#f59e0b' };
}
function statutBg($s) {
    return match($s) { 'active'=>'#edfaf5','terminee'=>'#eff6ff','annulee'=>'#fff1f3',default=>'#fffbeb' };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Campaigns — Cre8Connect</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:ital,wght@0,700;0,800;1,700&display=swap" rel="stylesheet">
    <style>
        :root{--primary:#5b4fff;--primary-hover:#4a3ee8;--primary-light:#eeecff;--primary-glow:rgba(91,79,255,0.18);--primary-border:rgba(91,79,255,0.2);--text-main:#0f0e1a;--text-sub:#6b6f80;--text-dim:#a0a4b2;--border:#ebebf2;--bg:#f6f6fc;--white:#ffffff;--danger:#f43f5e;--danger-light:#fff1f3;--danger-border:rgba(244,63,94,0.2);--success:#0ea370;--success-light:#edfaf5;--success-border:rgba(14,163,112,0.2);--warning:#f59e0b;--warning-light:#fffbeb;--warning-border:rgba(245,158,11,0.2);--info:#3b82f6;--info-light:#eff6ff;--card-shadow:0 1px 3px rgba(15,14,26,0.06),0 4px 16px rgba(91,79,255,0.06);--card-shadow-hover:0 8px 32px rgba(91,79,255,0.14);--radius:14px;--radius-sm:8px;--nav-h:66px;}
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text-main);min-height:100vh;}

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

        .page-wrapper{max-width:1160px;margin:0 auto;padding:40px 24px 80px;}
        .page-header{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:32px;gap:20px;flex-wrap:wrap;}
        .page-header-left h1{font-family:'Fraunces',serif;font-size:30px;font-weight:800;letter-spacing:-0.8px;line-height:1.1;}
        .page-header-left p{color:var(--text-sub);font-size:14px;margin-top:5px;}
        .page-header-actions{display:flex;gap:10px;align-items:center;flex-wrap:wrap;}
        .btn-add{display:inline-flex;align-items:center;gap:8px;background:var(--primary);color:#fff;border:none;border-radius:var(--radius-sm);padding:11px 20px;font-size:13.5px;font-weight:700;font-family:'DM Sans',sans-serif;cursor:pointer;text-decoration:none;transition:background .2s,transform .15s;box-shadow:0 3px 12px var(--primary-glow);}
        .btn-add:hover{background:var(--primary-hover);transform:translateY(-1px);}

        .flash{padding:13px 18px;border-radius:var(--radius-sm);font-size:14px;font-weight:600;margin-bottom:24px;display:flex;align-items:center;gap:10px;animation:slideDown .3s ease;}
        @keyframes slideDown{from{opacity:0;transform:translateY(-10px);}to{opacity:1;transform:translateY(0);}}
        .flash.success{background:var(--success-light);color:#065f46;border:1px solid var(--success-border);}
        .flash.delete{background:var(--danger-light);color:#9f1239;border:1px solid var(--danger-border);}

        .kpi-strip{display:grid;grid-template-columns:repeat(5,1fr);gap:14px;margin-bottom:32px;}
        .kpi-card{background:var(--white);border:1px solid var(--border);border-radius:var(--radius);padding:18px 20px;position:relative;overflow:hidden;transition:box-shadow .2s;}
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

        .section-head{display:flex;align-items:center;gap:10px;margin-bottom:14px;flex-wrap:wrap;}
        .section-head h2{font-family:'Fraunces',serif;font-size:17px;font-weight:800;}
        .count-pill{background:var(--primary-light);color:var(--primary);border-radius:20px;padding:3px 11px;font-size:12px;font-weight:700;}
        .tools-bar{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:18px;}
        .search-wrap{position:relative;}
        .search-wrap svg{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--text-dim);pointer-events:none;width:14px;height:14px;}
        .search-input{background:var(--white);border:1.5px solid var(--border);border-radius:var(--radius-sm);padding:8px 12px 8px 32px;font-size:13px;font-family:'DM Sans',sans-serif;color:var(--text-main);width:220px;outline:none;transition:border-color .2s,box-shadow .2s;}
        .search-input:focus{border-color:var(--primary);box-shadow:0 0 0 3px var(--primary-glow);}
        .filter-select{background:var(--white);border:1.5px solid var(--border);border-radius:var(--radius-sm);padding:8px 12px;font-size:13px;font-family:'DM Sans',sans-serif;outline:none;cursor:pointer;color:var(--text-main);}

        /* Campaign cards */
        .camp-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:18px;margin-bottom:32px;}
        .camp-card{background:var(--white);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;box-shadow:var(--card-shadow);display:flex;flex-direction:column;transition:transform .22s,box-shadow .22s,border-color .22s;}
        .camp-card:hover{transform:translateY(-3px);box-shadow:var(--card-shadow-hover);border-color:var(--primary-border);}
        .camp-card-header{padding:18px 18px 0;display:flex;align-items:flex-start;justify-content:space-between;gap:8px;}
        .camp-card-title{font-family:'Fraunces',serif;font-size:16px;font-weight:800;color:var(--text-main);line-height:1.25;flex:1;}
        .camp-badge{display:inline-flex;align-items:center;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;flex-shrink:0;}
        .camp-card-body{padding:12px 18px 16px;flex:1;display:flex;flex-direction:column;gap:8px;}
        .camp-desc{font-size:13px;color:var(--text-sub);line-height:1.65;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;}
        .camp-obj{font-size:12px;background:var(--primary-light);color:var(--primary);border-radius:7px;padding:6px 10px;font-weight:600;}
        .camp-meta{display:flex;flex-direction:column;gap:4px;font-size:12px;color:var(--text-sub);}
        .camp-budget{font-family:'Fraunces',serif;font-size:22px;font-weight:800;color:var(--primary);margin-top:4px;}

        /* ── JOINTURE : produits liés badge ── */
        .camp-products-badge{display:inline-flex;align-items:center;gap:5px;background:var(--success-light);color:var(--success);border:1px solid var(--success-border);border-radius:20px;padding:4px 12px;font-size:12px;font-weight:700;cursor:pointer;transition:background .18s;}
        .camp-products-badge:hover{background:#d1fae5;}
        .camp-products-badge.zero{background:var(--bg);color:var(--text-dim);border-color:var(--border);}
        .camp-products-badge.zero:hover{border-color:var(--primary);color:var(--primary);}

        .camp-actions{display:flex;gap:8px;padding:14px 18px;border-top:1px solid var(--border);flex-wrap:wrap;}
        .btn-camp-edit{flex:1;padding:8px;background:var(--primary-light);color:var(--primary);border:none;border-radius:7px;font-size:12px;font-weight:700;font-family:'DM Sans',sans-serif;cursor:pointer;text-align:center;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;gap:5px;transition:background .18s;}
        .btn-camp-edit:hover{background:#ddddf8;}
        .btn-camp-del{flex:0 0 auto;padding:8px 10px;background:var(--danger-light);color:var(--danger);border:none;border-radius:7px;font-size:12px;cursor:pointer;font-family:'DM Sans',sans-serif;display:inline-flex;align-items:center;transition:background .18s;}

        .empty-state{text-align:center;padding:60px 20px;background:var(--white);border:1.5px dashed var(--border);border-radius:var(--radius);}
        .empty-icon{font-size:48px;margin-bottom:14px;}
        .empty-state h3{font-family:'Fraunces',serif;font-size:18px;font-weight:800;margin-bottom:6px;}
        .empty-state p{font-size:13.5px;color:var(--text-sub);margin-bottom:20px;}

        /* Form */
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
        .form-group{display:flex;flex-direction:column;gap:6px;margin-bottom:14px;}
        label{font-size:12.5px;font-weight:700;color:var(--text-sub);}
        .form-input{width:100%;padding:10px 13px;border:1.5px solid var(--border);border-radius:var(--radius-sm);font-size:13.5px;color:var(--text-main);background:#fafafa;transition:border-color .2s,box-shadow .2s;outline:none;font-family:'DM Sans',sans-serif;}
        .form-input:focus{border-color:var(--primary);box-shadow:0 0 0 3px var(--primary-glow);background:var(--white);}
        textarea.form-input{resize:vertical;min-height:90px;}
        .input-with-prefix{display:flex;align-items:center;border:1.5px solid var(--border);border-radius:var(--radius-sm);background:#fafafa;overflow:hidden;transition:border-color .2s;}
        .input-with-prefix:focus-within{border-color:var(--primary);box-shadow:0 0 0 3px var(--primary-glow);}
        .prefix{padding:0 11px;font-size:13px;color:var(--text-dim);border-right:1.5px solid var(--border);background:var(--white);min-height:42px;display:flex;align-items:center;}
        .input-with-prefix .form-input{border:none;background:transparent;flex:1;}
        .form-actions{display:flex;gap:10px;align-items:center;padding-top:18px;border-top:1px solid var(--border);}
        .btn-submit{background:var(--primary);color:#fff;border:none;border-radius:var(--radius-sm);padding:11px 26px;font-size:14px;font-weight:700;cursor:pointer;font-family:'DM Sans',sans-serif;box-shadow:0 3px 10px var(--primary-glow);transition:background .2s,transform .15s;}
        .btn-submit:hover{background:var(--primary-hover);transform:translateY(-1px);}
        .btn-cancel{background:var(--bg);color:var(--text-sub);border:1.5px solid var(--border);border-radius:var(--radius-sm);padding:10px 18px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;font-family:'DM Sans',sans-serif;}

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

        /* ════════════════════════════════════════
           PRODUCTS DRAWER (Jointure FO Brand)
        ════════════════════════════════════════ */
        .drawer-overlay{position:fixed;inset:0;background:rgba(15,14,26,.45);z-index:200;display:none;align-items:flex-end;justify-content:center;}
        .drawer-overlay.open{display:flex;}
        .drawer{width:100%;max-width:680px;background:var(--white);border-radius:var(--radius) var(--radius) 0 0;padding:28px 32px 36px;box-shadow:0 -8px 40px rgba(15,14,26,.15);animation:slideUp .24s ease;max-height:85vh;overflow-y:auto;display:flex;flex-direction:column;gap:20px;}
        @keyframes slideUp{from{transform:translateY(40px);opacity:0;}to{transform:translateY(0);opacity:1;}}
        .drawer-header{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;}
        .drawer-title{font-family:'Fraunces',serif;font-size:20px;font-weight:800;color:var(--text-main);}
        .drawer-sub{font-size:13px;color:var(--text-sub);margin-top:3px;}
        .drawer-close{background:var(--bg);border:1.5px solid var(--border);border-radius:50%;width:34px;height:34px;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:16px;color:var(--text-sub);flex-shrink:0;transition:all .2s;}
        .drawer-close:hover{background:var(--danger-light);color:var(--danger);}
        .drawer-section-label{font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-dim);margin-bottom:10px;}

        /* Produits liés dans le drawer */
        .drawer-prod-list{display:flex;flex-direction:column;gap:8px;}
        .drawer-prod-item{display:flex;align-items:center;gap:12px;background:var(--bg);border:1px solid var(--border);border-radius:var(--radius-sm);padding:12px 14px;transition:border-color .15s;}
        .drawer-prod-item:hover{border-color:var(--primary-border);}
        .drawer-prod-thumb{width:42px;height:42px;border-radius:8px;object-fit:cover;background:var(--border);flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:18px;overflow:hidden;}
        .drawer-prod-thumb img{width:100%;height:100%;object-fit:cover;}
        .drawer-prod-info{flex:1;min-width:0;}
        .drawer-prod-name{font-size:13.5px;font-weight:700;color:var(--text-main);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
        .drawer-prod-meta{font-size:11px;color:var(--text-sub);margin-top:2px;}
        .drawer-prod-price{font-family:'Fraunces',serif;font-size:14px;font-weight:800;color:var(--primary);white-space:nowrap;}
        .btn-drawer-remove{background:var(--danger-light);color:var(--danger);border:1px solid var(--danger-border);border-radius:7px;padding:5px 12px;font-size:12px;font-weight:700;cursor:pointer;font-family:'DM Sans',sans-serif;transition:background .15s;flex-shrink:0;}
        .btn-drawer-remove:hover{background:#fecdd3;}

        /* Add product select */
        .drawer-add-row{display:flex;gap:10px;align-items:stretch;}
        .drawer-select{flex:1;background:#fafafa;border:1.5px solid var(--border);border-radius:var(--radius-sm);padding:10px 12px;font-size:13px;color:var(--text-main);font-family:'DM Sans',sans-serif;outline:none;transition:border-color .2s;}
        .drawer-select:focus{border-color:var(--primary);}
        .btn-drawer-add{background:var(--primary);color:#fff;border:none;border-radius:var(--radius-sm);padding:10px 18px;font-size:13px;font-weight:700;cursor:pointer;font-family:'DM Sans',sans-serif;white-space:nowrap;transition:background .18s;}
        .btn-drawer-add:hover{background:var(--primary-hover);}
        .drawer-empty{text-align:center;padding:20px;color:var(--text-dim);font-size:13px;}
        .drawer-loader{text-align:center;padding:18px;color:var(--text-sub);font-size:13px;}

        @media(max-width:700px){nav{padding:0 18px;}.page-wrapper{padding:24px 14px 60px;}.form-grid{grid-template-columns:1fr;}.kpi-strip{grid-template-columns:repeat(2,1fr);}}
    </style>
</head>
<body>

<nav>
    <a href="<?= $baseUrl ?>" class="nav-logo">
        <img src="<?= $baseUrl ?>/Vue/public/images/logo.png" alt="Cre8Connect">
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
            <p>Manage your campaigns and the products associated with each one.</p>
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
        <?= $messageType==='success' ? '✅' : '🗑' ?> <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <!-- KPI -->
    <div class="kpi-strip">
        <div class="kpi-card"><div class="kpi-icon">⚡</div><div class="kpi-value"><?= $totalActives ?></div><div class="kpi-label">Total Campaigns</div></div>
        <div class="kpi-card"><div class="kpi-icon">✅</div><div class="kpi-value"><?= $nbActives ?></div><div class="kpi-label">Active Now</div></div>
        <div class="kpi-card"><div class="kpi-icon">📝</div><div class="kpi-value"><?= $nbBrouillons ?></div><div class="kpi-label">Drafts</div></div>
        <div class="kpi-card"><div class="kpi-icon">🏁</div><div class="kpi-value"><?= $nbTerminees ?></div><div class="kpi-label">Ended</div></div>
        <div class="kpi-card"><div class="kpi-icon">💰</div><div class="kpi-value"><?= number_format($budgetTotal,0,',',' ') ?> €</div><div class="kpi-label">Total Budget</div></div>
    </div>

    <!-- FILTER BAR -->
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
            <?php foreach ($statuts as $s): ?>
            <option value="<?= $s ?>"><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
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
        ?>
        <div class="camp-card"
             data-titre="<?= strtolower(htmlspecialchars($c['titreCampagne'])) ?>"
             data-statut="<?= htmlspecialchars($c['statut']) ?>">
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
                    <div>📅 <?= $c['dateDebut']??'—' ?> → 🏁 <?= $c['dateFin']??'—' ?></div>
                </div>
                <div class="camp-budget"><?= number_format((float)$c['budget'],2,',',' ') ?> €</div>

                <!-- ── JOINTURE : badge produits ── -->
                <div>
                    <button class="camp-products-badge <?= $nbProd===0?'zero':'' ?>"
                            onclick="openDrawer(<?= $c['idCampagne'] ?>, '<?= htmlspecialchars(addslashes($c['titreCampagne'])) ?>')"
                            title="Manage products linked to this campaign">
                        📦 <?= $nbProd ?> product<?= $nbProd!==1?'s':'' ?>
                        <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    </button>
                </div>
            </div>
            <div class="camp-actions">
                <a href="?modifier=<?= $c['idCampagne'] ?>#formAnchor" class="btn-camp-edit">✏️ Edit</a>
                <button class="btn-camp-del" onclick="openDeleteModal(<?= $c['idCampagne'] ?>,'<?= htmlspecialchars(addslashes($c['titreCampagne'])) ?>')">🗑</button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- FORM -->
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
                        <div class="form-group">
                            <label>Campaign Title *</label>
                            <input type="text" name="titre" class="form-input" maxlength="100" value="<?= $editCampagne?htmlspecialchars($editCampagne['titreCampagne']):'' ?>">
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="statut" class="form-input">
                                <?php foreach ($statuts as $s): ?>
                                <option value="<?= $s ?>" <?= ($editCampagne&&$editCampagne['statut']===$s)?'selected':'' ?>><?= ucfirst($s) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description *</label>
                        <textarea name="description" class="form-input" maxlength="600"><?= $editCampagne?htmlspecialchars($editCampagne['description']):'' ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Objective / Goal</label>
                        <input type="text" name="objectif" class="form-input" maxlength="200" value="<?= $editCampagne?htmlspecialchars($editCampagne['objectif']??''):'' ?>">
                    </div>
                    <div class="form-grid-3">
                        <div class="form-group">
                            <label>Start Date *</label>
                            <input type="text" name="dateDebut" class="form-input" placeholder="YYYY-MM-DD" value="<?= $editCampagne?htmlspecialchars($editCampagne['dateDebut']??''):'' ?>">
                        </div>
                        <div class="form-group">
                            <label>End Date *</label>
                            <input type="text" name="dateFin" class="form-input" placeholder="YYYY-MM-DD" value="<?= $editCampagne?htmlspecialchars($editCampagne['dateFin']??''):'' ?>">
                        </div>
                        <div class="form-group">
                            <label>Budget (€) *</label>
                            <div class="input-with-prefix">
                                <span class="prefix">€</span>
                                <input type="text" name="budget" class="form-input" value="<?= $editCampagne?htmlspecialchars($editCampagne['budget']??''):'' ?>">
                            </div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn-submit"><?= $editCampagne?'💾 Save Changes':'🚀 Launch Campaign' ?></button>
                        <?php if ($editCampagne): ?><a href="index.php" class="btn-cancel">Cancel</a><?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<!-- ════════════════════════════════════════════════════
     PRODUCTS DRAWER (Jointure FO Brand)
════════════════════════════════════════════════════ -->
<div class="drawer-overlay" id="drawerOverlay" onclick="closeDrawerOutside(event)">
    <div class="drawer" id="drawerBox">
        <div class="drawer-header">
            <div>
                <div class="drawer-title" id="drawerTitle">Campaign Products</div>
                <div class="drawer-sub">Add or remove products linked to this campaign.</div>
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
        <div class="modal-text" id="deleteModalText">Are you sure?</div>
        <div class="modal-actions">
            <button class="btn-modal-cancel" onclick="closeDeleteModal()">Cancel</button>
            <a href="#" class="btn-modal-del" id="deleteModalLink">Yes, delete</a>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?= $baseUrl ?>';

// Flash
const flashEl = document.getElementById('flashMsg');
if (flashEl) setTimeout(() => { flashEl.style.transition='opacity .4s'; flashEl.style.opacity='0'; setTimeout(()=>flashEl.remove(),400); }, 3500);

// Filter
document.getElementById('searchInput').addEventListener('input', filterCampagnes);
function filterCampagnes() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    const s = document.getElementById('filterStatut').value;
    const cards = document.querySelectorAll('#campGrid .camp-card');
    let v = 0;
    cards.forEach(card => {
        const matchQ = !q || (card.dataset.titre||'').includes(q);
        const matchS = !s || card.dataset.statut === s;
        card.style.display = matchQ && matchS ? '' : 'none';
        if (matchQ && matchS) v++;
    });
    const el = document.getElementById('visibleCount');
    if (el) el.textContent = v;
}

// Delete modal
function openDeleteModal(id, titre) {
    document.getElementById('deleteModalText').textContent = 'Delete "' + titre + '"? This cannot be undone.';
    document.getElementById('deleteModalLink').href = 'index.php?supprimer=' + id;
    document.getElementById('deleteModal').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeDeleteModal() { document.getElementById('deleteModal').classList.remove('open'); document.body.style.overflow=''; }
document.getElementById('deleteModal').addEventListener('click', e => { if (e.target===document.getElementById('deleteModal')) closeDeleteModal(); });

// ════════════════════════════════════════════════════
// PRODUCTS DRAWER — Jointure FO Brand
// ════════════════════════════════════════════════════
let currentCampId = null;

function openDrawer(idCampagne, titre) {
    currentCampId = idCampagne;
    document.getElementById('drawerTitle').textContent = '📦 Products — ' + titre;
    document.getElementById('drawerContent').innerHTML = '<div class="drawer-loader">⏳ Loading products…</div>';
    document.getElementById('drawerOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';
    loadDrawerProducts(idCampagne);
}

function closeDrawer() {
    document.getElementById('drawerOverlay').classList.remove('open');
    document.body.style.overflow = '';
    currentCampId = null;
}
function closeDrawerOutside(e) {
    if (e.target === document.getElementById('drawerOverlay')) closeDrawer();
}

function loadDrawerProducts(idCampagne) {
    fetch('index.php?ajax_produits=' + idCampagne)
        .then(r => r.json())
        .then(data => renderDrawer(idCampagne, data.lies, data.dispos));
}

function renderDrawer(idCampagne, lies, dispos) {
    const content = document.getElementById('drawerContent');
    let html = '';

    // Produits liés
    html += '<div><div class="drawer-section-label">Linked products (' + lies.length + ')</div>';
    html += '<div class="drawer-prod-list">';
    if (!lies.length) {
        html += '<div class="drawer-empty">No products linked yet. Add one below.</div>';
    } else {
        lies.forEach(p => {
            const img = p.image
                ? `<img src="${BASE_URL}/Vue/public/produits/${p.image}" alt="">`
                : '📦';
            const cat = p.categorie ? ' · ' + escHtml(p.categorie) : '';
            html += `
            <div class="drawer-prod-item">
                <div class="drawer-prod-thumb">${img}</div>
                <div class="drawer-prod-info">
                    <div class="drawer-prod-name">${escHtml(p.nomProduit)}</div>
                    <div class="drawer-prod-meta">${escHtml(p.categorie||'')}${cat?'':''}</div>
                </div>
                <span class="drawer-prod-price">${parseFloat(p.prix).toFixed(2)} €</span>
                <button class="btn-drawer-remove" onclick="retirerProduit(${idCampagne},${p.idProduit})">✕ Remove</button>
            </div>`;
        });
    }
    html += '</div></div>';

    // Ajouter un produit
    html += '<div><div class="drawer-section-label">Add a product to this campaign</div>';
    if (!dispos.length) {
        html += '<div class="drawer-empty">All your active products are already linked to this campaign.</div>';
    } else {
        html += '<div class="drawer-add-row">';
        html += '<select class="drawer-select" id="drawerSelectProduit"><option value="">— Select a product —</option>';
        dispos.forEach(p => {
            html += `<option value="${p.idProduit}">${escHtml(p.nomProduit)} — ${parseFloat(p.prix).toFixed(2)} €`;
            if (p.categorie) html += ` (${escHtml(p.categorie)})`;
            html += '</option>';
        });
        html += '</select>';
        html += `<button class="btn-drawer-add" onclick="ajouterProduit(${idCampagne})">+ Link</button>`;
        html += '</div>';
    }
    html += '</div>';

    content.innerHTML = html;
}

function ajouterProduit(idCampagne) {
    const sel = document.getElementById('drawerSelectProduit');
    const idProduit = sel ? sel.value : '';
    if (!idProduit) return;
    fetch('index.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=lier_produit&idCampagne=' + idCampagne + '&idProduit=' + idProduit
    }).then(r => r.json()).then(data => {
        if (data.ok) loadDrawerProducts(idCampagne);
    });
}

function retirerProduit(idCampagne, idProduit) {
    if (!confirm('Remove this product from the campaign?')) return;
    fetch('index.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=retirer_produit&idCampagne=' + idCampagne + '&idProduit=' + idProduit
    }).then(r => r.json()).then(data => {
        if (data.ok) loadDrawerProducts(idCampagne);
    });
}

function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str || '';
    return d.innerHTML;
}

document.addEventListener('keydown', e => { if (e.key==='Escape') { closeDrawer(); closeDeleteModal(); } });
<?php if ($editCampagne): ?>
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('formAnchor').scrollIntoView({behavior:'smooth',block:'start'});
});
<?php endif; ?>
</script>
</body>
</html>