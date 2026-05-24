<?php
require_once __DIR__ . '/../layout/session_bridge.php';
require_once __DIR__ . '/../../../Controleur/condidatureC.php';

$currentUser = cre8_front_require_user();
if (!cre8_front_is_admin_visitor($currentUser)) {
    http_response_code(403);
    exit('Access denied for this workspace.');
}

$frontActive = 'collaborations';
$controller = new CondidatureC();
$isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
    || isset($_GET['ajax']);

function cre8_admin_cand_h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function cre8_admin_candidature_money($value): string
{
    if ($value === null || $value === '' || !is_numeric($value)) {
        return 'Not specified';
    }

    return 'EUR ' . number_format((float) $value, 2, '.', ',');
}

function cre8_admin_candidature_delay($value): string
{
    if ($value === null || $value === '' || (int) $value <= 0) {
        return 'Not specified';
    }

    return (int) $value . ' days';
}

function cre8_admin_candidature_excerpt($value, int $length = 150): string
{
    $value = trim((string) $value);
    if ($value === '') {
        return 'No message preview.';
    }

    return strlen($value) > $length ? rtrim(substr($value, 0, $length - 3)) . '...' : $value;
}

function cre8_admin_candidature_status_label($status): string
{
    return [
        'brouillon' => 'Draft',
        'envoyee' => 'Sent',
        'en_etude' => 'Under review',
        'negociation' => 'Negotiation',
        'acceptee' => 'Accepted',
        'refusee' => 'Refused',
        'retiree' => 'Withdrawn',
    ][(string) $status] ?? ucwords(str_replace('_', ' ', (string) $status));
}

function cre8_admin_candidature_origin_label($origin): string
{
    return (string) $origin === 'par_campagne' ? 'Campaign' : 'Offer';
}

function cre8_admin_candidature_filters(): array
{
    $allowedStatuses = ['', 'brouillon', 'envoyee', 'en_etude', 'negociation', 'acceptee', 'refusee', 'retiree'];
    $allowedOrigins = ['', 'par_offre', 'par_campagne'];

    $status = trim((string) ($_GET['status'] ?? ''));
    $origin = trim((string) ($_GET['origin'] ?? ''));

    return [
        'keyword' => trim((string) ($_GET['keyword'] ?? '')),
        'status' => in_array($status, $allowedStatuses, true) ? $status : '',
        'origin' => in_array($origin, $allowedOrigins, true) ? $origin : '',
        'sort' => trim((string) ($_GET['sort'] ?? '')),
        'limit' => 24,
        'offset' => 0,
    ];
}

function cre8_admin_candidature_load(CondidatureC $controller, array $filters): array
{
    try {
        return [$controller->getAdminCandidatures($filters), null];
    } catch (Throwable $e) {
        return [[], 'Candidatures could not be loaded right now.'];
    }
}

function cre8_admin_candidature_render_cards(array $contexts, ?string $loadError): string
{
    ob_start();
    ?>
    <?php if ($loadError): ?>
        <section class="readonly-empty-state"><?php echo cre8_admin_cand_h($loadError); ?></section>
    <?php elseif (empty($contexts)): ?>
        <section class="readonly-empty-state">
            <h2>No candidatures found</h2>
            <p>Try another filter or check back later.</p>
        </section>
    <?php else: ?>
        <section class="readonly-grid" aria-label="Read-only candidatures">
            <?php foreach ($contexts as $context): ?>
                <?php
                $candidature = $context['condidature'];
                $source = $context['source'] ?? [];
                $creator = $context['creator'] ?? [];
                $brand = $context['brand'] ?? [];
                $status = $candidature->getStatutCandidature();
                ?>
                <article class="readonly-card">
                    <div class="readonly-card-head">
                        <span class="readonly-badge status-<?php echo cre8_admin_cand_h($status); ?>"><?php echo cre8_admin_cand_h(cre8_admin_candidature_status_label($status)); ?></span>
                        <span class="readonly-origin"><?php echo cre8_admin_cand_h(cre8_admin_candidature_origin_label($candidature->getOrigineCandidature())); ?></span>
                    </div>
                    <h2><?php echo cre8_admin_cand_h($source['title'] ?? ('Candidature #' . $candidature->getIdCandidature())); ?></h2>
                    <p class="readonly-message"><?php echo cre8_admin_cand_h(cre8_admin_candidature_excerpt($candidature->getMessageMotivation())); ?></p>
                    <div class="readonly-meta">
                        <span><strong>Creator</strong><?php echo cre8_admin_cand_h($creator['nom'] ?? 'Unknown creator'); ?></span>
                        <span><strong>Brand</strong><?php echo cre8_admin_cand_h($brand['nom'] ?? 'Unknown brand'); ?></span>
                        <span><strong>Budget</strong><?php echo cre8_admin_cand_h(cre8_admin_candidature_money($candidature->getBudgetPropose())); ?></span>
                        <span><strong>Date</strong><?php echo cre8_admin_cand_h($candidature->getDateCandidature() ?: 'Unknown'); ?></span>
                    </div>
                    <button class="readonly-detail-btn" type="button" data-admin-candidature-details="<?php echo (int) $candidature->getIdCandidature(); ?>">
                        View details
                    </button>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
    <?php
    return trim(ob_get_clean());
}

