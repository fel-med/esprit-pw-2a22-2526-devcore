<?php
require_once '../../../Controleur/postC.php';

$postC = new PostC();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('Post ID is missing.');
}

$post = $postC->showPost($_GET['id']);

if (!$post) {
    die('Post not found.');
}

$postC->deletePostAdmin($_GET['id']);

header('Location: ./index.php');
exit();
