<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../layout/early-theme.php';
require_once __DIR__ . '/../../../Controleur/session_helper.php';
require_once __DIR__ . '/../../../Controleur/profileC.php';

$currentUserId = cc_require_admin('../../FrontOffice/utilisateur/login.php');
$profileC = new ProfileC();
$backActive = '';
$flash = null;

if (!function_exists('cre8_back_profile_asset_version')) {
    function cre8_back_profile_asset_version(string $absolutePath): string
    {
        return file_exists($absolutePath) ? '?v=' . urlencode((string) filemtime($absolutePath)) : '';
    }
}

if (!function_exists('cre8_back_profile_sync_session')) {
    function cre8_back_profile_sync_session(string $name, ?string $imageName): void
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

if (!function_exists('cre8_back_validate_face_descriptor_json')) {
    function cre8_back_validate_face_descriptor_json(string $raw): array
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

if (!function_exists('cre8_back_profile_save')) {
    function cre8_back_profile_save(ProfileC $profileC, int $userId): array
    {
        $name = trim((string)($_POST['nom'] ?? ''));
        if ($name === '') {
            return ['type' => 'danger', 'message' => 'Name is required.'];
        }

        $removeFace = (string)($_POST['removeFaceDescriptor'] ?? '') === '1';
        $postedFaceDescriptor = trim((string)($_POST['faceDescriptor'] ?? ''));
        $faceDescriptorToSave = null;
        $shouldUpdateFace = false;

        if ($removeFace) {
            $shouldUpdateFace = true;
            $faceDescriptorToSave = null;
        } elseif ($postedFaceDescriptor !== '') {
            $validation = cre8_back_validate_face_descriptor_json($postedFaceDescriptor);
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

            if (!$profileC->upsertProfileDetails($userId, $newImageName, null, $newImageName !== null, false)) {
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

        cre8_back_profile_sync_session($name, $newImageName);

        return ['type' => 'success', 'message' => 'Profile updated successfully.'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['back_profile_flash'] = cre8_back_profile_save($profileC, $currentUserId);
    header('Location: profile_settings.php');
    exit;
}

if (isset($_SESSION['back_profile_flash']) && is_array($_SESSION['back_profile_flash'])) {
    $flash = $_SESSION['back_profile_flash'];
    unset($_SESSION['back_profile_flash']);
}

$userRow = $profileC->getUserById($currentUserId) ?: [];
$userName = trim((string)($userRow['nom'] ?? $_SESSION['nom'] ?? 'Admin'));
$userName = $userName !== '' ? $userName : 'Admin';
$userRole = trim((string)($userRow['role'] ?? $_SESSION['role'] ?? 'admin'));
$userEmail = trim((string)($userRow['email'] ?? $_SESSION['email'] ?? ''));
$userInitial = function_exists('mb_substr') ? mb_substr($userName, 0, 1, 'UTF-8') : substr($userName, 0, 1);
$userInitial = strtoupper((string)$userInitial) ?: 'A';
$hasFaceDescriptor = trim((string)($userRow['face_descriptor'] ?? '')) !== '';
$profileImageUrl = $profileC->getProfileImageUrl($currentUserId, '../../public/uploads/profile');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php cre8_bo_early_theme_print_head_script(); ?>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Profile Settings - cre8connect</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="assets/vendors/mdi/css/materialdesignicons.min.css<?php echo cre8_back_profile_asset_version(__DIR__ . '/assets/vendors/mdi/css/materialdesignicons.min.css'); ?>">
  <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css<?php echo cre8_back_profile_asset_version(__DIR__ . '/assets/vendors/css/vendor.bundle.base.css'); ?>">
  <link rel="stylesheet" href="assets/css/style.css<?php echo cre8_back_profile_asset_version(__DIR__ . '/assets/css/style.css'); ?>">
  <link rel="stylesheet" href="../layout/back-layout.css<?php echo cre8_back_profile_asset_version(__DIR__ . '/../layout/back-layout.css'); ?>">
  <link rel="icon" type="image/png" sizes="16x16" href="../../public/images/favicon-16.png">
  <link rel="icon" type="image/png" sizes="32x32" href="../../public/images/favicon-32.png">
  <link rel="shortcut icon" type="image/png" href="../../public/images/favicon-32.png">
  <link rel="apple-touch-icon" sizes="180x180" href="../../public/images/apple-touch-icon.png">
  <style>
    .admin-profile-page {
      max-width: 1180px;
      margin: 0 auto;
    }

    .admin-profile-card {
      border: 1px solid rgba(148, 163, 184, 0.18);
      border-radius: 24px;
      background: linear-gradient(180deg, rgba(25, 28, 36, 0.98), rgba(17, 24, 39, 0.98));
      box-shadow: 0 22px 55px rgba(0, 0, 0, 0.28);
      overflow: hidden;
      color: #f8fafc;
    }

    .admin-profile-hero {
      display: grid;
      grid-template-columns: auto minmax(0, 1fr);
      gap: 1.15rem;
      align-items: center;
      padding: 1.35rem 1.45rem;
      border-bottom: 1px solid rgba(148, 163, 184, 0.16);
      background:
        radial-gradient(circle at 12% 10%, rgba(124, 92, 255, 0.20), transparent 30%),
        radial-gradient(circle at 90% 10%, rgba(14, 165, 233, 0.14), transparent 28%);
    }

    .admin-profile-avatar {
      width: 86px;
      height: 86px;
      border-radius: 24px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, #5b4fff, #ec4899);
      color: #fff;
      font-size: 32px;
      font-weight: 900;
      object-fit: cover;
      border: 2px solid rgba(255, 255, 255, 0.72);
      box-shadow: 0 16px 32px rgba(124, 92, 255, 0.22);
    }

    .admin-profile-eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      padding: 0.22rem 0.65rem;
      border-radius: 999px;
      background: rgba(124, 92, 255, 0.18);
      border: 1px solid rgba(167, 139, 250, 0.35);
      color: #ddd6fe;
      font-size: 0.72rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      margin-bottom: 0.45rem;
    }

    .admin-profile-title {
      margin: 0;
      color: #ffffff;
      font-size: clamp(1.55rem, 2.2vw, 2.15rem);
      font-weight: 900;
      letter-spacing: -0.03em;
    }

    .admin-profile-subtitle {
      margin: 0.35rem 0 0;
      color: #aab4c6;
      font-size: 0.95rem;
    }

    .admin-profile-meta {
      display: flex;
      flex-direction: row;
      gap: 0.5rem;
      align-items: center;
      flex-wrap: wrap;
      margin-top: 0.75rem;
    }

    .admin-profile-pill {
      display: inline-flex;
      align-items: center;
      gap: 0.45rem;
      border-radius: 999px;
      border: 1px solid rgba(148, 163, 184, 0.18);
      background: rgba(15, 23, 42, 0.55);
      padding: 0.38rem 0.75rem;
      color: #dbe4f4;
      font-size: 0.82rem;
      font-weight: 800;
      white-space: nowrap;
    }

    .admin-profile-body {
      padding: 1.35rem 1.45rem 1.5rem;
    }

    .admin-profile-grid {
      display: grid;
      grid-template-columns: 1fr;
      gap: 1rem;
      align-items: start;
    }

    .admin-profile-section {
      border: 1px solid rgba(148, 163, 184, 0.18);
      border-radius: 18px;
      background: rgba(15, 23, 42, 0.46);
      padding: 1rem;
    }

    .admin-profile-section.is-face-section {
      max-width: none;
    }

    .admin-profile-section + .admin-profile-section {
      margin-top: 0;
    }

    .admin-section-title {
      display: flex;
      align-items: center;
      gap: 0.55rem;
      color: #f8fafc;
      font-weight: 900;
      font-size: 0.95rem;
      margin-bottom: 0.85rem;
    }

    .admin-section-title i {
      width: 28px;
      height: 28px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 10px;
      background: rgba(124, 92, 255, 0.18);
      color: #c4b5fd;
    }


    .admin-profile-section .mb-3:last-child {
      margin-bottom: 0 !important;
    }

    .admin-profile-photo-row {
      display: grid;
      grid-template-columns: 64px minmax(0, 1fr);
      gap: 0.85rem;
      align-items: center;
    }

    .admin-profile-photo-preview {
      width: 58px;
      height: 58px;
      border-radius: 18px;
      object-fit: cover;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, #5b4fff, #ec4899);
      color: #fff;
      font-weight: 900;
      font-size: 1.25rem;
      border: 1px solid rgba(255, 255, 255, 0.55);
      box-shadow: 0 12px 28px rgba(124, 92, 255, 0.18);
    }

    .admin-profile-card .form-label {
      color: #eef2ff;
      font-weight: 800;
      font-size: 0.88rem;
    }

    .admin-profile-card .form-text,
    .admin-profile-card .text-muted {
      color: #94a3b8 !important;
    }

    .admin-profile-card .form-control {
      border: 1px solid rgba(148, 163, 184, 0.22);
      background: rgba(31, 41, 55, 0.74);
      color: #f8fafc;
      border-radius: 12px;
      min-height: 46px;
    }

    .admin-profile-card textarea.form-control {
      min-height: 140px;
      resize: vertical;
    }

    .admin-profile-card .form-control:focus {
      background: rgba(31, 41, 55, 0.88);
      color: #fff;
      border-color: #8b5cf6;
      box-shadow: 0 0 0 0.2rem rgba(139, 92, 246, 0.18);
    }

    .face-status-pill {
      display: inline-flex;
      align-items: center;
      gap: 0.42rem;
      padding: 0.36rem 0.72rem;
      border-radius: 999px;
      background: rgba(16, 185, 129, 0.15);
      color: #bbf7d0;
      border: 1px solid rgba(52, 211, 153, 0.34);
      font-size: 0.82rem;
      font-weight: 800;
    }

    .face-status-pill.is-missing {
      background: rgba(245, 158, 11, 0.14);
      color: #fde68a;
      border-color: rgba(245, 158, 11, 0.34);
    }

    .face-camera-panel {
      display: none;
      margin-top: 0.9rem;
      padding: 0.8rem;
      border: 1px solid rgba(148, 163, 184, 0.2);
      border-radius: 16px;
      background: rgba(2, 6, 23, 0.38);
      width: min(100%, 460px);
      max-width: 460px;
    }

    .face-camera-panel.is-open {
      display: block;
    }

    .face-video-wrap {
      position: relative;
      overflow: hidden;
      border-radius: 14px;
      background: #020617;
      aspect-ratio: 4 / 3;
      width: 100%;
      max-width: 420px;
      box-shadow: 0 14px 34px rgba(0, 0, 0, 0.26);
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
      margin: 0.75rem 0 0;
      font-size: 0.86rem;
      font-weight: 800;
      color: #fecaca;
    }

    .face-live-message.ok {
      color: #bbf7d0;
    }

    .profile-save-bar {
      margin-top: 1rem;
      padding-top: 1rem;
      border-top: 1px solid rgba(148, 163, 184, 0.16);
      display: flex;
      flex-wrap: wrap;
      gap: 0.65rem;
      justify-content: flex-end;
    }

    html[data-theme="light"] body.cre8-admin-layout .admin-profile-card,
    body.light-mode .admin-profile-card {
      background: #ffffff;
      color: #111827;
      border-color: #e5e7eb;
      box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
    }

    html[data-theme="light"] body.cre8-admin-layout .admin-profile-hero,
    body.light-mode .admin-profile-hero {
      background: linear-gradient(135deg, #f7f5ff, #ffffff);
      border-bottom-color: #e5e7eb;
    }

    html[data-theme="light"] body.cre8-admin-layout .admin-profile-title,
    body.light-mode .admin-profile-title,
    html[data-theme="light"] body.cre8-admin-layout .admin-section-title,
    body.light-mode .admin-section-title,
    html[data-theme="light"] body.cre8-admin-layout .admin-profile-card .form-label,
    body.light-mode .admin-profile-card .form-label {
      color: #111827;
    }

    html[data-theme="light"] body.cre8-admin-layout .admin-profile-subtitle,
    body.light-mode .admin-profile-subtitle {
      color: #64748b;
    }

    html[data-theme="light"] body.cre8-admin-layout .admin-profile-section,
    body.light-mode .admin-profile-section {
      background: #f8fafc;
      border-color: #e5e7eb;
    }

    html[data-theme="light"] body.cre8-admin-layout .admin-profile-card .form-control,
    body.light-mode .admin-profile-card .form-control {
      background: #ffffff;
      color: #111827;
      border-color: #d1d5db;
    }

    @media (max-width: 992px) {
      .admin-profile-grid {
        grid-template-columns: 1fr;
      }

      .admin-profile-hero {
        grid-template-columns: auto minmax(0, 1fr);
      }
    }

    @media (max-width: 576px) {
      .admin-profile-body,
      .admin-profile-hero {
        padding: 1rem;
      }

      .admin-profile-avatar {
        width: 68px;
        height: 68px;
        border-radius: 20px;
      }
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
<body class="cre8-admin-layout"><?php cre8_bo_early_theme_print_body_script(); ?>
  <div class="container-scroller cre8-admin-page">
    <?php require_once __DIR__ . '/../layout/sidebar.php'; ?>

    <div class="container-fluid page-body-wrapper cre8-admin-main">
      <?php require_once __DIR__ . '/../layout/header.php'; ?>

      <div class="main-panel">
        <div class="content-wrapper">
          <div class="admin-profile-page">
            <div class="admin-profile-card">
              <div class="admin-profile-hero">
                <?php if ($profileImageUrl): ?>
                  <img class="admin-profile-avatar" src="<?php echo htmlspecialchars($profileImageUrl); ?>" alt="Profile photo">
                <?php else: ?>
                  <div class="admin-profile-avatar"><?php echo htmlspecialchars($userInitial); ?></div>
                <?php endif; ?>

                <div>
                  <span class="admin-profile-eyebrow"><i class="mdi mdi-shield-account"></i> BackOffice profile</span>
                  <h2 class="admin-profile-title">Profile Settings</h2>
                  <p class="admin-profile-subtitle">Update your name, profile photo, and Face ID.</p>
                  <div class="admin-profile-meta" aria-label="Account metadata">
                    <span class="admin-profile-pill"><i class="mdi mdi-account-key"></i> <?php echo htmlspecialchars($userRole ?: 'admin'); ?></span>
                    <?php if ($userEmail !== ''): ?>
                      <span class="admin-profile-pill"><i class="mdi mdi-email-outline"></i> <?php echo htmlspecialchars($userEmail); ?></span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <div class="admin-profile-body">

                <?php if ($flash): ?>
                  <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?>" role="alert">
                    <?php echo htmlspecialchars($flash['message']); ?>
                  </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" autocomplete="off">
                  <div class="admin-profile-grid">
                    <section class="admin-profile-section">
                      <div class="admin-section-title">
                        <i class="bi bi-person-gear"></i>
                        Basic profile
                      </div>

                      <div class="mb-3">
                        <label class="form-label" for="profileName">Display name</label>
                        <input type="text" class="form-control" id="profileName" name="nom" value="<?php echo htmlspecialchars($userName); ?>" required>
                      </div>
                    </section>

                    <section class="admin-profile-section">
                      <div class="admin-section-title">
                        <i class="bi bi-image"></i>
                        Profile photo
                      </div>

                      <div class="admin-profile-photo-row">
                        <?php if ($profileImageUrl): ?>
                          <img class="admin-profile-photo-preview" src="<?php echo htmlspecialchars($profileImageUrl); ?>" alt="Current profile photo">
                        <?php else: ?>
                          <div class="admin-profile-photo-preview"><?php echo htmlspecialchars($userInitial); ?></div>
                        <?php endif; ?>
                        <div>
                          <label class="form-label" for="profileImage">Upload a new photo</label>
                          <input type="file" class="form-control" id="profileImage" name="profile_image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                          <div class="form-text">JPG, PNG, or WEBP. Max 2MB.</div>
                        </div>
                      </div>
                    </section>

                    <section class="admin-profile-section is-face-section">
                      <div class="admin-section-title">
                        <i class="bi bi-person-bounding-box"></i>
                        Face ID
                      </div>

                      <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <span id="faceSavedStatus" class="face-status-pill <?php echo $hasFaceDescriptor ? '' : 'is-missing'; ?>">
                          <i class="mdi <?php echo $hasFaceDescriptor ? 'mdi-check-circle' : 'mdi-alert-circle-outline'; ?>"></i>
                          <?php echo $hasFaceDescriptor ? 'Face ID saved' : 'No Face ID saved'; ?>
                        </span>
                        <button type="button" id="scanFaceBtn" class="btn btn-outline-primary btn-sm">
                          <i class="mdi mdi-video-account"></i>
                          <?php echo $hasFaceDescriptor ? 'Rescan Face ID' : 'Scan Face ID'; ?>
                        </button>
                        <?php if ($hasFaceDescriptor): ?>
                          <button type="button" id="removeFaceBtn" class="btn btn-outline-danger btn-sm">
                            <i class="mdi mdi-delete-outline"></i>
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
                        <p id="faceLiveMessage" class="face-live-message">Camera ready. Put your face inside the frame.</p>
                        <div class="d-flex flex-wrap gap-2 mt-3">
                          <button type="button" id="captureFaceBtn" class="btn btn-primary btn-sm">
                            <i class="mdi mdi-crosshairs-gps"></i>
                            Capture / Retry
                          </button>
                          <button type="button" id="cancelFaceBtn" class="btn btn-outline-secondary btn-sm">
                            Cancel
                          </button>
                        </div>
                      </div>
                    </section>
                  </div>

                  <div class="profile-save-bar">
                    <a href="../dashboard/index.php" class="btn btn-outline-secondary">Back to dashboard</a>
                    <button type="submit" class="btn btn-primary">
                      <i class="mdi mdi-content-save"></i> Save changes
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="assets/vendors/js/vendor.bundle.base.js<?php echo cre8_back_profile_asset_version(__DIR__ . '/assets/vendors/js/vendor.bundle.base.js'); ?>"></script>
  <script src="../layout/back-layout.js<?php echo cre8_back_profile_asset_version(__DIR__ . '/../layout/back-layout.js'); ?>"></script>
  <script src="assets/js/off-canvas.js<?php echo cre8_back_profile_asset_version(__DIR__ . '/assets/js/off-canvas.js'); ?>"></script>
  <script src="assets/js/hoverable-collapse.js<?php echo cre8_back_profile_asset_version(__DIR__ . '/assets/js/hoverable-collapse.js'); ?>"></script>
  <script src="assets/js/misc.js<?php echo cre8_back_profile_asset_version(__DIR__ . '/assets/js/misc.js'); ?>"></script>
  <script src="assets/js/settings.js<?php echo cre8_back_profile_asset_version(__DIR__ . '/assets/js/settings.js'); ?>"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/face-api.js/dist/face-api.min.js"></script>
  <script>
  document.addEventListener('DOMContentLoaded', () => {
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
          if (!message) return;
          message.textContent = text;
          message.classList.toggle('ok', ok);
      }

      function updateStatus(text, ok = true) {
          if (!faceSavedStatus) return;
          faceSavedStatus.innerHTML = `<i class="mdi ${ok ? 'mdi-check-circle' : 'mdi-alert-circle-outline'}"></i> ${text}`;
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

          const ctx = canvas?.getContext('2d');
          ctx?.clearRect(0, 0, canvas.width, canvas.height);
          hideFaceBox();

          if (stream) {
              stream.getTracks().forEach(track => track.stop());
              stream = null;
          }

          if (video) {
              video.srcObject = null;
          }
          facePanel?.classList.remove('is-open');
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
              facePanel?.classList.add('is-open');
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
</body>
</html>
