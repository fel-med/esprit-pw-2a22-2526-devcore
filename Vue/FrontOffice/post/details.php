<?php
require_once '../../../Controleur/session_helper.php';
cc_start_session();
require_once '../../../Controleur/postC.php';
require_once '../../../Controleur/commentC.php';
require_once '../comment/_render.php';

// ── VÉRIFICATION SESSION ──────────────────────────────────────
cc_require_login('../utilisateur/login.php');

$postC    = new PostC();
$commentC = new CommentC();
$idUser   = (int)$_SESSION['id'];
$currentUserName = (string)($_SESSION['nom'] ?? $_SESSION['user']['nom'] ?? $_SESSION['utilisateur']['nom'] ?? 'User');

if (empty($_GET['id']) && empty($_GET['idPost'])) {
    http_response_code(404);
    die('Post ID is missing.');
}

$postId = trim((string)($_GET['id'] ?? $_GET['idPost']));
if ($postId === '' || strlen($postId) > 80 || !preg_match('/^[A-Za-z0-9_-]+$/', $postId)) {
    http_response_code(404);
    die('Invalid post ID.');
}

$post = $postC->showPost($postId);
if (!$post) {
    http_response_code(404);
    die('Post not found.');
}

$postId = (string)$post['id'];
$postC->incrementViews($postId);
$commentsTree = $commentC->getCommentsTreeByPost($postId);
$commentCount = $commentC->countCommentsByPost($postId);

$pageTitle   = $post['subject'];
$currentPage = 'actuality';
$frontActive = 'myspace';
$backUrl = './index.php';

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

<section class="py-5">
    <div class="container px-4 px-lg-5">

        <div class="mb-4">
            <a href="<?= htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn social-nav-btn">
                <i class="bi bi-arrow-left"></i> <span data-i18n="post.backActuality">Back to Actuality</span>
            </a>
        </div>

        <article class="social-post-card">
            <div class="social-post-header">
                <?= cre8_render_avatar($post['idCreateur'] ?? 0, (string)($post['creatorName'] ?? 'Creator'), 'social-post-avatar') ?>
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
                                data-action="like" aria-label="Like post" data-i18n-title="post.likePost">
                            <i class="bi bi-heart"></i>
                        </button>
                        <button type="button" class="icon-action-btn reaction-btn js-reaction-btn"
                                data-post-id="<?= htmlspecialchars($post['id']) ?>"
                                data-action="dislike" aria-label="Dislike post" data-i18n-title="post.dislikePost">
                            <i class="bi bi-hand-thumbs-down"></i>
                        </button>
                    </div>
                </div>
                <div class="reaction-summary js-post-comments-scope" data-post-id="<?= htmlspecialchars($post['id']) ?>" data-context="details">
                    <span><i class="bi bi-heart-fill"></i> <span class="js-like-count"><?= (int)$post['numberOfLike'] ?></span></span>
                    <span><i class="bi bi-hand-thumbs-down-fill"></i> <span class="js-dislike-count"><?= (int)$post['numberOfDislike'] ?></span></span>
                    <span class="view-meta"><i class="bi bi-eye"></i> <span class="js-view-count"><?= (int)$post['numberOfView'] ?></span> <span data-i18n="post.views">views</span></span>
                    <span><i class="bi bi-chat"></i> <span class="js-comment-count"><?= $commentCount ?></span> <span data-i18n="post.commentsCount"><?= $commentCount !== 1 ? 'comments' : 'comment' ?></span></span>
                </div>
            </div>
        </article>

        <div id="comments" class="comments-section-full js-post-comments-scope" data-post-id="<?= htmlspecialchars($postId) ?>" data-context="details">
            <h3 class="comments-section-title">
                <i class="bi bi-chat-dots"></i>
                <span data-i18n="post.comments">Comments</span> <span class="comment-count-badge js-comment-count"><?= $commentCount ?></span>
            </h3>

            <div class="comment-form-wrap mb-4">
                <?= cre8_render_avatar($idUser, $currentUserName, 'comment-avatar-sm') ?>
                <div class="comment-input-area">
                    <?php render_comment_form($postId, $postId, 'post', 'details', 'Write a comment...', 'Post'); ?>
                </div>
            </div>

            <div class="comments-list js-comments-list">
                <?php if (empty($commentsTree)) : ?>
                    <div class="no-comments-msg">
                        <i class="bi bi-chat-square" style="font-size:2rem;opacity:.4;"></i>
                        <p class="mt-2 mb-0" data-i18n="post.noCommentsLong">No comments yet. Be the first to comment!</p>
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

