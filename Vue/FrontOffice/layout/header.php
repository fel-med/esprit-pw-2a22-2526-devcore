<?php
$cre8FrontPath = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');
$cre8FrontVuePos = strpos($cre8FrontPath, '/Vue/');
$cre8FrontBase = $cre8FrontVuePos !== false ? substr($cre8FrontPath, 0, $cre8FrontVuePos) : '';
$cre8FrontUser = $_SESSION['utilisateur'] ?? [];
$cre8FrontRole = strtolower(trim((string) ($cre8FrontUser['role'] ?? '')));
$cre8FrontName = trim((string) ($cre8FrontUser['nom'] ?? ''));
$cre8FrontEmail = trim((string) ($cre8FrontUser['email'] ?? ''));
$cre8FrontDisplayName = $cre8FrontName !== '' ? $cre8FrontName : ($cre8FrontEmail !== '' ? $cre8FrontEmail : 'Guest');
$cre8FrontInitial = strtoupper(substr($cre8FrontDisplayName, 0, 1));
$cre8FrontInitial = $cre8FrontInitial !== '' ? $cre8FrontInitial : 'C';
$cre8FrontLogo = $cre8FrontBase . '/Vue/public/images/logo.png';

$cre8FrontRoleLabel = match ($cre8FrontRole) {
    'marque' => 'Brand',
    'createur' => 'Creator',
    'admin' => 'Admin',
    default => 'Workspace',
};

$cre8FrontOffersHref = match ($cre8FrontRole) {
    'marque' => $cre8FrontBase . '/Vue/FrontOffice/offre/brand_index.php',
    'createur' => $cre8FrontBase . '/Vue/FrontOffice/offre/creator_list.php',
    'admin' => $cre8FrontBase . '/Vue/BackOffice/offre/index.php',
    default => $cre8FrontBase . '/Vue/FrontOffice/offre/login.php',
};

$cre8FrontCandidatureHref = match ($cre8FrontRole) {
    'marque' => $cre8FrontBase . '/Vue/FrontOffice/condidature/brand_index.php',
    'createur' => $cre8FrontBase . '/Vue/FrontOffice/condidature/index.php',
    'admin' => $cre8FrontBase . '/Vue/BackOffice/condidature/index.php',
    default => $cre8FrontBase . '/Vue/FrontOffice/offre/login.php',
};

$cre8FrontNav = $cre8FrontRole === 'createur'
    ? [
        ['label' => 'Dashboard', 'href' => $cre8FrontOffersHref, 'active' => false],
        ['label' => 'Offers', 'href' => $cre8FrontOffersHref, 'active' => strpos($cre8FrontPath, '/FrontOffice/offre/') !== false],
        ['label' => 'Candidatures', 'href' => $cre8FrontCandidatureHref, 'active' => strpos($cre8FrontPath, '/FrontOffice/condidature/') !== false],
        ['label' => 'Events', 'href' => '#', 'active' => false],
        ['label' => 'Forum', 'href' => '#', 'active' => false],
    ]
    : [
        ['label' => 'Dashboard', 'href' => $cre8FrontOffersHref, 'active' => false],
        ['label' => 'My Offers', 'href' => $cre8FrontOffersHref, 'active' => strpos($cre8FrontPath, '/FrontOffice/offre/') !== false],
        ['label' => 'Candidatures', 'href' => $cre8FrontCandidatureHref, 'active' => strpos($cre8FrontPath, '/FrontOffice/condidature/') !== false],
        ['label' => 'Campaigns', 'href' => '#', 'active' => false],
        ['label' => 'My Profile', 'href' => '#', 'active' => false],
    ];
?>
<header class="cre8-front-header" role="banner">
  <a class="cre8-front-brand" href="<?php echo htmlspecialchars($cre8FrontOffersHref); ?>" aria-label="Cre8Connect home">
    <img src="<?php echo htmlspecialchars($cre8FrontLogo); ?>" alt="Cre8Connect logo">
    <span>Cre8Connect</span>
  </a>

  <nav class="cre8-front-nav" aria-label="FrontOffice navigation">
    <?php foreach ($cre8FrontNav as $cre8FrontItem): ?>
      <a
        class="cre8-front-nav-link<?php echo !empty($cre8FrontItem['active']) ? ' is-active' : ''; ?>"
        href="<?php echo htmlspecialchars($cre8FrontItem['href']); ?>"
      >
        <?php echo htmlspecialchars($cre8FrontItem['label']); ?>
      </a>
    <?php endforeach; ?>
  </nav>

  <div class="cre8-front-user">
    <span class="cre8-front-role-pill"><?php echo htmlspecialchars($cre8FrontRoleLabel); ?></span>
    <span class="cre8-front-avatar" title="<?php echo htmlspecialchars($cre8FrontDisplayName); ?>"><?php echo htmlspecialchars($cre8FrontInitial); ?></span>
  </div>
</header>
