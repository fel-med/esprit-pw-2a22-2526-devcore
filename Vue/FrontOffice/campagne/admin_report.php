<?php
session_start();

require_once __DIR__ . '/../../../Controleur/session_helper.php';
require_once __DIR__ . '/../../../Controleur/campagneC.php';
require_once __DIR__ . '/../../../Controleur/produitC.php';

$sessionUser = $_SESSION['utilisateur'] ?? ($_SESSION['user'] ?? []);

if (!isset($sessionUser['id']) || !isBackOfficeRole(cc_current_user_role())) {
    header('Location: ../../FrontOffice/utilisateur/login.php');
    exit;
}


function businessReportValue($value, string $fallback = '—'): string
{
    $value = trim((string) $value);
    return $value !== '' ? $value : $fallback;
}

function businessReportLabel($value): string
{
    $value = trim((string) $value);
    return match ($value) {
        'brouillon' => 'Draft',
        'active' => 'Active',
        'terminee' => 'Completed',
        'annulee' => 'Cancelled',
        'archivee' => 'Archived',
        'publiee' => 'Published',
        'pending' => 'Pending',
        'cloturee' => 'Closed',
        'expiree' => 'Expired',
        default => $value !== '' ? ucfirst(str_replace('_', ' ', $value)) : 'Not set',
    };
}

function businessReportMoney($value): string
{
    if ($value === null || $value === '') {
        return '—';
    }

    return number_format((float) $value, 2, '.', ' ') . ' €';
}

function businessReportDate($value): string
{
    $value = trim((string) $value);
    if ($value === '') {
        return '—';
    }

    $timestamp = strtotime($value);
    return $timestamp ? date('Y-m-d', $timestamp) : $value;
}

