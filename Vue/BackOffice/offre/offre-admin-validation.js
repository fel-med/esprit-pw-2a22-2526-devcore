document.addEventListener('DOMContentLoaded', function () {
    const filterForm = document.querySelector('form[data-module-validation="admin-filters"]');

    if (!filterForm) {
        return;
    }

    filterForm.setAttribute('novalidate', 'novalidate');

    ['#budgetFrom', '#budgetTo', '#dateLimite'].forEach(function (selector) {
        const field = filterForm.querySelector(selector);
        if (!field) {
            return;
        }

        const refresh = function () {
            if (filterForm.dataset.validationSubmitted === '1') {
                runAdminFilterValidation(filterForm, false);
            }
        };

        field.addEventListener('input', refresh);
        field.addEventListener('change', refresh);
    });

    filterForm.addEventListener('submit', function (event) {
        filterForm.dataset.validationSubmitted = '1';

        if (!runAdminFilterValidation(filterForm, true)) {
            event.preventDefault();
        }
    });
});

function runAdminFilterValidation(form, focusFirstField) {
    clearAdminValidationState(form);

    const errors = [];
    const budgetFromInput = form.querySelector('#budgetFrom');
    const budgetToInput = form.querySelector('#budgetTo');
    const dateInput = form.querySelector('#dateLimite');

    validateAdminOptionalAmount(budgetFromInput, 'Budget from', errors);
    validateAdminOptionalAmount(budgetToInput, 'Budget to', errors);
    validateAdminOptionalDate(dateInput, 'Deadline from', errors);

    const fromValue = parseAdminNumber(budgetFromInput ? budgetFromInput.value : '');
    const toValue = parseAdminNumber(budgetToInput ? budgetToInput.value : '');

    if (fromValue !== null && toValue !== null && fromValue > toValue) {
        errors.push(buildAdminError('budget-range', '"Budget from" cannot be greater than "Budget to".', budgetToInput));
    }

    if (!errors.length) {
        return true;
    }

    renderAdminSummary(form, errors);
    renderAdminFieldErrors(errors);

    if (focusFirstField && errors[0].field && typeof errors[0].field.focus === 'function') {
        errors[0].field.focus();
    }

    return false;
}

function validateAdminOptionalAmount(field, label, errors) {
    if (!field) {
        return;
    }

    field.value = field.value.trim();
    if (field.value === '') {
        return;
    }

    const value = parseAdminNumber(field.value);
    if (value === null) {
        errors.push(buildAdminError(field.id, label + ' must be a valid number.', field));
        return;
    }

    if (value < 0) {
        errors.push(buildAdminError(field.id, label + ' cannot be negative.', field));
    }
}

function validateAdminOptionalDate(field, label, errors) {
    if (!field) {
        return;
    }

    field.value = field.value.trim();
    if (field.value === '') {
        return;
    }

    if (!parseAdminDate(field.value)) {
        errors.push(buildAdminError(field.id, label + ' must be a valid date.', field));
    }
}

function buildAdminError(key, message, field) {
    return {
        key: key,
        message: message,
        field: field,
        anchor: field
    };
}

function clearAdminValidationState(form) {
    const summary = form.querySelector('.validation-summary');
    if (summary) {
        summary.remove();
    }

    form.querySelectorAll('.validation-feedback').forEach(function (node) {
        node.remove();
    });

    form.querySelectorAll('.is-invalid').forEach(function (field) {
        field.classList.remove('is-invalid');
        field.removeAttribute('aria-invalid');
    });
}

function renderAdminSummary(form, errors) {
    const summary = document.createElement('div');
    summary.className = 'validation-summary';
    summary.innerHTML = '<strong>Please review the filters.</strong>';

    const list = document.createElement('ul');
    errors.forEach(function (error) {
        const item = document.createElement('li');
        item.textContent = error.message;
        list.appendChild(item);
    });

    summary.appendChild(list);
    form.prepend(summary);
}

function renderAdminFieldErrors(errors) {
    errors.forEach(function (error) {
        if (!error.anchor) {
            return;
        }

        error.field.classList.add('is-invalid');
        error.field.setAttribute('aria-invalid', 'true');

        const message = document.createElement('div');
        message.className = 'validation-feedback';
        message.textContent = error.message;
        error.anchor.insertAdjacentElement('afterend', message);
    });
}

function parseAdminNumber(value) {
    const normalized = String(value || '').replace(',', '.').trim();
    if (normalized === '' || Number.isNaN(Number(normalized))) {
        return null;
    }

    return Number(normalized);
}

function parseAdminDate(value) {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) {
        return null;
    }

    const parts = value.split('-').map(function (item) {
        return Number(item);
    });
    const year = parts[0];
    const month = parts[1];
    const day = parts[2];
    const date = new Date(Date.UTC(year, month - 1, day));

    if (
        Number.isNaN(date.getTime()) ||
        date.getUTCFullYear() !== year ||
        date.getUTCMonth() !== month - 1 ||
        date.getUTCDate() !== day
    ) {
        return null;
    }

    return date;
}
