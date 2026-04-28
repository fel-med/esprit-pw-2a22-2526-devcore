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
$idCandidature = isset($_GET['idCandidature']) && is_numeric($_GET['idCandidature']) ? (int) $_GET['idCandidature'] : null;
$origin = trim((string) ($_GET['origin'] ?? 'par_offre')) ?: 'par_offre';
$idSource = isset($_GET['idSource']) && is_numeric($_GET['idSource']) ? (int) $_GET['idSource'] : null;
$preferredMode = strtolower(trim((string) ($_GET['mode'] ?? '')));
$notice = trim((string) ($_GET['notice'] ?? ''));
$noticeType = trim((string) ($_GET['noticeType'] ?? 'success'));
$error = null;
$errors = [];
$context = null;
$source = null;

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

function normalizeComposerMode($mode)
{
    $mode = strtolower(trim((string) $mode));

    return in_array($mode, ['accept', 'negotiate', 'decline'], true) ? $mode : '';
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

function formatDateTimeLabel($value, $fallback = 'Not available')
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

function formatDelayLabel($value, $fallback = 'Not planned')
{
    if ($value === null || $value === '') {
        return $fallback;
    }

    $days = (int) $value;

    return $days . ' day' . ($days === 1 ? '' : 's');
}

function blankToFallback($value, $fallback)
{
    $value = trim((string) $value);

    return $value !== '' ? $value : $fallback;
}

function responseModeCardLabel($mode)
{
    return match ((string) $mode) {
        'negotiate' => 'Negotiation request',
        'decline' => 'Decline response',
        default => 'Acceptance response',
    };
}

function computeDelayFallback($source)
{
    if (!$source || empty($source['dateLimite'])) {
        return 7;
    }

    $today = new DateTime('today', new DateTimeZone('Africa/Tunis'));
    $deadline = DateTime::createFromFormat('Y-m-d', (string) $source['dateLimite'], new DateTimeZone('Africa/Tunis'));
    if (!$deadline) {
        return 7;
    }

    $diff = (int) $today->diff($deadline)->format('%r%a');

    return $diff > 0 ? min(45, $diff) : 7;
}

function formFieldValue(array $form, $key)
{
    return htmlspecialchars((string) ($form[$key] ?? ''));
}

function renderCreatorPlanFields(array $form, $suffix, $messageMode = 'accept')
{
    $isNegotiation = $messageMode === 'negotiate';
    ?>
    <section class="creator-modal-section">
        <div class="creator-modal-section-head">
            <strong>Availability and delivery</strong>
            <span>Share when you can start and how long the delivery should take.</span>
        </div>
        <div class="response-modal-field-grid">
            <div>
                <label for="dateDisponibilite<?php echo htmlspecialchars($suffix); ?>" class="form-label fw-semibold">Availability start date</label>
                <input type="date" class="form-control" id="dateDisponibilite<?php echo htmlspecialchars($suffix); ?>" name="dateDisponibilite" value="<?php echo formFieldValue($form, 'dateDisponibilite'); ?>">
            </div>
            <div>
                <label for="delaiPropose<?php echo htmlspecialchars($suffix); ?>" class="form-label fw-semibold"><?php echo $isNegotiation ? 'Proposed delay' : 'Delivery delay'; ?></label>
                <input type="number" class="form-control" id="delaiPropose<?php echo htmlspecialchars($suffix); ?>" name="delaiPropose" min="1" step="1" value="<?php echo formFieldValue($form, 'delaiPropose'); ?>">
            </div>
        </div>
    </section>

    <section class="creator-modal-section">
        <div class="creator-modal-section-head">
            <strong><?php echo $isNegotiation ? 'Negotiation message' : 'Creator message'; ?></strong>
            <span><?php echo $isNegotiation ? 'Explain the adjustment you want to propose.' : 'Add the short context the brand should read first.'; ?></span>
        </div>
        <label for="messageMotivation<?php echo htmlspecialchars($suffix); ?>" class="form-label fw-semibold"><?php echo $isNegotiation ? 'Negotiation message' : 'Creator message / motivation'; ?></label>
        <textarea class="form-control" id="messageMotivation<?php echo htmlspecialchars($suffix); ?>" name="messageMotivation" rows="4" placeholder="<?php echo $isNegotiation ? 'Explain the revised budget, timing, or collaboration context.' : 'Explain why this collaboration fits your content, audience, or execution approach.'; ?>"><?php echo formFieldValue($form, 'messageMotivation'); ?></textarea>

        <div class="mt-3">
            <label for="conditionsCreateur<?php echo htmlspecialchars($suffix); ?>" class="form-label fw-semibold">Creator terms and conditions</label>
            <textarea class="form-control" id="conditionsCreateur<?php echo htmlspecialchars($suffix); ?>" name="conditionsCreateur" rows="3" placeholder="Add optional creator-side conditions, process notes, or collaboration boundaries."><?php echo formFieldValue($form, 'conditionsCreateur'); ?></textarea>
        </div>
    </section>

    <section class="creator-modal-section">
        <div class="creator-modal-section-head">
            <strong>Profile support</strong>
            <span>Attach simple references the brand can review with your response.</span>
        </div>
        <div class="response-modal-field-grid">
            <div>
                <label for="cvPath<?php echo htmlspecialchars($suffix); ?>" class="form-label fw-semibold">CV path or CV note</label>
                <input type="text" class="form-control" id="cvPath<?php echo htmlspecialchars($suffix); ?>" name="cvPath" value="<?php echo formFieldValue($form, 'cvPath'); ?>" placeholder="Example: uploads/cv_sami_fit.pdf">
            </div>
            <div>
                <label for="portfolioUrl<?php echo htmlspecialchars($suffix); ?>" class="form-label fw-semibold">Portfolio URL</label>
                <input type="url" class="form-control" id="portfolioUrl<?php echo htmlspecialchars($suffix); ?>" name="portfolioUrl" value="<?php echo formFieldValue($form, 'portfolioUrl'); ?>" placeholder="https://portfolio.example.com">
            </div>
        </div>
    </section>
    <?php
}

function renderCreatorBudgetField(array $form, $suffix)
{
    ?>
    <section class="creator-modal-section creator-modal-section-warning">
        <div class="creator-modal-section-head">
            <strong>Negotiation terms</strong>
            <span>Add the budget you want to propose for this collaboration.</span>
        </div>
        <label for="budgetPropose<?php echo htmlspecialchars($suffix); ?>" class="form-label fw-semibold">Proposed budget</label>
        <div class="input-group">
            <span class="input-group-text">EUR</span>
            <input type="number" class="form-control" id="budgetPropose<?php echo htmlspecialchars($suffix); ?>" name="budgetPropose" step="0.01" value="<?php echo formFieldValue($form, 'budgetPropose'); ?>">
        </div>
    </section>
    <?php
}

function renderCreatorDeclineFields(array $form, $suffix, $isFinal = false)
{
    ?>
    <section class="creator-modal-section creator-modal-section-danger">
        <div class="creator-modal-section-head">
            <strong><?php echo $isFinal ? 'Withdrawal context' : 'Decline context'; ?></strong>
            <span><?php echo $isFinal ? 'Leave a final note if you want to explain why the latest terms do not work.' : 'A short reason helps the brand understand your decision.'; ?></span>
        </div>
        <label for="motifRefus<?php echo htmlspecialchars($suffix); ?>" class="form-label fw-semibold">Refusal reason</label>
        <textarea class="form-control" id="motifRefus<?php echo htmlspecialchars($suffix); ?>" name="motifRefus" rows="4" placeholder="Explain why you are declining this invitation."><?php echo formFieldValue($form, 'motifRefus'); ?></textarea>

        <div class="mt-3">
            <label for="messageMotivation<?php echo htmlspecialchars($suffix); ?>" class="form-label fw-semibold">Optional short note</label>
            <textarea class="form-control" id="messageMotivation<?php echo htmlspecialchars($suffix); ?>" name="messageMotivation" rows="3" placeholder="Add a short final note if you want to keep extra context."><?php echo formFieldValue($form, 'messageMotivation'); ?></textarea>
        </div>
    </section>
    <?php
}

if ($creatorId && $idCandidature === null && $idSource === null) {
    $feed = $controller->getCreatorOfferFeed($creatorId);
    foreach ($feed as $item) {
        if (!$item['condidature'] || $item['condidature']->canCreatorEdit()) {
            $idSource = (int) $item['source']['id'];
            $origin = (string) $item['source']['origin'];
            break;
        }
    }
}

if ($creatorId) {
    try {
        if ($idCandidature !== null) {
            $context = $controller->getCreatorCandidatureById($idCandidature, $creatorId);
        }

        if ($context) {
            $origin = (string) $context['source']['origin'];
            $idSource = (int) $context['source']['id'];
        }

        if ($idSource !== null) {
            if (!$context) {
                $context = $controller->getCreatorCandidatureBySource($creatorId, $origin, $idSource);
            }

            $source = $origin === 'par_offre'
                ? $controller->getOfferSourceById($idSource, $creatorId, $context !== null)
                : ($context['source'] ?? null);

            if (!$context && $source === null) {
                $error = 'The source for this candidature could not be found in your creator workspace.';
            }
        } elseif (!$context) {
            $error = 'Select a candidature or a source before opening this page.';
        }
    } catch (Throwable $exception) {
        $error = 'The candidature details could not be loaded right now.';
    }
} else {
    $error = 'No creator profile is available for this workspace.';
}

$condidature = $context['condidature'] ?? null;
$source = $source ?: ($context['source'] ?? null);
$brand = $source['brand'] ?? ($context['brand'] ?? ['nom' => '', 'email' => '']);
$negotiation = $context['negotiation'] ?? ['count' => 0, 'latest' => null, 'history' => []];
$negotiationOnly = $condidature && $condidature->canCreatorEditNegotiationOnly();
$lockedForCreator = $condidature && $condidature->isCreatorLocked();
$preferredMode = normalizeComposerMode($preferredMode);
$isOfferFlow = $origin === 'par_offre';

$form = [
    'responseMode' => $negotiationOnly
        ? 'negotiate'
        : ($condidature ? $condidature->getResponseMode() : ($preferredMode !== '' ? $preferredMode : 'accept')),
    'messageMotivation' => $condidature ? (string) $condidature->getMessageMotivation() : '',
    'budgetPropose' => $condidature ? (string) $condidature->getBudgetPropose() : (string) ($source['budgetPropose'] ?? ''),
    'delaiPropose' => $condidature && $condidature->getDelaiPropose()
        ? (string) $condidature->getDelaiPropose()
        : (string) computeDelayFallback($source),
    'dateDisponibilite' => $condidature ? (string) $condidature->getDateDisponibilite() : '',
    'conditionsCreateur' => $condidature ? (string) $condidature->getConditionsCreateur() : '',
    'cvPath' => $condidature ? (string) $condidature->getCvPath() : '',
    'portfolioUrl' => $condidature ? (string) $condidature->getPortfolioUrl() : '',
    'motifRefus' => $condidature ? (string) $condidature->getMotifRefus() : '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error && $creatorId && $idSource !== null) {
    $form = [
        'responseMode' => trim((string) ($_POST['responseMode'] ?? $form['responseMode'])),
        'messageMotivation' => trim((string) ($_POST['messageMotivation'] ?? '')),
        'budgetPropose' => trim((string) ($_POST['budgetPropose'] ?? '')),
        'delaiPropose' => trim((string) ($_POST['delaiPropose'] ?? '')),
        'dateDisponibilite' => trim((string) ($_POST['dateDisponibilite'] ?? '')),
        'conditionsCreateur' => trim((string) ($_POST['conditionsCreateur'] ?? '')),
        'cvPath' => trim((string) ($_POST['cvPath'] ?? '')),
        'portfolioUrl' => trim((string) ($_POST['portfolioUrl'] ?? '')),
        'motifRefus' => trim((string) ($_POST['motifRefus'] ?? '')),
    ];

    $submitIntent = trim((string) ($_POST['submitIntent'] ?? 'draft'));
    $result = $controller->saveCreatorCandidature($creatorId, $origin, $idSource, $submitIntent, $form, $condidature);

    if (!empty($result['success'])) {
        $savedContext = $result['context'] ?? null;
        $savedCondidature = $savedContext['condidature'] ?? null;
        $responseMode = $form['responseMode'];
        $notice = match (true) {
            $submitIntent === 'draft' => 'Draft candidature saved. You can continue it later.',
            $submitIntent === 'final_accept' => 'Negotiation closed. The latest terms were accepted without adding another negotiation message.',
            $submitIntent === 'final_decline' => 'Negotiation closed and the candidature was withdrawn without adding a redundant negotiation step.',
            $responseMode === 'negotiate' => 'Negotiation response sent successfully.',
            $responseMode === 'decline' => 'Decline response sent and kept in your candidature history.',
            default => 'Acceptance response sent successfully.',
        };
        $redirect = 'details.php?idCandidature=' . (int) ($savedCondidature ? $savedCondidature->getIdCandidature() : 0);
        $redirect .= '&notice=' . urlencode($notice);
        $redirect .= '&noticeType=' . urlencode(in_array($submitIntent, ['final_decline'], true) || $responseMode === 'decline' ? 'danger' : 'success');
        header('Location: ' . $redirect);
        exit;
    }

    $errors = $result['errors'] ?? ['Unable to save this candidature right now.'];
    $context = $result['context'] ?? $context;
    $condidature = $context['condidature'] ?? $condidature;
    $source = $result['source'] ?? $source;
    $brand = $source['brand'] ?? ($context['brand'] ?? $brand);
    $negotiation = $context['negotiation'] ?? $negotiation;
    $negotiationOnly = $condidature && $condidature->canCreatorEditNegotiationOnly();
    $lockedForCreator = $condidature && $condidature->isCreatorLocked();
    $isOfferFlow = ($source['origin'] ?? $origin) === 'par_offre';
}

$composerMode = normalizeComposerMode($form['responseMode']);
if ($composerMode === '') {
    $composerMode = $negotiationOnly ? 'negotiate' : 'accept';
}

$displayResponseMode = $condidature ? $condidature->getResponseMode() : $composerMode;
$latestBrandSignal = $negotiation['latestBrand'] ?? null;
$latestBrandMessage = trim((string) ($latestBrandSignal['message'] ?? ''));
$latestCreatorSignal = $negotiation['latestCreator'] ?? null;
$creatorCanFinalizeNegotiation = $negotiationOnly && $latestBrandSignal !== null;
$displayBudget = $condidature ? $condidature->getBudgetPropose() : ($source['budgetPropose'] ?? 0);
$displayDelay = $condidature ? $condidature->getDelaiPropose() : $form['delaiPropose'];
$displayAvailability = $condidature ? $condidature->getDateDisponibilite() : $form['dateDisponibilite'];
$displayTerms = $condidature ? $condidature->getConditionsCreateur() : $form['conditionsCreateur'];
$displayCvPath = $condidature ? $condidature->getCvPath() : $form['cvPath'];
$displayPortfolio = $condidature ? $condidature->getPortfolioUrl() : $form['portfolioUrl'];
$displayMotifRefus = $condidature ? $condidature->getMotifRefus() : $form['motifRefus'];
$responseSummaryTitle = $displayResponseMode === 'decline' ? 'Decline details' : 'Response details';
$composerAction = $idCandidature
    ? 'details.php?idCandidature=' . (int) $idCandidature
    : 'details.php?origin=' . urlencode($origin) . '&idSource=' . (int) $idSource;
$defaultCreatorModal = '';
if (!empty($errors) && !$lockedForCreator) {
    $defaultCreatorModal = $negotiationOnly ? 'negotiate' : $composerMode;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidature Details - Cre8Connect</title>
    <link rel="stylesheet" href="../css/frontoffice.css">
    <link rel="stylesheet" href="../offre/offre.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../offre/offre.css')); ?>">
    <link rel="stylesheet" href="condidature.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/condidature.css')); ?>">
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

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" role="alert">
                    <strong>Unable to save the candidature.</strong>
                    <ul class="mb-0 mt-2">
                        <?php foreach ($errors as $item): ?>
                            <li><?php echo htmlspecialchars($item); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <section class="empty-state-card">
                    <div class="empty-state-icon">!</div>
                    <h2 class="section-title">Candidature unavailable</h2>
                    <p class="section-subtitle"><?php echo htmlspecialchars($error); ?></p>
                    <div class="compact-actions justify-content-center mt-4">
                        <a class="btn btn-primary" href="index.php">Back to my candidatures</a>
                        <a class="btn btn-outline-secondary" href="../offre/creator_list.php">Open offer inbox</a>
                    </div>
                </section>
            <?php else: ?>
                <section class="module-hero">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                        <div>
                            <span class="module-eyebrow"><?php echo htmlspecialchars(translateOrigin($origin)); ?></span>
                            <h1 class="display-5 fw-bold mt-3 mb-2 gradient-title"><?php echo htmlspecialchars($source['title'] ?? 'Candidature source'); ?></h1>
                            <p class="lead text-muted mb-0">
                                <?php
                                $heroCopy = $isOfferFlow
                                    ? 'Turn this targeted offer into a structured candidature with the right response type, availability, delivery plan, and creator context.'
                                    : 'Review the current candidature, keep drafts moving, and follow the latest response state for this source.';
                                echo htmlspecialchars($heroCopy);
                                ?>
                            </p>
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <span class="origin-badge"><?php echo htmlspecialchars(translateOrigin($origin)); ?></span>
                            <span class="candidature-badge <?php echo htmlspecialchars(candidatureBadgeClass($condidature ? $condidature->getStatutCandidature() : 'brouillon')); ?>">
                                <?php echo htmlspecialchars($condidature ? $condidature->getDisplayStatusLabel() : 'No response started'); ?>
                            </span>
                        </div>
                    </div>
                    <div class="candidature-inline-meta mt-4">
                        <span class="offer-chip"><?php echo htmlspecialchars(formatMoney($displayBudget)); ?></span>
                        <span class="offer-chip">Source deadline: <?php echo htmlspecialchars(formatShortDate($source['dateLimite'] ?? null)); ?></span>
                        <span class="offer-chip">Availability: <?php echo htmlspecialchars(formatShortDate($displayAvailability, 'Not shared yet')); ?></span>
                        <span class="offer-chip">Last update: <?php echo htmlspecialchars(formatDateTimeLabel($condidature ? $condidature->getDateDerniereModification() : null, 'Not saved yet')); ?></span>
                    </div>
                </section>

                <div class="invitation-grid">
                    <div class="response-grid">
                        <section class="section-card">
                            <h2 class="section-title"><?php echo $isOfferFlow ? 'Offer response context' : 'Source response context'; ?></h2>
                            <p class="section-subtitle">Keep the source details visible while you shape the candidature response.</p>
                            <div class="offer-detail-list mt-4">
                                <div class="offer-detail-item">
                                    <strong>Brand</strong>
                                    <span><?php echo htmlspecialchars($brand['nom'] ?? 'Unknown brand'); ?></span>
                                    <p><?php echo htmlspecialchars($brand['email'] ?? ''); ?></p>
                                </div>
                                <div class="offer-detail-item">
                                    <strong><?php echo $isOfferFlow ? 'Offer objective' : 'Source objective'; ?></strong>
                                    <span><?php echo htmlspecialchars(blankToFallback($source['objective'] ?? '', 'No objective was added to this source.')); ?></span>
                                </div>
                                <div class="offer-detail-item">
                                    <strong>Publication date</strong>
                                    <span><?php echo htmlspecialchars(formatShortDate($source['datePublication'] ?? null)); ?></span>
                                </div>
                                <div class="offer-detail-item">
                                    <strong>Response window</strong>
                                    <span><?php echo htmlspecialchars(formatShortDate($source['dateLimite'] ?? null)); ?></span>
                                </div>
                            </div>
                        </section>

                        <div class="candidature-summary-grid">
                            <section class="info-card">
                                <h2 class="section-title">Candidature status</h2>
                                <div class="timeline-card mt-3">
                                    <strong><?php echo htmlspecialchars($condidature ? $condidature->getDisplayStatusLabel() : 'Not started yet'); ?></strong>
                                    <p>
                                        <?php
                                        $statusCopy = match (true) {
                                            !$condidature => 'No candidature has been created yet for this offer.',
                                            $condidature->isDraft() => 'This draft stays editable until you decide to send it.',
                                            $condidature->canCreatorEditNegotiationOnly() => 'Only negotiation-related fields can be updated right now.',
                                            $condidature->isFinal() => 'This response is final and can no longer be edited from the creator side.',
                                            default => 'This candidature has been sent and is now moving through the brand review workflow.',
                                        };
                                        echo htmlspecialchars($statusCopy);
                                        ?>
                                    </p>
                                </div>
                            </section>
                            <section class="info-card">
                                <h2 class="section-title">Response type</h2>
                                <div class="timeline-card mt-3">
                                    <strong><?php echo htmlspecialchars($condidature ? $condidature->getResponseTypeLabel() : responseModeCardLabel($composerMode)); ?></strong>
                                    <p>
                                        Budget: <?php echo htmlspecialchars(formatMoney($displayBudget)); ?><br>
                                        Delivery: <?php echo htmlspecialchars(formatDelayLabel($displayDelay)); ?><br>
                                        Negotiation messages: <?php echo (int) ($negotiation['count'] ?? 0); ?>
                                    </p>
                                </div>
                            </section>
                        </div>

                        <section class="section-card">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                                <div>
                                    <h2 class="section-title"><?php echo htmlspecialchars($responseSummaryTitle); ?></h2>
                                    <p class="section-subtitle">The structured response fields stay visible here so you can review what was sent with the candidature.</p>
                                </div>
                                <?php if ($condidature): ?>
                                    <span class="offer-chip"><?php echo htmlspecialchars($condidature->getResponseTypeLabel()); ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="decision-note-grid mt-4">
                                <section class="decision-note-card">
                                    <strong><?php echo $displayResponseMode === 'decline' ? 'Refusal reason' : 'Creator message'; ?></strong>
                                    <p>
                                        <?php
                                        $primaryMessage = $displayResponseMode === 'decline'
                                            ? blankToFallback($displayMotifRefus, 'No refusal reason was added.')
                                            : blankToFallback($condidature ? $condidature->getMessageMotivation() : $form['messageMotivation'], 'No creator message has been written yet.');
                                        echo htmlspecialchars($primaryMessage);
                                        ?>
                                    </p>
                                </section>
                                <section class="decision-note-card">
                                    <strong><?php echo $displayResponseMode === 'decline' ? 'Optional note' : 'Creator terms'; ?></strong>
                                    <p>
                                        <?php
                                        $secondaryMessage = $displayResponseMode === 'decline'
                                            ? blankToFallback($condidature ? $condidature->getMessageMotivation() : $form['messageMotivation'], 'No extra note was attached to this decline response.')
                                            : blankToFallback($displayTerms, 'No creator terms were attached to this candidature.');
                                        echo htmlspecialchars($secondaryMessage);
                                        ?>
                                    </p>
                                </section>
                            </div>

                            <?php if ($displayResponseMode !== 'decline'): ?>
                                <div class="decision-note-grid mt-4">
                                    <section class="decision-note-card">
                                        <strong>Availability start date</strong>
                                        <p><?php echo htmlspecialchars(formatShortDate($displayAvailability, 'Not shared yet')); ?></p>
                                    </section>
                                    <section class="decision-note-card">
                                        <strong>Delivery plan</strong>
                                        <p><?php echo htmlspecialchars(formatDelayLabel($displayDelay)); ?></p>
                                    </section>
                                </div>

                                <div class="decision-note-grid mt-4">
                                    <section class="decision-note-card">
                                        <strong>CV reference</strong>
                                        <p><?php echo htmlspecialchars(blankToFallback($displayCvPath, 'No CV reference was attached.')); ?></p>
                                    </section>
                                    <section class="decision-note-card">
                                        <strong>Portfolio URL</strong>
                                        <p>
                                            <?php if (trim((string) $displayPortfolio) !== ''): ?>
                                                <a class="inline-link" href="<?php echo htmlspecialchars($displayPortfolio); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($displayPortfolio); ?></a>
                                            <?php else: ?>
                                                No portfolio URL was attached.
                                            <?php endif; ?>
                                        </p>
                                    </section>
                                </div>
                            <?php endif; ?>
                        </section>

                        <section class="section-card">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                                <div>
                                    <h2 class="section-title">Negotiation history</h2>
                                    <p class="section-subtitle">Every negotiation message stays attached to this candidature so you can follow the full exchange in order.</p>
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
                                                <span class="offer-chip"><?php echo htmlspecialchars(formatDateTimeLabel($entry['dateMessage'] ?? null)); ?></span>
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
                                    <p>The thread will appear here once you or the brand send a negotiation update.</p>
                                </div>
                            <?php endif; ?>
                        </section>
                    </div>

                    <aside class="response-grid">
                        <?php if ($lockedForCreator): ?>
                            <section class="locked-state-card">
                                <h3>Editing is locked</h3>
                                <p>
                                    This candidature has already moved past the editable stage. You can keep reviewing the response details and negotiation history, but the creator form can no longer be changed from here.
                                </p>
                                <?php if ($condidature && $condidature->getDateDecision()): ?>
                                    <div class="candidature-inline-meta">
                                        <span class="offer-chip">Decision date: <?php echo htmlspecialchars(formatDateTimeLabel($condidature->getDateDecision())); ?></span>
                                    </div>
                                <?php endif; ?>
                            </section>
                        <?php else: ?>
                            <section class="composer-card brand-action-card creator-response-action-card">
                                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                                    <div>
                                        <h2 class="section-title"><?php echo $isOfferFlow ? 'Creator response actions' : 'Response actions'; ?></h2>
                                        <p class="section-subtitle">
                                            <?php echo htmlspecialchars($negotiationOnly
                                                ? 'Choose a final answer or send a real counter-proposal from a focused action window.'
                                                : 'Choose the response path first. Each action opens a focused window with only the fields needed for that decision.'); ?>
                                        </p>
                                    </div>
                                    <?php if ($condidature && $condidature->isDraft()): ?>
                                        <span class="candidature-badge status-draft"><?php echo htmlspecialchars($condidature->getDisplayStatusLabel()); ?></span>
                                    <?php elseif ($negotiationOnly): ?>
                                        <span class="candidature-badge status-negotiation">Negotiation open</span>
                                    <?php endif; ?>
                                </div>

                                <?php if ($negotiationOnly && $latestBrandMessage !== ''): ?>
                                    <div class="response-callout response-callout-review mt-4">
                                        <strong>Latest brand negotiation update</strong>
                                        <div class="mt-2 candidature-signal-copy"><?php echo htmlspecialchars($latestBrandMessage); ?></div>
                                        <div class="candidature-inline-meta mt-2">
                                            <?php if (!empty($latestBrandSignal['dateMessage'])): ?>
                                                <span class="offer-chip"><?php echo htmlspecialchars(formatDateTimeLabel($latestBrandSignal['dateMessage'])); ?></span>
                                            <?php endif; ?>
                                            <?php if ($latestBrandSignal['budgetPropose'] !== null): ?>
                                                <span class="offer-chip"><?php echo htmlspecialchars(formatMoney($latestBrandSignal['budgetPropose'])); ?></span>
                                            <?php endif; ?>
                                            <?php if ($latestBrandSignal['delaiPropose'] !== null): ?>
                                                <span class="offer-chip">Timeline: <?php echo (int) $latestBrandSignal['delaiPropose']; ?> days</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="brand-action-grid creator-action-grid mt-4">
                                    <?php if ($negotiationOnly): ?>
                                        <?php if ($creatorCanFinalizeNegotiation): ?>
                                            <button type="button" class="brand-action-launch brand-action-launch-accept" data-creator-response-modal-trigger="final_accept">
                                                <strong>Accept latest terms</strong>
                                                <span>End the negotiation cleanly without adding a duplicate message.</span>
                                            </button>
                                        <?php endif; ?>
                                        <button type="button" class="brand-action-launch brand-action-launch-refuse" data-creator-response-modal-trigger="decline">
                                            <strong>Decline</strong>
                                            <span>Withdraw from the current negotiation and keep the history readable.</span>
                                        </button>
                                        <button type="button" class="brand-action-launch brand-action-launch-negotiate" data-creator-response-modal-trigger="negotiate">
                                            <strong>Negotiate</strong>
                                            <span>Send a real adjustment to the message, budget, or timeline.</span>
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="brand-action-launch brand-action-launch-accept" data-creator-response-modal-trigger="accept">
                                            <strong>Accept</strong>
                                            <span>Send your availability, delivery plan, message, and profile references.</span>
                                        </button>
                                        <button type="button" class="brand-action-launch brand-action-launch-refuse" data-creator-response-modal-trigger="decline">
                                            <strong>Decline</strong>
                                            <span>Send a clean refusal reason and keep the invitation in your history.</span>
                                        </button>
                                        <button type="button" class="brand-action-launch brand-action-launch-negotiate" data-creator-response-modal-trigger="negotiate">
                                            <strong>Negotiate</strong>
                                            <span>Propose revised budget, timing, or collaboration context.</span>
                                        </button>
                                    <?php endif; ?>
                                </div>

                                <p class="composer-context-note mt-4">
                                    <?php echo $negotiationOnly
                                        ? 'Negotiation messages should only be used for real changes. If the latest terms work, accept them as a final decision.'
                                        : 'You can save a draft inside any action window if you want to finish the response later.'; ?>
                                </p>

                                <div
                                    class="response-modal-overlay"
                                    data-creator-response-modal-overlay
                                    data-default-modal="<?php echo htmlspecialchars($defaultCreatorModal); ?>"
                                    hidden
                                    aria-hidden="true"
                                >
                                    <?php if (!$negotiationOnly): ?>
                                        <div class="response-modal-panel" data-creator-response-modal-panel="accept" hidden>
                                            <form method="post" action="<?php echo htmlspecialchars($composerAction); ?>" class="response-modal-card creator-response-modal-card" data-modal-variant="accept" role="dialog" aria-modal="true" aria-labelledby="creatorAcceptModalTitle">
                                                <input type="hidden" name="responseMode" value="accept">
                                                <div class="response-modal-header">
                                                    <span class="response-modal-kicker">Acceptance</span>
                                                    <h2 id="creatorAcceptModalTitle">Accept this offer?</h2>
                                                    <p class="response-modal-subtitle">Confirm your interest and send a structured candidature response to the brand.</p>
                                                </div>
                                                <div class="response-modal-body">
                                                    <div class="response-modal-preview">
                                                        <span class="response-modal-preview-label">Selected offer</span>
                                                        <strong><?php echo htmlspecialchars($source['title'] ?? 'Candidature source'); ?></strong>
                                                        <span><?php echo htmlspecialchars($brand['nom'] ?? 'Unknown brand'); ?> | <?php echo htmlspecialchars(formatMoney($source['budgetPropose'] ?? $displayBudget)); ?></span>
                                                    </div>
                                                    <?php renderCreatorPlanFields($form, 'Accept', 'accept'); ?>
                                                    <div class="response-modal-actions">
                                                        <button type="button" class="response-modal-secondary" data-creator-response-modal-close>Keep reviewing</button>
                                                        <button type="submit" name="submitIntent" value="draft" class="response-modal-secondary">Save as draft</button>
                                                        <button type="submit" name="submitIntent" value="send" class="response-modal-primary">Send acceptance</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($negotiationOnly && $creatorCanFinalizeNegotiation): ?>
                                        <div class="response-modal-panel" data-creator-response-modal-panel="final_accept" hidden>
                                            <form method="post" action="<?php echo htmlspecialchars($composerAction); ?>" class="response-modal-card creator-response-modal-card" data-modal-variant="accept" role="dialog" aria-modal="true" aria-labelledby="creatorFinalAcceptModalTitle">
                                                <input type="hidden" name="responseMode" value="accept">
                                                <div class="response-modal-header">
                                                    <span class="response-modal-kicker">Final decision</span>
                                                    <h2 id="creatorFinalAcceptModalTitle">Accept latest terms?</h2>
                                                    <p class="response-modal-subtitle">Close the negotiation as accepted without creating another duplicate proposal message.</p>
                                                </div>
                                                <div class="response-modal-body">
                                                    <div class="response-modal-preview">
                                                        <span class="response-modal-preview-label">Latest brand terms</span>
                                                        <strong><?php echo htmlspecialchars($latestBrandMessage !== '' ? $latestBrandMessage : 'The latest brand terms are ready for your final answer.'); ?></strong>
                                                        <span><?php echo htmlspecialchars(formatMoney($latestBrandSignal['budgetPropose'] ?? $displayBudget)); ?> | Timeline: <?php echo (int) ($latestBrandSignal['delaiPropose'] ?? $displayDelay); ?> days</span>
                                                    </div>
                                                    <p class="response-modal-copy">This will update the candidature status and decision date. It will not add another negotiation history message.</p>
                                                    <div class="response-modal-actions">
                                                        <button type="button" class="response-modal-secondary" data-creator-response-modal-close>Keep reviewing</button>
                                                        <button type="submit" name="submitIntent" value="final_accept" class="response-modal-primary">Accept latest terms</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    <?php endif; ?>

                                    <div class="response-modal-panel" data-creator-response-modal-panel="decline" hidden>
                                        <form method="post" action="<?php echo htmlspecialchars($composerAction); ?>" class="response-modal-card creator-response-modal-card" data-modal-variant="refuse" role="dialog" aria-modal="true" aria-labelledby="creatorDeclineModalTitle">
                                            <input type="hidden" name="responseMode" value="decline">
                                            <div class="response-modal-header">
                                                <span class="response-modal-kicker"><?php echo $negotiationOnly ? 'Withdrawal' : 'Decline'; ?></span>
                                                <h2 id="creatorDeclineModalTitle"><?php echo $negotiationOnly ? 'Decline latest terms?' : 'Decline this offer?'; ?></h2>
                                                <p class="response-modal-subtitle"><?php echo $negotiationOnly ? 'Close your side of the negotiation while keeping the saved history visible.' : 'Send a clean refusal response without leaving this page.'; ?></p>
                                            </div>
                                            <div class="response-modal-body">
                                                <div class="response-modal-preview">
                                                    <span class="response-modal-preview-label">Selected offer</span>
                                                    <strong><?php echo htmlspecialchars($source['title'] ?? 'Candidature source'); ?></strong>
                                                    <span><?php echo htmlspecialchars($brand['nom'] ?? 'Unknown brand'); ?></span>
                                                </div>
                                                <?php renderCreatorDeclineFields($form, 'Decline', $negotiationOnly); ?>
                                                <div class="response-modal-actions">
                                                    <button type="button" class="response-modal-secondary" data-creator-response-modal-close>Keep reviewing</button>
                                                    <?php if (!$negotiationOnly): ?>
                                                        <button type="submit" name="submitIntent" value="draft" class="response-modal-secondary">Save as draft</button>
                                                        <button type="submit" name="submitIntent" value="send" class="response-modal-primary">Send decline</button>
                                                    <?php else: ?>
                                                        <button type="submit" name="submitIntent" value="final_decline" class="response-modal-primary">Decline latest terms</button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </form>
                                    </div>

                                    <div class="response-modal-panel" data-creator-response-modal-panel="negotiate" hidden>
                                        <form method="post" action="<?php echo htmlspecialchars($composerAction); ?>" class="response-modal-card creator-response-modal-card" data-modal-variant="negotiate" role="dialog" aria-modal="true" aria-labelledby="creatorNegotiateModalTitle">
                                            <input type="hidden" name="responseMode" value="negotiate">
                                            <div class="response-modal-header">
                                                <span class="response-modal-kicker">Negotiation</span>
                                                <h2 id="creatorNegotiateModalTitle"><?php echo $negotiationOnly ? 'Send a counter-proposal' : 'Negotiate this offer'; ?></h2>
                                                <p class="response-modal-subtitle">Use this only when you are proposing a real change to the current terms.</p>
                                            </div>
                                            <div class="response-modal-body">
                                                <?php if ($negotiationOnly && $latestBrandMessage !== ''): ?>
                                                    <div class="response-modal-preview">
                                                        <span class="response-modal-preview-label">Latest brand update</span>
                                                        <strong><?php echo htmlspecialchars($latestBrandMessage); ?></strong>
                                                        <span><?php echo htmlspecialchars(formatDateTimeLabel($latestBrandSignal['dateMessage'] ?? null)); ?></span>
                                                    </div>
                                                    <section class="creator-modal-section creator-modal-section-warning">
                                                        <div class="creator-modal-section-head">
                                                            <strong>Counter-proposal</strong>
                                                            <span>Change the message, budget, or timeline before sending another negotiation step.</span>
                                                        </div>
                                                        <label for="messageMotivationNegotiationOnly" class="form-label fw-semibold">Negotiation message</label>
                                                        <textarea class="form-control" id="messageMotivationNegotiationOnly" name="messageMotivation" rows="4" placeholder="Reply to the latest brand update and explain the new terms you want to propose."><?php echo formFieldValue($form, 'messageMotivation'); ?></textarea>
                                                        <div class="response-modal-field-grid mt-3">
                                                            <div>
                                                                <label for="budgetProposeNegotiationOnly" class="form-label fw-semibold">Proposed budget</label>
                                                                <div class="input-group">
                                                                    <span class="input-group-text">EUR</span>
                                                                    <input type="number" class="form-control" id="budgetProposeNegotiationOnly" name="budgetPropose" step="0.01" value="<?php echo formFieldValue($form, 'budgetPropose'); ?>">
                                                                </div>
                                                            </div>
                                                            <div>
                                                                <label for="delaiProposeNegotiationOnly" class="form-label fw-semibold">Proposed timeline</label>
                                                                <input type="number" class="form-control" id="delaiProposeNegotiationOnly" name="delaiPropose" min="1" step="1" value="<?php echo formFieldValue($form, 'delaiPropose'); ?>">
                                                            </div>
                                                        </div>
                                                    </section>
                                                <?php else: ?>
                                                    <div class="response-modal-preview">
                                                        <span class="response-modal-preview-label">Selected offer</span>
                                                        <strong><?php echo htmlspecialchars($source['title'] ?? 'Candidature source'); ?></strong>
                                                        <span><?php echo htmlspecialchars($brand['nom'] ?? 'Unknown brand'); ?> | Current budget: <?php echo htmlspecialchars(formatMoney($source['budgetPropose'] ?? $displayBudget)); ?></span>
                                                    </div>
                                                    <?php renderCreatorPlanFields($form, 'Negotiate', 'negotiate'); ?>
                                                    <?php renderCreatorBudgetField($form, 'Negotiate'); ?>
                                                <?php endif; ?>
                                                <div class="response-modal-actions">
                                                    <button type="button" class="response-modal-secondary" data-creator-response-modal-close>Keep reviewing</button>
                                                    <?php if (!$negotiationOnly): ?>
                                                        <button type="submit" name="submitIntent" value="draft" class="response-modal-secondary">Save as draft</button>
                                                    <?php endif; ?>
                                                    <button type="submit" name="submitIntent" value="send" class="response-modal-primary response-modal-primary-negotiate"><?php echo $negotiationOnly ? 'Send counter-proposal' : 'Send negotiation'; ?></button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </section>
                        <?php endif; ?>

                        <section class="info-card">
                            <h2 class="section-title">Dates and review markers</h2>
                            <div class="decision-note-card mt-3">
                                <strong>Timeline</strong>
                                <p>
                                    Source published: <?php echo htmlspecialchars(formatShortDate($source['datePublication'] ?? null)); ?><br>
                                    Source deadline: <?php echo htmlspecialchars(formatShortDate($source['dateLimite'] ?? null)); ?><br>
                                    Candidature submitted: <?php echo htmlspecialchars(formatShortDate($condidature ? $condidature->getDateCandidature() : null, 'Not submitted yet')); ?><br>
                                    Decision date: <?php echo htmlspecialchars(formatDateTimeLabel($condidature ? $condidature->getDateDecision() : null, 'No decision yet')); ?>
                                </p>
                            </div>
                        </section>

                        <div class="compact-actions">
                            <a class="btn btn-outline-secondary w-100" href="index.php">Back to my candidatures</a>
                            <?php if ($origin === 'par_offre'): ?>
                                <a class="btn btn-outline-secondary w-100" href="../offre/creator_details.php?idOffre=<?php echo (int) $idSource; ?>&idCreateur=<?php echo (int) $creatorId; ?>">View source offer</a>
                            <?php endif; ?>
                        </div>
                    </aside>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
    <script src="creator-actions-modal.js?v=<?php echo urlencode((string) filemtime(__DIR__ . '/creator-actions-modal.js')); ?>"></script>
</body>
</html>
