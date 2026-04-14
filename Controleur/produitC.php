<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Modele/produit.php';

class ProduitC {

    // ─── UPLOAD ────────────────────────────────────────────────────────────────
    public function gererUploadImage($fichier, $ancienneImage = null) {
        if (!isset($fichier) || $fichier['error'] !== UPLOAD_ERR_OK) {
            return $ancienneImage;
        }
        $extensionsAutorisees = ['jpg', 'jpeg', 'png', 'webp'];
        $extension = strtolower(pathinfo($fichier['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $extensionsAutorisees)) {
            return $ancienneImage;
        }
        $nomFichier  = uniqid('produit_', true) . '.' . $extension;
        $dossier     = __DIR__ . '/../Vue/public/produits/';
        $destination = $dossier . $nomFichier;
        if (move_uploaded_file($fichier['tmp_name'], $destination)) {
            if ($ancienneImage && file_exists($dossier . $ancienneImage)) {
                unlink($dossier . $ancienneImage);
            }
            return $nomFichier;
        }
        return $ancienneImage;
    }

    // ─── SQL MIGRATION HELPER (run once) ───────────────────────────────────────
    // Call this once to add new columns if they don't exist yet.
    public function migrerColonnes() {
        $db = config::getConnexion();
        $alterations = [
            "ALTER TABLE produit ADD COLUMN IF NOT EXISTS categorie VARCHAR(100) DEFAULT NULL",
            "ALTER TABLE produit ADD COLUMN IF NOT EXISTS estArchive TINYINT(1) NOT NULL DEFAULT 0",
            "ALTER TABLE produit ADD COLUMN IF NOT EXISTS estEpingle TINYINT(1) NOT NULL DEFAULT 0",
            "ALTER TABLE produit ADD COLUMN IF NOT EXISTS sortOrder INT NOT NULL DEFAULT 0",
            "ALTER TABLE produit ADD COLUMN IF NOT EXISTS dateDisponibilite DATE DEFAULT NULL",
            "ALTER TABLE produit ADD COLUMN IF NOT EXISTS noteInterne TEXT DEFAULT NULL",
        ];
        foreach ($alterations as $sql) {
            try { $db->exec($sql); } catch (Exception $e) { /* ignore if already exists */ }
        }
    }

    // ─── AJOUTER ───────────────────────────────────────────────────────────────
    public function ajouterProduit($produit) {
        if (empty(trim($produit->getNom())) || $produit->getPrix() < 0) {
            throw new Exception("Invalid product: name required, price must be >=0");
        }
        $sql = "INSERT INTO produit 
                    (nomProduit, description, caracteristiques, prix, idMarque, image,
                     categorie, estArchive, estEpingle, sortOrder, dateDisponibilite, noteInterne)
                VALUES 
                    (:nom, :description, :caracteristiques, :prix, :idMarque, :image,
                     :categorie, :estArchive, :estEpingle, :sortOrder, :dateDisponibilite, :noteInterne)";
        $db = config::getConnexion();
        $query = $db->prepare($sql);
        $query->execute([
            'nom'               => $produit->getNom(),
            'description'       => $produit->getDescription(),
            'caracteristiques'  => $produit->getCaracteristiques(),
            'prix'              => $produit->getPrix(),
            'idMarque'          => $produit->getIdMarque(),
            'image'             => $produit->getImage(),
            'categorie'         => $produit->getCategorie(),
            'estArchive'        => $produit->getEstArchive() ? 1 : 0,
            'estEpingle'        => $produit->getEstEpingle() ? 1 : 0,
            'sortOrder'         => $produit->getSortOrder() ?? 0,
            'dateDisponibilite' => $produit->getDateDisponibilite(),
            'noteInterne'       => $produit->getNoteInterne(),
        ]);
    }

    // ─── MODIFIER ──────────────────────────────────────────────────────────────
    public function modifierProduit($produit, $id) {
        if (empty(trim($produit->getNom())) || $produit->getPrix() < 0) {
            throw new Exception("Invalid product: name required, price must be >=0");
        }
        $sql = "UPDATE produit SET
                    nomProduit        = :nom,
                    description       = :description,
                    caracteristiques  = :caracteristiques,
                    prix              = :prix,
                    image             = :image,
                    categorie         = :categorie,
                    estArchive        = :estArchive,
                    estEpingle        = :estEpingle,
                    dateDisponibilite = :dateDisponibilite,
                    noteInterne       = :noteInterne
                WHERE idProduit = :id";
        $db = config::getConnexion();
        $query = $db->prepare($sql);
        $query->execute([
            'nom'               => $produit->getNom(),
            'description'       => $produit->getDescription(),
            'caracteristiques'  => $produit->getCaracteristiques(),
            'prix'              => $produit->getPrix(),
            'image'             => $produit->getImage(),
            'categorie'         => $produit->getCategorie(),
            'estArchive'        => $produit->getEstArchive() ? 1 : 0,
            'estEpingle'        => $produit->getEstEpingle() ? 1 : 0,
            'dateDisponibilite' => $produit->getDateDisponibilite(),
            'noteInterne'       => $produit->getNoteInterne(),
            'id'                => $id,
        ]);
    }

    // ─── AFFICHER (actifs, épinglés en tête, puis ordre drag) ──────────────────
    public function afficherProduits($idMarque = null) {
        $where = $idMarque ? "WHERE estArchive = 0 AND idMarque = :idMarque" : "WHERE estArchive = 0";
        $sql = "SELECT * FROM produit $where ORDER BY estEpingle DESC, sortOrder ASC, idProduit DESC";
        $db = config::getConnexion();
        if ($idMarque) {
            $q = $db->prepare($sql);
            $q->execute(['idMarque' => $idMarque]);
            return $q->fetchAll();
        }
        return $db->query($sql)->fetchAll();
    }

    // ─── AFFICHER ARCHIVÉS ─────────────────────────────────────────────────────
    public function afficherProduitsArchives($idMarque = null) {
        $where = $idMarque ? "WHERE estArchive = 1 AND idMarque = :idMarque" : "WHERE estArchive = 1";
        $sql = "SELECT * FROM produit $where ORDER BY idProduit DESC";
        $db = config::getConnexion();
        if ($idMarque) {
            $q = $db->prepare($sql);
            $q->execute(['idMarque' => $idMarque]);
            return $q->fetchAll();
        }
        return $db->query($sql)->fetchAll();
    }

    // ─── RÉCUPÉRER UN PRODUIT ──────────────────────────────────────────────────
    public function recupererProduit($id) {
        $sql = "SELECT * FROM produit WHERE idProduit = :id";
        $db = config::getConnexion();
        $query = $db->prepare($sql);
        $query->execute(['id' => $id]);
        return $query->fetch();
    }

    // ─── SUPPRIMER ─────────────────────────────────────────────────────────────
    public function supprimerProduit($id) {
        $produit = $this->recupererProduit($id);
        if ($produit && $produit['image']) {
            $fichier = __DIR__ . '/../Vue/public/produits/' . $produit['image'];
            if (file_exists($fichier)) unlink($fichier);
        }
        $sql = "DELETE FROM produit WHERE idProduit = :id";
        $db = config::getConnexion();
        $req = $db->prepare($sql);
        $req->execute(['id' => $id]);
    }

    // ─── ARCHIVER / DÉSARCHIVER ────────────────────────────────────────────────
    public function toggleArchive($id) {
        $sql = "UPDATE produit SET estArchive = NOT estArchive WHERE idProduit = :id";
        $db = config::getConnexion();
        $q = $db->prepare($sql);
        $q->execute(['id' => $id]);
    }

    // ─── ÉPINGLER / DÉSÉPINGLER ────────────────────────────────────────────────
    public function toggleEpingle($id) {
        $sql = "UPDATE produit SET estEpingle = NOT estEpingle WHERE idProduit = :id";
        $db = config::getConnexion();
        $q = $db->prepare($sql);
        $q->execute(['id' => $id]);
    }

    // ─── RÉORDONNER (drag & drop) ──────────────────────────────────────────────
    // $ordre = tableau ordonné d'idProduit
    public function reordonnerProduits(array $ordre) {
        $db = config::getConnexion();
        $sql = "UPDATE produit SET sortOrder = :ordre WHERE idProduit = :id";
        $q = $db->prepare($sql);
        foreach ($ordre as $index => $id) {
            $q->execute(['ordre' => $index, 'id' => (int)$id]);
        }
    }

    // ─── CATÉGORIES DISTINCTES ────────────────────────────────────────────────
    public function getCategories($idMarque = null) {
        $where = $idMarque ? "WHERE estArchive = 0 AND categorie IS NOT NULL AND idMarque = :idMarque"
                           : "WHERE estArchive = 0 AND categorie IS NOT NULL";
        $sql = "SELECT DISTINCT categorie FROM produit $where ORDER BY categorie ASC";
        $db = config::getConnexion();
        if ($idMarque) {
            $q = $db->prepare($sql);
            $q->execute(['idMarque' => $idMarque]);
            return $q->fetchAll(\PDO::FETCH_COLUMN);
        }
        return $db->query($sql)->fetchAll(\PDO::FETCH_COLUMN);
    }

    // ─── TOP PRODUITS (les plus demandés via offres) ───────────────────────────
    // Nécessite la table offre avec une colonne produit_id (à adapter selon votre schéma)
    public function getTopProduits($limit = 5) {
        $sql = "SELECT p.*, COUNT(o.idOffre) as nbOffres
                FROM produit p
                LEFT JOIN offre o ON o.idProduit = p.idProduit
                WHERE p.estArchive = 0
                GROUP BY p.idProduit
                ORDER BY nbOffres DESC, p.idProduit DESC
                LIMIT :limit";
        $db = config::getConnexion();
        $q = $db->prepare($sql);
        $q->bindValue(':limit', (int)$limit, \PDO::PARAM_INT);
        $q->execute();
        return $q->fetchAll();
    }
}
?>