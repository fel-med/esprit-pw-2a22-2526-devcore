<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Modele/utilisateur.php';
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

        $sql = "INSERT INTO utilisateur (nom,email,mot_de_passe,role,statut,tentatives_login)
                VALUES (?,?,?,?,?,?)";

        $query = $db->prepare($sql);
        $query->execute([
            $user->getNom(),
            $user->getEmail(),
            password_hash($user->getMotDePasse(), PASSWORD_DEFAULT),
            $user->getRole(),
            "actif",
            0
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
        $admin = $db->query("SELECT COUNT(*) as count FROM utilisateur WHERE role='admin'")->fetch()['count'];
        $createur = $db->query("SELECT COUNT(*) as count FROM utilisateur WHERE role='createur'")->fetch()['count'];
        $marque = $db->query("SELECT COUNT(*) as count FROM utilisateur WHERE role='marque'")->fetch()['count'];
        
        // Par statut
        $actif = $db->query("SELECT COUNT(*) as count FROM utilisateur WHERE statut='actif'")->fetch()['count'];
        $inactif = $db->query("SELECT COUNT(*) as count FROM utilisateur WHERE statut='inactif'")->fetch()['count'];
        
        return [
            'total' => $total,
            'admin' => $admin,
            'createur' => $createur,
            'marque' => $marque,
            'actif' => $actif,
            'inactif' => $inactif
        ];
    }

    public function supprimerUser($id) {
        $db = config::getConnexion();
        $db->prepare("DELETE FROM utilisateur WHERE id=?")->execute([$id]);
    }

    public function login($email, $password) {
    $db = config::getConnexion();

    $query = $db->prepare("SELECT * FROM utilisateur WHERE email=?");
    $query->execute([$email]);
    $user = $query->fetch();

    if (!$user) return "Utilisateur introuvable";

    if (!password_verify($password, $user['mot_de_passe'])) return "Mot de passe incorrect";

    if ($user['statut'] != 'actif') return "Compte non actif";

    session_start();

    $_SESSION['user'] = $user;
    $_SESSION['role'] = $user['role'];
    $_SESSION['id'] = $user['id']; 

    if ($user['role'] == 'admin')
        header("Location:http://127.0.0.1/crea8connect/Esprit-PW-2A22-2526-Devcore/Vue/BackOffice/utilisateur/index.php");
    else if ($user['role'] == 'createur')
        header("Location: ../utilisateur/creator.php");
    else 
        header("Location: ../utilisateur/brand.php");

    exit;
}
}
?>