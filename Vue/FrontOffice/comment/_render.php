<?php
if (!function_exists('comment_escape')) {
    function comment_escape($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('comment_user_initial')) {
    function comment_user_initial(array $comment): string
    {
        return strtoupper(substr((string)($comment['userName'] ?? 'U'), 0, 1));
    }
}

if (!function_exists('render_comment_form')) {
    function render_comment_form(string $postId, string $targetId, string $targetType, string $context, string $placeholder = 'Add a comment...', string $submitLabel = 'Post'): void
    {
        ?>
        <form action="../comment/createc.php" method="POST" enctype="multipart/form-data" class="js-comment-form" data-post-id="<?= comment_escape($postId) ?>" data-context="<?= comment_escape($context) ?>">
            <input type="hidden" name="postId" value="<?= comment_escape($postId) ?>">
            <input type="hidden" name="targetId" value="<?= comment_escape($targetId) ?>">
            <input type="hidden" name="targetType" value="<?= comment_escape($targetType) ?>">
            <input type="hidden" name="from" value="<?= comment_escape($context) ?>">
            <input type="hidden" name="sticker" class="js-sticker-input" value="">

            <textarea name="text" class="comment-textarea" placeholder="<?= comment_escape($placeholder) ?>" rows="2"></textarea>
            <div class="sticker-bar"></div>
            <div class="comment-form-toolbar">
                <button type="button" class="btn-form-icon btn-emoji-toggle" title="Emoji"><i class="bi bi-emoji-smile"></i></button>
                <label class="btn-form-icon" title="Add image" style="cursor:pointer;display:inline-flex;align-items:center;">
                    <i class="bi bi-image"></i>
                    <input type="file" name="image" accept="image/*" class="d-none js-image-input">
                </label>
                <button type="submit" class="btn-comment-submit"><i class="bi bi-send"></i> <?= comment_escape($submitLabel) ?></button>
            </div>
            <div class="emoji-picker-panel"></div>
            <div class="img-preview-wrap" style="display:none;">
                <img src="" alt="preview" class="js-img-preview">
                <button type="button" class="btn-remove-preview">×</button>
            </div>
        </form>
        <?php
    }
}

if (!function_exists('render_edit_comment_form')) {
    function render_edit_comment_form(array $comment, string $postId, string $context): void
    {
        $commentId = (string)($comment['id'] ?? '');
        $selectedSticker = (string)($comment['Sticker'] ?? $comment['sticker'] ?? '');
        $image = (string)($comment['image'] ?? '');
        ?>
        <div class="comment-edit-form" id="edit-form-<?= comment_escape($commentId) ?>">
            <form action="../comment/editc.php" method="POST" enctype="multipart/form-data" class="js-comment-edit-form" data-post-id="<?= comment_escape($postId) ?>" data-context="<?= comment_escape($context) ?>">
                <input type="hidden" name="id" value="<?= comment_escape($commentId) ?>">
                <input type="hidden" name="postId" value="<?= comment_escape($postId) ?>">
                <input type="hidden" name="from" value="<?= comment_escape($context) ?>">
                <input type="hidden" name="sticker" class="js-sticker-input" value="<?= comment_escape($selectedSticker) ?>">

                <textarea name="text" class="comment-textarea" rows="3" placeholder="Edit your comment..."><?= comment_escape($comment['text'] ?? '') ?></textarea>
                <div class="sticker-bar" data-selected="<?= comment_escape($selectedSticker) ?>"></div>

                <?php if ($image !== '') : ?>
                    <div class="comment-current-image mb-2">
                        <img src="../../public/<?= comment_escape($image) ?>" alt="current image" class="comment-image">
                        <label class="comment-inline-check mt-2">
                            <input type="checkbox" name="removeImage" value="1"> Remove current image
                        </label>
                    </div>
                <?php endif; ?>

                <div class="comment-form-toolbar">
                    <button type="button" class="btn-form-icon btn-emoji-toggle" title="Emoji"><i class="bi bi-emoji-smile"></i></button>
                    <label class="btn-form-icon" title="Replace image" style="cursor:pointer;display:inline-flex;align-items:center;">
                        <i class="bi bi-image"></i>
                        <input type="file" name="image" accept="image/*" class="d-none js-image-input">
                    </label>
                    <button type="submit" class="btn-comment-submit"><i class="bi bi-check2-circle"></i> Save</button>
                    <button type="button" class="btn-comment-secondary btn-cancel-edit" data-comment-id="<?= comment_escape($commentId) ?>">Cancel</button>
                </div>
                <div class="emoji-picker-panel"></div>
                <div class="img-preview-wrap" style="display:none;">
                    <img src="" alt="preview" class="js-img-preview">
                    <button type="button" class="btn-remove-preview">×</button>
                </div>
            </form>
        </div>
        <?php
    }
}

if (!function_exists('render_comment_tree_node')) {
    function render_comment_tree_node(array $comment, string $postId, string $context, int $currentUserId = 1, int $depth = 0): void
    {
        $commentId = (string)($comment['id'] ?? '');
        $margin = min($depth * 24, 96);
        $owned = (int)($comment['idUser'] ?? 0) === $currentUserId;
        ?>
        <div class="comment-item" id="comment-item-<?= comment_escape($commentId) ?>" style="margin-left: <?= (int)$margin ?>px;">
            <div class="comment-avatar-sm"><?= comment_escape(comment_user_initial($comment)) ?></div>
            <div style="flex:1;min-width:0;">
                <div class="comment-bubble" id="bubble-<?= comment_escape($commentId) ?>">
                    <div class="comment-author"><?= comment_escape($comment['userName'] ?? ('User #' . ($comment['idUser'] ?? ''))) ?></div>

                    <?php if (!empty($comment['Sticker']) || !empty($comment['sticker'])) : ?>
                        <span class="comment-sticker"><?= comment_escape($comment['Sticker'] ?? $comment['sticker']) ?></span>
                    <?php endif; ?>

                    <?php if (!empty($comment['text'])) : ?>
                        <p class="comment-text"><?= nl2br(comment_escape($comment['text'])) ?></p>
                    <?php endif; ?>

                    <?php if (!empty($comment['image'])) : ?>
                        <img src="../../public/<?= comment_escape($comment['image']) ?>" alt="comment image" class="comment-image">
                    <?php endif; ?>
                </div>

                <?php render_edit_comment_form($comment, $postId, $context); ?>

                <div class="comment-meta-row">
                    <button type="button" class="btn-comment-reaction js-comment-reaction" data-comment-id="<?= comment_escape($commentId) ?>" data-action="like">
                        <i class="bi bi-heart"></i>
                        <span class="js-reaction-count js-like-count"><?= (int)($comment['numberOfLike'] ?? 0) ?></span>
                    </button>

                    <button type="button" class="btn-comment-reaction js-comment-reaction" data-comment-id="<?= comment_escape($commentId) ?>" data-action="dislike">
                        <i class="bi bi-hand-thumbs-down"></i>
                        <span class="js-reaction-count js-dislike-count"><?= (int)($comment['numberOfDislike'] ?? 0) ?></span>
                    </button>

                    <button type="button" class="btn-comment-action js-reply-toggle" data-target="reply-form-<?= comment_escape($commentId) ?>">
                        <i class="bi bi-reply"></i> Reply
                    </button>

                    <?php if ($owned) : ?>
                        <button type="button" class="btn-comment-action edit btn-edit-comment" data-comment-id="<?= comment_escape($commentId) ?>"><i class="bi bi-pencil-square"></i> Edit</button>
                        <button type="button" class="btn-comment-action delete js-delete-comment" data-comment-id="<?= comment_escape($commentId) ?>" data-post-id="<?= comment_escape($postId) ?>" data-context="<?= comment_escape($context) ?>"><i class="bi bi-trash3"></i> Delete</button>
                    <?php endif; ?>
                </div>

                <div class="comment-edit-form" id="reply-form-<?= comment_escape($commentId) ?>">
                    <?php render_comment_form($postId, $commentId, 'comment', $context, 'Reply to this comment...', 'Reply'); ?>
                </div>

                <?php if (!empty($comment['replies'])) : ?>
                    <?php foreach ($comment['replies'] as $reply) : ?>
                        <?php render_comment_tree_node($reply, $postId, $context, $currentUserId, $depth + 1); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}

if (!function_exists('render_preview_comments')) {
    function render_preview_comments(array $previewComments): void
    {
        if (empty($previewComments)) {
            echo '<div class="text-muted small">No comments yet.</div>';
            return;
        }

        foreach ($previewComments as $preview) {
            ?>
            <div class="comment-item mb-2">
                <div class="comment-avatar-sm"><?= comment_escape(comment_user_initial($preview)) ?></div>
                <div style="flex:1;min-width:0;">
                    <div class="comment-bubble">
                        <div class="comment-author"><?= comment_escape($preview['userName'] ?? 'User') ?></div>
                        <?php if (!empty($preview['text'])) : ?>
                            <p class="comment-text mb-0"><?= nl2br(comment_escape($preview['text'])) ?></p>
                        <?php endif; ?>
                        <?php if (empty($preview['text']) && (!empty($preview['Sticker']) || !empty($preview['sticker']))) : ?>
                            <span class="comment-sticker"><?= comment_escape($preview['Sticker'] ?? $preview['sticker']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php
        }
    }
}
