<?php

class Contrat {
    private ?int $id;
    private int $idCampagne;
    private int $idMarque;
    private int $idCreateur;
    private string $titre;
    private string $description;
    private float $montant;
    private string $dateDebut;
    private string $dateFin;
    private string $statut; // 'en_attente', 'signe', 'resilie', 'expire'
    private string $dateCreation;
    private ?string $fichierPdf;

    public function __construct(
        ?int $id,
        int $idCampagne,
        int $idMarque,
        int $idCreateur,
        string $titre,
        string $description,
        float $montant,
        string $dateDebut,
        string $dateFin,
        string $statut = 'en_attente',
        string $dateCreation = '',
        ?string $fichierPdf = null
    ) {
        $this->id = $id;
        $this->idCampagne = $idCampagne;
        $this->idMarque = $idMarque;
        $this->idCreateur = $idCreateur;
        $this->titre = $titre;
        $this->description = $description;
        $this->montant = $montant;
        $this->dateDebut = $dateDebut;
        $this->dateFin = $dateFin;
        $this->statut = $statut;
        $this->dateCreation = $dateCreation ?: date('Y-m-d H:i:s');
        $this->fichierPdf = $fichierPdf;
    }

    // --- Getters ---
    public function getId(): ?int { return $this->id; }
    public function getIdCampagne(): int { return $this->idCampagne; }
    public function getIdMarque(): int { return $this->idMarque; }
    public function getIdCreateur(): int { return $this->idCreateur; }
    public function getTitre(): string { return $this->titre; }
    public function getDescription(): string { return $this->description; }
    public function getMontant(): float { return $this->montant; }
    public function getDateDebut(): string { return $this->dateDebut; }
    public function getDateFin(): string { return $this->dateFin; }
    public function getStatut(): string { return $this->statut; }
    public function getDateCreation(): string { return $this->dateCreation; }
    public function getFichierPdf(): ?string { return $this->fichierPdf; }

    // --- Setters ---
    public function setId(?int $id): void { $this->id = $id; }
    public function setIdCampagne(int $idCampagne): void { $this->idCampagne = $idCampagne; }
    public function setIdMarque(int $idMarque): void { $this->idMarque = $idMarque; }
    public function setIdCreateur(int $idCreateur): void { $this->idCreateur = $idCreateur; }
    public function setTitre(string $titre): void { $this->titre = $titre; }
    public function setDescription(string $description): void { $this->description = $description; }
    public function setMontant(float $montant): void { $this->montant = $montant; }
    public function setDateDebut(string $dateDebut): void { $this->dateDebut = $dateDebut; }
    public function setDateFin(string $dateFin): void { $this->dateFin = $dateFin; }
    public function setStatut(string $statut): void { $this->statut = $statut; }
    public function setDateCreation(string $dateCreation): void { $this->dateCreation = $dateCreation; }
    public function setFichierPdf(?string $fichierPdf): void { $this->fichierPdf = $fichierPdf; }
}