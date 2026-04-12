<?php
require_once '../../../Controleur/postC.php';

$postC = new PostC();
$creatorId = 1;
$pageTitle = 'My Space';
$currentPage = 'portfolio';

$posts = $postC->listPostsByCreator($creatorId);
$stats = $postC->getCreatorStats($creatorId);

$creatorDisplayName = 'Creator #1';
if (!empty($posts) && !empty($posts[0]['creatorName'])) {
    $creatorDisplayName = $posts[0]['creatorName'];
}

require_once '../partials/header.php';
?>

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
                    <div class="social-col">
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
            <span><i class="bi bi-heart-fill"></i> <?= (int)$post['numberOfLike'] ?></span>
            <span><i class="bi bi-hand-thumbs-down-fill"></i> <?= (int)$post['numberOfDislike'] ?></span>
            <span class="view-meta"><i class="bi bi-eye"></i> <?= (int)$post['numberOfView'] ?> views</span>
        </span>
    </div>

    <div class="social-action-right">
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
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once '../partials/footer.php'; ?>
