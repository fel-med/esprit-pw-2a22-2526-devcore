<?php
session_start();

if (!isset($_SESSION['utilisateur'])) {
    $_SESSION['utilisateur']['id'] = 1;
}

require_once __DIR__ . '/../../../Controleur/offreC.php';

$controller = new OffreC();
$brandId = $_SESSION['utilisateur']['id'];
$idOffre = isset($_GET['idOffre']) && is_numeric($_GET['idOffre']) ? (int) $_GET['idOffre'] : null;
$offre = null;
$error = null;
$creator = null;
$response = null;

function translateOfferStatus($status)
{
    return match ($status) {
        'brouillon' => 'Draft',
        'pending' => 'Pending launch',
        'publiee' => 'Published',
        'cloturee' => 'Closed',
        'expiree' => 'Expired',
        'archivee' => 'Archived',
        'active' => 'Active',
        'fermee', 'closed' => 'Closed',
        default => ucwords(str_replace('_', ' ', (string) $status)),
    };
}

function offerStatusClass($status)
{
    return match ($status) {
        'brouillon' => 'status-draft',
        'pending' => 'status-pending',
        'publiee', 'active' => 'status-published',
        'cloturee', 'fermee', 'closed' => 'status-closed',
        'expiree' => 'status-expired',
        'archivee' => 'status-archived',
        default => 'status-draft',
    };
}

function responseStatusLabel(array $response)
{
    return (string) ($response['displayStatusLabel'] ?? ucwords(str_replace('_', ' ', (string) ($response['statutCandidature'] ?? ''))));
}

function responseStatusClass($status)
{
    return match ($status) {
        'brouillon' => 'draft',
        'envoyee', 'en_attente' => 'pending',
        'negociation', 'en_etude' => 'review',
        'acceptee' => 'accepted',
        'refusee', 'retiree' => 'declined',
        default => 'pending',
    };
}

function formatMoney($value)
{
    return 'EUR ' . number_format((float) $value, 2, '.', ',');
}

function isAcceptedResponseLockStatus($status)
{
    return in_array((string) $status, ['envoyee', 'en_attente', 'acceptee'], true);
}

if ($idOffre !== null) {
    $offre = $controller->getOffreById($idOffre, $brandId);
    if ($offre) {
        if (!$offre->isDraftSansCreateur() && $offre->getIdCreateurCible()) {
            $users = $controller->getUsersByIds([$offre->getIdCreateurCible()], 'createur');
            $creator = $users[$offre->getIdCreateurCible()] ?? null;
            $response = $controller->getOfferResponseByCreator($offre->getIdCreateurCible(), $offre->getIdOffre());
        }
    }

    if (!$offre) {
        $error = 'Offer not found or access denied.';
    }
} else {
    $error = 'Invalid parameters for displaying the offer.';
}

