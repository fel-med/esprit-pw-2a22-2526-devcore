<?php
require_once __DIR__ . '/../layout/session_bridge.php';
$currentUser = cre8_front_require_user('marque');
$frontActive = 'collaborations';

require_once __DIR__ . '/../../../Controleur/condidatureC.php';

$controller = new CondidatureC();
$sessionUser = $currentUser;

$brandId = isset($sessionUser['id']) ? (int) $sessionUser['id'] : null;
$idCandidature = isset($_GET['idCandidature']) && is_numeric($_GET['idCandidature']) ? (int) $_GET['idCandidature'] : null;
$notice = trim((string) ($_GET['notice'] ?? ''));
$noticeType = trim((string) ($_GET['noticeType'] ?? 'success'));
$error = null;
$errors = [];
$decisionErrors = [];
$negotiationErrors = [];
$activeBrandModal = '';
$activeDecisionStatus = 'acceptee';
$context = null;

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

function formatDateLabel($value, $fallback = 'Not available')
{
    if (!$value) {
        return $fallback;
    }

    $timestamp = strtotime((string) $value);
    if ($timestamp === false) {
        return (string) $value;
    }

    return date('Y-m-d H:i', $timestamp);
}

function formatShortDate($value, $fallback = 'Not available')
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

function candidatureStoredFileHref($path)
{
    $path = trim(str_replace('\\', '/', (string) $path));
    if ($path === '') {
        return '';
    }

    if (preg_match('/^https?:\/\//i', $path) || str_starts_with($path, '/')) {
        return $path;
    }

    if (str_starts_with($path, 'Vue/public/')) {
        return '../../public/' . substr($path, strlen('Vue/public/'));
    }

    if (str_starts_with($path, 'public/')) {
        return '../../' . $path;
    }

    return $path;
}

function candidatureStoredFileName($path)
{
    $path = trim(str_replace('\\', '/', (string) $path));

    return $path !== '' ? basename($path) : '';
}

if ($brandId && $idCandidature !== null) {
    try {
        $context = $controller->getBrandCandidatureById($idCandidature, $brandId);
        if (!$context) {
            $error = 'This candidature could not be found in the current brand workspace.';
        }
    } catch (Throwable $exception) {
        $error = 'The candidature details could not be loaded right now.';
    }
} elseif (!$brandId) {
    $error = 'No brand profile is available for this workspace.';
} else {
    $error = 'Choose a candidature before opening the detail page.';
}

$condidature = $context['condidature'] ?? null;
$creator = $context['creator'] ?? ['nom' => '', 'email' => ''];
$brand = $context['brand'] ?? ['nom' => '', 'email' => ''];
$source = $context['source'] ?? ['origin' => 'par_offre', 'id' => 0, 'title' => '', 'objective' => '', 'description' => '', 'datePublication' => null, 'dateLimite' => null];
$negotiation = $context['negotiation'] ?? ['count' => 0, 'latest' => null, 'history' => []];

