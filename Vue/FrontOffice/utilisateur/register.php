<?php
require_once '../../../Controleur/utilisateurC.php';
require_once '../../../Controleur/profileC.php';

$error = "";
$allowedPublicRoles = ['createur', 'marque'];
$preselectedRole = strtolower(trim($_GET['role'] ?? ''));
if (!in_array($preselectedRole, $allowedPublicRoles, true)) {
    $preselectedRole = '';
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['nom'])) {
    $submittedRole = strtolower(trim($_POST['role'] ?? ''));
    if (in_array($submittedRole, $allowedPublicRoles, true)) {
        $preselectedRole = $submittedRole;
    }

    if (empty($_POST['g-recaptcha-response'])) {
        $error = "Veuillez valider le reCAPTCHA ❌";
    } else {
        $secret = "6Le_S9ksAAAAAOEjx9cRk48RuR3fYR1RxZrSWtYk";

        $verify = file_get_contents(
            "https://www.google.com/recaptcha/api/siteverify?secret=".$secret."&response=".$_POST['g-recaptcha-response']
        );

        $result = json_decode($verify);

        if (!$result->success) {
            $error = "Vérification humaine échouée ❌";
        } elseif (!in_array($submittedRole, $allowedPublicRoles, true)) {
            $error = "Veuillez choisir un rôle valide ❌";
        } elseif (!empty($_POST['nom']) &&
            filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) &&
            strlen($_POST['password']) >= 6) {

            $faceDescriptor = '';
            $postedFaceDescriptor = trim($_POST['faceDescriptor'] ?? '');
            $aboutMe = trim((string)($_POST['aboutMe'] ?? ''));
            $aboutLength = function_exists('mb_strlen') ? mb_strlen($aboutMe, 'UTF-8') : strlen($aboutMe);

            if ($aboutLength > 800) {
                $error = "About me must be 800 characters or fewer ❌";
            } elseif ($aboutMe !== '') {
                $profileC = new ProfileC();
                if (!$profileC->profileSupportsAboutMe()) {
                    $error = "The profile.aboutMe column is missing. Please add it in the database first ❌";
                }
            }

            if (empty($error) && $postedFaceDescriptor !== '') {
                $decoded = json_decode($postedFaceDescriptor, true);

                // 🔒 validate only when the optional face scan is used
                if (!is_array($decoded) || count($decoded) !== 128) {
                    $error = "Erreur reconnaissance visage ❌";
                } else {
                    $faceDescriptor = json_encode($decoded); // propre
                }
            }

            if (empty($error)) {
                $user = new Utilisateur(
                    null,
                    $_POST['nom'],
                    $_POST['email'],
                    password_hash($_POST['password'], PASSWORD_DEFAULT),
                    $submittedRole,
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
                    if ($aboutMe !== '') {
                        try {
                            $db = config::getConnexion();
                            $stmt = $db->prepare("SELECT id FROM utilisateur WHERE email = ? LIMIT 1");
                            $stmt->execute([$_POST['email']]);
                            $newUserId = (int) $stmt->fetchColumn();

                            if ($newUserId > 0) {
                                $profileC = isset($profileC) && $profileC instanceof ProfileC ? $profileC : new ProfileC();
                                $profileC->upsertProfileDetails($newUserId, null, $aboutMe, false, true);
                            }
                        } catch (Throwable $e) {
                            // Registration must not be blocked if the optional bio cannot be saved.
                            // The user can update it later from Profile Settings.
                        }
                    }

                    header("Location: login.php");
                    exit();
                }
            }
        } else {
            $error = "Veuillez vérifier les informations saisies ❌";
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
                padding: .15rem 0;
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
        

            .public-nav-logo {
                width: 170px;
                height: auto;
                max-height: 52px;
                object-fit: contain;
                display: block;
            }

            .auth-hero-logo {
                max-width: 250px;
                width: 60%;
                height: auto;
                object-fit: contain;
                filter: drop-shadow(0 18px 32px rgba(0, 0, 0, 0.18));
            }



            .role-card-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: .75rem;
            }

            .role-card-input {
                position: absolute;
                opacity: 0;
                pointer-events: none;
            }

            .role-card {
                border: 1.5px solid rgba(78, 84, 200, 0.28);
                border-radius: 16px;
                background: #fff;
                padding: .9rem .85rem;
                min-height: 86px;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: .65rem;
                text-align: left;
                transition: .2s ease;
                box-shadow: 0 .45rem 1.1rem rgba(31, 38, 135, .06);
            }

            .role-card i {
                width: 38px;
                height: 38px;
                border-radius: 12px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                color: #fff;
                background: linear-gradient(135deg, #1f2cf3 0%, #7b22d8 55%, #e0188a 100%);
                font-size: 1.05rem;
                flex: 0 0 auto;
            }

            .role-card-title {
                font-weight: 800;
                color: #2a2f3a;
                line-height: 1.1;
            }

            .role-card-subtitle {
                font-size: .78rem;
                color: #747b89;
                margin-top: .2rem;
            }

            .role-card-input:checked + .role-card {
                border-color: #4e54c8;
                background: linear-gradient(135deg, rgba(31, 44, 243, .08), rgba(224, 24, 138, .08));
                box-shadow: 0 .65rem 1.4rem rgba(95, 49, 232, .14);
            }

            .role-card-input:focus + .role-card {
                outline: 3px solid rgba(78, 84, 200, .18);
            }

            @media (max-width: 575.98px) {
                .role-card-grid {
                    grid-template-columns: 1fr;
                }
            }

            .face-scan-panel {
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
                box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
                background: #111;
            }

            .face-overlay {
                position: absolute;
                inset: 0;
                width: 100%;
                height: 100%;
                pointer-events: none;
                border-radius: 12px;
                z-index: 2;
            }


            .face-detection-box {
                position: absolute;
                z-index: 4;
                border: 3px solid #22c55e;
                border-radius: 8px;
                box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.18), 0 0 18px rgba(34, 197, 94, 0.55);
                pointer-events: none;
                display: none;
            }

            .face-detection-box[hidden] {
                display: none !important;
            }

            .face-detection-box span {
                position: absolute;
                left: -3px;
                top: -30px;
                padding: 4px 9px;
                border-radius: 999px;
                background: #22c55e;
                color: #ffffff;
                font-size: 12px;
                font-weight: 800;
                line-height: 1;
                white-space: nowrap;
                box-shadow: 0 6px 14px rgba(34, 197, 94, 0.35);
            }

            .about-match-hint {
                display: flex;
                gap: .55rem;
                align-items: flex-start;
                border: 1px solid rgba(91, 79, 255, 0.18);
                background: rgba(91, 79, 255, 0.07);
                color: #4b43c7;
                border-radius: 12px;
                padding: .65rem .75rem;
                font-size: .84rem;
                line-height: 1.35;
                margin-top: .5rem;
            }

            .about-match-hint i {
                margin-top: .05rem;
                flex: 0 0 auto;
            }

            .about-counter {
                font-size: .76rem;
            }
</style>
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
<link rel="icon" type="image/png" sizes="32x32" href="../../public/images/logo.png">
<link rel="shortcut icon" type="image/png" href="../../public/images/logo.png">
<link rel="apple-touch-icon" href="../../public/images/logo.png">
        
    </head>
    <body class="d-flex flex-column h-100 bg-light auth-shell">
        <header class="auth-topbar bg-white">
            <div class="container-fluid px-3 px-lg-4 d-flex align-items-center justify-content-between gap-3">
                <a class="navbar-brand m-0 d-inline-flex align-items-center" href="index.php"><img src="../../public/images/logoweb.png" alt="Cre8Connect" class="auth-brand-logo"></a>
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
        <div class="role-card-grid" role="radiogroup" aria-label="Choose a role">
            <div class="position-relative">
                <input class="role-card-input" type="radio" name="role" id="roleCreateur" value="createur" <?php echo $preselectedRole === 'createur' ? 'checked' : ''; ?>>
                <label class="role-card" for="roleCreateur">
                    <i class="bi bi-camera-reels"></i>
                    <span>
                        <span class="role-card-title d-block">Creator</span>
                        <span class="role-card-subtitle d-block">Create and collaborate</span>
                    </span>
                </label>
            </div>
            <div class="position-relative">
                <input class="role-card-input" type="radio" name="role" id="roleMarque" value="marque" <?php echo $preselectedRole === 'marque' ? 'checked' : ''; ?>>
                <label class="role-card" for="roleMarque">
                    <i class="bi bi-building"></i>
                    <span>
                        <span class="role-card-title d-block">Brand</span>
                        <span class="role-card-subtitle d-block">Launch campaigns</span>
                    </span>
                </label>
            </div>
        </div>
        <small id="roleError" class="text-danger d-block mt-1"></small>
    </div>
    <div class="mb-3">
        <label for="aboutMe" class="form-label fw-semibold">About me <span class="text-muted fw-normal">(optional)</span></label>
        <textarea id="aboutMe" name="aboutMe" class="form-control" rows="3" maxlength="800" placeholder="Tell creators or brands who you are, what you do, and what kind of collaborations you like."><?php echo htmlspecialchars($_POST['aboutMe'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
        <div class="about-match-hint" id="aboutMatchHint">
            <i class="bi bi-stars"></i>
            <span id="aboutMatchHintText">Choose Creator or Brand to see how this helps Cre8Connect suggest better matches.</span>
        </div>
        <div class="d-flex justify-content-end text-muted about-counter mt-1">
            <span id="aboutCount">0</span>/800
        </div>
    </div>

   <div class="g-recaptcha" data-sitekey="6Le_S9ksAAAAALQ8QeII5XANm_kyXmRF-Sq5OBt8"></div>

      <br/>
    <div id="faceScanPanel" class="face-scan-panel mb-3" style="display:none;">
        <div class="face-video-wrap">
            <video id="video" autoplay muted playsinline class="face-video"></video>
            <canvas id="faceOverlay" class="face-overlay"></canvas>
            <div id="faceBox" class="face-detection-box" hidden></div>
        </div>
        <div id="faceScanStatus" class="small text-muted mt-2">Camera is open. Click Capture / Retry when your face is clear.</div>
        <div class="d-flex gap-2 mt-3">
            <button type="button" id="cancelFaceBtn" class="btn btn-outline-secondary flex-fill py-2">Cancel</button>
            <button type="button" id="retryFaceBtn" class="btn btn-gradient flex-fill py-2">Capture / Retry</button>
        </div>
    </div>
<input type="hidden" name="faceDescriptor" id="faceDescriptor">

<button type="button" id="scanBtn" class="btn btn-gradient w-100 py-2 mb-3">
    Scan face <span class="fw-normal">(optional)</span>
</button>
<button type="submit" id="submitBtn" class="btn btn-outline-gradient w-100 py-2">
    Create Account
</button>
</form>
<?php if (!empty($error)) { ?>
    <div class="alert alert-danger">
        <?php echo $error; ?>
    </div>
<?php } ?>

                        <p class="mt-3 text-muted">
                            Already have an account?
                            <a href="login.php">Login</a>
                        </p>

                    </div>

                    <!-- IMAGE (DROITE) -->
                    <div class="col-lg-6 d-none d-lg-flex align-items-center justify-content-center bg-gradient-primary-to-secondary position-relative">

                        <img src="assets/logo.png" alt="Cre8Connect logo" class="img-fluid animate-img auth-hero-logo">

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
let roleInputs = document.querySelectorAll('input[name="role"]');


const aboutMe = document.getElementById("aboutMe");
const aboutCount = document.getElementById("aboutCount");
const aboutMatchHintText = document.getElementById("aboutMatchHintText");

const aboutRoleMessages = {
    createur: "For creators: this helps Cre8Connect understand your niche, audience, style, and goals so brands can discover you and collaboration opportunities can match you better.",
    marque: "For brands: this helps Cre8Connect present your brand, products, values, and campaign style so creators can choose collaborations that fit them best.",
    default: "Choose Creator or Brand to see how this helps Cre8Connect suggest better matches."
};

const aboutRolePlaceholders = {
    createur: "Tell brands what you create, your niche, audience, style, or collaboration goals.",
    marque: "Tell creators about your brand, products, values, campaign style, and ideal collaborators.",
    default: "Tell creators or brands who you are, what you do, and what kind of collaborations you like."
};

function updateAboutRoleHint() {
    const role = getSelectedRole();
    if (aboutMatchHintText) {
        aboutMatchHintText.textContent = aboutRoleMessages[role] || aboutRoleMessages.default;
    }
    if (aboutMe) {
        aboutMe.placeholder = aboutRolePlaceholders[role] || aboutRolePlaceholders.default;
    }
}

function updateAboutCount() {
    if (aboutMe && aboutCount) {
        aboutCount.textContent = String(aboutMe.value.length);
    }
}

aboutMe?.addEventListener("input", updateAboutCount);
updateAboutCount();

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
function getSelectedRole() {
    const checked = document.querySelector('input[name="role"]:checked');
    return checked ? checked.value : "";
}

function validateRole() {
    let error = document.getElementById("roleError");
    if (getSelectedRole() === "") {
        error.textContent = "Veuillez sélectionner un rôle";
        return false;
    }
    error.textContent = "";
    return true;
}

roleInputs.forEach(input => {
    input.addEventListener("change", () => {
        validateRole();
        updateAboutRoleHint();
    });
});
updateAboutRoleHint();

// ===== SUBMIT =====
document.getElementById("registerForm").addEventListener("submit", function (e) {

    if (
        nom.value.trim().length < 3 ||
        !/^[a-zA-Z\s]+$/.test(nom.value) ||
        !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value) ||
        password.value.length < 6 ||
        !validateRole()
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
    const overlay = document.getElementById("faceOverlay");
    const faceBox = document.getElementById("faceBox");
    const overlayCtx = overlay ? overlay.getContext("2d") : null;
    const faceScanPanel = document.getElementById("faceScanPanel");
    const faceScanStatus = document.getElementById("faceScanStatus");
    const cancelFaceBtn = document.getElementById("cancelFaceBtn");
    const retryFaceBtn = document.getElementById("retryFaceBtn");

    const faceDescriptorInput = document.getElementById("faceDescriptor");
    const removeScanText = "Remove face scan";

    let faceScanSaved = false;
    let faceModelsReady = false;
    let cameraOpen = false;
    let scanInProgress = false;
    let stream = null;
    let liveLoopId = null;
    let liveDetecting = false;
    let lastDescriptor = null;

    submitBtn.disabled = false;
    scanBtn.disabled = true;
    scanBtn.innerHTML = 'Scan face <span class="fw-normal">(optional)</span>';
    faceScanPanel.style.display = 'none';

    function updateStatus(message, type = 'muted') {
        faceScanStatus.textContent = message;
        faceScanStatus.className = 'small mt-2 text-' + type;
    }

    function hideFaceBox() {
        if (!faceBox) return;
        faceBox.hidden = true;
        faceBox.style.display = 'none';
    }

    function showFaceBox(x, y, w, h) {
        if (!faceBox) return;
        faceBox.hidden = false;
        faceBox.style.display = 'block';
        faceBox.style.left = `${Math.max(0, x)}px`;
        faceBox.style.top = `${Math.max(0, y)}px`;
        faceBox.style.width = `${Math.max(20, w)}px`;
        faceBox.style.height = `${Math.max(20, h)}px`;
    }

    function clearOverlay() {
        if (overlayCtx) {
            overlayCtx.clearRect(0, 0, overlay.width || 0, overlay.height || 0);
        }
        hideFaceBox();
    }

    function resizeOverlayToVideo() {
        if (!overlay || !video) return false;
        const width = video.clientWidth || video.videoWidth || 0;
        const height = video.clientHeight || video.videoHeight || 0;
        if (!width || !height) return false;
        if (overlay.width !== width || overlay.height !== height) {
            overlay.width = width;
            overlay.height = height;
        }
        return true;
    }

    function drawDetectionBox(detection) {
        if (!overlayCtx || !resizeOverlayToVideo() || !video.videoWidth || !video.videoHeight) return;
        clearOverlay();
        const box = detection.detection.box;
        const scaleX = overlay.width / video.videoWidth;
        const scaleY = overlay.height / video.videoHeight;
        const x = box.x * scaleX;
        const y = box.y * scaleY;
        const w = box.width * scaleX;
        const h = box.height * scaleY;

        showFaceBox(x, y, w, h);

        overlayCtx.save();
        overlayCtx.lineWidth = 3;
        overlayCtx.strokeStyle = "#22c55e";
        overlayCtx.shadowColor = "rgba(34, 197, 94, 0.8)";
        overlayCtx.shadowBlur = 8;
        overlayCtx.strokeRect(x, y, w, h);
        overlayCtx.shadowBlur = 0;

        overlayCtx.restore();
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
                    .withFaceLandmarks()
                    .withFaceDescriptor();

                if (detection) {
                    lastDescriptor = Array.from(detection.descriptor);
                    drawDetectionBox(detection);
                    if (!scanInProgress) updateStatus("Face detected ✅. Click Capture / Retry to save it.", 'success');
                } else {
                    lastDescriptor = null;
                    clearOverlay();
                    if (!scanInProgress) updateStatus("No face detected ❌. Adjust your position.", 'danger');
                }
            } catch (error) {
                console.error(error);
                lastDescriptor = null;
                clearOverlay();
                if (!scanInProgress) updateStatus("Face detection failed. Please try again.", 'danger');
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

    function stopCamera() {
        stopLiveDetection();
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }
        video.srcObject = null;
        cameraOpen = false;
    }

    function closeCameraPanel() {
        stopCamera();
        faceScanPanel.style.display = 'none';
        retryFaceBtn.disabled = false;
        scanInProgress = false;
    }

    function resetFaceScan() {
        faceDescriptorInput.value = "";
        faceScanSaved = false;
        lastDescriptor = null;
        closeCameraPanel();
        scanBtn.innerHTML = 'Scan face <span class="fw-normal">(optional)</span>';
        scanBtn.disabled = !faceModelsReady;
        updateStatus("Camera is open. Click Capture / Retry when your face is clear.", 'muted');
    }

    async function startCamera() {
        if (!stream) {
            stream = await navigator.mediaDevices.getUserMedia({ video: true });
            video.srcObject = stream;
            await video.play();
        }
        cameraOpen = true;
        resizeOverlayToVideo();
        stopLiveDetection();
        liveLoopId = requestAnimationFrame(liveDetectLoop);
    }

    async function openFacePanel() {
        if (!faceModelsReady) {
            updateStatus("Face scan is not available right now. You can still create your account.", 'warning');
            return;
        }

        try {
            faceScanPanel.style.display = 'block';
            updateStatus("Opening camera...", 'muted');
            await startCamera();
            updateStatus("Camera is open. Click Capture / Retry when your face is clear.", 'muted');
        } catch (err) {
            console.error(err);
            closeCameraPanel();
            alert("Impossible d'accéder à la caméra. Vérifiez les autorisations ou réessayez.");
        }
    }

    function captureFace() {
        if (!cameraOpen || scanInProgress) return;
        scanInProgress = true;
        retryFaceBtn.disabled = true;
        updateStatus("Scanning face...", 'muted');

        if (!lastDescriptor) {
            updateStatus("Face not detected ❌. Adjust your position and click Capture / Retry.", 'danger');
            retryFaceBtn.disabled = false;
            scanInProgress = false;
            return;
        }

        faceDescriptorInput.value = JSON.stringify(lastDescriptor);
        faceScanSaved = true;
        closeCameraPanel();
        submitBtn.disabled = false;
        scanBtn.textContent = removeScanText;
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
        await openFacePanel();
    };

    cancelFaceBtn.onclick = () => resetFaceScan();
    retryFaceBtn.onclick = () => captureFace();

    window.addEventListener("resize", resizeOverlayToVideo);
    window.addEventListener("beforeunload", stopCamera);
});
</script>

<!-- ✅ reCAPTCHA OK -->
<script src="https://www.google.com/recaptcha/api.js" async defer></script>    </body>
</html>
