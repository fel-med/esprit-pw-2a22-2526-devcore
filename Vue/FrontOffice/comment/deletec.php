<?php
require_once '../../../Controleur/session_helper.php';
cc_start_session();
require_once '../../../Controleur/commentC.php';

function cre8_is_ajax_request(): bool {
    return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
}

function cre8_comment_json_response(bool $success, string $message = '', array $extra = []): void {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message,
    ], $extra));
    exit();
}

$isAjax = cre8_is_ajax_request();

// ── SESSION CHECK ──────────────────────────────────────────────
$idUser = cc_require_login('../utilisateur/login.php');

$commentId = trim((string)($_POST['id'] ?? $_GET['id'] ?? ''));
$postId    = trim((string)($_POST['postId'] ?? $_GET['postId'] ?? ''));

if ($commentId === '') {
    if ($isAjax) {
        cre8_comment_json_response(false, 'Comment ID is required.');
    }
    die('Comment ID is required.');
}

$commentC = new CommentC();
$deleted = $commentC->deleteComment($commentId, $idUser);

if (!$deleted) {
    $message = 'Failed to delete comment. The comment may not exist anymore, or it does not belong to you.';
    if ($isAjax) {
        cre8_comment_json_response(false, $message);
    }
    die($message);
}

if ($isAjax) {
    cre8_comment_json_response(true, 'Comment deleted successfully.', [
        'id' => $commentId,
        'postId' => $postId,
    ]);
}

// ── REDIRECTION APRÈS SUPPRESSION ────────────────────────────

// 1. Redirection vers la page détails d'un post (front)
if (($_GET['redirect'] ?? '') === 'post' && !empty($_GET['postId'])) {
    header('Location: ../post/details.php?id=' . urlencode($_GET['postId']));
    exit();
}

// 2. Redirection vers le portfolio du créateur
if (($_GET['redirect'] ?? '') === 'portfolio') {
    header('Location: ../post/portfolio.php');
    exit();
}

// 3. Redirection par défaut vers l'accueil (actuality feed)
$searchType = trim($_GET['searchType'] ?? '');
$keyword    = trim($_GET['keyword'] ?? '');
$redirect   = '../post/index.php';

$params = [];
if ($searchType !== '') {
    $params['searchType'] = $searchType;
}
if ($keyword !== '') {
    $params['keyword'] = $keyword;
}

if (!empty($params)) {
    $redirect .= '?' . http_build_query($params);
}

header('Location: ' . $redirect);
exit();
