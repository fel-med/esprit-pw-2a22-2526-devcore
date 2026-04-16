(() => {
    const dialog = document.getElementById('offerDeleteDialog');
    const confirmButton = document.getElementById('offerDeleteDialogConfirm');
    const offerNode = document.getElementById('offerDeleteDialogOffer');
    const creatorNode = document.getElementById('offerDeleteDialogCreator');
    const forms = Array.from(document.querySelectorAll('form[data-delete-confirm]'));

    if (!dialog || !confirmButton || !offerNode || !creatorNode || !forms.length) {
        return;
    }

    let activeForm = null;
    let lastFocusedElement = null;

    function openDialog(form) {
        activeForm = form;
        lastFocusedElement = document.activeElement;

        const title = (form.dataset.deleteTitle || '').trim();
        const creator = (form.dataset.deleteCreator || '').trim();

        offerNode.textContent = title || 'Selected offer';
        creatorNode.textContent = creator ? `Target creator: ${creator}` : 'The selected offer will be removed from the dashboard pipeline.';

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

    forms.forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.dataset.deleteConfirmed === 'true') {
                delete form.dataset.deleteConfirmed;
                return;
            }

            event.preventDefault();
            openDialog(form);
        });
    });

    confirmButton.addEventListener('click', () => {
        if (!activeForm) {
            return;
        }

        const formToSubmit = activeForm;
        formToSubmit.dataset.deleteConfirmed = 'true';
        closeDialog();
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
