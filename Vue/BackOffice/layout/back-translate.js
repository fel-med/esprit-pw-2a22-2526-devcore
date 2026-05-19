(function () {
  var dictionaries = {};
  var supported = { en: true, fr: true };

  function readLang() {
    try {
      var lang = localStorage.getItem('cre8_back_lang')
        || localStorage.getItem('cre8_bo_lang')
        || localStorage.getItem('cre8_lang')
        || 'en';
      return supported[lang] ? lang : 'en';
    } catch (e) {
      return 'en';
    }
  }

  function writeLang(lang) {
    try {
      localStorage.setItem('cre8_back_lang', lang);
      localStorage.setItem('cre8_bo_lang', lang);
      localStorage.setItem('cre8_lang', lang);
    } catch (e) {
      /* ignore storage failures */
    }
  }

  function mergeDictionary(dictionary) {
    if (!dictionary || typeof dictionary !== 'object') {
      return;
    }

    Object.keys(dictionary).forEach(function (lang) {
      if (!supported[lang]) {
        return;
      }
      dictionaries[lang] = Object.assign(dictionaries[lang] || {}, dictionary[lang] || {});
    });
  }

  function textFor(key, lang) {
    return (dictionaries[lang] && dictionaries[lang][key])
      || (dictionaries.en && dictionaries.en[key])
      || key;
  }

  function applyTranslations() {
    var lang = readLang();

    document.querySelectorAll('[data-i18n]').forEach(function (node) {
      var key = node.getAttribute('data-i18n');
      if (key) {
        node.textContent = textFor(key, lang);
      }
    });

    document.querySelectorAll('[data-i18n-placeholder]').forEach(function (node) {
      var key = node.getAttribute('data-i18n-placeholder');
      if (key) {
        node.setAttribute('placeholder', textFor(key, lang));
      }
    });

    document.querySelectorAll('[data-i18n-title]').forEach(function (node) {
      var key = node.getAttribute('data-i18n-title');
      if (key) {
        node.setAttribute('title', textFor(key, lang));
      }
    });

    document.querySelectorAll('[data-i18n-aria-label]').forEach(function (node) {
      var key = node.getAttribute('data-i18n-aria-label');
      if (key) {
        node.setAttribute('aria-label', textFor(key, lang));
      }
    });

    document.querySelectorAll('[data-i18n-opt]').forEach(function (node) {
      var key = node.getAttribute('data-i18n-opt');
      if (key) {
        node.textContent = textFor(key, lang);
      }
    });

    document.querySelectorAll('[data-i18n-template]').forEach(function (node) {
      var key = node.getAttribute('data-i18n-template');
      if (!key) return;
      var text = textFor(key, lang);
      var count = node.getAttribute('data-i18n-count');
      node.textContent = count !== null && count !== '' ? text.replace('{count}', count) : text;
    });

    document.querySelectorAll('[data-tr]').forEach(function (node) {
      var key = node.getAttribute('data-tr');
      if (key) {
        node.textContent = textFor(key, lang);
      }
    });

    document.querySelectorAll('[data-tr-placeholder]').forEach(function (node) {
      var key = node.getAttribute('data-tr-placeholder');
      if (key) {
        node.setAttribute('placeholder', textFor(key, lang));
      }
    });

    document.querySelectorAll('[data-tr-title]').forEach(function (node) {
      var key = node.getAttribute('data-tr-title');
      if (key) {
        node.setAttribute('title', textFor(key, lang));
      }
    });

    document.querySelectorAll('[data-tr-aria-label]').forEach(function (node) {
      var key = node.getAttribute('data-tr-aria-label');
      if (key) {
        node.setAttribute('aria-label', textFor(key, lang));
      }
    });

    document.querySelectorAll('[data-tr-opt]').forEach(function (node) {
      var key = node.getAttribute('data-tr-opt');
      if (key) {
        node.textContent = textFor(key, lang);
      }
    });

    document.querySelectorAll('[data-cre8-back-lang]').forEach(function (button) {
      var active = button.getAttribute('data-cre8-back-lang') === lang;
      button.classList.toggle('active', active);
      button.classList.toggle('btn-primary', active);
      button.classList.toggle('btn-outline-secondary', !active);
      button.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
  }

  function setLang(lang) {
    lang = supported[lang] ? lang : 'en';
    writeLang(lang);
    applyTranslations();
    window.dispatchEvent(new CustomEvent('cre8:languagechange', { detail: { lang: lang } }));
  }

  function bindButtons() {
    document.querySelectorAll('[data-cre8-back-lang]').forEach(function (button) {
      if (button.dataset.cre8BackLangBound === '1') {
        return;
      }
      button.dataset.cre8BackLangBound = '1';
      button.addEventListener('click', function () {
        setLang(button.getAttribute('data-cre8-back-lang'));
      });
    });
  }

  window.cre8BackGetLang = readLang;
  window.cre8BackSetLang = setLang;
  window.cre8BackApplyTranslations = applyTranslations;
  window.cre8BackText = function (key, lang) {
    return textFor(key, supported[lang] ? lang : readLang());
  };
  window.cre8BackRegisterTranslations = function (dictionary) {
    mergeDictionary(dictionary);
    applyTranslations();
  };

  mergeDictionary({
    en: {
      'common.language': 'Language',
      'common.english': 'English',
      'common.french': 'French',
      'role.admin': 'Admin',
      'role.superAdmin': 'Super Admin',
      'role.hyperAdmin': 'Hyper Admin',
      'role.short.admin': 'Admin',
      'role.short.superAdmin': 'Super',
      'role.short.hyperAdmin': 'Hyper',
      'header.search': 'Search admin workspace',
      'header.searchPlaceholder': 'Search pages or modules...',
      'header.searchNoResults': 'No page found',
      'header.searchHint': 'Press Enter to open',
      'header.messages': 'Messages',
      'header.noMessages': 'No new messages',
      'header.notifications': 'Notifications',
      'header.notificationSubtitle': 'Latest admin updates',
      'header.markAllRead': 'Mark all as read',
      'header.noNotifications': 'No new notifications',
      'header.allCaughtUp': "You're all caught up.",
      'header.profile': 'Profile',
      'header.profileSettings': 'Profile Settings',
      'header.logout': 'Logout',
      // Keep every nav.* key used by layout/sidebar.php in both dictionaries.
      // Missing keys display raw labels like nav.userCenter.
      'nav.navigation': 'Navigation',
      'nav.dashboard': 'Dashboard',
      'nav.userCenter': 'User Center',
      'nav.users': 'Users',
      'nav.complaints': 'Complaints',
      'nav.collaborations': 'Collaborations',
      'nav.business': 'Business',
      'nav.campaigns': 'Campaigns',
      'nav.products': 'Products',
      'nav.contracts': 'Contracts',
      'nav.community': 'Community',
      'nav.posts': 'Posts',
      'nav.comments': 'Comments',
      'nav.eventsHub': 'Events',
      'nav.events': 'Events',
      'nav.forum': 'Forum',
      'nav.adminManagement': 'Admin Management',
      'nav.adminRequests': 'Admin Requests',
      'nav.serverCenter': 'Server Center',
      'group.userCenter': 'User Center',
      'group.business': 'Business',
      'group.community': 'Community',
      'group.events': 'Events'
    },
    fr: {
      'common.language': 'Langue',
      'common.english': 'Anglais',
      'common.french': 'Francais',
      'role.admin': 'Administrateur',
      'role.superAdmin': 'Super administrateur',
      'role.hyperAdmin': 'Hyper administrateur',
      'role.short.admin': 'Admin',
      'role.short.superAdmin': 'Super',
      'role.short.hyperAdmin': 'Hyper',
      'header.search': 'Rechercher dans l espace admin',
      'header.searchPlaceholder': 'Rechercher des pages ou modules...',
      'header.searchNoResults': 'Aucune page trouvee',
      'header.searchHint': 'Appuyez sur Entree pour ouvrir',
      'header.messages': 'Messages',
      'header.noMessages': 'Aucun nouveau message',
      'header.notifications': 'Notifications',
      'header.notificationSubtitle': 'Dernieres mises a jour admin',
      'header.markAllRead': 'Tout marquer comme lu',
      'header.noNotifications': 'Aucune notification',
      'header.allCaughtUp': 'Vous etes a jour.',
      'header.profile': 'Profil',
      'header.profileSettings': 'Parametres du profil',
      'header.logout': 'Deconnexion',
      // Keep every nav.* key used by layout/sidebar.php in both dictionaries.
      // Missing keys display raw labels like nav.userCenter.
      'nav.navigation': 'Navigation',
      'nav.dashboard': 'Tableau de bord',
      'nav.userCenter': 'Centre utilisateurs',
      'nav.users': 'Utilisateurs',
      'nav.complaints': 'Réclamations',
      'nav.collaborations': 'Collaborations',
      'nav.business': 'Business',
      'nav.campaigns': 'Campagnes',
      'nav.products': 'Produits',
      'nav.contracts': 'Contrats',
      'nav.community': 'Communauté',
      'nav.posts': 'Publications',
      'nav.comments': 'Commentaires',
      'nav.eventsHub': 'Événements',
      'nav.events': 'Événements',
      'nav.forum': 'Forum',
      'nav.adminManagement': 'Gestion des admins',
      'nav.adminRequests': 'Demandes admin',
      'nav.serverCenter': 'Centre serveur',
      'group.userCenter': 'Centre utilisateurs',
      'group.business': 'Business',
      'group.community': 'Communaute',
      'group.events': 'Evenements'
    }
  });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      bindButtons();
      applyTranslations();
    });
  } else {
    bindButtons();
    applyTranslations();
  }
})();
