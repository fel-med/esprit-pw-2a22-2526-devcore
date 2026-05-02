<?php
// This file can work both directly AND through the controller

// If accessed directly (for development/testing), fetch events from database
if (!isset($evenements)) {
    // Include config and fetch events directly
    require_once __DIR__ . '/../../../config.php';
    require_once __DIR__ . '/../../../Modele/evenement.php';
    
    try {
        $pdo = config::getConnexion();
        $stmt = $pdo->prepare(
            "SELECT * FROM evenement
             WHERE statut = 'actif'
             ORDER BY DateFormation ASC"
        );
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Hydrate events
        $evenements = [];
        foreach ($rows as $row) {
            $evenements[] = new Evenement(
                (int)($row['idFormation'] ?? 0),
                $row['TitreFormation'] ?? '',
                $row['description'] ?? '',
                $row['type'] ?? '',
                $row['statut'] ?? '',
                $row['lieu'] ?? '',
                $row['DateFormation'] ?? '',
                (int)($row['capacite'] ?? 0),
                (int)($row['nb_inscrits'] ?? 0),
                (int)($row['Duree'] ?? 0),
                $row['created_at'] ?? '',
                $row['image'] ?? null
            );
        }
        
        // Récupération des forums actifs
        $forumsData = [];
        $stmtForum = $pdo->prepare("SELECT idFormation, idForum, est_actif FROM forum WHERE est_actif = 1");
        $stmtForum->execute();
        while ($row = $stmtForum->fetch(PDO::FETCH_ASSOC)) {
            $forumsData[$row['idFormation']] = $row;
        }
        
    } catch (Exception $e) {
        $evenements = [];
        $forumsData = [];
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Événements - Cre8Connect</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700;9..144,800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    
            :root {
    /* Mode clair (par défaut) */
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
    --success:        #0ea370;
    --success-light:  #edfaf5;
    --warning:        #f59e0b;
    --warning-light:  #fffbeb;
    --radius:         14px;
    --radius-sm:      8px;
    --nav-h:          66px;
    --card-shadow:    0 1px 3px rgba(15,14,26,0.06), 0 4px 16px rgba(91,79,255,0.06);
    --card-shadow-hover: 0 8px 32px rgba(91,79,255,0.14);
}

/* Mode sombre */
body.dark-mode {
    --primary:        #7c6eff;
    --primary-light:  #2a2648;
    --primary-hover:  #8f82ff;
    --primary-glow:   rgba(124,110,255,0.2);
    --primary-border: rgba(124,110,255,0.3);
    --text-main:      #e6edf3;
    --text-sub:       #8b949e;
    --text-dim:       #6e7681;
    --border:         #30363d;
    --bg:             #0d1117;
    --white:          #161b22;
    --danger-light:   #3b1a24;
    --success-light:  #1a3e2a;
    --warning-light:  #3b2a1a;
    --card-shadow:    0 1px 3px rgba(0,0,0,0.3), 0 4px 16px rgba(0,0,0,0.2);
    --card-shadow-hover: 0 8px 32px rgba(0,0,0,0.4);
}
        

        body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text-main); min-height: 100vh; }

        /* ── NAV ── */
        nav { background: var(--white); border-bottom: 1px solid var(--border); padding: 0 48px; height: var(--nav-h); display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 200; box-shadow: 0 1px 0 var(--border), 0 2px 12px rgba(15,14,26,0.04); }
        .nav-logo { display: flex; align-items: center; gap: 10px; text-decoration: none; }
        .nav-logo img { width: 36px; height: 36px; object-fit: contain; border-radius: 9px; }
        .nav-logo-text { font-family: 'Fraunces', serif; font-size: 19px; font-weight: 800; color: var(--primary); letter-spacing: -0.5px; }
        .nav-links { display: flex; gap: 6px; list-style: none; }
        .nav-links a { text-decoration: none; color: var(--text-sub); font-size: 13.5px; font-weight: 600; padding: 6px 14px; border-radius: 8px; transition: all 0.18s; }
        .nav-links a:hover { background: var(--bg); color: var(--text-main); }
        .nav-links a.active { background: var(--primary-light); color: var(--primary); }
        .nav-right { display: flex; align-items: center; gap: 12px; }
        .nav-avatar { width: 36px; height: 36px; border-radius: 50%; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 14px; cursor: pointer; border: 2px solid var(--primary-border); }

        /* ── HERO ── */
        .hero { background: linear-gradient(135deg, #5b4fff 0%, #8b7cff 100%); color: white; text-align: center; padding: 48px 24px; margin-bottom: 32px; }
        .hero h1 { font-family: 'Fraunces', serif; font-size: 2.2rem; font-weight: 800; margin-bottom: 12px; }
        .hero p { font-size: 1rem; opacity: 0.9; max-width: 500px; margin: 0 auto; }
        .stats-bar { display: flex; justify-content: center; gap: 48px; margin-top: 32px; flex-wrap: wrap; }
        .stat-item { text-align: center; }
        .stat-value { font-family: 'Fraunces', serif; font-size: 2rem; font-weight: 800; }
        .stat-label { font-size: 0.8rem; opacity: 0.8; }

        /* ── PAGE WRAPPER ── */
        .page-wrapper { max-width: 1400px; margin: 0 auto; padding: 0 24px 80px; display: flex; gap: 32px; }

        /* ── SIDEBAR FILTERS ── */
        .sidebar { width: 280px; flex-shrink: 0; }
        .filter-section { background: var(--white); border-radius: var(--radius); padding: 20px; margin-bottom: 24px; border: 1px solid var(--border); box-shadow: var(--card-shadow); }
        .filter-title { font-weight: 700; font-size: 0.85rem; margin-bottom: 16px; color: var(--text-main); }
        .filter-options { display: flex; flex-direction: column; gap: 12px; }
        .filter-option { display: flex; align-items: center; gap: 10px; font-size: 0.85rem; color: var(--text-sub); cursor: pointer; }
        .filter-option input { width: 16px; height: 16px; accent-color: var(--primary); }
        .search-box { display: flex; align-items: center; gap: 8px; padding: 10px 16px; border: 1px solid var(--border); border-radius: 40px; background: var(--white); margin-bottom: 16px; }
        .search-box input { border: none; outline: none; font-size: 0.85rem; width: 100%; background: transparent; }
        .chip-group { display: flex; flex-wrap: wrap; gap: 8px; }
        .chip { padding: 6px 14px; border-radius: 50px; font-size: 0.75rem; font-weight: 600; border: 1px solid var(--border); background: var(--white); color: var(--text-sub); cursor: pointer; transition: all 0.2s; }
        .chip:hover, .chip.active { background: var(--primary); border-color: var(--primary); color: white; }

        /* ── EVENTS SECTION ── */
        .events-section { flex: 1; }
        .events-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
        .events-count { font-size: 0.85rem; color: var(--text-sub); font-weight: 600; }
        .sort-controls { display: flex; gap: 12px; align-items: center; }
        .sort-controls select { padding: 8px 12px; border: 1px solid var(--border); border-radius: var(--radius-sm); font-size: 0.8rem; background: var(--white); cursor: pointer; font-family: 'DM Sans', sans-serif; }

        /* ── EVENTS GRID ── */
        .events-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 24px; }
        .event-card { background: var(--white); border-radius: var(--radius); overflow: hidden; border: 1px solid var(--border); transition: all 0.25s; box-shadow: var(--card-shadow); }
        .event-card:hover { transform: translateY(-4px); box-shadow: var(--card-shadow-hover); border-color: var(--primary-border); }
        .event-image { height: 200px; background-size: cover; background-position: center; background-color: var(--bg); display: flex; align-items: center; justify-content: center; font-size: 3rem; }
        .event-info { padding: 20px; }
        .event-type { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; margin-bottom: 12px; }
        .type-formation { background: var(--primary-light); color: var(--primary); }
        .type-webinaire { background: var(--warning-light); color: var(--warning); }
        .type-meetup { background: #fce7f3; color: #ec4899; }
        .type-atelier { background: var(--success-light); color: var(--success); }
        .event-title { font-family: 'Fraunces', serif; font-size: 1.1rem; font-weight: 800; margin-bottom: 10px; color: var(--text-main); }
        .event-description { font-size: 0.8rem; color: var(--text-sub); line-height: 1.5; margin-bottom: 12px; }
        .event-meta { display: flex; gap: 16px; font-size: 0.7rem; color: var(--text-sub); margin-bottom: 12px; }
        .event-meta span { display: flex; align-items: center; gap: 4px; }
        .event-spots { font-size: 0.7rem; font-weight: 700; margin-bottom: 16px; padding: 8px 12px; border-radius: var(--radius-sm); }
        .event-spots.available { background: var(--success-light); color: var(--success); }
        .event-spots.full { background: var(--danger-light); color: var(--danger); }
        .btn-event { width: 100%; padding: 12px; background: var(--primary); color: white; border: none; border-radius: var(--radius-sm); font-weight: 700; font-size: 0.8rem; cursor: pointer; transition: all 0.2s; margin-bottom: 10px; }
        .btn-event:hover { background: var(--primary-hover); transform: translateY(-1px); }
        .btn-event.outline { background: transparent; color: var(--primary); border: 1.5px solid var(--primary); }
        .btn-event.outline:hover { background: var(--primary); color: white; }
        .btn-detail { width: 100%; padding: 10px; background: transparent; color: var(--text-sub); border: 1px solid var(--border); border-radius: var(--radius-sm); font-weight: 600; font-size: 0.8rem; cursor: pointer; transition: all 0.2s; margin-bottom: 8px; }
        .btn-detail:hover { background: var(--bg); color: var(--text-main); border-color: var(--primary-border); }
        .btn-forum { width: 100%; padding: 10px; background: var(--success); color: white; border: none; border-radius: var(--radius-sm); font-weight: 700; font-size: 0.8rem; text-align: center; display: inline-block; text-decoration: none; cursor: pointer; transition: all 0.2s; }
        .btn-forum:hover { background: #0c8b5e; transform: translateY(-1px); }

        /* ── MODALS ── */
        .inscription-modal, .detail-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15,14,26,0.6); backdrop-filter: blur(4px); z-index: 1000; align-items: center; justify-content: center; }
        .inscription-modal.show, .detail-modal.show { display: flex; }
        .inscription-card, .detail-modal-content { background: var(--white); border-radius: var(--radius); width: 500px; max-width: 90%; max-height: 85vh; overflow-y: auto; animation: slideUp 0.2s ease; }
        .inscription-card { padding: 28px; }
        .detail-modal-content { width: 900px; display: flex; flex-direction: column; }
        .detail-modal-body { display: flex; gap: 0; }
        .detail-image { flex: 1; min-height: 300px; background: var(--bg); display: flex; align-items: center; justify-content: center; border-radius: var(--radius) 0 0 var(--radius); overflow: hidden; }
        .detail-image img { width: 100%; height: 100%; object-fit: cover; }
        .detail-info { flex: 1; padding: 28px; }
        .detail-info h2 { font-family: 'Fraunces', serif; font-size: 1.5rem; font-weight: 800; margin-bottom: 12px; }
        .detail-meta { margin: 20px 0; padding: 16px 0; border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); display: flex; flex-direction: column; gap: 10px; }
        .inscription-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .inscription-header h3 { font-family: 'Fraunces', serif; font-size: 1.3rem; font-weight: 800; }
        .inscription-close, .detail-modal-close { background: none; border: none; font-size: 24px; cursor: pointer; color: var(--text-sub); }
        .inscription-form-group { margin-bottom: 16px; }
        .inscription-label { display: block; font-size: 0.8rem; font-weight: 700; margin-bottom: 6px; }
        .inscription-input { width: 100%; padding: 12px; border: 1.5px solid var(--border); border-radius: var(--radius-sm); font-size: 0.9rem; font-family: inherit; }
        .inscription-input:focus { outline: none; border-color: var(--primary); }
        .inscription-buttons { display: flex; gap: 12px; margin-top: 20px; }
        .btn-inscrire-modal, .btn-annuler-modal { flex: 1; padding: 12px; border-radius: var(--radius-sm); font-weight: 700; cursor: pointer; }
        .btn-inscrire-modal { background: var(--primary); color: white; border: none; }
        .btn-inscrire-modal:hover { background: var(--primary-hover); }
        .btn-annuler-modal { background: var(--bg); color: var(--text-sub); border: 1px solid var(--border); }
        .toast { position: fixed; bottom: 20px; right: 20px; background: var(--success); color: white; padding: 12px 24px; border-radius: 50px; display: none; z-index: 1002; font-weight: 600; }
        .toast.error { background: var(--danger); }

        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        @media (max-width: 1024px) { .page-wrapper { flex-direction: column; } .sidebar { width: 100%; display: flex; gap: 20px; overflow-x: auto; } .filter-section { min-width: 250px; } }
        @media (max-width: 768px) { nav { padding: 0 20px; } .nav-links { display: none; } .hero h1 { font-size: 1.5rem; } .stats-bar { gap: 24px; } .events-grid { grid-template-columns: 1fr; } .detail-modal-body { flex-direction: column; } .detail-image { border-radius: var(--radius) var(--radius) 0 0; min-height: 200px; } }

        /* Bouton de changement de thème */
