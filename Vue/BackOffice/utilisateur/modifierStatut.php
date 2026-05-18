<?php
require_once '../../../Controleur/session_helper.php';
require_once '../../../config.php';
require_once '../../../Controleur/reclamationC.php';

session_start();
cc_require_admin('../../FrontOffice/utilisateur/login.php');
$viewerRole = cc_current_user_role();

if (isset($_POST['id']) && isset($_POST['statut'])) {
    $c = new ReclamationC();
    $reclamation = $c->getReclamationWithUserById($_POST['id']);

    if (!$reclamation || !cc_can_view_reclamation_from_role($viewerRole, $reclamation['complainant_role'] ?? '')) {
        header("Location: reclamations.php?error=forbidden");
        exit();
    }

    $allowedStatuses = ['en_attente', 'traitee'];
    if (in_array($_POST['statut'], $allowedStatuses, true)) {
        $c->updateStatut($_POST['id'], $_POST['statut']);
    }
}

header("Location: reclamations.php");
exit();
