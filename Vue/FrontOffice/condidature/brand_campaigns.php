<?php
require_once __DIR__ . '/../layout/session_bridge.php';
$currentUser = cre8_front_require_user('marque');
$frontActive = 'collaborations';

require_once __DIR__ . '/../../../Controleur/condidatureC.php';

$controller = new CondidatureC();
$sessionUser = $currentUser;

$brandId = isset($sessionUser['id']) ? (int) $sessionUser['id'] : null;
$brandUser = $brandId ? ($controller->getUsersByIds([$brandId], 'marque')[$brandId] ?? null) : null;

$campaigns = [];
$error = null;

function formatBrandCampaignDate($value, $fallback = 'Not scheduled')
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

function excerptBrandCampaignText($text, $length = 180)
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

function brandCampaignStatusLabel($status)
{
    $status = trim((string) $status);

    return $status !== '' ? ucwords(str_replace('_', ' ', $status)) : 'Status not set';
}

if ($brandId) {
    try {
        $campaigns = $controller->getBrandCampaigns($brandId);
    } catch (Throwable $exception) {
        $error = 'Your campaigns could not be loaded right now.';
    }
} else {
    $error = 'No brand profile is available for this workspace.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/../layout/front-theme-bootstrap.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Campaigns - Cre8Connect</title>
    <link rel="stylesheet" href="../css/frontoffice.css">
    <link rel="stylesheet" href="../offre/offre.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../offre/offre.css')); ?>">
    <link rel="stylesheet" href="condidature.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/condidature.css')); ?>">
    <link rel="stylesheet" href="../layout/front-header.css">
    <style>
        /* Keep only the shared header notification bell. Hide any old page-level notification widget. */
        body > .notification-widget,
        body > .notification-widget-front,
        main .notification-widget,
        main .notification-widget-front {
            display: none !important;
        }
        .front-nav .notification-widget-front {
            display: inline-flex !important;
        }
    </style>
<link rel="icon" type="image/png" sizes="16x16" href="../../public/images/favicon-16.png">
<link rel="icon" type="image/png" sizes="32x32" href="../../public/images/favicon-32.png">
<link rel="shortcut icon" type="image/png" href="../../public/images/favicon-32.png">
<link rel="apple-touch-icon" sizes="180x180" href="../../public/images/apple-touch-icon.png">
</head>
<body>
    <?php require_once dirname(__DIR__) . '/layout/header.php'; ?>

    <main class="container py-5">
        <div class="offre-page-shell">
            <section class="module-hero campaign-opportunities-hero">
                <div>
                    <span class="module-eyebrow">Brand campaigns</span>
                    <h1 class="display-5 fw-bold mt-3 mb-2 gradient-title">My campaigns</h1>
                    <p class="lead text-muted mb-0">Review the campaigns owned by your brand and jump to their creator applications.</p>
                </div>
                <div class="compact-actions">
                    <a class="btn btn-outline-secondary" href="brand_index.php?origin=par_campagne">Campaign applications</a>
                    <a class="btn btn-outline-secondary" href="../offre/brand_index.php">My offers</a>
                </div>
            </section>

            <?php if ($brandUser): ?>
                <section class="note-block">
                    <strong><?php echo htmlspecialchars($brandUser['nom']); ?></strong>
                    <p><?php echo htmlspecialchars($brandUser['email']); ?></p>
                </section>
            <?php endif; ?>

            <?php if ($error): ?>
                <section class="empty-state-card">
                    <div class="empty-state-icon">!</div>
                    <h2 class="section-title">Campaigns unavailable</h2>
                    <p class="section-subtitle"><?php echo htmlspecialchars($error); ?></p>
                </section>
            <?php elseif (empty($campaigns)): ?>
                <section class="empty-state-card">
                    <div class="empty-state-icon">i</div>
                    <h2 class="section-title">No campaigns found</h2>
                    <p class="section-subtitle">No campaign is currently attached to this brand account.</p>
                </section>
            <?php else: ?>
                <section class="campaign-opportunity-grid brand-campaign-grid">
                    <?php foreach ($campaigns as $campaign): ?>
                        <article class="campaign-opportunity-card brand-campaign-card">
                            <div class="campaign-opportunity-top">
                                <span class="origin-badge">Campaign</span>
                                <span class="offer-chip"><?php echo htmlspecialchars(brandCampaignStatusLabel($campaign['status'])); ?></span>
                            </div>
                            <h2><?php echo htmlspecialchars($campaign['title'] ?: ('Campaign #' . $campaign['id'])); ?></h2>
                            <p><?php echo htmlspecialchars(excerptBrandCampaignText($campaign['description'] ?: 'No campaign description was provided yet.')); ?></p>
                            <div class="campaign-opportunity-meta">
                                <span>Start: <?php echo htmlspecialchars(formatBrandCampaignDate($campaign['dateDebut'])); ?></span>
                                <span>End: <?php echo htmlspecialchars(formatBrandCampaignDate($campaign['dateFin'])); ?></span>
                                <span>Total applications: <?php echo (int) $campaign['applicationCount']; ?></span>
                            </div>
                            <div class="candidature-inline-meta">
                                <span class="offer-chip">Waiting: <?php echo (int) $campaign['waitingCount']; ?></span>
                                <span class="offer-chip">Accepted: <?php echo (int) $campaign['acceptedCount']; ?></span>
                                <span class="offer-chip">Refused: <?php echo (int) $campaign['refusedCount']; ?></span>
                            </div>
                            <div class="compact-actions">
                                <a class="btn btn-primary" href="brand_index.php?origin=par_campagne">Review applications</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
<?php
$cre8PilotContext = [
    'page' => 'brand_offer_list',
    'role' => 'marque',
    'allowedActions' => ['normal_chat', 'summarize_page', 'analyze_page'],
    'formTarget' => null,
    'visibleEntityType' => 'campagne',
];
require __DIR__ . '/cre8pilot_widget.php';
?>
    <script>
        (() => {
            const translations = {
                en: {
                    'cand.brandCampaigns': 'Brand campaigns',
                    'cand.myCampaigns': 'My campaigns',
                    'cand.myCampaignsCopy': 'Review the campaigns owned by your brand and jump to their creator applications.',
                    'cand.campaignApplications': 'Campaign applications',
                    'cand.myOffers': 'My offers',
                    'cand.campaignsUnavailable': 'Campaigns unavailable',
                    'cand.noCampaigns': 'No campaigns found',
                    'cand.noCampaignsCopy': 'No campaign is currently attached to this brand account.',
                    'cand.campaign': 'Campaign',
                    'cand.noCampaignDescription': 'No campaign description was provided yet.',
                    'cand.start': 'Start',
                    'cand.end': 'End',
                    'cand.totalApplications': 'Total applications',
                    'cand.waiting': 'Waiting',
                    'cand.accepted': 'Accepted',
                    'cand.refused': 'Refused',
                    'cand.reviewApplications': 'Review applications',
                    'cand.statusNotSet': 'Status not set'
                },
                fr: {
                    'cand.brandCampaigns': 'Campagnes marque',
                    'cand.myCampaigns': 'Mes campagnes',
                    'cand.myCampaignsCopy': 'Consultez les campagnes de votre marque et accedez a leurs candidatures createurs.',
                    'cand.campaignApplications': 'Candidatures de campagne',
                    'cand.myOffers': 'Mes offres',
                    'cand.campaignsUnavailable': 'Campagnes indisponibles',
                    'cand.noCampaigns': 'Aucune campagne trouvee',
                    'cand.noCampaignsCopy': 'Aucune campagne n est actuellement attachee a ce compte marque.',
                    'cand.campaign': 'Campagne',
                    'cand.noCampaignDescription': 'Aucune description de campagne n a encore ete fournie.',
                    'cand.start': 'Debut',
                    'cand.end': 'Fin',
                    'cand.totalApplications': 'Total candidatures',
                    'cand.waiting': 'En attente',
                    'cand.accepted': 'Acceptees',
                    'cand.refused': 'Refusees',
                    'cand.reviewApplications': 'Examiner les candidatures',
                    'cand.statusNotSet': 'Statut non defini'
                }
            };
            const textKeys = {
                'Brand campaigns': 'cand.brandCampaigns',
                'My campaigns': 'cand.myCampaigns',
                'Review the campaigns owned by your brand and jump to their creator applications.': 'cand.myCampaignsCopy',
                'Campaign applications': 'cand.campaignApplications',
                'My offers': 'cand.myOffers',
                'Campaigns unavailable': 'cand.campaignsUnavailable',
                'No campaigns found': 'cand.noCampaigns',
                'No campaign is currently attached to this brand account.': 'cand.noCampaignsCopy',
                'Campaign': 'cand.campaign',
                'No campaign description was provided yet.': 'cand.noCampaignDescription',
                'Start': 'cand.start',
                'End': 'cand.end',
                'Total applications': 'cand.totalApplications',
                'Waiting': 'cand.waiting',
                'Accepted': 'cand.accepted',
                'Refused': 'cand.refused',
                'Review applications': 'cand.reviewApplications',
                'Status not set': 'cand.statusNotSet'
            };
            function lang() { return typeof window.cre8FrontReadLang === 'function' ? window.cre8FrontReadLang() : 'en'; }
            function keyForText(value) {
                const clean = String(value).trim().replace(/:$/, '');
                if (textKeys[clean]) return textKeys[clean];
                for (const locale of Object.keys(translations)) for (const key of Object.keys(translations[locale])) if (translations[locale][key] === clean) return key;
                return '';
            }
            function applyTranslations(root = document) {
                const dict = translations[lang()] || translations.en;
                if (typeof window.cre8ApplyI18n === 'function') window.cre8ApplyI18n(translations);
                const walker = document.createTreeWalker(root.body || root, NodeFilter.SHOW_TEXT, {
                    acceptNode(node) {
                        const parent = node.parentElement;
                        if (!parent || ['SCRIPT', 'STYLE', 'TEXTAREA'].includes(parent.tagName)) return NodeFilter.FILTER_REJECT;
                        return node.nodeValue.trim() ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT;
                    }
                });
                const nodes = [];
                while (walker.nextNode()) nodes.push(walker.currentNode);
                nodes.forEach((node) => {
                    const original = node.nodeValue.trim();
                    const key = keyForText(original);
                    if (!key || dict[key] === undefined) return;
                    const suffix = original.endsWith(':') ? ':' : '';
                    node.nodeValue = node.nodeValue.replace(original, dict[key] + suffix);
                    if (node.parentElement && node.parentElement.childNodes.length === 1) node.parentElement.setAttribute('data-i18n', key);
                });
            }
            document.addEventListener('DOMContentLoaded', () => {
                if (typeof window.cre8RegisterTranslations === 'function') window.cre8RegisterTranslations(translations);
                applyTranslations();
            });
            window.addEventListener('cre8:languagechange', () => applyTranslations());
        })();
    </script>
    <script src="../layout/front-header.js"></script>
</body>
</html>
