<?php
session_start();

if (!isset($_SESSION['utilisateur'])) {
    $_SESSION['utilisateur']['id'] = 1;
}

require_once __DIR__ . '/../../../Controleur/offreC.php';

$controller = new OffreC();
$brandId = $_SESSION['utilisateur']['id'];
$creatorPageSize = 6;
$errors = [];
$offer = null;
$currentResponse = null;
$isEditLocked = false;
$isCreatorFixed = false;
$isPublicationDateLocked = false;
$saveMode = 'publish';
$idOffre = isset($_GET['idOffre']) && is_numeric($_GET['idOffre']) ? (int) $_GET['idOffre'] : null;
$form = [
    'idCreateurCible' => '',
    'titre' => '',
    'description' => '',
    'objectif' => '',
    'budgetPropose' => '',
    'datePublication' => '',
    'dateLimite' => '',
    'raisonChoix' => '',
    'messagePersonnalise' => '',
    'attenteCollaboration' => '',
];

function creatorStatusLabel($status)
{
    return match ($status) {
        'actif' => 'Ready',
        'en_attente' => 'Pending review',
        'suspendu' => 'Limited',
        default => ucfirst((string) $status),
    };
}

function creatorStatusClass($status)
{
    return match ($status) {
        'actif' => 'active',
        'en_attente' => 'pending',
        default => '',
    };
}

function renderCreatorPickerCard(array $creator, $selectedCreatorId = 0)
{
    $creatorId = (int) ($creator['id'] ?? 0);
    $isSelected = $selectedCreatorId === $creatorId;
    $status = (string) ($creator['statut'] ?? '');
    $targetedOffers = (int) ($creator['targetedOffers'] ?? 0);
    $liveOffers = (int) ($creator['liveOffers'] ?? 0);
    ?>
    <button
        type="button"
        class="creator-option creator-option-button<?php echo $isSelected ? ' is-selected' : ''; ?>"
        data-creator-id="<?php echo $creatorId; ?>"
        data-name="<?php echo htmlspecialchars((string) ($creator['nom'] ?? '')); ?>"
        data-email="<?php echo htmlspecialchars((string) ($creator['email'] ?? '')); ?>"
        data-status-label="<?php echo htmlspecialchars(creatorStatusLabel($status)); ?>"
        data-status-class="<?php echo htmlspecialchars(creatorStatusClass($status)); ?>"
        data-targeted="<?php echo $targetedOffers; ?>"
        data-live="<?php echo $liveOffers; ?>"
    >
        <span class="creator-option-body">
            <span class="creator-selected-badge" aria-hidden="true">Selected</span>
            <span class="creator-top">
                <span>
                    <strong><?php echo htmlspecialchars((string) ($creator['nom'] ?? '')); ?></strong>
                    <span><?php echo htmlspecialchars((string) ($creator['email'] ?? '')); ?></span>
                </span>
                <span class="creator-pill <?php echo htmlspecialchars(creatorStatusClass($status)); ?>"><?php echo htmlspecialchars(creatorStatusLabel($status)); ?></span>
            </span>
            <span class="creator-meta">
                <span class="creator-pill">ID #<?php echo $creatorId; ?></span>
                <span class="creator-pill"><?php echo $targetedOffers; ?> targeted offers</span>
                <span class="creator-pill"><?php echo $liveOffers; ?> live</span>
            </span>
        </span>
    </button>
    <?php
}

function responseStatusLabel($status)
{
    return match ($status) {
        'envoyee', 'en_attente' => 'Accepted - waiting',
        'negociation', 'en_etude' => 'Negotiation requested',
        'acceptee' => 'Approved',
        'refusee' => 'Declined by brand',
        'retiree' => 'Declined by creator',
        default => ucfirst(str_replace('_', ' ', (string) $status)),
    };
}

function responseStatusClass($status)
{
    return match ($status) {
        'envoyee', 'en_attente' => 'pending',
        'negociation', 'en_etude' => 'review',
        'acceptee' => 'accepted',
        'refusee', 'retiree' => 'declined',
        default => 'pending',
    };
}

function isAcceptedResponseLockStatus($status)
{
    return in_array((string) $status, ['envoyee', 'en_attente', 'acceptee'], true);
}

function shouldLockPastPublicationDate($offer)
{
    if (!$offer || $offer->getStatutOffre() !== 'publiee') {
        return false;
    }

    $publicationDate = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $offer->getDatePublication(), new DateTimeZone('Africa/Tunis'));
    if (!$publicationDate) {
        return false;
    }

    return $publicationDate < new DateTimeImmutable('today', new DateTimeZone('Africa/Tunis'));
}

