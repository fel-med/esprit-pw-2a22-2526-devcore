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

if (!function_exists('cc_admin_audit_columns')) {
    function cc_admin_audit_columns(PDO $pdo): array
    {
        static $columns = null;
        if ($columns !== null) {
            return $columns;
        }

        $columns = [];
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM admin_audit_log");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $field = (string)($row['Field'] ?? '');
                if ($field !== '') {
                    $columns[$field] = true;
                }
            }
        } catch (Throwable $e) {
            error_log('Admin audit column inspection failed: ' . $e->getMessage());
        }

        return $columns;
    }
}

if (!function_exists('cc_admin_audit_json')) {
    function cc_admin_audit_json($value): ?string
    {
        if ($value === null) {
            return null;
        }

        try {
            $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return is_string($json) ? $json : null;
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('cc_log_admin_entity_action')) {
    function cc_log_admin_entity_action(
        ?PDO $pdo,
        $actorId,
        $actorRole,
        $actionType,
        $targetTable,
        $targetId,
        $beforeData,
        $afterData,
        $reason = null,
        $canUndo = 1,
        $undoStatus = 'available'
    ): void {
        try {
            $pdo = $pdo ?: config::getConnexion();
            $available = cc_admin_audit_columns($pdo);
            if ($available === []) {
                return;
            }

            $values = [
                'actor_id' => is_numeric($actorId) ? (int)$actorId : null,
                'actor_role' => $actorRole !== null ? (string)$actorRole : null,
                'action_type' => (string)$actionType,
                'target_table' => (string)$targetTable,
                'target_id' => (string)$targetId,
                'reason' => $reason !== null ? (string)$reason : null,
                'can_undo' => (int)(bool)$canUndo,
                'undo_status' => (string)$undoStatus,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
            ];

            if ((string)$targetTable === 'utilisateur' && is_numeric($targetId)) {
                $values['target_user_id'] = (int)$targetId;
            }
            if (is_array($beforeData) && isset($beforeData['role'])) {
                $values['target_role'] = (string)$beforeData['role'];
            } elseif (is_array($afterData) && isset($afterData['role'])) {
                $values['target_role'] = (string)$afterData['role'];
            }
            if (is_array($beforeData) && isset($beforeData['statut'])) {
                $values['old_status'] = (string)$beforeData['statut'];
            }
            if (is_array($afterData) && isset($afterData['statut'])) {
                $values['new_status'] = (string)$afterData['statut'];
            }

            $beforeJson = cc_admin_audit_json($beforeData);
            $afterJson = cc_admin_audit_json($afterData);
            $values['old_data_json'] = $beforeJson;
            $values['new_data_json'] = $afterJson;
            $values['before_json'] = $beforeJson;
            $values['after_json'] = $afterJson;

            $columns = [];
            $placeholders = [];
            $params = [];
            foreach ($values as $column => $value) {
                if (!isset($available[$column])) {
                    continue;
                }
                $columns[] = $column;
                $placeholders[] = ':' . $column;
                $params[':' . $column] = $value;
            }

            if ($columns === []) {
                return;
            }

            $sql = 'INSERT INTO admin_audit_log (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        } catch (Throwable $e) {
            error_log('Admin entity audit log failed: ' . $e->getMessage());
        }
    }
}
