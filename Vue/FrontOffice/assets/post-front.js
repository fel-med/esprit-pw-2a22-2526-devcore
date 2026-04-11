document.addEventListener("DOMContentLoaded", function () {
  const searchInput = document.getElementById("searchInput");
  if (!searchInput) return;

  searchInput.addEventListener("input", function () {
    const value = this.value.trim();
    const clearBtn = document.getElementById("clearSearchBtn");
    if (clearBtn) {
      clearBtn.style.display = value.length ? "inline-block" : "none";
    }
  });
});