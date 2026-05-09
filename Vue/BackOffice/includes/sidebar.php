<?php
$activeMenu = $activeMenu ?? '';
$boUserName = $_SESSION['user']['nom'] ?? 'Utilisateur';
$boLogoPath = $boLogoPath ?? '../../public/images/backoffice/logo.svg';
$boAvatarPath = $boAvatarPath ?? '../../public/images/backoffice/face15.jpg';

function boActiveClass(string $key, string $activeMenu): string {
    return $key === $activeMenu ? ' active' : '';
}
?>
<nav class="sidebar sidebar-offcanvas" id="sidebar">
  <div class="sidebar-brand-wrapper">
    <a class="sidebar-brand brand-logo" href="../utilisateur/index.php" aria-label="Cre8Connect">
      <img class="bo-logo-img" src="<?= htmlspecialchars($boLogoPath, ENT_QUOTES, 'UTF-8') ?>" alt="Cre8Connect">
    </a>
  </div>

  <ul class="nav">
    <li class="nav-item profile">
      <div class="profile-desc">
        <div class="profile-pic">
          <div class="count-indicator">
            <img class="bo-avatar-img" src="<?= htmlspecialchars($boAvatarPath, ENT_QUOTES, 'UTF-8') ?>" alt="">
            <span class="count bg-success"></span>
          </div>
          <div class="profile-name">
            <h5><?= htmlspecialchars($boUserName, ENT_QUOTES, 'UTF-8') ?></h5>
            <span>Admin</span>
          </div>
        </div>
        <a href="#" class="bo-profile-dots" aria-label="Profil"><i class="fas fa-ellipsis-v"></i></a>
      </div>
    </li>

    <li class="nav-item nav-category">
      <span class="nav-link">Navigation</span>
    </li>

    <li class="nav-item menu-items">
      <a class="nav-link" href="#" onclick="toggleDarkMode(); return false;">
        <span class="menu-icon"><i id="themeIcon" class="fas fa-moon"></i></span>
        <span class="menu-title">Mode jour / nuit</span>
      </a>
    </li>

    <li class="nav-item menu-items<?= boActiveClass('dashboard', $activeMenu) ?>">
      <a class="nav-link" href="../utilisateur/index.php">
        <span class="menu-icon"><i class="fas fa-tachometer-alt"></i></span>
        <span class="menu-title">Dashboard</span>
      </a>
    </li>

    <li class="nav-item menu-items<?= boActiveClass('reclamations', $activeMenu) ?>">
      <a class="nav-link" href="../utilisateur/reclamations.php">
        <span class="menu-icon"><i class="fas fa-stream"></i></span>
        <span class="menu-title">reclamations</span>
      </a>
    </li>

    <li class="nav-item menu-items<?= boActiveClass('offres', $activeMenu) ?>">
      <a class="nav-link" href="../offre/index.php">
        <span class="menu-icon"><i class="fas fa-th"></i></span>
        <span class="menu-title">offers</span>
      </a>
    </li>

    <li class="nav-item menu-items<?= boActiveClass('campagne', $activeMenu) ?>">
      <a class="nav-link" href="../campagne/index.php">
        <span class="menu-icon"><i class="fas fa-chart-bar"></i></span>
        <span class="menu-title">campagne</span>
      </a>
    </li>

    <li class="nav-item menu-items<?= boActiveClass('produit', $activeMenu) ?>">
      <a class="nav-link" href="../produit/index.php">
        <span class="menu-icon"><i class="fas fa-cube"></i></span>
        <span class="menu-title">produit</span>
      </a>
    </li>

    <li class="nav-item menu-items<?= boActiveClass('contrat', $activeMenu) ?>">
      <a class="nav-link" href="../contrat/index.php">
        <span class="menu-icon"><i class="fas fa-file-signature"></i></span>
        <span class="menu-title">contrat</span>
      </a>
    </li>

    <li class="nav-item menu-items<?= boActiveClass('events', $activeMenu) ?>">
      <a class="nav-link" href="../evenement/index.php">
        <span class="menu-icon"><i class="fas fa-id-card"></i></span>
        <span class="menu-title">events</span>
      </a>
    </li>
  </ul>
</nav>
