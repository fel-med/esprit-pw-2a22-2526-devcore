<?php
require_once __DIR__ . '/../../../Controleur/produitC.php';

$controller = new ProduitC();

$baseUrl = '/projet/Esprit-PW-2A22-2526-Devcore';
$id_marque = $_SESSION['user_id'] ?? 1;

$message = '';
$messageType = '';
$editProduit = null;

// ── AJAX : reorder ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reordonner') {
    $ordre = json_decode($_POST['ordre'] ?? '[]', true);
    if (is_array($ordre)) {
        $controller->reordonnerProduits($ordre);
    }
    echo json_encode(['ok' => true]);
    exit;
}

// ── AJAX : toggle pin ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'epingle') {
    $controller->toggleEpingle(intval($_POST['id']));
    echo json_encode(['ok' => true]);
    exit;
}

// ── AJAX : toggle archive ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'archive') {
    $controller->toggleArchive(intval($_POST['id']));
    echo json_encode(['ok' => true]);
    exit;
}

// ── ADD ───────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter') {
    $nomImage = $controller->gererUploadImage($_FILES['image'] ?? null);
    $produit = new Produit(
        null,
        htmlspecialchars($_POST['nom']),
        htmlspecialchars($_POST['description']),
        htmlspecialchars($_POST['caracteristiques']),
        floatval(str_replace(',', '.', $_POST['prix'])),
        $id_marque,
        $nomImage,
        htmlspecialchars($_POST['categorie'] ?? ''),
        0, 0, 0,
        !empty($_POST['dateDisponibilite']) ? $_POST['dateDisponibilite'] : null,
        htmlspecialchars($_POST['noteInterne'] ?? '')
    );
    $controller->ajouterProduit($produit);
    $message = "Product added successfully!";
    $messageType = "success";
}

// ── UPDATE ────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'modifier') {
    $ancienProduit = $controller->recupererProduit(intval($_POST['id']));
    $nomImage = $controller->gererUploadImage($_FILES['image'] ?? null, $ancienProduit['image'] ?? null);
    $produit = new Produit(
        null,
        htmlspecialchars($_POST['nom']),
        htmlspecialchars($_POST['description']),
        htmlspecialchars($_POST['caracteristiques']),
        floatval(str_replace(',', '.', $_POST['prix'])),
        $id_marque,
        $nomImage,
        htmlspecialchars($_POST['categorie'] ?? ''),
        intval($_POST['estArchive'] ?? 0),
        intval($_POST['estEpingle'] ?? 0),
        0,
        !empty($_POST['dateDisponibilite']) ? $_POST['dateDisponibilite'] : null,
        htmlspecialchars($_POST['noteInterne'] ?? '')
    );
    $controller->modifierProduit($produit, intval($_POST['id']));
    $message = "Product updated successfully!";
    $messageType = "success";
}

// ── DELETE ─────────────────────────────────────────────────────────────────────
if (isset($_GET['supprimer'])) {
    $controller->supprimerProduit(intval($_GET['supprimer']));
    $message = "Product deleted.";
    $messageType = "delete";
}

// ── EDIT ──────────────────────────────────────────────────────────────────────
if (isset($_GET['modifier'])) {
    $editProduit = $controller->recupererProduit(intval($_GET['modifier']));
}

// ── DATA ──────────────────────────────────────────────────────────────────────
$produits         = $controller->afficherProduits($id_marque);
$produitsArchives = $controller->afficherProduitsArchives($id_marque);
$categories       = $controller->getCategories($id_marque);
$totalProduits    = count($produits);
$totalArchives    = count($produitsArchives);
$allPrix          = array_column($produits, 'prix');
$prixMoyen        = $totalProduits > 0 ? array_sum($allPrix) / $totalProduits : 0;
$valeurCatalogue  = array_sum($allPrix);
$sanImage         = count(array_filter($produits, fn($p) => empty($p['image'])));
$epingles         = array_filter($produits, fn($p) => !empty($p['estEpingle']));
$produitsRecents  = array_slice($produits, 0, 3);

try {
    $topProduits = $controller->getTopProduits(5);
} catch (Exception $e) {
    $topProduits = array_slice($produits, 0, 5);
}

// Completion reminders
$rappels = [];
foreach ($produits as $p) {
    $pct = 0;
    if (!empty($p['nomProduit']))        $pct += 20;
    if (!empty($p['description']))       $pct += 20;
    if (!empty($p['caracteristiques'])) $pct += 20;
    if (!empty($p['prix']))              $pct += 20;
    if (!empty($p['image']))             $pct += 20;
    if ($pct < 100) {
        $manquants = [];
        if (empty($p['description']))       $manquants[] = 'description';
        if (empty($p['caracteristiques'])) $manquants[] = 'tags';
        if (empty($p['image']))             $manquants[] = 'image';
        if (empty($p['dateDisponibilite'])) $manquants[] = 'availability date';
        if (empty($p['noteInterne']))       $manquants[] = 'internal note';
        if (!empty($manquants)) {
            $rappels[] = ['nom' => $p['nomProduit'], 'id' => $p['idProduit'], 'pct' => $pct, 'manquants' => $manquants];
        }
    }
}
$rappels = array_slice($rappels, 0, 3);

$tagsDisponibles = ['Bio','Vegan','Gluten-free','Made in France','Premium','Natural','Certified','Recycled','Artisan','Sustainable','Paraben-free','Cruelty-free','Luxury','Sport','Tech'];

