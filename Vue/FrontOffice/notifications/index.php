<?php
require_once __DIR__ . '/../layout/session_bridge.php';
require_once __DIR__ . '/../../../Controleur/notificationC.php';

$currentPath = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');
$frontOfficeMarker = '/Vue/FrontOffice/';
$frontOfficePos = strpos($currentPath, $frontOfficeMarker);
$projectBase = $frontOfficePos !== false ? substr($currentPath, 0, $frontOfficePos) : '';
$frontBaseUrl = $projectBase . '/Vue/FrontOffice';

if (function_exists('cre8_front_require_user')) {
    cre8_front_require_user();
}

$currentFrontUser = function_exists('cre8_front_session_user') ? cre8_front_session_user() : [];
$notificationUserId = (int) ($currentFrontUser['id'] ?? $_SESSION['id'] ?? ($_SESSION['user']['id'] ?? 0));

if ($notificationUserId <= 0) {
    header('Location: ' . $frontBaseUrl . '/utilisateur/login.php');
    exit;
}

$frontActive = 'notifications';
$notificationController = new NotificationC();

$status = strtolower((string) ($_GET['status'] ?? 'all'));
$category = strtolower((string) ($_GET['category'] ?? 'all'));
$allowedStatuses = ['all', 'unread', 'read'];
$allowedCategories = ['all', 'posts', 'collaboration', 'admin', 'complaints', 'events'];
$status = in_array($status, $allowedStatuses, true) ? $status : 'all';
$category = in_array($category, $allowedCategories, true) ? $category : 'all';

$notifications = method_exists($notificationController, 'getNotificationsForUserFiltered')
    ? $notificationController->getNotificationsForUserFiltered($notificationUserId, $status, $category, 150)
    : $notificationController->getNotificationsForUser($notificationUserId, 50, $status === 'unread');

$totalCount = method_exists($notificationController, 'countNotificationsForUser')
    ? $notificationController->countNotificationsForUser($notificationUserId)
    : count($notificationController->getNotificationsForUser($notificationUserId, 50, false));
$unreadCount = method_exists($notificationController, 'countUnread')
    ? $notificationController->countUnread($notificationUserId)
    : 0;
$currentFilterCount = method_exists($notificationController, 'countNotificationsForUser')
    ? $notificationController->countNotificationsForUser($notificationUserId, $status, $category)
    : count($notifications);

$statusLabels = [
    'all' => ['label' => 'All', 'key' => 'notifications.filter.all'],
    'unread' => ['label' => 'Unread', 'key' => 'notifications.filter.unread'],
    'read' => ['label' => 'Read', 'key' => 'notifications.filter.read'],
];
$categoryLabels = [
    'all' => ['label' => 'All types', 'key' => 'notifications.category.all', 'icon' => 'bi-grid'],
    'posts' => ['label' => 'Posts', 'key' => 'notifications.category.posts', 'icon' => 'bi-chat-dots'],
    'collaboration' => ['label' => 'Collaboration', 'key' => 'notifications.category.collaboration', 'icon' => 'bi-briefcase'],
    'admin' => ['label' => 'Admin', 'key' => 'notifications.category.admin', 'icon' => 'bi-shield-exclamation'],
    'complaints' => ['label' => 'Complaints', 'key' => 'notifications.category.complaints', 'icon' => 'bi-life-preserver'],
    'events' => ['label' => 'Events', 'key' => 'notifications.category.events', 'icon' => 'bi-calendar-event'],
];

