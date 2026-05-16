<?php
require_once __DIR__ . '/../layout/session_bridge.php';
require_once __DIR__ . '/../../../Controleur/session_helper.php';
require_once __DIR__ . '/../../../Controleur/profileC.php';

$currentFrontUser = cre8_front_require_user();
$currentRole = cre8_front_normalize_role($currentFrontUser['role'] ?? '');

if (isBackOfficeRole($currentRole)) {
    $scriptPath = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');
    $frontOfficeMarker = '/Vue/FrontOffice/';
    $frontOfficePos = strpos($scriptPath, $frontOfficeMarker);
    $projectBase = $frontOfficePos !== false ? substr($scriptPath, 0, $frontOfficePos) : '';
    header('Location: ' . $projectBase . '/Vue/BackOffice/utilisateur/profile_settings.php');
    exit;
}

$userId = (int)($currentFrontUser['id'] ?? 0);
$profileC = new ProfileC();
$flash = null;

if (!function_exists('cre8_front_profile_sync_session')) {
    function cre8_front_profile_sync_session(string $name, ?string $imageName): void
    {
        $_SESSION['nom'] = $name;
        if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
            $_SESSION['user']['nom'] = $name;
        }
        if (isset($_SESSION['utilisateur']) && is_array($_SESSION['utilisateur'])) {
            $_SESSION['utilisateur']['nom'] = $name;
        }
        if ($imageName !== null) {
            $_SESSION['profile_image'] = $imageName;
        }
    }
}

if (!function_exists('cre8_validate_face_descriptor_json')) {
    function cre8_validate_face_descriptor_json(string $raw): array
    {
        $decoded = json_decode($raw, true);
        if (!is_array($decoded) || count($decoded) !== 128) {
            return ['valid' => false, 'value' => null];
        }

        $clean = [];
        foreach ($decoded as $value) {
            if (!is_numeric($value)) {
                return ['valid' => false, 'value' => null];
            }
            $clean[] = (float)$value;
        }

        return ['valid' => true, 'value' => json_encode($clean)];
    }
}

