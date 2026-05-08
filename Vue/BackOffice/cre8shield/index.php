<?php
session_start();

require_once __DIR__ . '/../../../Controleur/cre8shieldC.php';
require_once __DIR__ . '/../../../Controleur/condidatureC.php';

$candidatureController = new CondidatureC();
$cre8shieldController = new Cre8ShieldC();
$sessionUser = $_SESSION['utilisateur'] ?? [];

if (!isset($sessionUser['id']) || (($sessionUser['role'] ?? '') !== 'admin')) {
    $defaultAdmin = $candidatureController->getDefaultUserByRole('admin');
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

$adminId = isset($sessionUser['id']) ? (int) $sessionUser['id'] : 0;

$notice = '';
$noticeType = 'success';

$validTabs = ['high', 'medium', 'reviewed', 'escalated'];
$sortOptions = [
    'time_desc' => 'Newest recorded',
    'time_asc' => 'Oldest recorded',
    'score_desc' => 'Highest score',
    'score_asc' => 'Lowest score',
    'updated_desc' => 'Recently updated',
    'updated_asc' => 'Oldest updated',
    'status_priority' => 'Escalated priority',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['cre8shieldAction']) && $adminId > 0) {
    $action = (string) $_POST['cre8shieldAction'];
    $catchId = (int) ($_POST['catchId'] ?? 0);
    $tabRedirect = preg_replace('/[^a-z]/', '', (string) ($_POST['tab'] ?? 'high'));
    if (!in_array($tabRedirect, $validTabs, true)) {
        $tabRedirect = 'high';
    }
    $sortRedirect = preg_replace('/[^a-z_]/', '', (string) ($_POST['sort'] ?? $_GET['sort'] ?? 'time_desc'));
    if (!array_key_exists($sortRedirect, $sortOptions)) {
        $sortRedirect = 'time_desc';
    }

    if ($catchId > 0 && $cre8shieldController->isAvailable()) {
        $ok = false;
        switch ($action) {
            case 'mark_reviewed':
                $ok = $cre8shieldController->markReviewed($catchId, $adminId);
                $notice = $ok ? 'Catch marked as reviewed.' : 'Could not mark this catch as reviewed.';
                break;
            case 'ignore':
                $ok = $cre8shieldController->ignoreCatch($catchId, $adminId);
                $notice = $ok ? 'Catch ignored.' : 'Could not ignore this catch.';
                break;
            case 'escalate':
                $ok = $cre8shieldController->escalateCatch($catchId, $adminId);
                $notice = $ok ? 'Catch escalated for priority review.' : 'Could not escalate this catch.';
                break;
            case 'resolve':
                $ok = $cre8shieldController->resolveCatch($catchId, $adminId);
                $notice = $ok ? 'Catch resolved.' : 'Could not resolve this catch.';
                break;
            default:
                $notice = 'Unknown action.';
                break;
        }
        $noticeType = $ok ? 'success' : 'danger';
    } else {
        $notice = 'Cre8Shield monitor table is not available.';
        $noticeType = 'danger';
    }

    header('Location: index.php?tab=' . $tabRedirect . '&sort=' . urlencode($sortRedirect) . '&notice=' . urlencode($notice) . '&noticeType=' . urlencode($noticeType));
    exit;
}

$tab = preg_replace('/[^a-z]/', '', (string) ($_GET['tab'] ?? 'high'));
if (!in_array($tab, $validTabs, true)) {
    $tab = 'high';
}
$sort = preg_replace('/[^a-z_]/', '', (string) ($_GET['sort'] ?? 'time_desc'));
if (!array_key_exists($sort, $sortOptions)) {
    $sort = 'time_desc';
}

$notice = trim((string) ($_GET['notice'] ?? ''));
$noticeType = trim((string) ($_GET['noticeType'] ?? 'success'));

$tableAvailable = $cre8shieldController->isAvailable();

$rows = [];
$counts = ['high' => 0, 'medium' => 0, 'reviewed' => 0, 'escalated' => 0];

if ($tableAvailable) {
    $counts = $cre8shieldController->getMonitorCounts();
    if ($tab === 'high') {
        $rows = $cre8shieldController->listByRisk('high', 200, 0, $sort);
    } elseif ($tab === 'medium') {
        $rows = $cre8shieldController->listByRisk('medium', 200, 0, $sort);
    } elseif ($tab === 'escalated') {
        $rows = $cre8shieldController->listEscalated(200, 0, $sort);
    } else {
        $rows = $cre8shieldController->listReviewed(200, 0, $sort);
    }
}

/**
 * Decide whether a catch points at an offer, a candidature, or just a chat
 * prompt. The DB does not store an explicit item_type, so we fall back to the
 * page where the catch was raised plus the source_type. This keeps the
 * BackOffice "View source" modal pointed at the right snapshot template.
 */
function cre8shieldDetermineSourceKind($page, $sourceType, $sourceId)
{
    $page = strtolower((string) $page);
    $sourceType = strtolower((string) $sourceType);
    $sourceIdStr = trim((string) $sourceId);
    $hasNumericSourceId = $sourceIdStr !== '' && ctype_digit($sourceIdStr);

    if (!$hasNumericSourceId) {
        return 'prompt';
    }
    if (str_contains($page, 'offer_workspace')) {
        return 'offer';
    }
    if (str_contains($page, 'candidature_workspace')) {
        return 'candidature';
    }
    if (in_array($sourceType, ['chat_message', 'chat_link', 'chat_explain'], true)) {
        return 'prompt';
    }

    return 'prompt';
}

/**
 * Pull just enough offer fields to render the "View source" snapshot card.
 * Joined with the brand and the targeted creator so the modal can show both
 * sides without a second round trip per row.
 */
function cre8shieldFetchOfferSnapshots(PDO $pdo, array $ids): array
{
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn ($v) => $v > 0)));
    if ($ids === []) {
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $sql = "
        SELECT
            o.idOffre, o.titre, o.description, o.objectif, o.budgetPropose,
            o.datePublication, o.dateLimite, o.statutOffre,
            o.idMarque, o.idCreateurCible,
            mu.nom AS brandName, mu.email AS brandEmail, mu.role AS brandRole,
            cu.nom AS creatorName, cu.email AS creatorEmail, cu.role AS creatorRole
        FROM offre o
        LEFT JOIN utilisateur mu ON mu.id = o.idMarque
        LEFT JOIN utilisateur cu ON cu.id = o.idCreateurCible
        WHERE o.idOffre IN ($placeholders)
    ";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($ids);
        $out = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $out[(int) $row['idOffre']] = $row;
        }
        return $out;
    } catch (Throwable $e) {
        return [];
    }
}

/**
 * Same idea as the offer snapshot, but for candidatures: bring in the creator
 * who applied, the linked offer (when origineCandidature = par_offre), and the
 * brand on that offer so the modal can read like a quick incident sheet.
 */
