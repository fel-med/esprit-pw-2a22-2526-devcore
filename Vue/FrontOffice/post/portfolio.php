<?php
require_once '../../../Controleur/session_helper.php';
cc_start_session();
require_once '../../../Controleur/postC.php';
require_once '../../../Controleur/commentC.php';
require_once '../../../Controleur/profileC.php';
require_once '../comment/_render.php';

// ── VÉRIFICATION SESSION ──────────────────────────────────────
cc_require_login('../utilisateur/login.php');

$postC = new PostC();
$commentC = new CommentC();
$creatorId = (int)$_SESSION['id'];
$pageTitle = 'My Space';
$currentPage = 'portfolio';
$currentUserId = (int)$_SESSION['id'];

$posts = $postC->listPostsByCreator($creatorId);
$stats = $postC->getCreatorStats($creatorId);

$sessionUser = isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : [];
$legacyUser = isset($_SESSION['utilisateur']) && is_array($_SESSION['utilisateur']) ? $_SESSION['utilisateur'] : [];

$creatorDisplayName = trim((string) (
    $_SESSION['nom']
    ?? $sessionUser['nom']
    ?? $legacyUser['nom']
    ?? ''
));

if ($creatorDisplayName === '' && !empty($posts) && !empty($posts[0]['creatorName'])) {
    $creatorDisplayName = trim((string) $posts[0]['creatorName']);
}

if ($creatorDisplayName === '') {
    $creatorDisplayName = 'Creator #' . $creatorId;
}

$creatorEmail = trim((string) (
    $_SESSION['email']
    ?? $sessionUser['email']
    ?? $legacyUser['email']
    ?? (!empty($posts[0]['creatorEmail']) ? $posts[0]['creatorEmail'] : '')
));

$handleSource = $creatorEmail !== ''
    ? preg_replace('/@.*$/', '', $creatorEmail)
    : $creatorDisplayName;
$creatorHandle = strtolower(trim((string) $handleSource));
$creatorHandle = preg_replace('/[^a-z0-9_]+/', '_', $creatorHandle);
$creatorHandle = trim((string) $creatorHandle, '_');
$creatorHandle = $creatorHandle !== '' ? '@' . $creatorHandle : '@myspace_creator';
$creatorInitial = function_exists('mb_substr')
    ? mb_substr($creatorDisplayName, 0, 1, 'UTF-8')
    : substr($creatorDisplayName, 0, 1);
$creatorInitial = strtoupper((string) $creatorInitial) ?: 'C';
$profileImageUrl = null;

try {
    $profileC = new ProfileC();
    $profileImageUrl = $profileC->getProfileImageUrl($creatorId, '../../public/uploads/profile');
} catch (Throwable $e) {
    $profileImageUrl = null;
}

$frontActive = 'myspace';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/../layout/front-theme-bootstrap.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=DM+Sans:wght@400;500;700;800&family=Fraunces:wght@700;800&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" />
    <link href="../assets/css/styles.css" rel="stylesheet" />
    <link href="../layout/front-header.css" rel="stylesheet" />
    <link href="../assets/post-front.css?v=3" rel="stylesheet" />
    <link rel="stylesheet" href="../assets/comment-front.css">
<link rel="icon" type="image/png" sizes="16x16" href="../../public/images/favicon-16.png">
<link rel="icon" type="image/png" sizes="32x32" href="../../public/images/favicon-32.png">
<link rel="shortcut icon" type="image/png" href="../../public/images/favicon-32.png">
<link rel="apple-touch-icon" sizes="180x180" href="../../public/images/apple-touch-icon.png">
</head>
<body class="d-flex flex-column min-vh-100 social-body">
<main class="flex-shrink-0">
<?php require_once __DIR__ . '/../layout/header.php'; ?>

