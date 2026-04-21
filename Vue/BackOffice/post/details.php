<?php
require_once '../../../Controleur/postC.php';
require_once '../../../Controleur/commentC.php';

$postC = new PostC();
$commentC = new CommentC();
$pageTitle = 'Post Details';
$currentPage = 'posts';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('Post ID is missing.');
}

$postId = $_GET['id'];
$post = $postC->showPost($postId);
if (!$post) {
    die('Post not found.');
}

$comments = $commentC->searchCommentsAdmin('postId', $postId);

require_once '../partials/header.php';
?>

<div class="row">
    <div class="col-12 grid-margin stretch-card">
        <div class="card post-detail-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                    <div>
                        <h3 class="mb-1">Post Details</h3>
                        <p class="text-muted mb-0">Review complete content before moderation action.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="./index.php" class="btn btn-outline-light"><i class="mdi mdi-arrow-left"></i> Back</a>
                        <a href="../comment/index.php?searchType=postId&keyword=<?= urlencode($postId) ?>" class="btn btn-info"><i class="mdi mdi-comment-outline"></i> Manage Comments</a>
                        <a href="./delete.php?id=<?= urlencode($post['id']) ?>" class="btn btn-danger js-admin-delete"><i class="mdi mdi-delete-outline"></i> Delete Post</a>
                    </div>
                </div>

                <div class="mb-4">
                    <h2 class="mb-2"><?= htmlspecialchars($post['subject']) ?></h2>
                    <div class="post-creator-badge"><i class="mdi mdi-account-circle"></i> <?= htmlspecialchars($post['creatorName'] ?? ('Creator #' . $post['idCreateur'])) ?></div>
                    <div class="detail-meta-badges">
                        <span class="detail-meta-badge"><i class="mdi mdi-calendar-clock"></i> <?= htmlspecialchars($post['creationDate']) ?></span>
                        <span class="detail-meta-badge"><i class="mdi mdi-eye"></i> <?= (int)$post['numberOfView'] ?> views</span>
                        <span class="detail-meta-badge"><i class="mdi mdi-thumb-up-outline"></i> <?= (int)$post['numberOfLike'] ?> likes</span>
                        <span class="detail-meta-badge"><i class="mdi mdi-thumb-down-outline"></i> <?= (int)$post['numberOfDislike'] ?> dislikes</span>
                    </div>
                </div>

                <div class="mb-4">
                    <h5 class="mb-3">Post content</h5>
                    <p class="text-light" style="white-space: pre-line;"><?= htmlspecialchars($post['textContent']) ?></p>
                </div>

                <?php if (!empty($post['imageContent'])) : ?>
                    <div class="mb-4"><h5 class="mb-3">Image</h5><img src="../../public/<?= htmlspecialchars($post['imageContent']) ?>" alt="Post image" class="post-detail-media"></div>
                <?php endif; ?>
                <?php if (!empty($post['VideoContent'])) : ?>
                    <div class="mb-4"><h5 class="mb-3">Video</h5><video class="post-detail-media" controls preload="metadata" playsinline><source src="../../public/<?= htmlspecialchars($post['VideoContent']) ?>"></video></div>
                <?php endif; ?>

                <hr>
                <h4 class="mb-3">Comments linked to this post</h4>
                <?php if (empty($comments)) : ?>
                    <p class="text-muted mb-0">No comments on this post yet.</p>
                <?php else : ?>
                    <div class="table-responsive">
                        <table class="table table-dark admin-table">
                            <thead>
                                <tr>
                                    <th>Comment ID</th>
                                    <th>Type</th>
                                    <th>User</th>
                                    <th>Text</th>
                                    <th>Likes</th>
                                    <th>Dislikes</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($comments as $comment) : ?>
                                    <tr>
                                        <td><?= htmlspecialchars($comment['id']) ?></td>
                                        <td><?= htmlspecialchars($comment['commentedItem']) ?></td>
                                        <td><?= htmlspecialchars($comment['userName'] ?? ('User #' . $comment['idUser'])) ?></td>
                                        <td><?= htmlspecialchars($comment['text'] ?? '') ?></td>
                                        <td><?= (int)($comment['numberOfLike'] ?? 0) ?></td>
                                        <td><?= (int)($comment['numberOfDislike'] ?? 0) ?></td>
                                        <td>
                                            <a href="../comment/index.php?searchType=commentId&keyword=<?= urlencode($comment['id']) ?>" class="admin-action-btn admin-view-btn"><i class="mdi mdi-source-branch"></i> Replies</a>
                                            <a href="../comment/delete.php?id=<?= urlencode($comment['id']) ?>&redirect=post&postId=<?= urlencode($postId) ?>" class="admin-action-btn admin-delete-btn js-admin-delete"><i class="mdi mdi-delete-outline"></i> Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../partials/footer.php'; ?>
