<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Modele/utilisateur.php';
require_once __DIR__ . '/session_helper.php';
require_once __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;   
class UtilisateurC {

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
    $stmt = $db->prepare("
        SELECT id, nom, email, role, statut, suspended_by, suspended_by_role, suspended_at, suspension_reason
        FROM utilisateur
        WHERE role IN ($placeholders)
        ORDER BY role DESC, id DESC
    ");
    $stmt->execute($roles);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function getUserById($id) {
    $db = config::getConnexion();
    $stmt = $db->prepare("
        SELECT id, nom, email, role, statut, suspended_by, suspended_by_role, suspended_at, suspension_reason
        FROM utilisateur
        WHERE id = ?
    ");
    $stmt->execute([(int)$id]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

public function getUserByEmail($email) {
    $db = config::getConnexion();
    $stmt = $db->prepare("
        SELECT id, nom, email, role, statut, suspended_by, suspended_by_role, suspended_at, suspension_reason
        FROM utilisateur
        WHERE email = ?
        LIMIT 1
    ");
    $stmt->execute([trim((string)$email)]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
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

public function deleteUserById($id) {
    $db = config::getConnexion();
    $stmt = $db->prepare("DELETE FROM utilisateur WHERE id = ?");
    $stmt->execute([(int)$id]);
}

    public function afficherUsers($search = '', $role = '', $page = 1, $limit = 10) {
    $db = config::getConnexion();
    $sql = "SELECT * FROM utilisateur WHERE 1=1";
    
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

    $query = $db->prepare("SELECT * FROM utilisateur WHERE email=?");
    $query->execute([$email]);
    $user = $query->fetch();

    if (!$user) return "Email introuvable";

    // 🔐 Token sécurisé
    $token = bin2hex(random_bytes(50));
    $expire = date("Y-m-d H:i:s", strtotime("+15 minutes"));

    // Sauvegarde en DB
    $sql = "UPDATE utilisateur SET reset_token=?, reset_expire=? WHERE email=?";
    $db->prepare($sql)->execute([$token, $expire, $email]);

   $link = "http://localhost/crea8connect/Esprit-PW-2A22-2526-Devcore/Vue/FrontOffice/utilisateur/reset_password.php?token=" . $token;

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
            WHERE reset_token=? AND reset_expire > NOW()";
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
        $total = $db->query("SELECT COUNT(*) as total FROM utilisateur")->fetch()['total'];
        
        // Par rôle
        $admin = $db->query("SELECT COUNT(*) as count FROM utilisateur WHERE role IN ('admin', 'super_admin', 'hyper_admin')")->fetch()['count'];
        $createur = $db->query("SELECT COUNT(*) as count FROM utilisateur WHERE role='createur'")->fetch()['count'];
        $marque = $db->query("SELECT COUNT(*) as count FROM utilisateur WHERE role='marque'")->fetch()['count'];
        
        // Par statut - Inclure les NULL (traiter comme 'actif' par défaut)
        $actif = $db->query("SELECT COUNT(*) as count FROM utilisateur WHERE statut='actif' OR statut IS NULL")->fetch()['count'];
        $inactif = $db->query("SELECT COUNT(*) as count FROM utilisateur WHERE statut='inactif'")->fetch()['count'];
        $suspendu = $db->query("SELECT COUNT(*) as count FROM utilisateur WHERE statut='suspendu'")->fetch()['count'];
        $en_attente = $db->query("SELECT COUNT(*) as count FROM utilisateur WHERE statut='en_attente'")->fetch()['count'];
        
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
        $db = config::getConnexion();
        $db->prepare("DELETE FROM utilisateur WHERE id=?")->execute([$id]);
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
