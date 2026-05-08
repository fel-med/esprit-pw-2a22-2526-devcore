<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../Controleur/reclamationC.php';

session_start();

if (!isset($_SESSION['id'])) {
    die("Utilisateur non connecté");
}

if (isset($_POST['id'])) {

    $reclamationC = new ReclamationC();
    $rec = $reclamationC->recupererReclamation($_POST['id']);

    // 🔐 sécurité : vérifier que c'est son propre ticket
    if ($rec && $rec['idUtilisateur'] == $_SESSION['id']) {
        $reclamationC->supprimerReclamation($_POST['id']);
    }

    header("Location: reclamation.php");
    exit();
}