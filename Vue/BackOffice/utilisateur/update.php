<?php
require_once '../../../Controleur/utilisateurC.php';

$userC = new UtilisateurC();

if (isset($_POST['id'])) {
    $userC->updateUser(
        $_POST['id'],
        $_POST['nom'],
        $_POST['email'],
        $_POST['role']
    );
}

header("Location: index.php");
exit;