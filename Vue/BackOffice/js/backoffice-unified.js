(function () {
  function savedTheme() {
    return localStorage.getItem('theme') || localStorage.getItem('cre8_theme') || localStorage.getItem('cre8_theme_produit') || 'dark';
  }

  function getBackOfficeChartTheme() {
    const styles = getComputedStyle(document.body);
    const isLight = document.body.classList.contains('light-mode');
    return {
      theme: isLight ? 'light' : 'dark',
      text: isLight ? '#111827' : '#dfe3f2',
      grid: isLight ? '#e2e8f0' : '#2a3345',
      accent: styles.getPropertyValue('--bo-purple').trim() || '#8b5cf6',
      accentSoft: isLight ? 'rgba(139,92,246,.28)' : 'rgba(139,92,246,.35)',
      rose: '#ec4899',
      roseSoft: isLight ? 'rgba(236,72,153,.22)' : 'rgba(236,72,153,.35)',
      info: '#c084fc',
      infoSoft: isLight ? 'rgba(192,132,252,.22)' : 'rgba(192,132,252,.35)'
    };
  }

  function syncThemeIcon() {
    const icon = document.getElementById('themeIcon');
    if (!icon) return;

    if (document.body.classList.contains('light-mode')) {
      icon.className = 'fas fa-sun';
    } else {
      icon.className = 'fas fa-moon';
    }
  }

  function emitThemeChange() {
    window.dispatchEvent(new CustomEvent('cre8:themechange', {
      detail: {
        theme: document.body.classList.contains('light-mode') ? 'light' : 'dark',
        chart: getBackOfficeChartTheme()
      }
    }));
  }

  function applyBackOfficeTheme(theme) {
    const nextTheme = theme || savedTheme();
    document.body.classList.toggle('light-mode', nextTheme === 'light');
    localStorage.setItem('theme', nextTheme === 'light' ? 'light' : 'dark');
    localStorage.setItem('cre8_theme', nextTheme === 'light' ? 'light' : 'dark');
    syncThemeIcon();
    emitThemeChange();
    return nextTheme === 'light' ? 'light' : 'dark';
  }

  function toggleBackOfficeTheme() {
    const nextTheme = document.body.classList.contains('light-mode') ? 'dark' : 'light';
    return applyBackOfficeTheme(nextTheme);
  }

  function savedLanguage() {
    const lang = localStorage.getItem('cre8_lang') ||
      localStorage.getItem('cre8_lang_produit') ||
      localStorage.getItem('cre8_lang_contrat') ||
      document.documentElement.lang ||
      'fr';
    return String(lang).toLowerCase().startsWith('en') ? 'en' : 'fr';
  }

  function syncLanguageButton(lang) {
    const button = document.getElementById('boLangToggle');
    if (!button) return;
    const current = lang || savedLanguage();
    const next = current === 'fr' ? 'EN' : 'FR';
    const label = button.querySelector('.bo-lang-label');
    if (label) label.textContent = next;
    button.setAttribute('title', current === 'fr' ? 'Traduire en anglais' : 'Traduire en français');
    button.setAttribute('aria-label', current === 'fr' ? 'Traduire en anglais' : 'Traduire en français');
  }

  function applyBackOfficeLanguage(lang) {
    const nextLang = String(lang || savedLanguage()).toLowerCase().startsWith('en') ? 'en' : 'fr';
    localStorage.setItem('cre8_lang', nextLang);
    localStorage.setItem('cre8_lang_produit', nextLang);
    localStorage.setItem('cre8_lang_contrat', nextLang);

    if (typeof window.setLang === 'function') {
      window.setLang(nextLang);
    } else if (typeof window.translatePage === 'function') {
      window.translatePage(nextLang);
    }

    syncLanguageButton(nextLang);
    window.dispatchEvent(new CustomEvent('cre8:languagechange', { detail: { lang: nextLang } }));
    return nextLang;
  }

  function toggleBackOfficeLanguage() {
    return applyBackOfficeLanguage(savedLanguage() === 'fr' ? 'en' : 'fr');
  }

  window.getBackOfficeChartTheme = getBackOfficeChartTheme;
  window.applyBackOfficeTheme = applyBackOfficeTheme;
  window.toggleBackOfficeTheme = toggleBackOfficeTheme;
  window.toggleDarkMode = toggleBackOfficeTheme;
  window.applyBackOfficeLanguage = applyBackOfficeLanguage;
  window.toggleBackOfficeLanguage = toggleBackOfficeLanguage;

  document.addEventListener('DOMContentLoaded', function () {
    applyBackOfficeTheme();
    syncLanguageButton();
  });
})();
