<?php
require_once __DIR__ . '/../layout/session_bridge.php';

$currentFrontUser = cre8_front_session_user();

if (empty($currentFrontUser['isLoggedIn'])) {
    header('Location: ' . cre8_front_login_url());
    exit;
}

$currentRole = cre8_front_normalize_role($currentFrontUser['role'] ?? '');
$scriptPath = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');
$frontOfficeMarker = '/Vue/FrontOffice/';
$frontOfficePos = strpos($scriptPath, $frontOfficeMarker);
$projectBase = $frontOfficePos !== false ? substr($scriptPath, 0, $frontOfficePos) : '';

if ($currentRole === 'admin') {
    header('Location: ' . $projectBase . '/Vue/BackOffice/dashboard/index.php');
    exit;
}

header('Location: ' . $projectBase . '/Vue/FrontOffice/utilisateur/creator.php');
exit;
