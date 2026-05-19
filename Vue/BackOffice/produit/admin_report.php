<?php
require_once __DIR__ . '/../../../Controleur/session_helper.php';
require_once __DIR__ . '/../../../Controleur/produitC.php';

cc_require_admin('../../FrontOffice/utilisateur/login.php');

$produitC = new ProduitC();
$sessionUser = $_SESSION['utilisateur'] ?? ($_SESSION['user'] ?? []);
$generatedAt = date('Y-m-d H:i');
$generatedBy = trim((string)($sessionUser['nom'] ?? $_SESSION['nom'] ?? $_SESSION['username'] ?? 'Admin')) ?: 'Admin';
$autoPrintReport = (($_GET['download'] ?? '') === 'pdf');
$documentStamp = date('Y-m-d-His');
$error = null;
$activeProducts = [];
$archivedProducts = [];

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
    $activeProducts = $produitC->afficherProduits();
    $archivedProducts = $produitC->afficherProduitsArchives();
} catch (Throwable $exception) {
    $error = 'The product report could not be generated right now.';
}

$allProducts = array_merge($activeProducts, $archivedProducts);
$totalProducts = count($allProducts);
$activeCount = count($activeProducts);
$archivedCount = count($archivedProducts);
$pinnedCount = 0;
$totalValue = 0.0;
$categoryStats = [];
$brandStats = [];
$statusStats = ['Active' => $activeCount, 'Archived' => $archivedCount];
$priceSummary = [];
$thisMonth = 0;
$currentMonth = date('Y-m');

foreach ($allProducts as $product) {
    $price = (float)($product['prix'] ?? 0);
    $totalValue += $price;
    if (!empty($product['estEpingle'])) $pinnedCount++;

    $category = trim((string)($product['categorie'] ?? ''));
    $category = $category !== '' ? $category : 'Uncategorized';
    $categoryStats[$category] = ($categoryStats[$category] ?? 0) + 1;

    $brand = trim((string)($product['nomMarque'] ?? ''));
    if ($brand === '' && !empty($product['idMarque'])) {
        $brand = 'Brand #' . $product['idMarque'];
    }
    $brand = $brand !== '' ? $brand : 'Not available';
    $brandStats[$brand] = ($brandStats[$brand] ?? 0) + 1;

    $dateValue = firstAvailable($product, ['dateCreation', 'created_at', 'createdAt', 'dateAjout']);
    $time = $dateValue !== '' ? strtotime($dateValue) : false;
    if ($time && date('Y-m', $time) === $currentMonth) $thisMonth++;
}

