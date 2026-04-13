<?php
session_start();

if (!isset($_SESSION['utilisateur'])) {
    $_SESSION['utilisateur']['id'] = 2;
    $_SESSION['utilisateur']['role'] = 'createur';
}

require_once __DIR__ . '/../../../Controleur/offreC.php';

$controller = new OffreC();
$creatorId = $_SESSION['utilisateur']['id'];
$savedOffers = $_SESSION['saved_offer_ids'] ?? [];
$offres = [];
$error = null;
$notice = isset($_GET['notice']) ? trim((string) $_GET['notice']) : null;
$noticeType = isset($_GET['noticeType']) ? trim((string) $_GET['noticeType']) : 'success';

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
        'en_attente' => 'Accepted',
        'en_etude' => 'Negotiating',
        'acceptee' => 'Approved',
        'refusee' => 'Declined by brand',
        'retiree' => 'Declined',
        default => ucwords(str_replace('_', ' ', (string) $status)),
    };
}

function responseStatusClass($status)
{
    return match ($status) {
        'en_attente', 'acceptee' => 'accepted',
        'en_etude' => 'review',
        'refusee', 'retiree' => 'declined',
        default => 'pending',
    };
}

function formatMoney($value)
{
    return 'EUR ' . number_format((float) $value, 2, '.', ',');
}

function isAcceptedResponseStatus($status)
{
    return in_array((string) $status, ['en_attente', 'acceptee'], true);
}

function isDeclinedResponseStatus($status)
{
    return in_array((string) $status, ['retiree', 'refusee'], true);
}

function getCreatorResponseForOffer(array $responses, $creatorId)
{
    foreach ($responses as $response) {
        if ((int) $response['idCreateur'] === (int) $creatorId) {
            return $response;
        }
    }

    return null;
}

function getCreatorOfferSortRank($response)
{
    if (!$response) {
        return 2;
    }

    $status = (string) ($response['statutCandidature'] ?? '');
    if (isAcceptedResponseStatus($status)) {
        return 0;
    }

    if ($status === 'en_etude') {
        return 1;
    }

    if (isDeclinedResponseStatus($status)) {
        return 3;
    }

    return 2;
}

function excerptText($text, $length = 155)
{
    $text = trim((string) $text);
    if (strlen($text) <= $length) {
        return $text;
    }

    return rtrim(substr($text, 0, max(0, $length - 3))) . '...';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggleSaved'], $_POST['idOffre']) && is_numeric($_POST['idOffre'])) {
    $offerId = (int) $_POST['idOffre'];
    $savedOffers = $_SESSION['saved_offer_ids'] ?? [];

    if (in_array($offerId, $savedOffers, true)) {
        $savedOffers = array_values(array_filter($savedOffers, static fn($id) => (int) $id !== $offerId));
    } else {
        $savedOffers[] = $offerId;
        $savedOffers = array_values(array_unique(array_map('intval', $savedOffers)));
    }

    $_SESSION['saved_offer_ids'] = $savedOffers;
    $redirect = 'creator_list.php';
    if (!empty($_SERVER['QUERY_STRING'])) {
        $redirect .= '?' . $_SERVER['QUERY_STRING'];
    }
    header('Location: ' . $redirect);
    exit;
}

$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : null;
$budgetFrom = isset($_GET['budgetFrom']) && is_numeric($_GET['budgetFrom']) ? (float) $_GET['budgetFrom'] : null;
$budgetTo = isset($_GET['budgetTo']) && is_numeric($_GET['budgetTo']) ? (float) $_GET['budgetTo'] : null;
$dateLimite = isset($_GET['dateLimite']) && $_GET['dateLimite'] !== '' ? $_GET['dateLimite'] : null;
$savedOnly = isset($_GET['savedOnly']) && $_GET['savedOnly'] === '1';

