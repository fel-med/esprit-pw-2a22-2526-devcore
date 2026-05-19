<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../../Controleur/session_helper.php';
require_once __DIR__ . '/../../../Controleur/profileC.php';

$backActive = $backActive ?? 'dashboard';

$cre8SelfPath = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');
$cre8Marker = '/Vue/BackOffice/';
$cre8Pos = strpos($cre8SelfPath, $cre8Marker);

if ($cre8Pos !== false) {
    // Direct view access
    $backBoRootWeb = substr($cre8SelfPath, 0, $cre8Pos) . '/Vue/BackOffice';
} else {
    // Controller access — find project root via /Controleur/
    $_ctrlPos = strpos($cre8SelfPath, '/Controleur/');
    $backBoRootWeb = ($_ctrlPos !== false ? substr($cre8SelfPath, 0, $_ctrlPos) : '') . '/Vue/BackOffice';
}
$backBoUtilisateurWeb = $backBoRootWeb . '/utilisateur';
$backProfileSettingsUrl = $backBoUtilisateurWeb . '/profile_settings.php';
// Controller base — extract project root the same way as above
$_ctrlPosForUrl = strpos($cre8SelfPath, '/Controleur/');
$_projectRootForUrl = $_ctrlPosForUrl !== false
    ? substr($cre8SelfPath, 0, $_ctrlPosForUrl)
    : ($cre8Pos !== false ? substr($cre8SelfPath, 0, $cre8Pos) : '');
$backProfileUploadWeb = $_projectRootForUrl . '/Vue/public/uploads/profile';
$backBoControleurWeb = $_projectRootForUrl . '/Controleur';

$backUserName = $_SESSION['nom'] ?? ($_SESSION['user']['nom'] ?? ($_SESSION['utilisateur']['nom'] ?? 'User'));
$backUserName = trim((string) $backUserName) !== '' ? trim((string) $backUserName) : 'User';
$backUserInitial = function_exists('mb_substr') ? mb_substr($backUserName, 0, 1, 'UTF-8') : substr($backUserName, 0, 1);
$backUserInitial = strtoupper((string) $backUserInitial) ?: 'U';
$backRole = cc_current_user_role();
$backRoleLabel = match ($backRole) {
    'hyper_admin' => 'Hyper Admin',
    'super_admin' => 'Super Admin',
    default => 'Admin',
};
$backRoleKey = match ($backRole) {
    'hyper_admin' => 'role.hyperAdmin',
    'super_admin' => 'role.superAdmin',
    default => 'role.admin',
};
$backAvatarUrl = null;
$backUserId = cc_current_user_id();

if ($backUserId !== null) {
    try {
        $profileC = new ProfileC();
        $backAvatarUrl = $profileC->getProfileImageUrl($backUserId, $backProfileUploadWeb);
    } catch (Throwable $e) {
        $backAvatarUrl = null;
    }
}

$backTools = [];

if (isSuperAdminRole(cc_current_user_role())) {
    $backTools[] = [
        'key' => 'admin_management',
        'label' => 'Admin Management',
        'url' => $backBoRootWeb . '/utilisateur/admin_management.php',
        'icon' => 'mdi-shield-account',
        'iconClass' => 'text-success',
        'i18n' => 'nav.adminManagement',
        'aliases' => 'admins super admin hyper admin roles permissions management',
    ];
}

if (cc_is_backoffice_role($backRole)) {
    $backTools[] = [
        'key' => 'admin_requests',
        'label' => 'Admin Requests',
        'url' => $backBoRootWeb . '/utilisateur/admin_requests.php',
        'icon' => 'mdi-message-alert-outline',
        'iconClass' => 'text-info',
        'i18n' => 'nav.adminRequests',
        'aliases' => 'requests demands messages approval super hyper admin',
    ];
}

if (isHyperAdmin($backRole)) {
    $backTools[] = [
        'key' => 'server_center',
        'label' => 'Server Center',
        'url' => $backBoRootWeb . '/utilisateur/server_center.php',
        'icon' => 'mdi-server-security',
        'iconClass' => 'text-info',
        'i18n' => 'nav.serverCenter',
        'aliases' => 'server system diagnostics disk database php health',
    ];
}