if (!function_exists('cre8_notif_page_type_meta')) {
    function cre8_notif_page_type_meta(string $typeAction): array
    {
        return match ($typeAction) {
            'post_comment' => ['class' => 'type-post', 'icon' => 'bi-chat-dots', 'label' => 'Post', 'key' => 'notifications.type.post'],
            'post_reaction' => ['class' => 'type-post', 'icon' => 'bi-heart', 'label' => 'Post', 'key' => 'notifications.type.post'],
            'offer_invitation',
            'offer_accepted',
            'offer_refused',
            'candidature_received',
            'candidature_accepted',
            'candidature_refused',
            'negotiation_message' => ['class' => 'type-collaboration', 'icon' => 'bi-briefcase', 'label' => 'Collaboration', 'key' => 'notifications.type.collaboration'],
            'admin_post_removed',
            'admin_product_removed' => ['class' => 'type-admin', 'icon' => 'bi-shield-exclamation', 'label' => 'Admin', 'key' => 'notifications.type.admin'],
            'complaint_answered' => ['class' => 'type-complaint', 'icon' => 'bi-life-preserver', 'label' => 'Complaint', 'key' => 'notifications.type.complaint'],
            'event_today' => ['class' => 'type-event', 'icon' => 'bi-calendar-event', 'label' => 'Event', 'key' => 'notifications.type.event'],
            default => ['class' => 'type-default', 'icon' => 'bi-bell', 'label' => 'Notification', 'key' => 'notifications.type.notification'],
        };
    }
}

if (!function_exists('cre8_notif_page_date_label')) {
    function cre8_notif_page_date_label($value): string
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

if (!function_exists('cre8_notif_page_url')) {
    function cre8_notif_page_url(string $link, string $projectBase): string
    {
        $link = trim($link);
        if ($link === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $link)) {
            return $link;
        }
        if (str_starts_with($link, '/php/') || str_starts_with($link, $projectBase . '/')) {
            return $link;
        }
        if (str_starts_with($link, '/Vue/')) {
            return rtrim($projectBase, '/') . $link;
        }
        if (str_starts_with($link, 'Vue/')) {
            return rtrim($projectBase, '/') . '/' . $link;
        }
        if (str_starts_with($link, '/')) {
            return $link;
        }
        return rtrim($projectBase, '/') . '/' . ltrim($link, '/');
    }
}

