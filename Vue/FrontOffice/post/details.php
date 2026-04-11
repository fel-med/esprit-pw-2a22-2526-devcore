<?php
require_once '../../../Controleur/postC.php';

$postC = new PostC();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('Post ID is missing.');
}

$postId = $_GET['id'];
$postC->incrementViews($postId);
$post = $postC->showPost($postId);

if (!$post) {
    die('Post not found.');
}

$pageTitle = $post['subject'];
require_once '../partials/header.php';
?>

<section class="py-5">
    <div class="container px-5">
        <div class="mb-4">
            <a href="./index.php" class="btn btn-outline-dark">
                <i class="bi bi-arrow-left"></i> Back to feed
            </a>
        </div>

        <div class="card post-card border-0">
            <?php if (!empty($post['imageContent'])) : ?>
                <img src="../../public/<?= htmlspecialchars($post['imageContent']) ?>" class="post-media" alt="Post image">
            <?php elseif (!empty($post['VideoContent'])) : ?>
                <video class="post-video" controls>
                    <source src="../../public/<?= htmlspecialchars($post['VideoContent']) ?>">
                </video>
            <?php endif; ?>

            <div class="card-body p-5">
                <div class="post-meta mb-2">
                    Creator #<?= htmlspecialchars($post['idCreateur']) ?> • <?= htmlspecialchars($post['creationDate']) ?>
                </div>

                <h1 class="fw-bolder mb-4"><?= htmlspecialchars($post['subject']) ?></h1>

                <div class="d-flex flex-wrap gap-2 mb-4">
                    <span class="stat-badge"><i class="bi bi-eye"></i> <?= (int)$post['numberOfView'] + 1 ?></span>
                    <span class="stat-badge"><i class="bi bi-hand-thumbs-up"></i> <?= (int)$post['numberOfLike'] ?></span>
                    <span class="stat-badge"><i class="bi bi-hand-thumbs-down"></i> <?= (int)$post['numberOfDislike'] ?></span>
                </div>

                <p class="post-content text-muted fs-5">
                    <?= htmlspecialchars($post['textContent']) ?>
                </p>

                <div class="mt-4">
                    <a href="./like.php?id=<?= urlencode($post['id']) ?>" class="btn btn-outline-success me-2">
                        <i class="bi bi-hand-thumbs-up"></i> Like
                    </a>
                    <a href="./dislike.php?id=<?= urlencode($post['id']) ?>" class="btn btn-outline-danger">
                        <i class="bi bi-hand-thumbs-down"></i> Dislike
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once '../partials/footer.php'; ?>