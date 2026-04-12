<?php
/**
 * Controleur/evenementC.php
 * Cre8Connect – Module Événement / Forum
 * Controller: coordinates model, DB connection, and views
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Modele/evenement.php';

class EvenementControleur {

    private PDO $pdo;

    public function __construct() {
        $this->pdo = getConnexion(); // from config.php
    }

    // ────────────────────────────────────────────────────────
    // FRONT OFFICE
    // ────────────────────────────────────────────────────────

    /**
     * List all active events for the public page.
     * Route: evenements.php  (FrontOffice)
     */
    public function listerEvenementsPublics(): void {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM evenement
             WHERE statut = 'actif'
             ORDER BY date_evenement ASC"
        );
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $evenements = array_map([$this, 'hydrate'], $rows);

        require_once __DIR__ . '/../Vue/FrontOffice/evenements.php';
    }

    /**
     * Show detail of a single event (public view).
     */
    public function afficherEvenement(int $id): void {
        $stmt = $this->pdo->prepare("SELECT * FROM evenement WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            header('Location: evenements.php');
            exit;
        }

        $evenement = $this->hydrate($row);
        require_once __DIR__ . '/../Vue/FrontOffice/evenement_detail.php';
    }

    /**
     * Handle inscription (registration) for an event.
     * Only accessible to logged-in createur users.
     */
    public function inscrire(int $idEvenement, int $idUtilisateur): void {
        // Check capacity
        $stmt = $this->pdo->prepare(
            "SELECT capacite, nb_inscrits FROM evenement WHERE id = :id AND statut = 'actif'"
        );
        $stmt->execute([':id' => $idEvenement]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || $row['nb_inscrits'] >= $row['capacite']) {
            $_SESSION['error'] = "Cet événement est complet ou indisponible.";
            header("Location: evenement_detail.php?id={$idEvenement}");
            exit;
        }

        // Insert inscription
        $ins = $this->pdo->prepare(
            "INSERT IGNORE INTO inscription_evenement (id_evenement, id_utilisateur, inscrit_le)
             VALUES (:id_event, :id_user, NOW())"
        );
        $ins->execute([':id_event' => $idEvenement, ':id_user' => $idUtilisateur]);

        // Update counter
        $upd = $this->pdo->prepare(
            "UPDATE evenement SET nb_inscrits = nb_inscrits + 1 WHERE id = :id"
        );
        $upd->execute([':id' => $idEvenement]);

        $_SESSION['success'] = "Inscription confirmée !";
        header("Location: evenement_detail.php?id={$idEvenement}");
        exit;
    }

    // ────────────────────────────────────────────────────────
    // BACK OFFICE (Admin)
    // ────────────────────────────────────────────────────────

    /**
     * List all events for admin management table.
     * Route: admin_evenements.php  (BackOffice)
     */
    public function listerAdmin(): void {
        $stmt = $this->pdo->prepare(
            "SELECT e.*, u.nom AS organisateur_nom
             FROM evenement e
             LEFT JOIN utilisateur u ON e.id_organisateur = u.id
             ORDER BY e.created_at DESC"
        );
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $evenements = array_map([$this, 'hydrate'], $rows);

        // KPI counts
        $kpi = $this->getKpi();

        require_once __DIR__ . '/../Vue/BackOffice/admin_evenements.php';
    }

    /**
     * Create a new event (admin form POST).
     */
    public function creer(array $data): void {
        $stmt = $this->pdo->prepare(
            "INSERT INTO evenement
             (titre, description, type, statut, lieu, date_evenement, capacite, nb_inscrits, id_organisateur, created_at)
             VALUES
             (:titre, :description, :type, :statut, :lieu, :date_evenement, :capacite, 0, :id_organisateur, NOW())"
        );
        $stmt->execute([
            ':titre'           => htmlspecialchars(trim($data['titre'])),
            ':description'     => htmlspecialchars(trim($data['description'])),
            ':type'            => $data['type'],
            ':statut'          => $data['statut'] ?? 'brouillon',
            ':lieu'            => htmlspecialchars(trim($data['lieu'])),
            ':date_evenement'  => $data['date_evenement'],
            ':capacite'        => (int) $data['capacite'],
            ':id_organisateur' => (int) $data['id_organisateur'],
        ]);

        header('Location: admin_evenements.php?success=created');
        exit;
    }

    /**
     * Update an existing event (admin edit form POST).
     */
    public function modifier(int $id, array $data): void {
        $stmt = $this->pdo->prepare(
            "UPDATE evenement SET
             titre           = :titre,
             description     = :description,
             type            = :type,
             statut          = :statut,
             lieu            = :lieu,
             date_evenement  = :date_evenement,
             capacite        = :capacite
             WHERE id        = :id"
        );
        $stmt->execute([
            ':titre'          => htmlspecialchars(trim($data['titre'])),
            ':description'    => htmlspecialchars(trim($data['description'])),
            ':type'           => $data['type'],
            ':statut'         => $data['statut'],
            ':lieu'           => htmlspecialchars(trim($data['lieu'])),
            ':date_evenement' => $data['date_evenement'],
            ':capacite'       => (int) $data['capacite'],
            ':id'             => $id,
        ]);

        header('Location: admin_evenements.php?success=updated');
        exit;
    }

    /**
     * Change event status (quick action: validate / cancel).
     */
    public function changerStatut(int $id, string $statut): void {
        $allowed = ['brouillon', 'en_attente', 'actif', 'cloture', 'annule'];
        if (!in_array($statut, $allowed)) {
            header('Location: admin_evenements.php');
            exit;
        }

        $stmt = $this->pdo->prepare("UPDATE evenement SET statut = :statut WHERE id = :id");
        $stmt->execute([':statut' => $statut, ':id' => $id]);

        header('Location: admin_evenements.php?success=status_updated');
        exit;
    }

    /**
     * Delete an event (admin only).
     */
    public function supprimer(int $id): void {
        $stmt = $this->pdo->prepare("DELETE FROM evenement WHERE id = :id");
        $stmt->execute([':id' => $id]);

        header('Location: admin_evenements.php?success=deleted');
        exit;
    }

    // ────────────────────────────────────────────────────────
    // Private helpers
    // ────────────────────────────────────────────────────────

    private function hydrate(array $row): Evenement {
        return new Evenement(
            (int)   $row['id'],
                    $row['titre'],
                    $row['description'],
                    $row['type'],
                    $row['statut'],
                    $row['lieu'],
                    $row['date_evenement'],
            (int)   $row['capacite'],
            (int)   $row['nb_inscrits'],
            (int)   $row['id_organisateur'],
                    $row['created_at'] ?? ''
        );
    }

    private function getKpi(): array {
        $kpi = [];

        $kpi['total'] = $this->pdo
            ->query("SELECT COUNT(*) FROM evenement")
            ->fetchColumn();

        $kpi['total_inscrits'] = $this->pdo
            ->query("SELECT SUM(nb_inscrits) FROM evenement")
            ->fetchColumn();

        $kpi['en_attente'] = $this->pdo
            ->query("SELECT COUNT(*) FROM evenement WHERE statut = 'en_attente'")
            ->fetchColumn();

        $kpi['cette_semaine'] = $this->pdo
            ->query("SELECT COUNT(*) FROM evenement
                     WHERE statut = 'actif'
                     AND date_evenement BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)")
            ->fetchColumn();

        return $kpi;
    }
}

// ── Entry point ──────────────────────────────────────────────
// Dispatch based on action param (simple front-controller pattern)
$ctrl   = new EvenementControleur();
$action = $_GET['action'] ?? 'list';
$id     = isset($_GET['id']) ? (int) $_GET['id'] : 0;

switch ($action) {
    case 'admin':
        $ctrl->listerAdmin();
        break;

    case 'creer':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ctrl->creer($_POST);
        }
        break;

    case 'modifier':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id > 0) {
            $ctrl->modifier($id, $_POST);
        }
        break;

    case 'statut':
        if ($id > 0 && isset($_GET['statut'])) {
            $ctrl->changerStatut($id, $_GET['statut']);
        }
        break;

    case 'supprimer':
        if ($id > 0) {
            $ctrl->supprimer($id);
        }
        break;

    case 'detail':
        $ctrl->afficherEvenement($id);
        break;

    case 'inscrire':
        $idUser = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
        if ($id > 0 && $idUser > 0) {
            $ctrl->inscrire($id, $idUser);
        }
        break;

    default: // 'list'
        $ctrl->listerEvenementsPublics();
        break;
}