<?php

require_once __DIR__ . '/../Modele/contrat.php';
require_once __DIR__ . '/../config.php';

class ContratC
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = config::getConnexion();
    }

    // ─────────────────────────────────────────────
    // CRUD DE BASE
    // ─────────────────────────────────────────────

    public function getAll(): array
    {
        $stmt = $this->pdo->query("
            SELECT c.*,
                   u1.nom AS nomMarque,
                   u2.nom AS nomCreateur,
                   ca.titreCampagne AS titreCampagne
            FROM contrat c
            LEFT JOIN utilisateur u1 ON c.id_marque   = u1.id
            LEFT JOIN utilisateur u2 ON c.id_createur = u2.id
            LEFT JOIN campagne ca    ON c.id_campagne  = ca.idCampagne
            ORDER BY c.date_creation DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByMarque(int $idMarque): array
    {
        $stmt = $this->pdo->prepare("
            SELECT c.*,
                   u2.nom AS nomCreateur,
                   ca.titreCampagne AS titreCampagne
            FROM contrat c
            LEFT JOIN utilisateur u2 ON c.id_createur = u2.id
            LEFT JOIN campagne ca    ON c.id_campagne  = ca.idCampagne
            WHERE c.id_marque = :idMarque
            ORDER BY c.date_creation DESC
        ");
        $stmt->execute([':idMarque' => $idMarque]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByCreateur(int $idCreateur): array
    {
        $stmt = $this->pdo->prepare("
            SELECT c.*,
                   u1.nom AS nomMarque,
                   ca.titreCampagne AS titreCampagne
            FROM contrat c
            LEFT JOIN utilisateur u1 ON c.id_marque   = u1.id
            LEFT JOIN campagne ca    ON c.id_campagne  = ca.idCampagne
            WHERE c.id_createur = :idCreateur
            ORDER BY c.date_creation DESC
        ");
        $stmt->execute([':idCreateur' => $idCreateur]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT c.*,
                   u1.nom AS nomMarque,
                   u2.nom AS nomCreateur,
                   ca.titreCampagne AS titreCampagne
            FROM contrat c
            LEFT JOIN utilisateur u1 ON c.id_marque   = u1.id
            LEFT JOIN utilisateur u2 ON c.id_createur = u2.id
            LEFT JOIN campagne ca    ON c.id_campagne  = ca.idCampagne
            WHERE c.id = :id
        ");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(Contrat $contrat): bool
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO contrat
                (id_campagne, id_marque, id_createur, titre, description, montant,
                 date_debut, date_fin, statut, date_creation, fichier_pdf)
            VALUES
                (:idCampagne, :idMarque, :idCreateur, :titre, :description, :montant,
                 :dateDebut, :dateFin, :statut, :dateCreation, :fichierPdf)
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

    public function update(Contrat $contrat): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE contrat SET
                titre       = :titre,
                description = :description,
                montant     = :montant,
                date_debut  = :dateDebut,
                date_fin    = :dateFin,
                statut      = :statut,
                fichier_pdf = :fichierPdf
            WHERE id = :id
        ");
        return $stmt->execute([
            ':titre'      => $contrat->getTitre(),
            ':description'=> $contrat->getDescription(),
            ':montant'    => $contrat->getMontant(),
            ':dateDebut'  => $contrat->getDateDebut(),
            ':dateFin'    => $contrat->getDateFin(),
            ':statut'     => $contrat->getStatut(),
            ':fichierPdf' => $contrat->getFichierPdf(),
            ':id'         => $contrat->getId(),
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM contrat WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function updateStatut(int $id, string $statut): bool
    {
        $allowed = ['en_attente', 'signe', 'resilie', 'expire'];
        if (!in_array($statut, $allowed)) return false;
        $stmt = $this->pdo->prepare("UPDATE contrat SET statut = :statut WHERE id = :id");
        return $stmt->execute([':statut' => $statut, ':id' => $id]);
    }

    public function getStats(): array
    {
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

    // ════════════════════════════════════════════════════════════════
    // ─── IA : GÉNÉRATION DE CONTRAT (MARQUE) ──────────────────────
    // Utilisée dans : Vue/FrontOffice/contrat/index.php
    // ════════════════════════════════════════════════════════════════

    public function genererContratIA(string $campagne, float $remuneration, string $delai): ?array
    {
        $prompt = "Génère les clauses d'un contrat de collaboration pour la plateforme Cre8Connect.

Contexte :
- Campagne : $campagne
- Rémunération prévue : {$remuneration}€
- Délai de livraison : $delai

Réponds UNIQUEMENT avec un objet JSON valide contenant :
{
  \"titre_contrat\": \"Titre professionnel du contrat\",
  \"obligations_marque\": [\"Obligation 1\", \"Obligation 2\", \"Obligation 3\"],
  \"obligations_createur\": [\"Obligation 1\", \"Obligation 2\", \"Obligation 3\", \"Obligation 4\"],
  \"timeline\": [
    {\"etape\": \"Description étape 1\", \"delai\": \"J+0\"},
    {\"etape\": \"Description étape 2\", \"delai\": \"J+7\"},
    {\"etape\": \"Description étape 3\", \"delai\": \"J+14\"},
    {\"etape\": \"Description étape 4\", \"delai\": \"Fin\"}
  ],
  \"conditions_paiement\": \"Description détaillée des conditions et échéances de paiement\",
  \"droits_utilisation\": \"Description des droits d'utilisation du contenu créé\",
  \"clause_resiliation\": \"Conditions de résiliation du contrat\"
}

Sois professionnel, juridiquement crédible et adapté au droit français. Ne retourne RIEN d'autre que le JSON.";

        $response = callOpenRouter($prompt);
        if (!$response) return null;
        $clean  = trim(preg_replace('/```json\s*|\s*```/', '', $response));
        $parsed = json_decode($clean, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("IA ContratC generer: JSON invalide — " . json_last_error_msg());
            return null;
        }
        return $parsed;
    }

    // ─── IA : COMPRENDRE CONTRAT (CRÉATEUR) ───────────────────────
    // Utilisée dans : Vue/FrontOffice/contrat/indexC.php

    public function comprendreContratIA(
        string $titre, string $description, float $montant,
        string $dateDebut, string $dateFin
    ): ?array {
        $prompt = "Tu es un assistant juridique simplifié pour créateurs de contenu sur Cre8Connect.

Contrat :
- Titre : $titre
- Description : $description
- Montant : {$montant}€
- Période : $dateDebut → $dateFin

Explique ce contrat en langage simple pour un créateur. Réponds UNIQUEMENT en JSON :
{
  \"resume_simple\": \"Explication claire en 2-3 phrases\",
  \"points_cles\": [\"point 1\", \"point 2\", \"point 3\"],
  \"avantages\": [\"avantage 1\", \"avantage 2\"],
  \"points_vigilance\": [\"attention 1\", \"attention 2\"],
  \"estimation_travail\": \"Estimation du temps de travail nécessaire\",
  \"verdict\": \"Recommandation : accepter / négocier / refuser avec justification\"
}

Ne retourne RIEN d'autre que le JSON.";

        $response = callOpenRouter($prompt);
        if (!$response) return null;
        $clean  = trim(preg_replace('/```json\s*|\s*```/', '', $response));
        $parsed = json_decode($clean, true);
        return (json_last_error() === JSON_ERROR_NONE) ? $parsed : null;
    }

    // ─── IA : VÉRIFIER CONTRAT (ADMIN) ────────────────────────────
    // Utilisée dans : Vue/BackOffice/contrat/index.php

    public function verifierContratIA(
        string $titre, string $description, float $montant, string $statut
    ): ?array {
        $prompt = "Tu es un auditeur de contrats pour l'admin de Cre8Connect.

Contrat à vérifier :
- Titre : $titre
- Description : $description
- Montant : {$montant}€
- Statut : $statut

Audite ce contrat. Réponds UNIQUEMENT en JSON :
{
  \"conformite\": \"conforme / non-conforme / à vérifier\",
  \"score_risque\": \"Note de 1 (faible) à 10 (élevé)\",
  \"problemes_detectes\": [\"problème 1\", \"problème 2\"],
  \"clauses_manquantes\": [\"clause 1\", \"clause 2\"],
  \"recommandations_admin\": [\"action 1\", \"action 2\"],
  \"montant_coherent\": \"oui/non avec justification\"
}

Ne retourne RIEN d'autre que le JSON.";

        $response = callOpenRouter($prompt);
        if (!$response) return null;
        $clean  = trim(preg_replace('/```json\s*|\s*```/', '', $response));
        $parsed = json_decode($clean, true);
        return (json_last_error() === JSON_ERROR_NONE) ? $parsed : null;
    }
}