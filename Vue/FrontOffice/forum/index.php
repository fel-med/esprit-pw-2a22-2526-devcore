<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forums - Cre8Connect</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);
            color: #0f172a;
            min-height: 100vh;
        }

        /* Header */
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 16px 32px;
            border-bottom: 1px solid rgba(226, 232, 240, 0.5);
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
            gap: 12px;
            font-size: 1.3rem;
            font-weight: 700;
            text-decoration: none;
        }

        .logo-img {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            object-fit: cover;
            box-shadow: 0 4px 10px rgba(79, 70, 229, 0.2);
        }

        .logo-text {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .nav-links {
            display: flex;
            gap: 32px;
        }

        .nav-links a {
            text-decoration: none;
            color: #64748b;
            font-weight: 500;
            transition: all 0.2s;
            padding: 8px 0;
        }

        .nav-links a:hover, .nav-links a.active {
            color: #4f46e5;
            border-bottom: 2px solid #4f46e5;
        }

        /* Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 32px;
        }

        /* Hero */
        .hero {
            text-align: center;
            margin-bottom: 48px;
        }

        .hero-badge {
            display: inline-block;
            background: #ede9fe;
            color: #4f46e5;
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .hero h1 {
            font-size: 2.2rem;
            font-weight: 800;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 12px;
        }

        .hero p {
            color: #64748b;
            font-size: 1rem;
            max-width: 500px;
            margin: 0 auto;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 48px;
        }

        .stat-card {
            background: white;
            border-radius: 24px;
            padding: 24px;
            border: 1px solid rgba(226, 232, 240, 0.6);
            transition: all 0.3s;
            text-align: center;
            box-shadow: 0 4px 6px -4px rgba(0,0,0,0.02);
        }

        .stat-card:hover {
            transform: translateY(-4px);
            border-color: #c7d2fe;
            box-shadow: 0 12px 24px -12px rgba(79, 70, 229, 0.15);
        }

        .stat-icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #ede9fe, #e0e7ff);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            margin: 0 auto 16px;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: #0f172a;
        }

        .stat-label {
            color: #64748b;
            font-size: 0.85rem;
            margin-top: 4px;
        }

        /* Forum Cards */
        .forum-card {
            background: white;
            border-radius: 24px;
            border: 1px solid rgba(226, 232, 240, 0.6);
            margin-bottom: 20px;
            transition: all 0.3s;
            overflow: hidden;
        }

        .forum-card:hover {
            transform: translateY(-2px);
            border-color: #c7d2fe;
            box-shadow: 0 12px 24px -12px rgba(0,0,0,0.1);
        }

        .forum-card-inner {
            padding: 24px;
            display: flex;
            align-items: flex-start;
            gap: 20px;
        }

        .forum-avatar {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
            box-shadow: 0 8px 16px -8px rgba(79, 70, 229, 0.3);
        }

        .forum-content {
            flex: 1;
        }

        .forum-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 6px;
        }

        .forum-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            font-size: 0.75rem;
            color: #64748b;
            margin-bottom: 12px;
        }

        .forum-meta span {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .forum-sujet {
            font-size: 0.85rem;
            color: #475569;
            margin-bottom: 14px;
            line-height: 1.5;
            padding: 12px;
            background: #f8fafc;
            border-radius: 14px;
        }

        .forum-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .forum-stats {
            display: flex;
            gap: 20px;
            font-size: 0.75rem;
            color: #64748b;
        }

        .forum-stats span {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-discuter {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 40px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
        }

        .btn-discuter:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.35);
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 32px;
            border: 1px solid #e2e8f0;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }
            .header {
                flex-direction: column;
                gap: 16px;
                padding: 16px 20px;
            }
            .forum-card-inner {
                flex-direction: column;
            }
            .forum-footer {
                flex-direction: column;
                align-items: flex-start;
            }
            .container {
                padding: 24px 20px;
            }
            .hero h1 {
                font-size: 1.6rem;
            }
        }
    </style>
</head>
<body>

<header class="header">
    <a href="/ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/forumC.php" class="logo">
        <img src="/ProjetWeb/Esprit-PW-2A22-2526-Devcore/Vue/public/images/logo.png" alt="Cre8Connect" class="logo-img">
        <span class="logo-text">Cre8Connect</span>
    </a>
    <nav class="nav-links">
        <a href="/ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/evenementC.php">Événements</a>
        <a href="/ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/forumC.php" class="active">Forums</a>
    </nav>
</header>   

<div class="container">
    <div class="hero">
        <div class="hero-badge">🗯️ Communauté active</div>
        <h1>Forums de discussion</h1>
        <p>Échangez avec la communauté, posez vos questions et partagez vos expériences</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📋</div>
            <div class="stat-value"><?= count($forums) ?></div>
            <div class="stat-label">Forums</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🗯️</div>
            <div class="stat-value"><?= array_sum(array_column($forums, 'nb_messages')) ?? 0 ?></div>
            <div class="stat-label">Messages</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-value"><?= count(array_unique(array_column($forums, 'idUtilisateur'))) ?></div>
            <div class="stat-label">Participants</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">〽️</div>
            <div class="stat-value"><?= count($forums) ?></div>
            <div class="stat-label">Discussions actives</div>
        </div>
    </div>

    <?php if (empty($forums)): ?>
        <div class="empty-state">
            <div style="font-size: 3rem; margin-bottom: 16px;">🗯️</div>
            <h3>Aucun forum pour le moment</h3>
            <p>Soyez le premier à créer une discussion !</p>
        </div>
    <?php else: ?>
        <?php foreach ($forums as $forum): ?>
        <div class="forum-card">
            <div class="forum-card-inner">
                <div class="forum-avatar">🗯️</div>
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

</body>
</html>