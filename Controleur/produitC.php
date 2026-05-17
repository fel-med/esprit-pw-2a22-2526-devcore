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
    public function afficherProduits(?int $idMarque = null): array
    {
        $where = $idMarque ? "WHERE p.estArchive = 0 AND p.idMarque = :idMarque" : "WHERE p.estArchive = 0";
        $sql   = "SELECT p.*, u.nom AS nomMarque
                  FROM produit p
                  LEFT JOIN utilisateur u ON u.id = p.idMarque
                  $where
                  ORDER BY p.estEpingle DESC, p.sortOrder ASC, p.idProduit DESC";
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
