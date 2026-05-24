<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../layout/avatar_helper.php';

$scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
// Strip from /Controleur/ or /Vue/ to get the project root — same logic as events page
if (($pos = strpos($scriptPath, '/Vue/')) !== false) {
    $BASE = substr($scriptPath, 0, $pos);
} elseif (($pos = strpos($scriptPath, '/Controleur/')) !== false) {
    $BASE = substr($scriptPath, 0, $pos);
} else {
    $BASE = rtrim(dirname(dirname($scriptPath)), '/');
}
$BASE = rtrim($BASE, '/');
$frontActive = 'events';

if (!isset($forums) || !is_array($forums)) {
    require_once __DIR__ . '/../../../config.php';
    try {
        $pdo = config::getConnexion();
        $stmt = $pdo->query("
            SELECT f.*, e.TitreFormation as nom_evenement, e.image as image_evenement,
                   u.nom as nom_utilisateur,
                   (SELECT COUNT(*) FROM forum_messages WHERE idForum = f.idForum) as nb_messages
            FROM forum f
            LEFT JOIN evenement e ON f.idFormation = e.idFormation
            LEFT JOIN utilisateur u ON f.idUtilisateur = u.id
            WHERE f.est_actif = 1
            ORDER BY f.dateCreation DESC
        ");
        $forums = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $forums = [];
    }
}

$totalMessages = array_sum(array_column($forums, 'nb_messages'));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/../layout/front-theme-bootstrap.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forums - Cre8Connect</title>
    <link rel="stylesheet" href="<?= $BASE ?>/Vue/FrontOffice/css/frontoffice.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= $BASE ?>/Vue/FrontOffice/layout/front-header.css">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= $BASE ?>/Vue/public/images/favicon-16.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $BASE ?>/Vue/public/images/favicon-32.png">
    <link rel="shortcut icon" type="image/png" href="<?= $BASE ?>/Vue/public/images/favicon-32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= $BASE ?>/Vue/public/images/apple-touch-icon.png">
    <style>
        /* Map to front-header.css tokens (avoids undefined --text-main / --text-dim) */
        :root {
            --text-main: var(--text);
            --text-dim: var(--text-sub);
        }
        [data-theme="dark"] {
            --text-main: var(--text);
            --text-dim: var(--text-sub);
        }
        body {
            background-color: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ── HERO ── */
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
        .hero-stats { display: flex; gap: 32px; }
        .hero-stat-number { font-size: 28px; font-weight: 800; color: var(--primary); display: block; }
        .hero-stat-label  { font-size: 13px; color: var(--text-sub); }
        .hero-image { font-size: 80px; opacity: 0.45; flex-shrink: 0; }

        /* ── SECTION HEADER ── */
        .section-header {
            border-bottom: 1px solid var(--border);
            padding-bottom: 16px;
            margin-bottom: 24px;
        }
        .section-header h2 { font-size: 20px; font-weight: 700; margin-bottom: 4px; color: var(--text-main); }
        .section-header p  { font-size: 14px; color: var(--text-sub); }

        /* ── LAYOUT ── */
        .forums-layout {
            display: flex;
            gap: 32px;
        }
        .forums-sidebar {
            width: 280px;
            flex-shrink: 0;
        }
        .forums-sidebar .filter-section {
            background: var(--white);
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid var(--border);
        }
        .forums-sidebar .filter-title {
            font-size: 12px;
            font-weight: 700;
            color: var(--text-dim);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 16px;
        }
        .forums-sidebar .search-box {
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 10px 14px;
        }
        .forums-sidebar .search-box input {
            border: none;
            outline: none;
            background: transparent;
            width: 100%;
            color: var(--text-main);
            font-size: 14px;
        }

        /* ── CONTENT ── */
        .forums-content { flex: 1; min-width: 0; }
        .forums-header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }
        .forums-count { font-size: 14px; color: var(--text-sub); }

        /* ── FORUM CARDS GRID ── */
        .forums-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 24px;
        }
        .forum-card {
            background: var(--white);
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid var(--border);
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
        }
        .forum-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: var(--primary);
            border-radius: 14px 14px 0 0;
        }
        .forum-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 28px rgba(91,79,255,0.13);
            border-color: rgba(91,79,255,0.2);
        }
        .forum-card-image {
            height: 120px;
            background: var(--bg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            border-bottom: 1px solid var(--border);
            overflow: hidden;
        }
        .forum-card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .forum-card-content { padding: 20px; }
        .forum-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 12px;
            background: var(--primary-light);
            color: var(--primary);
        }
        .forum-title {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--text-main);
            line-height: 1.3;
        }
        .forum-event {
            font-size: 13px;
            color: var(--primary);
            margin-bottom: 10px;
        }
        .forum-sujet {
            font-size: 13px;
            color: var(--text-sub);
            line-height: 1.5;
            padding: 10px 12px;
            background: var(--bg);
            border-radius: 10px;
            margin-bottom: 14px;
            border-left: 3px solid var(--primary);
        }
        .forum-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            font-size: 12px;
            color: var(--text-sub);
            padding: 12px 0;
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
            margin-bottom: 14px;
        }
        .btn-forum-join {
            display: block;
            width: 100%;
            padding: 11px;
            background: var(--primary);
            color: white !important;
            text-align: center;
            text-decoration: none !important;
            border-radius: 12px;
            font-weight: 700;
            font-size: 14px;
            transition: background 0.2s, transform 0.15s;
            box-shadow: 0 2px 8px rgba(91,79,255,0.2);
        }
        .btn-forum-join:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
        }

        /* ── EMPTY STATE ── */
        .empty-state {
            text-align: center;
            padding: 60px 24px;
            background: var(--white);
            border-radius: 14px;
            border: 1px solid var(--border);
        }
        .empty-state-icon { font-size: 4rem; margin-bottom: 16px; }
        .empty-state h3 { font-size: 20px; font-weight: 700; margin-bottom: 8px; color: var(--text-main); }
        .empty-state p { color: var(--text-sub); }

        /* ── LANGUAGE TOGGLE ── */
        /* ── SECTION HEADER flex row ── */
        .section-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }
        .section-header-text h2 { font-size: 20px; font-weight: 700; margin-bottom: 4px; color: var(--text-main); }
        .section-header-text p  { font-size: 14px; color: var(--text-sub); }

        /* ── RESPONSIVE ── */
        @media (max-width: 1024px) {
            .forums-layout { flex-direction: column; }
            .forums-sidebar { width: 100%; }
        }
        @media (max-width: 768px) {
            .hero-section { flex-direction: column; text-align: center; padding: 24px; }
            .hero-stats { justify-content: center; }
            .hero-image { margin-top: 20px; }
            .forums-grid { grid-template-columns: 1fr; }
        }

        /* Shared FrontOffice visual bridge for forum index. */
        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg) !important;
            color: var(--text) !important;
        }

        .hero-content h1,
        .section-header h2,
        .section-header-text h2,
        .forum-title,
        .empty-state h3 {
            font-family: 'Fraunces', serif;
            color: var(--text) !important;
            letter-spacing: 0;
        }

        .hero-content p,
        .section-header p,
        .section-header-text p,
        .forum-sujet,
        .forum-meta,
        .forums-count,
        .empty-state p {
            color: var(--text-sub) !important;
        }

        .hero-section,
        .forum-card,
        .forums-sidebar .filter-section,
        .empty-state {
            background: var(--white) !important;
            border: 1px solid var(--border) !important;
            border-radius: 14px !important;
            color: var(--text) !important;
            box-shadow: 0 12px 32px rgba(15, 14, 26, 0.06);
        }

        .forum-card::before {
            height: 2px;
            opacity: 0.45;
            border-radius: 14px 14px 0 0;
        }

        .forum-card:hover {
            border-color: color-mix(in srgb, var(--primary, #5b4fff) 24%, var(--border, #ebebf2));
            box-shadow: 0 18px 42px rgba(91, 79, 255, 0.12);
        }

        .forum-card-image,
        .forums-sidebar .search-box,
        .forum-sujet {
            background: var(--bg) !important;
            border-color: var(--border) !important;
        }

        .forums-sidebar .search-box,
        .forums-sidebar .search-box input {
            color: var(--text) !important;
            font-family: 'DM Sans', sans-serif;
        }

        .forums-sidebar .search-box:focus-within {
            border-color: var(--primary) !important;
            box-shadow: 0 0 0 3px var(--primary-glow, rgba(91, 79, 255, 0.15));
        }

        .btn-forum-join {
            background: var(--primary) !important;
            border-radius: 10px !important;
            color: #ffffff !important;
            font-family: 'DM Sans', sans-serif;
            box-shadow: 0 3px 10px var(--primary-glow, rgba(91, 79, 255, 0.15));
        }

        .btn-forum-join:hover {
            background: var(--primary-hover, var(--primary)) !important;
        }

        [data-theme="dark"] .hero-section,
        [data-theme="dark"] .forum-card,
        [data-theme="dark"] .forums-sidebar .filter-section,
        [data-theme="dark"] .empty-state {
            background: var(--white) !important;
            border-color: var(--border) !important;
            color: var(--text) !important;
            box-shadow: 0 18px 42px rgba(0, 0, 0, 0.22);
        }

        /* Marketplace body composition for forum index. */
        main.container {
            max-width: 1220px;
            flex: 1 0 auto;
        }

        body > .cre8-front-footer {
            flex-shrink: 0;
            margin-top: auto;
        }

        .hero-section {
            position: relative;
            overflow: hidden;
            padding: clamp(1.5rem, 3vw, 2rem) !important;
            border-radius: 22px !important;
            background:
                radial-gradient(circle at 88% 12%, rgba(124, 111, 255, 0.20), transparent 12rem),
                radial-gradient(circle at 10% 0%, rgba(255, 255, 255, 0.88), transparent 15rem),
                linear-gradient(135deg, rgba(236, 233, 255, 0.90), rgba(255, 255, 255, 0.92)) !important;
            border-color: rgba(91, 79, 255, 0.14) !important;
            box-shadow: 0 18px 44px rgba(91, 79, 255, 0.10) !important;
        }

        .hero-content h1 {
            font-size: clamp(2rem, 4vw, 3rem);
            line-height: 1.04;
        }

        .hero-content p {
            max-width: 660px;
            margin-bottom: 1rem;
        }

        .hero-stats {
            gap: 0.65rem;
            flex-wrap: wrap;
        }

        .hero-stats > div {
            min-width: 120px;
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
            display: grid;
            place-items: center;
            width: clamp(88px, 12vw, 124px);
            height: clamp(88px, 12vw, 124px);
            border-radius: 28px;
            background: rgba(255, 255, 255, 0.50);
            box-shadow: inset 0 0 0 1px rgba(91, 79, 255, 0.10);
            font-size: clamp(2.9rem, 7vw, 4.5rem);
            opacity: 1;
        }

        .forums-layout {
            gap: 1.15rem;
        }

        .forums-sidebar {
            width: min(260px, 100%);
        }

        .forums-sidebar .filter-section {
            padding: 0.9rem !important;
            border-radius: 18px !important;
            background: color-mix(in srgb, var(--white) 84%, var(--primary-light, #ece9ff)) !important;
            box-shadow: 0 10px 26px rgba(91, 79, 255, 0.07) !important;
        }

        .forums-sidebar .filter-title {
            margin-bottom: 0.7rem;
            color: var(--text-sub);
            letter-spacing: 0;
            text-transform: none;
        }

        .forums-grid {
            gap: 1rem;
        }

        .forum-card {
            border-radius: 18px !important;
            box-shadow: 0 14px 34px rgba(91, 79, 255, 0.07) !important;
        }

        .forum-card::before {
            height: 0 !important;
            opacity: 0 !important;
        }

        .forum-card-content {
            padding: 1rem;
        }

        [data-theme="dark"] .hero-section {
            background:
                radial-gradient(circle at 88% 12%, rgba(124, 111, 255, 0.18), transparent 12rem),
                linear-gradient(135deg, color-mix(in srgb, var(--primary-light, #1e1a3a) 52%, var(--white)), var(--white)) !important;
            border-color: color-mix(in srgb, var(--primary, #7c6fff) 28%, var(--border, #2a2840)) !important;
        }

        [data-theme="dark"] .hero-stats > div,
        [data-theme="dark"] .hero-image,
        [data-theme="dark"] .forums-sidebar .filter-section {
            background: color-mix(in srgb, var(--white) 82%, var(--primary-light, #1e1a3a)) !important;
            border-color: var(--border) !important;
        }

        /* Unified FrontOffice indicators. */
        .hero-stats {
            gap: 0.55rem !important;
            align-items: center;
        }

        .hero-stats > div {
            min-width: auto !important;
            display: inline-flex;
            align-items: center;
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

        .forum-badge {
            min-height: 1.55rem;
            padding: 0.22rem 0.62rem !important;
            border-radius: 999px !important;
            border: 1px solid color-mix(in srgb, var(--primary, #5b4fff) 14%, var(--border, #ebebf2));
            background: var(--primary-light, #ece9ff) !important;
            color: var(--primary, #5b4fff) !important;
            font-size: 0.72rem !important;
            font-weight: 700 !important;
            letter-spacing: 0 !important;
        }

        [data-theme="dark"] .hero-stats > div,
        [data-theme="dark"] .forum-badge {
            background: color-mix(in srgb, var(--primary-light, #1e1a3a) 70%, var(--white)) !important;
            border-color: color-mix(in srgb, var(--primary, #7c6fff) 20%, var(--border, #2a2840)) !important;
            color: #ddd6fe !important;
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../layout/header.php'; ?>

    <main class="container py-5">

        <!-- Hero -->
        <div class="hero-section">
            <div class="hero-content">
                <h1>💬 <span data-i18n="forum_discussions">Forums de discussion</span></h1>
                <p data-i18n="forum_desc">Échangez avec la communauté, posez vos questions et partagez vos expériences.</p>
                <div class="hero-stats">
                    <div>
                        <span class="hero-stat-number"><?= count($forums) ?></span>
                        <span class="hero-stat-label" data-i18n="forums">Forums actifs</span>
                    </div>
                    <div>
                        <span class="hero-stat-number"><?= $totalMessages ?></span>
                        <span class="hero-stat-label" data-i18n="messages">Messages</span>
                    </div>
                </div>
            </div>
            <div class="hero-image">💬</div>
        </div>

        <!-- Section header -->
        <div class="section-header">
            <div class="section-header-text">
                <h2 data-i18n="all_forums">Tous les forums</h2>
                <p data-i18n="forums_subtitle">Participez aux discussions des événements qui vous intéressent</p>
            </div>
        </div>

        <!-- Layout: sidebar + grid -->
        <div class="forums-layout">

            <!-- Sidebar -->
            <aside class="forums-sidebar">
                <div class="filter-section">
                    <div class="filter-title">🔍 <span data-i18n="search_label">Recherche</span></div>
                    <div class="search-box">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" id="forumSearch" data-i18n-placeholder="search_forum" placeholder="Rechercher un forum..." oninput="filterForums()">
                    </div>
                </div>
            </aside>

            <!-- Content -->
            <div class="forums-content">
                <div class="forums-header-bar">
                    <div class="forums-count" id="forumsCount"><?= count($forums) ?> forums</div>
                </div>

                <?php if (empty($forums)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">💬</div>
                        <h3>Aucun forum pour le moment</h3>
                        <p>Les forums apparaissent automatiquement le jour des événements.</p>
                    </div>
                <?php else: ?>
                    <div class="forums-grid" id="forumsGrid">
                        <?php foreach ($forums as $forum): ?>
                            <div class="forum-card" data-title="<?= strtolower(htmlspecialchars($forum['TitreForum'] ?? '')) ?>">
                                <div class="forum-card-image">
                                    <?php 
                                    $imgPath = $forum['image_evenement'] ?? '';
                                    $fullSrc = $BASE . '/' . ltrim($imgPath, '/');
                                    ?>
                                    <?php if (!empty($imgPath)): ?>
                                        <img src="<?= htmlspecialchars($fullSrc) ?>"
                                             alt="<?= htmlspecialchars($forum['TitreForum'] ?? '') ?>"
                                             onerror="this.style.display='none';this.parentElement.innerHTML='💬';">
                                    <?php else: ?>
                                        💬
                                    <?php endif; ?>
                                </div>
                                <div class="forum-card-content">
                                    <span class="forum-badge">💬 Forum</span>
                                    <div class="forum-title"><?= htmlspecialchars($forum['TitreForum'] ?? 'Discussion') ?></div>
                                    <div class="forum-event">🎯 <?= htmlspecialchars($forum['nom_evenement'] ?? 'Événement') ?></div>
                                    <div class="forum-sujet">📌 <?= htmlspecialchars(mb_substr($forum['sujet'] ?? '', 0, 100)) ?>...</div>
                                    <div class="forum-meta">
                                        <div style="display:inline-flex;align-items:center;gap:.35rem;">
                                            <?= cre8_render_avatar($forum['idUtilisateur'] ?? 0, (string)($forum['nom_utilisateur'] ?? 'Admin'), 'cre8-avatar-sm') ?>
                                            <?= htmlspecialchars($forum['nom_utilisateur'] ?? 'Admin') ?>
                                        </div>
                                        <span>📅 <?= date('d/m/Y', strtotime($forum['dateCreation'] ?? 'now')) ?></span>
                                        <span>💬 <?= (int)($forum['nb_messages'] ?? 0) ?> messages</span>
                                        <span>👁️ <?= (int)($forum['vues'] ?? 0) ?> vues</span>
                                    </div>
                                    <a href="<?= $BASE ?>/Controleur/forumC.php?action=voir&id=<?= (int)$forum['idForum'] ?>"
                                       class="btn-forum-join">
                                        <span data-i18n="join_discussion">Rejoindre la discussion →</span>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php require __DIR__ . '/../layout/footer.php'; ?>
    <script src="<?= $BASE ?>/Vue/FrontOffice/layout/front-header.js"></script>
    <script>
        // ── Translations ─────────────────────────────────────────────────────
        const forumTranslations = {
            fr: {
                forum_discussions: 'Forums de discussion',
                forum_desc: 'Échangez avec la communauté, posez vos questions et partagez vos expériences.',
                forums: 'Forums actifs',
                messages: 'Messages',
                all_forums: 'Tous les forums',
                forums_subtitle: 'Participez aux discussions des événements qui vous intéressent',
                search_label: 'Recherche',
                search_forum: 'Rechercher un forum...',
                join_discussion: 'Rejoindre la discussion →'
            },
            en: {
                forum_discussions: 'Discussion Forums',
                forum_desc: 'Exchange with the community, ask questions and share your experiences.',
                forums: 'Active forums',
                messages: 'Messages',
                all_forums: 'All forums',
                forums_subtitle: 'Join discussions for events that interest you',
                search_label: 'Search',
                search_forum: 'Search for a forum...',
                join_discussion: 'Join the discussion →'
            }
        };

        function applyForumTranslation(lang) {
            const safe = (lang === 'en') ? 'en' : 'fr';
            const t = forumTranslations[safe];

            document.querySelectorAll('[data-i18n]').forEach(el => {
                const key = el.getAttribute('data-i18n');
                if (t[key] !== undefined) el.textContent = t[key];
            });
            document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
                const key = el.getAttribute('data-i18n-placeholder');
                if (t[key] !== undefined) el.placeholder = t[key];
            });
        }

        // ── Filter ────────────────────────────────────────────────────────────
        function filterForums() {
            const q = document.getElementById('forumSearch').value.toLowerCase();
            const cards = document.querySelectorAll('#forumsGrid .forum-card');
            let visible = 0;
            cards.forEach(card => {
                const title = card.getAttribute('data-title') || '';
                const show = !q || title.includes(q);
                card.style.display = show ? '' : 'none';
                if (show) visible++;
            });
            document.getElementById('forumsCount').textContent = visible + ' forum' + (visible !== 1 ? 's' : '');
        }

        // ── Init ──────────────────────────────────────────────────────────────
        document.addEventListener('DOMContentLoaded', function() {
            const savedLang = typeof window.cre8RegisterTranslations === 'function'
                ? window.cre8RegisterTranslations(forumTranslations)
                : (typeof window.cre8FrontReadLang === 'function' ? window.cre8FrontReadLang() : 'en');
            applyForumTranslation(savedLang);
            window.addEventListener('cre8:languagechange', function(event) {
                applyForumTranslation(event.detail && event.detail.lang ? event.detail.lang : savedLang);
            });
        });
    </script>
</body>
</html>

