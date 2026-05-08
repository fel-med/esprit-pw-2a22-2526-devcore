<?php
require_once '../../../config.php';
require_once '../../../Controleur/reclamationC.php';

if (isset($_POST['id']) && isset($_POST['statut'])) {
    $c = new ReclamationC();
    $c->updateStatut($_POST['id'], $_POST['statut']);
}

header("Location: index.php");