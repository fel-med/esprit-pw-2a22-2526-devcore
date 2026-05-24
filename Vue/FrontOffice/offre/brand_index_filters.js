(() => {
    function buildFilterListUrl(form) {
        const action = (form.getAttribute("action") || "").trim() || "brand_index.php";
        const url = new URL(action, window.location.href);
        url.search = "";
        const fd = new FormData(form);
        fd.forEach((value, key) => {
            const s = value == null ? "" : String(value).trim();
            if (s !== "") {
                url.searchParams.set(key, s);
            }
        });
        return url;
    }

    function replaceWorkspaceFromDocument(doc) {
        const next = doc.querySelector("[data-brand-index-workspace]");
        const cur = document.querySelector("[data-brand-index-workspace]");
        if (!next || !cur) {
            return false;
        }
        cur.replaceWith(document.importNode(next, true));
        const shell = document.querySelector(".offre-page-shell");
        const root = shell || document;
        if (typeof window.initOfferTabs === "function") {
            window.initOfferTabs(root);
        }
        if (typeof window.initDeleteConfirmForms === "function") {
            window.initDeleteConfirmForms(root);
        }
        window.dispatchEvent(new CustomEvent("brandIndexWorkspaceUpdated"));
        return true;
    }

    function setWorkspaceLoading(isLoading) {
        const workspace = document.querySelector("[data-brand-index-workspace]");
        if (!workspace) {
            return;
        }

        workspace.classList.toggle("is-loading", isLoading);
        workspace.setAttribute("aria-busy", isLoading ? "true" : "false");
    }

    async function fetchAndSwapWorkspace(url) {
        setWorkspaceLoading(true);
        try {
            const response = await fetch(url.toString(), {
                credentials: "same-origin",
                headers: {
                    Accept: "text/html",
                    "X-Requested-With": "XMLHttpRequest",
                },
            });
            if (!response.ok) {
                throw new Error("filter_fetch_failed");
            }
            const html = await response.text();
            const doc = new DOMParser().parseFromString(html, "text/html");
            if (!replaceWorkspaceFromDocument(doc)) {
                throw new Error("filter_parse_failed");
            }
        } finally {
            setWorkspaceLoading(false);
        }
    }

    function pushHistoryFromUrl(url) {
        const u = new URL(url.toString());
        u.searchParams.delete("partial");
        window.history.pushState({}, "", u.pathname + u.search);
    }

    async function applyBrandIndexFiltersAjax(form) {
        const url = buildFilterListUrl(form);
        await fetchAndSwapWorkspace(url);
        pushHistoryFromUrl(url);
    }

    async function followBrandIndexLinkAjax(link) {
        const url = new URL(link.href, window.location.href);
        await fetchAndSwapWorkspace(url);
        pushHistoryFromUrl(url);
    }

    function isBrandIndexFilterForm(form) {
        if (!form || form.tagName !== "FORM") {
            return false;
        }
        if ((form.getAttribute("method") || "get").toLowerCase() !== "get") {
            return false;
        }
        const act = (form.getAttribute("action") || "").toLowerCase();
        return act.includes("brand_index.php") || act === "" || act.endsWith("/brand_index.php");
    }

    window.__cre8connectBrandIndexApplyFilterAjax = function (form) {
        if (!isBrandIndexFilterForm(form)) {
            return false;
        }
        applyBrandIndexFiltersAjax(form).catch(() => {
            form.submit();
        });
        return true;
    };

    document.addEventListener("DOMContentLoaded", () => {
        const form = document.querySelector('form.filter-stack[method="get"]');
        if (!form || !isBrandIndexFilterForm(form)) {
            return;
        }

        form.addEventListener("submit", (event) => {
            if (form.dataset.brandFilterAjax === "0") {
                return;
            }
            event.preventDefault();
            applyBrandIndexFiltersAjax(form).catch(() => {
                form.dataset.brandFilterAjax = "0";
                form.submit();
            });
        });

        document.addEventListener("click", (event) => {
            const link = event.target.closest(".filter-actions a[href], .front-pagination a[href]");
            if (
                !link ||
                !link.closest("[data-brand-index-workspace]") ||
                event.defaultPrevented ||
                event.button !== 0 ||
                event.metaKey ||
                event.ctrlKey ||
                event.shiftKey ||
                event.altKey ||
                link.target === "_blank"
            ) {
                return;
            }

            event.preventDefault();
            followBrandIndexLinkAjax(link).catch(() => {
                window.location.assign(link.href);
            });
        });

        window.addEventListener("popstate", () => {
            const url = new URL(window.location.href);
            fetchAndSwapWorkspace(url).catch(() => {
                window.location.reload();
            });
        });
    });
})();
