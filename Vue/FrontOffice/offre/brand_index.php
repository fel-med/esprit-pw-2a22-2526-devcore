<?php
require_once __DIR__ . '/../layout/session_bridge.php';
$currentUser = cre8_front_require_user('marque');
$frontActive = 'collaborations';
require_once __DIR__ . '/../layout/avatar_helper.php';

require_once __DIR__ . '/../../../Controleur/offreC.php';
require_once __DIR__ . '/../../../Controleur/condidatureC.php';

$brandId = (int) $currentUser['id'];
$controller = new OffreC();
$candidatureController = new CondidatureC();

$offres = [];
$error = null;

function translateOfferStatus($status)
{
    return match ($status) {
        'brouillon' => 'Draft',
        'pending' => 'Pending launch',
        'publiee' => 'Published',
        'cloturee' => 'Closed',
        'expiree' => 'Expired',
        'archivee' => 'Archived',
        'active' => 'Active',
        'fermee', 'closed' => 'Closed',
        default => ucwords(str_replace('_', ' ', (string) $status)),
    };
}

function offerStatusI18nKey($status)
{
    return match ((string) $status) {
        'brouillon' => 'offer.status.draft',
        'pending' => 'offer.status.pendingLaunch',
        'publiee' => 'offer.status.published',
        'cloturee', 'fermee', 'closed' => 'offer.status.closed',
        'expiree' => 'offer.status.expired',
        'archivee' => 'offer.status.archived',
        'active' => 'offer.status.active',
        default => '',
    };
}

