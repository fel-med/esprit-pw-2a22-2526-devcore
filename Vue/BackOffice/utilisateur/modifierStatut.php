<?php
require_once '../../../Controleur/session_helper.php';
require_once '../../../config.php';
require_once '../../../Controleur/reclamationC.php';

cc_require_admin('../../FrontOffice/utilisateur/login.php');

if (isset($_POST['id']) && isset($_POST['statut'])) {
    $c = new ReclamationC();
    $c->updateStatut($_POST['id'], $_POST['statut']);
}

header("Location: index.php");
