<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../layout/early-theme.php';
require_once __DIR__ . '/../../../Controleur/session_helper.php';
require_once __DIR__ . '/../../../Controleur/utilisateurC.php';
require_once __DIR__ . '/../../../Controleur/adminAuditC.php';
require_once __DIR__ . '/../../../Controleur/notificationC.php';

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


function admin_management_role_i18n_key(string $role): string
{
    return match (strtolower(trim($role))) {
        'hyper_admin' => 'adminManagement.role.hyperAdmin',
        'super_admin' => 'adminManagement.role.superAdmin',
        'admin' => 'adminManagement.role.admin',
        default => 'adminManagement.role.other',
    };
}

function admin_management_flash_key(string $message): ?string
{
    return match ($message) {
        'Name is required.' => 'adminManagement.flash.nameRequired',
        'A valid email is required.' => 'adminManagement.flash.emailRequired',
        'Password must contain at least 6 characters.' => 'adminManagement.flash.passwordRequired',
        'You are not allowed to create this role.' => 'adminManagement.flash.createRoleDenied',
        'Account created successfully.' => 'adminManagement.flash.created',
        'Target account was not found.' => 'adminManagement.flash.targetNotFound',
        'You are not allowed to perform this action.' => 'adminManagement.flash.actionDenied',
        'Only active accounts can be suspended.' => 'adminManagement.flash.activeOnly',
        'You are not allowed to reactivate this suspension.' => 'adminManagement.flash.reactivateDenied',
        'Account blocked successfully.' => 'adminManagement.flash.blocked',
        'Account activated successfully.' => 'adminManagement.flash.activated',
        'Account deleted successfully.' => 'adminManagement.flash.deleted',
        'Invalid action.' => 'adminManagement.flash.invalidAction',
        default => null,
    };
}

