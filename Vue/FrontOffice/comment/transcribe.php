<?php
require_once '../../../Controleur/commentC.php';
header('Content-Type: application/json');

function json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Invalid request method.'], 405);
}
if (empty($_FILES['audio']) || (($_FILES['audio']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK)) {
    json_response(['success' => false, 'message' => 'Audio file is required.'], 400);
}
$tmpPath = $_FILES['audio']['tmp_name'] ?? '';
if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
    json_response(['success' => false, 'message' => 'Uploaded audio is invalid.'], 400);
}
$language = trim((string) ($_POST['language'] ?? 'en'));
$originalName = basename((string) ($_FILES['audio']['name'] ?? 'comment.webm'));
$commentC = new CommentC();
json_response($commentC->transcribeAudioWithGroq($tmpPath, $originalName, $language));
