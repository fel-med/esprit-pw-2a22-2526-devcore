<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../Modele/Reclamation.php';
require_once __DIR__ . '/../../../Controleur/reclamationC.php';
require_once __DIR__ . '/../../../Controleur/session_helper.php';

session_start();

$idUtilisateur = cc_current_reclamation_user_id();
$isSuspendedAppeal = cc_is_suspended_appeal_session();
$appealReason = function_exists('cc_account_appeal_reason') ? cc_account_appeal_reason() : ($isSuspendedAppeal ? 'account_suspended' : '');
$currentReclamationRole = cc_current_reclamation_user_role();

if ($idUtilisateur === null) {
    header('Location: login.php');
    exit();
}

if (!$isSuspendedAppeal && cc_is_backoffice_role($currentReclamationRole ?? '')) {
    header('Location: reclamation.php?admin_backoffice=1');
    exit();
}

if (isset($_POST['description']) && ($isSuspendedAppeal || isset($_POST['priorite']))) {
    $description = trim((string) $_POST['description']);
    $priorite = $_POST['priorite'] ?? 'normale';

    if ($isSuspendedAppeal) {
        $prefix = $appealReason === 'account_deleted'
            ? '[Account appeal - deleted]'
            : '[Account appeal - suspended]';
        if (!str_starts_with($description, $prefix)) {
            $description = $prefix . ' ' . $description;
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
    $createdId = $reclamationC->ajouterReclamation($reclamation, $isSuspendedAppeal);
    if ($createdId === false) {
        header('Location: reclamation.php' . ($isSuspendedAppeal ? '?appeal=1&error=1' : '?error=1'));
        exit();
    }

    header('Location: reclamation.php' . ($isSuspendedAppeal ? '?appeal=1&success=1' : '?success=1'));
    exit();
}
