<?php
if (!isset($pageTitle)) {
    $pageTitle = 'Community Posts';
}

if (!isset($currentPage)) {
    $currentPage = 'actuality';
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>

    <link rel="icon" type="image/x-icon" href="../assets/favicon.ico" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" />
    <link href="../assets/css/styles.css" rel="stylesheet" />
    <link href="../assets/post-front.css?v=3" rel="stylesheet" />
    <!-- Anti-flash: applique le thème avant le premier rendu -->
    <script>
      (function(){
        var t = localStorage.getItem('cre8_theme');
        if(!t) t = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        document.documentElement.setAttribute('data-theme', t);
      })();
    </script>
</head>
<body class="d-flex flex-column min-vh-100 social-body">
<main class="flex-shrink-0">

<nav class="navbar navbar-expand-lg social-navbar sticky-top">
<div class="container-fluid px-2 px-lg-3">       <a class="navbar-brand social-brand-logo" href="./index.php" aria-label="Home">
    <img src="../../public/images/logoweb.png" alt="Logo" class="social-logo-img">
</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                <?php if ($currentPage === 'actuality'): ?>
                    <li class="nav-item">
                        <a class="btn social-nav-btn" href="./portfolio.php">
                            <i class="bi bi-person-badge"></i> My Space
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="btn social-nav-btn" href="./index.php">
                            <i class="bi bi-newspaper"></i> Actuality
                        </a>
                    </li>
                <?php endif; ?>

                <li class="nav-item">
                    <a class="btn social-create-btn" href="./create.php">
                        <i class="bi bi-plus-circle"></i> Create Post
                    </a>
                </li>

                <!-- Dark / Light toggle -->
                <li class="nav-item">
                    <button id="themeToggleBtn" class="theme-toggle-btn" title="Toggle dark/light mode">
                        <span class="theme-toggle-icon theme-icon-sun" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="4"/><line x1="12" y1="2" x2="12" y2="4"/><line x1="12" y1="20" x2="12" y2="22"/>
                                <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                                <line x1="2" y1="12" x2="4" y2="12"/><line x1="20" y1="12" x2="22" y2="12"/>
                                <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                            </svg>
                        </span>
                        <span class="theme-toggle-track">
                            <span class="theme-toggle-knob"></span>
                        </span>
                        <span class="theme-toggle-icon theme-icon-moon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" stroke="none">
                                <path d="M21 12.79A9 9 0 1 1 11.21 3a7 7 0 0 0 9.79 9.79z"/>
                            </svg>
                        </span>
                    </button>
                </li>
            </ul>
        </div>
    </div>
</nav>

<script>
(function(){
  var KEY = 'cre8_theme';
  function applyTheme(t){
    document.documentElement.setAttribute('data-theme', t);
    localStorage.setItem(KEY, t);
  }
  var btn = document.getElementById('themeToggleBtn');
  if(btn){
    btn.addEventListener('click', function(){
      var next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      applyTheme(next);
    });
  }
})();
</script>