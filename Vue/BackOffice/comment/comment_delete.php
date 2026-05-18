<?php
require_once '../../../Controleur/session_helper.php';
cc_start_session();
cc_require_admin('../../FrontOffice/utilisateur/login.php');
require_once '../../../Controleur/commentC.php';

$commentId = trim($_GET['id'] ?? '');
if ($commentId === '') {
    die('Comment ID is required.');
}

$commentC = new CommentC();
$deleted = $commentC->deleteCommentAdmin($commentId);

if (!$deleted) {
    die('Failed to delete comment. The comment may not exist anymore, or one of its nested replies failed to delete.');
}

if (($_GET['redirect'] ?? '') === 'post' && !empty($_GET['postId'])) {
    header('Location: ../post/details.php?id=' . urlencode($_GET['postId']));
    exit;
}

$searchType = trim($_GET['searchType'] ?? '');
$keyword = trim($_GET['keyword'] ?? '');
$redirect = './index.php';

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
exit;