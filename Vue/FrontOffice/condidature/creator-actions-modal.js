(() => {
  const overlay = document.querySelector("[data-creator-response-modal-overlay]");

  if (!overlay) {
    return;
  }

  const panels = new Map(
    Array.from(overlay.querySelectorAll("[data-creator-response-modal-panel]")).map((panel) => [
      panel.dataset.creatorResponseModalPanel,
      panel,
    ]),
  );
  const triggers = Array.from(document.querySelectorAll("[data-creator-response-modal-trigger]"));
  const closeButtons = Array.from(overlay.querySelectorAll("[data-creator-response-modal-close]"));
  const modalForms = Array.from(overlay.querySelectorAll("form"));
  let activePanelName = "";
  let lastFocusedElement = null;

  if (overlay.parentElement !== document.body) {
    document.body.appendChild(overlay);
  }

  function hidePanels() {
    panels.forEach((panel) => {
      panel.setAttribute("hidden", "hidden");
    });
  }

  function focusFirstField(panel) {
    const focusTarget = panel.querySelector(
      "textarea, input:not([type='hidden']), select, button[type='submit'], [data-creator-response-modal-close]",
    );

    if (focusTarget && typeof focusTarget.focus === "function") {
      focusTarget.focus({ preventScroll: true });
    }
  }

  function todayIsoDate() {
    const today = new Date();
    const month = String(today.getMonth() + 1).padStart(2, "0");
    const day = String(today.getDate()).padStart(2, "0");

    return `${today.getFullYear()}-${month}-${day}`;
  }

  function getField(form, name) {
    return form.querySelector(`[name="${name}"]`);
  }

  function fieldValue(form, name) {
    const field = getField(form, name);
    return field ? field.value.trim() : "";
  }

  function isFinalSubmit(submitter) {
    const intent = submitter?.value || submitter?.getAttribute("value") || "";
    return intent !== "draft";
  }

  function getResponseMode(form) {
    return fieldValue(form, "responseMode");
  }

  function isPositiveNumber(value) {
    return value !== "" && !Number.isNaN(Number(value)) && Number(value) > 0;
  }

  function isPositiveInteger(value) {
    return /^[0-9]+$/.test(value) && Number(value) > 0;
  }

  function isValidUrl(value) {
    if (value === "") {
      return true;
    }

    try {
      const parsedUrl = new URL(value);
      return parsedUrl.protocol === "http:" || parsedUrl.protocol === "https:";
    } catch (error) {
      return false;
    }
  }

  function addError(errors, form, name, message) {
    const field = getField(form, name);

    if (field) {
      errors.push({ field, message });
    }
  }

  function validateCreatorActionForm(form, options = {}) {
    const errors = [];
    const strictRequired = options.strictRequired !== false;
    const mode = getResponseMode(form);
    const dateValue = fieldValue(form, "dateDisponibilite");
    const delayValue = fieldValue(form, "delaiPropose");
    const messageValue = fieldValue(form, "messageMotivation");
    const budgetValue = fieldValue(form, "budgetPropose");
    const refusalValue = fieldValue(form, "motifRefus");
    const portfolioValue = fieldValue(form, "portfolioUrl");
    const conditionsValue = fieldValue(form, "conditionsCreateur");
    const cvValue = fieldValue(form, "cvPath");

    if (messageValue.length > 2500) {
      addError(errors, form, "messageMotivation", "Your message must stay under 2500 characters.");
    }

    if (conditionsValue.length > 2000) {
      addError(errors, form, "conditionsCreateur", "Creator terms must stay under 2000 characters.");
    }

    if (cvValue.length > 255) {
      addError(errors, form, "cvPath", "The CV field must stay under 255 characters.");
    }

    if (portfolioValue.length > 255) {
      addError(errors, form, "portfolioUrl", "The portfolio URL must stay under 255 characters.");
    } else if (!isValidUrl(portfolioValue)) {
      addError(errors, form, "portfolioUrl", "Enter a valid portfolio URL starting with http:// or https://.");
    }

    if (refusalValue.length > 1500) {
      addError(errors, form, "motifRefus", "The refusal reason must stay under 1500 characters.");
    }

    if (dateValue !== "" && dateValue < todayIsoDate()) {
      addError(errors, form, "dateDisponibilite", "Availability start date cannot be in the past.");
    }

    if (delayValue !== "" && !isPositiveInteger(delayValue)) {
      addError(errors, form, "delaiPropose", "Enter a valid timeline in days.");
    }

    if (budgetValue !== "" && !isPositiveNumber(budgetValue)) {
      addError(errors, form, "budgetPropose", "Enter a valid proposed budget greater than 0.");
    }

    if (!strictRequired) {
      return errors;
    }

    if (mode === "decline") {
      if (refusalValue === "") {
        addError(errors, form, "motifRefus", "Add a refusal reason before sending the decline.");
      }

      return errors;
    }

    if (mode === "accept") {
      if (dateValue === "") {
        addError(errors, form, "dateDisponibilite", "Choose your availability start date.");
      }

      if (delayValue === "") {
        addError(errors, form, "delaiPropose", "Enter a valid delivery delay in days.");
      }

      if (messageValue === "") {
        addError(errors, form, "messageMotivation", "Add a short creator message before sending the acceptance.");
      }

      return errors;
    }

    if (mode === "negotiate") {
      const hasAvailabilityField = !!getField(form, "dateDisponibilite");

      if (hasAvailabilityField) {
        if (dateValue === "") {
          addError(errors, form, "dateDisponibilite", "Choose your availability start date.");
        }
      }

      if (messageValue === "") {
        addError(errors, form, "messageMotivation", "Add a negotiation message before sending this response.");
      }

      if (delayValue === "") {
        addError(errors, form, "delaiPropose", "Enter a valid proposed timeline in days.");
      }

      if (budgetValue === "") {
        addError(errors, form, "budgetPropose", "Enter a valid proposed budget greater than 0.");
      }
    }

    return errors;
  }

  function clearValidation(form) {
    form.querySelectorAll(".modal-validation-summary").forEach((summary) => summary.remove());
    form.querySelectorAll(".modal-field-error").forEach((error) => error.remove());
    form.querySelectorAll(".is-invalid").forEach((field) => {
      field.classList.remove("is-invalid");
      field.removeAttribute("aria-invalid");
    });
  }

  function getFieldErrorAnchor(field) {
    return field.closest(".input-group") || field;
  }

  function renderFieldErrors(errors, touchedOnly = false) {
    errors.forEach((error) => {
      if (touchedOnly && error.field.dataset.validationTouched !== "1") {
        return;
      }

      const anchor = getFieldErrorAnchor(error.field);
      const message = document.createElement("div");
      message.className = "modal-field-error";
      message.textContent = error.message;
      error.field.classList.add("is-invalid");
      error.field.setAttribute("aria-invalid", "true");
      anchor.insertAdjacentElement("afterend", message);
    });
  }

  function renderSummary(form, errors) {
    if (!errors.length) {
      return;
    }

    const summary = document.createElement("div");
    summary.className = "modal-validation-summary";
    summary.setAttribute("role", "alert");
    summary.innerHTML = `<strong>Please fix the highlighted fields.</strong><ul>${errors
      .map((error) => `<li>${error.message}</li>`)
      .join("")}</ul>`;

    const body = form.querySelector(".response-modal-body") || form;
    body.insertBefore(summary, body.firstElementChild || null);
  }

  function revealFirstError(error) {
    if (!error || !error.field) {
      return;
    }

    const body = error.field.closest(".response-modal-body");

    if (body) {
      const bodyRect = body.getBoundingClientRect();
      const fieldRect = error.field.getBoundingClientRect();
      body.scrollTo({
        top: body.scrollTop + fieldRect.top - bodyRect.top - 90,
        behavior: "smooth",
      });
    }

    window.setTimeout(() => error.field.focus({ preventScroll: true }), 180);
  }

  function runValidation(form, options = {}) {
    clearValidation(form);
    const errors = validateCreatorActionForm(form, options);
    const visibleErrors = options.touchedOnly
      ? errors.filter((error) => error.field.dataset.validationTouched === "1")
      : errors;

    renderFieldErrors(visibleErrors, false);

    if (!options.touchedOnly) {
      renderSummary(form, errors);

      if (errors.length) {
        revealFirstError(errors[0]);
      }
    }

    return errors.length === 0;
  }

  function initializeValidation() {
    modalForms.forEach((form) => {
      form.setAttribute("novalidate", "novalidate");

      form.querySelectorAll("input:not([type='hidden']), textarea, select").forEach((field) => {
        field.addEventListener("blur", () => {
          field.dataset.validationTouched = "1";
          runValidation(form, { touchedOnly: true, strictRequired: true });
        });

        field.addEventListener("input", () => {
          if (field.dataset.validationTouched === "1") {
            runValidation(form, { touchedOnly: true, strictRequired: true });
          }
        });
      });

      form.addEventListener("submit", (event) => {
        const strictRequired = isFinalSubmit(event.submitter);

        if (!runValidation(form, { strictRequired })) {
          event.preventDefault();
        }
      });
    });
  }

  function closeModal() {
    hidePanels();
    overlay.classList.remove("is-open");
    overlay.setAttribute("hidden", "hidden");
    overlay.setAttribute("aria-hidden", "true");
    document.documentElement.classList.remove("offre-modal-open");
    document.body.classList.remove("offre-modal-open");
    activePanelName = "";

    if (lastFocusedElement && typeof lastFocusedElement.focus === "function") {
      lastFocusedElement.focus({ preventScroll: true });
    }
  }

  function openModal(panelName) {
    const panel = panels.get(panelName);

    if (!panel) {
      return;
    }

    hidePanels();
    lastFocusedElement = document.activeElement;
    activePanelName = panelName;
    overlay.removeAttribute("hidden");
    overlay.setAttribute("aria-hidden", "false");
    overlay.setAttribute("tabindex", "-1");
    overlay.classList.add("is-open");
    panel.removeAttribute("hidden");
    document.documentElement.classList.add("offre-modal-open");
    document.body.classList.add("offre-modal-open");
    const modalBody = panel.querySelector(".response-modal-body");

    if (modalBody) {
      modalBody.scrollTop = 0;
    }

    window.requestAnimationFrame(() => {
      focusFirstField(panel);
    });
  }

  triggers.forEach((trigger) => {
    trigger.addEventListener("click", (event) => {
      event.preventDefault();
      openModal(trigger.dataset.creatorResponseModalTrigger || "");
    });
  });

  closeButtons.forEach((button) => {
    button.addEventListener("click", closeModal);
  });

  overlay.addEventListener("click", (event) => {
    if (event.target === overlay) {
      closeModal();
    }
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && activePanelName !== "") {
      closeModal();
    }
  });

  const defaultModal = (overlay.dataset.defaultModal || "").trim();
  initializeValidation();

  if (defaultModal !== "") {
    openModal(defaultModal);
  }
})();
