<?php
require_once __DIR__ . '/../layout/session_bridge.php';

$currentFrontUser = cre8_front_session_user();

if (empty($currentFrontUser['isLoggedIn'])) {
    header('Location: ' . cre8_front_login_url());
    exit;
}

$currentRole = cre8_front_normalize_role($currentFrontUser['role'] ?? '');

if ($currentRole === 'admin') {
    $scriptPath = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');
    $frontOfficeMarker = '/Vue/FrontOffice/';
    $frontOfficePos = strpos($scriptPath, $frontOfficeMarker);
    $projectBase = $frontOfficePos !== false ? substr($scriptPath, 0, $frontOfficePos) : '';
    header('Location: ' . $projectBase . '/Vue/BackOffice/utilisateur/index.php');
    exit;
}

$userName = $currentFrontUser['nom']
    ?? $_SESSION['nom']
    ?? ($_SESSION['user']['nom'] ?? 'User');
$userName = trim((string) $userName);
$userName = $userName !== '' ? $userName : 'User';

$userInitial = function_exists('mb_substr')
    ? mb_substr($userName, 0, 1, 'UTF-8')
    : substr($userName, 0, 1);
$userInitial = strtoupper((string) $userInitial);
$userInitial = $userInitial !== '' ? $userInitial : 'U';

$userHandle = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '_', $userName), '_'));
$userHandle = $userHandle !== '' ? $userHandle : 'user';

$isBrand = $currentRole === 'marque';
$frontActive = 'home';

$pageTitle = $isBrand ? 'Brand Home — Cre8connect' : 'Creator Home — Cre8connect';
$roleLabel = $isBrand ? 'Brand' : 'Creator';
$roleIcon = $isBrand ? 'bi-shop-window' : 'bi-patch-check-fill';

$quickCards = $isBrand
    ? [
        [
            'href' => '../campagne/index.php',
            'iconClass' => 'icon-purple',
            'icon' => 'bi-megaphone',
            'title' => 'My Campaigns',
            'subtitle' => 'Create and manage your campaigns',
        ],
        [
            'href' => '../produit/index.php',
            'iconClass' => 'icon-blue',
            'icon' => 'bi-box-seam',
            'title' => 'Products',
            'subtitle' => 'Organize your product catalog',
        ],
        [
            'href' => '../contrat/index.php',
            'iconClass' => 'icon-green',
            'icon' => 'bi-file-earmark-text',
            'title' => 'Contracts',
            'subtitle' => 'Track your collaboration agreements',
        ],
        [
            'href' => '../offre/brand_index.php',
            'iconClass' => 'icon-red',
            'icon' => 'bi-briefcase',
            'title' => 'Collaborations',
            'subtitle' => 'Manage offers and received applications',
        ],
        [
            'href' => '../evenement/index.php',
            'iconClass' => 'icon-orange',
            'icon' => 'bi-calendar-event',
            'title' => 'Events',
            'subtitle' => 'Discover community events',
        ],
    ]
    : [
        [
            'href' => '../post/portfolio.php',
            'iconClass' => 'icon-purple',
            'icon' => 'bi-person-badge',
            'title' => 'My Space',
            'subtitle' => 'Manage your portfolio and publications',
        ],
        [
            'href' => '../post/index.php',
            'iconClass' => 'icon-blue',
            'icon' => 'bi-newspaper',
            'title' => 'Feeds',
            'subtitle' => 'View community posts',
        ],
        [
            'href' => '../offre/creator_list.php',
            'iconClass' => 'icon-green',
            'icon' => 'bi-briefcase',
            'title' => 'Offers',
            'subtitle' => 'Browse brand invitations',
        ],
        [
            'href' => '../condidature/index.php',
            'iconClass' => 'icon-red',
            'icon' => 'bi-send-check',
            'title' => 'Applications',
            'subtitle' => 'Track your replies and negotiations',
        ],
        [
            'href' => '../campagne/indexC.php',
            'iconClass' => 'icon-purple',
            'icon' => 'bi-megaphone',
            'title' => 'Campaigns',
            'subtitle' => 'Discover available campaigns',
        ],
        [
            'href' => '../evenement/index.php',
            'iconClass' => 'icon-orange',
            'icon' => 'bi-calendar-event',
            'title' => 'Events',
            'subtitle' => 'Join events and workshops',
        ],
    ];

$welcomeText = $isBrand
    ? 'Your brand space helps you manage campaigns, organize products, track contracts, manage collaborations, and discover events from one place.'
    : 'Your creator space helps you publish content, grow your portfolio, discover available campaigns and events, reply to offers, and track your applications easily.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/../layout/front-theme-bootstrap.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700;800&family=Fraunces:wght@700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../layout/front-header.css" rel="stylesheet">
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
        .icon-orange { background: #fff7ed; color: #ea580c; }

        [data-theme="dark"] .icon-red    { background: #2a1422; }
        [data-theme="dark"] .icon-blue   { background: #0a1e2e; color: #38bdf8; }
        [data-theme="dark"] .icon-green  { background: #0a1f16; color: #34d399; }
        [data-theme="dark"] .icon-orange { background: #2a1a0a; color: #fb923c; }

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
            .page-wrapper { padding: 25px 16px 60px; }
            .creator-cover { padding: 1.5rem; }
            .cover-name { font-size: 1.3rem; }
            .welcome-banner { padding: 2rem 1.5rem; }
            .welcome-banner h2 { font-size: 1.6rem; }
            .quick-links { grid-template-columns: 1fr 1fr; }
        }
    </style>
<link rel="icon" type="image/png" sizes="32x32" href="../../public/images/logo.png">
<link rel="shortcut icon" type="image/png" href="../../public/images/logo.png">
<link rel="apple-touch-icon" href="../../public/images/logo.png">
</head>
<body>

<?php require_once __DIR__ . '/../layout/header.php'; ?>

<!-- ══ CONTENT ══ -->
<div class="page-wrapper">

    <!-- Cover -->
    <div class="creator-cover">
        <div class="cover-avatar"><?php echo $userInitial; ?></div>
        <div>
            <div class="cover-name"><?php echo htmlspecialchars($userName); ?></div>
            <div class="cover-role"><i class="bi <?php echo htmlspecialchars($roleIcon); ?>"></i> <?php echo htmlspecialchars($roleLabel); ?></div>
            <div class="cover-handle">@<?php echo htmlspecialchars($userHandle); ?></div>
        </div>
    </div>

    <!-- Quick links -->
    <div class="quick-links">
        <?php foreach ($quickCards as $card): ?>
            <a href="<?php echo htmlspecialchars($card['href']); ?>" class="quick-card">
                <div class="quick-card-icon <?php echo htmlspecialchars($card['iconClass']); ?>"><i class="bi <?php echo htmlspecialchars($card['icon']); ?>"></i></div>
                <div>
                    <div class="quick-card-title"><?php echo htmlspecialchars($card['title']); ?></div>
                    <div class="quick-card-sub"><?php echo htmlspecialchars($card['subtitle']); ?></div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Welcome banner -->
    <div class="welcome-banner">
        <h2>Welcome, <?php echo htmlspecialchars($userName); ?> 👋</h2>
        <p><?php echo htmlspecialchars($welcomeText); ?></p>
    </div>

</div>

<!-- ══ FOOTER ══ -->
<footer>Copyright © Cre8connect 2026</footer>

<script src="../layout/front-header.js"></script>

</body>
</html>
