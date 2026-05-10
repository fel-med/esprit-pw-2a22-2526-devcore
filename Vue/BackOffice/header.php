<?php
$cre8connectBackHeaderCurrentPath = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');
$cre8connectBackHeaderVuePos = strpos($cre8connectBackHeaderCurrentPath, '/Vue/');
$cre8connectBackHeaderBase = $cre8connectBackHeaderVuePos !== false
    ? substr($cre8connectBackHeaderCurrentPath, 0, $cre8connectBackHeaderVuePos)
    : '';

$cre8connectBackHeaderSessionUser = $_SESSION['utilisateur'] ?? [];
$cre8connectBackHeaderProfileName = trim((string) ($cre8connectBackHeaderSessionUser['nom'] ?? ''));
if ($cre8connectBackHeaderProfileName === '') {
    $cre8connectBackHeaderProfileName = trim((string) ($cre8connectBackHeaderSessionUser['email'] ?? 'Admin'));
}

$cre8connectBackHeaderLinks = [
    [
        'label' => 'Offer dashboard',
        'href' => $cre8connectBackHeaderBase . '/Vue/BackOffice/offre/index.php',
        'active' => strpos($cre8connectBackHeaderCurrentPath, '/BackOffice/offre/') !== false,
    ],
    [
        'label' => 'Candidature dashboard',
        'href' => $cre8connectBackHeaderBase . '/Vue/BackOffice/condidature/index.php',
        'active' => strpos($cre8connectBackHeaderCurrentPath, '/BackOffice/condidature/') !== false,
    ],
];
?>
<style>
  .cre8connect-admin-temp-header {
    width: min(1380px, calc(100vw - 2rem));
    margin: 1rem auto 0;
    padding: 1rem 1.15rem;
    border-radius: 1.25rem;
    border: 1px solid rgba(59, 130, 246, 0.14);
    background:
      radial-gradient(circle at top right, rgba(56, 189, 248, 0.08), transparent 28%),
      linear-gradient(180deg, rgba(15, 23, 42, 0.96), rgba(17, 24, 39, 0.98));
    box-shadow: 0 24px 56px rgba(2, 6, 23, 0.34);
  }

  .cre8connect-admin-temp-header__inner {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 1rem 1.4rem;
  }

  .cre8connect-admin-temp-header__eyebrow {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.34rem 0.74rem;
    border-radius: 999px;
    background: rgba(37, 99, 235, 0.16);
    color: #bfdbfe;
    font-size: 0.74rem;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
  }

  .cre8connect-admin-temp-header__title {
    margin: 0.55rem 0 0.15rem;
    color: #f8fafc;
    font-size: 1.14rem;
    font-weight: 800;
  }

  .cre8connect-admin-temp-header__copy {
    margin: 0;
    color: #94a3b8;
    font-size: 0.93rem;
    line-height: 1.65;
  }

  .cre8connect-admin-temp-header__meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.55rem;
    margin-top: 0.7rem;
  }

  .cre8connect-admin-temp-header__chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.4rem 0.8rem;
    border-radius: 999px;
    border: 1px solid rgba(51, 65, 85, 0.95);
    background: rgba(15, 23, 42, 0.84);
    color: #cbd5e1;
    font-size: 0.8rem;
    font-weight: 700;
  }

  .cre8connect-admin-temp-header__nav {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.36rem;
    border-radius: 999px;
    background: rgba(15, 23, 42, 0.86);
    border: 1px solid rgba(51, 65, 85, 0.9);
  }

  .cre8connect-admin-temp-header__link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 156px;
    padding: 0.72rem 1.05rem;
    border-radius: 999px;
    color: #94a3b8;
    font-size: 0.9rem;
    font-weight: 700;
    text-decoration: none;
    transition: background 0.18s ease, color 0.18s ease, transform 0.18s ease, box-shadow 0.18s ease;
  }

  .cre8connect-admin-temp-header__link:hover {
    color: #e2e8f0;
    background: rgba(37, 99, 235, 0.12);
    transform: translateY(-1px);
  }

  .cre8connect-admin-temp-header__link.is-active {
    background: linear-gradient(135deg, rgba(37, 99, 235, 0.96), rgba(14, 116, 144, 0.94));
    color: #ffffff;
    box-shadow: 0 14px 28px rgba(37, 99, 235, 0.24);
  }

  @media (max-width: 768px) {
    .cre8connect-admin-temp-header {
      width: min(1380px, calc(100vw - 1rem));
      margin-top: 0.6rem;
      padding: 0.9rem;
    }

    .cre8connect-admin-temp-header__nav {
      width: 100%;
      flex-wrap: wrap;
      border-radius: 1rem;
    }

    .cre8connect-admin-temp-header__link {
      flex: 1 1 200px;
    }
  }
</style>

<header class="cre8connect-admin-temp-header" role="banner">
  <div class="cre8connect-admin-temp-header__inner">
    <div>
      <span class="cre8connect-admin-temp-header__eyebrow">Temporary admin header</span>
      <h2 class="cre8connect-admin-temp-header__title">BackOffice module switcher</h2>
      <p class="cre8connect-admin-temp-header__copy">Move between the offer and candidature dashboards from one temporary header until the shared team header is ready.</p>
      <div class="cre8connect-admin-temp-header__meta">
        <span class="cre8connect-admin-temp-header__chip"><?php echo htmlspecialchars($cre8connectBackHeaderProfileName); ?></span>
        <span class="cre8connect-admin-temp-header__chip">Admin</span>
      </div>
    </div>

    <nav class="cre8connect-admin-temp-header__nav" aria-label="BackOffice module switcher">
      <?php foreach ($cre8connectBackHeaderLinks as $cre8connectBackHeaderLink): ?>
        <a
          class="cre8connect-admin-temp-header__link<?php echo !empty($cre8connectBackHeaderLink['active']) ? ' is-active' : ''; ?>"
          href="<?php echo htmlspecialchars($cre8connectBackHeaderLink['href']); ?>"
        >
          <?php echo htmlspecialchars($cre8connectBackHeaderLink['label']); ?>
        </a>
      <?php endforeach; ?>
    </nav>
  </div>
</header>
