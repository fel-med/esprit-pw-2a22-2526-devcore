<?php
require_once '../../../Controleur/postC.php';

header('Content-Type: application/json');

function ai_json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'xmlhttprequest') {
    ai_json_response(['success' => false, 'message' => 'AJAX request required.'], 400);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ai_json_response(['success' => false, 'message' => 'Invalid request method.'], 405);
}

$brief = trim($_POST['brief'] ?? '');
$style = trim($_POST['style'] ?? '');
$mode = trim($_POST['mode'] ?? 'generate');
$currentContent = trim($_POST['currentContent'] ?? '');
$existingImagePath = trim($_POST['existingImagePath'] ?? '');
$sentenceCountRaw = (int)($_POST['sentenceCount'] ?? 4);
$sentenceCount = max(1, min(12, $sentenceCountRaw));

try {
    $postC = new PostC();
    $content = $postC->generatePostContentWithAi(
        $brief,
        $style,
        $sentenceCount,
        $currentContent,
        $mode,
        $existingImagePath !== '' ? $existingImagePath : null
    );

    ai_json_response([
        'success' => true,
        'content' => $content,
        'message' => $mode === 'enhance' ? 'Content enhanced successfully.' : 'Content generated successfully.'
    ]);
} catch (Throwable $e) {
    ai_json_response([
        'success' => false,
        'message' => $e->getMessage()
    ], 500);
}
