<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../Controleur/condidatureC.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $controller = new CondidatureC();
    $sessionUser = $_SESSION['utilisateur'] ?? [];
    $sessionUser = is_array($sessionUser) ? $sessionUser : [];

    if ((string) ($_POST['action'] ?? '') === 'document_upload' && !empty($_FILES['file']) && is_array($_FILES['file'])) {
        $response = $controller->handleCre8PilotDocumentUpload($_POST, $_FILES, $sessionUser);
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return;
    }

    $rawInput = file_get_contents('php://input');
    $payload = [];

    if ($rawInput !== false && trim($rawInput) !== '') {
        $decoded = json_decode($rawInput, true);
        if (is_array($decoded)) {
            $payload = $decoded;
        }
    }

    if (empty($payload) && !empty($_POST) && (string) ($_POST['action'] ?? '') !== 'document_upload') {
        $payload = $_POST;
    }

    $response = $controller->handleCre8PilotMockRequest($payload, $sessionUser);

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