$backItems = array_merge([
    [
        'key' => 'dashboard',
        'label' => 'Dashboard',
        'url' => $backBoRootWeb . '/dashboard/index.php',
        'icon' => 'mdi-speedometer',
        'iconClass' => 'text-primary',
        'i18n' => 'nav.dashboard',
        'aliases' => 'home overview analytics statistics main',
        'activeKeys' => ['dashboard'],
    ],
    [
        'key' => 'user_center',
        'label' => 'User Center',
        'url' => $backBoRootWeb . '/utilisateur/index.php',
        'icon' => 'mdi-account-group',
        'iconClass' => 'text-warning',
        'i18n' => 'nav.userCenter',
        'aliases' => 'users user creator brand marque createur complaints reclamations suspension appeals accounts',
        'activeKeys' => ['user_center', 'users', 'reclamations'],
    ],
    [
        'key' => 'collaborations',
        'label' => 'Collaborations',
        'url' => $backBoRootWeb . '/offre/index.php',
        'icon' => 'mdi-briefcase-check',
        'iconClass' => 'text-info',
        'i18n' => 'nav.collaborations',
        'aliases' => 'offers offre candidatures applications invitations cre8shield collaboration',
        'activeKeys' => ['collaborations', 'offers', 'offre', 'condidature', 'candidature', 'cre8shield'],
    ],
    [
        'key' => 'business',
        'label' => 'Business',
        'url' => $backBoRootWeb . '/campagne/index.php',
        'icon' => 'mdi-chart-bar',
        'iconClass' => 'text-success',
        'i18n' => 'nav.business',
        'aliases' => 'campaigns campagnes products produits contracts contrats marketing business',
        'activeKeys' => ['business', 'campaigns', 'campagne', 'products', 'produit', 'contracts', 'contrat'],
    ],
    [
        'key' => 'community',
        'label' => 'Community',
        'url' => $backBoRootWeb . '/post/index.php',
        'icon' => 'mdi-forum',
        'iconClass' => 'text-danger',
        'i18n' => 'nav.community',
        'aliases' => 'posts publications comments commentaires community moderation content',
        'activeKeys' => ['community', 'posts', 'post', 'comments', 'comment'],
    ],
    [
        'key' => 'events_hub',
        'label' => 'Events',
        'url' => $backBoControleurWeb . '/evenementC.php?action=admin',
        'icon' => 'mdi-calendar-star',
        'iconClass' => 'text-success',
        'i18n' => 'nav.eventsHub',
        'aliases' => 'events evenement forum forums community events',
        'activeKeys' => ['events_hub', 'events', 'evenement', 'forum'],
    ],
], $backTools);

