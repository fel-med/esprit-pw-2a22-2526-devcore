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

if (!isset($sessionUser['id']) || (($sessionUser['role'] ?? '') !== 'admin')) {
    header('Location: ../../FrontOffice/offre/login.php');
    exit;
}

function reportLabel($value)
{
    $value = trim((string) $value);

    return match ($value) {
        'brouillon' => 'Draft',
        'envoyee' => 'Sent',
        'en_etude' => 'In review',
        'negociation' => 'Negotiation',
        'acceptee' => 'Accepted',
        'refusee' => 'Refused',
        'retiree' => 'Withdrawn',
        'par_offre' => 'From offers',
        'par_campagne' => 'From campaigns',
        'publiee' => 'Live now',
        'pending' => 'Pending launch',
        'cloturee' => 'Closed',
        'expiree' => 'Expired',
        'archivee' => 'Archived',
        'active' => 'Active',
        'fermee', 'closed' => 'Closed',
        'application' => 'Application',
        'acceptation' => 'Acceptance',
        'refus' => 'Refusal',
        'unknown', '' => 'Unknown',
        default => ucwords(str_replace('_', ' ', $value)),
    };
}

function reportMoney($value)
{
    return 'EUR ' . number_format((float) $value, 2, '.', ',');
}

function reportDate($value, $format = 'Y-m-d')
{
    if (!$value) {
        return 'Not available';
    }

    $timestamp = strtotime((string) $value);

    return $timestamp === false ? (string) $value : date($format, $timestamp);
}

function reportStatRows(array $rows)
{
    if (empty($rows)) {
        return '<tr><td colspan="2" class="empty-cell">No data available.</td></tr>';
    }

    $html = '';
    foreach ($rows as $row) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars(reportLabel($row['label'] ?? 'unknown')) . '</td>';
        $html .= '<td class="number-cell">' . (int) ($row['total'] ?? 0) . '</td>';
        $html .= '</tr>';
    }

    return $html;
}

$error = null;
$generatedAt = date('Y-m-d H:i');
$generatedBy = trim((string) ($sessionUser['nom'] ?? 'Admin')) ?: 'Admin';
$summaryStats = [];
$chartStats = [
    'candidatureStatus' => [],
    'offerStatus' => [],
    'candidatureOrigin' => [],
];
$recentOffers = [];
$recentCandidatures = [];

