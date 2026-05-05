<?php
require_once '../../../Controleur/utilisateurC.php';
require_once '../../../config.php';

session_start();

// Vérifier que c'est un admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(["success" => false, "message" => "Accès refusé"]);
    exit();
}

if (!isset($_GET['id']) || !isset($_GET['newStatus'])) {
    header('Content-Type: application/json');
    echo json_encode(["success" => false, "message" => "Paramètres manquants"]);
    exit();
}

$id = intval($_GET['id']);
$newStatus = $_GET['newStatus'];

// Valider le statut
if (!in_array($newStatus, ['actif', 'inactif', 'suspendu'])) {
    header('Content-Type: application/json');
    echo json_encode(["success" => false, "message" => "Statut invalide"]);
    exit();
}

$db = config::getConnexion();

try {
    $sql = "UPDATE utilisateur SET statut = :statut WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':statut' => $newStatus,
        ':id' => $id
    ]);

    header('Content-Type: application/json');
    echo json_encode(["success" => true, "message" => "Statut mis à jour"]);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(["success" => false, "message" => "Erreur: " . $e->getMessage()]);
}