<?php

require_once __DIR__ . '/../Modele/offre.php';
require_once __DIR__ . '/../Modele/condidature.php';
require_once __DIR__ . '/../config.php';

class OffreC
{
    private $pdo;
    private const MODULE_TIMEZONE = 'Africa/Tunis';

    public function __construct()
    {
        $this->pdo = config::getConnexion();
    }

    private function getModuleTimezone()
    {
        return new DateTimeZone(self::MODULE_TIMEZONE);
    }

    private function rowToOffre(array $row)
    {
        return new Offre(
            $row['idOffre'] ?? null,
            $row['idMarque'] ?? null,
            $row['idCreateurCible'] ?? null,
            $row['titre'] ?? null,
            $row['description'] ?? null,
            $row['objectif'] ?? null,
            $row['budgetPropose'] ?? ($row['budgetMin'] ?? $row['budgetMax'] ?? null),
            $row['datePublication'] ?? null,
            $row['dateLimite'] ?? null,
            $row['statutOffre'] ?? null,
            $row['raisonChoix'] ?? null,
            $row['messagePersonnalise'] ?? null,
            $row['attenteCollaboration'] ?? null,
            $row['draftSansCreateur'] ?? null
        );
    }

    private function hydrateOffres($statement)
    {
        $offres = [];
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $offres[] = $this->rowToOffre($row);
        }

