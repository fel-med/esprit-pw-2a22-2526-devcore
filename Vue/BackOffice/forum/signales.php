<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages signalés - Admin Cre8Connect</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: #0d1117;
            color: #e6edf3;
        }

        .admin-container { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background: #161b22; border-right: 1px solid #30363d; position: fixed; height: 100vh; overflow-y: auto; }
        .sidebar-logo { padding: 24px 20px; border-bottom: 1px solid #30363d; display: flex; align-items: center; gap: 12px; }
        .logo-img { width: 36px; height: 36px; border-radius: 10px; object-fit: cover; }
        .logo-text { font-size: 1.2rem; font-weight: 700; background: linear-gradient(135deg, #58a6ff, #bc8cff); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .sidebar-nav { padding: 20px 0; }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 12px 20px; color: #8b949e; text-decoration: none; transition: all 0.2s; font-size: 0.85rem; }
        .nav-item:hover, .nav-item.active { background: #21262d; color: #58a6ff; }
        .main-content { margin-left: 260px; flex: 1; padding: 24px 32px; }
        .page-header { margin-bottom: 28px; }
        .page-header h1 { font-size: 1.5rem; font-weight: 700; }
        .page-header p { color: #8b949e; font-size: 0.85rem; margin-top: 4px; }

        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 32px; }
        .stat-card { background: #161b22; border: 1px solid #30363d; border-radius: 16px; padding: 20px; }
        .stat-icon { font-size: 1.8rem; margin-bottom: 12px; }
        .stat-value { font-size: 1.8rem; font-weight: 700; color: #f85149; }
        .stat-label { font-size: 0.75rem; color: #8b949e; margin-top: 4px; }

        .message-card { background: #161b22; border: 1px solid #30363d; border-radius: 16px; padding: 20px; margin-bottom: 16px; }
        .message-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid #30363d; }
        .message-forum { font-size: 0.8rem; color: #58a6ff; }
        .message-author { display: flex; align-items: center; gap: 12px; }
        .author-name { font-weight: 600; }
        .message-date { font-size: 0.7rem; color: #8b949e; }
        .message-content { color: #e6edf3; line-height: 1.6; margin-bottom: 16px; padding: 12px; background: #0d1117; border-radius: 12px; }
        .btn-delete { background: #da3633; color: white; border: none; padding: 8px 20px; border-radius: 8px; cursor: pointer; font-size: 0.8rem; }
        .btn-delete:hover { background: #f85149; }
        .empty-state { text-align: center; padding: 60px; background: #161b22; border-radius: 16px; }

        @media (max-width: 768px) { .sidebar { display: none; } .main-content { margin-left: 0; padding: 20px; } .stats-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<div class="admin-container">
    <aside class="sidebar">
        <div class="sidebar-logo">
            <img src="/ProjetWeb/Esprit-PW-2A22-2526-Devcore/Vue/public/images/logo.png" alt="Logo" class="logo-img">
            <span class="logo-text">Cre8Connect</span>
        </div>
        <div class="sidebar-nav">
            <a href="/ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/evenementC.php?action=admin" class="nav-item">📅 Événements</a>
            <a href="/ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/forumC.php?action=admin" class="nav-item">💬 Forums</a>
            <a href="/ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/forumC.php?action=signales" class="nav-item active">🚩 Messages signalés</a>
            <a href="/ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/forumC.php?action=statistiques" class="nav-item">📊 Statistiques</a>
        </div>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <div>
                <h1>🚩 Messages signalés</h1>
                <p>Modération des messages signalés par les utilisateurs</p>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">🚩</div>
                <div class="stat-value"><?= count($messages) ?></div>
                <div class="stat-label">Messages signalés</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⚠️</div>
                <div class="stat-value"><?= count(array_filter($messages, function($m) { return $m['signalement'] == 1; })) ?></div>
                <div class="stat-label">En attente</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🗑️</div>
                <div class="stat-value">0</div>
                <div class="stat-label">Supprimés ce mois</div>
            </div>
        </div>

        <?php if (empty($messages)): ?>
            <div class="empty-state">
                <div style="font-size: 3rem; margin-bottom: 12px;">✅</div>
                <h3>Aucun message signalé</h3>
                <p>Tous les messages sont conformes aux règles de la communauté</p>
            </div>
        <?php else: ?>
            <?php foreach ($messages as $msg): ?>
            <div class="message-card">
                <div class="message-header">
                    <div class="message-forum">📌 Forum : <?= htmlspecialchars($msg['titreForum']) ?></div>
                    <button class="btn-delete" onclick="supprimerMessage(<?= $msg['idMessage'] ?>)">🗑 Supprimer le message</button>
                </div>
                <div class="message-author">
                    <span class="author-name">👤 <?= htmlspecialchars($msg['nom_utilisateur']) ?></span>
                    <span class="message-date">📅 <?= date('d/m/Y H:i', strtotime($msg['dateMessage'])) ?></span>
                </div>
                <div class="message-content">
                    <?= nl2br(htmlspecialchars($msg['message'])) ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
</div>

<script>
    function supprimerMessage(id) {
        if (confirm('Supprimer ce message signalé ? Cette action est irréversible.')) {
            window.location.href = `/ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/forumC.php?action=supprimer_message&id=${id}`;
        }
    }
</script>

</body>
</html>