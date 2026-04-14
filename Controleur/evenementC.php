<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Modele/evenement.php';

class EvenementControleur {

    private PDO $pdo;

    public function __construct() {
        $this->pdo = config::getConnexion();
    }

    public function listerAdmin(): void {
        $stmt = $this->pdo->query("SELECT * FROM evenement ORDER BY idFormation DESC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $evenements = array_map([$this, 'hydrate'], $rows);
        require_once __DIR__ . '/../Vue/BackOffice/evenement/index.php';
    }

    private function uploadImage($file, $eventId) {
        if ($file['error'] !== UPLOAD_ERR_OK) return null;
        
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $nom = 'event_' . $eventId . '_' . time() . '.' . $ext;
        $dossier = __DIR__ . '/../Vue/public/uploads/evenements/';
        
        if (!is_dir($dossier)) mkdir($dossier, 0777, true);
        
        if (move_uploaded_file($file['tmp_name'], $dossier . $nom)) {
            return 'Vue/public/uploads/evenements/' . $nom;
        }
        return null;
    }

    public function creer(array $data, array $files = []): void {
        $date = $data['date_evenement'];
        if (strpos($date, 'T') !== false) $date = explode('T', $date)[0];
        
        $stmt = $this->pdo->prepare(
            "INSERT INTO evenement 
            (TitreFormation, description, Duree, DateFormation, type, statut, lieu, capacite, nb_inscrits, image)
            VALUES 
            (:titre, :description, :duree, :date, :type, :statut, :lieu, :capacite, 0, :image)"
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
            ':image'       => null
        ]);
        
        $eventId = $this->pdo->lastInsertId();
        
        if (isset($files['image']) && $files['image']['error'] === UPLOAD_ERR_OK) {
            $path = $this->uploadImage($files['image'], $eventId);
            if ($path) {
                $this->pdo->prepare("UPDATE evenement SET image = ? WHERE idFormation = ?")->execute([$path, $eventId]);
            }
        }

        header('Location: /ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/evenementC.php?action=admin');
        exit;
    }

    public function modifier(int $id, array $data, array $files = []): void {
        $date = $data['date_evenement'];
        if (strpos($date, 'T') !== false) $date = explode('T', $date)[0];
        
        $old = $this->getEventById($id);
        $imagePath = $old ? $old->getImage() : null;
        
        if (isset($files['image']) && $files['image']['error'] === UPLOAD_ERR_OK) {
            if ($imagePath && file_exists(__DIR__ . '/../' . $imagePath)) unlink(__DIR__ . '/../' . $imagePath);
            $imagePath = $this->uploadImage($files['image'], $id);
        }
        
        $stmt = $this->pdo->prepare(
            "UPDATE evenement SET 
                TitreFormation = :titre,
                description = :description,
                Duree = :duree,
                DateFormation = :date,
                type = :type,
                statut = :statut,
                lieu = :lieu,
                capacite = :capacite,
                image = :image
            WHERE idFormation = :id"
        );

        $stmt->execute([
            ':id'          => $id,
            ':titre'       => $data['titre'],
            ':description' => $data['description'] ?? '',
            ':duree'       => (int)($data['duree'] ?? 0),
            ':date'        => $date,
            ':type'        => $data['type'],
            ':statut'      => $data['statut'],
            ':lieu'        => $data['lieu'] ?? '',
            ':capacite'    => (int)($data['capacite'] ?? 0),
            ':image'       => $imagePath
        ]);

        header('Location: /ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/evenementC.php?action=admin');
        exit;
    }

    public function supprimer(int $id): void {
        $event = $this->getEventById($id);
        if ($event && $event->getImage()) {
            $path = __DIR__ . '/../' . $event->getImage();
            if (file_exists($path)) unlink($path);
        }
        $this->pdo->prepare("DELETE FROM evenement WHERE idFormation = ?")->execute([$id]);
        header('Location: /ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/evenementC.php?action=admin');
        exit;
    }

