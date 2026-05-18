<?php
require_once '../../../Controleur/session_helper.php';
require_once '../../../Controleur/utilisateurC.php';
require_once '../../../Controleur/adminAuditC.php';

$actorId = cc_require_admin('../../FrontOffice/utilisateur/login.php');
$actorRole = cc_normalize_role(cc_current_user_role());

$userC = new UtilisateurC();

if (isset($_POST['id'])) {
    if ($actorRole === 'admin' || cc_admin_role_power($actorRole) === 1) {
        header("Location: index.php?error=forbidden");
        exit;
    }

    $targetId = (int)$_POST['id'];

    $targetUser = $userC->getUserById($targetId);
    if (!$targetUser) {
        header("Location: index.php");
        exit;
    }

    $targetRole = cc_normalize_role($targetUser['role'] ?? '');
    $role = array_key_exists('role', $_POST) ? cc_normalize_role($_POST['role']) : $targetRole;
    if ($role === '') {
        $role = $targetRole;
    }
    $roleChanged = $role !== $targetRole;
    $targetUserForPermission = array_merge($targetUser, [
        'id' => (int)($targetUser['id'] ?? $targetId),
        'role' => $targetRole,
        'statut' => cc_normalize_status($targetUser['statut'] ?? ''),
    ]);

    if (!in_array($role, ['createur', 'marque', 'admin', 'super_admin'], true)) {
        header("Location: index.php");
        exit;
    }

    if ($targetRole === 'hyper_admin' || $role === 'hyper_admin' || (int)$targetId === (int)$actorId) {
        header("Location: index.php");
        exit;
    }

    $canEditRole = cc_can_manage_user($actorId, $actorRole, $targetUserForPermission, 'edit_role');
    $canEditProfile = $canEditRole;

    if (!$canEditProfile) {
        header("Location: index.php");
        exit;
    }

    $assignableRoles = match (cc_normalize_role($actorRole)) {
        'super_admin' => ['createur', 'marque', 'admin'],
        'hyper_admin' => ['createur', 'marque', 'admin', 'super_admin'],
        default => [],
    };

    if ($roleChanged && (!$canEditRole || $actorRole === 'admin' || !in_array($role, $assignableRoles, true))) {
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
