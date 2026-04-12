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
    private string $type;          // formation | webinaire | meetup | atelier | evenement
    private string $statut;        // brouillon | en_attente | actif | cloture | annule
    private string $lieu;          // City name or "En ligne"
    private string $date_evenement;
    private int    $capacite;
    private int    $nb_inscrits;
    private int    $id_organisateur; // FK → utilisateur.id
    private string $created_at;

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
        int    $id_organisateur = 0,
        string $created_at    = ''
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
        $this->id_organisateur  = $id_organisateur;
        $this->created_at       = $created_at;
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
    public function getIdOrganisateur(): int   { return $this->id_organisateur; }
    public function getCreatedAt(): string     { return $this->created_at; }

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
    public function setIdOrganisateur(int $id): void            { $this->id_organisateur = $id; }

    // ── Helper ───────────────────────────────────────────────
    public function getPlacesRestantes(): int {
        return max(0, $this->capacite - $this->nb_inscrits);
    }

    public function isComplet(): bool {
        return $this->nb_inscrits >= $this->capacite;
    }
}