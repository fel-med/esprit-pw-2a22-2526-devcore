<?php
require_once __DIR__ . '/session_bridge.php';
require_once __DIR__ . '/../../../Controleur/notificationC.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $currentUser = cre8_front_session_user();
    $userId = (int) ($currentUser['id'] ?? 0);

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
        exit;
    }

    if ($userId <= 0 || empty($currentUser['isLoggedIn'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'You must be logged in.']);
        exit;
    }

    $action = (string) ($_POST['notificationAction'] ?? '');
    $controller = new NotificationC();

    if ($action === 'mark_visible') {
        $ids = $_POST['notificationIds'] ?? [];
        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn($id) => $id > 0)));
        $success = $controller->markAsRead($userId, $ids);
        echo json_encode([
            'success' => (bool) $success,
            'unreadCount' => $controller->countUnread($userId),
        ]);
        exit;
    }

    if ($action === 'mark_one') {
        $notificationId = (int) ($_POST['notificationId'] ?? ($_POST['idNotificationAction'] ?? 0));
        if ($notificationId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing notification id.']);
            exit;
        }

        $success = $controller->markAsRead($userId, [$notificationId]);
        echo json_encode([
            'success' => (bool) $success,
            'unreadCount' => $controller->countUnread($userId),
        ]);
        exit;
    }

    if ($action === 'mark_all') {
        $success = $controller->markAllAsRead($userId);
        echo json_encode([
            'success' => (bool) $success,
            'unreadCount' => $controller->countUnread($userId),
        ]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Unknown notification action.']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Notification update failed.']);
}