function businessReportText($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function businessReportPercent(int $count, int $total): string
{
    if ($total <= 0) {
        return '0%';
    }

    return number_format(($count / $total) * 100, 1) . '%';
}

function businessReportTopRows(array $rows, int $limit = 8): array
{
    return array_slice($rows, 0, $limit, true);
}


$controller = new CampagneC();
$productController = new ProduitC();
$generatedAt = date('Y-m-d H:i');
$generatedBy = trim(($sessionUser['prenom'] ?? '') . ' ' . ($sessionUser['nom'] ?? ''));
if ($generatedBy === '') {
    $generatedBy = $sessionUser['username'] ?? $sessionUser['email'] ?? 'BackOffice admin';
}

$error = null;
$allCampaigns = [];
$activeCampaigns = [];
$archivedCampaigns = [];
$statusRows = [];
$brandRows = [];
$budgetRows = [];
$recentCampaigns = [];

try {
    $allCampaigns = $controller->afficherToutesCampagnes();
    $activeCampaigns = $controller->afficherCampagnes();
    $archivedCampaigns = $controller->afficherCampagnesArchives();

    $totalAll = count($allCampaigns);
    $budgetTotal = array_sum(array_map(static fn($item) => (float) ($item['budget'] ?? 0), $activeCampaigns));
    $activeCount = count(array_filter($activeCampaigns, static fn($item) => ($item['statut'] ?? '') === 'active'));
    $draftCount = count(array_filter($activeCampaigns, static fn($item) => ($item['statut'] ?? '') === 'brouillon'));
    $completedCount = count(array_filter($allCampaigns, static fn($item) => ($item['statut'] ?? '') === 'terminee'));
    $archivedCount = count($archivedCampaigns);

    foreach ($allCampaigns as $campaign) {
        $status = businessReportLabel($campaign['statut'] ?? '');
        $brand = businessReportValue($campaign['nomMarque'] ?? '', 'Unknown brand');
        $budgetKey = $campaign['statut'] ?? 'unknown';

        $statusRows[$status] = ($statusRows[$status] ?? 0) + 1;
        $brandRows[$brand] = ($brandRows[$brand] ?? 0) + 1;
        $budgetRows[$budgetKey] = ($budgetRows[$budgetKey] ?? 0) + (float) ($campaign['budget'] ?? 0);
    }

    arsort($statusRows);
    arsort($brandRows);
    arsort($budgetRows);
    $recentCampaigns = businessReportTopRows($allCampaigns, 12);
} catch (Throwable $exception) {
    $error = 'The campaign report could not be generated right now.';
    $totalAll = $budgetTotal = $activeCount = $draftCount = $completedCount = $archivedCount = 0;
}

$autoPrintReport = (($_GET['download'] ?? '') === 'pdf');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cre8Connect Campaign Report</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #eef2f7;
            color: #111827;
            font-family: Arial, Helvetica, sans-serif;
            line-height: 1.45;
        }

        .report-toolbar {
            position: sticky;
            top: 0;
            z-index: 5;
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem 1.25rem;
            background: #111827;
            color: #ffffff;
            box-shadow: 0 12px 26px rgba(15, 23, 42, 0.18);
        }

        .report-toolbar div,
        .report-toolbar-actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .report-toolbar a,
        .report-toolbar button {
            border: 0;
            border-radius: 0.55rem;
            background: #6366f1;
            color: #ffffff;
            padding: 0.68rem 0.95rem;
            font: inherit;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }

        .report-toolbar a.secondary {
            background: #374151;
        }

        .report-page {
            width: min(1120px, calc(100% - 2rem));
            margin: 1.25rem auto 2rem;
            padding: 2rem;
            background: #ffffff;
            border-radius: 0.6rem;
            box-shadow: 0 18px 46px rgba(15, 23, 42, 0.14);
        }

        .report-header {
            display: flex;
            justify-content: space-between;
            gap: 1.25rem;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 1.1rem;
        }

        .report-header h1 {
            margin: 0;
            color: #312e81;
            font-size: 2rem;
        }

        .report-header p,
        .report-note {
            margin: 0.35rem 0 0;
            color: #4b5563;
        }

        .report-meta {
            min-width: 240px;
            text-align: right;
            color: #374151;
            font-size: 0.9rem;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.85rem;
            margin: 1.4rem 0;
        }

        .summary-card {
            border: 1px solid #e5e7eb;
            border-radius: 0.55rem;
            padding: 0.9rem;
            background: #f8fafc;
        }

        .summary-card span {
            display: block;
            color: #64748b;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .summary-card strong {
            display: block;
            margin-top: 0.45rem;
            color: #111827;
            font-size: 1.5rem;
        }

        .report-section {
            margin-top: 1.45rem;
            break-inside: avoid;
        }

        .report-section h2 {
            margin: 0 0 0.75rem;
            color: #1f2937;
            font-size: 1.16rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.9rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #dbe2ea;
            background: #ffffff;
            font-size: 0.9rem;
        }

        th,
        td {
            padding: 0.68rem 0.75rem;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #eef2ff;
            color: #3730a3;
            font-size: 0.76rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        tr:last-child td {
            border-bottom: 0;
        }

        .number-cell {
            text-align: right;
            font-weight: 700;
        }

        .empty-cell {
            color: #64748b;
            text-align: center;
        }

        .error-box {
            margin: 1.25rem 0;
            padding: 1rem;
            border: 1px solid #fecaca;
            border-radius: 0.6rem;
            background: #fef2f2;
            color: #991b1b;
        }

        @media (max-width: 900px) {
            .summary-grid,
            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .report-header {
                flex-direction: column;
            }

            .report-meta {
                text-align: left;
            }
        }

        @media print {
            @page {
                size: A4;
                margin: 14mm;
            }

            body {
                background: #ffffff !important;
                color: #000000 !important;
            }

            .no-print {
                display: none !important;
            }

            .report-page {
                width: 100%;
                margin: 0;
                padding: 0;
                border-radius: 0;
                box-shadow: none !important;
            }

            .summary-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }

            .stats-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            table {
                page-break-inside: auto;
            }

            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }

            .report-header {
                align-items: flex-start;
                gap: 10mm;
                padding-bottom: 6mm;
            }

            .report-header h1 {
                font-size: 18pt;
            }

            .report-note,
            .report-meta {
                font-size: 8.5pt;
            }

            .report-section {
                margin-top: 8mm;
                break-inside: avoid-page;
            }

            .summary-grid {
                gap: 3mm;
                margin: 6mm 0;
            }

            .summary-card {
                padding: 4mm;
                border-radius: 2mm;
                break-inside: avoid;
            }

            .summary-card span {
                font-size: 6.8pt;
            }

            .summary-card strong {
                font-size: 13pt;
            }

            .stats-grid {
                gap: 4mm;
            }

            table {
                table-layout: fixed;
                width: 100%;
                font-size: 7.2pt;
                break-inside: auto;
            }

            th,
            td {
                padding: 2.4mm 2.6mm;
                overflow-wrap: anywhere;
                word-break: break-word;
            }

            th {
                font-size: 6.4pt;
            }
        }
    </style>
    <link rel="icon" type="image/png" href="../../public/images/favicon-32x32.png">
