<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Modele/produit.php';
require_once __DIR__ . '/notificationC.php';
require_once __DIR__ . '/session_helper.php';

class ProduitC
{
    private function buildModuleLink($path, array $query = []): string
    {
        $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        if (($position = strpos($scriptName, '/Vue/')) !== false) {
            $base = substr($scriptName, 0, $position);
        } elseif (($position = strpos($scriptName, '/Controleur/')) !== false) {
            $base = substr($scriptName, 0, $position);
        } else {
            $base = '/php/cre8connect';
        }

        $link = rtrim($base, '/') . '/' . ltrim((string) $path, '/');
        if (!empty($query)) {
            $link .= '?' . http_build_query($query);
        }

        return $link;
    }

    // ─── UPLOAD ────────────────────────────────────────────────────────────────
    public function gererUploadImage($fichier, ?string $ancienneImage = null): ?string
    {
        if (!isset($fichier) || $fichier['error'] !== UPLOAD_ERR_OK) {
            return $ancienneImage;
        }
        $ext = strtolower(pathinfo($fichier['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            return $ancienneImage;
        }
        $nom  = uniqid('produit_', true) . '.' . $ext;
        $dir  = __DIR__ . '/../Vue/public/produits/';
        $dest = $dir . $nom;
        if (move_uploaded_file($fichier['tmp_name'], $dest)) {
            if ($ancienneImage && file_exists($dir . $ancienneImage)) {
                unlink($dir . $ancienneImage);
            }
            return $nom;
        }
        return $ancienneImage;
    }

    // ─── AJOUTER ───────────────────────────────────────────────────────────────
    public function ajouterProduit(Produit $produit): void
    {
        if (empty(trim($produit->getNom())) || $produit->getPrix() < 0) {
            throw new Exception("Invalid product: name required, price >= 0");
        }
        $sql = "INSERT INTO produit
                    (nomProduit, description, caracteristiques, prix, idMarque, image,
                     categorie, estArchive, estEpingle, sortOrder, dateDisponibilite, noteInterne)
                VALUES
                    (:nom, :desc, :carac, :prix, :idMarque, :image,
                     :cat, :arch, :pin, :sort, :dispo, :note)";
        $db = config::getConnexion();
        $q  = $db->prepare($sql);
        $q->execute([
            'nom'      => $produit->getNom(),
            'desc'     => $produit->getDescription(),
            'carac'    => $produit->getCaracteristiques(),
            'prix'     => $produit->getPrix(),
            'idMarque' => $produit->getIdMarque(),
            'image'    => $produit->getImage(),
            'cat'      => $produit->getCategorie(),
            'arch'     => $produit->getEstArchive() ? 1 : 0,
            'pin'      => $produit->getEstEpingle() ? 1 : 0,
            'sort'     => $produit->getSortOrder() ?? 0,
            'dispo'    => $produit->getDateDisponibilite(),
            'note'     => $produit->getNoteInterne(),
        ]);
    }

    // ─── MODIFIER ──────────────────────────────────────────────────────────────
    public function modifierProduit(Produit $produit, int $id): void
    {
        if (empty(trim($produit->getNom())) || $produit->getPrix() < 0) {
            throw new Exception("Invalid product: name required, price >= 0");
        }
        $sql = "UPDATE produit SET
                    nomProduit        = :nom,
                    description       = :desc,
                    caracteristiques  = :carac,
                    prix              = :prix,
                    image             = :image,
                    categorie         = :cat,
                    estArchive        = :arch,
                    estEpingle        = :pin,
                    dateDisponibilite = :dispo,
                    noteInterne       = :note
                WHERE idProduit = :id";
        $db = config::getConnexion();
        $q  = $db->prepare($sql);
        $q->execute([
            'nom'  => $produit->getNom(),
            'desc' => $produit->getDescription(),
            'carac'=> $produit->getCaracteristiques(),
            'prix' => $produit->getPrix(),
            'image'=> $produit->getImage(),
            'cat'  => $produit->getCategorie(),
            'arch' => $produit->getEstArchive() ? 1 : 0,
            'pin'  => $produit->getEstEpingle() ? 1 : 0,
            'dispo'=> $produit->getDateDisponibilite(),
            'note' => $produit->getNoteInterne(),
            'id'   => $id,
        ]);
    }

    // ─── AFFICHER (actifs) ─────────────────────────────────────────────────────
    public function afficherProduits(?int $idMarque = null, string $sort = ''): array
    {
        $where = $idMarque ? "WHERE p.estArchive = 0 AND p.idMarque = :idMarque" : "WHERE p.estArchive = 0";
        $orderBy = "p.estEpingle DESC, p.sortOrder ASC, p.idProduit DESC";
        if ($sort === 'rating_desc') {
            $orderBy = "(COALESCE(rv.reviewCount, 0) > 0) DESC, rv.avgRating DESC, rv.reviewCount DESC, p.idProduit DESC";
        } elseif ($sort === 'rating_asc') {
            $orderBy = "(COALESCE(rv.reviewCount, 0) > 0) DESC, rv.avgRating ASC, rv.reviewCount DESC, p.idProduit DESC";
        } elseif ($sort === 'reviews_desc') {
            $orderBy = "COALESCE(rv.reviewCount, 0) DESC, COALESCE(rv.avgRating, 0) DESC, p.idProduit DESC";
        }

        $sql   = "SELECT p.*, u.nom AS nomMarque,
                         COALESCE(rv.avgRating, 0) AS avgRating,
                         COALESCE(rv.reviewCount, 0) AS reviewCount
                  FROM produit p
                  LEFT JOIN utilisateur u ON u.id = p.idMarque
                  LEFT JOIN (
                      SELECT idProduit, AVG(rating) AS avgRating, COUNT(*) AS reviewCount
                      FROM produit_reviews
                      WHERE status = 'published'
                      GROUP BY idProduit
                  ) rv ON rv.idProduit = p.idProduit
                  $where
                  ORDER BY $orderBy";
        $db    = config::getConnexion();
        if ($idMarque) {
            $q = $db->prepare($sql);
            $q->execute(['idMarque' => $idMarque]);
            return $q->fetchAll();
        }
        return $db->query($sql)->fetchAll();
    }

    // ─── AFFICHER ARCHIVÉS ─────────────────────────────────────────────────────
    public function afficherProduitsArchives(?int $idMarque = null): array
    {
        $where = $idMarque ? "WHERE p.estArchive = 1 AND p.idMarque = :idMarque" : "WHERE p.estArchive = 1";
        $sql   = "SELECT p.*, u.nom AS nomMarque
                  FROM produit p
                  LEFT JOIN utilisateur u ON u.id = p.idMarque
                  $where
                  ORDER BY p.idProduit DESC";
        $db    = config::getConnexion();
        if ($idMarque) {
            $q = $db->prepare($sql);
            $q->execute(['idMarque' => $idMarque]);
            return $q->fetchAll();
        }
        return $db->query($sql)->fetchAll();
    }

    // ─── RÉCUPÉRER UN PRODUIT ──────────────────────────────────────────────────
    public function recupererProduit(int $id): ?array
    {
        $q = config::getConnexion()->prepare("
            SELECT p.*, u.nom AS nomMarque
            FROM produit p
            LEFT JOIN utilisateur u ON u.id = p.idMarque
            WHERE p.idProduit = :id
        ");
        $q->execute(['id' => $id]);
        $r = $q->fetch();
        return $r ?: null;
    }

    // ─── SUPPRIMER ─────────────────────────────────────────────────────────────
    public function supprimerProduit(int $id): void
    {
        $p = $this->recupererProduit($id);
        if ($p && $p['image']) {
            $f = __DIR__ . '/../Vue/public/produits/' . $p['image'];
            if (file_exists($f)) unlink($f);
        }
        $q = config::getConnexion()->prepare("DELETE FROM produit WHERE idProduit = :id");
        $deleted = $q->execute(['id' => $id]) && $q->rowCount() > 0;

        $ownerId = (int) ($p['idMarque'] ?? 0);
        $adminId = cc_current_user_id();
        $adminRole = cc_current_user_role();
        if ($deleted && $p && $ownerId > 0 && isBackOfficeRole($adminRole) && (!$adminId || $ownerId !== $adminId)) {
            (new NotificationC(config::getConnexion()))->createNotification(
                $ownerId,
                'admin_product_removed',
                'Product removed',
                'An admin removed one of your products.',
                $this->buildModuleLink('Vue/FrontOffice/produit/index.php'),
                'produit',
                $id,
                $adminId,
                $adminRole,
                'admin_product_removed_' . $id . '_user_' . $ownerId,
                [
                    'product_id' => $id,
                    'product_name' => (string) ($p['nomProduit'] ?? ''),
                ]
            );
        }
    }

    // ─── ARCHIVER / DÉSARCHIVER ────────────────────────────────────────────────
    public function toggleArchive(int $id): void
    {
        $q = config::getConnexion()->prepare(
            "UPDATE produit SET estArchive = NOT estArchive WHERE idProduit = :id"
        );
        $q->execute(['id' => $id]);
    }

    // ─── ÉPINGLER / DÉSÉPINGLER ────────────────────────────────────────────────
    public function toggleEpingle(int $id): void
    {
        $q = config::getConnexion()->prepare(
            "UPDATE produit SET estEpingle = NOT estEpingle WHERE idProduit = :id"
        );
        $q->execute(['id' => $id]);
    }

    // ─── RÉORDONNER ────────────────────────────────────────────────────────────
    public function reordonnerProduits(array $ordre): void
    {
        $db = config::getConnexion();
        $q  = $db->prepare("UPDATE produit SET sortOrder = :ordre WHERE idProduit = :id");
        foreach ($ordre as $index => $id) {
            $q->execute(['ordre' => $index, 'id' => (int)$id]);
        }
    }

    // ─── CATÉGORIES DISTINCTES ────────────────────────────────────────────────
    public function getCategories(?int $idMarque = null): array
    {
        $where = $idMarque
            ? "WHERE estArchive = 0 AND categorie IS NOT NULL AND idMarque = :idMarque"
            : "WHERE estArchive = 0 AND categorie IS NOT NULL";
        $sql = "SELECT DISTINCT categorie FROM produit $where ORDER BY categorie ASC";
        $db  = config::getConnexion();
        if ($idMarque) {
            $q = $db->prepare($sql);
            $q->execute(['idMarque' => $idMarque]);
            return $q->fetchAll(PDO::FETCH_COLUMN);
        }
        return $db->query($sql)->fetchAll(PDO::FETCH_COLUMN);
    }

    // ─── TOP PRODUITS ──────────────────────────────────────────────────────────
    public function getTopProduits(int $limit = 5): array
    {
        $sql = "SELECT p.*, COUNT(o.idOffre) as nbOffres
                FROM produit p
                LEFT JOIN offre o ON o.idProduit = p.idProduit
                WHERE p.estArchive = 0
                GROUP BY p.idProduit
                ORDER BY nbOffres DESC, p.idProduit DESC
                LIMIT :limit";
        $db = config::getConnexion();
        $q  = $db->prepare($sql);
        $q->bindValue(':limit', $limit, PDO::PARAM_INT);
        $q->execute();
        return $q->fetchAll();
    }

    // ─── JOINTURE CAMPAGNE ↔ PRODUIT ──────────────────────────────────────────

    public function getProduitsByCampagne(int $idCampagne): array
    {
        $sql = "SELECT p.*
                FROM produit p
                INNER JOIN campagne_produit cp ON cp.idProduit = p.idProduit
                WHERE cp.idCampagne = :c AND p.estArchive = 0
                ORDER BY p.estEpingle DESC, p.sortOrder ASC, p.idProduit DESC";
        $q = config::getConnexion()->prepare($sql);
        $q->execute(['c' => $idCampagne]);
        return $q->fetchAll();
    }

    public function getProduitsDisponiblesPourCampagne(int $idCampagne, ?int $idMarque = null): array
    {
        $m   = $idMarque ? "AND p.idMarque = :idMarque" : "";
        $sql = "SELECT p.*
                FROM produit p
                WHERE p.estArchive = 0 $m
                  AND p.idProduit NOT IN (
                      SELECT cp.idProduit FROM campagne_produit cp WHERE cp.idCampagne = :c
                  )
                ORDER BY p.estEpingle DESC, p.nomProduit ASC";
        $db     = config::getConnexion();
        $q      = $db->prepare($sql);
        $params = ['c' => $idCampagne];
        if ($idMarque) $params['idMarque'] = $idMarque;
        $q->execute($params);
        return $q->fetchAll();
    }

    // ════════════════════════════════════════════════════════════════
    // ─── IA : OPTIMISATION PRODUIT (MARQUE) ───────────────────────
    // Utilisée dans : Vue/FrontOffice/produit/index.php
    // ════════════════════════════════════════════════════════════════

    public function getProductReviewStats(array $productIds): array
    {
        $productIds = array_values(array_unique(array_filter(array_map('intval', $productIds), fn($id) => $id > 0)));
        if (empty($productIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $sql = "SELECT idProduit, AVG(rating) AS avgRating, COUNT(*) AS reviewCount
                FROM produit_reviews
                WHERE status = 'published' AND idProduit IN ($placeholders)
                GROUP BY idProduit";
        $q = config::getConnexion()->prepare($sql);
        $q->execute($productIds);

        $stats = [];
        foreach ($q->fetchAll() as $row) {
            $id = (int) $row['idProduit'];
            $stats[$id] = [
                'avgRating' => $row['avgRating'] !== null ? round((float) $row['avgRating'], 1) : null,
                'reviewCount' => (int) ($row['reviewCount'] ?? 0),
            ];
        }

        return $stats;
    }

    public function getProductReviewsForProducts(array $productIds): array
    {
        $productIds = array_values(array_unique(array_filter(array_map('intval', $productIds), fn($id) => $id > 0)));
        if (empty($productIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $sql = "SELECT r.idReview, r.idProduit, r.idCreateur, r.rating, r.title, r.reviewText, r.status,
                       r.createdAt, r.updatedAt, u.nom AS creatorName, pr.imageName
                FROM produit_reviews r
                LEFT JOIN utilisateur u ON u.id = r.idCreateur
                LEFT JOIN profile pr ON pr.idUtilisateur = u.id
                WHERE r.status = 'published' AND r.idProduit IN ($placeholders)
                ORDER BY r.createdAt DESC, r.idReview DESC";
        $q = config::getConnexion()->prepare($sql);
        $q->execute($productIds);

        $reviews = [];
        foreach ($q->fetchAll() as $row) {
            $id = (int) $row['idProduit'];
            $reviews[$id][] = $this->normalizeReviewRow($row);
        }

        return $reviews;
    }

    public function getProductReviewsByProduct(int $productId): array
    {
        return $this->getProductReviewsForProducts([$productId])[$productId] ?? [];
    }

    public function getPaginatedProductReviews(int $productId, int $page = 1, int $perPage = 5, ?int $rating = null): array
    {
        if ($productId <= 0) {
            return [
                'reviews' => [],
                'total' => 0,
                'page' => 1,
                'pages' => 1,
                'perPage' => max(1, $perPage),
                'rating' => null,
            ];
        }

        $page = max(1, $page);
        $perPage = max(1, min(12, $perPage));
        $rating = ($rating !== null && $rating >= 1 && $rating <= 5) ? $rating : null;

        $where = "WHERE r.status = 'published' AND r.idProduit = :productId";
        $params = ['productId' => $productId];
        if ($rating !== null) {
            $where .= " AND r.rating = :rating";
            $params['rating'] = $rating;
        }

        $db = config::getConnexion();
        $countStmt = $db->prepare("SELECT COUNT(*) FROM produit_reviews r $where");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT r.idReview, r.idProduit, r.idCreateur, r.rating, r.title, r.reviewText, r.status,
                       r.createdAt, r.updatedAt, u.nom AS creatorName, pr.imageName
                FROM produit_reviews r
                LEFT JOIN utilisateur u ON u.id = r.idCreateur
                LEFT JOIN profile pr ON pr.idUtilisateur = u.id
                $where
                ORDER BY r.createdAt DESC, r.idReview DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'reviews' => array_map(fn($row) => $this->normalizeReviewRow($row), $stmt->fetchAll()),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'perPage' => $perPage,
            'rating' => $rating,
        ];
    }

    public function getAllProductReviewsByProduct(int $productId): array
    {
        if ($productId <= 0) {
            return [];
        }

        $sql = "SELECT r.idReview, r.idProduit, r.idCreateur, r.rating, r.title, r.reviewText, r.status,
                       r.createdAt, r.updatedAt, u.nom AS creatorName, pr.imageName
                FROM produit_reviews r
                LEFT JOIN utilisateur u ON u.id = r.idCreateur
                LEFT JOIN profile pr ON pr.idUtilisateur = u.id
                WHERE r.idProduit = :productId
                ORDER BY r.createdAt DESC, r.idReview DESC";
        $q = config::getConnexion()->prepare($sql);
        $q->execute(['productId' => $productId]);

        return array_map(fn($row) => $this->normalizeReviewRow($row), $q->fetchAll());
    }

    public function getAllProductReviewStats(int $productId): array
    {
        if ($productId <= 0) {
            return ['avgRating' => null, 'reviewCount' => 0];
        }

        $q = config::getConnexion()->prepare(
            "SELECT AVG(rating) AS avgRating, COUNT(*) AS reviewCount
             FROM produit_reviews
             WHERE idProduit = :productId"
        );
        $q->execute(['productId' => $productId]);
        $row = $q->fetch() ?: [];

        return [
            'avgRating' => isset($row['avgRating']) && $row['avgRating'] !== null ? round((float) $row['avgRating'], 1) : null,
            'reviewCount' => (int) ($row['reviewCount'] ?? 0),
        ];
    }

    public function getCreatorReviewsForProducts(int $creatorId, array $productIds): array
    {
        $productIds = array_values(array_unique(array_filter(array_map('intval', $productIds), fn($id) => $id > 0)));
        if ($creatorId <= 0 || empty($productIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $sql = "SELECT idReview, idProduit, idCreateur, rating, title, reviewText, status, createdAt, updatedAt
                FROM produit_reviews
                WHERE idCreateur = ? AND idProduit IN ($placeholders)";
        $q = config::getConnexion()->prepare($sql);
        $q->execute(array_merge([$creatorId], $productIds));

        $reviews = [];
        foreach ($q->fetchAll() as $row) {
            $reviews[(int) $row['idProduit']] = $this->normalizeReviewRow($row);
        }

        return $reviews;
    }

    public function getCreatorReviewForProduct(int $productId, int $creatorId): ?array
    {
        return $this->getCreatorReviewsForProducts($creatorId, [$productId])[$productId] ?? null;
    }

    public function saveOrUpdateProductReview(int $productId, int $creatorId, int $rating, string $title, string $reviewText): array
    {
        if ($creatorId <= 0) {
            throw new InvalidArgumentException('You must be logged in to review this product.');
        }

        $product = $this->recupererProduit($productId);
        if (!$product || !empty($product['estArchive'])) {
            throw new InvalidArgumentException('Product not found or unavailable.');
        }

        if ($rating < 1 || $rating > 5) {
            throw new InvalidArgumentException('Rating must be between 1 and 5.');
        }

        $title = trim(strip_tags($title));
        $reviewText = trim(strip_tags($reviewText));
        if (function_exists('mb_substr')) {
            $title = mb_substr($title, 0, 150, 'UTF-8');
            $reviewText = mb_substr($reviewText, 0, 2000, 'UTF-8');
        } else {
            $title = substr($title, 0, 150);
            $reviewText = substr($reviewText, 0, 2000);
        }

        if ($title === '') {
            throw new InvalidArgumentException('Review title is required.');
        }
        if ($reviewText === '') {
            throw new InvalidArgumentException('Review text is required.');
        }

        $sql = "INSERT INTO produit_reviews (idProduit, idCreateur, rating, title, reviewText, status)
                VALUES (:productId, :creatorId, :rating, :title, :reviewText, 'published')
                ON DUPLICATE KEY UPDATE
                    rating = VALUES(rating),
                    title = VALUES(title),
                    reviewText = VALUES(reviewText),
                    status = 'published',
                    updatedAt = CURRENT_TIMESTAMP";
        $q = config::getConnexion()->prepare($sql);
        $q->execute([
            'productId' => $productId,
            'creatorId' => $creatorId,
            'rating' => $rating,
            'title' => $title,
            'reviewText' => $reviewText,
        ]);

        return $this->getCreatorReviewForProduct($productId, $creatorId) ?? [];
    }

    private function normalizeReviewRow(array $row): array
    {
        return [
            'idReview' => (int) ($row['idReview'] ?? 0),
            'idProduit' => (int) ($row['idProduit'] ?? 0),
            'idCreateur' => (int) ($row['idCreateur'] ?? 0),
            'rating' => (int) ($row['rating'] ?? 0),
            'title' => (string) ($row['title'] ?? ''),
            'reviewText' => (string) ($row['reviewText'] ?? ''),
            'status' => (string) ($row['status'] ?? 'published'),
            'creatorName' => (string) ($row['creatorName'] ?? 'Creator'),
            'imageName' => (string) ($row['imageName'] ?? ''),
            'createdAt' => (string) ($row['createdAt'] ?? ''),
            'updatedAt' => (string) ($row['updatedAt'] ?? ''),
        ];
    }

    public function optimiserProduitIA(
        string $nom, string $description, string $categorie
    ): ?array {
        $prompt = "Optimise la fiche produit suivante pour Cre8Connect (collaboration marques-créateurs).

Produit :
- Nom : $nom
- Description actuelle : $description
- Catégorie : $categorie

Réponds UNIQUEMENT avec un objet JSON valide contenant :
{
  \"description_amelioree\": \"Description marketing optimisée (200-500 caractères)\",
  \"mots_cles\": [\"mot1\", \"mot2\", \"mot3\", \"mot4\", \"mot5\"],
  \"hashtags\": [\"#h1\", \"#h2\", \"#h3\", \"#h4\", \"#h5\", \"#h6\", \"#h7\", \"#h8\"],
  \"conseil_prix\": \"Conseil sur le positionnement prix\",
  \"score_attractivite\": \"Note de 1 à 10\"
}

Adapté au marché francophone. Ne retourne RIEN d'autre que le JSON.";

        return $this->_parseIA(callOpenRouter($prompt));
    }

    // ─── IA : AUDIT PRODUIT (ADMIN) ───────────────────────────────
    // Utilisée dans : Vue/BackOffice/produit/index.php

    public function auditProduitIA(
        string $nom, string $description, float $prix, string $categorie
    ): ?array {
        $prompt = "Tu es un auditeur qualité pour l'admin de Cre8Connect.

Produit à auditer :
- Nom : $nom
- Description : $description
- Prix : {$prix}€
- Catégorie : $categorie

Réponds UNIQUEMENT en JSON :
{
  \"score_qualite\": \"Note de 1 à 10\",
  \"conformite\": \"conforme / non-conforme / à améliorer\",
  \"problemes\": [\"problème 1\", \"problème 2\"],
  \"ameliorations\": [\"amélioration 1\", \"amélioration 2\"],
  \"prix_coherent\": \"oui/non avec justification\",
  \"pret_pour_campagne\": \"oui/non avec justification\"
}

Ne retourne RIEN d'autre que le JSON.";

        return $this->_parseIA(callOpenRouter($prompt));
    }

    // ─── Utilitaire privé ──────────────────────────────────────────

    private function _parseIA(?string $raw): ?array
    {
        if (!$raw) return null;
        $clean  = trim(preg_replace('/```json\s*|\s*```/', '', $raw));
        $parsed = json_decode($clean, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("ProduitC IA: JSON invalide — " . json_last_error_msg());
            return null;
        }
        return $parsed;
    }
}
