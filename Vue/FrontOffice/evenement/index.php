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

        .btn-detail {
            width: 100%;
            padding: 10px;
            background: transparent;
            color: #4f46e5;
            border: 1px solid #4f46e5;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 8px;
        }

        .btn-detail:hover {
            background: #4f46e5;
            color: white;
        }

        /* Modal d'inscription stylé */
        .inscription-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .inscription-modal.show {
            display: flex;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .inscription-card {
            background: white;
            border-radius: 24px;
            width: 480px;
            max-width: 90%;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .inscription-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 24px 28px 0 28px;
        }

        .inscription-header h3 {
            font-size: 1.4rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
        }

        .inscription-close {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #94a3b8;
            transition: color 0.2s;
        }

        .inscription-close:hover {
            color: #ef4444;
        }

        .inscription-subtitle {
            padding: 8px 28px 0 28px;
            color: #64748b;
            font-size: 0.85rem;
            margin-bottom: 20px;
        }

        .inscription-form-group {
            padding: 0 28px;
            margin-bottom: 20px;
        }

        .inscription-label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
        }

        .inscription-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            font-size: 1rem;
            color: #94a3b8;
        }

        .inscription-input {
            width: 100%;
            padding: 12px 16px 12px 42px;
            border: 1.5px solid #e2e8f0;
            border-radius: 14px;
            font-size: 0.9rem;
            font-family: inherit;
            transition: all 0.2s;
            background: #f8fafc;
        }

        .inscription-input:focus {
            outline: none;
            border-color: #4f46e5;
            background: white;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .inscription-buttons {
            display: flex;
            gap: 12px;
            padding: 8px 28px 28px 28px;
        }

        .btn-inscrire-modal {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 20px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white;
            border: none;
            border-radius: 14px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-inscrire-modal:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(79, 70, 229, 0.4);
        }

        .btn-annuler-modal {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 20px;
            background: #f1f5f9;
            color: #475569;
            border: none;
            border-radius: 14px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-annuler-modal:hover {
            background: #e2e8f0;
        }

        /* Modal Détail Événement */
        .detail-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(8px);
            z-index: 1001;
            align-items: center;
            justify-content: center;
        }

        .detail-modal.show {
            display: flex;
            animation: fadeIn 0.3s ease;
        }

        .detail-modal-content {
            background: white;
            border-radius: 28px;
            width: 900px;
            max-width: 90%;
            max-height: 85vh;
            overflow-y: auto;
            position: relative;
            animation: slideUp 0.3s ease;
        }

        .detail-modal-close {
            position: absolute;
            top: 20px;
            right: 25px;
            background: white;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #64748b;
            z-index: 10;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .detail-modal-close:hover {
            background: #f1f5f9;
            color: #ef4444;
        }

        .detail-modal-body {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
        }

        .detail-image {
            height: 100%;
            min-height: 400px;
            background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 28px 0 0 28px;
            overflow: hidden;
        }

        .detail-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .detail-info {
            padding: 32px;
        }

        .detail-info h2 {
            font-size: 1.6rem;
            font-weight: 700;
            margin: 12px 0;
            color: #0f172a;
        }

        .detail-info p {
            color: #475569;
            line-height: 1.6;
            margin-bottom: 24px;
        }

        .detail-meta {
            display: flex;
            flex-direction: column;
            gap: 12px;
            padding: 20px 0;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 24px;
        }

        .detail-meta span {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            color: #475569;
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
            z-index: 1002;
        }
        .toast.error { background: #ef4444; }

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
            .detail-modal-body {
                grid-template-columns: 1fr;
            }
            .detail-image {
                min-height: 200px;
                border-radius: 28px 28px 0 0;
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
                $isFull = ($spotsLeft <= 0);
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
                        <?php if ($isFull): ?>
                            ❌ Complet
                        <?php else: ?>
                                 <?= $spotsLeft ?> places restantes
                        <?php endif; ?>
                    </div>
                    <button onclick="ouvrirModalInscription(<?= $event->getId() ?>)" class="btn-event <?= $isFull ? 'outline' : '' ?>">
                        <?= $isFull ? '📋 Liste d\'attente' : '✨ S\'inscrire' ?>
                    </button>
                    <button onclick="voirDetail(<?= $event->getId() ?>)" class="btn-detail">
                        👁 Voir les détails
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
        <div class="inscription-header">
            <h3>✨ Inscription à l'événement</h3>
            <button class="inscription-close" onclick="fermerModalInscription()">&times;</button>
        </div>
        <p class="inscription-subtitle">Rejoignez cet événement et connectez-vous avec la communauté</p>
        
        <div class="inscription-form-group">
            <label class="inscription-label">Nom complet</label>
            <div class="inscription-input-wrapper">
                <span class="input-icon">👤</span>
                <input type="text" id="inscrireNom" class="inscription-input" placeholder="Votre nom et prénom" autocomplete="off">
            </div>
        </div>
        
        <div class="inscription-form-group">
            <label class="inscription-label">Adresse email</label>
            <div class="inscription-input-wrapper">
                <span class="input-icon">📧</span>
                <input type="email" id="inscrireEmail" class="inscription-input" placeholder="votre@email.com" autocomplete="off">
            </div>
        </div>
        
        <div class="inscription-buttons">
            <button class="btn-annuler-modal" onclick="fermerModalInscription()">Annuler</button>
            <button class="btn-inscrire-modal" onclick="confirmerInscription()">S'inscrire</button>
        </div>
    </div>
</div>

<!-- Modal Détail Événement -->
<div id="detailModal" class="detail-modal">
    <div class="detail-modal-content">
        <button class="detail-modal-close" onclick="fermerDetailModal()">&times;</button>
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

    function voirDetail(eventId) {
        fetch('/ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/evenementC.php?action=get&id=' + eventId)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    showToast('Erreur lors du chargement', true);
                    return;
                }
                
                document.getElementById('detailTitre').innerHTML = data.titre;
                document.getElementById('detailType').innerHTML = data.type;
                document.getElementById('detailType').className = 'event-type type-' + data.type;
                document.getElementById('detailDescription').innerHTML = data.description;
                document.getElementById('detailDate').innerHTML = '📅 ' + new Date(data.date_evenement).toLocaleDateString('fr-FR', {day:'numeric', month:'long', year:'numeric'});
                document.getElementById('detailLieu').innerHTML = '📍 ' + (data.lieu || 'En ligne');
                
                const placesRestantes = data.capacite - data.nb_inscrits;
                if (placesRestantes > 0) {
                    document.getElementById('detailPlaces').innerHTML =  placesRestantes + ' places restantes';
                } else {
                    document.getElementById('detailPlaces').innerHTML = '❌ Complet';
                }
                
                if (data.image) {
                    document.getElementById('detailImage').innerHTML = '<img src="/ProjetWeb/Esprit-PW-2A22-2526-Devcore/' + data.image + '" alt="' + data.titre + '">';
                } else {
                    document.getElementById('detailImage').innerHTML = '<span style="font-size: 3rem;">🎯</span>';
                }
                
                const inscrireBtn = document.getElementById('detailInscrireBtn');
                inscrireBtn.setAttribute('onclick', 'ouvrirModalInscription(' + data.id + ')');
                if (placesRestantes <= 0) {
                    inscrireBtn.innerHTML = '📋 Liste d\'attente';
                    inscrireBtn.classList.add('outline');
                } else {
                    inscrireBtn.innerHTML = '✨ S\'inscrire maintenant';
                    inscrireBtn.classList.remove('outline');
                }
                
                document.getElementById('detailModal').classList.add('show');
            })
            .catch(error => {
                console.error('Erreur:', error);
                showToast('Erreur de chargement', true);
            });
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
    
    document.getElementById('detailModal').addEventListener('click', function(e) {
        if (e.target === this) fermerDetailModal();
    });
</script>

</body>
</html>