if (!function_exists('cre8_front_profile_save')) {
    function cre8_front_profile_save(ProfileC $profileC, int $userId): array
    {
        $name = trim((string)($_POST['nom'] ?? ''));
        if ($name === '') {
            return ['type' => 'danger', 'message' => 'Name is required.'];
        }

        $aboutMe = trim((string)($_POST['aboutMe'] ?? ''));
        if (function_exists('mb_strlen') ? mb_strlen($aboutMe, 'UTF-8') > 800 : strlen($aboutMe) > 800) {
            return ['type' => 'danger', 'message' => 'About me must be 800 characters or fewer.'];
        }

        if (!$profileC->profileSupportsAboutMe()) {
            return ['type' => 'danger', 'message' => 'The profile.aboutMe column is missing. Please add it in the database first.'];
        }

        $removeFace = (string)($_POST['removeFaceDescriptor'] ?? '') === '1';
        $postedFaceDescriptor = trim((string)($_POST['faceDescriptor'] ?? ''));
        $faceDescriptorToSave = null;
        $shouldUpdateFace = false;

        if ($removeFace) {
            $shouldUpdateFace = true;
            $faceDescriptorToSave = null;
        } elseif ($postedFaceDescriptor !== '') {
            $validation = cre8_validate_face_descriptor_json($postedFaceDescriptor);
            if (empty($validation['valid'])) {
                return ['type' => 'danger', 'message' => 'Invalid face scan data. Please scan your face again.'];
            }
            $shouldUpdateFace = true;
            $faceDescriptorToSave = $validation['value'];
        }

        if ($shouldUpdateFace && !$profileC->userSupportsFaceDescriptor()) {
            return ['type' => 'danger', 'message' => 'Face ID storage is not available in the utilisateur table.'];
        }

        $oldImageName = $profileC->getProfileImageName($userId);
        $upload = $profileC->storeUploadedImage($userId, $_FILES['profile_image'] ?? []);
        if (empty($upload['success'])) {
            return ['type' => 'danger', 'message' => $upload['message'] ?? 'Invalid profile photo.'];
        }

        $newImageName = $upload['imageName'] ?? null;

        try {
            if (!$profileC->updateUserDisplayName($userId, $name)) {
                if ($newImageName !== null) {
                    $profileC->deleteProfileImage($newImageName);
                }
                return ['type' => 'danger', 'message' => 'Could not update your display name.'];
            }

            if (!$profileC->upsertProfileDetails($userId, $newImageName, $aboutMe, $newImageName !== null, true)) {
                if ($newImageName !== null) {
                    $profileC->deleteProfileImage($newImageName);
                }
                return ['type' => 'danger', 'message' => 'Could not save your profile details.'];
            }

            if ($shouldUpdateFace && !$profileC->updateUserFaceDescriptor($userId, $faceDescriptorToSave)) {
                if ($newImageName !== null) {
                    $profileC->deleteProfileImage($newImageName);
                }
                return ['type' => 'danger', 'message' => 'Could not update your Face ID.'];
            }
        } catch (Throwable $e) {
            if ($newImageName !== null) {
                $profileC->deleteProfileImage($newImageName);
            }
            return ['type' => 'danger', 'message' => 'Could not update your profile.'];
        }

        if ($newImageName !== null && $oldImageName !== null && $oldImageName !== $newImageName) {
            $profileC->deleteProfileImage($oldImageName);
        }

        cre8_front_profile_sync_session($name, $newImageName);

        return ['type' => 'success', 'message' => 'Profile updated successfully.'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $flash = cre8_front_profile_save($profileC, $userId);
    $_SESSION['front_profile_flash'] = $flash;
    header('Location: profile_settings.php');
    exit;
}

if (isset($_SESSION['front_profile_flash']) && is_array($_SESSION['front_profile_flash'])) {
    $flash = $_SESSION['front_profile_flash'];
    unset($_SESSION['front_profile_flash']);
}

$userRow = $profileC->getUserById($userId) ?: $currentFrontUser;
$userName = trim((string)($userRow['nom'] ?? $currentFrontUser['nom'] ?? 'User'));
$userName = $userName !== '' ? $userName : 'User';
$userInitial = function_exists('mb_substr') ? mb_substr($userName, 0, 1, 'UTF-8') : substr($userName, 0, 1);
$userInitial = strtoupper((string)$userInitial) ?: 'U';
$profileImageUrl = $profileC->getProfileImageUrl($userId, '../../public/uploads/profile');
$aboutMe = $profileC->getProfileAboutMe($userId);
if ($currentRole === 'marque') {
    $aboutPlaceholder = 'Tell creators about your brand, products, values, campaign style, and ideal collaborators.';
    $aboutHintText = 'For brands: this helps Cre8Connect present your brand, products, values, and campaign style so creators can choose collaborations that fit them best.';
} elseif ($currentRole === 'createur') {
    $aboutPlaceholder = 'Tell brands what you create, your niche, audience, style, or collaboration goals.';
    $aboutHintText = 'For creators: this helps Cre8Connect understand your niche, audience, style, and goals so brands can discover you and collaboration opportunities can match you better.';
} else {
    $aboutPlaceholder = 'Tell creators or brands who you are, what you do, and what kind of collaborations you like.';
    $aboutHintText = 'This helps Cre8Connect understand your profile and suggest better collaboration matches.';
}
$hasFaceDescriptor = trim((string)($userRow['face_descriptor'] ?? '')) !== '';
$cancelUrl = $currentRole === 'marque' ? 'brand.php' : 'creator.php';
$frontActive = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/../layout/front-theme-bootstrap.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings - Cre8connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../layout/front-header.css" rel="stylesheet">
    <link rel="icon" type="image/png" sizes="16x16" href="../../public/images/favicon-16.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../../public/images/favicon-32.png">
    <link rel="shortcut icon" type="image/png" href="../../public/images/favicon-32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../../public/images/apple-touch-icon.png">
    <style>
        body {
            min-height: 100vh;
            background: var(--bg, #f6f6fc);
            color: var(--text, #0f0e1a);
            font-family: 'DM Sans', Arial, sans-serif;
        }
        .profile-settings-wrap {
            max-width: 860px;
            margin: 0 auto;
            padding: 42px 18px 72px;
        }
        .profile-settings-card {
            background: var(--white, #fff);
            border: 1px solid var(--border, #ebebf2);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 12px 34px rgba(15, 14, 26, 0.08);
        }
        .profile-settings-avatar {
            width: 86px;
            height: 86px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #5b4fff, #8b5cf6);
            color: #fff;
            font-size: 30px;
            font-weight: 800;
            object-fit: cover;
            box-shadow: 0 12px 28px rgba(91, 79, 255, 0.18);
        }
        .profile-section {
            border: 1px solid var(--border, #ebebf2);
            border-radius: 16px;
            padding: 18px;
            margin-bottom: 18px;
            background: color-mix(in srgb, var(--white, #fff) 92%, var(--primary-light, #ece9ff));
        }
        .profile-section-title {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            margin-bottom: 0.9rem;
            font-weight: 800;
            color: var(--text, #0f0e1a);
        }
        .face-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            border-radius: 999px;
            padding: 0.32rem 0.75rem;
            font-size: 0.82rem;
            font-weight: 800;
            background: var(--primary-light, #ece9ff);
            color: var(--primary, #5b4fff);
        }
        .face-status-pill.is-missing {
            background: #fff7ed;
            color: #c2410c;
        }
        .face-camera-panel {
            display: none;
            margin-top: 1rem;
            border: 1px solid var(--border, #ebebf2);
            border-radius: 16px;
            padding: 14px;
            background: rgba(15, 14, 26, 0.03);
        }
        .face-camera-panel.is-open {
            display: block;
        }
        .face-video-wrap {
            position: relative;
            width: min(100%, 420px);
            max-width: 420px;
            aspect-ratio: 4 / 3;
            border-radius: 14px;
            overflow: hidden;
            background: #111827;
            box-shadow: 0 12px 26px rgba(0,0,0,0.14);
        }
        #faceVideo,
        #faceCanvas {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover;
        }
        #faceVideo {
            position: relative;
            z-index: 1;
        }
        #faceCanvas {
            position: absolute;
            inset: 0;
            pointer-events: none;
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
        .face-live-message {
            font-weight: 800;
            margin: 0.75rem 0 0;
        }
        .face-live-message.ok {
            color: #059669;
        }
        .face-live-message.bad {
            color: #dc2626;
        }
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
    

        /* Face ID preview: match the working login/register coordinate model.
           Do not crop the video, otherwise the detection box is drawn off-face. */
        .face-video-wrap {
            position: relative !important;
            width: 100% !important;
            max-width: 420px !important;
            margin: 0 auto !important;
            line-height: 0 !important;
            aspect-ratio: auto !important;
            overflow: visible !important;
            border-radius: 14px !important;
            background: #111827 !important;
        }
        #faceVideo {
            width: 100% !important;
            height: auto !important;
            display: block !important;
            object-fit: contain !important;
            border-radius: 14px !important;
            background: #111827 !important;
        }
        #faceCanvas {
            position: absolute !important;
            inset: 0 !important;
            width: 100% !important;
            height: 100% !important;
            display: block !important;
            object-fit: contain !important;
            pointer-events: none !important;
            border-radius: 14px !important;
            z-index: 3 !important;
        }
        .face-detection-box {
            z-index: 4 !important;
        }

</style>
</head>
<body>
<?php require_once __DIR__ . '/../layout/header.php'; ?>

<main class="profile-settings-wrap">
    <div class="profile-settings-card">
        <div class="d-flex align-items-center gap-3 mb-4">
            <?php if ($profileImageUrl): ?>
                <img class="profile-settings-avatar" src="<?php echo htmlspecialchars($profileImageUrl); ?>" alt="Profile photo">
            <?php else: ?>
                <div class="profile-settings-avatar"><?php echo htmlspecialchars($userInitial); ?></div>
            <?php endif; ?>
            <div>
                <h1 class="h3 mb-1">Profile Settings</h1>
                <p class="text-muted mb-0">Update your name, profile photo, Face ID, and public bio.</p>
            </div>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?>" role="alert">
                <?php echo htmlspecialchars($flash['message']); ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" autocomplete="off">
            <div class="profile-section">
                <div class="profile-section-title">
                    <i class="bi bi-person-badge"></i>
                    Basic profile
                </div>

                <div class="mb-3">
                    <label class="form-label" for="profileName">Display name</label>
                    <input type="text" class="form-control" id="profileName" name="nom" value="<?php echo htmlspecialchars($userName); ?>" required>
                </div>

                <div class="mb-0">
                    <label class="form-label" for="profileImage">Profile photo</label>
                    <input type="file" class="form-control" id="profileImage" name="profile_image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                    <div class="form-text">JPG, PNG, or WEBP. Max 2MB.</div>
                </div>
            </div>

            <div class="profile-section">
                <div class="profile-section-title">
                    <i class="bi bi-camera"></i>
                    Face ID
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <span id="faceSavedStatus" class="face-status-pill <?php echo $hasFaceDescriptor ? '' : 'is-missing'; ?>">
                        <i class="bi <?php echo $hasFaceDescriptor ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill'; ?>"></i>
                        <?php echo $hasFaceDescriptor ? 'Face ID saved' : 'No Face ID saved'; ?>
                    </span>
                    <button type="button" id="scanFaceBtn" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-camera-video"></i>
                        <?php echo $hasFaceDescriptor ? 'Rescan Face ID' : 'Scan Face ID'; ?>
                    </button>
                    <?php if ($hasFaceDescriptor): ?>
                        <button type="button" id="removeFaceBtn" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-trash3"></i>
                            Remove Face ID
                        </button>
                    <?php endif; ?>
                </div>
                <p class="text-muted small mb-0">Face ID is optional. Use it if you want to log in with your face later.</p>

                <input type="hidden" name="faceDescriptor" id="faceDescriptor">
                <input type="hidden" name="removeFaceDescriptor" id="removeFaceDescriptor" value="0">

                <div id="faceCameraPanel" class="face-camera-panel">
                    <div class="face-video-wrap">
                        <video id="faceVideo" autoplay muted playsinline></video>
                        <canvas id="faceCanvas"></canvas>
                          <div id="faceBox" class="face-detection-box" hidden><span>Face detected</span></div>
                    </div>
                    <p id="faceLiveMessage" class="face-live-message bad">Camera ready. Put your face inside the frame.</p>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <button type="button" id="captureFaceBtn" class="btn btn-primary btn-sm">
                            <i class="bi bi-bounding-box-circles"></i>
                            Capture / Retry
                        </button>
                        <button type="button" id="cancelFaceBtn" class="btn btn-outline-secondary btn-sm">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>

            <div class="profile-section">
                <div class="profile-section-title">
                    <i class="bi bi-chat-square-heart"></i>
                    About me
                </div>
                <label class="form-label" for="aboutMe">Add something about you</label>
                <textarea class="form-control" id="aboutMe" name="aboutMe" maxlength="800" placeholder="<?php echo htmlspecialchars($aboutPlaceholder, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($aboutMe, ENT_QUOTES, 'UTF-8'); ?></textarea>
                <div class="profile-match-hint">
                    <i class="bi bi-stars"></i>
                    <span><?php echo htmlspecialchars($aboutHintText, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <div class="form-text d-flex justify-content-between">
                    <span>Optional, but useful for better matching.</span>
                    <span><span id="aboutCounter">0</span>/800 characters</span>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check2-circle"></i> Save changes
                </button>
                <a href="<?php echo htmlspecialchars($cancelUrl); ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</main>

<script src="../layout/front-header.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/face-api.js/dist/face-api.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const aboutMe = document.getElementById('aboutMe');
    const aboutCounter = document.getElementById('aboutCounter');
    const scanFaceBtn = document.getElementById('scanFaceBtn');
    const removeFaceBtn = document.getElementById('removeFaceBtn');
    const captureFaceBtn = document.getElementById('captureFaceBtn');
    const cancelFaceBtn = document.getElementById('cancelFaceBtn');
    const facePanel = document.getElementById('faceCameraPanel');
    const video = document.getElementById('faceVideo');
    const canvas = document.getElementById('faceCanvas');
    const faceBox = document.getElementById('faceBox');
    const message = document.getElementById('faceLiveMessage');
    const faceDescriptorInput = document.getElementById('faceDescriptor');
    const removeFaceInput = document.getElementById('removeFaceDescriptor');
    const faceSavedStatus = document.getElementById('faceSavedStatus');

    let modelsLoaded = false;
    let stream = null;
    let lastDescriptor = null;
    let detectLoop = null;
    let liveDetecting = false;

    function updateCounter() {
        if (aboutCounter && aboutMe) {
            aboutCounter.textContent = String(aboutMe.value.length);
        }
    }

    updateCounter();
    aboutMe?.addEventListener('input', updateCounter);

    function getModelBasePath() {
        const currentPath = window.location.pathname.replace(/\/[^\/]*$/, '');
        return window.location.origin + currentPath + '/../../../models';
    }

    async function loadModels() {
        if (modelsLoaded) return;

        if (!window.faceapi) {
            throw new Error('face-api.js is not loaded yet.');
        }

        const modelBase = getModelBasePath();
        await faceapi.nets.faceRecognitionNet.loadFromUri(modelBase);
        await faceapi.nets.faceLandmark68Net.loadFromUri(modelBase);
        await faceapi.nets.ssdMobilenetv1.loadFromUri(modelBase);
        modelsLoaded = true;
    }

    function setMessage(text, ok = false) {
        message.textContent = text;
        message.classList.toggle('ok', ok);
        message.classList.toggle('bad', !ok);
    }

    function updateStatus(text, ok = true) {
        faceSavedStatus.innerHTML = `<i class="bi ${ok ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill'}"></i> ${text}`;
        faceSavedStatus.classList.toggle('is-missing', !ok);
    }

    async function startCamera() {
        await loadModels();

        if (!stream) {
            stream = await navigator.mediaDevices.getUserMedia({ video: true });
            video.srcObject = stream;
            await video.play();
        }

        facePanel.classList.add('is-open');
        resizeCanvasToVideo();
        startDetectionLoop();
    }

    function stopCamera() {
        if (detectLoop) {
            cancelAnimationFrame(detectLoop);
            detectLoop = null;
        }
        liveDetecting = false;

        const ctx = canvas.getContext('2d');
        ctx?.clearRect(0, 0, canvas.width, canvas.height);
        hideFaceBox();

        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }

        video.srcObject = null;
        facePanel.classList.remove('is-open');
    }
    function resizeCanvasToVideo() {
        if (!canvas || !video) return false;

        const width = video.clientWidth || video.videoWidth || 0;
        const height = video.clientHeight || video.videoHeight || 0;

        if (!width || !height) return false;

        if (canvas.width !== width || canvas.height !== height) {
            canvas.width = width;
            canvas.height = height;
        }

        return true;
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

    function clearFaceCanvas(ctx) {
        if (ctx && canvas) {
            ctx.clearRect(0, 0, canvas.width || 0, canvas.height || 0);
        }
        hideFaceBox();
    }

    function drawFaceBox(ctx, detection) {
        if (!ctx || !detection || !resizeCanvasToVideo() || !video.videoWidth || !video.videoHeight) return;

        clearFaceCanvas(ctx);

        const box = detection.detection.box;
        const scaleX = canvas.width / video.videoWidth;
        const scaleY = canvas.height / video.videoHeight;
        const x = box.x * scaleX;
        const y = box.y * scaleY;
        const w = box.width * scaleX;
        const h = box.height * scaleY;

        showFaceBox(x, y, w, h);

        ctx.save();
        ctx.lineWidth = 3;
        ctx.strokeStyle = '#22c55e';
        ctx.shadowColor = 'rgba(34, 197, 94, 0.8)';
        ctx.shadowBlur = 8;
        ctx.strokeRect(x, y, w, h);
        ctx.shadowBlur = 0;
        ctx.fillStyle = 'rgba(34, 197, 94, 0.95)';
        ctx.font = '700 13px Arial, sans-serif';
        const label = 'Face detected';
        const labelWidth = ctx.measureText(label).width + 16;
        const labelY = y > 30 ? y - 28 : y + 8;
        ctx.fillRect(x, labelY, labelWidth, 22);
        ctx.fillStyle = '#ffffff';
        ctx.fillText(label, x + 8, labelY + 15);
        ctx.restore();
    }

    async function detectFace() {
        if (!stream || video.paused || video.ended) {
            const ctx = canvas?.getContext('2d');
            clearFaceCanvas(ctx);
            return;
        }

        if (!liveDetecting && video.readyState >= 2) {
            liveDetecting = true;
            const ctx = canvas.getContext('2d');
            resizeCanvasToVideo();

            try {
                const detection = await faceapi.detectSingleFace(video).withFaceLandmarks().withFaceDescriptor();

                if (detection) {
                    lastDescriptor = Array.from(detection.descriptor);
                    drawFaceBox(ctx, detection);
                    setMessage('Face detected ✅ You can capture it now.', true);
                } else {
                    lastDescriptor = null;
                    clearFaceCanvas(ctx);
                    setMessage('No face detected ❌ Move closer and face the camera.', false);
                }
            } catch (error) {
                console.error(error);
                lastDescriptor = null;
                clearFaceCanvas(ctx);
                setMessage('Face scan error. Please retry.', false);
            } finally {
                liveDetecting = false;
            }
        }

        detectLoop = requestAnimationFrame(detectFace);
    }

    function startDetectionLoop() {
        if (detectLoop) {
            cancelAnimationFrame(detectLoop);
            detectLoop = null;
        }
        liveDetecting = false;
        resizeCanvasToVideo();
        detectLoop = requestAnimationFrame(detectFace);
    }


    scanFaceBtn?.addEventListener('click', async () => {
        try {
            scanFaceBtn.disabled = true;
            setMessage('Loading face models...', true);
            await startCamera();
        } catch (error) {
            console.error(error);
            setMessage('Could not open camera or load face models. Check permissions/models.', false);
            facePanel.classList.add('is-open');
        } finally {
            scanFaceBtn.disabled = false;
        }
    });

    captureFaceBtn?.addEventListener('click', () => {
        if (!lastDescriptor) {
            setMessage('No face detected yet ❌ Please retry.', false);
            return;
        }

        faceDescriptorInput.value = JSON.stringify(lastDescriptor);
        removeFaceInput.value = '0';
        updateStatus('New Face ID captured — save changes to apply it', true);
        stopCamera();
    });

    cancelFaceBtn?.addEventListener('click', () => {
        stopCamera();
    });

    removeFaceBtn?.addEventListener('click', () => {
        faceDescriptorInput.value = '';
        removeFaceInput.value = '1';
        updateStatus('Face ID will be removed after saving', false);
        stopCamera();
    });

    window.addEventListener('beforeunload', stopCamera);
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
