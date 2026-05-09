<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$userName = $_SESSION['nom'] ?? 'Unknown User';
$userInitial = strtoupper(substr($userName, 0, 1));
?>
<!DOCTYPE html>
<html lang="fr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Creator Page — Cre8connect</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700;800&family=Fraunces:wght@700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

        :root {
            --primary:       #5b4fff;
            --primary-hover: #4438e0;
            --primary-light: #ece9ff;
            --primary-glow:  rgba(91,79,255,0.15);
            --bg:            #f6f6fc;
            --white:         #ffffff;
            --text:          #0f0e1a;
            --text-sub:      #6b6f80;
            --border:        #ebebf2;
            --danger:        #f43f5e;
            --card-shadow:   0 1px 3px rgba(15,14,26,0.06), 0 4px 16px rgba(91,79,255,0.06);
            --card-shadow-hover: 0 8px 32px rgba(91,79,255,0.14);
            --radius: 16px;
        }

        [data-theme="dark"] {
            --primary:       #7c6fff;
            --primary-hover: #9d8fff;
            --primary-light: #1e1a3a;
            --bg:            #13121f;
            --white:         #1c1a2e;
            --text:          #e8e6f5;
            --text-sub:      #9b9db8;
            --border:        #2a2840;
            --card-shadow:   0 1px 3px rgba(0,0,0,0.35), 0 4px 16px rgba(0,0,0,0.25);
            --card-shadow-hover: 0 8px 32px rgba(124,111,255,0.18);
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: background 0.3s, color 0.3s;
        }

        /* ══ NAVBAR ══ */
        nav {
            height: 70px;
            background: var(--white);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 40px;
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 999;
            box-shadow: 0 2px 12px rgba(15,14,26,0.04);
        }

        .nav-logo {
            text-decoration: none;
            font-size: 24px;
            font-weight: 800;
            font-family: 'Fraunces', serif;
            color: var(--primary);
        }

        .nav-links {
            list-style: none;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text-sub);
            padding: 8px 14px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 14px;
            transition: all 0.18s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .nav-links a:hover { background: var(--primary-light); color: var(--primary); }
        .nav-links a.active { background: var(--primary-light); color: var(--primary); }

        .nav-links a.nav-myspace {
            background: var(--primary-light);
            color: var(--primary);
        }
        .nav-links a.nav-myspace:hover {
            background: var(--primary-light);
            color: var(--primary);
        }

        .nav-links a.nav-logout { color: var(--danger); }
        .nav-links a.nav-logout:hover { background: #fff1f3; color: var(--danger); }

        .nav-right { display: flex; align-items: center; gap: 12px; }

        .nav-badge {
            background: var(--primary-light);
            color: var(--primary);
            padding: 7px 14px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .nav-avatar {
            width: 38px; height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, #5b4fff, #8b5cf6);
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: 800; font-size: 15px;
            box-shadow: 0 3px 10px rgba(91,79,255,0.3);
        }

        .theme-btn {
            background: none; border: none; cursor: pointer;
            color: var(--text-sub); font-size: 18px; padding: 8px;
            border-radius: 50%; transition: all 0.18s;
            display: flex; align-items: center;
        }
        .theme-btn:hover { background: var(--primary-light); color: var(--primary); }

        /* ══ PAGE ══ */
        .page-wrapper {
            max-width: 1100px;
            margin: 0 auto;
            padding: 50px 25px 80px;
            flex: 1;
        }

        /* ══ COVER ══ */
        .creator-cover {
            border-radius: 24px;
            background: rgba(210,200,255,0.45);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(210,200,255,0.6);
            box-shadow: 0 8px 32px rgba(91,79,255,0.08);
            padding: 2.5rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            flex-wrap: wrap;
            position: relative;
            overflow: hidden;
        }
        [data-theme="dark"] .creator-cover {
            background: rgba(40,34,80,0.5);
            border-color: rgba(124,111,255,0.2);
        }

        .creator-cover::before {
            content: '';
            position: absolute;
            width: 320px; height: 320px;
            border-radius: 50%;
            background: rgba(91,79,255,0.07);
            top: -80px; right: -80px;
            pointer-events: none;
        }

        .cover-avatar {
            width: 72px; height: 72px;
            border-radius: 50%;
            background: linear-gradient(135deg, #5b4fff, #8b5cf6);
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 1.8rem; font-weight: 800;
            font-family: 'Fraunces', serif;
            box-shadow: 0 6px 20px rgba(91,79,255,0.35);
            flex-shrink: 0;
        }

        .cover-name {
            font-family: 'Fraunces', serif;
            font-size: 1.6rem; font-weight: 800;
            color: var(--text); margin-bottom: 6px;
        }

        .cover-role {
            display: inline-flex; align-items: center; gap: 6px;
            background: var(--primary-light); color: var(--primary);
            font-size: 12px; font-weight: 700;
            padding: 4px 12px; border-radius: 999px;
            margin-bottom: 4px;
        }

        .cover-handle { color: var(--text-sub); font-size: 13px; }

        /* ══ QUICK CARDS ══ */
        .quick-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.2rem;
            margin-top: 2.5rem;
        }

        .quick-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1.5rem;
            text-decoration: none;
            color: var(--text);
            display: flex; align-items: flex-start; gap: 1rem;
            box-shadow: var(--card-shadow);
            transition: all 0.2s ease;
        }
        .quick-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--card-shadow-hover);
            color: var(--text);
            border-color: rgba(91,79,255,0.2);
        }

        .quick-card-icon {
            width: 44px; height: 44px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; flex-shrink: 0;
        }
        .icon-purple { background: var(--primary-light); color: var(--primary); }
        .icon-red    { background: #fff1f3; color: #f43f5e; }
        .icon-blue   { background: #f0f9ff; color: #0369a1; }
        .icon-green  { background: #f0fdf4; color: #15803d; }

        [data-theme="dark"] .icon-red   { background: #2a1422; }
        [data-theme="dark"] .icon-blue  { background: #0a1e2e; color: #38bdf8; }
        [data-theme="dark"] .icon-green { background: #0a1f16; color: #34d399; }

        .quick-card-title { font-weight: 800; font-size: 15px; margin-bottom: 4px; }
        .quick-card-sub   { font-size: 13px; color: var(--text-sub); line-height: 1.4; }

        /* ══ WELCOME BANNER ══ */
        .welcome-banner {
            margin-top: 2.5rem;
            border-radius: 24px;
            background: linear-gradient(135deg, #5b4fff 0%, #7c3aed 100%);
            padding: 3rem 2.5rem;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }
        .welcome-banner::before {
            content: ''; position: absolute;
            width: 300px; height: 300px; border-radius: 50%;
            background: rgba(255,255,255,0.06);
            top: -80px; left: -60px;
        }
        .welcome-banner::after {
            content: ''; position: absolute;
            width: 200px; height: 200px; border-radius: 50%;
            background: rgba(255,255,255,0.06);
            bottom: -60px; right: -40px;
        }
        .welcome-banner h2 {
            font-family: 'Fraunces', serif;
            font-size: 2.2rem; font-weight: 800;
            margin-bottom: 12px; position: relative;
        }
        .welcome-banner p {
            font-size: 16px; opacity: 0.85;
            max-width: 500px; margin: 0 auto; position: relative;
        }

        /* ══ FOOTER ══ */
        footer {
            background: var(--white);
            border-top: 1px solid var(--border);
            padding: 24px 40px;
            text-align: center;
            color: var(--text-sub);
            font-size: 13px;
        }

        /* ══ RESPONSIVE ══ */
        @media(max-width: 768px) {
            nav { padding: 0 16px; height: auto; flex-wrap: wrap; gap: 10px; padding: 12px 16px; }
            .nav-links { gap: 2px; }
            .nav-links a { padding: 7px 10px; font-size: 12px; }
            .page-wrapper { padding: 25px 16px 60px; }
            .creator-cover { padding: 1.5rem; }
            .cover-name { font-size: 1.3rem; }
            .welcome-banner { padding: 2rem 1.5rem; }
            .welcome-banner h2 { font-size: 1.6rem; }
            .quick-links { grid-template-columns: 1fr 1fr; }
            .nav-badge { display: none; }
        }
    </style>
</head>
<body>

<!-- ══ NAVBAR ══ -->
<nav>
    <a class="nav-logo" href="creator.php">Cre8connect</a>

    <ul class="nav-links">
        <li><a href="creator.php" class="active"><i class="bi bi-house"></i> Home</a></li>
        <li><a href="reclamation.php"><i class="bi bi-flag"></i> Réclamation</a></li>
        <li><a href="../post/portfolio.php" class="nav-myspace"><i class="bi bi-person-badge"></i> My Space</a></li>
        <li>
            <button class="theme-btn" id="themeBtn" title="Toggle dark mode">
                <i class="bi bi-moon-stars" id="themeIcon"></i>
            </button>
        </li>
        <li><a href="logout.php" class="nav-logout"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
    </ul>

    <div class="nav-right">
        <div class="nav-badge">
            <i class="bi bi-person-circle"></i>
            <?php echo htmlspecialchars($userName); ?>
        </div>
        <div class="nav-avatar"><?php echo $userInitial; ?></div>
    </div>
</nav>

<!-- ══ CONTENT ══ -->
<div class="page-wrapper">

    <!-- Cover -->
    <div class="creator-cover">
        <div class="cover-avatar"><?php echo $userInitial; ?></div>
        <div>
            <div class="cover-name"><?php echo htmlspecialchars($userName); ?></div>
            <div class="cover-role"><i class="bi bi-patch-check-fill"></i> Creator</div>
            <div class="cover-handle">@<?php echo strtolower(str_replace(' ', '_', $userName)); ?></div>
        </div>
    </div>

    <!-- Quick links -->
    <div class="quick-links">
        <a href="../post/portfolio.php" class="quick-card">
            <div class="quick-card-icon icon-purple"><i class="bi bi-person-badge"></i></div>
            <div>
                <div class="quick-card-title">My Space</div>
                <div class="quick-card-sub">Voir et gérer vos publications</div>
            </div>
        </a>

        <a href="../post/index.php" class="quick-card">
            <div class="quick-card-icon icon-blue"><i class="bi bi-newspaper"></i></div>
            <div>
                <div class="quick-card-title">Actualité</div>
                <div class="quick-card-sub">Explorer le feed communautaire</div>
            </div>
        </a>

    </div>

    <!-- Welcome banner -->
    <div class="welcome-banner">
        <h2>Welcome, <?php echo htmlspecialchars($userName); ?> 👋</h2>
        <p>Votre espace créateur vous attend. Publiez, partagez et connectez-vous avec votre audience.</p>
    </div>

</div>

<!-- ══ FOOTER ══ -->
<footer>Copyright © Cre8connect 2026</footer>

<script>
    const btn  = document.getElementById('themeBtn');
    const icon = document.getElementById('themeIcon');
    const html = document.documentElement;

    function applyTheme(t) {
        html.setAttribute('data-theme', t);
        localStorage.setItem('cre8_theme', t);
        icon.className = t === 'dark' ? 'bi bi-brightness-high' : 'bi bi-moon-stars';
    }

    const saved = localStorage.getItem('cre8_theme') ||
        (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    applyTheme(saved);

    btn.addEventListener('click', () => {
        applyTheme(html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
    });
</script>

</body>
</html>