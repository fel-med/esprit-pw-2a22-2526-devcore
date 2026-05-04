<?php
/**
 * Vue/FrontOffice/campagne/indexC.php
 * Rôle : CRÉATEUR — parcourir les campagnes + suggestions IA
 */

require_once __DIR__ . '/../../../Controleur/campagneC.php';
require_once __DIR__ . '/../../../Controleur/produitC.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$campagneC = new CampagneC();
$produitC  = new ProduitC();
$baseUrl   = '/projet/Esprit-PW-2A22-2526-Devcore';

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
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text-main);min-height:100vh;}

nav{background:var(--white);border-bottom:1px solid var(--border);padding:0 48px;height:var(--nav-h);display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:200;box-shadow:0 2px 12px rgba(15,14,26,0.04);}
.nav-logo{font-family:'Fraunces',serif;font-size:20px;font-weight:800;color:var(--primary);text-decoration:none;}
.nav-links{display:flex;gap:6px;list-style:none;}
.nav-links a{text-decoration:none;color:var(--text-sub);font-size:13.5px;font-weight:600;padding:6px 14px;border-radius:8px;transition:all .18s;}
.nav-links a:hover,.nav-links a.active{background:var(--primary-light);color:var(--primary);}
.nav-right{display:flex;align-items:center;gap:12px;}
.nav-badge{background:linear-gradient(135deg,var(--primary),#a78bfa);color:#fff;border-radius:20px;padding:5px 14px;font-size:12px;font-weight:700;}
.nav-avatar{width:36px;height:36px;border-radius:50%;background:var(--primary-light);color:var(--primary);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:14px;border:2px solid var(--primary-border);}

/* HERO */
.hero{background:linear-gradient(135deg,#0f0e1a 0%,#1a1632 60%,#2b1f60 100%);padding:56px 48px;color:#fff;position:relative;overflow:hidden;}
.hero::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse 80% 60% at 50% 0%,rgba(91,79,255,.35),transparent);}
.hero-content{position:relative;max-width:640px;margin:0 auto;text-align:center;}
.hero-tag{display:inline-flex;align-items:center;gap:6px;background:rgba(91,79,255,.2);border:1px solid rgba(91,79,255,.35);border-radius:20px;padding:5px 14px;font-size:12px;font-weight:700;color:#a78bfa;margin-bottom:18px;}
.hero h1{font-family:'Fraunces',serif;font-size:40px;font-weight:800;line-height:1.1;letter-spacing:-1px;margin-bottom:12px;}
.hero p{font-size:15px;color:rgba(255,255,255,.7);line-height:1.65;margin-bottom:28px;}
.hero-stats{display:flex;justify-content:center;gap:36px;}
.hero-stat-val{font-family:'Fraunces',serif;font-size:26px;font-weight:800;}
.hero-stat-label{font-size:12px;color:rgba(255,255,255,.55);margin-top:2px;}

.page-wrapper{max-width:1160px;margin:0 auto;padding:40px 24px 80px;}

/* IA SUGGESTIONS PANEL */
.ia-panel{background:linear-gradient(135deg,#edfaf5,#d1fae5);border:1.5px solid var(--success-border);border-radius:var(--radius);padding:24px 28px;margin-bottom:32px;}
.ia-panel-header{display:flex;align-items:center;gap:10px;margin-bottom:16px;}
.ia-panel-header h2{font-family:'Fraunces',serif;font-size:18px;font-weight:800;color:var(--success);}
.ia-form-grid{display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:12px;align-items:end;}
.ia-form-group{display:flex;flex-direction:column;gap:4px;}
.ia-form-group label{font-size:12px;font-weight:700;color:var(--text-sub);}
.ia-form-group input{padding:9px 13px;border:1.5px solid var(--border);border-radius:var(--radius-sm);font-family:'DM Sans',sans-serif;font-size:13px;background:var(--white);outline:none;transition:border-color .2s;}
.ia-form-group input:focus{border-color:var(--success);}
.btn-ia{display:inline-flex;align-items:center;gap:7px;background:linear-gradient(135deg,var(--success),#059669);color:#fff;border:none;border-radius:var(--radius-sm);padding:10px 18px;font-size:13px;font-weight:700;font-family:'DM Sans',sans-serif;cursor:pointer;white-space:nowrap;}
.ia-result{background:var(--white);border:1.5px solid var(--success-border);border-radius:var(--radius-sm);padding:18px;margin-top:16px;}
.ia-sug-card{background:var(--success-light);border:1px solid var(--success-border);border-radius:var(--radius-sm);padding:14px;margin-bottom:10px;}
.ia-sug-type{font-weight:700;color:var(--success);margin-bottom:4px;font-size:14px;}
.ia-sug-text{font-size:13px;color:var(--text-sub);line-height:1.6;}
.ia-sug-tip{font-size:12px;background:rgba(14,163,112,.08);padding:7px 10px;border-radius:7px;margin-top:6px;color:var(--success);}
.ia-conseil{padding:12px 16px;background:rgba(14,163,112,.06);border-radius:var(--radius-sm);margin-top:12px;font-size:13px;line-height:1.6;}
.ia-error{background:var(--danger-light);color:var(--danger);border-radius:var(--radius-sm);padding:10px 14px;font-size:13px;font-weight:600;margin-top:12px;}
.spinner{width:16px;height:16px;border:2.5px solid rgba(14,163,112,.3);border-top-color:var(--success);border-radius:50%;animation:spin .6s linear infinite;}
@keyframes spin{to{transform:rotate(360deg)}}
.ia-loading{display:none;align-items:center;gap:10px;padding:12px 0;color:var(--success);font-weight:600;font-size:13px;}
.ia-loading.show{display:flex;}

/* STATUS CHIPS */
.status-chips{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px;}
.s-chip{display:inline-flex;align-items:center;gap:5px;background:var(--white);border:1.5px solid var(--border);border-radius:20px;padding:6px 14px;font-size:12.5px;font-weight:700;cursor:pointer;border:none;font-family:'DM Sans',sans-serif;transition:all .18s;color:var(--text-sub);}
.s-chip:hover,.s-chip.active{background:var(--primary);color:#fff;}

/* FILTER BAR */
.filter-bar{background:var(--white);border:1px solid var(--border);border-radius:var(--radius);padding:16px 20px;margin-bottom:24px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;box-shadow:var(--card-shadow);}
.search-wrap{position:relative;flex:1;min-width:200px;}
.search-wrap svg{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--text-dim);pointer-events:none;width:14px;height:14px;}
.search-input{width:100%;background:var(--bg);border:1.5px solid var(--border);border-radius:var(--radius-sm);padding:9px 12px 9px 32px;font-size:13px;font-family:'DM Sans',sans-serif;outline:none;transition:border-color .2s;}
.search-input:focus{border-color:var(--primary);}
.filter-select{background:var(--bg);border:1.5px solid var(--border);border-radius:var(--radius-sm);padding:9px 12px;font-size:13px;font-family:'DM Sans',sans-serif;outline:none;cursor:pointer;}
.result-count{font-size:12.5px;color:var(--text-sub);font-weight:600;white-space:nowrap;}

/* CAMP GRID */
.camp-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(330px,1fr));gap:20px;}
.camp-card{background:var(--white);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;box-shadow:var(--card-shadow);display:flex;flex-direction:column;transition:transform .22s,box-shadow .22s,border-color .22s;position:relative;}
.camp-card:hover{transform:translateY(-4px);box-shadow:var(--card-shadow-hover);border-color:var(--primary-border);}
.camp-accent{height:4px;}
.accent-active{background:linear-gradient(90deg,#0ea370,#34d399);}
.accent-brouillon{background:linear-gradient(90deg,#f59e0b,#fbbf24);}
.accent-terminee{background:linear-gradient(90deg,#3b82f6,#60a5fa);}
.accent-annulee{background:linear-gradient(90deg,#f43f5e,#fb7185);}
.camp-card-header{padding:18px 18px 0;display:flex;align-items:flex-start;justify-content:space-between;gap:8px;}
.camp-card-title{font-family:'Fraunces',serif;font-size:16px;font-weight:800;flex:1;}
.camp-badge{display:inline-flex;align-items:center;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;flex-shrink:0;}
.camp-card-body{padding:12px 18px 16px;flex:1;display:flex;flex-direction:column;gap:10px;}
.camp-desc{font-size:13px;color:var(--text-sub);line-height:1.65;overflow:hidden;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;}
.camp-obj{font-size:12px;background:var(--primary-light);color:var(--primary);border-radius:7px;padding:7px 11px;font-weight:600;}
.camp-meta{font-size:12px;color:var(--text-sub);}
.camp-budget{font-family:'Fraunces',serif;font-size:22px;font-weight:800;color:var(--primary);}
.camp-prod-pill{display:inline-flex;align-items:center;gap:5px;border-radius:20px;padding:4px 12px;font-size:12px;font-weight:700;}
.camp-prod-pill.has{background:var(--success-light);color:var(--success);border:1px solid var(--success-border);}
.camp-prod-pill.none{background:var(--bg);color:var(--text-dim);border:1px solid var(--border);}
.camp-card-footer{padding:13px 18px;border-top:1px solid var(--border);display:flex;gap:8px;}
.btn-detail{flex:1;padding:9px;background:var(--primary);color:#fff;border:none;border-radius:var(--radius-sm);font-size:13px;font-weight:700;font-family:'DM Sans',sans-serif;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;box-shadow:0 2px 8px var(--primary-glow);}
.btn-detail:hover{background:var(--primary-hover);}
.btn-apply{padding:9px 14px;background:var(--success-light);color:var(--success);border:1px solid var(--success-border);border-radius:var(--radius-sm);font-size:12px;font-weight:700;cursor:pointer;font-family:'DM Sans',sans-serif;}

.empty-state{text-align:center;padding:64px 24px;background:var(--white);border-radius:var(--radius);border:1.5px dashed var(--border);}
.no-results{text-align:center;padding:48px;background:var(--white);border-radius:var(--radius);border:1px solid var(--border);}
.section-head{display:flex;align-items:center;gap:10px;margin-bottom:16px;}
.section-head h2{font-family:'Fraunces',serif;font-size:22px;font-weight:800;letter-spacing:-0.4px;}
.count-pill{background:var(--primary-light);color:var(--primary);border-radius:20px;padding:3px 11px;font-size:12px;font-weight:700;}

/* DETAIL MODAL */
.detail-modal{position:fixed;inset:0;background:rgba(15,14,26,.6);z-index:300;display:none;align-items:center;justify-content:center;padding:20px;overflow-y:auto;}
.detail-modal.open{display:flex;}
.detail-box{background:var(--white);border-radius:var(--radius);width:760px;max-width:100%;box-shadow:0 24px 80px rgba(15,14,26,.22);animation:popIn .22s ease;display:flex;flex-direction:column;margin:auto;}
@keyframes popIn{from{opacity:0;transform:scale(.94);}to{opacity:1;transform:scale(1);}}
.detail-header{padding:24px 28px 0;display:flex;align-items:flex-start;justify-content:space-between;gap:12px;}
.detail-title{font-family:'Fraunces',serif;font-size:22px;font-weight:800;line-height:1.2;}
.detail-close{background:var(--bg);border:1.5px solid var(--border);border-radius:50%;width:32px;height:32px;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:14px;color:var(--text-sub);}
.detail-body{padding:18px 28px 24px;display:flex;flex-direction:column;gap:16px;}
.detail-section-label{font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-dim);margin-bottom:6px;}
.detail-text{font-size:14px;color:var(--text-sub);line-height:1.7;}
.detail-meta-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.detail-meta-card{background:var(--bg);border-radius:var(--radius-sm);padding:14px 16px;border:1px solid var(--border);}
.detail-meta-val{font-family:'Fraunces',serif;font-size:18px;font-weight:800;margin-bottom:3px;}
.detail-meta-label{font-size:11px;color:var(--text-dim);font-weight:600;text-transform:uppercase;letter-spacing:.05em;}
.detail-products-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px;margin-top:12px;}
.detail-prod-card{background:var(--bg);border:1px solid var(--border);border-radius:var(--radius-sm);overflow:hidden;transition:border-color .2s;}
.detail-prod-card:hover{border-color:var(--primary-border);}
.detail-prod-img{height:100px;background:linear-gradient(135deg,#f0effb,var(--bg));display:flex;align-items:center;justify-content:center;font-size:28px;overflow:hidden;}
.detail-prod-img img{width:100%;height:100%;object-fit:cover;}
.detail-prod-body{padding:8px 10px;}
.detail-prod-name{font-size:12px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.detail-prod-price{font-family:'Fraunces',serif;font-size:13px;font-weight:800;color:var(--primary);}
.prod-loader{text-align:center;padding:24px;color:var(--text-dim);font-size:13px;grid-column:1/-1;}
.detail-footer{padding:16px 28px 22px;border-top:1px solid var(--border);display:flex;gap:10px;}
.btn-apply-big{flex:1;padding:12px;background:var(--primary);color:#fff;border:none;border-radius:var(--radius-sm);font-size:14px;font-weight:700;font-family:'DM Sans',sans-serif;cursor:pointer;}
.btn-close-detail{padding:12px 20px;background:var(--bg);color:var(--text-sub);border:1.5px solid var(--border);border-radius:var(--radius-sm);font-size:13px;font-weight:600;cursor:pointer;font-family:'DM Sans',sans-serif;}
</style>
</head>
<body>

<nav>
    <a href="#" class="nav-logo">Cre8Connect</a>
    <ul class="nav-links">
        <li><a href="#">Offres</a></li>
        <li><a href="#" class="active">Campagnes</a></li>
        <li><a href="../produit/indexC.php">Produits</a></li>
        <li><a href="../contrat/indexC.php">Contrats</a></li>
    </ul>
    <div class="nav-right">
        <span class="nav-badge">Créateur</span>
        <div class="nav-avatar">C</div>
    </div>
</nav>

<!-- HERO -->
<div class="hero">
    <div class="hero-content">
        <div class="hero-tag">⚡ Campagnes disponibles</div>
        <h1>Découvrez les Campagnes des Marques</h1>
        <p>Parcourez les campagnes actives, explorez les produits liés et postulez pour collaborer.</p>
        <div class="hero-stats">
            <div><div class="hero-stat-val"><?= $totalCampagnes ?></div><div class="hero-stat-label">Campagnes</div></div>
            <div><div class="hero-stat-val"><?= $nbActives ?></div><div class="hero-stat-label">Actives maintenant</div></div>
            <div><div class="hero-stat-val"><?= number_format($budgetTotal / 1000, 0) ?>K€</div><div class="hero-stat-label">Budget total</div></div>
        </div>
    </div>
</div>

<div class="page-wrapper">

    <!-- IA SUGGESTIONS -->
    <div class="ia-panel">
        <div class="ia-panel-header">
            <span style="font-size:22px;">💡</span>
            <h2>Suggestions de campagnes personnalisées par l'IA</h2>
        </div>
        <form method="POST" id="iaForm">
            <input type="hidden" name="action" value="ia_suggestions">
            <div class="ia-form-grid">
                <div class="ia-form-group">
                    <label>Vos compétences *</label>
                    <input type="text" name="competences" placeholder="Ex : Vidéo, photo, rédaction" value="<?= htmlspecialchars($_POST['competences'] ?? '') ?>">
                </div>
                <div class="ia-form-group">
                    <label>Vos centres d'intérêt *</label>
                    <input type="text" name="interets" placeholder="Ex : Mode, tech, bien-être" value="<?= htmlspecialchars($_POST['interets'] ?? '') ?>">
                </div>
                <div class="ia-form-group">
                    <label>Votre audience *</label>
                    <input type="text" name="audience" placeholder="Ex : 18-25 ans, Instagram, 10K abonnés" value="<?= htmlspecialchars($_POST['audience'] ?? '') ?>">
                </div>
                <button type="submit" class="btn-ia" onclick="document.getElementById('iaLoading').classList.add('show')">
                    💡 Obtenir suggestions
                </button>
            </div>
        </form>
        <div class="ia-loading" id="iaLoading"><div class="spinner"></div> L'IA analyse votre profil…</div>
        <?php if ($iaError): ?><div class="ia-error">⚠️ <?= htmlspecialchars($iaError) ?></div><?php endif; ?>
        <?php if ($iaResult): ?>
        <div class="ia-result">
            <div style="font-size:14px;font-weight:700;color:var(--success);margin-bottom:12px;">🎯 Campagnes recommandées pour vous</div>
            <?php if (!empty($iaResult['suggestions'])): foreach ($iaResult['suggestions'] as $s): ?>
            <div class="ia-sug-card">
                <div class="ia-sug-type"><?= htmlspecialchars($s['type_campagne'] ?? '') ?></div>
                <div class="ia-sug-text"><strong>Pourquoi :</strong> <?= htmlspecialchars($s['raison'] ?? '') ?></div>
                <div class="ia-sug-tip">💡 <?= htmlspecialchars($s['conseil'] ?? '') ?></div>
            </div>
            <?php endforeach; endif; ?>
            <?php if (!empty($iaResult['conseil_general'])): ?>
            <div class="ia-conseil">📌 <strong>Conseil :</strong> <?= htmlspecialchars($iaResult['conseil_general']) ?></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- STATUS CHIPS -->
    <div class="status-chips">
        <button class="s-chip active" onclick="filterChip('',this)">Toutes</button>
        <button class="s-chip" onclick="filterChip('active',this)">✅ Actives</button>
        <button class="s-chip" onclick="filterChip('brouillon',this)">📝 Brouillons</button>
        <button class="s-chip" onclick="filterChip('terminee',this)">🏁 Terminées</button>
    </div>

    <!-- FILTER BAR -->
    <div class="filter-bar">
        <div class="search-wrap">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
            <input type="text" id="searchInput" class="search-input" placeholder="Rechercher campagnes, marques, objectifs…">
        </div>
        <select id="sortSelect" class="filter-select" onchange="sortCampagnes()">
            <option value="">Trier par…</option>
            <option value="titre">Nom A→Z</option>
            <option value="budget_desc">Budget ↓</option>
            <option value="budget_asc">Budget ↑</option>
        </select>
        <span class="result-count"><span id="visibleCount"><?= $totalCampagnes ?></span> campagne(s)</span>
    </div>

    <!-- CAMP GRID -->
    <div class="section-head">
        <h2>📋 Toutes les campagnes</h2>
    </div>

    <?php if (empty($campagnes)): ?>
    <div class="empty-state">
        <div style="font-size:52px;margin-bottom:16px;">⚡</div>
        <h3 style="font-family:'Fraunces',serif;font-size:20px;font-weight:800;margin-bottom:8px;">Aucune campagne disponible</h3>
        <p style="color:var(--text-sub);">Les marques publient régulièrement de nouvelles campagnes. Revenez bientôt !</p>
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
                    📦 <?= $nbProd ?> produit<?= $nbProd !== 1 ? 's' : '' ?> lié<?= $nbProd !== 1 ? 's' : '' ?>
                </span>
            </div>
            <div class="camp-card-footer">
                <button class="btn-detail" onclick="openDetail(<?= $c['idCampagne'] ?>)">
                    👁 Voir les détails
                </button>
                <?php if ($c['statut'] === 'active'): ?>
                <button class="btn-apply" onclick="alert('Candidature envoyée !')">🙋 Postuler</button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <div id="noResults" class="no-results" style="display:none;">
        <div style="font-size:36px;margin-bottom:12px;">🔍</div>
        <h3 style="font-family:'Fraunces',serif;font-size:18px;font-weight:800;margin-bottom:6px;">Aucune campagne trouvée</h3>
        <p style="color:var(--text-sub);">Modifiez vos filtres ou votre recherche.</p>
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
            <div><div class="detail-section-label">Description</div><div class="detail-text" id="detailDesc"></div></div>
            <div id="detailObjWrap"><div class="detail-section-label">Objectif</div><div class="camp-obj" id="detailObj"></div></div>
            <div class="detail-meta-grid">
                <div class="detail-meta-card"><div class="detail-meta-val" id="detailBudget"></div><div class="detail-meta-label">Budget de la campagne</div></div>
                <div class="detail-meta-card"><div class="detail-meta-val" id="detailDates" style="font-size:14px;"></div><div class="detail-meta-label">Période</div></div>
            </div>
            <div>
                <div class="detail-section-label">Produits de cette campagne</div>
                <div class="detail-products-grid" id="detailProductsGrid"><div class="prod-loader">⏳ Chargement…</div></div>
            </div>
        </div>
        <div class="detail-footer">
            <button class="btn-apply-big" id="detailApplyBtn" onclick="alert('Candidature envoyée !')">🙋 Postuler à cette campagne</button>
            <button class="btn-close-detail" onclick="closeDetail()">Fermer</button>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?= $baseUrl ?>';

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

// Chip filter
let activeChip = '';
function filterChip(val, btn) {
    activeChip = val;
    document.querySelectorAll('.s-chip').forEach(c => c.classList.remove('active'));
    btn.classList.add('active');
    filterCampagnes();
}

document.getElementById('searchInput').addEventListener('input', filterCampagnes);
function filterCampagnes() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    const cards = document.querySelectorAll('#campGrid .camp-card');
    let v = 0;
    cards.forEach(card => {
        const mQ = !q || (card.dataset.titre||'').includes(q) || (card.dataset.brand||'').includes(q);
        const mS = !activeChip || card.dataset.statut === activeChip;
        card.style.display = mQ && mS ? '' : 'none';
        if (mQ && mS) v++;
    });
    document.getElementById('visibleCount').textContent = v;
    const noRes = document.getElementById('noResults');
    if (noRes) noRes.style.display = v === 0 ? '' : 'none';
}

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
        const img = p.image ? `<img src="${BASE_URL}/Vue/public/produits/${p.image}" alt="">` : '📦';
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
</body>
</html>