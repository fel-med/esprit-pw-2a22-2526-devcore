<?php
require_once __DIR__ . '/../../../Controleur/session_helper.php';
require_once __DIR__ . '/../../../Controleur/campagneC.php';

cc_require_admin('../../FrontOffice/utilisateur/login.php');

$campagneC = new CampagneC();
$sessionUser = $_SESSION['utilisateur'] ?? ($_SESSION['user'] ?? []);
$generatedAt = date('Y-m-d H:i');
$generatedBy = trim((string)($sessionUser['nom'] ?? $_SESSION['nom'] ?? $_SESSION['username'] ?? 'Admin')) ?: 'Admin';
$autoPrintReport = (($_GET['download'] ?? '') === 'pdf');
$documentStamp = date('Y-m-d-His');
$error = null;
$campaigns = [];

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function reportMoney($value): string
{
    return 'EUR ' . number_format((float)$value, 2, '.', ',');
}

function reportDate($value): string
{
    if (empty($value)) {
        return 'Not available';
    }
    $time = strtotime((string)$value);
    return $time ? date('Y-m-d', $time) : (string)$value;
}

function campaignStatusLabel($status): string
{
    return match ((string)$status) {
        'active' => 'Active',
        'brouillon' => 'Draft',
        'terminee' => 'Completed',
        'annulee' => 'Cancelled',
        default => ucwords(str_replace('_', ' ', (string)$status ?: 'Unknown')),
    };
}

function statRows(array $rows, string $empty = 'No data available.'): string
{
    if (!$rows) {
        return '<tr><td colspan="2" class="empty-cell">' . h($empty) . '</td></tr>';
    }
    $html = '';
    foreach ($rows as $label => $total) {
        $html .= '<tr><td>' . h($label) . '</td><td class="number-cell">' . h($total) . '</td></tr>';
    }
    return $html;
}

function firstAvailable(array $row, array $keys): string
{
    foreach ($keys as $key) {
        if (array_key_exists($key, $row) && trim((string)$row[$key]) !== '') {
            return (string)$row[$key];
        }
    }
    return '';
}

try {
    $campaigns = $campagneC->afficherToutesCampagnes();
} catch (Throwable $exception) {
    $error = 'The campaign report could not be generated right now.';
}

$totalCampaigns = count($campaigns);
$activeCampaigns = 0;
$draftCampaigns = 0;
$completedCampaigns = 0;
$archivedCampaigns = 0;
$totalBudget = 0.0;
$statusStats = [];
$monthStats = [];
$budgetByStatus = [];
$thisMonth = 0;
$currentMonth = date('Y-m');

foreach ($campaigns as $campaign) {
    $status = (string)($campaign['statut'] ?? 'unknown');
    $statusLabel = campaignStatusLabel($status);
    $budget = (float)($campaign['budget'] ?? 0);
    $totalBudget += $budget;

    if ($status === 'active') $activeCampaigns++;
    if ($status === 'brouillon') $draftCampaigns++;
    if ($status === 'terminee') $completedCampaigns++;
    if (!empty($campaign['estArchive']) || $status === 'terminee') $archivedCampaigns++;

    $statusStats[$statusLabel] = ($statusStats[$statusLabel] ?? 0) + 1;
    $budgetByStatus[$statusLabel] = ($budgetByStatus[$statusLabel] ?? 0) + $budget;

    $dateValue = firstAvailable($campaign, ['dateDebut', 'dateCreation', 'created_at', 'createdAt']);
    $time = $dateValue !== '' ? strtotime($dateValue) : false;
    if ($time) {
        $month = date('Y-m', $time);
        $monthStats[$month] = ($monthStats[$month] ?? 0) + 1;
        if ($month === $currentMonth) $thisMonth++;
    }
}

