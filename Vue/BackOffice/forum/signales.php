<?php
// ── Compute backoffice asset paths before any HTML output ──────────────────
require_once __DIR__ . '/../layout/bo_paths.php';

if (!isset($messages)) { $messages = []; }
$total_signales = count($messages);
$BASE = rtrim(str_replace('\\','/',dirname(dirname($_SERVER['SCRIPT_NAME']))),'/');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <?php require_once __DIR__ . '/../layout/early-theme.php'; cre8_bo_early_theme_print_head_script(); ?>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>cre8connect Admin – Messages Signalés</title>
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
    body.light-mode .card,body.light-mode .card-body,body.light-mode .modal-content { background-color:#ffffff!important; color:#111827!important; border-color:#d1d5db!important; }
    body.light-mode .card-title { color:#111827!important; }
    body:not(.light-mode) { background-color:#0f131d!important; color:#e7ebff!important; }
    body:not(.light-mode) .container-scroller,body:not(.light-mode) .page-body-wrapper,body:not(.light-mode) .main-panel,body:not(.light-mode) .content-wrapper { background-color:#101520!important; color:#e7ebff!important; }
    body:not(.light-mode) .card,body:not(.light-mode) .card-body { background-color:rgba(18,24,41,0.96)!important; color:#e7ebff!important; border-color:rgba(255,255,255,0.08)!important; box-shadow:0 18px 45px rgba(0,0,0,0.18); }
    body:not(.light-mode) .card-title { color:#eef3ff!important; }
    body:not(.light-mode) .page-header .page-title { color:#f3f7ff!important; }
    .table-action-btn { min-width:90px; height:36px; border-radius:14px!important; padding:6px 12px!important; font-weight:600; font-size:.85rem; box-shadow:0 2px 8px rgba(0,0,0,.12); transition:transform .2s,opacity .2s; display:inline-flex; justify-content:center; align-items:center; white-space:nowrap; }
    .table-action-btn:hover { transform:translateY(-1px); opacity:.95; }
    .message-card { border-left:4px solid #9B5DE0; margin-bottom:16px; }
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
        <div class="row mb-3 align-items-center">
          <div class="col">
            <h4 class="page-title mb-0">Messages Signalés</h4>
            <p class="text-muted mb-0" style="font-size:.85rem;">Modération des messages signalés par les utilisateurs</p>
          </div>
        </div>
        

        <!-- KPI Cards -->
        <div class="row mb-4 align-items-stretch">
          <div class="col-md-4 mb-3 d-flex">
            <div class="card shadow-sm text-center p-4 h-100 w-100" style="background:linear-gradient(135deg,#9B5DE0,#B771E5);color:white;border-radius:10px;">
              <i class="mdi mdi-flag" style="font-size:2rem;margin-bottom:10px;"></i>
              <h6 class="mb-2">Total Signalés</h6>
              <h3 class="mb-0"><?= $total_signales ?></h3>
              <small class="mt-2 opacity-75">messages signalés</small>
            </div>
          </div>
          <div class="col-md-4 mb-3 d-flex">
            <div class="card shadow-sm text-center p-4 h-100 w-100" style="background:linear-gradient(135deg,#E11D74,#D01565);color:white;border-radius:10px;">
              <i class="mdi mdi-clock-alert" style="font-size:2rem;margin-bottom:10px;"></i>
              <h6 class="mb-2">En attente</h6>
              <h3 class="mb-0"><?= $total_signales ?></h3>
              <small class="mt-2 opacity-75">à traiter</small>
            </div>
          </div>
          <div class="col-md-4 mb-3 d-flex">
            <div class="card shadow-sm text-center p-4 h-100 w-100" style="background:linear-gradient(135deg,#AEEA94,#99D98E);color:#2d5016;border-radius:10px;">
              <i class="mdi mdi-check-circle" style="font-size:2rem;margin-bottom:10px;"></i>
              <h6 class="mb-2">Supprimés</h6>
              <h3 class="mb-0">0</h3>
              <small class="mt-2" style="opacity:.75;">cette session</small>
            </div>
          </div>
        </div>

        <!-- Messages list -->
        <?php if (empty($messages)): ?>
          <div class="alert alert-success d-flex align-items-center" role="alert">
            <i class="mdi mdi-check-circle me-2" style="font-size:1.4rem;"></i>
            <div>Aucun message signalé — tous les messages sont conformes.</div>
          </div>
        <?php else: ?>
          <?php foreach ($messages as $msg): ?>
          <div class="card message-card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <span>
                <i class="mdi mdi-pin me-1" style="color:#9B5DE0;"></i>
                Forum : <strong><?= htmlspecialchars($msg['titreForum'] ?? '') ?></strong>
              </span>
              <button type="button" class="btn table-action-btn text-white" style="background:#D78FEE;"
                onclick="supprimerMessage(<?= $msg['idMessage'] ?>)">
                <i class="mdi mdi-delete me-1"></i> Supprimer
              </button>
            </div>
            <div class="card-body">
              <p class="mb-1 text-muted" style="font-size:.82rem;">
                <i class="mdi mdi-account me-1"></i><?= htmlspecialchars($msg['nom_utilisateur'] ?? '') ?>
                &nbsp;·&nbsp;
                <i class="mdi mdi-calendar me-1"></i><?= date('d/m/Y H:i', strtotime($msg['dateMessage'])) ?>
              </p>
              <p class="mb-0"><?= nl2br(htmlspecialchars($msg['message'] ?? '')) ?></p>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>

      </div><!-- content-wrapper -->
      <footer class="footer">
        <div class="d-sm-flex justify-content-center justify-content-sm-between">
          <span class="text-muted d-block text-center text-sm-left d-sm-inline-block">Copyright &copy; cre8connect 2024</span>
          <span class="float-none float-sm-right d-block mt-1 mt-sm-0 text-center">Messages Signalés</span>
        </div>
      </footer>
    </div><!-- main-panel -->
  </div><!-- page-body-wrapper -->
</div><!-- container-scroller -->

<script src="<?= $backBoUtilisateurWeb ?? '../utilisateur' ?>/assets/vendors/js/vendor.bundle.base.js"></script>
<script src="<?= ($backBoRootWeb ?? '..') ?>/layout/back-layout.js?v=<?php echo urlencode((string)filemtime(__DIR__.'/../layout/back-layout.js')); ?>"></script>
<script src="<?= $backBoUtilisateurWeb ?? '../utilisateur' ?>/assets/js/off-canvas.js"></script>
<script src="<?= $backBoUtilisateurWeb ?? '../utilisateur' ?>/assets/js/hoverable-collapse.js"></script>
<script src="<?= $backBoUtilisateurWeb ?? '../utilisateur' ?>/assets/js/misc.js"></script>
<script src="<?= $backBoUtilisateurWeb ?? '../utilisateur' ?>/assets/js/settings.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
  function supprimerMessage(id) {
    if (confirm('Supprimer ce message signalé ?'))
      window.location.href = '<?= $BASE ?>/Controleur/forumC.php?action=supprimer_message&id=' + id;
  }
</script>
</body>
</html>
