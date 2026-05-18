<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../layout/early-theme.php';
require_once __DIR__ . '/../../../Controleur/session_helper.php';
require_once __DIR__ . '/../../../Controleur/adminRequestC.php';
require_once __DIR__ . '/../../../Controleur/adminAuditC.php';

$currentUserId = cc_require_admin('../../FrontOffice/utilisateur/login.php');
$currentRole = cc_current_user_role();
$backActive = 'admin_requests';
$requestC = new AdminRequestC();

$requestTypeLabels = [
    'reactivate_account' => 'Reactivate account',
    'add_admin' => 'Add admin',
    'add_super_admin' => 'Add super admin',
    'delete_user' => 'Delete user',
    'security_review' => 'Security review',
    'server_action' => 'Server action',
    'other' => 'Other',
];

$receiverLabels = [
    'super_admins' => 'Super admins',
    'hyper_admins' => 'Hyper admins',
];


function cre8_admin_request_type_key(string $type): string
{
    return 'adminRequests.type.' . preg_replace('/[^a-z0-9_]/', '', strtolower($type));
}

function cre8_admin_request_receiver_key(string $scope): string
{
    return 'adminRequests.receiver.' . preg_replace('/[^a-z0-9_]/', '', strtolower($scope));
}

function cre8_admin_request_status_key(string $status): string
{
    return 'adminRequests.status.' . preg_replace('/[^a-z0-9_]/', '', strtolower($status));
}

function cre8_admin_request_flash_key(string $message): ?string
{
    return match ($message) {
        'Request created successfully.' => 'adminRequests.flash.created',
        'Unable to create request. Please try again.' => 'adminRequests.flash.createFailed',
        'You are not allowed to handle this request.' => 'adminRequests.flash.handleDenied',
        'Invalid request status.' => 'adminRequests.flash.invalidStatus',
        'Request status updated.' => 'adminRequests.flash.statusUpdated',
        'This request is no longer pending.' => 'adminRequests.flash.notPending',
        'Unable to update request. Please try again.' => 'adminRequests.flash.updateFailed',
        'You are not allowed to cancel this request.' => 'adminRequests.flash.cancelDenied',
        'Request cancelled.' => 'adminRequests.flash.cancelled',
        'Unable to cancel request. Please try again.' => 'adminRequests.flash.cancelFailed',
        default => null,
    };
}

function cre8_admin_request_flash(string $type, string $message): void
{
    $_SESSION['admin_request_flash'] = [
        'type' => $type === 'success' ? 'success' : 'danger',
        'message' => $message,
    ];
}

function cre8_admin_request_redirect(): void
{
    header('Location: admin_requests.php');
    exit;
}

function cre8_admin_request_audit_reason(array $request, string $status, string $response = ''): string
{
    $reason = sprintf(
        'Request #%d (%s): %s',
        (int)($request['id'] ?? 0),
        (string)($request['request_type'] ?? 'other'),
        (string)($request['title'] ?? '')
    );

    if ($response !== '') {
        $reason .= ' | Response: ' . $response;
    }

    return $reason . ' | Status: ' . $status;
}

function cre8_admin_request_status_badge(string $status): string
{
    return match ($status) {
        'approved' => 'success',
        'refused' => 'danger',
        'done' => 'info',
        'cancelled' => 'secondary',
        default => 'warning',
    };
}

