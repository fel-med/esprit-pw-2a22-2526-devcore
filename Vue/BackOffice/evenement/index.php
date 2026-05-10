<?php
// ── Compute backoffice asset paths before any HTML output ──────────────────
require_once __DIR__ . '/../layout/bo_paths.php';

// ========== HANDLE POST ACTIONS (DELETE, CREATE, UPDATE) ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../../../config.php';
    $pdo = config::getConnexion();
    if (isset($_POST['action']) && $_POST['action'] === 'delete_event' && isset($_POST['event_id'])) {
        try {
            $stmt = $pdo->prepare("DELETE FROM evenement WHERE idFormation = ?");
            $stmt->execute([$_POST['event_id']]);
            header('Location: ' . $_SERVER['PHP_SELF'] . '?deleted=1');
            exit;
        } catch (Exception $e) { $error = "Erreur lors de la suppression"; }
    }
    if (isset($_POST['action']) && $_POST['action'] === 'create_event') {
        try {
            $stmt = $pdo->prepare("INSERT INTO evenement (TitreFormation, description, type, statut, lieu, DateFormation, capacite, Duree, adresse_complete, image, created_at, nb_inscrits) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 0)");
            $imagePath = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../../public/uploads/evenements/';
                if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
                $fileName = time() . '_' . basename($_FILES['image']['name']);
                move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $fileName);
                $imagePath = 'Vue/public/uploads/evenements/' . $fileName;
            }
            $stmt->execute([$_POST['titre'], $_POST['description'], $_POST['type'], $_POST['statut'], $_POST['lieu'], $_POST['date_evenement'], (int)$_POST['capacite'], (int)$_POST['duree'], $_POST['adresse_complete'], $imagePath]);
            header('Location: ' . $_SERVER['PHP_SELF'] . '?created=1'); exit;
        } catch (Exception $e) { $error = "Erreur lors de la creation: " . $e->getMessage(); }
    }
    if (isset($_POST['action']) && $_POST['action'] === 'update_event' && isset($_POST['id'])) {
        try {
            $stmt = $pdo->prepare("SELECT image FROM evenement WHERE idFormation = ?");
            $stmt->execute([$_POST['id']]); $currentImage = $stmt->fetchColumn();
            $imagePath = $currentImage;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../../public/uploads/evenements/';
                if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
                $fileName = time() . '_' . basename($_FILES['image']['name']);
                move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $fileName);
                $imagePath = 'Vue/public/uploads/evenements/' . $fileName;
            }
            $stmt = $pdo->prepare("UPDATE evenement SET TitreFormation = ?, description = ?, type = ?, statut = ?, lieu = ?, DateFormation = ?, capacite = ?, Duree = ?, adresse_complete = ?, image = ? WHERE idFormation = ?");
            $stmt->execute([$_POST['titre'], $_POST['description'], $_POST['type'], $_POST['statut'], $_POST['lieu'], $_POST['date_evenement'], (int)$_POST['capacite'], (int)$_POST['duree'], $_POST['adresse_complete'], $imagePath, $_POST['id']]);
            header('Location: ' . $_SERVER['PHP_SELF'] . '?updated=1'); exit;
        } catch (Exception $e) { $error = "Erreur lors de la mise a jour: " . $e->getMessage(); }
    }
}
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    require_once __DIR__ . '/../../../config.php';
    $pdo = config::getConnexion();
    try {
        $stmt = $pdo->prepare("DELETE FROM evenement WHERE idFormation = ?");
        $stmt->execute([$_GET['id']]);
        header('Location: ' . strtok($_SERVER['PHP_SELF'], '?') . '?deleted=1'); exit;
    } catch (Exception $e) { $error = "Erreur lors de la suppression"; }
}
$BASE = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
$kpi_total = 0; $kpi_inscrits = 0; $kpi_actifs = 0; $kpi_upcoming = 0; $kpi_taux = 0;
$topEvents = []; $types = []; $months_labels = []; $events_data = []; $participants_data = []; $pendingEvents = 0;
if (!isset($evenements)) {
    require_once __DIR__ . '/../../../config.php';
    require_once __DIR__ . '/../../../Modele/evenement.php';
    try {
        $pdo = config::getConnexion();
        $stmt = $pdo->query("SELECT * FROM evenement ORDER BY idFormation DESC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $evenements = [];
        foreach ($rows as $row) {
            $evenements[] = new Evenement((int)($row['idFormation'] ?? 0), $row['TitreFormation'] ?? '', $row['description'] ?? '', $row['type'] ?? '', $row['statut'] ?? '', $row['lieu'] ?? '', $row['DateFormation'] ?? '', (int)($row['capacite'] ?? 0), (int)($row['nb_inscrits'] ?? 0), (int)($row['Duree'] ?? 0), $row['created_at'] ?? '', $row['image'] ?? null, $row['adresse_complete'] ?? null);
        }
    } catch (Exception $e) { $evenements = []; }
}
try {
    require_once __DIR__ . '/../../../config.php';
    $pdo = config::getConnexion();
    $kpi_total    = (int)$pdo->query("SELECT COUNT(*) FROM evenement")->fetchColumn();
    $kpi_inscrits = (int)$pdo->query("SELECT COALESCE(SUM(nb_inscrits), 0) FROM evenement")->fetchColumn();
    $kpi_actifs   = (int)$pdo->query("SELECT COUNT(*) FROM evenement WHERE statut = 'actif'")->fetchColumn();
    $kpi_upcoming = (int)$pdo->query("SELECT COUNT(*) FROM evenement WHERE DateFormation > CURDATE()")->fetchColumn();
    $avg = $pdo->query("SELECT AVG((nb_inscrits / capacite) * 100) FROM evenement WHERE capacite > 0")->fetchColumn();
    $kpi_taux = round($avg ?: 0);
    $pendingEvents = (int)$pdo->query("SELECT COUNT(*) FROM evenement WHERE statut = 'en_attente'")->fetchColumn();
    $months = [];
    for ($i = 5; $i >= 0; $i--) { $month = date('Y-m', strtotime("-$i months")); $months[$month] = ['events' => 0, 'participants' => 0]; }
    $stmtMonths = $pdo->query("SELECT DATE_FORMAT(DateFormation, '%Y-%m') as mois, COUNT(*) as nb_events, COALESCE(SUM(nb_inscrits), 0) as nb_participants FROM evenement WHERE DateFormation > DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY DATE_FORMAT(DateFormation, '%Y-%m') ORDER BY mois ASC");
    while ($row = $stmtMonths->fetch(PDO::FETCH_ASSOC)) { if (isset($months[$row['mois']])) { $months[$row['mois']]['events'] = (int)$row['nb_events']; $months[$row['mois']]['participants'] = (int)$row['nb_participants']; } }
    $months_labels = array_keys($months); $events_data = array_column($months, 'events'); $participants_data = array_column($months, 'participants');
    $stmtTop = $pdo->query("SELECT TitreFormation as titre, type, nb_inscrits as participants, capacite, ROUND((nb_inscrits / capacite) * 100, 1) as taux FROM evenement WHERE capacite > 0 ORDER BY nb_inscrits DESC LIMIT 5");
    $topEvents = $stmtTop->fetchAll(PDO::FETCH_ASSOC);
    $stmtTypes = $pdo->query("SELECT type, COUNT(*) as count, COALESCE(SUM(nb_inscrits), 0) as participants FROM evenement GROUP BY type");
    while ($row = $stmtTypes->fetch(PDO::FETCH_ASSOC)) { $types[$row['type']] = ['count' => (int)$row['count'], 'participants' => (int)$row['participants']]; }
} catch (Exception $e) { error_log("Erreur stats: " . $e->getMessage()); }
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <?php require_once __DIR__ . '/../layout/early-theme.php'; cre8_bo_early_theme_print_head_script(); ?>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>cre8connect Admin – Evenements</title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- plugins:css -->
  <link rel="stylesheet" href="<?= $backBoUtilisateurWeb ?>/assets/vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="<?= $backBoUtilisateurWeb ?>/assets/vendors/css/vendor.bundle.base.css">
  <!-- endinject -->
  <!-- Layout styles -->
  <link rel="stylesheet" href="<?= $backBoUtilisateurWeb ?>/assets/css/style.css">
  <!-- End layout styles -->
  <link rel="stylesheet" href="<?= $backBoRootWeb ?>/layout/back-layout.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../layout/back-layout.css')); ?>">
  <link rel="shortcut icon" href="<?= $backBoUtilisateurWeb ?>/assets/images/favicon.png">
  <style>
    body.light-mode {
      background-color: #f8fafc !important;
      color: #111827 !important;
    }
    body.light-mode .container-scroller,
    body.light-mode .page-body-wrapper,
    body.light-mode .main-panel,
    body.light-mode .content-wrapper,
    body.light-mode .footer {
      background-color: #ffffff !important;
      color: #111827 !important;
      border-color: #e2e8f0 !important;
    }
    body.light-mode .page-title,
    body.light-mode .card-title,
    body.light-mode .card-description,
    body.light-mode .table th,
    body.light-mode .table td,
    body.light-mode .badge,
    body.light-mode .form-control,
    body.light-mode .form-select,
    body.light-mode .dropdown-item,
    body.light-mode .modal-title,
    body.light-mode .modal-body,
    body.light-mode .modal-footer {
      color: #111827 !important;
    }
    body.light-mode .card,
    body.light-mode .card-body,
    body.light-mode .table,
    body.light-mode .table-responsive,
    body.light-mode .dropdown-menu,
    body.light-mode .modal-content,
    body.light-mode .form-control,
    body.light-mode .form-select,
    body.light-mode textarea {
      background-color: #ffffff !important;
      color: #111827 !important;
      border-color: #d1d5db !important;
      box-shadow: 0 8px 25px rgba(15, 23, 42, 0.06) !important;
    }
    body.light-mode .btn:not(.btn-primary):not(.btn-secondary):not(.btn-success):not(.btn-danger):not(.btn-warning):not(.btn-info):not(.btn-light):not(.btn-dark):not(.btn-outline-primary):not(.btn-outline-secondary):not(.btn-outline-success):not(.btn-outline-danger):not(.btn-outline-warning):not(.btn-outline-info):not(.btn-outline-light):not(.btn-outline-dark):not(.btn-gradient):not(.btn-outline-gradient) {
      background-color: #ffffff !important;
      color: #111827 !important;
      border-color: #d1d5db !important;
      box-shadow: 0 8px 25px rgba(15, 23, 42, 0.06) !important;
    }
    body.light-mode .table thead {
      background-color: #e2e8f0 !important;
      color: #111827 !important;
    }
    body.light-mode .table tbody tr:hover {
      background-color: #f8fafc !important;
    }
    body.light-mode input.form-control,
    body.light-mode select.form-select,
    body.light-mode textarea.form-control {
      background-color: #ffffff !important;
      color: #111827 !important;
      border-color: #d1d5db !important;
    }
    body.light-mode .modal-content {
      background-color: #ffffff !important;
      color: #111827 !important;
      border-color: #d1d5db !important;
    }
    body.light-mode .modal-header {
      background-color: #f1f5f9 !important;
      border-color: #d1d5db !important;
    }
    body:not(.light-mode) {
      background-color: #0f131d !important;
      color: #e7ebff !important;
    }
    body:not(.light-mode) .container-scroller,
    body:not(.light-mode) .page-body-wrapper,
    body:not(.light-mode) .main-panel,
    body:not(.light-mode) .content-wrapper,
    body:not(.light-mode) .footer {
      background-color: #101520 !important;
      color: #e7ebff !important;
    }
    body:not(.light-mode) .card,
    body:not(.light-mode) .card-body,
    body:not(.light-mode) .table,
    body:not(.light-mode) .dropdown-menu,
    body:not(.light-mode) .modal-content,
    body:not(.light-mode) .form-control,
    body:not(.light-mode) .form-select,
    body:not(.light-mode) .btn-outline-primary {
      background-color: rgba(18,24,41,0.96) !important;
      color: #e7ebff !important;
      border-color: rgba(255,255,255,0.08) !important;
    }
    body:not(.light-mode) .card {
      box-shadow: 0 18px 45px rgba(0,0,0,0.18);
    }
    body:not(.light-mode) .card-title,
    body:not(.light-mode) .card-description,
    body:not(.light-mode) .table thead th,
    body:not(.light-mode) .table td,
    body:not(.light-mode) .table th,
    body:not(.light-mode) .badge,
    body:not(.light-mode) .nav-link,
    body:not(.light-mode) .btn,
    body:not(.light-mode) .form-control,
    body:not(.light-mode) .form-select {
      color: #eef3ff !important;
    }
    body:not(.light-mode) .table thead {
      background-color: rgba(255,255,255,0.05) !important;
    }
    body:not(.light-mode) .page-header .page-title {
      color: #f3f7ff !important;
    }
    .table-action-btn {
      min-width: 100px;
      max-width: 130px;
      height: 38px;
      border-radius: 16px !important;
      padding: 8px 14px !important;
      font-weight: 600;
      font-size: 0.92rem;
      box-shadow: 0 2px 8px rgba(0,0,0,0.12);
      transition: transform 0.2s ease, opacity 0.2s ease;
      display: inline-flex;
      justify-content: center;
      align-items: center;
      text-align: center;
      white-space: nowrap;
    }
    .table-action-btn:hover {
      transform: translateY(-1px);
      opacity: 0.95;
    }
    body.light-mode table thead,
    body.light-mode table thead tr,
    body.light-mode table thead th,
    body.light-mode .table thead,
    body.light-mode .table thead tr,
    body.light-mode .table thead th {
      background: #f3f0ff !important;
      background-color: #f3f0ff !important;
      color: #5b4fff !important;
      border-color: #e5e7eb !important;
    }
    body.light-mode table tbody tr,
    body.light-mode table tbody td {
      background: #ffffff !important;
      background-color: #ffffff !important;
      color: #111827 !important;
      border-color: #e5e7eb !important;
    }
    body.light-mode table tbody tr:hover,
    body.light-mode table tbody tr:hover td {
      background: #f8fafc !important;
      background-color: #f8fafc !important;
    }
    /* Event-specific badge styles */
    .badge-actif    { background: rgba(34,197,94,0.15);  color: #22c55e; }
    .badge-en_attente { background: rgba(245,158,11,0.15); color: #f59e0b; }
    .badge-brouillon  { background: rgba(168,85,247,0.15); color: #a855f7; }
    .type-chip { display: inline-block; padding: 3px 10px; border-radius: 6px; font-size: 0.75rem; background: rgba(155,93,224,0.15); color: #9B5DE0; white-space: nowrap; }
    .progress-wrap { display: flex; align-items: center; gap: 8px; min-width: 80px; }
    .progress-bar-thin { flex: 1; height: 5px; border-radius: 10px; overflow: hidden; background: rgba(255,255,255,0.1); }
    .progress-fill { height: 100%; background: linear-gradient(90deg, #9B5DE0, #D78FEE); border-radius: 10px; transition: width 0.6s ease; }
    .rank-badge { display: inline-block; width: 24px; height: 24px; border-radius: 6px; text-align: center; line-height: 24px; font-size: 0.7rem; font-weight: 700; background: rgba(255,255,255,0.1); }
    .rank-1 { background: #fbbf24 !important; color: #0d1117 !important; }
    .rank-2 { background: #94a3b8 !important; color: #0d1117 !important; }
    .rank-3 { background: #cd7f32 !important; color: #0d1117 !important; }
    .sort-icon { display: inline-block; margin-left: 4px; font-size: 0.7rem; opacity: 0.5; }
    .sort-icon.active { opacity: 1; color: #9B5DE0; }
    /* Prevent content overlapping the sticky topbar */
    .content-wrapper { padding-top: 1.5rem !important; }
    /* Ensure Bootstrap modals appear above everything */
    .modal { z-index: 10500 !important; }
    .modal-backdrop { z-index: 10499 !important; }
    .modal-dialog { margin: 1.75rem auto; max-width: 560px !important; }
    .modal-content { border-radius: 12px; max-height: 85vh; overflow-y: auto; }
    .modal-body {
    max-height: 400px !important;
    overflow-y: auto !important;
}

.modal-dialog {
    max-width: 500px !important;
}

.modal-content {
    max-height: 80vh !important;
}
  </style>
</head>

<body class="cre8-admin-layout"><?php cre8_bo_early_theme_print_body_script(); ?>

<div class="container-scroller cre8-admin-page">
  <?php $backActive = 'events'; require_once __DIR__ . '/../layout/sidebar.php'; ?>
  <div class="container-fluid page-body-wrapper cre8-admin-main">
    <?php require_once __DIR__ . '/../layout/header.php'; ?>
    <div class="main-panel">
      <div class="content-wrapper">

        <?php if (isset($_GET['deleted'])): ?>
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="mdi mdi-check-circle me-2"></i> Evenement supprime avec succes.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>
        <?php if (isset($_GET['created'])): ?>
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="mdi mdi-check-circle me-2"></i> Evenement cree avec succes.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>
        <?php if (isset($_GET['updated'])): ?>
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="mdi mdi-check-circle me-2"></i> Evenement mis a jour avec succes.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>

        <?php if ($pendingEvents > 0): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
          <i class="mdi mdi-alert me-2"></i>
          <strong><?= $pendingEvents ?> evenement(s)</strong> en attente de validation.
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Page header -->
        <div class="row mb-3 align-items-center">
          <div class="col">
            <h4 class="page-title mb-0">Gestion des Evenements</h4>
            <p class="text-muted mb-0" style="font-size:0.85rem;">Supervision, moderation et administration de tous les evenements</p>
          </div>
          <div class="col-auto">
            <button class="btn text-white" style="background: linear-gradient(135deg, #9B5DE0, #B771E5); border-radius: 10px;" onclick="openModal()">
              <i class="mdi mdi-plus me-1"></i> Nouvel evenement
            </button>
          </div>
        </div>

        <!-- ===================== KPI CARDS ===================== -->
        <div class="row mb-4 align-items-stretch">

          <!-- Total Evenements -->
          <div class="col-md-3 mb-3 d-flex">
            <div class="card shadow-sm text-center p-4 h-100 w-100" style="background: linear-gradient(135deg, #9B5DE0 0%, #B771E5 100%); color: white; border-radius: 10px;">
              <i class="mdi mdi-calendar-check" style="font-size: 2rem; margin-bottom: 10px;"></i>
              <h6 class="mb-2">Total Evenements</h6>
              <h3 class="mb-0"><?= $kpi_total ?></h3>
              <small class="mt-2 opacity-75"><?= $kpi_actifs ?> actifs</small>
            </div>
          </div>

          <!-- Inscriptions -->
          <div class="col-md-3 mb-3 d-flex">
            <div class="card shadow-sm text-center p-4 h-100 w-100" style="background: linear-gradient(135deg, #E11D74 0%, #D01565 100%); color: white; border-radius: 10px;">
              <i class="mdi mdi-account-multiple" style="font-size: 2rem; margin-bottom: 10px;"></i>
              <h6 class="mb-2">Inscriptions</h6>
              <h3 class="mb-0"><?= $kpi_inscrits ?></h3>
              <small class="mt-2 opacity-75">total participants</small>
            </div>
          </div>

          <!-- Taux remplissage -->
          <div class="col-md-3 mb-3 d-flex">
            <div class="card shadow-sm text-center p-4 h-100 w-100" style="background: linear-gradient(135deg, #AEEA94 0%, #99D98E 100%); color: #2d5016; border-radius: 10px;">
              <i class="mdi mdi-chart-donut" style="font-size: 2rem; margin-bottom: 10px;"></i>
              <h6 class="mb-2">Taux remplissage</h6>
              <h3 class="mb-0"><?= $kpi_taux ?>%</h3>
              <small class="mt-2" style="opacity:0.75;">moyenne generale</small>
            </div>
          </div>

          <!-- A venir -->
          <div class="col-md-3 mb-3 d-flex">
            <div class="card shadow-sm text-center p-4 h-100 w-100" style="background: linear-gradient(135deg, #D78FEE 0%, #C96FE8 100%); color: white; border-radius: 10px;">
              <i class="mdi mdi-calendar-clock" style="font-size: 2rem; margin-bottom: 10px;"></i>
              <h6 class="mb-2">A venir</h6>
              <h3 class="mb-0"><?= $kpi_upcoming ?></h3>
              <small class="mt-2 opacity-75">prochains evenements</small>
            </div>
          </div>

        </div>

        <!-- ===================== CHARTS ===================== -->
        <div class="row mb-4">
          <div class="col-lg-6 mb-3">
            <div class="card shadow-sm">
              <div class="card-body">
                <h5 class="card-title mb-3">Evolution des participants</h5>
                <canvas id="participantsChart" style="max-height:250px;"></canvas>
              </div>
            </div>
          </div>
          <div class="col-lg-6 mb-3">
            <div class="card shadow-sm">
              <div class="card-body">
                <h5 class="card-title mb-3">Evenements par mois</h5>
                <canvas id="eventsChart" style="max-height:250px;"></canvas>
              </div>
            </div>
          </div>
        </div>

        <div class="row mb-4">
          <div class="col-lg-5 mb-3">
            <div class="card shadow-sm">
              <div class="card-body">
                <h5 class="card-title mb-3">Repartition par type</h5>
                <canvas id="typeChart" style="max-height:250px;"></canvas>
              </div>
            </div>
          </div>
          <div class="col-lg-7 mb-3">
            <div class="card shadow-sm">
              <div class="card-body">
                <h5 class="card-title mb-3">Top 5 evenements</h5>
                <div class="table-responsive">
                  <table class="table table-hover align-middle mb-0">
                    <thead>
                      <tr>
                        <th>#</th>
                        <th>Evenement</th>
                        <th>Type</th>
                        <th>Participants</th>
                        <th>Taux</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($topEvents as $index => $event): ?>
                      <tr>
                        <td><span class="rank-badge <?= $index == 0 ? 'rank-1' : ($index == 1 ? 'rank-2' : ($index == 2 ? 'rank-3' : '')) ?>"><?= $index + 1 ?></span></td>
                        <td><strong><?= htmlspecialchars(mb_substr($event['titre'], 0, 28)) ?>...</strong></td>
                        <td><span class="type-chip"><?= ucfirst($event['type']) ?></span></td>
                        <td><?= $event['participants'] ?> / <?= $event['capacite'] ?></td>
                        <td><span class="badge <?= $event['taux'] > 70 ? 'badge-actif' : ($event['taux'] > 30 ? 'badge-en_attente' : 'badge-brouillon') ?>"><?= $event['taux'] ?>%</span></td>
                      </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ===================== TABLE ===================== -->
        <div class="row">
          <div class="col-12 grid-margin">
            <div class="card">
              <div class="card-body">
                <h4 class="card-title">Gestion des Evenements</h4>

                <!-- Toolbar -->
                <div class="row mb-3 align-items-center">
                  <div class="col-md-6">
                    <div class="input-group">
                      <span class="input-group-text"><i class="mdi mdi-magnify"></i></span>
                      <input type="text" id="tableSearchInput" class="form-control" placeholder="Rechercher dans le tableau..." onkeyup="filterTable()">
                    </div>
                  </div>
                  <div class="col-md-4">
                    <span class="text-muted" style="font-size:0.85rem;">
                      <span id="tableCount"><?= count($evenements) ?></span> evenements affiches
                    </span>
                  </div>
                  <div class="col-md-2 text-end">
                    <button id="resetTableBtn" class="btn btn-sm" style="background:rgba(155,93,224,0.12); color:#9B5DE0; border:1px solid rgba(155,93,224,0.3);" onclick="resetTable()">
                      <i class="mdi mdi-refresh me-1"></i> Reinitialiser
                    </button>
                  </div>
                </div>

                <div class="table-responsive">
                  <table class="table table-hover align-middle">
                    <thead>
                      <tr>
                        <th><input type="checkbox"/></th>
                        <th>Image</th>
                        <th onclick="sortTable(2)" style="cursor:pointer;">Evenement <span class="sort-icon" id="sort-icon-2"></span></th>
                        <th onclick="sortTable(3)" style="cursor:pointer;">Type <span class="sort-icon" id="sort-icon-3"></span></th>
                        <th onclick="sortTable(4)" style="cursor:pointer;">Statut <span class="sort-icon" id="sort-icon-4"></span></th>
                        <th onclick="sortTable(5)" style="cursor:pointer;">Date <span class="sort-icon" id="sort-icon-5"></span></th>
                        <th onclick="sortTable(6)" style="cursor:pointer;">Lieu <span class="sort-icon" id="sort-icon-6"></span></th>
                        <th onclick="sortTable(7)" style="cursor:pointer;">Inscriptions <span class="sort-icon" id="sort-icon-7"></span></th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody id="tableBody">
                      <?php if (empty($evenements)): ?>
                        <tr><td colspan="9" class="text-center py-5">Aucun evenement trouve</td></tr>
                      <?php else: ?>
                        <?php foreach ($evenements as $event): ?>
                          <?php $percentage = ($event->getCapacite() > 0) ? ($event->getNbInscrits() / $event->getCapacite()) * 100 : 0; ?>
                          <tr data-event-id="<?= $event->getId() ?>">
                            <td><input type="checkbox" class="event-checkbox"/></td>
                            <td>
                              <?php if ($event->getImage()): ?>
                                <img src="<?= $BASE ?>/<?= $event->getImage() ?>" style="width:40px;height:40px;object-fit:cover;border-radius:8px;" alt="">
                              <?php else: ?>
                                <div style="width:40px;height:40px;background:rgba(155,93,224,0.15);border-radius:8px;display:flex;align-items:center;justify-content:center;">
                                  <i class="mdi mdi-calendar" style="color:#9B5DE0;"></i>
                                </div>
                              <?php endif; ?>
                            </td>
                            <td><strong><?= htmlspecialchars($event->getTitre()) ?></strong></td>
                            <td><span class="type-chip"><?= ucfirst($event->getType()) ?></span></td>
                            <td><span class="badge badge-<?= $event->getStatut() ?>"><?= $event->getStatut() ?></span></td>
                            <td><?= date('d M Y', strtotime($event->getDateEvenement())) ?></td>
                            <td><?= htmlspecialchars($event->getLieu() ?: 'En ligne') ?></td>
                            <td>
                              <div class="progress-wrap">
                                <div class="progress-bar-thin">
                                  <div class="progress-fill" style="width:<?= min(100, $percentage) ?>%"></div>
                                </div>
                                <span style="font-size:0.8rem;"><?= $event->getNbInscrits() ?>/<?= $event->getCapacite() ?></span>
                              </div>
                            </td>
                            <td>
                              <div class="d-flex gap-2 flex-wrap">
                                <button type="button" class="btn table-action-btn text-white"
                                  style="background-color: #9B5DE0;"
                                  onclick="editEvent(<?= $event->getId() ?>)">
                                  <i class="mdi mdi-pencil me-1"></i> Editer
                                </button>
                                <button type="button" class="btn table-action-btn text-white"
                                  style="background-color: #D78FEE;"
                                  onclick="deleteEvent(<?= $event->getId() ?>, '<?= htmlspecialchars(addslashes($event->getTitre())) ?>')">
                                  <i class="mdi mdi-delete me-1"></i> Suppr
                                </button>
                              </div>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>

              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- content-wrapper ends -->

      <!-- Footer -->
      <footer class="footer">
        <div class="d-sm-flex justify-content-center justify-content-sm-between">
          <span class="text-muted d-block text-center text-sm-left d-sm-inline-block">Copyright &copy; cre8connect 2024</span>
          <span class="float-none float-sm-right d-block mt-1 mt-sm-0 text-center">Gestion des Evenements</span>
        </div>
      </footer>
    </div>
    <!-- main-panel ends -->
  </div>
  <!-- page-body-wrapper ends -->
</div>
<!-- container-scroller -->

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <form method="POST" action="<?= $BASE ?>/Controleur/evenementC.php?action=create" enctype="multipart/form-data">
        <div class="modal-header">
          <h5 class="modal-title" id="createModalLabel"><i class="mdi mdi-plus-circle me-2"></i>Nouvel Evenement</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold">Titre *</label>
            <input type="text" name="titre" class="form-control" required/>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">Type *</label>
              <select name="type" class="form-select">
                <option value="formation">Formation</option>
                <option value="webinaire">Webinaire</option>
                <option value="meetup">Meetup</option>
                <option value="atelier">Atelier</option>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">Statut *</label>
              <select name="statut" class="form-select">
                <option value="brouillon">Brouillon</option>
                <option value="en_attente">En attente</option>
                <option value="actif">Actif</option>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Description</label>
            <textarea name="description" class="form-control" rows="3"></textarea>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">Date *</label>
              <input type="date" name="date_evenement" class="form-control" required/>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">Duree (heures)</label>
              <input type="number" name="duree" class="form-control"/>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">Lieu (ville)</label>
              <input type="text" name="lieu" class="form-control"/>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">Capacite</label>
              <input type="number" name="capacite" class="form-control" value="50"/>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Adresse complete</label>
            <input type="text" name="adresse_complete" class="form-control" placeholder="Ex: 45 Avenue Ahmed Tlili, Ariana"/>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Affiche</label>
            <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/jpg,image/webp" onchange="previewImage(this, 'createPreview')"/>
            <div id="createPreview" class="mt-2"></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn text-white" style="background: linear-gradient(135deg, #9B5DE0, #B771E5);">Creer</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <form method="POST" id="editForm" enctype="multipart/form-data">
        <div class="modal-header">
          <h5 class="modal-title" id="editModalLabel"><i class="mdi mdi-pencil me-2"></i>Modifier l'Evenement</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" id="edit_id"/>
          <div class="mb-3">
            <label class="form-label fw-semibold">Titre</label>
            <input type="text" name="titre" id="edit_titre" class="form-control" required/>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">Type</label>
              <select name="type" id="edit_type" class="form-select">
                <option value="formation">Formation</option>
                <option value="webinaire">Webinaire</option>
                <option value="meetup">Meetup</option>
                <option value="atelier">Atelier</option>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">Statut</label>
              <select name="statut" id="edit_statut" class="form-select">
                <option value="brouillon">Brouillon</option>
                <option value="en_attente">En attente</option>
                <option value="actif">Actif</option>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Description</label>
            <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">Date</label>
              <input type="date" name="date_evenement" id="edit_date" class="form-control" required/>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">Duree</label>
              <input type="number" name="duree" id="edit_duree" class="form-control"/>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">Lieu</label>
              <input type="text" name="lieu" id="edit_lieu" class="form-control"/>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">Capacite</label>
              <input type="number" name="capacite" id="edit_capacite" class="form-control"/>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Adresse complete</label>
            <input type="text" name="adresse_complete" id="edit_adresse" class="form-control"/>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Affiche</label>
            <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/jpg,image/webp" onchange="previewImage(this, 'editPreview')"/>
            <div id="editPreview" class="mt-2"></div>
            <div id="currentImageInfo" class="mt-2 text-muted" style="font-size:0.8rem;"></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn text-white" style="background: linear-gradient(135deg, #9B5DE0, #B771E5);">Mettre a jour</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- plugins:js -->
<script src="<?= $backBoUtilisateurWeb ?>/assets/vendors/js/vendor.bundle.base.js"></script>
<!-- endinject -->
<!-- Plugin js for this page -->
<script src="<?= $backBoUtilisateurWeb ?>/assets/vendors/chart.js/Chart.min.js"></script>
<!-- End plugin js for this page -->
<script src="<?= $backBoRootWeb ?>/layout/back-layout.js?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../layout/back-layout.js')); ?>"></script>
<script src="<?= $backBoUtilisateurWeb ?>/assets/js/off-canvas.js"></script>
<script src="<?= $backBoUtilisateurWeb ?>/assets/js/hoverable-collapse.js"></script>
<script src="<?= $backBoUtilisateurWeb ?>/assets/js/misc.js"></script>
<script src="<?= $backBoUtilisateurWeb ?>/assets/js/settings.js"></script>
<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Chart.js CDN fallback -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
  let sortColumn = 2;
  let sortDirection = 'asc';
  let originalRows = [];

  function saveOriginalOrder() {
    const tbody = document.getElementById('tableBody');
    if (!tbody) return;
    originalRows = Array.from(tbody.querySelectorAll('tr')).map(r => r.outerHTML);
  }

  function resetTable() {
    const tbody = document.getElementById('tableBody');
    if (!tbody) return;
    if (originalRows.length === 0) { saveOriginalOrder(); return; }
    tbody.innerHTML = '';
    originalRows.forEach(html => tbody.insertAdjacentHTML('beforeend', html));
    document.querySelectorAll('.sort-icon').forEach(i => { i.classList.remove('active','asc','desc'); i.textContent = ''; });
    sortColumn = 2; sortDirection = 'asc';
    const si = document.getElementById('tableSearchInput');
    if (si) si.value = '';
    document.getElementById('tableCount').textContent = document.querySelectorAll('#tableBody tr').length;
  }

  function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (input.files && input.files[0]) {
      const reader = new FileReader();
      reader.onload = e => { preview.innerHTML = '<img src="' + e.target.result + '" style="max-width:100px;border-radius:8px;">'; };
      reader.readAsDataURL(input.files[0]);
    } else { preview.innerHTML = ''; }
  }

  function openModal() {
    const m = new bootstrap.Modal(document.getElementById('createModal'));
    document.getElementById('createPreview').innerHTML = '';
    m.show();
  }

  function closeModal() {
    bootstrap.Modal.getInstance(document.getElementById('createModal'))?.hide();
  }

  function closeEditModal() {
    bootstrap.Modal.getInstance(document.getElementById('editModal'))?.hide();
  }

  function editEvent(id) {
    fetch('<?= $BASE ?>/Controleur/evenementC.php?action=get&id=' + id)
      .then(r => r.json())
      .then(data => {
        document.getElementById('edit_id').value = data.id;
        document.getElementById('edit_titre').value = data.titre;
        document.getElementById('edit_description').value = data.description;
        document.getElementById('edit_duree').value = data.duree;
        document.getElementById('edit_date').value = data.date_evenement;
        document.getElementById('edit_type').value = data.type;
        document.getElementById('edit_statut').value = data.statut;
        document.getElementById('edit_lieu').value = data.lieu;
        document.getElementById('edit_capacite').value = data.capacite;
        document.getElementById('edit_adresse').value = data.adresse_complete || '';
        if (data.image) {
          document.getElementById('currentImageInfo').innerHTML = '<strong>Image actuelle:</strong><br><img src="<?= $BASE ?>/' + data.image + '" style="max-width:100px;border-radius:8px;margin-top:5px;">';
        } else {
          document.getElementById('currentImageInfo').innerHTML = '<strong>Aucune image</strong>';
        }
        document.getElementById('editForm').action = '<?= $BASE ?>/Controleur/evenementC.php?action=edit&id=' + id;
        document.getElementById('editPreview').innerHTML = '';
        const m = new bootstrap.Modal(document.getElementById('editModal'));
        m.show();
      })
      .catch(() => alert('Erreur lors du chargement'));
  }

  function deleteEvent(id, titre) {
    if (confirm('Supprimer l\'evenement "' + titre + '" ?')) {
      window.location.href = '<?= $BASE ?>/Controleur/evenementC.php?action=delete&id=' + id;
    }
  }

  function filterTable() {
    const filter = document.getElementById('tableSearchInput').value.toLowerCase();
    const rows = document.querySelectorAll('#tableBody tr');
    let count = 0;
    rows.forEach(row => {
      const cells = row.querySelectorAll('td');
      let found = false;
      cells.forEach((cell, i) => {
        if (i !== 0 && i !== 8 && cell.textContent.toLowerCase().includes(filter)) found = true;
      });
      row.style.display = found ? '' : 'none';
      if (found) count++;
    });
    document.getElementById('tableCount').textContent = count;
  }

  function sortTable(col) {
    const tbody = document.getElementById('tableBody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    document.querySelectorAll('.sort-icon').forEach(i => { i.classList.remove('active','asc','desc'); i.textContent = ''; });
    if (sortColumn === col) { sortDirection = sortDirection === 'asc' ? 'desc' : 'asc'; } else { sortColumn = col; sortDirection = 'asc'; }
    const icon = document.getElementById('sort-icon-' + col);
    if (icon) { icon.classList.add('active', sortDirection); icon.textContent = sortDirection === 'asc' ? 'up' : 'down'; }
    rows.sort((a, b) => {
      let av = a.cells[col]?.textContent.trim() || '';
      let bv = b.cells[col]?.textContent.trim() || '';
      if (col === 7) {
        const am = av.match(/(\d+)\/(\d+)/), bm = bv.match(/(\d+)\/(\d+)/);
        if (am && bm) return sortDirection === 'asc' ? parseInt(am[1]) - parseInt(bm[1]) : parseInt(bm[1]) - parseInt(am[1]);
      }
      if (col === 5) { av = new Date(av); bv = new Date(bv); return sortDirection === 'asc' ? av - bv : bv - av; }
      av = av.toLowerCase(); bv = bv.toLowerCase();
      return sortDirection === 'asc' ? av.localeCompare(bv) : bv.localeCompare(av);
    });
    rows.forEach(r => tbody.appendChild(r));
  }

  document.addEventListener('DOMContentLoaded', function() {
    setTimeout(saveOriginalOrder, 200);

    const participantsData = <?= json_encode($participants_data) ?>;
    const eventsData       = <?= json_encode($events_data) ?>;
    const monthsLabels     = <?= json_encode($months_labels) ?>;
    const typeLabels       = <?= json_encode(array_keys($types)) ?>;
    const typeCounts       = <?= json_encode(array_column($types, 'count')) ?>;

    function textColor()  { return document.body.classList.contains('light-mode') ? '#374151' : '#e6edf3'; }
    function gridColor()  { return document.body.classList.contains('light-mode') ? 'rgba(0,0,0,0.08)' : '#30363d'; }

    const tc = textColor(), gc = gridColor();

    new Chart(document.getElementById('participantsChart'), {
      type: 'line',
      data: {
        labels: monthsLabels,
        datasets: [{ label: 'Participants', data: participantsData, borderColor: '#9B5DE0', backgroundColor: 'rgba(155,93,224,0.12)', tension: 0.3, fill: true, pointBackgroundColor: '#9B5DE0' }]
      },
      options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { labels: { color: tc } } }, scales: { y: { ticks: { color: tc }, grid: { color: gc } }, x: { ticks: { color: tc }, grid: { color: gc } } } }
    });

    new Chart(document.getElementById('eventsChart'), {
      type: 'bar',
      data: {
        labels: monthsLabels,
        datasets: [{ label: 'Evenements', data: eventsData, backgroundColor: '#D78FEE', borderRadius: 8 }]
      },
      options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { labels: { color: tc } } }, scales: { y: { ticks: { color: tc }, grid: { color: gc } }, x: { ticks: { color: tc }, grid: { color: gc } } } }
    });

    new Chart(document.getElementById('typeChart'), {
      type: 'doughnut',
      data: {
        labels: typeLabels.map(t => t.charAt(0).toUpperCase() + t.slice(1)),
        datasets: [{ data: typeCounts, backgroundColor: ['#9B5DE0','#E11D74','#AEEA94','#D78FEE','#B771E5'] }]
      },
      options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { labels: { color: tc } } } }
    });

    window.addEventListener('themeChanged', function() {
      Chart.helpers.each(Chart.instances, function(chart) { chart.destroy(); });
    });
  });
</script>

</body>
</html>
