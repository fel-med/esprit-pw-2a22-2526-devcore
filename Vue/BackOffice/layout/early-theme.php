<?php
/**
 * Cre8 BackOffice — apply saved theme before first paint (avoids light-mode FOUC).
 *
 * Include once near the top of the PHP entry (before any HTML output):
 *   require_once __DIR__ . '/../layout/early-theme.php';
 *
 * Inside <head>, before stylesheet links:
 *   <?php cre8_bo_early_theme_print_head_script(); ?>
 *
 * Immediately after opening <body class="cre8-admin-layout">:
 *   <?php cre8_bo_early_theme_print_body_script(); ?>
 */
require_once __DIR__ . '/../../../Controleur/session_helper.php';

if (!function_exists('cre8_bo_login_url')) {
    function cre8_bo_login_url(): string
    {
        $script = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');

        foreach (['/Vue/BackOffice/', '/Controleur/'] as $marker) {
            $pos = strpos($script, $marker);
            if ($pos !== false) {
                return substr($script, 0, $pos) . '/Vue/FrontOffice/utilisateur/login.php';
            }
        }

        return '../../FrontOffice/utilisateur/login.php';
    }
}

cc_require_admin(cre8_bo_login_url());

if (!function_exists('cre8_bo_early_theme_print_head_script')) {

    /**
     * Run in <head> before CSS: sets html[data-theme], html.light-mode, color-scheme.
     * localStorage key: theme ('light' | otherwise dark).
     */
    function cre8_bo_early_theme_print_head_script(): void
    {
        ?>
<script>
(function () {
  try {
    var theme = localStorage.getItem('theme') === 'light' ? 'light' : 'dark';
    var root = document.documentElement;
    root.setAttribute('data-theme', theme);
    root.classList.toggle('light-mode', theme === 'light');
    root.style.colorScheme = theme === 'light' ? 'light' : 'dark';
    window.__cre8BackThemeBooted = true;
  } catch (e) {
    document.documentElement.setAttribute('data-theme', 'dark');
    document.documentElement.classList.remove('light-mode');
    document.documentElement.style.colorScheme = 'dark';
  }
})();
</script>
        <?php
    }

    /**
     * Run as first child inside <body> so body.light-mode matches before module CSS variables apply.
     */
    function cre8_bo_early_theme_print_body_script(): void
    {
        ?>
<script>
(function () {
  try {
    var root = document.documentElement;
    var light = root.getAttribute('data-theme') === 'light';
    if (root.getAttribute('data-theme') === null || root.getAttribute('data-theme') === '') {
      light = localStorage.getItem('theme') === 'light';
      root.setAttribute('data-theme', light ? 'light' : 'dark');
      root.classList.toggle('light-mode', light);
      root.style.colorScheme = light ? 'light' : 'dark';
    }
    if (document.body) {
      document.body.classList.toggle('light-mode', light);
    }
  } catch (e) {}
})();
</script>
        <?php
    }
}
