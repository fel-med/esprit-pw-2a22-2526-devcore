<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../Controleur/reclamationC.php';
require_once __DIR__ . '/../../../Controleur/session_helper.php';

session_start();

// 🔐 vérifier login
if (!isset($_SESSION['id'])) {
    die("Utilisateur non connecté");
}

// 🔐 vérifier rôle admin
if (!isBackOfficeRole(cc_current_user_role())) {
    die("Accès refusé");
}

if (isset($_POST['id'])) {

    $reclamationC = new ReclamationC();
    $reclamationC->supprimerReclamation($_POST['id']);

    header("Location: reclamations.php");
    exit();
}
