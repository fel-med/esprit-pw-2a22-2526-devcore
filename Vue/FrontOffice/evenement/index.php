<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../../Controleur/session_helper.php';

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
$isAdminVisitor = cc_is_backoffice_role(cc_current_user_role());

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
    require_once __DIR__ . '/../../../Controleur/notificationC.php';
    
    try {
        $pdo = config::getConnexion();
        $currentEventUserId = (int) ($_SESSION['id'] ?? ($_SESSION['user']['id'] ?? ($_SESSION['user_id'] ?? ($_SESSION['utilisateur']['id'] ?? 0))));
        if ($currentEventUserId > 0) {
            $todayEventStmt = $pdo->prepare("
                SELECT e.idFormation, e.TitreFormation
                FROM inscription_evenement i
                INNER JOIN evenement e ON e.idFormation = i.id_evenement
                WHERE i.id_utilisateur = :userId
                  AND DATE(e.DateFormation) = CURDATE()
                  AND e.statut = 'actif'
            ");
            $todayEventStmt->execute(['userId' => $currentEventUserId]);
            $today = date('Y-m-d');
            $notificationC = new NotificationC($pdo);
            foreach ($todayEventStmt->fetchAll(PDO::FETCH_ASSOC) as $todayEvent) {
                $todayEventId = (int) ($todayEvent['idFormation'] ?? 0);
                if ($todayEventId <= 0) {
                    continue;
                }

                $todayEventTitle = trim((string) ($todayEvent['TitreFormation'] ?? '')) ?: 'your event';
                $notificationC->createNotification(
                    $currentEventUserId,
                    'event_today',
                    'Event starts today',
                    'The event ' . $todayEventTitle . ' starts today.',
                    event_front_url('Vue/FrontOffice/evenement/index.php'),
                    'evenement',
                    $todayEventId,
                    null,
                    null,
                    'event_today_' . $todayEventId . '_user_' . $currentEventUserId . '_' . $today,
                    [
                        'event_id' => $todayEventId,
                        'event_title' => $todayEventTitle,
                        'date' => $today,
                    ]
                );
            }
        }

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

        // Auto-create forums for events whose date has arrived
        require_once __DIR__ . '/../../../Controleur/forumC.php';
        $forumCtrl = new ForumC();
        $forumCtrl->creerForumsAuto();

        // Reload forumsData after potential new creations
        $forumsData = [];
        $stmtForum2 = $pdo->prepare("SELECT idFormation, idForum, est_actif FROM forum WHERE est_actif = 1");
        $stmtForum2->execute();
        while ($row = $stmtForum2->fetch(PDO::FETCH_ASSOC)) {
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
    <link rel="icon" type="image/png" sizes="16x16" href="<?= htmlspecialchars(event_front_url('Vue/public/images/favicon-16.png')) ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= htmlspecialchars(event_front_url('Vue/public/images/favicon-32.png')) ?>">
    <link rel="shortcut icon" type="image/png" href="<?= htmlspecialchars(event_front_url('Vue/public/images/favicon-32.png')) ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= htmlspecialchars(event_front_url('Vue/public/images/apple-touch-icon.png')) ?>">
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
            border-radius: 14px;
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
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }
        .section-header-text h2 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .section-header-text p {
            font-size: 14px;
            color: var(--text-sub);
        }
        /* Language toggle */
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
            border-radius: 14px;
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
            box-shadow: 0 8px 18px rgba(91, 79, 255, 0.22);
        }

        html:not([data-theme="dark"]) .events-sidebar .chip.active,
        body.light-mode .events-sidebar .chip.active {
            background: #5b4fff;
            border-color: #4438e0;
            color: #ffffff;
            box-shadow: 0 8px 18px rgba(91, 79, 255, 0.26);
        }

        .events-sidebar .chip[data-lieu-filter] {
            min-width: 58px;
            text-align: center;
        }

        html:not([data-theme="dark"]) .events-sidebar .chip[data-lieu-filter].active,
        body.light-mode .events-sidebar .chip[data-lieu-filter].active {
            background: #5b4fff !important;
            border-color: #4438e0 !important;
            color: #ffffff !important;
            box-shadow: 0 8px 18px rgba(91, 79, 255, 0.26) !important;
        }

        [data-theme="dark"] .events-sidebar .chip[data-lieu-filter].active,
        body.dark-mode .events-sidebar .chip[data-lieu-filter].active {
            background: var(--primary) !important;
            border-color: var(--primary) !important;
            color: #ffffff !important;
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
            border-radius: 14px;
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
            border-radius: 14px;
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
            border-radius: 14px;
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
            border-radius: 14px 0 0 14px;
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
            .detail-image { border-radius: 14px 14px 0 0; min-height: 200px; }
        }

        /* Shared FrontOffice visual bridge for event pages. */
        body,
        .event-page-main {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg) !important;
            color: var(--text) !important;
        }

        .hero-content h1,
        .section-header h2,
        .section-header-text h2,
        .event-card-title,
        .detail-info h2 {
            font-family: 'Fraunces', serif;
            color: var(--text) !important;
            letter-spacing: 0;
        }

        .hero-content p,
        .section-header p,
        .section-header-text p,
        .event-card-desc,
        .event-card-meta,
        .events-count,
        .detail-info p,
        .detail-meta span {
            color: var(--text-sub) !important;
        }

        .hero-section,
        .event-product-card,
        .events-sidebar .filter-section,
        .detail-modal-content,
        .inscription-modal-card,
        .empty-state {
            background: var(--white) !important;
            border: 1px solid var(--border) !important;
            border-radius: 14px !important;
            color: var(--text) !important;
            box-shadow: 0 12px 32px rgba(15, 14, 26, 0.06) !important;
        }

        .event-product-card:hover {
            border-color: color-mix(in srgb, var(--primary, #5b4fff) 24%, var(--border, #ebebf2));
            box-shadow: 0 18px 42px rgba(91, 79, 255, 0.12) !important;
        }

        .event-card-image,
        .detail-image,
        .events-sidebar .search-box,
        .events-sidebar .chip:not(.active),
        .sort-select,
        .inscription-modal-card .form-control {
            background: var(--bg) !important;
            border-color: var(--border) !important;
            color: var(--text) !important;
        }

        .events-sidebar .search-box,
        .sort-select,
        .inscription-modal-card .form-control {
            border-radius: 10px !important;
            font-family: 'DM Sans', sans-serif;
        }

        .sort-select:focus,
        .events-sidebar .search-box:focus-within,
        .inscription-modal-card .form-control:focus {
            background: var(--white) !important;
            border-color: var(--primary) !important;
            box-shadow: 0 0 0 3px var(--primary-glow, rgba(91, 79, 255, 0.15)) !important;
        }

        .btn-event-primary,
        .btn-inscription-detail,
        body > main .btn-primary {
            background: var(--primary) !important;
            border-color: var(--primary) !important;
            border-radius: 10px !important;
            color: #ffffff !important;
            font-family: 'DM Sans', sans-serif !important;
            box-shadow: 0 3px 10px var(--primary-glow, rgba(91, 79, 255, 0.15)) !important;
        }

        .btn-event-primary:hover,
        .btn-inscription-detail:hover,
        body > main .btn-primary:hover {
            background: var(--primary-hover, var(--primary)) !important;
            border-color: var(--primary-hover, var(--primary)) !important;
        }

        .btn-event-secondary,
        body > main .btn-outline-secondary {
            background: var(--bg) !important;
            border: 1px solid var(--border) !important;
            border-radius: 10px !important;
            color: var(--text) !important;
            font-family: 'DM Sans', sans-serif !important;
            box-shadow: none !important;
        }

        .btn-event-secondary:hover,
        body > main .btn-outline-secondary:hover {
            border-color: var(--primary) !important;
            color: var(--primary) !important;
        }

        .event-badge.formation { background: var(--primary-light) !important; color: var(--primary) !important; }

        [data-theme="dark"] .hero-section,
        [data-theme="dark"] .event-product-card,
        [data-theme="dark"] .events-sidebar .filter-section,
        [data-theme="dark"] .detail-modal-content,
        [data-theme="dark"] .inscription-modal-card,
        [data-theme="dark"] .empty-state {
            background: var(--white) !important;
            border-color: var(--border) !important;
            box-shadow: 0 18px 42px rgba(0, 0, 0, 0.22) !important;
        }

        /* Marketplace body composition for the event page. */
        .event-page-main {
            max-width: 1220px;
            margin-inline: auto;
        }

        .hero-section {
            position: relative;
            overflow: hidden;
            min-height: auto;
            padding: clamp(1.5rem, 3vw, 2.05rem) !important;
            border-radius: 22px !important;
            background:
                radial-gradient(circle at 88% 12%, rgba(124, 111, 255, 0.20), transparent 12rem),
                radial-gradient(circle at 10% 0%, rgba(255, 255, 255, 0.88), transparent 15rem),
                linear-gradient(135deg, rgba(236, 233, 255, 0.90), rgba(255, 255, 255, 0.92)) !important;
            border-color: rgba(91, 79, 255, 0.14) !important;
            box-shadow: 0 18px 44px rgba(91, 79, 255, 0.10) !important;
        }

        .hero-content h1 {
            max-width: 720px;
            font-size: clamp(2rem, 4vw, 3.05rem);
            line-height: 1.04;
        }

        .hero-content p {
            max-width: 680px;
            margin-bottom: 1rem;
        }

        .hero-stats {
            gap: 0.65rem;
            flex-wrap: wrap;
        }

        .hero-stat {
            min-width: 130px;
            padding: 0.68rem 0.85rem;
            border: 1px solid rgba(91, 79, 255, 0.12);
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.62);
        }

        .hero-stat-number {
            font-size: 1.28rem;
            line-height: 1;
        }

        .hero-image {
            width: clamp(96px, 14vw, 140px);
            height: clamp(96px, 14vw, 140px);
            padding: 0.8rem;
            border-radius: 28px;
            background: rgba(255, 255, 255, 0.50);
            box-shadow: inset 0 0 0 1px rgba(91, 79, 255, 0.10);
        }

        .events-layout {
            gap: 1.15rem;
            align-items: flex-start;
        }

        .events-sidebar {
            width: min(260px, 100%);
        }

        .events-sidebar .filter-section {
            padding: 0.9rem !important;
            border-radius: 18px !important;
            background: color-mix(in srgb, var(--white) 84%, var(--primary-light, #ece9ff)) !important;
            box-shadow: 0 10px 26px rgba(91, 79, 255, 0.07) !important;
        }

        .events-sidebar .filter-title {
            margin-bottom: 0.7rem;
            color: var(--text-sub);
            letter-spacing: 0;
            text-transform: none;
        }

        .events-sidebar .filter-options {
            gap: 0.55rem;
        }

        .events-sidebar .chip-group {
            gap: 0.45rem;
        }

        .events-sidebar .chip {
            border-radius: 999px !important;
            background: var(--white) !important;
        }

        .events-header-bar {
            margin-bottom: 1rem;
        }

        .events-product-grid {
            gap: 1rem;
        }

        .event-product-card {
            border-radius: 18px !important;
            box-shadow: 0 14px 34px rgba(91, 79, 255, 0.07) !important;
        }

        .event-card-content {
            padding: 1rem;
        }

        [data-theme="dark"] .hero-section {
            background:
                radial-gradient(circle at 88% 12%, rgba(124, 111, 255, 0.18), transparent 12rem),
                linear-gradient(135deg, color-mix(in srgb, var(--primary-light, #1e1a3a) 52%, var(--white)), var(--white)) !important;
            border-color: color-mix(in srgb, var(--primary, #7c6fff) 28%, var(--border, #2a2840)) !important;
        }

        [data-theme="dark"] .hero-stat,
        [data-theme="dark"] .hero-image,
        [data-theme="dark"] .events-sidebar .filter-section {
            background: color-mix(in srgb, var(--white) 82%, var(--primary-light, #1e1a3a)) !important;
            border-color: var(--border) !important;
        }

        /* Unified FrontOffice indicators. */
        .hero-stats {
            gap: 0.55rem !important;
            align-items: center;
        }

        .hero-stat {
            min-width: auto !important;
            display: inline-flex !important;
            align-items: center;
            flex-direction: row !important;
            gap: 0.45rem;
            padding: 0.48rem 0.72rem !important;
            border-radius: 999px !important;
            background: color-mix(in srgb, var(--white) 66%, var(--primary-light, #ece9ff)) !important;
            border: 1px solid color-mix(in srgb, var(--primary, #5b4fff) 14%, var(--border, #ebebf2)) !important;
            box-shadow: 0 8px 20px rgba(91, 79, 255, 0.07);
        }

        .hero-stat-number {
            font-family: 'Fraunces', serif;
            font-size: 1.2rem !important;
            line-height: 1;
            color: var(--primary) !important;
        }

        .hero-stat-label {
            color: var(--text-sub) !important;
            font-size: 0.76rem !important;
            font-weight: 700;
        }

        .event-badge {
            min-height: 1.55rem;
            padding: 0.22rem 0.62rem !important;
            border-radius: 999px !important;
            border: 1px solid transparent;
            font-size: 0.72rem !important;
            font-weight: 700 !important;
            letter-spacing: 0 !important;
        }

        .event-badge.formation,
        .event-badge:not(.webinaire):not(.meetup):not(.atelier) {
            background: var(--primary-light) !important;
            border-color: color-mix(in srgb, var(--primary) 14%, var(--border)) !important;
            color: var(--primary) !important;
        }

        .event-badge.webinaire { background: rgba(245, 158, 11, 0.14) !important; border-color: rgba(245, 158, 11, 0.20); color: #b54708 !important; }
        .event-badge.meetup { background: rgba(226, 30, 128, 0.10) !important; border-color: rgba(226, 30, 128, 0.16); color: #b42367 !important; }
        .event-badge.atelier { background: rgba(20, 128, 74, 0.12) !important; border-color: rgba(20, 128, 74, 0.18); color: #14804a !important; }

        [data-theme="dark"] .hero-stat,
        [data-theme="dark"] .event-badge.formation,
        [data-theme="dark"] .event-badge:not(.webinaire):not(.meetup):not(.atelier) {
            background: color-mix(in srgb, var(--primary-light, #1e1a3a) 70%, var(--white)) !important;
            border-color: color-mix(in srgb, var(--primary, #7c6fff) 20%, var(--border, #2a2840)) !important;
            color: #ddd6fe !important;
        }
    </style>
</head>
<body>
    <?php require_once dirname(__DIR__) . '/layout/header.php'; ?>
    
    <main class="container py-5 event-page-main">
        <!-- Hero Section -->
        <div class="hero-section">
            <div class="hero-content">
                <h1 data-i18n="discover_events">Découvrez les événements de la communauté</h1>
                <p data-i18n="events_desc">Formations, webinaires, meetups et ateliers pour les créateurs et les marques.</p>
                <div class="hero-stats">
                    <div class="hero-stat">
                        <span class="hero-stat-number"><?= count($evenements) ?></span>
                        <span class="hero-stat-label" data-i18n="events_available">Événements disponibles</span>
                    </div>
                    <div class="hero-stat">
                        <span class="hero-stat-number"><?= array_sum(array_map(function($e) { return $e->getNbInscrits(); }, $evenements)) ?></span>
                        <span class="hero-stat-label" data-i18n="participants">Participants</span>
                    </div>
                </div>
            </div>
            <div class="hero-image">
                <span style="font-size: 80px;">★ˎˊ˗</span>
            </div>
        </div>

        <div class="section-header">
            <div class="section-header-text">
                <h2 data-i18n="all_events">Tous les événements</h2>
                <p data-i18n="events_subtitle">Découvrez les événements disponibles près de chez vous ou en ligne</p>
            </div>
        </div>

        <div class="events-layout">
            <aside class="events-sidebar">
                <div class="filter-section">
                    <div class="filter-title">🔍 <span data-i18n="search_label">RECHERCHE</span></div>
                    <div class="search-box">
                        <input type="text" id="searchInput" data-i18n-placeholder="search_placeholder" placeholder="Rechercher un événement...">
                    </div>
                </div>
                <div class="filter-section">
                    <div class="filter-title">📂 <span data-i18n="type">TYPE</span></div>
                    <div class="filter-options">
                        <label class="filter-option"><input type="checkbox" value="formation" class="type-filter"> <span data-i18n="formation">Formation</span></label>
                        <label class="filter-option"><input type="checkbox" value="webinaire" class="type-filter"> <span data-i18n="webinaire">Webinaire</span></label>
                        <label class="filter-option"><input type="checkbox" value="meetup" class="type-filter"> <span data-i18n="meetup">Meetup</span></label>
                        <label class="filter-option"><input type="checkbox" value="atelier" class="type-filter"> <span data-i18n="atelier">Atelier</span></label>
                    </div>
                </div>
                <div class="filter-section">
                    <div class="filter-title">📍 <span data-i18n="location">LIEU</span></div>
                    <div class="chip-group">
                        <button class="chip active" onclick="filterChip(this, '')" data-lieu-filter="" data-i18n="all">Tous</button>
                        <button class="chip" onclick="filterChip(this, 'en_ligne')" data-lieu-filter="en_ligne" data-i18n="online">En ligne</button>
                        <button class="chip" onclick="filterChip(this, 'presentiel')" data-lieu-filter="presentiel" data-i18n="in_person">Présentiel</button>
                    </div>
                </div>
            </aside>

            <div class="events-content">
                <div class="events-header-bar">
                    <div class="events-count" id="eventsCount"><?= count($evenements) ?> événements</div>
                    <select id="sortSelect" class="sort-select">
                        <option value="date" data-i18n-opt="date_recent">Date (plus récent)</option>
                        <option value="date_asc" data-i18n-opt="date_oldest">Date (plus ancien)</option>
                        <option value="spots" data-i18n-opt="spots_available">Places disponibles</option>
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
        $forumHref = event_front_url('Controleur/forumC.php?action=voir&id=' . $forumId);
        $forumLink = '<a href="' . htmlspecialchars($forumHref) . '" class="btn-event-secondary mt-2">💬 <span data-i18n="access_forum">Accéder au forum</span></a>';
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
                                <div class="event-spots <?= $isFull ? 'full' : 'available' ?>"
                                     data-spots-left="<?= $spotsLeft ?>">
                                    <?= $isFull ? '❌ Complet' : '✅ ' . $spotsLeft . ' places restantes' ?>
                                </div>
                                <button onclick="ouvrirModalInscription(<?= $event->getId() ?>)" class="btn-event-primary">✨ <span data-i18n="register">S'inscrire</span></button>
                                <button onclick="voirDetail(<?= $event->getId() ?>)" class="btn-event-secondary">👁 <span data-i18n="view_details">Voir les détails</span></button>
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
        <div class="inscription-modal-card" style="background: var(--white); border-radius: 14px; width: 460px; max-width: 90%; overflow: hidden;">
            <div style="background: var(--primary); padding: 24px; text-align: center; color: white;">
                <h3>✨ <span data-i18n="registration">Inscription</span></h3>
                <p data-i18n="join_event">Rejoignez cet événement</p>
            </div>
            <div style="padding: 24px;">
                <div style="margin-bottom: 16px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 700;" data-i18n="full_name">Nom complet</label>
                    <input type="text" id="inscrireNom" class="form-control">
                </div>
                <div style="margin-bottom: 16px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 700;" data-i18n="email_address">Email</label>
                    <input type="email" id="inscrireEmail" class="form-control">
                </div>
                <div style="display: flex; gap: 12px;">
                    <button onclick="fermerModalInscription()" class="btn btn-outline-secondary flex-grow-1" data-i18n="cancel">Annuler</button>
                    <button onclick="confirmerInscription()" class="btn btn-primary flex-grow-1" data-i18n="confirm">Confirmer</button>
                </div>
            </div>
        </div>
    </div>

    <div id="toast" style="position: fixed; bottom: 28px; right: 28px; background: #0ea370; color: white; padding: 12px 24px; border-radius: 30px; display: none; z-index: 1070;"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php require __DIR__ . '/../layout/footer.php'; ?>
    <script src="<?= htmlspecialchars(event_front_url('Vue/FrontOffice/layout/front-header.js')) ?>"></script>
    <script>
        let currentEventId = null;
        const isAdminVisitor = <?= $isAdminVisitor ? 'true' : 'false' ?>;
        let currentLang = 'en';
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
                loading_error: 'Erreur de chargement',
                // Hero
                discover_events: 'Découvrez les événements de la communauté',
                events_desc: 'Formations, webinaires, meetups et ateliers pour les créateurs et les marques.',
                events_available: 'Événements disponibles',
                participants: 'Participants',
                // Page text
                all_events: 'Tous les événements',
                events_subtitle: 'Découvrez les événements disponibles près de chez vous ou en ligne',
                search_label: 'RECHERCHE',
                search_placeholder: 'Rechercher un événement...',
                type: 'TYPE',
                location: 'LIEU',
                all: 'Tous',
                online: 'En ligne',
                in_person: 'Présentiel',
                formation: 'Formation',
                webinaire: 'Webinaire',
                meetup: 'Meetup',
                atelier: 'Atelier',
                date_recent: 'Date (plus récent)',
                date_oldest: 'Date (plus ancien)',
                spots_available: 'Places disponibles',
                register: "S'inscrire",
                view_details: 'Voir les détails',
                access_forum: 'Accéder au forum',
                full: 'Complet',
                spots_left: 'places restantes',
                // Modal
                registration: 'Inscription',
                join_event: 'Rejoignez cet événement',
                full_name: 'Nom complet',
                email_address: 'Adresse email',
                cancel: 'Annuler',
                confirm: 'Confirmer',
                fill_fields: 'Veuillez remplir tous les champs',
                invalid_email: 'Email invalide',
                connection_error: 'Erreur de connexion au serveur',
                registration_progress: 'Inscription en cours...',
                events_count_suffix: 'événements'
            },
            en: {
                spots_remaining: 'spots remaining',
                complete: 'Full',
                waiting_list: 'Waiting list',
                register_now: 'Register now',
                loading_error: 'Loading error',
                // Hero
                discover_events: 'Discover community events',
                events_desc: 'Trainings, webinars, meetups and workshops for creators and brands.',
                events_available: 'Events available',
                participants: 'Participants',
                // Page text
                all_events: 'All events',
                events_subtitle: 'Discover events available near you or online',
                search_label: 'SEARCH',
                search_placeholder: 'Search for an event...',
                type: 'TYPE',
                location: 'LOCATION',
                all: 'All',
                online: 'Online',
                in_person: 'In person',
                formation: 'Training',
                webinaire: 'Webinar',
                meetup: 'Meetup',
                atelier: 'Workshop',
                date_recent: 'Date (newest)',
                date_oldest: 'Date (oldest)',
                spots_available: 'Available spots',
                register: 'Register',
                view_details: 'View details',
                access_forum: 'Open forum',
                full: 'Full',
                spots_left: 'spots left',
                // Modal
                registration: 'Registration',
                join_event: 'Join this event',
                full_name: 'Full name',
                email_address: 'Email address',
                cancel: 'Cancel',
                confirm: 'Confirm',
                fill_fields: 'Please fill in all fields',
                invalid_email: 'Invalid email',
                connection_error: 'Connection error',
                registration_progress: 'Registering...',
                events_count_suffix: 'events'
            }
        };

        function applyTranslation(lang) {
            const safe = (lang === 'en') ? 'en' : 'fr';
            currentLang = safe;

            const t = translations[safe];

            // data-i18n elements
            document.querySelectorAll('[data-i18n]').forEach(el => {
                const key = el.getAttribute('data-i18n');
                if (t[key] !== undefined) el.textContent = t[key];
            });

            // placeholders
            document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
                const key = el.getAttribute('data-i18n-placeholder');
                if (t[key] !== undefined) el.placeholder = t[key];
            });

            // sort <select> options via data-i18n-opt
            document.querySelectorAll('[data-i18n-opt]').forEach(el => {
                const key = el.getAttribute('data-i18n-opt');
                if (t[key] !== undefined) el.textContent = t[key];
            });

            // spots text on each card
            document.querySelectorAll('.event-spots[data-spots-left]').forEach(el => {
                const spots = parseInt(el.getAttribute('data-spots-left'), 10);
                if (spots <= 0) {
                    el.textContent = '❌ ' + t['full'];
                } else {
                    el.textContent = '✅ ' + spots + ' ' + t['spots_left'];
                }
            });

            // events count label
            const countEl = document.getElementById('eventsCount');
            if (countEl) {
                const num = countEl.textContent.match(/\d+/);
                if (num) countEl.textContent = num[0] + ' ' + t['events_count_suffix'];
            }

        }

        function showToast(message, isError = false) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.style.backgroundColor = isError ? '#f43f5e' : '#0ea370';
            toast.style.display = 'block';
            setTimeout(() => toast.style.display = 'none', 3000);
        }

        function ouvrirModalInscription(eventId) {
            if (isAdminVisitor) {
                showToast('Admins manage events from BackOffice.', true);
                return;
            }
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
                    const t = translations[currentLang];
                    document.getElementById('detailPlaces').textContent = placesRestantes > 0
                        ? '✅ ' + placesRestantes + ' ' + t['spots_remaining']
                        : '❌ ' + t['complete'];

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
                    btn.innerHTML = placesRestantes <= 0
                        ? '📋 ' + t['waiting_list']
                        : '✨ ' + t['register_now'];
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
            // Use data-lieu-filter attribute instead of text content so it works in both languages
            const lieuFilter = activeChip ? (activeChip.getAttribute('data-lieu-filter') || '') : '';
            
            const cards = document.querySelectorAll('.event-product-card');
            let visibleCount = 0;
            
            cards.forEach(card => {
                let show = true;
                const title = card.getAttribute('data-title');
                const type = card.getAttribute('data-type');
                const lieu = card.getAttribute('data-lieu');
                if (searchTerm && !title.includes(searchTerm)) show = false;
                if (selectedTypes.length > 0 && !selectedTypes.includes(type)) show = false;
                if (lieuFilter === 'en_ligne' && lieu !== 'en_ligne') show = false;
                if (lieuFilter === 'presentiel' && lieu !== 'presentiel') show = false;
                card.style.display = show ? '' : 'none';
                if (show) visibleCount++;
            });
            const t = translations[currentLang];
            document.getElementById('eventsCount').textContent = visibleCount + ' ' + t['events_count_suffix'];
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

        // ── Language toggle init ──────────────────────────────────────────────
        document.addEventListener('DOMContentLoaded', function() {
            currentLang = typeof window.cre8RegisterTranslations === 'function'
                ? window.cre8RegisterTranslations(translations)
                : (typeof window.cre8FrontReadLang === 'function' ? window.cre8FrontReadLang() : 'en');
            applyTranslation(currentLang);
            window.addEventListener('cre8:languagechange', function(event) {
                applyTranslation(event.detail && event.detail.lang ? event.detail.lang : currentLang);
            });
        });
    </script>
</body>
</html>
