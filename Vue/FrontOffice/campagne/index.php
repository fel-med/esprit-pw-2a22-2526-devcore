<?php
require_once __DIR__ . '/../../../Controleur/campagneC.php';
require_once __DIR__ . '/../../../Modele/campagne.php';

session_start();
$campagneC   = new CampagneC();
$baseUrl     = '/projet/Esprit-PW-2A22-2526-Devcore';
$id_marque   = $_SESSION['user_id'] ?? 1;

$message     = '';
$messageType = '';
$editCampagne = null;

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

// ── ADD ───────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter') {
    $campagne = new Campagne(
        null,
        trim($_POST['titre']),
        trim($_POST['description']),
        !empty($_POST['dateDebut']) ? $_POST['dateDebut'] : null,
        !empty($_POST['dateFin'])   ? $_POST['dateFin']   : null,
        floatval(str_replace(',', '.', $_POST['budget'])),
        $_POST['statut'] ?? 'brouillon',
        $id_marque,
        trim($_POST['objectif'] ?? ''),
        0
    );
    $campagneC->ajouterCampagne($campagne);
    $message = "Campaign added successfully!";
    $messageType = "success";
}

// ── UPDATE ────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'modifier') {
    $campagne = new Campagne(
        null,
        trim($_POST['titre']),
        trim($_POST['description']),
        !empty($_POST['dateDebut']) ? $_POST['dateDebut'] : null,
        !empty($_POST['dateFin'])   ? $_POST['dateFin']   : null,
        floatval(str_replace(',', '.', $_POST['budget'])),
        $_POST['statut'] ?? 'brouillon',
        $id_marque,
        trim($_POST['objectif'] ?? ''),
        intval($_POST['estArchive'] ?? 0)
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

// ── KPIs ──────────────────────────────────────────────────────────────────────
$totalActives   = count($campagnes);
$totalArchives  = count($campagnesArchives);
$budgets        = array_column($campagnes, 'budget');
$budgetTotal    = array_sum($budgets);
$nbActives      = count(array_filter($campagnes, fn($c) => $c['statut'] === 'active'));
$nbBrouillons   = count(array_filter($campagnes, fn($c) => $c['statut'] === 'brouillon'));
$nbTerminees    = count(array_filter($campagnes, fn($c) => $c['statut'] === 'terminee'));
$recentes       = array_slice($campagnes, 0, 3);

function statutLabel($s) {
    return match($s) {
        'active'   => '✅ Active',
        'terminee' => '🏁 Ended',
        'annulee'  => '❌ Cancelled',
        default    => '📝 Draft',
    };
}
function statutColor($s) {
    return match($s) {
        'active'   => '#0ea370',
        'terminee' => '#3b82f6',
        'annulee'  => '#f43f5e',
        default    => '#f59e0b',
    };
}
function statutBg($s) {
    return match($s) {
        'active'   => '#edfaf5',
        'terminee' => '#eff6ff',
        'annulee'  => '#fff1f3',
        default    => '#fffbeb',
    };
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
        :root {
            --primary:        #5b4fff;
            --primary-hover:  #4a3ee8;
            --primary-light:  #eeecff;
            --primary-glow:   rgba(91,79,255,0.18);
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
            --info:           #3b82f6;
            --info-light:     #eff6ff;
            --card-shadow:    0 1px 3px rgba(15,14,26,0.06), 0 4px 16px rgba(91,79,255,0.06);
            --card-shadow-hover: 0 8px 32px rgba(91,79,255,0.14);
            --radius:         14px;
            --radius-sm:      8px;
            --nav-h:          66px;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
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

        /* ── PAGE ── */
        .page-wrapper { max-width: 1160px; margin: 0 auto; padding: 40px 24px 80px; }
        .page-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 32px; gap: 20px; flex-wrap: wrap; }
        .page-header-left h1 { font-family: 'Fraunces', serif; font-size: 30px; font-weight: 800; color: var(--text-main); letter-spacing: -0.8px; line-height: 1.1; }
        .page-header-left p { color: var(--text-sub); font-size: 14px; margin-top: 5px; }
        .page-header-actions { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .btn-add { display: inline-flex; align-items: center; gap: 8px; background: var(--primary); color: #fff; border: none; border-radius: var(--radius-sm); padding: 11px 20px; font-size: 13.5px; font-weight: 700; font-family: 'DM Sans', sans-serif; cursor: pointer; text-decoration: none; transition: background .2s, transform .15s; box-shadow: 0 3px 12px var(--primary-glow); }
        .btn-add:hover { background: var(--primary-hover); transform: translateY(-1px); }
        .btn-outline { display: inline-flex; align-items: center; gap: 8px; background: var(--white); color: var(--text-sub); border: 1.5px solid var(--border); border-radius: var(--radius-sm); padding: 10px 16px; font-size: 13px; font-weight: 600; font-family: 'DM Sans', sans-serif; cursor: pointer; transition: all .2s; text-decoration: none; }
        .btn-outline:hover { border-color: var(--primary); color: var(--primary); background: var(--primary-light); }

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
        .kpi-card:nth-child(4)::before { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
        .kpi-card:nth-child(5)::before { background: linear-gradient(90deg, #64748b, #94a3b8); }
        .kpi-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-bottom: 10px; font-size: 17px; }
        .kpi-card:nth-child(1) .kpi-icon { background: var(--primary-light); }
        .kpi-card:nth-child(2) .kpi-icon { background: var(--success-light); }
        .kpi-card:nth-child(3) .kpi-icon { background: var(--warning-light); }
        .kpi-card:nth-child(4) .kpi-icon { background: var(--info-light); }
        .kpi-card:nth-child(5) .kpi-icon { background: #f1f5f9; }
        .kpi-value { font-family: 'Fraunces', serif; font-size: 24px; font-weight: 800; color: var(--text-main); letter-spacing: -0.5px; line-height: 1; }
        .kpi-label { font-size: 11px; font-weight: 600; color: var(--text-sub); margin-top: 4px; text-transform: uppercase; letter-spacing: .05em; }

        /* ── RECENT ── */
        .section-head { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; flex-wrap: wrap; }
        .section-head h2 { font-family: 'Fraunces', serif; font-size: 17px; font-weight: 800; }
        .count-pill { background: var(--primary-light); color: var(--primary); border-radius: 20px; padding: 3px 11px; font-size: 12px; font-weight: 700; }
        .recent-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 36px; }
        .recent-card { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius); padding: 20px; display: flex; flex-direction: column; gap: 10px; transition: box-shadow .2s, transform .2s; }
        .recent-card:hover { box-shadow: var(--card-shadow-hover); transform: translateY(-2px); }
        .rc-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; }
        .rc-title { font-family: 'Fraunces', serif; font-size: 14.5px; font-weight: 800; color: var(--text-main); line-height: 1.25; }
        .rc-badge { font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 20px; flex-shrink: 0; }
        .rc-dates { font-size: 12px; color: var(--text-sub); display: flex; align-items: center; gap: 5px; }
        .rc-budget { font-family: 'Fraunces', serif; font-size: 18px; font-weight: 800; color: var(--primary); }
        .rc-obj { font-size: 12.5px; color: var(--text-sub); line-height: 1.5; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
        .rc-actions { display: flex; gap: 7px; padding-top: 10px; border-top: 1px solid var(--border); }
        .btn-rc { flex: 1; padding: 7px; background: var(--primary-light); color: var(--primary); border: none; border-radius: 7px; font-size: 12px; font-weight: 700; font-family: 'DM Sans', sans-serif; cursor: pointer; text-align: center; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 5px; transition: background .18s; }
        .btn-rc:hover { background: #ddddf8; }
        .btn-rc-del { flex: 0 0 auto; padding: 7px 10px; background: var(--danger-light); color: var(--danger); border: none; border-radius: 7px; font-size: 12px; cursor: pointer; font-family: 'DM Sans', sans-serif; display: inline-flex; align-items: center; transition: background .18s; }
        .btn-rc-del:hover { background: #fecdd3; }

        /* ── CAMPAIGN GRID ── */
        .camp-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 18px; margin-bottom: 32px; }
        .camp-card { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; box-shadow: var(--card-shadow); display: flex; flex-direction: column; transition: transform .22s, box-shadow .22s, border-color .22s; }
        .camp-card:hover { transform: translateY(-3px); box-shadow: var(--card-shadow-hover); border-color: var(--primary-border); }
        .camp-card-header { padding: 18px 18px 0; display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; }
        .camp-card-title { font-family: 'Fraunces', serif; font-size: 16px; font-weight: 800; color: var(--text-main); line-height: 1.25; flex: 1; }
        .camp-card-body { padding: 12px 18px 18px; display: flex; flex-direction: column; gap: 10px; flex: 1; }
        .camp-card-desc { font-size: 13px; color: var(--text-sub); line-height: 1.6; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
        .camp-card-meta { display: flex; flex-direction: column; gap: 5px; font-size: 12px; color: var(--text-sub); }
        .camp-meta-row { display: flex; align-items: center; gap: 6px; }
        .camp-budget { font-family: 'Fraunces', serif; font-size: 20px; font-weight: 800; color: var(--primary); margin-top: 4px; }
        .camp-actions { display: flex; gap: 8px; padding: 14px 18px; border-top: 1px solid var(--border); }
        .btn-camp-edit { flex: 1; padding: 8px; background: var(--primary-light); color: var(--primary); border: none; border-radius: 7px; font-size: 12px; font-weight: 700; font-family: 'DM Sans', sans-serif; cursor: pointer; text-align: center; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 5px; transition: background .18s; }
        .btn-camp-edit:hover { background: #ddddf8; }
        .btn-camp-arch { flex: 0 0 auto; padding: 8px 10px; background: var(--warning-light); color: var(--warning); border: none; border-radius: 7px; font-size: 12px; cursor: pointer; font-family: 'DM Sans', sans-serif; display: inline-flex; align-items: center; transition: background .18s; }
        .btn-camp-del  { flex: 0 0 auto; padding: 8px 10px; background: var(--danger-light); color: var(--danger); border: none; border-radius: 7px; font-size: 12px; cursor: pointer; font-family: 'DM Sans', sans-serif; display: inline-flex; align-items: center; transition: background .18s; }

        /* ── BADGE ── */
        .badge { display: inline-flex; align-items: center; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 20px; }

        /* ── SEARCH / FILTER BAR ── */
        .tools-bar { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 18px; }
        .search-wrap { position: relative; }
        .search-wrap svg { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: var(--text-dim); pointer-events: none; width: 14px; height: 14px; }
        .search-input { background: var(--white); border: 1.5px solid var(--border); border-radius: var(--radius-sm); padding: 8px 12px 8px 32px; font-size: 13px; font-family: 'DM Sans', sans-serif; color: var(--text-main); width: 220px; outline: none; transition: border-color .2s, box-shadow .2s; }
        .search-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-glow); }
        .filter-select { background: var(--white); border: 1.5px solid var(--border); border-radius: var(--radius-sm); padding: 8px 12px; font-size: 13px; font-family: 'DM Sans', sans-serif; outline: none; cursor: pointer; color: var(--text-main); }
        .filter-select:focus { border-color: var(--primary); }
        .pf-reset { background: transparent; border: 1.5px solid var(--border); border-radius: var(--radius-sm); padding: 8px 13px; font-size: 12px; font-family: 'DM Sans', sans-serif; color: var(--text-sub); cursor: pointer; }
        .pf-reset:hover { border-color: var(--danger); color: var(--danger); }

        /* ── FORM CARD ── */
        .form-layout { margin-top: 30px; }
        .form-card { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; box-shadow: var(--card-shadow); }
        .form-card.edit-mode { border-color: var(--primary-border); box-shadow: 0 0 0 3px var(--primary-glow); }
        .form-card-header { padding: 22px 26px 0; margin-bottom: 6px; }
        .form-card-header h2 { font-family: 'Fraunces', serif; font-size: 19px; font-weight: 800; }
        .form-card-header p { font-size: 13px; color: var(--text-sub); margin-top: 3px; }
        .edit-banner { display: flex; align-items: center; justify-content: space-between; background: var(--primary-light); border: 1px solid var(--primary-border); border-radius: var(--radius-sm); padding: 9px 14px; margin: 14px 26px; font-size: 13px; }
        .edit-banner a { color: var(--danger); text-decoration: none; font-weight: 700; font-size: 12px; }
        .form-inner { padding: 14px 26px 28px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
        .form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 18px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 14px; }
        .form-group label { font-size: 12.5px; font-weight: 700; color: var(--text-sub); }
        .char-counter { font-size: 11px; color: var(--text-dim); font-weight: 400; margin-left: 4px; }
        .form-input { background: var(--bg); border: 1.5px solid var(--border); border-radius: var(--radius-sm); padding: 10px 13px; font-size: 13.5px; color: var(--text-main); font-family: 'DM Sans', sans-serif; outline: none; transition: border-color .2s, box-shadow .2s; width: 100%; }
        .form-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-glow); }
        .form-input.error-field { border-color: var(--danger); }
        textarea.form-input { resize: vertical; min-height: 90px; }
        .error-msg { font-size: 11.5px; color: var(--danger); display: none; margin-top: 2px; }
        .error-msg.visible { display: block; }
        .field-hint { font-size: 11px; color: var(--text-dim); margin-top: 3px; }
        .input-with-prefix { display: flex; align-items: center; border: 1.5px solid var(--border); border-radius: var(--radius-sm); background: var(--bg); overflow: hidden; transition: border-color .2s; }
        .input-with-prefix:focus-within { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-glow); }
        .prefix { padding: 0 11px; font-size: 13px; color: var(--text-dim); border-right: 1.5px solid var(--border); background: var(--white); min-height: 42px; display: flex; align-items: center; }
        .input-with-prefix .form-input { border: none; background: transparent; flex: 1; }
        .input-with-prefix .form-input:focus { box-shadow: none; }
        .form-actions { display: flex; gap: 10px; align-items: center; padding-top: 18px; border-top: 1px solid var(--border); }
        .btn-submit { background: var(--primary); color: #fff; border: none; border-radius: var(--radius-sm); padding: 11px 26px; font-size: 14px; font-weight: 700; cursor: pointer; font-family: 'DM Sans', sans-serif; box-shadow: 0 3px 10px var(--primary-glow); transition: background .2s, transform .15s; }
        .btn-submit:hover { background: var(--primary-hover); transform: translateY(-1px); }
        .btn-cancel { background: var(--bg); color: var(--text-sub); border: 1.5px solid var(--border); border-radius: var(--radius-sm); padding: 10px 18px; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; font-family: 'DM Sans', sans-serif; }

        /* ── COMPLETION BAR ── */
        .completion-wrap { margin-bottom: 18px; background: var(--bg); border-radius: var(--radius-sm); padding: 14px 16px; border: 1px solid var(--border); }
        .completion-top { display: flex; justify-content: space-between; font-size: 12px; font-weight: 600; color: var(--text-sub); margin-bottom: 7px; }
        .completion-bar { height: 5px; background: var(--border); border-radius: 10px; overflow: hidden; }
        .completion-fill { height: 100%; background: var(--primary); border-radius: 10px; transition: width .3s; }
        .completion-hint { font-size: 11px; color: var(--text-dim); margin-top: 5px; }

        /* ── EMPTY STATE ── */
        .empty-state { text-align: center; padding: 60px 20px; background: var(--white); border: 1.5px dashed var(--border); border-radius: var(--radius); }
        .empty-icon { font-size: 48px; margin-bottom: 14px; }
        .empty-state h3 { font-family: 'Fraunces', serif; font-size: 18px; font-weight: 800; margin-bottom: 6px; }
        .empty-state p { font-size: 13.5px; color: var(--text-sub); margin-bottom: 20px; }

        /* ── DELETE MODAL ── */
        .modal-overlay { position: fixed; inset: 0; background: rgba(15,14,26,.55); z-index: 300; display: none; align-items: center; justify-content: center; }
        .modal-overlay.open { display: flex; }
        .modal-box { background: var(--white); border-radius: var(--radius); padding: 30px 32px; width: 400px; max-width: 94vw; box-shadow: 0 20px 60px rgba(15,14,26,.18); animation: popIn .22s ease; }
        @keyframes popIn { from { opacity: 0; transform: scale(.93); } to { opacity: 1; transform: scale(1); } }
        .modal-title { font-family: 'Fraunces', serif; font-size: 18px; font-weight: 800; margin-bottom: 8px; }
        .modal-text  { font-size: 13.5px; color: var(--text-sub); margin-bottom: 24px; line-height: 1.6; }
        .modal-actions { display: flex; gap: 10px; justify-content: flex-end; }
        .btn-modal-cancel  { background: var(--bg); color: var(--text-sub); border: 1.5px solid var(--border); border-radius: var(--radius-sm); padding: 9px 18px; font-size: 13px; font-weight: 600; cursor: pointer; font-family: 'DM Sans', sans-serif; }
        .btn-modal-del     { background: var(--danger); color: #fff; border: none; border-radius: var(--radius-sm); padding: 9px 20px; font-size: 13px; font-weight: 700; cursor: pointer; font-family: 'DM Sans', sans-serif; }

        /* ── ARCHIVED SECTION ── */
        .arch-toggle { display: flex; align-items: center; gap: 8px; background: none; border: 1.5px solid var(--border); border-radius: var(--radius-sm); padding: 8px 14px; font-size: 13px; font-weight: 600; color: var(--text-sub); cursor: pointer; font-family: 'DM Sans', sans-serif; margin-bottom: 14px; }
        .arch-toggle:hover { border-color: var(--primary); color: var(--primary); }
        .arch-section { display: none; }
        .arch-section.open { display: block; }
        .arch-table { width: 100%; border-collapse: collapse; background: var(--white); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; }
        .arch-table th { background: var(--bg); text-align: left; padding: 10px 14px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--text-sub); }
        .arch-table td { padding: 11px 14px; font-size: 13px; border-top: 1px solid var(--border); color: var(--text-sub); }
        .arch-table tr:hover td { background: var(--bg); }
        .btn-restore { background: var(--info-light); color: var(--info); border: none; border-radius: 7px; padding: 5px 12px; font-size: 12px; font-weight: 700; cursor: pointer; font-family: 'DM Sans', sans-serif; }

        @media (max-width: 1000px) { .kpi-strip { grid-template-columns: repeat(3, 1fr); } .recent-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 700px)  { .kpi-strip { grid-template-columns: repeat(2, 1fr); } .recent-grid { grid-template-columns: 1fr; } .form-grid { grid-template-columns: 1fr; } nav { padding: 0 18px; } .page-wrapper { padding: 24px 14px 60px; } }
    </style>
</head>
<body>

<!-- NAV -->
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

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div class="page-header-left">
            <h1>⚡ My Campaigns</h1>
            <p>Manage all your collaboration campaigns in one place.</p>
        </div>
        <div class="page-header-actions">
            <a href="?export_csv=1" class="btn-outline">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Export CSV
            </a>
            <a href="#formAnchor" class="btn-add">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                New Campaign
            </a>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="flash <?= $messageType ?>" id="flashMsg">
        <?= $messageType === 'success' ? '✅' : '🗑' ?> <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <!-- KPI STRIP -->
    <div class="kpi-strip">
        <div class="kpi-card">
            <div class="kpi-icon">⚡</div>
            <div class="kpi-value"><?= $totalActives ?></div>
            <div class="kpi-label">Total Campaigns</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon">✅</div>
            <div class="kpi-value"><?= $nbActives ?></div>
            <div class="kpi-label">Active Now</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon">📝</div>
            <div class="kpi-value"><?= $nbBrouillons ?></div>
            <div class="kpi-label">Drafts</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon">🏁</div>
            <div class="kpi-value"><?= $nbTerminees ?></div>
            <div class="kpi-label">Ended</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon">💰</div>
            <div class="kpi-value"><?= number_format($budgetTotal, 0, ',', ' ') ?> €</div>
            <div class="kpi-label">Total Budget</div>
        </div>
    </div>

    <!-- RECENT CAMPAIGNS -->
    <?php if (!empty($recentes)): ?>
    <div style="margin-bottom:32px">
        <div class="section-head">
            <h2>🕐 Recent Campaigns</h2>
            <span class="count-pill"><?= count($recentes) ?></span>
        </div>
        <div class="recent-grid">
            <?php foreach ($recentes as $rc): ?>
            <div class="recent-card">
                <div class="rc-top">
                    <div class="rc-title"><?= htmlspecialchars($rc['titreCampagne']) ?></div>
                    <span class="rc-badge badge" style="background:<?= statutBg($rc['statut']) ?>;color:<?= statutColor($rc['statut']) ?>"><?= statutLabel($rc['statut']) ?></span>
                </div>
                <div class="rc-budget"><?= number_format((float)$rc['budget'], 2, ',', ' ') ?> €</div>
                <div class="rc-dates">📅 <?= $rc['dateDebut'] ?? '—' ?> → 🏁 <?= $rc['dateFin'] ?? '—' ?></div>
                <?php if (!empty($rc['objectif'])): ?>
                <div class="rc-obj"><?= htmlspecialchars($rc['objectif']) ?></div>
                <?php endif; ?>
                <div class="rc-actions">
                    <a href="?modifier=<?= $rc['idCampagne'] ?>#formAnchor" class="btn-rc">✏️ Edit</a>
                    <button class="btn-rc-del" onclick="openDeleteModal(<?= $rc['idCampagne'] ?>, '<?= htmlspecialchars(addslashes($rc['titreCampagne'])) ?>')">🗑</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ALL CAMPAIGNS -->
    <div class="section-head">
        <h2>📋 All Campaigns</h2>
        <span class="count-pill" id="visibleCount"><?= $totalActives ?></span>
    </div>

    <!-- FILTER / SEARCH BAR -->
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
        <select class="filter-select" id="sortSelect" onchange="sortCampagnes()">
            <option value="">Sort by…</option>
            <option value="titre">Name A→Z</option>
            <option value="budget_asc">Budget ↑</option>
            <option value="budget_desc">Budget ↓</option>
            <option value="date">Start date</option>
        </select>
        <button class="pf-reset" onclick="resetFilters()">✕ Reset</button>
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
        <?php foreach ($campagnes as $c): ?>
        <div class="camp-card"
             data-titre="<?= strtolower(htmlspecialchars($c['titreCampagne'])) ?>"
             data-statut="<?= htmlspecialchars($c['statut']) ?>"
             data-budget="<?= (float)$c['budget'] ?>"
             data-date="<?= $c['dateDebut'] ?? '' ?>">
            <div class="camp-card-header">
                <div class="camp-card-title"><?= htmlspecialchars($c['titreCampagne']) ?></div>
                <span class="badge" style="background:<?= statutBg($c['statut']) ?>;color:<?= statutColor($c['statut']) ?>"><?= statutLabel($c['statut']) ?></span>
            </div>
            <div class="camp-card-body">
                <div class="camp-card-desc"><?= htmlspecialchars($c['description']) ?></div>
                <?php if (!empty($c['objectif'])): ?>
                <div style="font-size:12px;color:var(--text-sub);background:var(--bg);border-radius:6px;padding:7px 10px;">
                    🎯 <?= htmlspecialchars($c['objectif']) ?>
                </div>
                <?php endif; ?>
                <div class="camp-card-meta">
                    <div class="camp-meta-row">📅 <span><?= $c['dateDebut'] ?? 'Not set' ?></span> → 🏁 <span><?= $c['dateFin'] ?? 'Not set' ?></span></div>
                </div>
                <div class="camp-budget"><?= number_format((float)$c['budget'], 2, ',', ' ') ?> €</div>
            </div>
            <div class="camp-actions">
                <a href="?modifier=<?= $c['idCampagne'] ?>#formAnchor" class="btn-camp-edit">✏️ Edit</a>
                <button class="btn-camp-arch" onclick="toggleArchive(<?= $c['idCampagne'] ?>)" title="Archive">📦</button>
                <button class="btn-camp-del" onclick="openDeleteModal(<?= $c['idCampagne'] ?>, '<?= htmlspecialchars(addslashes($c['titreCampagne'])) ?>')" title="Delete">🗑</button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ARCHIVED -->
    <?php if (!empty($campagnesArchives)): ?>
    <div style="margin-top:36px">
        <button class="arch-toggle" onclick="toggleArchSection(this)">
            📦 Archived Campaigns (<?= $totalArchives ?>) <span>▼</span>
        </button>
        <div class="arch-section" id="archSection">
            <table class="arch-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Budget</th>
                        <th>Dates</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($campagnesArchives as $c): ?>
                <tr>
                    <td style="font-weight:600;color:var(--text-main);opacity:.7"><?= htmlspecialchars($c['titreCampagne']) ?></td>
                    <td><span class="badge" style="background:<?= statutBg($c['statut']) ?>;color:<?= statutColor($c['statut']) ?>"><?= statutLabel($c['statut']) ?></span></td>
                    <td style="font-weight:700;color:var(--primary)"><?= number_format((float)$c['budget'], 2, ',', ' ') ?> €</td>
                    <td><?= $c['dateDebut'] ?? '—' ?> → <?= $c['dateFin'] ?? '—' ?></td>
                    <td>
                        <button class="btn-restore" onclick="toggleArchive(<?= $c['idCampagne'] ?>)">🔁 Restore</button>
                        <button class="btn-rc-del" onclick="openDeleteModal(<?= $c['idCampagne'] ?>, '<?= htmlspecialchars(addslashes($c['titreCampagne'])) ?>')" style="margin-left:6px">🗑</button>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- FORM -->
    <div class="form-layout" id="formAnchor">
        <div class="form-card <?= $editCampagne ? 'edit-mode' : '' ?>">
            <div class="form-card-header">
                <h2><?= $editCampagne ? '✏️ Edit Campaign' : '➕ New Campaign' ?></h2>
                <p><?= $editCampagne ? 'Update your campaign details.' : 'Fill in the details to launch a new campaign.' ?></p>
            </div>
            <?php if ($editCampagne): ?>
            <div class="edit-banner">
                <span>Editing: <strong><?= htmlspecialchars($editCampagne['titreCampagne']) ?></strong></span>
                <a href="index.php">✕ Cancel</a>
            </div>
            <?php endif; ?>
            <div class="form-inner">
                <!-- Completion Bar -->
                <div class="completion-wrap">
                    <div class="completion-top">
                        <span>Campaign completeness</span>
                        <span id="completionPct" style="color:var(--primary)">0%</span>
                    </div>
                    <div class="completion-bar"><div class="completion-fill" id="completionFill" style="width:0%"></div></div>
                    <div class="completion-hint" id="completionHint">Fill all fields to make your campaign visible to creators.</div>
                </div>

                <form method="POST" id="campagneForm" novalidate>
                    <input type="hidden" name="action" value="<?= $editCampagne ? 'modifier' : 'ajouter' ?>">
                    <?php if ($editCampagne): ?>
                    <input type="hidden" name="id" value="<?= $editCampagne['idCampagne'] ?>">
                    <input type="hidden" name="estArchive" value="<?= intval($editCampagne['estArchive'] ?? 0) ?>">
                    <?php endif; ?>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="titre">Campaign Title * <span class="char-counter" id="titreCounter">0 / 100</span></label>
                            <input type="text" id="titre" name="titre" class="form-input" maxlength="100"
                                   value="<?= $editCampagne ? htmlspecialchars($editCampagne['titreCampagne']) : '' ?>">
                            <div class="error-msg" id="titreError">Title is required (min. 2 characters).</div>
                        </div>
                        <div class="form-group">
                            <label for="statut">Status</label>
                            <select id="statut" name="statut" class="form-input">
                                <?php foreach ($statuts as $s): ?>
                                <option value="<?= $s ?>" <?= ($editCampagne && $editCampagne['statut'] === $s) ? 'selected' : '' ?>>
                                    <?= ucfirst($s) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">Description * <span class="char-counter" id="descCounter">0 / 600</span></label>
                        <textarea id="description" name="description" class="form-input" maxlength="600"><?= $editCampagne ? htmlspecialchars($editCampagne['description']) : '' ?></textarea>
                        <div class="error-msg" id="descError">Description is required (min. 10 characters).</div>
                    </div>

                    <div class="form-group">
                        <label for="objectif">Objective / Goal <span class="char-counter" id="objCounter">0 / 200</span></label>
                        <input type="text" id="objectif" name="objectif" class="form-input" maxlength="200"
                               value="<?= $editCampagne ? htmlspecialchars($editCampagne['objectif'] ?? '') : '' ?>">
                    </div>

                    <div class="form-grid-3">
                        <div class="form-group">
                            <label for="dateDebut">Start Date *</label>
                            <input type="text" id="dateDebut" name="dateDebut" class="form-input" placeholder="YYYY-MM-DD"
                                   value="<?= $editCampagne ? htmlspecialchars($editCampagne['dateDebut'] ?? '') : '' ?>">
                            <div class="error-msg" id="dateDebutError">Enter a valid start date (YYYY-MM-DD).</div>
                        </div>
                        <div class="form-group">
                            <label for="dateFin">End Date *</label>
                            <input type="text" id="dateFin" name="dateFin" class="form-input" placeholder="YYYY-MM-DD"
                                   value="<?= $editCampagne ? htmlspecialchars($editCampagne['dateFin'] ?? '') : '' ?>">
                            <div class="error-msg" id="dateFinError">End date must be after start date.</div>
                        </div>
                        <div class="form-group">
                            <label for="budget">Budget (€) *</label>
                            <div class="input-with-prefix">
                                <span class="prefix">€</span>
                                <input type="text" id="budget" name="budget" class="form-input" autocomplete="off"
                                       value="<?= $editCampagne ? htmlspecialchars($editCampagne['budget'] ?? '') : '' ?>">
                            </div>
                            <div class="error-msg" id="budgetError">Budget is required (number ≥ 0, max 2 decimals).</div>
                            <div class="field-hint">ℹ️ Use point or comma as decimal. Ex: 1500 or 1500.00</div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-submit">
                            <?= $editCampagne ? '💾 Save Changes' : '🚀 Launch Campaign' ?>
                        </button>
                        <?php if ($editCampagne): ?>
                        <a href="index.php" class="btn-cancel">Cancel</a>
                        <?php endif; ?>
                        <span style="font-size:12px;color:var(--text-dim);margin-left:auto">* Required fields</span>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div><!-- /page-wrapper -->

<!-- DELETE MODAL -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal-box">
        <div class="modal-title">🗑 Delete Campaign?</div>
        <div class="modal-text" id="deleteModalText">Are you sure you want to delete this campaign? This action cannot be undone.</div>
        <div class="modal-actions">
            <button class="btn-modal-cancel" onclick="closeDeleteModal()">Cancel</button>
            <a href="#" class="btn-modal-del" id="deleteModalLink">Yes, delete</a>
        </div>
    </div>
</div>

<script>
// ── FLASH AUTO-HIDE ────────────────────────────────────────────────────────────
const flashEl = document.getElementById('flashMsg');
if (flashEl) setTimeout(() => { flashEl.style.transition = 'opacity .4s'; flashEl.style.opacity = '0'; setTimeout(() => flashEl.remove(), 400); }, 3500);

// ── FILTER / SEARCH ────────────────────────────────────────────────────────────
document.getElementById('searchInput').addEventListener('input', filterCampagnes);

function filterCampagnes() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    const s = document.getElementById('filterStatut').value;
    const cards = document.querySelectorAll('#campGrid .camp-card');
    let visible = 0;
    cards.forEach(card => {
        const matchQ = !q || (card.dataset.titre || '').includes(q);
        const matchS = !s || card.dataset.statut === s;
        const show = matchQ && matchS;
        card.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    const countEl = document.getElementById('visibleCount');
    if (countEl) countEl.textContent = visible;
}

function sortCampagnes() {
    const mode = document.getElementById('sortSelect').value;
    const grid = document.getElementById('campGrid');
    if (!grid || !mode) return;
    const cards = Array.from(grid.querySelectorAll('.camp-card'));
    cards.sort((a, b) => {
        if (mode === 'titre')       return (a.dataset.titre || '').localeCompare(b.dataset.titre || '');
        if (mode === 'budget_asc')  return parseFloat(a.dataset.budget) - parseFloat(b.dataset.budget);
        if (mode === 'budget_desc') return parseFloat(b.dataset.budget) - parseFloat(a.dataset.budget);
        if (mode === 'date')        return (a.dataset.date || '').localeCompare(b.dataset.date || '');
        return 0;
    });
    cards.forEach(c => grid.appendChild(c));
}

function resetFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('filterStatut').value = '';
    document.getElementById('sortSelect').value = '';
    filterCampagnes();
}

// ── AJAX ARCHIVE ───────────────────────────────────────────────────────────────
function toggleArchive(id) {
    fetch('index.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=archive&id=' + id
    }).then(() => location.reload());
}

// ── DELETE MODAL ───────────────────────────────────────────────────────────────
function openDeleteModal(id, titre) {
    document.getElementById('deleteModalText').textContent = 'Delete "' + titre + '"? This cannot be undone.';
    document.getElementById('deleteModalLink').href = 'index.php?supprimer=' + id;
    document.getElementById('deleteModal').classList.add('open');
}
function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('open');
}
document.getElementById('deleteModal').addEventListener('click', e => {
    if (e.target === document.getElementById('deleteModal')) closeDeleteModal();
});
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDeleteModal(); });

