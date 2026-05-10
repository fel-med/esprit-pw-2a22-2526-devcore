<?php
require_once '../../../config.php';
require_once '../../../Controleur/reclamationC.php';

session_start();

if (!isset($_SESSION['id'])) {
    die("Utilisateur non connecté");
}

if (isset($_POST['id'], $_POST['description'], $_POST['priorite'])) {

    $reclamationC = new ReclamationC();

    // 🔐 sécurité (optionnelle si admin)
    $rec = $reclamationC->recupererReclamation($_POST['id']);
    if ($rec && $rec['idUtilisateur'] == $_SESSION['id']) {

        $reclamationC->modifierReclamation(
            $_POST['id'],
            $_POST['description'],
            $_POST['priorite']
        );
    }

    header("Location: reclamation.php");
    exit();
}