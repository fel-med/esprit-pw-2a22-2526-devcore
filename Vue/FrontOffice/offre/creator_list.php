<?php
session_start();

if (!isset($_SESSION['utilisateur'])) {
    $_SESSION['utilisateur']['id'] = 2;
    $_SESSION['utilisateur']['role'] = 'createur';
}

require_once __DIR__ . '/../../../Controleur/offreC.php';
require_once __DIR__ . '/../../../Controleur/condidatureC.php';

$controller = new OffreC();
$candidatureController = new CondidatureC();
$creatorId = $_SESSION['utilisateur']['id'];
$notificationController = $candidatureController;
$notificationUserId = (int) $creatorId;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notificationAction'])) {
    $notificationAction = (string) $_POST['notificationAction'];
    if ($notificationAction === 'mark_one') {
        $notificationController->markNotificationActionAsRead((int) ($_POST['idNotificationAction'] ?? 0), $notificationUserId);
    } elseif ($notificationAction === 'mark_all') {
        $notificationController->markAllNotificationActionsAsRead($notificationUserId);
    }

    $redirect = basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'creator_list.php'));
    if (!empty($_SERVER['QUERY_STRING'])) {
        $redirect .= '?' . $_SERVER['QUERY_STRING'];
    }
    header('Location: ' . $redirect);
    exit;
}

if ($notificationUserId > 0) {
    $notificationController->generateCreatorDeadlineSoonNotifications($notificationUserId);
}

$offres = [];
$error = null;
$notice = isset($_GET['notice']) ? trim((string) $_GET['notice']) : null;
$noticeType = isset($_GET['noticeType']) ? trim((string) $_GET['noticeType']) : 'success';
$isAjaxRequest = (isset($_REQUEST['ajax']) && (string) $_REQUEST['ajax'] === '1')
    || strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';

function translateOfferStatus($status)
{
    return match ($status) {
        'brouillon' => 'Draft',
        'publiee' => 'Published',
        'cloturee' => 'Closed',
        'expiree' => 'Expired',
        'archivee' => 'Archived',
        'active' => 'Active',
        'fermee', 'closed' => 'Closed',
        default => ucwords(str_replace('_', ' ', (string) $status)),
    };
}

function offerStatusClass($status)
{
    return match ($status) {
        'publiee', 'active' => 'status-published',
        'cloturee', 'fermee', 'closed' => 'status-closed',
        'expiree' => 'status-expired',
        'archivee' => 'status-archived',
        default => 'status-draft',
    };
}

function responseStatusLabel(array $response)
{
    return (string) ($response['displayStatusLabel'] ?? ucwords(str_replace('_', ' ', (string) ($response['statutCandidature'] ?? ''))));
}

function responseStatusClass($status)
{
    return match ($status) {
        'brouillon' => 'draft',
        'envoyee', 'en_attente', 'acceptee' => 'accepted',
        'negociation', 'en_etude' => 'review',
        'refusee', 'retiree' => 'declined',
        default => 'pending',
    };
}

function formatMoney($value)
{
    return 'EUR ' . number_format((float) $value, 2, '.', ',');
}

function isAcceptedResponseStatus($status)
{
    return in_array((string) $status, ['envoyee', 'en_attente', 'acceptee'], true);
}

function isDeclinedResponseStatus($status)
{
    return in_array((string) $status, ['retiree', 'refusee'], true);
}

function isOfferOutdated($offre)
{
    $deadline = DateTime::createFromFormat('Y-m-d', (string) $offre->getDateLimite());
    if (!$deadline) {
        return false;
    }

    return $deadline < new DateTime('today');
}

function getCreatorResponseForOffer(array $responses, $creatorId)
{
    foreach ($responses as $response) {
        if ((int) $response['idCreateur'] === (int) $creatorId) {
            return $response;
        }
    }

    return null;
}

function getCreatorOfferSortRank($response)
{
    if (!$response) {
        return 2;
    }

    $status = (string) ($response['statutCandidature'] ?? '');
    if (isAcceptedResponseStatus($status)) {
        return 0;
    }

    if (in_array($status, ['negociation', 'en_etude'], true)) {
        return 1;
    }

    if (isDeclinedResponseStatus($status)) {
        return 3;
    }

    return 2;
}

function getCreatorOfferSectionKey($response, $offre)
{
    $status = (string) ($response['statutCandidature'] ?? '');

    if (isAcceptedResponseStatus($status)) {
        return 'accepted';
    }

    if (isDeclinedResponseStatus($status)) {
        return 'declined';
    }

    if (isOfferOutdated($offre)) {
        return 'outdated';
    }

    return 'waiting';
}