function cre8shieldFetchCandidatureSnapshots(PDO $pdo, array $ids): array
{
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn ($v) => $v > 0)));
    if ($ids === []) {
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $sql = "
        SELECT
            c.idCandidature, c.idCreateur, c.idSource, c.origineCandidature,
            c.statutCandidature, c.budgetPropose, c.delaiPropose,
            c.messageMotivation, c.dateCandidature, c.dateDerniereModification,
            c.noteDecision, c.motifRefus,
            cu.nom AS creatorName, cu.email AS creatorEmail, cu.role AS creatorRole,
            o.idOffre, o.titre AS offerTitle, o.idMarque,
            mu.nom AS brandName, mu.email AS brandEmail, mu.role AS brandRole
        FROM candidature c
        LEFT JOIN utilisateur cu ON cu.id = c.idCreateur
        LEFT JOIN offre o ON c.origineCandidature = 'par_offre' AND o.idOffre = c.idSource
        LEFT JOIN utilisateur mu ON mu.id = o.idMarque
        WHERE c.idCandidature IN ($placeholders)
    ";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($ids);
        $out = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $out[(int) $row['idCandidature']] = $row;
        }
        return $out;
    } catch (Throwable $e) {
        return [];
    }
}

$reporterIds = [];
$reportedIds = [];
$reviewerIds = [];
$offerSourceIds = [];
$candidatureSourceIds = [];
foreach ($rows as $rowForLookup) {
    $rIdR = (int) ($rowForLookup['reporter_user_id'] ?? 0);
    if ($rIdR > 0) {
        $reporterIds[] = $rIdR;
    }
    $rIdT = (int) ($rowForLookup['reported_user_id'] ?? 0);
    if ($rIdT > 0) {
        $reportedIds[] = $rIdT;
    }
    $rev = (int) ($rowForLookup['reviewed_by'] ?? 0);
    if ($rev > 0) {
        $reviewerIds[] = $rev;
    }

    $kind = cre8shieldDetermineSourceKind(
        $rowForLookup['page'] ?? '',
        $rowForLookup['source_type'] ?? '',
        $rowForLookup['source_id'] ?? ''
    );
    if ($kind === 'offer') {
        $offerSourceIds[] = (int) $rowForLookup['source_id'];
    } elseif ($kind === 'candidature') {
        $candidatureSourceIds[] = (int) $rowForLookup['source_id'];
    }
}

$cre8shieldUserMap = $candidatureController->getUsersByIds(array_merge($reporterIds, $reportedIds, $reviewerIds));

$cre8shieldPdo = config::getConnexion();
$cre8shieldOfferSnapshots = cre8shieldFetchOfferSnapshots($cre8shieldPdo, $offerSourceIds);
$cre8shieldCandidatureSnapshots = cre8shieldFetchCandidatureSnapshots($cre8shieldPdo, $candidatureSourceIds);

// Some offers point to a brand or creator we have not yet loaded; pull those
// extra users in one go so the offer / candidature modals never miss a name.
$extraUserIds = [];
foreach ($cre8shieldOfferSnapshots as $oSnap) {
    $b = (int) ($oSnap['idMarque'] ?? 0);
    $c = (int) ($oSnap['idCreateurCible'] ?? 0);
    if ($b > 0 && !isset($cre8shieldUserMap[$b])) {
        $extraUserIds[] = $b;
    }
    if ($c > 0 && !isset($cre8shieldUserMap[$c])) {
        $extraUserIds[] = $c;
    }
}
foreach ($cre8shieldCandidatureSnapshots as $cSnap) {
    $cr = (int) ($cSnap['idCreateur'] ?? 0);
    $br = (int) ($cSnap['idMarque'] ?? 0);
    if ($cr > 0 && !isset($cre8shieldUserMap[$cr])) {
        $extraUserIds[] = $cr;
    }
    if ($br > 0 && !isset($cre8shieldUserMap[$br])) {
        $extraUserIds[] = $br;
    }
}
if ($extraUserIds !== []) {
    $extraUsers = $candidatureController->getUsersByIds($extraUserIds);
    foreach ($extraUsers as $extraId => $extraRow) {
        $cre8shieldUserMap[(int) $extraId] = $extraRow;
    }
}

$tabRowCount = count($rows);
$tabPromptCount = 0;
$tabLinkedSourceCount = 0;
$tabReportedKnownCount = 0;
$tabReporterKnownCount = 0;
$tabEscalatedCount = 0;
$tabRiskScoreSum = 0;
foreach ($rows as $metricRow) {
    $kind = cre8shieldDetermineSourceKind(
        $metricRow['page'] ?? '',
        $metricRow['source_type'] ?? '',
        $metricRow['source_id'] ?? ''
    );
    if ($kind === 'prompt') {
        $tabPromptCount++;
    } else {
        $tabLinkedSourceCount++;
    }
    if ((int) ($metricRow['reported_user_id'] ?? 0) > 0) {
        $tabReportedKnownCount++;
    }
    if ((int) ($metricRow['reporter_user_id'] ?? 0) > 0) {
        $tabReporterKnownCount++;
    }
    if (strtolower((string) ($metricRow['status'] ?? '')) === 'escalated') {
        $tabEscalatedCount++;
    }
    $tabRiskScoreSum += max(0, min(100, (int) ($metricRow['risk_score'] ?? 0)));
}
$tabUnknownReportedCount = max(0, $tabRowCount - $tabReportedKnownCount);
$tabReportedCoverage = $tabRowCount > 0 ? (int) round(($tabReportedKnownCount / $tabRowCount) * 100) : 0;
$tabAverageRiskScore = $tabRowCount > 0 ? (int) round($tabRiskScoreSum / $tabRowCount) : 0;
$openQueueTotal = (int) ($counts['high'] ?? 0) + (int) ($counts['medium'] ?? 0);
$tabTitle = match ($tab) {
    'high' => 'High risks',
    'medium' => 'Medium risks',
    'escalated' => 'Escalated',
    'reviewed' => 'Reviewed / resolved',
    default => 'Current tab',
};

function cre8shieldFormatDateLabel($value, $fallback = 'Not available')
{
    if (!$value) {
        return $fallback;
    }
    $ts = strtotime((string) $value);

    return $ts === false ? (string) $value : date('Y-m-d H:i', $ts);
}

function cre8shieldDecodeCategories($raw)
{
    $raw = trim((string) $raw);
    if ($raw === '') {
        return [];
    }
    if ($raw[0] === '[' || $raw[0] === '{') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return array_values(array_filter(array_map('strval', $decoded)));
        }
    }

    return array_values(array_filter(array_map('trim', explode(',', $raw))));
}

function cre8shieldExcerpt($text, $length = 220)
{
    $text = trim((string) $text);
    if ($text === '') {
        return '';
    }
    if (function_exists('mb_strlen') && mb_strlen($text) > $length) {
        return mb_substr($text, 0, $length - 1) . '…';
    }
    if (strlen($text) > $length) {
        return substr($text, 0, $length - 1) . '…';
    }

    return $text;
}

function cre8shieldStatusLabel($status)
{
    $s = strtolower(trim((string) $status));

    return match ($s) {
        'open' => 'Open',
        'reviewed' => 'Reviewed',
        'ignored' => 'Ignored',
        'escalated' => 'Escalated',
        'resolved' => 'Resolved',
        default => ucfirst($s !== '' ? $s : 'open'),
    };
}

/**
 * Convert a Cre8Shield internal category slug (snake_case) into a clean,
 * human-readable label. Unknown slugs are gracefully title-cased so the UI
 * never shows raw labels even when new categories are introduced.
 */
