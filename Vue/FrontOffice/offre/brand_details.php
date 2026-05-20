<?php
require_once __DIR__ . '/../layout/session_bridge.php';
$currentUser = cre8_front_require_user('marque');
$frontActive = 'collaborations';
require_once __DIR__ . '/../layout/avatar_helper.php';

require_once __DIR__ . '/../../../Controleur/offreC.php';

$controller = new OffreC();
$brandId = (int) $currentUser['id'];
$idOffre = isset($_GET['idOffre']) && is_numeric($_GET['idOffre']) ? (int) $_GET['idOffre'] : null;
$offre = null;
$error = null;
$creator = null;
$response = null;

function translateOfferStatus($status)
{
    return match ($status) {
        'brouillon' => 'Draft',
        'pending' => 'Pending launch',
        'publiee' => 'Published',
        'cloturee' => 'Closed',
        'expiree' => 'Expired',
        'archivee' => 'Archived',
        'active' => 'Active',
        'fermee', 'closed' => 'Closed',
        default => ucwords(str_replace('_', ' ', (string) $status)),
    };
}

function offerStatusClass($status)
{
    return match ($status) {
        'brouillon' => 'status-draft',
        'pending' => 'status-pending',
        'publiee', 'active' => 'status-published',
        'cloturee', 'fermee', 'closed' => 'status-closed',
        'expiree' => 'status-expired',
        'archivee' => 'status-archived',
        default => 'status-draft',
    };
}

function responseStatusLabel(array $response)
{
    return (string) ($response['displayStatusLabel'] ?? ucwords(str_replace('_', ' ', (string) ($response['statutCandidature'] ?? ''))));
}

function responseStatusClass($status)
{
    return match ($status) {
        'brouillon' => 'draft',
        'envoyee', 'en_attente' => 'pending',
        'negociation', 'en_etude' => 'review',
        'acceptee' => 'accepted',
        'refusee', 'retiree' => 'declined',
        default => 'pending',
    };
}

function formatMoney($value)
{
    return 'EUR ' . number_format((float) $value, 2, '.', ',');
}

function isAcceptedResponseLockStatus($status)
{
    return in_array((string) $status, ['envoyee', 'en_attente', 'acceptee'], true);
}

if ($idOffre !== null) {
    $offre = $controller->getOffreById($idOffre, $brandId);
    if ($offre) {
        if (!$offre->isDraftSansCreateur() && $offre->getIdCreateurCible()) {
            $users = $controller->getUsersByIds([$offre->getIdCreateurCible()], 'createur');
            $creator = $users[$offre->getIdCreateurCible()] ?? null;
            $response = $controller->getOfferResponseByCreator($offre->getIdCreateurCible(), $offre->getIdOffre());
        }
    }

    if (!$offre) {
        $error = 'Offer not found or access denied.';
    }
} else {
    $error = 'Invalid parameters for displaying the offer.';
}

