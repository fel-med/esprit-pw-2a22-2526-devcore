<?php
if (!isset($pageTitle)) {
    $pageTitle = 'Admin Posts Dashboard';
}
if (!isset($currentPage)) {
    $currentPage = 'posts';
}
function isActivePage($page, $currentPage)
{
    return $page === $currentPage ? 'active' : '';
}
$requestPath = $_SERVER['REQUEST_URI'] ?? ($_SERVER['PHP_SELF'] ?? '');
$isCommentSection = strpos($requestPath, '/comment/') !== false || strpos($requestPath, '\\comment\\') !== false;
$postsIndexUrl = $isCommentSection ? '../post/index.php' : './index.php';
$commentIndexUrl = $isCommentSection ? './index.php' : '../comment/index.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>

    <link rel="stylesheet" href="../assets/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="../assets/vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/post-admin.css?v=2">
</head>
<body>
<div class="container-scroller">

    <nav class="sidebar sidebar-offcanvas" id="sidebar">
        <div class="sidebar-brand-wrapper d-none d-lg-flex align-items-center justify-content-center fixed-top">
            <a class="sidebar-brand brand-logo text-white text-decoration-none" href="<?= htmlspecialchars($postsIndexUrl) ?>">
                <img src="../../public/images/logoweb.png" alt="Create Connect" style="height: 44px; width: auto;">
            </a>
            <a class="sidebar-brand brand-logo-mini text-white text-decoration-none" href="<?= htmlspecialchars($postsIndexUrl) ?>">
                <img src="../../public/images/logoweb.png" alt="Create Connect" style="height: 30px; width: auto;">
            </a>
        </div>

        <ul class="nav">
            <li class="nav-item profile">
                <div class="profile-desc">
                    <div class="profile-pic">
                        <div class="count-indicator">
                            <img class="img-xs rounded-circle" src="../assets/images/faces/face15.jpg" alt="profile">
                            <span class="count bg-success"></span>
                        </div>
                        <div class="profile-name">
                            <h5 class="mb-0 font-weight-normal">Administrator</h5>
                            <span>Posts moderation</span>
                        </div>
                    </div>
                </div>
            </li>

            <li class="nav-item">
                <button id="themeToggleBtn" class="nav-link theme-toggle-btn" title="Toggle dark/light mode">
                    <span class="menu-icon"><i id="themeIcon" class="mdi mdi-weather-night"></i></span>
                    <span class="menu-title" id="themeLabel">Light mode</span>
                </button>
            </li>

            <li class="nav-item nav-category"><span class="nav-link">Navigation</span></li>

            <li class="nav-item menu-items <?= isActivePage('posts', $currentPage); ?>">
                <a class="nav-link" href="<?= htmlspecialchars($postsIndexUrl) ?>">
                    <span class="menu-icon"><i class="mdi mdi-post-outline"></i></span>
                    <span class="menu-title">All Posts</span>
                </a>
            </li>

            <li class="nav-item menu-items <?= isActivePage('comments', $currentPage); ?>">
                <a class="nav-link" href="<?= htmlspecialchars($commentIndexUrl) ?>">
                    <span class="menu-icon"><i class="mdi mdi-comment-multiple-outline"></i></span>
                    <span class="menu-title">Manage Comments</span>
                </a>
            </li>

            <li class="nav-item menu-items">
                <a class="nav-link" href="../../FrontOffice/post/index.php">
                    <span class="menu-icon"><i class="mdi mdi-earth"></i></span>
                    <span class="menu-title">Actuality</span>
                </a>
            </li>

            <li class="nav-item menu-items">
                <a class="nav-link" href="../../FrontOffice/post/portfolio.php">
                    <span class="menu-icon"><i class="mdi mdi-account-box-outline"></i></span>
                    <span class="menu-title">Creator Space</span>
                </a>
            </li>
        </ul>
    </nav>

    <div class="container-fluid page-body-wrapper">
        <nav class="navbar p-0 fixed-top d-flex flex-row">
            <div class="navbar-menu-wrapper d-flex align-items-stretch">
                <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-toggle="minimize">
                    <span class="mdi mdi-menu"></span>
                </button>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <div class="nav-link mt-2 mt-md-0 d-none d-lg-flex search">
                            <input type="text" class="form-control" placeholder="Admin dashboard" disabled>
                        </div>
                    </li>
                </ul>
                <ul class="navbar-nav navbar-nav-right">
                    <li class="nav-item d-none d-lg-block"><span class="nav-link text-muted">Manage creators, posts, and comments</span></li>
                </ul>
            </div>
        </nav>
        <div class="main-panel"><div class="content-wrapper">
