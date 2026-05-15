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
        return in_array(strtolower(trim((string)$role)), ['admin', 'super_admin', 'hyper_admin'], true);
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