.theme-toggle-btn {
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: 50%;
    width: 36px;
    height: 36px;
    cursor: pointer;
    font-size: 1.2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
}

.theme-toggle-btn:hover {
    transform: scale(1.05);
    background: var(--primary-light);
}
    </style>
</head>
<body>

<!-- Navigation -->
<nav>
    <a href="#" class="nav-logo">
        <img src="/ProjetWeb/Esprit-PW-2A22-2526-Devcore/Vue/public/images/logo.png" alt="Cre8Connect">
        <span class="nav-logo-text">Cre8Connect</span>
    </a>
    <ul class="nav-links">
        <li><a href="#" class="active">Événements</a></li>
        <li><a href="/ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/forumC.php" class="nav-item">Forums</a></li>
    </ul>
    <div class="nav-right">
    <button id="themeToggle" class="theme-toggle-btn" title="Changer de thème">🌙</button>
    <div class="nav-avatar">👤</div>
</div>
</nav>

<!-- Hero -->
<section class="hero">
    <h1>Découvrez les événements de la communauté</h1>
    <p>Formations, webinaires, meetups et ateliers pour les créateurs et les marques.</p>
    <div class="stats-bar">
        <div class="stat-item">
            <div class="stat-value"><?= count($evenements) ?></div>
            <div class="stat-label">Événements disponibles</div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?= array_sum(array_map(function($e) { return $e->getNbInscrits(); }, $evenements)) ?></div>
            <div class="stat-label">Participants</div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?= count(array_filter($evenements, function($e) { return $e->getType() === 'formation'; })) ?></div>
            <div class="stat-label">Formations</div>
        </div>
    </div>