function cre8shieldPrettyCategory($slug)
{
    $key = strtolower(trim((string) $slug));
    if ($key === '') {
        return '';
    }

    static $map = [
        'off_platform_payment' => 'Off-platform payment',
        'platform_bypass' => 'Platform bypass',
        'suspicious_link' => 'Suspicious link',
        'phishing' => 'Phishing',
        'credential_theft' => 'Credential theft',
        'suspicious_invoice' => 'Suspicious invoice',
        'payment_risk' => 'Payment risk',
        'social_engineering' => 'Social engineering',
        'pii_leak' => 'PII leak',
        'malware' => 'Malware',
        'spam' => 'Spam',
        'harassment' => 'Harassment',
        'impersonation' => 'Impersonation',
    ];

    if (isset($map[$key])) {
        return $map[$key];
    }

    return ucwords(str_replace(['_', '-'], ' ', $key));
}

function cre8shieldPrettySource($value)
{
    $key = strtolower(trim((string) $value));
    if ($key === '') {
        return 'Unknown';
    }

    static $map = [
        'chat_message' => 'Chat message',
        'chat_link' => 'Chat link',
        'page_scan' => 'Page scan',
        'page_visit' => 'Page visit',
        'manual_report' => 'Manual report',
        'background_scan' => 'Background scan',
    ];

    return $map[$key] ?? ucwords(str_replace(['_', '-'], ' ', $key));
}

function cre8shieldPrettyRoleId($role, $userId)
{
    $role = trim((string) $role);
    $userId = (int) $userId;
    if ($role === '' && $userId <= 0) {
        return '—';
    }
    $rolePart = $role !== '' ? ucfirst($role) : 'User';
    $idPart = $userId > 0 ? ' #' . $userId : '';

    return $rolePart . $idPart;
}

/**
 * Render the Reporter / Reported cell. When we know the user id, the cell
 * becomes a clickable button that opens the user modal; otherwise it falls
 * back to the legacy text-only label so the layout never collapses.
 */
function cre8shieldRenderUserCell($role, $userId, array $userMap, $variant = 'reporter')
{
    $userId = (int) $userId;
    $role = trim((string) $role);
    $variant = $variant === 'reported' ? 'reported' : 'reporter';
    if ($userId <= 0) {
        $label = cre8shieldPrettyRoleId($role, 0);
        if ($role === '') {
            return '<span class="cre8shield-user-link is-empty" title="No user identified yet for this role.">—</span>';
        }
        return '<span class="cre8shield-user-link is-empty" title="Reported role is known but the specific user id was not captured.">'
            . htmlspecialchars($label)
            . '<small> (no id)</small></span>';
    }

    $known = $userMap[$userId] ?? null;
    $name = $known ? trim((string) ($known['nom'] ?? '')) : '';
    $effectiveRole = $role !== '' ? $role : (string) ($known['role'] ?? '');
    $primary = $name !== '' ? $name : ('User #' . $userId);

    return '<button type="button" class="cre8shield-user-link variant-' . htmlspecialchars($variant) . '" data-cre8shield-user-trigger data-user-id="' . $userId . '" title="Open user details">'
        . '<strong>' . htmlspecialchars($primary) . '</strong>'
        . '<span class="cre8shield-user-link-meta">' . htmlspecialchars(($effectiveRole !== '' ? ucfirst($effectiveRole) : 'User') . ' · #' . $userId) . '</span>'
        . '</button>';
}

function cre8shieldRoleColorClass($role)
{
    return match (strtolower((string) $role)) {
        'marque' => 'role-brand',
        'createur' => 'role-creator',
        'admin' => 'role-admin',
        default => 'role-other',
    };
}

function cre8shieldOfferStatusLabel($status)
{
    return match ((string) $status) {
        'brouillon' => 'Draft',
        'publiee' => 'Published',
        'archivee' => 'Archived',
        'annulee' => 'Cancelled',
        default => ucwords(str_replace('_', ' ', (string) $status)),
    };
}

function cre8shieldCandidatureStatusLabel($status)
{
    return match ((string) $status) {
        'brouillon' => 'Draft response',
        'envoyee' => 'Accepted invitation',
        'en_etude' => 'Response under review',
        'negociation' => 'Negotiation requested',
        'acceptee' => 'Accepted terms',
        'refusee' => 'Refused by brand',
        'retiree' => 'Declined invitation',
        default => ucwords(str_replace('_', ' ', (string) $status)),
    };
}

function cre8shieldRenderUserCardTemplate(int $userId, array $user)
{
    $name = trim((string) ($user['nom'] ?? '')) !== '' ? (string) $user['nom'] : ('User #' . $userId);
    $email = (string) ($user['email'] ?? '');
    $role = (string) ($user['role'] ?? '');
    $statut = (string) ($user['statut'] ?? '');
    $createdAt = (string) ($user['date_creation'] ?? '');
    $createdLabel = $createdAt !== '' ? cre8shieldFormatDateLabel($createdAt, '—') : '—';
    $roleLower = strtolower($role);
    $workspaceUrl = $roleLower === 'createur'
        ? '../condidature/index.php'
        : '../offre/index.php';
    $workspaceLabel = $roleLower === 'createur'
        ? 'Open creator workspace'
        : ($roleLower === 'marque' ? 'Open brand workspace' : 'Open admin workspace');

    ob_start();
    ?>
    <article class="cre8shield-modal-user-card">
        <header class="cre8shield-modal-user-head">
            <span class="cre8shield-role-pill <?php echo htmlspecialchars(cre8shieldRoleColorClass($role)); ?>">
                <?php echo htmlspecialchars($role !== '' ? ucfirst($role) : 'User'); ?>
            </span>
            <span class="cre8shield-modal-user-id">ID #<?php echo $userId; ?></span>
        </header>
        <h3 class="cre8shield-modal-user-name"><?php echo htmlspecialchars($name); ?></h3>
        <?php if ($email !== ''): ?>
            <p class="cre8shield-modal-user-email">
                <a href="mailto:<?php echo htmlspecialchars($email); ?>"><?php echo htmlspecialchars($email); ?></a>
            </p>
        <?php endif; ?>
        <dl class="cre8shield-modal-grid">
            <?php if ($statut !== ''): ?>
                <div>
                    <dt>Status</dt>
                    <dd><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $statut))); ?></dd>
                </div>
            <?php endif; ?>
            <div>
                <dt>Joined</dt>
                <dd><?php echo htmlspecialchars($createdLabel); ?></dd>
            </div>
            <div>
                <dt>Role</dt>
                <dd><?php echo htmlspecialchars($role !== '' ? ucfirst($role) : 'User'); ?></dd>
            </div>
        </dl>
        <div class="cre8shield-modal-user-actions">
            <a class="cre8shield-action-btn" href="<?php echo htmlspecialchars($workspaceUrl); ?>"><?php echo htmlspecialchars($workspaceLabel); ?></a>
        </div>
    </article>
    <?php
    return (string) ob_get_clean();
}

