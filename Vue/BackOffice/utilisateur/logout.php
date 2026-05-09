<?php
session_start();

// vider toutes les variables de session
$_SESSION = [];

// détruire la session
session_destroy();

// redirection vers login
header("Location: ../../FrontOffice/utilisateur/login.php");
exit();