if (isset($_GET['ajax']) && $_GET['ajax'] === 'creators') {
    $keyword = isset($_GET['keyword']) ? trim((string) $_GET['keyword']) : null;
    $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int) $_GET['limit'] : $creatorPageSize;
    $offset = isset($_GET['offset']) && is_numeric($_GET['offset']) ? (int) $_GET['offset'] : 0;

    $page = $controller->getAvailableCreatorsPage($keyword, $limit, $offset);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($page);
    exit;
}

$creatorPage = $controller->getAvailableCreatorsPage(null, $creatorPageSize, 0);
$availableCreators = $creatorPage['items'];
$creatorHasMore = $creatorPage['hasMore'];
$selectedCreatorProfile = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $saveMode = (isset($_POST['saveMode']) && $_POST['saveMode'] === 'draft') ? 'draft' : 'publish';
    $idOffre = isset($_POST['idOffre']) && is_numeric($_POST['idOffre']) ? (int) $_POST['idOffre'] : null;
    $offer = $idOffre ? $controller->getOffreById($idOffre, $brandId) : null;
    $targetStatus = $saveMode === 'draft' ? 'brouillon' : 'publiee';

    $form = [
        'idCreateurCible' => $_POST['idCreateurCible'] ?? '',
        'titre' => $_POST['titre'] ?? '',
        'description' => $_POST['description'] ?? '',
        'objectif' => $_POST['objectif'] ?? '',
        'budgetPropose' => $_POST['budgetPropose'] ?? '',
        'datePublication' => $_POST['datePublication'] ?? '',
        'dateLimite' => $_POST['dateLimite'] ?? '',
        'raisonChoix' => $_POST['raisonChoix'] ?? '',
        'messagePersonnalise' => $_POST['messagePersonnalise'] ?? '',
        'attenteCollaboration' => $_POST['attenteCollaboration'] ?? '',
    ];

    if (!$offer) {
        $errors[] = 'The offer you are trying to edit could not be found.';
    }

    $isPublicationDateLocked = shouldLockPastPublicationDate($offer);

    if ($offer && !$offer->isDraftSansCreateur() && $offer->getIdCreateurCible()) {
        $form['idCreateurCible'] = (string) $offer->getIdCreateurCible();
        $isCreatorFixed = true;
    }

    if ($offer && !$offer->isDraftSansCreateur() && $offer->getIdCreateurCible()) {
        $currentResponse = $controller->getOfferResponseByCreator($offer->getIdCreateurCible(), $offer->getIdOffre());
        $isEditLocked = $currentResponse && isAcceptedResponseLockStatus($currentResponse['statutCandidature'] ?? '');
    }

    if ($isEditLocked) {
        $errors[] = 'This offer is locked because the targeted creator already accepted it.';
    } else {
        $errors = array_merge($errors, $controller->validateOffreData($form, $saveMode, [
            'allowPastPublicationDate' => $isPublicationDateLocked,
        ]));
    }

    $creatorId = isset($form['idCreateurCible']) && is_numeric($form['idCreateurCible']) ? (int) $form['idCreateurCible'] : 0;
    $selectedCreatorProfile = $controller->getCreatorPickerProfile($creatorId);

    if ($saveMode !== 'draft' && !$selectedCreatorProfile) {
        $errors[] = 'Please choose a valid creator for this targeted offer.';
    } elseif ($creatorId > 0 && !$selectedCreatorProfile) {
        $errors[] = 'The selected creator could not be found anymore. Please choose another one.';
    }

    if (empty($errors) && !$isEditLocked) {
        $draftWithoutCreator = $saveMode === 'draft' && $creatorId <= 0;
        $persistedCreatorId = $isCreatorFixed && $offer ? $offer->getIdCreateurCible() : ($creatorId > 0 ? $creatorId : null);
        if ($draftWithoutCreator) {
            $persistedCreatorId = $controller->getDraftPlaceholderCreatorId();
            if (!$persistedCreatorId) {
                $errors[] = 'A creator account must exist in the platform before this draft can be stored.';
            }
        }

        if (empty($errors)) {
        $publicationDate = trim((string) $form['datePublication']) !== '' ? trim((string) $form['datePublication']) : date('Y-m-d');
        $deadlineDate = trim((string) $form['dateLimite']) !== ''
            ? trim((string) $form['dateLimite'])
            : date('Y-m-d', strtotime($publicationDate . ' +14 days'));
        $budgetValue = trim((string) $form['budgetPropose']) !== '' && is_numeric($form['budgetPropose'])
            ? (float) $form['budgetPropose']
            : 0.0;
        $updatedOffer = new Offre(
            $idOffre,
            $brandId,
            $persistedCreatorId,
            trim($form['titre']),
            trim($form['description']),
            trim($form['objectif']),
            $budgetValue,
            $publicationDate,
            $deadlineDate,
            $targetStatus,
            trim($form['raisonChoix']),
            trim($form['messagePersonnalise']),
            trim($form['attenteCollaboration']),
            $draftWithoutCreator
        );

        if ($controller->updateOffre($updatedOffer)) {
            $flashMessage = $saveMode === 'draft' ? 'Draft saved successfully.' : 'Offer published successfully.';
            header('Location: brand_index.php?message=' . urlencode($flashMessage));
            exit;
        }

        $errors[] = 'Unable to update the offer right now.';
        }
    }
} elseif ($idOffre) {
    $offer = $controller->getOffreById($idOffre, $brandId);
    if ($offer) {
        $form = [
            'idCreateurCible' => $offer->isDraftSansCreateur() ? '' : $offer->getIdCreateurCible(),
            'titre' => $offer->getTitre(),
            'description' => $offer->getDescription(),
            'objectif' => $offer->getObjectif(),
            'budgetPropose' => $offer->getBudgetPropose(),
            'datePublication' => $offer->getDatePublication(),
            'dateLimite' => $offer->getDateLimite(),
            'raisonChoix' => $offer->getRaisonChoix(),
            'messagePersonnalise' => $offer->getMessagePersonnalise(),
            'attenteCollaboration' => $offer->getAttenteCollaboration(),
        ];
        $currentResponse = !$offer->isDraftSansCreateur() && $offer->getIdCreateurCible()
            ? $controller->getOfferResponseByCreator($offer->getIdCreateurCible(), $offer->getIdOffre())
            : null;
        $isEditLocked = $currentResponse && isAcceptedResponseLockStatus($currentResponse['statutCandidature'] ?? '');
        $isCreatorFixed = !$offer->isDraftSansCreateur() && (bool) $offer->getIdCreateurCible();
        $isPublicationDateLocked = shouldLockPastPublicationDate($offer);
    }
}

