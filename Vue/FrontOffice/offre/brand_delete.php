<?php
session_start();
// Simulate logged-in user (remove this when login system is ready)
if (!isset($_SESSION['utilisateur'])) {
    $_SESSION['utilisateur']['id'] = 1;  // Test with marque ID 1
}

require_once __DIR__ . '/../../../Controleur/offreC.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: brand_index.php');
    exit;
}

$controller = new OffreC();
$brandId = $_SESSION['utilisateur']['id'];
$idOffre = isset($_POST['idOffre']) && is_numeric($_POST['idOffre']) ? intval($_POST['idOffre']) : null;

if ($idOffre !== null && $controller->deleteOffre($idOffre, $brandId)) {
    header('Location: brand_index.php?message=' . urlencode('Offer deleted successfully.'));
    exit;
}

$redirect = 'brand_index.php';
header('Location: ' . $redirect . '&message=' . urlencode('Unable to delete this offer.'));
exit;
