<?php
// Controller class for Campagne
// Handles campaign-related operations and connects to the Campagne model

require_once '../Modele/campagne.php';
require_once '../config.php';

class CampagneC {
    private $campagneModel;

    public function __construct() {
        // Instantiate the model
        $this->campagneModel = new Campagne();
    }

    public function afficherPage() {
        // Method to display the campaign page
        // This is a starter method - full logic will be added later
    }

    public function creerObjetExemple() {
        // Example method to create a sample campaign object
        // This demonstrates the connection between controller and model
        $campagne = new Campagne(1, 1, 1, 'Sample Campaign', 'Description', '2023-01-01', '2023-12-31', 'active');
        return $campagne;
    }
}
?>
