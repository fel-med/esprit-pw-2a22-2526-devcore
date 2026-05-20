<?php
require_once __DIR__ . '/../layout/session_bridge.php';
$currentUser = cre8_front_require_user('createur');
$frontActive = 'collaborations';

require_once __DIR__ . '/../../../Controleur/condidatureC.php';

$controller = new CondidatureC();
$sessionUser = $currentUser;

$creatorId = isset($sessionUser['id']) ? (int) $sessionUser['id'] : null;

$filters = [
    'keyword' => '',
    'status' => '',
];
$opportunities = [];
$error = null;

function formatCampaignDate($value, $fallback = 'Not scheduled')
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

function excerptCampaignText($text, $length = 170)
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

function campaignStatusLabel($status)
{
    $status = trim((string) $status);

    return $status !== '' ? ucwords(str_replace('_', ' ', $status)) : 'Status not set';
}

function campaignApplicationCta($condidature)
{
    if (!$condidature) {
        return ['label' => 'Apply to campaign', 'class' => 'btn-primary'];
    }

    if ($condidature->isDraft()) {
        return ['label' => 'Continue draft', 'class' => 'btn-primary'];
    }

    if ($condidature->isNegotiation()) {
        return ['label' => 'Open negotiation', 'class' => 'btn-primary'];
    }

    return ['label' => 'View application', 'class' => 'btn-outline-secondary'];
}

