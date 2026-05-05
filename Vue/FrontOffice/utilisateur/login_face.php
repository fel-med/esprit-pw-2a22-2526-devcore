<?php
require_once '../../../config.php';

session_start();

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['face']) || !is_array($data['face'])) {
    echo json_encode(["success" => false]);
    exit();
}

$inputFace = $data['face'];

$db = config::getConnexion();

$sql = "SELECT id, role, statut, face_descriptor FROM utilisateur";
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

foreach ($users as $user) {

    if (empty($user['face_descriptor'])) continue;

    $stored = json_decode($user['face_descriptor'], true);

    if (!is_array($stored)) continue;

    $dist = distance($inputFace, $stored);

    if ($dist < 0.6) {

        // 🔍 Vérifier le statut du compte
        if ($user['statut'] !== 'actif') {
            echo json_encode([
                "success" => false,
                "message" => "⚠️ Votre compte est suspendu. Contactez l'administrateur."
            ]);
            exit();
        }

        $_SESSION['user'] = $user['id'];
        $_SESSION['role'] = $user['role'];

        switch ($user['role']) {
            case 'admin':
                $redirect = "http://127.0.0.1/crea8connect/Esprit-PW-2A22-2526-Devcore/Vue/BackOffice/utilisateur/index.php";
                break;

            case 'createur':
                $redirect = "../utilisateur/creator.php";
                break;

            case 'marque':
                $redirect = "../utilisateur/brand.php";
                break;

            default:
                $redirect = "../utilisateur/creator.php";
        }

        echo json_encode([
            "success" => true,
            "redirect" => $redirect
        ]);
        exit();
    }
}

echo json_encode(["success" => false]);