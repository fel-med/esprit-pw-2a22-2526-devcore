(function () {
  "use strict";

  const STORAGE_KEY = "cre8_business_center_stats_visible";

  function applyStatsState(panel) {
    if (!panel) return;
    const visible = localStorage.getItem(STORAGE_KEY) !== "0";
    const button = panel.querySelector("[data-bc-stats-toggle]");
    panel.classList.toggle("is-collapsed", !visible);
    if (button) {
      const key = visible ? "common.hideStatistics" : "common.showStatistics";
      button.setAttribute("data-i18n", key);
      button.textContent = window.cre8BackText
        ? window.cre8BackText(key)
        : (visible ? "Hide statistics" : "Show statistics");
      if (window.cre8BackApplyStatsToggleButtons) {
        window.cre8BackApplyStatsToggleButtons();
      } else if (window.cre8BackApplyTranslations) {
        window.cre8BackApplyTranslations();
      }
    }
  }

  function setupStatsToggle() {
    document.querySelectorAll("[data-bc-stats]").forEach((panel) => {
      applyStatsState(panel);
      const button = panel.querySelector("[data-bc-stats-toggle]");
      if (!button || button.dataset.bcBound === "1") return;
      button.dataset.bcBound = "1";
      button.addEventListener("click", () => {
        const isCollapsed = panel.classList.contains("is-collapsed");
        localStorage.setItem(STORAGE_KEY, isCollapsed ? "1" : "0");
        applyStatsState(panel);
        setTimeout(() => window.dispatchEvent(new Event("resize")), 80);
      });
    });
  }

  function getRegion() {
    return document.getElementById("bcResultsRegion");
  }

  function replaceResultsFromUrl(url, push) {
    const region = getRegion();
    if (!region) {
      window.location.href = url;
      return;
    }

    region.classList.add("is-loading");

    fetch(url, {
      headers: { "X-Requested-With": "XMLHttpRequest" },
      credentials: "same-origin"
    })
      .then((response) => {
        if (!response.ok) throw new Error("Bad response");
        return response.text();
      })
      .then((html) => {
        const doc = new DOMParser().parseFromString(html, "text/html");
        const nextRegion = doc.getElementById("bcResultsRegion");
        if (!nextRegion) throw new Error("Missing result region");
        region.replaceWith(nextRegion);
        if (push) history.pushState({ bcAjax: true }, "", url);
        setupStatsToggle();
        window.dispatchEvent(new Event("resize"));
      })
      .catch(() => { window.location.href = url; })
      .finally(() => {
        const next = getRegion();
        if (next) next.classList.remove("is-loading");
      });
  }

  document.addEventListener("click", (event) => {
    const link = event.target.closest(".bc-results-region .bc-page-btn[href]");
    if (!link || link.classList.contains("is-disabled")) return;
    const href = link.getAttribute("href");
    if (!href || href === "#") return;
    event.preventDefault();
    replaceResultsFromUrl(new URL(href, window.location.href).toString(), true);
  });

  window.addEventListener("popstate", () => {
    const region = getRegion();
    if (region) replaceResultsFromUrl(window.location.href, false);
  });

  window.addEventListener("cre8:languagechange", () => {
    document.querySelectorAll("[data-bc-stats]").forEach(applyStatsState);
    if (window.cre8BackApplyStatsToggleButtons) {
      window.cre8BackApplyStatsToggleButtons();
    } else if (window.cre8BackApplyTranslations) {
      window.cre8BackApplyTranslations();
    }
  });

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", setupStatsToggle);
  } else {
    setupStatsToggle();
  }
})();
