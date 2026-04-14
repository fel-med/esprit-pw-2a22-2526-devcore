<?php
/**
 * Modele/evenement.php
 * Cre8Connect – Module Événement / Forum
 * Entity class for Evenement
 */

class Evenement {

    private int    $id;
    private string $titre;
    private string $description;
    private string $type;
    private string $statut;
    private string $lieu;
    private string $date_evenement;
    private int    $capacite;
    private int    $nb_inscrits;
    private int    $duree;
    private int    $id_organisateur;
    private string $created_at;
    private ?string $image;

    public function __construct(
        int    $id            = 0,
        string $titre         = '',
        string $description   = '',
        string $type          = '',
        string $statut        = 'brouillon',
        string $lieu          = '',
        string $date_evenement = '',
        int    $capacite      = 0,
        int    $nb_inscrits   = 0,
        int    $duree         = 0,
        string $created_at    = '',
        ?string $image        = null
    ) {
        $this->id               = $id;
        $this->titre            = $titre;
        $this->description      = $description;
        $this->type             = $type;
        $this->statut           = $statut;
        $this->lieu             = $lieu;
        $this->date_evenement   = $date_evenement;
        $this->capacite         = $capacite;
        $this->nb_inscrits      = $nb_inscrits;
        $this->duree            = $duree;
        $this->id_organisateur  = 0;
        $this->created_at       = $created_at;
        $this->image            = $image;
    }

    // ── Getters ──────────────────────────────────────────────
    public function getId(): int               { return $this->id; }
    public function getTitre(): string         { return $this->titre; }
    public function getDescription(): string   { return $this->description; }
    public function getType(): string          { return $this->type; }
    public function getStatut(): string        { return $this->statut; }
    public function getLieu(): string          { return $this->lieu; }
    public function getDateEvenement(): string { return $this->date_evenement; }
    public function getCapacite(): int         { return $this->capacite; }
    public function getNbInscrits(): int       { return $this->nb_inscrits; }
    public function getDuree(): int            { return $this->duree; }  // ← AJOUTE CETTE LIGNE
    public function getIdOrganisateur(): int   { return $this->id_organisateur; }
    public function getCreatedAt(): string     { return $this->created_at; }
    public function getImage(): ?string        { return $this->image; }

    // ── Setters ──────────────────────────────────────────────
    public function setId(int $id): void                        { $this->id = $id; }
    public function setTitre(string $titre): void               { $this->titre = $titre; }
    public function setDescription(string $desc): void         { $this->description = $desc; }
    public function setType(string $type): void                 { $this->type = $type; }
    public function setStatut(string $statut): void             { $this->statut = $statut; }
    public function setLieu(string $lieu): void                 { $this->lieu = $lieu; }
    public function setDateEvenement(string $date): void        { $this->date_evenement = $date; }
    public function setCapacite(int $capacite): void            { $this->capacite = $capacite; }
    public function setNbInscrits(int $nb): void                { $this->nb_inscrits = $nb; }
    public function setDuree(int $duree): void                  { $this->duree = $duree; }
    public function setIdOrganisateur(int $id): void            { $this->id_organisateur = $id; }
    public function setImage(?string $image): void              { $this->image = $image; }

    // ── Helper ───────────────────────────────────────────────
    public function getPlacesRestantes(): int {
        return max(0, $this->capacite - $this->nb_inscrits);
    }

    public function isComplet(): bool {
        return $this->nb_inscrits >= $this->capacite;
    }
}
?>