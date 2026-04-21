<?php
require_once '../../../Controleur/postC.php';

header('Content-Type: application/json');

$id = trim($_POST['id'] ?? $_GET['id'] ?? '');
if ($id === '') {
    echo json_encode(['success' => false, 'message' => 'Missing post ID']);
    exit;
}

$postC = new PostC();
$success = $postC->incrementViews($id);
$post = $postC->showPost($id);
$count = (int)($post['numberOfView'] ?? 0);

echo json_encode(['success' => $success, 'count' => $count]);
