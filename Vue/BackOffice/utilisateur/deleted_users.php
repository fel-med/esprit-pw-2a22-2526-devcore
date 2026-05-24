<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../layout/early-theme.php';
require_once __DIR__ . '/../../../Controleur/session_helper.php';
require_once __DIR__ . '/../../../Controleur/utilisateurC.php';

$currentUserId = cc_require_hyper_admin('../../FrontOffice/utilisateur/login.php');
$currentRole = cc_current_user_role();
$userC = new UtilisateurC();
$backActive = 'admin_management';

function deleted_users_redirect(): void
{
    header('Location: deleted_users.php');
    exit;
}

function deleted_users_flash(string $type, string $message): void
{
    $_SESSION['deleted_users_flash'] = [
        'type' => $type === 'success' ? 'success' : 'danger',
        'message' => $message,
    ];
}

function deleted_users_h($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function deleted_users_initial($name): string
{
    $name = trim((string)($name ?? ''));
    if ($name === '') {
        return '?';
    }

    return strtoupper(substr($name, 0, 1));
}

function deleted_users_status_class($status): string
{
    $status = strtolower(trim((string)($status ?? '')));
    return match ($status) {
        'actif' => 'uc-status-actif',
        'suspendu', 'bloque' => 'uc-status-bloque',
        'en_attente' => 'uc-status-en_attente',
        default => 'uc-status-inactif',
    };
}

function deleted_users_date_parts($date): array
{
    if (empty($date)) {
        return ['-', ''];
    }

    try {
        $dt = new DateTimeImmutable((string)$date);
        return [$dt->format('Y-m-d'), $dt->format('H:i')];
    } catch (Throwable $e) {
        return [(string)$date, ''];
    }
}

function deleted_users_final_delete_ready($deletedAt): bool
{
    if (empty($deletedAt)) {
        return false;
    }

    try {
        $deleted = new DateTimeImmutable((string)$deletedAt);
        return $deleted <= new DateTimeImmutable('-7 days');
    } catch (Throwable $e) {
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    $targetId = (int)($_POST['id'] ?? 0);

    if ($targetId <= 0) {
        deleted_users_flash('danger', 'Invalid user id.');
        deleted_users_redirect();
    }

    if ($action === 'restore') {
        $result = $userC->restoreDeletedUserById($targetId, $currentUserId, $currentRole, 'Restored from Deleted users');
        deleted_users_flash(
            !empty($result['success']) ? 'success' : 'danger',
            (string)($result['message'] ?? (!empty($result['success']) ? 'Account restored successfully.' : 'Unable to restore this account.'))
        );
        deleted_users_redirect();
    }

    if ($action === 'final_delete') {
        $result = $userC->finalDeleteUserById($targetId, $currentUserId, $currentRole);
        deleted_users_flash(
            !empty($result['success']) ? 'success' : 'danger',
            (string)($result['message'] ?? (!empty($result['success']) ? 'Account permanently deleted.' : 'Unable to permanently delete this account.'))
        );
        deleted_users_redirect();
    }

    deleted_users_flash('danger', 'Invalid action.');
    deleted_users_redirect();
}

$softDeleteReady = $userC->userSoftDeleteColumnsReady();
$deletedUsers = $softDeleteReady ? $userC->getDeletedUsers() : [];
$flash = $_SESSION['deleted_users_flash'] ?? null;
unset($_SESSION['deleted_users_flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php cre8_bo_early_theme_print_head_script(); ?>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Deleted users - Cre8Connect</title>
  <link rel="stylesheet" href="../assets/vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="../assets/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../layout/back-layout.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../layout/back-layout.css')); ?>">
  <link rel="stylesheet" href="user-center-admin.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/user-center-admin.css')); ?>">
  <link rel="stylesheet" href="../unified-table-admin.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../unified-table-admin.css')); ?>">
  <link rel="icon" type="image/png" sizes="16x16" href="../../public/images/favicon-16.png">
  <link rel="icon" type="image/png" sizes="32x32" href="../../public/images/favicon-32.png">
  <style>
    .deleted-users-card {
      border: 1px solid var(--uc-border);
      border-radius: 0.85rem;
      background: var(--uc-card);
      color: var(--uc-text);
      box-shadow: 0 14px 34px var(--uc-shadow);
      overflow: hidden;
    }

    .admin-management-shell .uc-entity-tabs {
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    }

    .deleted-users-card .card-title {
      color: var(--uc-text);
    }

    .deleted-users-muted {
      color: var(--uc-muted);
    }

    .deleted-users-actions {
      display: flex;
      flex-direction: column;
      align-items: stretch;
      gap: .45rem;
    }

    .deleted-users-actions .uc-action-btn {
      width: 100%;
      min-height: 2.2rem;
    }

    .deleted-users-table {
      width: 100%;
      margin: 0;
      border-collapse: separate;
      border-spacing: 0;
      color: var(--uc-text);
      table-layout: fixed;
    }

    .deleted-users-table thead th {
      padding: .9rem .95rem;
      border: 0;
      border-bottom: 1px solid var(--uc-border);
      background: var(--uc-table-head);
      color: var(--uc-text);
      font-size: .72rem;
      font-weight: 900;
      letter-spacing: .04em;
      text-transform: uppercase;
      white-space: nowrap;
    }

    .deleted-users-table tbody td {
      padding: 1rem .95rem;
      border: 0;
      border-bottom: 1px solid var(--uc-border);
      color: var(--uc-text);
      vertical-align: middle;
    }

    .deleted-users-table tbody tr {
      transition: background .16s ease;
    }

    .deleted-users-table tbody tr:hover {
      background: rgba(148, 163, 184, .08);
    }

    .deleted-users-table tbody tr:last-child td {
      border-bottom: 0;
    }

    .deleted-users-table th:nth-child(1),
    .deleted-users-table td:nth-child(1) { width: 5%; }
    .deleted-users-table th:nth-child(2),
    .deleted-users-table td:nth-child(2) { width: 20%; }
    .deleted-users-table th:nth-child(3),
    .deleted-users-table td:nth-child(3) { width: 10%; }
    .deleted-users-table th:nth-child(4),
    .deleted-users-table td:nth-child(4) { width: 10%; }
    .deleted-users-table th:nth-child(5),
    .deleted-users-table td:nth-child(5) { width: 10%; }
    .deleted-users-table th:nth-child(6),
    .deleted-users-table td:nth-child(6) { width: 11%; }
    .deleted-users-table th:nth-child(7),
    .deleted-users-table td:nth-child(7) { width: 14%; }
    .deleted-users-table th:nth-child(8),
    .deleted-users-table td:nth-child(8) { width: 8%; }
    .deleted-users-table th:nth-child(9),
    .deleted-users-table td:nth-child(9) { width: 12%; }
    
    .deleted-users-table-wrap {
      border: 1px solid var(--uc-border);
      border-radius: .75rem;
      overflow: hidden;
      background: var(--uc-card);
    }

    .deleted-users-card-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      padding: 1.15rem 1.25rem;
      border-bottom: 1px solid var(--uc-border);
    }

    .deleted-users-count {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-height: 2rem;
      border-radius: 999px;
      padding: .35rem .7rem;
      border: 1px solid rgba(220, 38, 38, .3);
      background: rgba(220, 38, 38, .12);
      color: #fecaca;
      font-size: .78rem;
      font-weight: 900;
      white-space: nowrap;
    }

    .deleted-users-body {
      padding: 1.15rem;
    }

    .deleted-user-cell {
      display: flex;
      align-items: center;
      gap: .75rem;
      min-width: 0;
    }

    .deleted-user-avatar {
      flex: 0 0 2.45rem;
      width: 2.45rem;
      height: 2.45rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 999px;
      color: #fff;
      background: linear-gradient(135deg, var(--uc-primary), var(--uc-pink));
      font-weight: 900;
      font-size: .95rem;
      box-shadow: 0 8px 18px rgba(0, 0, 0, .18);
    }

    .deleted-user-meta {
      min-width: 0;
    }

    .deleted-user-meta strong,
    .deleted-date-block strong,
    .deleted-by-block strong {
      display: block;
      color: var(--uc-text);
      font-weight: 900;
      line-height: 1.25;
      overflow-wrap: anywhere;
    }

    .deleted-user-meta span,
    .deleted-date-block span,
    .deleted-by-block span {
      display: block;
      margin-top: .16rem;
      color: var(--uc-muted);
      font-size: .8rem;
      line-height: 1.35;
      overflow-wrap: anywhere;
    }

    .deleted-role-chip,
    .deleted-status-chip,
    .deleted-pending-chip,
    .deleted-id-chip {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 999px;
      padding: .34rem .62rem;
      font-size: .74rem;
      font-weight: 900;
      line-height: 1;
      white-space: nowrap;
    }

    .deleted-role-chip {
      border: 1px solid var(--uc-border-strong);
      background: rgba(155, 93, 224, .14);
      color: #d8b4fe;
    }

    .deleted-status-chip {
      border: 1px solid var(--uc-border);
    }

    .deleted-pending-chip {
      border: 1px solid rgba(245, 158, 11, .28);
      background: rgba(245, 158, 11, .12);
      color: #fbbf24;
      text-align: center;
      white-space: normal;
      line-height: 1.25;
      max-width: 100%;
    }

    .deleted-id-chip {
      border: 1px solid var(--uc-border);
      background: var(--uc-card-soft);
      color: var(--uc-muted);
      font-variant-numeric: tabular-nums;
    }

    .deleted-date-block,
    .deleted-by-block {
      min-width: 0;
    }

    .deleted-users-reason {
      max-width: 13rem;
      white-space: normal;
      color: var(--uc-muted);
      font-size: .82rem;
      line-height: 1.45;
      overflow-wrap: anywhere;
    }

    .deleted-badge {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 999px;
      padding: .32rem .65rem;
      color: #fff;
      background: #dc2626;
      font-weight: 800;
      font-size: .75rem;
    }

    .deleted-empty-state {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      min-height: 14rem;
      padding: 2rem;
      border: 1px dashed var(--uc-border-strong);
      border-radius: .85rem;
      background: var(--uc-card-soft);
      text-align: center;
    }

    .deleted-empty-state i {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 3rem;
      height: 3rem;
      margin-bottom: .75rem;
      border-radius: .85rem;
      background: rgba(155, 93, 224, .16);
      color: var(--uc-primary);
      font-size: 1.45rem;
    }

    .deleted-empty-state strong {
      color: var(--uc-text);
      font-size: 1rem;
      font-weight: 900;
    }

    .deleted-empty-state p {
      max-width: 28rem;
      margin: .35rem 0 0;
      color: var(--uc-muted);
      font-size: .9rem;
      line-height: 1.5;
    }

    body.light-mode .deleted-users-card {
      background: #fff;
      border-color: #e5e7eb;
    }

    body.light-mode .deleted-users-card .card-title {
      color: #111827;
    }

    body.light-mode .deleted-users-muted {
      color: #6b7280;
    }

    body.light-mode .deleted-users-count {
      color: #991b1b;
      background: #fef2f2;
      border-color: #fecaca;
    }

    body.light-mode .deleted-role-chip {
      color: #6d28d9;
      background: #f5f3ff;
      border-color: #ddd6fe;
    }

    body.light-mode .deleted-pending-chip {
      color: #92400e;
      background: #fffbeb;
      border-color: #fde68a;
    }

    @media (max-width: 1100px) {
      .deleted-users-table {
        min-width: 980px;
      }

      .deleted-users-table-wrap {
        overflow-x: auto;
      }
    }
  </style>
</head>
<body class="cre8-admin-layout"><?php cre8_bo_early_theme_print_body_script(); ?>
  <div class="container-scroller cre8-admin-page">
    <?php require_once __DIR__ . '/../layout/sidebar.php'; ?>

    <div class="container-fluid page-body-wrapper cre8-admin-main">
      <?php require_once __DIR__ . '/../layout/header.php'; ?>

      <div class="main-panel">
        <div class="content-wrapper user-center-shell admin-management-shell">
          <div class="row">
            <div class="col-12">
              <div class="uc-page-head">
                <div>
                  <p class="uc-kicker">Access Control</p>
                  <h1>Deleted users</h1>
                  <p>Restore soft-deleted user accounts or permanently delete eligible accounts after 7 days.</p>
                </div>
                <span class="uc-badge uc-role-badge">Hyper Admin</span>
              </div>

              <?php if ($flash): ?>
                <div class="alert alert-<?php echo deleted_users_h($flash['type']); ?> alert-dismissible fade show" role="alert">
                  <?php echo deleted_users_h($flash['message']); ?>
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
              <?php endif; ?>

              <?php if (!$softDeleteReady): ?>
                <div class="alert alert-warning" role="alert">
                  Soft-delete columns are missing. Run the manual SQL before using this page.
                </div>
              <?php endif; ?>
            </div>
          </div>

          <div class="uc-entity-tabs" aria-label="Admin management sections">
            <a href="admin_management.php" class="uc-entity-tab">
              <span class="uc-tab-icon"><i class="mdi mdi-account-key"></i></span>
              <span>
                <strong>Admin Accounts</strong>
                <small>Create and manage active BackOffice accounts.</small>
              </span>
            </a>
            <span class="uc-entity-tab is-active">
              <span class="uc-tab-icon"><i class="mdi mdi-delete-restore"></i></span>
              <span>
                <strong>Deleted Users</strong>
                <small>Restore soft-deleted users or review final delete eligibility.</small>
              </span>
            </span>
            <a href="restore_logs.php" class="uc-entity-tab">
              <span class="uc-tab-icon"><i class="mdi mdi-email-check-outline"></i></span>
              <span>
                <strong>Restore Logs</strong>
                <small>Track restore notifications and retry failed emails.</small>
              </span>
            </a>
            <a href="account_email_logs.php" class="uc-entity-tab">
              <span class="uc-tab-icon"><i class="mdi mdi-email-outline"></i></span>
              <span>
                <strong>Account Emails</strong>
                <small>Retry failed suspension, deletion, and reactivation emails.</small>
              </span>
            </a>
          </div>

          <div class="card deleted-users-card">
            <div class="deleted-users-card-head">
              <div>
                <h4 class="card-title mb-1">Deleted accounts</h4>
                <p class="deleted-users-muted mb-0"><?php echo count($deletedUsers); ?> account(s) currently soft-deleted.</p>
              </div>
              <span class="deleted-users-count"><?php echo count($deletedUsers); ?> deleted</span>
            </div>

            <div class="deleted-users-body">
              <?php if (empty($deletedUsers)): ?>
                <div class="deleted-empty-state">
                  <i class="mdi mdi-account-check-outline"></i>
                  <strong>No deleted users</strong>
                  <p>Soft-deleted accounts will appear here for Hyper Admin restore.</p>
                </div>
              <?php else: ?>
                <div class="deleted-users-table-wrap">
                  <table class="deleted-users-table">
                    <thead>
                      <tr>
                        <th>ID</th>
                        <th>Name / email</th>
                        <th>Role</th>
                        <th>Previous statut</th>
                        <th>Deleted at</th>
                        <th>Deleted by</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($deletedUsers as $user): ?>
                        <?php
                          $canFinalDelete = deleted_users_final_delete_ready($user['deleted_at'] ?? null);
                          $deletedBy = trim((string)($user['deleted_by'] ?? ''));
                          $deletedByRole = trim((string)($user['deleted_by_role'] ?? ''));
                          $status = trim((string)($user['statut'] ?? ''));
                          [$deletedDate, $deletedTime] = deleted_users_date_parts($user['deleted_at'] ?? null);
                        ?>
                        <tr>
                          <td><span class="deleted-id-chip">#<?php echo (int)($user['id'] ?? 0); ?></span></td>
                          <td>
                            <div class="deleted-user-cell">
                              <span class="deleted-user-avatar"><?php echo deleted_users_h(deleted_users_initial($user['nom'] ?? '')); ?></span>
                              <span class="deleted-user-meta">
                                <strong><?php echo deleted_users_h(($user['nom'] ?? '') !== '' ? $user['nom'] : 'Unnamed user'); ?></strong>
                                <span><?php echo deleted_users_h($user['email'] ?? ''); ?></span>
                              </span>
                            </div>
                          </td>
                          <td><span class="deleted-role-chip"><?php echo deleted_users_h($user['role'] ?? '-'); ?></span></td>
                          <td>
                            <span class="uc-badge deleted-status-chip <?php echo deleted_users_h(deleted_users_status_class($status)); ?>">
                              <?php echo deleted_users_h($status !== '' ? $status : 'unknown'); ?>
                            </span>
                          </td>
                          <td>
                            <span class="deleted-date-block">
                              <strong><?php echo deleted_users_h($deletedDate); ?></strong>
                              <?php if ($deletedTime !== ''): ?>
                                <span><?php echo deleted_users_h($deletedTime); ?></span>
                              <?php endif; ?>
                            </span>
                          </td>
                          <td>
                            <span class="deleted-by-block">
                              <strong>ID: <?php echo deleted_users_h($deletedBy !== '' ? $deletedBy : '-'); ?></strong>
                              <span><?php echo deleted_users_h($deletedByRole !== '' ? $deletedByRole : 'Unknown role'); ?></span>
                            </span>
                          </td>
                          <td class="deleted-users-reason"><?php echo deleted_users_h(($user['delete_reason'] ?? '') !== '' ? $user['delete_reason'] : '-'); ?></td>
                          <td>
                            <?php if (!empty($user['deleted_at'])): ?>
                              <span class="deleted-badge">Deleted</span>
                            <?php endif; ?>
                          </td>
                          <td>
                            <div class="deleted-users-actions">
                              <form method="POST" class="m-0" onsubmit="return confirm('Restore this user account?');">
                                <input type="hidden" name="id" value="<?php echo (int)($user['id'] ?? 0); ?>">
                                <input type="hidden" name="action" value="restore">
                                <button type="submit" class="uc-action-btn uc-action-primary">Restore</button>
                              </form>

                              <?php if ($canFinalDelete): ?>
                                <form method="POST" class="m-0" onsubmit="return confirm('Permanently delete this user account? This cannot be undone.');">
                                  <input type="hidden" name="id" value="<?php echo (int)($user['id'] ?? 0); ?>">
                                  <input type="hidden" name="action" value="final_delete">
                                  <button type="submit" class="uc-action-btn uc-action-danger">Final Delete</button>
                                </form>
                              <?php else: ?>
                                <span class="deleted-pending-chip">Final delete after 7 days</span>
                              <?php endif; ?>
                            </div>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="assets/vendors/js/vendor.bundle.base.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../layout/back-layout.js?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../layout/back-layout.js')); ?>"></script>
</body>
</html>
