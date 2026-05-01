<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../Controleur/condidatureC.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $controller = new CondidatureC();
    $rawInput = file_get_contents('php://input');
    $payload = [];

    if ($rawInput !== false && trim($rawInput) !== '') {
        $decoded = json_decode($rawInput, true);
        if (is_array($decoded)) {
            $payload = $decoded;
        }
    }

    if (empty($payload) && !empty($_POST)) {
        $payload = $_POST;
    }

    $sessionUser = $_SESSION['utilisateur'] ?? [];
    $response = $controller->handleCre8PilotMockRequest($payload, is_array($sessionUser) ? $sessionUser : []);

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'intent' => 'error',
        'message' => 'Cre8Pilot could not process the request right now.',
        'confidence' => 0,
        'avatarState' => 'error',
        'clarification' => null,
        'actions' => [],
        'needsUserConfirmation' => false,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
