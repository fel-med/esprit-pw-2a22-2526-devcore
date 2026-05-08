<?php
session_start();

require_once __DIR__ . '/../../../Controleur/condidatureC.php';

$controller = new CondidatureC();
$sessionUser = $_SESSION['utilisateur'] ?? [];

if (!isset($sessionUser['id']) || (($sessionUser['role'] ?? '') !== 'admin')) {
    $defaultAdmin = $controller->getDefaultUserByRole('admin');
    if ($defaultAdmin) {
        $_SESSION['utilisateur'] = [
            'id' => (int) $defaultAdmin['id'],
            'role' => 'admin',
            'nom' => $defaultAdmin['nom'],
            'email' => $defaultAdmin['email'],
        ];
        $sessionUser = $_SESSION['utilisateur'];
    }
}

$idCandidature = isset($_GET['idCandidature']) && is_numeric($_GET['idCandidature']) ? (int) $_GET['idCandidature'] : null;
$notice = trim((string) ($_GET['notice'] ?? ''));
$noticeType = trim((string) ($_GET['noticeType'] ?? 'success'));
$error = null;
$errors = [];
$context = null;

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

function formatDateLabel($value, $fallback = 'Not available')
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

if ($idCandidature !== null) {
    try {
        $context = $controller->getAdminCandidatureById($idCandidature);
        if (!$context) {
            $error = 'This candidature could not be found.';
        }
    } catch (Throwable $exception) {
        $error = 'The candidature review page could not be loaded right now.';
    }
} else {
    $error = 'Choose a candidature from the admin list before opening the review page.';
}

$condidature = $context['condidature'] ?? null;
$creator = $context['creator'] ?? ['nom' => '', 'email' => ''];
$brand = $context['brand'] ?? ['nom' => '', 'email' => ''];
$source = $context['source'] ?? ['origin' => 'par_offre', 'title' => '', 'objective' => '', 'description' => '', 'budgetPropose' => 0];
$negotiation = $context['negotiation'] ?? ['count' => 0, 'latest' => null, 'history' => []];

$form = [
    'reviewStatus' => $condidature ? $condidature->getStatutCandidature() : 'en_etude',
    'noteDecision' => $condidature ? (string) $condidature->getNoteDecision() : '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error && $condidature) {
    $form = [
        'reviewStatus' => trim((string) ($_POST['reviewStatus'] ?? '')),
        'noteDecision' => trim((string) ($_POST['noteDecision'] ?? '')),
    ];

    $result = $controller->reviewCandidature($condidature->getIdCandidature(), $form);
    if (!empty($result['success'])) {
        $statusLabel = translateCandidatureStatus($form['reviewStatus']);
        header('Location: details.php?idCandidature=' . (int) $condidature->getIdCandidature() . '&notice=' . urlencode('Candidature moved to ' . $statusLabel . '.') . '&noticeType=success');
        exit;
    }

    $errors = $result['errors'] ?? ['Unable to save the admin review right now.'];
    $context = $result['context'] ?? $context;
    $condidature = $context['condidature'] ?? $condidature;
    $creator = $context['creator'] ?? $creator;
    $brand = $context['brand'] ?? $brand;
    $source = $context['source'] ?? $source;
}

$canReview = $condidature && $condidature->getStatutCandidature() !== 'retiree';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Back Office - Candidature Review</title>
    <link rel="stylesheet" href="../css/backoffice.css">
    <link rel="stylesheet" href="../css/new_style_backoffice.css">
    <link rel="stylesheet" href="../offre/offre-admin.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../offre/offre-admin.css')); ?>">
    <link rel="stylesheet" href="condidature-admin.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/condidature-admin.css')); ?>">
