document.addEventListener('DOMContentLoaded', initializeModuleValidation);

const MODULE_VALIDATORS = {
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

// ---------------------------------------------------------------------------
// 1. Validation bootstrap
// ---------------------------------------------------------------------------

function initializeModuleValidation() {
    document.querySelectorAll('form[data-module-validation]').forEach(function (form) {
        const validationType = form.dataset.moduleValidation;
        const config = MODULE_VALIDATORS[validationType];

        if (!config) {
            return;
        }

        form.setAttribute('novalidate', 'novalidate');
        attachLiveValidation(form, config);
        attachSubmitIntentTracking(form);
        attachSubmitValidation(form, config);
        restoreServerErrorFocus(form);
    });
}

function attachSubmitIntentTracking(form) {
    form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(function (submitter) {
        const syncIntent = function () {
            form.dataset.validationIntent = resolveSubmitIntent(submitter);
            form.__lastSubmitter = submitter;
        };

        submitter.addEventListener('click', syncIntent);
        submitter.addEventListener('pointerdown', syncIntent);
        submitter.addEventListener('mousedown', syncIntent);
        submitter.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                syncIntent();
            }
        });
    });
}

function attachLiveValidation(form, config) {
    config.trackedSelectors.forEach(function (selector) {
        form.querySelectorAll(selector).forEach(function (field) {
            const refresh = function () {
                if (form.dataset.validationSubmitted === '1') {
                    runValidation(form, config.validate, false, getCurrentValidationContext(form));
                }
            };

            field.addEventListener('input', refresh);
            field.addEventListener('change', refresh);
        });
    });
}

function attachSubmitValidation(form, config) {
    form.addEventListener('submit', function (event) {
        form.dataset.validationSubmitted = '1';
        form.dataset.validationIntent = resolveSubmitIntent(event.submitter || form.__lastSubmitter || document.activeElement);

        if (!runValidation(form, config.validate, true, getCurrentValidationContext(form))) {
            event.preventDefault();
        }
    });
}

function getCurrentValidationContext(form) {
    return {
        intent: form.dataset.validationIntent || 'publish'
    };
}

function resolveSubmitIntent(submitter) {
    if (!submitter) {
        return 'publish';
    }

    const intent = submitter.dataset.validationIntent || submitter.value || submitter.getAttribute('value') || 'publish';
    return intent === 'draft' ? 'draft' : 'publish';
}

function runValidation(form, validateCallback, focusFirstField, context) {
    clearValidationState(form);
    form.dataset.serverErrorFocus = '';

    const errors = validateCallback(form, context || getCurrentValidationContext(form));

    if (!errors.length) {
        return true;
    }

    renderValidationSummary(form, errors, focusFirstField);
    renderFieldErrors(errors);

    if (focusFirstField) {
        revealFirstError(errors[0]);
    }

    return false;
}

// ---------------------------------------------------------------------------
// 2. Form-level validation rules
// ---------------------------------------------------------------------------

function validateBrandOfferForm(form, context) {
    const errors = [];
    const fields = getBrandOfferFields(form);
    const validationIntent = context && context.intent === 'draft' ? 'draft' : 'publish';

    validateDraftStartingPoint(fields, errors, validationIntent);
    validateTargetCreatorSelection(fields, errors, validationIntent);
    validateBrandOfferTextRules(fields, errors, validationIntent);
    validateBrandOfferDateRules(fields, errors, validationIntent);
    validateBrandOfferOptionalBriefRules(fields, errors, validationIntent);

    return errors;
}

function getBrandOfferFields(form) {
    return {
        creatorInputs: Array.from(form.querySelectorAll('input[name="idCreateurCible"]:not([type="hidden"])')),
        creatorValueInput: form.querySelector('#idCreateurCible') || form.querySelector('input[name="idCreateurCible"]'),
        creatorGrid: form.querySelector('#creatorGrid') || form.querySelector('.creator-grid'),
        creatorSearch: form.querySelector('#creatorSearch'),
        titleInput: form.querySelector('#titre'),
        objectiveInput: form.querySelector('#objectif'),
        budgetInput: form.querySelector('#budgetPropose'),
        descriptionInput: form.querySelector('#description'),
        publicationInput: form.querySelector('#datePublication'),
        deadlineInput: form.querySelector('#dateLimite'),
        reasonInput: form.querySelector('#raisonChoix'),
        noteInput: form.querySelector('#messagePersonnalise'),
        expectationInput: form.querySelector('#attenteCollaboration')
    };
}

