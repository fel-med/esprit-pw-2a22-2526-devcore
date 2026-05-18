<?php
session_start();
require_once __DIR__ . '/../layout/early-theme.php';
require_once '../../../Controleur/session_helper.php';
require_once '../../../Controleur/reclamationC.php';
require_once '../../../Controleur/utilisateurC.php';

cc_require_admin('../../FrontOffice/utilisateur/login.php');
$viewerId = cc_current_user_id();
$viewerRole = cc_current_user_role();
$reclamationC = new ReclamationC();

// Récupérer les paramètres de recherche et tri
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$rawPriorite = isset($_GET['priorite']) ? trim($_GET['priorite']) : '';
$priorityAliasMap = [
    'haute' => 'haute',
    'high' => 'haute',
    'normale' => 'normale',
    'normal' => 'normale',
    'moyenne' => 'normale',
    'medium' => 'normale',
    'faible' => 'faible',
    'low' => 'faible',
    'basse' => 'faible',
];
$prioriteKey = strtolower($rawPriorite);
$priorite = $priorityAliasMap[$prioriteKey] ?? '';

if (!function_exists('cre8_bo_priority_label')) {
    function cre8_bo_priority_label($value) {
        $key = strtolower(trim((string) $value));
        if (in_array($key, ['haute', 'high'], true)) {
            return 'High';
        }
        if (in_array($key, ['normale', 'normal', 'moyenne', 'medium'], true)) {
            return 'Normal';
        }
        if (in_array($key, ['faible', 'low', 'basse'], true)) {
            return 'Low';
        }
        return $value !== '' ? ucfirst($value) : 'Normal';
    }
}
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 4; // Records per page

$result = $reclamationC->afficherReclamationsAdmin($search, $priorite, $page, $limit, $viewerRole);
$stmt = is_array($result) && isset($result['stmt']) ? $result['stmt'] : false;
$liste = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
$totalReclamations = is_array($result) && isset($result['total']) ? intval($result['total']) : 0;
$totalPages = is_array($result) && isset($result['totalPages']) ? max(1, intval($result['totalPages'])) : 1;
$currentPage = is_array($result) && isset($result['page']) ? max(1, intval($result['page'])) : 1;

$stats = $reclamationC->statistiques($viewerRole);
$statusTimeline = $reclamationC->getReclamationStatusTimeline(14, $viewerRole);
$priorityTimeline = $reclamationC->getReclamationPriorityTimeline(14, $viewerRole);

