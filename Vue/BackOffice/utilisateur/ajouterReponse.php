<?php
require_once '../../../config.php';
require_once '../../../Modele/Reponse.php';
require_once '../../../Controleur/reponseC.php';
require_once '../../../Controleur/reclamationC.php';
require_once '../../../Controleur/session_helper.php';

session_start();
$actorId = cc_require_admin('../../FrontOffice/utilisateur/login.php');
$viewerRole = cc_current_user_role();

if (isset($_POST['contenu']) && isset($_POST['idReclamation'])) {
    $reclamationC = new ReclamationC();
    $reclamation = $reclamationC->getReclamationWithUserById($_POST['idReclamation']);

    if (!$reclamation || !cc_can_view_reclamation_from_role($viewerRole, $reclamation['complainant_role'] ?? '')) {
        header("Location: reclamations.php?error=forbidden");
        exit();
    }

    $rep = new Reponse(
        null,
        $_POST['idReclamation'],
        $actorId,
        $_POST['contenu']
    );

    $repC = new ReponseC();
    $repC->ajouterReponse($rep);

    header("Location: reclamations.php?success=reponse_envoyee");
    exit();
}

header("Location: reclamations.php");
exit();
