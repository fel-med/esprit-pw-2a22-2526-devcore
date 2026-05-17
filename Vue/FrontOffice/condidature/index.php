<?php
session_start();
$frontActive = 'collaborations';

require_once __DIR__ . '/../layout/avatar_helper.php';
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
<link rel="icon" type="image/png" sizes="16x16" href="../../public/images/favicon-16.png">
<link rel="icon" type="image/png" sizes="32x32" href="../../public/images/favicon-32.png">
<link rel="shortcut icon" type="image/png" href="../../public/images/favicon-32.png">
<link rel="apple-touch-icon" sizes="180x180" href="../../public/images/apple-touch-icon.png">

    <style>
        /* Hide stale page-level notification widgets in this page body.
           The real global notification bell remains visible in the shared header. */
        main .notification-widget,
        main .notification-widget-front,
        main .notification-bell,
        .offre-page-shell .notification-widget,
        .offre-page-shell .notification-widget-front,
        .offre-page-shell .notification-bell,
        body > .notification-widget,
        body > .notification-widget-front,
        body > .notification-bell {
            display: none !important;
            visibility: hidden !important;
            pointer-events: none !important;
        }

        .front-nav .notification-widget,
        .front-nav .notification-widget-front {
            display: inline-flex !important;
            visibility: visible !important;
            pointer-events: auto !important;
        }

        .front-nav .notification-bell {
            display: inline-flex !important;
            visibility: visible !important;
            pointer-events: auto !important;
        }
    </style>
