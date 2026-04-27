<?php
$cre8connectFrontHeaderCurrentPath = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');
$cre8connectFrontHeaderVuePos = strpos($cre8connectFrontHeaderCurrentPath, '/Vue/');
$cre8connectFrontHeaderBase = $cre8connectFrontHeaderVuePos !== false
    ? substr($cre8connectFrontHeaderCurrentPath, 0, $cre8connectFrontHeaderVuePos)
    : '';

$cre8connectFrontHeaderSessionUser = $_SESSION['utilisateur'] ?? [];
$cre8connectFrontHeaderRole = strtolower(trim((string) ($cre8connectFrontHeaderSessionUser['role'] ?? '')));
$cre8connectFrontHeaderRoleLabel = match ($cre8connectFrontHeaderRole) {
    'marque' => 'Brand workspace',
    'createur' => 'Creator workspace',
    'admin' => 'Admin bridge',
    default => 'FrontOffice workspace',
};
$cre8connectFrontHeaderSubtitle = match ($cre8connectFrontHeaderRole) {
    'marque' => 'Switch between offer management and candidature review while the shared header is still temporary.',
    'createur' => 'Move between your targeted offers and candidature responses without leaving the module.',
    'admin' => 'Quick bridge to the admin dashboards while the final shared header is still being prepared.',
    default => 'Use this temporary switcher to move between the module workspaces.',
};

$cre8connectFrontHeaderProfileName = trim((string) ($cre8connectFrontHeaderSessionUser['nom'] ?? ''));
if ($cre8connectFrontHeaderProfileName === '') {
    $cre8connectFrontHeaderProfileName = trim((string) ($cre8connectFrontHeaderSessionUser['email'] ?? 'Current user'));
}

