<?php
$notificationController = $notificationController ?? null;
$notificationUserId = isset($notificationUserId) ? (int) $notificationUserId : 0;
$notificationItemsAll = [];
$notificationItemsUnread = [];
$notificationUnreadCount = 0;
$notificationActionUrl = $notificationActionUrl ?? '../layout/notification_actions.php';
$notificationPageUrl = $notificationPageUrl ?? null;
if (!$notificationPageUrl) {
    if (!empty($frontBaseUrl)) {
        $notificationPageUrl = rtrim((string) $frontBaseUrl, '/') . '/notifications/index.php';
    } else {
        $notificationCurrentPath = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');
        $notificationMarker = '/Vue/FrontOffice/';
        $notificationPos = strpos($notificationCurrentPath, $notificationMarker);
        if ($notificationPos !== false) {
            $notificationProjectBase = substr($notificationCurrentPath, 0, $notificationPos);
            $notificationPageUrl = $notificationProjectBase . '/Vue/FrontOffice/notifications/index.php';
        } else {
            $notificationPageUrl = '../notifications/index.php';
        }
    }
}

if ($notificationController && $notificationUserId > 0) {
    if (method_exists($notificationController, 'getNotificationsForUser')) {
        $notificationItemsAll = $notificationController->getNotificationsForUser($notificationUserId, 20, false);
        $notificationItemsUnread = $notificationController->getNotificationsForUser($notificationUserId, 20, true);
        $notificationUnreadCount = method_exists($notificationController, 'countUnread')
            ? $notificationController->countUnread($notificationUserId)
            : 0;
    } elseif (method_exists($notificationController, 'getNotificationActionsByUser')) {
        $notificationItemsAll = $notificationController->getNotificationActionsByUser($notificationUserId, false, 20);
        $notificationItemsUnread = $notificationController->getNotificationActionsByUser($notificationUserId, true, 20);
        $notificationUnreadCount = method_exists($notificationController, 'countUnreadNotificationActions')
            ? $notificationController->countUnreadNotificationActions($notificationUserId)
            : 0;
    }
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

if (!function_exists('cre8_notification_icon')) {
    function cre8_notification_icon($typeAction): string
    {
        return match ((string) $typeAction) {
            'post_comment' => 'bi-chat-dots',
            'post_reaction' => 'bi-heart',
            'offer_invitation',
            'offer_accepted',
            'offer_refused',
            'candidature_received',
            'candidature_accepted',
            'candidature_refused',
            'negotiation_message' => 'bi-briefcase',
            'admin_post_removed',
            'admin_product_removed' => 'bi-shield-exclamation',
            'complaint_answered' => 'bi-life-preserver',
            'event_today' => 'bi-calendar-event',
            default => 'bi-bell',
        };
    }
}

if (!function_exists('cre8_notification_group_class')) {
    function cre8_notification_group_class($typeAction): string
    {
        return match ((string) $typeAction) {
            'post_comment',
            'post_reaction' => 'type-post',
            'offer_invitation',
            'offer_accepted',
            'offer_refused',
            'candidature_received',
            'candidature_accepted',
            'candidature_refused',
            'negotiation_message' => 'type-collaboration',
            'admin_post_removed',
            'admin_product_removed' => 'type-admin',
            'complaint_answered' => 'type-complaint',
            'event_today' => 'type-event',
            default => 'type-default',
        };
    }
}

if (!function_exists('cre8RenderNotificationItems')) {
    function cre8RenderNotificationItems(array $items)
    {
        if (empty($items)) {
            ?>
            <div class="notification-empty" data-i18n="notifications.empty">No notifications yet.</div>
            <?php
            return;
        }

        foreach ($items as $item):
            $isUnread = (int) ($item['estLu'] ?? 0) === 0;
            $link = trim((string) ($item['lien'] ?? ''));
            $typeAction = (string) ($item['typeAction'] ?? '');
            $typeClass = cre8_notification_group_class($typeAction);
            $iconClass = cre8_notification_icon($typeAction);
            ?>
            <article class="notification-item front-notification-row <?php echo htmlspecialchars($typeClass); ?><?php echo $isUnread ? ' is-unread' : ''; ?>" data-notification-item data-notification-id="<?php echo (int) ($item['idNotificationAction'] ?? 0); ?>">
                <span class="notification-type-icon" aria-hidden="true">
                    <i class="bi <?php echo htmlspecialchars($iconClass); ?>"></i>
                    <span class="notification-dot"></span>
                </span>
                <div class="notification-item-body">
                    <strong><?php echo htmlspecialchars((string) ($item['titre'] ?? 'Notification')); ?></strong>
                    <p><?php echo htmlspecialchars((string) ($item['message'] ?? '')); ?></p>
                    <time><?php echo htmlspecialchars(cre8NotificationDateLabel($item['dateCreation'] ?? null)); ?></time>
                    <div class="notification-item-actions">
                        <?php if ($link !== ''): ?>
                            <a class="notification-open-link" href="<?php echo htmlspecialchars($link); ?>" data-i18n="notifications.open">Open</a>
                        <?php endif; ?>
                        <?php if ($isUnread): ?>
                            <form method="post" action="<?php echo htmlspecialchars((string) $notificationActionUrl); ?>" class="notification-inline-form" data-notification-read-form data-notification-id="<?php echo (int) ($item['idNotificationAction'] ?? 0); ?>">
                                <input type="hidden" name="notificationAction" value="mark_one">
                                <input type="hidden" name="notificationId" value="<?php echo (int) ($item['idNotificationAction'] ?? 0); ?>">
                                <button type="submit" data-i18n="notifications.markRead">Mark read</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        <?php endforeach;
    }
}
?>
<?php if (!defined('CRE8_NOTIFICATION_DROPDOWN_LAYOUT_STYLE')): ?>
    <?php define('CRE8_NOTIFICATION_DROPDOWN_LAYOUT_STYLE', true); ?>
    <style>
        .notification-widget-front .front-notification-dropdown {
            display: flex !important;
            flex-direction: column !important;
            width: min(420px, calc(100vw - 24px)) !important;
            max-height: min(620px, calc(100vh - 110px)) !important;
            overflow: hidden !important;
            opacity: 1 !important;
            visibility: visible !important;
            pointer-events: auto !important;
            transform: none !important;
        }

        .notification-widget-front .front-notification-dropdown[hidden],
        .notification-widget-front .front-notification-list[hidden] {
            display: none !important;
        }

        .notification-widget-front .front-notification-head,
        .notification-widget-front .front-notification-tabs,
        .notification-widget-front .front-notification-footer {
            flex-shrink: 0 !important;
        }

        .notification-widget-front .front-notification-list {
            flex: 1 1 auto !important;
            min-height: 0 !important;
            max-height: none !important;
            overflow-y: auto !important;
        }

        .notification-widget-front .front-notification-footer {
            display: block !important;
            position: sticky !important;
            bottom: 0 !important;
            z-index: 2 !important;
            border-top: 1px solid var(--border) !important;
            padding: 12px 14px !important;
            background: var(--white) !important;
        }

        @media (max-width: 768px) {
            .notification-widget-front .front-notification-dropdown {
                width: calc(100vw - 24px) !important;
                max-height: calc(100vh - 110px) !important;
            }
        }
    </style>
<?php endif; ?>
<div class="notification-widget notification-widget-front" data-notification-widget>
    <button type="button" class="notification-bell" data-notification-toggle aria-label="Open notifications" title="Open notifications" data-i18n-title="notifications.openNotifications">
        <span class="notification-bell-glyph" aria-hidden="true"><i class="bi bi-bell"></i></span>
        <span class="notification-count" data-notification-count<?php echo $notificationUnreadCount > 0 ? '' : ' hidden'; ?>><?php echo $notificationUnreadCount > 99 ? '99+' : (int) $notificationUnreadCount; ?></span>
    </button>

    <section class="notification-panel front-notification-dropdown" data-notification-panel hidden>
        <div class="notification-panel-head front-notification-head">
            <div>
                <strong data-i18n="notifications.title">Notifications</strong>
                <span data-notification-unread-label><span data-notification-unread-number><?php echo (int) $notificationUnreadCount; ?></span> <span data-i18n="notifications.unread">unread</span></span>
            </div>
            <form method="post" action="<?php echo htmlspecialchars((string) $notificationActionUrl); ?>" data-notification-read-all-form>
                <input type="hidden" name="notificationAction" value="mark_all">
                <button type="submit" <?php echo $notificationUnreadCount <= 0 ? 'disabled' : ''; ?> data-i18n="notifications.markAllRead">Mark all as read</button>
            </form>
        </div>

        <div class="notification-tabs front-notification-tabs" role="tablist" aria-label="Notification filters">
            <button type="button" class="is-active" data-notification-tab="all" data-i18n="notifications.all">All</button>
            <button type="button" data-notification-tab="unread" data-i18n="notifications.unreadTab">Unread</button>
        </div>

        <div class="notification-list front-notification-list" data-notification-list="all">
            <?php cre8RenderNotificationItems($notificationItemsAll); ?>
        </div>
        <div class="notification-list front-notification-list" data-notification-list="unread" hidden>
            <?php cre8RenderNotificationItems($notificationItemsUnread); ?>
        </div>

        <div class="notification-panel-foot front-notification-footer">
            <a class="notification-view-all" href="<?php echo htmlspecialchars((string) $notificationPageUrl); ?>">
                <span data-i18n="notifications.viewAll">View all notifications</span> <i class="bi bi-arrow-right-short" aria-hidden="true"></i>
            </a>
        </div>
    </section>
</div>
<script>
(() => {
    const notificationTranslations = {
        en: {
            'notifications.title': 'Notifications',
            'notifications.unread': 'unread',
            'notifications.markAllRead': 'Mark all as read',
            'notifications.all': 'All',
            'notifications.unreadTab': 'Unread',
            'notifications.open': 'Open',
            'notifications.markRead': 'Mark read',
            'notifications.viewAll': 'View all notifications',
            'notifications.empty': 'No notifications yet.',
            'notifications.openNotifications': 'Open notifications'
        },
        fr: {
            'notifications.title': 'Notifications',
            'notifications.unread': 'non lues',
            'notifications.markAllRead': 'Tout marquer comme lu',
            'notifications.all': 'Toutes',
            'notifications.unreadTab': 'Non lues',
            'notifications.open': 'Ouvrir',
            'notifications.markRead': 'Marquer comme lu',
            'notifications.viewAll': 'Voir toutes les notifications',
            'notifications.empty': 'Aucune notification pour le moment.',
            'notifications.openNotifications': 'Ouvrir les notifications'
        }
    };

    function getNotificationLang() {
        if (typeof window.cre8FrontReadLang === 'function') {
            return window.cre8FrontReadLang();
        }
        try {
            const stored = localStorage.getItem('cre8_front_lang') || localStorage.getItem('cre8_lang');
            return stored === 'fr' ? 'fr' : 'en';
        } catch (error) {
            return 'en';
        }
    }

    function applyNotificationTranslations(root) {
        const lang = getNotificationLang();
        const dict = notificationTranslations[lang] || notificationTranslations.en;
        root.querySelectorAll('[data-i18n]').forEach((element) => {
            const key = element.getAttribute('data-i18n');
            if (Object.prototype.hasOwnProperty.call(dict, key)) {
                element.textContent = dict[key];
            }
        });
        root.querySelectorAll('[data-i18n-title]').forEach((element) => {
            const key = element.getAttribute('data-i18n-title');
            if (Object.prototype.hasOwnProperty.call(dict, key)) {
                element.setAttribute('title', dict[key]);
                if (element.hasAttribute('aria-label')) {
                    element.setAttribute('aria-label', dict[key]);
                }
            }
        });
    }

    document.querySelectorAll('[data-notification-widget]').forEach((widget) => {
        if (widget.dataset.notificationReady === '1') {
            return;
        }
        widget.dataset.notificationReady = '1';
        applyNotificationTranslations(widget);
        window.addEventListener('cre8:languagechange', () => applyNotificationTranslations(widget));
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
                const unreadNumber = unreadLabel.querySelector('[data-notification-unread-number]');
                if (unreadNumber) {
                    unreadNumber.textContent = String(unreadCount);
                }
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
                unreadList.innerHTML = '<div class="notification-empty" data-i18n="notifications.empty">No notifications yet.</div>';
                applyNotificationTranslations(unreadList);
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

        function markVisibleUnreadInUi(ids, nextUnreadCount) {
            const idSet = new Set(ids.map(String));
            widget.querySelectorAll('[data-notification-item]').forEach((item) => {
                if (!idSet.has(String(item.dataset.notificationId))) {
                    return;
                }
                item.classList.remove('is-unread');
                const form = item.querySelector('[data-notification-read-form]');
                if (form) {
                    form.remove();
                }
            });
            updateUnreadCount(Number.isFinite(nextUnreadCount) ? nextUnreadCount : Math.max(0, unreadCount - ids.length));
            ensureUnreadEmptyState();
        }

        function markVisibleUnreadAsRead() {
            const visibleUnreadItems = Array.from(widget.querySelectorAll('[data-notification-list]:not([hidden]) [data-notification-item].is-unread'));
            const ids = visibleUnreadItems
                .map((item) => Number.parseInt(item.dataset.notificationId || '0', 10))
                .filter((id) => id > 0);

            if (ids.length === 0) {
                return;
            }

            const formData = new FormData();
            formData.append('notificationAction', 'mark_visible');
            ids.forEach((id) => formData.append('notificationIds[]', String(id)));

            fetch(<?php echo json_encode((string) $notificationActionUrl); ?>, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then((response) => response.json())
                .then((response) => {
                    if (!response || response.success !== true) {
                        throw new Error('Notification update failed.');
                    }
                    markVisibleUnreadInUi(ids, Number.parseInt(response.unreadCount, 10));
                })
                .catch(() => {});
        }

        function submitNotificationForm(form, onSuccess) {
            const submitButton = form.querySelector('button[type="submit"], button:not([type])');
            if (submitButton) {
                submitButton.disabled = true;
            }
            fetch(form.getAttribute('action') || <?php echo json_encode((string) $notificationActionUrl); ?>, {
                method: 'POST',
                body: new FormData(form),
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then((response) => response.json())
                .then((response) => {
                    if (!response || response.success !== true) {
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
            const willOpen = panel.hidden;
            panel.hidden = !panel.hidden;
            if (willOpen) {
                markVisibleUnreadAsRead();
            }
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
