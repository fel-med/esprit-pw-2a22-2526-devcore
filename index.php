<?php
// Cre8Connect project entry point.
// Guests go to the public PHP landing page.
// Logged-in users continue to the existing role-based home flow.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/Controleur/session_helper.php';

function cre8_root_normalize_role($role)
{
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

$sessionUser = isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : [];
$frontUser = isset($_SESSION['utilisateur']) && is_array($_SESSION['utilisateur']) ? $_SESSION['utilisateur'] : [];

$userId = $sessionUser['id']
    ?? $frontUser['id']
    ?? $_SESSION['id']
    ?? $_SESSION['user_id']
    ?? null;

$role = $sessionUser['role']
    ?? $frontUser['role']
    ?? $_SESSION['role']
    ?? '';

$isLoggedIn = !empty($_SESSION['connected'])
    || !empty($sessionUser)
    || !empty($frontUser)
    || !empty($userId);

if (!$isLoggedIn) {
    header('Location: Vue/FrontOffice/utilisateur/index.php');
    exit;
}

$role = cre8_root_normalize_role($role);

if (isBackOfficeRole($role)) {
    header('Location: Vue/BackOffice/dashboard/index.php');
    exit;
}

header('Location: Vue/FrontOffice/utilisateur/creator.php');
exit;
