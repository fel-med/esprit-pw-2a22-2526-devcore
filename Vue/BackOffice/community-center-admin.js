(function () {
  "use strict";

  const STORAGE_KEY = "cre8_community_center_stats_visible";

  function applyBackTranslations() {
    if (window.cre8BackApplyTranslations) {
      window.cre8BackApplyTranslations();
    }
  }

  function applyStatsState(panel) {
    if (!panel) return;
    const visible = localStorage.getItem(STORAGE_KEY) !== "0";
    const button = panel.querySelector("[data-cc-stats-toggle]");
    panel.classList.toggle("is-collapsed", !visible);
    if (button) {
      const key = visible ? "common.hideStatistics" : "common.showStatistics";
      button.setAttribute("data-i18n", key);
      button.textContent = window.cre8BackText
        ? window.cre8BackText(key)
        : (visible ? "Hide statistics" : "Show statistics");
      if (window.cre8BackApplyStatsToggleButtons) {
        window.cre8BackApplyStatsToggleButtons();
      } else {
        applyBackTranslations();
      }
    }
  }

  function setupStatsToggle() {
    document.querySelectorAll("[data-cc-stats]").forEach((panel) => {
      applyStatsState(panel);
      const button = panel.querySelector("[data-cc-stats-toggle]");
      if (!button || button.dataset.ccBound === "1") return;
      button.dataset.ccBound = "1";
      button.addEventListener("click", () => {
        const isCollapsed = panel.classList.contains("is-collapsed");
        localStorage.setItem(STORAGE_KEY, isCollapsed ? "1" : "0");
        applyStatsState(panel);
        setTimeout(() => window.dispatchEvent(new Event("resize")), 80);
      });
    });
  }



  function buildGetUrlFromForm(form) {
    const action = form.getAttribute("action") || window.location.pathname;
    const url = new URL(action, window.location.href);
    const params = new URLSearchParams();
    const data = new FormData(form);
    data.forEach((value, key) => {
      const stringValue = String(value).trim();
      if (stringValue !== "") {
        params.append(key, stringValue);
      }
    });
    url.search = params.toString();
    url.hash = "";
    return url.toString();
  }


  function getRegion() {
    return document.getElementById("ccResultsRegion");
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
        const nextRegion = doc.getElementById("ccResultsRegion");
        if (!nextRegion) throw new Error("Missing result region");
        region.replaceWith(nextRegion);
        if (push) history.pushState({ ccAjax: true }, "", url);
        setupStatsToggle();
        applyBackTranslations();
        window.dispatchEvent(new Event("resize"));
      })
      .catch(() => {
        window.location.href = url;
      })
      .finally(() => {
        const next = getRegion();
        if (next) next.classList.remove("is-loading");
        applyBackTranslations();
      });
  }



  document.addEventListener("submit", (event) => {
    const form = event.target.closest("form.cc-filter-grid");
    if (!form || event.defaultPrevented) return;
    event.preventDefault();
    replaceResultsFromUrl(buildGetUrlFromForm(form), true);
  });

  document.addEventListener("click", (event) => {
    const resetLink = event.target.closest("form.cc-filter-grid a[href]");
    if (!resetLink || event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    event.preventDefault();
    replaceResultsFromUrl(new URL(resetLink.getAttribute("href"), window.location.href).toString(), true);
  });


  document.addEventListener("click", (event) => {
    const link = event.target.closest(".cc-results-region .cc-page-btn[href]");
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
    document.querySelectorAll("[data-cc-stats]").forEach(applyStatsState);
    applyBackTranslations();
  });

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", () => { setupStatsToggle(); applyBackTranslations(); });
  } else {
    setupStatsToggle();
    applyBackTranslations();
  }
})();