$renderBackSidebarItem = static function (array $item) use ($backActive): void {
    $isDisabled = !empty($item['disabled']);
    $activeKeys = $item['activeKeys'] ?? [$item['key']];
    $isActive = in_array($backActive, $activeKeys, true);
    $itemClass = 'nav-item menu-items cre8-domain-nav-item' . ($isActive ? ' active' : '') . ($isDisabled ? ' back-nav-disabled' : '');
    $i18nKey = $item['i18n'] ?? ('nav.' . $item['key']);
    $aliases = trim((string)($item['aliases'] ?? ''));
    ?>
      <li class="<?php echo htmlspecialchars($itemClass); ?>">
        <a class="nav-link" href="<?php echo htmlspecialchars($item['url']); ?>" data-cre8-sidebar-link data-cre8-nav-key="<?php echo htmlspecialchars($item['key']); ?>" data-cre8-nav-aliases="<?php echo htmlspecialchars($aliases); ?>"<?php echo $isDisabled ? ' aria-disabled="true" tabindex="-1" onclick="return false;"' : ''; ?>>
          <span class="menu-icon">
            <i class="mdi <?php echo htmlspecialchars($item['icon'] . ' ' . $item['iconClass']); ?>"></i>
          </span>
          <span class="menu-title" data-i18n="<?php echo htmlspecialchars($i18nKey); ?>"><?php echo htmlspecialchars($item['label']); ?></span>
        </a>
      </li>
    <?php
};
?>
<!-- partial:partials/_sidebar.html -->
<nav class="sidebar sidebar-offcanvas" id="sidebar">
  <div class="sidebar-brand-wrapper d-none d-lg-flex align-items-center justify-content-center fixed-top">
    <a class="sidebar-brand brand-logo cre8-sidebar-wordmark" href="<?php echo htmlspecialchars($backBoRootWeb . '/dashboard/index.php'); ?>" aria-label="Cre8connect BackOffice">
      <span>Cre</span><img src="<?php echo htmlspecialchars($backBoUtilisateurWeb . '/assets/images/logo-mini.svg'); ?>" alt="8"><span>connect</span>
    </a>
    <a class="sidebar-brand brand-logo-mini" href="<?php echo htmlspecialchars($backBoRootWeb . '/dashboard/index.php'); ?>">
      <img src="<?php echo htmlspecialchars($backBoUtilisateurWeb . '/assets/images/logo-mini.svg'); ?>" alt="logo">
    </a>
  </div>

  <ul class="nav">
    <li class="nav-item profile">
      <div class="profile-desc">
        <div class="profile-pic">
          <div class="count-indicator">
            <?php if ($backAvatarUrl): ?>
              <img class="img-xs rounded-circle" src="<?php echo htmlspecialchars($backAvatarUrl); ?>" alt="Profile photo" style="object-fit:cover;">
            <?php else: ?>
              <span class="img-xs rounded-circle d-inline-flex align-items-center justify-content-center text-white font-weight-bold" style="background:linear-gradient(135deg,#5b4fff,#8b5cf6);"><?php echo htmlspecialchars($backUserInitial); ?></span>
            <?php endif; ?>
            <span class="count bg-success"></span>
          </div>
          <div class="profile-name">
            <h5 class="mb-0 font-weight-normal"><?php echo htmlspecialchars($backUserName); ?></h5>
            <span class="cre8-role-badge" data-i18n="<?php echo htmlspecialchars($backRoleKey); ?>"><?php echo htmlspecialchars($backRoleLabel); ?></span>
          </div>
        </div>

        <a href="#" id="profile-dropdown" data-toggle="dropdown" aria-label="Profile menu"><i class="mdi mdi-dots-vertical"></i></a>
        <div class="dropdown-menu dropdown-menu-right sidebar-dropdown preview-list" aria-labelledby="profile-dropdown">
          <a href="<?php echo htmlspecialchars($backProfileSettingsUrl); ?>" class="dropdown-item preview-item">
            <div class="preview-thumbnail"><div class="preview-icon bg-dark rounded-circle"><i class="mdi mdi-settings text-primary"></i></div></div>
            <div class="preview-item-content"><p class="preview-subject ellipsis mb-1 text-small" data-i18n="header.profileSettings">Profile Settings</p></div>
          </a>
          <div class="dropdown-divider"></div>
          <a href="#" class="dropdown-item preview-item">
            <div class="preview-thumbnail"><div class="preview-icon bg-dark rounded-circle"><i class="mdi mdi-onepassword text-info"></i></div></div>
            <div class="preview-item-content"><p class="preview-subject ellipsis mb-1 text-small">Change Password</p></div>
          </a>
          <div class="dropdown-divider"></div>
          <a href="#" class="dropdown-item preview-item">
            <div class="preview-thumbnail"><div class="preview-icon bg-dark rounded-circle"><i class="mdi mdi-calendar-today text-success"></i></div></div>
            <div class="preview-item-content"><p class="preview-subject ellipsis mb-1 text-small">To-do list</p></div>
          </a>
        </div>
      </div>
    </li>

    <li class="nav-item nav-category">
      <span class="nav-link" data-i18n="nav.navigation">Navigation</span>
    </li>

    <?php foreach ($backItems as $backItem): ?>
      <?php $renderBackSidebarItem($backItem); ?>
    <?php endforeach; ?>
  </ul>
</nav>
<!-- partial -->
