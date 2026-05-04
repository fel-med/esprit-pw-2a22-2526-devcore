<?php

class Contrat
{
    private ?int    $id;
    private int     $idCampagne;
    private int     $idMarque;
    private int     $idCreateur;
    private string  $titre;
    private string  $description;
    private float   $montant;
    private string  $dateDebut;
    private string  $dateFin;
    private string  $statut;        // en_attente | signe | resilie | expire
    private string  $dateCreation;
    private ?string $fichierPdf;

    public function __construct(
        ?int $id, int $idCampagne, int $idMarque, int $idCreateur,
        string $titre, string $description, float $montant,
        string $dateDebut, string $dateFin,
        string $statut = 'en_attente', string $dateCreation = '',
        ?string $fichierPdf = null
    ) {
        $this->id           = $id;
        $this->idCampagne   = $idCampagne;
        $this->idMarque     = $idMarque;
        $this->idCreateur   = $idCreateur;
        $this->titre        = $titre;
        $this->description  = $description;
        $this->montant      = $montant;
        $this->dateDebut    = $dateDebut;
        $this->dateFin      = $dateFin;
        $this->statut       = $statut;
        $this->dateCreation = $dateCreation ?: date('Y-m-d H:i:s');
        $this->fichierPdf   = $fichierPdf;
    }

    public function getId(): ?int             { return $this->id; }
    public function getIdCampagne(): int      { return $this->idCampagne; }
    public function getIdMarque(): int        { return $this->idMarque; }
    public function getIdCreateur(): int      { return $this->idCreateur; }
    public function getTitre(): string        { return $this->titre; }
    public function getDescription(): string  { return $this->description; }
    public function getMontant(): float       { return $this->montant; }
    public function getDateDebut(): string    { return $this->dateDebut; }
    public function getDateFin(): string      { return $this->dateFin; }
    public function getStatut(): string       { return $this->statut; }
    public function getDateCreation(): string { return $this->dateCreation; }
    public function getFichierPdf(): ?string  { return $this->fichierPdf; }

    public function setId(?int $v): void           { $this->id = $v; }
    public function setIdCampagne(int $v): void    { $this->idCampagne = $v; }
    public function setIdMarque(int $v): void      { $this->idMarque = $v; }
    public function setIdCreateur(int $v): void    { $this->idCreateur = $v; }
    public function setTitre(string $v): void      { $this->titre = $v; }
    public function setDescription(string $v): void{ $this->description = $v; }
    public function setMontant(float $v): void     { $this->montant = $v; }
    public function setDateDebut(string $v): void  { $this->dateDebut = $v; }
    public function setDateFin(string $v): void    { $this->dateFin = $v; }
    public function setStatut(string $v): void     { $this->statut = $v; }
    public function setDateCreation(string $v): void { $this->dateCreation = $v; }
    public function setFichierPdf(?string $v): void  { $this->fichierPdf = $v; }
}