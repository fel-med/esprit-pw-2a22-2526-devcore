<?php

class Profile
{
    private $idProfile;
    private $idUtilisateur;
    private $imageName;
    private $createdAt;
    private $updatedAt;

    public function __construct(
        $idProfile = null,
        $idUtilisateur = null,
        $imageName = null,
        $createdAt = null,
        $updatedAt = null
    ) {
        $this->idProfile = $idProfile;
        $this->idUtilisateur = $idUtilisateur;
        $this->imageName = $imageName;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public function getIdProfile() { return $this->idProfile; }
    public function setIdProfile($idProfile) { $this->idProfile = $idProfile; }

    public function getIdUtilisateur() { return $this->idUtilisateur; }
    public function setIdUtilisateur($idUtilisateur) { $this->idUtilisateur = $idUtilisateur; }

    public function getImageName() { return $this->imageName; }
    public function setImageName($imageName) { $this->imageName = $imageName; }

    public function getCreatedAt() { return $this->createdAt; }
    public function setCreatedAt($createdAt) { $this->createdAt = $createdAt; }

    public function getUpdatedAt() { return $this->updatedAt; }
    public function setUpdatedAt($updatedAt) { $this->updatedAt = $updatedAt; }
}

?>
