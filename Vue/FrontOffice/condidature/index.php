<?php
session_start();
$frontActive = 'collaborations';

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
$creatorUser = $creatorId ? ($controller->getUsersByIds([$creatorId], 'createur')[$creatorId] ?? null) : null;
$notificationController = $controller;
$notificationUserId = (int) ($creatorId ?? 0);

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

if ($notificationUserId > 0) {
    $notificationController->generateCreatorDeadlineSoonNotifications($notificationUserId);
}

$notice = trim((string) ($_GET['notice'] ?? ''));
$noticeType = trim((string) ($_GET['noticeType'] ?? 'success'));
$error = null;
$isAjaxRequest = (isset($_REQUEST['ajax']) && (string) $_REQUEST['ajax'] === '1')
    || strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';

function translateCandidatureStatus($status)
{
    return match ((string) $status) {
        'brouillon' => 'Draft response',
        'envoyee' => 'Accepted invitation',
        'en_etude' => 'Response under review',
        'negociation' => 'Negotiation requested',
        'acceptee' => 'Accepted terms',
        'refusee' => 'Refused by brand',
        'retiree' => 'Declined invitation',
        default => ucwords(str_replace('_', ' ', (string) $status)),
    };
}

function candidatureBadgeClass($status)
{
    return match ((string) $status) {
        'brouillon' => 'status-draft',
        'envoyee' => 'status-sent',
        'en_etude' => 'status-review',
        'negociation' => 'status-negotiation',
        'acceptee' => 'status-accepted',
        'refusee' => 'status-refused',
        'retiree' => 'status-withdrawn',
        default => 'status-pending',
    };
}

function translateOrigin($origin)
{
    return match ((string) $origin) {
        'par_offre' => 'Offer invitation',
        'par_campagne' => 'Campaign application',
        default => ucwords(str_replace('_', ' ', (string) $origin)),
    };
}

function formatMoney($value)
{
    return 'EUR ' . number_format((float) $value, 2, '.', ',');
}

function formatDateLabel($value, $fallback = 'Not scheduled')
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

function excerptText($text, $length = 150)
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

function isFinalCandidatureStatus($status)
{
    return in_array((string) $status, ['acceptee', 'refusee', 'retiree'], true);
}

function isContextOutdated(array $context)
{
    $deadline = $context['source']['dateLimite'] ?? null;
    if (!$deadline || isFinalCandidatureStatus($context['condidature']->getStatutCandidature())) {
        return false;
    }

    $timestamp = strtotime((string) $deadline);
    if ($timestamp === false) {
        return false;
    }

    return new DateTime(date('Y-m-d', $timestamp)) < new DateTime('today');
}

function candidatureCardToneClass($status, $isOutdated = false)
{
    if ($isOutdated) {
        return ' is-outdated';
    }

    return match ((string) $status) {
        'brouillon' => ' is-draft',
        'negociation', 'en_etude' => ' is-review',
        'envoyee', 'acceptee' => ' is-accepted',
        'refusee', 'retiree' => ' is-declined',
        default => '',
    };
}

function candidatureSignalToneClass($status)
{
    return match ((string) $status) {
        'envoyee', 'acceptee' => ' response-callout-accepted',
        'refusee', 'retiree' => ' response-callout-declined',
        default => '',
    };
}

function getSavedCreatorCandidatureIds($creatorId)
{
    $savedByUser = $_SESSION['saved_candidature_ids']['createur'] ?? [];

    return array_values(array_unique(array_map('intval', $savedByUser[(int) $creatorId] ?? [])));
}

function storeSavedCreatorCandidatureIds($creatorId, array $ids)
{
    if (!isset($_SESSION['saved_candidature_ids']) || !is_array($_SESSION['saved_candidature_ids'])) {
        $_SESSION['saved_candidature_ids'] = [];
    }

    if (!isset($_SESSION['saved_candidature_ids']['createur']) || !is_array($_SESSION['saved_candidature_ids']['createur'])) {
        $_SESSION['saved_candidature_ids']['createur'] = [];
    }

    $_SESSION['saved_candidature_ids']['createur'][(int) $creatorId] = array_values(array_unique(array_map('intval', $ids)));
}

function candidatureTabKey($status, array $context = null)
{
    if ($context !== null && isContextOutdated($context)) {
        return 'outdated';
    }

    return match ((string) $status) {
        'brouillon' => 'saved',
        'envoyee', 'acceptee' => 'accepted',
        'refusee', 'retiree' => 'refused',
        default => 'waiting',
    };
}

function statusFilterToCandidatureTab($status)
{
    if ($status === '') {
        return '';
    }

    return candidatureTabKey($status);
}

