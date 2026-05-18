<?php
require_once '../../../config.php';
require_once '../../../Modele/Reponse.php';
require_once '../../../Controleur/reponseC.php';
require_once '../../../Controleur/reclamationC.php';
require_once '../../../Controleur/session_helper.php';

session_start();
cc_require_admin('../../FrontOffice/utilisateur/login.php');
$viewerRole = cc_current_user_role();

if (isset($_POST['contenu']) && isset($_POST['idReclamation'])) {
    $reclamationC = new ReclamationC();
    $reclamation = $reclamationC->getReclamationWithUserById($_POST['idReclamation']);

    if (!$reclamation || !cc_can_view_reclamation_from_role($viewerRole, $reclamation['complainant_role'] ?? '')) {
        header("Location: reclamations.php?error=forbidden");
        exit();
    }

    $repC = new ReponseC();
    
    // Récupérer l'ID de la réponse via l'ID de la réclamation
    $idReponse = $repC->getReponseByReclamation($_POST['idReclamation']);
    
    if ($idReponse) {
        // Modifier la réponse
        $repC->modifierReponse($idReponse, $_POST['contenu']);
        
        header("Location: reclamations.php?success=modified");
    } else {
        header("Location: reclamations.php?error=not_found");
    }
} else {
    header("Location: reclamations.php?error=invalid");
}
?>
