<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Modele/campagne.php';

class CampagneC {

    // ─── AJOUTER ───────────────────────────────────────────────────────────────
    public function ajouterCampagne($campagne) {
        if (empty(trim($campagne->getTitre())) || $campagne->getBudget() < 0) {
            throw new Exception("Invalid campaign: title required, budget must be >= 0");
        }
        $sql = "INSERT INTO campagne
                    (titreCampagne, description, dateDebut, dateFin, budget, statut, idMarque, objectif, estArchive)
                VALUES
                    (:titre, :description, :dateDebut, :dateFin, :budget, :statut, :idMarque, :objectif, :estArchive)";
        $db = config::getConnexion();
        $query = $db->prepare($sql);
        $query->execute([
            'titre'       => $campagne->getTitre(),
            'description' => $campagne->getDescription(),
            'dateDebut'   => $campagne->getDateDebut(),
            'dateFin'     => $campagne->getDateFin(),
            'budget'      => $campagne->getBudget(),
            'statut'      => $campagne->getStatut(),
            'idMarque'    => $campagne->getIdMarque(),
            'objectif'    => $campagne->getObjectif(),
            'estArchive'  => $campagne->getEstArchive() ? 1 : 0,
        ]);
    }

    // ─── MODIFIER ──────────────────────────────────────────────────────────────
    public function modifierCampagne($campagne, $id) {
        if (empty(trim($campagne->getTitre())) || $campagne->getBudget() < 0) {
            throw new Exception("Invalid campaign: title required, budget must be >= 0");
        }
        $sql = "UPDATE campagne SET
                    titreCampagne = :titre,
                    description   = :description,
                    dateDebut     = :dateDebut,
                    dateFin       = :dateFin,
                    budget        = :budget,
                    statut        = :statut,
                    objectif      = :objectif,
                    estArchive    = :estArchive
                WHERE idCampagne = :id";
        $db = config::getConnexion();
        $query = $db->prepare($sql);
        $query->execute([
            'titre'       => $campagne->getTitre(),
            'description' => $campagne->getDescription(),
            'dateDebut'   => $campagne->getDateDebut(),
            'dateFin'     => $campagne->getDateFin(),
            'budget'      => $campagne->getBudget(),
            'statut'      => $campagne->getStatut(),
            'objectif'    => $campagne->getObjectif(),
            'estArchive'  => $campagne->getEstArchive() ? 1 : 0,
            'id'          => $id,
        ]);
    }

    // ─── AFFICHER (actives, non archivées) ─────────────────────────────────────
    public function afficherCampagnes($idMarque = null) {
        $where = $idMarque
            ? "WHERE estArchive = 0 AND idMarque = :idMarque"
            : "WHERE estArchive = 0";
        $sql = "SELECT c.*, u.nom AS nomMarque
                FROM campagne c
                LEFT JOIN utilisateur u ON u.id = c.idMarque
                $where
                ORDER BY c.dateDebut DESC";
        $db = config::getConnexion();
        if ($idMarque) {
            $q = $db->prepare($sql);
            $q->execute(['idMarque' => $idMarque]);
            return $q->fetchAll();
        }
        return $db->query($sql)->fetchAll();
    }

    // ─── AFFICHER ARCHIVÉES ────────────────────────────────────────────────────
    public function afficherCampagnesArchives($idMarque = null) {
        $where = $idMarque
            ? "WHERE estArchive = 1 AND idMarque = :idMarque"
            : "WHERE estArchive = 1";
        $sql = "SELECT c.*, u.nom AS nomMarque
                FROM campagne c
                LEFT JOIN utilisateur u ON u.id = c.idMarque
                $where
                ORDER BY c.idCampagne DESC";
        $db = config::getConnexion();
        if ($idMarque) {
            $q = $db->prepare($sql);
            $q->execute(['idMarque' => $idMarque]);
            return $q->fetchAll();
        }
        return $db->query($sql)->fetchAll();
    }

    // ─── RÉCUPÉRER UNE CAMPAGNE ────────────────────────────────────────────────
    public function recupererCampagne($id) {
        $sql = "SELECT c.*, u.nom AS nomMarque
                FROM campagne c
                LEFT JOIN utilisateur u ON u.id = c.idMarque
                WHERE c.idCampagne = :id";
        $db = config::getConnexion();
        $query = $db->prepare($sql);
        $query->execute(['id' => $id]);
        return $query->fetch();
    }

