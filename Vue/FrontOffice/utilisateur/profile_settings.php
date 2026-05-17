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
    function cre8_front_profile_sync_session(string $name, string $email, ?string $imageName): void
    {
        $_SESSION['nom'] = $name;
        $_SESSION['email'] = $email;
        if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
            $_SESSION['user']['nom'] = $name;
            $_SESSION['user']['email'] = $email;
        }
        if (isset($_SESSION['utilisateur']) && is_array($_SESSION['utilisateur'])) {
            $_SESSION['utilisateur']['nom'] = $name;
            $_SESSION['utilisateur']['email'] = $email;
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

if (!function_exists('cre8_front_profile_email_is_available')) {
    function cre8_front_profile_email_is_available(int $userId, string $email): bool
    {
        try {
            $db = config::getConnexion();
            $stmt = $db->prepare('SELECT id FROM utilisateur WHERE email = :email AND id <> :id LIMIT 1');
            $stmt->execute([
                'email' => $email,
                'id' => $userId,
            ]);
            return $stmt->fetch(PDO::FETCH_ASSOC) === false;
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('cre8_front_profile_update_email')) {
    function cre8_front_profile_update_email(int $userId, string $email): bool
    {
        try {
            $db = config::getConnexion();
            $stmt = $db->prepare('UPDATE utilisateur SET email = :email WHERE id = :id');
            return $stmt->execute([
                'email' => $email,
                'id' => $userId,
            ]);
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('cre8_front_profile_save')) {
    function cre8_front_profile_save(ProfileC $profileC, int $userId): array
    {
        $name = trim((string)($_POST['nom'] ?? ''));
        if ($name === '') {
            return ['type' => 'danger', 'message' => 'Name is required.'];
        }

        $email = trim((string)($_POST['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['type' => 'danger', 'message' => 'Please enter a valid email address.'];
        }
        $email = strtolower($email);
        if (!cre8_front_profile_email_is_available($userId, $email)) {
            return ['type' => 'danger', 'message' => 'This email address is already used by another account.'];
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

            if (!cre8_front_profile_update_email($userId, $email)) {
                if ($newImageName !== null) {
                    $profileC->deleteProfileImage($newImageName);
                }
                return ['type' => 'danger', 'message' => 'Could not update your email address.'];
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

        cre8_front_profile_sync_session($name, $email, $newImageName);

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
$userEmail = trim((string)($userRow['email'] ?? $currentFrontUser['email'] ?? ''));
$profileImageUrl = $profileC->getProfileImageUrl($userId, '../../public/uploads/profile');
$aboutMe = $profileC->getProfileAboutMe($userId);
if ($currentRole === 'marque') {
    $aboutPlaceholder = 'Tell creators about your brand, products, values, campaign style, and ideal collaborators.';
    $aboutPlaceholderKey = 'account.aboutBrandPlaceholder';
    $aboutHintText = 'For brands: this helps Cre8Connect present your brand, products, values, and campaign style so creators can choose collaborations that fit them best.';
    $aboutHintKey = 'account.aboutBrandHint';
} elseif ($currentRole === 'createur') {
    $aboutPlaceholder = 'Tell brands what you create, your niche, audience, style, or collaboration goals.';
    $aboutPlaceholderKey = 'account.aboutCreatorPlaceholder';
    $aboutHintText = 'For creators: this helps Cre8Connect understand your niche, audience, style, and goals so brands can discover you and collaboration opportunities can match you better.';
    $aboutHintKey = 'account.aboutCreatorHint';
} else {
    $aboutPlaceholder = 'Tell creators or brands who you are, what you do, and what kind of collaborations you like.';
    $aboutPlaceholderKey = 'account.aboutGenericPlaceholder';
    $aboutHintText = 'This helps Cre8Connect understand your profile and suggest better collaboration matches.';
    $aboutHintKey = 'account.aboutGenericHint';
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

        .profile-settings-card .form-label {
            color: var(--text, #0f0e1a);
            font-weight: 700;
        }
        .profile-settings-card .form-control,
        .profile-settings-card .form-select {
            background: var(--bg, #f6f6fc);
            color: var(--text, #0f0e1a);
            border-color: var(--border, #ebebf2);
            box-shadow: none;
        }
        .profile-settings-card .form-control:focus,
        .profile-settings-card .form-select:focus {
            background: var(--white, #ffffff);
            color: var(--text, #0f0e1a);
            border-color: var(--primary, #5b4fff);
            box-shadow: 0 0 0 0.2rem rgba(91, 79, 255, 0.14);
        }
        .profile-settings-card .form-control::placeholder {
            color: var(--text-sub, #6b6f80);
            opacity: 0.72;
        }
        .profile-settings-card .form-text,
        .profile-settings-card .text-muted {
            color: var(--text-sub, #6b6f80) !important;
        }
        .profile-settings-card input[type="file"].form-control {
            padding: 0.45rem;
        }
        .profile-settings-card input[type="file"].form-control::file-selector-button {
            margin: -0.45rem 0.75rem -0.45rem -0.45rem;
            padding: 0.55rem 0.8rem;
            border: 0;
            border-right: 1px solid var(--border, #ebebf2);
            background: var(--primary-light, #ece9ff);
            color: var(--primary, #5b4fff);
            font-weight: 800;
            cursor: pointer;
        }
        [data-theme="dark"] .profile-settings-card .form-control,
        [data-theme="dark"] .profile-settings-card .form-select {
            background: #17162a;
            color: var(--text, #e8e6f5);
            border-color: #34324d;
        }
        [data-theme="dark"] .profile-settings-card .form-control:focus,
        [data-theme="dark"] .profile-settings-card .form-select:focus {
            background: #1f1d34;
            color: var(--text, #e8e6f5);
            border-color: var(--primary, #7c6fff);
            box-shadow: 0 0 0 0.2rem rgba(124, 111, 255, 0.2);
        }
        [data-theme="dark"] .profile-settings-card input[type="file"].form-control::file-selector-button {
            background: rgba(124, 111, 255, 0.18);
            color: #d8d4ff;
            border-right-color: #34324d;
        }
        [data-theme="dark"] .profile-section {
            background: rgba(31, 29, 52, 0.82);
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
                <h1 class="h3 mb-1" data-i18n="account.profileSettings">Profile Settings</h1>
                <p class="text-muted mb-0" data-i18n="account.profileSubtitle">Update your name, profile photo, Face ID, and public bio.</p>
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
                    <span data-i18n="account.basicProfile">Basic profile</span>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="profileName" data-i18n="account.displayName">Display name</label>
                    <input type="text" class="form-control" id="profileName" name="nom" value="<?php echo htmlspecialchars($userName); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="profileEmail" data-i18n="account.emailAddress">Email address</label>
                    <input type="email" class="form-control" id="profileEmail" name="email" value="<?php echo htmlspecialchars($userEmail); ?>" required autocomplete="email" data-i18n-placeholder="account.emailPlaceholder" placeholder="name@example.com">
                    <div class="form-text" data-i18n="account.emailHelp">Use an active email address. You will use it to sign in.</div>
                </div>

                <div class="mb-0">
                    <label class="form-label" for="profileImage" data-i18n="account.profilePhoto">Profile photo</label>
                    <input type="file" class="form-control" id="profileImage" name="profile_image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                    <div class="form-text" data-i18n="account.photoHelp">JPG, PNG, or WEBP. Max 2MB.</div>
                </div>
            </div>

            <div class="profile-section">
                <div class="profile-section-title">
                    <i class="bi bi-camera"></i>
                    <span data-i18n="account.faceId">Face ID</span>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <span id="faceSavedStatus" class="face-status-pill <?php echo $hasFaceDescriptor ? '' : 'is-missing'; ?>">
                        <i class="bi <?php echo $hasFaceDescriptor ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill'; ?>"></i>
                        <span data-i18n="<?php echo $hasFaceDescriptor ? 'account.faceSaved' : 'account.faceMissing'; ?>"><?php echo $hasFaceDescriptor ? 'Face ID saved' : 'No Face ID saved'; ?></span>
                    </span>
                    <button type="button" id="scanFaceBtn" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-camera-video"></i>
                        <span data-i18n="<?php echo $hasFaceDescriptor ? 'account.rescanFace' : 'account.scanFace'; ?>"><?php echo $hasFaceDescriptor ? 'Rescan Face ID' : 'Scan Face ID'; ?></span>
                    </button>
                    <?php if ($hasFaceDescriptor): ?>
                        <button type="button" id="removeFaceBtn" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-trash3"></i>
                            <span data-i18n="account.removeFace">Remove Face ID</span>
                        </button>
                    <?php endif; ?>
                </div>
                <p class="text-muted small mb-0" data-i18n="account.faceHelp">Face ID is optional. Use it if you want to log in with your face later.</p>

                <input type="hidden" name="faceDescriptor" id="faceDescriptor">
                <input type="hidden" name="removeFaceDescriptor" id="removeFaceDescriptor" value="0">

                <div id="faceCameraPanel" class="face-camera-panel">
                    <div class="face-video-wrap">
                        <video id="faceVideo" autoplay muted playsinline></video>
                        <canvas id="faceCanvas"></canvas>
                          <div id="faceBox" class="face-detection-box" hidden><span data-i18n="account.faceDetected">Face detected</span></div>
                    </div>
                    <p id="faceLiveMessage" class="face-live-message bad" data-i18n="account.cameraReady">Camera ready. Put your face inside the frame.</p>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <button type="button" id="captureFaceBtn" class="btn btn-primary btn-sm">
                            <i class="bi bi-bounding-box-circles"></i>
                            <span data-i18n="account.captureRetry">Capture / Retry</span>
                        </button>
                        <button type="button" id="cancelFaceBtn" class="btn btn-outline-secondary btn-sm">
                            <span data-i18n="account.cancel">Cancel</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="profile-section">
                <div class="profile-section-title">
                    <i class="bi bi-chat-square-heart"></i>
                    <span data-i18n="account.aboutMe">About me</span>
                </div>
                <label class="form-label" for="aboutMe" data-i18n="account.aboutLabel">Add something about you</label>
                <textarea class="form-control" id="aboutMe" name="aboutMe" maxlength="800" placeholder="<?php echo htmlspecialchars($aboutPlaceholder, ENT_QUOTES, 'UTF-8'); ?>" data-i18n-placeholder="<?php echo htmlspecialchars($aboutPlaceholderKey); ?>"><?php echo htmlspecialchars($aboutMe, ENT_QUOTES, 'UTF-8'); ?></textarea>
                <div class="profile-match-hint">
                    <i class="bi bi-stars"></i>
                    <span data-i18n="<?php echo htmlspecialchars($aboutHintKey); ?>"><?php echo htmlspecialchars($aboutHintText, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <div class="form-text d-flex justify-content-between">
                    <span data-i18n="account.aboutOptional">Optional, but useful for better matching.</span>
                    <span><span id="aboutCounter">0</span>/800 <span data-i18n="account.characters">characters</span></span>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check2-circle"></i> <span data-i18n="account.saveChanges">Save changes</span>
                </button>
                <a href="<?php echo htmlspecialchars($cancelUrl); ?>" class="btn btn-outline-secondary" data-i18n="account.cancel">Cancel</a>
            </div>
        </form>
    </div>
</main>

<script>
var cre8AccountTranslations = {
    en: {
        'account.profileSettings': 'Profile Settings',
        'account.profileSubtitle': 'Update your name, profile photo, Face ID, and public bio.',
        'account.basicProfile': 'Basic profile',
        'account.displayName': 'Display name',
        'account.profilePhoto': 'Profile photo',
        'account.emailAddress': 'Email address',
        'account.emailPlaceholder': 'name@example.com',
        'account.emailHelp': 'Use an active email address. You will use it to sign in.',
        'account.photoHelp': 'JPG, PNG, or WEBP. Max 2MB.',
        'account.faceId': 'Face ID',
        'account.faceSaved': 'Face ID saved',
        'account.faceMissing': 'No Face ID saved',
        'account.rescanFace': 'Rescan Face ID',
        'account.scanFace': 'Scan Face ID',
        'account.removeFace': 'Remove Face ID',
        'account.faceHelp': 'Face ID is optional. Use it if you want to log in with your face later.',
        'account.faceDetected': 'Face detected',
        'account.cameraReady': 'Camera ready. Put your face inside the frame.',
        'account.captureRetry': 'Capture / Retry',
        'account.aboutMe': 'About me',
        'account.aboutLabel': 'Add something about you',
        'account.aboutBrandPlaceholder': 'Tell creators about your brand, products, values, campaign style, and ideal collaborators.',
        'account.aboutCreatorPlaceholder': 'Tell brands what you create, your niche, audience, style, or collaboration goals.',
        'account.aboutGenericPlaceholder': 'Tell creators or brands who you are, what you do, and what kind of collaborations you like.',
        'account.aboutBrandHint': 'For brands: this helps Cre8Connect present your brand, products, values, and campaign style so creators can choose collaborations that fit them best.',
        'account.aboutCreatorHint': 'For creators: this helps Cre8Connect understand your niche, audience, style, and goals so brands can discover you and collaboration opportunities can match you better.',
        'account.aboutGenericHint': 'This helps Cre8Connect understand your profile and suggest better collaboration matches.',
        'account.aboutOptional': 'Optional, but useful for better matching.',
        'account.characters': 'characters',
        'account.saveChanges': 'Save changes',
        'account.cancel': 'Cancel',
        'account.loadingModels': 'Loading face models...',
        'account.faceDetectedCapture': 'Face detected. You can capture it now.',
        'account.noFaceDetected': 'No face detected. Move closer and face the camera.',
        'account.faceScanError': 'Face scan error. Please retry.',
        'account.cameraError': 'Could not open camera or load face models. Check permissions/models.',
        'account.noFaceYet': 'No face detected yet. Please retry.',
        'account.newFaceCaptured': 'New Face ID captured - save changes to apply it',
        'account.faceWillBeRemoved': 'Face ID will be removed after saving'
    },
    fr: {
        'account.profileSettings': 'Parametres du profil',
        'account.profileSubtitle': 'Mettez a jour votre nom, photo de profil, Face ID et bio publique.',
        'account.basicProfile': 'Profil de base',
        'account.displayName': 'Nom affiche',
        'account.profilePhoto': 'Photo de profil',
        'account.emailAddress': 'Adresse email',
        'account.emailPlaceholder': 'nom@exemple.com',
        'account.emailHelp': 'Utilisez une adresse email active. Elle servira a vous connecter.',
        'account.photoHelp': 'JPG, PNG ou WEBP. Max 2 Mo.',
        'account.faceId': 'Face ID',
        'account.faceSaved': 'Face ID enregistre',
        'account.faceMissing': 'Aucun Face ID enregistre',
        'account.rescanFace': 'Scanner Face ID a nouveau',
        'account.scanFace': 'Scanner Face ID',
        'account.removeFace': 'Supprimer Face ID',
        'account.faceHelp': 'Face ID est optionnel. Utilisez-le si vous voulez vous connecter avec votre visage plus tard.',
        'account.faceDetected': 'Visage detecte',
        'account.cameraReady': 'Camera prete. Placez votre visage dans le cadre.',
        'account.captureRetry': 'Capturer / Reessayer',
        'account.aboutMe': 'A propos de moi',
        'account.aboutLabel': 'Ajoutez quelque chose sur vous',
        'account.aboutBrandPlaceholder': 'Parlez aux createurs de votre marque, vos produits, vos valeurs, votre style de campagne et vos collaborateurs ideaux.',
        'account.aboutCreatorPlaceholder': 'Dites aux marques ce que vous creez, votre niche, votre audience, votre style ou vos objectifs de collaboration.',
        'account.aboutGenericPlaceholder': 'Dites aux createurs ou marques qui vous etes, ce que vous faites et les collaborations que vous aimez.',
        'account.aboutBrandHint': 'Pour les marques : cela aide Cre8Connect a presenter votre marque, vos produits, vos valeurs et votre style de campagne afin que les createurs choisissent les collaborations adaptees.',
        'account.aboutCreatorHint': 'Pour les createurs : cela aide Cre8Connect a comprendre votre niche, audience, style et objectifs afin que les marques puissent vous decouvrir et mieux vous associer aux opportunites.',
        'account.aboutGenericHint': 'Cela aide Cre8Connect a comprendre votre profil et a suggerer de meilleures correspondances de collaboration.',
        'account.aboutOptional': 'Optionnel, mais utile pour un meilleur matching.',
        'account.characters': 'caracteres',
        'account.saveChanges': 'Enregistrer les modifications',
        'account.cancel': 'Annuler',
        'account.loadingModels': 'Chargement des modeles faciaux...',
        'account.faceDetectedCapture': 'Visage detecte. Vous pouvez le capturer maintenant.',
        'account.noFaceDetected': 'Aucun visage detecte. Approchez-vous et regardez la camera.',
        'account.faceScanError': 'Erreur de scan du visage. Veuillez reessayer.',
        'account.cameraError': 'Impossible d ouvrir la camera ou de charger les modeles. Verifiez les permissions/modeles.',
        'account.noFaceYet': 'Aucun visage detecte pour le moment. Veuillez reessayer.',
        'account.newFaceCaptured': 'Nouveau Face ID capture - enregistrez pour l appliquer',
        'account.faceWillBeRemoved': 'Face ID sera supprime apres enregistrement'
    }
};
function cre8AccountLang() {
    if (typeof window.cre8FrontReadLang === 'function') {
        return window.cre8FrontReadLang();
    }
    try {
        return (localStorage.getItem('cre8_front_lang') || localStorage.getItem('cre8_lang')) === 'fr' ? 'fr' : 'en';
    } catch (e) {
        return 'en';
    }
}
function cre8AccountText(key) {
    var lang = cre8AccountLang();
    return (cre8AccountTranslations[lang] && cre8AccountTranslations[lang][key]) || cre8AccountTranslations.en[key] || key;
}
function cre8RegisterAccountTranslations() {
    if (typeof window.cre8RegisterTranslations === 'function') {
        window.cre8RegisterTranslations(cre8AccountTranslations);
    }
}
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', cre8RegisterAccountTranslations);
} else {
    cre8RegisterAccountTranslations();
}
</script>
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
        message.textContent = cre8AccountText(text);
        message.classList.toggle('ok', ok);
        message.classList.toggle('bad', !ok);
    }

    function updateStatus(text, ok = true) {
        faceSavedStatus.innerHTML = `<i class="bi ${ok ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill'}"></i> ${cre8AccountText(text)}`;
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
        const label = cre8AccountText('account.faceDetected');
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
                    setMessage('account.faceDetectedCapture', true);
                } else {
                    lastDescriptor = null;
                    clearFaceCanvas(ctx);
                    setMessage('account.noFaceDetected', false);
                }
            } catch (error) {
                console.error(error);
                lastDescriptor = null;
                clearFaceCanvas(ctx);
                setMessage('account.faceScanError', false);
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
            setMessage('account.loadingModels', true);
            await startCamera();
        } catch (error) {
            console.error(error);
            setMessage('account.cameraError', false);
            facePanel.classList.add('is-open');
        } finally {
            scanFaceBtn.disabled = false;
        }
    });

    captureFaceBtn?.addEventListener('click', () => {
        if (!lastDescriptor) {
            setMessage('account.noFaceYet', false);
            return;
        }

        faceDescriptorInput.value = JSON.stringify(lastDescriptor);
        removeFaceInput.value = '0';
        updateStatus('account.newFaceCaptured', true);
        stopCamera();
    });

    cancelFaceBtn?.addEventListener('click', () => {
        stopCamera();
    });

    removeFaceBtn?.addEventListener('click', () => {
        faceDescriptorInput.value = '';
        removeFaceInput.value = '1';
        updateStatus('account.faceWillBeRemoved', false);
        stopCamera();
    });

    window.addEventListener('beforeunload', stopCamera);
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
