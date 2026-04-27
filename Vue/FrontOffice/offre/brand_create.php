<?php
session_start();

if (!isset($_SESSION['utilisateur'])) {
    $_SESSION['utilisateur']['id'] = 1;
}

require_once __DIR__ . '/../../../Controleur/offreC.php';

$controller = new OffreC();
$errors = [];
$successMessage = null;
$creatorPageSize = 6;
$saveMode = 'publish';
$moduleTimezone = new DateTimeZone('Africa/Tunis');
$todayDate = new DateTimeImmutable('now', $moduleTimezone);
$serverErrorFocusTarget = null;
$brandId = isset($_SESSION['utilisateur']['id']) && is_numeric($_SESSION['utilisateur']['id'])
    ? (int) $_SESSION['utilisateur']['id']
    : 0;

if ($brandId > 0) {
    $brandLookup = $controller->getUsersByIds([$brandId], 'marque');
    if (!isset($brandLookup[$brandId])) {
        $brandId = 0;
    }
}

if ($brandId <= 0) {
    $brandDirectory = $controller->getLoginDirectoryUsers(['marque']);
    $brandId = isset($brandDirectory['marque'][0]['id']) && is_numeric($brandDirectory['marque'][0]['id'])
        ? (int) $brandDirectory['marque'][0]['id']
        : 0;

    if ($brandId > 0) {
        $_SESSION['utilisateur']['id'] = $brandId;
        $_SESSION['utilisateur']['role'] = 'marque';
    }
}

