<?php
require_once '../../../Controleur/postC.php';

header('Content-Type: application/json; charset=UTF-8');

if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'XMLHttpRequest') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$input = json_decode(file_get_contents('php://input') ?: '', true);
$message = trim((string)($input['message'] ?? ''));
$posts = is_array($input['posts'] ?? null) ? $input['posts'] : [];

$postC = new PostC();
$result = $postC->runCreaAssistant($message, $posts);

$result['success'] = true;
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
