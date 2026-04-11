<?php
require_once '../../../Controleur/postC.php';

$postC = new PostC();
$creatorId = 1;

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('Post ID is missing.');
}

$postId = $_GET['id'];

if (!$postC->creatorOwnsPost($postId, $creatorId)) {
    die('Access denied.');
}

$postC->deletePost($postId, $creatorId);
header('Location: ./index.php');
exit();