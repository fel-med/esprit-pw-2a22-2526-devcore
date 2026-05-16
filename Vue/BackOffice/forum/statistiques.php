<?php
// ── Compute backoffice asset paths before any HTML output ──────────────────
require_once __DIR__ . '/../layout/bo_paths.php';

if (!isset($stats)) {
    $stats = ['total_forums'=>0,'total_messages'=>0,'forums_actifs'=>0,'messages_signales'=>0,'top_forums'=>[],'top_contributeurs'=>[],'months_labels'=>[],'messages_data'=>[]];
}
$BASE = rtrim(str_replace('\\','/',dirname(dirname($_SERVER['SCRIPT_NAME']))),'/');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <?php require_once __DIR__ . '/../layout/early-theme.php'; cre8_bo_early_theme_print_head_script(); ?>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>cre8connect Admin – Statistiques Forums</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= $backBoUtilisateurWeb ?? '../utilisateur' ?>/assets/vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="<?= $backBoUtilisateurWeb ?? '../utilisateur' ?>/assets/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="<?= $backBoUtilisateurWeb ?? '../utilisateur' ?>/assets/css/style.css">
  <link rel="stylesheet" href="<?= ($backBoRootWeb ?? '..') ?>/layout/back-layout.css?v=<?php echo urlencode((string)filemtime(__DIR__.'/../layout/back-layout.css')); ?>">
  <link rel="icon" type="image/png" sizes="16x16" href="<?= htmlspecialchars(dirname($backBoRootWeb) . '/public/images/favicon-16.png') ?>">
  <link rel="icon" type="image/png" sizes="32x32" href="<?= htmlspecialchars(dirname($backBoRootWeb) . '/public/images/favicon-32.png') ?>">
  <link rel="shortcut icon" type="image/png" href="<?= htmlspecialchars(dirname($backBoRootWeb) . '/public/images/favicon-32.png') ?>">
  <link rel="apple-touch-icon" sizes="180x180" href="<?= htmlspecialchars(dirname($backBoRootWeb) . '/public/images/apple-touch-icon.png') ?>">
  <style>
    body.light-mode { background-color:#f8fafc!important; color:#111827!important; }
    body.light-mode .container-scroller,body.light-mode .page-body-wrapper,body.light-mode .main-panel,body.light-mode .content-wrapper { background-color:#ffffff!important; color:#111827!important; }
    body.light-mode .card,body.light-mode .card-body { background-color:#ffffff!important; color:#111827!important; border-color:#d1d5db!important; }
    body.light-mode .card-title { color:#111827!important; }
    body.light-mode .table thead,body.light-mode .table thead th { background:#f3f0ff!important; color:#5b4fff!important; border-color:#e5e7eb!important; }
    body.light-mode .table tbody tr,body.light-mode .table tbody td { background:#ffffff!important; color:#111827!important; border-color:#e5e7eb!important; }
    body.light-mode .table tbody tr:hover,body.light-mode .table tbody tr:hover td { background:#f8fafc!important; }
    body:not(.light-mode) { background-color:#0f131d!important; color:#e7ebff!important; }
    body:not(.light-mode) .container-scroller,body:not(.light-mode) .page-body-wrapper,body:not(.light-mode) .main-panel,body:not(.light-mode) .content-wrapper { background-color:#101520!important; color:#e7ebff!important; }
    body:not(.light-mode) .card,body:not(.light-mode) .card-body { background-color:rgba(18,24,41,0.96)!important; color:#e7ebff!important; border-color:rgba(255,255,255,0.08)!important; box-shadow:0 18px 45px rgba(0,0,0,0.18); }
    body:not(.light-mode) .card-title,body:not(.light-mode) .table thead th,body:not(.light-mode) .table td,body:not(.light-mode) .table th { color:#eef3ff!important; }
    body:not(.light-mode) .table thead { background-color:rgba(255,255,255,0.05)!important; }
    body:not(.light-mode) .page-header .page-title { color:#f3f7ff!important; }
  </style>
</head>
<body class="cre8-admin-layout"><?php cre8_bo_early_theme_print_body_script(); ?>
<div class="container-scroller cre8-admin-page">
  <?php $backActive = 'forum'; require_once __DIR__ . '/../layout/sidebar.php'; ?>
  <div class="container-fluid page-body-wrapper cre8-admin-main">
    <?php require_once __DIR__ . '/../layout/header.php'; ?>
    <div class="main-panel">
      <div class="content-wrapper">
     
        <!-- Page header -->
        <div class="row mb-3">
          <div class="col">
            <h4 class="page-title mb-0">Statistiques des Forums</h4>
            <p class="text-muted mb-0" style="font-size:.85rem;">Analyse de l'activité de la communauté</p>
          </div>
        </div>
        

        <!-- KPI Cards -->
        <div class="row mb-4 align-items-stretch">
          <div class="col-md-3 mb-3 d-flex">
            <div class="card shadow-sm text-center p-4 h-100 w-100" style="background:linear-gradient(135deg,#9B5DE0,#B771E5);color:white;border-radius:10px;">
              <i class="mdi mdi-forum" style="font-size:2rem;margin-bottom:10px;"></i>
              <h6 class="mb-2">Total Forums</h6>
              <h3 class="mb-0"><?= $stats['total_forums'] ?? 0 ?></h3>
              <small class="mt-2 opacity-75">forums créés</small>
            </div>
          </div>
          <div class="col-md-3 mb-3 d-flex">
            <div class="card shadow-sm text-center p-4 h-100 w-100" style="background:linear-gradient(135deg,#E11D74,#D01565);color:white;border-radius:10px;">
              <i class="mdi mdi-message-text" style="font-size:2rem;margin-bottom:10px;"></i>
              <h6 class="mb-2">Total Messages</h6>
              <h3 class="mb-0"><?= $stats['total_messages'] ?? 0 ?></h3>
              <small class="mt-2 opacity-75">messages publiés</small>
            </div>
          </div>
          <div class="col-md-3 mb-3 d-flex">
            <div class="card shadow-sm text-center p-4 h-100 w-100" style="background:linear-gradient(135deg,#AEEA94,#99D98E);color:#2d5016;border-radius:10px;">
              <i class="mdi mdi-chart-line" style="font-size:2rem;margin-bottom:10px;"></i>
              <h6 class="mb-2">Forums Actifs (7j)</h6>
              <h3 class="mb-0"><?= $stats['forums_actifs'] ?? 0 ?></h3>
              <small class="mt-2" style="opacity:.75;">actifs cette semaine</small>
            </div>
          </div>
          <div class="col-md-3 mb-3 d-flex">
            <div class="card shadow-sm text-center p-4 h-100 w-100" style="background:linear-gradient(135deg,#D78FEE,#C96FE8);color:white;border-radius:10px;">
              <i class="mdi mdi-flag" style="font-size:2rem;margin-bottom:10px;"></i>
              <h6 class="mb-2">Messages Signalés</h6>
              <h3 class="mb-0"><?= $stats['messages_signales'] ?? 0 ?></h3>
              <small class="mt-2 opacity-75">à modérer</small>
            </div>
          </div>
        </div>

        <!-- Chart -->
        <div class="row mb-4">
          <div class="col-lg-12 mb-3">
            <div class="card shadow-sm">
              <div class="card-body">
                <h5 class="card-title mb-3"><i class="mdi mdi-chart-line me-2" style="color:#9B5DE0;"></i>Évolution des messages (6 mois)</h5>
                <canvas id="messagesChart" style="max-height:300px;"></canvas>
              </div>
            </div>
          </div>
        </div>

        <!-- Top tables -->
        <div class="row">
          <div class="col-lg-6 mb-3">
            <div class="card shadow-sm">
              <div class="card-body">
                <h5 class="card-title mb-3"><i class="mdi mdi-trophy me-2" style="color:#E11D74;"></i>Top 5 forums</h5>
                <div class="table-responsive">
                  <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>#</th><th>Forum</th><th>Messages</th><th>Activité</th></tr></thead>
                    <tbody>
                      <?php foreach (($stats['top_forums'] ?? []) as $i => $f): ?>
                      <tr>
                        <td><span class="badge" style="background:<?= $i===0?'#fbbf24':($i===1?'#94a3b8':'#cd7f32') ?>;color:#0d1117;"><?= $i+1 ?></span></td>
                        <td><strong><?= htmlspecialchars($f['TitreForum'] ?? 'Forum') ?></strong></td>
                        <td><?= $f['nb_messages'] ?? 0 ?></td>
                        <td>
                          <span class="badge" style="background:rgba(155,93,224,.15);color:#9B5DE0;">
                            <?= ($f['nb_messages']??0)>10?'🔥 Très actif':(($f['nb_messages']??0)>0?'💬 Actif':'🕰️ Calme') ?>
                          </span>
                        </td>
                      </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-6 mb-3">
            <div class="card shadow-sm">
              <div class="card-body">
                <h5 class="card-title mb-3"><i class="mdi mdi-account-star me-2" style="color:#9B5DE0;"></i>Top contributeurs</h5>
                <div class="table-responsive">
                  <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>#</th><th>Contributeur</th><th>Messages</th><th>Impact</th></tr></thead>
                    <tbody>
                      <?php foreach (($stats['top_contributeurs'] ?? []) as $i => $u): ?>
                      <tr>
                        <td><span class="badge" style="background:<?= $i===0?'#fbbf24':($i===1?'#94a3b8':'#cd7f32') ?>;color:#0d1117;"><?= $i+1 ?></span></td>
                        <td><strong><?= htmlspecialchars($u['nom'] ?? 'Anonyme') ?></strong></td>
                        <td><?= $u['nb_messages'] ?? 0 ?></td>
                        <td>
                          <span class="badge" style="background:rgba(225,29,116,.15);color:#E11D74;">
                            <?= ($u['nb_messages']??0)>50?'🏆 Expert':(($u['nb_messages']??0)>20?'📝 Actif':'🌱 Débutant') ?>
                          </span>
                        </td>
                      </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div><!-- content-wrapper -->
      <footer class="footer">
        <div class="d-sm-flex justify-content-center justify-content-sm-between">
          <span class="text-muted d-block text-center text-sm-left d-sm-inline-block">Copyright &copy; cre8connect 2024</span>
          <span class="float-none float-sm-right d-block mt-1 mt-sm-0 text-center">Statistiques Forums</span>
        </div>
      </footer>
    </div><!-- main-panel -->
  </div><!-- page-body-wrapper -->
</div><!-- container-scroller -->

<script src="<?= $backBoUtilisateurWeb ?? '../utilisateur' ?>/assets/vendors/js/vendor.bundle.base.js"></script>
<script src="<?= $backBoUtilisateurWeb ?? '../utilisateur' ?>/assets/vendors/chart.js/Chart.min.js"></script>
<script src="<?= ($backBoRootWeb ?? '..') ?>/layout/back-layout.js?v=<?php echo urlencode((string)filemtime(__DIR__.'/../layout/back-layout.js')); ?>"></script>
<script src="<?= $backBoUtilisateurWeb ?? '../utilisateur' ?>/assets/js/off-canvas.js"></script>
<script src="<?= $backBoUtilisateurWeb ?? '../utilisateur' ?>/assets/js/hoverable-collapse.js"></script>
<script src="<?= $backBoUtilisateurWeb ?? '../utilisateur' ?>/assets/js/misc.js"></script>
<script src="<?= $backBoUtilisateurWeb ?? '../utilisateur' ?>/assets/js/settings.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const tc = document.body.classList.contains('light-mode') ? '#374151' : '#e6edf3';
    const gc = document.body.classList.contains('light-mode') ? 'rgba(0,0,0,0.08)' : '#30363d';

    new Chart(document.getElementById('messagesChart'), {
      type: 'line',
      data: {
        labels: <?= json_encode($stats['months_labels'] ?? []) ?>,
        datasets: [{
          label: 'Messages',
          data: <?= json_encode($stats['messages_data'] ?? []) ?>,
          borderColor: '#9B5DE0',
          backgroundColor: 'rgba(155,93,224,0.12)',
          tension: 0.3,
          fill: true,
          pointBackgroundColor: '#9B5DE0'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: { legend: { labels: { color: tc } } },
        scales: { y: { ticks: { color: tc }, grid: { color: gc } }, x: { ticks: { color: tc }, grid: { color: gc } } }
      }
    });
  });
</script>
</body>
</html>
