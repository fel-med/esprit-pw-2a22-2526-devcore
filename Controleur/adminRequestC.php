<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Modele/adminRequest.php';
require_once __DIR__ . '/session_helper.php';
require_once __DIR__ . '/notificationC.php';

class AdminRequestC
{
    private const REQUEST_TYPES = [
        'reactivate_account',
        'add_admin',
        'add_super_admin',
        'delete_user',
        'security_review',
        'server_action',
        'other',
    ];

    private const RECEIVER_SCOPES = [
        'super_admins',
        'hyper_admins',
        'specific_admin',
    ];

    private const STATUSES = [
        'pending',
        'approved',
        'refused',
        'done',
        'cancelled',
    ];

    public function allowedRequestTypes(): array
    {
        return self::REQUEST_TYPES;
    }

    public function allowedStatuses(): array
    {
        return self::STATUSES;
    }

    public function createRequest($senderId, $senderRole, $receiverScope, $receiverId, $requestType, $targetUserId, $title, $message): int
    {
        $senderId = (int)$senderId;
        $senderRole = strtolower(trim((string)$senderRole));
        $receiverScope = strtolower(trim((string)$receiverScope));
        $requestType = strtolower(trim((string)$requestType));
        $title = trim((string)$title);
        $message = trim((string)$message);
        $receiverId = is_numeric($receiverId) && (int)$receiverId > 0 ? (int)$receiverId : null;
        $targetUserId = is_numeric($targetUserId) && (int)$targetUserId > 0 ? (int)$targetUserId : null;

        if ($senderId <= 0 || $title === '' || $message === '') {
            throw new InvalidArgumentException('Missing required request fields.');
        }

        if (!in_array($receiverScope, self::RECEIVER_SCOPES, true)) {
            throw new InvalidArgumentException('Invalid receiver scope.');
        }

        if (!in_array($requestType, self::REQUEST_TYPES, true)) {
            throw new InvalidArgumentException('Invalid request type.');
        }

        if (!cc_can_create_admin_request($senderRole, $receiverScope)) {
            throw new RuntimeException('You are not allowed to create this request.');
        }

        $db = config::getConnexion();
        $stmt = $db->prepare("
            INSERT INTO admin_requests
                (sender_id, sender_role, receiver_scope, receiver_id, request_type, target_user_id, title, message, status, created_at)
            VALUES
                (:sender_id, :sender_role, :receiver_scope, :receiver_id, :request_type, :target_user_id, :title, :message, 'pending', NOW())
        ");
        $stmt->execute([
            ':sender_id' => $senderId,
            ':sender_role' => $senderRole,
            ':receiver_scope' => $receiverScope,
            ':receiver_id' => $receiverId,
            ':request_type' => $requestType,
            ':target_user_id' => $targetUserId,
            ':title' => $title,
            ':message' => $message,
        ]);

        $requestId = (int)$db->lastInsertId();
        if ($requestId > 0) {
            try {
                $notificationC = new NotificationC($db);
                $notificationC->notifyAdminRequestReceived(
                    $requestId,
                    $senderId,
                    $senderRole,
                    $receiverScope,
                    $receiverId,
                    $requestType,
                    $title,
                    $message
                );
            } catch (Throwable $e) {
                error_log('Admin request received notification hook failed: ' . $e->getMessage());
            }
        }

        return $requestId;
    }

    public function listVisibleRequests($viewerId, $viewerRole, $filter = 'inbox'): array
    {
        $viewerId = is_numeric($viewerId) ? (int)$viewerId : 0;
        $viewerRole = strtolower(trim((string)$viewerRole));
        $filter = strtolower(trim((string)$filter));

        if ($viewerId <= 0 || !cc_is_backoffice_role($viewerRole)) {
            return [];
        }

        $db = config::getConnexion();

        if ($filter === 'sent') {
            $stmt = $db->prepare("SELECT * FROM admin_requests WHERE sender_id = :viewer_id ORDER BY created_at DESC, id DESC");
            $stmt->execute([':viewer_id' => $viewerId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        if ($filter === 'all' && $viewerRole === 'hyper_admin') {
            $stmt = $db->query("SELECT * FROM admin_requests ORDER BY created_at DESC, id DESC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $stmt = $db->query("SELECT * FROM admin_requests ORDER BY created_at DESC, id DESC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_values(array_filter($rows, static function (array $row) use ($viewerId, $viewerRole): bool {
            if ((int)($row['sender_id'] ?? 0) === $viewerId) {
                return false;
            }
            return cc_can_view_admin_request($viewerId, $viewerRole, $row);
        }));
    }

    public function getRequestById($id): ?array
    {
        $id = (int)$id;
        if ($id <= 0) {
            return null;
        }

        $db = config::getConnexion();
        $stmt = $db->prepare("SELECT * FROM admin_requests WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        return $request ?: null;
    }

    public function updateRequestStatus($id, $status, $responseMessage, $handledBy, $handledByRole): bool
    {
        $id = (int)$id;
        $status = strtolower(trim((string)$status));

        if ($id <= 0 || !in_array($status, self::STATUSES, true) || $status === 'pending') {
            return false;
        }

        $db = config::getConnexion();
        $request = $this->getRequestById($id);
        $stmt = $db->prepare("
            UPDATE admin_requests
            SET status = :status,
                response_message = :response_message,
                handled_by = :handled_by,
                handled_by_role = :handled_by_role,
                handled_at = NOW()
            WHERE id = :id
              AND status = 'pending'
        ");
        $stmt->execute([
            ':status' => $status,
            ':response_message' => trim((string)$responseMessage),
            ':handled_by' => is_numeric($handledBy) ? (int)$handledBy : null,
            ':handled_by_role' => strtolower(trim((string)$handledByRole)),
            ':id' => $id,
        ]);

        $updated = $stmt->rowCount() > 0;
        if ($updated && $request !== null && in_array($status, ['approved', 'refused', 'done'], true)) {
            try {
                $notificationC = new NotificationC($db);
                $notificationC->notifyAdminRequestStatusUpdated(
                    $id,
                    $request['sender_id'] ?? null,
                    $handledBy,
                    $handledByRole,
                    $status,
                    $request['title'] ?? '',
                    $responseMessage
                );
            } catch (Throwable $e) {
                error_log('Admin request status notification hook failed: ' . $e->getMessage());
            }
        }

        return $updated;
    }

    public function cancelRequest($id, $senderId): bool
    {
        $id = (int)$id;
        $senderId = (int)$senderId;
        if ($id <= 0 || $senderId <= 0) {
            return false;
        }

        $db = config::getConnexion();
        $stmt = $db->prepare("
            UPDATE admin_requests
            SET status = 'cancelled',
                handled_at = NOW()
            WHERE id = :id
              AND sender_id = :sender_id
              AND status = 'pending'
        ");
        $stmt->execute([
            ':id' => $id,
            ':sender_id' => $senderId,
        ]);

        return $stmt->rowCount() > 0;
    }
}