function cre8_admin_candidature_render_detail(?array $context): string
{
    if (!$context) {
        return '<div class="readonly-modal-error">Candidature details could not be found.</div>';
    }

    $candidature = $context['condidature'];
    $source = $context['source'] ?? [];
    $creator = $context['creator'] ?? [];
    $brand = $context['brand'] ?? [];
    $history = $context['negotiation']['history'] ?? [];

    ob_start();
    ?>
    <div class="readonly-detail">
        <div class="readonly-detail-hero">
            <span class="readonly-badge status-<?php echo cre8_admin_cand_h($candidature->getStatutCandidature()); ?>"><?php echo cre8_admin_cand_h(cre8_admin_candidature_status_label($candidature->getStatutCandidature())); ?></span>
            <h2><?php echo cre8_admin_cand_h($source['title'] ?? ('Candidature #' . $candidature->getIdCandidature())); ?></h2>
            <p><?php echo cre8_admin_cand_h($source['objective'] ?? ''); ?></p>
        </div>

        <div class="readonly-detail-grid">
            <span><strong>Creator</strong><?php echo cre8_admin_cand_h($creator['nom'] ?? 'Unknown creator'); ?></span>
            <span><strong>Brand</strong><?php echo cre8_admin_cand_h($brand['nom'] ?? 'Unknown brand'); ?></span>
            <span><strong>Origin</strong><?php echo cre8_admin_cand_h(cre8_admin_candidature_origin_label($candidature->getOrigineCandidature())); ?></span>
            <span><strong>Proposed budget</strong><?php echo cre8_admin_cand_h(cre8_admin_candidature_money($candidature->getBudgetPropose())); ?></span>
            <span><strong>Proposed delay</strong><?php echo cre8_admin_cand_h(cre8_admin_candidature_delay($candidature->getDelaiPropose())); ?></span>
            <span><strong>Candidature date</strong><?php echo cre8_admin_cand_h($candidature->getDateCandidature() ?: 'Unknown'); ?></span>
            <span><strong>Source status</strong><?php echo cre8_admin_cand_h($source['status'] ?? 'Unknown'); ?></span>
            <span><strong>Source budget</strong><?php echo cre8_admin_cand_h(cre8_admin_candidature_money($source['budgetPropose'] ?? null)); ?></span>
        </div>

        <section class="readonly-detail-block">
            <h3>Candidature message</h3>
            <p><?php echo nl2br(cre8_admin_cand_h($candidature->getMessageMotivation() ?: 'No candidature message.')); ?></p>
        </section>

        <section class="readonly-detail-block">
            <h3>Related <?php echo cre8_admin_cand_h(cre8_admin_candidature_origin_label($candidature->getOrigineCandidature())); ?></h3>
            <p><?php echo nl2br(cre8_admin_cand_h($source['description'] ?? 'No related source description.')); ?></p>
        </section>

        <section class="readonly-detail-block">
            <h3>Negotiation history</h3>
            <?php if (empty($history)): ?>
                <p class="readonly-muted">No negotiation history yet.</p>
            <?php else: ?>
                <div class="readonly-timeline">
                    <?php foreach ($history as $entry): ?>
                        <article class="readonly-timeline-entry">
                            <div class="readonly-timeline-head">
                                <strong><?php echo cre8_admin_cand_h(($entry['authorRoleLabel'] ?? 'User') . ' - ' . ($entry['authorName'] ?? 'Unknown')); ?></strong>
                                <span><?php echo cre8_admin_cand_h($entry['dateMessage'] ?? ''); ?></span>
                            </div>
                            <p><?php echo nl2br(cre8_admin_cand_h($entry['message'] ?? '')); ?></p>
                            <div class="readonly-timeline-terms">
                                <span>Budget: <?php echo cre8_admin_cand_h(cre8_admin_candidature_money($entry['budgetPropose'] ?? null)); ?></span>
                                <span>Delay: <?php echo cre8_admin_cand_h(cre8_admin_candidature_delay($entry['delaiPropose'] ?? null)); ?></span>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
    <?php
    return trim(ob_get_clean());
}

