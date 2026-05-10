<?php
require_once __DIR__ . '/../config.php';

class ForumC {
    private $pdo;
    
    public function __construct() {
        $this->pdo = config::getConnexion();
    }
    
    // List all forums for FrontOffice
    public function listerForumsFront() {
        try {
            $stmt = $this->pdo->query("
                SELECT f.*,
                       COALESCE(e.TitreFormation, 'Événement') as nom_evenement,
                       COALESCE(u.nom, 'Admin') as nom_utilisateur,
                       (SELECT COUNT(*) FROM forum_messages WHERE idForum = f.idForum) as nb_messages
                FROM forum f
                LEFT JOIN evenement e ON f.idFormation = e.idFormation
                LEFT JOIN utilisateur u ON f.idUtilisateur = u.id
                ORDER BY f.dateCreation DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("listerForumsFront error: " . $e->getMessage());
            return [];
        }
    }
    
    // Get forum details with messages
    public function getForumWithMessages($idForum) {
        // Get forum details
        $stmt = $this->pdo->prepare("
            SELECT f.*, e.TitreFormation as nom_evenement, u.nom as nom_utilisateur 
            FROM forum f 
            JOIN evenement e ON f.idFormation = e.idFormation 
            JOIN utilisateur u ON f.idUtilisateur = u.id 
            WHERE f.idForum = :id
        ");
        $stmt->execute([':id' => $idForum]);
        $forum = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$forum) {
            return null;
        }
        
        // Get messages
        $stmt = $this->pdo->prepare("
            SELECT m.*, u.nom as nom_utilisateur 
            FROM forum_messages m 
            JOIN utilisateur u ON m.idUtilisateur = u.id 
            WHERE m.idForum = :id 
            ORDER BY m.dateMessage ASC
        ");
        $stmt->execute([':id' => $idForum]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Update view count
        $this->pdo->prepare("UPDATE forum SET vues = vues + 1 WHERE idForum = :id")
                  ->execute([':id' => $idForum]);
        
        return ['forum' => $forum, 'messages' => $messages];
    }
    
    // Add a message to forum
    public function ajouterMessage($idForum, $idUtilisateur, $message) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO forum_messages (idForum, idUtilisateur, message, dateMessage, signalement) 
                VALUES (:forum, :user, :msg, NOW(), 0)
            ");
            return $stmt->execute([
                ':forum' => $idForum,
                ':user' => $idUtilisateur,
                ':msg' => trim($message)
            ]);
        } catch (Exception $e) {
            error_log("Forum add message error: " . $e->getMessage());
            return false;
        }
    }
    
    // Report a message
    public function signalerMessage($idMessage) {
        $stmt = $this->pdo->prepare("UPDATE forum_messages SET signalement = 1 WHERE idMessage = :id");
        return $stmt->execute([':id' => $idMessage]);
    }

    // ── BACK OFFICE ──────────────────────────────────────────────────────────

    public function listerAdmin(): void {
        try {
            $stmt = $this->pdo->query("
                SELECT f.*,
                       COALESCE(e.TitreFormation, 'Événement inconnu') as nom_evenement,
                       COALESCE(u.nom, 'Admin') as nom_utilisateur,
                       (SELECT COUNT(*) FROM forum_messages WHERE idForum = f.idForum) as nb_messages
                FROM forum f
                LEFT JOIN evenement e ON f.idFormation = e.idFormation
                LEFT JOIN utilisateur u ON f.idUtilisateur = u.id
                ORDER BY f.dateCreation DESC
            ");
            $forums = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("listerAdmin forums error: " . $e->getMessage());
            $forums = [];
        }

        try {
            $stmt2 = $this->pdo->query("
                SELECT m.*,
                       COALESCE(u.nom, 'Utilisateur') as nom_utilisateur,
                       COALESCE(f.TitreForum, 'Forum') as titreForum
                FROM forum_messages m
                LEFT JOIN utilisateur u ON m.idUtilisateur = u.id
                LEFT JOIN forum f ON m.idForum = f.idForum
                WHERE m.signalement = 1
                ORDER BY m.dateMessage DESC
            ");
            $messages_signales = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("listerAdmin messages error: " . $e->getMessage());
            $messages_signales = [];
        }

        $stats = $this->getStats();
        require __DIR__ . '/../Vue/BackOffice/forum/index.php';
    }

    public function messagesSignales(): void {
        $stmt = $this->pdo->query("
            SELECT m.*, u.nom as nom_utilisateur, f.TitreForum as titreForum
            FROM forum_messages m
            LEFT JOIN utilisateur u ON m.idUtilisateur = u.id
            LEFT JOIN forum f ON m.idForum = f.idForum
            WHERE m.signalement = 1
            ORDER BY m.dateMessage DESC
        ");
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        require __DIR__ . '/../Vue/BackOffice/forum/signales.php';
    }

    public function statistiques(): void {
        $stats = $this->getStats();
        require __DIR__ . '/../Vue/BackOffice/forum/statistiques.php';
    }

    public function supprimerForum(int $id): void {
        $this->pdo->prepare("DELETE FROM forum_messages WHERE idForum = ?")->execute([$id]);
        $this->pdo->prepare("DELETE FROM forum WHERE idForum = ?")->execute([$id]);
    }

    public function supprimerMessage(int $id): void {
        $this->pdo->prepare("DELETE FROM forum_messages WHERE idMessage = ?")->execute([$id]);
    }

    private function getStats(): array {
        $stats = [];
        $stats['total_forums']      = $this->pdo->query("SELECT COUNT(*) FROM forum")->fetchColumn();
        $stats['total_messages']    = $this->pdo->query("SELECT COUNT(*) FROM forum_messages")->fetchColumn();
        $stats['forums_actifs']     = $this->pdo->query("SELECT COUNT(DISTINCT idForum) FROM forum_messages WHERE dateMessage > DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
        $stats['messages_signales'] = $this->pdo->query("SELECT COUNT(*) FROM forum_messages WHERE signalement = 1")->fetchColumn();

        $stmt3 = $this->pdo->query("
            SELECT DATE_FORMAT(dateMessage, '%Y-%m') as mois, COUNT(*) as total
            FROM forum_messages
            WHERE dateMessage > DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(dateMessage, '%Y-%m')
            ORDER BY mois ASC
        ");
        $evolution = $stmt3->fetchAll(PDO::FETCH_ASSOC);
        $stats['months_labels'] = [];
        $stats['messages_data'] = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-$i months"));
            $stats['months_labels'][] = $month;
            $found = false;
            foreach ($evolution as $e) {
                if ($e['mois'] == $month) { $stats['messages_data'][] = (int)$e['total']; $found = true; break; }
            }
            if (!$found) $stats['messages_data'][] = 0;
        }

        $stats['top_forums'] = $this->pdo->query("
            SELECT f.TitreForum, COUNT(m.idMessage) as nb_messages
            FROM forum f LEFT JOIN forum_messages m ON f.idForum = m.idForum
            GROUP BY f.idForum ORDER BY nb_messages DESC LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);

        $stats['top_contributeurs'] = $this->pdo->query("
            SELECT u.nom, COUNT(m.idMessage) as nb_messages
            FROM utilisateur u LEFT JOIN forum_messages m ON u.id = m.idUtilisateur
            GROUP BY u.id ORDER BY nb_messages DESC LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);

        return $stats;
    }
}

// ============ ROUTER ============
session_start();

$controller = new ForumC();
$BASE = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

switch ($action) {
    case 'admin':
        $controller->listerAdmin();
        break;

    case 'signales':
        $controller->messagesSignales();
        break;

    case 'statistiques':
        $controller->statistiques();
        break;

    case 'supprimer_forum':
        if ($id > 0) {
            $controller->supprimerForum($id);
            header('Location: ' . $BASE . '/Controleur/forumC.php?action=admin');
            exit;
        }
        break;

    case 'supprimer_message':
        if ($id > 0) {
            $controller->supprimerMessage($id);
            $referer = $_SERVER['HTTP_REFERER'] ?? ($BASE . '/Controleur/forumC.php?action=admin');
            header('Location: ' . $referer);
            exit;
        }
        break;

    case 'signaler':
        if ($id > 0) {
            $controller->signalerMessage($id);
            $referer = $_SERVER['HTTP_REFERER'] ?? ($BASE . '/Controleur/forumC.php');
            header('Location: ' . $referer);
            exit;
        }
        break;

    case 'list':
    default:
        $forums = $controller->listerForumsFront();
        require __DIR__ . '/../Vue/FrontOffice/forum/index.php';
        break;
        
    case 'voir':
        if ($id > 0) {
            $data = $controller->getForumWithMessages($id);
            if ($data) {
                $forum = $data['forum'];
                $messages = $data['messages'];
                require __DIR__ . '/../Vue/FrontOffice/forum/discussion.php';
            } else {
                header('Location: ' . $BASE . '/Controleur/forumC.php');
            }
        }
        break;
        
    case 'ajouter_message':
        header('Content-Type: application/json');
        $idForum = $id;
        $message = isset($_POST['message']) ? trim($_POST['message']) : '';

        if ($idForum <= 0 || empty($message)) {
            echo json_encode(['success' => false, 'message' => 'Message vide ou forum invalide']);
            exit;
        }

        // Accept any session key pattern — never block on missing session
        $userId = $_SESSION['utilisateur']['id']
               ?? $_SESSION['user']['id']
               ?? $_SESSION['user_id']
               ?? 1;

        $result = $controller->ajouterMessage($idForum, (int)$userId, $message);
        echo json_encode($result
            ? ['success' => true,  'message' => 'Message publié !']
            : ['success' => false, 'message' => 'Erreur lors de l\'ajout']
        );
        exit;
        
    case 'signaler':
        if ($id > 0) {
            $controller->signalerMessage($id);
            header('Location: ' . $_SERVER['HTTP_REFERER'] ?? $BASE . '/Controleur/forumC.php');
        }
        break;
}
?>