$categoriesDisponibles = ['Beauty & Care','Fashion & Accessories','Tech & Gadgets','Food & Nutrition','Sport & Fitness','Home & Decor','Travel','Wellness','Gaming','Kids'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Products – Cre8Connect</title>
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
            --warning-border: rgba(245,158,11,0.2);
            --archive:        #64748b;
            --archive-light:  #f1f5f9;
            --pin:            #f59e0b;
            --pin-light:      #fffbeb;
            --card-shadow:    0 1px 3px rgba(15,14,26,0.06), 0 4px 16px rgba(91,79,255,0.06);
            --card-shadow-hover: 0 8px 32px rgba(91,79,255,0.14);
            --radius:         14px;
            --radius-sm:      8px;
            --nav-h:          66px;
        }

        body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text-main); min-height: 100vh; }

        /* ── NAV ── */
        nav { background: var(--white); border-bottom: 1px solid var(--border); padding: 0 48px; height: var(--nav-h); display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 200; box-shadow: 0 1px 0 var(--border), 0 2px 12px rgba(15,14,26,0.04); }
        .nav-logo { display: flex; align-items: center; gap: 10px; text-decoration: none; }
        .nav-logo img { width: 36px; height: 36px; object-fit: contain; border-radius: 9px; }
        .nav-logo-text { font-family: 'Fraunces', serif; font-size: 19px; font-weight: 800; color: var(--primary); letter-spacing: -0.5px; }
        .nav-links { display: flex; gap: 6px; list-style: none; }
        .nav-links a { text-decoration: none; color: var(--text-sub); font-size: 13.5px; font-weight: 600; padding: 6px 14px; border-radius: 8px; transition: background .18s, color .18s; }
        .nav-links a:hover { background: var(--bg); color: var(--text-main); }
        .nav-links a.active { background: var(--primary-light); color: var(--primary); }
        .nav-right { display: flex; align-items: center; gap: 12px; }
        .nav-badge { background: var(--primary); color: #fff; border-radius: 20px; padding: 5px 14px; font-size: 12px; font-weight: 700; }
        .nav-avatar { width: 36px; height: 36px; border-radius: 50%; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 14px; cursor: pointer; border: 2px solid var(--primary-border); }

        /* ── PAGE WRAPPER ── */
        .page-wrapper { max-width: 1160px; margin: 0 auto; padding: 40px 24px 80px; }

        /* ── PAGE HEADER ── */
        .page-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 32px; gap: 20px; flex-wrap: wrap; }
        .page-header-left h1 { font-family: 'Fraunces', serif; font-size: 30px; font-weight: 800; color: var(--text-main); letter-spacing: -0.8px; line-height: 1.1; }
        .page-header-left p { color: var(--text-sub); font-size: 14px; margin-top: 5px; }
        .page-header-actions { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .btn-add-product { display: inline-flex; align-items: center; gap: 8px; background: var(--primary); color: #fff; border: none; border-radius: var(--radius-sm); padding: 11px 20px; font-size: 13.5px; font-weight: 700; font-family: 'DM Sans', sans-serif; cursor: pointer; text-decoration: none; transition: background .2s, transform .15s; box-shadow: 0 3px 12px var(--primary-glow); }
        .btn-add-product:hover { background: var(--primary-hover); transform: translateY(-1px); }
        .btn-export { display: inline-flex; align-items: center; gap: 8px; background: var(--white); color: var(--text-sub); border: 1.5px solid var(--border); border-radius: var(--radius-sm); padding: 10px 16px; font-size: 13px; font-weight: 600; font-family: 'DM Sans', sans-serif; cursor: pointer; transition: all .2s; text-decoration: none; }
        .btn-export:hover { border-color: var(--primary); color: var(--primary); background: var(--primary-light); }

        /* ── FLASH ── */
        .flash { padding: 13px 18px; border-radius: var(--radius-sm); font-size: 14px; font-weight: 600; margin-bottom: 24px; display: flex; align-items: center; gap: 10px; animation: slideDown .3s ease; }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .flash.success { background: var(--success-light); color: #065f46; border: 1px solid var(--success-border); }
        .flash.delete  { background: var(--danger-light);  color: #9f1239; border: 1px solid var(--danger-border); }

        /* ── KPI STRIP ── */
        .kpi-strip { display: grid; grid-template-columns: repeat(5, 1fr); gap: 14px; margin-bottom: 32px; }
        .kpi-card { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius); padding: 18px 20px; position: relative; overflow: hidden; transition: box-shadow .2s; }
        .kpi-card:hover { box-shadow: var(--card-shadow-hover); }
        .kpi-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; border-radius: var(--radius) var(--radius) 0 0; }
        .kpi-card:nth-child(1)::before { background: linear-gradient(90deg, var(--primary), #a78bfa); }
        .kpi-card:nth-child(2)::before { background: linear-gradient(90deg, #0ea370, #34d399); }
        .kpi-card:nth-child(3)::before { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
        .kpi-card:nth-child(4)::before { background: linear-gradient(90deg, #f43f5e, #fb7185); }
        .kpi-card:nth-child(5)::before { background: linear-gradient(90deg, #64748b, #94a3b8); }
        .kpi-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-bottom: 10px; font-size: 17px; }
        .kpi-card:nth-child(1) .kpi-icon { background: var(--primary-light); }
        .kpi-card:nth-child(2) .kpi-icon { background: var(--success-light); }
        .kpi-card:nth-child(3) .kpi-icon { background: var(--warning-light); }
        .kpi-card:nth-child(4) .kpi-icon { background: var(--danger-light); }
        .kpi-card:nth-child(5) .kpi-icon { background: var(--archive-light); }
        .kpi-value { font-family: 'Fraunces', serif; font-size: 24px; font-weight: 800; color: var(--text-main); letter-spacing: -0.5px; line-height: 1; }
        .kpi-label { font-size: 11px; font-weight: 600; color: var(--text-sub); margin-top: 4px; text-transform: uppercase; letter-spacing: .05em; }
        .kpi-alert { font-size: 11px; color: var(--danger); font-weight: 600; margin-top: 5px; }
        .kpi-link { font-size: 11px; color: var(--primary); font-weight: 600; margin-top: 5px; cursor: pointer; text-decoration: underline; }

        /* ── COMPLETION REMINDERS ── */
        .rappels-section { background: var(--warning-light); border: 1px solid var(--warning-border); border-radius: var(--radius); padding: 18px 22px; margin-bottom: 28px; }
        .rappels-header { display: flex; align-items: center; gap: 8px; margin-bottom: 12px; }
        .rappels-header h3 { font-family: 'Fraunces', serif; font-size: 14px; font-weight: 800; color: #92400e; }
        .rappels-header .icon { font-size: 16px; }
        .rappels-list { display: flex; flex-direction: column; gap: 8px; }
        .rappel-item { background: var(--white); border-radius: var(--radius-sm); padding: 10px 14px; display: flex; align-items: center; gap: 12px; border: 1px solid var(--warning-border); }
        .rappel-name { font-size: 13px; font-weight: 700; color: var(--text-main); flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .rappel-bar-wrap { flex: 0 0 100px; }
        .rappel-bar { height: 5px; background: #fde68a; border-radius: 10px; overflow: hidden; }
        .rappel-bar-fill { height: 100%; background: var(--warning); border-radius: 10px; }
        .rappel-pct { font-size: 11px; font-weight: 800; color: var(--warning); margin-bottom: 3px; }
        .rappel-missing { font-size: 11px; color: var(--text-sub); }
        .rappel-btn { background: var(--warning); color: #fff; border: none; border-radius: 6px; padding: 5px 12px; font-size: 12px; font-weight: 700; cursor: pointer; font-family: 'DM Sans', sans-serif; text-decoration: none; white-space: nowrap; transition: background .2s; }
        .rappel-btn:hover { background: #d97706; }

        /* ── TOP PRODUCTS ── */
        .top-section { margin-bottom: 32px; }
        .top-header { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; }
        .top-header h2 { font-family: 'Fraunces', serif; font-size: 16px; font-weight: 800; color: var(--text-main); }
        .top-badge { background: linear-gradient(135deg, #f59e0b, #ef4444); color: #fff; border-radius: 20px; padding: 2px 10px; font-size: 11px; font-weight: 800; }
        .top-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; }
        .top-card { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 12px; display: flex; align-items: center; gap: 10px; position: relative; transition: box-shadow .2s; }
        .top-card:hover { box-shadow: var(--card-shadow-hover); }
        .top-rank { position: absolute; top: 6px; left: 6px; width: 20px; height: 20px; border-radius: 50%; background: var(--primary); color: #fff; font-size: 10px; font-weight: 800; display: flex; align-items: center; justify-content: center; font-family: 'Fraunces', serif; }
        .top-rank.r1 { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .top-rank.r2 { background: linear-gradient(135deg, #94a3b8, #64748b); }
        .top-rank.r3 { background: linear-gradient(135deg, #cd7c2f, #a0522d); }
        .top-thumb { width: 40px; height: 40px; border-radius: 8px; object-fit: cover; background: var(--bg); flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 18px; overflow: hidden; }
        .top-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .top-info { flex: 1; min-width: 0; }
        .top-name { font-size: 12px; font-weight: 700; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .top-stat { font-size: 11px; color: var(--primary); font-weight: 700; }

        /* ── RECENT PRODUCTS ── */
        .recent-section { margin-bottom: 36px; }
        .recent-header { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; }
        .recent-header h2 { font-family: 'Fraunces', serif; font-size: 16px; font-weight: 800; color: var(--text-main); }
        .recent-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--success); display: inline-block; animation: pulse 2s infinite; }
        @keyframes pulse { 0%,100% { opacity: 1; } 50% { opacity: 0.4; } }
        .recent-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
        .recent-card { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 14px; display: flex; align-items: center; gap: 12px; transition: box-shadow .2s, border-color .2s; }
        .recent-card:hover { box-shadow: var(--card-shadow-hover); border-color: var(--primary-border); }
        .recent-thumb { width: 48px; height: 48px; border-radius: 8px; object-fit: cover; background: var(--bg); flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 20px; overflow: hidden; }
        .recent-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .recent-info { flex: 1; min-width: 0; }
        .recent-name { font-size: 13px; font-weight: 700; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .recent-price { font-size: 12px; color: var(--primary); font-weight: 700; margin-top: 2px; }
        .recent-actions { display: flex; gap: 6px; flex-shrink: 0; }
        .recent-btn { padding: 4px 10px; font-size: 11px; font-weight: 700; border-radius: 6px; border: none; cursor: pointer; font-family: 'DM Sans', sans-serif; text-decoration: none; display: inline-flex; align-items: center; transition: background .18s; }
        .recent-btn.edit { background: var(--primary-light); color: var(--primary); }
        .recent-btn.edit:hover { background: #ddddf8; }

        /* ── TABS ── */
        .tab-bar { display: flex; gap: 4px; margin-bottom: 20px; border-bottom: 1px solid var(--border); }
        .tab-btn { background: none; border: none; padding: 10px 18px; font-size: 13.5px; font-weight: 600; color: var(--text-sub); cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -1px; transition: color .18s, border-color .18s; font-family: 'DM Sans', sans-serif; display: flex; align-items: center; gap: 6px; }
        .tab-btn.active { color: var(--primary); border-bottom-color: var(--primary); }
        .tab-btn:hover { color: var(--text-main); }
        .tab-pill { background: var(--primary-light); color: var(--primary); border-radius: 20px; padding: 1px 8px; font-size: 11px; font-weight: 800; }
        .tab-pill.archive { background: var(--archive-light); color: var(--archive); }
        .tab-content { display: none; }
        .tab-content.active { display: block; }

        /* ── CATEGORY FILTER ── */
        .cat-filter-bar { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 16px; align-items: center; }
        .cat-filter-label { font-size: 12px; font-weight: 700; color: var(--text-sub); margin-right: 4px; }
        .cat-chip { display: inline-flex; align-items: center; gap: 4px; padding: 5px 13px; border-radius: 20px; border: 1.5px solid var(--border); background: var(--white); font-size: 12px; font-weight: 600; color: var(--text-sub); cursor: pointer; transition: all .15s; font-family: 'DM Sans', sans-serif; }
        .cat-chip.active, .cat-chip:hover { background: var(--primary-light); color: var(--primary); border-color: var(--primary-border); }
        .cat-chip.active { font-weight: 700; }

        /* ── PINNED SECTION ── */
        .pinned-section { margin-bottom: 24px; }
        .pinned-header { display: flex; align-items: center; gap: 8px; margin-bottom: 12px; }
        .pinned-header h3 { font-family: 'Fraunces', serif; font-size: 14px; font-weight: 800; color: #92400e; }
        .pin-icon { font-size: 15px; }
        .pinned-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; }
        .pinned-card { background: linear-gradient(135deg, #fffbeb, #fef3c7); border: 1.5px solid #fcd34d; border-radius: var(--radius-sm); padding: 12px; position: relative; transition: box-shadow .2s; }
        .pinned-card:hover { box-shadow: 0 4px 16px rgba(245,158,11,0.2); }
        .pin-badge { position: absolute; top: -6px; right: 10px; background: var(--pin); color: #fff; border-radius: 20px; padding: 2px 9px; font-size: 10px; font-weight: 800; }
        .pinned-name { font-size: 13px; font-weight: 700; color: var(--text-main); margin-top: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .pinned-price { font-size: 12px; color: var(--primary); font-weight: 700; margin-top: 3px; }
        .pinned-cat { font-size: 11px; color: var(--text-sub); margin-top: 2px; }
        .pinned-actions { display: flex; gap: 5px; margin-top: 8px; }
        .btn-unpin { background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; border-radius: 6px; padding: 3px 10px; font-size: 11px; font-weight: 700; cursor: pointer; font-family: 'DM Sans', sans-serif; transition: background .15s; }
        .btn-unpin:hover { background: #fde68a; }

        /* ── FORM LAYOUT ── */
        .form-layout { display: grid; grid-template-columns: 1fr 300px; gap: 24px; margin-bottom: 48px; align-items: start; }
        .form-card { background: var(--white); border-radius: var(--radius); padding: 32px; box-shadow: var(--card-shadow); border: 1px solid var(--border); transition: border-color .2s; }
        .form-card.edit-mode { border: 2px solid var(--primary); }
        .form-card-header { margin-bottom: 24px; }
        .form-card-header h2 { font-family: 'Fraunces', serif; font-size: 20px; font-weight: 800; color: var(--text-main); letter-spacing: -0.4px; }
        .form-card-header p { font-size: 13px; color: var(--text-sub); margin-top: 4px; }
        .edit-banner { background: linear-gradient(135deg, var(--primary), #7c3aed); color: #fff; border-radius: 8px; padding: 10px 16px; font-size: 13px; font-weight: 600; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; }
        .edit-banner a { color: rgba(255,255,255,0.75); text-decoration: none; font-size: 12px; }
        .edit-banner a:hover { color: #fff; }

        /* ── COMPLETION BAR ── */
        .completion-wrap { background: var(--bg); border-radius: var(--radius-sm); padding: 14px 16px; margin-bottom: 20px; border: 1px solid var(--border); }
        .completion-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
        .completion-label { font-size: 12px; font-weight: 700; color: var(--text-sub); }
        .completion-pct { font-size: 13px; font-weight: 800; color: var(--primary); font-family: 'Fraunces', serif; }
        .completion-bar { height: 6px; background: var(--border); border-radius: 10px; overflow: hidden; }
        .completion-fill { height: 100%; background: linear-gradient(90deg, var(--primary), #a78bfa); border-radius: 10px; transition: width .4s ease; }
        .completion-hint { font-size: 11px; color: var(--text-dim); margin-top: 6px; }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-group { margin-bottom: 16px; position: relative; }
        label { display: flex; align-items: center; justify-content: space-between; font-size: 12.5px; font-weight: 700; color: var(--text-main); margin-bottom: 7px; letter-spacing: .01em; }
        .char-counter { font-size: 11px; font-weight: 500; color: var(--text-dim); }
        .form-input { width: 100%; padding: 11px 14px; border: 1.5px solid var(--border); border-radius: var(--radius-sm); font-size: 14px; font-family: 'DM Sans', sans-serif; color: var(--text-main); background: #fafafa; transition: border-color .2s, box-shadow .2s, background .2s; outline: none; -webkit-appearance: none; }
        .form-input::placeholder { color: var(--text-dim); }
        .form-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-glow); background: var(--white); }
        .form-input.error { border-color: var(--danger); box-shadow: 0 0 0 3px rgba(244,63,94,0.1); }
        textarea.form-input { resize: vertical; min-height: 100px; }
        select.form-input { cursor: pointer; }

        /* ── ERROR MESSAGES ── */
        .error-msg {
            font-size: 11.5px;
            color: var(--danger);
            font-weight: 600;
            margin-top: 5px;
            display: none;
            align-items: center;
            gap: 5px;
            animation: slideDown .2s ease;
        }
        .error-msg.visible { display: flex; }
        .error-msg::before { content: '⚠'; font-size: 11px; }

        /* ── HINT MESSAGE (prix) ── */
        .field-hint {
            font-size: 11px;
            color: var(--text-dim);
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .input-with-prefix { position: relative; }
        .input-with-prefix .prefix { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); font-size: 14px; font-weight: 700; color: var(--text-sub); pointer-events: none; }
        .input-with-prefix .form-input { padding-left: 30px; }

        /* ── INTERNAL NOTE ── */
        .note-interne-wrap { border: 1.5px dashed #fcd34d; border-radius: var(--radius-sm); background: #fffbeb; padding: 12px; }
        .note-interne-header { display: flex; align-items: center; gap: 6px; margin-bottom: 8px; }
        .note-interne-header span { font-size: 12px; font-weight: 700; color: #92400e; }
        .note-interne-header .lock-icon { font-size: 13px; }
        textarea.note-interne { border: none; background: transparent; resize: vertical; min-height: 70px; width: 100%; font-size: 13px; font-family: 'DM Sans', sans-serif; color: #78350f; outline: none; }
        textarea.note-interne::placeholder { color: #d97706; opacity: 0.7; }

        /* ── DATE AVAILABILITY ── */
        .dispo-badge { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 700; border-radius: 20px; padding: 3px 10px; margin-top: 5px; }
        .dispo-badge.available   { background: var(--success-light); color: var(--success); }
        .dispo-badge.future      { background: var(--warning-light); color: #92400e; }
        .dispo-badge.nodate      { background: var(--bg); color: var(--text-sub); }

        /* ── TOGGLES ── */
        .toggle-row { display: flex; gap: 16px; margin-bottom: 16px; }
        .toggle-item { display: flex; align-items: center; gap: 10px; padding: 10px 14px; background: var(--bg); border-radius: var(--radius-sm); border: 1px solid var(--border); flex: 1; }
        .toggle-item label { font-size: 13px; font-weight: 600; color: var(--text-main); margin: 0; cursor: pointer; }
        .toggle-item .toggle-desc { font-size: 11px; color: var(--text-sub); margin-top: 1px; }
        .toggle-switch { position: relative; width: 38px; height: 22px; flex-shrink: 0; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .toggle-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background: var(--border); border-radius: 22px; transition: .2s; }
        .toggle-slider::before { content: ''; position: absolute; width: 16px; height: 16px; border-radius: 50%; background: white; left: 3px; bottom: 3px; transition: .2s; }
        .toggle-switch input:checked + .toggle-slider { background: var(--primary); }
        .toggle-switch input:checked + .toggle-slider::before { transform: translateX(16px); }
        .toggle-switch.pin input:checked + .toggle-slider { background: var(--pin); }
        .toggle-switch.archive input:checked + .toggle-slider { background: var(--archive); }

        /* ── TAGS ── */
        .tags-container { border: 1.5px solid var(--border); border-radius: var(--radius-sm); padding: 12px; background: #fafafa; transition: border-color .2s, box-shadow .2s; }
        .tags-container:focus-within { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-glow); background: var(--white); }
        .tags-container.error { border-color: var(--danger); box-shadow: 0 0 0 3px rgba(244,63,94,0.1); }
        .tags-selected { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 10px; min-height: 28px; }
        .tags-selected:empty { display: none; }
        .tag-chip { display: inline-flex; align-items: center; gap: 5px; background: var(--primary-light); color: var(--primary); border-radius: 20px; padding: 4px 10px; font-size: 12px; font-weight: 700; }
        .tag-chip button { background: none; border: none; color: var(--primary); cursor: pointer; font-size: 14px; line-height: 1; padding: 0; opacity: 0.7; transition: opacity .15s; }
        .tag-chip button:hover { opacity: 1; }
        .tags-grid { display: flex; flex-wrap: wrap; gap: 6px; }
        .tag-btn { background: var(--white); border: 1.5px solid var(--border); border-radius: 20px; padding: 4px 12px; font-size: 12px; font-weight: 600; color: var(--text-sub); cursor: pointer; font-family: 'DM Sans', sans-serif; transition: all .18s; }
        .tag-btn:hover { border-color: var(--primary); color: var(--primary); background: var(--primary-light); }
        .tag-btn.selected { background: var(--primary); border-color: var(--primary); color: #fff; }
        .tags-hint { font-size: 11px; color: var(--text-dim); margin-top: 8px; }
        .tags-custom-row { display: flex; gap: 8px; margin-top: 8px; }
        .tags-custom-input { flex: 1; padding: 7px 12px; border: 1.5px solid var(--border); border-radius: 20px; font-size: 12px; font-family: 'DM Sans', sans-serif; outline: none; transition: border-color .2s; }
        .tags-custom-input:focus { border-color: var(--primary); }
        .tags-custom-btn { background: var(--primary-light); color: var(--primary); border: none; border-radius: 20px; padding: 7px 14px; font-size: 12px; font-weight: 700; cursor: pointer; font-family: 'DM Sans', sans-serif; transition: background .18s; }
        .tags-custom-btn:hover { background: #ddddf8; }

        /* ── UPLOAD ── */
        .upload-zone { border: 2px dashed var(--border); border-radius: var(--radius-sm); padding: 22px 16px; text-align: center; background: #fafafa; cursor: pointer; transition: all .2s; position: relative; overflow: hidden; }
        .upload-zone:hover, .upload-zone.dragging { border-color: var(--primary); background: var(--primary-light); }
        .upload-zone.error { border-color: var(--danger); }
        .upload-zone input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }
        .upload-icon-wrap { width: 44px; height: 44px; border-radius: 12px; background: var(--primary-light); display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; }
        .upload-icon-wrap svg { width: 22px; height: 22px; color: var(--primary); }
        .upload-zone p { font-size: 13px; color: var(--text-sub); margin: 0; font-weight: 500; }
        .upload-zone strong { color: var(--primary); font-weight: 700; }
        .upload-hint { font-size: 11px; color: var(--text-dim); margin-top: 5px; display: block; }
        .img-preview-realtime { display: none; margin-top: 12px; border-radius: var(--radius-sm); overflow: hidden; }
        .img-preview-realtime.visible { display: block; }
        .img-preview-realtime img { width: 100%; height: 160px; object-fit: cover; display: block; }
        .current-img-block { display: flex; align-items: center; gap: 14px; padding: 10px 14px; background: var(--primary-light); border-radius: var(--radius-sm); border: 1px solid var(--primary-border); margin-bottom: 10px; }
        .current-img-block img { width: 56px; height: 56px; object-fit: cover; border-radius: var(--radius-sm); }
        .current-img-block div { flex: 1; }
        .current-img-block strong { display: block; font-size: 13px; font-weight: 700; }
        .current-img-block span { font-size: 12px; color: var(--text-sub); }

        /* ── LIVE PREVIEW ── */
        .live-preview-wrap { margin-top: 20px; border-top: 1px solid var(--border); padding-top: 18px; }
        .live-preview-label { font-size: 11px; font-weight: 700; color: var(--text-sub); text-transform: uppercase; letter-spacing: .05em; margin-bottom: 12px; display: flex; align-items: center; gap: 6px; }
        .live-preview-label::before { content: ''; width: 6px; height: 6px; background: var(--success); border-radius: 50%; display: inline-block; animation: pulse 2s infinite; }
        .live-preview-card { border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; }
        .lp-img { height: 120px; background: linear-gradient(135deg, #f0effb, var(--bg)); display: flex; align-items: center; justify-content: center; font-size: 36px; color: #c0bde0; position: relative; overflow: hidden; }
        .lp-img img { width: 100%; height: 100%; object-fit: cover; display: none; }
        .lp-price-badge { position: absolute; bottom: 8px; right: 8px; background: var(--primary); color: #fff; border-radius: 20px; padding: 3px 10px; font-size: 12px; font-weight: 800; font-family: 'Fraunces', serif; }
        .lp-body { padding: 12px 14px; }
        .lp-name { font-family: 'Fraunces', serif; font-size: 14px; font-weight: 800; color: var(--text-main); margin-bottom: 4px; }
        .lp-desc { font-size: 12px; color: var(--text-sub); line-height: 1.5; margin-bottom: 8px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .lp-meta { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 6px; }
        .lp-cat { font-size: 10px; font-weight: 700; color: var(--text-sub); background: var(--bg); border-radius: 6px; padding: 2px 8px; }
        .lp-tags { display: flex; flex-wrap: wrap; gap: 4px; }
        .lp-tag { font-size: 10px; font-weight: 600; color: var(--primary); background: var(--primary-light); border-radius: 6px; padding: 3px 8px; }

        /* ── FORM ACTIONS ── */
        .form-actions { display: flex; gap: 10px; margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border); flex-wrap: wrap; }
        .btn-primary { display: inline-flex; align-items: center; gap: 7px; background: var(--primary); color: #fff; border: none; border-radius: var(--radius-sm); padding: 11px 24px; font-size: 14px; font-weight: 700; font-family: 'DM Sans', sans-serif; cursor: pointer; transition: background .2s, transform .15s; box-shadow: 0 3px 10px var(--primary-glow); }
        .btn-primary:hover { background: var(--primary-hover); transform: translateY(-1px); }
        .btn-outline { display: inline-flex; align-items: center; gap: 7px; background: transparent; color: var(--text-sub); border: 1.5px solid var(--border); border-radius: var(--radius-sm); padding: 10px 20px; font-size: 14px; font-weight: 600; font-family: 'DM Sans', sans-serif; cursor: pointer; text-decoration: none; transition: all .2s; }
        .btn-outline:hover { border-color: var(--text-sub); color: var(--text-main); background: var(--bg); }

        /* ── TIPS CARD ── */
        .tips-card { background: var(--white); border-radius: var(--radius); padding: 22px; border: 1px solid var(--border); box-shadow: var(--card-shadow); position: sticky; top: calc(var(--nav-h) + 20px); }
        .tips-card-header { display: flex; align-items: center; gap: 10px; margin-bottom: 18px; padding-bottom: 14px; border-bottom: 1px solid var(--border); }
        .tips-card-header .icon { width: 32px; height: 32px; background: var(--warning-light); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 16px; }
        .tips-card-header h3 { font-family: 'Fraunces', serif; font-size: 14px; font-weight: 800; }
        .tip-item { display: flex; gap: 10px; margin-bottom: 14px; }
        .tip-item:last-child { margin-bottom: 0; }
        .tip-dot { width: 20px; height: 20px; border-radius: 50%; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 800; flex-shrink: 0; margin-top: 1px; }
        .tip-item strong { display: block; font-size: 12.5px; font-weight: 700; margin-bottom: 2px; }
        .tip-item p { font-size: 12px; color: var(--text-sub); line-height: 1.5; }

        /* ── SECTION HEADER ── */
        .section-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; gap: 12px; flex-wrap: wrap; }
        .section-head-left { display: flex; align-items: center; gap: 10px; }
        .section-head-left h2 { font-family: 'Fraunces', serif; font-size: 19px; font-weight: 800; letter-spacing: -0.4px; }
        .count-pill { background: var(--primary-light); color: var(--primary); border-radius: 20px; padding: 3px 11px; font-size: 12px; font-weight: 700; }
        .tools-bar { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .search-wrap { position: relative; }
        .search-wrap svg { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: var(--text-dim); pointer-events: none; width: 14px; height: 14px; }
        .search-input { background: var(--white); border: 1.5px solid var(--border); border-radius: var(--radius-sm); padding: 8px 12px 8px 32px; font-size: 13px; font-family: 'DM Sans', sans-serif; color: var(--text-main); width: 200px; outline: none; transition: border-color .2s, box-shadow .2s; }
        .search-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-glow); }
        .price-filter { display: flex; align-items: center; gap: 8px; }
        .price-filter-label { font-size: 12px; font-weight: 600; color: var(--text-sub); white-space: nowrap; }
        .price-range-wrap { display: flex; align-items: center; gap: 6px; }
        .price-input { width: 70px; padding: 7px 10px; border: 1.5px solid var(--border); border-radius: var(--radius-sm); font-size: 12px; font-family: 'DM Sans', sans-serif; outline: none; transition: border-color .2s; }
        .price-input:focus { border-color: var(--primary); }
        .price-sep { font-size: 12px; color: var(--text-dim); }
        .sort-select { background: var(--white); border: 1.5px solid var(--border); border-radius: var(--radius-sm); padding: 8px 12px; font-size: 13px; font-family: 'DM Sans', sans-serif; outline: none; cursor: pointer; }
        .view-toggle { display: flex; border: 1.5px solid var(--border); border-radius: var(--radius-sm); overflow: hidden; background: var(--white); }
        .view-btn { background: transparent; border: none; padding: 7px 10px; cursor: pointer; color: var(--text-dim); display: flex; align-items: center; transition: background .15s, color .15s; }
        .view-btn.active, .view-btn:hover { background: var(--primary-light); color: var(--primary); }
        .view-btn svg { width: 15px; height: 15px; }

        /* ── DRAG HINT ── */
        .drag-hint { display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--text-sub); font-weight: 600; background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 6px 12px; }
        .drag-hint svg { width: 13px; height: 13px; color: var(--text-dim); }

        /* ── PRODUCT GRID ── */
        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 18px; }
        .products-grid.view-list { grid-template-columns: 1fr; }
        .products-grid.drag-mode .product-card { cursor: grab; }
        .product-card.drag-over { border: 2px dashed var(--primary); background: var(--primary-light); }
        .product-card.dragging { opacity: 0.4; }

        /* ── PRODUCT CARD ── */
        .product-card { background: var(--white); border-radius: var(--radius); overflow: hidden; box-shadow: var(--card-shadow); border: 1px solid var(--border); transition: transform .22s, box-shadow .22s, border-color .22s; display: flex; flex-direction: column; position: relative; }
        .product-card:hover { transform: translateY(-3px); box-shadow: var(--card-shadow-hover); border-color: var(--primary-border); }
        .view-list .product-card { flex-direction: row; }
        .product-card.is-pinned { border: 1.5px solid #fcd34d; }

        .badge-epingle { position: absolute; top: 10px; right: 10px; z-index: 10; background: var(--pin); color: #fff; border-radius: 20px; padding: 4px 10px; font-size: 11px; font-weight: 700; }
        .badge-dispo { position: absolute; bottom: 38px; left: 10px; z-index: 10; background: rgba(14,163,112,0.9); color: #fff; border-radius: 20px; padding: 3px 9px; font-size: 10px; font-weight: 700; }
        .badge-dispo.future { background: rgba(245,158,11,0.9); }

        .pcard-img { position: relative; height: 190px; overflow: hidden; background: var(--bg); flex-shrink: 0; }
        .view-list .pcard-img { width: 190px; height: auto; min-height: 140px; }
        .pcard-img img { width: 100%; height: 100%; object-fit: cover; transition: transform .35s; display: block; }
        .product-card:hover .pcard-img img { transform: scale(1.05); }
        .pcard-img-empty { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 40px; background: linear-gradient(135deg, #f0effb, var(--bg)); color: #c0bde0; }
        .pcard-price-badge { position: absolute; bottom: 10px; right: 10px; background: var(--primary); color: #fff; border-radius: 20px; padding: 4px 12px; font-size: 13px; font-weight: 800; font-family: 'Fraunces', serif; box-shadow: 0 2px 8px rgba(91,79,255,0.3); }
        .pcard-quickview { position: absolute; inset: 0; background: rgba(15,14,26,0.7); display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity .2s; }
        .product-card:hover .pcard-quickview { opacity: 1; }
        .btn-quickview { background: var(--white); color: var(--text-main); border: none; border-radius: var(--radius-sm); padding: 8px 16px; font-size: 12px; font-weight: 700; cursor: pointer; font-family: 'DM Sans', sans-serif; display: flex; align-items: center; gap: 6px; transition: background .18s; }
        .btn-quickview:hover { background: var(--primary-light); color: var(--primary); }
        .btn-quickview svg { width: 14px; height: 14px; }

        .pcard-body { padding: 18px; display: flex; flex-direction: column; flex: 1; }
        .view-list .pcard-body { padding: 18px 22px; }

        .pcard-cat { font-size: 11px; font-weight: 700; color: var(--text-sub); text-transform: uppercase; letter-spacing: .05em; margin-bottom: 5px; display: flex; align-items: center; gap: 5px; }
        .pcard-cat .cat-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--primary); opacity: 0.5; }

        .pcard-name { font-family: 'Fraunces', serif; font-size: 15.5px; font-weight: 800; color: var(--text-main); line-height: 1.25; margin-bottom: 6px; letter-spacing: -0.2px; }
        .pcard-desc { font-size: 13px; color: var(--text-sub); line-height: 1.6; margin-bottom: 10px; flex: 1; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .pcard-tags { display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 10px; }
        .pcard-tag { display: inline-flex; align-items: center; gap: 4px; font-size: 11px; color: var(--primary); background: var(--primary-light); border-radius: 6px; padding: 4px 9px; font-weight: 600; }
        .pcard-dispo-row { font-size: 11px; color: var(--text-sub); margin-bottom: 6px; display: flex; align-items: center; gap: 5px; }
        .pcard-dispo-row .dot-avail  { width: 6px; height: 6px; border-radius: 50%; background: var(--success); flex-shrink: 0; }
        .pcard-dispo-row .dot-future { width: 6px; height: 6px; border-radius: 50%; background: var(--warning); flex-shrink: 0; }
        .pcard-note-row { font-size: 11px; color: #92400e; background: #fffbeb; border: 1px solid #fcd34d; border-radius: 6px; padding: 5px 8px; margin-bottom: 8px; display: flex; align-items: flex-start; gap: 5px; }
        .pcard-note-row .note-label { font-weight: 700; flex-shrink: 0; }
        .pcard-note-text { overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }

        .pcard-actions { display: flex; gap: 8px; padding-top: 14px; border-top: 1px solid var(--border); flex-wrap: wrap; }
        .btn-edit-card { flex: 1; min-width: 80px; padding: 8px; background: var(--primary-light); color: var(--primary); border: none; border-radius: 7px; font-size: 12px; font-weight: 700; font-family: 'DM Sans', sans-serif; cursor: pointer; text-align: center; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 5px; transition: background .18s; }
        .btn-edit-card:hover { background: #ddddf8; }
        .btn-delete-card { flex: 0 0 auto; padding: 8px 12px; background: var(--danger-light); color: var(--danger); border: none; border-radius: 7px; font-size: 12px; font-weight: 700; cursor: pointer; font-family: 'DM Sans', sans-serif; display: inline-flex; align-items: center; justify-content: center; transition: background .18s; }
        .btn-delete-card:hover { background: #fecdd3; }
        .btn-pin-card { flex: 0 0 auto; padding: 8px 12px; background: var(--pin-light); color: var(--pin); border: 1px solid var(--warning-border); border-radius: 7px; font-size: 12px; font-weight: 700; cursor: pointer; font-family: 'DM Sans', sans-serif; display: inline-flex; align-items: center; gap: 4px; transition: background .18s; }
        .btn-pin-card:hover { background: #fde68a; }
        .btn-pin-card.pinned { background: var(--pin); color: #fff; border-color: var(--pin); }
        .btn-archive-card { flex: 0 0 auto; padding: 8px 12px; background: var(--archive-light); color: var(--archive); border: 1px solid #e2e8f0; border-radius: 7px; font-size: 12px; font-weight: 700; cursor: pointer; font-family: 'DM Sans', sans-serif; display: inline-flex; align-items: center; gap: 4px; transition: background .18s; }
        .btn-archive-card:hover { background: #e2e8f0; }

        /* ── EMPTY STATE ── */
        .empty-state { text-align: center; padding: 60px 24px; background: var(--white); border-radius: var(--radius); border: 1px solid var(--border); box-shadow: var(--card-shadow); }
        .empty-state .empty-icon { font-size: 52px; margin-bottom: 16px; display: block; }
        .empty-state h3 { font-family: 'Fraunces', serif; font-size: 20px; font-weight: 800; margin-bottom: 8px; }
        .empty-state p { font-size: 14px; color: var(--text-sub); }

        /* ── QUICK VIEW MODAL ── */
        .qv-overlay { display: none; position: fixed; inset: 0; background: rgba(15,14,26,0.5); z-index: 300; align-items: center; justify-content: center; padding: 20px; }
        .qv-overlay.open { display: flex; }
        .qv-box { background: var(--white); border-radius: var(--radius); width: 640px; max-width: 100%; overflow: hidden; box-shadow: 0 20px 60px rgba(15,14,26,0.2); animation: mslide .22s ease; display: flex; }
        @keyframes mslide { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }
        .qv-img { width: 220px; flex-shrink: 0; background: var(--bg); display: flex; align-items: center; justify-content: center; font-size: 56px; color: #c0bde0; position: relative; overflow: hidden; }
        .qv-img img { width: 100%; height: 100%; object-fit: cover; }
        .qv-content { flex: 1; padding: 28px; overflow-y: auto; max-height: 80vh; position: relative; }
        .qv-close { position: absolute; top: 16px; right: 16px; background: var(--white); border: none; border-radius: 50%; width: 32px; height: 32px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 16px; color: var(--text-sub); box-shadow: var(--card-shadow); transition: all .2s; z-index: 10; }
        .qv-cat { font-size: 11px; font-weight: 700; color: var(--text-sub); text-transform: uppercase; letter-spacing: .05em; margin-bottom: 6px; }
        .qv-name { font-family: 'Fraunces', serif; font-size: 22px; font-weight: 800; color: var(--text-main); margin-bottom: 8px; letter-spacing: -0.5px; }
        .qv-price { font-family: 'Fraunces', serif; font-size: 28px; font-weight: 900; color: var(--primary); margin-bottom: 12px; }
        .qv-dispo { font-size: 12px; font-weight: 700; margin-bottom: 12px; }
        .qv-dispo.avail { color: var(--success); }
        .qv-dispo.future { color: var(--warning); }
        .qv-desc { font-size: 14px; color: var(--text-sub); line-height: 1.7; margin-bottom: 16px; }
        .qv-note { background: #fffbeb; border: 1px solid #fcd34d; border-radius: 8px; padding: 10px 14px; margin-bottom: 16px; }
        .qv-note-label { font-size: 11px; font-weight: 800; color: #92400e; margin-bottom: 5px; }
        .qv-note-text { font-size: 13px; color: #78350f; line-height: 1.6; }
        .qv-tags { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 20px; }
        .qv-tag { font-size: 12px; font-weight: 600; color: var(--primary); background: var(--primary-light); border-radius: 6px; padding: 5px 10px; }
        .qv-actions { display: flex; gap: 10px; flex-wrap: wrap; }

        /* ── DELETE MODAL ── */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(15,14,26,0.5); z-index: 300; align-items: center; justify-content: center; padding: 20px; }
        .modal-overlay.open { display: flex; }
        .modal-box { background: var(--white); border-radius: var(--radius); padding: 28px; width: 400px; max-width: 100%; box-shadow: 0 20px 60px rgba(15,14,26,0.2); animation: mslide .22s ease; }
        .modal-icon { width: 46px; height: 46px; border-radius: 12px; background: var(--danger-light); display: flex; align-items: center; justify-content: center; margin-bottom: 16px; }
        .modal-icon svg { width: 22px; height: 22px; color: var(--danger); }
        .modal-box h3 { font-family: 'Fraunces', serif; font-size: 18px; font-weight: 800; margin-bottom: 8px; }
        .modal-box p { font-size: 13.5px; color: var(--text-sub); line-height: 1.6; margin-bottom: 22px; }
        .modal-actions { display: flex; gap: 10px; justify-content: flex-end; }
        .btn-cancel { background: var(--bg); color: var(--text-sub); border: 1.5px solid var(--border); border-radius: var(--radius-sm); padding: 9px 20px; font-size: 13.5px; font-weight: 700; font-family: 'DM Sans', sans-serif; cursor: pointer; transition: all .2s; }
        .btn-cancel:hover { background: var(--white); }
        .btn-confirm-delete { background: var(--danger); color: #fff; border: none; border-radius: var(--radius-sm); padding: 9px 20px; font-size: 13.5px; font-weight: 700; font-family: 'DM Sans', sans-serif; cursor: pointer; text-decoration: none; transition: background .2s; display: inline-block; }
        .btn-confirm-delete:hover { background: #e11d48; }

        /* ── ARCHIVED TAB ── */
        .archived-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 14px; }
        .archived-card { background: var(--white); border: 1px dashed var(--border); border-radius: var(--radius-sm); padding: 14px; display: flex; align-items: center; gap: 12px; opacity: 0.8; transition: opacity .2s; }
        .archived-card:hover { opacity: 1; }
        .archived-thumb { width: 44px; height: 44px; border-radius: 8px; background: var(--bg); display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; overflow: hidden; }
        .archived-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .archived-info { flex: 1; min-width: 0; }
        .archived-name { font-size: 13px; font-weight: 700; color: var(--text-sub); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .archived-cat { font-size: 11px; color: var(--text-dim); }
        .btn-restore { background: var(--success-light); color: var(--success); border: none; border-radius: 6px; padding: 5px 12px; font-size: 12px; font-weight: 700; cursor: pointer; font-family: 'DM Sans', sans-serif; transition: background .15s; }
        .btn-restore:hover { background: #c7f5e6; }
        .btn-delete-arch { background: var(--danger-light); color: var(--danger); border: none; border-radius: 6px; padding: 5px 10px; font-size: 12px; font-weight: 700; cursor: pointer; font-family: 'DM Sans', sans-serif; transition: background .15s; }
        .btn-delete-arch:hover { background: #fecdd3; }

        .no-results { text-align: center; padding: 40px; color: var(--text-sub); font-size: 14px; background: var(--white); border-radius: var(--radius); border: 1px solid var(--border); }

        @media (max-width: 1000px) { .kpi-strip { grid-template-columns: repeat(3, 1fr); } .top-grid { grid-template-columns: repeat(3, 1fr); } }
        @media (max-width: 900px) { .form-layout { grid-template-columns: 1fr; } .kpi-strip { grid-template-columns: 1fr 1fr; } .recent-grid { grid-template-columns: 1fr; } nav { padding: 0 20px; } .nav-links { display: none; } .qv-box { flex-direction: column; } .qv-img { width: 100%; height: 200px; } .top-grid { grid-template-columns: repeat(2, 1fr); } }
    </style>
</head>
<body>

<!-- NAV -->
<nav>
    <a href="#" class="nav-logo">
        <img src="<?= $baseUrl ?>/Vue/public/images/logo.png" alt="Cre8Connect">
        <span class="nav-logo-text">Cre8Connect</span>
    </a>
    <ul class="nav-links">
        <li><a href="#">Dashboard</a></li>
        <li><a href="#">My Offers</a></li>
        <li><a href="#" class="active">My Products</a></li>
        <li><a href="#">Campaigns</a></li>
        <li><a href="#">My Profile</a></li>
    </ul>
    <div class="nav-right">
        <span class="nav-badge">Brand</span>
        <div class="nav-avatar">B</div>
    </div>
</nav>

<div class="page-wrapper">

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div class="page-header-left">
            <h1>My Products</h1>
            <p>Manage, organize and promote your products to content creators.</p>
        </div>
        <div class="page-header-actions">
            <button type="button" id="btnExport" class="btn-export">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Export CSV
            </button>
            <a href="#formAnchor" class="btn-add-product" id="btnShowForm">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Add a product
            </a>
        </div>
    </div>

    <!-- FLASH -->
    <?php if ($message): ?>
        <div class="flash <?= $messageType ?>" id="flashMsg">
            <?= $messageType === 'success'
                ? '<svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>'
                : '<svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>' ?>
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- KPI STRIP -->
    <div class="kpi-strip">
        <div class="kpi-card">
            <div class="kpi-icon">📦</div>
            <div class="kpi-value"><?= $totalProduits ?></div>
            <div class="kpi-label">Active products</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon">💶</div>
            <div class="kpi-value"><?= number_format($prixMoyen, 0, '.', ' ') ?> €</div>
            <div class="kpi-label">Avg. price</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon">📌</div>
            <div class="kpi-value"><?= count($epingles) ?></div>
            <div class="kpi-label">Pinned</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon">🖼️</div>
            <div class="kpi-value"><?= $sanImage ?></div>
            <div class="kpi-label">Without image</div>
            <?php if ($sanImage > 0): ?>
                <div class="kpi-alert">⚠️ Complete these listings</div>
            <?php endif; ?>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon">🗄️</div>
            <div class="kpi-value"><?= $totalArchives ?></div>
            <div class="kpi-label">Archived</div>
            <?php if ($totalArchives > 0): ?>
                <div class="kpi-link">View archives →</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- COMPLETION REMINDERS -->
    <?php if (!empty($rappels)): ?>
    <div class="rappels-section" id="rappelsSection">
        <div class="rappels-header">
            <span class="icon">⚠️</span>
            <h3>Completion reminders — <?= count($rappels) ?> incomplete listing<?= count($rappels) > 1 ? 's' : '' ?></h3>
        </div>
        <div class="rappels-list">
            <?php foreach ($rappels as $r): ?>
            <div class="rappel-item">
                <div class="rappel-name"><?= htmlspecialchars($r['nom']) ?></div>
                <div class="rappel-bar-wrap">
                    <div class="rappel-pct"><?= $r['pct'] ?>%</div>
                    <div class="rappel-bar"><div class="rappel-bar-fill" style="width:<?= $r['pct'] ?>%"></div></div>
                </div>
                <div class="rappel-missing">Missing: <?= implode(', ', $r['manquants']) ?></div>
                <a href="index.php?modifier=<?= $r['id'] ?>" class="rappel-btn">Complete →</a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- TOP PRODUCTS -->
    <?php if (!empty($topProduits)): ?>
    <div class="top-section">
        <div class="top-header">
            <span>🔥</span>
            <h2>Top products</h2>
            <span class="top-badge">Most requested</span>
        </div>
        <div class="top-grid">
            <?php foreach ($topProduits as $i => $tp):
                $rankClass = $i === 0 ? 'r1' : ($i === 1 ? 'r2' : ($i === 2 ? 'r3' : ''));
            ?>
            <div class="top-card">
                <div class="top-rank <?= $rankClass ?>"><?= $i + 1 ?></div>
                <div class="top-thumb">
                    <?php if (!empty($tp['image'])): ?>
                        <img src="<?= $baseUrl ?>/Vue/public/produits/<?= htmlspecialchars($tp['image']) ?>" alt="">
                    <?php else: ?>📦<?php endif; ?>
                </div>
                <div class="top-info">
                    <div class="top-name"><?= htmlspecialchars($tp['nomProduit']) ?></div>
                    <div class="top-stat"><?= isset($tp['nbOffres']) ? $tp['nbOffres'] . ' offer' . ($tp['nbOffres'] > 1 ? 's' : '') : '—' ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- RECENT PRODUCTS -->
    <?php if (!empty($produitsRecents)): ?>
    <div class="recent-section">
        <div class="recent-header">
            <span class="recent-dot"></span>
            <h2>Recently added</h2>
            <span class="count-pill" style="font-size:11px;"><?= count($produitsRecents) ?> latest</span>
        </div>
        <div class="recent-grid">
            <?php foreach ($produitsRecents as $r): ?>
            <div class="recent-card">
                <div class="recent-thumb">
                    <?php if (!empty($r['image'])): ?>
                        <img src="<?= $baseUrl ?>/Vue/public/produits/<?= htmlspecialchars($r['image']) ?>" alt="">
                    <?php else: ?>📦<?php endif; ?>
                </div>
                <div class="recent-info">
                    <div class="recent-name"><?= htmlspecialchars($r['nomProduit']) ?></div>
                    <div class="recent-price"><?= number_format((float)$r['prix'], 2, '.', ' ') ?> €</div>
                </div>
                <div class="recent-actions">
                    <a href="index.php?modifier=<?= $r['idProduit'] ?>" class="recent-btn edit">✏️ Edit</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- FORM SECTION -->
    <div class="form-layout" id="formAnchor">
        <div class="form-card <?= $editProduit ? 'edit-mode' : '' ?>" id="formCard">
            <div class="form-card-header">
                <h2><?= $editProduit ? '✏️ Edit product' : '➕ Add a product' ?></h2>
                <p><?= $editProduit ? 'Update the fields and save your changes.' : 'Fill in the details of your new product.' ?></p>
            </div>
            <?php if ($editProduit): ?>
                <div class="edit-banner">
                    <span>Editing: <?= htmlspecialchars($editProduit['nomProduit']) ?></span>
                    <a href="index.php">✕ Cancel</a>
                </div>
            <?php endif; ?>

            <!-- Completion bar -->
            <div class="completion-wrap">
                <div class="completion-top">
                    <span class="completion-label">Listing completeness</span>
                    <span class="completion-pct" id="completionPct">0%</span>
                </div>
                <div class="completion-bar"><div class="completion-fill" id="completionFill" style="width:0%"></div></div>
                <div class="completion-hint" id="completionHint">Fill in all fields to maximize your product's visibility to creators.</div>
            </div>

            <form method="POST" action="index.php" enctype="multipart/form-data" id="produitForm" novalidate>
                <input type="hidden" name="action" value="<?= $editProduit ? 'modifier' : 'ajouter' ?>">
                <?php if ($editProduit): ?>
                    <input type="hidden" name="id" value="<?= $editProduit['idProduit'] ?>">
                    <input type="hidden" name="estEpingle" value="<?= (int)($editProduit['estEpingle'] ?? 0) ?>" id="epingleHidden">
                    <input type="hidden" name="estArchive" value="<?= (int)($editProduit['estArchive'] ?? 0) ?>" id="archiveHidden">
                <?php endif; ?>
                <input type="hidden" name="caracteristiques" id="caracHidden" value="<?= $editProduit ? htmlspecialchars($editProduit['caracteristiques'] ?? '') : '' ?>">

                <!-- Name -->
                <div class="form-group">
                    <label for="nom">Product name * <span class="char-counter" id="nomCounter">0 / 80</span></label>
                    <input type="text" id="nom" name="nom" class="form-input" maxlength="80"
                           value="<?= $editProduit ? htmlspecialchars($editProduit['nomProduit']) : '' ?>">
                    <div class="error-msg" id="nomError">Product name is required (min. 2 characters).</div>
                </div>

                <!-- Description -->
                <div class="form-group">
                    <label for="description">Description * <span class="char-counter" id="descCounter">0 / 400</span></label>
                    <textarea id="description" name="description" class="form-input" maxlength="400"><?= $editProduit ? htmlspecialchars($editProduit['description']) : '' ?></textarea>
                    <div class="error-msg" id="descError">A description is required (min. 10 characters).</div>
                </div>

                <!-- Category + Price -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="categorie">Category</label>
                        <select id="categorie" name="categorie" class="form-input">
                            <option value="">— Select a category —</option>
                            <?php foreach ($categoriesDisponibles as $cat): ?>
                                <option value="<?= htmlspecialchars($cat) ?>"
                                    <?= ($editProduit && ($editProduit['categorie'] ?? '') === $cat) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat) ?>
                                </option>
                            <?php endforeach; ?>
                            <?php if ($editProduit && !empty($editProduit['categorie']) && !in_array($editProduit['categorie'], $categoriesDisponibles)): ?>
                                <option value="<?= htmlspecialchars($editProduit['categorie']) ?>" selected><?= htmlspecialchars($editProduit['categorie']) ?></option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="prix">Price *</label>
                        <div class="input-with-prefix">
                            <span class="prefix">€</span>
                            <input type="text" name="prix" id="prix" class="form-input"
                                   autocomplete="off"
                                   value="<?= $editProduit ? htmlspecialchars($editProduit['prix']) : '' ?>">
                        </div>
                        <!-- Message d'erreur dynamique — modifié par JS selon le cas -->
                        <div class="error-msg" id="prixError">Le prix est obligatoire (nombre ≥ 0, max 2 décimales).</div>
                        <div class="field-hint">ℹ️ Utilisez un point ou une virgule comme séparateur décimal. Ex : 12.99 ou 12,99</div>
                    </div>
                </div>

                <!-- Availability date -->
                <div class="form-group">
                    <label for="dateDisponibilite">
                        Availability date
                        <span style="font-size:11px;color:var(--text-dim);font-weight:400;">Optional — leave empty = available now</span>
                    </label>
                    <input type="text" id="dateDisponibilite" name="dateDisponibilite" class="form-input"
                           autocomplete="off"
                           value="<?= $editProduit ? htmlspecialchars($editProduit['dateDisponibilite'] ?? '') : '' ?>">
                    <!-- Message d'erreur dynamique — modifié par JS selon le cas -->
                    <div class="error-msg" id="dateError">Format invalide. Utilisez AAAA-MM-JJ (ex : 2025-12-31).</div>
                    <div id="dispoPreview" class="dispo-badge nodate" style="margin-top:6px;">📅 Available now</div>
                </div>

                <!-- Tags / Characteristics -->
                <div class="form-group">
                    <label>Tags * <span style="font-size:11px;color:var(--text-dim);font-weight:500;">Characteristics</span></label>
                    <div class="tags-container" id="tagsContainer">
                        <div class="tags-selected" id="tagsSelected"></div>
                        <div class="tags-grid" id="tagsGrid">
                            <?php foreach ($tagsDisponibles as $tag): ?>
                                <button type="button" class="tag-btn"><?= htmlspecialchars($tag) ?></button>
                            <?php endforeach; ?>
                        </div>
                        <div class="tags-custom-row">
                            <input type="text" class="tags-custom-input" id="tagCustomInput" maxlength="30">
                            <button type="button" class="tags-custom-btn">+ Add</button>
                        </div>
                        <div class="tags-hint">Selected tags will appear on your product listing visible to creators.</div>
                    </div>
                    <div class="error-msg" id="caracError">Please add at least one tag.</div>
                </div>

                <!-- Internal note -->
                <div class="form-group">
                    <label>Internal note</label>
                    <div class="note-interne-wrap">
                        <div class="note-interne-header">
                            <span class="lock-icon">🔒</span>
                            <span>Visible to your brand team only</span>
                        </div>
                        <textarea name="noteInterne" id="noteInterne" class="note-interne"><?= $editProduit ? htmlspecialchars($editProduit['noteInterne'] ?? '') : '' ?></textarea>
                    </div>
                </div>

                <!-- Pin + Archive toggles (edit mode only) -->
                <?php if ($editProduit): ?>
                <div class="toggle-row">
                    <div class="toggle-item">
                        <div>
                            <label for="toggleEpingle" style="margin:0;cursor:pointer;font-size:13px;">📌 Pin this product</label>
                            <div class="toggle-desc">Displayed at the top of the catalog</div>
                        </div>
                        <label class="toggle-switch pin">
                            <input type="checkbox" id="toggleEpingle"
                                   <?= !empty($editProduit['estEpingle']) ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="toggle-item">
                        <div>
                            <label for="toggleArchive" style="margin:0;cursor:pointer;font-size:13px;">🗄️ Archive</label>
                            <div class="toggle-desc">Hidden from the active catalog</div>
                        </div>
                        <label class="toggle-switch archive">
                            <input type="checkbox" id="toggleArchive"
                                   <?= !empty($editProduit['estArchive']) ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Image -->
                <div class="form-group">
                    <label>Product image</label>
                    <?php if ($editProduit && !empty($editProduit['image'])): ?>
                        <div class="current-img-block">
                            <img src="<?= $baseUrl ?>/Vue/public/produits/<?= htmlspecialchars($editProduit['image']) ?>" alt="">
                            <div>
                                <strong>Current image</strong>
                                <span>Upload a new image to replace it.</span>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="upload-zone" id="uploadZone">
                        <input type="file" name="image" id="fileInput" accept="image/jpeg,image/png,image/webp">
                        <div class="upload-icon-wrap">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <p><strong>Click to upload</strong> or drag an image here</p>
                        <span class="upload-hint">JPG, PNG, WEBP — 2 MB max</span>
                    </div>
                    <div class="error-msg" id="imageError">File too large (max 2 MB) or invalid format (JPG, PNG, WEBP only).</div>
                    <div class="img-preview-realtime" id="imgPreviewWrap">
                        <img id="imgPreview" src="" alt="Preview">
                    </div>
                </div>

                <!-- Live Preview -->
                <div class="live-preview-wrap">
                    <div class="live-preview-label">Live card preview</div>
                    <div class="live-preview-card">
                        <div class="lp-img" id="lpImg">
                            <img id="lpImgEl" src="" alt="">
                            📦
                        </div>
                        <div class="lp-body">
                            <div class="lp-meta">
                                <span class="lp-cat" id="lpCat"></span>
                            </div>
                            <div class="lp-name" id="lpName">Product name</div>
                            <div class="lp-price-badge" id="lpPrice">0.00 €</div>
                            <div class="lp-desc" id="lpDesc">Description will appear here…</div>
                            <div class="lp-tags" id="lpTags"></div>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <?= $editProduit ? 'Save changes' : 'Add product' ?>
                    </button>
                    <?php if ($editProduit): ?>
                        <a href="index.php" class="btn-outline">Cancel</a>
                    <?php else: ?>
                        <button type="reset" class="btn-outline">Reset</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- TIPS SIDEBAR -->
        <div class="tips-card">
            <div class="tips-card-header">
                <div class="icon">💡</div>
                <h3>Publishing tips</h3>
            </div>
            <div class="tip-item">
                <div class="tip-dot">1</div>
                <div><strong>Catchy name</strong><p>Short, precise, and memorable.</p></div>
            </div>
            <div class="tip-item">
                <div class="tip-dot">2</div>
                <div><strong>Right category</strong><p>Helps creators filter and find your products quickly.</p></div>
            </div>
            <div class="tip-item">
                <div class="tip-dot">3</div>
                <div><strong>Availability date</strong><p>Plan a launch or upcoming promotion in advance.</p></div>
            </div>
            <div class="tip-item">
                <div class="tip-dot">4</div>
                <div><strong>Internal note</strong><p>Private briefing for your team or follow-up reminders.</p></div>
            </div>
            <div class="tip-item">
                <div class="tip-dot">5</div>
                <div><strong>Pin your stars</strong><p>Highlight your best products for creators to notice first.</p></div>
            </div>
            <div class="tip-item">
                <div class="tip-dot">6</div>
                <div><strong>Archive wisely</strong><p>Archived products disappear from the catalog but remain recoverable.</p></div>
            </div>
        </div>
    </div>

    <!-- CATALOGUE TABS -->
    <div class="section-head">
        <div class="section-head-left">
            <h2>Product catalog</h2>
        </div>
        <div class="tools-bar">
            <div class="search-wrap">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                <input type="text" id="searchInput" class="search-input">
            </div>
            <div class="price-filter">
                <span class="price-filter-label">Price:</span>
                <div class="price-range-wrap">
                    <input type="text" id="priceMin" class="price-input">
                    <span class="price-sep">–</span>
                    <input type="text" id="priceMax" class="price-input">
                </div>
            </div>
            <select id="sortSelect" class="sort-select">
                <option value="">Sort by…</option>
                <option value="nom">Name A→Z</option>
                <option value="prix_asc">Price ↑</option>
                <option value="prix_desc">Price ↓</option>
                <option value="epingle">Pinned first</option>
            </select>
            <div class="view-toggle">
                <button class="view-btn active" id="vbtn-grid" title="Grid">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                </button>
                <button class="view-btn" id="vbtn-list" title="List">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
            </div>
        </div>
    </div>

    <!-- TAB BAR -->
    <div class="tab-bar">
        <button class="tab-btn active" id="tab-actifs">
            Active <span class="tab-pill"><?= $totalProduits ?></span>
        </button>
        <button class="tab-btn" id="tab-archives">
            Archived <span class="tab-pill archive"><?= $totalArchives ?></span>
        </button>
    </div>

    <!-- TAB: ACTIVE -->
    <div class="tab-content active" id="content-actifs">

        <?php if (!empty($categories)): ?>
        <div class="cat-filter-bar" id="catFilterBar">
            <span class="cat-filter-label">Category:</span>
            <button class="cat-chip active">All</button>
            <?php foreach ($categories as $cat): ?>
                <button class="cat-chip">
                    <?= htmlspecialchars($cat) ?>
                </button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Pinned products -->
        <?php if (!empty($epingles)): ?>
        <div class="pinned-section">
            <div class="pinned-header">
                <span class="pin-icon">📌</span>
                <h3>Pinned products</h3>
                <span class="count-pill" style="font-size:11px;"><?= count($epingles) ?></span>
            </div>
            <div class="pinned-grid">
                <?php foreach ($epingles as $ep): ?>
                <div class="pinned-card" data-id="<?= $ep['idProduit'] ?>">
                    <span class="pin-badge">Pinned</span>
                    <div class="pinned-name"><?= htmlspecialchars($ep['nomProduit']) ?></div>
                    <div class="pinned-price"><?= number_format((float)$ep['prix'], 2, '.', ' ') ?> €</div>
                    <?php if (!empty($ep['categorie'])): ?>
                        <div class="pinned-cat">📂 <?= htmlspecialchars($ep['categorie']) ?></div>
                    <?php endif; ?>
                    <div class="pinned-actions">
                        <a href="index.php?modifier=<?= $ep['idProduit'] ?>" class="recent-btn edit" style="border-radius:6px;font-size:11px;">✏️ Edit</a>
                        <button class="btn-unpin">Unpin</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Drag hint -->
        <?php if ($totalProduits > 1): ?>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;flex-wrap:wrap;gap:8px;">
            <div class="drag-hint">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8h16M4 16h16"/></svg>
                Drag cards to reorder the display order
            </div>
            <button type="button" class="btn-export" id="btnSaveOrder" style="display:none;">
                💾 Save order
            </button>
        </div>
        <?php endif; ?>

        <?php if (empty($produits)): ?>
            <div class="empty-state">
                <span class="empty-icon">📦</span>
                <h3>No products yet</h3>
                <p>Add your first product to start collaborating with creators.</p>
            </div>
        <?php else: ?>
            <div class="products-grid drag-mode" id="productsGrid">
                <?php foreach ($produits as $p):
                    $tags = array_filter(array_map('trim', explode(',', $p['caracteristiques'] ?? '')));
                    $isPinned = !empty($p['estEpingle']);
                    $dispo = $p['dateDisponibilite'] ?? null;
                    $dispoFuture = $dispo && strtotime($dispo) > time();
                ?>
                <div class="product-card <?= $isPinned ? 'is-pinned' : '' ?>"
                     draggable="true"
                     data-id="<?= $p['idProduit'] ?>"
                     data-name="<?= htmlspecialchars(strtolower($p['nomProduit'])) ?>"
                     data-desc="<?= htmlspecialchars(strtolower($p['description'])) ?>"
                     data-prix="<?= (float)$p['prix'] ?>"
                     data-cat="<?= htmlspecialchars(strtolower($p['categorie'] ?? '')) ?>"
                     data-epingle="<?= (int)$isPinned ?>"
                     data-img="<?= !empty($p['image']) ? $baseUrl . '/Vue/public/produits/' . htmlspecialchars($p['image']) : '' ?>"
                     data-tags="<?= htmlspecialchars(implode(',', $tags)) ?>"
                     data-note="<?= htmlspecialchars($p['noteInterne'] ?? '') ?>"
                     data-dispo="<?= htmlspecialchars($dispo ?? '') ?>">

                    <div class="pcard-img">
                        <?php if ($isPinned): ?>
                            <div class="badge-epingle">📌 Pinned</div>
                        <?php endif; ?>
                        <?php if ($dispo): ?>
                            <div class="badge-dispo <?= $dispoFuture ? 'future' : '' ?>">
                                <?= $dispoFuture ? '⏳ From ' . date('d/m/Y', strtotime($dispo)) : '✅ Available' ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($p['image'])): ?>
                            <img src="<?= $baseUrl ?>/Vue/public/produits/<?= htmlspecialchars($p['image']) ?>"
                                 alt="<?= htmlspecialchars($p['nomProduit']) ?>" loading="lazy">
                        <?php else: ?>
                            <div class="pcard-img-empty">📦</div>
                        <?php endif; ?>
                        <div class="pcard-price-badge"><?= number_format((float)$p['prix'], 2, '.', ' ') ?> €</div>
                        <div class="pcard-quickview">
                            <button class="btn-quickview">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                Quick view
                            </button>
                        </div>
                    </div>

                    <div class="pcard-body">
                        <?php if (!empty($p['categorie'])): ?>
                            <div class="pcard-cat"><span class="cat-dot"></span><?= htmlspecialchars($p['categorie']) ?></div>
                        <?php endif; ?>
                        <div class="pcard-name"><?= htmlspecialchars($p['nomProduit']) ?></div>
                        <p class="pcard-desc"><?= htmlspecialchars($p['description']) ?></p>

                        <?php if ($dispo): ?>
                        <div class="pcard-dispo-row">
                            <span class="<?= $dispoFuture ? 'dot-future' : 'dot-avail' ?>"></span>
                            <?= $dispoFuture
                                ? 'Available from ' . date('d/m/Y', strtotime($dispo))
                                : 'Available since ' . date('d/m/Y', strtotime($dispo)) ?>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($p['noteInterne'])): ?>
                        <div class="pcard-note-row">
                            <span class="note-label">🔒</span>
                            <span class="pcard-note-text"><?= htmlspecialchars($p['noteInterne']) ?></span>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($tags)): ?>
                            <div class="pcard-tags">
                                <?php foreach (array_slice($tags, 0, 3) as $tag): ?>
                                    <span class="pcard-tag">🏷️ <?= htmlspecialchars($tag) ?></span>
                                <?php endforeach; ?>
                                <?php if (count($tags) > 3): ?>
                                    <span class="pcard-tag" style="background:var(--bg);color:var(--text-sub);">+<?= count($tags) - 3 ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="pcard-actions">
                            <a href="index.php?modifier=<?= $p['idProduit'] ?>" class="btn-edit-card">
                                <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                Edit
                            </a>
                            <button class="btn-pin-card <?= $isPinned ? 'pinned' : '' ?>"
                                    title="<?= $isPinned ? 'Unpin' : 'Pin' ?>">
                                📌
                            </button>
                            <button class="btn-archive-card"
                                    title="Archive this product">
                                🗄️
                            </button>
                            <button class="btn-delete-card">
                                <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div id="noResults" class="no-results" style="display:none;">🔍 No products match your search.</div>
        <?php endif; ?>
    </div>

    <!-- TAB: ARCHIVED -->
    <div class="tab-content" id="content-archives">
        <?php if (empty($produitsArchives)): ?>
            <div class="empty-state">
                <span class="empty-icon">🗄️</span>
                <h3>No archived products</h3>
                <p>Products you archive will appear here and can be restored at any time.</p>
            </div>
        <?php else: ?>
            <div class="archived-grid">
                <?php foreach ($produitsArchives as $a): ?>
                <div class="archived-card" data-id="<?= $a['idProduit'] ?>" data-name="<?= htmlspecialchars($a['nomProduit']) ?>">
                    <div class="archived-thumb">
                        <?php if (!empty($a['image'])): ?>
                            <img src="<?= $baseUrl ?>/Vue/public/produits/<?= htmlspecialchars($a['image']) ?>" alt="">
                        <?php else: ?>📦<?php endif; ?>
                    </div>
                    <div class="archived-info">
                        <div class="archived-name"><?= htmlspecialchars($a['nomProduit']) ?></div>
                        <div class="archived-cat"><?= !empty($a['categorie']) ? '📂 ' . htmlspecialchars($a['categorie']) : number_format((float)$a['prix'], 2, '.', ' ') . ' €' ?></div>
                    </div>
                    <button class="btn-restore" title="Restore to catalog">♻️ Restore</button>
                    <button class="btn-delete-arch" title="Delete permanently">🗑️</button>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- QUICK VIEW MODAL -->
<div class="qv-overlay" id="qvModal">
    <div class="qv-box">
        <div class="qv-img" id="qvImg">📦</div>
        <div class="qv-content">
            <button class="qv-close">✕</button>
            <div class="qv-cat" id="qvCat"></div>
            <div class="qv-name" id="qvName"></div>
            <div class="qv-price" id="qvPrice"></div>
            <div class="qv-dispo" id="qvDispo"></div>
            <div class="qv-desc" id="qvDesc"></div>
            <div class="qv-note" id="qvNoteWrap" style="display:none;">
                <div class="qv-note-label">🔒 Internal note (brand team only)</div>
                <div class="qv-note-text" id="qvNote"></div>
            </div>
            <div class="qv-tags" id="qvTags"></div>
            <div class="qv-actions">
                <a id="qvEditBtn" href="#" class="btn-primary" style="font-size:13px;padding:9px 18px;">✏️ Edit</a>
            </div>
        </div>
    </div>
</div>

<!-- DELETE MODAL -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal-box">
        <div class="modal-icon">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        </div>
        <h3>Delete this product?</h3>
        <p id="deleteModalText">This action is permanent and cannot be undone.</p>
        <div class="modal-actions">
            <button class="btn-cancel">Cancel</button>
            <a href="#" id="deleteConfirmBtn" class="btn-confirm-delete">Delete</a>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?= $baseUrl ?>';

/* ═══════════════════════════════════════════════════════════════════════════
   UTILITAIRE : afficher / masquer un message d'erreur sur un champ
   ═══════════════════════════════════════════════════════════════════════════ */
/**
 * Bascule l'état de validité d'un champ.
 * @param {string}  fieldId  — id du champ input/select/textarea
 * @param {string}  errorId  — id du div .error-msg
 * @param {boolean} isValid  — true = pas d'erreur, false = erreur
 */
function setFieldValidity(fieldId, errorId, isValid) {
    const field = document.getElementById(fieldId);
    const err   = document.getElementById(errorId);
    if (!field || !err) return;
    if (isValid) {
        field.classList.remove('error');
        err.classList.remove('visible');
    } else {
        field.classList.add('error');
        err.classList.add('visible');
    }
}

/* ═══════════════════════════════════════════════════════════════════════════
   VALIDATION PRIX  — type SQL : DECIMAL(10,2)
   Règles :
     • Obligatoire
     • Chiffres uniquement, point OU virgule comme séparateur décimal
     • Valeur >= 0
     • Partie entière : max 8 chiffres  (10 total − 2 décimales)
     • Partie décimale : max 2 chiffres
   ═══════════════════════════════════════════════════════════════════════════ */
function validatePrix() {
    const rawOriginal = document.getElementById('prix').value.trim();
    const raw         = rawOriginal.replace(',', '.');   // normalisation virgule → point
    const errEl       = document.getElementById('prixError');

    /* 1. Champ vide → obligatoire */
    if (rawOriginal === '') {
        errEl.textContent = 'Le prix est obligatoire.';
        setFieldValidity('prix', 'prixError', false);
        return false;
    }

    /* 2. Caractères interdits : seuls chiffres, un seul séparateur décimal */
    if (!/^\d+([.,]\d*)?$/.test(rawOriginal)) {
        errEl.textContent = 'Seuls les chiffres sont autorisés. Séparateur décimal : point ou virgule.';
        setFieldValidity('prix', 'prixError', false);
        return false;
    }

    const num = parseFloat(raw);

    /* 3. Valeur négative */
    if (num < 0) {
        errEl.textContent = 'Le prix ne peut pas être négatif.';
        setFieldValidity('prix', 'prixError', false);
        return false;
    }

    /* 4. Décomposition partie entière / décimale */
    const parts   = raw.split('.');
    const intPart = parts[0];          // jamais de signe car déjà filtré
    const decPart = parts[1] || '';

    /* 5. Partie entière : max 8 chiffres  (DECIMAL(10,2) → 10 − 2 = 8) */
    if (intPart.length > 8) {
        errEl.textContent = 'La partie entière ne peut pas dépasser 8 chiffres.';
        setFieldValidity('prix', 'prixError', false);
        return false;
    }

    /* 6. Partie décimale : max 2 chiffres */
    if (decPart.length > 2) {
        errEl.textContent = 'Maximum 2 décimales autorisées (ex : 12.99 ou 12,99).';
        setFieldValidity('prix', 'prixError', false);
        return false;
    }

    /* OK */
    setFieldValidity('prix', 'prixError', true);
    return true;
}

/* ═══════════════════════════════════════════════════════════════════════════
   BLOCAGE CLAVIER  — champ prix
   Autorisés  : chiffres 0-9, un seul point, une seule virgule,
                touches de navigation / édition.
   Refusés    : tout le reste.
   ═══════════════════════════════════════════════════════════════════════════ */
function onPrixKeydown(e) {
    /* Touches systèmes toujours autorisées */
    const systemKeys = [
        'Backspace','Delete','Tab','Escape','Enter',
        'ArrowLeft','ArrowRight','ArrowUp','ArrowDown',
        'Home','End'
    ];
    if (systemKeys.includes(e.key)) return;

    /* Copier / Coller / Couper / Sélectionner tout */
    if (e.ctrlKey || e.metaKey) return;

    /* Séparateur décimal : point ou virgule, un seul */
    if (e.key === '.' || e.key === ',') {
        const current = document.getElementById('prix').value;
        /* Interdit si un séparateur est déjà présent */
        if (current.includes('.') || current.includes(',')) {
            e.preventDefault();
        }
        return;
    }

    /* Chiffres uniquement */
    if (!/^\d$/.test(e.key)) {
        e.preventDefault();
    }
}

/* ═══════════════════════════════════════════════════════════════════════════
   VALIDATION DATE  — type SQL : DATE  (stocké en YYYY-MM-DD)
   Règles :
     • Optionnel — champ vide = valide (disponible immédiatement)
     • Format strict  YYYY-MM-DD
     • Année comprise entre 1000 et 9999 (plage SQL DATE)
     • Date calendaire réelle  (ex : 2024-02-30 refusé)
     • La date ne peut pas être dans le passé (règle métier)
   ═══════════════════════════════════════════════════════════════════════════ */
function validateDate() {
    const val   = document.getElementById('dateDisponibilite').value.trim();
    const errEl = document.getElementById('dateError');

    /* 1. Champ optionnel — vide = valide */
    if (val === '') {
        setFieldValidity('dateDisponibilite', 'dateError', true);
        return true;
    }

    /* 2. Format strict YYYY-MM-DD */
    if (!/^\d{4}-\d{2}-\d{2}$/.test(val)) {
        errEl.textContent = 'Format invalide. Utilisez AAAA-MM-JJ (ex : 2025-12-31).';
        setFieldValidity('dateDisponibilite', 'dateError', false);
        return false;
    }

    const [year, month, day] = val.split('-').map(Number);

    /* 3. Plage d'année acceptée par SQL DATE */
    if (year < 1000 || year > 9999) {
        errEl.textContent = "L'année doit être comprise entre 1000 et 9999.";
        setFieldValidity('dateDisponibilite', 'dateError', false);
        return false;
    }

    /* 4. Date calendaire réelle
          new Date() corrige silencieusement les dates invalides (ex : 31 fév → 2 mars).
          On compare donc les composantes avec celles fournies. */
    const dateObj = new Date(year, month - 1, day);
    const isRealDate = (
        dateObj.getFullYear() === year  &&
        dateObj.getMonth()    === month - 1 &&
        dateObj.getDate()     === day
    );

    if (!isRealDate) {
        errEl.textContent = "Cette date n'existe pas dans le calendrier (ex : le 31 février est refusé).";
        setFieldValidity('dateDisponibilite', 'dateError', false);
        return false;
    }

    /* 5. Pas dans le passé (règle métier : date de disponibilité future ou aujourd'hui) */
    const today = new Date();
    today.setHours(0, 0, 0, 0);   // minuit du jour courant pour comparaison juste

    if (dateObj < today) {
        errEl.textContent = 'La date de disponibilité ne peut pas être dans le passé.';
        setFieldValidity('dateDisponibilite', 'dateError', false);
        return false;
    }

    /* OK */
    setFieldValidity('dateDisponibilite', 'dateError', true);
    return true;
}

/* ═══════════════════════════════════════════════════════════════════════════
   BLOCAGE CLAVIER  — champ date
   Autorisés : chiffres 0-9, tiret, touches navigation / édition.
   ═══════════════════════════════════════════════════════════════════════════ */
function onDateKeydown(e) {
    const systemKeys = [
        'Backspace','Delete','Tab','Escape','Enter',
        'ArrowLeft','ArrowRight','ArrowUp','ArrowDown',
        'Home','End'
    ];
    if (systemKeys.includes(e.key)) return;
    if (e.ctrlKey || e.metaKey) return;

    /* Tiret autorisé (séparateur YYYY-MM-DD) */
    if (e.key === '-') return;

    /* Chiffres uniquement */
    if (!/^\d$/.test(e.key)) {
        e.preventDefault();
    }
}

/* ═══════════════════════════════════════════════════════════════════════════
   FORMATAGE AUTOMATIQUE DATE  — insère les tirets à la frappe
   YYYY → YYYY- → YYYY-MM → YYYY-MM- → YYYY-MM-DD
   ═══════════════════════════════════════════════════════════════════════════ */
function onDateInput(e) {
    let val = e.target.value.replace(/[^\d]/g, '');   // garde uniquement les chiffres
    if (val.length > 4)  val = val.slice(0,4) + '-' + val.slice(4);
    if (val.length > 7)  val = val.slice(0,7) + '-' + val.slice(7);
    if (val.length > 10) val = val.slice(0,10);        // max 10 caractères YYYY-MM-DD
    e.target.value = val;
    updateDispoPreview();
    updateCompletion();
}

/* ═══════════════════════════════════════════════════════════════════════════
   VALIDATION PRIX CATALOGUE  — filtres min/max
   Empêche les valeurs négatives dans les champs de filtre.
   ═══════════════════════════════════════════════════════════════════════════ */
function validatePriceInput(el) {
    const raw = el.value.replace(',', '.');
    const v   = parseFloat(raw);
    if (!isNaN(v) && v < 0) {
        el.value = '0';
    }
}

/* ═══════════════════════════════════════════════════════════════════════════
   AUTRES FONCTIONS DE VALIDATION  (inchangées)
   ═══════════════════════════════════════════════════════════════════════════ */
function validateNom() {
    const val = document.getElementById('nom').value.trim();
    const ok  = val.length >= 2;
    setFieldValidity('nom', 'nomError', ok);
    return ok;
}

function validateDescription() {
    const val = document.getElementById('description').value.trim();
    const ok  = val.length >= 10;
    setFieldValidity('description', 'descError', ok);
    return ok;
}

function validateTags() {
    const ok        = selectedTags.length > 0;
    const err       = document.getElementById('caracError');
    const container = document.getElementById('tagsContainer');
    if (ok) {
        if (err)       err.classList.remove('visible');
        if (container) container.classList.remove('error');
    } else {
        if (err)       err.classList.add('visible');
        if (container) container.classList.add('error');
    }
    return ok;
}

function validateImage() {
    const input = document.getElementById('fileInput');
    const err   = document.getElementById('imageError');
    const zone  = document.getElementById('uploadZone');
    if (!input || !input.files || input.files.length === 0) {
        if (err)  err.classList.remove('visible');
        if (zone) zone.classList.remove('error');
        return true;
    }
    const file         = input.files[0];
    const maxSize      = 2 * 1024 * 1024;
    const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    const ok           = file.size <= maxSize && allowedTypes.includes(file.type);
    if (ok) {
        if (err)  err.classList.remove('visible');
        if (zone) zone.classList.remove('error');
    } else {
        if (err)  err.classList.add('visible');
        if (zone) zone.classList.add('error');
    }
    return ok;
}

/* ═══════════════════════════════════════════════════════════════════════════
   LISTENERS  PRIX
   ═══════════════════════════════════════════════════════════════════════════ */
document.getElementById('prix').addEventListener('keydown', onPrixKeydown);

document.getElementById('prix').addEventListener('input', function () {
    updateLivePreview();
    updateCompletion();
});

document.getElementById('prix').addEventListener('blur', validatePrix);

/* ═══════════════════════════════════════════════════════════════════════════
   LISTENERS  DATE
   ═══════════════════════════════════════════════════════════════════════════ */
document.getElementById('dateDisponibilite').addEventListener('keydown', onDateKeydown);

document.getElementById('dateDisponibilite').addEventListener('input', onDateInput);

document.getElementById('dateDisponibilite').addEventListener('blur', function () {
    validateDate();
    updateDispoPreview();
    updateCompletion();
});

/* ═══════════════════════════════════════════════════════════════════════════
   AUTRES LISTENERS  (inchangés)
   ═══════════════════════════════════════════════════════════════════════════ */
document.getElementById('nom').addEventListener('input', function () {
    updateCounter('nom', 'nomCounter');
    updateLivePreview();
    updateCompletion();
    validateNom();
});
document.getElementById('nom').addEventListener('blur', validateNom);

document.getElementById('description').addEventListener('input', function () {
    updateCounter('description', 'descCounter');
    updateLivePreview();
    updateCompletion();
});
document.getElementById('description').addEventListener('blur', validateDescription);

document.getElementById('searchInput').addEventListener('input', filterProducts);

document.getElementById('priceMin').addEventListener('input', function () {
    validatePriceInput(this);
    filterProducts();
});
document.getElementById('priceMax').addEventListener('input', function () {
    validatePriceInput(this);
    filterProducts();
});

document.getElementById('categorie').addEventListener('change', function () {
    updateLivePreview();
    updateCompletion();
});

document.getElementById('noteInterne').addEventListener('input', updateCompletion);

document.getElementById('sortSelect').addEventListener('change', sortProducts);
document.getElementById('vbtn-grid').addEventListener('click', () => setView('grid'));
document.getElementById('vbtn-list').addEventListener('click', () => setView('list'));
document.getElementById('tab-actifs').addEventListener('click',   () => switchTab('actifs'));
document.getElementById('tab-archives').addEventListener('click', () => switchTab('archives'));

const kpiLink = document.querySelector('.kpi-link');
if (kpiLink) kpiLink.addEventListener('click', () => switchTab('archives'));

const btnExport = document.getElementById('btnExport');
if (btnExport) btnExport.addEventListener('click', exportCSV);

const btnSaveOrder = document.getElementById('btnSaveOrder');
if (btnSaveOrder) btnSaveOrder.addEventListener('click', saveDragOrder);

const resetButton = document.querySelector('button[type="reset"]');
if (resetButton) resetButton.addEventListener('click', resetForm);

const toggleEpingleEl = document.getElementById('toggleEpingle');
if (toggleEpingleEl) {
    toggleEpingleEl.addEventListener('change', function () {
        document.getElementById('epingleHidden').value = this.checked ? 1 : 0;
    });
}
const toggleArchiveEl = document.getElementById('toggleArchive');
if (toggleArchiveEl) {
    toggleArchiveEl.addEventListener('change', function () {
        document.getElementById('archiveHidden').value = this.checked ? 1 : 0;
    });
}

/* ═══════════════════════════════════════════════════════════════════════════
   TAGS
   ═══════════════════════════════════════════════════════════════════════════ */
let selectedTags = [];

(function initTags() {
    const existing = document.getElementById('caracHidden').value;
    if (!existing.trim()) return;
    existing.split(',').map(t => t.trim()).filter(Boolean).forEach(tag => {
        if (!selectedTags.includes(tag)) {
            selectedTags.push(tag);
            document.querySelectorAll('.tag-btn').forEach(btn => {
                if (btn.textContent.trim() === tag) btn.classList.add('selected');
            });
        }
    });
    renderSelectedTags();
    updateCompletion();
})();

function toggleTag(name, btn) {
    const idx = selectedTags.indexOf(name);
    if (idx === -1) { selectedTags.push(name); btn.classList.add('selected'); }
    else            { selectedTags.splice(idx, 1); btn.classList.remove('selected'); }
    renderSelectedTags();
    updateHiddenCarac();
    updateLivePreview();
    updateCompletion();
    validateTags();
}

function addCustomTag() {
    const input = document.getElementById('tagCustomInput');
    const val   = input.value.trim();
    if (!val || selectedTags.includes(val)) { input.value = ''; return; }
    selectedTags.push(val);
    renderSelectedTags();
    updateHiddenCarac();
    updateLivePreview();
    updateCompletion();
    validateTags();
    input.value = '';
}

document.getElementById('tagCustomInput').addEventListener('keydown', function (e) {
    if (e.key === 'Enter') { e.preventDefault(); addCustomTag(); }
});

function removeTag(name) {
    selectedTags = selectedTags.filter(t => t !== name);
    document.querySelectorAll('.tag-btn').forEach(btn => {
        if (btn.textContent.trim() === name) btn.classList.remove('selected');
    });
    renderSelectedTags();
    updateHiddenCarac();
    updateLivePreview();
    updateCompletion();
    validateTags();
}

function renderSelectedTags() {
    const wrap = document.getElementById('tagsSelected');
    wrap.innerHTML = '';
    selectedTags.forEach(tag => {
        const chip = document.createElement('div');
        chip.className = 'tag-chip';
        chip.innerHTML = `${tag} <button type="button">×</button>`;
        chip.querySelector('button').addEventListener('click', () => removeTag(tag));
        wrap.appendChild(chip);
    });
}

function updateHiddenCarac() {
    document.getElementById('caracHidden').value = selectedTags.join(', ');
}

/* ═══════════════════════════════════════════════════════════════════════════
   COMPLETION BAR
   ═══════════════════════════════════════════════════════════════════════════ */
function updateCompletion() {
    const nom      = document.getElementById('nom').value.trim();
    const desc     = document.getElementById('description').value.trim();
    const prix     = document.getElementById('prix').value;
    const cat      = document.getElementById('categorie').value;
    const dispo    = document.getElementById('dateDisponibilite').value;
    const note     = document.getElementById('noteInterne') ? document.getElementById('noteInterne').value.trim() : '';
    const hasImage = document.getElementById('imgPreviewWrap').classList.contains('visible')
                     || document.querySelector('.current-img-block') !== null;

    const checks = [!!nom, !!desc, !!prix, hasImage, selectedTags.length > 0, !!cat, !!dispo, !!note];
    const filled  = checks.filter(Boolean).length;
    const pct     = Math.round((filled / checks.length) * 100);

    document.getElementById('completionFill').style.width  = pct + '%';
    document.getElementById('completionPct').textContent   = pct + '%';

    const hints = {
        0:   'Start by entering the product name.',
        25:  'Add a description and price.',
        50:  'Set the category and add tags.',
        75:  'Add an image, availability date and internal note.',
        100: '✅ Listing complete — your product is ready to publish!'
    };
    const key = [0, 25, 50, 75, 100].reverse().find(k => pct >= k);
    document.getElementById('completionHint').textContent = hints[key];
}

/* ═══════════════════════════════════════════════════════════════════════════
   APERÇU DATE DE DISPONIBILITÉ
   ═══════════════════════════════════════════════════════════════════════════ */
function updateDispoPreview() {
    const val = document.getElementById('dateDisponibilite').value;
    const el  = document.getElementById('dispoPreview');
    if (!val || val.length < 10) {
        el.textContent = '📅 Available now';
        el.className   = 'dispo-badge nodate';
        return;
    }
    const [year, month, day] = val.split('-').map(Number);
    const d   = new Date(year, month - 1, day);
    const now = new Date();
    now.setHours(0, 0, 0, 0);
    const isRealDate = (
        d.getFullYear() === year &&
        d.getMonth()    === month - 1 &&
        d.getDate()     === day
    );
    if (!isRealDate) {
        el.textContent = '📅 Available now';
        el.className   = 'dispo-badge nodate';
        return;
    }
    const formatted = d.toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' });
    if (d > now) {
        el.textContent = '⏳ Available from ' + formatted;
        el.className   = 'dispo-badge future';
    } else {
        el.textContent = '✅ Available since ' + formatted;
        el.className   = 'dispo-badge available';
    }
}

/* ═══════════════════════════════════════════════════════════════════════════
   COMPTEURS DE CARACTÈRES
   ═══════════════════════════════════════════════════════════════════════════ */
function updateCounter(fieldId, counterId) {
    const field   = document.getElementById(fieldId);
    const counter = document.getElementById(counterId);
    if (!field || !counter) return;
    counter.textContent = field.value.length + ' / ' + (field.maxLength || '');
}

/* ═══════════════════════════════════════════════════════════════════════════
   APERÇU EN DIRECT
   ═══════════════════════════════════════════════════════════════════════════ */
function updateLivePreview() {
    const nom  = document.getElementById('nom').value.trim()              || 'Product name';
    const desc = document.getElementById('description').value.trim()      || 'Description will appear here…';
    const raw  = document.getElementById('prix').value.replace(',', '.');
    const prix = parseFloat(raw) || 0;
    const cat  = document.getElementById('categorie').value;

    document.getElementById('lpName').textContent  = nom;
    document.getElementById('lpDesc').textContent  = desc;
    document.getElementById('lpPrice').textContent = prix.toFixed(2) + ' €';

    const lpCat = document.getElementById('lpCat');
    if (lpCat) lpCat.textContent = cat || '';

    const tagsEl = document.getElementById('lpTags');
    tagsEl.innerHTML = '';
    selectedTags.slice(0, 3).forEach(tag => {
        const t = document.createElement('span');
        t.className   = 'lp-tag';
        t.textContent = '🏷️ ' + tag;
        tagsEl.appendChild(t);
    });
}

/* ═══════════════════════════════════════════════════════════════════════════
   UPLOAD IMAGE
   ═══════════════════════════════════════════════════════════════════════════ */
document.getElementById('fileInput').addEventListener('change', function () {
    const wrap    = document.getElementById('imgPreviewWrap');
    const preview = document.getElementById('imgPreview');
    const lpImg   = document.getElementById('lpImgEl');

    if (this.files && this.files[0]) {
        const isImageValid = validateImage();
        if (!isImageValid) {
            this.value = '';
            wrap.classList.remove('visible');
            lpImg.style.display = 'none';
            updateCompletion();
            return;
        }
        const reader = new FileReader();
        reader.onload = e => {
            preview.src         = e.target.result;
            wrap.classList.add('visible');
            lpImg.src           = e.target.result;
            lpImg.style.display = 'block';
        };
        reader.readAsDataURL(this.files[0]);
        updateCompletion();
    }
});

const uploadZone = document.getElementById('uploadZone');
uploadZone.addEventListener('dragover',  e => { e.preventDefault(); uploadZone.classList.add('dragging'); });
uploadZone.addEventListener('dragleave', ()  => uploadZone.classList.remove('dragging'));
uploadZone.addEventListener('drop',      e  => { e.preventDefault(); uploadZone.classList.remove('dragging'); });

/* ═══════════════════════════════════════════════════════════════════════════
   INITIALISATION DOMContentLoaded
   ═══════════════════════════════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', () => {
    updateCounter('nom', 'nomCounter');
    updateCounter('description', 'descCounter');
    updateCompletion();
    updateLivePreview();
    updateDispoPreview();
    initDragAndDrop();

    /* Placeholders */
    document.getElementById('nom').placeholder               = 'e.g. Premium Hydrating Cream';
    document.getElementById('description').placeholder       = 'Describe your product, its benefits, target audience…';
    document.getElementById('prix').placeholder              = '0.00';
    document.getElementById('dateDisponibilite').placeholder = 'AAAA-MM-JJ (optionnel)';
    document.getElementById('tagCustomInput').placeholder    = 'Add a custom tag…';
    document.getElementById('noteInterne').placeholder       = 'Briefing notes, creator instructions, follow-up remarks…';
    document.getElementById('searchInput').placeholder       = 'Search…';
    document.getElementById('priceMin').placeholder          = 'Min';
    document.getElementById('priceMax').placeholder          = 'Max';

    /* Chips de catégorie */
    document.querySelectorAll('.cat-chip').forEach((chip, index) => {
        const cat = index === 0 ? '' : chip.textContent.trim().toLowerCase();
        chip.addEventListener('click', () => filterByCategory(cat, chip));
    });

    /* Boutons de tags */
    document.querySelectorAll('.tag-btn').forEach(btn => {
        const tag = btn.textContent.trim();
        btn.addEventListener('click', () => toggleTag(tag, btn));
    });

    /* Bouton ajout tag personnalisé */
    document.querySelector('.tags-custom-btn').addEventListener('click', addCustomTag);

    /* Boutons Unpin */
    document.querySelectorAll('.btn-unpin').forEach(btn => {
        const id = btn.closest('.pinned-card').dataset.id;
        btn.addEventListener('click', () => toggleEpingle(id, btn));
    });

    /* Boutons des cartes produit */
    document.querySelectorAll('.btn-quickview').forEach(btn => {
        const card = btn.closest('.product-card');
        const id   = card.dataset.id;
        btn.addEventListener('click', e => openQuickView(e, id));
    });
    document.querySelectorAll('.btn-pin-card').forEach(btn => {
        const card = btn.closest('.product-card');
        const id   = card.dataset.id;
        btn.addEventListener('click', () => toggleEpingle(id, btn));
    });
    document.querySelectorAll('.btn-archive-card').forEach(btn => {
        const card = btn.closest('.product-card');
        const id   = card.dataset.id;
        btn.addEventListener('click', () => toggleArchive(id, btn));
    });
    document.querySelectorAll('.btn-delete-card').forEach(btn => {
        const card = btn.closest('.product-card');
        const id   = card.dataset.id;
        const name = card.dataset.name;
        btn.addEventListener('click', () => openDeleteModal(id, name));
    });

    /* Boutons onglet Archives */
    document.querySelectorAll('.btn-restore').forEach(btn => {
        const card = btn.closest('.archived-card');
        const id   = card.dataset.id;
        btn.addEventListener('click', () => restoreArchive(id, btn));
    });
    document.querySelectorAll('.btn-delete-arch').forEach(btn => {
        const card = btn.closest('.archived-card');
        const id   = card.dataset.id;
        const name = card.dataset.name;
        btn.addEventListener('click', () => openDeleteModal(id, name));
    });

    /* Modals */
    document.getElementById('qvModal').addEventListener('click', closeQVOutside);
    document.querySelector('.qv-close').addEventListener('click', closeQV);
    document.getElementById('deleteModal').addEventListener('click', closeModalOutside);
    document.querySelector('.btn-cancel').addEventListener('click', closeDeleteModal);
});

/* ═══════════════════════════════════════════════════════════════════════════
   SOUMISSION DU FORMULAIRE  — validation globale
   ═══════════════════════════════════════════════════════════════════════════ */
document.getElementById('produitForm').addEventListener('submit', function (e) {
    const nomOk   = validateNom();
    const descOk  = validateDescription();
    const prixOk  = validatePrix();
    const dateOk  = validateDate();
    const tagsOk  = validateTags();
    const imageOk = validateImage();

    if (!nomOk || !descOk || !prixOk || !dateOk || !tagsOk || !imageOk) {
        e.preventDefault();
        const firstError = document.querySelector('.form-input.error, .tags-container.error');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
});

/* ═══════════════════════════════════════════════════════════════════════════
   RESET FORMULAIRE
   ═══════════════════════════════════════════════════════════════════════════ */
function resetForm() {
    selectedTags = [];
    renderSelectedTags();
    updateHiddenCarac();
    document.querySelectorAll('.tag-btn').forEach(b => b.classList.remove('selected'));
    document.getElementById('imgPreviewWrap').classList.remove('visible');

    document.querySelectorAll('.form-input').forEach(el => el.classList.remove('error'));
    document.querySelectorAll('.error-msg').forEach(el => el.classList.remove('visible'));
    document.getElementById('tagsContainer').classList.remove('error');
    document.getElementById('uploadZone').classList.remove('error');

    updateCounter('nom', 'nomCounter');
    updateCounter('description', 'descCounter');
    updateCompletion();
    updateLivePreview();
    updateDispoPreview();
}

/* ═══════════════════════════════════════════════════════════════════════════
   ONGLETS
   ═══════════════════════════════════════════════════════════════════════════ */
function switchTab(tab) {
    ['actifs', 'archives'].forEach(t => {
        document.getElementById('tab-' + t).classList.toggle('active', t === tab);
        document.getElementById('content-' + t).classList.toggle('active', t === tab);
    });
}

/* ═══════════════════════════════════════════════════════════════════════════
   FILTRE PAR CATÉGORIE
   ═══════════════════════════════════════════════════════════════════════════ */
let activeCategory = '';

function filterByCategory(cat, btn) {
    activeCategory = cat.toLowerCase();
    document.querySelectorAll('.cat-chip').forEach(c => c.classList.remove('active'));
    btn.classList.add('active');
    filterProducts();
}

/* ═══════════════════════════════════════════════════════════════════════════
   FILTRE + TRI CATALOGUE
   ═══════════════════════════════════════════════════════════════════════════ */
function filterProducts() {
    const q    = document.getElementById('searchInput').value.toLowerCase().trim();
    const minP = parseFloat(document.getElementById('priceMin').value.replace(',', '.')) || 0;
    const maxP = parseFloat(document.getElementById('priceMax').value.replace(',', '.')) || Infinity;
    const cards = document.querySelectorAll('#productsGrid .product-card');
    let visible = 0;

    cards.forEach(card => {
        const name = card.dataset.name || '';
        const desc = card.dataset.desc || '';
        const prix = parseFloat(card.dataset.prix) || 0;
        const cat  = card.dataset.cat  || '';
        const matchText  = name.includes(q) || desc.includes(q);
        const matchPrice = prix >= minP && prix <= maxP;
        const matchCat   = !activeCategory || cat === activeCategory;
        const show = matchText && matchPrice && matchCat;
        card.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    const noRes = document.getElementById('noResults');
    if (noRes) noRes.style.display = visible === 0 ? '' : 'none';
}

function sortProducts() {
    const mode = document.getElementById('sortSelect').value;
    const grid = document.getElementById('productsGrid');
    if (!grid || !mode) return;
    const cards = Array.from(grid.querySelectorAll('.product-card'));
    cards.sort((a, b) => {
        if (mode === 'nom')       return (a.dataset.name || '').localeCompare(b.dataset.name || '');
        if (mode === 'prix_asc')  return parseFloat(a.dataset.prix) - parseFloat(b.dataset.prix);
        if (mode === 'prix_desc') return parseFloat(b.dataset.prix) - parseFloat(a.dataset.prix);
        if (mode === 'epingle')   return parseInt(b.dataset.epingle || 0) - parseInt(a.dataset.epingle || 0);
        return 0;
    });
    cards.forEach(c => grid.appendChild(c));
}

function setView(mode) {
    const grid = document.getElementById('productsGrid');
    if (!grid) return;
    grid.className = 'products-grid drag-mode' + (mode === 'list' ? ' view-list' : '');
    document.getElementById('vbtn-grid').classList.toggle('active', mode === 'grid');
    document.getElementById('vbtn-list').classList.toggle('active', mode === 'list');
}

/* ═══════════════════════════════════════════════════════════════════════════
   DRAG AND DROP
   ═══════════════════════════════════════════════════════════════════════════ */
let dragSrc = null;

function initDragAndDrop() {
    const grid = document.getElementById('productsGrid');
    if (!grid) return;

    grid.addEventListener('dragstart', e => {
        dragSrc = e.target.closest('.product-card');
        if (dragSrc) { dragSrc.classList.add('dragging'); e.dataTransfer.effectAllowed = 'move'; }
    });
    grid.addEventListener('dragend', () => {
        document.querySelectorAll('.product-card').forEach(c => c.classList.remove('dragging', 'drag-over'));
    });
    grid.addEventListener('dragover', e => {
        e.preventDefault();
        const target = e.target.closest('.product-card');
        if (target && dragSrc && target !== dragSrc) {
            document.querySelectorAll('.product-card').forEach(c => c.classList.remove('drag-over'));
            target.classList.add('drag-over');
        }
    });
    grid.addEventListener('drop', e => {
        e.preventDefault();
        const target = e.target.closest('.product-card');
        if (target && dragSrc && target !== dragSrc) {
            const cards  = Array.from(grid.querySelectorAll('.product-card'));
            const srcIdx = cards.indexOf(dragSrc);
            const tgtIdx = cards.indexOf(target);
            grid.insertBefore(dragSrc, srcIdx < tgtIdx ? target.nextSibling : target);
            const btn = document.getElementById('btnSaveOrder');
            if (btn) btn.style.display = '';
        }
        document.querySelectorAll('.product-card').forEach(c => c.classList.remove('drag-over'));
    });
}

function saveDragOrder() {
    const cards    = Array.from(document.querySelectorAll('#productsGrid .product-card'));
    const ordre    = cards.map(c => c.dataset.id);
    const formData = new FormData();
    formData.append('action', 'reordonner');
    formData.append('ordre', JSON.stringify(ordre));
    fetch('index.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                const btn = document.getElementById('btnSaveOrder');
                if (btn) {
                    btn.textContent = '✅ Order saved';
                    setTimeout(() => { btn.textContent = '💾 Save order'; btn.style.display = 'none'; }, 2000);
                }
            }
        });
}

/* ═══════════════════════════════════════════════════════════════════════════
   AJAX : toggle épingle / archive
   ═══════════════════════════════════════════════════════════════════════════ */
function toggleEpingle(id) {
    const formData = new FormData();
    formData.append('action', 'epingle');
    formData.append('id', id);
    fetch('index.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(() => location.reload());
}

function toggleArchive(id) {
    if (!confirm('Archive this product? It will be hidden from the active catalog.')) return;
    const formData = new FormData();
    formData.append('action', 'archive');
    formData.append('id', id);
    fetch('index.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(() => location.reload());
}

function restoreArchive(id) {
    const formData = new FormData();
    formData.append('action', 'archive');
    formData.append('id', id);
    fetch('index.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(() => location.reload());
}

/* ═══════════════════════════════════════════════════════════════════════════
   QUICK VIEW MODAL
   ═══════════════════════════════════════════════════════════════════════════ */
function openQuickView(e, id) {
    e.stopPropagation();
    const card = document.querySelector(`.product-card[data-id="${id}"]`);
    if (!card) return;

    const name  = card.querySelector('.pcard-name').textContent;
    const desc  = card.querySelector('.pcard-desc').textContent;
    const prix  = card.dataset.prix;
    const img   = card.dataset.img;
    const cat   = card.dataset.cat  || '';
    const tags  = (card.dataset.tags || '').split(',').filter(Boolean);
    const note  = card.dataset.note || '';
    const dispo = card.dataset.dispo || '';

    document.getElementById('qvName').textContent  = name;
    document.getElementById('qvPrice').textContent = parseFloat(prix).toFixed(2) + ' €';
    document.getElementById('qvDesc').textContent  = desc;

    const qvCat = document.getElementById('qvCat');
    if (qvCat) qvCat.textContent = cat ? '📂 ' + cat.charAt(0).toUpperCase() + cat.slice(1) : '';

    const qvDispo = document.getElementById('qvDispo');
    if (dispo) {
        const [y, m, d] = dispo.split('-').map(Number);
        const dateObj   = new Date(y, m - 1, d);
        const formatted = dateObj.toLocaleDateString('en-GB', { day: '2-digit', month: 'long', year: 'numeric' });
        const isFuture  = dateObj > new Date();
        qvDispo.textContent = isFuture ? '⏳ Available from ' + formatted : '✅ Available since ' + formatted;
        qvDispo.className   = 'qv-dispo ' + (isFuture ? 'future' : 'avail');
    } else {
        qvDispo.textContent = '';
    }

    const noteWrap = document.getElementById('qvNoteWrap');
    const noteEl   = document.getElementById('qvNote');
    if (note && noteWrap && noteEl) {
        noteEl.textContent     = note;
        noteWrap.style.display = 'block';
    } else if (noteWrap) {
        noteWrap.style.display = 'none';
    }

    const qvImg = document.getElementById('qvImg');
    if (img) { qvImg.innerHTML = `<img src="${img}" alt="${name}" style="width:100%;height:100%;object-fit:cover;">`; }
    else      { qvImg.innerHTML = '📦'; }

    const qvTags = document.getElementById('qvTags');
    qvTags.innerHTML = tags.map(t => `<span class="qv-tag">🏷️ ${t}</span>`).join('');
    document.getElementById('qvEditBtn').href = `index.php?modifier=${id}`;

    document.getElementById('qvModal').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeQV() { document.getElementById('qvModal').classList.remove('open'); document.body.style.overflow = ''; }
function closeQVOutside(e) { if (e.target === document.getElementById('qvModal')) closeQV(); }

/* ═══════════════════════════════════════════════════════════════════════════
   DELETE MODAL
   ═══════════════════════════════════════════════════════════════════════════ */
function openDeleteModal(id, name) {
    document.getElementById('deleteModalText').textContent = 'Are you sure you want to delete "' + name + '"? This action is permanent.';
    document.getElementById('deleteConfirmBtn').href = 'index.php?supprimer=' + id;
    document.getElementById('deleteModal').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeDeleteModal() { document.getElementById('deleteModal').classList.remove('open'); document.body.style.overflow = ''; }
function closeModalOutside(e) { if (e.target === document.getElementById('deleteModal')) closeDeleteModal(); }

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { closeDeleteModal(); closeQV(); }
});

/* ═══════════════════════════════════════════════════════════════════════════
   EXPORT CSV
   ═══════════════════════════════════════════════════════════════════════════ */
function exportCSV() {
    const cards = document.querySelectorAll('#productsGrid .product-card');
    if (!cards.length) { alert('No products to export.'); return; }

    const rows = [['Name', 'Description', 'Category', 'Price (€)', 'Tags', 'Availability', 'Internal note']];
    cards.forEach(card => {
        const nom   = card.querySelector('.pcard-name')?.textContent.trim() || '';
        const desc  = card.querySelector('.pcard-desc')?.textContent.trim() || '';
        const prix  = parseFloat(card.dataset.prix || 0).toFixed(2);
        const cat   = card.dataset.cat  || '';
        const tags  = (card.dataset.tags || '').replace(/"/g, '""');
        const dispo = card.dataset.dispo || '';
        const note  = (card.dataset.note || '').replace(/"/g, '""');
        rows.push([
            `"${nom.replace(/"/g, '""')}"`,
            `"${desc.replace(/"/g, '""')}"`,
            `"${cat}"`,
            prix,
            `"${tags}"`,
            dispo,
            `"${note}"`
        ]);
    });

    const csv  = rows.map(r => r.join(';')).join('\n');
    const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href = url; a.download = 'my_products_cre8connect.csv'; a.click();
    URL.revokeObjectURL(url);
}

/* ═══════════════════════════════════════════════════════════════════════════
   FLASH AUTO-DISMISS
   ═══════════════════════════════════════════════════════════════════════════ */
const flash = document.getElementById('flashMsg');
if (flash) {
    setTimeout(() => {
        flash.style.transition = 'opacity .4s';
        flash.style.opacity    = '0';
        setTimeout(() => flash.remove(), 400);
    }, 4000);
}

<?php if ($editProduit): ?>
document.getElementById('formAnchor').scrollIntoView({ behavior: 'smooth', block: 'start' });
<?php endif; ?>
</script>
</body>
</html>