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

$notice = trim((string) ($_GET['notice'] ?? ''));
$noticeType = trim((string) ($_GET['noticeType'] ?? 'success'));
$error = null;

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
        'par_campagne' => 'Campaign response',
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

function excerptText($text, $length = 90)
{
    $text = trim((string) $text);
    if ($text === '') {
        return '';
    }

    if (strlen($text) <= $length) {
        return $text;
    }

    return rtrim(substr($text, 0, max(0, $length - 3))) . '...';
}

$filters = [
    'keyword' => trim((string) ($_GET['keyword'] ?? '')),
    'status' => trim((string) ($_GET['status'] ?? '')),
    'origin' => trim((string) ($_GET['origin'] ?? '')),
    'creatorId' => trim((string) ($_GET['creatorId'] ?? '')),
    'brandId' => trim((string) ($_GET['brandId'] ?? '')),
    'dateFrom' => trim((string) ($_GET['dateFrom'] ?? '')),
];

$contexts = [];
$summary = [
    'total' => 0,
    'brouillon' => 0,
    'envoyee' => 0,
    'en_etude' => 0,
    'negociation' => 0,
    'acceptee' => 0,
    'refusee' => 0,
    'retiree' => 0,
    'averageBudget' => 0,
];
$creatorOptions = $controller->getUsersByRole('createur');
$brandOptions = $controller->getUsersByRole('marque');

try {
    $contexts = $controller->getAdminCandidatures($filters);
    $summary = $controller->summarizeContexts($contexts);
} catch (Throwable $exception) {
    $error = 'The admin candidature dashboard could not be loaded right now.';
}

