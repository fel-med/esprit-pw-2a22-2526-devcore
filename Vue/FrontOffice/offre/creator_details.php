<?php
require_once __DIR__ . '/../layout/session_bridge.php';
$currentUser = cre8_front_require_user('createur');
$frontActive = 'collaborations';
require_once __DIR__ . '/../layout/avatar_helper.php';

require_once __DIR__ . '/../../../Controleur/offreC.php';
require_once __DIR__ . '/../../../Controleur/condidatureC.php';

$offreController = new OffreC();
$candidatureController = new CondidatureC();
$idOffre = isset($_GET['idOffre']) && is_numeric($_GET['idOffre']) ? (int) $_GET['idOffre'] : null;
$sessionUser = $currentUser;
$idCreateur = isset($sessionUser['id'], $sessionUser['role']) && $sessionUser['role'] === 'createur'
    ? (int) $sessionUser['id']
    : (isset($_GET['idCreateur']) && is_numeric($_GET['idCreateur']) ? (int) $_GET['idCreateur'] : null);
$offre = null;
$brand = null;
$error = null;
$response = null;
$responseContext = null;
$notice = isset($_GET['notice']) ? trim($_GET['notice']) : null;
$noticeType = isset($_GET['noticeType']) ? trim($_GET['noticeType']) : 'success';

function translateOfferStatus($status)
{
    return match ($status) {
        'brouillon' => 'Draft',
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
        'publiee', 'active' => 'status-published',
        'cloturee', 'fermee', 'closed' => 'status-closed',
        'expiree' => 'status-expired',
        'archivee' => 'status-archived',
        default => 'status-draft',
    };
}

