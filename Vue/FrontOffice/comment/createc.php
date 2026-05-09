<?php
require_once '../../../Controleur/session_helper.php';
cc_start_session();
require_once '../../../Controleur/commentC.php';
require_once '../../../Modele/comment.php';

// ── VÉRIFICATION SESSION ──────────────────────────────────────
$idUser = cc_require_login('../utilisateur/login.php');

function is_ajax_request(): bool
{
    return cc_is_ajax_request();
}

function comment_json_response(array $payload): void
{
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (is_ajax_request()) {
        comment_json_response([
            'success' => false,
            'message' => 'Invalid request method'
        ]);
    }

    header('Location: ../post/index.php');
    exit;
}

$postId = trim($_POST['postId'] ?? $_POST['idPost'] ?? '');
$targetId = trim($_POST['targetId'] ?? $postId);
$targetType = strtolower(trim($_POST['targetType'] ?? 'post'));
$text = trim($_POST['text'] ?? '');
$sticker = trim($_POST['sticker'] ?? '') ?: null;
$from = trim($_POST['from'] ?? 'index');

if ($postId === '' || $targetId === '') {
    if (is_ajax_request()) {
        comment_json_response([
            'success' => false,
            'message' => 'Missing target post.'
        ]);
    }

    header('Location: ../post/index.php');
    exit;
}

$commentC = new CommentC();
$image = $commentC->handleCommentImage('image');

if ($text === '' && $sticker === null && $image === null) {
    if (is_ajax_request()) {
        comment_json_response([
            'success' => false,
            'message' => 'Write something, choose a sticker, or add an image.'
        ]);
    }

    $redirect = $from === 'details'
        ? '../post/details.php?id=' . urlencode($postId) . '#comments'
        : ($from === 'portfolio' ? '../post/portfolio.php' : '../post/index.php');

    header('Location: ' . $redirect);
    exit;
}

/*
|--------------------------------------------------------------------------
| NEW FK-BASED STRUCTURE
|--------------------------------------------------------------------------
| If targetType = post    => idPost = targetId,    idComment = null
| If targetType = comment => idPost = null,        idComment = targetId
|
| We still accept the same front-office payload, so the views/JS do not
| need to change.
|--------------------------------------------------------------------------
*/
$idPostRef = null;
$idCommentRef = null;

if ($targetType === 'comment') {
    $idCommentRef = $targetId;
} else {
    $idPostRef = $targetId;
}

$comment = new Comment();
$comment->setIdPost($idPostRef);
$comment->setIdComment($idCommentRef);
$comment->setIdUser((string)$idUser);
$comment->setText($text);
$comment->setSticker($sticker);
$comment->setImage($image);
$comment->setNumberOfLike(0);
$comment->setNumberOfDislike(0);

$success = $commentC->addComment($comment);

if (is_ajax_request()) {
    comment_json_response([
        'success' => $success,
        'postId' => $postId,
        'commentId' => $comment->getId(),
        'message' => $success ? 'Comment added.' : 'Unable to add comment.',
    ]);
}

$redirect = $from === 'details'
    ? '../post/details.php?id=' . urlencode($postId) . '#comments'
    : ($from === 'portfolio' ? '../post/portfolio.php' : '../post/index.php');

header('Location: ' . $redirect);
exit;