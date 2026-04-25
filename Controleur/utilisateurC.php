<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Modele/utilisateur.php';

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
    public function afficherUsers($search = '', $role = '') {
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
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    $_SESSION['id'] = $user['id']; // 🔥 AJOUT IMPORTANT

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