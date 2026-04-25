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

        /* Layout Admin */
        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
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

        .sidebar-nav {
            padding: 20px 0;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: #8b949e;
            text-decoration: none;
            transition: all 0.2s;
            font-size: 0.85rem;
        }

        .nav-item:hover, .nav-item.active {
            background: #21262d;
            color: #58a6ff;
        }

        /* Main Content */
        .main-content {
            margin-left: 260px;
            flex: 1;
            padding: 24px 32px;
        }

        /* Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
        }

        .page-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
        }

        .page-header p {
            color: #8b949e;
            font-size: 0.85rem;
            margin-top: 4px;
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
            transition: all 0.2s;
        }

        .stat-card:hover {
            border-color: #58a6ff;
        }

        .stat-icon {
            font-size: 1.8rem;
            margin-bottom: 12px;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #58a6ff;
        }

        .stat-label {
            font-size: 0.75rem;
            color: #8b949e;
            margin-top: 4px;
        }

        /* Table */
        .table-container {
            background: #161b22;
            border: 1px solid #30363d;
            border-radius: 16px;
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 14px 20px;
            background: #21262d;
            color: #8b949e;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 16px 20px;
            border-bottom: 1px solid #30363d;
            font-size: 0.85rem;
        }

        tr:hover {
            background: #1a1f2e;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .badge-active {
            background: #23863620;
            color: #3fb950;
            border: 1px solid #23863640;
        }

        .badge-inactive {
            background: #6b728020;
            color: #8b949e;
            border: 1px solid #6b728040;
        }

        .btn-delete {
            background: #da3633;
            color: white;
            border: none;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 0.7rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-delete:hover {
            background: #f85149;
        }

        .btn-view {
            background: #21262d;
            color: #58a6ff;
            border: 1px solid #30363d;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 0.7rem;
            cursor: pointer;
            text-decoration: none;
            margin-right: 8px;
            display: inline-block;
        }

        .btn-view:hover {
            background: #30363d;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .sidebar {
                display: none;
            }
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
        }
    </style>
</head>
<body>

<div class="admin-container">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-logo">
            <img src="/ProjetWeb/Esprit-PW-2A22-2526-Devcore/Vue/public/images/logo.png" alt="Logo" class="logo-img">
            <span class="logo-text">Cre8Connect</span>
        </div>
        <div class="sidebar-nav">
            <a href="/ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/evenementC.php?action=admin" class="nav-item">📅 Événements</a>
            <a href="/ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/forumC.php?action=admin" class="nav-item active">💬 Forums</a>
            <a href="/ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/forumC.php?action=signales" class="nav-item">🚩 Messages signalés</a>
            <a href="/ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/forumC.php?action=statistiques" class="nav-item">📊 Statistiques</a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="page-header">
            <div>
                <h1>💬 Gestion des Forums</h1>
                <p>Supervision et modération de tous les forums de discussion</p>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">🗂️</div>
                <div class="stat-value"><?= count($forums) ?></div>
                <div class="stat-label">Total forums</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💬</div>
                <div class="stat-value"><?= array_sum(array_column($forums, 'nb_messages')) ?></div>
                <div class="stat-label">Total messages</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-value"><?= count(array_unique(array_column($forums, 'idUtilisateur'))) ?></div>
                <div class="stat-label">Participants</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🔥</div>
                <div class="stat-value"><?= count($forums) ?></div>
                <div class="stat-label">Forums actifs</div>
            </div>
        </div>

        <!-- Table -->
        <div class="table-container">
             <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Titre</th>
                        <th>Événement</th>
                        <th>Auteur</th>
                        <th>Messages</th>
                        <th>Date création</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($forums)): ?>
                        <tr>
                            <td colspan="7" style="text-align:center; padding:40px;">Aucun forum trouvé</td>
                        </tr>
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
    </main>
</div>

<script>
    function supprimerForum(id, titre) {
        if (confirm(`Supprimer définitivement le forum "${titre}" ?\n\nTous les messages seront également supprimés.`)) {
            window.location.href = `/ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/forumC.php?action=supprimer_forum&id=${id}`;
        }
    }
</script>

</body>
</html>