function getCreatorOfferStageLabel($response, $offre = null)
{
    if (!$response) {
        return $offre && isOfferOutdated($offre) ? 'Outdated' : 'Waiting invitation';
    }

    $status = (string) ($response['statutCandidature'] ?? '');

    return match ($status) {
        'brouillon' => 'Draft response',
        default => responseStatusLabel($response),
    };
}

function getCreatorOfferStageClass($response, $offre = null)
{
    if (!$response) {
        return $offre && isOfferOutdated($offre) ? 'status-outdated' : 'status-waiting';
    }

    $status = (string) ($response['statutCandidature'] ?? '');

    return match ($status) {
        'brouillon' => 'status-draft',
        'negociation', 'en_etude' => 'status-review',
        'envoyee', 'en_attente', 'acceptee' => 'status-accepted',
        'retiree', 'refusee' => 'status-declined',
        default => 'status-waiting',
    };
}

function buildCreatorOfferSections(array $offres, array $responseGroups, array $savedOfferList, $creatorId)
{
    $sections = [
        'waiting' => [
            'key' => 'waiting',
            'themeClass' => 'section-waiting',
            'title' => 'Waiting invitations',
            'subtitle' => 'Invitations you can still review, open, or continue discussing with the brand.',
            'empty' => 'No waiting invitations right now.',
            'cards' => [],
        ],
        'accepted' => [
            'key' => 'accepted',
            'themeClass' => 'section-accepted',
            'title' => 'Accepted invitations',
            'subtitle' => 'Offers you already accepted and kept active in your collaboration pipeline.',
            'empty' => 'You have not accepted any invitation yet.',
            'cards' => [],
        ],
        'declined' => [
            'key' => 'declined',
            'themeClass' => 'section-declined',
            'title' => 'Declined invitations',
            'subtitle' => 'Offers you decided not to continue, kept visible as pipeline history.',
            'empty' => 'No declined invitations in your history.',
            'cards' => [],
        ],
        'outdated' => [
            'key' => 'outdated',
            'themeClass' => 'section-outdated',
            'title' => 'Outdated invitations',
            'subtitle' => 'Invitations whose deadline has already passed without a final answer.',
            'empty' => 'No outdated invitations right now.',
            'cards' => [],
        ],
        'saved' => [
            'key' => 'saved',
            'themeClass' => 'section-waiting',
            'title' => 'Saved invitations',
            'subtitle' => 'Offers you bookmarked so you can come back to them later.',
            'empty' => 'No saved invitations yet. Save invitations to review them later.',
            'cards' => $savedOfferList,
        ],
    ];

    foreach ($offres as $offre) {
        $response = getCreatorResponseForOffer($responseGroups[$offre->getIdOffre()] ?? [], $creatorId);
        $sections[getCreatorOfferSectionKey($response, $offre)]['cards'][] = $offre;
    }

    return array_values($sections);
}

function getDefaultCreatorSectionKey(array $sections)
{
    foreach ($sections as $section) {
        if (!empty($section['cards'])) {
            return $section['key'];
        }
    }

    return 'waiting';
}

function sortCreatorOffersForDisplay(array $offres, array $responseGroups, $creatorId)
{
    usort($offres, static function ($left, $right) use ($responseGroups, $creatorId) {
        $leftResponse = getCreatorResponseForOffer($responseGroups[$left->getIdOffre()] ?? [], $creatorId);
        $rightResponse = getCreatorResponseForOffer($responseGroups[$right->getIdOffre()] ?? [], $creatorId);
        $rankComparison = getCreatorOfferSortRank($leftResponse) <=> getCreatorOfferSortRank($rightResponse);
        if ($rankComparison !== 0) {
            return $rankComparison;
        }

        $budgetComparison = (float) $right->getBudgetPropose() <=> (float) $left->getBudgetPropose();
        if ($budgetComparison !== 0) {
            return $budgetComparison;
        }

        $publicationComparison = strcmp((string) $right->getDatePublication(), (string) $left->getDatePublication());
        if ($publicationComparison !== 0) {
            return $publicationComparison;
        }

        return (int) $right->getIdOffre() <=> (int) $left->getIdOffre();
    });

    return $offres;
}

