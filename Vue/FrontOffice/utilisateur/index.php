<?php
// Public Cre8Connect landing page.
// Guests see this page. Logged-in users are sent to the existing role hub.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$sessionUser = isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : [];
$frontUser = isset($_SESSION['utilisateur']) && is_array($_SESSION['utilisateur']) ? $_SESSION['utilisateur'] : [];

$userId = $sessionUser['id']
    ?? $frontUser['id']
    ?? $_SESSION['id']
    ?? $_SESSION['user_id']
    ?? null;

$isLoggedIn = !empty($_SESSION['connected'])
    || !empty($sessionUser)
    || !empty($frontUser)
    || !empty($userId);

if ($isLoggedIn) {
    header('Location: home.php');
    exit;
}
?>
<html lang="en"><head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta name="description" content="">
        <meta name="author" content="">
        <title>Cre8Connect - Home</title>
        <!-- Favicon-->
        <!-- Custom Google font-->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="">
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@100;200;300;400;500;600;700;800;900&amp;display=swap" rel="stylesheet">
        <!-- Bootstrap icons-->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
        <!-- Core theme CSS (includes Bootstrap)-->
        <link href="css/styles.css" rel="stylesheet">
<link rel="icon" type="image/png" sizes="16x16" href="../../public/images/favicon-16.png">
<link rel="icon" type="image/png" sizes="32x32" href="../../public/images/favicon-32.png">
<link rel="shortcut icon" type="image/png" href="../../public/images/favicon-32.png">
<link rel="apple-touch-icon" sizes="180x180" href="../../public/images/apple-touch-icon.png">
    
        <style>
            .public-nav-logo {
                width: 235px;
                height: auto;
                max-height: 72px;
                object-fit: contain;
                display: block;
            }



            .public-compact-header {
                position: sticky;
                top: 0;
                z-index: 1030;
                min-height: 72px;
                padding-top: 0 !important;
                padding-bottom: 0 !important;
                box-shadow: 0 .35rem 1.25rem rgba(31, 38, 135, .08);
            }

            .public-compact-header .container-fluid {
                min-height: 72px;
            }

            .public-compact-header .navbar-brand {
                padding-top: 0 !important;
                padding-bottom: 0 !important;
                margin-right: 1rem;
            }

            .public-compact-header .public-nav-logo {
                width: 235px;
                max-height: 64px;
            }

            .public-compact-header .nav-link {
                padding-top: .35rem;
                padding-bottom: .35rem;
            }

            .landing-section {
                padding: 5rem 0;
            }

            .landing-section-compact {
                padding: 3rem 0 2rem;
            }

            .section-kicker {
                letter-spacing: .12em;
                text-transform: uppercase;
                font-size: .78rem;
                font-weight: 800;
                color: #6c63ff;
            }

            .landing-card {
                border: 0;
                border-radius: 1.25rem;
                box-shadow: 0 1rem 2.5rem rgba(31, 38, 135, .08);
                height: 100%;
            }

            .feature-icon {
                width: 3rem;
                height: 3rem;
                border-radius: 1rem;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                color: #fff;
                background: linear-gradient(135deg, #1f2cf3 0%, #7b22d8 52%, #e0188a 100%);
                font-size: 1.25rem;
            }

            .soft-gradient-card {
                border: 0;
                border-radius: 1.5rem;
                background: linear-gradient(135deg, rgba(31, 44, 243, .08), rgba(224, 24, 138, .08));
            }

            .hero-copy {
                max-width: 560px;
            }

            .hero-actions {
                display: flex;
                flex-wrap: nowrap;
                gap: .85rem;
                align-items: stretch;
                justify-content: center;
                max-width: 560px;
                width: 100%;
            }

            @media (min-width: 1400px) {
                .hero-actions {
                    justify-content: flex-start;
                    max-width: 560px;
                }
            }

            .hero-action-btn {
                flex: 0 1 170px;
                min-width: 150px;
                min-height: 66px;
                border-radius: 14px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: .55rem;
                padding: .85rem .85rem;
                font-size: .98rem;
                font-weight: 800;
                line-height: 1;
                text-align: center;
                white-space: nowrap;
            }

            .hero-action-btn span {
                white-space: nowrap;
                display: inline-block;
            }

            .hero-action-btn i {
                font-size: 1.05rem;
                flex: 0 0 auto;
            }

            .hero-action-btn.btn-gradient-main {
                color: #fff;
                border: 0;
                background: linear-gradient(135deg, #1f2cf3 0%, #7b22d8 55%, #e0188a 100%);
                box-shadow: 0 .85rem 1.8rem rgba(95, 49, 232, .18);
            }

            .hero-action-btn.btn-outline-gradient-main {
                color: #4535d8;
                border: 2px solid rgba(95, 49, 232, .78);
                background: #fff;
            }

            .hero-action-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 .95rem 2rem rgba(95, 49, 232, .16);
            }

            .check-list {
                list-style: none;
                padding-left: 0;
                margin-bottom: 0;
            }

            .check-list li {
                display: flex;
                gap: .65rem;
                align-items: flex-start;
                margin-bottom: .75rem;
                color: #5f6674;
            }

            .check-list li i {
                color: #5f31e8;
                margin-top: .15rem;
            }

            .feature-pill {
                border: 1px solid rgba(95, 49, 232, .14);
                border-radius: 999px;
                padding: .75rem 1rem;
                background: #fff;
                box-shadow: 0 .5rem 1.25rem rgba(31, 38, 135, .05);
                font-weight: 700;
                color: #343b4a;
            }



            /* Compact bottom area: prevent the final CTA/footer from creating a tall empty block. */
            body {
                min-height: auto !important;
            }

            #contact.landing-section {
                padding: 2rem 0 1rem !important;
            }

            #contact .landing-card {
                height: auto !important;
                min-height: 0 !important;
                padding: 1.5rem !important;
                margin-bottom: 0 !important;
            }

            #contact .display-5 {
                font-size: 2rem;
                margin-bottom: .5rem !important;
            }

            #contact .lead {
                font-size: 1rem;
                margin-bottom: 1rem !important;
            }

            .site-footer {
                padding: .85rem 0 !important;
                margin: 0 !important;
                background: #fff !important;
            }

            .public-lang-switch {
                display: inline-flex;
                align-items: center;
                gap: .25rem;
                border: 1px solid rgba(95, 49, 232, .18);
                border-radius: 999px;
                padding: .2rem;
                background: #fff;
            }

            .public-lang-switch button {
                border: 0;
                border-radius: 999px;
                background: transparent;
                color: #5f6674;
                font-weight: 800;
                font-size: .72rem;
                padding: .25rem .55rem;
            }

            .public-lang-switch button.is-active {
                background: #5f31e8;
                color: #fff;
            }

            @media (max-width: 575.98px) {
                .public-nav-logo {
                    width: 175px;
                    max-height: 56px;
                }



                .public-compact-header {
                    min-height: 62px;
                }

                .public-compact-header .container-fluid {
                    min-height: 62px;
                }

                .public-compact-header .public-nav-logo {
                    width: 175px;
                    max-height: 54px;
                }

                .hero-actions {
                    flex-wrap: wrap;
                }

                .hero-action-btn {
                    flex: 1 1 100%;
                    min-height: 58px;
                }

                .landing-section {
                    padding: 3.5rem 0;
                }
            }
        </style>
    
