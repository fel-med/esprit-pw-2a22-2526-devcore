<?php
require_once __DIR__ . '/../layout/session_bridge.php';
$currentUser = cre8_front_require_user('marque');
$frontActive = 'collaborations';

require_once __DIR__ . '/../../../Controleur/offreC.php';
require_once __DIR__ . '/../../../Controleur/condidatureC.php';

$brandId = (int) $currentUser['id'];
$controller = new OffreC();
$candidatureController = new CondidatureC();
$notificationController = $candidatureController;
$notificationUserId = (int) $brandId;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notificationAction'])) {
    $notificationAction = (string) $_POST['notificationAction'];
    if ($notificationAction === 'mark_one') {
        $notificationController->markNotificationActionAsRead((int) ($_POST['idNotificationAction'] ?? 0), $notificationUserId);
    } elseif ($notificationAction === 'mark_all') {
        $notificationController->markAllNotificationActionsAsRead($notificationUserId);
    }

    $redirect = basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'brand_index.php'));
    if (!empty($_SERVER['QUERY_STRING'])) {
        $redirect .= '?' . $_SERVER['QUERY_STRING'];
    }
    header('Location: ' . $redirect);
    exit;
}

if ($notificationUserId > 0) {
    $notificationController->generateBrandDeadlineSoonNotifications($notificationUserId);
}

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
            'subtitle' => 'Offers that are already visible to creators.',
            'empty' => 'No live published offers right now.',
            'cards' => [],
        ],
        'accepted' => [
            'key' => 'accepted',
            'domId' => 'brandAcceptedGrid',
            'themeClass' => 'section-accepted',
            'title' => 'Accepted offers',
            'subtitle' => 'Offers already accepted by the targeted creator.',
            'empty' => 'No creator has accepted an offer yet.',
            'cards' => [],
        ],
        'draft-pending' => [
            'key' => 'draft-pending',
            'domId' => 'brandDraftPendingGrid',
            'themeClass' => 'section-draft-pending',
            'title' => 'Draft / pending offers',
            'subtitle' => 'Offers still in draft or waiting for their publication date.',
            'empty' => 'No draft or scheduled offers at the moment.',
            'cards' => [],
        ],
        'declined' => [
            'key' => 'declined',
            'domId' => 'brandDeclinedGrid',
            'themeClass' => 'section-declined',
            'title' => 'Declined offers',
            'subtitle' => 'Offers kept in the history after the creator declined them.',
            'empty' => 'No declined offers in your pipeline.',
            'cards' => [],
        ],
        'outdated' => [
            'key' => 'outdated',
            'domId' => 'brandOutdatedGrid',
            'themeClass' => 'section-outdated',
            'title' => 'Outdated offers',
            'subtitle' => 'Offers whose deadline has already passed without a final creator acceptance or decline.',
            'empty' => 'No outdated offers in your pipeline.',
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
<link rel="icon" type="image/png" sizes="32x32" href="../../public/images/logo.png">
<link rel="shortcut icon" type="image/png" href="../../public/images/logo.png">
<link rel="apple-touch-icon" href="../../public/images/logo.png">
</head>
<body>
    <?php require_once dirname(__DIR__) . '/layout/header.php'; ?>
    <main class="container py-5">
        <div class="offre-page-shell">
            <section class="module-hero module-hero-notification-shell">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div>
                        <span class="module-eyebrow">Brand workspace</span>
                        <h1 class="display-5 fw-bold mt-3 mb-2 gradient-title">My targeted collaboration offers</h1>
                        <p class="lead text-muted">Track every invitation you sent to creators, monitor response signals, and keep the next collaboration moving.</p>
                    </div>
                    <div class="compact-actions">
                        <?php require __DIR__ . '/../condidature/notification_widget.php'; ?>
                        <a class="btn btn-primary btn-lg" href="brand_create.php">Create a new offer</a>
                        <a class="btn btn-outline-secondary btn-lg" href="../condidature/brand_index.php">Open response workspace</a>
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
                    <span class="stat-label">Responses to review</span>
                    <span class="stat-value"><?php echo (int) ($brandCandidatureMetrics['responsesToReview'] ?? 0); ?></span>
                    <span class="stat-note">Sent or under review</span>
                </article>
                <article class="stat-card">
                    <span class="stat-label">Negotiations waiting reply</span>
                    <span class="stat-value"><?php echo (int) ($brandCandidatureMetrics['negotiationsWaitingReply'] ?? 0); ?></span>
                    <span class="stat-note">Active negotiation candidatures</span>
                </article>
                <article class="stat-card">
                    <span class="stat-label">Offers expiring soon</span>
                    <span class="stat-value"><?php echo (int) ($brandOfferMetrics['expiringSoon'] ?? 0); ?></span>
                    <span class="stat-note">Deadlines within the next 7 days</span>
                </article>
                <article class="stat-card">
                    <span class="stat-label">Draft offers</span>
                    <span class="stat-value"><?php echo (int) ($brandOfferMetrics['draftOffers'] ?? 0); ?></span>
                    <span class="stat-note">Not published yet</span>
                </article>
                <article class="stat-card">
                    <span class="stat-label">Accepted collaborations</span>
                    <span class="stat-value"><?php echo (int) ($brandCandidatureMetrics['acceptedCollaborations'] ?? 0); ?></span>
                    <span class="stat-note"><?php echo htmlspecialchars(formatMoney($brandCandidatureMetrics['acceptedBudgetTotal'] ?? 0)); ?> accepted budget</span>
                </article>
            </section>

            <section class="filter-card">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div>
                        <h2 class="section-title">Filter offers</h2>
                        <p class="section-subtitle">Search by offer text, target creator, budget, deadline, or status.</p>
                    </div>
                    <?php if ($activeFilterCount > 0): ?>
                        <span class="offer-chip"><?php echo $activeFilterCount; ?> active filter<?php echo $activeFilterCount > 1 ? 's' : ''; ?></span>
                    <?php endif; ?>
                </div>
                <form method="get" action="brand_index.php" class="filter-stack mt-4">
                    <div class="filter-grid">
                        <div>
                            <label for="keyword" class="form-label fw-semibold">Keyword</label>
                            <input type="text" class="form-control" id="keyword" name="keyword" value="<?php echo htmlspecialchars($filters['keyword']); ?>" placeholder="Offer, objective, creator...">
                        </div>
                        <div>
                            <label for="status" class="form-label fw-semibold">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All statuses</option>
                                <option value="brouillon"<?php echo $filters['status'] === 'brouillon' ? ' selected' : ''; ?>>Draft</option>
                                <option value="publiee"<?php echo $filters['status'] === 'publiee' ? ' selected' : ''; ?>>Published</option>
                                <option value="pending"<?php echo $filters['status'] === 'pending' ? ' selected' : ''; ?>>Pending launch</option>
                                <option value="cloturee"<?php echo $filters['status'] === 'cloturee' ? ' selected' : ''; ?>>Closed</option>
                                <option value="expiree"<?php echo $filters['status'] === 'expiree' ? ' selected' : ''; ?>>Expired</option>
                                <option value="archivee"<?php echo $filters['status'] === 'archivee' ? ' selected' : ''; ?>>Archived</option>
                            </select>
                        </div>
                        <div>
                            <label for="budgetFrom" class="form-label fw-semibold">Budget min</label>
                            <input type="number" step="0.01" class="form-control" id="budgetFrom" name="budgetFrom" value="<?php echo htmlspecialchars($filters['budgetFrom']); ?>">
                        </div>
                        <div>
                            <label for="budgetTo" class="form-label fw-semibold">Budget max</label>
                            <input type="number" step="0.01" class="form-control" id="budgetTo" name="budgetTo" value="<?php echo htmlspecialchars($filters['budgetTo']); ?>">
                        </div>
                        <div>
                            <label for="deadlineFrom" class="form-label fw-semibold">Deadline from</label>
                            <input type="date" class="form-control" id="deadlineFrom" name="deadlineFrom" value="<?php echo htmlspecialchars($filters['deadlineFrom']); ?>">
                        </div>
                        <div>
                            <label for="deadlineTo" class="form-label fw-semibold">Deadline to</label>
                            <input type="date" class="form-control" id="deadlineTo" name="deadlineTo" value="<?php echo htmlspecialchars($filters['deadlineTo']); ?>">
                        </div>
                        <div>
                            <label for="sort" class="form-label fw-semibold">Sort</label>
                            <select class="form-select" id="sort" name="sort">
                                <option value=""<?php echo $filters['sort'] === '' ? ' selected' : ''; ?>>Newest</option>
                                <option value="oldest"<?php echo $filters['sort'] === 'oldest' ? ' selected' : ''; ?>>Oldest</option>
                                <option value="deadline_soon"<?php echo $filters['sort'] === 'deadline_soon' ? ' selected' : ''; ?>>Deadline soon</option>
                                <option value="budget_high"<?php echo $filters['sort'] === 'budget_high' ? ' selected' : ''; ?>>Budget high to low</option>
                                <option value="budget_low"<?php echo $filters['sort'] === 'budget_low' ? ' selected' : ''; ?>>Budget low to high</option>
                                <option value="status"<?php echo $filters['sort'] === 'status' ? ' selected' : ''; ?>>Status</option>
                            </select>
                        </div>
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary">Apply filters</button>
                        <a class="btn btn-outline-secondary" href="brand_index.php">Reset</a>
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
                                <span class="offer-tab-label"><?php echo htmlspecialchars($section['title']); ?></span>
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
                                data-empty-message="<?php echo htmlspecialchars($section['empty'], ENT_QUOTES); ?>"
                                <?php echo $isActivePanel ? '' : 'hidden'; ?>
                            >
                                <div class="offer-section-header">
                                    <div class="offer-section-copy">
                                        <h2 class="section-title"><?php echo htmlspecialchars($section['title']); ?></h2>
                                        <p class="section-subtitle"><?php echo htmlspecialchars($section['subtitle']); ?></p>
                                    </div>
                                    <span class="offer-section-count" data-brand-section-count="<?php echo htmlspecialchars($section['key']); ?>">
                                        <?php echo count($section['cards']); ?> offer<?php echo count($section['cards']) === 1 ? '' : 's'; ?>
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
                                                        <span class="offer-status <?php echo htmlspecialchars($isOutdated ? 'status-outdated' : offerStatusClass($displayStatus)); ?>">
                                                            <?php echo htmlspecialchars($isOutdated ? 'Outdated' : translateOfferStatus($displayStatus)); ?>
                                                        </span>
                                                        <?php if ($isAccepted): ?>
                                                            <span class="priority-badge priority-badge-success js-accepted-flag">Accepted by creator</span>
                                                        <?php elseif ($isDeclined): ?>
                                                            <span class="priority-badge priority-badge-danger">Declined by creator</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <h2 class="offer-card-title"><?php echo htmlspecialchars($offre->getTitre()); ?></h2>
                                                    <p class="offer-summary mt-2"><?php echo htmlspecialchars(excerptText($offre->getDescription(), 165)); ?></p>
                                                </div>
                                            </div>

                                            <div class="offer-meta">
                                                <span class="offer-chip"><?php echo htmlspecialchars(formatMoney($offre->getBudgetPropose())); ?></span>
                                                <span class="offer-chip"><?php echo htmlspecialchars($publicationLabel); ?>: <?php echo htmlspecialchars($offre->getDatePublication()); ?></span>
                                                <span class="offer-chip">Deadline: <?php echo htmlspecialchars($offre->getDateLimite()); ?></span>
                                                <span class="offer-chip"><?php echo count($responses); ?> response<?php echo count($responses) === 1 ? '' : 's'; ?></span>
                                            </div>

                                            <div class="offer-detail-list">
                                                <div class="offer-detail-item">
                                                    <strong>Target creator</strong>
                                                    <span><?php echo htmlspecialchars($creator['nom'] ?? 'No creator selected yet'); ?></span>
                                                    <p><?php echo htmlspecialchars($creator['email'] ?? ''); ?></p>
                                                </div>
                                                <div class="offer-detail-item">
                                                    <strong>Objective</strong>
                                                    <span><?php echo htmlspecialchars($offre->getObjectif()); ?></span>
                                                </div>
                                            </div>

                                            <?php if ($targetedResponse): ?>
                                                <div class="response-callout<?php echo $isAccepted ? ' response-callout-accepted' : ($isDeclined ? ' response-callout-declined' : ''); ?>">
                                                    <strong>Latest creator signal</strong>
                                                    <div class="mt-2 d-flex flex-wrap gap-2 align-items-center">
                                                        <span class="response-status <?php echo htmlspecialchars(responseStatusClass($targetedResponse['statutCandidature'])); ?>">
                                                            <?php echo htmlspecialchars(responseStatusLabel($targetedResponse)); ?>
                                                        </span>
                                                        <span class="text-muted small">
                                                            <?php if ($isDeclined): ?>
                                                                This offer stays in the pipeline, but moves to the bottom.
                                                            <?php elseif (($targetedResponse['statutCandidature'] ?? '') === 'brouillon'): ?>
                                                                The creator saved a draft response but has not submitted it yet.
                                                            <?php else: ?>
                                                                Budget reply: EUR <?php echo htmlspecialchars($targetedResponse['budgetPropose']); ?>
                                                            <?php endif; ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            <?php elseif ($offre->isPendingPublication()): ?>
                                                <div class="response-callout">
                                                    <strong>Scheduled launch</strong>
                                                    <div class="mt-2 text-muted small">This offer will appear to the creator on <?php echo htmlspecialchars($offre->getDatePublication()); ?>.</div>
                                                </div>
                                            <?php elseif ($isOutdated): ?>
                                                <div class="response-callout">
                                                    <strong>Deadline passed</strong>
                                                    <div class="mt-2 text-muted small">This offer stays available for history, but the response window has ended.</div>
                                                </div>
                                            <?php else: ?>
                                                <div class="response-callout">
                                                    <strong>Waiting for creator reply</strong>
                                                    <div class="mt-2 text-muted small">No reply from the creator yet.</div>
                                                </div>
                                            <?php endif; ?>

                                        <div class="compact-actions">
                                            <?php if ($isAccepted): ?>
                                                <span class="btn btn-outline-secondary disabled" aria-disabled="true">Editing locked</span>
                                            <?php else: ?>
                                                <a class="btn btn-outline-secondary" href="brand_edit.php?idOffre=<?php echo (int) $offre->getIdOffre(); ?>">Edit offer</a>
                                            <?php endif; ?>
                                            <form
                                                method="post"
                                                action="brand_delete.php"
                                                    data-delete-confirm
                                                    data-delete-title="<?php echo htmlspecialchars($offre->getTitre(), ENT_QUOTES); ?>"
                                                    data-delete-creator="<?php echo htmlspecialchars($creator['nom'] ?? 'No creator selected yet', ENT_QUOTES); ?>"
                                                >
                                                    <input type="hidden" name="idOffre" value="<?php echo (int) $offre->getIdOffre(); ?>">
                                                    <button type="submit" class="btn btn-outline-danger">Delete</button>
                                                </form>
                                            </div>
                                        </article>
                                    <?php endforeach; ?>
                                </div>

                                <?php if (empty($section['cards'])): ?>
                                    <div class="note-block offer-section-empty" data-brand-section-empty="<?php echo htmlspecialchars($section['key']); ?>">
                                        <strong><?php echo htmlspecialchars($section['title']); ?></strong>
                                        <p><?php echo htmlspecialchars($section['empty']); ?></p>
                                    </div>
                                <?php endif; ?>
                            </section>
                        <?php endforeach; ?>
                    </div>
                </section>
                <nav class="front-pagination" aria-label="Brand offer pages">
                    <span>Page <?php echo $page; ?> · Showing up to <?php echo $perPage; ?> filtered offers</span>
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
                    <div class="empty-state-icon">+</div>
                    <h2 class="section-title">No targeted offers yet</h2>
                    <p class="section-subtitle">Start your collaboration pipeline by selecting a creator and sending a focused invitation instead of a generic brief.</p>
                    <div class="compact-actions justify-content-center mt-4">
                        <a class="btn btn-primary btn-lg" href="brand_create.php">Create your first targeted offer</a>
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
            const banner = document.getElementById('acceptedOfferBanner');
            const title = document.getElementById('acceptedBannerTitle');
            const text = document.getElementById('acceptedBannerText');
            const list = document.getElementById('acceptedBannerList');
            const stack = document.getElementById('liveNotificationStack');

            if (!banner || !stack || !window.fetch) {
                return;
            }

            let knownAcceptedIds = new Set((banner.dataset.acceptedIds || '').split(',').filter(Boolean));

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
                        emptyState.innerHTML = `
                            <strong>${escapeHtml(panel.dataset.emptyTitle || '')}</strong>
                            <p>${escapeHtml(panel.dataset.emptyMessage || '')}</p>
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
                    node.textContent = `${total} offer${total === 1 ? '' : 's'}`;
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
                        flag.textContent = 'Accepted by creator';
                        row.appendChild(flag);
                    }
                }

                const responseCallout = card.querySelector('.response-callout');
                if (responseCallout) {
                    responseCallout.classList.add('response-callout-accepted');
                    responseCallout.innerHTML = `
                        <strong>Latest creator signal</strong>
                        <div class="mt-2 d-flex flex-wrap gap-2 align-items-center">
                            <span class="response-status accepted">Creator accepted</span>
                            <span class="text-muted small">Open details to review the full creator response.</span>
                        </div>
                    `;
                }

                const emptyNote = document.querySelector('[data-brand-section-empty="accepted"]');
                if (emptyNote) {
                    emptyNote.remove();
                }

                acceptedGrid.prepend(card);
                updateSectionCounts();
            }

            function showLiveToast(newOffers) {
                if (!newOffers.length) {
                    return;
                }

                const toast = document.createElement('div');
                toast.className = 'live-toast live-toast-success';
                toast.innerHTML = `
                    <strong>${newOffers.length} new accepted offer${newOffers.length === 1 ? '' : 's'}</strong>
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
            window.setInterval(pollAcceptedOffers, 20000);
        })();
    </script>
<?php
$cre8PilotContext = [
    'page' => 'brand_offer_workspace',
    'mode' => 'list',
    'role' => 'marque',
    'allowedActions' => ['normal_chat', 'summarize_page', 'analyze_page', 'apply_filters', 'reset_filter_action', 'recommend_next_action', 'find_urgent_offers', 'explain_statuses', 'draft_invite_message', 'apply_search', 'sort_results'],
    'formTarget' => 'filter_form',
    'visibleEntityType' => 'offre',
];
require __DIR__ . '/../condidature/cre8pilot_widget.php';
?>
    <script src="../layout/front-header.js"></script>
</body>
</html>