function cre8_admin_candidature_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

$filters = cre8_admin_candidature_filters();

if ($isAjax && ($_GET['ajax'] ?? '') === 'detail') {
    $id = (int) ($_GET['id'] ?? 0);
    if ($id <= 0) {
        cre8_admin_candidature_json(['success' => false, 'message' => 'Invalid candidature id.'], 400);
    }

    try {
        $context = $controller->getAdminCandidatureById($id);
        cre8_admin_candidature_json([
            'success' => (bool) $context,
            'html' => cre8_admin_candidature_render_detail($context),
        ], $context ? 200 : 404);
    } catch (Throwable $e) {
        cre8_admin_candidature_json(['success' => false, 'message' => 'Details could not be loaded right now.'], 500);
    }
}

[$contexts, $loadError] = cre8_admin_candidature_load($controller, $filters);

if ($isAjax && ($_GET['ajax'] ?? '') === 'filter') {
    cre8_admin_candidature_json([
        'success' => true,
        'html' => cre8_admin_candidature_render_cards($contexts, $loadError),
        'count' => count($contexts),
    ]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/../layout/front-theme-bootstrap.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidatures - Cre8Connect</title>
    <link rel="stylesheet" href="../layout/front-header.css">
    <link rel="icon" type="image/png" sizes="16x16" href="../../public/images/favicon-16.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../../public/images/favicon-32.png">
    <link rel="shortcut icon" type="image/png" href="../../public/images/favicon-32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../../public/images/apple-touch-icon.png">
    <style>
        .admin-candidature-readonly {
            max-width: 1180px;
            margin: 0 auto;
            padding: 2rem 1rem 3rem;
        }

        .readonly-hero,
        .readonly-filter-panel,
        .readonly-card,
        .readonly-empty-state,
        .readonly-modal-panel {
            background: var(--white, #fff);
            border: 1px solid var(--border, #e5e7eb);
            box-shadow: 0 14px 34px rgba(15, 14, 26, 0.07);
        }

        .readonly-hero {
            border-radius: 22px;
            padding: 1.6rem;
        }

        .readonly-eyebrow {
            display: inline-flex;
            width: max-content;
            border-radius: 999px;
            padding: .32rem .72rem;
            background: rgba(91, 79, 255, .12);
            color: var(--primary, #5b4fff);
            font-size: .78rem;
            font-weight: 900;
        }

        .readonly-hero h1 {
            margin: .7rem 0 .35rem;
            color: var(--text-main, #111827);
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 900;
        }

        .readonly-hero p,
        .readonly-muted {
            color: var(--text-sub, #64748b);
        }

        .readonly-filter-panel {
            margin-top: 1rem;
            border-radius: 18px;
            padding: 1rem;
        }

        .readonly-filter-form {
            display: grid;
            grid-template-columns: minmax(220px, 1.4fr) minmax(180px, .8fr);
            gap: .9rem;
            align-items: end;
        }

        .readonly-filter-field {
            display: grid;
            gap: .4rem;
            color: var(--text-main, #111827);
            font-size: .86rem;
            font-weight: 850;
        }

        .readonly-filter-field input,
        .readonly-filter-field select {
            width: 100%;
            border: 1px solid var(--border, #e5e7eb);
            border-radius: 12px;
            padding: .72rem .82rem;
            background: var(--white, #fff);
            color: var(--text-main, #111827);
        }

        .readonly-origin-wrap {
            grid-column: 1 / -1;
            display: grid;
            gap: .45rem;
        }

        .readonly-origin-title {
            color: var(--text-main, #111827);
            font-size: .86rem;
            font-weight: 850;
        }

        .readonly-origin-group {
            display: flex;
            flex-wrap: wrap;
            gap: .55rem;
        }

        .readonly-origin-chip input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .readonly-origin-chip span {
            display: inline-flex;
            min-height: 40px;
            align-items: center;
            border: 1px solid var(--border, #e5e7eb);
            border-radius: 999px;
            padding: .55rem .9rem;
            background: rgba(91, 79, 255, .05);
            color: var(--text-main, #111827);
            font-size: .86rem;
            font-weight: 850;
            cursor: pointer;
        }

        .readonly-origin-chip input:checked + span {
            border-color: rgba(91, 79, 255, .55);
            background: rgba(91, 79, 255, .14);
            color: var(--primary, #5b4fff);
        }

        .readonly-filter-actions {
            grid-column: 1 / -1;
            display: flex;
            flex-wrap: wrap;
            gap: .65rem;
            align-items: center;
        }

        .readonly-primary-btn,
        .readonly-secondary-btn,
        .readonly-detail-btn {
            border: 0;
            border-radius: 999px;
            padding: .68rem 1rem;
            font-weight: 900;
            cursor: pointer;
            text-decoration: none;
        }

        .readonly-primary-btn,
        .readonly-detail-btn {
            background: linear-gradient(135deg, #5b4fff, #8b5cf6);
            color: #fff;
        }

        .readonly-secondary-btn {
            border: 1px solid var(--border, #e5e7eb);
            background: transparent;
            color: var(--text-main, #111827);
        }

        .readonly-results {
            margin-top: 1rem;
        }

        .readonly-results.is-loading {
            opacity: .6;
            pointer-events: none;
        }

        .readonly-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
            gap: 1rem;
        }

        .readonly-card,
        .readonly-empty-state {
            border-radius: 16px;
            padding: 1rem;
        }

        .readonly-card {
            display: grid;
            gap: .8rem;
        }

        .readonly-card-head {
            display: flex;
            justify-content: space-between;
            gap: .6rem;
            align-items: center;
        }

        .readonly-card h2 {
            margin: 0;
            color: var(--text-main, #111827);
            font-size: 1.02rem;
            font-weight: 900;
            line-height: 1.3;
        }

        .readonly-message,
        .readonly-meta {
            color: var(--text-sub, #64748b);
            font-size: .88rem;
        }

        .readonly-meta {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .5rem;
        }

        .readonly-meta span,
        .readonly-detail-grid span {
            display: grid;
            gap: .1rem;
            min-width: 0;
        }

        .readonly-meta strong,
        .readonly-detail-grid strong {
            color: var(--text-main, #111827);
            font-size: .72rem;
            text-transform: uppercase;
        }

        .readonly-badge,
        .readonly-origin {
            display: inline-flex;
            width: max-content;
            border-radius: 999px;
            padding: .25rem .65rem;
            background: rgba(91, 79, 255, .12);
            color: var(--primary, #5b4fff);
            font-weight: 900;
            font-size: .74rem;
        }

        .readonly-origin {
            background: rgba(14, 165, 233, .1);
            color: #0369a1;
        }

        .readonly-detail-btn {
            width: max-content;
            justify-self: start;
        }

        .readonly-empty-state {
            text-align: center;
            color: var(--text-sub, #64748b);
        }

        .readonly-modal {
            position: fixed;
            inset: 0;
            z-index: 1200;
            display: grid;
            place-items: center;
            padding: 1rem;
        }

        .readonly-modal[hidden] {
            display: none;
        }

        .readonly-modal-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 14, 26, .58);
        }

        .readonly-modal-panel {
            position: relative;
            z-index: 1;
            width: min(860px, 100%);
            max-height: min(760px, 92vh);
            overflow-y: auto;
            border-radius: 20px;
            padding: 1.2rem;
        }

        .readonly-modal-head {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            align-items: center;
            margin-bottom: 1rem;
        }

        .readonly-modal-close {
            border: 0;
            border-radius: 50%;
            width: 38px;
            height: 38px;
            background: rgba(91, 79, 255, .12);
            color: var(--primary, #5b4fff);
            font-size: 1.3rem;
            line-height: 1;
            cursor: pointer;
        }

        .readonly-detail {
            display: grid;
            gap: 1rem;
        }

        .readonly-detail-hero h2 {
            margin: .55rem 0 .25rem;
            color: var(--text-main, #111827);
            font-weight: 900;
        }

        .readonly-detail-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .8rem;
        }

        .readonly-detail-block {
            border-top: 1px solid var(--border, #e5e7eb);
            padding-top: 1rem;
        }

        .readonly-detail-block h3 {
            font-size: 1rem;
            font-weight: 900;
            color: var(--text-main, #111827);
        }

        .readonly-timeline {
            display: grid;
            gap: .75rem;
        }

        .readonly-timeline-entry {
            border: 1px solid var(--border, #e5e7eb);
            border-radius: 14px;
            padding: .9rem;
        }

        .readonly-timeline-head,
        .readonly-timeline-terms {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: .55rem;
            color: var(--text-sub, #64748b);
            font-size: .84rem;
        }

        html[data-theme="dark"] .readonly-hero,
        html[data-theme="dark"] .readonly-filter-panel,
        html[data-theme="dark"] .readonly-card,
        html[data-theme="dark"] .readonly-empty-state,
        html[data-theme="dark"] .readonly-modal-panel,
        body.dark-mode .readonly-hero,
        body.dark-mode .readonly-filter-panel,
        body.dark-mode .readonly-card,
        body.dark-mode .readonly-empty-state,
        body.dark-mode .readonly-modal-panel {
            background: var(--white, #1c1a2e);
            border-color: var(--border, #2a2840);
        }

        html[data-theme="dark"] .readonly-filter-field input,
        html[data-theme="dark"] .readonly-filter-field select,
        body.dark-mode .readonly-filter-field input,
        body.dark-mode .readonly-filter-field select {
            background: #151427;
            color: var(--text-main, #e8e6f5);
            border-color: var(--border, #2a2840);
        }

        @media (max-width: 760px) {
            .readonly-filter-form,
            .readonly-detail-grid,
            .readonly-meta {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<?php require_once __DIR__ . '/../layout/header.php'; ?>
<main class="admin-candidature-readonly">
    <section class="readonly-hero">
        <span class="readonly-eyebrow">Admin Visitor</span>
        <h1>Candidatures</h1>
        <p>Read-only overview of platform candidatures. Admin actions stay in BackOffice.</p>
    </section>

    <section class="readonly-filter-panel" aria-label="Candidature filters">
        <form method="get" class="readonly-filter-form" data-admin-candidature-filters>
            <label class="readonly-filter-field">
                Keyword
                <input type="search" name="keyword" value="<?php echo cre8_admin_cand_h($filters['keyword']); ?>" placeholder="Creator, brand, source, message...">
            </label>
            <label class="readonly-filter-field">
                Status
                <select name="status">
                    <option value="">All statuses</option>
                    <?php foreach (['brouillon', 'envoyee', 'en_etude', 'negociation', 'acceptee', 'refusee', 'retiree'] as $status): ?>
                        <option value="<?php echo cre8_admin_cand_h($status); ?>"<?php echo $filters['status'] === $status ? ' selected' : ''; ?>><?php echo cre8_admin_cand_h(cre8_admin_candidature_status_label($status)); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <div class="readonly-origin-wrap">
                <span class="readonly-origin-title">Origin</span>
                <div class="readonly-origin-group" role="radiogroup" aria-label="Origin">
                    <?php foreach (['' => 'All origins', 'par_offre' => 'Offer', 'par_campagne' => 'Campaign'] as $originValue => $originLabel): ?>
                        <label class="readonly-origin-chip">
                            <input type="radio" name="origin" value="<?php echo cre8_admin_cand_h($originValue); ?>"<?php echo $filters['origin'] === $originValue ? ' checked' : ''; ?>>
                            <span><?php echo cre8_admin_cand_h($originLabel); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="readonly-filter-actions">
                <button class="readonly-primary-btn" type="submit">Apply filters</button>
                <a class="readonly-secondary-btn" href="admin_readonly.php" data-admin-candidature-reset>Reset</a>
                <span class="readonly-muted" data-admin-candidature-count><?php echo (int) count($contexts); ?> shown</span>
            </div>
        </form>
    </section>

    <div class="readonly-results" data-admin-candidature-results>
        <?php echo cre8_admin_candidature_render_cards($contexts, $loadError); ?>
    </div>
</main>

<div class="readonly-modal" data-admin-candidature-modal hidden>
    <div class="readonly-modal-backdrop" data-admin-candidature-close></div>
    <section class="readonly-modal-panel" role="dialog" aria-modal="true" aria-labelledby="readonlyCandidatureTitle">
        <div class="readonly-modal-head">
            <h2 id="readonlyCandidatureTitle">Candidature details</h2>
            <button type="button" class="readonly-modal-close" data-admin-candidature-close aria-label="Close">&times;</button>
        </div>
        <div data-admin-candidature-modal-body></div>
    </section>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
<script src="../layout/front-header.js"></script>
<script>
(function () {
    var form = document.querySelector('[data-admin-candidature-filters]');
    var results = document.querySelector('[data-admin-candidature-results]');
    var count = document.querySelector('[data-admin-candidature-count]');
    var modal = document.querySelector('[data-admin-candidature-modal]');
    var modalBody = document.querySelector('[data-admin-candidature-modal-body]');

    function parseJsonResponse(response) {
        return response.text().then(function (text) {
            try {
                return JSON.parse(text);
            } catch (error) {
                throw new Error('The server returned an invalid response.');
            }
        });
    }

    function filterUrl(extra) {
        var params = new URLSearchParams(new FormData(form));
        Object.keys(extra || {}).forEach(function (key) {
            params.set(key, extra[key]);
        });
        return 'admin_readonly.php?' + params.toString();
    }

    function loadFilters(event) {
        if (event) {
            event.preventDefault();
        }
        if (!form || !results) {
            return;
        }

        results.classList.add('is-loading');
        fetch(filterUrl({ ajax: 'filter' }), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        })
            .then(parseJsonResponse)
            .then(function (payload) {
                if (!payload.success) {
                    throw new Error(payload.message || 'Filters could not be applied.');
                }
                results.innerHTML = payload.html || '';
                if (count) {
                    count.textContent = String(payload.count || 0) + ' shown';
                }
                var pageParams = new URLSearchParams(new FormData(form));
                window.history.replaceState({}, '', 'admin_readonly.php?' + pageParams.toString());
            })
            .catch(function (error) {
                results.innerHTML = '<section class="readonly-empty-state">' + error.message + '</section>';
            })
            .finally(function () {
                results.classList.remove('is-loading');
            });
    }

    function openModal(html) {
        if (!modal || !modalBody) {
            return;
        }
        modalBody.innerHTML = html || '';
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        if (!modal || !modalBody) {
            return;
        }
        modal.hidden = true;
        modalBody.innerHTML = '';
        document.body.style.overflow = '';
    }

    if (form && results && window.fetch) {
        form.addEventListener('submit', loadFilters);
        form.querySelectorAll('input[name="origin"]').forEach(function (input) {
            input.addEventListener('change', loadFilters);
        });
    }

    document.addEventListener('click', function (event) {
        var detailButton = event.target.closest('[data-admin-candidature-details]');
        if (detailButton) {
            event.preventDefault();
            detailButton.disabled = true;
            fetch('admin_readonly.php?ajax=detail&id=' + encodeURIComponent(detailButton.getAttribute('data-admin-candidature-details')), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            })
                .then(parseJsonResponse)
                .then(function (payload) {
                    if (!payload.success) {
                        throw new Error(payload.message || 'Details could not be loaded.');
                    }
                    openModal(payload.html || '');
                })
                .catch(function (error) {
                    openModal('<div class="readonly-modal-error">' + error.message + '</div>');
                })
                .finally(function () {
                    detailButton.disabled = false;
                });
            return;
        }

        if (event.target.closest('[data-admin-candidature-close]')) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal && !modal.hidden) {
            closeModal();
        }
    });
})();
</script>
</body>
</html>