</section>

<div class="page-wrapper">
    <!-- Sidebar Filters -->
    <aside class="sidebar">
        <div class="filter-section">
            <div class="filter-title">🔍 RECHERCHE</div>
            <div class="search-box">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <input type="text" id="searchInput" placeholder="Rechercher un événement..." onkeyup="filterEvents()">
            </div>
        </div>

        <div class="filter-section">
            <div class="filter-title">📂 TYPE</div>
            <div class="filter-options">
                <label class="filter-option"><input type="checkbox" value="formation" class="type-filter"> Formation</label>
                <label class="filter-option"><input type="checkbox" value="webinaire" class="type-filter"> Webinaire</label>
                <label class="filter-option"><input type="checkbox" value="meetup" class="type-filter"> Meetup</label>
                <label class="filter-option"><input type="checkbox" value="atelier" class="type-filter"> Atelier</label>
            </div>
        </div>

        <div class="filter-section">
            <div class="filter-title">📍 LIEU</div>
            <div class="chip-group">
                <button class="chip active" onclick="filterChip(this, '')">Tous</button>
                <button class="chip" onclick="filterChip(this, 'en_ligne')">En ligne</button>
                <button class="chip" onclick="filterChip(this, 'presentiel')">Présentiel</button>
            </div>
        </div>
    </aside>

    <!-- Events Section -->
    <div class="events-section">
        <div class="events-header">
            <div class="events-count" id="eventsCount"><?= count($evenements) ?> événements</div>
            <div class="sort-controls">
                <span>TRIER PAR</span>
                <select id="sortSelect" onchange="sortEvents()">
                    <option value="date">Date (plus récent)</option>
                    <option value="date_asc">Date (plus ancien)</option>
                    <option value="places">Places disponibles</option>
                </select>
            </div>
        </div>

        <div class="events-grid" id="eventsGrid">
            <?php foreach ($evenements as $event): 
                $spotsLeft = $event->getCapacite() - $event->getNbInscrits();
                $isFull = ($spotsLeft <= 0);
                $isOnline = strpos(strtolower($event->getLieu()), 'en ligne') !== false || empty($event->getLieu());
                
                $forumLink = '';
                if (isset($forumsData[$event->getId()])) {
                    $forumLink = '<a href="/ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/forumC.php" class="nav-item">💬 Forums</a>';
                }
            ?>
            <div class="event-card" 
                 data-type="<?= $event->getType() ?>" 
                 data-lieu="<?= $isOnline ? 'en_ligne' : 'presentiel' ?>" 
                 data-title="<?= strtolower(htmlspecialchars($event->getTitre())) ?>"
                 data-date="<?= strtotime($event->getDateEvenement()) ?>"
                 data-places="<?= $spotsLeft ?>">
                <div class="event-image" style="<?= $event->getImage() ? 'background-image: url(/ProjetWeb/Esprit-PW-2A22-2526-Devcore/' . $event->getImage() . '); background-size: cover;' : '' ?>">
                    <?php if (!$event->getImage()): ?><span>🎯</span><?php endif; ?>
                </div>
                <div class="event-info">
                    <span class="event-type type-<?= $event->getType() ?>"><?= ucfirst($event->getType()) ?></span>
                    <div class="event-title"><?= htmlspecialchars($event->getTitre()) ?></div>
                    <div class="event-description"><?= htmlspecialchars(substr($event->getDescription(), 0, 100)) ?>...</div>
                    <div class="event-meta">
                        <span>📅 <?= date('d M Y', strtotime($event->getDateEvenement())) ?></span>
                        <span>📍 <?= htmlspecialchars($event->getLieu() ?: 'En ligne') ?></span>
                    </div>
                    <div class="event-spots <?= $isFull ? 'full' : 'available' ?>">
                        <?= $isFull ? '❌ Complet' : '✅ ' . $spotsLeft . ' places restantes' ?>
                    </div>
                    <button onclick="ouvrirModalInscription(<?= $event->getId() ?>)" class="btn-event <?= $isFull ? 'outline' : '' ?>">
                        <?= $isFull ? '📋 Liste d\'attente' : '✨ S\'inscrire' ?>
                    </button>
                    <button onclick="voirDetail(<?= $event->getId() ?>)" class="btn-detail">
                        👁 Voir les détails
                    </button>
                    <?= $forumLink ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Modal Inscription -->