$selectedCreatorId = isset($form['idCreateurCible']) && is_numeric($form['idCreateurCible']) ? (int) $form['idCreateurCible'] : 0;
if (!$selectedCreatorProfile && $selectedCreatorId > 0) {
    $selectedCreatorProfile = $controller->getCreatorPickerProfile($selectedCreatorId);
}

$isDraftOffer = $offer && $offer->getStatutOffre() === 'brouillon';
$publishButtonLabel = $isDraftOffer ? 'Publish offer' : 'Publish updates';
$draftButtonLabel = $isDraftOffer ? 'Keep as draft' : 'Save as draft';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Targeted Offer - Cre8Connect</title>
    <link rel="stylesheet" href="../css/frontoffice.css">
    <link rel="stylesheet" href="offre.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/offre.css')); ?>">
</head>
<body>
    <?php require_once dirname(__DIR__) . '/layout/header.php'; ?>
    <main class="container py-5">
        <div class="offre-page-shell">
            <?php if (!$offer && $_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
                <div class="empty-state-card">
                    <div class="empty-state-icon">!</div>
                    <h2 class="section-title">Offer not found</h2>
                    <p class="section-subtitle">The targeted offer does not exist or you do not have permission to edit it.</p>
                    <div class="compact-actions justify-content-center mt-4">
                        <a class="btn btn-primary" href="brand_index.php">Back to my offers</a>
                    </div>
                </div>
            <?php else: ?>
                <section class="module-hero">
                    <span class="module-eyebrow">Brand workspace</span>
                    <h1 class="display-5 fw-bold mt-3 mb-2 gradient-title">Refine your targeted offer</h1>
                    <p class="lead text-muted"><?php echo $isEditLocked ? 'This accepted offer is now locked to preserve the agreed collaboration brief.' : 'Update the brief, budget, and dates while keeping the same offer flow.'; ?></p>
                </section>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Please review the changes.</strong>
                        <ul class="mb-0 mt-2">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($isEditLocked): ?>
                    <section class="section-card">
                        <h2 class="section-title">Editing locked after creator acceptance</h2>
                        <p class="section-subtitle">The targeted creator already accepted this offer, so the brand can review the response but cannot change the brief anymore.</p>

                        <div class="detail-columns mt-4">
                            <div class="info-card">
                                <h3 class="section-title">Current response</h3>
                                <div class="review-list mt-3">
                                    <div class="review-item">
                                        <strong>Status</strong>
                                        <span class="response-status <?php echo htmlspecialchars(responseStatusClass($currentResponse['statutCandidature'] ?? '')); ?>"><?php echo htmlspecialchars(responseStatusLabel($currentResponse['statutCandidature'] ?? '')); ?></span>
                                    </div>
                                    <div class="review-item">
                                        <strong>Budget reply</strong>
                                        <span>EUR <?php echo htmlspecialchars((string) ($currentResponse['budgetPropose'] ?? $offer->getBudgetPropose())); ?></span>
                                    </div>
                                    <div class="review-item">
                                        <strong>Creator message</strong>
                                        <span><?php echo htmlspecialchars((string) ($currentResponse['messageMotivation'] ?? 'No creator message was submitted.')); ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="info-card">
                                <h3 class="section-title">Next steps</h3>
                                <div class="note-block mt-3">
                                    <strong>Why editing is blocked</strong>
                                    <p>Once the targeted creator accepts, the offer becomes a fixed agreement point and can no longer be edited by the brand.</p>
                                </div>
                                <div class="compact-actions mt-4">
                                    <a class="btn btn-primary" href="brand_details.php?idOffre=<?php echo (int) $offer->getIdOffre(); ?>">Open offer details</a>
                                    <a class="btn btn-outline-secondary" href="brand_index.php">Back to my offers</a>
                                </div>
                            </div>
                        </div>
                    </section>
                <?php else: ?>
                <div class="wizard-layout">
                    <form method="post" action="brand_edit.php" class="wizard-stack" data-module-validation="brand-offer" novalidate>
                        <input type="hidden" name="idOffre" value="<?php echo (int) $idOffre; ?>">

                        <section class="wizard-card">
                            <div class="wizard-step-head">
                                <span class="wizard-step-number">1</span>
                                <div>
                                    <h3>Target creator</h3>
                                    <p><?php echo $isCreatorFixed ? 'The selected creator stays the same while you edit the rest of the offer.' : 'Select the creator before you publish this offer.'; ?></p>
                                </div>
                            </div>

                            <?php if ($isCreatorFixed): ?>
                                <input type="hidden" name="idCreateurCible" id="idCreateurCible" value="<?php echo $selectedCreatorId > 0 ? $selectedCreatorId : ''; ?>">
                                <div class="creator-selection-summary is-locked">
                                    <div class="creator-selection-copy">
                                        <span class="creator-selection-label">Selected creator</span>
                                        <strong><?php echo $selectedCreatorProfile ? htmlspecialchars((string) $selectedCreatorProfile['nom']) : 'Creator not found'; ?></strong>
                                        <span><?php echo $selectedCreatorProfile ? htmlspecialchars((string) ($selectedCreatorProfile['email'] ?? '')) : 'This creator record is no longer available.'; ?></span>
                                    </div>
                                    <div class="creator-meta">
                                        <?php if ($selectedCreatorProfile): ?>
                                            <span class="creator-pill <?php echo htmlspecialchars(creatorStatusClass($selectedCreatorProfile['statut'])); ?>"><?php echo htmlspecialchars(creatorStatusLabel($selectedCreatorProfile['statut'])); ?></span>
                                            <span class="creator-pill"><?php echo (int) $selectedCreatorProfile['targetedOffers']; ?> targeted offers</span>
                                            <span class="creator-pill"><?php echo (int) $selectedCreatorProfile['liveOffers']; ?> live</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="note-block mt-3">
                                    <strong>Creator locked</strong>
                                    <p>This offer stays linked to the same creator. You can still update the brief, budget, and dates.</p>
                                </div>
                            <?php else: ?>
                                <div
                                    class="creator-picker"
                                    data-creator-picker
                                    data-endpoint="brand_edit.php?ajax=creators"
                                    data-page-size="<?php echo (int) $creatorPageSize; ?>"
                                    data-has-more="<?php echo $creatorHasMore ? '1' : '0'; ?>"
                                    data-initial-count="<?php echo count($availableCreators); ?>"
                                    data-selected-id="<?php echo $selectedCreatorProfile ? (int) $selectedCreatorProfile['id'] : 0; ?>"
                                    data-selected-name="<?php echo $selectedCreatorProfile ? htmlspecialchars((string) $selectedCreatorProfile['nom']) : ''; ?>"
                                    data-selected-email="<?php echo $selectedCreatorProfile ? htmlspecialchars((string) ($selectedCreatorProfile['email'] ?? '')) : ''; ?>"
                                    data-selected-status="<?php echo $selectedCreatorProfile ? htmlspecialchars(creatorStatusLabel($selectedCreatorProfile['statut'])) : ''; ?>"
                                    data-selected-status-class="<?php echo $selectedCreatorProfile ? htmlspecialchars(creatorStatusClass($selectedCreatorProfile['statut'])) : ''; ?>"
                                    data-selected-targeted="<?php echo $selectedCreatorProfile ? (int) $selectedCreatorProfile['targetedOffers'] : 0; ?>"
                                    data-selected-live="<?php echo $selectedCreatorProfile ? (int) $selectedCreatorProfile['liveOffers'] : 0; ?>"
                                >
                                    <input type="hidden" name="idCreateurCible" id="idCreateurCible" value="<?php echo $selectedCreatorId > 0 ? $selectedCreatorId : ''; ?>">

                                    <div class="creator-search">
                                        <div class="creator-search-head">
                                            <label for="creatorSearch" class="form-label fw-semibold">Search creators</label>
                                            <span class="creator-search-status" data-creator-status>Browse the available creators or search by name, email, or ID.</span>
                                        </div>
                                        <input type="search" id="creatorSearch" class="form-control" data-creator-search placeholder="Search by name, email, or ID" autocomplete="off">
                                    </div>

                                    <div class="creator-selection-summary<?php echo $selectedCreatorProfile ? '' : ' is-hidden'; ?>" data-selected-summary>
                                        <div class="creator-selection-copy">
                                            <span class="creator-selection-label">Selected creator</span>
                                            <strong data-selected-name><?php echo $selectedCreatorProfile ? htmlspecialchars((string) $selectedCreatorProfile['nom']) : ''; ?></strong>
                                            <span data-selected-email><?php echo $selectedCreatorProfile ? htmlspecialchars((string) ($selectedCreatorProfile['email'] ?? '')) : ''; ?></span>
                                        </div>
                                        <div class="creator-meta" data-selected-meta>
                                            <?php if ($selectedCreatorProfile): ?>
                                                <span class="creator-pill <?php echo htmlspecialchars(creatorStatusClass($selectedCreatorProfile['statut'])); ?>"><?php echo htmlspecialchars(creatorStatusLabel($selectedCreatorProfile['statut'])); ?></span>
                                                <span class="creator-pill"><?php echo (int) $selectedCreatorProfile['targetedOffers']; ?> targeted offers</span>
                                                <span class="creator-pill"><?php echo (int) $selectedCreatorProfile['liveOffers']; ?> live</span>
                                            <?php endif; ?>
                                        </div>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" data-clear-selection>Clear</button>
                                    </div>

                                    <div class="creator-grid<?php echo count($availableCreators) > 0 ? '' : ' is-hidden'; ?>" id="creatorGrid" data-creator-grid>
                                        <?php foreach ($availableCreators as $creator): ?>
                                            <?php renderCreatorPickerCard($creator, $selectedCreatorId); ?>
                                        <?php endforeach; ?>
                                    </div>

                                    <div class="empty-state-card<?php echo count($availableCreators) > 0 ? ' is-hidden' : ''; ?>" data-creator-empty>
                                        <div class="empty-state-icon">!</div>
                                        <h3 class="section-title" data-creator-empty-title>No creators available</h3>
                                        <p class="section-subtitle" data-creator-empty-copy>Creator accounts will appear here when they are available for offers.</p>
                                    </div>

                                    <div class="creator-browser-footer<?php echo count($availableCreators) > 0 ? '' : ' is-hidden'; ?>" data-creator-footer>
                                        <button type="button" class="btn btn-outline-secondary" data-creator-load-more<?php echo $creatorHasMore ? '' : ' hidden'; ?>>Load more creators</button>
                                        <span class="creator-search-status" data-creator-results>
                                            Showing <?php echo count($availableCreators); ?> creators in the current list.
                                        </span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </section>

                        <section class="wizard-card">
                            <div class="wizard-step-head">
                                <span class="wizard-step-number">2</span>
                                <div>
                                    <h3>Collaboration fit and message</h3>
                                    <p>Add optional context so the creator understands why this invitation fits.</p>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="raisonChoix" class="form-label fw-semibold">Why this creator?</label>
                                    <textarea class="form-control" id="raisonChoix" name="raisonChoix" rows="3" data-cre8pilot-field="raisonChoix" placeholder="Audience fit, delivery style, category expertise..."><?php echo htmlspecialchars($form['raisonChoix']); ?></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label for="attenteCollaboration" class="form-label fw-semibold">Expected collaboration fit</label>
                                    <textarea class="form-control" id="attenteCollaboration" name="attenteCollaboration" rows="4" data-cre8pilot-field="attenteCollaboration" placeholder="Outline the working style or deliverable expectations."><?php echo htmlspecialchars($form['attenteCollaboration']); ?></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label for="messagePersonnalise" class="form-label fw-semibold">Personal note</label>
                                    <textarea class="form-control" id="messagePersonnalise" name="messagePersonnalise" rows="4" data-cre8pilot-field="messagePersonnalise" placeholder="Optional direct note to the creator."><?php echo htmlspecialchars($form['messagePersonnalise']); ?></textarea>
                                </div>
                            </div>
                        </section>

                        <section class="wizard-card">
                            <div class="wizard-step-head">
                                <span class="wizard-step-number">3</span>
                                <div>
                                    <h3>Offer details</h3>
                                    <p>Keep the brief practical and easy to assess at a glance.</p>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="titre" class="form-label fw-semibold">Offer title</label>
                                    <input type="text" class="form-control form-control-lg" id="titre" name="titre" data-cre8pilot-field="titre" value="<?php echo htmlspecialchars($form['titre']); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="objectif" class="form-label fw-semibold">Objective</label>
                                    <input type="text" class="form-control" id="objectif" name="objectif" data-cre8pilot-field="objectif" value="<?php echo htmlspecialchars($form['objectif']); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="budgetPropose" class="form-label fw-semibold">Proposed budget</label>
                                    <div class="input-group">
                                        <span class="input-group-text">EUR</span>
                                        <input type="number" class="form-control" id="budgetPropose" name="budgetPropose" step="0.01" data-cre8pilot-field="budgetPropose" value="<?php echo htmlspecialchars($form['budgetPropose']); ?>">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label for="description" class="form-label fw-semibold">Detailed description</label>
                                    <textarea class="form-control" id="description" name="description" rows="5" data-cre8pilot-field="description"><?php echo htmlspecialchars($form['description']); ?></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label for="<?php echo $isPublicationDateLocked ? 'datePublicationDisplay' : 'datePublication'; ?>" class="form-label fw-semibold">Publication date</label>
                                    <?php if ($isPublicationDateLocked): ?>
                                        <input type="hidden" id="datePublication" name="datePublication" value="<?php echo htmlspecialchars($form['datePublication']); ?>" data-allow-past-publication="1">
                                        <input type="date" class="form-control locked-date-input" id="datePublicationDisplay" value="<?php echo htmlspecialchars($form['datePublication']); ?>" disabled>
                                        <p class="composer-context-note mt-2">This offer is already live, so the original publication date stays locked.</p>
                                    <?php else: ?>
                                        <input type="date" class="form-control" id="datePublication" name="datePublication" value="<?php echo htmlspecialchars($form['datePublication']); ?>">
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <label for="dateLimite" class="form-label fw-semibold">Deadline</label>
                                    <input type="date" class="form-control" id="dateLimite" name="dateLimite" data-cre8pilot-field="dateLimite" value="<?php echo htmlspecialchars($form['dateLimite']); ?>">
                                </div>
                            </div>
                        </section>

                        <section class="wizard-card">
                            <div class="wizard-step-head">
                                <span class="wizard-step-number">4</span>
                                <div>
                                    <h3>Save the improved offer</h3>
                                    <p>Review the summary, then publish the changes or keep the offer as a draft.</p>
                                </div>
                            </div>

                            <div class="compact-actions">
                                <button type="submit" name="saveMode" value="publish" data-validation-intent="publish" class="btn btn-primary btn-lg"><?php echo htmlspecialchars($publishButtonLabel); ?></button>
                                <button type="submit" name="saveMode" value="draft" data-validation-intent="draft" class="btn btn-outline-primary btn-lg"><?php echo htmlspecialchars($draftButtonLabel); ?></button>
                                <a class="btn btn-outline-secondary btn-lg" href="brand_details.php?idOffre=<?php echo (int) $idOffre; ?>">Cancel</a>
                            </div>
                        </section>
                    </form>

                    <aside class="wizard-sidebar">
                        <section class="info-card live-review-card" data-live-review-card>
                            <h2 class="section-title">Live review</h2>
                            <p class="section-subtitle">A quick summary of the current creator, the offer context, and the updated details.</p>
                            <div class="review-list mt-4">
                                <div class="review-item" data-review-item="creator">
                                    <strong>Selected creator</strong>
                                    <span id="reviewCreator">Select a creator for this offer.</span>
                                </div>
                                <div class="review-item" data-review-item="title">
                                    <strong>Offer title</strong>
                                    <span id="reviewTitle"><?php echo htmlspecialchars($form['titre'] ?: 'Your title will appear here.'); ?></span>
                                </div>
                                <div class="review-item" data-review-item="objective">
                                    <strong>Objective</strong>
                                    <span id="reviewObjective"><?php echo htmlspecialchars($form['objectif'] ?: 'Define the collaboration goal.'); ?></span>
                                </div>
                                <div class="review-item" data-review-item="budget">
                                    <strong>Budget and timing</strong>
                                    <span id="reviewBudget">Update the budget and schedule.</span>
                                </div>
                                <div class="review-item" data-review-item="fit">
                                    <strong>Collaboration fit</strong>
                                    <span id="reviewFit">Add optional context about the collaboration fit.</span>
                                </div>
                            </div>
                        </section>

                        <?php if ($currentResponse): ?>
                            <section class="info-card">
                                <h2 class="section-title">Current creator response</h2>
                                <div class="review-list mt-4">
                                    <div class="review-item">
                                        <strong>Status</strong>
                                        <span class="response-status <?php echo htmlspecialchars(responseStatusClass($currentResponse['statutCandidature'])); ?>"><?php echo htmlspecialchars(responseStatusLabel($currentResponse['statutCandidature'])); ?></span>
                                    </div>
                                    <div class="review-item">
                                        <strong>Proposed budget</strong>
                                        <span>EUR <?php echo htmlspecialchars($currentResponse['budgetPropose']); ?></span>
                                    </div>
                                    <div class="review-item">
                                        <strong>Creator message</strong>
                                        <span><?php echo htmlspecialchars($currentResponse['messageMotivation']); ?></span>
                                    </div>
                                </div>
                            </section>
                        <?php endif; ?>
                    </aside>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
    <script src="offre-validation.js?v=<?php echo urlencode((string) filemtime(__DIR__ . '/offre-validation.js')); ?>"></script>
    <script src="offre-creator-picker.js?v=<?php echo urlencode((string) filemtime(__DIR__ . '/offre-creator-picker.js')); ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const creatorPicker = document.querySelector('[data-creator-picker]');
            const fixedSelectedCreator = <?php echo json_encode(
                $selectedCreatorProfile
                    ? [
                        'name' => (string) ($selectedCreatorProfile['nom'] ?? ''),
                        'email' => (string) ($selectedCreatorProfile['email'] ?? ''),
                        'status' => (string) creatorStatusLabel($selectedCreatorProfile['statut'] ?? ''),
                        'targeted' => (string) ((int) ($selectedCreatorProfile['targetedOffers'] ?? 0)),
                    ]
                    : null,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ); ?>;
            const reviewCreator = document.getElementById('reviewCreator');
            const reviewTitle = document.getElementById('reviewTitle');
            const reviewObjective = document.getElementById('reviewObjective');
            const reviewBudget = document.getElementById('reviewBudget');
            const reviewFit = document.getElementById('reviewFit');
            const liveReviewCard = document.querySelector('[data-live-review-card]');
            const titleInput = document.getElementById('titre');
            const objectiveInput = document.getElementById('objectif');
            const budgetInput = document.getElementById('budgetPropose');
            const descriptionInput = document.getElementById('description');
            const publicationInput = document.getElementById('datePublication');
            const deadlineInput = document.getElementById('dateLimite');
            const reasonInput = document.getElementById('raisonChoix');
            const noteInput = document.getElementById('messagePersonnalise');
            const expectationInput = document.getElementById('attenteCollaboration');
            const reviewItems = {
                creator: document.querySelector('[data-review-item="creator"]'),
                title: document.querySelector('[data-review-item="title"]'),
                objective: document.querySelector('[data-review-item="objective"]'),
                budget: document.querySelector('[data-review-item="budget"]'),
                fit: document.querySelector('[data-review-item="fit"]')
            };

            function getSelectedCreator() {
                if (creatorPicker && creatorPicker.dataset.selectedId && creatorPicker.dataset.selectedId !== '0') {
                    return {
                        name: creatorPicker.dataset.selectedName || '',
                        email: creatorPicker.dataset.selectedEmail || '',
                        status: creatorPicker.dataset.selectedStatus || '',
                        targeted: creatorPicker.dataset.selectedTargeted || ''
                    };
                }

                return fixedSelectedCreator;
            }

            function updateCreatorReview() {
                const selected = getSelectedCreator();
                if (!selected) {
                    reviewCreator.textContent = 'Select a creator for this offer.';
                    return;
                }

                const details = [
                    selected.name || 'Unknown creator',
                    selected.email || '',
                    selected.status ? 'Status: ' + selected.status : '',
                    selected.targeted ? selected.targeted + ' targeted offers so far' : ''
                ].filter(Boolean);

                reviewCreator.textContent = details.join(' | ');
            }

            function updateTextReview() {
                reviewTitle.textContent = titleInput.value.trim() || 'Your title will appear here.';
                reviewObjective.textContent = objectiveInput.value.trim() || 'Define the collaboration goal.';

                const budget = budgetInput.value.trim();
                const publication = publicationInput.value.trim();
                const deadline = deadlineInput.value.trim();
                const parts = [];

                if (budget) {
                    parts.push('EUR ' + budget);
                }
                if (publication) {
                    parts.push('Publish: ' + publication);
                }
                if (deadline) {
                    parts.push('Deadline: ' + deadline);
                }

                reviewBudget.textContent = parts.length ? parts.join(' | ') : 'Update the budget and schedule.';

                const fitBits = [
                    reasonInput.value.trim(),
                    expectationInput.value.trim(),
                    noteInput.value.trim()
                ].filter(Boolean);
                reviewFit.textContent = fitBits.length ? fitBits.join(' | ') : 'Add optional context about the collaboration fit.';
            }

            function parseDateInput(value) {
                if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) {
                    return null;
                }

                const date = new Date(value + 'T00:00:00');
                return Number.isNaN(date.getTime()) ? null : date;
            }

            function isBudgetAndTimingComplete() {
                const budgetValue = Number(String(budgetInput.value || '').replace(',', '.'));
                const publicationDate = parseDateInput(publicationInput.value.trim());
                const deadlineDate = parseDateInput(deadlineInput.value.trim());
                const today = new Date();
                today.setHours(0, 0, 0, 0);

                if (!Number.isFinite(budgetValue) || budgetValue <= 0 || !publicationDate || !deadlineDate) {
                    return false;
                }

                const allowPastPublication = publicationInput.dataset.allowPastPublication === '1';

                return (allowPastPublication || publicationDate >= today) && deadlineDate >= today && deadlineDate > publicationDate;
            }

            function setReviewCompletionState(key, isComplete) {
                const node = reviewItems[key];
                if (!node) {
                    return;
                }

                node.classList.toggle('is-complete', Boolean(isComplete));
            }

            function updateReviewCompletion() {
                const creatorComplete = !!getSelectedCreator();
                const titleComplete = titleInput.value.trim().length >= 3;
                const objectiveComplete = objectiveInput.value.trim().length >= 3;
                const fitComplete = [reasonInput, noteInput, expectationInput].some(function (input) {
                    return input && input.value.trim() !== '';
                });
                const budgetComplete = isBudgetAndTimingComplete();
                const descriptionComplete = descriptionInput.value.trim().length >= 20;
                const hasAnyProgress = creatorComplete
                    || titleInput.value.trim() !== ''
                    || objectiveInput.value.trim() !== ''
                    || budgetInput.value.trim() !== ''
                    || descriptionInput.value.trim() !== ''
                    || publicationInput.value.trim() !== ''
                    || deadlineInput.value.trim() !== ''
                    || fitComplete;

                setReviewCompletionState('creator', creatorComplete);
                setReviewCompletionState('title', titleComplete);
                setReviewCompletionState('objective', objectiveComplete);
                setReviewCompletionState('budget', budgetComplete);
                setReviewCompletionState('fit', fitComplete);

                if (!liveReviewCard) {
                    return;
                }

                liveReviewCard.classList.toggle('review-card-active', hasAnyProgress);
                liveReviewCard.classList.toggle('review-card-complete', creatorComplete && titleComplete && objectiveComplete && budgetComplete && descriptionComplete);
            }

            if (creatorPicker) {
                [
                    'creatorpicker:change',
                    'creatorpicker:ready',
                    'creatorpicker:clear',
                    'creatorpicker:results',
                    'creatorpicker:render'
                ].forEach(function (eventName) {
                    creatorPicker.addEventListener(eventName, function () {
                        updateCreatorReview();
                        updateReviewCompletion();
                    });
                });
            }

            [titleInput, objectiveInput, budgetInput, descriptionInput, publicationInput, deadlineInput, reasonInput, noteInput, expectationInput].forEach(function (input) {
                if (!input) {
                    return;
                }

                const refresh = function () {
                    updateTextReview();
                    updateReviewCompletion();
                };

                input.addEventListener('input', refresh);
                input.addEventListener('change', refresh);
            });

            updateCreatorReview();
            updateTextReview();
            updateReviewCompletion();
        });
    </script>
<?php
$cre8PilotContext = [
    'page' => 'brand_offer_workspace',
    'mode' => 'edit_offer',
    'role' => 'marque',
    'allowedActions' => ['normal_chat', 'fill_offer_form', 'recommend_creator', 'suggest_budget', 'improve_offer_text', 'summarize_page'],
    'formTarget' => 'offer_form',
    'visibleEntityType' => 'offre',
    'visibleEntityId' => $idOffre ?? null,
];
require __DIR__ . '/../condidature/cre8pilot_widget.php';
?>
</body>
</html>