<header class="portfolio-hero">
    <div class="container px-4 px-lg-5">
        <div class="portfolio-cover">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-4">
                <div class="d-flex align-items-center gap-3">

                    <!-- Avatar -->
                    <?php if ($profileImageUrl): ?>
                        <img class="creator-avatar" src="<?= htmlspecialchars($profileImageUrl) ?>" alt="Profile photo" style="object-fit:cover;padding:0;">
                    <?php else: ?>
                        <div class="creator-avatar">
                            <svg viewBox="0 0 24 24" fill="none" style="width:2rem;height:2rem;opacity:.85;">
                                <circle cx="12" cy="8" r="4" stroke="white" stroke-width="2"/>
                                <path d="M4 20c0-4 3.58-7 8-7s8 3 8 7" stroke="white" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </div>
                    <?php endif; ?>

                    <div>
                        <div class="creator-name"><?= htmlspecialchars($creatorDisplayName) ?></div>
                        <div class="creator-handle"><?= htmlspecialchars($creatorHandle) ?></div>
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

                <!-- Action buttons -->
                <div class="d-flex gap-2 flex-wrap">

                    <a href="./create.php" class="btn-new-post">
                        <svg viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/>
                            <line x1="12" y1="8" x2="12" y2="16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <line x1="8" y1="12" x2="16" y2="12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        New Post
                    </a>

                </div>
            </div>
        </div>
    </div>
</header>

