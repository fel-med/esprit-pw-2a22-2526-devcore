<?php
$cre8BackPath = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');
$cre8BackVuePos = strpos($cre8BackPath, '/Vue/');
$cre8BackBase = $cre8BackVuePos !== false ? substr($cre8BackPath, 0, $cre8BackVuePos) : '';
$cre8BackLogo = $cre8BackBase . '/Vue/public/images/logo.png';
$cre8BackAvatar = $cre8BackBase . '/Vue/public/images/face15.jpg';
$cre8BackCurrentModule = 'dashboard';
if (strpos($cre8BackPath, '/BackOffice/offre/') !== false
    || strpos($cre8BackPath, '/BackOffice/condidature/') !== false
    || strpos($cre8BackPath, '/BackOffice/cre8shield/') !== false
) {
    $cre8BackCurrentModule = 'offers';
} elseif (strpos($cre8BackPath, '/BackOffice/campagne/') !== false) {
    $cre8BackCurrentModule = 'campagne';
} elseif (strpos($cre8BackPath, '/BackOffice/evenement/') !== false || strpos($cre8BackPath, '/BackOffice/event/') !== false) {
    $cre8BackCurrentModule = 'events';
} elseif (strpos($cre8BackPath, '/BackOffice/reclamation/') !== false || strpos($cre8BackPath, '/BackOffice/reclamations/') !== false) {
    $cre8BackCurrentModule = 'reclamations';
} elseif (strpos($cre8BackPath, '/BackOffice/utilisateur/') !== false) {
    $cre8BackCurrentModule = 'dashboard';
}

if (!function_exists('cre8BackIsActivePath')) {
    function cre8BackIsActivePath(string $path, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle !== '' && strpos($path, $needle) !== false) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('cre8BackSidebarIcon')) {
    function cre8BackSidebarIcon(string $name): string
    {
        $icons = [
            'moon' => '<path d="M18.4 14.65A6.85 6.85 0 0 1 9.35 5.6 7.45 7.45 0 1 0 18.4 14.65Z" /><path d="M15.7 3.9 16.35 5.45 18 6l-1.65.55-.65 1.55-.65-1.55L13.4 6l1.65-.55.65-1.55Z" /><path d="m18.6 8.6.35.85.85.35-.85.35-.35.85-.35-.85-.85-.35.85-.35.35-.85Z" />',
            'dashboard' => '<path d="M6.4 14.2a5.6 5.6 0 1 1 11.2 0" /><path d="M8.2 17.1h7.6" /><path d="m12 14.2 3.25-3.35" />',
            'reclamations' => '<path d="M5 7.1h10.4" /><path d="M5 11.2h8.2" /><path d="M5 15.3h6.5" /><path d="m15.1 13.5 4.2 2.7-4.2 2.7v-5.4Z" />',
            'offers' => '<path d="M5 5h4.7v4.7H5V5Zm6.65 0h4.7v4.7h-4.7V5Zm6.65 0H23v4.7h-4.7V5ZM5 11.65h4.7v4.7H5v-4.7Zm6.65 0h4.7v4.7h-4.7v-4.7Zm6.65 0H23v4.7h-4.7v-4.7ZM5 18.3h4.7V23H5v-4.7Zm6.65 0h4.7V23h-4.7v-4.7Zm6.65 0H23V23h-4.7v-4.7Z" />',
            'campagne' => '<path d="M5.2 18.8v-5.7h3.1v5.7H5.2Zm5.4 0V6.2h3.1v12.6h-3.1Zm5.4 0v-8.6h3.1v8.6H16Z" />',
            'events' => '<path d="M7 4.4h10a1.4 1.4 0 0 1 1.4 1.4v13.8H5.6V5.8A1.4 1.4 0 0 1 7 4.4Z" /><path d="M8.8 3.1v3.2M15.2 3.1v3.2M5.6 8.6h12.8" /><path d="M9 12.1h6M9 15h4.1" />',
            'dots' => '<path d="M12 5.5h.01M12 12h.01M12 18.5h.01" />',
        ];

        return '<svg viewBox="0 0 24 24" aria-hidden="true">' . ($icons[$name] ?? $icons['dashboard']) . '</svg>';
    }
}

