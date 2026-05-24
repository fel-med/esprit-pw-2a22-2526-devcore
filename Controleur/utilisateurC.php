<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Modele/utilisateur.php';
require_once __DIR__ . '/session_helper.php';
require_once __DIR__ . '/adminAuditC.php';
require_once __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;   
class UtilisateurC {

    private function utilisateurColumns(PDO $db): array
    {
        static $columns = null;
        if ($columns !== null) {
            return $columns;
        }

        $columns = [];
        try {
            $stmt = $db->query("SHOW COLUMNS FROM utilisateur");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $field = (string)($row['Field'] ?? '');
                if ($field !== '') {
                    $columns[$field] = true;
                }
            }
        } catch (Throwable $e) {
            error_log('Utilisateur column inspection failed: ' . $e->getMessage());
        }

        return $columns;
    }

    private function utilisateurHasColumn(PDO $db, string $column): bool
    {
        $columns = $this->utilisateurColumns($db);
        return isset($columns[$column]);
    }

    private function userSelectColumns(PDO $db): string
    {
        $columns = [
            'id',
            'nom',
            'email',
            'role',
            'statut',
            'suspended_by',
            'suspended_by_role',
            'suspended_at',
            'suspension_reason',
        ];

        foreach (['deleted_at', 'deleted_by', 'deleted_by_role', 'delete_reason', 'restored_at', 'restored_by', 'restored_by_role'] as $column) {
            if ($this->utilisateurHasColumn($db, $column)) {
                $columns[] = $column;
            }
        }

        return implode(', ', $columns);
    }

    private function activeUserFilter(PDO $db): string
    {
        return $this->utilisateurHasColumn($db, 'deleted_at') ? ' AND deleted_at IS NULL' : '';
    }

    public function userSoftDeleteColumnsReady(): bool
    {
        $db = config::getConnexion();
        foreach (['deleted_at', 'deleted_by', 'deleted_by_role', 'delete_reason', 'restored_at', 'restored_by', 'restored_by_role'] as $column) {
            if (!$this->utilisateurHasColumn($db, $column)) {
                return false;
            }
        }
        return true;
    }

    private function resolveActorId($actorId): int
    {
        if (is_numeric($actorId) && (int)$actorId > 0) {
            return (int)$actorId;
        }

        return function_exists('cc_current_user_id') ? (int)cc_current_user_id() : 0;
    }

    private function resolveActorRole($actorRole): string
    {
        $role = trim((string)$actorRole);
        if ($role !== '') {
            return function_exists('cc_normalize_role') ? cc_normalize_role($role) : strtolower($role);
        }

        return function_exists('cc_current_user_role') ? cc_current_user_role() : '';
    }

    private function buildPasswordResetBaseUrl(): string
    {
        foreach (['APP_BASE_URL', 'APP_URL', 'BASE_URL', 'SITE_URL'] as $key) {
            $configuredUrl = trim((string)($_ENV[$key] ?? ''));
            if ($configuredUrl !== '') {
                return rtrim($configuredUrl, '/');
            }
        }

        $forwardedProto = strtolower(trim(explode(',', (string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0]));
        $scheme = $forwardedProto === 'https'
            || (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
            ? 'https'
            : 'http';

        $host = trim((string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
        $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
        $projectBasePath = '';

        $frontUserMarker = '/Vue/FrontOffice/utilisateur/';
        $markerPosition = stripos($scriptName, $frontUserMarker);
        if ($markerPosition !== false) {
            $projectBasePath = substr($scriptName, 0, $markerPosition);
        } else {
            $vueMarkerPosition = stripos($scriptName, '/Vue/');
            if ($vueMarkerPosition !== false) {
                $projectBasePath = substr($scriptName, 0, $vueMarkerPosition);
            }
        }

        return rtrim($scheme . '://' . $host . $projectBasePath, '/');
    }

    public function ajouterUser($user) {
        $db = config::getConnexion();

        $check = $db->prepare("SELECT * FROM utilisateur WHERE email=?");
        $check->execute([$user->getEmail()]);
        if ($check->rowCount() > 0) return "Email déjà utilisé";

        $sql = "INSERT INTO utilisateur (nom,email,mot_de_passe,role,statut,tentatives_login,face_descriptor)
        VALUES (?,?,?,?,?,?,?)";

$query = $db->prepare($sql);
$query->execute([
    $user->getNom(),
    $user->getEmail(),
    $user->getMotDePasse(),
    $user->getRole(),
    "actif",
    0,
    $user->getFaceDescriptor() // 👈 AJOUT IMPORTANT
]);

        return "success";
    }
public function updateUser($id, $nom, $email, $role) {
    $db = config::getConnexion();
    $sql = "UPDATE utilisateur SET nom = :nom, email = :email, role = :role WHERE id = :id";
    $req = $db->prepare($sql);
    $req->execute([
        'id' => $id,
        'nom' => $nom,
        'email' => $email,
        'role' => $role
        
    ]);
}

public function afficherAdminAccounts(array $roles) {
    $db = config::getConnexion();
    $roles = array_values(array_intersect($roles, ['admin', 'super_admin']));
    if (empty($roles)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($roles), '?'));
    $select = $this->userSelectColumns($db);
    $stmt = $db->prepare("
        SELECT $select
        FROM utilisateur
        WHERE role IN ($placeholders)" . $this->activeUserFilter($db) . "
        ORDER BY role DESC, id DESC
    ");
    $stmt->execute($roles);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function getUserById($id) {
    $db = config::getConnexion();
    $select = $this->userSelectColumns($db);
    $stmt = $db->prepare("
        SELECT $select
        FROM utilisateur
        WHERE id = ?
    ");
    $stmt->execute([(int)$id]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

public function getUserByEmail($email) {
    $db = config::getConnexion();
    $select = $this->userSelectColumns($db);
    $stmt = $db->prepare("
        SELECT $select
        FROM utilisateur
        WHERE email = ?
        LIMIT 1
    ");
    $stmt->execute([trim((string)$email)]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

public function getDeletedUsers(): array {
    $db = config::getConnexion();
    if (!$this->userSoftDeleteColumnsReady()) {
        return [];
    }

    $select = $this->userSelectColumns($db);
    $stmt = $db->query("
        SELECT $select
        FROM utilisateur
        WHERE deleted_at IS NOT NULL
        ORDER BY deleted_at DESC, id DESC
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function ajouterAdminAccount($nom, $email, $password, $role) {
    $db = config::getConnexion();

    $check = $db->prepare("SELECT id FROM utilisateur WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
        return "Email deja utilise";
    }

    $stmt = $db->prepare("
        INSERT INTO utilisateur (nom, email, mot_de_passe, role, statut, tentatives_login, face_descriptor)
        VALUES (?, ?, ?, ?, 'actif', 0, '')
    ");
    $stmt->execute([
        $nom,
        $email,
        password_hash($password, PASSWORD_DEFAULT),
        $role,
    ]);

    return "success";
}

public function updateUserStatus($id, $status) {
    $db = config::getConnexion();
    $stmt = $db->prepare("UPDATE utilisateur SET statut = ? WHERE id = ?");
    $stmt->execute([$status, (int)$id]);
}

public function suspendUserWithMetadata($targetId, $actorId, $actorRole, $reason) {
    $db = config::getConnexion();
    $stmt = $db->prepare("
        UPDATE utilisateur
        SET statut = 'suspendu',
            suspended_by = ?,
            suspended_by_role = ?,
            suspended_at = NOW(),
            suspension_reason = ?
        WHERE id = ?
    ");
    $stmt->execute([
        (int)$actorId,
        strtolower(trim((string)$actorRole)),
        trim((string)$reason),
        (int)$targetId,
    ]);
}

public function reactivateUserAndClearSuspension($targetId) {
    $db = config::getConnexion();
    $stmt = $db->prepare("
        UPDATE utilisateur
        SET statut = 'actif',
            suspended_by = NULL,
            suspended_by_role = NULL,
            suspended_at = NULL,
            suspension_reason = NULL
        WHERE id = ?
    ");
    $stmt->execute([(int)$targetId]);
}

public function softDeleteUserById($id, $actorId = null, $actorRole = null, $reason = null): array
{
    $db = config::getConnexion();
    if (!$this->userSoftDeleteColumnsReady()) {
        return ['success' => false, 'message' => 'Soft-delete columns are missing. Run the required SQL first.'];
    }

    $targetId = (int)$id;
    $actorId = $this->resolveActorId($actorId);
    $actorRole = $this->resolveActorRole($actorRole);
    $reason = trim((string)($reason ?? 'Deleted from BackOffice user management'));

    if ($targetId <= 0 || $actorId <= 0 || $actorRole === '') {
        return ['success' => false, 'message' => 'Missing actor or target user.'];
    }

    $before = $this->getUserById($targetId);
    if (!$before) {
        return ['success' => false, 'message' => 'Target account was not found.'];
    }
    if (!empty($before['deleted_at'])) {
        return ['success' => false, 'message' => 'Target account is already deleted.'];
    }

    $stmt = $db->prepare("
        UPDATE utilisateur
        SET deleted_at = NOW(),
            deleted_by = :deleted_by,
            deleted_by_role = :deleted_by_role,
            delete_reason = :delete_reason
        WHERE id = :id
          AND deleted_at IS NULL
    ");
    $ok = $stmt->execute([
        ':deleted_by' => $actorId,
        ':deleted_by_role' => $actorRole,
        ':delete_reason' => $reason !== '' ? $reason : null,
        ':id' => $targetId,
    ]);

    if (!$ok || $stmt->rowCount() < 1) {
        return ['success' => false, 'message' => 'Unable to delete this account.'];
    }

    $after = $this->getUserById($targetId);
    cc_log_admin_entity_action($db, $actorId, $actorRole, 'soft_delete_user', 'utilisateur', $targetId, $before, $after, $reason, 1, 'available');

    return ['success' => true, 'message' => 'Account deleted successfully.', 'before' => $before, 'after' => $after];
}

public function restoreDeletedUserById($id, $actorId = null, $actorRole = null, $reason = null): array
{
    $db = config::getConnexion();
    if (!$this->userSoftDeleteColumnsReady()) {
        return ['success' => false, 'message' => 'Soft-delete columns are missing. Run the required SQL first.'];
    }

    $targetId = (int)$id;
    $actorId = $this->resolveActorId($actorId);
    $actorRole = $this->resolveActorRole($actorRole);
    $reason = trim((string)($reason ?? 'Restored by Hyper Admin'));

    if ($targetId <= 0 || $actorId <= 0 || $actorRole === '') {
        return ['success' => false, 'message' => 'Missing actor or target user.'];
    }

    $before = $this->getUserById($targetId);
    if (!$before || empty($before['deleted_at'])) {
        return ['success' => false, 'message' => 'Deleted account was not found.'];
    }

    $stmt = $db->prepare("
        UPDATE utilisateur
        SET deleted_at = NULL,
            deleted_by = NULL,
            deleted_by_role = NULL,
            delete_reason = NULL,
            restored_at = NOW(),
            restored_by = :restored_by,
            restored_by_role = :restored_by_role
        WHERE id = :id
          AND deleted_at IS NOT NULL
    ");
    $ok = $stmt->execute([
        ':restored_by' => $actorId,
        ':restored_by_role' => $actorRole,
        ':id' => $targetId,
    ]);

    if (!$ok || $stmt->rowCount() < 1) {
        return ['success' => false, 'message' => 'Unable to restore this account.'];
    }

    $after = $this->getUserById($targetId);
    cc_log_admin_entity_action($db, $actorId, $actorRole, 'restore_deleted_user', 'utilisateur', $targetId, $before, $after, $reason, 0, 'not_available');

    return ['success' => true, 'message' => 'Account restored successfully.', 'before' => $before, 'after' => $after];
}

public function finalDeleteUserById($id, $actorId = null, $actorRole = null): array
{
    $db = config::getConnexion();
    if (!$this->userSoftDeleteColumnsReady()) {
        return ['success' => false, 'message' => 'Soft-delete columns are missing. Run the required SQL first.'];
    }

    $targetId = (int)$id;
    $actorId = $this->resolveActorId($actorId);
    $actorRole = $this->resolveActorRole($actorRole);
    if ($targetId <= 0 || $actorId <= 0 || $actorRole === '') {
        return ['success' => false, 'message' => 'Missing actor or target user.'];
    }

    $before = $this->getUserById($targetId);
    if (!$before || empty($before['deleted_at'])) {
        return ['success' => false, 'message' => 'Only soft-deleted accounts can be permanently deleted.'];
    }

    $ageStmt = $db->prepare("SELECT deleted_at <= (NOW() - INTERVAL 7 DAY) AS can_delete FROM utilisateur WHERE id = :id AND deleted_at IS NOT NULL");
    $ageStmt->execute([':id' => $targetId]);
    $canDelete = (int)($ageStmt->fetchColumn() ?: 0) === 1;
    if (!$canDelete) {
        return ['success' => false, 'message' => 'Final delete is available only after 7 days.'];
    }

    cc_log_admin_entity_action($db, $actorId, $actorRole, 'final_delete_user', 'utilisateur', $targetId, $before, null, 'Permanent delete after 7-day soft-delete window', 0, 'not_available');

    $stmt = $db->prepare("DELETE FROM utilisateur WHERE id = :id AND deleted_at IS NOT NULL AND deleted_at <= (NOW() - INTERVAL 7 DAY)");
    $ok = $stmt->execute([':id' => $targetId]);
    if (!$ok || $stmt->rowCount() < 1) {
        return ['success' => false, 'message' => 'Unable to permanently delete this account.'];
    }

    return ['success' => true, 'message' => 'Account permanently deleted.'];
}

public function deleteUserById($id) {
    $result = $this->softDeleteUserById($id, null, null, 'Deleted from Admin Management');
    return (bool)($result['success'] ?? false);
}

    public function afficherUsers($search = '', $role = '', $page = 1, $limit = 10) {
    $db = config::getConnexion();
    $sql = "SELECT * FROM utilisateur WHERE 1=1" . $this->activeUserFilter($db);
    
    if (!empty($search)) {
        $sql .= " AND (nom LIKE :search OR email LIKE :search)";
    }
    
    if (!empty($role)) {
        $sql .= " AND role = :role";
    }
    
    $sql .= " ORDER BY id DESC";

    $stmt = $db->prepare($sql);
    
    if (!empty($search)) {
        $searchTerm = "%$search%";
        $stmt->bindParam(':search', $searchTerm);
    }
    
    if (!empty($role)) {
        $stmt->bindParam(':role', $role);
    }
    
    // Backward compatibility: if only search and role are passed, return raw result array.
    if (func_num_args() < 3) {
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get total count for pagination
    $countSql = str_replace("SELECT *", "SELECT COUNT(*)", $sql);
    $countStmt = $db->prepare($countSql);
    
    if (!empty($search)) {
        $countStmt->bindParam(':search', $searchTerm);
    }
    
    if (!empty($role)) {
        $countStmt->bindParam(':role', $role);
    }
    
    $countStmt->execute();
    $totalRecords = $countStmt->fetchColumn();
    
    // Add LIMIT and OFFSET
    $offset = ($page - 1) * $limit;
    $sql .= " LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($sql);
    
    if (!empty($search)) {
        $stmt->bindParam(':search', $searchTerm);
    }
    
    if (!empty($role)) {
        $stmt->bindParam(':role', $role);
    }
    
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'data' => $results,
        'total' => $totalRecords,
        'page' => $page,
        'limit' => $limit,
        'totalPages' => ceil($totalRecords / $limit)
    ];
}

public function sendResetLink($email) {
    $db = config::getConnexion();

    $query = $db->prepare("SELECT * FROM utilisateur WHERE email=?" . $this->activeUserFilter($db));
    $query->execute([$email]);
    $user = $query->fetch();

    if (!$user) return "Email introuvable";

    // 🔐 Token sécurisé
    $token = bin2hex(random_bytes(50));
    $expire = date("Y-m-d H:i:s", strtotime("+15 minutes"));

    // Sauvegarde en DB
    $sql = "UPDATE utilisateur SET reset_token=?, reset_expire=? WHERE email=?";
    $db->prepare($sql)->execute([$token, $expire, $email]);

    $baseUrl = $this->buildPasswordResetBaseUrl();
    $link = rtrim($baseUrl, '/') . '/Vue/FrontOffice/utilisateur/reset_password.php?token=' . urlencode($token);

    $mail = new PHPMailer(true);

    try {
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'neylamhamddy@gmail.com';
        $mail->Password = 'aebg mpbl zomq idjn'; // ⚠️ PAS ton vrai mdp
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('neylamhamddy@gmail.com', 'Crea8Connect');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Réinitialisation du mot de passe - Crea8Connect';
       $mail->Body = '
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    body {
        margin:0;
        padding:0;
        background:#f4f6f9;
        font-family: Arial, sans-serif;
    }
    .container {
        max-width:600px;
        margin:40px auto;
        background:#ffffff;
        border-radius:12px;
        overflow:hidden;
        box-shadow:0 5px 15px rgba(0,0,0,0.1);
    }
    .header {
        background:linear-gradient(135deg, #9B5DE0, #B771E5);
        color:white;
        text-align:center;
        padding:30px;
        font-size:22px;
        font-weight:bold;
    }
    .content {
        padding:30px;
        color:#333;
        text-align:center;
    }
    .btn {
        display:inline-block;
        margin-top:20px;
        padding:12px 25px;
        background:#9B5DE0;
        color:white !important;
        text-decoration:none;
        border-radius:6px;
        font-weight:bold;
    }
    .footer {
        text-align:center;
        font-size:12px;
        color:#999;
        padding:20px;
    }
</style>
</head>

<body>

<div class="container">

    <div class="header">
        🔐 Crea8Connect
    </div>

    <div class="content">
        <h2>Mot de passe oublié ?</h2>

        <p>Pas de panique 👌<br>
        Clique sur le bouton ci-dessous pour réinitialiser ton mot de passe.</p>

        <a href="'.$link.'" class="btn">Réinitialiser le mot de passe</a>

        <p style="margin-top:25px; font-size:13px; color:#666;">
            ⏱ Ce lien expire dans <strong>15 minutes</strong>
        </p>
    </div>

    <div class="footer">
        © '.date("Y").' Crea8Connect — Tous droits réservés
    </div>

</div>

</body>
</html>
';
        $mail->send();

        return "Email envoyé avec succès";
    } catch (Exception $e) {
        return "Erreur: " . $mail->ErrorInfo;
    }
}
public function resetPassword($password, $token) {
    $db = config::getConnexion();

    $sql = "SELECT * FROM utilisateur 
            WHERE reset_token=? AND reset_expire > NOW()" . $this->activeUserFilter($db);
    $query = $db->prepare($sql);
    $query->execute([$token]);

    $user = $query->fetch();

    if (!$user) return "Lien invalide ou expiré";

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $sql = "UPDATE utilisateur 
            SET mot_de_passe=?, reset_token=NULL, reset_expire=NULL 
            WHERE id=?";
    $db->prepare($sql)->execute([$hashedPassword, $user['id']]);

    return "Mot de passe mis à jour";
}
    public function getStatistiquesUtilisateurs() {
        $db = config::getConnexion();
        
        // Total utilisateurs
        $activeFilter = $this->activeUserFilter($db);
        $total = $db->query("SELECT COUNT(*) as total FROM utilisateur WHERE 1=1$activeFilter")->fetch()['total'];
        
        // Par rôle
        $admin = $db->query("SELECT COUNT(*) as count FROM utilisateur WHERE role IN ('admin', 'super_admin', 'hyper_admin')$activeFilter")->fetch()['count'];
        $createur = $db->query("SELECT COUNT(*) as count FROM utilisateur WHERE role='createur'$activeFilter")->fetch()['count'];
        $marque = $db->query("SELECT COUNT(*) as count FROM utilisateur WHERE role='marque'$activeFilter")->fetch()['count'];
        
        // Par statut - Inclure les NULL (traiter comme 'actif' par défaut)
        $actif = $db->query("SELECT COUNT(*) as count FROM utilisateur WHERE (statut='actif' OR statut IS NULL)$activeFilter")->fetch()['count'];
        $inactif = $db->query("SELECT COUNT(*) as count FROM utilisateur WHERE statut='inactif'$activeFilter")->fetch()['count'];
        $suspendu = $db->query("SELECT COUNT(*) as count FROM utilisateur WHERE statut='suspendu'$activeFilter")->fetch()['count'];
        $en_attente = $db->query("SELECT COUNT(*) as count FROM utilisateur WHERE statut='en_attente'$activeFilter")->fetch()['count'];
        
        return [
            'total' => $total,
            'admin' => $admin,
            'createur' => $createur,
            'marque' => $marque,
            'actif' => $actif,
            'inactif' => $inactif,
            'suspendu' => $suspendu,
            'en_attente' => $en_attente
        ];
    }
    public function sendReclamationResponseNotification($email, $nom, $description, $reponse) {
        $mail = new PHPMailer(true);

        try {
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'neylamhamddy@gmail.com';
            $mail->Password = 'aebg mpbl zomq idjn';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('neylamhamddy@gmail.com', 'Crea8Connect');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Réponse à votre réclamation - Crea8Connect';

            $mail->Body = '
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    body {
        margin:0;
        padding:0;
        background:#f4f6f9;
        font-family: Arial, sans-serif;
    }
    .container {
        max-width:600px;
        margin:40px auto;
        background:#ffffff;
        border-radius:12px;
        overflow:hidden;
        box-shadow:0 5px 15px rgba(0,0,0,0.1);
    }
    .header {
        background:linear-gradient(135deg, #9B5DE0, #B771E5);
        color:white;
        text-align:center;
        padding:30px 20px;
    }
    .content {
        padding:30px 20px;
        line-height:1.6;
        color:#333;
    }
    .reclamation {
        background:#f8f9fa;
        border-left:4px solid #9B5DE0;
        padding:15px;
        margin:20px 0;
        border-radius:4px;
    }
    .response {
        background:#e8f4f8;
        border-left:4px solid #28a745;
        padding:15px;
        margin:20px 0;
        border-radius:4px;
    }
    .footer {
        background:#f8f9fa;
        text-align:center;
        padding:20px;
        color:#666;
        font-size:14px;
    }
</style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔔 Réponse à votre réclamation</h1>
        </div>
        <div class="content">
            <p>Bonjour <strong>' . htmlspecialchars($nom) . '</strong>,</p>

            <p>Votre réclamation a reçu une réponse de notre équipe :</p>

            <div class="reclamation">
                <h4>📝 Votre réclamation :</h4>
                <p>' . nl2br(htmlspecialchars($description)) . '</p>
            </div>

            <div class="response">
                <h4>💬 Réponse de l\'équipe :</h4>
                <p>' . nl2br(htmlspecialchars($reponse)) . '</p>
            </div>

            <p>Si vous avez d\'autres questions, n\'hésitez pas à nous contacter.</p>

            <p>Cordialement,<br>
            <strong>L\'équipe Crea8Connect</strong></p>
        </div>
        <div class="footer">
            <p>© 2024 Crea8Connect - Tous droits réservés</p>
        </div>
    </div>
</body>
</html>';

            $mail->send();
            return true;

        } catch (Exception $e) {
            // Log l'erreur pour le debugging
            error_log("Erreur envoi email réclamation: " . $mail->ErrorInfo);
            return false;
        }
    }
    public function supprimerUser($id) {
        $result = $this->softDeleteUserById($id, null, null, 'Deleted from BackOffice user management');
        return (bool)($result['success'] ?? false);
    }


private function getProjectBaseUrl(): string {
    $script = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');

    foreach (['/Vue/FrontOffice/', '/Vue/BackOffice/'] as $marker) {
        $pos = strpos($script, $marker);
        if ($pos !== false) {
            return substr($script, 0, $pos);
        }
    }

    return '';
}

private function appUrl(string $path): string {
    return rtrim($this->getProjectBaseUrl(), '/') . '/' . ltrim($path, '/');
}

private function normalizeRole($role): string {
    $role = strtolower(trim((string) $role));
    $role = str_replace(
        ['é', 'è', 'ê', 'ë', 'à', 'â', 'î', 'ï', 'ô', 'ù', 'û', 'ç'],
        ['e', 'e', 'e', 'e', 'a', 'a', 'i', 'i', 'o', 'u', 'u', 'c'],
        $role
    );

    return match ($role) {
        'brand' => 'marque',
        'creator', 'creatrice' => 'createur',
        'administrator', 'administrateur' => 'admin',
        default => $role,
    };
}

public function login($email, $password) {

    $db = config::getConnexion();

    $email = trim($email);
    $password = trim($password);

    $query = $db->prepare("
        SELECT * 
        FROM utilisateur 
        WHERE email=?
    ");

    $query->execute([$email]);

    $user = $query->fetch();

    if (!$user) {
        return "Utilisateur introuvable";
    }

    if (!empty($user['deleted_at'] ?? null)) {
        return "Compte indisponible";
    }

    // TEST PASSWORD — handle both bcrypt hashes and legacy plain text
    $passwordOk = password_verify($password, $user['mot_de_passe'])
               || $password === $user['mot_de_passe'];

    if (!$passwordOk) {
        return "Mot de passe incorrect";
    }

    // If password was plain text, upgrade it to a hash now
    if ($password === $user['mot_de_passe']) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $db->prepare("UPDATE utilisateur SET mot_de_passe = ? WHERE id = ?")
           ->execute([$hashed, $user['id']]);
    }

    // SESSION
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $normalizedRole = $this->normalizeRole($user['role'] ?? '');
    $user['role'] = $normalizedRole;

    if ($user['statut'] === 'suspendu') {
        unset($_SESSION['connected'], $_SESSION['id'], $_SESSION['nom'], $_SESSION['email'], $_SESSION['role'], $_SESSION['user'], $_SESSION['utilisateur']);
        $_SESSION['suspended_appeal'] = [
            'id' => (int) $user['id'],
            'role' => $normalizedRole,
            'nom' => $user['nom'] ?? '',
            'email' => $user['email'] ?? '',
            'statut' => 'suspendu',
        ];

        session_write_close();
        header('Location: ' . $this->appUrl('Vue/FrontOffice/utilisateur/reclamation.php?appeal=1'));
        exit();
    }

    if ($user['statut'] != 'actif') {
        return "Compte non actif";
    }

    unset($_SESSION['suspended_appeal']);
    $_SESSION['connected'] = true;
    $_SESSION['id'] = $user['id'];
    $_SESSION['nom'] = $user['nom'];
    $_SESSION['email'] = $user['email'] ?? '';
    $_SESSION['role'] = $normalizedRole;
    $_SESSION['user'] = $user;

    // Keep the real utilisateur login as the source of truth and clear old offre demo data.
    unset($_SESSION['utilisateur']);
    $_SESSION['utilisateur'] = [
        'id' => (int) $user['id'],
        'role' => $normalizedRole,
        'nom' => $user['nom'] ?? '',
        'email' => $user['email'] ?? '',
    ];

    session_write_close();

    // REDIRECTION
    if (isBackOfficeRole($normalizedRole)) {
        header('Location: ' . $this->appUrl('Vue/BackOffice/dashboard/index.php'));
    } else {
        header('Location: ' . $this->appUrl('Vue/FrontOffice/utilisateur/creator.php'));
    }

    exit();
}
}
?>