$form = [
    'idCreateurCible' => '',
    'titre' => '',
    'description' => '',
    'objectif' => '',
    'budgetPropose' => '',
    'datePublication' => $todayDate->format('Y-m-d'),
    'dateLimite' => $todayDate->modify('+14 days')->format('Y-m-d'),
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

function resolveCreateOfferErrorFocusTarget(array $errors)
{
    foreach ($errors as $error) {
        $normalizedError = strtolower(trim((string) $error));

        if (
            strpos($normalizedError, 'creator') !== false
            || strpos($normalizedError, 'draft') !== false
        ) {
            return 'creatorSearch';
        }

        if (strpos($normalizedError, 'title') !== false) {
            return 'titre';
        }

        if (strpos($normalizedError, 'description') !== false) {
            return 'description';
        }

        if (strpos($normalizedError, 'objective') !== false) {
            return 'objectif';
        }

        if (strpos($normalizedError, 'budget') !== false) {
            return 'budgetPropose';
        }

        if (strpos($normalizedError, 'publication date') !== false) {
            return 'datePublication';
        }

        if (strpos($normalizedError, 'deadline') !== false) {
            return 'dateLimite';
        }

        if (strpos($normalizedError, 'why this creator') !== false) {
            return 'raisonChoix';
        }

        if (strpos($normalizedError, 'personal note') !== false) {
            return 'messagePersonnalise';
        }

        if (strpos($normalizedError, 'collaboration expectations') !== false) {
            return 'attenteCollaboration';
        }
    }

    return null;
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

    $errors = $controller->validateOffreData($form, $saveMode);

    if ($brandId <= 0) {
        array_unshift($errors, 'A valid brand account is required before this offer can be saved.');
    }
    $creatorId = isset($form['idCreateurCible']) && is_numeric($form['idCreateurCible']) ? (int) $form['idCreateurCible'] : 0;
    $selectedCreatorProfile = $controller->getCreatorPickerProfile($creatorId);

    if ($saveMode !== 'draft' && !$selectedCreatorProfile) {
        $errors[] = 'Please choose a valid creator for this targeted offer.';
    } elseif ($saveMode !== 'draft' && $creatorId > 0 && !$selectedCreatorProfile) {
        $errors[] = 'The selected creator could not be found anymore. Please choose another one.';
    }

    if (empty($errors)) {
        if ($saveMode === 'draft' && $creatorId > 0 && !$selectedCreatorProfile) {
            $creatorId = 0;
        }

        $draftWithoutCreator = $saveMode === 'draft' && $creatorId <= 0;
        $persistedCreatorId = $creatorId > 0 ? $creatorId : null;
        if ($draftWithoutCreator) {
            $persistedCreatorId = $controller->getDraftPlaceholderCreatorId();
            if (!$persistedCreatorId) {
                $errors[] = 'A creator account must exist in the platform before this draft can be stored.';
            }
        }

        if (empty($errors)) {
            $publicationFallback = new DateTimeImmutable('now', $moduleTimezone);
            $publicationDate = trim((string) $form['datePublication']) !== ''
                ? trim((string) $form['datePublication'])
                : $publicationFallback->format('Y-m-d');
            $deadlineDate = trim((string) $form['dateLimite']) !== ''
                ? trim((string) $form['dateLimite'])
                : $publicationFallback->modify('+14 days')->format('Y-m-d');
            $budgetValue = trim((string) $form['budgetPropose']) !== '' && is_numeric($form['budgetPropose'])
                ? (float) $form['budgetPropose']
                : 0.0;
            $targetStatus = $saveMode === 'draft' ? 'brouillon' : 'publiee';
            $offre = new Offre(
                null,
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

            if ($controller->createOffre($offre)) {
                $flashMessage = $saveMode === 'draft' ? 'Draft saved.' : 'Offer published.';
                header('Location: brand_index.php?message=' . urlencode($flashMessage));
                exit;
            }

            $errors[] = 'Unable to create the offer right now. Please try again.';
        }
    }

    if (!empty($errors)) {
        $serverErrorFocusTarget = resolveCreateOfferErrorFocusTarget($errors);
    }
}

$selectedCreatorId = isset($form['idCreateurCible']) && is_numeric($form['idCreateurCible']) ? (int) $form['idCreateurCible'] : 0;
if (!$selectedCreatorProfile && $selectedCreatorId > 0) {
    $selectedCreatorProfile = $controller->getCreatorPickerProfile($selectedCreatorId);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Targeted Offer - Cre8Connect</title>
    <link rel="stylesheet" href="../css/frontoffice.css">
    <link rel="stylesheet" href="offre.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/offre.css')); ?>">
</head>
<body>
    <?php require_once dirname(__DIR__) . '/header.php'; ?>
    <main class="container py-5">
        <div class="offre-page-shell">
            <section class="module-hero">
                <span class="module-eyebrow">Brand workspace</span>
                <h1 class="display-5 fw-bold mt-3 mb-2 gradient-title">Create a targeted collaboration offer</h1>
                <p class="lead text-muted">Build the invitation in clear steps: choose the creator, explain the fit, define the offer, and review the final brief before sending it.</p>
            </section>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Please review the form.</strong>
                    <ul class="mb-0 mt-2">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($successMessage): ?>
                <div class="alert alert-success" role="alert"><?php echo htmlspecialchars($successMessage); ?></div>
            <?php endif; ?>

            <div class="wizard-layout">
                <form method="post" action="brand_create.php" class="wizard-stack" data-module-validation="brand-offer" data-server-error-focus="<?php echo htmlspecialchars((string) ($serverErrorFocusTarget ?? '')); ?>" novalidate>
                    <section class="wizard-card">
                        <div class="wizard-step-head">
                            <span class="wizard-step-number">1</span>
                            <div>
                                <h3>Choose the creator</h3>
                                <p>Pick the creator who should receive this offer.</p>
                            </div>
                        </div>

                        <div
                            class="creator-picker"
                            data-creator-picker
                            data-endpoint="brand_create.php?ajax=creators"
                            data-page-size="<?php echo (int) $creatorPageSize; ?>"
                            data-has-more="<?php echo $creatorHasMore ? '1' : '0'; ?>"
                            data-initial-count="<?php echo count($availableCreators); ?>"
                            data-selected-id="<?php echo $selectedCreatorProfile ? (int) $selectedCreatorProfile['id'] : 0; ?>"
                            data-selected-name="<?php echo $selectedCreatorProfile ? htmlspecialchars((string) $selectedCreatorProfile['nom']) : ''; ?>"
                            data-selected-email="<?php echo $selectedCreatorProfile ? htmlspecialchars((string) $selectedCreatorProfile['email']) : ''; ?>"
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
                                    <span data-selected-email><?php echo $selectedCreatorProfile ? htmlspecialchars((string) $selectedCreatorProfile['email']) : ''; ?></span>
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
                                <p class="section-subtitle" data-creator-empty-copy>Creator accounts are not available yet.</p>
                            </div>

                            <div class="creator-browser-footer<?php echo count($availableCreators) > 0 ? '' : ' is-hidden'; ?>" data-creator-footer>
                                <button type="button" class="btn btn-outline-secondary" data-open-creator-modal<?php echo count($availableCreators) > 0 ? '' : ' hidden'; ?>>Browse more creators</button>
                                <span class="creator-search-status" data-creator-results>
                                    Showing <?php echo count($availableCreators); ?> creators in the current list.
                                </span>
                            </div>

                            <div class="creator-modal-overlay" data-creator-modal hidden aria-hidden="true">
                                <div class="creator-modal-card" role="dialog" aria-modal="true" aria-labelledby="creatorModalTitle">
                                    <div class="creator-modal-head">
                                        <div>
                                            <span class="module-eyebrow">Creator browser</span>
                                            <h3 id="creatorModalTitle">Browse creators</h3>
                                            <p>Scroll through the creator stack, select one profile, or load the next group when you reach the bottom.</p>
                                        </div>
                                        <button type="button" class="creator-modal-close" data-close-creator-modal aria-label="Close creator browser">Close</button>
                                    </div>
                                    <div class="creator-modal-body">
                                        <div class="creator-modal-stack" data-creator-modal-grid></div>
                                        <div class="empty-state-card is-hidden" data-creator-modal-empty>
                                            <div class="empty-state-icon">!</div>
                                            <h3 class="section-title">No creators to show</h3>
                                            <p class="section-subtitle">Try changing the search field, then open the browser again.</p>
                                        </div>
                                    </div>
                                    <div class="creator-modal-footer">
                                        <button type="button" class="btn btn-primary" data-creator-modal-load-more<?php echo $creatorHasMore ? '' : ' hidden'; ?>>Load more</button>
                                        <span class="creator-search-status" data-creator-modal-results>Showing <?php echo count($availableCreators); ?> creators.</span>
                                        <span class="creator-search-status creator-modal-end" data-creator-modal-end<?php echo $creatorHasMore ? ' hidden' : ''; ?>>There are no more creators to load.</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="wizard-card">
                        <div class="wizard-step-head">
                            <span class="wizard-step-number">2</span>
                            <div>
                                <h3>Offer details</h3>
                                <p>Define the actual collaboration brief: the goal, the value, and the timeline.</p>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-12">
                                <label for="titre" class="form-label fw-semibold">Offer title</label>
                                <input type="text" class="form-control form-control-lg" id="titre" name="titre" value="<?php echo htmlspecialchars($form['titre']); ?>" placeholder="Example: Short-form product launch package">
                            </div>
                            <div class="col-md-6">
                                <label for="objectif" class="form-label fw-semibold">Objective</label>
                                <input type="text" class="form-control" id="objectif" name="objectif" value="<?php echo htmlspecialchars($form['objectif']); ?>" placeholder="Example: Drive 10 product demo videos in 2 weeks">
                            </div>
                            <div class="col-md-6">
                                <label for="budgetPropose" class="form-label fw-semibold">Proposed budget</label>
                                <div class="input-group">
                                    <span class="input-group-text">EUR</span>
                                    <input type="number" class="form-control" id="budgetPropose" name="budgetPropose" step="0.01" value="<?php echo htmlspecialchars($form['budgetPropose']); ?>" placeholder="1500">
                                </div>
                            </div>
                            <div class="col-12">
                                <label for="description" class="form-label fw-semibold">Detailed description</label>
                                <textarea class="form-control" id="description" name="description" rows="5" placeholder="Explain the deliverables, expected tone, product context, and what success looks like."><?php echo htmlspecialchars($form['description']); ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label for="datePublication" class="form-label fw-semibold">Publication date</label>
                                <input type="date" class="form-control" id="datePublication" name="datePublication" value="<?php echo htmlspecialchars($form['datePublication']); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="dateLimite" class="form-label fw-semibold">Deadline</label>
                                <input type="date" class="form-control" id="dateLimite" name="dateLimite" value="<?php echo htmlspecialchars($form['dateLimite']); ?>">
                            </div>
                        </div>
                    </section>

                    <section class="wizard-card">
                        <div class="wizard-step-head">
                            <span class="wizard-step-number">3</span>
                            <div>
                                <h3>Why this creator?</h3>
                                <p>This whole section is optional. Use it when you want to explain the creator fit more clearly and make the invitation feel more personal.</p>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-12">
                                <label for="raisonChoix" class="form-label fw-semibold">Why was this creator selected? <span class="optional-field-mark">Optional</span></label>
                                <textarea class="form-control" id="raisonChoix" name="raisonChoix" rows="3" placeholder="Audience fit, previous work, tone, niche expertise..."><?php echo htmlspecialchars($form['raisonChoix']); ?></textarea>
                                <p class="field-helper-text">Use this only if you want to explain the audience match, tone, or previous work that made this creator a good fit.</p>
                            </div>
                            <div class="col-md-6">
                                <label for="attenteCollaboration" class="form-label fw-semibold">Expected collaboration fit <span class="optional-field-mark">Optional</span></label>
                                <textarea class="form-control" id="attenteCollaboration" name="attenteCollaboration" rows="4" placeholder="What kind of partnership, energy, deliverables, or rhythm do you expect?"><?php echo htmlspecialchars($form['attenteCollaboration']); ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label for="messagePersonnalise" class="form-label fw-semibold">Personal note <span class="optional-field-mark">Optional</span></label>
                                <textarea class="form-control" id="messagePersonnalise" name="messagePersonnalise" rows="4" placeholder="Optional warm introduction or context for this creator."><?php echo htmlspecialchars($form['messagePersonnalise']); ?></textarea>
                            </div>
                        </div>
                    </section>

                    <section class="wizard-card">
                        <div class="wizard-step-head">
                            <span class="wizard-step-number">4</span>
                            <div>
                                <h3>Review and send</h3>
                                <p>Use the live summary on the right, then either publish the invitation now or keep it as a draft for later refinement.</p>
                            </div>
                        </div>

                        <div class="note-block">
                            <strong>Final check</strong>
                            <p>Publish when the creator fit, proposed budget, and timeline are ready. If you still need to refine the brief, save it as a draft and come back without losing your progress.</p>
                        </div>

                        <div class="compact-actions mt-4">
                            <button type="submit" name="saveMode" value="publish" data-validation-intent="publish" class="btn btn-primary btn-lg">Publish targeted offer</button>
                            <button type="submit" name="saveMode" value="draft" data-validation-intent="draft" data-draft-button class="btn btn-outline-primary btn-lg">Save as draft</button>
                            <a class="btn btn-outline-secondary btn-lg" href="brand_index.php">Cancel</a>
                        </div>
                    </section>
                </form>

                <aside class="wizard-sidebar">
                    <section class="info-card live-review-card" data-live-review-card>
                        <h2 class="section-title">Live review</h2>
                        <p class="section-subtitle">A final summary of the creator, core offer details, and optional collaboration context.</p>
                        <div class="review-list mt-4">
                            <div class="review-item" data-review-item="creator">
                                <strong>Selected creator</strong>
                                <span id="reviewCreator">Choose a creator from step 1.</span>
                            </div>
                            <div class="review-item" data-review-item="details">
                                <strong>Offer details</strong>
                                <span class="review-detail-line" id="reviewTitle">Title: Your title will appear here.</span>
                                <span class="review-detail-line" id="reviewObjective">Objective: Define the collaboration goal.</span>
                                <span class="review-detail-line" id="reviewBudget">Budget and timing: Add a proposed budget and schedule.</span>
                            </div>
                            <div class="review-item" data-review-item="fit">
                                <strong>Collaboration fit</strong>
                                <span id="reviewFit">Optional collaboration notes will appear here.</span>
                            </div>
                        </div>
                    </section>

                    <section class="info-card">
                        <h2 class="section-title">Quick tips</h2>
                        <div class="mini-note-list">
                            <div>
                                <strong>Choose a clear match</strong>
                                <span>Pick a creator whose audience and style fit the offer.</span>
                            </div>
                            <div>
                                <strong>Keep the brief specific</strong>
                                <span>Clear goals, budget, and dates make it easier for creators to answer.</span>
                            </div>
                            <div>
                                <strong>Add context only when useful</strong>
                                <span>A short personal note can help, but it does not need to be long.</span>
                            </div>
                        </div>
                    </section>
                </aside>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
    <script src="offre-validation.js?v=<?php echo urlencode((string) filemtime(__DIR__ . '/offre-validation.js')); ?>"></script>
    <script src="offre-creator-picker.js?v=<?php echo urlencode((string) filemtime(__DIR__ . '/offre-creator-picker.js')); ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const creatorPicker = document.querySelector('[data-creator-picker]');
            const reviewCreator = document.getElementById('reviewCreator');
            const reviewTitle = document.getElementById('reviewTitle');
            const reviewObjective = document.getElementById('reviewObjective');
            const reviewBudget = document.getElementById('reviewBudget');
            const reviewFit = document.getElementById('reviewFit');
            const liveReviewCard = document.querySelector('[data-live-review-card]');
            const draftButton = document.querySelector('[data-draft-button]');
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
                fit: document.querySelector('[data-review-item="fit"]'),
                details: document.querySelector('[data-review-item="details"]')
            };

            function getSelectedCreator() {
                if (!creatorPicker || !creatorPicker.dataset.selectedId || creatorPicker.dataset.selectedId === '0') {
                    return null;
                }

                return {
                    name: creatorPicker.dataset.selectedName || '',
                    email: creatorPicker.dataset.selectedEmail || '',
                    status: creatorPicker.dataset.selectedStatus || '',
                    targeted: creatorPicker.dataset.selectedTargeted || ''
                };
            }

            function updateCreatorReview() {
                const selected = getSelectedCreator();
                if (!selected) {
                    reviewCreator.textContent = 'Choose a creator from step 1.';
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
                reviewTitle.textContent = 'Title: ' + (titleInput.value.trim() || 'Your title will appear here.');
                reviewObjective.textContent = 'Objective: ' + (objectiveInput.value.trim() || 'Define the collaboration goal.');

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

                reviewBudget.textContent = 'Budget and timing: ' + (parts.length ? parts.join(' | ') : 'Add a proposed budget and schedule.');

                const fitBits = [
                    reasonInput.value.trim(),
                    expectationInput.value.trim(),
                    noteInput.value.trim()
                ].filter(Boolean);
                reviewFit.textContent = fitBits.length ? fitBits.join(' | ') : 'Optional collaboration notes will appear here.';
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

                return publicationDate >= today && deadlineDate >= today && deadlineDate > publicationDate;
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
                const detailsComplete = titleComplete && objectiveComplete && budgetComplete;
                const hasAnyProgress = creatorComplete
                    || titleInput.value.trim() !== ''
                    || objectiveInput.value.trim() !== ''
                    || budgetInput.value.trim() !== ''
                    || descriptionInput.value.trim() !== ''
                    || publicationInput.value.trim() !== ''
                    || deadlineInput.value.trim() !== ''
                    || fitComplete;

                setReviewCompletionState('creator', creatorComplete);
                setReviewCompletionState('fit', fitComplete);
                setReviewCompletionState('details', detailsComplete);

                if (!liveReviewCard) {
                    return;
                }

                liveReviewCard.classList.toggle('review-card-active', hasAnyProgress);
                liveReviewCard.classList.toggle('review-card-complete', creatorComplete && detailsComplete);
            }

            function hasDraftSignal() {
                if (getSelectedCreator()) {
                    return true;
                }

                return [
                    titleInput,
                    objectiveInput,
                    budgetInput,
                    descriptionInput,
                    reasonInput,
                    noteInput,
                    expectationInput
                ].some(function (input) {
                    return input && input.value.trim() !== '';
                });
            }

            function updateDraftButtonState() {
                if (!draftButton) {
                    return;
                }

                const enabled = hasDraftSignal();
                draftButton.disabled = !enabled;
                draftButton.setAttribute('aria-disabled', enabled ? 'false' : 'true');
                draftButton.title = enabled
                    ? 'Save the current work as a draft.'
                    : 'Select a creator or fill at least one field to enable draft saving.';
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
                        updateDraftButtonState();
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
                    updateDraftButtonState();
                };

                input.addEventListener('input', refresh);
                input.addEventListener('change', refresh);
            });

            updateCreatorReview();
            updateTextReview();
            updateReviewCompletion();
            updateDraftButtonState();
        });
    </script>
</body>
</html>
