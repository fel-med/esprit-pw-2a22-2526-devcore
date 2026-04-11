document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("postForm");
  if (!form) return;

  const subject = document.getElementById("subject");
  const textContent = document.getElementById("textContent");
  const image = document.getElementById("image");
  const video = document.getElementById("video");
  const imagePreview = document.getElementById("imagePreview");
  const videoPreview = document.getElementById("videoPreview");

  const allowedImageTypes = ["image/jpeg", "image/png", "image/webp", "image/jpg"];
  const allowedVideoTypes = ["video/mp4", "video/webm", "video/ogg"];

  function setError(fieldId, message) {
    const el = document.getElementById(fieldId + "Error");
    if (el) el.textContent = message;
  }

  function clearErrors() {
    ["subject", "textContent", "image", "video"].forEach((field) => {
      setError(field, "");
    });
  }

  function validateSubject() {
    const value = subject.value.trim();
    if (value.length === 0) {
      setError("subject", "Subject is required.");
      return false;
    }
    if (value.length < 3) {
      setError("subject", "Subject must contain at least 3 characters.");
      return false;
    }
    if (value.length > 150) {
      setError("subject", "Subject must not exceed 150 characters.");
      return false;
    }
    setError("subject", "");
    return true;
  }

  function validateText() {
    const value = textContent.value.trim();
    if (value.length === 0) {
      setError("textContent", "Post content is required.");
      return false;
    }
    if (value.length < 10) {
      setError("textContent", "Post content must contain at least 10 characters.");
      return false;
    }
    setError("textContent", "");
    return true;
  }

  function validateImage() {
    if (!image.files || image.files.length === 0) {
      setError("image", "");
      return true;
    }

    const file = image.files[0];
    if (!allowedImageTypes.includes(file.type)) {
      setError("image", "Allowed image formats: JPG, JPEG, PNG, WEBP.");
      return false;
    }

    if (file.size > 5 * 1024 * 1024) {
      setError("image", "Image size must be less than 5 MB.");
      return false;
    }

    setError("image", "");
    return true;
  }

  function validateVideo() {
    if (!video.files || video.files.length === 0) {
      setError("video", "");
      return true;
    }

    const file = video.files[0];
    if (!allowedVideoTypes.includes(file.type)) {
      setError("video", "Allowed video formats: MP4, WEBM, OGG.");
      return false;
    }

    if (file.size > 30 * 1024 * 1024) {
      setError("video", "Video size must be less than 30 MB.");
      return false;
    }

    setError("video", "");
    return true;
  }

  function previewImage() {
    if (!imagePreview) return;
    imagePreview.innerHTML = "";

    if (image.files && image.files[0]) {
      const file = image.files[0];
      const img = document.createElement("img");
      img.className = "media-preview";
      img.style.maxHeight = "220px";
      img.src = URL.createObjectURL(file);
      imagePreview.appendChild(img);
    }
  }

  function previewVideo() {
    if (!videoPreview) return;
    videoPreview.innerHTML = "";

    if (video.files && video.files[0]) {
      const file = video.files[0];
      const videoEl = document.createElement("video");
      videoEl.className = "media-preview";
      videoEl.style.maxHeight = "260px";
      videoEl.controls = true;
      videoEl.src = URL.createObjectURL(file);
      videoPreview.appendChild(videoEl);
    }
  }

  subject.addEventListener("input", validateSubject);
  textContent.addEventListener("input", validateText);
  image.addEventListener("change", function () {
    validateImage();
    previewImage();
  });
  video.addEventListener("change", function () {
    validateVideo();
    previewVideo();
  });

  form.addEventListener("submit", function (e) {
    clearErrors();

    const isSubjectValid = validateSubject();
    const isTextValid = validateText();
    
    if (!isSubjectValid || !isTextValid ) {
      e.preventDefault();
    }
  });
});