<?php
session_start();

require_once __DIR__ . '/../layout/early-theme.php';
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

$notificationController = $controller;
$notificationUserId = isset($sessionUser['id']) ? (int) $sessionUser['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notificationAction'])) {
    $notificationAction = (string) $_POST['notificationAction'];
    if ($notificationAction === 'mark_one') {
        $notificationController->markNotificationActionAsRead((int) ($_POST['idNotificationAction'] ?? 0), $notificationUserId);
    } elseif ($notificationAction === 'mark_all') {
        $notificationController->markAllNotificationActionsAsRead($notificationUserId);
    }

    $redirect = basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'index.php'));
    if (!empty($_SERVER['QUERY_STRING'])) {
        $redirect .= '?' . $_SERVER['QUERY_STRING'];
    }
    header('Location: ' . $redirect);
    exit;
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

function cleanHiddenMetadata($text)
{
    return trim((string) preg_replace(
        '/\s*<!--cre8connect-(?:condidature-form-meta|condidature-meta):.*?-->\s*/s',
        ' ',
        (string) $text
    ));
}

function excerptText($text, $length = 90)
{
    $text = cleanHiddenMetadata($text);
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
    'typeReponse' => trim((string) ($_GET['typeReponse'] ?? '')),
    'creatorId' => trim((string) ($_GET['creatorId'] ?? '')),
    'brandId' => trim((string) ($_GET['brandId'] ?? '')),
    'dateFrom' => trim((string) ($_GET['dateFrom'] ?? '')),
    'dateTo' => trim((string) ($_GET['dateTo'] ?? '')),
    'hasCv' => trim((string) ($_GET['hasCv'] ?? '')),
    'hasPortfolio' => trim((string) ($_GET['hasPortfolio'] ?? '')),
    'sort' => trim((string) ($_GET['sort'] ?? '')),
];
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$contexts = [];
$hasNextPage = false;
$adminPieChartStats = [
    'candidatureStatus' => [],
    'offerStatus' => [],
    'candidatureOrigin' => [],
];
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
$platformMetrics = [
    'realOffers' => 0,
    'realCandidatures' => 0,
    'pendingReviews' => 0,
    'openNegotiations' => 0,
    'expiredOffers' => 0,
    'acceptanceRate' => 0,
    'activityThisWeek' => 0,
];

try {
    $pagedFilters = $filters + [
        'limit' => $perPage + 1,
        'offset' => $offset,
    ];
    $contexts = $controller->getAdminCandidatures($pagedFilters);
    if (count($contexts) > $perPage) {
        $hasNextPage = true;
        array_pop($contexts);
    }
    $summary = $controller->summarizeContexts($contexts);
    $platformMetrics = $controller->getAdminPlatformMetrics();
    $adminPieChartStats = $controller->getAdminPieChartStats();
} catch (Throwable $exception) {
    $error = 'The admin candidature dashboard could not be loaded right now.';
}

$awaitingReviewCount = (int) ($summary['envoyee'] ?? 0) + (int) ($summary['en_etude'] ?? 0);
$finalCount = (int) ($summary['acceptee'] ?? 0) + (int) ($summary['refusee'] ?? 0) + (int) ($summary['retiree'] ?? 0);
$activeFilterCount = count(array_filter($filters, static fn($value) => $value !== '' && $value !== 'newest'));
$paginationBase = $_GET;
unset($paginationBase['page']);
$prevPageUrl = $page > 1 ? 'index.php?' . http_build_query($paginationBase + ['page' => $page - 1]) : '';
$nextPageUrl = $hasNextPage ? 'index.php?' . http_build_query($paginationBase + ['page' => $page + 1]) : '';
$backActive = 'collaborations';

