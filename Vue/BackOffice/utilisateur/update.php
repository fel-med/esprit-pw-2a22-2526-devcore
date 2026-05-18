<?php
require_once '../../../Controleur/session_helper.php';
require_once '../../../Controleur/utilisateurC.php';
require_once '../../../Controleur/adminAuditC.php';

$actorId = cc_require_admin('../../FrontOffice/utilisateur/login.php');
$actorRole = cc_current_user_role();

$userC = new UtilisateurC();

if (isset($_POST['id'])) {
    $targetId = (int)$_POST['id'];
    $role = strtolower(trim($_POST['role'] ?? ''));
    if (!in_array($role, ['createur', 'marque', 'admin', 'super_admin'], true)) {
        header("Location: index.php");
        exit;
    }

    $targetUser = $userC->getUserById($targetId);
    if (!$targetUser) {
        header("Location: index.php");
        exit;
    }

    $targetRole = strtolower(trim((string)($targetUser['role'] ?? '')));
    $roleChanged = $role !== $targetRole;

    if ($targetRole === 'hyper_admin' || $role === 'hyper_admin') {
        header("Location: index.php");
        exit;
    }

    $targetIsBackOffice = cc_is_backoffice_role($targetRole);
    if (($roleChanged || $targetIsBackOffice) && !cc_can_manage_user($actorId, $actorRole, $targetUser, 'edit_role')) {
        header("Location: index.php");
        exit;
    }

    $assignableRoles = match ($actorRole) {
        'super_admin' => ['createur', 'marque', 'admin'],
        'hyper_admin' => ['createur', 'marque', 'admin', 'super_admin'],
        default => ['createur', 'marque'],
    };

    if ($roleChanged && !in_array($role, $assignableRoles, true)) {
        header("Location: index.php");
        exit;
    }

    $userC->updateUser(
        $targetId,
        $_POST['nom'],
        $_POST['email'],
        $role
    );

    if ($roleChanged) {
        cc_log_admin_action(
            $actorId,
            $actorRole,
            'role_change',
            $targetId,
            $role,
            $targetRole,
            $role,
            'Role changed from ' . $targetRole . ' to ' . $role
        );
    }
}

header("Location: index.php");
exit;
