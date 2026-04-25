<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques Forums - Admin Cre8Connect</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            font-size: 0.85rem;
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

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: #161b22;
            border: 1px solid #30363d;
            border-radius: 20px;
            padding: 24px;
            transition: all 0.2s;
        }

        .stat-card:hover {
            border-color: #58a6ff;
            transform: translateY(-2px);
        }

        .stat-icon { font-size: 2rem; margin-bottom: 12px; }
        .stat-value { font-size: 2rem; font-weight: 800; color: #58a6ff; }
        .stat-label { font-size: 0.8rem; color: #8b949e; margin-top: 8px; }
        .stat-trend { font-size: 0.7rem; margin-top: 8px; color: #3fb950; }

        /* Charts */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
            margin-bottom: 32px;
        }

        .chart-card {
            background: #161b22;
            border: 1px solid #30363d;
            border-radius: 20px;
            padding: 24px;
        }

        .chart-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #e6edf3;
        }

        canvas { max-height: 300px; width: 100% !important; }

        /* Tables */
        .table-container {
            background: #161b22;
            border: 1px solid #30363d;
            border-radius: 20px;
            overflow: hidden;
            margin-bottom: 24px;
        }

        .table-header {
            padding: 16px 20px;
            border-bottom: 1px solid #30363d;
            font-weight: 600;
        }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px 20px; background: #21262d; color: #8b949e; font-size: 0.75rem; font-weight: 600; }
        td { padding: 12px 20px; border-bottom: 1px solid #30363d; font-size: 0.85rem; }
        tr:hover { background: #1a1f2e; }

        .rank-badge {
            display: inline-block;
            width: 28px;
            height: 28px;
            background: #21262d;
            border-radius: 10px;
            text-align: center;
            line-height: 28px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .rank-1 { background: #fbbf24; color: #0d1117; }
        .rank-2 { background: #94a3b8; color: #0d1117; }
        .rank-3 { background: #cd7f32; color: #0d1117; }

        @media (max-width: 1024px) {
            .charts-grid { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; padding: 20px; }
            .stats-grid { grid-template-columns: 1fr; }
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
            <a href="/ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/forumC.php?action=admin" class="nav-item">💬 Forums</a>
            <a href="/ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/forumC.php?action=signales" class="nav-item">🚩 Messages signalés</a>
            <a href="/ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/forumC.php?action=statistiques" class="nav-item active">📊 Statistiques</a>
        </div>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <div>
                <h1>📊 Tableau de bord</h1>
                <p>Analyse complète de l'activité de la communauté</p>
            </div>
        </div>

        <!-- KPI Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">🗂️</div>
                <div class="stat-value"><?= $stats['total_forums'] ?></div>
                <div class="stat-label">Total Forums</div>
                <div class="stat-trend">↑ +<?= $stats['forums_actifs'] ?> actifs cette semaine</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💬</div>
                <div class="stat-value"><?= $stats['total_messages'] ?></div>
                <div class="stat-label">Total Messages</div>
                <div class="stat-trend">📈 Moyenne de <?= round($stats['total_messages'] / max(1, $stats['total_forums']), 1) ?> msg/forum</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🔥</div>
                <div class="stat-value"><?= $stats['forums_actifs'] ?></div>
                <div class="stat-label">Forums Actifs (7j)</div>
                <div class="stat-trend">⚡ <?= round(($stats['forums_actifs'] / max(1, $stats['total_forums'])) * 100) ?>% des forums</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🚩</div>
                <div class="stat-value"><?= $stats['messages_signales'] ?></div>
                <div class="stat-label">Messages Signalés</div>
                <div class="stat-trend"><?= $stats['messages_signales'] == 0 ? '✅ Tout est propre' : '⚠️ À modérer' ?></div>
            </div>
        </div>

        <!-- Charts -->
        <div class="charts-grid">
            <div class="chart-card">
                <div class="chart-title">📈 Messages par jour (7 derniers jours)</div>
                <canvas id="messagesChart"></canvas>
            </div>
            <div class="chart-card">
                <div class="chart-title">📊 Évolution des messages (6 mois)</div>
                <canvas id="evolutionChart"></canvas>
            </div>
        </div>

        <!-- Top Forums -->
        <div class="table-container">
            <div class="table-header">🏆 Top 5 des forums les plus actifs</div>
            <table>
                <thead>
                    <tr><th>#</th><th>Forum</th><th>Messages</th><th>Activité</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($stats['top_forums'] as $index => $forum): ?>
                    <tr>
                        <td><span class="rank-badge <?= $index == 0 ? 'rank-1' : ($index == 1 ? 'rank-2' : ($index == 2 ? 'rank-3' : '')) ?>"><?= $index + 1 ?></span></td>
                        <td><strong><?= htmlspecialchars($forum['TitreForum']) ?></strong></td>
                        <td><?= $forum['nb_messages'] ?> messages</td>
                        <td><?= $forum['nb_messages'] > 10 ? '🔥 Très actif' : ($forum['nb_messages'] > 0 ? '💬 Actif' : '🕰️ Calme') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Top Contributeurs -->
        <div class="table-container">
            <div class="table-header">👥 Top 5 des contributeurs</div>
            <table>
                <thead>
                    <tr><th>#</th><th>Contributeur</th><th>Messages</th><th>Impact</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($stats['top_contributeurs'] as $index => $user): ?>
                    <tr>
                        <td><span class="rank-badge <?= $index == 0 ? 'rank-1' : ($index == 1 ? 'rank-2' : ($index == 2 ? 'rank-3' : '')) ?>"><?= $index + 1 ?></span></td>
                        <td><strong><?= htmlspecialchars($user['nom'] ?? 'Anonyme') ?></strong></td>
                        <td><?= $user['nb_messages'] ?> messages</td>
                        <td><?= $user['nb_messages'] > 20 ? '🏆 Expert' : ($user['nb_messages'] > 5 ? '📝 Actif' : '🌱 Débutant') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<script>
    // Graphique messages par jour
    const ctx1 = document.getElementById('messagesChart').getContext('2d');
    const jours = <?= json_encode(array_column($stats['messages_par_jour'], 'jour')) ?>;
    const totals = <?= json_encode(array_column($stats['messages_par_jour'], 'total')) ?>;
    
    new Chart(ctx1, {
        type: 'line',
        data: {
            labels: jours.map(j => new Date(j).toLocaleDateString('fr-FR', {day:'numeric', month:'short'})),
            datasets: [{
                label: 'Messages',
                data: totals,
                borderColor: '#58a6ff',
                backgroundColor: 'rgba(88, 166, 255, 0.1)',
                tension: 0.3,
                fill: true,
                pointBackgroundColor: '#58a6ff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: { legend: { labels: { color: '#e6edf3' } } },
            scales: { y: { ticks: { color: '#8b949e' }, grid: { color: '#30363d' } }, x: { ticks: { color: '#8b949e' }, grid: { color: '#30363d' } } }
        }
    });

    // Graphique évolution
    const ctx2 = document.getElementById('evolutionChart').getContext('2d');
    const mois = <?= json_encode(array_column($stats['evolution'], 'mois')) ?>;
    const evolution = <?= json_encode(array_column($stats['evolution'], 'total')) ?>;
    
    new Chart(ctx2, {
        type: 'bar',
        data: {
            labels: mois,
            datasets: [{
                label: 'Messages',
                data: evolution,
                backgroundColor: '#bc8cff',
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: { legend: { labels: { color: '#e6edf3' } } },
            scales: { y: { ticks: { color: '#8b949e' }, grid: { color: '#30363d' } }, x: { ticks: { color: '#8b949e' }, grid: { color: '#30363d' } } }
        }
    });
</script>

</body>
</html>