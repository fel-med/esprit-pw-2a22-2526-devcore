<?php
require_once '../../../config.php';

session_start();

// ⚠️ IMPORTANT
header('Content-Type: application/json; charset=utf-8');

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['face']) || !is_array($data['face'])) {
    echo json_encode(["success" => false, "message" => "Données invalides"]);
    exit();
}

$inputFace = $data['face'];

$db = config::getConnexion();

$sql = "SELECT id, role, statut, nom, face_descriptor FROM utilisateur WHERE face_descriptor IS NOT NULL AND face_descriptor != ''";
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

    if ($bestUser['statut'] !== 'actif') {
        echo json_encode([
            "success" => false,
            "message" => "Compte suspendu"
        ]);
        exit();
    }

    // 🔥 CRITIQUE
    $_SESSION['connected'] = true;
    $_SESSION['id'] = $bestUser['id'];
    $_SESSION['role'] = $bestUser['role'];
    $_SESSION['nom'] = $bestUser['nom'];
    $_SESSION['user'] = $bestUser;

    // 🔥 chemin ABSOLU (important)
    switch ($bestUser['role']) {
        case 'admin':
            $redirect = "/crea8connect/Esprit-PW-2A22-2526-Devcore/Vue/BackOffice/utilisateur/index.php";
            break;

        case 'createur':
            $redirect = "/crea8connect/Esprit-PW-2A22-2526-Devcore/Vue/FrontOffice/utilisateur/creator.php";
            break;

        case 'marque':
            $redirect = "/crea8connect/Esprit-PW-2A22-2526-Devcore/Vue/FrontOffice/utilisateur/brand.php";
            break;

        default:
            $redirect = "/crea8connect/Esprit-PW-2A22-2526-Devcore/Vue/FrontOffice/utilisateur/creator.php";
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