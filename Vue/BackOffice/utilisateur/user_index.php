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
$roleI18nKeys = [
    'createur' => 'users.role.creator',
    'marque' => 'users.role.brand',
    'admin' => 'users.role.admin',
    'super_admin' => 'users.role.superAdmin',
    'hyper_admin' => 'users.role.hyperAdmin',
];
$statusI18nKeys = [
    'actif' => 'users.status.active',
    'suspendu' => 'users.status.suspended',
    'bloque' => 'users.status.blocked',
    'en_attente' => 'users.status.pending',
    'inactif' => 'users.status.inactive',
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
  <link rel="stylesheet" href="user-center-admin.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/user-center-admin.css')); ?>">
  <link rel="stylesheet" href="../unified-table-admin.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../unified-table-admin.css')); ?>">
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
        <div class="content-wrapper user-center-shell">
          <section class="uc-page-head">
            <div>
              <p class="uc-kicker" data-i18n="userCenter.kicker">User Center</p>
              <h1 data-i18n="users.title">User administration</h1>
              <p data-i18n="users.subtitle">Manage accounts, roles, statuses, and security actions.</p>
            </div>
          </section>

          <nav class="uc-entity-tabs" aria-label="User Center sections">
            <a href="index.php" class="uc-entity-tab is-active">
              <span class="uc-tab-icon"><i class="mdi mdi-account-group"></i></span>
              <span><strong data-i18n="userCenter.tabs.users">Users</strong><small data-i18n="userCenter.tabs.usersHint">Accounts and roles</small></span>
            </a>
            <a href="reclamations.php" class="uc-entity-tab">
              <span class="uc-tab-icon"><i class="mdi mdi-alert-decagram"></i></span>
              <span><strong data-i18n="userCenter.tabs.complaints">Complaints</strong><small data-i18n="userCenter.tabs.complaintsHint">Reports and appeals</small></span>
            </a>
          </nav>

          <section class="uc-statistics-panel" data-uc-stats>
            <div class="uc-section-head">
              <div>
                <h2 data-i18n="userCenter.statistics.title">Workspace statistics</h2>
                <p data-i18n="users.statistics.subtitle">Live indicators and charts for user activity.</p>
              </div>
              <button type="button" class="uc-secondary-btn" data-uc-stats-toggle data-i18n="common.hideStatistics">Hide statistics</button>
            </div>

            <div class="uc-kpi-grid">
              <article class="uc-kpi-card uc-kpi-purple">
                <span data-i18n="users.kpi.total">Total users</span>
                <strong><?php echo intval($stats['total'] ?? $totalUsers); ?></strong>
                <small data-i18n="users.kpi.totalHint">All platform accounts</small>
              </article>
              <article class="uc-kpi-card uc-kpi-pink">
                <span data-i18n="users.kpi.admins">Admin accounts</span>
                <strong><?php echo intval($stats['admin'] ?? 0); ?></strong>
                <small data-i18n="users.kpi.adminsHint">Back-office accounts</small>
              </article>
              <article class="uc-kpi-card uc-kpi-green">
                <span data-i18n="users.kpi.creators">Creator accounts</span>
                <strong><?php echo intval($stats['createur'] ?? 0); ?></strong>
                <small data-i18n="users.kpi.creatorsHint">Creator accounts</small>
              </article>
              <article class="uc-kpi-card uc-kpi-magenta">
                <span data-i18n="users.kpi.brands">Brand accounts</span>
                <strong><?php echo intval($stats['marque'] ?? 0); ?></strong>
                <small data-i18n="users.kpi.brandsHint">Brand accounts</small>
              </article>
              <article class="uc-kpi-card uc-kpi-yellow">
                <span data-i18n="users.kpi.suspended">Suspended users</span>
                <strong><?php echo intval($stats['suspendu'] ?? 0); ?></strong>
                <small data-i18n="users.kpi.suspendedHint">Accounts under restriction</small>
              </article>
            </div>

            <div class="uc-stats-body">
              <article class="uc-chart-card">
                <div class="uc-chart-head">
                  <h3 data-i18n="users.charts.rolesTitle">User role trends</h3>
                  <p data-i18n="users.charts.rolesSubtitle">Distribution across administrators, creators, and brands.</p>
                </div>
                <div class="uc-chart-canvas">
                  <canvas id="chartAreaRole"></canvas>
                </div>
              </article>
              <article class="uc-chart-card">
                <div class="uc-chart-head">
                  <h3 data-i18n="users.charts.statusTitle">User status trends</h3>
                  <p data-i18n="users.charts.statusSubtitle">Active and suspended account balance.</p>
                </div>
                <div class="uc-chart-canvas">
                  <canvas id="chartAreaStatus"></canvas>
                </div>
              </article>
            </div>
          </section>

          <section class="uc-filter-card">
            <div class="uc-filter-head">
              <div>
                <h2 data-i18n="users.filters.title">User filters</h2>
                <p data-i18n="users.filters.subtitle">Search by user identity or isolate accounts by role.</p>
              </div>
            </div>
            <form method="GET" action="index.php" class="uc-filter-grid">
              <label class="uc-filter-field uc-filter-search">
                <span data-i18n="common.search">Search</span>
                <input type="text" name="search" placeholder="Search by name or email..." data-i18n-placeholder="users.filters.searchPlaceholder" value="<?php echo htmlspecialchars($search); ?>">
              </label>
              <label class="uc-filter-field">
                <span data-i18n="common.role">Role</span>
                <select name="role">
                  <option value="" data-i18n-opt="users.filters.allRoles">All roles</option>
                  <option value="admin" data-i18n-opt="users.role.admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Admin</option>
                  <option value="super_admin" data-i18n-opt="users.role.superAdmin" <?php echo $role === 'super_admin' ? 'selected' : ''; ?>>Super Admin</option>
                  <option value="createur" data-i18n-opt="users.role.creator" <?php echo $role === 'createur' ? 'selected' : ''; ?>>Creator</option>
                  <option value="marque" data-i18n-opt="users.role.brand" <?php echo $role === 'marque' ? 'selected' : ''; ?>>Brand</option>
                </select>
              </label>
              <div class="uc-filter-actions">
                <button type="submit" class="uc-primary-btn"><span data-i18n="common.applyFilters">Apply filters</span></button>
                <a href="index.php" class="uc-soft-btn"><span data-i18n="common.reset">Reset</span></a>
              </div>
            </form>
          </section>

          <section class="uc-table-card">
            <div class="uc-table-head">
              <div>
                <h2 data-i18n="users.table.title">User list</h2>
                <p><?php echo intval($totalUsers); ?> <span data-i18n="users.table.accountsFound">accounts found</span></p>
              </div>
            </div>

            <div id="ucResultsRegion" class="uc-results-region" aria-live="polite">
              <div class="uc-table-wrap">
                <table class="uc-table uc-users-table">
                  <thead>
                    <tr>
                      <th class="uc-col-id">ID</th>
                      <th data-i18n="users.table.user">User</th>
                      <th data-i18n="users.table.email">Email</th>
                      <th data-i18n="common.role">Role</th>
                      <th data-i18n="common.status">Status</th>
                      <th data-i18n="common.actions">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (empty($users)): ?>
                      <tr>
                        <td colspan="6">
                          <div class="uc-empty-state">
                            <span><i class="mdi mdi-account-search"></i></span>
                            <strong data-i18n="users.empty.title">No users found</strong>
                            <small data-i18n="users.empty.subtitle">Try changing the search or role filter.</small>
                          </div>
                        </td>
                      </tr>
                    <?php endif; ?>

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
                        $canEditRole = $actorRole === 'hyper_admin'
                            && (int)$actorId !== (int)($rowUser['id'] ?? 0)
                            && in_array($rowRole, ['admin', 'super_admin'], true)
                            && cc_can_manage_user($actorId, $actorRole, $rowUser, 'edit_role');
                        $canEditUser = $canEditRole;
                        $statusLabels = [
                            'actif' => 'Active',
                            'suspendu' => 'Suspended',
                            'bloque' => 'Blocked',
                            'en_attente' => 'Pending',
                            'inactif' => 'Inactive'
                        ];
                        $displayStatus = $statusLabels[$rowStatus] ?? ucfirst(str_replace('_', ' ', $rowStatus !== '' ? $rowStatus : 'inactif'));
                        $initial = strtoupper(substr(trim((string)($u['nom'] ?? 'U')) !== '' ? trim((string)$u['nom']) : 'U', 0, 1));
                        $updateFormId = 'ucUpdateUser' . (int)($u['id'] ?? 0);
                      ?>
                      <tr>
                        <td class="uc-col-id">#<?= (int)$u['id'] ?></td>
                        <td>
                          <div class="uc-user-cell">
                            <span class="uc-user-avatar"><?= htmlspecialchars($initial) ?></span>
                            <input form="<?= $updateFormId ?>" type="text" name="nom" value="<?= htmlspecialchars($u['nom'] ?? '') ?>" class="uc-inline-input" <?= $canEditUser ? '' : 'readonly disabled' ?>>
                          </div>
                        </td>
                        <td>
                          <input form="<?= $updateFormId ?>" type="email" name="email" value="<?= htmlspecialchars($u['email'] ?? '') ?>" class="uc-inline-input" <?= $canEditUser ? '' : 'readonly disabled' ?>>
                        </td>
                        <td>
                          <select form="<?= $updateFormId ?>" name="role" class="uc-inline-select" <?= $canEditRole ? '' : 'disabled' ?>>
                            <?php
                              if ($canEditRole && $rowRole === 'admin') {
                                  $roleOptions = ['admin', 'super_admin'];
                              } elseif ($canEditRole && $rowRole === 'super_admin') {
                                  $roleOptions = ['super_admin', 'admin'];
                              } else {
                                  $roleOptions = [$rowRole];
                              }
                              foreach ($roleOptions as $roleOption):
                            ?>
                              <option value="<?= htmlspecialchars($roleOption) ?>" data-i18n-opt="<?= htmlspecialchars($roleI18nKeys[$roleOption] ?? '') ?>" <?= $rowRole == $roleOption ? 'selected' : '' ?>><?= htmlspecialchars($roleLabels[$roleOption] ?? ucfirst(str_replace('_', ' ', $roleOption))) ?></option>
                            <?php endforeach; ?>
                          </select>
                        </td>
                        <td>
                          <span class="uc-badge uc-status-<?= htmlspecialchars($rowStatus !== '' ? $rowStatus : 'inactif') ?>" data-i18n="<?= htmlspecialchars($statusI18nKeys[$rowStatus] ?? '') ?>" title="<?= htmlspecialchars($u['suspension_reason'] ?? '') ?>">
                            <?= htmlspecialchars($displayStatus) ?>
                          </span>
                        </td>
                        <td>
                          <div class="uc-actions">
                            <form id="<?= $updateFormId ?>" method="POST" action="update.php" class="m-0">
                              <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                              <?php if ($canEditUser): ?>
                                <button type="submit" class="uc-action-btn uc-action-primary"><span data-i18n="common.save">Save</span></button>
                              <?php endif; ?>
                            </form>

                            <?php if ($canToggleStatus): ?>
                              <button type="button" class="uc-action-btn <?= $rowStatus === 'actif' ? 'uc-action-danger' : 'uc-action-success' ?>" onclick="toggleUserStatus(<?= (int)$u['id'] ?>, '<?= htmlspecialchars($rowStatus) ?>');">
                                <span data-i18n="<?= $rowStatus === 'actif' ? 'common.suspend' : 'common.activate' ?>"><?= $rowStatus === 'actif' ? 'Suspend' : 'Activate' ?></span>
                              </button>
                            <?php endif; ?>

                            <?php if ($canDeleteUser): ?>
                              <button type="button" class="uc-action-btn uc-action-soft-danger" onclick="if(confirm('Are you sure?')) window.location.href='delete.php?id=<?= (int)$u['id'] ?>';">
                                <span data-i18n="common.delete">Delete</span>
                              </button>
                            <?php endif; ?>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>

              <div class="uc-pagination">
                <p><span data-i18n="common.page">Page</span> <?= $currentPage ?> <span data-i18n="common.of">of</span> <?= $totalPages ?> · <?= $totalUsers ?> <span data-i18n="userCenter.pagination.users">users</span></p>
                <?php if ($totalPages > 1): ?>
                  <nav aria-label="Users pagination">
                    <a class="uc-page-btn <?= $currentPage <= 1 ? 'is-disabled' : '' ?>" href="?search=<?= urlencode($search) ?>&role=<?= urlencode($role) ?>&page=<?= max(1, $currentPage - 1) ?>">&laquo;</a>
                    <?php
                      $startPage = max(1, $currentPage - 2);
                      $endPage = min($totalPages, $currentPage + 2);
                      if ($startPage > 1):
                    ?>
                      <a class="uc-page-btn" href="?search=<?= urlencode($search) ?>&role=<?= urlencode($role) ?>&page=1">1</a>
                      <?php if ($startPage > 2): ?><span class="uc-page-ellipsis">...</span><?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                      <a class="uc-page-btn <?= $i == $currentPage ? 'is-active' : '' ?>" href="?search=<?= urlencode($search) ?>&role=<?= urlencode($role) ?>&page=<?= $i ?>"><?= $i ?></a>
                    <?php endfor; ?>

                    <?php if ($endPage < $totalPages): ?>
                      <?php if ($endPage < $totalPages - 1): ?><span class="uc-page-ellipsis">...</span><?php endif; ?>
                      <a class="uc-page-btn" href="?search=<?= urlencode($search) ?>&role=<?= urlencode($role) ?>&page=<?= $totalPages ?>"><?= $totalPages ?></a>
                    <?php endif; ?>
                    <a class="uc-page-btn <?= $currentPage >= $totalPages ? 'is-disabled' : '' ?>" href="?search=<?= urlencode($search) ?>&role=<?= urlencode($role) ?>&page=<?= min($totalPages, $currentPage + 1) ?>">&raquo;</a>
                  </nav>
                <?php endif; ?>
              </div>
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
  <script src="../layout/back-layout.js?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../layout/back-layout.js')); ?>"></script>
  <script src="user-center-admin.js?v=<?php echo urlencode((string) filemtime(__DIR__ . '/user-center-admin.js')); ?>"></script>
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
    window.cre8BackRegisterTranslations && window.cre8BackRegisterTranslations({
      en: {
        'userCenter.kicker': 'User Center',
        'userCenter.tabs.users': 'Users',
        'userCenter.tabs.usersHint': 'Accounts and roles',
        'userCenter.tabs.complaints': 'Complaints',
        'userCenter.tabs.complaintsHint': 'Reports and appeals',
        'userCenter.statistics.title': 'Workspace statistics',
        'userCenter.pagination.users': 'users',
        'users.title': 'User administration',
        'users.subtitle': 'Manage accounts, roles, statuses, and security actions.',
        'users.statistics.subtitle': 'Live indicators and charts for user activity.',
        'users.kpi.total': 'Total users',
        'users.kpi.totalHint': 'All platform accounts',
        'users.kpi.admins': 'Admin accounts',
        'users.kpi.adminsHint': 'Back-office accounts',
        'users.kpi.creators': 'Creator accounts',
        'users.kpi.creatorsHint': 'Creator accounts',
        'users.kpi.brands': 'Brand accounts',
        'users.kpi.brandsHint': 'Brand accounts',
        'users.kpi.suspended': 'Suspended users',
        'users.kpi.suspendedHint': 'Accounts under restriction',
        'users.charts.rolesTitle': 'User role trends',
        'users.charts.rolesSubtitle': 'Distribution across administrators, creators, and brands.',
        'users.charts.statusTitle': 'User status trends',
        'users.charts.statusSubtitle': 'Active and suspended account balance.',
        'users.filters.title': 'User filters',
        'users.filters.subtitle': 'Search by user identity or isolate accounts by role.',
        'users.filters.searchPlaceholder': 'Search by name or email...',
        'users.filters.allRoles': 'All roles',
        'users.table.title': 'User list',
        'users.table.accountsFound': 'accounts found',
        'users.table.user': 'User',
        'users.table.email': 'Email',
        'users.empty.title': 'No users found',
        'users.empty.subtitle': 'Try changing the search or role filter.',
        'users.role.admin': 'Admin',
        'users.role.superAdmin': 'Super Admin',
        'users.role.hyperAdmin': 'Hyper Admin',
        'users.role.creator': 'Creator',
        'users.role.brand': 'Brand',
        'users.status.active': 'Active',
        'users.status.suspended': 'Suspended',
        'users.status.blocked': 'Blocked',
        'users.status.pending': 'Pending',
        'users.status.inactive': 'Inactive'
      },
      fr: {
        'userCenter.kicker': 'Centre utilisateurs',
        'userCenter.tabs.users': 'Utilisateurs',
        'userCenter.tabs.usersHint': 'Comptes et roles',
        'userCenter.tabs.complaints': 'Reclamations',
        'userCenter.tabs.complaintsHint': 'Signalements et appels',
        'userCenter.statistics.title': 'Statistiques du workspace',
        'userCenter.pagination.users': 'utilisateurs',
        'users.title': 'Administration des utilisateurs',
        'users.subtitle': 'Gerer les comptes, roles, statuts et actions de securite.',
        'users.statistics.subtitle': 'Indicateurs et graphiques en direct pour l activite utilisateur.',
        'users.kpi.total': 'Total utilisateurs',
        'users.kpi.totalHint': 'Tous les comptes de la plateforme',
        'users.kpi.admins': 'Comptes admin',
        'users.kpi.adminsHint': 'Comptes BackOffice',
        'users.kpi.creators': 'Comptes createur',
        'users.kpi.creatorsHint': 'Comptes createur',
        'users.kpi.brands': 'Comptes marque',
        'users.kpi.brandsHint': 'Comptes marque',
        'users.kpi.suspended': 'Utilisateurs suspendus',
        'users.kpi.suspendedHint': 'Comptes sous restriction',
        'users.charts.rolesTitle': 'Tendances des roles',
        'users.charts.rolesSubtitle': 'Repartition entre admins, createurs et marques.',
        'users.charts.statusTitle': 'Tendances des statuts',
        'users.charts.statusSubtitle': 'Equilibre entre comptes actifs et suspendus.',
        'users.filters.title': 'Filtres utilisateurs',
        'users.filters.subtitle': 'Rechercher par identite ou isoler les comptes par role.',
        'users.filters.searchPlaceholder': 'Rechercher par nom ou email...',
        'users.filters.allRoles': 'Tous les roles',
        'users.table.title': 'Liste des utilisateurs',
        'users.table.accountsFound': 'comptes trouves',
        'users.table.user': 'Utilisateur',
        'users.table.email': 'Email',
        'users.empty.title': 'Aucun utilisateur trouve',
        'users.empty.subtitle': 'Essayez de modifier la recherche ou le filtre de role.',
        'users.role.admin': 'Admin',
        'users.role.superAdmin': 'Super admin',
        'users.role.hyperAdmin': 'Hyper admin',
        'users.role.creator': 'Createur',
        'users.role.brand': 'Marque',
        'users.status.active': 'Actif',
        'users.status.suspended': 'Suspendu',
        'users.status.blocked': 'Bloque',
        'users.status.pending': 'En attente',
        'users.status.inactive': 'Inactif'
      }
    });
  </script>

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