$displayStatus = $offre ? $offre->getDisplayStatusKey() : 'brouillon';
$publicationLabel = $offre && $offre->isPendingPublication() ? 'Goes live' : 'Published';
$isEditLocked = $response && isAcceptedResponseLockStatus($response['statutCandidature'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offer Details - Cre8Connect</title>
    <link rel="stylesheet" href="../css/frontoffice.css">
    <link rel="stylesheet" href="offre.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/offre.css')); ?>">
</head>
<body>
    <?php require_once dirname(__DIR__) . '/layout/header.php'; ?>
    <main class="container py-5">
        <div class="offre-page-shell">
            <?php if ($error): ?>
                <section class="empty-state-card">
                    <div class="empty-state-icon">!</div>
                    <h2 class="section-title">Offer not available</h2>
                    <p class="section-subtitle"><?php echo htmlspecialchars($error); ?></p>
                    <div class="compact-actions justify-content-center mt-4">
                        <a class="btn btn-primary" href="brand_index.php">Back to my offers</a>
                    </div>
                </section>
            <?php else: ?>
                <section class="module-hero">
                    <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start">
                        <div>
                            <span class="module-eyebrow">Targeted collaboration brief</span>
                            <h1 class="display-5 fw-bold mt-3 mb-2 gradient-title"><?php echo htmlspecialchars($offre->getTitre()); ?></h1>
                            <p class="lead text-muted mb-0"><?php echo htmlspecialchars($offre->getDescription()); ?></p>
                        </div>
                        <div class="offer-meta">
                            <span class="offer-status <?php echo htmlspecialchars(offerStatusClass($displayStatus)); ?>"><?php echo htmlspecialchars(translateOfferStatus($displayStatus)); ?></span>
                            <span class="offer-chip"><?php echo htmlspecialchars(formatMoney($offre->getBudgetPropose())); ?></span>
                            <span class="offer-chip"><?php echo htmlspecialchars($publicationLabel); ?>: <?php echo htmlspecialchars($offre->getDatePublication()); ?></span>
                        </div>
                    </div>
                </section>

                <div class="invitation-grid">
                    <div class="response-grid">
                        <section class="section-card">
                            <h2 class="section-title">Offer snapshot</h2>
                            <p class="section-subtitle">A clean view of the invitation this creator receives.</p>
                            <div class="offer-detail-list mt-4">
                                <div class="offer-detail-item">
                                    <strong>Target creator</strong>
                                    <span><?php echo htmlspecialchars($creator['nom'] ?? 'No creator selected yet'); ?></span>
                                    <p><?php echo htmlspecialchars($creator['email'] ?? ''); ?></p>
                                </div>
                                <div class="offer-detail-item">
                                    <strong>Objective</strong>
                                    <span><?php echo htmlspecialchars($offre->getObjectif()); ?></span>
                                </div>
                                <div class="offer-detail-item">
                                    <strong>Deadline</strong>
                                    <span><?php echo htmlspecialchars($offre->getDateLimite()); ?></span>
                                </div>
                                <div class="offer-detail-item">
                                    <strong>Budget</strong>
                                    <span><?php echo htmlspecialchars(formatMoney($offre->getBudgetPropose())); ?></span>
                                </div>
                            </div>
                        </section>

                        <div class="detail-columns">
                            <section class="info-card">
                                <h2 class="section-title">Why this creator</h2>
                                <div class="note-block mt-3">
                                    <strong>Selection rationale</strong>
                                    <p><?php echo htmlspecialchars($offre->getRaisonChoix() !== '' ? $offre->getRaisonChoix() : 'No specific rationale was added for this offer yet.'); ?></p>
                                </div>
                            </section>

                            <section class="info-card">
                                <h2 class="section-title">Expected collaboration fit</h2>
                                <div class="note-block mt-3">
                                    <strong>How this should work</strong>
                                    <p><?php echo htmlspecialchars($offre->getAttenteCollaboration() !== '' ? $offre->getAttenteCollaboration() : 'No collaboration expectations were written yet.'); ?></p>
                                </div>
                            </section>
                        </div>

                        <section class="section-card">
                            <h2 class="section-title">Personal note to the creator</h2>
                            <div class="note-block mt-3">
                                <strong>Message</strong>
                                <p><?php echo htmlspecialchars($offre->getMessagePersonnalise() !== '' ? $offre->getMessagePersonnalise() : 'No personal note has been added to this invitation.'); ?></p>
                            </div>
                        </section>
                    </div>

                    <aside class="response-grid">
                        <section class="info-card">
                            <h2 class="section-title">Creator response</h2>
                            <?php if ($response): ?>
                                <div class="review-list mt-4">
                                    <div class="review-item">
                                        <strong>Status</strong>
                                        <span class="response-status <?php echo htmlspecialchars(responseStatusClass($response['statutCandidature'])); ?>"><?php echo htmlspecialchars(responseStatusLabel($response)); ?></span>
                                    </div>
                                    <div class="review-item">
                                        <strong>Creator action</strong>
                                        <span><?php echo htmlspecialchars($response['responseTypeLabel'] ?? 'Offer response'); ?></span>
                                    </div>
                                    <div class="review-item">
                                        <strong>Budget reply</strong>
                                        <span>EUR <?php echo htmlspecialchars($response['budgetPropose']); ?></span>
                                    </div>
                                    <div class="review-item">
                                        <strong>Timeline reply</strong>
                                        <span><?php echo htmlspecialchars($response['delaiPropose']); ?> days</span>
                                    </div>
                                    <div class="review-item">
                                        <strong>Creator message</strong>
                                        <span><?php echo htmlspecialchars($response['messageMotivation']); ?></span>
                                    </div>
                                </div>
                                <div class="note-block mt-4">
                                    <strong>Response workspace</strong>
                                    <p>Open the candidature workspace to review the full response thread and continue negotiation if this collaboration still needs adjustments.</p>
                                    <div class="compact-actions mt-3">
                                        <a class="btn btn-outline-secondary" href="../condidature/brand_details.php?idCandidature=<?php echo (int) $response['idCandidature']; ?>">Open candidature details</a>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="response-callout mt-4">
                                    <strong>No response yet</strong>
                                    <div class="mt-2 text-muted small">The targeted creator has not answered this invitation yet.</div>
                                </div>
                            <?php endif; ?>
                        </section>

                        <section class="info-card">
                            <h2 class="section-title">Actions</h2>
                            <?php if ($isEditLocked): ?>
                                <div class="response-callout response-callout-accepted mt-4">
                                    <strong>Editing locked</strong>
                                    <div class="mt-2 text-muted small">The targeted creator already accepted this offer, so the brief can no longer be modified by the brand.</div>
                                </div>
                            <?php endif; ?>
                            <div class="compact-actions mt-4">
                                <?php if ($isEditLocked): ?>
                                    <span class="btn btn-outline-secondary disabled" aria-disabled="true">Editing locked</span>
                                <?php else: ?>
                                    <a class="btn btn-primary" href="brand_edit.php?idOffre=<?php echo (int) $offre->getIdOffre(); ?>">Edit offer</a>
                                <?php endif; ?>
                                <?php if ($response): ?>
                                    <a class="btn btn-outline-secondary" href="../condidature/brand_details.php?idCandidature=<?php echo (int) $response['idCandidature']; ?>">Open response</a>
                                <?php endif; ?>
                                <form
                                    method="post"
                                    action="brand_delete.php"
                                    data-delete-confirm
                                    data-delete-title="<?php echo htmlspecialchars($offre->getTitre(), ENT_QUOTES); ?>"
                                    data-delete-creator="<?php echo htmlspecialchars($creator['nom'] ?? 'No creator selected yet', ENT_QUOTES); ?>"
                                >
                                    <input type="hidden" name="idOffre" value="<?php echo (int) $offre->getIdOffre(); ?>">
                                    <button type="submit" class="btn btn-outline-danger">Delete</button>
                                </form>
                                <a class="btn btn-outline-secondary" href="brand_index.php">Back to offers</a>
                            </div>
                        </section>
                    </aside>
                </div>
            <?php endif; ?>
        </div>
    </main>
    <script src="offre-delete-confirm.js?v=<?php echo urlencode((string) filemtime(__DIR__ . '/offre-delete-confirm.js')); ?>"></script>
<?php
$cre8PilotContext = [
    'page' => 'brand_offer_workspace',
    'mode' => 'details',
    'role' => 'marque',
    'allowedActions' => ['normal_chat', 'summarize_page', 'analyze_page', 'suggest_budget'],
    'formTarget' => null,
    'visibleEntityType' => 'offre',
    'visibleEntityId' => $idOffre ?? null,
];
require __DIR__ . '/../condidature/cre8pilot_widget.php';
?>
</body>
</html>
