<?php
session_start();

if (!isset($_SESSION['utilisateur'])) {
    $_SESSION['utilisateur']['id'] = 1;
}

require_once __DIR__ . '/../../../Controleur/offreC.php';

$controller = new OffreC();
$brandId = $_SESSION['utilisateur']['id'];
$errors = [];
$successMessage = null;
$creatorPageSize = 6;
$form = [
    'idCreateurCible' => '',
    'titre' => '',
    'description' => '',
    'objectif' => '',
    'budgetPropose' => '',
    'datePublication' => date('Y-m-d'),
    'dateLimite' => date('Y-m-d', strtotime('+14 days')),
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

    $errors = $controller->validateOffreData($form);
    $creatorId = isset($form['idCreateurCible']) && is_numeric($form['idCreateurCible']) ? (int) $form['idCreateurCible'] : 0;
    $selectedCreatorProfile = $controller->getCreatorPickerProfile($creatorId);

    if (!$selectedCreatorProfile) {
        $errors[] = 'Please choose a valid creator for this targeted offer.';
    }

    if (empty($errors)) {
        $offre = new Offre(
            null,
            $brandId,
            $creatorId,
            trim($form['titre']),
            trim($form['description']),
            trim($form['objectif']),
            (float) $form['budgetPropose'],
            trim($form['datePublication']),
            trim($form['dateLimite']),
            'publiee',
            trim($form['raisonChoix']),
            trim($form['messagePersonnalise']),
            trim($form['attenteCollaboration'])
        );

        if ($controller->createOffre($offre)) {
            header('Location: brand_index.php?message=' . urlencode('Offer created successfully.'));
            exit;
        }

        $errors[] = 'Unable to create the offer right now. Please try again.';
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
    <link rel="stylesheet" href="offre.css">
</head>
<body>
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
                <form method="post" action="brand_create.php" class="wizard-stack" data-module-validation="brand-offer" novalidate>
                    <section class="wizard-card">
                        <div class="wizard-step-head">
                            <span class="wizard-step-number">1</span>
                            <div>
                                <h3>Choose the creator</h3>
                                <p>Select the person this invitation is meant for. The list is searchable and keeps the current targeted workflow.</p>
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
                                    <span class="creator-search-status" data-creator-status>Start with a focused shortlist. Search by name, email, or ID when you need someone specific.</span>
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
                                <p class="section-subtitle" data-creator-empty-copy>You can create the offer as soon as creator accounts are available in the platform.</p>
                            </div>

                            <div class="creator-browser-footer<?php echo count($availableCreators) > 0 ? '' : ' is-hidden'; ?>" data-creator-footer>
                                <button type="button" class="btn btn-outline-secondary" data-creator-load-more<?php echo $creatorHasMore ? '' : ' hidden'; ?>>Load more creators</button>
                                <span class="creator-search-status" data-creator-results>
                                    Showing <?php echo count($availableCreators); ?> creators. Search to narrow the list before loading more.
                                </span>
                            </div>
                        </div>
                    </section>

                    <section class="wizard-card">
                        <div class="wizard-step-head">
                            <span class="wizard-step-number">2</span>
                            <div>
                                <h3>Why this creator?</h3>
                                <p>Give your team and the creator more context about the collaboration fit. These fields are optional but make the invitation feel intentional.</p>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-12">
                                <label for="raisonChoix" class="form-label fw-semibold">Why was this creator selected?</label>
                                <textarea class="form-control" id="raisonChoix" name="raisonChoix" rows="3" placeholder="Audience fit, previous work, tone, niche expertise..."><?php echo htmlspecialchars($form['raisonChoix']); ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label for="attenteCollaboration" class="form-label fw-semibold">Expected collaboration fit</label>
                                <textarea class="form-control" id="attenteCollaboration" name="attenteCollaboration" rows="4" placeholder="What kind of partnership, energy, deliverables, or rhythm do you expect?"><?php echo htmlspecialchars($form['attenteCollaboration']); ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label for="messagePersonnalise" class="form-label fw-semibold">Personal note</label>
                                <textarea class="form-control" id="messagePersonnalise" name="messagePersonnalise" rows="4" placeholder="Optional warm introduction or context for this creator."><?php echo htmlspecialchars($form['messagePersonnalise']); ?></textarea>
                            </div>
                        </div>
                    </section>

                    <section class="wizard-card">
                        <div class="wizard-step-head">
                            <span class="wizard-step-number">3</span>
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
                            <span class="wizard-step-number">4</span>
                            <div>
                                <h3>Review and send</h3>
                                <p>Use the live summary on the right to review the invitation before publishing it to the targeted creator.</p>
                            </div>
                        </div>

                        <div class="note-block">
                            <strong>Final check</strong>
                            <p>Make sure the creator fit, proposed budget, and deadline all match the collaboration you want to start. When you submit, the offer will be published directly for the selected creator.</p>
                        </div>

                        <div class="compact-actions mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">Publish targeted offer</button>
                            <a class="btn btn-outline-secondary btn-lg" href="brand_index.php">Cancel</a>
                        </div>
                    </section>
                </form>

                <aside class="wizard-sidebar">
                    <section class="info-card">
                        <h2 class="section-title">Live review</h2>
                        <p class="section-subtitle">A final summary of what the creator will receive.</p>
                        <div class="review-list mt-4">
                            <div class="review-item">
                                <strong>Selected creator</strong>
                                <span id="reviewCreator">Choose a creator from step 1.</span>
                            </div>
                            <div class="review-item">
                                <strong>Offer title</strong>
                                <span id="reviewTitle"><?php echo $form['titre'] !== '' ? htmlspecialchars($form['titre']) : 'Your title will appear here.'; ?></span>
                            </div>
                            <div class="review-item">
                                <strong>Objective</strong>
                                <span id="reviewObjective"><?php echo $form['objectif'] !== '' ? htmlspecialchars($form['objectif']) : 'Define the collaboration goal.'; ?></span>
                            </div>
                            <div class="review-item">
                                <strong>Budget and timing</strong>
                                <span id="reviewBudget">Add a proposed budget and schedule.</span>
                            </div>
                            <div class="review-item">
                                <strong>Collaboration fit</strong>
                                <span id="reviewFit">Explain why this creator is a good match.</span>
                            </div>
                        </div>
                    </section>

                    <section class="info-card">
                        <h2 class="section-title">Helpful framing</h2>
                        <div class="mini-note-list">
                            <div>
                                <strong>Target the right creator</strong>
                                <span>Use the creator card to anchor the invitation in a real person, not a raw identifier.</span>
                            </div>
                            <div>
                                <strong>Keep the ask concrete</strong>
                                <span>Specific objectives and clear budgets make it easier for creators to reply quickly.</span>
                            </div>
                            <div>
                                <strong>Use the personal note well</strong>
                                <span>A short, thoughtful note can increase the sense of a genuine collaboration invitation.</span>
                            </div>
                        </div>
                    </section>
                </aside>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
    <script src="offre-validation.js"></script>
    <script src="offre-creator-picker.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const creatorPicker = document.querySelector('[data-creator-picker]');
            const reviewCreator = document.getElementById('reviewCreator');
            const reviewTitle = document.getElementById('reviewTitle');
            const reviewObjective = document.getElementById('reviewObjective');
            const reviewBudget = document.getElementById('reviewBudget');
            const reviewFit = document.getElementById('reviewFit');
            const titleInput = document.getElementById('titre');
            const objectiveInput = document.getElementById('objectif');
            const budgetInput = document.getElementById('budgetPropose');
            const publicationInput = document.getElementById('datePublication');
            const deadlineInput = document.getElementById('dateLimite');
            const reasonInput = document.getElementById('raisonChoix');
            const expectationInput = document.getElementById('attenteCollaboration');

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

                reviewBudget.textContent = parts.length ? parts.join(' | ') : 'Add a proposed budget and schedule.';

                const fitBits = [reasonInput.value.trim(), expectationInput.value.trim()].filter(Boolean);
                reviewFit.textContent = fitBits.length ? fitBits.join(' ') : 'Explain why this creator is a good match.';
            }

            if (creatorPicker) {
                creatorPicker.addEventListener('creatorpicker:change', updateCreatorReview);
                creatorPicker.addEventListener('creatorpicker:ready', updateCreatorReview);
                creatorPicker.addEventListener('creatorpicker:clear', updateCreatorReview);
                creatorPicker.addEventListener('creatorpicker:results', updateCreatorReview);
                creatorPicker.addEventListener('creatorpicker:render', updateCreatorReview);
            }

            [titleInput, objectiveInput, budgetInput, publicationInput, deadlineInput, reasonInput, expectationInput].forEach(function (input) {
                if (!input) {
                    return;
                }

                input.addEventListener('input', updateTextReview);
                input.addEventListener('change', updateTextReview);
            });

            updateCreatorReview();
            updateTextReview();
        });
    </script>
</body>
</html>
