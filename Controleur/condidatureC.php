<?php

require_once __DIR__ . '/../Modele/condidature.php';
require_once __DIR__ . '/../config.php';

class CondidatureC
{
    private $pdo;
    private const MODULE_TIMEZONE = 'Africa/Tunis';
    private $negotiationTableExists = null;

    public function __construct()
    {
        $this->pdo = config::getConnexion();
    }

    private function getModuleTimezone()
    {
        return new DateTimeZone(self::MODULE_TIMEZONE);
    }

    private function todayDate()
    {
        return (new DateTime('today', $this->getModuleTimezone()))->format('Y-m-d');
    }

    private function nowDateTime()
    {
        return (new DateTime('now', $this->getModuleTimezone()))->format('Y-m-d H:i:s');
    }

    private function rowToCondidature(array $row)
    {
        return Condidature::fromArray($row);
    }

    private function mapContextRow(array $row)
    {
        $condidature = $this->rowToCondidature($row);
        $origin = (string) $condidature->getOrigineCandidature();
        $sourceId = (int) $condidature->getIdSource();
        $sourceTitle = trim((string) ($row['sourceTitle'] ?? ''));

        if ($sourceTitle === '') {
            $sourceTitle = $origin === 'par_campagne'
                ? 'Campaign source #' . $sourceId
                : 'Offer source #' . $sourceId;
        }

        return [
            'condidature' => $condidature,
            'creator' => [
                'id' => isset($row['creatorId']) ? (int) $row['creatorId'] : (int) $condidature->getIdCreateur(),
                'nom' => (string) ($row['creatorName'] ?? ''),
                'email' => (string) ($row['creatorEmail'] ?? ''),
            ],
            'brand' => [
                'id' => isset($row['brandId']) ? (int) $row['brandId'] : null,
                'nom' => (string) ($row['brandName'] ?? ''),
                'email' => (string) ($row['brandEmail'] ?? ''),
            ],
            'source' => [
                'origin' => $origin,
                'id' => $sourceId,
                'title' => $sourceTitle,
                'objective' => (string) ($row['sourceObjective'] ?? ''),
                'description' => (string) ($row['sourceDescription'] ?? ''),
                'budgetPropose' => isset($row['sourceBudget']) ? (float) $row['sourceBudget'] : null,
                'datePublication' => $row['sourcePublicationDate'] ?? null,
                'dateLimite' => $row['sourceDeadline'] ?? null,
                'status' => $row['sourceStatus'] ?? null,
            ],
            'response' => [
                'mode' => $condidature->getResponseMode(),
                'type' => $condidature->getTypeReponse(),
                'label' => $condidature->getDisplayStatusLabel(),
            ],
        ];
    }

    private function hydrateContexts($statement)
    {
        $items = [];
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $items[] = $this->mapContextRow($row);
        }

