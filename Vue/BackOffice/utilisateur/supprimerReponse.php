<?php
require_once '../../../config.php';
require_once '../../../Modele/reponse.php';
require_once '../../../Controleur/reponseC.php';

session_start();

if (isset($_POST['idReclamation'])) {

    $repC = new ReponseC();
    
    // Récupérer l'ID de la réponse via l'ID de la réclamation
    $idReponse = $repC->getReponseByReclamation($_POST['idReclamation']);
    
    if ($idReponse) {
        // Supprimer la réponse
        $repC->supprimerReponse($idReponse);
        
        // Remettre la réclamation en "en_attente"
        $sqlUpdate = "UPDATE reclamation SET statut = 'en_attente' WHERE id = :id";
        $db = config::getConnexion();
        $req = $db->prepare($sqlUpdate);
        $req->execute(['id' => $_POST['idReclamation']]);
        
        header("Location: reclamations.php?success=deleted");
    } else {
        header("Location: reclamations.php?error=not_found");
    }
} else {
    header("Location: reclamations.php?error=invalid");
}
?>
