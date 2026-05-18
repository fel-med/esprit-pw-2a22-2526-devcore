<?php
require_once '../../../config.php';
require_once '../../../Controleur/reclamationC.php';
require_once '../../../Controleur/session_helper.php';

session_start();

$currentUserId = cc_current_reclamation_user_id();
$isSuspendedAppeal = cc_is_suspended_appeal_session();

if ($currentUserId === null) {
    die("Utilisateur non connecte");
}

if (isset($_POST['id'], $_POST['description']) && ($isSuspendedAppeal || isset($_POST['priorite']))) {
    $reclamationC = new ReclamationC();
    $rec = $reclamationC->recupererReclamation($_POST['id']);

    if ($rec && (int)$rec['idUtilisateur'] === (int)$currentUserId) {
        $description = trim((string) $_POST['description']);
        $priorite = $_POST['priorite'] ?? 'normale';

        if ($isSuspendedAppeal) {
            if (!str_starts_with($description, '[Suspension Appeal]')) {
                $description = '[Suspension Appeal] ' . $description;
            }
            $priorite = 'haute';
        }

        $reclamationC->modifierReclamation(
            $_POST['id'],
            $description,
            $priorite
        );
    }

    header('Location: reclamation.php' . ($isSuspendedAppeal ? '?appeal=1' : ''));
    exit();
}
