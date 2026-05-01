<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../Modele/Reclamation.php';
require_once __DIR__ . '/../../../Controleur/reclamationC.php';

session_start();

// Vérification utilisateur connecté
if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit();
}

if (isset($_POST['description']) && isset($_POST['priorite'])) {

    $idUtilisateur = $_SESSION['id'];

    $reclamation = new Reclamation(
        null,
        $idUtilisateur,
        $_POST['description'],
        null,
        'en_attente',
        $_POST['priorite']
    );

    $reclamationC = new ReclamationC();
    $reclamationC->ajouterReclamation($reclamation);

   header('Location: reclamation.php?success=1');
    exit();
}