<?php session_start(); ?>
<?php
require_once __DIR__ . '/../layout/early-theme.php';
require_once '../../../Controleur/session_helper.php';
require_once '../../../Controleur/utilisateurC.php';

cc_require_admin('../../FrontOffice/utilisateur/login.php');

$userC = new UtilisateurC();

// Récupérer les paramètres de recherche et tri
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$role = isset($_GET['role']) ? cc_normalize_role($_GET['role']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 4;

$usersResult = $userC->afficherUsers($search, $role, $page, $limit);
$users = is_array($usersResult) && isset($usersResult['data']) ? $usersResult['data'] : [];
$totalUsers = is_array($usersResult) && isset($usersResult['total']) ? intval($usersResult['total']) : count($users);
$totalPages = is_array($usersResult) && isset($usersResult['totalPages']) ? max(1, intval($usersResult['totalPages'])) : 1;
$currentPage = is_array($usersResult) && isset($usersResult['page']) ? max(1, intval($usersResult['page'])) : 1;

$stats = $userC->getStatistiquesUtilisateurs();
$actorId = cc_current_user_id();
$actorRole = cc_normalize_role(cc_current_user_role());
$assignableRoles = match ($actorRole) {
    'super_admin' => ['createur', 'marque', 'admin'],
    'hyper_admin' => ['createur', 'marque', 'admin', 'super_admin'],
    default => ['createur', 'marque'],
};
$roleLabels = [
    'createur' => 'Creator',
    'marque' => 'Brand',
    'admin' => 'Admin',
    'super_admin' => 'Super Admin',
    'hyper_admin' => 'Hyper Admin',
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <?php cre8_bo_early_theme_print_head_script(); ?>
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
  <link rel="stylesheet" href="../layout/back-layout.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../layout/back-layout.css')); ?>">
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
      background-color: #f8fafc !important;
      color: #111827 !important;
    }

    body.light-mode .container-scroller,
    body.light-mode .page-body-wrapper,
    body.light-mode .main-panel,
    body.light-mode .content-wrapper,
    body.light-mode .footer {
      background-color: #ffffff !important;
      color: #111827 !important;
      border-color: #e2e8f0 !important;
    }

    body.light-mode .page-title,
    body.light-mode .card-title,
    body.light-mode .card-description,
    body.light-mode .table th,
    body.light-mode .table td,
    body.light-mode .badge,
    body.light-mode .form-control,
    body.light-mode .form-select,
    body.light-mode .dropdown-item,
    body.light-mode .modal-title,
    body.light-mode .modal-body,
    body.light-mode .modal-footer {
      color: #111827 !important;
    }

    body.light-mode .card,
    body.light-mode .card-body,
    body.light-mode .table,
    body.light-mode .table-responsive,
    body.light-mode .dropdown-menu,
    body.light-mode .modal-content,
    body.light-mode .form-control,
    body.light-mode .form-select,
    body.light-mode textarea {
      background-color: #ffffff !important;
      color: #111827 !important;
      border-color: #d1d5db !important;
      box-shadow: 0 8px 25px rgba(15, 23, 42, 0.06) !important;
    }

    body.light-mode .btn:not(.btn-primary):not(.btn-secondary):not(.btn-success):not(.btn-danger):not(.btn-warning):not(.btn-info):not(.btn-light):not(.btn-dark):not(.btn-outline-primary):not(.btn-outline-secondary):not(.btn-outline-success):not(.btn-outline-danger):not(.btn-outline-warning):not(.btn-outline-info):not(.btn-outline-light):not(.btn-outline-dark):not(.btn-gradient):not(.btn-outline-gradient) {
      background-color: #ffffff !important;
      color: #111827 !important;
      border-color: #d1d5db !important;
      box-shadow: 0 8px 25px rgba(15, 23, 42, 0.06) !important;
    }

    body.light-mode .table thead {
      background-color: #e2e8f0 !important;
      color: #111827 !important;
    }

    body.light-mode .table tbody tr:hover {
      background-color: #f8fafc !important;
    }

    body.light-mode input.form-control,
    body.light-mode select.form-select,
    body.light-mode textarea.form-control {
      background-color: #ffffff !important;
      color: #111827 !important;
      border-color: #d1d5db !important;
    }

    body.light-mode .modal-content {
      background-color: #ffffff !important;
      color: #111827 !important;
      border-color: #d1d5db !important;
    }

    body.light-mode .modal-header {
      background-color: #f1f5f9 !important;
      border-color: #d1d5db !important;
    }

    body:not(.light-mode) {
      background-color: #0f131d !important;
      color: #e7ebff !important;
    }

    body:not(.light-mode) .container-scroller,
    body:not(.light-mode) .page-body-wrapper,
    body:not(.light-mode) .main-panel,
    body:not(.light-mode) .content-wrapper,
    body:not(.light-mode) .footer {
      background-color: #101520 !important;
      color: #e7ebff !important;
    }

    body:not(.light-mode) .card,
    body:not(.light-mode) .card-body,
    body:not(.light-mode) .table,
    body:not(.light-mode) .dropdown-menu,
    body:not(.light-mode) .modal-content,
    body:not(.light-mode) .form-control,
    body:not(.light-mode) .form-select,
    body:not(.light-mode) .btn-outline-primary {
      background-color: rgba(18,24,41,0.96) !important;
      color: #e7ebff !important;
      border-color: rgba(255,255,255,0.08) !important;
    }

    body:not(.light-mode) .card {
      box-shadow: 0 18px 45px rgba(0,0,0,0.18);
    }

    body:not(.light-mode) .card-title,
    body:not(.light-mode) .card-description,
    body:not(.light-mode) .table thead th,
    body:not(.light-mode) .table td,
    body:not(.light-mode) .table th,
    body:not(.light-mode) .badge,
    body:not(.light-mode) .nav-link,
    body:not(.light-mode) .btn,
    body:not(.light-mode) .form-control,
    body:not(.light-mode) .form-select {
      color: #eef3ff !important;
    }

    body:not(.light-mode) .table thead {
      background-color: rgba(255,255,255,0.05) !important;
    }

    body:not(.light-mode) .page-header .page-title {
      color: #f3f7ff !important;
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

    .table-action-btn.btn-sm {
      padding: 8px 14px !important;
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

  <div class="container-scroller cre8-admin-page">
    <?php
    $backActive = 'users';
    require_once __DIR__ . '/../layout/sidebar.php';
    ?>
    <div class="container-fluid page-body-wrapper cre8-admin-main">
    <?php
    require_once __DIR__ . '/../layout/header.php';
    ?>
      <div class="main-panel">
        <div class="content-wrapper">
          <div class="row">

          </div>

          <!-- ===================== ADVANCED STATISTICS ===================== -->
        <div class="row mb-4 align-items-stretch">

  <!-- Total Users -->
  <div class="col-md-3 mb-3 d-flex">
    <div class="card shadow-sm text-center p-4 h-100 w-100" style="background: linear-gradient(135deg, #9B5DE0 0%, #B771E5 100%); color: white; border-radius: 10px;">
      <i class="mdi mdi-account-multiple" style="font-size: 2rem; margin-bottom: 10px;"></i>
      <h6 class="mb-2">Total Users</h6>
      <h3 class="mb-0"><?php echo $stats['total']; ?></h3>
      <small class="mt-2 opacity-75">All accounts</small>
    </div>
  </div>

  <!-- Admin -->
  <div class="col-md-3 mb-3 d-flex">
    <div class="card shadow-sm text-center p-4 h-100 w-100" style="background: linear-gradient(135deg, #D78FEE 0%, #C96FE8 100%); color: white; border-radius: 10px;">
      <i class="mdi mdi-shield-admin" style="font-size: 2rem; margin-bottom: 10px;"></i>
      <h6 class="mb-2">Administrators</h6>
      <h3 class="mb-0"><?php echo $stats['admin']; ?></h3>
      <small class="mt-2 opacity-75">Admin accounts</small>
    </div>
  </div>

  <!-- Créateurs -->
  <div class="col-md-3 mb-3 d-flex">
    <div class="card shadow-sm text-center p-4 h-100 w-100" style="background: linear-gradient(135deg, #AEEA94 0%, #99D98E 100%); color: #2d5016; border-radius: 10px;">
      <i class="mdi mdi-account-convert" style="font-size: 2rem; margin-bottom: 10px;"></i>
      <h6 class="mb-2">Creators</h6>
      <h3 class="mb-0"><?php echo $stats['createur']; ?></h3>
      <small class="mt-2 opacity-75">Creator accounts</small>
    </div>
  </div>

  <!-- Marques -->
  <div class="col-md-3 mb-3 d-flex">
    <div class="card shadow-sm text-center p-4 h-100 w-100" style="background: linear-gradient(135deg, #E11D74 0%, #D01565 100%); color: white; border-radius: 10px;">
      <i class="mdi mdi-store" style="font-size: 2rem; margin-bottom: 10px;"></i>
      <h6 class="mb-2">Brands</h6>
      <h3 class="mb-0"><?php echo $stats['marque']; ?></h3>
      <small class="mt-2 opacity-75">Brand accounts</small>
    </div>
  </div>

</div>

<!-- ===================== STATUS STATS ===================== -->
<div class="row mb-4 align-items-stretch">

  <!-- Active Users -->
  <div class="col-md-6 mb-3 d-flex">
    <div class="card shadow-sm text-center p-4 h-100 w-100" style="background: linear-gradient(135deg, #9B5DE0 0%, #B771E5 100%); color: white; border-radius: 10px;">
      <i class="mdi mdi-account-check" style="font-size: 2.5rem; margin-bottom: 10px;"></i>
      <h6 class="mb-2">Active Accounts</h6>
      <h3 class="mb-0"><?php echo $stats['actif']; ?></h3>
      <small class="mt-2 opacity-75">✅ Active users</small>
    </div>
  </div>

  <!-- Suspended -->
  <div class="col-md-6 mb-3 d-flex">
    <div class="card shadow-sm text-center p-4 h-100 w-100" style="background: linear-gradient(135deg, #FFC107 0%, #FFB300 100%); color: #333; border-radius: 10px;">
      <i class="mdi mdi-account-lock" style="font-size: 2.5rem; margin-bottom: 10px;"></i>
      <h6 class="mb-2">Suspended Accounts</h6>
      <h3 class="mb-0"><?php echo $stats['suspendu']; ?></h3>
      <small class="mt-2 opacity-75">🔒 Suspended users</small>
    </div>
  </div>

</div>
          <!-- ===================== AREA CHARTS ===================== -->
          <div class="row mb-4">

            <!-- Area Chart - Role Distribution -->
            <div class="col-lg-12 mb-3">
              <div class="card shadow-sm">
                <div class="card-body">
                  <h5 class="card-title mb-4">📊 User Role Trends (Timeline)</h5>
                  <div style="height: 350px;">
                    <canvas id="chartAreaRole"></canvas>
                  </div>
                </div>
              </div>
            </div>

          </div>

          <!-- ===================== ADDITIONAL CHART ===================== -->
          <div class="row mb-4">

            <!-- Area Chart - User Status -->
            <div class="col-lg-12 mb-3">
              <div class="card shadow-sm">
                <div class="card-body">
                  <h5 class="card-title mb-4">👥 User Status Trends (Timeline)</h5>
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
                  <h4 class="card-title">User Management</h4>

                  <!-- Search and Filter -->
                  <div class="row mb-3">
                    <div class="col-md-6">
                      <form method="GET" action="index.php" class="d-flex gap-2">
                        <input type="text" name="search" class="form-control"
                          placeholder="Search by name or email..."
                          value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        <button type="submit" class="btn btn-sm"
                          style="background-color: #9B5DE0; color: white; width: 90px;">Search</button>
                      </form>
                    </div>
                    <div class="col-md-6">
                      <form method="GET" action="index.php" class="d-flex gap-2">
                        <select name="role" class="form-select form-select-sm" onchange="this.form.submit()"
                          style="background-color: #FDCFFA; border-color: #D78FEE;">
                          <option value="">Filter by role</option>
                          <option value="admin" <?php echo isset($_GET['role']) && $_GET['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                          <option value="createur" <?php echo isset($_GET['role']) && $_GET['role'] == 'createur' ? 'selected' : ''; ?>>Creator</option>
                          <option value="marque" <?php echo isset($_GET['role']) && $_GET['role'] == 'marque' ? 'selected' : ''; ?>>Brand</option>
                        </select>
                      </form>
                    </div>
                  </div>

                  <div class="table-responsive">
                    <table class="table table-hover align-middle">
                      <thead class="table-dark">
                        <tr>
                          <th>ID</th>
                          <th>Name</th>
                          <th>Email</th>
                          <th>Role</th>
                          <th>Status</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody>

                        <?php foreach ($users as $u): ?>
                          <?php
                            $rowRole = cc_normalize_role($u['role'] ?? '');
                            $rowStatus = cc_normalize_status($u['statut'] ?? '');
                            $rowUser = [
                                'id' => (int)($u['id'] ?? 0),
                                'role' => $rowRole,
                                'statut' => $rowStatus,
                                'suspended_by' => $u['suspended_by'] ?? null,
                                'suspended_by_role' => cc_normalize_role($u['suspended_by_role'] ?? ''),
                                'suspended_at' => $u['suspended_at'] ?? null,
                                'suspension_reason' => $u['suspension_reason'] ?? null,
                            ];
                            $u['role'] = $rowRole;
                            $u['statut'] = $rowStatus;
                            $canSuspendUser = $rowStatus === 'actif' && cc_can_manage_user($actorId, $actorRole, $rowUser, 'suspend');
                            $canReactivateUser = $rowStatus === 'suspendu' && cc_can_reactivate_suspension($actorId, $actorRole, $rowUser);
                            $canToggleStatus = $canSuspendUser || $canReactivateUser;
                            $canDeleteUser = cc_can_manage_user($actorId, $actorRole, $rowUser, 'delete');
                            $canEditRole = ($actorRole !== 'admin')
                                && cc_can_manage_user($actorId, $actorRole, $rowUser, 'edit_role');
                            $canEditUser = $canEditRole;
                          ?>
                          <tr>
                            <form method="POST" action="update.php" class="w-100">
                              <td><?= $u['id'] ?></td>

                              <td>
                                <input type="text" name="nom" value="<?= htmlspecialchars($u['nom']) ?>"
                                  class="form-control form-control-sm" <?= $canEditUser ? '' : 'readonly disabled' ?>>
                              </td>

                              <td>
                                <input type="email" name="email" value="<?= htmlspecialchars($u['email']) ?>"
                                  class="form-control form-control-sm" <?= $canEditUser ? '' : 'readonly disabled' ?>>
                              </td>

                              <td>
                                <select name="role" class="form-select form-select-sm"
                                  style="background-color: #FDCFFA; border-color: #D78FEE;" <?= $canEditRole ? '' : 'disabled' ?>>
                                  <?php
                                    $roleOptions = $canEditRole ? array_values(array_unique(array_merge([$rowRole], $assignableRoles))) : [$rowRole];
                                    foreach ($roleOptions as $roleOption):
                                      if ($roleOption === 'hyper_admin' && $rowRole !== 'hyper_admin') {
                                          continue;
                                      }
                                  ?>
                                    <option value="<?= htmlspecialchars($roleOption) ?>" <?= $rowRole == $roleOption ? 'selected' : '' ?>><?= htmlspecialchars($roleLabels[$roleOption] ?? ucfirst(str_replace('_', ' ', $roleOption))) ?></option>
                                  <?php endforeach; ?>
                                </select>
                              </td>

                             <td>
<?php
$statut = $rowStatus;

if ($statut == '') {
    $statut = 'inactif';
}

$statusLabels = [
    'actif' => 'Active',
    'suspendu' => 'Suspended',
    'bloque' => 'Blocked',
    'en_attente' => 'Pending',
    'inactif' => 'Inactive'
];

$color = ($statut == 'actif') ? '#9B5DE0' :
         (($statut == 'suspendu') ? '#dc3545' :
         (($statut == 'en_attente') ? '#ffc107' : '#6c757d'));

$displayStatus = $statusLabels[$statut] ?? ucfirst(str_replace('_', ' ', $statut));
?>

<span class="badge" style="background-color: <?= $color ?>; color:white;" title="<?= htmlspecialchars($u['suspension_reason'] ?? '') ?>">
    <?= $displayStatus ?>
</span>
</td>

                              <td>
                                <div class="d-flex gap-2 flex-wrap">
                                  <input type="hidden" name="id" value="<?= $u['id'] ?>">

                                  <?php if ($canEditUser): ?>
                                  <button type="submit" class="btn table-action-btn text-white"
                                    style="background-color: #9B5DE0;">
                                    Edit
                                  </button>
                                  <?php endif; ?>

                                  <?php if ($canToggleStatus): ?>
                                  <button type="button" class="btn table-action-btn text-white"
                                    style="background-color: <?= $rowStatus === 'actif' ? '#E11D74' : '#28a745' ?>; border: none;"
                                    onclick="toggleUserStatus(<?= $u['id'] ?>, '<?= htmlspecialchars($rowStatus) ?>');">
                                    <?= ($u['statut'] ?? 'actif') == 'actif' ? '🔒 Suspend' : '✅ Activate' ?>
                                  </button>
                                  <?php endif; ?>

                                  <?php if ($canDeleteUser): ?>
                                  <button type="button" class="btn table-action-btn text-white"
                                    onclick="if(confirm('Are you sure?')) window.location.href='delete.php?id=<?= $u['id'] ?>';"
                                    style="background-color: #D78FEE;">
                                    Delete
                                  </button>
                                  <?php endif; ?>
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
                    <nav aria-label="Users pagination">
                      <ul class="pagination">
                        <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                          <a class="page-link" href="?search=<?= urlencode($search) ?>&role=<?= urlencode($role) ?>&page=<?= $currentPage - 1 ?>" aria-label="Previous">&laquo;</a>
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
                          <a class="page-link" href="?search=<?= urlencode($search) ?>&role=<?= urlencode($role) ?>&page=<?= $currentPage + 1 ?>" aria-label="Next">&raquo;</a>
                        </li>
                      </ul>
                    </nav>
                  </div>
                  <div class="text-center text-muted">Page <?= $currentPage ?> of <?= $totalPages ?> (<?= $totalUsers ?> users)</div>
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
  <script src="../layout/back-layout.js?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../layout/back-layout.js')); ?>"></script>
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
    // Consistent color palette
    const colors = {
      admin: '#9B5DE0',
      createur: '#AEEA94',
      marque: '#E11D74',
      actif: '#9B5DE0',
      inactif: '#D78FEE',
      suspendu: '#FFC107'
    };

    const userStats = {
      admin: <?= intval($stats['admin']) ?>,
      createur: <?= intval($stats['createur']) ?>,
      marque: <?= intval($stats['marque']) ?>,
      actif: <?= intval($stats['actif']) ?>,
      inactif: <?= intval($stats['inactif']) ?>,
      suspendu: <?= intval($stats['suspendu'] ?? 0) ?>
    };

    const roleLabels = ['Administrators', 'Creators', 'Brands'];
    const statusLabels = ['Active', 'Suspended'];

    const ctxAreaRole = document.getElementById('chartAreaRole');
    new Chart(ctxAreaRole, {
      type: 'line',
      data: {
        labels: roleLabels,
        datasets: [
          {
            label: 'Administrators',
            data: [userStats.admin, 0, 0],
            borderColor: colors.admin,
            backgroundColor: colors.admin + '33',
            fill: true,
            tension: 0.4,
            pointRadius: 6,
            pointBackgroundColor: colors.admin,
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            borderWidth: 3
          },
          {
            label: 'Creators',
            data: [0, userStats.createur, 0],
            borderColor: colors.createur,
            backgroundColor: colors.createur + '33',
            fill: true,
            tension: 0.4,
            pointRadius: 6,
            pointBackgroundColor: colors.createur,
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            borderWidth: 3
          },
          {
            label: 'Brands',
            data: [0, 0, userStats.marque],
            borderColor: colors.marque,
            backgroundColor: colors.marque + '33',
            fill: true,
            tension: 0.4,
            pointRadius: 6,
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
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function(context) {
                const value = context.parsed.y;
                const total = userStats.admin + userStats.createur + userStats.marque;
                const percent = total ? Math.round(value / total * 100) : 0;
                return value + ' users (' + percent + '%)';
              }
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              stepSize: Math.max(1, Math.ceil(Math.max(userStats.admin, userStats.createur, userStats.marque) / 5))
            },
            grid: { color: 'rgba(0,0,0,0.05)' }
          },
          x: { grid: { display: false } }
        }
      }
    });

    const ctxAreaStatus = document.getElementById('chartAreaStatus');
    new Chart(ctxAreaStatus, {
      type: 'line',
      data: {
        labels: statusLabels,
        datasets: [
          {
            label: 'Active',
            data: [userStats.actif, 0],
            borderColor: colors.actif,
            backgroundColor: colors.actif + '33',
            fill: true,
            tension: 0.4,
            pointRadius: 6,
            pointBackgroundColor: colors.actif,
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            borderWidth: 3
          },
          {
            label: 'Suspended',
            data: [0, userStats.suspendu],
            borderColor: colors.suspendu,
            backgroundColor: colors.suspendu + '33',
            fill: true,
            tension: 0.4,
            pointRadius: 6,
            pointBackgroundColor: colors.suspendu,
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
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function(context) {
                const value = context.parsed.y;
                const total = userStats.actif + userStats.inactif;
                const percent = total ? Math.round(value / total * 100) : 0;
                return value + ' users (' + percent + '%)';
              }
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              stepSize: Math.max(1, Math.ceil(Math.max(userStats.actif, userStats.inactif) / 5))
            },
            grid: { color: 'rgba(0,0,0,0.05)' }
          },
          x: { grid: { display: false } }
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
    // Function to toggle user status
    function toggleUserStatus(userId, currentStatus) {
      const newStatus = currentStatus === 'actif' ? 'suspendu' : 'actif';
      const confirmMsg = currentStatus === 'actif' 
        ? 'Are you sure you want to suspend this user?' 
        : 'Are you sure you want to activate this user?';
      
      if (confirm(confirmMsg)) {
        fetch(`toggleStatus.php?id=${userId}&newStatus=${newStatus}`)
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              alert('✅ Status updated successfully');
              location.reload();
            } else {
              alert('❌ Error: ' + (data.message || 'Unable to update status'));
            }
          })
          .catch(err => alert('❌ Network error: ' + err));
      }
    }
  </script>
</body>

</html>