</head>
<body>
    <div class="report-toolbar no-print">
        <strong>Campaign report preview</strong>
        <div class="report-toolbar-actions">
            <button type="button" onclick="window.print()">Save PDF</button>
            <a class="secondary" href="index.php">Back to campaigns</a>
        </div>
    </div>

    <main class="report-page">
        <header class="report-header">
            <div>
                <h1>Cre8Connect Campaign Report</h1>
                <p class="report-note">This report summarizes campaign activity, status distribution, budgets and recent campaign records.</p>
            </div>
            <div class="report-meta">
                <div><strong>Export date:</strong> <?= businessReportText($generatedAt) ?></div>
                <div><strong>Generated by:</strong> <?= businessReportText($generatedBy) ?></div>
                <div><strong>Module:</strong> Business Center / Campaigns</div>
            </div>
        </header>

        <?php if ($error): ?>
            <div class="error-box"><?= businessReportText($error) ?></div>
        <?php endif; ?>

        <section class="summary-grid" aria-label="Campaign summary">
            <article class="summary-card"><span>Total campaigns</span><strong><?= (int) $totalAll ?></strong></article>
            <article class="summary-card"><span>Active now</span><strong><?= (int) $activeCount ?></strong></article>
            <article class="summary-card"><span>Drafts</span><strong><?= (int) $draftCount ?></strong></article>
            <article class="summary-card"><span>Completed</span><strong><?= (int) $completedCount ?></strong></article>
            <article class="summary-card"><span>Archived</span><strong><?= (int) $archivedCount ?></strong></article>
            <article class="summary-card"><span>Total budget</span><strong><?= businessReportMoney($budgetTotal) ?></strong></article>
            <article class="summary-card"><span>Brands involved</span><strong><?= count($brandRows) ?></strong></article>
            <article class="summary-card"><span>Completion share</span><strong><?= businessReportPercent((int) $completedCount, (int) $totalAll) ?></strong></article>
        </section>

        <section class="report-section">
            <h2>Campaign distribution</h2>
            <div class="stats-grid">
                <table>
                    <thead><tr><th>Status</th><th class="number-cell">Campaigns</th></tr></thead>
                    <tbody>
                    <?php if ($statusRows): foreach ($statusRows as $label => $total): ?>
                        <tr><td><?= businessReportText($label) ?></td><td class="number-cell"><?= (int) $total ?></td></tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="2" class="empty-cell">No status data available.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>

                <table>
                    <thead><tr><th>Brand</th><th class="number-cell">Campaigns</th></tr></thead>
                    <tbody>
                    <?php if ($brandRows): foreach (businessReportTopRows($brandRows, 8) as $label => $total): ?>
                        <tr><td><?= businessReportText($label) ?></td><td class="number-cell"><?= (int) $total ?></td></tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="2" class="empty-cell">No brand data available.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>

                <table>
                    <thead><tr><th>Status</th><th class="number-cell">Budget</th></tr></thead>
                    <tbody>
                    <?php if ($budgetRows): foreach ($budgetRows as $label => $total): ?>
                        <tr><td><?= businessReportText(businessReportLabel($label)) ?></td><td class="number-cell"><?= businessReportMoney($total) ?></td></tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="2" class="empty-cell">No budget data available.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="report-section">
            <h2>Recent campaigns</h2>
            <table>
                <thead>
                    <tr>
                        <th>Campaign</th>
                        <th>Brand</th>
                        <th>Status</th>
                        <th>Dates</th>
                        <th class="number-cell">Budget</th>
                        <th class="number-cell">Products</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($recentCampaigns): foreach ($recentCampaigns as $campaign): ?>
                    <?php $campaignId = (int) ($campaign['idCampagne'] ?? 0); ?>
                    <tr>
                        <td>
                            <strong><?= businessReportText($campaign['titreCampagne'] ?? 'Untitled campaign') ?></strong><br>
                            <span><?= businessReportText(businessReportValue($campaign['objectif'] ?? '', 'No objective shared')) ?></span>
                        </td>
                        <td><?= businessReportText(businessReportValue($campaign['nomMarque'] ?? '', 'Unknown brand')) ?></td>
                        <td><?= businessReportText(businessReportLabel($campaign['statut'] ?? '')) ?></td>
                        <td><?= businessReportText(businessReportDate($campaign['dateDebut'] ?? null)) ?> → <?= businessReportText(businessReportDate($campaign['dateFin'] ?? null)) ?></td>
                        <td class="number-cell"><?= businessReportMoney($campaign['budget'] ?? 0) ?></td>
                        <td class="number-cell"><?= $campaignId > 0 ? (int) $controller->compterProduitsCampagne($campaignId) : 0 ?></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="6" class="empty-cell">No campaigns available.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </section>

        <section class="report-section">
            <h2>Archived / completed campaigns</h2>
            <table>
                <thead>
                    <tr>
                        <th>Campaign</th>
                        <th>Brand</th>
                        <th>Status</th>
                        <th>End date</th>
                        <th class="number-cell">Budget</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($archivedCampaigns): foreach (businessReportTopRows($archivedCampaigns, 10) as $campaign): ?>
                    <tr>
                        <td><?= businessReportText($campaign['titreCampagne'] ?? 'Untitled campaign') ?></td>
                        <td><?= businessReportText(businessReportValue($campaign['nomMarque'] ?? '', 'Unknown brand')) ?></td>
                        <td><?= businessReportText(businessReportLabel($campaign['statut'] ?? '')) ?></td>
                        <td><?= businessReportText(businessReportDate($campaign['dateFin'] ?? null)) ?></td>
                        <td class="number-cell"><?= businessReportMoney($campaign['budget'] ?? 0) ?></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="5" class="empty-cell">No archived campaign available.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>

    <?php if ($autoPrintReport): ?>
    <script>
        window.addEventListener('load', function () {
            window.print();
        });
    </script>
    <?php endif; ?>
</body>
</html>
