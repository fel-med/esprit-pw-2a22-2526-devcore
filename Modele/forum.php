<?php
class Forum {
    private int $idForum;
    private int $idFormation;
    private int $idUtilisateur;
    private string $titreForum;
    private string $dateCreation;
    private string $sujet;
    private string $message;

    public function __construct(
        int $idForum = 0,
        int $idFormation = 0,
        int $idUtilisateur = 0,
        string $titreForum = '',
        string $dateCreation = '',
        string $sujet = '',
        string $message = ''
    ) {
        $this->idForum = $idForum;
        $this->idFormation = $idFormation;
        $this->idUtilisateur = $idUtilisateur;
        $this->titreForum = $titreForum;
        $this->dateCreation = $dateCreation;
        $this->sujet = $sujet;
        $this->message = $message;
    }

    // Getters
    public function getIdForum(): int { return $this->idForum; }
    public function getIdFormation(): int { return $this->idFormation; }
    public function getIdUtilisateur(): int { return $this->idUtilisateur; }
    public function getTitreForum(): string { return $this->titreForum; }
    public function getDateCreation(): string { return $this->dateCreation; }
    public function getSujet(): string { return $this->sujet; }
    public function getMessage(): string { return $this->message; }

    // Setters
    public function setTitreForum(string $titre): void { $this->titreForum = $titre; }
    public function setSujet(string $sujet): void { $this->sujet = $sujet; }
    public function setMessage(string $message): void { $this->message = $message; }
}
?>