<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../Controleur/offreC.php';
require_once __DIR__ . '/../../../Controleur/condidatureC.php';

$offreController = new OffreC();
$candidatureController = new CondidatureC();
$sessionUser = $_SESSION['utilisateur'] ?? [];

if (!isset($sessionUser['id']) || (($sessionUser['role'] ?? '') !== 'admin')) {
    $defaultAdmin = $candidatureController->getDefaultUserByRole('admin');
    if ($defaultAdmin) {
        $_SESSION['utilisateur'] = [
            'id' => (int) $defaultAdmin['id'],
            'role' => 'admin',
            'nom' => $defaultAdmin['nom'],
            'email' => $defaultAdmin['email'],
        ];
        $sessionUser = $_SESSION['utilisateur'];
    }
}

$notificationController = $candidatureController;
$notificationUserId = isset($sessionUser['id']) ? (int) $sessionUser['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notificationAction'])) {
    $notificationAction = (string) $_POST['notificationAction'];
    if ($notificationAction === 'mark_one') {
        $notificationController->markNotificationActionAsRead((int) ($_POST['idNotificationAction'] ?? 0), $notificationUserId);
    } elseif ($notificationAction === 'mark_all') {
        $notificationController->markAllNotificationActionsAsRead($notificationUserId);
    }

    $redirect = basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'index.php'));
    if (!empty($_SERVER['QUERY_STRING'])) {
        $redirect .= '?' . $_SERVER['QUERY_STRING'];
    }
    header('Location: ' . $redirect);
    exit;
}

