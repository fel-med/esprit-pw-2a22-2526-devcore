<?php
require_once '../../../Controleur/session_helper.php';
require_once '../../../Controleur/utilisateurC.php';
require_once '../../../Controleur/adminAuditC.php';

$actorId = cc_require_admin('../../FrontOffice/utilisateur/login.php');
$actorRole = cc_current_user_role();

function cre8_user_delete_redirect(string $message = '', string $type = 'error'): void
{
    $location = 'index.php';
    if ($message !== '') {
        $param = $type === 'success' ? 'success' : 'error';
        $location .= '?' . $param . '=' . urlencode($message);
    }
    header('Location: ' . $location);
    exit;
}

if (isset($_GET['id'])) {
    $userC = new UtilisateurC();
    $targetId = (int) $_GET['id'];
    $targetUser = $targetId > 0 ? $userC->getUserById($targetId) : null;

    if (!$targetUser) {
        cre8_user_delete_redirect('Target account was not found.');
    }

    if (!cc_can_manage_user($actorId, $actorRole, $targetUser, 'delete')) {
        cre8_user_delete_redirect('You are not allowed to perform this action.');
    }

    $result = $userC->softDeleteUserById($targetId, $actorId, $actorRole, 'Deleted from BackOffice user management');
    cre8_user_delete_redirect(
        (string)($result['message'] ?? (!empty($result['success']) ? 'Account deleted successfully.' : 'Unable to delete this account.')),
        !empty($result['success']) ? 'success' : 'error'
    );
}

header("Location: index.php");
exit;
