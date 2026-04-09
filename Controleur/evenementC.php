<?php
// Controller class for Evenement
// Handles event-related operations and connects to the Evenement model

require_once '../Modele/evenement.php';
require_once '../config.php';

class EvenementC {
    private $evenementModel;

    public function __construct() {
        // Instantiate the model
        $this->evenementModel = new Evenement();
    }

    public function afficherPage() {
        // Method to display the event page
        // This is a starter method - full logic will be added later
    }

    public function creerObjetExemple() {
        // Example method to create a sample event object
        // This demonstrates the connection between controller and model
        $evenement = new Evenement(1, 'Sample Event', 'Description', 2, '2023-06-01');
        return $evenement;
    }
}
?>