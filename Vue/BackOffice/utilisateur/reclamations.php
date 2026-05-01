<?php
session_start();
require_once '../../../Controleur/reclamationC.php';

$reclamationC = new ReclamationC();

// Récupérer les paramètres de recherche et tri
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$priorite = isset($_GET['priorite']) ? trim($_GET['priorite']) : '';

$stmt = $reclamationC->afficherReclamationsAdmin($search, $priorite);
$liste = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stats = $reclamationC->statistiques();

// Calculer les statistiques de priorité
$haute = 0;
$moyenne = 0;
$basse = 0;
foreach ($liste as $rec) {
    if ($rec['priorite'] == 'haute') $haute++;
    elseif ($rec['priorite'] == 'moyenne') $moyenne++;
    else $basse++;
}
?>

<html lang="en">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>cre8connect Admin</title>
  <!-- plugins:css -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
    }

    .light-mode .navbar,
    .light-mode .sidebar {
      background-color: #ffffff !important;
    }

    .light-mode .table {
      color: black !important;
    }

    .light-mode .table-dark {
      background-color: #e9ecef !important;
      color: black !important;
    }

    .light-mode input.form-control,
    .light-mode select.form-select,
    .light-mode textarea.form-control {
      background-color: #ffffff !important;
      color: black !important;
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

    body {
      transition: background-color 0.3s ease, color 0.3s ease;
    }

    .light-mode * {
      transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
    }

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
        <a class="sidebar-brand brand-logo" href="index.html"><img src="assets/images/logo.svg" alt="logo"></a>
        <a class="sidebar-brand brand-logo-mini" href="index.html"><img src="assets/images/logo-mini.svg"
            alt="logo"></a>
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
      <div class="main-panel">
        <div class="content-wrapper">
          <div class="row">

          </div>
          <!-- ===================== STATISTIQUES AVANCÉES ===================== -->
          <div class="row mb-4">

            <!-- KPI Cards -->
            <div class="col-md-3 mb-3">
              <div class="card shadow-sm text-center p-4" style="background: linear-gradient(135deg, #9B5DE0 0%, #B771E5 100%); color: white; border-radius: 10px;">
                <i class="mdi mdi-file-document-outline" style="font-size: 2rem; margin-bottom: 10px;"></i>
                <h6 class="mb-2">Total Réclamations</h6>
                <h3 class="mb-0"><?php echo $stats['total']; ?></h3>
                <small class="mt-2 opacity-75">Toutes les réclamations</small>
              </div>
            </div>

            <div class="col-md-3 mb-3">
              <div class="card shadow-sm text-center p-4" style="background: linear-gradient(135deg, #D78FEE 0%, #C96FE8 100%); color: white; border-radius: 10px;">
                <i class="mdi mdi-clock-outline" style="font-size: 2rem; margin-bottom: 10px;"></i>
                <h6 class="mb-2">En Attente</h6>
                <h3 class="mb-0"><?php echo $stats['en_attente']; ?></h3>
                <small class="mt-2 opacity-75">À traiter</small>
              </div>
            </div>

            <div class="col-md-3 mb-3">
              <div class="card shadow-sm text-center p-4" style="background: linear-gradient(135deg, #AEEA94 0%, #99D98E 100%); color: #2d5016; border-radius: 10px;">
                <i class="mdi mdi-check-circle-outline" style="font-size: 2rem; margin-bottom: 10px;"></i>
                <h6 class="mb-2">Traitées</h6>
                <h3 class="mb-0"><?php echo $stats['traitee']; ?></h3>
                <small class="mt-2 opacity-75">Résolvées</small>
              </div>
            </div>

            <div class="col-md-3 mb-3">
              <div class="card shadow-sm text-center p-4" style="background: linear-gradient(135deg, #E11D74 0%, #D01565 100%); color: white; border-radius: 10px;">
                <i class="mdi mdi-alert-circle-outline" style="font-size: 2rem; margin-bottom: 10px;"></i>
                <h6 class="mb-2">Priorité Haute</h6>
                <h3 class="mb-0"><?php echo $stats['haute']; ?></h3>
                <small class="mt-2 opacity-75">Urgentes</small>
              </div>
            </div>

          </div>

          <!-- ===================== GRAPHES AREA CHARTS ===================== -->
          <div class="row mb-4">

            <!-- Area Chart - Réclamations par Statut -->
            <div class="col-lg-12 mb-3">
              <div class="card shadow-sm">
                <div class="card-body">
                  <h5 class="card-title mb-4">📊 Évolution des Réclamations par Statut (Timeline)</h5>
                  <div style="height: 350px;">
                    <canvas id="chartAreaStatut"></canvas>
                  </div>
                </div>
              </div>
            </div>

          </div>

          <!-- ===================== GRAPHE SUPPLÉMENTAIRE ===================== -->
          <div class="row mb-4">

            <!-- Area Chart - Priorités -->
            <div class="col-lg-12 mb-3">
              <div class="card shadow-sm">
                <div class="card-body">
                  <h5 class="card-title mb-4">⚡ Distribution des Priorités (Timeline)</h5>
                  <div style="height: 350px;">
                    <canvas id="chartAreaPriorite"></canvas>
                  </div>
                </div>
              </div>
            </div>

          </div>

          <!-- ===================== TABLEAU ===================== -->
          <div class="row">
            <div class="col-12">
              <div class="card shadow-sm">
                <div class="card-body">

                  <h5 class="mb-3">Gestion des réclamations</h5>

                  <!-- Recherche et Tri -->
                  <div class="row mb-3">
                    <div class="col-md-6">
                      <form method="GET" action="reclamations.php" class="d-flex gap-2">
                        <input type="text" name="search" class="form-control" placeholder="Rechercher par utilisateur ou description..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        <button type="submit" class="btn btn-sm" style="background-color: #9B5DE0; color: white; width: 80px;">Rechercher</button>
                      </form>
                    </div>
                    <div class="col-md-6">
                      <form method="GET" action="reclamations.php" class="d-flex gap-2">
                        <select name="priorite" class="form-select form-select-sm" onchange="this.form.submit()" style="background-color: #FDCFFA; border-color: #D78FEE;">
                          <option value="">Trier par priorité</option>
                          <option value="haute" <?php echo isset($_GET['priorite']) && $_GET['priorite'] == 'haute' ? 'selected' : ''; ?>>Haute</option>
                          <option value="moyenne" <?php echo isset($_GET['priorite']) && $_GET['priorite'] == 'moyenne' ? 'selected' : ''; ?>>Moyenne</option>
                          <option value="basse" <?php echo isset($_GET['priorite']) && $_GET['priorite'] == 'basse' ? 'selected' : ''; ?>>Basse</option>
                        </select>
                      </form>
                    </div>
                  </div>

                  <div class="table-responsive">
                    <table class="table table-hover align-middle">
                      <thead class="table-dark">
                        <tr>
                          <th>ID</th>
                          <th>Utilisateur</th>
                          <th>Description</th>
                          <th>Date</th>
                          <th>Priorité</th>
                          <th>Statut</th>
                          <th>Réponse</th>
                          <th>Actions</th>
                        </tr>
                      </thead>

                      <tbody>
                        <?php foreach ($liste as $rec): ?>
                          <tr>
                            <td><?php echo $rec['id']; ?></td>
                            <td><?php echo $rec['nom']; ?></td>
                            <td><?php echo $rec['description']; ?></td>
                            <td><?php echo $rec['date_creation']; ?></td>
                            <td><?php echo $rec['priorite']; ?></td>

                            <td>
                              <span class="badge bg-<?php echo ($rec['statut'] == 'traitee') ? 'success' : 'warning'; ?>">
                                <?php echo $rec['statut']; ?>
                              </span>
                            </td>

                            <td>
                              <?php if ($rec['reponse']): ?>
                                <button type="button" class="btn btn-sm" style="background-color: #9B5DE0; color: white; width: 80px;"
                                        data-bs-toggle="modal"
                                        data-bs-target="#replyViewModal<?php echo $rec['id']; ?>">
                                  Voir
                                </button>
                              <?php else: ?>
                                <span class="badge bg-secondary">Aucune</span>
                              <?php endif; ?>
                            </td>

                            <td>
                              <div class="d-flex gap-2" style="flex-wrap: wrap;">
                                <!-- Répondre -->
                                <button type="button" class="btn btn-sm" style="background-color: #9B5DE0; color: white; width: 80px;"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modal<?php echo $rec['id']; ?>">
                                  Répondre
                                </button>

                                <!-- Supprimer -->
                                <button type="button" class="btn btn-sm" style="background-color: #D78FEE; color: white; width: 80px;"
                                  onclick="deleteReclamation(<?php echo $rec['id']; ?>)">
                                  🗑
                                </button>

                                <!-- Modifier -->
                                <select class="form-select form-select-sm" style="width: 80px; background-color: #FDCFFA; border-color: #D78FEE; font-size: 0.875rem;"
                                  onchange="updateStatus(<?php echo $rec['id']; ?>, this.value)">
                                  <option value="">Statut</option>
                                  <option value="en_attente" <?php if ($rec['statut'] == 'en_attente')
                                    echo 'selected'; ?>>En attente</option>
                                  <option value="traitee" <?php if ($rec['statut'] == 'traitee')
                                    echo 'selected'; ?>>Traitée</option>
                                </select>
                              </div>
                            </td>
                          </tr>
                          <div class="modal fade" id="modal<?php echo $rec['id']; ?>" tabindex="-1">
              <div class="modal-dialog">
                <div class="modal-content">

                  <form method="POST" action="ajouterReponse.php" id="formReply<?php echo $rec['id']; ?>" onsubmit="return validateReply(this)">

                    <div class="modal-header">
                      <h5 class="modal-title">Répondre</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                      <input type="hidden" name="idReclamation" value="<?php echo $rec['id']; ?>">

                      <textarea name="contenu" class="form-control" id="replyContent<?php echo $rec['id']; ?>"
                                placeholder="Votre réponse..."></textarea>
                      <small class="text-danger d-none" id="replyError<?php echo $rec['id']; ?>">Veuillez entrer une réponse.</small>
                    </div>

                    <div class="modal-footer d-flex justify-content-between">
                      <button type="button" class="btn btn-secondary" style="width: 80px;" data-bs-dismiss="modal">Annuler</button>
                      <button type="submit" class="btn btn-success" style="width: 80px;">Envoyer</button>
                    </div>

                  </form>

                </div>
              </div>
            </div>

            <!-- Modal pour voir la réponse complète -->
            <div class="modal fade" id="replyViewModal<?php echo $rec['id']; ?>" tabindex="-1">
              <div class="modal-dialog">
                <div class="modal-content">

                  <div class="modal-header">
                    <h5 class="modal-title">Réponse</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>

                  <div class="modal-body">
                    <p><?php echo nl2br(htmlspecialchars($rec['reponse'] ?? '')); ?></p>
                    <?php if ($rec['date_reponse']): ?>
                      <small class="text-muted">Date: <?php echo $rec['date_reponse']; ?></small>
                    <?php endif; ?>
                  </div>

                  <div class="modal-footer d-flex justify-content-between gap-2">
                    <!-- Modifier la réponse -->
                    <button type="button" class="btn btn-sm" style="background-color: #9B5DE0; color: white; width: 80px;"
                            data-bs-dismiss="modal"
                            data-bs-toggle="modal"
                            data-bs-target="#editReplyModal<?php echo $rec['id']; ?>">
                      Modifier
                    </button>

                    <!-- Supprimer la réponse -->
                    <button type="button" class="btn btn-sm" style="background-color: #D78FEE; color: white; width: 80px;"
                            onclick="deleteReply(<?php echo $rec['id']; ?>)">
                      Supprimer
                    </button>

                    <button type="button" class="btn btn-secondary" style="width: 80px;" data-bs-dismiss="modal">Fermer</button>
                  </div>

                </div>
              </div>
            </div>

            <!-- Modal pour modifier la réponse -->
            <div class="modal fade" id="editReplyModal<?php echo $rec['id']; ?>" tabindex="-1">
              <div class="modal-dialog">
                <div class="modal-content">

                  <form method="POST" action="modifierReponse.php" id="formEditReply<?php echo $rec['id']; ?>" onsubmit="return validateEditReply(this)">

                    <div class="modal-header">
                      <h5 class="modal-title">Modifier la réponse</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                      <input type="hidden" name="idReclamation" value="<?php echo $rec['id']; ?>">
                      <textarea name="contenu" class="form-control" id="editReplyContent<?php echo $rec['id']; ?>" placeholder="Modifier la réponse..."><?php echo htmlspecialchars($rec['reponse'] ?? ''); ?></textarea>
                      <small class="text-danger d-none" id="editReplyError<?php echo $rec['id']; ?>">Veuillez entrer une réponse valide.</small>
                    </div>

                    <div class="modal-footer d-flex justify-content-between">
                      <button type="button" class="btn btn-secondary" style="width: 80px;" data-bs-dismiss="modal">Annuler</button>
                      <button type="submit" class="btn btn-success" style="width: 80px;">Mettre à jour</button>
                    </div>

                  </form>

                </div>
              </div>
            </div>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>

                </div>
              </div>
            </div>
          </div>

          <!-- ===================== MODALS ===================== -->
          
        
          <!-- content-wrapper ends -->
          <!-- partial:partials/_footer.html -->
          <footer class="footer">
            <div class="d-sm-flex justify-content-center justify-content-sm-between">
              <span class="text-muted d-block text-center text-sm-left d-sm-inline-block">Copyright © bootstrapdash.com
                2020</span>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- endinject -->
    <!-- Custom js for this page -->
    <script src="assets/js/dashboard.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
      // Palette de couleurs cohérente
      const colors = {
        total: '#9B5DE0',
        enAttente: '#D78FEE',
        traitee: '#AEEA94',
        haute: '#E11D74',
        moyenne: '#FDFFB8',
        basse: '#4E56C0'
      };

      // Données temporelles simulées (par jour/semaine)
      const dates = ['Jan 01', 'Jan 08', 'Jan 15', 'Jan 22', 'Jan 29', 'Fév 05', 'Fév 12', 'Fév 19', 'Fév 26', 'Mar 05', 'Mar 12', 'Mar 19'];
      
      // ===== CHART 1: AREA CHART - Réclamations par Statut =====
      const ctxAreaStatut = document.getElementById('chartAreaStatut');
      new Chart(ctxAreaStatut, {
        type: 'line',
        data: {
          labels: dates,
          datasets: [
            {
              label: 'En Attente',
              data: [5, 8, 12, 15, 18, 22, 19, 25, 28, 32, 30, 28],
              borderColor: colors.enAttente,
              backgroundColor: colors.enAttente + '33',
              fill: true,
              tension: 0.4,
              pointRadius: 5,
              pointBackgroundColor: colors.enAttente,
              pointBorderColor: '#fff',
              pointBorderWidth: 2,
              borderWidth: 3
            },
            {
              label: 'Traitées',
              data: [2, 4, 6, 9, 12, 16, 20, 24, 28, 32, 35, 38],
              borderColor: colors.traitee,
              backgroundColor: colors.traitee + '33',
              fill: true,
              tension: 0.4,
              pointRadius: 5,
              pointBackgroundColor: colors.traitee,
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

      // ===== CHART 2: AREA CHART - Priorités =====
      const ctxAreaPriorite = document.getElementById('chartAreaPriorite');
      new Chart(ctxAreaPriorite, {
        type: 'line',
        data: {
          labels: dates,
          datasets: [
            {
              label: 'Haute Priorité',
              data: [3, 5, 7, 9, 11, 13, 15, 17, 19, 21, 22, 20],
              borderColor: colors.haute,
              backgroundColor: colors.haute + '33',
              fill: true,
              tension: 0.4,
              pointRadius: 5,
              pointBackgroundColor: colors.haute,
              pointBorderColor: '#fff',
              pointBorderWidth: 2,
              borderWidth: 3
            },
            {
              label: 'Priorité Moyenne',
              data: [2, 4, 6, 8, 10, 12, 14, 16, 18, 20, 19, 18],
              borderColor: colors.moyenne,
              backgroundColor: colors.moyenne + '33',
              fill: true,
              tension: 0.4,
              pointRadius: 5,
              pointBackgroundColor: colors.moyenne,
              pointBorderColor: '#fff',
              pointBorderWidth: 2,
              borderWidth: 3
            },
            {
              label: 'Basse Priorité',
              data: [1, 2, 3, 4, 5, 7, 8, 9, 10, 11, 12, 11],
              borderColor: colors.basse,
              backgroundColor: colors.basse + '33',
              fill: true,
              tension: 0.4,
              pointRadius: 5,
              pointBackgroundColor: colors.basse,
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

      // ===== ANIMATIONS ET INTERACTIONS =====
      // Appliquer le thème au chargement pour tous les graphes
      window.addEventListener('DOMContentLoaded', function() {
        updateChartsTheme();
      });

      function updateChartsTheme() {
        const isLightMode = document.body.classList.contains('light-mode');
        const textColor = isLightMode ? '#000' : '#fff';
        Chart.defaults.color = textColor;
      }

      // Fonction pour supprimer une réclamation
      function deleteReclamation(id) {
        if (confirm('Êtes-vous sûr de vouloir supprimer cette réclamation ?')) {
          const form = document.createElement('form');
          form.method = 'POST';
          form.action = 'supprimerReclamation.php';

          const input = document.createElement('input');
          input.type = 'hidden';
          input.name = 'id';
          input.value = id;

          form.appendChild(input);
          document.body.appendChild(form);
          form.submit();
        }
      }

      // Fonction pour mettre à jour le statut
      function updateStatus(id, statut) {
        if (!statut) return;

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'modifierStatut.php';

        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'id';
        idInput.value = id;

        const statutInput = document.createElement('input');
        statutInput.type = 'hidden';
        statutInput.name = 'statut';
        statutInput.value = statut;

        form.appendChild(idInput);
        form.appendChild(statutInput);
        document.body.appendChild(form);
        form.submit();
      }

      // Fonction pour supprimer une réponse
      function deleteReply(id) {
        if (confirm('Êtes-vous sûr de vouloir supprimer cette réponse ?')) {
          const form = document.createElement('form');
          form.method = 'POST';
          form.action = 'supprimerReponse.php';

          const input = document.createElement('input');
          input.type = 'hidden';
          input.name = 'idReclamation';
          input.value = id;

          form.appendChild(input);
          document.body.appendChild(form);
          form.submit();
        }
      }

      // ===== VALIDATION JAVASCRIPT =====
      
      // Validation pour ajouter une réponse
      function validateReply(form) {
        const idReclamation = form.querySelector('input[name="idReclamation"]').value;
        const textarea = form.querySelector('textarea[name="contenu"]');
        const errorEl = document.getElementById('replyError' + idReclamation);
        
        const content = textarea.value.trim();
        
        // Vérification du contenu
        if (content === '') {
          errorEl.classList.remove('d-none');
          textarea.classList.add('border-danger');
          return false;
        }
        
        // Vérification de la longueur minimale (5 caractères)
        if (content.length < 5) {
          errorEl.textContent = 'La réponse doit contenir au moins 5 caractères.';
          errorEl.classList.remove('d-none');
          textarea.classList.add('border-danger');
          return false;
        }
        
        // Vérification de la longueur maximale (1000 caractères)
        if (content.length > 1000) {
          errorEl.textContent = 'La réponse ne doit pas dépasser 1000 caractères.';
          errorEl.classList.remove('d-none');
          textarea.classList.add('border-danger');
          return false;
        }
        
        // Vérification que ce n'est pas que des espaces
        if (!/\S/.test(content)) {
          errorEl.textContent = 'La réponse ne peut pas contenir uniquement des espaces.';
          errorEl.classList.remove('d-none');
          textarea.classList.add('border-danger');
          return false;
        }
        
        // Succès
        errorEl.classList.add('d-none');
        textarea.classList.remove('border-danger');
        return true;
      }
      
      // Validation pour modifier une réponse
      function validateEditReply(form) {
        const idReclamation = form.querySelector('input[name="idReclamation"]').value;
        const textarea = form.querySelector('textarea[name="contenu"]');
        const errorEl = document.getElementById('editReplyError' + idReclamation);
        
        const content = textarea.value.trim();
        
        // Vérification du contenu
        if (content === '') {
          errorEl.classList.remove('d-none');
          textarea.classList.add('border-danger');
          return false;
        }
        
        // Vérification de la longueur minimale (5 caractères)
        if (content.length < 5) {
          errorEl.textContent = 'La réponse doit contenir au moins 5 caractères.';
          errorEl.classList.remove('d-none');
          textarea.classList.add('border-danger');
          return false;
        }
        
        // Vérification de la longueur maximale (1000 caractères)
        if (content.length > 1000) {
          errorEl.textContent = 'La réponse ne doit pas dépasser 1000 caractères.';
          errorEl.classList.remove('d-none');
          textarea.classList.add('border-danger');
          return false;
        }
        
        // Vérification que ce n'est pas que des espaces
        if (!/\S/.test(content)) {
          errorEl.textContent = 'La réponse ne peut pas contenir uniquement des espaces.';
          errorEl.classList.remove('d-none');
          textarea.classList.add('border-danger');
          return false;
        }
        
        // Succès
        errorEl.classList.add('d-none');
        textarea.classList.remove('border-danger');
        return true;
      }
      
      // Enlever les messages d'erreur au focus
      document.addEventListener('focusin', function(e) {
        if (e.target.tagName === 'TEXTAREA' && e.target.name === 'contenu') {
          e.target.classList.remove('border-danger');
          const errorEls = document.querySelectorAll('small.text-danger');
          errorEls.forEach(el => {
            if (!el.classList.contains('d-none')) {
              el.classList.add('d-none');
            }
          });
        }
      });
    </script>
    <div class="jvectormap-tip" style="display: none; left: 605.948px; top: 2089px;">United States</div>
    <script>
      // Mode sombre/clair
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

      // Appliquer le thème au chargement
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