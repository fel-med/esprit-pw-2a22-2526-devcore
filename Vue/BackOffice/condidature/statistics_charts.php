<?php
$adminPieChartStats = is_array($adminPieChartStats ?? null) ? $adminPieChartStats : [];

if (!function_exists('adminChartDisplayLabel')) {
    function adminChartDisplayLabel($value)
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
            'unknown', '' => 'Unknown',
            default => ucwords(str_replace('_', ' ', $value)),
        };
    }
}

$chartPalette = [
    '#818cf8',
    '#22c55e',
    '#f59e0b',
    '#ef476f',
    '#06b6d4',
    '#a78bfa',
    '#f97316',
    '#14b8a6',
    '#e879f9',
];

function adminNormalizeStatsItems(array $items, array $palette): array
{
    $out = [];
    foreach (array_values($items) as $itemIndex => $item) {
        $rawLabel = (string) ($item['label'] ?? 'unknown');
        $out[] = [
            'label' => adminChartDisplayLabel($rawLabel),
            'rawLabel' => $rawLabel,
            'total' => (int) ($item['total'] ?? 0),
            'color' => $palette[$itemIndex % max(1, count($palette))],
        ];
    }

    return $out;
}

$candidatureStatusItems = adminNormalizeStatsItems((array) ($adminPieChartStats['candidatureStatus'] ?? []), $chartPalette);
$offerStatusItems = adminNormalizeStatsItems((array) ($adminPieChartStats['offerStatus'] ?? []), $chartPalette);
$originItems = adminNormalizeStatsItems((array) ($adminPieChartStats['candidatureOrigin'] ?? []), $chartPalette);

$activeStatusKeys = ['publiee', 'pending', 'active', 'brouillon'];
$archivedStatusKeys = ['cloturee', 'expiree', 'archivee', 'fermee', 'closed', 'terminee', 'annulee'];
$activeOfferCount = 0;
$archivedOfferCount = 0;
foreach ($offerStatusItems as $item) {
    $raw = strtolower((string) ($item['rawLabel'] ?? ''));
    if (in_array($raw, $activeStatusKeys, true)) {
        $activeOfferCount += (int) $item['total'];
        continue;
    }
    if (in_array($raw, $archivedStatusKeys, true)) {
        $archivedOfferCount += (int) $item['total'];
        continue;
    }
    if ($raw !== '') {
        $activeOfferCount += (int) $item['total'];
    }
}

$activeArchivedItems = [
    ['label' => 'Active', 'rawLabel' => 'active', 'total' => $activeOfferCount, 'color' => '#22c55e'],
    ['label' => 'Archived', 'rawLabel' => 'archived', 'total' => $archivedOfferCount, 'color' => '#64748b'],
];

$chartCards = [
    [
        'id' => 'adminCandidatureStatusChart',
        'title' => 'Distribution by status',
        'items' => $candidatureStatusItems,
        'type' => 'doughnut',
    ],
    [
        'id' => 'adminOfferLifecycleChart',
        'title' => 'Active vs archived offers',
        'items' => $activeArchivedItems,
        'type' => 'bar',
    ],
    [
        'id' => 'adminOfferStatusVolumeChart',
        'title' => 'Offer volume by status',
        'items' => $offerStatusItems,
        'type' => 'bar',
    ],
];

$chartPayload = [];
foreach ($chartCards as $card) {
    $items = array_values(array_filter((array) ($card['items'] ?? []), static fn ($x) => isset($x['total'])));
    $chartPayload[] = [
        'id' => $card['id'],
        'title' => $card['title'],
        'type' => (string) ($card['type'] ?? 'doughnut'),
        'labels' => array_column($items, 'label'),
        'values' => array_map(static fn ($v) => (int) $v, array_column($items, 'total')),
        'colors' => array_column($items, 'color'),
    ];
}

