(() => {
    const dialog = document.getElementById('candidatureDeleteDialog');
    const confirmButton = document.getElementById('candidatureDeleteDialogConfirm');
    const titleNode = document.getElementById('candidatureDeleteDialogTitleText');
    const contextNode = document.getElementById('candidatureDeleteDialogContext');

    if (!dialog || !confirmButton || !titleNode || !contextNode) {
        return;
    }

    let activeForm = null;
    let lastFocusedElement = null;

    function openDialog(form) {
        activeForm = form;
        lastFocusedElement = document.activeElement;

        const title = (form.dataset.deleteTitle || '').trim();
        const context = (form.dataset.deleteCreator || '').trim();

        titleNode.textContent = title || 'Selected candidature';
        contextNode.textContent = context
            ? `Source context: ${context}`
            : 'The selected candidature will be removed from the dashboard pipeline.';

        if (typeof dialog.showModal === 'function') {
            if (!dialog.open) {
                dialog.showModal();
            }
        } else {
            dialog.setAttribute('open', 'open');
        }

        window.requestAnimationFrame(() => {
            confirmButton.focus();
        });
    }

    function closeDialog() {
        if (typeof dialog.close === 'function' && dialog.open) {
            dialog.close();
        } else {
            dialog.removeAttribute('open');
        }

        activeForm = null;

        if (lastFocusedElement && typeof lastFocusedElement.focus === 'function') {
            lastFocusedElement.focus();
        }
    }

    document.addEventListener('submit', (event) => {
        const form = event.target.closest('form[data-delete-confirm]');
        if (!form) {
            return;
        }

        if (form.dataset.deleteConfirmed === 'true') {
            delete form.dataset.deleteConfirmed;
            return;
        }

        event.preventDefault();
        openDialog(form);
    });

    confirmButton.addEventListener('click', () => {
        if (!activeForm) {
            return;
        }

        const formToSubmit = activeForm;
        const submitButton = formToSubmit.querySelector('button[type="submit"]');
        formToSubmit.dataset.deleteConfirmed = 'true';
        closeDialog();

        if (typeof formToSubmit.requestSubmit === 'function') {
            formToSubmit.requestSubmit(submitButton || undefined);
            return;
        }

        formToSubmit.submit();
    });

    dialog.addEventListener('click', (event) => {
        if (event.target === dialog || event.target.closest('[data-delete-close]')) {
            closeDialog();
        }
    });

    dialog.addEventListener('cancel', (event) => {
        event.preventDefault();
        closeDialog();
    });
})();