<script>
(function () {
    var detailsI18nObserver = null;
    var detailsI18nApplying = false;

    var translations = {
        en: {
            'post.backActuality': 'Back to Actuality',
            'post.likePost': 'Like post',
            'post.dislikePost': 'Dislike post',
            'post.views': 'views',
            'post.commentsCount': 'comments',
            'post.comments': 'Comments',
            'post.noCommentsLong': 'No comments yet. Be the first to comment!',
            'post.noCommentsShort': 'No comments yet.',
            'post.writeCommentPlaceholder': 'Write a comment...',
            'post.commentPlaceholder': 'Add a comment...',
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
            'post.voiceRecording': 'Listening... speak now'
        },
        fr: {
            'post.backActuality': 'Retour aux actualites',
            'post.likePost': 'Aimer le post',
            'post.dislikePost': 'Ne pas aimer le post',
            'post.views': 'vues',
            'post.commentsCount': 'commentaires',
            'post.comments': 'Commentaires',
            'post.noCommentsLong': 'Aucun commentaire pour le moment. Soyez le premier a commenter !',
            'post.noCommentsShort': 'Aucun commentaire pour le moment.',
            'post.writeCommentPlaceholder': 'Ecrire un commentaire...',
            'post.commentPlaceholder': 'Ajouter un commentaire...',
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
            'post.voiceRecording': 'Ecoute en cours... parlez maintenant'
        }
    };
    function reconnectDetailsObserver() {
        if (detailsI18nObserver && document.body) {
            detailsI18nObserver.observe(document.body, { childList: true, subtree: true });
        }
    }

    function applyExtraLabels() {
        if (detailsI18nApplying) {
            return;
        }
        detailsI18nApplying = true;
        if (detailsI18nObserver) {
            detailsI18nObserver.disconnect();
        }

        try {
            if (typeof window.cre8ApplyI18n === 'function') {
                window.cre8ApplyI18n(translations);
            }
            Array.prototype.forEach.call(document.querySelectorAll('[data-i18n-idle-label]'), function (el) {
                var lang = typeof window.cre8FrontReadLang === 'function' ? window.cre8FrontReadLang() : 'en';
                var value = translations[lang] && translations[lang][el.getAttribute('data-i18n-idle-label')];
                if (value) el.setAttribute('data-idle-label', value);
            });
            Array.prototype.forEach.call(document.querySelectorAll('[data-i18n-recording-label]'), function (el) {
                var lang = typeof window.cre8FrontReadLang === 'function' ? window.cre8FrontReadLang() : 'en';
                var value = translations[lang] && translations[lang][el.getAttribute('data-i18n-recording-label')];
                if (value) el.setAttribute('data-recording-label', value);
            });
        } finally {
            window.setTimeout(function () {
                detailsI18nApplying = false;
                reconnectDetailsObserver();
            }, 0);
        }
    }

    function registerPostTranslations() {
        if (typeof window.cre8RegisterTranslations === 'function') {
            window.cre8RegisterTranslations(translations);
        }
        if (!detailsI18nObserver && typeof MutationObserver !== 'undefined') {
            detailsI18nObserver = new MutationObserver(applyExtraLabels);
        }
        applyExtraLabels();
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', registerPostTranslations);
    } else {
        registerPostTranslations();
    }
    window.addEventListener('cre8:languagechange', applyExtraLabels);
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
