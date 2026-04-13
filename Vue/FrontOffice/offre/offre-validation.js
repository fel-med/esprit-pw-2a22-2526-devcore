document.addEventListener('DOMContentLoaded', function () {
    const validatorMap = {
        'brand-offer': {
            trackedSelectors: [
                'input[name="idCreateurCible"]',
                '#titre',
                '#objectif',
                '#budgetPropose',
                '#description',
                '#datePublication',
                '#dateLimite',
                '#raisonChoix',
                '#messagePersonnalise',
                '#attenteCollaboration'
            ],
            validate: validateBrandOfferForm
        },
        'creator-response': {
            trackedSelectors: ['#messageMotivation', '#budgetPropose', '#delaiPropose'],
            validate: validateCreatorResponseForm
        },
        'creator-filters': {
            trackedSelectors: ['#budgetFrom', '#budgetTo', '#dateLimite'],
            validate: validateBudgetFilterForm
        },
        'login': {
            trackedSelectors: ['#id'],
            validate: validateLoginForm
        }
    };

    document.querySelectorAll('form[data-module-validation]').forEach(function (form) {
        const validationType = form.dataset.moduleValidation;
        const config = validatorMap[validationType];

        if (!config) {
            return;
        }

        form.setAttribute('novalidate', 'novalidate');

        config.trackedSelectors.forEach(function (selector) {
            form.querySelectorAll(selector).forEach(function (field) {
                const refresh = function () {
                    if (form.dataset.validationSubmitted === '1') {
                        runValidation(form, config.validate, false);
                    }
                };

                field.addEventListener('input', refresh);
                field.addEventListener('change', refresh);
            });
        });

        form.addEventListener('submit', function (event) {
            form.dataset.validationSubmitted = '1';

            if (!runValidation(form, config.validate, true)) {
                event.preventDefault();
            }
        });
    });
});

function runValidation(form, validateCallback, focusFirstField) {
    clearValidationState(form);

    const errors = validateCallback(form);

    if (!errors.length) {
        return true;
    }

    renderValidationSummary(form, errors, focusFirstField);
    renderFieldErrors(errors);

    if (focusFirstField && errors[0].focusElement && typeof errors[0].focusElement.focus === 'function') {
        errors[0].focusElement.focus();
    }

    return false;
}

function validateBrandOfferForm(form) {
    const errors = [];
    const creatorInputs = Array.from(form.querySelectorAll('input[name="idCreateurCible"]:not([type="hidden"])'));
    const creatorValueInput = form.querySelector('#idCreateurCible') || form.querySelector('input[name="idCreateurCible"]');
    const creatorGrid = form.querySelector('#creatorGrid') || form.querySelector('.creator-grid');
    const creatorSearch = form.querySelector('#creatorSearch');
    const selectedCreator = creatorValueInput && creatorValueInput.value.trim() !== '';
    const titleInput = form.querySelector('#titre');
    const objectiveInput = form.querySelector('#objectif');
    const budgetInput = form.querySelector('#budgetPropose');
    const descriptionInput = form.querySelector('#description');
    const publicationInput = form.querySelector('#datePublication');
    const deadlineInput = form.querySelector('#dateLimite');
    const reasonInput = form.querySelector('#raisonChoix');
    const noteInput = form.querySelector('#messagePersonnalise');
    const expectationInput = form.querySelector('#attenteCollaboration');

    if (!selectedCreator) {
        errors.push(buildError('target-creator', 'Please select a targeted creator.', creatorSearch || creatorGrid || creatorValueInput || creatorInputs[0], creatorGrid));
    }

    validateRequiredTextField(titleInput, 'Offer title', 3, 150, errors);
    validateRequiredTextField(objectiveInput, 'Objective', 3, 255, errors);
    validateRequiredTextField(descriptionInput, 'Detailed description', 20, 4000, errors);
    validatePositiveAmount(budgetInput, 'Proposed budget', errors);
    validateDateField(publicationInput, 'Publication date', errors);
    validateDateField(deadlineInput, 'Deadline', errors);
    validateDateOrder(publicationInput, deadlineInput, errors);
    validateOptionalTextField(reasonInput, 'Why this creator', 600, errors);
    validateOptionalTextField(noteInput, 'Personal note', 600, errors);
    validateOptionalTextField(expectationInput, 'Expected collaboration fit', 600, errors);

    return errors;
}