        return $offres;
    }

    private function normalizeCandidatureStatus($status)
    {
        $status = trim((string) $status);

        return match ($status) {
            '', 'en_attente' => 'envoyee',
            default => $status,
        };
    }

    private function normalizeCandidatureRow(array $row)
    {
        if (array_key_exists('statutCandidature', $row)) {
            $row['statutCandidature'] = $this->normalizeCandidatureStatus($row['statutCandidature']);
        }

        $condidature = Condidature::fromArray($row);
        $row['noteDecision'] = $condidature->getNoteDecision();
        $row['responseMode'] = $condidature->getResponseMode();
        $row['typeReponse'] = $condidature->getTypeReponse();
        $row['displayStatusLabel'] = $condidature->getDisplayStatusLabel();
        $row['responseTypeLabel'] = $condidature->getResponseTypeLabel();

        return $row;
    }

    private function isAcceptedCreatorResponseStatus($status)
    {
        return in_array(
            $this->normalizeCandidatureStatus($status),
            ['envoyee', 'en_attente', 'acceptee'],
            true
        );
    }

    public function getOffresByMarque($idMarque)
    {
        $sql = 'SELECT * FROM offre WHERE idMarque = :idMarque ORDER BY datePublication DESC, idOffre DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['idMarque' => $idMarque]);

        return $this->hydrateOffres($stmt);
    }

    public function getOffreById($idOffre, $idMarque)
    {
        $sql = 'SELECT * FROM offre WHERE idOffre = :idOffre AND idMarque = :idMarque';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['idOffre' => $idOffre, 'idMarque' => $idMarque]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->rowToOffre($row) : null;
    }

    public function getAllOffres()
    {
        $stmt = $this->pdo->query('SELECT * FROM offre ORDER BY datePublication DESC, idOffre DESC');

        return $this->hydrateOffres($stmt);
    }

    public function getOffreByIdAdmin($idOffre)
    {
        $stmt = $this->pdo->prepare('SELECT * FROM offre WHERE idOffre = :idOffre');
        $stmt->execute(['idOffre' => $idOffre]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->rowToOffre($row) : null;
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

    public function getLoginDirectoryUsers(array $roles = ['marque', 'createur', 'admin'])
    {
        $allowedRoles = ['marque', 'createur', 'admin'];
        $roles = array_values(array_unique(array_filter(
            array_map(static fn($role) => strtolower(trim((string) $role)), $roles),
            static fn($role) => in_array($role, $allowedRoles, true)
        )));

        if (empty($roles)) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($roles), '?'));
        $orderCases = [];
        foreach ($roles as $index => $role) {
            $orderCases[] = "WHEN '" . $role . "' THEN " . $index;
        }

        $sql = "
            SELECT id, nom, email, role, statut, date_creation
            FROM utilisateur
            WHERE statut != ? AND role IN ({$placeholders})
            ORDER BY
                CASE role " . implode(' ', $orderCases) . " ELSE 99 END,
                CASE statut
                    WHEN 'actif' THEN 0
                    WHEN 'en_attente' THEN 1
                    ELSE 2
                END,
                nom ASC,
                id ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_merge(['bloque'], $roles));

        $grouped = [];
        foreach ($roles as $role) {
            $grouped[$role] = [];
        }

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $role = (string) ($row['role'] ?? '');
            if (!isset($grouped[$role])) {
                $grouped[$role] = [];
            }
            $grouped[$role][] = $row;
        }

        return $grouped;
    }

    private function normalizeCreatorPickerRow(array $row)
    {
        return [
            'id' => (int) ($row['id'] ?? 0),
            'nom' => (string) ($row['nom'] ?? ''),
            'email' => (string) ($row['email'] ?? ''),
            'statut' => (string) ($row['statut'] ?? ''),
            'targetedOffers' => (int) ($row['targetedOffers'] ?? 0),
            'liveOffers' => (int) ($row['liveOffers'] ?? 0),
        ];
    }

    public function getAvailableCreatorsPage($keyword = null, $limit = 12, $offset = 0)
    {
        $limit = max(1, min(24, (int) $limit));
        $offset = max(0, (int) $offset);
        $sql = "
            SELECT
                u.id,
                u.nom,
                u.email,
                u.statut,
                COUNT(o.idOffre) AS targetedOffers,
                SUM(CASE WHEN o.statutOffre = 'publiee' AND o.datePublication <= CURDATE() THEN 1 ELSE 0 END) AS liveOffers
            FROM utilisateur u
            LEFT JOIN offre o ON o.idCreateurCible = u.id
            WHERE u.role = :role AND u.statut != :blocked
        ";
        $params = [
            'role' => 'createur',
            'blocked' => 'bloque',
        ];

        if ($keyword !== null && trim((string) $keyword) !== '') {
            $sql .= ' AND (u.nom LIKE :keyword OR u.email LIKE :keyword OR CAST(u.id AS CHAR) LIKE :keyword)';
            $params['keyword'] = '%' . trim((string) $keyword) . '%';
        }

        $sql .= "
            GROUP BY u.id, u.nom, u.email, u.statut
            ORDER BY
                CASE u.statut
                    WHEN 'actif' THEN 0
                    WHEN 'en_attente' THEN 1
                    ELSE 2
                END,
                liveOffers DESC,
                targetedOffers DESC,
                u.nom ASC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $limit + 1, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $hasMore = count($rows) > $limit;

        if ($hasMore) {
            array_pop($rows);
        }

        return [
            'items' => array_map([$this, 'normalizeCreatorPickerRow'], $rows),
            'hasMore' => $hasMore,
            'offset' => $offset,
            'nextOffset' => $offset + count($rows),
        ];
    }

    public function getAvailableCreators($keyword = null, $limit = 18, $offset = 0)
    {
        return $this->getAvailableCreatorsPage($keyword, $limit, $offset)['items'];
    }

    public function getCreatorPickerProfile($creatorId)
    {
        $creatorId = (int) $creatorId;
        if ($creatorId <= 0) {
            return null;
        }

        $sql = "
            SELECT
                u.id,
                u.nom,
                u.email,
                u.statut,
                COUNT(o.idOffre) AS targetedOffers,
                SUM(CASE WHEN o.statutOffre = 'publiee' AND o.datePublication <= CURDATE() THEN 1 ELSE 0 END) AS liveOffers
            FROM utilisateur u
            LEFT JOIN offre o ON o.idCreateurCible = u.id
            WHERE u.role = :role AND u.statut != :blocked AND u.id = :creatorId
            GROUP BY u.id, u.nom, u.email, u.statut
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'role' => 'createur',
            'blocked' => 'bloque',
            'creatorId' => $creatorId,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->normalizeCreatorPickerRow($row) : null;
    }

    public function getDraftPlaceholderCreatorId()
    {
        $stmt = $this->pdo->query("
            SELECT id
            FROM utilisateur
            WHERE role = 'createur' AND statut != 'bloque'
            ORDER BY
                CASE statut
                    WHEN 'actif' THEN 0
                    WHEN 'en_attente' THEN 1
                    ELSE 2
                END,
                id ASC
            LIMIT 1
        ");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? (int) $row['id'] : null;
    }

    public function getCandidatureCountByOffre($idOffre)
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) AS total FROM candidature WHERE origineCandidature = :origine AND idSource = :idSource');
        $stmt->execute([
            'origine' => 'par_offre',
            'idSource' => $idOffre,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? (int) $row['total'] : 0;
    }

    public function getCandidatureCountsForOfferIds(array $offerIds)
    {
        $offerIds = array_values(array_unique(array_map('intval', array_filter($offerIds, static fn($id) => (int) $id > 0))));
        if (empty($offerIds)) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($offerIds), '?'));
        $params = array_merge(['par_offre'], $offerIds);
        $sql = "
            SELECT idSource, COUNT(*) AS total
            FROM candidature
            WHERE origineCandidature = ? AND idSource IN ({$placeholders})
            GROUP BY idSource
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $counts = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $counts[(int) $row['idSource']] = (int) $row['total'];
        }

        return $counts;
    }

    public function getCandidaturesByOffre($idOffre)
    {
        $sql = "
            SELECT
                c.*,
                u.nom AS createurNom,
                u.email AS createurEmail
            FROM candidature c
            LEFT JOIN utilisateur u ON u.id = c.idCreateur
            WHERE c.origineCandidature = :origine AND c.idSource = :idSource
            ORDER BY c.dateCandidature DESC, c.idCandidature DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'origine' => 'par_offre',
            'idSource' => $idOffre,
        ]);

        return array_map([$this, 'normalizeCandidatureRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getCandidaturesGroupedByOfferIds(array $offerIds)
    {
        $offerIds = array_values(array_unique(array_map('intval', array_filter($offerIds, static fn($id) => (int) $id > 0))));
        if (empty($offerIds)) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($offerIds), '?'));
        $params = array_merge(['par_offre'], $offerIds);
        $sql = "
            SELECT
                c.*,
                u.nom AS createurNom,
                u.email AS createurEmail
            FROM candidature c
            LEFT JOIN utilisateur u ON u.id = c.idCreateur
            WHERE c.origineCandidature = ? AND c.idSource IN ({$placeholders})
            ORDER BY c.dateCandidature DESC, c.idCandidature DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $grouped = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $grouped[(int) $row['idSource']][] = $this->normalizeCandidatureRow($row);
        }

        return $grouped;
    }

    public function getOfferResponseBreakdown($idOffre)
    {
        $stmt = $this->pdo->prepare("
            SELECT statutCandidature, COUNT(*) AS total
            FROM candidature
            WHERE origineCandidature = :origine AND idSource = :idSource
            GROUP BY statutCandidature
        ");
        $stmt->execute([
            'origine' => 'par_offre',
            'idSource' => $idOffre,
        ]);

        $breakdown = [
            'brouillon' => 0,
            'envoyee' => 0,
            'negociation' => 0,
            'en_etude' => 0,
            'acceptee' => 0,
            'refusee' => 0,
            'retiree' => 0,
            'total' => 0,
        ];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $status = $this->normalizeCandidatureStatus($row['statutCandidature'] ?? '');
            $count = (int) $row['total'];
            if (!array_key_exists($status, $breakdown)) {
                $breakdown[$status] = 0;
            }
            $breakdown[$status] += $count;
            $breakdown['total'] += $count;
        }

        return $breakdown;
    }

    public function getOffresByCreateurCible($idCreateurCible)
    {
        $sql = 'SELECT * FROM offre WHERE idCreateurCible = :idCreateurCible AND statutOffre = :statut AND datePublication <= CURDATE() ORDER BY datePublication DESC, idOffre DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'idCreateurCible' => $idCreateurCible,
            'statut' => 'publiee',
        ]);

        return $this->hydrateOffres($stmt);
    }

    public function searchOffers($idCreateurCible, $keyword = null, $budgetMin = null, $budgetMax = null, $dateLimite = null)
    {
        $sql = 'SELECT * FROM offre WHERE idCreateurCible = :idCreateurCible AND statutOffre = :statut AND datePublication <= CURDATE()';
        $params = [
            'idCreateurCible' => $idCreateurCible,
            'statut' => 'publiee',
        ];

        if ($keyword !== null && trim((string) $keyword) !== '') {
            $sql .= ' AND (titre LIKE :keyword OR objectif LIKE :keyword OR description LIKE :keyword OR raisonChoix LIKE :keyword OR attenteCollaboration LIKE :keyword OR messagePersonnalise LIKE :keyword)';
            $params['keyword'] = '%' . trim((string) $keyword) . '%';
        }

        if ($budgetMin !== null && is_numeric($budgetMin)) {
            $sql .= ' AND budgetPropose >= :budgetMin';
            $params['budgetMin'] = $budgetMin;
        }

        if ($budgetMax !== null && is_numeric($budgetMax)) {
            $sql .= ' AND budgetPropose <= :budgetMax';
            $params['budgetMax'] = $budgetMax;
        }

        if ($dateLimite) {
            $sql .= ' AND dateLimite >= :dateLimite';
            $params['dateLimite'] = $dateLimite;
        }

        $sql .= ' ORDER BY budgetPropose DESC, datePublication DESC, idOffre DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $this->hydrateOffres($stmt);
    }

    public function searchOffresAdmin($keyword = null, $statut = null, $idMarque = null, $idCreateurCible = null, $budgetMin = null, $budgetMax = null, $dateLimite = null)
    {
        $sql = "
            SELECT o.*
            FROM offre o
            LEFT JOIN utilisateur marque ON marque.id = o.idMarque
            LEFT JOIN utilisateur createur ON createur.id = o.idCreateurCible
            WHERE 1 = 1
        ";
        $params = [];

        if ($keyword !== null && trim((string) $keyword) !== '') {
            $sql .= '
                AND (
                    o.titre LIKE :keyword
                    OR o.objectif LIKE :keyword
                    OR o.description LIKE :keyword
                    OR o.raisonChoix LIKE :keyword
                    OR o.attenteCollaboration LIKE :keyword
                    OR o.messagePersonnalise LIKE :keyword
                    OR CAST(o.idOffre AS CHAR) LIKE :keyword
                    OR marque.nom LIKE :keyword
                    OR marque.email LIKE :keyword
                    OR createur.nom LIKE :keyword
                    OR createur.email LIKE :keyword
                )
            ';
            $params['keyword'] = '%' . trim((string) $keyword) . '%';
        }

        if ($statut) {
            if ($statut === 'pending') {
                $sql .= ' AND o.statutOffre = :pendingStatus AND o.datePublication > CURDATE()';
                $params['pendingStatus'] = 'publiee';
            } elseif ($statut === 'publiee') {
                $sql .= ' AND o.statutOffre = :statut AND o.datePublication <= CURDATE()';
                $params['statut'] = $statut;
            } else {
                $sql .= ' AND o.statutOffre = :statut';
                $params['statut'] = $statut;
            }
        }

        if ($idMarque !== null && is_numeric($idMarque)) {
            $sql .= ' AND o.idMarque = :idMarque';
            $params['idMarque'] = $idMarque;
        }

        if ($idCreateurCible !== null && is_numeric($idCreateurCible)) {
            $sql .= ' AND o.idCreateurCible = :idCreateurCible';
            $params['idCreateurCible'] = $idCreateurCible;
        }

        if ($budgetMin !== null && is_numeric($budgetMin)) {
            $sql .= ' AND o.budgetPropose >= :budgetMin';
            $params['budgetMin'] = $budgetMin;
        }

        if ($budgetMax !== null && is_numeric($budgetMax)) {
            $sql .= ' AND o.budgetPropose <= :budgetMax';
            $params['budgetMax'] = $budgetMax;
        }

        if ($dateLimite) {
            $sql .= ' AND o.dateLimite >= :dateLimite';
            $params['dateLimite'] = $dateLimite;
        }

        $sql .= ' ORDER BY o.datePublication DESC, o.idOffre DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $this->hydrateOffres($stmt);
    }

    public function getPublishedOffreById($idOffre, $idCreateurCible)
    {
        $sql = 'SELECT * FROM offre WHERE idOffre = :idOffre AND idCreateurCible = :idCreateurCible AND statutOffre = :statut AND datePublication <= CURDATE()';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'idOffre' => $idOffre,
            'idCreateurCible' => $idCreateurCible,
            'statut' => 'publiee',
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->rowToOffre($row) : null;
    }

    public function createOffre(Offre $offre)
    {
        $sql = '
            INSERT INTO offre (
                idMarque,
                idCreateurCible,
                titre,
                description,
                objectif,
                budgetPropose,
                datePublication,
                dateLimite,
                statutOffre,
                raisonChoix,
                attenteCollaboration,
                messagePersonnalise
            ) VALUES (
                :idMarque,
                :idCreateurCible,
                :titre,
                :description,
                :objectif,
                :budgetPropose,
                :datePublication,
                :dateLimite,
                :statutOffre,
                :raisonChoix,
                :attenteCollaboration,
                :messagePersonnalise
            )
        ';
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            'idMarque' => $offre->getIdMarque(),
            'idCreateurCible' => $offre->getIdCreateurCible(),
            'titre' => $offre->getTitre(),
            'description' => $offre->getDescriptionForStorage(),
            'objectif' => $offre->getObjectif(),
            'budgetPropose' => $offre->getBudgetPropose(),
            'datePublication' => $offre->getDatePublication(),
            'dateLimite' => $offre->getDateLimite(),
            'statutOffre' => $offre->getStatutOffre() ?: 'publiee',
            'raisonChoix' => $offre->getRaisonChoix(),
            'attenteCollaboration' => $offre->getAttenteCollaboration(),
            'messagePersonnalise' => $offre->getMessagePersonnalise(),
        ]);
    }

    public function updateOffre(Offre $offre)
    {
        $existingOffer = $this->getOffreById($offre->getIdOffre(), $offre->getIdMarque());
        if (!$existingOffer) {
            return false;
        }

        if (!$existingOffer->isDraftSansCreateur() && $existingOffer->getIdCreateurCible()) {
            $targetedResponse = $this->getOfferResponseByCreator($existingOffer->getIdCreateurCible(), $existingOffer->getIdOffre());
            if ($targetedResponse && $this->isAcceptedCreatorResponseStatus($targetedResponse['statutCandidature'] ?? '')) {
                return false;
            }
        }

        $targetCreatorId = $offre->getIdCreateurCible();
        if (!$existingOffer->isDraftSansCreateur() && $existingOffer->getIdCreateurCible()) {
            $targetCreatorId = $existingOffer->getIdCreateurCible();
        }

        $sql = '
            UPDATE offre
            SET
                idCreateurCible = :idCreateurCible,
                titre = :titre,
                description = :description,
                objectif = :objectif,
                budgetPropose = :budgetPropose,
                datePublication = :datePublication,
                dateLimite = :dateLimite,
                statutOffre = :statutOffre,
                raisonChoix = :raisonChoix,
                attenteCollaboration = :attenteCollaboration,
                messagePersonnalise = :messagePersonnalise
            WHERE idOffre = :idOffre AND idMarque = :idMarque
        ';
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            'idCreateurCible' => $targetCreatorId,
            'titre' => $offre->getTitre(),
            'description' => $offre->getDescriptionForStorage(),
            'objectif' => $offre->getObjectif(),
            'budgetPropose' => $offre->getBudgetPropose(),
            'datePublication' => $offre->getDatePublication(),
            'dateLimite' => $offre->getDateLimite(),
            'statutOffre' => $offre->getStatutOffre() ?: 'publiee',
            'raisonChoix' => $offre->getRaisonChoix(),
            'attenteCollaboration' => $offre->getAttenteCollaboration(),
            'messagePersonnalise' => $offre->getMessagePersonnalise(),
            'idOffre' => $offre->getIdOffre(),
            'idMarque' => $offre->getIdMarque(),
        ]);
    }

    public function deleteOffre($idOffre, $idMarque)
    {
        $stmt = $this->pdo->prepare('DELETE FROM offre WHERE idOffre = :idOffre AND idMarque = :idMarque');

        return $stmt->execute([
            'idOffre' => $idOffre,
            'idMarque' => $idMarque,
        ]);
    }

    public function getOfferResponseByCreator($idCreateur, $idSource)
    {
        $sql = "
            SELECT *
            FROM candidature
            WHERE idCreateur = :idCreateur
              AND origineCandidature = :origine
              AND idSource = :idSource
            ORDER BY dateCandidature DESC, idCandidature DESC
            LIMIT 1
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'idCreateur' => $idCreateur,
            'origine' => 'par_offre',
            'idSource' => $idSource,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->normalizeCandidatureRow($row) : null;
    }

    public function submitOfferResponse($idCreateur, $idOffre, $responseType, $messageMotivation = '', $budgetPropose = null, $delaiPropose = null)
    {
        $offer = $this->getPublishedOffreById($idOffre, $idCreateur);
        if (!$offer) {
            return false;
        }

        $responseType = strtolower(trim((string) $responseType));
        $statusMap = [
            'accept' => 'envoyee',
            'decline' => 'retiree',
            'negotiate' => 'negociation',
        ];

        if (!isset($statusMap[$responseType])) {
            return false;
        }

        $defaultMessages = [
            'accept' => 'Accepted the invitation and is ready to move forward.',
            'decline' => 'Declined this targeted invitation.',
            'negotiate' => 'Requested a discussion before confirming the collaboration.',
        ];

        $finalMessage = trim((string) $messageMotivation);
        if ($finalMessage === '') {
            $finalMessage = $defaultMessages[$responseType];
        }

        $finalBudget = $budgetPropose !== null && $budgetPropose !== '' && is_numeric($budgetPropose)
            ? (float) $budgetPropose
            : (float) $offer->getBudgetPropose();

        $finalDelay = $delaiPropose !== null && $delaiPropose !== '' && is_numeric($delaiPropose)
            ? max(1, (int) $delaiPropose)
            : $this->buildDefaultResponseDelay($offer->getDateLimite());

        $existing = $this->getOfferResponseByCreator($idCreateur, $idOffre);
        $record = $existing ? Condidature::fromArray($existing) : new Condidature();
        $record->setIdCreateur($idCreateur);
        $record->setOrigineCandidature('par_offre');
        $record->setIdSource($idOffre);
        $record->setStatutCandidature($statusMap[$responseType]);
        $record->setMessageMotivation($finalMessage);
        $record->setBudgetPropose($finalBudget);
        $record->setDelaiPropose($finalDelay);
        $record->setResponseMode($responseType);
        $record->setNoteDecision('');
        $storedNoteDecision = $record->getNoteDecisionForStorage();

        if ($existing) {
            $sql = '
                UPDATE candidature
                SET
                    dateCandidature = CURDATE(),
                    statutCandidature = :statutCandidature,
                    messageMotivation = :messageMotivation,
                    budgetPropose = :budgetPropose,
                    delaiPropose = :delaiPropose,
                    noteDecision = :noteDecision
                WHERE idCandidature = :idCandidature
            ';
            $stmt = $this->pdo->prepare($sql);

            return $stmt->execute([
                'statutCandidature' => $statusMap[$responseType],
                'messageMotivation' => $finalMessage,
                'budgetPropose' => $finalBudget,
                'delaiPropose' => $finalDelay,
                'noteDecision' => $storedNoteDecision,
                'idCandidature' => $existing['idCandidature'],
            ]);
        }

        $sql = '
            INSERT INTO candidature (
                idCreateur,
                origineCandidature,
                idSource,
                dateCandidature,
                statutCandidature,
                messageMotivation,
                budgetPropose,
                delaiPropose,
                noteDecision
            ) VALUES (
                :idCreateur,
                :origineCandidature,
                :idSource,
                CURDATE(),
                :statutCandidature,
                :messageMotivation,
                :budgetPropose,
                :delaiPropose,
                :noteDecision
            )
        ';
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            'idCreateur' => $idCreateur,
            'origineCandidature' => 'par_offre',
            'idSource' => $idOffre,
            'statutCandidature' => $statusMap[$responseType],
            'messageMotivation' => $finalMessage,
            'budgetPropose' => $finalBudget,
            'delaiPropose' => $finalDelay,
            'noteDecision' => $storedNoteDecision,
        ]);
    }

    public function createCandidature($idCreateur, $origineCandidature, $idSource, $messageMotivation = '', $budgetPropose = null, $delaiPropose = null, $statutCandidature = 'envoyee')
    {
        if ($origineCandidature === 'par_offre') {
            return $this->submitOfferResponse($idCreateur, $idSource, 'accept', $messageMotivation, $budgetPropose, $delaiPropose);
        }

        if ($budgetPropose === null || !is_numeric($budgetPropose)) {
            return false;
        }

        $delay = $delaiPropose !== null && $delaiPropose !== '' && is_numeric($delaiPropose)
            ? max(1, (int) $delaiPropose)
            : 7;

        $stmt = $this->pdo->prepare('
            INSERT INTO candidature (
                idCreateur,
                origineCandidature,
                idSource,
                dateCandidature,
                statutCandidature,
                messageMotivation,
                budgetPropose,
                delaiPropose,
                noteDecision
            ) VALUES (
                :idCreateur,
                :origineCandidature,
                :idSource,
                CURDATE(),
                :statutCandidature,
                :messageMotivation,
                :budgetPropose,
                :delaiPropose,
                :noteDecision
            )
        ');

        return $stmt->execute([
            'idCreateur' => $idCreateur,
            'origineCandidature' => $origineCandidature,
            'idSource' => $idSource,
            'statutCandidature' => $statutCandidature,
            'messageMotivation' => trim((string) $messageMotivation) ?: 'Submitted from a platform invitation.',
            'budgetPropose' => (float) $budgetPropose,
            'delaiPropose' => $delay,
            'noteDecision' => 'Created from the offer module.',
        ]);
    }

    private function buildDefaultResponseDelay($dateLimite)
    {
        $today = new DateTime('today');
        $deadline = DateTime::createFromFormat('Y-m-d', (string) $dateLimite);

        if (!$deadline) {
            return 7;
        }

        $diff = (int) $today->diff($deadline)->format('%r%a');
        if ($diff < 1) {
            return 7;
        }

        return min(45, $diff);
    }

    public function validateOffreData(array $data, $mode = 'publish', array $options = [])
    {
        $errors = [];
        $isDraft = $mode === 'draft';
        $allowPastPublicationDate = !empty($options['allowPastPublicationDate']);
        $timezone = $this->getModuleTimezone();
        $today = new DateTime('today', $timezone);

        $idCreateurCible = trim((string) ($data['idCreateurCible'] ?? ''));
        $titre = trim((string) ($data['titre'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        $objectif = trim((string) ($data['objectif'] ?? ''));
        $budgetPropose = trim((string) ($data['budgetPropose'] ?? ''));
        $datePublication = trim((string) ($data['datePublication'] ?? ''));
        $dateLimite = trim((string) ($data['dateLimite'] ?? ''));
        $raisonChoix = trim((string) ($data['raisonChoix'] ?? ''));
        $messagePersonnalise = trim((string) ($data['messagePersonnalise'] ?? ''));
        $attenteCollaboration = trim((string) ($data['attenteCollaboration'] ?? ''));
        $hasCreator = $idCreateurCible !== '' && is_numeric($idCreateurCible) && (int) $idCreateurCible > 0;

        if ($isDraft) {
            $hasDraftSignal = $hasCreator
                || $titre !== ''
                || $description !== ''
                || $objectif !== ''
                || $budgetPropose !== ''
                || $raisonChoix !== ''
                || $messagePersonnalise !== ''
                || $attenteCollaboration !== '';

            if (!$hasDraftSignal) {
                $errors[] = 'Start the draft by selecting a creator or filling at least one offer field.';
            }
            return $errors;
        }

        if ($titre === '') {
            $errors[] = 'Title is required.';
        } elseif (strlen($titre) > 150) {
            $errors[] = 'Title must stay under 150 characters.';
        }

        if ($description === '') {
            $errors[] = 'Description is required.';
        }

        if ($objectif === '') {
            $errors[] = 'Objective is required.';
        }

        if ($budgetPropose === '' || !is_numeric($budgetPropose)) {
            $errors[] = 'Proposed budget must be a valid amount.';
        } elseif ((float) $budgetPropose <= 0) {
            $errors[] = 'Proposed budget must be greater than zero.';
        }

        $publicationDate = null;
        if ($datePublication !== '') {
            $publicationDate = DateTime::createFromFormat('!Y-m-d', $datePublication, $timezone);
            if (!$publicationDate || $publicationDate->format('Y-m-d') !== $datePublication) {
                $errors[] = 'Publication date must use the YYYY-MM-DD format.';
            } elseif (!$allowPastPublicationDate && $publicationDate < $today) {
                $errors[] = 'Publication date cannot be in the past.';
            }
        } else {
            $errors[] = 'Publication date must use the YYYY-MM-DD format.';
        }

        $limiteDate = null;
        if ($dateLimite !== '') {
            $limiteDate = DateTime::createFromFormat('!Y-m-d', $dateLimite, $timezone);
            if (!$limiteDate || $limiteDate->format('Y-m-d') !== $dateLimite) {
                $errors[] = 'Deadline must use the YYYY-MM-DD format.';
            } elseif ($limiteDate < $today) {
                $errors[] = 'Deadline cannot be in the past.';
            }
        } else {
            $errors[] = 'Deadline must use the YYYY-MM-DD format.';
        }

        if ($publicationDate && $limiteDate && $publicationDate >= $limiteDate) {
            $errors[] = 'Deadline must be later than the publication date.';
        }

        if (strlen($raisonChoix) > 600) {
            $errors[] = 'Why this creator must stay under 600 characters.';
        }

        if (strlen($messagePersonnalise) > 600) {
            $errors[] = 'Personal note must stay under 600 characters.';
        }

        if (strlen($attenteCollaboration) > 600) {
            $errors[] = 'Collaboration expectations must stay under 600 characters.';
        }

        return $errors;
    }
}

?>
