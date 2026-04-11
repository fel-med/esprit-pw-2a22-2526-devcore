<?php
// Model class for Offre entity
// Represents an offer in the system

class Offre {
    private $idOffre;
    private $idMarque;
    private $idCreateurCible;
    private $titre;
    private $description;
    private $objectif;
    private $budgetMin;
    private $budgetMax;
    private $datePublication;
    private $dateLimite;
    private $statutOffre;

    public function __construct($idOffre = null, $idMarque = null, $idCreateurCible = null, $titre = null, $description = null, $objectif = null, $budgetMin = null, $budgetMax = null, $datePublication = null, $dateLimite = null, $statutOffre = null) {
        $this->idOffre = $idOffre;
        $this->idMarque = $idMarque;
        $this->idCreateurCible = $idCreateurCible;
        $this->titre = $titre;
        $this->description = $description;
        $this->objectif = $objectif;
        $this->budgetMin = $budgetMin;
        $this->budgetMax = $budgetMax;
        $this->datePublication = $datePublication;
        $this->dateLimite = $dateLimite;
        $this->statutOffre = $statutOffre;
    }

    public function getIdOffre() { return $this->idOffre; }
    public function setIdOffre($idOffre) { $this->idOffre = $idOffre; }

    public function getIdMarque() { return $this->idMarque; }
    public function setIdMarque($idMarque) { $this->idMarque = $idMarque; }

    public function getIdCreateurCible() { return $this->idCreateurCible; }
    public function setIdCreateurCible($idCreateurCible) { $this->idCreateurCible = $idCreateurCible; }

    public function getTitre() { return $this->titre; }
    public function setTitre($titre) { $this->titre = $titre; }

    public function getDescription() { return $this->description; }
    public function setDescription($description) { $this->description = $description; }

    public function getObjectif() { return $this->objectif; }
    public function setObjectif($objectif) { $this->objectif = $objectif; }

    public function getBudgetMin() { return $this->budgetMin; }
    public function setBudgetMin($budgetMin) { $this->budgetMin = $budgetMin; }

    public function getBudgetMax() { return $this->budgetMax; }
    public function setBudgetMax($budgetMax) { $this->budgetMax = $budgetMax; }

    public function getDatePublication() { return $this->datePublication; }
    public function setDatePublication($datePublication) { $this->datePublication = $datePublication; }

    public function getDateLimite() { return $this->dateLimite; }
    public function setDateLimite($dateLimite) { $this->dateLimite = $dateLimite; }

    public function getStatutOffre() { return $this->statutOffre; }
    public function setStatutOffre($statutOffre) { $this->statutOffre = $statutOffre; }

    public static function fromArray(array $data) {
        return new self(
            $data['idOffre'] ?? null,
            $data['idMarque'] ?? null,
            $data['idCreateurCible'] ?? null,
            $data['titre'] ?? null,
            $data['description'] ?? null,
            $data['objectif'] ?? null,
            $data['budgetMin'] ?? null,
            $data['budgetMax'] ?? null,
            $data['datePublication'] ?? null,
            $data['dateLimite'] ?? null,
            $data['statutOffre'] ?? null
        );
    }
}
?>