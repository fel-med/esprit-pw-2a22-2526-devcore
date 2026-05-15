<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../layout/early-theme.php';
require_once __DIR__ . '/../../../Controleur/session_helper.php';
require_once __DIR__ . '/../../../Controleur/utilisateurC.php';

$currentUserId = cc_require_admin('../../FrontOffice/utilisateur/login.php');
$currentRole = cc_current_user_role();

if (!isSuperAdminRole($currentRole)) {
    http_response_code(403);
    die('Access denied. Super admin account required.');
}

$userC = new UtilisateurC();
$backActive = 'admin_management';
$isHyperAdmin = isHyperAdmin($currentRole);
$visibleRoles = $isHyperAdmin ? ['admin', 'super_admin'] : ['admin'];
$createRoles = $isHyperAdmin ? ['admin', 'super_admin'] : ['admin'];

function admin_management_redirect(): void
{
    header('Location: admin_management.php');
    exit;
}

function admin_management_flash(string $type, string $message): void
{
    $_SESSION['admin_management_flash'] = [
        'type' => $type === 'success' ? 'success' : 'danger',
        'message' => $message,
    ];
}

function admin_management_role_label(string $role): string
{
    return match ($role) {
        'super_admin' => 'Super Admin',
        'admin' => 'Admin',
        default => ucfirst(str_replace('_', ' ', $role)),
    };
}