try {
    $summaryStats = $controller->getAdminReportSummaryStats();
    $chartStats = $controller->getAdminPieChartStats();
    $recentOffers = $controller->getAdminRecentOffers(10);
    $recentCandidatures = $controller->getAdminRecentCandidatures(10);
} catch (Throwable $exception) {
    $error = 'The report could not be generated right now.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cre8Connect Admin Report</title>
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

        .report-toolbar div {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
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
        }
    </style>
</head>
<body>
    <div class="report-toolbar no-print">
        <strong>Admin report preview</strong>
        <div>
            <button type="button" onclick="window.print()">Print / Save as PDF</button>
            <a class="secondary" href="index.php">Back to dashboard</a>
        </div>
    </div>

    <main class="report-page">
        <header class="report-header">
            <div>
                <h1>Cre8Connect Admin Report</h1>
                <p class="report-note">This report gives an overview of the current offer and candidature activity on the platform.</p>
            </div>
            <div class="report-meta">
                <div><strong>Export date:</strong> <?php echo htmlspecialchars($generatedAt); ?></div>
                <div><strong>Generated by:</strong> <?php echo htmlspecialchars($generatedBy); ?></div>
            </div>
        </header>

        <?php if ($error): ?>
            <div class="error-box"><?php echo htmlspecialchars($error); ?></div>
        <?php else: ?>
            <section class="report-section">
                <h2>Global indicators</h2>
                <div class="summary-grid">
                    <div class="summary-card"><span>Real offers</span><strong><?php echo (int) ($summaryStats['totalOffers'] ?? 0); ?></strong></div>
                    <div class="summary-card"><span>Real candidatures</span><strong><?php echo (int) ($summaryStats['totalRealCandidatures'] ?? 0); ?></strong></div>
                    <div class="summary-card"><span>Pending reviews</span><strong><?php echo (int) ($summaryStats['pendingReviews'] ?? 0); ?></strong></div>
                    <div class="summary-card"><span>Open negotiations</span><strong><?php echo (int) ($summaryStats['openNegotiations'] ?? 0); ?></strong></div>
                    <div class="summary-card"><span>Expired offers</span><strong><?php echo (int) ($summaryStats['expiredOffers'] ?? 0); ?></strong></div>
                    <div class="summary-card"><span>Acceptance rate</span><strong><?php echo htmlspecialchars((string) ($summaryStats['acceptanceRate'] ?? 0)); ?>%</strong></div>
                    <div class="summary-card"><span>Activity this week</span><strong><?php echo (int) ($summaryStats['activityThisWeek'] ?? 0); ?></strong></div>
                    <div class="summary-card"><span>Weekly split</span><strong><?php echo (int) ($summaryStats['offersThisWeek'] ?? 0); ?> + <?php echo (int) ($summaryStats['candidaturesThisWeek'] ?? 0); ?></strong></div>
                </div>
            </section>

            <section class="report-section">
                <h2>Statistics tables</h2>
                <div class="stats-grid">
                    <table>
                        <thead><tr><th>Candidature status</th><th>Count</th></tr></thead>
                        <tbody><?php echo reportStatRows($chartStats['candidatureStatus'] ?? []); ?></tbody>
                    </table>
                    <table>
                        <thead><tr><th>Offer status</th><th>Count</th></tr></thead>
                        <tbody><?php echo reportStatRows($chartStats['offerStatus'] ?? []); ?></tbody>
                    </table>
                    <table>
                        <thead><tr><th>Candidature origin</th><th>Count</th></tr></thead>
                        <tbody><?php echo reportStatRows($chartStats['candidatureOrigin'] ?? []); ?></tbody>
                    </table>
                </div>
            </section>

            <section class="report-section">
                <h2>Recent offers</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Brand</th>
                            <th>Budget</th>
                            <th>Deadline</th>
                            <th>Status</th>
                            <th>Publication</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentOffers)): ?>
                            <tr><td colspan="6" class="empty-cell">No recent offers available.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentOffers as $offer): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($offer['titre'] ?? 'Untitled offer'); ?></td>
                                    <td><?php echo htmlspecialchars($offer['brandName'] ?? 'Unknown brand'); ?></td>
                                    <td><?php echo htmlspecialchars(reportMoney($offer['budgetPropose'] ?? 0)); ?></td>
                                    <td><?php echo htmlspecialchars(reportDate($offer['dateLimite'] ?? null)); ?></td>
                                    <td><?php echo htmlspecialchars(reportLabel($offer['statutOffre'] ?? 'unknown')); ?></td>
                                    <td><?php echo htmlspecialchars(reportDate($offer['datePublication'] ?? null)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>

            <section class="report-section">
                <h2>Recent candidatures</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Creator</th>
                            <th>Source type</th>
                            <th>Source</th>
                            <th>Status</th>
                            <th>Response type</th>
                            <th>Budget</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentCandidatures)): ?>
                            <tr><td colspan="7" class="empty-cell">No recent real candidatures available.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentCandidatures as $candidature): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($candidature['creatorName'] ?? ('Creator #' . (int) ($candidature['idCreateur'] ?? 0))); ?></td>
                                    <td><?php echo htmlspecialchars(reportLabel($candidature['origineCandidature'] ?? 'unknown')); ?></td>
                                    <td><?php echo htmlspecialchars($candidature['sourceTitle'] ?? 'Unknown source'); ?></td>
                                    <td><?php echo htmlspecialchars(reportLabel($candidature['statutCandidature'] ?? 'unknown')); ?></td>
                                    <td><?php echo htmlspecialchars(reportLabel($candidature['typeReponse'] ?? 'unknown')); ?></td>
                                    <td><?php echo htmlspecialchars(reportMoney($candidature['budgetPropose'] ?? 0)); ?></td>
                                    <td><?php echo htmlspecialchars(reportDate($candidature['dateCandidature'] ?? null)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
