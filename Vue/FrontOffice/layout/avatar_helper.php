<?php
require_once __DIR__ . '/../../../Controleur/profileC.php';

if (!function_exists('cre8_avatar_escape')) {
    function cre8_avatar_escape($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('cre8_user_initial')) {
    function cre8_user_initial(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return 'U';
        }

        $initial = function_exists('mb_substr')
            ? mb_substr($name, 0, 1, 'UTF-8')
            : substr($name, 0, 1);

        return function_exists('mb_strtoupper')
            ? mb_strtoupper((string) $initial, 'UTF-8')
            : strtoupper((string) $initial);
    }
}

if (!function_exists('cre8_profile_upload_web_base')) {
    function cre8_profile_upload_web_base(): string
    {
        $scriptPath = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        $projectBase = '';

        if (($pos = strpos($scriptPath, '/Vue/')) !== false) {
            $projectBase = substr($scriptPath, 0, $pos);
        } elseif (($pos = strpos($scriptPath, '/Controleur/')) !== false) {
            $projectBase = substr($scriptPath, 0, $pos);
        }

        return rtrim($projectBase, '/') . '/Vue/public/uploads/profile';
    }
}

if (!function_exists('cre8_profile_image_url')) {
    function cre8_profile_image_url($userId, ?string $webBase = null): ?string
    {
        $userId = is_numeric($userId) ? (int) $userId : 0;
        if ($userId <= 0) {
            return null;
        }

        $webBase = $webBase ?: cre8_profile_upload_web_base();
        static $profileC = null;
        static $cache = [];

        $cacheKey = $userId . '|' . $webBase;
        if (array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }

        try {
            if (!$profileC instanceof ProfileC) {
                $profileC = new ProfileC();
            }
            $cache[$cacheKey] = $profileC->getProfileImageUrl($userId, $webBase);
        } catch (Throwable $e) {
            $cache[$cacheKey] = null;
        }

        return $cache[$cacheKey];
    }
}

if (!function_exists('cre8_avatar_inline_style')) {
    function cre8_avatar_inline_style(string $classes, bool $isImage): string
    {
        $style = $isImage
            ? 'border-radius:50%;object-fit:cover;padding:0;flex-shrink:0;'
            : 'border-radius:50%;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;';

        if (strpos($classes, 'cre8-avatar-sm') !== false) {
            $style .= 'width:24px;height:24px;font-size:.72rem;font-weight:800;';
        } elseif (strpos($classes, 'cre8-avatar-md') !== false) {
            $style .= 'width:36px;height:36px;font-size:.9rem;font-weight:800;';
        } elseif (strpos($classes, 'cre8-avatar-lg') !== false) {
            $style .= 'width:64px;height:64px;font-size:1.35rem;font-weight:800;';
        }

        if (!$isImage && strpos($classes, 'cre8-avatar-') !== false) {
            $style .= 'background:linear-gradient(135deg,#e0d8ff 0%,#ede8ff 100%);color:#7b6fcf;';
        }

        return $style;
    }
}

if (!function_exists('cre8_render_avatar')) {
    function cre8_render_avatar($userId, string $name, string $classes = '', ?string $webBase = null): string
    {
        $classes = trim('cre8-avatar ' . $classes);
        $imageUrl = cre8_profile_image_url($userId, $webBase);
        $safeClass = cre8_avatar_escape($classes);
        $safeAlt = cre8_avatar_escape(trim($name) !== '' ? trim($name) . ' profile photo' : 'Profile photo');

        if ($imageUrl !== null) {
            return '<img class="' . $safeClass . '" src="' . cre8_avatar_escape($imageUrl) . '" alt="' . $safeAlt . '" loading="lazy" style="' . cre8_avatar_inline_style($classes, true) . '">';
        }

        return '<div class="' . $safeClass . '" aria-hidden="true" style="' . cre8_avatar_inline_style($classes, false) . '">' . cre8_avatar_escape(cre8_user_initial($name)) . '</div>';
    }
}
