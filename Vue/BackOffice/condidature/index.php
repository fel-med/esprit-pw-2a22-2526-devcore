<?php
session_start();

require_once __DIR__ . '/../layout/early-theme.php';
require_once __DIR__ . '/../../../Controleur/session_helper.php';
require_once __DIR__ . '/../../../Controleur/condidatureC.php';

$controller = new CondidatureC();
$sessionUser = $_SESSION['utilisateur'] ?? ($_SESSION['user'] ?? []);

if (!isset($sessionUser['id']) || !isBackOfficeRole(cc_current_user_role())) {
    header('Location: ../../FrontOffice/utilisateur/login.php');
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

function candidatureStatusI18nKey($status)
{
    $key = strtolower((string) $status);
    $key = str_replace(['-', ' '], '_', $key);
    return match ($key) {
        'brouillon' => 'candidatures.status.draft',
        'envoyee' => 'candidatures.status.sent',
        'en_etude' => 'candidatures.status.underReview',
        'negociation' => 'candidatures.status.negotiation',
        'acceptee' => 'candidatures.status.accepted',
        'refusee' => 'candidatures.status.refused',
        'retiree' => 'candidatures.status.withdrawn',
        default => '',
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

function originI18nKey($origin)
{
    return match ((string) $origin) {
        'par_offre' => 'candidatures.origin.offer',
        'par_campagne' => 'candidatures.origin.campaign',
        default => '',
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

function adminPageUrl(array $base, int $page): string
{
    return 'index.php?' . http_build_query($base + ['page' => max(1, $page)]);
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
    <title>Back Office - Candidature Management</title>
    <link rel="stylesheet" href="../css/backoffice.css">
    <link rel="stylesheet" href="../layout/back-layout.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../layout/back-layout.css')); ?>">
    <link rel="stylesheet" href="../utilisateur/assets/vendors/mdi/css/materialdesignicons.min.css?v=<?php echo urlencode((string) (@filemtime(__DIR__ . '/../utilisateur/assets/vendors/mdi/css/materialdesignicons.min.css') ?: 0)); ?>">
    <link rel="stylesheet" href="../css/new_style_backoffice.css">
    <link rel="stylesheet" href="../offre/offre-admin.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../offre/offre-admin.css')); ?>">
    <link rel="stylesheet" href="condidature-admin.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/condidature-admin.css')); ?>">
    <link rel="stylesheet" href="../unified-table-admin.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../unified-table-admin.css')); ?>">

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
                    <h1 data-i18n="candidatures.title">Candidature administration</h1>
                    <p data-i18n="candidatures.subtitle">Inspect creator responses, follow review stages, and keep every targeted candidature visible from the same dashboard.</p>
                </div>
            </div>
        </header>

        <?php renderBackOfficeCollaborationTabs('candidatures'); ?>

        <?php if ($notice !== ''): ?>
            <div class="admin-flash <?php echo $noticeType === 'danger' ? 'error' : 'success'; ?>"><?php echo htmlspecialchars($notice); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="admin-flash error"><?php echo htmlspecialchars($error); ?></div>
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
                    <h3 data-i18n="collaboration.kpi.realCandidatures">Real candidatures</h3>
                    <p><?php echo (int) ($platformMetrics['realCandidatures'] ?? 0); ?></p>
                    <small data-i18n="candidatures.kpi.technicalExcluded">Technical campaign placeholders excluded</small>
                </article>
                <article class="admin-card card card-body bo-kpi-card bo-kpi-card-2">
                    <h3 data-i18n="collaboration.kpi.pendingReviews">Pending reviews</h3>
                    <p><?php echo (int) ($platformMetrics['pendingReviews'] ?? 0); ?></p>
                    <small data-i18n="collaboration.kpi.sentOrReview">Sent or under review</small>
                </article>
                <article class="admin-card card card-body bo-kpi-card bo-kpi-card-3">
                    <h3 data-i18n="collaboration.kpi.openNegotiations">Open negotiations</h3>
                    <p><?php echo (int) ($platformMetrics['openNegotiations'] ?? 0); ?></p>
                    <small data-i18n="collaboration.kpi.activeNegotiations">Active negotiation candidatures</small>
                </article>
                <article class="admin-card card card-body bo-kpi-card bo-kpi-card-4">
                    <h3 data-i18n="offers.kpi.expiredOffers">Expired offers</h3>
                    <p><?php echo (int) ($platformMetrics['expiredOffers'] ?? 0); ?></p>
                    <small data-i18n="offers.kpi.expiredOffersSub">Past deadline and not archived</small>
                </article>
                <article class="admin-card card card-body bo-kpi-card bo-kpi-card-5">
                    <h3 data-i18n="candidatures.kpi.acceptanceRate">Acceptance rate</h3>
                    <p><?php echo htmlspecialchars((string) ($platformMetrics['acceptanceRate'] ?? 0)); ?>%</p>
                    <small data-i18n="candidatures.kpi.acceptanceRateSub">Accepted over accepted + refused</small>
                </article>
                <article class="admin-card card card-body bo-kpi-card bo-kpi-card-6">
                    <h3 data-i18n="collaboration.kpi.activityThisWeek">Activity this week</h3>
                    <p><?php echo (int) ($platformMetrics['activityThisWeek'] ?? 0); ?></p>
                    <small><?php echo (int) ($platformMetrics['offersThisWeek'] ?? 0); ?> offers + <?php echo (int) ($platformMetrics['candidaturesThisWeek'] ?? 0); ?> candidatures</small>
                </article>
            </section>

            <?php require __DIR__ . '/statistics_charts.php'; ?>
        </section>

        <section class="card grid-margin search-panel search-panel-simple">
            <div class="search-panel-head">
                <div class="search-panel-copy">
                    <span class="search-panel-title" data-i18n="candidatures.filter.title">Admin filters</span>
                    <span class="search-panel-subtitle">
                        <?php echo $activeFilterCount > 0 ? $activeFilterCount . ' filter' . ($activeFilterCount > 1 ? 's' : '') . ' applied to this candidature view.' : '<span data-i18n="candidatures.filter.subtitle">Filter by source, creator, brand, date, or workflow state.</span>'; ?>
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
                        <label for="keyword" data-i18n="common.search">Search</label>
                        <input id="keyword" name="keyword" type="search" class="form-control" value="<?php echo htmlspecialchars($filters['keyword']); ?>" placeholder="Source, creator, brand, message..." data-i18n-placeholder="candidatures.filter.searchPlaceholder">
                    </div>

                    <div class="search-group">
                        <label for="status" data-i18n="common.status">Status</label>
                        <select id="status" name="status" class="form-control">
                            <option value="" data-i18n-opt="common.all">All</option>
                            <option value="brouillon"<?php echo $filters['status'] === 'brouillon' ? ' selected' : ''; ?> data-i18n-opt="candidatures.status.draft">Draft</option>
                            <option value="envoyee"<?php echo $filters['status'] === 'envoyee' ? ' selected' : ''; ?> data-i18n-opt="candidatures.status.sent">Sent</option>
                            <option value="en_etude"<?php echo $filters['status'] === 'en_etude' ? ' selected' : ''; ?> data-i18n-opt="candidatures.status.underReview">Under review</option>
                            <option value="negociation"<?php echo $filters['status'] === 'negociation' ? ' selected' : ''; ?> data-i18n-opt="candidatures.status.negotiation">Negotiation</option>
                            <option value="acceptee"<?php echo $filters['status'] === 'acceptee' ? ' selected' : ''; ?> data-i18n-opt="candidatures.status.accepted">Accepted</option>
                            <option value="refusee"<?php echo $filters['status'] === 'refusee' ? ' selected' : ''; ?> data-i18n-opt="candidatures.status.refused">Refused</option>
                            <option value="retiree"<?php echo $filters['status'] === 'retiree' ? ' selected' : ''; ?> data-i18n-opt="candidatures.status.withdrawn">Withdrawn</option>
                        </select>
                    </div>

                    <div class="search-group">
                        <label for="origin" data-i18n="candidatures.filter.origin">Origin</label>
                        <select id="origin" name="origin" class="form-control">
                            <option value="" data-i18n-opt="common.all">All</option>
                            <option value="par_offre"<?php echo $filters['origin'] === 'par_offre' ? ' selected' : ''; ?> data-i18n-opt="candidatures.origin.offer">Offer invitation</option>
                            <option value="par_campagne"<?php echo $filters['origin'] === 'par_campagne' ? ' selected' : ''; ?> data-i18n-opt="candidatures.origin.campaign">Campaign application</option>
                        </select>
                    </div>

                    <div class="search-group">
                        <label for="typeReponse" data-i18n="candidatures.filter.responseType">Response type</label>
                        <select id="typeReponse" name="typeReponse" class="form-control">
                            <option value="" data-i18n-opt="common.all">All</option>
                            <option value="application"<?php echo $filters['typeReponse'] === 'application' ? ' selected' : ''; ?> data-i18n-opt="candidatures.response.application">Application</option>
                            <option value="acceptation"<?php echo $filters['typeReponse'] === 'acceptation' ? ' selected' : ''; ?> data-i18n-opt="candidatures.response.acceptance">Acceptance</option>
                            <option value="negociation"<?php echo $filters['typeReponse'] === 'negociation' ? ' selected' : ''; ?> data-i18n-opt="candidatures.response.negotiation">Negotiation</option>
                            <option value="refus"<?php echo $filters['typeReponse'] === 'refus' ? ' selected' : ''; ?> data-i18n-opt="candidatures.response.refusal">Refusal</option>
                        </select>
                    </div>

                    <div class="search-group">
                        <label for="creatorId" data-i18n="common.creator">Creator</label>
                        <select id="creatorId" name="creatorId" class="form-control">
                            <option value="" data-i18n-opt="common.all">All</option>
                            <?php foreach ($creatorOptions as $creator): ?>
                                <option value="<?php echo (int) $creator['id']; ?>"<?php echo $filters['creatorId'] === (string) $creator['id'] ? ' selected' : ''; ?>>
                                    <?php echo htmlspecialchars($creator['nom'] . ' (#' . $creator['id'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="search-group">
                        <label for="brandId" data-i18n="common.brand">Brand</label>
                        <select id="brandId" name="brandId" class="form-control">
                            <option value="" data-i18n-opt="common.all">All</option>
                            <?php foreach ($brandOptions as $brand): ?>
                                <option value="<?php echo (int) $brand['id']; ?>"<?php echo $filters['brandId'] === (string) $brand['id'] ? ' selected' : ''; ?>>
                                    <?php echo htmlspecialchars($brand['nom'] . ' (#' . $brand['id'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="search-group">
                        <label for="dateFrom" data-i18n="candidatures.filter.submittedFrom">Submitted from</label>
                        <input id="dateFrom" name="dateFrom" type="date" class="form-control" value="<?php echo htmlspecialchars($filters['dateFrom']); ?>">
                    </div>

                    <div class="search-group">
                        <label for="dateTo" data-i18n="candidatures.filter.submittedTo">Submitted to</label>
                        <input id="dateTo" name="dateTo" type="date" class="form-control" value="<?php echo htmlspecialchars($filters['dateTo']); ?>">
                    </div>

                    <div class="search-group">
                        <label for="hasCv" data-i18n="candidatures.filter.cv">CV file</label>
                        <select id="hasCv" name="hasCv" class="form-control">
                            <option value="" data-i18n-opt="common.all">All</option>
                            <option value="1"<?php echo $filters['hasCv'] === '1' ? ' selected' : ''; ?> data-i18n-opt="candidatures.filter.hasCv">Has CV</option>
                            <option value="0"<?php echo $filters['hasCv'] === '0' ? ' selected' : ''; ?> data-i18n-opt="candidatures.filter.noCv">No CV</option>
                        </select>
                    </div>

                    <div class="search-group">
                        <label for="hasPortfolio" data-i18n="candidatures.filter.portfolio">Portfolio</label>
                        <select id="hasPortfolio" name="hasPortfolio" class="form-control">
                            <option value="" data-i18n-opt="common.all">All</option>
                            <option value="1"<?php echo $filters['hasPortfolio'] === '1' ? ' selected' : ''; ?> data-i18n-opt="candidatures.filter.hasPortfolio">Has portfolio</option>
                            <option value="0"<?php echo $filters['hasPortfolio'] === '0' ? ' selected' : ''; ?> data-i18n-opt="candidatures.filter.noPortfolio">No portfolio</option>
                        </select>
                    </div>

                    <div class="search-group">
                        <label for="sort" data-i18n="offers.filter.sort">Sort</label>
                        <select id="sort" name="sort" class="form-control">
                            <option value=""<?php echo $filters['sort'] === '' ? ' selected' : ''; ?> data-i18n-opt="candidatures.sort.workflow">Workflow priority</option>
                            <option value="newest"<?php echo $filters['sort'] === 'newest' ? ' selected' : ''; ?> data-i18n-opt="offers.sort.newest">Newest</option>
                            <option value="oldest"<?php echo $filters['sort'] === 'oldest' ? ' selected' : ''; ?> data-i18n-opt="offers.sort.oldest">Oldest</option>
                            <option value="budget_high"<?php echo $filters['sort'] === 'budget_high' ? ' selected' : ''; ?> data-i18n-opt="offers.sort.budgetHigh">Budget high to low</option>
                            <option value="budget_low"<?php echo $filters['sort'] === 'budget_low' ? ' selected' : ''; ?> data-i18n-opt="offers.sort.budgetLow">Budget low to high</option>
                            <option value="proposed_delay"<?php echo $filters['sort'] === 'proposed_delay' ? ' selected' : ''; ?> data-i18n-opt="candidatures.sort.proposedDelay">Proposed delay</option>
                            <option value="decision_date"<?php echo $filters['sort'] === 'decision_date' ? ' selected' : ''; ?> data-i18n-opt="candidatures.sort.decisionDate">Decision date</option>
                            <option value="status"<?php echo $filters['sort'] === 'status' ? ' selected' : ''; ?> data-i18n-opt="common.status">Status</option>
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
                    <h2 class="card-title" data-i18n="candidatures.table.title">Candidature list</h2>
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
                                    <th data-i18n="common.creator">Creator</th>
                                    <th data-i18n="common.source">Source</th>
                                    <th data-i18n="candidatures.table.origin">Origin</th>
                                    <th data-i18n="candidatures.table.creatorAction">Creator action</th>
                                    <th data-i18n="common.status">Status</th>
                                    <th data-i18n="candidatures.table.submitted">Submitted</th>
                                    <th data-i18n="common.budget">Budget</th>
                                    <th data-i18n="candidatures.table.updated">Updated</th>
                                    <th data-i18n="common.actions">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($contexts)): ?>
                                    <tr>
                                        <td colspan="9">
                                            <div class="admin-empty-state">
                                                <div class="admin-empty-icon">i</div>
                                                <strong data-i18n="common.empty">No records found</strong>
                                                <span data-i18n="common.emptyHint">Try changing filters or search terms.</span>
                                            </div>
                                        </td>
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
                                            <span class="source-kind" data-i18n="<?php echo htmlspecialchars(originI18nKey($source['origin'])); ?>"><?php echo htmlspecialchars($originLabel); ?></span>
                                            <strong title="<?php echo htmlspecialchars($source['title']); ?>"><?php echo htmlspecialchars(excerptText($source['title'], 40)); ?></strong>
                                            <span><?php echo htmlspecialchars(($source['origin'] === 'par_campagne' ? 'Campaign' : 'Offer') . ' #' . (int) $source['id']); ?></span>
                                            <span><?php echo htmlspecialchars($brand['nom'] ?: 'Unknown brand'); ?></span>
                                        </td>
                                        <td><span class="candidature-origin-pill" data-i18n="<?php echo htmlspecialchars(originI18nKey($source['origin'])); ?>"><?php echo htmlspecialchars($originLabel); ?></span></td>
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
                                        <td><span class="badge badge-status <?php echo htmlspecialchars($condidature->getStatutCandidature()); ?>" data-i18n="<?php echo htmlspecialchars(candidatureStatusI18nKey($condidature->getStatutCandidature())); ?>"><?php echo htmlspecialchars($condidature->getDisplayStatusLabel()); ?></span></td>
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
                                                ><span data-i18n="common.source">Source</span></button>
                                                <a class="btn btn-info btn-sm inspect-link" href="details.php?idCandidature=<?php echo (int) $condidature->getIdCandidature(); ?>"><span data-i18n="common.review">Review</span></a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <nav class="admin-pagination" aria-label="Candidature pages">
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
                <button type="button" class="source-preview-close" data-source-preview-close aria-label="Close source preview" data-i18n-aria-label="candidatures.preview.close">&times;</button>
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
                    <strong data-i18n="common.description">Description</strong>
                    <p data-source-preview-description data-i18n="candidatures.preview.noDescription">No description was joined for this source.</p>
                </article>
            </div>
        </section>
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
                'candidatures.title': 'Candidature administration',
                'candidatures.subtitle': 'Inspect creator responses, follow review stages, and keep every targeted candidature visible from the same dashboard.',
                'candidatures.kpi.technicalExcluded': 'Technical campaign placeholders excluded',
                'candidatures.kpi.acceptanceRate': 'Acceptance rate',
                'candidatures.kpi.acceptanceRateSub': 'Accepted over accepted + refused',
                'candidatures.filter.title': 'Admin filters',
                'candidatures.filter.subtitle': 'Filter by source, creator, brand, date, or workflow state.',
                'candidatures.filter.searchPlaceholder': 'Source, creator, brand, message...',
                'candidatures.filter.origin': 'Origin',
                'candidatures.filter.responseType': 'Response type',
                'candidatures.filter.submittedFrom': 'Submitted from',
                'candidatures.filter.submittedTo': 'Submitted to',
                'candidatures.filter.cv': 'CV file',
                'candidatures.filter.portfolio': 'Portfolio',
                'candidatures.filter.hasCv': 'Has CV',
                'candidatures.filter.noCv': 'No CV',
                'candidatures.filter.hasPortfolio': 'Has portfolio',
                'candidatures.filter.noPortfolio': 'No portfolio',
                'candidatures.table.title': 'Candidature list',
                'candidatures.table.origin': 'Origin',
                'candidatures.table.creatorAction': 'Creator action',
                'candidatures.table.submitted': 'Submitted',
                'candidatures.table.updated': 'Updated',
                'candidatures.status.draft': 'Draft response',
                'candidatures.status.sent': 'Accepted invitation',
                'candidatures.status.underReview': 'Response under review',
                'candidatures.status.negotiation': 'Negotiation requested',
                'candidatures.status.accepted': 'Accepted terms',
                'candidatures.status.refused': 'Refused by brand',
                'candidatures.status.withdrawn': 'Declined invitation',
                'candidatures.origin.offer': 'Offer invitation',
                'candidatures.origin.campaign': 'Campaign application',
                'candidatures.response.application': 'Application',
                'candidatures.response.acceptance': 'Acceptance',
                'candidatures.response.negotiation': 'Negotiation',
                'candidatures.response.refusal': 'Refusal',
                'candidatures.sort.workflow': 'Workflow priority',
                'candidatures.sort.proposedDelay': 'Proposed delay',
                'candidatures.sort.decisionDate': 'Decision date',
                'candidatures.preview.close': 'Close source preview',
                'candidatures.preview.campaignSource': 'Campaign source',
                'candidatures.preview.offerSource': 'Offer source',
                'candidatures.preview.unknownBrand': 'Unknown brand',
                'candidatures.preview.notSet': 'Not set',
                'candidatures.preview.campaignBrief': 'Campaign brief',
                'candidatures.preview.offerObjective': 'Offer objective',
                'candidatures.preview.noCampaignBrief': 'No campaign brief was joined for this source.',
                'candidatures.preview.noOfferObjective': 'No offer objective was joined for this source.',
                'candidatures.preview.noDescription': 'No description was joined for this source.',
                'common.emptyHint': 'Try changing filters or search terms.'
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
                'candidatures.title': 'Administration des candidatures',
                'candidatures.subtitle': 'Inspectez les reponses des createurs, suivez les etapes de revue et gardez chaque candidature visible.',
                'candidatures.kpi.technicalExcluded': 'Elements techniques de campagne exclus',
                'candidatures.kpi.acceptanceRate': 'Taux d acceptation',
                'candidatures.kpi.acceptanceRateSub': 'Acceptees sur acceptees + refusees',
                'candidatures.filter.title': 'Filtres admin',
                'candidatures.filter.subtitle': 'Filtrer par source, createur, marque, date ou etat du workflow.',
                'candidatures.filter.searchPlaceholder': 'Source, createur, marque, message...',
                'candidatures.filter.origin': 'Origine',
                'candidatures.filter.responseType': 'Type de reponse',
                'candidatures.filter.submittedFrom': 'Soumise depuis',
                'candidatures.filter.submittedTo': 'Soumise jusqu a',
                'candidatures.filter.cv': 'Fichier CV',
                'candidatures.filter.portfolio': 'Portfolio',
                'candidatures.filter.hasCv': 'Avec CV',
                'candidatures.filter.noCv': 'Sans CV',
                'candidatures.filter.hasPortfolio': 'Avec portfolio',
                'candidatures.filter.noPortfolio': 'Sans portfolio',
                'candidatures.table.title': 'Liste des candidatures',
                'candidatures.table.origin': 'Origine',
                'candidatures.table.creatorAction': 'Action createur',
                'candidatures.table.submitted': 'Soumise',
                'candidatures.table.updated': 'Mise a jour',
                'candidatures.status.draft': 'Reponse brouillon',
                'candidatures.status.sent': 'Invitation acceptee',
                'candidatures.status.underReview': 'Reponse en cours de revue',
                'candidatures.status.negotiation': 'Negociation demandee',
                'candidatures.status.accepted': 'Conditions acceptees',
                'candidatures.status.refused': 'Refusee par la marque',
                'candidatures.status.withdrawn': 'Invitation declinee',
                'candidatures.origin.offer': 'Invitation d offre',
                'candidatures.origin.campaign': 'Candidature campagne',
                'candidatures.response.application': 'Candidature',
                'candidatures.response.acceptance': 'Acceptation',
                'candidatures.response.negotiation': 'Negociation',
                'candidatures.response.refusal': 'Refus',
                'candidatures.sort.workflow': 'Priorite workflow',
                'candidatures.sort.proposedDelay': 'Delai propose',
                'candidatures.sort.decisionDate': 'Date de decision',
                'candidatures.preview.close': 'Fermer l apercu source',
                'candidatures.preview.campaignSource': 'Source campagne',
                'candidatures.preview.offerSource': 'Source offre',
                'candidatures.preview.unknownBrand': 'Marque inconnue',
                'candidatures.preview.notSet': 'Non defini',
                'candidatures.preview.campaignBrief': 'Brief campagne',
                'candidatures.preview.offerObjective': 'Objectif de l offre',
                'candidatures.preview.noCampaignBrief': 'Aucun brief campagne n est joint a cette source.',
                'candidatures.preview.noOfferObjective': 'Aucun objectif d offre n est joint a cette source.',
                'candidatures.preview.noDescription': 'Aucune description n est jointe a cette source.',
                'common.emptyHint': 'Essayez de modifier les filtres ou la recherche.'
            }
        });
    </script>
    <script src="../layout/back-layout.js?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../layout/back-layout.js')); ?>"></script>
    <script>
        (() => {
            const overlay = document.querySelector('[data-source-preview-overlay]');
            if (!overlay) {
                return;
            }

            const dialog = overlay.querySelector('.source-preview-dialog');
            const closeButton = overlay.querySelector('[data-source-preview-close]');
            const t = (key, fallback) => {
                    const lang = window.cre8BackGetLang ? window.cre8BackGetLang() : 'en';
                    const dict = {
                        en: {
                            'common.source': 'Source',
                            'common.status': 'Status',
                            'common.budget': 'Budget',
                            'common.start': 'Start',
                            'common.end': 'End',
                            'common.published': 'Published',
                            'common.offer': 'Offer',
                            'common.campaign': 'Campaign',
                            'common.notAvailable': 'Not available',
                            'common.notShared': 'Not shared',
                            'candidatures.preview.campaignSource': 'Campaign source',
                            'candidatures.preview.offerSource': 'Offer source',
                            'candidatures.preview.unknownBrand': 'Unknown brand',
                            'candidatures.preview.notSet': 'Not set',
                            'candidatures.preview.campaignBrief': 'Campaign brief',
                            'candidatures.preview.offerObjective': 'Offer objective',
                            'candidatures.preview.noCampaignBrief': 'No campaign brief was joined for this source.',
                            'candidatures.preview.noOfferObjective': 'No offer objective was joined for this source.',
                            'candidatures.preview.noDescription': 'No description was joined for this source.'
                        },
                        fr: {
                            'common.source': 'Source',
                            'common.status': 'Statut',
                            'common.budget': 'Budget',
                            'common.start': 'Debut',
                            'common.end': 'Fin',
                            'common.published': 'Publie',
                            'common.offer': 'Offre',
                            'common.campaign': 'Campagne',
                            'common.notAvailable': 'Non disponible',
                            'common.notShared': 'Non partage',
                            'candidatures.preview.campaignSource': 'Source campagne',
                            'candidatures.preview.offerSource': 'Source offre',
                            'candidatures.preview.unknownBrand': 'Marque inconnue',
                            'candidatures.preview.notSet': 'Non defini',
                            'candidatures.preview.campaignBrief': 'Brief campagne',
                            'candidatures.preview.offerObjective': 'Objectif de l offre',
                            'candidatures.preview.noCampaignBrief': 'Aucun brief campagne n est joint a cette source.',
                            'candidatures.preview.noOfferObjective': 'Aucun objectif d offre n est joint a cette source.',
                            'candidatures.preview.noDescription': 'Aucune description n est jointe a cette source.'
                        }
                    };
                    return (dict[lang] && dict[lang][key]) || (dict.en && dict.en[key]) || fallback || key;
                };

                const setText = (selector, value, fallback = '') => {
                const node = overlay.querySelector(selector);
                if (node) {
                    node.textContent = value || fallback;
                }
            };

            const openPreview = (button) => {
                const data = button.dataset;
                const isCampaign = data.sourceOrigin === 'par_campagne';

                setText('[data-source-preview-origin]', data.sourceOriginLabel, t('common.source', 'Source'));
                setText('[data-source-preview-title]', data.sourceTitle, isCampaign ? t('candidatures.preview.campaignSource', 'Campaign source') : t('candidatures.preview.offerSource', 'Offer source'));
                setText('[data-source-preview-brand]', `${data.sourceBrand || t('candidatures.preview.unknownBrand', 'Unknown brand')}${data.sourceBrandEmail ? ` - ${data.sourceBrandEmail}` : ''}`);
                setText('[data-source-preview-id]', `${isCampaign ? t('common.campaign', 'Campaign') : t('common.offer', 'Offer')} #${data.sourceId || ''}`);
                setText('[data-source-preview-status]', `${t('common.status', 'Status')}: ${data.sourceStatus || t('candidatures.preview.notSet', 'Not set')}`);
                setText('[data-source-preview-budget]', `${t('common.budget', 'Budget')}: ${data.sourceBudget || t('common.notShared', 'Not shared')}`);
                setText('[data-source-preview-start]', `${isCampaign ? t('common.start', 'Start') : t('common.published', 'Published')}: ${data.sourceStart || t('common.notAvailable', 'Not available')}`);
                setText('[data-source-preview-end]', `${isCampaign ? t('common.end', 'End') : t('common.deadline', 'Deadline')}: ${data.sourceEnd || t('common.notAvailable', 'Not available')}`);
                setText('[data-source-preview-objective-label]', isCampaign ? t('candidatures.preview.campaignBrief', 'Campaign brief') : t('candidatures.preview.offerObjective', 'Offer objective'));
                setText('[data-source-preview-objective]', data.sourceObjective, isCampaign ? t('candidatures.preview.noCampaignBrief', 'No campaign brief was joined for this source.') : t('candidatures.preview.noOfferObjective', 'No offer objective was joined for this source.'));
                setText('[data-source-preview-description]', data.sourceDescription, t('candidatures.preview.noDescription', 'No description was joined for this source.'));

                overlay.hidden = false;
                document.body.classList.add('source-preview-open');
                closeButton?.focus({ preventScroll: true });
            };

            const closePreview = () => {
                overlay.hidden = true;
                document.body.classList.remove('source-preview-open');
            };

            document.addEventListener('click', (event) => {
                const button = event.target.closest('[data-source-preview-trigger]');
                if (!button) {
                    return;
                }
                event.preventDefault();
                openPreview(button);
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

        (() => {
            const selector = '#admin-results-region';
            const getRegion = () => document.querySelector(selector);

            if (!window.fetch || !window.DOMParser || !window.history || !getRegion()) {
                return;
            }

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
                toggle.setAttribute('data-i18n', visible ? 'common.hideStatistics' : 'common.showStatistics');
                toggle.textContent = visible ? 'Hide statistics' : 'Show statistics';
                if (window.cre8BackApplyTranslations) { window.cre8BackApplyTranslations(); }
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
