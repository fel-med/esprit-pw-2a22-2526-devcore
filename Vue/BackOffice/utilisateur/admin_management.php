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

$adminAccountsTotal = count($adminAccounts);
$adminAccountsPerPageOptions = [5, 10, 20, 50];
$adminAccountsPerPage = (int)($_GET['per_page'] ?? 10);
if (!in_array($adminAccountsPerPage, $adminAccountsPerPageOptions, true)) {
    $adminAccountsPerPage = 10;
}
$adminAccountsTotalPages = max(1, (int)ceil($adminAccountsTotal / $adminAccountsPerPage));
$adminAccountsPage = max(1, (int)($_GET['page'] ?? 1));
$adminAccountsPage = min($adminAccountsPage, $adminAccountsTotalPages);
$adminAccountsOffset = ($adminAccountsPage - 1) * $adminAccountsPerPage;
$adminAccountsPageRows = array_slice($adminAccounts, $adminAccountsOffset, $adminAccountsPerPage);

function admin_management_page_url(int $page, int $perPage): string
{
    $query = $_GET;
    $query['page'] = max(1, $page);
    $query['per_page'] = $perPage;
    return 'admin_management.php?' . http_build_query($query);
}

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
  <link rel="stylesheet" href="user-center-admin.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/user-center-admin.css')); ?>">
  <link rel="stylesheet" href="../unified-table-admin.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../unified-table-admin.css')); ?>">
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
        <div class="content-wrapper user-center-shell admin-management-shell">
          <div class="row">
            <div class="col-12">
              <div class="uc-page-head">
                <div>
                  <p class="uc-kicker" data-i18n="adminManagement.kicker">Access Control</p>
                  <h1 data-i18n="adminManagement.title">Admin Management</h1>
                  <p data-i18n="adminManagement.subtitle">Create and manage BackOffice administrator accounts.</p>
                </div>
                <span class="uc-badge uc-role-badge">
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

          <div class="admin-tool-grid two-col">
            <div>
              <div class="admin-management-card">
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

                    <button type="submit" class="uc-primary-btn w-100">
                      <i class="mdi mdi-account-plus"></i> <span data-i18n="adminManagement.action.create">Create Account</span>
                    </button>
                  </form>
                </div>
              </div>
            </div>

            <div>
              <div class="admin-management-card">
                <div class="card-body">
                  <h4 class="card-title mb-3" data-i18n="adminManagement.table.title">Managed Accounts</h4>
                  <div class="uc-table-wrap">
                    <table class="uc-table uc-admin-management-table align-middle">
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
                        <?php if ($adminAccountsTotal === 0): ?>
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
                          <tr data-admin-account-row>
                            <td><?php echo (int)$account['id']; ?></td>
                            <td><?php echo htmlspecialchars($account['nom'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($account['email'] ?? ''); ?></td>
                            <td>
                              <span class="uc-badge uc-role-badge admin-management-badge"><span data-i18n="<?php echo htmlspecialchars(admin_management_role_i18n_key($targetRole)); ?>"><?php echo htmlspecialchars(admin_management_role_label($targetRole)); ?></span></span>
                            </td>
                            <td>
                              <span class="uc-badge <?php echo $isSuspended ? 'uc-status-bloque' : 'uc-status-actif'; ?> admin-management-badge">
                                <span data-i18n="<?php echo $isSuspended ? 'adminManagement.status.blocked' : 'adminManagement.status.active'; ?>"><?php echo $isSuspended ? 'Blocked' : 'Active'; ?></span>
                              </span>
                            </td>
                            <td>
                              <div class="admin-management-actions">
                                <?php if ($canBlock): ?>
                                  <form method="POST" class="m-0">
                                    <input type="hidden" name="id" value="<?php echo (int)$account['id']; ?>">
                                    <input type="hidden" name="action" value="<?php echo $isSuspended ? 'activate' : 'block'; ?>">
                                    <button type="submit" class="uc-action-btn <?php echo $isSuspended ? 'uc-action-success' : 'uc-action-warning'; ?>">
                                      <span data-i18n="<?php echo $isSuspended ? 'common.activate' : 'adminManagement.action.block'; ?>"><?php echo $isSuspended ? 'Activate' : 'Block'; ?></span>
                                    </button>
                                  </form>
                                <?php endif; ?>

                                <?php if ($canDelete): ?>
                                  <form method="POST" class="m-0" onsubmit="return confirm(window.cre8BackText ? window.cre8BackText('adminManagement.confirm.delete') : 'Delete this administrator account permanently?');">
                                    <input type="hidden" name="id" value="<?php echo (int)$account['id']; ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="uc-action-btn uc-action-danger" data-i18n="common.delete">Delete</button>
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
                  <?php if ($adminAccountsTotal > 0): ?>
                    <div class="uc-pagination" data-admin-pagination data-initial-page="<?php echo (int)$adminAccountsPage; ?>" data-initial-per-page="<?php echo (int)$adminAccountsPerPage; ?>" data-total="<?php echo (int)$adminAccountsTotal; ?>">
                      <p>
                        <span data-i18n="adminManagement.pagination.showing">Showing</span>
                        <span data-admin-page-range><?php echo (int)($adminAccountsOffset + 1); ?>-<?php echo (int)min($adminAccountsOffset + $adminAccountsPerPage, $adminAccountsTotal); ?></span>
                        / <span data-admin-total><?php echo (int)$adminAccountsTotal; ?></span>
                      </p>
                      <div class="admin-pagination-size">
                        <label>
                          <span data-i18n="adminManagement.pagination.perPage">Per page</span>
                          <select name="per_page" data-admin-per-page>
                            <?php foreach ($adminAccountsPerPageOptions as $perPageOption): ?>
                              <option value="<?php echo (int)$perPageOption; ?>" <?php echo $perPageOption === $adminAccountsPerPage ? 'selected' : ''; ?>><?php echo (int)$perPageOption; ?></option>
                            <?php endforeach; ?>
                          </select>
                        </label>
                      </div>
                      <nav aria-label="Admin account pagination" data-admin-page-nav>
                        <a class="uc-page-btn <?php echo $adminAccountsPage <= 1 ? 'is-disabled' : ''; ?>" href="<?php echo htmlspecialchars(admin_management_page_url($adminAccountsPage - 1, $adminAccountsPerPage)); ?>" data-i18n="common.previous">Previous</a>
                        <?php
                          $startPage = max(1, $adminAccountsPage - 2);
                          $endPage = min($adminAccountsTotalPages, $adminAccountsPage + 2);
                          for ($pageNumber = $startPage; $pageNumber <= $endPage; $pageNumber++):
                        ?>
                          <a class="uc-page-btn <?php echo $pageNumber === $adminAccountsPage ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars(admin_management_page_url($pageNumber, $adminAccountsPerPage)); ?>"><?php echo (int)$pageNumber; ?></a>
                        <?php endfor; ?>
                        <a class="uc-page-btn <?php echo $adminAccountsPage >= $adminAccountsTotalPages ? 'is-disabled' : ''; ?>" href="<?php echo htmlspecialchars(admin_management_page_url($adminAccountsPage + 1, $adminAccountsPerPage)); ?>" data-i18n="common.next">Next</a>
                      </nav>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        </div>

        <?php require __DIR__ . '/../layout/footer.php'; ?>
      </div>
    </div>
  </div>


<script>
window.cre8BackRegisterTranslations && window.cre8BackRegisterTranslations({
  en: {
    'adminManagement.kicker': 'Access Control',
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
    'adminManagement.pagination.showing': 'Showing',
    'adminManagement.pagination.perPage': 'Per page',
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
    'adminManagement.kicker': 'Controle des acces',
    'adminManagement.title': 'Gestion des admins',
    'adminManagement.subtitle': 'Creez et gerez les comptes administrateurs du BackOffice.',
    'adminManagement.role.hyperAdmin': 'Hyper Admin',
    'adminManagement.role.superAdmin': 'Super Admin',
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
    'adminManagement.pagination.showing': 'Affichage',
    'adminManagement.pagination.perPage': 'Par page',
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
<script>
(function () {
  var pagination = document.querySelector('[data-admin-pagination]');
  var rows = Array.prototype.slice.call(document.querySelectorAll('[data-admin-account-row]'));
  if (!pagination || !rows.length) {
    return;
  }

  var select = pagination.querySelector('[data-admin-per-page]');
  var range = pagination.querySelector('[data-admin-page-range]');
  var totalNode = pagination.querySelector('[data-admin-total]');
  var nav = pagination.querySelector('[data-admin-page-nav]');
  var total = rows.length;
  var currentPage = Math.max(1, parseInt(pagination.getAttribute('data-initial-page') || '1', 10) || 1);
  var perPage = Math.max(1, parseInt((select && select.value) || pagination.getAttribute('data-initial-per-page') || '10', 10) || 10);

  function translate(key, fallback) {
    return window.cre8BackText ? window.cre8BackText(key) : fallback;
  }

  function makeButton(label, page, className, disabled) {
    var button = document.createElement('button');
    button.type = 'button';
    button.className = className || 'uc-page-btn';
    button.textContent = label;
    button.dataset.adminPage = String(page);
    if (disabled) {
      button.classList.add('is-disabled');
      button.disabled = true;
    }
    return button;
  }

  function setUrlState() {
    if (!window.history || !window.history.replaceState) {
      return;
    }
    var url = new URL(window.location.href);
    url.searchParams.set('page', String(currentPage));
    url.searchParams.set('per_page', String(perPage));
    window.history.replaceState({}, '', url.toString());
  }

  function render() {
    var totalPages = Math.max(1, Math.ceil(total / perPage));
    currentPage = Math.min(Math.max(1, currentPage), totalPages);
    var start = (currentPage - 1) * perPage;
    var end = Math.min(start + perPage, total);

    rows.forEach(function (row, index) {
      row.hidden = index < start || index >= end;
    });

    if (range) {
      range.textContent = total ? String(start + 1) + '-' + String(end) : '0-0';
    }
    if (totalNode) {
      totalNode.textContent = String(total);
    }

    if (nav) {
      nav.innerHTML = '';
      nav.appendChild(makeButton(translate('common.previous', 'Previous'), currentPage - 1, 'uc-page-btn', currentPage <= 1));

      var windowStart = Math.max(1, currentPage - 2);
      var windowEnd = Math.min(totalPages, currentPage + 2);
      if (windowStart > 1) {
        nav.appendChild(makeButton('1', 1, 'uc-page-btn', false));
        if (windowStart > 2) {
          var ellipsisStart = document.createElement('span');
          ellipsisStart.className = 'uc-page-ellipsis';
          ellipsisStart.textContent = '...';
          nav.appendChild(ellipsisStart);
        }
      }
      for (var page = windowStart; page <= windowEnd; page += 1) {
        var pageButton = makeButton(String(page), page, 'uc-page-btn', false);
        if (page === currentPage) {
          pageButton.classList.add('is-active');
          pageButton.setAttribute('aria-current', 'page');
        }
        nav.appendChild(pageButton);
      }
      if (windowEnd < totalPages) {
        if (windowEnd < totalPages - 1) {
          var ellipsisEnd = document.createElement('span');
          ellipsisEnd.className = 'uc-page-ellipsis';
          ellipsisEnd.textContent = '...';
          nav.appendChild(ellipsisEnd);
        }
        nav.appendChild(makeButton(String(totalPages), totalPages, 'uc-page-btn', false));
      }

      nav.appendChild(makeButton(translate('common.next', 'Next'), currentPage + 1, 'uc-page-btn', currentPage >= totalPages));
    }

    setUrlState();
  }

  if (select) {
    select.addEventListener('change', function () {
      perPage = Math.max(1, parseInt(select.value || '10', 10) || 10);
      currentPage = 1;
      render();
    });
  }

  if (nav) {
    nav.addEventListener('click', function (event) {
      var button = event.target.closest('[data-admin-page]');
      if (!button || button.disabled) {
        return;
      }
      event.preventDefault();
      currentPage = Math.max(1, parseInt(button.dataset.adminPage || '1', 10) || 1);
      render();
    });
  }

  window.addEventListener('cre8:languagechange', render);
  render();
}());
</script>
  <script src="assets/vendors/js/vendor.bundle.base.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../layout/back-layout.js?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../layout/back-layout.js')); ?>"></script>
</body>
</html>
