<?php
require_once __DIR__ . '/../config.php';

class ReponseC {

    // ✔️ Ajouter une réponse (admin)
    public function ajouterReponse($reponse) {
        $sql = "INSERT INTO reponse 
                (idReclamation, idAdmin, contenu) 
                VALUES (:idReclamation, :idAdmin, :contenu)";
        $db = config::getConnexion();

        try {
            $query = $db->prepare($sql);
            $query->execute([
                'idReclamation' => $reponse->getIdReclamation(),
                'idAdmin'       => $reponse->getIdAdmin(),
                'contenu'       => $reponse->getContenu()
            ]);

            // 🔥 Mettre automatiquement la réclamation en "traitée"
            $sqlUpdate = "UPDATE reclamation SET statut = 'traitee' WHERE id = :id";
            $req = $db->prepare($sqlUpdate);
            $req->execute(['id' => $reponse->getIdReclamation()]);

        } catch (Exception $e) {
            die('Erreur ajout réponse: ' . $e->getMessage());
        }
    }

    // ✔️ Afficher réponses d’une réclamation
    public function afficherReponsesParReclamation($idReclamation) {
        $sql = "SELECT 
                    rep.contenu,
                    rep.date_reponse,
                    admin.nom AS admin_nom
                FROM reponse rep
                JOIN utilisateur admin ON rep.idAdmin = admin.id
                WHERE rep.idReclamation = :id";

        $db = config::getConnexion();
        $req = $db->prepare($sql);
        $req->execute(['id' => $idReclamation]);
        return $req->fetchAll();
    }

    // ✔️ Supprimer réponse
    public function supprimerReponse($id) {
        $sql = "DELETE FROM reponse WHERE id = :id";
        $db = config::getConnexion();
        $req = $db->prepare($sql);
        $req->execute(['id' => $id]);
    }
}