$filters = [
    'keyword' => trim((string) ($_GET['keyword'] ?? '')),
    'status' => trim((string) ($_GET['status'] ?? '')),
    'origin' => trim((string) ($_GET['origin'] ?? '')),
    'typeReponse' => trim((string) ($_GET['typeReponse'] ?? '')),
    'dateFrom' => trim((string) ($_GET['dateFrom'] ?? '')),
    'dateTo' => trim((string) ($_GET['dateTo'] ?? '')),
    'hasCv' => trim((string) ($_GET['hasCv'] ?? '')),
    'hasPortfolio' => trim((string) ($_GET['hasPortfolio'] ?? '')),
    'sort' => trim((string) ($_GET['sort'] ?? '')),
    'editableOnly' => isset($_GET['editableOnly']) && $_GET['editableOnly'] === '1',
];
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;
$hasNextPage = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $creatorId && isset($_POST['toggleSaved'], $_POST['idCandidature']) && is_numeric($_POST['idCandidature'])) {
    $idCandidature = (int) $_POST['idCandidature'];
    $savedCandidatures = getSavedCreatorCandidatureIds($creatorId);

    if (in_array($idCandidature, $savedCandidatures, true)) {
        $savedCandidatures = array_values(array_filter($savedCandidatures, static fn($id) => (int) $id !== $idCandidature));
    } else {
        $savedCandidatures[] = $idCandidature;
    }

    storeSavedCreatorCandidatureIds($creatorId, $savedCandidatures);

    if (!$isAjaxRequest) {
        $redirect = 'index.php';
        if (!empty($_SERVER['QUERY_STRING'])) {
            $redirect .= '?' . $_SERVER['QUERY_STRING'];
        }

        header('Location: ' . $redirect);
        exit;
    }
}

$allContexts = [];
$contexts = [];
$offerFeed = [];
$actionableOffers = [];
$creatorMetrics = [
    'invitationsToAnswer' => 0,
    'negotiationsWaitingReply' => 0,
    'closestDeadline' => null,
    'bestProposedBudget' => 0,
    'applicationsWaitingDecision' => 0,
    'draftApplications' => 0,
    'acceptedCollaborations' => 0,
];
$summary = [
    'total' => 0,
    'editable' => 0,
    'envoyee' => 0,
    'en_etude' => 0,
    'negociation' => 0,
    'acceptee' => 0,
];

if ($creatorId) {
    try {
        $allContexts = $controller->getCreatorCandidatures($creatorId);
        $pagedFilters = $filters + [
            'limit' => $perPage + 1,
            'offset' => $offset,
        ];
        $contexts = $controller->getCreatorCandidatures($creatorId, $pagedFilters);
        if (count($contexts) > $perPage) {
            $hasNextPage = true;
            array_pop($contexts);
        }
        $offerFeed = $controller->getCreatorOfferFeed($creatorId, ['keyword' => $filters['keyword']]);
        $summary = $controller->summarizeContexts($allContexts);
        $creatorMetrics = $controller->getCreatorActionMetrics($creatorId);
        $actionableOffers = array_values(array_filter($offerFeed, static function ($item) {
            $condidature = $item['condidature'];

            return !$condidature || $condidature->canCreatorEdit();
        }));
    } catch (Throwable $exception) {
        $error = 'The candidature workspace could not be loaded right now.';
    }
} else {
    $error = 'No creator profile is available for this workspace.';
}

$awaitingReviewCount = (int) ($summary['envoyee'] ?? 0) + (int) ($summary['en_etude'] ?? 0);
$activeFilterCount = count(array_filter([
    $filters['keyword'],
    $filters['status'],
    $filters['origin'],
    $filters['typeReponse'],
    $filters['dateFrom'],
    $filters['dateTo'],
    $filters['hasCv'],
    $filters['hasPortfolio'],
    $filters['sort'],
    $filters['editableOnly'] ? '1' : '',
], static fn($value) => $value !== ''));
$paginationBase = $_GET;
unset($paginationBase['page']);
$prevPageUrl = $page > 1 ? 'index.php?' . http_build_query($paginationBase + ['page' => $page - 1]) : '';
$nextPageUrl = $hasNextPage ? 'index.php?' . http_build_query($paginationBase + ['page' => $page + 1]) : '';
$savedCandidatureIds = $creatorId ? getSavedCreatorCandidatureIds($creatorId) : [];

$creatorSectionBuckets = [
    'waiting' => [],
    'accepted' => [],
    'refused' => [],
    'outdated' => [],
    'saved' => [],
];