<section class="pb-5 pt-4">
    <div class="container px-4 px-lg-5">
        <?php if (empty($posts)) : ?>
            <div class="empty-state-box">
                <h3>No posts yet</h3>
                <p class="text-muted mb-4">Start building your creator space with your first publication.</p>
                <a href="./create.php" class="action-btn readmore-btn">Create First Post</a>
            </div>
        <?php else : ?>
            <div class="social-grid">
                <?php foreach ($posts as $post) : ?>
                    <?php
                    $commentCount    = $commentC->countCommentsByPost($post['id']);
                    $previewComments = $commentC->listLatestCommentsByPost($post['id'], 2);
                    $allCommentsTree = $commentC->getCommentsTreeByPost($post['id']);
                    $modalId = 'commentsModal-' . preg_replace('/[^a-zA-Z0-9_-]/', '', $post['id']);
                    ?>
                    <div class="social-col js-post-comments-scope js-post-view-track"
                         data-post-id="<?= htmlspecialchars($post['id']) ?>"
                         data-context="portfolio">

                        <article class="social-post-card">

                            <!-- Header -->
                            <div class="social-post-header">
                                <?php if ($profileImageUrl): ?>
                                    <img class="social-post-avatar" src="<?= htmlspecialchars($profileImageUrl) ?>" alt="Profile photo" style="object-fit:cover;padding:0;">
                                <?php else: ?>
                                    <div class="social-post-avatar">
                                        <?= htmlspecialchars(substr($post['creatorName'] ?? 'C', 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <div class="social-post-author"><?= htmlspecialchars($post['creatorName'] ?? ('Creator #' . $post['idCreateur'])) ?></div>
                                    <div class="social-post-meta"><?= htmlspecialchars($post['creationDate']) ?></div>
                                </div>
                            </div>

                            <!-- Body -->
                            <div class="social-post-body">
                                <h2 class="social-post-title"><?= htmlspecialchars($post['subject']) ?></h2>
                                <p class="social-post-text"><?= htmlspecialchars(mb_strimwidth($post['textContent'], 0, 260, '...')) ?></p>
                            </div>

                            <!-- Media -->
                            <?php if (!empty($post['imageContent']) || !empty($post['VideoContent'])) : ?>
                                <div class="social-post-media-wrap">
                                    <?php if (!empty($post['imageContent'])) : ?>
                                        <img src="../../public/<?= htmlspecialchars($post['imageContent']) ?>"
                                             alt="Post image" class="social-post-image" loading="lazy">
                                    <?php endif; ?>
                                    <?php if (!empty($post['VideoContent'])) : ?>
                                        <video class="social-post-video" controls preload="metadata" playsinline>
                                            <source src="../../public/<?= htmlspecialchars($post['VideoContent']) ?>">
                                        </video>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Actions -->
                            <div class="social-post-actions">

                                <!-- Reaction summary left | action buttons right -->
                                <div class="social-action-top">
                                    <div class="social-action-left">
                                        <span class="reaction-summary">
                                            <span>
                                                <svg viewBox="0 0 24 24" fill="currentColor" style="width:12px;height:12px;color:#be123c;">
                                                    <path d="M12 21C12 21 3 14.5 3 8.5C3 5.42 5.42 3 8.5 3C10.24 3 11.81 3.85 12 5C12.19 3.85 13.76 3 15.5 3C18.58 3 21 5.42 21 8.5C21 14.5 12 21 12 21Z"/>
                                                </svg>
                                                <span class="js-like-count"><?= (int)$post['numberOfLike'] ?></span>
                                            </span>
                                            <span>
                                                <svg viewBox="0 0 24 24" fill="currentColor" style="width:12px;height:12px;color:#5b4fff;">
                                                    <path d="M17 2H7L3 10v2c0 1.1.9 2 2 2h6l-1 5.5c-.1.6.3 1.1.9 1.4l.6.1c.5 0 1-.3 1.2-.8L15 14h4a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2z"/>
                                                </svg>
                                                <span class="js-dislike-count"><?= (int)$post['numberOfDislike'] ?></span>
                                            </span>
                                            <span class="view-meta">
                                                <svg viewBox="0 0 24 24" fill="none" style="width:12px;height:12px;">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2"/>
                                                    <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                                                </svg>
                                                <span class="js-view-count"><?= (int)$post['numberOfView'] ?></span> views
                                            </span>
                                            <span>
                                                <svg viewBox="0 0 24 24" fill="none" style="width:12px;height:12px;color:#0369a1;">
                                                    <path d="M21 15C21 15.53 20.79 16.04 20.41 16.41C20.04 16.79 19.53 17 19 17H7L3 21V5C3 4.47 3.21 3.96 3.59 3.59C3.96 3.21 4.47 3 5 3H19C19.53 3 20.04 3.21 20.41 3.59C20.79 3.96 21 4.47 21 5V15Z"
                                                          stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                                <span class="js-comment-count"><?= $commentCount ?></span>
                                            </span>
                                        </span>
                                    </div>

                                    <div class="social-action-right">
                                        <!-- Comments toggle -->
                                        <button type="button"
                                                class="icon-action-btn js-open-comments-modal"
                                                data-bs-target="#<?= $modalId ?>"
                                                aria-label="Open comments">
                                            <svg viewBox="0 0 24 24" fill="none" style="width:16px;height:16px;">
                                                <path d="M21 15C21 15.53 20.79 16.04 20.41 16.41C20.04 16.79 19.53 17 19 17H7L3 21V5C3 4.47 3.21 3.96 3.59 3.59C3.96 3.21 4.47 3 5 3H19C19.53 3 20.04 3.21 20.41 3.59C20.79 3.96 21 4.47 21 5V15Z"
                                                      stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                <line x1="8" y1="9" x2="16" y2="9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                                <line x1="8" y1="13" x2="13" y2="13" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                            </svg>
                                        </button>

                                        <a href="./details.php?id=<?= urlencode($post['id']) ?>" class="action-btn view-btn">
                                            <svg viewBox="0 0 24 24" fill="none" style="width:13px;height:13px;">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2"/>
                                                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                                            </svg>
                                            View
                                        </a>

                                        <a href="./edit.php?id=<?= urlencode($post['id']) ?>" class="action-btn edit-btn">
                                            <svg viewBox="0 0 24 24" fill="none" style="width:13px;height:13px;">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            Edit
                                        </a>

                                        <a href="./delete.php?id=<?= urlencode($post['id']) ?>"
                                           class="action-btn delete-btn"
                                           onclick="return confirm('Are you sure you want to delete this post?');">
                                            <svg viewBox="0 0 24 24" fill="none" style="width:13px;height:13px;">
                                                <polyline points="3 6 5 6 21 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            Delete
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Preview comments -->
                            <div class="comments-preview px-3 pb-1 js-comments-preview">
                                <?php render_preview_comments($previewComments); ?>
                            </div>

                            <?php if ($commentCount > 0) : ?>
                                <div class="comment-create-inline px-3 pb-3">
                                    <button type="button" class="comment-see-all-btn js-open-comments-modal" data-bs-target="#<?= $modalId ?>">
                                        <svg viewBox="0 0 24 24" fill="none" style="width:14px;height:14px;">
                                            <path d="M21 15C21 15.53 20.79 16.04 20.41 16.41C20.04 16.79 19.53 17 19 17H7L3 21V5C3 4.47 3.21 3.96 3.59 3.59C3.96 3.21 4.47 3 5 3H19C19.53 3 20.04 3.21 20.41 3.59C20.79 3.96 21 4.47 21 5V15Z"
                                                  stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                  fill="currentColor" fill-opacity="0.1"/>
                                        </svg>
                                        See all comments
                                    </button>
                                </div>
                            <?php endif; ?>
                        </article>
                    </div>

                    <!-- Comments Modal -->
                    <div class="modal fade" id="<?= $modalId ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-scrollable modal-lg">
                            <div class="modal-content" style="background:#fff;border-radius:20px;">
                                <div class="modal-header border-0 pb-0">
                                    <h5 class="modal-title" style="font-family:'Fraunces',serif;font-weight:800;font-size:1rem;display:flex;align-items:center;gap:.5rem;">
                                        <span style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:8px;background:#f0f9ff;color:#0369a1;flex-shrink:0;">
                                            <svg viewBox="0 0 24 24" fill="none" style="width:14px;height:14px;">
                                                <path d="M21 15C21 15.53 20.79 16.04 20.41 16.41C20.04 16.79 19.53 17 19 17H7L3 21V5C3 4.47 3.21 3.96 3.59 3.59C3.96 3.21 4.47 3 5 3H19C19.53 3 20.04 3.21 20.41 3.59C20.79 3.96 21 4.47 21 5V15Z"
                                                      stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                <line x1="8" y1="9" x2="16" y2="9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                                <line x1="8" y1="13" x2="13" y2="13" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                            </svg>
                                        </span>
                                        Comments — <?= htmlspecialchars($post['subject']) ?>
                                        <span class="comment-count-badge js-comment-count"><?= $commentCount ?></span>
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body js-post-comments-scope"
                                     data-post-id="<?= htmlspecialchars($post['id']) ?>"
                                     data-context="portfolio">
                                    <div class="comment-form-wrap mb-4">
                                        <?php if ($profileImageUrl): ?>
                                            <img class="comment-avatar-sm" src="<?= htmlspecialchars($profileImageUrl) ?>" alt="Profile photo" style="object-fit:cover;padding:0;">
                                        <?php else: ?>
                                            <div class="comment-avatar-sm"><?= htmlspecialchars($creatorInitial) ?></div>
                                        <?php endif; ?>
                                        <div class="comment-input-area">
                                            <?php render_comment_form($post['id'], $post['id'], 'post', 'portfolio', 'Add a comment...', 'Post'); ?>
                                        </div>
                                    </div>
                                    <div class="comments-list js-comments-list">
                                        <?php if (empty($allCommentsTree)) : ?>
                                            <div class="no-comments-msg">
                                                <svg viewBox="0 0 24 24" fill="none" style="width:2rem;height:2rem;opacity:.3;display:block;margin:0 auto .5rem;">
                                                    <path d="M21 15C21 15.53 20.79 16.04 20.41 16.41C20.04 16.79 19.53 17 19 17H7L3 21V5C3 4.47 3.21 3.96 3.59 3.59C3.96 3.21 4.47 3 5 3H19C19.53 3 20.04 3.21 20.41 3.59C20.79 3.96 21 4.47 21 5V15Z"
                                                          stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
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
<script src="../layout/front-header.js"></script>
<?php require_once '../partials/footer.php'; ?>
