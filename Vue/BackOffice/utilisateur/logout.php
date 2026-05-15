<?php
session_start();

// vider toutes les variables de session
$_SESSION = [];

// détruire la session
session_destroy();

// redirection vers la page publique
header("Location: ../../FrontOffice/utilisateur/index.php");
exit();