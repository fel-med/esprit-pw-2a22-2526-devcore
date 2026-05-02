<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forums - Cre8Connect</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700;9..144,800&display=swap" rel="stylesheet">
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

        body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text-main); min-height: 100vh; }

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
        .theme-toggle-btn { background: var(--bg); border: 1px solid var(--border); border-radius: 50%; width: 36px; height: 36px; cursor: pointer; font-size: 1.2rem; display: flex; align-items: center; justify-content: center; transition: all 0.3s; }
        .theme-toggle-btn:hover { transform: scale(1.05); background: var(--primary-light); }

        .hero { background: linear-gradient(135deg, #5b4fff 0%, #8b7cff 100%); color: white; text-align: center; padding: 48px 24px; margin-bottom: 32px; }
        .hero h1 { font-family: 'Fraunces', serif; font-size: 2.2rem; font-weight: 800; margin-bottom: 12px; }
        .hero p { font-size: 1rem; opacity: 0.9; max-width: 500px; margin: 0 auto; }

        .stats-container { max-width: 1200px; margin: -30px auto 32px; padding: 0 24px; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
        .stat-card { background: var(--white); border-radius: var(--radius); padding: 24px; text-align: center; box-shadow: var(--card-shadow); border: 1px solid var(--border); transition: all 0.2s; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: var(--card-shadow-hover); border-color: var(--primary-border); }
        .stat-icon { font-size: 2rem; margin-bottom: 12px; }
        .stat-value { font-family: 'Fraunces', serif; font-size: 2rem; font-weight: 800; color: var(--primary); }
        .stat-label { font-size: 0.8rem; color: var(--text-sub); margin-top: 4px; }

        .main-container { max-width: 1200px; margin: 0 auto; padding: 0 24px 80px; }

        .forum-card { background: var(--white); border-radius: var(--radius); margin-bottom: 20px; border: 1px solid var(--border); transition: all 0.25s; box-shadow: var(--card-shadow); overflow: hidden; }
        .forum-card:hover { transform: translateY(-3px); box-shadow: var(--card-shadow-hover); border-color: var(--primary-border); }
        .forum-card-inner { padding: 24px; display: flex; gap: 20px; }
        .forum-image { width: 80px; height: 80px; flex-shrink: 0; border-radius: 12px; overflow: hidden; background: linear-gradient(135deg, var(--primary-light), #ddddf8); display: flex; align-items: center; justify-content: center; }
        .forum-image img { width: 100%; height: 100%; object-fit: cover; }
        .forum-image .forum-icon { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 2rem; background: none; }
        .forum-content { flex: 1; }
        .forum-title { font-family: 'Fraunces', serif; font-size: 1.2rem; font-weight: 800; color: var(--text-main); margin-bottom: 8px; }
        .forum-meta { display: flex; flex-wrap: wrap; gap: 20px; font-size: 0.7rem; color: var(--text-sub); margin-bottom: 12px; }
        .forum-meta span { display: flex; align-items: center; gap: 6px; }
        .forum-sujet { background: var(--bg); padding: 14px; border-radius: var(--radius-sm); font-size: 0.85rem; color: var(--text-sub); margin-bottom: 16px; border-left: 3px solid var(--primary); }
        .forum-footer { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
        .forum-stats { display: flex; gap: 20px; font-size: 0.7rem; color: var(--text-sub); }
        .forum-stats span { display: flex; align-items: center; gap: 5px; }
        .btn-discuter { background: var(--primary); color: white; border: none; padding: 10px 24px; border-radius: 40px; font-size: 0.8rem; font-weight: 700; cursor: pointer; transition: all 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-discuter:hover { background: var(--primary-hover); transform: translateY(-1px); box-shadow: 0 4px 12px var(--primary-glow); }

        .empty-state { text-align: center; padding: 60px 24px; background: var(--white); border-radius: var(--radius); border: 1px solid var(--border); }
        .empty-state-icon { font-size: 4rem; margin-bottom: 16px; }
        .empty-state h3 { font-family: 'Fraunces', serif; font-size: 1.3rem; margin-bottom: 8px; }
        .empty-state p { color: var(--text-sub); }

        @media (max-width: 768px) {
            nav { padding: 0 20px; }
            .nav-links { display: none; }
            .hero h1 { font-size: 1.5rem; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
            .forum-card-inner { flex-direction: column; }
            .forum-footer { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>

<nav>
    <a href="/ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/evenementC.php" class="nav-logo">
        <img src="/ProjetWeb/Esprit-PW-2A22-2526-Devcore/Vue/public/images/logo.png" alt="Cre8Connect">
        <span class="nav-logo-text">Cre8Connect</span>
    </a>
    <ul class="nav-links">
        <li><a href="/ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/evenementC.php">Événements</a></li>
        <li><a href="#" class="active">Forums</a></li>
    </ul>
    <div class="nav-right">
        <button id="themeToggle" class="theme-toggle-btn">◑</button>
        <div class="nav-avatar">👤</div>
    </div>
</nav>

<section class="hero">
    <h1> Forums de discussion</h1>
    <p>Échangez avec la communauté, posez vos questions et partagez vos expériences</p>
</section>

<div class="stats-container">
    <div class="stats-grid">
        <div class="stat-card"><div class="stat-icon">🗂️</div><div class="stat-value"><?= count($forums) ?></div><div class="stat-label">Forums</div></div>
        <div class="stat-card"><div class="stat-icon">💬</div><div class="stat-value"><?= array_sum(array_column($forums, 'nb_messages')) ?></div><div class="stat-label">Messages</div></div>
        <div class="stat-card"><div class="stat-icon">👥</div><div class="stat-value"><?= count(array_unique(array_column($forums, 'idUtilisateur'))) ?></div><div class="stat-label">Participants</div></div>
        <div class="stat-card"><div class="stat-icon">🔥</div><div class="stat-value"><?= count($forums) ?></div><div class="stat-label">Discussions actives</div></div>
    </div>
</div>

<div class="main-container">
    <?php if (empty($forums)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">◑</div>
            <h3>Aucun forum pour le moment</h3>
            <p>Soyez le premier à créer une discussion !</p>
        </div>
    <?php else: ?>
        <?php foreach ($forums as $forum): ?>
        <div class="forum-card">
            <div class="forum-card-inner">
                <div class="forum-image">
                    <?php if (!empty($forum['image_evenement'])): ?>
                        <img src="/ProjetWeb/Esprit-PW-2A22-2526-Devcore/<?= $forum['image_evenement'] ?>" alt="<?= htmlspecialchars($forum['nom_evenement']) ?>">
                    <?php else: ?>
                        <div class="forum-icon">◑</div>
                    <?php endif; ?>
                </div>
                <div class="forum-content">
                    <div class="forum-title"><?= htmlspecialchars($forum['TitreForum'] ?? $forum['titreForum'] ?? 'Discussion') ?></div>
                    <div class="forum-meta">
                        <span>🎯 <?= htmlspecialchars($forum['nom_evenement']) ?></span>
                        <span>👤 <?= htmlspecialchars($forum['nom_utilisateur'] ?? 'Utilisateur') ?></span>
                        <span>📅 <?= date('d/m/Y', strtotime($forum['dateCreation'])) ?></span>
                    </div>
                    <div class="forum-sujet">
                        📌 <?= htmlspecialchars($forum['sujet']) ?>
                    </div>
                    <div class="forum-footer">
                        <div class="forum-stats">
                            <span>💬 <?= $forum['nb_messages'] ?? 0 ?> messages</span>
                            <span>👁️ <?= $forum['vues'] ?? 0 ?> vues</span>
                        </div>
                        <a href="/ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/forumC.php?action=voir&id=<?= $forum['idForum'] ?>" class="btn-discuter">
                            Rejoindre la discussion →
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
    function initTheme() {
        const savedTheme = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
            document.body.classList.add('dark-mode');
            const toggleBtn = document.getElementById('themeToggle');
            if (toggleBtn) toggleBtn.textContent = '◑';
        } else {
            document.body.classList.remove('dark-mode');
            const toggleBtn = document.getElementById('themeToggle');
            if (toggleBtn) toggleBtn.textContent = '◑';
        }
    }

    function toggleTheme() {
        if (document.body.classList.contains('dark-mode')) {
            document.body.classList.remove('dark-mode');
            localStorage.setItem('theme', 'light');
            document.getElementById('themeToggle').textContent = '◑';
        } else {
            document.body.classList.add('dark-mode');
            localStorage.setItem('theme', 'dark');
            document.getElementById('themeToggle').textContent = '◑';
        }
    }

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