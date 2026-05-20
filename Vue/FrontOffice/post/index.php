<?php
require_once '../../../Controleur/session_helper.php';
cc_start_session();
require_once '../../../Controleur/postC.php';
require_once '../../../Controleur/commentC.php';
require_once '../comment/_render.php';

// ── VÉRIFICATION SESSION ──────────────────────────────────────
cc_require_login('../utilisateur/login.php');

$postC = new PostC();
$commentC = new CommentC();
$pageTitle = 'Actuality';
$currentPage = 'actuality';
$currentUserId = (int)$_SESSION['id'];
$currentUserName = (string)($_SESSION['nom'] ?? $_SESSION['user']['nom'] ?? $_SESSION['utilisateur']['nom'] ?? 'User');

$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'recent';

if ($search !== '') {
    $posts = $postC->searchPosts($search);
} elseif ($sort === 'trending') {
    $posts = $postC->listTrendingPosts();
} else {
    $posts = $postC->listPosts();
}


if (!function_exists('collect_commenter_names')) {
    function collect_commenter_names(array $nodes, array &$names): void
    {
        foreach ($nodes as $node) {
            $name = trim((string)($node['userName'] ?? ''));
            if ($name !== '') {
                $names[] = $name;
            }
            if (!empty($node['replies']) && is_array($node['replies'])) {
                collect_commenter_names($node['replies'], $names);
            }
        }
    }
}

$creaAssistantPosts = [];

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

<header class="feed-hero">
    <div class="container px-4 px-lg-5">
        <div class="hero-panel">
            <h1 class="hero-title">Discover the latest creator actuality</h1>
            <p class="hero-subtitle">
                Explore all posts, search by creator or subject, and discover the most trending publications through a clean and responsive social-media experience.
            </p>

            <div class="toolbar-card mt-4">
                <form method="GET" action="./index.php">
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <input
                            type="text"
                            name="search"
                            class="toolbar-input flex-grow-1"
                            placeholder="Search by creator name or subject..."
                            value="<?= htmlspecialchars($search) ?>"
                            style="min-width:200px;"
                        >

                        <!-- Search — primary violet, filled -->
                        <button type="submit" class="btn-search-action">
                            <svg viewBox="0 0 24 24" fill="none">
                                <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/>
                                <path d="M16.5 16.5L21 21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            Search
                        </button>

                        <!-- Trending — warm orange -->
                        <a href="./index.php?sort=trending" class="btn-trending-action">
                            <svg viewBox="0 0 24 24" fill="none">
                                <polyline points="22 7 13.5 15.5 8.5 10.5 2 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <polyline points="16 7 22 7 22 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            Trending
                        </a>

                        <!-- Reset — neutral -->
                        <a href="./index.php" class="btn-reset-action">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M3 12C3 7.03 7.03 3 12 3C15.3 3 18.17 4.77 19.77 7.42" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                <path d="M21 12C21 16.97 16.97 21 12 21C8.7 21 5.83 19.23 4.23 16.58" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                <polyline points="20 3 20 8 15 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <polyline points="4 21 4 16 9 16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</header>

