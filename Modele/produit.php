<?php
class Produit {
    private $id;
    private $nom;
    private $description;
    private $caracteristiques;
    private $prix;
    private $id_marque;
    private $image; // ← AJOUTÉ

    public function __construct($id, $nom, $description, $caracteristiques, $prix, $id_marque, $image = null) {
        $this->id = $id;
        $this->nom = $nom;
        $this->description = $description;
        $this->caracteristiques = $caracteristiques;
        $this->prix = $prix;
        $this->id_marque = $id_marque;
        $this->image = $image; // ← AJOUTÉ
    }

    public function getId() { return $this->id; }
    public function getNom() { return $this->nom; }
    public function getDescription() { return $this->description; }
    public function getCaracteristiques() { return $this->caracteristiques; }
    public function getPrix() { return $this->prix; }
    public function getIdMarque() { return $this->id_marque; }
    public function getImage() { return $this->image; } // ← AJOUTÉ

    public function setNom($nom) { $this->nom = $nom; }
    public function setDescription($description) { $this->description = $description; }
    public function setCaracteristiques($c) { $this->caracteristiques = $c; }
    public function setPrix($prix) { $this->prix = $prix; }
    public function setImage($image) { $this->image = $image; } // ← AJOUTÉ
}
?>