<?php session_start(); ?>
<?php
require_once '../../../Controleur/utilisateurC.php';

$userC = new UtilisateurC();

// Récupérer les paramètres de recherche et tri
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$role = isset($_GET['role']) ? trim($_GET['role']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 4;

$usersResult = $userC->afficherUsers($search, $role, $page, $limit);
$users = is_array($usersResult) && isset($usersResult['data']) ? $usersResult['data'] : [];
$totalUsers = is_array($usersResult) && isset($usersResult['total']) ? intval($usersResult['total']) : count($users);
$totalPages = is_array($usersResult) && isset($usersResult['totalPages']) ? max(1, intval($usersResult['totalPages'])) : 1;
$currentPage = is_array($usersResult) && isset($usersResult['page']) ? max(1, intval($usersResult['page'])) : 1;

$stats = $userC->getStatistiquesUtilisateurs();
?>

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>cre8connect Admin</title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- plugins:css -->
  <link rel="stylesheet" href="assets/vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
  <!-- endinject -->
  <!-- Plugin css for this page -->
  <link rel="stylesheet" href="assets/vendors/jvectormap/jquery-jvectormap.css">
  <link rel="stylesheet" href="assets/vendors/flag-icon-css/css/flag-icon.min.css">
  <link rel="stylesheet" href="assets/vendors/owl-carousel-2/owl.carousel.min.css">
  <link rel="stylesheet" href="assets/vendors/owl-carousel-2/owl.theme.default.min.css">
  <!-- End plugin css for this page -->
  <!-- inject:css -->
  <!-- endinject -->
  <!-- Layout styles -->
  <link rel="stylesheet" href="assets/css/style.css">
  <!-- End layout styles -->
  <link rel="shortcut icon" href="assets/images/favicon.png">
  <style type="text/css">
    /* Chart.js */
    @keyframes chartjs-render-animation {
      from {
        opacity: .99
      }

      to {
        opacity: 1
      }
    }

    .chartjs-render-monitor {
      animation: chartjs-render-animation 1ms
    }

    .chartjs-size-monitor,
    .chartjs-size-monitor-expand,
    .chartjs-size-monitor-shrink {
      position: absolute;
      direction: ltr;
      left: 0;
      top: 0;
      right: 0;
      bottom: 0;
      overflow: hidden;
      pointer-events: none;
      visibility: hidden;
      z-index: -1
    }

    .chartjs-size-monitor-expand>div {
      position: absolute;
      width: 1000000px;
      height: 1000000px;
      left: 0;
      top: 0
    }

    .chartjs-size-monitor-shrink>div {
      position: absolute;
      width: 200%;
      height: 200%;
      left: 0;
      top: 0
    }
  </style>
  <style>
    body.light-mode {
      background-color: #ffffff !important;
      color: #000 !important;
    }

    .light-mode .card {
      background-color: #f8f9fa !important;
      color: black !important;
      border-color: #dee2e6 !important;
    }

    .light-mode .navbar,
    .light-mode .sidebar {
      background-color: #ffffff !important;
      border-color: #dee2e6 !important;
    }

    .light-mode .table {
      color: black !important;
    }

    .light-mode .table-dark {
      background-color: #e9ecef !important;
      color: black !important;
    }

    .light-mode .table thead {
      background-color: #dee2e6 !important;
      color: black !important;
    }

    .light-mode input.form-control,
    .light-mode select.form-select,
    .light-mode textarea.form-control {
      background-color: #ffffff !important;
      color: black !important;
      border-color: #dee2e6 !important;
    }

    .light-mode .btn {
      border-color: #dee2e6 !important;
    }

    .light-mode .modal-content {
      background-color: #f8f9fa !important;
      color: black !important;
    }

    .light-mode .modal-header {
      background-color: #e9ecef !important;
      border-color: #dee2e6 !important;
    }

    /* Transition smooth pour le mode jour/nuit */
    body {
      transition: background-color 0.3s ease, color 0.3s ease;
    }

    .light-mode * {
      transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
    }

    /* Style du bouton jour/nuit */
    .nav-link i#themeIcon {
      transition: all 0.3s ease;
      font-size: 1.2rem;
    }

    .nav-link:hover i#themeIcon {
      transform: rotate(20deg);
    }
  </style>
