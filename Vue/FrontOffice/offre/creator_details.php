<?php
session_start();

if (!isset($_SESSION['utilisateur'])) {
    $_SESSION['utilisateur']['id'] = 2;
    $_SESSION['utilisateur']['role'] = 'createur';
}

require_once __DIR__ . '/../../../Controleur/offreC.php';

$controller = new OffreC();
$idOffre = isset($_GET['idOffre']) && is_numeric($_GET['idOffre']) ? (int) $_GET['idOffre'] : null;
$sessionUser = $_SESSION['utilisateur'] ?? [];
$idCreateur = isset($sessionUser['id'], $sessionUser['role']) && $sessionUser['role'] === 'createur'
    ? (int) $sessionUser['id']
    : (isset($_GET['idCreateur']) && is_numeric($_GET['idCreateur']) ? (int) $_GET['idCreateur'] : null);
$offre = null;
$brand = null;
$error = null;
$response = null;
$savedOffers = $_SESSION['saved_offer_ids'] ?? [];
$notice = isset($_GET['notice']) ? trim($_GET['notice']) : null;
$noticeType = isset($_GET['noticeType']) ? trim($_GET['noticeType']) : 'success';

function translateOfferStatus($status)
{
    return match ($status) {
        'brouillon' => 'Draft',
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
        'publiee', 'active' => 'status-published',
        'cloturee', 'fermee', 'closed' => 'status-closed',
        'expiree' => 'status-expired',
        'archivee' => 'status-archived',
        default => 'status-draft',
    };
}

function responseStatusLabel($status)
{
    return match ($status) {
        'en_attente' => 'Accepted and waiting',
        'en_etude' => 'Negotiation in progress',
        'acceptee' => 'Approved',
        'refusee' => 'Declined by brand',
        'retiree' => 'Declined by creator',
        default => ucwords(str_replace('_', ' ', (string) $status)),
    };
}

function responseStatusClass($status)
{
    return match ($status) {
        'en_attente' => 'pending',
        'en_etude' => 'review',
        'acceptee' => 'accepted',
        'refusee', 'retiree' => 'declined',
        default => 'pending',
    };
}

function formatMoney($value)
{
    return 'EUR ' . number_format((float) $value, 2, '.', ',');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggleSaved'], $_POST['idOffre']) && is_numeric($_POST['idOffre'])) {
    $offerId = (int) $_POST['idOffre'];
    $savedOffers = $_SESSION['saved_offer_ids'] ?? [];

    if (in_array($offerId, $savedOffers, true)) {
        $savedOffers = array_values(array_filter($savedOffers, static fn($id) => (int) $id !== $offerId));
        $message = 'Offer removed from your saved list.';
    } else {
        $savedOffers[] = $offerId;
        $savedOffers = array_values(array_unique(array_map('intval', $savedOffers)));
        $message = 'Offer saved for later.';
    }

    $_SESSION['saved_offer_ids'] = $savedOffers;
    header('Location: creator_details.php?idOffre=' . $offerId . '&idCreateur=' . $idCreateur . '&notice=' . urlencode($message) . '&noticeType=success');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['responseType'])) {
    $responseType = trim((string) ($_POST['responseType'] ?? ''));
    $messageMotivation = trim((string) ($_POST['messageMotivation'] ?? ''));
    $budgetPropose = $_POST['budgetPropose'] ?? null;
    $delaiPropose = $_POST['delaiPropose'] ?? null;

    $ok = $controller->submitOfferResponse($idCreateur, $idOffre, $responseType, $messageMotivation, $budgetPropose, $delaiPropose);

    if ($ok) {
        $feedback = match ($responseType) {
            'accept' => 'You accepted the invitation.',
            'decline' => 'You declined the invitation.',
            'negotiate' => 'Your negotiation response was sent.',
            default => 'Your response was sent.',
        };
        if (in_array($responseType, ['accept', 'decline'], true)) {
            $noticeType = $responseType === 'decline' ? 'danger' : 'success';
            header('Location: creator_list.php?notice=' . urlencode($feedback) . '&noticeType=' . urlencode($noticeType));
            exit;
        }

        header('Location: creator_details.php?idOffre=' . $idOffre . '&idCreateur=' . $idCreateur . '&notice=' . urlencode($feedback) . '&noticeType=success');
        exit;
    }

    header('Location: creator_details.php?idOffre=' . $idOffre . '&idCreateur=' . $idCreateur . '&notice=' . urlencode('Unable to save your response right now.') . '&noticeType=danger');
    exit;
}

