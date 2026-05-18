<?php
require_once __DIR__ . '/../../../Controleur/session_helper.php';
require_once __DIR__ . '/../../../Controleur/notificationC.php';

cc_start_session();
header('Content-Type: application/json; charset=utf-8');

function bo_notifications_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$currentUserId = cc_current_user_id();
$currentRole = cc_current_user_role();

if ($currentUserId === null || !cc_is_backoffice_role($currentRole)) {
    bo_notifications_json([
        'success' => false,
        'message' => 'BackOffice session required',
    ], 401);
}

$action = strtolower(trim((string)($_POST['action'] ?? $_GET['action'] ?? 'list')));
$notificationC = new NotificationC();
$isPost = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST';

try {
    if ($action === 'list') {
        $rows = $notificationC->getNotificationsForUser($currentUserId, 10, false);
        $notifications = array_map(static function (array $row): array {
            return [
                'id' => (int)($row['idNotificationAction'] ?? 0),
                'title' => (string)($row['titre'] ?? 'Notification'),
                'message' => (string)($row['message'] ?? ''),
                'link' => (string)($row['lien'] ?? ''),
                'is_read' => !empty($row['estLu']),
                'created_at' => (string)($row['dateCreation'] ?? ''),
                'type' => (string)($row['typeAction'] ?? 'notification'),
            ];
        }, $rows);

        bo_notifications_json([
            'success' => true,
            'unread_count' => $notificationC->countUnread($currentUserId),
            'notifications' => $notifications,
        ]);
    }

    if ($action === 'mark_read') {
        if (!$isPost) {
            bo_notifications_json([
                'success' => false,
                'message' => 'Method not allowed',
            ], 405);
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            bo_notifications_json([
                'success' => false,
                'message' => 'Invalid notification',
            ], 400);
        }

        $notificationC->markAsRead($currentUserId, [$id]);

        bo_notifications_json([
            'success' => true,
            'unread_count' => $notificationC->countUnread($currentUserId),
        ]);
    }

    if ($action === 'mark_all_read') {
        if (!$isPost) {
            bo_notifications_json([
                'success' => false,
                'message' => 'Method not allowed',
            ], 405);
        }

        $notificationC->markAllAsRead($currentUserId);

        bo_notifications_json([
            'success' => true,
            'unread_count' => $notificationC->countUnread($currentUserId),
        ]);
    }

    bo_notifications_json([
        'success' => false,
        'message' => 'Unsupported action',
    ], 400);
} catch (Throwable $e) {
    error_log('BackOffice notifications API error: ' . $e->getMessage());
    bo_notifications_json([
        'success' => false,
        'message' => 'Unable to load notifications',
    ], 500);
}
