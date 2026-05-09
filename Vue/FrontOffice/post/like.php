<?php
require_once '../../../Controleur/session_helper.php';
cc_start_session();
require_once '../../../Controleur/postC.php';

header('Content-Type: application/json; charset=utf-8');
cc_require_login('../utilisateur/login.php');

$postC = new PostC();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing post ID.']);
    exit();
}

$postId = $_GET['id'];

// Initialiser le tableau des votes en session
if (!isset($_SESSION['votes'])) {
    $_SESSION['votes'] = [];
}

$existingVote = $_SESSION['votes'][$postId] ?? null;

if ($existingVote === 'like') {
    // Déjà liké → annuler
    $postC->decrementLike($postId);
    unset($_SESSION['votes'][$postId]);
    $userVote = null;

} elseif ($existingVote === 'dislike') {
    // Avait disliké → basculer vers like
    $postC->decrementDislike($postId);
    $postC->incrementLike($postId);
    $_SESSION['votes'][$postId] = 'like';
    $userVote = 'like';

} else {
    // Aucun vote → liker
    $postC->incrementLike($postId);
    $_SESSION['votes'][$postId] = 'like';
    $userVote = 'like';
}

echo json_encode([
    'success'  => true,
    'postId'   => $postId,
    'likes'    => $postC->getLikeCount($postId),
    'dislikes' => $postC->getDislikeCount($postId),
    'userVote' => $userVote
]);
exit();