$cre8connectFrontHeaderLinks = match ($cre8connectFrontHeaderRole) {
    'marque' => [
        [
            'label' => 'Offers',
            'href' => $cre8connectFrontHeaderBase . '/Vue/FrontOffice/offre/brand_index.php',
            'active' => strpos($cre8connectFrontHeaderCurrentPath, '/FrontOffice/offre/') !== false,
        ],
        [
            'label' => 'Candidatures',
            'href' => $cre8connectFrontHeaderBase . '/Vue/FrontOffice/condidature/brand_index.php',
            'active' => strpos($cre8connectFrontHeaderCurrentPath, '/FrontOffice/condidature/') !== false,
        ],
    ],
    'createur' => [
        [
            'label' => 'Offers',
            'href' => $cre8connectFrontHeaderBase . '/Vue/FrontOffice/offre/creator_list.php',
            'active' => strpos($cre8connectFrontHeaderCurrentPath, '/FrontOffice/offre/') !== false,
        ],
        [
            'label' => 'Candidatures',
            'href' => $cre8connectFrontHeaderBase . '/Vue/FrontOffice/condidature/index.php',
            'active' => strpos($cre8connectFrontHeaderCurrentPath, '/FrontOffice/condidature/') !== false,
        ],
    ],
    'admin' => [
        [
            'label' => 'Admin offers',
            'href' => $cre8connectFrontHeaderBase . '/Vue/BackOffice/offre/index.php',
            'active' => false,
        ],
        [
            'label' => 'Admin candidatures',
            'href' => $cre8connectFrontHeaderBase . '/Vue/BackOffice/condidature/index.php',
            'active' => false,
        ],
    ],
    default => [
        [
            'label' => 'Module login',
            'href' => $cre8connectFrontHeaderBase . '/Vue/FrontOffice/offre/login.php',
            'active' => strpos($cre8connectFrontHeaderCurrentPath, '/FrontOffice/offre/login.php') !== false,
        ],
    ],
};
?>
<style>
  .cre8connect-temp-header {
    width: min(1280px, calc(100vw - 2rem));
    margin: 1.15rem auto 0;
    padding: 1rem 1.1rem;
    border-radius: 1.35rem;
    border: 1px solid rgba(99, 102, 241, 0.14);
    background:
      radial-gradient(circle at top right, rgba(236, 72, 153, 0.09), transparent 28%),
      radial-gradient(circle at left bottom, rgba(59, 130, 246, 0.08), transparent 26%),
      linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(247, 249, 255, 0.98));
    box-shadow: 0 22px 48px rgba(20, 31, 80, 0.08);
  }

  .cre8connect-temp-header__inner {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 1rem 1.4rem;
  }

  .cre8connect-temp-header__eyebrow {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.38rem 0.78rem;
    border-radius: 999px;
    background: rgba(30, 48, 243, 0.08);
    color: #3347cc;
    font-size: 0.76rem;
    font-weight: 800;
    letter-spacing: 0.05em;
    text-transform: uppercase;
  }

  .cre8connect-temp-header__title {
    margin: 0.6rem 0 0.15rem;
    color: #162033;
    font-size: 1.18rem;
    font-weight: 800;
  }

  .cre8connect-temp-header__copy {
    margin: 0;
    color: #667085;
    font-size: 0.94rem;
    line-height: 1.65;
  }

  .cre8connect-temp-header__meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.55rem;
    margin-top: 0.7rem;
  }

  .cre8connect-temp-header__chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.42rem 0.82rem;
    border-radius: 999px;
    border: 1px solid rgba(148, 163, 184, 0.22);
    background: rgba(255, 255, 255, 0.88);
    color: #344054;
    font-size: 0.82rem;
    font-weight: 700;
  }

  .cre8connect-temp-header__nav {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.38rem;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.88);
    border: 1px solid rgba(148, 163, 184, 0.18);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.78);
  }

  .cre8connect-temp-header__link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 122px;
    padding: 0.72rem 1.05rem;
    border-radius: 999px;
    color: #4a5877;
    font-size: 0.92rem;
    font-weight: 700;
    text-decoration: none;
    transition: background 0.18s ease, color 0.18s ease, transform 0.18s ease, box-shadow 0.18s ease;
  }

  .cre8connect-temp-header__link:hover {
    color: #1d2a44;
    background: rgba(30, 48, 243, 0.06);
    transform: translateY(-1px);
  }

  .cre8connect-temp-header__link.is-active {
    background: linear-gradient(135deg, #3142f7, #5c37d7);
    color: #ffffff;
    box-shadow: 0 14px 28px rgba(49, 66, 247, 0.22);
  }

  @media (max-width: 768px) {
    .cre8connect-temp-header {
      width: min(1280px, calc(100vw - 1rem));
      margin-top: 0.6rem;
      padding: 0.9rem;
    }

    .cre8connect-temp-header__nav {
      width: 100%;
      flex-wrap: wrap;
      border-radius: 1.15rem;
    }

    .cre8connect-temp-header__link {
      flex: 1 1 180px;
    }
  }
</style>

<header class="cre8connect-temp-header" role="banner">
  <div class="cre8connect-temp-header__inner">
    <div>
      <span class="cre8connect-temp-header__eyebrow">Temporary module header</span>
      <h2 class="cre8connect-temp-header__title"><?php echo htmlspecialchars($cre8connectFrontHeaderRoleLabel); ?></h2>
      <p class="cre8connect-temp-header__copy"><?php echo htmlspecialchars($cre8connectFrontHeaderSubtitle); ?></p>
      <div class="cre8connect-temp-header__meta">
        <span class="cre8connect-temp-header__chip"><?php echo htmlspecialchars($cre8connectFrontHeaderProfileName); ?></span>
        <?php if ($cre8connectFrontHeaderRole !== ''): ?>
          <span class="cre8connect-temp-header__chip"><?php echo htmlspecialchars(ucfirst($cre8connectFrontHeaderRole)); ?></span>
        <?php endif; ?>
      </div>
    </div>

    <nav class="cre8connect-temp-header__nav" aria-label="FrontOffice module switcher">
      <?php foreach ($cre8connectFrontHeaderLinks as $cre8connectFrontHeaderLink): ?>
        <a
          class="cre8connect-temp-header__link<?php echo !empty($cre8connectFrontHeaderLink['active']) ? ' is-active' : ''; ?>"
          href="<?php echo htmlspecialchars($cre8connectFrontHeaderLink['href']); ?>"
        >
          <?php echo htmlspecialchars($cre8connectFrontHeaderLink['label']); ?>
        </a>
      <?php endforeach; ?>
    </nav>
  </div>
</header>
