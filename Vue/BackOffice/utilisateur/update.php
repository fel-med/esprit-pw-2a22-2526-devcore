<?php
require_once '../../../Controleur/session_helper.php';
require_once '../../../Controleur/utilisateurC.php';

cc_require_admin('../../FrontOffice/utilisateur/login.php');

$userC = new UtilisateurC();

if (isset($_POST['id'])) {
    $targetId = (int)$_POST['id'];
    $role = strtolower(trim($_POST['role'] ?? ''));
    if (!in_array($role, ['createur', 'marque', 'admin'], true)) {
        header("Location: index.php");
        exit;
    }

    $targetUser = $userC->getUserById($targetId);
    if (!$targetUser) {
        header("Location: index.php");
        exit;
    }

    $currentRole = cc_current_user_role();
    if ($targetId === cc_current_user_id() && $role !== strtolower(trim((string)($targetUser['role'] ?? '')))) {
        header("Location: index.php");
        exit;
    }

    if ($role === 'admin' && !isSuperAdminRole($currentRole)) {
        header("Location: index.php");
        exit;
    }

    $userC->updateUser(
        $targetId,
        $_POST['nom'],
        $_POST['email'],
        $role
    );
}

header("Location: index.php");
exit;
