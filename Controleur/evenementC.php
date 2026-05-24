<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Modele/evenement.php';
require_once __DIR__ . '/notificationC.php';
require_once __DIR__ . '/session_helper.php';

// ── Base URL helper ──────────────────────────────────────────────────────────
// Detects the project sub-path dynamically so no URL is ever hardcoded.
function evenementBaseUrl(): string {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    if (($pos = strpos($script, '/Controleur/')) !== false) {
        return rtrim(substr($script, 0, $pos), '/');
    }
    if (($pos = strpos($script, '/Vue/')) !== false) {
        return rtrim(substr($script, 0, $pos), '/');
    }
    return rtrim(dirname(dirname($script)), '/');
}

class EvenementControleur {

    private PDO $pdo;

    public function __construct() {
        $this->pdo = config::getConnexion();
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    private function currentUserId(): int {
        if (isset($_SESSION['user']['id'])) return (int)$_SESSION['user']['id'];
        if (isset($_SESSION['id'])) return (int)$_SESSION['id'];
        if (isset($_SESSION['user_id'])) return (int)$_SESSION['user_id'];
        if (isset($_SESSION['utilisateur']['id'])) return (int)$_SESSION['utilisateur']['id'];
        return 0;
    }

    private function currentUserRole(): string {
        return cc_current_user_role();
    }

    private function generateTodayEventNotificationsForUser(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        $stmt = $this->pdo->prepare("
            SELECT e.idFormation, e.TitreFormation
            FROM inscription_evenement i
            INNER JOIN evenement e ON e.idFormation = i.id_evenement
            WHERE i.id_utilisateur = :userId
              AND DATE(e.DateFormation) = CURDATE()
              AND e.statut = 'actif'
        ");
        $stmt->execute(['userId' => $userId]);

        $today = date('Y-m-d');
        $notificationC = new NotificationC($this->pdo);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $event) {
            $eventId = (int) ($event['idFormation'] ?? 0);
            if ($eventId <= 0) {
                continue;
            }

            $title = trim((string) ($event['TitreFormation'] ?? '')) ?: 'your event';
            $notificationC->createNotification(
                $userId,
                'event_today',
                'Event starts today',
                'The event ' . $title . ' starts today.',
                evenementBaseUrl() . '/Vue/FrontOffice/evenement/index.php',
                'evenement',
                $eventId,
                null,
                null,
                'event_today_' . $eventId . '_user_' . $userId . '_' . $today,
                [
                    'event_id' => $eventId,
                    'event_title' => $title,
                    'date' => $today,
                ]
            );
        }
    }

    private function normalizeDateForInput(string $date): string {
        $date = trim($date);
        if ($date === '') return '';
        if (strpos($date, 'T') !== false) $date = explode('T', $date)[0];
        if (strpos($date, ' ') !== false) $date = explode(' ', $date)[0];
        return substr($date, 0, 10);
    }

    private function redirect(string $path): void {
        header('Location: ' . evenementBaseUrl() . $path);
        exit;
    }

    private function uploadImage(array $file, int $eventId): ?string {
        if ($file['error'] !== UPLOAD_ERR_OK) return null;

        $ext    = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'jfif'];
        if (!in_array($ext, $allowed)) return null;

        $nom    = 'event_' . $eventId . '_' . time() . '.' . $ext;
        $dossier = __DIR__ . '/../Vue/public/uploads/evenements/';

        if (!is_dir($dossier)) mkdir($dossier, 0777, true);

        if (move_uploaded_file($file['tmp_name'], $dossier . $nom)) {
            return 'Vue/public/uploads/evenements/' . $nom;
        }
        return null;
    }

    // ==================== BACK OFFICE ====================

