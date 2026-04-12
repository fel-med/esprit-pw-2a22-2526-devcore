<?php
require_once '../../../Controleur/postC.php';

$postC = new PostC();
$pageTitle = 'Post Details';
$currentPage = 'posts';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('Post ID is missing.');
}

$post = $postC->showPost($_GET['id']);

if (!$post) {
    die('Post not found.');
}

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
                        <a href="./index.php" class="btn btn-outline-light">
                            <i class="mdi mdi-arrow-left"></i> Back
                        </a>

                        <a href="./delete.php?id=<?= urlencode($post['id']) ?>"
                           class="btn btn-danger js-admin-delete">
                            <i class="mdi mdi-delete-outline"></i> Delete Post
                        </a>
                    </div>
                </div>

                <div class="mb-4">
                    <h2 class="mb-2"><?= htmlspecialchars($post['subject']) ?></h2>
                    <div class="post-creator-badge">
                        <i class="mdi mdi-account-circle"></i>
                        <?= htmlspecialchars($post['creatorName'] ?? ('Creator #' . $post['idCreateur'])) ?>
                    </div>

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
                    <div class="mb-4">
                        <h5 class="mb-3">Image</h5>
                        <img
                            src="../../public/<?= htmlspecialchars($post['imageContent']) ?>"
                            alt="Post image"
                            class="post-detail-media"
                        >
                    </div>
                <?php endif; ?>

                <?php if (!empty($post['VideoContent'])) : ?>
                    <div class="mb-4">
                        <h5 class="mb-3">Video</h5>
                        <video class="post-detail-media" controls preload="metadata" playsinline>
                            <source src="../../public/<?= htmlspecialchars($post['VideoContent']) ?>">
                        </video>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<?php require_once '../partials/footer.php'; ?>