<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../Modele/Reclamation.php';
require_once __DIR__ . '/../../../Controleur/reclamationC.php';
require_once __DIR__ . '/../../../Controleur/session_helper.php';

session_start();

$idUtilisateur = cc_current_reclamation_user_id();
$isSuspendedAppeal = cc_is_suspended_appeal_session();

if ($idUtilisateur === null) {
    header('Location: login.php');
    exit();
}

if (isset($_POST['description']) && ($isSuspendedAppeal || isset($_POST['priorite']))) {
    $description = trim((string) $_POST['description']);
    $priorite = $_POST['priorite'] ?? 'normale';

    if ($isSuspendedAppeal) {
        if (!str_starts_with($description, '[Suspension Appeal]')) {
            $description = '[Suspension Appeal] ' . $description;
        }
        $priorite = 'haute';
    }

    $reclamation = new Reclamation(
        null,
        $idUtilisateur,
        $description,
        null,
        'en_attente',
        $priorite
    );

    $reclamationC = new ReclamationC();
    $reclamationC->ajouterReclamation($reclamation);

    header('Location: reclamation.php' . ($isSuspendedAppeal ? '?appeal=1&success=1' : '?success=1'));
    exit();
}
