<?php
session_start();

if (!isset($_SESSION['utilisateur'])) {
    $_SESSION['utilisateur']['id'] = 1;
}

require_once __DIR__ . '/../../../Controleur/offreC.php';

$brandId = $_SESSION['utilisateur']['id'];
$controller = new OffreC();
$offres = [];
$error = null;

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

function responseStatusLabel($status)
{
    return match ($status) {
        'en_attente' => 'Creator accepted',
        'en_etude' => 'Negotiation requested',
        'acceptee' => 'Approved',
        'refusee' => 'Declined by brand',
        'retiree' => 'Declined by creator',
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

function translateOfferFlashMessage($message)
{
    $translations = [
        'Offre creee avec succes.' => 'Offer created successfully.',
        'Offre créée avec succès.' => 'Offer created successfully.',
        'Offre mise a jour avec succes.' => 'Offer updated successfully.',
        'Offre mise à jour avec succès.' => 'Offer updated successfully.',
        'Offre supprimee avec succes.' => 'Offer deleted successfully.',
        'Offre supprimée avec succès.' => 'Offer deleted successfully.',
        'Impossible de supprimer cette offre.' => 'Unable to delete this offer.',
    ];

    return $translations[$message] ?? $message;
}

function formatMoney($value)
{
    return 'EUR ' . number_format((float) $value, 2, '.', ',');
}

function excerptText($text, $length = 165)
{
    $text = trim((string) $text);
    if (strlen($text) <= $length) {
        return $text;
    }

    return rtrim(substr($text, 0, max(0, $length - 3))) . '...';
}

function isAcceptedTargetResponseStatus($status)
{
    return in_array((string) $status, ['en_attente', 'acceptee'], true);
}

function isDeclinedTargetResponseStatus($status)
{
    return in_array((string) $status, ['retiree', 'refusee'], true);
}

function getTargetResponseSortRank($response)
{
    if (!$response) {
        return 2;
    }

    $status = (string) ($response['statutCandidature'] ?? '');
    if (isAcceptedTargetResponseStatus($status)) {
        return 0;
    }

    if ($status === 'en_etude') {
        return 1;
    }

    if (isDeclinedTargetResponseStatus($status)) {
        return 3;
    }

    return 2;
}

function buildBrandOfferCards(array $offres, array $creatorMap, array $responseGroups)
{
    $cards = [];

    foreach ($offres as $offre) {
        $responses = $responseGroups[$offre->getIdOffre()] ?? [];
        $targetedResponse = null;
        $hasRealTargetCreator = !$offre->isDraftSansCreateur() && $offre->getIdCreateurCible();

        if ($hasRealTargetCreator) {
            foreach ($responses as $response) {
                if ((int) $response['idCreateur'] === (int) $offre->getIdCreateurCible()) {
                    $targetedResponse = $response;
                    break;
                }
            }
        }

        $cards[] = [
            'offre' => $offre,
            'creator' => $hasRealTargetCreator ? ($creatorMap[$offre->getIdCreateurCible()] ?? null) : null,
            'responses' => $responses,
            'targetedResponse' => $targetedResponse,
            'isAccepted' => $targetedResponse && isAcceptedTargetResponseStatus($targetedResponse['statutCandidature'] ?? ''),
            'isDeclined' => $targetedResponse && isDeclinedTargetResponseStatus($targetedResponse['statutCandidature'] ?? ''),
            'acceptedAt' => $targetedResponse['dateCandidature'] ?? null,
        ];
    }

    usort($cards, static function ($left, $right) {
        $rankComparison = getTargetResponseSortRank($left['targetedResponse']) <=> getTargetResponseSortRank($right['targetedResponse']);
        if ($rankComparison !== 0) {
            return $rankComparison;
        }

        if ($left['isAccepted'] && $right['isAccepted']) {
            $acceptedDateComparison = strcmp((string) ($right['acceptedAt'] ?? ''), (string) ($left['acceptedAt'] ?? ''));
            if ($acceptedDateComparison !== 0) {
                return $acceptedDateComparison;
            }
        }

        $budgetComparison = (float) $right['offre']->getBudgetPropose() <=> (float) $left['offre']->getBudgetPropose();
        if ($budgetComparison !== 0) {
            return $budgetComparison;
        }

        $publicationComparison = strcmp((string) $right['offre']->getDatePublication(), (string) $left['offre']->getDatePublication());
        if ($publicationComparison !== 0) {
            return $publicationComparison;
        }

        return (int) $right['offre']->getIdOffre() <=> (int) $left['offre']->getIdOffre();
    });

    return $cards;
}

if ($brandId !== null) {
    $offres = $controller->getOffresByMarque($brandId);
} else {
    $error = 'Brand ID is missing.';
}

$message = isset($_GET['message']) ? translateOfferFlashMessage($_GET['message']) : null;
$offerIds = array_map(static fn($offre) => $offre->getIdOffre(), $offres);
$creatorIds = array_map(static fn($offre) => $offre->getIdCreateurCible(), $offres);
$creatorMap = $controller->getUsersByIds($creatorIds, 'createur');
$responseGroups = $controller->getCandidaturesGroupedByOfferIds($offerIds);
$offerCards = buildBrandOfferCards($offres, $creatorMap, $responseGroups);
$acceptedOfferCards = array_values(array_filter($offerCards, static fn($card) => $card['isAccepted']));

if (isset($_GET['notificationPing']) && $_GET['notificationPing'] === '1') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'acceptedCount' => count($acceptedOfferCards),
        'acceptedOffers' => array_map(static function ($card) {
            return [
                'idOffre' => (int) $card['offre']->getIdOffre(),
                'titre' => (string) $card['offre']->getTitre(),
                'creatorName' => (string) ($card['creator']['nom'] ?? 'Target creator'),
                'acceptedAt' => (string) ($card['acceptedAt'] ?? ''),
            ];
        }, $acceptedOfferCards),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$acceptedCount = count($acceptedOfferCards);
$acceptedOfferIds = array_map(static fn($card) => (int) $card['offre']->getIdOffre(), $acceptedOfferCards);

$liveCount = 0;
$pendingCount = 0;
$responseCount = 0;
$averageBudget = 0;
$closestDeadline = null;
$declinedOfferCount = count(array_filter($offerCards, static fn($card) => $card['isDeclined']));
$averageBudgetCards = [];

if (!empty($offerCards)) {

    foreach ($offerCards as $card) {
        $offre = $card['offre'];
        if ($offre->isPendingPublication()) {
            $pendingCount++;
        } elseif ($offre->isLivePublication()) {
            $liveCount++;

            if (!$card['isDeclined']) {
                $averageBudgetCards[] = $card;
            }
        }

        $responseCount += count($card['responses']);

        $deadline = $offre->getDateLimite();
        if ($offre->isLivePublication() && $deadline && ($closestDeadline === null || $deadline < $closestDeadline)) {
            $closestDeadline = $deadline;
        }
    }
}

if (!empty($averageBudgetCards)) {
    $averageBudget = array_sum(array_map(static fn($card) => (float) $card['offre']->getBudgetPropose(), $averageBudgetCards)) / count($averageBudgetCards);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Targeted Offers - Cre8Connect</title>
    <link rel="stylesheet" href="../css/frontoffice.css">
    <link rel="stylesheet" href="offre.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/offre.css')); ?>">
</head>
<body>
    <main class="container py-5">
        <div class="offre-page-shell">
            <section class="module-hero">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div>
                        <span class="module-eyebrow">Brand workspace</span>
                        <h1 class="display-5 fw-bold mt-3 mb-2 gradient-title">My targeted collaboration offers</h1>
                        <p class="lead text-muted">Track every invitation you sent to creators, monitor response signals, and keep the next collaboration moving.</p>
                    </div>
                    <div class="compact-actions">
                        <a class="btn btn-primary btn-lg" href="brand_create.php">Create a new offer</a>
                    </div>
                </div>
            </section>

            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <section
                id="acceptedOfferBanner"
                class="notification-banner notification-banner-success<?php echo $acceptedCount > 0 ? '' : ' is-hidden'; ?>"
                data-accepted-ids="<?php echo htmlspecialchars(implode(',', $acceptedOfferIds)); ?>"
            >
                <div>
                    <span class="notification-pill">Acceptance monitor</span>
                    <h2 id="acceptedBannerTitle">
                        <?php if ($acceptedCount > 0): ?>
                            <?php echo $acceptedCount; ?> offer<?php echo $acceptedCount === 1 ? '' : 's'; ?> accepted by creators
                        <?php else: ?>
                            No creator acceptance yet
                        <?php endif; ?>
                    </h2>
                    <p id="acceptedBannerText">
                        <?php if ($acceptedCount > 0): ?>
                            Accepted invitations are pinned to the top so your team can react quickly.
                        <?php else: ?>
                            When a creator accepts one of your targeted offers, it will appear here and move to the top of the list.
                        <?php endif; ?>
                    </p>
                </div>
                <div class="notification-banner-list" id="acceptedBannerList">
                    <?php foreach (array_slice($acceptedOfferCards, 0, 3) as $card): ?>
                        <span class="notification-chip">
                            <?php echo htmlspecialchars($card['offre']->getTitre()); ?> - <?php echo htmlspecialchars($card['creator']['nom'] ?? 'No creator selected yet'); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </section>

            <div id="liveNotificationStack" class="live-notification-stack" aria-live="polite"></div>

            <section class="stats-grid brand-stats-grid">
                <article class="stat-card">
                    <span class="stat-label">Total offers</span>
                    <span class="stat-value"><?php echo count($offerCards); ?></span>
                    <span class="stat-note">All invitations in your pipeline</span>
                </article>
                <article class="stat-card">
                    <span class="stat-label">Live invitations</span>
                    <span class="stat-value"><?php echo $liveCount; ?></span>
                    <span class="stat-note">Currently published to creators</span>
                </article>
                <article class="stat-card">
                    <span class="stat-label">Pending launch</span>
                    <span class="stat-value"><?php echo $pendingCount; ?></span>
                    <span class="stat-note">Scheduled but not visible yet</span>
                </article>
                <article class="stat-card">
                    <span class="stat-label">Creator accepted</span>
                    <span class="stat-value"><?php echo $acceptedCount; ?></span>
                    <span class="stat-note"><?php echo $responseCount; ?> total response<?php echo $responseCount === 1 ? '' : 's'; ?> received</span>
                </article>
                <article class="stat-card">
                    <span class="stat-label">Average budget</span>
                    <span class="stat-value"><?php echo count($averageBudgetCards) ? htmlspecialchars(formatMoney($averageBudget)) : 'EUR 0.00'; ?></span>
                    <span class="stat-note">
                        <?php
                        $budgetNote = $liveCount > 0
                            ? 'Based on live invitations'
                            : 'No live invitations yet';
                        if ($closestDeadline) {
                            $budgetNote .= '<br />Closest deadline: ' . htmlspecialchars($closestDeadline);
                        }
                        if ($declinedOfferCount > 0) {
                            $budgetNote .= ' | Declined offers excluded';
                        }
                        if ($pendingCount > 0) {
                            $budgetNote .= ' | Pending launches excluded';
                        }
                        echo $budgetNote;
                        ?>
                    </span>
                </article>
            </section>

            <?php if (!empty($offerCards)): ?>
                <section class="offer-grid" id="brandOfferGrid">
                    <?php foreach ($offerCards as $card): ?>
                        <?php
                        $offre = $card['offre'];
                        $creator = $card['creator'];
                        $responses = $card['responses'];
                        $targetedResponse = $card['targetedResponse'];
                        $isAccepted = $card['isAccepted'];
                        $isDeclined = $card['isDeclined'];
                        $displayStatus = $offre->getDisplayStatusKey();
                        $publicationLabel = $offre->isPendingPublication() ? 'Goes live' : 'Published';
                        ?>
                        <article
                            class="offer-card<?php echo $isAccepted ? ' is-accepted' : ($isDeclined ? ' is-declined' : ''); ?>"
                            data-offer-id="<?php echo (int) $offre->getIdOffre(); ?>"
                            data-offer-title="<?php echo htmlspecialchars($offre->getTitre(), ENT_QUOTES); ?>"
                            data-creator-name="<?php echo htmlspecialchars($creator['nom'] ?? 'No creator selected yet', ENT_QUOTES); ?>"
                        >
                            <div class="offer-card-head">
                                <div>
                                    <div class="offer-flag-row">
                                        <span class="offer-status <?php echo htmlspecialchars(offerStatusClass($displayStatus)); ?>">
                                            <?php echo htmlspecialchars(translateOfferStatus($displayStatus)); ?>
                                        </span>
                                        <?php if ($isAccepted): ?>
                                            <span class="priority-badge priority-badge-success js-accepted-flag">Accepted by creator</span>
                                        <?php elseif ($isDeclined): ?>
                                            <span class="priority-badge priority-badge-danger">Declined by creator</span>
                                        <?php endif; ?>
                                    </div>
                                    <h2 class="offer-card-title"><?php echo htmlspecialchars($offre->getTitre()); ?></h2>
                                    <p class="offer-summary mt-2"><?php echo htmlspecialchars(excerptText($offre->getDescription(), 165)); ?></p>
                                </div>
                            </div>

                            <div class="offer-meta">
                                <span class="offer-chip"><?php echo htmlspecialchars(formatMoney($offre->getBudgetPropose())); ?></span>
                                <span class="offer-chip"><?php echo htmlspecialchars($publicationLabel); ?>: <?php echo htmlspecialchars($offre->getDatePublication()); ?></span>
                                <span class="offer-chip">Deadline: <?php echo htmlspecialchars($offre->getDateLimite()); ?></span>
                                <span class="offer-chip"><?php echo count($responses); ?> response<?php echo count($responses) === 1 ? '' : 's'; ?></span>
                            </div>

                            <div class="offer-detail-list">
                                <div class="offer-detail-item">
                                    <strong>Target creator</strong>
                                    <span><?php echo htmlspecialchars($creator['nom'] ?? 'No creator selected yet'); ?></span>
                                    <p><?php echo htmlspecialchars($creator['email'] ?? ''); ?></p>
                                </div>
                                <div class="offer-detail-item">
                                    <strong>Objective</strong>
                                    <span><?php echo htmlspecialchars($offre->getObjectif()); ?></span>
                                </div>
                            </div>

                            <?php if ($targetedResponse): ?>
                                <div class="response-callout<?php echo $isAccepted ? ' response-callout-accepted' : ($isDeclined ? ' response-callout-declined' : ''); ?>">
                                    <strong>Latest creator signal</strong>
                                    <div class="mt-2 d-flex flex-wrap gap-2 align-items-center">
                                        <span class="response-status <?php echo htmlspecialchars(responseStatusClass($targetedResponse['statutCandidature'])); ?>">
                                            <?php echo htmlspecialchars(responseStatusLabel($targetedResponse['statutCandidature'])); ?>
                                        </span>
                                        <span class="text-muted small">
                                            <?php if ($isDeclined): ?>
                                                This offer stays in the pipeline, but moves to the bottom.
                                            <?php else: ?>
                                                Budget reply: EUR <?php echo htmlspecialchars($targetedResponse['budgetPropose']); ?>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </div>
                            <?php elseif ($offre->isPendingPublication()): ?>
                                <div class="response-callout">
                                    <strong>Scheduled launch</strong>
                                    <div class="mt-2 text-muted small">This offer will become visible to the creator on <?php echo htmlspecialchars($offre->getDatePublication()); ?>.</div>
                                </div>
                            <?php else: ?>
                                <div class="response-callout">
                                    <strong>Waiting for creator feedback</strong>
                                    <div class="mt-2 text-muted small">No response yet from the targeted creator.</div>
                                </div>
                            <?php endif; ?>

                            <div class="compact-actions">
                                <a class="btn btn-primary" href="brand_details.php?idOffre=<?php echo (int) $offre->getIdOffre(); ?>">Open details</a>
                                <a class="btn btn-outline-secondary" href="brand_edit.php?idOffre=<?php echo (int) $offre->getIdOffre(); ?>">Edit offer</a>
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
                            </div>
                        </article>
                    <?php endforeach; ?>
                </section>
            <?php else: ?>
                <section class="empty-state-card">
                    <div class="empty-state-icon">+</div>
                    <h2 class="section-title">No targeted offers yet</h2>
                    <p class="section-subtitle">Start your collaboration pipeline by selecting a creator and sending a focused invitation instead of a generic brief.</p>
                    <div class="compact-actions justify-content-center mt-4">
                        <a class="btn btn-primary btn-lg" href="brand_create.php">Create your first targeted offer</a>
                    </div>
                </section>
            <?php endif; ?>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
    <script src="offre-delete-confirm.js?v=<?php echo urlencode((string) filemtime(__DIR__ . '/offre-delete-confirm.js')); ?>"></script>
    <script>
        (() => {
            const grid = document.getElementById('brandOfferGrid');
            const banner = document.getElementById('acceptedOfferBanner');
            const title = document.getElementById('acceptedBannerTitle');
            const text = document.getElementById('acceptedBannerText');
            const list = document.getElementById('acceptedBannerList');
            const stack = document.getElementById('liveNotificationStack');

            if (!banner || !stack || !window.fetch) {
                return;
            }

            let knownAcceptedIds = new Set((banner.dataset.acceptedIds || '').split(',').filter(Boolean));

            function escapeHtml(value) {
                return String(value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function updateBanner(data) {
                const offers = Array.isArray(data.acceptedOffers) ? data.acceptedOffers : [];
                banner.dataset.acceptedIds = offers.map((offer) => offer.idOffre).join(',');

                if (!offers.length) {
                    banner.classList.add('is-hidden');
                    list.innerHTML = '';
                    return;
                }

                banner.classList.remove('is-hidden');
                title.textContent = `${offers.length} offer${offers.length === 1 ? '' : 's'} accepted by creators`;
                text.textContent = 'Accepted invitations are pinned to the top so your team can react quickly.';
                list.innerHTML = offers.slice(0, 3).map((offer) => (
                    `<span class="notification-chip">${escapeHtml(offer.titre)} - ${escapeHtml(offer.creatorName)}</span>`
                )).join('');
            }

            function elevateAcceptedCard(offer) {
                if (!grid) {
                    return;
                }

                const card = grid.querySelector(`[data-offer-id="${offer.idOffre}"]`);
                if (!card) {
                    return;
                }

                card.classList.add('is-accepted');

                let flag = card.querySelector('.js-accepted-flag');
                if (!flag) {
                    const row = card.querySelector('.offer-flag-row');
                    if (row) {
                        flag = document.createElement('span');
                        flag.className = 'priority-badge priority-badge-success js-accepted-flag';
                        flag.textContent = 'Accepted by creator';
                        row.appendChild(flag);
                    }
                }

                const responseCallout = card.querySelector('.response-callout');
                if (responseCallout) {
                    responseCallout.classList.add('response-callout-accepted');
                    responseCallout.innerHTML = `
                        <strong>Latest creator signal</strong>
                        <div class="mt-2 d-flex flex-wrap gap-2 align-items-center">
                            <span class="response-status accepted">Creator accepted</span>
                            <span class="text-muted small">Open details to review the full creator response.</span>
                        </div>
                    `;
                }

                grid.prepend(card);
            }

            function showLiveToast(newOffers) {
                if (!newOffers.length) {
                    return;
                }

                const toast = document.createElement('div');
                toast.className = 'live-toast live-toast-success';
                toast.innerHTML = `
                    <strong>${newOffers.length} creator acceptance${newOffers.length === 1 ? '' : 's'} received</strong>
                    <span>${escapeHtml(newOffers.map((offer) => offer.titre).join(', '))}</span>
                `;
                stack.prepend(toast);

                window.setTimeout(() => {
                    toast.classList.add('is-leaving');
                    window.setTimeout(() => toast.remove(), 350);
                }, 5000);
            }

            async function pollAcceptedOffers() {
                try {
                    const url = new URL(window.location.href);
                    url.searchParams.set('notificationPing', '1');
                    const response = await fetch(url.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (!response.ok) {
                        return;
                    }

                    const data = await response.json();
                    const offers = Array.isArray(data.acceptedOffers) ? data.acceptedOffers : [];
                    const freshOffers = offers.filter((offer) => !knownAcceptedIds.has(String(offer.idOffre)));

                    if (freshOffers.length) {
                        freshOffers.forEach(elevateAcceptedCard);
                        showLiveToast(freshOffers);
                    }

                    updateBanner(data);
                    knownAcceptedIds = new Set(offers.map((offer) => String(offer.idOffre)));
                } catch (error) {
                    console.error('Unable to refresh accepted offer notifications.', error);
                }
            }

            window.setInterval(pollAcceptedOffers, 20000);
        })();
    </script>
</body>
</html>
