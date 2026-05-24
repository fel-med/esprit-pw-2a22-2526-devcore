<?php
if (!function_exists('cre8_front_start_session')) {
    function cre8_front_start_session(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}

if (!function_exists('cre8_front_normalize_role')) {
    function cre8_front_normalize_role($role): string
    {
        $role = strtolower(trim((string) $role));
        $role = str_replace(
            ['é', 'è', 'ê', 'ë', 'à', 'â', 'î', 'ï', 'ô', 'ù', 'û', 'ç'],
            ['e', 'e', 'e', 'e', 'a', 'a', 'i', 'i', 'o', 'u', 'u', 'c'],
            $role
        );

        return match ($role) {
            'brand', 'brands', 'marque' => 'marque',
            'creator', 'creators', 'createur', 'creatrice' => 'createur',
            'admin', 'administrator', 'administrateur' => 'admin',
            'super_admin' => 'super_admin',
            'hyper_admin' => 'hyper_admin',
            default => $role,
        };
    }
}

if (!function_exists('cre8_front_is_admin_role')) {
    function cre8_front_is_admin_role($role): bool
    {
        return in_array(cre8_front_normalize_role($role), ['admin', 'super_admin', 'hyper_admin'], true);
    }
}

if (!function_exists('cre8_front_is_admin_visitor')) {
    function cre8_front_is_admin_visitor($userOrRole = null): bool
    {
        $role = is_array($userOrRole)
            ? ($userOrRole['role'] ?? '')
            : ($userOrRole ?? ($_SESSION['role'] ?? ($_SESSION['user']['role'] ?? ($_SESSION['utilisateur']['role'] ?? ''))));

        return cre8_front_is_admin_role($role);
    }
}

if (!function_exists('cre8_front_permission_mode')) {
    function cre8_front_permission_mode($userOrRole = null): string
    {
        return cre8_front_is_admin_visitor($userOrRole)
            ? 'admin_visitor'
            : cre8_front_normalize_role(is_array($userOrRole) ? ($userOrRole['role'] ?? '') : (string) $userOrRole);
    }
}

if (!function_exists('cre8_front_login_url')) {
    function cre8_front_login_url(): string
    {
        $script = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');
        $marker = '/Vue/FrontOffice/';
        $pos = strpos($script, $marker);
        $base = $pos !== false ? substr($script, 0, $pos) : '';

        return $base . '/Vue/FrontOffice/utilisateur/login.php';
    }
}

if (!function_exists('cre8_front_is_ajax_request')) {
    function cre8_front_is_ajax_request(): bool
    {
        return strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest'
            || (isset($_REQUEST['ajax']) && (string) $_REQUEST['ajax'] === '1');
    }
}

if (!function_exists('cre8_front_session_user')) {
    function cre8_front_session_user(): array
    {
        cre8_front_start_session();

        $realUser = isset($_SESSION['user']) && is_array($_SESSION['user'])
            ? $_SESSION['user']
            : [];
        $legacyUser = isset($_SESSION['utilisateur']) && is_array($_SESSION['utilisateur'])
            ? $_SESSION['utilisateur']
            : [];

        /*
         * The real utilisateur login must win over the old offre demo selector.
         * Old demo data in $_SESSION['utilisateur'] can contain only id + role and
         * can make a creator look like a brand. Keep it only as a fallback.
         */
        $id = $realUser['id']
            ?? $_SESSION['id']
            ?? $_SESSION['user_id']
            ?? $legacyUser['id']
            ?? null;

        $role = $realUser['role']
            ?? $_SESSION['role']
            ?? $legacyUser['role']
            ?? '';

        $name = $realUser['nom']
            ?? $_SESSION['nom']
            ?? $legacyUser['nom']
            ?? $realUser['name']
            ?? $legacyUser['name']
            ?? '';

        $email = $realUser['email']
            ?? $_SESSION['email']
            ?? $legacyUser['email']
            ?? '';

        $id = is_numeric($id) ? (int) $id : 0;
        $role = cre8_front_normalize_role($role);
        $name = trim((string) $name);
        $email = trim((string) $email);

        if ($id <= 0 || $role === '') {
            return [
                'id' => 0,
                'role' => '',
                'nom' => '',
                'email' => '',
                'isLoggedIn' => false,
            ];
        }

        $user = [
            'id' => $id,
            'role' => $role,
            'nom' => $name !== '' ? $name : 'Utilisateur',
            'email' => $email,
            'isLoggedIn' => true,
        ];

        // Synchronize the legacy key for older module code, but never let it override real login data.
        $_SESSION['utilisateur'] = $user;
        $_SESSION['id'] = $id;
        $_SESSION['role'] = $role;
        $_SESSION['nom'] = $user['nom'];
        $_SESSION['connected'] = true;

        return $user;
    }
}

if (!function_exists('cre8_front_require_user')) {
    function cre8_front_require_user(?string $requiredRole = null): array
    {
        $user = cre8_front_session_user();
        $userId = isset($user['id']) && is_numeric($user['id']) ? (int) $user['id'] : 0;

        if ($userId <= 0 || empty($user['isLoggedIn'])) {
            if (cre8_front_is_ajax_request()) {
                http_response_code(401);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'message' => 'You must be logged in to access this page.',
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }

            header('Location: ' . cre8_front_login_url());
            exit;
        }

        if ($requiredRole !== null) {
            $requiredRole = cre8_front_normalize_role($requiredRole);
            $currentRole = cre8_front_normalize_role($user['role'] ?? '');

            if ($currentRole !== $requiredRole) {
                http_response_code(403);
                die('Access denied for this workspace.');
            }
        }

        return $user;
    }
}
