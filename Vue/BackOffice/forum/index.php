<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Forums - Admin Cre8Connect</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: #0d1117;
            color: #e6edf3;
        }

        .admin-container { display: flex; min-height: 100vh; }
        
        .sidebar {
            width: 260px;
            background: #161b22;
            border-right: 1px solid #30363d;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-logo {
            padding: 24px 20px;
            border-bottom: 1px solid #30363d;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-img {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            object-fit: cover;
        }

        .logo-text {
            font-size: 1.2rem;
            font-weight: 700;
            background: linear-gradient(135deg, #58a6ff, #bc8cff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .sidebar-nav { padding: 20px 0; }
        
        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: #8b949e;
            text-decoration: none;
            transition: all 0.2s;
            font-size: 0.9rem;
        }

        .nav-item:hover, .nav-item.active {
            background: #21262d;
            color: #58a6ff;
        }

        .main-content {
            margin-left: 260px;
            flex: 1;
            padding: 24px 32px;
        }

        .page-header { margin-bottom: 28px; }
        .page-header h1 { font-size: 1.5rem; font-weight: 700; }
        .page-header p { color: #8b949e; font-size: 0.85rem; margin-top: 4px; }

        /* Onglets */
        .tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 28px;
            border-bottom: 1px solid #30363d;
            padding-bottom: 0;
        }

        .tab-btn {
            padding: 12px 24px;
            background: transparent;
            border: none;
            color: #8b949e;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            border-radius: 8px 8px 0 0;
        }

        .tab-btn:hover {
            color: #58a6ff;
            background: #21262d;
        }

        .tab-btn.active {
            color: #58a6ff;
            border-bottom: 2px solid #58a6ff;
        }

        .tab-content {
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: #161b22;
            border: 1px solid #30363d;
            border-radius: 16px;
            padding: 20px;
        }

        .stat-card:hover { border-color: #58a6ff; }
        .stat-icon { font-size: 1.8rem; margin-bottom: 12px; }
        .stat-value { font-size: 1.8rem; font-weight: 700; color: #58a6ff; }
        .stat-label { font-size: 0.75rem; color: #8b949e; margin-top: 4px; }

        /* Table */
        .table-container {
            background: #161b22;
            border: 1px solid #30363d;
            border-radius: 16px;
            overflow: hidden;
        }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 14px 20px; background: #21262d; color: #8b949e; font-size: 0.75rem; font-weight: 600; }
        td { padding: 16px 20px; border-bottom: 1px solid #30363d; font-size: 0.85rem; }
        tr:hover { background: #1a1f2e; }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .badge-active { background: #23863620; color: #3fb950; border: 1px solid #23863640; }
        .badge-signale { background: #da363320; color: #f85149; border: 1px solid #da363340; }

        .btn-delete {
            background: #da3633;
            color: white;
            border: none;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 0.7rem;
            cursor: pointer;
        }
        .btn-delete:hover { background: #f85149; }

        .btn-view {
            background: #21262d;
            color: #58a6ff;
            border: 1px solid #30363d;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 0.7rem;
            text-decoration: none;
            margin-right: 8px;
            display: inline-block;
        }
        .btn-view:hover { background: #30363d; }

        .message-card {
            background: #161b22;
            border: 1px solid #30363d;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 16px;
        }
        .message-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid #30363d;
        }
        .message-content {
            color: #e6edf3;
            line-height: 1.6;
            padding: 12px;
            background: #0d1117;
            border-radius: 12px;
        }

        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; padding: 20px; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .tabs { flex-wrap: wrap; }
        }
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
            <a href="/ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/forumC.php?action=admin" class="nav-item active">💬 Forums</a>
        </div>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <div>
                <h1>💬 Gestion des Forums</h1>
                <p>Supervision, modération et statistiques de la communauté</p>
            </div>
        </div>

        <!-- Onglets -->
        <div class="tabs">
            <button class="tab-btn active" onclick="showTab('forums')">📋 Forums</button>
            <button class="tab-btn" onclick="showTab('signales')">🚩 Messages signalés (<?= $total_signales ?? 0 ?>)</button>
            <button class="tab-btn" onclick="showTab('stats')">📊 Statistiques</button>
        </div>

        <!-- TAB 1 : FORUMS -->
        <div id="tab-forums" class="tab-content active">
            <div class="stats-grid">
                <div class="stat-card"><div class="stat-icon">🗂️</div><div class="stat-value"><?= $stats['total_forums'] ?? 0 ?></div><div class="stat-label">Total forums</div></div>
                <div class="stat-card"><div class="stat-icon">💬</div><div class="stat-value"><?= $stats['total_messages'] ?? 0 ?></div><div class="stat-label">Total messages</div></div>
                <div class="stat-card"><div class="stat-icon">👥</div><div class="stat-value"><?= $stats['total_participants'] ?? 0 ?></div><div class="stat-label">Participants</div></div>
                <div class="stat-card"><div class="stat-icon">🔥</div><div class="stat-value"><?= $stats['forums_actifs'] ?? 0 ?></div><div class="stat-label">Forums actifs</div></div>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr><th>ID</th><th>Titre</th><th>Événement</th><th>Auteur</th><th>Messages</th><th>Date création</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($forums)): ?>
                            <tr><td colspan="7" style="text-align:center; padding:40px;">Aucun forum trouvé</td></tr>
                        <?php else: ?>
                            <?php foreach ($forums as $forum): ?>
                            <tr>
                                <td><?= $forum['idForum'] ?></td>
                                <td><strong><?= htmlspecialchars($forum['TitreForum'] ?? $forum['titreForum']) ?></strong></td>
                                <td><?= htmlspecialchars($forum['nom_evenement']) ?></td>
                                <td><?= htmlspecialchars($forum['nom_utilisateur'] ?? 'Admin') ?></td>
                                <td><span class="badge badge-active"><?= $forum['nb_messages'] ?? 0 ?> messages</span></td>
                                <td><?= date('d/m/Y', strtotime($forum['dateCreation'])) ?></td>
                                <td>
                                    <a href="/ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/forumC.php?action=voir&id=<?= $forum['idForum'] ?>" class="btn-view">👁 Voir</a>
                                    <button class="btn-delete" onclick="supprimerForum(<?= $forum['idForum'] ?>, '<?= htmlspecialchars(addslashes($forum['TitreForum'] ?? $forum['titreForum'])) ?>')">🗑 Supprimer</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB 2 : MESSAGES SIGNALÉS -->
        <div id="tab-signales" class="tab-content">
            <div class="stats-grid">
                <div class="stat-card"><div class="stat-icon">🚩</div><div class="stat-value"><?= $total_signales ?? 0 ?></div><div class="stat-label">Messages signalés</div></div>
                <div class="stat-card"><div class="stat-icon">⚠️</div><div class="stat-value"><?= $total_signales ?? 0 ?></div><div class="stat-label">En attente</div></div>
                <div class="stat-card"><div class="stat-icon">🗑️</div><div class="stat-value">0</div><div class="stat-label">Supprimés</div></div>
            </div>

            <?php if (empty($messages_signales)): ?>
                <div class="table-container" style="text-align:center; padding:60px;">
                    <div style="font-size: 3rem; margin-bottom: 12px;">✅</div>
                    <h3>Aucun message signalé</h3>
                    <p>Tous les messages sont conformes aux règles de la communauté</p>
                </div>
            <?php else: ?>
                <?php foreach ($messages_signales as $msg): ?>
                <div class="message-card">
                    <div class="message-header">
                        <div><strong>📌 Forum :</strong> <?= htmlspecialchars($msg['titreForum']) ?></div>
                        <button class="btn-delete" onclick="supprimerMessage(<?= $msg['idMessage'] ?>)">🗑 Supprimer le message</button>
                    </div>
                    <div style="margin-bottom: 8px;">👤 <?= htmlspecialchars($msg['nom_utilisateur']) ?> • 📅 <?= date('d/m/Y H:i', strtotime($msg['dateMessage'])) ?></div>
                    <div class="message-content"><?= nl2br(htmlspecialchars($msg['message'])) ?></div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- TAB 3 : STATISTIQUES -->
        <div id="tab-stats" class="tab-content">
            <div class="stats-grid">
                <div class="stat-card"><div class="stat-icon">🗂️</div><div class="stat-value"><?= $stats['total_forums'] ?? 0 ?></div><div class="stat-label">Total Forums</div></div>
                <div class="stat-card"><div class="stat-icon">💬</div><div class="stat-value"><?= $stats['total_messages'] ?? 0 ?></div><div class="stat-label">Total Messages</div></div>
                <div class="stat-card"><div class="stat-icon">🔥</div><div class="stat-value"><?= $stats['forums_actifs'] ?? 0 ?></div><div class="stat-label">Forums Actifs (7j)</div></div>
                <div class="stat-card"><div class="stat-icon">🚩</div><div class="stat-value"><?= $total_signales ?? 0 ?></div><div class="stat-label">Messages Signalés</div></div>
            </div>

            <div class="table-container">
                <div style="padding: 20px; border-bottom: 1px solid #30363d; font-weight: 600;">🏆 Top 5 des forums les plus actifs</div>
                <table>
                    <thead><tr><th>#</th><th>Forum</th><th>Messages</th><th>Activité</th></tr></thead>
                    <tbody>
                        <?php foreach (($stats['top_forums'] ?? []) as $index => $forum): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><strong><?= htmlspecialchars($forum['TitreForum']) ?></strong></td>
                            <td><?= $forum['nb_messages'] ?> messages</td>
                            <td><?= $forum['nb_messages'] > 10 ? '🔥 Très actif' : ($forum['nb_messages'] > 0 ? '💬 Actif' : '🕰️ Calme') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="table-container" style="margin-top: 20px;">
                <div style="padding: 20px; border-bottom: 1px solid #30363d; font-weight: 600;">👥 Top 5 des contributeurs</div>
                <tr>
                    <thead><tr><th>#</th><th>Contributeur</th><th>Messages</th><th>Impact</th></tr></thead>
                    <tbody>
                        <?php foreach (($stats['top_contributeurs'] ?? []) as $index => $user): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><strong><?= htmlspecialchars($user['nom'] ?? 'Anonyme') ?></strong></td>
                            <td><?= $user['nb_messages'] ?> messages</td>
                            <td><?= $user['nb_messages'] > 20 ? '🏆 Expert' : ($user['nb_messages'] > 5 ? '📝 Actif' : '🌱 Débutant') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script>
    function showTab(tabName) {
        document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.getElementById(`tab-${tabName}`).classList.add('active');
        event.target.classList.add('active');
    }

    function supprimerForum(id, titre) {
        if (confirm(`Supprimer définitivement le forum "${titre}" ?\n\nTous les messages seront également supprimés.`)) {
            window.location.href = `/ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/forumC.php?action=supprimer_forum&id=${id}`;
        }
    }

    function supprimerMessage(id) {
        if (confirm('Supprimer ce message signalé ? Cette action est irréversible.')) {
            window.location.href = `/ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/forumC.php?action=supprimer_message&id=${id}`;
        }
    }
</script>

</body>
</html>