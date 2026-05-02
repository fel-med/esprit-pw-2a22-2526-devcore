<?php
require_once 'config.php';
require_once 'Controleur/forumC.php';

$ctrl = new ForumControleur();
$ctrl->creerForumsAuto();

echo "Forums créés !";
?>