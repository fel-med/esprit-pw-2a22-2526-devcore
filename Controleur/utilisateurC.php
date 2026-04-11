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
    public function afficherUsers() {
    $db = config::getConnexion();
    $sql = "SELECT * FROM utilisateur";
    return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
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

        if ($user['role'] == 'admin')
            header("Location:http://127.0.0.1/crea8connect/Esprit-PW-2A22-2526-Devcore/Vue/BackOffice/utilisateur/index.php");
        else
            header("Location: ../FrontOffice/home.php");

        exit;
    }
}
?>