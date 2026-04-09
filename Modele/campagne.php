<?php
// Model class for Campagne entity
// Represents a campaign in the system

class Campagne {
    private $idCampagne;
    private $idMarque;
    private $idCandidature;
    private $titre;
    private $description;
    private $dateDebut;
    private $dateFin;
    private $statut;

    public function __construct($idCampagne = null, $idMarque = null, $idCandidature = null, $titre = null, $description = null, $dateDebut = null, $dateFin = null, $statut = null) {
        $this->idCampagne = $idCampagne;
        $this->idMarque = $idMarque;
        $this->idCandidature = $idCandidature;
        $this->titre = $titre;
        $this->description = $description;
        $this->dateDebut = $dateDebut;
        $this->dateFin = $dateFin;
        $this->statut = $statut;
    }

    public function getIdCampagne() { return $this->idCampagne; }
    public function setIdCampagne($idCampagne) { $this->idCampagne = $idCampagne; }

    public function getIdMarque() { return $this->idMarque; }
    public function setIdMarque($idMarque) { $this->idMarque = $idMarque; }

    public function getIdCandidature() { return $this->idCandidature; }
    public function setIdCandidature($idCandidature) { $this->idCandidature = $idCandidature; }

    public function getTitre() { return $this->titre; }
    public function setTitre($titre) { $this->titre = $titre; }

    public function getDescription() { return $this->description; }
    public function setDescription($description) { $this->description = $description; }

    public function getDateDebut() { return $this->dateDebut; }
    public function setDateDebut($dateDebut) { $this->dateDebut = $dateDebut; }

    public function getDateFin() { return $this->dateFin; }
    public function setDateFin($dateFin) { $this->dateFin = $dateFin; }

    public function getStatut() { return $this->statut; }
    public function setStatut($statut) { $this->statut = $statut; }
}
?>