function cre8shieldRenderOfferSourceTemplate(int $offerId, array $offer, array $userMap)
{
    $title = (string) ($offer['titre'] ?? ('Offer #' . $offerId));
    $status = (string) ($offer['statutOffre'] ?? '');
    $statusLabel = cre8shieldOfferStatusLabel($status);
    $description = trim((string) ($offer['description'] ?? ''));
    $objective = trim((string) ($offer['objectif'] ?? ''));
    $budget = $offer['budgetPropose'] ?? null;
    $publication = (string) ($offer['datePublication'] ?? '');
    $deadline = (string) ($offer['dateLimite'] ?? '');
    $brandId = (int) ($offer['idMarque'] ?? 0);
    $creatorId = (int) ($offer['idCreateurCible'] ?? 0);
    $brandName = (string) ($offer['brandName'] ?? '');
    $creatorName = (string) ($offer['creatorName'] ?? '');
    $inspectUrl = '../offre/index.php?idOffre=' . $offerId;

    ob_start();
    ?>
    <article class="cre8shield-modal-source-card kind-offer">
        <header class="cre8shield-modal-source-head">
            <span class="cre8shield-source-kind-pill kind-offer">Offer</span>
            <span class="cre8shield-modal-source-id">ID #<?php echo $offerId; ?></span>
            <?php if ($status !== ''): ?>
                <span class="cre8shield-source-status status-<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($statusLabel); ?></span>
            <?php endif; ?>
        </header>
        <h3 class="cre8shield-modal-source-title"><?php echo htmlspecialchars($title); ?></h3>
        <?php if ($objective !== ''): ?>
            <p class="cre8shield-modal-source-line"><strong>Objective:</strong> <?php echo htmlspecialchars($objective); ?></p>
        <?php endif; ?>
        <?php if ($description !== ''): ?>
            <div class="cre8shield-modal-source-block">
                <h5>Description</h5>
                <p><?php echo nl2br(htmlspecialchars(cre8shieldExcerpt($description, 480))); ?></p>
            </div>
        <?php endif; ?>
        <dl class="cre8shield-modal-grid">
            <?php if ($budget !== null && $budget !== ''): ?>
                <div>
                    <dt>Budget</dt>
                    <dd>EUR <?php echo htmlspecialchars(number_format((float) $budget, 2, '.', ',')); ?></dd>
                </div>
            <?php endif; ?>
            <?php if ($publication !== ''): ?>
                <div>
                    <dt>Published</dt>
                    <dd><?php echo htmlspecialchars($publication); ?></dd>
                </div>
            <?php endif; ?>
            <?php if ($deadline !== ''): ?>
                <div>
                    <dt>Deadline</dt>
                    <dd><?php echo htmlspecialchars($deadline); ?></dd>
                </div>
            <?php endif; ?>
            <div>
                <dt>Brand</dt>
                <dd>
                    <?php if ($brandId > 0): ?>
                        <button type="button" class="cre8shield-mini-user-btn" data-cre8shield-user-trigger data-user-id="<?php echo $brandId; ?>">
                            <?php echo htmlspecialchars($brandName !== '' ? $brandName : ('Brand #' . $brandId)); ?>
                            <span>· #<?php echo $brandId; ?></span>
                        </button>
                    <?php else: ?>
                        <span>—</span>
                    <?php endif; ?>
                </dd>
            </div>
            <div>
                <dt>Targeted creator</dt>
                <dd>
                    <?php if ($creatorId > 0): ?>
                        <button type="button" class="cre8shield-mini-user-btn" data-cre8shield-user-trigger data-user-id="<?php echo $creatorId; ?>">
                            <?php echo htmlspecialchars($creatorName !== '' ? $creatorName : ('Creator #' . $creatorId)); ?>
                            <span>· #<?php echo $creatorId; ?></span>
                        </button>
                    <?php else: ?>
                        <span>No creator selected</span>
                    <?php endif; ?>
                </dd>
            </div>
        </dl>
        <div class="cre8shield-modal-source-actions">
            <a class="cre8shield-action-btn is-primary" href="<?php echo htmlspecialchars($inspectUrl); ?>">Inspect in admin offer workspace</a>
        </div>
    </article>
    <?php
    return (string) ob_get_clean();
}

function cre8shieldRenderCandidatureSourceTemplate(int $candidatureId, array $candidature)
{
    $status = (string) ($candidature['statutCandidature'] ?? '');
    $statusLabel = cre8shieldCandidatureStatusLabel($status);
    $origin = (string) ($candidature['origineCandidature'] ?? '');
    $offerTitle = (string) ($candidature['offerTitle'] ?? '');
    $offerId = (int) ($candidature['idOffre'] ?? 0);
    $brandId = (int) ($candidature['idMarque'] ?? 0);
    $brandName = (string) ($candidature['brandName'] ?? '');
    $creatorId = (int) ($candidature['idCreateur'] ?? 0);
    $creatorName = (string) ($candidature['creatorName'] ?? '');
    $message = trim((string) ($candidature['messageMotivation'] ?? ''));
    $budget = $candidature['budgetPropose'] ?? null;
    $delai = $candidature['delaiPropose'] ?? null;
    $submitted = (string) ($candidature['dateCandidature'] ?? '');
    $updated = (string) ($candidature['dateDerniereModification'] ?? '');
    $inspectUrl = '../condidature/details.php?idCandidature=' . $candidatureId;

    ob_start();
    ?>
    <article class="cre8shield-modal-source-card kind-candidature">
        <header class="cre8shield-modal-source-head">
            <span class="cre8shield-source-kind-pill kind-candidature">Candidature</span>
            <span class="cre8shield-modal-source-id">ID #<?php echo $candidatureId; ?></span>
            <?php if ($status !== ''): ?>
                <span class="cre8shield-source-status status-<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($statusLabel); ?></span>
            <?php endif; ?>
        </header>
        <h3 class="cre8shield-modal-source-title">
            <?php echo htmlspecialchars($offerTitle !== '' ? $offerTitle : ('Candidature #' . $candidatureId)); ?>
        </h3>
        <?php if ($origin !== ''): ?>
            <p class="cre8shield-modal-source-line">
                <strong>Origin:</strong>
                <?php echo htmlspecialchars($origin === 'par_offre' ? 'Offer invitation' : ucwords(str_replace('_', ' ', $origin))); ?>
            </p>
        <?php endif; ?>
        <?php if ($message !== ''): ?>
            <div class="cre8shield-modal-source-block">
                <h5>Creator message</h5>
                <p><?php echo nl2br(htmlspecialchars(cre8shieldExcerpt($message, 480))); ?></p>
            </div>
        <?php endif; ?>
        <dl class="cre8shield-modal-grid">
            <?php if ($budget !== null && $budget !== ''): ?>
                <div>
                    <dt>Proposed budget</dt>
                    <dd>EUR <?php echo htmlspecialchars(number_format((float) $budget, 2, '.', ',')); ?></dd>
                </div>
            <?php endif; ?>
            <?php if ($delai !== null && $delai !== ''): ?>
                <div>
                    <dt>Proposed timeline</dt>
                    <dd><?php echo (int) $delai; ?> days</dd>
                </div>
            <?php endif; ?>
            <?php if ($submitted !== ''): ?>
                <div>
                    <dt>Submitted</dt>
                    <dd><?php echo htmlspecialchars(cre8shieldFormatDateLabel($submitted, '—')); ?></dd>
                </div>
            <?php endif; ?>
            <?php if ($updated !== ''): ?>
                <div>
                    <dt>Last update</dt>
                    <dd><?php echo htmlspecialchars(cre8shieldFormatDateLabel($updated, '—')); ?></dd>
                </div>
            <?php endif; ?>
            <div>
                <dt>Creator</dt>
                <dd>
                    <?php if ($creatorId > 0): ?>
                        <button type="button" class="cre8shield-mini-user-btn" data-cre8shield-user-trigger data-user-id="<?php echo $creatorId; ?>">
                            <?php echo htmlspecialchars($creatorName !== '' ? $creatorName : ('Creator #' . $creatorId)); ?>
                            <span>· #<?php echo $creatorId; ?></span>
                        </button>
                    <?php else: ?>
                        <span>—</span>
                    <?php endif; ?>
                </dd>
            </div>
            <div>
                <dt>Brand</dt>
                <dd>
                    <?php if ($brandId > 0): ?>
                        <button type="button" class="cre8shield-mini-user-btn" data-cre8shield-user-trigger data-user-id="<?php echo $brandId; ?>">
                            <?php echo htmlspecialchars($brandName !== '' ? $brandName : ('Brand #' . $brandId)); ?>
                            <span>· #<?php echo $brandId; ?></span>
                        </button>
                    <?php else: ?>
                        <span>—</span>
                    <?php endif; ?>
                </dd>
            </div>
        </dl>
        <div class="cre8shield-modal-source-actions">
            <a class="cre8shield-action-btn is-primary" href="<?php echo htmlspecialchars($inspectUrl); ?>">Open candidature</a>
            <?php if ($offerId > 0): ?>
                <a class="cre8shield-action-btn" href="../offre/index.php?idOffre=<?php echo $offerId; ?>">Open linked offer</a>
            <?php endif; ?>
        </div>
    </article>
    <?php
    return (string) ob_get_clean();
}

