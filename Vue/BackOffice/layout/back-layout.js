/**
 * BackOffice theme toggle.
 * Keeps Corona behavior (body.light-mode + localStorage.theme)
 * and syncs html[data-theme] + html.light-mode for shared pages.
 * Head inline script (early-theme.php) applies theme before first paint; this file reinforces on load/toggle.
 */
(function () {
  function cre8BackApplyInitialThemeEarly() {
    if (window.__cre8BackThemeBooted) {
      return;
    }
    try {
      var theme = localStorage.getItem('theme') === 'light' ? 'light' : 'dark';
      var root = document.documentElement;
      root.setAttribute('data-theme', theme);
      root.classList.toggle('light-mode', theme === 'light');
      root.style.colorScheme = theme === 'light' ? 'light' : 'dark';
    } catch (e) {
      document.documentElement.setAttribute('data-theme', 'dark');
      document.documentElement.classList.remove('light-mode');
      document.documentElement.style.colorScheme = 'dark';
    }
  }

  function cre8BackSetTheme(theme) {
    var isLight = theme === 'light';
    var root = document.documentElement;

    root.setAttribute('data-theme', isLight ? 'light' : 'dark');
    root.classList.toggle('light-mode', isLight);
    root.style.colorScheme = isLight ? 'light' : 'dark';

    if (document.body) {
      document.body.classList.toggle('light-mode', isLight);
    }

    try {
      localStorage.setItem('theme', isLight ? 'light' : 'dark');
    } catch (e) {
      /* ignore */
    }

    var icon = document.getElementById('themeIcon');
    if (icon) {
      icon.classList.remove('mdi-weather-night', 'mdi-white-balance-sunny', 'fa-moon', 'fa-sun');
      if (icon.classList.contains('mdi') || icon.className.indexOf('mdi') !== -1) {
        icon.classList.add(isLight ? 'mdi-white-balance-sunny' : 'mdi-weather-night');
      } else {
        icon.classList.add(isLight ? 'fa-sun' : 'fa-moon');
      }
    }
  }

  cre8BackApplyInitialThemeEarly();

  window.cre8BackSetTheme = cre8BackSetTheme;

  window.toggleDarkMode = function () {
    var isLightNow = document.documentElement.getAttribute('data-theme') === 'light';
    cre8BackSetTheme(isLightNow ? 'dark' : 'light');
  };

  function initCre8ProfileDropdown() {
    var dropdowns = document.querySelectorAll('[data-cre8-profile-dropdown]');
    if (!dropdowns.length) {
      return;
    }

    function closeAll(except) {
      dropdowns.forEach(function (drop) {
        if (drop === except) {
          return;
        }
        var toggle = drop.querySelector('[data-cre8-profile-toggle]');
        var menu = drop.querySelector('[data-cre8-profile-menu]');
        drop.classList.remove('show');
        if (menu) {
          menu.classList.remove('show');
        }
        if (toggle) {
          toggle.setAttribute('aria-expanded', 'false');
        }
      });
    }

    dropdowns.forEach(function (drop) {
      var toggle = drop.querySelector('[data-cre8-profile-toggle]');
      var menu = drop.querySelector('[data-cre8-profile-menu]');
      if (!toggle || !menu) {
        return;
      }

      toggle.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();

        var isOpen = menu.classList.contains('show');
        closeAll(drop);

        drop.classList.toggle('show', !isOpen);
        menu.classList.toggle('show', !isOpen);
        toggle.setAttribute('aria-expanded', String(!isOpen));
      });

      menu.addEventListener('click', function (event) {
        event.stopPropagation();
      });
    });

    document.addEventListener('click', function () {
      closeAll(null);
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        closeAll(null);
      }
    });
  }

  window.addEventListener('DOMContentLoaded', function () {
    var theme = document.documentElement.getAttribute('data-theme') === 'light' ? 'light' : 'dark';
    cre8BackSetTheme(theme);
    initCre8ProfileDropdown();
  });
})();