function cre8_notif_page_filter_url(string $status, string $category): string
{
    return 'index.php?status=' . rawurlencode($status) . '&category=' . rawurlencode($category);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-i18n="notifications.pageTitle">Notifications | Cre8Connect</title>
    <?php
    $themeBootstrap = __DIR__ . '/../layout/front-theme-bootstrap.php';
    if (is_file($themeBootstrap)) {
        require $themeBootstrap;
    }
    ?>
    <link rel="stylesheet" href="../layout/front-header.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../layout/front-header.css')); ?>">
    <link rel="stylesheet" href="notifications-page.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/notifications-page.css')); ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="../../public/images/favicon-16.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../../public/images/favicon-32.png">
    <link rel="shortcut icon" type="image/png" href="../../public/images/favicon-32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../../public/images/apple-touch-icon.png">
</head>
<body class="d-flex flex-column min-vh-100">
<?php require_once __DIR__ . '/../layout/header.php'; ?>

<main class="front-notifications-page">
    <section class="front-notifications-shell">
        <div class="front-notifications-hero">
            <div>
                <span class="front-notifications-eyebrow"><i class="bi bi-bell"></i> <span data-i18n="notifications.center">Notification center</span></span>
                <h1 data-i18n="notifications.heroTitle">All your updates in one place</h1>
                <p data-i18n="notifications.heroCopy">Track comments, reactions, collaboration updates, admin decisions, complaints, and event reminders from one clean history page.</p>
            </div>
            <div class="front-notifications-count-card" aria-label="Unread notifications" title="Unread notifications" data-i18n-title="notifications.unreadNotifications">
                <strong><?php echo (int) $unreadCount; ?></strong>
                <span data-i18n="notifications.filter.unread">Unread</span>
            </div>
        </div>

        <div class="front-notifications-toolbar">
            <div class="front-notifications-filter-row" aria-label="Status filters">
                <span class="front-notifications-filter-label" data-i18n="notifications.status">Status</span>
                <?php foreach ($statusLabels as $statusKey => $statusMeta): ?>
                    <a class="front-notifications-filter-pill <?php echo $status === $statusKey ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars(cre8_notif_page_filter_url($statusKey, $category)); ?>">
                        <span data-i18n="<?php echo htmlspecialchars($statusMeta['key']); ?>"><?php echo htmlspecialchars($statusMeta['label']); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="front-notifications-filter-row" aria-label="Type filters">
                <span class="front-notifications-filter-label" data-i18n="notifications.type">Type</span>
                <?php foreach ($categoryLabels as $categoryKey => $meta): ?>
                    <a class="front-notifications-filter-pill <?php echo $category === $categoryKey ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars(cre8_notif_page_filter_url($status, $categoryKey)); ?>">
                        <i class="bi <?php echo htmlspecialchars($meta['icon']); ?>"></i>
                        <span data-i18n="<?php echo htmlspecialchars($meta['key']); ?>"><?php echo htmlspecialchars($meta['label']); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="front-notifications-actions">
                <button type="button" class="front-notifications-mark-btn" data-notifications-mark-all data-action-url="<?php echo htmlspecialchars($frontBaseUrl . '/layout/notification_actions.php'); ?>" <?php echo $unreadCount <= 0 ? 'disabled' : ''; ?>>
                    <span data-i18n="notifications.markAllRead">Mark all as read</span>
                </button>
            </div>
        </div>

        <div class="front-notifications-filter-row" style="margin-bottom:0.85rem;">
            <span class="front-notifications-filter-label"><?php echo (int) $currentFilterCount; ?> <span data-i18n="<?php echo $currentFilterCount === 1 ? 'notifications.result' : 'notifications.results'; ?>"><?php echo $currentFilterCount === 1 ? 'result' : 'results'; ?></span></span>
            <span class="front-notifications-filter-label">/</span>
            <span class="front-notifications-filter-label"><?php echo (int) $totalCount; ?> <span data-i18n="notifications.total">total</span></span>
        </div>

        <?php if (empty($notifications)): ?>
            <div class="front-notifications-empty">
                <i class="bi bi-bell-slash"></i>
                <span data-i18n="notifications.emptyFiltered">No notifications match this filter yet.</span>
            </div>
        <?php else: ?>
            <div class="front-notifications-list-page">
                <?php foreach ($notifications as $notification):
                    $typeAction = (string) ($notification['typeAction'] ?? '');
                    $meta = cre8_notif_page_type_meta($typeAction);
                    $isUnread = (int) ($notification['estLu'] ?? 0) === 0;
                    $link = cre8_notif_page_url((string) ($notification['lien'] ?? ''), $projectBase);
                    ?>
                    <article class="front-notifications-card <?php echo htmlspecialchars($meta['class']); ?><?php echo $isUnread ? ' is-unread' : ''; ?>">
                        <span class="front-notifications-type-icon" aria-hidden="true">
                            <i class="bi <?php echo htmlspecialchars($meta['icon']); ?>"></i>
                        </span>
                        <div>
                            <h2><?php echo htmlspecialchars((string) ($notification['titre'] ?? 'Notification')); ?></h2>
                            <p><?php echo htmlspecialchars((string) ($notification['message'] ?? '')); ?></p>
                            <div class="front-notifications-meta">
                                <span class="front-notifications-status" data-i18n="<?php echo $isUnread ? 'notifications.filter.unread' : 'notifications.filter.read'; ?>"><?php echo $isUnread ? 'Unread' : 'Read'; ?></span>
                                <span data-i18n="<?php echo htmlspecialchars($meta['key']); ?>"><?php echo htmlspecialchars($meta['label']); ?></span>
                                <span>•</span>
                                <time><?php echo htmlspecialchars(cre8_notif_page_date_label($notification['dateCreation'] ?? null)); ?></time>
                            </div>
                        </div>
                        <?php if ($link !== ''): ?>
                            <a class="front-notifications-open" href="<?php echo htmlspecialchars($link); ?>">
                                <span data-i18n="notifications.open">Open</span> <i class="bi bi-arrow-right-short"></i>
                            </a>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<script src="<?php echo htmlspecialchars($frontBaseUrl . '/layout/front-header.js'); ?>"></script>
<script>
(() => {
    const translations = {
        en: {
            'notifications.pageTitle': 'Notifications | Cre8Connect',
            'notifications.center': 'Notification center',
            'notifications.heroTitle': 'All your updates in one place',
            'notifications.heroCopy': 'Track comments, reactions, collaboration updates, admin decisions, complaints, and event reminders from one clean history page.',
            'notifications.unreadNotifications': 'Unread notifications',
            'notifications.status': 'Status',
            'notifications.type': 'Type',
            'notifications.filter.all': 'All',
            'notifications.filter.unread': 'Unread',
            'notifications.filter.read': 'Read',
            'notifications.category.all': 'All types',
            'notifications.category.posts': 'Posts',
            'notifications.category.collaboration': 'Collaboration',
            'notifications.category.admin': 'Admin',
            'notifications.category.complaints': 'Complaints',
            'notifications.category.events': 'Events',
            'notifications.markAllRead': 'Mark all as read',
            'notifications.result': 'result',
            'notifications.results': 'results',
            'notifications.total': 'total',
            'notifications.emptyFiltered': 'No notifications match this filter yet.',
            'notifications.type.post': 'Post',
            'notifications.type.collaboration': 'Collaboration',
            'notifications.type.admin': 'Admin',
            'notifications.type.complaint': 'Complaint',
            'notifications.type.event': 'Event',
            'notifications.type.notification': 'Notification',
            'notifications.open': 'Open'
        },
        fr: {
            'notifications.pageTitle': 'Notifications | Cre8Connect',
            'notifications.center': 'Centre de notifications',
            'notifications.heroTitle': 'Toutes vos mises a jour au meme endroit',
            'notifications.heroCopy': 'Suivez les commentaires, reactions, collaborations, decisions admin, reclamations et rappels d evenements depuis un historique clair.',
            'notifications.unreadNotifications': 'Notifications non lues',
            'notifications.status': 'Statut',
            'notifications.type': 'Type',
            'notifications.filter.all': 'Toutes',
            'notifications.filter.unread': 'Non lues',
            'notifications.filter.read': 'Lues',
            'notifications.category.all': 'Tous les types',
            'notifications.category.posts': 'Posts',
            'notifications.category.collaboration': 'Collaboration',
            'notifications.category.admin': 'Admin',
            'notifications.category.complaints': 'Reclamations',
            'notifications.category.events': 'Evenements',
            'notifications.markAllRead': 'Tout marquer comme lu',
            'notifications.result': 'resultat',
            'notifications.results': 'resultats',
            'notifications.total': 'total',
            'notifications.emptyFiltered': 'Aucune notification ne correspond a ce filtre pour le moment.',
            'notifications.type.post': 'Post',
            'notifications.type.collaboration': 'Collaboration',
            'notifications.type.admin': 'Admin',
            'notifications.type.complaint': 'Reclamation',
            'notifications.type.event': 'Evenement',
            'notifications.type.notification': 'Notification',
            'notifications.open': 'Ouvrir'
        }
    };

    function registerNotificationTranslations() {
        if (typeof window.cre8RegisterTranslations === 'function') {
            window.cre8RegisterTranslations(translations);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', registerNotificationTranslations);
    } else {
        registerNotificationTranslations();
    }

    const button = document.querySelector('[data-notifications-mark-all]');
    if (!button) {
        return;
    }
    button.addEventListener('click', () => {
        if (button.disabled) {
            return;
        }
        const actionUrl = button.dataset.actionUrl || '../layout/notification_actions.php';
        const formData = new FormData();
        formData.append('notificationAction', 'mark_all');
        button.disabled = true;
        fetch(actionUrl, {
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
                window.location.reload();
            })
            .catch(() => {
                button.disabled = false;
            });
    });
})();
</script>
</body>
</html>
