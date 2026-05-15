<?php
require_once '../../../Controleur/utilisateurC.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['nom'])) {
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = strtolower(trim($_POST['role'] ?? ''));

    // Public registration is only for normal public users.
    // Admin accounts must be created through a separate secure/admin-only flow.
    $allowedPublicRoles = ['createur', 'marque'];

    if (empty($_POST['g-recaptcha-response'])) {
        $error = "Veuillez valider le reCAPTCHA ❌";
    } else {
        $secret = "6Le_S9ksAAAAAOEjx9cRk48RuR3fYR1RxZrSWtYk";

        $verify = file_get_contents(
            "https://www.google.com/recaptcha/api/siteverify?secret=" . $secret . "&response=" . $_POST['g-recaptcha-response']
        );

        $result = json_decode($verify);

        if (!$result || empty($result->success)) {
            $error = "Vérification humaine échouée ❌";
        } elseif (!in_array($role, $allowedPublicRoles, true)) {
            $error = "Invalid role. Public sign-up is only available for Creator and Brand accounts.";
        } elseif (
            empty($nom) ||
            !filter_var($email, FILTER_VALIDATE_EMAIL) ||
            strlen($password) < 6
        ) {
            $error = "Veuillez vérifier les informations saisies ❌";
        } else {
            $faceDescriptor = "";
            $rawFaceDescriptor = trim($_POST['faceDescriptor'] ?? '');

            // Face ID is optional. If the user scanned a face, validate and save it.
            // If not, keep an empty descriptor so normal sign-up still works.
            if ($rawFaceDescriptor !== '') {
                $decoded = json_decode($rawFaceDescriptor, true);

                if (!is_array($decoded) || count($decoded) !== 128) {
                    $error = "Erreur reconnaissance visage ❌";
                } else {
                    $faceDescriptor = json_encode(array_values($decoded));
                }
            }

            if (empty($error)) {
                $user = new Utilisateur(
                    null,
                    $nom,
                    $email,
                    password_hash($password, PASSWORD_DEFAULT),
                    $role,
                    "actif",
                    0,
                    null,
                    $faceDescriptor
                );

                $userC = new UtilisateurC();
                $result = $userC->ajouterUser($user);

                if ($result !== 'success') {
                    $error = $result;
                } else {
                    header("Location: login.php");
                    exit();
                }
            }
        }
    }
}
?> 

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>Cre8Connect - Register</title>
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

            .auth-card {
                border-radius: 20px;
            }

            .btn-gradient {
                background: linear-gradient(45deg, #4e54c8, #8f94fb);
                border: none;
                color: white;
                font-weight: 600;
                transition: 0.3s ease;
            }

            .btn-gradient:hover {
                opacity: 0.92;
                transform: translateY(-2px);
            }

            .btn-outline-gradient {
                color: #4e54c8;
                border: 1px solid #4e54c8;
                background: transparent;
                font-weight: 600;
                transition: 0.3s ease;
            }

            .btn-outline-gradient:hover {
                background: rgba(78, 84, 200, 0.08);
                color: #3d45b5;
            }

            @media (max-width: 575.98px) {
                .auth-main {
                    padding: 1.25rem 0;
                }

                .auth-form-panel {
                    padding: 2rem !important;
                }
            }
        
            .face-scan-panel {
                display: none;
                border: 1px solid rgba(78, 84, 200, 0.16);
                background: rgba(78, 84, 200, 0.04);
                border-radius: 16px;
                padding: 0.9rem;
            }

            .face-video-wrap {
                position: relative;
                width: 100%;
                max-width: 320px;
                margin: 0 auto;
                line-height: 0;
                border-radius: 14px;
                overflow: hidden;
                background: #111827;
                box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
            }

            .face-video {
                display: block;
                width: 100%;
                height: auto;
                border-radius: 14px;
            }

            .face-overlay-canvas {
                position: absolute;
                inset: 0;
                width: 100%;
                height: 100%;
                pointer-events: none;
            }

            .face-action-row {
                display: flex;
                gap: 0.5rem;
                margin-top: 0.75rem;
            }

            .face-action-row .btn {
                flex: 1;
            }
</style>
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
<link rel="icon" type="image/png" sizes="32x32" href="../../public/images/logo.png">
<link rel="shortcut icon" type="image/png" href="../../public/images/logo.png">
<link rel="apple-touch-icon" href="../../public/images/logo.png">
        
    </head>
    <body class="d-flex flex-column h-100 bg-light auth-shell">
        <header class="auth-topbar bg-white py-3">
            <div class="container px-4 px-lg-5 d-flex align-items-center justify-content-between gap-3">
                <a class="navbar-brand m-0" href="index.php"><span class="fw-bolder text-primary">CRE8CONNECT</span></a>
                <a class="btn btn-outline-dark btn-sm auth-home-link px-3" href="index.php">&larr; Home</a>
            </div>
        </header>
        <main class="flex-grow-1 d-flex align-items-center justify-content-center auth-main">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-10">

            <!-- CARD -->
            <div class="card shadow-lg border-0 overflow-hidden auth-card">

                <div class="row g-0">

                    <!-- FORMULAIRE (GAUCHE) -->
                    <div class="col-lg-6 p-5 d-flex flex-column justify-content-center auth-form-panel">

                        <h2 class="fw-bolder mb-4 text-gradient">Create Account</h2>

                        <form id="registerForm" method="POST" >

    <div class="mb-3">
        <input type="text" id="nom" name="nom" class="form-control" placeholder="Name">
        <small id="nomError" class="text-danger"></small>
    </div>

    <div class="mb-3">
        <input type="text" id="email" name="email" class="form-control" placeholder="Email">
        <small id="emailError" class="text-danger"></small>
    </div>

    <div class="mb-3">
        <input type="password" id="password" name="password" class="form-control" placeholder="Password">
        <small id="passwordError" class="text-danger"></small>
    </div>

    <div class="mb-3">
        <select id="role" name="role" class="form-control">
            <option value="">Choose a role</option>
            <option value="createur">Creator</option>
            <option value="marque">Brand</option>
        </select>
        <small id="roleError" class="text-danger"></small>
    </div>
   <div class="g-recaptcha" data-sitekey="6Le_S9ksAAAAALQ8QeII5XANm_kyXmRF-Sq5OBt8"></div>
      <br/>
<input type="hidden" name="faceDescriptor" id="faceDescriptor">

<button type="button" id="scanBtn" class="btn btn-gradient w-100 py-2 mb-3">
    Scan face <span class="fw-normal">(optional)</span>
</button>

<div id="faceScanPanel" class="face-scan-panel mb-3">
    <div class="face-video-wrap">
        <video id="video" class="face-video" autoplay muted playsinline></video>
        <canvas id="faceOverlay" class="face-overlay-canvas"></canvas>
    </div>
    <div id="faceScanStatus" class="small text-muted mt-2">
        Camera ready. Center your face, then click Capture / Retry.
    </div>
    <div class="face-action-row">
        <button type="button" id="cancelFaceBtn" class="btn btn-outline-secondary btn-sm">Cancel</button>
        <button type="button" id="retryFaceBtn" class="btn btn-gradient btn-sm">Capture / Retry</button>
    </div>
</div>

<button type="submit" id="submitBtn" class="btn btn-outline-gradient w-100 py-2">
    Create Account
</button>
</form>
<?php if (!empty($error)) { ?>
    <div class="alert alert-danger">
        <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
    </div>
<?php } ?>

                        <p class="mt-3 text-muted">
                            Already have an account?
                            <a href="login.php">Login</a>
                        </p>

                    </div>

                    <!-- IMAGE (DROITE) -->
                    <div class="col-lg-6 d-none d-lg-flex align-items-center justify-content-center bg-gradient-primary-to-secondary position-relative">

                        <img src="assets/logo.png" class="img-fluid animate-img" style="max-width: 250px;">

                    </div>

                </div>

            </div>

        </div>
    </div>
</div>

</main>
        
        <!-- Footer-->
        <footer class="bg-white py-4 mt-auto">
            <div class="container px-5">
                <div class="row align-items-center justify-content-between flex-column flex-sm-row">
                    <div class="col-auto"><div class="small m-0">Copyright &copy; cre8connect 2026</div></div>
                    <div class="col-auto">
                        <a class="small" href="#!">Privacy</a>
                        <span class="mx-1">&middot;</span>
                        <a class="small" href="#!">Terms</a>
                        <span class="mx-1">&middot;</span>
                        <a class="small" href="#!">Contact</a>
                    </div>
                </div>
            </div>
        </footer>
        <!-- Bootstrap core JS-->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
        <!-- Core theme JS-->
    
            
        </script>
        <script>

// récupération des champs
let nom = document.getElementById("nom");
let email = document.getElementById("email");
let password = document.getElementById("password");
let role = document.getElementById("role");

// ===== NOM =====
nom.addEventListener("input", function () {
    let error = document.getElementById("nomError");

    if (nom.value.trim() === "") {
        error.textContent = "Le nom est requis";
        nom.classList.add("is-invalid");
    } 
    else if (nom.value.length < 3) {
        error.textContent = "Le nom doit contenir au moins 3 caractères";
        nom.classList.add("is-invalid");
    }
    else if (!/^[a-zA-Z\s]+$/.test(nom.value)) {
        error.textContent = "Le nom ne doit contenir que des lettres";
        nom.classList.add("is-invalid");
    }
    else {
        error.textContent = "";
        nom.classList.remove("is-invalid");
        nom.classList.add("is-valid");
    }
});

// ===== EMAIL =====
email.addEventListener("input", function () {
    let error = document.getElementById("emailError");
    let regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (email.value.trim() === "") {
        error.textContent = "L'email est requis";
        email.classList.add("is-invalid");
    }
    else if (!regex.test(email.value)) {
        error.textContent = "Veuillez entrer une adresse email valide";
        email.classList.add("is-invalid");
    }
    else {
        error.textContent = "";
        email.classList.remove("is-invalid");
        email.classList.add("is-valid");
    }
});

// ===== PASSWORD =====
password.addEventListener("input", function () {
    let error = document.getElementById("passwordError");

    if (password.value.trim() === "") {
        error.textContent = "Le mot de passe est requis";
        password.classList.add("is-invalid");
    }
    else if (password.value.length < 6) {
        error.textContent = "Minimum 6 caractères";
        password.classList.add("is-invalid");
    }
    else {
        error.textContent = "";
        password.classList.remove("is-invalid");
        password.classList.add("is-valid");
    }
});

// ===== ROLE =====
role.addEventListener("change", function () {
    let error = document.getElementById("roleError");

    if (role.value === "") {
        error.textContent = "Please choose a role";
        role.classList.add("is-invalid");
    } else if (!["createur", "marque"].includes(role.value)) {
        error.textContent = "Admin sign-up is not available from this page";
        role.classList.add("is-invalid");
    } else {
        error.textContent = "";
        role.classList.remove("is-invalid");
        role.classList.add("is-valid");
    }
});

// ===== SUBMIT =====
document.getElementById("registerForm").addEventListener("submit", function (e) {

    if (
        nom.value.trim().length < 3 ||
        !/^[a-zA-Z\s]+$/.test(nom.value) ||
        !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value) ||
        password.value.length < 6 ||
        !["createur", "marque"].includes(role.value)
    ) {
        e.preventDefault();
        alert("Veuillez corriger les erreurs avant de continuer !");
    }

});

