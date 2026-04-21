<?php
require_once '../../../Controleur/commentC.php';

header('Content-Type: application/json');

$idUser = 1;
$commentId = trim($_POST['id'] ?? $_GET['id'] ?? '');
$postId = trim($_POST['postId'] ?? $_GET['postId'] ?? '');

if ($commentId === '' || $postId === '') {
    echo json_encode(['success' => false, 'message' => 'Missing identifiers.']);
    exit;
}

$commentC = new CommentC();
$success = $commentC->deleteComment($commentId, $idUser);

echo json_encode([
    'success' => $success,
    'postId' => $postId,
    'message' => $success ? 'Comment deleted.' : 'Unable to delete comment.',
]);
