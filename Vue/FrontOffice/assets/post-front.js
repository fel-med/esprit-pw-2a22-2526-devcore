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
    if (value.length === 0) return setError("subject", "The subject is required."), false;
    if (value.length < 3) return setError("subject", "The subject must contain at least 3 characters."), false;
    if (value.length > 150) return setError("subject", "The subject must not exceed 150 characters."), false;
    setError("subject", "");
    return true;
  }

  function validateTextContent() {
    if (!textContent) return true;
    const value = textContent.value.trim();
    if (value.length === 0) return setError("textContent", "The content is required."), false;
    if (value.length < 10) return setError("textContent", "The content must contain at least 10 characters."), false;
    if (value.length > 5000) return setError("textContent", "The content is too long."), false;
    setError("textContent", "");
    return true;
  }

  function validateImage() {
    if (!image || !image.files || image.files.length === 0) return setError("image", ""), true;
    const file = image.files[0];
    const allowedImageTypes = ["image/jpeg", "image/png", "image/webp", "image/jpg"];
    if (!allowedImageTypes.includes(file.type)) return setError("image", "Only JPG, JPEG, PNG and WEBP images are allowed."), false;
    if (file.size > 5 * 1024 * 1024) return setError("image", "The image must be smaller than 5 MB."), false;
    setError("image", "");
    return true;
  }

  function validateVideo() {
    if (!video || !video.files || video.files.length === 0) return setError("video", ""), true;
    const file = video.files[0];
    const allowedVideoTypes = ["video/mp4", "video/webm", "video/ogg"];
    if (!allowedVideoTypes.includes(file.type)) return setError("video", "Only MP4, WEBM and OGG videos are allowed."), false;
    if (file.size > 200 * 1024 * 1024) return setError("video", "The video must be smaller than 30 MB."), false;
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
        videos.forEach((other) => { if (other !== videoEl) other.pause(); });
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
        headers: { "X-Requested-With": "XMLHttpRequest" }
      });
      const data = await response.json();
      if (!data.success) throw new Error(data.message || "Reaction failed.");
      if (likeCountEl) likeCountEl.textContent = data.likes;
      if (dislikeCountEl) dislikeCountEl.textContent = data.dislikes;
      button.classList.add(action === "like" ? "is-active-like" : "is-active-dislike");
    } catch (error) {
      console.error(error);
      alert("Unable to update reaction right now.");
    } finally {
      button.classList.remove("is-loading");
      allButtons?.forEach((btn) => btn.removeAttribute("disabled"));
    }
  }

  function setupReactionButtons() {
    document.querySelectorAll(".js-reaction-btn").forEach((button) => {
      button.addEventListener("click", function (e) {
        e.preventDefault();
        handleReactionClick(button);
      });
    });
  }

  async function sendView(postId, scope) {
    const seen = JSON.parse(sessionStorage.getItem("trackedPostViews") || "[]");
    if (seen.includes(postId)) return;
    try {
      const response = await fetch(`./view.php`, {
        method: "POST",
        headers: {
          "X-Requested-With": "XMLHttpRequest",
          "Content-Type": "application/x-www-form-urlencoded"
        },
        body: `id=${encodeURIComponent(postId)}`
      });
      const data = await response.json();
      if (data.success) {
        scope.querySelectorAll(".js-view-count").forEach((el) => el.textContent = data.count);
        seen.push(postId);
        sessionStorage.setItem("trackedPostViews", JSON.stringify(seen));
      }
    } catch (error) {
      console.error(error);
    }
  }

  function setupViewTracking() {
    const cards = document.querySelectorAll(".js-post-view-track[data-post-id]");
    if (!cards.length || !("IntersectionObserver" in window)) return;
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        const scope = entry.target;
        const postId = scope.dataset.postId;
        if (postId) sendView(postId, scope);
        observer.unobserve(scope);
      });
    }, { threshold: 0.55 });
    cards.forEach((card) => observer.observe(card));
  }

  subject?.addEventListener("input", function () { validateSubject(); updateCounters(); });
  textContent?.addEventListener("input", function () { validateTextContent(); updateCounters(); });
  image?.addEventListener("change", function () { validateImage(); renderImagePreview(); });
  video?.addEventListener("change", function () { validateVideo(); renderVideoPreview(); });

  updateCounters();
  setupFeedVideos();
  setupReactionButtons();
  setupViewTracking();

  if (!form) return;
  form.addEventListener("submit", function (e) {
    clearErrors();
    const validSubject = validateSubject();
    const validText = validateTextContent();
    if (!validSubject || !validText) {
      e.preventDefault();
      const firstError = document.querySelector(".validation-error:not(:empty)");
      if (firstError) firstError.scrollIntoView({ behavior: "smooth", block: "center" });
    }
  });
});
