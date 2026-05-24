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

function account_email_logs_redirect(): void
{
    header('Location: account_email_logs.php');
    exit;
}

function account_email_logs_flash(string $type, string $message): void
{
    $_SESSION['account_email_logs_flash'] = [
        'type' => $type === 'success' ? 'success' : 'danger',
        'message' => $message,
    ];
}

function account_email_logs_h($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function account_email_logs_initial($name): string
{
    $name = trim((string)($name ?? ''));
    return $name !== '' ? strtoupper(substr($name, 0, 1)) : '?';
}

function account_email_logs_status(array $row): string
{
    $status = strtolower(trim((string)($row['emailStatus'] ?? 'pending')));
    return in_array($status, ['sent', 'failed', 'pending'], true) ? $status : 'pending';
}

function account_email_logs_last_attempt(array $row): string
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
    $emailLogId = (int)($_POST['idEmailLog'] ?? 0);

    if ($action !== 'retry_email' || $emailLogId <= 0) {
        account_email_logs_flash('danger', 'Invalid retry request.');
        account_email_logs_redirect();
    }

    $result = $userC->retryAccountStatusEmailByLogId($emailLogId, $currentUserId, $currentRole);
    account_email_logs_flash(
        !empty($result['success']) ? 'success' : 'danger',
        (string)($result['message'] ?? (!empty($result['success']) ? 'Account email sent successfully.' : 'Account email retry failed.'))
    );
    account_email_logs_redirect();
}

