<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../Controleur/produitC.php';

$controller = new ProduitC();

$baseUrl = '/projet/Esprit-PW-2A22-2526-Devcore';
$id_marque = $_SESSION['user_id'] ?? 1;

$message = '';
$messageType = '';
$editProduit = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter') {
    $nomImage = $controller->gererUploadImage($_FILES['image'] ?? null);
    $produit = new Produit(null, htmlspecialchars($_POST['nom']), htmlspecialchars($_POST['description']), htmlspecialchars($_POST['caracteristiques']), floatval($_POST['prix']), $id_marque, $nomImage);
    $controller->ajouterProduit($produit);
    $message = "Produit ajouté avec succès !";
    $messageType = "success";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'modifier') {
    $ancienProduit = $controller->recupererProduit(intval($_POST['id']));
    $nomImage = $controller->gererUploadImage($_FILES['image'] ?? null, $ancienProduit['image'] ?? null);
    $produit = new Produit(null, htmlspecialchars($_POST['nom']), htmlspecialchars($_POST['description']), htmlspecialchars($_POST['caracteristiques']), floatval($_POST['prix']), $id_marque, $nomImage);
    $controller->modifierProduit($produit, intval($_POST['id']));
    $message = "Produit modifié avec succès !";
    $messageType = "success";
}

if (isset($_GET['supprimer'])) {
    $controller->supprimerProduit(intval($_GET['supprimer']));
    $message = "Produit supprimé.";
    $messageType = "delete";
}

if (isset($_GET['modifier'])) {
    $editProduit = $controller->recupererProduit(intval($_GET['modifier']));
}

