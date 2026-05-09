<?php
require_once __DIR__ . '/../layout/session_bridge.php';
$currentUser = cre8_front_require_user('marque');

require_once __DIR__ . '/../../../Controleur/offreC.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: brand_index.php');
    exit;
}

$controller = new OffreC();
$brandId = (int) $currentUser['id'];
$idOffre = isset($_POST['idOffre']) && is_numeric($_POST['idOffre']) ? intval($_POST['idOffre']) : null;

if ($idOffre !== null && $controller->deleteOffre($idOffre, $brandId)) {
    header('Location: brand_index.php?message=' . urlencode('Offer deleted successfully.'));
    exit;
}

$redirect = 'brand_index.php';
header('Location: ' . $redirect . '&message=' . urlencode('Unable to delete this offer.'));
exit;
