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
<link rel="icon" type="image/png" sizes="32x32" href="../../public/images/logo.png">
<link rel="shortcut icon" type="image/png" href="../../public/images/logo.png">
<link rel="apple-touch-icon" href="../../public/images/logo.png">
    
        <style>
            .public-nav-logo {
                width: 235px;
                height: auto;
                max-height: 72px;
                object-fit: contain;
                display: block;
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

            @media (max-width: 575.98px) {
                .public-nav-logo {
                    width: 175px;
                    max-height: 56px;
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
    </head>
    <body>
        <main class="flex-shrink-0">
            <!-- Navigation-->
            <nav class="navbar navbar-expand-lg navbar-light bg-white py-2">
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
                                    <a class="btn hero-action-btn btn-gradient-main" href="register.php?role=createur"><i class="bi bi-camera-reels"></i><span>Join as Creator</span></a>
                                    <a class="btn hero-action-btn btn-outline-gradient-main" href="register.php?role=marque"><i class="bi bi-building"></i><span>Join as Brand</span></a>
                                    <a class="btn hero-action-btn btn-gradient-main" href="login.php"><i class="bi bi-box-arrow-in-right"></i><span>Sign in</span></a>
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
                                        <h3 class="h4 fw-bolder">For Creators</h3>
                                        <ul class="check-list mt-3">
                                            <li><i class="bi bi-check-circle-fill"></i><span>Discover collaboration offers.</span></li>
                                            <li><i class="bi bi-check-circle-fill"></i><span>Apply to campaigns and negotiate terms.</span></li>
                                            <li><i class="bi bi-check-circle-fill"></i><span>Publish posts and grow visibility.</span></li>
                                            <li><i class="bi bi-check-circle-fill"></i><span>Join events, webinars, and forums.</span></li>
                                            <li><i class="bi bi-check-circle-fill"></i><span>Track performance and engagement.</span></li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card landing-card p-4">
                                        <div class="feature-icon mb-3"><i class="bi bi-briefcase"></i></div>
                                        <h3 class="h4 fw-bolder">For Brands</h3>
                                        <ul class="check-list mt-3">
                                            <li><i class="bi bi-check-circle-fill"></i><span>Create offers and campaigns.</span></li>
                                            <li><i class="bi bi-check-circle-fill"></i><span>Manage products and contracts.</span></li>
                                            <li><i class="bi bi-check-circle-fill"></i><span>Review creator applications.</span></li>
                                            <li><i class="bi bi-check-circle-fill"></i><span>Negotiate and accept collaborations.</span></li>
                                            <li><i class="bi bi-check-circle-fill"></i><span>Track campaign performance.</span></li>
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
                                <h3 class="h2 fw-bolder mb-3"><span class="text-gradient d-inline">For creators</span></h3>
                                <ul class="check-list">
                                    <li><i class="bi bi-check-circle-fill"></i><span><strong>Cre8Pilot</strong> helps improve applications, summarize opportunities, and prepare negotiation messages.</span></li>
                                    <li><i class="bi bi-check-circle-fill"></i><span><strong>Crea</strong> supports post creation with smarter content ideas and writing help.</span></li>
                                    <li><i class="bi bi-check-circle-fill"></i><span><strong>Personalized campaign suggestions</strong> recommend opportunities that match the creator profile and interests.</span></li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-lg-6" id="security">
                            <div class="card landing-card p-4 p-lg-5">
                                <div class="feature-icon mb-3"><i class="bi bi-magic"></i></div>
                                <h3 class="h2 fw-bolder mb-3"><span class="text-gradient d-inline">For brands</span></h3>
                                <ul class="check-list">
                                    <li><i class="bi bi-check-circle-fill"></i><span><strong>Generate a campaign with AI</strong> from a product, objective, audience, and budget.</span></li>
                                    <li><i class="bi bi-check-circle-fill"></i><span><strong>Generate a contract with AI</strong> after an accepted collaboration, then review it before use.</span></li>
                                    <li><i class="bi bi-check-circle-fill"></i><span><strong>Cre8Shield</strong> helps warn users about suspicious links, off-platform payment pressure, fake support messages, and risky behavior.</span></li>
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
                                <div class="col-sm-6"><div class="feature-pill"><i class="bi bi-calendar-event me-2 text-primary"></i> Events</div></div>
                                <div class="col-sm-6"><div class="feature-pill"><i class="bi bi-easel me-2 text-primary"></i> Webinars</div></div>
                                <div class="col-sm-6"><div class="feature-pill"><i class="bi bi-tools me-2 text-primary"></i> Workshops</div></div>
                                <div class="col-sm-6"><div class="feature-pill"><i class="bi bi-chat-square-text me-2 text-primary"></i> Forums</div></div>
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
                        <div class="col-md-6 col-lg-4"><div class="feature-pill"><i class="bi bi-broadcast me-2 text-primary"></i> Collaboration Offers</div></div>
                        <div class="col-md-6 col-lg-4"><div class="feature-pill"><i class="bi bi-chat-left-text me-2 text-primary"></i> Applications & Negotiation</div></div>
                        <div class="col-md-6 col-lg-4"><div class="feature-pill"><i class="bi bi-kanban me-2 text-primary"></i> Campaign Planning</div></div>
                        <div class="col-md-6 col-lg-4"><div class="feature-pill"><i class="bi bi-box-seam me-2 text-primary"></i> Product Showcases</div></div>
                        <div class="col-md-6 col-lg-4"><div class="feature-pill"><i class="bi bi-file-earmark-text me-2 text-primary"></i> Collaboration Contracts</div></div>
                        <div class="col-md-6 col-lg-4"><div class="feature-pill"><i class="bi bi-pencil-square me-2 text-primary"></i> Creator Posts with Crea</div></div>
                        <div class="col-md-6 col-lg-4"><div class="feature-pill"><i class="bi bi-lightbulb me-2 text-primary"></i> AI Campaign Suggestions</div></div>
                        <div class="col-md-6 col-lg-4"><div class="feature-pill"><i class="bi bi-magic me-2 text-primary"></i> AI Campaign & Contract Generation</div></div>
                        <div class="col-md-6 col-lg-4"><div class="feature-pill"><i class="bi bi-people me-2 text-primary"></i> Events & Forums</div></div>
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
        <footer class="site-footer bg-white">
            <div class="container px-5">
                <div class="row align-items-center justify-content-between flex-column flex-sm-row">
                    <div class="col-auto"><div class="small m-0">Copyright © cre8connect 2026</div></div>
                    <div class="col-auto">
                        <a class="small" href="#contact">Privacy</a>
                        <span class="mx-1">·</span>
                        <a class="small" href="#contact">Terms</a>
                        <span class="mx-1">·</span>
                        <a class="small" href="#contact">Contact</a>
                    </div>
                </div>
            </div>
        </footer>
        <!-- Bootstrap core JS-->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
        <!-- Core theme JS-->
        <script src="js/scripts.js"></script>
    

</body></html>