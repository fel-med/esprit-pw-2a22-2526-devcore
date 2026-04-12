document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("postForm");
  const subject = document.getElementById("subject");
  const textContent = document.getElementById("textContent");
  const image = document.getElementById("image");
  const video = document.getElementById("video");
  const imagePreview = document.getElementById("imagePreview");
  const videoPreview = document.getElementById("videoPreview");
  const subjectCounter = document.getElementById("subjectCounter");
  const contentCounter = document.getElementById("contentCounter");

  function setError(id, message) {
    const el = document.getElementById(id + "Error");
    if (el) el.textContent = message;
  }

  function clearErrors() {
    ["subject", "textContent", "image", "video"].forEach((field) => setError(field, ""));
  }

  function updateCounters() {
    if (subject && subjectCounter) {
      subjectCounter.textContent = `${subject.value.trim().length}/150 characters`;
    }
    if (textContent && contentCounter) {
      contentCounter.textContent = `${textContent.value.trim().length}/5000 characters`;
    }
  }

  function validateSubject() {
    if (!subject) return true;
    const value = subject.value.trim();

    if (value.length === 0) {
      setError("subject", "The subject is required.");
      return false;
    }

    if (value.length < 3) {
      setError("subject", "The subject must contain at least 3 characters.");
      return false;
    }

    if (value.length > 150) {
      setError("subject", "The subject must not exceed 150 characters.");
      return false;
    }

    setError("subject", "");
    return true;
  }

  function validateTextContent() {
    if (!textContent) return true;
    const value = textContent.value.trim();

    if (value.length === 0) {
      setError("textContent", "The content is required.");
      return false;
    }

    if (value.length < 10) {
      setError("textContent", "The content must contain at least 10 characters.");
      return false;
    }

    if (value.length > 5000) {
      setError("textContent", "The content is too long.");
      return false;
    }

    setError("textContent", "");
    return true;
  }

  function validateImage() {
    if (!image || !image.files || image.files.length === 0) {
      setError("image", "");
      return true;
    }

    const file = image.files[0];
    const allowedImageTypes = ["image/jpeg", "image/png", "image/webp", "image/jpg"];

    if (!allowedImageTypes.includes(file.type)) {
      setError("image", "Only JPG, JPEG, PNG and WEBP images are allowed.");
      return false;
    }

    if (file.size > 5 * 1024 * 1024) {
      setError("image", "The image must be smaller than 5 MB.");
      return false;
    }

    setError("image", "");
    return true;
  }

  function validateVideo() {
    if (!video || !video.files || video.files.length === 0) {
      setError("video", "");
      return true;
    }

    const file = video.files[0];
    const allowedVideoTypes = ["video/mp4", "video/webm", "video/ogg"];

    if (!allowedVideoTypes.includes(file.type)) {
      setError("video", "Only MP4, WEBM and OGG videos are allowed.");
      return false;
    }

    if (file.size > 200 * 1024 * 1024) {
      setError("video", "The video must be smaller than 30 MB.");
      return false;
    }

    setError("video", "");
    return true;
  }

  function renderImagePreview() {
    if (!imagePreview) return;
    imagePreview.innerHTML = "";

    if (image && image.files && image.files[0]) {
      const img = document.createElement("img");
      img.src = URL.createObjectURL(image.files[0]);
      img.alt = "Selected image preview";
      imagePreview.appendChild(img);
    }
  }

  function renderVideoPreview() {
    if (!videoPreview) return;
    videoPreview.innerHTML = "";

    if (video && video.files && video.files[0]) {
      const videoEl = document.createElement("video");
      videoEl.src = URL.createObjectURL(video.files[0]);
      videoEl.controls = true;
      videoEl.muted = true;
      videoEl.playsInline = true;
      videoEl.preload = "metadata";
      videoPreview.appendChild(videoEl);
    }
  }

  function setupFeedVideos() {
    const videos = document.querySelectorAll(".social-post-video, .preview-box video");

    videos.forEach((videoEl) => {
      videoEl.setAttribute("playsinline", "true");
      videoEl.setAttribute("preload", "metadata");

      videoEl.addEventListener("play", () => {
        videos.forEach((other) => {
          if (other !== videoEl) {
            other.pause();
          }
        });
      });
    });
  }

  async function handleReactionClick(button) {
    const postId = button.dataset.postId;
    const action = button.dataset.action;

    if (!postId || !action) return;

    const card = button.closest(".social-post-card");
    const likeCountEl = card?.querySelector(".js-like-count");
    const dislikeCountEl = card?.querySelector(".js-dislike-count");
    const allButtons = card?.querySelectorAll(".js-reaction-btn");

    if (button.classList.contains("is-loading")) return;

    button.classList.add("is-loading");
    allButtons?.forEach((btn) => btn.setAttribute("disabled", "disabled"));

    try {
      const response = await fetch(`./${action}.php?id=${encodeURIComponent(postId)}`, {
        method: "GET",
        headers: {
          "X-Requested-With": "XMLHttpRequest"
        }
      });

      const data = await response.json();

      if (!data.success) {
        throw new Error(data.message || "Reaction failed.");
      }

      if (likeCountEl) likeCountEl.textContent = data.likes;
      if (dislikeCountEl) dislikeCountEl.textContent = data.dislikes;

      if (action === "like") {
        button.classList.add("is-active-like");
      }

      if (action === "dislike") {
        button.classList.add("is-active-dislike");
      }
    } catch (error) {
      console.error(error);
      alert("Unable to update reaction right now.");
    } finally {
      button.classList.remove("is-loading");
      allButtons?.forEach((btn) => btn.removeAttribute("disabled"));
    }
  }

  function setupReactionButtons() {
    const reactionButtons = document.querySelectorAll(".js-reaction-btn");

    reactionButtons.forEach((button) => {
      button.addEventListener("click", function (e) {
        e.preventDefault();
        handleReactionClick(button);
      });
    });
  }

  subject?.addEventListener("input", function () {
    validateSubject();
    updateCounters();
  });

  textContent?.addEventListener("input", function () {
    validateTextContent();
    updateCounters();
  });

  image?.addEventListener("change", function () {
    validateImage();
    renderImagePreview();
  });

  video?.addEventListener("change", function () {
    validateVideo();
    renderVideoPreview();
  });

  updateCounters();
  setupFeedVideos();
  setupReactionButtons();

  if (!form) return;

  form.addEventListener("submit", function (e) {
    clearErrors();

    const validSubject = validateSubject();
    const validText = validateTextContent();
   
    if (!validSubject || !validText ) {
      e.preventDefault();
      const firstError = document.querySelector(".validation-error:not(:empty)");
      if (firstError) {
        firstError.scrollIntoView({ behavior: "smooth", block: "center" });
      }
    }
  });
});