if ($creatorId) {
    try {
        $opportunities = $controller->getCreatorCampaignOpportunities($creatorId, $filters);
    } catch (Throwable $exception) {
        $error = 'Campaign opportunities could not be loaded right now.';
    }
} else {
    $error = 'No creator profile is available for this workspace.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/../layout/front-theme-bootstrap.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campaign Opportunities - Cre8Connect</title>
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
                    <span class="module-eyebrow">Campaign applications</span>
                    <h1 class="display-5 fw-bold mt-3 mb-2 gradient-title">Campaign opportunities</h1>
                    <p class="lead text-muted mb-0">Browse active campaign briefs and submit a structured creator application.</p>
                </div>
                <div class="compact-actions">
                    <a class="btn btn-outline-secondary" href="index.php">My candidatures</a>
                    <a class="btn btn-outline-secondary" href="../offre/creator_list.php">Offer inbox</a>
                </div>
            </section>

            <?php if ($error): ?>
                <section class="empty-state-card">
                    <div class="empty-state-icon">!</div>
                    <h2 class="section-title">Campaigns unavailable</h2>
                    <p class="section-subtitle"><?php echo htmlspecialchars($error); ?></p>
                </section>
            <?php elseif (empty($opportunities)): ?>
                <section class="empty-state-card">
                    <div class="empty-state-icon">i</div>
                    <h2 class="section-title">No campaign opportunities found</h2>
                    <p class="section-subtitle">Try a broader search or come back when brands publish more campaign briefs.</p>
                </section>
            <?php else: ?>
                <section class="campaign-opportunity-grid">
                    <?php foreach ($opportunities as $item): ?>
                        <?php
                        $source = $item['source'];
                        $brand = $item['brand'];
                        $condidature = $item['condidature'];
                        $cta = campaignApplicationCta($condidature);
                        $href = $condidature
                            ? 'details.php?idCandidature=' . (int) $condidature->getIdCandidature()
                            : 'details.php?origin=par_campagne&idSource=' . (int) $source['id'];
                        ?>
                        <article class="campaign-opportunity-card">
                            <div class="campaign-opportunity-top">
                                <span class="origin-badge">Campaign application</span>
                                <span class="offer-chip"><?php echo htmlspecialchars(campaignStatusLabel($source['status'])); ?></span>
                            </div>
                            <h2><?php echo htmlspecialchars($source['title'] ?: ('Campaign #' . $source['id'])); ?></h2>
                            <p><?php echo htmlspecialchars(excerptCampaignText($source['description'] ?: 'No campaign description was provided yet.')); ?></p>
                            <div class="campaign-opportunity-meta">
                                <span>Brand: <?php echo htmlspecialchars($brand['nom'] ?: 'Unknown brand'); ?></span>
                                <span><?php echo htmlspecialchars($brand['email']); ?></span>
                                <span>Start: <?php echo htmlspecialchars(formatCampaignDate($source['datePublication'])); ?></span>
                                <span>End: <?php echo htmlspecialchars(formatCampaignDate($source['dateLimite'])); ?></span>
                            </div>
                            <?php if ($condidature): ?>
                                <div class="response-callout response-callout-review">
                                    <strong><?php echo htmlspecialchars($condidature->getDisplayStatusLabel()); ?></strong>
                                    <span><?php echo htmlspecialchars($condidature->getResponseTypeLabel()); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="compact-actions">
                                <a class="btn <?php echo htmlspecialchars($cta['class']); ?>" href="<?php echo htmlspecialchars($href); ?>"><?php echo htmlspecialchars($cta['label']); ?></a>
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
    'page' => 'creator_candidature_list',
    'role' => 'createur',
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
                    'cand.campaignApplications': 'Campaign applications',
                    'cand.campaignOpportunities': 'Campaign opportunities',
                    'cand.campaignOpportunitiesCopy': 'Browse active campaign briefs and submit a structured creator application.',
                    'cand.myCandidatures': 'My candidatures',
                    'cand.offerInbox': 'Offer inbox',
                    'cand.campaignsUnavailable': 'Campaigns unavailable',
                    'cand.noCampaignOpportunities': 'No campaign opportunities found',
                    'cand.noCampaignOpportunitiesCopy': 'Try a broader search or come back when brands publish more campaign briefs.',
                    'cand.campaignApplication': 'Campaign application',
                    'cand.noCampaignDescription': 'No campaign description was provided yet.',
                    'cand.brand': 'Brand',
                    'cand.unknownBrand': 'Unknown brand',
                    'cand.start': 'Start',
                    'cand.end': 'End',
                    'cand.applyCampaign': 'Apply to campaign',
                    'cand.continueDraft': 'Continue draft',
                    'cand.openNegotiation': 'Open negotiation',
                    'cand.viewApplication': 'View application',
                    'cand.statusNotSet': 'Status not set'
                },
                fr: {
                    'cand.campaignApplications': 'Candidatures de campagne',
                    'cand.campaignOpportunities': 'Opportunites de campagne',
                    'cand.campaignOpportunitiesCopy': 'Parcourez les briefs de campagne actifs et envoyez une candidature createur structuree.',
                    'cand.myCandidatures': 'Mes candidatures',
                    'cand.offerInbox': 'Boite offres',
                    'cand.campaignsUnavailable': 'Campagnes indisponibles',
                    'cand.noCampaignOpportunities': 'Aucune opportunite de campagne trouvee',
                    'cand.noCampaignOpportunitiesCopy': 'Essayez une recherche plus large ou revenez quand les marques publient plus de briefs.',
                    'cand.campaignApplication': 'Candidature de campagne',
                    'cand.noCampaignDescription': 'Aucune description de campagne n a encore ete fournie.',
                    'cand.brand': 'Marque',
                    'cand.unknownBrand': 'Marque inconnue',
                    'cand.start': 'Debut',
                    'cand.end': 'Fin',
                    'cand.applyCampaign': 'Postuler a la campagne',
                    'cand.continueDraft': 'Continuer le brouillon',
                    'cand.openNegotiation': 'Ouvrir la negociation',
                    'cand.viewApplication': 'Voir la candidature',
                    'cand.statusNotSet': 'Statut non defini'
                }
            };
            const textKeys = {
                'Campaign applications': 'cand.campaignApplications',
                'Campaign opportunities': 'cand.campaignOpportunities',
                'Browse active campaign briefs and submit a structured creator application.': 'cand.campaignOpportunitiesCopy',
                'My candidatures': 'cand.myCandidatures',
                'Offer inbox': 'cand.offerInbox',
                'Campaigns unavailable': 'cand.campaignsUnavailable',
                'No campaign opportunities found': 'cand.noCampaignOpportunities',
                'Try a broader search or come back when brands publish more campaign briefs.': 'cand.noCampaignOpportunitiesCopy',
                'Campaign application': 'cand.campaignApplication',
                'No campaign description was provided yet.': 'cand.noCampaignDescription',
                'Brand': 'cand.brand',
                'Unknown brand': 'cand.unknownBrand',
                'Start': 'cand.start',
                'End': 'cand.end',
                'Apply to campaign': 'cand.applyCampaign',
                'Continue draft': 'cand.continueDraft',
                'Open negotiation': 'cand.openNegotiation',
                'View application': 'cand.viewApplication',
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
    <?php require __DIR__ . '/../layout/footer.php'; ?>
    <script src="../layout/front-header.js"></script>
</body>
</html>
