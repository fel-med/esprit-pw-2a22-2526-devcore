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
$adminRole = function_exists('cc_current_user_role') ? cc_current_user_role() : 'admin';
$adminRoleKey = match ($adminRole) {
    'hyper_admin' => 'role.hyperAdmin',
    'super_admin' => 'role.superAdmin',
    default => 'role.admin',
};
$adminRoleShortKey = match ($adminRole) {
    'hyper_admin' => 'role.short.hyperAdmin',
    'super_admin' => 'role.short.superAdmin',
    default => 'role.short.admin',
};
$adminRoleShortLabel = match ($adminRole) {
    'hyper_admin' => 'Hyper',
    'super_admin' => 'Super',
    default => 'Admin',
};
$adminRoleLabel = match ($adminRole) {
    'hyper_admin' => 'Hyper Admin',
    'super_admin' => 'Super Admin',
    default => 'Regular Admin',
};
$adminRoleClass = match ($adminRole) {
    'hyper_admin' => 'cre8-role-badge--hyper',
    'super_admin' => 'cre8-role-badge--super',
    default => 'cre8-role-badge--admin',
};


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
          <ul class="navbar-nav w-100">
            <li class="nav-item w-100">
              <form class="nav-link mt-2 mt-md-0 d-none d-lg-flex search cre8-workspace-search" action="#" method="get" role="search" autocomplete="off" data-cre8-workspace-search>
                <input id="cre8WorkspaceSearchInput" type="text" class="form-control" placeholder="Quick open a BackOffice area..." data-i18n-placeholder="header.searchPlaceholder" aria-controls="cre8WorkspaceSearchResults" aria-expanded="false">
                <span class="cre8-workspace-search-kbd" aria-hidden="true">Ctrl&nbsp;K</span>
                <div id="cre8WorkspaceSearchResults" class="cre8-workspace-search-results" hidden>
                  <div class="cre8-workspace-search-empty">
                    <strong data-i18n="header.searchNoResults">No page found</strong>
                    <span data-i18n="header.searchHint">Press Enter to open</span>
                  </div>
                </div>
              </form>
            </li>
          </ul>
          <ul class="navbar-nav navbar-nav-right cre8-header-actions">
            <li class="nav-item nav-settings d-none d-lg-block">
              <a class="nav-link" href="<?php echo htmlspecialchars($backBoRootWeb . '/dashboard/index.php'); ?>" title="Dashboard" data-i18n-title="nav.dashboard" data-i18n-aria-label="nav.dashboard">
                <i class="mdi mdi-view-grid"></i>
              </a>
            </li>
            <li class="nav-item cre8-header-action-item">
              <a class="nav-link count-indicator back-theme-toggle" href="#" onclick="toggleDarkMode(); return false;" title="Mode jour / nuit" aria-label="Toggle light or dark mode">
                <i id="themeIcon" class="mdi mdi-weather-night"></i>
              </a>
            </li>
            <li class="nav-item cre8-header-action-item d-flex align-items-center" title="Language" data-i18n-title="common.language">
              <div class="cre8-lang-toggle" aria-label="Language" data-i18n-aria-label="common.language">
                <button type="button" class="cre8-lang-btn" data-cre8-back-lang="en" data-i18n-title="common.english">EN</button>
                <button type="button" class="cre8-lang-btn" data-cre8-back-lang="fr" data-i18n-title="common.french">FR</button>
              </div>
            </li>
            <li class="nav-item dropdown cre8-header-action-item">
              <a class="nav-link count-indicator dropdown-toggle" id="notificationDropdown" href="#" data-toggle="dropdown" title="Notifications" data-i18n-title="header.notifications" data-i18n-aria-label="header.notifications" data-bo-notification-toggle>
                <i class="mdi mdi-bell"></i>
                <span class="count bg-danger d-none" id="boNotifCount" aria-live="polite" style="min-width:18px;height:18px;line-height:18px;font-size:.65rem;text-align:center;border-radius:999px;"></span>
              </a>
              <div class="dropdown-menu dropdown-menu-right navbar-dropdown cre8-notification-menu" aria-labelledby="notificationDropdown" data-bo-notification-dropdown>
                <div class="cre8-notification-head">
                  <div>
                    <h6 data-i18n="header.notifications">Notifications</h6>
                    <small data-i18n="header.notificationSubtitle">Latest admin updates</small>
                  </div>
                  <button type="button" class="cre8-notification-mark-all" id="boNotifMarkAll" data-i18n="header.markAllRead">Mark all as read</button>
                </div>
                <div id="boNotifList" class="cre8-notification-list" data-bo-notification-list>
                  <div class="cre8-notification-empty">
                    <span class="cre8-notification-empty-icon"><i class="mdi mdi-bell-check-outline"></i></span>
                    <strong data-i18n="header.noNotifications">No new notifications</strong>
                    <small data-i18n="header.allCaughtUp">You're all caught up.</small>
                  </div>
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
                  <div class="cre8-header-user-meta d-none d-sm-flex">
                    <span class="cre8-header-user-name-row">
                      <span class="navbar-profile-name"><?php echo htmlspecialchars($adminName); ?></span>
                      <i class="mdi mdi-menu-down cre8-profile-chevron" aria-hidden="true"></i>
                    </span>
                    <span class="cre8-role-badge <?php echo htmlspecialchars($adminRoleClass); ?> d-none d-md-inline-flex"><?php echo htmlspecialchars($adminRoleLabel); ?></span>
                  </div>
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
      <script src="<?php echo htmlspecialchars($backBoRootWeb . '/layout/back-workspace-search.js?v=' . filemtime(__DIR__ . '/back-workspace-search.js')); ?>"></script>
