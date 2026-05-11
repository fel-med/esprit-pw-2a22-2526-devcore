<?php

require_once __DIR__ . '/../Modele/contrat.php';
require_once __DIR__ . '/../config.php';

class ContratC
{
    private PDO $pdo;
    private array $columnCache = [];

    public function __construct()
    {
        $this->pdo = config::getConnexion();
    }

    // ─────────────────────────────────────────────
    // CRUD DE BASE
    // ─────────────────────────────────────────────

    private function normalizeIntOrNull($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        $int = (int) $value;
        return $int > 0 ? $int : null;
    }

    private function sourceJoinSql(): string
    {
        $candidatureColumn = $this->firstExistingColumn('contrat', ['id_candidature', 'idCandidature']);
        if (!$candidatureColumn) {
            return "
            LEFT JOIN campagne ca ON c.id_campagne = ca.idCampagne
            ";
        }

        return "
            LEFT JOIN candidature cand ON c.`{$candidatureColumn}` = cand.idCandidature
            LEFT JOIN offre off ON cand.origineCandidature = 'par_offre' AND off.idOffre = cand.idSource
            LEFT JOIN campagne ca ON ca.idCampagne = COALESCE(
                CASE WHEN cand.origineCandidature = 'par_campagne' THEN cand.idSource ELSE NULL END,
                c.id_campagne
            )
        ";
    }

    private function sourceSelectSql(): string
    {
        $candidatureColumn = $this->firstExistingColumn('contrat', ['id_candidature', 'idCandidature']);
        if (!$candidatureColumn) {
            return "ca.TitreCampagne AS titreCampagne";
        }

        return "
                   COALESCE(NULLIF(ca.TitreCampagne, ''), NULLIF(off.titre, '')) AS titreCampagne,
                   cand.idCandidature AS idCandidature,
                   cand.origineCandidature AS origineCandidature,
                   cand.idSource AS idSource,
                   off.titre AS titreOffre
        ";
    }