    public function listerAdmin(): void {
        $stmt = $this->pdo->query("SELECT * FROM evenement ORDER BY idFormation DESC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $evenements = array_map([$this, 'hydrate'], $rows);
        require __DIR__ . '/../Vue/BackOffice/evenement/index.php';
    }

    public function creer(array $data, array $files = []): void {
        $date  = $data['date_evenement'] ?? '';
        $today = date('Y-m-d');

        if ($date < $today) {
            $this->redirect('/Controleur/evenementC.php?action=admin&error=date');
        }

        if ((int)($data['capacite'] ?? 0) <= 0) {
            $this->redirect('/Controleur/evenementC.php?action=admin&error=capacite');
        }

        if (strpos($date, 'T') !== false) {
            $date = explode('T', $date)[0];
        }

        $stmt = $this->pdo->prepare(
            "INSERT INTO evenement
            (TitreFormation, description, Duree, DateFormation, type, statut, lieu, capacite, nb_inscrits, image, adresse_complete)
            VALUES
            (:titre, :description, :duree, :date, :type, :statut, :lieu, :capacite, 0, :image, :adresse)"
        );

        $stmt->execute([
            ':titre'       => $data['titre'],
            ':description' => $data['description'] ?? '',
            ':duree'       => (int)($data['duree'] ?? 0),
            ':date'        => $date,
            ':type'        => $data['type'] ?? 'formation',
            ':statut'      => $data['statut'] ?? 'brouillon',
            ':lieu'        => $data['lieu'] ?? '',
            ':capacite'    => (int)($data['capacite'] ?? 0),
            ':image'       => null,
            ':adresse'     => $data['adresse_complete'] ?? null,
        ]);

        $eventId = (int)$this->pdo->lastInsertId();

        if (isset($files['image']) && $files['image']['error'] === UPLOAD_ERR_OK) {
            $path = $this->uploadImage($files['image'], $eventId);
            if ($path) {
                $this->pdo->prepare("UPDATE evenement SET image = ? WHERE idFormation = ?")->execute([$path, $eventId]);
            }
        }

        $this->redirect('/Controleur/evenementC.php?action=admin&success=created');
    }

    public function modifier(int $id, array $data, array $files = []): void {
        $old = $this->getEventById($id);
        if (!$old) {
            $this->redirect('/Controleur/evenementC.php?action=admin&error=notfound');
        }

        $date = $this->normalizeDateForInput((string)($data['date_evenement'] ?? ''));
        if ($date === '') {
            $date = $this->normalizeDateForInput($old->getDateEvenement());
        }

        $capacite = (int)($data['capacite'] ?? $old->getCapacite());
        if ($capacite <= 0) {
            $this->redirect('/Controleur/evenementC.php?action=admin&error=capacite');
        }

        $imagePath = $old->getImage();

        if (isset($files['image']) && $files['image']['error'] === UPLOAD_ERR_OK) {
            if ($imagePath && file_exists(__DIR__ . '/../' . $imagePath)) {
                unlink(__DIR__ . '/../' . $imagePath);
            }
            $uploadedPath = $this->uploadImage($files['image'], $id);
            if ($uploadedPath) {
                $imagePath = $uploadedPath;
            }
        }

        $stmt = $this->pdo->prepare(
            "UPDATE evenement SET
                TitreFormation    = :titre,
                description       = :description,
                Duree             = :duree,
                DateFormation     = :date,
                type              = :type,
                statut            = :statut,
                lieu              = :lieu,
                capacite          = :capacite,
                image             = :image,
                adresse_complete  = :adresse
            WHERE idFormation = :id"
        );

        $stmt->execute([
            ':id'          => $id,
            ':titre'       => $data['titre'] ?? $old->getTitre(),
            ':description' => $data['description'] ?? $old->getDescription(),
            ':duree'       => (int)($data['duree'] ?? $old->getDuree()),
            ':date'        => $date,
            ':type'        => $data['type'] ?? $old->getType(),
            ':statut'      => $data['statut'] ?? $old->getStatut(),
            ':lieu'        => $data['lieu'] ?? $old->getLieu(),
            ':capacite'    => $capacite,
            ':image'       => $imagePath,
            ':adresse'     => $data['adresse_complete'] ?? $old->getAdresseComplete(),
        ]);

        $this->redirect('/Controleur/evenementC.php?action=admin&success=updated');
    }

    public function supprimer(int $id): void {
        $event = $this->getEventById($id);
        if ($event && $event->getImage()) {
            $path = __DIR__ . '/../' . $event->getImage();
            if (file_exists($path)) unlink($path);
        }
        $this->pdo->prepare("DELETE FROM evenement WHERE idFormation = ?")->execute([$id]);
        $this->redirect('/Controleur/evenementC.php?action=admin&success=deleted');
    }

    // ==================== FRONT OFFICE ====================

    public function listerEvenementsPublics(): void {
        $stmt = $this->pdo->prepare("SELECT * FROM evenement WHERE statut = 'actif' ORDER BY DateFormation ASC");
        $stmt->execute();
        $evenements = array_map([$this, 'hydrate'], $stmt->fetchAll(PDO::FETCH_ASSOC));
        $this->generateTodayEventNotificationsForUser($this->currentUserId());

        $forumsData = [];
        $stmtForum  = $this->pdo->prepare("SELECT idFormation, idForum, est_actif FROM forum WHERE est_actif = 1");
        $stmtForum->execute();
        while ($row = $stmtForum->fetch(PDO::FETCH_ASSOC)) {
            $forumsData[$row['idFormation']] = $row;
        }

        require __DIR__ . '/../Vue/FrontOffice/evenement/index.php';
    }

    public function inscrireEvenement($eventId, $nom, $email) {
        $userId = $this->currentUserId();
        if ($userId <= 0) {
            return ['success' => false, 'message' => 'Please log in to register for this event.'];
        }
        if (cc_is_backoffice_role($this->currentUserRole())) {
            return ['success' => false, 'message' => 'Admins manage events from BackOffice.'];
        }

        try {
            $pdo = config::getConnexion();
            $pdo->beginTransaction();

            $eventStmt = $pdo->prepare("SELECT capacite, nb_inscrits FROM evenement WHERE idFormation = :id FOR UPDATE");
            $eventStmt->execute([':id' => $eventId]);
            $eventRow = $eventStmt->fetch(PDO::FETCH_ASSOC);

            if (!$eventRow) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Event not found.'];
            }

            if ((int)$eventRow['nb_inscrits'] >= (int)$eventRow['capacite']) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'This event is already full.'];
            }

