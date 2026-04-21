<?php
require_once '../../../Controleur/postC.php';
require_once '../../../Controleur/commentC.php';
require_once '../comment/_render.php';

$postC = new PostC();
$commentC = new CommentC();
$pageTitle = 'Actuality';
$currentPage = 'actuality';
$currentUserId = 1;

$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'recent';

if ($search !== '') {
    $posts = $postC->searchPosts($search);
} elseif ($sort === 'trending') {
    $posts = $postC->listTrendingPosts();
} else {
    $posts = $postC->listPosts();
}

require_once '../partials/header.php';
?>
<link rel="stylesheet" href="../assets/comment-front.css">

<header class="feed-hero">
    <div class="container px-4 px-lg-5">
        <div class="hero-panel">
            <h1 class="hero-title">Discover the latest creator actuality</h1>
            <p class="hero-subtitle">
                Explore all posts, search by creator or subject, and discover the most trending publications through a clean and responsive social-media experience.
            </p>

            <div class="toolbar-card mt-4">
                <form method="GET" action="./index.php" class="row g-3 align-items-center">
                    <div class="col-lg-8">
                        <input
                            type="text"
                            id="searchInput"
                            name="search"
                            class="form-control toolbar-input"
                            placeholder="Search by creator name or subject..."
                            value="<?= htmlspecialchars($search) ?>"
                        >
                    </div>

                    <div class="col-lg-4 d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary toolbar-btn flex-grow-1">
                            <i class="bi bi-search"></i> Search
                        </button>
                        <a href="./index.php?sort=trending" class="btn btn-dark toolbar-btn">
                            <i class="bi bi-fire"></i> Trending
                        </a>
                        <a href="./index.php" class="btn btn-outline-secondary toolbar-btn">
                            Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</header>

<section class="pb-5">
    <div class="container px-4 px-lg-5">
        <?php if (empty($posts)) : ?>
            <div class="empty-state-box">
                <h3>No posts found</h3>
                <p class="text-muted mb-0">Try another search or go back to the full actuality feed.</p>
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
                    <div class="social-col js-post-comments-scope js-post-view-track" data-post-id="<?= htmlspecialchars($post['id']) ?>" data-context="index">
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

                                        <button
                                            type="button"
                                            class="icon-action-btn btn-comment-toggle js-open-comments-modal"
                                            data-bs-target="#<?= $modalId ?>"
                                            aria-label="Open comments"
                                        >
                                            <i class="bi bi-chat-dots"></i>
                                        </button>
                                    </div>

                                    <div class="social-action-right">
                                        <a href="./details.php?id=<?= urlencode($post['id']) ?>" class="action-btn readmore-btn">
                                            <i class="bi bi-arrow-right-circle"></i> Read More
                                        </a>
                                    </div>
                                </div>

                                <div class="reaction-summary">
                                    <span><i class="bi bi-heart-fill"></i> <span class="js-like-count"><?= (int)$post['numberOfLike'] ?></span></span>
                                    <span><i class="bi bi-hand-thumbs-down-fill"></i> <span class="js-dislike-count"><?= (int)$post['numberOfDislike'] ?></span></span>
                                    <span class="view-meta"><i class="bi bi-eye"></i> <span class="js-view-count"><?= (int)$post['numberOfView'] ?></span> views</span>
                                    <span><i class="bi bi-chat-left-text"></i> <span class="js-comment-count"><?= $commentCount ?></span></span>
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
                                <div class="modal-body js-post-comments-scope" data-post-id="<?= htmlspecialchars($post['id']) ?>" data-context="index">
                                    <div class="comment-form-wrap mb-4">
                                        <div class="comment-avatar-sm">U</div>
                                        <div class="comment-input-area">
                                            <?php render_comment_form($post['id'], $post['id'], 'post', 'index', 'Add a comment...', 'Post'); ?>
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
                                                <?php render_comment_tree_node($commentNode, $post['id'], 'index', $currentUserId); ?>
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
