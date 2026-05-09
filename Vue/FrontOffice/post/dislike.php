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

if (!isset($_SESSION['votes'])) {
    $_SESSION['votes'] = [];
}

$existingVote = $_SESSION['votes'][$postId] ?? null;

if ($existingVote === 'dislike') {
    // Déjà disliké → annuler
    $postC->decrementDislike($postId);
    unset($_SESSION['votes'][$postId]);
    $userVote = null;

} elseif ($existingVote === 'like') {
    // Avait liké → basculer vers dislike
    $postC->decrementLike($postId);
    $postC->incrementDislike($postId);
    $_SESSION['votes'][$postId] = 'dislike';
    $userVote = 'dislike';

} else {
    // Aucun vote → disliker
    $postC->incrementDislike($postId);
    $_SESSION['votes'][$postId] = 'dislike';
    $userVote = 'dislike';
}

echo json_encode([
    'success'  => true,
    'postId'   => $postId,
    'likes'    => $postC->getLikeCount($postId),
    'dislikes' => $postC->getDislikeCount($postId),
    'userVote' => $userVote
]);
exit();