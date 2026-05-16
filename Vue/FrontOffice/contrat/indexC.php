<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$frontActive = 'campaigns';

// ── TEMPORAIRE : à supprimer après intégration de l'authentification ──
$_SESSION['id']   = 4;  // Sophie Martin — créateur
$_SESSION['role'] = 'createur';
// ─────────────────────────────────────────────────────────────────────

require_once __DIR__ . '/../../../Controleur/contratC.php';

$controller = new ContratC();
$action     = $_GET['action'] ?? 'index';
$idCreateur = 4;

if ($action === 'signer') {
    $id = (int)($_GET['id'] ?? 0);
    $controller->updateStatut($id, 'signe');
    header('Location: indexC.php?action=index');
    exit;
}

$contrats = $controller->getByCreateur($idCreateur);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/../layout/front-theme-bootstrap.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Contrats — Cre8Connect</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../layout/front-header.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../layout/front-header.css')); ?>">
    <style>
        :root {
            --bg:             #f7f8fc;
            --bg-white:       #ffffff;
            --bg-soft:        #eef0f8;
            --border:         #e2e5ef;
            --border-dark:    #cdd1e2;
            --accent:         #7c3aed;
            --accent-soft:    rgba(124,58,237,.08);
            --accent-hover:   #9154f5;
            --success:        #059669;
            --success-soft:   rgba(5,150,105,.1);
            --warning:        #d97706;
            --warning-soft:   rgba(217,119,6,.1);
            --danger:         #dc2626;
            --danger-soft:    rgba(220,38,38,.1);
            --neutral:        #6b7280;
            --neutral-soft:   rgba(107,114,128,.12);
            --text-primary:   #111827;
            --text-secondary: #374151;
            --text-muted:     #9ca3af;
            --shadow-sm:      0 1px 3px rgba(0,0,0,.05);
            --shadow-md:      0 4px 16px rgba(0,0,0,.07);
            --radius:         14px;
            --radius-lg:      20px;
        }

        /* ===== ADDED FEATURE: LIGHT / DARK MODE CSS ===== */
        body.dark-mode,
        html[data-theme="dark"] body {
            --bg:             #0f172a;
            --bg-white:       #1e293b;
            --bg-soft:        #334155;
            --border:         #334155;
            --border-dark:    #475569;
            --text-primary:   #f8fafc;
            --text-secondary: #cbd5e1;
            --text-muted:     #94a3b8;
            --shadow-sm:      0 1px 3px rgba(0,0,0,.3);
            --shadow-md:      0 4px 16px rgba(0,0,0,.4);
        }

        * { margin:0; padding:0; box-sizing:border-box; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg);
            color: var(--text-primary);
            min-height: 100vh;
            transition: background 0.3s ease, color 0.3s ease;
        }
        .nav-logo { font-size:1.25rem; font-weight:800; color:var(--accent); }
        .nav-logo em { color:var(--text-primary); font-style:normal; }
        .nav-links { display:flex; gap:4px; }
        .nav-link {
            padding:8px 16px; border-radius:8px;
            color:var(--text-secondary); text-decoration:none;
            font-size:.875rem; font-weight:600; transition:all .15s;
        }
        .nav-link:hover, .nav-link.active {
            background:var(--accent-soft); color:var(--accent);
        }

        /* ===== ADDED FEATURE: NAV CONTROLS (THEME & LANG) ===== */
        .nav-controls { display: flex; align-items: center; gap: 12px; }
        .control-btn {
            background: var(--bg-soft); border: 1px solid var(--border);
            padding: 6px 10px; border-radius: 8px; cursor: pointer;
            color: var(--text-primary); font-size: 0.85rem; font-weight: 700;
        }

        .avatar-pill {
            display:flex; align-items:center; gap:8px;
            background:var(--bg-soft); border:1px solid var(--border);
            border-radius:30px; padding:6px 14px 6px 6px;
            font-size:.825rem; font-weight:600; color:var(--text-secondary);
        }
        .avatar-circle {
            width:28px; height:28px; border-radius:50%;
            background:linear-gradient(135deg,var(--accent),#a78bfa);
            color:#fff; display:flex; align-items:center; justify-content:center;
            font-size:.75rem; font-weight:800;
        }

        /* ── HERO ── */
        .hero {
            background: linear-gradient(135deg, #7c3aed 0%, #a78bfa 50%, #c4b5fd 100%);
            padding: 48px 40px;
            color: white;
            position: relative;
            overflow: hidden;
        }
        .hero::before {
            content:'';
            position:absolute; top:-60px; right:-60px;
            width:280px; height:280px; border-radius:50%;
            background:rgba(255,255,255,.07);
        }
        .hero::after {
            content:'';
            position:absolute; bottom:-80px; left:20%;
            width:200px; height:200px; border-radius:50%;
            background:rgba(255,255,255,.05);
        }
        .hero-inner {
            max-width:1100px; margin:0 auto;
            position:relative; z-index:1;
        }
        .hero h1 {
            font-family:'Instrument Serif', serif;
            font-size:2.1rem; font-weight:400;
            line-height:1.2; margin-bottom:8px;
        }
        .hero p { font-size:.9rem; opacity:.85; }

        /* ── CONTENT ── */
        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 40px;
        }

        /* ===== ADDED FEATURE: ADVANCED FILTERING & SORTING UI ===== */
        .advanced-filters {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 20px;
            margin-bottom: 24px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            box-shadow: var(--shadow-sm);
        }
        .filter-group { display: flex; flex-direction: column; gap: 6px; }
        .filter-group label { font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; }
        .filter-input {
            padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border-dark);
            background: var(--bg); color: var(--text-primary); font-family: inherit; font-size: 0.85rem;
        }

        /* Tabs de filtre */
        .filter-tabs {
            display:flex; gap:8px;
            margin-bottom:20px;
            flex-wrap:wrap;
        }
        .tab {
            padding:8px 18px; border-radius:30px;
            font-size:.825rem; font-weight:700;
            cursor:pointer; border:none;
            font-family:inherit; transition:all .15s;
            background:var(--bg-white);
            border:1px solid var(--border-dark);
            color:var(--text-secondary);
        }
        .tab.active, .tab:hover { background:var(--accent); color:#fff; border-color:var(--accent); }

        /* ── CONTRAT CARD (vue créateur) ── */
        .contract-item {
            background:var(--bg-white);
            border:1px solid var(--border);
            border-radius:var(--radius-lg);
            padding:0;
            box-shadow:var(--shadow-sm);
            margin-bottom:16px;
            overflow:hidden;
            transition:box-shadow .2s;
        }
        .contract-item:hover { box-shadow:var(--shadow-md); }

        .item-header {
            display:flex; align-items:center; justify-content:space-between;
            padding:20px 24px;
            border-bottom:1px solid var(--border);
            gap:16px; flex-wrap:wrap;
        }
        .item-title { font-size:1rem; font-weight:700; margin-bottom:4px; }
        .item-campaign {
            font-size:.8rem; color:var(--text-muted);
            display:flex; align-items:center; gap:4px;
        }
        .item-campaign i { color:var(--accent); font-size:.75rem; }

        .badge {
            display:inline-flex; align-items:center; gap:4px;
            padding:5px 12px; border-radius:20px;
            font-size:.75rem; font-weight:700;
            text-transform:uppercase; letter-spacing:.5px;
        }
        .badge-pending  { background:var(--warning-soft); color:var(--warning); }
        .badge-signed   { background:var(--success-soft); color:var(--success); }
        .badge-resilie  { background:var(--danger-soft);  color:var(--danger);  }
        .badge-expire   { background:var(--neutral-soft); color:var(--neutral); }

        .item-body {
            padding: 20px 24px;
            display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr));
            gap:20px;
        }
        .detail-label {
            font-size:.7rem; font-weight:700; color:var(--text-muted);
            text-transform:uppercase; letter-spacing:1px; margin-bottom:4px;
        }
        .detail-value { font-size:.9rem; font-weight:600; }
        .detail-value.big { font-size:1.15rem; color:var(--accent); }

        .item-footer {
            padding:14px 24px;
            background:var(--bg-soft);
            border-top:1px solid var(--border);
            display:flex; align-items:center; justify-content:space-between;
            gap:12px; flex-wrap:wrap;
        }
        .item-footer-meta { font-size:.78rem; color:var(--text-muted); }

        /* ===== ADDED FEATURE: PAGINATION CSS ===== */
        .pagination-bar {
            display: flex; justify-content: center; align-items: center;
            gap: 10px; margin-top: 30px;
        }
        .page-btn {
            background: var(--bg-white); border: 1px solid var(--border-dark);
            padding: 8px 15px; border-radius: 8px; cursor: pointer;
            color: var(--text-primary); font-weight: 700; transition: 0.2s;
        }
        .page-btn.active { background: var(--accent); color: #fff; border-color: var(--accent); }
        .page-btn:disabled { opacity: 0.4; cursor: not-allowed; }

        /* ── BTN ── */
        .btn {
            display:inline-flex; align-items:center; gap:6px;
            padding:9px 18px; border-radius:10px;
            font-family:inherit; font-size:.85rem; font-weight:700;
            cursor:pointer; text-decoration:none;
            border:none; transition:all .15s;
        }
        .btn-sign {
            background:var(--success);
            color:#fff;
            box-shadow:0 3px 10px rgba(5,150,105,.25);
        }
        .btn-sign:hover { background:#047857; transform:translateY(-1px); }
        .btn-disabled {
            background:var(--neutral-soft); color:var(--neutral);
            cursor:not-allowed;
        }
        .btn-sm { padding:7px 14px; font-size:.78rem; }

        .empty-state { text-align:center; padding:80px 20px; }
        .empty-icon {
            width:80px; height:80px; border-radius:50%;
            background:var(--accent-soft);
            display:flex; align-items:center; justify-content:center;
            margin:0 auto 20px;
            font-size:2rem; color:var(--accent);
        }

        .toast {
            position:fixed; bottom:24px; right:24px;
            background:var(--success); color:#fff;
            padding:12px 20px; border-radius:10px;
            font-size:.85rem; font-weight:700;
            box-shadow:0 4px 16px rgba(0,0,0,.15);
            display:none; align-items:center; gap:8px;
            z-index:999;
        }
        .toast.show { display:flex; }
    </style>
<link rel="icon" type="image/png" sizes="16x16" href="../../public/images/favicon-16.png">
<link rel="icon" type="image/png" sizes="32x32" href="../../public/images/favicon-32.png">
<link rel="shortcut icon" type="image/png" href="../../public/images/favicon-32.png">
<link rel="apple-touch-icon" sizes="180x180" href="../../public/images/apple-touch-icon.png">
</head>
<body>

<?php require_once __DIR__ . '/../layout/header.php'; ?>

<div class="contract-front">
<div class="hero">
    <div class="hero-inner">
        <h1 data-i18n="hero_title">Mes Contrats</h1>
        <p data-i18n="hero_desc">Consultez et signez vos accords de collaboration avec les marques</p>
    </div>
</div>

<div class="container">

    <?php if (empty($contrats)): ?>
        <div class="empty-state">
            <div class="empty-icon"><i class="fas fa-file-contract"></i></div>
            <h3 data-i18n="empty_title">Aucun contrat pour l'instant</h3>
            <p data-i18n="empty_desc">Les marques avec lesquelles vous collaborez vous enverront des contrats ici.</p>
        </div>
    <?php else: ?>

        <div class="advanced-filters">
            <div class="filter-group">
                <label data-i18n="filter_search">Recherche</label>
                <input type="text" id="searchFilter" class="filter-input" data-i18n-placeholder="ph_search" placeholder="..." onkeyup="applyAllFilters()">
            </div>
            <div class="filter-group">
                <label data-i18n="filter_brand">Marque</label>
                <input type="text" id="brandFilter" class="filter-input" data-i18n-placeholder="ph_brand" placeholder="..." onkeyup="applyAllFilters()">
            </div>
            <div class="filter-group">
                <label data-i18n="filter_date">Date min</label>
                <input type="date" id="dateFilter" class="filter-input" onchange="applyAllFilters()">
            </div>
            <div class="filter-group">
                <label data-i18n="filter_sort">Trier par</label>
                <select id="sortSelect" class="filter-input" onchange="applyAllFilters()">
                    <option value="date_desc" data-i18n="sort_date_desc">Plus récent</option>
                    <option value="date_asc" data-i18n="sort_date_asc">Plus ancien</option>
                    <option value="amount_desc" data-i18n="sort_amount_desc">Montant (Max)</option>
                    <option value="amount_asc" data-i18n="sort_amount_asc">Montant (Min)</option>
                </select>
            </div>
        </div>

        <div class="filter-tabs" id="statusTabs">
            <button class="tab active" data-status="all" onclick="setStatutFilter('all', this)">
                <span data-i18n="status_all">Tous</span> (<?= count($contrats) ?>)
            </button>
            <?php
                $en_attente = array_filter($contrats, fn($c) => $c['statut'] === 'en_attente');
                $signes     = array_filter($contrats, fn($c) => $c['statut'] === 'signe');
            ?>
            <button class="tab" data-status="en_attente" onclick="setStatutFilter('en_attente', this)">
                <span data-i18n="status_pending">⏳ En attente</span> (<?= count($en_attente) ?>)
            </button>
            <button class="tab" data-status="signe" onclick="setStatutFilter('signe', this)">
                <span data-i18n="status_signed">✅ Signés</span> (<?= count($signes) ?>)
            </button>
        </div>

        <div id="contractsList">
        <?php foreach ($contrats as $c):
            $statut = $c['statut'];
            $badgeClass = match($statut) {
                'signe'   => 'badge-signed',
                'resilie' => 'badge-resilie',
                'expire'  => 'badge-expire',
                default   => 'badge-pending',
            };
            $badgeLabel = match($statut) {
                'signe'     => '✅ Signé',
                'resilie'   => '❌ Résilié',
                'expire'    => '🕐 Expiré',
                default     => '⏳ En attente de signature',
            };
            $canSign = ($statut === 'en_attente');
        ?>
        <div class="contract-item" 
             data-statut="<?= $statut ?>" 
             data-title="<?= strtolower(htmlspecialchars($c['titre'])) ?>"
             data-brand="<?= strtolower(htmlspecialchars($c['nomMarque'] ?? '')) ?>"
             data-date="<?= $c['date_creation'] ?>"
             data-amount="<?= $c['montant'] ?>">
            
            <div class="item-header">
                <div class="item-title-block">
                    <div class="item-title"><?= htmlspecialchars($c['titre']) ?></div>
                    <div class="item-campaign">
                        <i class="fas fa-rocket"></i>
                        <?= htmlspecialchars($c['titreCampagne'] ?? 'Campagne non renseignée') ?>
                        · <strong class="brand-name-val"><?= htmlspecialchars($c['nomMarque'] ?? 'Marque') ?></strong>
                    </div>
                </div>
                <span class="badge <?= $badgeClass ?>"><?= $badgeLabel ?></span>
            </div>

            <div class="item-body">
                <div class="detail-block">
                    <div class="detail-label" data-i18n="label_remuneration">Rémunération</div>
                    <div class="detail-value big"><?= number_format($c['montant'],2,',',' ') ?> €</div>
                </div>
                <div class="detail-block">
                    <div class="detail-label" data-i18n="label_start">Début</div>
                    <div class="detail-value"><?= date('d/m/Y', strtotime($c['date_debut'])) ?></div>
                </div>
                <div class="detail-block">
                    <div class="detail-label" data-i18n="label_end">Fin</div>
                    <div class="detail-value"><?= date('d/m/Y', strtotime($c['date_fin'])) ?></div>
                </div>
                <div class="detail-block">
                    <div class="detail-label" data-i18n="label_description">Description</div>
                    <div class="detail-value" style="font-size:.82rem;color:var(--text-secondary);font-weight:500">
                        <?= nl2br(htmlspecialchars(substr($c['description'], 0, 120))) ?>
                        <?= strlen($c['description']) > 120 ? '...' : '' ?>
                    </div>
                </div>
            </div>

            <div class="item-footer">
                <div class="item-footer-meta">
                    <i class="fas fa-calendar-alt" style="margin-right:4px"></i>
                    <span data-i18n="received_on">Reçu le</span> <?= date('d/m/Y', strtotime($c['date_creation'])) ?>
                </div>
                <?php if ($canSign): ?>
                    <a href="?module=contrat&action=signer&id=<?= $c['id'] ?>" class="btn btn-sign btn-sm"
                       onclick="return confirm('Confirmez-vous la signature de ce contrat ?')">
                        <i class="fas fa-signature"></i> <span data-i18n="btn_sign">Signer le contrat</span>
                    </a>
                <?php else: ?>
                    <button class="btn btn-disabled btn-sm" disabled>
                        <i class="fas fa-lock"></i> 
                        <span data-status-locked="<?= $statut ?>">
                            <?= match($statut) {
                                'signe'   => 'Déjà signé',
                                'resilie' => 'Contrat résilié',
                                'expire'  => 'Contrat expiré',
                                default   => 'Non disponible'
                            } ?>
                        </span>
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        </div>

        <div class="pagination-bar" id="paginationControls"></div>

    <?php endif; ?>
</div>
</div>

<div class="toast" id="toast"><i class="fas fa-check-circle"></i> <span data-i18n="toast_success">Action effectuée avec succès</span></div>

<script>
// ===== ADDED FEATURE: TRANSLATION SYSTEM =====

const translations = {
    fr: {
        nav_dashboard: "Dashboard", nav_offers: "Offres", nav_campaigns: "Campagnes", nav_contracts: "Contrats", nav_posts: "Posts", nav_events: "Événements",
        my_profile: "Mon Profil", hero_title: "Mes Contrats", hero_desc: "Consultez et signez vos accords de collaboration avec les marques",
        empty_title: "Aucun contrat pour l'instant", empty_desc: "Les marques avec lesquelles vous collaborez vous enverront des contrats ici.",
        filter_search: "Recherche", filter_brand: "Marque", filter_date: "Date min", filter_sort: "Trier par",
        ph_search: "Rechercher par titre…",
        ph_brand: "Filtrer par marque…",
        sort_date_desc: "Plus récent", sort_date_asc: "Plus ancien", sort_amount_desc: "Montant (Max)", sort_amount_asc: "Montant (Min)",
        status_all: "Tous", status_pending: "⏳ En attente", status_signed: "✅ Signés",
        label_remuneration: "Rémunération", label_start: "Début", label_end: "Fin", label_description: "Description",
        received_on: "Reçu le", btn_sign: "Signer le contrat", toast_success: "Action effectuée avec succès"
    },
    en: {
        nav_dashboard: "Dashboard", nav_offers: "Offers", nav_campaigns: "Campaigns", nav_contracts: "Contracts", nav_posts: "Posts", nav_events: "Events",
        my_profile: "My Profile", hero_title: "My Contracts", hero_desc: "View and sign your collaboration agreements with brands",
        empty_title: "No contracts yet", empty_desc: "Brands you collaborate with will send contracts here.",
        filter_search: "Search", filter_brand: "Brand", filter_date: "Min Date", filter_sort: "Sort by",
        ph_search: "Search by title…",
        ph_brand: "Filter by brand…",
        sort_date_desc: "Most Recent", sort_date_asc: "Oldest", sort_amount_desc: "Amount (High)", sort_amount_asc: "Amount (Low)",
        status_all: "All", status_pending: "⏳ Pending", status_signed: "✅ Signed",
        label_remuneration: "Compensation", label_start: "Start", label_end: "End", label_description: "Description",
        received_on: "Received on", btn_sign: "Sign Contract", toast_success: "Action successful"
    }
};

let creatorContractLang = 'en';

function switchLang(lang) {
    const safe = lang === 'fr' ? 'fr' : 'en';
    creatorContractLang = safe;
    const dict = translations[safe] || translations.fr;
    document.querySelectorAll('[data-i18n]').forEach(el => {
        const key = el.getAttribute('data-i18n');
        if (key && dict[key] !== undefined) el.textContent = dict[key];
    });
    document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
        const key = el.getAttribute('data-i18n-placeholder');
        if (key && dict[key] !== undefined) el.setAttribute('placeholder', dict[key]);
    });
    document.querySelectorAll('[data-i18n-title]').forEach(el => {
        const key = el.getAttribute('data-i18n-title');
        if (key && dict[key] !== undefined) el.setAttribute('title', dict[key]);
    });
}

