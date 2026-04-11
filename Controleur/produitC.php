<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Modele/produit.php';

class ProduitC {

    // Gère l'upload de fichier et retourne le nom du fichier sauvegardé (ou null)
    public function gererUploadImage($fichier, $ancienneImage = null) {
        if (!isset($fichier) || $fichier['error'] !== UPLOAD_ERR_OK) {
            return $ancienneImage; // pas de nouveau fichier, on garde l'ancien
        }

        $extensionsAutorisees = ['jpg', 'jpeg', 'png', 'webp'];
        $extension = strtolower(pathinfo($fichier['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, $extensionsAutorisees)) {
            return $ancienneImage; // extension non autorisée, on garde l'ancien
        }

        $nomFichier = uniqid('produit_', true) . '.' . $extension;
        $dossier = __DIR__ . '/../Vue/public/produits/';
        $destination = $dossier . $nomFichier;

        if (move_uploaded_file($fichier['tmp_name'], $destination)) {
            // Supprimer l'ancienne image si elle existe
            if ($ancienneImage && file_exists($dossier . $ancienneImage)) {
                unlink($dossier . $ancienneImage);
            }
            return $nomFichier;
        }

        return $ancienneImage;
    }

    public function ajouterProduit($produit) {
        $sql = "INSERT INTO produit (nomProduit, description, caracteristiques, prix, idMarque, image) 
                VALUES (:nom, :description, :caracteristiques, :prix, :idMarque, :image)";
        $db = config::getConnexion();
        $query = $db->prepare($sql);
        $query->execute([
            'nom'             => $produit->getNom(),
            'description'     => $produit->getDescription(),
            'caracteristiques'=> $produit->getCaracteristiques(),
            'prix'            => $produit->getPrix(),
            'idMarque'        => $produit->getIdMarque(),
            'image'           => $produit->getImage(), // ← AJOUTÉ
        ]);
    }

    public function afficherProduits() {
        $sql = "SELECT * FROM produit ORDER BY idProduit DESC";
        $db = config::getConnexion();
        return $db->query($sql)->fetchAll();
    }

    public function supprimerProduit($id) {
        // Récupérer l'image avant de supprimer pour effacer le fichier
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

    public function modifierProduit($produit, $id) {
        $sql = "UPDATE produit SET 
                nomProduit        = :nom,
                description       = :description,
                caracteristiques  = :caracteristiques,
                prix              = :prix,
                image             = :image
                WHERE idProduit   = :id";
        $db = config::getConnexion();
        $query = $db->prepare($sql);
        $query->execute([
            'nom'             => $produit->getNom(),
            'description'     => $produit->getDescription(),
            'caracteristiques'=> $produit->getCaracteristiques(),
            'prix'            => $produit->getPrix(),
            'image'           => $produit->getImage(), // ← AJOUTÉ
            'id'              => $id,
        ]);
    }

    public function recupererProduit($id) {
        $sql = "SELECT * FROM produit WHERE idProduit = :id";
        $db = config::getConnexion();
        $query = $db->prepare($sql);
        $query->execute(['id' => $id]);
        return $query->fetch();
    }
}
?>