<div id="inscriptionModal" class="inscription-modal">
    <div class="inscription-card">
        <div class="inscription-header">
            <h3>✨ Inscription à l'événement</h3>
            <button class="inscription-close" onclick="fermerModalInscription()">&times;</button>
        </div>
        <div class="inscription-form-group">
            <label class="inscription-label">Nom complet</label>
            <input type="text" id="inscrireNom" class="inscription-input" placeholder="Votre nom et prénom">
        </div>
        <div class="inscription-form-group">
            <label class="inscription-label">Email</label>
            <input type="email" id="inscrireEmail" class="inscription-input" placeholder="votre@email.com">
        </div>
        <div class="inscription-buttons">
            <button class="btn-annuler-modal" onclick="fermerModalInscription()">Annuler</button>
            <button class="btn-inscrire-modal" onclick="confirmerInscription()">S'inscrire</button>
        </div>
    </div>
</div>

<!-- Modal Détail -->
<div id="detailModal" class="detail-modal">
    <div class="detail-modal-content">
        <div style="display: flex; justify-content: flex-end; padding: 16px;">
            <button class="detail-modal-close" onclick="fermerDetailModal()">&times;</button>
        </div>
        <div class="detail-modal-body">
            <div class="detail-image" id="detailImage"></div>
            <div class="detail-info">
                <span class="event-type" id="detailType"></span>
                <h2 id="detailTitre"></h2>
                <p id="detailDescription"></p>
                <div class="detail-meta">
                    <span id="detailDate"></span>
                    <span id="detailLieu"></span>
                    <span id="detailPlaces"></span>
                </div>
                <button id="detailInscrireBtn" class="btn-event">✨ S'inscrire maintenant</button>
            </div>
        </div>
    </div>
