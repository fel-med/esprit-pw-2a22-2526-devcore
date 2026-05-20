<?php
require_once __DIR__ . '/../layout/session_bridge.php';
$currentUser = cre8_front_require_user('createur');
$frontActive = 'collaborations';

require_once __DIR__ . '/../layout/avatar_helper.php';
require_once __DIR__ . '/../../../Controleur/condidatureC.php';

$controller = new CondidatureC();
$sessionUser = $currentUser;

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
        'par_campagne' => 'Campaign application',
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

function responseModeCardLabelForOrigin($mode, $origin)
{
    if ((string) $origin === 'par_campagne' && (string) $mode === 'accept') {
        return 'Campaign application';
    }

    return responseModeCardLabel($mode);
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

function saveUploadedCandidatureFile($existingPath, array &$uploadErrors)
{
    if (!isset($_FILES['cvFile']) || !is_array($_FILES['cvFile'])) {
        return trim((string) $existingPath);
    }

    $file = $_FILES['cvFile'];
    $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($errorCode === UPLOAD_ERR_NO_FILE) {
        return trim((string) $existingPath);
    }

    if ($errorCode !== UPLOAD_ERR_OK) {
        $uploadErrors[] = 'The CV/reference file could not be uploaded. Please try again.';
        return trim((string) $existingPath);
    }

    $maxSize = 5 * 1024 * 1024;
    if ((int) ($file['size'] ?? 0) > $maxSize) {
        $uploadErrors[] = 'The CV/reference file must be 5 MB or smaller.';
        return trim((string) $existingPath);
    }

    $originalName = (string) ($file['name'] ?? '');
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowedExtensions = ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg'];
    if (!in_array($extension, $allowedExtensions, true)) {
        $uploadErrors[] = 'Upload a CV/reference file as PDF, DOC, DOCX, PNG, JPG, or JPEG.';
        return trim((string) $existingPath);
    }

    $temporaryPath = (string) ($file['tmp_name'] ?? '');
    if ($temporaryPath === '' || !is_uploaded_file($temporaryPath)) {
        $uploadErrors[] = 'The uploaded CV/reference file could not be verified.';
        return trim((string) $existingPath);
    }

    $uploadDirectory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'condidature';
    if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0775, true) && !is_dir($uploadDirectory)) {
        $uploadErrors[] = 'The upload folder is not available right now.';
        return trim((string) $existingPath);
    }

    $baseName = pathinfo($originalName, PATHINFO_FILENAME);
    $safeBaseName = trim((string) preg_replace('/[^a-zA-Z0-9_-]+/', '-', $baseName), '-');
    if ($safeBaseName === '') {
        $safeBaseName = 'creator-file';
    }

    try {
        $randomSuffix = bin2hex(random_bytes(4));
    } catch (Throwable $exception) {
        $randomSuffix = uniqid('', true);
    }

    $fileName = 'candidature_' . date('Ymd_His') . '_' . $randomSuffix . '_' . $safeBaseName . '.' . $extension;
    $targetPath = $uploadDirectory . DIRECTORY_SEPARATOR . $fileName;

    if (!move_uploaded_file($temporaryPath, $targetPath)) {
        $uploadErrors[] = 'The CV/reference file could not be saved. Please try again.';
        return trim((string) $existingPath);
    }

    return 'Vue/public/uploads/condidature/' . $fileName;
}

function renderCreatorPlanFields(array $form, $suffix, $messageMode = 'accept')
{
    $isNegotiation = $messageMode === 'negotiate';
    $currentCvPath = trim((string) ($form['cvPath'] ?? ''));
    $currentCvHref = candidatureStoredFileHref($currentCvPath);
    $currentCvName = candidatureStoredFileName($currentCvPath);
    ?>
    <section class="creator-modal-section">
        <div class="creator-modal-section-head">
            <strong data-i18n="cand.availabilityDelivery">Availability and delivery</strong>
            <span data-i18n="cand.availabilityDeliveryCopy">Share when you can start and how long the delivery should take.</span>
        </div>
        <div class="response-modal-field-grid">
            <div>
                <label for="dateDisponibilite<?php echo htmlspecialchars($suffix); ?>" class="form-label fw-semibold" data-i18n="cand.availabilityStart">Availability start date</label>
                <input type="date" class="form-control" id="dateDisponibilite<?php echo htmlspecialchars($suffix); ?>" name="dateDisponibilite" data-cre8pilot-field="dateDisponibilite" value="<?php echo formFieldValue($form, 'dateDisponibilite'); ?>">
            </div>
            <div>
                <label for="delaiPropose<?php echo htmlspecialchars($suffix); ?>" class="form-label fw-semibold" data-i18n="<?php echo $isNegotiation ? 'cand.proposedDelay' : 'cand.deliveryDelay'; ?>"><?php echo $isNegotiation ? 'Proposed delay' : 'Delivery delay'; ?></label>
                <input type="number" class="form-control" id="delaiPropose<?php echo htmlspecialchars($suffix); ?>" name="delaiPropose" min="1" step="1" data-cre8pilot-field="delaiPropose" value="<?php echo formFieldValue($form, 'delaiPropose'); ?>">
            </div>
        </div>
    </section>

    <section class="creator-modal-section">
        <div class="creator-modal-section-head">
            <strong data-i18n="<?php echo $isNegotiation ? 'cand.negotiationMessage' : 'cand.creatorMessage'; ?>"><?php echo $isNegotiation ? 'Negotiation message' : 'Creator message'; ?></strong>
            <span data-i18n="<?php echo $isNegotiation ? 'cand.adjustmentExplain' : 'cand.shortContextFirst'; ?>"><?php echo $isNegotiation ? 'Explain the adjustment you want to propose.' : 'Add the short context the brand should read first.'; ?></span>
        </div>
        <label for="messageMotivation<?php echo htmlspecialchars($suffix); ?>" class="form-label fw-semibold" data-i18n="<?php echo $isNegotiation ? 'cand.negotiationMessage' : 'cand.messageMotivation'; ?>"><?php echo $isNegotiation ? 'Negotiation message' : 'Creator message / motivation'; ?></label>
        <textarea class="form-control" id="messageMotivation<?php echo htmlspecialchars($suffix); ?>" name="messageMotivation" rows="4" data-cre8pilot-field="messageMotivation" placeholder="<?php echo $isNegotiation ? 'Explain the revised budget, timing, or collaboration context.' : 'Explain why this collaboration fits your content, audience, or execution approach.'; ?>"><?php echo formFieldValue($form, 'messageMotivation'); ?></textarea>

        <div class="mt-3">
            <label for="conditionsCreateur<?php echo htmlspecialchars($suffix); ?>" class="form-label fw-semibold" data-i18n="cand.creatorTerms">Creator terms and conditions</label>
            <textarea class="form-control" id="conditionsCreateur<?php echo htmlspecialchars($suffix); ?>" name="conditionsCreateur" rows="3" data-cre8pilot-field="conditionsCreateur" placeholder="Add optional creator-side conditions, process notes, or collaboration boundaries."><?php echo formFieldValue($form, 'conditionsCreateur'); ?></textarea>
        </div>
    </section>

    <section class="creator-modal-section">
        <div class="creator-modal-section-head">
            <strong data-i18n="cand.profileSupport">Profile support</strong>
            <span data-i18n="cand.profileSupportCopy">Attach simple references the brand can review with your response.</span>
        </div>
        <div class="response-modal-field-grid">
            <div>
                <label for="cvFile<?php echo htmlspecialchars($suffix); ?>" class="form-label fw-semibold" data-i18n="cand.cvFile">CV or reference file</label>
                <input type="hidden" name="existingCvPath" value="<?php echo formFieldValue($form, 'cvPath'); ?>">
                <input type="file" class="form-control" id="cvFile<?php echo htmlspecialchars($suffix); ?>" name="cvFile" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg">
                <small class="upload-helper">PDF, DOC, DOCX, PNG, JPG, or JPEG. Max 5 MB.</small>
                <?php if ($currentCvPath !== ''): ?>
                    <a class="current-upload-link" href="<?php echo htmlspecialchars($currentCvHref); ?>" target="_blank" rel="noopener noreferrer">
                        Current file: <?php echo htmlspecialchars($currentCvName ?: $currentCvPath); ?>
                    </a>
                <?php endif; ?>
            </div>
            <div>
                <label for="portfolioUrl<?php echo htmlspecialchars($suffix); ?>" class="form-label fw-semibold" data-i18n="cand.portfolio">Portfolio URL</label>
                <input type="url" class="form-control" id="portfolioUrl<?php echo htmlspecialchars($suffix); ?>" name="portfolioUrl" data-cre8pilot-field="portfolioUrl" value="<?php echo formFieldValue($form, 'portfolioUrl'); ?>" placeholder="https://portfolio.example.com">
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
            <strong data-i18n="cand.negotiationTerms">Negotiation terms</strong>
            <span data-i18n="cand.negotiationTermsCopy">Add the budget you want to propose for this collaboration.</span>
        </div>
        <label for="budgetPropose<?php echo htmlspecialchars($suffix); ?>" class="form-label fw-semibold" data-i18n="cand.proposedBudget">Proposed budget</label>
        <div class="input-group">
            <span class="input-group-text">EUR</span>
            <input type="number" class="form-control" id="budgetPropose<?php echo htmlspecialchars($suffix); ?>" name="budgetPropose" step="0.01" data-cre8pilot-field="budgetPropose" value="<?php echo formFieldValue($form, 'budgetPropose'); ?>">
        </div>
    </section>
    <?php
}

