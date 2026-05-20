    <?php
    require_once __DIR__ . '/../layout/session_bridge.php';
    $currentUser = cre8_front_require_user('marque');
    $frontActive = 'collaborations';

    require_once __DIR__ . '/../layout/avatar_helper.php';
    require_once __DIR__ . '/../../../Controleur/condidatureC.php';
    require_once __DIR__ . '/../../../Controleur/offreC.php';

    $controller = new CondidatureC();
    $offreController = new OffreC();
    $sessionUser = $currentUser;

    $brandId = isset($sessionUser['id']) ? (int) $sessionUser['id'] : null;

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

    function emptyWorkflowBuckets()
    {
        return [
            'waiting' => [],
            'accepted' => [],
            'refused' => [],
            'outdated' => [],
        ];
    }

    function bucketContextsByWorkflow(array $contexts)
    {
        $buckets = emptyWorkflowBuckets();

        foreach ($contexts as $context) {
            $statusKey = (string) $context['condidature']->getStatutCandidature();
            $workflowKey = candidatureTabKey($statusKey, $context);
            $buckets[$workflowKey][] = $context;
        }

        return $buckets;
    }

    function filterContextsByOrigin(array $contexts, $origin)
    {
        return array_values(array_filter($contexts, static function ($context) use ($origin) {
            return ($context['source']['origin'] ?? '') === $origin;
        }));
    }

    function countWorkflowBuckets(array $buckets)
    {
        return array_sum(array_map('count', $buckets));
    }

    function resolveWorkflowDefaultTab(array $workflowSections, array $buckets, $requestedTab = '')
    {
        if ($requestedTab !== '' && isset($buckets[$requestedTab])) {
            return $requestedTab;
        }

        foreach ($workflowSections as $section) {
            if (!empty($buckets[$section['key']] ?? [])) {
                return $section['key'];
            }
        }

        return 'waiting';
    }

    function renderBrandCandidatureCard(array $context, array $savedCandidatureIds)
    {
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
                    <div style="display:flex;align-items:center;gap:.65rem;">
                        <?php echo cre8_render_avatar($creator['id'] ?? 0, (string) ($creator['nom'] ?? 'Creator'), 'cre8-avatar-md'); ?>
                        <div>
                            <span><?php echo htmlspecialchars($creator['nom'] ?: 'Unknown creator'); ?></span>
                            <p><?php echo htmlspecialchars($creator['email']); ?></p>
                        </div>
                    </div>
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
        <?php
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
    ];
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $perPage = 10;
    $offset = ($page - 1) * $perPage;
    $hasNextPage = false;

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
    $filteredContexts = [];
    $contexts = [];
    $totalFilteredContexts = 0;
    $brandMetrics = [
        'responsesToReview' => 0,
        'negotiationsWaitingReply' => 0,
        'acceptedCollaborations' => 0,
        'acceptedBudgetTotal' => 0,
        'recentlyAccepted' => 0,
    ];
    $brandOfferMetrics = [
        'draftOffers' => 0,
        'expiringSoon' => 0,
    ];
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
            $filteredContexts = $controller->getBrandCandidatures($brandId, $filters);
            $totalFilteredContexts = count($filteredContexts);

            // Important: workflow tabs must be built from the full filtered result set,
            // not only from page 1. Otherwise an accepted candidature can appear in the
            // contract selector but be hidden from the Accepted/Campaign tabs here.
            $contexts = $filteredContexts;
            $hasNextPage = false;

            $summary = $controller->summarizeContexts($allContexts);
            $brandMetrics = $controller->getBrandActionMetrics($brandId);
            $brandOfferMetrics = $offreController->getBrandOfferActionMetrics($brandId);
        } catch (Throwable $exception) {
            $error = 'The brand candidature workspace could not be loaded right now.';
        }
    } else {
        $error = 'No brand profile is available for this workspace.';
    }

    $awaitingDecision = (int) ($summary['envoyee'] ?? 0) + (int) ($summary['en_etude'] ?? 0);
    $finalCount = (int) ($summary['acceptee'] ?? 0) + (int) ($summary['refusee'] ?? 0) + (int) ($summary['retiree'] ?? 0);
    $activeFilterCount = count(array_filter($filters, static fn($value) => $value !== ''));
    $paginationBase = $_GET;
    unset($paginationBase['page']);
    // The response workspace now displays the complete filtered result set inside
    // the origin/workflow tabs, so page links are intentionally disabled here.
    $prevPageUrl = '';
    $nextPageUrl = '';
    $savedCandidatureIds = $brandId ? getSavedBrandCandidatureIds($brandId) : [];

    $savedDisplayContextMap = [];
    foreach ($contexts as $context) {
        $idCandidature = (int) $context['condidature']->getIdCandidature();
        if ($context['condidature']->isDraft() || in_array($idCandidature, $savedCandidatureIds, true)) {
            $savedDisplayContextMap[$idCandidature] = $context;
        }
    }

    $savedContexts = array_values($savedDisplayContextMap);
    $validSavedCandidatureIds = array_values(array_map(
        static fn($context) => (int) $context['condidature']->getIdCandidature(),
        array_filter($allContexts, static fn($context) => in_array((int) $context['condidature']->getIdCandidature(), $savedCandidatureIds, true))
    ));

    if ($brandId && $validSavedCandidatureIds !== $savedCandidatureIds) {
        storeSavedBrandCandidatureIds($brandId, $validSavedCandidatureIds);
        $savedCandidatureIds = $validSavedCandidatureIds;
    }

    $brandWorkflowSections = [
        [
            'key' => 'waiting',
            'title' => 'Waiting',
            'subtitle' => 'Responses that still need negotiation handling, review, or a final brand decision.',
            'themeClass' => 'section-waiting',
            'emptyTitle' => 'No waiting responses',
            'emptyCopy' => 'Nothing is currently waiting for the brand in this filtered view.',
        ],
        [
            'key' => 'accepted',
            'title' => 'Accepted',
            'subtitle' => 'Responses where the latest collaboration terms were fully accepted.',
            'themeClass' => 'section-accepted',
            'emptyTitle' => 'No accepted responses',
            'emptyCopy' => 'No candidature has been accepted in this filtered view yet.',
        ],
        [
            'key' => 'refused',
            'title' => 'Refused',
            'subtitle' => 'Responses that ended with a refusal or a declined invitation outcome.',
            'themeClass' => 'section-declined',
            'emptyTitle' => 'No refused responses',
            'emptyCopy' => 'No refused or declined candidature is visible in this filtered view.',
        ],
        [
            'key' => 'outdated',
            'title' => 'Outdated',
            'subtitle' => 'Responses linked to sources whose deadline has already passed without a final outcome.',
            'themeClass' => 'section-outdated',
            'emptyTitle' => 'No outdated responses',
            'emptyCopy' => 'No response is past its source deadline in this filtered view.',
        ],
    ];

    $requestedBrandWorkflowTab = statusFilterToCandidatureTab($filters['status']);
    $brandParentGroups = [
        [
            'key' => 'all',
            'title' => 'All applications',
            'titleKey' => 'cand.allApplications',
            'subtitle' => 'Every offer response and campaign application in one view.',
            'emptyTitle' => 'No applications',
            'emptyCopy' => 'No candidature is visible in this filtered view.',
            'count' => count($allContexts),
            'buckets' => bucketContextsByWorkflow($contexts),
        ],
        [
            'key' => 'from_offre',
            'title' => 'From offers',
            'titleKey' => 'cand.fromOffers',
            'subtitle' => 'Candidatures created from targeted offer invitations.',
            'emptyTitle' => 'No offer candidatures',
            'emptyCopy' => 'No creator response from an offer is visible in this filtered view.',
            'count' => count(filterContextsByOrigin($allContexts, 'par_offre')),
            'buckets' => bucketContextsByWorkflow(filterContextsByOrigin($contexts, 'par_offre')),
        ],
        [
            'key' => 'from_campagne',
            'title' => 'From campaigns',
            'titleKey' => 'cand.fromCampaigns',
            'subtitle' => 'Applications submitted by creators to campaign opportunities.',
            'emptyTitle' => 'No campaign applications',
            'emptyCopy' => 'No creator application from a campaign is visible in this filtered view.',
            'count' => count(filterContextsByOrigin($allContexts, 'par_campagne')),
            'buckets' => bucketContextsByWorkflow(filterContextsByOrigin($contexts, 'par_campagne')),
        ],
        [
            'key' => 'saved',
            'title' => 'Saved',
            'titleKey' => 'cand.saved',
            'subtitle' => 'Draft responses and bookmarked candidatures kept close for follow-up.',
            'emptyTitle' => 'No saved candidatures',
            'emptyCopy' => 'No draft or bookmarked candidature is visible for this brand right now.',
            'count' => count($savedContexts),
            'buckets' => bucketContextsByWorkflow($savedContexts),
        ],
    ];

    foreach ($brandParentGroups as &$brandParentGroup) {
        if (!isset($brandParentGroup['count'])) {
            $brandParentGroup['count'] = countWorkflowBuckets($brandParentGroup['buckets']);
        }
        $brandParentGroup['defaultWorkflowTab'] = resolveWorkflowDefaultTab($brandWorkflowSections, $brandParentGroup['buckets'], $requestedBrandWorkflowTab);
    }
    unset($brandParentGroup);

    $brandDefaultParentKey = match ($filters['origin']) {
        'par_offre' => 'from_offre',
        'par_campagne' => 'from_campagne',
        default => 'all',
    };

    if ($filters['origin'] === '') {
        foreach ($brandParentGroups as $brandParentGroup) {
            if ((int) $brandParentGroup['count'] > 0) {
                $brandDefaultParentKey = $brandParentGroup['key'];
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
        <title>Brand Candidatures - Cre8Connect</title>
        <link rel="stylesheet" href="../css/frontoffice.css">
        <link rel="stylesheet" href="../offre/offre.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../offre/offre.css')); ?>">
        <link rel="stylesheet" href="condidature.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/condidature.css')); ?>">
        <link rel="stylesheet" href="../layout/front-header.css">
    <style>
        /* Keep only the shared header notification bell. Hide any old page-level notification widget. */
        body > .notification-widget,
        body > .notification-widget-front,
        main .notification-widget,
        main .notification-widget-front {
            display: none !important;
        }
        .front-nav .notification-widget-front {
            display: inline-flex !important;
        }
    </style>
    <link rel="icon" type="image/png" sizes="16x16" href="../../public/images/favicon-16.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../../public/images/favicon-32.png">
    <link rel="shortcut icon" type="image/png" href="../../public/images/favicon-32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../../public/images/apple-touch-icon.png">
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
                        <span class="stat-label">Responses to review</span>
                        <span class="stat-value"><?php echo (int) ($brandMetrics['responsesToReview'] ?? 0); ?></span>
                        <span class="stat-note">Sent or under review</span>
                    </article>
                    <article class="stat-card">
                        <span class="stat-label">Negotiations waiting reply</span>
                        <span class="stat-value"><?php echo (int) ($brandMetrics['negotiationsWaitingReply'] ?? 0); ?></span>
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
                        <span class="stat-value"><?php echo (int) ($brandMetrics['acceptedCollaborations'] ?? 0); ?></span>
                        <span class="stat-note">Final positive outcomes</span>
                    </article>
                    <article class="stat-card">
                        <span class="stat-label">Accepted budget total</span>
                        <span class="stat-value"><?php echo htmlspecialchars(formatMoney($brandMetrics['acceptedBudgetTotal'] ?? 0)); ?></span>
                        <span class="stat-note">Accepted candidatures only</span>
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
                        </div>
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary">Apply filters</button>
                            <a class="btn btn-outline-secondary" href="brand_index.php">Reset</a>
                        </div>
                    </form>
                </section>

                <section
                    class="brand-source-tab-shell"
                    data-brand-source-tab-shell
                    data-default-source-tab="<?php echo htmlspecialchars($brandDefaultParentKey); ?>"
                >
                    <div class="brand-source-tab-list" role="tablist" aria-label="Candidature source groups">
                        <?php foreach ($brandParentGroups as $group): ?>
                            <?php $isActiveParent = $group['key'] === $brandDefaultParentKey; ?>
                            <button
                                type="button"
                                class="brand-source-tab-button<?php echo $isActiveParent ? ' is-active' : ''; ?>"
                                id="brand-source-tab-<?php echo htmlspecialchars($group['key']); ?>"
                                role="tab"
                                aria-selected="<?php echo $isActiveParent ? 'true' : 'false'; ?>"
                                aria-controls="brand-source-panel-<?php echo htmlspecialchars($group['key']); ?>"
                                data-brand-source-tab="<?php echo htmlspecialchars($group['key']); ?>"
                            >
                                <span<?php echo !empty($group['titleKey']) ? ' data-i18n="' . htmlspecialchars($group['titleKey']) . '"' : ''; ?>><?php echo htmlspecialchars($group['title']); ?></span>
                                <strong><?php echo (int) $group['count']; ?></strong>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <div class="brand-source-tab-panels">
                        <?php foreach ($brandParentGroups as $group): ?>
                            <?php $isActiveParentPanel = $group['key'] === $brandDefaultParentKey; ?>
                            <section
                                class="brand-source-tab-panel"
                                id="brand-source-panel-<?php echo htmlspecialchars($group['key']); ?>"
                                role="tabpanel"
                                aria-labelledby="brand-source-tab-<?php echo htmlspecialchars($group['key']); ?>"
                                data-brand-source-tab-panel="<?php echo htmlspecialchars($group['key']); ?>"
                                <?php echo $isActiveParentPanel ? '' : 'hidden'; ?>
                            >
                                <div class="brand-source-panel-heading">
                                    <div>
                                        <h2 class="section-title"<?php echo !empty($group['titleKey']) ? ' data-i18n="' . htmlspecialchars($group['titleKey']) . '"' : ''; ?>><?php echo htmlspecialchars($group['title']); ?></h2>
                                        <p class="section-subtitle"><?php echo htmlspecialchars($group['subtitle']); ?></p>
                                    </div>
                                    <span class="offer-section-count">
                                        <?php echo (int) $group['count']; ?> candidature<?php echo (int) $group['count'] === 1 ? '' : 's'; ?>
                                    </span>
                                </div>

                                <section class="offer-tab-shell brand-workflow-tab-shell" data-offer-tab-shell data-default-tab="<?php echo htmlspecialchars($group['defaultWorkflowTab']); ?>">
                                    <div class="offer-tab-list brand-workflow-tab-list" role="tablist" aria-label="<?php echo htmlspecialchars($group['title']); ?> workflow tabs">
                                        <?php foreach ($brandWorkflowSections as $section): ?>
                                            <?php
                                            $cards = $group['buckets'][$section['key']] ?? [];
                                            $isActiveTab = $section['key'] === $group['defaultWorkflowTab'];
                                            $tabId = 'brand-' . $group['key'] . '-tab-' . $section['key'];
                                            $panelId = 'brand-' . $group['key'] . '-panel-' . $section['key'];
                                            ?>
                                            <button
                                                type="button"
                                                class="offer-tab-button<?php echo $isActiveTab ? ' is-active' : ''; ?>"
                                                id="<?php echo htmlspecialchars($tabId); ?>"
                                                role="tab"
                                                aria-selected="<?php echo $isActiveTab ? 'true' : 'false'; ?>"
                                                aria-controls="<?php echo htmlspecialchars($panelId); ?>"
                                                data-offer-tab="<?php echo htmlspecialchars($section['key']); ?>"
                                            >
                                                <span class="offer-tab-label"><?php echo htmlspecialchars($section['title']); ?></span>
                                                <span class="offer-tab-badge"><?php echo count($cards); ?></span>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>

                                    <div class="offer-tab-panels">
                                        <?php foreach ($brandWorkflowSections as $section): ?>
                                            <?php
                                            $cards = $group['buckets'][$section['key']] ?? [];
                                            $isActivePanel = $section['key'] === $group['defaultWorkflowTab'];
                                            $tabId = 'brand-' . $group['key'] . '-tab-' . $section['key'];
                                            $panelId = 'brand-' . $group['key'] . '-panel-' . $section['key'];
                                            $emptyTitle = $group['count'] === 0 ? $group['emptyTitle'] : $section['emptyTitle'];
                                            $emptyCopy = $group['count'] === 0 ? $group['emptyCopy'] : $section['emptyCopy'];
                                            ?>
                                            <section
                                                class="section-card offer-section-card <?php echo htmlspecialchars($section['themeClass']); ?> offer-tab-panel"
                                                id="<?php echo htmlspecialchars($panelId); ?>"
                                                role="tabpanel"
                                                aria-labelledby="<?php echo htmlspecialchars($tabId); ?>"
                                                data-offer-tab-panel="<?php echo htmlspecialchars($section['key']); ?>"
                                                <?php echo $isActivePanel ? '' : 'hidden'; ?>
                                            >
                                                <div class="offer-section-header">
                                                    <div class="offer-section-copy">
                                                        <h3 class="section-title"><?php echo htmlspecialchars($section['title']); ?></h3>
                                                        <p class="section-subtitle"><?php echo htmlspecialchars($section['subtitle']); ?></p>
                                                    </div>
                                                    <span class="offer-section-count">
                                                        <?php echo count($cards); ?> candidature<?php echo count($cards) === 1 ? '' : 's'; ?>
                                                    </span>
                                                </div>

                                                <?php if (!empty($cards)): ?>
                                                    <div class="candidature-card-grid">
                                                        <?php foreach ($cards as $context): ?>
                                                            <?php renderBrandCandidatureCard($context, $savedCandidatureIds); ?>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="note-block offer-section-empty">
                                                        <strong><?php echo htmlspecialchars($emptyTitle); ?></strong>
                                                        <p><?php echo htmlspecialchars($emptyCopy); ?></p>
                                                    </div>
                                                <?php endif; ?>
                                            </section>
                                        <?php endforeach; ?>
                                    </div>
                                </section>
                            </section>
                        <?php endforeach; ?>
                    </div>
                </section>
                <nav class="front-pagination" aria-label="Brand candidature pages">
                    <span>Showing <?php echo (int) count($contexts); ?> of <?php echo (int) $totalFilteredContexts; ?> filtered candidature<?php echo (int) $totalFilteredContexts === 1 ? '' : 's'; ?></span>
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
        'page' => 'brand_candidature_workspace',
        'mode' => 'list',
        'role' => 'marque',
        'allowedActions' => ['normal_chat', 'summarize_page', 'analyze_page', 'apply_filters', 'reset_filter_action', 'recommend_next_action', 'explain_statuses', 'apply_search'],
        'formTarget' => 'filter_form',
        'visibleEntityType' => 'candidature',
    ];
    require __DIR__ . '/cre8pilot_widget.php';
    ?>
        <script>
            (() => {
                const translations = {
                    en: {
                        'cand.brandWorkspace': 'Brand candidature workspace',
                        'cand.responseWorkspace': 'Response workspace',
                        'cand.heroCopy': 'Review creator applications, handle negotiation signals, and keep each collaboration path moving from one dashboard.',
                        'cand.responsesReview': 'Responses to review',
                        'cand.sentReview': 'Sent or under review',
                        'cand.negotiationsWaiting': 'Negotiations waiting reply',
                        'cand.activeNegotiations': 'Active negotiation candidatures',
                        'cand.offersExpiring': 'Offers expiring soon',
                        'cand.deadlinesNext': 'Deadlines within the next 7 days',
                        'cand.acceptedCollaborations': 'Accepted collaborations',
                        'cand.finalPositive': 'Final positive outcomes',
                        'cand.acceptedBudgetTotal': 'Accepted budget total',
                        'cand.acceptedOnly': 'Accepted candidatures only',
                        'cand.filterWorkspace': 'Filter the response workspace',
                        'cand.filterCopy': 'Search by creator, source title, status, or keep only one origin in view.',
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
                        'cand.applyFilters': 'Apply filters',
                        'cand.reset': 'Reset',
                        'cand.waiting': 'Waiting',
                        'cand.accepted': 'Accepted',
                        'cand.refused': 'Refused',
                        'cand.outdated': 'Outdated',
                        'cand.waitingSubtitle': 'Responses that still need negotiation handling, review, or a final brand decision.',
                        'cand.acceptedSubtitle': 'Responses where the latest collaboration terms were fully accepted.',
                        'cand.refusedSubtitle': 'Responses that ended with a refusal or a declined invitation outcome.',
                        'cand.outdatedSubtitle': 'Responses linked to sources whose deadline has already passed without a final outcome.',
                        'cand.allApplications': 'All applications',
                        'cand.fromOffers': 'From offers',
                        'cand.fromCampaigns': 'From campaigns',
                        'cand.noWaiting': 'No waiting responses',
                        'cand.noAccepted': 'No accepted responses',
                        'cand.noRefused': 'No refused responses',
                        'cand.noOutdated': 'No outdated responses',
                        'cand.creator': 'Creator',
                        'cand.unknownCreator': 'Unknown creator',
                        'cand.submitted': 'Submitted',
                        'cand.lastUpdate': 'Last update',
                        'cand.finalDecision': 'Final decision',
                        'cand.latestCreatorSignal': 'Latest creator signal',
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
                        'cand.saved': 'Saved'
                    },
                    fr: {
                        'cand.brandWorkspace': 'Espace candidatures marque',
                        'cand.responseWorkspace': 'Espace reponses',
                        'cand.heroCopy': 'Examinez les candidatures createurs, gerez les negociations et faites avancer chaque collaboration depuis un tableau de bord.',
                        'cand.responsesReview': 'Reponses a examiner',
                        'cand.sentReview': 'Envoyees ou en examen',
                        'cand.negotiationsWaiting': 'Negociations en attente',
                        'cand.activeNegotiations': 'Candidatures en negociation active',
                        'cand.offersExpiring': 'Offres bientot expirees',
                        'cand.deadlinesNext': 'Echeances dans les 7 prochains jours',
                        'cand.acceptedCollaborations': 'Collaborations acceptees',
                        'cand.finalPositive': 'Resultats positifs finaux',
                        'cand.acceptedBudgetTotal': 'Budget accepte total',
                        'cand.acceptedOnly': 'Candidatures acceptees seulement',
                        'cand.filterWorkspace': 'Filtrer l espace reponses',
                        'cand.filterCopy': 'Recherchez par createur, source, statut ou gardez une seule origine.',
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
                        'cand.applyFilters': 'Appliquer les filtres',
                        'cand.reset': 'Reinitialiser',
                        'cand.waiting': 'En attente',
                        'cand.accepted': 'Acceptees',
                        'cand.refused': 'Refusees',
                        'cand.outdated': 'Expirees',
                        'cand.waitingSubtitle': 'Reponses qui demandent encore negociation, examen ou decision finale.',
                        'cand.acceptedSubtitle': 'Reponses dont les derniers termes de collaboration ont ete acceptes.',
                        'cand.refusedSubtitle': 'Reponses terminees par un refus ou une invitation declinee.',
                        'cand.outdatedSubtitle': 'Reponses liees a des sources dont l echeance est passee sans resultat final.',
                        'cand.allApplications': 'Toutes les candidatures',
                        'cand.fromOffers': 'Depuis les offres',
                        'cand.fromCampaigns': 'Depuis les campagnes',
                        'cand.noWaiting': 'Aucune reponse en attente',
                        'cand.noAccepted': 'Aucune reponse acceptee',
                        'cand.noRefused': 'Aucune reponse refusee',
                        'cand.noOutdated': 'Aucune reponse expiree',
                        'cand.creator': 'Createur',
                        'cand.unknownCreator': 'Createur inconnu',
                        'cand.submitted': 'Envoyee',
                        'cand.lastUpdate': 'Derniere mise a jour',
                        'cand.finalDecision': 'Decision finale',
                        'cand.latestCreatorSignal': 'Dernier signal createur',
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
                        'cand.saved': 'Enregistree'
                    }
                };
                const textKeys = {
                    'Brand candidature workspace': 'cand.brandWorkspace',
                    'Response workspace': 'cand.responseWorkspace',
                    'Review creator applications, handle negotiation signals, and keep each collaboration path moving from one dashboard.': 'cand.heroCopy',
                    'Responses to review': 'cand.responsesReview',
                    'Sent or under review': 'cand.sentReview',
                    'Negotiations waiting reply': 'cand.negotiationsWaiting',
                    'Active negotiation candidatures': 'cand.activeNegotiations',
                    'Offers expiring soon': 'cand.offersExpiring',
                    'Deadlines within the next 7 days': 'cand.deadlinesNext',
                    'Accepted collaborations': 'cand.acceptedCollaborations',
                    'Final positive outcomes': 'cand.finalPositive',
                    'Accepted budget total': 'cand.acceptedBudgetTotal',
                    'Accepted candidatures only': 'cand.acceptedOnly',
                    'Filter the response workspace': 'cand.filterWorkspace',
                    'Search by creator, source title, status, or keep only one origin in view.': 'cand.filterCopy',
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
                    'Apply filters': 'cand.applyFilters',
                    'Reset': 'cand.reset',
                    'Waiting': 'cand.waiting',
                    'Accepted': 'cand.accepted',
                    'Refused': 'cand.refused',
                    'Outdated': 'cand.outdated',
                    'Responses that still need negotiation handling, review, or a final brand decision.': 'cand.waitingSubtitle',
                    'Responses where the latest collaboration terms were fully accepted.': 'cand.acceptedSubtitle',
                    'Responses that ended with a refusal or a declined invitation outcome.': 'cand.refusedSubtitle',
                    'Responses linked to sources whose deadline has already passed without a final outcome.': 'cand.outdatedSubtitle',
                    'All applications': 'cand.allApplications',
                    'From offers': 'cand.fromOffers',
                    'From campaigns': 'cand.fromCampaigns',
                    'No waiting responses': 'cand.noWaiting',
                    'No accepted responses': 'cand.noAccepted',
                    'No refused responses': 'cand.noRefused',
                    'No outdated responses': 'cand.noOutdated',
                    'Creator': 'cand.creator',
                    'Unknown creator': 'cand.unknownCreator',
                    'Submitted': 'cand.submitted',
                    'Last update': 'cand.lastUpdate',
                    'Final decision': 'cand.finalDecision',
                    'Latest creator signal': 'cand.latestCreatorSignal',
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
                    'Saved': 'cand.saved'
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
        <?php require __DIR__ . '/../layout/footer.php'; ?>
        <script src="../layout/front-header.js"></script>
    </body>
</html>
