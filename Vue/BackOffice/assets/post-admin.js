function initAdminTheme() {
  var theme = localStorage.getItem('adminTheme');
  var isLight = theme === 'light';
  document.body.classList.toggle('light-mode', isLight);

  var icon = document.getElementById('themeIcon');
  var label = document.getElementById('themeLabel');
  if (icon) {
    icon.className = isLight ? 'mdi mdi-weather-sunny' : 'mdi mdi-weather-night';
  }
  if (label) {
    label.textContent = isLight ? 'Dark mode' : 'Light mode';
  }
}

document.addEventListener("DOMContentLoaded", function () {
  initAdminTheme();

  document.getElementById('themeToggleBtn').addEventListener('click', function() {
    var currentTheme = localStorage.getItem('adminTheme');
    var newTheme = currentTheme === 'light' ? 'dark' : 'light';
    localStorage.setItem('adminTheme', newTheme);
    initAdminTheme();
  });

  const deleteButtons = document.querySelectorAll(".js-admin-delete");

  deleteButtons.forEach((button) => {
    button.addEventListener("click", function (e) {
      const confirmed = confirm("Are you sure you want to delete this post?");
      if (!confirmed) {
        e.preventDefault();
      }
    });
  });

  const videos = document.querySelectorAll("video");
  videos.forEach((videoEl) => {
    videoEl.addEventListener("play", () => {
      videos.forEach((other) => {
        if (other !== videoEl) {
          other.pause();
        }
      });
    });
  });
});