function renderCampaignApplicationFields(array $form)
{
    $currentCvPath = trim((string) ($form['cvPath'] ?? ''));
    $currentCvHref = candidatureStoredFileHref($currentCvPath);
    $currentCvName = candidatureStoredFileName($currentCvPath);
    ?>
    <section class="creator-modal-section">
        <div class="creator-modal-section-head">
            <strong data-i18n="cand.applicationMessage">Application message</strong>
            <span data-i18n="cand.applicationMessageCopy">Explain why you want to join this campaign and what your content can bring to it.</span>
        </div>
        <label for="campaignMessageMotivation" class="form-label fw-semibold" data-i18n="cand.whyJoin">Why do you want to join this campaign?</label>
        <textarea class="form-control" id="campaignMessageMotivation" name="messageMotivation" rows="5" data-cre8pilot-field="messageMotivation" placeholder="Share your campaign fit, audience angle, and proposed execution."><?php echo formFieldValue($form, 'messageMotivation'); ?></textarea>
    </section>

    <section class="creator-modal-section">
        <div class="creator-modal-section-head">
            <strong data-i18n="cand.contentIdea">Your proposed content idea</strong>
            <span data-i18n="cand.contentIdeaCopy">Add the creator-side concept, terms, or production notes the brand should review.</span>
        </div>
        <label for="campaignConditionsCreateur" class="form-label fw-semibold" data-i18n="cand.creatorTermsShort">Creator terms</label>
        <textarea class="form-control" id="campaignConditionsCreateur" name="conditionsCreateur" rows="4" data-cre8pilot-field="conditionsCreateur" placeholder="Describe your content idea, creator terms, usage boundaries, or production process."><?php echo formFieldValue($form, 'conditionsCreateur'); ?></textarea>
    </section>

    <section class="creator-modal-section">
        <div class="creator-modal-section-head">
            <strong data-i18n="cand.availabilityProposal">Availability and proposal</strong>
            <span data-i18n="cand.timelineBudgetCampaign">Share your timeline and budget for this campaign application.</span>
        </div>
        <div class="response-modal-field-grid">
            <div>
                <label for="campaignDateDisponibilite" class="form-label fw-semibold" data-i18n="cand.availabilityDate">Availability date</label>
                <input type="date" class="form-control" id="campaignDateDisponibilite" name="dateDisponibilite" data-cre8pilot-field="dateDisponibilite" value="<?php echo formFieldValue($form, 'dateDisponibilite'); ?>">
            </div>
            <div>
                <label for="campaignDelaiPropose" class="form-label fw-semibold" data-i18n="cand.estimatedDelay">Estimated delivery delay</label>
                <input type="number" class="form-control" id="campaignDelaiPropose" name="delaiPropose" min="1" step="1" data-cre8pilot-field="delaiPropose" value="<?php echo formFieldValue($form, 'delaiPropose'); ?>">
            </div>
            <div>
                <label for="campaignBudgetPropose" class="form-label fw-semibold" data-i18n="cand.proposedBudget">Proposed budget</label>
                <div class="input-group">
                    <span class="input-group-text">EUR</span>
                    <input type="number" class="form-control" id="campaignBudgetPropose" name="budgetPropose" step="0.01" data-cre8pilot-field="budgetPropose" value="<?php echo formFieldValue($form, 'budgetPropose'); ?>">
                </div>
            </div>
        </div>
    </section>

    <section class="creator-modal-section">
        <div class="creator-modal-section-head">
            <strong data-i18n="cand.supportUpload">CV / profile support upload</strong>
            <span data-i18n="cand.attachReferencesPortfolio">Attach references and add a portfolio link for brand review.</span>
        </div>
        <div class="response-modal-field-grid">
            <div>
                <label for="campaignCvFile" class="form-label fw-semibold" data-i18n="cand.cvProfileSupportFile">CV or profile support file</label>
                <input type="hidden" name="existingCvPath" value="<?php echo formFieldValue($form, 'cvPath'); ?>">
                <input type="file" class="form-control" id="campaignCvFile" name="cvFile" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg">
                <small class="upload-helper">PDF, DOC, DOCX, PNG, JPG, or JPEG. Max 5 MB.</small>
                <?php if ($currentCvPath !== ''): ?>
                    <a class="current-upload-link" href="<?php echo htmlspecialchars($currentCvHref); ?>" target="_blank" rel="noopener noreferrer">
                        Current file: <?php echo htmlspecialchars($currentCvName ?: $currentCvPath); ?>
                    </a>
                <?php endif; ?>
            </div>
            <div>
                <label for="campaignPortfolioUrl" class="form-label fw-semibold" data-i18n="cand.portfolio">Portfolio URL</label>
                <input type="url" class="form-control" id="campaignPortfolioUrl" name="portfolioUrl" data-cre8pilot-field="portfolioUrl" value="<?php echo formFieldValue($form, 'portfolioUrl'); ?>" placeholder="https://portfolio.example.com">
            </div>
        </div>
    </section>
    <?php
}

