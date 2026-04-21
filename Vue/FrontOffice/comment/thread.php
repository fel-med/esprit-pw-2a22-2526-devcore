<?php
require_once '../../../Controleur/commentC.php';
require_once './_render.php';

header('Content-Type: application/json');

$postId = trim($_GET['postId'] ?? '');
$context = trim($_GET['context'] ?? 'index');
$idUser = 1;

if ($postId === '') {
    echo json_encode(['success' => false, 'message' => 'Missing post ID']);
    exit;
}

$commentC = new CommentC();
$previewComments = $commentC->listLatestCommentsByPost($postId, 2);
$allCommentsTree = $commentC->getCommentsTreeByPost($postId);
$commentCount = $commentC->countCommentsByPost($postId);

ob_start();
render_preview_comments($previewComments);
$previewHtml = ob_get_clean();

ob_start();
if (empty($allCommentsTree)) {
    ?>
    <div class="no-comments-msg">
        <i class="bi bi-chat-square" style="font-size:2rem;opacity:.4;"></i>
        <p class="mt-2 mb-0">No comments yet. Be the first to comment!</p>
    </div>
    <?php
} else {
    foreach ($allCommentsTree as $commentNode) {
        render_comment_tree_node($commentNode, $postId, $context, $idUser);
        echo '<hr class="comment-separator">';
    }
}
$listHtml = ob_get_clean();

echo json_encode([
    'success' => true,
    'postId' => $postId,
    'count' => $commentCount,
    'previewHtml' => $previewHtml,
    'listHtml' => $listHtml,
]);
