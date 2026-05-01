<?php
session_start();

require_once __DIR__ . '/../../../Controleur/condidatureC.php';

$controller = new CondidatureC();
$sessionUser = $_SESSION['utilisateur'] ?? [];

if (!isset($sessionUser['id']) || (($sessionUser['role'] ?? '') !== 'marque')) {
    $defaultBrand = $controller->getDefaultUserByRole('marque');
    if ($defaultBrand) {
        $_SESSION['utilisateur'] = [
            'id' => (int) $defaultBrand['id'],
            'role' => 'marque',
            'nom' => $defaultBrand['nom'],
            'email' => $defaultBrand['email'],
        ];
        $sessionUser = $_SESSION['utilisateur'];
    }
}

$brandId = isset($sessionUser['id']) ? (int) $sessionUser['id'] : null;
$brandUser = $brandId ? ($controller->getUsersByIds([$brandId], 'marque')[$brandId] ?? null) : null;
$notificationController = $controller;
$notificationUserId = (int) ($brandId ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notificationAction'])) {
    $notificationAction = (string) $_POST['notificationAction'];
    if ($notificationAction === 'mark_one') {
        $notificationController->markNotificationActionAsRead((int) ($_POST['idNotificationAction'] ?? 0), $notificationUserId);
    } elseif ($notificationAction === 'mark_all') {
        $notificationController->markAllNotificationActionsAsRead($notificationUserId);
    }

    $redirect = basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'brand_campaigns.php'));
    if (!empty($_SERVER['QUERY_STRING'])) {
        $redirect .= '?' . $_SERVER['QUERY_STRING'];
    }
    header('Location: ' . $redirect);
    exit;
}

if ($notificationUserId > 0) {
    $notificationController->generateBrandDeadlineSoonNotifications($notificationUserId);
}

$campaigns = [];
$error = null;

function formatBrandCampaignDate($value, $fallback = 'Not scheduled')
{
    if (!$value) {
        return $fallback;
    }

    $timestamp = strtotime((string) $value);
    if ($timestamp === false) {
        return (string) $value;
    }

    return date('Y-m-d', $timestamp);
}

function excerptBrandCampaignText($text, $length = 180)
{
    $text = trim((string) $text);
    if ($text === '') {
        return '';
    }

    if (strlen($text) <= $length) {
        return $text;
    }

    return rtrim(substr($text, 0, max(0, $length - 3))) . '...';
}

function brandCampaignStatusLabel($status)
{
    $status = trim((string) $status);

    return $status !== '' ? ucwords(str_replace('_', ' ', $status)) : 'Status not set';
}

if ($brandId) {
    try {
        $campaigns = $controller->getBrandCampaigns($brandId);
    } catch (Throwable $exception) {
        $error = 'Your campaigns could not be loaded right now.';
    }
} else {
    $error = 'No brand profile is available for this workspace.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Campaigns - Cre8Connect</title>
    <link rel="stylesheet" href="../css/frontoffice.css">
    <link rel="stylesheet" href="../offre/offre.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../offre/offre.css')); ?>">
    <link rel="stylesheet" href="condidature.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/condidature.css')); ?>">
</head>
<body>
    <?php require_once dirname(__DIR__) . '/layout/header.php'; ?>

    <main class="container py-5">
        <div class="offre-page-shell">
            <section class="module-hero module-hero-notification-shell campaign-opportunities-hero">
                <div>
                    <span class="module-eyebrow">Brand campaigns</span>
                    <h1 class="display-5 fw-bold mt-3 mb-2 gradient-title">My campaigns</h1>
                    <p class="lead text-muted mb-0">Review the campaigns owned by your brand and jump to their creator applications.</p>
                </div>
                <div class="compact-actions">
                    <?php require __DIR__ . '/notification_widget.php'; ?>
                    <a class="btn btn-outline-secondary" href="brand_index.php?origin=par_campagne">Campaign applications</a>
                    <a class="btn btn-outline-secondary" href="../offre/brand_index.php">My offers</a>
                </div>
            </section>

            <?php if ($brandUser): ?>
                <section class="note-block">
                    <strong><?php echo htmlspecialchars($brandUser['nom']); ?></strong>
                    <p><?php echo htmlspecialchars($brandUser['email']); ?></p>
                </section>
            <?php endif; ?>

            <?php if ($error): ?>
                <section class="empty-state-card">
                    <div class="empty-state-icon">!</div>
                    <h2 class="section-title">Campaigns unavailable</h2>
                    <p class="section-subtitle"><?php echo htmlspecialchars($error); ?></p>
                </section>
            <?php elseif (empty($campaigns)): ?>
                <section class="empty-state-card">
                    <div class="empty-state-icon">i</div>
                    <h2 class="section-title">No campaigns found</h2>
                    <p class="section-subtitle">No campaign is currently attached to this brand account.</p>
                </section>
            <?php else: ?>
                <section class="campaign-opportunity-grid brand-campaign-grid">
                    <?php foreach ($campaigns as $campaign): ?>
                        <article class="campaign-opportunity-card brand-campaign-card">
                            <div class="campaign-opportunity-top">
                                <span class="origin-badge">Campaign</span>
                                <span class="offer-chip"><?php echo htmlspecialchars(brandCampaignStatusLabel($campaign['status'])); ?></span>
                            </div>
                            <h2><?php echo htmlspecialchars($campaign['title'] ?: ('Campaign #' . $campaign['id'])); ?></h2>
                            <p><?php echo htmlspecialchars(excerptBrandCampaignText($campaign['description'] ?: 'No campaign description was provided yet.')); ?></p>
                            <div class="campaign-opportunity-meta">
                                <span>Start: <?php echo htmlspecialchars(formatBrandCampaignDate($campaign['dateDebut'])); ?></span>
                                <span>End: <?php echo htmlspecialchars(formatBrandCampaignDate($campaign['dateFin'])); ?></span>
                                <span>Total applications: <?php echo (int) $campaign['applicationCount']; ?></span>
                            </div>
                            <div class="candidature-inline-meta">
                                <span class="offer-chip">Waiting: <?php echo (int) $campaign['waitingCount']; ?></span>
                                <span class="offer-chip">Accepted: <?php echo (int) $campaign['acceptedCount']; ?></span>
                                <span class="offer-chip">Refused: <?php echo (int) $campaign['refusedCount']; ?></span>
                            </div>
                            <div class="compact-actions">
                                <a class="btn btn-primary" href="brand_index.php?origin=par_campagne">Review applications</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
<?php
$cre8PilotContext = [
    'page' => 'brand_offer_list',
    'role' => 'marque',
    'allowedActions' => ['normal_chat', 'summarize_page', 'analyze_page'],
    'formTarget' => null,
    'visibleEntityType' => 'campagne',
];
require __DIR__ . '/cre8pilot_widget.php';
?>
</body>
</html>
