<?php
/**
 * Vue/FrontOffice/campagne/indexC.php
 * Rôle : CRÉATEUR — parcourir les campagnes + suggestions IA
 * Enhanced with: Translation, Dark/Light Mode, Pagination
 */

require_once __DIR__ . '/../../../Controleur/campagneC.php';
require_once __DIR__ . '/../../../Controleur/produitC.php';

if (session_status() === PHP_SESSION_NONE) session_start();
$frontActive = 'campaigns';

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

// AJAX : produits d'une campagne (lecture seule)
if (isset($_GET['ajax_produits_creator'])) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($produitC->getProduitsByCampagne(intval($_GET['ajax_produits_creator'])), JSON_UNESCAPED_UNICODE);
    exit;
}

// IA : suggestions
$iaResult = null;
$iaError  = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'ia_suggestions') {
    $comp = trim($_POST['competences'] ?? '');
    $int  = trim($_POST['interets'] ?? '');
    $aud  = trim($_POST['audience'] ?? '');
    if ($comp && $int && $aud) {
        $iaResult = $campagneC->suggererCampagnesIA($comp, $int, $aud);
        if (!$iaResult) $iaError = "L'IA n'a pas pu générer de suggestions. Réessayez.";
    } else {
        $iaError = "Remplissez tous les champs.";
    }
}

$campagnes      = $campagneC->afficherCampagnes();
$totalCampagnes = count($campagnes);
$nbActives      = count(array_filter($campagnes, fn($c) => $c['statut'] === 'active'));
$budgetTotal    = array_sum(array_column($campagnes, 'budget'));

$campagneDetail = null;
if (isset($_GET['voir'])) {
    $campagneDetail = $campagneC->recupererCampagne(intval($_GET['voir']));
}

