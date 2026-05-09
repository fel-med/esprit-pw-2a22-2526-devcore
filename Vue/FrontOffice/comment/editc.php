<?php
require_once '../../../Controleur/session_helper.php';
cc_start_session();
require_once '../../../Controleur/commentC.php';
require_once '../../../Modele/comment.php';

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
    comment_json_response(['success' => false, 'message' => 'Invalid request method']);
}

$commentId = trim($_POST['id'] ?? '');
$postId = trim($_POST['postId'] ?? '');
$text = trim($_POST['text'] ?? '');
$sticker = trim($_POST['sticker'] ?? '') ?: null;
$removeImage = !empty($_POST['removeImage']);

if ($commentId === '' || $postId === '') {
    comment_json_response(['success' => false, 'message' => 'Missing identifiers.']);
}

$commentC = new CommentC();

if (!$commentC->userOwnsComment($commentId, $idUser)) {
    comment_json_response(['success' => false, 'message' => 'You cannot edit this comment.']);
}

$current = $commentC->showComment($commentId);
if (!$current) {
    comment_json_response(['success' => false, 'message' => 'Comment not found.']);
}

$newImage = $commentC->handleCommentImage('image');
$finalImage = $current['image'] ?? null;

if ($removeImage && !empty($finalImage)) {
    $commentC->removeStoredImage($finalImage);
    $finalImage = null;
}

if ($newImage !== null) {
    if (!empty($finalImage)) {
        $commentC->removeStoredImage($finalImage);
    }
    $finalImage = $newImage;
}

if ($text === '' && $sticker === null && $finalImage === null) {
    comment_json_response(['success' => false, 'message' => 'A comment cannot be fully empty.']);
}

$comment = new Comment();
$comment->setId($commentId);
$comment->setIdPost($current['idPost'] ?? null);
$comment->setIdComment($current['idComment'] ?? null);
$comment->setIdUser((string)$idUser);
$comment->setText($text);
$comment->setSticker($sticker);
$comment->setImage($finalImage);
$comment->setNumberOfLike((int)($current['numberOfLike'] ?? 0));
$comment->setNumberOfDislike((int)($current['numberOfDislike'] ?? 0));

$success = $commentC->updateComment($comment);

comment_json_response([
    'success' => $success,
    'postId' => $postId,
    'message' => $success ? 'Comment updated.' : 'Unable to update comment.'
]);