$displayStatus = $offre ? $offre->getDisplayStatusKey() : 'brouillon';
$publicationLabel = $offre && $offre->isPendingPublication() ? 'Goes live' : 'Published';
$isEditLocked = $response && isAcceptedResponseLockStatus($response['statutCandidature'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/../layout/front-theme-bootstrap.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offer Details - Cre8Connect</title>
    <link rel="stylesheet" href="../css/frontoffice.css">
    <link rel="stylesheet" href="offre.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/offre.css')); ?>">
    <link rel="stylesheet" href="../layout/front-header.css">
<link rel="icon" type="image/png" sizes="16x16" href="../../public/images/favicon-16.png">
<link rel="icon" type="image/png" sizes="32x32" href="../../public/images/favicon-32.png">
<link rel="shortcut icon" type="image/png" href="../../public/images/favicon-32.png">
<link rel="apple-touch-icon" sizes="180x180" href="../../public/images/apple-touch-icon.png">
<style>
/* Cre8 safety: hide stale page-level notification widgets only.
   The real notification bell must stay inside the shared .front-nav header. */
body > .notification-widget,
body > .notification-widget-front,
main .notification-widget,
main .notification-widget-front,
.offre-page-shell .notification-widget,
.offre-page-shell .notification-widget-front,
.module-hero .notification-widget,
.module-hero .notification-widget-front {
    display: none !important;
    visibility: hidden !important;
    pointer-events: none !important;
}

.front-nav .notification-widget,
.front-nav .notification-widget-front {
    display: inline-flex !important;
    visibility: visible !important;
    pointer-events: auto !important;
}
</style>
</head>
<body>
    <?php require_once dirname(__DIR__) . '/layout/header.php'; ?>
    <main class="container py-5">
        <div class="offre-page-shell">
            <?php if ($error): ?>
                <section class="empty-state-card">
                    <div class="empty-state-icon">!</div>
                    <h2 class="section-title">Offer not available</h2>
                    <p class="section-subtitle"><?php echo htmlspecialchars($error); ?></p>
                    <div class="compact-actions justify-content-center mt-4">
                        <a class="btn btn-primary" href="brand_index.php">Back to my offers</a>
                    </div>
                </section>
            <?php else: ?>
                <section class="module-hero">
                    <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start">
                        <div>
                            <span class="module-eyebrow">Targeted collaboration brief</span>
                            <h1 class="display-5 fw-bold mt-3 mb-2 gradient-title"><?php echo htmlspecialchars($offre->getTitre()); ?></h1>
                            <p class="lead text-muted mb-0"><?php echo htmlspecialchars($offre->getDescription()); ?></p>
                        </div>
                        <div class="offer-meta">
                            <span class="offer-status <?php echo htmlspecialchars(offerStatusClass($displayStatus)); ?>"><?php echo htmlspecialchars(translateOfferStatus($displayStatus)); ?></span>
                            <span class="offer-chip"><?php echo htmlspecialchars(formatMoney($offre->getBudgetPropose())); ?></span>
                            <span class="offer-chip"><?php echo htmlspecialchars($publicationLabel); ?>: <?php echo htmlspecialchars($offre->getDatePublication()); ?></span>
                        </div>
                    </div>
                </section>

                <div class="invitation-grid">
                    <div class="response-grid">
                        <section class="section-card">
                            <h2 class="section-title">Offer snapshot</h2>
                            <p class="section-subtitle">A clean view of the invitation this creator receives.</p>
                            <div class="offer-detail-list mt-4">
                                <div class="offer-detail-item">
                                    <strong>Target creator</strong>
                                    <div style="display:flex;align-items:center;gap:.65rem;">
                                        <?php echo cre8_render_avatar($creator['id'] ?? ($offre ? $offre->getIdCreateurCible() : 0), (string) ($creator['nom'] ?? 'Creator'), 'cre8-avatar-md'); ?>
                                        <div>
                                            <span><?php echo htmlspecialchars($creator['nom'] ?? 'No creator selected yet'); ?></span>
                                            <p><?php echo htmlspecialchars($creator['email'] ?? ''); ?></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="offer-detail-item">
                                    <strong>Objective</strong>
                                    <span><?php echo htmlspecialchars($offre->getObjectif()); ?></span>
                                </div>
                                <div class="offer-detail-item">
                                    <strong>Deadline</strong>
                                    <span><?php echo htmlspecialchars($offre->getDateLimite()); ?></span>
                                </div>
                                <div class="offer-detail-item">
                                    <strong>Budget</strong>
                                    <span><?php echo htmlspecialchars(formatMoney($offre->getBudgetPropose())); ?></span>
                                </div>
                            </div>
                        </section>

                        <div class="detail-columns">
                            <section class="info-card">
                                <h2 class="section-title">Why this creator</h2>
                                <div class="note-block mt-3">
                                    <strong>Selection rationale</strong>
                                    <p><?php echo htmlspecialchars($offre->getRaisonChoix() !== '' ? $offre->getRaisonChoix() : 'No specific rationale was added for this offer yet.'); ?></p>
                                </div>
                            </section>

                            <section class="info-card">
                                <h2 class="section-title">Expected collaboration fit</h2>
                                <div class="note-block mt-3">
                                    <strong>How this should work</strong>
                                    <p><?php echo htmlspecialchars($offre->getAttenteCollaboration() !== '' ? $offre->getAttenteCollaboration() : 'No collaboration expectations were written yet.'); ?></p>
                                </div>
                            </section>
                        </div>

                        <section class="section-card">
                            <h2 class="section-title">Personal note to the creator</h2>
                            <div class="note-block mt-3">
                                <strong>Message</strong>
                                <p><?php echo htmlspecialchars($offre->getMessagePersonnalise() !== '' ? $offre->getMessagePersonnalise() : 'No personal note has been added to this invitation.'); ?></p>
                            </div>
                        </section>
                    </div>

                    <aside class="response-grid">
                        <section class="info-card">
                            <h2 class="section-title">Creator response</h2>
                            <?php if ($response): ?>
                                <div class="review-list mt-4">
                                    <div class="review-item">
                                        <strong>Status</strong>
                                        <span class="response-status <?php echo htmlspecialchars(responseStatusClass($response['statutCandidature'])); ?>"><?php echo htmlspecialchars(responseStatusLabel($response)); ?></span>
                                    </div>
                                    <div class="review-item">
                                        <strong>Creator action</strong>
                                        <span><?php echo htmlspecialchars($response['responseTypeLabel'] ?? 'Offer response'); ?></span>
                                    </div>
                                    <div class="review-item">
                                        <strong>Budget reply</strong>
                                        <span>EUR <?php echo htmlspecialchars($response['budgetPropose']); ?></span>
                                    </div>
                                    <div class="review-item">
                                        <strong>Timeline reply</strong>
                                        <span><?php echo htmlspecialchars($response['delaiPropose']); ?> days</span>
                                    </div>
                                    <div class="review-item">
                                        <strong>Creator message</strong>
                                        <span><?php echo htmlspecialchars($response['messageMotivation']); ?></span>
                                    </div>
                                </div>
                                <div class="note-block mt-4">
                                    <strong>Response workspace</strong>
                                    <p>Open the candidature workspace to review the full response thread and continue negotiation if this collaboration still needs adjustments.</p>
                                    <div class="compact-actions mt-3">
                                        <a class="btn btn-outline-secondary" href="../condidature/brand_details.php?idCandidature=<?php echo (int) $response['idCandidature']; ?>">Open candidature details</a>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="response-callout mt-4">
                                    <strong>No response yet</strong>
                                    <div class="mt-2 text-muted small">The targeted creator has not answered this invitation yet.</div>
                                </div>
                            <?php endif; ?>
                        </section>

                        <section class="info-card">
                            <h2 class="section-title">Actions</h2>
                            <?php if ($isEditLocked): ?>
                                <div class="response-callout response-callout-accepted mt-4">
                                    <strong>Editing locked</strong>
                                    <div class="mt-2 text-muted small">The targeted creator already accepted this offer, so the brief can no longer be modified by the brand.</div>
                                </div>
                            <?php endif; ?>
                            <div class="compact-actions mt-4">
                                <?php if ($isEditLocked): ?>
                                    <span class="btn btn-outline-secondary disabled" aria-disabled="true">Editing locked</span>
                                <?php else: ?>
                                    <a class="btn btn-primary" href="brand_edit.php?idOffre=<?php echo (int) $offre->getIdOffre(); ?>">Edit offer</a>
                                <?php endif; ?>
                                <?php if ($response): ?>
                                    <a class="btn btn-outline-secondary" href="../condidature/brand_details.php?idCandidature=<?php echo (int) $response['idCandidature']; ?>">Open response</a>
                                <?php endif; ?>
                                <form
                                    method="post"
                                    action="brand_delete.php"
                                    data-delete-confirm
                                    data-delete-title="<?php echo htmlspecialchars($offre->getTitre(), ENT_QUOTES); ?>"
                                    data-delete-creator="<?php echo htmlspecialchars($creator['nom'] ?? 'No creator selected yet', ENT_QUOTES); ?>"
                                >
                                    <input type="hidden" name="idOffre" value="<?php echo (int) $offre->getIdOffre(); ?>">
                                    <button type="submit" class="btn btn-outline-danger">Delete</button>
                                </form>
                                <a class="btn btn-outline-secondary" href="brand_index.php">Back to offers</a>
                            </div>
                        </section>
                    </aside>
                </div>
            <?php endif; ?>
        </div>
    </main>
    <script src="offre-delete-confirm.js?v=<?php echo urlencode((string) filemtime(__DIR__ . '/offre-delete-confirm.js')); ?>"></script>
<?php
$cre8PilotContext = [
    'page' => 'brand_offer_workspace',
    'mode' => 'details',
    'role' => 'marque',
    'allowedActions' => ['normal_chat', 'summarize_page', 'analyze_page', 'suggest_budget', 'draft_invite_message', 'recommend_next_action', 'explain_statuses'],
    'formTarget' => null,
    'visibleEntityType' => 'offre',
    'visibleEntityId' => $idOffre ?? null,
];
require __DIR__ . '/../condidature/cre8pilot_widget.php';
?>
    <script>
        (() => {
            const translations = {
                en: {
                    'offer.notAvailable': 'Offer not available',
                    'offer.backMine': 'Back to my offers',
                    'offer.brief': 'Targeted collaboration brief',
                    'offer.goesLive': 'Goes live',
                    'offer.published': 'Published',
                    'offer.snapshot': 'Offer snapshot',
                    'offer.snapshotCopy': 'A clean view of the invitation this creator receives.',
                    'offer.targetCreator': 'Target creator',
                    'offer.noCreator': 'No creator selected yet',
                    'offer.objective': 'Objective',
                    'offer.deadline': 'Deadline',
                    'offer.budget': 'Budget',
                    'offer.whyCreator': 'Why this creator',
                    'offer.rationale': 'Selection rationale',
                    'offer.noRationale': 'No specific rationale was added for this offer yet.',
                    'offer.expectedFit': 'Expected collaboration fit',
                    'offer.howWork': 'How this should work',
                    'offer.noExpectations': 'No collaboration expectations were written yet.',
                    'offer.personalNote': 'Personal note to the creator',
                    'offer.message': 'Message',
                    'offer.noPersonalNote': 'No personal note has been added to this invitation.',
                    'offer.creatorResponse': 'Creator response',
                    'offer.status': 'Status',
                    'offer.creatorAction': 'Creator action',
                    'offer.budgetReply': 'Budget reply',
                    'offer.timelineReply': 'Timeline reply',
                    'offer.creatorMessage': 'Creator message',
                    'offer.responseWorkspace': 'Response workspace',
                    'offer.responseWorkspaceCopy': 'Open the candidature workspace to review the full response thread and continue negotiation if this collaboration still needs adjustments.',
                    'offer.openCandidatureDetails': 'Open candidature details',
                    'offer.noResponse': 'No response yet',
                    'offer.noResponseCopy': 'The targeted creator has not answered this invitation yet.',
                    'offer.actions': 'Actions',
                    'offer.editingLocked': 'Editing locked',
                    'offer.editingLockedCopy': 'The targeted creator already accepted this offer, so the brief can no longer be modified by the brand.',
                    'offer.editOffer': 'Edit offer',
                    'offer.openResponse': 'Open response',
                    'offer.delete': 'Delete',
                    'offer.draft': 'Draft',
                    'offer.pendingLaunch': 'Pending launch',
                    'offer.closed': 'Closed',
                    'offer.expired': 'Expired',
                    'offer.archived': 'Archived',
                    'offer.active': 'Active'
                },
                fr: {
                    'offer.notAvailable': 'Offre indisponible',
                    'offer.backMine': 'Retour a mes offres',
                    'offer.brief': 'Brief de collaboration ciblee',
                    'offer.goesLive': 'Mise en ligne',
                    'offer.published': 'Publiee',
                    'offer.snapshot': 'Apercu de l offre',
                    'offer.snapshotCopy': 'Une vue claire de l invitation recue par ce createur.',
                    'offer.targetCreator': 'Createur cible',
                    'offer.noCreator': 'Aucun createur selectionne',
                    'offer.objective': 'Objectif',
                    'offer.deadline': 'Echeance',
                    'offer.budget': 'Budget',
                    'offer.whyCreator': 'Pourquoi ce createur',
                    'offer.rationale': 'Raison de la selection',
                    'offer.noRationale': 'Aucune raison specifique n a encore ete ajoutee.',
                    'offer.expectedFit': 'Adequation attendue',
                    'offer.howWork': 'Comment cela devrait fonctionner',
                    'offer.noExpectations': 'Aucune attente de collaboration n a encore ete ecrite.',
                    'offer.personalNote': 'Note personnelle au createur',
                    'offer.message': 'Message',
                    'offer.noPersonalNote': 'Aucune note personnelle n a ete ajoutee a cette invitation.',
                    'offer.creatorResponse': 'Reponse du createur',
                    'offer.status': 'Statut',
                    'offer.creatorAction': 'Action du createur',
                    'offer.budgetReply': 'Reponse budget',
                    'offer.timelineReply': 'Reponse delai',
                    'offer.creatorMessage': 'Message du createur',
                    'offer.responseWorkspace': 'Espace de reponse',
                    'offer.responseWorkspaceCopy': 'Ouvrez l espace candidature pour examiner le fil complet et continuer la negociation si besoin.',
                    'offer.openCandidatureDetails': 'Ouvrir les details de candidature',
                    'offer.noResponse': 'Aucune reponse',
                    'offer.noResponseCopy': 'Le createur cible n a pas encore repondu a cette invitation.',
                    'offer.actions': 'Actions',
                    'offer.editingLocked': 'Modification verrouillee',
                    'offer.editingLockedCopy': 'Le createur cible a deja accepte cette offre, le brief ne peut plus etre modifie.',
                    'offer.editOffer': 'Modifier l offre',
                    'offer.openResponse': 'Ouvrir la reponse',
                    'offer.delete': 'Supprimer',
                    'offer.draft': 'Brouillon',
                    'offer.pendingLaunch': 'Publication planifiee',
                    'offer.closed': 'Fermee',
                    'offer.expired': 'Expiree',
                    'offer.archived': 'Archivee',
                    'offer.active': 'Active'
                }
            };
            const textKeys = {
                'Offer not available': 'offer.notAvailable',
                'Back to my offers': 'offer.backMine',
                'Targeted collaboration brief': 'offer.brief',
                'Goes live': 'offer.goesLive',
                'Published': 'offer.published',
                'Offer snapshot': 'offer.snapshot',
                'A clean view of the invitation this creator receives.': 'offer.snapshotCopy',
                'Target creator': 'offer.targetCreator',
                'No creator selected yet': 'offer.noCreator',
                'Objective': 'offer.objective',
                'Deadline': 'offer.deadline',
                'Budget': 'offer.budget',
                'Why this creator': 'offer.whyCreator',
                'Selection rationale': 'offer.rationale',
                'No specific rationale was added for this offer yet.': 'offer.noRationale',
                'Expected collaboration fit': 'offer.expectedFit',
                'How this should work': 'offer.howWork',
                'No collaboration expectations were written yet.': 'offer.noExpectations',
                'Personal note to the creator': 'offer.personalNote',
                'Message': 'offer.message',
                'No personal note has been added to this invitation.': 'offer.noPersonalNote',
                'Creator response': 'offer.creatorResponse',
                'Status': 'offer.status',
                'Creator action': 'offer.creatorAction',
                'Budget reply': 'offer.budgetReply',
                'Timeline reply': 'offer.timelineReply',
                'Creator message': 'offer.creatorMessage',
                'Response workspace': 'offer.responseWorkspace',
                'Open the candidature workspace to review the full response thread and continue negotiation if this collaboration still needs adjustments.': 'offer.responseWorkspaceCopy',
                'Open candidature details': 'offer.openCandidatureDetails',
                'No response yet': 'offer.noResponse',
                'The targeted creator has not answered this invitation yet.': 'offer.noResponseCopy',
                'Actions': 'offer.actions',
                'Editing locked': 'offer.editingLocked',
                'The targeted creator already accepted this offer, so the brief can no longer be modified by the brand.': 'offer.editingLockedCopy',
                'Edit offer': 'offer.editOffer',
                'Open response': 'offer.openResponse',
                'Delete': 'offer.delete',
                'Draft': 'offer.draft',
                'Pending launch': 'offer.pendingLaunch',
                'Closed': 'offer.closed',
                'Expired': 'offer.expired',
                'Archived': 'offer.archived',
                'Active': 'offer.active'
            };
            function currentLang() { return typeof window.cre8FrontReadLang === 'function' ? window.cre8FrontReadLang() : 'en'; }
            function keyForText(value) {
                const clean = String(value).trim();
                if (textKeys[clean]) return textKeys[clean];
                for (const lang of Object.keys(translations)) for (const key of Object.keys(translations[lang])) if (translations[lang][key] === clean) return key;
                return '';
            }
            function applyOfferTranslations(root = document) {
                const dict = translations[currentLang()] || translations.en;
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
                    const key = keyForText(node.nodeValue);
                    if (!key || dict[key] === undefined) return;
                    node.nodeValue = node.nodeValue.replace(node.nodeValue.trim(), dict[key]);
                    if (node.parentElement && node.parentElement.childNodes.length === 1) node.parentElement.setAttribute('data-i18n', key);
                });
            }
            document.addEventListener('DOMContentLoaded', () => {
                if (typeof window.cre8RegisterTranslations === 'function') window.cre8RegisterTranslations(translations);
                applyOfferTranslations();
            });
            window.addEventListener('cre8:languagechange', () => applyOfferTranslations());
        })();
    </script>
    <?php require __DIR__ . '/../layout/footer.php'; ?>
    <script src="../layout/front-header.js"></script>
</body>
</html>
