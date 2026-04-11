<?php
// Controller class for Offre
// Handles offer-related operations and connects to the Offre model

require_once __DIR__ . '/../Modele/offre.php';
require_once __DIR__ . '/../config.php';

class OffreC {
    private $pdo;

    public function __construct() {
        $this->pdo = config::getConnexion();
    }

    private function rowToOffre(array $row) {
        return new Offre(
            $row['idOffre'] ?? null,
            $row['idMarque'] ?? null,
            $row['idCreateurCible'] ?? null,
            $row['titre'] ?? null,
            $row['description'] ?? null,
            $row['objectif'] ?? null,
            $row['budgetMin'] ?? null,
            $row['budgetMax'] ?? null,
            $row['datePublication'] ?? null,
            $row['dateLimite'] ?? null,
            $row['statutOffre'] ?? null
        );
    }

    public function getOffresByMarque($idMarque) {
        $sql = 'SELECT * FROM offre WHERE idMarque = :idMarque ORDER BY datePublication DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['idMarque' => $idMarque]);
        $rows = $stmt->fetchAll();

        $offres = [];
        foreach ($rows as $row) {
            $offres[] = $this->rowToOffre($row);
        }
        return $offres;
    }

    public function getOffreById($idOffre, $idMarque) {
        $sql = 'SELECT * FROM offre WHERE idOffre = :idOffre AND idMarque = :idMarque';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['idOffre' => $idOffre, 'idMarque' => $idMarque]);
        $row = $stmt->fetch();

