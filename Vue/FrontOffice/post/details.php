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
$currentPage = 'actuality';

require_once '../partials/header.php';
?>

<section class="py-5">
    <div class="container px-4 px-lg-5">
        <div class="mb-4">
            <a href="./index.php" class="btn social-nav-btn">
                <i class="bi bi-arrow-left"></i> Back to Actuality
            </a>
        </div>

        <article class="social-post-card">
            <div class="social-post-header">
                <div class="social-post-avatar">
                    <?= htmlspecialchars(substr($post['creatorName'] ?? 'C', 0, 1)) ?>
                </div>
                <div>
                    <div class="social-post-author"><?= htmlspecialchars($post['creatorName'] ?? ('Creator #' . $post['idCreateur'])) ?></div>
                    <div class="social-post-meta"><?= htmlspecialchars($post['creationDate']) ?></div>
                </div>
            </div>

            <div class="social-post-body">
                <h1 class="social-post-title"><?= htmlspecialchars($post['subject']) ?></h1>
                <p class="social-post-text"><?= htmlspecialchars($post['textContent']) ?></p>

                
            </div>

            <?php if (!empty($post['imageContent']) || !empty($post['VideoContent'])) : ?>
                <div class="social-post-media-wrap">
                    <?php if (!empty($post['imageContent'])) : ?>
                        <img src="../../public/<?= htmlspecialchars($post['imageContent']) ?>" alt="Post image" class="social-post-image">
                    <?php endif; ?>

                    <?php if (!empty($post['VideoContent'])) : ?>
                        <video class="social-post-video" controls preload="metadata" playsinline>
                            <source src="../../public/<?= htmlspecialchars($post['VideoContent']) ?>">
                        </video>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="social-post-actions">
    <div class="social-action-top">
        <div class="social-action-left">
            <button
                type="button"
                class="icon-action-btn reaction-btn js-reaction-btn"
                data-post-id="<?= htmlspecialchars($post['id']) ?>"
                data-action="like"
                aria-label="Like post"
            >
                <i class="bi bi-heart"></i>
            </button>

            <button
                type="button"
                class="icon-action-btn reaction-btn js-reaction-btn"
                data-post-id="<?= htmlspecialchars($post['id']) ?>"
                data-action="dislike"
                aria-label="Dislike post"
            >
                <i class="bi bi-hand-thumbs-down"></i>
            </button>
        </div>
    </div>

    <div class="reaction-summary">
        <span><i class="bi bi-heart-fill"></i> <span class="js-like-count"><?= (int)$post['numberOfLike'] ?></span></span>
        <span><i class="bi bi-hand-thumbs-down-fill"></i> <span class="js-dislike-count"><?= (int)$post['numberOfDislike'] ?></span></span>
        <span class="view-meta"><i class="bi bi-eye"></i> <?= (int)$post['numberOfView'] ?> views</span>
    </div>
</div>
        </article>
    </div>
</section>

<?php require_once '../partials/footer.php'; ?>