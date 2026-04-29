(function () {
    'use strict';

    const EMOJIS = ['😀','😂','🥰','😍','😢','😡','😮','🤔','😎','🥹','😭','🤣','😊','🙄','😴','😬','🤯','😏','🤩','🥳','👍','👎','👏','🙌','🤝','💪','👀','🫶','🤞','✌️','❤️','🔥','🎉','✨','💯','⭐','🌟','🎊','💔','💡'];
    const STICKERS = ['🎉','🔥','❤️','😂','👏','💯','🫶','😍','🤯','💔','🎊','✨'];

    /* ═══════════════════════════════════════════════
       VALIDATION CONFIG
    ═══════════════════════════════════════════════ */

    const MAX_COMMENT_LENGTH = 200;

    /**
     * Bad words list — normalized to lowercase.
     * Each entry is matched as a whole word (regex \b boundaries)
     * so "assassin" won't be blocked because of "ass".
     * Leet-speak variants (@ → a, 3 → e, etc.) are also handled
     * by normalizing the input before matching.
     */
    const BAD_WORDS = [
        // English
        'fuck', 'fucker', 'fucking', 'fucked', 'fck',
        'shit', 'shitty', 'bullshit',
        'bitch', 'bitches',
        'asshole', 'ass',
        'bastard',
        'cunt',
        
        'pussy',
        'whore',
        'slut',
        'nigger', 'nigga',
        'faggot', 'fag',
        'retard', 'retarded',
        'idiot', 'moron', 'imbecile',
        'damn', 'crap',
        // French
        'putain', 'pute',
        'merde',
        'salope', 'salaud',
        'connard', 'connasse', 'con',
        'encule', 'enculé', 'enculer',
        'fils de pute',
        'nique', 'niquer',
        'batard', 'bâtard',
        'pédé', 'pede',
        'gouine',
        'fdp',
        'tg',
    ];

    /**
     * Normalize text to defeat simple leet-speak bypasses:
     * h3ll0 → hello, @ss → ass, etc.
     */
    function normalizeLeet(text) {
        return text
            .toLowerCase()
            .replace(/@/g, 'a')
            .replace(/4/g, 'a')
            .replace(/3/g, 'e')
            .replace(/1/g, 'i')
            .replace(/!/g, 'i')
            .replace(/0/g, 'o')
            .replace(/\$/g, 's')
            .replace(/5/g, 's')
            .replace(/7/g, 't')
            .replace(/\+/g, 't')
            .replace(/8/g, 'b')
            .replace(/\|/g, 'l')
            .replace(/\*/g, '')
            .replace(/\./g, '')
            .replace(/-/g, '')
            .replace(/_/g, '')
            .replace(/\s+/g, ' ')
            .trim();
    }

    /**
     * Returns the matched bad word if found, null otherwise.
     * Uses whole-word \b boundaries where possible.
     * For multi-word phrases uses plain includes().
     */
    function findBadWord(text) {
        const normalized = normalizeLeet(text);

        for (const word of BAD_WORDS) {
            const normalizedWord = normalizeLeet(word);

            if (normalizedWord.includes(' ')) {
                if (normalized.includes(normalizedWord)) return word;
                continue;
            }

            try {
                const regex = new RegExp(`\\b${normalizedWord}\\b`, 'i');
                if (regex.test(normalized)) return word;
            } catch (_) {
                if (normalized.includes(normalizedWord)) return word;
            }
        }
        return null;
    }

    /* ═══════════════════════════════════════════════
       DOM HELPERS
    ═══════════════════════════════════════════════ */

    function getOrCreateCounter(textarea) {
        const area = textarea.closest('.comment-input-area');
        if (!area) return null;
        let counter = area.querySelector('.js-char-counter');
        if (!counter) {
            counter = document.createElement('div');
            counter.className = 'js-char-counter comment-char-counter';
            textarea.insertAdjacentElement('afterend', counter);
        }
        return counter;
    }

    function getOrCreateError(textarea) {
        const area = textarea.closest('.comment-input-area');
        if (!area) return null;
        let err = area.querySelector('.js-comment-error');
        if (!err) {
            err = document.createElement('div');
            err.className = 'js-comment-error comment-validation-error';
            err.setAttribute('role', 'alert');
            err.setAttribute('aria-live', 'polite');
            err.style.display = 'none';
            const toolbar = area.querySelector('.comment-form-toolbar');
            if (toolbar) toolbar.insertAdjacentElement('afterend', err);
            else textarea.insertAdjacentElement('afterend', err);
        }
        return err;
    }

    function updateCounter(textarea) {
        const counter = getOrCreateCounter(textarea);
        if (!counter) return;
        const len = textarea.value.length;
        const remaining = MAX_COMMENT_LENGTH - len;
        counter.textContent = `${len} / ${MAX_COMMENT_LENGTH}`;
        counter.classList.remove('is-warning', 'is-danger');
        if (remaining <= 0)       counter.classList.add('is-danger');
        else if (remaining <= 30) counter.classList.add('is-warning');
    }

    function showError(el, msg) {
        if (!el) return;
        el.textContent = msg;
        el.style.display = 'flex';
    }

    function hideError(el) {
        if (!el) return;
        el.textContent = '';
        el.style.display = 'none';
    }

    /* ═══════════════════════════════════════════════
       CORE VALIDATION
    ═══════════════════════════════════════════════ */

    function validateTextarea(textarea) {
        const err = getOrCreateError(textarea);
        const raw  = textarea.value;
        const text = raw.trim();

        if (text === '') {
            hideError(err);
            textarea.classList.remove('is-invalid', 'is-valid');
            return true;
        }

        if (raw.length > MAX_COMMENT_LENGTH) {
            showError(err, `⚠ Comment too long — maximum ${MAX_COMMENT_LENGTH} characters (${raw.length} entered).`);
            textarea.classList.add('is-invalid');
            textarea.classList.remove('is-valid');
            return false;
        }

        const matched = findBadWord(text);
        if (matched !== null) {
            showError(err, '🚫 Your comment contains inappropriate language. Please keep it respectful.');
            textarea.classList.add('is-invalid');
            textarea.classList.remove('is-valid');
            return false;
        }

        hideError(err);
        textarea.classList.remove('is-invalid');
        textarea.classList.add('is-valid');
        return true;
    }

    /* ═══════════════════════════════════════════════
       BIND VALIDATION
    ═══════════════════════════════════════════════ */

    function bindValidation(textarea) {
        if (textarea.dataset.validationBound === '1') return;
        textarea.dataset.validationBound = '1';
        updateCounter(textarea);
        textarea.addEventListener('input', () => {
            updateCounter(textarea);
            if (textarea.dataset.submitted === '1') validateTextarea(textarea);
        });
    }

    function bindValidationInRoot(root) {
        root.querySelectorAll('.comment-textarea').forEach(bindValidation);
    }

    /* ════════════════════════════════════════════
       ORIGINAL CODE — untouched below this line
    ════════════════════════════════════════════ */

    document.addEventListener('DOMContentLoaded', () => {
        initCommentUi();
    });

    function initCommentUi() {
        buildStickerBars();
        buildEmojiPickers();
        initAutoResize();
        initImagePreviews();
        initCommentReactions();
        initReplyAndEditToggles();
        initCommentModalButtons();
        initAjaxCommentForms();
        initDeleteCommentButtons();
        bindValidationInRoot(document);
    }

    function buildStickerBars(root = document) {
        root.querySelectorAll('.sticker-bar').forEach(bar => {
            if (bar.dataset.built === '1') return;
            bar.dataset.built = '1';
            const hiddenInput = bar.closest('form')?.querySelector('.js-sticker-input');
            const preSelected = bar.dataset.selected || hiddenInput?.value || '';
            STICKERS.forEach(sticker => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'sticker-btn' + (preSelected === sticker ? ' is-selected' : '');
                btn.textContent = sticker;
                btn.addEventListener('click', () => {
                    const selected = btn.classList.contains('is-selected');
                    bar.querySelectorAll('.sticker-btn').forEach(b => b.classList.remove('is-selected'));
                    if (!selected) {
                        btn.classList.add('is-selected');
                        if (hiddenInput) hiddenInput.value = sticker;
                    } else if (hiddenInput) {
                        hiddenInput.value = '';
                    }
                });
                bar.appendChild(btn);
            });
        });
    }

    function buildEmojiPickers(root = document) {
        root.querySelectorAll('.emoji-picker-panel').forEach(panel => {
            if (panel.dataset.built === '1') return;
            panel.dataset.built = '1';
            const textarea = panel.closest('.comment-input-area')?.querySelector('.comment-textarea');
            EMOJIS.forEach(em => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'emoji-btn';
                btn.textContent = em;
                btn.addEventListener('click', e => {
                    e.stopPropagation();
                    if (textarea) insertAtCursor(textarea, em);
                    panel.classList.remove('is-open');
                });
                panel.appendChild(btn);
            });
        });

        root.querySelectorAll('.btn-emoji-toggle').forEach(btn => {
            if (btn.dataset.bound === '1') return;
            btn.dataset.bound = '1';
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                const area = btn.closest('.comment-input-area');
                const panel = area?.querySelector('.emoji-picker-panel');
                if (panel) panel.classList.toggle('is-open');
            });
        });

        document.addEventListener('click', () => {
            document.querySelectorAll('.emoji-picker-panel.is-open').forEach(p => p.classList.remove('is-open'));
        }, { once: true });
    }

    function insertAtCursor(el, text) {
        const start = el.selectionStart ?? el.value.length;
        const end = el.selectionEnd ?? el.value.length;
        el.value = el.value.slice(0, start) + text + el.value.slice(end);
        el.selectionStart = el.selectionEnd = start + text.length;
        el.focus();
        el.dispatchEvent(new Event('input'));
    }

    function initAutoResize(root = document) {
        root.querySelectorAll('.comment-textarea').forEach(ta => {
            if (ta.dataset.bound === '1') return;
            ta.dataset.bound = '1';
            ta.addEventListener('input', autoResize);
            autoResize.call(ta);
        });
    }

    function autoResize() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 140) + 'px';
    }

    function initImagePreviews() {
        document.addEventListener('change', function (e) {
            const input = e.target.closest('.js-image-input');
            if (!input || !input.files?.[0]) return;
            const wrap = input.closest('.comment-input-area')?.querySelector('.img-preview-wrap');
            const preview = wrap?.querySelector('.js-img-preview');
            if (!wrap || !preview) return;
            const reader = new FileReader();
            reader.onload = ev => {
                preview.src = ev.target.result;
                wrap.style.display = '';
            };
            reader.readAsDataURL(input.files[0]);
        });

        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.btn-remove-preview');
            if (!btn) return;
            const wrap = btn.closest('.img-preview-wrap');
            const form = btn.closest('form');
            const input = form?.querySelector('.js-image-input');
            if (input) input.value = '';
            if (wrap) wrap.style.display = 'none';
        });
    }

    function initCommentReactions() {
        document.addEventListener('click', async function (e) {
            const btn = e.target.closest('.js-comment-reaction');
            if (!btn) return;
            e.preventDefault();
            const commentId = btn.dataset.commentId;
            const action = btn.dataset.action;
            const countEl = btn.querySelector(action === 'like' ? '.js-like-count' : '.js-dislike-count');
            try {
                const form = new URLSearchParams();
                form.set('id', commentId);
                const response = await fetch(`../comment/${action}c.php`, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: form.toString()
                });
                const data = await response.json();
                if (data.success && countEl) {
                    countEl.textContent = data.count;
                    btn.classList.add('is-active');
                }
            } catch (error) {
                console.error(error);
            }
        });
    }

    function initReplyAndEditToggles() {
        document.addEventListener('click', function (e) {
            const replyBtn = e.target.closest('.js-reply-toggle');
            if (replyBtn) {
                e.preventDefault();
                const target = document.getElementById(replyBtn.dataset.target);
                if (!target) return;
                target.classList.toggle('is-open');
                if (target.classList.contains('is-open')) {
                    initCommentUiInside(target);
                    target.querySelector('.comment-textarea')?.focus();
                }
                return;
            }

            const editBtn = e.target.closest('.btn-edit-comment');
            if (editBtn) {
                e.preventDefault();
                toggleEditForm(editBtn.dataset.commentId, true);
                return;
            }

            const cancelBtn = e.target.closest('.btn-cancel-edit');
            if (cancelBtn) {
                e.preventDefault();
                toggleEditForm(cancelBtn.dataset.commentId, false);
            }
        });
    }

    function toggleEditForm(commentId, open) {
        const bubble = document.getElementById('bubble-' + commentId);
        const editForm = document.getElementById('edit-form-' + commentId);
        if (!bubble || !editForm) return;
        bubble.style.display = open ? 'none' : '';
        editForm.classList.toggle('is-open', open);
        if (open) {
            initCommentUiInside(editForm);
            editForm.querySelector('.comment-textarea')?.focus();
        }
    }

    function initCommentModalButtons() {
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.js-open-comments-modal');
            if (!btn) return;
            e.preventDefault();
            const target = btn.dataset.bsTarget || btn.dataset.target;
            if (!target || typeof bootstrap === 'undefined') return;
            const modalEl = document.querySelector(target);
            if (!modalEl) return;
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        });
    }

    function initAjaxCommentForms() {
        document.addEventListener('submit', async function (e) {
            const form = e.target.closest('.js-comment-form, .js-comment-edit-form');
            if (!form) return;
            e.preventDefault();

            // Mark textarea as submitted so live validation activates
            const textarea = form.querySelector('.comment-textarea');
            if (textarea) {
                textarea.dataset.submitted = '1';
                if (!validateTextarea(textarea)) {
                    textarea.focus();
                    return;
                }
            }

            const postId = form.dataset.postId;
            const context = form.dataset.context || 'index';
            const submitBtn = form.querySelector('.btn-comment-submit');
            if (submitBtn) submitBtn.disabled = true;
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: new FormData(form)
                });
                const data = await response.json();
                if (!data.success) {
                    alert(data.message || 'Action failed.');
                    return;
                }
                form.reset();
                if (textarea) {
                    textarea.dataset.submitted = '0';
                    textarea.classList.remove('is-invalid', 'is-valid');
                    updateCounter(textarea);
                    hideError(getOrCreateError(textarea));
                }
                form.querySelectorAll('.sticker-btn').forEach(btn => btn.classList.remove('is-selected'));
                form.querySelectorAll('.img-preview-wrap').forEach(wrap => wrap.style.display = 'none');
                const removeImage = form.querySelector('input[name="removeImage"]');
                if (removeImage) removeImage.checked = false;
                const editBlock = form.closest('.comment-edit-form');
                if (editBlock && editBlock.id.startsWith('edit-form-')) {
                    const commentId = editBlock.id.replace('edit-form-', '');
                    toggleEditForm(commentId, false);
                }
                await refreshCommentsForPost(postId, context);
            } catch (error) {
                console.error(error);
                alert('Unable to save your comment right now.');
            } finally {
                if (submitBtn) submitBtn.disabled = false;
            }
        });
    }

    function initDeleteCommentButtons() {
        document.addEventListener('click', async function (e) {
            const btn = e.target.closest('.js-delete-comment');
            if (!btn) return;
            e.preventDefault();
            if (!confirm('Delete this comment and its replies?')) return;
            try {
                const form = new URLSearchParams();
                form.set('id', btn.dataset.commentId || '');
                form.set('postId', btn.dataset.postId || '');
                const response = await fetch('../comment/deletec.php', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: form.toString()
                });
                const data = await response.json();
                if (!data.success) {
                    alert(data.message || 'Unable to delete comment.');
                    return;
                }
                await refreshCommentsForPost(btn.dataset.postId, btn.dataset.context || 'index');
            } catch (error) {
                console.error(error);
                alert('Unable to delete comment right now.');
            }
        });
    }

    async function refreshCommentsForPost(postId, fallbackContext) {
        const scopes = document.querySelectorAll(`.js-post-comments-scope[data-post-id="${CSS.escape(postId)}"]`);
        if (!scopes.length) return;
        for (const scope of scopes) {
            const context = scope.dataset.context || fallbackContext || 'index';
            try {
                const response = await fetch(`../comment/thread.php?postId=${encodeURIComponent(postId)}&context=${encodeURIComponent(context)}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await response.json();
                if (!data.success) continue;
                scope.querySelectorAll('.js-comment-count').forEach(el => el.textContent = data.count);
                const preview = scope.querySelector('.js-comments-preview');
                const list = scope.querySelector('.js-comments-list');
                if (preview) preview.innerHTML = data.previewHtml;
                if (list) list.innerHTML = data.listHtml;
                initCommentUiInside(scope);
            } catch (error) {
                console.error(error);
            }
        }
    }

    function initCommentUiInside(root) {
        buildStickerBars(root);
        buildEmojiPickers(root);
        initAutoResize(root);
        bindValidationInRoot(root);
    }
})();