function excerptText($text, $length = 155)
{
    $text = trim((string) $text);
    if (strlen($text) <= $length) {
        return $text;
    }

    return rtrim(substr($text, 0, max(0, $length - 3))) . '...';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggleSaved'], $_POST['idOffre']) && is_numeric($_POST['idOffre'])) {
    $offerId = (int) $_POST['idOffre'];
    $wasSaved = $controller->isOffreSavedByCreator($creatorId, $offerId);
    $noticeMessage = '';
    $noticeTypeForRedirect = 'success';

    if ($wasSaved) {
        $controller->unsaveOffreForCreator($creatorId, $offerId);
        $noticeMessage = 'Invitation removed from your saved list.';
    } else {
        if ($controller->saveOffreForCreator($creatorId, $offerId)) {
            $noticeMessage = 'Invitation saved for later.';
        } else {
            $noticeMessage = 'This invitation cannot be saved anymore.';
            $noticeTypeForRedirect = 'danger';
        }
    }

    if (!$isAjaxRequest) {
        $redirectQuery = $_GET;
        $redirectQuery['notice'] = $noticeMessage;
        $redirectQuery['noticeType'] = $noticeTypeForRedirect;
        $redirect = 'creator_list.php?' . http_build_query($redirectQuery);
        header('Location: ' . $redirect);
        exit;
    }
}

$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : null;
$budgetFrom = isset($_GET['budgetFrom']) && is_numeric($_GET['budgetFrom']) ? (float) $_GET['budgetFrom'] : null;
$budgetTo = isset($_GET['budgetTo']) && is_numeric($_GET['budgetTo']) ? (float) $_GET['budgetTo'] : null;
$dateLimite = isset($_GET['dateLimite']) && $_GET['dateLimite'] !== '' ? $_GET['dateLimite'] : null;
$dateLimiteTo = isset($_GET['dateLimiteTo']) && $_GET['dateLimiteTo'] !== '' ? $_GET['dateLimiteTo'] : null;
$sort = isset($_GET['sort']) ? trim((string) $_GET['sort']) : '';
$savedOfferFilters = [
    'keyword' => $keyword,
    'budgetFrom' => $budgetFrom,
    'budgetTo' => $budgetTo,
    'deadlineFrom' => $dateLimite,
    'deadlineTo' => $dateLimiteTo,
];
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;
$hasNextPage = false;
$creatorMetrics = [
    'invitationsToAnswer' => 0,
    'negotiationsWaitingReply' => 0,
    'closestDeadline' => null,
    'bestProposedBudget' => 0,
    'applicationsWaitingDecision' => 0,
    'draftApplications' => 0,
    'acceptedCollaborations' => 0,
];

try {
    $offres = $controller->searchOffers($creatorId, $keyword, $budgetFrom, $budgetTo, $dateLimite, $dateLimiteTo, $sort ?: 'budget_high', $perPage + 1, $offset);
    if (count($offres) > $perPage) {
        $hasNextPage = true;
        array_pop($offres);
    }
    $offerIds = array_map(static fn($offre) => $offre->getIdOffre(), $offres);
    $responseGroups = $controller->getCandidaturesGroupedByOfferIds($offerIds);
    if ($sort === '') {
        $offres = sortCreatorOffersForDisplay($offres, $responseGroups, $creatorId);
    }
    $creatorMetrics = $candidatureController->getCreatorActionMetrics($creatorId);
} catch (Exception $exception) {
    $error = 'An error occurred while loading your invitations.';
}

$offerIds = array_map(static fn($offre) => $offre->getIdOffre(), $offres);
$brandIds = array_map(static fn($offre) => $offre->getIdMarque(), $offres);
$brandMap = $controller->getUsersByIds($brandIds, 'marque');
$responseGroups = $controller->getCandidaturesGroupedByOfferIds($offerIds);
$savedOffers = $controller->getSavedOffreIdsByCreator($creatorId);
$savedOfferList = [];
$savedBrandMap = [];
$savedResponseGroups = [];

if (!empty($savedOffers)) {
    $savedOfferList = $controller->getSavedOffresByCreator($creatorId, $savedOfferFilters, $sort === '' ? 'recently_saved' : $sort, 100, 0);
    $savedOfferIds = array_map(static fn($offre) => $offre->getIdOffre(), $savedOfferList);
    $savedBrandIds = array_map(static fn($offre) => $offre->getIdMarque(), $savedOfferList);
    $savedBrandMap = $controller->getUsersByIds($savedBrandIds, 'marque');
    $savedResponseGroups = $controller->getCandidaturesGroupedByOfferIds($savedOfferIds);
}

$creatorSections = buildCreatorOfferSections($offres, $responseGroups, $savedOfferList, $creatorId);
$creatorDefaultSectionKey = getDefaultCreatorSectionKey($creatorSections);

$closingSoon = 0;
$respondedOffers = 0;
$averageBudget = 0;
$topBudget = null;
$topBudgetOffer = null;

