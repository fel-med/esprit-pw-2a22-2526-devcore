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

function restore_logs_redirect(): void
{
    header('Location: restore_logs.php');
    exit;
}

function restore_logs_flash(string $type, string $message): void
{
    $_SESSION['restore_logs_flash'] = [
        'type' => $type === 'success' ? 'success' : 'danger',
        'message' => $message,
    ];
}

function restore_logs_h($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function restore_logs_initial($name): string
{
    $name = trim((string)($name ?? ''));
    return $name !== '' ? strtoupper(substr($name, 0, 1)) : '?';
}

function restore_logs_email_status(array $row): string
{
    $explicit = strtolower(trim((string)($row['emailStatus'] ?? '')));
    if (in_array($explicit, ['sent', 'failed', 'pending'], true)) {
        return $explicit;
    }

    return (int)($row['emailSent'] ?? 0) === 1 ? 'sent' : 'failed';
}

function restore_logs_attempts(array $row): int
{
    if (isset($row['emailAttempts']) && is_numeric($row['emailAttempts'])) {
        return max(0, (int)$row['emailAttempts']);
    }

    return 1;
}

function restore_logs_last_attempt(array $row): string
{
    foreach (['lastEmailAttemptAt', 'emailSentAt', 'createdAt'] as $key) {
        if (!empty($row[$key])) {
            return (string)$row[$key];
        }
    }

    return '-';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    $restoreLogId = (int)($_POST['idRestore'] ?? 0);

    if ($action !== 'retry_email' || $restoreLogId <= 0) {
        restore_logs_flash('danger', 'Invalid retry request.');
        restore_logs_redirect();
    }

    $result = $userC->retryRestoreEmailByLogId($restoreLogId, $currentUserId, $currentRole);
    restore_logs_flash(
        !empty($result['success']) ? 'success' : 'danger',
        (string)($result['message'] ?? (!empty($result['success']) ? 'Restore email sent successfully.' : 'Restore email retry failed.'))
    );
    restore_logs_redirect();
}

$logsReady = $userC->accountRestoreLogsReady();
$restoreLogs = $logsReady ? $userC->getAccountRestoreLogs() : [];
$flash = $_SESSION['restore_logs_flash'] ?? null;
unset($_SESSION['restore_logs_flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php cre8_bo_early_theme_print_head_script(); ?>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Restore logs - Cre8Connect</title>
  <link rel="stylesheet" href="../assets/vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="../assets/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../layout/back-layout.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../layout/back-layout.css')); ?>">
  <link rel="stylesheet" href="user-center-admin.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/user-center-admin.css')); ?>">
  <link rel="stylesheet" href="../unified-table-admin.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../unified-table-admin.css')); ?>">
  <link rel="icon" type="image/png" sizes="16x16" href="../../public/images/favicon-16.png">
  <link rel="icon" type="image/png" sizes="32x32" href="../../public/images/favicon-32.png">
  <style>
    .restore-logs-shell .uc-entity-tabs {
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    }

    .restore-logs-card {
      border: 1px solid var(--uc-border);
      border-radius: .85rem;
      background: var(--uc-card);
      color: var(--uc-text);
      box-shadow: 0 14px 34px var(--uc-shadow);
      overflow: hidden;
    }

    .restore-logs-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      padding: 1.15rem 1.25rem;
      border-bottom: 1px solid var(--uc-border);
    }

    .restore-logs-head h4 {
      margin: 0;
      color: var(--uc-text);
      font-weight: 900;
    }

    .restore-logs-muted {
      color: var(--uc-muted);
    }

    .restore-logs-body {
      padding: 1.15rem;
    }

    .restore-logs-table-wrap {
      border: 1px solid var(--uc-border);
      border-radius: .75rem;
      overflow-x: auto;
      background: var(--uc-card);
    }

    .restore-logs-table {
      width: 100%;
      min-width: 1120px;
      margin: 0;
      border-collapse: separate;
      border-spacing: 0;
      table-layout: fixed;
      color: var(--uc-text);
    }

    .restore-logs-table th,
    .restore-logs-table td {
      padding: .95rem;
      border: 0;
      border-bottom: 1px solid var(--uc-border);
      vertical-align: middle;
    }

    .restore-logs-table th {
      background: var(--uc-table-head);
      color: var(--uc-text);
      font-size: .72rem;
      font-weight: 900;
      letter-spacing: .04em;
      text-transform: uppercase;
      white-space: nowrap;
    }

    .restore-logs-table tbody tr:hover {
      background: rgba(148, 163, 184, .08);
    }

    .restore-logs-table tbody tr:last-child td {
      border-bottom: 0;
    }

    .restore-user-cell {
      display: flex;
      align-items: center;
      gap: .75rem;
      min-width: 0;
    }

    .restore-user-avatar {
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
    }

    .restore-user-meta strong,
    .restore-small-block strong {
      display: block;
      color: var(--uc-text);
      font-weight: 900;
      overflow-wrap: anywhere;
    }

    .restore-user-meta span,
    .restore-small-block span,
    .restore-error {
      display: block;
      color: var(--uc-muted);
      font-size: .8rem;
      line-height: 1.35;
      overflow-wrap: anywhere;
    }

    .restore-chip {
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

    .restore-chip.sent {
      color: #bbf7d0;
      background: rgba(22, 163, 74, .16);
      border: 1px solid rgba(22, 163, 74, .3);
    }

    .restore-chip.failed {
      color: #fecaca;
      background: rgba(220, 38, 38, .16);
      border: 1px solid rgba(220, 38, 38, .3);
    }

    .restore-chip.pending {
      color: #fde68a;
      background: rgba(245, 158, 11, .14);
      border: 1px solid rgba(245, 158, 11, .3);
    }

    .restore-empty-state {
      padding: 2rem;
      border: 1px dashed var(--uc-border-strong);
      border-radius: .85rem;
      background: var(--uc-card-soft);
      text-align: center;
      color: var(--uc-muted);
    }

    body.light-mode .restore-chip.sent {
      color: #166534;
      background: #f0fdf4;
      border-color: #bbf7d0;
    }

    body.light-mode .restore-chip.failed {
      color: #991b1b;
      background: #fef2f2;
      border-color: #fecaca;
    }

    body.light-mode .restore-chip.pending {
      color: #92400e;
      background: #fffbeb;
      border-color: #fde68a;
    }
  </style>
</head>
<body class="cre8-admin-layout"><?php cre8_bo_early_theme_print_body_script(); ?>
  <div class="container-scroller cre8-admin-page">
    <?php require_once __DIR__ . '/../layout/sidebar.php'; ?>

    <div class="container-fluid page-body-wrapper cre8-admin-main">
      <?php require_once __DIR__ . '/../layout/header.php'; ?>

      <div class="main-panel">
        <div class="content-wrapper user-center-shell admin-management-shell restore-logs-shell">
          <div class="row">
            <div class="col-12">
              <div class="uc-page-head">
                <div>
                  <p class="uc-kicker">Access Control</p>
                  <h1>Restore logs</h1>
                  <p>Review account restore email delivery and retry failed restore emails.</p>
                </div>
                <span class="uc-badge uc-role-badge">Hyper Admin</span>
              </div>

              <?php if ($flash): ?>
                <div class="alert alert-<?php echo restore_logs_h($flash['type']); ?> alert-dismissible fade show" role="alert">
                  <?php echo restore_logs_h($flash['message']); ?>
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
              <?php endif; ?>

              <?php if (!$logsReady): ?>
                <div class="alert alert-warning" role="alert">
                  account_restore_logs table was not found. Run the manual SQL before using restore logs.
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
            <a href="deleted_users.php" class="uc-entity-tab">
              <span class="uc-tab-icon"><i class="mdi mdi-delete-restore"></i></span>
              <span>
                <strong>Deleted Users</strong>
                <small>Restore soft-deleted users or review final delete eligibility.</small>
              </span>
            </a>
            <span class="uc-entity-tab is-active">
              <span class="uc-tab-icon"><i class="mdi mdi-email-check-outline"></i></span>
              <span>
                <strong>Restore Logs</strong>
                <small>Track restore notifications and retry failed emails.</small>
              </span>
            </span>
            <a href="account_email_logs.php" class="uc-entity-tab">
              <span class="uc-tab-icon"><i class="mdi mdi-email-outline"></i></span>
              <span>
                <strong>Account Emails</strong>
                <small>Retry failed suspension, deletion, and reactivation emails.</small>
              </span>
            </a>
          </div>

          <div class="restore-logs-card">
            <div class="restore-logs-head">
              <div>
                <h4>Restored accounts</h4>
                <p class="restore-logs-muted mb-0"><?php echo count($restoreLogs); ?> restore log(s).</p>
              </div>
            </div>

            <div class="restore-logs-body">
              <?php if (empty($restoreLogs)): ?>
                <div class="restore-empty-state">
                  <strong>No restore logs yet.</strong><br>
                  Restored accounts will appear here after Hyper Admin restores a deleted user.
                </div>
              <?php else: ?>
                <div class="restore-logs-table-wrap">
                  <table class="restore-logs-table">
                    <thead>
                      <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Restored at</th>
                        <th>Restored by</th>
                        <th>Email status</th>
                        <th>Attempts</th>
                        <th>Last attempt</th>
                        <th>Error</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($restoreLogs as $row): ?>
                        <?php
                          $emailStatus = restore_logs_email_status($row);
                          $attempts = restore_logs_attempts($row);
                          $lastAttempt = restore_logs_last_attempt($row);
                          $error = trim((string)($row['emailError'] ?? ''));
                          if ($error === '' && $emailStatus === 'failed') {
                              $error = 'Email failed or not sent. Run SQL for detailed error storage.';
                          }
                          $restoredAt = $row['restored_at'] ?? $row['createdAt'] ?? '-';
                          $restoredBy = $row['restored_by'] ?? $row['restoredBy'] ?? null;
                          $restoredByRole = $row['restored_by_role'] ?? $row['restoredByRole'] ?? '';
                        ?>
                        <tr>
                          <td>
                            <div class="restore-user-cell">
                              <span class="restore-user-avatar"><?php echo restore_logs_h(restore_logs_initial($row['nom'] ?? '')); ?></span>
                              <span class="restore-user-meta">
                                <strong>#<?php echo (int)($row['idUtilisateur'] ?? 0); ?> <?php echo restore_logs_h(($row['nom'] ?? '') !== '' ? $row['nom'] : 'Unknown user'); ?></strong>
                                <span><?php echo restore_logs_h($row['email'] ?? ''); ?></span>
                              </span>
                            </div>
                          </td>
                          <td><span class="restore-chip pending"><?php echo restore_logs_h($row['role'] ?? '-'); ?></span></td>
                          <td><span class="restore-small-block"><strong><?php echo restore_logs_h($restoredAt); ?></strong></span></td>
                          <td>
                            <span class="restore-small-block">
                              <strong>ID: <?php echo restore_logs_h($restoredBy !== null && $restoredBy !== '' ? $restoredBy : '-'); ?></strong>
                              <span><?php echo restore_logs_h($restoredByRole !== '' ? $restoredByRole : 'Unknown role'); ?></span>
                            </span>
                          </td>
                          <td><span class="restore-chip <?php echo restore_logs_h($emailStatus); ?>"><?php echo restore_logs_h($emailStatus); ?></span></td>
                          <td><?php echo (int)$attempts; ?></td>
                          <td><?php echo restore_logs_h($lastAttempt); ?></td>
                          <td><span class="restore-error"><?php echo restore_logs_h($error !== '' ? $error : '-'); ?></span></td>
                          <td>
                            <?php if ($emailStatus === 'failed'): ?>
                              <form method="POST" class="m-0" onsubmit="return confirm('Retry restore email for this user?');">
                                <input type="hidden" name="idRestore" value="<?php echo (int)($row['idRestore'] ?? 0); ?>">
                                <input type="hidden" name="action" value="retry_email">
                                <button type="submit" class="uc-action-btn uc-action-primary">Retry Email</button>
                              </form>
                            <?php else: ?>
                              <span class="restore-logs-muted small">No action</span>
                            <?php endif; ?>
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
