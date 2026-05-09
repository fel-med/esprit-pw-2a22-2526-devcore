<?php
require_once '../../../Controleur/session_helper.php';
cc_start_session();
require_once '../../../Controleur/postC.php';

// ── VÉRIFICATION SESSION ──────────────────────────────────────
cc_require_login('../utilisateur/login.php');

$postC = new PostC();
$creatorId = (int)$_SESSION['id']; // ✅ Depuis la session, pas hardcodé à 1

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('Post ID is missing.');
}

$postId = $_GET['id'];

if (!$postC->creatorOwnsPost($postId, $creatorId)) {
    die('Access denied. This post does not belong to you.');
}

$post = $postC->showPost($postId);

if (!$post) {
    die('Post not found.');
}

$postC->deletePost($postId, $creatorId);

header('Location: ./portfolio.php');
exit();