(function () {
  function normalize(value) {
    return String(value || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
  }

  function cleanText(node) {
    return node ? node.textContent.replace(/\s+/g, ' ').trim() : '';
  }

  function isSafeHref(href) {
    if (!href || href === '#') {
      return false;
    }

    try {
      var url = new URL(href, window.location.href);
      return url.origin === window.location.origin;
    } catch (e) {
      return false;
    }
  }

  function collectEntries() {
    var links = Array.prototype.slice.call(document.querySelectorAll('#sidebar a[data-cre8-sidebar-link]'));
    var seen = {};

    return links.reduce(function (entries, link) {
      var href = link.href;
      if (!isSafeHref(href) || link.getAttribute('aria-disabled') === 'true' || seen[href]) {
        return entries;
      }

      var title = cleanText(link.querySelector('.menu-title')) || cleanText(link);
      var key = link.getAttribute('data-cre8-nav-key') || '';
      var aliases = link.getAttribute('data-cre8-nav-aliases') || '';
      var path = href.replace(window.location.origin, '');
      var haystack = normalize([title, key, aliases, path].join(' '));

      if (!title) {
        return entries;
      }

      seen[href] = true;
      entries.push({
        title: title,
        key: key,
        aliases: aliases,
        href: href,
        path: path,
        haystack: haystack
      });
      return entries;
    }, []);
  }

  function initWorkspaceSearch() {
    var form = document.querySelector('[data-cre8-workspace-search]');
    var input = document.getElementById('cre8WorkspaceSearchInput');
    var results = document.getElementById('cre8WorkspaceSearchResults');

    if (!form || !input || !results) {
      return;
    }

    var activeIndex = 0;
    var currentMatches = [];

    function clearResults() {
      while (results.firstChild) {
        results.removeChild(results.firstChild);
      }
    }

    function closeResults() {
      results.hidden = true;
      input.setAttribute('aria-expanded', 'false');
      activeIndex = 0;
    }

    function openResults() {
      results.hidden = false;
      input.setAttribute('aria-expanded', 'true');
    }

    function translateAfterRender() {
      if (window.cre8BackApplyTranslations) {
        window.cre8BackApplyTranslations();
      }
    }

    function renderEmpty() {
      var empty = document.createElement('div');
      var strong = document.createElement('strong');
      var hint = document.createElement('span');

      empty.className = 'cre8-workspace-search-empty';
      strong.setAttribute('data-i18n', 'header.searchNoResults');
      hint.setAttribute('data-i18n', 'header.searchHint');
      strong.textContent = 'No page found';
      hint.textContent = 'Type a module name or press Enter to open';
      empty.appendChild(strong);
      empty.appendChild(hint);
      results.appendChild(empty);
      translateAfterRender();
    }

    function setActive(index) {
      var items = results.querySelectorAll('.cre8-workspace-search-item');
      if (!items.length) {
        activeIndex = 0;
        return;
      }

      activeIndex = Math.max(0, Math.min(index, items.length - 1));
      items.forEach(function (item, itemIndex) {
        item.classList.toggle('is-active', itemIndex === activeIndex);
      });
    }

    function renderMatches(matches) {
      clearResults();
      currentMatches = matches.slice(0, 10);

      if (!currentMatches.length) {
        renderEmpty();
        openResults();
        return;
      }

      currentMatches.forEach(function (entry, index) {
        var button = document.createElement('button');
        var text = document.createElement('span');
        var title = document.createElement('strong');
        var meta = document.createElement('small');
        var open = document.createElement('em');

        button.type = 'button';
        button.className = 'cre8-workspace-search-item';
        button.dataset.href = entry.href;

        text.className = 'cre8-workspace-search-copy';
        title.textContent = entry.title;
        meta.textContent = entry.aliases ? entry.aliases.split(/\s+/).slice(0, 5).join(' ') : entry.path;
        open.className = 'cre8-workspace-search-open';
        open.setAttribute('data-i18n', 'header.searchOpen');
        open.textContent = 'Open';

        text.appendChild(title);
        text.appendChild(meta);
        button.appendChild(text);
        button.appendChild(open);

        button.addEventListener('click', function () {
          window.location.href = entry.href;
        });
        results.appendChild(button);

        if (index === 0) {
          button.classList.add('is-active');
        }
      });

      activeIndex = 0;
      openResults();
      translateAfterRender();
    }

    function scoreEntry(entry, query) {
      var title = normalize(entry.title);
      var key = normalize(entry.key);
      var haystack = entry.haystack;

      if (!query) {
        return 10;
      }
      if (title === query || key === query) {
        return 100;
      }
      if (title.indexOf(query) === 0 || key.indexOf(query) === 0) {
        return 80;
      }
      if (haystack.indexOf(query) !== -1) {
        return 50;
      }
      return 0;
    }

    function updateSearch(showAll) {
      var query = normalize(input.value);
      var entries = collectEntries();
      var matches;

      if (!query && !showAll) {
        closeResults();
        return;
      }

      matches = entries
        .map(function (entry) {
          entry.score = scoreEntry(entry, query);
          return entry;
        })
        .filter(function (entry) {
          return query ? entry.score > 0 : true;
        })
        .sort(function (a, b) {
          return b.score - a.score || a.title.localeCompare(b.title);
        });

      renderMatches(matches);
    }

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      var target = currentMatches[activeIndex] || currentMatches[0];
      if (target) {
        window.location.href = target.href;
      }
    });

    input.addEventListener('input', function () {
      updateSearch(false);
    });

    input.addEventListener('focus', function () {
      updateSearch(true);
    });

    input.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        closeResults();
        input.blur();
        return;
      }

      if (event.key === 'ArrowDown') {
        event.preventDefault();
        setActive(activeIndex + 1);
      }

      if (event.key === 'ArrowUp') {
        event.preventDefault();
        setActive(activeIndex - 1);
      }
    });

    document.addEventListener('keydown', function (event) {
      if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        input.focus();
        input.select();
        updateSearch(true);
      }
    });

    document.addEventListener('click', function (event) {
      if (!form.contains(event.target)) {
        closeResults();
      }
    });

    window.addEventListener('cre8:languagechange', function () {
      if (!results.hidden) {
        updateSearch(true);
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initWorkspaceSearch);
  } else {
    initWorkspaceSearch();
  }
})();
