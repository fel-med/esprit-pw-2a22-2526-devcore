<?php
$boUserName = $_SESSION['user']['nom'] ?? 'Utilisateur';
$boSearchPlaceholder = $boSearchPlaceholder ?? 'Search products';
$boAvatarPath = $boAvatarPath ?? '../../public/images/backoffice/face15.jpg';
?>
<nav class="navbar p-0 fixed-top d-flex flex-row">
  <div class="navbar-menu-wrapper flex-grow d-flex align-items-stretch">
    <button class="navbar-toggler align-self-center" type="button" aria-label="Menu">
      <span class="fas fa-bars"></span>
    </button>
    <ul class="navbar-nav w-100">
      <li class="nav-item w-100">
        <form class="nav-link mt-2 mt-md-0 d-none d-lg-flex search" onsubmit="return false;">
          <input type="text" class="form-control" placeholder="<?= htmlspecialchars($boSearchPlaceholder, ENT_QUOTES, 'UTF-8') ?>">
        </form>
      </li>
    </ul>
    <ul class="navbar-nav navbar-nav-right">
      <li class="nav-item bo-lang-item">
        <button type="button" class="bo-lang-toggle" id="boLangToggle" onclick="toggleBackOfficeLanguage()" aria-label="Traduction français anglais">
          <span class="bo-lang-icon"><i class="fas fa-language"></i></span>
          <span class="bo-lang-label">EN</span>
        </button>
      </li>
      <li class="nav-item nav-settings d-none d-lg-block">
        <a class="nav-link" href="#" aria-label="Apps"><i class="fas fa-th-large"></i></a>
      </li>
      <li class="nav-item dropdown border-left">
        <a class="nav-link count-indicator" href="#" aria-label="Messages">
          <i class="fas fa-envelope"></i>
          <span class="count bg-success"></span>
        </a>
      </li>
      <li class="nav-item dropdown border-left">
        <a class="nav-link count-indicator" href="#" aria-label="Notifications">
          <i class="fas fa-bell"></i>
          <span class="count bg-danger"></span>
        </a>
      </li>
      <li class="nav-item dropdown">
        <a class="nav-link" id="profileDropdown" href="#">
          <div class="navbar-profile">
            <img class="bo-avatar-img bo-avatar-sm" src="<?= htmlspecialchars($boAvatarPath, ENT_QUOTES, 'UTF-8') ?>" alt="">
            <p class="mb-0 d-none d-sm-block navbar-profile-name"><?= htmlspecialchars($boUserName, ENT_QUOTES, 'UTF-8') ?></p>
            <i class="fas fa-caret-down d-none d-sm-block"></i>
          </div>
        </a>
      </li>
    </ul>
  </div>
</nav>
