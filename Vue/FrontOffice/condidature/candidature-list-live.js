(() => {
    const REGION_SELECTOR = '[data-candidature-live-region]';
    const SAVE_FORM_SELECTOR = 'form[data-candidature-save-toggle-form]';
    let latestRequestId = 0;

    function getRegion() {
        return document.querySelector(REGION_SELECTOR);
    }

    function getActiveTabKey(root = document) {
        const activeTab = root.querySelector('[data-offer-tab].is-active, [data-offer-tab][aria-selected="true"]');
        return activeTab ? activeTab.getAttribute('data-offer-tab') : null;
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

    async function replaceRegion(requestUrl, fetchOptions, activeTab) {
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

            if (activeTab) {
                const nextShell = nextRegion.querySelector('[data-offer-tab-shell]');
                if (nextShell) {
                    nextShell.setAttribute('data-default-tab', activeTab);
                }
            }

            if (typeof window.initOfferTabs === 'function') {
                window.initOfferTabs(nextRegion);
            }

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
        const activeTab = getActiveTabKey();

        if (button) {
            button.disabled = true;
        }

        try {
            await replaceRegion(requestUrl.toString(), {
                method: 'POST',
                body: formData
            }, activeTab);
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
})();