<section class="pb-5 pt-4">
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
                    $commentCount    = $commentC->countCommentsByPost($post['id']);
                    $previewComments = $commentC->listLatestCommentsByPost($post['id'], 2);
                    $allCommentsTree = $commentC->getCommentsTreeByPost($post['id']);
                    $modalId = 'commentsModal-' . preg_replace('/[^a-zA-Z0-9_-]/', '', $post['id']);
                    $commenterNames = [];
                    collect_commenter_names($allCommentsTree, $commenterNames);
                    $commenterNames = array_values(array_unique(array_filter($commenterNames)));
                    $creaAssistantPosts[] = [
                        'id' => (string)$post['id'],
                        'creatorName' => (string)($post['creatorName'] ?? ('Creator #' . ($post['idCreateur'] ?? ''))),
                        'subject' => (string)($post['subject'] ?? ''),
                        'textPreview' => (string)mb_strimwidth((string)($post['textContent'] ?? ''), 0, 320, ''),
                        'creationDate' => (string)($post['creationDate'] ?? ''),
                        'commentCount' => (int)$commentCount,
                        'commenters' => $commenterNames,
                    ];
                    ?>
                    <div class="social-col js-post-comments-scope js-post-view-track"
                         data-post-id="<?= htmlspecialchars($post['id']) ?>"
                         data-context="index"
                         data-creator-name="<?= htmlspecialchars(mb_strtolower((string)($post['creatorName'] ?? ''))) ?>"
                         data-subject="<?= htmlspecialchars(mb_strtolower((string)($post['subject'] ?? ''))) ?>"
                         data-post-date="<?= htmlspecialchars((string)($post['creationDate'] ?? '')) ?>">

                        <article class="social-post-card">

                            <!-- Header -->
                            <div class="social-post-header">
                                <?= cre8_render_avatar($post['idCreateur'] ?? 0, (string)($post['creatorName'] ?? 'Creator'), 'social-post-avatar') ?>
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

                                <!-- Top row: like · dislike · comment  |  Read More -->
                                <div class="social-action-top">
                                    <div class="social-action-left">

                                        <!-- Like — heart SVG, red -->
                                        <button type="button"
                                                class="reaction-btn js-reaction-btn"
                                                data-post-id="<?= htmlspecialchars($post['id']) ?>"
                                                data-action="like"
                                                aria-label="Like post">
                                            <svg viewBox="0 0 24 24" fill="none">
                                                <path d="M12 21C12 21 3 14.5 3 8.5C3 5.42 5.42 3 8.5 3C10.24 3 11.81 3.85 12 5C12.19 3.85 13.76 3 15.5 3C18.58 3 21 5.42 21 8.5C21 14.5 12 21 12 21Z"
                                                      stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                      fill="currentColor" fill-opacity="0.12"/>
                                            </svg>
                                        </button>

                                        <!-- Dislike — thumbs-down SVG, violet -->
                                        <button type="button"
                                                class="btn-dislike-post js-reaction-btn"
                                                data-post-id="<?= htmlspecialchars($post['id']) ?>"
                                                data-action="dislike"
                                                aria-label="Dislike post">
                                            <svg viewBox="0 0 24 24" fill="none">
                                                <path d="M17 2H7L3 10v2c0 1.1.9 2 2 2h6l-1 5.5c-.1.6.3 1.1.9 1.4l.6.1c.5 0 1-.3 1.2-.8L15 14h4a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2z"
                                                      stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </button>

                                        <!-- Comment bubble -->
                                        <button type="button"
                                                class="action-btn btn-comment-post js-open-comments-modal"
                                                data-bs-target="#<?= $modalId ?>"
                                                aria-label="Open comments">
                                            <svg viewBox="0 0 24 24" fill="none">
                                                <path d="M21 15C21 15.53 20.79 16.04 20.41 16.41C20.04 16.79 19.53 17 19 17H7L3 21V5C3 4.47 3.21 3.96 3.59 3.59C3.96 3.21 4.47 3 5 3H19C19.53 3 20.04 3.21 20.41 3.59C20.79 3.96 21 4.47 21 5V15Z"
                                                      stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                      fill="currentColor" fill-opacity="0.1"/>
                                                <line x1="8" y1="9" x2="16" y2="9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                                <line x1="8" y1="13" x2="13" y2="13" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Read More — same row, right side, vertically centered -->
                                    <div class="social-action-right">
                                        <a href="./details.php?id=<?= urlencode($post['id']) ?>" class="action-btn readmore-btn">
                                            <svg viewBox="0 0 24 24" fill="none" style="width:14px;height:14px;">
                                                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/>
                                                <path d="M9 12h6M13 10l2 2-2 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            Read More
                                        </a>
                                    </div>
                                </div>

                                <!-- Summary row -->
                                <div class="reaction-summary">
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
                                        Comments
                                        <span class="comment-count-badge js-comment-count"><?= $commentCount ?></span>
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body js-post-comments-scope"
                                     data-post-id="<?= htmlspecialchars($post['id']) ?>"
                                     data-context="index">
                                    <div class="comment-form-wrap mb-4">
                                        <?= cre8_render_avatar($currentUserId, $currentUserName, 'comment-avatar-sm') ?>
                                        <div class="comment-input-area">
                                            <?php render_comment_form($post['id'], $post['id'], 'post', 'index', 'Add a comment...', 'Post'); ?>
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


