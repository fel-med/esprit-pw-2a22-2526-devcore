<?php
session_start();
require_once __DIR__ . '/../layout/early-theme.php';
require_once '../../../Controleur/reclamationC.php';

$reclamationC = new ReclamationC();

// Récupérer les paramètres de recherche et tri
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$priorite = isset($_GET['priorite']) ? trim($_GET['priorite']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 4; // Records per page

$result = $reclamationC->afficherReclamationsAdmin($search, $priorite, $page, $limit);
$stmt = is_array($result) && isset($result['stmt']) ? $result['stmt'] : false;
$liste = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
$totalReclamations = is_array($result) && isset($result['total']) ? intval($result['total']) : 0;
$totalPages = is_array($result) && isset($result['totalPages']) ? max(1, intval($result['totalPages'])) : 1;
$currentPage = is_array($result) && isset($result['page']) ? max(1, intval($result['page'])) : 1;

$stats = $reclamationC->statistiques();
$statusTimeline = $reclamationC->getReclamationStatusTimeline(14);
$priorityTimeline = $reclamationC->getReclamationPriorityTimeline(14);
$statusTimeline = $reclamationC->getReclamationStatusTimeline(14);
$priorityTimeline = $reclamationC->getReclamationPriorityTimeline(14);
$statusTimeline = $reclamationC->getReclamationStatusTimeline(14);
$priorityTimeline = $reclamationC->getReclamationPriorityTimeline(14);

// Calculate priority statistics
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
  <?php cre8_bo_early_theme_print_head_script(); ?>
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
  <link rel="stylesheet" href="../layout/back-layout.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../layout/back-layout.css')); ?>">
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
      background-color: #f9fafb !important;
      color: #111827 !important;
    }

    body.light-mode .container-scroller,
    body.light-mode .page-body-wrapper,
    body.light-mode .main-panel,
    body.light-mode .content-wrapper,
    body.light-mode .footer,
    body.light-mode .navbar,
    body.light-mode .sidebar,
    body.light-mode .navbar-custom {
      background-color: #ffffff !important;
      color: #111827 !important;
      border-color: #e2e8f0 !important;
    }

    .light-mode .card,
    .light-mode .card-body,
    .light-mode .table,
    .light-mode .table-responsive,
    .light-mode .dropdown-menu,
    .light-mode .modal-content,
    .light-mode .form-control,
    .light-mode .form-select,
    .light-mode textarea {
      background-color: #ffffff !important;
      color: #111827 !important;
      border-color: #e2e8f0 !important;
    }

    .light-mode .table thead {
      background-color: #e2e8f0 !important;
      color: #111827 !important;
    }

    .light-mode .table tbody tr:hover {
      background-color: #f8fafc !important;
    }

    .light-mode .navbar-custom a,
    .light-mode .nav-link,
    .light-mode .profile-name h5,
    .light-mode .profile-name span,
    .light-mode .badge,
    .light-mode .card-title,
    .light-mode .card-description {
      color: #111827 !important;
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

    .pagination {
      margin-bottom: 0;
    }

    .pagination .page-link {
      color: #9B5DE0;
      background-color: #fff;
      border: 1px solid #dee2e6;
      padding: 0.375rem 0.75rem;
      margin: 0 2px;
      border-radius: 0.375rem;
      text-decoration: none;
      transition: all 0.3s ease;
    }

    .pagination .page-link:hover {
      color: #8a2be2;
      background-color: #f8f9fa;
      border-color: #adb5bd;
    }

    .table-action-btn {
      min-width: 100px;
      max-width: 130px;
      height: 38px;
      border-radius: 16px !important;
      padding: 8px 14px !important;
      font-weight: 600;
      font-size: 0.92rem;
      box-shadow: 0 2px 8px rgba(0,0,0,0.12);
      transition: transform 0.2s ease, opacity 0.2s ease;
      display: inline-flex;
      justify-content: center;
      align-items: center;
      text-align: center;
      white-space: nowrap;
    }

    .table-action-btn:hover {
      transform: translateY(-1px);
      opacity: 0.95;
    }

    .table-action-select {
      min-width: 110px;
      max-width: 130px;
      border-radius: 16px;
      background-color: #FDCFFA;
      border-color: #D78FEE;
      font-size: 0.88rem;
      height: 38px;
      padding: 0 12px;
    }

    .pagination .page-item.active .page-link {
      background-color: #9B5DE0;
      border-color: #9B5DE0;
      color: white;
    }

    .pagination .page-item.disabled .page-link {
      color: #6c757d;
      background-color: #fff;
      border-color: #dee2e6;
    }
  

    /* =========================================================
       USERS / RECLAMATIONS TABLE HEADER LIGHT MODE FIX
       Keep table headers readable in light mode.
       ========================================================= */

    body.light-mode table thead,
    body.light-mode table thead tr,
    body.light-mode table thead th,
    html[data-theme="light"] body table thead,
    html[data-theme="light"] body table thead tr,
    html[data-theme="light"] body table thead th {
      background: #f3f0ff !important;
      background-color: #f3f0ff !important;
      color: #5b4fff !important;
      border-color: #e5e7eb !important;
    }

    body.light-mode .table thead,
    body.light-mode .table thead tr,
    body.light-mode .table thead th,
    html[data-theme="light"] body .table thead,
    html[data-theme="light"] body .table thead tr,
    html[data-theme="light"] body .table thead th {
      background: #f3f0ff !important;
      background-color: #f3f0ff !important;
      color: #5b4fff !important;
      border-color: #e5e7eb !important;
    }

    body.light-mode table tbody tr,
    body.light-mode table tbody td,
    html[data-theme="light"] body table tbody tr,
    html[data-theme="light"] body table tbody td {
      background: #ffffff !important;
      background-color: #ffffff !important;
      color: #111827 !important;
      border-color: #e5e7eb !important;
    }

    body.light-mode table tbody tr:hover,
    body.light-mode table tbody tr:hover td,
    html[data-theme="light"] body table tbody tr:hover,
    html[data-theme="light"] body table tbody tr:hover td {
      background: #f8fafc !important;
      background-color: #f8fafc !important;
    }

  </style>
</head>

<body class="cre8-admin-layout"><?php cre8_bo_early_theme_print_body_script(); ?>
  <!-- Success notification -->
  <?php if (isset($_GET['success']) && $_GET['success'] === 'reponse_envoyee'): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert" style="position: fixed; top: 20px; right: 20px; width: 350px; z-index: 9999; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
    <strong>✅ Success!</strong> Your reply has been sent successfully and the user has been notified by email.
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
  <script>
    // Auto-dismiss après 5 secondes
    setTimeout(() => {
      const alert = document.querySelector('.alert');
      if (alert) {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
      }
    }, 5000);
  </script>
  <?php endif; ?>

  <div class="container-scroller">
    <?php
    $backActive = 'reclamations';
    require_once __DIR__ . '/../layout/sidebar.php';
    ?>
    <div class="container-fluid page-body-wrapper">
      <?php require_once __DIR__ . '/../layout/header.php'; ?>
      <div class="main-panel">
        <div class="content-wrapper">
          <div class="row">

          </div>
          <!-- ===================== ADVANCED STATISTICS ===================== -->
          <div class="row mb-4">

            <!-- KPI Cards -->
            <div class="col-md-3 mb-3">
              <div class="card shadow-sm text-center p-4" style="background: linear-gradient(135deg, #9B5DE0 0%, #B771E5 100%); color: white; border-radius: 10px;">
                <i class="mdi mdi-file-document-outline" style="font-size: 2rem; margin-bottom: 10px;"></i>
                <h6 class="mb-2">Total Complaints</h6>
                <h3 class="mb-0"><?php echo $stats['total']; ?></h3>
                <small class="mt-2 opacity-75">All complaints</small>
              </div>
            </div>

            <div class="col-md-3 mb-3">
              <div class="card shadow-sm text-center p-4" style="background: linear-gradient(135deg, #D78FEE 0%, #C96FE8 100%); color: white; border-radius: 10px;">
                <i class="mdi mdi-clock-outline" style="font-size: 2rem; margin-bottom: 10px;"></i>
                <h6 class="mb-2">Pending</h6>
                <h3 class="mb-0"><?php echo $stats['en_attente']; ?></h3>
                <small class="mt-2 opacity-75">To be processed</small>
              </div>
            </div>

            <div class="col-md-3 mb-3">
              <div class="card shadow-sm text-center p-4" style="background: linear-gradient(135deg, #AEEA94 0%, #99D98E 100%); color: #2d5016; border-radius: 10px;">
                <i class="mdi mdi-check-circle-outline" style="font-size: 2rem; margin-bottom: 10px;"></i>
                <h6 class="mb-2">Processed</h6>
                <h3 class="mb-0"><?php echo $stats['traitee']; ?></h3>
                <small class="mt-2 opacity-75">Resolved</small>
              </div>
            </div>

            <div class="col-md-3 mb-3">
              <div class="card shadow-sm text-center p-4" style="background: linear-gradient(135deg, #E11D74 0%, #D01565 100%); color: white; border-radius: 10px;">
                <i class="mdi mdi-alert-circle-outline" style="font-size: 2rem; margin-bottom: 10px;"></i>
                <h6 class="mb-2">High Priority</h6>
                <h3 class="mb-0"><?php echo $stats['haute']; ?></h3>
                <small class="mt-2 opacity-75">Urgent</small>
              </div>
            </div>

          </div>

          <!-- ===================== GRAPHES AREA CHARTS ===================== -->
          <div class="row mb-4">

            <!-- Area Chart - Complaints by Status -->
            <div class="col-lg-12 mb-3">
              <div class="card shadow-sm">
                <div class="card-body">
                  <h5 class="card-title mb-4">📊 Complaint Status Trend (Timeline)</h5>
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
                  <h5 class="card-title mb-4">⚡ Priority Distribution (Timeline)</h5>
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

                  <h5 class="mb-3">Complaint Management</h5>

                  <!-- Search and Filter -->
                  <div class="row mb-3">
                    <div class="col-md-6">
                      <form method="GET" action="reclamations.php" class="d-flex gap-2">
                        <input type="text" name="search" class="form-control" placeholder="Search by user or description..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        <input type="hidden" name="priorite" value="<?php echo isset($_GET['priorite']) ? htmlspecialchars($_GET['priorite']) : ''; ?>">
                        <button type="submit" class="btn btn-sm" style="background-color: #9B5DE0; color: white; width: 90px;">Search</button>
                      </form>
                    </div>
                    <div class="col-md-6">
                      <form method="GET" action="reclamations.php" class="d-flex gap-2">
                        <input type="hidden" name="search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        <select name="priorite" class="form-select form-select-sm" onchange="this.form.submit()" style="background-color: #FDCFFA; border-color: #D78FEE;">
                          <option value="">Filter by priority</option>
                          <option value="haute" <?php echo isset($_GET['priorite']) && $_GET['priorite'] == 'haute' ? 'selected' : ''; ?>>High</option>
                          <option value="moyenne" <?php echo isset($_GET['priorite']) && $_GET['priorite'] == 'moyenne' ? 'selected' : ''; ?>>Medium</option>
                          <option value="basse" <?php echo isset($_GET['priorite']) && $_GET['priorite'] == 'basse' ? 'selected' : ''; ?>>Low</option>
                        </select>
                      </form>
                    </div>
                  </div>

                  <div class="table-responsive">
                    <table class="table table-hover align-middle">
                      <thead class="table-dark">
                        <tr>
                          <th>ID</th>
                          <th>User</th>
                          <th>Description</th>
                          <th>Date</th>
                          <th>Priority</th>
                          <th>Status</th>
                          <th>Response</th>
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
                            <td><?php echo ($rec['priorite'] == 'haute' ? 'High' : ($rec['priorite'] == 'moyenne' ? 'Medium' : 'Low')); ?></td>

                            <td>
                              <span class="badge bg-<?php echo ($rec['statut'] == 'traitee') ? 'success' : 'warning'; ?>">
                                <?php echo ($rec['statut'] == 'traitee') ? 'Processed' : 'Pending'; ?>
                              </span>
                            </td>

                            <td>
                              <?php if ($rec['reponse']): ?>
                                <button type="button" class="btn table-action-btn text-white" style="background-color: #9B5DE0;"
                                        data-bs-toggle="modal"
                                        data-bs-target="#replyViewModal<?php echo $rec['id']; ?>">
                                  View
                                </button>
                              <?php else: ?>
                                <span class="badge bg-secondary">None</span>
                              <?php endif; ?>
                            </td>

                            <td>
                              <div class="d-flex gap-2" style="flex-wrap: wrap;">
                                <!-- Reply -->
                                <button type="button" class="btn table-action-btn text-white" style="background-color: #9B5DE0;"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modal<?php echo $rec['id']; ?>">
                                  Reply
                                </button>

                                <!-- Delete -->
                                <button type="button" class="btn table-action-btn text-white" style="background-color: #D78FEE;"
                                  onclick="deleteReclamation(<?php echo $rec['id']; ?>)">
                                  🗑
                                </button>

                                <!-- Edit -->
                                <select class="form-select form-select-sm table-action-select"
                                  onchange="updateStatus(<?php echo $rec['id']; ?>, this.value)">
                                  <option value="">Status</option>
                                  <option value="en_attente" <?php if ($rec['statut'] == 'en_attente')
                                    echo 'selected'; ?>>Pending</option>
                                  <option value="traitee" <?php if ($rec['statut'] == 'traitee')
                                    echo 'selected'; ?>>Processed</option>
                                </select>
                              </div>
                            </td>
                          </tr>
                          <div class="modal fade" id="modal<?php echo $rec['id']; ?>" tabindex="-1">
              <div class="modal-dialog">
                <div class="modal-content">

                  <form method="POST" action="ajouterReponse.php" id="formReply<?php echo $rec['id']; ?>" onsubmit="return validateReply(this)">

                    <div class="modal-header">
                      <h5 class="modal-title">Reply</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                      <input type="hidden" name="idReclamation" value="<?php echo $rec['id']; ?>">

                      <textarea name="contenu" class="form-control" id="replyContent<?php echo $rec['id']; ?>"
                                placeholder="Your reply..."></textarea>
                      <small class="text-danger d-none" id="replyError<?php echo $rec['id']; ?>">Please enter a reply.</small>
                    </div>

                    <div class="modal-footer d-flex justify-content-between">
                      <button type="button" class="btn table-action-btn btn-secondary" style="width: 100px;" data-bs-dismiss="modal">Cancel</button>
                      <button type="submit" class="btn table-action-btn btn-success" style="width: 100px;">Send</button>
                    </div>

                  </form>

                </div>
              </div>
            </div>

            <!-- Modal to view full response -->
            <div class="modal fade" id="replyViewModal<?php echo $rec['id']; ?>" tabindex="-1">
              <div class="modal-dialog">
                <div class="modal-content">

                  <div class="modal-header">
                    <h5 class="modal-title">Response</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>

                  <div class="modal-body">
                    <p><?php echo nl2br(htmlspecialchars($rec['reponse'] ?? '')); ?></p>
                    <?php if ($rec['date_reponse']): ?>
                      <small class="text-muted">Date: <?php echo $rec['date_reponse']; ?></small>
                    <?php endif; ?>
                  </div>

                  <div class="modal-footer d-flex justify-content-between gap-2">
                    <!-- Edit response -->
                    <button type="button" class="btn table-action-btn text-white" style="background-color: #9B5DE0;"
                            data-bs-dismiss="modal"
                            data-bs-toggle="modal"
                            data-bs-target="#editReplyModal<?php echo $rec['id']; ?>">
                      Edit
                    </button>

                    <!-- Delete response -->
                    <button type="button" class="btn table-action-btn text-white" style="background-color: #D78FEE;"
                            onclick="deleteReply(<?php echo $rec['id']; ?>)">
                      Delete
                    </button>

                    <button type="button" class="btn table-action-btn btn-secondary" style="width: 100px;" data-bs-dismiss="modal">Close</button>
                </div>
              </div>
            </div>

            <!-- Modal to edit response -->
            <div class="modal fade" id="editReplyModal<?php echo $rec['id']; ?>" tabindex="-1">
              <div class="modal-dialog">
                <div class="modal-content">

                  <form method="POST" action="modifierReponse.php" id="formEditReply<?php echo $rec['id']; ?>" onsubmit="return validateEditReply(this)">

                    <div class="modal-header">
                      <h5 class="modal-title">Edit response</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                      <input type="hidden" name="idReclamation" value="<?php echo $rec['id']; ?>">
                      <textarea name="contenu" class="form-control" id="editReplyContent<?php echo $rec['id']; ?>" placeholder="Edit your response..."><?php echo htmlspecialchars($rec['reponse'] ?? ''); ?></textarea>
                      <small class="text-danger d-none" id="editReplyError<?php echo $rec['id']; ?>">Please enter a valid response.</small>
                    </div>

                    <div class="modal-footer d-flex justify-content-between">
                      <button type="button" class="btn btn-secondary" style="width: 80px;" data-bs-dismiss="modal">Cancel</button>
                      <button type="submit" class="btn btn-success" style="width: 80px;">Update</button>
                    </div>

                  </form>

                </div>
              </div>
            </div>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>

                  <!-- Pagination -->
                  <?php if ($totalPages > 1): ?>
                  <div class="d-flex justify-content-center mt-4">
                      <nav aria-label="Complaints pagination">
                          <ul class="pagination">
                              <!-- Previous button -->
                              <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                                  <a class="page-link" href="?page=<?= $currentPage - 1 ?>&search=<?= urlencode($search) ?>&priorite=<?= urlencode($priorite) ?>" aria-label="Previous">
                                      <span aria-hidden="true">&laquo;</span>
                                  </a>
                              </li>

                              <!-- Page numbers -->
                              <?php
                              $startPage = max(1, $currentPage - 2);
                              $endPage = min($totalPages, $currentPage + 2);

                              if ($startPage > 1): ?>
                                  <li class="page-item">
                                      <a class="page-link" href="?page=1&search=<?= urlencode($search) ?>&priorite=<?= urlencode($priorite) ?>">1</a>
                                  </li>
                                  <?php if ($startPage > 2): ?>
                                      <li class="page-item disabled">
                                          <span class="page-link">...</span>
                                      </li>
                                  <?php endif; ?>
                              <?php endif; ?>

                              <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                  <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                                      <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&priorite=<?= urlencode($priorite) ?>"><?= $i ?></a>
                                  </li>
                              <?php endfor; ?>

                              <?php if ($endPage < $totalPages): ?>
                                  <?php if ($endPage < $totalPages - 1): ?>
                                      <li class="page-item disabled">
                                          <span class="page-link">...</span>
                                      </li>
                                  <?php endif; ?>
                                  <li class="page-item">
                                      <a class="page-link" href="?page=<?= $totalPages ?>&search=<?= urlencode($search) ?>&priorite=<?= urlencode($priorite) ?>"><?= $totalPages ?></a>
                                  </li>
                              <?php endif; ?>

                              <!-- Next button -->
                              <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                                  <a class="page-link" href="?page=<?= $currentPage + 1 ?>&search=<?= urlencode($search) ?>&priorite=<?= urlencode($priorite) ?>" aria-label="Next">
                                      <span aria-hidden="true">&raquo;</span>
                                  </a>
                              </li>
                          </ul>
                      </nav>
                  </div>
                  <div class="text-center mt-2 text-muted">
                      Page <?= $currentPage ?> of <?= $totalPages ?> (<?= $totalReclamations ?> total complaints)
                  </div>
                  <?php endif; ?>

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
    <script src="../layout/back-layout.js?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../layout/back-layout.js')); ?>"></script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- endinject -->
    <!-- Custom js for this page -->
    <script src="assets/js/dashboard.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
      // Palette de couleurs cohérente
      const colors = {
        statutEnAttente: '#D78FEE',
        statutTraitee: '#AEEA94',
        prioriteHaute: '#E11D74',
        prioriteMoyenne: '#FDFFB8',
        prioriteBasse: '#4E56C0'
      };

      const reclamationStats = {
        enAttente: <?= intval($stats['en_attente']) ?>,
        traitee: <?= intval($stats['traitee']) ?>,
        haute: <?= intval($stats['haute']) ?>,
        moyenne: <?= intval($stats['moyenne']) ?>,
        basse: <?= intval($stats['basse']) ?>
      };

      const dateLabels = <?= json_encode($statusTimeline['dates']) ?>;
      const statusEnAttente = <?= json_encode($statusTimeline['en_attente']) ?>;
      const statusTraitee = <?= json_encode($statusTimeline['traitee']) ?>;
      const priorityHaute = <?= json_encode($priorityTimeline['haute']) ?>;
      const priorityMoyenne = <?= json_encode($priorityTimeline['moyenne']) ?>;
      const priorityBasse = <?= json_encode($priorityTimeline['basse']) ?>;

      const ctxAreaStatut = document.getElementById('chartAreaStatut');
      new Chart(ctxAreaStatut, {
        type: 'line',
        data: {
          labels: dateLabels,
          datasets: [
            {
              label: 'Pending',
              data: statusEnAttente,
              borderColor: colors.statutEnAttente,
              backgroundColor: colors.statutEnAttente + '33',
              fill: true,
              tension: 0.4,
              pointRadius: 5,
              pointBackgroundColor: colors.statutEnAttente,
              pointBorderColor: '#fff',
              pointBorderWidth: 2,
              borderWidth: 3
            },
            {
              label: 'Processed',
              data: statusTraitee,
              borderColor: colors.statutTraitee,
              backgroundColor: colors.statutTraitee + '33',
              fill: true,
              tension: 0.4,
              pointRadius: 5,
              pointBackgroundColor: colors.statutTraitee,
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
            legend: { display: true },
            tooltip: {
              callbacks: {
                label: function(context) {
                  const value = context.parsed.y;
                  return value + ' complaints';
                }
              }
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                stepSize: Math.max(1, Math.ceil(Math.max(...statusEnAttente, ...statusTraitee) / 5))
              },
              grid: { color: 'rgba(0,0,0,0.05)' }
            },
            x: {
              grid: { display: false },
              ticks: { maxRotation: 0, minRotation: 0 }
            }
          }
        }
      });

      const ctxAreaPriorite = document.getElementById('chartAreaPriorite');
      new Chart(ctxAreaPriorite, {
        type: 'line',
        data: {
          labels: dateLabels,
          datasets: [
            {
              label: 'High',
              data: priorityHaute,
              borderColor: colors.prioriteHaute,
              backgroundColor: colors.prioriteHaute + '33',
              fill: true,
              tension: 0.4,
              pointRadius: 5,
              pointBackgroundColor: colors.prioriteHaute,
              pointBorderColor: '#fff',
              pointBorderWidth: 2,
              borderWidth: 3
            },
            {
              label: 'Medium',
              data: priorityMoyenne,
              borderColor: colors.prioriteMoyenne,
              backgroundColor: colors.prioriteMoyenne + '33',
              fill: true,
              tension: 0.4,
              pointRadius: 5,
              pointBackgroundColor: colors.prioriteMoyenne,
              pointBorderColor: '#fff',
              pointBorderWidth: 2,
              borderWidth: 3
            },
            {
              label: 'Low',
              data: priorityBasse,
              borderColor: colors.prioriteBasse,
              backgroundColor: colors.prioriteBasse + '33',
              fill: true,
              tension: 0.4,
              pointRadius: 5,
              pointBackgroundColor: colors.prioriteBasse,
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
            legend: { display: true },
            tooltip: {
              callbacks: {
                label: function(context) {
                  const value = context.parsed.y;
                  return value + ' complaints';
                }
              }
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                stepSize: Math.max(1, Math.ceil(Math.max(...priorityHaute, ...priorityMoyenne, ...priorityBasse) / 5))
              },
              grid: { color: 'rgba(0,0,0,0.05)' }
            },
            x: {
              grid: { display: false },
              ticks: { maxRotation: 0, minRotation: 0 }
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

      // Function to delete a complaint
      function deleteReclamation(id) {
        if (confirm('Are you sure you want to delete this complaint?')) {
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

      // Function to delete a reply
      function deleteReply(id) {
        if (confirm('Are you sure you want to delete this response?')) {
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
      
      // ===== VALIDATION JAVASCRIPT =====
      // Validation for adding a reply
      function validateReply(form) {
        const idReclamation = form.querySelector('input[name="idReclamation"]').value;
        const textarea = form.querySelector('textarea[name="contenu"]');
        const errorEl = document.getElementById('replyError' + idReclamation);
        
        const content = textarea.value.trim();
        
        // Validate content
        if (content === '') {
          errorEl.textContent = 'The response cannot be empty.';
          errorEl.classList.remove('d-none');
          textarea.classList.add('border-danger');
          return false;
        }
        
        // Validate minimum length (5 characters)
        if (content.length < 5) {
          errorEl.textContent = 'The response must contain at least 5 characters.';
          errorEl.classList.remove('d-none');
          textarea.classList.add('border-danger');
          return false;
        }
        
        // Validate maximum length (1000 characters)
        if (content.length > 1000) {
          errorEl.textContent = 'The response must not exceed 1000 characters.';
          errorEl.classList.remove('d-none');
          textarea.classList.add('border-danger');
          return false;
        }
        
        // Validate non-whitespace content
        if (!/\S/.test(content)) {
          errorEl.textContent = 'The response cannot contain only spaces.';
          errorEl.classList.remove('d-none');
          textarea.classList.add('border-danger');
          return false;
        }
        
        // Success
        errorEl.classList.add('d-none');
        textarea.classList.remove('border-danger');
        return true;
      }
      
      // Validation for editing a reply
      function validateEditReply(form) {
        const idReclamation = form.querySelector('input[name="idReclamation"]').value;
        const textarea = form.querySelector('textarea[name="contenu"]');
        const errorEl = document.getElementById('editReplyError' + idReclamation);
        
        const content = textarea.value.trim();
        
        // Validate content
        if (content === '') {
          errorEl.textContent = 'The response cannot be empty.';
          errorEl.classList.remove('d-none');
          textarea.classList.add('border-danger');
          return false;
        }
        
        // Validate minimum length (5 characters)
        if (content.length < 5) {
          errorEl.textContent = 'The response must contain at least 5 characters.';
          errorEl.classList.remove('d-none');
          textarea.classList.add('border-danger');
          return false;
        }
        
        // Validate maximum length (1000 characters)
        if (content.length > 1000) {
          errorEl.textContent = 'The response must not exceed 1000 characters.';
          errorEl.classList.remove('d-none');
          textarea.classList.add('border-danger');
          return false;
        }
        
        // Validate non-whitespace content
        if (!/\S/.test(content)) {
          errorEl.textContent = 'The response cannot contain only spaces.';
          errorEl.classList.remove('d-none');
          textarea.classList.add('border-danger');
          return false;
        }
        
        // Success
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
</body>

</html>