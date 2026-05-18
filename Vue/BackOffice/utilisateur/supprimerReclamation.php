<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../Controleur/reclamationC.php';
require_once __DIR__ . '/../../../Controleur/session_helper.php';

session_start();
cc_require_admin('../../FrontOffice/utilisateur/login.php');
$viewerRole = cc_current_user_role();

if (isset($_POST['id'])) {
    $reclamationC = new ReclamationC();
    $reclamation = $reclamationC->getReclamationWithUserById($_POST['id']);

    if (!$reclamation || !cc_can_view_reclamation_from_role($viewerRole, $reclamation['complainant_role'] ?? '')) {
        header("Location: reclamations.php?error=forbidden");
        exit();
    }

    $reclamationC->supprimerReclamation($_POST['id']);

    header("Location: reclamations.php");
    exit();
}

header("Location: reclamations.php");
exit();
