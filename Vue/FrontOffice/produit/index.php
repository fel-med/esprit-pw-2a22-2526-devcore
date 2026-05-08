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

        /* ===== ADDED FEATURE: DARK MODE VARIABLES ===== */
        body.dark-mode {
            --primary:        #7c6fff;
            --primary-light:  #2a2660;
            --primary-hover:  #6357e8;
            --primary-glow:   rgba(124,111,255,0.2);
            --primary-border: rgba(124,111,255,0.3);
            --text-main:      #f0eeff;
            --text-sub:       #a8a4c0;
            --text-dim:       #6b6880;
            --border:         #2e2b45;
            --bg:             #12111e;
            --white:          #1c1a2e;
            --danger:         #f87191;
            --danger-light:   #2a1520;
            --danger-border:  rgba(248,113,145,0.25);
            --success:        #34d399;
            --success-light:  #0d2e22;
            --success-border: rgba(52,211,153,0.25);
            --warning:        #fbbf24;
            --warning-light:  #271d08;
            --warning-border: rgba(251,191,36,0.25);
            --archive:        #94a3b8;
            --archive-light:  #1e293b;
            --pin:            #fbbf24;
            --pin-light:      #271d08;
            --card-shadow:    0 1px 3px rgba(0,0,0,0.3), 0 4px 16px rgba(0,0,0,0.2);
            --card-shadow-hover: 0 8px 32px rgba(124,111,255,0.2);
        }
        /* ===== END DARK MODE VARIABLES ===== */

        body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text-main); min-height: 100vh; transition: background .3s, color .3s; }

        /* ── NAV ── */
        nav { background: var(--white); border-bottom: 1px solid var(--border); padding: 0 48px; height: var(--nav-h); display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 200; box-shadow: 0 1px 0 var(--border), 0 2px 12px rgba(15,14,26,0.04); transition: background .3s, border-color .3s; }
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

        /* ===== ADDED FEATURE: THEME TOGGLE BUTTON ===== */
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
        /* ===== END THEME TOGGLE ===== */

        /* ===== ADDED FEATURE: LANGUAGE SWITCHER ===== */
        .lang-switcher { display: flex; gap: 4px; align-items: center; }
        .lang-btn {
            background: var(--bg);
            border: 1.5px solid var(--border);
            border-radius: 7px;
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            color: var(--text-sub);
            font-family: 'DM Sans', sans-serif;
            transition: all .18s;
        }
        .lang-btn:hover, .lang-btn.active { background: var(--primary-light); color: var(--primary); border-color: var(--primary-border); }
        /* ===== END LANGUAGE SWITCHER ===== */

        /* ── PAGE WRAPPER ── */
        .page-wrapper { max-width: 1200px; margin: 0 auto; padding: 32px 24px 80px; }

        /* ── PAGE HEADER / HERO BANNER ── */
        .page-header {
            background: linear-gradient(130deg, #ece9ff 0%, #eeeaff 45%, #e4ecfd 100%);
            border-radius: 20px;
            padding: 40px 52px;
            margin-bottom: 32px;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 200px;
            border: 1px solid rgba(91,79,255,0.08);
            box-shadow: 0 2px 24px rgba(91,79,255,0.07);
        }
        .page-header::before {
            content: '';
            position: absolute;
            top: -60px; right: -60px;
            width: 260px; height: 260px;
            background: radial-gradient(circle, rgba(91,79,255,0.12) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }
        .page-header-left { flex: 1; z-index: 1; }
        .page-header-left h1 {
            font-family: 'Fraunces', serif;
            font-size: 34px;
            font-weight: 900;
            color: var(--text-main);
            letter-spacing: -1px;
            line-height: 1.15;
            margin-bottom: 10px;
        }
        .page-header-left p { color: var(--text-sub); font-size: 14px; line-height: 1.6; max-width: 480px; margin-bottom: 22px; }
        .page-header-stats { display: flex; gap: 24px; align-items: center; flex-wrap: wrap; }
        .page-header-stat { display: flex; align-items: center; gap: 8px; }
        .page-header-stat .stat-icon {
            width: 28px; height: 28px;
            background: var(--primary);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px;
            color: #fff;
            font-weight: 800;
            font-family: 'Fraunces', serif;
        }
        .page-header-stat .stat-text { font-size: 13px; font-weight: 600; color: var(--text-main); }
        .page-header-illus {
            flex-shrink: 0;
            width: 130px; height: 130px;
            display: flex; align-items: center; justify-content: center;
            font-size: 80px;
            opacity: 0.55;
            z-index: 1;
            filter: drop-shadow(0 8px 24px rgba(91,79,255,0.18));
            user-select: none;
        }
        .page-header-actions { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-top: 6px; }
        .btn-add-product {
            display: inline-flex; align-items: center; gap: 8px;
            background: var(--primary); color: #fff; border: none;
            border-radius: var(--radius-sm); padding: 11px 20px;
            font-size: 13.5px; font-weight: 700; font-family: 'DM Sans', sans-serif;
            cursor: pointer; text-decoration: none;
            transition: background .2s, transform .15s;
            box-shadow: 0 3px 12px var(--primary-glow);
        }
        .btn-add-product:hover { background: var(--primary-hover); transform: translateY(-1px); }
        .btn-export {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(255,255,255,0.75); color: var(--text-sub);
            border: 1.5px solid rgba(255,255,255,0.6);
            border-radius: var(--radius-sm); padding: 10px 16px;
            font-size: 13px; font-weight: 600; font-family: 'DM Sans', sans-serif;
            cursor: pointer; transition: all .2s; text-decoration: none;
            backdrop-filter: blur(6px);
        }
        .btn-export:hover { border-color: var(--primary); color: var(--primary); background: rgba(255,255,255,0.95); }

        /* ── FLASH ── */
        .flash { padding: 13px 18px; border-radius: var(--radius-sm); font-size: 14px; font-weight: 600; margin-bottom: 24px; display: flex; align-items: center; gap: 10px; animation: slideDown .3s ease; }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .flash.success { background: var(--success-light); color: #065f46; border: 1px solid var(--success-border); }
        .flash.delete  { background: var(--danger-light);  color: #9f1239; border: 1px solid var(--danger-border); }

        /* ── KPI STRIP ── */
        .kpi-strip { display: grid; grid-template-columns: repeat(5, 1fr); gap: 14px; margin-bottom: 32px; }
        .kpi-card { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius); padding: 18px 20px; position: relative; overflow: hidden; transition: box-shadow .2s, background .3s, border-color .3s; }
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
        .rappels-section { background: var(--warning-light); border: 1px solid var(--warning-border); border-radius: var(--radius); padding: 18px 22px; margin-bottom: 28px; transition: background .3s; }
        .rappels-header { display: flex; align-items: center; gap: 8px; margin-bottom: 12px; }
        .rappels-header h3 { font-family: 'Fraunces', serif; font-size: 14px; font-weight: 800; color: #92400e; }
        .rappels-header .icon { font-size: 16px; }
        .rappels-list { display: flex; flex-direction: column; gap: 8px; }
        .rappel-item { background: var(--white); border-radius: var(--radius-sm); padding: 10px 14px; display: flex; align-items: center; gap: 12px; border: 1px solid var(--warning-border); transition: background .3s; }
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
        .top-card { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 12px; display: flex; align-items: center; gap: 10px; position: relative; transition: box-shadow .2s, background .3s; }
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
        .recent-card { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 14px; display: flex; align-items: center; gap: 12px; transition: box-shadow .2s, border-color .2s, background .3s; }
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
        .pinned-card { background: linear-gradient(135deg, var(--warning-light), var(--warning-light)); border: 1.5px solid #fcd34d; border-radius: var(--radius-sm); padding: 12px; position: relative; transition: box-shadow .2s; }
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
        .form-card { background: var(--white); border-radius: var(--radius); padding: 32px; box-shadow: var(--card-shadow); border: 1px solid var(--border); transition: border-color .2s, background .3s; }
        .form-card.edit-mode { border: 2px solid var(--primary); }
        .form-card-header { margin-bottom: 24px; }
        .form-card-header h2 { font-family: 'Fraunces', serif; font-size: 20px; font-weight: 800; color: var(--text-main); letter-spacing: -0.4px; }
        .form-card-header p { font-size: 13px; color: var(--text-sub); margin-top: 4px; }
        .edit-banner { background: linear-gradient(135deg, var(--primary), #7c3aed); color: #fff; border-radius: 8px; padding: 10px 16px; font-size: 13px; font-weight: 600; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; }
        .edit-banner a { color: rgba(255,255,255,0.75); text-decoration: none; font-size: 12px; }
        .edit-banner a:hover { color: #fff; }

        /* ── COMPLETION BAR ── */
        .completion-wrap { background: var(--bg); border-radius: var(--radius-sm); padding: 14px 16px; margin-bottom: 20px; border: 1px solid var(--border); transition: background .3s; }
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
        .form-input { width: 100%; padding: 11px 14px; border: 1.5px solid var(--border); border-radius: var(--radius-sm); font-size: 14px; font-family: 'DM Sans', sans-serif; color: var(--text-main); background: var(--bg); transition: border-color .2s, box-shadow .2s, background .2s; outline: none; -webkit-appearance: none; }
        .form-input::placeholder { color: var(--text-dim); }
        .form-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-glow); background: var(--white); }
        .form-input.error { border-color: var(--danger); box-shadow: 0 0 0 3px rgba(244,63,94,0.1); }
        textarea.form-input { resize: vertical; min-height: 100px; }
        select.form-input { cursor: pointer; }

        /* ── ERROR MESSAGES ── */
        .error-msg { font-size: 11.5px; color: var(--danger); font-weight: 600; margin-top: 5px; display: none; align-items: center; gap: 5px; animation: slideDown .2s ease; }
        .error-msg.visible { display: flex; }
        .error-msg::before { content: '⚠'; font-size: 11px; }
        .field-hint { font-size: 11px; color: var(--text-dim); margin-top: 4px; display: flex; align-items: center; gap: 4px; }

        .input-with-prefix { position: relative; }
        .input-with-prefix .prefix { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); font-size: 14px; font-weight: 700; color: var(--text-sub); pointer-events: none; }
        .input-with-prefix .form-input { padding-left: 30px; }

        /* ── INTERNAL NOTE ── */
        .note-interne-wrap { border: 1.5px dashed #fcd34d; border-radius: var(--radius-sm); background: var(--warning-light); padding: 12px; transition: background .3s; }
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
        .toggle-item { display: flex; align-items: center; gap: 10px; padding: 10px 14px; background: var(--bg); border-radius: var(--radius-sm); border: 1px solid var(--border); flex: 1; transition: background .3s; }
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
        .tags-container { border: 1.5px solid var(--border); border-radius: var(--radius-sm); padding: 12px; background: var(--bg); transition: border-color .2s, box-shadow .2s; }
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
        .tags-custom-input { flex: 1; padding: 7px 12px; border: 1.5px solid var(--border); border-radius: 20px; font-size: 12px; font-family: 'DM Sans', sans-serif; outline: none; transition: border-color .2s; background: var(--white); color: var(--text-main); }
        .tags-custom-input:focus { border-color: var(--primary); }
        .tags-custom-btn { background: var(--primary-light); color: var(--primary); border: none; border-radius: 20px; padding: 7px 14px; font-size: 12px; font-weight: 700; cursor: pointer; font-family: 'DM Sans', sans-serif; transition: background .18s; }
        .tags-custom-btn:hover { background: #ddddf8; }

        /* ── UPLOAD ── */
        .upload-zone { border: 2px dashed var(--border); border-radius: var(--radius-sm); padding: 22px 16px; text-align: center; background: var(--bg); cursor: pointer; transition: all .2s; position: relative; overflow: hidden; }
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
        .lp-body { padding: 12px 14px; background: var(--white); }
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
        .tips-card { background: var(--white); border-radius: var(--radius); padding: 22px; border: 1px solid var(--border); box-shadow: var(--card-shadow); position: sticky; top: calc(var(--nav-h) + 20px); transition: background .3s; }
        .tips-card-header { display: flex; align-items: center; gap: 10px; margin-bottom: 18px; padding-bottom: 14px; border-bottom: 1px solid var(--border); }
        .tips-card-header .icon { width: 32px; height: 32px; background: var(--warning-light); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 16px; }
        .tips-card-header h3 { font-family: 'Fraunces', serif; font-size: 14px; font-weight: 800; }
        .tip-item { display: flex; gap: 10px; margin-bottom: 14px; }
        .tip-item:last-child { margin-bottom: 0; }
        .tip-dot { width: 20px; height: 20px; border-radius: 50%; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 800; flex-shrink: 0; margin-top: 1px; }
        .tip-item strong { display: block; font-size: 12.5px; font-weight: 700; margin-bottom: 2px; }
        .tip-item p { font-size: 12px; color: var(--text-sub); line-height: 1.5; }

        /* ── SECTION HEADER ── */
        .section-head {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 14px; gap: 12px; flex-wrap: wrap;
        }
        .section-head-left { display: flex; align-items: center; gap: 12px; }
        .section-head-left h2 {
            font-family: 'Fraunces', serif; font-size: 22px;
            font-weight: 900; letter-spacing: -0.5px; color: var(--text-main);
        }
        .count-pill {
            background: var(--primary-light); color: var(--primary);
            border-radius: 20px; padding: 4px 13px; font-size: 12px; font-weight: 700;
        }
        .tools-bar { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

        /* Search */
        .search-wrap { position: relative; }
        .search-wrap svg { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-dim); pointer-events: none; width: 15px; height: 15px; }
        .search-input {
            background: var(--white); border: 1.5px solid var(--border);
            border-radius: 10px; padding: 9px 14px 9px 36px;
            font-size: 13px; font-family: 'DM Sans', sans-serif;
            color: var(--text-main); width: 220px; outline: none;
            transition: border-color .2s, box-shadow .2s, background .3s;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        }
        .search-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-glow); }
        .search-input::placeholder { color: var(--text-dim); }

        /* Sort */
        .sort-select {
            background: var(--white); border: 1.5px solid var(--border);
            border-radius: 10px; padding: 9px 14px;
            font-size: 13px; font-family: 'DM Sans', sans-serif;
            outline: none; cursor: pointer; color: var(--text-sub);
            transition: background .3s, border-color .2s;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        }
        .sort-select:focus { border-color: var(--primary); }

        /* View toggle */
        .view-toggle {
            display: flex; border: 1.5px solid var(--border);
            border-radius: 10px; overflow: hidden; background: var(--white);
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        }
        .view-btn { background: transparent; border: none; padding: 8px 11px; cursor: pointer; color: var(--text-dim); display: flex; align-items: center; transition: background .15s, color .15s; }
        .view-btn.active, .view-btn:hover { background: var(--primary-light); color: var(--primary); }
        .view-btn svg { width: 16px; height: 16px; }

        /* ── CATEGORY FILTER CARD ── */
        .cat-filter-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 14px 20px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        }
        .cat-filter-label { font-size: 13px; font-weight: 600; color: var(--text-sub); margin-right: 4px; white-space: nowrap; }
        .cat-pill {
            display: inline-flex; align-items: center;
            background: var(--bg); color: var(--text-sub);
            border: 1.5px solid var(--border); border-radius: 20px;
            padding: 6px 16px; font-size: 13px; font-weight: 600;
            cursor: pointer; font-family: 'DM Sans', sans-serif;
            transition: all .18s;
        }
        .cat-pill:hover { border-color: var(--primary); color: var(--primary); background: var(--primary-light); }
        .cat-pill.active { background: var(--primary); color: #fff; border-color: var(--primary); box-shadow: 0 2px 8px var(--primary-glow); }

        /* ── PRICE FILTER CARD ── */
        .price-filter-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 14px 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        }
        .price-filter { display: flex; align-items: center; gap: 10px; }
        .price-filter-label { font-size: 13px; font-weight: 600; color: var(--text-sub); white-space: nowrap; }
        .price-range-wrap { display: flex; align-items: center; gap: 8px; }
        .price-input {
            width: 80px; padding: 7px 12px;
            border: 1.5px solid var(--border); border-radius: 8px;
            font-size: 13px; font-family: 'DM Sans', sans-serif;
            outline: none; transition: border-color .2s;
            background: var(--bg); color: var(--text-main);
        }
        .price-input:focus { border-color: var(--primary); background: var(--white); }
        .price-input::placeholder { color: var(--text-dim); }
        .price-sep { font-size: 13px; color: var(--text-dim); font-weight: 500; }
        .price-catalog-hint { font-size: 12px; color: var(--text-dim); font-weight: 500; margin-left: 4px; }
        .btn-reset-filters {
            margin-left: auto; background: transparent; color: var(--text-sub);
            border: none; font-size: 13px; font-weight: 600;
            font-family: 'DM Sans', sans-serif; cursor: pointer;
            padding: 6px 2px; transition: color .18s;
        }
        .btn-reset-filters:hover { color: var(--primary); }

        /* ── DRAG HINT ── */
        .drag-hint { display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--text-sub); font-weight: 600; background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 6px 12px; }
        .drag-hint svg { width: 13px; height: 13px; color: var(--text-dim); }

        /* ── PRODUCT GRID ── */
        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; margin-bottom: 28px; }
        .products-grid.view-list { grid-template-columns: 1fr; }
        .products-grid.drag-mode .product-card { cursor: grab; }
        .product-card.drag-over { border: 2px dashed var(--primary); background: var(--primary-light); }
        .product-card.dragging { opacity: 0.4; }

        /* ── PRODUCT CARD ── */
        .product-card {
            background: var(--white);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(15,14,26,0.05), 0 0 0 1px var(--border);
            border: 1px solid var(--border);
            transition: transform .22s, box-shadow .22s, border-color .22s, background .3s;
            display: flex; flex-direction: column; position: relative;
        }
        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 36px rgba(91,79,255,0.13);
            border-color: var(--primary-border);
        }
        .view-list .product-card { flex-direction: row; }
        .product-card.is-pinned { border: 1.5px solid #fcd34d; box-shadow: 0 2px 14px rgba(245,158,11,0.12); }

        .badge-epingle { position: absolute; top: 12px; right: 12px; z-index: 10; background: var(--pin); color: #fff; border-radius: 20px; padding: 4px 11px; font-size: 11px; font-weight: 700; box-shadow: 0 2px 6px rgba(245,158,11,0.3); }
        .badge-dispo { position: absolute; bottom: 42px; left: 12px; z-index: 10; background: rgba(14,163,112,0.92); color: #fff; border-radius: 20px; padding: 3px 10px; font-size: 10px; font-weight: 700; }
        .badge-dispo.future { background: rgba(245,158,11,0.92); }

        .pcard-img {
            position: relative; height: 200px; overflow: hidden;
            background: linear-gradient(135deg, #f0eeff 0%, #ece9ff 100%);
            flex-shrink: 0;
        }
        .view-list .pcard-img { width: 200px; height: auto; min-height: 160px; }
        .pcard-img img { width: 100%; height: 100%; object-fit: cover; transition: transform .4s ease; display: block; }
        .product-card:hover .pcard-img img { transform: scale(1.06); }
        .pcard-img-empty {
            width: 100%; height: 100%;
            display: flex; align-items: center; justify-content: center;
            font-size: 44px;
            background: linear-gradient(135deg, #ede9ff, #f5f3ff);
            color: #c4bde8;
        }
        .pcard-price-badge {
            position: absolute; bottom: 12px; right: 12px;
            background: var(--primary); color: #fff;
            border-radius: 20px; padding: 5px 14px;
            font-size: 13px; font-weight: 800; font-family: 'Fraunces', serif;
            box-shadow: 0 3px 10px rgba(91,79,255,0.35);
        }
        .pcard-quickview {
            position: absolute; inset: 0;
            background: rgba(15,14,26,0.62);
            display: flex; align-items: center; justify-content: center;
            opacity: 0; transition: opacity .2s;
        }
        .product-card:hover .pcard-quickview { opacity: 1; }
        .btn-quickview {
            background: rgba(255,255,255,0.96); color: var(--text-main);
            border: none; border-radius: 10px; padding: 9px 18px;
            font-size: 12px; font-weight: 700; cursor: pointer;
            font-family: 'DM Sans', sans-serif;
            display: flex; align-items: center; gap: 6px;
            transition: background .18s; box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }
        .btn-quickview:hover { background: var(--primary-light); color: var(--primary); }
        .btn-quickview svg { width: 14px; height: 14px; }

        .pcard-body { padding: 18px 20px; display: flex; flex-direction: column; flex: 1; }
        .view-list .pcard-body { padding: 20px 24px; }

        .pcard-cat {
            font-size: 11px; font-weight: 700; color: var(--text-sub);
            text-transform: uppercase; letter-spacing: .06em; margin-bottom: 6px;
            display: flex; align-items: center; gap: 5px;
        }
        .pcard-cat .cat-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--primary); opacity: 0.45; }
        .pcard-name {
            font-family: 'Fraunces', serif; font-size: 16px; font-weight: 900;
            color: var(--text-main); line-height: 1.25; margin-bottom: 7px;
            letter-spacing: -0.3px;
        }
        .pcard-desc {
            font-size: 13px; color: var(--text-sub); line-height: 1.65;
            margin-bottom: 12px; flex: 1;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
        }
        .pcard-tags { display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 12px; }
        .pcard-tag {
            display: inline-flex; align-items: center; gap: 3px;
            font-size: 11px; color: var(--primary); background: var(--primary-light);
            border-radius: 20px; padding: 3px 10px; font-weight: 600;
        }
        .pcard-dispo-row { font-size: 11px; color: var(--text-sub); margin-bottom: 6px; display: flex; align-items: center; gap: 5px; }
        .pcard-dispo-row .dot-avail  { width: 6px; height: 6px; border-radius: 50%; background: var(--success); flex-shrink: 0; }
        .pcard-dispo-row .dot-future { width: 6px; height: 6px; border-radius: 50%; background: var(--warning); flex-shrink: 0; }
        .pcard-note-row { font-size: 11px; color: #92400e; background: var(--warning-light); border: 1px solid #fcd34d; border-radius: 8px; padding: 6px 10px; margin-bottom: 10px; display: flex; align-items: flex-start; gap: 5px; }
        .pcard-note-row .note-label { font-weight: 700; flex-shrink: 0; }
        .pcard-note-text { overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }

        .pcard-actions {
            display: flex; gap: 7px; padding-top: 14px;
            border-top: 1px solid var(--border); flex-wrap: wrap;
            margin-top: auto;
        }
        .btn-edit-card {
            flex: 1; min-width: 80px; padding: 8px 10px;
            background: var(--primary-light); color: var(--primary);
            border: none; border-radius: 8px; font-size: 12px; font-weight: 700;
            font-family: 'DM Sans', sans-serif; cursor: pointer;
            text-align: center; text-decoration: none;
            display: inline-flex; align-items: center; justify-content: center; gap: 5px;
            transition: background .18s, transform .15s;
        }
        .btn-edit-card:hover { background: #ddddf8; transform: translateY(-1px); }
        .btn-delete-card {
            flex: 0 0 auto; padding: 8px 12px;
            background: var(--danger-light); color: var(--danger);
            border: none; border-radius: 8px; font-size: 12px; font-weight: 700;
            cursor: pointer; font-family: 'DM Sans', sans-serif;
            display: inline-flex; align-items: center; justify-content: center;
            transition: background .18s;
        }
        .btn-delete-card:hover { background: #fecdd3; }
        .btn-pin-card {
            flex: 0 0 auto; padding: 8px 12px;
            background: var(--pin-light); color: var(--pin);
            border: 1px solid var(--warning-border); border-radius: 8px;
            font-size: 12px; font-weight: 700; cursor: pointer;
            font-family: 'DM Sans', sans-serif;
            display: inline-flex; align-items: center; gap: 4px;
            transition: background .18s;
        }
        .btn-pin-card:hover { background: #fde68a; }
        .btn-pin-card.pinned { background: var(--pin); color: #fff; border-color: var(--pin); }
        .btn-archive-card {
            flex: 0 0 auto; padding: 8px 12px;
            background: var(--archive-light); color: var(--archive);
            border: 1px solid #e2e8f0; border-radius: 8px; font-size: 12px; font-weight: 700;
            cursor: pointer; font-family: 'DM Sans', sans-serif;
            display: inline-flex; align-items: center; gap: 4px;
            transition: background .18s;
        }
        .btn-archive-card:hover { background: #e2e8f0; }

        /* ── EMPTY STATE ── */
        .empty-state { text-align: center; padding: 60px 24px; background: var(--white); border-radius: var(--radius); border: 1px solid var(--border); box-shadow: var(--card-shadow); }
        .empty-state .empty-icon { font-size: 52px; margin-bottom: 16px; display: block; }
        .empty-state h3 { font-family: 'Fraunces', serif; font-size: 20px; font-weight: 800; margin-bottom: 8px; }
        .empty-state p { font-size: 14px; color: var(--text-sub); }

        /* ── QUICK VIEW MODAL ── */
        .qv-overlay { display: none; position: fixed; inset: 0; background: rgba(15,14,26,0.5); z-index: 300; align-items: center; justify-content: center; padding: 20px; }
        .qv-overlay.open { display: flex; }
        .qv-box { background: var(--white); border-radius: var(--radius); width: 640px; max-width: 100%; overflow: hidden; box-shadow: 0 20px 60px rgba(15,14,26,0.2); animation: mslide .22s ease; display: flex; transition: background .3s; }
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
        .qv-note { background: var(--warning-light); border: 1px solid #fcd34d; border-radius: 8px; padding: 10px 14px; margin-bottom: 16px; }
        .qv-note-label { font-size: 11px; font-weight: 800; color: #92400e; margin-bottom: 5px; }
        .qv-note-text { font-size: 13px; color: #78350f; line-height: 1.6; }
        .qv-tags { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 20px; }
        .qv-tag { font-size: 12px; font-weight: 600; color: var(--primary); background: var(--primary-light); border-radius: 6px; padding: 5px 10px; }
        .qv-actions { display: flex; gap: 10px; flex-wrap: wrap; }

        /* ── DELETE MODAL ── */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(15,14,26,0.5); z-index: 300; align-items: center; justify-content: center; padding: 20px; }
        .modal-overlay.open { display: flex; }
        .modal-box { background: var(--white); border-radius: var(--radius); padding: 28px; width: 400px; max-width: 100%; box-shadow: 0 20px 60px rgba(15,14,26,0.2); animation: mslide .22s ease; transition: background .3s; }
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
        .archived-card { background: var(--white); border: 1px dashed var(--border); border-radius: var(--radius-sm); padding: 14px; display: flex; align-items: center; gap: 12px; opacity: 0.8; transition: opacity .2s, background .3s; }
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

        /* ===== ADDED FEATURE: ADVANCED FILTERS PANEL ===== */
        .adv-filters-panel {
            background: var(--white);
            border: 1.5px solid var(--border);
            border-radius: var(--radius);
            padding: 16px 20px;
            margin-bottom: 18px;
            display: none;
            gap: 14px;
            flex-wrap: wrap;
            align-items: flex-end;
            box-shadow: var(--card-shadow);
            transition: background .3s;
        }
        .adv-filters-panel.open { display: flex; }
        .adv-filter-group { display: flex; flex-direction: column; gap: 4px; }
        .adv-filter-group label { font-size: 11.5px; font-weight: 700; color: var(--text-sub); }
        .adv-filter-select {
            background: var(--bg);
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 8px 12px;
            font-size: 12px;
            font-family: 'DM Sans', sans-serif;
            outline: none;
            cursor: pointer;
            color: var(--text-main);
            min-width: 140px;
            transition: border-color .2s, background .3s;
        }
        .adv-filter-select:focus { border-color: var(--primary); }
        .adv-filter-toggle-group { display: flex; gap: 6px; }
        .adv-toggle-btn {
            padding: 6px 12px;
            border-radius: 20px;
            border: 1.5px solid var(--border);
            background: var(--white);
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            color: var(--text-sub);
            font-family: 'DM Sans', sans-serif;
            transition: all .18s;
        }
        .adv-toggle-btn:hover { border-color: var(--primary); color: var(--primary); }
        .adv-toggle-btn.active { background: var(--primary); color: #fff; border-color: var(--primary); }
        .adv-toggle-btn.active-pin { background: var(--pin); color: #fff; border-color: var(--pin); }
        .btn-adv-filters {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--bg);
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 8px 14px;
            font-size: 12.5px;
            font-weight: 600;
            cursor: pointer;
            color: var(--text-sub);
            font-family: 'DM Sans', sans-serif;
            transition: all .2s;
        }
        .btn-adv-filters:hover, .btn-adv-filters.open { border-color: var(--primary); color: var(--primary); background: var(--primary-light); }
        .btn-reset-filters {
            padding: 8px 14px;
            border-radius: var(--radius-sm);
            border: 1.5px solid var(--danger-border);
            background: var(--danger-light);
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            color: var(--danger);
            font-family: 'DM Sans', sans-serif;
            transition: all .18s;
        }
        .btn-reset-filters:hover { background: #fecdd3; }
        .active-filters-bar {
            display: none;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 14px;
            font-size: 12px;
            color: var(--text-sub);
        }
        .active-filters-bar.has-filters { display: flex; }
        .active-filter-chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: var(--primary-light);
            color: var(--primary);
            border-radius: 20px;
            padding: 3px 10px;
            font-size: 11.5px;
            font-weight: 700;
        }
        .active-filter-chip button {
            background: none;
            border: none;
            color: var(--primary);
            cursor: pointer;
            font-size: 13px;
            line-height: 1;
            padding: 0;
            opacity: .7;
        }
        .active-filter-chip button:hover { opacity: 1; }
        /* ===== END ADVANCED FILTERS ===== */

        /* ===== ADDED FEATURE: PAGINATION ===== */
        .pagination-bar {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin: 24px 0 8px;
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
            transition: background .3s;
        }
        /* ===== END PAGINATION ===== */

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
        <li><a href="#" data-i18n="nav_dashboard">Dashboard</a></li>
        <li><a href="#" data-i18n="nav_offers">My Offers</a></li>
        <li><a href="#" class="active" data-i18n="nav_products">My Products</a></li>
        <li><a href="#" data-i18n="nav_campaigns">Campaigns</a></li>
        <li><a href="#" data-i18n="nav_profile">My Profile</a></li>
    </ul>
    <div class="nav-right">
        <!-- ===== ADDED FEATURE: LANGUAGE SWITCHER ===== -->
        <div class="lang-switcher">
            <button class="lang-btn active" onclick="setLang('en')" id="btn-en">EN</button>
            <button class="lang-btn" onclick="setLang('fr')" id="btn-fr">FR</button>
        </div>
        <!-- ===== END LANGUAGE SWITCHER ===== -->

        <!-- ===== ADDED FEATURE: DARK MODE TOGGLE ===== -->
        <button class="theme-toggle" id="themeToggle" onclick="toggleTheme()">
            <span id="themeIcon">🌙</span>
            <span id="themeLabel" data-i18n="theme_dark">Dark mode</span>
        </button>
        <!-- ===== END DARK MODE TOGGLE ===== -->

        <span class="nav-badge" data-i18n="role_brand">Brand</span>
        <div class="nav-avatar">B</div>
    </div>
</nav>

<div class="page-wrapper">

    <!-- PAGE HEADER / HERO BANNER -->
    <div class="page-header">
        <div class="page-header-left">
            <h1 data-i18n="page_title">My Products</h1>
            <p data-i18n="page_subtitle">Manage, organize and promote your products to content creators.</p>
            <div class="page-header-stats">
                <div class="page-header-stat">
                    <div class="stat-icon"><?= $totalProduits ?></div>
                    <span class="stat-text" data-i18n="stat_products_available">Products available</span>
                </div>
                <div class="page-header-stat">
                    <div class="stat-icon"><?= count($categories) ?></div>
                    <span class="stat-text" data-i18n="stat_categories">Categories</span>
                </div>
            </div>
            <div class="page-header-actions">
                <button type="button" id="btnExport" class="btn-export">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span data-i18n="btn_export">Export CSV</span>
                </button>
                <a href="#formAnchor" class="btn-add-product" id="btnShowForm">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    <span data-i18n="btn_add_product">Add a product</span>
                </a>
            </div>
        </div>
        <div class="page-header-illus">🛍️</div>
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
            <div class="kpi-label" data-i18n="kpi_active">Active products</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon">💶</div>
            <div class="kpi-value"><?= number_format($prixMoyen, 0, '.', ' ') ?> €</div>
            <div class="kpi-label" data-i18n="kpi_avg_price">Avg. price</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon">📌</div>
            <div class="kpi-value"><?= count($epingles) ?></div>
            <div class="kpi-label" data-i18n="kpi_pinned">Pinned</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon">🖼️</div>
            <div class="kpi-value"><?= $sanImage ?></div>
            <div class="kpi-label" data-i18n="kpi_no_image">Without image</div>
            <?php if ($sanImage > 0): ?>
                <div class="kpi-alert" data-i18n="kpi_complete_listings">⚠️ Complete these listings</div>
            <?php endif; ?>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon">🗄️</div>
            <div class="kpi-value"><?= $totalArchives ?></div>
            <div class="kpi-label" data-i18n="kpi_archived">Archived</div>
            <?php if ($totalArchives > 0): ?>
                <div class="kpi-link" data-i18n="kpi_view_archives">View archives →</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- COMPLETION REMINDERS -->
    <?php if (!empty($rappels)): ?>
    <div class="rappels-section" id="rappelsSection">
        <div class="rappels-header">
            <span class="icon">⚠️</span>
            <h3 data-i18n="reminders_title">Completion reminders</h3>
        </div>
        <div class="rappels-list">
            <?php foreach ($rappels as $r): ?>
            <div class="rappel-item">
                <div class="rappel-name"><?= htmlspecialchars($r['nom']) ?></div>
                <div class="rappel-bar-wrap">
                    <div class="rappel-pct"><?= $r['pct'] ?>%</div>
                    <div class="rappel-bar"><div class="rappel-bar-fill" style="width:<?= $r['pct'] ?>%"></div></div>
                </div>
                <div class="rappel-missing"><span data-i18n="missing_label">Missing:</span> <?= implode(', ', $r['manquants']) ?></div>
                <a href="index.php?modifier=<?= $r['id'] ?>" class="rappel-btn" data-i18n="btn_complete">Complete →</a>
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
            <h2 data-i18n="top_products_title">Top products</h2>
            <span class="top-badge" data-i18n="top_products_badge">Most requested</span>
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
            <h2 data-i18n="recent_title">Recently added</h2>
            <span class="count-pill" style="font-size:11px;"><?= count($produitsRecents) ?> <span data-i18n="recent_latest">latest</span></span>
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
                    <a href="index.php?modifier=<?= $r['idProduit'] ?>" class="recent-btn edit">✏️ <span data-i18n="btn_edit">Edit</span></a>
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
                <h2><?= $editProduit ? '✏️ <span data-i18n="form_edit_title">Edit product</span>' : '➕ <span data-i18n="form_add_title">Add a product</span>' ?></h2>
                <p><?= $editProduit ? '<span data-i18n="form_edit_subtitle">Update the fields and save your changes.</span>' : '<span data-i18n="form_add_subtitle">Fill in the details of your new product.</span>' ?></p>
            </div>
            <?php if ($editProduit): ?>
                <div class="edit-banner">
                    <span><span data-i18n="editing_label">Editing:</span> <?= htmlspecialchars($editProduit['nomProduit']) ?></span>
                    <a href="index.php">✕ <span data-i18n="btn_cancel">Cancel</span></a>
                </div>
            <?php endif; ?>

            <!-- Completion bar -->
            <div class="completion-wrap">
                <div class="completion-top">
                    <span class="completion-label" data-i18n="completion_label">Listing completeness</span>
                    <span class="completion-pct" id="completionPct">0%</span>
                </div>
                <div class="completion-bar"><div class="completion-fill" id="completionFill" style="width:0%"></div></div>
                <div class="completion-hint" id="completionHint" data-i18n="completion_hint_default">Fill in all fields to maximize your product's visibility to creators.</div>
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
                    <label for="nom"><span data-i18n="label_name">Product name *</span> <span class="char-counter" id="nomCounter">0 / 80</span></label>
                    <input type="text" id="nom" name="nom" class="form-input" maxlength="80"
                           value="<?= $editProduit ? htmlspecialchars($editProduit['nomProduit']) : '' ?>">
                    <div class="error-msg" id="nomError" data-i18n="err_name">Product name is required (min. 2 characters).</div>
                </div>

                <!-- Description -->
                <div class="form-group">
                    <label for="description"><span data-i18n="label_description">Description *</span> <span class="char-counter" id="descCounter">0 / 400</span></label>
                    <textarea id="description" name="description" class="form-input" maxlength="400"><?= $editProduit ? htmlspecialchars($editProduit['description']) : '' ?></textarea>
                    <div class="error-msg" id="descError" data-i18n="err_description">A description is required (min. 10 characters).</div>
                </div>

                <!-- Category + Price -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="categorie" data-i18n="label_category">Category</label>
                        <select id="categorie" name="categorie" class="form-input">
                            <option value="" data-i18n="select_category">— Select a category —</option>
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
                        <label for="prix" data-i18n="label_price">Price *</label>
                        <div class="input-with-prefix">
                            <span class="prefix">€</span>
                            <input type="text" name="prix" id="prix" class="form-input" autocomplete="off"
                                   value="<?= $editProduit ? htmlspecialchars($editProduit['prix']) : '' ?>">
                        </div>
                        <div class="error-msg" id="prixError" data-i18n="err_price">Le prix est obligatoire (nombre ≥ 0, max 2 décimales).</div>
                        <div class="field-hint">ℹ️ <span data-i18n="price_hint">Use a dot or comma as decimal separator. E.g. 12.99 or 12,99</span></div>
                    </div>
                </div>

                <!-- Availability date -->
                <div class="form-group">
                    <label for="dateDisponibilite">
                        <span data-i18n="label_availability">Availability date</span>
                        <span style="font-size:11px;color:var(--text-dim);font-weight:400;" data-i18n="availability_hint">Optional — leave empty = available now</span>
                    </label>
                    <input type="text" id="dateDisponibilite" name="dateDisponibilite" class="form-input" autocomplete="off"
                           value="<?= $editProduit ? htmlspecialchars($editProduit['dateDisponibilite'] ?? '') : '' ?>">
                    <div class="error-msg" id="dateError" data-i18n="err_date">Format invalide. Utilisez AAAA-MM-JJ (ex : 2025-12-31).</div>
                    <div id="dispoPreview" class="dispo-badge nodate" style="margin-top:6px;" data-i18n="dispo_now">📅 Available now</div>
                </div>

                <!-- Tags / Characteristics -->
                <div class="form-group">
                    <label><span data-i18n="label_tags">Tags *</span> <span style="font-size:11px;color:var(--text-dim);font-weight:500;" data-i18n="label_characteristics">Characteristics</span></label>
                    <div class="tags-container" id="tagsContainer">
                        <div class="tags-selected" id="tagsSelected"></div>
                        <div class="tags-grid" id="tagsGrid">
                            <?php foreach ($tagsDisponibles as $tag): ?>
                                <button type="button" class="tag-btn"><?= htmlspecialchars($tag) ?></button>
                            <?php endforeach; ?>
                        </div>
                        <div class="tags-custom-row">
                            <input type="text" class="tags-custom-input" id="tagCustomInput" maxlength="30">
                            <button type="button" class="tags-custom-btn" data-i18n="btn_add_tag">+ Add</button>
                        </div>
                        <div class="tags-hint" data-i18n="tags_hint">Selected tags will appear on your product listing visible to creators.</div>
                    </div>
                    <div class="error-msg" id="caracError" data-i18n="err_tags">Please add at least one tag.</div>
                </div>

                <!-- Internal note -->
                <div class="form-group">
                    <label data-i18n="label_internal_note">Internal note</label>
                    <div class="note-interne-wrap">
                        <div class="note-interne-header">
                            <span class="lock-icon">🔒</span>
                            <span data-i18n="note_team_only">Visible to your brand team only</span>
                        </div>
                        <textarea name="noteInterne" id="noteInterne" class="note-interne"><?= $editProduit ? htmlspecialchars($editProduit['noteInterne'] ?? '') : '' ?></textarea>
                    </div>
                </div>

                <!-- Pin + Archive toggles (edit mode only) -->
                <?php if ($editProduit): ?>
                <div class="toggle-row">
                    <div class="toggle-item">
                        <div>
                            <label for="toggleEpingle" style="margin:0;cursor:pointer;font-size:13px;" data-i18n="toggle_pin">📌 Pin this product</label>
                            <div class="toggle-desc" data-i18n="toggle_pin_desc">Displayed at the top of the catalog</div>
                        </div>
                        <label class="toggle-switch pin">
                            <input type="checkbox" id="toggleEpingle" <?= !empty($editProduit['estEpingle']) ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="toggle-item">
                        <div>
                            <label for="toggleArchive" style="margin:0;cursor:pointer;font-size:13px;" data-i18n="toggle_archive">🗄️ Archive</label>
                            <div class="toggle-desc" data-i18n="toggle_archive_desc">Hidden from the active catalog</div>
                        </div>
                        <label class="toggle-switch archive">
                            <input type="checkbox" id="toggleArchive" <?= !empty($editProduit['estArchive']) ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Image -->
                <div class="form-group">
                    <label data-i18n="label_image">Product image</label>
                    <?php if ($editProduit && !empty($editProduit['image'])): ?>
                        <div class="current-img-block">
                            <img src="<?= $baseUrl ?>/Vue/public/produits/<?= htmlspecialchars($editProduit['image']) ?>" alt="">
                            <div>
                                <strong data-i18n="current_image">Current image</strong>
                                <span data-i18n="current_image_hint">Upload a new image to replace it.</span>
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
                        <p><strong data-i18n="upload_click">Click to upload</strong> <span data-i18n="upload_drag">or drag an image here</span></p>
                        <span class="upload-hint" data-i18n="upload_hint">JPG, PNG, WEBP — 2 MB max</span>
                    </div>
                    <div class="error-msg" id="imageError" data-i18n="err_image">File too large (max 2 MB) or invalid format (JPG, PNG, WEBP only).</div>
                    <div class="img-preview-realtime" id="imgPreviewWrap">
                        <img id="imgPreview" src="" alt="Preview">
                    </div>
                </div>

                <!-- Live Preview -->
                <div class="live-preview-wrap">
                    <div class="live-preview-label" data-i18n="live_preview_label">Live card preview</div>
                    <div class="live-preview-card">
                        <div class="lp-img" id="lpImg">
                            <img id="lpImgEl" src="" alt="">
                            📦
                        </div>
                        <div class="lp-body">
                            <div class="lp-meta">
                                <span class="lp-cat" id="lpCat"></span>
                            </div>
                            <div class="lp-name" id="lpName" data-i18n="lp_name_placeholder">Product name</div>
                            <div class="lp-price-badge" id="lpPrice">0.00 €</div>
                            <div class="lp-desc" id="lpDesc" data-i18n="lp_desc_placeholder">Description will appear here…</div>
                            <div class="lp-tags" id="lpTags"></div>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <?= $editProduit ? '<span data-i18n="btn_save_changes">Save changes</span>' : '<span data-i18n="btn_add_submit">Add product</span>' ?>
                    </button>
                    <?php if ($editProduit): ?>
                        <a href="index.php" class="btn-outline" data-i18n="btn_cancel">Cancel</a>
                    <?php else: ?>
                        <button type="reset" class="btn-outline" data-i18n="btn_reset">Reset</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- TIPS SIDEBAR -->
        <div class="tips-card">
            <div class="tips-card-header">
                <div class="icon">💡</div>
                <h3 data-i18n="tips_title">Publishing tips</h3>
            </div>
            <div class="tip-item">
                <div class="tip-dot">1</div>
                <div><strong data-i18n="tip1_title">Catchy name</strong><p data-i18n="tip1_body">Short, precise, and memorable.</p></div>
            </div>
            <div class="tip-item">
                <div class="tip-dot">2</div>
                <div><strong data-i18n="tip2_title">Right category</strong><p data-i18n="tip2_body">Helps creators filter and find your products quickly.</p></div>
            </div>
            <div class="tip-item">
                <div class="tip-dot">3</div>
                <div><strong data-i18n="tip3_title">Availability date</strong><p data-i18n="tip3_body">Plan a launch or upcoming promotion in advance.</p></div>
            </div>
            <div class="tip-item">
                <div class="tip-dot">4</div>
                <div><strong data-i18n="tip4_title">Internal note</strong><p data-i18n="tip4_body">Private briefing for your team or follow-up reminders.</p></div>
            </div>
            <div class="tip-item">
                <div class="tip-dot">5</div>
                <div><strong data-i18n="tip5_title">Pin your stars</strong><p data-i18n="tip5_body">Highlight your best products for creators to notice first.</p></div>
            </div>
            <div class="tip-item">
                <div class="tip-dot">6</div>
                <div><strong data-i18n="tip6_title">Archive wisely</strong><p data-i18n="tip6_body">Archived products disappear from the catalog but remain recoverable.</p></div>
            </div>
        </div>
    </div>

    <!-- CATALOGUE SECTION HEADER -->
    <div class="section-head" style="margin-bottom:16px;">
        <div class="section-head-left">
            <h2 data-i18n="catalog_title">All Products</h2>
            <span class="count-pill"><?= $totalProduits ?> products</span>
        </div>
        <div class="tools-bar">
            <div class="search-wrap">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                <input type="text" id="searchInput" class="search-input" placeholder="Search products…">
            </div>
            <select id="sortSelect" class="sort-select">
                <option value="" data-i18n="sort_default">Sort by…</option>
                <option value="nom" data-i18n="sort_name">Name A→Z</option>
                <option value="prix_asc" data-i18n="sort_price_asc">Price ↑</option>
                <option value="prix_desc" data-i18n="sort_price_desc">Price ↓</option>
                <option value="epingle" data-i18n="sort_pinned">Pinned first</option>
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

    <!-- CATEGORY FILTER PILLS -->
    <div class="cat-filter-card">
        <span class="cat-filter-label" data-i18n="filter_category_label">Category:</span>
        <button class="cat-pill active" data-cat="" onclick="filterByCategory(this, '')" data-i18n="filter_all_cats">All</button>
        <?php foreach ($categories as $cat): ?>
            <button class="cat-pill" data-cat="<?= strtolower(htmlspecialchars($cat)) ?>" onclick="filterByCategory(this, '<?= strtolower(htmlspecialchars($cat)) ?>')"><?= htmlspecialchars($cat) ?></button>
        <?php endforeach; ?>
    </div>

    <!-- PRICE FILTER CARD -->
    <div class="price-filter-card">
        <div class="price-filter">
            <span class="price-filter-label" data-i18n="price_filter_label">Price range:</span>
            <div class="price-range-wrap">
                <input type="text" id="priceMin" class="price-input" placeholder="Min €">
                <span class="price-sep">–</span>
                <input type="text" id="priceMax" class="price-input" placeholder="Max €">
            </div>
            <?php if (!empty($produits)): ?>
            <span class="price-catalog-hint">Catalog: <?= number_format(min(array_column($produits,'prix')),0) ?> € – <?= number_format(max(array_column($produits,'prix')),0) ?> €</span>
            <?php endif; ?>
        </div>
        <div style="display:flex;align-items:center;gap:12px;margin-left:auto;">
            <!-- ===== ADDED FEATURE: PER PAGE ===== -->
            <select id="perPageSelect" class="per-page-select" onchange="changePerPage()">
                <option value="6">6/page</option>
                <option value="9" selected>9/page</option>
                <option value="12">12/page</option>
                <option value="999" data-i18n="show_all">All</option>
            </select>
            <!-- ===== END ADDED FEATURE ===== -->
            <button class="btn-reset-filters" onclick="resetAllFilters()" data-i18n="btn_reset_filters">Reset filters</button>
        </div>
    </div>

    <!-- ===== ADVANCED FILTERS PANEL (hidden, kept for JS compat) ===== -->
    <div class="adv-filters-panel" id="advFiltersPanel">
        <div class="adv-filter-group">
            <label data-i18n="filter_category_label">Category</label>
            <select class="adv-filter-select" id="advCatFilter" onchange="applyAdvancedFilters()">
                <option value="" data-i18n="filter_all_cats">All categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= strtolower(htmlspecialchars($cat)) ?>"><?= htmlspecialchars($cat) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="adv-filter-group">
            <label data-i18n="filter_availability_label">Availability</label>
            <select class="adv-filter-select" id="advDispoFilter" onchange="applyAdvancedFilters()">
                <option value="" data-i18n="filter_all_availability">All</option>
                <option value="available" data-i18n="filter_available_now">Available now</option>
                <option value="future" data-i18n="filter_future">Future date</option>
                <option value="noddate" data-i18n="filter_no_date">No date set</option>
            </select>
        </div>
        <div class="adv-filter-group">
            <label data-i18n="filter_status_label">Status</label>
            <div class="adv-filter-toggle-group">
                <button class="adv-toggle-btn active" id="filterAll" onclick="setStatusFilter('all', this)" data-i18n="filter_all">All</button>
                <button class="adv-toggle-btn" id="filterPinned" onclick="setStatusFilter('pinned', this)">📌 <span data-i18n="filter_pinned">Pinned</span></button>
                <button class="adv-toggle-btn" id="filterNoImage" onclick="setStatusFilter('noimage', this)">🖼️ <span data-i18n="filter_no_image_btn">No image</span></button>
            </div>
        </div>
        <div class="adv-filter-group" style="margin-left:auto;">
            <label>&nbsp;</label>
            <button class="btn-reset-filters" onclick="resetAllFilters()">↺ <span data-i18n="btn_reset_filters">Reset filters</span></button>
        </div>
    </div>

    <!-- ACTIVE FILTER CHIPS BAR -->
    <div class="active-filters-bar" id="activeFiltersBar">
        <span style="font-size:12px;font-weight:700;color:var(--text-sub);" data-i18n="active_filters_label">Active filters:</span>
    </div>
    <!-- ===== END ADVANCED FILTERS PANEL ===== -->

    <!-- TAB BAR -->
    <div class="tab-bar">
        <button class="tab-btn active" id="tab-actifs">
            <span data-i18n="tab_active">Active</span> <span class="tab-pill"><?= $totalProduits ?></span>
        </button>
        <button class="tab-btn" id="tab-archives">
            <span data-i18n="tab_archived">Archived</span> <span class="tab-pill archive"><?= $totalArchives ?></span>
        </button>
    </div>

    <!-- TAB: ACTIVE -->
    <div class="tab-content active" id="content-actifs">

        <?php if (!empty($categories)): ?>
        <div class="cat-filter-bar" id="catFilterBar">
            <span class="cat-filter-label" data-i18n="cat_filter_label">Category:</span>
            <button class="cat-chip active" data-i18n="filter_all_cats_chip">All</button>
            <?php foreach ($categories as $cat): ?>
                <button class="cat-chip"><?= htmlspecialchars($cat) ?></button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Pinned products -->
        <?php if (!empty($epingles)): ?>
        <div class="pinned-section">
            <div class="pinned-header">
                <span class="pin-icon">📌</span>
                <h3 data-i18n="pinned_title">Pinned products</h3>
                <span class="count-pill" style="font-size:11px;"><?= count($epingles) ?></span>
            </div>
            <div class="pinned-grid">
                <?php foreach ($epingles as $ep): ?>
                <div class="pinned-card" data-id="<?= $ep['idProduit'] ?>">
                    <span class="pin-badge" data-i18n="pinned_badge">Pinned</span>
                    <div class="pinned-name"><?= htmlspecialchars($ep['nomProduit']) ?></div>
                    <div class="pinned-price"><?= number_format((float)$ep['prix'], 2, '.', ' ') ?> €</div>
                    <?php if (!empty($ep['categorie'])): ?>
                        <div class="pinned-cat">📂 <?= htmlspecialchars($ep['categorie']) ?></div>
                    <?php endif; ?>
                    <div class="pinned-actions">
                        <a href="index.php?modifier=<?= $ep['idProduit'] ?>" class="recent-btn edit" style="border-radius:6px;font-size:11px;">✏️ <span data-i18n="btn_edit">Edit</span></a>
                        <button class="btn-unpin" data-i18n="btn_unpin">Unpin</button>
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
                <span data-i18n="drag_hint">Drag cards to reorder the display order</span>
            </div>
            <button type="button" class="btn-export" id="btnSaveOrder" style="display:none;">
                💾 <span data-i18n="btn_save_order">Save order</span>
            </button>
        </div>
        <?php endif; ?>

        <?php if (empty($produits)): ?>
            <div class="empty-state">
                <span class="empty-icon">📦</span>
                <h3 data-i18n="empty_title">No products yet</h3>
                <p data-i18n="empty_subtitle">Add your first product to start collaborating with creators.</p>
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
                     data-dispo="<?= htmlspecialchars($dispo ?? '') ?>"
                     data-hasimage="<?= !empty($p['image']) ? '1' : '0' ?>">

                    <div class="pcard-img">
                        <?php if ($isPinned): ?>
                            <div class="badge-epingle">📌 <span data-i18n="pinned_badge">Pinned</span></div>
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
                                <span data-i18n="btn_quick_view">Quick view</span>
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
                                <span data-i18n="btn_edit">Edit</span>
                            </a>
                            <button class="btn-pin-card <?= $isPinned ? 'pinned' : '' ?>" title="<?= $isPinned ? 'Unpin' : 'Pin' ?>">📌</button>
                            <button class="btn-archive-card" title="Archive this product">🗄️</button>
                            <button class="btn-delete-card">
                                <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- ===== ADDED FEATURE: PAGINATION BAR ===== -->
            <div class="pagination-bar" id="paginationBar">
                <span class="pagination-info" id="paginationInfo"></span>
                <button class="page-btn" id="btnPrev" onclick="changePage(-1)">← <span data-i18n="prev">Prev.</span></button>
                <div id="pageNumbers" style="display:flex;gap:4px;"></div>
                <button class="page-btn" id="btnNext" onclick="changePage(1)"><span data-i18n="next">Next</span> →</button>
            </div>
            <!-- ===== END PAGINATION ===== -->

            <div id="noResults" class="no-results" style="display:none;" data-i18n="no_results">🔍 No products match your search.</div>
        <?php endif; ?>
    </div>

    <!-- TAB: ARCHIVED -->
    <div class="tab-content" id="content-archives">
        <?php if (empty($produitsArchives)): ?>
            <div class="empty-state">
                <span class="empty-icon">🗄️</span>
                <h3 data-i18n="archived_empty_title">No archived products</h3>
                <p data-i18n="archived_empty_subtitle">Products you archive will appear here and can be restored at any time.</p>
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
                    <button class="btn-restore" data-i18n="btn_restore">♻️ Restore</button>
                    <button class="btn-delete-arch">🗑️</button>
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
                <div class="qv-note-label" data-i18n="qv_note_label">🔒 Internal note (brand team only)</div>
                <div class="qv-note-text" id="qvNote"></div>
            </div>
            <div class="qv-tags" id="qvTags"></div>
            <div class="qv-actions">
                <a id="qvEditBtn" href="#" class="btn-primary" style="font-size:13px;padding:9px 18px;">✏️ <span data-i18n="btn_edit">Edit</span></a>
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
        <h3 data-i18n="modal_delete_title">Delete this product?</h3>
        <p id="deleteModalText" data-i18n="modal_delete_text">This action is permanent and cannot be undone.</p>
        <div class="modal-actions">
            <button class="btn-cancel" data-i18n="btn_cancel">Cancel</button>
            <a href="#" id="deleteConfirmBtn" class="btn-confirm-delete" data-i18n="btn_delete_confirm">Delete</a>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?= $baseUrl ?>';

// ===== ADDED FEATURE: TRANSLATION SYSTEM =====
const translations = {
    en: {
        nav_dashboard: 'Dashboard', nav_offers: 'My Offers', nav_products: 'My Products',
        nav_campaigns: 'Campaigns', nav_profile: 'My Profile',
        role_brand: 'Brand', theme_dark: 'Dark mode', theme_light: 'Light mode',
        page_title: 'My Products', page_subtitle: 'Manage, organize and promote your products to content creators.',
        btn_export: 'Export CSV', btn_add_product: 'Add a product',
        kpi_active: 'Active products', kpi_avg_price: 'Avg. price', kpi_pinned: 'Pinned',
        kpi_no_image: 'Without image', kpi_complete_listings: '⚠️ Complete these listings',
        kpi_archived: 'Archived', kpi_view_archives: 'View archives →',
        reminders_title: 'Completion reminders', missing_label: 'Missing:', btn_complete: 'Complete →',
        top_products_title: 'Top products', top_products_badge: 'Most requested',
        recent_title: 'Recently added', recent_latest: 'latest',
        form_edit_title: 'Edit product', form_add_title: 'Add a product',
        form_edit_subtitle: 'Update the fields and save your changes.',
        form_add_subtitle: 'Fill in the details of your new product.',
        editing_label: 'Editing:', btn_cancel: 'Cancel',
        completion_label: 'Listing completeness',
        completion_hint_default: 'Fill in all fields to maximize visibility to creators.',
        label_name: 'Product name *', err_name: 'Product name is required (min. 2 characters).',
        label_description: 'Description *', err_description: 'A description is required (min. 10 characters).',
        label_category: 'Category', select_category: '— Select a category —',
        label_price: 'Price *', err_price: 'Price is required (number ≥ 0, max 2 decimals).',
        price_hint: 'Use a dot or comma as decimal separator. E.g. 12.99 or 12,99',
        label_availability: 'Availability date', availability_hint: 'Optional — leave empty = available now',
        err_date: 'Invalid format. Use YYYY-MM-DD (e.g. 2025-12-31).', dispo_now: '📅 Available now',
        label_tags: 'Tags *', label_characteristics: 'Characteristics',
        btn_add_tag: '+ Add', tags_hint: 'Selected tags appear on your product listing.',
        err_tags: 'Please add at least one tag.', label_internal_note: 'Internal note',
        note_team_only: 'Visible to your brand team only', toggle_pin: '📌 Pin this product',
        toggle_pin_desc: 'Displayed at the top of the catalog',
        toggle_archive: '🗄️ Archive', toggle_archive_desc: 'Hidden from the active catalog',
        label_image: 'Product image', current_image: 'Current image',
        current_image_hint: 'Upload a new image to replace it.',
        upload_click: 'Click to upload', upload_drag: 'or drag an image here',
        upload_hint: 'JPG, PNG, WEBP — 2 MB max', err_image: 'File too large or invalid format (JPG, PNG, WEBP only).',
        live_preview_label: 'Live card preview', lp_name_placeholder: 'Product name',
        lp_desc_placeholder: 'Description will appear here…',
        btn_save_changes: 'Save changes', btn_add_submit: 'Add product', btn_reset: 'Reset',
        tips_title: 'Publishing tips', tip1_title: 'Catchy name', tip1_body: 'Short, precise, and memorable.',
        tip2_title: 'Right category', tip2_body: 'Helps creators filter and find your products quickly.',
        tip3_title: 'Availability date', tip3_body: 'Plan a launch or upcoming promotion in advance.',
        tip4_title: 'Internal note', tip4_body: 'Private briefing for your team.',
        tip5_title: 'Pin your stars', tip5_body: 'Highlight your best products for creators.',
        tip6_title: 'Archive wisely', tip6_body: 'Archived products stay recoverable.',
        catalog_title: 'Product catalog', sort_default: 'Sort by…',
        sort_name: 'Name A→Z', sort_price_asc: 'Price ↑', sort_price_desc: 'Price ↓', sort_pinned: 'Pinned first',
        btn_adv_filters: 'Filters', per_page: 'page', show_all: 'All',
        price_filter_label: 'Price:',
        filter_category_label: 'Category', filter_all_cats: 'All categories',
        filter_availability_label: 'Availability', filter_all_availability: 'All',
        filter_available_now: 'Available now', filter_future: 'Future date', filter_no_date: 'No date set',
        filter_status_label: 'Status', filter_all: 'All', filter_pinned: 'Pinned',
        filter_no_image_btn: 'No image', btn_reset_filters: 'Reset filters',
        active_filters_label: 'Active filters:',
        filter_all_cats_chip: 'All', cat_filter_label: 'Category:',
        pinned_title: 'Pinned products', pinned_badge: 'Pinned',
        drag_hint: 'Drag cards to reorder the display order', btn_save_order: 'Save order',
        empty_title: 'No products yet', empty_subtitle: 'Add your first product to start collaborating with creators.',
        btn_edit: 'Edit', btn_unpin: 'Unpin', btn_quick_view: 'Quick view',
        prev: 'Prev.', next: 'Next', no_results: '🔍 No products match your search.',
        pagination_showing: 'Showing', pagination_of: 'of', pagination_products: 'products',
        tab_active: 'Active', tab_archived: 'Archived',
        btn_restore: '♻️ Restore',
        archived_empty_title: 'No archived products',
        archived_empty_subtitle: 'Products you archive will appear here and can be restored.',
        qv_note_label: '🔒 Internal note (brand team only)',
        modal_delete_title: 'Delete this product?',
        modal_delete_text: 'This action is permanent and cannot be undone.',
        btn_delete_confirm: 'Delete',
    },
    fr: {
        nav_dashboard: 'Tableau de bord', nav_offers: 'Mes offres', nav_products: 'Mes produits',
        nav_campaigns: 'Campagnes', nav_profile: 'Mon profil',
        role_brand: 'Marque', theme_dark: 'Mode sombre', theme_light: 'Mode clair',
        page_title: 'Mes Produits', page_subtitle: 'Gérez, organisez et promouvez vos produits auprès des créateurs.',
        btn_export: 'Exporter CSV', btn_add_product: 'Ajouter un produit',
        kpi_active: 'Produits actifs', kpi_avg_price: 'Prix moyen', kpi_pinned: 'Épinglés',
        kpi_no_image: 'Sans image', kpi_complete_listings: '⚠️ Complétez ces fiches',
        kpi_archived: 'Archivés', kpi_view_archives: 'Voir les archives →',
        reminders_title: 'Rappels de complétion', missing_label: 'Manquant :', btn_complete: 'Compléter →',
        top_products_title: 'Top produits', top_products_badge: 'Les plus demandés',
        recent_title: 'Récemment ajoutés', recent_latest: 'derniers',
        form_edit_title: 'Modifier le produit', form_add_title: 'Ajouter un produit',
        form_edit_subtitle: 'Mettez à jour les champs et sauvegardez.',
        form_add_subtitle: 'Remplissez les détails de votre nouveau produit.',
        editing_label: 'Modification :', btn_cancel: 'Annuler',
        completion_label: 'Complétude de la fiche',
        completion_hint_default: 'Remplissez tous les champs pour maximiser la visibilité.',
        label_name: 'Nom du produit *', err_name: 'Le nom est requis (min. 2 caractères).',
        label_description: 'Description *', err_description: 'Une description est requise (min. 10 caractères).',
        label_category: 'Catégorie', select_category: '— Sélectionner une catégorie —',
        label_price: 'Prix *', err_price: 'Le prix est obligatoire (nombre ≥ 0, max 2 décimales).',
        price_hint: 'Utilisez un point ou une virgule. Ex : 12.99 ou 12,99',
        label_availability: 'Date de disponibilité', availability_hint: 'Optionnel — vide = disponible maintenant',
        err_date: 'Format invalide. Utilisez AAAA-MM-JJ (ex : 2025-12-31).', dispo_now: '📅 Disponible maintenant',
        label_tags: 'Tags *', label_characteristics: 'Caractéristiques',
        btn_add_tag: '+ Ajouter', tags_hint: 'Les tags apparaissent sur votre fiche produit.',
        err_tags: 'Ajoutez au moins un tag.', label_internal_note: 'Note interne',
        note_team_only: 'Visible uniquement par votre équipe', toggle_pin: '📌 Épingler ce produit',
        toggle_pin_desc: 'Affiché en tête du catalogue',
        toggle_archive: '🗄️ Archiver', toggle_archive_desc: 'Masqué du catalogue actif',
        label_image: 'Image du produit', current_image: 'Image actuelle',
        current_image_hint: 'Téléversez une nouvelle image pour la remplacer.',
        upload_click: 'Cliquez pour téléverser', upload_drag: 'ou glissez une image ici',
        upload_hint: 'JPG, PNG, WEBP — 2 Mo max', err_image: 'Fichier trop volumineux ou format invalide (JPG, PNG, WEBP).',
        live_preview_label: 'Aperçu en direct', lp_name_placeholder: 'Nom du produit',
        lp_desc_placeholder: 'La description apparaîtra ici…',
        btn_save_changes: 'Enregistrer', btn_add_submit: 'Ajouter le produit', btn_reset: 'Réinitialiser',
        tips_title: 'Conseils de publication', tip1_title: 'Nom accrocheur', tip1_body: 'Court, précis et mémorable.',
        tip2_title: 'Bonne catégorie', tip2_body: 'Aide les créateurs à filtrer rapidement.',
        tip3_title: 'Date de disponibilité', tip3_body: 'Planifiez un lancement à l\'avance.',
        tip4_title: 'Note interne', tip4_body: 'Briefing privé pour votre équipe.',
        tip5_title: 'Épinglez vos stars', tip5_body: 'Mettez en avant vos meilleurs produits.',
        tip6_title: 'Archivez avec soin', tip6_body: 'Les produits archivés restent récupérables.',
        catalog_title: 'Catalogue produits', sort_default: 'Trier par…',
        sort_name: 'Nom A→Z', sort_price_asc: 'Prix ↑', sort_price_desc: 'Prix ↓', sort_pinned: 'Épinglés d\'abord',
        btn_adv_filters: 'Filtres', per_page: 'page', show_all: 'Tout',
        price_filter_label: 'Prix :',
        filter_category_label: 'Catégorie', filter_all_cats: 'Toutes les catégories',
        filter_availability_label: 'Disponibilité', filter_all_availability: 'Toutes',
        filter_available_now: 'Disponible maintenant', filter_future: 'Date future', filter_no_date: 'Sans date',
        filter_status_label: 'Statut', filter_all: 'Tous', filter_pinned: 'Épinglés',
        filter_no_image_btn: 'Sans image', btn_reset_filters: 'Réinitialiser',
        active_filters_label: 'Filtres actifs :',
        filter_all_cats_chip: 'Toutes', cat_filter_label: 'Catégorie :',
        pinned_title: 'Produits épinglés', pinned_badge: 'Épinglé',
        drag_hint: 'Glissez les cartes pour réorganiser l\'ordre d\'affichage', btn_save_order: 'Sauvegarder l\'ordre',
        empty_title: 'Aucun produit pour l\'instant', empty_subtitle: 'Ajoutez votre premier produit pour collaborer avec des créateurs.',
        btn_edit: 'Modifier', btn_unpin: 'Désépingler', btn_quick_view: 'Aperçu rapide',
        prev: 'Préc.', next: 'Suiv.', no_results: '🔍 Aucun produit ne correspond à votre recherche.',
        pagination_showing: 'Affichage', pagination_of: 'sur', pagination_products: 'produits',
        tab_active: 'Actifs', tab_archived: 'Archivés',
        btn_restore: '♻️ Restaurer',
        archived_empty_title: 'Aucun produit archivé',
        archived_empty_subtitle: 'Les produits archivés apparaissent ici et peuvent être restaurés.',
        qv_note_label: '🔒 Note interne (équipe marque uniquement)',
        modal_delete_title: 'Supprimer ce produit ?',
        modal_delete_text: 'Cette action est permanente et ne peut pas être annulée.',
        btn_delete_confirm: 'Supprimer',
    }
};

let currentLang = localStorage.getItem('cre8_lang_produit') || 'en';

function applyTranslation(lang) {
    currentLang = lang;
    localStorage.setItem('cre8_lang_produit', lang);
    const dict = translations[lang] || translations['en'];
    document.querySelectorAll('[data-i18n]').forEach(el => {
        const key = el.getAttribute('data-i18n');
        if (dict[key] !== undefined) el.textContent = dict[key];
    });
    document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
        const key = el.getAttribute('data-i18n-placeholder');
        if (dict[key]) el.placeholder = dict[key];
    });
    document.querySelectorAll('.lang-btn').forEach(b => b.classList.remove('active'));
    const activeBtn = document.getElementById('btn-' + lang);
    if (activeBtn) activeBtn.classList.add('active');
    // sync theme label
    const isDark = document.body.classList.contains('dark-mode');
    const themeLabel = document.getElementById('themeLabel');
    if (themeLabel) themeLabel.textContent = dict[isDark ? 'theme_light' : 'theme_dark'] || '';
    renderPaginationInfo();
}

function setLang(lang) { applyTranslation(lang); }
// ===== END TRANSLATION SYSTEM =====

// ===== ADDED FEATURE: DARK MODE =====
(function initTheme() {
    const saved = localStorage.getItem('cre8_theme_fo');
    if (saved === 'dark') applyDark(true, false);
})();

function applyDark(isDark, save = true) {
    document.body.classList.toggle('dark-mode', isDark);
    const icon  = document.getElementById('themeIcon');
    const label = document.getElementById('themeLabel');
    if (icon)  icon.textContent = isDark ? '☀️' : '🌙';
    const dict = translations[currentLang] || translations['en'];
    if (label) label.textContent = dict[isDark ? 'theme_light' : 'theme_dark'] || '';
    if (save)  localStorage.setItem('cre8_theme_fo', isDark ? 'dark' : 'light');
}

function toggleTheme() { applyDark(!document.body.classList.contains('dark-mode')); }
// ===== END DARK MODE =====

// Apply translation on load
applyTranslation(currentLang);

/* ═══════════════════════════════════════════════════════════════════════════
   ORIGINAL VALIDATION FUNCTIONS (all preserved)
   ═══════════════════════════════════════════════════════════════════════════ */
function setFieldValidity(fieldId, errorId, isValid) {
    const field = document.getElementById(fieldId);
    const err   = document.getElementById(errorId);
    if (!field || !err) return;
    if (isValid) { field.classList.remove('error'); err.classList.remove('visible'); }
    else         { field.classList.add('error');    err.classList.add('visible'); }
}

function validatePrix() {
    const rawOriginal = document.getElementById('prix').value.trim();
    const raw         = rawOriginal.replace(',', '.');
    const errEl       = document.getElementById('prixError');
    if (rawOriginal === '') { errEl.textContent = 'Le prix est obligatoire.'; setFieldValidity('prix', 'prixError', false); return false; }
    if (!/^\d+([.,]\d*)?$/.test(rawOriginal)) { errEl.textContent = 'Seuls les chiffres sont autorisés.'; setFieldValidity('prix', 'prixError', false); return false; }
    const num = parseFloat(raw);
    if (num < 0) { errEl.textContent = 'Le prix ne peut pas être négatif.'; setFieldValidity('prix', 'prixError', false); return false; }
    const parts = raw.split('.');
    if (parts[0].length > 8) { errEl.textContent = 'La partie entière ne peut pas dépasser 8 chiffres.'; setFieldValidity('prix', 'prixError', false); return false; }
    if ((parts[1] || '').length > 2) { errEl.textContent = 'Maximum 2 décimales autorisées.'; setFieldValidity('prix', 'prixError', false); return false; }
    setFieldValidity('prix', 'prixError', true); return true;
}

function onPrixKeydown(e) {
    const systemKeys = ['Backspace','Delete','Tab','Escape','Enter','ArrowLeft','ArrowRight','ArrowUp','ArrowDown','Home','End'];
    if (systemKeys.includes(e.key)) return;
    if (e.ctrlKey || e.metaKey) return;
    if (e.key === '.' || e.key === ',') {
        const current = document.getElementById('prix').value;
        if (current.includes('.') || current.includes(',')) e.preventDefault();
        return;
    }
    if (!/^\d$/.test(e.key)) e.preventDefault();
}

function validateDate() {
    const val   = document.getElementById('dateDisponibilite').value.trim();
    const errEl = document.getElementById('dateError');
    if (val === '') { setFieldValidity('dateDisponibilite', 'dateError', true); return true; }
    if (!/^\d{4}-\d{2}-\d{2}$/.test(val)) { errEl.textContent = 'Format invalide. Utilisez AAAA-MM-JJ (ex : 2025-12-31).'; setFieldValidity('dateDisponibilite', 'dateError', false); return false; }
    const [year, month, day] = val.split('-').map(Number);
    if (year < 1000 || year > 9999) { errEl.textContent = "L'année doit être entre 1000 et 9999."; setFieldValidity('dateDisponibilite', 'dateError', false); return false; }
    const dateObj = new Date(year, month - 1, day);
    const isRealDate = (dateObj.getFullYear() === year && dateObj.getMonth() === month - 1 && dateObj.getDate() === day);
    if (!isRealDate) { errEl.textContent = "Cette date n'existe pas dans le calendrier."; setFieldValidity('dateDisponibilite', 'dateError', false); return false; }
    const today = new Date(); today.setHours(0,0,0,0);
    if (dateObj < today) { errEl.textContent = 'La date de disponibilité ne peut pas être dans le passé.'; setFieldValidity('dateDisponibilite', 'dateError', false); return false; }
    setFieldValidity('dateDisponibilite', 'dateError', true); return true;
}

function onDateKeydown(e) {
    const systemKeys = ['Backspace','Delete','Tab','Escape','Enter','ArrowLeft','ArrowRight','ArrowUp','ArrowDown','Home','End'];
    if (systemKeys.includes(e.key)) return;
    if (e.ctrlKey || e.metaKey) return;
    if (e.key === '-') return;
    if (!/^\d$/.test(e.key)) e.preventDefault();
}

function onDateInput(e) {
    let val = e.target.value.replace(/[^\d]/g, '');
    if (val.length > 4) val = val.slice(0,4) + '-' + val.slice(4);
    if (val.length > 7) val = val.slice(0,7) + '-' + val.slice(7);
    if (val.length > 10) val = val.slice(0,10);
    e.target.value = val;
    updateDispoPreview();
    updateCompletion();
}

function validatePriceInput(el) {
    const raw = el.value.replace(',', '.');
    const v   = parseFloat(raw);
    if (!isNaN(v) && v < 0) el.value = '0';
}

function validateNom() {
    const val = document.getElementById('nom').value.trim();
    const ok  = val.length >= 2;
    setFieldValidity('nom', 'nomError', ok); return ok;
}

function validateDescription() {
    const val = document.getElementById('description').value.trim();
    const ok  = val.length >= 10;
    setFieldValidity('description', 'descError', ok); return ok;
}

function validateTags() {
    const ok        = selectedTags.length > 0;
    const err       = document.getElementById('caracError');
    const container = document.getElementById('tagsContainer');
    ok ? (err?.classList.remove('visible'), container?.classList.remove('error'))
       : (err?.classList.add('visible'),    container?.classList.add('error'));
    return ok;
}

function validateImage() {
    const input = document.getElementById('fileInput');
    const err   = document.getElementById('imageError');
    const zone  = document.getElementById('uploadZone');
    if (!input?.files?.length) { err?.classList.remove('visible'); zone?.classList.remove('error'); return true; }
    const file         = input.files[0];
    const maxSize      = 2 * 1024 * 1024;
    const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    const ok           = file.size <= maxSize && allowedTypes.includes(file.type);
    ok ? (err?.classList.remove('visible'), zone?.classList.remove('error'))
       : (err?.classList.add('visible'),    zone?.classList.add('error'));
    return ok;
}

/* ── LISTENERS ─────────────────────────────────────────────────────────────── */
document.getElementById('prix').addEventListener('keydown', onPrixKeydown);
document.getElementById('prix').addEventListener('input',   () => { updateLivePreview(); updateCompletion(); });
document.getElementById('prix').addEventListener('blur',    validatePrix);
document.getElementById('dateDisponibilite').addEventListener('keydown', onDateKeydown);
document.getElementById('dateDisponibilite').addEventListener('input',   onDateInput);
document.getElementById('dateDisponibilite').addEventListener('blur',    () => { validateDate(); updateDispoPreview(); updateCompletion(); });
document.getElementById('nom').addEventListener('input',  () => { updateCounter('nom', 'nomCounter'); updateLivePreview(); updateCompletion(); validateNom(); });
document.getElementById('nom').addEventListener('blur',   validateNom);
document.getElementById('description').addEventListener('input', () => { updateCounter('description', 'descCounter'); updateLivePreview(); updateCompletion(); });
document.getElementById('description').addEventListener('blur', validateDescription);
document.getElementById('searchInput').addEventListener('input', applyAllFilters);
document.getElementById('priceMin').addEventListener('input', function() { validatePriceInput(this); applyAllFilters(); });
document.getElementById('priceMax').addEventListener('input', function() { validatePriceInput(this); applyAllFilters(); });
document.getElementById('categorie').addEventListener('change', () => { updateLivePreview(); updateCompletion(); });
document.getElementById('noteInterne').addEventListener('input', updateCompletion);
document.getElementById('sortSelect').addEventListener('change', sortProducts);
document.getElementById('vbtn-grid').addEventListener('click', () => setView('grid'));
document.getElementById('vbtn-list').addEventListener('click', () => setView('list'));
document.getElementById('tab-actifs').addEventListener('click',   () => switchTab('actifs'));
document.getElementById('tab-archives').addEventListener('click', () => switchTab('archives'));
const kpiLink = document.querySelector('.kpi-link');
if (kpiLink) kpiLink.addEventListener('click', () => switchTab('archives'));
const btnExport    = document.getElementById('btnExport');
if (btnExport) btnExport.addEventListener('click', exportCSV);
const btnSaveOrder = document.getElementById('btnSaveOrder');
if (btnSaveOrder) btnSaveOrder.addEventListener('click', saveDragOrder);
const resetButton  = document.querySelector('button[type="reset"]');
if (resetButton) resetButton.addEventListener('click', resetForm);
const toggleEpingleEl = document.getElementById('toggleEpingle');
if (toggleEpingleEl) toggleEpingleEl.addEventListener('change', function() { document.getElementById('epingleHidden').value = this.checked ? 1 : 0; });
const toggleArchiveEl = document.getElementById('toggleArchive');
if (toggleArchiveEl) toggleArchiveEl.addEventListener('change', function() { document.getElementById('archiveHidden').value = this.checked ? 1 : 0; });

/* ── TAGS ──────────────────────────────────────────────────────────────────── */
let selectedTags = [];
(function initTags() {
    const existing = document.getElementById('caracHidden').value;
    if (!existing.trim()) return;
    existing.split(',').map(t => t.trim()).filter(Boolean).forEach(tag => {
        if (!selectedTags.includes(tag)) {
            selectedTags.push(tag);
            document.querySelectorAll('.tag-btn').forEach(btn => { if (btn.textContent.trim() === tag) btn.classList.add('selected'); });
        }
    });
    renderSelectedTags(); updateCompletion();
})();

function toggleTag(name, btn) {
    const idx = selectedTags.indexOf(name);
    if (idx === -1) { selectedTags.push(name); btn.classList.add('selected'); }
    else            { selectedTags.splice(idx, 1); btn.classList.remove('selected'); }
    renderSelectedTags(); updateHiddenCarac(); updateLivePreview(); updateCompletion(); validateTags();
}
function addCustomTag() {
    const input = document.getElementById('tagCustomInput');
    const val   = input.value.trim();
    if (!val || selectedTags.includes(val)) { input.value = ''; return; }
    selectedTags.push(val);
    renderSelectedTags(); updateHiddenCarac(); updateLivePreview(); updateCompletion(); validateTags();
    input.value = '';
}
document.getElementById('tagCustomInput').addEventListener('keydown', function(e) { if (e.key === 'Enter') { e.preventDefault(); addCustomTag(); } });
function removeTag(name) {
    selectedTags = selectedTags.filter(t => t !== name);
    document.querySelectorAll('.tag-btn').forEach(btn => { if (btn.textContent.trim() === name) btn.classList.remove('selected'); });
    renderSelectedTags(); updateHiddenCarac(); updateLivePreview(); updateCompletion(); validateTags();
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
function updateHiddenCarac() { document.getElementById('caracHidden').value = selectedTags.join(', '); }

/* ── COMPLETION BAR ─────────────────────────────────────────────────────────── */
function updateCompletion() {
    const nom      = document.getElementById('nom').value.trim();
    const desc     = document.getElementById('description').value.trim();
    const prix     = document.getElementById('prix').value;
    const cat      = document.getElementById('categorie').value;
    const dispo    = document.getElementById('dateDisponibilite').value;
    const note     = document.getElementById('noteInterne')?.value.trim() || '';
    const hasImage = document.getElementById('imgPreviewWrap').classList.contains('visible') || document.querySelector('.current-img-block') !== null;
    const checks   = [!!nom, !!desc, !!prix, hasImage, selectedTags.length > 0, !!cat, !!dispo, !!note];
    const filled   = checks.filter(Boolean).length;
    const pct      = Math.round((filled / checks.length) * 100);
    document.getElementById('completionFill').style.width = pct + '%';
    document.getElementById('completionPct').textContent  = pct + '%';
    const hints = { 0:'Start by entering the product name.', 25:'Add a description and price.', 50:'Set the category and add tags.', 75:'Add an image, date and internal note.', 100:'✅ Listing complete!' };
    const key   = [0,25,50,75,100].reverse().find(k => pct >= k);
    document.getElementById('completionHint').textContent = hints[key];
}

/* ── DISPO PREVIEW ──────────────────────────────────────────────────────────── */
function updateDispoPreview() {
    const val = document.getElementById('dateDisponibilite').value;
    const el  = document.getElementById('dispoPreview');
    const dict = translations[currentLang] || translations['en'];
    if (!val || val.length < 10) { el.textContent = dict.dispo_now || '📅 Available now'; el.className = 'dispo-badge nodate'; return; }
    const [year, month, day] = val.split('-').map(Number);
    const d   = new Date(year, month - 1, day);
    const now = new Date(); now.setHours(0,0,0,0);
    const isReal = (d.getFullYear()===year && d.getMonth()===month-1 && d.getDate()===day);
    if (!isReal) { el.textContent = dict.dispo_now || '📅 Available now'; el.className = 'dispo-badge nodate'; return; }
    const formatted = d.toLocaleDateString('en-GB', { day:'2-digit', month:'2-digit', year:'numeric' });
    if (d > now) { el.textContent = '⏳ Available from ' + formatted; el.className = 'dispo-badge future'; }
    else          { el.textContent = '✅ Available since ' + formatted; el.className = 'dispo-badge available'; }
}

/* ── COUNTERS ───────────────────────────────────────────────────────────────── */
function updateCounter(fieldId, counterId) {
    const field   = document.getElementById(fieldId);
    const counter = document.getElementById(counterId);
    if (!field || !counter) return;
    counter.textContent = field.value.length + ' / ' + (field.maxLength || '');
}

/* ── LIVE PREVIEW ───────────────────────────────────────────────────────────── */
function updateLivePreview() {
    const dict = translations[currentLang] || translations['en'];
    const nom  = document.getElementById('nom').value.trim()         || (dict.lp_name_placeholder || 'Product name');
    const desc = document.getElementById('description').value.trim() || (dict.lp_desc_placeholder || 'Description will appear here…');
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
        const t = document.createElement('span'); t.className = 'lp-tag'; t.textContent = '🏷️ ' + tag; tagsEl.appendChild(t);
    });
}

/* ── IMAGE UPLOAD ───────────────────────────────────────────────────────────── */
document.getElementById('fileInput').addEventListener('change', function() {
    const wrap    = document.getElementById('imgPreviewWrap');
    const preview = document.getElementById('imgPreview');
    const lpImg   = document.getElementById('lpImgEl');
    if (this.files && this.files[0]) {
        if (!validateImage()) { this.value = ''; wrap.classList.remove('visible'); lpImg.style.display = 'none'; updateCompletion(); return; }
        const reader = new FileReader();
        reader.onload = e => { preview.src = e.target.result; wrap.classList.add('visible'); lpImg.src = e.target.result; lpImg.style.display = 'block'; };
        reader.readAsDataURL(this.files[0]);
        updateCompletion();
    }
});
const uploadZone = document.getElementById('uploadZone');
uploadZone.addEventListener('dragover',  e => { e.preventDefault(); uploadZone.classList.add('dragging'); });
uploadZone.addEventListener('dragleave', ()  => uploadZone.classList.remove('dragging'));
uploadZone.addEventListener('drop',      e  => { e.preventDefault(); uploadZone.classList.remove('dragging'); });

/* ── DOM READY ──────────────────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
    updateCounter('nom', 'nomCounter');
    updateCounter('description', 'descCounter');
    updateCompletion(); updateLivePreview(); updateDispoPreview(); initDragAndDrop();

    document.getElementById('nom').placeholder               = 'e.g. Premium Hydrating Cream';
    document.getElementById('description').placeholder       = 'Describe your product, its benefits, target audience…';
    document.getElementById('prix').placeholder              = '0.00';
    document.getElementById('dateDisponibilite').placeholder = 'YYYY-MM-DD (optional)';
    document.getElementById('tagCustomInput').placeholder    = 'Add a custom tag…';
    document.getElementById('noteInterne').placeholder       = 'Briefing notes, creator instructions…';
    document.getElementById('searchInput').placeholder       = 'Search…';
    document.getElementById('priceMin').placeholder          = 'Min';
    document.getElementById('priceMax').placeholder          = 'Max';

    document.querySelectorAll('.cat-chip').forEach((chip, index) => {
        const cat = index === 0 ? '' : chip.textContent.trim().toLowerCase();
        chip.addEventListener('click', () => filterByCategory(cat, chip));
    });
    document.querySelectorAll('.tag-btn').forEach(btn => {
        btn.addEventListener('click', () => toggleTag(btn.textContent.trim(), btn));
    });
    document.querySelector('.tags-custom-btn').addEventListener('click', addCustomTag);
    document.querySelectorAll('.btn-unpin').forEach(btn => {
        const id = btn.closest('.pinned-card').dataset.id;
        btn.addEventListener('click', () => toggleEpingle(id));
    });
    document.querySelectorAll('.btn-quickview').forEach(btn => {
        const card = btn.closest('.product-card');
        btn.addEventListener('click', e => openQuickView(e, card.dataset.id));
    });
    document.querySelectorAll('.btn-pin-card').forEach(btn => {
        const card = btn.closest('.product-card');
        btn.addEventListener('click', () => toggleEpingle(card.dataset.id));
    });
    document.querySelectorAll('.btn-archive-card').forEach(btn => {
        const card = btn.closest('.product-card');
        btn.addEventListener('click', () => toggleArchive(card.dataset.id));
    });
    document.querySelectorAll('.btn-delete-card').forEach(btn => {
        const card = btn.closest('.product-card');
        btn.addEventListener('click', () => openDeleteModal(card.dataset.id, card.dataset.name));
    });
    document.querySelectorAll('.btn-restore').forEach(btn => {
        const card = btn.closest('.archived-card');
        btn.addEventListener('click', () => restoreArchive(card.dataset.id));
    });
    document.querySelectorAll('.btn-delete-arch').forEach(btn => {
        const card = btn.closest('.archived-card');
        btn.addEventListener('click', () => openDeleteModal(card.dataset.id, card.dataset.name));
    });
    document.getElementById('qvModal').addEventListener('click', closeQVOutside);
    document.querySelector('.qv-close').addEventListener('click', closeQV);
    document.getElementById('deleteModal').addEventListener('click', closeModalOutside);
    document.querySelector('.btn-cancel').addEventListener('click', closeDeleteModal);

    // Init pagination
    filteredCards = getAllCards();
    renderPage();
});

/* ── FORM SUBMIT ────────────────────────────────────────────────────────────── */
document.getElementById('produitForm').addEventListener('submit', function(e) {
    const nomOk   = validateNom();
    const descOk  = validateDescription();
    const prixOk  = validatePrix();
    const dateOk  = validateDate();
    const tagsOk  = validateTags();
    const imageOk = validateImage();
    if (!nomOk || !descOk || !prixOk || !dateOk || !tagsOk || !imageOk) {
        e.preventDefault();
        document.querySelector('.form-input.error, .tags-container.error')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
});

/* ── RESET FORM ─────────────────────────────────────────────────────────────── */
function resetForm() {
    selectedTags = [];
    renderSelectedTags(); updateHiddenCarac();
    document.querySelectorAll('.tag-btn').forEach(b => b.classList.remove('selected'));
    document.getElementById('imgPreviewWrap').classList.remove('visible');
    document.querySelectorAll('.form-input').forEach(el => el.classList.remove('error'));
    document.querySelectorAll('.error-msg').forEach(el => el.classList.remove('visible'));
    document.getElementById('tagsContainer').classList.remove('error');
    document.getElementById('uploadZone').classList.remove('error');
    updateCounter('nom', 'nomCounter'); updateCounter('description', 'descCounter');
    updateCompletion(); updateLivePreview(); updateDispoPreview();
}

/* ── TABS ───────────────────────────────────────────────────────────────────── */
function switchTab(tab) {
    ['actifs', 'archives'].forEach(t => {
        document.getElementById('tab-' + t).classList.toggle('active', t === tab);
        document.getElementById('content-' + t).classList.toggle('active', t === tab);
    });
}

/* ── CATEGORY FILTER ────────────────────────────────────────────────────────── */
let activeCategory = '';
function filterByCategory(cat, btn) {
    activeCategory = cat.toLowerCase();
    document.querySelectorAll('.cat-chip').forEach(c => c.classList.remove('active'));
    btn.classList.add('active');
    // sync advanced filter dropdown
    const advCat = document.getElementById('advCatFilter');
    if (advCat) advCat.value = activeCategory;
    applyAllFilters();
}

/* ═══════════════════════════════════════════════════════════════════════════
   ===== ADDED FEATURE: ADVANCED FILTER SYSTEM =====
   ═══════════════════════════════════════════════════════════════════════════ */
let advStatusFilter = 'all'; // 'all' | 'pinned' | 'noimage'

function toggleAdvFilters() {
    const panel = document.getElementById('advFiltersPanel');
    const btn   = document.getElementById('btnAdvFilters');
    panel.classList.toggle('open');
    btn.classList.toggle('open');
}

function setStatusFilter(mode, btn) {
    advStatusFilter = mode;
    document.querySelectorAll('.adv-toggle-btn').forEach(b => b.classList.remove('active', 'active-pin'));
    btn.classList.add(mode === 'pinned' ? 'active-pin' : 'active');
    applyAllFilters();
}

function applyAdvancedFilters() {
    const advCat   = document.getElementById('advCatFilter').value;
    const advDispo = document.getElementById('advDispoFilter').value;
    // sync cat chip bar
    if (advCat !== activeCategory) {
        activeCategory = advCat;
        document.querySelectorAll('.cat-chip').forEach((chip, i) => {
            const chipCat = i === 0 ? '' : chip.textContent.trim().toLowerCase();
            chip.classList.toggle('active', chipCat === activeCategory);
        });
    }
    applyAllFilters();
}

function applyAllFilters() {
    const q        = document.getElementById('searchInput').value.toLowerCase().trim();
    const minP     = parseFloat(document.getElementById('priceMin').value.replace(',', '.')) || 0;
    const maxP     = parseFloat(document.getElementById('priceMax').value.replace(',', '.')) || Infinity;
    const advCat   = document.getElementById('advCatFilter').value;
    const advDispo = document.getElementById('advDispoFilter').value;
    const cards    = getAllCards();
    const now      = new Date(); now.setHours(0,0,0,0);

    filteredCards = cards.filter(card => {
        const name     = card.dataset.name  || '';
        const desc     = card.dataset.desc  || '';
        const prix     = parseFloat(card.dataset.prix) || 0;
        const cat      = card.dataset.cat   || '';
        const epingle  = card.dataset.epingle === '1';
        const hasImage = card.dataset.hasimage === '1';
        const dispoStr = card.dataset.dispo || '';

        // Text match
        const mQ = !q || name.includes(q) || desc.includes(q);
        // Price
        const mP = prix >= minP && prix <= maxP;
        // Category (from chip bar or advanced filter dropdown)
        const mC = !activeCategory || cat === activeCategory || !advCat || cat === advCat;
        // Status
        let mS = true;
        if (advStatusFilter === 'pinned')  mS = epingle;
        if (advStatusFilter === 'noimage') mS = !hasImage;
        // Availability
        let mD = true;
        if (advDispo === 'available') {
            mD = dispoStr === '' || (dispoStr && new Date(dispoStr) <= now);
        } else if (advDispo === 'future') {
            mD = dispoStr !== '' && new Date(dispoStr) > now;
        } else if (advDispo === 'noddate') {
            mD = dispoStr === '';
        }

        return mQ && mP && mC && mS && mD;
    });

    document.getElementById('visibleCount') && (document.getElementById('visibleCount').textContent = filteredCards.length);
    const noRes = document.getElementById('noResults');
    if (noRes) noRes.style.display = filteredCards.length === 0 ? '' : 'none';

    updateActiveFilterChips();
    currentPage = 1;
    renderPage();
}

function updateActiveFilterChips() {
    const bar  = document.getElementById('activeFiltersBar');
    const dict = translations[currentLang] || translations['en'];
    if (!bar) return;
    // remove old chips but keep label
    bar.querySelectorAll('.active-filter-chip').forEach(c => c.remove());
    const chips = [];

    const q      = document.getElementById('searchInput').value.trim();
    const minP   = document.getElementById('priceMin').value.trim();
    const maxP   = document.getElementById('priceMax').value.trim();
    const dispo  = document.getElementById('advDispoFilter').value;

    if (q)              chips.push({ label: `🔍 "${q}"`,              clear: () => { document.getElementById('searchInput').value = ''; applyAllFilters(); } });
    if (activeCategory) chips.push({ label: `📂 ${activeCategory}`,   clear: () => { filterByCategory('', document.querySelector('.cat-chip')); } });
    if (minP || maxP)   chips.push({ label: `💶 ${minP||'0'}–${maxP||'∞'} €`, clear: () => { document.getElementById('priceMin').value = ''; document.getElementById('priceMax').value = ''; applyAllFilters(); } });
    if (advStatusFilter !== 'all') chips.push({ label: advStatusFilter === 'pinned' ? '📌 Pinned' : '🖼️ No image', clear: () => { setStatusFilter('all', document.getElementById('filterAll')); } });
    if (dispo)          chips.push({ label: dispo === 'available' ? '✅ Available now' : dispo === 'future' ? '⏳ Future' : '📅 No date', clear: () => { document.getElementById('advDispoFilter').value = ''; applyAllFilters(); } });

    chips.forEach(c => {
        const chip = document.createElement('span');
        chip.className = 'active-filter-chip';
        chip.innerHTML = `${c.label} <button>×</button>`;
        chip.querySelector('button').addEventListener('click', c.clear);
        bar.appendChild(chip);
    });

    bar.classList.toggle('has-filters', chips.length > 0);
}

function resetAllFilters() {
    document.getElementById('searchInput').value      = '';
    document.getElementById('priceMin').value         = '';
    document.getElementById('priceMax').value         = '';
    document.getElementById('advCatFilter').value     = '';
    document.getElementById('advDispoFilter').value   = '';
    advStatusFilter = 'all';
    document.querySelectorAll('.adv-toggle-btn').forEach(b => b.classList.remove('active', 'active-pin'));
    document.getElementById('filterAll')?.classList.add('active');
    activeCategory = '';
    document.querySelectorAll('.cat-chip').forEach((c, i) => c.classList.toggle('active', i === 0));
    applyAllFilters();
}
// ===== END ADVANCED FILTERS =====

/* ═══════════════════════════════════════════════════════════════════════════
   ===== ADDED FEATURE: PAGINATION =====
   ═══════════════════════════════════════════════════════════════════════════ */
let currentPage  = 1;
let perPage      = 9;
let filteredCards = [];

function getAllCards() {
    return Array.from(document.querySelectorAll('#productsGrid .product-card'));
}

function renderPage() {
    const all = getAllCards();
    all.forEach(c => c.style.display = 'none');
    if (!filteredCards.length) { renderPaginationBar(0, 0); renderPaginationInfo(); return; }
    const totalPages = Math.ceil(filteredCards.length / perPage);
    if (currentPage > totalPages) currentPage = totalPages;
    if (currentPage < 1)          currentPage = 1;
    const start = (currentPage - 1) * perPage;
    const end   = Math.min(start + perPage, filteredCards.length);
    filteredCards.forEach((card, i) => { card.style.display = (i >= start && i < end) ? '' : 'none'; });
    renderPaginationBar(totalPages, currentPage);
    renderPaginationInfo(start + 1, end, filteredCards.length);
}

function renderPaginationBar(totalPages, cur) {
    const bar      = document.getElementById('paginationBar');
    const btnPrev  = document.getElementById('btnPrev');
    const btnNext  = document.getElementById('btnNext');
    const pageNums = document.getElementById('pageNumbers');
    if (!bar) return;
    bar.style.display = (totalPages <= 1) ? 'none' : 'flex';
    if (btnPrev) btnPrev.disabled = cur <= 1;
    if (btnNext) btnNext.disabled = cur >= totalPages;
    if (!pageNums) return;
    pageNums.innerHTML = '';
    const startP = Math.max(1, cur - 2);
    const endP   = Math.min(totalPages, startP + 4);
    for (let i = startP; i <= endP; i++) {
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
    const dict = translations[currentLang] || translations['en'];
    info.textContent = `${dict.pagination_showing||'Showing'} ${from}–${to} ${dict.pagination_of||'of'} ${total} ${dict.pagination_products||'products'}`;
}

function changePage(delta) { currentPage += delta; renderPage(); }

function changePerPage() {
    const sel = document.getElementById('perPageSelect');
    perPage = parseInt(sel?.value) || 9;
    currentPage = 1;
    renderPage();
}
// ===== END PAGINATION =====

/* ── CATEGORY PILL FILTER ───────────────────────────────────────────────────── */
function filterByCategory(btn, cat) {
    document.querySelectorAll('.cat-pill').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    const advCat = document.getElementById('advCatFilter');
    if (advCat) { advCat.value = cat; }
    applyAdvancedFilters();
}

function resetAllFilters() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) searchInput.value = '';
    const priceMin = document.getElementById('priceMin');
    const priceMax = document.getElementById('priceMax');
    if (priceMin) priceMin.value = '';
    if (priceMax) priceMax.value = '';
    document.querySelectorAll('.cat-pill').forEach(p => p.classList.remove('active'));
    const allPill = document.querySelector('.cat-pill[data-cat=""]');
    if (allPill) allPill.classList.add('active');
    const advCat = document.getElementById('advCatFilter');
    if (advCat) advCat.value = '';
    const advDispo = document.getElementById('advDispoFilter');
    if (advDispo) advDispo.value = '';
    const sortSel = document.getElementById('sortSelect');
    if (sortSel) sortSel.value = '';
    applyAdvancedFilters();
}

/* ── SORT ───────────────────────────────────────────────────────────────────── */
function sortProducts() {
    const mode = document.getElementById('sortSelect').value;
    const grid = document.getElementById('productsGrid');
    if (!grid || !mode) return;
    const cards = Array.from(grid.querySelectorAll('.product-card'));
    cards.sort((a, b) => {
        if (mode === 'nom')       return (a.dataset.name||'').localeCompare(b.dataset.name||'');
        if (mode === 'prix_asc')  return parseFloat(a.dataset.prix) - parseFloat(b.dataset.prix);
        if (mode === 'prix_desc') return parseFloat(b.dataset.prix) - parseFloat(a.dataset.prix);
        if (mode === 'epingle')   return parseInt(b.dataset.epingle||0) - parseInt(a.dataset.epingle||0);
        return 0;
    });
    cards.forEach(c => grid.appendChild(c));
    applyAllFilters();
}

/* ── filterProducts (legacy kept for compatibility) ─────────────────────────── */
function filterProducts() { applyAllFilters(); }

function setView(mode) {
    const grid = document.getElementById('productsGrid');
    if (!grid) return;
    grid.className = 'products-grid drag-mode' + (mode === 'list' ? ' view-list' : '');
    document.getElementById('vbtn-grid').classList.toggle('active', mode === 'grid');
    document.getElementById('vbtn-list').classList.toggle('active', mode === 'list');
}

/* ── DRAG AND DROP ──────────────────────────────────────────────────────────── */
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
                    const dict = translations[currentLang] || {};
                    btn.textContent = '✅ ' + (dict.btn_save_order || 'Order saved');
                    setTimeout(() => { btn.textContent = '💾 ' + (dict.btn_save_order || 'Save order'); btn.style.display = 'none'; }, 2000);
                }
            }
        });
}

/* ── AJAX TOGGLES ───────────────────────────────────────────────────────────── */
function toggleEpingle(id) {
    const formData = new FormData();
    formData.append('action', 'epingle'); formData.append('id', id);
    fetch('index.php', { method: 'POST', body: formData }).then(r => r.json()).then(() => location.reload());
}
function toggleArchive(id) {
    if (!confirm('Archive this product?')) return;
    const formData = new FormData();
    formData.append('action', 'archive'); formData.append('id', id);
    fetch('index.php', { method: 'POST', body: formData }).then(r => r.json()).then(() => location.reload());
}
function restoreArchive(id) {
    const formData = new FormData();
    formData.append('action', 'archive'); formData.append('id', id);
    fetch('index.php', { method: 'POST', body: formData }).then(r => r.json()).then(() => location.reload());
}

/* ── QUICK VIEW MODAL ───────────────────────────────────────────────────────── */
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
        const [y,m,d2] = dispo.split('-').map(Number);
        const dateObj  = new Date(y, m - 1, d2);
        const formatted = dateObj.toLocaleDateString('en-GB', { day:'2-digit', month:'long', year:'numeric' });
        const isFuture  = dateObj > new Date();
        qvDispo.textContent = isFuture ? '⏳ Available from ' + formatted : '✅ Available since ' + formatted;
        qvDispo.className   = 'qv-dispo ' + (isFuture ? 'future' : 'avail');
    } else {
        qvDispo.textContent = '';
    }
    const noteWrap = document.getElementById('qvNoteWrap');
    const noteEl   = document.getElementById('qvNote');
    if (note && noteWrap && noteEl) { noteEl.textContent = note; noteWrap.style.display = 'block'; }
    else if (noteWrap) noteWrap.style.display = 'none';
    const qvImg = document.getElementById('qvImg');
    if (img) qvImg.innerHTML = `<img src="${img}" alt="${name}" style="width:100%;height:100%;object-fit:cover;">`;
    else     qvImg.innerHTML = '📦';
    document.getElementById('qvTags').innerHTML = tags.map(t => `<span class="qv-tag">🏷️ ${t}</span>`).join('');
    document.getElementById('qvEditBtn').href = `index.php?modifier=${id}`;
    document.getElementById('qvModal').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeQV() { document.getElementById('qvModal').classList.remove('open'); document.body.style.overflow = ''; }
function closeQVOutside(e) { if (e.target === document.getElementById('qvModal')) closeQV(); }

/* ── DELETE MODAL ───────────────────────────────────────────────────────────── */
function openDeleteModal(id, name) {
    const dict = translations[currentLang] || translations['en'];
    document.getElementById('deleteModalText').textContent = `${dict.modal_delete_text||'This action is permanent.'} "${name}"`;
    document.getElementById('deleteConfirmBtn').href = 'index.php?supprimer=' + id;
    document.getElementById('deleteModal').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeDeleteModal() { document.getElementById('deleteModal').classList.remove('open'); document.body.style.overflow = ''; }
function closeModalOutside(e) { if (e.target === document.getElementById('deleteModal')) closeDeleteModal(); }

document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeDeleteModal(); closeQV(); } });

/* ── EXPORT CSV ─────────────────────────────────────────────────────────────── */
function exportCSV() {
    const cards = document.querySelectorAll('#productsGrid .product-card');
    if (!cards.length) { alert('No products to export.'); return; }
    const rows = [['Name', 'Description', 'Category', 'Price (€)', 'Tags', 'Availability', 'Internal note']];
    cards.forEach(card => {
        const nom  = card.querySelector('.pcard-name')?.textContent.trim() || '';
        const desc = card.querySelector('.pcard-desc')?.textContent.trim() || '';
        const prix = parseFloat(card.dataset.prix || 0).toFixed(2);
        const cat  = card.dataset.cat  || '';
        const tags = (card.dataset.tags || '').replace(/"/g, '""');
        const dispo= card.dataset.dispo || '';
        const note = (card.dataset.note || '').replace(/"/g, '""');
        rows.push([`"${nom.replace(/"/g,'""')}"`,`"${desc.replace(/"/g,'""')}"`,`"${cat}"`,prix,`"${tags}"`,dispo,`"${note}"`]);
    });
    const csv  = rows.map(r => r.join(';')).join('\n');
    const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href = url; a.download = 'my_products_cre8connect.csv'; a.click();
    URL.revokeObjectURL(url);
}

/* ── FLASH AUTO-DISMISS ─────────────────────────────────────────────────────── */
const flash = document.getElementById('flashMsg');
if (flash) {
    setTimeout(() => { flash.style.transition = 'opacity .4s'; flash.style.opacity = '0'; setTimeout(() => flash.remove(), 400); }, 4000);
}

<?php if ($editProduit): ?>
document.getElementById('formAnchor').scrollIntoView({ behavior: 'smooth', block: 'start' });
<?php endif; ?>
</script>
</body>
</html>