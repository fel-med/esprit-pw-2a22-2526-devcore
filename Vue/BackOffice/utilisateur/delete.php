<?php
require_once '../../../Controleur/session_helper.php';
require_once '../../../Controleur/utilisateurC.php';

cc_require_admin('../../FrontOffice/utilisateur/login.php');

if (isset($_GET['id'])) {
    $userC = new UtilisateurC();
    $userC->supprimerUser($_GET['id']);
}

header("Location: index.php");
?>
