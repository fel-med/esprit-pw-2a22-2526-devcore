<?php
require_once '../../../config.php';
require_once '../../../Modele/reponse.php';
require_once '../../../Controleur/reponseC.php';

session_start();

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