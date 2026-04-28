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
        'par_campagne' => 'Campaign response',
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

function getSavedBrandCandidatureIds($brandId)
{
    $savedByUser = $_SESSION['saved_candidature_ids']['marque'] ?? [];

    return array_values(array_unique(array_map('intval', $savedByUser[(int) $brandId] ?? [])));
}

function storeSavedBrandCandidatureIds($brandId, array $ids)
{
    if (!isset($_SESSION['saved_candidature_ids']) || !is_array($_SESSION['saved_candidature_ids'])) {
        $_SESSION['saved_candidature_ids'] = [];
    }

    if (!isset($_SESSION['saved_candidature_ids']['marque']) || !is_array($_SESSION['saved_candidature_ids']['marque'])) {
        $_SESSION['saved_candidature_ids']['marque'] = [];
    }

    $_SESSION['saved_candidature_ids']['marque'][(int) $brandId] = array_values(array_unique(array_map('intval', $ids)));
}

function candidatureTabKey($status, array $context = null)
{
    if ($context !== null && isContextOutdated($context)) {
        return 'outdated';
    }

    return match ((string) $status) {
        'brouillon' => 'saved',
        'acceptee' => 'accepted',
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
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $brandId && isset($_POST['toggleSaved'], $_POST['idCandidature']) && is_numeric($_POST['idCandidature'])) {
    $idCandidature = (int) $_POST['idCandidature'];
    $savedCandidatures = getSavedBrandCandidatureIds($brandId);

    if (in_array($idCandidature, $savedCandidatures, true)) {
        $savedCandidatures = array_values(array_filter($savedCandidatures, static fn($id) => (int) $id !== $idCandidature));
    } else {
        $savedCandidatures[] = $idCandidature;
    }

    storeSavedBrandCandidatureIds($brandId, $savedCandidatures);

    if (!$isAjaxRequest) {
        $redirect = 'brand_index.php';
        if (!empty($_SERVER['QUERY_STRING'])) {
            $redirect .= '?' . $_SERVER['QUERY_STRING'];
        }

        header('Location: ' . $redirect);
        exit;
    }
}

$allContexts = [];
$contexts = [];
$summary = [
    'total' => 0,
    'envoyee' => 0,
    'en_etude' => 0,
    'negociation' => 0,
    'acceptee' => 0,
    'refusee' => 0,
    'retiree' => 0,
    'negotiationMessages' => 0,
];

if ($brandId) {
    try {
        $allContexts = $controller->getBrandCandidatures($brandId);
        $contexts = $controller->getBrandCandidatures($brandId, $filters);
        $summary = $controller->summarizeContexts($allContexts);
    } catch (Throwable $exception) {
        $error = 'The brand candidature workspace could not be loaded right now.';
    }
} else {
    $error = 'No brand profile is available for this workspace.';
}

$awaitingDecision = (int) ($summary['envoyee'] ?? 0) + (int) ($summary['en_etude'] ?? 0);
$finalCount = (int) ($summary['acceptee'] ?? 0) + (int) ($summary['refusee'] ?? 0) + (int) ($summary['retiree'] ?? 0);
$activeFilterCount = count(array_filter($filters, static fn($value) => $value !== ''));
$savedCandidatureIds = $brandId ? getSavedBrandCandidatureIds($brandId) : [];

$brandSectionBuckets = [
    'waiting' => [],
    'accepted' => [],
    'refused' => [],
    'outdated' => [],
    'saved' => [],
];

foreach ($contexts as $context) {
    $statusKey = (string) $context['condidature']->getStatutCandidature();
    $brandSectionBuckets[candidatureTabKey($statusKey, $context)][] = $context;
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

if ($brandId && $validSavedCandidatureIds !== $savedCandidatureIds) {
    storeSavedBrandCandidatureIds($brandId, $validSavedCandidatureIds);
    $savedCandidatureIds = $validSavedCandidatureIds;
}

$brandSections = [
    [
        'key' => 'waiting',
        'title' => 'Waiting',
        'subtitle' => 'Responses that still need negotiation handling, review, or a final brand decision.',
        'themeClass' => 'section-waiting',
        'emptyTitle' => 'No waiting responses',
        'emptyCopy' => 'Nothing is currently waiting for the brand in this filtered view.',
        'cards' => $brandSectionBuckets['waiting'],
    ],
    [
        'key' => 'accepted',
        'title' => 'Accepted',
        'subtitle' => 'Responses where the latest collaboration terms were fully accepted.',
        'themeClass' => 'section-accepted',
        'emptyTitle' => 'No accepted responses',
        'emptyCopy' => 'No candidature has been accepted in this filtered view yet.',
        'cards' => $brandSectionBuckets['accepted'],
    ],
    [
        'key' => 'refused',
        'title' => 'Refused',
        'subtitle' => 'Responses that ended with a refusal or a declined invitation outcome.',
        'themeClass' => 'section-declined',
        'emptyTitle' => 'No refused responses',
        'emptyCopy' => 'No refused or declined candidature is visible in this filtered view.',
        'cards' => $brandSectionBuckets['refused'],
    ],
    [
        'key' => 'outdated',
        'title' => 'Outdated',
        'subtitle' => 'Responses linked to sources whose deadline has already passed without a final outcome.',
        'themeClass' => 'section-outdated',
        'emptyTitle' => 'No outdated responses',
        'emptyCopy' => 'No response is past its source deadline in this filtered view.',
        'cards' => $brandSectionBuckets['outdated'],
    ],
    [
        'key' => 'saved',
        'title' => 'Saved',
        'subtitle' => 'Draft responses and bookmarked candidatures you want to keep close in the brand workspace.',
        'themeClass' => 'section-draft-pending',
        'emptyTitle' => 'No saved candidatures',
        'emptyCopy' => 'No draft or bookmarked candidature is visible for this brand right now.',
        'cards' => $savedContexts,
    ],
];

$brandDefaultSectionKey = 'waiting';
$requestedBrandTab = statusFilterToCandidatureTab($filters['status']);
if ($requestedBrandTab !== '') {
    $brandDefaultSectionKey = $requestedBrandTab;
} else {
    foreach ($brandSections as $section) {
        if (!empty($section['cards'])) {
            $brandDefaultSectionKey = $section['key'];
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Brand Candidatures - Cre8Connect</title>
    <link rel="stylesheet" href="../css/frontoffice.css">
    <link rel="stylesheet" href="../offre/offre.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../offre/offre.css')); ?>">
    <link rel="stylesheet" href="condidature.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/condidature.css')); ?>">
</head>
<body>
    <?php require_once dirname(__DIR__) . '/layout/header.php'; ?>
    <main class="container py-5">
        <div class="offre-page-shell" data-candidature-live-region>
            <section class="module-hero">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div>
                        <span class="module-eyebrow">Brand candidature workspace</span>
                        <h1 class="display-5 fw-bold mt-3 mb-2 gradient-title">Creator responses and negotiations</h1>
                        <p class="lead text-muted mb-0">
                            Review offer and campaign responses, follow negotiation threads, and keep every creator reply visible from one brand workspace.
                        </p>
                    </div>
                    <?php if ($brandUser): ?>
                        <div class="note-block">
                            <strong><?php echo htmlspecialchars($brandUser['nom']); ?></strong>
                            <p><?php echo htmlspecialchars($brandUser['email']); ?></p>
                        </div>
                    <?php endif; ?>
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
                    <span class="stat-label">Total responses</span>
                    <span class="stat-value"><?php echo (int) ($summary['total'] ?? 0); ?></span>
                    <span class="stat-note">All creator candidatures in this brand view</span>
                </article>
                <article class="stat-card">
                    <span class="stat-label">Awaiting decision</span>
                    <span class="stat-value"><?php echo $awaitingDecision; ?></span>
                    <span class="stat-note">Accepted responses still moving through review</span>
                </article>
                <article class="stat-card">
                    <span class="stat-label">Negotiation live</span>
                    <span class="stat-value"><?php echo (int) ($summary['negociation'] ?? 0); ?></span>
                    <span class="stat-note">Threads that currently need negotiation handling</span>
                </article>
                <article class="stat-card">
                    <span class="stat-label">Final outcomes</span>
                    <span class="stat-value"><?php echo $finalCount; ?></span>
                    <span class="stat-note">Approved, refused, or declined responses</span>
                </article>
                <article class="stat-card">
                    <span class="stat-label">History messages</span>
                    <span class="stat-value"><?php echo (int) ($summary['negotiationMessages'] ?? 0); ?></span>
                    <span class="stat-note">Stored across all visible negotiation threads</span>
                </article>
            </section>

            <section class="filter-card">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div>
                        <h2 class="section-title">Filter the response workspace</h2>
                        <p class="section-subtitle">Search by creator, source title, status, or keep only one origin in view.</p>
                    </div>
                    <?php if ($activeFilterCount > 0): ?>
                        <span class="offer-chip"><?php echo $activeFilterCount; ?> active filter<?php echo $activeFilterCount > 1 ? 's' : ''; ?></span>
                    <?php endif; ?>
                </div>
                <form method="get" action="brand_index.php" class="filter-stack mt-4">
                    <div class="filter-grid">
                        <div>
                            <label for="keyword" class="form-label fw-semibold">Keyword</label>
                            <input type="text" class="form-control" id="keyword" name="keyword" value="<?php echo htmlspecialchars($filters['keyword']); ?>" placeholder="Creator, source, message...">
                        </div>
                        <div>
                            <label for="status" class="form-label fw-semibold">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All statuses</option>
                                <option value="envoyee"<?php echo $filters['status'] === 'envoyee' ? ' selected' : ''; ?>>Accepted invitation</option>
                                <option value="en_etude"<?php echo $filters['status'] === 'en_etude' ? ' selected' : ''; ?>>Under review</option>
                                <option value="negociation"<?php echo $filters['status'] === 'negociation' ? ' selected' : ''; ?>>Negotiation requested</option>
                                <option value="acceptee"<?php echo $filters['status'] === 'acceptee' ? ' selected' : ''; ?>>Accepted terms</option>
                                <option value="refusee"<?php echo $filters['status'] === 'refusee' ? ' selected' : ''; ?>>Refused by brand</option>
                                <option value="retiree"<?php echo $filters['status'] === 'retiree' ? ' selected' : ''; ?>>Declined invitation</option>
                            </select>
                        </div>
                        <div>
                            <label for="origin" class="form-label fw-semibold">Origin</label>
                            <select class="form-select" id="origin" name="origin">
                                <option value="">All origins</option>
                                <option value="par_offre"<?php echo $filters['origin'] === 'par_offre' ? ' selected' : ''; ?>>Offer invitation</option>
                                <option value="par_campagne"<?php echo $filters['origin'] === 'par_campagne' ? ' selected' : ''; ?>>Campaign response</option>
                            </select>
                        </div>
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary">Apply filters</button>
                        <a class="btn btn-outline-secondary" href="brand_index.php">Reset</a>
                    </div>
                </form>
            </section>

            <section class="offer-tab-shell" data-offer-tab-shell data-default-tab="<?php echo htmlspecialchars($brandDefaultSectionKey); ?>">
                <div class="offer-tab-list" role="tablist" aria-label="Brand candidature tabs">
                    <?php foreach ($brandSections as $section): ?>
                        <?php $isActiveTab = $section['key'] === $brandDefaultSectionKey; ?>
                        <button
                            type="button"
                            class="offer-tab-button<?php echo $isActiveTab ? ' is-active' : ''; ?>"
                            id="brand-candidature-tab-<?php echo htmlspecialchars($section['key']); ?>"
                            role="tab"
                            aria-selected="<?php echo $isActiveTab ? 'true' : 'false'; ?>"
                            aria-controls="brand-candidature-panel-<?php echo htmlspecialchars($section['key']); ?>"
                            data-offer-tab="<?php echo htmlspecialchars($section['key']); ?>"
                        >
                            <span class="offer-tab-label"><?php echo htmlspecialchars($section['title']); ?></span>
                            <span class="offer-tab-badge"><?php echo count($section['cards']); ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>

                <div class="offer-tab-panels">
                    <?php foreach ($brandSections as $section): ?>
                        <?php $isActivePanel = $section['key'] === $brandDefaultSectionKey; ?>
                        <section
                            class="section-card offer-section-card <?php echo htmlspecialchars($section['themeClass']); ?> offer-tab-panel"
                            id="brand-candidature-panel-<?php echo htmlspecialchars($section['key']); ?>"
                            role="tabpanel"
                            aria-labelledby="brand-candidature-tab-<?php echo htmlspecialchars($section['key']); ?>"
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
                                        $creator = $context['creator'];
                                        $isSavedForLater = in_array((int) $condidature->getIdCandidature(), $savedCandidatureIds, true);
                                        $statusKey = (string) $condidature->getStatutCandidature();
                                        $isOutdated = isContextOutdated($context);
                                        $latestCreatorSignal = $context['negotiation']['latestCreator'] ?? null;
                                        $creatorSignalMessage = trim((string) ($latestCreatorSignal['message'] ?? '')) !== ''
                                            ? (string) $latestCreatorSignal['message']
                                            : (trim((string) $condidature->getMessageMotivation()) !== '' ? (string) $condidature->getMessageMotivation() : '');
                                        $decisionNotePreview = trim((string) $condidature->getNoteDecision());
                                        ?>
                                        <article
                                            class="candidature-card<?php echo htmlspecialchars(candidatureCardToneClass($statusKey, $isOutdated)); ?>"
                                            data-card-href="brand_details.php?idCandidature=<?php echo (int) $condidature->getIdCandidature(); ?>"
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
                                                        <?php echo htmlspecialchars(excerptText($source['description'] ?: $source['objective'], 145) ?: 'No source summary was added for this candidature.'); ?>
                                                    </p>
                                                </div>
                                                <form method="post" action="brand_index.php<?php echo !empty($_SERVER['QUERY_STRING']) ? '?' . htmlspecialchars($_SERVER['QUERY_STRING']) : ''; ?>" class="m-0" data-candidature-save-toggle-form>
                                                    <input type="hidden" name="idCandidature" value="<?php echo (int) $condidature->getIdCandidature(); ?>">
                                                    <button type="submit" name="toggleSaved" class="saved-toggle <?php echo $isSavedForLater ? 'saved' : ''; ?>">
                                                        <?php echo $isSavedForLater ? 'Saved' : 'Save for later'; ?>
                                                    </button>
                                                </form>
                                            </div>

                                            <div class="candidature-inline-meta">
                                                <span class="offer-chip"><?php echo htmlspecialchars(formatMoney($condidature->getBudgetPropose())); ?></span>
                                                <span class="offer-chip"><?php echo (int) $condidature->getDelaiPropose(); ?> days</span>
                                                <span class="offer-chip"><?php echo (int) ($context['negotiation']['count'] ?? 0); ?> message<?php echo (int) ($context['negotiation']['count'] ?? 0) === 1 ? '' : 's'; ?></span>
                                            </div>

                                            <div class="offer-detail-list">
                                                <div class="offer-detail-item">
                                                    <strong>Creator</strong>
                                                    <span><?php echo htmlspecialchars($creator['nom'] ?: 'Unknown creator'); ?></span>
                                                    <p><?php echo htmlspecialchars($creator['email']); ?></p>
                                                </div>
                                                <div class="offer-detail-item">
                                                    <strong>Submitted</strong>
                                                    <span><?php echo htmlspecialchars(formatDateLabel($condidature->getDateCandidature())); ?></span>
                                                </div>
                                                <div class="offer-detail-item">
                                                    <strong>Last update</strong>
                                                    <span><?php echo htmlspecialchars(formatDateLabel($condidature->getDateDerniereModification(), 'Not updated')); ?></span>
                                                </div>
                                            </div>

                                            <?php if ($statusKey === 'acceptee' && $decisionNotePreview !== ''): ?>
                                                <div class="response-callout<?php echo htmlspecialchars(candidatureSignalToneClass($statusKey)); ?>">
                                                    <strong>Final decision</strong>
                                                    <div class="mt-2 candidature-signal-copy"><?php echo htmlspecialchars(excerptText($decisionNotePreview, 150)); ?></div>
                                                </div>
                                            <?php elseif ($creatorSignalMessage !== ''): ?>
                                                <div class="response-callout<?php echo htmlspecialchars(candidatureSignalToneClass($statusKey)); ?>">
                                                    <strong>Latest creator signal</strong>
                                                    <div class="mt-2 candidature-signal-copy"><?php echo htmlspecialchars(excerptText($creatorSignalMessage, 150)); ?></div>
                                                    <div class="candidature-inline-meta mt-2">
                                                        <?php if ($latestCreatorSignal && !empty($latestCreatorSignal['dateMessage'])): ?>
                                                            <span class="offer-chip"><?php echo htmlspecialchars(formatDateLabel($latestCreatorSignal['dateMessage'])); ?></span>
                                                        <?php endif; ?>
                                                        <?php if ($latestCreatorSignal && $latestCreatorSignal['budgetPropose'] !== null): ?>
                                                            <span class="offer-chip"><?php echo htmlspecialchars(formatMoney($latestCreatorSignal['budgetPropose'])); ?></span>
                                                        <?php endif; ?>
                                                        <?php if ($latestCreatorSignal && $latestCreatorSignal['delaiPropose'] !== null): ?>
                                                            <span class="offer-chip">Timeline: <?php echo (int) $latestCreatorSignal['delaiPropose']; ?> days</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <div class="compact-actions">
                                                <?php if ($source['origin'] === 'par_offre'): ?>
                                                    <a class="btn btn-outline-secondary" href="../offre/brand_details.php?idOffre=<?php echo (int) $source['id']; ?>">View source offer</a>
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
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
    <script src="../offre/offre-tabs.js?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../offre/offre-tabs.js')); ?>"></script>
    <script src="candidature-list-live.js?v=<?php echo urlencode((string) filemtime(__DIR__ . '/candidature-list-live.js')); ?>"></script>
</body>
</html>
