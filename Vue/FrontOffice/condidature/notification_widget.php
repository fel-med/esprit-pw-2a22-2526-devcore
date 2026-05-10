<?php
$notificationController = $notificationController ?? null;
$notificationUserId = isset($notificationUserId) ? (int) $notificationUserId : 0;
$notificationItemsAll = [];
$notificationItemsUnread = [];
$notificationUnreadCount = 0;

if ($notificationController && $notificationUserId > 0) {
    $notificationItemsAll = $notificationController->getNotificationActionsByUser($notificationUserId, false, 10);
    $notificationItemsUnread = $notificationController->getNotificationActionsByUser($notificationUserId, true, 10);
    $notificationUnreadCount = $notificationController->countUnreadNotificationActions($notificationUserId);
}

if (!function_exists('cre8NotificationDateLabel')) {
    function cre8NotificationDateLabel($value)
    {
        if (!$value) {
            return 'Just now';
        }

        $timestamp = strtotime((string) $value);
        if ($timestamp === false) {
            return (string) $value;
        }

        return date('Y-m-d H:i', $timestamp);
    }
}

if (!function_exists('cre8RenderNotificationItems')) {
    function cre8RenderNotificationItems(array $items)
    {
        if (empty($items)) {
            ?>
            <div class="notification-empty">No notifications yet.</div>
            <?php
            return;
        }

        foreach ($items as $item):
            $isUnread = (int) ($item['estLu'] ?? 0) === 0;
            $link = trim((string) ($item['lien'] ?? ''));
            ?>
            <article class="notification-item<?php echo $isUnread ? ' is-unread' : ''; ?>" data-notification-item data-notification-id="<?php echo (int) ($item['idNotificationAction'] ?? 0); ?>">
                <span class="notification-dot" aria-hidden="true"></span>
                <div class="notification-item-body">
                    <strong><?php echo htmlspecialchars((string) ($item['titre'] ?? 'Notification')); ?></strong>
                    <p><?php echo htmlspecialchars((string) ($item['message'] ?? '')); ?></p>
                    <time><?php echo htmlspecialchars(cre8NotificationDateLabel($item['dateCreation'] ?? null)); ?></time>
                    <div class="notification-item-actions">
                        <?php if ($link !== ''): ?>
                            <a href="<?php echo htmlspecialchars($link); ?>">Open</a>
                        <?php endif; ?>
                        <?php if ($isUnread): ?>
                            <form method="post" class="notification-inline-form" data-notification-read-form data-notification-id="<?php echo (int) ($item['idNotificationAction'] ?? 0); ?>">
                                <input type="hidden" name="notificationAction" value="mark_one">
                                <input type="hidden" name="idNotificationAction" value="<?php echo (int) ($item['idNotificationAction'] ?? 0); ?>">
                                <button type="submit">Mark read</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        <?php endforeach;
    }
}
?>
<div class="notification-widget notification-widget-front" data-notification-widget>
    <button type="button" class="notification-bell" data-notification-toggle aria-label="Open notifications">
        <span class="notification-bell-glyph" aria-hidden="true">&#128276;</span>
        <span class="notification-count" data-notification-count<?php echo $notificationUnreadCount > 0 ? '' : ' hidden'; ?>><?php echo $notificationUnreadCount > 99 ? '99+' : (int) $notificationUnreadCount; ?></span>
    </button>

    <section class="notification-panel" data-notification-panel hidden>
        <div class="notification-panel-head">
            <div>
                <strong>Notifications</strong>
                <span data-notification-unread-label><?php echo (int) $notificationUnreadCount; ?> unread</span>
            </div>
            <form method="post" data-notification-read-all-form>
                <input type="hidden" name="notificationAction" value="mark_all">
                <button type="submit" <?php echo $notificationUnreadCount <= 0 ? 'disabled' : ''; ?>>Mark all as read</button>
            </form>
        </div>

        <div class="notification-tabs" role="tablist" aria-label="Notification filters">
            <button type="button" class="is-active" data-notification-tab="all">All</button>
            <button type="button" data-notification-tab="unread">Unread</button>
        </div>

        <div class="notification-list" data-notification-list="all">
            <?php cre8RenderNotificationItems($notificationItemsAll); ?>
        </div>
        <div class="notification-list" data-notification-list="unread" hidden>
            <?php cre8RenderNotificationItems($notificationItemsUnread); ?>
        </div>
    </section>
