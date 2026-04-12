document.addEventListener("DOMContentLoaded", function () {
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