function admin_management_can_manage_target(string $actorRole, int $actorId, array $target, string $action): bool
{
    $targetRole = strtolower(trim((string)($target['role'] ?? '')));
    $allowedTargetRoles = $actorRole === 'hyper_admin' ? ['admin', 'super_admin'] : ['admin'];
    if (!in_array($targetRole, $allowedTargetRoles, true)) {
        return false;
    }

    $helperAction = match ($action) {
        'block' => 'suspend',
        'activate' => 'reactivate',
        default => $action,
    };

    return cc_can_manage_user($actorId, $actorRole, $target, $helperAction);
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
            if ($result === 'success') {
                $createdAdmin = $userC->getUserByEmail($email);
                if ($createdAdmin && !empty($createdAdmin['id'])) {
                    try {
                        $notificationC = new NotificationC();
                        $notificationC->notifyAdminCreated(
                            $createdAdmin['id'],
                            $createdAdmin['role'] ?? $role,
                            $createdAdmin['nom'] ?? $name,
                            $createdAdmin['email'] ?? $email,
                            $currentUserId,
                            $currentRole
                        );
                    } catch (Throwable $e) {
                        error_log('Admin Management admin_created notification failed: ' . $e->getMessage());
                    }
                }
            }
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
            if (strtolower(trim((string)($target['statut'] ?? ''))) !== 'actif') {
                admin_management_flash('danger', 'Only active accounts can be suspended.');
                admin_management_redirect();
            }

            $reason = 'Suspended from Admin Management';
            $userC->suspendUserWithMetadata($targetId, $currentUserId, $currentRole, $reason);
            cc_log_admin_action($currentUserId, $currentRole, 'suspend_user', $targetId, $target['role'] ?? null, $target['statut'] ?? null, 'suspendu', $reason);
            admin_management_flash('success', 'Account blocked successfully.');
        } elseif ($action === 'activate') {
            if (!cc_can_reactivate_suspension($currentUserId, $currentRole, $target)) {
                admin_management_flash('danger', 'You are not allowed to reactivate this suspension.');
                admin_management_redirect();
            }

            $reason = 'Reactivated from Admin Management';
            $userC->reactivateUserAndClearSuspension($targetId);
            cc_log_admin_action($currentUserId, $currentRole, 'reactivate_user', $targetId, $target['role'] ?? null, $target['statut'] ?? null, 'actif', $reason);
            admin_management_flash('success', 'Account activated successfully.');
        } elseif ($action === 'delete') {
            $userC->deleteUserById($targetId);
            cc_log_admin_action($currentUserId, $currentRole, 'delete_user', $targetId, $target['role'] ?? null, $target['statut'] ?? null, 'deleted', 'Deleted from Admin Management');
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
  <link rel="icon" type="image/png" sizes="16x16" href="../../public/images/favicon-16.png">
  <link rel="icon" type="image/png" sizes="32x32" href="../../public/images/favicon-32.png">
  <link rel="shortcut icon" type="image/png" href="../../public/images/favicon-32.png">
  <link rel="apple-touch-icon" sizes="180x180" href="../../public/images/apple-touch-icon.png">
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
                  <h2 class="page-title mb-1" data-i18n="adminManagement.title">Admin Management</h2>
                  <p class="admin-management-muted mb-0" data-i18n="adminManagement.subtitle">Create and manage BackOffice administrator accounts.</p>
                </div>
                <span class="badge bg-primary admin-management-badge">
                  <span data-i18n="<?php echo htmlspecialchars(admin_management_role_i18n_key($currentRole)); ?>"><?php echo htmlspecialchars(admin_management_role_label($currentRole)); ?></span>
                </span>
              </div>

              <?php if ($flash): ?>
                <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?> alert-dismissible fade show" role="alert">
                  <?php $flashKey = admin_management_flash_key((string)($flash['message'] ?? '')); ?>
                  <?php if ($flashKey): ?>
                    <span data-i18n="<?php echo htmlspecialchars($flashKey); ?>"><?php echo htmlspecialchars($flash['message']); ?></span>
                  <?php else: ?>
                    <?php echo htmlspecialchars($flash['message']); ?>
                  <?php endif; ?>
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <div class="row g-4">
            <div class="col-lg-4">
              <div class="card admin-management-card">
                <div class="card-body">
                  <h4 class="card-title mb-3" data-i18n="adminManagement.form.title">Add Account</h4>
                  <form method="POST" autocomplete="off">
                    <input type="hidden" name="action" value="create">

                    <div class="mb-3">
                      <label class="form-label" for="adminName" data-i18n="adminManagement.form.name">Name</label>
                      <input type="text" class="form-control" id="adminName" name="nom" required>
                    </div>

                    <div class="mb-3">
                      <label class="form-label" for="adminEmail" data-i18n="adminManagement.form.email">Email</label>
                      <input type="email" class="form-control" id="adminEmail" name="email" required>
                    </div>

                    <div class="mb-3">
                      <label class="form-label" for="adminPassword" data-i18n="adminManagement.form.password">Password</label>
                      <input type="password" class="form-control" id="adminPassword" name="password" minlength="6" required>
                    </div>

                    <div class="mb-4">
                      <label class="form-label" for="adminRole" data-i18n="adminManagement.form.role">Role</label>
                      <select class="form-select" id="adminRole" name="role" required>
                        <?php foreach ($createRoles as $role): ?>
                          <option value="<?php echo htmlspecialchars($role); ?>" data-i18n-opt="<?php echo htmlspecialchars(admin_management_role_i18n_key($role)); ?>"><?php echo htmlspecialchars(admin_management_role_label($role)); ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                      <i class="mdi mdi-account-plus"></i> <span data-i18n="adminManagement.action.create">Create Account</span>
                    </button>
                  </form>
                </div>
              </div>
            </div>

            <div class="col-lg-8">
              <div class="card admin-management-card">
                <div class="card-body">
                  <h4 class="card-title mb-3" data-i18n="adminManagement.table.title">Managed Accounts</h4>
                  <div class="table-responsive">
                    <table class="table table-hover align-middle">
                      <thead>
                        <tr>
                          <th>ID</th>
                          <th data-i18n="adminManagement.table.name">Name</th>
                          <th data-i18n="adminManagement.table.email">Email</th>
                          <th data-i18n="adminManagement.table.role">Role</th>
                          <th data-i18n="common.status">Status</th>
                          <th data-i18n="common.actions">Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php if (empty($adminAccounts)): ?>
                          <tr>
                            <td colspan="6" class="text-center admin-management-muted py-4" data-i18n="adminManagement.table.empty">No accounts found.</td>
                          </tr>
                        <?php endif; ?>

                        <?php foreach ($adminAccounts as $account): ?>
                          <?php
                            $targetRole = strtolower(trim((string)($account['role'] ?? '')));
                            $targetStatus = strtolower(trim((string)($account['statut'] ?? '')));
                            $isSelf = (int)$account['id'] === $currentUserId;
                            $canBlock = $targetStatus === 'suspendu'
                              ? admin_management_can_manage_target($currentRole, $currentUserId, $account, 'activate') && cc_can_reactivate_suspension($currentUserId, $currentRole, $account)
                              : ($targetStatus === 'actif' && admin_management_can_manage_target($currentRole, $currentUserId, $account, 'block'));
                            $canDelete = admin_management_can_manage_target($currentRole, $currentUserId, $account, 'delete');
                            $isSuspended = $targetStatus === 'suspendu';
                          ?>
                          <tr>
                            <td><?php echo (int)$account['id']; ?></td>
                            <td><?php echo htmlspecialchars($account['nom'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($account['email'] ?? ''); ?></td>
                            <td>
                              <span class="badge bg-info admin-management-badge"><span data-i18n="<?php echo htmlspecialchars(admin_management_role_i18n_key($targetRole)); ?>"><?php echo htmlspecialchars(admin_management_role_label($targetRole)); ?></span></span>
                            </td>
                            <td>
                              <span class="badge <?php echo $isSuspended ? 'bg-warning text-dark' : 'bg-success'; ?> admin-management-badge">
                                <span data-i18n="<?php echo $isSuspended ? 'adminManagement.status.blocked' : 'adminManagement.status.active'; ?>"><?php echo $isSuspended ? 'Blocked' : 'Active'; ?></span>
                              </span>
                            </td>
                            <td>
                              <div class="admin-management-actions">
                                <?php if ($canBlock): ?>
                                  <form method="POST" class="m-0">
                                    <input type="hidden" name="id" value="<?php echo (int)$account['id']; ?>">
                                    <input type="hidden" name="action" value="<?php echo $isSuspended ? 'activate' : 'block'; ?>">
                                    <button type="submit" class="btn btn-sm <?php echo $isSuspended ? 'btn-success' : 'btn-warning'; ?>">
                                      <span data-i18n="<?php echo $isSuspended ? 'common.activate' : 'adminManagement.action.block'; ?>"><?php echo $isSuspended ? 'Activate' : 'Block'; ?></span>
                                    </button>
                                  </form>
                                <?php endif; ?>

                                <?php if ($canDelete): ?>
                                  <form method="POST" class="m-0" onsubmit="return confirm(window.cre8BackText ? window.cre8BackText('adminManagement.confirm.delete') : 'Delete this administrator account permanently?');">
                                    <input type="hidden" name="id" value="<?php echo (int)$account['id']; ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="btn btn-sm btn-danger" data-i18n="common.delete">Delete</button>
                                  </form>
                                <?php endif; ?>

                                <?php if (!$canBlock && !$canDelete): ?>
                                  <span class="admin-management-muted small"><span data-i18n="<?php echo $isSelf ? 'adminManagement.action.currentAccount' : 'adminManagement.action.noActions'; ?>"><?php echo $isSelf ? 'Current account' : 'No actions'; ?></span></span>
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


<script>
window.cre8BackRegisterTranslations && window.cre8BackRegisterTranslations({
  en: {
    'adminManagement.title': 'Admin Management',
    'adminManagement.subtitle': 'Create and manage BackOffice administrator accounts.',
    'adminManagement.role.hyperAdmin': 'Hyper Admin',
    'adminManagement.role.superAdmin': 'Super Admin',
    'adminManagement.role.admin': 'Admin',
    'adminManagement.role.other': 'Other',
    'adminManagement.form.title': 'Add Account',
    'adminManagement.form.name': 'Name',
    'adminManagement.form.email': 'Email',
    'adminManagement.form.password': 'Password',
    'adminManagement.form.role': 'Role',
    'adminManagement.action.create': 'Create Account',
    'adminManagement.action.block': 'Block',
    'adminManagement.action.currentAccount': 'Current account',
    'adminManagement.action.noActions': 'No actions',
    'adminManagement.table.title': 'Managed Accounts',
    'adminManagement.table.name': 'Name',
    'adminManagement.table.email': 'Email',
    'adminManagement.table.role': 'Role',
    'adminManagement.table.empty': 'No accounts found.',
    'adminManagement.status.active': 'Active',
    'adminManagement.status.blocked': 'Blocked',
    'adminManagement.confirm.delete': 'Delete this administrator account permanently?',
    'adminManagement.flash.nameRequired': 'Name is required.',
    'adminManagement.flash.emailRequired': 'A valid email is required.',
    'adminManagement.flash.passwordRequired': 'Password must contain at least 6 characters.',
    'adminManagement.flash.createRoleDenied': 'You are not allowed to create this role.',
    'adminManagement.flash.created': 'Account created successfully.',
    'adminManagement.flash.targetNotFound': 'Target account was not found.',
    'adminManagement.flash.actionDenied': 'You are not allowed to perform this action.',
    'adminManagement.flash.activeOnly': 'Only active accounts can be suspended.',
    'adminManagement.flash.reactivateDenied': 'You are not allowed to reactivate this suspension.',
    'adminManagement.flash.blocked': 'Account blocked successfully.',
    'adminManagement.flash.activated': 'Account activated successfully.',
    'adminManagement.flash.deleted': 'Account deleted successfully.',
    'adminManagement.flash.invalidAction': 'Invalid action.'
  },
  fr: {
    'adminManagement.title': 'Gestion des admins',
    'adminManagement.subtitle': 'Creez et gerez les comptes administrateurs du BackOffice.',
    'adminManagement.role.hyperAdmin': 'Hyper admin',
    'adminManagement.role.superAdmin': 'Super admin',
    'adminManagement.role.admin': 'Admin',
    'adminManagement.role.other': 'Autre',
    'adminManagement.form.title': 'Ajouter un compte',
    'adminManagement.form.name': 'Nom',
    'adminManagement.form.email': 'Email',
    'adminManagement.form.password': 'Mot de passe',
    'adminManagement.form.role': 'Role',
    'adminManagement.action.create': 'Creer le compte',
    'adminManagement.action.block': 'Bloquer',
    'adminManagement.action.currentAccount': 'Compte actuel',
    'adminManagement.action.noActions': 'Aucune action',
    'adminManagement.table.title': 'Comptes geres',
    'adminManagement.table.name': 'Nom',
    'adminManagement.table.email': 'Email',
    'adminManagement.table.role': 'Role',
    'adminManagement.table.empty': 'Aucun compte trouve.',
    'adminManagement.status.active': 'Actif',
    'adminManagement.status.blocked': 'Bloque',
    'adminManagement.confirm.delete': 'Supprimer definitivement ce compte administrateur ?',
    'adminManagement.flash.nameRequired': 'Le nom est obligatoire.',
    'adminManagement.flash.emailRequired': 'Un email valide est obligatoire.',
    'adminManagement.flash.passwordRequired': 'Le mot de passe doit contenir au moins 6 caracteres.',
    'adminManagement.flash.createRoleDenied': 'Vous n etes pas autorise a creer ce role.',
    'adminManagement.flash.created': 'Compte cree avec succes.',
    'adminManagement.flash.targetNotFound': 'Le compte cible est introuvable.',
    'adminManagement.flash.actionDenied': 'Vous n etes pas autorise a effectuer cette action.',
    'adminManagement.flash.activeOnly': 'Seuls les comptes actifs peuvent etre suspendus.',
    'adminManagement.flash.reactivateDenied': 'Vous n etes pas autorise a reactiver cette suspension.',
    'adminManagement.flash.blocked': 'Compte bloque avec succes.',
    'adminManagement.flash.activated': 'Compte active avec succes.',
    'adminManagement.flash.deleted': 'Compte supprime avec succes.',
    'adminManagement.flash.invalidAction': 'Action invalide.'
  }
});
</script>
  <script src="assets/vendors/js/vendor.bundle.base.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../layout/back-layout.js?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../layout/back-layout.js')); ?>"></script>
</body>
</html>