</head>

<body>
  <div class="container-scroller">
    <!-- partial:partials/_sidebar.html -->
    <nav class="sidebar sidebar-offcanvas" id="sidebar">
      <div class="sidebar-brand-wrapper d-none d-lg-flex align-items-center justify-content-center fixed-top">
        <a class="sidebar-brand brand-logo" href="index.php"><img src="assets/images/logo.svg" alt="logo"></a>
        <a class="sidebar-brand brand-logo-mini" href="index.php"><img src="assets/images/logo-mini.svg" alt="logo"></a>
      </div>
      <ul class="nav">
        <li class="nav-item profile">
          <div class="profile-desc">
            <div class="profile-pic">
              <div class="count-indicator">
                <img class="img-xs rounded-circle " src="assets/images/faces/face15.jpg" alt="">
                <span class="count bg-success"></span>
              </div>
              <div class="profile-name">
                <h5 class="mb-0 font-weight-normal"> <?= htmlspecialchars($_SESSION['user']['nom'] ?? 'Utilisateur') ?>
                </h5>


                <span>Admin</span>
              </div>
            </div>

            <a href="#" id="profile-dropdown" data-toggle="dropdown"><i class="mdi mdi-dots-vertical"></i></a>
            <div class="dropdown-menu dropdown-menu-right sidebar-dropdown preview-list"
              aria-labelledby="profile-dropdown">
              <a href="#" class="dropdown-item preview-item">
                <div class="preview-thumbnail">
                  <div class="preview-icon bg-dark rounded-circle">
                    <i class="mdi mdi-settings text-primary"></i>
                  </div>
                </div>
                <div class="preview-item-content">
                  <p class="preview-subject ellipsis mb-1 text-small">Account settings</p>
                </div>
              </a>
              <div class="dropdown-divider"></div>
              <a href="#" class="dropdown-item preview-item">
                <div class="preview-thumbnail">
                  <div class="preview-icon bg-dark rounded-circle">
                    <i class="mdi mdi-onepassword  text-info"></i>
                  </div>
                </div>
                <div class="preview-item-content">
                  <p class="preview-subject ellipsis mb-1 text-small">Change Password</p>
                </div>
              </a>
              <div class="dropdown-divider"></div>
              <a href="#" class="dropdown-item preview-item">
                <div class="preview-thumbnail">
                  <div class="preview-icon bg-dark rounded-circle">
                    <i class="mdi mdi-calendar-today text-success"></i>
                  </div>
                </div>
                <div class="preview-item-content">
                  <p class="preview-subject ellipsis mb-1 text-small">To-do list</p>
                </div>
              </a>
            </div>
          </div>
        </li>
        <li class="nav-item nav-category">
          <span class="nav-link">Navigation</span>
        </li>
        <li class="nav-item menu-items">
          <a class="nav-link" href="#" onclick="toggleDarkMode(); return false;">
            <span class="menu-icon">
              <i id="themeIcon" class="mdi mdi-weather-night"></i>
            </span>
            <span class="menu-title">Mode jour / nuit</span>
          </a>
        </li>
        <li class="nav-item menu-items active">
          <a class="nav-link" href="index.php">
            <span class="menu-icon">
              <i class="mdi mdi-speedometer"></i>
            </span>
            <span class="menu-title">Dashboard</span>
          </a>
        </li>
        <li class="nav-item menu-items">
          <a class="nav-link" href="reclamations.php">
            <span class="menu-icon">
              <i class="mdi mdi-playlist-play"></i>
            </span>
            <span class="menu-title">reclamations</span>
          </a>
        </li>
        <li class="nav-item menu-items">
          <a class="nav-link" href="pages/tables/basic-table.html">
            <span class="menu-icon">
              <i class="mdi mdi-table-large"></i>
            </span>
            <span class="menu-title">offers</span>
          </a>
        </li>
        <li class="nav-item menu-items">
          <a class="nav-link" href="pages/charts/chartjs.html">
            <span class="menu-icon">
              <i class="mdi mdi-chart-bar"></i>
            </span>
            <span class="menu-title">campagne</span>
          </a>
        </li>
        <li class="nav-item menu-items">
          <a class="nav-link" href="pages/icons/mdi.html">
            <span class="menu-icon">
              <i class="mdi mdi-contacts"></i>
            </span>
            <span class="menu-title">events</span>
          </a>
        </li>
      </ul>
    </nav>
    <!-- partial -->
    <div class="container-fluid page-body-wrapper">
      <!-- partial:partials/_navbar.html -->
      <nav class="navbar p-0 fixed-top d-flex flex-row">
        <div class="navbar-brand-wrapper d-flex d-lg-none align-items-center justify-content-center">
          <a class="navbar-brand brand-logo-mini" href="index.php"><img src="assets/images/logo-mini.svg"
              alt="logo"></a>
        </div>
        <div class="navbar-menu-wrapper flex-grow d-flex align-items-stretch">
          <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-toggle="minimize">
            <span class="mdi mdi-menu"></span>
          </button>
          <ul class="navbar-nav w-100">
            <li class="nav-item w-100">
              <form class="nav-link mt-2 mt-md-0 d-none d-lg-flex search">
                <input type="text" class="form-control" placeholder="Search products">
              </form>
            </li>
          </ul>
          <ul class="navbar-nav navbar-nav-right">
            <li class="nav-item dropdown d-none d-lg-block">
              <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list"
                aria-labelledby="createbuttonDropdown">
                <h6 class="p-3 mb-0">Projects</h6>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item preview-item">
                  <div class="preview-thumbnail">
                    <div class="preview-icon bg-dark rounded-circle">
                      <i class="mdi mdi-file-outline text-primary"></i>
                    </div>
                  </div>
                  <div class="preview-item-content">
                    <p class="preview-subject ellipsis mb-1">Software Development</p>
                  </div>
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item preview-item">
                  <div class="preview-thumbnail">
                    <div class="preview-icon bg-dark rounded-circle">
                      <i class="mdi mdi-web text-info"></i>
                    </div>
                  </div>
                  <div class="preview-item-content">
                    <p class="preview-subject ellipsis mb-1">UI Development</p>
                  </div>
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item preview-item">
                  <div class="preview-thumbnail">
                    <div class="preview-icon bg-dark rounded-circle">
                      <i class="mdi mdi-layers text-danger"></i>
                    </div>
                  </div>
                  <div class="preview-item-content">
                    <p class="preview-subject ellipsis mb-1">Software Testing</p>
                  </div>
                </a>
                <div class="dropdown-divider"></div>
                <p class="p-3 mb-0 text-center">See all projects</p>
              </div>
            </li>
            <li class="nav-item nav-settings d-none d-lg-block">
              <a class="nav-link" href="#">
                <i class="mdi mdi-view-grid"></i>
              </a>
            </li>
            <li class="nav-item dropdown border-left">
              <a class="nav-link count-indicator dropdown-toggle" id="messageDropdown" href="#" data-toggle="dropdown"
                aria-expanded="false">
                <i class="mdi mdi-email"></i>
                <span class="count bg-success"></span>
              </a>
              <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list"
                aria-labelledby="messageDropdown">
                <h6 class="p-3 mb-0">Messages</h6>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item preview-item">
                  <div class="preview-thumbnail">
                    <img src="assets/images/faces/face4.jpg" alt="image" class="rounded-circle profile-pic">
                  </div>
                  <div class="preview-item-content">
                    <p class="preview-subject ellipsis mb-1">Mark send you a message</p>
                    <p class="text-muted mb-0"> 1 Minutes ago </p>
                  </div>
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item preview-item">
                  <div class="preview-thumbnail">
                    <img src="assets/images/faces/face2.jpg" alt="image" class="rounded-circle profile-pic">
                  </div>
                  <div class="preview-item-content">
                    <p class="preview-subject ellipsis mb-1">Cregh send you a message</p>
                    <p class="text-muted mb-0"> 15 Minutes ago </p>
                  </div>
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item preview-item">
                  <div class="preview-thumbnail">
                    <img src="assets/images/faces/face3.jpg" alt="image" class="rounded-circle profile-pic">
                  </div>
                  <div class="preview-item-content">
                    <p class="preview-subject ellipsis mb-1">Profile picture updated</p>
                    <p class="text-muted mb-0"> 18 Minutes ago </p>
                  </div>
                </a>
                <div class="dropdown-divider"></div>
                <p class="p-3 mb-0 text-center">4 new messages</p>
              </div>
            </li>
            <li class="nav-item dropdown border-left">
              <a class="nav-link count-indicator dropdown-toggle" id="notificationDropdown" href="#"
                data-toggle="dropdown">
                <i class="mdi mdi-bell"></i>
                <span class="count bg-danger"></span>
              </a>
              <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list"
                aria-labelledby="notificationDropdown">
                <h6 class="p-3 mb-0">Notifications</h6>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item preview-item">
                  <div class="preview-thumbnail">
                    <div class="preview-icon bg-dark rounded-circle">
                      <i class="mdi mdi-calendar text-success"></i>
                    </div>
                  </div>
                  <div class="preview-item-content">
                    <p class="preview-subject mb-1">Event today</p>
                    <p class="text-muted ellipsis mb-0"> Just a reminder that you have an event today </p>
                  </div>
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item preview-item">
                  <div class="preview-thumbnail">
                    <div class="preview-icon bg-dark rounded-circle">
                      <i class="mdi mdi-settings text-danger"></i>
                    </div>
                  </div>
                  <div class="preview-item-content">
                    <p class="preview-subject mb-1">Settings</p>
                    <p class="text-muted ellipsis mb-0"> Update dashboard </p>
                  </div>
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item preview-item">
                  <div class="preview-thumbnail">
                    <div class="preview-icon bg-dark rounded-circle">
                      <i class="mdi mdi-link-variant text-warning"></i>
                    </div>
                  </div>
                  <div class="preview-item-content">
                    <p class="preview-subject mb-1">Launch Admin</p>
                    <p class="text-muted ellipsis mb-0"> New admin wow! </p>
                  </div>
                </a>
                <div class="dropdown-divider"></div>
                <p class="p-3 mb-0 text-center">See all notifications</p>
              </div>
            </li>
            <li class="nav-item dropdown">
              <a class="nav-link" id="profileDropdown" href="#" data-toggle="dropdown">
                <div class="navbar-profile">
                  <img class="img-xs rounded-circle" src="assets/images/faces/face15.jpg" alt="">
                  <p class="mb-0 d-none d-sm-block navbar-profile-name">
                    <?= htmlspecialchars($_SESSION['user']['nom'] ?? 'Utilisateur') ?>
                  </p>
                  <i class="mdi mdi-menu-down d-none d-sm-block"></i>
                </div>
              </a>
              <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list"
                aria-labelledby="profileDropdown">
                <h6 class="p-3 mb-0">Profile</h6>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item preview-item">
                  <div class="preview-thumbnail">
                    <div class="preview-icon bg-dark rounded-circle">
                      <i class="mdi mdi-settings text-success"></i>
                    </div>
                  </div>
                  <div class="preview-item-content">
                    <p class="preview-subject mb-1">Settings</p>
                  </div>
                </a>
                <div class="dropdown-divider"></div>
                <a href="../utilisateur/logout.php" class="dropdown-item preview-item">
                  <div class="preview-thumbnail">
                    <div class="preview-icon bg-dark rounded-circle">
                      <i class="mdi mdi-logout text-danger"></i>
                    </div>
                  </div>
                  <div class="preview-item-content">
                    <p class="preview-subject mb-1">Log out</p>
                  </div>
                </a>
                <div class="dropdown-divider"></div>
                <p class="p-3 mb-0 text-center">Advanced settings</p>
              </div>
            </li>
          </ul>
          <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button"
            data-toggle="offcanvas">
            <span class="mdi mdi-format-line-spacing"></span>
          </button>
        </div>
      </nav>
      <!-- partial -->
      <div class="main-panel">
        <div class="content-wrapper">
          <div class="row">

          </div>

          <!-- ===================== STATISTIQUES AVANCÉES ===================== -->
        <div class="row mb-4 align-items-stretch">

  <!-- Total Utilisateurs -->
  <div class="col-md-3 mb-3 d-flex">
    <div class="card shadow-sm text-center p-4 h-100 w-100" style="background: linear-gradient(135deg, #9B5DE0 0%, #B771E5 100%); color: white; border-radius: 10px;">
      <i class="mdi mdi-account-multiple" style="font-size: 2rem; margin-bottom: 10px;"></i>
      <h6 class="mb-2">Total Utilisateurs</h6>
      <h3 class="mb-0"><?php echo $stats['total']; ?></h3>
      <small class="mt-2 opacity-75">Tous les comptes</small>
    </div>
  </div>

  <!-- Admin -->
  <div class="col-md-3 mb-3 d-flex">
    <div class="card shadow-sm text-center p-4 h-100 w-100" style="background: linear-gradient(135deg, #D78FEE 0%, #C96FE8 100%); color: white; border-radius: 10px;">
      <i class="mdi mdi-shield-admin" style="font-size: 2rem; margin-bottom: 10px;"></i>
      <h6 class="mb-2">Administrateurs</h6>
      <h3 class="mb-0"><?php echo $stats['admin']; ?></h3>
      <small class="mt-2 opacity-75">Comptes admin</small>
    </div>
  </div>

  <!-- Créateurs -->
  <div class="col-md-3 mb-3 d-flex">
    <div class="card shadow-sm text-center p-4 h-100 w-100" style="background: linear-gradient(135deg, #AEEA94 0%, #99D98E 100%); color: #2d5016; border-radius: 10px;">
      <i class="mdi mdi-account-convert" style="font-size: 2rem; margin-bottom: 10px;"></i>
      <h6 class="mb-2">Créateurs</h6>
      <h3 class="mb-0"><?php echo $stats['createur']; ?></h3>
      <small class="mt-2 opacity-75">Comptes créateur</small>
    </div>
  </div>

  <!-- Marques -->
  <div class="col-md-3 mb-3 d-flex">
    <div class="card shadow-sm text-center p-4 h-100 w-100" style="background: linear-gradient(135deg, #E11D74 0%, #D01565 100%); color: white; border-radius: 10px;">
      <i class="mdi mdi-store" style="font-size: 2rem; margin-bottom: 10px;"></i>
      <h6 class="mb-2">Marques</h6>
      <h3 class="mb-0"><?php echo $stats['marque']; ?></h3>
      <small class="mt-2 opacity-75">Comptes marque</small>
    </div>
  </div>

