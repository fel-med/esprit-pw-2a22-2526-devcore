<?php
require_once '../../../Controleur/postC.php';

$postC = new PostC();

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $postC->incrementDislike($_GET['id']);
}

$redirect = $_SERVER['HTTP_REFERER'] ?? './index.php';
header('Location: ' . $redirect);
exit();