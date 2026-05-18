<?php
session_start();

require_once '../../../Controleur/session_helper.php';
require_once '../../../Controleur/reclamationC.php';
require_once '../../../Controleur/utilisateurC.php';
require_once '../../../Controleur/adminAuditC.php';

$actorId = cc_require_admin('../../FrontOffice/utilisateur/login.php');
$actorRole = cc_current_user_role();

function cc_reclamation_reactivate_redirect(string $messageKey): void
{
    header('Location: reclamations.php?' . $messageKey . '=1');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    cc_reclamation_reactivate_redirect('error');
}

$idReclamation = isset($_POST['idReclamation']) && is_numeric($_POST['idReclamation'])
    ? (int)$_POST['idReclamation']
    : 0;

if ($idReclamation <= 0) {
    cc_reclamation_reactivate_redirect('error');
}

$reclamationC = new ReclamationC();
$userC = new UtilisateurC();

try {
    $complaint = $reclamationC->getReclamationWithUserById($idReclamation);
    if (!$complaint) {
        cc_reclamation_reactivate_redirect('error');
    }

    $complainantRole = cc_normalize_role($complaint['complainant_role'] ?? '');
    $complainantId = (int)($complaint['complainant_id'] ?? 0);

    if (!cc_can_view_reclamation_from_role($actorRole, $complainantRole)) {
        cc_reclamation_reactivate_redirect('forbidden');
    }

    $targetUser = $userC->getUserById($complainantId);
    if (!$targetUser) {
        cc_reclamation_reactivate_redirect('error');
    }

    $targetUser['role'] = cc_normalize_role($targetUser['role'] ?? '');
    $targetUser['statut'] = cc_normalize_status($targetUser['statut'] ?? '');
    $targetUser['suspended_by_role'] = cc_normalize_role($targetUser['suspended_by_role'] ?? '');

    if ($targetUser['statut'] !== 'suspendu') {
        cc_reclamation_reactivate_redirect('error');
    }

    if (!cc_can_reactivate_suspension($actorId, $actorRole, $targetUser)) {
        cc_reclamation_reactivate_redirect('forbidden');
    }

    $userC->reactivateUserAndClearSuspension($complainantId);
    $updatedUser = $userC->getUserById($complainantId);
    if (!$updatedUser || cc_normalize_status($updatedUser['statut'] ?? '') !== 'actif') {
        cc_reclamation_reactivate_redirect('error');
    }

    cc_log_admin_action(
        $actorId,
        $actorRole,
        'reactivate_user',
        $complainantId,
        $targetUser['role'],
        'suspendu',
        'actif',
        'Reactivated from suspension appeal complaint #' . $idReclamation
    );

    $reclamationC->updateStatut($idReclamation, 'traitee');
    cc_reclamation_reactivate_redirect('success');
} catch (Throwable $e) {
    error_log('Suspension appeal reactivation failed: ' . $e->getMessage());
    cc_reclamation_reactivate_redirect('error');
}
