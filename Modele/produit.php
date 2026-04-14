<?php
class Produit {
    private $id;
    private $nom;
    private $description;
    private $caracteristiques;
    private $prix;
    private $id_marque;
    private $image;
    private $categorie;
    private $estArchive;
    private $estEpingle;
    private $sortOrder;
    private $dateDisponibilite;
    private $noteInterne;

    public function __construct(
        $id, $nom, $description, $caracteristiques, $prix, $id_marque,
        $image = null, $categorie = null, $estArchive = 0, $estEpingle = 0,
        $sortOrder = 0, $dateDisponibilite = null, $noteInterne = null
    ) {
        $this->id               = $id;
        $this->nom              = $nom;
        $this->description      = $description;
        $this->caracteristiques = $caracteristiques;
        $this->prix             = $prix;
        $this->id_marque        = $id_marque;
        $this->image            = $image;
        $this->categorie        = $categorie;
        $this->estArchive       = $estArchive;
        $this->estEpingle       = $estEpingle;
        $this->sortOrder        = $sortOrder;
        $this->dateDisponibilite = $dateDisponibilite;
        $this->noteInterne      = $noteInterne;
    }

    public function getId()               { return $this->id; }
    public function getNom()              { return $this->nom; }
    public function getDescription()      { return $this->description; }
    public function getCaracteristiques() { return $this->caracteristiques; }
    public function getPrix()             { return $this->prix; }
    public function getIdMarque()         { return $this->id_marque; }
    public function getImage()            { return $this->image; }
    public function getCategorie()        { return $this->categorie; }
    public function getEstArchive()       { return $this->estArchive; }
    public function getEstEpingle()       { return $this->estEpingle; }
    public function getSortOrder()        { return $this->sortOrder; }
    public function getDateDisponibilite(){ return $this->dateDisponibilite; }
    public function getNoteInterne()      { return $this->noteInterne; }

    public function setNom($nom)                        { $this->nom = $nom; }
    public function setDescription($description)        { $this->description = $description; }
    public function setCaracteristiques($c)             { $this->caracteristiques = $c; }
    public function setPrix($prix)                      { $this->prix = $prix; }
    public function setImage($image)                    { $this->image = $image; }
    public function setCategorie($categorie)            { $this->categorie = $categorie; }
    public function setEstArchive($estArchive)          { $this->estArchive = $estArchive; }
    public function setEstEpingle($estEpingle)          { $this->estEpingle = $estEpingle; }
    public function setSortOrder($sortOrder)            { $this->sortOrder = $sortOrder; }
    public function setDateDisponibilite($date)         { $this->dateDisponibilite = $date; }
    public function setNoteInterne($noteInterne)        { $this->noteInterne = $noteInterne; }
}
?>