function admin_management_can_manage_target(string $actorRole, int $actorId, array $target, string $action): bool
{
    $targetId = (int)($target['id'] ?? 0);
    $targetRole = strtolower(trim((string)($target['role'] ?? '')));

    if ($targetId <= 0 || $targetId === $actorId || $targetRole === 'hyper_admin') {
        return false;
    }

    if ($actorRole === 'super_admin') {
        return $action !== 'delete' && $targetRole === 'admin';
    }

    if ($actorRole === 'hyper_admin') {
        return in_array($targetRole, ['admin', 'super_admin'], true);
    }

    return false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = strtolower(trim((string)($_POST['action'] ?? '')));

    if ($action === 'create') {
        $name = trim((string)($_POST['nom'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $role = strtolower(trim((string)($_POST['role'] ?? '')));

        if ($name === '') {
            admin_management_flash('danger', 'Name is required.');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            admin_management_flash('danger', 'A valid email is required.');
        } elseif (strlen($password) < 6) {
            admin_management_flash('danger', 'Password must contain at least 6 characters.');
        } elseif (!in_array($role, $createRoles, true)) {
            admin_management_flash('danger', 'You are not allowed to create this role.');
        } else {
            $result = $userC->ajouterAdminAccount($name, $email, $password, $role);
            admin_management_flash(
                $result === 'success' ? 'success' : 'danger',
                $result === 'success' ? 'Account created successfully.' : $result
            );
        }

        admin_management_redirect();
    }

    if (in_array($action, ['block', 'activate', 'delete'], true)) {
        $targetId = (int)($_POST['id'] ?? 0);
        $target = $userC->getUserById($targetId);

        if (!$target) {
            admin_management_flash('danger', 'Target account was not found.');
            admin_management_redirect();
        }

        if (!admin_management_can_manage_target($currentRole, $currentUserId, $target, $action)) {
            admin_management_flash('danger', 'You are not allowed to perform this action.');
            admin_management_redirect();
        }

        if ($action === 'block') {
            $userC->updateUserStatus($targetId, 'suspendu');
            admin_management_flash('success', 'Account blocked successfully.');
        } elseif ($action === 'activate') {
            $userC->updateUserStatus($targetId, 'actif');
            admin_management_flash('success', 'Account activated successfully.');
        } elseif ($action === 'delete') {
            if (!$isHyperAdmin) {
                admin_management_flash('danger', 'Only a hyper admin can delete admin accounts.');
                admin_management_redirect();
            }

            $userC->deleteUserById($targetId);
            admin_management_flash('success', 'Account deleted successfully.');
        }

        admin_management_redirect();
    }

    admin_management_flash('danger', 'Invalid action.');
    admin_management_redirect();
}

$adminAccounts = $userC->afficherAdminAccounts($visibleRoles);
$flash = $_SESSION['admin_management_flash'] ?? null;
unset($_SESSION['admin_management_flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php cre8_bo_early_theme_print_head_script(); ?>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Admin Management - cre8connect</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="../layout/back-layout.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../layout/back-layout.css')); ?>">
  <link rel="icon" type="image/png" sizes="32x32" href="../../public/images/logo.png">
  <style>
    .admin-management-card {
      border: 1px solid rgba(255,255,255,.08);
      border-radius: 14px;
      background: #191c24;
      box-shadow: 0 10px 30px rgba(0,0,0,.18);
    }

    .admin-management-card .card-title {
      color: #fff;
    }

    .admin-management-muted {
      color: #9ca3af;
    }

    .admin-management-badge {
      border-radius: 999px;
      padding: .35rem .65rem;
      font-weight: 700;
    }

    .admin-management-actions {
      display: flex;
      flex-wrap: wrap;
      gap: .4rem;
    }

    body.light-mode .admin-management-card {
      background: #fff;
      border-color: #e5e7eb;
    }

    body.light-mode .admin-management-card .card-title {
      color: #111827;
    }

    body.light-mode .admin-management-muted {
      color: #6b7280;
    }
  </style>
</head>
<body class="cre8-admin-layout"><?php cre8_bo_early_theme_print_body_script(); ?>
  <div class="container-scroller cre8-admin-page">
    <?php require_once __DIR__ . '/../layout/sidebar.php'; ?>

    <div class="container-fluid page-body-wrapper cre8-admin-main">
      <?php require_once __DIR__ . '/../layout/header.php'; ?>

      <div class="main-panel">
        <div class="content-wrapper">
          <div class="row">
            <div class="col-12">
              <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
                <div>
                  <h2 class="page-title mb-1">Admin Management</h2>
                  <p class="admin-management-muted mb-0">Create and manage BackOffice administrator accounts.</p>
                </div>
                <span class="badge bg-primary admin-management-badge">
                  <?php echo htmlspecialchars(admin_management_role_label($currentRole)); ?>
                </span>
              </div>

              <?php if ($flash): ?>
                <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?> alert-dismissible fade show" role="alert">
                  <?php echo htmlspecialchars($flash['message']); ?>
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <div class="row g-4">
            <div class="col-lg-4">
              <div class="card admin-management-card">
                <div class="card-body">
                  <h4 class="card-title mb-3">Add Account</h4>
                  <form method="POST" autocomplete="off">
                    <input type="hidden" name="action" value="create">

                    <div class="mb-3">
                      <label class="form-label" for="adminName">Name</label>
                      <input type="text" class="form-control" id="adminName" name="nom" required>
                    </div>

                    <div class="mb-3">
                      <label class="form-label" for="adminEmail">Email</label>
                      <input type="email" class="form-control" id="adminEmail" name="email" required>
                    </div>

                    <div class="mb-3">
                      <label class="form-label" for="adminPassword">Password</label>
                      <input type="password" class="form-control" id="adminPassword" name="password" minlength="6" required>
                    </div>

                    <div class="mb-4">
                      <label class="form-label" for="adminRole">Role</label>
                      <select class="form-select" id="adminRole" name="role" required>
                        <?php foreach ($createRoles as $role): ?>
                          <option value="<?php echo htmlspecialchars($role); ?>"><?php echo htmlspecialchars(admin_management_role_label($role)); ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                      <i class="mdi mdi-account-plus"></i> Create Account
                    </button>
                  </form>
                </div>
              </div>
            </div>

            <div class="col-lg-8">
              <div class="card admin-management-card">
                <div class="card-body">
                  <h4 class="card-title mb-3">Managed Accounts</h4>
                  <div class="table-responsive">
                    <table class="table table-hover align-middle">
                      <thead>
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
                        <?php if (empty($adminAccounts)): ?>
                          <tr>
                            <td colspan="6" class="text-center admin-management-muted py-4">No accounts found.</td>
                          </tr>
                        <?php endif; ?>

                        <?php foreach ($adminAccounts as $account): ?>
                          <?php
                            $targetRole = strtolower(trim((string)($account['role'] ?? '')));
                            $targetStatus = strtolower(trim((string)($account['statut'] ?? '')));
                            $isSelf = (int)$account['id'] === $currentUserId;
                            $canBlock = admin_management_can_manage_target($currentRole, $currentUserId, $account, 'block');
                            $canDelete = admin_management_can_manage_target($currentRole, $currentUserId, $account, 'delete') && $isHyperAdmin;
                            $isSuspended = $targetStatus === 'suspendu';
                          ?>
                          <tr>
                            <td><?php echo (int)$account['id']; ?></td>
                            <td><?php echo htmlspecialchars($account['nom'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($account['email'] ?? ''); ?></td>
                            <td>
                              <span class="badge bg-info admin-management-badge"><?php echo htmlspecialchars(admin_management_role_label($targetRole)); ?></span>
                            </td>
                            <td>
                              <span class="badge <?php echo $isSuspended ? 'bg-warning text-dark' : 'bg-success'; ?> admin-management-badge">
                                <?php echo $isSuspended ? 'Blocked' : 'Active'; ?>
                              </span>
                            </td>
                            <td>
                              <div class="admin-management-actions">
                                <?php if ($canBlock): ?>
                                  <form method="POST" class="m-0">
                                    <input type="hidden" name="id" value="<?php echo (int)$account['id']; ?>">
                                    <input type="hidden" name="action" value="<?php echo $isSuspended ? 'activate' : 'block'; ?>">
                                    <button type="submit" class="btn btn-sm <?php echo $isSuspended ? 'btn-success' : 'btn-warning'; ?>">
                                      <?php echo $isSuspended ? 'Activate' : 'Block'; ?>
                                    </button>
                                  </form>
                                <?php endif; ?>

                                <?php if ($canDelete): ?>
                                  <form method="POST" class="m-0" onsubmit="return confirm('Delete this administrator account permanently?');">
                                    <input type="hidden" name="id" value="<?php echo (int)$account['id']; ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                  </form>
                                <?php endif; ?>

                                <?php if (!$canBlock && !$canDelete): ?>
                                  <span class="admin-management-muted small"><?php echo $isSelf ? 'Current account' : 'No actions'; ?></span>
                                <?php endif; ?>
                              </div>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <footer class="footer">
          <div class="d-sm-flex justify-content-center justify-content-sm-between">
            <span class="text-muted d-block text-center text-sm-left d-sm-inline-block">Copyright &copy; cre8connect 2026</span>
          </div>
        </footer>
      </div>
    </div>
  </div>

  <script src="assets/vendors/js/vendor.bundle.base.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../layout/back-layout.js?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../layout/back-layout.js')); ?>"></script>
</body>
</html>