<div class="crea-chatbot" id="creaChatbot">
    <button type="button" class="crea-chatbot-fab" id="creaChatbotFab" aria-label="Open Crea assistant">
        <span class="crea-chatbot-fab-icon"><i class="bi bi-chat-dots-fill"></i></span>
        <span class="crea-chatbot-fab-text">Crea</span>
    </button>

    <section class="crea-chatbot-panel" id="creaChatbotPanel" aria-hidden="true">
        <div class="crea-chatbot-shell">
            <div class="crea-chatbot-header">
                <div class="crea-chatbot-brand">
                    <div class="crea-chatbot-avatar">C</div>
                    <div>
                        <div class="crea-chatbot-name">Crea</div>
                        <div class="crea-chatbot-subtitle">Feed assistant</div>
                    </div>
                </div>
                <button type="button" class="crea-chatbot-close" id="creaChatbotClose" aria-label="Close chatbot">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div class="crea-chatbot-body" id="creaChatbotMessages">
                <div class="crea-message crea-message-bot">
                    <div class="crea-message-bubble">Welcome to cre8connect, a platform that connects content creators with big brands. How can I assist you today? I can help you find specific posts, get who commented on one post, find the last post posted, filter the feed by creator or subject, and explain how to create or comment on a post.</div>
                </div>
            </div>

            <div class="crea-chatbot-quick-actions" id="creaChatbotQuickActions">
                <button type="button" class="crea-quick-chip" data-crea-prompt="Find for me the last post posted">Latest post</button>
                <button type="button" class="crea-quick-chip" data-crea-prompt="Show me posts related to design">Posts by subject</button>
                <button type="button" class="crea-quick-chip" data-crea-prompt="How do I create a post?">How to create</button>
                <button type="button" class="crea-quick-chip" data-crea-action="reset">Reset feed</button>
            </div>

            <form class="crea-chatbot-form" id="creaChatbotForm">
                <textarea class="crea-chatbot-input" id="creaChatbotInput" rows="1" placeholder="Ask Crea to find or explain something..."></textarea>
                <button type="submit" class="crea-chatbot-send" id="creaChatbotSend">
                    <i class="bi bi-arrow-up"></i>
                </button>
            </form>
        </div>
    </section>
</div>