if ($idOffre !== null && $idCreateur !== null) {
    $offre = $controller->getPublishedOffreById($idOffre, $idCreateur);
    if ($offre) {
        $brandMap = $controller->getUsersByIds([$offre->getIdMarque()], 'marque');
        $brand = $brandMap[$offre->getIdMarque()] ?? null;
        $response = $controller->getOfferResponseByCreator($idCreateur, $idOffre);
    } else {
        $error = 'Offer not found or not available to you.';
    }
} else {
    $error = 'Invalid parameters for displaying the offer.';
}

$isSaved = $idOffre !== null ? in_array($idOffre, $_SESSION['saved_offer_ids'] ?? [], true) : false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation Details - Cre8Connect</title>
    <link rel="stylesheet" href="../css/frontoffice.css">
    <link rel="stylesheet" href="offre.css">
</head>
<body>
    <main class="container py-5">
        <div class="offre-page-shell">
            <?php if ($error): ?>
                <section class="empty-state-card">
                    <div class="empty-state-icon">!</div>
                    <h2 class="section-title">Invitation unavailable</h2>
                    <p class="section-subtitle"><?php echo htmlspecialchars($error); ?></p>
                    <div class="compact-actions justify-content-center mt-4">
                        <a class="btn btn-primary" href="creator_list.php">Back to offers</a>
                    </div>
                </section>
            <?php else: ?>
                <section class="module-hero">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                        <div>
                            <span class="module-eyebrow">Targeted invitation</span>
                            <h1 class="display-5 fw-bold mt-3 mb-2 gradient-title"><?php echo htmlspecialchars($offre->getTitre()); ?></h1>
                            <p class="lead text-muted mb-0"><?php echo htmlspecialchars($offre->getDescription()); ?></p>
                        </div>
                        <div class="compact-actions">
                            <span class="offer-status <?php echo htmlspecialchars(offerStatusClass($offre->getStatutOffre())); ?>"><?php echo htmlspecialchars(translateOfferStatus($offre->getStatutOffre())); ?></span>
                            <form method="post" action="creator_details.php?idOffre=<?php echo (int) $idOffre; ?>&idCreateur=<?php echo (int) $idCreateur; ?>">
                                <input type="hidden" name="idOffre" value="<?php echo (int) $idOffre; ?>">
                                <button type="submit" name="toggleSaved" class="saved-toggle <?php echo $isSaved ? 'saved' : ''; ?>">
                                    <?php echo $isSaved ? 'Saved for later' : 'Save for later'; ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </section>

                <?php if ($notice): ?>
                    <div class="alert alert-<?php echo $noticeType === 'danger' ? 'danger' : 'success'; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($notice); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="invitation-grid">
                    <div class="response-grid">
                        <section class="section-card">
                            <h2 class="section-title">Invitation context</h2>
                            <p class="section-subtitle">This is a targeted collaboration request from a brand that selected you directly.</p>
                            <div class="offer-detail-list mt-4">
                                <div class="offer-detail-item">
                                    <strong>Brand</strong>
                                    <span><?php echo htmlspecialchars($brand['nom'] ?? 'Unknown brand'); ?></span>
                                    <p><?php echo htmlspecialchars($brand['email'] ?? ''); ?></p>
                                </div>
                                <div class="offer-detail-item">
                                    <strong>Objective</strong>
                                    <span><?php echo htmlspecialchars($offre->getObjectif()); ?></span>
                                </div>
                                <div class="offer-detail-item">
                                    <strong>Proposed budget</strong>
                                    <span><?php echo htmlspecialchars(formatMoney($offre->getBudgetPropose())); ?></span>
                                </div>
                                <div class="offer-detail-item">
                                    <strong>Deadline</strong>
                                    <span><?php echo htmlspecialchars($offre->getDateLimite()); ?></span>
                                </div>
                            </div>
                        </section>

                        <div class="detail-columns">
                            <section class="info-card">
                                <h2 class="section-title">Why you were chosen</h2>
                                <div class="note-block mt-3">
                                    <strong>Brand context</strong>
                                    <p><?php echo htmlspecialchars($offre->getRaisonChoix() !== '' ? $offre->getRaisonChoix() : 'The brand did not add a detailed selection note yet.'); ?></p>
                                </div>
                            </section>

                            <section class="info-card">
                                <h2 class="section-title">Expected collaboration fit</h2>
                                <div class="note-block mt-3">
                                    <strong>How they imagine the partnership</strong>
                                    <p><?php echo htmlspecialchars($offre->getAttenteCollaboration() !== '' ? $offre->getAttenteCollaboration() : 'No detailed collaboration expectation was attached to this invitation.'); ?></p>
                                </div>
                            </section>
                        </div>

                        <?php if ($offre->getMessagePersonnalise() !== ''): ?>
                            <section class="section-card">
                                <h2 class="section-title">Personal note from the brand</h2>
                                <div class="note-block mt-3">
                                    <strong>Message</strong>
                                    <p><?php echo htmlspecialchars($offre->getMessagePersonnalise()); ?></p>
                                </div>
                            </section>
                        <?php endif; ?>
                    </div>

                    <aside class="response-grid">
                        <section class="info-card">
                            <h2 class="section-title">Your response</h2>
                            <?php if ($response): ?>
                                <div class="review-list mt-4">
                                    <div class="review-item">
                                        <strong>Current status</strong>
                                        <span class="response-status <?php echo htmlspecialchars(responseStatusClass($response['statutCandidature'])); ?>"><?php echo htmlspecialchars(responseStatusLabel($response['statutCandidature'])); ?></span>
                                    </div>
                                    <div class="review-item">
                                        <strong>Your budget reply</strong>
                                        <span>EUR <?php echo htmlspecialchars($response['budgetPropose']); ?></span>
                                    </div>
                                    <div class="review-item">
                                        <strong>Your proposed timeline</strong>
                                        <span><?php echo htmlspecialchars($response['delaiPropose']); ?> days</span>
                                    </div>
                                    <div class="review-item">
                                        <strong>Your message</strong>
                                        <span><?php echo htmlspecialchars($response['messageMotivation']); ?></span>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="response-callout mt-4">
                                    <strong>No response sent yet</strong>
                                    <div class="mt-2 text-muted small">Accept if you are ready, decline if it is not a fit, or negotiate if you want to adjust the brief.</div>
                                </div>
                            <?php endif; ?>
                        </section>

                        <section class="response-card">
                            <h2 class="section-title">Quick actions</h2>
                            <div class="response-actions mt-4">
                                <form method="post" action="creator_details.php?idOffre=<?php echo (int) $idOffre; ?>&idCreateur=<?php echo (int) $idCreateur; ?>">
                                    <input type="hidden" name="responseType" value="accept">
                                    <button type="submit" class="btn btn-success w-100">Accept invitation</button>
                                </form>
                                <form method="post" action="creator_details.php?idOffre=<?php echo (int) $idOffre; ?>&idCreateur=<?php echo (int) $idCreateur; ?>">
                                    <input type="hidden" name="responseType" value="decline">
                                    <button type="submit" class="btn btn-outline-danger w-100">Decline invitation</button>
                                </form>
                            </div>
                        </section>

                        <section class="response-card">
                            <h2 class="section-title">Negotiate or respond with context</h2>
                            <form method="post" action="creator_details.php?idOffre=<?php echo (int) $idOffre; ?>&idCreateur=<?php echo (int) $idCreateur; ?>" class="response-grid mt-4" data-module-validation="creator-response" novalidate>
                                <input type="hidden" name="responseType" value="negotiate">
                                <div>
                                    <label for="messageMotivation" class="form-label fw-semibold">Message</label>
                                    <textarea class="form-control" id="messageMotivation" name="messageMotivation" rows="4" placeholder="Explain what you would like to adjust or clarify."><?php echo htmlspecialchars($response['messageMotivation'] ?? ''); ?></textarea>
                                </div>
                                <div>
                                    <label for="budgetPropose" class="form-label fw-semibold">Your proposed budget</label>
                                    <div class="input-group">
                                        <span class="input-group-text">EUR</span>
                                        <input type="number" class="form-control" id="budgetPropose" name="budgetPropose" step="0.01" value="<?php echo htmlspecialchars($response['budgetPropose'] ?? $offre->getBudgetPropose()); ?>">
                                    </div>
                                </div>
                                <div>
                                    <label for="delaiPropose" class="form-label fw-semibold">Your timeline in days</label>
                                    <input type="number" class="form-control" id="delaiPropose" name="delaiPropose" step="1" value="<?php echo htmlspecialchars($response['delaiPropose'] ?? '7'); ?>">
                                </div>
                                <button type="submit" class="btn btn-primary">Send negotiation response</button>
                            </form>
                        </section>

                        <div class="compact-actions">
                            <a class="btn btn-outline-secondary w-100" href="creator_list.php">Back to offers</a>
                        </div>
                    </aside>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
    <script src="offre-validation.js"></script>
</body>
</html>
