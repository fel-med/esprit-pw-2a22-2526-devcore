<?php
require_once '../../../Controleur/postC.php';

header('Content-Type: application/json; charset=utf-8');

$postC = new PostC();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing post ID.'
    ]);
    exit();
}

$postId = $_GET['id'];

$success = $postC->incrementLike($postId);

if (!$success) {
    echo json_encode([
        'success' => false,
        'message' => 'Unable to update like.'
    ]);
    exit();
}

echo json_encode([
    'success' => true,
    'postId' => $postId,
    'likes' => $postC->getLikeCount($postId),
    'dislikes' => $postC->getDislikeCount($postId)
]);
exit();