function cre8shieldRenderPromptSourceTemplate(array $row)
{
    $sourceType = cre8shieldPrettySource((string) ($row['source_type'] ?? ''));
    $sourceLabel = (string) ($row['source_label'] ?? '');
    $page = (string) ($row['page'] ?? '');
    $mode = (string) ($row['mode'] ?? '');
    $sanitized = (string) ($row['sanitized_message'] ?? '');
    $rawSnapshot = (string) ($row['raw_message_snapshot'] ?? '');
    $aiDecision = (string) ($row['ai_decision'] ?? '');
    $aiRationale = (string) ($row['ai_rationale'] ?? '');

    ob_start();
    ?>
    <article class="cre8shield-modal-source-card kind-prompt">
        <header class="cre8shield-modal-source-head">
            <span class="cre8shield-source-kind-pill kind-prompt">Prompt</span>
            <span class="cre8shield-modal-source-id"><?php echo htmlspecialchars($sourceType); ?></span>
        </header>
        <?php if ($sourceLabel !== ''): ?>
            <p class="cre8shield-modal-source-line"><strong>Source label:</strong> <?php echo htmlspecialchars($sourceLabel); ?></p>
        <?php endif; ?>
        <?php if ($page !== ''): ?>
            <p class="cre8shield-modal-source-line"><strong>Page:</strong> <?php echo htmlspecialchars($page . ($mode !== '' ? ' · ' . $mode : '')); ?></p>
        <?php endif; ?>
        <?php if ($sanitized !== ''): ?>
            <div class="cre8shield-modal-source-block">
                <h5>Sanitized message</h5>
                <pre class="cre8shield-pre"><?php echo htmlspecialchars($sanitized); ?></pre>
            </div>
        <?php endif; ?>
        <?php if ($rawSnapshot !== '' && $rawSnapshot !== $sanitized): ?>
            <div class="cre8shield-modal-source-block">
                <h5>Raw snapshot (admin only)</h5>
                <pre class="cre8shield-pre is-raw"><?php echo htmlspecialchars($rawSnapshot); ?></pre>
            </div>
        <?php endif; ?>
        <?php if ($aiDecision !== ''): ?>
            <p class="cre8shield-modal-source-line"><strong>AI decision:</strong> <?php echo htmlspecialchars($aiDecision); ?></p>
        <?php endif; ?>
        <?php if ($aiRationale !== ''): ?>
            <div class="cre8shield-modal-source-block">
                <h5>AI rationale</h5>
                <p><?php echo nl2br(htmlspecialchars($aiRationale)); ?></p>
            </div>
        <?php endif; ?>
    </article>
    <?php
    return (string) ob_get_clean();
}

$tabBase = function ($name) use ($tab) {
    return 'nav-link cre8shield-tab' . ($tab === $name ? ' is-active active' : '');
};
$tabHref = function ($name) use ($sort) {
    return 'index.php?tab=' . urlencode((string) $name) . '&sort=' . urlencode((string) $sort);
};

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Back Office - Cre8Shield Monitor</title>
    <link rel="stylesheet" href="../css/backoffice.css">
    <link rel="stylesheet" href="../css/new_style_backoffice.css">
    <link rel="stylesheet" href="../offre/offre-admin.css?v=<?php echo urlencode((string) (@filemtime(__DIR__ . '/../offre/offre-admin.css') ?: 0)); ?>">
    <link rel="stylesheet" href="../condidature/condidature-admin.css?v=<?php echo urlencode((string) (@filemtime(__DIR__ . '/../condidature/condidature-admin.css') ?: 0)); ?>">
    <link rel="stylesheet" href="cre8shield-admin.css?v=<?php echo urlencode((string) (@filemtime(__DIR__ . '/cre8shield-admin.css') ?: 0)); ?>">
