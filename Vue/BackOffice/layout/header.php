<?php
$cre8BackHeaderPath = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');
$cre8BackHeaderCurrent = 'Dashboard';
if (strpos($cre8BackHeaderPath, '/BackOffice/offre/') !== false) {
    $cre8BackHeaderCurrent = 'Offers';
} elseif (strpos($cre8BackHeaderPath, '/BackOffice/condidature/') !== false) {
    $cre8BackHeaderCurrent = 'Candidatures';
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
    <a class="cre8-admin-action" href="../offre/index.php">Offers</a>
    <a class="cre8-admin-action cre8-admin-action-primary" href="../condidature/index.php">Candidatures</a>
  </div>
</header>