// ── ARCH SECTION TOGGLE ────────────────────────────────────────────────────────
function toggleArchSection(btn) {
    const sec = document.getElementById('archSection');
    sec.classList.toggle('open');
    btn.querySelector('span').textContent = sec.classList.contains('open') ? '▲' : '▼';
}

// ── FORM VALIDATION ────────────────────────────────────────────────────────────
document.getElementById('campagneForm').addEventListener('submit', function(e) {
    let valid = true;

    function showErr(id, inputId) {
        document.getElementById(id).classList.add('visible');
        if (inputId) document.getElementById(inputId).classList.add('error-field');
        valid = false;
    }
    function clearErr(id, inputId) {
        document.getElementById(id).classList.remove('visible');
        if (inputId) document.getElementById(inputId).classList.remove('error-field');
    }

    // Titre
    const titre = document.getElementById('titre').value.trim();
    titre.length < 2 ? showErr('titreError', 'titre') : clearErr('titreError', 'titre');

    // Description
    const desc = document.getElementById('description').value.trim();
    desc.length < 10 ? showErr('descError', 'description') : clearErr('descError', 'description');

    // Budget
    const budgetRaw = document.getElementById('budget').value.trim();
    const budgetVal = budgetRaw.replace(',', '.');
    const budgetNum = parseFloat(budgetVal);
    const budgetOk  = budgetRaw !== '' && !isNaN(budgetNum) && budgetNum >= 0 && /^\d+([.,]\d{1,2})?$/.test(budgetRaw);
    if (!budgetOk) {
        showErr('budgetError', 'budget');
    } else {
        clearErr('budgetError', 'budget');
        document.getElementById('budget').value = budgetVal;
    }

    // Dates
    const reDate = /^\d{4}-\d{2}-\d{2}$/;
    const dateDebut = document.getElementById('dateDebut').value.trim();
    const dateFin   = document.getElementById('dateFin').value.trim();

    if (dateDebut && !reDate.test(dateDebut)) {
        showErr('dateDebutError', 'dateDebut');
    } else {
        clearErr('dateDebutError', 'dateDebut');
    }

    if (dateFin && !reDate.test(dateFin)) {
        document.getElementById('dateFinError').textContent = 'Enter a valid end date (YYYY-MM-DD).';
        showErr('dateFinError', 'dateFin');
    } else if (dateDebut && dateFin && dateFin < dateDebut) {
        document.getElementById('dateFinError').textContent = 'End date must be after start date.';
        showErr('dateFinError', 'dateFin');
    } else {
        clearErr('dateFinError', 'dateFin');
    }

    if (!valid) e.preventDefault();
});