$logsReady = $userC->accountEmailLogsReady();
$emailLogs = $logsReady ? $userC->getAccountEmailLogs() : [];
$flash = $_SESSION['account_email_logs_flash'] ?? null;
unset($_SESSION['account_email_logs_flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php cre8_bo_early_theme_print_head_script(); ?>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Account emails - Cre8Connect</title>
  <link rel="stylesheet" href="../assets/vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="../assets/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../layout/back-layout.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../layout/back-layout.css')); ?>">
  <link rel="stylesheet" href="user-center-admin.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/user-center-admin.css')); ?>">
  <link rel="stylesheet" href="../unified-table-admin.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../unified-table-admin.css')); ?>">
  <link rel="icon" type="image/png" sizes="16x16" href="../../public/images/favicon-16.png">
  <link rel="icon" type="image/png" sizes="32x32" href="../../public/images/favicon-32.png">
  <style>
    .account-email-shell .uc-entity-tabs {
      grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    }

    .account-email-card {
      border: 1px solid var(--uc-border);
      border-radius: .85rem;
      background: var(--uc-card);
      color: var(--uc-text);
      box-shadow: 0 14px 34px var(--uc-shadow);
      overflow: hidden;
    }

    .account-email-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      padding: 1.15rem 1.25rem;
      border-bottom: 1px solid var(--uc-border);
    }

    .account-email-head h4 {
      margin: 0;
      color: var(--uc-text);
      font-weight: 900;
    }

    .account-email-muted {
      color: var(--uc-muted);
    }

    .account-email-body {
      padding: 1.15rem;
    }

    .account-email-table-wrap {
      border: 1px solid var(--uc-border);
      border-radius: .75rem;
      overflow-x: auto;
      background: var(--uc-card);
    }

    .account-email-table {
      width: 100%;
      min-width: 1160px;
      margin: 0;
      border-collapse: separate;
      border-spacing: 0;
      table-layout: fixed;
      color: var(--uc-text);
    }

    .account-email-table th,
    .account-email-table td {
      padding: .9rem;
      border: 0;
      border-bottom: 1px solid var(--uc-border);
      vertical-align: middle;
    }

    .account-email-table th {
      background: var(--uc-table-head);
      color: var(--uc-text);
      font-size: .72rem;
      font-weight: 900;
      letter-spacing: .04em;
      text-transform: uppercase;
      white-space: nowrap;
    }

    .account-email-table tbody tr:hover {
      background: rgba(148, 163, 184, .08);
    }

    .account-email-table tbody tr:last-child td {
      border-bottom: 0;
    }

    .account-email-user {
      display: flex;
      align-items: center;
      gap: .75rem;
      min-width: 0;
    }

    .account-email-avatar {
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

    .account-email-user strong,
    .account-email-small strong {
      display: block;
      color: var(--uc-text);
      font-weight: 900;
      overflow-wrap: anywhere;
    }

    .account-email-user span,
    .account-email-small span,
    .account-email-error,
    .account-email-reason {
      display: block;
      color: var(--uc-muted);
      font-size: .8rem;
      line-height: 1.35;
      overflow-wrap: anywhere;
    }

    .account-email-chip {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 999px;
      padding: .34rem .62rem;
      font-size: .74rem;
      font-weight: 900;
      line-height: 1;
      white-space: nowrap;
      border: 1px solid rgba(148, 163, 184, .24);
      background: rgba(148, 163, 184, .12);
      color: var(--uc-text);
    }

    .account-email-chip.sent {
      color: #bbf7d0;
      background: rgba(22, 163, 74, .16);
      border-color: rgba(22, 163, 74, .3);
    }

    .account-email-chip.failed {
      color: #fecaca;
      background: rgba(220, 38, 38, .16);
      border-color: rgba(220, 38, 38, .3);
    }

    .account-email-chip.pending {
      color: #fde68a;
      background: rgba(245, 158, 11, .14);
      border-color: rgba(245, 158, 11, .3);
    }

    .account-email-empty {
      padding: 2rem;
      border: 1px dashed var(--uc-border-strong);
      border-radius: .85rem;
      background: var(--uc-card-soft);
      text-align: center;
      color: var(--uc-muted);
    }

    body.light-mode .account-email-chip.sent {
      color: #166534;
      background: #f0fdf4;
      border-color: #bbf7d0;
    }

    body.light-mode .account-email-chip.failed {
      color: #991b1b;
      background: #fef2f2;
      border-color: #fecaca;
    }

    body.light-mode .account-email-chip.pending {
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
        <div class="content-wrapper user-center-shell admin-management-shell account-email-shell">
          <div class="row">
            <div class="col-12">
              <div class="uc-page-head">
                <div>
                  <p class="uc-kicker">Access Control</p>
                  <h1>Account emails</h1>
                  <p>Review account status email delivery and retry failed notifications.</p>
                </div>
                <span class="uc-badge uc-role-badge">Hyper Admin</span>
              </div>

              <?php if ($flash): ?>
                <div class="alert alert-<?php echo account_email_logs_h($flash['type']); ?> alert-dismissible fade show" role="alert">
                  <?php echo account_email_logs_h($flash['message']); ?>
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
              <?php endif; ?>

              <?php if (!$logsReady): ?>
                <div class="alert alert-warning" role="alert">
                  account_email_logs table was not found. Run the manual SQL before using account email logs.
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
            <a href="restore_logs.php" class="uc-entity-tab">
              <span class="uc-tab-icon"><i class="mdi mdi-email-check-outline"></i></span>
              <span>
                <strong>Restore Logs</strong>
                <small>Track restore notifications and retry failed emails.</small>
              </span>
            </a>
            <span class="uc-entity-tab is-active">
              <span class="uc-tab-icon"><i class="mdi mdi-email-outline"></i></span>
              <span>
                <strong>Account Emails</strong>
                <small>Retry failed suspension, deletion, and reactivation emails.</small>
              </span>
            </span>
          </div>

          <?php if ($logsReady): ?>
            <div class="account-email-card">
              <div class="account-email-head">
                <div>
                  <h4>Account status emails</h4>
                  <p class="account-email-muted mb-0"><?php echo count($emailLogs); ?> email log(s).</p>
                </div>
              </div>

              <div class="account-email-body">
                <?php if (empty($emailLogs)): ?>
                  <div class="account-email-empty">
                    <strong>No account email logs yet.</strong><br>
                    Suspension, deletion, reactivation, and restore emails will appear here.
                  </div>
                <?php else: ?>
                  <div class="account-email-table-wrap">
                    <table class="account-email-table">
                      <thead>
                        <tr>
                          <th>User</th>
                          <th>Event</th>
                          <th>Actor</th>
                          <th>Reason</th>
                          <th>Status</th>
                          <th>Attempts</th>
                          <th>Last attempt</th>
                          <th>Error</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($emailLogs as $row): ?>
                          <?php
                            $emailStatus = account_email_logs_status($row);
                            $name = trim((string)($row['nom'] ?? ''));
                            $email = trim((string)($row['email'] ?? ''));
                            $displayName = $name !== '' ? $name : 'Unknown user';
                          ?>
                          <tr>
                            <td>
                              <div class="account-email-user">
                                <span class="account-email-avatar"><?php echo account_email_logs_h(account_email_logs_initial($displayName)); ?></span>
                                <span>
                                  <strong>#<?php echo (int)($row['idUtilisateur'] ?? 0); ?> <?php echo account_email_logs_h($displayName); ?></strong>
                                  <span><?php echo account_email_logs_h($email); ?></span>
                                </span>
                              </div>
                            </td>
                            <td><span class="account-email-chip"><?php echo account_email_logs_h($row['eventType'] ?? '-'); ?></span></td>
                            <td>
                              <span class="account-email-small">
                                <strong>ID: <?php echo account_email_logs_h(($row['actorId'] ?? '') !== '' ? $row['actorId'] : '-'); ?></strong>
                                <span><?php echo account_email_logs_h(($row['actorRole'] ?? '') !== '' ? $row['actorRole'] : 'Unknown role'); ?></span>
                              </span>
                            </td>
                            <td><span class="account-email-reason"><?php echo account_email_logs_h(($row['reason'] ?? '') !== '' ? $row['reason'] : '-'); ?></span></td>
                            <td><span class="account-email-chip <?php echo account_email_logs_h($emailStatus); ?>"><?php echo account_email_logs_h($emailStatus); ?></span></td>
                            <td><?php echo (int)($row['emailAttempts'] ?? 0); ?></td>
                            <td><?php echo account_email_logs_h(account_email_logs_last_attempt($row)); ?></td>
                            <td><span class="account-email-error"><?php echo account_email_logs_h(($row['emailError'] ?? '') !== '' ? $row['emailError'] : '-'); ?></span></td>
                            <td>
                              <?php if ($emailStatus === 'failed'): ?>
                                <form method="POST" class="m-0" onsubmit="return confirm('Retry this account email?');">
                                  <input type="hidden" name="idEmailLog" value="<?php echo (int)($row['idEmailLog'] ?? 0); ?>">
                                  <input type="hidden" name="action" value="retry_email">
                                  <button type="submit" class="uc-action-btn uc-action-primary">Retry Email</button>
                                </form>
                              <?php else: ?>
                                <span class="account-email-muted small">No action</span>
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
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <script src="assets/vendors/js/vendor.bundle.base.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../layout/back-layout.js?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../layout/back-layout.js')); ?>"></script>
</body>
</html>
