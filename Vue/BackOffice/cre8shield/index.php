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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['cre8shieldAction']) && $adminId > 0) {
    $action = (string) $_POST['cre8shieldAction'];
    $catchId = (int) ($_POST['catchId'] ?? 0);
    $tabRedirect = preg_replace('/[^a-z]/', '', (string) ($_POST['tab'] ?? 'high'));
    if (!in_array($tabRedirect, $validTabs, true)) {
        $tabRedirect = 'high';
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

    header('Location: index.php?tab=' . $tabRedirect . '&notice=' . urlencode($notice) . '&noticeType=' . urlencode($noticeType));
    exit;
}

$tab = preg_replace('/[^a-z]/', '', (string) ($_GET['tab'] ?? 'high'));
if (!in_array($tab, $validTabs, true)) {
    $tab = 'high';
}

$notice = trim((string) ($_GET['notice'] ?? ''));
$noticeType = trim((string) ($_GET['noticeType'] ?? 'success'));

$tableAvailable = $cre8shieldController->isAvailable();

$rows = [];
$counts = ['high' => 0, 'medium' => 0, 'reviewed' => 0, 'escalated' => 0];

if ($tableAvailable) {
    $counts = $cre8shieldController->getMonitorCounts();
    if ($tab === 'high') {
        $rows = $cre8shieldController->listByRisk('high', 200, 0);
    } elseif ($tab === 'medium') {
        $rows = $cre8shieldController->listByRisk('medium', 200, 0);
    } elseif ($tab === 'escalated') {
        $rows = $cre8shieldController->listEscalated(200, 0);
    } else {
        $rows = $cre8shieldController->listReviewed(200, 0);
    }
}

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

$tabBase = function ($name) use ($tab) {
    return 'cre8shield-tab' . ($tab === $name ? ' is-active' : '');
};

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Back Office - Cre8Shield Monitor</title>
    <link rel="stylesheet" href="../css/backoffice.css">
    <link rel="stylesheet" href="../offre/offre-admin.css?v=<?php echo urlencode((string) (@filemtime(__DIR__ . '/../offre/offre-admin.css') ?: 0)); ?>">
    <link rel="stylesheet" href="../condidature/condidature-admin.css?v=<?php echo urlencode((string) (@filemtime(__DIR__ . '/../condidature/condidature-admin.css') ?: 0)); ?>">
    <link rel="stylesheet" href="cre8shield-admin.css?v=<?php echo urlencode((string) (@filemtime(__DIR__ . '/cre8shield-admin.css') ?: 0)); ?>">
</head>
<body class="cre8-admin-layout">
    <div class="cre8-admin-page">
        <?php require_once dirname(__DIR__) . '/layout/sidebar.php'; ?>
        <main class="cre8-admin-main">
            <?php require_once dirname(__DIR__) . '/layout/header.php'; ?>
            <div class="admin-shell">
                <header class="admin-header">
                    <div class="admin-header-main">
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
                    <article>
                        <h3>Open high risks</h3>
                        <p><?php echo (int) ($counts['high'] ?? 0); ?></p>
                    </article>
                    <article>
                        <h3>Open medium risks</h3>
                        <p><?php echo (int) ($counts['medium'] ?? 0); ?></p>
                    </article>
                    <article>
                        <h3>Escalated</h3>
                        <p><?php echo (int) ($counts['escalated'] ?? 0); ?></p>
                    </article>
                    <article>
                        <h3>Reviewed / resolved</h3>
                        <p><?php echo (int) ($counts['reviewed'] ?? 0); ?></p>
                    </article>
                </section>

                <nav class="cre8shield-tabs" aria-label="Cre8Shield tabs">
                    <a class="<?php echo $tabBase('high'); ?>" href="index.php?tab=high">
                        High risks <span class="cre8shield-tab-count"><?php echo (int) ($counts['high'] ?? 0); ?></span>
                    </a>
                    <a class="<?php echo $tabBase('medium'); ?>" href="index.php?tab=medium">
                        Medium risks <span class="cre8shield-tab-count"><?php echo (int) ($counts['medium'] ?? 0); ?></span>
                    </a>
                    <a class="<?php echo $tabBase('escalated'); ?>" href="index.php?tab=escalated">
                        Escalated <span class="cre8shield-tab-count"><?php echo (int) ($counts['escalated'] ?? 0); ?></span>
                    </a>
                    <a class="<?php echo $tabBase('reviewed'); ?>" href="index.php?tab=reviewed">
                        Reviewed / resolved <span class="cre8shield-tab-count"><?php echo (int) ($counts['reviewed'] ?? 0); ?></span>
                    </a>
                </nav>

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
                            $catchId = (int) ($row['id_catch'] ?? 0);
                            $detailsId = 'cre8shieldDetails' . $catchId;
                            ?>
                            <article class="cre8shield-card risk-<?php echo htmlspecialchars($level); ?><?php echo $isEscalated ? ' is-escalated' : ''; ?>">
                                <?php if ($isEscalated): ?>
                                    <div class="cre8shield-escalated-banner" aria-label="Escalated catch">
                                        <span class="cre8shield-escalated-dot"></span>
                                        ESCALATED · priority review
                                    </div>
                                <?php endif; ?>

                                <div class="cre8shield-card-head">
                                    <div class="cre8shield-card-head-left">
                                        <span class="cre8shield-pill risk-<?php echo htmlspecialchars($level); ?>">
                                            <?php echo htmlspecialchars(ucfirst($level)); ?>
                                        </span>
                                        <span class="cre8shield-score">Score <strong><?php echo $score; ?></strong>/100</span>
                                    </div>
                                    <span class="cre8shield-pill status-<?php echo htmlspecialchars($status); ?>">
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
                                        <dd><?php echo htmlspecialchars($reporterLabel); ?></dd>
                                    </div>
                                    <div>
                                        <dt>Reported</dt>
                                        <dd><?php echo htmlspecialchars($reportedLabel); ?></dd>
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
                                            <div><strong>Reporter:</strong> <?php echo htmlspecialchars($reporterLabel); ?></div>
                                            <div><strong>Reported:</strong> <?php echo htmlspecialchars($reportedLabel); ?></div>
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
                                    <?php if ($status === 'open' || $status === 'escalated'): ?>
                                        <form method="post" action="index.php">
                                            <input type="hidden" name="cre8shieldAction" value="mark_reviewed">
                                            <input type="hidden" name="catchId" value="<?php echo $catchId; ?>">
                                            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
                                            <button type="submit" class="cre8shield-action-btn is-primary">Mark reviewed</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($status !== 'ignored'): ?>
                                        <form method="post" action="index.php">
                                            <input type="hidden" name="cre8shieldAction" value="ignore">
                                            <input type="hidden" name="catchId" value="<?php echo $catchId; ?>">
                                            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
                                            <button type="submit" class="cre8shield-action-btn is-muted">Ignore</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($status !== 'escalated'): ?>
                                        <form method="post" action="index.php">
                                            <input type="hidden" name="cre8shieldAction" value="escalate">
                                            <input type="hidden" name="catchId" value="<?php echo $catchId; ?>">
                                            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
                                            <button type="submit" class="cre8shield-action-btn is-warning">Escalate</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($status !== 'resolved'): ?>
                                        <form method="post" action="index.php">
                                            <input type="hidden" name="cre8shieldAction" value="resolve">
                                            <input type="hidden" name="catchId" value="<?php echo $catchId; ?>">
                                            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
                                            <button type="submit" class="cre8shield-action-btn is-success">Resolve</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
