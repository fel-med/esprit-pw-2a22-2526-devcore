<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../Controleur/reclamationC.php';
require_once __DIR__ . '/../../../Controleur/session_helper.php';

session_start();

$currentUserId = cc_current_reclamation_user_id();
$isSuspendedAppeal = cc_is_suspended_appeal_session();

if ($currentUserId === null) {
    die("Utilisateur non connecte");
}

if (isset($_POST['id'])) {
    $reclamationC = new ReclamationC();
    $rec = $reclamationC->recupererReclamation($_POST['id']);

    if ($rec && (int)$rec['idUtilisateur'] === (int)$currentUserId) {
        $reclamationC->supprimerReclamation($_POST['id']);
    }

    header('Location: reclamation.php' . ($isSuspendedAppeal ? '?appeal=1' : ''));
    exit();
}
