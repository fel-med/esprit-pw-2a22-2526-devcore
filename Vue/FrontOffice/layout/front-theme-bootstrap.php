<?php
/**
 * Inline theme bootstrap — include as the FIRST output inside <head> (before any CSS).
 * Prevents FOUC when saved theme is dark (or light). Canonical key: cre8_theme.
 */
if (defined('CRE8_FRONT_THEME_BOOTSTRAP_EMITTED')) {
    return;
}
define('CRE8_FRONT_THEME_BOOTSTRAP_EMITTED', true);
?>
<script>
(function () {
    try {
        var stored = localStorage.getItem('cre8_theme');
        var legacy = localStorage.getItem('cre8_theme_fo');
        var generic = localStorage.getItem('theme');
        var theme;
        if (stored === 'dark' || stored === 'light') {
            theme = stored;
        } else if (legacy === 'dark' || legacy === 'light') {
            theme = legacy;
            localStorage.setItem('cre8_theme', theme);
        } else if (generic === 'dark' || generic === 'light') {
            theme = generic;
            localStorage.setItem('cre8_theme', theme);
        } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            theme = 'dark';
        } else {
            theme = 'light';
        }
        if (theme !== 'dark' && theme !== 'light') {
            theme = 'light';
        }
        var root = document.documentElement;
        root.setAttribute('data-theme', theme);
        root.classList.toggle('dark-mode', theme === 'dark');
        root.classList.toggle('light-mode', theme !== 'dark');
        root.style.colorScheme = theme === 'dark' ? 'dark' : 'light';
    } catch (e) {
        var r = document.documentElement;
        r.setAttribute('data-theme', 'light');
        r.classList.add('light-mode');
        r.classList.remove('dark-mode');
        r.style.colorScheme = 'light';
    }
})();
</script>
