(function () {
  'use strict';

  var config = window.Cre8BackNotifications || {};
  var apiUrl = config.apiUrl || '../utilisateur/notifications_api.php';
  var countEl;
  var listEl;
  var markAllButton;
  var refreshTimer;
  var notificationTranslations = {
    en: {
      'header.markAllRead': 'Mark all as read',
      'header.notificationSubtitle': 'Latest admin updates',
      'header.noNotifications': 'No new notifications',
      'header.allCaughtUp': "You're all caught up."
    },
    fr: {
      'header.markAllRead': 'Tout marquer comme lu',
      'header.notificationSubtitle': 'Dernieres mises a jour admin',
      'header.noNotifications': 'Aucune notification',
      'header.allCaughtUp': 'Vous etes a jour.'
    }
  };

  function t(key, fallback) {
    var lang = window.cre8BackGetLang ? window.cre8BackGetLang() : (localStorage.getItem('cre8_back_lang') || 'en');
    return (notificationTranslations[lang] && notificationTranslations[lang][key])
      || (notificationTranslations.en && notificationTranslations.en[key])
      || fallback;
  }

  function request(action, data) {
    var body = new URLSearchParams();
    body.set('action', action);
    Object.keys(data || {}).forEach(function (key) {
      body.set(key, data[key]);
    });

    return fetch(apiUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: body.toString()
    }).then(function (response) {
      return response.json();
    });
  }

  function safeLink(link) {
    if (!link || typeof link !== 'string') {
      return '';
    }

    var value = link.trim();
    if (value === '' || /^javascript:/i.test(value)) {
      return '';
    }

    try {
      var url = new URL(value, window.location.origin);
      if (url.origin !== window.location.origin) {
        return '';
      }
      return url.href;
    } catch (e) {
      return '';
    }
  }

  function updateCount(count) {
    if (!countEl) {
      return;
    }

    var unread = Math.max(0, parseInt(count, 10) || 0);
    countEl.textContent = unread > 99 ? '99+' : String(unread);
    countEl.classList.toggle('d-none', unread <= 0);
  }

  function clearList() {
    while (listEl && listEl.firstChild) {
      listEl.removeChild(listEl.firstChild);
    }
  }

  function renderEmpty() {
    clearList();

    var empty = document.createElement('div');
    empty.className = 'cre8-notification-empty';

    var icon = document.createElement('span');
    icon.className = 'cre8-notification-empty-icon';
    icon.setAttribute('aria-hidden', 'true');
    icon.textContent = '✓';

    var title = document.createElement('strong');
    title.setAttribute('data-i18n', 'header.noNotifications');
    title.textContent = t('header.noNotifications', 'No new notifications');

    var note = document.createElement('small');
    note.setAttribute('data-i18n', 'header.allCaughtUp');
    note.textContent = t('header.allCaughtUp', "You're all caught up.");

    empty.appendChild(icon);
    empty.appendChild(title);
    empty.appendChild(note);
    listEl.appendChild(empty);
  }

  function renderNotifications(items) {
    clearList();

    if (!items || items.length === 0) {
      renderEmpty();
      return;
    }

    items.forEach(function (item, index) {
      var link = safeLink(item.link);
      var row = document.createElement(link ? 'a' : 'button');
      row.className = 'cre8-notification-item';
      if (!item.is_read) {
        row.classList.add('is-unread');
      }
      row.type = link ? undefined : 'button';
      if (link) {
        row.href = link;
      }
      row.dataset.notificationId = String(item.id || '');

      var icon = document.createElement('span');
      icon.className = 'cre8-notification-icon';
      var iconGlyph = document.createElement('i');
      iconGlyph.className = item.is_read ? 'mdi mdi-bell-outline' : 'mdi mdi-bell-ring';
      icon.appendChild(iconGlyph);

      var content = document.createElement('div');
      content.className = 'cre8-notification-content';

      var title = document.createElement('p');
      title.className = 'cre8-notification-title';
      title.textContent = item.title || 'Notification';

      var message = document.createElement('p');
      message.className = 'cre8-notification-message';
      message.textContent = item.message || '';

      var date = document.createElement('p');
      date.className = 'cre8-notification-date';
      date.textContent = item.created_at || '';

      content.appendChild(title);
      if (item.message) {
        content.appendChild(message);
      }
      if (item.created_at) {
        content.appendChild(date);
      }

      if (!item.is_read) {
        var unreadDot = document.createElement('span');
        unreadDot.className = 'cre8-notification-dot';
        unreadDot.setAttribute('aria-hidden', 'true');
        content.appendChild(unreadDot);
      }

      row.appendChild(icon);
      row.appendChild(content);

      row.addEventListener('click', function (event) {
        event.preventDefault();
        markRead(item.id, link);
      });

      listEl.appendChild(row);
    });

    if (window.cre8BackApplyTranslations) {
      window.cre8BackApplyTranslations();
    }
  }

  function refresh() {
    if (!listEl) {
      return Promise.resolve();
    }

    return request('list')
      .then(function (payload) {
        if (!payload || !payload.success) {
          renderEmpty();
          updateCount(0);
          return;
        }

        updateCount(payload.unread_count);
        renderNotifications(payload.notifications || []);
      })
      .catch(function () {
        renderEmpty();
        updateCount(0);
      });
  }

  function markRead(id, link) {
    if (!id) {
      if (link) {
        window.location.href = link;
      }
      return;
    }

    request('mark_read', { id: id })
      .then(function (payload) {
        if (payload && payload.success) {
          updateCount(payload.unread_count);
        }
      })
      .finally(function () {
        if (link) {
          window.location.href = link;
        } else {
          refresh();
        }
      });
  }

  function markAllRead() {
    request('mark_all_read')
      .then(function (payload) {
        if (payload && payload.success) {
          updateCount(payload.unread_count);
        }
      })
      .finally(refresh);
  }

  function init() {
    countEl = document.getElementById('boNotifCount');
    listEl = document.getElementById('boNotifList');
    markAllButton = document.getElementById('boNotifMarkAll');

    if (!listEl) {
      return;
    }

    if (window.cre8BackRegisterTranslations) {
      window.cre8BackRegisterTranslations(notificationTranslations);
    }

    if (markAllButton) {
      markAllButton.addEventListener('click', function (event) {
        event.preventDefault();
        markAllRead();
      });
    }

    refresh();
    refreshTimer = window.setInterval(refresh, 60000);
    window.addEventListener('beforeunload', function () {
      if (refreshTimer) {
        window.clearInterval(refreshTimer);
      }
    });
    window.addEventListener('cre8:languagechange', function () {
      if (window.cre8BackApplyTranslations) {
        window.cre8BackApplyTranslations();
      }
      if (listEl && listEl.children.length === 1 && listEl.textContent.trim() !== '') {
        var empty = listEl.querySelector('[data-i18n="header.noNotifications"]');
        if (empty) {
          empty.textContent = t('header.noNotifications', 'No new notifications');
        }
        var caughtUp = listEl.querySelector('[data-i18n="header.allCaughtUp"]');
        if (caughtUp) {
          caughtUp.textContent = t('header.allCaughtUp', "You're all caught up.");
        }
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
