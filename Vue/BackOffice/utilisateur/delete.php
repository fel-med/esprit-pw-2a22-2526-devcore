<?php
require_once '../../../Controleur/utilisateurC.php';

if (isset($_GET['id'])) {
    $userC = new UtilisateurC();
    $userC->supprimerUser($_GET['id']);
}

header("Location: list.php");
?>