<style>
/* Keep public landing CTA labels compact in long languages without changing layout. */
.hero-actions .hero-action-btn { min-width: 0; }
.hero-actions .hero-action-btn span { white-space: nowrap; }
@media (max-width: 768px) {
    .hero-actions .hero-action-btn span { white-space: normal; }
}
</style>
</head>
    <body>
        <main class="flex-shrink-0">
            <!-- Navigation-->
            <nav class="navbar navbar-expand-lg navbar-light bg-white py-1 public-compact-header public-sticky-header">
                <div class="container-fluid px-3 px-lg-4">
                    <a class="navbar-brand d-inline-flex align-items-center" href="index.php"><img src="../../public/images/logoweb.png" alt="Cre8Connect" class="public-nav-logo"></a>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                        <ul class="navbar-nav ms-auto mb-2 mb-lg-0 small fw-bolder align-items-lg-center">
                            <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                            <li class="nav-item"><a class="nav-link" href="#how-it-works">How it works</a></li>
                            <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                            <li class="nav-item"><a class="nav-link" href="register.php">Sign up</a></li>
                            <li class="nav-item"><a class="nav-link" href="login.php">Sign in</a></li>
                            <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
                            <li class="nav-item">
                                <div class="public-lang-switch ms-lg-2" aria-label="Language">
                                    <button type="button" data-lang-choice="en">EN</button>
                                    <button type="button" data-lang-choice="fr">FR</button>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>
            <!-- Header-->
            <header class="py-5">
                <div class="container px-5 pb-5">
                    <div class="row gx-5 align-items-center">
                        <div class="col-xxl-5">
                            <!-- Header text content-->
                            <div class="text-center text-xxl-start">
                                <div class="badge bg-gradient-primary-to-secondary text-white mb-4"><div class="text-uppercase"> Create · Connect · Grow</div></div>
                                <div class="fs-3 fw-light text-muted">For creators, brands, and real digital partnerships</div>
                                <h1 class="display-3 fw-bolder mb-4"><span class="text-gradient d-inline">Where Creators and Brands Build Real Collaborations</span></h1>
                                <p class="lead fw-light text-muted mb-5 hero-copy mx-auto mx-xxl-0">Find offers, launch campaigns, manage contracts, and grow with AI-powered support that keeps final decisions in your hands.</p>
                                <div class="hero-actions mx-auto mx-xxl-0 mb-3">
                                    <a class="btn hero-action-btn btn-gradient-main" href="register.php?role=createur"><i class="bi bi-camera-reels"></i><span data-i18n="public.joinCreator">Join as Creator</span></a>
                                    <a class="btn hero-action-btn btn-outline-gradient-main" href="register.php?role=marque"><i class="bi bi-building"></i><span data-i18n="public.joinBrand">Join as Brand</span></a>
                                    <a class="btn hero-action-btn btn-gradient-main" href="login.php"><i class="bi bi-box-arrow-in-right"></i><span data-i18n="public.signIn">Sign in</span></a>
                                </div>
                            </div>
                        </div>
                        <div class="col-xxl-7">
                            <!-- Header profile picture-->
                            <div class="d-flex justify-content-center mt-5 mt-xxl-0">
                                <div class="profile bg-gradient-primary-to-secondary">
                                    <!-- TIP: For best results, use a photo with a transparent background like the demo example below-->
                                    <!-- Watch a tutorial on how to do this on YouTube (link)-->
                                    <img class="profile-img" src="assets/profile.png" alt="...">
                                    <div class="dots-1">
                                        <!-- SVG Dots-->
                                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 191.6 1215.4" style="enable-background: new 0 0 191.6 1215.4" xml:space="preserve">
                                            <g transform="translate(0.000000,1280.000000) scale(0.100000,-0.100000)">
                                                <path d="M227.7,12788.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C383.7,12801.6,289.7,12808.6,227.7,12788.6z"></path>
                                                <path d="M1507.7,12788.6c-151-50-253-216-222-362c25-119,136-230,254-255c194-41,395,142,375,339c-11,105-90,213-190,262        C1663.7,12801.6,1569.7,12808.6,1507.7,12788.6z"></path>
                                                <path d="M227.7,11508.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C383.7,11521.6,289.7,11528.6,227.7,11508.6z"></path>
                                                <path d="M1507.7,11508.6c-151-50-253-216-222-362c25-119,136-230,254-255c194-41,395,142,375,339c-11,105-90,213-190,262        C1663.7,11521.6,1569.7,11528.6,1507.7,11508.6z"></path>
                                                <path d="M227.7,10228.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C383.7,10241.6,289.7,10248.6,227.7,10228.6z"></path>
                                                <path d="M1507.7,10228.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C1663.7,10241.6,1569.7,10248.6,1507.7,10228.6z"></path>
                                                <path d="M227.7,8948.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C383.7,8961.6,289.7,8968.6,227.7,8948.6z"></path>
                                                <path d="M1507.7,8948.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C1663.7,8961.6,1569.7,8968.6,1507.7,8948.6z"></path>
                                                <path d="M227.7,7668.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C383.7,7681.6,289.7,7688.6,227.7,7668.6z"></path>
                                                <path d="M1507.7,7668.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C1663.7,7681.6,1569.7,7688.6,1507.7,7668.6z"></path>
                                                <path d="M227.7,6388.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C383.7,6401.6,289.7,6408.6,227.7,6388.6z"></path>
                                                <path d="M1507.7,6388.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C1663.7,6401.6,1569.7,6408.6,1507.7,6388.6z"></path>
                                                <path d="M227.7,5108.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C383.7,5121.6,289.7,5128.6,227.7,5108.6z"></path>
                                                <path d="M1507.7,5108.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C1663.7,5121.6,1569.7,5128.6,1507.7,5108.6z"></path>
                                                <path d="M227.7,3828.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C383.7,3841.6,289.7,3848.6,227.7,3828.6z"></path>
                                                <path d="M1507.7,3828.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C1663.7,3841.6,1569.7,3848.6,1507.7,3828.6z"></path>
                                                <path d="M227.7,2548.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C383.7,2561.6,289.7,2568.6,227.7,2548.6z"></path>
                                                <path d="M1507.7,2548.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C1663.7,2561.6,1569.7,2568.6,1507.7,2548.6z"></path>
                                                <path d="M227.7,1268.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C383.7,1281.6,289.7,1288.6,227.7,1268.6z"></path>
                                                <path d="M1507.7,1268.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C1663.7,1281.6,1569.7,1288.6,1507.7,1268.6z"></path>
                                            </g>
                                        </svg>
                                        <!-- END of SVG dots-->
                                    </div>
                                    <div class="dots-2">
                                        <!-- SVG Dots-->
                                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 191.6 1215.4" style="enable-background: new 0 0 191.6 1215.4" xml:space="preserve">
                                            <g transform="translate(0.000000,1280.000000) scale(0.100000,-0.100000)">
                                                <path d="M227.7,12788.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C383.7,12801.6,289.7,12808.6,227.7,12788.6z"></path>
                                                <path d="M1507.7,12788.6c-151-50-253-216-222-362c25-119,136-230,254-255c194-41,395,142,375,339c-11,105-90,213-190,262        C1663.7,12801.6,1569.7,12808.6,1507.7,12788.6z"></path>
                                                <path d="M227.7,11508.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C383.7,11521.6,289.7,11528.6,227.7,11508.6z"></path>
                                                <path d="M1507.7,11508.6c-151-50-253-216-222-362c25-119,136-230,254-255c194-41,395,142,375,339c-11,105-90,213-190,262        C1663.7,11521.6,1569.7,11528.6,1507.7,11508.6z"></path>
                                                <path d="M227.7,10228.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C383.7,10241.6,289.7,10248.6,227.7,10228.6z"></path>
                                                <path d="M1507.7,10228.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C1663.7,10241.6,1569.7,10248.6,1507.7,10228.6z"></path>
                                                <path d="M227.7,8948.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C383.7,8961.6,289.7,8968.6,227.7,8948.6z"></path>
                                                <path d="M1507.7,8948.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C1663.7,8961.6,1569.7,8968.6,1507.7,8948.6z"></path>
                                                <path d="M227.7,7668.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C383.7,7681.6,289.7,7688.6,227.7,7668.6z"></path>
                                                <path d="M1507.7,7668.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C1663.7,7681.6,1569.7,7688.6,1507.7,7668.6z"></path>
                                                <path d="M227.7,6388.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C383.7,6401.6,289.7,6408.6,227.7,6388.6z"></path>
                                                <path d="M1507.7,6388.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C1663.7,6401.6,1569.7,6408.6,1507.7,6388.6z"></path>
                                                <path d="M227.7,5108.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C383.7,5121.6,289.7,5128.6,227.7,5108.6z"></path>
                                                <path d="M1507.7,5108.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C1663.7,5121.6,1569.7,5128.6,1507.7,5108.6z"></path>
                                                <path d="M227.7,3828.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C383.7,3841.6,289.7,3848.6,227.7,3828.6z"></path>
                                                <path d="M1507.7,3828.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C1663.7,3841.6,1569.7,3848.6,1507.7,3828.6z"></path>
                                                <path d="M227.7,2548.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C383.7,2561.6,289.7,2568.6,227.7,2548.6z"></path>
                                                <path d="M1507.7,2548.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C1663.7,2561.6,1569.7,2568.6,1507.7,2548.6z"></path>
                                                <path d="M227.7,1268.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C383.7,1281.6,289.7,1288.6,227.7,1268.6z"></path>
                                                <path d="M1507.7,1268.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C1663.7,1281.6,1569.7,1288.6,1507.7,1268.6z"></path>
                                            </g>
                                        </svg>
                                        <!-- END of SVG dots-->
                                    </div>
                                    <div class="dots-3">
                                        <!-- SVG Dots-->
                                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 191.6 1215.4" style="enable-background: new 0 0 191.6 1215.4" xml:space="preserve">
                                            <g transform="translate(0.000000,1280.000000) scale(0.100000,-0.100000)">
                                                <path d="M227.7,12788.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C383.7,12801.6,289.7,12808.6,227.7,12788.6z"></path>
                                                <path d="M1507.7,12788.6c-151-50-253-216-222-362c25-119,136-230,254-255c194-41,395,142,375,339c-11,105-90,213-190,262        C1663.7,12801.6,1569.7,12808.6,1507.7,12788.6z"></path>
                                                <path d="M227.7,11508.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C383.7,11521.6,289.7,11528.6,227.7,11508.6z"></path>
                                                <path d="M1507.7,11508.6c-151-50-253-216-222-362c25-119,136-230,254-255c194-41,395,142,375,339c-11,105-90,213-190,262        C1663.7,11521.6,1569.7,11528.6,1507.7,11508.6z"></path>
                                                <path d="M227.7,10228.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C383.7,10241.6,289.7,10248.6,227.7,10228.6z"></path>
                                                <path d="M1507.7,10228.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C1663.7,10241.6,1569.7,10248.6,1507.7,10228.6z"></path>
                                                <path d="M227.7,8948.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C383.7,8961.6,289.7,8968.6,227.7,8948.6z"></path>
                                                <path d="M1507.7,8948.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C1663.7,8961.6,1569.7,8968.6,1507.7,8948.6z"></path>
                                                <path d="M227.7,7668.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C383.7,7681.6,289.7,7688.6,227.7,7668.6z"></path>
                                                <path d="M1507.7,7668.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C1663.7,7681.6,1569.7,7688.6,1507.7,7668.6z"></path>
                                                <path d="M227.7,6388.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C383.7,6401.6,289.7,6408.6,227.7,6388.6z"></path>
                                                <path d="M1507.7,6388.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C1663.7,6401.6,1569.7,6408.6,1507.7,6388.6z"></path>
                                                <path d="M227.7,5108.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C383.7,5121.6,289.7,5128.6,227.7,5108.6z"></path>
                                                <path d="M1507.7,5108.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C1663.7,5121.6,1569.7,5128.6,1507.7,5108.6z"></path>
                                                <path d="M227.7,3828.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C383.7,3841.6,289.7,3848.6,227.7,3828.6z"></path>
                                                <path d="M1507.7,3828.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C1663.7,3841.6,1569.7,3848.6,1507.7,3828.6z"></path>
                                                <path d="M227.7,2548.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C383.7,2561.6,289.7,2568.6,227.7,2548.6z"></path>
                                                <path d="M1507.7,2548.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C1663.7,2561.6,1569.7,2568.6,1507.7,2548.6z"></path>
                                                <path d="M227.7,1268.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C383.7,1281.6,289.7,1288.6,227.7,1268.6z"></path>
                                                <path d="M1507.7,1268.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C1663.7,1281.6,1569.7,1288.6,1507.7,1268.6z"></path>
                                            </g>
                                        </svg>
                                        <!-- END of SVG dots-->
                                    </div>
                                    <div class="dots-4">
                                        <!-- SVG Dots-->
                                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 191.6 1215.4" style="enable-background: new 0 0 191.6 1215.4" xml:space="preserve">
                                            <g transform="translate(0.000000,1280.000000) scale(0.100000,-0.100000)">
                                                <path d="M227.7,12788.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C383.7,12801.6,289.7,12808.6,227.7,12788.6z"></path>
                                                <path d="M1507.7,12788.6c-151-50-253-216-222-362c25-119,136-230,254-255c194-41,395,142,375,339c-11,105-90,213-190,262        C1663.7,12801.6,1569.7,12808.6,1507.7,12788.6z"></path>
                                                <path d="M227.7,11508.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C383.7,11521.6,289.7,11528.6,227.7,11508.6z"></path>
                                                <path d="M1507.7,11508.6c-151-50-253-216-222-362c25-119,136-230,254-255c194-41,395,142,375,339c-11,105-90,213-190,262        C1663.7,11521.6,1569.7,11528.6,1507.7,11508.6z"></path>
                                                <path d="M227.7,10228.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C383.7,10241.6,289.7,10248.6,227.7,10228.6z"></path>
                                                <path d="M1507.7,10228.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C1663.7,10241.6,1569.7,10248.6,1507.7,10228.6z"></path>
                                                <path d="M227.7,8948.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C383.7,8961.6,289.7,8968.6,227.7,8948.6z"></path>
                                                <path d="M1507.7,8948.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C1663.7,8961.6,1569.7,8968.6,1507.7,8948.6z"></path>
                                                <path d="M227.7,7668.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C383.7,7681.6,289.7,7688.6,227.7,7668.6z"></path>
                                                <path d="M1507.7,7668.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C1663.7,7681.6,1569.7,7688.6,1507.7,7668.6z"></path>
                                                <path d="M227.7,6388.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C383.7,6401.6,289.7,6408.6,227.7,6388.6z"></path>
                                                <path d="M1507.7,6388.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C1663.7,6401.6,1569.7,6408.6,1507.7,6388.6z"></path>
                                                <path d="M227.7,5108.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C383.7,5121.6,289.7,5128.6,227.7,5108.6z"></path>
                                                <path d="M1507.7,5108.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C1663.7,5121.6,1569.7,5128.6,1507.7,5108.6z"></path>
                                                <path d="M227.7,3828.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C383.7,3841.6,289.7,3848.6,227.7,3828.6z"></path>
                                                <path d="M1507.7,3828.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C1663.7,3841.6,1569.7,3848.6,1507.7,3828.6z"></path>
                                                <path d="M227.7,2548.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C383.7,2561.6,289.7,2568.6,227.7,2548.6z"></path>
                                                <path d="M1507.7,2548.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C1663.7,2561.6,1569.7,2568.6,1507.7,2548.6z"></path>
                                                <path d="M227.7,1268.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C383.7,1281.6,289.7,1288.6,227.7,1268.6z"></path>
                                                <path d="M1507.7,1268.6c-105-35-200-141-222-248c-43-206,163-412,369-369c155,32,275,190,260,339c-11,105-90,213-190,262        C1663.7,1281.6,1569.7,1288.6,1507.7,1268.6z"></path>
                                            </g>
                                        </svg>
                                        <!-- END of SVG dots-->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
            <!-- How It Works Section-->
            <section id="how-it-works" class="landing-section bg-light">
                <div class="container px-5">
                    <div class="text-center mb-5">
                        <div class="section-kicker mb-2">Simple collaboration flow</div>
                        <h2 class="display-5 fw-bolder"><span class="text-gradient d-inline">How Cre8Connect works</span></h2>
                        <p class="lead fw-light text-muted mb-0">From opportunity to signed collaboration, every step is organized in one workspace.</p>
                    </div>
                    <div class="row gx-4 gy-4">
                        <div class="col-md-4">
                            <div class="card landing-card p-4">
                                <div class="feature-icon mb-3"><i class="bi bi-megaphone"></i></div>
                                <h3 class="h5 fw-bolder">Brands publish opportunities</h3>
                                <p class="text-muted mb-0">Brands create offers and campaigns with clear budgets, objectives, products, and collaboration expectations.</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card landing-card p-4">
                                <div class="feature-icon mb-3"><i class="bi bi-chat-dots"></i></div>
                                <h3 class="h5 fw-bolder">Creators apply and negotiate</h3>
                                <p class="text-muted mb-0">Creators send applications, propose budgets and timelines, follow their status, and negotiate professionally.</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card landing-card p-4">
                                <div class="feature-icon mb-3"><i class="bi bi-file-earmark-check"></i></div>
                                <h3 class="h5 fw-bolder">Collaborations become contracts</h3>
                                <p class="text-muted mb-0">Accepted applications can become structured contracts with dates, amounts, deliverables, and clear responsibilities.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Creator and Brand Section-->
            <section id="about" class="landing-section bg-white">
                <div class="container px-5">
                    <div class="row gx-5 align-items-center">
                        <div class="col-lg-5 mb-5 mb-lg-0">
                            <div class="section-kicker mb-2">Two sides, one platform</div>
                            <h2 class="display-5 fw-bolder mb-4"><span class="text-gradient d-inline">Built for creators and brands</span></h2>
                            <p class="lead fw-light text-muted">Cre8Connect helps creators find serious collaboration opportunities and helps brands manage campaigns from idea to contract.</p>
                        </div>
                        <div class="col-lg-7">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="card landing-card p-4">
                                        <div class="feature-icon mb-3"><i class="bi bi-camera-reels"></i></div>
                                        <h3 class="h4 fw-bolder" data-i18n="public.forCreators">For Creators</h3>
                                        <ul class="check-list mt-3">
                                            <li><i class="bi bi-check-circle-fill"></i><span data-i18n="public.creatorBullet.discover">Discover collaboration offers.</span></li>
                                            <li><i class="bi bi-check-circle-fill"></i><span data-i18n="public.creatorBullet.apply">Apply to campaigns and negotiate terms.</span></li>
                                            <li><i class="bi bi-check-circle-fill"></i><span data-i18n="public.creatorBullet.publish">Publish posts and grow visibility.</span></li>
                                            <li><i class="bi bi-check-circle-fill"></i><span data-i18n="public.creatorBullet.events">Join events, webinars, and forums.</span></li>
                                            <li><i class="bi bi-check-circle-fill"></i><span data-i18n="public.creatorBullet.track">Track performance and engagement.</span></li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card landing-card p-4">
                                        <div class="feature-icon mb-3"><i class="bi bi-briefcase"></i></div>
                                        <h3 class="h4 fw-bolder" data-i18n="public.forBrands">For Brands</h3>
                                        <ul class="check-list mt-3">
                                            <li><i class="bi bi-check-circle-fill"></i><span data-i18n="public.brandBullet.create">Create offers and campaigns.</span></li>
                                            <li><i class="bi bi-check-circle-fill"></i><span data-i18n="public.brandBullet.manage">Manage products and contracts.</span></li>
                                            <li><i class="bi bi-check-circle-fill"></i><span data-i18n="public.brandBullet.review">Review creator applications.</span></li>
                                            <li><i class="bi bi-check-circle-fill"></i><span data-i18n="public.brandBullet.negotiate">Negotiate and accept collaborations.</span></li>
                                            <li><i class="bi bi-check-circle-fill"></i><span data-i18n="public.brandBullet.track">Track campaign performance.</span></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- AI and Safety Section-->
            <section id="ai" class="landing-section bg-light">
                <div class="container px-5">
                    <div class="text-center mb-5">
                        <div class="section-kicker mb-2">AI-powered collaboration</div>
                        <h2 class="display-5 fw-bolder"><span class="text-gradient d-inline">AI that helps creators and brands move faster</span></h2>
                        <p class="lead fw-light text-muted mb-0">Cre8Connect includes practical assistants for posts, campaigns, applications, contracts, and safer collaboration decisions.</p>
                    </div>
                    <div class="row gx-4 gy-4 align-items-stretch">
                        <div class="col-lg-6">
                            <div class="card landing-card soft-gradient-card p-4 p-lg-5">
                                <div class="feature-icon mb-3"><i class="bi bi-stars"></i></div>
                                <h3 class="h2 fw-bolder mb-3"><span class="text-gradient d-inline" data-i18n="public.aiForCreators">For creators</span></h3>
                                <ul class="check-list">
                                    <li><i class="bi bi-check-circle-fill"></i><span><strong>Cre8Pilot</strong><span data-i18n="public.aiCreator.cre8pilotText"> helps improve applications, summarize opportunities, and prepare negotiation messages.</span></span></li>
                                    <li><i class="bi bi-check-circle-fill"></i><span><strong>Crea</strong><span data-i18n="public.aiCreator.creaText"> supports post creation with smarter content ideas and writing help.</span></span></li>
                                    <li><i class="bi bi-check-circle-fill"></i><span><strong data-i18n="public.aiCreator.personalizedTitle">Personalized campaign suggestions</strong><span data-i18n="public.aiCreator.personalizedText"> recommend opportunities that match the creator profile and interests.</span></span></li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-lg-6" id="security">
                            <div class="card landing-card p-4 p-lg-5">
                                <div class="feature-icon mb-3"><i class="bi bi-magic"></i></div>
                                <h3 class="h2 fw-bolder mb-3"><span class="text-gradient d-inline" data-i18n="public.aiForBrands">For brands</span></h3>
                                <ul class="check-list">
                                    <li><i class="bi bi-check-circle-fill"></i><span><strong data-i18n="public.aiBrand.campaignTitle">Generate a campaign with AI</strong><span data-i18n="public.aiBrand.campaignText"> from a product, objective, audience, and budget.</span></span></li>
                                    <li><i class="bi bi-check-circle-fill"></i><span><strong data-i18n="public.aiBrand.contractTitle">Generate a contract with AI</strong><span data-i18n="public.aiBrand.contractText"> after an accepted collaboration, then review it before use.</span></span></li>
                                    <li><i class="bi bi-check-circle-fill"></i><span><strong>Cre8Shield</strong><span data-i18n="public.aiBrand.shieldText"> helps warn users about suspicious links, off-platform payment pressure, fake support messages, and risky behavior.</span></span></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <p class="text-center fw-bolder text-muted mt-4 mb-0">AI helps prepare and suggest. Creators and brands always keep the final decision.</p>
                </div>
            </section>

            <!-- Community Section-->
            <section id="community" class="landing-section bg-white">
                <div class="container px-5">
                    <div class="row gx-5 align-items-center">
                        <div class="col-lg-6 mb-4 mb-lg-0">
                            <div class="section-kicker mb-2">Events & community</div>
                            <h2 class="display-6 fw-bolder"><span class="text-gradient d-inline">Learn, network, and exchange ideas</span></h2>
                            <p class="lead fw-light text-muted mb-0">Cre8Connect supports workshops, webinars, meetups, and forums so users can learn from each other and build stronger professional relationships.</p>
                        </div>
                        <div class="col-lg-6">
                            <div class="row g-3">
                                <div class="col-sm-6"><div class="feature-pill"><i class="bi bi-calendar-event me-2 text-primary"></i> <span data-i18n="public.community.event">Events</span></div></div>
                                <div class="col-sm-6"><div class="feature-pill"><i class="bi bi-easel me-2 text-primary"></i> <span data-i18n="public.community.webinars">Webinars</span></div></div>
                                <div class="col-sm-6"><div class="feature-pill"><i class="bi bi-tools me-2 text-primary"></i> <span data-i18n="public.community.workshops">Workshops</span></div></div>
                                <div class="col-sm-6"><div class="feature-pill"><i class="bi bi-chat-square-text me-2 text-primary"></i> <span data-i18n="public.community.forums">Forums</span></div></div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Features Section-->
            <section id="features" class="landing-section bg-light">
                <div class="container px-5">
                    <div class="text-center mb-5">
                        <div class="section-kicker mb-2">Creator & brand workspace</div>
                        <h2 class="display-5 fw-bolder"><span class="text-gradient d-inline">Everything needed for a collaboration</span></h2>
                        <p class="lead fw-light text-muted mb-0">A focused workspace for creators and brands, from discovery to content delivery.</p>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6 col-lg-4"><div class="feature-pill"><i class="bi bi-broadcast me-2 text-primary"></i> <span data-i18n="public.feature.offers">Collaboration Offers</span></div></div>
                        <div class="col-md-6 col-lg-4"><div class="feature-pill"><i class="bi bi-chat-left-text me-2 text-primary"></i> <span data-i18n="public.feature.applications">Applications & Negotiation</span></div></div>
                        <div class="col-md-6 col-lg-4"><div class="feature-pill"><i class="bi bi-kanban me-2 text-primary"></i> <span data-i18n="public.feature.campaignPlanning">Campaign Planning</span></div></div>
                        <div class="col-md-6 col-lg-4"><div class="feature-pill"><i class="bi bi-box-seam me-2 text-primary"></i> <span data-i18n="public.feature.products">Product Showcases</span></div></div>
                        <div class="col-md-6 col-lg-4"><div class="feature-pill"><i class="bi bi-file-earmark-text me-2 text-primary"></i> <span data-i18n="public.feature.contracts">Collaboration Contracts</span></div></div>
                        <div class="col-md-6 col-lg-4"><div class="feature-pill"><i class="bi bi-pencil-square me-2 text-primary"></i> <span data-i18n="public.feature.posts">Creator Posts with Crea</span></div></div>
                        <div class="col-md-6 col-lg-4"><div class="feature-pill"><i class="bi bi-lightbulb me-2 text-primary"></i> <span data-i18n="public.feature.campaignSuggestions">AI Campaign Suggestions</span></div></div>
                        <div class="col-md-6 col-lg-4"><div class="feature-pill"><i class="bi bi-magic me-2 text-primary"></i> <span data-i18n="public.feature.aiGeneration">AI Campaign & Contract Generation</span></div></div>
                        <div class="col-md-6 col-lg-4"><div class="feature-pill"><i class="bi bi-people me-2 text-primary"></i> <span data-i18n="public.feature.eventsForums">Events & Forums</span></div></div>
                    </div>
                </div>
            </section>


            <!-- Visitor collaboration CTA Section-->
            <section class="py-5 bg-gradient-primary-to-secondary text-white">
                <div class="container px-5 my-5">
                    <div class="text-center">
                        <h2 class="display-4 fw-bolder mb-4">Let's build something together</h2>
                    </div>
                </div>
            </section>

            <!-- Final CTA Section-->
            <section id="contact" class="landing-section landing-section-compact bg-white">
                <div class="container px-5">
                    <div class="card landing-card soft-gradient-card text-center">
                        <div class="section-kicker mb-2">Start now</div>
                        <h2 class="display-5 fw-bolder mb-3"><span class="text-gradient d-inline">Ready to start your next collaboration?</span></h2>
                        <p class="lead fw-light text-muted mb-4">Join Cre8Connect and connect with the right people for your next campaign.</p>
                        <div class="d-grid gap-3 d-sm-flex justify-content-sm-center">
                            <a class="btn btn-primary btn-lg px-5 py-3 fs-6 fw-bolder" href="register.php">Create Account</a>
                            <a class="btn btn-outline-dark btn-lg px-5 py-3 fs-6 fw-bolder" href="login.php">Sign in</a>
                        </div>
                    </div>
                </div>
            </section>
        </main>
        <!-- Footer-->
        <?php require __DIR__ . '/../layout/footer.php'; ?>
        <!-- Bootstrap core JS-->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
        <!-- Core theme JS-->
        <script src="js/scripts.js"></script>
        <script src="../layout/front-translate.js"></script>
        <script>
        (function () {
            var translations = {
                en: {
                    'public.home': 'Home',
                    'public.howItWorks': 'How it works',
                    'public.features': 'Features',
                    'public.signUp': 'Sign up',
                    'public.signIn': 'Sign in',
                    'public.contact': 'Contact',
                    'public.badge': 'Create · Connect · Grow',
                    'public.heroKicker': 'For creators, brands, and real digital partnerships',
                    'public.heroTitle': 'Where Creators and Brands Build Real Collaborations',
                    'public.heroCopy': 'Find offers, launch campaigns, manage contracts, and grow with AI-powered support that keeps final decisions in your hands.',
                    'public.joinCreator': 'Join as Creator',
                    'public.joinBrand': 'Join as Brand',
                    'public.flowKicker': 'Simple collaboration flow',
                    'public.flowTitle': 'How Cre8Connect works',
                    'public.flowCopy': 'From opportunity to signed collaboration, every step is organized in one workspace.',
                    'public.brandsPublish': 'Brands publish opportunities',
                    'public.brandsPublishCopy': 'Brands create offers and campaigns with clear budgets, objectives, products, and collaboration expectations.',
                    'public.creatorsApply': 'Creators apply and negotiate',
                    'public.creatorsApplyCopy': 'Creators send applications, propose budgets and timelines, follow their status, and negotiate professionally.',
                    'public.contractsTitle': 'Collaborations become contracts',
                    'public.contractsCopy': 'Accepted applications can become structured contracts with dates, amounts, deliverables, and clear responsibilities.',
                    'public.twoSides': 'Two sides, one platform',
                    'public.builtFor': 'Built for creators and brands',
                    'public.builtForCopy': 'Cre8Connect helps creators find serious collaboration opportunities and helps brands manage campaigns from idea to contract.',
                    'public.forCreators': 'For Creators',
                    'public.forBrands': 'For Brands',
                    'public.aiKicker': 'AI-powered collaboration',
                    'public.aiTitle': 'AI that helps creators and brands move faster',
                    'public.aiCopy': 'Cre8Connect includes practical assistants for posts, campaigns, applications, contracts, and safer collaboration decisions.',
                    'public.aiDecision': 'AI helps prepare and suggest. Creators and brands always keep the final decision.',
                    'public.communityKicker': 'Events & community',
                    'public.communityTitle': 'Learn, network, and exchange ideas',
                    'public.communityCopy': 'Cre8Connect supports workshops, webinars, meetups, and forums so users can learn from each other and build stronger professional relationships.',
                    'public.workspaceKicker': 'Creator & brand workspace',
                    'public.workspaceTitle': 'Everything needed for a collaboration',
                    'public.workspaceCopy': 'A focused workspace for creators and brands, from discovery to content delivery.',
                    'public.buildTogether': "Let's build something together",
                    'public.startNow': 'Start now',
                    'public.ready': 'Ready to start your next collaboration?',
                    'public.readyCopy': 'Join Cre8Connect and connect with the right people for your next campaign.',
                    'public.createAccount': 'Create Account',
                    'public.privacy': 'Privacy',
                    'public.terms': 'Terms',
                    'public.copyright': 'Copyright © cre8connect 2026'
                },
                fr: {
                    'public.home': 'Accueil',
                    'public.howItWorks': 'Comment ca marche',
                    'public.features': 'Fonctionnalites',
                    'public.signUp': 'S inscrire',
                    'public.signIn': 'Se connecter',
                    'public.contact': 'Contact',
                    'public.badge': 'Creer · Connecter · Grandir',
                    'public.heroKicker': 'Pour les createurs, les marques et les vrais partenariats digitaux',
                    'public.heroTitle': 'La ou createurs et marques construisent de vraies collaborations',
                    'public.heroCopy': 'Trouvez des offres, lancez des campagnes, gerez les contrats et evoluez avec une assistance IA tout en gardant la decision finale.',
                    'public.joinCreator': 'Rejoindre comme createur',
                    'public.joinBrand': 'Rejoindre comme marque',
                    'public.flowKicker': 'Parcours de collaboration simple',
                    'public.flowTitle': 'Comment fonctionne Cre8Connect',
                    'public.flowCopy': 'De l opportunite au contrat signe, chaque etape est organisee dans un seul espace.',
                    'public.brandsPublish': 'Les marques publient des opportunites',
                    'public.brandsPublishCopy': 'Les marques creent des offres et campagnes avec budgets, objectifs, produits et attentes clairs.',
                    'public.creatorsApply': 'Les createurs postulent et negocient',
                    'public.creatorsApplyCopy': 'Les createurs envoient des candidatures, proposent budgets et delais, suivent leur statut et negocient professionnellement.',
                    'public.contractsTitle': 'Les collaborations deviennent des contrats',
                    'public.contractsCopy': 'Les candidatures acceptees peuvent devenir des contrats structures avec dates, montants, livrables et responsabilites claires.',
                    'public.twoSides': 'Deux cotes, une plateforme',
                    'public.builtFor': 'Concu pour les createurs et les marques',
                    'public.builtForCopy': 'Cre8Connect aide les createurs a trouver de vraies opportunites et aide les marques a gerer leurs campagnes de l idee au contrat.',
                    'public.forCreators': 'Pour les createurs',
                    'public.forBrands': 'Pour les marques',
                    'public.aiKicker': 'Collaboration assistee par IA',
                    'public.aiTitle': 'Une IA qui aide createurs et marques a avancer plus vite',
                    'public.aiCopy': 'Cre8Connect inclut des assistants pratiques pour posts, campagnes, candidatures, contrats et decisions plus sures.',
                    'public.aiDecision': 'L IA aide a preparer et suggerer. Les createurs et marques gardent toujours la decision finale.',
                    'public.communityKicker': 'Evenements et communaute',
                    'public.communityTitle': 'Apprendre, reseauter et echanger des idees',
                    'public.communityCopy': 'Cre8Connect soutient ateliers, webinaires, meetups et forums afin que les utilisateurs apprennent ensemble et construisent de meilleures relations professionnelles.',
                    'public.workspaceKicker': 'Espace createur et marque',
                    'public.workspaceTitle': 'Tout le necessaire pour collaborer',
                    'public.workspaceCopy': 'Un espace concentre pour createurs et marques, de la decouverte a la livraison du contenu.',
                    'public.buildTogether': 'Construisons quelque chose ensemble',
                    'public.startNow': 'Commencer maintenant',
                    'public.ready': 'Pret a lancer votre prochaine collaboration ?',
                    'public.readyCopy': 'Rejoignez Cre8Connect et connectez-vous aux bonnes personnes pour votre prochaine campagne.',
                    'public.createAccount': 'Creer un compte',
                    'public.privacy': 'Confidentialite',
                    'public.terms': 'Conditions',
                    'public.copyright': 'Copyright © cre8connect 2026'
                }
            };
            var aliases = {
                'Home': 'public.home',
                'How it works': 'public.howItWorks',
                'Features': 'public.features',
                'Sign up': 'public.signUp',
                'Sign in': 'public.signIn',
                'Contact': 'public.contact',
                'Create · Connect · Grow': 'public.badge',
                'For creators, brands, and real digital partnerships': 'public.heroKicker',
                'Where Creators and Brands Build Real Collaborations': 'public.heroTitle',
                'Find offers, launch campaigns, manage contracts, and grow with AI-powered support that keeps final decisions in your hands.': 'public.heroCopy',
                'Join as Creator': 'public.joinCreator',
                'Join as Brand': 'public.joinBrand',
                'Simple collaboration flow': 'public.flowKicker',
                'How Cre8Connect works': 'public.flowTitle',
                'From opportunity to signed collaboration, every step is organized in one workspace.': 'public.flowCopy',
                'Brands publish opportunities': 'public.brandsPublish',
                'Brands create offers and campaigns with clear budgets, objectives, products, and collaboration expectations.': 'public.brandsPublishCopy',
                'Creators apply and negotiate': 'public.creatorsApply',
                'Creators send applications, propose budgets and timelines, follow their status, and negotiate professionally.': 'public.creatorsApplyCopy',
                'Collaborations become contracts': 'public.contractsTitle',
                'Accepted applications can become structured contracts with dates, amounts, deliverables, and clear responsibilities.': 'public.contractsCopy',
                'Two sides, one platform': 'public.twoSides',
                'Built for creators and brands': 'public.builtFor',
                'Cre8Connect helps creators find serious collaboration opportunities and helps brands manage campaigns from idea to contract.': 'public.builtForCopy',
                'For Creators': 'public.forCreators',
                'For Brands': 'public.forBrands',
                'AI-powered collaboration': 'public.aiKicker',
                'AI that helps creators and brands move faster': 'public.aiTitle',
                'Cre8Connect includes practical assistants for posts, campaigns, applications, contracts, and safer collaboration decisions.': 'public.aiCopy',
                'AI helps prepare and suggest. Creators and brands always keep the final decision.': 'public.aiDecision',
                'Events & community': 'public.communityKicker',
                'Learn, network, and exchange ideas': 'public.communityTitle',
                'Cre8Connect supports workshops, webinars, meetups, and forums so users can learn from each other and build stronger professional relationships.': 'public.communityCopy',
                'Creator & brand workspace': 'public.workspaceKicker',
                'Everything needed for a collaboration': 'public.workspaceTitle',
                'A focused workspace for creators and brands, from discovery to content delivery.': 'public.workspaceCopy',
                "Let's build something together": 'public.buildTogether',
                'Start now': 'public.startNow',
                'Ready to start your next collaboration?': 'public.ready',
                'Join Cre8Connect and connect with the right people for your next campaign.': 'public.readyCopy',
                'Create Account': 'public.createAccount',
                'Privacy': 'public.privacy',
                'Terms': 'public.terms',
                'Copyright © cre8connect 2026': 'public.copyright'
            };

            // Landing page translation fixes for the current design.
            // Keep the existing layout untouched; only add missing static labels.
            Object.assign(translations.en, {
                'public.creatorBullet.discover': 'Discover collaboration offers.',
                'public.creatorBullet.apply': 'Apply to campaigns and negotiate terms.',
                'public.creatorBullet.publish': 'Publish posts and grow visibility.',
                'public.creatorBullet.events': 'Join events, webinars, and forums.',
                'public.creatorBullet.track': 'Track performance and engagement.',
                'public.brandBullet.create': 'Create offers and campaigns.',
                'public.brandBullet.manage': 'Manage products and contracts.',
                'public.brandBullet.review': 'Review creator applications.',
                'public.brandBullet.negotiate': 'Negotiate and accept collaborations.',
                'public.brandBullet.track': 'Track campaign performance.',
                'public.aiForCreators': 'For creators',
                'public.aiForBrands': 'For brands',
                'public.aiCreator.cre8pilotText': ' helps improve applications, summarize opportunities, and prepare negotiation messages.',
                'public.aiCreator.creaText': ' supports post creation with smarter content ideas and writing help.',
                'public.aiCreator.personalizedTitle': 'Personalized campaign suggestions',
                'public.aiCreator.personalizedText': ' recommend opportunities that match the creator profile and interests.',
                'public.aiBrand.campaignTitle': 'Generate a campaign with AI',
                'public.aiBrand.campaignText': ' from a product, objective, audience, and budget.',
                'public.aiBrand.contractTitle': 'Generate a contract with AI',
                'public.aiBrand.contractText': ' after an accepted collaboration, then review it before use.',
                'public.aiBrand.shieldText': ' helps warn users about suspicious links, off-platform payment pressure, fake support messages, and risky behavior.',
                'public.community.event': 'Events',
                'public.community.webinars': 'Webinars',
                'public.community.workshops': 'Workshops',
                'public.community.forums': 'Forums',
                'public.feature.offers': 'Collaboration Offers',
                'public.feature.applications': 'Applications & Negotiation',
                'public.feature.campaignPlanning': 'Campaign Planning',
                'public.feature.products': 'Product Showcases',
                'public.feature.contracts': 'Collaboration Contracts',
                'public.feature.posts': 'Creator Posts with Crea',
                'public.feature.campaignSuggestions': 'AI Campaign Suggestions',
                'public.feature.aiGeneration': 'AI Campaign & Contract Generation',
                'public.feature.eventsForums': 'Events & Forums'
            });
            Object.assign(translations.fr, {
                // Short hero CTA labels prevent the French version from overflowing.
                'public.joinCreator': 'Créateur',
                'public.joinBrand': 'Marque',
                'public.creatorBullet.discover': 'Découvrez des offres de collaboration.',
                'public.creatorBullet.apply': 'Postulez aux campagnes et négociez les conditions.',
                'public.creatorBullet.publish': 'Publiez des posts et augmentez votre visibilité.',
                'public.creatorBullet.events': 'Participez aux événements, webinaires et forums.',
                'public.creatorBullet.track': 'Suivez la performance et l’engagement.',
                'public.brandBullet.create': 'Créez des offres et des campagnes.',
                'public.brandBullet.manage': 'Gérez produits et contrats.',
                'public.brandBullet.review': 'Analysez les candidatures des créateurs.',
                'public.brandBullet.negotiate': 'Négociez et acceptez les collaborations.',
                'public.brandBullet.track': 'Suivez la performance des campagnes.',
                'public.aiForCreators': 'Pour les créateurs',
                'public.aiForBrands': 'Pour les marques',
                'public.aiCreator.cre8pilotText': ' aide à améliorer les candidatures, résumer les opportunités et préparer les messages de négociation.',
                'public.aiCreator.creaText': ' accompagne la création de posts avec des idées de contenu et une aide à la rédaction.',
                'public.aiCreator.personalizedTitle': 'Suggestions de campagnes personnalisées',
                'public.aiCreator.personalizedText': ' recommandent des opportunités adaptées au profil et aux intérêts du créateur.',
                'public.aiBrand.campaignTitle': 'Générer une campagne avec l’IA',
                'public.aiBrand.campaignText': ' à partir d’un produit, d’un objectif, d’une audience et d’un budget.',
                'public.aiBrand.contractTitle': 'Générer un contrat avec l’IA',
                'public.aiBrand.contractText': ' après une collaboration acceptée, puis le vérifier avant utilisation.',
                'public.aiBrand.shieldText': ' aide à signaler les liens suspects, la pression de paiement hors plateforme, les faux messages de support et les comportements risqués.',
                'public.community.event': 'Événements',
                'public.community.webinars': 'Webinaires',
                'public.community.workshops': 'Ateliers',
                'public.community.forums': 'Forums',
                'public.feature.offers': 'Offres de collaboration',
                'public.feature.applications': 'Candidatures et négociation',
                'public.feature.campaignPlanning': 'Planification de campagnes',
                'public.feature.products': 'Présentation des produits',
                'public.feature.contracts': 'Contrats de collaboration',
                'public.feature.posts': 'Posts créateur avec Crea',
                'public.feature.campaignSuggestions': 'Suggestions de campagnes IA',
                'public.feature.aiGeneration': 'Génération IA de campagnes et contrats',
                'public.feature.eventsForums': 'Événements et forums'
            });
            Object.assign(aliases, {
                'Discover collaboration offers.': 'public.creatorBullet.discover',
                'Apply to campaigns and negotiate terms.': 'public.creatorBullet.apply',
                'Publish posts and grow visibility.': 'public.creatorBullet.publish',
                'Join events, webinars, and forums.': 'public.creatorBullet.events',
                'Track performance and engagement.': 'public.creatorBullet.track',
                'Create offers and campaigns.': 'public.brandBullet.create',
                'Manage products and contracts.': 'public.brandBullet.manage',
                'Review creator applications.': 'public.brandBullet.review',
                'Negotiate and accept collaborations.': 'public.brandBullet.negotiate',
                'Track campaign performance.': 'public.brandBullet.track',
                'For creators': 'public.aiForCreators',
                'For brands': 'public.aiForBrands',
                'helps improve applications, summarize opportunities, and prepare negotiation messages.': 'public.aiCreator.cre8pilotText',
                'supports post creation with smarter content ideas and writing help.': 'public.aiCreator.creaText',
                'Personalized campaign suggestions': 'public.aiCreator.personalizedTitle',
                'recommend opportunities that match the creator profile and interests.': 'public.aiCreator.personalizedText',
                'Generate a campaign with AI': 'public.aiBrand.campaignTitle',
                'from a product, objective, audience, and budget.': 'public.aiBrand.campaignText',
                'Generate a contract with AI': 'public.aiBrand.contractTitle',
                'after an accepted collaboration, then review it before use.': 'public.aiBrand.contractText',
                'helps warn users about suspicious links, off-platform payment pressure, fake support messages, and risky behavior.': 'public.aiBrand.shieldText',
                'Events': 'public.community.event',
                'Webinars': 'public.community.webinars',
                'Workshops': 'public.community.workshops',
                'Forums': 'public.community.forums',
                'Collaboration Offers': 'public.feature.offers',
                'Applications & Negotiation': 'public.feature.applications',
                'Campaign Planning': 'public.feature.campaignPlanning',
                'Product Showcases': 'public.feature.products',
                'Collaboration Contracts': 'public.feature.contracts',
                'Creator Posts with Crea': 'public.feature.posts',
                'AI Campaign Suggestions': 'public.feature.campaignSuggestions',
                'AI Campaign & Contract Generation': 'public.feature.aiGeneration',
                'Events & Forums': 'public.feature.eventsForums'
            });

            function lang() { return typeof cre8FrontReadLang === 'function' ? cre8FrontReadLang() : ((localStorage.getItem('cre8_front_lang') || localStorage.getItem('cre8_lang')) === 'fr' ? 'fr' : 'en'); }
            function text(key) { return (translations[lang()] && translations[lang()][key]) || translations.en[key] || null; }
            function keyFor(value) {
                var clean = String(value || '').replace(/\s+/g, ' ').trim();
                if (aliases[clean]) return aliases[clean];
                for (var l of ['en', 'fr']) for (var k in translations[l]) if (translations[l][k] === clean) return k;
                return null;
            }
            function applyPublicTranslations() {
                if (typeof cre8RegisterTranslations === 'function') cre8RegisterTranslations(translations);
                var walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
                var nodes = [];
                while (walker.nextNode()) nodes.push(walker.currentNode);
                nodes.forEach(function (node) {
                    if (!node.parentElement || node.parentElement.closest('script,style,svg')) return;
                    var key = keyFor(node.nodeValue);
                    var value = text(key);
                    if (value) node.nodeValue = node.nodeValue.replace(String(node.nodeValue).trim(), value);
                });
                document.title = lang() === 'fr' ? 'Cre8Connect - Accueil' : 'Cre8Connect - Home';
            }
            if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', applyPublicTranslations); else applyPublicTranslations();
            window.addEventListener('cre8:languagechange', applyPublicTranslations);
        })();
        </script>
    

</body></html>
