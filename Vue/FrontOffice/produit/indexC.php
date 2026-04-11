<?php
require_once __DIR__ . '/../../../Controleur/produitC.php';

$controller = new ProduitC();
$allProduits = $controller->afficherProduits();

$baseUrl = '/projet/Esprit-PW-2A22-2526-Devcore';

$search = trim($_GET['q'] ?? '');
$sort   = $_GET['sort'] ?? '';
$pmin   = isset($_GET['pmin']) && $_GET['pmin'] !== '' ? floatval($_GET['pmin']) : null;
$pmax   = isset($_GET['pmax']) && $_GET['pmax'] !== '' ? floatval($_GET['pmax']) : null;

$produits = $allProduits;

if ($search !== '') {
    $produits = array_filter($produits, fn($p) =>
        stripos($p['nomProduit'], $search) !== false ||
        stripos($p['description'], $search) !== false ||
        stripos($p['caracteristiques'], $search) !== false
    );
}
if ($pmin !== null) $produits = array_filter($produits, fn($p) => (float)$p['prix'] >= $pmin);
if ($pmax !== null) $produits = array_filter($produits, fn($p) => (float)$p['prix'] <= $pmax);

$produits = array_values($produits);

if ($sort === 'prix_asc')  usort($produits, fn($a,$b) => $a['prix'] <=> $b['prix']);
if ($sort === 'prix_desc') usort($produits, fn($a,$b) => $b['prix'] <=> $a['prix']);
if ($sort === 'nom')       usort($produits, fn($a,$b) => strcmp($a['nomProduit'], $b['nomProduit']));

$total = count($produits);

$allPrix = array_column($allProduits, 'prix');
$globalMin = $allPrix ? (int)floor(min($allPrix)) : 0;
$globalMax = $allPrix ? (int)ceil(max($allPrix))  : 500;
$prixMoyen = count($allProduits) > 0 ? array_sum($allPrix) / count($allProduits) : 0;

function isNew($produit) {
    if (empty($produit['dateAjout'])) return false;
    return (time() - strtotime($produit['dateAjout'])) < 7 * 24 * 3600;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Catalogue Produits — Cre8Connect</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Fraunces:wght@700;900&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
    --primary:       #4040f2;
    --primary-light: #eeeeff;
    --primary-hover: #2e2ed4;
    --primary-glow:  rgba(64,64,242,0.13);
    --text-main:     #1a1a2e;
    --text-sub:      #6b7280;
    --text-dim:      #9ca3af;
    --border:        #e5e7eb;
    --bg:            #f8f8fd;
    --white:         #ffffff;
    --success:       #10b981;
    --success-light: #ecfdf5;
    --warning:       #f59e0b;
    --warning-light: #fffbeb;
    --danger:        #ef4444;
    --danger-light:  #fef2f2;
    --radius:        14px;
    --radius-sm:     8px;
    --radius-xs:     5px;
    --nav-h:         66px;
    --card-shadow:   0 2px 14px rgba(64,64,242,0.07);
    --card-shadow-hover: 0 10px 32px rgba(64,64,242,0.14);
}
body { font-family:'DM Sans',sans-serif; background:var(--bg); color:var(--text-main); min-height:100vh; }

/* ── NAV ── */
nav {
    background:var(--white); border-bottom:1px solid var(--border);
    padding:0 48px; height:var(--nav-h);
    display:flex; align-items:center; justify-content:space-between;
    position:sticky; top:0; z-index:300;
    box-shadow:0 1px 0 var(--border), 0 4px 16px rgba(0,0,0,0.04);
}
.nav-logo { display:flex; align-items:center; gap:10px; text-decoration:none; }
.nav-logo img { width:36px; height:36px; object-fit:contain; border-radius:var(--radius-sm); }
.nav-logo-text { font-family:'Fraunces',serif; font-size:20px; font-weight:900; color:var(--primary); letter-spacing:-.5px; }
.nav-links { display:flex; gap:30px; list-style:none; }
.nav-links a { text-decoration:none; color:var(--text-sub); font-size:14px; font-weight:500; padding:6px 0; border-bottom:2px solid transparent; transition:color .2s,border-color .2s; }
.nav-links a:hover { color:var(--primary); }
.nav-links a.active { color:var(--primary); border-bottom-color:var(--primary); }
.nav-avatar { width:36px; height:36px; border-radius:50%; background:var(--primary-light); color:var(--primary); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:14px; cursor:pointer; border:2px solid var(--primary-light); transition:border-color .2s; }
.nav-avatar:hover { border-color:var(--primary); }

