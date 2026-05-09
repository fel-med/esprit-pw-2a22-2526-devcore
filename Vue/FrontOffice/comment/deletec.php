<?php
require_once '../../../Controleur/session_helper.php';
cc_start_session();
require_once '../../../Controleur/commentC.php';

// ── VÉRIFICATION SESSION ──────────────────────────────────────
$idUser = cc_require_login('../utilisateur/login.php');

$commentId = trim($_GET['id'] ?? '');
if ($commentId === '') {
    die('Comment ID is required.');
}

$commentC = new CommentC();
$deleted = $commentC->deleteComment($commentId, $idUser);

if (!$deleted) {
    die('Failed to delete comment. The comment may not exist anymore, or it does not belong to you.');
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