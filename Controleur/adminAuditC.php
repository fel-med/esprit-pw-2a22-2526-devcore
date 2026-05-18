<?php
require_once __DIR__ . '/../config.php';

if (!function_exists('cc_log_admin_action')) {
    function cc_log_admin_action(
        $actorId,
        $actorRole,
        $actionType,
        $targetUserId = null,
        $targetRole = null,
        $oldStatus = null,
        $newStatus = null,
        $reason = null
    ): void {
        try {
            $db = config::getConnexion();
            $stmt = $db->prepare("
                INSERT INTO admin_audit_log
                    (actor_id, actor_role, action_type, target_user_id, target_role, old_status, new_status, reason, ip_address, created_at)
                VALUES
                    (:actor_id, :actor_role, :action_type, :target_user_id, :target_role, :old_status, :new_status, :reason, :ip_address, NOW())
            ");
            $stmt->execute([
                ':actor_id' => is_numeric($actorId) ? (int) $actorId : null,
                ':actor_role' => $actorRole !== null ? (string) $actorRole : null,
                ':action_type' => (string) $actionType,
                ':target_user_id' => is_numeric($targetUserId) ? (int) $targetUserId : null,
                ':target_role' => $targetRole !== null ? (string) $targetRole : null,
                ':old_status' => $oldStatus !== null ? (string) $oldStatus : null,
                ':new_status' => $newStatus !== null ? (string) $newStatus : null,
                ':reason' => $reason !== null ? (string) $reason : null,
                ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
        } catch (Throwable $e) {
            error_log('Admin audit log failed: ' . $e->getMessage());
        }
    }
}