function responseStatusLabel($response)
{
    return $response ? $response->getDisplayStatusLabel() : 'No response started';
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

function buildResponseWorkspaceUrl($idOffre, $mode = null)
{
    $query = [
        'origin' => 'par_offre',
        'idSource' => (int) $idOffre,
    ];

    if ($mode !== null && $mode !== '') {
        $query['mode'] = $mode;
    }

    return '../condidature/details.php?' . http_build_query($query);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggleSaved'], $_POST['idOffre']) && is_numeric($_POST['idOffre'])) {
    $offerId = (int) $_POST['idOffre'];
    $message = '';
    $redirectNoticeType = 'success';

    if ($offreController->isOffreSavedByCreator($idCreateur, $offerId)) {
        $offreController->unsaveOffreForCreator($idCreateur, $offerId);
        $message = 'Offer removed from your saved list.';
    } else {
        if ($offreController->saveOffreForCreator($idCreateur, $offerId)) {
            $message = 'Offer saved for later.';
        } else {
            $message = 'This offer cannot be saved anymore.';
            $redirectNoticeType = 'danger';
        }
    }

    header('Location: creator_details.php?idOffre=' . $offerId . '&idCreateur=' . $idCreateur . '&notice=' . urlencode($message) . '&noticeType=' . urlencode($redirectNoticeType));
    exit;
}

if ($idOffre !== null && $idCreateur !== null) {
    $offre = $offreController->getPublishedOffreById($idOffre, $idCreateur);
    if ($offre) {
        $brandMap = $offreController->getUsersByIds([$offre->getIdMarque()], 'marque');
        $brand = $brandMap[$offre->getIdMarque()] ?? null;
        $responseContext = $candidatureController->getCreatorCandidatureBySource($idCreateur, 'par_offre', $idOffre);
        $response = $responseContext['condidature'] ?? null;
        if ($response) {
            $offreController->removeSavedOffreWhenResponseExists($idCreateur, $idOffre);
        }
    } else {
        $error = 'Offer not found or not available to you.';
    }
} else {
    $error = 'Invalid parameters for displaying the offer.';
}

$isSaved = $idOffre !== null && $idCreateur !== null ? $offreController->isOffreSavedByCreator($idCreateur, $idOffre) : false;
$currentResponseMode = $response ? $response->getResponseMode() : 'accept';
$isNegotiationOnly = $response && $response->canCreatorEditNegotiationOnly();
$isResponseLocked = $response && $response->isCreatorLocked();
$responseWorkspaceUrl = $idOffre !== null ? buildResponseWorkspaceUrl($idOffre, $currentResponseMode) : '#';
$acceptWorkspaceUrl = $idOffre !== null ? buildResponseWorkspaceUrl($idOffre, 'accept') : '#';
$negotiateWorkspaceUrl = $idOffre !== null ? buildResponseWorkspaceUrl($idOffre, 'negotiate') : '#';
$declineWorkspaceUrl = $idOffre !== null ? buildResponseWorkspaceUrl($idOffre, 'decline') : '#';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/../layout/front-theme-bootstrap.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation Details - Cre8Connect</title>
    <link rel="stylesheet" href="../css/frontoffice.css">
    <link rel="stylesheet" href="offre.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/offre.css')); ?>">
    <link rel="stylesheet" href="../layout/front-header.css">
<link rel="icon" type="image/png" sizes="16x16" href="../../public/images/favicon-16.png">
<link rel="icon" type="image/png" sizes="32x32" href="../../public/images/favicon-32.png">
<link rel="shortcut icon" type="image/png" href="../../public/images/favicon-32.png">
<link rel="apple-touch-icon" sizes="180x180" href="../../public/images/apple-touch-icon.png">
    <style>
        /* Keep only the shared header notification bell.
           Some old offer/candidature pages can leave a stale widget in the page body. */
        body > .notification-widget,
        body > .notification-widget-front,
        main .notification-widget,
        main .notification-widget-front,
        .container > .notification-widget,
        .container > .notification-widget-front,
        .offre-page-shell > .notification-widget,
        .offre-page-shell > .notification-widget-front,
        .module-hero .notification-widget,
        .module-hero .notification-widget-front {
            display: none !important;
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
                    <h2 class="section-title">Invitation unavailable</h2>
                    <p class="section-subtitle"><?php echo htmlspecialchars($error); ?></p>
                    <div class="compact-actions justify-content-center mt-4">
                        <a class="btn btn-primary" href="creator_list.php">Back to offers</a>
                    </div>
                </section>
            <?php else: ?>
                <section class="module-hero">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                        <div>
                            <span class="module-eyebrow">Targeted invitation</span>
                            <h1 class="display-5 fw-bold mt-3 mb-2 gradient-title"><?php echo htmlspecialchars($offre->getTitre()); ?></h1>
                            <p class="lead text-muted mb-0"><?php echo htmlspecialchars($offre->getDescription()); ?></p>
                        </div>
                        <div class="compact-actions">
                            <span class="offer-status <?php echo htmlspecialchars(offerStatusClass($offre->getStatutOffre())); ?>"><?php echo htmlspecialchars(translateOfferStatus($offre->getStatutOffre())); ?></span>
                            <?php if (!$response): ?>
                                <form method="post" action="creator_details.php?idOffre=<?php echo (int) $idOffre; ?>&idCreateur=<?php echo (int) $idCreateur; ?>">
                                    <input type="hidden" name="idOffre" value="<?php echo (int) $idOffre; ?>">
                                    <button type="submit" name="toggleSaved" class="saved-toggle <?php echo $isSaved ? 'saved' : ''; ?>">
                                        <?php echo $isSaved ? 'Remove saved' : 'Save for later'; ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <?php if ($notice): ?>
                    <div class="alert alert-<?php echo $noticeType === 'danger' ? 'danger' : 'success'; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($notice); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="invitation-grid">
                    <div class="response-grid">
                        <section class="section-card">
                            <h2 class="section-title">Invitation context</h2>
                            <p class="section-subtitle">This is a targeted collaboration request from a brand that selected you directly.</p>
                            <div class="offer-detail-list mt-4">
                                <div class="offer-detail-item">
                                    <strong>Brand</strong>
                                    <div style="display:flex;align-items:center;gap:.65rem;">
                                        <?php echo cre8_render_avatar($brand['id'] ?? ($offre ? $offre->getIdMarque() : 0), (string) ($brand['nom'] ?? 'Brand'), 'cre8-avatar-md'); ?>
                                        <div>
                                            <span><?php echo htmlspecialchars($brand['nom'] ?? 'Unknown brand'); ?></span>
                                            <p><?php echo htmlspecialchars($brand['email'] ?? ''); ?></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="offer-detail-item">
                                    <strong>Objective</strong>
                                    <span><?php echo htmlspecialchars($offre->getObjectif()); ?></span>
                                </div>
                                <div class="offer-detail-item">
                                    <strong>Proposed budget</strong>
                                    <span><?php echo htmlspecialchars(formatMoney($offre->getBudgetPropose())); ?></span>
                                </div>
                                <div class="offer-detail-item">
                                    <strong>Deadline</strong>
                                    <span><?php echo htmlspecialchars($offre->getDateLimite()); ?></span>
                                </div>
                            </div>
                        </section>

                        <div class="detail-columns">
                            <section class="info-card">
                                <h2 class="section-title">Why you were chosen</h2>
                                <div class="note-block mt-3">
                                    <strong>Brand context</strong>
                                    <p><?php echo htmlspecialchars($offre->getRaisonChoix() !== '' ? $offre->getRaisonChoix() : 'The brand did not add a detailed selection note yet.'); ?></p>
                                </div>
                            </section>

                            <section class="info-card">
                                <h2 class="section-title">Expected collaboration fit</h2>
                                <div class="note-block mt-3">
                                    <strong>How they imagine the partnership</strong>
                                    <p><?php echo htmlspecialchars($offre->getAttenteCollaboration() !== '' ? $offre->getAttenteCollaboration() : 'No detailed collaboration expectation was attached to this invitation.'); ?></p>
                                </div>
                            </section>
                        </div>

                        <?php if ($offre->getMessagePersonnalise() !== ''): ?>
                            <section class="section-card">
                                <h2 class="section-title">Personal note from the brand</h2>
                                <div class="note-block mt-3">
                                    <strong>Message</strong>
                                    <p><?php echo htmlspecialchars($offre->getMessagePersonnalise()); ?></p>
                                </div>
                            </section>
                        <?php endif; ?>
                    </div>

                    <aside class="response-grid">
                        <section class="info-card">
                            <h2 class="section-title">Your response</h2>
                            <?php if ($response): ?>
                                <div class="review-list mt-4">
                                    <div class="review-item">
                                        <strong>Current status</strong>
                                        <span class="response-status <?php echo htmlspecialchars(responseStatusClass($response->getStatutCandidature())); ?>"><?php echo htmlspecialchars(responseStatusLabel($response)); ?></span>
                                    </div>
                                    <div class="review-item">
                                        <strong>Response path</strong>
                                        <span><?php echo htmlspecialchars($response->getResponseTypeLabel()); ?></span>
                                    </div>
                                    <div class="review-item">
                                        <strong>Your budget reply</strong>
                                        <span><?php echo htmlspecialchars(formatMoney($response->getBudgetPropose())); ?></span>
                                    </div>
                                    <div class="review-item">
                                        <strong>Your proposed timeline</strong>
                                        <span><?php echo htmlspecialchars((string) $response->getDelaiPropose()); ?> days</span>
                                    </div>
                                    <div class="review-item">
                                        <strong>Your message</strong>
                                        <span><?php echo htmlspecialchars(trim((string) $response->getMessageMotivation()) !== '' ? $response->getMessageMotivation() : 'No response message has been added yet.'); ?></span>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="response-callout mt-4">
                                    <strong>No response started yet</strong>
                                    <div class="mt-2 text-muted small">Open the response workflow to accept, decline, request negotiation, or save a draft for later.</div>
                                </div>
                            <?php endif; ?>
                        </section>

                        <section class="response-card">
                            <h2 class="section-title">Response workflow</h2>
                            <p class="section-subtitle">
                                Complete your response, terms, availability, and negotiation from the candidature workspace.
                            </p>

                            <div class="response-callout mt-4">
                                <strong><?php echo $isResponseLocked ? 'Response already stored' : ($isNegotiationOnly ? 'Negotiation continues in candidature' : 'Ready to respond'); ?></strong>
                                <div class="mt-2 text-muted small">
                                    <?php echo $isResponseLocked
                                        ? 'Open the candidature workspace to review the final response details and decision history.'
                                        : 'Use the candidature workspace for the real Accept, Decline, Negotiate, and Save as draft actions.'; ?>
                                </div>
                            </div>
                            <div class="compact-actions mt-4">
                                <a class="btn btn-primary w-100" href="<?php echo htmlspecialchars($responseWorkspaceUrl); ?>">Open candidature workspace</a>
                            </div>
                        </section>

                        <div class="compact-actions">
                            <a class="btn btn-outline-secondary w-100" href="creator_list.php">Back to offers</a>
                        </div>
                    </aside>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
<?php
$cre8PilotContext = [
    'page' => 'creator_offer_workspace',
    'mode' => 'details',
    'role' => 'createur',
    'allowedActions' => ['normal_chat', 'summarize_page', 'analyze_page'],
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
                    'offer.invitationUnavailable': 'Invitation unavailable',
                    'offer.backToOffers': 'Back to offers',
                    'offer.targetedInvitation': 'Targeted invitation',
                    'offer.removeSaved': 'Remove saved',
                    'offer.saveForLater': 'Save for later',
                    'offer.invitationContext': 'Invitation context',
                    'offer.invitationContextCopy': 'This is a targeted collaboration request from a brand that selected you directly.',
                    'offer.brand': 'Brand',
                    'offer.objective': 'Objective',
                    'offer.proposedBudget': 'Proposed budget',
                    'offer.deadline': 'Deadline',
                    'offer.whyChosen': 'Why you were chosen',
                    'offer.brandContext': 'Brand context',
                    'offer.noSelectionNote': 'The brand did not add a detailed selection note yet.',
                    'offer.expectedFit': 'Expected collaboration fit',
                    'offer.partnership': 'How they imagine the partnership',
                    'offer.noExpectation': 'No detailed collaboration expectation was attached to this invitation.',
                    'offer.personalNote': 'Personal note from the brand',
                    'offer.message': 'Message',
                    'offer.yourResponse': 'Your response',
                    'offer.currentStatus': 'Current status',
                    'offer.responsePath': 'Response path',
                    'offer.budgetReply': 'Your budget reply',
                    'offer.timeline': 'Your proposed timeline',
                    'offer.yourMessage': 'Your message',
                    'offer.noResponseMessage': 'No response message has been added yet.',
                    'offer.noResponseStarted': 'No response started yet',
                    'offer.noResponseStartedCopy': 'Open the response workflow to accept, decline, request negotiation, or save a draft for later.',
                    'offer.responseWorkflow': 'Response workflow',
                    'offer.responseWorkflowCopy': 'Complete your response, terms, availability, and negotiation from the candidature workspace.',
                    'offer.responseStored': 'Response already stored',
                    'offer.negotiationContinues': 'Negotiation continues in candidature',
                    'offer.readyRespond': 'Ready to respond',
                    'offer.reviewFinal': 'Open the candidature workspace to review the final response details and decision history.',
                    'offer.useWorkspace': 'Use the candidature workspace for the real Accept, Decline, Negotiate, and Save as draft actions.',
                    'offer.openWorkspace': 'Open candidature workspace',
                    'offer.draft': 'Draft',
                    'offer.published': 'Published',
                    'offer.closed': 'Closed',
                    'offer.expired': 'Expired',
                    'offer.archived': 'Archived',
                    'offer.active': 'Active',
                    'offer.noResponseStartedLabel': 'No response started'
                },
                fr: {
                    'offer.invitationUnavailable': 'Invitation indisponible',
                    'offer.backToOffers': 'Retour aux offres',
                    'offer.targetedInvitation': 'Invitation ciblee',
                    'offer.removeSaved': 'Retirer',
                    'offer.saveForLater': 'Enregistrer',
                    'offer.invitationContext': 'Contexte de l invitation',
                    'offer.invitationContextCopy': 'Ceci est une demande de collaboration ciblee envoyee par une marque qui vous a selectionne directement.',
                    'offer.brand': 'Marque',
                    'offer.objective': 'Objectif',
                    'offer.proposedBudget': 'Budget propose',
                    'offer.deadline': 'Echeance',
                    'offer.whyChosen': 'Pourquoi vous avez ete choisi',
                    'offer.brandContext': 'Contexte de la marque',
                    'offer.noSelectionNote': 'La marque n a pas encore ajoute de note detaillee.',
                    'offer.expectedFit': 'Adequation attendue',
                    'offer.partnership': 'Comment la marque imagine le partenariat',
                    'offer.noExpectation': 'Aucune attente detaillee n a ete ajoutee a cette invitation.',
                    'offer.personalNote': 'Note personnelle de la marque',
                    'offer.message': 'Message',
                    'offer.yourResponse': 'Votre reponse',
                    'offer.currentStatus': 'Statut actuel',
                    'offer.responsePath': 'Parcours de reponse',
                    'offer.budgetReply': 'Votre reponse budget',
                    'offer.timeline': 'Votre delai propose',
                    'offer.yourMessage': 'Votre message',
                    'offer.noResponseMessage': 'Aucun message de reponse n a encore ete ajoute.',
                    'offer.noResponseStarted': 'Aucune reponse commencee',
                    'offer.noResponseStartedCopy': 'Ouvrez le workflow pour accepter, refuser, negocier ou enregistrer un brouillon.',
                    'offer.responseWorkflow': 'Workflow de reponse',
                    'offer.responseWorkflowCopy': 'Completez votre reponse, vos conditions, disponibilites et negociation depuis l espace candidature.',
                    'offer.responseStored': 'Reponse deja enregistree',
                    'offer.negotiationContinues': 'La negociation continue dans la candidature',
                    'offer.readyRespond': 'Pret a repondre',
                    'offer.reviewFinal': 'Ouvrez l espace candidature pour revoir la reponse finale et l historique de decision.',
                    'offer.useWorkspace': 'Utilisez l espace candidature pour accepter, refuser, negocier et enregistrer un brouillon.',
                    'offer.openWorkspace': 'Ouvrir l espace candidature',
                    'offer.draft': 'Brouillon',
                    'offer.published': 'Publiee',
                    'offer.closed': 'Fermee',
                    'offer.expired': 'Expiree',
                    'offer.archived': 'Archivee',
                    'offer.active': 'Active',
                    'offer.noResponseStartedLabel': 'Aucune reponse commencee'
                }
            };
            const textKeys = {
                'Invitation unavailable': 'offer.invitationUnavailable',
                'Back to offers': 'offer.backToOffers',
                'Targeted invitation': 'offer.targetedInvitation',
                'Remove saved': 'offer.removeSaved',
                'Save for later': 'offer.saveForLater',
                'Invitation context': 'offer.invitationContext',
                'This is a targeted collaboration request from a brand that selected you directly.': 'offer.invitationContextCopy',
                'Brand': 'offer.brand',
                'Objective': 'offer.objective',
                'Proposed budget': 'offer.proposedBudget',
                'Deadline': 'offer.deadline',
                'Why you were chosen': 'offer.whyChosen',
                'Brand context': 'offer.brandContext',
                'The brand did not add a detailed selection note yet.': 'offer.noSelectionNote',
                'Expected collaboration fit': 'offer.expectedFit',
                'How they imagine the partnership': 'offer.partnership',
                'No detailed collaboration expectation was attached to this invitation.': 'offer.noExpectation',
                'Personal note from the brand': 'offer.personalNote',
                'Message': 'offer.message',
                'Your response': 'offer.yourResponse',
                'Current status': 'offer.currentStatus',
                'Response path': 'offer.responsePath',
                'Your budget reply': 'offer.budgetReply',
                'Your proposed timeline': 'offer.timeline',
                'Your message': 'offer.yourMessage',
                'No response message has been added yet.': 'offer.noResponseMessage',
                'No response started yet': 'offer.noResponseStarted',
                'Open the response workflow to accept, decline, request negotiation, or save a draft for later.': 'offer.noResponseStartedCopy',
                'Response workflow': 'offer.responseWorkflow',
                'Complete your response, terms, availability, and negotiation from the candidature workspace.': 'offer.responseWorkflowCopy',
                'Response already stored': 'offer.responseStored',
                'Negotiation continues in candidature': 'offer.negotiationContinues',
                'Ready to respond': 'offer.readyRespond',
                'Open the candidature workspace to review the final response details and decision history.': 'offer.reviewFinal',
                'Use the candidature workspace for the real Accept, Decline, Negotiate, and Save as draft actions.': 'offer.useWorkspace',
                'Open candidature workspace': 'offer.openWorkspace',
                'Draft': 'offer.draft',
                'Published': 'offer.published',
                'Closed': 'offer.closed',
                'Expired': 'offer.expired',
                'Archived': 'offer.archived',
                'Active': 'offer.active',
                'No response started': 'offer.noResponseStartedLabel'
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
    <script src="../layout/front-header.js"></script>
</body>
</html>
