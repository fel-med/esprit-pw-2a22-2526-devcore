(() => {
    const CREATOR_REGION_SELECTOR = '[data-creator-live-region]';
    const CREATOR_FILTER_SELECTOR = 'form[data-creator-filter-form]';
    const SAVE_TOGGLE_SELECTOR = 'form[data-save-toggle-form]';
    const RESET_LINK_SELECTOR = 'a[data-creator-reset-link]';
    let latestRequestId = 0;

    function getCreatorRegion() {
        return document.querySelector(CREATOR_REGION_SELECTOR);
    }

    function setCreatorRegionLoading(region, isLoading) {
        if (!region) {
            return;
        }

        region.classList.toggle('is-loading', isLoading);
        region.setAttribute('aria-busy', isLoading ? 'true' : 'false');
    }

    function getActiveCreatorTabKey(root = document) {
        const activeTab = root.querySelector('[data-offer-tab].is-active, [data-offer-tab][aria-selected="true"]');
        return activeTab ? activeTab.getAttribute('data-offer-tab') : null;
    }

    function normalizeFilterParams(form) {
        const params = new URLSearchParams();
        const formData = new FormData(form);

        for (const [key, rawValue] of formData.entries()) {
            const value = typeof rawValue === 'string' ? rawValue.trim() : rawValue;

            if (value === '') {
                continue;
            }

            params.set(key, value);
        }

        return params;
    }

    async function replaceCreatorRegion(requestUrl, fetchOptions = {}, options = {}) {
        const currentRegion = getCreatorRegion();

        if (!currentRegion) {
            return false;
        }

        const requestId = ++latestRequestId;
        const activeTab = options.activeTab || getActiveCreatorTabKey();
        setCreatorRegionLoading(currentRegion, true);

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

            const documentParser = new DOMParser();
            const parsedDocument = documentParser.parseFromString(html, 'text/html');
            const nextRegion = parsedDocument.querySelector(CREATOR_REGION_SELECTOR);

            if (!nextRegion) {
                throw new Error('Updated creator region was not found in the response.');
            }

            currentRegion.replaceWith(nextRegion);

            if (activeTab) {
                const nextTabShell = nextRegion.querySelector('[data-offer-tab-shell]');

                if (nextTabShell) {
                    nextTabShell.setAttribute('data-default-tab', activeTab);
                }
            }

            if (options.historyMode === 'push' && options.historyUrl) {
                window.history.pushState({ creatorLive: true }, '', options.historyUrl);
            } else if (options.historyMode === 'replace' && options.historyUrl) {
                window.history.replaceState({ creatorLive: true }, '', options.historyUrl);
            }

            if (typeof window.initOfferTabs === 'function') {
                window.initOfferTabs(nextRegion);
            }

            if (typeof window.initializeModuleValidation === 'function') {
                window.initializeModuleValidation(nextRegion);
            }

            if (typeof window.cre8OfferApplyTranslations === 'function') {
                window.setTimeout(() => window.cre8OfferApplyTranslations(nextRegion), 0);
            }

            window.dispatchEvent(new CustomEvent('creatorListUpdated', {
                detail: { region: nextRegion }
            }));

            return true;
        } finally {
            setCreatorRegionLoading(getCreatorRegion(), false);
        }
    }

    async function handleFilterSubmit(form) {
        const params = normalizeFilterParams(form);
        const requestUrl = new URL(form.action, window.location.href);
        requestUrl.search = params.toString();
        requestUrl.searchParams.set('ajax', '1');

        const historyUrl = new URL(requestUrl);
        historyUrl.searchParams.delete('ajax');

        try {
            await replaceCreatorRegion(requestUrl.toString(), {}, {
                activeTab: getActiveCreatorTabKey(),
                historyMode: 'push',
                historyUrl: historyUrl.toString()
            });
        } catch (error) {
            window.location.assign(historyUrl.toString());
        }
    }

    function appendSubmitterToFormData(formData, submitter) {
        if (!submitter || !submitter.name) {
            return;
        }

        formData.append(submitter.name, submitter.value || submitter.getAttribute('value') || '');
    }

    function submitFormWithSubmitter(form, submitter) {
        if (submitter && submitter.name) {
            const hiddenSubmitter = document.createElement('input');
            hiddenSubmitter.type = 'hidden';
            hiddenSubmitter.name = submitter.name;
            hiddenSubmitter.value = submitter.value || submitter.getAttribute('value') || '';
            form.appendChild(hiddenSubmitter);
        }

        HTMLFormElement.prototype.submit.call(form);
    }

    async function handleSaveToggle(form, submitter) {
        const effectiveSubmitter = submitter || form.querySelector('button[type="submit"], input[type="submit"]');
        const formData = new FormData(form);
        appendSubmitterToFormData(formData, effectiveSubmitter);
        formData.set('ajax', '1');

        const requestUrl = new URL(form.action, window.location.href);
        const submitButton = effectiveSubmitter;

        if (submitButton) {
            submitButton.disabled = true;
        }

        try {
            await replaceCreatorRegion(requestUrl.toString(), {
                method: 'POST',
                body: formData
            }, {
                activeTab: getActiveCreatorTabKey()
            });
        } catch (error) {
            submitFormWithSubmitter(form, effectiveSubmitter);
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
            }
        }
    }

    async function handleResetLink(link) {
        const requestUrl = new URL(link.href, window.location.href);
        requestUrl.searchParams.set('ajax', '1');

        try {
            await replaceCreatorRegion(requestUrl.toString(), {}, {
                activeTab: getActiveCreatorTabKey(),
                historyMode: 'push',
                historyUrl: link.href
            });
        } catch (error) {
            window.location.assign(link.href);
        }
    }

    function shouldIgnoreModifiedClick(event, link) {
        return event.defaultPrevented
            || event.button !== 0
            || event.metaKey
            || event.ctrlKey
            || event.shiftKey
            || event.altKey
            || link.target === '_blank';
    }

    document.addEventListener('submit', (event) => {
        const targetForm = event.target;

        if (!(targetForm instanceof HTMLFormElement)) {
            return;
        }

        const filterForm = targetForm.closest(CREATOR_FILTER_SELECTOR);
        if (filterForm) {
            if (event.defaultPrevented) {
                return;
            }

            event.preventDefault();
            handleFilterSubmit(filterForm);
            return;
        }

        const saveToggleForm = targetForm.closest(SAVE_TOGGLE_SELECTOR);
        if (!saveToggleForm) {
            return;
        }

        event.preventDefault();
        handleSaveToggle(saveToggleForm, event.submitter);
    });

    document.addEventListener('click', (event) => {
        const resetLink = event.target.closest(RESET_LINK_SELECTOR);

        if (!resetLink || shouldIgnoreModifiedClick(event, resetLink)) {
            return;
        }

        event.preventDefault();
        handleResetLink(resetLink);
    });

    window.addEventListener('popstate', () => {
        if (!getCreatorRegion()) {
            return;
        }

        const requestUrl = new URL(window.location.href);
        requestUrl.searchParams.set('ajax', '1');

        replaceCreatorRegion(requestUrl.toString(), {}, {
            activeTab: getActiveCreatorTabKey()
        }).catch(() => {
            window.location.reload();
        });
    });
})();
