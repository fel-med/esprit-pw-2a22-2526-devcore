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
    } catch (Exception $e) {
        $evenements = [];
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Événements - Cre8Connect</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            color: #0f172a;
        }

        /* Header */
        .header {
            background: white;
            padding: 16px 32px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.3rem;
            font-weight: 700;
            color: #4f46e5;
            text-decoration: none;
        }

        .logo-img {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            object-fit: cover;
        }

        .nav-links {
            display: flex;
            gap: 32px;
        }

        .nav-links a {
            text-decoration: none;
            color: #64748b;
            font-weight: 500;
            transition: color 0.2s;
        }

        .nav-links a:hover, .nav-links a.active {
            color: #4f46e5;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            cursor: pointer;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            padding: 48px 32px;
            text-align: center;
            color: white;
        }

        .hero h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .hero p {
            font-size: 1rem;
            opacity: 0.9;
            max-width: 500px;
            margin: 0 auto;
        }

        /* Stats Bar */
        .stats-bar {
            display: flex;
            justify-content: center;
            gap: 48px;
            margin-top: 32px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
        }

        .stat-label {
            font-size: 0.8rem;
            opacity: 0.8;
        }

        /* Main Layout */
        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 32px;
            display: flex;
            gap: 32px;
        }

        /* Sidebar Filters */
        .sidebar {
            width: 280px;
            flex-shrink: 0;
        }

        .filter-section {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }

        .filter-title {
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 16px;
            color: #0f172a;
        }

        .filter-options {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .filter-option {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.85rem;
            color: #475569;
            cursor: pointer;
        }

        .filter-option input {
            width: 16px;
            height: 16px;
            accent-color: #4f46e5;
        }

        .search-box {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 40px;
            background: white;
            margin-bottom: 16px;
        }

        .search-box input {
            border: none;
            outline: none;
            font-size: 0.85rem;
            width: 100%;
        }

        .chip-group {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .chip {
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 500;
            border: 1px solid #e2e8f0;
            background: white;
            color: #475569;
            cursor: pointer;
            transition: all 0.2s;
        }

        .chip:hover, .chip.active {
            background: #4f46e5;
            border-color: #4f46e5;
            color: white;
        }

        /* Events Section */
        .events-section {
            flex: 1;
        }

        .events-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .events-count {
            font-size: 0.85rem;
            color: #64748b;
        }

        .sort-controls {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .sort-controls select {
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.8rem;
            background: white;
            cursor: pointer;
        }

        /* Events Grid */
        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 24px;
        }

        .event-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
        }

        .event-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.1);
        }

        .event-image {
            height: 180px;
            background-size: cover;
            background-position: center;
            background-color: #e0e7ff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
        }

        .event-info {
            padding: 16px;
        }

        .event-type {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .type-formation { background: #dbeafe; color: #1d4ed8; }
        .type-webinaire { background: #fef3c7; color: #92400e; }
        .type-meetup { background: #fce7f3; color: #ec4899; }
        .type-atelier { background: #d1fae5; color: #065f46; }

        .event-title {
            font-weight: 700;
            font-size: 1rem;
            margin-bottom: 8px;
            color: #0f172a;
        }

        .event-description {
            font-size: 0.8rem;
            color: #64748b;
            line-height: 1.4;
            margin-bottom: 12px;
        }

        .event-meta {
            display: flex;
            gap: 12px;
            font-size: 0.7rem;
            color: #64748b;
            margin-bottom: 12px;
        }

        .event-spots {
            font-size: 0.7rem;
            color: #10b981;
            font-weight: 500;
            margin-bottom: 12px;
        }

        .event-spots.full {
            color: #ef4444;
        }

        .btn-event {
            width: 100%;
            padding: 10px;
            background: #4f46e5;
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.8rem;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-event:hover {
            background: #4338ca;
        }

        .btn-event.outline {
            background: transparent;
            color: #4f46e5;
            border: 1.5px solid #4f46e5;
        }

        .btn-event.outline:hover {
            background: #4f46e5;
            color: white;
        }

        /* Modal */
        .inscription-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .inscription-modal.show { display: flex; }
        .inscription-card {
            background: white;
            border-radius: 20px;
            width: 400px;
            max-width: 90%;
            padding: 30px;
        }
        .inscription-card h3 { font-size: 1.3rem; margin-bottom: 10px; }
        .inscription-card p { color: #6b7280; font-size: 0.85rem; margin-bottom: 20px; }
        .inscription-input {
            width: 100%;
            padding: 12px 15px;
            margin-bottom: 15px;
            border: 1.5px solid #e5e7eb;
            border-radius: 12px;
            font-size: 0.9rem;
        }
        .inscription-input:focus { outline: none; border-color: #4f46e5; }
        .inscription-buttons { display: flex; gap: 12px; }
        .btn-inscrire-modal {
            flex: 1;
            padding: 12px;
            background: #4f46e5;
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-annuler-modal {
            flex: 1;
            padding: 12px;
            background: #f3f4f6;
            color: #374151;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
        }
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #10b981;
            color: white;
            padding: 12px 24px;
            border-radius: 50px;
            display: none;
            z-index: 1000;
        }
        .toast.error { background: #ef4444; }

        /* Responsive */
        @media (max-width: 1024px) {
            .main-container {
                flex-direction: column;
            }
            .sidebar {
                width: 100%;
                display: flex;
                gap: 20px;
                overflow-x: auto;
            }
            .filter-section {
                min-width: 250px;
            }
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 16px;
            }
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }
            .events-header {
                flex-direction: column;
                align-items: stretch;
            }
            .stats-bar {
                flex-direction: column;
                gap: 16px;
            }
        }
    </style>
</head>
<body>

<!-- Header -->
<header class="header">
    <a href="#" class="logo">
        <img src="/ProjetWeb/Esprit-PW-2A22-2526-Devcore/Vue/public/images/logo.png" alt="Cre8Connect" class="logo-img">
        <span>Cre8Connect</span>
    </a>
    <nav class="nav-links">
        <a href="#">Accueil</a>
        <a href="#" class="active">Événements</a>
        <a href="#">Produits</a>
        <a href="#">Offres</a>
        <a href="#">Forum</a>
    </nav>
    <div class="user-menu">
        <div class="avatar"></div>
    </div>
</header>

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

<!-- Main Content -->
<div class="main-container">
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
                <label class="filter-option">
                    <input type="checkbox" value="formation" class="type-filter"> Formation
                </label>
                <label class="filter-option">
                    <input type="checkbox" value="webinaire" class="type-filter"> Webinaire
                </label>
                <label class="filter-option">
                    <input type="checkbox" value="meetup" class="type-filter"> Meetup
                </label>
                <label class="filter-option">
                    <input type="checkbox" value="atelier" class="type-filter"> Atelier
                </label>
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
                <span style="font-size:0.8rem; color:#64748b;">TRIER PAR</span>
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
                $isFull = $spotsLeft <= 0;
                $isOnline = strpos(strtolower($event->getLieu()), 'en ligne') !== false || empty($event->getLieu());
            ?>
            <div class="event-card" 
                 data-type="<?= $event->getType() ?>" 
                 data-lieu="<?= $isOnline ? 'en_ligne' : 'presentiel' ?>" 
                 data-title="<?= strtolower(htmlspecialchars($event->getTitre())) ?>"
                 data-date="<?= strtotime($event->getDateEvenement()) ?>"
                 data-places="<?= $spotsLeft ?>">
                <div class="event-image" style="<?= $event->getImage() ? 'background-image: url(/ProjetWeb/Esprit-PW-2A22-2526-Devcore/' . $event->getImage() . '); background-size: cover;' : '' ?>">
                    <?php if (!$event->getImage()): ?>
                        <?php $emojis = ['🎯', '📚', '💡', '🚀', '✨', '🎨', '💻', '🤝']; echo $emojis[array_rand($emojis)]; ?>
                    <?php endif; ?>
                </div>
                <div class="event-info">
                    <span class="event-type type-<?= $event->getType() ?>"><?= ucfirst($event->getType()) ?></span>
                    <div class="event-title"><?= htmlspecialchars($event->getTitre()) ?></div>
                    <div class="event-description"><?= htmlspecialchars(substr($event->getDescription(), 0, 100)) ?>...</div>
                    <div class="event-meta">
                        <span>📅 <?= date('d M Y', strtotime($event->getDateEvenement())) ?></span>
                        <span>📍 <?= htmlspecialchars($event->getLieu() ?: 'En ligne') ?></span>
                    </div>
                    <div class="event-spots <?= $isFull ? 'full' : '' ?>">
                        <?= $isFull ? 'Complet' : ($spotsLeft . ' places restantes') ?>
                    </div>
                    <button onclick="ouvrirModalInscription(<?= $event->getId() ?>)" class="btn-event <?= $isFull ? 'outline' : '' ?>">
                        <?= $isFull ? '📋 Liste d\'attente' : '✨ S\'inscrire' ?>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Modal Inscription -->
<div id="inscriptionModal" class="inscription-modal">
    <div class="inscription-card">
        <h3>📝 Inscription à l'événement</h3>
        <p>Renseignez vos informations pour vous inscrire</p>
        <input type="text" id="inscrireNom" class="inscription-input" placeholder="Votre nom complet">
        <input type="email" id="inscrireEmail" class="inscription-input" placeholder="Votre email">
        <div class="inscription-buttons">
            <button class="btn-annuler-modal" onclick="fermerModalInscription()">Annuler</button>
            <button class="btn-inscrire-modal" onclick="confirmerInscription()">S'inscrire</button>
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
        currentEventId = null;
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
            if (sortBy === 'date') {
                return parseInt(b.getAttribute('data-date')) - parseInt(a.getAttribute('data-date'));
            } else if (sortBy === 'date_asc') {
                return parseInt(a.getAttribute('data-date')) - parseInt(b.getAttribute('data-date'));
            } else if (sortBy === 'places') {
                return parseInt(b.getAttribute('data-places')) - parseInt(a.getAttribute('data-places'));
            }
            return 0;
        });
        
        cards.forEach(card => grid.appendChild(card));
    }
    
    document.querySelectorAll('.type-filter').forEach(cb => {
        cb.addEventListener('change', filterEvents);
    });
    
    document.getElementById('inscriptionModal').addEventListener('click', function(e) {
        if (e.target === this) fermerModalInscription();
    });
</script>

</body>
</html> 