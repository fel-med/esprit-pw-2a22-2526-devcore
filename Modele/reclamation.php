<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Modele/reclamation.php';
class Reclamation {
    private $id;
    private $idUtilisateur;
    private $description;
    private $date_creation;
    private $statut;
    private $priorite;

    // ✔️ Constructeur
    public function __construct($id = null, $idUtilisateur, $description, $date_creation = null, $statut = 'en_attente', $priorite = 'normale') {
        $this->id = $id;
        $this->idUtilisateur = $idUtilisateur;
        $this->description = $description;
        $this->date_creation = $date_creation;
        $this->statut = $statut;
        $this->priorite = $priorite;
    }

    // ✔️ Getters
    public function getId() {
        return $this->id;
    }

    public function getIdUtilisateur() {
        return $this->idUtilisateur;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getDateCreation() {
        return $this->date_creation;
    }

    public function getStatut() {
        return $this->statut;
    }

    public function getPriorite() {
        return $this->priorite;
    }

    // ✔️ Setters
    public function setId($id) {
        $this->id = $id;
    }

    public function setIdUtilisateur($idUtilisateur) {
        $this->idUtilisateur = $idUtilisateur;
    }

    public function setDescription($description) {
        $this->description = $description;
    }

    public function setDateCreation($date_creation) {
        $this->date_creation = $date_creation;
    }

    public function setStatut($statut) {
        $this->statut = $statut;
    }

    public function setPriorite($priorite) {
        $this->priorite = $priorite;
    }
}