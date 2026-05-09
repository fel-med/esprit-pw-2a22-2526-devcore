<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// This old offre login selector was only for development/demo accounts.
// Production FrontOffice authentication must use the real utilisateur login.
unset($_SESSION['utilisateur']);

$script = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');
$marker = '/Vue/FrontOffice/';
$pos = strpos($script, $marker);
$base = $pos !== false ? substr($script, 0, $pos) : '';

header('Location: ' . $base . '/Vue/FrontOffice/utilisateur/login.php');
exit;
