(function () {
  "use strict";

  const STORAGE_KEY = "cre8_user_center_stats_visible";

  function applyStatsState(panel) {
    if (!panel) return;
    const visible = localStorage.getItem(STORAGE_KEY) !== "0";
    const button = panel.querySelector("[data-uc-stats-toggle]");
    panel.classList.toggle("is-collapsed", !visible);
    if (button) {
      button.textContent = visible ? "Hide statistics" : "Show statistics";
    }
  }

  function setupStatsToggle() {
    document.querySelectorAll("[data-uc-stats]").forEach((panel) => {
      applyStatsState(panel);
      const button = panel.querySelector("[data-uc-stats-toggle]");
      if (!button || button.dataset.ucBound === "1") return;
      button.dataset.ucBound = "1";
      button.addEventListener("click", () => {
        const isCollapsed = panel.classList.contains("is-collapsed");
        localStorage.setItem(STORAGE_KEY, isCollapsed ? "1" : "0");
        applyStatsState(panel);
        setTimeout(() => window.dispatchEvent(new Event("resize")), 80);
      });
    });
  }

  function getRegion() {
    return document.getElementById("ucResultsRegion");
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
        const nextRegion = doc.getElementById("ucResultsRegion");
        if (!nextRegion) throw new Error("Missing result region");
        region.replaceWith(nextRegion);
        if (push) {
          history.pushState({ ucAjax: true }, "", url);
        }
      })
      .catch(() => {
        window.location.href = url;
      })
      .finally(() => {
        const next = getRegion();
        if (next) next.classList.remove("is-loading");
      });
  }

  document.addEventListener("click", (event) => {
    const link = event.target.closest(".uc-results-region .uc-page-btn");
    if (!link || link.classList.contains("is-disabled")) return;
    const href = link.getAttribute("href");
    if (!href || href === "#") return;
    event.preventDefault();
    replaceResultsFromUrl(new URL(href, window.location.href).toString(), true);
  });

  window.addEventListener("popstate", () => {
    replaceResultsFromUrl(window.location.href, false);
  });

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", setupStatsToggle);
  } else {
    setupStatsToggle();
  }
})();