            $stmt = $pdo->prepare("SELECT 1 FROM inscription_evenement WHERE id_evenement = :event_id AND id_utilisateur = :user_id LIMIT 1");
            $stmt->execute([':event_id' => $eventId, ':user_id' => $userId]);

            if ($stmt->fetchColumn()) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'You are already registered for this event.'];
            }

            $stmt = $pdo->prepare("INSERT INTO inscription_evenement
                (id_evenement, id_utilisateur, nom_utilisateur, email_utilisateur, statut, inscrit_le)
                VALUES (:event_id, :user_id, :nom, :email, 'en_attente', NOW())");
            $stmt->execute([
                ':event_id' => $eventId,
                ':user_id'  => $userId,
                ':nom'      => $nom,
                ':email'    => $email
            ]);

            $pdo->prepare("UPDATE evenement SET nb_inscrits = nb_inscrits + 1 WHERE idFormation = :id")
                ->execute([':id' => $eventId]);

            $pdo->commit();
            return ['success' => true, 'message' => 'Registration successful!'];
        } catch (Exception $e) {
            if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public function listerInscriptions(): void {
        $stmt = $this->pdo->query("
            SELECT i.*, e.TitreFormation as titre_evenement
            FROM inscription_evenement i
            LEFT JOIN evenement e ON i.id_evenement = e.idFormation
            ORDER BY i.inscrit_le DESC
        ");
        $inscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        require_once __DIR__ . '/../Vue/BackOffice/inscriptions/index.php';
    }

    public function supprimerInscription(int $id): void {
        $insc = $this->pdo->prepare("SELECT id_evenement FROM inscription_evenement WHERE id = ?");
        $insc->execute([$id]);
        $idEvent = $insc->fetchColumn();

        if ($idEvent) {
            $this->pdo->prepare("DELETE FROM inscription_evenement WHERE id = ?")->execute([$id]);
            $this->pdo->prepare("UPDATE evenement SET nb_inscrits = nb_inscrits - 1 WHERE idFormation = ?")->execute([$idEvent]);
        }

        $this->redirect('/Controleur/evenementC.php?action=inscriptions');
    }

    public function afficherDetailEvenement(int $id): void {
        $stmt = $this->pdo->prepare("SELECT * FROM evenement WHERE idFormation = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $this->redirect('/Controleur/evenementC.php');
        }

        $evenement = $this->hydrate($row);
        require_once __DIR__ . '/../Vue/FrontOffice/evenement/detail.php';
    }

    public function getEventDetails($id) {
    header('Content-Type: application/json');
    
    try {
        $evenement = $this->getEventById($id);
        
        if ($evenement) {
            echo json_encode([
                'success' => true,
                'id' => $evenement->getId(),
                'titre' => $evenement->getTitre(),
                'description' => $evenement->getDescription(),
                'type' => $evenement->getType(),
                'date_evenement' => $this->normalizeDateForInput($evenement->getDateEvenement()),
                'lieu' => $evenement->getLieu(),
                'capacite' => $evenement->getCapacite(),
                'nb_inscrits' => $evenement->getNbInscrits(),
                'image' => $evenement->getImage(),
                'adresse_complete' => $evenement->getAdresseComplete()
            ]);
        } else {
            echo json_encode(['error' => 'Event not found']);
        }
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

public function getEventById($id) {
    try {
        $pdo = config::getConnexion();
        $stmt = $pdo->prepare("SELECT * FROM evenement WHERE idFormation = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            return new Evenement(
                (int)($row['idFormation'] ?? 0),
                $row['TitreFormation'] ?? '',
                $row['description'] ?? '',
                $row['type'] ?? '',
                $row['statut'] ?? '',
                $row['lieu'] ?? '',
                $row['DateFormation'] ?? '',
                (int)($row['capacite'] ?? 0),
                (int)($row['nb_inscrits'] ?? 0),
                (int)($row['Duree'] ?? 0),
                $row['created_at'] ?? '',
                $row['image'] ?? null,
                $row['adresse_complete'] ?? null
            );
        }
        return null;
    } catch (Exception $e) {
        return null;
    }
}

    private function hydrate(array $row): Evenement {
        return new Evenement(
            (int)($row['idFormation']    ?? 0),
            $row['TitreFormation']       ?? '',
            $row['description']          ?? '',
            $row['type']                 ?? '',
            $row['statut']               ?? '',
            $row['lieu']                 ?? '',
            $row['DateFormation']        ?? '',
            (int)($row['capacite']       ?? 0),
            (int)($row['nb_inscrits']    ?? 0),
            (int)($row['Duree']          ?? 0),
            $row['created_at']           ?? '',
            $row['image']                ?? null,
            $row['adresse_complete']     ?? null
        );
    }
}

// ==================== ROUTER ====================
$ctrl   = new EvenementControleur();
$action = $_GET['action'] ?? 'list';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

switch ($action) {
    case 'admin':
        $ctrl->listerAdmin();
        break;
    case 'create':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') $ctrl->creer($_POST, $_FILES);
        break;
    case 'edit':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id > 0) $ctrl->modifier($id, $_POST, $_FILES);
        break; 
    case 'delete':
        if ($id > 0) $ctrl->supprimer($id);
        break;
    case 'inscrire':
    if ($id > 0) {
        $nom = $_POST['nom'] ?? '';
        $email = $_POST['email'] ?? '';
        $result = $ctrl->inscrireEvenement($id, $nom, $email);
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
    break;
    case 'inscriptions':
        $ctrl->listerInscriptions();
        break;
    case 'delete_insc':
        if ($id > 0) $ctrl->supprimerInscription($id);
        break;
    case 'detail':
        if ($id > 0) $ctrl->afficherDetailEvenement($id);
        break;
   case 'get':
    if ($id > 0) {
        $event = $ctrl->getEventById($id);
        header('Content-Type: application/json');
        if ($event) {
            echo json_encode([
                'id'               => $event->getId(),
                'titre'            => $event->getTitre(),
                'description'      => $event->getDescription(),
                'duree'            => $event->getDuree(),
                'date_evenement'   => substr((string)$event->getDateEvenement(), 0, 10),
                'type'             => $event->getType(),
                'statut'           => $event->getStatut(),
                'lieu'             => $event->getLieu(),
                'capacite'         => $event->getCapacite(),
                'nb_inscrits'      => $event->getNbInscrits(),
                'image'            => $event->getImage(),
                'adresse_complete' => $event->getAdresseComplete(),
            ]);
        } else {
            echo json_encode(['error' => 'Event not found']);
        }
        exit;
    }
    break;
    default:
        $ctrl->listerEvenementsPublics();
        break;
}
?>