$produits = $controller->afficherProduits();
$totalProduits = count($produits);
$allPrix = array_column($produits, 'prix');
$prixMoyen = $totalProduits > 0 ? array_sum($allPrix) / $totalProduits : 0;
$valeurCatalogue = array_sum($allPrix);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Produits – Cre8Connect</title>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,700;0,9..144,900;1,9..144,700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary:        #5b4fff;
            --primary-light:  #ece9ff;
            --primary-hover:  #4438e0;
            --primary-glow:   rgba(91,79,255,0.15);
            --primary-border: rgba(91,79,255,0.2);
            --text-main:      #0f0e1a;
            --text-sub:       #6b6f80;
            --text-dim:       #a0a4b2;
            --border:         #ebebf2;
            --bg:             #f6f6fc;
            --white:          #ffffff;
            --danger:         #f43f5e;
            --danger-light:   #fff1f3;
            --danger-border:  rgba(244,63,94,0.2);
            --success:        #0ea370;
            --success-light:  #edfaf5;
            --success-border: rgba(14,163,112,0.2);
            --warning:        #f59e0b;
            --warning-light:  #fffbeb;
            --card-shadow:    0 1px 3px rgba(15,14,26,0.06), 0 4px 16px rgba(91,79,255,0.06);
            --card-shadow-hover: 0 8px 32px rgba(91,79,255,0.14);
            --radius:         14px;
            --radius-sm:      8px;
            --nav-h:          66px;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--text-main);
            min-height: 100vh;
        }

        /* ── NAV ── */
        nav {
            background: var(--white);
            border-bottom: 1px solid var(--border);
            padding: 0 48px;
            height: var(--nav-h);
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 200;
            box-shadow: 0 1px 0 var(--border), 0 2px 12px rgba(15,14,26,0.04);
        }

        .nav-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .nav-logo img {
            width: 36px;
            height: 36px;
            object-fit: contain;
            border-radius: 9px;
        }

        .nav-logo-text {
            font-family: 'Fraunces', serif;
            font-size: 19px;
            font-weight: 800;
            color: var(--primary);
            letter-spacing: -0.5px;
        }

        .nav-links {
            display: flex;
            gap: 6px;
            list-style: none;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text-sub);
            font-size: 13.5px;
            font-weight: 600;
            padding: 6px 14px;
            border-radius: 8px;
            transition: background 0.18s, color 0.18s;
        }

        .nav-links a:hover { background: var(--bg); color: var(--text-main); }
        .nav-links a.active { background: var(--primary-light); color: var(--primary); }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .nav-badge {
            background: var(--primary);
            color: #fff;
            border-radius: 20px;
            padding: 5px 14px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .02em;
        }

        .nav-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--primary-light);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 14px;
            font-family: 'Fraunces', serif;
            cursor: pointer;
            border: 2px solid var(--primary-border);
            transition: border-color .2s;
        }

        .nav-avatar:hover { border-color: var(--primary); }

        /* ── PAGE WRAPPER ── */
        .page-wrapper {
            max-width: 1160px;
            margin: 0 auto;
            padding: 40px 24px 80px;
        }

        /* ── PAGE HEADER ── */
        .page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 32px;
            gap: 20px;
            flex-wrap: wrap;
        }

        .page-header-left h1 {
            font-family: 'Fraunces', serif;
            font-size: 30px;
            font-weight: 800;
            color: var(--text-main);
            letter-spacing: -0.8px;
            line-height: 1.1;
        }

        .page-header-left p {
            color: var(--text-sub);
            font-size: 14px;
            margin-top: 5px;
            font-weight: 400;
        }

        .btn-add-product {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: var(--radius-sm);
            padding: 11px 20px;
            font-size: 13.5px;
            font-weight: 700;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            text-decoration: none;
            transition: background .2s, transform .15s, box-shadow .2s;
            box-shadow: 0 3px 12px var(--primary-glow);
            flex-shrink: 0;
        }

        .btn-add-product:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px var(--primary-glow);
        }

        .btn-add-product svg { width: 16px; height: 16px; }

        /* ── FLASH ── */
        .flash {
            padding: 13px 18px;
            border-radius: var(--radius-sm);
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown .3s ease;
        }

        @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

        .flash.success { background: var(--success-light); color: #065f46; border: 1px solid var(--success-border); }
        .flash.delete  { background: var(--danger-light);  color: #9f1239; border: 1px solid var(--danger-border); }

        /* ── KPI STRIP ── */
        .kpi-strip {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            margin-bottom: 32px;
        }

        .kpi-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 20px 22px;
            position: relative;
            overflow: hidden;
            transition: box-shadow .2s;
        }

        .kpi-card:hover { box-shadow: var(--card-shadow-hover); }

        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), #a78bfa);
            border-radius: var(--radius) var(--radius) 0 0;
        }

        .kpi-card:nth-child(2)::before { background: linear-gradient(90deg, #0ea370, #34d399); }
        .kpi-card:nth-child(3)::before { background: linear-gradient(90deg, #f59e0b, #fbbf24); }

        .kpi-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
            font-size: 18px;
        }

        .kpi-card:nth-child(1) .kpi-icon { background: var(--primary-light); }
        .kpi-card:nth-child(2) .kpi-icon { background: var(--success-light); }
        .kpi-card:nth-child(3) .kpi-icon { background: var(--warning-light); }

        .kpi-value {
            font-family: 'Fraunces', serif;
            font-size: 26px;
            font-weight: 800;
            color: var(--text-main);
            letter-spacing: -0.5px;
            line-height: 1;
        }

        .kpi-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-sub);
            margin-top: 4px;
            text-transform: uppercase;
            letter-spacing: .05em;
        }

        /* ── LAYOUT FORM + SIDEBAR ── */
        .form-layout {
            display: grid;
            grid-template-columns: 1fr 270px;
            gap: 24px;
            margin-bottom: 48px;
            align-items: start;
        }

        @media (max-width: 820px) {
            .form-layout { grid-template-columns: 1fr; }
            .kpi-strip { grid-template-columns: 1fr 1fr; }
            nav { padding: 0 20px; }
            .nav-links { display: none; }
        }

        /* ── FORM CARD ── */
        .form-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 32px;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border);
            transition: border-color .2s;
        }

        .form-card.edit-mode {
            border: 2px solid var(--primary);
        }

        .form-card-header {
            margin-bottom: 24px;
        }

        .form-card-header h2 {
            font-family: 'Fraunces', serif;
            font-size: 20px;
            font-weight: 800;
            color: var(--text-main);
            letter-spacing: -0.4px;
        }

        .form-card-header p {
            font-size: 13px;
            color: var(--text-sub);
            margin-top: 4px;
        }

        .edit-banner {
            background: linear-gradient(135deg, var(--primary), #7c3aed);
            color: #fff;
            border-radius: 8px;
            padding: 10px 16px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .edit-banner a {
            color: rgba(255,255,255,0.75);
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            transition: color .2s;
        }

        .edit-banner a:hover { color: #fff; }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .form-group {
            margin-bottom: 16px;
            position: relative;
        }

        .form-group.full { grid-column: 1 / -1; }

        label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 12.5px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 7px;
            letter-spacing: .01em;
        }

        .char-counter {
            font-size: 11px;
            font-weight: 500;
            color: var(--text-dim);
        }

        .form-input {
            width: 100%;
            padding: 11px 14px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: 14px;
            font-family: 'DM Sans', sans-serif;
            color: var(--text-main);
            background: #fafafa;
            transition: border-color .2s, box-shadow .2s, background .2s;
            outline: none;
            -webkit-appearance: none;
        }

        .form-input::placeholder { color: var(--text-dim); }

        .form-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-glow);
            background: var(--white);
        }

        .form-input.error {
            border-color: var(--danger);
            box-shadow: 0 0 0 3px rgba(244,63,94,0.1);
        }

        textarea.form-input {
            resize: vertical;
            min-height: 100px;
        }

        .error-msg {
            font-size: 11.5px;
            color: var(--danger);
            font-weight: 600;
            margin-top: 4px;
            display: none;
        }

        .error-msg.visible { display: block; }

        .input-with-prefix {
            position: relative;
        }

        .input-with-prefix .prefix {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 14px;
            font-weight: 700;
            color: var(--text-sub);
            pointer-events: none;
        }

        .input-with-prefix .form-input { padding-left: 30px; }

        /* ── UPLOAD ZONE ── */
        .upload-zone {
            border: 2px dashed var(--border);
            border-radius: var(--radius-sm);
            padding: 22px 16px;
            text-align: center;
            background: #fafafa;
            cursor: pointer;
            transition: all .2s;
            position: relative;
            overflow: hidden;
        }

        .upload-zone:hover, .upload-zone.dragging {
            border-color: var(--primary);
            background: var(--primary-light);
        }

        .upload-zone input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }

        .upload-icon-wrap {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: var(--primary-light);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
        }

        .upload-icon-wrap svg { width: 22px; height: 22px; color: var(--primary); }

        .upload-zone p {
            font-size: 13px;
            color: var(--text-sub);
            margin: 0;
            font-weight: 500;
        }

        .upload-zone strong { color: var(--primary); font-weight: 700; }

        .upload-hint {
            font-size: 11px;
            color: var(--text-dim);
            margin-top: 5px;
            display: block;
        }

        /* Image preview realtime */
        .img-preview-realtime {
            display: none;
            margin-top: 12px;
            border-radius: var(--radius-sm);
            overflow: hidden;
            position: relative;
        }

        .img-preview-realtime.visible { display: block; }

        .img-preview-realtime img {
            width: 100%;
            height: 160px;
            object-fit: cover;
            display: block;
        }

        .img-preview-overlay {
            position: absolute;
            inset: 0;
            background: rgba(15,14,26,0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity .2s;
        }

        .img-preview-realtime:hover .img-preview-overlay { opacity: 1; }

        .img-preview-overlay span {
            color: #fff;
            font-size: 12px;
            font-weight: 600;
        }

        /* Current image */
        .current-img-block {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 10px 14px;
            background: var(--primary-light);
            border-radius: var(--radius-sm);
            border: 1px solid var(--primary-border);
            margin-bottom: 10px;
        }

        .current-img-block img {
            width: 56px;
            height: 56px;
            object-fit: cover;
            border-radius: var(--radius-sm);
            border: 2px solid var(--white);
            box-shadow: var(--card-shadow);
        }

        .current-img-block div { flex: 1; }

        .current-img-block strong {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 2px;
        }

        .current-img-block span {
            font-size: 12px;
            color: var(--text-sub);
        }

        /* ── FORM ACTIONS ── */
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
            flex-wrap: wrap;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: var(--radius-sm);
            padding: 11px 24px;
            font-size: 14px;
            font-weight: 700;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            transition: background .2s, transform .15s;
            box-shadow: 0 3px 10px var(--primary-glow);
        }

        .btn-primary:hover { background: var(--primary-hover); transform: translateY(-1px); }

        .btn-outline {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: transparent;
            color: var(--text-sub);
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 600;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            text-decoration: none;
            transition: all .2s;
        }

        .btn-outline:hover { border-color: var(--text-sub); color: var(--text-main); background: var(--bg); }

        /* ── TIPS CARD ── */
        .tips-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 22px;
            border: 1px solid var(--border);
            box-shadow: var(--card-shadow);
            position: sticky;
            top: calc(var(--nav-h) + 20px);
        }

        .tips-card-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 18px;
            padding-bottom: 14px;
            border-bottom: 1px solid var(--border);
        }

        .tips-card-header .icon {
            width: 32px;
            height: 32px;
            background: var(--warning-light);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }

        .tips-card-header h3 {
            font-family: 'Fraunces', serif;
            font-size: 14px;
            font-weight: 800;
            color: var(--text-main);
        }

        .tip-item {
            display: flex;
            gap: 10px;
            margin-bottom: 14px;
        }

        .tip-item:last-child { margin-bottom: 0; }

        .tip-dot {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--primary-light);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 800;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .tip-item strong {
            display: block;
            font-size: 12.5px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 2px;
        }

        .tip-item p {
            font-size: 12px;
            color: var(--text-sub);
            line-height: 1.5;
        }

        /* ── SECTION HEADER + TOOLS ── */
        .section-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
            gap: 12px;
            flex-wrap: wrap;
        }

        .section-head-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-head-left h2 {
            font-family: 'Fraunces', serif;
            font-size: 19px;
            font-weight: 800;
            color: var(--text-main);
            letter-spacing: -0.4px;
        }

        .count-pill {
            background: var(--primary-light);
            color: var(--primary);
            border-radius: 20px;
            padding: 3px 11px;
            font-size: 12px;
            font-weight: 700;
        }

        .tools-bar {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .search-wrap {
            position: relative;
        }

        .search-wrap svg {
            position: absolute;
            left: 11px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-dim);
            pointer-events: none;
            width: 14px;
            height: 14px;
        }

        .search-input {
            background: var(--white);
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 8px 12px 8px 32px;
            font-size: 13px;
            font-family: 'DM Sans', sans-serif;
            color: var(--text-main);
            width: 200px;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
        }

        .search-input::placeholder { color: var(--text-dim); }
        .search-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-glow); }

        .sort-select {
            background: var(--white);
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 8px 12px;
            font-size: 13px;
            font-family: 'DM Sans', sans-serif;
            color: var(--text-main);
            outline: none;
            cursor: pointer;
            transition: border-color .2s;
        }

        .sort-select:focus { border-color: var(--primary); }

        .view-toggle {
            display: flex;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            overflow: hidden;
            background: var(--white);
        }

        .view-btn {
            background: transparent;
            border: none;
            padding: 7px 10px;
            cursor: pointer;
            color: var(--text-dim);
            display: flex;
            align-items: center;
            transition: background .15s, color .15s;
        }

        .view-btn.active, .view-btn:hover { background: var(--primary-light); color: var(--primary); }
        .view-btn svg { width: 15px; height: 15px; }

        /* ── GRID ── */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 18px;
        }

        .products-grid.view-list {
            grid-template-columns: 1fr;
        }

        /* ── PRODUCT CARD ── */
        .product-card {
            background: var(--white);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border);
            transition: transform .22s, box-shadow .22s, border-color .22s;
            display: flex;
            flex-direction: column;
        }

        .product-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--card-shadow-hover);
            border-color: var(--primary-border);
        }

        .view-list .product-card {
            flex-direction: row;
        }

        /* Image */
        .pcard-img {
            position: relative;
            height: 190px;
            overflow: hidden;
            background: var(--bg);
            flex-shrink: 0;
        }

        .view-list .pcard-img {
            width: 190px;
            height: auto;
            min-height: 140px;
        }

        .pcard-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform .35s;
            display: block;
        }

        .product-card:hover .pcard-img img { transform: scale(1.05); }

        .pcard-img-empty {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            background: linear-gradient(135deg, #f0effb, var(--bg));
            color: #c0bde0;
        }

        .pcard-price-badge {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: var(--primary);
            color: #fff;
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 13px;
            font-weight: 800;
            font-family: 'Fraunces', serif;
            box-shadow: 0 2px 8px rgba(91,79,255,0.3);
        }

        /* Body */
        .pcard-body {
            padding: 18px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .view-list .pcard-body { padding: 18px 22px; }

        .pcard-name {
            font-family: 'Fraunces', serif;
            font-size: 15.5px;
            font-weight: 800;
            color: var(--text-main);
            line-height: 1.25;
            margin-bottom: 6px;
            letter-spacing: -0.2px;
        }

        .pcard-desc {
            font-size: 13px;
            color: var(--text-sub);
            line-height: 1.6;
            margin-bottom: 10px;
            flex: 1;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .pcard-carac {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11.5px;
            color: var(--primary);
            background: var(--primary-light);
            border-radius: 6px;
            padding: 5px 10px;
            font-weight: 600;
            margin-bottom: 14px;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* Actions */
        .pcard-actions {
            display: flex;
            gap: 8px;
            padding-top: 14px;
            border-top: 1px solid var(--border);
        }

        .btn-edit-card {
            flex: 1;
            padding: 8px;
            background: var(--primary-light);
            color: var(--primary);
            border: none;
            border-radius: 7px;
            font-size: 13px;
            font-weight: 700;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            transition: background .18s, transform .15s;
        }

        .btn-edit-card:hover { background: #ddddf8; transform: translateY(-1px); }

        .btn-delete-card {
            flex: 1;
            padding: 8px;
            background: var(--danger-light);
            color: var(--danger);
            border: none;
            border-radius: 7px;
            font-size: 13px;
            font-weight: 700;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            transition: background .18s;
        }

        .btn-delete-card:hover { background: #fecdd3; }

        /* ── EMPTY STATE ── */
        .empty-state {
            text-align: center;
            padding: 60px 24px;
            background: var(--white);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            box-shadow: var(--card-shadow);
        }

        .empty-state .empty-icon { font-size: 52px; margin-bottom: 16px; display: block; }

        .empty-state h3 {
            font-family: 'Fraunces', serif;
            font-size: 20px;
            font-weight: 800;
            color: var(--text-main);
            margin-bottom: 8px;
        }

        .empty-state p { font-size: 14px; color: var(--text-sub); }

        /* ── MODAL DELETE ── */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15,14,26,0.5);
            z-index: 300;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-overlay.open { display: flex; }

        .modal-box {
            background: var(--white);
            border-radius: var(--radius);
            padding: 28px;
            width: 400px;
            max-width: 100%;
            box-shadow: 0 20px 60px rgba(15,14,26,0.2);
            animation: mslide .22s ease;
        }

        @keyframes mslide { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }

        .modal-icon {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            background: var(--danger-light);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
        }

        .modal-icon svg { width: 22px; height: 22px; color: var(--danger); }

        .modal-box h3 {
            font-family: 'Fraunces', serif;
            font-size: 18px;
            font-weight: 800;
            color: var(--text-main);
            margin-bottom: 8px;
        }

        .modal-box p { font-size: 13.5px; color: var(--text-sub); line-height: 1.6; margin-bottom: 22px; }

        .modal-actions { display: flex; gap: 10px; justify-content: flex-end; }

        .btn-cancel {
            background: var(--bg);
            color: var(--text-sub);
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 9px 20px;
            font-size: 13.5px;
            font-weight: 700;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            transition: all .2s;
        }

        .btn-cancel:hover { background: var(--white); border-color: var(--text-sub); }

        .btn-confirm-delete {
            background: var(--danger);
            color: #fff;
            border: none;
            border-radius: var(--radius-sm);
            padding: 9px 20px;
            font-size: 13.5px;
            font-weight: 700;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            text-decoration: none;
            transition: background .2s;
            display: inline-block;
        }

        .btn-confirm-delete:hover { background: #e11d48; }

        /* ── NO RESULTS ── */
        .no-results {
            text-align: center;
            padding: 40px;
            color: var(--text-sub);
            font-size: 14px;
            background: var(--white);
            border-radius: var(--radius);
            border: 1px solid var(--border);
        }
    </style>
</head>
<body>

<!-- ════ NAV ════ -->
<nav>
    <a href="#" class="nav-logo">
        <img src="<?= $baseUrl ?>/Vue/public/images/logo.png" alt="Cre8Connect">
        <span class="nav-logo-text">Cre8Connect</span>
    </a>
    <ul class="nav-links">
        <li><a href="#">Tableau de bord</a></li>
        <li><a href="#">Mes Offres</a></li>
        <li><a href="#" class="active">Mes Produits</a></li>
        <li><a href="#">Campagnes</a></li>
        <li><a href="#">Mon Profil</a></li>
    </ul>
    <div class="nav-right">
        <span class="nav-badge">Marque</span>
        <div class="nav-avatar" title="Mon compte">M</div>
    </div>
</nav>

<div class="page-wrapper">

    <!-- ── PAGE HEADER ── -->
    <div class="page-header">
        <div class="page-header-left">
            <h1>Mes Produits</h1>
            <p>Gérez et promouvez vos produits auprès des créateurs de contenu.</p>
        </div>
        <a href="#formAnchor" class="btn-add-product" id="btnShowForm">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Ajouter un produit
        </a>
    </div>

    <!-- ── FLASH ── -->
    <?php if ($message): ?>
        <div class="flash <?= $messageType ?>" id="flashMsg">
            <?= $messageType === 'success'
                ? '<svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>'
                : '<svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>' ?>
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- ── KPI STRIP ── -->
    <div class="kpi-strip">
        <div class="kpi-card">
            <div class="kpi-icon">📦</div>
            <div class="kpi-value"><?= $totalProduits ?></div>
            <div class="kpi-label">Produits au catalogue</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon">💶</div>
            <div class="kpi-value"><?= number_format($prixMoyen, 0, ',', ' ') ?> €</div>
            <div class="kpi-label">Prix moyen</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon">📊</div>
            <div class="kpi-value"><?= number_format($valeurCatalogue, 0, ',', ' ') ?> €</div>
            <div class="kpi-label">Valeur totale</div>
        </div>
    </div>

    <!-- ── FORM SECTION ── -->
    <div class="form-layout" id="formAnchor">
        <div class="form-card <?= $editProduit ? 'edit-mode' : '' ?>" id="formCard">

            <div class="form-card-header">
                <h2><?= $editProduit ? '✏️ Modifier le produit' : '➕ Ajouter un produit' ?></h2>
                <p><?= $editProduit
                    ? 'Modifiez les champs ci-dessous et sauvegardez les changements.'
                    : 'Renseignez les informations de votre nouveau produit.' ?></p>
            </div>

            <?php if ($editProduit): ?>
                <div class="edit-banner">
                    <span>Mode édition : <?= htmlspecialchars($editProduit['nomProduit']) ?></span>
                    <a href="index.php">✕ Annuler</a>
                </div>
            <?php endif; ?>

            <form method="POST" action="index.php" enctype="multipart/form-data" id="produitForm" novalidate>
                <input type="hidden" name="action" value="<?= $editProduit ? 'modifier' : 'ajouter' ?>">
                <?php if ($editProduit): ?>
                    <input type="hidden" name="id" value="<?= $editProduit['idProduit'] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="nom">
                        Nom du produit
                        <span class="char-counter" id="nomCounter">0 / 80</span>
                    </label>
                    <input type="text" name="nom" id="nom" class="form-input"
                           placeholder="Ex : Crème hydratante premium"
                           maxlength="80"
                           value="<?= $editProduit ? htmlspecialchars($editProduit['nomProduit']) : '' ?>"
                           oninput="updateCounter('nom', 'nomCounter')"
                           required>
                    <div class="error-msg" id="nomError">Le nom est requis.</div>
                </div>

                <div class="form-group">
                    <label for="description">
                        Description détaillée
                        <span class="char-counter" id="descCounter">0 / 400</span>
                    </label>
                    <textarea name="description" id="description" class="form-input"
                              placeholder="Décrivez votre produit, ses atouts, son public cible…"
                              maxlength="400"
                              oninput="updateCounter('description', 'descCounter')"
                              required><?= $editProduit ? htmlspecialchars($editProduit['description']) : '' ?></textarea>
                    <div class="error-msg" id="descError">La description est requise.</div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="caracteristiques">
                            Caractéristiques
                            <span class="char-counter" id="caracCounter">0 / 120</span>
                        </label>
                        <input type="text" name="caracteristiques" id="caracteristiques" class="form-input"
                               placeholder="Ex : Sans parabène, vegan, 200ml"
                               maxlength="120"
                               value="<?= $editProduit ? htmlspecialchars($editProduit['caracteristiques']) : '' ?>"
                               oninput="updateCounter('caracteristiques', 'caracCounter')"
                               required>
                        <div class="error-msg" id="caracError">Les caractéristiques sont requises.</div>
                    </div>
                    <div class="form-group">
                        <label for="prix">Prix</label>
                        <div class="input-with-prefix">
                            <span class="prefix">€</span>
                            <input type="number" name="prix" id="prix" class="form-input"
                                   placeholder="0.00" step="0.01" min="0"
                                   value="<?= $editProduit ? htmlspecialchars($editProduit['prix']) : '' ?>"
                                   required>
                        </div>
                        <div class="error-msg" id="prixError">Un prix valide est requis.</div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Image du produit</label>

                    <?php if ($editProduit && !empty($editProduit['image'])): ?>
                        <div class="current-img-block">
                            <img src="<?= $baseUrl ?>/Vue/public/produits/<?= htmlspecialchars($editProduit['image']) ?>"
                                 alt="Image actuelle">
                            <div>
                                <strong>Image actuelle</strong>
                                <span>Importez une nouvelle image pour la remplacer, ou laissez vide pour conserver celle-ci.</span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="upload-zone" id="uploadZone">
                        <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp" id="fileInput">
                        <div class="upload-icon-wrap">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <p><strong>Cliquez pour importer</strong> ou glissez une image ici</p>
                        <span class="upload-hint">JPG, PNG, WEBP — 2 Mo maximum</span>
                    </div>

                    <!-- Aperçu temps réel -->
                    <div class="img-preview-realtime" id="imgPreviewWrap">
                        <img id="imgPreview" src="" alt="Aperçu">
                        <div class="img-preview-overlay">
                            <span>🖼️ Aperçu de l'image</span>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        <?= $editProduit ? 'Sauvegarder les modifications' : 'Ajouter le produit' ?>
                    </button>
                    <?php if ($editProduit): ?>
                        <a href="index.php" class="btn-outline">Annuler</a>
                    <?php else: ?>
                        <button type="reset" class="btn-outline" onclick="resetForm()">Réinitialiser</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- TIPS -->
        <div class="tips-card">
            <div class="tips-card-header">
                <div class="icon">💡</div>
                <h3>Conseils de publication</h3>
            </div>
            <div class="tip-item">
                <div class="tip-dot">1</div>
                <div>
                    <strong>Nom percutant</strong>
                    <p>Court, précis, mémorable. Évitez les noms trop génériques.</p>
                </div>
            </div>
            <div class="tip-item">
                <div class="tip-dot">2</div>
                <div>
                    <strong>Description convaincante</strong>
                    <p>Les créateurs veulent comprendre ce qu'ils vont promouvoir. Soyez clair sur les bénéfices.</p>
                </div>
            </div>
            <div class="tip-item">
                <div class="tip-dot">3</div>
                <div>
                    <strong>Caractéristiques précises</strong>
                    <p>Matériaux, formats, labels (bio, vegan, certifié…) rassurent et attirent.</p>
                </div>
            </div>
            <div class="tip-item">
                <div class="tip-dot">4</div>
                <div>
                    <strong>Prix cohérent</strong>
                    <p>Un prix bien positionné aide à estimer la valeur de la collaboration.</p>
                </div>
            </div>
            <div class="tip-item">
                <div class="tip-dot">5</div>
                <div>
                    <strong>Belle image</strong>
                    <p>Une photo nette et lumineuse augmente fortement l'attractivité du produit.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ── PRODUCTS LIST ── -->
    <div class="section-head">
        <div class="section-head-left">
            <h2>Mes produits</h2>
            <span class="count-pill"><?= $totalProduits ?> produit<?= $totalProduits > 1 ? 's' : '' ?></span>
        </div>
        <div class="tools-bar">
            <div class="search-wrap">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
                </svg>
                <input type="text" id="searchInput" class="search-input" placeholder="Rechercher…" oninput="filterProducts()">
            </div>
            <select id="sortSelect" class="sort-select" onchange="sortProducts()">
                <option value="">Trier par…</option>
                <option value="nom">Nom A→Z</option>
                <option value="prix_asc">Prix croissant</option>
                <option value="prix_desc">Prix décroissant</option>
            </select>
            <div class="view-toggle">
                <button class="view-btn active" id="vbtn-grid" onclick="setView('grid')" title="Grille">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                        <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
                    </svg>
                </button>
                <button class="view-btn" id="vbtn-list" onclick="setView('list')" title="Liste">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <?php if (empty($produits)): ?>
        <div class="empty-state">
            <span class="empty-icon">📦</span>
            <h3>Aucun produit pour l'instant</h3>
            <p>Ajoutez votre premier produit pour commencer à collaborer avec des créateurs.</p>
        </div>
    <?php else: ?>
        <div class="products-grid" id="productsGrid">
            <?php foreach ($produits as $p): ?>
                <div class="product-card"
                     data-name="<?= htmlspecialchars(strtolower($p['nomProduit'])) ?>"
                     data-desc="<?= htmlspecialchars(strtolower($p['description'])) ?>"
                     data-prix="<?= (float)$p['prix'] ?>">

                    <div class="pcard-img">
                        <?php if (!empty($p['image'])): ?>
                            <img src="<?= $baseUrl ?>/Vue/public/produits/<?= htmlspecialchars($p['image']) ?>"
                                 alt="<?= htmlspecialchars($p['nomProduit']) ?>" loading="lazy">
                        <?php else: ?>
                            <div class="pcard-img-empty">📦</div>
                        <?php endif; ?>
                        <div class="pcard-price-badge"><?= number_format((float)$p['prix'], 2, ',', ' ') ?> €</div>
                    </div>

                    <div class="pcard-body">
                        <div class="pcard-name"><?= htmlspecialchars($p['nomProduit']) ?></div>
                        <p class="pcard-desc"><?= htmlspecialchars($p['description']) ?></p>
                        <?php if (!empty($p['caracteristiques'])): ?>
                            <div class="pcard-carac">
                                🏷️ <?= htmlspecialchars($p['caracteristiques']) ?>
                            </div>
                        <?php endif; ?>
                        <div class="pcard-actions">
                            <a href="index.php?modifier=<?= $p['idProduit'] ?>" class="btn-edit-card">
                                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                Modifier
                            </a>
                            <button class="btn-delete-card"
                                    onclick="openDeleteModal(<?= $p['idProduit'] ?>, '<?= htmlspecialchars($p['nomProduit'], ENT_QUOTES) ?>')">
                                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                Supprimer
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div id="noResults" class="no-results" style="display:none;">
            🔍 Aucun produit ne correspond à votre recherche.
        </div>
    <?php endif; ?>

</div>

<!-- ── MODAL DELETE ── -->
<div class="modal-overlay" id="deleteModal" onclick="closeModalOutside(event)">
    <div class="modal-box">
        <div class="modal-icon">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
        </div>
        <h3>Supprimer le produit ?</h3>
        <p id="deleteModalText">Cette action est irréversible et supprimera définitivement ce produit.</p>
        <div class="modal-actions">
            <button class="btn-cancel" onclick="closeDeleteModal()">Annuler</button>
            <a href="#" id="deleteConfirmBtn" class="btn-confirm-delete">Supprimer</a>
        </div>
    </div>
</div>

<script>
/* ── Char counters ── */
function updateCounter(fieldId, counterId) {
    const field = document.getElementById(fieldId);
    const counter = document.getElementById(counterId);
    if (!field || !counter) return;
    counter.textContent = field.value.length + ' / ' + (field.maxLength || '');
    counter.style.color = field.value.length > field.maxLength * 0.85 ? 'var(--warning)' : '';
}

// Init counters on load
document.addEventListener('DOMContentLoaded', () => {
    ['nom', 'description', 'caracteristiques'].forEach(id => {
        const field = document.getElementById(id);
        const counter = document.getElementById(id + (id === 'caracteristiques' ? 'Counter' : 'Counter').replace('caracteristiques', 'carac'));
        if (field) {
            const counterId = id === 'caracteristiques' ? 'caracCounter' : id + 'Counter';
            updateCounter(id, counterId);
        }
    });
    updateCounter('nom', 'nomCounter');
    updateCounter('description', 'descCounter');
    updateCounter('caracteristiques', 'caracCounter');
});

/* ── Image preview ── */
document.getElementById('fileInput').addEventListener('change', function () {
    const wrap = document.getElementById('imgPreviewWrap');
    const preview = document.getElementById('imgPreview');
    if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            preview.src = e.target.result;
            wrap.classList.add('visible');
        };
        reader.readAsDataURL(this.files[0]);
        document.getElementById('uploadZone').style.borderColor = 'var(--primary)';
        document.getElementById('uploadZone').style.background = 'var(--primary-light)';
    }
});

/* Drag & drop visual feedback */
const uploadZone = document.getElementById('uploadZone');
uploadZone.addEventListener('dragover', e => { e.preventDefault(); uploadZone.classList.add('dragging'); });
uploadZone.addEventListener('dragleave', () => uploadZone.classList.remove('dragging'));
uploadZone.addEventListener('drop', e => { e.preventDefault(); uploadZone.classList.remove('dragging'); });

/* ── Reset form ── */
function resetForm() {
    document.getElementById('imgPreviewWrap').classList.remove('visible');
    document.getElementById('uploadZone').style.borderColor = '';
    document.getElementById('uploadZone').style.background = '';
    updateCounter('nom', 'nomCounter');
    updateCounter('description', 'descCounter');
    updateCounter('caracteristiques', 'caracCounter');
}

/* ── Client-side validation ── */
document.getElementById('produitForm').addEventListener('submit', function(e) {
    let valid = true;
    const fields = [
        { id: 'nom', errId: 'nomError', msg: 'Le nom est requis.' },
        { id: 'description', errId: 'descError', msg: 'La description est requise.' },
        { id: 'caracteristiques', errId: 'caracError', msg: 'Les caractéristiques sont requises.' },
        { id: 'prix', errId: 'prixError', msg: 'Un prix valide est requis.' }
    ];
    fields.forEach(f => {
        const el = document.getElementById(f.id);
        const err = document.getElementById(f.errId);
        if (!el.value.trim() || (f.id === 'prix' && parseFloat(el.value) < 0)) {
            el.classList.add('error');
            err.classList.add('visible');
            valid = false;
        } else {
            el.classList.remove('error');
            err.classList.remove('visible');
        }
    });
    if (!valid) e.preventDefault();
});

/* ── Filter products ── */
function filterProducts() {
    const q = document.getElementById('searchInput').value.toLowerCase().trim();
    const cards = document.querySelectorAll('#productsGrid .product-card');
    let visible = 0;
    cards.forEach(card => {
        const name = card.dataset.name || '';
        const desc = card.dataset.desc || '';
        const match = name.includes(q) || desc.includes(q);
        card.style.display = match ? '' : 'none';
        if (match) visible++;
    });
    const noRes = document.getElementById('noResults');
    if (noRes) noRes.style.display = visible === 0 ? '' : 'none';
}

/* ── Sort products ── */
function sortProducts() {
    const mode = document.getElementById('sortSelect').value;
    const grid = document.getElementById('productsGrid');
    if (!grid || !mode) return;
    const cards = Array.from(grid.querySelectorAll('.product-card'));
    cards.sort((a, b) => {
        if (mode === 'nom') return (a.dataset.name || '').localeCompare(b.dataset.name || '');
        if (mode === 'prix_asc')  return parseFloat(a.dataset.prix) - parseFloat(b.dataset.prix);
        if (mode === 'prix_desc') return parseFloat(b.dataset.prix) - parseFloat(a.dataset.prix);
        return 0;
    });
    cards.forEach(c => grid.appendChild(c));
}

/* ── View toggle ── */
function setView(mode) {
    const grid = document.getElementById('productsGrid');
    if (!grid) return;
    grid.className = 'products-grid' + (mode === 'list' ? ' view-list' : '');
    document.getElementById('vbtn-grid').classList.toggle('active', mode === 'grid');
    document.getElementById('vbtn-list').classList.toggle('active', mode === 'list');
}

/* ── Modal delete ── */
function openDeleteModal(id, name) {
    document.getElementById('deleteModalText').textContent =
        'Voulez-vous vraiment supprimer "' + name + '" ? Cette action est irréversible.';
    document.getElementById('deleteConfirmBtn').href = 'index.php?supprimer=' + id;
    document.getElementById('deleteModal').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('open');
    document.body.style.overflow = '';
}

function closeModalOutside(e) {
    if (e.target === document.getElementById('deleteModal')) closeDeleteModal();
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDeleteModal(); });

/* ── Auto-dismiss flash ── */
const flash = document.getElementById('flashMsg');
if (flash) {
    setTimeout(() => {
        flash.style.transition = 'opacity .4s';
        flash.style.opacity = '0';
        setTimeout(() => flash.remove(), 400);
    }, 4000);
}

/* ── Scroll to form if edit mode ── */
<?php if ($editProduit): ?>
    document.getElementById('formAnchor').scrollIntoView({ behavior: 'smooth', block: 'start' });
<?php endif; ?>
</script>
</body>
</html>