arsort($categoryStats);
arsort($brandStats);
$categoryStats = array_slice($categoryStats, 0, 8, true);
$brandStats = array_slice($brandStats, 0, 8, true);
$averagePrice = $totalProducts > 0 ? $totalValue / $totalProducts : 0;
$prices = array_map(fn($product) => (float)($product['prix'] ?? 0), $allProducts);
$priceSummary = [
    'Total catalogue value' => reportMoney($totalValue),
    'Average price' => reportMoney($averagePrice),
    'Highest price' => reportMoney($prices ? max($prices) : 0),
    'Lowest price' => reportMoney($prices ? min($prices) : 0),
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cre8Connect Product Report</title>
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
        .stats-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.9rem}
        table{width:100%;border-collapse:collapse;border:1px solid #dbe2ea;background:#fff;font-size:.9rem}
        th,td{padding:.68rem .75rem;border-bottom:1px solid #e5e7eb;text-align:left;vertical-align:top}
        th{background:#eef2ff;color:#3730a3;font-size:.76rem;text-transform:uppercase;letter-spacing:.04em}
        tr:last-child td{border-bottom:0}.number-cell{text-align:right;font-weight:700}.empty-cell{color:#64748b;text-align:center}.error-box{margin:1.25rem 0;padding:1rem;border:1px solid #fecaca;border-radius:.6rem;background:#fef2f2;color:#991b1b}
        @media(max-width:1000px){.summary-grid,.stats-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.report-header{flex-direction:column}.report-meta{text-align:left}}
        @media print{@page{size:A4;margin:14mm}body{background:#fff!important;color:#000!important}.no-print{display:none!important}.report-page{width:100%;margin:0;padding:0;border-radius:0;box-shadow:none!important}.summary-grid{grid-template-columns:repeat(4,minmax(0,1fr));gap:3mm;margin:6mm 0}.stats-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:4mm}.report-header{align-items:flex-start;gap:10mm;padding-bottom:6mm}.report-header h1{font-size:18pt}.report-note,.report-meta{font-size:8.5pt}.report-section{margin-top:8mm;break-inside:avoid-page}.summary-card{padding:4mm;border-radius:2mm;break-inside:avoid}.summary-card span{font-size:6.8pt}.summary-card strong{font-size:13pt}table{table-layout:fixed;width:100%;font-size:7.2pt;break-inside:auto}tr{page-break-inside:avoid;page-break-after:auto}th,td{padding:2.4mm 2.6mm;overflow-wrap:anywhere;word-break:break-word}th{font-size:6.4pt}}
    </style>
</head>
<body>
    <div class="report-toolbar no-print">
        <strong>Product report preview</strong>
        <div class="report-toolbar-actions">
            <button type="button" onclick="window.print()">Save PDF</button>
            <a class="secondary" href="index.php">Back to products</a>
        </div>
    </div>
    <main class="report-page">
        <header class="report-header">
            <div>
                <h1>Cre8Connect Product Report</h1>
                <p class="report-note">Overview of current product catalogue and moderation state.</p>
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
                    <div class="summary-card"><span>Total products</span><strong><?= $totalProducts ?></strong></div>
                    <div class="summary-card"><span>Active products</span><strong><?= $activeCount ?></strong></div>
                    <div class="summary-card"><span>Archived products</span><strong><?= $archivedCount ?></strong></div>
                    <div class="summary-card"><span>Pinned products</span><strong><?= $pinnedCount ?></strong></div>
                    <div class="summary-card"><span>Total value</span><strong><?= h(reportMoney($totalValue)) ?></strong></div>
                    <div class="summary-card"><span>Average price</span><strong><?= h(reportMoney($averagePrice)) ?></strong></div>
                    <div class="summary-card"><span>Products this month</span><strong><?= $thisMonth ?></strong></div>
                    <div class="summary-card"><span>Categories</span><strong><?= count($categoryStats) ?></strong></div>
                </div>
            </section>

            <section class="report-section">
                <h2>Statistics tables</h2>
                <div class="stats-grid">
                    <table><thead><tr><th>Product status</th><th>Count</th></tr></thead><tbody><?= statRows($statusStats) ?></tbody></table>
                    <table><thead><tr><th>Category</th><th>Count</th></tr></thead><tbody><?= statRows($categoryStats) ?></tbody></table>
                    <table><thead><tr><th>Brand</th><th>Count</th></tr></thead><tbody><?= statRows($brandStats) ?></tbody></table>
                    <table><thead><tr><th>Price summary</th><th>Value</th></tr></thead><tbody><?= statRows($priceSummary) ?></tbody></table>
                </div>
            </section>

            <section class="report-section">
                <h2>Detailed products</h2>
                <table>
                    <thead><tr><th>ID</th><th>Product name</th><th>Brand</th><th>Category</th><th>Price</th><th>Status</th><th>Pinned</th><th>Created date</th></tr></thead>
                    <tbody>
                    <?php if (!$allProducts): ?>
                        <tr><td colspan="8" class="empty-cell">No products available.</td></tr>
                    <?php else: ?>
                        <?php foreach ($allProducts as $product): ?>
                            <?php
                                $brand = trim((string)($product['nomMarque'] ?? ''));
                                if ($brand === '' && !empty($product['idMarque'])) $brand = 'Brand #' . $product['idMarque'];
                            ?>
                            <tr>
                                <td>#<?= h($product['idProduit'] ?? '') ?></td>
                                <td><?= h($product['nomProduit'] ?? 'Untitled product') ?></td>
                                <td><?= h($brand !== '' ? $brand : 'Not available') ?></td>
                                <td><?= h(trim((string)($product['categorie'] ?? '')) !== '' ? $product['categorie'] : 'Uncategorized') ?></td>
                                <td><?= h(reportMoney($product['prix'] ?? 0)) ?></td>
                                <td><?= !empty($product['estArchive']) ? 'Archived' : 'Active' ?></td>
                                <td><?= !empty($product['estEpingle']) ? 'Yes' : 'No' ?></td>
                                <td><?= h(reportDate(firstAvailable($product, ['dateCreation', 'created_at', 'createdAt', 'dateAjout']))) ?></td>
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
                document.title = 'cre8connect-product-report-<?= h($documentStamp) ?>';
                window.setTimeout(function () { window.print(); }, 250);
            });
        })();
    </script>
</body>
</html>
