<?php
require_once '../../../Controleur/utilisateurC.php';

$loginMessage = "";
$resetMessage = "";

$userC = new UtilisateurC();

if (isset($_POST['login'])) {
    $loginMessage = $userC->login($_POST['email'], $_POST['password']);
}
if (isset($_POST['reset_email'])) {
    $resetMessage = $userC->sendResetLink($_POST['reset_email']);
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>Cre8Connect - Login</title>
        <!-- Favicon-->
        <!-- Custom Google font-->
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@100;200;300;400;500;600;700;800;900&amp;display=swap" rel="stylesheet" />
        <!-- Bootstrap icons-->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet" />
        <!-- Core theme CSS (includes Bootstrap)-->
        <link href="css/styles.css" rel="stylesheet" />
        <style>
           .auth-shell {
    min-height: 100vh;
}

.auth-topbar {
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
}

.auth-main {
    padding: 2rem 0;
}

.auth-home-link {
    border-radius: 999px;
    font-weight: 700;
}

.auth-brand-logo {
    width: 235px;
    height: auto;
    max-height: 72px;
    object-fit: contain;
    display: block;
}

@media (max-width: 575.98px) {
    .auth-brand-logo {
        width: 175px;
        max-height: 56px;
    }
}

.auth-card {
    border-radius: 20px;
}

.public-lang-switch {
    display: inline-flex;
    align-items: center;
    gap: .25rem;
    border: 1px solid rgba(78, 84, 200, .22);
    border-radius: 999px;
    padding: .2rem;
    background: #fff;
}

.public-lang-switch button {
    border: 0;
    border-radius: 999px;
    background: transparent;
    color: #5f6674;
    font-weight: 800;
    font-size: .72rem;
    padding: .25rem .55rem;
}

.public-lang-switch button.is-active {
    background: #4e54c8;
    color: #fff;
}

           .text-gradient {
    background: linear-gradient(45deg, #4e54c8, #8f94fb);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.btn-gradient {
    background: linear-gradient(45deg, #4e54c8, #8f94fb);
    border: none;
    color: white;
    font-weight: 600;
    transition: 0.3s;
}

.btn-gradient:hover {
    opacity: 0.9;
    transform: translateY(-2px);
}

.input-custom {
    border-radius: 10px;
    padding: 12px;
    border: 1px solid #ddd;
    transition: 0.3s;
}

.input-custom:focus {
    border-color: #4e54c8;
    box-shadow: 0 0 8px rgba(78, 84, 200, 0.3);
}

.face-login-panel {
    padding: 0.85rem;
    border: 1px solid rgba(78, 84, 200, 0.16);
    border-radius: 16px;
    background: rgba(78, 84, 200, 0.04);
}

.face-video-wrap {
    position: relative;
    width: 100%;
    max-width: 320px;
    margin: 0 auto;
    line-height: 0;
}

.face-video {
    width: 100%;
    display: block;
    border-radius: 12px;
    background: #111;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
}

.face-overlay {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    border-radius: 12px;
}

@media (max-width: 575.98px) {
    .auth-main {
        padding: 1.25rem 0;
    }

    .auth-form-panel {
        padding: 2rem !important;
    }
} </style>
<link rel="icon" type="image/png" sizes="16x16" href="../../public/images/favicon-16.png">
<link rel="icon" type="image/png" sizes="32x32" href="../../public/images/favicon-32.png">
<link rel="shortcut icon" type="image/png" href="../../public/images/favicon-32.png">
<link rel="apple-touch-icon" sizes="180x180" href="../../public/images/apple-touch-icon.png">
    </head>
    <body class="d-flex flex-column h-100 bg-light auth-shell">
        <header class="auth-topbar bg-white">
            <div class="container-fluid px-3 px-lg-4 d-flex align-items-center justify-content-between gap-3">
                <a class="navbar-brand m-0 d-inline-flex align-items-center" href="index.php"><img src="../../public/images/logoweb.png" alt="Cre8Connect" class="auth-brand-logo"></a>
                <div class="d-flex align-items-center gap-2">
                    <div class="public-lang-switch" aria-label="Language">
                        <button type="button" data-lang-choice="en">EN</button>
                        <button type="button" data-lang-choice="fr">FR</button>
                    </div>
                    <a class="btn btn-outline-dark btn-sm auth-home-link px-3" href="index.php">&larr; <span data-i18n="auth.home">Home</span></a>
                </div>
            </div>
        </header>
        <main class="flex-grow-1 d-flex align-items-center justify-content-center auth-main">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-10">

            <!-- CARD -->
            <div class="card shadow-lg border-0 overflow-hidden auth-card">

                <div class="row g-0">

            <div class="col-lg-6 p-5 d-flex flex-column justify-content-center auth-form-panel">

    <h2 class="fw-bold mb-4 text-gradient text-center" data-i18n="auth.loginTitle">Log in to your account</h2>

    <?php if (!empty($loginMessage)): ?>
        <div class="alert alert-danger alert-dismissible fade show text-center" role="alert">
            ⚠️ <?php echo htmlspecialchars($loginMessage); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form method="POST" class="shadow p-4 rounded-4 bg-white">

        <div class="mb-3">
            <input type="text" name="email" class="form-control input-custom" placeholder="Email" data-i18n-placeholder="auth.email">
        </div>

        <div class="mb-3">
            <input type="password" name="password" class="form-control input-custom" placeholder="Password" data-i18n-placeholder="auth.password">
        </div>

        <button name="login" class="btn btn-gradient w-100 py-2 mb-3">
            <span data-i18n="auth.signIn">Sign in</span>
        </button>

        <!-- Face login panel -->
        <div id="faceLoginPanel" class="face-login-panel mb-3" style="display:none;">
            <div class="face-video-wrap">
                <video id="video" autoplay muted playsinline class="face-video"></video>
                <canvas id="faceOverlay" class="face-overlay"></canvas>
            </div>
            <div id="faceLoginStatus" class="small text-muted mt-2 text-center">
                <span data-i18n="auth.faceCameraOpen">Camera is open. Click Capture / Retry when your face is clear.</span>
            </div>
            <div class="d-flex gap-2 mt-3">
                <button type="button" id="cancelFaceLogin" class="btn btn-outline-secondary flex-fill py-2" data-i18n="auth.cancel">Cancel</button>
                <button type="button" id="captureFaceLogin" class="btn btn-gradient flex-fill py-2" data-i18n="auth.captureRetry">Capture / Retry</button>
            </div>
        </div>

        <button type="button" class="btn btn-outline-primary w-100 py-2" id="scanLogin">
            <span data-i18n="auth.faceLogin">Log in with Face</span>
        </button>

    </form>

    <p class="mt-3 text-center text-muted">
        <span data-i18n="auth.noAccount">You don't have an account?</span>
        <a href="register.php" class="fw-bold text-gradient" data-i18n="auth.signUp">Sign up</a>
    </p>

    <p class="text-center">
        <a href="#" data-bs-toggle="modal" data-bs-target="#forgotModal" class="text-decoration-none">
            <span data-i18n="auth.forgotPasswordQ">Forgot password?</span>
        </a>
    </p>

</div>

                    <!-- IMAGE (RIGHT) -->
                    <div class="col-lg-6 d-none d-lg-flex align-items-center justify-content-center bg-gradient-primary-to-secondary position-relative">

                        <img src="assets/logo.png" class="img-fluid animate-img" style="max-width: 250px;">

                    </div>

                </div>

            </div>

        </div>
    </div>
</div>
</main>
<div class="modal fade" id="forgotModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content p-4" style="border-radius: 15px;">

      <div class="modal-header border-0">
        <h5 class="modal-title" data-i18n="auth.forgotPassword">Forgot password</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">

        <form method="POST" id="resetForm">

          <input type="email" name="reset_email" id="emailInput"
                 class="form-control mb-2"
                 placeholder="Enter your email" data-i18n-placeholder="auth.enterEmail" required>

          <small id="emailError" class="text-danger d-none">
            <span data-i18n="auth.invalidEmail">Invalid email</span>
          </small>

          <button name="reset" class="btn btn-primary w-100 mt-2" id="resetBtn">
            <span id="btnText" data-i18n="auth.sendLink">Send link</span>
            <span id="btnLoader" class="spinner-border spinner-border-sm d-none"></span>
          </button>

        </form>

      </div>

    </div>
  </div>
</div>
        <!-- Footer-->
        <footer class="bg-white py-4 mt-auto">
            <div class="container px-5">
                <div class="row align-items-center justify-content-between flex-column flex-sm-row">
                    <div class="col-auto"><div class="small m-0">Copyright &copy; cre8connect 2026</div></div>
                    <div class="col-auto">
                        <a class="small" href="#!" data-i18n="auth.privacy">Privacy</a>
                        <span class="mx-1">&middot;</span>
                        <a class="small" href="#!" data-i18n="auth.terms">Terms</a>
                        <span class="mx-1">&middot;</span>
                        <a class="small" href="#!" data-i18n="auth.contact">Contact</a>
                    </div>
                </div>
            </div>
            
        </footer>
        <!-- Bootstrap core JS-->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="../layout/front-translate.js"></script>
<script>
const cre8AuthTranslations = {
    en: {
        'auth.home': 'Home',
        'auth.loginTitle': 'Log in to your account',
        'auth.email': 'Email',
        'auth.password': 'Password',
        'auth.signIn': 'Sign in',
        'auth.faceCameraOpen': 'Camera is open. Click Capture / Retry when your face is clear.',
        'auth.cancel': 'Cancel',
        'auth.captureRetry': 'Capture / Retry',
        'auth.faceLogin': 'Log in with Face',
        'auth.noAccount': "You don't have an account?",
        'auth.signUp': 'Sign up',
        'auth.forgotPasswordQ': 'Forgot password?',
        'auth.forgotPassword': 'Forgot password',
        'auth.enterEmail': 'Enter your email',
        'auth.invalidEmail': 'Invalid email',
        'auth.sendLink': 'Send link',
        'auth.faceDetectedLogin': 'Face detected. Click Capture / Retry to log in.',
        'auth.noFaceDetected': 'No face detected. Adjust your position.',
        'auth.faceDetectionFailed': 'Face detection failed. Please try again.',
        'auth.scanningFace': 'Scanning face...',
        'auth.faceNotDetectedRetry': 'Face not detected. Adjust your position and click Capture / Retry.',
        'auth.faceChecking': 'Face detected. Checking account...',
        'auth.userNotRecognized': 'User not recognized',
        'auth.faceLoginFailed': 'Face login failed. Please click Capture / Retry again.',
        'auth.faceUnavailable': 'Face login unavailable',
        'auth.faceNotAvailable': 'Face login is not available right now.',
        'auth.cameraPermission': 'Could not access the camera. Check permissions or try again.',
        'auth.privacy': 'Privacy',
        'auth.terms': 'Terms',
        'auth.contact': 'Contact'
    },
    fr: {
        'auth.home': 'Accueil',
        'auth.loginTitle': 'Connectez-vous a votre compte',
        'auth.email': 'Email',
        'auth.password': 'Mot de passe',
        'auth.signIn': 'Se connecter',
        'auth.faceCameraOpen': 'La camera est ouverte. Cliquez sur Capturer / Reessayer quand votre visage est clair.',
        'auth.cancel': 'Annuler',
        'auth.captureRetry': 'Capturer / Reessayer',
        'auth.faceLogin': 'Connexion avec Face ID',
        'auth.noAccount': "Vous n avez pas de compte ?",
        'auth.signUp': 'S inscrire',
        'auth.forgotPasswordQ': 'Mot de passe oublie ?',
        'auth.forgotPassword': 'Mot de passe oublie',
        'auth.enterEmail': 'Entrez votre email',
        'auth.invalidEmail': 'Email invalide',
        'auth.sendLink': 'Envoyer le lien',
        'auth.faceDetectedLogin': 'Visage detecte. Cliquez sur Capturer / Reessayer pour vous connecter.',
        'auth.noFaceDetected': 'Aucun visage detecte. Ajustez votre position.',
        'auth.faceDetectionFailed': 'Detection du visage echouee. Veuillez reessayer.',
        'auth.scanningFace': 'Scan du visage...',
        'auth.faceNotDetectedRetry': 'Visage non detecte. Ajustez votre position et cliquez sur Capturer / Reessayer.',
        'auth.faceChecking': 'Visage detecte. Verification du compte...',
        'auth.userNotRecognized': 'Utilisateur non reconnu',
        'auth.faceLoginFailed': 'Connexion Face ID echouee. Cliquez sur Capturer / Reessayer.',
        'auth.faceUnavailable': 'Connexion Face ID indisponible',
        'auth.faceNotAvailable': 'La connexion Face ID est indisponible pour le moment.',
        'auth.cameraPermission': 'Impossible d acceder a la camera. Verifiez les autorisations ou reessayez.',
        'auth.privacy': 'Confidentialite',
        'auth.terms': 'Conditions',
        'auth.contact': 'Contact'
    }
};
function cre8AuthLang() {
    if (typeof cre8FrontReadLang === 'function') return cre8FrontReadLang();
    try { return (localStorage.getItem('cre8_front_lang') || localStorage.getItem('cre8_lang')) === 'fr' ? 'fr' : 'en'; } catch (e) { return 'en'; }
}
function cre8AuthText(key) {
    const lang = cre8AuthLang();
    return (cre8AuthTranslations[lang] && cre8AuthTranslations[lang][key]) || cre8AuthTranslations.en[key] || key;
}
function cre8RegisterAuthTranslations() {
    if (typeof cre8RegisterTranslations === 'function') cre8RegisterTranslations(cre8AuthTranslations);
    document.title = cre8AuthLang() === 'fr' ? 'Cre8Connect - Connexion' : 'Cre8Connect - Login';
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', cre8RegisterAuthTranslations); else cre8RegisterAuthTranslations();
window.addEventListener('cre8:languagechange', cre8RegisterAuthTranslations);
</script>
        <script defer src="https://cdn.jsdelivr.net/npm/face-api.js/dist/face-api.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", async () => {
    const video = document.getElementById("video");
    const scanBtn = document.getElementById("scanLogin");
    const faceLoginPanel = document.getElementById("faceLoginPanel");
    const faceLoginStatus = document.getElementById("faceLoginStatus");
    const cancelFaceLogin = document.getElementById("cancelFaceLogin");
    const captureFaceLogin = document.getElementById("captureFaceLogin");
    const overlay = document.getElementById("faceOverlay");
    const overlayCtx = overlay ? overlay.getContext("2d") : null;

    let modelsLoaded = false;
    let cameraOpen = false;
    let stream = null;
    let scanInProgress = false;
    let liveLoopId = null;
    let liveDetecting = false;

    scanBtn.disabled = true;

    function updateStatus(message, type = "muted") {
        faceLoginStatus.textContent = cre8AuthText(message);
        faceLoginStatus.className = "small mt-2 text-center text-" + type;
    }

    function clearOverlay() {
        if (overlayCtx) {
            overlayCtx.clearRect(0, 0, overlay.width || 0, overlay.height || 0);
        }
    }

    function resizeOverlayToVideo() {
        if (!overlay || !video) {
            return false;
        }

        const width = video.clientWidth || video.videoWidth || 0;
        const height = video.clientHeight || video.videoHeight || 0;

        if (!width || !height) {
            return false;
        }

        if (overlay.width !== width || overlay.height !== height) {
            overlay.width = width;
            overlay.height = height;
        }

        return true;
    }

    function drawDetectionBox(detection) {
        if (!overlayCtx || !resizeOverlayToVideo() || !video.videoWidth || !video.videoHeight) {
            return;
        }

        clearOverlay();

        const box = detection.detection.box;
        const scaleX = overlay.width / video.videoWidth;
        const scaleY = overlay.height / video.videoHeight;

        overlayCtx.lineWidth = 3;
        overlayCtx.strokeStyle = "#22c55e";
        overlayCtx.shadowColor = "rgba(34, 197, 94, 0.8)";
        overlayCtx.shadowBlur = 8;
        overlayCtx.strokeRect(
            box.x * scaleX,
            box.y * scaleY,
            box.width * scaleX,
            box.height * scaleY
        );
        overlayCtx.shadowBlur = 0;
    }

    async function liveDetectLoop() {
        if (!cameraOpen) {
            clearOverlay();
            return;
        }

        if (!liveDetecting && video.readyState >= 2) {
            liveDetecting = true;

            try {
                const detection = await faceapi
                    .detectSingleFace(video)
                    .withFaceLandmarks();

                if (detection) {
                    drawDetectionBox(detection);
                    if (!scanInProgress) {
                        updateStatus("auth.faceDetectedLogin", "success");
                    }
                } else {
                    clearOverlay();
                    if (!scanInProgress) {
                        updateStatus("auth.noFaceDetected", "danger");
                    }
                }
            } catch (error) {
                console.error(error);
                clearOverlay();
                if (!scanInProgress) {
                    updateStatus("auth.faceDetectionFailed", "danger");
                }
            } finally {
                liveDetecting = false;
            }
        }

        liveLoopId = requestAnimationFrame(liveDetectLoop);
    }

    function stopLiveDetection() {
        if (liveLoopId) {
            cancelAnimationFrame(liveLoopId);
            liveLoopId = null;
        }
        liveDetecting = false;
        clearOverlay();
    }

    async function startCamera() {
        if (!stream) {
            stream = await navigator.mediaDevices.getUserMedia({ video: true });
            video.srcObject = stream;
            await video.play();
        }

        cameraOpen = true;
        faceLoginPanel.style.display = "block";
        updateStatus("auth.faceCameraOpen", "muted");
        resizeOverlayToVideo();
        stopLiveDetection();
        liveLoopId = requestAnimationFrame(liveDetectLoop);
    }

    function stopCamera() {
        stopLiveDetection();

        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }

        video.srcObject = null;
        cameraOpen = false;
        faceLoginPanel.style.display = "none";
        captureFaceLogin.disabled = false;
        scanInProgress = false;
        updateStatus("auth.faceCameraOpen", "muted");
    }

    function getModelBasePath() {
        const currentPath = window.location.pathname.replace(/\/[^\/]*$/, "");
        return window.location.origin + currentPath + "/../../../models";
    }

    async function captureAndLogin() {
        if (!cameraOpen || scanInProgress) {
            return;
        }

        scanInProgress = true;
        captureFaceLogin.disabled = true;
        updateStatus("auth.scanningFace", "muted");

        try {
            const detection = await faceapi
                .detectSingleFace(video)
                .withFaceLandmarks()
                .withFaceDescriptor();

            if (!detection) {
                clearOverlay();
                updateStatus("auth.faceNotDetectedRetry", "danger");
                return;
            }

            drawDetectionBox(detection);
            updateStatus("auth.faceChecking", "success");

            const descriptor = Array.from(detection.descriptor);

            const response = await fetch("login_face.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ face: descriptor })
            });

            const data = await response.json();

            if (data.success) {
                stopCamera();
                window.location.href = data.redirect;
                return;
            }

            const distanceText = Number.isFinite(Number(data.distance))
                ? " Distance: " + Number(data.distance).toFixed(4)
                : "";

            updateStatus((data.message || cre8AuthText("auth.userNotRecognized")) + distanceText, "danger");
        } catch (error) {
            console.error(error);
            updateStatus("auth.faceLoginFailed", "danger");
        } finally {
            captureFaceLogin.disabled = false;
            scanInProgress = false;
        }
    }

    try {
        const modelBase = getModelBasePath();
        await faceapi.nets.faceRecognitionNet.loadFromUri(modelBase);
        await faceapi.nets.faceLandmark68Net.loadFromUri(modelBase);
        await faceapi.nets.ssdMobilenetv1.loadFromUri(modelBase);

        modelsLoaded = true;
        scanBtn.disabled = false;
    } catch (error) {
        console.error("Face models failed to load ❌", error);
        scanBtn.textContent = cre8AuthText("auth.faceUnavailable");
        scanBtn.disabled = true;
    }

    scanBtn.addEventListener("click", async () => {
        if (!modelsLoaded) {
            updateStatus("auth.faceNotAvailable", "warning");
            return;
        }

        try {
            await startCamera();
        } catch (error) {
            console.error(error);
            stopCamera();
            alert(cre8AuthText("auth.cameraPermission"));
        }
    });

    cancelFaceLogin.addEventListener("click", () => {
        stopCamera();
    });

    captureFaceLogin.addEventListener("click", async () => {
        await captureAndLogin();
    });

    window.addEventListener("beforeunload", () => {
        stopCamera();
    });
});
</script>
<script>
const form = document.getElementById("resetForm");
const emailInput = document.getElementById("emailInput");
const emailError = document.getElementById("emailError");
const btnText = document.getElementById("btnText");
const btnLoader = document.getElementById("btnLoader");

// 📧 live validation
emailInput.addEventListener("input", function() {
    const email = emailInput.value;
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (!regex.test(email)) {
        emailError.classList.remove("d-none");
        emailInput.classList.add("is-invalid");
    } else {
        emailError.classList.add("d-none");
        emailInput.classList.remove("is-invalid");
    }
});

// ⏳ loader
form.addEventListener("submit", function() {
    btnText.classList.add("d-none");
    btnLoader.classList.remove("d-none");
});
</script>
<div class="toast position-fixed bottom-0 end-0 m-4" id="toastMsg">
  <div class="toast-body bg-success text-white rounded">
    <?= $resetMessage ?? "" ?>
  </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- 🔥 Toast script -->
<?php if (!empty($resetMessage)) { ?>
<script>
    var toast = new bootstrap.Toast(document.getElementById('toastMsg'));
    toast.show();
</script>
<?php } ?>
    </body>
</html>