foreach ($contexts as $context) {
    $statusKey = (string) $context['condidature']->getStatutCandidature();
    $creatorSectionBuckets[candidatureTabKey($statusKey, $context)][] = $context;
}

$savedContextMap = [];
foreach ($allContexts as $context) {
    $idCandidature = (int) $context['condidature']->getIdCandidature();
    if ($context['condidature']->isDraft() || in_array($idCandidature, $savedCandidatureIds, true)) {
        $savedContextMap[$idCandidature] = $context;
    }
}

$savedContexts = array_values($savedContextMap);
$validSavedCandidatureIds = array_values(array_map(
    static fn($context) => (int) $context['condidature']->getIdCandidature(),
    array_filter($savedContexts, static fn($context) => in_array((int) $context['condidature']->getIdCandidature(), $savedCandidatureIds, true))
));

if ($creatorId && $validSavedCandidatureIds !== $savedCandidatureIds) {
    storeSavedCreatorCandidatureIds($creatorId, $validSavedCandidatureIds);
    $savedCandidatureIds = $validSavedCandidatureIds;
}

$creatorSections = [
    [
        'key' => 'waiting',
        'title' => 'Waiting',
        'subtitle' => 'Responses that are still moving through review, negotiation, or a pending brand decision.',
        'themeClass' => 'section-waiting',
        'emptyTitle' => 'No waiting candidatures',
        'emptyCopy' => 'Nothing is waiting for a next step in this filtered view right now.',
        'cards' => $creatorSectionBuckets['waiting'],
    ],
    [
        'key' => 'accepted',
        'title' => 'Accepted',
        'subtitle' => 'Final positive outcomes where the latest terms were fully accepted.',
        'themeClass' => 'section-accepted',
        'emptyTitle' => 'No accepted candidatures',
        'emptyCopy' => 'No candidature has reached an accepted outcome in this view yet.',
        'cards' => $creatorSectionBuckets['accepted'],
    ],
    [
        'key' => 'refused',
        'title' => 'Refused',
        'subtitle' => 'Responses that ended with a refusal or a declined invitation outcome.',
        'themeClass' => 'section-declined',
        'emptyTitle' => 'No refused candidatures',
        'emptyCopy' => 'No refused or declined candidature appears in this filtered view.',
        'cards' => $creatorSectionBuckets['refused'],
    ],
    [
        'key' => 'outdated',
        'title' => 'Outdated',
        'subtitle' => 'Responses linked to sources whose deadline has already passed without a final outcome.',
        'themeClass' => 'section-outdated',
        'emptyTitle' => 'No outdated candidatures',
        'emptyCopy' => 'No candidature is past its source deadline in this filtered view.',
        'cards' => $creatorSectionBuckets['outdated'],
    ],
    [
        'key' => 'saved',
        'title' => 'Saved',
        'subtitle' => 'Draft responses and bookmarked candidatures you want to revisit later.',
        'themeClass' => 'section-draft-pending',
        'emptyTitle' => 'No saved candidatures',
        'emptyCopy' => 'You do not have any draft or bookmarked candidature in this workspace yet.',
        'cards' => $savedContexts,
    ],
];

