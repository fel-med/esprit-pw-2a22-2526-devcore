(() => {
    function activateTab(shell, key) {
        const tabs = shell.querySelectorAll('[data-offer-tab]');
        const panels = shell.querySelectorAll('[data-offer-tab-panel]');

        tabs.forEach((tab) => {
            const isActive = tab.getAttribute('data-offer-tab') === key;
            tab.classList.toggle('is-active', isActive);
            tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
            tab.tabIndex = isActive ? 0 : -1;
        });

        panels.forEach((panel) => {
            const isActive = panel.getAttribute('data-offer-tab-panel') === key;
            panel.hidden = !isActive;
        });
    }

    function getTabs(shell) {
        return Array.from(shell.querySelectorAll('[data-offer-tab]'));
    }

    function bindTabShell(shell) {
        const tabs = getTabs(shell);
        if (!tabs.length) {
            return;
        }

        if (shell.dataset.offerTabsBound !== '1') {
            tabs.forEach((tab) => {
                tab.addEventListener('click', () => {
                    activateTab(shell, tab.getAttribute('data-offer-tab'));
                });

                tab.addEventListener('keydown', (event) => {
                    const items = getTabs(shell);
                    const currentIndex = items.indexOf(tab);

                    if (currentIndex === -1) {
                        return;
                    }

                    let nextIndex = null;

                    if (event.key === 'ArrowRight') {
                        nextIndex = (currentIndex + 1) % items.length;
                    } else if (event.key === 'ArrowLeft') {
                        nextIndex = (currentIndex - 1 + items.length) % items.length;
                    } else if (event.key === 'Home') {
                        nextIndex = 0;
                    } else if (event.key === 'End') {
                        nextIndex = items.length - 1;
                    }

                    if (nextIndex === null) {
                        return;
                    }

                    event.preventDefault();
                    const nextTab = items[nextIndex];
                    activateTab(shell, nextTab.getAttribute('data-offer-tab'));
                    nextTab.focus();
                });
            });

            shell.dataset.offerTabsBound = '1';
        }

        const requestedDefault = shell.getAttribute('data-default-tab');
        const firstKey = tabs[0].getAttribute('data-offer-tab');
        const defaultKey = tabs.some((tab) => tab.getAttribute('data-offer-tab') === requestedDefault)
            ? requestedDefault
            : firstKey;

        activateTab(shell, defaultKey);
    }

    function initOfferTabs(root = document) {
        root.querySelectorAll('[data-offer-tab-shell]').forEach(bindTabShell);
        bindClickableCards(root);
    }

    function shouldIgnoreCardClick(event) {
        return event.defaultPrevented
            || event.button !== 0
            || event.metaKey
            || event.ctrlKey
            || event.shiftKey
            || event.altKey
            || !!event.target.closest('a, button, input, textarea, select, label, form, [data-card-ignore]');
    }

    function openCard(card) {
        const href = card.getAttribute('data-card-href');

        if (!href) {
            return;
        }

        window.location.assign(href);
    }

    function bindClickableCards(root = document) {
        root.querySelectorAll('[data-card-href]').forEach((card) => {
            if (card.dataset.cardBound === '1') {
                return;
            }

            card.setAttribute('role', 'link');
            card.tabIndex = 0;

            card.addEventListener('click', (event) => {
                if (shouldIgnoreCardClick(event)) {
                    return;
                }

                openCard(card);
            });

            card.addEventListener('keydown', (event) => {
                if (event.key !== 'Enter' && event.key !== ' ') {
                    return;
                }

                if (event.target.closest('a, button, input, textarea, select, label, form, [data-card-ignore]')) {
                    return;
                }

                event.preventDefault();
                openCard(card);
            });

            card.dataset.cardBound = '1';
        });
    }

    window.initOfferTabs = initOfferTabs;
    window.initClickableCards = bindClickableCards;
    document.addEventListener('DOMContentLoaded', () => initOfferTabs(document));
})();
