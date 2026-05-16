(function () {
    'use strict';

    var STORAGE_KEY = 'cre8_front_lang';
    var LEGACY_KEY = 'cre8_lang';
    var currentDict = null;

    function normalizeLang(lang) {
        return lang === 'fr' ? 'fr' : 'en';
    }

    function readStoredLang() {
        try {
            var stored = localStorage.getItem(STORAGE_KEY);
            if (stored === 'en' || stored === 'fr') {
                return stored;
            }

            var legacy = localStorage.getItem(LEGACY_KEY);
            if (legacy === 'en' || legacy === 'fr') {
                localStorage.setItem(STORAGE_KEY, legacy);
                return legacy;
            }
        } catch (e) {}

        return 'en';
    }

    function writeStoredLang(lang) {
        var safe = normalizeLang(lang);
        try {
            localStorage.setItem(STORAGE_KEY, safe);
        } catch (e) {}
        return safe;
    }

    function getText(dict, lang, key) {
        if (!dict || !key) {
            return undefined;
        }

        if (dict[lang] && Object.prototype.hasOwnProperty.call(dict[lang], key)) {
            return dict[lang][key];
        }

        if (dict[key] && typeof dict[key] === 'object' && Object.prototype.hasOwnProperty.call(dict[key], lang)) {
            return dict[key][lang];
        }

        return undefined;
    }

    function applyValue(elements, attrName, dict, lang, setter) {
        Array.prototype.forEach.call(elements, function (el) {
            var key = el.getAttribute(attrName);
            var value = getText(dict, lang, key);
            if (value === undefined || value === null) {
                return;
            }
            setter(el, String(value));
        });
    }

    function syncLanguageButtons(lang) {
        Array.prototype.forEach.call(document.querySelectorAll('[data-lang-choice]'), function (btn) {
            var active = normalizeLang(btn.getAttribute('data-lang-choice')) === lang;
            btn.classList.toggle('is-active', active);
            btn.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    }

    function applyI18n(dict) {
        var lang = readStoredLang();
        syncLanguageButtons(lang);

        if (!dict) {
            return lang;
        }

        applyValue(document.querySelectorAll('[data-i18n]'), 'data-i18n', dict, lang, function (el, value) {
            el.textContent = value;
        });
        applyValue(document.querySelectorAll('[data-i18n-placeholder]'), 'data-i18n-placeholder', dict, lang, function (el, value) {
            el.setAttribute('placeholder', value);
        });
        applyValue(document.querySelectorAll('[data-i18n-title]'), 'data-i18n-title', dict, lang, function (el, value) {
            el.setAttribute('title', value);
        });
        applyValue(document.querySelectorAll('[data-i18n-opt]'), 'data-i18n-opt', dict, lang, function (el, value) {
            el.textContent = value;
        });

        return lang;
    }

    function dispatchLanguageChange(lang) {
        try {
            window.dispatchEvent(new CustomEvent('cre8:languagechange', {
                detail: { lang: lang }
            }));
        } catch (e) {}
    }

    function setLanguage(lang) {
        var safe = writeStoredLang(lang);
        applyI18n(currentDict);
        dispatchLanguageChange(safe);
        return safe;
    }

    function registerTranslations(dict) {
        currentDict = dict || null;
        return applyI18n(currentDict);
    }

    window.cre8FrontReadLang = readStoredLang;
    window.cre8FrontWriteLang = writeStoredLang;
    window.cre8ApplyI18n = applyI18n;
    window.cre8RegisterTranslations = registerTranslations;
    window.cre8SetLanguage = setLanguage;

    function bindLanguageButtons() {
        applyI18n(currentDict);
        Array.prototype.forEach.call(document.querySelectorAll('[data-lang-choice]'), function (btn) {
            if (btn.dataset.cre8LangBound === '1') {
                return;
            }
            btn.dataset.cre8LangBound = '1';
            btn.addEventListener('click', function () {
                setLanguage(btn.getAttribute('data-lang-choice'));
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindLanguageButtons);
    } else {
        bindLanguageButtons();
    }
})();
