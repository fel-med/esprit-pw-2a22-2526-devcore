<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Modele/campagne.php';

class CampagneC
{
    // ─── AJOUTER ───────────────────────────────────────────────────────────────
    public function ajouterCampagne(Campagne $campagne): void
    {
        if (empty(trim($campagne->getTitre())) || $campagne->getBudget() < 0) {
            throw new Exception("Invalid campaign: title required, budget must be >= 0");
        }
        $sql = "INSERT INTO campagne
                    (titre, description, dateDebut, dateFin, statut, idMarque)
                VALUES
                    (:titre, :description, :dateDebut, :dateFin, :statut, :idMarque)";
        $db  = config::getConnexion();
        $q   = $db->prepare($sql);
        $q->execute([
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
    public function modifierCampagne(Campagne $campagne, int $id): void
    {
        if (empty(trim($campagne->getTitre())) || $campagne->getBudget() < 0) {
            throw new Exception("Invalid campaign: title required, budget must be >= 0");
        }
        $sql = "UPDATE campagne SET
                    titre = :titre,
                    description   = :description,
                    dateDebut     = :dateDebut,
                    dateFin       = :dateFin,
                    statut        = :statut
                WHERE idCampagne = :id";
        $db  = config::getConnexion();
        $q   = $db->prepare($sql);
        $q->execute([
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
    public function afficherCampagnes(?int $idMarque = null): array
    {
        $where = $idMarque
            ? "WHERE idMarque = :idMarque"
            : "WHERE 1=1";
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
    public function afficherCampagnesArchives(?int $idMarque = null): array
    {
        $where = $idMarque
            ? "WHERE idMarque = :idMarque AND statut = 'terminee'"
            : "WHERE c.statut = 'terminee'";
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

    // ─── TOUTES CAMPAGNES (admin) ──────────────────────────────────────────────
    public function afficherToutesCampagnes(): array
    {
        $sql = "SELECT c.*, u.nom AS nomMarque
                FROM campagne c
                LEFT JOIN utilisateur u ON u.id = c.idMarque
                ORDER BY c.idCampagne DESC";
        return config::getConnexion()->query($sql)->fetchAll();
    }

    // ─── RÉCUPÉRER UNE CAMPAGNE ────────────────────────────────────────────────
    public function recupererCampagne(int $id): ?array
    {
        $sql = "SELECT c.*, u.nom AS nomMarque
                FROM campagne c
                LEFT JOIN utilisateur u ON u.id = c.idMarque
                WHERE c.idCampagne = :id";
        $db  = config::getConnexion();
        $q   = $db->prepare($sql);
        $q->execute(['id' => $id]);
        $r = $q->fetch();
        return $r ?: null;
    }

    // ─── SUPPRIMER ─────────────────────────────────────────────────────────────
    public function supprimerCampagne(int $id): void
    {
        $q = config::getConnexion()->prepare("DELETE FROM campagne WHERE idCampagne = :id");
        $q->execute(['id' => $id]);
    }

    // ─── ARCHIVER / DÉSARCHIVER ────────────────────────────────────────────────
    public function toggleArchive(int $id): void
    {
        $q = config::getConnexion()->prepare(
            "UPDATE campagne SET statut = IF(statut='terminee','planifiee','terminee') WHERE idCampagne = :id"
        );
        $q->execute(['id' => $id]);
    }

    // ─── CHANGER STATUT ────────────────────────────────────────────────────────
    public function changerStatut(int $id, string $statut): void
    {
        if (!in_array($statut, $this->getStatuts())) return;
        $q = config::getConnexion()->prepare(
            "UPDATE campagne SET statut = :statut WHERE idCampagne = :id"
        );
        $q->execute(['statut' => $statut, 'id' => $id]);
    }

    // ─── STATUTS DISPONIBLES ───────────────────────────────────────────────────
    public function getStatuts(): array
    {
        return ['brouillon', 'active', 'terminee', 'annulee'];
    }

    // ════════════════════════════════════════════════════════════════
    // ─── JOINTURE CAMPAGNE ↔ PRODUIT ────────────────────────────────
    // ════════════════════════════════════════════════════════════════

    public function ajouterProduitCampagne(int $idCampagne, int $idProduit): void
    {
        $q = config::getConnexion()->prepare(
            "INSERT IGNORE INTO campagne_produit (idCampagne, idProduit) VALUES (:c, :p)"
        );
        $q->execute(['c' => $idCampagne, 'p' => $idProduit]);
    }

    public function retirerProduitCampagne(int $idCampagne, int $idProduit): void
    {
        $q = config::getConnexion()->prepare(
            "DELETE FROM campagne_produit WHERE idCampagne = :c AND idProduit = :p"
        );
        $q->execute(['c' => $idCampagne, 'p' => $idProduit]);
    }

    public function getCampagnesByProduit(int $idProduit): array
    {
        $sql = "SELECT c.*, u.nom AS nomMarque
                FROM campagne c
                INNER JOIN campagne_produit cp ON cp.idCampagne = c.idCampagne
                LEFT JOIN utilisateur u ON u.id = c.idMarque
                WHERE cp.idProduit = :p
                ORDER BY c.dateDebut DESC";
        $q = config::getConnexion()->prepare($sql);
        $q->execute(['p' => $idProduit]);
        return $q->fetchAll();
    }

    public function compterProduitsCampagne(int $idCampagne): int
    {
        $sql = "SELECT COUNT(*) FROM campagne_produit cp
                INNER JOIN produit p ON p.idProduit = cp.idProduit
                WHERE cp.idCampagne = :c";
        $q = config::getConnexion()->prepare($sql);
        $q->execute(['c' => $idCampagne]);
        return (int) $q->fetchColumn();
    }

    // ════════════════════════════════════════════════════════════════
    // ─── IA : GÉNÉRATION CAMPAGNE (MARQUE) ────────────────────────
    // Utilisée dans : Vue/FrontOffice/campagne/index.php
    // ════════════════════════════════════════════════════════════════

    public function genererCampagneIA(string $produit, string $cible, float $budget): ?array
    {
        $prompt = "Génère une campagne marketing pour la plateforme Cre8Connect.

Contexte :
- Produit à promouvoir : $produit
- Audience cible : $cible
- Budget disponible : {$budget}€

Réponds UNIQUEMENT avec un objet JSON valide contenant :
{
  \"titre\": \"Titre accrocheur de la campagne (max 100 caractères)\",
  \"description\": \"Description détaillée (200-400 caractères)\",
  \"objectif\": \"Objectif mesurable (max 200 caractères)\",
  \"type_contenu\": \"Types de contenu recommandés\"
}

Ne retourne RIEN d'autre que le JSON.";

        return $this->_parseIA(callOpenRouter($prompt));
    }

    // ─── IA : ANALYSE CAMPAGNE (ADMIN) ────────────────────────────
    // Utilisée dans : Vue/BackOffice/campagne/index.php

    public function analyserCampagneIA(
        string $titre, string $description, float $budget, string $statut
    ): ?array {
        $prompt = "Analyse la campagne suivante pour un administrateur de Cre8Connect.

Campagne :
- Titre : $titre
- Description : $description
- Budget : {$budget}€
- Statut : $statut

Réponds UNIQUEMENT avec un objet JSON valide contenant :
{
  \"score_qualite\": \"Note de 1 à 10\",
  \"points_forts\": [\"point 1\", \"point 2\"],
  \"points_faibles\": [\"point 1\", \"point 2\"],
  \"risques\": [\"risque 1\", \"risque 2\"],
  \"recommandations\": [\"reco 1\", \"reco 2\", \"reco 3\"],
  \"budget_adequat\": \"oui/non avec justification courte\"
}

Ne retourne RIEN d'autre que le JSON.";

        return $this->_parseIA(callOpenRouter($prompt));
    }

    // ─── IA : SUGGESTIONS CAMPAGNES (CRÉATEUR) ────────────────────
    // Utilisée dans : Vue/FrontOffice/campagne/indexC.php

    public function suggererCampagnesIA(
        string $competences, string $interets, string $audience
    ): ?array {
        $prompt = "Tu es un conseiller pour créateurs de contenu sur Cre8Connect.

Profil du créateur :
- Compétences : $competences
- Centres d'intérêt : $interets
- Type d'audience : $audience

Suggère des types de campagnes. Réponds UNIQUEMENT en JSON :
{
  \"suggestions\": [
    {\"type_campagne\": \"...\", \"raison\": \"...\", \"conseil\": \"...\"},
    {\"type_campagne\": \"...\", \"raison\": \"...\", \"conseil\": \"...\"},
    {\"type_campagne\": \"...\", \"raison\": \"...\", \"conseil\": \"...\"}
  ],
  \"conseil_general\": \"Conseil personnalisé\"
}

Ne retourne RIEN d'autre que le JSON.";

        return $this->_parseIA(callOpenRouter($prompt));
    }

    // ─── Utilitaire privé ──────────────────────────────────────────

   private function _parseIA(?string $raw): ?array
{
    if (!$raw) return null;

    // Nettoyer les balises markdown
    $clean = trim(preg_replace('/```json\s*|\s*```/', '', $raw));

    // Extraire le premier bloc JSON valide si du texte précède
    if (preg_match('/\{[\s\S]*\}/u', $clean, $matches)) {
        $clean = $matches[0];
    }

    $parsed = json_decode($clean, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("CampagneC IA: JSON invalide — " . json_last_error_msg());
        error_log("CampagneC IA: Raw content — " . substr($raw, 0, 500));
        return null;
    }

    return $parsed;
}
}
