<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Modele/forum.php';

// ── Base URL helper ──────────────────────────────────────────────────────────
// Detects the project sub-path dynamically so no URL is ever hardcoded.
// Works whether the project lives at /ProjetWeb/Esprit-PW-2A22-2526-Devcore/
// or at any other path under the web root.
function forumBaseUrl(): string {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']); // e.g. /ProjetWeb/.../Controleur/forumC.php
    $base   = rtrim(dirname(dirname($script)), '/');            // e.g. /ProjetWeb/...
    return $base;
}

class ForumControleur {

    private PDO $pdo;

    public function __construct() {
        $this->pdo = config::getConnexion();
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    private function currentUserId(): int {
        return (int)($_SESSION['user_id'] ?? $_SESSION['utilisateur']['id'] ?? 0);
    }

    private function redirect(string $path): void {
        header('Location: ' . forumBaseUrl() . $path);
        exit;
    }

    // ==================== FRONT OFFICE ====================

    public function listerForums(): void {
        $this->creerForumsAuto();
        $this->fermerForumsExpires();

        $stmt = $this->pdo->prepare("
            SELECT f.*, e.TitreFormation as nom_evenement, e.image as image_evenement, u.nom as nom_utilisateur,
                   (SELECT COUNT(*) FROM forum_messages WHERE idForum = f.idForum) as nb_messages
            FROM forum f
            LEFT JOIN evenement e ON f.idFormation = e.idFormation
            LEFT JOIN utilisateur u ON f.idUtilisateur = u.id
            ORDER BY f.dateCreation DESC
        ");
        $stmt->execute();
        $forums = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require_once __DIR__ . '/../Vue/FrontOffice/forum/index.php';
    }

    public function voirForum(int $idForum): void {
        $check = $this->pdo->prepare("SELECT est_actif FROM forum WHERE idForum = ?");
        $check->execute([$idForum]);
        $forumInfo = $check->fetch(PDO::FETCH_ASSOC);

        if (!$forumInfo || $forumInfo['est_actif'] == 0) {
            $_SESSION['error'] = "Ce forum est fermé.";
            $this->redirect('/Controleur/forumC.php');
        }

        $this->pdo->prepare("UPDATE forum SET vues = vues + 1 WHERE idForum = ?")->execute([$idForum]);

        $stmt = $this->pdo->prepare("
            SELECT f.*, e.TitreFormation as nom_evenement, u.nom as nom_utilisateur
            FROM forum f
            LEFT JOIN evenement e ON f.idFormation = e.idFormation
            LEFT JOIN utilisateur u ON f.idUtilisateur = u.id
            WHERE f.idForum = ?
        ");
        $stmt->execute([$idForum]);
        $forum = $stmt->fetch(PDO::FETCH_ASSOC);

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

    public function ajouterMessage(int $idForum): void {
        $check = $this->pdo->prepare("SELECT est_actif FROM forum WHERE idForum = ?");
        $check->execute([$idForum]);
        $forumInfo = $check->fetch(PDO::FETCH_ASSOC);

        if (!$forumInfo || $forumInfo['est_actif'] == 0) {
            $_SESSION['error'] = "Ce forum est fermé.";
            $this->redirect('/Controleur/forumC.php');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $message      = trim($_POST['message'] ?? '');
            $idUtilisateur = $this->currentUserId();

            if ($idUtilisateur === 0) {
                $_SESSION['error'] = "Vous devez être connecté pour poster un message.";
                $this->redirect("/Controleur/forumC.php?action=voir&id=$idForum");
            }

            if (empty($message)) {
                $_SESSION['error'] = "Message vide";
                $this->redirect("/Controleur/forumC.php?action=voir&id=$idForum");
            }

            $stmt = $this->pdo->prepare("INSERT INTO forum_messages (idForum, idUtilisateur, message, dateMessage) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$idForum, $idUtilisateur, $message]);

            $_SESSION['success'] = "Message ajouté !";
            $this->redirect("/Controleur/forumC.php?action=voir&id=$idForum");
        }
    }

    public function signalerMessage(int $id): void {
        $this->pdo->prepare("UPDATE forum_messages SET signalement = 1 WHERE idMessage = ?")->execute([$id]);
        $_SESSION['success'] = "Message signalé";
        $referer = $_SERVER['HTTP_REFERER'] ?? (forumBaseUrl() . '/Controleur/forumC.php');
        header('Location: ' . $referer);
        exit;
    }

    public function modifierMessage(int $id): void {
        header('Content-Type: application/json');

        $nouveauMessage = trim($_POST['message'] ?? '');

        if (empty($nouveauMessage)) {
            echo json_encode(['success' => false, 'message' => 'Message vide']);
            exit;
        }

        $idUtilisateur = $this->currentUserId();
        if ($idUtilisateur === 0) {
            echo json_encode(['success' => false, 'message' => 'Non connecté']);
            exit;
        }

        $check = $this->pdo->prepare("SELECT idUtilisateur FROM forum_messages WHERE idMessage = ?");
        $check->execute([$id]);
        $auteur = $check->fetch(PDO::FETCH_ASSOC);

        if (!$auteur || $auteur['idUtilisateur'] != $idUtilisateur) {
            echo json_encode(['success' => false, 'message' => 'Vous ne pouvez pas modifier ce message']);
            exit;
        }

        $stmt   = $this->pdo->prepare("UPDATE forum_messages SET message = ? WHERE idMessage = ?");
        $result = $stmt->execute([$nouveauMessage, $id]);

        echo json_encode($result
            ? ['success' => true,  'message' => 'Message modifié']
            : ['success' => false, 'message' => 'Erreur lors de la modification']
        );
        exit;
    }

    // ==================== BACK OFFICE ====================

    public function listerAdmin(): void {
        $stmt = $this->pdo->query("
            SELECT f.*, e.TitreFormation as nom_evenement, u.nom as nom_utilisateur,
                   (SELECT COUNT(*) FROM forum_messages WHERE idForum = f.idForum) as nb_messages
            FROM forum f
            LEFT JOIN evenement e ON f.idFormation = e.idFormation
            LEFT JOIN utilisateur u ON f.idUtilisateur = u.id
            ORDER BY f.dateCreation DESC
        ");
        $forums = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt2 = $this->pdo->query("
            SELECT m.*, u.nom as nom_utilisateur, f.TitreForum as titreForum
            FROM forum_messages m
            LEFT JOIN utilisateur u ON m.idUtilisateur = u.id
            LEFT JOIN forum f ON m.idForum = f.idForum
            WHERE m.signalement = 1
            ORDER BY m.dateMessage DESC
        ");
        $messages_signales = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        $stats = [];
        $stats['total_forums']     = $this->pdo->query("SELECT COUNT(*) FROM forum")->fetchColumn();
        $stats['total_messages']   = $this->pdo->query("SELECT COUNT(*) FROM forum_messages")->fetchColumn();
        $stats['forums_actifs']    = $this->pdo->query("SELECT COUNT(DISTINCT idForum) FROM forum_messages WHERE dateMessage > DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
        $stats['messages_signales']= $this->pdo->query("SELECT COUNT(*) FROM forum_messages WHERE signalement = 1")->fetchColumn();

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
                if ($e['mois'] == $month) {
                    $stats['messages_data'][] = (int)$e['total'];
                    $found = true;
                    break;
                }
            }
            if (!$found) $stats['messages_data'][] = 0;
        }

        $stats['top_forums'] = $this->pdo->query("
            SELECT f.TitreForum, COUNT(m.idMessage) as nb_messages
            FROM forum f
            LEFT JOIN forum_messages m ON f.idForum = m.idForum
            GROUP BY f.idForum
            ORDER BY nb_messages DESC
            LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);

        $stats['top_contributeurs'] = $this->pdo->query("
            SELECT u.nom, COUNT(m.idMessage) as nb_messages
            FROM utilisateur u
            LEFT JOIN forum_messages m ON u.id = m.idUtilisateur
            GROUP BY u.id
            ORDER BY nb_messages DESC
            LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);

        require_once __DIR__ . '/../Vue/BackOffice/forum/index.php';
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
        require_once __DIR__ . '/../Vue/BackOffice/forum/signales.php';
    }

    public function supprimerMessage(int $id): void {
        $this->pdo->prepare("DELETE FROM forum_messages WHERE idMessage = ?")->execute([$id]);
        $this->redirect('/Controleur/forumC.php?action=signales');
    }

    public function supprimerForum(int $id): void {
        $this->pdo->prepare("DELETE FROM forum_messages WHERE idForum = ?")->execute([$id]);
        $this->pdo->prepare("DELETE FROM forum WHERE idForum = ?")->execute([$id]);
        $this->redirect('/Controleur/forumC.php?action=admin');
    }

    public function statistiques(): void {
        $stats = [];
        $stats['total_forums']      = $this->pdo->query("SELECT COUNT(*) FROM forum")->fetchColumn();
        $stats['total_messages']    = $this->pdo->query("SELECT COUNT(*) FROM forum_messages")->fetchColumn();
        $stats['forums_actifs']     = $this->pdo->query("SELECT COUNT(DISTINCT idForum) FROM forum_messages WHERE dateMessage > DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
        $stats['messages_signales'] = $this->pdo->query("SELECT COUNT(*) FROM forum_messages WHERE signalement = 1")->fetchColumn();

        $stats['top_forums'] = $this->pdo->query("
            SELECT f.TitreForum, COUNT(m.idMessage) as nb_messages
            FROM forum f
            LEFT JOIN forum_messages m ON f.idForum = m.idForum
            GROUP BY f.idForum
            ORDER BY nb_messages DESC
            LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);

        $stats['top_contributeurs'] = $this->pdo->query("
            SELECT u.nom, COUNT(m.idMessage) as nb_messages
            FROM utilisateur u
            LEFT JOIN forum_messages m ON u.id = m.idUtilisateur
            GROUP BY u.id
            ORDER BY nb_messages DESC
            LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Évolution 6 mois (needed by statistiques view)
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

        require_once __DIR__ . '/../Vue/BackOffice/forum/statistiques.php';
    }

    // ==================== CRÉATION AUTO ====================

    public function creerForumsAuto(): void {
        $stmt = $this->pdo->prepare("
            SELECT e.*
            FROM evenement e
            LEFT JOIN forum f ON e.idFormation = f.idFormation
            WHERE e.DateFormation = CURDATE()
            AND f.idForum IS NULL
            AND e.statut = 'actif'
        ");
        $stmt->execute();
        $evenements = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $count = 0;
        foreach ($evenements as $event) {
            $date_fermeture = date('Y-m-d', strtotime($event['DateFormation'] . ' + 7 days'));
            $stmt2 = $this->pdo->prepare("
                INSERT INTO forum (idFormation, idUtilisateur, TitreForum, sujet, message, dateCreation, date_ouverture, date_fermeture, est_actif)
                VALUES (?, 1, ?, ?, ?, NOW(), ?, ?, 1)
            ");
            $stmt2->execute([
                $event['idFormation'],
                "Forum - " . $event['TitreFormation'],
                "Bienvenue sur le forum de l'événement : " . $event['TitreFormation'],
                "Discutez ici de l'événement !",
                $event['DateFormation'],
                $date_fermeture
            ]);
            $count++;
        }

        if ($count > 0) {
            $_SESSION['success'] = "$count forum(s) créé(s) pour les événements du jour.";
        }
    }

    private function fermerForumsExpires(): void {
        $this->pdo->prepare("
            UPDATE forum SET est_actif = 0
            WHERE date_fermeture < CURDATE() AND est_actif = 1
        ")->execute();
    }
}

// ==================== ROUTER ====================
$ctrl   = new ForumControleur();
$action = $_GET['action'] ?? 'list';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

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
    case 'signales':
        $ctrl->messagesSignales();
        break;
    case 'supprimer_message':
        if ($id > 0) $ctrl->supprimerMessage($id);
        break;
    case 'statistiques':
        $ctrl->statistiques();
        break;
    case 'creer_forums_auto':
        $ctrl->creerForumsAuto();
        header('Location: ' . forumBaseUrl() . '/Controleur/forumC.php?action=admin');
        exit;
    case 'signaler':
        if ($id > 0) $ctrl->signalerMessage($id);
        break;
    case 'modifier_message':
        if ($id > 0) $ctrl->modifierMessage($id);
        break;
    default:
        $ctrl->listerForums();
        break;
}
?>

class ForumControleur {

    private PDO $pdo;

    public function __construct() {
        $this->pdo = config::getConnexion();
    }

    // ==================== FRONT OFFICE ====================

    public function listerForums(): void {
    $this->creerForumsAuto();
    $this->fermerForumsExpires();
    
    $stmt = $this->pdo->prepare("
        SELECT f.*, e.TitreFormation as nom_evenement, e.image as image_evenement, u.nom as nom_utilisateur,
               (SELECT COUNT(*) FROM forum_messages WHERE idForum = f.idForum) as nb_messages
        FROM forum f
        LEFT JOIN evenement e ON f.idFormation = e.idFormation
        LEFT JOIN utilisateur u ON f.idUtilisateur = u.id
        -- COMMENTE LA LIGNE CI-DESSOUS TEMPORAIREMENT
        -- WHERE (f.est_actif = 1 OR f.est_actif IS NULL)
        ORDER BY f.dateCreation DESC
    ");
    $stmt->execute();
    $forums = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    require_once __DIR__ . '/../Vue/FrontOffice/forum/index.php';
}

    public function voirForum(int $idForum): void {
        $check = $this->pdo->prepare("SELECT est_actif FROM forum WHERE idForum = ?");
        $check->execute([$idForum]);
        $forumInfo = $check->fetch(PDO::FETCH_ASSOC);
        
        if (!$forumInfo || $forumInfo['est_actif'] == 0) {
            $_SESSION['error'] = "Ce forum est fermé.";
            header('Location: /ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/forumC.php');
            exit;
        }
        
        $this->pdo->prepare("UPDATE forum SET vues = vues + 1 WHERE idForum = ?")->execute([$idForum]);
        
        $stmt = $this->pdo->prepare("
            SELECT f.*, e.TitreFormation as nom_evenement, u.nom as nom_utilisateur
            FROM forum f
            LEFT JOIN evenement e ON f.idFormation = e.idFormation
            LEFT JOIN utilisateur u ON f.idUtilisateur = u.id
            WHERE f.idForum = ?
        ");
        $stmt->execute([$idForum]);
        $forum = $stmt->fetch(PDO::FETCH_ASSOC);
        
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

    public function ajouterMessage(int $idForum): void {
        $check = $this->pdo->prepare("SELECT est_actif FROM forum WHERE idForum = ?");
        $check->execute([$idForum]);
        $forumInfo = $check->fetch(PDO::FETCH_ASSOC);
        
        if (!$forumInfo || $forumInfo['est_actif'] == 0) {
            $_SESSION['error'] = "Ce forum est fermé.";
            header('Location: /ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/forumC.php');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $message = $_POST['message'] ?? '';
            $idUtilisateur = $_SESSION['user_id'] ?? 1;
            
            if (empty($message)) {
                $_SESSION['error'] = "Message vide";
                header("Location: /ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/forumC.php?action=voir&id=$idForum");
                exit;
            }
            
            $stmt = $this->pdo->prepare("INSERT INTO forum_messages (idForum, idUtilisateur, message, dateMessage) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$idForum, $idUtilisateur, $message]);
            
            $_SESSION['success'] = "Message ajouté !";
            header("Location: /ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/forumC.php?action=voir&id=$idForum");
            exit;
        }
    }

    public function signalerMessage(int $id): void {
        $this->pdo->prepare("UPDATE forum_messages SET signalement = 1 WHERE idMessage = ?")->execute([$id]);
        $_SESSION['success'] = "Message signalé";
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    public function modifierMessage(int $id): void {
        header('Content-Type: application/json');
        
        $nouveauMessage = $_POST['message'] ?? '';
        
        if (empty($nouveauMessage)) {
            echo json_encode(['success' => false, 'message' => 'Message vide']);
            exit;
        }
        
        $idUtilisateur = $_SESSION['user_id'] ?? 1;
        $check = $this->pdo->prepare("SELECT idUtilisateur FROM forum_messages WHERE idMessage = ?");
        $check->execute([$id]);
        $auteur = $check->fetch(PDO::FETCH_ASSOC);
        
        if (!$auteur || $auteur['idUtilisateur'] != $idUtilisateur) {
            echo json_encode(['success' => false, 'message' => 'Vous ne pouvez pas modifier ce message']);
            exit;
        }
        
        $stmt = $this->pdo->prepare("UPDATE forum_messages SET message = ? WHERE idMessage = ?");
        $result = $stmt->execute([$nouveauMessage, $id]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Message modifié']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la modification']);
        }
        exit;
    }

    // ==================== BACK OFFICE ====================

    public function listerAdmin(): void {
    // 1. Récupérer tous les forums
    $stmt = $this->pdo->query("
        SELECT f.*, e.TitreFormation as nom_evenement, u.nom as nom_utilisateur,
               (SELECT COUNT(*) FROM forum_messages WHERE idForum = f.idForum) as nb_messages
        FROM forum f
        LEFT JOIN evenement e ON f.idFormation = e.idFormation
        LEFT JOIN utilisateur u ON f.idUtilisateur = u.id
        ORDER BY f.dateCreation DESC
    ");
    $forums = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 2. Récupérer les messages signalés
    $stmt2 = $this->pdo->query("
        SELECT m.*, u.nom as nom_utilisateur, f.TitreForum as titreForum
        FROM forum_messages m
        LEFT JOIN utilisateur u ON m.idUtilisateur = u.id
        LEFT JOIN forum f ON m.idForum = f.idForum
        WHERE m.signalement = 1
        ORDER BY m.dateMessage DESC
    ");
    $messages_signales = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
    // 3. Statistiques
    $stats = [];
    $stats['total_forums'] = $this->pdo->query("SELECT COUNT(*) FROM forum")->fetchColumn();
    $stats['total_messages'] = $this->pdo->query("SELECT COUNT(*) FROM forum_messages")->fetchColumn();
    $stats['forums_actifs'] = $this->pdo->query("SELECT COUNT(DISTINCT idForum) FROM forum_messages WHERE dateMessage > DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
    $stats['messages_signales'] = $this->pdo->query("SELECT COUNT(*) FROM forum_messages WHERE signalement = 1")->fetchColumn();
    
    // 4. Évolution des messages par mois (6 derniers mois) - POUR LE GRAPHIQUE
    $stmt3 = $this->pdo->query("
        SELECT DATE_FORMAT(dateMessage, '%Y-%m') as mois, COUNT(*) as total
        FROM forum_messages
        WHERE dateMessage > DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(dateMessage, '%Y-%m')
        ORDER BY mois ASC
    ");
    $evolution = $stmt3->fetchAll(PDO::FETCH_ASSOC);
    
    // Créer un tableau avec les 6 derniers mois
    $stats['months_labels'] = [];
    $stats['messages_data'] = [];
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $stats['months_labels'][] = $month;
        $found = false;
        foreach ($evolution as $e) {
            if ($e['mois'] == $month) {
                $stats['messages_data'][] = (int)$e['total'];
                $found = true;
                break;
            }
        }
        if (!$found) {
            $stats['messages_data'][] = 0;
        }
    }
    
    // 5. Top forums
    $stats['top_forums'] = $this->pdo->query("
        SELECT f.TitreForum, COUNT(m.idMessage) as nb_messages
        FROM forum f
        LEFT JOIN forum_messages m ON f.idForum = m.idForum
        GROUP BY f.idForum
        ORDER BY nb_messages DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // 6. Top contributeurs
    $stats['top_contributeurs'] = $this->pdo->query("
        SELECT u.nom, COUNT(m.idMessage) as nb_messages
        FROM utilisateur u
        LEFT JOIN forum_messages m ON u.id = m.idUtilisateur
        GROUP BY u.id
        ORDER BY nb_messages DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    require_once __DIR__ . '/../Vue/BackOffice/forum/index.php';
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
    require_once __DIR__ . '/../Vue/BackOffice/forum/signales.php';
}

    public function supprimerMessage(int $id): void {
        $this->pdo->prepare("DELETE FROM forum_messages WHERE idMessage = ?")->execute([$id]);
        header('Location: /ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/forumC.php?action=signales');
        exit;
    }

    public function supprimerForum(int $id): void {
        $this->pdo->prepare("DELETE FROM forum_messages WHERE idForum = ?")->execute([$id]);
        $this->pdo->prepare("DELETE FROM forum WHERE idForum = ?")->execute([$id]);
        header('Location: /ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/forumC.php?action=admin');
        exit;
    }

    public function statistiques(): void {
        $stats = [];
        $stats['total_forums'] = $this->pdo->query("SELECT COUNT(*) FROM forum")->fetchColumn();
        $stats['total_messages'] = $this->pdo->query("SELECT COUNT(*) FROM forum_messages")->fetchColumn();
        $stats['forums_actifs'] = $this->pdo->query("SELECT COUNT(DISTINCT idForum) FROM forum_messages WHERE dateMessage > DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
        $stats['messages_signales'] = $this->pdo->query("SELECT COUNT(*) FROM forum_messages WHERE signalement = 1")->fetchColumn();
        
        $stats['top_forums'] = $this->pdo->query("
            SELECT f.TitreForum, COUNT(m.idMessage) as nb_messages
            FROM forum f
            LEFT JOIN forum_messages m ON f.idForum = m.idForum
            GROUP BY f.idForum
            ORDER BY nb_messages DESC
            LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        $stats['top_contributeurs'] = $this->pdo->query("
            SELECT u.nom, COUNT(m.idMessage) as nb_messages
            FROM utilisateur u
            LEFT JOIN forum_messages m ON u.id = m.idUtilisateur
            GROUP BY u.id
            ORDER BY nb_messages DESC
            LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        require_once __DIR__ . '/../Vue/BackOffice/forum/statistiques.php';
    }

    // ==================== CRÉATION AUTO ====================

    public function creerForumsAuto(): void {
        $stmt = $this->pdo->prepare("
            SELECT e.* 
            FROM evenement e
            LEFT JOIN forum f ON e.idFormation = f.idFormation
            WHERE e.DateFormation = CURDATE() 
            AND f.idForum IS NULL
            AND e.statut = 'actif'
        ");
        $stmt->execute();
        $evenements = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $count = 0;
        foreach ($evenements as $event) {
            $date_fermeture = date('Y-m-d', strtotime($event['DateFormation'] . ' + 7 days'));
            
            $stmt2 = $this->pdo->prepare("
                INSERT INTO forum (idFormation, idUtilisateur, TitreForum, sujet, message, dateCreation, date_ouverture, date_fermeture, est_actif)
                VALUES (?, 1, ?, ?, ?, NOW(), ?, ?, 1)
            ");
            
            $titre = "Forum - " . $event['TitreFormation'];
            $sujet = "Bienvenue sur le forum de l'événement : " . $event['TitreFormation'];
            $message = "Discutez ici de l'événement !";
            
            $stmt2->execute([
                $event['idFormation'],
                $titre,
                $sujet,
                $message,
                $event['DateFormation'],
                $date_fermeture
            ]);
            $count++;
        }
        
        $_SESSION['success'] = $count . " forum(s) créé(s) pour les événements du jour.";
    }

    private function fermerForumsExpires(): void {
        $this->pdo->prepare("
            UPDATE forum SET est_actif = 0 
            WHERE date_fermeture < CURDATE() AND est_actif = 1
        ")->execute();
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
    case 'signales':
        $ctrl->messagesSignales();
        break;
    case 'supprimer_message':
        if ($id > 0) $ctrl->supprimerMessage($id);
        break;
    case 'signales':
        $ctrl->messagesSignales();
    break;
    case 'statistiques':
        $ctrl->statistiques();
        break;
    case 'creer_forums_auto':
        $ctrl->creerForumsAuto();
        header('Location: /ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/forumC.php?action=admin');
        exit;
    case 'modifier_message':
        if ($id > 0) $ctrl->modifierMessage($id);
        break;
    default:
        $ctrl->listerForums();
        break;
}
?>