</div>

<div id="toast" class="toast"></div>

<script>
    let currentEventId = null;

    function showToast(message, isError = false) {
        const toast = document.getElementById('toast');
        toast.textContent = message;
        toast.className = 'toast' + (isError ? ' error' : '');
        toast.style.display = 'block';
        setTimeout(() => { toast.style.display = 'none'; }, 3000);
    }

    function ouvrirModalInscription(eventId) {
        currentEventId = eventId;
        document.getElementById('inscrireNom').value = '';
        document.getElementById('inscrireEmail').value = '';
        document.getElementById('inscriptionModal').classList.add('show');
    }

    function fermerModalInscription() {
        document.getElementById('inscriptionModal').classList.remove('show');
    }

    function confirmerInscription() {
        const nom = document.getElementById('inscrireNom').value.trim();
        const email = document.getElementById('inscrireEmail').value.trim();
        
        if (!nom || !email) {
            showToast('Veuillez remplir tous les champs', true);
            return;
        }
        if (!email.includes('@')) {
            showToast('Email invalide', true);
            return;
        }
        
        fermerModalInscription();
        showToast("Inscription en cours...", false);
        
        const formData = new FormData();
        formData.append('nom', nom);
        formData.append('email', email);
        
        fetch('/ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/evenementC.php?action=inscrire&id=' + currentEventId, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            showToast(data.message, !data.success);
            if (data.success) setTimeout(() => location.reload(), 2000);
        })
        .catch(() => showToast('Erreur de connexion', true));
    }

    function voirDetail(eventId) {
        fetch('/ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/evenementC.php?action=get&id=' + eventId)
            .then(response => response.json())
            .then(data => {
                if (data.error) return;
                document.getElementById('detailTitre').innerHTML = data.titre;
                document.getElementById('detailType').innerHTML = data.type;
                document.getElementById('detailType').className = 'event-type type-' + data.type;
                document.getElementById('detailDescription').innerHTML = data.description;
                document.getElementById('detailDate').innerHTML = '📅 ' + new Date(data.date_evenement).toLocaleDateString('fr-FR');
                document.getElementById('detailLieu').innerHTML = '📍 ' + (data.lieu || 'En ligne');
                const placesRestantes = data.capacite - data.nb_inscrits;
                document.getElementById('detailPlaces').innerHTML = placesRestantes > 0 ? placesRestantes + ' places restantes' : '❌ Complet';
                document.getElementById('detailImage').innerHTML = data.image ? '<img src="/ProjetWeb/Esprit-PW-2A22-2526-Devcore/' + data.image + '">' : '<span style="font-size:3rem;">🎯</span>';
                const btn = document.getElementById('detailInscrireBtn');
                btn.setAttribute('onclick', 'ouvrirModalInscription(' + data.id + ')');
                btn.innerHTML = placesRestantes <= 0 ? '📋 Liste d\'attente' : '✨ S\'inscrire maintenant';
                document.getElementById('detailModal').classList.add('show');
            })
            .catch(() => showToast('Erreur de chargement', true));
    }

    function fermerDetailModal() {
        document.getElementById('detailModal').classList.remove('show');
    }

    function filterEvents() {
        const searchTerm = document.getElementById('searchInput').value.toLowerCase();
        const checkboxes = document.querySelectorAll('.type-filter:checked');
        const selectedTypes = Array.from(checkboxes).map(cb => cb.value);
        const activeChip = document.querySelector('.chip.active');
        const lieuFilter = activeChip ? activeChip.textContent.toLowerCase() : '';
        
        const cards = document.querySelectorAll('.event-card');
        let visibleCount = 0;
        
        cards.forEach(card => {
            let show = true;
            const title = card.getAttribute('data-title');
            const type = card.getAttribute('data-type');
            const lieu = card.getAttribute('data-lieu');
            
            if (searchTerm && !title.includes(searchTerm)) show = false;
            if (selectedTypes.length > 0 && !selectedTypes.includes(type)) show = false;
            if (lieuFilter === 'en ligne' && lieu !== 'en_ligne') show = false;
            if (lieuFilter === 'présentiel' && lieu !== 'presentiel') show = false;
            
            card.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });
        
        document.getElementById('eventsCount').textContent = visibleCount + ' événements';
    }
    
    function filterChip(btn, filter) {
        document.querySelectorAll('.chip').forEach(chip => chip.classList.remove('active'));
        btn.classList.add('active');
        filterEvents();
    }
    
    function sortEvents() {
        const sortBy = document.getElementById('sortSelect').value;
        const grid = document.getElementById('eventsGrid');
        const cards = Array.from(grid.querySelectorAll('.event-card'));
        
        cards.sort((a, b) => {
            if (sortBy === 'date') return parseInt(b.getAttribute('data-date')) - parseInt(a.getAttribute('data-date'));
            if (sortBy === 'date_asc') return parseInt(a.getAttribute('data-date')) - parseInt(b.getAttribute('data-date'));
            if (sortBy === 'places') return parseInt(b.getAttribute('data-places')) - parseInt(a.getAttribute('data-places'));
            return 0;
        });
        
        cards.forEach(card => grid.appendChild(card));
    }
    
    document.querySelectorAll('.type-filter').forEach(cb => cb.addEventListener('change', filterEvents));
    document.getElementById('inscriptionModal').addEventListener('click', function(e) { if (e.target === this) fermerModalInscription(); });
    document.getElementById('detailModal').addEventListener('click', function(e) { if (e.target === this) fermerDetailModal(); });
    // Gestion du thème (dark/light mode)
    function initTheme() {
        const savedTheme = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
            document.body.classList.add('dark-mode');
            const toggleBtn = document.getElementById('themeToggle');
            if (toggleBtn) toggleBtn.textContent = ' ◑';
        } else {
            document.body.classList.remove('dark-mode');
            const toggleBtn = document.getElementById('themeToggle');
            if (toggleBtn) toggleBtn.textContent = ' ◑';
        }
    }

    function toggleTheme() {
        if (document.body.classList.contains('dark-mode')) {
            document.body.classList.remove('dark-mode');
            localStorage.setItem('theme', 'light');
            document.getElementById('themeToggle').textContent = ' ◑';
        } else {
            document.body.classList.add('dark-mode');
            localStorage.setItem('theme', 'dark');
            document.getElementById('themeToggle').textContent = ' ◑';
        }
    }

    // Initialiser le thème au chargement de la page
    document.addEventListener('DOMContentLoaded', function() {
        initTheme();
        const toggleBtn = document.getElementById('themeToggle');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', toggleTheme);
        }
    });

</script>

</body>
</html>