/* ── HERO ── */
.hero-band { background:linear-gradient(135deg,#4040f2 0%,#7c3aed 100%); padding:52px 48px 48px; color:#fff; position:relative; overflow:hidden; }
.hero-band::before { content:''; position:absolute; top:-60px; right:-60px; width:280px; height:280px; background:rgba(255,255,255,0.06); border-radius:50%; }
.hero-band::after  { content:''; position:absolute; bottom:-80px; left:30%; width:200px; height:200px; background:rgba(255,255,255,0.04); border-radius:50%; }
.hero-inner { max-width:1160px; margin:0 auto; position:relative; z-index:1; }
.hero-label { display:inline-flex; align-items:center; gap:6px; background:rgba(255,255,255,0.15); border:1px solid rgba(255,255,255,0.25); border-radius:20px; padding:4px 14px; font-size:12px; font-weight:600; letter-spacing:.04em; text-transform:uppercase; color:rgba(255,255,255,0.9); margin-bottom:18px; }
.hero-band h1 { font-family:'Fraunces',serif; font-size:42px; font-weight:900; letter-spacing:-1.5px; line-height:1.08; margin-bottom:12px; }
.hero-band p { font-size:16px; color:rgba(255,255,255,0.75); max-width:520px; line-height:1.6; }
.hero-stats { display:flex; gap:36px; margin-top:32px; flex-wrap:wrap; }
.hero-stat-value { font-family:'Fraunces',serif; font-size:28px; font-weight:900; color:#fff; display:block; }
.hero-stat-label { font-size:12px; color:rgba(255,255,255,0.6); font-weight:500; }

/* ── PAGE WRAPPER ── */
.page-wrapper { max-width:1160px; margin:0 auto; padding:36px 24px 72px; }

/* ── TOOLBAR ── */
.toolbar { display:flex; align-items:center; justify-content:space-between; gap:14px; margin-bottom:20px; flex-wrap:wrap; }
.toolbar-left { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.results-label { font-size:14px; color:var(--text-sub); font-weight:500; }
.results-label strong { color:var(--text-main); }
.search-wrap { position:relative; }
.search-wrap svg { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-dim); pointer-events:none; width:16px; height:16px; }
.search-input { background:var(--white); border:1.5px solid var(--border); border-radius:var(--radius-sm); padding:9px 14px 9px 36px; font-size:14px; font-family:'DM Sans',sans-serif; color:var(--text-main); width:260px; outline:none; transition:border-color .2s,box-shadow .2s; }
.search-input::placeholder { color:var(--text-dim); }
.search-input:focus { border-color:var(--primary); box-shadow:0 0 0 3px var(--primary-glow); }
.btn-search { background:var(--primary); color:#fff; border:none; border-radius:0 var(--radius-sm) var(--radius-sm) 0; padding:9px 16px; font-size:13px; font-weight:600; font-family:'DM Sans',sans-serif; cursor:pointer; margin-left:-2px; transition:background .2s; }
.btn-search:hover { background:var(--primary-hover); }
.view-toggle { display:flex; border:1.5px solid var(--border); border-radius:var(--radius-sm); overflow:hidden; background:var(--white); }
.view-btn { background:transparent; border:none; padding:7px 10px; cursor:pointer; color:var(--text-dim); display:flex; align-items:center; transition:background .15s,color .15s; }
.view-btn.active, .view-btn:hover { background:var(--primary-light); color:var(--primary); }
.view-btn svg { width:16px; height:16px; }

/* ── FILTER BAR ── */
.filter-bar { background:var(--white); border:1px solid var(--border); border-radius:var(--radius); padding:18px 22px; margin-bottom:24px; }
.filter-bar-inner { display:flex; gap:28px; align-items:flex-start; flex-wrap:wrap; }
.filter-group { display:flex; flex-direction:column; gap:8px; }
.filter-group-label { font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.06em; color:var(--text-dim); }
.chips-row { display:flex; gap:6px; flex-wrap:wrap; }
.chip { padding:5px 13px; border-radius:20px; font-size:12px; font-weight:500; cursor:pointer; border:1.5px solid var(--border); background:var(--white); color:var(--text-sub); transition:all .15s; user-select:none; }
.chip:hover { border-color:var(--primary); color:var(--primary); }
.chip.active { background:var(--primary-light); border-color:var(--primary); color:var(--primary); }
.price-range-wrap { display:flex; flex-direction:column; gap:8px; min-width:220px; }
.range-row { display:flex; align-items:center; gap:10px; }
.range-row label { font-size:12px; color:var(--text-sub); width:36px; flex-shrink:0; }
.range-row input[type=range] { flex:1; accent-color:var(--primary); cursor:pointer; }
.range-val { font-size:12px; font-weight:600; color:var(--primary); min-width:50px; text-align:right; }
.active-filters { display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-top:14px; padding-top:14px; border-top:1px solid var(--border); }
.active-filters-label { font-size:12px; color:var(--text-dim); }
.filter-tag { display:inline-flex; align-items:center; gap:5px; background:var(--primary-light); color:var(--primary); border-radius:20px; padding:3px 11px; font-size:12px; font-weight:500; }
.filter-tag-remove { cursor:pointer; opacity:.6; font-size:13px; line-height:1; }
.filter-tag-remove:hover { opacity:1; }
.clear-all { font-size:12px; color:var(--danger); cursor:pointer; text-decoration:underline; margin-left:4px; }

/* ── GRIDS ── */
.products-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:22px; }
.products-grid.view-compact { grid-template-columns:repeat(auto-fill,minmax(170px,1fr)); gap:12px; }
.products-grid.view-list { grid-template-columns:1fr; gap:12px; }

/* ── CARD ── */
.product-card { background:var(--white); border-radius:var(--radius); overflow:hidden; box-shadow:var(--card-shadow); border:1px solid var(--border); transition:transform .22s,box-shadow .22s,border-color .22s; cursor:pointer; display:flex; flex-direction:column; }
.product-card:hover { transform:translateY(-4px); box-shadow:var(--card-shadow-hover); border-color:rgba(64,64,242,0.2); }
.view-list .product-card { flex-direction:row; }
.view-compact .product-card { border-radius:var(--radius-sm); }

/* Image */
.pcard-img-wrap { position:relative; overflow:hidden; height:195px; background:var(--bg); flex-shrink:0; }
.view-list .pcard-img-wrap { width:180px; height:auto; min-height:140px; }
.view-compact .pcard-img-wrap { height:130px; }
.pcard-img-wrap img { width:100%; height:100%; object-fit:cover; transition:transform .35s; display:block; }
.product-card:hover .pcard-img-wrap img { transform:scale(1.05); }
.pcard-img-empty { width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-size:44px; color:#c4c4e0; background:linear-gradient(135deg,#f0f0ff,#f8f8fd); }
.view-compact .pcard-img-empty { font-size:28px; }

/* Badges flottants */
.badge-float-tl { position:absolute; top:10px; left:10px; display:flex; flex-direction:column; gap:4px; }
.badge { display:inline-flex; align-items:center; gap:4px; font-size:10px; font-weight:700; padding:3px 9px; border-radius:20px; letter-spacing:.02em; }
.badge-new     { background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0; }
.badge-hot     { background:#fffbeb; color:#92400e; border:1px solid #fcd34d; }
.badge-promo   { background:#fdf2f8; color:#9d174d; border:1px solid #f9a8d4; }
.badge-verified{ background:var(--primary-light); color:var(--primary); border:1px solid #c7d2fe; }

.price-badge-float { position:absolute; bottom:10px; right:10px; background:var(--primary); color:#fff; border-radius:20px; padding:4px 12px; font-size:12px; font-weight:700; box-shadow:0 2px 8px rgba(64,64,242,0.28); }
.fav-btn { position:absolute; top:10px; right:10px; width:28px; height:28px; border-radius:50%; background:rgba(255,255,255,0.92); display:flex; align-items:center; justify-content:center; cursor:pointer; border:1px solid var(--border); transition:border-color .2s,background .2s; }
.fav-btn:hover { background:#fff; border-color:var(--primary); }
.fav-btn svg { width:13px; height:13px; transition:fill .2s,stroke .2s; }
.fav-btn.on svg { fill:#ef4444; stroke:#ef4444; }

/* Card body */
.pcard-body { padding:18px 18px 14px; display:flex; flex-direction:column; flex:1; }
.view-list .pcard-body { padding:18px 22px; }
.view-compact .pcard-body { padding:10px 11px 10px; }
.pcard-brand-tag { display:inline-flex; align-items:center; gap:4px; font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:var(--primary); background:var(--primary-light); border-radius:20px; padding:2px 9px; margin-bottom:8px; width:fit-content; }
.view-compact .pcard-brand-tag { display:none; }
.pcard-name { font-family:'Fraunces',serif; font-size:17px; font-weight:700; color:var(--text-main); line-height:1.25; margin-bottom:6px; letter-spacing:-.3px; }
.view-compact .pcard-name { font-family:'DM Sans',sans-serif; font-size:13px; font-weight:600; }
.pcard-desc { font-size:13px; color:var(--text-sub); line-height:1.6; margin-bottom:12px; flex:1; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
.view-compact .pcard-desc { display:none; }
.pcard-carac { display:flex; align-items:center; gap:5px; font-size:12px; color:var(--primary); background:var(--primary-light); border-radius:var(--radius-xs); padding:5px 10px; font-weight:500; margin-bottom:12px; line-height:1.4; }
.pcard-carac svg { width:12px; height:12px; flex-shrink:0; }
.view-compact .pcard-carac { display:none; }
.pcard-footer { display:flex; align-items:center; justify-content:space-between; padding-top:12px; border-top:1px solid var(--border); }
.view-compact .pcard-footer { padding-top:6px; }
.price-main { font-family:'Fraunces',serif; font-size:19px; font-weight:900; color:var(--primary); letter-spacing:-.4px; }
.view-compact .price-main { font-size:14px; font-family:'DM Sans',sans-serif; font-weight:700; }
.price-currency { font-size:13px; color:var(--text-sub); margin-left:1px; }
.btn-voir { display:inline-flex; align-items:center; gap:5px; background:var(--primary); color:#fff; border-radius:var(--radius-xs); padding:7px 14px; font-size:12px; font-weight:600; font-family:'DM Sans',sans-serif; text-decoration:none; border:none; cursor:pointer; transition:background .2s,transform .15s; }
.btn-voir:hover { background:var(--primary-hover); transform:translateY(-1px); }
.btn-voir svg { width:13px; height:13px; }
.view-compact .btn-voir { padding:5px 9px; font-size:11px; }

/* ── EMPTY STATE ── */
.empty-state { text-align:center; padding:72px 24px; background:var(--white); border-radius:var(--radius); border:1px solid var(--border); box-shadow:var(--card-shadow); }
.empty-icon { font-size:52px; margin-bottom:16px; display:block; }
.empty-state h3 { font-family:'Fraunces',serif; font-size:22px; font-weight:700; color:var(--text-main); margin-bottom:8px; }
.empty-state p { font-size:14px; color:var(--text-sub); max-width:340px; margin:0 auto; line-height:1.65; }
.btn-clear { display:inline-flex; align-items:center; gap:6px; margin-top:22px; background:var(--primary-light); color:var(--primary); border:none; border-radius:var(--radius-sm); padding:10px 22px; font-size:14px; font-weight:600; font-family:'DM Sans',sans-serif; cursor:pointer; text-decoration:none; transition:background .2s; }
.btn-clear:hover { background:#ddddf8; }

/* ── MODAL ── */
.modal-overlay { display:none; position:fixed; inset:0; background:rgba(10,10,35,0.58); z-index:500; align-items:center; justify-content:center; padding:20px; }
.modal-overlay.open { display:flex; }
.modal-box { background:var(--white); border-radius:18px; width:780px; max-width:100%; max-height:92vh; overflow-y:auto; box-shadow:0 28px 70px rgba(64,64,242,0.18); display:flex; flex-direction:column; animation:mslide .25s ease; }
@keyframes mslide { from{opacity:0;transform:translateY(18px)} to{opacity:1;transform:translateY(0)} }
.modal-img-wrap { height:270px; overflow:hidden; position:relative; border-radius:18px 18px 0 0; background:var(--bg); flex-shrink:0; }
.modal-img-wrap img { width:100%; height:100%; object-fit:cover; display:block; }
.modal-img-empty { width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-size:70px; background:linear-gradient(135deg,#f0f0ff,#f8f8fd); }
.modal-badges-float { position:absolute; top:14px; left:16px; display:flex; gap:6px; flex-wrap:wrap; }
.modal-price-pill { position:absolute; bottom:14px; left:16px; background:var(--primary); color:#fff; border-radius:20px; padding:6px 18px; font-family:'Fraunces',serif; font-size:20px; font-weight:900; letter-spacing:-.4px; box-shadow:0 3px 12px rgba(64,64,242,0.28); }
.modal-close { position:absolute; top:14px; right:14px; background:rgba(255,255,255,0.92); border:none; border-radius:50%; width:34px; height:34px; display:flex; align-items:center; justify-content:center; cursor:pointer; color:var(--text-main); font-size:16px; box-shadow:0 2px 6px rgba(0,0,0,0.1); transition:background .15s; }
.modal-close:hover { background:#fff; }
.modal-body { padding:26px 30px 30px; }
.modal-tags { display:flex; gap:6px; flex-wrap:wrap; margin-bottom:12px; }
.modal-title { font-family:'Fraunces',serif; font-size:26px; font-weight:900; color:var(--text-main); letter-spacing:-.7px; line-height:1.15; margin-bottom:12px; }
.modal-desc { font-size:14.5px; color:var(--text-sub); line-height:1.72; margin-bottom:18px; }
.modal-carac-block { background:var(--primary-light); border:1px solid #c7d2fe; border-radius:var(--radius-sm); padding:12px 16px; margin-bottom:18px; }
.modal-carac-block h4 { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:var(--primary); margin-bottom:5px; }
.modal-carac-block p { font-size:13.5px; color:var(--text-main); font-weight:500; line-height:1.5; }
.modal-offer-block { background:#f0fdf4; border:1px solid #a7f3d0; border-radius:var(--radius-sm); padding:12px 16px; margin-bottom:18px; display:flex; align-items:center; justify-content:space-between; gap:12px; }
.modal-offer-block-left { display:flex; flex-direction:column; gap:2px; }
.modal-offer-label { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:#065f46; }
.modal-offer-name { font-size:13px; font-weight:600; color:var(--text-main); }
.btn-offer { display:inline-flex; align-items:center; gap:5px; background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0; border-radius:var(--radius-xs); padding:6px 13px; font-size:12px; font-weight:600; font-family:'DM Sans',sans-serif; cursor:pointer; transition:background .2s; white-space:nowrap; text-decoration:none; }
.btn-offer:hover { background:#d1fae5; }
.modal-footer { display:flex; align-items:center; justify-content:space-between; padding-top:18px; border-top:1px solid var(--border); flex-wrap:wrap; gap:14px; }
.modal-price-label span { font-size:12px; color:var(--text-dim); font-weight:500; display:block; margin-bottom:2px; }
.modal-price-value { font-family:'Fraunces',serif; font-size:30px; font-weight:900; color:var(--primary); letter-spacing:-.7px; }
.modal-price-value small { font-size:15px; color:var(--text-sub); font-weight:400; }
.modal-actions { display:flex; align-items:center; gap:9px; }
.btn-copy { display:inline-flex; align-items:center; gap:5px; background:transparent; color:var(--text-sub); border:1.5px solid var(--border); border-radius:var(--radius-xs); padding:8px 13px; font-size:12px; font-weight:600; font-family:'DM Sans',sans-serif; cursor:pointer; transition:all .2s; }
.btn-copy:hover { border-color:var(--primary); color:var(--primary); }
.btn-copy svg { width:13px; height:13px; }
.btn-copy.copied { border-color:var(--success); color:var(--success); background:var(--success-light); }
.btn-interested { display:inline-flex; align-items:center; gap:7px; background:linear-gradient(135deg,var(--primary),#7c3aed); color:#fff; border:none; border-radius:var(--radius-xs); padding:11px 24px; font-size:14px; font-weight:700; font-family:'DM Sans',sans-serif; cursor:pointer; transition:opacity .2s,transform .15s; box-shadow:0 3px 14px rgba(64,64,242,0.22); }
.btn-interested:hover { opacity:.9; transform:translateY(-1px); }
.btn-interested svg { width:15px; height:15px; }

/* ── FOOTER ── */
.footer-strip { background:var(--white); border-top:1px solid var(--border); padding:26px 48px; text-align:center; margin-top:40px; }
.footer-strip p { font-size:13px; color:var(--text-dim); }
.footer-strip a { color:var(--primary); text-decoration:none; font-weight:600; }

/* ── TOAST ── */
.toast { position:fixed; bottom:28px; left:50%; transform:translateX(-50%) translateY(20px); background:#1a1a2e; color:#fff; border-radius:var(--radius-sm); padding:10px 22px; font-size:13px; font-weight:500; opacity:0; pointer-events:none; transition:opacity .25s,transform .25s; z-index:600; }
.toast.show { opacity:1; transform:translateX(-50%) translateY(0); }

/* ── RESPONSIVE ── */
@media(max-width:900px) {
    nav { padding:0 20px; }
    .nav-links { display:none; }
    .hero-band { padding:36px 20px 32px; }
    .hero-band h1 { font-size:28px; }
    .hero-stats { gap:20px; }
    .page-wrapper { padding:24px 16px 56px; }
    .search-input { width:200px; }
    .products-grid { grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); }
    .view-list .product-card { flex-direction:column; }
    .view-list .pcard-img-wrap { width:100%; height:180px; }
    .filter-bar-inner { gap:18px; }
    .modal-box { border-radius:14px; }
    .modal-body { padding:18px 18px 22px; }
    .modal-title { font-size:20px; }
    .modal-img-wrap { height:200px; }
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
        <li><a href="#">Offres</a></li>
        <li><a href="#">Évènements</a></li>
        <li><a href="#" class="active">Produits</a></li>
        <li><a href="#">Mon Profil</a></li>
    </ul>
    <div class="nav-avatar" title="Mon compte">C</div>
</nav>

<!-- ════ HERO ════ -->
<div class="hero-band">
    <div class="hero-inner">
        <div class="hero-label">
            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 10V11"/></svg>
            Catalogue produits
        </div>
        <h1>Découvrez les produits<br>à promouvoir</h1>
        <p>Explorez les produits des marques partenaires et trouvez les collaborations qui correspondent à votre univers créatif.</p>
        <div class="hero-stats">
            <div class="hero-stat">
                <span class="hero-stat-value"><?= count($allProduits) ?></span>
                <span class="hero-stat-label">Produits disponibles</span>
            </div>
            <div class="hero-stat">
                <span class="hero-stat-value"><?= number_format($prixMoyen, 0, ',', ' ') ?> €</span>
                <span class="hero-stat-label">Prix moyen catalogue</span>
            </div>
            <div class="hero-stat">
                <span class="hero-stat-value">100%</span>
                <span class="hero-stat-label">Marques vérifiées</span>
            </div>
        </div>
    </div>
</div>

<!-- ════ CONTENT ════ -->
<div class="page-wrapper">

    <!-- TOOLBAR -->
    <div class="toolbar">
        <div class="toolbar-left">
            <p class="results-label">
                <strong><?= $total ?></strong> produit<?= $total > 1 ? 's' : '' ?>
                <?php if ($search !== ''): ?>
                    pour <em>"<?= htmlspecialchars($search) ?>"</em>
                <?php endif; ?>
            </p>
        </div>
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            <form method="GET" action="" style="display:flex;">
                <div class="search-wrap">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                    <input type="text" name="q" class="search-input" placeholder="Rechercher un produit…" value="<?= htmlspecialchars($search) ?>">
                    <?php if ($pmin !== null): ?><input type="hidden" name="pmin" value="<?= $pmin ?>"><?php endif; ?>
                    <?php if ($pmax !== null): ?><input type="hidden" name="pmax" value="<?= $pmax ?>"><?php endif; ?>
                    <?php if ($sort !== ''): ?><input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>"><?php endif; ?>
                </div>
                <button type="submit" class="btn-search">Chercher</button>
            </form>
            <div class="view-toggle">
                <button class="view-btn active" id="vbtn-grid" onclick="setView('grid')" title="Grille standard">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                </button>
                <button class="view-btn" id="vbtn-compact" onclick="setView('compact')" title="Grille compacte">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="5" height="5" rx="1"/><rect x="9.5" y="2" width="5" height="5" rx="1"/><rect x="17" y="2" width="5" height="5" rx="1"/><rect x="2" y="9.5" width="5" height="5" rx="1"/><rect x="9.5" y="9.5" width="5" height="5" rx="1"/><rect x="17" y="9.5" width="5" height="5" rx="1"/><rect x="2" y="17" width="5" height="5" rx="1"/><rect x="9.5" y="17" width="5" height="5" rx="1"/><rect x="17" y="17" width="5" height="5" rx="1"/></svg>
                </button>
                <button class="view-btn" id="vbtn-list" onclick="setView('list')" title="Liste">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
            </div>
        </div>
    </div>

    <!-- FILTER BAR -->
    <div class="filter-bar">
        <div class="filter-bar-inner">
            <div class="filter-group">
                <div class="filter-group-label">Trier par</div>
                <div class="chips-row">
                    <a href="?<?= http_build_query(array_merge($_GET,['sort'=>''])) ?>" class="chip <?= $sort==='' ? 'active' : '' ?>">Récents</a>
                    <a href="?<?= http_build_query(array_merge($_GET,['sort'=>'prix_asc'])) ?>" class="chip <?= $sort==='prix_asc' ? 'active' : '' ?>">Prix ↑</a>
                    <a href="?<?= http_build_query(array_merge($_GET,['sort'=>'prix_desc'])) ?>" class="chip <?= $sort==='prix_desc' ? 'active' : '' ?>">Prix ↓</a>
                    <a href="?<?= http_build_query(array_merge($_GET,['sort'=>'nom'])) ?>" class="chip <?= $sort==='nom' ? 'active' : '' ?>">A → Z</a>
                </div>
            </div>
            <div class="filter-group">
                <div class="filter-group-label">Fourchette de prix</div>
                <div class="price-range-wrap">
                    <div class="range-row">
                        <label>Min</label>
                        <input type="range" id="sliderMin" min="<?= $globalMin ?>" max="<?= $globalMax ?>" value="<?= $pmin ?? $globalMin ?>" step="1" oninput="updateSlider()">
                        <span class="range-val" id="valMin"><?= intval($pmin ?? $globalMin) ?> €</span>
                    </div>
                    <div class="range-row">
                        <label>Max</label>
                        <input type="range" id="sliderMax" min="<?= $globalMin ?>" max="<?= $globalMax ?>" value="<?= $pmax ?? $globalMax ?>" step="1" oninput="updateSlider()">
                        <span class="range-val" id="valMax"><?= intval($pmax ?? $globalMax) ?> €</span>
                    </div>
                    <div style="display:flex;gap:8px;margin-top:4px;">
                        <button onclick="applyPrice()" style="flex:1;padding:5px 0;border-radius:var(--radius-xs);border:1.5px solid var(--primary);background:var(--primary);color:#fff;font-size:12px;font-weight:600;font-family:'DM Sans',sans-serif;cursor:pointer;">Appliquer</button>
                        <?php if ($pmin !== null || $pmax !== null): ?>
                        <a href="?<?= http_build_query(array_filter(array_merge($_GET,['pmin'=>'','pmax'=>'']),fn($v)=>$v!=='')) ?>" style="flex:1;padding:5px 0;border-radius:var(--radius-xs);border:1.5px solid var(--border);background:transparent;color:var(--text-sub);font-size:12px;font-weight:600;font-family:'DM Sans',sans-serif;cursor:pointer;text-align:center;text-decoration:none;display:block;">Réinitialiser</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tags filtres actifs -->
        <?php $hasFilters = ($search!=='' || $sort!=='' || $pmin!==null || $pmax!==null); ?>
        <?php if ($hasFilters): ?>
        <div class="active-filters">
            <span class="active-filters-label">Filtres actifs :</span>
            <?php if ($search !== ''): ?>
                <span class="filter-tag">Recherche : "<?= htmlspecialchars($search) ?>" <a href="?<?= http_build_query(array_filter(array_merge($_GET,['q'=>'']),fn($v)=>$v!=='')) ?>" class="filter-tag-remove">✕</a></span>
            <?php endif; ?>
            <?php if ($sort !== ''): ?>
                <span class="filter-tag">Tri : <?= ['prix_asc'=>'Prix ↑','prix_desc'=>'Prix ↓','nom'=>'A→Z'][$sort] ?? $sort ?> <a href="?<?= http_build_query(array_filter(array_merge($_GET,['sort'=>'']),fn($v)=>$v!=='')) ?>" class="filter-tag-remove">✕</a></span>
            <?php endif; ?>
            <?php if ($pmin !== null || $pmax !== null): ?>
                <span class="filter-tag">Prix : <?= intval($pmin??$globalMin) ?> – <?= intval($pmax??$globalMax) ?> € <a href="?<?= http_build_query(array_filter(array_merge($_GET,['pmin'=>'','pmax'=>'']),fn($v)=>$v!=='')) ?>" class="filter-tag-remove">✕</a></span>
            <?php endif; ?>
            <a href="?" class="clear-all">Tout effacer</a>
        </div>
        <?php endif; ?>
    </div>

    <!-- GRID -->
    <?php if (empty($produits)): ?>
        <div class="empty-state">
            <span class="empty-icon"><?= $search !== '' ? '🔍' : '📦' ?></span>
            <h3><?= $search !== '' ? 'Aucun produit trouvé' : 'Catalogue bientôt disponible' ?></h3>
            <p>
                <?php if ($search !== ''): ?>
                    Aucun résultat pour <strong>"<?= htmlspecialchars($search) ?>"</strong>. Essayez d'autres mots-clés.
                <?php else: ?>
                    Les marques préparent leurs produits. Revenez prochainement pour découvrir les collaborations.
                <?php endif; ?>
            </p>
            <?php if ($hasFilters): ?>
                <a href="?" class="btn-clear">✕ Réinitialiser tous les filtres</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="products-grid" id="productsGrid">
            <?php foreach ($produits as $p):
                $isNew   = isNew($p);
                $isHot   = !empty($p['tendance']);
                $isPromo = !empty($p['promo']);
            ?>
            <div class="product-card" onclick="openModal(<?= (int)$p['idProduit'] ?>)">

                <div class="pcard-img-wrap">
                    <?php if (!empty($p['image'])): ?>
                        <img src="<?= $baseUrl ?>/Vue/public/produits/<?= htmlspecialchars($p['image']) ?>"
                             alt="<?= htmlspecialchars($p['nomProduit']) ?>" loading="lazy">
                    <?php else: ?>
                        <div class="pcard-img-empty">📦</div>
                    <?php endif; ?>

                    <!-- Badges flottants -->
                    <div class="badge-float-tl">
                        <?php if ($isNew):  ?><span class="badge badge-new">✦ Nouveau</span><?php endif; ?>
                        <?php if ($isHot):  ?><span class="badge badge-hot">🔥 Tendance</span><?php endif; ?>
                        <?php if ($isPromo):?><span class="badge badge-promo">% Promo</span><?php endif; ?>
                    </div>

                    <div class="price-badge-float"><?= number_format((float)$p['prix'],2,',',' ') ?> €</div>

                    <div class="fav-btn" id="fav-<?= $p['idProduit'] ?>"
                         onclick="event.stopPropagation(); toggleFav(<?= (int)$p['idProduit'] ?>, this)"
                         title="Ajouter aux favoris">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/>
                        </svg>
                    </div>
                </div>

                <div class="pcard-body">
                    <span class="pcard-brand-tag">
                        <svg width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                        Marque vérifiée
                    </span>
                    <div class="pcard-name"><?= htmlspecialchars($p['nomProduit']) ?></div>
                    <p class="pcard-desc"><?= htmlspecialchars($p['description']) ?></p>
                    <?php if (!empty($p['caracteristiques'])): ?>
                    <div class="pcard-carac">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                        <?= htmlspecialchars($p['caracteristiques']) ?>
                    </div>
                    <?php endif; ?>
                    <div class="pcard-footer">
                        <div>
                            <span class="price-main"><?= number_format((float)$p['prix'],2,',',' ') ?></span>
                            <span class="price-currency">€</span>
                        </div>
                        <button class="btn-voir" onclick="event.stopPropagation(); openModal(<?= (int)$p['idProduit'] ?>)">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            Voir
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- ════ MODAL ════ -->
<?php
$produitsJson = [];
foreach ($allProduits as $p) {
    $produitsJson[$p['idProduit']] = [
        'id'              => (int)$p['idProduit'],
        'nom'             => $p['nomProduit'],
        'description'     => $p['description'],
        'caracteristiques'=> $p['caracteristiques'],
        'prix'            => number_format((float)$p['prix'], 2, ',', ' '),
        'image'           => !empty($p['image'])
                              ? $baseUrl.'/Vue/public/produits/'.htmlspecialchars($p['image'],ENT_QUOTES)
                              : null,
        'isNew'           => isNew($p),
        'isHot'           => !empty($p['tendance']),
        'isPromo'         => !empty($p['promo']),
        'offreName'       => $p['offreName'] ?? null,
        'offreId'         => $p['offreId']   ?? null,
    ];
}
?>

<div class="modal-overlay" id="productModal" onclick="closeModalOutside(event)">
    <div class="modal-box" id="modalBox">
        <div class="modal-img-wrap" id="modalImgWrap">
            <button class="modal-close" onclick="closeModal()">✕</button>
            <div id="modalImg"></div>
            <div class="modal-badges-float" id="modalBadges"></div>
            <div class="modal-price-pill" id="modalPricePill"></div>
        </div>
        <div class="modal-body">
            <div class="modal-tags" id="modalTags"></div>
            <h2 class="modal-title" id="modalTitle"></h2>
            <p class="modal-desc" id="modalDesc"></p>
            <div class="modal-carac-block" id="modalCaracBlock">
                <h4>Caractéristiques</h4>
                <p id="modalCarac"></p>
            </div>
            <div class="modal-offer-block" id="modalOfferBlock" style="display:none">
                <div class="modal-offer-block-left">
                    <span class="modal-offer-label">Offre associée</span>
                    <span class="modal-offer-name" id="modalOfferName"></span>
                </div>
                <a href="#" class="btn-offer" id="modalOfferBtn">
                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    Voir l'offre
                </a>
            </div>
            <div class="modal-footer">
                <div class="modal-price-label">
                    <span>Prix catalogue</span>
                    <div class="modal-price-value" id="modalPriceValue"><small>€</small></div>
                </div>
                <div class="modal-actions">
                    <button class="btn-copy" id="btnCopy" onclick="copyLink()">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path stroke-linecap="round" stroke-linejoin="round" d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/></svg>
                        Copier le lien
                    </button>
                    <button class="btn-interested">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                        Je suis intéressé(e)
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ════ FOOTER ════ -->
<div class="footer-strip">
    <p>© 2026 <a href="#">Cre8Connect</a> — Plateforme de collaboration entre marques et créateurs.</p>
</div>

<div class="toast" id="toast"></div>

<script>
const DATA = <?= json_encode($produitsJson, JSON_UNESCAPED_UNICODE) ?>;
const BASE  = '<?= $baseUrl ?>';
let currentId = null;
let favs = JSON.parse(localStorage.getItem('cre8_favs') || '[]');

/* ── Restore favs ── */
favs.forEach(id => {
    const el = document.getElementById('fav-' + id);
    if (el) el.classList.add('on');
});

/* ── Fav toggle ── */
function toggleFav(id, el) {
    el.classList.toggle('on');
    if (el.classList.contains('on')) {
        if (!favs.includes(id)) favs.push(id);
        showToast('Ajouté aux favoris ❤️');
    } else {
        favs = favs.filter(f => f !== id);
        showToast('Retiré des favoris');
    }
    localStorage.setItem('cre8_favs', JSON.stringify(favs));
}

/* ── View toggle ── */
function setView(mode) {
    const grid = document.getElementById('productsGrid');
    if (!grid) return;
    grid.className = 'products-grid' + (mode !== 'grid' ? ' view-' + mode : '');
    ['grid','compact','list'].forEach(m => {
        document.getElementById('vbtn-' + m)?.classList.toggle('active', m === mode);
    });
    localStorage.setItem('cre8_view', mode);
}
const savedView = localStorage.getItem('cre8_view');
if (savedView && savedView !== 'grid') setView(savedView);

/* ── Price slider ── */
function updateSlider() {
    const mn = parseInt(document.getElementById('sliderMin').value);
    const mx = parseInt(document.getElementById('sliderMax').value);
    document.getElementById('valMin').textContent = mn + ' €';
    document.getElementById('valMax').textContent = mx + ' €';
}
function applyPrice() {
    const mn = document.getElementById('sliderMin').value;
    const mx = document.getElementById('sliderMax').value;
    const params = new URLSearchParams(window.location.search);
    params.set('pmin', mn);
    params.set('pmax', mx);
    window.location.search = params.toString();
}

/* ── Modal ── */
function openModal(id) {
    const p = DATA[id];
    if (!p) return;
    currentId = id;

    document.getElementById('modalImg').innerHTML = p.image
        ? `<img src="${p.image}" alt="${p.nom}" style="width:100%;height:100%;object-fit:cover;display:block;">`
        : `<div class="modal-img-empty">📦</div>`;

    let badges = '';
    if (p.isNew)   badges += `<span class="badge badge-new">✦ Nouveau</span>`;
    if (p.isHot)   badges += `<span class="badge badge-hot">🔥 Tendance</span>`;
    if (p.isPromo) badges += `<span class="badge badge-promo">% Promo</span>`;
    document.getElementById('modalBadges').innerHTML = badges;

    document.getElementById('modalPricePill').textContent = p.prix + ' €';

    let tags = `<span class="badge badge-verified">✓ Marque vérifiée</span>`;
    document.getElementById('modalTags').innerHTML = tags;

    document.getElementById('modalTitle').textContent = p.nom;
    document.getElementById('modalDesc').textContent  = p.description;

    const caracBlock = document.getElementById('modalCaracBlock');
    document.getElementById('modalCarac').textContent = p.caracteristiques || '—';
    caracBlock.style.display = p.caracteristiques ? '' : 'none';

    const offerBlock = document.getElementById('modalOfferBlock');
    if (p.offreName && p.offreId) {
        document.getElementById('modalOfferName').textContent = p.offreName;
        document.getElementById('modalOfferBtn').href = BASE + '/Vue/FrontOffice/offre/detail.php?id=' + p.offreId;
        offerBlock.style.display = '';
    } else {
        offerBlock.style.display = 'none';
    }

    document.getElementById('modalPriceValue').innerHTML = p.prix + ' <small>€</small>';

    document.getElementById('btnCopy').classList.remove('copied');
    document.getElementById('btnCopy').innerHTML = `<svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path stroke-linecap="round" stroke-linejoin="round" d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/></svg> Copier le lien`;

    document.getElementById('productModal').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('productModal').classList.remove('open');
    document.body.style.overflow = '';
    currentId = null;
}

function closeModalOutside(e) {
    if (e.target === document.getElementById('productModal')) closeModal();
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

/* ── Copy link ── */
function copyLink() {
    if (!currentId) return;
    const url = window.location.origin + window.location.pathname + '?voir=' + currentId;
    navigator.clipboard.writeText(url).then(() => {
        const btn = document.getElementById('btnCopy');
        btn.classList.add('copied');
        btn.innerHTML = `<svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Copié !`;
        showToast('Lien copié dans le presse-papier ✓');
        setTimeout(() => {
            btn.classList.remove('copied');
            btn.innerHTML = `<svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path stroke-linecap="round" stroke-linejoin="round" d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/></svg> Copier le lien`;
        }, 2500);
    });
}

/* ── Toast ── */
function showToast(msg) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 2400);
}

/* ── Open modal from URL ?voir= ── */
const urlParams = new URLSearchParams(window.location.search);
const voirId = urlParams.get('voir');
if (voirId && DATA[parseInt(voirId)]) openModal(parseInt(voirId));
</script>
</body>
</html>