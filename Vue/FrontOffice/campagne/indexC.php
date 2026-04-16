<?php
require_once __DIR__ . '/../../../Controleur/campagneC.php';

session_start();
$campagneC = new CampagneC();
$baseUrl   = '/projet/Esprit-PW-2A22-2526-Devcore';

// ── DATA ──────────────────────────────────────────────────────────────────────
$campagnes = $campagneC->afficherCampagnes();
$statuts   = $campagneC->getStatuts();

// ── DETAIL MODAL ──────────────────────────────────────────────────────────────
$campagneDetail = null;
if (isset($_GET['voir'])) {
    $campagneDetail = $campagneC->recupererCampagne(intval($_GET['voir']));
}

// ── KPIs ──────────────────────────────────────────────────────────────────────
$totalCampagnes = count($campagnes);
$nbActives      = count(array_filter($campagnes, fn($c) => $c['statut'] === 'active'));
$budgets        = array_column($campagnes, 'budget');
$budgetTotal    = array_sum($budgets);

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
    <title>Campaigns — Cre8Connect</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:ital,wght@0,700;0,800;1,700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary:       #5b4fff;
            --primary-hover: #4a3ee8;
            --primary-light: #eeecff;
            --primary-glow:  rgba(91,79,255,0.18);
            --primary-border:rgba(91,79,255,0.2);
            --text-main:     #0f0e1a;
            --text-sub:      #6b6f80;
            --text-dim:      #a0a4b2;
            --border:        #ebebf2;
            --bg:            #f6f6fc;
            --white:         #ffffff;
            --success:       #0ea370;
            --success-light: #edfaf5;
            --warning:       #f59e0b;
            --warning-light: #fffbeb;
            --info:          #3b82f6;
            --info-light:    #eff6ff;
            --danger:        #f43f5e;
            --danger-light:  #fff1f3;
            --card-shadow:   0 1px 3px rgba(15,14,26,0.06), 0 4px 16px rgba(91,79,255,0.06);
            --card-shadow-hover: 0 8px 32px rgba(91,79,255,0.14);
            --radius:        14px;
            --radius-sm:     8px;
            --nav-h:         66px;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text-main); min-height: 100vh; }

        /* NAV */
        nav { background: var(--white); border-bottom: 1px solid var(--border); padding: 0 48px; height: var(--nav-h); display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 200; box-shadow: 0 1px 0 var(--border), 0 2px 12px rgba(15,14,26,0.04); }
        .nav-logo { display: flex; align-items: center; gap: 10px; text-decoration: none; }
        .nav-logo img { width: 36px; height: 36px; object-fit: contain; border-radius: 9px; }
        .nav-logo-text { font-family: 'Fraunces', serif; font-size: 19px; font-weight: 800; color: var(--primary); letter-spacing: -0.5px; }
        .nav-links { display: flex; gap: 6px; list-style: none; }
        .nav-links a { text-decoration: none; color: var(--text-sub); font-size: 13.5px; font-weight: 600; padding: 6px 14px; border-radius: 8px; transition: background .18s, color .18s; }
        .nav-links a:hover, .nav-links a.active { background: var(--primary-light); color: var(--primary); }
        .nav-right { display: flex; align-items: center; gap: 12px; }
        .nav-badge { background: var(--primary); color: #fff; border-radius: 20px; padding: 5px 14px; font-size: 12px; font-weight: 700; }
        .nav-avatar { width: 36px; height: 36px; border-radius: 50%; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 14px; cursor: pointer; border: 2px solid var(--primary-border); }

        /* HERO */
        .hero { background: linear-gradient(135deg, #0f0e1a 0%, #1a1632 60%, #2b1f60 100%); padding: 60px 48px; text-align: center; color: #fff; position: relative; overflow: hidden; }
        .hero::before { content: ''; position: absolute; inset: 0; background: radial-gradient(ellipse 80% 60% at 50% 0%, rgba(91,79,255,.35), transparent); }
        .hero-content { position: relative; max-width: 640px; margin: 0 auto; }
        .hero-tag { display: inline-flex; align-items: center; gap: 6px; background: rgba(91,79,255,.2); border: 1px solid rgba(91,79,255,.35); border-radius: 20px; padding: 5px 14px; font-size: 12px; font-weight: 700; color: #a78bfa; margin-bottom: 18px; }
        .hero h1 { font-family: 'Fraunces', serif; font-size: 42px; font-weight: 800; line-height: 1.1; letter-spacing: -1px; margin-bottom: 14px; }
        .hero p { font-size: 15px; color: rgba(255,255,255,.7); line-height: 1.65; margin-bottom: 30px; }
        .hero-stats { display: flex; justify-content: center; gap: 36px; }
        .hero-stat-val { font-family: 'Fraunces', serif; font-size: 28px; font-weight: 800; }
        .hero-stat-label { font-size: 12px; color: rgba(255,255,255,.55); margin-top: 2px; }

        /* PAGE */
        .page-wrapper { max-width: 1160px; margin: 0 auto; padding: 40px 24px 80px; }

        /* FILTER BAR */
        .filter-bar { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius); padding: 18px 22px; margin-bottom: 28px; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; box-shadow: var(--card-shadow); }
        .search-wrap { position: relative; flex: 1; min-width: 200px; }
        .search-wrap svg { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: var(--text-dim); pointer-events: none; width: 15px; height: 15px; }
        .search-input { width: 100%; background: var(--bg); border: 1.5px solid var(--border); border-radius: var(--radius-sm); padding: 9px 12px 9px 34px; font-size: 13.5px; font-family: 'DM Sans', sans-serif; color: var(--text-main); outline: none; transition: border-color .2s, box-shadow .2s; }
        .search-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-glow); }
        .filter-select { background: var(--bg); border: 1.5px solid var(--border); border-radius: var(--radius-sm); padding: 9px 12px; font-size: 13px; font-family: 'DM Sans', sans-serif; outline: none; cursor: pointer; color: var(--text-main); }
        .filter-select:focus { border-color: var(--primary); }
        .pf-reset { background: transparent; border: 1.5px solid var(--border); border-radius: var(--radius-sm); padding: 9px 14px; font-size: 12.5px; font-family: 'DM Sans', sans-serif; color: var(--text-sub); cursor: pointer; transition: border-color .2s, color .2s; }
        .pf-reset:hover { border-color: var(--danger); color: var(--danger); }
        .result-count { font-size: 12.5px; color: var(--text-sub); font-weight: 600; white-space: nowrap; }

        /* STATUS CHIPS */
        .status-chips { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 22px; }
        .s-chip { display: inline-flex; align-items: center; gap: 5px; background: var(--white); border: 1.5px solid var(--border); border-radius: 20px; padding: 6px 14px; font-size: 12.5px; font-weight: 700; cursor: pointer; transition: all .18s; color: var(--text-sub); }
        .s-chip:hover { border-color: var(--primary); color: var(--primary); background: var(--primary-light); }
        .s-chip.active { background: var(--primary); color: #fff; border-color: var(--primary); }
        .s-chip .dot { width: 7px; height: 7px; border-radius: 50%; background: currentColor; opacity: .5; }

        /* CAMPAIGN GRID */
        .camp-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(330px, 1fr)); gap: 20px; }
        .camp-card { background: var(--white); border-radius: var(--radius); overflow: hidden; box-shadow: var(--card-shadow); border: 1px solid var(--border); display: flex; flex-direction: column; transition: transform .22s, box-shadow .22s, border-color .22s; cursor: pointer; }
        .camp-card:hover { transform: translateY(-4px); box-shadow: var(--card-shadow-hover); border-color: var(--primary-border); }

        /* Card top accent */
        .camp-card-accent { height: 4px; }
        .accent-active   { background: linear-gradient(90deg, #0ea370, #34d399); }
        .accent-brouillon{ background: linear-gradient(90deg, #f59e0b, #fbbf24); }
        .accent-terminee { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
        .accent-annulee  { background: linear-gradient(90deg, #f43f5e, #fb7185); }

        .camp-card-header { padding: 18px 18px 0; display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; }
        .camp-card-title { font-family: 'Fraunces', serif; font-size: 16px; font-weight: 800; color: var(--text-main); line-height: 1.25; flex: 1; }
        .camp-badge { display: inline-flex; align-items: center; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 20px; flex-shrink: 0; }
        .camp-card-body { padding: 12px 18px 16px; flex: 1; display: flex; flex-direction: column; gap: 10px; }
        .camp-desc { font-size: 13px; color: var(--text-sub); line-height: 1.65; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; }
        .camp-obj { font-size: 12px; background: var(--primary-light); color: var(--primary); border-radius: 7px; padding: 7px 11px; font-weight: 600; }
        .camp-meta { display: flex; flex-direction: column; gap: 4px; font-size: 12px; color: var(--text-sub); }
        .camp-meta-row { display: flex; align-items: center; gap: 6px; }
        .camp-budget { font-family: 'Fraunces', serif; font-size: 22px; font-weight: 800; color: var(--primary); margin-top: 4px; }
        .camp-brand { display: inline-flex; align-items: center; gap: 5px; background: var(--bg); border: 1px solid var(--border); border-radius: 20px; padding: 3px 10px; font-size: 11px; font-weight: 700; color: var(--text-sub); }
        .camp-card-footer { padding: 13px 18px; border-top: 1px solid var(--border); display: flex; gap: 8px; }
        .btn-see-detail { flex: 1; padding: 9px; background: var(--primary-light); color: var(--primary); border: none; border-radius: var(--radius-sm); font-size: 13px; font-weight: 700; font-family: 'DM Sans', sans-serif; cursor: pointer; transition: background .18s; display: flex; align-items: center; justify-content: center; gap: 6px; }
        .btn-see-detail:hover { background: #ddddf8; }
        .btn-interested { flex: 0 0 auto; padding: 9px 14px; background: var(--success-light); color: var(--success); border: 1px solid rgba(14,163,112,.2); border-radius: var(--radius-sm); font-size: 12px; font-weight: 700; font-family: 'DM Sans', sans-serif; cursor: pointer; transition: background .18s; }
        .btn-interested:hover { background: #d1fae5; }

        /* EMPTY STATE */
        .empty-state { text-align: center; padding: 70px 20px; background: var(--white); border: 1.5px dashed var(--border); border-radius: var(--radius); }
        .empty-icon { font-size: 52px; margin-bottom: 16px; }
        .empty-state h3 { font-family: 'Fraunces', serif; font-size: 20px; font-weight: 800; margin-bottom: 8px; }
        .empty-state p { font-size: 14px; color: var(--text-sub); }

        /* DETAIL MODAL */
        .detail-modal { position: fixed; inset: 0; background: rgba(15,14,26,.6); z-index: 300; display: none; align-items: center; justify-content: center; padding: 20px; }
        .detail-modal.open { display: flex; }
        .detail-box { background: var(--white); border-radius: var(--radius); width: 700px; max-width: 100%; max-height: 90vh; overflow-y: auto; box-shadow: 0 24px 80px rgba(15,14,26,.22); animation: popIn .22s ease; display: flex; flex-direction: column; }
        @keyframes popIn { from { opacity: 0; transform: scale(.94); } to { opacity: 1; transform: scale(1); } }
        .detail-header { padding: 24px 28px 0; display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; }
        .detail-title { font-family: 'Fraunces', serif; font-size: 22px; font-weight: 800; line-height: 1.2; }
        .detail-close { background: var(--bg); border: 1.5px solid var(--border); border-radius: 50%; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 14px; color: var(--text-sub); flex-shrink: 0; transition: background .18s; }
        .detail-close:hover { background: var(--danger-light); color: var(--danger); }
        .detail-body { padding: 18px 28px 24px; flex: 1; display: flex; flex-direction: column; gap: 16px; }
        .detail-section-label { font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .07em; color: var(--text-dim); margin-bottom: 4px; }
        .detail-text { font-size: 14px; color: var(--text-sub); line-height: 1.7; }
        .detail-meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .detail-meta-card { background: var(--bg); border-radius: var(--radius-sm); padding: 14px 16px; border: 1px solid var(--border); }
        .detail-meta-val { font-family: 'Fraunces', serif; font-size: 18px; font-weight: 800; color: var(--text-main); margin-bottom: 3px; }
        .detail-meta-label { font-size: 11px; color: var(--text-dim); font-weight: 600; text-transform: uppercase; letter-spacing: .05em; }
        .detail-footer { padding: 16px 28px 22px; border-top: 1px solid var(--border); display: flex; gap: 10px; }
        .btn-interested-big { flex: 1; padding: 12px; background: var(--primary); color: #fff; border: none; border-radius: var(--radius-sm); font-size: 14px; font-weight: 700; font-family: 'DM Sans', sans-serif; cursor: pointer; box-shadow: 0 3px 10px var(--primary-glow); transition: background .2s; }
        .btn-interested-big:hover { background: var(--primary-hover); }
        .btn-close-detail { padding: 12px 20px; background: var(--bg); color: var(--text-sub); border: 1.5px solid var(--border); border-radius: var(--radius-sm); font-size: 13px; font-weight: 600; cursor: pointer; font-family: 'DM Sans', sans-serif; }

        @media (max-width: 700px) { nav { padding: 0 16px; } .hero { padding: 40px 20px; } .hero h1 { font-size: 28px; } .page-wrapper { padding: 24px 14px 60px; } .filter-bar { flex-direction: column; align-items: stretch; } .hero-stats { gap: 20px; } }
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
        <li><a href="#">Offers</a></li>
        <li><a href="#" class="active">Campaigns</a></li>
        <li><a href="#">Events</a></li>
        <li><a href="#">Forum</a></li>
    </ul>
    <div class="nav-right">
        <span class="nav-badge">Creator</span>
        <div class="nav-avatar">C</div>
    </div>
</nav>

<!-- HERO -->
<div class="hero">
    <div class="hero-content">
        <div class="hero-tag">⚡ Active Campaigns</div>
        <h1>Discover Brand Campaigns</h1>
        <p>Browse active campaigns from top brands and apply to collaborate on exciting projects.</p>
        <div class="hero-stats">
            <div>
                <div class="hero-stat-val"><?= $totalCampagnes ?></div>
                <div class="hero-stat-label">Total Campaigns</div>
            </div>
            <div>
                <div class="hero-stat-val"><?= $nbActives ?></div>
                <div class="hero-stat-label">Currently Active</div>
            </div>
            <div>
                <div class="hero-stat-val"><?= number_format($budgetTotal / 1000, 0) ?>K€</div>
                <div class="hero-stat-label">Total Budget</div>
            </div>
        </div>
    </div>
</div>

<div class="page-wrapper">

    <!-- STATUS CHIPS -->
    <div class="status-chips">
        <div class="s-chip active" data-val="" onclick="filterChip('', this)"><span class="dot"></span> All</div>
        <div class="s-chip" data-val="active" onclick="filterChip('active', this)"><span class="dot" style="background:#0ea370"></span> Active</div>
        <div class="s-chip" data-val="brouillon" onclick="filterChip('brouillon', this)"><span class="dot" style="background:#f59e0b"></span> Draft</div>
        <div class="s-chip" data-val="terminee" onclick="filterChip('terminee', this)"><span class="dot" style="background:#3b82f6"></span> Ended</div>
    </div>

    <!-- FILTER BAR -->
    <div class="filter-bar">
        <div class="search-wrap">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
            <input type="text" class="search-input" id="searchInput" placeholder="Search campaigns, objectives, brands…">
        </div>
        <select class="filter-select" id="sortSelect" onchange="sortCampagnes()">
            <option value="">Sort by…</option>
            <option value="titre">Name A→Z</option>
            <option value="budget_desc">Budget ↓</option>
            <option value="budget_asc">Budget ↑</option>
            <option value="date">Start date</option>
        </select>
        <button class="pf-reset" onclick="resetFilters()">✕ Reset</button>
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
        <?php foreach ($campagnes as $c): ?>
        <?php $accentClass = 'accent-' . $c['statut']; ?>
        <div class="camp-card"
             data-titre="<?= strtolower(htmlspecialchars($c['titreCampagne'])) ?>"
             data-statut="<?= htmlspecialchars($c['statut']) ?>"
             data-budget="<?= (float)$c['budget'] ?>"
             data-date="<?= $c['dateDebut'] ?? '' ?>"
             data-brand="<?= strtolower(htmlspecialchars($c['nomMarque'] ?? '')) ?>"
             data-obj="<?= strtolower(htmlspecialchars($c['objectif'] ?? '')) ?>"
             onclick="openDetail(<?= $c['idCampagne'] ?>)">
            <div class="camp-card-accent <?= $accentClass ?>"></div>
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
                    <div class="camp-meta-row">📅 <span><?= $c['dateDebut'] ?? '—' ?></span> → 🏁 <span><?= $c['dateFin'] ?? '—' ?></span></div>
                    <?php if (!empty($c['nomMarque'])): ?>
                    <div class="camp-meta-row">🏢 <span><?= htmlspecialchars($c['nomMarque']) ?></span></div>
                    <?php endif; ?>
                </div>
                <div class="camp-budget"><?= number_format((float)$c['budget'], 2, ',', ' ') ?> €</div>
            </div>
            <div class="camp-card-footer" onclick="event.stopPropagation()">
                <button class="btn-see-detail" onclick="openDetail(<?= $c['idCampagne'] ?>)">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    View Details
                </button>
                <?php if ($c['statut'] === 'active'): ?>
                <button class="btn-interested">🙋 Apply</button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <div id="noResults" style="display:none;text-align:center;padding:48px;color:var(--text-dim);font-size:14px">
        No campaigns match your search.
    </div>
    <?php endif; ?>

</div><!-- /page-wrapper -->

<!-- DETAIL MODAL -->
<div class="detail-modal" id="detailModal" onclick="closeDetailOutside(event)">
    <div class="detail-box">
        <div class="detail-header">
            <div>
                <div id="detailBadge" class="camp-badge" style="margin-bottom:8px;font-size:12px"></div>
                <div class="detail-title" id="detailTitle"></div>
                <?php if ($campagneDetail && !empty($campagneDetail['nomMarque'])): ?>
                <div style="margin-top:6px">
                    <span class="camp-brand">🏢 <?= htmlspecialchars($campagneDetail['nomMarque']) ?></span>
                </div>
                <?php endif; ?>
            </div>
            <button class="detail-close" onclick="closeDetail()">✕</button>
        </div>
        <div class="detail-body">
            <div>
                <div class="detail-section-label">Description</div>
                <div class="detail-text" id="detailDesc"></div>
            </div>
            <div id="detailObjWrap">
                <div class="detail-section-label">Objective</div>
                <div class="camp-obj" id="detailObj"></div>
            </div>
            <div class="detail-meta-grid">
                <div class="detail-meta-card">
                    <div class="detail-meta-val" id="detailBudget"></div>
                    <div class="detail-meta-label">Campaign Budget</div>
                </div>
                <div class="detail-meta-card">
                    <div class="detail-meta-val" id="detailDates" style="font-size:14px"></div>
                    <div class="detail-meta-label">Campaign Period</div>
                </div>
                <div class="detail-meta-card">
                    <div class="detail-meta-val" id="detailBrand"></div>
                    <div class="detail-meta-label">Brand</div>
                </div>
                <div class="detail-meta-card">
                    <div class="detail-meta-val" id="detailStatut"></div>
                    <div class="detail-meta-label">Status</div>
                </div>
            </div>
        </div>
        <div class="detail-footer">
            <button class="btn-interested-big" id="detailApplyBtn">🙋 Apply to this campaign</button>
            <button class="btn-close-detail" onclick="closeDetail()">Close</button>
        </div>
    </div>
</div>

<script>
// ── CAMPAIGN DATA MAP ──────────────────────────────────────────────────────────
const campagnesMap = {};
<?php foreach ($campagnes as $c): ?>
campagnesMap[<?= $c['idCampagne'] ?>] = {
    id:      <?= $c['idCampagne'] ?>,
    titre:   <?= json_encode($c['titreCampagne']) ?>,
    desc:    <?= json_encode($c['description'] ?? '') ?>,
    obj:     <?= json_encode($c['objectif'] ?? '') ?>,
    budget:  <?= (float)$c['budget'] ?>,
    debut:   <?= json_encode($c['dateDebut'] ?? '') ?>,
    fin:     <?= json_encode($c['dateFin'] ?? '') ?>,
    statut:  <?= json_encode($c['statut']) ?>,
    marque:  <?= json_encode($c['nomMarque'] ?? '') ?>,
};
<?php endforeach; ?>

const statutLabels = {
    active:    '✅ Active',
    brouillon: '📝 Draft',
    terminee:  '🏁 Ended',
    annulee:   '❌ Cancelled',
};
const statutColors = {
    active: '#0ea370', brouillon: '#f59e0b', terminee: '#3b82f6', annulee: '#f43f5e'
};
const statutBgs = {
    active: '#edfaf5', brouillon: '#fffbeb', terminee: '#eff6ff', annulee: '#fff1f3'
};

// ── CHIP FILTER ────────────────────────────────────────────────────────────────
let activeChip = '';
function filterChip(val, btn) {
    activeChip = val;
    document.querySelectorAll('.s-chip').forEach(c => c.classList.remove('active'));
    btn.classList.add('active');
    filterCampagnes();
}

// ── SEARCH + FILTER ────────────────────────────────────────────────────────────
document.getElementById('searchInput').addEventListener('input', filterCampagnes);

function filterCampagnes() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    const cards = document.querySelectorAll('#campGrid .camp-card');
    let visible = 0;
    cards.forEach(card => {
        const matchQ = !q || (card.dataset.titre||'').includes(q) || (card.dataset.brand||'').includes(q) || (card.dataset.obj||'').includes(q);
        const matchS = !activeChip || card.dataset.statut === activeChip;
        const show = matchQ && matchS;
        card.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    const countEl = document.getElementById('visibleCount');
    if (countEl) countEl.textContent = visible;
    const noRes = document.getElementById('noResults');
    if (noRes) noRes.style.display = visible === 0 ? '' : 'none';
}

function resetFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('sortSelect').value = '';
    activeChip = '';
    document.querySelectorAll('.s-chip').forEach((c,i) => c.classList.toggle('active', i === 0));
    filterCampagnes();
}

function sortCampagnes() {
    const mode = document.getElementById('sortSelect').value;
    const grid = document.getElementById('campGrid');
    if (!grid || !mode) return;
    const cards = Array.from(grid.querySelectorAll('.camp-card'));
    cards.sort((a, b) => {
        if (mode === 'titre')       return (a.dataset.titre||'').localeCompare(b.dataset.titre||'');
        if (mode === 'budget_asc')  return parseFloat(a.dataset.budget) - parseFloat(b.dataset.budget);
        if (mode === 'budget_desc') return parseFloat(b.dataset.budget) - parseFloat(a.dataset.budget);
        if (mode === 'date')        return (a.dataset.date||'').localeCompare(b.dataset.date||'');
        return 0;
    });
    cards.forEach(c => grid.appendChild(c));
}

// ── DETAIL MODAL ───────────────────────────────────────────────────────────────
function openDetail(id) {
    const c = campagnesMap[id];
    if (!c) return;

    const badge = document.getElementById('detailBadge');
    badge.textContent = statutLabels[c.statut] || c.statut;
    badge.style.background = statutBgs[c.statut] || '#f0f0f0';
    badge.style.color       = statutColors[c.statut] || '#555';

    document.getElementById('detailTitle').textContent  = c.titre;
    document.getElementById('detailDesc').textContent   = c.desc || 'No description available.';
    document.getElementById('detailBudget').textContent = new Intl.NumberFormat('fr-FR').format(c.budget) + ' €';
    document.getElementById('detailDates').innerHTML    = (c.debut || '—') + ' → ' + (c.fin || '—');
    document.getElementById('detailBrand').textContent  = c.marque || '—';
    document.getElementById('detailStatut').textContent = statutLabels[c.statut] || c.statut;

    const objWrap = document.getElementById('detailObjWrap');
    const objEl   = document.getElementById('detailObj');
    if (c.obj) { objEl.textContent = c.obj; objWrap.style.display = ''; }
    else { objWrap.style.display = 'none'; }

    const applyBtn = document.getElementById('detailApplyBtn');
    applyBtn.style.display = c.statut === 'active' ? '' : 'none';

    document.getElementById('detailModal').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeDetail() {
    document.getElementById('detailModal').classList.remove('open');
    document.body.style.overflow = '';
}
function closeDetailOutside(e) {
    if (e.target === document.getElementById('detailModal')) closeDetail();
}

<?php if ($campagneDetail): ?>
document.addEventListener('DOMContentLoaded', () => openDetail(<?= $campagneDetail['idCampagne'] ?>));
<?php endif; ?>

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDetail(); });
</script>
</body>
</html>