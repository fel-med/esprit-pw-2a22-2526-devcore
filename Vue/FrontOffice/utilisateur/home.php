<?php
// Legacy compatibility entry for old/restored links to utilisateur/home.php.
// Do not render the old minimal page; send logged-in users to the real hub.

$bridgePath = __DIR__ . '/../layout/session_bridge.php';
if (file_exists($bridgePath)) {
    require_once $bridgePath;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$scriptPath = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');
$frontOfficeMarker = '/Vue/FrontOffice/';
$frontOfficePos = strpos($scriptPath, $frontOfficeMarker);
$projectBase = $frontOfficePos !== false ? substr($scriptPath, 0, $frontOfficePos) : '';

function cre8_home_normalize_role_compat($role)
{
    if (function_exists('cre8_front_normalize_role')) {
        return cre8_front_normalize_role($role);
    }

    $role = strtolower(trim((string) $role));
    $role = str_replace(
        ['é', 'è', 'ê', 'ë', 'à', 'â', 'î', 'ï', 'ô', 'ù', 'û', 'ç'],
        ['e', 'e', 'e', 'e', 'a', 'a', 'i', 'i', 'o', 'u', 'u', 'c'],
        $role
    );

    if (in_array($role, ['brand', 'brands', 'marque'], true)) {
        return 'marque';
    }

    if (in_array($role, ['creator', 'creators', 'createur', 'creatrice'], true)) {
        return 'createur';
    }

    if (in_array($role, ['admin', 'administrator', 'administrateur'], true)) {
        return 'admin';
    }

    return $role;
}

$currentFrontUser = function_exists('cre8_front_session_user')
    ? cre8_front_session_user()
    : [];

$sessionUser = isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : [];
$frontUser = isset($_SESSION['utilisateur']) && is_array($_SESSION['utilisateur']) ? $_SESSION['utilisateur'] : [];

$userId = $currentFrontUser['id']
    ?? $sessionUser['id']
    ?? $frontUser['id']
    ?? $_SESSION['id']
    ?? $_SESSION['user_id']
    ?? null;

$role = $currentFrontUser['role']
    ?? $sessionUser['role']
    ?? $frontUser['role']
    ?? $_SESSION['role']
    ?? '';

$isLoggedIn = !empty($currentFrontUser['isLoggedIn'])
    || !empty($_SESSION['connected'])
    || !empty($sessionUser)
    || !empty($frontUser)
    || !empty($userId);

if (!$isLoggedIn) {
    $loginUrl = function_exists('cre8_front_login_url')
        ? cre8_front_login_url()
        : $projectBase . '/Vue/FrontOffice/utilisateur/login.php';

    header('Location: ' . $loginUrl);
    exit;
}

$role = cre8_home_normalize_role_compat($role);

// Keep admin users in BackOffice dashboard.
if ($role === 'admin') {
    header('Location: ' . $projectBase . '/Vue/BackOffice/dashboard/index.php');
    exit;
}

// Creators and brands use the shared FrontOffice hub.
header('Location: ' . $projectBase . '/Vue/FrontOffice/utilisateur/creator.php');
exit;