$message = '';
$searchKeyword = trim($_GET['keyword'] ?? '');
$searchStatut = trim($_GET['statut'] ?? '');
$searchBudgetFrom = trim($_GET['budgetFrom'] ?? '');
$searchBudgetTo = trim($_GET['budgetTo'] ?? '');
$searchDateLimite = trim($_GET['dateLimite'] ?? '');
$searchDateLimiteTo = trim($_GET['dateLimiteTo'] ?? '');
$searchSort = trim($_GET['sort'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$filterValues = [
    $searchKeyword,
    $searchStatut,
    $searchBudgetFrom,
    $searchBudgetTo,
    $searchDateLimite,
    $searchDateLimiteTo,
    $searchSort,
];
$activeFilterCount = count(array_filter($filterValues, static fn($value) => $value !== '' && $value !== null && $value !== 'newest'));
$hasActiveFilters = $activeFilterCount > 0;

function translateOfferStatus($status)
{
    return match ($status) {
        'brouillon' => 'Draft',
        'pending' => 'Pending launch',
        'publiee' => 'Live now',
        'cloturee' => 'Closed',
        'expiree' => 'Expired',
        'archivee' => 'Archived',
        'active' => 'Active',
        'fermee', 'closed' => 'Closed',
        default => ucwords(str_replace('_', ' ', (string) $status)),
    };
}

function responseStatusLabel(array $response)
{
    return (string) ($response['displayStatusLabel'] ?? ucwords(str_replace('_', ' ', (string) ($response['statutCandidature'] ?? ''))));
}

function formatMoney($value)
{
    return 'EUR ' . number_format((float) $value, 2, '.', ',');
}

function cleanHiddenMetadata($text)
{
    return trim((string) preg_replace(
        '/\s*<!--cre8connect-(?:condidature-form-meta|condidature-meta|offre-meta):.*?-->\s*/s',
        ' ',
        (string) $text
    ));
}

function excerptText($text, $length = 80)
{
    $text = cleanHiddenMetadata($text);
    if (strlen($text) <= $length) {
        return $text;
    }

    return rtrim(substr($text, 0, max(0, $length - 3))) . '...';
}

function responseTypeLabel(array $response)
{
    return (string) ($response['responseTypeLabel'] ?? ucwords(str_replace('_', ' ', (string) ($response['typeReponse'] ?? 'Response'))));
}

function formatDateLabel($value, $fallback = 'Not available')
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

function buildInspectUrl($offerId, array $filters)
{
    $query = $filters;
    $query['idOffre'] = (int) $offerId;

    return 'index.php?' . http_build_query($query);
}

function renderOfferResponsesPanelHtml($offer, array $responses)
{
    ob_start();
    ?>
    <section class="offer-responses-panel">
        <div class="offer-responses-summary">
            <span class="quick-overview-tag">Offer responses</span>
            <strong><?php echo htmlspecialchars($offer ? $offer->getTitre() : 'Selected offer'); ?></strong>
            <span><?php echo count($responses); ?> related candidature<?php echo count($responses) === 1 ? '' : 's'; ?></span>
        </div>

        <?php if (empty($responses)): ?>
            <div class="detail-empty-state">
                <span class="detail-empty-icon">i</span>
                <h4>No creator responses yet for this offer.</h4>
                <p>This panel only shows creator responses attached to this offer invitation.</p>
            </div>
        <?php else: ?>
            <div class="offer-responses-list">
                <?php foreach ($responses as $response): ?>
                    <?php
                    $messagePreview = excerptText((string) ($response['messageMotivation'] ?? ''), 120);
                    $delay = isset($response['delaiPropose']) && $response['delaiPropose'] !== '' ? (int) $response['delaiPropose'] . ' days' : 'No delay shared';
                    ?>
                    <article class="offer-response-row">
                        <div class="offer-response-main">
                            <strong><?php echo htmlspecialchars($response['createurNom'] ?: ('Creator #' . $response['idCreateur'])); ?></strong>
                            <span><?php echo htmlspecialchars($response['createurEmail'] ?? ''); ?></span>
                            <p><?php echo htmlspecialchars($messagePreview !== '' ? $messagePreview : 'No response message was provided.'); ?></p>
                        </div>
                        <div class="offer-response-meta">
                            <span class="status-pill"><?php echo htmlspecialchars(responseTypeLabel($response)); ?></span>
                            <span class="status-pill"><?php echo htmlspecialchars(responseStatusLabel($response)); ?></span>
                            <span><?php echo htmlspecialchars(formatDateLabel($response['dateCandidature'] ?? null)); ?></span>
                            <span><?php echo htmlspecialchars(formatMoney($response['budgetPropose'] ?? 0)); ?></span>
                            <span><?php echo htmlspecialchars($delay); ?></span>
                        </div>
                        <a class="inspect-link offer-response-review" href="../condidature/details.php?idCandidature=<?php echo (int) ($response['idCandidature'] ?? 0); ?>">Review</a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
    <?php

    return trim((string) ob_get_clean());
}

function renderOfferInsightsHtml($selectedOffer, $selectedBrand, $selectedCreator, array $selectedResponses, array $selectedBreakdown, $selectedOfferOutsideFilters)
{
    ob_start();
    $displayStatus = $selectedOffer ? $selectedOffer->getDisplayStatusKey() : 'brouillon';
    $publicationLabel = $selectedOffer && $selectedOffer->isPendingPublication() ? 'Goes live' : 'Published';
    ?>
    <?php if ($selectedOffer): ?>
        <?php if ($selectedOfferOutsideFilters): ?>
            <div class="admin-inline-note">
                The selected offer is open in the insights panel, but it is outside the current filter view.
            </div>
        <?php endif; ?>
        <div class="quick-overview-card">
            <div class="quick-overview-top">
                <span class="quick-overview-tag">Selected offer</span>
                <span class="badge-status <?php echo htmlspecialchars($displayStatus); ?>"><?php echo htmlspecialchars(translateOfferStatus($displayStatus)); ?></span>
            </div>
            <h3><?php echo htmlspecialchars($selectedOffer->getTitre()); ?></h3>
            <p class="quick-overview-description"><?php echo htmlspecialchars(excerptText($selectedOffer->getDescription(), 150)); ?></p>
            <div class="quick-overview-meta">
                <span><?php echo htmlspecialchars(formatMoney($selectedOffer->getBudgetPropose())); ?></span>
                <span><?php echo htmlspecialchars($publicationLabel); ?>: <?php echo htmlspecialchars($selectedOffer->getDatePublication()); ?></span>
                <span>Deadline: <?php echo htmlspecialchars($selectedOffer->getDateLimite()); ?></span>
            </div>
        </div>

        <div class="detail-section">
            <div class="quick-stat-list">
                <div class="quick-stat-item">
                    <span class="quick-stat-label">Brand</span>
                    <span class="quick-stat-value"><?php echo htmlspecialchars($selectedBrand['nom'] ?? 'Unknown brand'); ?></span>
                </div>
                <div class="quick-stat-item">
                    <span class="quick-stat-label">Target creator</span>
                    <span class="quick-stat-value"><?php echo htmlspecialchars($selectedCreator['nom'] ?? 'No creator selected yet'); ?></span>
                </div>
                <div class="quick-stat-item">
                    <span class="quick-stat-label">Response volume</span>
                    <span class="quick-stat-value"><?php echo count($selectedResponses); ?> total responses</span>
                </div>
                <div class="quick-stat-item">
                    <span class="quick-stat-label">Breakdown</span>
                    <span class="quick-stat-value">
                        Drafts <?php echo $selectedBreakdown['brouillon'] ?? 0; ?>,
                        Accepted <?php echo $selectedBreakdown['envoyee']; ?>,
                        Negotiating <?php echo $selectedBreakdown['negociation'] + $selectedBreakdown['en_etude']; ?>,
                        Declined <?php echo ($selectedBreakdown['retiree'] ?? 0) + ($selectedBreakdown['refusee'] ?? 0); ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="detail-section">
            <div class="detail-grid">
                <div class="detail-block">
                    <h4>Why this creator</h4>
                    <p><?php echo htmlspecialchars($selectedOffer->getRaisonChoix() !== '' ? excerptText($selectedOffer->getRaisonChoix(), 120) : 'No specific rationale was attached to this offer.'); ?></p>
                </div>
                <div class="detail-block">
                    <h4>Expected fit</h4>
                    <p><?php echo htmlspecialchars($selectedOffer->getAttenteCollaboration() !== '' ? excerptText($selectedOffer->getAttenteCollaboration(), 120) : 'No collaboration expectations were added.'); ?></p>
                </div>
                <div class="detail-block">
                    <h4>Personal note</h4>
                    <p><?php echo htmlspecialchars($selectedOffer->getMessagePersonnalise() !== '' ? excerptText($selectedOffer->getMessagePersonnalise(), 120) : 'No personal note was added.'); ?></p>
                </div>
                <div class="detail-block">
                    <h4>Objective</h4>
                    <p><?php echo htmlspecialchars(excerptText($selectedOffer->getObjectif(), 120)); ?></p>
                </div>
            </div>
        </div>

        <div class="detail-section">
            <h4 class="quick-stat-label">Recent creator responses</h4>
            <?php if (!empty($selectedResponses)): ?>
                <div class="quick-candidate-list">
                    <?php foreach ($selectedResponses as $response): ?>
                        <div class="quick-candidate-item">
                            <div>
                                <strong><?php echo htmlspecialchars($response['createurNom'] ?: ('Creator #' . $response['idCreateur'])); ?></strong>
                                <span><?php echo htmlspecialchars($response['createurEmail'] ?? ''); ?></span>
                                <span><?php echo htmlspecialchars(excerptText((string) ($response['messageMotivation'] ?? ''), 72)); ?></span>
                            </div>
                            <div>
                                <span class="status-pill"><?php echo htmlspecialchars(responseStatusLabel($response)); ?></span>
                                <span class="status-pill"><?php echo htmlspecialchars(formatMoney($response['budgetPropose'])); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="detail-empty-state">
                    <span class="detail-empty-icon">i</span>
                    <h4>No creator response yet</h4>
                    <p>This targeted offer has not received any acceptance, decline, or negotiation signal yet.</p>
                </div>
            <?php endif; ?>
        </div>

        <p class="admin-note">Tip: use the filters above to isolate closing offers, high budgets, or creator-specific invitation flows without leaving this module.</p>
    <?php else: ?>
        <div class="detail-empty-state">
            <span class="detail-empty-icon">i</span>
            <h4>No offer selected</h4>
            <p>Choose an offer from the list to inspect its target creator, collaboration brief, and response activity.</p>
        </div>
    <?php endif; ?>
    <?php

    return trim((string) ob_get_clean());
}

function renderOfferInspectCardHtml($selectedOffer, $selectedBrand, $selectedCreator, array $selectedResponses, array $selectedBreakdown, $selectedOfferOutsideFilters)
{
    ob_start();
    $latestResponse = !empty($selectedResponses) ? $selectedResponses[0] : null;
    $displayStatus = $selectedOffer ? $selectedOffer->getDisplayStatusKey() : 'brouillon';
    $publicationLabel = $selectedOffer && $selectedOffer->isPendingPublication() ? 'Goes live' : 'Published';
    ?>
    <?php if ($selectedOffer): ?>
        <?php if ($selectedOfferOutsideFilters): ?>
            <div class="admin-inline-note">
                The selected offer is open in the preview card, but it is outside the current filter view.
            </div>
        <?php endif; ?>
        <article class="inspect-card">
            <header class="inspect-card-header">
                <div class="inspect-card-header-copy">
                    <span class="inspect-card-kicker">Offer preview</span>
                    <p class="inspect-card-intro">A focused snapshot of the selected collaboration pipeline.</p>
                </div>
                <span class="badge-status <?php echo htmlspecialchars($displayStatus); ?>"><?php echo htmlspecialchars(translateOfferStatus($displayStatus)); ?></span>
            </header>

            <section class="inspect-card-hero">
                <p class="inspect-card-description"><?php echo htmlspecialchars($selectedOffer->getDescription() !== '' ? $selectedOffer->getDescription() : 'No description was provided for this offer.'); ?></p>
            </section>

            <div class="inspect-card-meta">
                <span><?php echo htmlspecialchars(formatMoney($selectedOffer->getBudgetPropose())); ?></span>
                <span><?php echo htmlspecialchars($publicationLabel); ?>: <?php echo htmlspecialchars($selectedOffer->getDatePublication()); ?></span>
                <span>Deadline: <?php echo htmlspecialchars($selectedOffer->getDateLimite()); ?></span>
                <span>Responses: <?php echo count($selectedResponses); ?></span>
            </div>

            <div class="inspect-card-grid">
                <section class="inspect-card-block">
                    <h4>Objective</h4>
                    <p><?php echo htmlspecialchars($selectedOffer->getObjectif() !== '' ? $selectedOffer->getObjectif() : 'No objective was added.'); ?></p>
                </section>
                <section class="inspect-card-block">
                    <h4>Why this creator</h4>
                    <p><?php echo htmlspecialchars($selectedOffer->getRaisonChoix() !== '' ? $selectedOffer->getRaisonChoix() : 'No specific rationale was attached to this offer.'); ?></p>
                </section>
                <section class="inspect-card-block">
                    <h4>Expected fit</h4>
                    <p><?php echo htmlspecialchars($selectedOffer->getAttenteCollaboration() !== '' ? $selectedOffer->getAttenteCollaboration() : 'No collaboration expectations were added.'); ?></p>
                </section>
                <section class="inspect-card-block">
                    <h4>Personal note</h4>
                    <p><?php echo htmlspecialchars($selectedOffer->getMessagePersonnalise() !== '' ? $selectedOffer->getMessagePersonnalise() : 'No personal note was added.'); ?></p>
                </section>
            </div>

            <section class="inspect-card-block inspect-card-response">
                <h4>Latest creator signal</h4>
                <?php if ($latestResponse): ?>
                    <?php $latestMessage = cleanHiddenMetadata((string) ($latestResponse['messageMotivation'] ?? '')); ?>
                    <div class="inspect-card-response-top">
                        <span class="status-pill"><?php echo htmlspecialchars(responseStatusLabel($latestResponse)); ?></span>
                        <span class="status-pill"><?php echo htmlspecialchars(formatMoney($latestResponse['budgetPropose'])); ?></span>
                    </div>
                    <p><?php echo htmlspecialchars($latestMessage !== '' ? $latestMessage : 'No response message was provided.'); ?></p>
                <?php else: ?>
                    <p>No creator response has been submitted for this offer yet.</p>
                <?php endif; ?>
            </section>
        </article>
    <?php else: ?>
        <div class="detail-empty-state">
            <span class="detail-empty-icon">i</span>
            <h4>No offer selected</h4>
            <p>Choose an offer from the list to inspect its target creator, collaboration brief, and response activity.</p>
        </div>
    <?php endif; ?>
    <?php

    return trim((string) ob_get_clean());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deleteOffre'], $_POST['idOffreToDelete']) && is_numeric($_POST['idOffreToDelete'])) {
    $deleteId = (int) $_POST['idOffreToDelete'];
    $offerToDelete = $offreController->getOffreByIdAdmin($deleteId);
    if ($offerToDelete) {
        $offreController->deleteOffre($deleteId, $offerToDelete->getIdMarque());
        $query = $_GET;
        $query['deleted'] = 1;
        unset($query['idOffre']);
        header('Location: index.php?' . http_build_query($query));
        exit;
    }

    $message = 'Offer not found or impossible to delete.';
}

$offres = $offreController->searchOffresAdmin(
    $searchKeyword ?: null,
    $searchStatut ?: null,
    null,
    null,
    $searchBudgetFrom !== '' ? $searchBudgetFrom : null,
    $searchBudgetTo !== '' ? $searchBudgetTo : null,
    $searchDateLimite ?: null,
    $searchDateLimiteTo ?: null,
    $searchSort ?: 'newest',
    $perPage + 1,
    $offset
);
$hasNextPage = count($offres) > $perPage;
if ($hasNextPage) {
    array_pop($offres);
}

$persistedFilters = [
    'keyword' => $searchKeyword,
    'statut' => $searchStatut,
    'budgetFrom' => $searchBudgetFrom,
    'budgetTo' => $searchBudgetTo,
    'dateLimite' => $searchDateLimite,
    'dateLimiteTo' => $searchDateLimiteTo,
    'sort' => $searchSort,
];

$offerIds = array_map(static fn($offre) => $offre->getIdOffre(), $offres);
$brandIds = array_map(static fn($offre) => $offre->getIdMarque(), $offres);
$creatorIds = array_map(static fn($offre) => $offre->getIdCreateurCible(), $offres);
$brandMap = $offreController->getUsersByIds($brandIds, 'marque');
$creatorMap = $offreController->getUsersByIds($creatorIds, 'createur');
$responseGroups = $offreController->getCandidaturesGroupedByOfferIds($offerIds);

$allOffres = $offreController->getAllOffres();
$allBrandIds = array_map(static fn($offre) => $offre->getIdMarque(), $allOffres);
$allCreatorIds = array_map(static fn($offre) => $offre->getIdCreateurCible(), $allOffres);
$allBrandMap = $offreController->getUsersByIds($allBrandIds, 'marque');
$allCreatorMap = $offreController->getUsersByIds($allCreatorIds, 'createur');

$selectedOffer = null;
$selectedOfferInList = false;
$selectedId = isset($_GET['idOffre']) && is_numeric($_GET['idOffre']) ? (int) $_GET['idOffre'] : null;

if ($selectedId !== null) {
    $selectedOffer = $offreController->getOffreByIdAdmin($selectedId);
    foreach ($offres as $offre) {
        if ((int) $offre->getIdOffre() === $selectedId) {
            $selectedOfferInList = true;
            break;
        }
    }
}

if (!$selectedOffer && !empty($offres)) {
    $selectedOffer = $offres[0];
    $selectedOfferInList = true;
}

$selectedResponses = $selectedOffer
    ? ($responseGroups[$selectedOffer->getIdOffre()] ?? $offreController->getCandidaturesByOffre($selectedOffer->getIdOffre()))
    : [];
$selectedBreakdown = $selectedOffer ? $offreController->getOfferResponseBreakdown($selectedOffer->getIdOffre()) : [
    'envoyee' => 0,
    'negociation' => 0,
    'en_etude' => 0,
    'acceptee' => 0,
    'refusee' => 0,
    'retiree' => 0,
    'total' => 0,
];
$selectedOfferOutsideFilters = $selectedOffer && !$selectedOfferInList;
$selectedBrand = $selectedOffer ? ($brandMap[$selectedOffer->getIdMarque()] ?? $allBrandMap[$selectedOffer->getIdMarque()] ?? null) : null;
$selectedCreator = $selectedOffer && !$selectedOffer->isDraftSansCreateur()
    ? ($creatorMap[$selectedOffer->getIdCreateurCible()] ?? $allCreatorMap[$selectedOffer->getIdCreateurCible()] ?? null)
    : null;

if (isset($_GET['ajax']) && $_GET['ajax'] === 'insights') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'selectedId' => $selectedOffer ? (int) $selectedOffer->getIdOffre() : null,
        'title' => $selectedOffer ? (string) $selectedOffer->getTitre() : 'Offer preview',
        'html' => renderOfferInsightsHtml(
            $selectedOffer,
            $selectedBrand,
            $selectedCreator,
            $selectedResponses,
            $selectedBreakdown,
            $selectedOfferOutsideFilters
        ),
        'inspectHtml' => renderOfferInspectCardHtml(
            $selectedOffer,
            $selectedBrand,
            $selectedCreator,
            $selectedResponses,
            $selectedBreakdown,
            $selectedOfferOutsideFilters
        ),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$paginationBase = $_GET;
unset($paginationBase['page'], $paginationBase['ajax']);
$prevPageUrl = $page > 1 ? 'index.php?' . http_build_query($paginationBase + ['page' => $page - 1]) : '';
$nextPageUrl = $hasNextPage ? 'index.php?' . http_build_query($paginationBase + ['page' => $page + 1]) : '';
$adminOfferMetrics = $offreController->getAdminOfferMetrics();
$platformMetrics = $candidatureController->getAdminPlatformMetrics();
$adminPieChartStats = $candidatureController->getAdminPieChartStats();

$liveCount = 0;
$pendingCount = 0;
$offersWithResponses = 0;
$averageBudget = 0;
$closingSoon = 0;
$liveBudgetOffers = [];

if (!empty($offres)) {
    $today = new DateTime('today');

    foreach ($offres as $offre) {
        if ($offre->isPendingPublication()) {
            $pendingCount++;
        } elseif ($offre->isLivePublication()) {
            $liveCount++;
            $liveBudgetOffers[] = $offre;
        }
        if (!empty($responseGroups[$offre->getIdOffre()] ?? [])) {
            $offersWithResponses++;
        }

        $deadline = DateTime::createFromFormat('Y-m-d', (string) $offre->getDateLimite());
        if ($deadline) {
            $days = (int) $today->diff($deadline)->format('%r%a');
            if ($days >= 0 && $days <= 7) {
                $closingSoon++;
            }
        }
    }
}

if (!empty($liveBudgetOffers)) {
    $averageBudget = array_sum(array_map(static fn($offre) => (float) $offre->getBudgetPropose(), $liveBudgetOffers)) / count($liveBudgetOffers);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Back Office - Offer Management</title>
    <link rel="stylesheet" href="../css/backoffice.css">
    <link rel="stylesheet" href="offre-admin.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/offre-admin.css')); ?>">
</head>
<body class="cre8-admin-layout">
    <div class="cre8-admin-page">
        <?php require_once dirname(__DIR__) . '/layout/sidebar.php'; ?>
        <main class="cre8-admin-main">
            <?php require_once dirname(__DIR__) . '/layout/header.php'; ?>
    <div class="admin-shell">
        <header class="admin-header">
            <div class="admin-header-main">
                <div>
                    <h1>Offer administration</h1>
                    <p>Track targeted offers, understand creator response behavior, and keep the collaboration pipeline visible for admins.</p>
                </div>
                <?php require __DIR__ . '/../condidature/notification_widget.php'; ?>
            </div>
        </header>

        <?php if (!empty($message)): ?>
            <div class="admin-flash success"><?php echo htmlspecialchars($message); ?></div>
        <?php elseif (isset($_GET['deleted'])): ?>
            <div class="admin-flash success">Offer deleted successfully.</div>
        <?php endif; ?>

        <section class="search-panel search-panel-simple">
            <div class="search-panel-head">
                <div class="search-panel-copy">
                    <span class="search-panel-title">Filter the offer list</span>
                    <span class="search-panel-subtitle">
                        Search by offer, brand, creator, budget, or deadline without opening extra controls.
                    </span>
                </div>
                <?php if ($hasActiveFilters): ?>
                    <span class="search-panel-badge"><?php echo $activeFilterCount; ?> active</span>
                <?php endif; ?>
            </div>

            <form method="get" class="search-form" data-module-validation="admin-filters" novalidate>
                <div class="search-grid">
                    <div class="search-group">
                        <label for="keyword">Keyword</label>
                        <input id="keyword" name="keyword" type="search" value="<?php echo htmlspecialchars($searchKeyword); ?>" placeholder="Offer, brand, creator, or ID...">
                    </div>

                    <div class="search-group">
                        <label for="statut">Status</label>
                        <select id="statut" name="statut">
                            <option value="">All</option>
                            <option value="brouillon"<?php echo $searchStatut === 'brouillon' ? ' selected' : ''; ?>>Draft</option>
                            <option value="publiee"<?php echo $searchStatut === 'publiee' ? ' selected' : ''; ?>>Live now</option>
                            <option value="pending"<?php echo $searchStatut === 'pending' ? ' selected' : ''; ?>>Pending launch</option>
                            <option value="cloturee"<?php echo $searchStatut === 'cloturee' ? ' selected' : ''; ?>>Closed</option>
                            <option value="expiree"<?php echo $searchStatut === 'expiree' ? ' selected' : ''; ?>>Expired</option>
                            <option value="archivee"<?php echo $searchStatut === 'archivee' ? ' selected' : ''; ?>>Archived</option>
                        </select>
                    </div>

                    <div class="search-group">
                        <label for="budgetFrom">Budget from</label>
                        <input id="budgetFrom" name="budgetFrom" type="number" step="0.01" value="<?php echo htmlspecialchars($searchBudgetFrom); ?>" placeholder="0">
                    </div>

                    <div class="search-group">
                        <label for="budgetTo">Budget to</label>
                        <input id="budgetTo" name="budgetTo" type="number" step="0.01" value="<?php echo htmlspecialchars($searchBudgetTo); ?>" placeholder="0">
                    </div>

                    <div class="search-group">
                        <label for="dateLimite">Deadline from</label>
                        <input id="dateLimite" name="dateLimite" type="date" value="<?php echo htmlspecialchars($searchDateLimite); ?>">
                    </div>

                    <div class="search-group">
                        <label for="dateLimiteTo">Deadline to</label>
                        <input id="dateLimiteTo" name="dateLimiteTo" type="date" value="<?php echo htmlspecialchars($searchDateLimiteTo); ?>">
                    </div>

                    <div class="search-group">
                        <label for="sort">Sort</label>
                        <select id="sort" name="sort">
                            <option value=""<?php echo $searchSort === '' ? ' selected' : ''; ?>>Newest</option>
                            <option value="oldest"<?php echo $searchSort === 'oldest' ? ' selected' : ''; ?>>Oldest</option>
                            <option value="deadline_soon"<?php echo $searchSort === 'deadline_soon' ? ' selected' : ''; ?>>Deadline soon</option>
                            <option value="budget_high"<?php echo $searchSort === 'budget_high' ? ' selected' : ''; ?>>Budget high to low</option>
                            <option value="budget_low"<?php echo $searchSort === 'budget_low' ? ' selected' : ''; ?>>Budget low to high</option>
                            <option value="status"<?php echo $searchSort === 'status' ? ' selected' : ''; ?>>Status</option>
                        </select>
                    </div>
                </div>

                <div class="search-actions">
                    <button type="submit">Apply filters</button>
                    <a class="clear-link" href="index.php">Reset</a>
                </div>
            </form>
        </section>

        <section class="admin-summary">
            <article class="admin-card">
                <h3>Real offers</h3>
                <p><?php echo (int) ($adminOfferMetrics['realOffers'] ?? 0); ?></p>
                <small>Total targeted invitations</small>
            </article>
            <article class="admin-card">
                <h3>Real candidatures</h3>
                <p><?php echo (int) ($platformMetrics['realCandidatures'] ?? 0); ?></p>
                <small>Placeholders excluded</small>
            </article>
            <article class="admin-card">
                <h3>Pending reviews</h3>
                <p><?php echo (int) ($platformMetrics['pendingReviews'] ?? 0); ?></p>
                <small>Sent or under review</small>
            </article>
            <article class="admin-card">
                <h3>Open negotiations</h3>
                <p><?php echo (int) ($platformMetrics['openNegotiations'] ?? 0); ?></p>
                <small>Active negotiation candidatures</small>
            </article>
            <article class="admin-card">
                <h3>Expired offers</h3>
                <p><?php echo (int) ($adminOfferMetrics['expiredOffers'] ?? 0); ?></p>
                <small>Past deadline and not archived</small>
            </article>
            <article class="admin-card">
                <h3>Activity this week</h3>
                <p><?php echo (int) ($platformMetrics['activityThisWeek'] ?? 0); ?></p>
                <small><?php echo htmlspecialchars((string) ($platformMetrics['acceptanceRate'] ?? 0)); ?>% acceptance rate</small>
            </article>
        </section>

        <?php require __DIR__ . '/../condidature/statistics_charts.php'; ?>

        <div class="admin-layout">
            <section class="admin-panel admin-table-panel">
                <div class="admin-panel-header">
                    <h2>Offer list</h2>
                </div>
                <div class="admin-panel-body">
                    <div class="admin-table-wrapper">
                        <table class="admin-table admin-offer-table">
                            <colgroup>
                                <col class="offer-col-main">
                                <col class="offer-col-brand">
                                <col class="offer-col-creator">
                                <col class="offer-col-budget">
                                <col class="offer-col-date">
                                <col class="offer-col-status">
                                <col class="offer-col-responses">
                                <col class="offer-col-actions">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Offer</th>
                                    <th>Brand</th>
                                    <th>Target creator</th>
                                    <th>Budget</th>
                                    <th>Deadline</th>
                                    <th>Status</th>
                                    <th>Responses</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($offres)): ?>
                                    <tr>
                                        <td colspan="8" style="padding: 1.5rem; text-align: center; color: #94a3b8;">No offers match the current filters.</td>
                                    </tr>
                                <?php endif; ?>

                                <?php foreach ($offres as $offre): ?>
                                    <?php
                                    $isSelected = $selectedOffer && (int) $selectedOffer->getIdOffre() === (int) $offre->getIdOffre();
                                    $responses = $responseGroups[$offre->getIdOffre()] ?? [];
                                    $brand = $brandMap[$offre->getIdMarque()] ?? null;
                                    $creator = $creatorMap[$offre->getIdCreateurCible()] ?? null;
                                    $inspectUrl = buildInspectUrl($offre->getIdOffre(), $persistedFilters);
                                    $offerTitle = (string) $offre->getTitre();
                                    $offerObjective = 'ID #' . (int) $offre->getIdOffre() . ' - ' . (string) $offre->getObjectif();
                                    $brandName = (string) ($brand['nom'] ?? ('ID #' . $offre->getIdMarque()));
                                    $displayStatus = $offre->getDisplayStatusKey();
                                    $creatorName = $offre->isDraftSansCreateur()
                                        ? 'No creator selected'
                                        : (string) ($creator['nom'] ?? ($offre->getIdCreateurCible() ? ('ID #' . $offre->getIdCreateurCible()) : 'No creator selected'));
                                    ?>
                                    <tr<?php echo $isSelected ? ' class="is-selected"' : ''; ?> data-offer-id="<?php echo (int) $offre->getIdOffre(); ?>" data-inspect-url="<?php echo htmlspecialchars($inspectUrl); ?>" data-offer-title="<?php echo htmlspecialchars($offerTitle, ENT_QUOTES); ?>" tabindex="0">
                                        <td class="offer-cell">
                                            <strong class="offer-row-title table-hover-text" title="<?php echo htmlspecialchars($offerTitle, ENT_QUOTES); ?>"><?php echo htmlspecialchars(excerptText($offerTitle, 44)); ?></strong>
                                            <span class="row-label table-hover-text" title="<?php echo htmlspecialchars($offerObjective, ENT_QUOTES); ?>">ID #<?php echo (int) $offre->getIdOffre(); ?> - <?php echo htmlspecialchars(excerptText($offre->getObjectif(), 42)); ?></span>
                                        </td>
                                        <td class="entity-cell">
                                            <strong class="entity-primary table-hover-text" title="<?php echo htmlspecialchars($brandName, ENT_QUOTES); ?>"><?php echo htmlspecialchars($brandName); ?></strong>
                                        </td>
                                        <td class="entity-cell">
                                            <strong class="entity-primary table-hover-text" title="<?php echo htmlspecialchars($creatorName, ENT_QUOTES); ?>"><?php echo htmlspecialchars($creatorName); ?></strong>
                                        </td>
                                        <td class="money-cell"><?php echo htmlspecialchars(formatMoney($offre->getBudgetPropose())); ?></td>
                                        <td><?php echo htmlspecialchars($offre->getDateLimite()); ?></td>
                                        <td><span class="badge-status <?php echo htmlspecialchars($displayStatus); ?>"><?php echo htmlspecialchars(translateOfferStatus($displayStatus)); ?></span></td>
                                        <td class="responses-cell">
                                            <button
                                                type="button"
                                                class="responses-link"
                                                data-offer-responses-trigger
                                                data-offer-id="<?php echo (int) $offre->getIdOffre(); ?>"
                                                data-offer-title="<?php echo htmlspecialchars($offerTitle, ENT_QUOTES); ?>"
                                            >
                                                <strong><?php echo count($responses); ?></strong>
                                                <span>Responses</span>
                                            </button>
                                        </td>
                                        <td class="admin-actions">
                                            <div class="admin-actions-stack">
                                                <a class="inspect-link" href="<?php echo htmlspecialchars($inspectUrl); ?>">Inspect</a>
                                                <form
                                                    method="post"
                                                    class="inline-delete-form"
                                                    data-delete-confirm
                                                    data-delete-title="<?php echo htmlspecialchars($offerTitle, ENT_QUOTES); ?>"
                                                    data-delete-creator="<?php echo htmlspecialchars($creatorName, ENT_QUOTES); ?>"
                                                >
                                                    <input type="hidden" name="idOffreToDelete" value="<?php echo (int) $offre->getIdOffre(); ?>">
                                                    <button type="submit" name="deleteOffre" class="delete-btn">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="offer-response-templates" hidden>
                        <?php foreach ($offres as $offre): ?>
                            <?php $responses = $responseGroups[$offre->getIdOffre()] ?? []; ?>
                            <template id="offerResponsesTemplate-<?php echo (int) $offre->getIdOffre(); ?>">
                                <?php echo renderOfferResponsesPanelHtml($offre, $responses); ?>
                            </template>
                        <?php endforeach; ?>
                    </div>
                    <nav class="admin-pagination" aria-label="Offer pages">
                        <span>Page <?php echo $page; ?> · Showing up to <?php echo $perPage; ?> offers</span>
                        <div>
                            <?php if ($prevPageUrl !== ''): ?>
                                <a class="clear-link" href="<?php echo htmlspecialchars($prevPageUrl); ?>">Previous</a>
                            <?php else: ?>
                                <span class="clear-link is-disabled">Previous</span>
                            <?php endif; ?>
                            <?php if ($nextPageUrl !== ''): ?>
                                <a class="clear-link" href="<?php echo htmlspecialchars($nextPageUrl); ?>">Next</a>
                            <?php else: ?>
                                <span class="clear-link is-disabled">Next</span>
                            <?php endif; ?>
                        </div>
                    </nav>
                </div>
            </section>

            <aside class="admin-panel admin-details-panel">
                <div class="admin-panel-header">
                    <h2>Offer insights</h2>
                </div>
                <div class="admin-panel-body admin-insights-body" id="offerInsightsBody">
                    <?php echo renderOfferInsightsHtml(
                        $selectedOffer,
                        $selectedBrand,
                        $selectedCreator,
                        $selectedResponses,
                        $selectedBreakdown,
                        $selectedOfferOutsideFilters
                    ); ?>
                </div>
            </aside>
        </div>
    </div>
    <dialog class="inspect-dialog" id="offerInspectDialog" aria-labelledby="offerInspectDialogTitle">
        <div class="inspect-dialog-card">
            <div class="inspect-dialog-header">
                <div>
                    <span class="inspect-dialog-kicker">Admin preview</span>
                    <h2 id="offerInspectDialogTitle">Offer preview</h2>
                    <p>Open a compact offer card without leaving the dashboard list.</p>
                </div>
                <button type="button" class="inspect-dialog-close" data-close-inspect-dialog aria-label="Close offer preview">Close</button>
            </div>
            <div class="inspect-dialog-body" id="offerInspectDialogBody">
                <?php echo renderOfferInspectCardHtml(
                    $selectedOffer,
                    $selectedBrand,
                    $selectedCreator,
                    $selectedResponses,
                    $selectedBreakdown,
                    $selectedOfferOutsideFilters
                ); ?>
            </div>
            </div>
    </dialog>
    <dialog class="responses-dialog" id="offerResponsesDialog" aria-labelledby="offerResponsesDialogTitle">
        <div class="responses-dialog-card">
            <div class="responses-dialog-header">
                <div>
                    <span class="inspect-dialog-kicker">Related candidatures</span>
                    <h2 id="offerResponsesDialogTitle">Offer responses</h2>
                    <p>Creator responses linked to this offer invitation only.</p>
                </div>
                <button type="button" class="inspect-dialog-close" data-close-responses-dialog aria-label="Close offer responses">Close</button>
            </div>
            <div class="responses-dialog-body" id="offerResponsesDialogBody">
                <div class="detail-empty-state">
                    <span class="detail-empty-icon">i</span>
                    <h4>No creator responses yet for this offer.</h4>
                    <p>Select an offer response button from the table to load related candidatures.</p>
                </div>
            </div>
        </div>
    </dialog>
    <dialog class="delete-dialog" id="offerDeleteDialog" aria-labelledby="offerDeleteDialogTitle">
        <div class="delete-dialog-card">
            <div class="delete-dialog-header">
                <div>
                    <span class="delete-dialog-kicker">Delete offer</span>
                    <h2 id="offerDeleteDialogTitle">Remove this offer?</h2>
                    <p>This action permanently removes the selected offer from the admin pipeline.</p>
                </div>
                <button type="button" class="delete-dialog-close" data-delete-close aria-label="Cancel offer deletion">Cancel</button>
            </div>
            <div class="delete-dialog-body">
                <div class="delete-dialog-preview">
                    <span class="delete-dialog-preview-label">Selected offer</span>
                    <strong id="offerDeleteDialogOffer">This targeted offer</strong>
                    <span id="offerDeleteDialogCreator">Creator context will appear here.</span>
                </div>
                <p class="delete-dialog-warning">This action cannot be undone.</p>
                <div class="delete-dialog-actions">
                    <button type="button" class="delete-dialog-secondary" data-delete-close>Keep offer</button>
                    <button type="button" class="delete-dialog-danger" id="offerDeleteDialogConfirm">Delete permanently</button>
                </div>
            </div>
        </div>
    </dialog>
        </main>
    </div>
    <script src="offre-admin-validation.js"></script>
    <script src="offre-admin-delete.js?v=<?php echo urlencode((string) filemtime(__DIR__ . '/offre-admin-delete.js')); ?>"></script>
    <script>
        (() => {
            const rows = document.querySelectorAll('.admin-table tbody tr[data-inspect-url]');
            const insightsBody = document.getElementById('offerInsightsBody');
            const inspectDialog = document.getElementById('offerInspectDialog');
            const inspectDialogBody = document.getElementById('offerInspectDialogBody');
            const responsesDialog = document.getElementById('offerResponsesDialog');
            const responsesDialogBody = document.getElementById('offerResponsesDialogBody');
            const responsesDialogTitle = document.getElementById('offerResponsesDialogTitle');
            const responseTriggers = document.querySelectorAll('[data-offer-responses-trigger]');
            const tablePanel = document.querySelector('.admin-table-panel');
            const tablePanelBody = tablePanel ? tablePanel.querySelector('.admin-panel-body') : null;
            const tableWrapper = tablePanel ? tablePanel.querySelector('.admin-table-wrapper') : null;
            const detailsPanel = document.querySelector('.admin-details-panel');
            const loadingCardHtml = `
                <div class="detail-empty-state">
                    <span class="detail-empty-icon">…</span>
                    <h4>Loading offer preview</h4>
                    <p>The detailed inspection card is being prepared for this offer.</p>
                </div>
            `;

            function resetDashboardPanelHeight() {
                if (tablePanel) {
                    tablePanel.style.height = '';
                }

                if (tableWrapper) {
                    tableWrapper.style.height = '';
                    tableWrapper.style.maxHeight = '';
                }
            }

            function syncDashboardPanelHeight() {
                if (!tablePanel || !tablePanelBody || !tableWrapper || !detailsPanel) {
                    return;
                }

                if (window.innerWidth <= 1180) {
                    resetDashboardPanelHeight();
                    return;
                }

                const detailsHeight = Math.round(detailsPanel.getBoundingClientRect().height);
                if (!detailsHeight) {
                    return;
                }

                const tableHeader = tablePanel.querySelector('.admin-panel-header');
                const tableHeaderHeight = tableHeader ? tableHeader.offsetHeight : 0;
                const bodyStyles = window.getComputedStyle(tablePanelBody);
                const bodyPadding =
                    parseFloat(bodyStyles.paddingTop || '0') +
                    parseFloat(bodyStyles.paddingBottom || '0');
                const availableTableHeight = Math.max(260, Math.floor(detailsHeight - tableHeaderHeight - bodyPadding));

                tablePanel.style.height = `${detailsHeight}px`;
                tableWrapper.style.height = `${availableTableHeight}px`;
                tableWrapper.style.maxHeight = `${availableTableHeight}px`;
            }

            function openInspectDialog(html) {
                if (!inspectDialog || !inspectDialogBody) {
                    return;
                }

                inspectDialogBody.innerHTML = html || '';

                if (typeof inspectDialog.showModal === 'function') {
                    if (!inspectDialog.open) {
                        inspectDialog.showModal();
                    }
                    return;
                }

                inspectDialog.setAttribute('open', 'open');
            }

            function closeInspectDialog() {
                if (!inspectDialog) {
                    return;
                }

                if (typeof inspectDialog.close === 'function' && inspectDialog.open) {
                    inspectDialog.close();
                    return;
                }

                inspectDialog.removeAttribute('open');
            }

            function openResponsesDialog(trigger) {
                if (!responsesDialog || !responsesDialogBody || !trigger) {
                    return;
                }

                const template = document.getElementById(`offerResponsesTemplate-${trigger.dataset.offerId || ''}`);
                responsesDialogBody.innerHTML = template ? template.innerHTML : `
                    <div class="detail-empty-state">
                        <span class="detail-empty-icon">i</span>
                        <h4>No creator responses yet for this offer.</h4>
                        <p>This panel only shows creator responses attached to this offer invitation.</p>
                    </div>
                `;

                if (responsesDialogTitle) {
                    responsesDialogTitle.textContent = trigger.dataset.offerTitle
                        ? `Responses for ${trigger.dataset.offerTitle}`
                        : 'Offer responses';
                }

                if (typeof responsesDialog.showModal === 'function') {
                    if (!responsesDialog.open) {
                        responsesDialog.showModal();
                    }
                    return;
                }

                responsesDialog.setAttribute('open', 'open');
            }

            function closeResponsesDialog() {
                if (!responsesDialog) {
                    return;
                }

                if (typeof responsesDialog.close === 'function' && responsesDialog.open) {
                    responsesDialog.close();
                    return;
                }

                responsesDialog.removeAttribute('open');
            }

            function setSelectedRow(selectedId) {
                rows.forEach((row) => {
                    row.classList.toggle('is-selected', String(row.dataset.offerId) === String(selectedId));
                });
            }

            async function loadInsights(url, row, options = {}) {
                if (!insightsBody || !window.fetch) {
                    window.location.href = url;
                    return;
                }

                if (options.openModal) {
                    openInspectDialog(loadingCardHtml);
                }

                const ajaxUrl = new URL(url, window.location.href);
                ajaxUrl.searchParams.set('ajax', 'insights');
                insightsBody.classList.add('is-loading');

                try {
                    const response = await fetch(ajaxUrl.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (!response.ok) {
                        throw new Error('Unable to load offer insights.');
                    }

                    const payload = await response.json();
                    insightsBody.innerHTML = payload.html || '';
                    if (payload.selectedId !== null && payload.selectedId !== undefined) {
                        setSelectedRow(payload.selectedId);
                    } else if (row) {
                        setSelectedRow(row.dataset.offerId);
                    }

                    if (options.openModal) {
                        openInspectDialog(payload.inspectHtml || payload.html || '');
                    }

                    window.history.replaceState({}, '', url);
                    syncDashboardPanelHeight();
                } catch (error) {
                    if (options.openModal) {
                        openInspectDialog(`
                            <div class="detail-empty-state">
                                <span class="detail-empty-icon">!</span>
                                <h4>Preview unavailable</h4>
                                <p>The preview card could not be loaded right now. The dashboard will open the offer directly instead.</p>
                            </div>
                        `);
                        window.setTimeout(() => {
                            window.location.href = url;
                        }, 500);
                        return;
                    }
                    window.location.href = url;
                } finally {
                    insightsBody.classList.remove('is-loading');
                }
            }

            rows.forEach((row) => {
                const url = row.dataset.inspectUrl;
                if (!url) {
                    return;
                }

                const inspectLink = row.querySelector('.inspect-link');
                if (inspectLink) {
                    inspectLink.addEventListener('click', (event) => {
                        event.preventDefault();
                        loadInsights(url, row, { openModal: true });
                    });
                }

                row.addEventListener('click', (event) => {
                    if (event.target.closest('button, form, .delete-btn')) {
                        return;
                    }
                    if (event.target.closest('a')) {
                        return;
                    }
                    loadInsights(url, row);
                });

                row.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        loadInsights(url, row);
                    }
                });
            });

            responseTriggers.forEach((trigger) => {
                trigger.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    openResponsesDialog(trigger);
                });
            });

            if (inspectDialog) {
                inspectDialog.addEventListener('click', (event) => {
                    if (event.target === inspectDialog || event.target.closest('[data-close-inspect-dialog]')) {
                        closeInspectDialog();
                    }
                });

                inspectDialog.addEventListener('cancel', () => {
                    closeInspectDialog();
                });
            }

            if (responsesDialog) {
                responsesDialog.addEventListener('click', (event) => {
                    if (event.target === responsesDialog || event.target.closest('[data-close-responses-dialog]')) {
                        closeResponsesDialog();
                    }
                });

                responsesDialog.addEventListener('cancel', () => {
                    closeResponsesDialog();
                });
            }

            syncDashboardPanelHeight();
            window.addEventListener('resize', syncDashboardPanelHeight);

            if (window.ResizeObserver && detailsPanel) {
                const dashboardObserver = new ResizeObserver(() => {
                    syncDashboardPanelHeight();
                });
                dashboardObserver.observe(detailsPanel);
            }
        })();
    </script>
<?php
$cre8PilotContext = [
    'page' => 'admin_offer_workspace',
    'mode' => 'table',
    'role' => 'admin',
    'allowedActions' => ['normal_chat', 'summarize_page', 'analyze_page', 'explain_statistics', 'detect_risky_items', 'explain_statuses', 'recommend_next_action', 'apply_filters', 'apply_search', 'sort_results'],
    'formTarget' => 'filter_form',
    'visibleEntityType' => 'offre',
];
require __DIR__ . '/../../FrontOffice/condidature/cre8pilot_widget.php';
?>
</body>
</html>