function validateCreatorResponseForm(form) {
    const errors = [];
    const messageInput = form.querySelector('#messageMotivation');
    const budgetInput = form.querySelector('#budgetPropose');
    const delayInput = form.querySelector('#delaiPropose');

    validateRequiredTextField(messageInput, 'Message', 8, 600, errors);
    validatePositiveAmount(budgetInput, 'Your proposed budget', errors);
    validatePositiveInteger(delayInput, 'Your timeline in days', errors);

    return errors;
}

function validateBudgetFilterForm(form) {
    const errors = [];
    const budgetFromInput = form.querySelector('#budgetFrom');
    const budgetToInput = form.querySelector('#budgetTo');
    const dateInput = form.querySelector('#dateLimite');

    validateOptionalAmount(budgetFromInput, 'Budget from', errors);
    validateOptionalAmount(budgetToInput, 'Budget to', errors);
    validateOptionalDateField(dateInput, 'Deadline from', errors);

    const fromValue = parseNumberValue(budgetFromInput ? budgetFromInput.value : '');
    const toValue = parseNumberValue(budgetToInput ? budgetToInput.value : '');

    if (fromValue !== null && toValue !== null && fromValue > toValue) {
        errors.push(buildError('budget-range', '"Budget from" cannot be greater than "Budget to".', budgetToInput, getErrorAnchor(budgetToInput)));
    }

    return errors;
}

function validateLoginForm(form) {
    const errors = [];
    const idInput = form.querySelector('#id');

    if (idInput) {
        idInput.value = idInput.value.trim();
        if (idInput.value === '') {
            errors.push(buildError('login-id', 'ID is required.', idInput));
        } else if (!/^[0-9]+$/.test(idInput.value)) {
            errors.push(buildError('login-id', 'ID must be a whole number.', idInput));
        } else if (Number(idInput.value) <= 0) {
            errors.push(buildError('login-id', 'ID must be greater than zero.', idInput));
        }
    }

    return errors;
}

function validateRequiredTextField(field, label, minLength, maxLength, errors) {
    if (!field) {
        return;
    }

    field.value = field.value.trim();
    const value = field.value;

    if (value === '') {
        errors.push(buildError(field.id, label + ' is required.', field));
        return;
    }

    if (value.length < minLength) {
        errors.push(buildError(field.id, label + ' must contain at least ' + minLength + ' characters.', field));
        return;
    }

    if (maxLength && value.length > maxLength) {
        errors.push(buildError(field.id, label + ' must stay under ' + maxLength + ' characters.', field));
    }
}

function validateOptionalTextField(field, label, maxLength, errors) {
    if (!field) {
        return;
    }

    field.value = field.value.trim();
    if (field.value === '') {
        return;
    }

    if (maxLength && field.value.length > maxLength) {
        errors.push(buildError(field.id, label + ' must stay under ' + maxLength + ' characters.', field));
    }
}

function validatePositiveAmount(field, label, errors) {
    if (!field) {
        return;
    }

    field.value = field.value.trim();
    const value = parseNumberValue(field.value);

    if (field.value === '') {
        errors.push(buildError(field.id, label + ' is required.', field));
        return;
    }

    if (value === null) {
        errors.push(buildError(field.id, label + ' must be a valid number.', field));
        return;
    }

    if (value <= 0) {
        errors.push(buildError(field.id, label + ' must be greater than zero.', field));
    }
}

