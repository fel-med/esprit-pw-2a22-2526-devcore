<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Modele/reclamation.php';


class ReclamationC {

    // ✔️ Ajouter une réclamation
    public function ajouterReclamation($reclamation) {
        $sql = "INSERT INTO reclamation 
                (idUtilisateur, description, statut, priorite) 
                VALUES (:idUtilisateur, :description, :statut, :priorite)";
        $db = config::getConnexion();
        try {
            $query = $db->prepare($sql);
            $query->execute([
                'idUtilisateur' => $reclamation->getIdUtilisateur(),
                'description'   => $reclamation->getDescription(),
                'statut'        => $reclamation->getStatut(),
                'priorite'      => $reclamation->getPriorite()
            ]);
        } catch (Exception $e) {
            die('Erreur ajout reclamation: ' . $e->getMessage());
        }
    }
public function afficherReclamationsAvecReponsesUser($idUtilisateur) {
    $sql = "SELECT 
                r.id,
                r.description,
                r.date_creation,
                r.statut,
                r.priorite,
                rep.contenu AS reponse,
                rep.date_reponse
            FROM reclamation r
            LEFT JOIN reponse rep ON r.id = rep.idReclamation
            WHERE r.idUtilisateur = :id
            ORDER BY r.date_creation DESC";

    $db = config::getConnexion();
    $req = $db->prepare($sql);
    $req->execute(['id' => $idUtilisateur]);

    return $req->fetchAll();
}
public function modifierReclamation($id, $description, $priorite) {
    $sql = "UPDATE reclamation 
            SET description = :description, priorite = :priorite
            WHERE id = :id";

    $db = config::getConnexion();
    $req = $db->prepare($sql);

    $req->execute([
        'id' => $id,
        'description' => $description,
        'priorite' => $priorite
    ]);
}
public function statistiques() {
    $sql = "SELECT 
                COUNT(*) AS total,
                SUM(statut = 'en_attente') AS en_attente,
                SUM(statut = 'traitee') AS traitee,
                SUM(priorite = 'haute') AS haute
            FROM reclamation";

    $db = config::getConnexion();
    return $db->query($sql)->fetch();
}
public function afficherReclamationsAdmin($search = '', $priorite = '', $page = 1, $limit = 10) {
    $sql = "SELECT 
                r.id,
                u.nom,
                r.description,
                r.date_creation,
                r.statut,
                r.priorite,
                rep.contenu AS reponse,
                rep.date_reponse
            FROM reclamation r
            JOIN utilisateur u ON r.idUtilisateur = u.id
            LEFT JOIN reponse rep ON r.id = rep.idReclamation
            WHERE 1=1";

    if (!empty($search)) {
        $sql .= " AND (u.nom LIKE :search OR r.description LIKE :search)";
    }

    if (!empty($priorite)) {
        $sql .= " AND r.priorite = :priorite";
    }

    $sql .= " ORDER BY r.date_creation DESC";

    $db = config::getConnexion();

    // Backward compatibility: if the method is called without page/limit, return the raw statement.
    if (func_num_args() < 3) {
        $stmt = $db->prepare($sql);
        if (!empty($search)) {
            $searchTerm = "%$search%";
            $stmt->bindParam(':search', $searchTerm);
        }
        if (!empty($priorite)) {
            $stmt->bindParam(':priorite', $priorite);
        }
        $stmt->execute();
        return $stmt;
    }
    
    // Get total count for pagination
    $countSql = "SELECT COUNT(*) FROM reclamation r
                 JOIN utilisateur u ON r.idUtilisateur = u.id
                 LEFT JOIN reponse rep ON r.id = rep.idReclamation
                 WHERE 1=1";
    
    if (!empty($search)) {
        $countSql .= " AND (u.nom LIKE :search OR r.description LIKE :search)";
    }

    if (!empty($priorite)) {
        $countSql .= " AND r.priorite = :priorite";
    }

    $countStmt = $db->prepare($countSql);

    if (!empty($search)) {
        $searchTerm = "%$search%";
        $countStmt->bindParam(':search', $searchTerm);
    }

    if (!empty($priorite)) {
        $countStmt->bindParam(':priorite', $priorite);
    }

    $countStmt->execute();
    $totalRecords = $countStmt->fetchColumn();

    // Add LIMIT and OFFSET
    $offset = ($page - 1) * $limit;
    $sql .= " LIMIT :limit OFFSET :offset";

    $stmt = $db->prepare($sql);

    if (!empty($search)) {
        $stmt->bindParam(':search', $searchTerm);
    }

    if (!empty($priorite)) {
        $stmt->bindParam(':priorite', $priorite);
    }

    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    
    return [
        'stmt' => $stmt,
        'total' => $totalRecords,
        'page' => $page,
        'limit' => $limit,
        'totalPages' => ceil($totalRecords / $limit)
    ];
}
    // ✔️ Afficher toutes les réclamations avec jointure
    public function afficherReclamations() {
        $sql = "SELECT 
                    r.id,
                    u.nom AS utilisateur_nom,
                    u.email,
                    r.description,
                    r.date_creation,
                    r.statut,
                    r.priorite
                FROM reclamation r
                JOIN utilisateur u ON r.idUtilisateur = u.id
                ORDER BY r.date_creation DESC";

        $db = config::getConnexion();
        return $db->query($sql);
    }

    // ✔️ Supprimer
    public function supprimerReclamation($id) {
        $sql = "DELETE FROM reclamation WHERE id = :id";
        $db = config::getConnexion();
        $req = $db->prepare($sql);
        $req->bindValue(':id', $id);
        $req->execute();
    }

    // ✔️ Modifier statut (ex: traitée)
    public function updateStatut($id, $statut) {
        $sql = "UPDATE reclamation SET statut = :statut WHERE id = :id";
        $db = config::getConnexion();
        $req = $db->prepare($sql);
        $req->execute([
            'statut' => $statut,
            'id'     => $id
        ]);
    }

    // ✔️ Récupérer une réclamation
    public function recupererReclamation($id) {
        $sql = "SELECT * FROM reclamation WHERE id = :id";
        $db = config::getConnexion();
        $req = $db->prepare($sql);
        $req->execute(['id' => $id]);
        return $req->fetch();
    }
}