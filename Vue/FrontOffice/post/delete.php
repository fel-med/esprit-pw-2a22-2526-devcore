<?php
require_once '../../../Controleur/postC.php';

$postC = new PostC();
$creatorId = 1;

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('Post ID is missing.');
}

$postId = $_GET['id'];

if (!$postC->creatorOwnsPost($postId, $creatorId)) {
    die('Access denied. This post does not belong to creator #1.');
}

$post = $postC->showPost($postId);

if (!$post) {
    die('Post not found.');
}

$postC->deletePost($postId, $creatorId);

header('Location: ./portfolio.php');
exit();