<?php
$cre8BackPath = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');
$cre8BackVuePos = strpos($cre8BackPath, '/Vue/');
$cre8BackBase = $cre8BackVuePos !== false ? substr($cre8BackPath, 0, $cre8BackVuePos) : '';
$cre8BackLogo = $cre8BackBase . '/Vue/public/images/logo.png';
$cre8BackUser = $_SESSION['utilisateur'] ?? [];
$cre8BackName = trim((string) ($cre8BackUser['nom'] ?? 'Administrator'));
$cre8BackInitial = strtoupper(substr($cre8BackName, 0, 1)) ?: 'A';
$cre8BackItems = [
    ['section' => 'DASHBOARD', 'items' => [
        ['label' => 'Home', 'icon' => '⌂', 'href' => $cre8BackBase . '/Vue/BackOffice/offre/index.php', 'active' => false],
        ['label' => 'Users', 'icon' => '♙', 'href' => '#', 'active' => false],
        ['label' => 'Complaints', 'icon' => '□', 'href' => '#', 'active' => false],
    ]],
    ['section' => 'MODULES', 'items' => [
        ['label' => 'Offers & Applications', 'icon' => '▱', 'href' => $cre8BackBase . '/Vue/BackOffice/offre/index.php', 'active' => strpos($cre8BackPath, '/BackOffice/offre/') !== false || strpos($cre8BackPath, '/BackOffice/condidature/') !== false],
        ['label' => 'Events & Forums', 'icon' => '□', 'href' => '#', 'active' => false],
        ['label' => 'Campaigns', 'icon' => '⚡', 'href' => '#', 'active' => false],
        ['label' => 'Products', 'icon' => '◇', 'href' => '#', 'active' => false],
        ['label' => 'Posts & Comments', 'icon' => '▤', 'href' => '#', 'active' => false],
    ]],
];
?>
<aside class="cre8-admin-sidebar" aria-label="BackOffice sidebar">
  <a class="cre8-admin-brand" href="<?php echo htmlspecialchars($cre8BackBase . '/Vue/BackOffice/offre/index.php'); ?>">
    <img src="<?php echo htmlspecialchars($cre8BackLogo); ?>" alt="Cre8Connect logo">
    <span>
      <strong>Cre8Connect</strong>
      <small>ADMIN</small>
    </span>
  </a>

  <nav class="cre8-admin-nav" aria-label="Admin navigation">
    <?php foreach ($cre8BackItems as $cre8BackSection): ?>
      <section class="cre8-admin-nav-section">
        <h2><?php echo htmlspecialchars($cre8BackSection['section']); ?></h2>
        <?php foreach ($cre8BackSection['items'] as $cre8BackItem): ?>
          <a class="cre8-admin-nav-item<?php echo !empty($cre8BackItem['active']) ? ' is-active' : ''; ?>" href="<?php echo htmlspecialchars($cre8BackItem['href']); ?>">
            <span class="cre8-admin-nav-icon"><?php echo htmlspecialchars($cre8BackItem['icon']); ?></span>
            <span><?php echo htmlspecialchars($cre8BackItem['label']); ?></span>
          </a>
        <?php endforeach; ?>
      </section>
    <?php endforeach; ?>
  </nav>

  <div class="cre8-admin-profile">
    <span class="cre8-admin-avatar"><?php echo htmlspecialchars($cre8BackInitial); ?></span>
    <span>
      <strong><?php echo htmlspecialchars($cre8BackName !== '' ? $cre8BackName : 'Administrator'); ?></strong>
      <small>Super Admin</small>
    </span>
  </div>
</aside>
