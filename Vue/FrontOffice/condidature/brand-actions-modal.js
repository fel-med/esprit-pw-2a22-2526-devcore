(() => {
  const overlay = document.querySelector("[data-response-modal-overlay]");

  if (!overlay) {
    return;
  }

  const panels = new Map(
    Array.from(overlay.querySelectorAll("[data-response-modal-panel]")).map((panel) => [
      panel.dataset.responseModalPanel,
      panel,
    ]),
  );
  const triggers = Array.from(document.querySelectorAll("[data-response-modal-trigger]"));
  const closeButtons = Array.from(overlay.querySelectorAll("[data-response-modal-close]"));
  const decisionPanel = panels.get("decision") || null;
  const decisionCard = decisionPanel?.querySelector("[data-response-modal-card]") || null;
  const decisionStatusInput = decisionPanel?.querySelector("[data-decision-status-input]") || null;
  const decisionTitle = decisionPanel?.querySelector("[data-decision-title]") || null;
  const decisionSubtitle = decisionPanel?.querySelector("[data-decision-subtitle]") || null;
  const decisionCopy = decisionPanel?.querySelector("[data-decision-copy]") || null;
  const decisionSubmit = decisionPanel?.querySelector("[data-decision-submit]") || null;
  const negotiationPanel = panels.get("negotiate") || null;
  const negotiationMessage = negotiationPanel?.querySelector("#negotiationModalMessage") || null;
  const decisionNote = decisionPanel?.querySelector("#decisionModalNote") || null;
  const modalForms = Array.from(overlay.querySelectorAll("form"));

  if (overlay.parentElement !== document.body) {
    document.body.appendChild(overlay);
  }

  const decisionConfig = {
    acceptee: {
      variant: "accept",
      title: "Accept this candidature?",
      subtitle: "Confirm the final outcome for this creator response without leaving the current page.",
      copy: "Approve the creator response and store your note as the final brand-side decision.",
      submitLabel: "Accept candidature",
    },
    refusee: {
      variant: "refuse",
      title: "Refuse this candidature?",
      subtitle: "Close this response from the brand workspace while keeping the stored history readable.",
      copy: "Refuse the current response and store your note as the final brand-side decision.",
      submitLabel: "Refuse candidature",
    },
  };

  let activePanelName = "";
  let lastFocusedElement = null;

  function getField(form, name) {
    return form.querySelector(`[name="${name}"]`);
  }

  function fieldValue(form, name) {
    const field = getField(form, name);
    return field ? field.value.trim() : "";
  }

  function isPositiveNumber(value) {
    return value !== "" && !Number.isNaN(Number(value)) && Number(value) > 0;
  }

  function isPositiveInteger(value) {
    return /^[0-9]+$/.test(value) && Number(value) > 0;
  }

  function normalizeNumberText(value) {
    if (value === "" || Number.isNaN(Number(value))) {
      return "";
    }

    return String(Number(value));
  }

  function hasNegotiationDelta(form, message, budget, delay) {
    if (form.dataset.requireNegotiationDelta !== "1") {
      return true;
    }

    const baselineMessage = (form.dataset.baselineMessage || "").trim();
    const baselineBudget = normalizeNumberText((form.dataset.baselineBudget || "").trim());
    const baselineDelay = normalizeNumberText((form.dataset.baselineDelay || "").trim());

    return (
      message.trim() !== baselineMessage ||
      normalizeNumberText(budget) !== baselineBudget ||
      normalizeNumberText(delay) !== baselineDelay
    );
  }

  function addError(errors, form, name, message) {
    const field = getField(form, name);

    if (field) {
      errors.push({ field, message });
    }
  }

  function validateBrandActionForm(form) {
    const errors = [];
    const action = fieldValue(form, "brandAction");
    const note = fieldValue(form, "noteDecision");
    const message = fieldValue(form, "message");
    const budget = fieldValue(form, "budgetPropose");
    const delay = fieldValue(form, "delaiPropose");

    if (note.length > 1500) {
      addError(errors, form, "noteDecision", "Decision note must stay under 1500 characters.");
    }

    if (action !== "negotiate") {
      return errors;
    }

    if (message === "") {
      addError(errors, form, "message", "Add a negotiation message before sending this reply.");
    } else if (message.length > 2500) {
      addError(errors, form, "message", "Negotiation message must stay under 2500 characters.");
    }

    if (budget !== "" && !isPositiveNumber(budget)) {
      addError(errors, form, "budgetPropose", "Enter a valid budget greater than 0, or leave it empty.");
    }

    if (delay !== "" && !isPositiveInteger(delay)) {
      addError(errors, form, "delaiPropose", "Enter a valid timeline in days, or leave it empty.");
    }

    if (!hasNegotiationDelta(form, message, budget, delay)) {
      addError(errors, form, "message", "Change the negotiation message, budget, or timeline before saving this proposal.");
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

  function renderFieldErrors(errors) {
    errors.forEach((error) => {
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

  function runValidation(form, touchedOnly = false) {
    clearValidation(form);
    const errors = validateBrandActionForm(form);
    const visibleErrors = touchedOnly
      ? errors.filter((error) => error.field.dataset.validationTouched === "1")
      : errors;

    renderFieldErrors(visibleErrors);

    if (!touchedOnly) {
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
          runValidation(form, true);
        });

        field.addEventListener("input", () => {
          if (field.dataset.validationTouched === "1") {
            runValidation(form, true);
          }
        });
      });

      form.addEventListener("submit", (event) => {
        if (!runValidation(form, false)) {
          event.preventDefault();
        }
      });
    });
  }

  function hideAllPanels() {
    panels.forEach((panel) => {
      panel.setAttribute("hidden", "hidden");
    });
  }

  function setDecisionVariant(status) {
    const safeStatus = status === "refusee" ? "refusee" : "acceptee";
    const config = decisionConfig[safeStatus];

    if (decisionCard) {
      decisionCard.dataset.modalVariant = config.variant;
    }

    if (decisionStatusInput) {
      decisionStatusInput.value = safeStatus;
    }

    if (decisionTitle) {
      decisionTitle.textContent = config.title;
    }

    if (decisionSubtitle) {
      decisionSubtitle.textContent = config.subtitle;
    }

    if (decisionCopy) {
      decisionCopy.textContent = config.copy;
    }

    if (decisionSubmit) {
      decisionSubmit.textContent = config.submitLabel;
    }
  }

  function closeModal() {
    hideAllPanels();
    overlay.classList.remove("is-open");
    overlay.style.display = "none";
    overlay.setAttribute("hidden", "hidden");
    document.documentElement.classList.remove("offre-modal-open");
    document.body.classList.remove("offre-modal-open");
    activePanelName = "";

    if (lastFocusedElement && typeof lastFocusedElement.focus === "function") {
      lastFocusedElement.focus({ preventScroll: true });
    }
  }

  function openModal(panelName, options = {}) {
    const panel = panels.get(panelName);

    if (!panel) {
      return;
    }

    hideAllPanels();
    lastFocusedElement = document.activeElement;
    activePanelName = panelName;

    if (panelName === "decision") {
      setDecisionVariant(options.decisionStatus || overlay.dataset.defaultDecisionStatus || "acceptee");
    }

    overlay.removeAttribute("hidden");
    overlay.setAttribute("tabindex", "-1");
    overlay.style.display = "flex";
    overlay.classList.add("is-open");
    panel.removeAttribute("hidden");
    document.documentElement.classList.add("offre-modal-open");
    document.body.classList.add("offre-modal-open");
    const modalBody = panel.querySelector(".response-modal-body");

    if (modalBody) {
      modalBody.scrollTop = 0;
    }

    window.requestAnimationFrame(() => {
      if (panelName === "negotiate" && negotiationMessage) {
        negotiationMessage.focus({ preventScroll: true });
        return;
      }

      if (panelName === "decision" && decisionNote) {
        decisionNote.focus({ preventScroll: true });
      }
    });
  }

  triggers.forEach((trigger) => {
    trigger.addEventListener("click", (event) => {
      event.preventDefault();
      const modalName = trigger.dataset.responseModalTrigger || "";
      const decisionStatus = trigger.dataset.decisionStatus || "";

      openModal(modalName, { decisionStatus });
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
    openModal(defaultModal, {
      decisionStatus: overlay.dataset.defaultDecisionStatus || "acceptee",
    });
  }

  window.__cre8connectBrandActionsModal = {
    open: openModal,
    close: closeModal,
  };
})();
