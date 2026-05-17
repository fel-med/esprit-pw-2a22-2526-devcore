<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Modele/reclamation.php';
require_once __DIR__ . '/../Modele/reponse.php';
require_once __DIR__ . '/../Controleur/utilisateurC.php';
require_once __DIR__ . '/../Controleur/notificationC.php';
require_once __DIR__ . '/../Controleur/session_helper.php';

class ReponseC {

    private function buildModuleLink($path, array $query = []): string
    {
        $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        if (($position = strpos($scriptName, '/Vue/')) !== false) {
            $base = substr($scriptName, 0, $position);
        } elseif (($position = strpos($scriptName, '/Controleur/')) !== false) {
            $base = substr($scriptName, 0, $position);
        } else {
            $base = '/php/cre8connect';
        }

        $link = rtrim($base, '/') . '/' . ltrim((string) $path, '/');
        if (!empty($query)) {
            $link .= '?' . http_build_query($query);
        }

        return $link;
    }

    private function notifyComplaintAnswered(PDO $db, $idReclamation, $idAdmin): void
    {
        $idReclamation = (int) $idReclamation;
        $idAdmin = (int) $idAdmin;
        if ($idReclamation <= 0) {
            return;
        }

        $stmt = $db->prepare("
            SELECT r.id, r.idUtilisateur, admin.nom AS adminName, admin.role AS adminRole
            FROM reclamation r
            LEFT JOIN utilisateur admin ON admin.id = :idAdmin
            WHERE r.id = :idReclamation
            LIMIT 1
        ");
        $stmt->execute([
            'idAdmin' => $idAdmin,
            'idReclamation' => $idReclamation,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $ownerId = (int) ($row['idUtilisateur'] ?? 0);
        if ($ownerId <= 0 || ($idAdmin > 0 && $ownerId === $idAdmin)) {
            return;
        }

        $adminName = trim((string) ($row['adminName'] ?? ''));
        $adminRole = trim((string) ($row['adminRole'] ?? cc_current_user_role())) ?: 'admin';

        (new NotificationC($db))->createNotification(
            $ownerId,
            'complaint_answered',
            'Complaint answered',
            $adminName !== '' ? $adminName . ' answered your complaint.' : 'Support answered your complaint.',
            $this->buildModuleLink('Vue/FrontOffice/utilisateur/reclamation.php'),
            'reclamation',
            $idReclamation,
            $idAdmin > 0 ? $idAdmin : null,
            $adminRole,
            'complaint_answered_' . $idReclamation . '_user_' . $ownerId,
            [
                'complaint_id' => $idReclamation,
                'admin_name' => $adminName,
            ]
        );
    }

    // ✔️ Ajouter une réponse (admin)
    public function ajouterReponse($reponse) {
        $sql = "INSERT INTO reponse
                (idReclamation, idAdmin, contenu)
                VALUES (:idReclamation, :idAdmin, :contenu)";
        $db = config::getConnexion();

        try {
            $query = $db->prepare($sql);
            $query->execute([
                'idReclamation' => $reponse->getIdReclamation(),
                'idAdmin'       => $reponse->getIdAdmin(),
                'contenu'       => $reponse->getContenu()
            ]);

            // 🔥 Mettre automatiquement la réclamation en "traitée"
            $sqlUpdate = "UPDATE reclamation SET statut = 'traitee' WHERE id = :id";
            $req = $db->prepare($sqlUpdate);
            $req->execute(['id' => $reponse->getIdReclamation()]);

            // 🔔 Envoyer une notification à l'utilisateur via email
            $sqlUser = "SELECT u.email, u.nom, r.description FROM reclamation r JOIN utilisateur u ON r.idUtilisateur = u.id WHERE r.id = :id";
            $stmt = $db->prepare($sqlUser);
            $stmt->execute(['id' => $reponse->getIdReclamation()]);
            $info = $stmt->fetch(PDO::FETCH_ASSOC);

            $emailSent = false;
            if ($info) {
                $userC = new UtilisateurC();
                $emailSent = $userC->sendReclamationResponseNotification(
                    $info['email'],
                    $info['nom'],
                    $info['description'],
                    $reponse->getContenu()
                );
            }

            $this->notifyComplaintAnswered($db, $reponse->getIdReclamation(), $reponse->getIdAdmin());

            // Retourner le statut de l'envoi d'email
            return [
                'success' => true,
                'email_sent' => $emailSent,
                'message' => $emailSent ? 'Réponse ajoutée et email envoyé' : 'Réponse ajoutée mais email non envoyé'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'email_sent' => false,
                'message' => 'Erreur ajout réponse: ' . $e->getMessage()
            ];
        }
    }

    // ✔️ Afficher réponses d’une réclamation
    public function afficherReponsesParReclamation($idReclamation) {
        $sql = "SELECT 
                    rep.contenu,
                    rep.date_reponse,
                    admin.nom AS admin_nom
                FROM reponse rep
                JOIN utilisateur admin ON rep.idAdmin = admin.id
                WHERE rep.idReclamation = :id";

        $db = config::getConnexion();
        $req = $db->prepare($sql);
        $req->execute(['id' => $idReclamation]);
        return $req->fetchAll();
    }

    // ✔️ Supprimer réponse
    public function supprimerReponse($id) {
        $sql = "DELETE FROM reponse WHERE id = :id";
        $db = config::getConnexion();
        $req = $db->prepare($sql);
        $req->execute(['id' => $id]);
    }

    // ✔️ Modifier réponse
    public function modifierReponse($id, $contenu) {
        $sql = "UPDATE reponse SET contenu = :contenu WHERE id = :id";
        $db = config::getConnexion();
        try {
            $req = $db->prepare($sql);
            $req->execute([
                'id' => $id,
                'contenu' => $contenu
            ]);
        } catch (Exception $e) {
            die('Erreur modification réponse: ' . $e->getMessage());
        }
    }

    // ✔️ Récupérer une réponse par ID réclamation
    public function getReponseByReclamation($idReclamation) {
        $sql = "SELECT id FROM reponse WHERE idReclamation = :idReclamation LIMIT 1";
        $db = config::getConnexion();
        $req = $db->prepare($sql);
        $req->execute(['idReclamation' => $idReclamation]);
        $result = $req->fetch();
        return $result ? $result['id'] : null;
    }
}
