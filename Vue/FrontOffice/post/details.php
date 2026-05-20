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

<script src="../layout/front-translate.js"></script>
<script>
(function () {
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
    function applyExtraLabels() {
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
    }
    function registerPostTranslations() {
        if (typeof window.cre8RegisterTranslations === 'function') {
            window.cre8RegisterTranslations(translations);
        }
        applyExtraLabels();
        new MutationObserver(applyExtraLabels).observe(document.body, { childList: true, subtree: true });
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
