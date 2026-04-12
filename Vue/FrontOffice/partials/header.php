<?php
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>

    <link rel="icon" type="image/x-icon" href="../assets/favicon.ico" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" />
    <link href="../assets/css/styles.css" rel="stylesheet" />
    <link href="../assets/post-front.css?v=2" rel="stylesheet" />
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
            </ul>
        </div>
    </div>
</nav>