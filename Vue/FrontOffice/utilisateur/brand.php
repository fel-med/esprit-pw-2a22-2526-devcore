<?php
session_start();
require_once __DIR__ . '/../../../Controleur/profileC.php';

if (!isset($_SESSION['id'])) {

    echo "User not connected";
    exit();
}

/*
|--------------------------------------------------------------------------
| Connected user name
|--------------------------------------------------------------------------
*/

$userName = $_SESSION['nom'] ?? 'Brand User';
$userInitial = function_exists('mb_substr')
    ? mb_substr($userName, 0, 1, 'UTF-8')
    : substr($userName, 0, 1);
$userInitial = strtoupper((string) $userInitial) ?: 'B';
$profileImageUrl = null;

try {
    $profileC = new ProfileC();
    $profileImageUrl = $profileC->getProfileImageUrl((int) $_SESSION['id'], '../../public/uploads/profile');
} catch (Throwable $e) {
    $profileImageUrl = null;
}

$brandPagePath = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');
$brandFrontMarker = '/Vue/FrontOffice/';
$brandFrontPos = strpos($brandPagePath, $brandFrontMarker);
$projectBase = $brandFrontPos !== false ? substr($brandPagePath, 0, $brandFrontPos) : '';

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/../layout/front-theme-bootstrap.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Brand Page</title>


    <!-- GOOGLE FONTS -->
    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700;800&family=Fraunces:wght@700;800&display=swap" rel="stylesheet">

    <!-- ICONS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">

    <link rel="stylesheet" href="../layout/front-header.css">

    <style>

        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
        }

        :root{

            /* ===== BLEU COMME CREATOR ===== */

            --primary:#5b4fff;
            --primary-hover:#4438e0;
            --primary-light:#ece9ff;

            --bg:#f6f6fc;
            --white:#ffffff;

            --text:#111827;
            --text-sub:#6b7280;

            --border:#ececec;

            --danger:#ef4444;
        }

        body{
            font-family:'DM Sans',sans-serif;
            background:var(--bg);
            color:var(--text);
            min-height:100vh;
        }

        /* =========================
           NAVBAR
        ========================= */

        nav{

            height:75px;

            background:white;

            border-bottom:1px solid var(--border);

            display:flex;
            align-items:center;
            justify-content:space-between;

            padding:0 50px;

            position:sticky;
            top:0;

            z-index:999;
        }

        .nav-logo{
            text-decoration:none;
            display:inline-flex;
            align-items:center;
        }

        .nav-links{

            list-style:none;

            display:flex;
            align-items:center;
            gap:10px;
        }

        .nav-links a{

            text-decoration:none;

            color:var(--text-sub);

            padding:10px 16px;

            border-radius:12px;

            font-weight:700;

            transition:0.3s;
        }

        .nav-links a:hover{

            background:var(--primary-light);
            color:var(--primary);
        }

        .nav-links .active{

            background:var(--primary-light);
            color:var(--primary);
        }

        .nav-right{

            display:flex;
            align-items:center;
            gap:15px;
        }

        .nav-badge{

            background:var(--primary);

            color:white;

            padding:8px 14px;

            border-radius:30px;

            font-size:13px;
            font-weight:700;
        }

        .nav-avatar{

            width:42px;
            height:42px;

            border-radius:50%;

            background:var(--primary-light);

            display:flex;
            align-items:center;
            justify-content:center;

            color:var(--primary);

            font-size:16px;
            font-weight:800;
        }

        /* =========================
           PAGE
        ========================= */

        .page-wrapper{

            max-width:1200px;

            margin:auto;

            padding:70px 25px;
        }

        .page-header{

            display:flex;
            justify-content:space-between;
            align-items:center;

            gap:20px;

            flex-wrap:wrap;
        }

        .page-header-left h1{

            font-size:65px;

            font-family:'Fraunces',serif;

            margin-bottom:15px;
        }

        .page-header-left p{

            font-size:22px;

            color:var(--text-sub);
        }

        .page-header-actions{

            display:flex;
            gap:15px;
        }

        .btn-export{

            text-decoration:none;

            background:white;

            border:1px solid var(--border);

            padding:18px 35px;

            border-radius:18px;

            color:var(--text);

            font-weight:700;

            transition:0.3s;
        }

        .btn-export:hover{

            background:var(--primary-light);

            color:var(--primary);
        }

        .btn-brand{

            text-decoration:none;

            background:var(--primary);

            color:white;

            padding:18px 35px;

            border-radius:18px;

            font-weight:700;

            transition:0.3s;
        }

        .btn-brand:hover{

            background:var(--primary-hover);
        }

        /* =========================
           HERO SECTION
        ========================= */

        .hero-section{

            margin-top:70px;

            background:linear-gradient(135deg,#5b4fff,#7c3aed);

            border-radius:30px;

            padding:110px 40px;

            text-align:center;

            color:white;

            position:relative;

            overflow:hidden;
        }

        .hero-section::before{

            content:'';

            position:absolute;

            width:350px;
            height:350px;

            border-radius:50%;

            background:rgba(255,255,255,0.1);

            top:-120px;
            right:-120px;
        }

        .hero-section::after{

            content:'';

            position:absolute;

            width:250px;
            height:250px;

            border-radius:50%;

            background:rgba(255,255,255,0.08);

            bottom:-100px;
            left:-100px;
        }

        .hero-content{

            position:relative;
            z-index:2;
        }

        .hero-section h2{

            font-size:52px;

            font-family:'Fraunces',serif;

            margin-bottom:20px;
        }

        .hero-section p{

            font-size:20px;

            opacity:0.95;
        }

        /* =========================
           CARDS
        ========================= */

        .cards-grid{

            margin-top:50px;

            display:grid;

            grid-template-columns:repeat(auto-fit,minmax(250px,1fr));

            gap:25px;
        }

        .info-card{

            background:white;

            border-radius:24px;

            padding:30px;

            box-shadow:0 10px 30px rgba(0,0,0,0.05);

            transition:0.3s;
        }

        .info-card:hover{

            transform:translateY(-5px);
        }

        .info-card i{

            font-size:40px;

            color:var(--primary);

            margin-bottom:20px;
        }

        .info-card h3{

            margin-bottom:10px;

            font-size:22px;
        }

        .info-card p{

            color:var(--text-sub);

            line-height:1.6;
        }

        .info-card-link{

            display:block;
            text-decoration:none;
            color:var(--text);
        }

        .info-card-link:hover{

            color:var(--text);
        }

        .info-card.event-card i{

            color:#ea580c;
        }

        /* =========================
           FOOTER
        ========================= */

        footer{

            margin-top:80px;

            background:white;

            border-top:1px solid var(--border);

            padding:35px;

            text-align:center;
        }

        footer p{

            color:var(--text-sub);

            font-size:14px;
        }

        /* =========================
           DARK MODE
        ========================= */

        .light-mode{

            background:#111827 !important;
            color:white !important;
        }

        .light-mode nav{

            background:#1f2937;
            border-color:#374151;
        }

        .light-mode .info-card,
        .light-mode footer{

            background:#1f2937;
            color:white;
        }

        .light-mode .info-card-link{

            color:white;
        }

        .light-mode .info-card.event-card i{

            color:#fb923c;
        }

        .light-mode .page-header-left p,
        .light-mode .info-card p{

            color:#d1d5db;
        }

        .light-mode .btn-export{

            background:#1f2937;
            border-color:#374151;
            color:white;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media(max-width:768px){

            nav{

                padding:20px;

                height:auto;

                flex-direction:column;

                gap:15px;
            }

            .page-header{

                flex-direction:column;

                align-items:flex-start;
            }

            .page-header-left h1{

                font-size:45px;
            }

            .hero-section h2{

                font-size:36px;
            }

            .page-header-actions{

                width:100%;

                flex-direction:column;
            }

            .btn-export,
            .btn-brand{

                width:100%;

                text-align:center;
            }
        }

    </style>
<link rel="icon" type="image/png" sizes="16x16" href="../../public/images/favicon-16.png">
<link rel="icon" type="image/png" sizes="32x32" href="../../public/images/favicon-32.png">
<link rel="shortcut icon" type="image/png" href="../../public/images/favicon-32.png">
<link rel="apple-touch-icon" sizes="180x180" href="../../public/images/apple-touch-icon.png">

</head>

<body>

<!-- =========================
     NAVBAR
========================= -->

<nav>

    <a class="nav-logo" href="brand.php">
        <img src="<?php echo htmlspecialchars($projectBase . '/Vue/public/images/logoweb.png'); ?>" alt="Cre8Connect" class="front-header-logo">
    </a>

    <ul class="nav-links">

        <li>
            <a href="brand.php" class="active">
                <span data-i18n="brand.home">Home</span>
            </a>
        </li>

        <li>
            <a href="../evenement/index.php">
                <span data-i18n="brand.events">Events</span>
            </a>
        </li>

        <li>
            <a href="reclamation.php">
                <span data-i18n="brand.complaints">Complaints</span>
            </a>
        </li>

        <li>
            <a href="#" onclick="brandThemeToggle(); return false;">
                <i class="bi bi-moon-stars" id="themeIcon"></i>
            </a>
        </li>

        <li>
            <a href="logout.php" style="color:#ef4444;">
                <i class="bi bi-box-arrow-right"></i>
                <span data-i18n="brand.logout">Logout</span>
            </a>
        </li>

    </ul>

    <div class="nav-right">

        <div class="nav-badge">
            &#128100; <?php echo htmlspecialchars($userName); ?>
        </div>

        <?php if ($profileImageUrl): ?>
            <img class="nav-avatar" src="<?php echo htmlspecialchars($profileImageUrl); ?>" alt="Profile photo" style="object-fit:cover;padding:0;">
        <?php else: ?>
            <div class="nav-avatar">
                <?php echo htmlspecialchars($userInitial); ?>
            </div>
        <?php endif; ?>

    </div>

</nav>

<!-- =========================
     MAIN
========================= -->

<div class="page-wrapper">

    <div class="page-header">

        <div class="page-header-left">

            <h1 data-i18n="brand.pageTitle">Brand Page</h1>

            <p>
                <span data-i18n="brand.hello">Hello</span> :
                <strong>
                    <?php echo htmlspecialchars($userName); ?>
                </strong>
            </p>

        </div>

        <div class="page-header-actions">

            <a href="reclamation.php" class="btn-export">
                <span data-i18n="brand.complaints">Complaints</span>
            </a>

            <a href="#" class="btn-brand">
                <span data-i18n="brand.brandSpace">Brand Space</span>
            </a>

            <a href="../evenement/index.php" class="btn-brand">
                <span data-i18n="brand.events">Events</span>
            </a>

        </div>

    </div>

    <!-- HERO -->

    <section class="hero-section">

        <div class="hero-content">

            <h2>
                  <span data-i18n="brand.heroTitle">Let's build something together</span>
            </h2>

    
        </div>

    </section>

    <!-- EVENTS ACCESS -->
    <div class="cards-grid">

        <a href="../evenement/index.php" class="info-card info-card-link event-card">
            <i class="bi bi-calendar-event"></i>
            <h3 data-i18n="brand.events">Events</h3>
            <p data-i18n="brand.eventsHelp">Discover training sessions, meetups, webinars, and workshops available for the community.</p>
        </a>

    </div>


</div>

<!-- =========================
     FOOTER
========================= -->

<footer>

    <p>
        <span data-i18n="brand.copyright">Copyright © Cre8connect 2026</span>
    </p>

</footer>

<script src="../layout/front-header.js"></script>
<script src="../layout/front-translate.js"></script>
<script>
(function () {
    var translations = {
        en: {
            'brand.home': 'Home',
            'brand.events': 'Events',
            'brand.complaints': 'Complaints',
            'brand.logout': 'Logout',
            'brand.pageTitle': 'Brand Page',
            'brand.documentTitle': 'Brand Page',
            'brand.hello': 'Hello',
            'brand.brandSpace': 'Brand Space',
            'brand.heroTitle': "Let's build something together",
            'brand.eventsHelp': 'Discover training sessions, meetups, webinars, and workshops available for the community.',
            'brand.copyright': 'Copyright © Cre8connect 2026'
        },
        fr: {
            'brand.home': 'Accueil',
            'brand.events': 'Evenements',
            'brand.complaints': 'Reclamations',
            'brand.logout': 'Deconnexion',
            'brand.pageTitle': 'Page marque',
            'brand.documentTitle': 'Page marque',
            'brand.hello': 'Bonjour',
            'brand.brandSpace': 'Espace marque',
            'brand.heroTitle': 'Construisons quelque chose ensemble',
            'brand.eventsHelp': 'Decouvrez les formations, meetups, webinaires et ateliers disponibles pour la communaute.',
            'brand.copyright': 'Copyright © Cre8connect 2026'
        }
    };
    function registerBrandTranslations() {
        if (typeof window.cre8RegisterTranslations === 'function') {
            window.cre8RegisterTranslations(translations);
        }
        var lang = typeof window.cre8FrontReadLang === 'function' ? window.cre8FrontReadLang() : 'en';
        document.title = (translations[lang] && translations[lang]['brand.documentTitle']) || translations.en['brand.documentTitle'];
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', registerBrandTranslations);
    } else {
        registerBrandTranslations();
    }
    window.addEventListener('cre8:languagechange', registerBrandTranslations);
})();
</script>
<script>
(function () {
    function syncBrandDarkUiClass() {
        var dark = document.documentElement.getAttribute('data-theme') === 'dark';
        document.body.classList.toggle('light-mode', dark);
    }
    window.brandThemeToggle = function () {
        if (typeof window.cre8ApplyFrontTheme === 'function') {
            window.cre8ApplyFrontTheme(document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark', true);
        }
        syncBrandDarkUiClass();
    };
    document.addEventListener('DOMContentLoaded', function () {
        syncBrandDarkUiClass();
        if (window.MutationObserver) {
            new MutationObserver(syncBrandDarkUiClass).observe(document.documentElement, {
                attributes: true,
                attributeFilter: ['data-theme']
            });
        }
    });
})();
</script>

</body>
</html>