$form = [
    'message' => '',
    'budgetPropose' => $condidature ? (string) $condidature->getBudgetPropose() : '',
    'delaiPropose' => $condidature ? (string) $condidature->getDelaiPropose() : '',
];
$decisionForm = [
    'noteDecision' => $condidature ? (string) $condidature->getNoteDecision() : '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error && $condidature) {
    $brandAction = trim((string) ($_POST['brandAction'] ?? 'negotiate'));

    if ($brandAction === 'decide') {
        $decisionForm = [
            'noteDecision' => trim((string) ($_POST['noteDecision'] ?? '')),
        ];
        $decisionStatus = trim((string) ($_POST['decisionStatus'] ?? ''));
        $activeDecisionStatus = $decisionStatus === 'refusee' ? 'refusee' : 'acceptee';

        $result = $controller->decideCandidatureAsBrand($condidature->getIdCandidature(), $brandId, [
            'decisionStatus' => $decisionStatus,
            'noteDecision' => $decisionForm['noteDecision'],
        ]);

        if (!empty($result['success'])) {
            $noticeMessage = $decisionStatus === 'acceptee'
                ? 'Candidature accepted successfully from the brand workspace.'
                : 'Candidature refused successfully from the brand workspace.';
            $noticeKind = $decisionStatus === 'acceptee' ? 'success' : 'danger';
            header('Location: brand_details.php?idCandidature=' . (int) $condidature->getIdCandidature() . '&notice=' . urlencode($noticeMessage) . '&noticeType=' . urlencode($noticeKind));
            exit;
        }

        $errors = $result['errors'] ?? ['Unable to save the brand decision right now.'];
        $decisionErrors = $errors;
        $activeBrandModal = 'decision';
    } else {
        $form = [
            'message' => trim((string) ($_POST['message'] ?? '')),
            'budgetPropose' => trim((string) ($_POST['budgetPropose'] ?? '')),
            'delaiPropose' => trim((string) ($_POST['delaiPropose'] ?? '')),
        ];

        $result = $controller->replyToNegotiationAsBrand($condidature->getIdCandidature(), $brandId, $form);
        if (!empty($result['success'])) {
            header('Location: brand_details.php?idCandidature=' . (int) $condidature->getIdCandidature() . '&notice=' . urlencode('Brand negotiation reply sent successfully.') . '&noticeType=success');
            exit;
        }

        $errors = $result['errors'] ?? ['Unable to send the brand negotiation reply right now.'];
        $negotiationErrors = $errors;
        $activeBrandModal = 'negotiate';
    }

    $context = $result['context'] ?? $context;
    $condidature = $context['condidature'] ?? $condidature;
    $creator = $context['creator'] ?? $creator;
    $brand = $context['brand'] ?? $brand;
    $source = $context['source'] ?? $source;
    $negotiation = $context['negotiation'] ?? $negotiation;
}

$brandCanNegotiate = $condidature && $condidature->canBrandNegotiate();
$brandCanDecide = $condidature && $condidature->canBrandDecide();
$composerTitle = $condidature && $condidature->isNegotiation() ? 'Reply to negotiation' : 'Start negotiation';
$composerHint = $condidature && $condidature->isNegotiation()
    ? 'Reply inside the existing negotiation thread only when you are proposing a real change to the budget, timeline, or execution terms.'
    : 'Use this panel to open a negotiation round from the brand side and send a structured counter-proposal.';
$actionHubStateLabel = $brandCanDecide && $brandCanNegotiate
    ? 'Decision and negotiation open'
    : ($brandCanDecide ? 'Decision open' : ($brandCanNegotiate ? 'Negotiation open' : 'Actions locked'));
$latestCreatorEntry = $negotiation['latestCreator'] ?? null;
$latestNegotiationEntry = $negotiation['latest'] ?? null;
$brandIsLatestNegotiationSender = $latestNegotiationEntry && ($latestNegotiationEntry['auteur'] ?? '') === 'marque';
$latestCreatorMessage = trim((string) ($latestCreatorEntry['message'] ?? '')) !== ''
    ? (string) $latestCreatorEntry['message']
    : (trim((string) $condidature->getMessageMotivation()) !== '' ? (string) $condidature->getMessageMotivation() : 'No creator message was added yet.');
$latestCreatorLabel = $latestCreatorEntry ? 'Latest creator update' : 'Initial context';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $brandIsLatestNegotiationSender) {
    $form['message'] = (string) ($latestNegotiationEntry['message'] ?? $form['message']);
    if (($latestNegotiationEntry['budgetPropose'] ?? null) !== null) {
        $form['budgetPropose'] = (string) $latestNegotiationEntry['budgetPropose'];
    }
    if (($latestNegotiationEntry['delaiPropose'] ?? null) !== null) {
        $form['delaiPropose'] = (string) $latestNegotiationEntry['delaiPropose'];
    }
}

$brandNegotiationBaselineMessage = $brandIsLatestNegotiationSender ? (string) ($latestNegotiationEntry['message'] ?? '') : '';
$brandNegotiationBaselineBudget = $brandIsLatestNegotiationSender && ($latestNegotiationEntry['budgetPropose'] ?? null) !== null
    ? (string) $latestNegotiationEntry['budgetPropose']
    : (string) ($condidature ? $condidature->getBudgetPropose() : $form['budgetPropose']);