        return $items;
    }

    private function getContextBaseQuery()
    {
        return "
            SELECT
                c.*,
                cu.id AS creatorId,
                cu.nom AS creatorName,
                cu.email AS creatorEmail,
                o.idOffre AS sourceOfferId,
                cp.idCampagne AS sourceCampaignId,
                COALESCE(NULLIF(o.titre, ''), NULLIF(cp.titre, '')) AS sourceTitle,
                COALESCE(NULLIF(o.objectif, ''), NULLIF(cp.description, '')) AS sourceObjective,
                COALESCE(NULLIF(o.description, ''), NULLIF(cp.description, '')) AS sourceDescription,
                COALESCE(o.budgetPropose, 0) AS sourceBudget,
                COALESCE(o.datePublication, cp.dateDebut) AS sourcePublicationDate,
                COALESCE(o.dateLimite, cp.dateFin) AS sourceDeadline,
                COALESCE(o.statutOffre, cp.statut) AS sourceStatus,
                COALESCE(om.id, cm.id) AS brandId,
                COALESCE(om.nom, cm.nom) AS brandName,
                COALESCE(om.email, cm.email) AS brandEmail
            FROM candidature c
            LEFT JOIN utilisateur cu ON cu.id = c.idCreateur
            LEFT JOIN offre o ON c.origineCandidature = 'par_offre' AND o.idOffre = c.idSource
            LEFT JOIN campagne cp ON c.origineCandidature = 'par_campagne' AND cp.idCampagne = c.idSource
            LEFT JOIN utilisateur om ON om.id = o.idMarque
            LEFT JOIN utilisateur cm ON cm.id = cp.idMarque
        ";
    }

    private function getCreatorStatusRankSql($field = 'c.statutCandidature')
    {
        return "
            CASE {$field}
                WHEN 'brouillon' THEN 0
                WHEN 'negociation' THEN 1
                WHEN 'envoyee' THEN 2
                WHEN 'en_etude' THEN 3
                WHEN 'acceptee' THEN 4
                WHEN 'refusee' THEN 5
                WHEN 'retiree' THEN 6
                ELSE 7
            END
        ";
    }

    private function getAdminStatusRankSql($field = 'c.statutCandidature')
    {
        return "
            CASE {$field}
                WHEN 'envoyee' THEN 0
                WHEN 'en_etude' THEN 1
                WHEN 'negociation' THEN 2
                WHEN 'brouillon' THEN 3
                WHEN 'acceptee' THEN 4
                WHEN 'refusee' THEN 5
                WHEN 'retiree' THEN 6
                ELSE 7
            END
        ";
    }

    private function normalizeNegotiationAuthor($author)
    {
        $author = strtolower(trim((string) $author));

        return in_array($author, ['createur', 'marque'], true) ? $author : null;
    }

    private function negotiationTableExists()
    {
        if ($this->negotiationTableExists !== null) {
            return $this->negotiationTableExists;
        }

        try {
            $this->pdo->query('SELECT 1 FROM negociation_candidature LIMIT 1');
            $this->negotiationTableExists = true;
        } catch (Throwable $exception) {
            $this->negotiationTableExists = false;
        }

        return $this->negotiationTableExists;
    }

    private function normalizeNegotiationRow(array $row)
    {
        return [
            'idNegociation' => isset($row['idNegociation']) ? (int) $row['idNegociation'] : null,
            'idCandidature' => isset($row['idCandidature']) ? (int) $row['idCandidature'] : null,
            'auteur' => $this->normalizeNegotiationAuthor($row['auteur'] ?? '') ?: 'createur',
            'message' => trim((string) ($row['message'] ?? '')),
            'budgetPropose' => isset($row['budgetPropose']) && $row['budgetPropose'] !== '' ? (float) $row['budgetPropose'] : null,
            'delaiPropose' => isset($row['delaiPropose']) && $row['delaiPropose'] !== '' ? (int) $row['delaiPropose'] : null,
            'dateMessage' => $row['dateMessage'] ?? null,
        ];
    }

    private function getNegotiationHistoryMap(array $candidatureIds)
    {
        $candidatureIds = array_values(array_unique(array_map('intval', array_filter($candidatureIds, static fn($id) => (int) $id > 0))));
        if (empty($candidatureIds) || !$this->negotiationTableExists()) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($candidatureIds), '?'));
        $stmt = $this->pdo->prepare("
            SELECT
                idNegociation,
                idCandidature,
                auteur,
                message,
                budgetPropose,
                delaiPropose,
                dateMessage
            FROM negociation_candidature
            WHERE idCandidature IN ({$placeholders})
            ORDER BY dateMessage ASC, idNegociation ASC
        ");
        $stmt->execute($candidatureIds);

        $historyMap = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $normalized = $this->normalizeNegotiationRow($row);
            $historyMap[(int) $normalized['idCandidature']][] = $normalized;
        }

        return $historyMap;
    }

    private function attachNegotiationDataToContexts(array $contexts, $withHistory = false)
    {
        if (empty($contexts)) {
            return $contexts;
        }

        $historyMap = $this->getNegotiationHistoryMap(array_map(
            static fn($context) => (int) $context['condidature']->getIdCandidature(),
            $contexts
        ));

        foreach ($contexts as &$context) {
            $idCandidature = (int) $context['condidature']->getIdCandidature();
            $rawHistory = $historyMap[$idCandidature] ?? [];
            $history = [];
            $latestCreator = null;
            $latestBrand = null;

            foreach ($rawHistory as $entry) {
                $authorKey = $entry['auteur'] === 'marque' ? 'brand' : 'creator';
                $authorContext = $context[$authorKey] ?? [];
                $authorName = trim((string) ($authorContext['nom'] ?? ''));
                $authorEmail = trim((string) ($authorContext['email'] ?? ''));

                $history[] = $entry + [
                    'authorRoleLabel' => $entry['auteur'] === 'marque' ? 'Brand' : 'Creator',
                    'authorName' => $authorName !== '' ? $authorName : ($entry['auteur'] === 'marque' ? 'Brand' : 'Creator'),
                    'authorEmail' => $authorEmail,
                ];

                if ($entry['auteur'] === 'marque') {
                    $latestBrand = $history[array_key_last($history)];
                } else {
                    $latestCreator = $history[array_key_last($history)];
                }
            }

            $latest = !empty($history) ? $history[array_key_last($history)] : null;
            $context['negotiation'] = [
                'count' => count($history),
                'latest' => $latest,
                'latestCreator' => $latestCreator,
                'latestBrand' => $latestBrand,
                'history' => $withHistory ? $history : [],
            ];
        }
        unset($context);

        return $contexts;
    }

    private function attachNegotiationDataToContext($context, $withHistory = true)
    {
        if (!$context) {
            return null;
        }

        $contexts = $this->attachNegotiationDataToContexts([$context], $withHistory);

        return $contexts[0] ?? $context;
    }

    private function insertNegotiationMessage($idCandidature, $author, $message, $budgetPropose = null, $delaiPropose = null, $dateMessage = null)
    {
        $author = $this->normalizeNegotiationAuthor($author);
        if (!$author) {
            throw new InvalidArgumentException('Invalid negotiation author.');
        }

        if (!$this->negotiationTableExists()) {
            throw new RuntimeException('Negotiation history storage is not available.');
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO negociation_candidature (
                idCandidature,
                auteur,
                message,
                budgetPropose,
                delaiPropose,
                dateMessage
            ) VALUES (
                :idCandidature,
                :auteur,
                :message,
                :budgetPropose,
                :delaiPropose,
                :dateMessage
            )
        ");
        $stmt->execute([
            'idCandidature' => (int) $idCandidature,
            'auteur' => $author,
            'message' => trim((string) $message),
            'budgetPropose' => $budgetPropose !== null && $budgetPropose !== '' ? (float) $budgetPropose : null,
            'delaiPropose' => $delaiPropose !== null && $delaiPropose !== '' ? (int) $delaiPropose : null,
            'dateMessage' => $dateMessage ?: $this->nowDateTime(),
        ]);
    }

    public function getUsersByIds(array $ids, $role = null)
    {
        $ids = array_values(array_unique(array_map('intval', array_filter($ids, static fn($id) => (int) $id > 0))));
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $sql = 'SELECT id, nom, email, role, statut, date_creation FROM utilisateur WHERE id IN (' . $placeholders . ')';
        $params = $ids;

        if ($role !== null) {
            $sql .= ' AND role = ?';
            $params[] = $role;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $users = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $users[(int) $row['id']] = $row;
        }

        return $users;
    }

    public function getUsersByRole($role)
    {
        $stmt = $this->pdo->prepare("
            SELECT id, nom, email, role, statut, date_creation
            FROM utilisateur
            WHERE role = :role AND statut != :blocked
            ORDER BY
                CASE statut
                    WHEN 'actif' THEN 0
                    WHEN 'en_attente' THEN 1
                    ELSE 2
                END,
                nom ASC,
                id ASC
        ");
        $stmt->execute([
            'role' => $role,
            'blocked' => 'bloque',
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDefaultUserByRole($role)
    {
        $users = $this->getUsersByRole($role);

        return $users[0] ?? null;
    }

    public function getOfferSourceById($idSource, $idCreateur = null, $allowHidden = false)
    {
        $sql = "
            SELECT
                o.*,
                m.id AS brandId,
                m.nom AS brandName,
                m.email AS brandEmail
            FROM offre o
            LEFT JOIN utilisateur m ON m.id = o.idMarque
            WHERE o.idOffre = :idSource
        ";
        $params = [
            'idSource' => (int) $idSource,
        ];

        if ($idCreateur !== null) {
            $sql .= ' AND o.idCreateurCible = :idCreateur';
            $params['idCreateur'] = (int) $idCreateur;

            if (!$allowHidden) {
                $sql .= " AND o.statutOffre = 'publiee' AND o.datePublication <= CURDATE()";
            }
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return [
            'origin' => 'par_offre',
            'id' => (int) ($row['idOffre'] ?? 0),
            'title' => (string) ($row['titre'] ?? ''),
            'objective' => (string) ($row['objectif'] ?? ''),
            'description' => (string) ($row['description'] ?? ''),
            'budgetPropose' => isset($row['budgetPropose']) ? (float) $row['budgetPropose'] : 0.0,
            'datePublication' => $row['datePublication'] ?? null,
            'dateLimite' => $row['dateLimite'] ?? null,
            'status' => $row['statutOffre'] ?? null,
            'brand' => [
                'id' => isset($row['brandId']) ? (int) $row['brandId'] : (isset($row['idMarque']) ? (int) $row['idMarque'] : null),
                'nom' => (string) ($row['brandName'] ?? ''),
                'email' => (string) ($row['brandEmail'] ?? ''),
            ],
        ];
    }

    public function getCampaignSourceById($idSource)
    {
        $stmt = $this->pdo->prepare("
            SELECT
                c.idCampagne,
                c.idMarque,
                c.titre,
                c.description,
                c.dateDebut,
                c.dateFin,
                c.statut,
                m.id AS brandId,
                m.nom AS brandName,
                m.email AS brandEmail
            FROM campagne c
            LEFT JOIN utilisateur m ON m.id = c.idMarque
            WHERE c.idCampagne = :idSource
            LIMIT 1
        ");
        $stmt->execute([
            'idSource' => (int) $idSource,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return [
            'origin' => 'par_campagne',
            'id' => (int) ($row['idCampagne'] ?? 0),
            'title' => (string) ($row['titre'] ?? ''),
            'objective' => (string) ($row['description'] ?? ''),
            'description' => (string) ($row['description'] ?? ''),
            'budgetPropose' => 0.0,
            'datePublication' => $row['dateDebut'] ?? null,
            'dateLimite' => $row['dateFin'] ?? null,
            'status' => $row['statut'] ?? null,
            'brand' => [
                'id' => isset($row['brandId']) ? (int) $row['brandId'] : (isset($row['idMarque']) ? (int) $row['idMarque'] : null),
                'nom' => (string) ($row['brandName'] ?? ''),
                'email' => (string) ($row['brandEmail'] ?? ''),
            ],
        ];
    }

    public function getSourceContext($origin, $idSource, $idCreateur = null, $allowHidden = false)
    {
        return match ((string) $origin) {
            'par_offre' => $this->getOfferSourceById($idSource, $idCreateur, $allowHidden),
            'par_campagne' => $this->getCampaignSourceById($idSource),
            default => null,
        };
    }

    public function getCreatorOfferFeed($idCreateur, array $filters = [])
    {
        $sql = "
            SELECT
                o.idOffre,
                o.idMarque,
                o.idCreateurCible,
                o.titre,
                o.description,
                o.objectif,
                o.budgetPropose,
                o.datePublication,
                o.dateLimite,
                o.statutOffre,
                m.nom AS brandName,
                m.email AS brandEmail
            FROM offre o
            LEFT JOIN utilisateur m ON m.id = o.idMarque
            WHERE o.idCreateurCible = :idCreateur
              AND o.statutOffre = 'publiee'
              AND o.datePublication <= CURDATE()
        ";
        $params = [
            'idCreateur' => (int) $idCreateur,
        ];

        $keyword = trim((string) ($filters['keyword'] ?? ''));
        if ($keyword !== '') {
            $sql .= ' AND (o.titre LIKE :keyword OR o.objectif LIKE :keyword OR o.description LIKE :keyword OR m.nom LIKE :keyword)';
            $params['keyword'] = '%' . $keyword . '%';
        }

        $sql .= ' ORDER BY o.dateLimite ASC, o.datePublication DESC, o.idOffre DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sourceIds = array_map(static fn($row) => (int) $row['idOffre'], $rows);
        $candidatureMap = $this->getCreatorCandidaturesBySourceIds($idCreateur, 'par_offre', $sourceIds);

        $items = [];
        foreach ($rows as $row) {
            $sourceId = (int) $row['idOffre'];
            $items[] = [
                'source' => [
                    'origin' => 'par_offre',
                    'id' => $sourceId,
                    'title' => (string) ($row['titre'] ?? ''),
                    'objective' => (string) ($row['objectif'] ?? ''),
                    'description' => (string) ($row['description'] ?? ''),
                    'budgetPropose' => isset($row['budgetPropose']) ? (float) $row['budgetPropose'] : 0.0,
                    'datePublication' => $row['datePublication'] ?? null,
                    'dateLimite' => $row['dateLimite'] ?? null,
                    'status' => $row['statutOffre'] ?? null,
                ],
                'brand' => [
                    'id' => isset($row['idMarque']) ? (int) $row['idMarque'] : null,
                    'nom' => (string) ($row['brandName'] ?? ''),
                    'email' => (string) ($row['brandEmail'] ?? ''),
                ],
                'condidature' => $candidatureMap[$sourceId]['condidature'] ?? null,
                'context' => $candidatureMap[$sourceId] ?? null,
            ];
        }

        return $items;
    }

    public function getCreatorCandidaturesBySourceIds($idCreateur, $origin, array $sourceIds)
    {
        $sourceIds = array_values(array_unique(array_map('intval', array_filter($sourceIds, static fn($id) => (int) $id > 0))));
        if (empty($sourceIds)) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($sourceIds), '?'));
        $sql = $this->getContextBaseQuery() . "
            WHERE c.idCreateur = ?
              AND c.origineCandidature = ?
              AND c.idSource IN ({$placeholders})
            ORDER BY c.dateDerniereModification DESC, c.idCandidature DESC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_merge([(int) $idCreateur, $origin], $sourceIds));

        $map = [];
        foreach ($this->hydrateContexts($stmt) as $context) {
            $map[(int) $context['condidature']->getIdSource()] = $context;
        }

        return $map;
    }

    public function getCreatorCandidatures($idCreateur, array $filters = [])
    {
        $sql = $this->getContextBaseQuery() . '
            WHERE c.idCreateur = :idCreateur
        ';
        $params = [
            'idCreateur' => (int) $idCreateur,
        ];

        $keyword = trim((string) ($filters['keyword'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));
        $origin = trim((string) ($filters['origin'] ?? ''));
        $editableOnly = !empty($filters['editableOnly']);

        if ($keyword !== '') {
            $sql .= ' AND (
                COALESCE(o.titre, cp.titre) LIKE :keyword
                OR COALESCE(o.objectif, cp.description) LIKE :keyword
                OR COALESCE(o.description, cp.description) LIKE :keyword
                OR c.messageMotivation LIKE :keyword
                OR c.noteDecision LIKE :keyword
                OR COALESCE(om.nom, cm.nom) LIKE :keyword
            )';
            $params['keyword'] = '%' . $keyword . '%';
        }

        if ($status !== '') {
            $sql .= ' AND c.statutCandidature = :status';
            $params['status'] = $status;
        }

        if ($origin !== '') {
            $sql .= ' AND c.origineCandidature = :origin';
            $params['origin'] = $origin;
        }

        if ($editableOnly) {
            $sql .= " AND c.statutCandidature IN ('brouillon', 'negociation')";
        }

        $sql .= '
            ORDER BY
                ' . $this->getCreatorStatusRankSql() . ',
                c.dateDerniereModification DESC,
                c.dateCandidature DESC,
                c.idCandidature DESC
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $this->attachNegotiationDataToContexts($this->hydrateContexts($stmt), false);
    }

    public function getCreatorCandidatureById($idCandidature, $idCreateur)
    {
        $sql = $this->getContextBaseQuery() . '
            WHERE c.idCandidature = :idCandidature
              AND c.idCreateur = :idCreateur
            LIMIT 1
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'idCandidature' => (int) $idCandidature,
            'idCreateur' => (int) $idCreateur,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->attachNegotiationDataToContext($this->mapContextRow($row), true) : null;
    }

    public function getCreatorCandidatureBySource($idCreateur, $origin, $idSource)
    {
        $sql = $this->getContextBaseQuery() . '
            WHERE c.idCreateur = :idCreateur
              AND c.origineCandidature = :origin
              AND c.idSource = :idSource
            ORDER BY c.dateDerniereModification DESC, c.idCandidature DESC
            LIMIT 1
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'idCreateur' => (int) $idCreateur,
            'origin' => $origin,
            'idSource' => (int) $idSource,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->attachNegotiationDataToContext($this->mapContextRow($row), true) : null;
    }

    public function getBrandCandidatures($idMarque, array $filters = [])
    {
        $sql = $this->getContextBaseQuery() . '
            WHERE COALESCE(o.idMarque, cp.idMarque) = :idMarque
        ';
        $params = [
            'idMarque' => (int) $idMarque,
        ];

        $keyword = trim((string) ($filters['keyword'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));
        $origin = trim((string) ($filters['origin'] ?? ''));

        if ($keyword !== '') {
            $sql .= ' AND (COALESCE(o.titre, cp.titre) LIKE :keyword OR COALESCE(o.description, cp.description) LIKE :keyword OR c.messageMotivation LIKE :keyword OR cu.nom LIKE :keyword)';
            $params['keyword'] = '%' . $keyword . '%';
        }

        if ($status !== '') {
            $sql .= ' AND c.statutCandidature = :status';
            $params['status'] = $status;
        }

        if ($origin !== '') {
            $sql .= ' AND c.origineCandidature = :origin';
            $params['origin'] = $origin;
        }

        $sql .= '
            ORDER BY
                ' . $this->getAdminStatusRankSql() . ',
                c.dateDerniereModification DESC,
                c.dateCandidature DESC,
                c.idCandidature DESC
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $this->attachNegotiationDataToContexts($this->hydrateContexts($stmt), false);
    }

    public function getBrandCandidatureById($idCandidature, $idMarque)
    {
        $sql = $this->getContextBaseQuery() . '
            WHERE c.idCandidature = :idCandidature
              AND COALESCE(o.idMarque, cp.idMarque) = :idMarque
            LIMIT 1
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'idCandidature' => (int) $idCandidature,
            'idMarque' => (int) $idMarque,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->attachNegotiationDataToContext($this->mapContextRow($row), true) : null;
    }

    public function getAdminCandidatures(array $filters = [])
    {
        $sql = $this->getContextBaseQuery() . '
            WHERE 1 = 1
        ';
        $params = [];

        $keyword = trim((string) ($filters['keyword'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));
        $origin = trim((string) ($filters['origin'] ?? ''));
        $creatorId = trim((string) ($filters['creatorId'] ?? ''));
        $brandId = trim((string) ($filters['brandId'] ?? ''));
        $dateFrom = trim((string) ($filters['dateFrom'] ?? ''));

        if ($keyword !== '') {
            $sql .= ' AND (
                COALESCE(o.titre, cp.titre) LIKE :keyword
                OR COALESCE(o.objectif, cp.description) LIKE :keyword
                OR COALESCE(o.description, cp.description) LIKE :keyword
                OR c.messageMotivation LIKE :keyword
                OR cu.nom LIKE :keyword
                OR COALESCE(om.nom, cm.nom) LIKE :keyword
            )';
            $params['keyword'] = '%' . $keyword . '%';
        }

        if ($status !== '') {
            $sql .= ' AND c.statutCandidature = :status';
            $params['status'] = $status;
        }

        if ($origin !== '') {
            $sql .= ' AND c.origineCandidature = :origin';
            $params['origin'] = $origin;
        }

        if ($creatorId !== '' && is_numeric($creatorId)) {
            $sql .= ' AND c.idCreateur = :creatorId';
            $params['creatorId'] = (int) $creatorId;
        }

        if ($brandId !== '' && is_numeric($brandId)) {
            $sql .= ' AND o.idMarque = :brandId';
            $params['brandId'] = (int) $brandId;
        }

        if ($dateFrom !== '') {
            $sql .= ' AND c.dateCandidature >= :dateFrom';
            $params['dateFrom'] = $dateFrom;
        }

        $sql .= '
            ORDER BY
                ' . $this->getAdminStatusRankSql() . ',
                c.dateDerniereModification DESC,
                c.dateCandidature DESC,
                c.idCandidature DESC
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $this->attachNegotiationDataToContexts($this->hydrateContexts($stmt), false);
    }

    public function getAdminCandidatureById($idCandidature)
    {
        $sql = $this->getContextBaseQuery() . '
            WHERE c.idCandidature = :idCandidature
            LIMIT 1
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'idCandidature' => (int) $idCandidature,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->attachNegotiationDataToContext($this->mapContextRow($row), true) : null;
    }

    public function summarizeContexts(array $contexts)
    {
        $summary = [
            'total' => count($contexts),
            'brouillon' => 0,
            'envoyee' => 0,
            'en_etude' => 0,
            'negociation' => 0,
            'acceptee' => 0,
            'refusee' => 0,
            'retiree' => 0,
            'editable' => 0,
            'averageBudget' => 0,
            'offerResponses' => 0,
            'acceptation' => 0,
            'negociationReponse' => 0,
            'refus' => 0,
            'negotiationMessages' => 0,
        ];

        $budgetPool = [];

        foreach ($contexts as $context) {
            $condidature = $context['condidature'];
            $status = (string) $condidature->getStatutCandidature();
            if (!isset($summary[$status])) {
                $summary[$status] = 0;
            }

            $summary[$status]++;
            if ($condidature->canCreatorEdit()) {
                $summary['editable']++;
            }

            if ($condidature->isOfferResponse()) {
                $summary['offerResponses']++;
            }

            $responseType = $condidature->getTypeReponse();
            if ($responseType === 'acceptation') {
                $summary['acceptation']++;
            } elseif ($responseType === 'negociation') {
                $summary['negociationReponse']++;
            } elseif ($responseType === 'refus') {
                $summary['refus']++;
            }

            $summary['negotiationMessages'] += (int) (($context['negotiation']['count'] ?? 0));

            if ($condidature->getBudgetPropose() !== null && $condidature->getBudgetPropose() !== '') {
                $budgetPool[] = (float) $condidature->getBudgetPropose();
            }
        }

        if (!empty($budgetPool)) {
            $summary['averageBudget'] = array_sum($budgetPool) / count($budgetPool);
        }

        return $summary;
    }

    public function getDecisionStatusOptions()
    {
        return ['en_etude', 'negociation', 'acceptee', 'refusee'];
    }

    public function getCreatorResponseModeOptions()
    {
        return ['accept', 'negotiate', 'decline'];
    }

    private function isValidIsoDate($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return false;
        }

        $date = DateTime::createFromFormat('Y-m-d', $value, $this->getModuleTimezone());

        return $date instanceof DateTime && $date->format('Y-m-d') === $value;
    }

    private function hasNegotiationDelta($message, $budgetPropose, $delaiPropose, $baselineMessage = '', $baselineBudget = null, $baselineDelay = null)
    {
        $incomingMessage = trim((string) $message);
        $messageChanged = $incomingMessage !== '' && $incomingMessage !== trim((string) $baselineMessage);

        $budgetChanged = $budgetPropose !== ''
            && is_numeric($budgetPropose)
            && (float) $budgetPropose !== (float) $baselineBudget;

        $delayChanged = $delaiPropose !== ''
            && is_numeric($delaiPropose)
            && (int) $delaiPropose !== (int) $baselineDelay;

        return $messageChanged || $budgetChanged || $delayChanged;
    }

    public function validateCreatorCandidature(array $data, $intent = 'draft', $source = null, Condidature $existing = null)
    {
        $errors = [];
        $intent = strtolower(trim((string) $intent));
        $responseMode = strtolower(trim((string) ($data['responseMode'] ?? '')));
        $messageMotivation = trim((string) ($data['messageMotivation'] ?? ''));
        $budgetPropose = trim((string) ($data['budgetPropose'] ?? ''));
        $delaiPropose = trim((string) ($data['delaiPropose'] ?? ''));
        $dateDisponibilite = trim((string) ($data['dateDisponibilite'] ?? ''));
        $conditionsCreateur = trim((string) ($data['conditionsCreateur'] ?? ''));
        $cvPath = trim((string) ($data['cvPath'] ?? ''));
        $portfolioUrl = trim((string) ($data['portfolioUrl'] ?? ''));
        $motifRefus = trim((string) ($data['motifRefus'] ?? ''));
        $origin = (string) ($source['origin'] ?? ($existing ? $existing->getOrigineCandidature() : ''));
        $isOfferFlow = $origin === 'par_offre';
        $negotiationOnly = $existing && $existing->canCreatorEditNegotiationOnly();

        if ($source === null && $intent !== 'review') {
            $errors[] = 'The targeted source is not available for this candidature.';
            return $errors;
        }

        if ($existing && $existing->isCreatorLocked()) {
            $errors[] = 'This candidature is locked and can no longer be edited by the creator.';
            return $errors;
        }

        if (strlen($messageMotivation) > 2500) {
            $errors[] = 'Your message must stay under 2500 characters.';
        }

        if (strlen($conditionsCreateur) > 2000) {
            $errors[] = 'Creator terms must stay under 2000 characters.';
        }

        if (strlen($cvPath) > 255) {
            $errors[] = 'The CV field must stay under 255 characters.';
        }

        if (strlen($portfolioUrl) > 255) {
            $errors[] = 'The portfolio URL must stay under 255 characters.';
        }

        if (strlen($motifRefus) > 1500) {
            $errors[] = 'The refusal reason must stay under 1500 characters.';
        }

        if ($intent === 'draft') {
            return $errors;
        }

        if ($intent === 'final_accept') {
            if (!$existing || !$existing->canCreatorFinalizeNegotiation()) {
                $errors[] = 'Final acceptance is only available while a candidature is in negotiation.';
            }

            return $errors;
        }

        if ($intent === 'final_decline') {
            if (!$existing || !$existing->canCreatorFinalizeNegotiation()) {
                $errors[] = 'Final withdrawal is only available while a candidature is in negotiation.';
            }

            return $errors;
        }

        if ($intent === 'send') {
            if ($portfolioUrl !== '' && filter_var($portfolioUrl, FILTER_VALIDATE_URL) === false) {
                $errors[] = 'Enter a valid portfolio URL.';
            }

            if ($dateDisponibilite !== '') {
                if (!$this->isValidIsoDate($dateDisponibilite)) {
                    $errors[] = 'Choose a valid availability start date.';
                } elseif ($dateDisponibilite < $this->todayDate()) {
                    $errors[] = 'Availability start date cannot be in the past.';
                }
            }

            if (!in_array($responseMode, $this->getCreatorResponseModeOptions(), true)) {
                $errors[] = 'Choose how you want to respond before submitting the candidature.';
                return $errors;
            }

            if ($negotiationOnly && $responseMode !== 'negotiate') {
                $errors[] = 'This candidature is currently in negotiation, so only negotiation updates are allowed.';
                return $errors;
            }

            if ($negotiationOnly) {
                if (!$this->hasNegotiationDelta(
                    $messageMotivation,
                    $budgetPropose,
                    $delaiPropose,
                    $existing ? $existing->getMessageMotivation() : '',
                    $existing ? $existing->getBudgetPropose() : null,
                    $existing ? $existing->getDelaiPropose() : null
                )) {
                    $errors[] = 'Change the negotiation message, budget, or timeline before sending another negotiation step. Use the final acceptance action when you already agree with the latest terms.';
                }

                if ($budgetPropose !== '' && (!is_numeric($budgetPropose) || (float) $budgetPropose <= 0)) {
                    $errors[] = 'Enter a valid proposed budget for negotiation.';
                }

                if ($delaiPropose !== '' && (!is_numeric($delaiPropose) || (int) $delaiPropose <= 0)) {
                    $errors[] = 'Enter a valid delivery timeline in days.';
                }

                return $errors;
            }

            if ($responseMode === 'decline') {
                return $errors;
            }

            if ($delaiPropose === '' || !is_numeric($delaiPropose) || (int) $delaiPropose <= 0) {
                $errors[] = $responseMode === 'negotiate'
                    ? 'Enter a valid proposed delivery timeline in days.'
                    : 'Enter a valid delivery delay in days.';
            }

            if ($messageMotivation === '') {
                $errors[] = $responseMode === 'negotiate'
                    ? 'Add a negotiation message before sending this response.'
                    : 'Add a short creator message before sending the candidature.';
            }

            if ($isOfferFlow && $dateDisponibilite === '') {
                $errors[] = 'Choose your availability start date before sending the candidature.';
            }

            if ($responseMode === 'negotiate') {
                if ($budgetPropose === '' || !is_numeric($budgetPropose) || (float) $budgetPropose <= 0) {
                    $errors[] = 'Enter a valid proposed budget for negotiation.';
                }
            } elseif ($budgetPropose !== '' && (!is_numeric($budgetPropose) || (float) $budgetPropose <= 0)) {
                $errors[] = 'Enter a valid proposed budget.';
            }
        }

        return $errors;
    }

    private function computeDefaultDelay($source)
    {
        $today = new DateTime('today', $this->getModuleTimezone());
        $deadline = isset($source['dateLimite']) && trim((string) $source['dateLimite']) !== ''
            ? DateTime::createFromFormat('Y-m-d', (string) $source['dateLimite'], $this->getModuleTimezone())
            : false;

        if (!$deadline) {
            return 7;
        }

        $diff = (int) $today->diff($deadline)->format('%r%a');
        if ($diff < 1) {
            return 7;
        }

        return min(45, $diff);
    }

    private function resolveCreatorStatus($intent, $responseMode)
    {
        if ($intent === 'draft') {
            return 'brouillon';
        }

        if ($intent === 'final_accept') {
            return 'acceptee';
        }

        if ($intent === 'final_decline') {
            return 'retiree';
        }

        if ($intent === 'decline' || $responseMode === 'decline') {
            return 'retiree';
        }

        if ($responseMode === 'negotiate') {
            return 'negociation';
        }

        return 'envoyee';
    }

    private function resolveCreatorMessage($intent, $responseMode, $messageMotivation)
    {
        $messageMotivation = trim((string) $messageMotivation);
        if ($messageMotivation !== '') {
            return $messageMotivation;
        }

        return match (true) {
            $intent === 'draft' => '',
            $intent === 'final_accept' => 'The creator accepted the latest negotiated terms.',
            $intent === 'final_decline' => 'The creator declined the latest negotiated terms.',
            $intent === 'decline' || $responseMode === 'decline' => 'The creator withdrew from this targeted invitation.',
            $responseMode === 'negotiate' => 'The creator requested a negotiation round for this collaboration.',
            default => 'The creator submitted the candidature and is ready for the next review step.',
        };
    }

    private function resolveCreatorDecisionNote($intent, $responseMode, array $data, Condidature $existing = null)
    {
        if ($intent === 'final_accept') {
            return 'Creator accepted the latest negotiated terms.';
        }

        if ($intent === 'final_decline') {
            $motifRefus = trim((string) ($data['motifRefus'] ?? ''));
            $messageMotivation = trim((string) ($data['messageMotivation'] ?? ''));

            return $motifRefus !== ''
                ? $motifRefus
                : ($messageMotivation !== '' ? $messageMotivation : 'Creator declined the latest negotiated terms.');
        }

        return $existing ? (string) $existing->getNoteDecision() : '';
    }

    public function saveCreatorCandidature($idCreateur, $origin, $idSource, $intent, array $data = [], Condidature $existing = null)
    {
        $intent = strtolower(trim((string) $intent));
        $responseMode = strtolower(trim((string) ($data['responseMode'] ?? ($existing ? $existing->getResponseMode() : 'accept'))));
        $existingContext = $existing ? $this->getCreatorCandidatureById($existing->getIdCandidature(), $idCreateur) : null;

        if (!$existing) {
            $existingContext = $this->getCreatorCandidatureBySource($idCreateur, $origin, $idSource);
            $existing = $existingContext['condidature'] ?? null;
        }

        $source = $this->getSourceContext($origin, $idSource, $idCreateur, $existingContext !== null);

        $errors = $this->validateCreatorCandidature($data, $intent, $source, $existing);
        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors,
                'context' => $existingContext,
                'source' => $source,
            ];
        }

        $status = $this->resolveCreatorStatus($intent, $responseMode);
        $messageMotivation = $this->resolveCreatorMessage($intent, $responseMode, $data['messageMotivation'] ?? '');
        $decisionNote = $this->resolveCreatorDecisionNote($intent, $responseMode, $data, $existing);
        $budgetPropose = trim((string) ($data['budgetPropose'] ?? ''));
        $delaiPropose = trim((string) ($data['delaiPropose'] ?? ''));
        $dateDisponibilite = trim((string) ($data['dateDisponibilite'] ?? ''));
        $conditionsCreateur = trim((string) ($data['conditionsCreateur'] ?? ''));
        $cvPath = trim((string) ($data['cvPath'] ?? ''));
        $portfolioUrl = trim((string) ($data['portfolioUrl'] ?? ''));
        $motifRefus = trim((string) ($data['motifRefus'] ?? ''));
        $negotiationOnly = $existing && $existing->canCreatorEditNegotiationOnly();
        $finalizeNegotiation = in_array($intent, ['final_accept', 'final_decline'], true);

        if ($finalizeNegotiation && $existing) {
            $messageMotivation = (string) $existing->getMessageMotivation();
        }

        $budgetValue = $budgetPropose !== '' && is_numeric($budgetPropose)
            ? (float) $budgetPropose
            : ($existing ? (float) $existing->getBudgetPropose() : (float) ($source['budgetPropose'] ?? 0));

        $delayValue = $delaiPropose !== '' && is_numeric($delaiPropose)
            ? max(1, (int) $delaiPropose)
            : ($existing && $existing->getDelaiPropose() ? (int) $existing->getDelaiPropose() : $this->computeDefaultDelay($source));

        $now = $this->nowDateTime();
        $today = $this->todayDate();
        $record = $existing ? Condidature::fromArray([
            'idCandidature' => $existing->getIdCandidature(),
            'idCreateur' => $existing->getIdCreateur(),
            'origineCandidature' => $existing->getOrigineCandidature(),
            'idSource' => $existing->getIdSource(),
            'dateCandidature' => $existing->getDateCandidature(),
            'statutCandidature' => $existing->getStatutCandidature(),
            'messageMotivation' => $existing->getMessageMotivationForStorage(),
            'budgetPropose' => $existing->getBudgetPropose(),
            'delaiPropose' => $existing->getDelaiPropose(),
            'noteDecision' => $existing->getNoteDecisionForStorage(),
            'dateDerniereModification' => $existing->getDateDerniereModification(),
            'dateDecision' => $existing->getDateDecision(),
            'responseMode' => $existing->getResponseMode(),
            'dateDisponibilite' => $existing->getDateDisponibilite(),
            'conditionsCreateur' => $existing->getConditionsCreateur(),
            'cvPath' => $existing->getCvPath(),
            'portfolioUrl' => $existing->getPortfolioUrl(),
            'motifRefus' => $existing->getMotifRefus(),
        ]) : new Condidature();

        $record->setIdCreateur($idCreateur);
        $record->setOrigineCandidature($origin);
        $record->setIdSource($idSource);
        $record->setStatutCandidature($status);
        $record->setMessageMotivation($messageMotivation);
        $record->setBudgetPropose($budgetValue);
        $record->setDelaiPropose($delayValue);
        $record->setResponseMode($intent === 'final_decline' ? 'decline' : $responseMode);
        $record->setNoteDecision($decisionNote);

        if ($finalizeNegotiation) {
            if ($intent === 'final_decline') {
                $record->setMotifRefus($motifRefus);
            }
        } elseif (!$negotiationOnly) {
            if ($responseMode === 'decline') {
                $record->setDateDisponibilite('');
                $record->setConditionsCreateur('');
                $record->setCvPath('');
                $record->setPortfolioUrl('');
                $record->setMotifRefus($motifRefus);
            } else {
                $record->setDateDisponibilite($dateDisponibilite);
                $record->setConditionsCreateur($conditionsCreateur);
                $record->setCvPath($cvPath);
                $record->setPortfolioUrl($portfolioUrl);
                $record->setMotifRefus('');
            }
        }

        $storedMessageMotivation = $record->getMessageMotivationForStorage();
        $storedNoteDecision = $record->getNoteDecisionForStorage();
        $candidatureId = null;

        try {
            $this->pdo->beginTransaction();

            if ($existing) {
                $stmt = $this->pdo->prepare("
                UPDATE candidature
                SET
                    dateCandidature = :dateCandidature,
                    dateDerniereModification = :dateDerniereModification,
                    statutCandidature = :statutCandidature,
                        messageMotivation = :messageMotivation,
                        budgetPropose = :budgetPropose,
                        delaiPropose = :delaiPropose,
                        noteDecision = :noteDecision,
                        dateDecision = :dateDecision
                    WHERE idCandidature = :idCandidature
                      AND idCreateur = :idCreateur
                ");
                $stmt->execute([
                    'dateCandidature' => $today,
                    'dateDerniereModification' => $now,
                    'statutCandidature' => $status,
                    'messageMotivation' => $storedMessageMotivation,
                    'budgetPropose' => $budgetValue,
                    'delaiPropose' => $delayValue,
                    'noteDecision' => $storedNoteDecision,
                    'dateDecision' => $finalizeNegotiation ? $now : null,
                    'idCandidature' => (int) $existing->getIdCandidature(),
                    'idCreateur' => (int) $idCreateur,
                ]);
                $candidatureId = (int) $existing->getIdCandidature();
            } else {
                $stmt = $this->pdo->prepare("
                    INSERT INTO candidature (
                        idCreateur,
                        origineCandidature,
                        idSource,
                        dateCandidature,
                        dateDerniereModification,
                        statutCandidature,
                        dateDecision,
                        messageMotivation,
                        budgetPropose,
                        delaiPropose,
                        noteDecision
                    ) VALUES (
                        :idCreateur,
                        :origineCandidature,
                        :idSource,
                        :dateCandidature,
                        :dateDerniereModification,
                        :statutCandidature,
                        :dateDecision,
                        :messageMotivation,
                        :budgetPropose,
                        :delaiPropose,
                        :noteDecision
                    )
                ");
                $stmt->execute([
                    'idCreateur' => (int) $idCreateur,
                    'origineCandidature' => $origin,
                    'idSource' => (int) $idSource,
                    'dateCandidature' => $today,
                    'dateDerniereModification' => $now,
                    'statutCandidature' => $status,
                    'dateDecision' => $finalizeNegotiation ? $now : null,
                    'messageMotivation' => $storedMessageMotivation,
                    'budgetPropose' => $budgetValue,
                    'delaiPropose' => $delayValue,
                    'noteDecision' => $storedNoteDecision,
                ]);

                $candidatureId = (int) $this->pdo->lastInsertId();
            }

            if ($intent === 'send' && $responseMode === 'negotiate') {
                $this->insertNegotiationMessage($candidatureId, 'createur', $messageMotivation, $budgetValue, $delayValue, $now);
            }

            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return [
                'success' => false,
                'errors' => ['Unable to save this candidature right now.'],
                'context' => $existingContext,
                'source' => $source,
            ];
        }

        return [
            'success' => true,
            'context' => $this->getCreatorCandidatureById($candidatureId, $idCreateur),
            'source' => $source,
        ];
    }

    public function validateAdminReview(array $data, Condidature $condidature = null)
    {
        $errors = [];
        $status = trim((string) ($data['reviewStatus'] ?? ''));
        $noteDecision = trim((string) ($data['noteDecision'] ?? ''));

        if (!$condidature) {
            $errors[] = 'This candidature could not be found for review.';
            return $errors;
        }

        if ($condidature->getStatutCandidature() === 'retiree') {
            $errors[] = 'The creator already withdrew this candidature, so it is no longer reviewable.';
            return $errors;
        }

        if (!in_array($status, $this->getDecisionStatusOptions(), true)) {
            $errors[] = 'Choose a valid review status.';
        }

        if (in_array($status, ['acceptee', 'refusee'], true) && $noteDecision === '') {
            $errors[] = 'Add a decision note when accepting or refusing a candidature.';
        }

        if (strlen($noteDecision) > 2500) {
            $errors[] = 'Decision note must stay under 2500 characters.';
        }

        return $errors;
    }

    public function validateBrandNegotiation(array $data, Condidature $condidature = null)
    {
        $errors = [];
        $message = trim((string) ($data['message'] ?? ''));
        $budgetPropose = trim((string) ($data['budgetPropose'] ?? ''));
        $delaiPropose = trim((string) ($data['delaiPropose'] ?? ''));

        if (!$condidature) {
            $errors[] = 'This candidature could not be found for brand review.';

            return $errors;
        }

        if (in_array($condidature->getStatutCandidature(), ['brouillon', 'acceptee', 'refusee', 'retiree'], true)) {
            $errors[] = 'Negotiation is not available for this candidature anymore.';

            return $errors;
        }

        if (!$this->hasNegotiationDelta(
            $message,
            $budgetPropose,
            $delaiPropose,
            '',
            $condidature->getBudgetPropose(),
            $condidature->getDelaiPropose()
        )) {
            $errors[] = 'Change the message, budget, or timeline before sending another negotiation reply. Use the final decision actions when the latest terms are already acceptable.';
        }

        if (strlen($message) > 2500) {
            $errors[] = 'Negotiation message must stay under 2500 characters.';
        }

        if ($budgetPropose !== '' && (!is_numeric($budgetPropose) || (float) $budgetPropose <= 0)) {
            $errors[] = 'Enter a valid negotiation budget above zero.';
        }

        if ($delaiPropose !== '' && (!is_numeric($delaiPropose) || (int) $delaiPropose <= 0)) {
            $errors[] = 'Enter a valid delivery timeline in days.';
        }

        return $errors;
    }

    public function replyToNegotiationAsBrand($idCandidature, $idMarque, array $data)
    {
        $context = $this->getBrandCandidatureById($idCandidature, $idMarque);
        $condidature = $context['condidature'] ?? null;
        $errors = $this->validateBrandNegotiation($data, $condidature);

        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors,
                'context' => $context,
            ];
        }

        $message = trim((string) ($data['message'] ?? ''));
        $budgetPropose = trim((string) ($data['budgetPropose'] ?? ''));
        $delaiPropose = trim((string) ($data['delaiPropose'] ?? ''));
        $budgetValue = $budgetPropose !== '' ? (float) $budgetPropose : (float) $condidature->getBudgetPropose();
        $delayValue = $delaiPropose !== '' ? max(1, (int) $delaiPropose) : (int) $condidature->getDelaiPropose();
        $now = $this->nowDateTime();

        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("
                UPDATE candidature
                SET
                    statutCandidature = :statutCandidature,
                    budgetPropose = :budgetPropose,
                    delaiPropose = :delaiPropose,
                    dateDerniereModification = :dateDerniereModification,
                    dateDecision = NULL
                WHERE idCandidature = :idCandidature
            ");
            $stmt->execute([
                'statutCandidature' => 'negociation',
                'budgetPropose' => $budgetValue,
                'delaiPropose' => $delayValue,
                'dateDerniereModification' => $now,
                'idCandidature' => (int) $idCandidature,
            ]);

            $historyMessage = $message !== '' ? $message : 'The brand shared updated negotiation terms.';
            $this->insertNegotiationMessage($idCandidature, 'marque', $historyMessage, $budgetPropose !== '' ? $budgetValue : null, $delaiPropose !== '' ? $delayValue : null, $now);

            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return [
                'success' => false,
                'errors' => ['Unable to send the brand negotiation reply right now.'],
                'context' => $context,
            ];
        }

        return [
            'success' => true,
            'context' => $this->getBrandCandidatureById($idCandidature, $idMarque),
        ];
    }

    public function validateBrandDecision(array $data, Condidature $condidature = null)
    {
        $errors = [];
        $decisionStatus = trim((string) ($data['decisionStatus'] ?? ''));
        $noteDecision = trim((string) ($data['noteDecision'] ?? ''));

        if (!$condidature) {
            $errors[] = 'This candidature could not be found for brand review.';

            return $errors;
        }

        if (!$condidature->canBrandDecide()) {
            $errors[] = 'This candidature can no longer be accepted or refused from the brand workspace.';

            return $errors;
        }

        if (!in_array($decisionStatus, ['acceptee', 'refusee'], true)) {
            $errors[] = 'Choose a valid brand decision before saving.';
        }

        if (strlen($noteDecision) > 2500) {
            $errors[] = 'Decision note must stay under 2500 characters.';
        }

        return $errors;
    }

    public function decideCandidatureAsBrand($idCandidature, $idMarque, array $data)
    {
        $context = $this->getBrandCandidatureById($idCandidature, $idMarque);
        $condidature = $context['condidature'] ?? null;
        $errors = $this->validateBrandDecision($data, $condidature);

        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors,
                'context' => $context,
            ];
        }

        $decisionStatus = trim((string) ($data['decisionStatus'] ?? ''));
        $noteDecision = trim((string) ($data['noteDecision'] ?? ''));
        if ($noteDecision === '') {
            $noteDecision = $decisionStatus === 'acceptee'
                ? 'Approved by brand from the response workspace.'
                : 'Refused by brand from the response workspace.';
        }

        $now = $this->nowDateTime();
        $condidature->setStatutCandidature($decisionStatus);
        $condidature->setNoteDecision($noteDecision);

        try {
            $stmt = $this->pdo->prepare("
                UPDATE candidature
                SET
                    statutCandidature = :statutCandidature,
                    noteDecision = :noteDecision,
                    dateDerniereModification = :dateDerniereModification,
                    dateDecision = :dateDecision
                WHERE idCandidature = :idCandidature
            ");
            $stmt->execute([
                'statutCandidature' => $decisionStatus,
                'noteDecision' => $condidature->getNoteDecisionForStorage(),
                'dateDerniereModification' => $now,
                'dateDecision' => $now,
                'idCandidature' => (int) $idCandidature,
            ]);
        } catch (Throwable $exception) {
            return [
                'success' => false,
                'errors' => ['Unable to save the brand decision right now.'],
                'context' => $context,
            ];
        }

        return [
            'success' => true,
            'context' => $this->getBrandCandidatureById($idCandidature, $idMarque),
        ];
    }

    public function reviewCandidature($idCandidature, array $data)
    {
        $context = $this->getAdminCandidatureById($idCandidature);
        $condidature = $context['condidature'] ?? null;
        $errors = $this->validateAdminReview($data, $condidature);

        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors,
                'context' => $context,
            ];
        }

        $status = trim((string) ($data['reviewStatus'] ?? ''));
        $noteDecision = trim((string) ($data['noteDecision'] ?? ''));
        $now = $this->nowDateTime();
        $dateDecision = in_array($status, ['acceptee', 'refusee'], true) ? $now : null;
        $condidature->setStatutCandidature($status);
        $condidature->setNoteDecision($noteDecision);

        $stmt = $this->pdo->prepare("
            UPDATE candidature
            SET
                statutCandidature = :statutCandidature,
                noteDecision = :noteDecision,
                dateDerniereModification = :dateDerniereModification,
                dateDecision = :dateDecision
            WHERE idCandidature = :idCandidature
        ");
        $stmt->execute([
            'statutCandidature' => $status,
            'noteDecision' => $condidature->getNoteDecisionForStorage(),
            'dateDerniereModification' => $now,
            'dateDecision' => $dateDecision,
            'idCandidature' => (int) $idCandidature,
        ]);

        return [
            'success' => true,
            'context' => $this->getAdminCandidatureById($idCandidature),
        ];
    }
}

?>
