(() => {
  const OVERLAY_ID = "cc-delete-confirm-overlay";

  function ensureOverlay() {
    let overlay = document.getElementById(OVERLAY_ID);
    if (overlay) {
      return overlay;
    }

    overlay = document.createElement("div");
    overlay.id = OVERLAY_ID;
    overlay.className = "delete-confirm-overlay";
    overlay.setAttribute("hidden", "hidden");
    overlay.style.display = "none";
    overlay.style.position = "fixed";
    overlay.style.inset = "0";
    overlay.style.padding = "1.5rem";
    overlay.style.alignItems = "center";
    overlay.style.justifyContent = "center";
    overlay.style.background = "rgba(15, 23, 42, 0.38)";
    overlay.style.backdropFilter = "blur(12px)";
    overlay.style.zIndex = "1050";
    overlay.innerHTML = `
    <div class="delete-confirm-modal" role="dialog" aria-modal="true" aria-labelledby="deleteConfirmTitle">
      <div class="delete-confirm-header">
        <div>
          <span class="delete-confirm-kicker">Delete offer</span>
          <h2 id="deleteConfirmTitle">Delete this offer?</h2>
          <p class="delete-confirm-subtitle">Remove this offer from your pipeline.</p>
        </div>
      </div>
      <div class="delete-confirm-body">
        <p class="delete-confirm-copy">This will permanently remove the offer from your pipeline and you will not be able to restore it later.</p>
        <div class="delete-confirm-preview">
          <span class="delete-confirm-preview-label">Selected offer</span>
          <strong id="deleteConfirmOffer">This targeted offer</strong>
          <span id="deleteConfirmCreator">Creator context will appear here.</span>
        </div>
        <p class="delete-confirm-warning">This action cannot be undone.</p>
        <div class="delete-confirm-actions">
          <button type="button" class="delete-confirm-secondary" data-delete-cancel>Keep offer</button>
          <button type="button" class="delete-confirm-danger" id="deleteConfirmApprove">Delete permanently</button>
        </div>
      </div>
    </div>
  `;

    document.body.appendChild(overlay);

    const offerNode = overlay.querySelector("#deleteConfirmOffer");
    const creatorNode = overlay.querySelector("#deleteConfirmCreator");
    const approveButton = overlay.querySelector("#deleteConfirmApprove");
    const cancelButtons = overlay.querySelectorAll("[data-delete-cancel]");

    let activeForm = null;
    let lastFocusedElement = null;

    function closeModal() {
      overlay.classList.remove("is-open");
      overlay.style.display = "none";
      overlay.setAttribute("hidden", "hidden");
      document.body.classList.remove("offre-modal-open");
      activeForm = null;

      if (lastFocusedElement && typeof lastFocusedElement.focus === "function") {
        lastFocusedElement.focus();
      }
    }

    function openModal(form) {
      activeForm = form;
      lastFocusedElement = document.activeElement;

      const title = (form.dataset.deleteTitle || "").trim();
      const creator = (form.dataset.deleteCreator || "").trim();

      offerNode.textContent = title || "This targeted offer";
      creatorNode.textContent = creator ? `Target creator: ${creator}` : "No target creator is linked to this offer.";

      overlay.removeAttribute("hidden");
      overlay.classList.add("is-open");
      overlay.style.display = "flex";
      document.body.classList.add("offre-modal-open");

      window.requestAnimationFrame(() => {
        approveButton.focus();
      });
    }

    approveButton.addEventListener("click", () => {
      if (!activeForm) {
        return;
      }

      const formToSubmit = activeForm;
      formToSubmit.dataset.deleteConfirmed = "true";
      closeModal();
      formToSubmit.submit();
    });

    cancelButtons.forEach((button) => {
      button.addEventListener("click", closeModal);
    });

    overlay.addEventListener("click", (event) => {
      if (event.target === overlay) {
        closeModal();
      }
    });

    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape" && overlay.classList.contains("is-open")) {
        closeModal();
      }
    });

    overlay.__deleteConfirmBindForms = function bindForms(root) {
      const scope = root && root.querySelectorAll ? root : document;
      const forms = Array.from(scope.querySelectorAll("form[data-delete-confirm]"));
      forms.forEach((form) => {
        if (form.dataset.deleteConfirmBound === "1") {
          return;
        }
        form.dataset.deleteConfirmBound = "1";
        form.addEventListener("submit", (event) => {
          if (form.dataset.deleteConfirmed === "true") {
            delete form.dataset.deleteConfirmed;
            return;
          }

          event.preventDefault();
          openModal(form);
        });
      });
    };

    return overlay;
  }

  function bindDeleteFormsIn(root) {
    const overlay = ensureOverlay();
    if (overlay && typeof overlay.__deleteConfirmBindForms === "function") {
      overlay.__deleteConfirmBindForms(root || document);
    }
  }

  window.initDeleteConfirmForms = bindDeleteFormsIn;
  document.addEventListener("DOMContentLoaded", () => bindDeleteFormsIn(document));
})();
