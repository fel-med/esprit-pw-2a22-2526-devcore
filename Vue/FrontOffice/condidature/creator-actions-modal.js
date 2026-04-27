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
  let activePanelName = "";
  let lastFocusedElement = null;

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
      focusTarget.focus();
    }
  }

  function closeModal() {
    hidePanels();
    overlay.classList.remove("is-open");
    overlay.setAttribute("hidden", "hidden");
    overlay.setAttribute("aria-hidden", "true");
    document.body.classList.remove("offre-modal-open");
    activePanelName = "";

    if (lastFocusedElement && typeof lastFocusedElement.focus === "function") {
      lastFocusedElement.focus();
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
    overlay.classList.add("is-open");
    panel.removeAttribute("hidden");
    document.body.classList.add("offre-modal-open");

    window.requestAnimationFrame(() => focusFirstField(panel));
  }

  triggers.forEach((trigger) => {
    trigger.addEventListener("click", () => {
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
  if (defaultModal !== "") {
    openModal(defaultModal);
  }
})();