        return $row ? $this->rowToOffre($row) : null;
    }

    public function getAllOffres() {
        $sql = 'SELECT * FROM offre ORDER BY datePublication DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $offres = [];
        foreach ($rows as $row) {
            $offres[] = $this->rowToOffre($row);
        }
        return $offres;
    }

    public function getOffreByIdAdmin($idOffre) {
        $sql = 'SELECT * FROM offre WHERE idOffre = :idOffre';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['idOffre' => $idOffre]);
        $row = $stmt->fetch();

        return $row ? $this->rowToOffre($row) : null;
    }

    public function getCandidatureCountByOffre($idOffre) {
        $sql = 'SELECT COUNT(*) AS total FROM candidature WHERE origineCandidature = :origine AND idSource = :idSource';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['origine' => 'par_offre', 'idSource' => $idOffre]);
        $row = $stmt->fetch();

        return $row ? intval($row['total']) : 0;
    }

    public function getCandidaturesByOffre($idOffre) {
        $sql = 'SELECT c.*, u.nom AS createurNom, u.email AS createurEmail FROM candidature c LEFT JOIN utilisateur u ON u.id = c.idCreateur WHERE c.origineCandidature = :origine AND c.idSource = :idSource ORDER BY c.dateCandidature DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['origine' => 'par_offre', 'idSource' => $idOffre]);
        return $stmt->fetchAll();
    }

    // New method for creator: Get all offers targeted to a specific creator
    public function getOffresByCreateurCible($idCreateurCible) {
        $sql = 'SELECT * FROM offre WHERE idCreateurCible = :idCreateurCible AND statutOffre = :statut ORDER BY datePublication DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['idCreateurCible' => $idCreateurCible, 'statut' => 'publiee']);
        $rows = $stmt->fetchAll();

        $offres = [];
        foreach ($rows as $row) {
            $offres[] = $this->rowToOffre($row);
        }
        return $offres;
    }

    // New method for creator: Search and filter published offers targeted to a creator
    public function searchOffers($idCreateurCible, $keyword = null, $budgetMin = null, $budgetMax = null, $dateLimite = null) {
        $sql = 'SELECT * FROM offre WHERE idCreateurCible = :idCreateurCible AND statutOffre = :statut';
        $params = ['idCreateurCible' => $idCreateurCible, 'statut' => 'publiee'];

        if ($keyword) {
            $sql .= ' AND (titre LIKE :keyword OR objectif LIKE :keyword OR description LIKE :keyword)';
            $params['keyword'] = '%' . $keyword . '%';
        }
        if ($budgetMin !== null && is_numeric($budgetMin)) {
            $sql .= ' AND budgetMax >= :budgetMin';
            $params['budgetMin'] = $budgetMin;
        }
        if ($budgetMax !== null && is_numeric($budgetMax)) {
            $sql .= ' AND budgetMin <= :budgetMax';
            $params['budgetMax'] = $budgetMax;
        }
        if ($dateLimite) {
            $sql .= ' AND dateLimite >= :dateLimite';
            $params['dateLimite'] = $dateLimite;
        }

        $sql .= ' ORDER BY datePublication DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $offres = [];
        foreach ($rows as $row) {
            $offres[] = $this->rowToOffre($row);
        }
        return $offres;
    }

    // Admin search/filter for offers
    public function searchOffresAdmin($keyword = null, $statut = null, $idMarque = null, $idCreateurCible = null, $budgetMin = null, $budgetMax = null, $dateLimite = null) {
        $sql = 'SELECT * FROM offre WHERE 1=1';
        $params = [];

        if ($keyword) {
            $sql .= ' AND (titre LIKE :keyword OR objectif LIKE :keyword OR description LIKE :keyword)';
            $params['keyword'] = '%' . $keyword . '%';
        }
        if ($statut) {
            $sql .= ' AND statutOffre = :statut';
            $params['statut'] = $statut;
        }
        if ($idMarque !== null && is_numeric($idMarque)) {
            $sql .= ' AND idMarque = :idMarque';
            $params['idMarque'] = $idMarque;
        }
        if ($idCreateurCible !== null && is_numeric($idCreateurCible)) {
            $sql .= ' AND idCreateurCible = :idCreateurCible';
            $params['idCreateurCible'] = $idCreateurCible;
        }
        if ($budgetMin !== null && is_numeric($budgetMin)) {
            $sql .= ' AND budgetMax >= :budgetMin';
            $params['budgetMin'] = $budgetMin;
        }
        if ($budgetMax !== null && is_numeric($budgetMax)) {
            $sql .= ' AND budgetMin <= :budgetMax';
            $params['budgetMax'] = $budgetMax;
        }
        if ($dateLimite) {
            $sql .= ' AND dateLimite >= :dateLimite';
            $params['dateLimite'] = $dateLimite;
        }

        $sql .= ' ORDER BY datePublication DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $offres = [];
        foreach ($rows as $row) {
            $offres[] = $this->rowToOffre($row);
        }
        return $offres;
    }

    // New method for creator: Get a specific published offer by ID targeted to a creator
    public function getPublishedOffreById($idOffre, $idCreateurCible) {
        $sql = 'SELECT * FROM offre WHERE idOffre = :idOffre AND idCreateurCible = :idCreateurCible AND statutOffre = :statut';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['idOffre' => $idOffre, 'idCreateurCible' => $idCreateurCible, 'statut' => 'publiee']);
        $row = $stmt->fetch();

        return $row ? $this->rowToOffre($row) : null;
    }

    public function createOffre(Offre $offre) {
        $sql = 'INSERT INTO offre (idMarque, idCreateurCible, titre, description, objectif, budgetMin, budgetMax, datePublication, dateLimite, statutOffre) VALUES (:idMarque, :idCreateurCible, :titre, :description, :objectif, :budgetMin, :budgetMax, :datePublication, :dateLimite, :statutOffre)';
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            'idMarque' => $offre->getIdMarque(),
            'idCreateurCible' => $offre->getIdCreateurCible(),
            'titre' => $offre->getTitre(),
            'description' => $offre->getDescription(),
            'objectif' => $offre->getObjectif(),
            'budgetMin' => $offre->getBudgetMin(),
            'budgetMax' => $offre->getBudgetMax(),
            'datePublication' => $offre->getDatePublication(),
            'dateLimite' => $offre->getDateLimite(),
            'statutOffre' => $offre->getStatutOffre() ?? 'active'
        ]);
    }

    public function updateOffre(Offre $offre) {
        $sql = 'UPDATE offre SET idCreateurCible = :idCreateurCible, titre = :titre, description = :description, objectif = :objectif, budgetMin = :budgetMin, budgetMax = :budgetMax, datePublication = :datePublication, dateLimite = :dateLimite, statutOffre = :statutOffre WHERE idOffre = :idOffre AND idMarque = :idMarque';
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            'idCreateurCible' => $offre->getIdCreateurCible(),
            'titre' => $offre->getTitre(),
            'description' => $offre->getDescription(),
            'objectif' => $offre->getObjectif(),
            'budgetMin' => $offre->getBudgetMin(),
            'budgetMax' => $offre->getBudgetMax(),
            'datePublication' => $offre->getDatePublication(),
            'dateLimite' => $offre->getDateLimite(),
            'statutOffre' => $offre->getStatutOffre() ?? 'active',
            'idOffre' => $offre->getIdOffre(),
            'idMarque' => $offre->getIdMarque()
        ]);
    }

    public function deleteOffre($idOffre, $idMarque) {
        $sql = 'DELETE FROM offre WHERE idOffre = :idOffre AND idMarque = :idMarque';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['idOffre' => $idOffre, 'idMarque' => $idMarque]);
    }

    public function createCandidature($idCreateur, $origineCandidature, $idSource) {
        $sql = 'INSERT INTO candidature (idCreateur, origineCandidature, idSource, dateCandidature, statutCandidature) VALUES (:idCreateur, :origine, :idSource, NOW(), :statut)';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['idCreateur' => $idCreateur, 'origine' => $origineCandidature, 'idSource' => $idSource, 'statut' => 'en_attente']);
    }

    public function validateOffreData(array $data) {
        $errors = [];

        $titre = trim($data['titre'] ?? '');
        $description = trim($data['description'] ?? '');
        $objectif = trim($data['objectif'] ?? '');
        $budgetMin = trim($data['budgetMin'] ?? '');
        $budgetMax = trim($data['budgetMax'] ?? '');
        $datePublication = trim($data['datePublication'] ?? '');
        $dateLimite = trim($data['dateLimite'] ?? '');

        if ($titre === '') {
            $errors[] = 'Le titre est requis.';
        }
        if ($description === '') {
            $errors[] = 'La description est requise.';
        }
        if ($objectif === '') {
            $errors[] = 'L\'objectif est requis.';
        }

        if ($budgetMin === '' || !is_numeric($budgetMin)) {
            $errors[] = 'Le budget minimum doit être un montant numérique valide.';
        }
        if ($budgetMax === '' || !is_numeric($budgetMax)) {
            $errors[] = 'Le budget maximum doit être un montant numérique valide.';
        }
        if (is_numeric($budgetMin) && is_numeric($budgetMax) && floatval($budgetMin) > floatval($budgetMax)) {
            $errors[] = 'Le budget minimum doit être inférieur ou égal au budget maximum.';
        }

        $publicationDate = DateTime::createFromFormat('Y-m-d', $datePublication);
        if (!$publicationDate || $publicationDate->format('Y-m-d') !== $datePublication) {
            $errors[] = 'La date de publication doit être au format AAAA-MM-JJ.';
        }

        $limiteDate = DateTime::createFromFormat('Y-m-d', $dateLimite);
        if (!$limiteDate || $limiteDate->format('Y-m-d') !== $dateLimite) {
            $errors[] = 'La date limite doit être au format AAAA-MM-JJ.';
        }

        if ($publicationDate && $limiteDate && $publicationDate > $limiteDate) {
            $errors[] = 'La date de publication doit être antérieure ou égale à la date limite.';
        }

        return $errors;
    }
}
?>