$createReceiverOptions = [];
if ($currentRole === 'admin') {
    $createReceiverOptions = ['super_admins', 'hyper_admins'];
} elseif ($currentRole === 'super_admin') {
    $createReceiverOptions = ['hyper_admins'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = strtolower(trim((string)($_POST['action'] ?? '')));

    if ($action === 'create_request') {
        $receiverScope = strtolower(trim((string)($_POST['receiver_scope'] ?? '')));
        $requestType = strtolower(trim((string)($_POST['request_type'] ?? '')));
        $targetUserId = trim((string)($_POST['target_user_id'] ?? ''));
        $title = trim((string)($_POST['title'] ?? ''));
        $message = trim((string)($_POST['message'] ?? ''));

        try {
            if (!in_array($receiverScope, $createReceiverOptions, true) || !cc_can_create_admin_request($currentRole, $receiverScope)) {
                throw new RuntimeException('You are not allowed to send to this receiver.');
            }
            if (!array_key_exists($requestType, $requestTypeLabels)) {
                throw new RuntimeException('Invalid request type.');
            }
            if ($targetUserId !== '' && !ctype_digit($targetUserId)) {
                throw new RuntimeException('Target user ID must be numeric.');
            }
            if ($title === '' || $message === '') {
                throw new RuntimeException('Title and message are required.');
            }

            $requestId = $requestC->createRequest(
                $currentUserId,
                $currentRole,
                $receiverScope,
                null,
                $requestType,
                $targetUserId !== '' ? (int)$targetUserId : null,
                $title,
                $message
            );

            cc_log_admin_action(
                $currentUserId,
                $currentRole,
                'create_admin_request',
                $targetUserId !== '' ? (int)$targetUserId : null,
                null,
                null,
                'pending',
                sprintf('Request #%d (%s): %s', $requestId, $requestType, $title)
            );
            cre8_admin_request_flash('success', 'Request created successfully.');
        } catch (Throwable $e) {
            error_log('Admin request create failed: ' . $e->getMessage());
            cre8_admin_request_flash('danger', 'Unable to create request. Please try again.');
        }

        cre8_admin_request_redirect();
    }

    if ($action === 'handle_request') {
        $requestId = (int)($_POST['request_id'] ?? 0);
        $newStatus = strtolower(trim((string)($_POST['new_status'] ?? '')));
        $responseMessage = trim((string)($_POST['response_message'] ?? ''));
        $request = $requestC->getRequestById($requestId);

        if (!$request || !cc_can_handle_admin_request($currentUserId, $currentRole, $request)) {
            cre8_admin_request_flash('danger', 'You are not allowed to handle this request.');
            cre8_admin_request_redirect();
        }

        if (!in_array($newStatus, ['approved', 'refused', 'done'], true)) {
            cre8_admin_request_flash('danger', 'Invalid request status.');
            cre8_admin_request_redirect();
        }

        try {
            $updated = $requestC->updateRequestStatus($requestId, $newStatus, $responseMessage, $currentUserId, $currentRole);
            if ($updated) {
                $auditAction = match ($newStatus) {
                    'approved' => 'approve_admin_request',
                    'refused' => 'refuse_admin_request',
                    default => 'complete_admin_request',
                };
                cc_log_admin_action(
                    $currentUserId,
                    $currentRole,
                    $auditAction,
                    $request['target_user_id'] ?? null,
                    null,
                    $request['status'] ?? null,
                    $newStatus,
                    cre8_admin_request_audit_reason($request, $newStatus, $responseMessage)
                );
                cre8_admin_request_flash('success', 'Request status updated.');
            } else {
                cre8_admin_request_flash('danger', 'This request is no longer pending.');
            }
        } catch (Throwable $e) {
            error_log('Admin request update failed: ' . $e->getMessage());
            cre8_admin_request_flash('danger', 'Unable to update request. Please try again.');
        }

        cre8_admin_request_redirect();
    }

    if ($action === 'cancel_request') {
        $requestId = (int)($_POST['request_id'] ?? 0);
        $request = $requestC->getRequestById($requestId);

        if (!$request || (int)($request['sender_id'] ?? 0) !== $currentUserId || ($request['status'] ?? '') !== 'pending') {
            cre8_admin_request_flash('danger', 'You are not allowed to cancel this request.');
            cre8_admin_request_redirect();
        }

        try {
            if ($requestC->cancelRequest($requestId, $currentUserId)) {
                cc_log_admin_action(
                    $currentUserId,
                    $currentRole,
                    'cancel_admin_request',
                    $request['target_user_id'] ?? null,
                    null,
                    $request['status'] ?? null,
                    'cancelled',
                    cre8_admin_request_audit_reason($request, 'cancelled')
                );
                cre8_admin_request_flash('success', 'Request cancelled.');
            } else {
                cre8_admin_request_flash('danger', 'Unable to cancel request. Please try again.');
            }
        } catch (Throwable $e) {
            error_log('Admin request cancel failed: ' . $e->getMessage());
            cre8_admin_request_flash('danger', 'Unable to cancel request. Please try again.');
        }

        cre8_admin_request_redirect();
    }
}

$inboxRequests = $requestC->listVisibleRequests($currentUserId, $currentRole, 'inbox');
$sentRequests = $requestC->listVisibleRequests($currentUserId, $currentRole, 'sent');
$allRequests = $currentRole === 'hyper_admin' ? $requestC->listVisibleRequests($currentUserId, $currentRole, 'all') : [];
$flash = $_SESSION['admin_request_flash'] ?? null;
unset($_SESSION['admin_request_flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php cre8_bo_early_theme_print_head_script(); ?>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Admin Requests - Cre8connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="../layout/back-layout.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../layout/back-layout.css')); ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="../../public/images/favicon-16.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../../public/images/favicon-32.png">
    <style>
        .request-card { border-radius: 14px; border: 1px solid rgba(255,255,255,.08); }
        .request-table td, .request-table th { vertical-align: middle; }
        .request-message { max-width: 320px; white-space: normal; }
        .request-actions form { display: inline-flex; gap: .4rem; align-items: center; flex-wrap: wrap; }
        .request-actions textarea { min-width: 220px; min-height: 38px; }
        body.light-mode .request-card { background: #fff; color: #111827; border-color: #e5e7eb; }
    </style>
</head>
<body class="cre8-admin-layout"><?php cre8_bo_early_theme_print_body_script(); ?>
<div class="container-scroller cre8-admin-page">
    <?php require_once __DIR__ . '/../layout/sidebar.php'; ?>
    <div class="container-fluid page-body-wrapper cre8-admin-main">
        <?php require_once __DIR__ . '/../layout/header.php'; ?>
        <div class="main-panel">
            <div class="content-wrapper">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                    <div>
                        <h2 class="mb-1" data-i18n="adminRequests.title">Admin Requests</h2>
                        <p class="text-muted mb-0" data-i18n="adminRequests.subtitle">Send and handle internal admin-level requests without executing account or server actions automatically.</p>
                    </div>
                </div>

                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?> alert-dismissible fade show" role="alert">
                        <?php $flashKey = cre8_admin_request_flash_key((string)($flash['message'] ?? '')); ?>
                        <?php if ($flashKey): ?>
                            <span data-i18n="<?php echo htmlspecialchars($flashKey); ?>"><?php echo htmlspecialchars($flash['message']); ?></span>
                        <?php else: ?>
                            <?php echo htmlspecialchars($flash['message']); ?>
                        <?php endif; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="row g-4 mb-4">
                    <div class="col-lg-5">
                        <div class="card request-card h-100">
                            <div class="card-body">
                                <h5 class="card-title" data-i18n="adminRequests.create.title">Create request</h5>
                                <?php if (empty($createReceiverOptions)): ?>
                                    <p class="text-muted mb-0" data-i18n="adminRequests.create.noReceiver">No higher admin level is available for your account.</p>
                                <?php else: ?>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="create_request">
                                        <div class="mb-3">
                                            <label class="form-label" data-i18n="adminRequests.form.receiver">Receiver</label>
                                            <select name="receiver_scope" class="form-select" required>
                                                <?php foreach ($createReceiverOptions as $scope): ?>
                                                    <option value="<?php echo htmlspecialchars($scope); ?>" data-i18n-opt="<?php echo htmlspecialchars(cre8_admin_request_receiver_key($scope)); ?>"><?php echo htmlspecialchars($receiverLabels[$scope] ?? $scope); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label" data-i18n="adminRequests.form.requestType">Request type</label>
                                            <select name="request_type" class="form-select" required>
                                                <?php foreach ($requestTypeLabels as $type => $label): ?>
                                                    <option value="<?php echo htmlspecialchars($type); ?>" data-i18n-opt="<?php echo htmlspecialchars(cre8_admin_request_type_key($type)); ?>"><?php echo htmlspecialchars($label); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label"><span data-i18n="adminRequests.form.targetUserId">Target user ID</span> <span class="text-muted" data-i18n="common.optional">(optional)</span></label>
                                            <input type="number" min="1" name="target_user_id" class="form-control" placeholder="Example: 42" data-i18n-placeholder="adminRequests.form.targetPlaceholder">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label" data-i18n="adminRequests.form.title">Title</label>
                                            <input type="text" name="title" class="form-control" maxlength="255" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label" data-i18n="adminRequests.form.message">Message</label>
                                            <textarea name="message" class="form-control" rows="5" required></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-primary" data-i18n="adminRequests.action.send">Send request</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-7">
                        <div class="card request-card h-100">
                            <div class="card-body">
                                <h5 class="card-title" data-i18n="adminRequests.inbox.title">Inbox</h5>
                                <?php if (empty($inboxRequests)): ?>
                                    <p class="text-muted mb-0" data-i18n="adminRequests.inbox.empty">No incoming requests.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover request-table">
                                            <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th data-i18n="adminRequests.table.sender">Sender</th>
                                                <th data-i18n="adminRequests.table.type">Type</th>
                                                <th data-i18n="adminRequests.table.target">Target</th>
                                                <th data-i18n="adminRequests.table.title">Title</th>
                                                <th data-i18n="common.status">Status</th>
                                                <th data-i18n="adminRequests.table.created">Created</th>
                                                <th data-i18n="common.actions">Actions</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <?php foreach ($inboxRequests as $request): ?>
                                                <tr>
                                                    <td>#<?php echo (int)$request['id']; ?></td>
                                                    <td><?php echo htmlspecialchars($request['sender_role']); ?></td>
                                                    <td><span data-i18n="<?php echo htmlspecialchars(cre8_admin_request_type_key((string)$request['request_type'])); ?>"><?php echo htmlspecialchars($requestTypeLabels[$request['request_type']] ?? $request['request_type']); ?></span></td>
                                                    <td><?php echo $request['target_user_id'] ? (int)$request['target_user_id'] : '-'; ?></td>
                                                    <td class="request-message">
                                                        <strong><?php echo htmlspecialchars($request['title']); ?></strong>
                                                        <div class="text-muted small"><?php echo nl2br(htmlspecialchars($request['message'])); ?></div>
                                                        <?php if (!empty($request['response_message'])): ?>
                                                            <div class="small mt-2"><strong data-i18n="adminRequests.table.response">Response:</strong> <?php echo nl2br(htmlspecialchars($request['response_message'])); ?></div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><span class="badge bg-<?php echo cre8_admin_request_status_badge($request['status']); ?>"><span data-i18n="<?php echo htmlspecialchars(cre8_admin_request_status_key((string)$request['status'])); ?>"><?php echo htmlspecialchars($request['status']); ?></span></span></td>
                                                    <td><?php echo htmlspecialchars($request['created_at'] ?? ''); ?></td>
                                                    <td class="request-actions">
                                                        <?php if (cc_can_handle_admin_request($currentUserId, $currentRole, $request)): ?>
                                                            <form method="POST">
                                                                <input type="hidden" name="action" value="handle_request">
                                                                <input type="hidden" name="request_id" value="<?php echo (int)$request['id']; ?>">
                                                                <textarea name="response_message" class="form-control form-control-sm" placeholder="Optional response" data-i18n-placeholder="adminRequests.action.optionalResponse"></textarea>
                                                                <button name="new_status" value="approved" class="btn btn-sm btn-success" type="submit" data-i18n="adminRequests.action.approve">Approve</button>
                                                                <button name="new_status" value="refused" class="btn btn-sm btn-danger" type="submit" data-i18n="adminRequests.action.refuse">Refuse</button>
                                                                <button name="new_status" value="done" class="btn btn-sm btn-info" type="submit" data-i18n="adminRequests.action.done">Done</button>
                                                            </form>
                                                        <?php else: ?>
                                                            <span class="text-muted small" data-i18n="adminRequests.action.noActions">No actions</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card request-card mb-4">
                    <div class="card-body">
                        <h5 class="card-title" data-i18n="adminRequests.sent.title">Sent requests</h5>
                        <?php if (empty($sentRequests)): ?>
                            <p class="text-muted mb-0" data-i18n="adminRequests.sent.empty">You have not sent any requests yet.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover request-table">
                                    <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th data-i18n="adminRequests.table.receiver">Receiver</th>
                                        <th data-i18n="adminRequests.table.type">Type</th>
                                        <th data-i18n="adminRequests.table.target">Target</th>
                                        <th data-i18n="adminRequests.table.title">Title</th>
                                        <th data-i18n="common.status">Status</th>
                                        <th data-i18n="adminRequests.table.handledBy">Handled by</th>
                                        <th data-i18n="adminRequests.table.created">Created</th>
                                        <th data-i18n="common.actions">Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($sentRequests as $request): ?>
                                        <tr>
                                            <td>#<?php echo (int)$request['id']; ?></td>
                                            <td><span data-i18n="<?php echo htmlspecialchars(cre8_admin_request_receiver_key((string)$request['receiver_scope'])); ?>"><?php echo htmlspecialchars($receiverLabels[$request['receiver_scope']] ?? $request['receiver_scope']); ?></span></td>
                                            <td><span data-i18n="<?php echo htmlspecialchars(cre8_admin_request_type_key((string)$request['request_type'])); ?>"><?php echo htmlspecialchars($requestTypeLabels[$request['request_type']] ?? $request['request_type']); ?></span></td>
                                            <td><?php echo $request['target_user_id'] ? (int)$request['target_user_id'] : '-'; ?></td>
                                            <td class="request-message">
                                                <strong><?php echo htmlspecialchars($request['title']); ?></strong>
                                                <div class="text-muted small"><?php echo nl2br(htmlspecialchars($request['message'])); ?></div>
                                                <?php if (!empty($request['response_message'])): ?>
                                                    <div class="small mt-2"><strong data-i18n="adminRequests.table.response">Response:</strong> <?php echo nl2br(htmlspecialchars($request['response_message'])); ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td><span class="badge bg-<?php echo cre8_admin_request_status_badge($request['status']); ?>"><span data-i18n="<?php echo htmlspecialchars(cre8_admin_request_status_key((string)$request['status'])); ?>"><?php echo htmlspecialchars($request['status']); ?></span></span></td>
                                            <td><?php echo htmlspecialchars(trim(($request['handled_by_role'] ?? '') . ' #' . ($request['handled_by'] ?? ''), ' #')); ?><br><span class="text-muted small"><?php echo htmlspecialchars($request['handled_at'] ?? ''); ?></span></td>
                                            <td><?php echo htmlspecialchars($request['created_at'] ?? ''); ?></td>
                                            <td>
                                                <?php if (($request['status'] ?? '') === 'pending' && (int)$request['sender_id'] === $currentUserId): ?>
                                                    <form method="POST" onsubmit="return confirm(window.cre8BackText ? window.cre8BackText('adminRequests.confirm.cancel') : 'Cancel this request?');">
                                                        <input type="hidden" name="action" value="cancel_request">
                                                        <input type="hidden" name="request_id" value="<?php echo (int)$request['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-secondary" data-i18n="common.cancel">Cancel</button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="text-muted small">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($currentRole === 'hyper_admin'): ?>
                    <div class="card request-card">
                        <div class="card-body">
                            <h5 class="card-title" data-i18n="adminRequests.all.title">All requests</h5>
                            <div class="table-responsive">
                                <table class="table table-hover request-table">
                                    <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th data-i18n="adminRequests.table.sender">Sender</th>
                                        <th data-i18n="adminRequests.table.receiver">Receiver</th>
                                        <th data-i18n="adminRequests.table.type">Type</th>
                                        <th data-i18n="common.status">Status</th>
                                        <th data-i18n="adminRequests.table.title">Title</th>
                                        <th data-i18n="adminRequests.table.created">Created</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($allRequests as $request): ?>
                                        <tr>
                                            <td>#<?php echo (int)$request['id']; ?></td>
                                            <td><?php echo htmlspecialchars($request['sender_role']); ?> #<?php echo (int)$request['sender_id']; ?></td>
                                            <td><span data-i18n="<?php echo htmlspecialchars(cre8_admin_request_receiver_key((string)$request['receiver_scope'])); ?>"><?php echo htmlspecialchars($receiverLabels[$request['receiver_scope']] ?? $request['receiver_scope']); ?></span></td>
                                            <td><span data-i18n="<?php echo htmlspecialchars(cre8_admin_request_type_key((string)$request['request_type'])); ?>"><?php echo htmlspecialchars($requestTypeLabels[$request['request_type']] ?? $request['request_type']); ?></span></td>
                                            <td><span class="badge bg-<?php echo cre8_admin_request_status_badge($request['status']); ?>"><span data-i18n="<?php echo htmlspecialchars(cre8_admin_request_status_key((string)$request['status'])); ?>"><?php echo htmlspecialchars($request['status']); ?></span></span></td>
                                            <td><?php echo htmlspecialchars($request['title']); ?></td>
                                            <td><?php echo htmlspecialchars($request['created_at'] ?? ''); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
window.cre8BackRegisterTranslations && window.cre8BackRegisterTranslations({
  en: {
    'common.optional': '(optional)',
    'adminRequests.title': 'Admin Requests',
    'adminRequests.subtitle': 'Send and handle internal admin-level requests without executing account or server actions automatically.',
    'adminRequests.create.title': 'Create request',
    'adminRequests.create.noReceiver': 'No higher admin level is available for your account.',
    'adminRequests.form.receiver': 'Receiver',
    'adminRequests.form.requestType': 'Request type',
    'adminRequests.form.targetUserId': 'Target user ID',
    'adminRequests.form.targetPlaceholder': 'Example: 42',
    'adminRequests.form.title': 'Title',
    'adminRequests.form.message': 'Message',
    'adminRequests.action.send': 'Send request',
    'adminRequests.action.approve': 'Approve',
    'adminRequests.action.refuse': 'Refuse',
    'adminRequests.action.done': 'Done',
    'adminRequests.action.optionalResponse': 'Optional response',
    'adminRequests.action.noActions': 'No actions',
    'adminRequests.confirm.cancel': 'Cancel this request?',
    'adminRequests.inbox.title': 'Inbox',
    'adminRequests.inbox.empty': 'No incoming requests.',
    'adminRequests.sent.title': 'Sent requests',
    'adminRequests.sent.empty': 'You have not sent any requests yet.',
    'adminRequests.all.title': 'All requests',
    'adminRequests.table.sender': 'Sender',
    'adminRequests.table.receiver': 'Receiver',
    'adminRequests.table.type': 'Type',
    'adminRequests.table.target': 'Target',
    'adminRequests.table.title': 'Title',
    'adminRequests.table.created': 'Created',
    'adminRequests.table.handledBy': 'Handled by',
    'adminRequests.table.response': 'Response:',
    'adminRequests.type.reactivate_account': 'Reactivate account',
    'adminRequests.type.add_admin': 'Add admin',
    'adminRequests.type.add_super_admin': 'Add super admin',
    'adminRequests.type.delete_user': 'Delete user',
    'adminRequests.type.security_review': 'Security review',
    'adminRequests.type.server_action': 'Server action',
    'adminRequests.type.other': 'Other',
    'adminRequests.receiver.super_admins': 'Super admins',
    'adminRequests.receiver.hyper_admins': 'Hyper admins',
    'adminRequests.status.pending': 'Pending',
    'adminRequests.status.approved': 'Approved',
    'adminRequests.status.refused': 'Refused',
    'adminRequests.status.done': 'Done',
    'adminRequests.status.cancelled': 'Cancelled',
    'adminRequests.flash.created': 'Request created successfully.',
    'adminRequests.flash.createFailed': 'Unable to create request. Please try again.',
    'adminRequests.flash.handleDenied': 'You are not allowed to handle this request.',
    'adminRequests.flash.invalidStatus': 'Invalid request status.',
    'adminRequests.flash.statusUpdated': 'Request status updated.',
    'adminRequests.flash.notPending': 'This request is no longer pending.',
    'adminRequests.flash.updateFailed': 'Unable to update request. Please try again.',
    'adminRequests.flash.cancelDenied': 'You are not allowed to cancel this request.',
    'adminRequests.flash.cancelled': 'Request cancelled.',
    'adminRequests.flash.cancelFailed': 'Unable to cancel request. Please try again.'
  },
  fr: {
    'common.optional': '(optionnel)',
    'adminRequests.title': 'Demandes admin',
    'adminRequests.subtitle': 'Envoyez et traitez les demandes internes entre admins sans executer automatiquement des actions de compte ou de serveur.',
    'adminRequests.create.title': 'Creer une demande',
    'adminRequests.create.noReceiver': 'Aucun niveau admin superieur n est disponible pour votre compte.',
    'adminRequests.form.receiver': 'Destinataire',
    'adminRequests.form.requestType': 'Type de demande',
    'adminRequests.form.targetUserId': 'ID utilisateur cible',
    'adminRequests.form.targetPlaceholder': 'Exemple : 42',
    'adminRequests.form.title': 'Titre',
    'adminRequests.form.message': 'Message',
    'adminRequests.action.send': 'Envoyer la demande',
    'adminRequests.action.approve': 'Approuver',
    'adminRequests.action.refuse': 'Refuser',
    'adminRequests.action.done': 'Terminer',
    'adminRequests.action.optionalResponse': 'Reponse optionnelle',
    'adminRequests.action.noActions': 'Aucune action',
    'adminRequests.confirm.cancel': 'Annuler cette demande ?',
    'adminRequests.inbox.title': 'Boite de reception',
    'adminRequests.inbox.empty': 'Aucune demande recue.',
    'adminRequests.sent.title': 'Demandes envoyees',
    'adminRequests.sent.empty': 'Vous n avez encore envoye aucune demande.',
    'adminRequests.all.title': 'Toutes les demandes',
    'adminRequests.table.sender': 'Expediteur',
    'adminRequests.table.receiver': 'Destinataire',
    'adminRequests.table.type': 'Type',
    'adminRequests.table.target': 'Cible',
    'adminRequests.table.title': 'Titre',
    'adminRequests.table.created': 'Creee',
    'adminRequests.table.handledBy': 'Traitee par',
    'adminRequests.table.response': 'Reponse :',
    'adminRequests.type.reactivate_account': 'Reactiver un compte',
    'adminRequests.type.add_admin': 'Ajouter un admin',
    'adminRequests.type.add_super_admin': 'Ajouter un super admin',
    'adminRequests.type.delete_user': 'Supprimer un utilisateur',
    'adminRequests.type.security_review': 'Revue de securite',
    'adminRequests.type.server_action': 'Action serveur',
    'adminRequests.type.other': 'Autre',
    'adminRequests.receiver.super_admins': 'Super admins',
    'adminRequests.receiver.hyper_admins': 'Hyper admins',
    'adminRequests.status.pending': 'En attente',
    'adminRequests.status.approved': 'Approuvee',
    'adminRequests.status.refused': 'Refusee',
    'adminRequests.status.done': 'Terminee',
    'adminRequests.status.cancelled': 'Annulee',
    'adminRequests.flash.created': 'Demande creee avec succes.',
    'adminRequests.flash.createFailed': 'Impossible de creer la demande. Reessayez.',
    'adminRequests.flash.handleDenied': 'Vous n etes pas autorise a traiter cette demande.',
    'adminRequests.flash.invalidStatus': 'Statut de demande invalide.',
    'adminRequests.flash.statusUpdated': 'Statut de la demande mis a jour.',
    'adminRequests.flash.notPending': 'Cette demande n est plus en attente.',
    'adminRequests.flash.updateFailed': 'Impossible de mettre a jour la demande. Reessayez.',
    'adminRequests.flash.cancelDenied': 'Vous n etes pas autorise a annuler cette demande.',
    'adminRequests.flash.cancelled': 'Demande annulee.',
    'adminRequests.flash.cancelFailed': 'Impossible d annuler la demande. Reessayez.'
  }
});
</script>
<script src="assets/vendors/js/vendor.bundle.base.js"></script>
<script src="assets/js/off-canvas.js"></script>
<script src="assets/js/hoverable-collapse.js"></script>
<script src="assets/js/misc.js"></script>
<script src="../layout/back-layout.js?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../layout/back-layout.js')); ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
