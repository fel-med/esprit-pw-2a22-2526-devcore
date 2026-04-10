<?php
require_once '../../../Controleur/postC.php';

$postC = new PostC();

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $postC->deletePost($_GET['id']);
    header("Location: listpost.php");
    exit();
} else {
    echo "Erreur : ID du post manquant.";
}
?>