</script>

<!-- ✅ 1. charger face-api AVANT -->
<script defer src="https://cdn.jsdelivr.net/npm/face-api.js/dist/face-api.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", async () => {

    const submitBtn = document.getElementById("submitBtn");
    const scanBtn = document.getElementById("scanBtn");
    const video = document.getElementById("video");
    const faceOverlay = document.getElementById("faceOverlay");
    const faceScanPanel = document.getElementById("faceScanPanel");
    const faceScanStatus = document.getElementById("faceScanStatus");
    const cancelFaceBtn = document.getElementById("cancelFaceBtn");
    const retryFaceBtn = document.getElementById("retryFaceBtn");

    // Face scan is optional, so registration must stay available.
    const faceDescriptorInput = document.getElementById("faceDescriptor");
    const defaultScanText = "Scan face (optional)";
    const removeScanText = "Remove face scan";
    let faceScanSaved = false;
    let faceModelsReady = false;
    let facePanelOpen = false;
    let liveDetectionFrame = null;
    let liveDetectionRunning = false;
    let latestDetection = null;
    let latestDetectionAt = 0;
    let lastDetectionTick = 0;

    submitBtn.disabled = false;
    scanBtn.disabled = true;
    scanBtn.textContent = defaultScanText;
    faceScanPanel.style.display = "none";

    function updateStatus(message, type = "muted") {
        const validType = ["muted", "danger", "success", "warning"].includes(type) ? type : "muted";
        faceScanStatus.className = "small mt-2 text-" + validType;
        faceScanStatus.textContent = message;
    }

    function clearOverlay() {
        if (!faceOverlay) {
            return;
        }

        const ctx = faceOverlay.getContext("2d");
        ctx.clearRect(0, 0, faceOverlay.width || 0, faceOverlay.height || 0);
    }

    function syncOverlaySize() {
        const rect = video.getBoundingClientRect();
        const width = Math.max(1, Math.round(rect.width));
        const height = Math.max(1, Math.round(rect.height));

        if (faceOverlay.width !== width || faceOverlay.height !== height) {
            faceOverlay.width = width;
            faceOverlay.height = height;
        }

        return { width, height };
    }

    function drawFaceBox(detection) {
        if (!detection) {
            clearOverlay();
            return;
        }

        const displaySize = syncOverlaySize();
        const resized = faceapi.resizeResults(detection, displaySize);
        const box = resized.detection.box;
        const ctx = faceOverlay.getContext("2d");

        ctx.clearRect(0, 0, faceOverlay.width, faceOverlay.height);
        ctx.lineWidth = 3;
        ctx.strokeStyle = "#16a34a";
        ctx.shadowColor = "rgba(22, 163, 74, 0.55)";
        ctx.shadowBlur = 8;
        ctx.strokeRect(box.x, box.y, box.width, box.height);

        ctx.shadowBlur = 0;
        ctx.fillStyle = "rgba(22, 163, 74, 0.92)";
        ctx.font = "13px Arial";
        const label = "Face detected";
        const labelWidth = ctx.measureText(label).width + 12;
        const labelY = Math.max(0, box.y - 24);
        ctx.fillRect(box.x, labelY, labelWidth, 22);
        ctx.fillStyle = "#ffffff";
        ctx.fillText(label, box.x + 6, labelY + 15);
    }

    function stopLiveDetection() {
        facePanelOpen = false;
        latestDetection = null;
        latestDetectionAt = 0;
        lastDetectionTick = 0;
        liveDetectionRunning = false;

        if (liveDetectionFrame) {
            cancelAnimationFrame(liveDetectionFrame);
            liveDetectionFrame = null;
        }

        clearOverlay();
    }

    async function liveDetectionLoop(timestamp) {
        if (!facePanelOpen) {
            return;
        }

        liveDetectionFrame = requestAnimationFrame(liveDetectionLoop);

        // Avoid launching many heavy face-api detections at the same time.
        if (liveDetectionRunning || timestamp - lastDetectionTick < 350) {
            return;
        }

        if (!video.videoWidth || !video.videoHeight) {
            return;
        }

        liveDetectionRunning = true;
        lastDetectionTick = timestamp;

        try {
            const detection = await faceapi
                .detectSingleFace(video)
                .withFaceLandmarks()
                .withFaceDescriptor();

            if (!facePanelOpen) {
                return;
            }

            if (detection) {
                latestDetection = detection;
                latestDetectionAt = Date.now();
                drawFaceBox(detection);
                updateStatus("Face detected ✅. Click Capture / Retry to save it.", "success");
            } else {
                latestDetection = null;
                latestDetectionAt = 0;
                clearOverlay();
                updateStatus("No face detected ❌. Move closer and center your face.", "danger");
            }
        } catch (error) {
            console.error("Live face detection error:", error);
            clearOverlay();
            updateStatus("Face detection error. Please retry.", "warning");
        } finally {
            liveDetectionRunning = false;
        }
    }

    function stopCamera() {
        stopLiveDetection();

        if (video.srcObject) {
            video.srcObject.getTracks().forEach(track => track.stop());
            video.srcObject = null;
        }

        video.removeAttribute("src");
        video.load();
    }

    function closeCameraPanel() {
        stopCamera();
        faceScanPanel.style.display = "none";
        retryFaceBtn.disabled = false;
    }

    function resetFaceScan() {
        faceDescriptorInput.value = "";
        faceScanSaved = false;
        closeCameraPanel();
        scanBtn.textContent = defaultScanText;
        scanBtn.disabled = !faceModelsReady;
        updateStatus("Camera ready. Center your face, then click Capture / Retry.", "muted");
    }

    async function startCamera() {
        if (!video.srcObject) {
            const stream = await navigator.mediaDevices.getUserMedia({ video: true });
            video.srcObject = stream;
            await video.play();
        }

        facePanelOpen = true;
        syncOverlaySize();
        updateStatus("Looking for your face...", "muted");
        liveDetectionFrame = requestAnimationFrame(liveDetectionLoop);
    }

    async function openFacePanel() {
        faceScanPanel.style.display = "block";
        retryFaceBtn.disabled = false;
        updateStatus("Opening camera...", "muted");
        await startCamera();
    }

    async function captureFace() {
        retryFaceBtn.disabled = true;
        updateStatus("Capturing face...", "muted");

        try {
            const hasFreshDetection = latestDetection && (Date.now() - latestDetectionAt < 1500);
            const detection = hasFreshDetection
                ? latestDetection
                : await faceapi
                    .detectSingleFace(video)
                    .withFaceLandmarks()
                    .withFaceDescriptor();

            if (!detection) {
                updateStatus("No face detected ❌. Keep the camera open and try again.", "danger");
                clearOverlay();
                retryFaceBtn.disabled = false;
                return;
            }

            drawFaceBox(detection);

            const descriptor = Array.from(detection.descriptor);
            faceDescriptorInput.value = JSON.stringify(descriptor);
            faceScanSaved = true;

            closeCameraPanel();
            scanBtn.textContent = removeScanText;
            scanBtn.disabled = false;
        } catch (err) {
            console.error(err);
            updateStatus("Unable to capture the face. Please retry.", "danger");
            retryFaceBtn.disabled = false;
        }
    }

    function getModelBasePath() {
        const currentPath = window.location.pathname.replace(/\/[^\/]*$/, '');
        return window.location.origin + currentPath + '/../../../models';
    }

    try {
        const modelBase = getModelBasePath();
        await faceapi.nets.faceRecognitionNet.loadFromUri(modelBase);
        await faceapi.nets.faceLandmark68Net.loadFromUri(modelBase);
        await faceapi.nets.ssdMobilenetv1.loadFromUri(modelBase);

        console.log("Models loaded ✅", modelBase);

        faceModelsReady = true;
        scanBtn.disabled = false;

    } catch (error) {
        console.error("Erreur chargement modèles ❌", error);
        scanBtn.textContent = "Face scan unavailable (optional)";
        scanBtn.disabled = true;
    }

    scanBtn.onclick = async () => {
        if (faceScanSaved || faceDescriptorInput.value.trim() !== "") {
            resetFaceScan();
            return;
        }

        try {
            await openFacePanel();
        } catch (err) {
            console.error(err);
            updateStatus("Cannot access camera. Check permission and retry.", "danger");
        }
    };

    cancelFaceBtn.onclick = () => {
        closeCameraPanel();
        updateStatus("Camera ready. Center your face, then click Capture / Retry.", "muted");
    };

    retryFaceBtn.onclick = captureFace;

    window.addEventListener("beforeunload", stopCamera);
});
</script>

<!-- ✅ reCAPTCHA OK -->
<script src="https://www.google.com/recaptcha/api.js" async defer></script>    </body>
</html>