// ===== ADDED FEATURE: LIGHT / DARK MODE JS =====
function toggleTheme() {
    const isDark = document.body.classList.toggle('dark-mode');
    const legacyThemeToggle = document.getElementById('themeToggle');
    if (legacyThemeToggle) legacyThemeToggle.innerText = isDark ? '☀️' : '🌙';
    localStorage.setItem('cc_theme', isDark ? 'dark' : 'light');
}

// ===== ADDED FEATURE: ADVANCED FILTERING, SORTING & PAGINATION JS =====
let currentStatut = 'all';
const itemsPerPage = 5;
let currentPage = 1;

function setStatutFilter(statut, btn) {
    document.querySelectorAll('#statusTabs .tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    currentStatut = statut;
    currentPage = 1;
    applyAllFilters();
}

function applyAllFilters() {
    const list = document.getElementById('contractsList');
    if (!list) return;
    const cards = Array.from(list.getElementsByClassName('contract-item'));
    
    const searchText = document.getElementById('searchFilter').value.toLowerCase();
    const brandText = document.getElementById('brandFilter').value.toLowerCase();
    const dateMin = document.getElementById('dateFilter').value;
    const sortVal = document.getElementById('sortSelect').value;

    // 1. Filtering
    let filteredCards = cards.filter(card => {
        const matchesStatus = (currentStatut === 'all' || card.dataset.statut === currentStatut);
        const matchesSearch = card.dataset.title.includes(searchText);
        const matchesBrand = card.dataset.brand.includes(brandText);
        const matchesDate = !dateMin || new Date(card.dataset.date) >= new Date(dateMin);
        
        return matchesStatus && matchesSearch && matchesBrand && matchesDate;
    });

    // 2. Sorting
    filteredCards.sort((a, b) => {
        if (sortVal === 'amount_desc') return b.dataset.amount - a.dataset.amount;
        if (sortVal === 'amount_asc') return a.dataset.amount - b.dataset.amount;
        if (sortVal === 'date_asc') return new Date(a.dataset.date) - new Date(b.dataset.date);
        return new Date(b.dataset.date) - new Date(a.dataset.date);
    });

    // 3. Re-order DOM for sorting
    filteredCards.forEach(card => list.appendChild(card));

    // 4. Pagination
    const totalPages = Math.ceil(filteredCards.length / itemsPerPage);
    cards.forEach(c => c.style.display = 'none'); // Hide all
    
    const start = (currentPage - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    const cardsToShow = filteredCards.slice(start, end);
    cardsToShow.forEach(c => c.style.display = '');

    renderPagination(totalPages);
}

function renderPagination(totalPages) {
    const container = document.getElementById('paginationControls');
    if (!container) return;
    container.innerHTML = '';
    if (totalPages <= 1) return;

    for (let i = 1; i <= totalPages; i++) {
        const btn = document.createElement('button');
        btn.innerText = i;
        btn.className = `page-btn ${i === currentPage ? 'active' : ''}`;
        btn.onclick = () => { currentPage = i; applyAllFilters(); window.scrollTo(0, 0); };
        container.appendChild(btn);
    }
}

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    try {
        var cct = localStorage.getItem('cc_theme');
        if ((cct === 'dark' || cct === 'light') && !localStorage.getItem('cre8_theme')) {
            localStorage.setItem('cre8_theme', cct);
        }
    } catch (e) {}
    if (typeof window.cre8ApplyFrontTheme === 'function') {
        window.cre8ApplyFrontTheme(document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light', false);
    } else {
        var savedTheme = localStorage.getItem('cre8_theme') || localStorage.getItem('cc_theme');
        document.body.classList.toggle('dark-mode', savedTheme === 'dark');
    }
    creatorContractLang = typeof window.cre8RegisterTranslations === 'function'
        ? window.cre8RegisterTranslations(translations)
        : (typeof window.cre8FrontReadLang === 'function' ? window.cre8FrontReadLang() : 'en');
    switchLang(creatorContractLang);
    window.addEventListener('cre8:languagechange', function (event) {
        switchLang(event.detail && event.detail.lang ? event.detail.lang : creatorContractLang);
    });
    applyAllFilters();
});

// Toast original auto-show
const params = new URLSearchParams(window.location.search);
if (params.get('success') === '1') {
    const toast = document.getElementById('toast');
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3500);
}
</script>
<script src="../layout/front-header.js?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../layout/front-header.js')); ?>"></script>
</body>
</html>
