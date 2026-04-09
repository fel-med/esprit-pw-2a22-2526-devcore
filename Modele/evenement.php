<?php
// Model class for Evenement entity
// Represents an event/formation in the system

class Evenement {
    private $idFormation;
    private $TitreFormation;
    private $description;
    private $Duree;
    private $DateFormation;

    public function __construct($idFormation = null, $TitreFormation = null, $description = null, $Duree = null, $DateFormation = null) {
        $this->idFormation = $idFormation;
        $this->TitreFormation = $TitreFormation;
        $this->description = $description;
        $this->Duree = $Duree;
        $this->DateFormation = $DateFormation;
    }

    public function getIdFormation() { return $this->idFormation; }
    public function setIdFormation($idFormation) { $this->idFormation = $idFormation; }

    public function getTitreFormation() { return $this->TitreFormation; }
    public function setTitreFormation($TitreFormation) { $this->TitreFormation = $TitreFormation; }

    public function getDescription() { return $this->description; }
    public function setDescription($description) { $this->description = $description; }

    public function getDuree() { return $this->Duree; }
    public function setDuree($Duree) { $this->Duree = $Duree; }

    public function getDateFormation() { return $this->DateFormation; }
    public function setDateFormation($DateFormation) { $this->DateFormation = $DateFormation; }
}
?>