if (!function_exists('renderBackOfficeCollaborationTabs')) {
    function renderBackOfficeCollaborationTabs(string $activeTab): void
    {
        $tabs = [
            'offers' => [
                'label' => 'Offers',
                'hint' => 'Targeted invitations',
                'href' => '../offre/index.php',
                'icon' => 'mdi-briefcase-check',
            ],
            'candidatures' => [
                'label' => 'Candidatures',
                'hint' => 'Creator responses',
                'href' => '../condidature/index.php',
                'icon' => 'mdi-account-check',
            ],
            'cre8shield' => [
                'label' => 'Cre8Shield',
                'hint' => 'Risk monitoring',
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
                                <span class="collaboration-subnav__title"><?php echo htmlspecialchars($tab['label']); ?></span>
                                <span class="collaboration-subnav__hint"><?php echo htmlspecialchars($tab['hint']); ?></span>
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
    <title>Back Office - Candidature Management</title>
    <link rel="stylesheet" href="../css/backoffice.css">
    <link rel="stylesheet" href="../layout/back-layout.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../layout/back-layout.css')); ?>">
    <link rel="stylesheet" href="../utilisateur/assets/vendors/mdi/css/materialdesignicons.min.css?v=<?php echo urlencode((string) (@filemtime(__DIR__ . '/../utilisateur/assets/vendors/mdi/css/materialdesignicons.min.css') ?: 0)); ?>">
    <link rel="stylesheet" href="../css/new_style_backoffice.css">
    <link rel="stylesheet" href="../offre/offre-admin.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../offre/offre-admin.css')); ?>">
    <link rel="stylesheet" href="condidature-admin.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/condidature-admin.css')); ?>">

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
<link rel="icon" type="image/png" sizes="32x32" href="../../public/images/logo.png">
<link rel="shortcut icon" type="image/png" href="../../public/images/logo.png">
<link rel="apple-touch-icon" href="../../public/images/logo.png">
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
                    <h1>Candidature administration</h1>
                    <p>Inspect creator responses, follow review stages, and keep every targeted candidature visible from the same dashboard.</p>
                </div>
                <?php require __DIR__ . '/notification_widget.php'; ?>
            </div>
        </header>

        <?php renderBackOfficeCollaborationTabs('candidatures'); ?>

        <?php if ($notice !== ''): ?>
            <div class="admin-flash <?php echo $noticeType === 'danger' ? 'error' : 'success'; ?>"><?php echo htmlspecialchars($notice); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="admin-flash error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <section class="card grid-margin search-panel search-panel-simple">
            <div class="search-panel-head">
                <div class="search-panel-copy">
                    <span class="search-panel-title">Admin filters</span>
                    <span class="search-panel-subtitle">
                        <?php echo $activeFilterCount > 0 ? $activeFilterCount . ' filter' . ($activeFilterCount > 1 ? 's' : '') . ' applied to this candidature view.' : 'Filter by source, creator, brand, date, or workflow state.'; ?>
                    </span>
                </div>
                <span class="search-panel-status">
                    <?php if ($activeFilterCount > 0): ?>
                        <span class="search-panel-badge"><?php echo $activeFilterCount; ?> active</span>
                    <?php endif; ?>
                </span>
            </div>

            <form method="get" class="search-form">
                <div class="search-grid">
                    <div class="search-group">
                        <label for="keyword">Search</label>
                        <input id="keyword" name="keyword" type="search" class="form-control" value="<?php echo htmlspecialchars($filters['keyword']); ?>" placeholder="Source, creator, brand, message...">
                    </div>

                    <div class="search-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-control">
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
                        <select id="origin" name="origin" class="form-control">
                            <option value="">All</option>
                            <option value="par_offre"<?php echo $filters['origin'] === 'par_offre' ? ' selected' : ''; ?>>Offer invitation</option>
                            <option value="par_campagne"<?php echo $filters['origin'] === 'par_campagne' ? ' selected' : ''; ?>>Campaign application</option>
                        </select>
                    </div>

                    <div class="search-group">
                        <label for="typeReponse">Response type</label>
                        <select id="typeReponse" name="typeReponse" class="form-control">
                            <option value="">All</option>
                            <option value="application"<?php echo $filters['typeReponse'] === 'application' ? ' selected' : ''; ?>>Application</option>
                            <option value="acceptation"<?php echo $filters['typeReponse'] === 'acceptation' ? ' selected' : ''; ?>>Acceptance</option>
                            <option value="negociation"<?php echo $filters['typeReponse'] === 'negociation' ? ' selected' : ''; ?>>Negotiation</option>
                            <option value="refus"<?php echo $filters['typeReponse'] === 'refus' ? ' selected' : ''; ?>>Refusal</option>
                        </select>
                    </div>

                    <div class="search-group">
                        <label for="creatorId">Creator</label>
                        <select id="creatorId" name="creatorId" class="form-control">
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
                        <select id="brandId" name="brandId" class="form-control">
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
                        <input id="dateFrom" name="dateFrom" type="date" class="form-control" value="<?php echo htmlspecialchars($filters['dateFrom']); ?>">
                    </div>

                    <div class="search-group">
                        <label for="dateTo">Submitted to</label>
                        <input id="dateTo" name="dateTo" type="date" class="form-control" value="<?php echo htmlspecialchars($filters['dateTo']); ?>">
                    </div>

                    <div class="search-group">
                        <label for="hasCv">CV file</label>
                        <select id="hasCv" name="hasCv" class="form-control">
                            <option value="">All</option>
                            <option value="1"<?php echo $filters['hasCv'] === '1' ? ' selected' : ''; ?>>Has CV</option>
                            <option value="0"<?php echo $filters['hasCv'] === '0' ? ' selected' : ''; ?>>No CV</option>
                        </select>
                    </div>

                    <div class="search-group">
                        <label for="hasPortfolio">Portfolio</label>
                        <select id="hasPortfolio" name="hasPortfolio" class="form-control">
                            <option value="">All</option>
                            <option value="1"<?php echo $filters['hasPortfolio'] === '1' ? ' selected' : ''; ?>>Has portfolio</option>
                            <option value="0"<?php echo $filters['hasPortfolio'] === '0' ? ' selected' : ''; ?>>No portfolio</option>
                        </select>
                    </div>

                    <div class="search-group">
                        <label for="sort">Sort</label>
                        <select id="sort" name="sort" class="form-control">
                            <option value=""<?php echo $filters['sort'] === '' ? ' selected' : ''; ?>>Workflow priority</option>
                            <option value="newest"<?php echo $filters['sort'] === 'newest' ? ' selected' : ''; ?>>Newest</option>
                            <option value="oldest"<?php echo $filters['sort'] === 'oldest' ? ' selected' : ''; ?>>Oldest</option>
                            <option value="budget_high"<?php echo $filters['sort'] === 'budget_high' ? ' selected' : ''; ?>>Budget high to low</option>
                            <option value="budget_low"<?php echo $filters['sort'] === 'budget_low' ? ' selected' : ''; ?>>Budget low to high</option>
                            <option value="proposed_delay"<?php echo $filters['sort'] === 'proposed_delay' ? ' selected' : ''; ?>>Proposed delay</option>
                            <option value="decision_date"<?php echo $filters['sort'] === 'decision_date' ? ' selected' : ''; ?>>Decision date</option>
                            <option value="status"<?php echo $filters['sort'] === 'status' ? ' selected' : ''; ?>>Status</option>
                        </select>
                    </div>
                </div>

                <div class="search-actions">
                    <button type="submit" class="btn btn-primary">Apply filters</button>
                    <a class="btn btn-secondary clear-link" href="index.php">Reset</a>
                </div>
            </form>
        </section>

        <section class="admin-summary">
            <article class="admin-card card card-body bo-kpi-card bo-kpi-card-1">
                <h3>Real candidatures</h3>
                <p><?php echo (int) ($platformMetrics['realCandidatures'] ?? 0); ?></p>
                <small>Technical campaign placeholders excluded</small>
            </article>
            <article class="admin-card card card-body bo-kpi-card bo-kpi-card-2">
                <h3>Pending reviews</h3>
                <p><?php echo (int) ($platformMetrics['pendingReviews'] ?? 0); ?></p>
                <small>Sent or under review</small>
            </article>
            <article class="admin-card card card-body bo-kpi-card bo-kpi-card-3">
                <h3>Open negotiations</h3>
                <p><?php echo (int) ($platformMetrics['openNegotiations'] ?? 0); ?></p>
                <small>Active negotiation candidatures</small>
            </article>
            <article class="admin-card card card-body bo-kpi-card bo-kpi-card-4">
                <h3>Expired offers</h3>
                <p><?php echo (int) ($platformMetrics['expiredOffers'] ?? 0); ?></p>
                <small>Past deadline and not archived</small>
            </article>
            <article class="admin-card card card-body bo-kpi-card bo-kpi-card-5">
                <h3>Acceptance rate</h3>
                <p><?php echo htmlspecialchars((string) ($platformMetrics['acceptanceRate'] ?? 0)); ?>%</p>
                <small>Accepted over accepted + refused</small>
            </article>
            <article class="admin-card card card-body bo-kpi-card bo-kpi-card-6">
                <h3>Activity this week</h3>
                <p><?php echo (int) ($platformMetrics['activityThisWeek'] ?? 0); ?></p>
                <small><?php echo (int) ($platformMetrics['offersThisWeek'] ?? 0); ?> offers + <?php echo (int) ($platformMetrics['candidaturesThisWeek'] ?? 0); ?> candidatures</small>
            </article>
        </section>

        <?php require __DIR__ . '/statistics_charts.php'; ?>

        <div class="admin-layout">
            <section class="admin-panel admin-table-panel card grid-margin">
                <div class="admin-panel-header">
                    <h2 class="card-title">Candidature list</h2>
                </div>
                <div class="admin-panel-body card-body">
                    <div class="admin-table-wrapper">
                        <table class="admin-table admin-candidature-table table table-hover table-striped">
                            <colgroup>
                                <col class="cand-col-creator">
                                <col class="cand-col-source">
                                <col class="cand-col-origin">
                                <col class="cand-col-action">
                                <col class="cand-col-status">
                                <col class="cand-col-date">
                                <col class="cand-col-budget">
                                <col class="cand-col-updated">
                                <col class="cand-col-actions">
                            </colgroup>
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
                                    $originLabel = translateOrigin($source['origin']);
                                    $primaryPreview = $condidature->getResponseMode() === 'decline'
                                        ? (string) $condidature->getMotifRefus()
                                        : (string) $condidature->getMessageMotivation();
                                    if (trim($primaryPreview) === '') {
                                        $primaryPreview = (string) $condidature->getConditionsCreateur();
                                    }
                                    ?>
                                    <tr>
                                        <td class="candidature-table-person">
                                            <strong><?php echo htmlspecialchars($creator['nom'] ?: ('Creator #' . $creator['id'])); ?></strong>
                                            <span><?php echo htmlspecialchars($creator['email']); ?></span>
                                        </td>
                                        <td class="candidature-table-source">
                                            <span class="source-kind"><?php echo htmlspecialchars($originLabel); ?></span>
                                            <strong title="<?php echo htmlspecialchars($source['title']); ?>"><?php echo htmlspecialchars(excerptText($source['title'], 40)); ?></strong>
                                            <span><?php echo htmlspecialchars(($source['origin'] === 'par_campagne' ? 'Campaign' : 'Offer') . ' #' . (int) $source['id']); ?></span>
                                            <span><?php echo htmlspecialchars($brand['nom'] ?: 'Unknown brand'); ?></span>
                                        </td>
                                        <td><span class="candidature-origin-pill"><?php echo htmlspecialchars($originLabel); ?></span></td>
                                        <td class="candidature-table-response">
                                            <span class="candidature-detail-pill"><?php echo htmlspecialchars($condidature->getResponseTypeLabel()); ?></span>
                                            <?php if (trim($primaryPreview) !== ''): ?>
                                                <small class="response-preview" title="<?php echo htmlspecialchars(cleanHiddenMetadata($primaryPreview)); ?>">
                                                    <?php echo htmlspecialchars(excerptText($primaryPreview, 64)); ?>
                                                </small>
                                            <?php endif; ?>
                                            <?php if ($condidature->getDateDisponibilite()): ?>
                                                <small>Available: <?php echo htmlspecialchars(formatShortDate($condidature->getDateDisponibilite())); ?></small>
                                            <?php endif; ?>
                                            <?php if ((int) ($context['negotiation']['count'] ?? 0) > 0): ?>
                                                <small><?php echo (int) $context['negotiation']['count']; ?> negotiation message<?php echo (int) $context['negotiation']['count'] === 1 ? '' : 's'; ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge badge-status <?php echo htmlspecialchars($condidature->getStatutCandidature()); ?>"><?php echo htmlspecialchars($condidature->getDisplayStatusLabel()); ?></span></td>
                                        <td class="date-cell"><?php echo htmlspecialchars(formatShortDate($condidature->getDateCandidature())); ?></td>
                                        <td class="money-cell"><?php echo htmlspecialchars(formatMoney($condidature->getBudgetPropose())); ?></td>
                                        <td class="date-cell"><?php echo htmlspecialchars(formatDateLabel($condidature->getDateDerniereModification())); ?></td>
                                        <td class="admin-actions">
                                            <div class="admin-actions-stack">
                                                <button
                                                    type="button"
                                                    class="btn btn-info btn-sm inspect-link source-preview-trigger"
                                                    data-source-preview-trigger
                                                    data-source-origin="<?php echo htmlspecialchars($source['origin']); ?>"
                                                    data-source-origin-label="<?php echo htmlspecialchars($originLabel); ?>"
                                                    data-source-id="<?php echo (int) $source['id']; ?>"
                                                    data-source-title="<?php echo htmlspecialchars($source['title']); ?>"
                                                    data-source-brand="<?php echo htmlspecialchars($brand['nom'] ?: 'Unknown brand'); ?>"
                                                    data-source-brand-email="<?php echo htmlspecialchars($brand['email'] ?? ''); ?>"
                                                    data-source-objective="<?php echo htmlspecialchars(cleanHiddenMetadata($source['objective'] ?? '')); ?>"
                                                    data-source-description="<?php echo htmlspecialchars(cleanHiddenMetadata($source['description'] ?? '')); ?>"
                                                    data-source-budget="<?php echo htmlspecialchars($source['budgetPropose'] !== null ? formatMoney($source['budgetPropose']) : 'Not shared'); ?>"
                                                    data-source-start="<?php echo htmlspecialchars(formatShortDate($source['datePublication'] ?? null)); ?>"
                                                    data-source-end="<?php echo htmlspecialchars(formatShortDate($source['dateLimite'] ?? null)); ?>"
                                                    data-source-status="<?php echo htmlspecialchars(trim((string) ($source['status'] ?? '')) !== '' ? ucwords(str_replace('_', ' ', (string) $source['status'])) : 'Not set'); ?>"
                                                >Source</button>
                                                <a class="btn btn-info btn-sm inspect-link" href="details.php?idCandidature=<?php echo (int) $condidature->getIdCandidature(); ?>">Review</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <nav class="admin-pagination" aria-label="Candidature pages">
                        <span>Page <?php echo $page; ?> · Showing up to <?php echo $perPage; ?> candidatures</span>
                        <div>
                            <?php if ($prevPageUrl !== ''): ?>
                                <a class="btn btn-secondary btn-sm clear-link" href="<?php echo htmlspecialchars($prevPageUrl); ?>">Previous</a>
                            <?php else: ?>
                                <span class="btn btn-secondary btn-sm clear-link is-disabled">Previous</span>
                            <?php endif; ?>
                            <?php if ($nextPageUrl !== ''): ?>
                                <a class="btn btn-primary btn-sm clear-link" href="<?php echo htmlspecialchars($nextPageUrl); ?>">Next</a>
                            <?php else: ?>
                                <span class="btn btn-secondary btn-sm clear-link is-disabled">Next</span>
                            <?php endif; ?>
                        </div>
                    </nav>
                </div>
            </section>
        </div>
    </div>
    </div>
        </main>
    </div>

    <div class="source-preview-overlay" data-source-preview-overlay hidden>
        <section class="source-preview-dialog" role="dialog" aria-modal="true" aria-labelledby="sourcePreviewTitle">
            <div class="source-preview-header">
                <div>
                    <span class="source-preview-kicker" data-source-preview-origin>Source</span>
                    <h2 id="sourcePreviewTitle" data-source-preview-title>Source details</h2>
                    <p data-source-preview-brand>Brand</p>
                </div>
                <button type="button" class="source-preview-close" data-source-preview-close aria-label="Close source preview">&times;</button>
            </div>

            <div class="source-preview-meta">
                <span data-source-preview-id>Source #</span>
                <span data-source-preview-status>Status</span>
                <span data-source-preview-budget>Budget</span>
                <span data-source-preview-start>Start</span>
                <span data-source-preview-end>End</span>
            </div>

            <div class="source-preview-grid">
                <article>
                    <strong data-source-preview-objective-label>Objective</strong>
                    <p data-source-preview-objective>No objective was joined for this source.</p>
                </article>
                <article>
                    <strong>Description</strong>
                    <p data-source-preview-description>No description was joined for this source.</p>
                </article>
            </div>
        </section>
    </div>

    <script src="../layout/back-layout.js?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../layout/back-layout.js')); ?>"></script>
    <script>
        (() => {
            const overlay = document.querySelector('[data-source-preview-overlay]');
            if (!overlay) {
                return;
            }

            const dialog = overlay.querySelector('.source-preview-dialog');
            const closeButton = overlay.querySelector('[data-source-preview-close]');
            const setText = (selector, value, fallback = '') => {
                const node = overlay.querySelector(selector);
                if (node) {
                    node.textContent = value || fallback;
                }
            };

            const openPreview = (button) => {
                const data = button.dataset;
                const isCampaign = data.sourceOrigin === 'par_campagne';

                setText('[data-source-preview-origin]', data.sourceOriginLabel, 'Source');
                setText('[data-source-preview-title]', data.sourceTitle, isCampaign ? 'Campaign source' : 'Offer source');
                setText('[data-source-preview-brand]', `${data.sourceBrand || 'Unknown brand'}${data.sourceBrandEmail ? ` - ${data.sourceBrandEmail}` : ''}`);
                setText('[data-source-preview-id]', `${isCampaign ? 'Campaign' : 'Offer'} #${data.sourceId || ''}`);
                setText('[data-source-preview-status]', `Status: ${data.sourceStatus || 'Not set'}`);
                setText('[data-source-preview-budget]', `Budget: ${data.sourceBudget || 'Not shared'}`);
                setText('[data-source-preview-start]', `${isCampaign ? 'Start' : 'Published'}: ${data.sourceStart || 'Not available'}`);
                setText('[data-source-preview-end]', `${isCampaign ? 'End' : 'Deadline'}: ${data.sourceEnd || 'Not available'}`);
                setText('[data-source-preview-objective-label]', isCampaign ? 'Campaign brief' : 'Offer objective');
                setText('[data-source-preview-objective]', data.sourceObjective, isCampaign ? 'No campaign brief was joined for this source.' : 'No offer objective was joined for this source.');
                setText('[data-source-preview-description]', data.sourceDescription, 'No description was joined for this source.');

                overlay.hidden = false;
                document.body.classList.add('source-preview-open');
                closeButton?.focus({ preventScroll: true });
            };

            const closePreview = () => {
                overlay.hidden = true;
                document.body.classList.remove('source-preview-open');
            };

            document.querySelectorAll('[data-source-preview-trigger]').forEach((button) => {
                button.addEventListener('click', () => openPreview(button));
            });

            closeButton?.addEventListener('click', closePreview);
            overlay.addEventListener('click', (event) => {
                if (event.target === overlay) {
                    closePreview();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !overlay.hidden) {
                    closePreview();
                }
            });

            dialog?.addEventListener('click', (event) => event.stopPropagation());
        })();
    </script>
<?php
$cre8PilotContext = [
    'page' => 'admin_candidature_workspace',
    'mode' => 'table',
    'role' => 'admin',
    'allowedActions' => ['normal_chat', 'summarize_page', 'analyze_page', 'explain_statistics', 'detect_risky_items', 'explain_statuses', 'apply_filters', 'apply_search'],
    'formTarget' => 'filter_form',
    'visibleEntityType' => 'candidature',
];
require __DIR__ . '/../../FrontOffice/condidature/cre8pilot_widget.php';
?>
</body>
</html>