function renderCreatorDeclineFields(array $form, $suffix, $isFinal = false)
{
    ?>
    <section class="creator-modal-section creator-modal-section-danger">
        <div class="creator-modal-section-head">
            <strong data-i18n="<?php echo $isFinal ? 'cand.withdrawalContext' : 'cand.declineContext'; ?>"><?php echo $isFinal ? 'Withdrawal context' : 'Decline context'; ?></strong>
            <span data-i18n="<?php echo $isFinal ? 'cand.finalNoteLatestTerms' : 'cand.shortReasonDecision'; ?>"><?php echo $isFinal ? 'Leave a final note if you want to explain why the latest terms do not work.' : 'A short reason helps the brand understand your decision.'; ?></span>
        </div>
        <label for="motifRefus<?php echo htmlspecialchars($suffix); ?>" class="form-label fw-semibold" data-i18n="cand.refusalReason">Refusal reason</label>
        <textarea class="form-control" id="motifRefus<?php echo htmlspecialchars($suffix); ?>" name="motifRefus" rows="4" data-cre8pilot-field="motifRefus" placeholder="Explain why you are declining this invitation."><?php echo formFieldValue($form, 'motifRefus'); ?></textarea>

        <div class="mt-3">
            <label for="messageMotivation<?php echo htmlspecialchars($suffix); ?>" class="form-label fw-semibold" data-i18n="cand.optionalShortNote">Optional short note</label>
            <textarea class="form-control" id="messageMotivation<?php echo htmlspecialchars($suffix); ?>" name="messageMotivation" rows="3" data-cre8pilot-field="messageMotivation" placeholder="Add a short final note if you want to keep extra context."><?php echo formFieldValue($form, 'messageMotivation'); ?></textarea>
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
                : $controller->getCampaignSourceById($idSource);

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
$isCampaignFlow = $origin === 'par_campagne';

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

if ($isCampaignFlow && !$condidature && (float) ($form['budgetPropose'] ?: 0) <= 0) {
    $form['budgetPropose'] = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error && $creatorId && $idSource !== null) {
    $form = [
        'responseMode' => trim((string) ($_POST['responseMode'] ?? $form['responseMode'])),
        'messageMotivation' => trim((string) ($_POST['messageMotivation'] ?? '')),
        'budgetPropose' => trim((string) ($_POST['budgetPropose'] ?? '')),
        'delaiPropose' => trim((string) ($_POST['delaiPropose'] ?? '')),
        'dateDisponibilite' => trim((string) ($_POST['dateDisponibilite'] ?? '')),
        'conditionsCreateur' => trim((string) ($_POST['conditionsCreateur'] ?? '')),
        'cvPath' => trim((string) ($_POST['existingCvPath'] ?? ($form['cvPath'] ?? ''))),
        'portfolioUrl' => trim((string) ($_POST['portfolioUrl'] ?? '')),
        'motifRefus' => trim((string) ($_POST['motifRefus'] ?? '')),
    ];

    $submitIntent = trim((string) ($_POST['submitIntent'] ?? 'draft'));
    $uploadErrors = [];
    if (in_array($form['responseMode'], ['accept', 'negotiate'], true)) {
        $form['cvPath'] = saveUploadedCandidatureFile($form['cvPath'], $uploadErrors);
    }

    $result = !empty($uploadErrors)
        ? ['success' => false, 'errors' => $uploadErrors, 'context' => $context, 'source' => $source]
        : $controller->saveCreatorCandidature($creatorId, $origin, $idSource, $submitIntent, $form, $condidature);

    if (!empty($result['success'])) {
        $savedContext = $result['context'] ?? null;
        $savedCondidature = $savedContext['condidature'] ?? null;
        $responseMode = $form['responseMode'];
        $notice = match (true) {
            $submitIntent === 'draft' => $isCampaignFlow ? 'Campaign application draft saved. You can continue it later.' : 'Draft candidature saved. You can continue it later.',
            $submitIntent === 'final_accept' => 'Negotiation closed. The latest terms were accepted without adding another negotiation message.',
            $submitIntent === 'final_decline' => 'Negotiation closed and the candidature was withdrawn without adding a redundant negotiation step.',
            $responseMode === 'negotiate' => 'Negotiation response sent successfully.',
            $responseMode === 'decline' => 'Decline response sent and kept in your candidature history.',
            $isCampaignFlow => 'Campaign application sent successfully.',
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
    $isCampaignFlow = ($source['origin'] ?? $origin) === 'par_campagne';
}

$composerMode = normalizeComposerMode($form['responseMode']);
if ($composerMode === '') {
    $composerMode = $negotiationOnly ? 'negotiate' : 'accept';
}

$displayResponseMode = $condidature ? $condidature->getResponseMode() : $composerMode;
$latestNegotiationEntry = $negotiation['latest'] ?? null;
$creatorIsLatestNegotiationSender = $latestNegotiationEntry && ($latestNegotiationEntry['auteur'] ?? '') === 'createur';
$latestBrandSignal = $negotiation['latestBrand'] ?? null;
$latestBrandMessage = trim((string) ($latestBrandSignal['message'] ?? ''));
$latestCreatorSignal = $negotiation['latestCreator'] ?? null;
$creatorCanFinalizeNegotiation = $negotiationOnly && $latestBrandSignal !== null && !$creatorIsLatestNegotiationSender;
$displayBudget = $condidature ? $condidature->getBudgetPropose() : ($source['budgetPropose'] ?? 0);
$displayDelay = $condidature ? $condidature->getDelaiPropose() : $form['delaiPropose'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $negotiationOnly && $creatorIsLatestNegotiationSender) {
    $form['messageMotivation'] = (string) ($latestNegotiationEntry['message'] ?? $form['messageMotivation']);
    if (($latestNegotiationEntry['budgetPropose'] ?? null) !== null) {
        $form['budgetPropose'] = (string) $latestNegotiationEntry['budgetPropose'];
    }
    if (($latestNegotiationEntry['delaiPropose'] ?? null) !== null) {
        $form['delaiPropose'] = (string) $latestNegotiationEntry['delaiPropose'];
    }
}

$creatorNegotiationBaselineMessage = $creatorIsLatestNegotiationSender
    ? (string) ($latestNegotiationEntry['message'] ?? '')
    : (string) ($condidature ? $condidature->getMessageMotivation() : '');
$creatorNegotiationBaselineBudget = $creatorIsLatestNegotiationSender && ($latestNegotiationEntry['budgetPropose'] ?? null) !== null
    ? (string) $latestNegotiationEntry['budgetPropose']
    : (string) ($condidature ? $condidature->getBudgetPropose() : $displayBudget);
$creatorNegotiationBaselineDelay = $creatorIsLatestNegotiationSender && ($latestNegotiationEntry['delaiPropose'] ?? null) !== null
    ? (string) $latestNegotiationEntry['delaiPropose']
    : (string) ($condidature ? $condidature->getDelaiPropose() : $displayDelay);
$displayAvailability = $condidature ? $condidature->getDateDisponibilite() : $form['dateDisponibilite'];
$displayTerms = $condidature ? $condidature->getConditionsCreateur() : $form['conditionsCreateur'];
$displayCvPath = $condidature ? $condidature->getCvPath() : $form['cvPath'];
$displayPortfolio = $condidature ? $condidature->getPortfolioUrl() : $form['portfolioUrl'];
$displayMotifRefus = $condidature ? $condidature->getMotifRefus() : $form['motifRefus'];
$responseSummaryTitle = $displayResponseMode === 'decline'
    ? 'Decline details'
    : ($isCampaignFlow ? 'Application details' : 'Response details');
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
    <?php require_once __DIR__ . '/../layout/front-theme-bootstrap.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidature Details - Cre8Connect</title>
    <link rel="stylesheet" href="../css/frontoffice.css">
    <link rel="stylesheet" href="../offre/offre.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../offre/offre.css')); ?>">
    <link rel="stylesheet" href="condidature.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/condidature.css')); ?>">
    <link rel="stylesheet" href="../layout/front-header.css">
<link rel="icon" type="image/png" sizes="16x16" href="../../public/images/favicon-16.png">
<link rel="icon" type="image/png" sizes="32x32" href="../../public/images/favicon-32.png">
<link rel="shortcut icon" type="image/png" href="../../public/images/favicon-32.png">
<link rel="apple-touch-icon" sizes="180x180" href="../../public/images/apple-touch-icon.png">
<style>
/* Cre8 safety: hide stale page-level notification widgets only.
   The real notification bell must stay inside the shared .front-nav header. */
body > .notification-widget,
body > .notification-widget-front,
main .notification-widget,
main .notification-widget-front,
.offre-page-shell .notification-widget,
.offre-page-shell .notification-widget-front,
.module-hero .notification-widget,
.module-hero .notification-widget-front {
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
</style>
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
                                    : 'Apply to this campaign with a structured creator proposal, availability, budget, and profile references.';
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
                            <h2 class="section-title" data-i18n="<?php echo $isOfferFlow ? 'cand.offerResponseContext' : 'cand.campaignApplicationContext'; ?>"><?php echo $isOfferFlow ? 'Offer response context' : 'Campaign application context'; ?></h2>
                            <p class="section-subtitle" data-i18n="cand.sourceDetailsVisible">Keep the source details visible while you shape the candidature response.</p>
                            <div class="offer-detail-list mt-4">
                                <div class="offer-detail-item">
                                    <strong data-i18n="cand.brand">Brand</strong>
                                    <div style="display:flex;align-items:center;gap:.65rem;">
                                        <?php echo cre8_render_avatar($brand['id'] ?? 0, (string) ($brand['nom'] ?? 'Brand'), 'cre8-avatar-md'); ?>
                                        <div>
                                            <span><?php echo htmlspecialchars($brand['nom'] ?? 'Unknown brand'); ?></span>
                                            <p><?php echo htmlspecialchars($brand['email'] ?? ''); ?></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="offer-detail-item">
                                    <strong data-i18n="<?php echo $isOfferFlow ? 'cand.offerObjective' : 'cand.campaignBrief'; ?>"><?php echo $isOfferFlow ? 'Offer objective' : 'Campaign brief'; ?></strong>
                                    <span><?php echo htmlspecialchars(blankToFallback($source['objective'] ?? '', 'No objective was added to this source.')); ?></span>
                                </div>
                                <div class="offer-detail-item">
                                    <strong data-i18n="<?php echo $isOfferFlow ? 'cand.publicationDate' : 'cand.campaignStart'; ?>"><?php echo $isOfferFlow ? 'Publication date' : 'Campaign start date'; ?></strong>
                                    <span><?php echo htmlspecialchars(formatShortDate($source['datePublication'] ?? null)); ?></span>
                                </div>
                                <div class="offer-detail-item">
                                    <strong data-i18n="<?php echo $isOfferFlow ? 'cand.responseWindow' : 'cand.campaignEnd'; ?>"><?php echo $isOfferFlow ? 'Response window' : 'Campaign end date'; ?></strong>
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
                                            !$condidature => $isCampaignFlow ? 'No campaign application has been created yet.' : 'No candidature has been created yet for this offer.',
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
                                    <strong><?php echo htmlspecialchars($condidature ? $condidature->getResponseTypeLabel() : responseModeCardLabelForOrigin($composerMode, $origin)); ?></strong>
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
                                    <p class="section-subtitle" data-i18n="cand.structuredFieldsVisible">The structured response fields stay visible here so you can review what was sent with the candidature.</p>
                                </div>
                                <?php if ($condidature): ?>
                                    <span class="offer-chip"><?php echo htmlspecialchars($condidature->getResponseTypeLabel()); ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="decision-note-grid mt-4">
                                <section class="decision-note-card">
                                    <strong data-i18n="<?php echo $displayResponseMode === 'decline' ? 'cand.refusalReason' : ($isCampaignFlow ? 'cand.applicationMessage' : 'cand.creatorMessage'); ?>"><?php echo $displayResponseMode === 'decline' ? 'Refusal reason' : ($isCampaignFlow ? 'Application message' : 'Creator message'); ?></strong>
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
                                    <strong data-i18n="<?php echo $displayResponseMode === 'decline' ? 'cand.optionalNote' : 'cand.creatorTermsShort'; ?>"><?php echo $displayResponseMode === 'decline' ? 'Optional note' : 'Creator terms'; ?></strong>
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
                                        <strong data-i18n="cand.availabilityStart">Availability start date</strong>
                                        <p><?php echo htmlspecialchars(formatShortDate($displayAvailability, 'Not shared yet')); ?></p>
                                    </section>
                                    <section class="decision-note-card">
                                        <strong data-i18n="cand.deliveryPlan">Delivery plan</strong>
                                        <p><?php echo htmlspecialchars(formatDelayLabel($displayDelay)); ?></p>
                                    </section>
                                </div>

                                <div class="decision-note-grid mt-4">
                                    <section class="decision-note-card">
                                        <strong data-i18n="cand.cvReference">CV reference</strong>
                                        <p>
                                            <?php if (trim((string) $displayCvPath) !== ''): ?>
                                                <a class="inline-link" href="<?php echo htmlspecialchars(candidatureStoredFileHref($displayCvPath)); ?>" target="_blank" rel="noopener noreferrer">
                                                    <?php echo htmlspecialchars(candidatureStoredFileName($displayCvPath) ?: $displayCvPath); ?>
                                                </a>
                                            <?php else: ?>
                                                <span data-i18n="cand.noCvReference">No CV/reference file was attached.</span>
                                            <?php endif; ?>
                                        </p>
                                    </section>
                                    <section class="decision-note-card">
                                        <strong data-i18n="cand.portfolio">Portfolio URL</strong>
                                        <p>
                                            <?php if (trim((string) $displayPortfolio) !== ''): ?>
                                                <a class="inline-link" href="<?php echo htmlspecialchars($displayPortfolio); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($displayPortfolio); ?></a>
                                            <?php else: ?>
                                                <span data-i18n="cand.noPortfolioUrl">No portfolio URL was attached.</span>
                                            <?php endif; ?>
                                        </p>
                                    </section>
                                </div>
                            <?php endif; ?>
                        </section>

                        <section class="section-card">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                                <div>
                                    <h2 class="section-title" data-i18n="cand.negotiationHistory">Negotiation history</h2>
                                    <p class="section-subtitle" data-i18n="cand.negotiationHistoryCopy">Every negotiation message stays attached to this candidature so you can follow the full exchange in order.</p>
                                </div>
                                <span class="offer-chip"><?php echo (int) ($negotiation['count'] ?? 0); ?> <span data-i18n="<?php echo (int) ($negotiation['count'] ?? 0) === 1 ? 'cand.messageSingular' : 'cand.messagePlural'; ?>"><?php echo (int) ($negotiation['count'] ?? 0) === 1 ? 'message' : 'messages'; ?></span></span>
                            </div>

                            <?php if (!empty($negotiation['history'])): ?>
                                <div class="negotiation-thread mt-4">
                                    <?php foreach ($negotiation['history'] as $entry): ?>
                                        <?php
                                        $entryIsBrand = ($entry['auteur'] ?? '') === 'marque';
                                        $entryAvatarUser = $entryIsBrand
                                            ? ($brand ?? [])
                                            : ($context['creator'] ?? ['id' => $creatorId, 'nom' => $sessionUser['nom'] ?? 'Creator']);
                                        ?>
                                        <article class="negotiation-entry negotiation-entry-<?php echo htmlspecialchars($entry['auteur']); ?>">
                                            <div class="negotiation-entry-top">
                                                <div class="negotiation-author-block" style="display:flex;align-items:center;gap:.65rem;">
                                                    <?php echo cre8_render_avatar($entryAvatarUser['id'] ?? 0, (string) ($entry['authorName'] ?? ''), 'cre8-avatar-md'); ?>
                                                    <div>
                                                        <span class="negotiation-role negotiation-role-<?php echo htmlspecialchars($entry['auteur']); ?>">
                                                            <?php echo htmlspecialchars($entry['authorRoleLabel']); ?>
                                                        </span>
                                                        <strong><?php echo htmlspecialchars($entry['authorName']); ?></strong>
                                                        <?php if (!empty($entry['authorEmail'])): ?>
                                                            <span><?php echo htmlspecialchars($entry['authorEmail']); ?></span>
                                                        <?php endif; ?>
                                                    </div>
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
                                                        <span class="offer-chip"><span data-i18n="cand.timeline">Timeline</span>: <?php echo (int) $entry['delaiPropose']; ?> <span data-i18n="cand.days">days</span></span>
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
                        <?php elseif ($isCampaignFlow && !$negotiationOnly): ?>
                            <section class="composer-card brand-action-card creator-response-action-card">
                                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                                    <div>
                                        <h2 class="section-title">Campaign application</h2>
                                        <p class="section-subtitle">Save this application as a draft or send it to the brand for review.</p>
                                    </div>
                                    <?php if ($condidature && $condidature->isDraft()): ?>
                                        <span class="candidature-badge status-draft"><?php echo htmlspecialchars($condidature->getDisplayStatusLabel()); ?></span>
                                    <?php endif; ?>
                                </div>

                                <form method="post" action="<?php echo htmlspecialchars($composerAction); ?>" enctype="multipart/form-data" class="campaign-application-form mt-4" data-campaign-application-form novalidate>
                                    <input type="hidden" name="responseMode" value="accept">
                                    <?php renderCampaignApplicationFields($form); ?>
                                    <div class="campaign-application-actions">
                                        <button type="submit" name="submitIntent" value="draft" class="response-modal-secondary">Save as draft</button>
                                        <button type="submit" name="submitIntent" value="send" class="response-modal-primary">Send application</button>
                                    </div>
                                </form>
                            </section>
                        <?php else: ?>
                            <section class="composer-card brand-action-card creator-response-action-card">
                                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                                    <div>
                                        <h2 class="section-title" data-i18n="<?php echo $isOfferFlow ? 'cand.creatorResponseActions' : 'cand.responseActions'; ?>"><?php echo $isOfferFlow ? 'Creator response actions' : 'Response actions'; ?></h2>
                                        <p class="section-subtitle" data-i18n="<?php echo $negotiationOnly ? 'cand.finalAnswerOrCounter' : 'cand.chooseResponsePath'; ?>">
                                            <?php echo htmlspecialchars($negotiationOnly
                                                ? 'Choose a final answer or send a real counter-proposal from a focused action window.'
                                                : 'Choose the response path first. Each action opens a focused window with only the fields needed for that decision.'); ?>
                                        </p>
                                    </div>
                                    <?php if ($condidature && $condidature->isDraft()): ?>
                                        <span class="candidature-badge status-draft"><?php echo htmlspecialchars($condidature->getDisplayStatusLabel()); ?></span>
                                    <?php elseif ($negotiationOnly): ?>
                                        <span class="candidature-badge status-negotiation" data-i18n="cand.negotiationOpen">Negotiation open</span>
                                    <?php endif; ?>
                                </div>

                                <?php if ($negotiationOnly && $latestBrandMessage !== ''): ?>
                                    <div class="response-callout response-callout-review mt-4">
                                        <strong data-i18n="cand.latestBrandNegotiationUpdate">Latest brand negotiation update</strong>
                                        <div class="mt-2 candidature-signal-copy"><?php echo htmlspecialchars($latestBrandMessage); ?></div>
                                        <div class="candidature-inline-meta mt-2">
                                            <?php if (!empty($latestBrandSignal['dateMessage'])): ?>
                                                <span class="offer-chip"><?php echo htmlspecialchars(formatDateTimeLabel($latestBrandSignal['dateMessage'])); ?></span>
                                            <?php endif; ?>
                                            <?php if ($latestBrandSignal['budgetPropose'] !== null): ?>
                                                <span class="offer-chip"><?php echo htmlspecialchars(formatMoney($latestBrandSignal['budgetPropose'])); ?></span>
                                            <?php endif; ?>
                                            <?php if ($latestBrandSignal['delaiPropose'] !== null): ?>
                                                <span class="offer-chip"><span data-i18n="cand.timeline">Timeline</span>: <?php echo (int) $latestBrandSignal['delaiPropose']; ?> <span data-i18n="cand.days">days</span></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="brand-action-grid creator-action-grid mt-4">
                                    <?php if ($negotiationOnly): ?>
                                        <?php if ($creatorCanFinalizeNegotiation): ?>
                                            <button type="button" class="brand-action-launch brand-action-launch-accept" data-creator-response-modal-trigger="final_accept">
                                                <strong data-i18n="cand.acceptLatestTerms">Accept latest terms</strong>
                                                <span data-i18n="cand.endNegotiationCleanly">End the negotiation cleanly without adding a duplicate message.</span>
                                            </button>
                                        <?php endif; ?>
                                        <button type="button" class="brand-action-launch brand-action-launch-refuse" data-creator-response-modal-trigger="decline">
                                            <strong data-i18n="cand.decline">Decline</strong>
                                            <span data-i18n="cand.withdrawNegotiationReadable">Withdraw from the current negotiation and keep the history readable.</span>
                                        </button>
                                        <button type="button" class="brand-action-launch brand-action-launch-negotiate" data-creator-response-modal-trigger="negotiate">
                                            <strong data-i18n="cand.negotiate">Negotiate</strong>
                                            <span data-i18n="cand.realAdjustment">Send a real adjustment to the message, budget, or timeline.</span>
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="brand-action-launch brand-action-launch-accept" data-creator-response-modal-trigger="accept">
                                            <strong data-i18n="cand.accept">Accept</strong>
                                            <span data-i18n="cand.acceptCreatorResponseCopy">Send your availability, delivery plan, message, and profile references.</span>
                                        </button>
                                        <button type="button" class="brand-action-launch brand-action-launch-refuse" data-creator-response-modal-trigger="decline">
                                            <strong data-i18n="cand.decline">Decline</strong>
                                            <span data-i18n="cand.cleanRefusalCopy">Send a clean refusal reason and keep the invitation in your history.</span>
                                        </button>
                                        <button type="button" class="brand-action-launch brand-action-launch-negotiate" data-creator-response-modal-trigger="negotiate">
                                            <strong data-i18n="cand.negotiate">Negotiate</strong>
                                            <span data-i18n="cand.proposeRevisedTerms">Propose revised budget, timing, or collaboration context.</span>
                                        </button>
                                    <?php endif; ?>
                                </div>

                                <p class="composer-context-note mt-4" data-i18n="<?php echo $negotiationOnly ? 'cand.realChangesOnly' : 'cand.saveDraftLater'; ?>">
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
                                            <form method="post" action="<?php echo htmlspecialchars($composerAction); ?>" enctype="multipart/form-data" class="response-modal-card creator-response-modal-card" data-modal-variant="accept" role="dialog" aria-modal="true" aria-labelledby="creatorAcceptModalTitle">
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
                                            <form method="post" action="<?php echo htmlspecialchars($composerAction); ?>" enctype="multipart/form-data" class="response-modal-card creator-response-modal-card" data-modal-variant="accept" role="dialog" aria-modal="true" aria-labelledby="creatorFinalAcceptModalTitle">
                                                <input type="hidden" name="responseMode" value="accept">
                                                <div class="response-modal-header">
                                                    <span class="response-modal-kicker" data-i18n="cand.finalDecision">Final decision</span>
                                                    <h2 id="creatorFinalAcceptModalTitle" data-i18n="cand.acceptLatestTermsQuestion">Accept latest terms?</h2>
                                                    <p class="response-modal-subtitle" data-i18n="cand.closeAcceptedNoDuplicate">Close the negotiation as accepted without creating another duplicate proposal message.</p>
                                                </div>
                                                <div class="response-modal-body">
                                                    <div class="response-modal-preview">
                                                        <span class="response-modal-preview-label" data-i18n="cand.latestBrandTerms">Latest brand terms</span>
                                                        <strong><?php echo htmlspecialchars($latestBrandMessage !== '' ? $latestBrandMessage : 'The latest brand terms are ready for your final answer.'); ?></strong>
                                                        <span><?php echo htmlspecialchars(formatMoney($latestBrandSignal['budgetPropose'] ?? $displayBudget)); ?> | <span data-i18n="cand.timeline">Timeline</span>: <?php echo (int) ($latestBrandSignal['delaiPropose'] ?? $displayDelay); ?> <span data-i18n="cand.days">days</span></span>
                                                    </div>
                                                    <p class="response-modal-copy" data-i18n="cand.finalAcceptNoHistoryMessage">This will update the candidature status and decision date. It will not add another negotiation history message.</p>
                                                    <div class="response-modal-actions">
                                                        <button type="button" class="response-modal-secondary" data-creator-response-modal-close data-i18n="cand.keepReviewing">Keep reviewing</button>
                                                        <button type="submit" name="submitIntent" value="final_accept" class="response-modal-primary" data-i18n="cand.acceptLatestTerms">Accept latest terms</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    <?php endif; ?>

                                    <div class="response-modal-panel" data-creator-response-modal-panel="decline" hidden>
                                        <form method="post" action="<?php echo htmlspecialchars($composerAction); ?>" enctype="multipart/form-data" class="response-modal-card creator-response-modal-card" data-modal-variant="refuse" role="dialog" aria-modal="true" aria-labelledby="creatorDeclineModalTitle">
                                            <input type="hidden" name="responseMode" value="decline">
                                            <div class="response-modal-header">
                                                <span class="response-modal-kicker" data-i18n="<?php echo $negotiationOnly ? 'cand.withdrawal' : 'cand.decline'; ?>"><?php echo $negotiationOnly ? 'Withdrawal' : 'Decline'; ?></span>
                                                <h2 id="creatorDeclineModalTitle" data-i18n="<?php echo $negotiationOnly ? 'cand.declineLatestTermsQuestion' : 'cand.declineOfferQuestion'; ?>"><?php echo $negotiationOnly ? 'Decline latest terms?' : 'Decline this offer?'; ?></h2>
                                                <p class="response-modal-subtitle" data-i18n="<?php echo $negotiationOnly ? 'cand.closeNegotiationHistoryVisible' : 'cand.cleanRefusalNoLeave'; ?>"><?php echo $negotiationOnly ? 'Close your side of the negotiation while keeping the saved history visible.' : 'Send a clean refusal response without leaving this page.'; ?></p>
                                            </div>
                                            <div class="response-modal-body">
                                                <div class="response-modal-preview">
                                                    <span class="response-modal-preview-label" data-i18n="cand.selectedOffer">Selected offer</span>
                                                    <strong><?php echo htmlspecialchars($source['title'] ?? 'Candidature source'); ?></strong>
                                                    <span><?php echo htmlspecialchars($brand['nom'] ?? 'Unknown brand'); ?></span>
                                                </div>
                                                <?php renderCreatorDeclineFields($form, 'Decline', $negotiationOnly); ?>
                                                <div class="response-modal-actions">
                                                    <button type="button" class="response-modal-secondary" data-creator-response-modal-close data-i18n="cand.keepReviewing">Keep reviewing</button>
                                                    <?php if (!$negotiationOnly): ?>
                                                        <button type="submit" name="submitIntent" value="draft" class="response-modal-secondary" data-i18n="cand.saveDraft">Save as draft</button>
                                                        <button type="submit" name="submitIntent" value="send" class="response-modal-primary" data-i18n="cand.sendDecline">Send decline</button>
                                                    <?php else: ?>
                                                        <button type="submit" name="submitIntent" value="final_decline" class="response-modal-primary" data-i18n="cand.declineLatestTerms">Decline latest terms</button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </form>
                                    </div>

                                    <div class="response-modal-panel" data-creator-response-modal-panel="negotiate" hidden>
                                        <form
                                            method="post"
                                            action="<?php echo htmlspecialchars($composerAction); ?>"
                                            enctype="multipart/form-data"
                                            class="response-modal-card creator-response-modal-card"
                                            data-modal-variant="negotiate"
                                            data-require-negotiation-delta="1"
                                            data-baseline-message="<?php echo htmlspecialchars($creatorNegotiationBaselineMessage); ?>"
                                            data-baseline-budget="<?php echo htmlspecialchars($creatorNegotiationBaselineBudget); ?>"
                                            data-baseline-delay="<?php echo htmlspecialchars($creatorNegotiationBaselineDelay); ?>"
                                            role="dialog"
                                            aria-modal="true"
                                            aria-labelledby="creatorNegotiateModalTitle"
                                        >
                                            <input type="hidden" name="responseMode" value="negotiate">
                                            <div class="response-modal-header">
                                                <span class="response-modal-kicker" data-i18n="cand.negotiation">Negotiation</span>
                                                <h2 id="creatorNegotiateModalTitle" data-i18n="<?php echo $creatorIsLatestNegotiationSender ? 'cand.updateYourProposal' : ($negotiationOnly ? 'cand.sendCounterProposal' : 'cand.negotiateThisOffer'); ?>"><?php echo $creatorIsLatestNegotiationSender ? 'Update your proposal' : ($negotiationOnly ? 'Send a counter-proposal' : 'Negotiate this offer'); ?></h2>
                                                <p class="response-modal-subtitle" data-i18n="<?php echo $creatorIsLatestNegotiationSender ? 'cand.editLatestNoDuplicate' : 'cand.realChangeCurrentTerms'; ?>"><?php echo $creatorIsLatestNegotiationSender ? 'You sent the latest negotiation step, so edit that proposal instead of adding a duplicate message.' : 'Use this only when you are proposing a real change to the current terms.'; ?></p>
                                            </div>
                                            <div class="response-modal-body">
                                                <?php if (!empty($errors) && $defaultCreatorModal === 'negotiate'): ?>
                                                    <div class="response-modal-summary response-modal-summary-danger">
                                                        <strong>Unable to save the negotiation.</strong>
                                                        <ul>
                                                            <?php foreach ($errors as $item): ?>
                                                                <li><?php echo htmlspecialchars($item); ?></li>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($negotiationOnly && ($latestBrandMessage !== '' || $creatorIsLatestNegotiationSender)): ?>
                                                    <?php
                                                    $creatorModalReference = $creatorIsLatestNegotiationSender ? $latestNegotiationEntry : $latestBrandSignal;
                                                    $creatorModalReferenceMessage = trim((string) ($creatorModalReference['message'] ?? ''));
                                                    ?>
                                                    <div class="response-modal-preview">
                                                        <span class="response-modal-preview-label" data-i18n="<?php echo $creatorIsLatestNegotiationSender ? 'cand.yourLatestProposal' : 'cand.latestBrandUpdate'; ?>"><?php echo $creatorIsLatestNegotiationSender ? 'Your latest proposal' : 'Latest brand update'; ?></span>
                                                        <strong><?php echo htmlspecialchars($creatorModalReferenceMessage !== '' ? $creatorModalReferenceMessage : 'No message body was added to this negotiation step.'); ?></strong>
                                                        <span><?php echo htmlspecialchars(formatDateTimeLabel($creatorModalReference['dateMessage'] ?? null)); ?></span>
                                                    </div>
                                                    <section class="creator-modal-section creator-modal-section-warning">
                                                        <div class="creator-modal-section-head">
                                                            <strong data-i18n="<?php echo $creatorIsLatestNegotiationSender ? 'cand.latestProposalEdit' : 'cand.counterProposal'; ?>"><?php echo $creatorIsLatestNegotiationSender ? 'Latest proposal edit' : 'Counter-proposal'; ?></strong>
                                                            <span data-i18n="<?php echo $creatorIsLatestNegotiationSender ? 'cand.changeBeforeUpdate' : 'cand.changeBeforeAnotherStep'; ?>"><?php echo $creatorIsLatestNegotiationSender ? 'Change the message, budget, or timeline before updating your latest proposal.' : 'Change the message, budget, or timeline before sending another negotiation step.'; ?></span>
                                                        </div>
                                                        <label for="messageMotivationNegotiationOnly" class="form-label fw-semibold" data-i18n="cand.negotiationMessage">Negotiation message</label>
                                                        <textarea class="form-control" id="messageMotivationNegotiationOnly" name="messageMotivation" rows="4" data-cre8pilot-field="messageNegociation" placeholder="Reply to the latest brand update and explain the new terms you want to propose."><?php echo formFieldValue($form, 'messageMotivation'); ?></textarea>
                                                        <div class="response-modal-field-grid mt-3">
                                                            <div>
                                                                <label for="budgetProposeNegotiationOnly" class="form-label fw-semibold" data-i18n="cand.proposedBudget">Proposed budget</label>
                                                                <div class="input-group">
                                                                    <span class="input-group-text">EUR</span>
                                                                    <input type="number" class="form-control" id="budgetProposeNegotiationOnly" name="budgetPropose" step="0.01" data-cre8pilot-field="budgetPropose" value="<?php echo formFieldValue($form, 'budgetPropose'); ?>">
                                                                </div>
                                                            </div>
                                                            <div>
                                                                <label for="delaiProposeNegotiationOnly" class="form-label fw-semibold" data-i18n="cand.proposedTimeline">Proposed timeline</label>
                                                                <input type="number" class="form-control" id="delaiProposeNegotiationOnly" name="delaiPropose" min="1" step="1" data-cre8pilot-field="delaiPropose" value="<?php echo formFieldValue($form, 'delaiPropose'); ?>">
                                                            </div>
                                                        </div>
                                                    </section>
                                                <?php else: ?>
                                                    <div class="response-modal-preview">
                                                        <span class="response-modal-preview-label" data-i18n="cand.selectedOffer">Selected offer</span>
                                                        <strong><?php echo htmlspecialchars($source['title'] ?? 'Candidature source'); ?></strong>
                                                        <span><?php echo htmlspecialchars($brand['nom'] ?? 'Unknown brand'); ?> | Current budget: <?php echo htmlspecialchars(formatMoney($source['budgetPropose'] ?? $displayBudget)); ?></span>
                                                    </div>
                                                    <?php renderCreatorPlanFields($form, 'Negotiate', 'negotiate'); ?>
                                                    <?php renderCreatorBudgetField($form, 'Negotiate'); ?>
                                                <?php endif; ?>
                                                <div class="response-modal-actions">
                                                    <button type="button" class="response-modal-secondary" data-creator-response-modal-close data-i18n="cand.keepReviewing">Keep reviewing</button>
                                                    <?php if (!$negotiationOnly): ?>
                                                        <button type="submit" name="submitIntent" value="draft" class="response-modal-secondary" data-i18n="cand.saveDraft">Save as draft</button>
                                                    <?php endif; ?>
                                                    <button type="submit" name="submitIntent" value="send" class="response-modal-primary response-modal-primary-negotiate" data-i18n="<?php echo $creatorIsLatestNegotiationSender ? 'cand.updateProposal' : ($negotiationOnly ? 'cand.sendCounter' : 'cand.sendNegotiation'); ?>"><?php echo $creatorIsLatestNegotiationSender ? 'Update proposal' : ($negotiationOnly ? 'Send counter-proposal' : 'Send negotiation'); ?></button>
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
                            <?php elseif ($origin === 'par_campagne'): ?>
                                <a class="btn btn-outline-secondary w-100" href="campaign_opportunities.php">Browse campaign opportunities</a>
                            <?php endif; ?>
                        </div>
                    </aside>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
    <script src="creator-actions-modal.js?v=<?php echo urlencode((string) filemtime(__DIR__ . '/creator-actions-modal.js')); ?>"></script>
    <?php if ($isCampaignFlow && !$lockedForCreator && !$negotiationOnly): ?>
        <script>
            (() => {
                const form = document.querySelector('[data-campaign-application-form]');
                if (!form) {
                    return;
                }

                const field = (name) => form.querySelector(`[name="${name}"]`);
                const value = (name) => (field(name)?.value || '').trim();
                const todayIso = () => {
                    const today = new Date();
                    return `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
                };
                const addError = (errors, name, message) => {
                    const target = field(name);
                    if (target) {
                        errors.push({ target, name, message });
                    }
                };
                const errorAnchor = (target) => target.closest('.input-group') || target;
                const clearSummary = () => {
                    form.querySelectorAll('.modal-validation-summary').forEach((node) => node.remove());
                };
                const clearFieldError = (target) => {
                    target.classList.remove('is-invalid');
                    target.removeAttribute('aria-invalid');
                    form.querySelectorAll('.modal-field-error').forEach((node) => {
                        if (node.dataset.fieldErrorFor === target.name) {
                            node.remove();
                        }
                    });
                };
                const clearErrors = () => {
                    clearSummary();
                    form.querySelectorAll('.is-invalid').forEach(clearFieldError);
                };
                const isPositiveNumber = (text) => text !== '' && !Number.isNaN(Number(text)) && Number(text) > 0;
                const isPositiveInteger = (text) => /^[0-9]+$/.test(text) && Number(text) > 0;
                const isValidUrl = (text) => {
                    if (text === '') {
                        return true;
                    }
                    try {
                        const parsed = new URL(text);
                        return parsed.protocol === 'http:' || parsed.protocol === 'https:';
                    } catch (error) {
                        return false;
                    }
                };
                const validateFile = () => {
                    const fileField = field('cvFile');
                    if (!fileField || !fileField.files || fileField.files.length === 0) {
                        return '';
                    }
                    const file = fileField.files[0];
                    const extension = (file.name.split('.').pop() || '').toLowerCase();
                    if (!['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg'].includes(extension)) {
                        return 'Upload a CV/profile support file as PDF, DOC, DOCX, PNG, JPG, or JPEG.';
                    }
                    return file.size > 5 * 1024 * 1024 ? 'The CV/profile support file must be 5 MB or smaller.' : '';
                };
                const fieldError = (name, strict) => {
                    const text = value(name);

                    if (name === 'messageMotivation') {
                        if (strict && text === '') {
                            return 'Add an application message before sending.';
                        }
                        return text.length > 2500 ? 'Your application message must stay under 2500 characters.' : '';
                    }

                    if (name === 'conditionsCreateur') {
                        return text.length > 2000 ? 'Creator terms must stay under 2000 characters.' : '';
                    }

                    if (name === 'dateDisponibilite') {
                        if (strict && text === '') {
                            return 'Choose your availability date before sending.';
                        }
                        return text !== '' && text < todayIso() ? 'Availability date cannot be in the past.' : '';
                    }

                    if (name === 'delaiPropose') {
                        if (strict && text === '') {
                            return 'Enter an estimated delivery delay before sending.';
                        }
                        return text !== '' && !isPositiveInteger(text) ? 'Enter a valid estimated delivery delay in days.' : '';
                    }

                    if (name === 'budgetPropose') {
                        if (strict && text === '') {
                            return 'Enter your proposed budget before sending.';
                        }
                        return text !== '' && !isPositiveNumber(text) ? 'Enter a valid proposed budget greater than 0.' : '';
                    }

                    if (name === 'portfolioUrl') {
                        return text.length > 255 || !isValidUrl(text)
                            ? 'Enter a valid portfolio URL starting with http:// or https://.'
                            : '';
                    }

                    if (name === 'cvFile') {
                        return validateFile();
                    }

                    return '';
                };
                const validateField = (name, strict) => {
                    const target = field(name);
                    if (!target) {
                        return null;
                    }

                    const message = fieldError(name, strict);
                    return message ? { target, name, message } : null;
                };
                const showFieldValidation = (name, strict = true) => {
                    const target = field(name);
                    if (!target) {
                        return;
                    }

                    clearFieldError(target);
                    const error = validateField(name, strict);
                    if (!error) {
                        return;
                    }

                    target.classList.add('is-invalid');
                    target.setAttribute('aria-invalid', 'true');

                    const note = document.createElement('div');
                    note.className = 'modal-field-error';
                    note.dataset.fieldErrorFor = name;
                    note.textContent = error.message;
                    errorAnchor(target).insertAdjacentElement('afterend', note);
                };
                const validate = (strict) => {
                    const errors = [];
                    [
                        'messageMotivation',
                        'conditionsCreateur',
                        'dateDisponibilite',
                        'delaiPropose',
                        'budgetPropose',
                        'portfolioUrl',
                        'cvFile'
                    ].forEach((name) => {
                        const error = validateField(name, strict);
                        if (error) {
                            addError(errors, name, error.message);
                        }
                    });

                    return errors;
                };
                const renderErrors = (errors) => {
                    if (!errors.length) {
                        return;
                    }
                    const summary = document.createElement('div');
                    summary.className = 'modal-validation-summary';
                    summary.setAttribute('role', 'alert');
                    summary.innerHTML = `<strong>Please fix the highlighted fields.</strong><ul>${errors.map((item) => `<li>${item.message}</li>`).join('')}</ul>`;
                    form.insertBefore(summary, form.firstElementChild);
                    errors.forEach(({ name }) => showFieldValidation(name, true));
                    errors[0].target.focus({ preventScroll: false });
                };

                [
                    'messageMotivation',
                    'conditionsCreateur',
                    'dateDisponibilite',
                    'delaiPropose',
                    'budgetPropose',
                    'portfolioUrl',
                    'cvFile'
                ].forEach((name) => {
                    const target = field(name);
                    if (!target) {
                        return;
                    }

                    target.addEventListener('blur', () => {
                        clearSummary();
                        showFieldValidation(name, true);
                    });

                    target.addEventListener(target.type === 'file' ? 'change' : 'input', () => {
                        if (target.classList.contains('is-invalid')) {
                            clearSummary();
                            showFieldValidation(name, true);
                        }
                    });
                });

                form.addEventListener('submit', (event) => {
                    clearErrors();
                    const strict = (event.submitter?.value || '') === 'send';
                    const errors = validate(strict);
                    if (errors.length) {
                        event.preventDefault();
                        renderErrors(errors);
                    }
                });
            })();
        </script>
    <?php endif; ?>
<?php
$cre8PilotContext = [
    'page' => 'creator_candidature_workspace',
    'mode' => $negotiationOnly ? 'negotiation_reply' : 'application_form',
    'role' => (string) ($_SESSION['utilisateur']['role'] ?? ''),
    'allowedActions' => $negotiationOnly
        ? ['normal_chat', 'prepare_negotiation_reply', 'prepare_creator_acceptance_note', 'prepare_creator_refusal_note', 'summarize_negotiation', 'improve_negotiation_message', 'security_check']
        : ['normal_chat', 'summarize_page', 'fill_candidature_form', 'improve_motivation_message', 'suggest_budget_delay', 'prepare_negotiation_reply'],
    'formTarget' => $negotiationOnly ? 'negotiation_form' : 'candidature_form',
    'visibleEntityType' => $negotiationOnly ? 'negociation' : 'candidature',
    'visibleEntityId' => $condidature ? $condidature->getIdCandidature() : null,
];
require __DIR__ . '/cre8pilot_widget.php';
?>
    <script>
        (() => {
            const translations = {
                en: {
                    'cand.unavailable': 'Candidature unavailable',
                    'cand.backMine': 'Back to my candidatures',
                    'cand.viewSourceOffer': 'View source offer',
                    'cand.browseCampaigns': 'Browse campaign opportunities',
                    'cand.offerInvitation': 'Offer invitation',
                    'cand.campaignApplication': 'Campaign application',
                    'cand.acceptanceResponse': 'Acceptance response',
                    'cand.negotiationRequest': 'Negotiation request',
                    'cand.declineResponse': 'Decline response',
                    'cand.offerResponseContext': 'Offer response context',
                    'cand.campaignApplicationContext': 'Campaign application context',
                    'cand.sourceDetailsVisible': 'Keep the source details visible while you shape the candidature response.',
                    'cand.responseContext': 'Response context',
                    'cand.responseContextCopy': 'Keep the brand and source details visible while you prepare or review the candidature.',
                    'cand.brand': 'Brand',
                    'cand.unknownBrand': 'Unknown brand',
                    'cand.offerObjective': 'Offer objective',
                    'cand.campaignBrief': 'Campaign brief',
                    'cand.publicationDate': 'Publication date',
                    'cand.responseWindow': 'Response window',
                    'cand.campaignDescription': 'Campaign description',
                    'cand.sourcePublished': 'Source published',
                    'cand.sourceDeadline': 'Source deadline',
                    'cand.campaignStart': 'Campaign start date',
                    'cand.campaignEnd': 'Campaign end date',
                    'cand.yourResponse': 'Your response',
                    'cand.currentStatus': 'Current status',
                    'cand.responseType': 'Response type',
                    'cand.budgetReply': 'Budget reply',
                    'cand.timelineReply': 'Timeline reply',
                    'cand.availability': 'Availability',
                    'cand.creatorMessage': 'Creator message',
                    'cand.creatorTerms': 'Creator terms and conditions',
                    'cand.creatorTermsShort': 'Creator terms',
                    'cand.structuredFieldsVisible': 'The structured response fields stay visible here so you can review what was sent with the candidature.',
                    'cand.refusalReason': 'Refusal reason',
                    'cand.optionalNote': 'Optional note',
                    'cand.deliveryPlan': 'Delivery plan',
                    'cand.cvReference': 'CV reference',
                    'cand.noCvReference': 'No CV/reference file was attached.',
                    'cand.noPortfolioUrl': 'No portfolio URL was attached.',
                    'cand.portfolio': 'Portfolio URL',
                    'cand.cvFile': 'CV or reference file',
                    'cand.noFile': 'No file attached yet',
                    'cand.noPortfolio': 'No portfolio link attached yet',
                    'cand.negotiationHistory': 'Negotiation history',
                    'cand.negotiationHistoryCopy': 'Every negotiation message stays attached to this candidature so you can follow the full exchange in order.',
                    'cand.negotiationThread': 'Negotiation thread',
                    'cand.latestBrandUpdate': 'Latest brand update',
                    'cand.latestBrandNegotiationUpdate': 'Latest brand negotiation update',
                    'cand.yourLatestProposal': 'Your latest proposal',
                    'cand.latestProposalEdit': 'Latest proposal edit',
                    'cand.counterProposal': 'Counter-proposal',
                    'cand.selectedOffer': 'Selected offer',
                    'cand.candidatureSource': 'Candidature source',
                    'cand.availabilityDelivery': 'Availability and delivery',
                    'cand.availabilityDeliveryCopy': 'Share when you can start and how long the delivery should take.',
                    'cand.availabilityStart': 'Availability start date',
                    'cand.deliveryDelay': 'Delivery delay',
                    'cand.proposedDelay': 'Proposed delay',
                    'cand.proposedTimeline': 'Proposed timeline',
                    'cand.negotiationMessage': 'Negotiation message',
                    'cand.adjustmentExplain': 'Explain the adjustment you want to propose.',
                    'cand.shortContextFirst': 'Add the short context the brand should read first.',
                    'cand.messageMotivation': 'Creator message / motivation',
                    'cand.profileSupport': 'Profile support',
                    'cand.profileSupportCopy': 'Attach simple references the brand can review with your response.',
                    'cand.negotiationTerms': 'Negotiation terms',
                    'cand.negotiationTermsCopy': 'Add the budget you want to propose for this collaboration.',
                    'cand.proposedBudget': 'Proposed budget',
                    'cand.applicationMessage': 'Application message',
                    'cand.applicationMessageCopy': 'Explain why you want to join this campaign and what your content can bring to it.',
                    'cand.whyJoin': 'Why do you want to join this campaign?',
                    'cand.contentIdea': 'Your proposed content idea',
                    'cand.contentIdeaCopy': 'Add the creator-side concept, terms, or production notes the brand should review.',
                    'cand.availabilityProposal': 'Availability and proposal',
                    'cand.timelineBudgetCampaign': 'Share your timeline and budget for this campaign application.',
                    'cand.availabilityDate': 'Availability date',
                    'cand.estimatedDelay': 'Estimated delivery delay',
                    'cand.supportUpload': 'CV / profile support upload',
                    'cand.attachReferencesPortfolio': 'Attach references and add a portfolio link for brand review.',
                    'cand.cvProfileSupportFile': 'CV or profile support file',
                    'cand.withdrawalContext': 'Withdrawal context',
                    'cand.declineContext': 'Decline context',
                    'cand.finalNoteLatestTerms': 'Leave a final note if you want to explain why the latest terms do not work.',
                    'cand.shortReasonDecision': 'A short reason helps the brand understand your decision.',
                    'cand.optionalShortNote': 'Optional short note',
                    'cand.keepReviewing': 'Keep reviewing',
                    'cand.saveDraft': 'Save as draft',
                    'cand.sendDecline': 'Send decline',
                    'cand.sendNegotiation': 'Send negotiation',
                    'cand.sendCounter': 'Send counter-proposal',
                    'cand.updateProposal': 'Update proposal',
                    'cand.datesMarkers': 'Dates and review markers',
                    'cand.timeline': 'Timeline',
                    'cand.days': 'days',
                    'cand.messageSingular': 'message',
                    'cand.messagePlural': 'messages',
                    'cand.creatorResponseActions': 'Creator response actions',
                    'cand.responseActions': 'Response actions',
                    'cand.finalAnswerOrCounter': 'Choose a final answer or send a real counter-proposal from a focused action window.',
                    'cand.chooseResponsePath': 'Choose the response path first. Each action opens a focused window with only the fields needed for that decision.',
                    'cand.negotiationOpen': 'Negotiation open',
                    'cand.acceptLatestTerms': 'Accept latest terms',
                    'cand.endNegotiationCleanly': 'End the negotiation cleanly without adding a duplicate message.',
                    'cand.decline': 'Decline',
                    'cand.withdrawNegotiationReadable': 'Withdraw from the current negotiation and keep the history readable.',
                    'cand.negotiate': 'Negotiate',
                    'cand.realAdjustment': 'Send a real adjustment to the message, budget, or timeline.',
                    'cand.accept': 'Accept',
                    'cand.acceptCreatorResponseCopy': 'Send your availability, delivery plan, message, and profile references.',
                    'cand.cleanRefusalCopy': 'Send a clean refusal reason and keep the invitation in your history.',
                    'cand.proposeRevisedTerms': 'Propose revised budget, timing, or collaboration context.',
                    'cand.realChangesOnly': 'Negotiation messages should only be used for real changes. If the latest terms work, accept them as a final decision.',
                    'cand.saveDraftLater': 'You can save a draft inside any action window if you want to finish the response later.',
                    'cand.finalDecision': 'Final decision',
                    'cand.acceptLatestTermsQuestion': 'Accept latest terms?',
                    'cand.closeAcceptedNoDuplicate': 'Close the negotiation as accepted without creating another duplicate proposal message.',
                    'cand.latestBrandTerms': 'Latest brand terms',
                    'cand.finalAcceptNoHistoryMessage': 'This will update the candidature status and decision date. It will not add another negotiation history message.',
                    'cand.withdrawal': 'Withdrawal',
                    'cand.declineLatestTermsQuestion': 'Decline latest terms?',
                    'cand.declineOfferQuestion': 'Decline this offer?',
                    'cand.closeNegotiationHistoryVisible': 'Close your side of the negotiation while keeping the saved history visible.',
                    'cand.cleanRefusalNoLeave': 'Send a clean refusal response without leaving this page.',
                    'cand.declineLatestTerms': 'Decline latest terms',
                    'cand.negotiation': 'Negotiation',
                    'cand.updateYourProposal': 'Update your proposal',
                    'cand.sendCounterProposal': 'Send a counter-proposal',
                    'cand.negotiateThisOffer': 'Negotiate this offer',
                    'cand.editLatestNoDuplicate': 'You sent the latest negotiation step, so edit that proposal instead of adding a duplicate message.',
                    'cand.realChangeCurrentTerms': 'Use this only when you are proposing a real change to the current terms.',
                    'cand.changeBeforeUpdate': 'Change the message, budget, or timeline before updating your latest proposal.',
                    'cand.changeBeforeAnotherStep': 'Change the message, budget, or timeline before sending another negotiation step.',
                    'cand.draftResponse': 'Draft response',
                    'cand.acceptedInvitation': 'Accepted invitation',
                    'cand.underReview': 'Response under review',
                    'cand.negotiationRequested': 'Negotiation requested',
                    'cand.acceptedTerms': 'Accepted terms',
                    'cand.refusedBrand': 'Refused by brand',
                    'cand.declinedInvitation': 'Declined invitation'
                },
                fr: {
                    'cand.unavailable': 'Candidature indisponible',
                    'cand.backMine': 'Retour a mes candidatures',
                    'cand.viewSourceOffer': 'Voir l offre source',
                    'cand.browseCampaigns': 'Parcourir les opportunites de campagne',
                    'cand.offerInvitation': 'Invitation offre',
                    'cand.campaignApplication': 'Candidature campagne',
                    'cand.acceptanceResponse': 'Reponse d acceptation',
                    'cand.negotiationRequest': 'Demande de negociation',
                    'cand.declineResponse': 'Reponse de refus',
                    'cand.offerResponseContext': 'Contexte de reponse a l offre',
                    'cand.campaignApplicationContext': 'Contexte de candidature campagne',
                    'cand.sourceDetailsVisible': 'Gardez les details source visibles pendant que vous preparez la reponse de candidature.',
                    'cand.responseContext': 'Contexte de reponse',
                    'cand.responseContextCopy': 'Gardez les details de la marque et de la source visibles pendant la preparation ou la revue.',
                    'cand.brand': 'Marque',
                    'cand.unknownBrand': 'Marque inconnue',
                    'cand.offerObjective': 'Objectif de l offre',
                    'cand.campaignBrief': 'Brief de campagne',
                    'cand.publicationDate': 'Date de publication',
                    'cand.responseWindow': 'Fenetre de reponse',
                    'cand.campaignDescription': 'Description de la campagne',
                    'cand.sourcePublished': 'Source publiee',
                    'cand.sourceDeadline': 'Echeance source',
                    'cand.campaignStart': 'Date de debut de campagne',
                    'cand.campaignEnd': 'Date de fin de campagne',
                    'cand.yourResponse': 'Votre reponse',
                    'cand.currentStatus': 'Statut actuel',
                    'cand.responseType': 'Type de reponse',
                    'cand.budgetReply': 'Reponse budget',
                    'cand.timelineReply': 'Reponse delai',
                    'cand.availability': 'Disponibilite',
                    'cand.creatorMessage': 'Message createur',
                    'cand.creatorTerms': 'Conditions createur',
                    'cand.creatorTermsShort': 'Conditions createur',
                    'cand.structuredFieldsVisible': 'Les champs structures de reponse restent visibles ici pour revoir ce qui a ete envoye avec la candidature.',
                    'cand.refusalReason': 'Motif de refus',
                    'cand.optionalNote': 'Note optionnelle',
                    'cand.deliveryPlan': 'Plan de livraison',
                    'cand.cvReference': 'Reference CV',
                    'cand.noCvReference': 'Aucun CV/fichier de reference joint.',
                    'cand.noPortfolioUrl': 'Aucune URL portfolio jointe.',
                    'cand.portfolio': 'URL portfolio',
                    'cand.cvFile': 'CV ou fichier de reference',
                    'cand.noFile': 'Aucun fichier joint',
                    'cand.noPortfolio': 'Aucun lien portfolio joint',
                    'cand.negotiationHistory': 'Historique de negociation',
                    'cand.negotiationHistoryCopy': 'Chaque message de negociation reste attache a cette candidature pour suivre tout l echange dans l ordre.',
                    'cand.negotiationThread': 'Fil de negociation',
                    'cand.latestBrandUpdate': 'Derniere mise a jour marque',
                    'cand.latestBrandNegotiationUpdate': 'Derniere mise a jour de negociation de la marque',
                    'cand.yourLatestProposal': 'Votre derniere proposition',
                    'cand.latestProposalEdit': 'Modification de la derniere proposition',
                    'cand.counterProposal': 'Contre-proposition',
                    'cand.selectedOffer': 'Offre selectionnee',
                    'cand.candidatureSource': 'Source de candidature',
                    'cand.availabilityDelivery': 'Disponibilite et livraison',
                    'cand.availabilityDeliveryCopy': 'Indiquez quand vous pouvez commencer et le delai de livraison.',
                    'cand.availabilityStart': 'Date de debut de disponibilite',
                    'cand.deliveryDelay': 'Delai de livraison',
                    'cand.proposedDelay': 'Delai propose',
                    'cand.proposedTimeline': 'Delai propose',
                    'cand.negotiationMessage': 'Message de negociation',
                    'cand.adjustmentExplain': 'Expliquez l ajustement que vous souhaitez proposer.',
                    'cand.shortContextFirst': 'Ajoutez le court contexte que la marque doit lire en premier.',
                    'cand.messageMotivation': 'Message createur / motivation',
                    'cand.profileSupport': 'Support de profil',
                    'cand.profileSupportCopy': 'Joignez des references simples que la marque peut examiner.',
                    'cand.negotiationTerms': 'Termes de negociation',
                    'cand.negotiationTermsCopy': 'Ajoutez le budget que vous souhaitez proposer.',
                    'cand.proposedBudget': 'Budget propose',
                    'cand.applicationMessage': 'Message de candidature',
                    'cand.applicationMessageCopy': 'Expliquez pourquoi vous souhaitez rejoindre cette campagne.',
                    'cand.whyJoin': 'Pourquoi voulez-vous rejoindre cette campagne ?',
                    'cand.contentIdea': 'Votre idee de contenu proposee',
                    'cand.contentIdeaCopy': 'Ajoutez le concept, les termes ou notes de production cote createur.',
                    'cand.availabilityProposal': 'Disponibilite et proposition',
                    'cand.timelineBudgetCampaign': 'Partagez votre delai et votre budget pour cette candidature campagne.',
                    'cand.availabilityDate': 'Date de disponibilite',
                    'cand.estimatedDelay': 'Delai de livraison estime',
                    'cand.supportUpload': 'CV / support de profil',
                    'cand.attachReferencesPortfolio': 'Joignez des references et ajoutez un lien portfolio pour la revue de la marque.',
                    'cand.cvProfileSupportFile': 'CV ou fichier support de profil',
                    'cand.withdrawalContext': 'Contexte de retrait',
                    'cand.declineContext': 'Contexte de refus',
                    'cand.finalNoteLatestTerms': 'Laissez une note finale si vous voulez expliquer pourquoi les derniers termes ne conviennent pas.',
                    'cand.shortReasonDecision': 'Une courte raison aide la marque a comprendre votre decision.',
                    'cand.optionalShortNote': 'Courte note optionnelle',
                    'cand.keepReviewing': 'Continuer la revue',
                    'cand.saveDraft': 'Enregistrer en brouillon',
                    'cand.sendDecline': 'Envoyer le refus',
                    'cand.sendNegotiation': 'Envoyer la negociation',
                    'cand.sendCounter': 'Envoyer une contre-proposition',
                    'cand.updateProposal': 'Mettre a jour la proposition',
                    'cand.datesMarkers': 'Dates et reperes de revue',
                    'cand.timeline': 'Chronologie',
                    'cand.days': 'jours',
                    'cand.messageSingular': 'message',
                    'cand.messagePlural': 'messages',
                    'cand.creatorResponseActions': 'Actions de reponse createur',
                    'cand.responseActions': 'Actions de reponse',
                    'cand.finalAnswerOrCounter': 'Choisissez une reponse finale ou envoyez une vraie contre-proposition depuis une fenetre d action ciblee.',
                    'cand.chooseResponsePath': 'Choisissez d abord le type de reponse. Chaque action ouvre une fenetre avec seulement les champs necessaires.',
                    'cand.negotiationOpen': 'Negociation ouverte',
                    'cand.acceptLatestTerms': 'Accepter les derniers termes',
                    'cand.endNegotiationCleanly': 'Terminez proprement la negociation sans ajouter de message en double.',
                    'cand.decline': 'Refuser',
                    'cand.withdrawNegotiationReadable': 'Se retirer de la negociation actuelle tout en gardant l historique lisible.',
                    'cand.negotiate': 'Negocier',
                    'cand.realAdjustment': 'Envoyer un vrai ajustement du message, du budget ou du delai.',
                    'cand.accept': 'Accepter',
                    'cand.acceptCreatorResponseCopy': 'Envoyer votre disponibilite, plan de livraison, message et references de profil.',
                    'cand.cleanRefusalCopy': 'Envoyer un motif de refus clair et garder l invitation dans votre historique.',
                    'cand.proposeRevisedTerms': 'Proposer un budget, un delai ou un contexte de collaboration revise.',
                    'cand.realChangesOnly': 'Les messages de negociation doivent servir uniquement a de vrais changements. Si les derniers termes conviennent, acceptez-les comme decision finale.',
                    'cand.saveDraftLater': 'Vous pouvez enregistrer un brouillon dans une fenetre d action pour terminer la reponse plus tard.',
                    'cand.finalDecision': 'Decision finale',
                    'cand.acceptLatestTermsQuestion': 'Accepter les derniers termes ?',
                    'cand.closeAcceptedNoDuplicate': 'Cloturer la negociation comme acceptee sans creer de proposition en double.',
                    'cand.latestBrandTerms': 'Derniers termes de la marque',
                    'cand.finalAcceptNoHistoryMessage': 'Cela mettra a jour le statut et la date de decision sans ajouter de message a l historique de negociation.',
                    'cand.withdrawal': 'Retrait',
                    'cand.declineLatestTermsQuestion': 'Refuser les derniers termes ?',
                    'cand.declineOfferQuestion': 'Refuser cette offre ?',
                    'cand.closeNegotiationHistoryVisible': 'Cloturez votre cote de la negociation tout en gardant l historique visible.',
                    'cand.cleanRefusalNoLeave': 'Envoyez une reponse de refus claire sans quitter cette page.',
                    'cand.declineLatestTerms': 'Refuser les derniers termes',
                    'cand.negotiation': 'Negociation',
                    'cand.updateYourProposal': 'Mettre a jour votre proposition',
                    'cand.sendCounterProposal': 'Envoyer une contre-proposition',
                    'cand.negotiateThisOffer': 'Negocier cette offre',
                    'cand.editLatestNoDuplicate': 'Vous avez envoye la derniere etape de negociation, modifiez cette proposition au lieu d ajouter un message en double.',
                    'cand.realChangeCurrentTerms': 'Utilisez ceci seulement pour proposer un vrai changement aux termes actuels.',
                    'cand.changeBeforeUpdate': 'Modifiez le message, le budget ou le delai avant de mettre a jour votre derniere proposition.',
                    'cand.changeBeforeAnotherStep': 'Modifiez le message, le budget ou le delai avant d envoyer une autre etape de negociation.',
                    'cand.draftResponse': 'Reponse brouillon',
                    'cand.acceptedInvitation': 'Invitation acceptee',
                    'cand.underReview': 'Reponse en examen',
                    'cand.negotiationRequested': 'Negociation demandee',
                    'cand.acceptedTerms': 'Termes acceptes',
                    'cand.refusedBrand': 'Refusee par la marque',
                    'cand.declinedInvitation': 'Invitation refusee'
                }
            };
            const textKeys = {
                'Candidature unavailable': 'cand.unavailable',
                'Back to my candidatures': 'cand.backMine',
                'View source offer': 'cand.viewSourceOffer',
                'Browse campaign opportunities': 'cand.browseCampaigns',
                'Offer invitation': 'cand.offerInvitation',
                'Campaign application': 'cand.campaignApplication',
                'Acceptance response': 'cand.acceptanceResponse',
                'Negotiation request': 'cand.negotiationRequest',
                'Decline response': 'cand.declineResponse',
                'Response context': 'cand.responseContext',
                'Keep the brand and source details visible while you prepare or review the candidature.': 'cand.responseContextCopy',
                'Brand': 'cand.brand',
                'Unknown brand': 'cand.unknownBrand',
                'Offer objective': 'cand.offerObjective',
                'Campaign description': 'cand.campaignDescription',
                'Source published': 'cand.sourcePublished',
                'Source deadline': 'cand.sourceDeadline',
                'Campaign start date': 'cand.campaignStart',
                'Campaign end date': 'cand.campaignEnd',
                'Your response': 'cand.yourResponse',
                'Current status': 'cand.currentStatus',
                'Response type': 'cand.responseType',
                'Budget reply': 'cand.budgetReply',
                'Timeline reply': 'cand.timelineReply',
                'Availability': 'cand.availability',
                'Creator message': 'cand.creatorMessage',
                'Creator terms and conditions': 'cand.creatorTerms',
                'Portfolio URL': 'cand.portfolio',
                'CV or reference file': 'cand.cvFile',
                'No file attached yet': 'cand.noFile',
                'No portfolio link attached yet': 'cand.noPortfolio',
                'Negotiation thread': 'cand.negotiationThread',
                'Latest brand update': 'cand.latestBrandUpdate',
                'Your latest proposal': 'cand.yourLatestProposal',
                'Latest proposal edit': 'cand.latestProposalEdit',
                'Counter-proposal': 'cand.counterProposal',
                'Selected offer': 'cand.selectedOffer',
                'Candidature source': 'cand.candidatureSource',
                'Availability and delivery': 'cand.availabilityDelivery',
                'Share when you can start and how long the delivery should take.': 'cand.availabilityDeliveryCopy',
                'Availability start date': 'cand.availabilityStart',
                'Delivery delay': 'cand.deliveryDelay',
                'Proposed delay': 'cand.proposedDelay',
                'Negotiation message': 'cand.negotiationMessage',
                'Creator message / motivation': 'cand.messageMotivation',
                'Profile support': 'cand.profileSupport',
                'Attach simple references the brand can review with your response.': 'cand.profileSupportCopy',
                'Negotiation terms': 'cand.negotiationTerms',
                'Add the budget you want to propose for this collaboration.': 'cand.negotiationTermsCopy',
                'Proposed budget': 'cand.proposedBudget',
                'Application message': 'cand.applicationMessage',
                'Explain why you want to join this campaign and what your content can bring to it.': 'cand.applicationMessageCopy',
                'Why do you want to join this campaign?': 'cand.whyJoin',
                'Your proposed content idea': 'cand.contentIdea',
                'Add the creator-side concept, terms, or production notes the brand should review.': 'cand.contentIdeaCopy',
                'Estimated delivery delay': 'cand.estimatedDelay',
                'CV / profile support upload': 'cand.supportUpload',
                'Keep reviewing': 'cand.keepReviewing',
                'Save as draft': 'cand.saveDraft',
                'Send negotiation': 'cand.sendNegotiation',
                'Send counter-proposal': 'cand.sendCounter',
                'Update proposal': 'cand.updateProposal',
                'Dates and review markers': 'cand.datesMarkers',
                'Timeline': 'cand.timeline',
                'Draft response': 'cand.draftResponse',
                'Accepted invitation': 'cand.acceptedInvitation',
                'Response under review': 'cand.underReview',
                'Negotiation requested': 'cand.negotiationRequested',
                'Accepted terms': 'cand.acceptedTerms',
                'Refused by brand': 'cand.refusedBrand',
                'Declined invitation': 'cand.declinedInvitation'
            };
            const placeholderKeys = {
                'Explain the revised budget, timing, or collaboration context.': 'cand.negotiationMessage',
                'Reply to the latest brand update and explain the new terms you want to propose.': 'cand.negotiationMessage',
                'Explain why this collaboration fits your content, audience, or execution approach.': 'cand.messageMotivation',
                'Add optional creator-side conditions, process notes, or collaboration boundaries.': 'cand.creatorTerms',
                'Share your campaign fit, audience angle, and proposed execution.': 'cand.applicationMessage',
                'Describe your content idea, creator terms, usage boundaries, or production process.': 'cand.contentIdea',
                'Explain why you are declining this invitation.': 'cand.refusalReason',
                'Add a short final note if you want to keep extra context.': 'cand.optionalShortNote'
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
                root.querySelectorAll('[placeholder]').forEach((el) => {
                    const key = placeholderKeys[el.getAttribute('placeholder')] || el.getAttribute('data-i18n-placeholder');
                    if (key && dict[key]) {
                        el.setAttribute('data-i18n-placeholder', key);
                        el.setAttribute('placeholder', dict[key]);
                    }
                });
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
                document.addEventListener('click', () => window.setTimeout(applyCandidatureTranslations, 0), true);
            });
            window.addEventListener('cre8:languagechange', () => applyCandidatureTranslations());
        })();
    </script>
    <?php require __DIR__ . '/../layout/footer.php'; ?>
    <script src="../layout/front-header.js"></script>
</body>
</html>
