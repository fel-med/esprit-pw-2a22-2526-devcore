<?php
require_once '../../../config.php';
require_once '../../../Controleur/session_helper.php';

session_start();

// ⚠️ IMPORTANT
header('Content-Type: application/json; charset=utf-8');

function cre8_login_project_base(): string
{
    $script = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');

    foreach (['/Vue/FrontOffice/', '/Vue/BackOffice/'] as $marker) {
        $pos = strpos($script, $marker);
        if ($pos !== false) {
            return substr($script, 0, $pos);
        }
    }

    return '';
}

function cre8_login_url(string $path): string
{
    return rtrim(cre8_login_project_base(), '/') . '/' . ltrim($path, '/');
}

function cre8_normalize_login_role($role): string
{
    $role = strtolower(trim((string) $role));
    $role = str_replace(
        ['é', 'è', 'ê', 'ë', 'à', 'â', 'î', 'ï', 'ô', 'ù', 'û', 'ç'],
        ['e', 'e', 'e', 'e', 'a', 'a', 'i', 'i', 'o', 'u', 'u', 'c'],
        $role
    );

    return match ($role) {
        'brand' => 'marque',
        'creator', 'creatrice' => 'createur',
        'administrator', 'administrateur' => 'admin',
        default => $role,
    };
}

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['face']) || !is_array($data['face'])) {
    echo json_encode(["success" => false, "message" => "Données invalides"]);
    exit();
}

$inputFace = $data['face'];

$db = config::getConnexion();

$sql = "SELECT id, nom, email, role, statut, face_descriptor FROM utilisateur WHERE face_descriptor IS NOT NULL AND face_descriptor != ''";
$users = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

function distance($a, $b) {
    if (count($a) !== count($b)) return 999;

    $sum = 0;
    for ($i = 0; $i < count($a); $i++) {
        $diff = $a[$i] - $b[$i];
        $sum += $diff * $diff;
    }
    return sqrt($sum);
}

// 🔍 BEST MATCH
$bestUser = null;
$bestDist = 999;

foreach ($users as $user) {

    if (empty($user['face_descriptor'])) continue;

    $stored = json_decode($user['face_descriptor'], true);
    if (!is_array($stored)) continue;

    $dist = distance($inputFace, $stored);

    if ($dist < $bestDist) {
        $bestDist = $dist;
        $bestUser = $user;
    }
}

// ✅ utilisateur reconnu
if ($bestUser && $bestDist < 0.75) {

    $normalizedRole = cre8_normalize_login_role($bestUser['role'] ?? '');
    $bestUser['role'] = $normalizedRole;

    if ($bestUser['statut'] === 'suspendu') {
        unset($_SESSION['connected'], $_SESSION['id'], $_SESSION['nom'], $_SESSION['email'], $_SESSION['role'], $_SESSION['user'], $_SESSION['utilisateur']);
        $_SESSION['suspended_appeal'] = [
            'id' => (int) $bestUser['id'],
            'role' => $normalizedRole,
            'nom' => $bestUser['nom'] ?? '',
            'email' => $bestUser['email'] ?? '',
            'statut' => 'suspendu',
        ];

        echo json_encode([
            "success" => true,
            "redirect" => cre8_login_url('Vue/FrontOffice/utilisateur/reclamation.php?appeal=1'),
            "distance" => $bestDist
        ]);
        exit();
    }

    if ($bestUser['statut'] !== 'actif') {
        echo json_encode([
            "success" => false,
            "message" => "Compte suspendu"
        ]);
        exit();
    }

    // Real login session: keep the same shape as the normal form login.
    unset($_SESSION['suspended_appeal']);
    $_SESSION['connected'] = true;
    $_SESSION['id'] = (int) $bestUser['id'];
    $_SESSION['role'] = $normalizedRole;
    $_SESSION['nom'] = $bestUser['nom'] ?? '';
    $_SESSION['email'] = $bestUser['email'] ?? '';
    $_SESSION['user'] = $bestUser;

    unset($_SESSION['utilisateur']);
    $_SESSION['utilisateur'] = [
        'id' => (int) $bestUser['id'],
        'role' => $normalizedRole,
        'nom' => $bestUser['nom'] ?? '',
        'email' => $bestUser['email'] ?? '',
    ];

    if (isBackOfficeRole($normalizedRole)) {
        $redirect = cre8_login_url('Vue/BackOffice/dashboard/index.php');
    } else {
        $redirect = cre8_login_url('Vue/FrontOffice/utilisateur/creator.php');
    }

    echo json_encode([
        "success" => true,
        "redirect" => $redirect,
        "distance" => $bestDist
    ]);
    exit();
}

// ❌ non reconnu
$message = "Utilisateur non reconnu";
if ($bestDist === 999) {
    $message = "Aucun visage enregistré pour la connexion faciale";
}
echo json_encode([
    "success" => false,
    "message" => $message,
    "distance" => $bestDist
]);
