<?php
session_start();

require_once __DIR__ . '/../../../Controleur/session_helper.php';
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


$controller = new ProduitC();
$generatedAt = date('Y-m-d H:i');
$generatedBy = trim(($sessionUser['prenom'] ?? '') . ' ' . ($sessionUser['nom'] ?? ''));
if ($generatedBy === '') {
    $generatedBy = $sessionUser['username'] ?? $sessionUser['email'] ?? 'BackOffice admin';
}

$error = null;
$activeProducts = [];
$archivedProducts = [];
$allProducts = [];
$categoryRows = [];
$brandRows = [];
$priceRows = [];
$recentProducts = [];

try {
    $activeProducts = $controller->afficherProduits();
    $archivedProducts = $controller->afficherProduitsArchives();
    $allProducts = array_merge($activeProducts, $archivedProducts);

    $totalActive = count($activeProducts);
    $totalArchived = count($archivedProducts);
    $pinnedCount = count(array_filter($activeProducts, static fn($item) => !empty($item['estEpingle'])));
    $withoutImage = count(array_filter($activeProducts, static fn($item) => empty($item['image'])));
    $catalogValue = array_sum(array_map(static fn($item) => (float) ($item['prix'] ?? 0), $activeProducts));
    $avgPrice = $totalActive > 0 ? $catalogValue / $totalActive : 0;
    $highestPrice = $totalActive > 0 ? max(array_map(static fn($item) => (float) ($item['prix'] ?? 0), $activeProducts)) : 0;

    foreach ($allProducts as $product) {
        $category = businessReportValue($product['categorie'] ?? '', 'Uncategorized');
        $brand = businessReportValue($product['nomMarque'] ?? '', 'Unknown brand');

        $categoryRows[$category] = ($categoryRows[$category] ?? 0) + 1;
        $brandRows[$brand] = ($brandRows[$brand] ?? 0) + 1;
        $priceRows[$category] = ($priceRows[$category] ?? 0) + (float) ($product['prix'] ?? 0);
    }

    arsort($categoryRows);
    arsort($brandRows);
    arsort($priceRows);
    $recentProducts = businessReportTopRows($activeProducts, 12);
} catch (Throwable $exception) {
    $error = 'The product report could not be generated right now.';
    $totalActive = $totalArchived = $pinnedCount = $withoutImage = 0;
    $catalogValue = $avgPrice = $highestPrice = 0;
}

$autoPrintReport = (($_GET['download'] ?? '') === 'pdf');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cre8Connect Product Report</title>
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
                <p class="report-note">This report summarizes product catalog health, categories, brands, archived products and pricing indicators.</p>
            </div>
            <div class="report-meta">
                <div><strong>Export date:</strong> <?= businessReportText($generatedAt) ?></div>
                <div><strong>Generated by:</strong> <?= businessReportText($generatedBy) ?></div>
                <div><strong>Module:</strong> Business Center / Products</div>
            </div>
        </header>

        <?php if ($error): ?>
            <div class="error-box"><?= businessReportText($error) ?></div>
        <?php endif; ?>

        <section class="summary-grid" aria-label="Product summary">
            <article class="summary-card"><span>Active products</span><strong><?= (int) $totalActive ?></strong></article>
            <article class="summary-card"><span>Archived products</span><strong><?= (int) $totalArchived ?></strong></article>
            <article class="summary-card"><span>Pinned products</span><strong><?= (int) $pinnedCount ?></strong></article>
            <article class="summary-card"><span>Without image</span><strong><?= (int) $withoutImage ?></strong></article>
            <article class="summary-card"><span>Catalog value</span><strong><?= businessReportMoney($catalogValue) ?></strong></article>
            <article class="summary-card"><span>Average price</span><strong><?= businessReportMoney($avgPrice) ?></strong></article>
            <article class="summary-card"><span>Highest price</span><strong><?= businessReportMoney($highestPrice) ?></strong></article>
            <article class="summary-card"><span>Categories</span><strong><?= count($categoryRows) ?></strong></article>
        </section>

        <section class="report-section">
            <h2>Catalog distribution</h2>
            <div class="stats-grid">
                <table>
                    <thead><tr><th>Category</th><th class="number-cell">Products</th></tr></thead>
                    <tbody>
                    <?php if ($categoryRows): foreach (businessReportTopRows($categoryRows, 8) as $label => $total): ?>
                        <tr><td><?= businessReportText($label) ?></td><td class="number-cell"><?= (int) $total ?></td></tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="2" class="empty-cell">No category data available.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>

                <table>
                    <thead><tr><th>Brand</th><th class="number-cell">Products</th></tr></thead>
                    <tbody>
                    <?php if ($brandRows): foreach (businessReportTopRows($brandRows, 8) as $label => $total): ?>
                        <tr><td><?= businessReportText($label) ?></td><td class="number-cell"><?= (int) $total ?></td></tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="2" class="empty-cell">No brand data available.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>

                <table>
                    <thead><tr><th>Category</th><th class="number-cell">Value</th></tr></thead>
                    <tbody>
                    <?php if ($priceRows): foreach (businessReportTopRows($priceRows, 8) as $label => $total): ?>
                        <tr><td><?= businessReportText($label) ?></td><td class="number-cell"><?= businessReportMoney($total) ?></td></tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="2" class="empty-cell">No price data available.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="report-section">
            <h2>Active product catalog</h2>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Brand</th>
                        <th>Category</th>
                        <th class="number-cell">Price</th>
                        <th>Pinned</th>
                        <th>Availability</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($recentProducts): foreach ($recentProducts as $product): ?>
                    <tr>
                        <td>
                            <strong><?= businessReportText($product['nomProduit'] ?? 'Untitled product') ?></strong><br>
                            <span><?= businessReportText(businessReportValue($product['description'] ?? '', 'No description shared')) ?></span>
                        </td>
                        <td><?= businessReportText(businessReportValue($product['nomMarque'] ?? '', 'Unknown brand')) ?></td>
                        <td><?= businessReportText(businessReportValue($product['categorie'] ?? '', 'Uncategorized')) ?></td>
                        <td class="number-cell"><?= businessReportMoney($product['prix'] ?? 0) ?></td>
                        <td><?= !empty($product['estEpingle']) ? 'Yes' : 'No' ?></td>
                        <td><?= businessReportText(businessReportDate($product['dateDisponibilite'] ?? null)) ?></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="6" class="empty-cell">No active products available.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </section>

        <section class="report-section">
            <h2>Archived products</h2>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Brand</th>
                        <th>Category</th>
                        <th class="number-cell">Price</th>
                        <th>Availability</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($archivedProducts): foreach (businessReportTopRows($archivedProducts, 10) as $product): ?>
                    <tr>
                        <td><?= businessReportText($product['nomProduit'] ?? 'Untitled product') ?></td>
                        <td><?= businessReportText(businessReportValue($product['nomMarque'] ?? '', 'Unknown brand')) ?></td>
                        <td><?= businessReportText(businessReportValue($product['categorie'] ?? '', 'Uncategorized')) ?></td>
                        <td class="number-cell"><?= businessReportMoney($product['prix'] ?? 0) ?></td>
                        <td><?= businessReportText(businessReportDate($product['dateDisponibilite'] ?? null)) ?></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="5" class="empty-cell">No archived products available.</td></tr>
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