    // ─── SUPPRIMER ─────────────────────────────────────────────────────────────
    public function supprimerCampagne($id) {
        $sql = "DELETE FROM campagne WHERE idCampagne = :id";
        $db = config::getConnexion();
        $req = $db->prepare($sql);
        $req->execute(['id' => $id]);
    }

    // ─── ARCHIVER / DÉSARCHIVER ────────────────────────────────────────────────
    public function toggleArchive($id) {
        $sql = "UPDATE campagne SET estArchive = NOT estArchive WHERE idCampagne = :id";
        $db = config::getConnexion();
        $q = $db->prepare($sql);
        $q->execute(['id' => $id]);
    }

    // ─── CHANGER STATUT ────────────────────────────────────────────────────────
    public function changerStatut($id, $statut) {
        $statutsValides = ['brouillon', 'active', 'terminee', 'annulee'];
        if (!in_array($statut, $statutsValides)) return;
        $sql = "UPDATE campagne SET statut = :statut WHERE idCampagne = :id";
        $db = config::getConnexion();
        $q = $db->prepare($sql);
        $q->execute(['statut' => $statut, 'id' => $id]);
    }

    // ─── STATUTS DISTINCTS ─────────────────────────────────────────────────────
    public function getStatuts() {
        return ['brouillon', 'active', 'terminee', 'annulee'];
    }

    // ─── TOUTES CAMPAGNES (admin) ──────────────────────────────────────────────
    public function afficherToutesCampagnes() {
        $sql = "SELECT c.*, u.nom AS nomMarque
                FROM campagne c
                LEFT JOIN utilisateur u ON u.id = c.idMarque
                ORDER BY c.idCampagne DESC";
        $db = config::getConnexion();
        return $db->query($sql)->fetchAll();
    }

    // ════════════════════════════════════════════════════════════════
    // ─── JOINTURE CAMPAGNE ↔ PRODUIT ────────────────────────────────
    // ════════════════════════════════════════════════════════════════

    /**
     * Lie un produit à une campagne.
     * Ignore silencieusement si le lien existe déjà (INSERT IGNORE).
     *
     * @param int $idCampagne
     * @param int $idProduit
     */
    public function ajouterProduitCampagne(int $idCampagne, int $idProduit): void {
        $sql = "INSERT IGNORE INTO campagne_produit (idCampagne, idProduit) VALUES (:idCampagne, :idProduit)";
        $db  = config::getConnexion();
        $q   = $db->prepare($sql);
        $q->execute(['idCampagne' => $idCampagne, 'idProduit' => $idProduit]);
    }

    /**
     * Supprime le lien entre un produit et une campagne.
     *
     * @param int $idCampagne
     * @param int $idProduit
     */
    public function retirerProduitCampagne(int $idCampagne, int $idProduit): void {
        $sql = "DELETE FROM campagne_produit WHERE idCampagne = :idCampagne AND idProduit = :idProduit";
        $db  = config::getConnexion();
        $q   = $db->prepare($sql);
        $q->execute(['idCampagne' => $idCampagne, 'idProduit' => $idProduit]);
    }

    /**
     * Retourne les campagnes actives auxquelles un produit est lié.
     * Utile pour la vue détail produit côté créateur.
     *
     * @param int $idProduit
     * @return array
     */
    public function getCampagnesByProduit(int $idProduit): array {
        $sql = "SELECT c.*, u.nom AS nomMarque
                FROM campagne c
                INNER JOIN campagne_produit cp ON cp.idCampagne = c.idCampagne
                LEFT JOIN utilisateur u ON u.id = c.idMarque
                WHERE cp.idProduit = :idProduit
                  AND c.estArchive  = 0
                ORDER BY c.dateDebut DESC";
        $db = config::getConnexion();
        $q  = $db->prepare($sql);
        $q->execute(['idProduit' => $idProduit]);
        return $q->fetchAll();
    }

    /**
     * Compte le nombre de produits liés à une campagne.
     * Utile pour les KPIs et badges.
     *
     * @param int $idCampagne
     * @return int
     */
    public function compterProduitsCampagne(int $idCampagne): int {
        $sql = "SELECT COUNT(*) FROM campagne_produit cp
                INNER JOIN produit p ON p.idProduit = cp.idProduit
                WHERE cp.idCampagne = :idCampagne AND p.estArchive = 0";
        $db = config::getConnexion();
        $q  = $db->prepare($sql);
        $q->execute(['idCampagne' => $idCampagne]);
        return (int) $q->fetchColumn();
    }
}
?>