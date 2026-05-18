<?php
require_once __DIR__ . '/../../../Controleur/session_helper.php';
require_once __DIR__ . '/../../../Controleur/profileC.php';

cc_start_session();

$cre8SelfPath = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');
$cre8Marker = '/Vue/BackOffice/';
$cre8Pos = strpos($cre8SelfPath, $cre8Marker);

if ($cre8Pos !== false) {
    $backProjectBase = substr($cre8SelfPath, 0, $cre8Pos);
    $backBoRootWeb = $backProjectBase . '/Vue/BackOffice';
} else {
    $_ctrlPos = strpos($cre8SelfPath, '/Controleur/');
    $backProjectBase = $_ctrlPos !== false ? substr($cre8SelfPath, 0, $_ctrlPos) : '';
    $backBoRootWeb = $backProjectBase . '/Vue/BackOffice';
}
$backBoUtilisateurWeb = $backBoRootWeb . '/utilisateur';
$backProfileSettingsUrl = $backBoUtilisateurWeb . '/profile_settings.php';
$backProfileUploadWeb = $backProjectBase . '/Vue/public/uploads/profile';

$adminName = $_SESSION['user']['nom'] ?? ($_SESSION['utilisateur']['nom'] ?? ($_SESSION['nom'] ?? 'Utilisateur')); 
$adminName = trim((string) $adminName) ?: 'Utilisateur';
$adminInitial = function_exists('mb_substr') ? mb_substr($adminName, 0, 1, 'UTF-8') : substr($adminName, 0, 1);
$adminInitial = strtoupper((string) $adminInitial) ?: 'U';
$adminAvatarUrl = null;
$adminId = cc_current_user_id();

