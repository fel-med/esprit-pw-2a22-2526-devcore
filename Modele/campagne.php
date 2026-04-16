<?php
class Campagne {
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

    public function setTitre($titre)            { $this->titre = $titre; }
    public function setDescription($description){ $this->description = $description; }
    public function setDateDebut($dateDebut)    { $this->dateDebut = $dateDebut; }
    public function setDateFin($dateFin)        { $this->dateFin = $dateFin; }
    public function setBudget($budget)          { $this->budget = $budget; }
    public function setStatut($statut)          { $this->statut = $statut; }
    public function setIdMarque($idMarque)      { $this->idMarque = $idMarque; }
    public function setObjectif($objectif)      { $this->objectif = $objectif; }
    public function setEstArchive($estArchive)  { $this->estArchive = $estArchive; }
}
?>