</div>
          <!-- ===================== GRAPHES AREA CHARTS ===================== -->
          <div class="row mb-4">

            <!-- Area Chart - Distribution des Rôles -->
            <div class="col-lg-12 mb-3">
              <div class="card shadow-sm">
                <div class="card-body">
                  <h5 class="card-title mb-4">📊 Évolution des Utilisateurs par Rôle (Timeline)</h5>
                  <div style="height: 350px;">
                    <canvas id="chartAreaRole"></canvas>
                  </div>
                </div>
              </div>
            </div>

          </div>

          <!-- ===================== GRAPHE SUPPLÉMENTAIRE ===================== -->
          <div class="row mb-4">

            <!-- Area Chart - Statut des Utilisateurs -->
            <div class="col-lg-12 mb-3">
              <div class="card shadow-sm">
                <div class="card-body">
                  <h5 class="card-title mb-4">👥 Statut des Utilisateurs (Timeline)</h5>
                  <div style="height: 350px;">
                    <canvas id="chartAreaStatus"></canvas>
                  </div>
                </div>
              </div>
            </div>

          </div>
          <div class="row ">
            <div class="col-12 grid-margin">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title">Gestion des Utilisateurs</h4>

                  <!-- Recherche et Tri -->
                  <div class="row mb-3">
                    <div class="col-md-6">
                      <form method="GET" action="index.php" class="d-flex gap-2">
                        <input type="text" name="search" class="form-control"
                          placeholder="Rechercher par nom ou email..."
                          value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        <button type="submit" class="btn btn-sm"
                          style="background-color: #9B5DE0; color: white; width: 80px;">Rechercher</button>
                      </form>
                    </div>
                    <div class="col-md-6">
                      <form method="GET" action="index.php" class="d-flex gap-2">
                        <select name="role" class="form-select form-select-sm" onchange="this.form.submit()"
                          style="background-color: #FDCFFA; border-color: #D78FEE;">
                          <option value="">Trier par rôle</option>
                          <option value="admin" <?php echo isset($_GET['role']) && $_GET['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                          <option value="createur" <?php echo isset($_GET['role']) && $_GET['role'] == 'createur' ? 'selected' : ''; ?>>Créateur</option>
                          <option value="marque" <?php echo isset($_GET['role']) && $_GET['role'] == 'marque' ? 'selected' : ''; ?>>Marque</option>
                        </select>
                      </form>
                    </div>
                  </div>

                  <div class="table-responsive">
                    <table class="table table-hover align-middle">
                      <thead class="table-dark">
                        <tr>
                          <th>ID</th>
                          <th>Nom</th>
                          <th>Email</th>
                          <th>Rôle</th>
                          <th>Statut</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody>

                        <?php foreach ($users as $u): ?>
                          <tr>
                            <form method="POST" action="update.php" class="w-100">
                              <td><?= $u['id'] ?></td>

                              <td>
                                <input type="text" name="nom" value="<?= htmlspecialchars($u['nom']) ?>"
                                  class="form-control form-control-sm">
                              </td>

                              <td>
                                <input type="email" name="email" value="<?= htmlspecialchars($u['email']) ?>"
                                  class="form-control form-control-sm">
                              </td>

                              <td>
                                <select name="role" class="form-select form-select-sm"
                                  style="background-color: #FDCFFA; border-color: #D78FEE;">
                                  <option value="admin" <?= $u['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                                  <option value="createur" <?= $u['role'] == 'createur' ? 'selected' : '' ?>>Créateur</option>
                                  <option value="marque" <?= $u['role'] == 'marque' ? 'selected' : '' ?>>Marque</option>
                                </select>
                              </td>

                              <td>
                                <span class="badge"
                                  style="background-color: <?= $u['statut'] == 'actif' ? '#9B5DE0' : '#D78FEE' ?>; color: white;">
                                  <?= ucfirst($u['statut']) ?>
                                </span>
                              </td>

                              <td>
                                <div class="d-flex gap-2">
                                  <input type="hidden" name="id" value="<?= $u['id'] ?>">

                                  <button type="submit" class="btn btn-sm"
                                    style="background-color: #9B5DE0; color: white; width: 80px;">
                                    Modifier
                                  </button>

                                  <button type="button"
                                    onclick="if(confirm('Êtes-vous sûr ?')) window.location.href='delete.php?id=<?= $u['id'] ?>';"
                                    class="btn btn-sm" style="background-color: #D78FEE; color: white; width: 80px;">
                                    Supprimer
                                  </button>
                                </div>
                              </td>

                            </form>
                          </tr>
                        <?php endforeach; ?>

                      </tbody>
                    </table>
                  </div>

                  <?php if ($totalPages > 1): ?>
                  <div class="d-flex justify-content-center mt-3">
                    <nav aria-label="Pagination utilisateurs">
                      <ul class="pagination">
                        <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                          <a class="page-link" href="?search=<?= urlencode($search) ?>&role=<?= urlencode($role) ?>&page=<?= $currentPage - 1 ?>" aria-label="Précédent">&laquo;</a>
                        </li>
                        <?php
                        $startPage = max(1, $currentPage - 2);
                        $endPage = min($totalPages, $currentPage + 2);
                        if ($startPage > 1): ?>
                          <li class="page-item"><a class="page-link" href="?search=<?= urlencode($search) ?>&role=<?= urlencode($role) ?>&page=1">1</a></li>
                          <?php if ($startPage > 2): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                          <?php endif; ?>
                        <?php endif; ?>
                        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                          <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                            <a class="page-link" href="?search=<?= urlencode($search) ?>&role=<?= urlencode($role) ?>&page=<?= $i ?>"><?= $i ?></a>
                          </li>
                        <?php endfor; ?>
                        <?php if ($endPage < $totalPages): ?>
                          <?php if ($endPage < $totalPages - 1): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                          <?php endif; ?>
                          <li class="page-item"><a class="page-link" href="?search=<?= urlencode($search) ?>&role=<?= urlencode($role) ?>&page=<?= $totalPages ?>"><?= $totalPages ?></a></li>
                        <?php endif; ?>
                        <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                          <a class="page-link" href="?search=<?= urlencode($search) ?>&role=<?= urlencode($role) ?>&page=<?= $currentPage + 1 ?>" aria-label="Suivant">&raquo;</a>
                        </li>
                      </ul>
                    </nav>
                  </div>
                  <div class="text-center text-muted">Page <?= $currentPage ?> sur <?= $totalPages ?> (<?= $totalUsers ?> utilisateurs)</div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- content-wrapper ends -->
        <!-- partial:partials/_footer.html -->
        <footer class="footer">
          <div class="d-sm-flex justify-content-center justify-content-sm-between">
            <span class="text-muted d-block text-center text-sm-left d-sm-inline-block">Copyright © bootstrapdash.com
              2020</span>
            <span class="float-none float-sm-right d-block mt-1 mt-sm-0 text-center"> Free <a
                href="https://www.bootstrapdash.com/bootstrap-admin-template/" target="_blank">Bootstrap admin
                templates</a> from Bootstrapdash.com</span>
          </div>
        </footer>
        <!-- partial -->
      </div>
      <!-- main-panel ends -->
    </div>
    <!-- page-body-wrapper ends -->
  </div>
  <!-- container-scroller -->
  <!-- plugins:js -->
  <script src="assets/vendors/js/vendor.bundle.base.js"></script>
  <!-- endinject -->
  <!-- Plugin js for this page -->
  <script src="assets/vendors/chart.js/Chart.min.js"></script>
  <script src="assets/vendors/progressbar.js/progressbar.min.js"></script>
  <script src="assets/vendors/jvectormap/jquery-jvectormap.min.js"></script>
  <script src="assets/vendors/jvectormap/jquery-jvectormap-world-mill-en.js"></script>
  <script src="assets/vendors/owl-carousel-2/owl.carousel.min.js"></script>
  <!-- End plugin js for this page -->
  <!-- inject:js -->
  <script src="assets/js/off-canvas.js"></script>
  <script src="assets/js/hoverable-collapse.js"></script>
  <script src="assets/js/misc.js"></script>
  <script src="assets/js/settings.js"></script>
  <script src="assets/js/todolist.js"></script>
  <!-- endinject -->
  <!-- Bootstrap 5 JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/off-canvas.js"></script>
  <script src="assets/js/hoverable-collapse.js"></script>
  <script src="assets/js/misc.js"></script>
  <script src="assets/js/settings.js"></script>
  <script src="assets/js/todolist.js"></script>
  <!-- endinject -->
  <!-- Custom js for this page -->
  <script src="assets/js/dashboard.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <!-- End custom js for this page -->

  <div class="jvectormap-tip" style="display: none; left: 605.948px; top: 2089px;">United States</div>

  <script>
    // Palette de couleurs cohérente
    const colors = {
      admin: '#9B5DE0',
      createur: '#AEEA94',
      marque: '#E11D74',
      actif: '#9B5DE0',
      inactif: '#D78FEE'
    };

    // Données temporelles simulées
    const dates = ['Jan 01', 'Jan 08', 'Jan 15', 'Jan 22', 'Jan 29', 'Fév 05', 'Fév 12', 'Fév 19', 'Fév 26', 'Mar 05', 'Mar 12', 'Mar 19'];
    
    // ===== CHART 1: AREA CHART - Utilisateurs par Rôle =====
    const ctxAreaRole = document.getElementById('chartAreaRole');
    new Chart(ctxAreaRole, {
      type: 'line',
      data: {
        labels: dates,
        datasets: [
          {
            label: 'Administrateurs',
            data: [2, 2, 2, 3, 3, 3, 4, 4, 4, 4, 4, 4],
            borderColor: colors.admin,
            backgroundColor: colors.admin + '33',
            fill: true,
            tension: 0.4,
            pointRadius: 5,
            pointBackgroundColor: colors.admin,
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            borderWidth: 3
          },
          {
            label: 'Créateurs',
            data: [8, 10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30],
            borderColor: colors.createur,
            backgroundColor: colors.createur + '33',
            fill: true,
            tension: 0.4,
            pointRadius: 5,
            pointBackgroundColor: colors.createur,
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            borderWidth: 3
          },
          {
            label: 'Marques',
            data: [5, 6, 7, 8, 9, 11, 12, 13, 14, 15, 16, 17],
            borderColor: colors.marque,
            backgroundColor: colors.marque + '33',
            fill: true,
            tension: 0.4,
            pointRadius: 5,
            pointBackgroundColor: colors.marque,
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            borderWidth: 3
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'top',
            labels: {
              padding: 15,
              font: { size: 12, weight: 'bold' },
              usePointStyle: true
            }
          },
          filler: {
            propagate: true
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            grid: {
              color: 'rgba(0,0,0,0.05)'
            }
          },
          x: {
            grid: {
              display: false
            }
          }
        }
      }
    });

    // ===== CHART 2: AREA CHART - Statut des Utilisateurs =====
    const ctxAreaStatus = document.getElementById('chartAreaStatus');
    new Chart(ctxAreaStatus, {
      type: 'line',
      data: {
        labels: dates,
        datasets: [
          {
            label: 'Actif',
            data: [15, 18, 21, 24, 27, 30, 33, 36, 39, 42, 45, 48],
            borderColor: colors.actif,
            backgroundColor: colors.actif + '33',
            fill: true,
            tension: 0.4,
            pointRadius: 5,
            pointBackgroundColor: colors.actif,
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            borderWidth: 3
          },
          {
            label: 'Inactif',
            data: [0, 0, 0, 1, 1, 2, 3, 3, 4, 4, 5, 6],
            borderColor: colors.inactif,
            backgroundColor: colors.inactif + '33',
            fill: true,
            tension: 0.4,
            pointRadius: 5,
            pointBackgroundColor: colors.inactif,
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            borderWidth: 3
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'top',
            labels: {
              padding: 15,
              font: { size: 12, weight: 'bold' },
              usePointStyle: true
            }
          },
          filler: {
            propagate: true
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            grid: {
              color: 'rgba(0,0,0,0.05)'
            }
          },
          x: {
            grid: {
              display: false
            }
          }
        }
      }
    });

    // Appliquer le thème au chargement pour tous les graphes
    window.addEventListener('DOMContentLoaded', function() {
      updateChartsTheme();
    });

    function updateChartsTheme() {
      const isLightMode = document.body.classList.contains('light-mode');
      const textColor = isLightMode ? '#000' : '#fff';
      Chart.defaults.color = textColor;
    }
  </script>

  <script>
    function toggleDarkMode() {
      document.body.classList.toggle("light-mode");

      let icon = document.getElementById("themeIcon");

      if (document.body.classList.contains("light-mode")) {
        localStorage.setItem("theme", "light");
        if (icon) icon.className = "mdi mdi-white-balance-sunny";
      } else {
        localStorage.setItem("theme", "dark");
        if (icon) icon.className = "mdi mdi-weather-night";
      }
    }

    window.addEventListener('DOMContentLoaded', function () {
      let icon = document.getElementById("themeIcon");
      if (localStorage.getItem("theme") === "light") {
        document.body.classList.add("light-mode");
        if (icon) icon.className = "mdi mdi-white-balance-sunny";
      }
    });
  </script>
</body>

</html>