// ── CHAR COUNTERS ──────────────────────────────────────────────────────────────
function setupCounter(inputId, counterId, max) {
    const el = document.getElementById(inputId);
    const co = document.getElementById(counterId);
    if (!el || !co) return;
    const update = () => { co.textContent = el.value.length + ' / ' + max; };
    el.addEventListener('input', update);
    update();
}
setupCounter('titre', 'titreCounter', 100);
setupCounter('description', 'descCounter', 600);
setupCounter('objectif', 'objCounter', 200);

// ── BUDGET RESTRICT ────────────────────────────────────────────────────────────
document.getElementById('budget').addEventListener('input', function() {
    let v = this.value.replace(/[^0-9.,]/g, '');
    const parts = v.split(/[.,]/);
    if (parts.length > 2) v = parts[0] + '.' + parts.slice(1).join('');
    if (parts[1] && parts[1].length > 2) v = parts[0] + '.' + parts[1].substring(0, 2);
    this.value = v;
});

// ── DATE MASK ──────────────────────────────────────────────────────────────────
function applyDateMask(id) {
    document.getElementById(id).addEventListener('input', function() {
        let v = this.value.replace(/[^0-9]/g, '');
        if (v.length > 4 && v.length <= 6) v = v.slice(0,4) + '-' + v.slice(4);
        else if (v.length > 6) v = v.slice(0,4) + '-' + v.slice(4,6) + '-' + v.slice(6,8);
        this.value = v;
    });
}
applyDateMask('dateDebut');
applyDateMask('dateFin');

// ── COMPLETION BAR ─────────────────────────────────────────────────────────────
function updateCompletion() {
    const fields = [
        document.getElementById('titre').value.trim(),
        document.getElementById('description').value.trim(),
        document.getElementById('budget').value.trim(),
        document.getElementById('dateDebut').value.trim(),
        document.getElementById('dateFin').value.trim(),
        document.getElementById('objectif').value.trim(),
    ];
    const filled = fields.filter(f => f.length > 0).length;
    const pct = Math.round((filled / fields.length) * 100);
    document.getElementById('completionFill').style.width = pct + '%';
    document.getElementById('completionPct').textContent = pct + '%';
    document.getElementById('completionHint').textContent = pct === 100
        ? '✅ Perfect! Your campaign is fully defined.'
        : 'Fill all fields to give your campaign maximum visibility.';
}
['titre','description','budget','dateDebut','dateFin','objectif'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('input', updateCompletion);
});
updateCompletion();

// ── SCROLL ON EDIT ─────────────────────────────────────────────────────────────
<?php if ($editCampagne): ?>
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('formAnchor').scrollIntoView({ behavior: 'smooth', block: 'start' });
});
<?php endif; ?>
</script>
</body>
</html>