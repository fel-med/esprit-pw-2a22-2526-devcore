<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../layout/early-theme.php';
require_once __DIR__ . '/../../../Controleur/session_helper.php';

cc_require_admin('../../FrontOffice/utilisateur/login.php');

$backActive = 'dashboard';

function backAssetVersion(string $absolutePath): string
{
    return file_exists($absolutePath) ? '?v=' . urlencode((string) filemtime($absolutePath)) : '';
}

$stats = [
    'total' => 0,
    'admin' => 0,
    'createur' => 0,
    'marque' => 0,
    'actif' => 0,
    'suspendu' => 0,
];

$utilisateurControllerPath = __DIR__ . '/../../../Controleur/utilisateurC.php';
if (file_exists($utilisateurControllerPath)) {
    require_once $utilisateurControllerPath;
    if (class_exists('UtilisateurC')) {
        try {
            $userC = new UtilisateurC();
            if (method_exists($userC, 'getStatistiquesUtilisateurs')) {
                $controllerStats = $userC->getStatistiquesUtilisateurs();
                if (is_array($controllerStats)) {
                    $stats = array_merge($stats, array_intersect_key($controllerStats, $stats));
                }
            }
        } catch (Throwable $e) {
            // Keep the dashboard available even if statistics cannot be loaded.
        }
    }
}

$adminName = $_SESSION['user']['nom'] ?? ($_SESSION['utilisateur']['nom'] ?? ($_SESSION['nom'] ?? 'Admin'));
$adminName = trim((string) $adminName) ?: 'Admin';