    public function getAll(): array
    {
        $sourceSelect = $this->sourceSelectSql();
        $sourceJoin = $this->sourceJoinSql();
        $stmt = $this->pdo->query("
            SELECT c.*,
                   u1.nom AS nomMarque,
                   u2.nom AS nomCreateur,
                   {$sourceSelect}
            FROM contrat c
            LEFT JOIN utilisateur u1 ON c.id_marque   = u1.id
            LEFT JOIN utilisateur u2 ON c.id_createur = u2.id
            {$sourceJoin}
            ORDER BY c.date_creation DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByMarque(int $idMarque): array
    {
        $sourceSelect = $this->sourceSelectSql();
        $sourceJoin = $this->sourceJoinSql();
        $stmt = $this->pdo->prepare("
            SELECT c.*,
                   u2.nom AS nomCreateur,
                   {$sourceSelect}
            FROM contrat c
            LEFT JOIN utilisateur u2 ON c.id_createur = u2.id
            {$sourceJoin}
            WHERE c.id_marque = :idMarque
            ORDER BY c.date_creation DESC
        ");
        $stmt->execute([':idMarque' => $idMarque]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByCreateur(int $idCreateur): array
    {
        $sourceSelect = $this->sourceSelectSql();
        $sourceJoin = $this->sourceJoinSql();
        $stmt = $this->pdo->prepare("
            SELECT c.*,
                   u1.nom AS nomMarque,
                   {$sourceSelect}
            FROM contrat c
            LEFT JOIN utilisateur u1 ON c.id_marque   = u1.id
            {$sourceJoin}
            WHERE c.id_createur = :idCreateur
            ORDER BY c.date_creation DESC
        ");
        $stmt->execute([':idCreateur' => $idCreateur]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $sourceSelect = $this->sourceSelectSql();
        $sourceJoin = $this->sourceJoinSql();
        $stmt = $this->pdo->prepare("
            SELECT c.*,
                   u1.nom AS nomMarque,
                   u2.nom AS nomCreateur,
                   {$sourceSelect}
            FROM contrat c
            LEFT JOIN utilisateur u1 ON c.id_marque   = u1.id
            LEFT JOIN utilisateur u2 ON c.id_createur = u2.id
            {$sourceJoin}
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
        if (!in_array($statut, $allowed, true)) return false;
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


    // ─────────────────────────────────────────────
    // CREATE FROM ACCEPTED CANDIDATURE
    // ─────────────────────────────────────────────

    private function tableHasColumn(string $table, string $column): bool
    {
        $key = strtolower($table . '.' . $column);
        if (array_key_exists($key, $this->columnCache)) {
            return $this->columnCache[$key];
        }

        try {
            $safeTable = str_replace('`', '``', $table);
            $stmt = $this->pdo->prepare("SHOW COLUMNS FROM `{$safeTable}` LIKE :column");
            $stmt->execute([':column' => $column]);
            $this->columnCache[$key] = (bool) $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            $this->columnCache[$key] = false;
        }

        return $this->columnCache[$key];
    }

    private function firstExistingColumn(string $table, array $columns): ?string
    {
        foreach ($columns as $column) {
            if ($this->tableHasColumn($table, $column)) {
                return $column;
            }
        }
        return null;
    }

    private function candidatureFromContext(array $context): ?object
    {
        return isset($context['condidature']) && is_object($context['condidature'])
            ? $context['condidature']
            : null;
    }

    public function supportsCandidatureOrigin(array $context): bool
    {
        $candidature = $this->candidatureFromContext($context);
        if (!$candidature || !method_exists($candidature, 'getOrigineCandidature')) {
            return false;
        }

        $origin = (string) $candidature->getOrigineCandidature();
        if ($origin === 'par_campagne') {
            return true;
        }

        if ($origin === 'par_offre') {
            return $this->firstExistingColumn('contrat', ['id_candidature', 'idCandidature']) !== null
                || $this->firstExistingColumn('contrat', ['id_offre', 'idOffre']) !== null
                || ($this->firstExistingColumn('contrat', ['origine_candidature', 'origineCandidature']) !== null
                    && $this->firstExistingColumn('contrat', ['id_source', 'idSource']) !== null);
        }

        return false;
    }

    public function contractExistsForCandidature(array $context, int $idMarque): bool
    {
        $candidature = $this->candidatureFromContext($context);
        if (!$candidature) {
            return false;
        }

        $idCandidature = method_exists($candidature, 'getIdCandidature') ? (int) $candidature->getIdCandidature() : 0;
        $idCreateur = method_exists($candidature, 'getIdCreateur') ? (int) $candidature->getIdCreateur() : 0;
        $origin = method_exists($candidature, 'getOrigineCandidature') ? (string) $candidature->getOrigineCandidature() : '';
        $idSource = method_exists($candidature, 'getIdSource') ? (int) $candidature->getIdSource() : 0;

        $candidatureColumn = $this->firstExistingColumn('contrat', ['id_candidature', 'idCandidature']);
        if ($candidatureColumn && $idCandidature > 0) {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM contrat WHERE `{$candidatureColumn}` = :idCandidature AND id_marque = :idMarque");
            $stmt->execute([
                ':idCandidature' => $idCandidature,
                ':idMarque' => $idMarque,
            ]);
            if ((int) $stmt->fetchColumn() > 0) {
                return true;
            }
        }

        if ($origin === 'par_campagne' && $idSource > 0 && $idCreateur > 0) {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM contrat WHERE id_marque = :idMarque AND id_createur = :idCreateur AND id_campagne = :idCampagne");
            $stmt->execute([
                ':idMarque' => $idMarque,
                ':idCreateur' => $idCreateur,
                ':idCampagne' => $idSource,
            ]);
            return (int) $stmt->fetchColumn() > 0;
        }

        $offerColumn = $this->firstExistingColumn('contrat', ['id_offre', 'idOffre']);
        if ($origin === 'par_offre' && $offerColumn && $idSource > 0 && $idCreateur > 0) {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM contrat WHERE id_marque = :idMarque AND id_createur = :idCreateur AND `{$offerColumn}` = :idOffre");
            $stmt->execute([
                ':idMarque' => $idMarque,
                ':idCreateur' => $idCreateur,
                ':idOffre' => $idSource,
            ]);
            if ((int) $stmt->fetchColumn() > 0) {
                return true;
            }
        }

        // Legacy safety fallback: old offer contracts created before id_candidature existed
        // cannot be matched exactly. We only use this when no exact id_candidature link exists.
        if ($origin === 'par_offre' && $idCreateur > 0) {
            $amount = $this->getAmountFromCandidatureContext($context);
            $sql = "SELECT COUNT(*) FROM contrat
                    WHERE id_marque = :idMarque
                      AND id_createur = :idCreateur
                      AND ABS(montant - :montant) < 0.01";
            $params = [
                ':idMarque' => $idMarque,
                ':idCreateur' => $idCreateur,
                ':montant' => $amount,
            ];
            if ($candidatureColumn) {
                $sql .= " AND (`{$candidatureColumn}` IS NULL OR `{$candidatureColumn}` = 0)";
            }
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return (int) $stmt->fetchColumn() > 0;
        }

        return false;
    }

    public function getAmountFromCandidatureContext(array $context): float
    {
        $candidature = $this->candidatureFromContext($context);
        $candidateAmount = 0.0;
        if ($candidature && method_exists($candidature, 'getBudgetPropose')) {
            $candidateAmount = (float) $candidature->getBudgetPropose();
        }

        if ($candidateAmount > 0) {
            return $candidateAmount;
        }

        return isset($context['source']['budgetPropose']) ? (float) $context['source']['budgetPropose'] : 0.0;
    }

    public function createFromCandidatureContext(array $context, int $idMarque, string $titre, string $description, string $dateDebut, string $dateFin): bool
    {
        $candidature = $this->candidatureFromContext($context);
        if (!$candidature) {
            throw new RuntimeException('Selected application was not found.');
        }

        $idCandidature = method_exists($candidature, 'getIdCandidature') ? (int) $candidature->getIdCandidature() : 0;
        $idCreateur = method_exists($candidature, 'getIdCreateur') ? (int) $candidature->getIdCreateur() : 0;
        $origin = method_exists($candidature, 'getOrigineCandidature') ? (string) $candidature->getOrigineCandidature() : '';
        $idSource = method_exists($candidature, 'getIdSource') ? (int) $candidature->getIdSource() : 0;
        $montant = $this->getAmountFromCandidatureContext($context);

        if ($idCandidature <= 0 || $idCreateur <= 0 || $idSource <= 0 || $idMarque <= 0) {
            throw new RuntimeException('Invalid application data.');
        }

        if (!$this->supportsCandidatureOrigin($context)) {
            throw new RuntimeException('This application source is not supported by the current contract table. Run the contract migration first.');
        }

        $candidatureColumn = $this->firstExistingColumn('contrat', ['id_candidature', 'idCandidature']);
        $idCampagne = $origin === 'par_campagne' ? $idSource : null;

        $columns = [
            'id_campagne' => $idCampagne,
            'id_marque' => $idMarque,
            'id_createur' => $idCreateur,
            'titre' => $titre,
            'description' => $description,
            'montant' => $montant,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'statut' => 'en_attente',
            'date_creation' => date('Y-m-d H:i:s'),
            'fichier_pdf' => null,
        ];

        if ($candidatureColumn) {
            $columns[$candidatureColumn] = $idCandidature;
        }

        $originColumn = $this->firstExistingColumn('contrat', ['origine_candidature', 'origineCandidature', 'source_type']);
        if ($originColumn) {
            $columns[$originColumn] = $origin;
        }

        $sourceColumn = $this->firstExistingColumn('contrat', ['id_source', 'idSource']);
        if ($sourceColumn) {
            $columns[$sourceColumn] = $idSource;
        }

        if ($origin === 'par_offre') {
            $offerColumn = $this->firstExistingColumn('contrat', ['id_offre', 'idOffre']);
            if ($offerColumn) {
                $columns[$offerColumn] = $idSource;
            }
        }

        $fieldNames = array_keys($columns);
        $sql = 'INSERT INTO contrat (`' . implode('`, `', $fieldNames) . '`) VALUES (:' . implode(', :', $fieldNames) . ')';
        $params = [];
        foreach ($columns as $field => $value) {
            $params[':' . $field] = $value;
        }

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
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