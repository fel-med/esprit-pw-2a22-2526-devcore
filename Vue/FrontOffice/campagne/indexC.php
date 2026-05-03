<?php
require_once __DIR__ . '/../../../Controleur/campagneC.php';
require_once __DIR__ . '/../../../Controleur/produitC.php';

session_start();
$campagneC = new CampagneC();
$produitC  = new ProduitC();
$baseUrl   = '/projet/Esprit-PW-2A22-2526-Devcore';

// AJAX : produits d'une campagne (lecture seule — créateur)
if (isset($_GET['ajax_produits_creator'])) {
    $idC = intval($_GET['ajax_produits_creator']);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($produitC->getProduitsByCampagne($idC), JSON_UNESCAPED_UNICODE);
    exit;
}

$campagnes      = $campagneC->afficherCampagnes();
$totalCampagnes = count($campagnes);
$nbActives      = count(array_filter($campagnes, fn($c) => $c['statut'] === 'active'));
$budgets        = array_column($campagnes, 'budget');
$budgetTotal    = array_sum($budgets);

$campagneDetail = null;
if (isset($_GET['voir'])) {
    $campagneDetail = $campagneC->recupererCampagne(intval($_GET['voir']));
}

function statutLabel($s){ return match($s){'active'=>'✅ Active','terminee'=>'🏁 Ended','annulee'=>'❌ Cancelled',default=>'📝 Draft'}; }
function statutColor($s){ return match($s){'active'=>'#0ea370','terminee'=>'#3b82f6','annulee'=>'#f43f5e',default=>'#f59e0b'}; }
function statutBg($s){    return match($s){'active'=>'#edfaf5','terminee'=>'#eff6ff','annulee'=>'#fff1f3',default=>'#fffbeb'}; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Campaigns — Cre8Connect</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:ital,wght@0,700;0,800;1,700&display=swap" rel="stylesheet">
<style>
:root{
    --primary:#5b4fff;--primary-hover:#4a3ee8;--primary-light:#eeecff;
    --primary-glow:rgba(91,79,255,0.18);--primary-border:rgba(91,79,255,0.2);
    --text-main:#0f0e1a;--text-sub:#6b6f80;--text-dim:#a0a4b2;
    --border:#ebebf2;--bg:#f6f6fc;--white:#ffffff;
    --danger:#f43f5e;--danger-light:#fff1f3;
    --success:#0ea370;--success-light:#edfaf5;--success-border:rgba(14,163,112,0.2);
    --warning:#f59e0b;--warning-light:#fffbeb;
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
.wishlist-nav-btn{display:flex;align-items:center;gap:6px;background:var(--white);border:1.5px solid var(--border);border-radius:var(--radius-sm);padding:6px 12px;font-size:12px;font-weight:700;cursor:pointer;color:var(--text-sub);transition:all .18s;font-family:'DM Sans',sans-serif;}
.wishlist-nav-btn:hover{border-color:var(--primary);color:var(--primary);}
.wishlist-nav-btn .wl-count{background:var(--primary);color:#fff;border-radius:50%;width:18px;height:18px;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:800;}

/* TOAST */
#toastContainer{position:fixed;top:76px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:8px;pointer-events:none;}
.toast{display:flex;align-items:center;gap:10px;background:var(--white);border:1.5px solid var(--border);border-radius:var(--radius-sm);padding:12px 16px;font-size:13px;min-width:240px;box-shadow:0 8px 24px rgba(15,14,26,.12);pointer-events:all;animation:toastIn .3s ease;}
.toast.hide{opacity:0;transform:translateX(20px);transition:opacity .4s,transform .4s;}
@keyframes toastIn{from{opacity:0;transform:translateX(20px);}to{opacity:1;transform:translateX(0);}}
.toast-close{margin-left:auto;background:none;border:none;color:var(--text-dim);cursor:pointer;font-size:14px;}

/* HERO */
.hero{background:linear-gradient(135deg,#0f0e1a 0%,#1a1632 60%,#2b1f60 100%);padding:60px 48px;text-align:center;color:#fff;position:relative;overflow:hidden;}
.hero::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse 80% 60% at 50% 0%,rgba(91,79,255,.35),transparent);}
.hero-content{position:relative;max-width:640px;margin:0 auto;}
.hero-tag{display:inline-flex;align-items:center;gap:6px;background:rgba(91,79,255,.2);border:1px solid rgba(91,79,255,.35);border-radius:20px;padding:5px 14px;font-size:12px;font-weight:700;color:#a78bfa;margin-bottom:18px;}
.hero h1{font-family:'Fraunces',serif;font-size:42px;font-weight:800;line-height:1.1;letter-spacing:-1px;margin-bottom:14px;}
.hero p{font-size:15px;color:rgba(255,255,255,.7);line-height:1.65;margin-bottom:30px;}
.hero-stats{display:flex;justify-content:center;gap:36px;}
.hero-stat-val{font-family:'Fraunces',serif;font-size:28px;font-weight:800;}
.hero-stat-label{font-size:12px;color:rgba(255,255,255,.55);margin-top:2px;}

/* PAGE */
.page-wrapper{max-width:1160px;margin:0 auto;padding:40px 24px 80px;}

/* STATUS CHIPS */
.status-chips{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:22px;}
.s-chip{display:inline-flex;align-items:center;gap:5px;background:var(--white);border:1.5px solid var(--border);border-radius:20px;padding:6px 14px;font-size:12.5px;font-weight:700;cursor:pointer;transition:all .18s;color:var(--text-sub);}
.s-chip:hover,.s-chip.active{background:var(--primary);color:#fff;border-color:var(--primary);}
.s-chip .dot{width:7px;height:7px;border-radius:50%;background:currentColor;opacity:.5;}

/* FILTER BAR */
.filter-bar{background:var(--white);border:1px solid var(--border);border-radius:var(--radius);padding:18px 22px;margin-bottom:28px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;box-shadow:var(--card-shadow);}
.search-wrap{position:relative;flex:1;min-width:200px;}
.search-wrap svg{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--text-dim);pointer-events:none;width:15px;height:15px;}
.search-input{width:100%;background:var(--bg);border:1.5px solid var(--border);border-radius:var(--radius-sm);padding:9px 12px 9px 34px;font-size:13.5px;font-family:'DM Sans',sans-serif;color:var(--text-main);outline:none;transition:border-color .2s;}
.search-input:focus{border-color:var(--primary);}
.filter-select{background:var(--bg);border:1.5px solid var(--border);border-radius:var(--radius-sm);padding:9px 12px;font-size:13px;font-family:'DM Sans',sans-serif;outline:none;cursor:pointer;}
.result-count{font-size:12.5px;color:var(--text-sub);font-weight:600;white-space:nowrap;}

/* COMPARE BAR */
.compare-bar{background:var(--primary);color:#fff;border-radius:var(--radius-sm);padding:10px 18px;display:none;align-items:center;gap:12px;margin-bottom:18px;font-size:13px;font-weight:600;animation:slideDown .25s ease;}
@keyframes slideDown{from{opacity:0;transform:translateY(-8px);}to{opacity:1;transform:translateY(0);}}
.compare-bar.visible{display:flex;}
.btn-compare-now{background:#fff;color:var(--primary);border:none;border-radius:6px;padding:6px 14px;font-size:12px;font-weight:700;cursor:pointer;font-family:'DM Sans',sans-serif;}
.btn-compare-clear{background:rgba(255,255,255,.2);color:#fff;border:none;border-radius:6px;padding:6px 12px;font-size:12px;font-weight:700;cursor:pointer;font-family:'DM Sans',sans-serif;margin-left:auto;}

/* CAMP GRID */
.camp-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(330px,1fr));gap:20px;}
.camp-card{background:var(--white);border-radius:var(--radius);overflow:hidden;box-shadow:var(--card-shadow);border:1px solid var(--border);display:flex;flex-direction:column;transition:transform .22s,box-shadow .22s,border-color .22s;position:relative;}
.camp-card:hover{transform:translateY(-4px);box-shadow:var(--card-shadow-hover);border-color:var(--primary-border);}
.camp-card.in-compare{border:2px solid var(--primary);box-shadow:0 0 0 3px var(--primary-glow);}
.camp-accent{height:4px;}
.accent-active{background:linear-gradient(90deg,#0ea370,#34d399);}
.accent-brouillon{background:linear-gradient(90deg,#f59e0b,#fbbf24);}
.accent-terminee{background:linear-gradient(90deg,#3b82f6,#60a5fa);}
.accent-annulee{background:linear-gradient(90deg,#f43f5e,#fb7185);}
.camp-card-header{padding:18px 18px 0;display:flex;align-items:flex-start;justify-content:space-between;gap:8px;}
.camp-card-title{font-family:'Fraunces',serif;font-size:16px;font-weight:800;color:var(--text-main);line-height:1.25;flex:1;}
.camp-badge{display:inline-flex;align-items:center;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;flex-shrink:0;}
.camp-card-body{padding:12px 18px 16px;flex:1;display:flex;flex-direction:column;gap:10px;}
.camp-desc{font-size:13px;color:var(--text-sub);line-height:1.65;overflow:hidden;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;}
.camp-obj{font-size:12px;background:var(--primary-light);color:var(--primary);border-radius:7px;padding:7px 11px;font-weight:600;}
.camp-meta{display:flex;flex-direction:column;gap:4px;font-size:12px;color:var(--text-sub);}
.camp-budget{font-family:'Fraunces',serif;font-size:22px;font-weight:800;color:var(--primary);margin-top:4px;}
/* Products pill */
.camp-products-pill{display:inline-flex;align-items:center;gap:5px;border-radius:20px;padding:4px 12px;font-size:12px;font-weight:700;}
.camp-products-pill.has{background:var(--success-light);color:var(--success);border:1px solid var(--success-border);}
.camp-products-pill.none{background:var(--bg);color:var(--text-dim);border:1px solid var(--border);}
/* Wishlist btn */
.btn-wishlist{position:absolute;top:48px;right:14px;width:32px;height:32px;border-radius:50%;background:var(--white);border:1.5px solid var(--border);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:16px;transition:all .2s;box-shadow:var(--card-shadow);}
.btn-wishlist.active{background:#fff1f3;border-color:var(--danger);}
.btn-wishlist:hover{transform:scale(1.1);}
/* Compare checkbox */
.compare-check-wrap{position:absolute;top:50px;left:14px;display:flex;align-items:center;gap:5px;background:var(--white);border:1.5px solid var(--border);border-radius:20px;padding:3px 9px;font-size:11px;font-weight:700;cursor:pointer;transition:all .2s;}
.compare-check-wrap:hover{border-color:var(--primary);color:var(--primary);}
.compare-check-wrap input{accent-color:var(--primary);cursor:pointer;}
/* Card footer */
.camp-card-footer{padding:13px 18px;border-top:1px solid var(--border);display:flex;gap:8px;}
.btn-see-detail{flex:1;padding:9px;background:var(--primary);color:#fff;border:none;border-radius:var(--radius-sm);font-size:13px;font-weight:700;font-family:'DM Sans',sans-serif;cursor:pointer;transition:background .18s;display:flex;align-items:center;justify-content:center;gap:6px;box-shadow:0 2px 8px var(--primary-glow);}
.btn-see-detail:hover{background:var(--primary-hover);}
.btn-apply{flex:0 0 auto;padding:9px 14px;background:var(--success-light);color:var(--success);border:1px solid var(--success-border);border-radius:var(--radius-sm);font-size:12px;font-weight:700;font-family:'DM Sans',sans-serif;cursor:pointer;transition:background .18s;}
.btn-apply:hover{background:#d1fae5;}

/* EMPTY */
.empty-state{text-align:center;padding:70px 20px;background:var(--white);border:1.5px dashed var(--border);border-radius:var(--radius);}
.empty-icon{font-size:52px;margin-bottom:16px;}
.empty-state h3{font-family:'Fraunces',serif;font-size:20px;font-weight:800;margin-bottom:8px;}
.empty-state p{font-size:14px;color:var(--text-sub);}

/* DETAIL MODAL */
.detail-modal{position:fixed;inset:0;background:rgba(15,14,26,.6);z-index:300;display:none;align-items:flex-start;justify-content:center;padding:24px 20px;overflow-y:auto;}
.detail-modal.open{display:flex;}
.detail-box{background:var(--white);border-radius:var(--radius);width:760px;max-width:100%;box-shadow:0 24px 80px rgba(15,14,26,.22);animation:popIn .22s ease;display:flex;flex-direction:column;margin:auto;}
@keyframes popIn{from{opacity:0;transform:scale(.94);}to{opacity:1;transform:scale(1);}}
.detail-header{padding:24px 28px 0;display:flex;align-items:flex-start;justify-content:space-between;gap:12px;}
.detail-title{font-family:'Fraunces',serif;font-size:22px;font-weight:800;line-height:1.2;}
.detail-close{background:var(--bg);border:1.5px solid var(--border);border-radius:50%;width:32px;height:32px;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:14px;color:var(--text-sub);transition:all .2s;}
.detail-close:hover{background:var(--danger-light);color:var(--danger);}
.detail-body{padding:18px 28px 24px;display:flex;flex-direction:column;gap:16px;}
.detail-section-label{font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-dim);margin-bottom:6px;}
.detail-text{font-size:14px;color:var(--text-sub);line-height:1.7;}
.detail-meta-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.detail-meta-card{background:var(--bg);border-radius:var(--radius-sm);padding:14px 16px;border:1px solid var(--border);}
.detail-meta-val{font-family:'Fraunces',serif;font-size:18px;font-weight:800;margin-bottom:3px;}
.detail-meta-label{font-size:11px;color:var(--text-dim);font-weight:600;text-transform:uppercase;letter-spacing:.05em;}
/* Products grid in modal */
.detail-products-section{border-top:1px solid var(--border);padding-top:18px;}
.detail-products-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;margin-top:12px;}
.detail-prod-card{background:var(--bg);border:1px solid var(--border);border-radius:var(--radius-sm);overflow:hidden;transition:border-color .2s;}
.detail-prod-card:hover{border-color:var(--primary-border);}
.detail-prod-img{height:110px;background:linear-gradient(135deg,#f0effb,var(--bg));display:flex;align-items:center;justify-content:center;font-size:32px;overflow:hidden;}
.detail-prod-img img{width:100%;height:100%;object-fit:cover;}
.detail-prod-body{padding:10px 12px;}
.detail-prod-name{font-size:12.5px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.detail-prod-price{font-family:'Fraunces',serif;font-size:14px;font-weight:800;color:var(--primary);}
.prod-loader{text-align:center;padding:28px;color:var(--text-dim);font-size:13px;grid-column:1/-1;}
.prod-empty{text-align:center;padding:28px;color:var(--text-dim);font-size:13px;grid-column:1/-1;}
.detail-footer{padding:16px 28px 22px;border-top:1px solid var(--border);display:flex;gap:10px;}
.btn-apply-big{flex:1;padding:12px;background:var(--primary);color:#fff;border:none;border-radius:var(--radius-sm);font-size:14px;font-weight:700;font-family:'DM Sans',sans-serif;cursor:pointer;box-shadow:0 3px 10px var(--primary-glow);}
.btn-apply-big:hover{background:var(--primary-hover);}
.btn-close-detail{padding:12px 20px;background:var(--bg);color:var(--text-sub);border:1.5px solid var(--border);border-radius:var(--radius-sm);font-size:13px;font-weight:600;cursor:pointer;font-family:'DM Sans',sans-serif;}

/* WISHLIST PANEL */
.wishlist-panel-overlay{position:fixed;inset:0;background:rgba(15,14,26,.45);z-index:250;display:none;align-items:flex-start;justify-content:flex-end;}
.wishlist-panel-overlay.open{display:flex;}
.wishlist-panel{width:380px;height:100vh;background:var(--white);border-left:1px solid var(--border);padding:24px;overflow-y:auto;animation:slideInRight .22s ease;}
@keyframes slideInRight{from{transform:translateX(40px);opacity:0;}to{transform:translateX(0);opacity:1;}}
.wl-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;}
.wl-title{font-family:'Fraunces',serif;font-size:18px;font-weight:800;}
.wl-close{background:var(--bg);border:1.5px solid var(--border);border-radius:50%;width:30px;height:30px;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:14px;}
.wl-item{display:flex;align-items:center;gap:10px;padding:12px 0;border-bottom:1px solid var(--border);}
.wl-item-title{font-size:13px;font-weight:700;flex:1;}
.wl-item-budget{font-size:12px;color:var(--primary);font-weight:700;}
.wl-remove{background:var(--danger-light);color:var(--danger);border:none;border-radius:6px;padding:4px 10px;font-size:11px;font-weight:700;cursor:pointer;font-family:'DM Sans',sans-serif;}
.wl-empty{text-align:center;padding:40px 10px;color:var(--text-dim);}

/* COMPARE MODAL */
.compare-modal-overlay{position:fixed;inset:0;background:rgba(15,14,26,.6);z-index:350;display:none;align-items:center;justify-content:center;padding:20px;overflow-y:auto;}
.compare-modal-overlay.open{display:flex;}
.compare-modal-box{background:var(--white);border-radius:var(--radius);width:900px;max-width:100%;box-shadow:0 24px 80px rgba(15,14,26,.22);animation:popIn .22s ease;}
.compare-header{padding:22px 28px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;}
.compare-title{font-family:'Fraunces',serif;font-size:18px;font-weight:800;}
.compare-close{background:var(--bg);border:1.5px solid var(--border);border-radius:50%;width:30px;height:30px;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:14px;}
.compare-grid{display:grid;grid-template-columns:160px repeat(var(--cols,2),1fr);border-bottom:1px solid var(--border);}
.compare-row{display:contents;}
.compare-cell{padding:14px 18px;border-right:1px solid var(--border);font-size:13px;}
.compare-cell:last-child{border-right:none;}
.compare-cell.header{font-weight:700;font-size:13.5px;background:var(--bg);}
.compare-cell.label{font-weight:700;color:var(--text-sub);background:var(--bg);font-size:12px;border-bottom:1px solid var(--border);}
.compare-cell.value{border-bottom:1px solid var(--border);color:var(--text-main);}
.compare-cell.value-budget{font-family:'Fraunces',serif;font-weight:800;color:var(--primary);font-size:16px;}

@media(max-width:700px){nav{padding:0 16px;}.nav-links{display:none;}.hero{padding:40px 20px;}.hero h1{font-size:28px;}.page-wrapper{padding:24px 14px 60px;}.detail-meta-grid{grid-template-columns:1fr;}.detail-products-grid{grid-template-columns:1fr 1fr;}.compare-modal-box{width:98vw;}}
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
        <li><a href="#">Offers</a></li>
        <li><a href="#" class="active">Campaigns</a></li>
        <li><a href="#">Events</a></li>
        <li><a href="#">Forum</a></li>
    </ul>
    <div class="nav-right">
        <button class="wishlist-nav-btn" onclick="openWishlistPanel()">
            ❤️ Saved <span class="wl-count" id="wlNavCount">0</span>
        </button>
        <span class="nav-badge">Creator</span>
        <div class="nav-avatar">C</div>
    </div>
</nav>

<!-- HERO -->
<div class="hero">
    <div class="hero-content">
        <div class="hero-tag">⚡ Active Campaigns</div>
        <h1>Discover Brand Campaigns</h1>
        <p>Browse active campaigns, explore linked products, and apply to collaborate with top brands.</p>
        <div class="hero-stats">
            <div><div class="hero-stat-val"><?= $totalCampagnes ?></div><div class="hero-stat-label">Campaigns</div></div>
            <div><div class="hero-stat-val"><?= $nbActives ?></div><div class="hero-stat-label">Active Now</div></div>
            <div><div class="hero-stat-val"><?= number_format($budgetTotal/1000,0) ?>K€</div><div class="hero-stat-label">Total Budget</div></div>
        </div>
    </div>
</div>

<div class="page-wrapper">

    <!-- STATUS CHIPS -->
    <div class="status-chips">
        <div class="s-chip active" onclick="filterChip('',this)"><span class="dot"></span>All</div>
        <div class="s-chip" onclick="filterChip('active',this)"><span class="dot" style="background:#0ea370"></span>Active</div>
        <div class="s-chip" onclick="filterChip('brouillon',this)"><span class="dot" style="background:#f59e0b"></span>Draft</div>
        <div class="s-chip" onclick="filterChip('terminee',this)"><span class="dot" style="background:#3b82f6"></span>Ended</div>
    </div>

    <!-- COMPARE BAR -->
    <div class="compare-bar" id="compareBar">
        <span id="compareCount">0 campaigns selected for comparison</span>
        <button class="btn-compare-now" onclick="openCompareModal()">Compare now →</button>
        <button class="btn-compare-clear" onclick="clearCompare()">✕ Clear</button>
    </div>

    <!-- FILTER BAR -->
    <div class="filter-bar">
        <div class="search-wrap">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
            <input type="text" class="search-input" id="searchInput" placeholder="Search campaigns, brands, objectives…">
        </div>
        <select class="filter-select" id="sortSelect" onchange="sortCampagnes()">
            <option value="">Sort by…</option>
            <option value="titre">Name A→Z</option>
            <option value="budget_desc">Budget ↓</option>
            <option value="budget_asc">Budget ↑</option>
            <option value="date">Start date</option>
        </select>
        <span class="result-count"><span id="visibleCount"><?= $totalCampagnes ?></span> campaign(s)</span>
    </div>

    <!-- CAMPAIGN GRID -->
    <?php if (empty($campagnes)): ?>
    <div class="empty-state">
        <div class="empty-icon">⚡</div>
        <h3>No campaigns available yet</h3>
        <p>Check back soon — brands are launching new campaigns regularly.</p>
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
             data-date="<?= $c['dateDebut']??'' ?>"
             data-brand="<?= strtolower(htmlspecialchars($c['nomMarque']??'')) ?>">
            <div class="camp-accent accent-<?= $c['statut'] ?>"></div>
            <!-- WISHLIST BTN -->
            <button class="btn-wishlist" data-id="<?= $c['idCampagne'] ?>" onclick="toggleWishlist(<?= $c['idCampagne'] ?>,this)" title="Save to wishlist">🤍</button>
            <!-- COMPARE CHECK -->
            <label class="compare-check-wrap" title="Compare">
                <input type="checkbox" class="compare-check" value="<?= $c['idCampagne'] ?>" onchange="updateCompareBar()"> Compare
            </label>
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
                    <?php if (!empty($c['nomMarque'])): ?><div>🏢 <?= htmlspecialchars($c['nomMarque']) ?></div><?php endif; ?>
                </div>
                <div class="camp-budget"><?= number_format((float)$c['budget'],2,',',' ') ?> €</div>
                <!-- Products pill -->
                <span class="camp-products-pill <?= $nbProd>0?'has':'none' ?>">
                    📦 <?= $nbProd ?> product<?= $nbProd!==1?'s':'' ?> linked
                </span>
            </div>
            <div class="camp-card-footer">
                <button class="btn-see-detail" onclick="openDetail(<?= $c['idCampagne'] ?>)">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    View Details
                </button>
                <?php if ($c['statut']==='active'): ?>
                <button class="btn-apply" onclick="showToast('Application sent for &quot;<?= htmlspecialchars(addslashes($c['titreCampagne'])) ?>&quot;!','success')">🙋 Apply</button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <div id="noResults" style="display:none;text-align:center;padding:48px;color:var(--text-dim);font-size:14px;">No campaigns match your search.</div>
    <?php endif; ?>

</div>

<!-- DETAIL MODAL -->
<div class="detail-modal" id="detailModal">
    <div class="detail-box">
        <div class="detail-header">
            <div>
                <div id="detailBadge" class="camp-badge" style="margin-bottom:8px;font-size:12px"></div>
                <div class="detail-title" id="detailTitle"></div>
                <div id="detailBrandBadge" style="margin-top:8px"></div>
            </div>
            <button class="detail-close" onclick="closeDetail()">✕</button>
        </div>
        <div class="detail-body">
            <div><div class="detail-section-label">Description</div><div class="detail-text" id="detailDesc"></div></div>
            <div id="detailObjWrap"><div class="detail-section-label">Objective</div><div class="camp-obj" id="detailObj"></div></div>
            <div class="detail-meta-grid">
                <div class="detail-meta-card"><div class="detail-meta-val" id="detailBudget"></div><div class="detail-meta-label">Campaign Budget</div></div>
                <div class="detail-meta-card"><div class="detail-meta-val" id="detailDates" style="font-size:14px"></div><div class="detail-meta-label">Campaign Period</div></div>
            </div>
            <!-- PRODUCTS -->
            <div class="detail-products-section">
                <div class="detail-section-label">Products in this campaign</div>
                <div class="detail-products-grid" id="detailProductsGrid"><div class="prod-loader">⏳ Loading products…</div></div>
            </div>
        </div>
        <div class="detail-footer">
            <button class="btn-apply-big" id="detailApplyBtn" onclick="showToast('Application sent!','success')">🙋 Apply to this campaign</button>
            <button class="btn-close-detail" onclick="closeDetail()">Close</button>
        </div>
    </div>
</div>

<!-- WISHLIST PANEL -->
<div class="wishlist-panel-overlay" id="wishlistOverlay" onclick="closeWishlistOutside(event)">
    <div class="wishlist-panel">
        <div class="wl-header">
            <div class="wl-title">❤️ Saved Campaigns</div>
            <button class="wl-close" onclick="closeWishlistPanel()">✕</button>
        </div>
        <div id="wishlistContent"><div class="wl-empty">No saved campaigns yet.</div></div>
    </div>
</div>

<!-- COMPARE MODAL -->
<div class="compare-modal-overlay" id="compareModal">
    <div class="compare-modal-box">
        <div class="compare-header">
            <div class="compare-title">🔍 Campaign Comparison</div>
            <button class="compare-close" onclick="closeCompareModal()">✕</button>
        </div>
        <div style="overflow-x:auto;">
            <div class="compare-grid" id="compareGrid" style="--cols:2"></div>
        </div>
    </div>
</div>

<script>
const BASE_URL='<?= $baseUrl ?>';

/* ─── TOAST ──────────────────────────────────────────────────────── */
function showToast(msg,type='success',dur=4000){
    const icons={success:'✅',error:'❌',info:'ℹ️',warning:'⚠️'};
    const c=document.getElementById('toastContainer');
    const t=document.createElement('div');
    t.className=`toast`;t.style.borderColor=type==='success'?'rgba(14,163,112,.3)':type==='error'?'rgba(244,63,94,.3)':'rgba(91,79,255,.2)';
    t.innerHTML=`<span>${icons[type]||'ℹ️'}</span><span style="flex:1">${msg}</span><button class="toast-close" onclick="this.closest('.toast').remove()">✕</button>`;
    c.appendChild(t);
    setTimeout(()=>{t.classList.add('hide');setTimeout(()=>t.remove(),450);},dur);
}

/* ─── CAMPAIGN DATA ──────────────────────────────────────────────── */
const campagnesMap={};
<?php foreach ($campagnes as $c): ?>
campagnesMap[<?= $c['idCampagne'] ?>]={
    id:<?= $c['idCampagne'] ?>,
    titre:<?= json_encode($c['titreCampagne']) ?>,
    desc:<?= json_encode($c['description']??'') ?>,
    obj:<?= json_encode($c['objectif']??'') ?>,
    budget:<?= (float)$c['budget'] ?>,
    debut:<?= json_encode($c['dateDebut']??'') ?>,
    fin:<?= json_encode($c['dateFin']??'') ?>,
    statut:<?= json_encode($c['statut']) ?>,
    marque:<?= json_encode($c['nomMarque']??'') ?>,
};
<?php endforeach; ?>

const sLabels={active:'✅ Active',brouillon:'📝 Draft',terminee:'🏁 Ended',annulee:'❌ Cancelled'};
const sColors={active:'#0ea370',brouillon:'#f59e0b',terminee:'#3b82f6',annulee:'#f43f5e'};
const sBgs={active:'#edfaf5',brouillon:'#fffbeb',terminee:'#eff6ff',annulee:'#fff1f3'};

/* ─── WISHLIST (localStorage) ────────────────────────────────────── */
let wishlist=JSON.parse(localStorage.getItem('cre8_campaign_wishlist')||'[]');
function saveWishlist(){localStorage.setItem('cre8_campaign_wishlist',JSON.stringify(wishlist));}
function updateWlNavCount(){document.getElementById('wlNavCount').textContent=wishlist.length;}
function toggleWishlist(id,btn){
    const idx=wishlist.indexOf(id);
    if(idx===-1){
        wishlist.push(id);btn.textContent='❤️';btn.classList.add('active');
        showToast('Added to saved campaigns!','success');
    } else {
        wishlist.splice(idx,1);btn.textContent='🤍';btn.classList.remove('active');
        showToast('Removed from saved.','warning');
    }
    saveWishlist();updateWlNavCount();renderWishlistPanel();
}
function initWishlistButtons(){
    document.querySelectorAll('.btn-wishlist').forEach(btn=>{
        const id=parseInt(btn.dataset.id);
        if(wishlist.includes(id)){btn.textContent='❤️';btn.classList.add('active');}
    });
    updateWlNavCount();
}
function renderWishlistPanel(){
    const content=document.getElementById('wishlistContent');
    if(!wishlist.length){content.innerHTML='<div class="wl-empty">No saved campaigns yet.<br>Click 🤍 on any campaign to save it.</div>';return;}
    let html='';
    wishlist.forEach(id=>{
        const c=campagnesMap[id];if(!c)return;
        html+=`<div class="wl-item"><div class="wl-item-title">${escHtml(c.titre)}<br><small style="color:var(--text-dim);font-size:11px">${sLabels[c.statut]||c.statut}</small></div><span class="wl-item-budget">${c.budget.toFixed(2)} €</span><button class="wl-remove" onclick="removeFromWishlist(${id})">✕</button></div>`;
    });
    content.innerHTML=html;
}
function removeFromWishlist(id){
    wishlist=wishlist.filter(i=>i!==id);saveWishlist();
    document.querySelectorAll(`.btn-wishlist[data-id="${id}"]`).forEach(b=>{b.textContent='🤍';b.classList.remove('active');});
    updateWlNavCount();renderWishlistPanel();
    showToast('Removed from saved.','warning');
}
function openWishlistPanel(){renderWishlistPanel();document.getElementById('wishlistOverlay').classList.add('open');}
function closeWishlistPanel(){document.getElementById('wishlistOverlay').classList.remove('open');}
function closeWishlistOutside(e){if(e.target.id==='wishlistOverlay')closeWishlistPanel();}

/* ─── COMPARE ────────────────────────────────────────────────────── */
let compareList=[];
function updateCompareBar(){
    compareList=[...document.querySelectorAll('.compare-check:checked')].map(c=>parseInt(c.value));
    const bar=document.getElementById('compareBar');
    bar.classList.toggle('visible',compareList.length>=2);
    document.getElementById('compareCount').textContent=`${compareList.length} campaign${compareList.length>1?'s':''} selected`;
    document.querySelectorAll('.camp-card').forEach(card=>{
        const id=parseInt(card.dataset.id);
        card.classList.toggle('in-compare',compareList.includes(id));
    });
}
function clearCompare(){
    document.querySelectorAll('.compare-check').forEach(c=>c.checked=false);
    compareList=[];updateCompareBar();
}
function openCompareModal(){
    if(compareList.length<2){showToast('Select at least 2 campaigns to compare.','warning');return;}
    const grid=document.getElementById('compareGrid');
    grid.style.setProperty('--cols',compareList.length);
    const fields=[
        {label:'Campaign',fn:c=>escHtml(c.titre),cls:'header'},
        {label:'Status',fn:c=>`<span style="background:${sBgs[c.statut]};color:${sColors[c.statut]};padding:2px 8px;border-radius:20px;font-size:11px;font-weight:700">${sLabels[c.statut]}</span>`,cls:'value'},
        {label:'Budget',fn:c=>`${c.budget.toFixed(2)} €`,cls:'value-budget'},
        {label:'Start',fn:c=>c.debut||'—',cls:'value'},
        {label:'End',fn:c=>c.fin||'—',cls:'value'},
        {label:'Brand',fn:c=>escHtml(c.marque)||'—',cls:'value'},
        {label:'Objective',fn:c=>escHtml(c.obj)||'—',cls:'value'},
    ];
    let html='';
    fields.forEach(f=>{
        html+=`<div class="compare-cell label">${f.label}</div>`;
        compareList.forEach(id=>{
            const c=campagnesMap[id];
            html+=`<div class="compare-cell ${f.cls}">${c?f.fn(c):'—'}</div>`;
        });
    });
    grid.innerHTML=html;
    document.getElementById('compareModal').classList.add('open');
}
function closeCompareModal(){document.getElementById('compareModal').classList.remove('open');}

/* ─── FILTER + SORT ──────────────────────────────────────────────── */
let activeChip='';
function filterChip(val,btn){
    activeChip=val;
    document.querySelectorAll('.s-chip').forEach(c=>c.classList.remove('active'));
    btn.classList.add('active');filterCampagnes();
}
document.getElementById('searchInput').addEventListener('input',filterCampagnes);
function filterCampagnes(){
    const q=document.getElementById('searchInput').value.toLowerCase();
    const cards=document.querySelectorAll('#campGrid .camp-card');
    let v=0;
    cards.forEach(card=>{
        const mQ=!q||(card.dataset.titre||'').includes(q)||(card.dataset.brand||'').includes(q);
        const mS=!activeChip||card.dataset.statut===activeChip;
        card.style.display=mQ&&mS?'':'none';
        if(mQ&&mS)v++;
    });
    document.getElementById('visibleCount').textContent=v;
    document.getElementById('noResults').style.display=v===0?'':'none';
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

/* ─── DETAIL MODAL ───────────────────────────────────────────────── */
function openDetail(id){
    const c=campagnesMap[id];if(!c)return;
    const badge=document.getElementById('detailBadge');
    badge.textContent=sLabels[c.statut]||c.statut;
    badge.style.background=sBgs[c.statut]||'#f0f0f0';
    badge.style.color=sColors[c.statut]||'#555';
    document.getElementById('detailTitle').textContent=c.titre;
    document.getElementById('detailBrandBadge').innerHTML=c.marque?`<span style="display:inline-flex;align-items:center;gap:5px;background:var(--bg);border:1px solid var(--border);border-radius:20px;padding:3px 10px;font-size:12px;font-weight:600;color:var(--text-sub);">🏢 ${escHtml(c.marque)}</span>`:'';
    document.getElementById('detailDesc').textContent=c.desc||'No description.';
    const objWrap=document.getElementById('detailObjWrap');
    if(c.obj){document.getElementById('detailObj').textContent=c.obj;objWrap.style.display='';}
    else objWrap.style.display='none';
    document.getElementById('detailBudget').textContent=new Intl.NumberFormat('fr-FR').format(c.budget)+' €';
    document.getElementById('detailDates').innerHTML=(c.debut||'—')+' → '+(c.fin||'—');
    document.getElementById('detailApplyBtn').style.display=c.statut==='active'?'':'none';
    document.getElementById('detailProductsGrid').innerHTML='<div class="prod-loader">⏳ Loading products…</div>';
    document.getElementById('detailModal').classList.add('open');
    document.body.style.overflow='hidden';
    // Load products
    fetch('indexC.php?ajax_produits_creator='+id)
        .then(r=>r.json()).then(renderDetailProducts)
        .catch(()=>{document.getElementById('detailProductsGrid').innerHTML='<div class="prod-empty">⚠ Unable to load products.</div>';});
}
function renderDetailProducts(produits){
    const grid=document.getElementById('detailProductsGrid');
    if(!produits?.length){grid.innerHTML='<div class="prod-empty">No products linked to this campaign.</div>';return;}
    grid.innerHTML=produits.map(p=>{
        const img=p.image?`<img src="${BASE_URL}/Vue/public/produits/${escHtml(p.image)}" alt="">`:'📦';
        return `<div class="detail-prod-card"><div class="detail-prod-img">${img}</div><div class="detail-prod-body"><div class="detail-prod-name" title="${escHtml(p.nomProduit)}">${escHtml(p.nomProduit)}</div><div class="detail-prod-price">${parseFloat(p.prix).toFixed(2)} €</div>${p.categorie?`<div style="font-size:10px;color:var(--text-dim);margin-top:2px">📂 ${escHtml(p.categorie)}</div>`:''}</div></div>`;
    }).join('');
}
function closeDetail(){document.getElementById('detailModal').classList.remove('open');document.body.style.overflow='';}
document.getElementById('detailModal').addEventListener('click',e=>{if(e.target.id==='detailModal')closeDetail();});

function escHtml(s){const d=document.createElement('div');d.textContent=s||'';return d.innerHTML;}

document.addEventListener('keydown',e=>{
    if(e.key==='Escape'){closeDetail();closeWishlistPanel();closeCompareModal();}
});

// Init
document.addEventListener('DOMContentLoaded',()=>{
    initWishlistButtons();
    <?php if ($campagneDetail): ?>
    openDetail(<?= $campagneDetail['idCampagne'] ?>);
    <?php endif; ?>
});
</script>
</body>
</html>