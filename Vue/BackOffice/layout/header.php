<?php
$cre8BackHeaderPath = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');
$cre8BackHeaderVuePos = strpos($cre8BackHeaderPath, '/Vue/');
$cre8BackHeaderBase = $cre8BackHeaderVuePos !== false ? substr($cre8BackHeaderPath, 0, $cre8BackHeaderVuePos) : '';
$cre8BackHeaderCssPath = __DIR__ . '/back-layout.css';
$cre8BackHeaderCssHref = $cre8BackHeaderBase . '/Vue/BackOffice/layout/back-layout.css';
if (is_file($cre8BackHeaderCssPath)) {
    $cre8BackHeaderCssHref .= '?v=' . rawurlencode((string) filemtime($cre8BackHeaderCssPath));
}
$cre8BackHeaderAvatar = $cre8BackHeaderBase . '/Vue/public/images/face15.jpg';
$cre8BackHeaderUser = $_SESSION['utilisateur'] ?? [];
$cre8BackHeaderName = trim((string) ($cre8BackHeaderUser['nom'] ?? 'Utilisateur'));
$cre8BackHeaderInitial = strtoupper(substr($cre8BackHeaderName, 0, 1)) ?: 'U';
$cre8BackHeaderTabs = [
    [
        'label' => 'Offers',
        'href' => $cre8BackHeaderBase . '/Vue/BackOffice/offre/index.php',
        'active' => strpos($cre8BackHeaderPath, '/BackOffice/offre/') !== false,
    ],
    [
        'label' => 'Candidature',
        'href' => $cre8BackHeaderBase . '/Vue/BackOffice/condidature/index.php',
        'active' => strpos($cre8BackHeaderPath, '/BackOffice/condidature/') !== false,
    ],
    [
        'label' => 'Cre8Shield',
        'href' => $cre8BackHeaderBase . '/Vue/BackOffice/cre8shield/index.php',
        'active' => strpos($cre8BackHeaderPath, '/BackOffice/cre8shield/') !== false,
    ],
];
?>
<link rel="stylesheet" href="<?php echo htmlspecialchars($cre8BackHeaderCssHref); ?>">
<header class="cre8-back-header cre8-admin-unified-topbar" role="banner">
  <button class="cre8-admin-menu-button" type="button" aria-label="Open admin menu">
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <path d="M6 8h12M6 12h12M6 16h12" />
    </svg>
  </button>

  <label class="cre8-admin-unified-search">
    <span class="cre8-admin-search-icon" aria-hidden="true">
      <svg viewBox="0 0 24 24">
        <path d="m20 20-4.3-4.3m1.8-4.7a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z" />
      </svg>
    </span>
    <input type="search" placeholder="Search products" aria-label="Search products">
  </label>

  <div class="cre8-admin-topbar-tools" aria-label="Admin shortcuts">
    <button class="cre8-admin-icon-button" type="button" aria-label="Applications">
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M4.5 4.5h6.2v6.2H4.5V4.5Zm8.8 0h6.2v6.2h-6.2V4.5ZM4.5 13.3h6.2v6.2H4.5v-6.2Zm8.8 0h6.2v6.2h-6.2v-6.2Z" />
      </svg>
    </button>

    <span class="cre8-admin-topbar-separator" aria-hidden="true"></span>

    <button class="cre8-admin-icon-button has-dot has-dot-green" type="button" aria-label="Messages">
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M4.2 6.2h15.6c.66 0 1.2.54 1.2 1.2v9.7c0 .66-.54 1.2-1.2 1.2H4.2c-.66 0-1.2-.54-1.2-1.2V7.4c0-.66.54-1.2 1.2-1.2Zm.9 2.3 6.9 4.65 6.9-4.65v-.35L12 12.8 5.1 8.15v.35Z" />
      </svg>
    </button>

    <span class="cre8-admin-topbar-separator" aria-hidden="true"></span>

    <button class="cre8-admin-icon-button has-dot has-dot-red" type="button" aria-label="Notifications">
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M12 22a2.35 2.35 0 0 0 2.25-1.7h-4.5A2.35 2.35 0 0 0 12 22Zm7.35-5.15-1.45-1.9V10a5.95 5.95 0 0 0-4.45-5.75v-.7a1.45 1.45 0 0 0-2.9 0v.7A5.95 5.95 0 0 0 6.1 10v4.95l-1.45 1.9a.9.9 0 0 0 .72 1.45h13.26a.9.9 0 0 0 .72-1.45Z" />
      </svg>
    </button>

    <div class="cre8-admin-user-menu" aria-label="Current user">
      <span class="cre8-admin-topbar-avatar">
        <img src="<?php echo htmlspecialchars($cre8BackHeaderAvatar); ?>" alt="Utilisateur avatar">
      </span>
      <span class="cre8-admin-user-name">Utilisateur</span>
      <svg class="cre8-admin-user-arrow" viewBox="0 0 24 24" aria-hidden="true">
        <path d="m7 10 5 5 5-5" />
      </svg>
    </div>
  </div>
</header>
<nav class="cre8-back-module-tabs" aria-label="BackOffice module tabs">
  <?php foreach ($cre8BackHeaderTabs as $cre8BackHeaderTab): ?>
    <a
      class="cre8-back-module-tab<?php echo !empty($cre8BackHeaderTab['active']) ? ' is-active' : ''; ?>"
      href="<?php echo htmlspecialchars($cre8BackHeaderTab['href']); ?>"
    >
      <?php echo htmlspecialchars($cre8BackHeaderTab['label']); ?>
    </a>
  <?php endforeach; ?>
</nav>
