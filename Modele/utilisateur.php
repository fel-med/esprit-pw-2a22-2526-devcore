<?php
// Model class for Utilisateur entity
// Represents a user in the system with basic attributes

class Utilisateur {
    private $id;
    private $nom;
    private $email;
    private $mot_de_passe;
    private $role;
    private $statut;
    private $tentatives_login;
    private $date_creation;

    public function __construct($id = null, $nom = null, $email = null, $mot_de_passe = null, $role = null, $statut = null, $tentatives_login = null, $date_creation = null) {
        $this->id = $id;
        $this->nom = $nom;
        $this->email = $email;
        $this->mot_de_passe = $mot_de_passe;
        $this->role = $role;
        $this->statut = $statut;
        $this->tentatives_login = $tentatives_login;
        $this->date_creation = $date_creation;
    }

    public function getId() { return $this->id; }
    public function setId($id) { $this->id = $id; }

    public function getNom() { return $this->nom; }
    public function setNom($nom) { $this->nom = $nom; }

    public function getEmail() { return $this->email; }
    public function setEmail($email) { $this->email = $email; }

    public function getMotDePasse() { return $this->mot_de_passe; }
    public function setMotDePasse($mot_de_passe) { $this->mot_de_passe = $mot_de_passe; }

    public function getRole() { return $this->role; }
    public function setRole($role) { $this->role = $role; }

    public function getStatut() { return $this->statut; }
    public function setStatut($statut) { $this->statut = $statut; }

    public function getTentativesLogin() { return $this->tentatives_login; }
    public function setTentativesLogin($tentatives_login) { $this->tentatives_login = $tentatives_login; }

    public function getDateCreation() { return $this->date_creation; }
    public function setDateCreation($date_creation) { $this->date_creation = $date_creation; }
}
?>