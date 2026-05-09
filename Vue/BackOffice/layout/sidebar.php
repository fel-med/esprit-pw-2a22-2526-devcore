<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$backActive = $backActive ?? 'dashboard';

$cre8SelfPath = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');
$cre8Marker = '/Vue/BackOffice/';
$cre8Pos = strpos($cre8SelfPath, $cre8Marker);
$backBoRootWeb = ($cre8Pos !== false ? substr($cre8SelfPath, 0, $cre8Pos) : '') . '/Vue/BackOffice';
$backBoUtilisateurWeb = $backBoRootWeb . '/utilisateur';

$backUserName = $_SESSION['nom'] ?? ($_SESSION['user']['nom'] ?? ($_SESSION['utilisateur']['nom'] ?? 'Utilisateur'));
$backUserName = trim((string) $backUserName) !== '' ? trim((string) $backUserName) : 'Utilisateur';

$backItems = [
    ['key' => 'dashboard',      'label' => 'Dashboard',      'url' => $backBoRootWeb . '/dashboard/index.php',      'icon' => 'mdi-speedometer',            'iconClass' => 'text-primary'],
    ['key' => 'users',          'label' => 'Users',          'url' => $backBoRootWeb . '/utilisateur/index.php',    'icon' => 'mdi-account-multiple',      'iconClass' => 'text-warning'],
    ['key' => 'reclamations',   'label' => 'Reclamations',   'url' => $backBoRootWeb . '/utilisateur/reclamations.php', 'icon' => 'mdi-playlist-play',      'iconClass' => 'text-danger'],
    ['key' => 'collaborations', 'label' => 'Collaborations', 'url' => $backBoRootWeb . '/offre/index.php',          'icon' => 'mdi-briefcase-check',       'iconClass' => 'text-info'],
    ['key' => 'campaigns',      'label' => 'Campaigns',      'url' => $backBoRootWeb . '/campagne/index.php',       'icon' => 'mdi-chart-bar',             'iconClass' => 'text-success'],
    ['key' => 'products',       'label' => 'Products',       'url' => $backBoRootWeb . '/produit/index.php',        'icon' => 'mdi-cube-outline',          'iconClass' => 'text-primary'],
    ['key' => 'contracts',      'label' => 'Contracts',      'url' => $backBoRootWeb . '/contrat/index.php',        'icon' => 'mdi-file-document-outline', 'iconClass' => 'text-warning'],
    ['key' => 'posts',          'label' => 'Posts',          'url' => $backBoRootWeb . '/post/index.php',           'icon' => 'mdi-format-list-bulleted',  'iconClass' => 'text-danger'],
    ['key' => 'comments',       'label' => 'Comments',       'url' => $backBoRootWeb . '/comment/index.php',        'icon' => 'mdi-comment-text-outline',  'iconClass' => 'text-info'],
    ['key' => 'events',         'label' => 'Events',         'url' => $backBoRootWeb . '/evenement/index.php',      'icon' => 'mdi-calendar-check',        'iconClass' => 'text-success'],
    ['key' => 'forum',          'label' => 'Forum',          'url' => $backBoRootWeb . '/forum/index.php',          'icon' => 'mdi-forum-outline',         'iconClass' => 'text-primary'],
];
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
            <img class="img-xs rounded-circle" src="<?php echo htmlspecialchars($backBoUtilisateurWeb . '/assets/images/faces/face15.jpg'); ?>" alt="Admin avatar">
            <span class="count bg-success"></span>
          </div>
          <div class="profile-name">
            <h5 class="mb-0 font-weight-normal"><?php echo htmlspecialchars($backUserName); ?></h5>
            <span>Admin</span>
          </div>
        </div>

        <a href="#" id="profile-dropdown" data-toggle="dropdown" aria-label="Profile menu"><i class="mdi mdi-dots-vertical"></i></a>
        <div class="dropdown-menu dropdown-menu-right sidebar-dropdown preview-list" aria-labelledby="profile-dropdown">
          <a href="#" class="dropdown-item preview-item">
            <div class="preview-thumbnail"><div class="preview-icon bg-dark rounded-circle"><i class="mdi mdi-settings text-primary"></i></div></div>
            <div class="preview-item-content"><p class="preview-subject ellipsis mb-1 text-small">Account settings</p></div>
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
      <span class="nav-link">Navigation</span>
    </li>

    <?php foreach ($backItems as $item): ?>
      <?php
        $isDisabled = !empty($item['disabled']);
        $isActive = $backActive === $item['key'];
        $itemClass = 'nav-item menu-items' . ($isActive ? ' active' : '') . ($isDisabled ? ' back-nav-disabled' : '');
      ?>
      <li class="<?php echo htmlspecialchars($itemClass); ?>">
        <a class="nav-link" href="<?php echo htmlspecialchars($item['url']); ?>"<?php echo $isDisabled ? ' aria-disabled="true" tabindex="-1" onclick="return false;"' : ''; ?>>
          <span class="menu-icon">
            <i class="mdi <?php echo htmlspecialchars($item['icon'] . ' ' . $item['iconClass']); ?>"></i>
          </span>
          <span class="menu-title"><?php echo htmlspecialchars($item['label']); ?></span>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
</nav>
<!-- partial -->