function validateOptionalAmount(field, label, errors) {
    if (!field) {
        return;
    }

    field.value = field.value.trim();
    if (field.value === '') {
        return;
    }

    const value = parseNumberValue(field.value);
    if (value === null) {
        errors.push(buildError(field.id, label + ' must be a valid number.', field));
        return;
    }

    if (value < 0) {
        errors.push(buildError(field.id, label + ' cannot be negative.', field));
    }
}

function validatePositiveInteger(field, label, errors) {
    if (!field) {
        return;
    }

    field.value = field.value.trim();
    if (field.value === '') {
        errors.push(buildError(field.id, label + ' is required.', field));
        return;
    }

    if (!/^[0-9]+$/.test(field.value)) {
        errors.push(buildError(field.id, label + ' must be a whole number.', field));
        return;
    }

    if (Number(field.value) < 1) {
        errors.push(buildError(field.id, label + ' must be at least 1 day.', field));
    }
}

function validateDateField(field, label, errors) {
    if (!field) {
        return;
    }

    field.value = field.value.trim();
    if (field.value === '') {
        errors.push(buildError(field.id, label + ' is required.', field));
        return;
    }

    if (!parseDateValue(field.value)) {
        errors.push(buildError(field.id, label + ' must be a valid date.', field));
    }
}

function validateOptionalDateField(field, label, errors) {
    if (!field) {
        return;
    }

    field.value = field.value.trim();
    if (field.value === '') {
        return;
    }

    if (!parseDateValue(field.value)) {
        errors.push(buildError(field.id, label + ' must be a valid date.', field));
    }
}

function validateDateOrder(publicationInput, deadlineInput, errors) {
    if (!publicationInput || !deadlineInput) {
        return;
    }

    const publicationDate = parseDateValue(publicationInput.value.trim());
    const deadlineDate = parseDateValue(deadlineInput.value.trim());

    if (!publicationDate || !deadlineDate) {
        return;
    }

    if (deadlineDate < publicationDate) {
        errors.push(buildError(deadlineInput.id, 'Deadline cannot be earlier than publication date.', deadlineInput));
    }
}

function buildError(key, message, focusElement, anchor) {
    return {
        key: key,
        message: message,
        focusElement: focusElement || null,
        anchor: anchor || getErrorAnchor(focusElement)
    };
}

function clearValidationState(form) {
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

    form.querySelectorAll('.validation-group-invalid').forEach(function (field) {
        field.classList.remove('validation-group-invalid');
    });
}

function renderValidationSummary(form, errors, shouldScroll) {
    const summary = document.createElement('div');
    summary.className = 'validation-summary';
    summary.innerHTML = '<strong>Please fix the highlighted fields.</strong>';

    const list = document.createElement('ul');
    errors.forEach(function (error) {
        const item = document.createElement('li');
        item.textContent = error.message;
        list.appendChild(item);
    });

    summary.appendChild(list);
    form.prepend(summary);

    if (shouldScroll) {
        summary.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

function renderFieldErrors(errors) {
    errors.forEach(function (error) {
        if (!error.anchor) {
            return;
        }

        if (error.focusElement && error.focusElement.classList) {
            error.focusElement.classList.add('is-invalid');
            error.focusElement.setAttribute('aria-invalid', 'true');
        }

        if (error.anchor !== error.focusElement && error.anchor.classList) {
            error.anchor.classList.add('validation-group-invalid');
        }

        const message = document.createElement('div');
        message.className = 'validation-feedback';
        message.textContent = error.message;
        error.anchor.insertAdjacentElement('afterend', message);
    });
}

function getErrorAnchor(field) {
    if (!field) {
        return null;
    }

    const inputGroup = field.closest('.input-group');
    if (inputGroup) {
        return inputGroup;
    }

    return field;
}

function parseNumberValue(value) {
    if (value === null || value === undefined) {
        return null;
    }

    const normalized = String(value).replace(',', '.').trim();
    if (normalized === '' || Number.isNaN(Number(normalized))) {
        return null;
    }

    return Number(normalized);
}

function parseDateValue(value) {
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
