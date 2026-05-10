(function () {
    'use strict';

    var html = document.documentElement;

    function normalizeTheme(t) {
        return t === 'dark' ? 'dark' : 'light';
    }

    function readStoredTheme() {
        try {
            var stored = localStorage.getItem('cre8_theme');
            if (stored === 'dark' || stored === 'light') {
                return stored;
            }
            var legacy = localStorage.getItem('cre8_theme_fo');
            if (legacy === 'dark' || legacy === 'light') {
                localStorage.setItem('cre8_theme', legacy);
                return legacy;
            }
            var generic = localStorage.getItem('theme');
            if (generic === 'dark' || generic === 'light') {
                localStorage.setItem('cre8_theme', generic);
                return generic;
            }
        } catch (e) {}
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            return 'dark';
        }
        return 'light';
    }

    function syncBodyFromHtml() {
        var theme = html.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
        if (document.body) {
            document.body.classList.toggle('dark-mode', theme === 'dark');
            document.body.classList.toggle('light-mode', theme !== 'dark');
        }
    }

    function syncIcon(theme) {
        var icon = document.getElementById('themeIcon');
        if (!icon) {
            return;
        }
        icon.className = theme === 'dark' ? 'bi bi-brightness-high' : 'bi bi-moon-stars';
    }

    function applyFrontTheme(theme, persist) {
        var t = normalizeTheme(theme);
        html.setAttribute('data-theme', t);
        html.classList.toggle('dark-mode', t === 'dark');
        html.classList.toggle('light-mode', t !== 'dark');
        try {
            html.style.colorScheme = t === 'dark' ? 'dark' : 'light';
        } catch (e) {}
        if (persist !== false) {
            try {
                localStorage.setItem('cre8_theme', t);
            } catch (e2) {}
        }
        syncBodyFromHtml();
        syncIcon(t);
    }

    window.cre8ApplyFrontTheme = applyFrontTheme;
    window.toggleDarkMode = function () {
        applyFrontTheme(html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark', true);
    };

    applyFrontTheme(readStoredTheme(), false);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            applyFrontTheme(html.getAttribute('data-theme') || readStoredTheme(), false);
            var btn = document.getElementById('themeBtn');
            if (btn && !btn.dataset.cre8ThemeBound) {
                btn.dataset.cre8ThemeBound = '1';
                btn.addEventListener('click', function () {
                    applyFrontTheme(html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark', true);
                });
            }
        });
    } else {
        syncBodyFromHtml();
        syncIcon(html.getAttribute('data-theme') === 'dark' ? 'dark' : 'light');
        var btn0 = document.getElementById('themeBtn');
        if (btn0 && !btn0.dataset.cre8ThemeBound) {
            btn0.dataset.cre8ThemeBound = '1';
            btn0.addEventListener('click', function () {
                applyFrontTheme(html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark', true);
            });
        }
    }

    if (window.MutationObserver) {
        new MutationObserver(function () {
            syncBodyFromHtml();
        }).observe(html, { attributes: true, attributeFilter: ['data-theme'] });
    }
})();
