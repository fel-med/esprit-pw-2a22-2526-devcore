<?php
require_once '../../../Controleur/postC.php';
require_once '../../../Controleur/commentC.php';
require_once '../comment/_render.php';

$postC    = new PostC();
$commentC = new CommentC();
$idUser   = 1;

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('Post ID is missing.');
}

$postId = $_GET['id'];
$postC->incrementViews($postId);
$post = $postC->showPost($postId);
if (!$post) {
    die('Post not found.');
}

$commentsTree = $commentC->getCommentsTreeByPost($postId);
$commentCount = $commentC->countCommentsByPost($postId);

$pageTitle   = $post['subject'];
$currentPage = 'actuality';

require_once '../partials/header.php';
?>
<link rel="stylesheet" href="../assets/comment-front.css">

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
                        <img src="../../public/<?= htmlspecialchars($post['imageContent']) ?>"
                             alt="Post image" class="social-post-image">
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
                        <button type="button" class="icon-action-btn reaction-btn js-reaction-btn"
                                data-post-id="<?= htmlspecialchars($post['id']) ?>"
                                data-action="like" aria-label="Like post">
                            <i class="bi bi-heart"></i>
                        </button>
                        <button type="button" class="icon-action-btn reaction-btn js-reaction-btn"
                                data-post-id="<?= htmlspecialchars($post['id']) ?>"
                                data-action="dislike" aria-label="Dislike post">
                            <i class="bi bi-hand-thumbs-down"></i>
                        </button>
                    </div>
                </div>
                <div class="reaction-summary js-post-comments-scope" data-post-id="<?= htmlspecialchars($post['id']) ?>" data-context="details">
                    <span><i class="bi bi-heart-fill"></i> <span class="js-like-count"><?= (int)$post['numberOfLike'] ?></span></span>
                    <span><i class="bi bi-hand-thumbs-down-fill"></i> <span class="js-dislike-count"><?= (int)$post['numberOfDislike'] ?></span></span>
                    <span class="view-meta"><i class="bi bi-eye"></i> <span class="js-view-count"><?= (int)$post['numberOfView'] ?></span> views</span>
                    <span><i class="bi bi-chat"></i> <span class="js-comment-count"><?= $commentCount ?></span> comment<?= $commentCount !== 1 ? 's' : '' ?></span>
                </div>
            </div>
        </article>

        <div id="comments" class="comments-section-full js-post-comments-scope" data-post-id="<?= htmlspecialchars($postId) ?>" data-context="details">
            <h3 class="comments-section-title">
                <i class="bi bi-chat-dots"></i>
                Comments <span class="comment-count-badge js-comment-count"><?= $commentCount ?></span>
            </h3>

            <div class="comment-form-wrap mb-4">
                <div class="comment-avatar-sm">U</div>
                <div class="comment-input-area">
                    <?php render_comment_form($postId, $postId, 'post', 'details', 'Write a comment...', 'Post'); ?>
                </div>
            </div>

            <div class="comments-list js-comments-list">
                <?php if (empty($commentsTree)) : ?>
                    <div class="no-comments-msg">
                        <i class="bi bi-chat-square" style="font-size:2rem;opacity:.4;"></i>
                        <p class="mt-2 mb-0">No comments yet. Be the first to comment!</p>
                    </div>
                <?php else : ?>
                    <?php foreach ($commentsTree as $commentNode) : ?>
                        <?php render_comment_tree_node($commentNode, $postId, 'details', $idUser); ?>
                        <hr class="comment-separator">
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script src="../assets/comment-front.js"></script>
<?php require_once '../partials/footer.php'; ?>