try {
    $offres = $controller->searchOffers($creatorId, $keyword, $budgetFrom, $budgetTo, $dateLimite);
    if ($savedOnly) {
        $offres = array_values(array_filter($offres, static fn($offre) => in_array($offre->getIdOffre(), $_SESSION['saved_offer_ids'] ?? [], true)));
    }

    $offerIds = array_map(static fn($offre) => $offre->getIdOffre(), $offres);
    $responseGroups = $controller->getCandidaturesGroupedByOfferIds($offerIds);

    usort($offres, static function ($left, $right) use ($responseGroups, $creatorId) {
        $leftResponse = getCreatorResponseForOffer($responseGroups[$left->getIdOffre()] ?? [], $creatorId);
        $rightResponse = getCreatorResponseForOffer($responseGroups[$right->getIdOffre()] ?? [], $creatorId);
        $rankComparison = getCreatorOfferSortRank($leftResponse) <=> getCreatorOfferSortRank($rightResponse);
        if ($rankComparison !== 0) {
            return $rankComparison;
        }

        $budgetComparison = (float) $right->getBudgetPropose() <=> (float) $left->getBudgetPropose();
        if ($budgetComparison !== 0) {
            return $budgetComparison;
        }

        $publicationComparison = strcmp((string) $right->getDatePublication(), (string) $left->getDatePublication());
        if ($publicationComparison !== 0) {
            return $publicationComparison;
        }

        return (int) $right->getIdOffre() <=> (int) $left->getIdOffre();
    });
} catch (Exception $exception) {
    $error = 'An error occurred while loading your invitations.';
}

$offerIds = array_map(static fn($offre) => $offre->getIdOffre(), $offres);
$brandIds = array_map(static fn($offre) => $offre->getIdMarque(), $offres);
$brandMap = $controller->getUsersByIds($brandIds, 'marque');
$responseGroups = $controller->getCandidaturesGroupedByOfferIds($offerIds);
$savedOffers = $_SESSION['saved_offer_ids'] ?? [];

$closingSoon = 0;
$respondedOffers = 0;
$averageBudget = 0;
$topBudget = null;
$topBudgetOffer = null;

