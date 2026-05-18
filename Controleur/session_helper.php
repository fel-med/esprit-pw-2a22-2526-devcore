<?php
/**
 * Shared authentication/session helpers for the project.
 * Use these helpers instead of hardcoding user id = 1.
 */

if (!function_exists('cc_start_session')) {
    function cc_start_session(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}

if (!function_exists('cc_current_user_id')) {
    function cc_current_user_id(): ?int
    {
        cc_start_session();
        $id = $_SESSION['id']
            ?? ($_SESSION['user']['id'] ?? null)
            ?? ($_SESSION['utilisateur']['id'] ?? null);

        if (!is_numeric($id) || (int)$id <= 0) {
            return null;
        }

        return (int)$id;
    }
}

if (!function_exists('cc_current_user_role')) {
    function cc_current_user_role(): string
    {
        cc_start_session();
        $role = $_SESSION['role'] ?? '';
        if ($role === '' && isset($_SESSION['user']) && is_array($_SESSION['user'])) {
            $role = $_SESSION['user']['role'] ?? '';
        }
        if ($role === '' && isset($_SESSION['utilisateur']) && is_array($_SESSION['utilisateur'])) {
            $role = $_SESSION['utilisateur']['role'] ?? '';
        }
        return strtolower(trim((string)$role));
    }
}

if (!function_exists('isBackOfficeRole')) {
    function isBackOfficeRole($role): bool
    {
        return cc_is_backoffice_role($role);
    }
}

if (!function_exists('cc_admin_role_power')) {
    function cc_admin_role_power($role): int
    {
        return match (strtolower(trim((string)$role))) {
            'hyper_admin' => 3,
            'super_admin' => 2,
            'admin' => 1,
            default => 0,
        };
    }
}

if (!function_exists('cc_is_backoffice_role')) {
    function cc_is_backoffice_role($role): bool
    {
        return cc_admin_role_power($role) > 0;
    }
}

if (!function_exists('cc_can_manage_user_role')) {
    function cc_can_manage_user_role($actorRole, $targetRole, $action): bool
    {
        $actorRole = strtolower(trim((string)$actorRole));
        $targetRole = strtolower(trim((string)$targetRole));
        $action = strtolower(trim((string)$action));

        if (!in_array($action, ['suspend', 'reactivate', 'delete', 'edit_role'], true)) {
            return false;
        }

        if ($targetRole === 'hyper_admin' || !cc_is_backoffice_role($actorRole)) {
            return false;
        }

        if ($actorRole === 'admin') {
            return in_array($targetRole, ['createur', 'marque'], true)
                && in_array($action, ['suspend', 'reactivate', 'delete'], true);
        }

        if ($actorRole === 'super_admin') {
            if ($action === 'delete') {
                return in_array($targetRole, ['createur', 'marque'], true);
            }

            return in_array($targetRole, ['createur', 'marque', 'admin'], true);
        }

        if ($actorRole === 'hyper_admin') {
            return in_array($targetRole, ['createur', 'marque', 'admin', 'super_admin'], true);
        }

        return false;
    }
}

if (!function_exists('cc_can_manage_user')) {
    function cc_can_manage_user($actorId, $actorRole, array $targetUser, $action): bool
    {
        $actorId = is_numeric($actorId) ? (int)$actorId : 0;
        $targetId = $targetUser['id'] ?? null;
        $targetRole = $targetUser['role'] ?? '';

        if ($actorId <= 0 || !is_numeric($targetId) || (int)$targetId <= 0) {
            return false;
        }

        if ((int)$targetId === $actorId) {
            return false;
        }

        // Suspension ownership is enforced separately by cc_can_reactivate_suspension().
        return cc_can_manage_user_role($actorRole, $targetRole, $action);
    }
}

if (!function_exists('cc_can_reactivate_suspension')) {
    function cc_can_reactivate_suspension($actorId, $actorRole, array $targetUser): bool
    {
        if (!cc_can_manage_user($actorId, $actorRole, $targetUser, 'reactivate')) {
            return false;
        }

        $actorId = is_numeric($actorId) ? (int)$actorId : 0;
        $actorRole = strtolower(trim((string)$actorRole));
        $targetRole = strtolower(trim((string)($targetUser['role'] ?? '')));
        $targetStatus = strtolower(trim((string)($targetUser['statut'] ?? '')));
        $suspendedBy = $targetUser['suspended_by'] ?? null;
        $suspendedByRole = strtolower(trim((string)($targetUser['suspended_by_role'] ?? '')));

        if ($targetStatus !== 'suspendu' || $targetRole === 'hyper_admin') {
            return false;
        }

        if ($suspendedByRole === 'hyper_admin') {
            return $actorRole === 'hyper_admin';
        }

        if ($suspendedByRole === 'super_admin') {
            return $actorRole === 'hyper_admin'
                || ($actorRole === 'super_admin' && is_numeric($suspendedBy) && (int)$suspendedBy === $actorId);
        }

        if ($suspendedByRole === 'admin') {
            return $actorRole === 'hyper_admin'
                || $actorRole === 'super_admin'
                || ($actorRole === 'admin' && is_numeric($suspendedBy) && (int)$suspendedBy === $actorId);
        }

        if ($suspendedByRole === '') {
            if ($actorRole === 'hyper_admin' || $actorRole === 'super_admin') {
                return true;
            }

            return $actorRole === 'admin' && in_array($targetRole, ['createur', 'marque'], true);
        }

        return false;
    }
}

if (!function_exists('cc_can_view_reclamation_from_role')) {
    function cc_can_view_reclamation_from_role($viewerRole, $complainantRole): bool
    {
        $viewerRole = strtolower(trim((string)$viewerRole));
        $complainantRole = strtolower(trim((string)$complainantRole));

        if (in_array($complainantRole, ['createur', 'marque'], true)) {
            return in_array($viewerRole, ['admin', 'super_admin', 'hyper_admin'], true);
        }

        if ($complainantRole === 'admin') {
            return in_array($viewerRole, ['super_admin', 'hyper_admin'], true);
        }

        if ($complainantRole === 'super_admin') {
            return $viewerRole === 'hyper_admin';
        }

        if ($complainantRole === 'hyper_admin') {
            return $viewerRole === 'hyper_admin';
        }

        return false;
    }
}

if (!function_exists('cc_is_suspended_appeal_session')) {
    function cc_is_suspended_appeal_session(): bool
    {
        cc_start_session();
        $appealUser = $_SESSION['suspended_appeal'] ?? null;

        return is_array($appealUser)
            && strtolower(trim((string)($appealUser['statut'] ?? ''))) === 'suspendu'
            && isset($appealUser['id'])
            && is_numeric($appealUser['id'])
            && (int)$appealUser['id'] > 0;
    }
}

if (!function_exists('cc_suspended_appeal_user')) {
    function cc_suspended_appeal_user(): ?array
    {
        return cc_is_suspended_appeal_session() ? $_SESSION['suspended_appeal'] : null;
    }
}

if (!function_exists('cc_current_reclamation_user_id')) {
    function cc_current_reclamation_user_id(): ?int
    {
        if (cc_is_suspended_appeal_session()) {
            return (int)$_SESSION['suspended_appeal']['id'];
        }

        return cc_current_user_id();
    }
}

if (!function_exists('cc_current_reclamation_user_role')) {
    function cc_current_reclamation_user_role(): ?string
    {
        if (cc_is_suspended_appeal_session()) {
            return strtolower(trim((string)($_SESSION['suspended_appeal']['role'] ?? '')));
        }

        $role = cc_current_user_role();
        return $role !== '' ? $role : null;
    }
}

if (!function_exists('cc_project_base_url')) {
    function cc_project_base_url(): string
    {
        $script = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');

        foreach (['/Vue/FrontOffice/', '/Vue/BackOffice/', '/Controleur/'] as $marker) {
            $pos = strpos($script, $marker);
            if ($pos !== false) {
                return substr($script, 0, $pos);
            }
        }

        return '';
    }
}

if (!function_exists('cc_app_url')) {
    function cc_app_url(string $path): string
    {
        return rtrim(cc_project_base_url(), '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('cc_clear_normal_auth_session')) {
    function cc_clear_normal_auth_session(bool $clearAppeal = false): void
    {
        unset(
            $_SESSION['connected'],
            $_SESSION['id'],
            $_SESSION['nom'],
            $_SESSION['email'],
            $_SESSION['role'],
            $_SESSION['user'],
            $_SESSION['utilisateur']
        );

        if ($clearAppeal) {
            unset($_SESSION['suspended_appeal']);
        }
    }
}

if (!function_exists('cc_fetch_session_user')) {
    function cc_fetch_session_user(int $userId): ?array
    {
        if ($userId <= 0) {
            return null;
        }

        require_once __DIR__ . '/../config.php';

        try {
            $db = config::getConnexion();
            $stmt = $db->prepare("
                SELECT id, nom, email, role, statut
                FROM utilisateur
                WHERE id = ?
                LIMIT 1
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            return $user ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('cc_enforce_active_normal_session')) {
    function cc_enforce_active_normal_session(string $loginRedirectPath = '../utilisateur/login.php'): void
    {
        cc_start_session();

        if (cc_is_suspended_appeal_session()) {
            return;
        }

        $sessionId = $_SESSION['id']
            ?? ($_SESSION['user']['id'] ?? null)
            ?? ($_SESSION['utilisateur']['id'] ?? null);

        if (!is_numeric($sessionId) || (int)$sessionId <= 0) {
            return;
        }

        $user = cc_fetch_session_user((int)$sessionId);
        if (!$user) {
            cc_clear_normal_auth_session(true);
            header('Location: ' . $loginRedirectPath);
            exit;
        }

        $status = strtolower(trim((string)($user['statut'] ?? '')));
        if ($status === 'actif') {
            return;
        }

        if ($status === 'suspendu') {
            cc_clear_normal_auth_session(false);
            $_SESSION['suspended_appeal'] = [
                'id' => (int)$user['id'],
                'role' => strtolower(trim((string)($user['role'] ?? ''))),
                'nom' => $user['nom'] ?? '',
                'email' => $user['email'] ?? '',
                'statut' => 'suspendu',
            ];

            header('Location: ' . cc_app_url('Vue/FrontOffice/utilisateur/reclamation.php?appeal=1'));
            exit;
        }

        cc_clear_normal_auth_session(true);
        header('Location: ' . $loginRedirectPath . (str_contains($loginRedirectPath, '?') ? '&' : '?') . 'error=account_inactive');
        exit;
    }
}

if (!function_exists('isSuperAdminRole')) {
    function isSuperAdminRole($role): bool
    {
        return in_array(strtolower(trim((string)$role)), ['super_admin', 'hyper_admin'], true);
    }
}

if (!function_exists('isHyperAdmin')) {
    function isHyperAdmin($role): bool
    {
        return strtolower(trim((string)$role)) === 'hyper_admin';
    }
}

if (!function_exists('cc_is_logged_in')) {
    function cc_is_logged_in(): bool
    {
        cc_start_session();
        return cc_current_user_id() !== null;
    }
}

if (!function_exists('cc_is_admin')) {
    function cc_is_admin(): bool
    {
        return cc_is_logged_in() && isBackOfficeRole(cc_current_user_role());
    }
}

if (!function_exists('cc_is_ajax_request')) {
    function cc_is_ajax_request(): bool
    {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
    }
}

if (!function_exists('cc_json_response')) {
    function cc_json_response(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

if (!function_exists('cc_require_login')) {
    function cc_require_login(string $redirectPath = '../utilisateur/login.php', bool $jsonOnAjax = true): int
    {
        cc_start_session();

        $userId = cc_current_user_id();
        if ($userId !== null) {
            cc_enforce_active_normal_session($redirectPath);
            $userId = cc_current_user_id();
            if ($userId === null) {
                header('Location: ' . $redirectPath);
                exit;
            }
            // Normalise older sessions created before this integration.
            $_SESSION['connected'] = true;
            return $userId;
        }

        if ($jsonOnAjax && cc_is_ajax_request()) {
            cc_json_response([
                'success' => false,
                'message' => 'Vous devez être connecté pour effectuer cette action.',
            ], 401);
        }

        header('Location: ' . $redirectPath);
        exit;
    }
}

if (!function_exists('cc_require_admin')) {
    function cc_require_admin(string $redirectPath = '../../FrontOffice/utilisateur/login.php'): int
    {
        $userId = cc_require_login($redirectPath, false);
        if (!cc_is_admin()) {
            http_response_code(403);
            die('Access denied. BackOffice account required.');
        }
        return $userId;
    }
}
