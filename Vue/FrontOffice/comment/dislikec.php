<?php
require_once '../../../Controleur/session_helper.php';
cc_start_session();
require_once '../../../Controleur/commentC.php';

header('Content-Type: application/json');
cc_require_login('../utilisateur/login.php');

$id = trim($_POST['id'] ?? '');
if ($id === '') {
    echo json_encode(['success' => false, 'message' => 'Missing comment ID']);
    exit;
}

$commentC = new CommentC();
$success = $commentC->incrementDislike($id);
$count = $commentC->getDislikeCount($id);

echo json_encode(['success' => $success, 'count' => $count]);
