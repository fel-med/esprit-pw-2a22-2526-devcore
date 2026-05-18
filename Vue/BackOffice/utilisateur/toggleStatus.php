<?php
require_once '../../../Controleur/session_helper.php';
require_once '../../../Controleur/utilisateurC.php';
require_once '../../../Controleur/adminAuditC.php';

$actorId = cc_require_admin('../../FrontOffice/utilisateur/login.php');
$actorRole = cc_current_user_role();

if (!isset($_GET['id'], $_GET['newStatus'])) {
    cc_json_response(["success" => false, "message" => "Missing parameters"], 400);
}

$id = (int) $_GET['id'];
$newStatus = strtolower(trim((string) $_GET['newStatus']));
$requestReason = trim((string)($_GET['reason'] ?? $_POST['reason'] ?? ''));

if ($id <= 0) {
    cc_json_response(["success" => false, "message" => "Invalid user"], 400);
}

if (!in_array($newStatus, ['actif', 'suspendu'], true)) {
    cc_json_response(["success" => false, "message" => "Invalid status"], 400);
}

$userC = new UtilisateurC();

try {
    $targetUser = $userC->getUserById($id);

    if (!$targetUser) {
        cc_json_response(["success" => false, "message" => "User not found"], 404);
    }

    $oldStatus = strtolower(trim((string)($targetUser['statut'] ?? '')));
    if (in_array($oldStatus, ['bloque', 'en_attente'], true)) {
        cc_json_response(["success" => false, "message" => "This status cannot be changed from this endpoint"], 403);
    }

    if ($newStatus === 'suspendu') {
        if ($oldStatus !== 'actif') {
            cc_json_response(["success" => false, "message" => "Only active users can be suspended"], 400);
        }

        if (!cc_can_manage_user($actorId, $actorRole, $targetUser, 'suspend')) {
            cc_json_response(["success" => false, "message" => "You are not allowed to perform this action"], 403);
        }

        $reason = $requestReason !== '' ? $requestReason : 'Suspended from BackOffice user management';
        $userC->suspendUserWithMetadata($id, $actorId, $actorRole, $reason);
        cc_log_admin_action($actorId, $actorRole, 'suspend_user', $id, $targetUser['role'] ?? null, $oldStatus, 'suspendu', $reason);

        cc_json_response(["success" => true, "message" => "Status updated successfully"]);
    }

    if ($oldStatus !== 'suspendu') {
        cc_json_response(["success" => false, "message" => "Only suspended users can be reactivated"], 400);
    }

    if (!cc_can_manage_user($actorId, $actorRole, $targetUser, 'reactivate') || !cc_can_reactivate_suspension($actorId, $actorRole, $targetUser)) {
        cc_json_response(["success" => false, "message" => "You are not allowed to perform this action"], 403);
    }

    $reason = 'Reactivated from BackOffice user management';
    $userC->reactivateUserAndClearSuspension($id);
    cc_log_admin_action($actorId, $actorRole, 'reactivate_user', $id, $targetUser['role'] ?? null, $oldStatus, 'actif', $reason);

    cc_json_response(["success" => true, "message" => "Status updated successfully"]);
} catch (Exception $e) {
    cc_json_response(["success" => false, "message" => "Unable to update status"], 500);
}
