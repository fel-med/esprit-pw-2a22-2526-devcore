<?php

require_once __DIR__ . '/../Modele/contrat.php';
require_once __DIR__ . '/../config.php';

class ContratC {

    private PDO $pdo;

    public function __construct() {
        $this->pdo = config::getConnexion(); // ✅
    }

    // ─────────────────────────────────────────────
    // CRUD DE BASE
    // ─────────────────────────────────────────────

    public function getAll(): array {
        $stmt = $this->pdo->query("
            SELECT c.*, 
                   u1.nom AS nomMarque, 
                   u2.nom AS nomCreateur,
                   ca.titreCampagne AS titreCampagne
            FROM contrat c
            LEFT JOIN utilisateur u1 ON c.id_marque = u1.id
            LEFT JOIN utilisateur u2 ON c.id_createur = u2.id
            LEFT JOIN campagne ca ON c.id_campagne = ca.idCampagne
            ORDER BY c.date_creation DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByMarque(int $idMarque): array {
        $stmt = $this->pdo->prepare("
            SELECT c.*, 
                   u2.nom AS nomCreateur,
                   ca.titreCampagne AS titreCampagne
            FROM contrat c
            LEFT JOIN utilisateur u2 ON c.id_createur = u2.id
            LEFT JOIN campagne ca ON c.id_campagne = ca.idCampagne
            WHERE c.id_marque = :idMarque
            ORDER BY c.date_creation DESC
        ");
        $stmt->execute([':idMarque' => $idMarque]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByCreateur(int $idCreateur): array {
        $stmt = $this->pdo->prepare("
            SELECT c.*, 
                   u1.nom AS nomMarque,
                   ca.titreCampagne AS titreCampagne
            FROM contrat c
            LEFT JOIN utilisateur u1 ON c.id_marque = u1.id
            LEFT JOIN campagne ca ON c.id_campagne = ca.idCampagne
            WHERE c.id_createur = :idCreateur
            ORDER BY c.date_creation DESC
        ");
        $stmt->execute([':idCreateur' => $idCreateur]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array {
        $stmt = $this->pdo->prepare("
            SELECT c.*, 
                   u1.nom AS nomMarque, 
                   u2.nom AS nomCreateur,
                   ca.titreCampagne AS titreCampagne
            FROM contrat c
            LEFT JOIN utilisateur u1 ON c.id_marque = u1.id
            LEFT JOIN utilisateur u2 ON c.id_createur = u2.id
            LEFT JOIN campagne ca ON c.id_campagne = ca.idCampagne
            WHERE c.id = :id
        ");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(Contrat $contrat): bool {
        $stmt = $this->pdo->prepare("
            INSERT INTO contrat 
                (id_campagne, id_marque, id_createur, titre, description, montant, date_debut, date_fin, statut, date_creation, fichier_pdf)
            VALUES 
                (:idCampagne, :idMarque, :idCreateur, :titre, :description, :montant, :dateDebut, :dateFin, :statut, :dateCreation, :fichierPdf)
        ");
        return $stmt->execute([
            ':idCampagne'   => $contrat->getIdCampagne(),
            ':idMarque'     => $contrat->getIdMarque(),
            ':idCreateur'   => $contrat->getIdCreateur(),
            ':titre'        => $contrat->getTitre(),
            ':description'  => $contrat->getDescription(),
            ':montant'      => $contrat->getMontant(),
            ':dateDebut'    => $contrat->getDateDebut(),
            ':dateFin'      => $contrat->getDateFin(),
            ':statut'       => $contrat->getStatut(),
            ':dateCreation' => $contrat->getDateCreation(),
            ':fichierPdf'   => $contrat->getFichierPdf(),
        ]);
    }

    public function update(Contrat $contrat): bool {
        $stmt = $this->pdo->prepare("
            UPDATE contrat SET
                titre        = :titre,
                description  = :description,
                montant      = :montant,
                date_debut   = :dateDebut,
                date_fin     = :dateFin,
                statut       = :statut,
                fichier_pdf  = :fichierPdf
            WHERE id = :id
        ");
        return $stmt->execute([
            ':titre'       => $contrat->getTitre(),
            ':description' => $contrat->getDescription(),
            ':montant'     => $contrat->getMontant(),
            ':dateDebut'   => $contrat->getDateDebut(),
            ':dateFin'     => $contrat->getDateFin(),
            ':statut'      => $contrat->getStatut(),
            ':fichierPdf'  => $contrat->getFichierPdf(),
            ':id'          => $contrat->getId(),
        ]);
    }

    public function delete(int $id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM contrat WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function updateStatut(int $id, string $statut): bool {
        $allowed = ['en_attente', 'signe', 'resilie', 'expire'];
        if (!in_array($statut, $allowed)) return false;
        $stmt = $this->pdo->prepare("UPDATE contrat SET statut = :statut WHERE id = :id");
        return $stmt->execute([':statut' => $statut, ':id' => $id]);
    }

    public function getStats(): array {
        $stmt = $this->pdo->query("
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN statut = 'en_attente' THEN 1 ELSE 0 END) AS en_attente,
                SUM(CASE WHEN statut = 'signe'      THEN 1 ELSE 0 END) AS signes,
                SUM(CASE WHEN statut = 'resilie'    THEN 1 ELSE 0 END) AS resilies,
                SUM(CASE WHEN statut = 'expire'     THEN 1 ELSE 0 END) AS expires,
                SUM(montant) AS montant_total
            FROM contrat
        ");
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}