</head>
<body>
    <?php require_once dirname(__DIR__) . '/layout/header.php'; ?>
    <main class="container py-5">
        <div class="offre-page-shell" data-candidature-live-region>
            <section class="module-hero">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div>
                        <span class="module-eyebrow" data-i18n="cand.creatorWorkspace">Creator application workspace</span>
                        <h1 class="display-5 fw-bold mt-3 mb-2 gradient-title" data-i18n="cand.myCandidatures">My applications</h1>
                        <p class="lead text-muted mb-0" data-i18n="cand.heroCopy">
                            Track every response you sent, keep drafts moving, and follow each targeted collaboration from first reply to final decision.
                        </p>
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
                    <span class="stat-label" data-i18n="cand.draftApplications">Draft applications</span>
                    <span class="stat-value"><?php echo (int) ($creatorMetrics['draftApplications'] ?? 0); ?></span>
                    <span class="stat-note" data-i18n="cand.savedFinish">Saved applications to finish</span>
                </article>
                <article class="stat-card">
                    <span class="stat-label" data-i18n="cand.acceptedCollaborations">Accepted collaborations</span>
                    <span class="stat-value"><?php echo (int) ($creatorMetrics['acceptedCollaborations'] ?? 0); ?></span>
                    <span class="stat-note"><?php echo (int) ($creatorMetrics['applicationsWaitingDecision'] ?? 0); ?> <span data-i18n="cand.waitingDecision">waiting decision</span></span>
                </article>
            </section>

            <section class="section-card">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div>
                        <h2 class="section-title">Offers ready for your response</h2>
                        <p class="section-subtitle">Start a candidature from the targeted invitations currently visible in your creator inbox.</p>
                    </div>
                    <div class="compact-actions">
                        <a class="btn btn-outline-secondary" href="../campagne/indexC.php" data-i18n="cand.browseCampaigns">Browse campaign opportunities</a>
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
                                    <div class="offer-chip">
                                        <?php echo cre8_render_avatar($brand['id'] ?? 0, (string) ($brand['nom'] ?? 'Brand'), 'cre8-avatar-sm'); ?>
                                        Brand: <?php echo htmlspecialchars($brand['nom'] ?: 'Unknown brand'); ?>
                                    </div>
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
                        <h2 class="section-title" data-i18n="cand.filterWorkspace">Filter your applications</h2>
                        <p class="section-subtitle" data-i18n="cand.filterCopy">Search by source, message, status, or keep only the responses that still need your attention.</p>
                    </div>
                    <?php if ($activeFilterCount > 0): ?>
                        <span class="offer-chip"><?php echo $activeFilterCount; ?> active filter<?php echo $activeFilterCount > 1 ? 's' : ''; ?></span>
                    <?php endif; ?>
                </div>
                <form method="get" action="index.php" class="filter-stack mt-4">
                    <div class="filter-grid">
                        <div>
                            <label for="keyword" class="form-label fw-semibold" data-i18n="cand.keyword">Keyword</label>
                            <input type="text" class="form-control" id="keyword" name="keyword" value="<?php echo htmlspecialchars($filters['keyword']); ?>" placeholder="Source title, message, brand..." data-i18n-placeholder="cand.keywordPlaceholder">
                        </div>
                        <div>
                            <label for="status" class="form-label fw-semibold" data-i18n="cand.status">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="" data-i18n="cand.allStatuses">All statuses</option>
                                <option value="brouillon"<?php echo $filters['status'] === 'brouillon' ? ' selected' : ''; ?> data-i18n="cand.statusDraft">Draft</option>
                                <option value="envoyee"<?php echo $filters['status'] === 'envoyee' ? ' selected' : ''; ?> data-i18n="cand.statusSent">Sent</option>
                                <option value="en_etude"<?php echo $filters['status'] === 'en_etude' ? ' selected' : ''; ?> data-i18n="cand.statusReview">Under review</option>
                                <option value="negociation"<?php echo $filters['status'] === 'negociation' ? ' selected' : ''; ?> data-i18n="cand.statusNegotiation">Negotiation</option>
                                <option value="acceptee"<?php echo $filters['status'] === 'acceptee' ? ' selected' : ''; ?> data-i18n="cand.accepted">Accepted</option>
                                <option value="refusee"<?php echo $filters['status'] === 'refusee' ? ' selected' : ''; ?> data-i18n="cand.refused">Refused</option>
                                <option value="retiree"<?php echo $filters['status'] === 'retiree' ? ' selected' : ''; ?> data-i18n="cand.statusWithdrawn">Withdrawn</option>
                            </select>
                        </div>
                        <div>
                            <label for="origin" class="form-label fw-semibold" data-i18n="cand.origin">Origin</label>
                            <select class="form-select" id="origin" name="origin">
                                <option value="" data-i18n="cand.allOrigins">All origins</option>
                                <option value="par_offre"<?php echo $filters['origin'] === 'par_offre' ? ' selected' : ''; ?> data-i18n="cand.offerInvitation">Offer invitation</option>
                                <option value="par_campagne"<?php echo $filters['origin'] === 'par_campagne' ? ' selected' : ''; ?> data-i18n="cand.campaignApplication">Campaign application</option>
                            </select>
                        </div>
                        <div>
                            <label for="typeReponse" class="form-label fw-semibold" data-i18n="cand.responseType">Response type</label>
                            <select class="form-select" id="typeReponse" name="typeReponse">
                                <option value="" data-i18n="cand.allTypes">All types</option>
                                <option value="application"<?php echo $filters['typeReponse'] === 'application' ? ' selected' : ''; ?> data-i18n="cand.typeApplication">Application</option>
                                <option value="acceptation"<?php echo $filters['typeReponse'] === 'acceptation' ? ' selected' : ''; ?> data-i18n="cand.typeAcceptance">Acceptance</option>
                                <option value="negociation"<?php echo $filters['typeReponse'] === 'negociation' ? ' selected' : ''; ?> data-i18n="cand.statusNegotiation">Negotiation</option>
                                <option value="refus"<?php echo $filters['typeReponse'] === 'refus' ? ' selected' : ''; ?> data-i18n="cand.typeRefusal">Refusal</option>
                            </select>
                        </div>
                        <div>
                            <label for="dateFrom" class="form-label fw-semibold" data-i18n="cand.submittedFrom">Submitted from</label>
                            <input type="date" class="form-control" id="dateFrom" name="dateFrom" value="<?php echo htmlspecialchars($filters['dateFrom']); ?>">
                        </div>
                        <div>
                            <label for="dateTo" class="form-label fw-semibold" data-i18n="cand.submittedTo">Submitted to</label>
                            <input type="date" class="form-control" id="dateTo" name="dateTo" value="<?php echo htmlspecialchars($filters['dateTo']); ?>">
                        </div>
                        <div>
                            <label for="hasCv" class="form-label fw-semibold" data-i18n="cand.cvFile">CV file</label>
                            <select class="form-select" id="hasCv" name="hasCv">
                                <option value="" data-i18n="cand.all">All</option>
                                <option value="1"<?php echo $filters['hasCv'] === '1' ? ' selected' : ''; ?> data-i18n="cand.hasCv">Has CV</option>
                                <option value="0"<?php echo $filters['hasCv'] === '0' ? ' selected' : ''; ?> data-i18n="cand.noCv">No CV</option>
                            </select>
                        </div>
                        <div>
                            <label for="hasPortfolio" class="form-label fw-semibold" data-i18n="cand.portfolio">Portfolio</label>
                            <select class="form-select" id="hasPortfolio" name="hasPortfolio">
                                <option value="" data-i18n="cand.all">All</option>
                                <option value="1"<?php echo $filters['hasPortfolio'] === '1' ? ' selected' : ''; ?> data-i18n="cand.hasPortfolio">Has portfolio</option>
                                <option value="0"<?php echo $filters['hasPortfolio'] === '0' ? ' selected' : ''; ?> data-i18n="cand.noPortfolio">No portfolio</option>
                            </select>
                        </div>
                        <div>
                            <label for="sort" class="form-label fw-semibold" data-i18n="cand.sort">Sort</label>
                            <select class="form-select" id="sort" name="sort">
                                <option value=""<?php echo $filters['sort'] === '' ? ' selected' : ''; ?> data-i18n="cand.sortWorkflow">Workflow priority</option>
                                <option value="newest"<?php echo $filters['sort'] === 'newest' ? ' selected' : ''; ?> data-i18n="cand.sortNewest">Newest</option>
                                <option value="oldest"<?php echo $filters['sort'] === 'oldest' ? ' selected' : ''; ?> data-i18n="cand.sortOldest">Oldest</option>
                                <option value="budget_high"<?php echo $filters['sort'] === 'budget_high' ? ' selected' : ''; ?> data-i18n="cand.sortBudgetHigh">Budget high to low</option>
                                <option value="budget_low"<?php echo $filters['sort'] === 'budget_low' ? ' selected' : ''; ?> data-i18n="cand.sortBudgetLow">Budget low to high</option>
                                <option value="proposed_delay"<?php echo $filters['sort'] === 'proposed_delay' ? ' selected' : ''; ?> data-i18n="cand.sortDelay">Proposed delay</option>
                                <option value="decision_date"<?php echo $filters['sort'] === 'decision_date' ? ' selected' : ''; ?> data-i18n="cand.sortDecisionDate">Decision date</option>
                                <option value="status"<?php echo $filters['sort'] === 'status' ? ' selected' : ''; ?> data-i18n="cand.status">Status</option>
                            </select>
                        </div>
                        <div>
                            <label for="editableOnly" class="form-label fw-semibold" data-i18n="cand.editableOnly">Editable only</label>
                            <select class="form-select" id="editableOnly" name="editableOnly">
                                <option value="0"<?php echo !$filters['editableOnly'] ? ' selected' : ''; ?> data-i18n="cand.showAll">Show all</option>
                                <option value="1"<?php echo $filters['editableOnly'] ? ' selected' : ''; ?> data-i18n="cand.editableNow">Editable now</option>
                            </select>
                        </div>
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary" data-i18n="cand.applyFilters">Apply filters</button>
                        <a class="btn btn-outline-secondary" href="index.php" data-i18n="cand.reset">Reset</a>
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
                                                    <div style="display:flex;align-items:center;gap:.65rem;">
                                                        <?php echo cre8_render_avatar($brand['id'] ?? 0, (string) ($brand['nom'] ?? 'Brand'), 'cre8-avatar-md'); ?>
                                                        <div>
                                                            <span><?php echo htmlspecialchars($brand['nom'] ?: 'Unknown brand'); ?></span>
                                                            <p><?php echo htmlspecialchars($brand['email']); ?></p>
                                                        </div>
                                                    </div>
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
                <span><span data-i18n="cand.page">Page</span> <?php echo $page; ?> · <span data-i18n="cand.showingUpTo">Showing up to</span> <?php echo $perPage; ?> <span data-i18n="cand.filteredCandidatures">filtered applications</span></span>
                <div>
                    <?php if ($prevPageUrl !== ''): ?>
                        <a class="btn btn-outline-secondary" href="<?php echo htmlspecialchars($prevPageUrl); ?>" data-i18n="cand.previous">Previous</a>
                    <?php endif; ?>
                    <?php if ($nextPageUrl !== ''): ?>
                        <a class="btn btn-primary" href="<?php echo htmlspecialchars($nextPageUrl); ?>" data-i18n="cand.loadMore">Load more</a>
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
    <script>
        (() => {
            const translations = {
                en: {
                    'cand.creatorWorkspace': 'Creator application workspace',
                    'cand.myCandidatures': 'My applications',
                    'cand.heroCopy': 'Track every response you sent, keep drafts moving, and follow each targeted collaboration from first reply to final decision.',
                    'cand.invitationsAnswer': 'Invitations to answer',
                    'cand.activeOffersNoFinal': 'Active offers without a final response',
                    'cand.negotiationsWaiting': 'Negotiations waiting reply',
                    'cand.activeNegotiations': 'Active negotiation candidatures',
                    'cand.closestDeadline': 'Closest deadline',
                    'cand.nearestActive': 'Nearest active invitation or candidature',
                    'cand.bestBudget': 'Best proposed budget',
                    'cand.highestBudget': 'Highest active invitation budget',
                    'cand.draftApplications': 'Draft applications',
                    'cand.savedFinish': 'Saved applications to finish',
                    'cand.acceptedCollaborations': 'Accepted collaborations',
                    'cand.waitingDecision': 'waiting decision',
                    'cand.readyResponse': 'Offers ready for your response',
                    'cand.readyResponseCopy': 'Start a candidature from the targeted invitations currently visible in your creator inbox.',
                    'cand.browseCampaigns': 'Browse campaign opportunities',
                    'cand.ready': 'ready',
                    'cand.startResponse': 'Start response',
                    'cand.continueDraft': 'Continue draft',
                    'cand.updateNegotiation': 'Update negotiation',
                    'cand.filterWorkspace': 'Filter your applications',
                    'cand.filterCopy': 'Search by source, message, status, or keep only the responses that still need your attention.',
                    'cand.activeFilter': 'active filter',
                    'cand.activeFilters': 'active filters',
                    'cand.keyword': 'Keyword',
                    'cand.status': 'Status',
                    'cand.allStatuses': 'All statuses',
                    'cand.origin': 'Origin',
                    'cand.allOrigins': 'All origins',
                    'cand.responseType': 'Response type',
                    'cand.allTypes': 'All types',
                    'cand.submittedFrom': 'Submitted from',
                    'cand.submittedTo': 'Submitted to',
                    'cand.cvFile': 'CV file',
                    'cand.all': 'All',
                    'cand.hasCv': 'Has CV',
                    'cand.noCv': 'No CV',
                    'cand.portfolio': 'Portfolio',
                    'cand.hasPortfolio': 'Has portfolio',
                    'cand.noPortfolio': 'No portfolio',
                    'cand.sort': 'Sort',
                    'cand.editableOnly': 'Editable only',
                    'cand.showAll': 'Show all',
                    'cand.editableNow': 'Editable now',
                    'cand.applyFilters': 'Apply filters',
                    'cand.reset': 'Reset',
                    'cand.waiting': 'Waiting',
                    'cand.accepted': 'Accepted',
                    'cand.refused': 'Refused',
                    'cand.outdated': 'Outdated',
                    'cand.saved': 'Saved',
                    'cand.waitingSubtitle': 'Responses that are still moving through review, negotiation, or a pending brand decision.',
                    'cand.acceptedSubtitle': 'Final positive outcomes where the latest terms were fully accepted.',
                    'cand.refusedSubtitle': 'Responses that ended with a refusal or a declined invitation outcome.',
                    'cand.outdatedSubtitle': 'Responses linked to sources whose deadline has already passed without a final outcome.',
                    'cand.savedSubtitle': 'Draft responses and bookmarked candidatures you want to revisit later.',
                    'cand.noWaiting': 'No waiting candidatures',
                    'cand.noAccepted': 'No accepted candidatures',
                    'cand.noRefused': 'No refused candidatures',
                    'cand.noOutdated': 'No outdated candidatures',
                    'cand.noSaved': 'No saved candidatures',
                    'cand.brand': 'Brand',
                    'cand.unknownBrand': 'Unknown brand',
                    'cand.offerContext': 'Offer context',
                    'cand.creatorSupport': 'Creator support',
                    'cand.refusalReason': 'Refusal reason',
                    'cand.finalDecision': 'Final decision',
                    'cand.latestBrandUpdate': 'Latest brand update',
                    'cand.decisionNote': 'Decision note',
                    'cand.viewSourceOffer': 'View source offer',
                    'cand.previous': 'Previous',
                    'cand.loadMore': 'Load more',
                    'cand.offerInvitation': 'Offer invitation',
                    'cand.campaignApplication': 'Campaign application',
                    'cand.draftResponse': 'Draft response',
                    'cand.acceptedInvitation': 'Accepted invitation',
                    'cand.underReview': 'Response under review',
                    'cand.negotiationRequested': 'Negotiation requested',
                    'cand.acceptedTerms': 'Accepted terms',
                    'cand.refusedBrand': 'Refused by brand',
                    'cand.declinedInvitation': 'Declined invitation',
                    'cand.saveLater': 'Save for later',
                    'cand.keywordPlaceholder': 'Source title, message, brand...',
                    'cand.statusDraft': 'Draft',
                    'cand.statusSent': 'Sent',
                    'cand.statusReview': 'Under review',
                    'cand.statusNegotiation': 'Negotiation',
                    'cand.statusWithdrawn': 'Withdrawn',
                    'cand.typeApplication': 'Application',
                    'cand.typeAcceptance': 'Acceptance',
                    'cand.typeRefusal': 'Refusal',
                    'cand.sortWorkflow': 'Workflow priority',
                    'cand.sortNewest': 'Newest',
                    'cand.sortOldest': 'Oldest',
                    'cand.sortBudgetHigh': 'Budget high to low',
                    'cand.sortBudgetLow': 'Budget low to high',
                    'cand.sortDelay': 'Proposed delay',
                    'cand.sortDecisionDate': 'Decision date',
                    'cand.page': 'Page',
                    'cand.showingUpTo': 'Showing up to',
                    'cand.filteredCandidatures': 'filtered applications',
                    'cand.noCreatorNote': 'No creator response note has been added yet.'
                },
                fr: {
                    'cand.creatorWorkspace': 'Espace candidatures créateur',
                    'cand.myCandidatures': 'Mes candidatures',
                    'cand.heroCopy': 'Suivez chaque réponse envoyée, faites avancer les brouillons et chaque collaboration jusqu à la décision finale.',
                    'cand.invitationsAnswer': 'Invitations a traiter',
                    'cand.activeOffersNoFinal': 'Offres actives sans reponse finale',
                    'cand.negotiationsWaiting': 'Negociations en attente',
                    'cand.activeNegotiations': 'Candidatures en negociation active',
                    'cand.closestDeadline': 'Echeance la plus proche',
                    'cand.nearestActive': 'Invitation ou candidature active la plus proche',
                    'cand.bestBudget': 'Meilleur budget propose',
                    'cand.highestBudget': 'Budget actif le plus eleve',
                    'cand.draftApplications': 'Candidatures brouillon',
                    'cand.savedFinish': 'Candidatures enregistrées à finaliser',
                    'cand.acceptedCollaborations': 'Collaborations acceptees',
                    'cand.waitingDecision': 'en attente de décision',
                    'cand.readyResponse': 'Offres pretes pour votre reponse',
                    'cand.readyResponseCopy': 'Demarrez une candidature depuis les invitations ciblees visibles dans votre boite createur.',
                    'cand.browseCampaigns': 'Parcourir les opportunites de campagne',
                    'cand.ready': 'pretes',
                    'cand.startResponse': 'Commencer la reponse',
                    'cand.continueDraft': 'Continuer le brouillon',
                    'cand.updateNegotiation': 'Mettre a jour la negociation',
                    'cand.filterWorkspace': 'Filtrer vos candidatures',
                    'cand.filterCopy': 'Recherchez par source, message, statut ou gardez seulement les réponses qui demandent votre attention.',
                    'cand.activeFilter': 'filtre actif',
                    'cand.activeFilters': 'filtres actifs',
                    'cand.keyword': 'Mot-cle',
                    'cand.status': 'Statut',
                    'cand.allStatuses': 'Tous les statuts',
                    'cand.origin': 'Origine',
                    'cand.allOrigins': 'Toutes les origines',
                    'cand.responseType': 'Type de reponse',
                    'cand.allTypes': 'Tous les types',
                    'cand.submittedFrom': 'Envoyee depuis',
                    'cand.submittedTo': 'Envoyee jusqu au',
                    'cand.cvFile': 'Fichier CV',
                    'cand.all': 'Tous',
                    'cand.hasCv': 'Avec CV',
                    'cand.noCv': 'Sans CV',
                    'cand.portfolio': 'Portfolio',
                    'cand.hasPortfolio': 'Avec portfolio',
                    'cand.noPortfolio': 'Sans portfolio',
                    'cand.sort': 'Tri',
                    'cand.editableOnly': 'Modifiables seulement',
                    'cand.showAll': 'Tout afficher',
                    'cand.editableNow': 'Modifiable maintenant',
                    'cand.applyFilters': 'Appliquer les filtres',
                    'cand.reset': 'Reinitialiser',
                    'cand.waiting': 'En attente',
                    'cand.accepted': 'Acceptees',
                    'cand.refused': 'Refusees',
                    'cand.outdated': 'Expirees',
                    'cand.saved': 'Enregistrees',
                    'cand.waitingSubtitle': 'Reponses encore en examen, negociation ou en attente de decision.',
                    'cand.acceptedSubtitle': 'Resultats positifs finaux dont les derniers termes ont ete acceptes.',
                    'cand.refusedSubtitle': 'Reponses terminees par un refus ou une invitation declinee.',
                    'cand.outdatedSubtitle': 'Reponses liees a des sources dont l echeance est passee sans resultat final.',
                    'cand.savedSubtitle': 'Brouillons et candidatures marquees pour y revenir plus tard.',
                    'cand.noWaiting': 'Aucune candidature en attente',
                    'cand.noAccepted': 'Aucune candidature acceptee',
                    'cand.noRefused': 'Aucune candidature refusee',
                    'cand.noOutdated': 'Aucune candidature expiree',
                    'cand.noSaved': 'Aucune candidature enregistree',
                    'cand.brand': 'Marque',
                    'cand.unknownBrand': 'Marque inconnue',
                    'cand.offerContext': 'Contexte de l offre',
                    'cand.creatorSupport': 'Support createur',
                    'cand.refusalReason': 'Raison du refus',
                    'cand.finalDecision': 'Decision finale',
                    'cand.latestBrandUpdate': 'Derniere mise a jour marque',
                    'cand.decisionNote': 'Note de decision',
                    'cand.viewSourceOffer': 'Voir l offre source',
                    'cand.previous': 'Precedent',
                    'cand.loadMore': 'Charger plus',
                    'cand.offerInvitation': 'Invitation offre',
                    'cand.campaignApplication': 'Candidature campagne',
                    'cand.draftResponse': 'Reponse brouillon',
                    'cand.acceptedInvitation': 'Invitation acceptee',
                    'cand.underReview': 'Reponse en examen',
                    'cand.negotiationRequested': 'Negociation demandee',
                    'cand.acceptedTerms': 'Termes acceptes',
                    'cand.refusedBrand': 'Refusee par la marque',
                    'cand.declinedInvitation': 'Invitation refusee',
                    'cand.saveLater': 'Enregistrer',
                    'cand.keywordPlaceholder': 'Titre source, message, marque...',
                    'cand.statusDraft': 'Brouillon',
                    'cand.statusSent': 'Envoyée',
                    'cand.statusReview': 'En examen',
                    'cand.statusNegotiation': 'Négociation',
                    'cand.statusWithdrawn': 'Retirée',
                    'cand.typeApplication': 'Candidature',
                    'cand.typeAcceptance': 'Acceptation',
                    'cand.typeRefusal': 'Refus',
                    'cand.sortWorkflow': 'Priorité du workflow',
                    'cand.sortNewest': 'Plus récentes',
                    'cand.sortOldest': 'Plus anciennes',
                    'cand.sortBudgetHigh': 'Budget décroissant',
                    'cand.sortBudgetLow': 'Budget croissant',
                    'cand.sortDelay': 'Délai proposé',
                    'cand.sortDecisionDate': 'Date de décision',
                    'cand.page': 'Page',
                    'cand.showingUpTo': 'Affichage jusqu à',
                    'cand.filteredCandidatures': 'candidatures filtrées',
                    'cand.noCreatorNote': 'Aucune note de réponse créateur n a encore été ajoutée.'
                }
            };
            const textKeys = {
                'Creator candidature workspace': 'cand.creatorWorkspace',
                'Creator application workspace': 'cand.creatorWorkspace',
                'My applications': 'cand.myCandidatures',
                'Saved applications to finish': 'cand.savedFinish',
                'Filter your applications': 'cand.filterWorkspace',
                'Filter your candidatures': 'cand.filterWorkspace',
                'Search by source, message, status, or keep only the responses that still need your attention.': 'cand.filterCopy',
                'Source title, message, brand...': 'cand.keywordPlaceholder',
                'Draft': 'cand.statusDraft',
                'Sent': 'cand.statusSent',
                'Under review': 'cand.statusReview',
                'Negotiation': 'cand.statusNegotiation',
                'Withdrawn': 'cand.statusWithdrawn',
                'Application': 'cand.typeApplication',
                'Acceptance': 'cand.typeAcceptance',
                'Refusal': 'cand.typeRefusal',
                'Workflow priority': 'cand.sortWorkflow',
                'Newest': 'cand.sortNewest',
                'Oldest': 'cand.sortOldest',
                'Budget high to low': 'cand.sortBudgetHigh',
                'Budget low to high': 'cand.sortBudgetLow',
                'Proposed delay': 'cand.sortDelay',
                'Decision date': 'cand.sortDecisionDate',
                'filtered applications': 'cand.filteredCandidatures',

                'My candidatures': 'cand.myCandidatures',
                'Track every response you sent, keep drafts moving, and follow each targeted collaboration from first reply to final decision.': 'cand.heroCopy',
                'Invitations to answer': 'cand.invitationsAnswer',
                'Active offers without a final response': 'cand.activeOffersNoFinal',
                'Negotiations waiting reply': 'cand.negotiationsWaiting',
                'Active negotiation candidatures': 'cand.activeNegotiations',
                'Closest deadline': 'cand.closestDeadline',
                'Nearest active invitation or candidature': 'cand.nearestActive',
                'Best proposed budget': 'cand.bestBudget',
                'Highest active invitation budget': 'cand.highestBudget',
                'Draft applications': 'cand.draftApplications',
                'Saved candidatures to finish': 'cand.savedFinish',
                'Accepted collaborations': 'cand.acceptedCollaborations',
                'waiting decision': 'cand.waitingDecision',
                'Offers ready for your response': 'cand.readyResponse',
                'Start a candidature from the targeted invitations currently visible in your creator inbox.': 'cand.readyResponseCopy',
                'Browse campaign opportunities': 'cand.browseCampaigns',
                'ready': 'cand.ready',
                'Start response': 'cand.startResponse',
                'Continue draft': 'cand.continueDraft',
                'Update negotiation': 'cand.updateNegotiation',
                'Filter candidature workspace': 'cand.filterWorkspace',
                'Search by brand, source title, status, origin, or response type.': 'cand.filterCopy',
                'active filter': 'cand.activeFilter',
                'active filters': 'cand.activeFilters',
                'Keyword': 'cand.keyword',
                'Status': 'cand.status',
                'All statuses': 'cand.allStatuses',
                'Origin': 'cand.origin',
                'All origins': 'cand.allOrigins',
                'Response type': 'cand.responseType',
                'All types': 'cand.allTypes',
                'Submitted from': 'cand.submittedFrom',
                'Submitted to': 'cand.submittedTo',
                'CV file': 'cand.cvFile',
                'All': 'cand.all',
                'Has CV': 'cand.hasCv',
                'No CV': 'cand.noCv',
                'Portfolio': 'cand.portfolio',
                'Has portfolio': 'cand.hasPortfolio',
                'No portfolio': 'cand.noPortfolio',
                'Sort': 'cand.sort',
                'Editable only': 'cand.editableOnly',
                'Show all': 'cand.showAll',
                'Editable now': 'cand.editableNow',
                'Apply filters': 'cand.applyFilters',
                'Reset': 'cand.reset',
                'Waiting': 'cand.waiting',
                'Accepted': 'cand.accepted',
                'Refused': 'cand.refused',
                'Outdated': 'cand.outdated',
                'Saved': 'cand.saved',
                'Responses that are still moving through review, negotiation, or a pending brand decision.': 'cand.waitingSubtitle',
                'Final positive outcomes where the latest terms were fully accepted.': 'cand.acceptedSubtitle',
                'Responses that ended with a refusal or a declined invitation outcome.': 'cand.refusedSubtitle',
                'Responses linked to sources whose deadline has already passed without a final outcome.': 'cand.outdatedSubtitle',
                'Draft responses and bookmarked candidatures you want to revisit later.': 'cand.savedSubtitle',
                'No waiting candidatures': 'cand.noWaiting',
                'No accepted candidatures': 'cand.noAccepted',
                'No refused candidatures': 'cand.noRefused',
                'No outdated candidatures': 'cand.noOutdated',
                'No saved candidatures': 'cand.noSaved',
                'Brand': 'cand.brand',
                'Unknown brand': 'cand.unknownBrand',
                'Offer context': 'cand.offerContext',
                'Creator support': 'cand.creatorSupport',
                'Refusal reason': 'cand.refusalReason',
                'Final decision': 'cand.finalDecision',
                'Latest brand update': 'cand.latestBrandUpdate',
                'Decision note': 'cand.decisionNote',
                'View source offer': 'cand.viewSourceOffer',
                'Previous': 'cand.previous',
                'Load more': 'cand.loadMore',
                'Offer invitation': 'cand.offerInvitation',
                'Campaign application': 'cand.campaignApplication',
                'Draft response': 'cand.draftResponse',
                'Accepted invitation': 'cand.acceptedInvitation',
                'Response under review': 'cand.underReview',
                'Negotiation requested': 'cand.negotiationRequested',
                'Accepted terms': 'cand.acceptedTerms',
                'Refused by brand': 'cand.refusedBrand',
                'Declined invitation': 'cand.declinedInvitation',
                'Save for later': 'cand.saveLater',
                'No creator response note has been added yet.': 'cand.noCreatorNote'
            };
            function candLang() { return typeof window.cre8FrontReadLang === 'function' ? window.cre8FrontReadLang() : 'en'; }
            function keyForText(value) {
                const clean = String(value).trim().replace(/:$/, '');
                if (textKeys[clean]) return textKeys[clean];
                for (const locale of Object.keys(translations)) for (const key of Object.keys(translations[locale])) if (translations[locale][key] === clean) return key;
                return '';
            }
            function applyCandidatureTranslations(root = document) {
                const dict = translations[candLang()] || translations.en;
                if (typeof window.cre8ApplyI18n === 'function') window.cre8ApplyI18n(translations);
                const walker = document.createTreeWalker(root.body || root, NodeFilter.SHOW_TEXT, {
                    acceptNode(node) {
                        const parent = node.parentElement;
                        if (!parent || ['SCRIPT', 'STYLE', 'TEXTAREA'].includes(parent.tagName)) return NodeFilter.FILTER_REJECT;
                        return node.nodeValue.trim() ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT;
                    }
                });
                const nodes = [];
                while (walker.nextNode()) nodes.push(walker.currentNode);
                nodes.forEach((node) => {
                    const original = node.nodeValue.trim();
                    const key = keyForText(original);
                    if (!key || dict[key] === undefined) return;
                    const suffix = original.endsWith(':') ? ':' : '';
                    node.nodeValue = node.nodeValue.replace(original, dict[key] + suffix);
                    if (node.parentElement && node.parentElement.childNodes.length === 1) node.parentElement.setAttribute('data-i18n', key);
                });
            }
            window.cre8CandidatureApplyTranslations = applyCandidatureTranslations;
            document.addEventListener('DOMContentLoaded', () => {
                if (typeof window.cre8RegisterTranslations === 'function') window.cre8RegisterTranslations(translations);
                applyCandidatureTranslations();
            });
            window.addEventListener('cre8:languagechange', () => applyCandidatureTranslations());
            window.addEventListener('candidatureListUpdated', () => window.setTimeout(applyCandidatureTranslations, 0));
        })();
    </script>
    <script src="../layout/front-header.js?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../layout/front-header.js')); ?>"></script>
</body>
</html>
