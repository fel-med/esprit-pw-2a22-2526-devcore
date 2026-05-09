<?php
$cre8SelfPath = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');
$cre8Marker = '/Vue/BackOffice/';
$cre8Pos = strpos($cre8SelfPath, $cre8Marker);
$backBoRootWeb = ($cre8Pos !== false ? substr($cre8SelfPath, 0, $cre8Pos) : '') . '/Vue/BackOffice';
$backBoUtilisateurWeb = $backBoRootWeb . '/utilisateur';

$adminName = $_SESSION['user']['nom'] ?? ($_SESSION['utilisateur']['nom'] ?? ($_SESSION['nom'] ?? 'Utilisateur')); 
$adminName = trim((string) $adminName) ?: 'Utilisateur';
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
                <input type="text" class="form-control" placeholder="Search admin workspace">
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
              <a class="nav-link" href="<?php echo htmlspecialchars($backBoRootWeb . '/dashboard/index.php'); ?>" title="Dashboard">
                <i class="mdi mdi-view-grid"></i>
              </a>
            </li>
            <li class="nav-item border-left">
              <a class="nav-link count-indicator back-theme-toggle" href="#" onclick="toggleDarkMode(); return false;" title="Mode jour / nuit" aria-label="Toggle light or dark mode">
                <i id="themeIcon" class="mdi mdi-weather-night"></i>
              </a>
            </li>
            <li class="nav-item dropdown border-left">
              <a class="nav-link count-indicator dropdown-toggle" id="messageDropdown" href="#" data-toggle="dropdown" aria-expanded="false">
                <i class="mdi mdi-email"></i>
                <span class="count bg-success"></span>
              </a>
              <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list" aria-labelledby="messageDropdown">
                <h6 class="p-3 mb-0">Messages</h6>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item preview-item">
                  <div class="preview-thumbnail"><img src="<?php echo htmlspecialchars($backBoUtilisateurWeb . '/assets/images/faces/face4.jpg'); ?>" alt="image" class="rounded-circle profile-pic"></div>
                  <div class="preview-item-content"><p class="preview-subject ellipsis mb-1">Admin message center</p><p class="text-muted mb-0"> Ready for team updates </p></div>
                </a>
                <div class="dropdown-divider"></div>
                <p class="p-3 mb-0 text-center">No new messages</p>
              </div>
            </li>
            <li class="nav-item dropdown border-left">
              <a class="nav-link count-indicator dropdown-toggle" id="notificationDropdown" href="#" data-toggle="dropdown">
                <i class="mdi mdi-bell"></i>
                <span class="count bg-danger"></span>
              </a>
              <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list" aria-labelledby="notificationDropdown">
                <h6 class="p-3 mb-0">Notifications</h6>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item preview-item">
                  <div class="preview-thumbnail"><div class="preview-icon bg-dark rounded-circle"><i class="mdi mdi-calendar text-success"></i></div></div>
                  <div class="preview-item-content"><p class="preview-subject mb-1">Admin workspace</p><p class="text-muted ellipsis mb-0"> Shared layout is active </p></div>
                </a>
                <div class="dropdown-divider"></div>
                <p class="p-3 mb-0 text-center">See all notifications</p>
              </div>
            </li>
            <li class="nav-item dropdown cre8-profile-dropdown" data-cre8-profile-dropdown>
              <a class="nav-link cre8-profile-toggle" id="profileDropdown" href="#" data-toggle="dropdown" data-cre8-profile-toggle aria-haspopup="true" aria-expanded="false">
                <div class="navbar-profile">
                  <img class="img-xs rounded-circle" src="<?php echo htmlspecialchars($backBoUtilisateurWeb . '/assets/images/faces/face15.jpg'); ?>" alt="">
                  <p class="mb-0 d-none d-sm-block navbar-profile-name"><?php echo htmlspecialchars($adminName); ?></p>
                  <i class="mdi mdi-menu-down d-none d-sm-block"></i>
                </div>
              </a>
              <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list cre8-profile-menu" aria-labelledby="profileDropdown" data-cre8-profile-menu>
                <h6 class="p-3 mb-0">Profile</h6>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item preview-item" href="#">
                  <div class="preview-thumbnail"><div class="preview-icon bg-dark rounded-circle"><i class="mdi mdi-settings text-success"></i></div></div>
                  <div class="preview-item-content"><p class="preview-subject mb-1">Settings</p></div>
                </a>
                <div class="dropdown-divider"></div>
                <a href="<?php echo htmlspecialchars($backBoUtilisateurWeb . '/logout.php'); ?>" class="dropdown-item preview-item">
                  <div class="preview-thumbnail"><div class="preview-icon bg-dark rounded-circle"><i class="mdi mdi-logout text-danger"></i></div></div>
                  <div class="preview-item-content"><p class="preview-subject mb-1">Log out</p></div>
                </a>
                <div class="dropdown-divider"></div>
                <p class="p-3 mb-0 text-center">Advanced settings</p>
              </div>
            </li>
          </ul>
          <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-toggle="offcanvas">
            <span class="mdi mdi-format-line-spacing"></span>
          </button>
        </div>
      </nav>
      <!-- partial -->