$brandNegotiationBaselineDelay = $brandIsLatestNegotiationSender && ($latestNegotiationEntry['delaiPropose'] ?? null) !== null
    ? (string) $latestNegotiationEntry['delaiPropose']
    : (string) ($condidature ? $condidature->getDelaiPropose() : $form['delaiPropose']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/../layout/front-theme-bootstrap.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Brand Candidature Details - Cre8Connect</title>
    <link rel="stylesheet" href="../css/frontoffice.css">
    <link rel="stylesheet" href="../offre/offre.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../offre/offre.css')); ?>">
    <link rel="stylesheet" href="condidature.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/condidature.css')); ?>">
    <link rel="stylesheet" href="../layout/front-header.css">
<link rel="icon" type="image/png" sizes="32x32" href="../../public/images/logo.png">
<link rel="shortcut icon" type="image/png" href="../../public/images/logo.png">
<link rel="apple-touch-icon" href="../../public/images/logo.png">
</head>
<body>
    <?php require_once dirname(__DIR__) . '/layout/header.php'; ?>
    <main class="container py-5">
        <div class="offre-page-shell">
            <?php if ($notice !== ''): ?>
                <div class="alert alert-<?php echo $noticeType === 'danger' ? 'danger' : 'success'; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($notice); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <section class="empty-state-card">
                    <div class="empty-state-icon">!</div>
                    <h2 class="section-title">Candidature unavailable</h2>
                    <p class="section-subtitle"><?php echo htmlspecialchars($error); ?></p>
                    <div class="compact-actions justify-content-center mt-4">
                        <a class="btn btn-primary" href="brand_index.php">Back to brand workspace</a>
                    </div>
                </section>
            <?php else: ?>
                <section class="module-hero">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                        <div>
                            <span class="module-eyebrow"><?php echo htmlspecialchars(translateOrigin($source['origin'])); ?></span>
                            <h1 class="display-5 fw-bold mt-3 mb-2 gradient-title"><?php echo htmlspecialchars($source['title']); ?></h1>
                            <p class="lead text-muted mb-0">
                                Review the creator response, follow the negotiation thread, and send a compact brand-side counter-proposal without leaving this workspace.
                            </p>
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <span class="origin-badge"><?php echo htmlspecialchars(translateOrigin($source['origin'])); ?></span>
                            <span class="candidature-badge <?php echo htmlspecialchars(candidatureBadgeClass($condidature->getStatutCandidature())); ?>">
                                <?php echo htmlspecialchars($condidature->getDisplayStatusLabel()); ?>
                            </span>
                        </div>
                    </div>
                    <div class="candidature-inline-meta mt-4">
                        <span class="offer-chip"><?php echo htmlspecialchars($condidature->getResponseTypeLabel()); ?></span>
                        <span class="offer-chip"><?php echo htmlspecialchars(formatMoney($condidature->getBudgetPropose())); ?></span>
                        <span class="offer-chip"><?php echo (int) $condidature->getDelaiPropose(); ?> days</span>
                        <span class="offer-chip"><?php echo (int) ($negotiation['count'] ?? 0); ?> negotiation message<?php echo (int) ($negotiation['count'] ?? 0) === 1 ? '' : 's'; ?></span>
                    </div>
                </section>

                <div class="invitation-grid">
                    <div class="response-grid">
                        <section class="section-card">
                            <h2 class="section-title">Response context</h2>
                            <p class="section-subtitle">Keep the creator profile and source details visible while you review the thread.</p>
                            <div class="offer-detail-list mt-4">
                                <div class="offer-detail-item">
                                    <strong>Creator</strong>
                                    <span><?php echo htmlspecialchars($creator['nom'] ?: 'Unknown creator'); ?></span>
                                    <p><?php echo htmlspecialchars($creator['email']); ?></p>
                                </div>
                                <div class="offer-detail-item">
                                    <strong>Brand workspace</strong>
                                    <span><?php echo htmlspecialchars($brand['nom'] ?: 'Unknown brand'); ?></span>
                                    <p><?php echo htmlspecialchars($brand['email']); ?></p>
                                </div>
                                <div class="offer-detail-item">
                                    <strong><?php echo ($source['origin'] ?? '') === 'par_campagne' ? 'Campaign description' : 'Offer objective'; ?></strong>
                                    <span><?php echo htmlspecialchars($source['objective'] ?: 'No source objective was attached to this candidature.'); ?></span>
                                </div>
                                <div class="offer-detail-item">
                                    <strong><?php echo ($source['origin'] ?? '') === 'par_campagne' ? 'Campaign start date' : 'Source published'; ?></strong>
                                    <span><?php echo htmlspecialchars(formatShortDate($source['datePublication'] ?? null)); ?></span>
                                </div>
                                <div class="offer-detail-item">
                                    <strong><?php echo ($source['origin'] ?? '') === 'par_campagne' ? 'Campaign end date' : 'Source deadline'; ?></strong>
                                    <span><?php echo htmlspecialchars(formatShortDate($source['dateLimite'] ?? null)); ?></span>
                                </div>
                                <div class="offer-detail-item">
                                    <strong>CV/reference file</strong>
                                    <?php if (trim((string) $condidature->getCvPath()) !== ''): ?>
                                        <a class="current-upload-link" href="<?php echo htmlspecialchars(candidatureStoredFileHref($condidature->getCvPath())); ?>" target="_blank" rel="noopener noreferrer">
                                            <?php echo htmlspecialchars(candidatureStoredFileName($condidature->getCvPath()) ?: $condidature->getCvPath()); ?>
                                        </a>
                                    <?php else: ?>
                                        <span>No file attached yet</span>
                                    <?php endif; ?>
                                </div>
                                <div class="offer-detail-item">
                                    <strong>Portfolio URL</strong>
                                    <?php if (trim((string) $condidature->getPortfolioUrl()) !== ''): ?>
                                        <a class="current-upload-link" href="<?php echo htmlspecialchars($condidature->getPortfolioUrl()); ?>" target="_blank" rel="noopener noreferrer">
                                            <?php echo htmlspecialchars($condidature->getPortfolioUrl()); ?>
                                        </a>
                                    <?php else: ?>
                                        <span>No portfolio link attached yet</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </section>

                        <div class="candidature-summary-grid">
                            <section class="info-card">
                                <h2 class="section-title">Creator message</h2>
                                <div class="decision-note-card mt-3">
                                    <strong><?php echo htmlspecialchars($latestCreatorLabel); ?></strong>
                                    <p><?php echo htmlspecialchars($latestCreatorMessage); ?></p>
                                    <?php if ($latestCreatorEntry): ?>
                                        <div class="candidature-inline-meta mt-3">
                                            <span class="offer-chip"><?php echo htmlspecialchars(formatDateLabel($latestCreatorEntry['dateMessage'] ?? null)); ?></span>
                                            <?php if ($latestCreatorEntry['budgetPropose'] !== null): ?>
                                                <span class="offer-chip"><?php echo htmlspecialchars(formatMoney($latestCreatorEntry['budgetPropose'])); ?></span>
                                            <?php endif; ?>
                                            <?php if ($latestCreatorEntry['delaiPropose'] !== null): ?>
                                                <span class="offer-chip">Timeline: <?php echo (int) $latestCreatorEntry['delaiPropose']; ?> days</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </section>
                            <section class="info-card">
                                <h2 class="section-title">Decision note</h2>
                                <div class="decision-note-card mt-3">
                                    <strong>Stored review feedback</strong>
                                    <p><?php echo htmlspecialchars(trim((string) $condidature->getNoteDecision()) !== '' ? $condidature->getNoteDecision() : 'No decision note is attached yet.'); ?></p>
                                </div>
                            </section>
                        </div>

                        <section class="section-card">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                                <div>
                                    <h2 class="section-title">Negotiation history</h2>
                                    <p class="section-subtitle">Every creator and brand message is kept in order so the current negotiation state stays readable.</p>
                                </div>
                                <span class="offer-chip"><?php echo (int) ($negotiation['count'] ?? 0); ?> message<?php echo (int) ($negotiation['count'] ?? 0) === 1 ? '' : 's'; ?></span>
                            </div>

                            <?php if (!empty($negotiation['history'])): ?>
                                <div class="negotiation-thread mt-4">
                                    <?php foreach ($negotiation['history'] as $entry): ?>
                                        <article class="negotiation-entry negotiation-entry-<?php echo htmlspecialchars($entry['auteur']); ?>">
                                            <div class="negotiation-entry-top">
                                                <div class="negotiation-author-block">
                                                    <span class="negotiation-role negotiation-role-<?php echo htmlspecialchars($entry['auteur']); ?>">
                                                        <?php echo htmlspecialchars($entry['authorRoleLabel']); ?>
                                                    </span>
                                                    <strong><?php echo htmlspecialchars($entry['authorName']); ?></strong>
                                                    <?php if (!empty($entry['authorEmail'])): ?>
                                                        <span><?php echo htmlspecialchars($entry['authorEmail']); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <span class="offer-chip"><?php echo htmlspecialchars(formatDateLabel($entry['dateMessage'] ?? null)); ?></span>
                                            </div>
                                            <p class="negotiation-entry-message"><?php echo htmlspecialchars($entry['message'] !== '' ? $entry['message'] : 'No message body was added to this negotiation step.'); ?></p>
                                            <?php if ($entry['budgetPropose'] !== null || $entry['delaiPropose'] !== null): ?>
                                                <div class="candidature-inline-meta">
                                                    <?php if ($entry['budgetPropose'] !== null): ?>
                                                        <span class="offer-chip"><?php echo htmlspecialchars(formatMoney($entry['budgetPropose'])); ?></span>
                                                    <?php endif; ?>
                                                    <?php if ($entry['delaiPropose'] !== null): ?>
                                                        <span class="offer-chip">Timeline: <?php echo (int) $entry['delaiPropose']; ?> days</span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="note-block mt-4">
                                    <strong>No negotiation messages yet</strong>
                                    <p>This candidature has no negotiation thread yet. Use the brand panel to start one when you need to adjust terms.</p>
                                </div>
                            <?php endif; ?>
                        </section>
                    </div>

                    <aside class="response-grid">
                        <section class="info-card">
                            <h2 class="section-title">Status snapshot</h2>
                            <div class="timeline-card mt-3">
                                <strong><?php echo htmlspecialchars($condidature->getDisplayStatusLabel()); ?></strong>
                                <p>
                                    Response type: <?php echo htmlspecialchars($condidature->getResponseTypeLabel()); ?><br>
                                    Submitted: <?php echo htmlspecialchars(formatShortDate($condidature->getDateCandidature())); ?><br>
                                    Last update: <?php echo htmlspecialchars(formatDateLabel($condidature->getDateDerniereModification())); ?><br>
                                    Decision date: <?php echo htmlspecialchars(formatDateLabel($condidature->getDateDecision(), 'No final decision yet')); ?>
                                </p>
                            </div>
                        </section>

                        <?php if ($brandCanDecide || $brandCanNegotiate): ?>
                            <section class="composer-card brand-action-card">
                                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                                    <div>
                                        <h2 class="section-title">Brand actions</h2>
                                        <p class="section-subtitle">Choose the next move from one action hub instead of managing separate stacked forms.</p>
                                    </div>
                                    <span class="candidature-badge <?php echo htmlspecialchars(candidatureBadgeClass($condidature->getStatutCandidature())); ?>">
                                        <?php echo htmlspecialchars($actionHubStateLabel); ?>
                                    </span>
                                </div>
                                <div class="brand-action-grid mt-4">
                                    <?php if ($brandCanDecide): ?>
                                        <button
                                            type="button"
                                            class="brand-action-launch brand-action-launch-accept"
                                            data-response-modal-trigger="decision"
                                            data-decision-status="acceptee"
                                        >
                                            <strong>Accept</strong>
                                            <span>Approve the creator response and close the workflow with a final decision.</span>
                                        </button>
                                        <button
                                            type="button"
                                            class="brand-action-launch brand-action-launch-refuse"
                                            data-response-modal-trigger="decision"
                                            data-decision-status="refusee"
                                        >
                                            <strong>Refuse</strong>
                                            <span>Stop the current response and store a clear refusal note for later review.</span>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($brandCanNegotiate): ?>
                                        <button
                                            type="button"
                                            class="brand-action-launch brand-action-launch-negotiate"
                                            data-response-modal-trigger="negotiate"
                                        >
                                            <strong><?php echo htmlspecialchars($brandIsLatestNegotiationSender ? 'Update your proposal' : $composerTitle); ?></strong>
                                            <span><?php echo htmlspecialchars($brandIsLatestNegotiationSender ? 'You sent the latest negotiation step, so edit that proposal instead of adding a duplicate message.' : $composerHint); ?></span>
                                        </button>
                                    <?php endif; ?>
                                </div>
                                <p class="composer-context-note mt-3">
                                    Use negotiation only for real adjustments. If the latest terms are already acceptable, finish the workflow with Accept or Refuse instead of repeating the same proposal in another message.
                                </p>
                            </section>
                        <?php else: ?>
                            <section class="locked-state-card">
                                <h3>Actions locked</h3>
                                <p>
                                    This candidature already has a final outcome, so the brand can keep reviewing the thread but cannot send a new decision or negotiation reply from here.
                                </p>
                            </section>
                        <?php endif; ?>

                        <div class="compact-actions">
                            <a class="btn btn-outline-secondary w-100" href="brand_index.php">Back to brand workspace</a>
                            <?php if ($source['origin'] === 'par_offre'): ?>
                                <a class="btn btn-outline-secondary w-100" href="../offre/brand_details.php?idOffre=<?php echo (int) $source['id']; ?>">View source offer</a>
                            <?php endif; ?>
                        </div>
                    </aside>
                </div>

                <?php if ($brandCanDecide || $brandCanNegotiate): ?>
                    <div
                        class="response-modal-overlay"
                        data-response-modal-overlay
                        data-default-modal="<?php echo htmlspecialchars($activeBrandModal); ?>"
                        data-default-decision-status="<?php echo htmlspecialchars($activeDecisionStatus); ?>"
                        hidden
                    >
                        <?php if ($brandCanDecide): ?>
                            <div class="response-modal-panel" data-response-modal-panel="decision" hidden>
                                <form
                                    method="post"
                                    action="brand_details.php?idCandidature=<?php echo (int) $condidature->getIdCandidature(); ?>"
                                    class="response-modal-card"
                                    data-response-modal-card
                                    data-cre8pilot-form="brand_decision_form"
                                    data-modal-variant="<?php echo htmlspecialchars($activeDecisionStatus === 'refusee' ? 'refuse' : 'accept'); ?>"
                                    role="dialog"
                                    aria-modal="true"
                                    aria-labelledby="brandDecisionModalTitle"
                                >
                                    <div class="response-modal-header">
                                        <div>
                                            <span class="response-modal-kicker" data-decision-kicker>Brand decision</span>
                                            <h2 id="brandDecisionModalTitle" data-decision-title>Accept this candidature?</h2>
                                            <p class="response-modal-subtitle" data-decision-subtitle">Confirm the final outcome for this creator response without leaving the current page.</p>
                                        </div>
                                    </div>
                                    <div class="response-modal-body">
                                        <?php if (!empty($decisionErrors)): ?>
                                            <div class="response-modal-summary response-modal-summary-danger">
                                                <strong>Unable to save the brand decision.</strong>
                                                <ul>
                                                    <?php foreach ($decisionErrors as $item): ?>
                                                        <li><?php echo htmlspecialchars($item); ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        <?php endif; ?>

                                        <input type="hidden" name="brandAction" value="decide">
                                        <input type="hidden" name="decisionStatus" value="<?php echo htmlspecialchars($activeDecisionStatus); ?>" data-decision-status-input>

                                        <p class="response-modal-copy" data-decision-copy">
                                            Approve the creator response and store your note as the final brand-side decision.
                                        </p>

                                        <div class="response-modal-preview">
                                            <span class="response-modal-preview-label">Selected response</span>
                                            <strong><?php echo htmlspecialchars($source['title']); ?></strong>
                                            <span>Creator: <?php echo htmlspecialchars($creator['nom'] ?: 'Unknown creator'); ?></span>
                                            <span>Current status: <?php echo htmlspecialchars($condidature->getDisplayStatusLabel()); ?></span>
                                        </div>

                                        <div>
                                            <label for="decisionModalNote" class="form-label fw-semibold">Decision note</label>
                                            <textarea
                                                class="form-control"
                                                id="decisionModalNote"
                                                name="noteDecision"
                                                data-cre8pilot-field="noteDecision"
                                                rows="5"
                                                placeholder="Add optional feedback for the creator or your internal review trail."
                                            ><?php echo htmlspecialchars($decisionForm['noteDecision']); ?></textarea>
                                            <p class="composer-context-note mt-2">If you leave this empty, the module will store a short default decision note automatically.</p>
                                        </div>

                                        <div class="response-modal-actions">
                                            <button type="button" class="response-modal-secondary" data-response-modal-close>Keep reviewing</button>
                                            <button type="submit" class="response-modal-primary" data-decision-submit>Accept candidature</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>

                        <?php if ($brandCanNegotiate): ?>
                            <div class="response-modal-panel" data-response-modal-panel="negotiate" data-cre8pilot-section="negotiation" hidden>
                                <form
                                    method="post"
                                    action="brand_details.php?idCandidature=<?php echo (int) $condidature->getIdCandidature(); ?>"
                                    class="response-modal-card"
                                    data-response-modal-card
                                    data-cre8pilot-form="negotiation_form"
                                    data-modal-variant="negotiate"
                                    data-require-negotiation-delta="1"
                                    data-baseline-message="<?php echo htmlspecialchars($brandNegotiationBaselineMessage); ?>"
                                    data-baseline-budget="<?php echo htmlspecialchars($brandNegotiationBaselineBudget); ?>"
                                    data-baseline-delay="<?php echo htmlspecialchars($brandNegotiationBaselineDelay); ?>"
                                    role="dialog"
                                    aria-modal="true"
                                    aria-labelledby="brandNegotiationModalTitle"
                                >
                                    <div class="response-modal-header">
                                        <div>
                                            <span class="response-modal-kicker">Negotiation</span>
                                            <h2 id="brandNegotiationModalTitle"><?php echo htmlspecialchars($brandIsLatestNegotiationSender ? 'Update your proposal' : $composerTitle); ?></h2>
                                            <p class="response-modal-subtitle"><?php echo htmlspecialchars($brandIsLatestNegotiationSender ? 'Change your latest message, budget, or timeline. It will update the last brand proposal instead of creating a duplicate history step.' : $composerHint); ?></p>
                                        </div>
                                    </div>
                                    <div class="response-modal-body">
                                        <?php if (!empty($negotiationErrors)): ?>
                                            <div class="response-modal-summary response-modal-summary-danger">
                                                <strong>Unable to send the negotiation reply.</strong>
                                                <ul>
                                                    <?php foreach ($negotiationErrors as $item): ?>
                                                        <li><?php echo htmlspecialchars($item); ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        <?php endif; ?>

                                        <input type="hidden" name="brandAction" value="negotiate">

                                        <p class="response-modal-copy">
                                            <?php echo $brandIsLatestNegotiationSender
                                                ? 'Your latest brand proposal will be updated in place. Change at least one field before saving it.'
                                                : 'Your reply will be added to the negotiation history only as a real counter-proposal. If you already agree with the latest creator terms, close the workflow with a final decision instead of repeating them here.'; ?>
                                        </p>

                                        <div class="response-modal-preview">
                                            <span class="response-modal-preview-label"><?php echo $brandIsLatestNegotiationSender ? 'Your latest proposal' : 'Latest creator update'; ?></span>
                                            <strong><?php echo htmlspecialchars($brandIsLatestNegotiationSender ? 'Latest brand proposal' : $latestCreatorLabel); ?></strong>
                                            <span><?php echo htmlspecialchars($brandIsLatestNegotiationSender ? (string) ($latestNegotiationEntry['message'] ?? 'No message body was added to this negotiation step.') : $latestCreatorMessage); ?></span>
                                            <?php if ($brandIsLatestNegotiationSender || $latestCreatorEntry): ?>
                                                <span><?php echo htmlspecialchars(formatDateLabel($brandIsLatestNegotiationSender ? ($latestNegotiationEntry['dateMessage'] ?? null) : ($latestCreatorEntry['dateMessage'] ?? null))); ?></span>
                                            <?php endif; ?>
                                        </div>

                                        <div>
                                            <label for="negotiationModalMessage" class="form-label fw-semibold">Negotiation message</label>
                                            <textarea
                                                class="form-control"
                                                id="negotiationModalMessage"
                                                name="message"
                                                data-cre8pilot-field="messageNegociation"
                                                rows="4"
                                                placeholder="Explain the adjustment you want to propose or clarify the current terms."
                                            ><?php echo htmlspecialchars($form['message']); ?></textarea>
                                        </div>

                                        <div class="response-modal-field-grid">
                                            <div>
                                                <label for="negotiationModalBudget" class="form-label fw-semibold">Budget proposal</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">EUR</span>
                                                    <input type="number" class="form-control" id="negotiationModalBudget" name="budgetPropose" step="0.01" data-cre8pilot-field="budgetPropose" value="<?php echo htmlspecialchars($form['budgetPropose']); ?>">
                                                </div>
                                            </div>
                                            <div>
                                                <label for="negotiationModalDelay" class="form-label fw-semibold">Timeline proposal</label>
                                                <input type="number" class="form-control" id="negotiationModalDelay" name="delaiPropose" step="1" data-cre8pilot-field="delaiPropose" value="<?php echo htmlspecialchars($form['delaiPropose']); ?>">
                                            </div>
                                        </div>

                                        <div class="response-modal-actions">
                                            <button type="button" class="response-modal-secondary" data-response-modal-close>Keep reviewing</button>
                                            <button type="submit" class="response-modal-primary response-modal-primary-negotiate"><?php echo htmlspecialchars($brandIsLatestNegotiationSender ? 'Update proposal' : ($condidature->isNegotiation() ? 'Send negotiation reply' : 'Start negotiation')); ?></button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
    <?php if (!$error && ($brandCanDecide || $brandCanNegotiate)): ?>
        <script src="brand-actions-modal.js?v=<?php echo urlencode((string) filemtime(__DIR__ . '/brand-actions-modal.js')); ?>"></script>
    <?php endif; ?>
<?php
$cre8PilotContext = [
    'page' => 'brand_candidature_workspace',
    'mode' => ($condidature && $condidature->isNegotiation()) ? 'negotiation_reply' : 'review_details',
    'role' => 'marque',
    'allowedActions' => ($condidature && $condidature->isNegotiation())
        ? ['normal_chat', 'prepare_negotiation_reply', 'prepare_acceptance_note', 'prepare_refusal_note', 'summarize_negotiation', 'improve_negotiation_message', 'security_check']
        : ['normal_chat', 'summarize_candidature', 'prepare_acceptance_note', 'prepare_refusal_note', 'prepare_negotiation_reply', 'analyze_candidature_quality', 'security_check'],
    'formTarget' => ($condidature && $condidature->isNegotiation()) ? 'negotiation_form' : 'brand_decision_form',
    'visibleEntityType' => ($condidature && $condidature->isNegotiation()) ? 'negociation' : 'candidature',
    'visibleEntityId' => $idCandidature ?? null,
];
require __DIR__ . '/cre8pilot_widget.php';
?>
    <script src="../layout/front-header.js"></script>
</body>
</html>