// Calculate priority statistics
$haute = 0;
$normale = 0;
$faible = 0;
foreach ($liste as $rec) {
    $priorityKey = strtolower(trim((string) ($rec['priorite'] ?? '')));
    if (in_array($priorityKey, ['haute', 'high'], true)) {
        $haute++;
    } elseif (in_array($priorityKey, ['normale', 'normal', 'moyenne', 'medium'], true)) {
        $normale++;
    } else {
        $faible++;
    }
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
  <link rel="icon" type="image/png" sizes="16x16" href="../../public/images/favicon-16.png">
  <link rel="icon" type="image/png" sizes="32x32" href="../../public/images/favicon-32.png">
  <link rel="shortcut icon" type="image/png" href="../../public/images/favicon-32.png">
  <link rel="apple-touch-icon" sizes="180x180" href="../../public/images/apple-touch-icon.png">
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
  <link rel="stylesheet" href="user-center-admin.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/user-center-admin.css')); ?>">
  <link rel="stylesheet" href="../unified-table-admin.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../unified-table-admin.css')); ?>">
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

  <div class="container-scroller cre8-admin-page">
    <?php
    $backActive = 'reclamations';
    require_once __DIR__ . '/../layout/sidebar.php';
    ?>
    <div class="container-fluid page-body-wrapper cre8-admin-main">
      <?php require_once __DIR__ . '/../layout/header.php'; ?>
      <div class="main-panel">
        <div class="content-wrapper user-center-shell">
          <section class="uc-page-head">
            <div>
              <p class="uc-kicker">User Center</p>
              <h1>Complaint center</h1>
              <p>Review user complaints, reply to reports, and react quickly to suspension appeals.</p>
            </div>
          </section>

          <nav class="uc-entity-tabs" aria-label="User Center sections">
            <a href="index.php" class="uc-entity-tab">
              <span class="uc-tab-icon"><i class="mdi mdi-account-group"></i></span>
              <span><strong>Users</strong><small>Accounts and roles</small></span>
            </a>
            <a href="reclamations.php" class="uc-entity-tab is-active">
              <span class="uc-tab-icon"><i class="mdi mdi-alert-decagram"></i></span>
              <span><strong>Complaints</strong><small>Reports and appeals</small></span>
            </a>
          </nav>

          <section class="uc-statistics-panel" data-uc-stats>
            <div class="uc-section-head">
              <div>
                <h2>Workspace statistics</h2>
                <p>Live indicators and charts for complaint activity.</p>
              </div>
              <button type="button" class="uc-secondary-btn" data-uc-stats-toggle>Hide statistics</button>
            </div>

            <div class="uc-kpi-grid">
              <article class="uc-kpi-card uc-kpi-purple">
                <span>Total complaints</span>
                <strong><?php echo intval($stats['total'] ?? $totalReclamations); ?></strong>
                <small>All visible reports</small>
              </article>
              <article class="uc-kpi-card uc-kpi-pink">
                <span>Pending</span>
                <strong><?php echo intval($stats['en_attente'] ?? 0); ?></strong>
                <small>Needs admin review</small>
              </article>
              <article class="uc-kpi-card uc-kpi-green">
                <span>Processed</span>
                <strong><?php echo intval($stats['traitee'] ?? 0); ?></strong>
                <small>Resolved complaints</small>
              </article>
              <article class="uc-kpi-card uc-kpi-magenta">
                <span>High priority</span>
                <strong><?php echo intval($stats['haute'] ?? 0); ?></strong>
                <small>Urgent or appeals</small>
              </article>
              <article class="uc-kpi-card uc-kpi-blue">
                <span>Low priority</span>
                <strong><?php echo intval($stats['faible'] ?? $stats['basse'] ?? 0); ?></strong>
                <small>Normal queue pressure</small>
              </article>
            </div>

            <div class="uc-stats-body">
              <article class="uc-chart-card">
                <div class="uc-chart-head">
                  <h3>Complaint status trend</h3>
                  <p>Pending versus processed complaints over time.</p>
                </div>
                <div class="uc-chart-canvas">
                  <canvas id="chartAreaStatut"></canvas>
                </div>
              </article>
              <article class="uc-chart-card">
                <div class="uc-chart-head">
                  <h3>Priority distribution</h3>
                  <p>High, normal, and low priority evolution.</p>
                </div>
                <div class="uc-chart-canvas">
                  <canvas id="chartAreaPriorite"></canvas>
                </div>
              </article>
            </div>
          </section>

          <section class="uc-filter-card">
            <div class="uc-filter-head">
              <div>
                <h2>Admin filters</h2>
                <p>Filter by user, description, or priority.</p>
              </div>
            </div>
            <form method="GET" action="reclamations.php" class="uc-filter-grid">
              <label class="uc-filter-field uc-filter-search">
                <span>Search</span>
                <input type="text" name="search" placeholder="User or description..." value="<?php echo htmlspecialchars($search); ?>">
              </label>
              <label class="uc-filter-field">
                <span>Priority</span>
                <select name="priorite">
                  <option value="">All priorities</option>
                  <option value="haute" <?php echo $priorite === 'haute' ? 'selected' : ''; ?>>High</option>
                  <option value="normale" <?php echo $priorite === 'normale' ? 'selected' : ''; ?>>Normal</option>
                  <option value="faible" <?php echo $priorite === 'faible' ? 'selected' : ''; ?>>Low</option>
                </select>
              </label>
              <div class="uc-filter-actions">
                <button type="submit" class="uc-primary-btn">Apply filters</button>
                <a href="reclamations.php" class="uc-soft-btn">Reset</a>
              </div>
            </form>
          </section>

          <section class="uc-table-card">
            <div class="uc-table-head">
              <div>
                <h2>Complaint list</h2>
                <p><?php echo intval($totalReclamations); ?> complaints found</p>
              </div>
            </div>

            <div id="ucResultsRegion" class="uc-results-region" aria-live="polite">
              <div class="uc-table-wrap">
                <table class="uc-table uc-complaints-table">
                  <thead>
                    <tr>
                      <th class="uc-col-id">ID</th>
                      <th>User</th>
                      <th>Complaint</th>
                      <th>Date</th>
                      <th>Priority</th>
                      <th>Status</th>
                      <th>Response</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (empty($liste)): ?>
                      <tr>
                        <td colspan="8">
                          <div class="uc-empty-state">
                            <span><i class="mdi mdi-alert-circle-outline"></i></span>
                            <strong>No complaints found</strong>
                            <small>Try changing the search or priority filter.</small>
                          </div>
                        </td>
                      </tr>
                    <?php endif; ?>

                    <?php foreach ($liste as $rec): ?>
                      <?php
                        $complainantRole = cc_normalize_role($rec['complainant_role'] ?? '');
                        $complainantStatus = cc_normalize_status($rec['complainant_statut'] ?? '');
                        $isSuspensionAppeal = stripos((string)($rec['description'] ?? ''), '[Suspension Appeal]') !== false
                            || $complainantStatus === 'suspendu';
                        $complainantUserRow = [
                            'id' => (int)($rec['complainant_id'] ?? 0),
                            'role' => $complainantRole,
                            'statut' => $complainantStatus,
                            'suspended_by' => $rec['suspended_by'] ?? null,
                            'suspended_by_role' => cc_normalize_role($rec['suspended_by_role'] ?? ''),
                            'suspended_at' => $rec['suspended_at'] ?? null,
                            'suspension_reason' => $rec['suspension_reason'] ?? null,
                        ];
                        $canQuickReactivate = $complainantStatus === 'suspendu'
                            && cc_can_view_reclamation_from_role($viewerRole, $complainantRole)
                            && cc_can_reactivate_suspension($viewerId, $viewerRole, $complainantUserRow);
                        $priorityLabel = cre8_bo_priority_label($rec['priorite'] ?? '');
                        $priorityClass = strtolower($priorityLabel);
                        $statusClass = ($rec['statut'] == 'traitee') ? 'traitee' : 'en_attente';
                      ?>
                      <tr>
                        <td class="uc-col-id">#<?php echo (int)$rec['id']; ?></td>
                        <td>
                          <div class="uc-person-cell">
                            <strong><?php echo htmlspecialchars($rec['nom'] ?? 'Unknown'); ?></strong>
                            <?php if (!empty($rec['complainant_role'])): ?>
                              <span><?php echo htmlspecialchars($rec['complainant_role']); ?></span>
                            <?php endif; ?>
                          </div>
                        </td>
                        <td>
                          <div class="uc-complaint-text">
                            <?php if ($isSuspensionAppeal): ?>
                              <span class="uc-badge uc-status-suspendu">Suspension appeal</span>
                            <?php endif; ?>
                            <p><?php echo nl2br(htmlspecialchars((string)($rec['description'] ?? ''))); ?></p>
                            <?php if ($isSuspensionAppeal): ?>
                              <small>
                                Status: <?= htmlspecialchars($complainantStatus !== '' ? $complainantStatus : 'unknown') ?>
                                <?php if (!empty($rec['suspended_by_role'])): ?>
                                  · Suspended by: <?= htmlspecialchars(cc_normalize_role($rec['suspended_by_role'])) ?>
                                <?php endif; ?>
                                <?php if (!empty($rec['suspended_at'])): ?>
                                  · At: <?= htmlspecialchars((string)$rec['suspended_at']) ?>
                                <?php endif; ?>
                                <?php if (!empty($rec['suspension_reason'])): ?>
                                  <br>Reason: <?= htmlspecialchars((string)$rec['suspension_reason']) ?>
                                <?php endif; ?>
                              </small>
                            <?php endif; ?>
                          </div>
                        </td>
                        <td class="uc-date-cell"><?php echo htmlspecialchars((string)$rec['date_creation']); ?></td>
                        <td><span class="uc-badge uc-priority-<?= htmlspecialchars($priorityClass) ?>"><?php echo htmlspecialchars($priorityLabel); ?></span></td>
                        <td><span class="uc-badge uc-status-<?= htmlspecialchars($statusClass) ?>"><?php echo $rec['statut'] == 'traitee' ? 'Processed' : 'Pending'; ?></span></td>
                        <td>
                          <?php if ($rec['reponse']): ?>
                            <button type="button" class="uc-action-btn uc-action-primary" data-bs-toggle="modal" data-bs-target="#replyViewModal<?php echo (int)$rec['id']; ?>">
                              View
                            </button>
                          <?php else: ?>
                            <span class="uc-badge uc-status-inactif">None</span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <div class="uc-actions">
                            <button type="button" class="uc-action-btn uc-action-primary" data-bs-toggle="modal" data-bs-target="#modal<?php echo (int)$rec['id']; ?>">
                              Reply
                            </button>
                            <button type="button" class="uc-action-btn uc-action-soft-danger" onclick="deleteReclamation(<?php echo (int)$rec['id']; ?>)">
                              Delete
                            </button>
                            <select class="uc-inline-select uc-status-select" onchange="updateStatus(<?php echo (int)$rec['id']; ?>, this.value)">
                              <option value="">Status</option>
                              <option value="en_attente" <?php if ($rec['statut'] == 'en_attente') echo 'selected'; ?>>Pending</option>
                              <option value="traitee" <?php if ($rec['statut'] == 'traitee') echo 'selected'; ?>>Processed</option>
                            </select>
                            <?php if ($canQuickReactivate): ?>
                              <form method="POST" action="reclamation_reactivate_user.php" class="m-0" onsubmit="return confirm('Reactivate this suspended account?');">
                                <input type="hidden" name="idReclamation" value="<?php echo (int)$rec['id']; ?>">
                                <button type="submit" class="uc-action-btn uc-action-success">Reactivate</button>
                              </form>
                            <?php endif; ?>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>

              <div class="uc-pagination">
                <p>Page <?= $currentPage ?> of <?= $totalPages ?> · <?= $totalReclamations ?> complaints</p>
                <?php if ($totalPages > 1): ?>
                  <nav aria-label="Complaints pagination">
                    <a class="uc-page-btn <?= $currentPage <= 1 ? 'is-disabled' : '' ?>" href="?page=<?= max(1, $currentPage - 1) ?>&search=<?= urlencode($search) ?>&priorite=<?= urlencode($priorite) ?>">&laquo;</a>
                    <?php
                      $startPage = max(1, $currentPage - 2);
                      $endPage = min($totalPages, $currentPage + 2);
                      if ($startPage > 1):
                    ?>
                      <a class="uc-page-btn" href="?page=1&search=<?= urlencode($search) ?>&priorite=<?= urlencode($priorite) ?>">1</a>
                      <?php if ($startPage > 2): ?><span class="uc-page-ellipsis">...</span><?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                      <a class="uc-page-btn <?= $i == $currentPage ? 'is-active' : '' ?>" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&priorite=<?= urlencode($priorite) ?>"><?= $i ?></a>
                    <?php endfor; ?>

                    <?php if ($endPage < $totalPages): ?>
                      <?php if ($endPage < $totalPages - 1): ?><span class="uc-page-ellipsis">...</span><?php endif; ?>
                      <a class="uc-page-btn" href="?page=<?= $totalPages ?>&search=<?= urlencode($search) ?>&priorite=<?= urlencode($priorite) ?>"><?= $totalPages ?></a>
                    <?php endif; ?>

                    <a class="uc-page-btn <?= $currentPage >= $totalPages ? 'is-disabled' : '' ?>" href="?page=<?= min($totalPages, $currentPage + 1) ?>&search=<?= urlencode($search) ?>&priorite=<?= urlencode($priorite) ?>">&raquo;</a>
                  </nav>
                <?php endif; ?>
              </div>

              <?php foreach ($liste as $rec): ?>
            <div class="modal fade uc-modal" id="modal<?php echo (int)$rec['id']; ?>" tabindex="-1">
              <div class="modal-dialog">
                <div class="modal-content">
                  <form method="POST" action="ajouterReponse.php" id="formReply<?php echo (int)$rec['id']; ?>" onsubmit="return validateReply(this)">
                    <div class="modal-header">
                      <h5 class="modal-title">Reply</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                      <input type="hidden" name="idReclamation" value="<?php echo (int)$rec['id']; ?>">
                      <textarea name="contenu" class="form-control" id="replyContent<?php echo (int)$rec['id']; ?>" placeholder="Your reply..."></textarea>
                      <small class="text-danger d-none" id="replyError<?php echo (int)$rec['id']; ?>">Please enter a reply.</small>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="uc-action-btn uc-action-muted" data-bs-dismiss="modal">Cancel</button>
                      <button type="submit" class="uc-action-btn uc-action-success">Send</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>

            <div class="modal fade uc-modal" id="replyViewModal<?php echo (int)$rec['id']; ?>" tabindex="-1">
              <div class="modal-dialog">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title">Response</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <p><?php echo nl2br(htmlspecialchars($rec['reponse'] ?? '')); ?></p>
                    <?php if ($rec['date_reponse']): ?>
                      <small class="text-muted">Date: <?php echo htmlspecialchars((string)$rec['date_reponse']); ?></small>
                    <?php endif; ?>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="uc-action-btn uc-action-primary" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#editReplyModal<?php echo (int)$rec['id']; ?>">Edit</button>
                    <button type="button" class="uc-action-btn uc-action-soft-danger" onclick="deleteReply(<?php echo (int)$rec['id']; ?>)">Delete</button>
                    <button type="button" class="uc-action-btn uc-action-muted" data-bs-dismiss="modal">Close</button>
                  </div>
                </div>
              </div>
            </div>

            <div class="modal fade uc-modal" id="editReplyModal<?php echo (int)$rec['id']; ?>" tabindex="-1">
              <div class="modal-dialog">
                <div class="modal-content">
                  <form method="POST" action="modifierReponse.php" id="formEditReply<?php echo (int)$rec['id']; ?>" onsubmit="return validateEditReply(this)">
                    <div class="modal-header">
                      <h5 class="modal-title">Edit response</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                      <input type="hidden" name="idReclamation" value="<?php echo (int)$rec['id']; ?>">
                      <textarea name="contenu" class="form-control" id="editReplyContent<?php echo (int)$rec['id']; ?>" placeholder="Edit your response..."><?php echo htmlspecialchars($rec['reponse'] ?? ''); ?></textarea>
                      <small class="text-danger d-none" id="editReplyError<?php echo (int)$rec['id']; ?>">Please enter a valid response.</small>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="uc-action-btn uc-action-muted" data-bs-dismiss="modal">Cancel</button>
                      <button type="submit" class="uc-action-btn uc-action-success">Update</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
            </div>
          </section>
        </div>
        <!-- content-wrapper ends -->
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
  <script src="user-center-admin.js?v=<?php echo urlencode((string) filemtime(__DIR__ . '/user-center-admin.js')); ?>"></script>

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
        prioriteNormale: '#FDFFB8',
        prioriteFaible: '#4E56C0'
      };

      const reclamationStats = {
        enAttente: <?= intval($stats['en_attente']) ?>,
        traitee: <?= intval($stats['traitee']) ?>,
        haute: <?= intval($stats['haute']) ?>,
        normale: <?= intval($stats['normale'] ?? $stats['moyenne'] ?? 0) ?>,
        faible: <?= intval($stats['faible'] ?? $stats['basse'] ?? 0) ?>
      };

      const dateLabels = <?= json_encode($statusTimeline['dates']) ?>;
      const statusEnAttente = <?= json_encode($statusTimeline['en_attente']) ?>;
      const statusTraitee = <?= json_encode($statusTimeline['traitee']) ?>;
      const priorityHaute = <?= json_encode($priorityTimeline['haute']) ?>;
      const priorityNormale = <?= json_encode($priorityTimeline['normale'] ?? $priorityTimeline['moyenne'] ?? []) ?>;
      const priorityFaible = <?= json_encode($priorityTimeline['faible'] ?? $priorityTimeline['basse'] ?? []) ?>;

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
              label: 'Normal',
              data: priorityNormale,
              borderColor: colors.prioriteNormale,
              backgroundColor: colors.prioriteNormale + '33',
              fill: true,
              tension: 0.4,
              pointRadius: 5,
              pointBackgroundColor: colors.prioriteNormale,
              pointBorderColor: '#fff',
              pointBorderWidth: 2,
              borderWidth: 3
            },
            {
              label: 'Low',
              data: priorityFaible,
              borderColor: colors.prioriteFaible,
              backgroundColor: colors.prioriteFaible + '33',
              fill: true,
              tension: 0.4,
              pointRadius: 5,
              pointBackgroundColor: colors.prioriteFaible,
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
                stepSize: Math.max(1, Math.ceil(Math.max(...priorityHaute, ...priorityNormale, ...priorityFaible) / 5))
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
