<?php
if (!isset($pageTitle)) {
    $pageTitle = 'Post Dashboard';
}
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
    <link rel="stylesheet" href="./assets/post.css">
</head>
<body>
<div class="container-scroller">

    <nav class="sidebar sidebar-offcanvas" id="sidebar">
        <div class="sidebar-brand-wrapper d-none d-lg-flex align-items-center justify-content-center fixed-top">
            <a class="sidebar-brand brand-logo text-white text-decoration-none" href="../post/index.php">Creator Panel</a>
            <a class="sidebar-brand brand-logo-mini text-white text-decoration-none" href="../post/index.php">CP</a>
        </div>

        <ul class="nav">
            <li class="nav-item profile">
                <div class="profile-desc">
                    <div class="profile-pic">
                        <div class="count-indicator">
                            <img class="img-xs rounded-circle" src="../../assets/images/faces/face15.jpg" alt="profile">
                            <span class="count bg-success"></span>
                        </div>
                        <div class="profile-name">
                            <h5 class="mb-0 font-weight-normal">Creator #1</h5>
                            <span>Post Manager</span>
                        </div>
                    </div>
                </div>
            </li>

            <li class="nav-item nav-category">
                <span class="nav-link">Posts</span>
            </li>

            <li class="nav-item menu-items">
                <a class="nav-link" href="../post/index.php">
                    <span class="menu-icon"><i class="mdi mdi-view-dashboard"></i></span>
                    <span class="menu-title">Dashboard</span>
                </a>
            </li>

            <li class="nav-item menu-items">
                <a class="nav-link" href="../post/create.php">
                    <span class="menu-icon"><i class="mdi mdi-plus-circle"></i></span>
                    <span class="menu-title">Create Post</span>
                </a>
            </li>

            <li class="nav-item menu-items">
                <a class="nav-link" href="../../FrontOffice/post/index.php">
                    <span class="menu-icon"><i class="mdi mdi-earth"></i></span>
                    <span class="menu-title">See FrontOffice</span>
                </a>
            </li>
        </ul>
    </nav>

    <div class="container-fluid page-body-wrapper">
        <nav class="navbar p-0 fixed-top d-flex flex-row">
            <div class="navbar-brand-wrapper d-flex d-lg-none align-items-center justify-content-center">
                <a class="navbar-brand brand-logo-mini" href="../post/index.php">CP</a>
            </div>

            <div class="navbar-menu-wrapper flex-grow d-flex align-items-stretch">
                <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-toggle="minimize">
                    <span class="mdi mdi-menu"></span>
                </button>

                <ul class="navbar-nav w-100">
                    <li class="nav-item w-100">
                        <div class="nav-link mt-2 mt-md-0 d-none d-lg-flex search">
                            <input type="text" class="form-control" placeholder="Post management area" disabled>
                        </div>
                    </li>
                </ul>

                <ul class="navbar-nav navbar-nav-right">
                    <li class="nav-item dropdown d-none d-lg-block">
                        <a class="nav-link btn btn-success create-new-button" href="../post/create.php">+ New Post</a>
                    </li>
                </ul>
            </div>
        </nav>

        <div class="main-panel">
            <div class="content-wrapper">