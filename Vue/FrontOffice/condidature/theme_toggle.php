<button type="button" class="theme-toggle" data-theme-toggle data-default-theme="light" aria-label="Switch to dark mode">
    <span data-theme-toggle-icon aria-hidden="true">&#9728;</span>
    <span data-theme-toggle-label>Light</span>
</button>
<script>
(() => {
    const storageKey = 'cre8connect_theme';
    const themeMeta = {
        light: { label: 'Light', icon: '&#9728;', action: 'Switch to dark mode' },
        dark: { label: 'Dark', icon: '&#9790;', action: 'Switch to light mode' }
    };

    function normalizeTheme(theme, fallback) {
        return theme === 'dark' || theme === 'light' ? theme : fallback;
    }

    function applyTheme(theme, fallback = 'light') {
        const normalized = normalizeTheme(theme, fallback);
        document.documentElement.setAttribute('data-theme', normalized);
        document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
            const label = button.querySelector('[data-theme-toggle-label]');
            const icon = button.querySelector('[data-theme-toggle-icon]');
            const meta = themeMeta[normalized] || themeMeta[fallback] || themeMeta.light;
            if (label) {
                label.textContent = meta.label;
            }
            if (icon) {
                icon.innerHTML = meta.icon;
            }
            button.setAttribute('aria-label', meta.action);
        });
    }

    function bindThemeToggles(defaultTheme) {
        document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
            if (button.dataset.themeReady === '1') {
                return;
            }

            button.dataset.themeReady = '1';
            button.addEventListener('click', () => {
                const currentTheme = normalizeTheme(document.documentElement.getAttribute('data-theme'), defaultTheme);
                const nextTheme = currentTheme === 'dark' ? 'light' : 'dark';
                localStorage.setItem(storageKey, nextTheme);
                applyTheme(nextTheme, defaultTheme);
            });
        });
    }

    const currentScript = document.currentScript;
    const button = currentScript ? currentScript.previousElementSibling : null;
    const defaultTheme = normalizeTheme(button ? button.dataset.defaultTheme : '', 'light');
    applyTheme(normalizeTheme(localStorage.getItem(storageKey), defaultTheme), defaultTheme);
    bindThemeToggles(defaultTheme);
})();
</script>
