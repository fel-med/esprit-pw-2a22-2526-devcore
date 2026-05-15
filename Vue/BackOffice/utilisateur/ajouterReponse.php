<?php
require_once '../../../config.php';
require_once '../../../Modele/Reponse.php';
require_once '../../../Controleur/reponseC.php';
require_once '../../../Controleur/session_helper.php';

session_start();
cc_require_admin('../../FrontOffice/utilisateur/login.php');

if (isset($_POST['contenu']) && isset($_POST['idReclamation'])) {

    $rep = new Reponse(
        null,
        $_POST['idReclamation'],
        $_SESSION['id'],
        $_POST['contenu']
    );

    $repC = new ReponseC();
    $repC->ajouterReponse($rep);

    header("Location: reclamations.php?success=reponse_envoyee");
}
