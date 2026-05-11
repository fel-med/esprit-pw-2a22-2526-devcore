<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
if (($pos = strpos($scriptName, '/Vue/')) !== false) {
    $APP_BASE = substr($scriptName, 0, $pos);
} elseif (($pos = strpos($scriptName, '/Controleur/')) !== false) {
    $APP_BASE = substr($scriptName, 0, $pos);
} else {
    $APP_BASE = rtrim(dirname(dirname($scriptName)), '/');
}
$APP_BASE = rtrim($APP_BASE, '/');
$BASE = ($APP_BASE === '' ? '' : $APP_BASE) . '/Vue';
$frontActive = 'events';

if (!function_exists('event_front_url')) {
    function event_front_url($path) {
        global $APP_BASE;
        $path = (string)$path;
        if (preg_match('#^(?:https?:)?//#', $path)) {
            return $path;
        }
        return ($APP_BASE === '' ? '' : rtrim($APP_BASE, '/')) . '/' . ltrim($path, '/');
    }
}

if (!isset($evenements)) {
    require_once __DIR__ . '/../../../config.php';
    require_once __DIR__ . '/../../../Modele/evenement.php';
    
    try {
        $pdo = config::getConnexion();
        $stmt = $pdo->prepare("SELECT * FROM evenement WHERE statut = 'actif' ORDER BY DateFormation ASC");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                $row['image'] ?? null,
                $row['adresse_complete'] ?? null
            );
        }
        
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
    <?php require_once __DIR__ . '/../layout/front-theme-bootstrap.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Événements - Cre8Connect</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(event_front_url('Vue/FrontOffice/css/frontoffice.css')) ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(event_front_url('Vue/FrontOffice/layout/front-header.css')) ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= htmlspecialchars(event_front_url('Vue/public/images/logo.png')) ?>">
    <style>

        /* =========================================================
           Cre8Connect Event page theme repair
           The shared FrontOffice theme defines --text / --text-sub,
           while this page was written with --text-main / --text-dim.
           These aliases keep the page scoped and prevent Bootstrap's
           light body colors from leaking into dark mode.
           ========================================================= */
        :root {
            --text-main: var(--text);
            --text-dim: var(--text-sub);
            --danger-light: #fff1f3;
            --primary-glow: rgba(91, 79, 255, 0.24);
            --radius-sm: 12px;
        }

        [data-theme="dark"] {
            --text-main: var(--text);
            --text-dim: var(--text-sub);
            --danger-light: rgba(244, 63, 94, 0.14);
            --primary-glow: rgba(124, 111, 255, 0.28);
        }

        html,
        body {
            background: var(--bg) !important;
            color: var(--text) !important;
            min-height: 100%;
        }

        body,
        .event-page-main {
            transition: background-color 0.18s ease, color 0.18s ease;
        }

        .event-page-main {
            background: var(--bg);
            color: var(--text);
        }

        .section-header h2,
        .event-card-title,
        .detail-info h2,
        .events-sidebar label,
        .inscription-modal-card label {
            color: var(--text-main);
        }

        .events-sidebar .chip {
            color: var(--text-sub);
        }

        .events-sidebar .chip:not(.active):hover {
            color: var(--primary);
            border-color: var(--primary);
        }

        .events-sidebar .search-box input::placeholder {
            color: var(--text-sub);
            opacity: 0.85;
        }

        .sort-select:focus,
        .events-sidebar .search-box:focus-within {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-light);
        }

        .sort-select option {
            background: var(--white);
            color: var(--text-main);
        }

        .inscription-modal-card {
            background: var(--white) !important;
            color: var(--text-main) !important;
            border: 1px solid var(--border);
            box-shadow: 0 20px 60px rgba(15,14,26,0.18);
        }

        .inscription-modal-card .form-control {
            background: var(--bg);
            border-color: var(--border);
            color: var(--text-main);
        }

        .inscription-modal-card .form-control:focus {
            background: var(--bg);
            color: var(--text-main);
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-light);
        }

        [data-theme="dark"] .event-product-card:hover,
        [data-theme="dark"] .detail-modal-content,
        [data-theme="dark"] .inscription-modal-card {
            box-shadow: 0 20px 60px rgba(0,0,0,0.34);
        }

        [data-theme="dark"] .event-badge.formation { background: rgba(124, 111, 255, 0.16); color: #b9b2ff; }
        [data-theme="dark"] .event-badge.webinaire { background: rgba(245, 158, 11, 0.14); color: #f8c46c; }
        [data-theme="dark"] .event-badge.meetup { background: rgba(236, 72, 153, 0.14); color: #f7a3cb; }
        [data-theme="dark"] .event-badge.atelier { background: rgba(14, 163, 112, 0.14); color: #70dbb6; }
        /* Hero Section with Image */
        .hero-section {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--bg) 100%);
            border-radius: 24px;
            padding: 40px 48px;
            margin-bottom: 32px;
            border: 1px solid var(--border);
        }
        .hero-content {
            flex: 1;
        }
        .hero-content h1 {
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 12px;
            color: var(--text-main);
        }
        .hero-content p {
            font-size: 16px;
            color: var(--text-sub);
            margin-bottom: 24px;
        }
        .hero-stats {
            display: flex;
            gap: 32px;
        }
        .hero-stat {
            display: flex;
            flex-direction: column;
        }
        .hero-stat-number {
            font-size: 28px;
            font-weight: 800;
            color: var(--primary);
        }
        .hero-stat-label {
            font-size: 13px;
            color: var(--text-sub);
        }
        .hero-image {
            flex-shrink: 0;
            width: 180px;
            height: 180px;
        }
        .hero-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .section-header {
            border-bottom: 1px solid var(--border);
            padding-bottom: 16px;
            margin-bottom: 24px;
        }
        .section-header h2 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .section-header p {
            font-size: 14px;
            color: var(--text-sub);
        }

        .events-layout {
            display: flex;
            gap: 32px;
            margin-top: 24px;
        }
        .events-sidebar {
            width: 280px;
            flex-shrink: 0;
        }
        .events-sidebar .filter-section {
            background: var(--white);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid var(--border);
        }
        .events-sidebar .filter-title {
            font-size: 12px;
            font-weight: 700;
            color: var(--text-dim);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 16px;
        }
        .events-sidebar .filter-options {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .events-sidebar .filter-option {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: var(--text-sub);
            cursor: pointer;
        }
        .events-sidebar .filter-option input {
            width: 16px;
            height: 16px;
            accent-color: var(--primary);
        }
        .events-sidebar .search-box {
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 10px 14px;
        }
        .events-sidebar .search-box input {
            border: none;
            outline: none;
            background: transparent;
            width: 100%;
            color: var(--text-main);
        }
        .events-sidebar .chip-group {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .events-sidebar .chip {
            padding: 6px 14px;
            border-radius: 30px;
            border: 1px solid var(--border);
            background: var(--white);
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .events-sidebar .chip.active {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }
        .events-content {
            flex: 1;
        }
        .events-header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }
        .events-count {
            font-size: 14px;
            color: var(--text-sub);
        }
        .sort-select {
            padding: 8px 16px;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: var(--white);
            color: var(--text-main);
        }
        .events-product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 24px;
        }
        .event-product-card {
            background: var(--white);
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid var(--border);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .event-product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 28px rgba(0,0,0,0.1);
        }
        .event-card-image {
            height: 160px;
            background: var(--bg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            border-bottom: 1px solid var(--border);
            overflow: hidden;
        }
        .event-card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .event-card-content {
            padding: 20px;
        }
        .event-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 12px;
        }
        .event-badge.formation { background: #ece9ff; color: #5b4fff; }
        .event-badge.webinaire { background: #fffbeb; color: #f59e0b; }
        .event-badge.meetup { background: #fce7f3; color: #ec4899; }
        .event-badge.atelier { background: #edfaf5; color: #0ea370; }
        .event-card-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 10px;
            color: var(--text-main);
        }
        .event-card-desc {
            font-size: 13px;
            color: var(--text-sub);
            line-height: 1.5;
            margin-bottom: 16px;
        }
        .event-card-meta {
            display: flex;
            gap: 12px;
            font-size: 12px;
            color: var(--text-sub);
            padding: 12px 0;
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
            margin-bottom: 16px;
        }
        .event-spots {
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 16px;
        }
        .event-spots.available { color: #0ea370; }
        .event-spots.full { color: #f43f5e; }
        .btn-event-primary {
            width: 100%;
            padding: 12px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            margin-bottom: 8px;
        }
        .btn-event-primary:hover { background: var(--primary-hover); }
        .btn-event-secondary {
            width: 100%;
            padding: 10px;
            background: transparent;
            color: var(--text-sub);
            border: 1px solid var(--border);
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .btn-event-secondary:hover {
            border-color: var(--primary);
            color: var(--primary);
        }
        
        /* Modal Styles */
        .detail-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15,14,26,0.45);
            backdrop-filter: blur(6px);
            z-index: 1002;
            align-items: center;
            justify-content: center;
        }
        .detail-modal.show {
            display: flex;
            animation: fadeIn 0.25s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .detail-modal-content {
            background: var(--white);
            border-radius: 20px;
            width: 860px;
            max-width: 90%;
            max-height: 85vh;
            overflow-y: auto;
            position: relative;
            animation: slideUp 0.25s ease;
            border: 1px solid var(--border);
            box-shadow: 0 20px 60px rgba(15,14,26,0.18);
        }
        .detail-modal-close {
            position: absolute;
            top: 13px;
            right: 15px;
            background: var(--bg);
            border: 1px solid var(--border);
            font-size: 18px;
            cursor: pointer;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all .18s;
            z-index: 10;
            color: var(--text-sub);
        }
        .detail-modal-close:hover {
            background: var(--danger-light);
            color: var(--danger);
            border-color: rgba(244,63,94,0.2);
        }
        .detail-modal-body {
            display: flex;
        }
        .detail-image {
            flex: 1;
            min-height: 340px;
            background: var(--bg);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 20px 0 0 20px;
            overflow: hidden;
            font-size: 3rem;
            color: #c0bde0;
        }
        .detail-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .detail-info {
            flex: 1;
            padding: 28px;
        }
        .detail-info h2 {
            font-family: 'Fraunces', serif;
            font-size: 1.6rem;
            font-weight: 800;
            margin-bottom: 10px;
            color: var(--text-main);
            letter-spacing: -0.4px;
        }
        .detail-info p {
            color: var(--text-sub);
            line-height: 1.7;
            margin-bottom: 18px;
            font-size: 13.5px;
        }
        .detail-meta {
            margin: 16px 0;
            padding: 14px 0;
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .detail-meta span {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: var(--text-sub);
        }
        .detail-map {
            margin-top: 16px;
            border-radius: var(--radius-sm);
            overflow: hidden;
        }
        .detail-map iframe {
            width: 100%;
            height: 200px;
            border: 0;
            border-radius: var(--radius-sm);
        }
        .detail-actions {
            margin-top: 20px;
        }
        .btn-inscription-detail {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            width: 100%;
            padding: 11px 20px;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: var(--radius-sm);
            font-size: 14px;
            font-weight: 700;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            transition: background .18s, transform .15s;
            box-shadow: 0 2px 10px var(--primary-glow);
        }
        .btn-inscription-detail:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
        }
        
        @media (max-width: 768px) {
            .hero-section { flex-direction: column; text-align: center; padding: 24px; }
            .hero-stats { justify-content: center; }
            .hero-image { margin-top: 20px; }
            .events-layout { flex-direction: column; }
            .events-sidebar { width: 100%; }
            .detail-modal-body { flex-direction: column; }
            .detail-image { border-radius: 20px 20px 0 0; min-height: 200px; }
        }
    </style>
</head>
<body>
    <?php require_once dirname(__DIR__) . '/layout/header.php'; ?>
    
    <main class="container py-5 event-page-main">
        <!-- Hero Section -->
        <div class="hero-section">
            <div class="hero-content">
                <h1>Découvrez les événements de la communauté</h1>
                <p>Formations, webinaires, meetups et ateliers pour les créateurs et les marques.</p>
                <div class="hero-stats">
                    <div class="hero-stat">
                        <span class="hero-stat-number"><?= count($evenements) ?></span>
                        <span class="hero-stat-label">Événements disponibles</span>
                    </div>
                    <div class="hero-stat">
                        <span class="hero-stat-number"><?= array_sum(array_map(function($e) { return $e->getNbInscrits(); }, $evenements)) ?></span>
                        <span class="hero-stat-label">Participants</span>
                    </div>
                </div>
            </div>
            <div class="hero-image">
                <span style="font-size: 80px;">★ˎˊ˗</span>
            </div>
        </div>

        <div class="section-header">
            <h2>Tous les événements</h2>
            <p>Découvrez les événements disponibles près de chez vous ou en ligne</p>
        </div>

        <div class="events-layout">
            <aside class="events-sidebar">
                <div class="filter-section">
                    <div class="filter-title">🔍 RECHERCHE</div>
                    <div class="search-box">
                        <input type="text" id="searchInput" placeholder="Rechercher un événement...">
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

            <div class="events-content">
                <div class="events-header-bar">
                    <div class="events-count" id="eventsCount"><?= count($evenements) ?> événements</div>
                    <select id="sortSelect" class="sort-select">
                        <option value="date">Date (plus récent)</option>
                        <option value="date_asc">Date (plus ancien)</option>
                        <option value="spots">Places disponibles</option>
                    </select>
                </div>

                <div class="events-product-grid" id="eventsGrid"> 
                    <?php foreach ($evenements as $event): 
    $spotsLeft = $event->getCapacite() - $event->getNbInscrits();
    $isFull = ($spotsLeft <= 0);
    $eventImage = trim((string)$event->getImage());
    
    $forumLink = '';
    if (isset($forumsData[$event->getId()])) {
        $forumId = (int)$forumsData[$event->getId()]['idForum'];
        $forumHref = event_front_url('Controleur/forumC.php?idForum=' . $forumId);
        $forumLink = '<a href="' . htmlspecialchars($forumHref) . '" class="btn-event-secondary mt-2">💬 Accéder au forum</a>';
    }
?>
                        <div class="event-product-card" data-type="<?= $event->getType() ?>" data-title="<?= strtolower(htmlspecialchars($event->getTitre())) ?>" data-date="<?= strtotime($event->getDateEvenement()) ?>" data-spots="<?= $spotsLeft ?>" data-lieu="<?= strpos(strtolower($event->getLieu()), 'en ligne') !== false || empty($event->getLieu()) ? 'en_ligne' : 'presentiel' ?>">
                            <div class="event-card-image">
                                <?php if ($eventImage !== ''): 
                                    $imgSrc = event_front_url($eventImage);
                                ?>
                                    <img src="<?= htmlspecialchars($imgSrc) ?>"
                                         alt="<?= htmlspecialchars($event->getTitre()) ?>"
                                         onerror="this.style.display='none';this.parentElement.innerHTML='📅';">
                                <?php else: ?>
                                    📅
                                <?php endif; ?>
                            </div>
                            <div class="event-card-content">
                                <span class="event-badge <?= $event->getType() ?>"><?= ucfirst($event->getType()) ?></span>
                                <div class="event-card-title"><?= htmlspecialchars($event->getTitre()) ?></div>
                                <div class="event-card-desc"><?= htmlspecialchars(substr($event->getDescription(), 0, 100)) ?>...</div>
                                <div class="event-card-meta">
                                    <span>📅 <?= date('d M Y', strtotime($event->getDateEvenement())) ?></span>
                                    <span>📍 <?= htmlspecialchars($event->getLieu() ?: 'En ligne') ?></span>
                                </div>
                                <div class="event-spots <?= $isFull ? 'full' : 'available' ?>">
                                    <?= $isFull ? '❌ Complet' : '✅ ' . $spotsLeft . ' places restantes' ?>
                                </div>
                                <button onclick="ouvrirModalInscription(<?= $event->getId() ?>)" class="btn-event-primary">✨ S'inscrire</button>
                                <button onclick="voirDetail(<?= $event->getId() ?>)" class="btn-event-secondary">👁 Voir les détails</button>
                                <?= $forumLink ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Detail Modal -->
    <div id="detailModal" class="detail-modal">
        <div class="detail-modal-content">
            <button class="detail-modal-close" onclick="fermerDetailModal()">&times;</button>
            <div class="detail-modal-body">
                <div class="detail-image" id="detailImage"></div>
                <div class="detail-info">
                    <span class="event-badge" id="detailType"></span>
                    <h2 id="detailTitre"></h2>
                    <p id="detailDescription"></p>
                    <div class="detail-meta">
                        <span id="detailDate"></span>
                        <span id="detailLieu"></span>
                        <span id="detailPlaces"></span>
                    </div>
                    <div id="detailMap" class="detail-map"></div>
                    <div class="detail-actions">
                        <button id="detailInscrireBtn" class="btn-inscription-detail">✨ S'inscrire maintenant</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Inscription Modal -->
    <div id="inscriptionModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(6px); z-index: 1060; align-items: center; justify-content: center;">
        <div class="inscription-modal-card" style="background: var(--white); border-radius: 24px; width: 460px; max-width: 90%; overflow: hidden;">
            <div style="background: linear-gradient(135deg, #5b4fff, #7c3aed); padding: 24px; text-align: center; color: white;">
                <h3>✨ Inscription</h3>
                <p>Rejoignez cet événement</p>
            </div>
            <div style="padding: 24px;">
                <div style="margin-bottom: 16px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 700;">Nom complet</label>
                    <input type="text" id="inscrireNom" class="form-control">
                </div>
                <div style="margin-bottom: 16px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 700;">Email</label>
                    <input type="email" id="inscrireEmail" class="form-control">
                </div>
                <div style="display: flex; gap: 12px;">
                    <button onclick="fermerModalInscription()" class="btn btn-outline-secondary flex-grow-1">Annuler</button>
                    <button onclick="confirmerInscription()" class="btn btn-primary flex-grow-1">Confirmer</button>
                </div>
            </div>
        </div>
    </div>

    <div id="toast" style="position: fixed; bottom: 28px; right: 28px; background: #0ea370; color: white; padding: 12px 24px; border-radius: 30px; display: none; z-index: 1070;"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= htmlspecialchars(event_front_url('Vue/FrontOffice/layout/front-header.js')) ?>"></script>
    <script>
        let currentEventId = null;
        let currentLang = 'fr';
        const EVENT_APP_BASE = <?= json_encode($APP_BASE, JSON_UNESCAPED_SLASHES) ?>;

        function eventAppUrl(path) {
            const base = String(EVENT_APP_BASE || '').replace(/\/$/, '');
            return base + '/' + String(path || '').replace(/^\/+/, '');
        }
        
        const translations = {
            fr: {
                spots_remaining: 'places restantes',
                complete: 'Complet',
                waiting_list: "Liste d'attente",
                register_now: "S'inscrire maintenant",
                loading_error: 'Erreur de chargement'
            }
        };

        function showToast(message, isError = false) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.style.backgroundColor = isError ? '#f43f5e' : '#0ea370';
            toast.style.display = 'block';
            setTimeout(() => toast.style.display = 'none', 3000);
        }

        function ouvrirModalInscription(eventId) {
            currentEventId = eventId;
            document.getElementById('inscrireNom').value = '';
            document.getElementById('inscrireEmail').value = '';
            document.getElementById('inscriptionModal').style.display = 'flex';
        }

        function fermerModalInscription() {
            document.getElementById('inscriptionModal').style.display = 'none';
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
    showToast('Inscription en cours...');
    
    // Use FormData for POST request
    const formData = new FormData();
    formData.append('nom', nom);
    formData.append('email', email);
    
    fetch(eventAppUrl('Controleur/evenementC.php?action=inscrire&id=' + encodeURIComponent(currentEventId)),  {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, false);
            setTimeout(() => location.reload(), 2000);
        } else {
            showToast(data.message || 'Erreur lors de l\'inscription', true);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Erreur de connexion au serveur', true);
    });
}

        function voirDetail(eventId) {
            fetch(eventAppUrl('Controleur/evenementC.php?action=get&id=' + encodeURIComponent(eventId)))
                .then(response => {
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        showToast('Event non trouvé', true);
                        return;
                    }

                    document.getElementById('detailTitre').textContent = data.titre || '';
                    document.getElementById('detailType').textContent = data.type || '';
                    document.getElementById('detailType').className = 'event-badge ' + (data.type || '');
                    document.getElementById('detailDescription').textContent = data.description || '';
                    document.getElementById('detailDate').textContent = '📅 ' + new Date(data.date_evenement).toLocaleDateString('fr-FR', {day:'numeric', month:'long', year:'numeric'});
                    document.getElementById('detailLieu').textContent = '📍 ' + (data.lieu || 'En ligne');

                    const placesRestantes = Number(data.capacite || 0) - Number(data.nb_inscrits || 0);
                    document.getElementById('detailPlaces').textContent = placesRestantes > 0 ? '✅ ' + placesRestantes + ' places restantes' : '❌ Complet';

                    const detailImage = document.getElementById('detailImage');
                    detailImage.innerHTML = '';
                    if (data.image) {
                        const image = document.createElement('img');
                        image.src = eventAppUrl(data.image);
                        image.alt = data.titre || 'Événement';
                        image.onerror = function () {
                            detailImage.innerHTML = '<span style="font-size:3rem;">🎯</span>';
                        };
                        detailImage.appendChild(image);
                    } else {
                        detailImage.innerHTML = '<span style="font-size:3rem;">🎯</span>';
                    }

                    const detailMap = document.getElementById('detailMap');
                    detailMap.innerHTML = '';
                    if (data.adresse_complete) {
                        const iframe = document.createElement('iframe');
                        iframe.src = 'https://maps.google.com/maps?q=' + encodeURIComponent(data.adresse_complete) + '&output=embed';
                        iframe.loading = 'lazy';
                        detailMap.appendChild(iframe);

                        const address = document.createElement('p');
                        address.style.marginTop = '8px';
                        address.textContent = '📍 ' + data.adresse_complete;
                        detailMap.appendChild(address);
                    }
                    
                    const btn = document.getElementById('detailInscrireBtn');
                    btn.onclick = function() { ouvrirModalInscription(data.id); };
                    btn.innerHTML = placesRestantes <= 0 ? '📋 Liste d\'attente' : '✨ S\'inscrire maintenant';
                    document.getElementById('detailModal').classList.add('show');
                })
                .catch(error => {
                    console.error('Event detail error:', error);
                    showToast('Erreur de connexion', true);
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
            
            const cards = document.querySelectorAll('.event-product-card');
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
            const cards = Array.from(grid.querySelectorAll('.event-product-card'));
            cards.sort((a, b) => {
                if (sortBy === 'date') return parseInt(b.getAttribute('data-date')) - parseInt(a.getAttribute('data-date'));
                if (sortBy === 'date_asc') return parseInt(a.getAttribute('data-date')) - parseInt(b.getAttribute('data-date'));
                if (sortBy === 'spots') return parseInt(b.getAttribute('data-spots')) - parseInt(a.getAttribute('data-spots'));
                return 0;
            });
            cards.forEach(card => grid.appendChild(card));
        }

        document.querySelectorAll('.type-filter').forEach(cb => cb.addEventListener('change', filterEvents));
        document.getElementById('searchInput').addEventListener('keyup', filterEvents);
        document.getElementById('sortSelect').addEventListener('change', sortEvents);
        
        document.getElementById('inscriptionModal').addEventListener('click', function(e) {
            if (e.target === this) fermerModalInscription();
        });
        document.getElementById('detailModal').addEventListener('click', function(e) {
            if (e.target === this) fermerDetailModal();
        });
    </script>
</body>
</html>