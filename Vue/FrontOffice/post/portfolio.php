<?php
require_once '../../../Controleur/postC.php';
require_once '../../../Controleur/commentC.php';
require_once '../comment/_render.php';

$postC = new PostC();
$commentC = new CommentC();
$creatorId = 1;
$pageTitle = 'My Space';
$currentPage = 'portfolio';
$currentUserId = 1;

$posts = $postC->listPostsByCreator($creatorId);
$stats = $postC->getCreatorStats($creatorId);

$creatorDisplayName = 'Creator #1';
if (!empty($posts) && !empty($posts[0]['creatorName'])) {
    $creatorDisplayName = $posts[0]['creatorName'];
}

require_once '../partials/header.php';
?>
<link rel="stylesheet" href="../assets/comment-front.css">

<header class="portfolio-hero">
    <div class="container px-4 px-lg-5">
        <div class="portfolio-cover">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="creator-avatar">
                        <i class="bi bi-person-fill"></i>
                    </div>
                    <div>
                        <div class="creator-name"><?= htmlspecialchars($creatorDisplayName) ?></div>
                        <div class="creator-handle">@myspace_creator</div>
                        <div class="creator-stats">
                            <div class="creator-stat">
                                <strong><?= (int)$stats['totalPosts'] ?></strong>
                                <span>Posts</span>
                            </div>
                            <div class="creator-stat">
                                <strong><?= (int)$stats['totalLikes'] ?></strong>
                                <span>Likes</span>
                            </div>
                            <div class="creator-stat">
                                <strong><?= (int)$stats['totalDislikes'] ?></strong>
                                <span>Dislikes</span>
                            </div>
                            <div class="creator-stat">
                                <strong><?= (int)$stats['totalViews'] ?></strong>
                                <span>Views</span>
                            </div>
                        </div>
                    </div>
                </div>

                <a href="./create.php" class="btn btn-light btn-lg rounded-pill fw-bold">
                    <i class="bi bi-plus-circle"></i> New Post
                </a>
            </div>
        </div>
    </div>
</header>

<section class="pb-5">
    <div class="container px-4 px-lg-5">
        <?php if (empty($posts)) : ?>
            <div class="empty-state-box">
                <h3>No posts yet</h3>
                <p class="text-muted mb-4">Start building your creator space with your first publication.</p>
                <a href="./create.php" class="btn btn-primary btn-lg rounded-pill">Create First Post</a>
            </div>
        <?php else : ?>
            <div class="social-grid">
                <?php foreach ($posts as $post) : ?>
                    <?php
                    $commentCount = $commentC->countCommentsByPost($post['id']);
                    $previewComments = $commentC->listLatestCommentsByPost($post['id'], 2);
                    $allCommentsTree = $commentC->getCommentsTreeByPost($post['id']);
                    $modalId = 'commentsModal-' . preg_replace('/[^a-zA-Z0-9_-]/', '', $post['id']);
                    ?>
                    <div class="social-col js-post-comments-scope js-post-view-track" data-post-id="<?= htmlspecialchars($post['id']) ?>" data-context="portfolio">
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
                                <h2 class="social-post-title"><?= htmlspecialchars($post['subject']) ?></h2>
                                <p class="social-post-text"><?= htmlspecialchars(mb_strimwidth($post['textContent'], 0, 260, '...')) ?></p>
                            </div>

                            <?php if (!empty($post['imageContent']) || !empty($post['VideoContent'])) : ?>
                                <div class="social-post-media-wrap">
                                    <?php if (!empty($post['imageContent'])) : ?>
                                        <img
                                            src="../../public/<?= htmlspecialchars($post['imageContent']) ?>"
                                            alt="Post image"
                                            class="social-post-image"
                                            loading="lazy"
                                        >
                                    <?php endif; ?>

                                    <?php if (!empty($post['VideoContent'])) : ?>
                                        <video class="social-post-video" controls preload="metadata" playsinline>
                                            <source src="../../public/<?= htmlspecialchars($post['VideoContent']) ?>">
                                        </video>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <div class="social-post-actions">
                                <div class="social-action-left">
                                    <span class="reaction-summary">
                                        <span><i class="bi bi-heart-fill"></i> <span class="js-like-count"><?= (int)$post['numberOfLike'] ?></span></span>
                                        <span><i class="bi bi-hand-thumbs-down-fill"></i> <span class="js-dislike-count"><?= (int)$post['numberOfDislike'] ?></span></span>
                                        <span class="view-meta"><i class="bi bi-eye"></i> <span class="js-view-count"><?= (int)$post['numberOfView'] ?></span> views</span>
                                        <span><i class="bi bi-chat-left-text"></i> <span class="js-comment-count"><?= $commentCount ?></span></span>
                                    </span>
                                </div>

                                <div class="social-action-right">
                                    <button type="button" class="icon-action-btn btn-comment-toggle js-open-comments-modal" data-bs-target="#<?= $modalId ?>" aria-label="Open comments">
                                        <i class="bi bi-chat-dots"></i>
                                    </button>

                                    <a href="./details.php?id=<?= urlencode($post['id']) ?>" class="action-btn view-btn">
                                        <i class="bi bi-eye"></i> View
                                    </a>

                                    <a href="./edit.php?id=<?= urlencode($post['id']) ?>" class="action-btn edit-btn">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </a>

                                    <a href="./delete.php?id=<?= urlencode($post['id']) ?>"
                                       class="action-btn delete-btn"
                                       onclick="return confirm('Are you sure you want to delete this post?');">
                                        <i class="bi bi-trash3"></i> Delete
                                    </a>
                                </div>
                            </div>

                            <div class="comments-preview mt-3 js-comments-preview">
                                <?php render_preview_comments($previewComments); ?>
                            </div>

                            <?php if ($commentCount > 0) : ?>
                                <div class="comment-create-inline">
                                    <button type="button" class="comment-see-all-btn js-open-comments-modal" data-bs-target="#<?= $modalId ?>">
                                        <i class="bi bi-chat-dots-fill"></i> See all comments
                                    </button>
                                </div>
                            <?php endif; ?>
                        </article>
                    </div>

                    <div class="modal fade" id="<?= $modalId ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-scrollable modal-lg">
                            <div class="modal-content" style="background:#fff;border-radius:20px;">
                                <div class="modal-header">
                                    <h5 class="modal-title">
                                        Comments — <?= htmlspecialchars($post['subject']) ?>
                                        <span class="comment-count-badge js-comment-count"><?= $commentCount ?></span>
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body js-post-comments-scope" data-post-id="<?= htmlspecialchars($post['id']) ?>" data-context="portfolio">
                                    <div class="comment-form-wrap mb-4">
                                        <div class="comment-avatar-sm">U</div>
                                        <div class="comment-input-area">
                                            <?php render_comment_form($post['id'], $post['id'], 'post', 'portfolio', 'Add a comment...', 'Post'); ?>
                                        </div>
                                    </div>

                                    <div class="comments-list js-comments-list">
                                        <?php if (empty($allCommentsTree)) : ?>
                                            <div class="no-comments-msg">
                                                <i class="bi bi-chat-square" style="font-size:2rem;opacity:.4;"></i>
                                                <p class="mt-2 mb-0">No comments yet. Be the first to comment!</p>
                                            </div>
                                        <?php else : ?>
                                            <?php foreach ($allCommentsTree as $commentNode) : ?>
                                                <?php render_comment_tree_node($commentNode, $post['id'], 'portfolio', $currentUserId); ?>
                                                <hr class="comment-separator">
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<script src="../assets/comment-front.js"></script>
<?php require_once '../partials/footer.php'; ?>