if (!empty($offres)) {
    $averageBudget = array_sum(array_map(static fn($offre) => (float) $offre->getBudgetPropose(), $offres)) / count($offres);
    $today = new DateTime('today');

    foreach ($offres as $offre) {
        $creatorResponse = getCreatorResponseForOffer($responseGroups[$offre->getIdOffre()] ?? [], $creatorId);
        $deadline = DateTime::createFromFormat('Y-m-d', (string) $offre->getDateLimite());
        if ($deadline) {
            $days = (int) $today->diff($deadline)->format('%r%a');
            if ($days >= 0 && $days <= 7) {
                $closingSoon++;
            }
        }

        if ($creatorResponse) {
            $respondedOffers++;
        }

        if (!$creatorResponse && ($topBudget === null || (float) $offre->getBudgetPropose() > $topBudget)) {
            $topBudget = (float) $offre->getBudgetPropose();
            $topBudgetOffer = $offre;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offers for You - Cre8Connect</title>
    <link rel="stylesheet" href="../css/frontoffice.css">
    <link rel="stylesheet" href="offre.css">
</head>
<body>
    <main class="container py-5">
        <div class="offre-page-shell">
            <section class="module-hero">
                <span class="module-eyebrow">Creator inbox</span>
                <h1 class="display-5 fw-bold mt-3 mb-2 gradient-title">Offers for you</h1>
                <p class="lead text-muted">Browse targeted invitations from brands, save the ones you want to revisit, and respond when the collaboration feels right.</p>
            </section>

            <section class="stats-grid">
                <article class="stat-card">
                    <span class="stat-label">Visible invitations</span>
                    <span class="stat-value"><?php echo count($offres); ?></span>
                    <span class="stat-note">Offers currently matching your filters</span>
                </article>
                <article class="stat-card">
                    <span class="stat-label">Saved for later</span>
                    <span class="stat-value"><?php echo count($savedOffers); ?></span>
                    <span class="stat-note">Session-based shortlist</span>
                </article>
                <article class="stat-card">
                    <span class="stat-label">Closing soon</span>
                    <span class="stat-value"><?php echo $closingSoon; ?></span>
                    <span class="stat-note">Deadlines within the next 7 days</span>
                </article>
                <article class="stat-card">
                    <span class="stat-label">Average budget</span>
                    <span class="stat-value"><?php echo count($offres) ? htmlspecialchars(formatMoney($averageBudget)) : 'EUR 0.00'; ?></span>
                    <span class="stat-note">
                        <?php if ($topBudgetOffer): ?>
                            Top budget: <?php echo htmlspecialchars(formatMoney($topBudgetOffer->getBudgetPropose())); ?>
                        <?php else: ?>
                            No premium invitations yet
                        <?php endif; ?>
                    </span>
                </article>
            </section>

            <section class="filter-card">
                <h2 class="section-title">Filter your invitation inbox</h2>
                <p class="section-subtitle">Narrow the list by topic, budget, deadline, or your saved shortlist.</p>
                <form method="get" action="creator_list.php" class="filter-stack mt-4" data-module-validation="creator-filters" novalidate>
                    <div class="filter-grid">
                        <div>
                            <label for="keyword" class="form-label fw-semibold">Keyword</label>
                            <input type="text" class="form-control" id="keyword" name="keyword" value="<?php echo htmlspecialchars($keyword ?? ''); ?>" placeholder="Brand message, offer title, objective...">
                        </div>
                        <div>
                            <label for="budgetFrom" class="form-label fw-semibold">Budget from</label>
                            <input type="number" class="form-control" id="budgetFrom" name="budgetFrom" value="<?php echo htmlspecialchars($budgetFrom ?? ''); ?>" step="0.01">
                        </div>
                        <div>
                            <label for="budgetTo" class="form-label fw-semibold">Budget to</label>
                            <input type="number" class="form-control" id="budgetTo" name="budgetTo" value="<?php echo htmlspecialchars($budgetTo ?? ''); ?>" step="0.01">
                        </div>
                        <div>
                            <label for="dateLimite" class="form-label fw-semibold">Deadline from</label>
                            <input type="date" class="form-control" id="dateLimite" name="dateLimite" value="<?php echo htmlspecialchars($dateLimite ?? ''); ?>">
                        </div>
                        <div>
                            <label for="savedOnly" class="form-label fw-semibold">Saved only</label>
                            <select class="form-select" id="savedOnly" name="savedOnly">
                                <option value="0"<?php echo !$savedOnly ? ' selected' : ''; ?>>Show all</option>
                                <option value="1"<?php echo $savedOnly ? ' selected' : ''; ?>>Saved first</option>
                            </select>
                        </div>
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary">Apply filters</button>
                        <a class="btn btn-outline-secondary" href="creator_list.php">Reset</a>
                    </div>
                </form>
            </section>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($notice): ?>
                <div class="alert alert-<?php echo $noticeType === 'danger' ? 'danger' : 'success'; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($notice); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($offres)): ?>
                <section class="offer-grid">
                    <?php foreach ($offres as $offre): ?>
                        <?php
                        $brand = $brandMap[$offre->getIdMarque()] ?? null;
                        $saved = in_array($offre->getIdOffre(), $savedOffers, true);
                        $creatorResponse = getCreatorResponseForOffer($responseGroups[$offre->getIdOffre()] ?? [], $creatorId);
                        $isAccepted = $creatorResponse && isAcceptedResponseStatus($creatorResponse['statutCandidature'] ?? '');
                        $isDeclined = $creatorResponse && isDeclinedResponseStatus($creatorResponse['statutCandidature'] ?? '');
                        $isTopBudget = !$creatorResponse && $topBudget !== null && (float) $offre->getBudgetPropose() === (float) $topBudget;
                        ?>
                        <article class="offer-card<?php echo $isAccepted ? ' is-accepted' : ($isDeclined ? ' is-declined' : ($isTopBudget ? ' is-top-budget' : '')); ?>">
                            <div class="offer-card-head">
                                <div>
                                    <div class="offer-flag-row mb-2">
                                        <span class="offer-status <?php echo htmlspecialchars(offerStatusClass($offre->getStatutOffre())); ?>"><?php echo htmlspecialchars(translateOfferStatus($offre->getStatutOffre())); ?></span>
                                        <?php if ($isAccepted): ?>
                                            <span class="priority-badge priority-badge-success">Accepted</span>
                                        <?php elseif ($isDeclined): ?>
                                            <span class="priority-badge priority-badge-danger">Declined</span>
                                        <?php elseif ($isTopBudget): ?>
                                            <span class="priority-badge priority-badge-gold">Top budget match</span>
                                        <?php endif; ?>
                                        <?php if ($saved): ?>
                                            <span class="saved-badge">Saved</span>
                                        <?php endif; ?>
                                    </div>
                                    <h2 class="offer-card-title"><?php echo htmlspecialchars($offre->getTitre()); ?></h2>
                                    <p class="offer-summary mt-2"><?php echo htmlspecialchars(excerptText($offre->getDescription(), 155)); ?></p>
                                </div>
                                <form method="post" action="creator_list.php<?php echo !empty($_SERVER['QUERY_STRING']) ? '?' . htmlspecialchars($_SERVER['QUERY_STRING']) : ''; ?>">
                                    <input type="hidden" name="idOffre" value="<?php echo (int) $offre->getIdOffre(); ?>">
                                    <button type="submit" name="toggleSaved" class="saved-toggle <?php echo $saved ? 'saved' : ''; ?>">
                                        <?php echo $saved ? 'Saved' : 'Save for later'; ?>
                                    </button>
                                </form>
                            </div>

                            <div class="offer-meta">
                                <span class="offer-chip"><?php echo htmlspecialchars(formatMoney($offre->getBudgetPropose())); ?></span>
                                <span class="offer-chip">Deadline: <?php echo htmlspecialchars($offre->getDateLimite()); ?></span>
                                <?php if ($brand): ?>
                                    <span class="offer-chip">Brand: <?php echo htmlspecialchars($brand['nom']); ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="offer-detail-list">
                                <div class="offer-detail-item">
                                    <strong>Brand</strong>
                                    <span><?php echo htmlspecialchars($brand['nom'] ?? 'Unknown brand'); ?></span>
                                    <p><?php echo htmlspecialchars($brand['email'] ?? ''); ?></p>
                                </div>
                                <div class="offer-detail-item">
                                    <strong>Objective</strong>
                                    <span><?php echo htmlspecialchars($offre->getObjectif()); ?></span>
                                </div>
                            </div>

                            <?php if ($offre->getRaisonChoix() !== ''): ?>
                                <div class="note-block">
                                    <strong>Why you were picked</strong>
                                    <p><?php echo htmlspecialchars(excerptText($offre->getRaisonChoix(), 170)); ?></p>
                                </div>
                            <?php endif; ?>

                            <?php if ($creatorResponse): ?>
                                <div class="response-callout<?php echo $isAccepted ? ' response-callout-accepted' : ($isDeclined ? ' response-callout-declined' : ''); ?>">
                                    <strong>Your current response</strong>
                                    <div class="mt-2 d-flex flex-wrap gap-2 align-items-center">
                                        <span class="response-status <?php echo htmlspecialchars(responseStatusClass($creatorResponse['statutCandidature'])); ?>">
                                            <?php echo htmlspecialchars(responseStatusLabel($creatorResponse['statutCandidature'])); ?>
                                        </span>
                                        <span class="text-muted small">
                                            <?php if ($isDeclined): ?>
                                                This invitation stays in your pipeline history.
                                            <?php else: ?>
                                                Budget reply: EUR <?php echo htmlspecialchars($creatorResponse['budgetPropose']); ?>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="compact-actions">
                                <a class="btn btn-primary" href="creator_details.php?idOffre=<?php echo (int) $offre->getIdOffre(); ?>&idCreateur=<?php echo (int) $creatorId; ?>">Open invitation</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </section>
            <?php else: ?>
                <section class="empty-state-card">
                    <div class="empty-state-icon"><?php echo $savedOnly ? '*' : '!'; ?></div>
                    <h2 class="section-title"><?php echo $savedOnly ? 'No saved invitations match this view' : 'No targeted offers found'; ?></h2>
                    <p class="section-subtitle"><?php echo $savedOnly ? 'Try removing the saved-only filter or save more offers for later.' : 'Adjust the filters and check back soon for new brand invitations.'; ?></p>
                    <div class="compact-actions justify-content-center mt-4">
                        <a class="btn btn-outline-secondary" href="creator_list.php">Reset filters</a>
                    </div>
                </section>
            <?php endif; ?>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
    <script src="offre-validation.js"></script>
</body>
</html>
