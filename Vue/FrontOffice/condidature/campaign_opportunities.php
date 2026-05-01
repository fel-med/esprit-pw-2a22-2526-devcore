<?php
session_start();

require_once __DIR__ . '/../../../Controleur/condidatureC.php';

$controller = new CondidatureC();
$sessionUser = $_SESSION['utilisateur'] ?? [];

if (!isset($sessionUser['id']) || (($sessionUser['role'] ?? '') !== 'createur')) {
    $defaultCreator = $controller->getDefaultUserByRole('createur');
    if ($defaultCreator) {
        $_SESSION['utilisateur'] = [
            'id' => (int) $defaultCreator['id'],
            'role' => 'createur',
            'nom' => $defaultCreator['nom'],
            'email' => $defaultCreator['email'],
        ];
        $sessionUser = $_SESSION['utilisateur'];
    }
}

$creatorId = isset($sessionUser['id']) ? (int) $sessionUser['id'] : null;
$notificationController = $controller;
$notificationUserId = (int) ($creatorId ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notificationAction'])) {
    $notificationAction = (string) $_POST['notificationAction'];
    if ($notificationAction === 'mark_one') {
        $notificationController->markNotificationActionAsRead((int) ($_POST['idNotificationAction'] ?? 0), $notificationUserId);
    } elseif ($notificationAction === 'mark_all') {
        $notificationController->markAllNotificationActionsAsRead($notificationUserId);
    }

    $redirect = basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'campaign_opportunities.php'));
    if (!empty($_SERVER['QUERY_STRING'])) {
        $redirect .= '?' . $_SERVER['QUERY_STRING'];
    }
    header('Location: ' . $redirect);
    exit;
}

if ($notificationUserId > 0) {
    $notificationController->generateCreatorDeadlineSoonNotifications($notificationUserId);
}

$filters = [
    'keyword' => '',
    'status' => '',
];
$opportunities = [];
$error = null;

function formatCampaignDate($value, $fallback = 'Not scheduled')
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

function excerptCampaignText($text, $length = 170)
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

function campaignStatusLabel($status)
{
    $status = trim((string) $status);

    return $status !== '' ? ucwords(str_replace('_', ' ', $status)) : 'Status not set';
}

function campaignApplicationCta($condidature)
{
    if (!$condidature) {
        return ['label' => 'Apply to campaign', 'class' => 'btn-primary'];
    }

    if ($condidature->isDraft()) {
        return ['label' => 'Continue draft', 'class' => 'btn-primary'];
    }

    if ($condidature->isNegotiation()) {
        return ['label' => 'Open negotiation', 'class' => 'btn-primary'];
    }

    return ['label' => 'View application', 'class' => 'btn-outline-secondary'];
}

if ($creatorId) {
    try {
        $opportunities = $controller->getCreatorCampaignOpportunities($creatorId, $filters);
    } catch (Throwable $exception) {
        $error = 'Campaign opportunities could not be loaded right now.';
    }
} else {
    $error = 'No creator profile is available for this workspace.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campaign Opportunities - Cre8Connect</title>
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
                    <span class="module-eyebrow">Campaign applications</span>
                    <h1 class="display-5 fw-bold mt-3 mb-2 gradient-title">Campaign opportunities</h1>
                    <p class="lead text-muted mb-0">Browse active campaign briefs and submit a structured creator application.</p>
                </div>
                <div class="compact-actions">
                    <?php require __DIR__ . '/notification_widget.php'; ?>
                    <a class="btn btn-outline-secondary" href="index.php">My candidatures</a>
                    <a class="btn btn-outline-secondary" href="../offre/creator_list.php">Offer inbox</a>
                </div>
            </section>

            <?php if ($error): ?>
                <section class="empty-state-card">
                    <div class="empty-state-icon">!</div>
                    <h2 class="section-title">Campaigns unavailable</h2>
                    <p class="section-subtitle"><?php echo htmlspecialchars($error); ?></p>
                </section>
            <?php elseif (empty($opportunities)): ?>
                <section class="empty-state-card">
                    <div class="empty-state-icon">i</div>
                    <h2 class="section-title">No campaign opportunities found</h2>
                    <p class="section-subtitle">Try a broader search or come back when brands publish more campaign briefs.</p>
                </section>
            <?php else: ?>
                <section class="campaign-opportunity-grid">
                    <?php foreach ($opportunities as $item): ?>
                        <?php
                        $source = $item['source'];
                        $brand = $item['brand'];
                        $condidature = $item['condidature'];
                        $cta = campaignApplicationCta($condidature);
                        $href = $condidature
                            ? 'details.php?idCandidature=' . (int) $condidature->getIdCandidature()
                            : 'details.php?origin=par_campagne&idSource=' . (int) $source['id'];
                        ?>
                        <article class="campaign-opportunity-card">
                            <div class="campaign-opportunity-top">
                                <span class="origin-badge">Campaign application</span>
                                <span class="offer-chip"><?php echo htmlspecialchars(campaignStatusLabel($source['status'])); ?></span>
                            </div>
                            <h2><?php echo htmlspecialchars($source['title'] ?: ('Campaign #' . $source['id'])); ?></h2>
                            <p><?php echo htmlspecialchars(excerptCampaignText($source['description'] ?: 'No campaign description was provided yet.')); ?></p>
                            <div class="campaign-opportunity-meta">
                                <span>Brand: <?php echo htmlspecialchars($brand['nom'] ?: 'Unknown brand'); ?></span>
                                <span><?php echo htmlspecialchars($brand['email']); ?></span>
                                <span>Start: <?php echo htmlspecialchars(formatCampaignDate($source['datePublication'])); ?></span>
                                <span>End: <?php echo htmlspecialchars(formatCampaignDate($source['dateLimite'])); ?></span>
                            </div>
                            <?php if ($condidature): ?>
                                <div class="response-callout response-callout-review">
                                    <strong><?php echo htmlspecialchars($condidature->getDisplayStatusLabel()); ?></strong>
                                    <span><?php echo htmlspecialchars($condidature->getResponseTypeLabel()); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="compact-actions">
                                <a class="btn <?php echo htmlspecialchars($cta['class']); ?>" href="<?php echo htmlspecialchars($href); ?>"><?php echo htmlspecialchars($cta['label']); ?></a>
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
    'page' => 'creator_candidature_list',
    'role' => 'createur',
    'allowedActions' => ['normal_chat', 'summarize_page', 'analyze_page'],
    'formTarget' => null,
    'visibleEntityType' => 'campagne',
];
require __DIR__ . '/cre8pilot_widget.php';
?>
</body>
</html>
