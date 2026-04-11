<?php
// Controller class for Utilisateur
// Handles user-related operations and connects to the Utilisateur model

require_once __DIR__ . '/../Modele/utilisateur.php';
require_once __DIR__ . '/../config.php';

class UtilisateurC {
    private $utilisateurModel;

    public function __construct() {
        // Instantiate the model
        $this->utilisateurModel = new Utilisateur();
    }

    public function afficherPage() {
        // Method to display the user page
        // This is a starter method - full logic will be added later
    }

    public function creerObjetExemple() {
        // Example method to create a sample user object
        // This demonstrates the connection between controller and model
        $user = new Utilisateur(1, 'John Doe', 'john@example.com', 'password123', 'user', 'active', 0, '2023-01-01');
        return $user;
    }

    public function getUserByIdAndRole($id, $role) {
        $pdo = config::getConnexion();
        $sql = 'SELECT * FROM utilisateur WHERE id = :id AND role = :role AND statut != :statutBloque';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id, 'role' => $role, 'statutBloque' => 'bloque']);
        $row = $stmt->fetch();
        if ($row) {
            return new Utilisateur($row['id'], $row['nom'], $row['email'], '', $row['role'], $row['statut'], $row['tentatives_login'], $row['date_creation']);
        }
        return null;
    }

    public function authenticate($email, $password) {
        $pdo = config::getConnexion();
        $sql = 'SELECT * FROM utilisateur WHERE email = :email AND statut = :statut';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['email' => $email, 'statut' => 'actif']);
        $row = $stmt->fetch();
        if ($row && password_verify($password, $row['mot_de_passe'])) {
            return new Utilisateur($row['id'], $row['nom'], $row['email'], '', $row['role'], $row['statut'], $row['tentatives_login'], $row['date_creation']);
        }
        return null;
    }
}
?>