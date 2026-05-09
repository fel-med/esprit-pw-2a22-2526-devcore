<?php

class Reponse {
    private $id;
    private $idReclamation;
    private $idAdmin;
    private $contenu;
    private $date_reponse;

    // ✔️ Constructeur
    public function __construct($id = null, $idReclamation, $idAdmin, $contenu, $date_reponse = null) {
        $this->id = $id;
        $this->idReclamation = $idReclamation;
        $this->idAdmin = $idAdmin;
        $this->contenu = $contenu;
        $this->date_reponse = $date_reponse;
    }

    // ✔️ Getters
    public function getId() {
        return $this->id;
    }

    public function getIdReclamation() {
        return $this->idReclamation;
    }

    public function getIdAdmin() {
        return $this->idAdmin;
    }

    public function getContenu() {
        return $this->contenu;
    }

    public function getDateReponse() {
        return $this->date_reponse;
    }

    // ✔️ Setters
    public function setId($id) {
        $this->id = $id;
    }

    public function setIdReclamation($idReclamation) {
        $this->idReclamation = $idReclamation;
    }

    public function setIdAdmin($idAdmin) {
        $this->idAdmin = $idAdmin;
    }

    public function setContenu($contenu) {
        $this->contenu = $contenu;
    }

    public function setDateReponse($date_reponse) {
        $this->date_reponse = $date_reponse;
    }
}