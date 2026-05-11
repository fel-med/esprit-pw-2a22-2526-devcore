<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Controleur/forumC.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$ctrl = new ForumC();
$created = $ctrl->creerForumsAuto();
echo "✅ $created forum(s) créé(s) automatiquement.\n";
?>