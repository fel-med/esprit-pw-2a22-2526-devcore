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

$chartCards = [
    [
        'id' => 'adminCandidatureStatusChart',
        'title' => 'Candidatures by status',
        'items' => $adminPieChartStats['candidatureStatus'] ?? [],
    ],
    [
        'id' => 'adminOfferStatusChart',
        'title' => 'Offers by status',
        'items' => $adminPieChartStats['offerStatus'] ?? [],
    ],
    [
        'id' => 'adminCandidatureOriginChart',
        'title' => 'Candidatures by origin',
        'items' => $adminPieChartStats['candidatureOrigin'] ?? [],
    ],
];

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

$chartPayload = [];
foreach ($chartCards as $cardIndex => $card) {
    $items = [];
    foreach ((array) ($card['items'] ?? []) as $itemIndex => $item) {
        $rawLabel = (string) ($item['label'] ?? 'unknown');
        $items[] = [
            'label' => adminChartDisplayLabel($rawLabel),
            'rawLabel' => $rawLabel,
            'total' => (int) ($item['total'] ?? 0),
            'color' => $chartPalette[$itemIndex % count($chartPalette)],
        ];
    }

    $chartCards[$cardIndex]['items'] = $items;
    $chartPayload[] = [
        'id' => $card['id'],
        'title' => $card['title'],
        'labels' => array_column($items, 'label'),
        'values' => array_column($items, 'total'),
        'colors' => array_column($items, 'color'),
    ];
}
?>
<section class="admin-chart-section" aria-labelledby="admin-statistics-title">
    <div class="admin-chart-section-header">
        <div>
            <h2 id="admin-statistics-title">Visual statistics</h2>
            <p>Real offer and candidature distributions for platform control.</p>
        </div>
        <a class="admin-report-link" href="../condidature/admin_report.php">Export PDF report</a>
    </div>

    <div class="admin-chart-grid">
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
        new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: chart.labels,
                datasets: [{
                    data: values,
                    backgroundColor: chart.colors,
                    borderColor: cardColor,
                    borderWidth: 2,
                    hoverOffset: 6,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '64%',
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