function validateDraftStartingPoint(fields, errors, validationIntent) {
    if (validationIntent !== 'draft') {
        return;
    }

    const selectedCreator = fields.creatorValueInput && fields.creatorValueInput.value.trim() !== '';
    const hasTypedContent = [
        fields.titleInput,
        fields.objectiveInput,
        fields.budgetInput,
        fields.descriptionInput,
        fields.reasonInput,
        fields.noteInput,
        fields.expectationInput
    ].some(function (field) {
        return field && field.value.trim() !== '';
    });

    if (!selectedCreator && !hasTypedContent) {
        errors.push(
            buildError(
                'draft-start',
                'Start the draft by selecting a creator or filling at least one offer field.',
                fields.titleInput || fields.creatorSearch || fields.creatorGrid || fields.creatorValueInput || fields.creatorInputs[0],
                fields.titleInput || fields.creatorGrid || fields.creatorSearch
            )
        );
    }
}

function validateTargetCreatorSelection(fields, errors, validationIntent) {
    if (validationIntent === 'draft') {
        return;
    }

    const selectedCreator = fields.creatorValueInput && fields.creatorValueInput.value.trim() !== '';

    if (!selectedCreator) {
        errors.push(
            buildError(
                'target-creator',
                'Please select a targeted creator.',
                fields.creatorSearch || fields.creatorGrid || fields.creatorValueInput || fields.creatorInputs[0],
                fields.creatorGrid
            )
        );
    }
}

function validateBrandOfferTextRules(fields, errors, validationIntent) {
    if (validationIntent === 'draft') {
        return;
    }

    validateRequiredTextField(fields.titleInput, 'Offer title', 3, 150, errors);
    validateRequiredTextField(fields.objectiveInput, 'Objective', 3, 255, errors);
    validateRequiredTextField(fields.descriptionInput, 'Detailed description', 20, 4000, errors);
    validatePositiveAmount(fields.budgetInput, 'Proposed budget', errors);
}

function validateBrandOfferDateRules(fields, errors, validationIntent) {
    if (validationIntent === 'draft') {
        return;
    }

    validateDateField(fields.publicationInput, 'Publication date', errors);
    validateDateField(fields.deadlineInput, 'Deadline', errors);
    validateDateNotPast(fields.publicationInput, 'Publication date', errors);
    validateDateNotPast(fields.deadlineInput, 'Deadline', errors);

    // Current JS behavior:
    // - confirms both dates are filled in for publish
    // - confirms each date is valid
    // - confirms entered dates are not in the past
    // - confirms deadline is later than publication date
    validateDateOrder(fields.publicationInput, fields.deadlineInput, errors);
}

function validateBrandOfferOptionalBriefRules(fields, errors, validationIntent) {
    if (validationIntent === 'draft') {
        return;
    }

    validateOptionalTextField(fields.reasonInput, 'Why this creator', 600, errors);
    validateOptionalTextField(fields.noteInput, 'Personal note', 600, errors);
    validateOptionalTextField(fields.expectationInput, 'Expected collaboration fit', 600, errors);
}

function validateCreatorResponseForm(form) {
    const errors = [];
    const fields = {
        messageInput: form.querySelector('#messageMotivation'),
        budgetInput: form.querySelector('#budgetPropose'),
        delayInput: form.querySelector('#delaiPropose')
    };

    validateRequiredTextField(fields.messageInput, 'Message', 8, 600, errors);
    validatePositiveAmount(fields.budgetInput, 'Your proposed budget', errors);
    validatePositiveInteger(fields.delayInput, 'Your timeline in days', errors);

    return errors;
}

function validateBudgetFilterForm(form) {
    const errors = [];
    const fields = {
        budgetFromInput: form.querySelector('#budgetFrom'),
        budgetToInput: form.querySelector('#budgetTo'),
        dateInput: form.querySelector('#dateLimite')
    };

    validateOptionalAmount(fields.budgetFromInput, 'Budget from', errors);
    validateOptionalAmount(fields.budgetToInput, 'Budget to', errors);
    validateOptionalDateField(fields.dateInput, 'Deadline from', errors);
    validateBudgetRange(fields.budgetFromInput, fields.budgetToInput, errors);

    return errors;
}

