<?php

class Campagne
{
    private $id;
    private $titre;
    private $description;
    private $dateDebut;
    private $dateFin;
    private $budget;
    private $statut;
    private $idMarque;
    private $objectif;
    private $estArchive;

    public function __construct(
        $id, $titre, $description, $dateDebut, $dateFin,
        $budget, $statut = 'brouillon', $idMarque = null,
        $objectif = null, $estArchive = 0
    ) {
        $this->id          = $id;
        $this->titre       = $titre;
        $this->description = $description;
        $this->dateDebut   = $dateDebut;
        $this->dateFin     = $dateFin;
        $this->budget      = $budget;
        $this->statut      = $statut;
        $this->idMarque    = $idMarque;
        $this->objectif    = $objectif;
        $this->estArchive  = $estArchive;
    }

    public function getId()          { return $this->id; }
    public function getTitre()       { return $this->titre; }
    public function getDescription() { return $this->description; }
    public function getDateDebut()   { return $this->dateDebut; }
    public function getDateFin()     { return $this->dateFin; }
    public function getBudget()      { return $this->budget; }
    public function getStatut()      { return $this->statut; }
    public function getIdMarque()    { return $this->idMarque; }
    public function getObjectif()    { return $this->objectif; }
    public function getEstArchive()  { return $this->estArchive; }

    public function setTitre($v)       { $this->titre = $v; }
    public function setDescription($v) { $this->description = $v; }
    public function setDateDebut($v)   { $this->dateDebut = $v; }
    public function setDateFin($v)     { $this->dateFin = $v; }
    public function setBudget($v)      { $this->budget = $v; }
    public function setStatut($v)      { $this->statut = $v; }
    public function setIdMarque($v)    { $this->idMarque = $v; }
    public function setObjectif($v)    { $this->objectif = $v; }
    public function setEstArchive($v)  { $this->estArchive = $v; }
}