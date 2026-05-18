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
  window.cre8BackRegisterTranslations = function (dictionary) {
    mergeDictionary(dictionary);
    applyTranslations();
  };

  mergeDictionary({
    en: {
      'common.language': 'Language',
      'common.english': 'English',
      'common.french': 'French',
      'header.search': 'Search admin workspace',
      'header.searchPlaceholder': 'Quick open a BackOffice area...',
      'header.searchNoResults': 'No page found',
      'header.searchHint': 'Type a module name or press Enter to open',
      'header.searchOpen': 'Open',
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
      'nav.navigation': 'Navigation',
      'nav.dashboard': 'Dashboard',
      'nav.users': 'Users',
      'nav.userCenter': 'User Center',
      'nav.adminManagement': 'Admin Management',
      'nav.serverCenter': 'Server Center',
      'nav.complaints': 'Complaints',
      'nav.adminRequests': 'Admin Requests',
      'nav.collaborations': 'Collaborations',
      'nav.campaigns': 'Campaigns',
      'nav.business': 'Business',
      'nav.products': 'Products',
      'nav.contracts': 'Contracts',
      'nav.posts': 'Posts',
      'nav.community': 'Community',
      'nav.comments': 'Comments',
      'nav.events': 'Events',
      'nav.eventsHub': 'Events',
      'nav.forum': 'Forum',
      'group.userCenter': 'User Center',
      'group.business': 'Business',
      'group.community': 'Community',
      'group.events': 'Events'
    },
    fr: {
      'common.language': 'Langue',
      'common.english': 'Anglais',
      'common.french': 'Francais',
      'header.search': 'Rechercher dans l espace admin',
      'header.searchPlaceholder': 'Ouvrir rapidement une zone BackOffice...',
      'header.searchNoResults': 'Aucune page trouvee',
      'header.searchHint': 'Tapez un module ou appuyez sur Entree',
      'header.searchOpen': 'Ouvrir',
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
      'nav.navigation': 'Navigation',
      'nav.dashboard': 'Tableau de bord',
      'nav.users': 'Utilisateurs',
      'nav.userCenter': 'Centre utilisateurs',
      'nav.adminManagement': 'Gestion des admins',
      'nav.serverCenter': 'Centre serveur',
      'nav.complaints': 'Reclamations',
      'nav.adminRequests': 'Demandes admin',
      'nav.collaborations': 'Collaborations',
      'nav.campaigns': 'Campagnes',
      'nav.business': 'Business',
      'nav.products': 'Produits',
      'nav.contracts': 'Contrats',
      'nav.posts': 'Publications',
      'nav.community': 'Communaute',
      'nav.comments': 'Commentaires',
      'nav.events': 'Evenements',
      'nav.eventsHub': 'Evenements',
      'nav.forum': 'Forum',
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