if (!empty($offres)) {
    $averageBudgetOffers = array_values(array_filter($offres, static fn($offre) => !isOfferOutdated($offre)));
    if (!empty($averageBudgetOffers)) {
        $averageBudget = array_sum(array_map(static fn($offre) => (float) $offre->getBudgetPropose(), $averageBudgetOffers)) / count($averageBudgetOffers);
    }
    $today = new DateTime('today');

    foreach ($offres as $offre) {
        $creatorResponse = getCreatorResponseForOffer($responseGroups[$offre->getIdOffre()] ?? [], $creatorId);
        $deadline = DateTime::createFromFormat('Y-m-d', (string) $offre->getDateLimite());
        if ($deadline) {
            $days = (int) $today->diff($deadline)->format('%r%a');
            if ($days >= 0 && $days <= 7) {
                $closingSoon++;
            }
        }

        if ($creatorResponse) {
            $respondedOffers++;
        }

        if (!isOfferOutdated($offre) && !$creatorResponse && ($topBudget === null || (float) $offre->getBudgetPropose() > $topBudget)) {
            $topBudget = (float) $offre->getBudgetPropose();
            $topBudgetOffer = $offre;
        }
    }
}
$paginationBase = $_GET;
unset($paginationBase['page']);
$prevPageUrl = $page > 1 ? 'creator_list.php?' . http_build_query($paginationBase + ['page' => $page - 1]) : '';
$nextPageUrl = $hasNextPage ? 'creator_list.php?' . http_build_query($paginationBase + ['page' => $page + 1]) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offers for You - Cre8Connect</title>
    <link rel="stylesheet" href="../css/frontoffice.css">
    <link rel="stylesheet" href="offre.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/offre.css')); ?>">