    public function getEventById(int $id): ?Evenement {
        $stmt = $this->pdo->prepare("SELECT * FROM evenement WHERE idFormation = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) {
            return null;
        }
        
        return $this->hydrate($row);
    }

    private function hydrate(array $row): Evenement {
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
            $row['image'] ?? null
        );
    }
    
    public function listerEvenementsPublics(): void {
        $stmt = $this->pdo->prepare("SELECT * FROM evenement WHERE statut = 'actif' ORDER BY DateFormation ASC");
        $stmt->execute();
        $evenements = array_map([$this, 'hydrate'], $stmt->fetchAll(PDO::FETCH_ASSOC));
        require_once __DIR__ . '/../Vue/FrontOffice/evenement/index.php';
    }
    
    public function afficherEvenement(int $id): void {
        $stmt = $this->pdo->prepare("SELECT * FROM evenement WHERE idFormation = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) { header('Location: /ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/evenementC.php'); exit; }
        $evenement = $this->hydrate($row);
        require_once __DIR__ . '/../Vue/FrontOffice/evenement/detail.php';
    }

public function inscrireEvenement(int $idEvenement): void {
    header('Content-Type: application/json');
    
    $nom = $_POST['nom'] ?? '';
    $email = $_POST['email'] ?? '';
    
    if (empty($nom) || empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Nom et email requis']);
        exit;
    }
    
    try {
        $userId = 1; // ID temporaire
        
        // Vérifier si déjà inscrit
        $check = $this->pdo->prepare("SELECT * FROM inscription_evenement WHERE id_evenement = ? AND id_utilisateur = ?");
        $check->execute([$idEvenement, $userId]);
        
        if ($check->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Vous êtes déjà inscrit']);
            exit;
        }
        
        // Vérifier les places
        $event = $this->getEventById($idEvenement);
        if ($event && $event->getNbInscrits() >= $event->getCapacite()) {
            echo json_encode(['success' => false, 'message' => 'Événement complet']);
            exit;
        }
        
        // Insérer
        $stmt = $this->pdo->prepare("INSERT INTO inscription_evenement (id_evenement, id_utilisateur, nom_utilisateur, email_utilisateur, inscrit_le) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$idEvenement, $userId, $nom, $email]);
        
        // Mettre à jour le compteur
        $this->pdo->prepare("UPDATE evenement SET nb_inscrits = nb_inscrits + 1 WHERE idFormation = ?")->execute([$idEvenement]);
        
        echo json_encode(['success' => true, 'message' => 'Inscription confirmée !']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
    }
    exit;
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
        
        header('Location: /ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/evenementC.php?action=inscriptions');
        exit;
    }
}

// ROUTER
$ctrl = new EvenementControleur();
$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

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
    case 'get':
        if ($id > 0) {
            $event = $ctrl->getEventById($id);
            if ($event) {
                header('Content-Type: application/json');
                echo json_encode([
                    'id' => $event->getId(),
                    'titre' => $event->getTitre(),
                    'description' => $event->getDescription(),
                    'duree' => $event->getDuree(),
                    'date_evenement' => $event->getDateEvenement(),
                    'type' => $event->getType(),
                    'statut' => $event->getStatut(),
                    'lieu' => $event->getLieu(),
                    'capacite' => $event->getCapacite(),
                    'image' => $event->getImage()
                ]);
            } else {
                echo json_encode(['error' => 'Event not found']);
            }
            exit;
        }
        break;
    case 'inscrire':
        if ($id > 0) {
            $ctrl->inscrireEvenement($id);
        }
        break;
    case 'inscriptions':
        $ctrl->listerInscriptions();
        break;
    case 'delete_insc':
        if ($id > 0) {
            $ctrl->supprimerInscription($id);
        }
        break;
    default:
        $ctrl->listerEvenementsPublics();
        break;
}
?>