function validateBudgetRange(budgetFromInput, budgetToInput, errors) {
    const fromValue = parseNumberValue(budgetFromInput ? budgetFromInput.value : '');
    const toValue = parseNumberValue(budgetToInput ? budgetToInput.value : '');

    if (fromValue !== null && toValue !== null && fromValue > toValue) {
        errors.push(buildError('budget-range', '"Budget from" cannot be greater than "Budget to".', budgetToInput, getErrorAnchor(budgetToInput)));
    }
}

function validateLoginForm(form) {
    const errors = [];
    const idInput = form.querySelector('#id');

    if (!idInput) {
        return errors;
    }

    idInput.value = idInput.value.trim();

    if (idInput.value === '') {
        errors.push(buildError('login-id', 'ID is required.', idInput));
    } else if (!/^[0-9]+$/.test(idInput.value)) {
        errors.push(buildError('login-id', 'ID must be a whole number.', idInput));
    } else if (Number(idInput.value) <= 0) {
        errors.push(buildError('login-id', 'ID must be greater than zero.', idInput));
    }

    return errors;
}

// ---------------------------------------------------------------------------
// 3. Reusable field-level rules
// ---------------------------------------------------------------------------

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

function validateOptionalPositiveAmount(field, label, errors) {
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

    if (value <= 0) {
        errors.push(buildError(field.id, label + ' must be greater than zero.', field));
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

    if (deadlineDate <= publicationDate) {
        errors.push(buildError(deadlineInput.id, 'Deadline must be later than the publication date.', deadlineInput));
    }
}

function validateDateNotPast(field, label, errors) {
    if (!field) {
        return;
    }

    const fieldValue = field.value.trim();
    const parsedDate = parseDateValue(fieldValue);
    if (!parsedDate) {
        return;
    }

    const today = getTodayDate();
    if (parsedDate < today) {
        errors.push(buildError(field.id, label + ' cannot be in the past.', field));
    }
}

// ---------------------------------------------------------------------------
// 4. Error object creation and UI feedback
// ---------------------------------------------------------------------------

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

function revealFirstError(error) {
    if (!error) {
        return;
    }

    const scrollTarget = error.focusElement || error.anchor;
    if (scrollTarget && typeof scrollTarget.scrollIntoView === 'function') {
        scrollTarget.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });
    }

    if (error.focusElement && typeof error.focusElement.focus === 'function') {
        try {
            error.focusElement.focus({ preventScroll: true });
        } catch (focusError) {
            error.focusElement.focus();
        }
    }
}

function restoreServerErrorFocus(form) {
    const targetId = (form.dataset.serverErrorFocus || '').trim();
    if (!targetId) {
        return;
    }

    const preferredFocus = form.querySelector('#' + escapeSelector(targetId)) || document.getElementById(targetId);
    if (!preferredFocus) {
        return;
    }

    const resolvedAnchor = preferredFocus.type === 'hidden'
        ? getErrorAnchor(form.querySelector('#creatorSearch') || form.querySelector('#creatorGrid') || preferredFocus)
        : getErrorAnchor(preferredFocus);
    const focusElement = preferredFocus.type === 'hidden'
        ? (form.querySelector('#creatorSearch') || preferredFocus)
        : preferredFocus;

    window.setTimeout(function () {
        revealFirstError({
            focusElement: focusElement,
            anchor: resolvedAnchor || focusElement
        });
    }, 80);
}

// ---------------------------------------------------------------------------
// 5. Parsing helpers
// ---------------------------------------------------------------------------

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

function getTodayDate() {
    const now = new Date();
    return new Date(Date.UTC(now.getFullYear(), now.getMonth(), now.getDate()));
}

function escapeSelector(value) {
    if (window.CSS && typeof window.CSS.escape === 'function') {
        return window.CSS.escape(value);
    }

    return String(value).replace(/([ #;?%&,.+*~':"!^$[\]()=>|\/@])/g, '\\$1');
}