$awaitingReviewCount = (int) ($summary['envoyee'] ?? 0) + (int) ($summary['en_etude'] ?? 0);
$finalCount = (int) ($summary['acceptee'] ?? 0) + (int) ($summary['refusee'] ?? 0) + (int) ($summary['retiree'] ?? 0);
$activeFilterCount = count(array_filter($filters, static fn($value) => $value !== ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Back Office - Candidature Management</title>
    <link rel="stylesheet" href="../css/backoffice.css">
    <link rel="stylesheet" href="../offre/offre-admin.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../offre/offre-admin.css')); ?>">
    <link rel="stylesheet" href="condidature-admin.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/condidature-admin.css')); ?>">
</head>
<body class="cre8-admin-layout">
    <div class="cre8-admin-page">
        <?php require_once dirname(__DIR__) . '/layout/sidebar.php'; ?>
        <main class="cre8-admin-main">
            <?php require_once dirname(__DIR__) . '/layout/header.php'; ?>
    <div class="admin-shell">
        <header class="admin-header">
            <h1>Candidature administration</h1>
            <p>Inspect creator responses, follow review stages, and keep every targeted candidature visible from the same dashboard.</p>
        </header>

        <?php if ($notice !== ''): ?>
            <div class="admin-flash <?php echo $noticeType === 'danger' ? 'error' : 'success'; ?>"><?php echo htmlspecialchars($notice); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="admin-flash error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <details class="search-panel" <?php echo $activeFilterCount > 0 ? 'open' : ''; ?>>
            <summary class="search-panel-summary">
                <span class="search-panel-heading">
                    <span class="search-panel-title">Admin filters</span>
                    <span class="search-panel-subtitle">
                        <?php echo $activeFilterCount > 0 ? $activeFilterCount . ' filter' . ($activeFilterCount > 1 ? 's' : '') . ' applied to this candidature view.' : 'Filter by source, creator, brand, date, or workflow state.'; ?>
                    </span>
                </span>
                <span class="search-panel-status">
                    <?php if ($activeFilterCount > 0): ?>
                        <span class="search-panel-badge"><?php echo $activeFilterCount; ?> active</span>
                    <?php endif; ?>
                    <span class="search-panel-toggle">
                        <span class="search-panel-toggle-label search-panel-toggle-label-closed">Open filters</span>
                        <span class="search-panel-toggle-label search-panel-toggle-label-open">Close filters</span>
                        <span class="search-panel-toggle-icon" aria-hidden="true"></span>
                    </span>
                </span>
            </summary>

            <form method="get" class="search-form">
                <div class="search-grid">
                    <div class="search-group">
                        <label for="keyword">Search</label>
                        <input id="keyword" name="keyword" type="search" value="<?php echo htmlspecialchars($filters['keyword']); ?>" placeholder="Source, creator, brand, message...">
                    </div>

                    <div class="search-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="">All</option>
                            <option value="brouillon"<?php echo $filters['status'] === 'brouillon' ? ' selected' : ''; ?>>Draft</option>
                            <option value="envoyee"<?php echo $filters['status'] === 'envoyee' ? ' selected' : ''; ?>>Sent</option>
                            <option value="en_etude"<?php echo $filters['status'] === 'en_etude' ? ' selected' : ''; ?>>Under review</option>
                            <option value="negociation"<?php echo $filters['status'] === 'negociation' ? ' selected' : ''; ?>>Negotiation</option>
                            <option value="acceptee"<?php echo $filters['status'] === 'acceptee' ? ' selected' : ''; ?>>Accepted</option>
                            <option value="refusee"<?php echo $filters['status'] === 'refusee' ? ' selected' : ''; ?>>Refused</option>
                            <option value="retiree"<?php echo $filters['status'] === 'retiree' ? ' selected' : ''; ?>>Withdrawn</option>
                        </select>
                    </div>

                    <div class="search-group">
                        <label for="origin">Origin</label>
                        <select id="origin" name="origin">
                            <option value="">All</option>
                            <option value="par_offre"<?php echo $filters['origin'] === 'par_offre' ? ' selected' : ''; ?>>Offer invitation</option>
                            <option value="par_campagne"<?php echo $filters['origin'] === 'par_campagne' ? ' selected' : ''; ?>>Campaign response</option>
                        </select>
                    </div>

                    <div class="search-group">
                        <label for="creatorId">Creator</label>
                        <select id="creatorId" name="creatorId">
                            <option value="">All</option>
                            <?php foreach ($creatorOptions as $creator): ?>
                                <option value="<?php echo (int) $creator['id']; ?>"<?php echo $filters['creatorId'] === (string) $creator['id'] ? ' selected' : ''; ?>>
                                    <?php echo htmlspecialchars($creator['nom'] . ' (#' . $creator['id'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="search-group">
                        <label for="brandId">Brand</label>
                        <select id="brandId" name="brandId">
                            <option value="">All</option>
                            <?php foreach ($brandOptions as $brand): ?>
                                <option value="<?php echo (int) $brand['id']; ?>"<?php echo $filters['brandId'] === (string) $brand['id'] ? ' selected' : ''; ?>>
                                    <?php echo htmlspecialchars($brand['nom'] . ' (#' . $brand['id'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="search-group">
                        <label for="dateFrom">Submitted from</label>
                        <input id="dateFrom" name="dateFrom" type="date" value="<?php echo htmlspecialchars($filters['dateFrom']); ?>">
                    </div>
                </div>

                <div class="search-actions">
                    <button type="submit">Apply filters</button>
                    <a class="clear-link" href="index.php">Reset</a>
                </div>
            </form>
        </details>

        <section class="admin-summary">
            <article class="admin-card">
                <h3>Candidatures in view</h3>
                <p><?php echo (int) ($summary['total'] ?? 0); ?></p>
                <small>Current filtered result set</small>
            </article>
            <article class="admin-card">
                <h3>Draft responses</h3>
                <p><?php echo (int) ($summary['brouillon'] ?? 0); ?></p>
                <small>Saved but not submitted yet</small>
            </article>
            <article class="admin-card">
                <h3>Waiting review</h3>
                <p><?php echo $awaitingReviewCount; ?></p>
                <small>Submitted creator responses currently moving through review</small>
            </article>
            <article class="admin-card">
                <h3>Negotiation requests</h3>
                <p><?php echo (int) ($summary['negociation'] ?? 0); ?></p>
                <small><?php echo (int) ($summary['negotiationMessages'] ?? 0); ?> stored message<?php echo (int) ($summary['negotiationMessages'] ?? 0) === 1 ? '' : 's'; ?></small>
            </article>
            <article class="admin-card">
                <h3>Average budget</h3>
                <p><?php echo (int) ($summary['total'] ?? 0) > 0 ? htmlspecialchars(formatMoney($summary['averageBudget'] ?? 0)) : 'EUR 0.00'; ?></p>
                <small><?php echo $finalCount; ?> final outcome<?php echo $finalCount === 1 ? '' : 's'; ?></small>
            </article>
        </section>

        <div class="admin-layout">
            <section class="admin-panel admin-table-panel">
                <div class="admin-panel-header">
                    <h2>Candidature list</h2>
                </div>
                <div class="admin-panel-body">
                    <div class="admin-table-wrapper">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Creator</th>
                                    <th>Source</th>
                                    <th>Origin</th>
                                    <th>Creator action</th>
                                    <th>Status</th>
                                    <th>Submitted</th>
                                    <th>Budget</th>
                                    <th>Updated</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($contexts)): ?>
                                    <tr>
                                        <td colspan="9" style="padding: 1.5rem; text-align: center; color: #94a3b8;">No candidatures match the current filters.</td>
                                    </tr>
                                <?php endif; ?>

                                <?php foreach ($contexts as $context): ?>
                                    <?php
                                    $condidature = $context['condidature'];
                                    $creator = $context['creator'];
                                    $source = $context['source'];
                                    $brand = $context['brand'];
                                    ?>
                                    <tr>
                                        <td class="candidature-table-person">
                                            <strong><?php echo htmlspecialchars($creator['nom'] ?: ('Creator #' . $creator['id'])); ?></strong>
                                            <span><?php echo htmlspecialchars($creator['email']); ?></span>
                                        </td>
                                        <td class="candidature-table-source">
                                            <strong title="<?php echo htmlspecialchars($source['title']); ?>"><?php echo htmlspecialchars(excerptText($source['title'], 40)); ?></strong>
                                            <span><?php echo htmlspecialchars($brand['nom'] ?: 'Unknown brand'); ?></span>
                                        </td>
                                        <td><span class="candidature-origin-pill"><?php echo htmlspecialchars(translateOrigin($source['origin'])); ?></span></td>
                                        <td class="candidature-table-response">
                                            <span class="candidature-detail-pill"><?php echo htmlspecialchars($condidature->getResponseTypeLabel()); ?></span>
                                            <?php if ($condidature->getDateDisponibilite()): ?>
                                                <small>Available: <?php echo htmlspecialchars(formatShortDate($condidature->getDateDisponibilite())); ?></small>
                                            <?php endif; ?>
                                            <?php if ($condidature->getResponseMode() === 'decline' && trim((string) $condidature->getMotifRefus()) !== ''): ?>
                                                <small title="<?php echo htmlspecialchars($condidature->getMotifRefus()); ?>">
                                                    <?php echo htmlspecialchars(excerptText($condidature->getMotifRefus(), 54)); ?>
                                                </small>
                                            <?php elseif (trim((string) $condidature->getConditionsCreateur()) !== ''): ?>
                                                <small title="<?php echo htmlspecialchars($condidature->getConditionsCreateur()); ?>">
                                                    <?php echo htmlspecialchars(excerptText($condidature->getConditionsCreateur(), 54)); ?>
                                                </small>
                                            <?php endif; ?>
                                            <?php if ((int) ($context['negotiation']['count'] ?? 0) > 0): ?>
                                                <small><?php echo (int) $context['negotiation']['count']; ?> negotiation message<?php echo (int) $context['negotiation']['count'] === 1 ? '' : 's'; ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge-status <?php echo htmlspecialchars($condidature->getStatutCandidature()); ?>"><?php echo htmlspecialchars($condidature->getDisplayStatusLabel()); ?></span></td>
                                        <td><?php echo htmlspecialchars(formatShortDate($condidature->getDateCandidature())); ?></td>
                                        <td><?php echo htmlspecialchars(formatMoney($condidature->getBudgetPropose())); ?></td>
                                        <td><?php echo htmlspecialchars(formatDateLabel($condidature->getDateDerniereModification())); ?></td>
                                        <td class="admin-actions">
                                            <div class="admin-actions-stack">
                                                <a class="inspect-link" href="details.php?idCandidature=<?php echo (int) $condidature->getIdCandidature(); ?>">Review</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </div>
        </main>
    </div>
</body>
</html>
