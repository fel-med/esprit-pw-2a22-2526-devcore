(() => {
    const REGION_SELECTOR = '[data-candidature-live-region]';
    const SAVE_FORM_SELECTOR = 'form[data-candidature-save-toggle-form]';
    const SOURCE_SHELL_SELECTOR = '[data-brand-source-tab-shell]';
    let latestRequestId = 0;

    function getRegion() {
        return document.querySelector(REGION_SELECTOR);
    }

    function getActiveTabKey(root = document) {
        const activeTab = root.querySelector('[data-offer-tab].is-active, [data-offer-tab][aria-selected="true"]');
        return activeTab ? activeTab.getAttribute('data-offer-tab') : null;
    }

    function getActiveSourceKey(root = document) {
        const activeTab = root.querySelector('[data-brand-source-tab].is-active, [data-brand-source-tab][aria-selected="true"]');
        return activeTab ? activeTab.getAttribute('data-brand-source-tab') : null;
    }

    function getActiveTabState(root = document) {
        const sourceKey = getActiveSourceKey(root);
        const sourcePanel = sourceKey
            ? root.querySelector(`[data-brand-source-tab-panel="${sourceKey}"]`)
            : null;

        return {
            source: sourceKey,
            workflow: getActiveTabKey(sourcePanel || root)
        };
    }

    function activateSourceTab(shell, key) {
        const tabs = shell.querySelectorAll('[data-brand-source-tab]');
        const panels = shell.querySelectorAll('[data-brand-source-tab-panel]');

        tabs.forEach((tab) => {
            const isActive = tab.getAttribute('data-brand-source-tab') === key;
            tab.classList.toggle('is-active', isActive);
            tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
            tab.tabIndex = isActive ? 0 : -1;
        });

        panels.forEach((panel) => {
            const isActive = panel.getAttribute('data-brand-source-tab-panel') === key;
            panel.hidden = !isActive;
        });
    }

    function bindBrandSourceTabs(root = document) {
        root.querySelectorAll(SOURCE_SHELL_SELECTOR).forEach((shell) => {
            const tabs = Array.from(shell.querySelectorAll('[data-brand-source-tab]'));
            if (!tabs.length) {
                return;
            }

            if (shell.dataset.brandSourceTabsBound !== '1') {
                tabs.forEach((tab) => {
                    tab.addEventListener('click', () => {
                        activateSourceTab(shell, tab.getAttribute('data-brand-source-tab'));
                    });

                    tab.addEventListener('keydown', (event) => {
                        const currentIndex = tabs.indexOf(tab);
                        let nextIndex = null;

                        if (event.key === 'ArrowRight') {
                            nextIndex = (currentIndex + 1) % tabs.length;
                        } else if (event.key === 'ArrowLeft') {
                            nextIndex = (currentIndex - 1 + tabs.length) % tabs.length;
                        } else if (event.key === 'Home') {
                            nextIndex = 0;
                        } else if (event.key === 'End') {
                            nextIndex = tabs.length - 1;
                        }

                        if (nextIndex === null) {
                            return;
                        }

                        event.preventDefault();
                        const nextTab = tabs[nextIndex];
                        activateSourceTab(shell, nextTab.getAttribute('data-brand-source-tab'));
                        nextTab.focus();
                    });
                });

                shell.dataset.brandSourceTabsBound = '1';
            }

            const requestedDefault = shell.getAttribute('data-default-source-tab');
            const firstKey = tabs[0].getAttribute('data-brand-source-tab');
            const defaultKey = tabs.some((tab) => tab.getAttribute('data-brand-source-tab') === requestedDefault)
                ? requestedDefault
                : firstKey;

            activateSourceTab(shell, defaultKey);
        });
    }

    function appendSubmitter(formData, submitter) {
        if (submitter && submitter.name) {
            formData.append(submitter.name, submitter.value || submitter.getAttribute('value') || '');
        }
    }

    function nativeSubmit(form, submitter) {
        if (submitter && submitter.name) {
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = submitter.name;
            hidden.value = submitter.value || submitter.getAttribute('value') || '';
            form.appendChild(hidden);
        }

        HTMLFormElement.prototype.submit.call(form);
    }

    async function replaceRegion(requestUrl, fetchOptions, activeState) {
        const currentRegion = getRegion();
        if (!currentRegion) {
            return false;
        }

        const requestId = ++latestRequestId;
        currentRegion.classList.add('is-loading');
        currentRegion.setAttribute('aria-busy', 'true');

        try {
            const response = await fetch(requestUrl, {
                ...fetchOptions,
                headers: {
                    Accept: 'text/html',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(fetchOptions.headers || {})
                }
            });

            if (!response.ok) {
                throw new Error(`Request failed with status ${response.status}`);
            }

            const html = await response.text();
            if (requestId !== latestRequestId) {
                return false;
            }

            const parsed = new DOMParser().parseFromString(html, 'text/html');
            const nextRegion = parsed.querySelector(REGION_SELECTOR);

            if (!nextRegion) {
                throw new Error('Updated candidature region was not found.');
            }

            currentRegion.replaceWith(nextRegion);

            if (activeState?.source) {
                const nextSourceShell = nextRegion.querySelector(SOURCE_SHELL_SELECTOR);
                if (nextSourceShell) {
                    nextSourceShell.setAttribute('data-default-source-tab', activeState.source);
                }
            }

            if (activeState?.workflow) {
                const nextSourcePanel = activeState.source
                    ? nextRegion.querySelector(`[data-brand-source-tab-panel="${activeState.source}"]`)
                    : null;
                const nextShell = (nextSourcePanel || nextRegion).querySelector('[data-offer-tab-shell]');
                if (nextShell) {
                    nextShell.setAttribute('data-default-tab', activeState.workflow);
                }
            }

            bindBrandSourceTabs(nextRegion);

            if (typeof window.initOfferTabs === 'function') {
                window.initOfferTabs(nextRegion);
            }

            if (typeof window.cre8CandidatureApplyTranslations === 'function') {
                window.setTimeout(() => window.cre8CandidatureApplyTranslations(nextRegion), 0);
            }

            window.dispatchEvent(new CustomEvent('candidatureListUpdated', {
                detail: { region: nextRegion }
            }));

            return true;
        } finally {
            const region = getRegion();
            if (region) {
                region.classList.remove('is-loading');
                region.setAttribute('aria-busy', 'false');
            }
        }
    }

    async function handleSaveToggle(form, submitter) {
        const formData = new FormData(form);
        appendSubmitter(formData, submitter);
        formData.set('ajax', '1');

        const requestUrl = new URL(form.action, window.location.href);
        const button = submitter || form.querySelector('button[type="submit"], input[type="submit"]');
        const activeState = getActiveTabState();

        if (button) {
            button.disabled = true;
        }

        try {
            await replaceRegion(requestUrl.toString(), {
                method: 'POST',
                body: formData
            }, activeState);
        } catch (error) {
            nativeSubmit(form, submitter);
        } finally {
            if (button) {
                button.disabled = false;
            }
        }
    }

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        const saveForm = form.closest(SAVE_FORM_SELECTOR);
        if (!saveForm) {
            return;
        }

        event.preventDefault();
        handleSaveToggle(saveForm, event.submitter);
    });

    document.addEventListener('DOMContentLoaded', () => bindBrandSourceTabs(document));
})();