</head>
<body class="cre8-admin-layout">
    <div class="container-scroller cre8-admin-page">
        <?php require_once dirname(__DIR__) . '/layout/sidebar.php'; ?>
        <main class="page-body-wrapper cre8-admin-main">
            <?php require_once dirname(__DIR__) . '/layout/header.php'; ?>
            <div class="main-panel">
            <div class="content-wrapper admin-shell">
                <header class="admin-header card grid-margin">
                    <div class="admin-header-main card-body">
                        <div>
                            <h1>Cre8Shield Monitor</h1>
                            <p>Review suspicious AI/security catches detected from prompts and page scans.</p>
                        </div>
                    </div>
                </header>

                <?php if ($notice !== ''): ?>
                    <div class="admin-flash <?php echo $noticeType === 'danger' ? 'error' : 'success'; ?>">
                        <?php echo htmlspecialchars($notice); ?>
                    </div>
                <?php endif; ?>

                <?php if (!$tableAvailable): ?>
                    <div class="admin-flash error">
                        The cre8shield_catches table is not available on this database. Cre8Pilot will continue to run rule-based and AI checks, but catches cannot be persisted yet.
                    </div>
                <?php endif; ?>

                <section class="cre8shield-summary">
                    <article class="card card-body">
                        <h3>Open queue now</h3>
                        <p><?php echo $openQueueTotal; ?></p>
                        <small>
                            High: <?php echo (int) ($counts['high'] ?? 0); ?> ·
                            Medium: <?php echo (int) ($counts['medium'] ?? 0); ?> ·
                            Escalated: <?php echo (int) ($counts['escalated'] ?? 0); ?>
                        </small>
                    </article>
                    <article class="card card-body">
                        <h3><?php echo htmlspecialchars($tabTitle); ?></h3>
                        <p><?php echo $tabRowCount; ?></p>
                        <small>
                            Avg risk score: <?php echo $tabAverageRiskScore; ?>/100
                            <?php if ($tabEscalatedCount > 0): ?>
                                · Escalated in tab: <?php echo $tabEscalatedCount; ?>
                            <?php endif; ?>
                        </small>
                    </article>
                    <article class="card card-body">
                        <h3>Reported user identified</h3>
                        <p><?php echo $tabReportedCoverage; ?>%</p>
                        <small>
                            Known: <?php echo $tabReportedKnownCount; ?>/<?php echo $tabRowCount; ?>
                            <?php if ($tabUnknownReportedCount > 0): ?>
                                · Missing IDs: <?php echo $tabUnknownReportedCount; ?>
                            <?php endif; ?>
                        </small>
                    </article>
                    <article class="card card-body">
                        <h3>Source quality in tab</h3>
                        <p><?php echo $tabLinkedSourceCount; ?></p>
                        <small>
                            Linked offers/candidatures: <?php echo $tabLinkedSourceCount; ?>
                            · Prompt-only: <?php echo $tabPromptCount; ?>
                        </small>
                    </article>
                </section>

                <nav class="nav nav-tabs cre8shield-tabs" aria-label="Cre8Shield tabs">
                    <a class="<?php echo $tabBase('high'); ?>" href="<?php echo htmlspecialchars($tabHref('high')); ?>">
                        High risks <span class="badge badge-danger cre8shield-tab-count"><?php echo (int) ($counts['high'] ?? 0); ?></span>
                    </a>
                    <a class="<?php echo $tabBase('medium'); ?>" href="<?php echo htmlspecialchars($tabHref('medium')); ?>">
                        Medium risks <span class="badge badge-warning cre8shield-tab-count"><?php echo (int) ($counts['medium'] ?? 0); ?></span>
                    </a>
                    <a class="<?php echo $tabBase('escalated'); ?>" href="<?php echo htmlspecialchars($tabHref('escalated')); ?>">
                        Escalated <span class="badge badge-danger cre8shield-tab-count"><?php echo (int) ($counts['escalated'] ?? 0); ?></span>
                    </a>
                    <a class="<?php echo $tabBase('reviewed'); ?>" href="<?php echo htmlspecialchars($tabHref('reviewed')); ?>">
                        Reviewed / resolved <span class="badge badge-success cre8shield-tab-count"><?php echo (int) ($counts['reviewed'] ?? 0); ?></span>
                    </a>
                </nav>

                <section class="card grid-margin cre8shield-toolbar" aria-label="Cre8Shield ordering controls">
                    <div>
                        <strong>Order catches</strong>
                        <span><?php echo htmlspecialchars($sortOptions[$sort] ?? 'Newest recorded'); ?></span>
                    </div>
                    <form method="get" action="index.php" class="cre8shield-sort-form form-inline">
                        <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
                        <label for="cre8shieldSort">Sort by</label>
                        <select id="cre8shieldSort" name="sort" class="form-control" onchange="this.form.submit()">
                            <?php foreach ($sortOptions as $sortKey => $sortLabel): ?>
                                <option value="<?php echo htmlspecialchars($sortKey); ?>"<?php echo $sort === $sortKey ? ' selected' : ''; ?>>
                                    <?php echo htmlspecialchars($sortLabel); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary">Apply</button>
                    </form>
                </section>

                <?php if (empty($rows)): ?>
                    <div class="cre8shield-empty">
                        <strong>No catches in this tab.</strong>
                        <p>Cre8Shield will list medium and high risk findings as soon as they are raised by chat or page scans.</p>
                    </div>
                <?php else: ?>
                    <div class="cre8shield-grid">
                        <?php foreach ($rows as $row): ?>
                            <?php
                            $level = strtolower((string) ($row['risk_level'] ?? 'low'));
                            $score = (int) ($row['risk_score'] ?? 0);
                            $cats = cre8shieldDecodeCategories($row['risk_categories'] ?? '');
                            $status = strtolower((string) ($row['status'] ?? 'open'));
                            $riskBadgeClass = match ($level) {
                                'high' => 'badge-danger',
                                'medium' => 'badge-warning',
                                'low', 'info' => 'badge-info',
                                default => 'badge-secondary',
                            };
                            $statusBadgeClass = match ($status) {
                                'reviewed', 'resolved' => 'badge-success',
                                'ignored' => 'badge-secondary',
                                'escalated' => 'badge-danger',
                                default => 'badge-info',
                            };
                            $isEscalated = $status === 'escalated';

                            $sourceType = cre8shieldPrettySource((string) ($row['source_type'] ?? ''));
                            $sourceLabel = (string) ($row['source_label'] ?? '');
                            $page = (string) ($row['page'] ?? '');
                            $mode = (string) ($row['mode'] ?? '');
                            $role = (string) ($row['role'] ?? '');
                            $finding = (string) ($row['finding_summary'] ?? '');
                            $recommendations = (string) ($row['safe_recommendations'] ?? '');
                            $sanitized = (string) ($row['sanitized_message'] ?? '');
                            $rawSnapshot = (string) ($row['raw_message_snapshot'] ?? '');
                            $aiDecision = (string) ($row['ai_decision'] ?? '');
                            $aiRationale = (string) ($row['ai_rationale'] ?? '');
                            $adminNotes = (string) ($row['admin_notes'] ?? '');
                            $created = cre8shieldFormatDateLabel($row['created_at'] ?? '');
                            $updated = cre8shieldFormatDateLabel($row['updated_at'] ?? '', '—');
                            $reviewed = cre8shieldFormatDateLabel($row['reviewed_at'] ?? '', '—');
                            $reviewedBy = isset($row['reviewed_by']) && (int) $row['reviewed_by'] > 0 ? '#' . (int) $row['reviewed_by'] : '—';
                            $reporterLabel = cre8shieldPrettyRoleId($row['reporter_role'] ?? '', $row['reporter_user_id'] ?? 0);
                            $reportedLabel = cre8shieldPrettyRoleId($row['reported_role'] ?? '', $row['reported_user_id'] ?? 0);
                            $reporterCellHtml = cre8shieldRenderUserCell($row['reporter_role'] ?? '', $row['reporter_user_id'] ?? 0, $cre8shieldUserMap, 'reporter');
                            $reportedCellHtml = cre8shieldRenderUserCell($row['reported_role'] ?? '', $row['reported_user_id'] ?? 0, $cre8shieldUserMap, 'reported');
                            $catchId = (int) ($row['id_catch'] ?? 0);
                            $detailsId = 'cre8shieldDetails' . $catchId;
                            $sourceKind = cre8shieldDetermineSourceKind($row['page'] ?? '', $row['source_type'] ?? '', $row['source_id'] ?? '');
                            $sourceTargetId = $sourceKind === 'prompt'
                                ? ('prompt-' . $catchId)
                                : ($sourceKind . '-' . (int) ($row['source_id'] ?? 0));
                            $hasNumericSourceId = isset($row['source_id']) && ctype_digit(trim((string) $row['source_id']));
                            $viewSourceLabel = match ($sourceKind) {
                                'offer' => 'View offer source',
                                'candidature' => 'View candidature source',
                                default => 'View prompt source',
                            };
                            ?>
                            <article class="card cre8shield-card risk-<?php echo htmlspecialchars($level); ?><?php echo $isEscalated ? ' is-escalated' : ''; ?>">
                                <?php if ($isEscalated): ?>
                                    <div class="cre8shield-escalated-banner" aria-label="Escalated catch">
                                        <span class="cre8shield-escalated-dot"></span>
                                        ESCALATED · priority review
                                    </div>
                                <?php endif; ?>

                                <div class="cre8shield-card-head">
                                    <div class="cre8shield-card-head-left">
                                        <span class="badge <?php echo htmlspecialchars($riskBadgeClass); ?> cre8shield-pill risk-<?php echo htmlspecialchars($level); ?>">
                                            <?php echo htmlspecialchars(ucfirst($level)); ?>
                                        </span>
                                        <span class="cre8shield-score">Score <strong><?php echo $score; ?></strong>/100</span>
                                    </div>
                                    <span class="badge <?php echo htmlspecialchars($statusBadgeClass); ?> cre8shield-pill status-<?php echo htmlspecialchars($status); ?>">
                                        <?php echo htmlspecialchars(cre8shieldStatusLabel($status)); ?>
                                    </span>
                                </div>

                                <dl class="cre8shield-card-meta-grid">
                                    <div>
                                        <dt>Created</dt>
                                        <dd><?php echo htmlspecialchars($created); ?></dd>
                                    </div>
                                    <div>
                                        <dt>Source</dt>
                                        <dd><?php echo htmlspecialchars($sourceType); ?></dd>
                                    </div>
                                    <?php if ($page !== ''): ?>
                                        <div>
                                            <dt>Page</dt>
                                            <dd><?php echo htmlspecialchars($page); ?></dd>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <dt>Reporter</dt>
                                        <dd><?php echo $reporterCellHtml; ?></dd>
                                    </div>
                                    <div>
                                        <dt>Reported</dt>
                                        <dd><?php echo $reportedCellHtml; ?></dd>
                                    </div>
                                </dl>

                                <?php if ($sourceLabel !== ''): ?>
                                    <p class="cre8shield-card-source-label" title="<?php echo htmlspecialchars($sourceLabel); ?>">
                                        <strong>Source label:</strong> <?php echo htmlspecialchars(cre8shieldExcerpt($sourceLabel, 160)); ?>
                                    </p>
                                <?php endif; ?>

                                <?php if (!empty($cats)): ?>
                                    <div class="cre8shield-card-categories">
                                        <?php foreach ($cats as $cat): ?>
                                            <span class="cre8shield-cat-chip"><?php echo htmlspecialchars(cre8shieldPrettyCategory($cat)); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($finding !== ''): ?>
                                    <div class="cre8shield-card-section">
                                        <h4>Finding</h4>
                                        <p><?php echo nl2br(htmlspecialchars(cre8shieldExcerpt($finding, 280))); ?></p>
                                    </div>
                                <?php elseif ($sanitized !== ''): ?>
                                    <div class="cre8shield-card-section">
                                        <h4>Sanitized excerpt</h4>
                                        <p><?php echo nl2br(htmlspecialchars(cre8shieldExcerpt($sanitized, 280))); ?></p>
                                    </div>
                                <?php endif; ?>

                                <?php if ($recommendations !== ''): ?>
                                    <div class="cre8shield-card-section is-recommend">
                                        <h4>Safe recommendation</h4>
                                        <p><?php echo nl2br(htmlspecialchars(cre8shieldExcerpt($recommendations, 220))); ?></p>
                                    </div>
                                <?php endif; ?>

                                <?php if ($adminNotes !== ''): ?>
                                    <div class="cre8shield-card-section is-admin-note">
                                        <h4>Admin note</h4>
                                        <p><?php echo nl2br(htmlspecialchars(cre8shieldExcerpt($adminNotes, 220))); ?></p>
                                    </div>
                                <?php endif; ?>

                                <details class="cre8shield-details" id="<?php echo htmlspecialchars($detailsId); ?>">
                                    <summary class="cre8shield-details-summary">View full details</summary>
                                    <div class="cre8shield-details-body">
                                        <?php if ($finding !== ''): ?>
                                            <section>
                                                <h5>Full finding</h5>
                                                <p><?php echo nl2br(htmlspecialchars($finding)); ?></p>
                                            </section>
                                        <?php endif; ?>

                                        <?php if ($recommendations !== ''): ?>
                                            <section>
                                                <h5>Safe recommendations</h5>
                                                <p><?php echo nl2br(htmlspecialchars($recommendations)); ?></p>
                                            </section>
                                        <?php endif; ?>

                                        <?php if ($sanitized !== ''): ?>
                                            <section>
                                                <h5>Sanitized message</h5>
                                                <pre class="cre8shield-pre"><?php echo htmlspecialchars($sanitized); ?></pre>
                                            </section>
                                        <?php endif; ?>

                                        <?php if ($rawSnapshot !== '' && $rawSnapshot !== $sanitized): ?>
                                            <section>
                                                <h5>Raw snapshot (admin only)</h5>
                                                <pre class="cre8shield-pre is-raw"><?php echo htmlspecialchars($rawSnapshot); ?></pre>
                                            </section>
                                        <?php endif; ?>

                                        <section class="cre8shield-details-grid">
                                            <div><strong>Source type:</strong> <?php echo htmlspecialchars($sourceType); ?></div>
                                            <?php if (!empty($row['source_id'])): ?>
                                                <div><strong>Source id:</strong> <?php echo htmlspecialchars((string) $row['source_id']); ?></div>
                                            <?php endif; ?>
                                            <?php if ($sourceLabel !== ''): ?>
                                                <div><strong>Source label:</strong> <?php echo htmlspecialchars($sourceLabel); ?></div>
                                            <?php endif; ?>
                                            <?php if ($page !== ''): ?><div><strong>Page:</strong> <?php echo htmlspecialchars($page); ?></div><?php endif; ?>
                                            <?php if ($mode !== ''): ?><div><strong>Mode:</strong> <?php echo htmlspecialchars($mode); ?></div><?php endif; ?>
                                            <?php if ($role !== ''): ?><div><strong>Role:</strong> <?php echo htmlspecialchars(ucfirst($role)); ?></div><?php endif; ?>
                                            <div><strong>Reporter:</strong> <?php echo $reporterCellHtml; ?></div>
                                            <div><strong>Reported:</strong> <?php echo $reportedCellHtml; ?></div>
                                            <?php if ($aiDecision !== ''): ?><div><strong>AI decision:</strong> <?php echo htmlspecialchars($aiDecision); ?></div><?php endif; ?>
                                            <div><strong>Created:</strong> <?php echo htmlspecialchars($created); ?></div>
                                            <div><strong>Updated:</strong> <?php echo htmlspecialchars($updated); ?></div>
                                            <div><strong>Reviewed at:</strong> <?php echo htmlspecialchars($reviewed); ?></div>
                                            <div><strong>Reviewed by:</strong> <?php echo htmlspecialchars($reviewedBy); ?></div>
                                        </section>

                                        <?php if ($aiRationale !== ''): ?>
                                            <section>
                                                <h5>AI rationale</h5>
                                                <p><?php echo nl2br(htmlspecialchars($aiRationale)); ?></p>
                                            </section>
                                        <?php endif; ?>
                                    </div>
                                </details>

                                <div class="cre8shield-card-actions">
                                    <button type="button"
                                            class="btn btn-info btn-sm cre8shield-action-btn is-source"
                                            data-cre8shield-source-trigger
                                            data-source-id="<?php echo htmlspecialchars($sourceTargetId); ?>"
                                            data-source-kind="<?php echo htmlspecialchars($sourceKind); ?>"
                                            data-catch-id="<?php echo $catchId; ?>"
                                            title="Open the original offer, candidature, or prompt that triggered this catch."
                                    >
                                        <?php echo htmlspecialchars($viewSourceLabel); ?>
                                    </button>
                                    <?php if ($status === 'open' || $status === 'escalated'): ?>
                                        <form method="post" action="index.php">
                                            <input type="hidden" name="cre8shieldAction" value="mark_reviewed">
                                            <input type="hidden" name="catchId" value="<?php echo $catchId; ?>">
                                            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
                                            <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
                                            <button type="submit" class="btn btn-primary btn-sm cre8shield-action-btn is-primary">Mark reviewed</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($status !== 'ignored'): ?>
                                        <form method="post" action="index.php">
                                            <input type="hidden" name="cre8shieldAction" value="ignore">
                                            <input type="hidden" name="catchId" value="<?php echo $catchId; ?>">
                                            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
                                            <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
                                            <button type="submit" class="btn btn-secondary btn-sm cre8shield-action-btn is-muted">Ignore</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($status !== 'escalated'): ?>
                                        <form method="post" action="index.php">
                                            <input type="hidden" name="cre8shieldAction" value="escalate">
                                            <input type="hidden" name="catchId" value="<?php echo $catchId; ?>">
                                            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
                                            <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
                                            <button type="submit" class="btn btn-warning btn-sm cre8shield-action-btn is-warning">Escalate</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($status !== 'resolved'): ?>
                                        <form method="post" action="index.php">
                                            <input type="hidden" name="cre8shieldAction" value="resolve">
                                            <input type="hidden" name="catchId" value="<?php echo $catchId; ?>">
                                            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
                                            <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
                                            <button type="submit" class="btn btn-success btn-sm cre8shield-action-btn is-success">Resolve</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="cre8shield-templates" hidden>
                    <?php foreach ($cre8shieldUserMap as $tplUserId => $tplUser): ?>
                        <template id="cre8shieldUserTemplate-<?php echo (int) $tplUserId; ?>"><?php echo cre8shieldRenderUserCardTemplate((int) $tplUserId, $tplUser); ?></template>
                    <?php endforeach; ?>
                    <?php foreach ($cre8shieldOfferSnapshots as $tplOfferId => $tplOffer): ?>
                        <template id="cre8shieldSourceTemplate-offer-<?php echo (int) $tplOfferId; ?>"><?php echo cre8shieldRenderOfferSourceTemplate((int) $tplOfferId, $tplOffer, $cre8shieldUserMap); ?></template>
                    <?php endforeach; ?>
                    <?php foreach ($cre8shieldCandidatureSnapshots as $tplCandId => $tplCand): ?>
                        <template id="cre8shieldSourceTemplate-candidature-<?php echo (int) $tplCandId; ?>"><?php echo cre8shieldRenderCandidatureSourceTemplate((int) $tplCandId, $tplCand); ?></template>
                    <?php endforeach; ?>
                    <?php foreach ($rows as $tplRow): ?>
                        <?php
                        $tplCatchId = (int) ($tplRow['id_catch'] ?? 0);
                        if ($tplCatchId <= 0) {
                            continue;
                        }
                        $tplKind = cre8shieldDetermineSourceKind($tplRow['page'] ?? '', $tplRow['source_type'] ?? '', $tplRow['source_id'] ?? '');
                        if ($tplKind !== 'prompt') {
                            continue;
                        }
                        ?>
                        <template id="cre8shieldSourceTemplate-prompt-<?php echo $tplCatchId; ?>"><?php echo cre8shieldRenderPromptSourceTemplate($tplRow); ?></template>
                    <?php endforeach; ?>
                </div>

                <div class="cre8shield-modal-overlay" data-cre8shield-modal hidden aria-hidden="true">
                    <div class="cre8shield-modal-card" role="dialog" aria-modal="true" aria-labelledby="cre8shieldModalTitle">
                        <div class="cre8shield-modal-head">
                            <div>
                                <span class="cre8shield-modal-eyebrow" data-cre8shield-modal-eyebrow>Details</span>
                                <h3 id="cre8shieldModalTitle" data-cre8shield-modal-title>Cre8Shield context</h3>
                            </div>
                            <button type="button" class="cre8shield-modal-close" data-cre8shield-modal-close aria-label="Close">Close</button>
                        </div>
                        <div class="cre8shield-modal-body" data-cre8shield-modal-body>
                            <p class="cre8shield-modal-empty">Loading…</p>
                        </div>
                    </div>
                </div>
            </div>
            </div>
        </main>
    </div>
    <script>
        (function () {
            const modal = document.querySelector('[data-cre8shield-modal]');
            if (!modal) {
                return;
            }
            const body = modal.querySelector('[data-cre8shield-modal-body]');
            const titleNode = modal.querySelector('[data-cre8shield-modal-title]');
            const eyebrow = modal.querySelector('[data-cre8shield-modal-eyebrow]');
            const closeButtons = modal.querySelectorAll('[data-cre8shield-modal-close]');

            function openModalWithTemplate(templateId, title, eyebrowLabel) {
                if (!body) {
                    return;
                }
                const tpl = document.getElementById(templateId);
                body.innerHTML = '';
                if (tpl && tpl.content) {
                    body.appendChild(tpl.content.cloneNode(true));
                } else if (tpl) {
                    body.innerHTML = tpl.innerHTML;
                } else {
                    body.innerHTML = '<p class="cre8shield-modal-empty">No data is available for this entry.</p>';
                }
                if (titleNode && title) {
                    titleNode.textContent = title;
                }
                if (eyebrow && eyebrowLabel) {
                    eyebrow.textContent = eyebrowLabel;
                }
                modal.hidden = false;
                modal.setAttribute('aria-hidden', 'false');
                document.documentElement.classList.add('cre8shield-modal-open');
                document.body.classList.add('cre8shield-modal-open');
                const focusTarget = modal.querySelector('[data-cre8shield-modal-close]');
                if (focusTarget) {
                    window.setTimeout(function () { focusTarget.focus({ preventScroll: true }); }, 30);
                }
            }

            function closeModal() {
                modal.hidden = true;
                modal.setAttribute('aria-hidden', 'true');
                document.documentElement.classList.remove('cre8shield-modal-open');
                document.body.classList.remove('cre8shield-modal-open');
            }

            closeButtons.forEach(function (btn) {
                btn.addEventListener('click', closeModal);
            });
            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeModal();
                }
            });
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && !modal.hidden) {
                    closeModal();
                }
            });

            document.addEventListener('click', function (event) {
                const userTrigger = event.target.closest('[data-cre8shield-user-trigger]');
                if (userTrigger) {
                    event.preventDefault();
                    const uid = userTrigger.dataset.userId || '';
                    if (!uid) {
                        return;
                    }
                    openModalWithTemplate(
                        'cre8shieldUserTemplate-' + uid,
                        'User #' + uid,
                        'User profile'
                    );
                    return;
                }

                const sourceTrigger = event.target.closest('[data-cre8shield-source-trigger]');
                if (sourceTrigger) {
                    event.preventDefault();
                    const sid = sourceTrigger.dataset.sourceId || '';
                    const kind = sourceTrigger.dataset.sourceKind || '';
                    if (!sid) {
                        return;
                    }
                    const titleByKind = {
                        offer: 'Offer source',
                        candidature: 'Candidature source',
                        prompt: 'Prompt source',
                    };
                    openModalWithTemplate(
                        'cre8shieldSourceTemplate-' + sid,
                        titleByKind[kind] || 'Catch source',
                        'Source snapshot'
                    );
                }
            });
        })();
    </script>
</body>
</html>