krsort($monthStats);
$monthStats = array_slice($monthStats, 0, 8, true);
$averageBudget = $totalCampaigns > 0 ? $totalBudget / $totalCampaigns : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cre8Connect Campaign Report</title>
    <style>
        *{box-sizing:border-box}
        body{margin:0;background:#eef2f7;color:#111827;font-family:Arial,Helvetica,sans-serif;line-height:1.45}
        .report-toolbar{position:sticky;top:0;z-index:5;display:flex;justify-content:space-between;gap:1rem;padding:1rem 1.25rem;background:#111827;color:#fff;box-shadow:0 12px 26px rgba(15,23,42,.18)}
        .report-toolbar-actions{display:flex;gap:.75rem;flex-wrap:wrap;align-items:center}
        .report-toolbar a,.report-toolbar button{border:0;border-radius:.55rem;background:#6366f1;color:#fff;padding:.68rem .95rem;font:inherit;font-weight:700;text-decoration:none;cursor:pointer}
        .report-toolbar a.secondary{background:#374151}
        .report-page{width:min(1120px,calc(100% - 2rem));margin:1.25rem auto 2rem;padding:2rem;background:#fff;border-radius:.6rem;box-shadow:0 18px 46px rgba(15,23,42,.14)}
        .report-header{display:flex;justify-content:space-between;gap:1.25rem;border-bottom:2px solid #e5e7eb;padding-bottom:1.1rem}
        .report-header h1{margin:0;color:#312e81;font-size:2rem}
        .report-note{margin:.35rem 0 0;color:#4b5563}.report-meta{min-width:240px;text-align:right;color:#374151;font-size:.9rem}
        .summary-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.85rem;margin:1.4rem 0}
        .summary-card{border:1px solid #e5e7eb;border-radius:.55rem;padding:.9rem;background:#f8fafc}
        .summary-card span{display:block;color:#64748b;font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em}
        .summary-card strong{display:block;margin-top:.45rem;color:#111827;font-size:1.5rem}
        .report-section{margin-top:1.45rem;break-inside:avoid}.report-section h2{margin:0 0 .75rem;color:#1f2937;font-size:1.16rem}
        .stats-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.9rem}
        table{width:100%;border-collapse:collapse;border:1px solid #dbe2ea;background:#fff;font-size:.9rem}
        th,td{padding:.68rem .75rem;border-bottom:1px solid #e5e7eb;text-align:left;vertical-align:top}
        th{background:#eef2ff;color:#3730a3;font-size:.76rem;text-transform:uppercase;letter-spacing:.04em}
        tr:last-child td{border-bottom:0}.number-cell{text-align:right;font-weight:700}.empty-cell{color:#64748b;text-align:center}.error-box{margin:1.25rem 0;padding:1rem;border:1px solid #fecaca;border-radius:.6rem;background:#fef2f2;color:#991b1b}
        @media(max-width:900px){.summary-grid,.stats-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.report-header{flex-direction:column}.report-meta{text-align:left}}
        @media print{@page{size:A4;margin:14mm}body{background:#fff!important;color:#000!important}.no-print{display:none!important}.report-page{width:100%;margin:0;padding:0;border-radius:0;box-shadow:none!important}.summary-grid{grid-template-columns:repeat(4,minmax(0,1fr));gap:3mm;margin:6mm 0}.stats-grid{grid-template-columns:repeat(3,minmax(0,1fr));gap:4mm}.report-header{align-items:flex-start;gap:10mm;padding-bottom:6mm}.report-header h1{font-size:18pt}.report-note,.report-meta{font-size:8.5pt}.report-section{margin-top:8mm;break-inside:avoid-page}.summary-card{padding:4mm;border-radius:2mm;break-inside:avoid}.summary-card span{font-size:6.8pt}.summary-card strong{font-size:13pt}table{table-layout:fixed;width:100%;font-size:7.2pt;break-inside:auto}tr{page-break-inside:avoid;page-break-after:auto}th,td{padding:2.4mm 2.6mm;overflow-wrap:anywhere;word-break:break-word}th{font-size:6.4pt}}
    </style>
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
                <p class="report-note">Overview of current campaign activity on the platform.</p>
            </div>
            <div class="report-meta">
                <div><strong>Export date:</strong> <?= h($generatedAt) ?></div>
                <div><strong>Generated by:</strong> <?= h($generatedBy) ?></div>
            </div>
        </header>

        <?php if ($error): ?>
            <div class="error-box"><?= h($error) ?></div>
        <?php else: ?>
            <section class="report-section">
                <h2>Global indicators</h2>
                <div class="summary-grid">
                    <div class="summary-card"><span>Total campaigns</span><strong><?= $totalCampaigns ?></strong></div>
                    <div class="summary-card"><span>Active campaigns</span><strong><?= $activeCampaigns ?></strong></div>
                    <div class="summary-card"><span>Draft campaigns</span><strong><?= $draftCampaigns ?></strong></div>
                    <div class="summary-card"><span>Completed campaigns</span><strong><?= $completedCampaigns ?></strong></div>
                    <div class="summary-card"><span>Archived campaigns</span><strong><?= $archivedCampaigns ?></strong></div>
                    <div class="summary-card"><span>Total budget</span><strong><?= h(reportMoney($totalBudget)) ?></strong></div>
                    <div class="summary-card"><span>Average budget</span><strong><?= h(reportMoney($averageBudget)) ?></strong></div>
                    <div class="summary-card"><span>Campaigns this month</span><strong><?= $thisMonth ?></strong></div>
                </div>
            </section>

            <section class="report-section">
                <h2>Statistics tables</h2>
                <div class="stats-grid">
                    <table><thead><tr><th>Campaign status</th><th>Count</th></tr></thead><tbody><?= statRows($statusStats) ?></tbody></table>
                    <table><thead><tr><th>Campaign month</th><th>Count</th></tr></thead><tbody><?= statRows($monthStats) ?></tbody></table>
                    <table><thead><tr><th>Budget by status</th><th>Total</th></tr></thead><tbody><?= statRows(array_map('reportMoney', $budgetByStatus)) ?></tbody></table>
                </div>
            </section>

            <section class="report-section">
                <h2>Detailed campaigns</h2>
                <table>
                    <thead><tr><th>ID</th><th>Title</th><th>Status</th><th>Budget</th><th>Start date</th><th>End date</th><th>Brand</th><th>Created date</th></tr></thead>
                    <tbody>
                    <?php if (!$campaigns): ?>
                        <tr><td colspan="8" class="empty-cell">No campaigns available.</td></tr>
                    <?php else: ?>
                        <?php foreach ($campaigns as $campaign): ?>
                            <tr>
                                <td>#<?= h($campaign['idCampagne'] ?? '') ?></td>
                                <td><?= h($campaign['titreCampagne'] ?? 'Untitled campaign') ?></td>
                                <td><?= h(campaignStatusLabel($campaign['statut'] ?? 'unknown')) ?></td>
                                <td><?= h(reportMoney($campaign['budget'] ?? 0)) ?></td>
                                <td><?= h(reportDate($campaign['dateDebut'] ?? null)) ?></td>
                                <td><?= h(reportDate($campaign['dateFin'] ?? null)) ?></td>
                                <td><?= h($campaign['nomMarque'] ?? (($campaign['idMarque'] ?? '') !== '' ? 'Brand #' . $campaign['idMarque'] : 'Not available')) ?></td>
                                <td><?= h(reportDate(firstAvailable($campaign, ['dateCreation', 'created_at', 'createdAt']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </section>
        <?php endif; ?>
    </main>
    <script>
        (function () {
            var shouldAutoPrint = <?= $autoPrintReport ? 'true' : 'false' ?>;
            if (!shouldAutoPrint) return;
            window.addEventListener('load', function () {
                document.title = 'cre8connect-campaign-report-<?= h($documentStamp) ?>';
                window.setTimeout(function () { window.print(); }, 250);
            });
        })();
    </script>
</body>
</html>