</head>
<body>
    <?php require_once dirname(__DIR__) . '/layout/header.php'; ?>
    <main class="container py-5">
        <div class="offre-page-shell">
            <section class="module-hero module-hero-notification-shell">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div>
                        <span class="module-eyebrow">Creator inbox</span>
                        <h1 class="display-5 fw-bold mt-3 mb-2 gradient-title">Offers for you</h1>
                        <p class="lead text-muted">Browse targeted invitations from brands, save the ones you want to revisit, and respond when the collaboration feels right.</p>
                    </div>
                    <div class="compact-actions">
                        <?php require __DIR__ . '/../condidature/notification_widget.php'; ?>
                    </div>
                </div>
            </section>

            <div class="creator-live-region" data-creator-live-region>
                <section class="stats-grid">
                <article class="stat-card">
                    <span class="stat-label">Invitations to answer</span>
                    <span class="stat-value"><?php echo (int) ($creatorMetrics['invitationsToAnswer'] ?? 0); ?></span>
                    <span class="stat-note">Active offers without a final response</span>
                </article>
                <article class="stat-card">
                    <span class="stat-label">Negotiations waiting reply</span>
                    <span class="stat-value"><?php echo (int) ($creatorMetrics['negotiationsWaitingReply'] ?? 0); ?></span>
                    <span class="stat-note">Active negotiation candidatures</span>
                </article>
                <article class="stat-card">
                    <span class="stat-label">Closest deadline</span>
                    <span class="stat-value"><?php echo htmlspecialchars($creatorMetrics['closestDeadline'] ?: 'None'); ?></span>
                    <span class="stat-note">Nearest active invitation or candidature</span>
                </article>
                <article class="stat-card">
                    <span class="stat-label">Best proposed budget</span>
                    <span class="stat-value"><?php echo htmlspecialchars(formatMoney($creatorMetrics['bestProposedBudget'] ?? 0)); ?></span>
                    <span class="stat-note"><?php echo (int) ($creatorMetrics['draftApplications'] ?? 0); ?> draft applications</span>
                </article>
            </section>

            <section class="section-card saved-offer-section" hidden>
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div>
                        <h2 class="section-title">Saved for later</h2>
                        <p class="section-subtitle">Saved offers stay here so you can come back to them later.</p>
                    </div>
                    <span class="offer-chip"><?php echo count($savedOfferList); ?> saved</span>
                </div>

                <?php if (!empty($savedOfferList)): ?>
                    <div class="saved-offer-grid mt-4">
                        <?php foreach ($savedOfferList as $offre): ?>
                            <?php
                            $brand = $savedBrandMap[$offre->getIdMarque()] ?? null;
                            $creatorResponse = getCreatorResponseForOffer($savedResponseGroups[$offre->getIdOffre()] ?? [], $creatorId);
                            $isAccepted = $creatorResponse && isAcceptedResponseStatus($creatorResponse['statutCandidature'] ?? '');
                            $isDeclined = $creatorResponse && isDeclinedResponseStatus($creatorResponse['statutCandidature'] ?? '');
                            ?>
                            <article
                                class="saved-offer-card<?php echo $isAccepted ? ' is-accepted' : ($isDeclined ? ' is-declined' : (isOfferOutdated($offre) ? ' is-outdated' : '')); ?>"
                                data-card-href="creator_details.php?idOffre=<?php echo (int) $offre->getIdOffre(); ?>&idCreateur=<?php echo (int) $creatorId; ?>"
                            >
                                <div class="saved-offer-head">
                                    <div>
                                        <div class="offer-flag-row mb-2">
                                            <?php if ($isAccepted): ?>
                                                <span class="priority-badge priority-badge-success">Accepted</span>
                                            <?php elseif ($isDeclined): ?>
                                                <span class="priority-badge priority-badge-danger">Declined</span>
                                            <?php else: ?>
                                                <span class="saved-badge">Saved</span>
                                            <?php endif; ?>
                                        </div>
                                        <h3><?php echo htmlspecialchars($offre->getTitre()); ?></h3>
                                        <p><?php echo htmlspecialchars(excerptText($offre->getDescription(), 110)); ?></p>
                                    </div>
                                </div>

                                <div class="saved-offer-meta">
                                    <span class="offer-chip"><?php echo htmlspecialchars(formatMoney($offre->getBudgetPropose())); ?></span>
                                    <span class="offer-chip">Deadline: <?php echo htmlspecialchars($offre->getDateLimite()); ?></span>
                                    <?php if ($brand): ?>
                                        <span class="offer-chip">Brand: <?php echo htmlspecialchars($brand['nom']); ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="saved-offer-actions">
                                    <form method="post" action="creator_list.php<?php echo !empty($_SERVER['QUERY_STRING']) ? '?' . htmlspecialchars($_SERVER['QUERY_STRING']) : ''; ?>" data-save-toggle-form>
                                        <input type="hidden" name="idOffre" value="<?php echo (int) $offre->getIdOffre(); ?>">
                                        <button type="submit" name="toggleSaved" class="btn btn-outline-secondary">Remove</button>
                                    </form>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="note-block mt-4">
                        <strong>No saved offers yet</strong>
                        <p>Use the “Save for later” button on any invitation and it will appear here for quick access.</p>
                    </div>
                <?php endif; ?>
            </section>

            <section class="filter-card">
                <h2 class="section-title">Filter your invitation inbox</h2>
                <p class="section-subtitle">Narrow the list by topic, budget, or deadline.</p>
                <form method="get" action="creator_list.php" class="filter-stack mt-4" data-module-validation="creator-filters" data-creator-filter-form novalidate>
                    <div class="filter-grid">
                        <div>
                            <label for="keyword" class="form-label fw-semibold">Keyword</label>
                            <input type="text" class="form-control" id="keyword" name="keyword" value="<?php echo htmlspecialchars($keyword ?? ''); ?>" placeholder="Brand message, offer title, objective...">
                        </div>
                        <div>
                            <label for="budgetFrom" class="form-label fw-semibold">Budget from</label>
                            <input type="number" class="form-control" id="budgetFrom" name="budgetFrom" value="<?php echo htmlspecialchars($budgetFrom ?? ''); ?>" step="0.01">
                        </div>
                        <div>
                            <label for="budgetTo" class="form-label fw-semibold">Budget to</label>
                            <input type="number" class="form-control" id="budgetTo" name="budgetTo" value="<?php echo htmlspecialchars($budgetTo ?? ''); ?>" step="0.01">
                        </div>
                        <div>
                            <label for="dateLimite" class="form-label fw-semibold">Deadline from</label>
                            <input type="date" class="form-control" id="dateLimite" name="dateLimite" value="<?php echo htmlspecialchars($dateLimite ?? ''); ?>">
                        </div>
                        <div>
                            <label for="dateLimiteTo" class="form-label fw-semibold">Deadline to</label>
                            <input type="date" class="form-control" id="dateLimiteTo" name="dateLimiteTo" value="<?php echo htmlspecialchars($dateLimiteTo ?? ''); ?>">
                        </div>
                        <div>
                            <label for="sort" class="form-label fw-semibold">Sort</label>
                            <select class="form-select" id="sort" name="sort">
                                <option value=""<?php echo $sort === '' ? ' selected' : ''; ?>>Recommended</option>
                                <option value="recently_saved"<?php echo $sort === 'recently_saved' ? ' selected' : ''; ?>>Recently saved</option>
                                <option value="newest"<?php echo $sort === 'newest' ? ' selected' : ''; ?>>Newest</option>
                                <option value="oldest"<?php echo $sort === 'oldest' ? ' selected' : ''; ?>>Oldest</option>
                                <option value="deadline_soon"<?php echo $sort === 'deadline_soon' ? ' selected' : ''; ?>>Deadline soon</option>
                                <option value="budget_high"<?php echo $sort === 'budget_high' ? ' selected' : ''; ?>>Budget high to low</option>
                                <option value="budget_low"<?php echo $sort === 'budget_low' ? ' selected' : ''; ?>>Budget low to high</option>
                                <option value="status"<?php echo $sort === 'status' ? ' selected' : ''; ?>>Status</option>
                            </select>
                        </div>
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary">Apply filters</button>
                        <a class="btn btn-outline-secondary" href="creator_list.php" data-creator-reset-link>Reset</a>
                    </div>
                </form>
            </section>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($notice): ?>
                <div class="alert alert-<?php echo $noticeType === 'danger' ? 'danger' : 'success'; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($notice); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($offres) || !empty($savedOfferList)): ?>
                <section class="offer-tab-shell" data-offer-tab-shell data-default-tab="<?php echo htmlspecialchars($creatorDefaultSectionKey); ?>">
                    <div class="offer-tab-list" role="tablist" aria-label="Creator invitation tabs">
                        <?php foreach ($creatorSections as $section): ?>
                            <?php $isActiveTab = $section['key'] === $creatorDefaultSectionKey; ?>
                            <button
                                type="button"
                                class="offer-tab-button<?php echo $isActiveTab ? ' is-active' : ''; ?>"
                                id="creator-tab-<?php echo htmlspecialchars($section['key']); ?>"
                                role="tab"
                                aria-selected="<?php echo $isActiveTab ? 'true' : 'false'; ?>"
                                aria-controls="creator-panel-<?php echo htmlspecialchars($section['key']); ?>"
                                data-offer-tab="<?php echo htmlspecialchars($section['key']); ?>"
                            >
                                <span class="offer-tab-label"><?php echo htmlspecialchars($section['title']); ?></span>
                                <span class="offer-tab-badge"><?php echo count($section['cards']); ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <div class="offer-tab-panels">
                        <?php foreach ($creatorSections as $section): ?>
                            <?php $isActivePanel = $section['key'] === $creatorDefaultSectionKey; ?>
                            <section
                                class="section-card offer-section-card <?php echo htmlspecialchars($section['themeClass']); ?> offer-tab-panel"
                                id="creator-panel-<?php echo htmlspecialchars($section['key']); ?>"
                                role="tabpanel"
                                aria-labelledby="creator-tab-<?php echo htmlspecialchars($section['key']); ?>"
                                data-offer-tab-panel="<?php echo htmlspecialchars($section['key']); ?>"
                                <?php echo $isActivePanel ? '' : 'hidden'; ?>
                            >
                                <div class="offer-section-header">
                                    <div class="offer-section-copy">
                                        <h2 class="section-title"><?php echo htmlspecialchars($section['title']); ?></h2>
                                        <p class="section-subtitle"><?php echo htmlspecialchars($section['subtitle']); ?></p>
                                    </div>
                                    <span class="offer-section-count">
                                        <?php echo count($section['cards']); ?> offer<?php echo count($section['cards']) === 1 ? '' : 's'; ?>
                                    </span>
                                </div>

                                <?php if (!empty($section['cards'])): ?>
                                    <?php if ($section['key'] === 'saved'): ?>
                                        <div class="saved-offer-grid">
                                            <?php foreach ($section['cards'] as $offre): ?>
                                                <?php
                                                $brand = $savedBrandMap[$offre->getIdMarque()] ?? ($brandMap[$offre->getIdMarque()] ?? null);
                                                $creatorResponse = getCreatorResponseForOffer($savedResponseGroups[$offre->getIdOffre()] ?? [], $creatorId);
                                                $isAccepted = $creatorResponse && isAcceptedResponseStatus($creatorResponse['statutCandidature'] ?? '');
                                                $isDeclined = $creatorResponse && isDeclinedResponseStatus($creatorResponse['statutCandidature'] ?? '');
                                                ?>
                                                <article
                                                    class="saved-offer-card<?php echo $isAccepted ? ' is-accepted' : ($isDeclined ? ' is-declined' : (isOfferOutdated($offre) ? ' is-outdated' : '')); ?>"
                                                    data-card-href="creator_details.php?idOffre=<?php echo (int) $offre->getIdOffre(); ?>&idCreateur=<?php echo (int) $creatorId; ?>"
                                                >
                                                    <div class="saved-offer-head">
                                                        <div>
                                                            <div class="offer-flag-row mb-2">
                                                                <?php if ($isAccepted): ?>
                                                                    <span class="priority-badge priority-badge-success">Accepted</span>
                                                                <?php elseif ($isDeclined): ?>
                                                                    <span class="priority-badge priority-badge-danger">Declined</span>
                                                                <?php else: ?>
                                                                    <span class="saved-badge">Saved</span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <h3><?php echo htmlspecialchars($offre->getTitre()); ?></h3>
                                                            <p><?php echo htmlspecialchars(excerptText($offre->getDescription(), 110)); ?></p>
                                                        </div>
                                                    </div>

                                                    <div class="saved-offer-meta">
                                                        <span class="offer-chip"><?php echo htmlspecialchars(formatMoney($offre->getBudgetPropose())); ?></span>
                                                        <span class="offer-chip">Deadline: <?php echo htmlspecialchars($offre->getDateLimite()); ?></span>
                                                        <?php if ($brand): ?>
                                                            <span class="offer-chip">Brand: <?php echo htmlspecialchars($brand['nom']); ?></span>
                                                        <?php endif; ?>
                                                    </div>

                                                    <div class="saved-offer-actions">
                                                        <form method="post" action="creator_list.php<?php echo !empty($_SERVER['QUERY_STRING']) ? '?' . htmlspecialchars($_SERVER['QUERY_STRING']) : ''; ?>" data-save-toggle-form>
                                                            <input type="hidden" name="idOffre" value="<?php echo (int) $offre->getIdOffre(); ?>">
                                                            <button type="submit" name="toggleSaved" class="btn btn-outline-secondary">Remove saved</button>
                                                        </form>
                                                    </div>
                                                </article>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="offer-grid">
                                            <?php foreach ($section['cards'] as $offre): ?>
                                                <?php
                                                $brand = $brandMap[$offre->getIdMarque()] ?? null;
                                                $saved = in_array($offre->getIdOffre(), $savedOffers, true);
                                                $creatorResponse = getCreatorResponseForOffer($responseGroups[$offre->getIdOffre()] ?? [], $creatorId);
                                                $isAccepted = $creatorResponse && isAcceptedResponseStatus($creatorResponse['statutCandidature'] ?? '');
                                                $isDeclined = $creatorResponse && isDeclinedResponseStatus($creatorResponse['statutCandidature'] ?? '');
                                                $isTopBudget = !$creatorResponse && $topBudget !== null && (float) $offre->getBudgetPropose() === (float) $topBudget;
                                                ?>
                                                <?php $isOutdated = isOfferOutdated($offre); ?>
                                                <article
                                                    class="offer-card<?php echo $isAccepted ? ' is-accepted' : ($isDeclined ? ' is-declined' : ($isOutdated ? ' is-outdated' : ($isTopBudget ? ' is-top-budget' : ''))); ?>"
                                                    data-card-href="creator_details.php?idOffre=<?php echo (int) $offre->getIdOffre(); ?>&idCreateur=<?php echo (int) $creatorId; ?>"
                                                >
                                                    <div class="offer-card-head">
                                                        <div>
                                                            <div class="offer-flag-row mb-2">
                                                                <span class="offer-status <?php echo htmlspecialchars(getCreatorOfferStageClass($creatorResponse, $offre)); ?>">
                                                                    <?php echo htmlspecialchars(getCreatorOfferStageLabel($creatorResponse, $offre)); ?>
                                                                </span>
                                                                <?php if ($isTopBudget): ?>
                                                                    <span class="priority-badge priority-badge-gold">Top budget match</span>
                                                                <?php endif; ?>
                                                                <?php if ($saved): ?>
                                                                    <span class="saved-badge">Saved</span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <h2 class="offer-card-title"><?php echo htmlspecialchars($offre->getTitre()); ?></h2>
                                                            <p class="offer-summary mt-2"><?php echo htmlspecialchars(excerptText($offre->getDescription(), 155)); ?></p>
                                                        </div>
                                                        <?php if (!$creatorResponse): ?>
                                                            <form method="post" action="creator_list.php<?php echo !empty($_SERVER['QUERY_STRING']) ? '?' . htmlspecialchars($_SERVER['QUERY_STRING']) : ''; ?>" data-save-toggle-form>
                                                                <input type="hidden" name="idOffre" value="<?php echo (int) $offre->getIdOffre(); ?>">
                                                                <button type="submit" name="toggleSaved" class="saved-toggle <?php echo $saved ? 'saved' : ''; ?>">
                                                                    <?php echo $saved ? 'Remove saved' : 'Save for later'; ?>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>

                                                    <div class="offer-meta">
                                                        <span class="offer-chip"><?php echo htmlspecialchars(formatMoney($offre->getBudgetPropose())); ?></span>
                                                        <span class="offer-chip">Deadline: <?php echo htmlspecialchars($offre->getDateLimite()); ?></span>
                                                        <?php if ($brand): ?>
                                                            <span class="offer-chip">Brand: <?php echo htmlspecialchars($brand['nom']); ?></span>
                                                        <?php endif; ?>
                                                    </div>

                                                    <div class="offer-detail-list">
                                                        <div class="offer-detail-item">
                                                            <strong>Brand</strong>
                                                            <span><?php echo htmlspecialchars($brand['nom'] ?? 'Unknown brand'); ?></span>
                                                            <p><?php echo htmlspecialchars($brand['email'] ?? ''); ?></p>
                                                        </div>
                                                        <div class="offer-detail-item">
                                                            <strong>Objective</strong>
                                                            <span><?php echo htmlspecialchars($offre->getObjectif()); ?></span>
                                                        </div>
                                                    </div>

                                                    <?php if ($offre->getRaisonChoix() !== ''): ?>
                                                        <div class="note-block">
                                                            <strong>Why you were picked</strong>
                                                            <p><?php echo htmlspecialchars(excerptText($offre->getRaisonChoix(), 170)); ?></p>
                                                        </div>
                                                    <?php endif; ?>

                                                    <?php if ($creatorResponse): ?>
                                                        <div class="response-callout<?php echo $isAccepted ? ' response-callout-accepted' : ($isDeclined ? ' response-callout-declined' : ''); ?>">
                                                            <strong>Your current response</strong>
                                                            <div class="mt-2 d-flex flex-wrap gap-2 align-items-center">
                                                                <span class="response-status <?php echo htmlspecialchars(responseStatusClass($creatorResponse['statutCandidature'])); ?>">
                                                                    <?php echo htmlspecialchars(responseStatusLabel($creatorResponse)); ?>
                                                                </span>
                                                                <span class="text-muted small">
                                                                    <?php if ($isDeclined): ?>
                                                                        This invitation stays in your pipeline history.
                                                                    <?php elseif (($creatorResponse['statutCandidature'] ?? '') === 'brouillon'): ?>
                                                                        Draft response saved. Open the invitation to finish it when you are ready.
                                                                    <?php else: ?>
                                                                        Budget reply: EUR <?php echo htmlspecialchars($creatorResponse['budgetPropose']); ?>
                                                                    <?php endif; ?>
                                                                </span>
                                                            </div>
                                                        </div>
                                                    <?php elseif ($isOutdated): ?>
                                                        <div class="response-callout">
                                                            <strong>Deadline passed</strong>
                                                            <div class="mt-2 text-muted small">This invitation is kept for history, but the response window has ended.</div>
                                                        </div>
                                                    <?php elseif ($section['key'] === 'waiting'): ?>
                                                        <div class="response-callout">
                                                            <strong>Waiting for your response</strong>
                                                            <div class="mt-2 text-muted small">This invitation is still open and ready for your decision.</div>
                                                        </div>
                                                    <?php endif; ?>
                                                </article>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="note-block offer-section-empty">
                                        <strong><?php echo htmlspecialchars($section['title']); ?></strong>
                                        <p><?php echo htmlspecialchars($section['empty']); ?></p>
                                    </div>
                                <?php endif; ?>
                            </section>
                        <?php endforeach; ?>
                    </div>
                </section>
                <nav class="front-pagination" aria-label="Creator invitation pages">
                    <span>Page <?php echo $page; ?> · Showing up to <?php echo $perPage; ?> filtered invitations</span>
                    <div>
                        <?php if ($prevPageUrl !== ''): ?>
                            <a class="btn btn-outline-secondary" href="<?php echo htmlspecialchars($prevPageUrl); ?>">Previous</a>
                        <?php endif; ?>
                        <?php if ($nextPageUrl !== ''): ?>
                            <a class="btn btn-primary" href="<?php echo htmlspecialchars($nextPageUrl); ?>">Load more</a>
                        <?php endif; ?>
                    </div>
                </nav>
            <?php else: ?>
                <section class="empty-state-card">
                    <div class="empty-state-icon">!</div>
                    <h2 class="section-title">No targeted offers found</h2>
                    <p class="section-subtitle">Adjust the filters and check back soon for new brand invitations.</p>
                    <div class="compact-actions justify-content-center mt-4">
                        <a class="btn btn-outline-secondary" href="creator_list.php" data-creator-reset-link>Reset filters</a>
                    </div>
                </section>
            <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
    <script src="offre-tabs.js?v=<?php echo urlencode((string) filemtime(__DIR__ . '/offre-tabs.js')); ?>"></script>
    <script src="offre-validation.js"></script>
    <script src="creator-list-live.js?v=<?php echo urlencode((string) filemtime(__DIR__ . '/creator-list-live.js')); ?>"></script>
<?php
$cre8PilotContext = [
    'page' => 'creator_offer_workspace',
    'mode' => 'list',
    'role' => 'createur',
    'allowedActions' => ['normal_chat', 'summarize_page', 'analyze_page', 'apply_filters', 'recommend_next_action', 'creator_collaboration_draft', 'explain_statuses', 'apply_search', 'sort_results'],
    'formTarget' => 'filter_form',
    'visibleEntityType' => 'offre',
];
require __DIR__ . '/../condidature/cre8pilot_widget.php';
?>
</body>
</html>
