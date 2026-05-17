<?php
require_once '../../../Controleur/session_helper.php';
cc_start_session();
require_once '../../../Controleur/commentC.php';
require_once '../../../Controleur/postC.php';
require_once '../../../Controleur/notificationC.php';
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

function cre8_post_details_link_for_notification(string $postId): string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $base = '';
    if (($pos = strpos($script, '/Vue/')) !== false) {
        $base = substr($script, 0, $pos);
    }

    return $base . '/Vue/FrontOffice/post/details.php?id=' . rawurlencode($postId);
}

function cre8_current_actor_name(): string
{
    $name = $_SESSION['nom']
        ?? ($_SESSION['user']['nom'] ?? null)
        ?? ($_SESSION['utilisateur']['nom'] ?? null)
        ?? ($_SESSION['user']['name'] ?? null)
        ?? ($_SESSION['utilisateur']['name'] ?? null)
        ?? '';

    $name = trim((string) $name);
    return $name !== '' ? $name : 'Someone';
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

if ($success) {
    try {
        $postC = new PostC();
        $post = $postC->showPost($postId);
        $ownerId = isset($post['idCreateur']) ? (int) $post['idCreateur'] : 0;
        $actorId = (int) $idUser;

        if ($ownerId > 0 && $actorId > 0 && $ownerId !== $actorId) {
            $commentId = (string) $comment->getId();
            $actorName = cre8_current_actor_name();
            $notificationC = new NotificationC();
            $notificationC->createNotification(
                $ownerId,
                'post_comment',
                'New comment on your post',
                $actorName . ' commented on your post.',
                cre8_post_details_link_for_notification($postId),
                'post',
                $postId,
                $actorId,
                cc_current_user_role(),
                'post_comment_' . $commentId . '_post_' . $postId . '_user_' . $ownerId,
                [
                    'comment_id' => $commentId,
                    'post_id' => $postId,
                    'actor_name' => $actorName,
                ]
            );
        }
    } catch (Throwable $e) {
        error_log('Post comment notification failed: ' . $e->getMessage());
    }
}

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