<script>
window.creaChatbotPosts = <?= json_encode($creaAssistantPosts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>

<script>
(function () {
    var translations = {
        en: {
            'post.feedTitle': 'Discover the latest creator actuality',
            'post.feedSubtitle': 'Explore all posts, search by creator or subject, and discover the most trending publications through a clean and responsive social-media experience.',
            'post.searchPlaceholder': 'Search by creator name or subject...',
            'post.search': 'Search',
            'post.trending': 'Trending',
            'post.reset': 'Reset',
            'post.noPostsFound': 'No posts found',
            'post.noPostsFoundCopy': 'Try another search or go back to the full actuality feed.',
            'post.readMore': 'Read More',
            'post.views': 'views',
            'post.comments': 'Comments',
            'post.seeAllComments': 'See all comments',
            'post.noCommentsLong': 'No comments yet. Be the first to comment!',
            'post.noCommentsShort': 'No comments yet.',
            'post.commentPlaceholder': 'Add a comment...',
            'post.writeCommentPlaceholder': 'Write a comment...',
            'post.replyPlaceholder': 'Reply to this comment...',
            'post.editCommentPlaceholder': 'Edit your comment...',
            'post.postButton': 'Post',
            'post.reply': 'Reply',
            'post.edit': 'Edit',
            'post.delete': 'Delete',
            'post.save': 'Save',
            'post.cancel': 'Cancel',
            'post.removeCurrentImage': 'Remove current image',
            'post.emoji': 'Emoji',
            'post.addImage': 'Add image',
            'post.replaceImage': 'Replace image',
            'post.voiceTranscription': 'Voice transcription',
            'post.transcriptionLanguage': 'Transcription language',
            'post.voiceIdle': 'Tap the microphone to dictate your comment',
            'post.voiceRecording': 'Listening... speak now',
            'post.openComments': 'Open comments',
            'post.feedAssistant': 'Feed assistant',
            'post.chatWelcome': 'Welcome to cre8connect, a platform that connects content creators with big brands. How can I assist you today? I can help you find specific posts, get who commented on one post, find the last post posted, filter the feed by creator or subject, and explain how to create or comment on a post.',
            'post.latestPost': 'Latest post',
            'post.postsBySubject': 'Posts by subject',
            'post.howCreate': 'How to create',
            'post.resetFeed': 'Reset feed',
            'post.creaPlaceholder': 'Ask Crea to find or explain something...',
            'post.closeChatbot': 'Close chatbot'
        },
        fr: {
            'post.feedTitle': 'Decouvrez les dernieres actualites createur',
            'post.feedSubtitle': 'Explorez tous les posts, recherchez par createur ou sujet, et decouvrez les publications les plus tendance dans une experience sociale claire et responsive.',
            'post.searchPlaceholder': 'Rechercher par nom de createur ou sujet...',
            'post.search': 'Rechercher',
            'post.trending': 'Tendance',
            'post.reset': 'Reinitialiser',
            'post.noPostsFound': 'Aucun post trouve',
            'post.noPostsFoundCopy': 'Essayez une autre recherche ou revenez au fil complet des actualites.',
            'post.readMore': 'Lire plus',
            'post.views': 'vues',
            'post.comments': 'Commentaires',
            'post.seeAllComments': 'Voir tous les commentaires',
            'post.noCommentsLong': 'Aucun commentaire pour le moment. Soyez le premier a commenter !',
            'post.noCommentsShort': 'Aucun commentaire pour le moment.',
            'post.commentPlaceholder': 'Ajouter un commentaire...',
            'post.writeCommentPlaceholder': 'Ecrire un commentaire...',
            'post.replyPlaceholder': 'Repondre a ce commentaire...',
            'post.editCommentPlaceholder': 'Modifier votre commentaire...',
            'post.postButton': 'Publier',
            'post.reply': 'Repondre',
            'post.edit': 'Modifier',
            'post.delete': 'Supprimer',
            'post.save': 'Enregistrer',
            'post.cancel': 'Annuler',
            'post.removeCurrentImage': 'Supprimer l image actuelle',
            'post.emoji': 'Emoji',
            'post.addImage': 'Ajouter une image',
            'post.replaceImage': 'Remplacer l image',
            'post.voiceTranscription': 'Transcription vocale',
            'post.transcriptionLanguage': 'Langue de transcription',
            'post.voiceIdle': 'Appuyez sur le micro pour dicter votre commentaire',
            'post.voiceRecording': 'Ecoute en cours... parlez maintenant',
            'post.openComments': 'Ouvrir les commentaires',
            'post.feedAssistant': 'Assistant du fil',
            'post.chatWelcome': 'Bienvenue sur cre8connect, une plateforme qui connecte les createurs de contenu aux grandes marques. Comment puis-je vous aider aujourd hui ? Je peux vous aider a trouver des posts precis, voir qui a commente un post, trouver le dernier post publie, filtrer le fil par createur ou sujet, et expliquer comment creer ou commenter un post.',
            'post.latestPost': 'Dernier post',
            'post.postsBySubject': 'Posts par sujet',
            'post.howCreate': 'Comment creer',
            'post.resetFeed': 'Reinitialiser le fil',
            'post.creaPlaceholder': 'Demandez a Crea de trouver ou expliquer quelque chose...',
            'post.closeChatbot': 'Fermer le chatbot'
        }
    };
    var aliases = {
        'Discover the latest creator actuality': 'post.feedTitle',
        'Explore all posts, search by creator or subject, and discover the most trending publications through a clean and responsive social-media experience.': 'post.feedSubtitle',
        'Search by creator name or subject...': 'post.searchPlaceholder',
        'Search': 'post.search',
        'Trending': 'post.trending',
        'Reset': 'post.reset',
        'No posts found': 'post.noPostsFound',
        'Try another search or go back to the full actuality feed.': 'post.noPostsFoundCopy',
        'Read More': 'post.readMore',
        'views': 'post.views',
        'Comments': 'post.comments',
        'See all comments': 'post.seeAllComments',
        'No comments yet. Be the first to comment!': 'post.noCommentsLong',
        'No comments yet.': 'post.noCommentsShort',
        'Add a comment...': 'post.commentPlaceholder',
        'Write a comment...': 'post.writeCommentPlaceholder',
        'Reply to this comment...': 'post.replyPlaceholder',
        'Edit your comment...': 'post.editCommentPlaceholder',
        'Post': 'post.postButton',
        'Reply': 'post.reply',
        'Edit': 'post.edit',
        'Delete': 'post.delete',
        'Save': 'post.save',
        'Cancel': 'post.cancel',
        'Remove current image': 'post.removeCurrentImage',
        'Emoji': 'post.emoji',
        'Add image': 'post.addImage',
        'Replace image': 'post.replaceImage',
        'Voice transcription': 'post.voiceTranscription',
        'Transcription language': 'post.transcriptionLanguage',
        'Tap the microphone to dictate your comment': 'post.voiceIdle',
        'Listening... speak now': 'post.voiceRecording',
        'Open comments': 'post.openComments',
        'Feed assistant': 'post.feedAssistant',
        'Welcome to cre8connect, a platform that connects content creators with big brands. How can I assist you today? I can help you find specific posts, get who commented on one post, find the last post posted, filter the feed by creator or subject, and explain how to create or comment on a post.': 'post.chatWelcome',
        'Latest post': 'post.latestPost',
        'Posts by subject': 'post.postsBySubject',
        'How to create': 'post.howCreate',
        'Reset feed': 'post.resetFeed',
        'Ask Crea to find or explain something...': 'post.creaPlaceholder',
        'Close chatbot': 'post.closeChatbot'
    };
    function currentLang() {
        if (typeof window.cre8FrontReadLang === 'function') {
            return window.cre8FrontReadLang();
        }
        try {
            return localStorage.getItem('cre8_front_lang') === 'fr' || localStorage.getItem('cre8_lang') === 'fr' ? 'fr' : 'en';
        } catch (e) {
            return 'en';
        }
    }
    function keyFor(value) {
        var text = String(value || '').replace(/\s+/g, ' ').trim();
        if (!text) return null;
        if (aliases[text]) return aliases[text];
        var langs = ['en', 'fr'];
        for (var i = 0; i < langs.length; i++) {
            var dict = translations[langs[i]];
            for (var key in dict) {
                if (Object.prototype.hasOwnProperty.call(dict, key) && dict[key] === text) return key;
            }
        }
        return null;
    }
    function translated(key) {
        var dict = translations[currentLang()] || translations.en;
        return dict[key] || translations.en[key] || null;
    }
    function applyAttr(selector, attr, keyAttr) {
        Array.prototype.forEach.call(document.querySelectorAll(selector), function (el) {
            var key = el.getAttribute(keyAttr) || keyFor(el.getAttribute(attr));
            var value = translated(key);
            if (value) {
                el.setAttribute(keyAttr, key);
                el.setAttribute(attr, value);
            }
        });
    }
    function skipText(parent) {
        return !parent || parent.closest('script,style,textarea,.social-post-title,.social-post-text,.comment-text,.comment-author,.creator-name,.creator-handle,.social-post-author,.social-post-meta,.comment-sticker');
    }
    function applyPostTranslations(root) {
        if (typeof window.cre8ApplyI18n === 'function') {
            window.cre8ApplyI18n(translations);
        }
        applyAttr('[placeholder]', 'placeholder', 'data-i18n-placeholder');
        applyAttr('[title]', 'title', 'data-i18n-title');
        applyAttr('[aria-label]', 'aria-label', 'data-i18n-title');
        Array.prototype.forEach.call(document.querySelectorAll('[data-i18n-idle-label]'), function (el) {
            var value = translated(el.getAttribute('data-i18n-idle-label'));
            if (value) el.setAttribute('data-idle-label', value);
        });
        Array.prototype.forEach.call(document.querySelectorAll('[data-i18n-recording-label]'), function (el) {
            var value = translated(el.getAttribute('data-i18n-recording-label'));
            if (value) el.setAttribute('data-recording-label', value);
        });
        var scope = root && root.nodeType === 1 ? root : document.body;
        if (!scope) return;
        var walker = document.createTreeWalker(scope, NodeFilter.SHOW_TEXT);
        var nodes = [];
        while (walker.nextNode()) nodes.push(walker.currentNode);
        nodes.forEach(function (node) {
            if (skipText(node.parentElement)) return;
            var key = keyFor(node.nodeValue);
            var value = translated(key);
            if (value) node.nodeValue = node.nodeValue.replace(String(node.nodeValue).trim(), value);
        });
    }
    function initPostTranslations() {
        if (typeof window.cre8RegisterTranslations === 'function') {
            window.cre8RegisterTranslations(translations);
        }
        applyPostTranslations(document.body);
        var pending = false;
        new MutationObserver(function () {
            if (pending) return;
            pending = true;
            window.setTimeout(function () {
                pending = false;
                applyPostTranslations(document.body);
            }, 0);
        }).observe(document.body, { childList: true, subtree: true });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPostTranslations);
    } else {
        initPostTranslations();
    }
    window.addEventListener('cre8:languagechange', function () {
        applyPostTranslations(document.body);
    });
})();
</script>

<script src="../assets/comment-front.js"></script>
<script src="../layout/front-header.js"></script>
</main>
<?php require __DIR__ . '/../layout/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/scripts.js"></script>
<script src="../assets/post-front.js?v=2"></script>
</body>
</html>
