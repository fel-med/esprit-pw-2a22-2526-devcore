<?php
$cre8BackHeaderPath = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');
$cre8BackHeaderVuePos = strpos($cre8BackHeaderPath, '/Vue/');
$cre8BackHeaderBase = $cre8BackHeaderVuePos !== false ? substr($cre8BackHeaderPath, 0, $cre8BackHeaderVuePos) : '';
$cre8BackHeaderExitHref = $cre8BackHeaderBase . '/Vue/FrontOffice/offre/login.php?logout=1';
$cre8BackHeaderCurrent = 'Dashboard';
$cre8BackHeaderIsOffers = false;
$cre8BackHeaderIsCandidatures = false;
if (strpos($cre8BackHeaderPath, '/BackOffice/offre/') !== false) {
    $cre8BackHeaderCurrent = 'Offers';
    $cre8BackHeaderIsOffers = true;
} elseif (strpos($cre8BackHeaderPath, '/BackOffice/condidature/') !== false) {
    $cre8BackHeaderCurrent = 'Candidatures';
    $cre8BackHeaderIsCandidatures = true;
}
?>
<header class="cre8-admin-topbar" role="banner">
  <nav class="cre8-admin-breadcrumb" aria-label="Breadcrumb">
    <span>Cre8Connect</span>
    <span>/</span>
    <span>Admin</span>
    <span>/</span>
    <strong><?php echo htmlspecialchars($cre8BackHeaderCurrent); ?></strong>
  </nav>
  <div class="cre8-admin-topbar-actions">
    <label class="cre8-admin-search">
      <span aria-hidden="true">⌕</span>
      <input type="search" placeholder="Search dashboard..." aria-label="Search dashboard">
    </label>
    <?php require __DIR__ . '/../condidature/theme_toggle.php'; ?>
    <div class="cre8-admin-module-switch" role="radiogroup" aria-label="Admin module">
      <a
        class="cre8-admin-action<?php echo $cre8BackHeaderIsOffers ? ' is-active' : ''; ?>"
        href="../offre/index.php"
        role="radio"
        aria-checked="<?php echo $cre8BackHeaderIsOffers ? 'true' : 'false'; ?>"
      >Offers</a>
      <a
        class="cre8-admin-action<?php echo $cre8BackHeaderIsCandidatures ? ' is-active' : ''; ?>"
        href="../condidature/index.php"
        role="radio"
        aria-checked="<?php echo $cre8BackHeaderIsCandidatures ? 'true' : 'false'; ?>"
      >Candidatures</a>
    </div>
    <a class="cre8-admin-action cre8-admin-action-exit" href="<?php echo htmlspecialchars($cre8BackHeaderExitHref); ?>">Exit</a>
  </div>
</header>