</div>
<script>
(() => {
    document.querySelectorAll('[data-notification-widget]').forEach((widget) => {
        if (widget.dataset.notificationReady === '1') {
            return;
        }
        widget.dataset.notificationReady = '1';
        const toggle = widget.querySelector('[data-notification-toggle]');
        const panel = widget.querySelector('[data-notification-panel]');
        const tabs = widget.querySelectorAll('[data-notification-tab]');
        const lists = widget.querySelectorAll('[data-notification-list]');
        const countBadge = widget.querySelector('[data-notification-count]');
        const unreadLabel = widget.querySelector('[data-notification-unread-label]');
        const markAllForm = widget.querySelector('[data-notification-read-all-form]');
        let unreadCount = Number.parseInt(countBadge ? countBadge.textContent : '<?php echo (int) $notificationUnreadCount; ?>', 10);

        function formatCount(value) {
            return value > 99 ? '99+' : String(Math.max(0, value));
        }

        function updateUnreadCount(nextCount) {
            unreadCount = Math.max(0, nextCount);
            if (countBadge) {
                countBadge.textContent = formatCount(unreadCount);
                countBadge.hidden = unreadCount <= 0;
            }
            if (unreadLabel) {
                unreadLabel.textContent = unreadCount + ' unread';
            }
            if (markAllForm) {
                const button = markAllForm.querySelector('button');
                if (button) {
                    button.disabled = unreadCount <= 0;
                }
            }
        }

        function ensureUnreadEmptyState() {
            const unreadList = widget.querySelector('[data-notification-list="unread"]');
            if (!unreadList || unreadList.querySelector('[data-notification-item]')) {
                return;
            }
            if (!unreadList.querySelector('.notification-empty')) {
                unreadList.innerHTML = '<div class="notification-empty">No notifications yet.</div>';
            }
        }

        function markItemReadInUi(notificationId) {
            let changed = false;
            widget.querySelectorAll('[data-notification-item]').forEach((item) => {
                if (String(item.dataset.notificationId) !== String(notificationId)) {
                    return;
                }
                if (item.classList.contains('is-unread')) {
                    changed = true;
                }
                item.classList.remove('is-unread');
                const form = item.querySelector('[data-notification-read-form]');
                if (form) {
                    form.remove();
                }
                if (item.closest('[data-notification-list="unread"]')) {
                    item.remove();
                }
            });
            if (changed) {
                updateUnreadCount(unreadCount - 1);
            }
            ensureUnreadEmptyState();
        }

        function markAllReadInUi() {
            widget.querySelectorAll('[data-notification-item]').forEach((item) => {
                item.classList.remove('is-unread');
                const form = item.querySelector('[data-notification-read-form]');
                if (form) {
                    form.remove();
                }
                if (item.closest('[data-notification-list="unread"]')) {
                    item.remove();
                }
            });
            updateUnreadCount(0);
            ensureUnreadEmptyState();
        }

        function submitNotificationForm(form, onSuccess) {
            const submitButton = form.querySelector('button[type="submit"], button:not([type])');
            if (submitButton) {
                submitButton.disabled = true;
            }
            fetch(window.location.href, {
                method: 'POST',
                body: new FormData(form),
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error('Notification update failed.');
                    }
                    onSuccess();
                })
                .catch(() => {
                    if (submitButton) {
                        submitButton.disabled = false;
                    }
                });
        }

        if (!toggle || !panel) {
            return;
        }

        toggle.addEventListener('click', (event) => {
            event.stopPropagation();
            panel.hidden = !panel.hidden;
        });

        panel.addEventListener('click', (event) => {
            event.stopPropagation();
        });

        tabs.forEach((tab) => {
            tab.addEventListener('click', () => {
                const target = tab.dataset.notificationTab;
                tabs.forEach((item) => item.classList.toggle('is-active', item === tab));
                lists.forEach((list) => {
                    list.hidden = list.dataset.notificationList !== target;
                });
            });
        });

        widget.querySelectorAll('[data-notification-read-form]').forEach((form) => {
            form.addEventListener('submit', (event) => {
                event.preventDefault();
                const notificationId = form.dataset.notificationId;
                submitNotificationForm(form, () => markItemReadInUi(notificationId));
            });
        });

        if (markAllForm) {
            markAllForm.addEventListener('submit', (event) => {
                event.preventDefault();
                submitNotificationForm(markAllForm, markAllReadInUi);
            });
        }

        document.addEventListener('click', () => {
            panel.hidden = true;
        });
    });
})();
</script>