function statutLabel($s) { return match($s) { 'active'=>'✅ Active', 'terminee'=>'🏁 Terminée', 'annulee'=>'❌ Annulée', default=>'📝 Brouillon' }; }
function statutColor($s) { return match($s) { 'active'=>'#0ea370', 'terminee'=>'#3b82f6', 'annulee'=>'#f43f5e', default=>'#f59e0b' }; }
function statutBg($s)    { return match($s) { 'active'=>'#edfaf5', 'terminee'=>'#eff6ff', 'annulee'=>'#fff1f3', default=>'#fffbeb' }; }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<?php require_once __DIR__ . '/../layout/front-theme-bootstrap.php'; ?>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Campagnes disponibles — Cre8Connect</title>
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
html[data-theme="dark"] .hero,
body.dark-mode .hero {
    background: linear-gradient(130deg,#0a091a 0%,#12103a 60%,#1e1558 100%);
}
html[data-theme="dark"] .detail-box,
html[data-theme="dark"] .detail-modal > .detail-box,
body.dark-mode .detail-box,
body.dark-mode .detail-modal > .detail-box {
    background: var(--white);
}
/* ===== END DARK MODE VARIABLES ===== */

*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text-main);min-height:100vh;transition:background .3s,color .3s;}
.nav-logo{font-family:'Fraunces',serif;font-size:20px;font-weight:800;color:var(--primary);text-decoration:none;}
.nav-links{display:flex;gap:6px;list-style:none;}
.nav-links a{text-decoration:none;color:var(--text-sub);font-size:13.5px;font-weight:600;padding:6px 14px;border-radius:8px;transition:all .18s;}
.nav-links a:hover,.nav-links a.active{background:var(--primary-light);color:var(--primary);}
.nav-right{display:flex;align-items:center;gap:12px;}
.nav-badge{background:linear-gradient(135deg,var(--primary),#a78bfa);color:#fff;border-radius:20px;padding:5px 14px;font-size:12px;font-weight:700;}
.nav-avatar{width:36px;height:36px;border-radius:50%;background:var(--primary-light);color:var(--primary);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:14px;border:2px solid var(--primary-border);}

/* ===== ADDED FEATURE: DARK MODE TOGGLE ===== */
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

/* ===== ADDED FEATURE: LANGUAGE SWITCHER ===== */
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
.hero-lang-row {
    display: flex;
    justify-content: flex-end;
    width: 100%;
    margin-bottom: 10px;
}
.hero-inner-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 32px;
    width: 100%;
}
/* ===== END LANGUAGE SWITCHER ===== */

/* HERO — light lavender banner (Cre8Connect style) */
.hero {
    background: linear-gradient(130deg, #ece9ff 0%, #eeeaff 45%, #e4ecfd 100%);
    padding: 44px 48px;
    position: relative;
    overflow: hidden;
    transition: background .3s;
    border-bottom: 1px solid rgba(91,79,255,0.08);
}
.hero::before {
    content: '';
    position: absolute;
    top: -80px; right: -80px;
    width: 320px; height: 320px;
    background: radial-gradient(circle, rgba(91,79,255,0.12) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}
.hero::after {
    content: '';
    position: absolute;
    bottom: -40px; left: -40px;
    width: 200px; height: 200px;
    background: radial-gradient(circle, rgba(91,79,255,0.06) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}
.hero-content {
    position: relative;
    max-width: 1160px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 32px;
}
.hero-content.campaign-front {
    flex-direction: column;
    align-items: stretch;
    gap: 0;
}
.hero-content.campaign-front .hero-inner-row {
    align-items: center;
    margin-top: 0;
}
.hero-left { flex: 1; }
.hero-tag {
    display: inline-flex; align-items: center; gap: 6px;
    background: rgba(91,79,255,0.1); border: 1px solid rgba(91,79,255,0.2);
    border-radius: 20px; padding: 5px 14px;
    font-size: 12px; font-weight: 700; color: var(--primary);
    margin-bottom: 16px;
}
.hero h1 {
    font-family: 'Fraunces', serif;
    font-size: 36px; font-weight: 900;
    line-height: 1.12; letter-spacing: -1px;
    color: var(--text-main);
    margin-bottom: 12px;
}
.hero p {
    font-size: 14.5px; color: var(--text-sub);
    line-height: 1.65; margin-bottom: 24px;
    max-width: 480px;
}
.hero-stats { display: flex; gap: 28px; align-items: center; flex-wrap: wrap; }
.hero-stat { display: flex; align-items: center; gap: 10px; }
.hero-stat-icon {
    width: 32px; height: 32px;
    background: var(--primary);
    border-radius: 9px;
    display: flex; align-items: center; justify-content: center;
    font-family: 'Fraunces', serif;
    font-size: 14px; font-weight: 800; color: #fff;
}
.hero-stat-info {}
.hero-stat-val {
    font-family: 'Fraunces', serif;
    font-size: 20px; font-weight: 800;
    color: var(--text-main); line-height: 1;
}
.hero-stat-label { font-size: 12px; color: var(--text-sub); font-weight: 500; margin-top: 1px; }
.hero-illus {
    font-size: 90px;
    opacity: 0.5;
    filter: drop-shadow(0 8px 24px rgba(91,79,255,0.2));
    user-select: none;
    flex-shrink: 0;
}

.page-wrapper{max-width:1200px;margin:0 auto;padding:36px 24px 80px;}

/* IA SUGGESTIONS PANEL */
.ia-panel{
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 24px 28px;
    margin-bottom: 28px;
    box-shadow: 0 2px 8px rgba(91,79,255,0.05);
    transition:background .3s;
}
.ia-panel-header{display:flex;align-items:center;gap:10px;margin-bottom:16px;}
.ia-panel-header h2{font-family:'Fraunces',serif;font-size:17px;font-weight:800;color:var(--success);}
.ia-form-grid{display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:12px;align-items:end;}
.ia-form-group{display:flex;flex-direction:column;gap:4px;}
.ia-form-group label{font-size:12px;font-weight:700;color:var(--text-sub);}
.ia-form-group input{padding:9px 13px;border:1.5px solid var(--border);border-radius:10px;font-family:'DM Sans',sans-serif;font-size:13px;background:var(--bg);color:var(--text-main);outline:none;transition:border-color .2s,background .3s;}
.ia-form-group input:focus{border-color:var(--success); background: var(--white);}
.btn-ia{display:inline-flex;align-items:center;gap:7px;background:linear-gradient(135deg,var(--success),#059669);color:#fff;border:none;border-radius:10px;padding:10px 18px;font-size:13px;font-weight:700;font-family:'DM Sans',sans-serif;cursor:pointer;white-space:nowrap;box-shadow:0 2px 8px rgba(14,163,112,0.25);}
.ia-result{background:var(--bg);border:1.5px solid var(--success-border);border-radius:12px;padding:18px;margin-top:16px;transition:background .3s;}
.ia-sug-card{background:var(--white);border:1px solid var(--success-border);border-radius:10px;padding:14px;margin-bottom:10px;box-shadow:0 1px 4px rgba(0,0,0,0.04);}
.ia-sug-type{font-weight:700;color:var(--success);margin-bottom:4px;font-size:14px;}
.ia-sug-text{font-size:13px;color:var(--text-sub);line-height:1.6;}
.ia-sug-tip{font-size:12px;background:rgba(14,163,112,.08);padding:7px 10px;border-radius:7px;margin-top:6px;color:var(--success);}
.ia-conseil{padding:12px 16px;background:rgba(14,163,112,.06);border-radius:var(--radius-sm);margin-top:12px;font-size:13px;line-height:1.6;}
.ia-error{background:var(--danger-light);color:var(--danger);border-radius:var(--radius-sm);padding:10px 14px;font-size:13px;font-weight:600;margin-top:12px;}
.spinner{width:16px;height:16px;border:2.5px solid rgba(14,163,112,.3);border-top-color:var(--success);border-radius:50%;animation:spin .6s linear infinite;}
@keyframes spin{to{transform:rotate(360deg)}}
.ia-loading{display:none;align-items:center;gap:10px;padding:12px 0;color:var(--success);font-weight:600;font-size:13px;}
.ia-loading.show{display:flex;}

/* SECTION HEAD */
.section-head {
    display: flex; align-items: center; gap: 12px;
    margin-bottom: 14px;
}
.section-head h2 {
    font-family: 'Fraunces', serif;
    font-size: 22px; font-weight: 900; letter-spacing: -0.5px;
}
.count-pill {
    background: var(--primary-light); color: var(--primary);
    border-radius: 20px; padding: 4px 13px; font-size: 12px; font-weight: 700;
}

/* STATUS CHIPS CARD */
.status-chips-card {
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 14px 20px;
    margin-bottom: 12px;
    display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
    box-shadow: 0 1px 4px rgba(0,0,0,0.04);
}
.chips-label {
    font-size: 13px; font-weight: 600; color: var(--text-sub);
    margin-right: 4px; white-space: nowrap;
}
.status-chips { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 0; }
.s-chip {
    display: inline-flex; align-items: center; gap: 5px;
    background: var(--bg); border: 1.5px solid var(--border);
    border-radius: 20px; padding: 6px 16px;
    font-size: 13px; font-weight: 600; cursor: pointer;
    font-family: 'DM Sans', sans-serif; transition: all .18s; color: var(--text-sub);
}
.s-chip:hover { border-color: var(--primary); color: var(--primary); background: var(--primary-light); }
.s-chip.active { background: var(--primary); color: #fff; border-color: var(--primary); box-shadow: 0 2px 8px var(--primary-glow); }

/* FILTER BAR */
.filter-bar {
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 14px 20px;
    margin-bottom: 24px;
    display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
    box-shadow: 0 1px 4px rgba(0,0,0,0.04);
    transition: background .3s;
}
.search-wrap { position: relative; flex: 1; min-width: 200px; }
.search-wrap svg { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-dim); pointer-events: none; width: 15px; height: 15px; }
.search-input {
    width: 100%; background: var(--bg); border: 1.5px solid var(--border);
    border-radius: 10px; padding: 9px 12px 9px 36px;
    font-size: 13px; font-family: 'DM Sans', sans-serif;
    outline: none; transition: border-color .2s, box-shadow .2s, background .3s; color: var(--text-main);
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
}
.search-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-glow); background: var(--white); }
.search-input::placeholder { color: var(--text-dim); }
.filter-select {
    background: var(--bg); border: 1.5px solid var(--border);
    border-radius: 10px; padding: 9px 14px;
    font-size: 13px; font-family: 'DM Sans', sans-serif;
    outline: none; cursor: pointer; color: var(--text-sub);
    transition: background .3s, border-color .2s;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
}
.filter-select:focus { border-color: var(--primary); }
.result-count { font-size: 12.5px; color: var(--text-sub); font-weight: 600; white-space: nowrap; }
.btn-reset-filters {
    background: transparent; color: var(--text-sub);
    border: none; font-size: 13px; font-weight: 600;
    font-family: 'DM Sans', sans-serif; cursor: pointer;
    padding: 6px 2px; transition: color .18s; white-space: nowrap;
}
.btn-reset-filters:hover { color: var(--primary); }

/* CAMP GRID */
.camp-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; margin-bottom: 28px; }
.camp-card {
    background: var(--white); border: 1px solid var(--border);
    border-radius: 16px; overflow: hidden;
    box-shadow: 0 2px 8px rgba(15,14,26,0.05), 0 0 0 1px var(--border);
    display: flex; flex-direction: column;
    transition: transform .22s, box-shadow .22s, border-color .22s, background .3s;
    position: relative;
}
.camp-card:hover { transform: translateY(-4px); box-shadow: 0 12px 36px rgba(91,79,255,0.13); border-color: var(--primary-border); }
.camp-accent { height: 4px; border-radius: 0; }
.accent-active    { background: linear-gradient(90deg, #0ea370, #34d399); }
.accent-brouillon { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
.accent-terminee  { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
.accent-annulee   { background: linear-gradient(90deg, #f43f5e, #fb7185); }
.camp-card-header { padding: 18px 20px 0; display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; }
.camp-card-title { font-family: 'Fraunces', serif; font-size: 16px; font-weight: 900; flex: 1; letter-spacing: -0.2px; }
.camp-badge { display: inline-flex; align-items: center; font-size: 11px; font-weight: 700; padding: 4px 11px; border-radius: 20px; flex-shrink: 0; }
.camp-card-body { padding: 12px 20px 16px; flex: 1; display: flex; flex-direction: column; gap: 10px; }
.camp-desc { font-size: 13px; color: var(--text-sub); line-height: 1.65; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; }
.camp-obj { font-size: 12px; background: var(--primary-light); color: var(--primary); border-radius: 20px; padding: 6px 13px; font-weight: 600; }
.camp-meta { font-size: 12px; color: var(--text-sub); }
.camp-budget { font-family: 'Fraunces', serif; font-size: 24px; font-weight: 900; color: var(--primary); letter-spacing: -0.5px; }
.camp-prod-pill { display: inline-flex; align-items: center; gap: 5px; border-radius: 20px; padding: 4px 13px; font-size: 12px; font-weight: 700; }
.camp-prod-pill.has { background: var(--success-light); color: var(--success); border: 1px solid var(--success-border); }
.camp-prod-pill.none { background: var(--bg); color: var(--text-dim); border: 1px solid var(--border); }
.camp-card-footer { padding: 13px 20px; border-top: 1px solid var(--border); display: flex; gap: 8px; }
.btn-detail {
    flex: 1; padding: 9px;
    background: var(--primary); color: #fff; border: none;
    border-radius: 10px; font-size: 13px; font-weight: 700;
    font-family: 'DM Sans', sans-serif; cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: 6px;
    box-shadow: 0 2px 8px var(--primary-glow);
    transition: background .2s, transform .15s;
}
.btn-detail:hover { background: var(--primary-hover); transform: translateY(-1px); }
.btn-apply {
    padding: 9px 14px;
    background: var(--success-light); color: var(--success);
    border: 1px solid var(--success-border); border-radius: 10px;
    font-size: 12px; font-weight: 700; cursor: pointer;
    font-family: 'DM Sans', sans-serif; transition: background .18s;
}
.btn-apply:hover { background: #c7f5e6; }

.empty-state { text-align: center; padding: 64px 24px; background: var(--white); border-radius: 16px; border: 1.5px dashed var(--border); }
.no-results { text-align: center; padding: 48px; background: var(--white); border-radius: 16px; border: 1px solid var(--border); }

/* DETAIL MODAL */
.detail-modal{position:fixed;inset:0;background:rgba(15,14,26,.55);z-index:300;display:none;align-items:center;justify-content:center;padding:20px;overflow-y:auto;}
.detail-modal.open{display:flex;}
.detail-box{background:var(--white);border-radius:20px;width:780px;max-width:100%;box-shadow:0 24px 80px rgba(15,14,26,.22);animation:popIn .22s ease;display:flex;flex-direction:column;margin:auto;transition:background .3s;}
@keyframes popIn{from{opacity:0;transform:scale(.94);}to{opacity:1;transform:scale(1);}}
.detail-header{padding:24px 28px 0;display:flex;align-items:flex-start;justify-content:space-between;gap:12px;}
.detail-title{font-family:'Fraunces',serif;font-size:22px;font-weight:900;line-height:1.2;letter-spacing:-0.4px;}
.detail-close{background:var(--bg);border:1.5px solid var(--border);border-radius:50%;width:34px;height:34px;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:14px;color:var(--text-sub);transition:all .18s;}
.detail-close:hover{border-color:var(--danger);color:var(--danger);}
.detail-body{padding:18px 28px 24px;display:flex;flex-direction:column;gap:16px;}
.detail-section-label{font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-dim);margin-bottom:6px;}
.detail-text{font-size:14px;color:var(--text-sub);line-height:1.7;}
.detail-meta-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.detail-meta-card{background:var(--bg);border-radius:12px;padding:14px 16px;border:1px solid var(--border);transition:background .3s;}
.detail-meta-val{font-family:'Fraunces',serif;font-size:20px;font-weight:800;margin-bottom:3px;}
.detail-meta-label{font-size:11px;color:var(--text-dim);font-weight:600;text-transform:uppercase;letter-spacing:.05em;}
.detail-products-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px;margin-top:12px;}
.detail-prod-card{background:var(--bg);border:1px solid var(--border);border-radius:12px;overflow:hidden;transition:border-color .2s,background .3s,box-shadow .2s;}
.detail-prod-card:hover{border-color:var(--primary-border);box-shadow:0 4px 12px rgba(91,79,255,0.1);}
.detail-prod-img{height:100px;background:linear-gradient(135deg,#ede9ff,#f5f3ff);display:flex;align-items:center;justify-content:center;font-size:28px;overflow:hidden;}
.detail-prod-img img{width:100%;height:100%;object-fit:cover;}
.detail-prod-body{padding:8px 10px;}
.detail-prod-name{font-size:12px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.detail-prod-price{font-family:'Fraunces',serif;font-size:13px;font-weight:800;color:var(--primary);}
.prod-loader{text-align:center;padding:24px;color:var(--text-dim);font-size:13px;grid-column:1/-1;}
.detail-footer{padding:16px 28px 22px;border-top:1px solid var(--border);display:flex;gap:10px;}
.btn-apply-big{flex:1;padding:12px;background:var(--primary);color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:700;font-family:'DM Sans',sans-serif;cursor:pointer;box-shadow:0 3px 10px var(--primary-glow);transition:background .2s;}
.btn-apply-big:hover{background:var(--primary-hover);}
.btn-close-detail{padding:12px 20px;background:var(--bg);color:var(--text-sub);border:1.5px solid var(--border);border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;font-family:'DM Sans',sans-serif;transition:all .18s;}
.btn-close-detail:hover{border-color:var(--text-sub);}

/* ===== ADDED FEATURE: PAGINATION ===== */
.pagination-bar {
    display: flex; align-items: center; justify-content: center;
    gap: 6px; margin-bottom: 32px; flex-wrap: wrap;
}
.pagination-info { font-size: 13px; color: var(--text-sub); margin-right: 8px; }
.page-btn {
    background: var(--white); border: 1.5px solid var(--border);
    border-radius: 10px; padding: 7px 13px; font-size: 13px; font-weight: 600;
    cursor: pointer; color: var(--text-sub); font-family: 'DM Sans', sans-serif;
    transition: all .18s; min-width: 36px; text-align: center;
}
.page-btn:hover { border-color: var(--primary); color: var(--primary); }
.page-btn.active { background: var(--primary); color: #fff; border-color: var(--primary); }
.page-btn:disabled { opacity: .4; cursor: not-allowed; pointer-events: none; }
.per-page-select {
    background: var(--bg); border: 1.5px solid var(--border);
    border-radius: 10px; padding: 8px 12px; font-size: 12px;
    font-family: 'DM Sans', sans-serif; cursor: pointer;
    color: var(--text-sub); outline: none; transition: background .3s;
}
/* ===== END PAGINATION ===== */
</style>
    <!-- Shared FrontOffice header assets (after page CSS so front-header wins on shared tokens) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../layout/front-header.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../layout/front-header.css')); ?>">
<link rel="icon" type="image/png" sizes="32x32" href="../../public/images/logo.png">
<link rel="shortcut icon" type="image/png" href="../../public/images/logo.png">
<link rel="apple-touch-icon" href="../../public/images/logo.png">
</head>
<body>
<?php require_once __DIR__ . '/../layout/header.php'; ?>

<!-- HERO -->
<div class="hero">
    <div class="hero-content campaign-front">
        <div class="hero-lang-row">
            <div class="front-lang-switch" role="group" aria-label="Language">
                <button type="button" class="front-lang-btn" data-lang-choice="en" aria-pressed="false">EN</button>
                <button type="button" class="front-lang-btn" data-lang-choice="fr" aria-pressed="false">FR</button>
            </div>
        </div>
        <div class="hero-inner-row">
        <div class="hero-left">
            <div class="hero-tag">⚡ <span data-i18n="hero_tag">Campagnes disponibles</span></div>
            <h1 data-i18n="hero_title">Découvrez les Campagnes des Marques</h1>
            <p data-i18n="hero_subtitle">Parcourez les campagnes actives, explorez les produits liés et postulez pour collaborer.</p>
            <div class="hero-stats">
                <div class="hero-stat">
                    <div class="hero-stat-icon"><?= $totalCampagnes ?></div>
                    <div class="hero-stat-info">
                        <div class="hero-stat-val" style="font-size:16px;"><?= $totalCampagnes ?></div>
                        <div class="hero-stat-label" data-i18n="stat_campaigns">Campagnes</div>
                    </div>
                </div>
                <div class="hero-stat">
                    <div class="hero-stat-icon" style="background:var(--success);"><?= $nbActives ?></div>
                    <div class="hero-stat-info">
                        <div class="hero-stat-val" style="font-size:16px;"><?= $nbActives ?></div>
                        <div class="hero-stat-label" data-i18n="stat_active_now">Actives maintenant</div>
                    </div>
                </div>
                <div class="hero-stat">
                    <div class="hero-stat-icon" style="background:var(--warning);font-size:11px;"><?= number_format($budgetTotal/1000,0) ?>K</div>
                    <div class="hero-stat-info">
                        <div class="hero-stat-val" style="font-size:16px;"><?= number_format($budgetTotal/1000,0) ?>K€</div>
                        <div class="hero-stat-label" data-i18n="stat_total_budget">Budget total</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="hero-illus">📋</div>
        </div>
    </div>
</div>

<div class="page-wrapper">

    <!-- IA SUGGESTIONS -->
    <div class="ia-panel">
        <div class="ia-panel-header">
            <span style="font-size:22px;">💡</span>
            <h2 data-i18n="ia_title">Suggestions de campagnes personnalisées par l'IA</h2>
        </div>
        <form method="POST" id="iaForm">
            <input type="hidden" name="action" value="ia_suggestions">
            <div class="ia-form-grid">
                <div class="ia-form-group">
                    <label data-i18n="ia_skills_label">Vos compétences *</label>
                    <input type="text" name="competences" data-i18n-placeholder="ia_skills_placeholder" placeholder="Ex : Vidéo, photo, rédaction" value="<?= htmlspecialchars($_POST['competences'] ?? '') ?>">
                </div>
                <div class="ia-form-group">
                    <label data-i18n="ia_interests_label">Vos centres d'intérêt *</label>
                    <input type="text" name="interets" data-i18n-placeholder="ia_interests_placeholder" placeholder="Ex : Mode, tech, bien-être" value="<?= htmlspecialchars($_POST['interets'] ?? '') ?>">
                </div>
                <div class="ia-form-group">
                    <label data-i18n="ia_audience_label">Votre audience *</label>
                    <input type="text" name="audience" data-i18n-placeholder="ia_audience_placeholder" placeholder="Ex : 18-25 ans, Instagram, 10K abonnés" value="<?= htmlspecialchars($_POST['audience'] ?? '') ?>">
                </div>
                <button type="submit" class="btn-ia" onclick="document.getElementById('iaLoading').classList.add('show')">
                    💡 <span data-i18n="ia_btn">Obtenir suggestions</span>
                </button>
            </div>
        </form>
        <div class="ia-loading" id="iaLoading"><div class="spinner"></div> <span data-i18n="ia_loading">L'IA analyse votre profil…</span></div>
        <?php if ($iaError): ?><div class="ia-error">⚠️ <?= htmlspecialchars($iaError) ?></div><?php endif; ?>
        <?php if ($iaResult): ?>
        <div class="ia-result">
            <div style="font-size:14px;font-weight:700;color:var(--success);margin-bottom:12px;">🎯 <span data-i18n="ia_result_title">Campagnes recommandées pour vous</span></div>
            <?php if (!empty($iaResult['suggestions'])): foreach ($iaResult['suggestions'] as $s): ?>
            <div class="ia-sug-card">
                <div class="ia-sug-type"><?= htmlspecialchars($s['type_campagne'] ?? '') ?></div>
                <div class="ia-sug-text"><strong data-i18n="ia_why">Pourquoi :</strong> <?= htmlspecialchars($s['raison'] ?? '') ?></div>
                <div class="ia-sug-tip">💡 <?= htmlspecialchars($s['conseil'] ?? '') ?></div>
            </div>
            <?php endforeach; endif; ?>
            <?php if (!empty($iaResult['conseil_general'])): ?>
            <div class="ia-conseil">📌 <strong data-i18n="ia_advice">Conseil :</strong> <?= htmlspecialchars($iaResult['conseil_general']) ?></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- STATUS CHIPS CARD -->
    <div class="status-chips-card">
        <span class="chips-label" data-i18n="filter_status_label">Statut :</span>
        <div class="status-chips">
            <button class="s-chip active" onclick="filterChip('',this)" data-i18n="chip_all">Toutes</button>
            <button class="s-chip" onclick="filterChip('active',this)" data-i18n="chip_active">✅ Actives</button>
            <button class="s-chip" onclick="filterChip('brouillon',this)" data-i18n="chip_draft">📝 Brouillons</button>
            <button class="s-chip" onclick="filterChip('terminee',this)" data-i18n="chip_done">🏁 Terminées</button>
        </div>
    </div>

    <!-- FILTER BAR -->
    <div class="filter-bar">
        <div class="search-wrap">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
            <input type="text" id="searchInput" class="search-input" data-i18n-placeholder="search_placeholder" placeholder="Rechercher campagnes, marques, objectifs…">
        </div>
        <select id="sortSelect" class="filter-select" onchange="sortCampagnes()">
            <option value="" data-i18n="sort_default">Trier par…</option>
            <option value="titre" data-i18n="sort_name">Nom A→Z</option>
            <option value="budget_desc" data-i18n="sort_budget_desc">Budget ↓</option>
            <option value="budget_asc" data-i18n="sort_budget_asc">Budget ↑</option>
        </select>
        <select id="perPageSelect" class="per-page-select" onchange="changePerPage()">
            <option value="6">6 / page</option>
            <option value="9" selected>9 / page</option>
            <option value="12">12 / page</option>
            <option value="999" data-i18n="show_all">Tout afficher</option>
        </select>
        <span class="result-count"><span id="visibleCount"><?= $totalCampagnes ?></span> <span data-i18n="result_count_label">campagne(s)</span></span>
        <button class="btn-reset-filters" onclick="resetFilters()" data-i18n="btn_reset_filters">Réinitialiser</button>
    </div>

    <!-- CAMP GRID -->
    <div class="section-head">
        <h2 data-i18n="section_all_campaigns">Toutes les campagnes</h2>
        <span class="count-pill"><?= $totalCampagnes ?> campagnes</span>
    </div>

    <?php if (empty($campagnes)): ?>
    <div class="empty-state">
        <div style="font-size:52px;margin-bottom:16px;">⚡</div>
        <h3 style="font-family:'Fraunces',serif;font-size:20px;font-weight:800;margin-bottom:8px;" data-i18n="empty_title">Aucune campagne disponible</h3>
        <p style="color:var(--text-sub);" data-i18n="empty_subtitle">Les marques publient régulièrement de nouvelles campagnes. Revenez bientôt !</p>
    </div>
    <?php else: ?>
    <div class="camp-grid" id="campGrid">
        <?php foreach ($campagnes as $c):
            $nbProd = $campagneC->compterProduitsCampagne($c['idCampagne']);
        ?>
        <div class="camp-card"
             data-id="<?= $c['idCampagne'] ?>"
             data-titre="<?= strtolower(htmlspecialchars($c['titreCampagne'])) ?>"
             data-statut="<?= htmlspecialchars($c['statut']) ?>"
             data-budget="<?= (float)$c['budget'] ?>"
             data-brand="<?= strtolower(htmlspecialchars($c['nomMarque'] ?? '')) ?>">
            <div class="camp-accent accent-<?= $c['statut'] ?>"></div>
            <div class="camp-card-header">
                <div class="camp-card-title"><?= htmlspecialchars($c['titreCampagne']) ?></div>
                <span class="camp-badge" style="background:<?= statutBg($c['statut']) ?>;color:<?= statutColor($c['statut']) ?>"><?= statutLabel($c['statut']) ?></span>
            </div>
            <div class="camp-card-body">
                <div class="camp-desc"><?= htmlspecialchars($c['description']) ?></div>
                <?php if (!empty($c['objectif'])): ?><div class="camp-obj">🎯 <?= htmlspecialchars($c['objectif']) ?></div><?php endif; ?>
                <div class="camp-meta">
                    📅 <?= $c['dateDebut'] ?? '—' ?> → 🏁 <?= $c['dateFin'] ?? '—' ?>
                    <?php if (!empty($c['nomMarque'])): ?><br>🏢 <?= htmlspecialchars($c['nomMarque']) ?><?php endif; ?>
                </div>
                <div class="camp-budget"><?= number_format((float)$c['budget'], 2, ',', ' ') ?> €</div>
                <span class="camp-prod-pill <?= $nbProd > 0 ? 'has' : 'none' ?>">
                    📦 <?= $nbProd ?> <span data-i18n="products_linked">produit<?= $nbProd !== 1 ? 's' : '' ?> lié<?= $nbProd !== 1 ? 's' : '' ?></span>
                </span>
            </div>
            <div class="camp-card-footer">
                <button class="btn-detail" onclick="openDetail(<?= $c['idCampagne'] ?>)">
                    👁 <span data-i18n="btn_details">Voir les détails</span>
                </button>
                <?php if ($c['statut'] === 'active'): ?>
                <button class="btn-apply" onclick="alert('Candidature envoyée !')">🙋 <span data-i18n="btn_apply">Postuler</span></button>
                <?php endif; ?>
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

    <div id="noResults" class="no-results" style="display:none;">
        <div style="font-size:36px;margin-bottom:12px;">🔍</div>
        <h3 style="font-family:'Fraunces',serif;font-size:18px;font-weight:800;margin-bottom:6px;" data-i18n="no_results_title">Aucune campagne trouvée</h3>
        <p style="color:var(--text-sub);" data-i18n="no_results_subtitle">Modifiez vos filtres ou votre recherche.</p>
    </div>
    <?php endif; ?>

</div>

<!-- DETAIL MODAL -->
<div class="detail-modal" id="detailModal">
    <div class="detail-box">
        <div class="detail-header">
            <div>
                <div id="detailBadge" class="camp-badge" style="margin-bottom:8px;font-size:12px;"></div>
                <div class="detail-title" id="detailTitle"></div>
                <div id="detailMarque" style="margin-top:8px;font-size:12px;color:var(--text-sub);"></div>
            </div>
            <button class="detail-close" onclick="closeDetail()">✕</button>
        </div>
        <div class="detail-body">
            <div><div class="detail-section-label" data-i18n="modal_desc_label">Description</div><div class="detail-text" id="detailDesc"></div></div>
            <div id="detailObjWrap"><div class="detail-section-label" data-i18n="modal_obj_label">Objectif</div><div class="camp-obj" id="detailObj"></div></div>
            <div class="detail-meta-grid">
                <div class="detail-meta-card"><div class="detail-meta-val" id="detailBudget"></div><div class="detail-meta-label" data-i18n="modal_budget_label">Budget de la campagne</div></div>
                <div class="detail-meta-card"><div class="detail-meta-val" id="detailDates" style="font-size:14px;"></div><div class="detail-meta-label" data-i18n="modal_period_label">Période</div></div>
            </div>
            <div>
                <div class="detail-section-label" data-i18n="modal_products_label">Produits de cette campagne</div>
                <div class="detail-products-grid" id="detailProductsGrid"><div class="prod-loader">⏳ <span data-i18n="loading">Chargement…</span></div></div>
            </div>
        </div>
        <div class="detail-footer">
            <button class="btn-apply-big" id="detailApplyBtn" onclick="alert('Candidature envoyée !')">🙋 <span data-i18n="btn_apply_campaign">Postuler à cette campagne</span></button>
            <button class="btn-close-detail" onclick="closeDetail()" data-i18n="btn_close">Fermer</button>
        </div>
    </div>
</div>

<script>
const BASE_URL = <?= json_encode($baseUrl, JSON_UNESCAPED_SLASHES) ?>;

const campagnesMap = {};
<?php foreach ($campagnes as $c): ?>
campagnesMap[<?= $c['idCampagne'] ?>] = {
    id: <?= $c['idCampagne'] ?>,
    titre: <?= json_encode($c['titreCampagne']) ?>,
    desc:  <?= json_encode($c['description'] ?? '') ?>,
    obj:   <?= json_encode($c['objectif'] ?? '') ?>,
    budget: <?= (float)$c['budget'] ?>,
    debut: <?= json_encode($c['dateDebut'] ?? '') ?>,
    fin:   <?= json_encode($c['dateFin'] ?? '') ?>,
    statut: <?= json_encode($c['statut']) ?>,
    marque: <?= json_encode($c['nomMarque'] ?? '') ?>,
};
<?php endforeach; ?>

const sLabels = {active:'✅ Active',brouillon:'📝 Brouillon',terminee:'🏁 Terminée',annulee:'❌ Annulée'};
const sColors = {active:'#0ea370',brouillon:'#f59e0b',terminee:'#3b82f6',annulee:'#f43f5e'};
const sBgs    = {active:'#edfaf5',brouillon:'#fffbeb',terminee:'#eff6ff',annulee:'#fff1f3'};

// Theme: ../layout/front-header.js (cre8_theme, html data-theme). Sync script + MutationObserver below.

// ===== ADDED FEATURE: TRANSLATION SYSTEM =====
const translations = {
    fr: {
        nav_offres: 'Offres',
        nav_campagnes: 'Campagnes',
        nav_produits: 'Produits',
        nav_contrats: 'Contrats',
        role_creator: 'Créateur',
        theme_dark: 'Mode sombre',
        theme_light: 'Mode clair',
        hero_tag: 'Campagnes disponibles',
        hero_title: 'Découvrez les Campagnes des Marques',
        hero_subtitle: 'Parcourez les campagnes actives, explorez les produits liés et postulez pour collaborer.',
        stat_campaigns: 'Campagnes',
        stat_active_now: 'Actives maintenant',
        stat_total_budget: 'Budget total',
        ia_title: "Suggestions de campagnes personnalisées par l'IA",
        ia_skills_label: 'Vos compétences *',
        ia_skills_placeholder: 'Ex : Vidéo, photo, rédaction',
        ia_interests_label: "Vos centres d'intérêt *",
        ia_interests_placeholder: 'Ex : Mode, tech, bien-être',
        ia_audience_label: 'Votre audience *',
        ia_audience_placeholder: 'Ex : 18-25 ans, Instagram, 10K abonnés',
        ia_btn: 'Obtenir suggestions',
        ia_loading: "L'IA analyse votre profil…",
        ia_result_title: 'Campagnes recommandées pour vous',
        ia_why: 'Pourquoi :',
        ia_advice: 'Conseil :',
        chip_all: 'Toutes',
        chip_active: '✅ Actives',
        chip_draft: '📝 Brouillons',
        chip_done: '🏁 Terminées',
        search_placeholder: 'Rechercher campagnes, marques, objectifs…',
        sort_default: 'Trier par…',
        sort_name: 'Nom A→Z',
        sort_budget_desc: 'Budget ↓',
        sort_budget_asc: 'Budget ↑',
        show_all: 'Tout afficher',
        result_count_label: 'campagne(s)',
        section_all_campaigns: 'Toutes les campagnes',
        empty_title: 'Aucune campagne disponible',
        empty_subtitle: 'Les marques publient régulièrement de nouvelles campagnes. Revenez bientôt !',
        products_linked: 'produits liés',
        btn_details: 'Voir les détails',
        btn_apply: 'Postuler',
        prev: 'Préc.',
        next: 'Suiv.',
        pagination_showing: 'Affichage',
        pagination_of: 'sur',
        pagination_campaigns: 'campagnes',
        no_results_title: 'Aucune campagne trouvée',
        no_results_subtitle: 'Modifiez vos filtres ou votre recherche.',
        modal_desc_label: 'Description',
        modal_obj_label: 'Objectif',
        modal_budget_label: 'Budget de la campagne',
        modal_period_label: 'Période',
        modal_products_label: 'Produits de cette campagne',
        btn_apply_campaign: 'Postuler à cette campagne',
        btn_close: 'Fermer',
        loading: 'Chargement…',
    },
    en: {
        nav_offres: 'Offers',
        nav_campagnes: 'Campaigns',
        nav_produits: 'Products',
        nav_contrats: 'Contracts',
        role_creator: 'Creator',
        theme_dark: 'Dark mode',
        theme_light: 'Light mode',
        hero_tag: 'Available campaigns',
        hero_title: 'Discover Brand Campaigns',
        hero_subtitle: 'Browse active campaigns, explore linked products, and apply to collaborate.',
        stat_campaigns: 'Campaigns',
        stat_active_now: 'Active now',
        stat_total_budget: 'Total budget',
        ia_title: 'AI-personalized campaign suggestions',
        ia_skills_label: 'Your skills *',
        ia_skills_placeholder: 'E.g. Video, photo, writing',
        ia_interests_label: 'Your interests *',
        ia_interests_placeholder: 'E.g. Fashion, tech, wellness',
        ia_audience_label: 'Your audience *',
        ia_audience_placeholder: 'E.g. 18-25 y/o, Instagram, 10K followers',
        ia_btn: 'Get suggestions',
        ia_loading: 'AI is analyzing your profile…',
        ia_result_title: 'Recommended campaigns for you',
        ia_why: 'Why:',
        ia_advice: 'Tip:',
        chip_all: 'All',
        chip_active: '✅ Active',
        chip_draft: '📝 Drafts',
        chip_done: '🏁 Completed',
        search_placeholder: 'Search campaigns, brands, objectives…',
        sort_default: 'Sort by…',
        sort_name: 'Name A→Z',
        sort_budget_desc: 'Budget ↓',
        sort_budget_asc: 'Budget ↑',
        show_all: 'Show all',
        result_count_label: 'campaign(s)',
        section_all_campaigns: 'All campaigns',
        empty_title: 'No campaigns available',
        empty_subtitle: 'Brands publish new campaigns regularly. Check back soon!',
        products_linked: 'linked products',
        btn_details: 'View details',
        btn_apply: 'Apply',
        prev: 'Prev.',
        next: 'Next',
        pagination_showing: 'Showing',
        pagination_of: 'of',
        pagination_campaigns: 'campaigns',
        no_results_title: 'No campaigns found',
        no_results_subtitle: 'Adjust your filters or search terms.',
        modal_desc_label: 'Description',
        modal_obj_label: 'Objective',
        modal_budget_label: 'Campaign budget',
        modal_period_label: 'Period',
        modal_products_label: 'Products in this campaign',
        btn_apply_campaign: 'Apply to this campaign',
        btn_close: 'Close',
        loading: 'Loading…',
    }
};

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

    document.querySelectorAll('.s-chip').forEach(btn => {
        const key = btn.getAttribute('data-i18n');
        if (key && dict[key]) btn.textContent = dict[key];
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
let activeChip = '';

function getAllCards() {
    return Array.from(document.querySelectorAll('#campGrid .camp-card'));
}

function filterAndPaginate() {
    const q = document.getElementById('searchInput')?.value.toLowerCase() || '';
    const all = getAllCards();
    filteredCards = all.filter(card => {
        const mQ = !q || (card.dataset.titre||'').includes(q) || (card.dataset.brand||'').includes(q);
        const mS = !activeChip || card.dataset.statut === activeChip;
        return mQ && mS;
    });
    document.getElementById('visibleCount').textContent = filteredCards.length;
    const noRes = document.getElementById('noResults');
    if (noRes) noRes.style.display = filteredCards.length === 0 ? '' : 'none';
    currentPage = 1;
    renderPage();
}

function renderPage() {
    const all = getAllCards();
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
    const btnPrev  = document.getElementById('btnPrev');
    const btnNext  = document.getElementById('btnNext');
    const pageNums = document.getElementById('pageNumbers');
    if (btnPrev) btnPrev.disabled = cur <= 1;
    if (btnNext) btnNext.disabled = cur >= totalPages;
    if (!pageNums) return;
    pageNums.innerHTML = '';
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
    info.textContent = `${dict.pagination_showing} ${from}–${to} ${dict.pagination_of} ${total} ${dict.pagination_campaigns}`;
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

document.addEventListener('DOMContentLoaded', function() {
    filteredCards = getAllCards();
    renderPage();
});
// ===== END PAGINATION =====

// Chip filter — now hooks into pagination
function filterChip(val, btn) {
    activeChip = val;
    document.querySelectorAll('.s-chip').forEach(c => c.classList.remove('active'));
    btn.classList.add('active');
    filterAndPaginate();
}

function resetFilters() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) searchInput.value = '';
    const sortSel = document.getElementById('sortSelect');
    if (sortSel) sortSel.value = '';
    activeChip = '';
    document.querySelectorAll('.s-chip').forEach(c => c.classList.remove('active'));
    const allChip = document.querySelector('.s-chip[data-i18n="chip_all"]');
    if (allChip) allChip.classList.add('active');
    filterAndPaginate();
}

document.getElementById('searchInput')?.addEventListener('input', filterAndPaginate);

function sortCampagnes() {
    const mode = document.getElementById('sortSelect').value;
    const grid = document.getElementById('campGrid');
    if (!grid || !mode) return;
    const cards = Array.from(grid.querySelectorAll('.camp-card'));
    cards.sort((a,b) => {
        if (mode==='titre')       return (a.dataset.titre||'').localeCompare(b.dataset.titre||'');
        if (mode==='budget_asc')  return parseFloat(a.dataset.budget)-parseFloat(b.dataset.budget);
        if (mode==='budget_desc') return parseFloat(b.dataset.budget)-parseFloat(a.dataset.budget);
        return 0;
    });
    cards.forEach(c => grid.appendChild(c));
    filterAndPaginate();
}

// Detail modal
function openDetail(id) {
    const c = campagnesMap[id]; if (!c) return;
    const badge = document.getElementById('detailBadge');
    badge.textContent = sLabels[c.statut] || c.statut;
    badge.style.background = sBgs[c.statut] || '#f0f0f0';
    badge.style.color = sColors[c.statut] || '#555';
    document.getElementById('detailTitle').textContent = c.titre;
    document.getElementById('detailMarque').textContent = c.marque ? '🏢 ' + c.marque : '';
    document.getElementById('detailDesc').textContent = c.desc || 'Aucune description.';
    const objWrap = document.getElementById('detailObjWrap');
    if (c.obj) { document.getElementById('detailObj').textContent = c.obj; objWrap.style.display = ''; }
    else objWrap.style.display = 'none';
    document.getElementById('detailBudget').textContent = new Intl.NumberFormat('fr-FR').format(c.budget) + ' €';
    document.getElementById('detailDates').textContent = (c.debut || '—') + ' → ' + (c.fin || '—');
    document.getElementById('detailApplyBtn').style.display = c.statut === 'active' ? '' : 'none';
    document.getElementById('detailProductsGrid').innerHTML = '<div class="prod-loader">⏳ Chargement des produits…</div>';
    document.getElementById('detailModal').classList.add('open');
    document.body.style.overflow = 'hidden';
    fetch('indexC.php?ajax_produits_creator=' + id)
        .then(r => r.json()).then(renderDetailProducts)
        .catch(() => { document.getElementById('detailProductsGrid').innerHTML = '<div class="prod-loader">⚠ Impossible de charger les produits.</div>'; });
}

function renderDetailProducts(produits) {
    const grid = document.getElementById('detailProductsGrid');
    if (!produits?.length) { grid.innerHTML = '<div class="prod-loader">Aucun produit lié à cette campagne.</div>'; return; }
    grid.innerHTML = produits.map(p => {
        const img = p.image ? `<img src="${BASE_URL}/Vue/public/produits/${encodeURIComponent(p.image)}" alt="">` : '📦';
        return `<div class="detail-prod-card"><div class="detail-prod-img">${img}</div><div class="detail-prod-body"><div class="detail-prod-name" title="${esc(p.nomProduit)}">${esc(p.nomProduit)}</div><div class="detail-prod-price">${parseFloat(p.prix).toFixed(2)} €</div></div></div>`;
    }).join('');
}

function closeDetail() { document.getElementById('detailModal').classList.remove('open'); document.body.style.overflow = ''; }
document.getElementById('detailModal').addEventListener('click', e => { if (e.target.id === 'detailModal') closeDetail(); });

function esc(s) { const d = document.createElement('div'); d.textContent = s||''; return d.innerHTML; }

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDetail(); });

<?php if ($campagneDetail): ?>
document.addEventListener('DOMContentLoaded', () => openDetail(<?= $campagneDetail['idCampagne'] ?>));
<?php endif; ?>
</script>
<script src="../layout/front-header.js?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../layout/front-header.js')); ?>"></script>
</body>
</html>