function offerStatusClass($status)
{
    return match ($status) {
        'brouillon' => 'status-draft',
        'pending' => 'status-pending',
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

function responseStatusI18nKey(array $response)
{
    return match ((string) ($response['statutCandidature'] ?? '')) {
        'brouillon' => 'offer.responseStatus.draft',
        'envoyee', 'en_attente' => 'offer.responseStatus.waitingDecision',
        'en_etude' => 'offer.responseStatus.underReview',
        'negociation' => 'offer.responseStatus.negotiation',
        'acceptee' => 'offer.responseStatus.accepted',
        'refusee' => 'offer.responseStatus.refused',
        'retiree' => 'offer.responseStatus.withdrawn',
        default => '',
    };
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

function translateOfferFlashMessage($message)
{
    $translations = [
        'Offre creee avec succes.' => 'Offer created successfully.',
        'Offre créée avec succès.' => 'Offer created successfully.',
        'Offre mise a jour avec succes.' => 'Offer updated successfully.',
        'Offre mise à jour avec succès.' => 'Offer updated successfully.',
        'Offre supprimee avec succes.' => 'Offer deleted successfully.',
        'Offre supprimée avec succès.' => 'Offer deleted successfully.',
        'Impossible de supprimer cette offre.' => 'Unable to delete this offer.',
    ];

    return $translations[$message] ?? $message;
}

function formatMoney($value)
{
    return 'EUR ' . number_format((float) $value, 2, '.', ',');
}

function excerptText($text, $length = 165)
{
    $text = trim((string) $text);
    if (strlen($text) <= $length) {
        return $text;
    }

    return rtrim(substr($text, 0, max(0, $length - 3))) . '...';
}

function isAcceptedTargetResponseStatus($status)
{
    return in_array((string) $status, ['envoyee', 'en_attente', 'acceptee'], true);
}

function isDeclinedTargetResponseStatus($status)
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

function getTargetResponseSortRank($response)
{
    if (!$response) {
        return 2;
    }

    $status = (string) ($response['statutCandidature'] ?? '');
    if (isAcceptedTargetResponseStatus($status)) {
        return 0;
    }

    if (in_array($status, ['negociation', 'en_etude'], true)) {
        return 1;
    }

    if (isDeclinedTargetResponseStatus($status)) {
        return 3;
    }

    return 2;
}

function buildBrandOfferCards(array $offres, array $creatorMap, array $responseGroups)
{
    $cards = [];

    foreach ($offres as $offre) {
        $responses = $responseGroups[$offre->getIdOffre()] ?? [];
        $targetedResponse = null;
        $hasRealTargetCreator = !$offre->isDraftSansCreateur() && $offre->getIdCreateurCible();

        if ($hasRealTargetCreator) {
            foreach ($responses as $response) {
                if ((int) $response['idCreateur'] === (int) $offre->getIdCreateurCible()) {
                    $targetedResponse = $response;
                    break;
                }
            }
        }

        $cards[] = [
            'offre' => $offre,
            'creator' => $hasRealTargetCreator ? ($creatorMap[$offre->getIdCreateurCible()] ?? null) : null,
            'responses' => $responses,
            'targetedResponse' => $targetedResponse,
            'isAccepted' => $targetedResponse && isAcceptedTargetResponseStatus($targetedResponse['statutCandidature'] ?? ''),
            'isDeclined' => $targetedResponse && isDeclinedTargetResponseStatus($targetedResponse['statutCandidature'] ?? ''),
            'isOutdated' => isOfferOutdated($offre),
            'acceptedAt' => $targetedResponse['dateCandidature'] ?? null,
        ];
    }

    usort($cards, static function ($left, $right) {
        $rankComparison = getTargetResponseSortRank($left['targetedResponse']) <=> getTargetResponseSortRank($right['targetedResponse']);
        if ($rankComparison !== 0) {
            return $rankComparison;
        }

        if ($left['isAccepted'] && $right['isAccepted']) {
            $acceptedDateComparison = strcmp((string) ($right['acceptedAt'] ?? ''), (string) ($left['acceptedAt'] ?? ''));
            if ($acceptedDateComparison !== 0) {
                return $acceptedDateComparison;
            }
        }

        $budgetComparison = (float) $right['offre']->getBudgetPropose() <=> (float) $left['offre']->getBudgetPropose();
        if ($budgetComparison !== 0) {
            return $budgetComparison;
        }

        $publicationComparison = strcmp((string) $right['offre']->getDatePublication(), (string) $left['offre']->getDatePublication());
        if ($publicationComparison !== 0) {
            return $publicationComparison;
        }

        return (int) $right['offre']->getIdOffre() <=> (int) $left['offre']->getIdOffre();
    });

    return $cards;
}

function getBrandOfferSectionKey(array $card)
{
    $offre = $card['offre'];
    $displayStatus = $offre->getDisplayStatusKey();

    if ($card['isAccepted']) {
        return 'accepted';
    }

    if ($card['isDeclined']) {
        return 'declined';
    }

    if ($card['isOutdated']) {
        return 'outdated';
    }

    if (
        in_array($displayStatus, ['brouillon', 'pending'], true)
        || $offre->isPendingPublication()
        || $offre->isDraftSansCreateur()
    ) {
        return 'draft-pending';
    }

    return 'published';
}

function buildBrandOfferSections(array $offerCards)
{
    $sections = [
        'published' => [
            'key' => 'published',
            'domId' => 'brandPublishedGrid',
            'themeClass' => 'section-published',
            'title' => 'Published offers',
            'titleKey' => 'offer.section.published',
            'subtitle' => 'Offers that are already visible to creators.',
            'subtitleKey' => 'offer.section.publishedCopy',
            'empty' => 'No live published offers right now.',
            'emptyKey' => 'offer.section.noPublished',
            'cards' => [],
        ],
        'accepted' => [
            'key' => 'accepted',
            'domId' => 'brandAcceptedGrid',
            'themeClass' => 'section-accepted',
            'title' => 'Accepted offers',
            'titleKey' => 'offer.section.accepted',
            'subtitle' => 'Offers already accepted by the targeted creator.',
            'subtitleKey' => 'offer.section.acceptedCopy',
            'empty' => 'No creator has accepted an offer yet.',
            'emptyKey' => 'offer.section.noAccepted',
            'cards' => [],
        ],
        'draft-pending' => [
            'key' => 'draft-pending',
            'domId' => 'brandDraftPendingGrid',
            'themeClass' => 'section-draft-pending',
            'title' => 'Draft / pending offers',
            'titleKey' => 'offer.section.draftPending',
            'subtitle' => 'Offers still in draft or waiting for their publication date.',
            'subtitleKey' => 'offer.section.draftPendingCopy',
            'empty' => 'No draft or scheduled offers at the moment.',
            'emptyKey' => 'offer.section.noDraftPending',
            'cards' => [],
        ],
        'declined' => [
            'key' => 'declined',
            'domId' => 'brandDeclinedGrid',
            'themeClass' => 'section-declined',
            'title' => 'Declined offers',
            'titleKey' => 'offer.section.declined',
            'subtitle' => 'Offers kept in the history after the creator declined them.',
            'subtitleKey' => 'offer.section.declinedCopy',
            'empty' => 'No declined offers in your pipeline.',
            'emptyKey' => 'offer.section.noDeclined',
            'cards' => [],
        ],
        'outdated' => [
            'key' => 'outdated',
            'domId' => 'brandOutdatedGrid',
            'themeClass' => 'section-outdated',
            'title' => 'Outdated offers',
            'titleKey' => 'offer.section.outdated',
            'subtitle' => 'Offers whose deadline has already passed without a final creator acceptance or decline.',
            'subtitleKey' => 'offer.section.outdatedCopy',
            'empty' => 'No outdated offers in your pipeline.',
            'emptyKey' => 'offer.section.noOutdated',
            'cards' => [],
        ],
    ];

    foreach ($offerCards as $card) {
        $sections[getBrandOfferSectionKey($card)]['cards'][] = $card;
    }

    return array_values($sections);
}

function getDefaultBrandSectionKey(array $sections)
{
    foreach ($sections as $section) {
        if (!empty($section['cards'])) {
            return $section['key'];
        }
    }

    return 'published';
}

$filters = [
    'keyword' => trim((string) ($_GET['keyword'] ?? '')),
    'status' => trim((string) ($_GET['status'] ?? '')),
    'budgetFrom' => trim((string) ($_GET['budgetFrom'] ?? '')),
    'budgetTo' => trim((string) ($_GET['budgetTo'] ?? '')),
    'deadlineFrom' => trim((string) ($_GET['deadlineFrom'] ?? '')),
    'deadlineTo' => trim((string) ($_GET['deadlineTo'] ?? '')),
    'sort' => trim((string) ($_GET['sort'] ?? '')),
];
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;
$hasNextPage = false;

if ($brandId !== null) {
    $pagedFilters = $filters + [
        'limit' => $perPage + 1,
        'offset' => $offset,
    ];
    $offres = $controller->getOffresByMarque($brandId, $pagedFilters);
    if (count($offres) > $perPage) {
        $hasNextPage = true;
        array_pop($offres);
    }
} else {
    $error = 'Brand ID is missing.';
}

$message = isset($_GET['message']) ? translateOfferFlashMessage($_GET['message']) : null;
$offerIds = array_map(static fn($offre) => $offre->getIdOffre(), $offres);
$creatorIds = array_map(static fn($offre) => $offre->getIdCreateurCible(), $offres);
$creatorMap = $controller->getUsersByIds($creatorIds, 'createur');
$responseGroups = $controller->getCandidaturesGroupedByOfferIds($offerIds);
$offerCards = buildBrandOfferCards($offres, $creatorMap, $responseGroups);
$acceptedOfferCards = array_values(array_filter($offerCards, static fn($card) => $card['isAccepted']));
$brandSections = buildBrandOfferSections($offerCards);
$brandDefaultSectionKey = getDefaultBrandSectionKey($brandSections);
$brandOfferMetrics = $brandId ? $controller->getBrandOfferActionMetrics($brandId) : [
    'draftOffers' => 0,
    'expiringSoon' => 0,
];
$brandCandidatureMetrics = $brandId ? $candidatureController->getBrandActionMetrics($brandId) : [
    'responsesToReview' => 0,
    'negotiationsWaitingReply' => 0,
    'acceptedCollaborations' => 0,
    'acceptedBudgetTotal' => 0,
    'recentlyAccepted' => 0,
];
$activeFilterCount = count(array_filter($filters, static fn($value) => $value !== ''));
$paginationBase = $_GET;
unset($paginationBase['page'], $paginationBase['notificationPing']);
$prevPageUrl = $page > 1 ? 'brand_index.php?' . http_build_query($paginationBase + ['page' => $page - 1]) : '';
$nextPageUrl = $hasNextPage ? 'brand_index.php?' . http_build_query($paginationBase + ['page' => $page + 1]) : '';

if (isset($_GET['notificationPing']) && $_GET['notificationPing'] === '1') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'acceptedCount' => count($acceptedOfferCards),
        'acceptedOffers' => array_map(static function ($card) {
            return [
                'idOffre' => (int) $card['offre']->getIdOffre(),
                'titre' => (string) $card['offre']->getTitre(),
                'creatorName' => (string) ($card['creator']['nom'] ?? 'Target creator'),
                'acceptedAt' => (string) ($card['acceptedAt'] ?? ''),
            ];
        }, $acceptedOfferCards),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$acceptedCount = count($acceptedOfferCards);
$acceptedOfferIds = array_map(static fn($card) => (int) $card['offre']->getIdOffre(), $acceptedOfferCards);

$liveCount = 0;
$pendingCount = 0;
$responseCount = 0;
$averageBudget = 0;
$closestDeadline = null;
$declinedOfferCount = count(array_filter($offerCards, static fn($card) => $card['isDeclined']));
$averageBudgetCards = [];

if (!empty($offerCards)) {

    foreach ($offerCards as $card) {
        $offre = $card['offre'];
        if ($offre->isPendingPublication()) {
            $pendingCount++;
        } elseif ($offre->isLivePublication() && !$card['isOutdated']) {
            $liveCount++;

            if (!$card['isDeclined']) {
                $averageBudgetCards[] = $card;
            }
        }

        $responseCount += count($card['responses']);

        $deadline = $offre->getDateLimite();
        if ($offre->isLivePublication() && $deadline && ($closestDeadline === null || $deadline < $closestDeadline)) {
            $closestDeadline = $deadline;
        }
    }
}

if (!empty($averageBudgetCards)) {
    $averageBudget = array_sum(array_map(static fn($card) => (float) $card['offre']->getBudgetPropose(), $averageBudgetCards)) / count($averageBudgetCards);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/../layout/front-theme-bootstrap.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Targeted Offers - Cre8Connect</title>
    <link rel="stylesheet" href="../css/frontoffice.css">
    <link rel="stylesheet" href="offre.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/offre.css')); ?>">
    <link rel="stylesheet" href="../layout/front-header.css">
<link rel="icon" type="image/png" sizes="16x16" href="../../public/images/favicon-16.png">
<link rel="icon" type="image/png" sizes="32x32" href="../../public/images/favicon-32.png">
<link rel="shortcut icon" type="image/png" href="../../public/images/favicon-32.png">
<link rel="apple-touch-icon" sizes="180x180" href="../../public/images/apple-touch-icon.png">
</head>
<body>
    <?php require_once dirname(__DIR__) . '/layout/header.php'; ?>
    <main class="container py-5">
        <div class="offre-page-shell">
            <section class="module-hero">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div>
                        <span class="module-eyebrow" data-i18n="offer.heroEyebrow">Brand workspace</span>
                        <h1 class="display-5 fw-bold mt-3 mb-2 gradient-title" data-i18n="offer.heroTitle">My targeted collaboration offers</h1>
                        <p class="lead text-muted" data-i18n="offer.heroCopy">Track every invitation you sent to creators, monitor response signals, and keep the next collaboration moving.</p>
                    </div>
                    <div class="compact-actions">
                        <a class="btn btn-primary btn-lg" href="brand_create.php" data-i18n="offer.createNewOffer">Create a new offer</a>
                        <a class="btn btn-outline-secondary btn-lg" href="../condidature/brand_index.php" data-i18n="offer.openResponseWorkspace">Open response workspace</a>
                    </div>
                </div>
            </section>

            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div data-brand-index-workspace>
            <section class="stats-grid brand-stats-grid">
                <article class="stat-card">
                    <span class="stat-label" data-i18n="offer.responsesToReview">Responses to review</span>
                    <span class="stat-value"><?php echo (int) ($brandCandidatureMetrics['responsesToReview'] ?? 0); ?></span>
                    <span class="stat-note" data-i18n="offer.sentOrUnderReview">Sent or under review</span>
                </article>
                <article class="stat-card">
                    <span class="stat-label" data-i18n="offer.negotiationsWaitingReply">Negotiations waiting reply</span>
                    <span class="stat-value"><?php echo (int) ($brandCandidatureMetrics['negotiationsWaitingReply'] ?? 0); ?></span>
                    <span class="stat-note" data-i18n="offer.activeNegotiationCandidatures">Active negotiation candidatures</span>
                </article>
                <article class="stat-card">
                    <span class="stat-label" data-i18n="offer.offersExpiringSoon">Offers expiring soon</span>
                    <span class="stat-value"><?php echo (int) ($brandOfferMetrics['expiringSoon'] ?? 0); ?></span>
                    <span class="stat-note" data-i18n="offer.deadlinesNext7">Deadlines within the next 7 days</span>
                </article>
                <article class="stat-card">
                    <span class="stat-label" data-i18n="offer.draftOffers">Draft offers</span>
                    <span class="stat-value"><?php echo (int) ($brandOfferMetrics['draftOffers'] ?? 0); ?></span>
                    <span class="stat-note" data-i18n="offer.notPublishedYet">Not published yet</span>
                </article>
                <article class="stat-card">
                    <span class="stat-label" data-i18n="offer.acceptedCollaborations">Accepted collaborations</span>
                    <span class="stat-value"><?php echo (int) ($brandCandidatureMetrics['acceptedCollaborations'] ?? 0); ?></span>
                    <span class="stat-note"><?php echo htmlspecialchars(formatMoney($brandCandidatureMetrics['acceptedBudgetTotal'] ?? 0)); ?> <span data-i18n="offer.acceptedBudget">accepted budget</span></span>
                </article>
            </section>

            <section class="filter-card">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div>
                        <h2 class="section-title" data-i18n="offer.filterTitle">Filter offers</h2>
                        <p class="section-subtitle" data-i18n="offer.filterSubtitle">Search by offer text, target creator, budget, deadline, or status.</p>
                    </div>
                    <?php if ($activeFilterCount > 0): ?>
                        <span class="offer-chip"><?php echo $activeFilterCount; ?> <span data-i18n="<?php echo $activeFilterCount === 1 ? 'offer.activeFilter' : 'offer.activeFilters'; ?>"><?php echo $activeFilterCount === 1 ? 'active filter' : 'active filters'; ?></span></span>
                    <?php endif; ?>
                </div>
                <form method="get" action="brand_index.php" class="filter-stack mt-4">
                    <div class="filter-grid">
                        <div>
                            <label for="keyword" class="form-label fw-semibold" data-i18n="offer.keyword">Keyword</label>
                            <input type="text" class="form-control" id="keyword" name="keyword" value="<?php echo htmlspecialchars($filters['keyword']); ?>" placeholder="Offer, objective, creator..." data-i18n-placeholder="offer.keywordPlaceholder">
                        </div>
                        <div>
                            <label for="status" class="form-label fw-semibold" data-i18n="offer.status">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="" data-i18n-opt="offer.allStatuses">All statuses</option>
                                <option value="brouillon"<?php echo $filters['status'] === 'brouillon' ? ' selected' : ''; ?> data-i18n-opt="offer.status.draft">Draft</option>
                                <option value="publiee"<?php echo $filters['status'] === 'publiee' ? ' selected' : ''; ?> data-i18n-opt="offer.status.published">Published</option>
                                <option value="pending"<?php echo $filters['status'] === 'pending' ? ' selected' : ''; ?> data-i18n-opt="offer.status.pendingLaunch">Pending launch</option>
                                <option value="cloturee"<?php echo $filters['status'] === 'cloturee' ? ' selected' : ''; ?> data-i18n-opt="offer.status.closed">Closed</option>
                                <option value="expiree"<?php echo $filters['status'] === 'expiree' ? ' selected' : ''; ?> data-i18n-opt="offer.status.expired">Expired</option>
                                <option value="archivee"<?php echo $filters['status'] === 'archivee' ? ' selected' : ''; ?> data-i18n-opt="offer.status.archived">Archived</option>
                            </select>
                        </div>
                        <div>
                            <label for="budgetFrom" class="form-label fw-semibold" data-i18n="offer.budgetMin">Budget min</label>
                            <input type="number" step="0.01" class="form-control" id="budgetFrom" name="budgetFrom" value="<?php echo htmlspecialchars($filters['budgetFrom']); ?>">
                        </div>
                        <div>
                            <label for="budgetTo" class="form-label fw-semibold" data-i18n="offer.budgetMax">Budget max</label>
                            <input type="number" step="0.01" class="form-control" id="budgetTo" name="budgetTo" value="<?php echo htmlspecialchars($filters['budgetTo']); ?>">
                        </div>
                        <div>
                            <label for="deadlineFrom" class="form-label fw-semibold" data-i18n="offer.deadlineFrom">Deadline from</label>
                            <input type="date" class="form-control" id="deadlineFrom" name="deadlineFrom" value="<?php echo htmlspecialchars($filters['deadlineFrom']); ?>">
                        </div>
                        <div>
                            <label for="deadlineTo" class="form-label fw-semibold" data-i18n="offer.deadlineTo">Deadline to</label>
                            <input type="date" class="form-control" id="deadlineTo" name="deadlineTo" value="<?php echo htmlspecialchars($filters['deadlineTo']); ?>">
                        </div>
                        <div>
                            <label for="sort" class="form-label fw-semibold" data-i18n="offer.sort">Sort</label>
                            <select class="form-select" id="sort" name="sort">
                                <option value=""<?php echo $filters['sort'] === '' ? ' selected' : ''; ?> data-i18n-opt="offer.recommended">Recommended</option>
                                <option value="oldest"<?php echo $filters['sort'] === 'oldest' ? ' selected' : ''; ?> data-i18n-opt="offer.oldest">Oldest</option>
                                <option value="deadline_soon"<?php echo $filters['sort'] === 'deadline_soon' ? ' selected' : ''; ?> data-i18n-opt="offer.deadlineSoon">Deadline soon</option>
                                <option value="budget_high"<?php echo $filters['sort'] === 'budget_high' ? ' selected' : ''; ?> data-i18n-opt="offer.budgetHighLow">Budget high to low</option>
                                <option value="budget_low"<?php echo $filters['sort'] === 'budget_low' ? ' selected' : ''; ?> data-i18n-opt="offer.budgetLowHigh">Budget low to high</option>
                                <option value="status"<?php echo $filters['sort'] === 'status' ? ' selected' : ''; ?> data-i18n-opt="offer.status">Status</option>
                            </select>
                        </div>
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary" data-i18n="offer.applyFilters">Apply filters</button>
                        <a class="btn btn-outline-secondary" href="brand_index.php" data-i18n="offer.reset">Reset</a>
                    </div>
                </form>
            </section>

            <?php if (!empty($offerCards)): ?>
                <section class="offer-tab-shell" data-offer-tab-shell data-default-tab="<?php echo htmlspecialchars($brandDefaultSectionKey); ?>">
                    <div class="offer-tab-list" role="tablist" aria-label="Brand offer pipeline tabs">
                        <?php foreach ($brandSections as $section): ?>
                            <?php $isActiveTab = $section['key'] === $brandDefaultSectionKey; ?>
                            <button
                                type="button"
                                class="offer-tab-button<?php echo $isActiveTab ? ' is-active' : ''; ?>"
                                id="brand-tab-<?php echo htmlspecialchars($section['key']); ?>"
                                role="tab"
                                aria-selected="<?php echo $isActiveTab ? 'true' : 'false'; ?>"
                                aria-controls="brand-panel-<?php echo htmlspecialchars($section['key']); ?>"
                                data-offer-tab="<?php echo htmlspecialchars($section['key']); ?>"
                            >
                                <span class="offer-tab-label" data-i18n="<?php echo htmlspecialchars($section['titleKey']); ?>"><?php echo htmlspecialchars($section['title']); ?></span>
                                <span class="offer-tab-badge" data-brand-tab-count="<?php echo htmlspecialchars($section['key']); ?>"><?php echo count($section['cards']); ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <div class="offer-tab-panels">
                        <?php foreach ($brandSections as $section): ?>
                            <?php $isActivePanel = $section['key'] === $brandDefaultSectionKey; ?>
                            <section
                                class="section-card offer-section-card <?php echo htmlspecialchars($section['themeClass']); ?> offer-tab-panel"
                                id="brand-panel-<?php echo htmlspecialchars($section['key']); ?>"
                                role="tabpanel"
                                aria-labelledby="brand-tab-<?php echo htmlspecialchars($section['key']); ?>"
                                data-offer-tab-panel="<?php echo htmlspecialchars($section['key']); ?>"
                                data-brand-section-panel="<?php echo htmlspecialchars($section['key']); ?>"
                                data-empty-title="<?php echo htmlspecialchars($section['title'], ENT_QUOTES); ?>"
                                data-empty-title-key="<?php echo htmlspecialchars($section['titleKey'], ENT_QUOTES); ?>"
                                data-empty-message="<?php echo htmlspecialchars($section['empty'], ENT_QUOTES); ?>"
                                data-empty-message-key="<?php echo htmlspecialchars($section['emptyKey'], ENT_QUOTES); ?>"
                                <?php echo $isActivePanel ? '' : 'hidden'; ?>
                            >
                                <div class="offer-section-header">
                                    <div class="offer-section-copy">
                                        <h2 class="section-title" data-i18n="<?php echo htmlspecialchars($section['titleKey']); ?>"><?php echo htmlspecialchars($section['title']); ?></h2>
                                        <p class="section-subtitle" data-i18n="<?php echo htmlspecialchars($section['subtitleKey']); ?>"><?php echo htmlspecialchars($section['subtitle']); ?></p>
                                    </div>
                                    <?php $sectionCardCount = count($section['cards']); ?>
                                    <span class="offer-section-count" data-brand-section-count="<?php echo htmlspecialchars($section['key']); ?>">
                                        <?php echo $sectionCardCount; ?> <span data-i18n="<?php echo $sectionCardCount === 1 ? 'offer.offerSingular' : 'offer.offerPlural'; ?>"><?php echo $sectionCardCount === 1 ? 'offer' : 'offers'; ?></span>
                                    </span>
                                </div>

                                <div
                                    class="offer-grid"
                                    id="<?php echo htmlspecialchars($section['domId']); ?>"
                                    data-brand-section="<?php echo htmlspecialchars($section['key']); ?>"
                                >
                                    <?php foreach ($section['cards'] as $card): ?>
                                        <?php
                                        $offre = $card['offre'];
                                        $creator = $card['creator'];
                                        $responses = $card['responses'];
                                        $targetedResponse = $card['targetedResponse'];
                                        $isAccepted = $card['isAccepted'];
                                        $isDeclined = $card['isDeclined'];
                                        $isOutdated = $card['isOutdated'];
                                        $displayStatus = $offre->getDisplayStatusKey();
                                        $publicationLabel = $offre->isPendingPublication() ? 'Goes live' : 'Published';
                                        $publicationLabelKey = $offre->isPendingPublication() ? 'offer.goesLive' : 'offer.published';
                                        $statusLabelKey = $isOutdated ? 'offer.status.outdated' : offerStatusI18nKey($displayStatus);
                                        $responseStatusKey = $targetedResponse ? responseStatusI18nKey($targetedResponse) : '';
                                        $cre8PilotSignalSummary = 'Waiting for creator reply';
                                        if ($targetedResponse) {
                                            if ($isDeclined) {
                                                $cre8PilotSignalSummary = 'Declined by creator';
                                            } elseif (($targetedResponse['statutCandidature'] ?? '') === 'brouillon') {
                                                $cre8PilotSignalSummary = 'Creator draft response not submitted yet';
                                            } elseif (in_array((string) ($targetedResponse['statutCandidature'] ?? ''), ['negociation', 'en_etude'], true)) {
                                                $cre8PilotSignalSummary = 'Negotiation activity — creator budget reply EUR ' . number_format((float) ($targetedResponse['budgetPropose'] ?? 0), 2, '.', ',');
                                            } else {
                                                $cre8PilotSignalSummary = responseStatusLabel($targetedResponse) . ' — budget reply EUR ' . number_format((float) ($targetedResponse['budgetPropose'] ?? 0), 2, '.', ',');
                                            }
                                        } elseif ($offre->isPendingPublication()) {
                                            $cre8PilotSignalSummary = 'Scheduled / pending publication';
                                        } elseif ($isOutdated) {
                                            $cre8PilotSignalSummary = 'Outdated — deadline passed';
                                        }
                                        ?>
                                        <article
                                            class="offer-card<?php echo $isAccepted ? ' is-accepted' : ($isDeclined ? ' is-declined' : ($isOutdated ? ' is-outdated' : '')); ?>"
                                            data-offer-id="<?php echo (int) $offre->getIdOffre(); ?>"
                                            data-offer-title="<?php echo htmlspecialchars($offre->getTitre(), ENT_QUOTES); ?>"
                                            data-creator-name="<?php echo htmlspecialchars($creator['nom'] ?? 'No creator selected yet', ENT_QUOTES); ?>"
                                            data-brand-section-key="<?php echo htmlspecialchars($section['key']); ?>"
                                            data-card-href="brand_details.php?idOffre=<?php echo (int) $offre->getIdOffre(); ?>"
                                            data-cre8pilot-budget="<?php echo htmlspecialchars(formatMoney($offre->getBudgetPropose()), ENT_QUOTES); ?>"
                                            data-cre8pilot-deadline="<?php echo htmlspecialchars((string) $offre->getDateLimite(), ENT_QUOTES); ?>"
                                            data-cre8pilot-published="<?php echo htmlspecialchars((string) $offre->getDatePublication(), ENT_QUOTES); ?>"
                                            data-cre8pilot-published-date="<?php echo htmlspecialchars((string) $offre->getDatePublication(), ENT_QUOTES); ?>"
                                            data-cre8pilot-status="<?php echo htmlspecialchars($isOutdated ? 'Outdated' : translateOfferStatus($displayStatus), ENT_QUOTES); ?>"
                                            data-cre8pilot-response-count="<?php echo count($responses); ?>"
                                            data-cre8pilot-objective="<?php echo htmlspecialchars(excerptText($offre->getObjectif(), 260), ENT_QUOTES); ?>"
                                            data-cre8pilot-signal="<?php echo htmlspecialchars($cre8PilotSignalSummary, ENT_QUOTES); ?>"
                                        >
                                            <div class="offer-card-head">
                                                <div>
                                                    <div class="offer-flag-row">
                                                        <span class="offer-status <?php echo htmlspecialchars($isOutdated ? 'status-outdated' : offerStatusClass($displayStatus)); ?>"<?php echo $statusLabelKey !== '' ? ' data-i18n="' . htmlspecialchars($statusLabelKey) . '"' : ''; ?>>
                                                            <?php echo htmlspecialchars($isOutdated ? 'Outdated' : translateOfferStatus($displayStatus)); ?>
                                                        </span>
                                                        <?php if ($isAccepted): ?>
                                                            <span class="priority-badge priority-badge-success js-accepted-flag" data-i18n="offer.acceptedByCreator">Accepted by creator</span>
                                                        <?php elseif ($isDeclined): ?>
                                                            <span class="priority-badge priority-badge-danger" data-i18n="offer.declinedByCreator">Declined by creator</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <h2 class="offer-card-title"><?php echo htmlspecialchars($offre->getTitre()); ?></h2>
                                                    <p class="offer-summary mt-2"><?php echo htmlspecialchars(excerptText($offre->getDescription(), 165)); ?></p>
                                                </div>
                                            </div>

                                            <div class="offer-meta">
                                                <span class="offer-chip"><?php echo htmlspecialchars(formatMoney($offre->getBudgetPropose())); ?></span>
                                                <span class="offer-chip"><span data-i18n="<?php echo htmlspecialchars($publicationLabelKey); ?>"><?php echo htmlspecialchars($publicationLabel); ?></span>: <?php echo htmlspecialchars($offre->getDatePublication()); ?></span>
                                                <span class="offer-chip"><span data-i18n="offer.deadline">Deadline</span>: <?php echo htmlspecialchars($offre->getDateLimite()); ?></span>
                                                <?php $responseTotal = count($responses); ?>
                                                <span class="offer-chip"><?php echo $responseTotal; ?> <span data-i18n="<?php echo $responseTotal === 1 ? 'offer.responseSingular' : 'offer.responsePlural'; ?>"><?php echo $responseTotal === 1 ? 'response' : 'responses'; ?></span></span>
                                            </div>

                                            <div class="offer-detail-list">
                                                <div class="offer-detail-item">
                                                    <strong data-i18n="offer.targetCreator">Target creator</strong>
                                                    <div style="display:flex;align-items:center;gap:.65rem;">
                                                        <?php echo cre8_render_avatar($creator['id'] ?? $offre->getIdCreateurCible(), (string) ($creator['nom'] ?? 'Creator'), 'cre8-avatar-md'); ?>
                                                        <div>
                                                            <span><?php echo htmlspecialchars($creator['nom'] ?? 'No creator selected yet'); ?></span>
                                                            <p><?php echo htmlspecialchars($creator['email'] ?? ''); ?></p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="offer-detail-item">
                                                    <strong data-i18n="offer.objective">Objective</strong>
                                                    <span><?php echo htmlspecialchars($offre->getObjectif()); ?></span>
                                                </div>
                                            </div>

                                            <?php if ($targetedResponse): ?>
                                                <div class="response-callout<?php echo $isAccepted ? ' response-callout-accepted' : ($isDeclined ? ' response-callout-declined' : ''); ?>">
                                                    <strong data-i18n="offer.latestCreatorSignal">Latest creator signal</strong>
                                                    <div class="mt-2 d-flex flex-wrap gap-2 align-items-center">
                                                        <span class="response-status <?php echo htmlspecialchars(responseStatusClass($targetedResponse['statutCandidature'])); ?>"<?php echo $responseStatusKey !== '' ? ' data-i18n="' . htmlspecialchars($responseStatusKey) . '"' : ''; ?>>
                                                            <?php echo htmlspecialchars(responseStatusLabel($targetedResponse)); ?>
                                                        </span>
                                                        <span class="text-muted small">
                                                            <?php if ($isDeclined): ?>
                                                                <span data-i18n="offer.declinedPipelineCopy">This offer stays in the pipeline, but moves to the bottom.</span>
                                                            <?php elseif (($targetedResponse['statutCandidature'] ?? '') === 'brouillon'): ?>
                                                                <span data-i18n="offer.creatorDraftCopy">The creator saved a draft response but has not submitted it yet.</span>
                                                            <?php else: ?>
                                                                <span data-i18n="offer.budgetReply">Budget reply</span>: EUR <?php echo htmlspecialchars($targetedResponse['budgetPropose']); ?>
                                                            <?php endif; ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            <?php elseif ($offre->isPendingPublication()): ?>
                                                <div class="response-callout">
                                                    <strong data-i18n="offer.scheduledLaunch">Scheduled launch</strong>
                                                    <div class="mt-2 text-muted small"><span data-i18n="offer.scheduledLaunchCopy">This offer will appear to the creator on</span> <?php echo htmlspecialchars($offre->getDatePublication()); ?>.</div>
                                                </div>
                                            <?php elseif ($isOutdated): ?>
                                                <div class="response-callout">
                                                    <strong data-i18n="offer.deadlinePassed">Deadline passed</strong>
                                                    <div class="mt-2 text-muted small" data-i18n="offer.deadlinePassedCopy">This offer stays available for history, but the response window has ended.</div>
                                                </div>
                                            <?php else: ?>
                                                <div class="response-callout">
                                                    <strong data-i18n="offer.waitingCreatorReply">Waiting for creator reply</strong>
                                                    <div class="mt-2 text-muted small" data-i18n="offer.noCreatorReply">No reply from the creator yet.</div>
                                                </div>
                                            <?php endif; ?>

                                        <div class="compact-actions">
                                            <?php if ($isAccepted): ?>
                                                <span class="btn btn-outline-secondary disabled" aria-disabled="true" data-i18n="offer.editingLocked">Editing locked</span>
                                            <?php else: ?>
                                                <a class="btn btn-outline-secondary" href="brand_edit.php?idOffre=<?php echo (int) $offre->getIdOffre(); ?>" data-i18n="offer.editOffer">Edit offer</a>
                                            <?php endif; ?>
                                            <form
                                                method="post"
                                                action="brand_delete.php"
                                                    data-delete-confirm
                                                    data-delete-title="<?php echo htmlspecialchars($offre->getTitre(), ENT_QUOTES); ?>"
                                                    data-delete-creator="<?php echo htmlspecialchars($creator['nom'] ?? 'No creator selected yet', ENT_QUOTES); ?>"
                                                >
                                                    <input type="hidden" name="idOffre" value="<?php echo (int) $offre->getIdOffre(); ?>">
                                                    <button type="submit" class="btn btn-outline-danger" data-i18n="offer.delete">Delete</button>
                                                </form>
                                            </div>
                                        </article>
                                    <?php endforeach; ?>
                                </div>

                                <?php if (empty($section['cards'])): ?>
                                    <div class="note-block offer-section-empty" data-brand-section-empty="<?php echo htmlspecialchars($section['key']); ?>">
                                        <strong data-i18n="<?php echo htmlspecialchars($section['titleKey']); ?>"><?php echo htmlspecialchars($section['title']); ?></strong>
                                        <p data-i18n="<?php echo htmlspecialchars($section['emptyKey']); ?>"><?php echo htmlspecialchars($section['empty']); ?></p>
                                    </div>
                                <?php endif; ?>
                            </section>
                        <?php endforeach; ?>
                    </div>
                </section>
                <nav class="front-pagination" aria-label="Brand offer pages">
                    <span>
                        <span data-i18n="offer.page">Page</span> <?php echo $page; ?>
                        <span aria-hidden="true"> &middot; </span>
                        <span data-i18n="offer.showingUpTo">Showing up to</span> <?php echo $perPage; ?>
                        <span data-i18n="offer.filteredOffers">filtered offers</span>
                    </span>
                    <div>
                        <?php if ($prevPageUrl !== ''): ?>
                            <a class="btn btn-outline-secondary" href="<?php echo htmlspecialchars($prevPageUrl); ?>" data-i18n="offer.previous">Previous</a>
                        <?php endif; ?>
                        <?php if ($nextPageUrl !== ''): ?>
                            <a class="btn btn-primary" href="<?php echo htmlspecialchars($nextPageUrl); ?>" data-i18n="offer.loadMore">Load more</a>
                        <?php endif; ?>
                    </div>
                </nav>
            <?php else: ?>
                <section class="empty-state-card">
                    <div class="empty-state-icon">+</div>
                    <h2 class="section-title" data-i18n="offer.emptyTitle">No targeted offers yet</h2>
                    <p class="section-subtitle" data-i18n="offer.emptyCopy">Start your collaboration pipeline by selecting a creator and sending a focused invitation instead of a generic brief.</p>
                    <div class="compact-actions justify-content-center mt-4">
                        <a class="btn btn-primary btn-lg" href="brand_create.php" data-i18n="offer.createFirstOffer">Create your first targeted offer</a>
                    </div>
                </section>
            <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
    <script src="offre-delete-confirm.js?v=<?php echo urlencode((string) filemtime(__DIR__ . '/offre-delete-confirm.js')); ?>"></script>
    <script src="offre-tabs.js?v=<?php echo urlencode((string) filemtime(__DIR__ . '/offre-tabs.js')); ?>"></script>
    <script src="brand_index_filters.js?v=<?php echo urlencode((string) filemtime(__DIR__ . '/brand_index_filters.js')); ?>"></script>
    <script>
        (() => {
            const translations = {
                en: {
                    'offer.heroEyebrow': 'Brand workspace',
                    'offer.heroTitle': 'My targeted collaboration offers',
                    'offer.heroCopy': 'Track every invitation you sent to creators, monitor response signals, and keep the next collaboration moving.',
                    'offer.createNewOffer': 'Create a new offer',
                    'offer.openResponseWorkspace': 'Open response workspace',
                    'offer.activeFilter': 'active filter',
                    'offer.activeFilters': 'active filters',
                    'offer.offerSingular': 'offer',
                    'offer.offerPlural': 'offers',
                    'offer.responseSingular': 'response',
                    'offer.responsePlural': 'responses',
                    'offer.responsesToReview': 'Responses to review',
                    'offer.sentOrUnderReview': 'Sent or under review',
                    'offer.negotiationsWaitingReply': 'Negotiations waiting reply',
                    'offer.activeNegotiationCandidatures': 'Active negotiation candidatures',
                    'offer.offersExpiringSoon': 'Offers expiring soon',
                    'offer.deadlinesNext7': 'Deadlines within the next 7 days',
                    'offer.draftOffers': 'Draft offers',
                    'offer.notPublishedYet': 'Not published yet',
                    'offer.acceptedCollaborations': 'Accepted collaborations',
                    'offer.acceptedBudget': 'accepted budget',
                    'offer.filterTitle': 'Filter offers',
                    'offer.filterSubtitle': 'Search by offer text, target creator, budget, deadline, or status.',
                    'offer.keyword': 'Keyword',
                    'offer.keywordPlaceholder': 'Offer, objective, creator...',
                    'offer.status': 'Status',
                    'offer.allStatuses': 'All statuses',
                    'offer.budgetMin': 'Budget min',
                    'offer.budgetMax': 'Budget max',
                    'offer.deadlineFrom': 'Deadline from',
                    'offer.deadlineTo': 'Deadline to',
                    'offer.sort': 'Sort',
                    'offer.recommended': 'Recommended',
                    'offer.oldest': 'Oldest',
                    'offer.deadlineSoon': 'Deadline soon',
                    'offer.budgetHighLow': 'Budget high to low',
                    'offer.budgetLowHigh': 'Budget low to high',
                    'offer.applyFilters': 'Apply filters',
                    'offer.reset': 'Reset',
                    'offer.page': 'Page',
                    'offer.showingUpTo': 'Showing up to',
                    'offer.filteredOffers': 'filtered offers',
                    'offer.previous': 'Previous',
                    'offer.loadMore': 'Load more',
                    'offer.goesLive': 'Goes live',
                    'offer.published': 'Published',
                    'offer.deadline': 'Deadline',
                    'offer.targetCreator': 'Target creator',
                    'offer.objective': 'Objective',
                    'offer.latestCreatorSignal': 'Latest creator signal',
                    'offer.acceptedByCreator': 'Accepted by creator',
                    'offer.declinedByCreator': 'Declined by creator',
                    'offer.declinedPipelineCopy': 'This offer stays in the pipeline, but moves to the bottom.',
                    'offer.creatorDraftCopy': 'The creator saved a draft response but has not submitted it yet.',
                    'offer.budgetReply': 'Budget reply',
                    'offer.scheduledLaunch': 'Scheduled launch',
                    'offer.scheduledLaunchCopy': 'This offer will appear to the creator on',
                    'offer.deadlinePassed': 'Deadline passed',
                    'offer.deadlinePassedCopy': 'This offer stays available for history, but the response window has ended.',
                    'offer.waitingCreatorReply': 'Waiting for creator reply',
                    'offer.noCreatorReply': 'No reply from the creator yet.',
                    'offer.creatorAccepted': 'Creator accepted',
                    'offer.openDetailsFullResponse': 'Open details to review the full creator response.',
                    'offer.editingLocked': 'Editing locked',
                    'offer.editOffer': 'Edit offer',
                    'offer.delete': 'Delete',
                    'offer.emptyTitle': 'No targeted offers yet',
                    'offer.emptyCopy': 'Start your collaboration pipeline by selecting a creator and sending a focused invitation instead of a generic brief.',
                    'offer.createFirstOffer': 'Create your first targeted offer',
                    'offer.newAcceptedOffer': 'new accepted offer',
                    'offer.newAcceptedOffers': 'new accepted offers',
                    'offer.section.published': 'Published offers',
                    'offer.section.publishedCopy': 'Offers that are already visible to creators.',
                    'offer.section.noPublished': 'No live published offers right now.',
                    'offer.section.accepted': 'Accepted offers',
                    'offer.section.acceptedCopy': 'Offers already accepted by the targeted creator.',
                    'offer.section.noAccepted': 'No creator has accepted an offer yet.',
                    'offer.section.draftPending': 'Draft / pending offers',
                    'offer.section.draftPendingCopy': 'Offers still in draft or waiting for their publication date.',
                    'offer.section.noDraftPending': 'No draft or scheduled offers at the moment.',
                    'offer.section.declined': 'Declined offers',
                    'offer.section.declinedCopy': 'Offers kept in the history after the creator declined them.',
                    'offer.section.noDeclined': 'No declined offers in your pipeline.',
                    'offer.section.outdated': 'Outdated offers',
                    'offer.section.outdatedCopy': 'Offers whose deadline has already passed without a final creator acceptance or decline.',
                    'offer.section.noOutdated': 'No outdated offers in your pipeline.',
                    'offer.status.draft': 'Draft',
                    'offer.status.pendingLaunch': 'Pending launch',
                    'offer.status.published': 'Published',
                    'offer.status.closed': 'Closed',
                    'offer.status.expired': 'Expired',
                    'offer.status.archived': 'Archived',
                    'offer.status.active': 'Active',
                    'offer.status.outdated': 'Outdated',
                    'offer.responseStatus.draft': 'Pending',
                    'offer.responseStatus.waitingDecision': 'Waiting decision',
                    'offer.responseStatus.underReview': 'Under review',
                    'offer.responseStatus.negotiation': 'Under review',
                    'offer.responseStatus.accepted': 'Accepted',
                    'offer.responseStatus.refused': 'Closed',
                    'offer.responseStatus.withdrawn': 'Closed'
                },
                fr: {
                    'offer.heroEyebrow': 'Espace marque',
                    'offer.heroTitle': 'Mes offres de collaboration ciblees',
                    'offer.heroCopy': 'Suivez chaque invitation envoyee aux createurs, surveillez les signaux de reponse et faites avancer la prochaine collaboration.',
                    'offer.createNewOffer': 'Creer une nouvelle offre',
                    'offer.openResponseWorkspace': 'Ouvrir l espace reponses',
                    'offer.activeFilter': 'filtre actif',
                    'offer.activeFilters': 'filtres actifs',
                    'offer.offerSingular': 'offre',
                    'offer.offerPlural': 'offres',
                    'offer.responseSingular': 'reponse',
                    'offer.responsePlural': 'reponses',
                    'offer.responsesToReview': 'Reponses a examiner',
                    'offer.sentOrUnderReview': 'Envoyees ou en cours d examen',
                    'offer.negotiationsWaitingReply': 'Negociations en attente de reponse',
                    'offer.activeNegotiationCandidatures': 'Candidatures en negociation active',
                    'offer.offersExpiringSoon': 'Offres bientot expirees',
                    'offer.deadlinesNext7': 'Echeances dans les 7 prochains jours',
                    'offer.draftOffers': 'Offres brouillon',
                    'offer.notPublishedYet': 'Pas encore publiees',
                    'offer.acceptedCollaborations': 'Collaborations acceptees',
                    'offer.acceptedBudget': 'budget accepte',
                    'offer.filterTitle': 'Filtrer les offres',
                    'offer.filterSubtitle': 'Recherchez par texte d offre, createur cible, budget, echeance ou statut.',
                    'offer.keyword': 'Mot-cle',
                    'offer.keywordPlaceholder': 'Offre, objectif, createur...',
                    'offer.status': 'Statut',
                    'offer.allStatuses': 'Tous les statuts',
                    'offer.budgetMin': 'Budget minimum',
                    'offer.budgetMax': 'Budget maximum',
                    'offer.deadlineFrom': 'Echeance du',
                    'offer.deadlineTo': 'Echeance au',
                    'offer.sort': 'Tri',
                    'offer.recommended': 'Recommande',
                    'offer.oldest': 'Plus anciennes',
                    'offer.deadlineSoon': 'Echeance proche',
                    'offer.budgetHighLow': 'Budget decroissant',
                    'offer.budgetLowHigh': 'Budget croissant',
                    'offer.applyFilters': 'Appliquer les filtres',
                    'offer.reset': 'Reinitialiser',
                    'offer.page': 'Page',
                    'offer.showingUpTo': 'Affichage jusqu a',
                    'offer.filteredOffers': 'offres filtrees',
                    'offer.previous': 'Precedent',
                    'offer.loadMore': 'Charger plus',
                    'offer.goesLive': 'Mise en ligne',
                    'offer.published': 'Publiee',
                    'offer.deadline': 'Echeance',
                    'offer.targetCreator': 'Createur cible',
                    'offer.objective': 'Objectif',
                    'offer.latestCreatorSignal': 'Dernier signal createur',
                    'offer.acceptedByCreator': 'Acceptee par le createur',
                    'offer.declinedByCreator': 'Refusee par le createur',
                    'offer.declinedPipelineCopy': 'Cette offre reste dans le pipeline, mais passe en bas.',
                    'offer.creatorDraftCopy': 'Le createur a enregistre une reponse brouillon sans encore l envoyer.',
                    'offer.budgetReply': 'Reponse budget',
                    'offer.scheduledLaunch': 'Publication planifiee',
                    'offer.scheduledLaunchCopy': 'Cette offre apparaitra au createur le',
                    'offer.deadlinePassed': 'Echeance depassee',
                    'offer.deadlinePassedCopy': 'Cette offre reste disponible dans l historique, mais la periode de reponse est terminee.',
                    'offer.waitingCreatorReply': 'En attente de la reponse du createur',
                    'offer.noCreatorReply': 'Aucune reponse du createur pour le moment.',
                    'offer.creatorAccepted': 'Createur accepte',
                    'offer.openDetailsFullResponse': 'Ouvrez les details pour examiner la reponse complete du createur.',
                    'offer.editingLocked': 'Modification verrouillee',
                    'offer.editOffer': 'Modifier l offre',
                    'offer.delete': 'Supprimer',
                    'offer.emptyTitle': 'Aucune offre ciblee pour le moment',
                    'offer.emptyCopy': 'Lancez votre pipeline de collaboration en selectionnant un createur et en envoyant une invitation precise plutot qu un brief generique.',
                    'offer.createFirstOffer': 'Creer votre premiere offre ciblee',
                    'offer.newAcceptedOffer': 'nouvelle offre acceptee',
                    'offer.newAcceptedOffers': 'nouvelles offres acceptees',
                    'offer.section.published': 'Offres publiees',
                    'offer.section.publishedCopy': 'Offres deja visibles par les createurs.',
                    'offer.section.noPublished': 'Aucune offre publiee en ligne pour le moment.',
                    'offer.section.accepted': 'Offres acceptees',
                    'offer.section.acceptedCopy': 'Offres deja acceptees par le createur cible.',
                    'offer.section.noAccepted': 'Aucun createur n a encore accepte d offre.',
                    'offer.section.draftPending': 'Offres brouillon / en attente',
                    'offer.section.draftPendingCopy': 'Offres encore en brouillon ou en attente de leur date de publication.',
                    'offer.section.noDraftPending': 'Aucune offre brouillon ou planifiee pour le moment.',
                    'offer.section.declined': 'Offres refusees',
                    'offer.section.declinedCopy': 'Offres conservees dans l historique apres refus du createur.',
                    'offer.section.noDeclined': 'Aucune offre refusee dans votre pipeline.',
                    'offer.section.outdated': 'Offres expirees',
                    'offer.section.outdatedCopy': 'Offres dont l echeance est deja passee sans acceptation ou refus final du createur.',
                    'offer.section.noOutdated': 'Aucune offre expiree dans votre pipeline.',
                    'offer.status.draft': 'Brouillon',
                    'offer.status.pendingLaunch': 'Publication planifiee',
                    'offer.status.published': 'Publiee',
                    'offer.status.closed': 'Fermee',
                    'offer.status.expired': 'Expiree',
                    'offer.status.archived': 'Archivee',
                    'offer.status.active': 'Active',
                    'offer.status.outdated': 'Expiree',
                    'offer.responseStatus.draft': 'En attente',
                    'offer.responseStatus.waitingDecision': 'En attente de decision',
                    'offer.responseStatus.underReview': 'En cours d examen',
                    'offer.responseStatus.negotiation': 'En cours d examen',
                    'offer.responseStatus.accepted': 'Acceptee',
                    'offer.responseStatus.refused': 'Fermee',
                    'offer.responseStatus.withdrawn': 'Fermee'
                }
            };
            window.cre8BrandOfferTranslations = translations;

            function registerTranslations() {
                if (typeof window.cre8RegisterTranslations === 'function') {
                    window.cre8RegisterTranslations(translations);
                    return;
                }
                window.cre8TranslationQueue = window.cre8TranslationQueue || [];
                window.cre8TranslationQueue.push(translations);
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', registerTranslations);
            } else {
                registerTranslations();
            }
        })();
    </script>
    <script>
        (() => {
            const banner = document.getElementById('acceptedOfferBanner');
            const title = document.getElementById('acceptedBannerTitle');
            const text = document.getElementById('acceptedBannerText');
            const list = document.getElementById('acceptedBannerList');
            const stack = document.getElementById('liveNotificationStack');

            if (!banner || !stack || !window.fetch) {
                return;
            }

            let knownAcceptedIds = new Set((banner.dataset.acceptedIds || '').split(',').filter(Boolean));

            function currentLang() {
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

            function translate(key, fallback) {
                const dict = window.cre8BrandOfferTranslations || {};
                const lang = currentLang();
                if (dict[lang] && dict[lang][key] !== undefined) {
                    return dict[lang][key];
                }
                if (dict.en && dict.en[key] !== undefined) {
                    return dict.en[key];
                }
                return fallback;
            }

            function offerCountLabel(total) {
                return translate(total === 1 ? 'offer.offerSingular' : 'offer.offerPlural', total === 1 ? 'offer' : 'offers');
            }

            function escapeHtml(value) {
                return String(value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function updateBanner(data) {
                const offers = Array.isArray(data.acceptedOffers) ? data.acceptedOffers : [];
                banner.dataset.acceptedIds = offers.map((offer) => offer.idOffre).join(',');

                if (!offers.length) {
                    banner.classList.remove('is-hidden');
                    list.innerHTML = '';
                    return;
                }

                banner.classList.remove('is-hidden');
                title.textContent = `${offers.length} offer${offers.length === 1 ? '' : 's'} accepted by creators`;
                text.textContent = 'Review accepted offers in the dedicated Accepted tab.';
                list.innerHTML = offers.slice(0, 3).map((offer) => (
                    `<span class="notification-chip">${escapeHtml(offer.titre)} - ${escapeHtml(offer.creatorName)}</span>`
                )).join('');
            }

            function syncSectionEmptyState(key) {
                const panel = document.querySelector(`[data-brand-section-panel="${key}"]`);
                const grid = document.querySelector(`[data-brand-section="${key}"]`);

                if (!panel || !grid) {
                    return;
                }

                const total = grid.querySelectorAll('.offer-card').length;
                let emptyState = panel.querySelector(`[data-brand-section-empty="${key}"]`);

                if (total === 0) {
                    if (!emptyState) {
                        emptyState = document.createElement('div');
                        emptyState.className = 'note-block offer-section-empty';
                        emptyState.dataset.brandSectionEmpty = key;
                        const titleKey = panel.dataset.emptyTitleKey || '';
                        const messageKey = panel.dataset.emptyMessageKey || '';
                        emptyState.innerHTML = `
                            <strong${titleKey ? ` data-i18n="${escapeHtml(titleKey)}"` : ''}>${escapeHtml(translate(titleKey, panel.dataset.emptyTitle || ''))}</strong>
                            <p${messageKey ? ` data-i18n="${escapeHtml(messageKey)}"` : ''}>${escapeHtml(translate(messageKey, panel.dataset.emptyMessage || ''))}</p>
                        `;
                        panel.appendChild(emptyState);
                    }
                } else if (emptyState) {
                    emptyState.remove();
                }
            }

            function updateSectionCounts() {
                document.querySelectorAll('[data-brand-section-count]').forEach((node) => {
                    const key = node.getAttribute('data-brand-section-count');
                    const grid = document.querySelector(`[data-brand-section="${key}"]`);
                    const total = grid ? grid.querySelectorAll('.offer-card').length : 0;
                    node.textContent = `${total} ${offerCountLabel(total)}`;
                });

                document.querySelectorAll('[data-brand-tab-count]').forEach((node) => {
                    const key = node.getAttribute('data-brand-tab-count');
                    const grid = document.querySelector(`[data-brand-section="${key}"]`);
                    const total = grid ? grid.querySelectorAll('.offer-card').length : 0;
                    node.textContent = String(total);
                    syncSectionEmptyState(key);
                });
            }

            function elevateAcceptedCard(offer) {
                const acceptedGrid = document.getElementById('brandAcceptedGrid');
                if (!acceptedGrid) {
                    return;
                }

                const card = document.querySelector(`[data-offer-id="${offer.idOffre}"]`);
                if (!card) {
                    return;
                }

                card.classList.add('is-accepted');
                card.classList.remove('is-declined');
                card.dataset.brandSectionKey = 'accepted';

                let flag = card.querySelector('.js-accepted-flag');
                if (!flag) {
                    const row = card.querySelector('.offer-flag-row');
                    if (row) {
                        flag = document.createElement('span');
                        flag.className = 'priority-badge priority-badge-success js-accepted-flag';
                        flag.setAttribute('data-i18n', 'offer.acceptedByCreator');
                        flag.textContent = 'Accepted by creator';
                        row.appendChild(flag);
                    }
                }

                const responseCallout = card.querySelector('.response-callout');
                if (responseCallout) {
                    responseCallout.classList.add('response-callout-accepted');
                    responseCallout.innerHTML = `
                        <strong data-i18n="offer.latestCreatorSignal">Latest creator signal</strong>
                        <div class="mt-2 d-flex flex-wrap gap-2 align-items-center">
                            <span class="response-status accepted" data-i18n="offer.creatorAccepted">Creator accepted</span>
                            <span class="text-muted small" data-i18n="offer.openDetailsFullResponse">Open details to review the full creator response.</span>
                        </div>
                    `;
                }

                const emptyNote = document.querySelector('[data-brand-section-empty="accepted"]');
                if (emptyNote) {
                    emptyNote.remove();
                }

                acceptedGrid.prepend(card);
                if (typeof window.cre8ApplyI18n === 'function') {
                    window.cre8ApplyI18n();
                }
                updateSectionCounts();
            }

            function showLiveToast(newOffers) {
                if (!newOffers.length) {
                    return;
                }

                const toast = document.createElement('div');
                toast.className = 'live-toast live-toast-success';
                const acceptedLabel = translate(newOffers.length === 1 ? 'offer.newAcceptedOffer' : 'offer.newAcceptedOffers', `new accepted offer${newOffers.length === 1 ? '' : 's'}`);
                toast.innerHTML = `
                    <strong>${newOffers.length} ${escapeHtml(acceptedLabel)}</strong>
                    <span>${escapeHtml(newOffers.map((offer) => offer.titre).join(', '))}</span>
                `;
                stack.prepend(toast);

                window.setTimeout(() => {
                    toast.classList.add('is-leaving');
                    window.setTimeout(() => toast.remove(), 350);
                }, 5000);
            }

            async function pollAcceptedOffers() {
                try {
                    const url = new URL(window.location.href);
                    url.searchParams.set('notificationPing', '1');
                    const response = await fetch(url.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (!response.ok) {
                        return;
                    }

                    const data = await response.json();
                    const offers = Array.isArray(data.acceptedOffers) ? data.acceptedOffers : [];
                    const freshOffers = offers.filter((offer) => !knownAcceptedIds.has(String(offer.idOffre)));

                    if (freshOffers.length) {
                        freshOffers.forEach(elevateAcceptedCard);
                        showLiveToast(freshOffers);
                    }

                    updateBanner(data);
                    knownAcceptedIds = new Set(offers.map((offer) => String(offer.idOffre)));
                } catch (error) {
                    console.error('Unable to refresh accepted offer notifications.', error);
                }
            }

            updateSectionCounts();
            window.addEventListener('brandIndexWorkspaceUpdated', () => {
                updateSectionCounts();
            });
            window.addEventListener('cre8:languagechange', () => {
                updateSectionCounts();
            });
            window.setInterval(pollAcceptedOffers, 20000);
        })();
    </script>
    <script src="../layout/front-header.js"></script>
</body>
</html>