if ($adminId !== null) {
    try {
        $profileC = new ProfileC();
        $adminAvatarUrl = $profileC->getProfileImageUrl($adminId, $backProfileUploadWeb);
    } catch (Throwable $e) {
        $adminAvatarUrl = null;
    }
}
?>
      <!-- partial:partials/_navbar.html -->
      <nav class="navbar p-0 fixed-top d-flex flex-row">
        <div class="navbar-brand-wrapper d-flex d-lg-none align-items-center justify-content-center">
          <a class="navbar-brand brand-logo-mini" href="<?php echo htmlspecialchars($backBoRootWeb . '/dashboard/index.php'); ?>">
            <img src="<?php echo htmlspecialchars($backBoUtilisateurWeb . '/assets/images/logo-mini.svg'); ?>" alt="logo">
          </a>
        </div>
        <div class="navbar-menu-wrapper flex-grow d-flex align-items-stretch">
          <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-toggle="minimize">
            <span class="mdi mdi-menu"></span>
          </button>
          <ul class="navbar-nav w-100">
            <li class="nav-item w-100">
              <form class="nav-link mt-2 mt-md-0 d-none d-lg-flex search" action="#" method="get">
                <input type="text" class="form-control" placeholder="Search admin workspace" data-i18n-placeholder="header.search">
              </form>
            </li>
          </ul>
          <ul class="navbar-nav navbar-nav-right">
            <li class="nav-item dropdown d-none d-lg-block">
              <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list" aria-labelledby="createbuttonDropdown">
                <h6 class="p-3 mb-0">Projects</h6>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item preview-item" href="<?php echo htmlspecialchars($backBoRootWeb . '/campagne/index.php'); ?>">
                  <div class="preview-thumbnail"><div class="preview-icon bg-dark rounded-circle"><i class="mdi mdi-bullhorn text-primary"></i></div></div>
                  <div class="preview-item-content"><p class="preview-subject ellipsis mb-1">Campaigns</p></div>
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item preview-item" href="<?php echo htmlspecialchars($backBoRootWeb . '/produit/index.php'); ?>">
                  <div class="preview-thumbnail"><div class="preview-icon bg-dark rounded-circle"><i class="mdi mdi-package-variant-closed text-info"></i></div></div>
                  <div class="preview-item-content"><p class="preview-subject ellipsis mb-1">Products</p></div>
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item preview-item" href="<?php echo htmlspecialchars($backBoRootWeb . '/offre/index.php'); ?>">
                  <div class="preview-thumbnail"><div class="preview-icon bg-dark rounded-circle"><i class="mdi mdi-briefcase-check text-danger"></i></div></div>
                  <div class="preview-item-content"><p class="preview-subject ellipsis mb-1">Collaborations</p></div>
                </a>
                <div class="dropdown-divider"></div>
                <p class="p-3 mb-0 text-center">Admin shortcuts</p>
              </div>
            </li>
            <li class="nav-item nav-settings d-none d-lg-block">
              <a class="nav-link" href="<?php echo htmlspecialchars($backBoRootWeb . '/dashboard/index.php'); ?>" title="Dashboard" data-i18n-title="nav.dashboard" data-i18n-aria-label="nav.dashboard">
                <i class="mdi mdi-view-grid"></i>
              </a>
            </li>
            <li class="nav-item border-left">
              <a class="nav-link count-indicator back-theme-toggle" href="#" onclick="toggleDarkMode(); return false;" title="Mode jour / nuit" aria-label="Toggle light or dark mode">
                <i id="themeIcon" class="mdi mdi-weather-night"></i>
              </a>
            </li>
            <li class="nav-item border-left d-flex align-items-center px-2" title="Language" data-i18n-title="common.language">
              <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 mr-1" data-cre8-back-lang="en" data-i18n-title="common.english">EN</button>
              <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" data-cre8-back-lang="fr" data-i18n-title="common.french">FR</button>
            </li>
            <li class="nav-item dropdown border-left">
              <a class="nav-link count-indicator dropdown-toggle" id="messageDropdown" href="#" data-toggle="dropdown" aria-expanded="false" title="Messages" data-i18n-title="header.messages" data-i18n-aria-label="header.messages">
                <i class="mdi mdi-email"></i>
                <span class="count bg-success"></span>
              </a>
              <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list" aria-labelledby="messageDropdown">
                <h6 class="p-3 mb-0" data-i18n="header.messages">Messages</h6>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item preview-item">
                  <div class="preview-thumbnail"><img src="<?php echo htmlspecialchars($backBoUtilisateurWeb . '/assets/images/faces/face4.jpg'); ?>" alt="image" class="rounded-circle profile-pic"></div>
                  <div class="preview-item-content"><p class="preview-subject ellipsis mb-1">Admin message center</p><p class="text-muted mb-0"> Ready for team updates </p></div>
                </a>
                <div class="dropdown-divider"></div>
                <p class="p-3 mb-0 text-center" data-i18n="header.noMessages">No new messages</p>
              </div>
            </li>
            <li class="nav-item dropdown border-left">
              <a class="nav-link count-indicator dropdown-toggle" id="notificationDropdown" href="#" data-toggle="dropdown" title="Notifications" data-i18n-title="header.notifications" data-i18n-aria-label="header.notifications" data-bo-notification-toggle>
                <i class="mdi mdi-bell"></i>
                <span class="count bg-danger d-none" id="boNotifCount" aria-live="polite" style="min-width:18px;height:18px;line-height:18px;font-size:.65rem;text-align:center;border-radius:999px;"></span>
              </a>
              <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list" aria-labelledby="notificationDropdown" data-bo-notification-dropdown>
                <div class="d-flex align-items-center justify-content-between px-3 py-2">
                  <h6 class="mb-0" data-i18n="header.notifications">Notifications</h6>
                  <button type="button" class="btn btn-link btn-sm p-0 text-primary" id="boNotifMarkAll" data-i18n="header.markAllRead">Mark all as read</button>
                </div>
                <div class="dropdown-divider"></div>
                <div id="boNotifList" data-bo-notification-list>
                  <p class="p-3 mb-0 text-center" data-i18n="header.noNotifications">No new notifications</p>
                </div>
              </div>
            </li>
            <li class="nav-item dropdown cre8-profile-dropdown" data-cre8-profile-dropdown>
              <a class="nav-link cre8-profile-toggle" id="profileDropdown" href="#" data-toggle="dropdown" data-cre8-profile-toggle aria-haspopup="true" aria-expanded="false">
                <div class="navbar-profile">
                  <?php if ($adminAvatarUrl): ?>
                    <img class="img-xs rounded-circle" src="<?php echo htmlspecialchars($adminAvatarUrl); ?>" alt="Profile photo" style="object-fit:cover;">
                  <?php else: ?>
                    <span class="img-xs rounded-circle d-inline-flex align-items-center justify-content-center text-white font-weight-bold" style="background:linear-gradient(135deg,#5b4fff,#8b5cf6);"><?php echo htmlspecialchars($adminInitial); ?></span>
                  <?php endif; ?>
                  <p class="mb-0 d-none d-sm-block navbar-profile-name"><?php echo htmlspecialchars($adminName); ?></p>
                  <i class="mdi mdi-menu-down d-none d-sm-block"></i>
                </div>
              </a>
              <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list cre8-profile-menu" aria-labelledby="profileDropdown" data-cre8-profile-menu>
                <h6 class="p-3 mb-0" data-i18n="header.profile">Profile</h6>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item preview-item" href="<?php echo htmlspecialchars($backProfileSettingsUrl); ?>">
                  <div class="preview-thumbnail"><div class="preview-icon bg-dark rounded-circle"><i class="mdi mdi-settings text-success"></i></div></div>
                  <div class="preview-item-content"><p class="preview-subject mb-1" data-i18n="header.profileSettings">Profile Settings</p></div>
                </a>
                <div class="dropdown-divider"></div>
                <a href="<?php echo htmlspecialchars($backBoUtilisateurWeb . '/logout.php'); ?>" class="dropdown-item preview-item">
                  <div class="preview-thumbnail"><div class="preview-icon bg-dark rounded-circle"><i class="mdi mdi-logout text-danger"></i></div></div>
                  <div class="preview-item-content"><p class="preview-subject mb-1" data-i18n="header.logout">Logout</p></div>
                </a>
              </div>
            </li>
          </ul>
          <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-toggle="offcanvas">
            <span class="mdi mdi-format-line-spacing"></span>
          </button>
        </div>
      </nav>
      <!-- partial -->
      <script src="<?php echo htmlspecialchars($backBoRootWeb . '/layout/back-translate.js?v=' . filemtime(__DIR__ . '/back-translate.js')); ?>"></script>
      <script>
        window.Cre8BackNotifications = {
          apiUrl: <?php echo json_encode($backBoUtilisateurWeb . '/notifications_api.php', JSON_UNESCAPED_SLASHES); ?>
        };
      </script>
      <script src="<?php echo htmlspecialchars($backBoRootWeb . '/layout/back-notifications.js?v=' . filemtime(__DIR__ . '/back-notifications.js')); ?>"></script>
