<?php
session_start();

require_once __DIR__ . '/../layout/early-theme.php';
require_once __DIR__ . '/../../../Controleur/session_helper.php';
require_once __DIR__ . '/../../../Controleur/cre8shieldC.php';
require_once __DIR__ . '/../../../Controleur/condidatureC.php';
require_once __DIR__ . '/../../../Controleur/utilisateurC.php';
require_once __DIR__ . '/../../../Controleur/adminAuditC.php';
require_once __DIR__ . '/../../FrontOffice/layout/avatar_helper.php';

$candidatureController = new CondidatureC();
$cre8shieldController = new Cre8ShieldC();
$userController = new UtilisateurC();
$sessionUser = $_SESSION['utilisateur'] ?? ($_SESSION['user'] ?? []);

if (!isset($sessionUser['id']) || !isBackOfficeRole(cc_current_user_role())) {
    header('Location: ../../FrontOffice/utilisateur/login.php');
    exit;
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

    if ($action === 'suspend_reported_user') {
        $targetUserId = (int) ($_POST['reportedUserId'] ?? 0);
        $targetUser = $targetUserId > 0 ? $userController->getUserById($targetUserId) : null;

        if (!$targetUser) {
            $notice = 'Reported user was not found.';
            $noticeType = 'danger';
        } elseif (!cc_can_manage_user($adminId, cc_current_user_role(), $targetUser, 'suspend')) {
            $notice = 'You are not allowed to suspend this reported user.';
            $noticeType = 'danger';
        } elseif (strtolower(trim((string) ($targetUser['statut'] ?? ''))) !== 'actif') {
            $notice = 'Only active reported users can be suspended.';
            $noticeType = 'danger';
        } else {
            $reason = 'Suspended from Cre8Shield catch #' . ($catchId > 0 ? $catchId : 'unknown');
            $userController->suspendUserWithMetadata($targetUserId, $adminId, cc_current_user_role(), $reason);
            cc_log_admin_action(
                $adminId,
                cc_current_user_role(),
                'suspend_user',
                $targetUserId,
                $targetUser['role'] ?? null,
                $targetUser['statut'] ?? null,
                'suspendu',
                $reason
            );
            $notice = 'Reported user suspended successfully.';
            $noticeType = 'success';
        }
    } elseif ($catchId > 0 && $cre8shieldController->isAvailable()) {
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

$tabTitleKey = match ($tab) {
    'high' => 'cre8shield.tabs.high',
    'medium' => 'cre8shield.tabs.medium',
    'escalated' => 'cre8shield.tabs.escalated',
    'reviewed' => 'cre8shield.tabs.reviewed',
    default => 'cre8shield.tabs.current',
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


function cre8shieldI18nAttr(?string $key): string
{
    $key = trim((string) $key);
    return $key !== '' ? ' data-i18n="' . htmlspecialchars($key, ENT_QUOTES) . '"' : '';
}

function cre8shieldStatusI18nKey($status): ?string
{
    $s = strtolower(trim((string) $status));
    return match ($s) {
        'open' => 'cre8shield.status.open',
        'reviewed' => 'cre8shield.status.reviewed',
        'ignored' => 'cre8shield.status.ignored',
        'escalated' => 'cre8shield.status.escalated',
        'resolved' => 'cre8shield.status.resolved',
        default => null,
    };
}

function cre8shieldRiskI18nKey($level): ?string
{
    $level = strtolower(trim((string) $level));
    return match ($level) {
        'high' => 'cre8shield.risk.high',
        'medium' => 'cre8shield.risk.medium',
        'low' => 'cre8shield.risk.low',
        'info' => 'cre8shield.risk.info',
        default => null,
    };
}

function cre8shieldSortI18nKey($sortKey): ?string
{
    return match ((string) $sortKey) {
        'time_desc' => 'cre8shield.sort.timeDesc',
        'time_asc' => 'cre8shield.sort.timeAsc',
        'score_desc' => 'cre8shield.sort.scoreDesc',
        'score_asc' => 'cre8shield.sort.scoreAsc',
        'updated_desc' => 'cre8shield.sort.updatedDesc',
        'updated_asc' => 'cre8shield.sort.updatedAsc',
        'status_priority' => 'cre8shield.sort.statusPriority',
        default => null,
    };
}

function cre8shieldCategoryI18nKey($slug): ?string
{
    $key = strtolower(trim((string) $slug));
    return in_array($key, [
        'off_platform_payment', 'platform_bypass', 'suspicious_link', 'phishing',
        'credential_theft', 'suspicious_invoice', 'payment_risk', 'social_engineering',
        'pii_leak', 'malware', 'spam', 'harassment', 'impersonation',
    ], true) ? 'cre8shield.category.' . $key : null;
}

function cre8shieldSourceI18nKey($value): ?string
{
    $key = strtolower(trim((string) $value));
    return match ($key) {
        'chat_message' => 'cre8shield.source.chatMessage',
        'chat_link' => 'cre8shield.source.chatLink',
        'page_scan' => 'cre8shield.source.pageScan',
        'page_visit' => 'cre8shield.source.pageVisit',
        'manual_report' => 'cre8shield.source.manualReport',
        'background_scan' => 'cre8shield.source.backgroundScan',
        default => null,
    };
}

function cre8shieldOfferStatusI18nKey($status): ?string
{
    return match ((string) $status) {
        'brouillon' => 'cre8shield.offerStatus.draft',
        'publiee' => 'cre8shield.offerStatus.published',
        'archivee' => 'cre8shield.offerStatus.archived',
        'annulee' => 'cre8shield.offerStatus.cancelled',
        default => null,
    };
}

function cre8shieldCandidatureStatusI18nKey($status): ?string
{
    return match ((string) $status) {
        'brouillon' => 'cre8shield.candidatureStatus.draft',
        'envoyee' => 'cre8shield.candidatureStatus.sent',
        'en_etude' => 'cre8shield.candidatureStatus.review',
        'negociation' => 'cre8shield.candidatureStatus.negotiation',
        'acceptee' => 'cre8shield.candidatureStatus.accepted',
        'refusee' => 'cre8shield.candidatureStatus.refused',
        'retiree' => 'cre8shield.candidatureStatus.withdrawn',
        default => null,
    };
}

function cre8shieldRoleI18nKey($role): ?string
{
    return match (strtolower(trim((string) $role))) {
        'marque' => 'cre8shield.role.brand',
        'createur' => 'cre8shield.role.creator',
        'admin' => 'cre8shield.role.admin',
        'super_admin' => 'cre8shield.role.superAdmin',
        'hyper_admin' => 'cre8shield.role.hyperAdmin',
        default => null,
    };
}

function cre8shieldNoticeI18nKey($notice): ?string
{
    return match (trim((string) $notice)) {
        'Catch marked as reviewed.' => 'cre8shield.notice.reviewed',
        'Could not mark this catch as reviewed.' => 'cre8shield.notice.reviewedFail',
        'Catch ignored.' => 'cre8shield.notice.ignored',
        'Could not ignore this catch.' => 'cre8shield.notice.ignoredFail',
        'Catch escalated for priority review.' => 'cre8shield.notice.escalated',
        'Could not escalate this catch.' => 'cre8shield.notice.escalatedFail',
        'Catch resolved.' => 'cre8shield.notice.resolved',
        'Could not resolve this catch.' => 'cre8shield.notice.resolvedFail',
        'Reported user was not found.' => 'cre8shield.notice.reportedUserMissing',
        'You are not allowed to suspend this reported user.' => 'cre8shield.notice.suspendDenied',
        'Only active reported users can be suspended.' => 'cre8shield.notice.suspendActiveOnly',
        'Reported user suspended successfully.' => 'cre8shield.notice.reportedSuspended',
        'Unknown action.' => 'cre8shield.notice.unknownAction',
        'Cre8Shield monitor table is not available.' => 'cre8shield.notice.tableUnavailable',
        default => null,
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
function cre8shieldRenderUserCell($role, $userId, array $userMap, $variant = 'reporter', $catchId = 0)
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

    $avatarHtml = cre8_render_avatar($userId, $primary, 'cre8-avatar-sm cre8shield-user-avatar');

    $catchId = (int) $catchId;
    return '<button type="button" class="cre8shield-user-link variant-' . htmlspecialchars($variant) . '" data-cre8shield-user-trigger data-user-id="' . $userId . '" data-user-context="' . htmlspecialchars($variant) . '" data-catch-id="' . $catchId . '" title="Open user details">'
        . $avatarHtml
        . '<span class="cre8shield-user-link-copy">'
        . '<strong>' . htmlspecialchars($primary) . '</strong>'
        . '<span class="cre8shield-user-link-meta">' . htmlspecialchars(($effectiveRole !== '' ? ucfirst($effectiveRole) : 'User') . ' · #' . $userId) . '</span>'
        . '</span>'
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

function cre8shieldUserWorkspaceUrl(string $role, int $userId): string
{
    $roleLower = cc_normalize_role($role);
    $userId = max(0, $userId);

    if ($roleLower === 'createur') {
        return '../condidature/index.php?' . http_build_query([
            'creatorId' => $userId,
            'from' => 'cre8shield',
        ]);
    }

    if ($roleLower === 'marque') {
        return '../offre/index.php?' . http_build_query([
            'brandId' => $userId,
            'from' => 'cre8shield',
        ]);
    }

    if (in_array($roleLower, ['admin', 'super_admin', 'hyper_admin'], true)) {
        return '../utilisateur/admin_management.php?' . http_build_query(['from' => 'cre8shield']);
    }

    return '../utilisateur/index.php?' . http_build_query([
        'search' => (string) $userId,
        'from' => 'cre8shield',
    ]);
}

function cre8shieldRenderUserCardTemplate(int $userId, array $user, int $currentUserId, string $currentRole, string $tab, string $sort)
{
    $name = trim((string) ($user['nom'] ?? '')) !== '' ? (string) $user['nom'] : ('User #' . $userId);
    $email = (string) ($user['email'] ?? '');
    $role = (string) ($user['role'] ?? '');
    $statut = (string) ($user['statut'] ?? '');
    $createdAt = (string) ($user['date_creation'] ?? '');
    $createdLabel = $createdAt !== '' ? cre8shieldFormatDateLabel($createdAt, '—') : '—';
    $roleLower = strtolower($role);
    $workspaceUrl = cre8shieldUserWorkspaceUrl($role, $userId);
    $workspaceLabel = $roleLower === 'createur'
        ? 'Open creator workspace'
        : ($roleLower === 'marque' ? 'Open brand workspace' : 'Open admin workspace');
    $policyTarget = $user;
    $policyTarget['id'] = $userId;
    $canSuspendReported = strtolower(trim($statut)) === 'actif'
        && function_exists('cc_can_manage_user')
        && cc_can_manage_user($currentUserId, $currentRole, $policyTarget, 'suspend');

    ob_start();
    ?>
    <article class="cre8shield-modal-user-card">
        <header class="cre8shield-modal-user-head">
            <div class="cre8shield-modal-user-identity">
                <?php echo cre8_render_avatar($userId, $name, 'cre8-avatar-lg cre8shield-modal-user-avatar'); ?>
                <div class="cre8shield-modal-user-copy">
                    <span class="cre8shield-role-pill <?php echo htmlspecialchars(cre8shieldRoleColorClass($role)); ?>"<?php echo cre8shieldI18nAttr(cre8shieldRoleI18nKey($role) ?? 'cre8shield.role.user'); ?>>
                        <?php echo htmlspecialchars($role !== '' ? ucfirst($role) : 'User'); ?>
                    </span>
                    <h3 class="cre8shield-modal-user-name"><?php echo htmlspecialchars($name); ?></h3>
                    <?php if ($email !== ''): ?>
                        <p class="cre8shield-modal-user-email">
                            <a href="mailto:<?php echo htmlspecialchars($email); ?>"><?php echo htmlspecialchars($email); ?></a>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            <span class="cre8shield-modal-user-id">ID #<?php echo $userId; ?></span>
        </header>
        <dl class="cre8shield-modal-grid">
            <?php if ($statut !== ''): ?>
                <div>
                    <dt data-i18n="common.status">Status</dt>
                    <dd><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $statut))); ?></dd>
                </div>
            <?php endif; ?>
            <div>
                <dt data-i18n="cre8shield.modal.joined">Joined</dt>
                <dd><?php echo htmlspecialchars($createdLabel); ?></dd>
            </div>
            <div>
                <dt data-i18n="common.role">Role</dt>
                <dd><?php echo htmlspecialchars($role !== '' ? ucfirst($role) : 'User'); ?></dd>
            </div>
        </dl>
        <div class="cre8shield-modal-user-actions">
            <a class="cre8shield-action-btn" href="<?php echo htmlspecialchars($workspaceUrl); ?>"><?php echo htmlspecialchars($workspaceLabel); ?></a>
            <?php if ($canSuspendReported): ?>
                <form method="post" action="index.php" class="cre8shield-reported-suspend-form" data-cre8shield-reported-only hidden>
                    <input type="hidden" name="cre8shieldAction" value="suspend_reported_user">
                    <input type="hidden" name="reportedUserId" value="<?php echo $userId; ?>">
                    <input type="hidden" name="catchId" value="" data-cre8shield-reported-catch-input>
                    <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
                    <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
                    <button type="submit" class="cre8shield-action-btn is-warning" data-i18n="cre8shield.actions.suspendReported">Suspend reported user</button>
                </form>
            <?php endif; ?>
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
            <span class="cre8shield-source-kind-pill kind-offer" data-i18n="cre8shield.sourceKind.offer">Offer</span>
            <span class="cre8shield-modal-source-id">ID #<?php echo $offerId; ?></span>
            <?php if ($status !== ''): ?>
                <span class="cre8shield-source-status status-<?php echo htmlspecialchars($status); ?>"<?php echo cre8shieldI18nAttr(cre8shieldOfferStatusI18nKey($status) ?? cre8shieldCandidatureStatusI18nKey($status)); ?>><?php echo htmlspecialchars($statusLabel); ?></span>
            <?php endif; ?>
        </header>
        <h3 class="cre8shield-modal-source-title"><?php echo htmlspecialchars($title); ?></h3>
        <?php if ($objective !== ''): ?>
            <p class="cre8shield-modal-source-line"><strong data-i18n="cre8shield.modal.objective">Objective:</strong> <?php echo htmlspecialchars($objective); ?></p>
        <?php endif; ?>
        <?php if ($description !== ''): ?>
            <div class="cre8shield-modal-source-block">
                <h5 data-i18n="cre8shield.modal.description">Description</h5>
                <p><?php echo nl2br(htmlspecialchars(cre8shieldExcerpt($description, 480))); ?></p>
            </div>
        <?php endif; ?>
        <dl class="cre8shield-modal-grid">
            <?php if ($budget !== null && $budget !== ''): ?>
                <div>
                    <dt data-i18n="cre8shield.modal.budget">Budget</dt>
                    <dd>EUR <?php echo htmlspecialchars(number_format((float) $budget, 2, '.', ',')); ?></dd>
                </div>
            <?php endif; ?>
            <?php if ($publication !== ''): ?>
                <div>
                    <dt data-i18n="cre8shield.modal.published">Published</dt>
                    <dd><?php echo htmlspecialchars($publication); ?></dd>
                </div>
            <?php endif; ?>
            <?php if ($deadline !== ''): ?>
                <div>
                    <dt data-i18n="cre8shield.modal.deadline">Deadline</dt>
                    <dd><?php echo htmlspecialchars($deadline); ?></dd>
                </div>
            <?php endif; ?>
            <div>
                <dt data-i18n="cre8shield.modal.brand">Brand</dt>
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
                <dt data-i18n="cre8shield.modal.targetedCreator">Targeted creator</dt>
                <dd>
                    <?php if ($creatorId > 0): ?>
                        <button type="button" class="cre8shield-mini-user-btn" data-cre8shield-user-trigger data-user-id="<?php echo $creatorId; ?>">
                            <?php echo htmlspecialchars($creatorName !== '' ? $creatorName : ('Creator #' . $creatorId)); ?>
                            <span>· #<?php echo $creatorId; ?></span>
                        </button>
                    <?php else: ?>
                        <span data-i18n="cre8shield.modal.noCreatorSelected">No creator selected</span>
                    <?php endif; ?>
                </dd>
            </div>
        </dl>
        <div class="cre8shield-modal-source-actions">
            <a class="cre8shield-action-btn is-primary" href="<?php echo htmlspecialchars($inspectUrl); ?>"><span data-i18n="cre8shield.modal.inspectOfferWorkspace">Inspect in admin offer workspace</span></a>
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
            <span class="cre8shield-source-kind-pill kind-candidature" data-i18n="cre8shield.sourceKind.candidature">Candidature</span>
            <span class="cre8shield-modal-source-id">ID #<?php echo $candidatureId; ?></span>
            <?php if ($status !== ''): ?>
                <span class="cre8shield-source-status status-<?php echo htmlspecialchars($status); ?>"<?php echo cre8shieldI18nAttr(cre8shieldOfferStatusI18nKey($status) ?? cre8shieldCandidatureStatusI18nKey($status)); ?>><?php echo htmlspecialchars($statusLabel); ?></span>
            <?php endif; ?>
        </header>
        <h3 class="cre8shield-modal-source-title">
            <?php echo htmlspecialchars($offerTitle !== '' ? $offerTitle : ('Candidature #' . $candidatureId)); ?>
        </h3>
        <?php if ($origin !== ''): ?>
            <p class="cre8shield-modal-source-line">
                <strong data-i18n="cre8shield.modal.origin">Origin:</strong>
                <span<?php echo $origin === 'par_offre' ? ' data-i18n="cre8shield.modal.offerInvitation"' : ''; ?>><?php echo htmlspecialchars($origin === 'par_offre' ? 'Offer invitation' : ucwords(str_replace('_', ' ', $origin))); ?></span>
            </p>
        <?php endif; ?>
        <?php if ($message !== ''): ?>
            <div class="cre8shield-modal-source-block">
                <h5 data-i18n="cre8shield.modal.creatorMessage">Creator message</h5>
                <p><?php echo nl2br(htmlspecialchars(cre8shieldExcerpt($message, 480))); ?></p>
            </div>
        <?php endif; ?>
        <dl class="cre8shield-modal-grid">
            <?php if ($budget !== null && $budget !== ''): ?>
                <div>
                    <dt data-i18n="cre8shield.modal.proposedBudget">Proposed budget</dt>
                    <dd>EUR <?php echo htmlspecialchars(number_format((float) $budget, 2, '.', ',')); ?></dd>
                </div>
            <?php endif; ?>
            <?php if ($delai !== null && $delai !== ''): ?>
                <div>
                    <dt data-i18n="cre8shield.modal.proposedTimeline">Proposed timeline</dt>
                    <dd><?php echo (int) $delai; ?> <span data-i18n="cre8shield.modal.days">days</span></dd>
                </div>
            <?php endif; ?>
            <?php if ($submitted !== ''): ?>
                <div>
                    <dt data-i18n="cre8shield.modal.submitted">Submitted</dt>
                    <dd><?php echo htmlspecialchars(cre8shieldFormatDateLabel($submitted, '—')); ?></dd>
                </div>
            <?php endif; ?>
            <?php if ($updated !== ''): ?>
                <div>
                    <dt data-i18n="cre8shield.modal.lastUpdate">Last update</dt>
                    <dd><?php echo htmlspecialchars(cre8shieldFormatDateLabel($updated, '—')); ?></dd>
                </div>
            <?php endif; ?>
            <div>
                <dt data-i18n="cre8shield.modal.creator">Creator</dt>
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
                <dt data-i18n="cre8shield.modal.brand">Brand</dt>
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
            <a class="cre8shield-action-btn is-primary" href="<?php echo htmlspecialchars($inspectUrl); ?>"><span data-i18n="cre8shield.modal.openCandidature">Open candidature</span></a>
            <?php if ($offerId > 0): ?>
                <a class="cre8shield-action-btn" href="../offre/index.php?idOffre=<?php echo $offerId; ?>"><span data-i18n="cre8shield.modal.openLinkedOffer">Open linked offer</span></a>
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
            <span class="cre8shield-source-kind-pill kind-prompt" data-i18n="cre8shield.sourceKind.prompt">Prompt</span>
            <span class="cre8shield-modal-source-id"<?php echo cre8shieldI18nAttr(cre8shieldSourceI18nKey($row['source_type'] ?? '')); ?>><?php echo htmlspecialchars($sourceType); ?></span>
        </header>
        <?php if ($sourceLabel !== ''): ?>
            <p class="cre8shield-modal-source-line"><strong data-i18n="cre8shield.modal.sourceLabel">Source label:</strong> <?php echo htmlspecialchars($sourceLabel); ?></p>
        <?php endif; ?>
        <?php if ($page !== ''): ?>
            <p class="cre8shield-modal-source-line"><strong data-i18n="cre8shield.modal.page">Page:</strong> <?php echo htmlspecialchars($page . ($mode !== '' ? ' · ' . $mode : '')); ?></p>
        <?php endif; ?>
        <?php if ($sanitized !== ''): ?>
            <div class="cre8shield-modal-source-block">
                <h5 data-i18n="cre8shield.modal.sanitizedMessage">Sanitized message</h5>
                <pre class="cre8shield-pre"><?php echo htmlspecialchars($sanitized); ?></pre>
            </div>
        <?php endif; ?>
        <?php if ($rawSnapshot !== '' && $rawSnapshot !== $sanitized): ?>
            <div class="cre8shield-modal-source-block">
                <h5 data-i18n="cre8shield.modal.rawSnapshot">Raw snapshot (admin only)</h5>
                <pre class="cre8shield-pre is-raw"><?php echo htmlspecialchars($rawSnapshot); ?></pre>
            </div>
        <?php endif; ?>
        <?php if ($aiDecision !== ''): ?>
            <p class="cre8shield-modal-source-line"><strong data-i18n="cre8shield.modal.aiDecision">AI decision:</strong> <?php echo htmlspecialchars($aiDecision); ?></p>
        <?php endif; ?>
        <?php if ($aiRationale !== ''): ?>
            <div class="cre8shield-modal-source-block">
                <h5 data-i18n="cre8shield.modal.aiRationale">AI rationale</h5>
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

$backActive = 'collaborations';

if (!function_exists('renderBackOfficeCollaborationTabs')) {
    function renderBackOfficeCollaborationTabs(string $activeTab): void
    {
        $tabs = [
            'offers' => [
                'label' => 'Offers',
                'hint' => 'Targeted invitations',
                'labelKey' => 'collaboration.tab.offers',
                'hintKey' => 'collaboration.tab.offersHint',
                'href' => '../offre/index.php',
                'icon' => 'mdi-briefcase-check',
            ],
            'candidatures' => [
                'label' => 'Candidatures',
                'hint' => 'Creator responses',
                'labelKey' => 'collaboration.tab.candidatures',
                'hintKey' => 'collaboration.tab.candidaturesHint',
                'href' => '../condidature/index.php',
                'icon' => 'mdi-account-check',
            ],
            'cre8shield' => [
                'label' => 'Cre8Shield',
                'hint' => 'Risk monitoring',
                'labelKey' => 'collaboration.tab.cre8shield',
                'hintKey' => 'collaboration.tab.cre8shieldHint',
                'href' => '../cre8shield/index.php',
                'icon' => 'mdi-shield-check',
            ],
        ];
        ?>
        <nav class="collaboration-subnav" aria-label="Collaboration workspace tabs">
            <ul class="collaboration-subnav__list">
                <?php foreach ($tabs as $key => $tab): ?>
                    <?php $isActive = $activeTab === $key; ?>
                    <li>
                        <a class="collaboration-subnav__link<?php echo $isActive ? ' is-active' : ''; ?>" href="<?php echo htmlspecialchars($tab['href']); ?>"<?php echo $isActive ? ' aria-current="page"' : ''; ?>>
                            <span class="collaboration-subnav__icon" aria-hidden="true">
                                <i class="mdi <?php echo htmlspecialchars($tab['icon']); ?>"></i>
                            </span>
                            <span class="collaboration-subnav__text">
                                <span class="collaboration-subnav__title" data-i18n="<?php echo htmlspecialchars($tab['labelKey']); ?>"><?php echo htmlspecialchars($tab['label']); ?></span>
                                <span class="collaboration-subnav__hint" data-i18n="<?php echo htmlspecialchars($tab['hintKey']); ?>"><?php echo htmlspecialchars($tab['hint']); ?></span>
                            </span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
        <?php
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php cre8_bo_early_theme_print_head_script(); ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Back Office - Cre8Shield Monitor</title>
    <link rel="stylesheet" href="../css/backoffice.css">
    <link rel="stylesheet" href="../layout/back-layout.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../layout/back-layout.css')); ?>">
    <link rel="stylesheet" href="../utilisateur/assets/vendors/mdi/css/materialdesignicons.min.css?v=<?php echo urlencode((string) (@filemtime(__DIR__ . '/../utilisateur/assets/vendors/mdi/css/materialdesignicons.min.css') ?: 0)); ?>">
    <link rel="stylesheet" href="../css/new_style_backoffice.css">
    <link rel="stylesheet" href="../offre/offre-admin.css?v=<?php echo urlencode((string) (@filemtime(__DIR__ . '/../offre/offre-admin.css') ?: 0)); ?>">
    <link rel="stylesheet" href="../condidature/condidature-admin.css?v=<?php echo urlencode((string) (@filemtime(__DIR__ . '/../condidature/condidature-admin.css') ?: 0)); ?>">
    <link rel="stylesheet" href="cre8shield-admin.css?v=<?php echo urlencode((string) (@filemtime(__DIR__ . '/cre8shield-admin.css') ?: 0)); ?>">

    <style>
        .collaboration-subnav {
            margin: 0 0 1.5rem;
            padding: 0.75rem;
            border: 1px solid rgba(148, 163, 184, 0.16);
            border-radius: 18px;
            background: rgba(17, 24, 39, 0.94);
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.18);
        }

        .collaboration-subnav__list {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.75rem;
            padding: 0;
            margin: 0;
            list-style: none;
        }

        .collaboration-subnav__link {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            min-height: 74px;
            padding: 0.9rem 1rem;
            border: 1px solid rgba(148, 163, 184, 0.14);
            border-radius: 15px;
            background: rgba(255, 255, 255, 0.04);
            color: #cbd5e1;
            text-decoration: none;
            transition: transform 0.18s ease, border-color 0.18s ease, background 0.18s ease, color 0.18s ease;
        }

        .collaboration-subnav__link:hover,
        .collaboration-subnav__link:focus {
            transform: translateY(-1px);
            border-color: rgba(151, 92, 232, 0.45);
            background: rgba(151, 92, 232, 0.12);
            color: #ffffff;
            text-decoration: none;
            outline: none;
        }

        .collaboration-subnav__link.is-active {
            border-color: rgba(255, 255, 255, 0.22);
            background: linear-gradient(135deg, rgba(151, 92, 232, 0.96), rgba(210, 24, 118, 0.9));
            color: #ffffff;
            box-shadow: 0 14px 30px rgba(151, 92, 232, 0.24);
        }

        .collaboration-subnav__icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 42px;
            width: 42px;
            height: 42px;
            border-radius: 14px;
            background: rgba(15, 23, 42, 0.28);
            color: inherit;
            font-size: 1.35rem;
        }

        .collaboration-subnav__text {
            display: flex;
            flex-direction: column;
            gap: 0.15rem;
            line-height: 1.2;
        }

        .collaboration-subnav__title {
            font-weight: 700;
            letter-spacing: 0.01em;
        }

        .collaboration-subnav__hint {
            color: rgba(226, 232, 240, 0.72);
            font-size: 0.78rem;
            font-weight: 500;
        }

        .collaboration-subnav__link.is-active .collaboration-subnav__hint {
            color: rgba(255, 255, 255, 0.82);
        }

        body.light-mode .collaboration-subnav {
            background: #ffffff;
            border-color: rgba(15, 23, 42, 0.08);
            box-shadow: 0 16px 36px rgba(15, 23, 42, 0.08);
        }

        body.light-mode .collaboration-subnav__link {
            background: #f8fafc;
            border-color: rgba(15, 23, 42, 0.08);
            color: #334155;
        }

        body.light-mode .collaboration-subnav__link:hover,
        body.light-mode .collaboration-subnav__link:focus {
            color: #111827;
            background: #f1f5f9;
        }

        body.light-mode .collaboration-subnav__link.is-active {
            color: #ffffff;
            background: linear-gradient(135deg, #7c3aed, #d21876);
        }

        body.light-mode .collaboration-subnav__hint {
            color: #64748b;
        }


        .cre8shield-user-link {
            display: inline-flex;
            align-items: center;
            gap: 0.7rem;
            text-align: left;
        }

        .cre8shield-user-link-copy {
            display: inline-flex;
            flex-direction: column;
            gap: 0.1rem;
            min-width: 0;
        }

        .cre8shield-user-avatar,
        .cre8shield-modal-user-avatar,
        .cre8shield-user-avatar img,
        .cre8shield-modal-user-avatar img {
            border-radius: 999px !important;
            object-fit: cover !important;
            overflow: hidden !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        .cre8shield-user-avatar {
            width: 34px !important;
            height: 34px !important;
            min-width: 34px !important;
            flex: 0 0 34px !important;
            font-size: 0.84rem !important;
            font-weight: 800 !important;
            color: #fff !important;
            background: linear-gradient(135deg, #8b5cf6, #ec4899) !important;
            box-shadow: 0 8px 18px rgba(139, 92, 246, 0.22);
            border: 2px solid rgba(255, 255, 255, 0.76);
        }

        .cre8shield-modal-user-identity {
            display: flex;
            align-items: center;
            gap: 1rem;
            min-width: 0;
        }

        .cre8shield-modal-user-avatar {
            width: 64px !important;
            height: 64px !important;
            min-width: 64px !important;
            flex: 0 0 64px !important;
            font-size: 1.35rem !important;
            font-weight: 900 !important;
            color: #fff !important;
            background: linear-gradient(135deg, #8b5cf6, #ec4899) !important;
            box-shadow: 0 14px 28px rgba(139, 92, 246, 0.24);
            border: 3px solid rgba(255, 255, 255, 0.82);
        }

        .cre8shield-modal-user-head {
            align-items: center;
            gap: 1rem;
        }

        @media (max-width: 900px) {
            .collaboration-subnav__list {
                grid-template-columns: 1fr;
            }
        }

        /* Cre8Shield user modal final clean alignment */
        .cre8shield-modal-user-card {
            padding: 1.05rem 1.1rem 1.15rem !important;
            gap: 0.85rem !important;
        }

        .cre8shield-modal-user-head {
            display: flex !important;
            align-items: flex-start !important;
            justify-content: space-between !important;
            gap: 1rem !important;
        }

        .cre8shield-modal-user-identity {
            display: flex !important;
            align-items: center !important;
            gap: 0.85rem !important;
            min-width: 0 !important;
            flex: 1 1 auto !important;
        }

        .cre8shield-modal-user-avatar,
        .cre8shield-modal-user-avatar img {
            width: 56px !important;
            height: 56px !important;
            min-width: 56px !important;
            flex: 0 0 56px !important;
            border-radius: 50% !important;
            object-fit: cover !important;
        }

        .cre8shield-modal-user-copy {
            min-width: 0 !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 0.22rem !important;
        }

        .cre8shield-modal-user-copy .cre8shield-role-pill {
            margin: 0 !important;
            width: fit-content !important;
        }

        .cre8shield-modal-user-name {
            margin: 0 !important;
            font-size: 1.05rem !important;
            line-height: 1.18 !important;
            font-weight: 800 !important;
            max-width: 100% !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            white-space: nowrap !important;
        }

        .cre8shield-modal-user-email {
            padding-left: 0 !important;
            margin: 0 !important;
            max-width: 100% !important;
            font-size: 0.86rem !important;
            line-height: 1.3 !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            white-space: nowrap !important;
        }

        .cre8shield-modal-user-id {
            flex: 0 0 auto !important;
            align-self: flex-start !important;
            white-space: nowrap !important;
            margin-top: 0.1rem !important;
        }

        .cre8shield-modal-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
            gap: 0.6rem !important;
            margin-top: 0.2rem !important;
        }

        .cre8shield-modal-user-actions {
            padding-left: 0 !important;
            margin-top: 0.05rem !important;
            display: flex !important;
            justify-content: flex-end !important;
            width: 100% !important;
        }

        .cre8shield-modal-user-actions .cre8shield-action-btn,
        .cre8shield-modal-user-actions a.cre8shield-action-btn {
            min-width: 190px !important;
            text-align: center !important;
            justify-content: center !important;
        }

        @media (max-width: 640px) {
            .cre8shield-modal-user-head {
                flex-direction: column !important;
                align-items: stretch !important;
            }

            .cre8shield-modal-user-id {
                align-self: flex-start !important;
            }

            .cre8shield-modal-grid {
                grid-template-columns: 1fr !important;
            }

            .cre8shield-modal-user-actions {
                justify-content: stretch !important;
            }

            .cre8shield-modal-user-actions .cre8shield-action-btn,
            .cre8shield-modal-user-actions a.cre8shield-action-btn {
                width: 100% !important;
                min-width: 0 !important;
            }
        }
    </style>
<link rel="icon" type="image/png" sizes="16x16" href="../../public/images/favicon-16.png">
<link rel="icon" type="image/png" sizes="32x32" href="../../public/images/favicon-32.png">
<link rel="shortcut icon" type="image/png" href="../../public/images/favicon-32.png">
<link rel="apple-touch-icon" sizes="180x180" href="../../public/images/apple-touch-icon.png">
</head>
<body class="cre8-admin-layout"><?php cre8_bo_early_theme_print_body_script(); ?>
    <div class="container-scroller cre8-admin-page">
        <?php require_once dirname(__DIR__) . '/layout/sidebar.php'; ?>
        <main class="page-body-wrapper cre8-admin-main">
            <?php require_once dirname(__DIR__) . '/layout/header.php'; ?>
            <div class="main-panel">
            <div class="content-wrapper admin-shell">
                <header class="admin-header card grid-margin">
                    <div class="admin-header-main card-body">
                        <div>
                            <h1 data-i18n="cre8shield.title">Cre8Shield Monitor</h1>
                            <p data-i18n="cre8shield.subtitle">Review suspicious AI/security catches detected from prompts and page scans.</p>
                        </div>
                    </div>
                </header>

        <?php renderBackOfficeCollaborationTabs('cre8shield'); ?>

                <?php if ($notice !== ''): ?>
                    <div class="admin-flash <?php echo $noticeType === 'danger' ? 'error' : 'success'; ?>">
                        <span<?php echo cre8shieldI18nAttr(cre8shieldNoticeI18nKey($notice)); ?>><?php echo htmlspecialchars($notice); ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!$tableAvailable): ?>
                    <div class="admin-flash error">
                        <span data-i18n="cre8shield.notice.tableUnavailableLong">The cre8shield_catches table is not available on this database. Cre8Pilot will continue to run rule-based and AI checks, but catches cannot be persisted yet.</span>
                    </div>
                <?php endif; ?>

                <section class="cre8shield-summary">
                    <article class="card card-body">
                        <h3 data-i18n="cre8shield.kpi.openQueue">Open queue now</h3>
                        <p><?php echo $openQueueTotal; ?></p>
                        <small>
                            <span data-i18n="cre8shield.tabs.high">High risks</span>: <?php echo (int) ($counts['high'] ?? 0); ?> ·
                            <span data-i18n="cre8shield.tabs.medium">Medium risks</span>: <?php echo (int) ($counts['medium'] ?? 0); ?> ·
                            <span data-i18n="cre8shield.tabs.escalated">Escalated</span>: <?php echo (int) ($counts['escalated'] ?? 0); ?>
                        </small>
                    </article>
                    <article class="card card-body">
                        <h3<?php echo cre8shieldI18nAttr($tabTitleKey); ?>><?php echo htmlspecialchars($tabTitle); ?></h3>
                        <p><?php echo $tabRowCount; ?></p>
                        <small>
                            <span data-i18n="cre8shield.kpi.avgRiskScore">Avg risk score</span>: <?php echo $tabAverageRiskScore; ?>/100
                            <?php if ($tabEscalatedCount > 0): ?>
                                · <span data-i18n="cre8shield.kpi.escalatedInTab">Escalated in tab</span>: <?php echo $tabEscalatedCount; ?>
                            <?php endif; ?>
                        </small>
                    </article>
                    <article class="card card-body">
                        <h3 data-i18n="cre8shield.kpi.reportedIdentified">Reported user identified</h3>
                        <p><?php echo $tabReportedCoverage; ?>%</p>
                        <small>
                            <span data-i18n="cre8shield.kpi.known">Known</span>: <?php echo $tabReportedKnownCount; ?>/<?php echo $tabRowCount; ?>
                            <?php if ($tabUnknownReportedCount > 0): ?>
                                · <span data-i18n="cre8shield.kpi.missingIds">Missing IDs</span>: <?php echo $tabUnknownReportedCount; ?>
                            <?php endif; ?>
                        </small>
                    </article>
                    <article class="card card-body">
                        <h3 data-i18n="cre8shield.kpi.sourceQuality">Source quality in tab</h3>
                        <p><?php echo $tabLinkedSourceCount; ?></p>
                        <small>
                            <span data-i18n="cre8shield.kpi.linkedSources">Linked offers/candidatures</span>: <?php echo $tabLinkedSourceCount; ?>
                            · <span data-i18n="cre8shield.kpi.promptOnly">Prompt-only</span>: <?php echo $tabPromptCount; ?>
                        </small>
                    </article>
                </section>

                <nav class="nav nav-tabs cre8shield-tabs" aria-label="Cre8Shield tabs">
                    <a class="<?php echo $tabBase('high'); ?>" href="<?php echo htmlspecialchars($tabHref('high')); ?>">
                        <span data-i18n="cre8shield.tabs.high">High risks</span> <span class="badge badge-danger cre8shield-tab-count"><?php echo (int) ($counts['high'] ?? 0); ?></span>
                    </a>
                    <a class="<?php echo $tabBase('medium'); ?>" href="<?php echo htmlspecialchars($tabHref('medium')); ?>">
                        <span data-i18n="cre8shield.tabs.medium">Medium risks</span> <span class="badge badge-warning cre8shield-tab-count"><?php echo (int) ($counts['medium'] ?? 0); ?></span>
                    </a>
                    <a class="<?php echo $tabBase('escalated'); ?>" href="<?php echo htmlspecialchars($tabHref('escalated')); ?>">
                        <span data-i18n="cre8shield.tabs.escalated">Escalated</span> <span class="badge badge-danger cre8shield-tab-count"><?php echo (int) ($counts['escalated'] ?? 0); ?></span>
                    </a>
                    <a class="<?php echo $tabBase('reviewed'); ?>" href="<?php echo htmlspecialchars($tabHref('reviewed')); ?>">
                        <span data-i18n="cre8shield.tabs.reviewed">Reviewed / resolved</span> <span class="badge badge-success cre8shield-tab-count"><?php echo (int) ($counts['reviewed'] ?? 0); ?></span>
                    </a>
                </nav>

                <section class="card grid-margin cre8shield-toolbar" aria-label="Cre8Shield ordering controls">
                    <div>
                        <strong data-i18n="cre8shield.toolbar.orderCatches">Order catches</strong>
                        <span<?php echo cre8shieldI18nAttr(cre8shieldSortI18nKey($sort)); ?>><?php echo htmlspecialchars($sortOptions[$sort] ?? 'Newest recorded'); ?></span>
                    </div>
                    <form method="get" action="index.php" class="cre8shield-sort-form form-inline">
                        <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
                        <label for="cre8shieldSort" data-i18n="cre8shield.toolbar.sortBy">Sort by</label>
                        <select id="cre8shieldSort" name="sort" class="form-control" onchange="this.form.submit()">
                            <?php foreach ($sortOptions as $sortKey => $sortLabel): ?>
                                <option value="<?php echo htmlspecialchars($sortKey); ?>"<?php echo $sort === $sortKey ? ' selected' : ''; ?><?php echo cre8shieldI18nAttr(cre8shieldSortI18nKey($sortKey)); ?>>
                                    <?php echo htmlspecialchars($sortLabel); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary"><span data-i18n="common.applyFilters">Apply filters</span></button>
                    </form>
                </section>

                <?php if (empty($rows)): ?>
                    <div class="cre8shield-empty">
                        <strong data-i18n="cre8shield.empty.title">No catches in this tab.</strong>
                        <p data-i18n="cre8shield.empty.subtitle">Cre8Shield will list medium and high risk findings as soon as they are raised by chat or page scans.</p>
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
                            $catchId = (int) ($row['id_catch'] ?? 0);
                            $reporterLabel = cre8shieldPrettyRoleId($row['reporter_role'] ?? '', $row['reporter_user_id'] ?? 0);
                            $reportedLabel = cre8shieldPrettyRoleId($row['reported_role'] ?? '', $row['reported_user_id'] ?? 0);
                            $reporterCellHtml = cre8shieldRenderUserCell($row['reporter_role'] ?? '', $row['reporter_user_id'] ?? 0, $cre8shieldUserMap, 'reporter', $catchId);
                            $reportedCellHtml = cre8shieldRenderUserCell($row['reported_role'] ?? '', $row['reported_user_id'] ?? 0, $cre8shieldUserMap, 'reported', $catchId);
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
                                    <div class="cre8shield-escalated-banner" aria-label="Escalated catch" data-i18n-aria-label="cre8shield.card.escalatedCatch">
                                        <span class="cre8shield-escalated-dot"></span>
                                        <span data-i18n="cre8shield.card.escalatedPriority">ESCALATED · priority review</span>
                                    </div>
                                <?php endif; ?>

                                <div class="cre8shield-card-head">
                                    <div class="cre8shield-card-head-left">
                                        <span class="badge <?php echo htmlspecialchars($riskBadgeClass); ?> cre8shield-pill risk-<?php echo htmlspecialchars($level); ?>">
                                            <span<?php echo cre8shieldI18nAttr(cre8shieldRiskI18nKey($level)); ?>><?php echo htmlspecialchars(ucfirst($level)); ?></span>
                                        </span>
                                        <span class="cre8shield-score"><span data-i18n="cre8shield.card.score">Score</span> <strong><?php echo $score; ?></strong>/100</span>
                                    </div>
                                    <span class="badge <?php echo htmlspecialchars($statusBadgeClass); ?> cre8shield-pill status-<?php echo htmlspecialchars($status); ?>">
                                        <span<?php echo cre8shieldI18nAttr(cre8shieldStatusI18nKey($status)); ?>><?php echo htmlspecialchars(cre8shieldStatusLabel($status)); ?></span>
                                    </span>
                                </div>

                                <dl class="cre8shield-card-meta-grid">
                                    <div>
                                        <dt data-i18n="cre8shield.card.created">Created</dt>
                                        <dd><?php echo htmlspecialchars($created); ?></dd>
                                    </div>
                                    <div>
                                        <dt data-i18n="cre8shield.card.source">Source</dt>
                                        <dd<?php echo cre8shieldI18nAttr(cre8shieldSourceI18nKey($row['source_type'] ?? '')); ?>><?php echo htmlspecialchars($sourceType); ?></dd>
                                    </div>
                                    <?php if ($page !== ''): ?>
                                        <div>
                                            <dt data-i18n="cre8shield.card.page">Page</dt>
                                            <dd><?php echo htmlspecialchars($page); ?></dd>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <dt data-i18n="cre8shield.card.reporter">Reporter</dt>
                                        <dd><?php echo $reporterCellHtml; ?></dd>
                                    </div>
                                    <div>
                                        <dt data-i18n="cre8shield.card.reported">Reported</dt>
                                        <dd><?php echo $reportedCellHtml; ?></dd>
                                    </div>
                                </dl>

                                <?php if ($sourceLabel !== ''): ?>
                                    <p class="cre8shield-card-source-label" title="<?php echo htmlspecialchars($sourceLabel); ?>">
                                        <strong data-i18n="cre8shield.modal.sourceLabel">Source label:</strong> <?php echo htmlspecialchars(cre8shieldExcerpt($sourceLabel, 160)); ?>
                                    </p>
                                <?php endif; ?>

                                <?php if (!empty($cats)): ?>
                                    <div class="cre8shield-card-categories">
                                        <?php foreach ($cats as $cat): ?>
                                            <span class="cre8shield-cat-chip"<?php echo cre8shieldI18nAttr(cre8shieldCategoryI18nKey($cat)); ?>><?php echo htmlspecialchars(cre8shieldPrettyCategory($cat)); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($finding !== ''): ?>
                                    <div class="cre8shield-card-section">
                                        <h4 data-i18n="cre8shield.card.finding">Finding</h4>
                                        <p><?php echo nl2br(htmlspecialchars(cre8shieldExcerpt($finding, 280))); ?></p>
                                    </div>
                                <?php elseif ($sanitized !== ''): ?>
                                    <div class="cre8shield-card-section">
                                        <h4 data-i18n="cre8shield.card.sanitizedExcerpt">Sanitized excerpt</h4>
                                        <p><?php echo nl2br(htmlspecialchars(cre8shieldExcerpt($sanitized, 280))); ?></p>
                                    </div>
                                <?php endif; ?>

                                <?php if ($recommendations !== ''): ?>
                                    <div class="cre8shield-card-section is-recommend">
                                        <h4 data-i18n="cre8shield.card.safeRecommendation">Safe recommendation</h4>
                                        <p><?php echo nl2br(htmlspecialchars(cre8shieldExcerpt($recommendations, 220))); ?></p>
                                    </div>
                                <?php endif; ?>

                                <?php if ($adminNotes !== ''): ?>
                                    <div class="cre8shield-card-section is-admin-note">
                                        <h4 data-i18n="cre8shield.card.adminNote">Admin note</h4>
                                        <p><?php echo nl2br(htmlspecialchars(cre8shieldExcerpt($adminNotes, 220))); ?></p>
                                    </div>
                                <?php endif; ?>

                                <button type="button"
                                        class="cre8shield-details-summary cre8shield-details-modal-trigger"
                                        data-cre8shield-details-trigger
                                        data-details-template="<?php echo htmlspecialchars($detailsId); ?>Template"
                                >
                                    <span data-i18n="cre8shield.card.viewFullDetails">View full details</span>
                                </button>
                                <template id="<?php echo htmlspecialchars($detailsId); ?>Template">
                                    <div class="cre8shield-details-body">
                                        <?php if ($finding !== ''): ?>
                                            <section>
                                                <h5 data-i18n="cre8shield.card.fullFinding">Full finding</h5>
                                                <p><?php echo nl2br(htmlspecialchars($finding)); ?></p>
                                            </section>
                                        <?php endif; ?>

                                        <?php if ($recommendations !== ''): ?>
                                            <section>
                                                <h5 data-i18n="cre8shield.card.safeRecommendations">Safe recommendations</h5>
                                                <p><?php echo nl2br(htmlspecialchars($recommendations)); ?></p>
                                            </section>
                                        <?php endif; ?>

                                        <?php if ($sanitized !== ''): ?>
                                            <section>
                                                <h5 data-i18n="cre8shield.modal.sanitizedMessage">Sanitized message</h5>
                                                <pre class="cre8shield-pre"><?php echo htmlspecialchars($sanitized); ?></pre>
                                            </section>
                                        <?php endif; ?>

                                        <?php if ($rawSnapshot !== '' && $rawSnapshot !== $sanitized): ?>
                                            <section>
                                                <h5 data-i18n="cre8shield.modal.rawSnapshot">Raw snapshot (admin only)</h5>
                                                <pre class="cre8shield-pre is-raw"><?php echo htmlspecialchars($rawSnapshot); ?></pre>
                                            </section>
                                        <?php endif; ?>

                                        <section class="cre8shield-details-grid">
                                            <div><strong data-i18n="cre8shield.card.sourceType">Source type:</strong> <span<?php echo cre8shieldI18nAttr(cre8shieldSourceI18nKey($row['source_type'] ?? '')); ?>><?php echo htmlspecialchars($sourceType); ?></span></div>
                                            <?php if (!empty($row['source_id'])): ?>
                                                <div><strong data-i18n="cre8shield.card.sourceId">Source id:</strong> <?php echo htmlspecialchars((string) $row['source_id']); ?></div>
                                            <?php endif; ?>
                                            <?php if ($sourceLabel !== ''): ?>
                                                <div><strong data-i18n="cre8shield.modal.sourceLabel">Source label:</strong> <?php echo htmlspecialchars($sourceLabel); ?></div>
                                            <?php endif; ?>
                                            <?php if ($page !== ''): ?><div><strong data-i18n="cre8shield.modal.page">Page:</strong> <?php echo htmlspecialchars($page); ?></div><?php endif; ?>
                                            <?php if ($mode !== ''): ?><div><strong data-i18n="cre8shield.card.mode">Mode:</strong> <?php echo htmlspecialchars($mode); ?></div><?php endif; ?>
                                            <?php if ($role !== ''): ?><div><strong data-i18n="common.role">Role:</strong> <?php echo htmlspecialchars(ucfirst($role)); ?></div><?php endif; ?>
                                            <div><strong data-i18n="cre8shield.card.reporterLabel">Reporter:</strong> <?php echo $reporterCellHtml; ?></div>
                                            <div><strong data-i18n="cre8shield.card.reportedLabel">Reported:</strong> <?php echo $reportedCellHtml; ?></div>
                                            <?php if ($aiDecision !== ''): ?><div><strong data-i18n="cre8shield.modal.aiDecision">AI decision:</strong> <?php echo htmlspecialchars($aiDecision); ?></div><?php endif; ?>
                                            <div><strong data-i18n="cre8shield.card.createdLabel">Created:</strong> <?php echo htmlspecialchars($created); ?></div>
                                            <div><strong data-i18n="cre8shield.card.updatedLabel">Updated:</strong> <?php echo htmlspecialchars($updated); ?></div>
                                            <div><strong data-i18n="cre8shield.card.reviewedAt">Reviewed at:</strong> <?php echo htmlspecialchars($reviewed); ?></div>
                                            <div><strong data-i18n="cre8shield.card.reviewedBy">Reviewed by:</strong> <?php echo htmlspecialchars($reviewedBy); ?></div>
                                        </section>

                                        <?php if ($aiRationale !== ''): ?>
                                            <section>
                                                <h5 data-i18n="cre8shield.modal.aiRationale">AI rationale</h5>
                                                <p><?php echo nl2br(htmlspecialchars($aiRationale)); ?></p>
                                            </section>
                                        <?php endif; ?>
                                    </div>
                                </template>

                                <div class="cre8shield-card-actions">
                                    <button type="button"
                                            class="btn btn-info btn-sm cre8shield-action-btn is-source"
                                            data-cre8shield-source-trigger
                                            data-source-id="<?php echo htmlspecialchars($sourceTargetId); ?>"
                                            data-source-kind="<?php echo htmlspecialchars($sourceKind); ?>"
                                            data-catch-id="<?php echo $catchId; ?>"
                                            title="Open the original offer, candidature, or prompt that triggered this catch."
                                    >
                                        <span<?php echo cre8shieldI18nAttr(match ($sourceKind) { 'offer' => 'cre8shield.actions.viewOfferSource', 'candidature' => 'cre8shield.actions.viewCandidatureSource', default => 'cre8shield.actions.viewPromptSource' }); ?>><?php echo htmlspecialchars($viewSourceLabel); ?></span>
                                    </button>
                                    <?php if ($status === 'open' || $status === 'escalated'): ?>
                                        <form method="post" action="index.php">
                                            <input type="hidden" name="cre8shieldAction" value="mark_reviewed">
                                            <input type="hidden" name="catchId" value="<?php echo $catchId; ?>">
                                            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
                                            <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
                                            <button type="submit" class="btn btn-primary btn-sm cre8shield-action-btn is-primary"><span data-i18n="cre8shield.actions.markReviewed">Mark reviewed</span></button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($status !== 'ignored'): ?>
                                        <form method="post" action="index.php">
                                            <input type="hidden" name="cre8shieldAction" value="ignore">
                                            <input type="hidden" name="catchId" value="<?php echo $catchId; ?>">
                                            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
                                            <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
                                            <button type="submit" class="btn btn-secondary btn-sm cre8shield-action-btn is-muted"><span data-i18n="cre8shield.actions.ignore">Ignore</span></button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($status !== 'escalated'): ?>
                                        <form method="post" action="index.php">
                                            <input type="hidden" name="cre8shieldAction" value="escalate">
                                            <input type="hidden" name="catchId" value="<?php echo $catchId; ?>">
                                            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
                                            <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
                                            <button type="submit" class="btn btn-warning btn-sm cre8shield-action-btn is-warning"><span data-i18n="cre8shield.actions.escalate">Escalate</span></button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($status !== 'resolved'): ?>
                                        <form method="post" action="index.php">
                                            <input type="hidden" name="cre8shieldAction" value="resolve">
                                            <input type="hidden" name="catchId" value="<?php echo $catchId; ?>">
                                            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
                                            <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
                                            <button type="submit" class="btn btn-success btn-sm cre8shield-action-btn is-success"><span data-i18n="cre8shield.actions.resolve">Resolve</span></button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="cre8shield-templates" hidden>
                    <?php foreach ($cre8shieldUserMap as $tplUserId => $tplUser): ?>
                        <template id="cre8shieldUserTemplate-<?php echo (int) $tplUserId; ?>"><?php echo cre8shieldRenderUserCardTemplate((int) $tplUserId, $tplUser, $adminId, cc_current_user_role(), $tab, $sort); ?></template>
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
                                <span class="cre8shield-modal-eyebrow" data-cre8shield-modal-eyebrow data-i18n="cre8shield.modal.details">Details</span>
                                <h3 id="cre8shieldModalTitle" data-cre8shield-modal-title data-i18n="cre8shield.modal.context">Cre8Shield context</h3>
                            </div>
                            <button type="button" class="cre8shield-modal-close" data-cre8shield-modal-close aria-label="Close" data-i18n-aria-label="cre8shield.modal.close"><span data-i18n="cre8shield.modal.close">Close</span></button>
                        </div>
                        <div class="cre8shield-modal-body" data-cre8shield-modal-body>
                            <p class="cre8shield-modal-empty" data-i18n="cre8shield.modal.loading">Loading…</p>
                        </div>
                    </div>
                </div>
            </div>
            </div>
        </main>
    </div>
    <script src="../layout/back-layout.js?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../layout/back-layout.js')); ?>"></script>
    <script>
        window.cre8BackRegisterTranslations && window.cre8BackRegisterTranslations({
            en: {
                'collaboration.tab.offers': 'Offers',
                'collaboration.tab.offersHint': 'Targeted invitations',
                'collaboration.tab.candidatures': 'Candidatures',
                'collaboration.tab.candidaturesHint': 'Creator responses',
                'collaboration.tab.cre8shield': 'Cre8Shield',
                'collaboration.tab.cre8shieldHint': 'Risk monitoring',
                'cre8shield.title': 'Cre8Shield Monitor',
                'cre8shield.subtitle': 'Review suspicious AI/security catches detected from prompts and page scans.',
                'cre8shield.kpi.openQueue': 'Open queue now',
                'cre8shield.kpi.avgRiskScore': 'Avg risk score',
                'cre8shield.kpi.escalatedInTab': 'Escalated in tab',
                'cre8shield.kpi.reportedIdentified': 'Reported user identified',
                'cre8shield.kpi.known': 'Known',
                'cre8shield.kpi.missingIds': 'Missing IDs',
                'cre8shield.kpi.sourceQuality': 'Source quality in tab',
                'cre8shield.kpi.linkedSources': 'Linked offers/candidatures',
                'cre8shield.kpi.promptOnly': 'Prompt-only',
                'cre8shield.tabs.high': 'High risks',
                'cre8shield.tabs.medium': 'Medium risks',
                'cre8shield.tabs.escalated': 'Escalated',
                'cre8shield.tabs.reviewed': 'Reviewed / resolved',
                'cre8shield.tabs.current': 'Current tab',
                'cre8shield.toolbar.orderCatches': 'Order catches',
                'cre8shield.toolbar.sortBy': 'Sort by',
                'cre8shield.sort.timeDesc': 'Newest recorded',
                'cre8shield.sort.timeAsc': 'Oldest recorded',
                'cre8shield.sort.scoreDesc': 'Highest score',
                'cre8shield.sort.scoreAsc': 'Lowest score',
                'cre8shield.sort.updatedDesc': 'Recently updated',
                'cre8shield.sort.updatedAsc': 'Oldest updated',
                'cre8shield.sort.statusPriority': 'Escalated priority',
                'cre8shield.empty.title': 'No catches in this tab.',
                'cre8shield.empty.subtitle': 'Cre8Shield will list medium and high risk findings as soon as they are raised by chat or page scans.',
                'cre8shield.card.escalatedCatch': 'Escalated catch',
                'cre8shield.card.escalatedPriority': 'ESCALATED · priority review',
                'cre8shield.card.score': 'Score',
                'cre8shield.card.created': 'Created',
                'cre8shield.card.updated': 'Updated',
                'cre8shield.card.source': 'Source',
                'cre8shield.card.page': 'Page',
                'cre8shield.card.reporter': 'Reporter',
                'cre8shield.card.reported': 'Reported',
                'cre8shield.card.finding': 'Finding',
                'cre8shield.card.sanitizedExcerpt': 'Sanitized excerpt',
                'cre8shield.card.safeRecommendation': 'Safe recommendation',
                'cre8shield.card.adminNote': 'Admin note',
                'cre8shield.card.viewFullDetails': 'View full details',
                'cre8shield.card.fullFinding': 'Full finding',
                'cre8shield.card.safeRecommendations': 'Safe recommendations',
                'cre8shield.card.sourceType': 'Source type:',
                'cre8shield.card.sourceId': 'Source id:',
                'cre8shield.card.sourceLabel': 'Source label:',
                'cre8shield.card.pageLabel': 'Page:',
                'cre8shield.card.mode': 'Mode:',
                'cre8shield.card.reporterLabel': 'Reporter:',
                'cre8shield.card.reportedLabel': 'Reported:',
                'cre8shield.card.aiDecision': 'AI decision:',
                'cre8shield.card.createdLabel': 'Created:',
                'cre8shield.card.updatedLabel': 'Updated:',
                'cre8shield.card.reviewedAt': 'Reviewed at:',
                'cre8shield.card.reviewedBy': 'Reviewed by:',
                'cre8shield.risk.high': 'High',
                'cre8shield.risk.medium': 'Medium',
                'cre8shield.risk.low': 'Low',
                'cre8shield.risk.info': 'Info',
                'cre8shield.status.open': 'Open',
                'cre8shield.status.reviewed': 'Reviewed',
                'cre8shield.status.ignored': 'Ignored',
                'cre8shield.status.escalated': 'Escalated',
                'cre8shield.status.resolved': 'Resolved',
                'cre8shield.actions.viewOfferSource': 'View offer source',
                'cre8shield.actions.viewCandidatureSource': 'View candidature source',
                'cre8shield.actions.viewPromptSource': 'View prompt source',
                'cre8shield.actions.markReviewed': 'Mark reviewed',
                'cre8shield.actions.ignore': 'Ignore',
                'cre8shield.actions.escalate': 'Escalate',
                'cre8shield.actions.resolve': 'Resolve',
                'cre8shield.actions.suspendReported': 'Suspend reported user',
                'cre8shield.modal.details': 'Details',
                'cre8shield.modal.context': 'Cre8Shield context',
                'cre8shield.modal.close': 'Close',
                'cre8shield.modal.loading': 'Loading…',
                'cre8shield.modal.noData': 'No data is available for this entry.',
                'cre8shield.modal.userTitle': 'User',
                'cre8shield.modal.userProfile': 'User profile',
                'cre8shield.modal.offerSource': 'Offer source',
                'cre8shield.modal.candidatureSource': 'Candidature source',
                'cre8shield.modal.promptSource': 'Prompt source',
                'cre8shield.modal.catchSource': 'Catch source',
                'cre8shield.modal.sourceSnapshot': 'Source snapshot',
                'cre8shield.modal.joined': 'Joined',
                'cre8shield.modal.budget': 'Budget',
                'cre8shield.modal.published': 'Published',
                'cre8shield.modal.deadline': 'Deadline',
                'cre8shield.modal.brand': 'Brand',
                'cre8shield.modal.creator': 'Creator',
                'cre8shield.modal.targetedCreator': 'Targeted creator',
                'cre8shield.modal.proposedBudget': 'Proposed budget',
                'cre8shield.modal.proposedTimeline': 'Proposed timeline',
                'cre8shield.modal.submitted': 'Submitted',
                'cre8shield.modal.lastUpdate': 'Last update',
                'cre8shield.modal.description': 'Description',
                'cre8shield.modal.objective': 'Objective:',
                'cre8shield.modal.origin': 'Origin:',
                'cre8shield.modal.offerInvitation': 'Offer invitation',
                'cre8shield.modal.days': 'days',
                'cre8shield.modal.creatorMessage': 'Creator message',
                'cre8shield.modal.noCreatorSelected': 'No creator selected',
                'cre8shield.modal.inspectOfferWorkspace': 'Inspect in admin offer workspace',
                'cre8shield.modal.openCandidature': 'Open candidature',
                'cre8shield.modal.openLinkedOffer': 'Open linked offer',
                'cre8shield.modal.sanitizedMessage': 'Sanitized message',
                'cre8shield.modal.rawSnapshot': 'Raw snapshot (admin only)',
                'cre8shield.modal.aiRationale': 'AI rationale',
                'cre8shield.modal.sourceLabel': 'Source label:',
                'cre8shield.modal.page': 'Page:',
                'cre8shield.modal.aiDecision': 'AI decision:',
                'cre8shield.sourceKind.offer': 'Offer',
                'cre8shield.sourceKind.candidature': 'Candidature',
                'cre8shield.sourceKind.prompt': 'Prompt',
                'cre8shield.offerStatus.draft': 'Draft',
                'cre8shield.offerStatus.published': 'Published',
                'cre8shield.offerStatus.archived': 'Archived',
                'cre8shield.offerStatus.cancelled': 'Cancelled',
                'cre8shield.candidatureStatus.draft': 'Draft response',
                'cre8shield.candidatureStatus.sent': 'Accepted invitation',
                'cre8shield.candidatureStatus.review': 'Response under review',
                'cre8shield.candidatureStatus.negotiation': 'Negotiation requested',
                'cre8shield.candidatureStatus.accepted': 'Accepted terms',
                'cre8shield.candidatureStatus.refused': 'Refused by brand',
                'cre8shield.candidatureStatus.withdrawn': 'Declined invitation',
                'cre8shield.role.user': 'User',
                'cre8shield.role.brand': 'Brand',
                'cre8shield.role.creator': 'Creator',
                'cre8shield.role.admin': 'Admin',
                'cre8shield.role.superAdmin': 'Super admin',
                'cre8shield.role.hyperAdmin': 'Hyper admin',
                'cre8shield.category.off_platform_payment': 'Off-platform payment',
                'cre8shield.category.platform_bypass': 'Platform bypass',
                'cre8shield.category.suspicious_link': 'Suspicious link',
                'cre8shield.category.phishing': 'Phishing',
                'cre8shield.category.credential_theft': 'Credential theft',
                'cre8shield.category.suspicious_invoice': 'Suspicious invoice',
                'cre8shield.category.payment_risk': 'Payment risk',
                'cre8shield.category.social_engineering': 'Social engineering',
                'cre8shield.category.pii_leak': 'PII leak',
                'cre8shield.category.malware': 'Malware',
                'cre8shield.category.spam': 'Spam',
                'cre8shield.category.harassment': 'Harassment',
                'cre8shield.category.impersonation': 'Impersonation',
                'cre8shield.source.chatMessage': 'Chat message',
                'cre8shield.source.chatLink': 'Chat link',
                'cre8shield.source.pageScan': 'Page scan',
                'cre8shield.source.pageVisit': 'Page visit',
                'cre8shield.source.manualReport': 'Manual report',
                'cre8shield.source.backgroundScan': 'Background scan',
                'cre8shield.notice.reviewed': 'Catch marked as reviewed.',
                'cre8shield.notice.reviewedFail': 'Could not mark this catch as reviewed.',
                'cre8shield.notice.ignored': 'Catch ignored.',
                'cre8shield.notice.ignoredFail': 'Could not ignore this catch.',
                'cre8shield.notice.escalated': 'Catch escalated for priority review.',
                'cre8shield.notice.escalatedFail': 'Could not escalate this catch.',
                'cre8shield.notice.resolved': 'Catch resolved.',
                'cre8shield.notice.resolvedFail': 'Could not resolve this catch.',
                'cre8shield.notice.reportedUserMissing': 'Reported user was not found.',
                'cre8shield.notice.suspendDenied': 'You are not allowed to suspend this reported user.',
                'cre8shield.notice.suspendActiveOnly': 'Only active reported users can be suspended.',
                'cre8shield.notice.reportedSuspended': 'Reported user suspended successfully.',
                'cre8shield.notice.unknownAction': 'Unknown action.',
                'cre8shield.notice.tableUnavailable': 'Cre8Shield monitor table is not available.',
                'cre8shield.notice.tableUnavailableLong': 'The cre8shield_catches table is not available on this database. Cre8Pilot will continue to run rule-based and AI checks, but catches cannot be persisted yet.'
            },
            fr: {
                'collaboration.tab.offers': 'Offres',
                'collaboration.tab.offersHint': 'Invitations ciblees',
                'collaboration.tab.candidatures': 'Candidatures',
                'collaboration.tab.candidaturesHint': 'Reponses des createurs',
                'collaboration.tab.cre8shield': 'Cre8Shield',
                'collaboration.tab.cre8shieldHint': 'Surveillance des risques',
                'cre8shield.title': 'Moniteur Cre8Shield',
                'cre8shield.subtitle': 'Examinez les alertes IA/securite suspectes detectees dans les prompts et les scans de pages.',
                'cre8shield.kpi.openQueue': 'File ouverte',
                'cre8shield.kpi.avgRiskScore': 'Score de risque moyen',
                'cre8shield.kpi.escalatedInTab': 'Escalades dans l onglet',
                'cre8shield.kpi.reportedIdentified': 'Utilisateur signale identifie',
                'cre8shield.kpi.known': 'Connus',
                'cre8shield.kpi.missingIds': 'IDs manquants',
                'cre8shield.kpi.sourceQuality': 'Qualite des sources',
                'cre8shield.kpi.linkedSources': 'Offres/candidatures liees',
                'cre8shield.kpi.promptOnly': 'Prompts seuls',
                'cre8shield.tabs.high': 'Risques eleves',
                'cre8shield.tabs.medium': 'Risques moyens',
                'cre8shield.tabs.escalated': 'Escalades',
                'cre8shield.tabs.reviewed': 'Verifies / resolus',
                'cre8shield.tabs.current': 'Onglet actuel',
                'cre8shield.toolbar.orderCatches': 'Trier les alertes',
                'cre8shield.toolbar.sortBy': 'Trier par',
                'cre8shield.sort.timeDesc': 'Plus recent',
                'cre8shield.sort.timeAsc': 'Plus ancien',
                'cre8shield.sort.scoreDesc': 'Score le plus haut',
                'cre8shield.sort.scoreAsc': 'Score le plus bas',
                'cre8shield.sort.updatedDesc': 'Mis a jour recemment',
                'cre8shield.sort.updatedAsc': 'Plus ancienne mise a jour',
                'cre8shield.sort.statusPriority': 'Priorite aux escalades',
                'cre8shield.empty.title': 'Aucune alerte dans cet onglet.',
                'cre8shield.empty.subtitle': 'Cre8Shield affichera les risques moyens et eleves des qu ils seront detectes par le chat ou les scans de pages.',
                'cre8shield.card.escalatedCatch': 'Alerte escaladee',
                'cre8shield.card.escalatedPriority': 'ESCALADE · verification prioritaire',
                'cre8shield.card.score': 'Score',
                'cre8shield.card.created': 'Creation',
                'cre8shield.card.updated': 'Mise a jour',
                'cre8shield.card.source': 'Source',
                'cre8shield.card.page': 'Page',
                'cre8shield.card.reporter': 'Signalant',
                'cre8shield.card.reported': 'Signale',
                'cre8shield.card.finding': 'Constat',
                'cre8shield.card.sanitizedExcerpt': 'Extrait nettoye',
                'cre8shield.card.safeRecommendation': 'Recommandation sure',
                'cre8shield.card.adminNote': 'Note admin',
                'cre8shield.card.viewFullDetails': 'Voir les details complets',
                'cre8shield.card.fullFinding': 'Constat complet',
                'cre8shield.card.safeRecommendations': 'Recommandations sures',
                'cre8shield.card.sourceType': 'Type de source :',
                'cre8shield.card.sourceId': 'ID source :',
                'cre8shield.card.sourceLabel': 'Libelle source :',
                'cre8shield.card.pageLabel': 'Page :',
                'cre8shield.card.mode': 'Mode :',
                'cre8shield.card.reporterLabel': 'Signalant :',
                'cre8shield.card.reportedLabel': 'Signale :',
                'cre8shield.card.aiDecision': 'Decision IA :',
                'cre8shield.card.createdLabel': 'Creation :',
                'cre8shield.card.updatedLabel': 'Mise a jour :',
                'cre8shield.card.reviewedAt': 'Verifie le :',
                'cre8shield.card.reviewedBy': 'Verifie par :',
                'cre8shield.risk.high': 'Eleve',
                'cre8shield.risk.medium': 'Moyen',
                'cre8shield.risk.low': 'Faible',
                'cre8shield.risk.info': 'Info',
                'cre8shield.status.open': 'Ouvert',
                'cre8shield.status.reviewed': 'Verifie',
                'cre8shield.status.ignored': 'Ignore',
                'cre8shield.status.escalated': 'Escalade',
                'cre8shield.status.resolved': 'Resolu',
                'cre8shield.actions.viewOfferSource': 'Voir la source offre',
                'cre8shield.actions.viewCandidatureSource': 'Voir la source candidature',
                'cre8shield.actions.viewPromptSource': 'Voir la source prompt',
                'cre8shield.actions.markReviewed': 'Marquer verifie',
                'cre8shield.actions.ignore': 'Ignorer',
                'cre8shield.actions.escalate': 'Escalader',
                'cre8shield.actions.resolve': 'Resoudre',
                'cre8shield.actions.suspendReported': 'Suspendre l utilisateur signale',
                'cre8shield.modal.details': 'Details',
                'cre8shield.modal.context': 'Contexte Cre8Shield',
                'cre8shield.modal.close': 'Fermer',
                'cre8shield.modal.loading': 'Chargement…',
                'cre8shield.modal.noData': 'Aucune donnee disponible pour cette entree.',
                'cre8shield.modal.userTitle': 'Utilisateur',
                'cre8shield.modal.userProfile': 'Profil utilisateur',
                'cre8shield.modal.offerSource': 'Source offre',
                'cre8shield.modal.candidatureSource': 'Source candidature',
                'cre8shield.modal.promptSource': 'Source prompt',
                'cre8shield.modal.catchSource': 'Source de l alerte',
                'cre8shield.modal.sourceSnapshot': 'Instantane source',
                'cre8shield.modal.joined': 'Inscription',
                'cre8shield.modal.budget': 'Budget',
                'cre8shield.modal.published': 'Publication',
                'cre8shield.modal.deadline': 'Echeance',
                'cre8shield.modal.brand': 'Marque',
                'cre8shield.modal.creator': 'Createur',
                'cre8shield.modal.targetedCreator': 'Createur cible',
                'cre8shield.modal.proposedBudget': 'Budget propose',
                'cre8shield.modal.proposedTimeline': 'Delai propose',
                'cre8shield.modal.submitted': 'Soumission',
                'cre8shield.modal.lastUpdate': 'Derniere mise a jour',
                'cre8shield.modal.description': 'Description',
                'cre8shield.modal.objective': 'Objectif :',
                'cre8shield.modal.origin': 'Origine :',
                'cre8shield.modal.offerInvitation': 'Invitation d offre',
                'cre8shield.modal.days': 'jours',
                'cre8shield.modal.creatorMessage': 'Message createur',
                'cre8shield.modal.noCreatorSelected': 'Aucun createur selectionne',
                'cre8shield.modal.inspectOfferWorkspace': 'Inspecter dans l espace admin des offres',
                'cre8shield.modal.openCandidature': 'Ouvrir la candidature',
                'cre8shield.modal.openLinkedOffer': 'Ouvrir l offre liee',
                'cre8shield.modal.sanitizedMessage': 'Message nettoye',
                'cre8shield.modal.rawSnapshot': 'Instantane brut (admin seulement)',
                'cre8shield.modal.aiRationale': 'Raisonnement IA',
                'cre8shield.modal.sourceLabel': 'Libelle source :',
                'cre8shield.modal.page': 'Page :',
                'cre8shield.modal.aiDecision': 'Decision IA :',
                'cre8shield.sourceKind.offer': 'Offre',
                'cre8shield.sourceKind.candidature': 'Candidature',
                'cre8shield.sourceKind.prompt': 'Prompt',
                'cre8shield.offerStatus.draft': 'Brouillon',
                'cre8shield.offerStatus.published': 'Publiee',
                'cre8shield.offerStatus.archived': 'Archivee',
                'cre8shield.offerStatus.cancelled': 'Annulee',
                'cre8shield.candidatureStatus.draft': 'Reponse brouillon',
                'cre8shield.candidatureStatus.sent': 'Invitation acceptee',
                'cre8shield.candidatureStatus.review': 'Reponse en etude',
                'cre8shield.candidatureStatus.negotiation': 'Negociation demandee',
                'cre8shield.candidatureStatus.accepted': 'Conditions acceptees',
                'cre8shield.candidatureStatus.refused': 'Refusee par la marque',
                'cre8shield.candidatureStatus.withdrawn': 'Invitation declinee',
                'cre8shield.role.user': 'Utilisateur',
                'cre8shield.role.brand': 'Marque',
                'cre8shield.role.creator': 'Createur',
                'cre8shield.role.admin': 'Admin',
                'cre8shield.role.superAdmin': 'Super admin',
                'cre8shield.role.hyperAdmin': 'Hyper admin',
                'cre8shield.category.off_platform_payment': 'Paiement hors plateforme',
                'cre8shield.category.platform_bypass': 'Contournement plateforme',
                'cre8shield.category.suspicious_link': 'Lien suspect',
                'cre8shield.category.phishing': 'Hameconnage',
                'cre8shield.category.credential_theft': 'Vol d identifiants',
                'cre8shield.category.suspicious_invoice': 'Facture suspecte',
                'cre8shield.category.payment_risk': 'Risque de paiement',
                'cre8shield.category.social_engineering': 'Ingenierie sociale',
                'cre8shield.category.pii_leak': 'Fuite de donnees personnelles',
                'cre8shield.category.malware': 'Malware',
                'cre8shield.category.spam': 'Spam',
                'cre8shield.category.harassment': 'Harcelement',
                'cre8shield.category.impersonation': 'Usurpation',
                'cre8shield.source.chatMessage': 'Message de chat',
                'cre8shield.source.chatLink': 'Lien de chat',
                'cre8shield.source.pageScan': 'Scan de page',
                'cre8shield.source.pageVisit': 'Visite de page',
                'cre8shield.source.manualReport': 'Signalement manuel',
                'cre8shield.source.backgroundScan': 'Scan en arriere-plan',
                'cre8shield.notice.reviewed': 'Alerte marquee comme verifiee.',
                'cre8shield.notice.reviewedFail': 'Impossible de marquer cette alerte comme verifiee.',
                'cre8shield.notice.ignored': 'Alerte ignoree.',
                'cre8shield.notice.ignoredFail': 'Impossible d ignorer cette alerte.',
                'cre8shield.notice.escalated': 'Alerte escaladee pour verification prioritaire.',
                'cre8shield.notice.escalatedFail': 'Impossible d escalader cette alerte.',
                'cre8shield.notice.resolved': 'Alerte resolue.',
                'cre8shield.notice.resolvedFail': 'Impossible de resoudre cette alerte.',
                'cre8shield.notice.reportedUserMissing': 'Utilisateur signale introuvable.',
                'cre8shield.notice.suspendDenied': 'Vous n etes pas autorise a suspendre cet utilisateur signale.',
                'cre8shield.notice.suspendActiveOnly': 'Seuls les utilisateurs signales actifs peuvent etre suspendus.',
                'cre8shield.notice.reportedSuspended': 'Utilisateur signale suspendu avec succes.',
                'cre8shield.notice.unknownAction': 'Action inconnue.',
                'cre8shield.notice.tableUnavailable': 'La table du moniteur Cre8Shield n est pas disponible.',
                'cre8shield.notice.tableUnavailableLong': 'La table cre8shield_catches n est pas disponible dans cette base. Cre8Pilot continuera les controles IA et regles, mais les alertes ne peuvent pas encore etre enregistrees.'
            }
        });
    </script>
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

            function openModalWithTemplate(templateId, title, eyebrowLabel, options) {
                options = options || {};
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
                    body.innerHTML = '<p class="cre8shield-modal-empty" data-i18n="cre8shield.modal.noData">No data is available for this entry.</p>';
                }
                const reportedOnlyBlocks = body.querySelectorAll('[data-cre8shield-reported-only]');
                reportedOnlyBlocks.forEach(function (block) {
                    block.hidden = options.context !== 'reported';
                });
                const catchInputs = body.querySelectorAll('[data-cre8shield-reported-catch-input]');
                catchInputs.forEach(function (input) {
                    input.value = options.catchId || '';
                });
                if (titleNode && title) {
                    titleNode.textContent = title;
                }
                if (eyebrow && eyebrowLabel) {
                    eyebrow.textContent = eyebrowLabel;
                }
                if (window.cre8BackApplyTranslations) {
                    window.cre8BackApplyTranslations();
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
                        (window.cre8BackText ? window.cre8BackText('cre8shield.modal.userTitle') : 'User') + ' #' + uid,
                        window.cre8BackText ? window.cre8BackText('cre8shield.modal.userProfile') : 'User profile',
                        {
                            context: userTrigger.dataset.userContext || '',
                            catchId: userTrigger.dataset.catchId || ''
                        }
                    );
                    return;
                }

                const detailsTrigger = event.target.closest('[data-cre8shield-details-trigger]');
                if (detailsTrigger) {
                    event.preventDefault();
                    const templateId = detailsTrigger.dataset.detailsTemplate || '';
                    if (!templateId) {
                        return;
                    }
                    openModalWithTemplate(
                        templateId,
                        window.cre8BackText ? window.cre8BackText('cre8shield.card.viewFullDetails') : 'View full details',
                        window.cre8BackText ? window.cre8BackText('cre8shield.modal.details') : 'Details'
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
                        offer: window.cre8BackText ? window.cre8BackText('cre8shield.modal.offerSource') : 'Offer source',
                        candidature: window.cre8BackText ? window.cre8BackText('cre8shield.modal.candidatureSource') : 'Candidature source',
                        prompt: window.cre8BackText ? window.cre8BackText('cre8shield.modal.promptSource') : 'Prompt source',
                    };
                    openModalWithTemplate(
                        'cre8shieldSourceTemplate-' + sid,
                        titleByKind[kind] || (window.cre8BackText ? window.cre8BackText('cre8shield.modal.catchSource') : 'Catch source'),
                        window.cre8BackText ? window.cre8BackText('cre8shield.modal.sourceSnapshot') : 'Source snapshot'
                    );
                }
            });
        })();
    </script>
</body>
</html>
