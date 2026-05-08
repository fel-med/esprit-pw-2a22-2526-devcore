<?php
if (function_exists('ini_set')) {
    ini_set('display_errors', '0');
}
if (function_exists('ob_start')) {
    ob_start();
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../Controleur/condidatureC.php';

header('Content-Type: application/json; charset=utf-8');

if (!function_exists('cre8pilot_endpoint_emit_json')) {
    function cre8pilot_endpoint_emit_json(array $response, int $httpCode = 200): void
    {
        http_response_code($httpCode);
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        while (function_exists('ob_get_level') && ob_get_level() > 0) {
            @ob_end_clean();
        }

        $json = json_encode(
            $response,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
        );
        if (!is_string($json) || $json === '') {
            $json = '{"status":"error","intent":"error","message":"Cre8Pilot could not encode the response safely.","confidence":0,"avatarState":"error","clarification":null,"actions":[],"needsUserConfirmation":false}';
        }

        echo $json;
    }
}

try {
    $controller = new CondidatureC();
    $sessionUser = $_SESSION['utilisateur'] ?? [];
    $sessionUser = is_array($sessionUser) ? $sessionUser : [];

    if ((string) ($_POST['action'] ?? '') === 'document_upload' && !empty($_FILES['file']) && is_array($_FILES['file'])) {
        $response = $controller->handleCre8PilotDocumentUpload($_POST, $_FILES, $sessionUser);
        cre8pilot_endpoint_emit_json($response);

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

    cre8pilot_endpoint_emit_json($response);
} catch (Throwable $exception) {
    cre8pilot_endpoint_emit_json([
        'status' => 'error',
        'intent' => 'error',
        'message' => 'Cre8Pilot could not process the request right now.',
        'confidence' => 0,
        'avatarState' => 'error',
        'clarification' => null,
        'actions' => [],
        'needsUserConfirmation' => false,
    ], 500);
}