</head>
<body class="cre8-admin-layout">
    <div class="container-scroller cre8-admin-page">
        <?php require_once dirname(__DIR__) . '/layout/sidebar.php'; ?>
        <main class="page-body-wrapper cre8-admin-main">
            <?php require_once dirname(__DIR__) . '/layout/header.php'; ?>
    <div class="main-panel">
    <div class="content-wrapper admin-shell">
        <header class="admin-header card grid-margin">
            <div class="admin-header-main card-body">
                <div>
                    <h1>Candidature review</h1>
                    <p>Inspect the creator response, compare it to the targeted offer context, and record the current review outcome clearly.</p>
                </div>
            </div>
        </header>

        <?php if ($notice !== ''): ?>
            <div class="admin-flash <?php echo $noticeType === 'danger' ? 'error' : 'success'; ?>"><?php echo htmlspecialchars($notice); ?></div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="admin-flash error">
                <strong>Unable to save the review.</strong>
                <ul style="margin: 0.6rem 0 0; padding-left: 1.1rem;">
                    <?php foreach ($errors as $item): ?>
                        <li><?php echo htmlspecialchars($item); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <section class="admin-panel admin-table-panel card grid-margin">
                <div class="admin-panel-body card-body">
                    <div class="detail-empty-state">
                        <span class="detail-empty-icon">!</span>
                        <h4>Candidature unavailable</h4>
                        <p><?php echo htmlspecialchars($error); ?></p>
                        <div class="search-actions" style="margin-top: 1rem;">
                            <a class="btn btn-secondary clear-link" href="index.php">Back to candidature list</a>
                        </div>
                    </div>
                </div>
            </section>
        <?php else: ?>
            <div class="candidature-admin-grid">
                <section class="admin-panel admin-table-panel card grid-margin">
                    <div class="admin-panel-header">
                        <h2 class="card-title">Review snapshot</h2>
                    </div>
                    <div class="admin-panel-body card-body">
                        <div class="quick-overview-card">
                            <div class="quick-overview-top">
                                <span class="quick-overview-tag"><?php echo htmlspecialchars(translateOrigin($source['origin'])); ?></span>
                                <span class="badge badge-status <?php echo htmlspecialchars($condidature->getStatutCandidature()); ?>"><?php echo htmlspecialchars($condidature->getDisplayStatusLabel()); ?></span>
                            </div>
                            <h3><?php echo htmlspecialchars($source['title']); ?></h3>
                            <p class="quick-overview-description"><?php echo htmlspecialchars($source['description'] ?: 'No source description was attached to this candidature.'); ?></p>
                            <div class="quick-overview-meta">
                                <span><?php echo htmlspecialchars(formatMoney($condidature->getBudgetPropose())); ?></span>
                                <span>Delay: <?php echo htmlspecialchars((string) $condidature->getDelaiPropose()); ?> days</span>
                                <span>Available: <?php echo htmlspecialchars(formatShortDate($condidature->getDateDisponibilite(), 'Not shared')); ?></span>
                                <span>Submitted: <?php echo htmlspecialchars(formatShortDate($condidature->getDateCandidature())); ?></span>
                                <span><?php echo htmlspecialchars($condidature->getResponseTypeLabel()); ?></span>
                                <span>Negotiation messages: <?php echo (int) ($negotiation['count'] ?? 0); ?></span>
                            </div>
                        </div>

                        <div class="candidature-review-grid" style="margin-top: 1rem;">
                            <section class="candidature-review-card">
                                <h3>Creator</h3>
                                <p><?php echo htmlspecialchars($creator['nom'] ?: 'Unknown creator'); ?><br><?php echo htmlspecialchars($creator['email']); ?></p>
                            </section>
                            <section class="candidature-review-card">
                                <h3>Brand</h3>
                                <p><?php echo htmlspecialchars($brand['nom'] ?: 'Unknown brand'); ?><br><?php echo htmlspecialchars($brand['email']); ?></p>
                            </section>
                            <section class="candidature-review-card">
                                <h3>Source objective</h3>
                                <p><?php echo htmlspecialchars($source['objective'] ?: 'No source objective was added.'); ?></p>
                            </section>
                            <section class="candidature-review-card">
                                <h3>Creator action</h3>
                                <p><?php echo htmlspecialchars($condidature->getResponseTypeLabel()); ?></p>
                            </section>
                            <section class="candidature-review-card">
                                <h3>Decision date</h3>
                                <p><?php echo htmlspecialchars(formatDateLabel($condidature->getDateDecision(), 'No final decision yet')); ?></p>
                            </section>
                            <section class="candidature-review-card">
                                <h3>Availability start date</h3>
                                <p><?php echo htmlspecialchars(formatShortDate($condidature->getDateDisponibilite(), 'Not shared yet')); ?></p>
                            </section>
                            <section class="candidature-review-card">
                                <h3>Delivery delay</h3>
                                <p><?php echo htmlspecialchars((string) $condidature->getDelaiPropose()); ?> day<?php echo (int) $condidature->getDelaiPropose() === 1 ? '' : 's'; ?></p>
                            </section>
                        </div>

                        <div class="candidature-decision-grid" style="margin-top: 1rem;">
                            <section class="candidature-message-card">
                                <h3>Creator message</h3>
                                <p><?php echo htmlspecialchars(trim((string) $condidature->getMessageMotivation()) !== '' ? $condidature->getMessageMotivation() : 'No creator message was submitted.'); ?></p>
                            </section>
                            <section class="candidature-message-card">
                                <h3>Decision note</h3>
                                <p><?php echo htmlspecialchars(trim((string) $condidature->getNoteDecision()) !== '' ? $condidature->getNoteDecision() : 'No admin decision note has been stored yet.'); ?></p>
                            </section>
                        </div>

                        <div class="candidature-decision-grid" style="margin-top: 1rem;">
                            <section class="candidature-message-card">
                                <h3>Creator terms</h3>
                                <p><?php echo htmlspecialchars(trim((string) $condidature->getConditionsCreateur()) !== '' ? $condidature->getConditionsCreateur() : 'No creator terms were attached to this candidature.'); ?></p>
                            </section>
                            <section class="candidature-message-card">
                                <h3>Refusal reason</h3>
                                <p><?php echo htmlspecialchars(trim((string) $condidature->getMotifRefus()) !== '' ? $condidature->getMotifRefus() : 'No refusal reason was attached to this candidature.'); ?></p>
                            </section>
                        </div>

                        <div class="candidature-decision-grid" style="margin-top: 1rem;">
                            <section class="candidature-message-card">
                                <h3>CV reference</h3>
                                <p>
                                    <?php if (trim((string) $condidature->getCvPath()) !== ''): ?>
                                        <a class="admin-inline-link" href="<?php echo htmlspecialchars(candidatureStoredFileHref($condidature->getCvPath())); ?>" target="_blank" rel="noopener noreferrer">
                                            <?php echo htmlspecialchars(candidatureStoredFileName($condidature->getCvPath()) ?: $condidature->getCvPath()); ?>
                                        </a>
                                    <?php else: ?>
                                        No CV/reference file was attached.
                                    <?php endif; ?>
                                </p>
                            </section>
                            <section class="candidature-message-card">
                                <h3>Portfolio URL</h3>
                                <p>
                                    <?php if (trim((string) $condidature->getPortfolioUrl()) !== ''): ?>
                                        <a class="admin-inline-link" href="<?php echo htmlspecialchars($condidature->getPortfolioUrl()); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($condidature->getPortfolioUrl()); ?></a>
                                    <?php else: ?>
                                        No portfolio URL was attached.
                                    <?php endif; ?>
                                </p>
                            </section>
                        </div>

                        <section class="candidature-message-card" style="margin-top: 1rem;">
                            <div class="admin-negotiation-header">
                                <div>
                                    <h3>Negotiation history</h3>
                                    <p class="admin-negotiation-subtitle">Read the full creator and brand exchange in chronological order.</p>
                                </div>
                                <span class="candidature-detail-pill"><?php echo (int) ($negotiation['count'] ?? 0); ?> message<?php echo (int) ($negotiation['count'] ?? 0) === 1 ? '' : 's'; ?></span>
                            </div>

                            <?php if (!empty($negotiation['history'])): ?>
                                <div class="admin-negotiation-thread">
                                    <?php foreach ($negotiation['history'] as $entry): ?>
                                        <article class="admin-negotiation-entry admin-negotiation-entry-<?php echo htmlspecialchars($entry['auteur']); ?>">
                                            <div class="admin-negotiation-top">
                                                <div class="admin-negotiation-author">
                                                    <span class="admin-negotiation-role admin-negotiation-role-<?php echo htmlspecialchars($entry['auteur']); ?>">
                                                        <?php echo htmlspecialchars($entry['authorRoleLabel']); ?>
                                                    </span>
                                                    <strong><?php echo htmlspecialchars($entry['authorName']); ?></strong>
                                                    <?php if (!empty($entry['authorEmail'])): ?>
                                                        <span><?php echo htmlspecialchars($entry['authorEmail']); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <span class="candidature-detail-pill"><?php echo htmlspecialchars(formatDateLabel($entry['dateMessage'] ?? null)); ?></span>
                                            </div>
                                            <p><?php echo htmlspecialchars($entry['message'] !== '' ? $entry['message'] : 'No message body was added to this negotiation step.'); ?></p>
                                            <?php if ($entry['budgetPropose'] !== null || $entry['delaiPropose'] !== null): ?>
                                                <div class="admin-negotiation-meta">
                                                    <?php if ($entry['budgetPropose'] !== null): ?>
                                                        <span class="candidature-detail-pill"><?php echo htmlspecialchars(formatMoney($entry['budgetPropose'])); ?></span>
                                                    <?php endif; ?>
                                                    <?php if ($entry['delaiPropose'] !== null): ?>
                                                        <span class="candidature-detail-pill">Timeline: <?php echo (int) $entry['delaiPropose']; ?> days</span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="detail-empty-state detail-empty-inline">
                                    <span class="detail-empty-icon">!</span>
                                    <h4>No negotiation history yet</h4>
                                    <p>The candidature is currently stored without negotiation messages.</p>
                                </div>
                            <?php endif; ?>
                        </section>
                    </div>
                </section>

                <aside class="admin-panel admin-details-panel card grid-margin">
                    <div class="admin-panel-header">
                        <h2 class="card-title">Review controls</h2>
                    </div>
                    <div class="admin-panel-body card-body">
                        <section class="candidature-status-card">
                            <h3>Current workflow state</h3>
                            <p>
                                Status: <?php echo htmlspecialchars($condidature->getDisplayStatusLabel()); ?><br>
                                Creator action: <?php echo htmlspecialchars($condidature->getResponseTypeLabel()); ?><br>
                                Availability: <?php echo htmlspecialchars(formatShortDate($condidature->getDateDisponibilite(), 'Not shared')); ?><br>
                                Last update: <?php echo htmlspecialchars(formatDateLabel($condidature->getDateDerniereModification())); ?><br>
                                Origin: <?php echo htmlspecialchars(translateOrigin($source['origin'])); ?>
                            </p>
                        </section>

                        <?php if ($canReview): ?>
                            <form method="post" class="candidature-review-form" style="margin-top: 1rem;">
                                <div class="search-group">
                                    <label for="reviewStatus">Review status</label>
                                    <select id="reviewStatus" name="reviewStatus" class="form-control">
                                        <?php foreach ($controller->getDecisionStatusOptions() as $status): ?>
                                            <option value="<?php echo htmlspecialchars($status); ?>"<?php echo $form['reviewStatus'] === $status ? ' selected' : ''; ?>>
                                                <?php echo htmlspecialchars(translateCandidatureStatus($status)); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="search-group">
                                    <label for="noteDecision">Decision note</label>
                                    <textarea id="noteDecision" name="noteDecision" rows="8" class="form-control" data-cre8pilot-field="noteDecision" placeholder="Explain the current review decision, next step, or the reason behind this outcome."><?php echo htmlspecialchars($form['noteDecision']); ?></textarea>
                                </div>

                                <div class="candidature-review-actions">
                                    <button type="submit" class="btn btn-primary">Save review</button>
                                    <a class="btn btn-secondary clear-link" href="index.php">Back to list</a>
                                </div>
                            </form>
                        <?php else: ?>
                            <section class="candidature-status-card" style="margin-top: 1rem;">
                                <h3>Review locked</h3>
                                <p>The creator already withdrew this candidature, so the admin review can stay visible but should not move it into a new decision state.</p>
                            </section>
                            <div class="candidature-review-actions" style="margin-top: 1rem;">
                                <a class="btn btn-secondary clear-link" href="index.php">Back to list</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </aside>
            </div>
        <?php endif; ?>
    </div>
    </div>
        </main>
    </div>
<?php
$cre8PilotContext = [
    'page' => 'admin_candidature_workspace',
    'mode' => 'details',
    'role' => 'admin',
    'allowedActions' => ['normal_chat', 'summarize_page', 'analyze_page', 'prepare_negotiation_reply'],
    'formTarget' => null,
    'visibleEntityType' => 'candidature',
    'visibleEntityId' => $idCandidature ?? null,
];
require __DIR__ . '/../../FrontOffice/condidature/cre8pilot_widget.php';
?>
</body>
</html>
