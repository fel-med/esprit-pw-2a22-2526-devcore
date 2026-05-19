<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../layout/early-theme.php';
require_once __DIR__ . '/../../../Controleur/session_helper.php';
require_once __DIR__ . '/../../../Controleur/offreC.php';
require_once __DIR__ . '/../../../Controleur/condidatureC.php';

$offreController = new OffreC();
$candidatureController = new CondidatureC();
$sessionUser = $_SESSION['utilisateur'] ?? ($_SESSION['user'] ?? []);

if (!isset($sessionUser['id']) || !isBackOfficeRole(cc_current_user_role())) {
    header('Location: ../../FrontOffice/utilisateur/login.php');
    exit;
}

$message = '';
$searchKeyword = trim($_GET['keyword'] ?? '');
$searchStatut = trim($_GET['statut'] ?? '');
$searchBudgetFrom = trim($_GET['budgetFrom'] ?? '');
$searchBudgetTo = trim($_GET['budgetTo'] ?? '');
$searchDateLimite = trim($_GET['dateLimite'] ?? '');
$searchDateLimiteTo = trim($_GET['dateLimiteTo'] ?? '');
$searchSort = trim($_GET['sort'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$brandIdFilter = null;
if (isset($_GET['brandId']) && $_GET['brandId'] !== '') {
    $brandIdFilter = (int) $_GET['brandId'];
} elseif (isset($_GET['idMarque']) && $_GET['idMarque'] !== '') {
    $brandIdFilter = (int) $_GET['idMarque'];
}
if ($brandIdFilter !== null && $brandIdFilter <= 0) {
    $brandIdFilter = null;
}

$filterValues = [
    $searchKeyword,
    $searchStatut,
    $searchBudgetFrom,
    $searchBudgetTo,
    $searchDateLimite,
    $searchDateLimiteTo,
    $searchSort,
];
$activeFilterCount = count(array_filter($filterValues, static fn($value) => $value !== '' && $value !== null && $value !== 'newest'));
if ($brandIdFilter !== null) {
    $activeFilterCount++;
}
$hasActiveFilters = $activeFilterCount > 0;

function translateOfferStatus($status)
{
    return match ($status) {
        'brouillon' => 'Draft',
        'pending' => 'Pending launch',
        'publiee' => 'Live now',
        'cloturee' => 'Closed',
        'expiree' => 'Expired',
        'archivee' => 'Archived',
        'active' => 'Active',
        'fermee', 'closed' => 'Closed',
        default => ucwords(str_replace('_', ' ', (string) $status)),
    };
}

function offerStatusI18nKey($status)
{
    $key = strtolower((string) $status);
    $key = str_replace(['-', ' '], '_', $key);
    return match ($key) {
        'brouillon' => 'offers.status.draft',
        'pending' => 'offers.status.pending',
        'publiee', 'active' => 'offers.status.live',
        'cloturee', 'fermee', 'closed' => 'offers.status.closed',
        'expiree' => 'offers.status.expired',
        'archivee' => 'offers.status.archived',
        default => '',
    };
}

function responseStatusLabel(array $response)
{
    return (string) ($response['displayStatusLabel'] ?? ucwords(str_replace('_', ' ', (string) ($response['statutCandidature'] ?? ''))));
}

function formatMoney($value)
{
    return 'EUR ' . number_format((float) $value, 2, '.', ',');
}

function cleanHiddenMetadata($text)
{
    return trim((string) preg_replace(
        '/\s*<!--cre8connect-(?:condidature-form-meta|condidature-meta|offre-meta):.*?-->\s*/s',
        ' ',
        (string) $text
    ));
}

function excerptText($text, $length = 80)
{
    $text = cleanHiddenMetadata($text);
    if (strlen($text) <= $length) {
        return $text;
    }

    return rtrim(substr($text, 0, max(0, $length - 3))) . '...';
}

function responseTypeLabel(array $response)
{
    return (string) ($response['responseTypeLabel'] ?? ucwords(str_replace('_', ' ', (string) ($response['typeReponse'] ?? 'Response'))));
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

    return date('Y-m-d', $timestamp);
}

function buildInspectUrl($offerId, array $filters)
{
    $query = $filters;
    $query['idOffre'] = (int) $offerId;

    return 'index.php?' . http_build_query($query);
}

function adminPageUrl(array $base, int $page): string
{
    return 'index.php?' . http_build_query($base + ['page' => max(1, $page)]);
}

function renderOfferResponsesPanelHtml($offer, array $responses)
{
    ob_start();
    ?>
    <section class="offer-responses-panel">
        <div class="offer-responses-summary">
            <span class="quick-overview-tag">Offer responses</span>
            <strong><?php echo htmlspecialchars($offer ? $offer->getTitre() : 'Selected offer'); ?></strong>
            <span><?php echo count($responses); ?> related candidature<?php echo count($responses) === 1 ? '' : 's'; ?></span>
        </div>

        <?php if (empty($responses)): ?>
            <div class="detail-empty-state">
                <span class="detail-empty-icon">i</span>
                <h4 data-i18n="offers.dialog.noResponses">No creator responses yet for this offer.</h4>
                <p>This panel only shows creator responses attached to this offer invitation.</p>
            </div>
        <?php else: ?>
            <div class="offer-responses-list">
                <?php foreach ($responses as $response): ?>
                    <?php
                    $messagePreview = excerptText((string) ($response['messageMotivation'] ?? ''), 120);
                    $delay = isset($response['delaiPropose']) && $response['delaiPropose'] !== '' ? (int) $response['delaiPropose'] . ' days' : 'No delay shared';
                    ?>
                    <article class="offer-response-row">
                        <div class="offer-response-main">
                            <strong><?php echo htmlspecialchars($response['createurNom'] ?: ('Creator #' . $response['idCreateur'])); ?></strong>
                            <span><?php echo htmlspecialchars($response['createurEmail'] ?? ''); ?></span>
                            <p><?php echo htmlspecialchars($messagePreview !== '' ? $messagePreview : 'No response message was provided.'); ?></p>
                        </div>
                        <div class="offer-response-meta">
                            <span class="status-pill"><?php echo htmlspecialchars(responseTypeLabel($response)); ?></span>
                            <span class="status-pill"><?php echo htmlspecialchars(responseStatusLabel($response)); ?></span>
                            <span><?php echo htmlspecialchars(formatDateLabel($response['dateCandidature'] ?? null)); ?></span>
                            <span><?php echo htmlspecialchars(formatMoney($response['budgetPropose'] ?? 0)); ?></span>
                            <span><?php echo htmlspecialchars($delay); ?></span>
                        </div>
                        <a class="inspect-link offer-response-review" href="../condidature/details.php?idCandidature=<?php echo (int) ($response['idCandidature'] ?? 0); ?>">Review</a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
    <?php

    return trim((string) ob_get_clean());
}

function renderOfferInsightsHtml($selectedOffer, $selectedBrand, $selectedCreator, array $selectedResponses, array $selectedBreakdown, $selectedOfferOutsideFilters)
{
    ob_start();
    $displayStatus = $selectedOffer ? $selectedOffer->getDisplayStatusKey() : 'brouillon';
    $publicationLabel = $selectedOffer && $selectedOffer->isPendingPublication() ? 'Goes live' : 'Published';
    ?>
    <?php if ($selectedOffer): ?>
        <?php if ($selectedOfferOutsideFilters): ?>
            <div class="admin-inline-note">
                The selected offer is open in the insights panel, but it is outside the current filter view.
            </div>
        <?php endif; ?>
        <div class="quick-overview-card">
            <div class="quick-overview-top">
                <span class="quick-overview-tag">Selected offer</span>
                <span class="badge-status <?php echo htmlspecialchars($displayStatus); ?>"><?php echo htmlspecialchars(translateOfferStatus($displayStatus)); ?></span>
            </div>
            <h3><?php echo htmlspecialchars($selectedOffer->getTitre()); ?></h3>
            <p class="quick-overview-description"><?php echo htmlspecialchars(excerptText($selectedOffer->getDescription(), 150)); ?></p>
            <div class="quick-overview-meta">
                <span><?php echo htmlspecialchars(formatMoney($selectedOffer->getBudgetPropose())); ?></span>
                <span><?php echo htmlspecialchars($publicationLabel); ?>: <?php echo htmlspecialchars($selectedOffer->getDatePublication()); ?></span>
                <span>Deadline: <?php echo htmlspecialchars($selectedOffer->getDateLimite()); ?></span>
            </div>
        </div>

        <div class="detail-section">
            <div class="quick-stat-list">
                <div class="quick-stat-item">
                    <span class="quick-stat-label">Brand</span>
                    <span class="quick-stat-value"><?php echo htmlspecialchars($selectedBrand['nom'] ?? 'Unknown brand'); ?></span>
                </div>
                <div class="quick-stat-item">
                    <span class="quick-stat-label">Target creator</span>
                    <span class="quick-stat-value"><?php echo htmlspecialchars($selectedCreator['nom'] ?? 'No creator selected yet'); ?></span>
                </div>
                <div class="quick-stat-item">
                    <span class="quick-stat-label">Response volume</span>
                    <span class="quick-stat-value"><?php echo count($selectedResponses); ?> total responses</span>
                </div>
                <div class="quick-stat-item">
                    <span class="quick-stat-label">Breakdown</span>
                    <span class="quick-stat-value">
                        Drafts <?php echo $selectedBreakdown['brouillon'] ?? 0; ?>,
                        Accepted <?php echo $selectedBreakdown['envoyee']; ?>,
                        Negotiating <?php echo $selectedBreakdown['negociation'] + $selectedBreakdown['en_etude']; ?>,
                        Declined <?php echo ($selectedBreakdown['retiree'] ?? 0) + ($selectedBreakdown['refusee'] ?? 0); ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="detail-section">
            <div class="detail-grid">
                <div class="detail-block">
                    <h4>Why this creator</h4>
                    <p><?php echo htmlspecialchars($selectedOffer->getRaisonChoix() !== '' ? excerptText($selectedOffer->getRaisonChoix(), 120) : 'No specific rationale was attached to this offer.'); ?></p>
                </div>
                <div class="detail-block">
                    <h4>Expected fit</h4>
                    <p><?php echo htmlspecialchars($selectedOffer->getAttenteCollaboration() !== '' ? excerptText($selectedOffer->getAttenteCollaboration(), 120) : 'No collaboration expectations were added.'); ?></p>
                </div>
                <div class="detail-block">
                    <h4>Personal note</h4>
                    <p><?php echo htmlspecialchars($selectedOffer->getMessagePersonnalise() !== '' ? excerptText($selectedOffer->getMessagePersonnalise(), 120) : 'No personal note was added.'); ?></p>
                </div>
                <div class="detail-block">
                    <h4>Objective</h4>
                    <p><?php echo htmlspecialchars(excerptText($selectedOffer->getObjectif(), 120)); ?></p>
                </div>
            </div>
        </div>

        <div class="detail-section">
            <h4 class="quick-stat-label">Recent creator responses</h4>
            <?php if (!empty($selectedResponses)): ?>
                <div class="quick-candidate-list">
                    <?php foreach ($selectedResponses as $response): ?>
                        <div class="quick-candidate-item">
                            <div>
                                <strong><?php echo htmlspecialchars($response['createurNom'] ?: ('Creator #' . $response['idCreateur'])); ?></strong>
                                <span><?php echo htmlspecialchars($response['createurEmail'] ?? ''); ?></span>
                                <span><?php echo htmlspecialchars(excerptText((string) ($response['messageMotivation'] ?? ''), 72)); ?></span>
                            </div>
                            <div>
                                <span class="status-pill"><?php echo htmlspecialchars(responseStatusLabel($response)); ?></span>
                                <span class="status-pill"><?php echo htmlspecialchars(formatMoney($response['budgetPropose'])); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="detail-empty-state">
                    <span class="detail-empty-icon">i</span>
                    <h4>No creator response yet</h4>
                    <p>This targeted offer has not received any acceptance, decline, or negotiation signal yet.</p>
                </div>
            <?php endif; ?>
        </div>

        <p class="admin-note">Tip: use the filters above to isolate closing offers, high budgets, or creator-specific invitation flows without leaving this module.</p>
    <?php else: ?>
        <div class="detail-empty-state">
            <span class="detail-empty-icon">i</span>
            <h4>No offer selected</h4>
            <p>Choose an offer from the list to inspect its target creator, collaboration brief, and response activity.</p>
        </div>
    <?php endif; ?>
    <?php

    return trim((string) ob_get_clean());
}

function renderOfferInspectCardHtml($selectedOffer, $selectedBrand, $selectedCreator, array $selectedResponses, array $selectedBreakdown, $selectedOfferOutsideFilters)
{
    ob_start();
    $latestResponse = !empty($selectedResponses) ? $selectedResponses[0] : null;
    $displayStatus = $selectedOffer ? $selectedOffer->getDisplayStatusKey() : 'brouillon';
    $publicationLabel = $selectedOffer && $selectedOffer->isPendingPublication() ? 'Goes live' : 'Published';
    ?>
    <?php if ($selectedOffer): ?>
        <?php if ($selectedOfferOutsideFilters): ?>
            <div class="admin-inline-note">
                The selected offer is open in the preview card, but it is outside the current filter view.
            </div>
        <?php endif; ?>
        <article class="inspect-card">
            <header class="inspect-card-header">
                <div class="inspect-card-header-copy">
                    <span class="inspect-card-kicker">Offer preview</span>
                    <p class="inspect-card-intro">A focused snapshot of the selected collaboration pipeline.</p>
                </div>
                <span class="badge-status <?php echo htmlspecialchars($displayStatus); ?>"><?php echo htmlspecialchars(translateOfferStatus($displayStatus)); ?></span>
            </header>

            <section class="inspect-card-hero">
                <p class="inspect-card-description"><?php echo htmlspecialchars($selectedOffer->getDescription() !== '' ? $selectedOffer->getDescription() : 'No description was provided for this offer.'); ?></p>
            </section>

            <div class="inspect-card-meta">
                <span><?php echo htmlspecialchars(formatMoney($selectedOffer->getBudgetPropose())); ?></span>
                <span><?php echo htmlspecialchars($publicationLabel); ?>: <?php echo htmlspecialchars($selectedOffer->getDatePublication()); ?></span>
                <span>Deadline: <?php echo htmlspecialchars($selectedOffer->getDateLimite()); ?></span>
                <span>Responses: <?php echo count($selectedResponses); ?></span>
            </div>

            <div class="inspect-card-grid">
                <section class="inspect-card-block">
                    <h4>Objective</h4>
                    <p><?php echo htmlspecialchars($selectedOffer->getObjectif() !== '' ? $selectedOffer->getObjectif() : 'No objective was added.'); ?></p>
                </section>
                <section class="inspect-card-block">
                    <h4>Why this creator</h4>
                    <p><?php echo htmlspecialchars($selectedOffer->getRaisonChoix() !== '' ? $selectedOffer->getRaisonChoix() : 'No specific rationale was attached to this offer.'); ?></p>
                </section>
                <section class="inspect-card-block">
                    <h4>Expected fit</h4>
                    <p><?php echo htmlspecialchars($selectedOffer->getAttenteCollaboration() !== '' ? $selectedOffer->getAttenteCollaboration() : 'No collaboration expectations were added.'); ?></p>
                </section>
                <section class="inspect-card-block">
                    <h4>Personal note</h4>
                    <p><?php echo htmlspecialchars($selectedOffer->getMessagePersonnalise() !== '' ? $selectedOffer->getMessagePersonnalise() : 'No personal note was added.'); ?></p>
                </section>
            </div>

            <section class="inspect-card-block inspect-card-response">
                <h4>Latest creator signal</h4>
                <?php if ($latestResponse): ?>
                    <?php $latestMessage = cleanHiddenMetadata((string) ($latestResponse['messageMotivation'] ?? '')); ?>
                    <div class="inspect-card-response-top">
                        <span class="status-pill"><?php echo htmlspecialchars(responseStatusLabel($latestResponse)); ?></span>
                        <span class="status-pill"><?php echo htmlspecialchars(formatMoney($latestResponse['budgetPropose'])); ?></span>
                    </div>
                    <p><?php echo htmlspecialchars($latestMessage !== '' ? $latestMessage : 'No response message was provided.'); ?></p>
                <?php else: ?>
                    <p>No creator response has been submitted for this offer yet.</p>
                <?php endif; ?>
            </section>
        </article>
    <?php else: ?>
        <div class="detail-empty-state">
            <span class="detail-empty-icon">i</span>
            <h4>No offer selected</h4>
            <p>Choose an offer from the list to inspect its target creator, collaboration brief, and response activity.</p>
        </div>
    <?php endif; ?>
    <?php

    return trim((string) ob_get_clean());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deleteOffre'], $_POST['idOffreToDelete']) && is_numeric($_POST['idOffreToDelete'])) {
    $deleteId = (int) $_POST['idOffreToDelete'];
    $offerToDelete = $offreController->getOffreByIdAdmin($deleteId);
    if ($offerToDelete) {
        $offreController->deleteOffre($deleteId, $offerToDelete->getIdMarque());
        $query = $_GET;
        $query['deleted'] = 1;
        unset($query['idOffre']);
        header('Location: index.php?' . http_build_query($query));
        exit;
    }

    $message = 'Offer not found or impossible to delete.';
}

$offres = $offreController->searchOffresAdmin(
    $searchKeyword ?: null,
    $searchStatut ?: null,
    $brandIdFilter,
    null,
    $searchBudgetFrom !== '' ? $searchBudgetFrom : null,
    $searchBudgetTo !== '' ? $searchBudgetTo : null,
    $searchDateLimite ?: null,
    $searchDateLimiteTo ?: null,
    $searchSort ?: 'newest',
    $perPage + 1,
    $offset
);
$hasNextPage = count($offres) > $perPage;
if ($hasNextPage) {
    array_pop($offres);
}

$persistedFilters = [
    'keyword' => $searchKeyword,
    'statut' => $searchStatut,
    'budgetFrom' => $searchBudgetFrom,
    'budgetTo' => $searchBudgetTo,
    'dateLimite' => $searchDateLimite,
    'dateLimiteTo' => $searchDateLimiteTo,
    'sort' => $searchSort,
];
if ($brandIdFilter !== null) {
    $persistedFilters['brandId'] = $brandIdFilter;
}

$offerIds = array_map(static fn($offre) => $offre->getIdOffre(), $offres);
$brandIds = array_map(static fn($offre) => $offre->getIdMarque(), $offres);
$creatorIds = array_map(static fn($offre) => $offre->getIdCreateurCible(), $offres);
$brandMap = $offreController->getUsersByIds($brandIds, 'marque');
$creatorMap = $offreController->getUsersByIds($creatorIds, 'createur');
$responseGroups = $offreController->getCandidaturesGroupedByOfferIds($offerIds);

$allOffres = $offreController->getAllOffres();
$allBrandIds = array_map(static fn($offre) => $offre->getIdMarque(), $allOffres);
$allCreatorIds = array_map(static fn($offre) => $offre->getIdCreateurCible(), $allOffres);
$allBrandMap = $offreController->getUsersByIds($allBrandIds, 'marque');
$allCreatorMap = $offreController->getUsersByIds($allCreatorIds, 'createur');

$selectedOffer = null;
$selectedOfferInList = false;
$selectedId = isset($_GET['idOffre']) && is_numeric($_GET['idOffre']) ? (int) $_GET['idOffre'] : null;

if ($selectedId !== null) {
    $selectedOffer = $offreController->getOffreByIdAdmin($selectedId);
    foreach ($offres as $offre) {
        if ((int) $offre->getIdOffre() === $selectedId) {
            $selectedOfferInList = true;
            break;
        }
    }
}

if (!$selectedOffer && !empty($offres)) {
    $selectedOffer = $offres[0];
    $selectedOfferInList = true;
}

$selectedResponses = $selectedOffer
    ? ($responseGroups[$selectedOffer->getIdOffre()] ?? $offreController->getCandidaturesByOffre($selectedOffer->getIdOffre()))
    : [];
$selectedBreakdown = $selectedOffer ? $offreController->getOfferResponseBreakdown($selectedOffer->getIdOffre()) : [
    'envoyee' => 0,
    'negociation' => 0,
    'en_etude' => 0,
    'acceptee' => 0,
    'refusee' => 0,
    'retiree' => 0,
    'total' => 0,
];
$selectedOfferOutsideFilters = $selectedOffer && !$selectedOfferInList;
$selectedBrand = $selectedOffer ? ($brandMap[$selectedOffer->getIdMarque()] ?? $allBrandMap[$selectedOffer->getIdMarque()] ?? null) : null;
$selectedCreator = $selectedOffer && !$selectedOffer->isDraftSansCreateur()
    ? ($creatorMap[$selectedOffer->getIdCreateurCible()] ?? $allCreatorMap[$selectedOffer->getIdCreateurCible()] ?? null)
    : null;

if (isset($_GET['ajax']) && $_GET['ajax'] === 'insights') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'selectedId' => $selectedOffer ? (int) $selectedOffer->getIdOffre() : null,
        'title' => $selectedOffer ? (string) $selectedOffer->getTitre() : 'Offer preview',
        'html' => renderOfferInsightsHtml(
            $selectedOffer,
            $selectedBrand,
            $selectedCreator,
            $selectedResponses,
            $selectedBreakdown,
            $selectedOfferOutsideFilters
        ),
        'inspectHtml' => renderOfferInspectCardHtml(
            $selectedOffer,
            $selectedBrand,
            $selectedCreator,
            $selectedResponses,
            $selectedBreakdown,
            $selectedOfferOutsideFilters
        ),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$paginationBase = $_GET;
unset($paginationBase['page'], $paginationBase['ajax']);
$prevPageUrl = $page > 1 ? 'index.php?' . http_build_query($paginationBase + ['page' => $page - 1]) : '';
$nextPageUrl = $hasNextPage ? 'index.php?' . http_build_query($paginationBase + ['page' => $page + 1]) : '';
$adminOfferMetrics = $offreController->getAdminOfferMetrics();
$platformMetrics = $candidatureController->getAdminPlatformMetrics();
$adminPieChartStats = $candidatureController->getAdminPieChartStats();

$liveCount = 0;
$pendingCount = 0;
$offersWithResponses = 0;
$averageBudget = 0;
$closingSoon = 0;
$liveBudgetOffers = [];

if (!empty($offres)) {
    $today = new DateTime('today');

    foreach ($offres as $offre) {
        if ($offre->isPendingPublication()) {
            $pendingCount++;
        } elseif ($offre->isLivePublication()) {
            $liveCount++;
            $liveBudgetOffers[] = $offre;
        }
        if (!empty($responseGroups[$offre->getIdOffre()] ?? [])) {
            $offersWithResponses++;
        }

        $deadline = DateTime::createFromFormat('Y-m-d', (string) $offre->getDateLimite());
        if ($deadline) {
            $days = (int) $today->diff($deadline)->format('%r%a');
            if ($days >= 0 && $days <= 7) {
                $closingSoon++;
            }
        }
    }
}

if (!empty($liveBudgetOffers)) {
    $averageBudget = array_sum(array_map(static fn($offre) => (float) $offre->getBudgetPropose(), $liveBudgetOffers)) / count($liveBudgetOffers);
}
$backActive = 'collaborations';

if (!function_exists('renderBackOfficeCollaborationTabs')) {
    function renderBackOfficeCollaborationTabs(string $activeTab): void
    {
        $tabs = [
            'offers' => [
                'label' => 'Offers',
                'labelKey' => 'collaboration.tab.offers',
                'hint' => 'Targeted invitations',
                'hintKey' => 'collaboration.tab.offersHint',
                'href' => '../offre/index.php',
                'icon' => 'mdi-briefcase-check',
            ],
            'candidatures' => [
                'label' => 'Candidatures',
                'labelKey' => 'collaboration.tab.candidatures',
                'hint' => 'Creator responses',
                'hintKey' => 'collaboration.tab.candidaturesHint',
                'href' => '../condidature/index.php',
                'icon' => 'mdi-account-check',
            ],
            'cre8shield' => [
                'label' => 'Cre8Shield',
                'labelKey' => 'collaboration.tab.cre8shield',
                'hint' => 'Risk monitoring',
                'hintKey' => 'collaboration.tab.cre8shieldHint',
                'href' => '../cre8shield/index.php',
                'icon' => 'mdi-shield-check',
            ],
        ];
        ?>
        <nav class="collaboration-subnav" aria-label="Collaboration workspace tabs">
            <ul class="collaboration-subnav__list">
                <?php foreach ($tabs as $key => $tab): ?>
                    <?php $isActive = $activeTab === $key; ?>
                    <li>
                        <a class="collaboration-subnav__link<?php echo $isActive ? ' is-active' : ''; ?>" href="<?php echo htmlspecialchars($tab['href']); ?>"<?php echo $isActive ? ' aria-current="page"' : ''; ?>>
                            <span class="collaboration-subnav__icon" aria-hidden="true">
                                <i class="mdi <?php echo htmlspecialchars($tab['icon']); ?>"></i>
                            </span>
                            <span class="collaboration-subnav__text">
                                <span class="collaboration-subnav__title" data-i18n="<?php echo htmlspecialchars($tab['labelKey']); ?>"><?php echo htmlspecialchars($tab['label']); ?></span>
                                <span class="collaboration-subnav__hint" data-i18n="<?php echo htmlspecialchars($tab['hintKey']); ?>"><?php echo htmlspecialchars($tab['hint']); ?></span>
                            </span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
        <?php
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php cre8_bo_early_theme_print_head_script(); ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Back Office - Offer Management</title>
    <link rel="stylesheet" href="../css/backoffice.css">
    <link rel="stylesheet" href="../layout/back-layout.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../layout/back-layout.css')); ?>">
    <link rel="stylesheet" href="../utilisateur/assets/vendors/mdi/css/materialdesignicons.min.css?v=<?php echo urlencode((string) (@filemtime(__DIR__ . '/../utilisateur/assets/vendors/mdi/css/materialdesignicons.min.css') ?: 0)); ?>">
    <link rel="stylesheet" href="../css/new_style_backoffice.css">
    <link rel="stylesheet" href="offre-admin.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/offre-admin.css')); ?>">

    <style>
        .collaboration-subnav {
            margin: 0 0 1.5rem;
            padding: 0.75rem;
            border: 1px solid rgba(148, 163, 184, 0.16);
            border-radius: 18px;
            background: rgba(17, 24, 39, 0.94);
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.18);
        }

        .collaboration-subnav__list {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.75rem;
            padding: 0;
            margin: 0;
            list-style: none;
        }

        .collaboration-subnav__link {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            min-height: 74px;
            padding: 0.9rem 1rem;
            border: 1px solid rgba(148, 163, 184, 0.14);
            border-radius: 15px;
            background: rgba(255, 255, 255, 0.04);
            color: #cbd5e1;
            text-decoration: none;
            transition: transform 0.18s ease, border-color 0.18s ease, background 0.18s ease, color 0.18s ease;
        }

        .collaboration-subnav__link:hover,
        .collaboration-subnav__link:focus {
            transform: translateY(-1px);
            border-color: rgba(151, 92, 232, 0.45);
            background: rgba(151, 92, 232, 0.12);
            color: #ffffff;
            text-decoration: none;
            outline: none;
        }

        .collaboration-subnav__link.is-active {
            border-color: rgba(255, 255, 255, 0.22);
            background: linear-gradient(135deg, rgba(151, 92, 232, 0.96), rgba(210, 24, 118, 0.9));
            color: #ffffff;
            box-shadow: 0 14px 30px rgba(151, 92, 232, 0.24);
        }

        .collaboration-subnav__icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 42px;
            width: 42px;
            height: 42px;
            border-radius: 14px;
            background: rgba(15, 23, 42, 0.28);
            color: inherit;
            font-size: 1.35rem;
        }

        .collaboration-subnav__text {
            display: flex;
            flex-direction: column;
            gap: 0.15rem;
            line-height: 1.2;
        }

        .collaboration-subnav__title {
            font-weight: 700;
            letter-spacing: 0.01em;
        }

        .collaboration-subnav__hint {
            color: rgba(226, 232, 240, 0.72);
            font-size: 0.78rem;
            font-weight: 500;
        }

        .collaboration-subnav__link.is-active .collaboration-subnav__hint {
            color: rgba(255, 255, 255, 0.82);
        }

        body.light-mode .collaboration-subnav {
            background: #ffffff;
            border-color: rgba(15, 23, 42, 0.08);
            box-shadow: 0 16px 36px rgba(15, 23, 42, 0.08);
        }

        body.light-mode .collaboration-subnav__link {
            background: #f8fafc;
            border-color: rgba(15, 23, 42, 0.08);
            color: #334155;
        }

        body.light-mode .collaboration-subnav__link:hover,
        body.light-mode .collaboration-subnav__link:focus {
            color: #111827;
            background: #f1f5f9;
        }

        body.light-mode .collaboration-subnav__link.is-active {
            color: #ffffff;
            background: linear-gradient(135deg, #7c3aed, #d21876);
        }

        body.light-mode .collaboration-subnav__hint {
            color: #64748b;
        }

        @media (max-width: 900px) {
            .collaboration-subnav__list {
                grid-template-columns: 1fr;
            }
        }
    </style>
<link rel="icon" type="image/png" sizes="16x16" href="../../public/images/favicon-16.png">
<link rel="icon" type="image/png" sizes="32x32" href="../../public/images/favicon-32.png">
<link rel="shortcut icon" type="image/png" href="../../public/images/favicon-32.png">
<link rel="apple-touch-icon" sizes="180x180" href="../../public/images/apple-touch-icon.png">
</head>
<body class="cre8-admin-layout"><?php cre8_bo_early_theme_print_body_script(); ?>
    <div class="container-scroller cre8-admin-page">
        <?php require_once dirname(__DIR__) . '/layout/sidebar.php'; ?>
        <main class="page-body-wrapper cre8-admin-main">
            <?php require_once dirname(__DIR__) . '/layout/header.php'; ?>
    <div class="main-panel">
    <div class="content-wrapper admin-shell">
        <header class="admin-header card grid-margin">
            <div class="admin-header-main card-body">
                <div>
                    <h1 data-i18n="offers.title">Offer administration</h1>
                    <p data-i18n="offers.subtitle">Track targeted offers, understand creator response behavior, and keep the collaboration pipeline visible for admins.</p>
                </div>
                <div class="bc-page-actions">
                    <a href="../condidature/admin_report.php?download=pdf" class="btn-export">
                        <i class="mdi mdi-printer"></i><span data-i18n="common.printPdf">Print / PDF</span>
                    </a>
                </div>
            </div>
        </header>

        <?php renderBackOfficeCollaborationTabs('offers'); ?>

        <?php if (!empty($message)): ?>
            <div class="admin-flash success"><?php echo htmlspecialchars($message); ?></div>
        <?php elseif (isset($_GET['deleted'])): ?>
            <div class="admin-flash success" data-i18n="offers.flash.deleted">Offer deleted successfully.</div>
        <?php endif; ?>

        <div class="admin-stats-toolbar">
            <div>
                <strong data-i18n="collaboration.statistics.title">Workspace statistics</strong>
                <span data-i18n="collaboration.statistics.subtitle">Live indicators and charts for collaboration activity.</span>
            </div>
            <button type="button" class="admin-stats-toggle" data-stats-toggle data-i18n="common.hideStatistics" aria-expanded="true">Hide statistics</button>
        </div>

        <section class="admin-stats-region" data-stats-region>
            <section class="admin-summary">
                <article class="admin-card card card-body bo-kpi-card bo-kpi-card-1">
                    <h3 data-i18n="offers.kpi.realOffers">Real offers</h3>
                    <p><?php echo (int) ($adminOfferMetrics['realOffers'] ?? 0); ?></p>
                    <small data-i18n="offers.kpi.realOffersSub">Total targeted invitations</small>
                </article>
                <article class="admin-card card card-body bo-kpi-card bo-kpi-card-2">
                    <h3 data-i18n="collaboration.kpi.realCandidatures">Real candidatures</h3>
                    <p><?php echo (int) ($platformMetrics['realCandidatures'] ?? 0); ?></p>
                    <small data-i18n="collaboration.kpi.placeholdersExcluded">Placeholders excluded</small>
                </article>
                <article class="admin-card card card-body bo-kpi-card bo-kpi-card-3">
                    <h3 data-i18n="collaboration.kpi.pendingReviews">Pending reviews</h3>
                    <p><?php echo (int) ($platformMetrics['pendingReviews'] ?? 0); ?></p>
                    <small data-i18n="collaboration.kpi.sentOrReview">Sent or under review</small>
                </article>
                <article class="admin-card card card-body bo-kpi-card bo-kpi-card-4">
                    <h3 data-i18n="collaboration.kpi.openNegotiations">Open negotiations</h3>
                    <p><?php echo (int) ($platformMetrics['openNegotiations'] ?? 0); ?></p>
                    <small data-i18n="collaboration.kpi.activeNegotiations">Active negotiation candidatures</small>
                </article>
                <article class="admin-card card card-body bo-kpi-card bo-kpi-card-5">
                    <h3 data-i18n="offers.kpi.expiredOffers">Expired offers</h3>
                    <p><?php echo (int) ($adminOfferMetrics['expiredOffers'] ?? 0); ?></p>
                    <small data-i18n="offers.kpi.expiredOffersSub">Past deadline and not archived</small>
                </article>
                <article class="admin-card card card-body bo-kpi-card bo-kpi-card-6">
                    <h3 data-i18n="collaboration.kpi.activityThisWeek">Activity this week</h3>
                    <p><?php echo (int) ($platformMetrics['activityThisWeek'] ?? 0); ?></p>
                    <small><?php echo htmlspecialchars((string) ($platformMetrics['acceptanceRate'] ?? 0)); ?>% acceptance rate</small>
                </article>
            </section>

            <?php require __DIR__ . '/../condidature/statistics_charts.php'; ?>
        </section>

        <section class="card grid-margin search-panel search-panel-simple">
            <div class="search-panel-head">
                <div class="search-panel-copy">
                    <span class="search-panel-title" data-i18n="offers.filter.title">Filter the offer list</span>
                    <span class="search-panel-subtitle">
                        <span data-i18n="offers.filter.subtitle">Search by offer, brand, creator, budget, or deadline without opening extra controls.</span>
                    </span>
                </div>
                <?php if ($hasActiveFilters): ?>
                    <span class="search-panel-badge"><?php echo $activeFilterCount; ?> active</span>
                <?php endif; ?>
            </div>

            <form method="get" class="search-form" data-module-validation="admin-filters" novalidate>
                <?php if ($brandIdFilter !== null): ?>
                    <input type="hidden" name="brandId" value="<?php echo (int) $brandIdFilter; ?>">
                <?php endif; ?>
                <div class="search-grid">
                    <div class="search-group">
                        <label for="keyword" data-i18n="offers.filter.keyword">Keyword</label>
                        <input id="keyword" name="keyword" type="search" class="form-control" value="<?php echo htmlspecialchars($searchKeyword); ?>" placeholder="Offer, brand, creator, or ID..." data-i18n-placeholder="offers.filter.keywordPlaceholder">
                    </div>

                    <div class="search-group">
                        <label for="statut" data-i18n="common.status">Status</label>
                        <select id="statut" name="statut" class="form-control">
                            <option value="" data-i18n-opt="common.all">All</option>
                            <option value="brouillon"<?php echo $searchStatut === 'brouillon' ? ' selected' : ''; ?> data-i18n-opt="offers.status.draft">Draft</option>
                            <option value="publiee"<?php echo $searchStatut === 'publiee' ? ' selected' : ''; ?> data-i18n-opt="offers.status.live">Live now</option>
                            <option value="pending"<?php echo $searchStatut === 'pending' ? ' selected' : ''; ?> data-i18n-opt="offers.status.pending">Pending launch</option>
                            <option value="cloturee"<?php echo $searchStatut === 'cloturee' ? ' selected' : ''; ?> data-i18n-opt="offers.status.closed">Closed</option>
                            <option value="expiree"<?php echo $searchStatut === 'expiree' ? ' selected' : ''; ?> data-i18n-opt="offers.status.expired">Expired</option>
                            <option value="archivee"<?php echo $searchStatut === 'archivee' ? ' selected' : ''; ?> data-i18n-opt="offers.status.archived">Archived</option>
                        </select>
                    </div>

                    <div class="search-group">
                        <label for="budgetFrom" data-i18n="offers.filter.budgetFrom">Budget from</label>
                        <input id="budgetFrom" name="budgetFrom" type="number" step="0.01" class="form-control" value="<?php echo htmlspecialchars($searchBudgetFrom); ?>" placeholder="0">
                    </div>

                    <div class="search-group">
                        <label for="budgetTo" data-i18n="offers.filter.budgetTo">Budget to</label>
                        <input id="budgetTo" name="budgetTo" type="number" step="0.01" class="form-control" value="<?php echo htmlspecialchars($searchBudgetTo); ?>" placeholder="0">
                    </div>

                    <div class="search-group">
                        <label for="dateLimite" data-i18n="offers.filter.deadlineFrom">Deadline from</label>
                        <input id="dateLimite" name="dateLimite" type="date" class="form-control" value="<?php echo htmlspecialchars($searchDateLimite); ?>">
                    </div>

                    <div class="search-group">
                        <label for="dateLimiteTo" data-i18n="offers.filter.deadlineTo">Deadline to</label>
                        <input id="dateLimiteTo" name="dateLimiteTo" type="date" class="form-control" value="<?php echo htmlspecialchars($searchDateLimiteTo); ?>">
                    </div>

                    <div class="search-group">
                        <label for="sort" data-i18n="offers.filter.sort">Sort</label>
                        <select id="sort" name="sort" class="form-control">
                            <option value=""<?php echo $searchSort === '' ? ' selected' : ''; ?> data-i18n-opt="offers.sort.newest">Newest</option>
                            <option value="oldest"<?php echo $searchSort === 'oldest' ? ' selected' : ''; ?> data-i18n-opt="offers.sort.oldest">Oldest</option>
                            <option value="deadline_soon"<?php echo $searchSort === 'deadline_soon' ? ' selected' : ''; ?> data-i18n-opt="offers.sort.deadlineSoon">Deadline soon</option>
                            <option value="budget_high"<?php echo $searchSort === 'budget_high' ? ' selected' : ''; ?> data-i18n-opt="offers.sort.budgetHigh">Budget high to low</option>
                            <option value="budget_low"<?php echo $searchSort === 'budget_low' ? ' selected' : ''; ?> data-i18n-opt="offers.sort.budgetLow">Budget low to high</option>
                            <option value="status"<?php echo $searchSort === 'status' ? ' selected' : ''; ?> data-i18n-opt="common.status">Status</option>
                        </select>
                    </div>
                </div>

                <div class="search-actions">
                    <button type="submit" class="btn btn-primary"><span data-i18n="common.applyFilters">Apply filters</span></button>
                    <a class="btn btn-secondary clear-link" href="index.php"><span data-i18n="common.reset">Reset</span></a>
                </div>
            </form>
        </section>

        <div id="admin-results-region" class="admin-results-region">
            <div class="admin-layout">
                <section class="admin-panel admin-table-panel card grid-margin">
                <div class="admin-panel-header">
                    <h2 class="card-title" data-i18n="offers.table.title">Offer list</h2>
                </div>
                <div class="admin-panel-body card-body">
                    <div class="admin-table-wrapper">
                        <table class="admin-table admin-offer-table table table-hover table-striped">
                            <colgroup>
                                <col class="offer-col-main">
                                <col class="offer-col-brand">
                                <col class="offer-col-creator">
                                <col class="offer-col-budget">
                                <col class="offer-col-date">
                                <col class="offer-col-status">
                                <col class="offer-col-responses">
                                <col class="offer-col-actions">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th data-i18n="common.offer">Offer</th>
                                    <th data-i18n="common.brand">Brand</th>
                                    <th data-i18n="offers.table.targetCreator">Target creator</th>
                                    <th data-i18n="common.budget">Budget</th>
                                    <th data-i18n="common.deadline">Deadline</th>
                                    <th data-i18n="common.status">Status</th>
                                    <th data-i18n="common.responses">Responses</th>
                                    <th data-i18n="common.actions">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($offres)): ?>
                                    <tr>
                                        <td colspan="8">
                                            <div class="admin-empty-state">
                                                <div class="admin-empty-icon">i</div>
                                                <strong data-i18n="common.empty">No records found</strong>
                                                <span data-i18n="common.emptyHint">Try changing filters or search terms.</span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>

                                <?php foreach ($offres as $offre): ?>
                                    <?php
                                    $isSelected = $selectedOffer && (int) $selectedOffer->getIdOffre() === (int) $offre->getIdOffre();
                                    $responses = $responseGroups[$offre->getIdOffre()] ?? [];
                                    $brand = $brandMap[$offre->getIdMarque()] ?? null;
                                    $creator = $creatorMap[$offre->getIdCreateurCible()] ?? null;
                                    $inspectUrl = buildInspectUrl($offre->getIdOffre(), $persistedFilters);
                                    $offerTitle = (string) $offre->getTitre();
                                    $offerObjective = 'ID #' . (int) $offre->getIdOffre() . ' - ' . (string) $offre->getObjectif();
                                    $brandName = (string) ($brand['nom'] ?? ('ID #' . $offre->getIdMarque()));
                                    $displayStatus = $offre->getDisplayStatusKey();
                                    $creatorName = $offre->isDraftSansCreateur()
                                        ? 'No creator selected'
                                        : (string) ($creator['nom'] ?? ($offre->getIdCreateurCible() ? ('ID #' . $offre->getIdCreateurCible()) : 'No creator selected'));
                                    ?>
                                    <tr<?php echo $isSelected ? ' class="is-selected"' : ''; ?> data-offer-id="<?php echo (int) $offre->getIdOffre(); ?>" data-inspect-url="<?php echo htmlspecialchars($inspectUrl); ?>" data-offer-title="<?php echo htmlspecialchars($offerTitle, ENT_QUOTES); ?>" tabindex="0">
                                        <td class="offer-cell">
                                            <strong class="offer-row-title table-hover-text" title="<?php echo htmlspecialchars($offerTitle, ENT_QUOTES); ?>"><?php echo htmlspecialchars(excerptText($offerTitle, 44)); ?></strong>
                                            <span class="row-label table-hover-text" title="<?php echo htmlspecialchars($offerObjective, ENT_QUOTES); ?>">ID #<?php echo (int) $offre->getIdOffre(); ?> - <?php echo htmlspecialchars(excerptText($offre->getObjectif(), 42)); ?></span>
                                        </td>
                                        <td class="entity-cell">
                                            <strong class="entity-primary table-hover-text" title="<?php echo htmlspecialchars($brandName, ENT_QUOTES); ?>"><?php echo htmlspecialchars($brandName); ?></strong>
                                        </td>
                                        <td class="entity-cell">
                                            <strong class="entity-primary table-hover-text" title="<?php echo htmlspecialchars($creatorName, ENT_QUOTES); ?>"><?php echo htmlspecialchars($creatorName); ?></strong>
                                        </td>
                                        <td class="money-cell"><?php echo htmlspecialchars(formatMoney($offre->getBudgetPropose())); ?></td>
                                        <td><?php echo htmlspecialchars($offre->getDateLimite()); ?></td>
                                        <td><span class="badge badge-status <?php echo htmlspecialchars($displayStatus); ?>" data-i18n="<?php echo htmlspecialchars(offerStatusI18nKey($displayStatus)); ?>"><?php echo htmlspecialchars(translateOfferStatus($displayStatus)); ?></span></td>
                                        <td class="responses-cell">
                                            <button
                                                type="button"
                                                class="responses-link"
                                                data-offer-responses-trigger
                                                data-offer-id="<?php echo (int) $offre->getIdOffre(); ?>"
                                                data-offer-title="<?php echo htmlspecialchars($offerTitle, ENT_QUOTES); ?>"
                                            >
                                                <strong><?php echo count($responses); ?></strong>
                                                <span data-i18n="common.responses">Responses</span>
                                            </button>
                                        </td>
                                        <td class="admin-actions">
                                            <div class="admin-actions-stack">
                                                <a class="btn btn-info btn-sm inspect-link" href="<?php echo htmlspecialchars($inspectUrl); ?>"><span data-i18n="common.inspect">Inspect</span></a>
                                                <form
                                                    method="post"
                                                    class="inline-delete-form"
                                                    data-delete-confirm
                                                    data-delete-title="<?php echo htmlspecialchars($offerTitle, ENT_QUOTES); ?>"
                                                    data-delete-creator="<?php echo htmlspecialchars($creatorName, ENT_QUOTES); ?>"
                                                >
                                                    <input type="hidden" name="idOffreToDelete" value="<?php echo (int) $offre->getIdOffre(); ?>">
                                                    <button type="submit" name="deleteOffre" class="btn btn-danger btn-sm delete-btn"><span data-i18n="common.delete">Delete</span></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="offer-response-templates" hidden>
                        <?php foreach ($offres as $offre): ?>
                            <?php $responses = $responseGroups[$offre->getIdOffre()] ?? []; ?>
                            <template id="offerResponsesTemplate-<?php echo (int) $offre->getIdOffre(); ?>">
                                <?php echo renderOfferResponsesPanelHtml($offre, $responses); ?>
                            </template>
                        <?php endforeach; ?>
                    </div>
                    <nav class="admin-pagination" aria-label="Offer pages">
                        <span class="admin-pagination-info">Page <?php echo $page; ?> of <?php echo $hasNextPage ? ($page + 1) . '+' : $page; ?></span>
                        <div class="admin-page-list">
                            <?php if ($prevPageUrl !== ''): ?>
                                <a class="admin-page-btn" href="<?php echo htmlspecialchars($prevPageUrl); ?>">&laquo;</a>
                            <?php else: ?>
                                <span class="admin-page-btn is-disabled">&laquo;</span>
                            <?php endif; ?>
                            <?php
                            $lastKnownPage = $hasNextPage ? $page + 1 : $page;
                            $startPage = max(1, $page - 2);
                            $endPage = max($lastKnownPage, min($lastKnownPage, $page + 2));
                            if ($startPage > 1): ?>
                                <a class="admin-page-btn" href="<?php echo htmlspecialchars(adminPageUrl($paginationBase, 1)); ?>">1</a>
                                <?php if ($startPage > 2): ?><span class="admin-page-ellipsis">...</span><?php endif; ?>
                            <?php endif; ?>
                            <?php for ($pageNumber = $startPage; $pageNumber <= $endPage; $pageNumber++): ?>
                                <?php if ($pageNumber === $page): ?>
                                    <span class="admin-page-btn active"><?php echo $pageNumber; ?></span>
                                <?php else: ?>
                                    <a class="admin-page-btn" href="<?php echo htmlspecialchars(adminPageUrl($paginationBase, $pageNumber)); ?>"><?php echo $pageNumber; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            <?php if ($hasNextPage): ?><span class="admin-page-ellipsis">...</span><?php endif; ?>
                            <?php if ($nextPageUrl !== ''): ?>
                                <a class="admin-page-btn" href="<?php echo htmlspecialchars($nextPageUrl); ?>">&raquo;</a>
                            <?php else: ?>
                                <span class="admin-page-btn is-disabled">&raquo;</span>
                            <?php endif; ?>
                        </div>
                    </nav>
                </div>
                </section>
            </div>
        </div>
    </div>
    <?php require __DIR__ . '/../layout/footer.php'; ?>
    <dialog class="inspect-dialog" id="offerInspectDialog" aria-labelledby="offerInspectDialogTitle">
        <div class="inspect-dialog-card">
            <div class="inspect-dialog-header">
                <div>
                    <span class="inspect-dialog-kicker" data-i18n="offers.dialog.adminPreview">Admin preview</span>
                    <h2 id="offerInspectDialogTitle" data-i18n="offers.dialog.previewTitle">Offer preview</h2>
                    <p data-i18n="offers.dialog.previewSubtitle">Open a compact offer card without leaving the dashboard list.</p>
                </div>
                <button type="button" class="inspect-dialog-close" data-close-inspect-dialog aria-label="Close offer preview" data-i18n-aria-label="offers.dialog.closePreview"><span data-i18n="common.close">Close</span></button>
            </div>
            <div class="inspect-dialog-body" id="offerInspectDialogBody">
                <?php echo renderOfferInspectCardHtml(
                    $selectedOffer,
                    $selectedBrand,
                    $selectedCreator,
                    $selectedResponses,
                    $selectedBreakdown,
                    $selectedOfferOutsideFilters
                ); ?>
            </div>
            </div>
    </dialog>
    <dialog class="responses-dialog" id="offerResponsesDialog" aria-labelledby="offerResponsesDialogTitle">
        <div class="responses-dialog-card">
            <div class="responses-dialog-header">
                <div>
                    <span class="inspect-dialog-kicker" data-i18n="offers.dialog.relatedCandidatures">Related candidatures</span>
                    <h2 id="offerResponsesDialogTitle" data-i18n="offers.dialog.responsesTitle">Offer responses</h2>
                    <p data-i18n="offers.dialog.responsesSubtitle">Creator responses linked to this offer invitation only.</p>
                </div>
                <button type="button" class="inspect-dialog-close" data-close-responses-dialog aria-label="Close offer responses" data-i18n-aria-label="offers.dialog.closeResponses"><span data-i18n="common.close">Close</span></button>
            </div>
            <div class="responses-dialog-body" id="offerResponsesDialogBody">
                <div class="detail-empty-state">
                    <span class="detail-empty-icon">i</span>
                    <h4 data-i18n="offers.dialog.noResponses">No creator responses yet for this offer.</h4>
                    <p data-i18n="offers.dialog.selectResponseHint">Select an offer response button from the table to load related candidatures.</p>
                </div>
            </div>
        </div>
    </dialog>
    <dialog class="delete-dialog" id="offerDeleteDialog" aria-labelledby="offerDeleteDialogTitle">
        <div class="delete-dialog-card">
            <div class="delete-dialog-header">
                <div>
                    <span class="delete-dialog-kicker" data-i18n="offers.delete.kicker">Delete offer</span>
                    <h2 id="offerDeleteDialogTitle" data-i18n="offers.delete.title">Remove this offer?</h2>
                    <p data-i18n="offers.delete.subtitle">This action permanently removes the selected offer from the admin pipeline.</p>
                </div>
                <button type="button" class="delete-dialog-close" data-delete-close aria-label="Cancel offer deletion" data-i18n-aria-label="offers.delete.cancelLabel"><span data-i18n="common.cancel">Cancel</span></button>
            </div>
            <div class="delete-dialog-body">
                <div class="delete-dialog-preview">
                    <span class="delete-dialog-preview-label" data-i18n="offers.delete.selectedOffer">Selected offer</span>
                    <strong id="offerDeleteDialogOffer">This targeted offer</strong>
                    <span id="offerDeleteDialogCreator">Creator context will appear here.</span>
                </div>
                <p class="delete-dialog-warning" data-i18n="common.cannotUndo">This action cannot be undone.</p>
                <div class="delete-dialog-actions">
                    <button type="button" class="delete-dialog-secondary" data-delete-close><span data-i18n="offers.delete.keep">Keep offer</span></button>
                    <button type="button" class="delete-dialog-danger" id="offerDeleteDialogConfirm"><span data-i18n="common.deletePermanently">Delete permanently</span></button>
                </div>
            </div>
        </div>
    </dialog>
        </main>
    </div>
    <script>
        window.cre8BackRegisterTranslations && window.cre8BackRegisterTranslations({
            en: {
                'common.search': 'Search',
                'common.applyFilters': 'Apply filters',
                'common.reset': 'Reset',
                'common.hideStatistics': 'Hide statistics',
                'common.showStatistics': 'Show statistics',
                'common.actions': 'Actions',
                'common.status': 'Status',
                'common.page': 'Page',
                'common.of': 'of',
                'common.empty': 'No records found',
                'common.delete': 'Delete',
                'common.inspect': 'Inspect',
                'common.review': 'Review',
                'common.source': 'Source',
                'common.responses': 'Responses',
                'common.close': 'Close',
                'common.cancel': 'Cancel',
                'common.all': 'All',
                'common.brand': 'Brand',
                'common.creator': 'Creator',
                'common.deadline': 'Deadline',
                'common.budget': 'Budget',
                'common.description': 'Description',
                'common.start': 'Start',
                'common.end': 'End',
                'common.published': 'Published',
                'common.offer': 'Offer',
                'common.campaign': 'Campaign',
                'common.notAvailable': 'Not available',
                'common.notShared': 'Not shared',
                'collaboration.tab.offers': 'Offers',
                'collaboration.tab.offersHint': 'Targeted invitations',
                'collaboration.tab.candidatures': 'Candidatures',
                'collaboration.tab.candidaturesHint': 'Creator responses',
                'collaboration.tab.cre8shield': 'Cre8Shield',
                'collaboration.tab.cre8shieldHint': 'Risk monitoring',
                'collaboration.statistics.title': 'Workspace statistics',
                'collaboration.statistics.subtitle': 'Live indicators and charts for collaboration activity.',
                'collaboration.kpi.realCandidatures': 'Real candidatures',
                'collaboration.kpi.placeholdersExcluded': 'Placeholders excluded',
                'collaboration.kpi.pendingReviews': 'Pending reviews',
                'collaboration.kpi.sentOrReview': 'Sent or under review',
                'collaboration.kpi.openNegotiations': 'Open negotiations',
                'collaboration.kpi.activeNegotiations': 'Active negotiation candidatures',
                'collaboration.kpi.activityThisWeek': 'Activity this week',
                'offers.kpi.expiredOffers': 'Expired offers',
                'offers.kpi.expiredOffersSub': 'Past deadline and not archived',
                'offers.filter.sort': 'Sort',
                'offers.sort.newest': 'Newest',
                'offers.sort.oldest': 'Oldest',
                'offers.sort.budgetHigh': 'Budget high to low',
                'offers.sort.budgetLow': 'Budget low to high',
                'offers.title': 'Offer administration',
                'offers.subtitle': 'Track targeted offers, understand creator response behavior, and keep the collaboration pipeline visible for admins.',
                'offers.flash.deleted': 'Offer deleted successfully.',
                'offers.kpi.realOffers': 'Real offers',
                'offers.kpi.realOffersSub': 'Total targeted invitations',
                'offers.filter.title': 'Filter the offer list',
                'offers.filter.subtitle': 'Search by offer, brand, creator, budget, or deadline without opening extra controls.',
                'offers.filter.keyword': 'Keyword',
                'offers.filter.keywordPlaceholder': 'Offer, brand, creator, or ID...',
                'offers.filter.budgetFrom': 'Budget from',
                'offers.filter.budgetTo': 'Budget to',
                'offers.filter.deadlineFrom': 'Deadline from',
                'offers.filter.deadlineTo': 'Deadline to',
                'offers.table.title': 'Offer list',
                'offers.table.targetCreator': 'Target creator',
                'offers.status.draft': 'Draft',
                'offers.status.live': 'Live now',
                'offers.status.pending': 'Pending launch',
                'offers.status.closed': 'Closed',
                'offers.status.expired': 'Expired',
                'offers.status.archived': 'Archived',
                'offers.sort.deadlineSoon': 'Deadline soon',
                'offers.dialog.adminPreview': 'Admin preview',
                'offers.dialog.previewTitle': 'Offer preview',
                'offers.dialog.previewSubtitle': 'Open a compact offer card without leaving the dashboard list.',
                'offers.dialog.closePreview': 'Close offer preview',
                'offers.dialog.relatedCandidatures': 'Related candidatures',
                'offers.dialog.responsesTitle': 'Offer responses',
                'offers.dialog.responsesSubtitle': 'Creator responses linked to this offer invitation only.',
                'offers.dialog.closeResponses': 'Close offer responses',
                'offers.dialog.noResponses': 'No creator responses yet for this offer.',
                'offers.dialog.selectResponseHint': 'Select an offer response button from the table to load related candidatures.',
                'offers.delete.kicker': 'Delete offer',
                'offers.delete.title': 'Remove this offer?',
                'offers.delete.subtitle': 'This action permanently removes the selected offer from the admin pipeline.',
                'offers.delete.cancelLabel': 'Cancel offer deletion',
                'offers.delete.selectedOffer': 'Selected offer',
                'offers.delete.keep': 'Keep offer',
                'common.emptyHint': 'Try changing filters or search terms.',
                'common.cannotUndo': 'This action cannot be undone.',
                'common.deletePermanently': 'Delete permanently'
            },
            fr: {
                'common.search': 'Rechercher',
                'common.applyFilters': 'Appliquer les filtres',
                'common.reset': 'Reinitialiser',
                'common.hideStatistics': 'Masquer les statistiques',
                'common.showStatistics': 'Afficher les statistiques',
                'common.actions': 'Actions',
                'common.status': 'Statut',
                'common.page': 'Page',
                'common.of': 'sur',
                'common.empty': 'Aucun enregistrement trouve',
                'common.delete': 'Supprimer',
                'common.inspect': 'Inspecter',
                'common.review': 'Examiner',
                'common.source': 'Source',
                'common.responses': 'Reponses',
                'common.close': 'Fermer',
                'common.cancel': 'Annuler',
                'common.all': 'Tous',
                'common.brand': 'Marque',
                'common.creator': 'Createur',
                'common.deadline': 'Date limite',
                'common.budget': 'Budget',
                'common.description': 'Description',
                'common.start': 'Debut',
                'common.end': 'Fin',
                'common.published': 'Publie',
                'common.offer': 'Offre',
                'common.campaign': 'Campagne',
                'common.notAvailable': 'Non disponible',
                'common.notShared': 'Non partage',
                'collaboration.tab.offers': 'Offres',
                'collaboration.tab.offersHint': 'Invitations ciblees',
                'collaboration.tab.candidatures': 'Candidatures',
                'collaboration.tab.candidaturesHint': 'Reponses des createurs',
                'collaboration.tab.cre8shield': 'Cre8Shield',
                'collaboration.tab.cre8shieldHint': 'Surveillance des risques',
                'collaboration.statistics.title': 'Statistiques de l espace',
                'collaboration.statistics.subtitle': 'Indicateurs et graphiques en direct pour les collaborations.',
                'collaboration.kpi.realCandidatures': 'Candidatures reelles',
                'collaboration.kpi.placeholdersExcluded': 'Elements techniques exclus',
                'collaboration.kpi.pendingReviews': 'Revues en attente',
                'collaboration.kpi.sentOrReview': 'Envoyees ou en cours de revue',
                'collaboration.kpi.openNegotiations': 'Negociations ouvertes',
                'collaboration.kpi.activeNegotiations': 'Candidatures en negociation active',
                'collaboration.kpi.activityThisWeek': 'Activite cette semaine',
                'offers.kpi.expiredOffers': 'Offres expirees',
                'offers.kpi.expiredOffersSub': 'Date limite depassee et non archivee',
                'offers.filter.sort': 'Tri',
                'offers.sort.newest': 'Plus recentes',
                'offers.sort.oldest': 'Plus anciennes',
                'offers.sort.budgetHigh': 'Budget decroissant',
                'offers.sort.budgetLow': 'Budget croissant',
                'offers.title': 'Administration des offres',
                'offers.subtitle': 'Suivez les offres ciblees, les reponses des createurs et le pipeline de collaboration.',
                'offers.flash.deleted': 'Offre supprimee avec succes.',
                'offers.kpi.realOffers': 'Offres reelles',
                'offers.kpi.realOffersSub': 'Total des invitations ciblees',
                'offers.filter.title': 'Filtrer la liste des offres',
                'offers.filter.subtitle': 'Recherchez par offre, marque, createur, budget ou date limite.',
                'offers.filter.keyword': 'Mot-cle',
                'offers.filter.keywordPlaceholder': 'Offre, marque, createur ou ID...',
                'offers.filter.budgetFrom': 'Budget min',
                'offers.filter.budgetTo': 'Budget max',
                'offers.filter.deadlineFrom': 'Date limite depuis',
                'offers.filter.deadlineTo': 'Date limite jusqu a',
                'offers.table.title': 'Liste des offres',
                'offers.table.targetCreator': 'Createur cible',
                'offers.status.draft': 'Brouillon',
                'offers.status.live': 'En ligne',
                'offers.status.pending': 'Lancement en attente',
                'offers.status.closed': 'Cloturee',
                'offers.status.expired': 'Expiree',
                'offers.status.archived': 'Archivee',
                'offers.sort.deadlineSoon': 'Date limite proche',
                'offers.dialog.adminPreview': 'Apercu admin',
                'offers.dialog.previewTitle': 'Apercu de l offre',
                'offers.dialog.previewSubtitle': 'Ouvrir une carte compacte sans quitter la liste.',
                'offers.dialog.closePreview': 'Fermer l apercu',
                'offers.dialog.relatedCandidatures': 'Candidatures liees',
                'offers.dialog.responsesTitle': 'Reponses a l offre',
                'offers.dialog.responsesSubtitle': 'Reponses des createurs liees uniquement a cette invitation.',
                'offers.dialog.closeResponses': 'Fermer les reponses',
                'offers.dialog.noResponses': 'Aucune reponse de createur pour cette offre.',
                'offers.dialog.selectResponseHint': 'Selectionnez le bouton reponses dans le tableau pour charger les candidatures.',
                'offers.delete.kicker': 'Supprimer l offre',
                'offers.delete.title': 'Supprimer cette offre ?',
                'offers.delete.subtitle': 'Cette action retire definitivement l offre du pipeline admin.',
                'offers.delete.cancelLabel': 'Annuler la suppression',
                'offers.delete.selectedOffer': 'Offre selectionnee',
                'offers.delete.keep': 'Garder l offre',
                'common.emptyHint': 'Essayez de modifier les filtres ou la recherche.',
                'common.cannotUndo': 'Cette action est irreversible.',
                'common.deletePermanently': 'Supprimer definitivement'
            }
        });
    </script>
    <script src="../layout/back-layout.js?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../layout/back-layout.js')); ?>"></script>
    <script src="offre-admin-validation.js"></script>
    <script src="offre-admin-delete.js?v=<?php echo urlencode((string) filemtime(__DIR__ . '/offre-admin-delete.js')); ?>"></script>
    <script>
        (() => {
            const rows = document.querySelectorAll('.admin-table tbody tr[data-inspect-url]');
            const insightsBody = document.getElementById('offerInsightsBody');
            const inspectDialog = document.getElementById('offerInspectDialog');
            const inspectDialogBody = document.getElementById('offerInspectDialogBody');
            const responsesDialog = document.getElementById('offerResponsesDialog');
            const responsesDialogBody = document.getElementById('offerResponsesDialogBody');
            const responsesDialogTitle = document.getElementById('offerResponsesDialogTitle');
            const responseTriggers = document.querySelectorAll('[data-offer-responses-trigger]');
            const tablePanel = document.querySelector('.admin-table-panel');
            const tablePanelBody = tablePanel ? tablePanel.querySelector('.admin-panel-body') : null;
            const tableWrapper = tablePanel ? tablePanel.querySelector('.admin-table-wrapper') : null;
            const detailsPanel = document.querySelector('.admin-details-panel');
            const loadingCardHtml = `
                <div class="detail-empty-state">
                    <span class="detail-empty-icon">…</span>
                    <h4>Loading offer preview</h4>
                    <p>The detailed inspection card is being prepared for this offer.</p>
                </div>
            `;

            function resetDashboardPanelHeight() {
                if (tablePanel) {
                    tablePanel.style.height = '';
                }

                if (tableWrapper) {
                    tableWrapper.style.height = '';
                    tableWrapper.style.maxHeight = '';
                }
            }

            function syncDashboardPanelHeight() {
                if (!tablePanel || !tablePanelBody || !tableWrapper || !detailsPanel) {
                    return;
                }

                if (window.innerWidth <= 1180) {
                    resetDashboardPanelHeight();
                    return;
                }

                const detailsHeight = Math.round(detailsPanel.getBoundingClientRect().height);
                if (!detailsHeight) {
                    return;
                }

                const tableHeader = tablePanel.querySelector('.admin-panel-header');
                const tableHeaderHeight = tableHeader ? tableHeader.offsetHeight : 0;
                const bodyStyles = window.getComputedStyle(tablePanelBody);
                const bodyPadding =
                    parseFloat(bodyStyles.paddingTop || '0') +
                    parseFloat(bodyStyles.paddingBottom || '0');
                const availableTableHeight = Math.max(260, Math.floor(detailsHeight - tableHeaderHeight - bodyPadding));

                tablePanel.style.height = `${detailsHeight}px`;
                tableWrapper.style.height = `${availableTableHeight}px`;
                tableWrapper.style.maxHeight = `${availableTableHeight}px`;
            }

            function openInspectDialog(html) {
                if (!inspectDialog || !inspectDialogBody) {
                    return;
                }

                inspectDialogBody.innerHTML = html || '';
                if (window.cre8BackApplyTranslations) { window.cre8BackApplyTranslations(); }

                if (typeof inspectDialog.showModal === 'function') {
                    if (!inspectDialog.open) {
                        inspectDialog.showModal();
                    }
                    return;
                }

                inspectDialog.setAttribute('open', 'open');
            }

            function closeInspectDialog() {
                if (!inspectDialog) {
                    return;
                }

                if (typeof inspectDialog.close === 'function' && inspectDialog.open) {
                    inspectDialog.close();
                    return;
                }

                inspectDialog.removeAttribute('open');
            }

            function openResponsesDialog(trigger) {
                if (!responsesDialog || !responsesDialogBody || !trigger) {
                    return;
                }

                const template = document.getElementById(`offerResponsesTemplate-${trigger.dataset.offerId || ''}`);
                responsesDialogBody.innerHTML = template ? template.innerHTML : `
                    <div class="detail-empty-state">
                        <span class="detail-empty-icon">i</span>
                        <h4 data-i18n="offers.dialog.noResponses">No creator responses yet for this offer.</h4>
                        <p>This panel only shows creator responses attached to this offer invitation.</p>
                    </div>
                `;

                if (responsesDialogTitle) {
                    responsesDialogTitle.textContent = trigger.dataset.offerTitle
                        ? `Responses for ${trigger.dataset.offerTitle}`
                        : 'Offer responses';
                }

                if (typeof responsesDialog.showModal === 'function') {
                    if (!responsesDialog.open) {
                        responsesDialog.showModal();
                    }
                    return;
                }

                responsesDialog.setAttribute('open', 'open');
            }

            function closeResponsesDialog() {
                if (!responsesDialog) {
                    return;
                }

                if (typeof responsesDialog.close === 'function' && responsesDialog.open) {
                    responsesDialog.close();
                    return;
                }

                responsesDialog.removeAttribute('open');
            }

            function setSelectedRow(selectedId) {
                rows.forEach((row) => {
                    row.classList.toggle('is-selected', String(row.dataset.offerId) === String(selectedId));
                });
            }

            async function loadInsights(url, row, options = {}) {
                if (!window.fetch || (!insightsBody && !options.openModal)) {
                    window.location.href = url;
                    return;
                }

                if (options.openModal) {
                    openInspectDialog(loadingCardHtml);
                }

                const ajaxUrl = new URL(url, window.location.href);
                ajaxUrl.searchParams.set('ajax', 'insights');
                if (insightsBody) {
                    insightsBody.classList.add('is-loading');
                }

                try {
                    const response = await fetch(ajaxUrl.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (!response.ok) {
                        throw new Error('Unable to load offer insights.');
                    }

                    const payload = await response.json();
                    if (insightsBody) {
                        insightsBody.innerHTML = payload.html || '';
                    }
                    if (payload.selectedId !== null && payload.selectedId !== undefined) {
                        setSelectedRow(payload.selectedId);
                    } else if (row) {
                        setSelectedRow(row.dataset.offerId);
                    }

                    if (options.openModal) {
                        openInspectDialog(payload.inspectHtml || payload.html || '');
                    }

                    window.history.replaceState({}, '', url);
                    syncDashboardPanelHeight();
                } catch (error) {
                    if (options.openModal) {
                        openInspectDialog(`
                            <div class="detail-empty-state">
                                <span class="detail-empty-icon">!</span>
                                <h4>Preview unavailable</h4>
                                <p>The preview card could not be loaded right now. The dashboard will open the offer directly instead.</p>
                            </div>
                        `);
                        window.setTimeout(() => {
                            window.location.href = url;
                        }, 500);
                        return;
                    }
                    window.location.href = url;
                } finally {
                    if (insightsBody) {
                        insightsBody.classList.remove('is-loading');
                    }
                }
            }

            rows.forEach((row) => {
                const url = row.dataset.inspectUrl;
                if (!url) {
                    return;
                }

                const inspectLink = row.querySelector('.inspect-link');
                if (inspectLink) {
                    inspectLink.addEventListener('click', (event) => {
                        event.preventDefault();
                        loadInsights(url, row, { openModal: true });
                    });
                }

                row.addEventListener('click', (event) => {
                    if (event.target.closest('button, form, .delete-btn')) {
                        return;
                    }
                    if (event.target.closest('a')) {
                        return;
                    }
                    loadInsights(url, row);
                });

                row.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        loadInsights(url, row);
                    }
                });
            });

            responseTriggers.forEach((trigger) => {
                trigger.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    openResponsesDialog(trigger);
                });
            });

            document.addEventListener('click', (event) => {
                const trigger = event.target.closest('[data-offer-responses-trigger]');
                if (!trigger || Array.prototype.includes.call(responseTriggers, trigger)) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();
                openResponsesDialog(trigger);
            });

            if (inspectDialog) {
                inspectDialog.addEventListener('click', (event) => {
                    if (event.target === inspectDialog || event.target.closest('[data-close-inspect-dialog]')) {
                        closeInspectDialog();
                    }
                });

                inspectDialog.addEventListener('cancel', () => {
                    closeInspectDialog();
                });
            }

            if (responsesDialog) {
                responsesDialog.addEventListener('click', (event) => {
                    if (event.target === responsesDialog || event.target.closest('[data-close-responses-dialog]')) {
                        closeResponsesDialog();
                    }
                });

                responsesDialog.addEventListener('cancel', () => {
                    closeResponsesDialog();
                });
            }

            syncDashboardPanelHeight();
            window.addEventListener('resize', syncDashboardPanelHeight);

            if (window.ResizeObserver && detailsPanel) {
                const dashboardObserver = new ResizeObserver(() => {
                    syncDashboardPanelHeight();
                });
                dashboardObserver.observe(detailsPanel);
            }
        })();

        (() => {
            const selector = '#admin-results-region';
            const getRegion = () => document.querySelector(selector);

            if (!window.fetch || !window.DOMParser || !window.history || !getRegion()) {
                return;
            }



            const buildGetUrlFromForm = (form) => {
                const action = form.getAttribute('action') || window.location.pathname;
                const url = new URL(action, window.location.href);
                const params = new URLSearchParams();
                const data = new FormData(form);
                data.forEach((value, key) => {
                    const stringValue = String(value).trim();
                    if (stringValue !== '') {
                        params.append(key, stringValue);
                    }
                });
                url.search = params.toString();
                url.hash = '';
                return url.toString();
            };

            const replaceRegion = async (url, pushState = true) => {
                const currentRegion = getRegion();
                if (!currentRegion) {
                    window.location.href = url;
                    return;
                }

                currentRegion.classList.add('is-loading');

                try {
                    const response = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (!response.ok) {
                        throw new Error('Unable to load results.');
                    }

                    const html = await response.text();
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const nextRegion = doc.querySelector(selector);

                    if (!nextRegion) {
                        throw new Error('Results region missing.');
                    }

                    currentRegion.replaceWith(nextRegion);
                    if (pushState) {
                        window.history.pushState({ adminResultsUrl: url }, '', url);
                    }
                    window.dispatchEvent(new Event('resize'));
                    if (window.cre8BackApplyTranslations) { window.cre8BackApplyTranslations(); }
                } catch (error) {
                    window.location.href = url;
                } finally {
                    const activeRegion = getRegion();
                    if (activeRegion) {
                        activeRegion.classList.remove('is-loading');
                    }
                }
            };

            document.addEventListener('click', (event) => {
                const link = event.target.closest(`${selector} .admin-pagination a.admin-page-btn`);
                if (!link || event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                    return;
                }

                const url = link.href;
                if (!url) {
                    return;
                }

                event.preventDefault();
                replaceRegion(url);
            });



            document.addEventListener('submit', (event) => {
                const form = event.target.closest('form.search-form');
                if (!form || event.defaultPrevented) {
                    return;
                }

                const method = (form.getAttribute('method') || 'get').toLowerCase();
                if (method !== 'get') {
                    return;
                }

                event.preventDefault();
                replaceRegion(buildGetUrlFromForm(form));
            });

            document.addEventListener('click', (event) => {
                const resetLink = event.target.closest('form.search-form a.clear-link[href]');
                if (!resetLink || event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                    return;
                }

                event.preventDefault();
                replaceRegion(new URL(resetLink.getAttribute('href'), window.location.href).toString());
            });


            window.addEventListener('popstate', () => {
                replaceRegion(window.location.href, false);
            });
        })();

        (() => {
            const region = document.querySelector('[data-stats-region]');
            const toggle = document.querySelector('[data-stats-toggle]');
            if (!region || !toggle) {
                return;
            }

            const key = 'cre8_bo_stats_visible';
            const setVisible = (visible) => {
                region.hidden = !visible;
                const key = visible ? 'common.hideStatistics' : 'common.showStatistics';
                toggle.setAttribute('data-i18n', key);
                toggle.textContent = window.cre8BackText
                    ? window.cre8BackText(key)
                    : (visible ? 'Hide statistics' : 'Show statistics');
                if (window.cre8BackApplyStatsToggleButtons) {
                    window.cre8BackApplyStatsToggleButtons();
                } else if (window.cre8BackApplyTranslations) {
                    window.cre8BackApplyTranslations();
                }
                toggle.setAttribute('aria-expanded', visible ? 'true' : 'false');
                if (visible) {
                    window.dispatchEvent(new Event('resize'));
                }
            };

            let visible = true;
            try {
                visible = localStorage.getItem(key) !== '0';
            } catch (error) {}

            setVisible(visible);
            toggle.addEventListener('click', () => {
                visible = region.hidden;
                try {
                    localStorage.setItem(key, visible ? '1' : '0');
                } catch (error) {}
                setVisible(visible);
            });
        })();
    </script>
</body>
</html>
