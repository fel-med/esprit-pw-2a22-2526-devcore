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
  const likeBtn = card?.querySelector('.js-reaction-btn[data-action="like"]');
  const dislikeBtn = card?.querySelector('.js-reaction-btn[data-action="dislike"]');
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

    // Réinitialiser les deux boutons
    likeBtn?.classList.remove("is-active-like");
    dislikeBtn?.classList.remove("is-active-dislike");

    // Appliquer l'état selon userVote retourné par le PHP
    if (data.userVote === "like") {
      likeBtn?.classList.add("is-active-like");
    } else if (data.userVote === "dislike") {
      dislikeBtn?.classList.add("is-active-dislike");
    }
    // si userVote === null → les deux restent non colorés (vote annulé)

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



  function setAiStatus(scope, message, type = "info") {
    const statusEl = scope.querySelector(".ai-status") || document.querySelector(".ai-status");
    if (!statusEl) return;
    statusEl.textContent = message;
    statusEl.classList.remove("text-danger", "text-success", "text-muted");
    if (type === "error") statusEl.classList.add("text-danger");
    else if (type === "success") statusEl.classList.add("text-success");
    else statusEl.classList.add("text-muted");
  }

  async function handleAiGenerate(event) {
    event.preventDefault();

    const button = event.currentTarget;
    const scope = button.closest("form") || document;
    const briefField = scope.querySelector("#aiBrief, [name='aiBrief']");
    const styleField = scope.querySelector("#aiStyle, [name='aiStyle']");
    const sentenceCountField = scope.querySelector("#aiSentenceCount, [name='aiSentenceCount']");
    const contentField = scope.querySelector("#textContent, [name='textContent']");
    const imageField = scope.querySelector("#image, [name='image']");
    const existingImageField = scope.querySelector("#existingImagePath, [name='existingImagePath']");

    const brief = (briefField?.value || "").trim();
    const style = (styleField?.value || "").trim();
    const sentenceCount = (sentenceCountField?.value || "4").trim();
    const currentContent = (contentField?.value || "").trim();
    const existingImagePath = (existingImageField?.value || "").trim();
    const mode = button.dataset.aiMode || "generate";

    if (!brief) {
      setAiStatus(scope, "Please fill in the 'Describe your idea' field first.", "error");
      if (briefField) briefField.focus();
      return;
    }

    if (!contentField) {
      setAiStatus(scope, "Content field not found.", "error");
      return;
    }

    const formData = new FormData();
    formData.append("brief", brief);
    formData.append("style", style);
    formData.append("sentenceCount", sentenceCount);
    formData.append("mode", mode);
    formData.append("currentContent", currentContent);
    if (existingImagePath) {
      formData.append("existingImagePath", existingImagePath);
    }
    if (imageField && imageField.files && imageField.files[0]) {
      formData.append("image", imageField.files[0]);
    }

    button.disabled = true;
    const previousLabel = button.innerHTML;
    button.innerHTML = mode === "enhance" ? "Enhancing..." : "Generating...";
    setAiStatus(scope, mode === "enhance" ? "Enhancing content with AI..." : "Generating content with AI...", "info");

    try {
      const response = await fetch("./ai-generate.php", {
        method: "POST",
        headers: {
          "X-Requested-With": "XMLHttpRequest"
        },
        body: formData
      });

      const data = await response.json();
      if (!response.ok || !data.success) {
        throw new Error(data.message || "AI generation failed.");
      }

      contentField.value = data.content || "";
      updateCounters();
      validateTextContent();
      setAiStatus(scope, data.message || "AI content ready.", "success");
    } catch (error) {
      console.error(error);
      setAiStatus(scope, error.message || "Unable to generate AI content right now.", "error");
    } finally {
      button.disabled = false;
      button.innerHTML = previousLabel;
    }
  }

  function setupAiButtons() {
    document.querySelectorAll(".js-ai-generate").forEach((button) => {
      button.addEventListener("click", handleAiGenerate);
    });
  }


  function initCreaChatbot() {
    const root = document.getElementById("creaChatbot");
    const fab = document.getElementById("creaChatbotFab");
    const panel = document.getElementById("creaChatbotPanel");
    const closeBtn = document.getElementById("creaChatbotClose");
    const messages = document.getElementById("creaChatbotMessages");
    const formEl = document.getElementById("creaChatbotForm");
    const input = document.getElementById("creaChatbotInput");
    const sendBtn = document.getElementById("creaChatbotSend");
    const quickActions = document.getElementById("creaChatbotQuickActions");
    const toolbarSearch = document.querySelector('.toolbar-input[name="search"]');

    if (!root || !fab || !panel || !messages || !formEl || !input || !sendBtn) return;

    root.style.position = "fixed";
    root.style.left = "24px";
    root.style.right = "auto";
    root.style.bottom = "24px";
    root.style.zIndex = "9999";
    root.style.display = "flex";
    root.style.flexDirection = "column";
    root.style.alignItems = "flex-start";
    fab.style.display = "inline-flex";

    const postsData = Array.isArray(window.creaChatbotPosts) ? window.creaChatbotPosts : [];
    const postScopes = Array.from(document.querySelectorAll('.js-post-comments-scope[data-post-id]'));

    const addMessage = (text, role = 'bot', extraClass = '') => {
      const wrap = document.createElement('div');
      wrap.className = `crea-message crea-message-${role} ${extraClass}`.trim();
      const bubble = document.createElement('div');
      bubble.className = 'crea-message-bubble';
      bubble.textContent = text;
      wrap.appendChild(bubble);
      messages.appendChild(wrap);
      messages.scrollTop = messages.scrollHeight;
      return wrap;
    };

    const setOpen = (open) => {
      panel.classList.toggle('is-open', open);
      panel.setAttribute('aria-hidden', open ? 'false' : 'true');
      root.classList.toggle('is-open', open);
      if (open) {
        setTimeout(() => input.focus(), 120);
      }
    };

    const resetFeed = ({ keepMessage = true } = {}) => {
      postScopes.forEach((scope) => {
        scope.style.display = '';
        scope.classList.remove('crea-post-highlight');
      });
      document.querySelectorAll('.crea-post-highlight').forEach((el) => el.classList.remove('crea-post-highlight'));
      if (toolbarSearch) toolbarSearch.value = '';
      if (keepMessage) addMessage('The feed is back to its full view.', 'bot');
    };

    const highlightPost = (postId, openComments = false) => {
      const scope = document.querySelector(`.js-post-comments-scope[data-post-id="${CSS.escape(postId)}"]`);
      if (!scope) return false;
      postScopes.forEach((item) => item.classList.remove('crea-post-highlight'));
      scope.classList.add('crea-post-highlight');
      scope.scrollIntoView({ behavior: 'smooth', block: 'center' });
      if (openComments) {
        const btn = scope.querySelector('.js-open-comments-modal');
        if (btn) {
          setTimeout(() => btn.click(), 320);
        }
      }
      return true;
    };

    const applyPostFilter = (ids, replyIfEmpty) => {
      const wanted = new Set((ids || []).filter(Boolean));
      if (!wanted.size) {
        if (replyIfEmpty) addMessage(replyIfEmpty, 'bot');
        return false;
      }
      let visibleCount = 0;
      postScopes.forEach((scope) => {
        const match = wanted.has(scope.dataset.postId || '');
        scope.style.display = match ? '' : 'none';
        scope.classList.toggle('crea-post-highlight', match && visibleCount === 0);
        if (match) visibleCount += 1;
      });
      const first = postScopes.find((scope) => wanted.has(scope.dataset.postId || ''));
      if (first) {
        first.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
      return visibleCount > 0;
    };

    const handleAssistantAction = (data) => {
      switch (data.action) {
        case 'reset_feed':
          resetFeed({ keepMessage: false });
          break;
        case 'highlight_post':
          if (data.postId) {
            highlightPost(data.postId, false);
          }
          break;
        case 'open_post_comments':
          if (data.postId) {
            highlightPost(data.postId, true);
          }
          break;
        case 'filter_user':
        case 'filter_subject':
          applyPostFilter(data.matchedPostIds || [], 'I could not find a matching post in the current feed.');
          break;
        default:
          break;
      }
    };

    const sendToAssistant = async (message) => {
      addMessage(message, 'user');
      const typing = addMessage('Crea is thinking...', 'bot', 'is-typing');
      sendBtn.disabled = true;

      try {
        const response = await fetch('./chatbot.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify({ message, posts: postsData })
        });

        const data = await response.json();
        typing.remove();

        if (!response.ok || !data.success) {
          throw new Error(data.message || 'Crea could not answer right now.');
        }

        addMessage(data.reply || 'Done.', 'bot');
        handleAssistantAction(data);
      } catch (error) {
        console.error(error);
        typing.remove();
        addMessage('I could not process that request right now. Please try again.', 'bot');
      } finally {
        sendBtn.disabled = false;
      }
    };

    fab.setAttribute('type', 'button');
    /* ── DRAG & DROP ── */
let isDragging = false;
let dragStartX = 0, dragStartY = 0;
let initialLeft = 0, initialBottom = 0;

const getPos = () => {
  const rect = root.getBoundingClientRect();
  return {
    left: rect.left,
    bottom: window.innerHeight - rect.bottom
  };
};

const startDrag = (clientX, clientY) => {
  isDragging = true;
  dragStartX = clientX;
  dragStartY = clientY;
  const pos = getPos();
  initialLeft = pos.left;
  initialBottom = pos.bottom;
  root.classList.add('is-dragging');
  // Applique les coordonnées absolues dès le début
  root.style.left = initialLeft + 'px';
  root.style.bottom = initialBottom + 'px';
  root.style.right = 'auto';
  root.style.top = 'auto';
};

const moveDrag = (clientX, clientY) => {
  if (!isDragging) return;
  const dx = clientX - dragStartX;
  const dy = clientY - dragStartY; // positif = bas → réduit le bottom
  let newLeft = initialLeft + dx;
  let newBottom = initialBottom - dy;

  // Garder dans la fenêtre (avec 12px de marge)
  const margin = 12;
  const maxLeft = window.innerWidth - root.offsetWidth - margin;
  const maxBottom = window.innerHeight - root.offsetHeight - margin;
  newLeft = Math.max(margin, Math.min(newLeft, maxLeft));
  newBottom = Math.max(margin, Math.min(newBottom, maxBottom));

  root.style.left = newLeft + 'px';
  root.style.bottom = newBottom + 'px';
};

const endDrag = () => {
  if (!isDragging) return;
  isDragging = false;
  root.classList.remove('is-dragging');
};

// Mouse
fab.addEventListener('mousedown', (e) => {
  // Clic simple (pas de mouvement) → ouvre/ferme le panel
  // On démarre le drag mais on le détecte seulement si mouvement réel
  dragStartX = e.clientX;
  dragStartY = e.clientY;
  const pos = getPos();
  initialLeft = pos.left;
  initialBottom = pos.bottom;

  const onMouseMove = (ev) => {
    const dist = Math.hypot(ev.clientX - dragStartX, ev.clientY - dragStartY);
    if (!isDragging && dist > 5) {
      // Mouvement suffisant → on est en drag
      isDragging = true;
      root.classList.add('is-dragging');
      root.style.left = initialLeft + 'px';
      root.style.bottom = initialBottom + 'px';
      root.style.right = 'auto';
      root.style.top = 'auto';
    }
    if (isDragging) moveDrag(ev.clientX, ev.clientY);
  };

  const onMouseUp = (ev) => {
    const wasDragging = isDragging;
    endDrag();
    document.removeEventListener('mousemove', onMouseMove);
    document.removeEventListener('mouseup', onMouseUp);
    if (wasDragging) {
      // Empêche le clic d'ouvrir le panel après un drag
      ev.stopImmediatePropagation();
    }
  };

  document.addEventListener('mousemove', onMouseMove);
  document.addEventListener('mouseup', onMouseUp);
});

// Touch
fab.addEventListener('touchstart', (e) => {
  const touch = e.touches[0];
  dragStartX = touch.clientX;
  dragStartY = touch.clientY;
  const pos = getPos();
  initialLeft = pos.left;
  initialBottom = pos.bottom;
}, { passive: true });

fab.addEventListener('touchmove', (e) => {
  const touch = e.touches[0];
  const dist = Math.hypot(touch.clientX - dragStartX, touch.clientY - dragStartY);
  if (!isDragging && dist > 5) {
    isDragging = true;
    root.classList.add('is-dragging');
    root.style.left = initialLeft + 'px';
    root.style.bottom = initialBottom + 'px';
    root.style.right = 'auto';
    root.style.top = 'auto';
  }
  if (isDragging) {
    e.preventDefault();
    moveDrag(touch.clientX, touch.clientY);
  }
}, { passive: false });

fab.addEventListener('touchend', (e) => {
  if (isDragging) {
    endDrag();
    e.preventDefault(); // évite le clic fantôme
  }
}, { passive: false });
/* ── FIN DRAG & DROP ── */
    fab.addEventListener('click', (event) => {
      event.preventDefault();
      event.stopPropagation();
      setOpen(!panel.classList.contains('is-open'));
    });

    closeBtn?.addEventListener('click', (event) => {
      event.preventDefault();
      event.stopPropagation();
      setOpen(false);
    });

    document.addEventListener('click', (event) => {
      if (!root.classList.contains('is-open')) return;
      if (root.contains(event.target)) return;
      setOpen(false);
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && root.classList.contains('is-open')) {
        setOpen(false);
      }
    });

    formEl.addEventListener('submit', async (event) => {
      event.preventDefault();
      const message = input.value.trim();
      if (!message) return;
      input.value = '';
      input.style.height = 'auto';
      await sendToAssistant(message);
    });

    input.addEventListener('input', () => {
      input.style.height = 'auto';
      input.style.height = Math.min(input.scrollHeight, 120) + 'px';
    });

    input.addEventListener('keydown', (event) => {
      if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        formEl.requestSubmit();
      }
    });

    quickActions?.addEventListener('click', (event) => {
      const chip = event.target.closest('.crea-quick-chip');
      if (!chip) return;
      if (chip.dataset.creaAction === 'reset') {
        resetFeed();
        return;
      }
      const prompt = chip.dataset.creaPrompt || '';
      if (prompt) {
        input.value = prompt;
        formEl.requestSubmit();
      }
    });
  }
  subject?.addEventListener("input", function () { validateSubject(); updateCounters(); });
  textContent?.addEventListener("input", function () { validateTextContent(); updateCounters(); });
  image?.addEventListener("change", function () { validateImage(); renderImagePreview(); });
  video?.addEventListener("change", function () { validateVideo(); renderVideoPreview(); });

  updateCounters();
  setupFeedVideos();
  setupReactionButtons();
  setupViewTracking();
  setupAiButtons();
  initCreaChatbot();

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

