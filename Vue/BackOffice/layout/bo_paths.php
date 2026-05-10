<?php
/**
 * Compute BackOffice asset base URLs.
 * Works whether the entry point is a view file (Vue/BackOffice/...)
 * or a controller (Controleur/...).
 * Include this at the very top of any BackOffice view, before <head>.
 */
if (!isset($backBoRootWeb)) {
    $_boSelf = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');

    // Try /Vue/BackOffice/ marker first (direct view access)
    $_boPos = strpos($_boSelf, '/Vue/BackOffice/');
    if ($_boPos !== false) {
        $backBoRootWeb = substr($_boSelf, 0, $_boPos) . '/Vue/BackOffice';
    } else {
        // Served via controller — strip /Controleur/... to get project root
        $_ctrlPos = strpos($_boSelf, '/Controleur/');
        $projectRoot = $_ctrlPos !== false ? substr($_boSelf, 0, $_ctrlPos) : '';
        $backBoRootWeb = $projectRoot . '/Vue/BackOffice';
    }

    $backBoUtilisateurWeb = $backBoRootWeb . '/utilisateur';
}
