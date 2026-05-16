<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../layout/avatar_helper.php';

if (!isset($pageTitle)) {
    $pageTitle = 'Community Posts';
}

if (!isset($currentPage)) {
    $currentPage = 'actuality';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/../layout/front-theme-bootstrap.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>


    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />

    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" />

    <!-- CSS -->
    <link href="../layout/front-header.css" rel="stylesheet" />
    <link href="../assets/css/styles.css" rel="stylesheet" />
    <link href="../assets/post-front.css?v=3" rel="stylesheet" />

<link rel="icon" type="image/png" sizes="16x16" href="../../public/images/favicon-16.png">
<link rel="icon" type="image/png" sizes="32x32" href="../../public/images/favicon-32.png">
<link rel="shortcut icon" type="image/png" href="../../public/images/favicon-32.png">
<link rel="apple-touch-icon" sizes="180x180" href="../../public/images/apple-touch-icon.png">
</head>

<body class="d-flex flex-column min-vh-100 social-body">

<main class="flex-shrink-0">

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg social-navbar sticky-top">
    <div class="container-fluid px-2 px-lg-3">

        <!-- Logo -->
        <a class="navbar-brand social-brand-logo" href="./index.php" aria-label="Home">
            <img src="../../public/images/logoweb.png"
                 alt="Cre8Connect"
                 class="social-logo-img front-header-logo">
        </a>

        <!-- Mobile toggle -->
        <button class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navbarSupportedContent"
                aria-controls="navbarSupportedContent"
                aria-expanded="false"
                aria-label="Toggle navigation">

            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar content -->
        <div class="collapse navbar-collapse" id="navbarSupportedContent">

            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">

                <!-- Home -->
                <li class="nav-item">
                    <a class="btn social-nav-btn"
                       href="../utilisateur/creator.php">
                        <i class="bi bi-house"></i>
                        Home
                    </a>
                </li>

                <!-- Dynamic page button -->
                <?php if ($currentPage === 'actuality'): ?>

                    <li class="nav-item">
                        <a class="btn social-nav-btn"
                           href="./portfolio.php">
                            <i class="bi bi-person-badge"></i>
                            My Space
                        </a>
                    </li>

                <?php else: ?>

                    <li class="nav-item">
                        <a class="btn social-nav-btn"
                           href="./index.php">
                            <i class="bi bi-newspaper"></i>
                            Actuality
                        </a>
                    </li>

                <?php endif; ?>

                <!-- Create post -->
                <li class="nav-item">
                    <a class="btn social-create-btn"
                       href="./create.php">
                        <i class="bi bi-plus-circle"></i>
                        Create Post
                    </a>
                </li>

                <!-- User -->
                <?php if (!empty($_SESSION['id'])): ?>

                    <li class="nav-item d-flex align-items-center gap-2">

                        <div class="navbar-text text-muted d-inline-flex align-items-center gap-2">
                            <?= cre8_render_avatar($_SESSION['id'], (string)($_SESSION['nom'] ?? 'User'), 'cre8-avatar-sm') ?>
                            <?= htmlspecialchars($_SESSION['nom'] ?? 'User') ?>
                        </div>

                        <!-- Logout -->
                        <a class="btn social-nav-btn social-logout-btn"
                           href="../utilisateur/logout.php">

                            <i class="bi bi-box-arrow-right"></i>
                            Logout
                        </a>

                    </li>

                <?php endif; ?>

                <!-- Theme toggle -->
                <li class="nav-item">

                    <button id="themeToggleBtn"
                            class="theme-toggle-btn"
                            title="Toggle dark/light mode">

                        <!-- Sun -->
                        <span class="theme-toggle-icon theme-icon-sun" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg"
                                 width="15"
                                 height="15"
                                 viewBox="0 0 24 24"
                                 fill="none"
                                 stroke="currentColor"
                                 stroke-width="2.2"
                                 stroke-linecap="round"
                                 stroke-linejoin="round">

                                <circle cx="12" cy="12" r="4"/>
                                <line x1="12" y1="2" x2="12" y2="4"/>
                                <line x1="12" y1="20" x2="12" y2="22"/>
                                <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
                                <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                                <line x1="2" y1="12" x2="4" y2="12"/>
                                <line x1="20" y1="12" x2="22" y2="12"/>
                                <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
                                <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                            </svg>
                        </span>

                        <!-- Toggle -->
                        <span class="theme-toggle-track">
                            <span class="theme-toggle-knob"></span>
                        </span>

                        <!-- Moon -->
                        <span class="theme-toggle-icon theme-icon-moon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg"
                                 width="14"
                                 height="14"
                                 viewBox="0 0 24 24"
                                 fill="currentColor"
                                 stroke="none">

                                <path d="M21 12.79A9 9 0 1 1 11.21 3a7 7 0 0 0 9.79 9.79z"/>
                            </svg>
                        </span>

                    </button>

                </li>

            </ul>
        </div>
    </div>
</nav>

<!-- Theme Script -->
<script>
(function(){

    var KEY = 'cre8_theme';

    function applyTheme(theme){
        var t = theme === 'dark' ? 'dark' : 'light';
        document.documentElement.setAttribute('data-theme', t);
        document.documentElement.classList.toggle('dark-mode', t === 'dark');
        document.documentElement.classList.toggle('light-mode', t !== 'dark');
        try { document.documentElement.style.colorScheme = t === 'dark' ? 'dark' : 'light'; } catch (e) {}
        if (document.body) {
            document.body.classList.toggle('dark-mode', t === 'dark');
            document.body.classList.toggle('light-mode', t !== 'dark');
        }
        localStorage.setItem(KEY, t);
    }

    var btn = document.getElementById('themeToggleBtn');

    if(btn){

        btn.addEventListener('click', function(){

            var current =
                document.documentElement.getAttribute('data-theme');

            var next =
                current === 'dark'
                    ? 'light'
                    : 'dark';

            applyTheme(next);
        });
    }

})();
</script>
