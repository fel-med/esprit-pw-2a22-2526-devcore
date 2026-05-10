<?php
if (!isset($forums) || !is_array($forums)) {
    $forums = [];
}
// Dynamic base path — works regardless of where the project is deployed
$BASE = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forums - Cre8Connect</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700;9..144,800&display=swap" rel="stylesheet">
    <script src="<?= $BASE ?>/Vue/public/js/translations.js"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary:        #5b4fff;
            --primary-light:  #ece9ff;
            --primary-hover:  #4438e0;
            --primary-glow:   rgba(91,79,255,0.15);
            --primary-border: rgba(91,79,255,0.2);
            --text-main:      #0f0e1a;
            --text-sub:       #6b6f80;
            --text-dim:       #a0a4b2;
            --border:         #e8e8f0;
            --bg:             #f4f4fb;
            --white:          #ffffff;
            --danger:         #f43f5e;
            --danger-light:   #fff1f3;
            --success:        #0ea370;
            --success-light:  #edfaf5;
            --warning:        #f59e0b;
            --warning-light:  #fffbeb;
            --radius:         16px;
            --radius-sm:      10px;
            --nav-h:          60px;
            --card-shadow:    0 1px 4px rgba(15,14,26,0.07), 0 4px 16px rgba(91,79,255,0.05);
            --card-shadow-hover: 0 8px 32px rgba(91,79,255,0.13);
        }

        body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text-main); min-height: 100vh; }

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
            --card-shadow:    0 1px 3px rgba(0,0,0,0.3), 0 4px 20px rgba(0,0,0,0.2);
            --card-shadow-hover: 0 12px 32px rgba(0,0,0,0.4);
        }

        /* ── NAV ── */
        nav {
            background: var(--white);
            border-bottom: 1px solid var(--border);
            padding: 0 48px;
            height: var(--nav-h);
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 200;
            box-shadow: 0 1px 0 var(--border);
        }
        .nav-logo { display: flex; align-items: center; gap: 8px; text-decoration: none; }
        .nav-logo img { width: 32px; height: 32px; object-fit: contain; border-radius: 8px; }
        .nav-logo-text { font-family: 'Fraunces', serif; font-size: 18px; font-weight: 800; color: var(--primary); letter-spacing: -0.5px; }
        .nav-links { display: flex; gap: 4px; list-style: none; }
        .nav-links a { text-decoration: none; color: var(--text-sub); font-size: 13.5px; font-weight: 600; padding: 6px 14px; border-radius: 8px; transition: all 0.18s; }
        .nav-links a:hover { background: var(--bg); color: var(--text-main); }
        .nav-links a.active { background: var(--primary); color: #fff; border-radius: 20px; }
        .nav-right { display: flex; align-items: center; gap: 10px; }
        .nav-avatar { width: 34px; height: 34px; border-radius: 50%; background: var(--primary); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; cursor: pointer; }
        .theme-toggle-btn, .lang-toggle-btn { background: var(--bg); border: 1px solid var(--border); border-radius: 20px; padding: 5px 12px; cursor: pointer; font-size: 0.8rem; font-weight: 600; color: var(--text-sub); transition: all 0.2s; display: flex; align-items: center; gap: 5px; height: 32px; }
        .theme-toggle-btn:hover, .lang-toggle-btn:hover { background: var(--primary-light); color: var(--primary); border-color: var(--primary-border); }

        /* ── HERO ── */
        .hero { background: var(--bg); padding: 28px 48px; }

        .hero-card {
            background: linear-gradient(140deg, #edeaff 0%, #eef0ff 50%, #e8ecff 100%);
            border: 1px solid #dddaf5;
            border-radius: 18px;
            padding: 32px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            max-width: 1400px;
            margin: 0 auto;
            overflow: hidden;
        }

        body.dark-mode .hero-card {
            background: linear-gradient(140deg, #1e1a3a 0%, #1a1d35 50%, #181c30 100%);
            border-color: #2e2a50;
        }

        .hero-content { flex: 1; }
        .hero-content h1 {
            font-family: 'Fraunces', serif;
            font-size: 1.75rem;
            font-weight: 800;
            color: #0f0e1a;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
            line-height: 1.2;
        }
        body.dark-mode .hero-content h1 { color: var(--text-main); }
        .hero-content p { font-size: 12.5px; color: var(--text-sub); max-width: 400px; line-height: 1.6; margin-bottom: 0; }

        .stats-bar { display: flex; gap: 20px; margin-top: 18px; flex-wrap: wrap; align-items: center; }
        .stat-item { display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; font-weight: 600; color: var(--text-sub); }
        .stat-num { font-family: 'Fraunces', serif; font-weight: 800; font-size: 14px; color: var(--primary); }
        .stat-label { color: var(--text-sub); font-weight: 600; }

        .hero-img { flex-shrink: 0; width: 160px; height: 160px; display: flex; align-items: center; justify-content: center; opacity: 0.45; }
        .hero-img img { width: 100%; height: 100%; object-fit: contain; }

        /* ── MAIN CONTAINER ── */
        .main-container { max-width: 1400px; margin: 0 auto; padding: 32px 48px 80px; }

        /* ── FORUM CARDS ── */
        .forum-card {
            background: var(--white);
            border-radius: var(--radius);
            margin-bottom: 16px;
            border: 1px solid var(--border);
            transition: transform .22s, box-shadow .22s, border-color .22s;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            position: relative;
        }
        .forum-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: var(--primary);
        }
        .forum-card:hover { transform: translateY(-3px); box-shadow: var(--card-shadow-hover); border-color: var(--primary-border); }
        .forum-card-inner { padding: 22px 24px; display: flex; gap: 20px; }

        .forum-image {
            width: 72px; height: 72px;
            flex-shrink: 0;
            border-radius: 12px;
            overflow: hidden;
            background: var(--primary-light);
            display: flex; align-items: center; justify-content: center;
        }
        .forum-image img { width: 100%; height: 100%; object-fit: cover; }
        .forum-icon { font-size: 1.8rem; }

        .forum-content { flex: 1; min-width: 0; }
        .forum-title { font-family: 'Fraunces', serif; font-size: 1.05rem; font-weight: 800; margin-bottom: 6px; color: var(--text-main); }
        .forum-meta { display: flex; flex-wrap: wrap; gap: 16px; font-size: 0.75rem; color: var(--text-sub); margin-bottom: 10px; }
        .forum-meta span { display: flex; align-items: center; gap: 5px; }
        .forum-sujet {
            background: var(--bg);
            padding: 12px 14px;
            border-radius: var(--radius-sm);
            font-size: 0.82rem;
            color: var(--text-sub);
            margin-bottom: 14px;
            border-left: 3px solid var(--primary);
            line-height: 1.5;
        }
        .forum-footer { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        .forum-stats { display: flex; gap: 16px; font-size: 0.75rem; color: var(--text-sub); }
        .forum-stats span { display: flex; align-items: center; gap: 5px; }

        .btn-discuter {
            background: var(--primary);
            color: white;
            border: none;
            padding: 9px 22px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.18s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 2px 8px var(--primary-glow);
        }
        .btn-discuter:hover { background: var(--primary-hover); transform: translateY(-1px); }

        /* ── EMPTY STATE ── */
        .empty-state { text-align: center; padding: 60px 24px; background: var(--white); border-radius: var(--radius); border: 1px solid var(--border); }
        .empty-state-icon { font-size: 4rem; margin-bottom: 16px; }
        .empty-state h3 { font-family: 'Fraunces', serif; font-size: 1.3rem; margin-bottom: 8px; }
        .empty-state p { color: var(--text-sub); }

        /* ── RESPONSIVE ── */
        @media (max-width: 768px) {
            nav { padding: 0 20px; }
            .nav-links { display: none; }
            .hero { padding: 16px; }
            .hero-card { padding: 22px 18px; }
            .hero-img { width: 70px; height: 70px; }
            .hero-content h1 { font-size: 1.3rem; }
            .main-container { padding: 20px 16px; }
            .forum-card-inner { flex-direction: column; }
            .forum-footer { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>

<nav>
    <a href="<?= $BASE ?>/Controleur/evenementC.php" class="nav-logo">
        <img src="<?= $BASE ?>/Vue/public/images/logo.png" alt="Cre8Connect">
        <span class="nav-logo-text">Cre8Connect</span>
    </a>
    <ul class="nav-links">
        <li><a href="<?= $BASE ?>/Controleur/evenementC.php" data-i18n="events">Événements</a></li>
        <li><a href="#" class="active" data-i18n="forum">Forums</a></li>
    </ul>
    <div class="nav-right">
        <button id="languageToggle" class="lang-toggle-btn" onclick="toggleLanguage()">🇫🇷 FR</button>
        <button id="themeToggle" class="theme-toggle-btn">◑ Dark mode</button>
        <div class="nav-avatar">👤</div>
    </div>
</nav>

<section class="hero">
    <div class="hero-card">
        <div class="hero-content">
            <h1 data-i18n="forum_discussions">💬 Forums de discussion</h1>
            <p data-i18n="forum_desc">Échangez avec la communauté, posez vos questions et partagez vos expériences.</p>
            <div class="stats-bar">
                <div class="stat-item">
                    <span class="stat-num"><?= count($forums) ?></span>
                    <span class="stat-label" data-i18n="forums">Forums actifs</span>
                </div>
                <div class="stat-item">
                    <span class="stat-num"><?= array_sum(array_column($forums, 'nb_messages')) ?></span>
                    <span class="stat-label" data-i18n="messages">Messages</span>
                </div>
            </div>
        </div>
        <div class="hero-img">
            <span style="font-size:6rem;opacity:0.35;">💬</span>
        </div>
    </div>
</section>

<div class="main-container">
    <?php if (empty($forums)): ?>
        <div class="empty-state"><div class="empty-state-icon"></div><h3 data-i18n="no_messages">Aucun forum pour le moment</h3><p data-i18n="be_first">Soyez le premier à créer une discussion !</p></div>
    <?php else: ?>
        <?php foreach ($forums as $forum): ?>
        <div class="forum-card">
            <div class="forum-card-inner">
                <div class="forum-image">
                    <?php if (!empty($forum['image_evenement'])): ?>
                        <img src="<?= $BASE ?>/<?= $forum['image_evenement'] ?>" alt="<?= htmlspecialchars($forum['nom_evenement']) ?>">
                    <?php else: ?>
                        <div class="forum-icon"></div>
                    <?php endif; ?>
                </div>
                <div class="forum-content">
                    <div class="forum-title"><?= htmlspecialchars($forum['TitreForum'] ?? $forum['titreForum'] ?? 'Discussion') ?></div>
                    <div class="forum-meta">
                        <span>🎯 <?= htmlspecialchars($forum['nom_evenement']) ?></span>
                        <span>👤 <?= htmlspecialchars($forum['nom_utilisateur'] ?? 'Utilisateur') ?></span>
                        <span>📅 <?= date('d/m/Y', strtotime($forum['dateCreation'])) ?></span>
                    </div>
                    <div class="forum-sujet">📌 <?= htmlspecialchars($forum['sujet']) ?></div>
                    <div class="forum-footer">
                        <div class="forum-stats"><span>💬 <?= $forum['nb_messages'] ?? 0 ?> <?= ($forum['nb_messages'] ?? 0) > 1 ? 'messages' : 'message' ?></span><span>👁️ <?= $forum['vues'] ?? 0 ?> <span data-i18n="views">vues</span></span></div>
                        <a href="<?= $BASE ?>/Controleur/forumC.php?action=voir&id=<?= $forum['idForum'] ?>" class="btn-discuter" data-i18n="join_discussion">Rejoindre la discussion →</a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
    function initTheme() {
        const saved = localStorage.getItem('theme');
        if (saved === 'dark') { document.body.classList.add('dark-mode'); document.getElementById('themeToggle').textContent = '◑ Light mode'; }
        else { document.body.classList.remove('dark-mode'); document.getElementById('themeToggle').textContent = '◑ Dark mode'; }
    }
    function toggleTheme() {
        if (document.body.classList.contains('dark-mode')) { document.body.classList.remove('dark-mode'); localStorage.setItem('theme', 'light'); document.getElementById('themeToggle').textContent = '◑ Dark mode'; }
        else { document.body.classList.add('dark-mode'); localStorage.setItem('theme', 'dark'); document.getElementById('themeToggle').textContent = '◑ Light mode'; }
    }
    document.addEventListener('DOMContentLoaded', function() { initTheme(); document.getElementById('themeToggle').addEventListener('click', toggleTheme); initLanguage(); });
</script>
</body>
</html>