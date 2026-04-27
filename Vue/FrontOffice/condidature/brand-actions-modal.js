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
    document.body.classList.remove("offre-modal-open");
    activePanelName = "";

    if (lastFocusedElement && typeof lastFocusedElement.focus === "function") {
      lastFocusedElement.focus();
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
    overlay.style.display = "flex";
    overlay.classList.add("is-open");
    panel.removeAttribute("hidden");
    document.body.classList.add("offre-modal-open");

    window.requestAnimationFrame(() => {
      if (panelName === "negotiate" && negotiationMessage) {
        negotiationMessage.focus();
        return;
      }

      if (panelName === "decision" && decisionNote) {
        decisionNote.focus();
      }
    });
  }

  triggers.forEach((trigger) => {
    trigger.addEventListener("click", () => {
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
  if (defaultModal !== "") {
    openModal(defaultModal, {
      decisionStatus: overlay.dataset.defaultDecisionStatus || "acceptee",
    });
  }
})();