$creatorDefaultSectionKey = 'waiting';
$requestedCreatorTab = statusFilterToCandidatureTab($filters['status']);
if ($requestedCreatorTab !== '') {
    $creatorDefaultSectionKey = $requestedCreatorTab;
} else {
    foreach ($creatorSections as $section) {
        if (!empty($section['cards'])) {
            $creatorDefaultSectionKey = $section['key'];
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/../layout/front-theme-bootstrap.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Candidatures - Cre8Connect</title>
    <link rel="stylesheet" href="../css/frontoffice.css">
    <link rel="stylesheet" href="../offre/offre.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../offre/offre.css')); ?>">
    <link rel="stylesheet" href="condidature.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/condidature.css')); ?>">
    <link rel="stylesheet" href="../layout/front-header.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../layout/front-header.css')); ?>">
<link rel="icon" type="image/png" sizes="32x32" href="../../public/images/logo.png">
<link rel="shortcut icon" type="image/png" href="../../public/images/logo.png">
<link rel="apple-touch-icon" href="../../public/images/logo.png">
</head>
<body>
    <?php require_once dirname(__DIR__) . '/layout/header.php'; ?>
    <main class="container py-5">
        <div class="offre-page-shell" data-candidature-live-region>
            <section class="module-hero module-hero-notification-shell">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div>
                        <span class="module-eyebrow">Creator candidature workspace</span>
                        <h1 class="display-5 fw-bold mt-3 mb-2 gradient-title">My candidatures</h1>
                        <p class="lead text-muted mb-0">
                            Track every response you sent, keep drafts moving, and follow each targeted collaboration from first reply to final decision.
                        </p>
                    </div>
                    <div class="compact-actions">
                        <?php require __DIR__ . '/notification_widget.php'; ?>
                        <?php if ($creatorUser): ?>
                            <div class="note-block">
                                <strong><?php echo htmlspecialchars($creatorUser['nom']); ?></strong>
                                <p><?php echo htmlspecialchars($creatorUser['email']); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <?php if ($notice !== ''): ?>
                <div class="alert alert-<?php echo $noticeType === 'danger' ? 'danger' : 'success'; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($notice); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <section class="stats-grid candidature-stats-grid">
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
                    <span class="stat-note">Highest active invitation budget</span>
                </article>
                <article class="stat-card">
                    <span class="stat-label">Draft applications</span>
                    <span class="stat-value"><?php echo (int) ($creatorMetrics['draftApplications'] ?? 0); ?></span>
                    <span class="stat-note">Saved candidatures to finish</span>
                </article>
                <article class="stat-card">
                    <span class="stat-label">Accepted collaborations</span>
                    <span class="stat-value"><?php echo (int) ($creatorMetrics['acceptedCollaborations'] ?? 0); ?></span>
                    <span class="stat-note"><?php echo (int) ($creatorMetrics['applicationsWaitingDecision'] ?? 0); ?> waiting decision</span>
                </article>
            </section>

            <section class="section-card">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div>
                        <h2 class="section-title">Offers ready for your response</h2>
                        <p class="section-subtitle">Start a candidature from the targeted invitations currently visible in your creator inbox.</p>
                    </div>
                    <div class="compact-actions">
                        <a class="btn btn-outline-secondary" href="campaign_opportunities.php">Browse campaign opportunities</a>
                        <span class="offer-chip"><?php echo count($actionableOffers); ?> ready</span>
                    </div>
                </div>

                <?php if (!empty($actionableOffers)): ?>
                    <div class="source-feed-grid mt-4">
                        <?php foreach (array_slice($actionableOffers, 0, 4) as $item): ?>
                            <?php
                            $source = $item['source'];
                            $brand = $item['brand'];
                            $condidature = $item['condidature'];
                            $responseLabel = !$condidature
                                ? 'Start response'
                                : ($condidature->isDraft() ? 'Continue draft' : 'Update negotiation');
                            ?>
                            <article class="source-feed-card">
                                <div class="source-feed-top">
                                    <div>
                                        <span class="origin-badge"><?php echo htmlspecialchars(translateOrigin($source['origin'])); ?></span>
                                        <h3 class="source-feed-title"><?php echo htmlspecialchars($source['title']); ?></h3>
                                    </div>
                                    <?php if ($condidature): ?>
                                        <span class="candidature-badge <?php echo htmlspecialchars(candidatureBadgeClass($condidature->getStatutCandidature())); ?>">
                                            <?php echo htmlspecialchars($condidature->getDisplayStatusLabel()); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <p class="source-feed-copy"><?php echo htmlspecialchars(excerptText($source['description'] ?: $source['objective'], 130)); ?></p>
                                <div class="candidature-inline-meta">
                                    <span class="offer-chip"><?php echo htmlspecialchars(formatMoney($source['budgetPropose'] ?? 0)); ?></span>
                                    <span class="offer-chip">Deadline: <?php echo htmlspecialchars(formatDateLabel($source['dateLimite'])); ?></span>
                                    <span class="offer-chip">Brand: <?php echo htmlspecialchars($brand['nom'] ?: 'Unknown brand'); ?></span>
                                </div>
                                <div class="compact-actions mt-3">
                                    <a class="btn btn-primary" href="details.php?origin=par_offre&idSource=<?php echo (int) $source['id']; ?>"><?php echo htmlspecialchars($responseLabel); ?></a>
                                    <a class="btn btn-outline-secondary" href="../offre/creator_details.php?idOffre=<?php echo (int) $source['id']; ?>&idCreateur=<?php echo (int) $creatorId; ?>">View source offer</a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="note-block mt-4">
                        <strong>No response-ready offers right now</strong>
                        <p>All visible invitations are already locked, or there are no active targeted offers for this creator at the moment.</p>
                    </div>
                <?php endif; ?>
            </section>

            <section class="filter-card">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div>
                        <h2 class="section-title">Filter your candidatures</h2>
                        <p class="section-subtitle">Search by source, message, status, or keep only the responses that still need your attention.</p>
                    </div>
                    <?php if ($activeFilterCount > 0): ?>
                        <span class="offer-chip"><?php echo $activeFilterCount; ?> active filter<?php echo $activeFilterCount > 1 ? 's' : ''; ?></span>
                    <?php endif; ?>
                </div>
                <form method="get" action="index.php" class="filter-stack mt-4">
                    <div class="filter-grid">
                        <div>
                            <label for="keyword" class="form-label fw-semibold">Keyword</label>
                            <input type="text" class="form-control" id="keyword" name="keyword" value="<?php echo htmlspecialchars($filters['keyword']); ?>" placeholder="Source title, message, brand...">
                        </div>
                        <div>
                            <label for="status" class="form-label fw-semibold">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All statuses</option>
                                <option value="brouillon"<?php echo $filters['status'] === 'brouillon' ? ' selected' : ''; ?>>Draft</option>
                                <option value="envoyee"<?php echo $filters['status'] === 'envoyee' ? ' selected' : ''; ?>>Sent</option>
                                <option value="en_etude"<?php echo $filters['status'] === 'en_etude' ? ' selected' : ''; ?>>Under review</option>
                                <option value="negociation"<?php echo $filters['status'] === 'negociation' ? ' selected' : ''; ?>>Negotiation</option>
                                <option value="acceptee"<?php echo $filters['status'] === 'acceptee' ? ' selected' : ''; ?>>Accepted</option>
                                <option value="refusee"<?php echo $filters['status'] === 'refusee' ? ' selected' : ''; ?>>Refused</option>
                                <option value="retiree"<?php echo $filters['status'] === 'retiree' ? ' selected' : ''; ?>>Withdrawn</option>
                            </select>
                        </div>
                        <div>
                            <label for="origin" class="form-label fw-semibold">Origin</label>
                            <select class="form-select" id="origin" name="origin">
                                <option value="">All origins</option>
                                <option value="par_offre"<?php echo $filters['origin'] === 'par_offre' ? ' selected' : ''; ?>>Offer invitation</option>
                                <option value="par_campagne"<?php echo $filters['origin'] === 'par_campagne' ? ' selected' : ''; ?>>Campaign application</option>
                            </select>
                        </div>
                        <div>
                            <label for="typeReponse" class="form-label fw-semibold">Response type</label>
                            <select class="form-select" id="typeReponse" name="typeReponse">
                                <option value="">All types</option>
                                <option value="application"<?php echo $filters['typeReponse'] === 'application' ? ' selected' : ''; ?>>Application</option>
                                <option value="acceptation"<?php echo $filters['typeReponse'] === 'acceptation' ? ' selected' : ''; ?>>Acceptance</option>
                                <option value="negociation"<?php echo $filters['typeReponse'] === 'negociation' ? ' selected' : ''; ?>>Negotiation</option>
                                <option value="refus"<?php echo $filters['typeReponse'] === 'refus' ? ' selected' : ''; ?>>Refusal</option>
                            </select>
                        </div>
                        <div>
                            <label for="dateFrom" class="form-label fw-semibold">Submitted from</label>
                            <input type="date" class="form-control" id="dateFrom" name="dateFrom" value="<?php echo htmlspecialchars($filters['dateFrom']); ?>">
                        </div>
                        <div>
                            <label for="dateTo" class="form-label fw-semibold">Submitted to</label>
                            <input type="date" class="form-control" id="dateTo" name="dateTo" value="<?php echo htmlspecialchars($filters['dateTo']); ?>">
                        </div>
                        <div>
                            <label for="hasCv" class="form-label fw-semibold">CV file</label>
                            <select class="form-select" id="hasCv" name="hasCv">
                                <option value="">All</option>
                                <option value="1"<?php echo $filters['hasCv'] === '1' ? ' selected' : ''; ?>>Has CV</option>
                                <option value="0"<?php echo $filters['hasCv'] === '0' ? ' selected' : ''; ?>>No CV</option>
                            </select>
                        </div>
                        <div>
                            <label for="hasPortfolio" class="form-label fw-semibold">Portfolio</label>
                            <select class="form-select" id="hasPortfolio" name="hasPortfolio">
                                <option value="">All</option>
                                <option value="1"<?php echo $filters['hasPortfolio'] === '1' ? ' selected' : ''; ?>>Has portfolio</option>
                                <option value="0"<?php echo $filters['hasPortfolio'] === '0' ? ' selected' : ''; ?>>No portfolio</option>
                            </select>
                        </div>
                        <div>
                            <label for="sort" class="form-label fw-semibold">Sort</label>
                            <select class="form-select" id="sort" name="sort">
                                <option value=""<?php echo $filters['sort'] === '' ? ' selected' : ''; ?>>Workflow priority</option>
                                <option value="newest"<?php echo $filters['sort'] === 'newest' ? ' selected' : ''; ?>>Newest</option>
                                <option value="oldest"<?php echo $filters['sort'] === 'oldest' ? ' selected' : ''; ?>>Oldest</option>
                                <option value="budget_high"<?php echo $filters['sort'] === 'budget_high' ? ' selected' : ''; ?>>Budget high to low</option>
                                <option value="budget_low"<?php echo $filters['sort'] === 'budget_low' ? ' selected' : ''; ?>>Budget low to high</option>
                                <option value="proposed_delay"<?php echo $filters['sort'] === 'proposed_delay' ? ' selected' : ''; ?>>Proposed delay</option>
                                <option value="decision_date"<?php echo $filters['sort'] === 'decision_date' ? ' selected' : ''; ?>>Decision date</option>
                                <option value="status"<?php echo $filters['sort'] === 'status' ? ' selected' : ''; ?>>Status</option>
                            </select>
                        </div>
                        <div>
                            <label for="editableOnly" class="form-label fw-semibold">Editable only</label>
                            <select class="form-select" id="editableOnly" name="editableOnly">
                                <option value="0"<?php echo !$filters['editableOnly'] ? ' selected' : ''; ?>>Show all</option>
                                <option value="1"<?php echo $filters['editableOnly'] ? ' selected' : ''; ?>>Editable now</option>
                            </select>
                        </div>
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary">Apply filters</button>
                        <a class="btn btn-outline-secondary" href="index.php">Reset</a>
                    </div>
                </form>
            </section>

            <section class="offer-tab-shell" data-offer-tab-shell data-default-tab="<?php echo htmlspecialchars($creatorDefaultSectionKey); ?>">
                <div class="offer-tab-list" role="tablist" aria-label="Creator candidature tabs">
                    <?php foreach ($creatorSections as $section): ?>
                        <?php $isActiveTab = $section['key'] === $creatorDefaultSectionKey; ?>
                        <button
                            type="button"
                            class="offer-tab-button<?php echo $isActiveTab ? ' is-active' : ''; ?>"
                            id="creator-candidature-tab-<?php echo htmlspecialchars($section['key']); ?>"
                            role="tab"
                            aria-selected="<?php echo $isActiveTab ? 'true' : 'false'; ?>"
                            aria-controls="creator-candidature-panel-<?php echo htmlspecialchars($section['key']); ?>"
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
                            id="creator-candidature-panel-<?php echo htmlspecialchars($section['key']); ?>"
                            role="tabpanel"
                            aria-labelledby="creator-candidature-tab-<?php echo htmlspecialchars($section['key']); ?>"
                            data-offer-tab-panel="<?php echo htmlspecialchars($section['key']); ?>"
                            <?php echo $isActivePanel ? '' : 'hidden'; ?>
                        >
                            <div class="offer-section-header">
                                <div class="offer-section-copy">
                                    <h2 class="section-title"><?php echo htmlspecialchars($section['title']); ?></h2>
                                    <p class="section-subtitle"><?php echo htmlspecialchars($section['subtitle']); ?></p>
                                </div>
                                <span class="offer-section-count">
                                    <?php echo count($section['cards']); ?> candidature<?php echo count($section['cards']) === 1 ? '' : 's'; ?>
                                </span>
                            </div>

                            <?php if (!empty($section['cards'])): ?>
                                <div class="candidature-card-grid">
                                    <?php foreach ($section['cards'] as $context): ?>
                                        <?php
                                        $condidature = $context['condidature'];
                                        $source = $context['source'];
                                        $brand = $context['brand'];
                                        $isEditable = $condidature->canCreatorEdit();
                                        $isSavedForLater = in_array((int) $condidature->getIdCandidature(), $savedCandidatureIds, true);
                                        $statusKey = (string) $condidature->getStatutCandidature();
                                        $isOutdated = isContextOutdated($context);
                                        $latestBrandSignal = $context['negotiation']['latestBrand'] ?? null;
                                        $brandSignalMessage = trim((string) ($latestBrandSignal['message'] ?? ''));
                                        $decisionNotePreview = trim((string) $condidature->getNoteDecision());
                                        $availabilityLabel = formatDateLabel($condidature->getDateDisponibilite(), 'Not shared yet');
                                        $termsPreview = excerptText($condidature->getConditionsCreateur(), 110);
                                        $portfolioPreview = trim((string) $condidature->getPortfolioUrl());
                                        $refusalPreview = excerptText($condidature->getMotifRefus(), 110);
                                        $notePreview = excerptText($condidature->getMessageMotivation(), 90);
                                        $responsePreview = $condidature->getResponseMode() === 'decline'
                                            ? excerptText($condidature->getMotifRefus(), 145)
                                            : excerptText($condidature->getMessageMotivation(), 145);
                                        ?>
                                        <article
                                            class="candidature-card<?php echo htmlspecialchars(candidatureCardToneClass($statusKey, $isOutdated)); ?>"
                                            data-candidature-id="<?php echo (int) $condidature->getIdCandidature(); ?>"
                                            data-card-href="details.php?idCandidature=<?php echo (int) $condidature->getIdCandidature(); ?>"
                                        >
                                            <div class="candidature-card-top">
                                                <div>
                                                    <div class="offer-flag-row">
                                                        <span class="origin-badge"><?php echo htmlspecialchars(translateOrigin($source['origin'])); ?></span>
                                                        <span class="candidature-badge <?php echo htmlspecialchars($isOutdated ? 'status-outdated' : candidatureBadgeClass($condidature->getStatutCandidature())); ?>">
                                                            <?php echo htmlspecialchars($isOutdated ? 'Outdated' : $condidature->getDisplayStatusLabel()); ?>
                                                        </span>
                                                        <?php if ($isSavedForLater): ?>
                                                            <span class="saved-badge">Saved</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <h2 class="candidature-card-title"><?php echo htmlspecialchars($source['title']); ?></h2>
                                                    <p class="candidature-card-copy">
                                                        <?php
                                                        echo htmlspecialchars($responsePreview !== '' ? $responsePreview : 'No creator response note has been added yet.');
                                                        ?>
                                                    </p>
                                                </div>
                                                <form method="post" action="index.php<?php echo !empty($_SERVER['QUERY_STRING']) ? '?' . htmlspecialchars($_SERVER['QUERY_STRING']) : ''; ?>" class="m-0" data-candidature-save-toggle-form>
                                                    <input type="hidden" name="idCandidature" value="<?php echo (int) $condidature->getIdCandidature(); ?>">
                                                    <button type="submit" name="toggleSaved" class="saved-toggle <?php echo $isSavedForLater ? 'saved' : ''; ?>">
                                                        <?php echo $isSavedForLater ? 'Saved' : 'Save for later'; ?>
                                                    </button>
                                                </form>
                                            </div>

                                            <div class="candidature-inline-meta">
                                                <span class="offer-chip"><?php echo htmlspecialchars(formatMoney($condidature->getBudgetPropose())); ?></span>
                                                <span class="offer-chip"><?php echo htmlspecialchars((int) $condidature->getDelaiPropose()); ?> day<?php echo (int) $condidature->getDelaiPropose() === 1 ? '' : 's'; ?></span>
                                                <?php if ($condidature->getResponseMode() !== 'decline'): ?>
                                                    <span class="offer-chip">Available: <?php echo htmlspecialchars($availabilityLabel); ?></span>
                                                <?php endif; ?>
                                                <span class="offer-chip">Submitted: <?php echo htmlspecialchars(formatDateLabel($condidature->getDateCandidature())); ?></span>
                                                <span class="offer-chip">Updated: <?php echo htmlspecialchars(formatDateLabel($condidature->getDateDerniereModification(), 'Not updated')); ?></span>
                                            </div>

                                            <div class="offer-detail-list">
                                                <div class="offer-detail-item">
                                                    <strong>Brand</strong>
                                                    <span><?php echo htmlspecialchars($brand['nom'] ?: 'Unknown brand'); ?></span>
                                                    <p><?php echo htmlspecialchars($brand['email']); ?></p>
                                                </div>
                                                <div class="offer-detail-item">
                                                    <strong>Offer context</strong>
                                                    <span><?php echo htmlspecialchars(excerptText($source['objective'], 100) ?: 'No source objective was added.'); ?></span>
                                                </div>
                                                <div class="offer-detail-item">
                                                    <strong><?php echo $condidature->getResponseMode() === 'decline' ? 'Refusal reason' : 'Creator support'; ?></strong>
                                                    <span>
                                                        <?php
                                                        if ($condidature->getResponseMode() === 'decline') {
                                                            echo htmlspecialchars($refusalPreview !== '' ? $refusalPreview : 'No refusal reason was attached.');
                                                        } else {
                                                            echo htmlspecialchars($termsPreview !== '' ? $termsPreview : 'No creator terms were attached.');
                                                        }
                                                        ?>
                                                    </span>
                                                    <p>
                                                        <?php
                                                        if ($condidature->getResponseMode() === 'decline') {
                                                            echo htmlspecialchars($notePreview !== '' ? $notePreview : 'No extra creator note was attached.');
                                                        } else {
                                                            echo htmlspecialchars($portfolioPreview !== '' ? $portfolioPreview : 'No portfolio link was attached.');
                                                        }
                                                        ?>
                                                    </p>
                                                </div>
                                            </div>

                                            <?php if ($statusKey === 'acceptee' && $decisionNotePreview !== ''): ?>
                                                <div class="response-callout<?php echo htmlspecialchars(candidatureSignalToneClass($statusKey)); ?>">
                                                    <strong>Final decision</strong>
                                                    <div class="mt-2 candidature-signal-copy"><?php echo htmlspecialchars(excerptText($decisionNotePreview, 160)); ?></div>
                                                </div>
                                            <?php elseif ($brandSignalMessage !== ''): ?>
                                                <div class="response-callout<?php echo htmlspecialchars(candidatureSignalToneClass($statusKey)); ?>">
                                                    <strong>Latest brand update</strong>
                                                    <div class="mt-2 candidature-signal-copy"><?php echo htmlspecialchars(excerptText($brandSignalMessage, 160)); ?></div>
                                                    <div class="candidature-inline-meta mt-2">
                                                        <?php if (!empty($latestBrandSignal['dateMessage'])): ?>
                                                            <span class="offer-chip"><?php echo htmlspecialchars(formatDateLabel($latestBrandSignal['dateMessage'])); ?></span>
                                                        <?php endif; ?>
                                                        <?php if ($latestBrandSignal['budgetPropose'] !== null): ?>
                                                            <span class="offer-chip"><?php echo htmlspecialchars(formatMoney($latestBrandSignal['budgetPropose'])); ?></span>
                                                        <?php endif; ?>
                                                        <?php if ($latestBrandSignal['delaiPropose'] !== null): ?>
                                                            <span class="offer-chip">Timeline: <?php echo (int) $latestBrandSignal['delaiPropose']; ?> days</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php elseif ($decisionNotePreview !== ''): ?>
                                                <div class="response-callout<?php echo htmlspecialchars(candidatureSignalToneClass($statusKey)); ?>">
                                                    <strong>Decision note</strong>
                                                    <div class="mt-2 candidature-signal-copy"><?php echo htmlspecialchars(excerptText($decisionNotePreview, 160)); ?></div>
                                                </div>
                                            <?php endif; ?>

                                            <div class="compact-actions">
                                                <?php if ($source['origin'] === 'par_offre'): ?>
                                                    <a class="btn btn-outline-secondary" href="../offre/creator_details.php?idOffre=<?php echo (int) $source['id']; ?>&idCreateur=<?php echo (int) $creatorId; ?>">View source offer</a>
                                                <?php endif; ?>
                                                <?php if ($isEditable): ?>
                                                    <span class="offer-chip">Editable now</span>
                                                <?php endif; ?>
                                            </div>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="note-block offer-section-empty">
                                    <strong><?php echo htmlspecialchars($section['emptyTitle']); ?></strong>
                                    <p><?php echo htmlspecialchars($section['emptyCopy']); ?></p>
                                </div>
                            <?php endif; ?>
                        </section>
                    <?php endforeach; ?>
                </div>
            </section>
            <nav class="front-pagination" aria-label="Candidature pages">
                <span>Page <?php echo $page; ?> · Showing up to <?php echo $perPage; ?> filtered candidatures</span>
                <div>
                    <?php if ($prevPageUrl !== ''): ?>
                        <a class="btn btn-outline-secondary" href="<?php echo htmlspecialchars($prevPageUrl); ?>">Previous</a>
                    <?php endif; ?>
                    <?php if ($nextPageUrl !== ''): ?>
                        <a class="btn btn-primary" href="<?php echo htmlspecialchars($nextPageUrl); ?>">Load more</a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
    <script src="../offre/offre-tabs.js?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../offre/offre-tabs.js')); ?>"></script>
    <script src="candidature-list-live.js?v=<?php echo urlencode((string) filemtime(__DIR__ . '/candidature-list-live.js')); ?>"></script>
<?php
$cre8PilotContext = [
    'page' => 'creator_candidature_workspace',
    'mode' => 'list',
    'role' => 'createur',
    'allowedActions' => ['normal_chat', 'summarize_page', 'analyze_page', 'apply_filters', 'recommend_next_action', 'security_check'],
    'formTarget' => 'filter_form',
    'visibleEntityType' => 'candidature',
];
require __DIR__ . '/cre8pilot_widget.php';
?>
    <script src="../layout/front-header.js?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../layout/front-header.js')); ?>"></script>
</body>
</html>
