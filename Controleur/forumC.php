<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Modele/forum.php';

class ForumControleur {

    private PDO $pdo;

    public function __construct() {
        $this->pdo = config::getConnexion();
    }

    // ==================== FRONT OFFICE ====================

    // Afficher tous les forums liés aux événements
    public function listerForums(): void {
    $stmt = $this->pdo->query("
        SELECT f.*, 
               e.TitreFormation as nom_evenement, 
               u.nom as nom_utilisateur,
               (SELECT COUNT(*) FROM forum_messages WHERE idForum = f.idForum) as nb_messages
        FROM forum f
        LEFT JOIN evenement e ON f.idFormation = e.idFormation
        LEFT JOIN utilisateur u ON f.idUtilisateur = u.id
        ORDER BY f.dateCreation DESC
    ");
    $forums = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    require_once __DIR__ . '/../Vue/FrontOffice/forum/index.php';
}

    // Afficher les messages d'un forum spécifique
    public function voirForum(int $idForum): void {
    // Incrémenter le compteur de vues
    $this->pdo->prepare("UPDATE forum SET vues = vues + 1 WHERE idForum = ?")->execute([$idForum]);
    
    // Récupérer les infos du forum
    $stmt = $this->pdo->prepare("
        SELECT f.*, e.TitreFormation as nom_evenement, u.nom as nom_utilisateur
        FROM forum f
        LEFT JOIN evenement e ON f.idFormation = e.idFormation
        LEFT JOIN utilisateur u ON f.idUtilisateur = u.id
        WHERE f.idForum = ?
    ");
    $stmt->execute([$idForum]);
    $forum = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$forum) {
            header('Location: /ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/forumC.php');
            exit;
        }

        // Récupérer les messages du forum
        $stmt2 = $this->pdo->prepare("
            SELECT m.*, u.nom as nom_utilisateur
            FROM forum_messages m
            LEFT JOIN utilisateur u ON m.idUtilisateur = u.id
            WHERE m.idForum = ?
            ORDER BY m.dateMessage ASC
        ");
        $stmt2->execute([$idForum]);
        $messages = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        require_once __DIR__ . '/../Vue/FrontOffice/forum/discussion.php';
    }

    // Ajouter un message dans un forum
    public function ajouterMessage(int $idForum): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $message = $_POST['message'] ?? '';
            $idUtilisateur = $_SESSION['user_id'] ?? 1; // temporaire

            if (empty($message)) {
                $_SESSION['error'] = "Le message ne peut pas être vide";
                header("Location: /ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/forumC.php?action=voir&id=$idForum");
                exit;
            }

            $stmt = $this->pdo->prepare("
                INSERT INTO forum_messages (idForum, idUtilisateur, message, dateMessage)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$idForum, $idUtilisateur, $message]);

            $_SESSION['success'] = "Message ajouté !";
            header("Location: /ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/forumC.php?action=voir&id=$idForum");
            exit;
        }
    }

    // ==================== BACK OFFICE ====================

    // Lister tous les forums (admin)
    public function listerAdmin(): void {
    try {
        $stmt = $this->pdo->query("
            SELECT f.*, 
                   e.TitreFormation as nom_evenement,
                   (SELECT COUNT(*) FROM forum_messages WHERE idForum = f.idForum) as nb_messages
            FROM forum f
            LEFT JOIN evenement e ON f.idFormation = e.idFormation
            ORDER BY f.dateCreation DESC
        ");
        $forums = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Debug - voir si des données sont récupérées
        if (empty($forums)) {
            echo "<!-- Aucun forum trouvé dans la base de données -->";
        } else {
            echo "<!-- " . count($forums) . " forums trouvés -->";
        }
        
        require_once __DIR__ . '/../Vue/BackOffice/forum/index.php';
    } catch (Exception $e) {
        echo "Erreur: " . $e->getMessage();
        $forums = [];
        require_once __DIR__ . '/../Vue/BackOffice/forum/index.php';
    }
}

    // Supprimer un forum
    public function supprimerForum(int $id): void {
        // Supprimer d'abord les messages liés
        $this->pdo->prepare("DELETE FROM forum_messages WHERE idForum = ?")->execute([$id]);
        // Puis supprimer le forum
        $this->pdo->prepare("DELETE FROM forum WHERE idForum = ?")->execute([$id]);
        
        header('Location: /ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/forumC.php?action=admin');
        exit;
    }

    // Supprimer un message signalé
    public function supprimerMessage(int $id): void {
        $this->pdo->prepare("DELETE FROM forum_messages WHERE idMessage = ?")->execute([$id]);
        
        header('Location: /ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/forumC.php?action=messages_signales');
        exit;
    }

    // Voir les messages signalés
    public function messagesSignales(): void {
        $stmt = $this->pdo->query("
            SELECT m.*, u.nom as nom_utilisateur, f.titreForum
            FROM forum_messages m
            LEFT JOIN utilisateur u ON m.idUtilisateur = u.id
            LEFT JOIN forum f ON m.idForum = f.idForum
            WHERE m.signalement = 1
            ORDER BY m.dateMessage DESC
        ");
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        require_once __DIR__ . '/../Vue/BackOffice/forum/signales.php';
    }

    // Signaler un message (FrontOffice)
    public function signalerMessage(int $id): void {
        $this->pdo->prepare("UPDATE forum_messages SET signalement = 1 WHERE idMessage = ?")->execute([$id]);
        
        $_SESSION['success'] = "Message signalé à l'administrateur";
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    // Statistiques
    

    public function statistiques(): void {
    // Statistiques générales
    $stats['total_forums'] = $this->pdo->query("SELECT COUNT(*) FROM forum")->fetchColumn();
    $stats['total_messages'] = $this->pdo->query("SELECT COUNT(*) FROM forum_messages")->fetchColumn();
    $stats['forums_actifs'] = $this->pdo->query("
        SELECT COUNT(DISTINCT idForum) FROM forum_messages 
        WHERE dateMessage > DATE_SUB(NOW(), INTERVAL 7 DAY)
    ")->fetchColumn();
    $stats['messages_signales'] = $this->pdo->query("SELECT COUNT(*) FROM forum_messages WHERE signalement = 1")->fetchColumn();
    
    // Messages par jour (7 derniers jours)
    $stats['messages_par_jour'] = $this->pdo->query("
        SELECT DATE(dateMessage) as jour, COUNT(*) as total
        FROM forum_messages
        WHERE dateMessage > DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(dateMessage)
        ORDER BY jour ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Top forums les plus actifs
    $stats['top_forums'] = $this->pdo->query("
        SELECT f.TitreForum, COUNT(m.idMessage) as nb_messages
        FROM forum f
        LEFT JOIN forum_messages m ON f.idForum = m.idForum
        GROUP BY f.idForum
        ORDER BY nb_messages DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Top contributeurs
    $stats['top_contributeurs'] = $this->pdo->query("
        SELECT u.nom, COUNT(m.idMessage) as nb_messages
        FROM utilisateur u
        LEFT JOIN forum_messages m ON u.id = m.idUtilisateur
        GROUP BY u.id
        ORDER BY nb_messages DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Evolution des messages par mois
    $stats['evolution'] = $this->pdo->query("
        SELECT DATE_FORMAT(dateMessage, '%Y-%m') as mois, COUNT(*) as total
        FROM forum_messages
        WHERE dateMessage > DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(dateMessage, '%Y-%m')
        ORDER BY mois ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    require_once __DIR__ . '/../Vue/BackOffice/forum/statistiques.php';
}
}

// ==================== ROUTER ====================
$ctrl = new ForumControleur();
$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

switch ($action) {
    case 'admin':
        $ctrl->listerAdmin();
        break;
    case 'voir':
        if ($id > 0) $ctrl->voirForum($id);
        break;
    case 'ajouter_message':
        if ($id > 0) $ctrl->ajouterMessage($id);
        break;
    case 'supprimer_forum':
        if ($id > 0) $ctrl->supprimerForum($id);
        break;
    case 'supprimer_message':
        if ($id > 0) $ctrl->supprimerMessage($id);
        break;
    case 'signales':
        $ctrl->messagesSignales();
        break;
    case 'signaler':
        if ($id > 0) $ctrl->signalerMessage($id);
        break;
    case 'statistiques':
        $ctrl->statistiques();
        break;
    default:
        $ctrl->listerForums();
        break;
}
?>