$modules = [
    [
        'label' => 'Users',
        'description' => 'Manage admins, creators and brands.',
        'href' => '../utilisateur/index.php',
        'icon' => 'mdi-account-multiple',
        'accent' => 'violet',
        'count' => (int) ($stats['total'] ?? 0),
    ],
    [
        'label' => 'Reclamations',
        'description' => 'Review user reports and support requests.',
        'href' => '../utilisateur/reclamations.php',
        'icon' => 'mdi-playlist-play',
        'accent' => 'rose',
        'count' => null,
    ],
    [
        'label' => 'Collaborations',
        'description' => 'Control offers and candidatures workflow.',
        'href' => '../offre/index.php',
        'icon' => 'mdi-briefcase-check',
        'accent' => 'blue',
        'count' => null,
    ],
    [
        'label' => 'Campaigns',
        'description' => 'Follow campaign content and brand activity.',
        'href' => '../campagne/index.php',
        'icon' => 'mdi-chart-bar',
        'accent' => 'green',
        'count' => null,
    ],
    [
        'label' => 'Products',
        'description' => 'Monitor product catalog and availability.',
        'href' => '../produit/index.php',
        'icon' => 'mdi-cube-outline',
        'accent' => 'cyan',
        'count' => null,
    ],
    [
        'label' => 'Contracts',
        'description' => 'Track agreements between brands and creators.',
        'href' => '../contrat/index.php',
        'icon' => 'mdi-file-document-outline',
        'accent' => 'amber',
        'count' => null,
    ],
    [
        'label' => 'Posts',
        'description' => 'Moderate creator publications.',
        'href' => '../post/index.php',
        'icon' => 'mdi-format-list-bulleted',
        'accent' => 'red',
        'count' => null,
    ],
    [
        'label' => 'Comments',
        'description' => 'Review community comments and interactions.',
        'href' => '../comment/index.php',
        'icon' => 'mdi-comment-text-outline',
        'accent' => 'indigo',
        'count' => null,
    ],
    [
        'label' => 'Events',
        'description' => 'Prepare event administration after merge.',
        'href' => '../evenement/index.php',
        'icon' => 'mdi-calendar-check',
        'accent' => 'emerald',
        'count' => null,
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php cre8_bo_early_theme_print_head_script(); ?>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Cre8connect Admin Dashboard</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../utilisateur/assets/vendors/mdi/css/materialdesignicons.min.css<?php echo backAssetVersion(__DIR__ . '/../utilisateur/assets/vendors/mdi/css/materialdesignicons.min.css'); ?>">
  <link rel="stylesheet" href="../utilisateur/assets/vendors/css/vendor.bundle.base.css<?php echo backAssetVersion(__DIR__ . '/../utilisateur/assets/vendors/css/vendor.bundle.base.css'); ?>">
  <link rel="stylesheet" href="../utilisateur/assets/css/style.css<?php echo backAssetVersion(__DIR__ . '/../utilisateur/assets/css/style.css'); ?>">
  <link rel="stylesheet" href="../layout/back-layout.css<?php echo backAssetVersion(__DIR__ . '/../layout/back-layout.css'); ?>">

  <style>
    .admin-dashboard-shell {
      min-height: 100%;
      padding: 1.65rem 1.75rem 2.25rem;
      background:
        radial-gradient(circle at top right, rgba(124, 92, 255, 0.16), transparent 34%),
        radial-gradient(circle at top left, rgba(0, 144, 231, 0.10), transparent 28%),
        #0f1117;
    }

    .admin-dashboard-hero {
      display: grid;
      grid-template-columns: minmax(0, 1fr) auto;
      gap: 1.4rem;
      align-items: center;
      margin-bottom: 1.35rem;
      padding: 1.45rem;
      border: 1px solid rgba(148, 163, 184, 0.14);
      border-radius: 1rem;
      background: linear-gradient(135deg, rgba(24, 24, 32, 0.98), rgba(18, 20, 30, 0.94));
      box-shadow: 0 18px 42px rgba(0, 0, 0, 0.22);
    }

    .admin-dashboard-kicker {
      display: inline-flex;
      align-items: center;
      gap: 0.45rem;
      margin-bottom: 0.75rem;
      padding: 0.35rem 0.75rem;
      border-radius: 999px;
      background: rgba(124, 92, 255, 0.15);
      color: #c4b5fd;
      font-size: 0.74rem;
      font-weight: 900;
      letter-spacing: 0.06em;
      text-transform: uppercase;
    }

    .admin-dashboard-hero h1 {
      margin: 0;
      color: #f8fafc;
      font-size: clamp(1.75rem, 3vw, 2.75rem);
      font-weight: 900;
      letter-spacing: -0.055em;
      line-height: 1.05;
    }

    .admin-dashboard-hero p {
      max-width: 58rem;
      margin: 0.8rem 0 0;
      color: #aeb7cf;
      font-size: 1rem;
      line-height: 1.65;
    }

    .admin-dashboard-hero-badge {
      min-width: 190px;
      padding: 1rem;
      border-radius: 0.9rem;
      background: rgba(124, 92, 255, 0.14);
      border: 1px solid rgba(167, 139, 250, 0.18);
      color: #eef2ff;
      text-align: center;
    }

    .admin-dashboard-hero-badge strong {
      display: block;
      font-size: 1.9rem;
      line-height: 1;
    }

    .admin-dashboard-hero-badge span {
      display: block;
      margin-top: 0.45rem;
      color: #aeb7cf;
      font-size: 0.82rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    .admin-dashboard-stats {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 1rem;
      margin-bottom: 1.25rem;
    }

    .admin-dashboard-stat,
    .admin-dashboard-card,
    .admin-dashboard-panel {
      border: 1px solid rgba(148, 163, 184, 0.14);
      border-radius: 0.92rem;
      background: #181820;
      box-shadow: 0 16px 34px rgba(0, 0, 0, 0.18);
    }

    .admin-dashboard-stat {
      padding: 1rem;
      overflow: hidden;
      position: relative;
    }

    .admin-dashboard-stat::after {
      content: "";
      position: absolute;
      right: -32px;
      top: -32px;
      width: 86px;
      height: 86px;
      border-radius: 999px;
      background: rgba(124, 92, 255, 0.16);
    }

    .admin-dashboard-stat span {
      color: #94a3b8;
      font-size: 0.75rem;
      font-weight: 900;
      letter-spacing: 0.055em;
      text-transform: uppercase;
    }

    .admin-dashboard-stat strong {
      display: block;
      margin-top: 0.55rem;
      color: #ffffff;
      font-size: 1.8rem;
      line-height: 1;
      font-weight: 900;
    }

    .admin-dashboard-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 1rem;
    }

    .admin-dashboard-card {
      display: flex;
      gap: 0.9rem;
      align-items: flex-start;
      min-height: 150px;
      padding: 1rem;
      color: #e5e7eb;
      text-decoration: none;
      transition: transform 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;
    }

    .admin-dashboard-card:hover {
      transform: translateY(-3px);
      border-color: rgba(124, 92, 255, 0.48);
      color: #ffffff;
      box-shadow: 0 22px 44px rgba(0, 0, 0, 0.26);
      text-decoration: none;
    }

    .admin-dashboard-card-icon {
      width: 48px;
      height: 48px;
      flex: 0 0 auto;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 0.85rem;
      font-size: 1.35rem;
      color: #ffffff;
      background: #7c5cff;
      box-shadow: 0 12px 24px rgba(124, 92, 255, 0.22);
    }

    .admin-dashboard-card h2 {
      margin: 0;
      color: #f8fafc;
      font-size: 1.05rem;
      font-weight: 900;
    }

    .admin-dashboard-card p {
      margin: 0.42rem 0 0;
      color: #9ca8bf;
      font-size: 0.9rem;
      line-height: 1.5;
    }

    .admin-dashboard-card-count {
      display: inline-flex;
      margin-top: 0.72rem;
      padding: 0.24rem 0.58rem;
      border-radius: 999px;
      background: rgba(124, 92, 255, 0.14);
      color: #c4b5fd;
      font-size: 0.74rem;
      font-weight: 900;
    }

    .accent-rose { background: #d9468a; }
    .accent-blue { background: #0090e7; }
    .accent-green { background: #22c55e; }
    .accent-cyan { background: #06b6d4; }
    .accent-amber { background: #f59e0b; }
    .accent-red { background: #ef4444; }
    .accent-indigo { background: #6366f1; }
    .accent-emerald { background: #10b981; }

    .admin-dashboard-panel {
      margin-top: 1rem;
      padding: 1.1rem 1.25rem;
    }

    .admin-dashboard-panel h2 {
      margin: 0 0 0.65rem;
      color: #f8fafc;
      font-size: 1.1rem;
      font-weight: 900;
    }

    .admin-dashboard-panel p {
      margin: 0;
      color: #94a3b8;
      line-height: 1.65;
    }

    html[data-theme="light"] .admin-dashboard-shell,
    body.light-mode .admin-dashboard-shell {
      background:
        radial-gradient(circle at top right, rgba(124, 92, 255, 0.12), transparent 34%),
        radial-gradient(circle at top left, rgba(0, 144, 231, 0.08), transparent 28%),
        #f6f7fb;
    }

    html[data-theme="light"] .admin-dashboard-hero,
    body.light-mode .admin-dashboard-hero,
    html[data-theme="light"] .admin-dashboard-stat,
    body.light-mode .admin-dashboard-stat,
    html[data-theme="light"] .admin-dashboard-card,
    body.light-mode .admin-dashboard-card,
    html[data-theme="light"] .admin-dashboard-panel,
    body.light-mode .admin-dashboard-panel {
      background: #ffffff;
      border-color: rgba(30, 48, 243, 0.10);
      box-shadow: 0 16px 34px rgba(15, 23, 42, 0.07);
    }

    html[data-theme="light"] .admin-dashboard-hero h1,
    body.light-mode .admin-dashboard-hero h1,
    html[data-theme="light"] .admin-dashboard-card h2,
    body.light-mode .admin-dashboard-card h2,
    html[data-theme="light"] .admin-dashboard-panel h2,
    body.light-mode .admin-dashboard-panel h2,
    html[data-theme="light"] .admin-dashboard-stat strong,
    body.light-mode .admin-dashboard-stat strong {
      color: #111827;
    }

    html[data-theme="light"] .admin-dashboard-hero p,
    body.light-mode .admin-dashboard-hero p,
    html[data-theme="light"] .admin-dashboard-card p,
    body.light-mode .admin-dashboard-card p,
    html[data-theme="light"] .admin-dashboard-panel p,
    body.light-mode .admin-dashboard-panel p {
      color: #64748b;
    }

    @media (max-width: 1180px) {
      .admin-dashboard-stats,
      .admin-dashboard-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }
    }

    @media (max-width: 680px) {
      .admin-dashboard-shell {
        padding: 1rem;
      }

      .admin-dashboard-hero {
        grid-template-columns: 1fr;
      }

      .admin-dashboard-stats,
      .admin-dashboard-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
<link rel="icon" type="image/png" sizes="16x16" href="../../public/images/favicon-16.png">
<link rel="icon" type="image/png" sizes="32x32" href="../../public/images/favicon-32.png">
<link rel="shortcut icon" type="image/png" href="../../public/images/favicon-32.png">
<link rel="apple-touch-icon" sizes="180x180" href="../../public/images/apple-touch-icon.png">
</head>

<body class="cre8-admin-layout"><?php cre8_bo_early_theme_print_body_script(); ?>

  <div class="container-scroller cre8-admin-page">
    <?php require_once __DIR__ . '/../layout/sidebar.php'; ?>

    <div class="container-fluid page-body-wrapper cre8-admin-main">
      <?php require_once __DIR__ . '/../layout/header.php'; ?>

      <div class="main-panel">
        <div class="content-wrapper admin-dashboard-shell">
          <section class="admin-dashboard-hero">
            <div>
              <span class="admin-dashboard-kicker"><i class="mdi mdi-view-dashboard-outline"></i> Admin dashboard</span>
              <h1>Welcome, <?php echo htmlspecialchars($adminName); ?>.</h1>
              <p>Manage Cre8connect from one place. Use this page as the neutral BackOffice home for users, collaborations, campaigns, products, contracts, posts, comments, events and reports.</p>
            </div>
            <div class="admin-dashboard-hero-badge">
              <strong><?php echo (int) ($stats['total'] ?? 0); ?></strong>
              <span>Total accounts</span>
            </div>
          </section>

          <section class="admin-dashboard-stats" aria-label="BackOffice statistics">
            <div class="admin-dashboard-stat">
              <span>Administrators</span>
              <strong><?php echo (int) ($stats['admin'] ?? 0); ?></strong>
            </div>
            <div class="admin-dashboard-stat">
              <span>Creators</span>
              <strong><?php echo (int) ($stats['createur'] ?? 0); ?></strong>
            </div>
            <div class="admin-dashboard-stat">
              <span>Brands</span>
              <strong><?php echo (int) ($stats['marque'] ?? 0); ?></strong>
            </div>
            <div class="admin-dashboard-stat">
              <span>Suspended</span>
              <strong><?php echo (int) ($stats['suspendu'] ?? 0); ?></strong>
            </div>
          </section>

          <section class="admin-dashboard-grid" aria-label="BackOffice modules">
            <?php foreach ($modules as $module): ?>
              <a class="admin-dashboard-card" href="<?php echo htmlspecialchars($module['href']); ?>">
                <span class="admin-dashboard-card-icon accent-<?php echo htmlspecialchars($module['accent']); ?>">
                  <i class="mdi <?php echo htmlspecialchars($module['icon']); ?>"></i>
                </span>
                <span>
                  <h2><?php echo htmlspecialchars($module['label']); ?></h2>
                  <p><?php echo htmlspecialchars($module['description']); ?></p>
                  <?php if ($module['count'] !== null): ?>
                    <span class="admin-dashboard-card-count"><?php echo (int) $module['count']; ?> records</span>
                  <?php endif; ?>
                </span>
              </a>
            <?php endforeach; ?>
          </section>

          <section class="admin-dashboard-panel">
            <h2>BackOffice migration status</h2>
            <p>This dashboard uses the shared BackOffice sidebar and header. Keep migrating the remaining BackOffice modules batch by batch, then test each module in both dark mode and light mode.</p>
          </section>
        </div>
      </div>
    </div>
  </div>

  <script src="../utilisateur/assets/vendors/js/vendor.bundle.base.js<?php echo backAssetVersion(__DIR__ . '/../utilisateur/assets/vendors/js/vendor.bundle.base.js'); ?>"></script>
  <script src="../layout/back-layout.js<?php echo backAssetVersion(__DIR__ . '/../layout/back-layout.js'); ?>"></script>
  <script src="../utilisateur/assets/js/off-canvas.js<?php echo backAssetVersion(__DIR__ . '/../utilisateur/assets/js/off-canvas.js'); ?>"></script>
  <script src="../utilisateur/assets/js/hoverable-collapse.js<?php echo backAssetVersion(__DIR__ . '/../utilisateur/assets/js/hoverable-collapse.js'); ?>"></script>
  <script src="../utilisateur/assets/js/misc.js<?php echo backAssetVersion(__DIR__ . '/../utilisateur/assets/js/misc.js'); ?>"></script>
  <script src="../utilisateur/assets/js/settings.js<?php echo backAssetVersion(__DIR__ . '/../utilisateur/assets/js/settings.js'); ?>"></script>
  <script src="../utilisateur/assets/js/todolist.js<?php echo backAssetVersion(__DIR__ . '/../utilisateur/assets/js/todolist.js'); ?>"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
