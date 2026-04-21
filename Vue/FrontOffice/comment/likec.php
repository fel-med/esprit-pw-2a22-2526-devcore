<?php
require_once '../../../Controleur/commentC.php';

header('Content-Type: application/json');

$id = trim($_POST['id'] ?? '');
if (empty($id)) {
    echo json_encode(['success' => false, 'message' => 'Missing comment ID']);
    exit;
}

$commentC = new CommentC();
$success  = $commentC->incrementLike($id);
$count    = $commentC->getLikeCount($id);

echo json_encode(['success' => $success, 'count' => $count]);