$totalCandidatures = array_sum(array_map(static fn ($item) => (int) ($item['total'] ?? 0), $candidatureStatusItems));
$totalOffers = array_sum(array_map(static fn ($item) => (int) ($item['total'] ?? 0), $offerStatusItems));
$offerActiveRatio = $totalOffers > 0 ? (int) round(($activeOfferCount / $totalOffers) * 100) : 0;
$offerArchivedRatio = $totalOffers > 0 ? max(0, 100 - $offerActiveRatio) : 0;
?>
<section class="admin-chart-section" aria-labelledby="admin-statistics-title">
    <div class="admin-chart-section-header">
        <div>
            <h2 id="admin-statistics-title">Dynamic Statistics</h2>
            <p>Live operational view across candidature and offer workflows.</p>
        </div>
        <div class="admin-chart-section-actions">
            <a class="admin-report-link" href="../condidature/admin_report.php?download=pdf" data-i18n="collaboration.export.report">Export PDF report</a>
            <button type="button" class="admin-chart-toggle" data-admin-chart-toggle aria-expanded="true">Hide</button>
        </div>
    </div>

    <div class="admin-stats-indicators" data-admin-chart-body>
        <article class="admin-stats-indicator">
            <h3>Total candidatures</h3>
            <p><?php echo (int) $totalCandidatures; ?></p>
            <small>Real creator responses currently tracked.</small>
        </article>
        <article class="admin-stats-indicator">
            <h3>Total offers</h3>
            <p><?php echo (int) $totalOffers; ?></p>
            <small>Targeted invitations across all statuses.</small>
        </article>
        <article class="admin-stats-indicator">
            <h3>Offer lifecycle split</h3>
            <p><?php echo $offerActiveRatio; ?>% / <?php echo $offerArchivedRatio; ?>%</p>
            <small>Active vs archived offer balance.</small>
        </article>
    </div>

    <div class="admin-chart-grid" data-admin-chart-body>
        <?php foreach ($chartCards as $card): ?>
            <?php $items = $card['items']; ?>
            <article class="admin-chart-card">
                <h3><?php echo htmlspecialchars($card['title']); ?></h3>

                <?php if (empty($items)): ?>
                    <div class="admin-chart-empty">No statistics available yet.</div>
                <?php else: ?>
                    <div class="admin-chart-canvas-wrap">
                        <canvas id="<?php echo htmlspecialchars($card['id']); ?>" aria-label="<?php echo htmlspecialchars($card['title']); ?>"></canvas>
                    </div>

                    <ul class="admin-chart-legend">
                        <?php foreach ($items as $item): ?>
                            <li>
                                <span class="admin-chart-legend-label">
                                    <span class="admin-chart-dot" style="--chart-color: <?php echo htmlspecialchars($item['color']); ?>"></span>
                                    <?php echo htmlspecialchars($item['label']); ?>
                                </span>
                                <strong><?php echo (int) $item['total']; ?></strong>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(() => {
    const chartStats = <?php echo json_encode($chartPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    const toggleButton = document.querySelector('[data-admin-chart-toggle]');
    const bodyBlocks = document.querySelectorAll('[data-admin-chart-body]');

    if (toggleButton && bodyBlocks.length > 0) {
        toggleButton.addEventListener('click', () => {
            const isOpen = toggleButton.getAttribute('aria-expanded') === 'true';
            const nextOpen = !isOpen;
            bodyBlocks.forEach((node) => {
                node.hidden = !nextOpen;
            });
            toggleButton.setAttribute('aria-expanded', nextOpen ? 'true' : 'false');
            toggleButton.textContent = nextOpen ? 'Hide' : 'Show';
        });
    }

    if (typeof Chart === 'undefined' || !Array.isArray(chartStats)) {
        return;
    }

    chartStats.forEach((chart) => {
        const canvas = document.getElementById(chart.id);
        const values = Array.isArray(chart.values) ? chart.values : [];

        if (!canvas || values.length === 0 || values.every((value) => Number(value) === 0)) {
            return;
        }

        const rootStyles = getComputedStyle(document.documentElement);
        const textColor = rootStyles.getPropertyValue('--text-color').trim() || '#e2e8f0';
        const cardColor = rootStyles.getPropertyValue('--card-bg').trim() || '#111827';
        const borderColor = rootStyles.getPropertyValue('--border-color').trim() || 'rgba(148, 163, 184, 0.2)';
        const type = chart.type === 'bar' ? 'bar' : 'doughnut';
        new Chart(canvas, {
            type,
            data: {
                labels: chart.labels,
                datasets: [{
                    data: values,
                    backgroundColor: chart.colors,
                    borderColor: type === 'doughnut' ? cardColor : borderColor,
                    borderWidth: type === 'doughnut' ? 2 : 1,
                    hoverOffset: 6,
                    borderRadius: type === 'bar' ? 8 : 0,
                    maxBarThickness: type === 'bar' ? 44 : undefined,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: type === 'doughnut' ? '64%' : undefined,
                scales: type === 'bar' ? {
                    x: {
                        ticks: {
                            color: textColor,
                            font: {
                                size: 11,
                                weight: '600',
                            },
                        },
                        grid: {
                            color: borderColor,
                        },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: textColor,
                            precision: 0,
                            font: {
                                size: 11,
                                weight: '600',
                            },
                        },
                        grid: {
                            color: borderColor,
                        },
                    },
                } : undefined,
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        backgroundColor: cardColor,
                        borderColor,
                        borderWidth: 1,
                        titleColor: textColor,
                        bodyColor: textColor,
                        callbacks: {
                            label: (context) => `${context.label}: ${context.parsed}`,
                        },
                    },
                },
            },
        });
    });
})();
</script>
