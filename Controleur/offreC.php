<?php
// Controller class for Offre
// Handles offer-related operations and connects to the Offre model

require_once '../Modele/offre.php';
require_once '../config.php';

class OffreC {
    private $offreModel;

    public function __construct() {
        // Instantiate the model
        $this->offreModel = new Offre();
    }

    public function afficherPage() {
        // Method to display the offer page
        // This is a starter method - full logic will be added later
    }

    public function creerObjetExemple() {
        // Example method to create a sample offer object
        // This demonstrates the connection between controller and model
        $offre = new Offre(1, 1, 'Sample Offer', 'Description', 'Objective', 1000, 5000, '2023-01-01', '2023-12-31', 'active');
        return $offre;
    }
}
?>