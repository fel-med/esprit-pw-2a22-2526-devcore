<?php
require_once '../../../Controleur/commentC.php';
require_once '../../../Modele/comment.php';

$idUser = 1;

function is_ajax_request(): bool
{
    return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
}

function comment_json_response(array $payload): void
{
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (is_ajax_request()) {
        comment_json_response(['success' => false, 'message' => 'Invalid request method']);
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
        comment_json_response(['success' => false, 'message' => 'Missing target post.']);
    }
    header('Location: ../post/index.php');
    exit;
}

$commentC = new CommentC();
$image = $commentC->handleCommentImage('image');

if ($text === '' && $sticker === null && $image === null) {
    if (is_ajax_request()) {
        comment_json_response(['success' => false, 'message' => 'Write something, choose a sticker, or add an image.']);
    }
    $redirect = $from === 'details' ? '../post/details.php?id=' . urlencode($postId) . '#comments' : '../post/index.php';
    header('Location: ' . $redirect);
    exit;
}

$comment = new Comment();
$comment->setIdCommentedElement($targetId);
$comment->setIdUser($idUser);
$comment->setCommentedItem($targetType === 'comment' ? 'comment' : 'post');
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

$redirect = $from === 'details' ? '../post/details.php?id=' . urlencode($postId) . '#comments' : ($from === 'portfolio' ? '../post/portfolio.php' : '../post/index.php');
header('Location: ' . $redirect);
exit;