$cre8BackMenuItems = [
    [
        'label' => 'Dashboard',
        'icon' => 'dashboard',
        'tone' => 'orange',
        'href' => $cre8BackBase . '/Vue/BackOffice/utilisateur/index.php',
        'active' => $cre8BackCurrentModule === 'dashboard',
    ],
    [
        'label' => 'reclamations',
        'icon' => 'reclamations',
        'tone' => 'red',
        'href' => '#',
        'active' => $cre8BackCurrentModule === 'reclamations',
    ],
    [
        'label' => 'offers',
        'icon' => 'offers',
        'tone' => 'blue',
        'href' => $cre8BackBase . '/Vue/BackOffice/offre/index.php',
        'active' => $cre8BackCurrentModule === 'offers',
    ],
    [
        'label' => 'campagne',
        'icon' => 'campagne',
        'tone' => 'green',
        'href' => $cre8BackBase . '/Vue/BackOffice/campagne/index.php',
        'active' => $cre8BackCurrentModule === 'campagne',
    ],
    [
        'label' => 'events',
        'icon' => 'events',
        'tone' => 'purple',
        'href' => $cre8BackBase . '/Vue/BackOffice/evenement/index.php',
        'active' => $cre8BackCurrentModule === 'events',
    ],
];
?>
<aside class="cre8-admin-sidebar" aria-label="BackOffice sidebar">
  <a class="cre8-admin-brand cre8-back-sidebar-logo" href="<?php echo htmlspecialchars($cre8BackBase . '/Vue/BackOffice/utilisateur/index.php'); ?>">
    <span class="cre8-back-logo-word">Cre</span>
    <img src="<?php echo htmlspecialchars($cre8BackLogo); ?>" alt="Cre8Connect logo mark">
    <span class="cre8-back-logo-word cre8-back-logo-word-end">connect</span>
  </a>

  <div class="cre8-back-sidebar-profile">
    <span class="cre8-back-profile-avatar">
      <img src="<?php echo htmlspecialchars($cre8BackAvatar); ?>" alt="Utilisateur avatar">
    </span>
    <span class="cre8-back-profile-copy">
      <strong>Utilisateur</strong>
      <small>Admin</small>
    </span>
    <span class="cre8-back-profile-dots" aria-hidden="true"><?php echo cre8BackSidebarIcon('dots'); ?></span>
  </div>

  <nav class="cre8-admin-nav cre8-back-nav" aria-label="Admin navigation">
    <h2 class="cre8-back-nav-title">Navigation</h2>

    <button type="button" class="cre8-admin-nav-item cre8-back-nav-item cre8-back-theme-toggle" data-theme-toggle data-default-theme="dark">
      <span class="cre8-admin-nav-icon cre8-back-nav-icon" data-theme-toggle-icon aria-hidden="true"><?php echo cre8BackSidebarIcon('moon'); ?></span>
      <span>Mode jour / nuit</span>
    </button>

    <?php foreach ($cre8BackMenuItems as $cre8BackItem): ?>
      <a
        class="cre8-admin-nav-item cre8-back-nav-item<?php echo !empty($cre8BackItem['active']) ? ' is-active' : ''; ?>"
        href="<?php echo htmlspecialchars($cre8BackItem['href']); ?>"
      >
        <span class="cre8-admin-nav-icon cre8-back-nav-icon cre8-back-icon-<?php echo htmlspecialchars($cre8BackItem['tone']); ?>"><?php echo cre8BackSidebarIcon($cre8BackItem['icon']); ?></span>
        <span><?php echo htmlspecialchars($cre8BackItem['label']); ?></span>
      </a>
    <?php endforeach; ?>
  </nav>
</aside>

<script>
(() => {
  const storageKey = 'cre8connect_theme';
  const themeButtons = document.querySelectorAll('[data-theme-toggle]');
  if (!themeButtons.length) {
    return;
  }

  const normalizeTheme = (theme, fallback) => theme === 'dark' || theme === 'light' ? theme : fallback;
  const moonIcon = '<?php echo addslashes(cre8BackSidebarIcon('moon')); ?>';
  const sunIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4V2M12 22v-2M4.9 4.9 3.5 3.5M20.5 20.5l-1.4-1.4M4 12H2M22 12h-2M4.9 19.1l-1.4 1.4M20.5 3.5l-1.4 1.4" /><path d="M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z" /></svg>';

  const applyTheme = (theme, fallback = 'dark') => {
    const normalized = normalizeTheme(theme, fallback);
    document.documentElement.setAttribute('data-theme', normalized);
    themeButtons.forEach((button) => {
      const icon = button.querySelector('[data-theme-toggle-icon]');
      if (icon) {
        icon.innerHTML = normalized === 'dark' ? moonIcon : sunIcon;
      }
      button.setAttribute('aria-label', normalized === 'dark' ? 'Switch to light mode' : 'Switch to dark mode');
    });
  };

  themeButtons.forEach((button) => {
    if (button.dataset.themeReady === '1') {
      return;
    }
    button.dataset.themeReady = '1';
    const defaultTheme = normalizeTheme(button.dataset.defaultTheme, 'dark');
    button.addEventListener('click', () => {
      const currentTheme = normalizeTheme(document.documentElement.getAttribute('data-theme'), defaultTheme);
      const nextTheme = currentTheme === 'dark' ? 'light' : 'dark';
      localStorage.setItem(storageKey, nextTheme);
      applyTheme(nextTheme, defaultTheme);
    });
  });

  applyTheme(normalizeTheme(localStorage.getItem(storageKey), 'dark'), 'dark');
})();
</script>
