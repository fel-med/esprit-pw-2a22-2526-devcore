<?php
// Controller class for Utilisateur
// Handles user-related operations and connects to the Utilisateur model

require_once '../Modele/utilisateur.php';
require_once '../config.php';

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
}
?>