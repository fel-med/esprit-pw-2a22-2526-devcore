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
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
if (($pos = strpos($scriptName, '/Controleur/')) !== false) {
    $BASE = rtrim(substr($scriptName, 0, $pos), '/');
} elseif (($pos = strpos($scriptName, '/Vue/')) !== false) {
    $BASE = rtrim(substr($scriptName, 0, $pos), '/');
} else {
    $BASE = rtrim(dirname(dirname($scriptName)), '/');
}
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
    <link rel="stylesheet" href="<?= $backBoRootWeb ?>/event-center-admin.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../event-center-admin.css')); ?>">
  <?php if (file_exists(__DIR__ . '/../unified-table-admin.css')): ?>
  <link rel="stylesheet" href="<?= $backBoRootWeb ?>/unified-table-admin.css?v=<?= urlencode((string) filemtime(__DIR__ . '/../unified-table-admin.css')) ?>">
  <?php endif; ?>
<link rel="icon" type="image/png" sizes="16x16" href="<?= htmlspecialchars(dirname($backBoRootWeb) . '/public/images/favicon-16.png') ?>">
  <link rel="icon" type="image/png" sizes="32x32" href="<?= htmlspecialchars(dirname($backBoRootWeb) . '/public/images/favicon-32.png') ?>">
  <link rel="shortcut icon" type="image/png" href="<?= htmlspecialchars(dirname($backBoRootWeb) . '/public/images/favicon-32.png') ?>">
  <link rel="apple-touch-icon" sizes="180x180" href="<?= htmlspecialchars(dirname($backBoRootWeb) . '/public/images/apple-touch-icon.png') ?>">
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
      <div class="content-wrapper event-center-shell">

        

        <?php
          $ecActiveTab = 'events';
          $eventSearch = trim((string)($_GET['search'] ?? ''));
          $eventStatusFilter = trim((string)($_GET['status'] ?? ''));
          $eventTypeFilter = trim((string)($_GET['type'] ?? ''));
          $eventPerPage = max(5, min(25, (int)($_GET['per_page'] ?? 10)));
          $eventPage = max(1, (int)($_GET['page'] ?? 1));
          $eventTypes = [];
          foreach ($evenements as $eventItem) {
              $t = method_exists($eventItem, 'getType') ? (string)$eventItem->getType() : '';
              if ($t !== '') { $eventTypes[$t] = $t; }
          }
          ksort($eventTypes);
          $filteredEvents = array_values(array_filter($evenements, function($eventItem) use ($eventSearch, $eventStatusFilter, $eventTypeFilter) {
              $title = method_exists($eventItem, 'getTitre') ? (string)$eventItem->getTitre() : '';
              $type = method_exists($eventItem, 'getType') ? (string)$eventItem->getType() : '';
              $status = method_exists($eventItem, 'getStatut') ? (string)$eventItem->getStatut() : '';
              $place = method_exists($eventItem, 'getLieu') ? (string)$eventItem->getLieu() : '';
              $haystack = strtolower($title . ' ' . $type . ' ' . $status . ' ' . $place);
              if ($eventSearch !== '' && strpos($haystack, strtolower($eventSearch)) === false) { return false; }
              if ($eventStatusFilter !== '' && $status !== $eventStatusFilter) { return false; }
              if ($eventTypeFilter !== '' && $type !== $eventTypeFilter) { return false; }
              return true;
          }));
          $eventTotalRows = count($filteredEvents);
          $eventTotalPages = max(1, (int)ceil($eventTotalRows / $eventPerPage));
          $eventPage = min($eventPage, $eventTotalPages);
          $pagedEvents = array_slice($filteredEvents, ($eventPage - 1) * $eventPerPage, $eventPerPage);
          $eventQueryBase = $_GET;
          $eventQueryBase['action'] = $eventQueryBase['action'] ?? 'admin';
          unset($eventQueryBase['page']);
          $eventPageUrl = function($page) use ($eventQueryBase) {
              $q = $eventQueryBase;
              $q['page'] = $page;
              return '?' . http_build_query($q);
          };
          $eventFinished = 0;
          $today = date('Y-m-d');
          foreach ($evenements as $eventItem) {
              $d = method_exists($eventItem, 'getDateEvenement') ? substr((string)$eventItem->getDateEvenement(), 0, 10) : '';
              if ($d !== '' && $d < $today) { $eventFinished++; }
          }
        ?>

        <?php if (isset($_GET['deleted'])): ?>
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="mdi mdi-check-circle me-2"></i> <span data-i18n="events.alert.deleted">Event deleted successfully.</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>
        <?php if (isset($_GET['created'])): ?>
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="mdi mdi-check-circle me-2"></i> <span data-i18n="events.alert.created">Event created successfully.</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>
        <?php if (isset($_GET['updated'])): ?>
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="mdi mdi-check-circle me-2"></i> <span data-i18n="events.alert.updated">Event updated successfully.</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>

        <section class="ec-page-head">
          <div>
            <p class="ec-kicker" data-i18n="eventCenter.kicker">Event Center</p>
            <h1 data-i18n="events.title">Event administration</h1>
            <p data-i18n="events.subtitle">Track events, participation, forums, and moderation activity from one clean dashboard.</p>
          </div>
          <div class="ec-page-actions">
            <button type="button" class="ec-primary-btn" onclick="openModal()">
              <i class="mdi mdi-plus me-1"></i> <span data-i18n="events.action.new">New event</span>
            </button>
          </div>
        </section>

        <nav class="ec-entity-tabs" aria-label="Event Center sections">
          <a class="ec-entity-tab is-active" href="<?= htmlspecialchars($BASE . '/Controleur/evenementC.php?action=admin') ?>">
            <span class="ec-tab-icon"><i class="mdi mdi-calendar-star"></i></span>
            <span><strong data-i18n="eventCenter.tab.events">Events</strong><small data-i18n="eventCenter.tab.eventsSub">Planning and participation</small></span>
          </a>
          <a class="ec-entity-tab" href="<?= htmlspecialchars($BASE . '/Controleur/forumC.php?action=admin') ?>">
            <span class="ec-tab-icon"><i class="mdi mdi-forum"></i></span>
            <span><strong data-i18n="eventCenter.tab.forum">Forum</strong><small data-i18n="eventCenter.tab.forumSub">Discussions and moderation</small></span>
          </a>
        </nav>

        <section class="ec-statistics-panel" data-ec-stats>
          <div class="ec-section-head">
            <div>
              <h2 data-i18n="events.stats.title">Workspace statistics</h2>
              <p data-i18n="events.stats.subtitle">Live indicators and charts for event activity.</p>
            </div>
            <button type="button" class="ec-secondary-btn" data-ec-stats-toggle data-i18n="common.hideStatistics">Hide statistics</button>
          </div>

          <div class="ec-kpi-grid">
            <article class="ec-kpi-card ec-kpi-purple"><span data-i18n="events.kpi.total">Total events</span><strong><?= $kpi_total ?></strong><small><?= $kpi_actifs ?> <span data-i18n="events.kpi.activeNow">active now</span></small></article>
            <article class="ec-kpi-card ec-kpi-pink"><span data-i18n="events.kpi.registrations">Registrations</span><strong><?= $kpi_inscrits ?></strong><small data-i18n="events.kpi.totalParticipants">Total participants</small></article>
            <article class="ec-kpi-card ec-kpi-green"><span data-i18n="events.kpi.fillRate">Fill rate</span><strong><?= $kpi_taux ?>%</strong><small data-i18n="events.kpi.averageCapacity">Average capacity usage</small></article>
            <article class="ec-kpi-card ec-kpi-blue"><span data-i18n="events.kpi.upcoming">Upcoming</span><strong><?= $kpi_upcoming ?></strong><small data-i18n="events.kpi.futureEvents">Future events</small></article>
            <article class="ec-kpi-card ec-kpi-magenta"><span data-i18n="events.kpi.pending">Pending</span><strong><?= $pendingEvents ?></strong><small data-i18n="events.kpi.needValidation">Need validation</small></article>
            <article class="ec-kpi-card ec-kpi-yellow"><span data-i18n="events.kpi.finished">Finished</span><strong><?= $eventFinished ?></strong><small data-i18n="events.kpi.pastEvents">Past events</small></article>
          </div>

          <div class="ec-stats-body ec-stats-body-events">
            <article class="ec-chart-card">
              <div class="ec-chart-head"><h3 data-i18n="events.chart.participants">Participants evolution</h3><p data-i18n="events.chart.participantsSub">Registration volume over the last months.</p></div>
              <div class="ec-chart-canvas"><canvas id="participantsChart"></canvas></div>
            </article>
            <article class="ec-chart-card">
              <div class="ec-chart-head"><h3 data-i18n="events.chart.perMonth">Events per month</h3><p data-i18n="events.chart.perMonthSub">Scheduled event volume.</p></div>
              <div class="ec-chart-canvas"><canvas id="eventsChart"></canvas></div>
            </article>
            <article class="ec-chart-card">
              <div class="ec-chart-head"><h3 data-i18n="events.chart.typeDistribution">Type distribution</h3><p data-i18n="events.chart.typeDistributionSub">Breakdown by event type.</p></div>
              <div class="ec-chart-canvas"><canvas id="typeChart"></canvas></div>
            </article>
          </div>
        </section>

        <section class="ec-filter-card">
          <div class="ec-filter-head">
            <div>
              <h2 data-i18n="events.filter.title">Admin filters</h2>
              <p data-i18n="events.filter.subtitle">Filter by title, type, status, or location before reviewing the list.</p>
            </div>
          </div>
          <form class="ec-filter-grid" method="get" action="<?= htmlspecialchars($_SERVER['PHP_SELF'] ?? '') ?>">
            <?php if (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/Controleur/') !== false): ?><input type="hidden" name="action" value="admin"><?php endif; ?>
            <label class="ec-filter-field"><span data-i18n="common.search">Search</span><input type="search" name="search" value="<?= htmlspecialchars($eventSearch) ?>" placeholder="Title, type, status, place..." data-i18n-placeholder="events.filter.searchPlaceholder"></label>
            <label class="ec-filter-field"><span data-i18n="common.status">Status</span><select name="status"><option value="" data-i18n-opt="common.allStatuses">All statuses</option><?php foreach (['actif','en_attente','brouillon','archive','termine'] as $st): ?><option value="<?= htmlspecialchars($st) ?>" <?= $eventStatusFilter === $st ? 'selected' : '' ?> data-i18n-opt="events.status.<?= htmlspecialchars($st) ?>"><?= htmlspecialchars(ucfirst(str_replace('_',' ', $st))) ?></option><?php endforeach; ?></select></label>
            <label class="ec-filter-field"><span data-i18n="events.table.type">Type</span><select name="type"><option value="" data-i18n-opt="events.filter.allTypes">All types</option><?php foreach ($eventTypes as $t): ?><option value="<?= htmlspecialchars($t) ?>" <?= $eventTypeFilter === $t ? 'selected' : '' ?> data-i18n-opt="events.type.<?= htmlspecialchars($t) ?>"><?= htmlspecialchars(ucfirst($t)) ?></option><?php endforeach; ?></select></label>
            <label class="ec-filter-field"><span data-i18n="common.perPage">Per page</span><select name="per_page"><option value="10" <?= $eventPerPage === 10 ? 'selected' : '' ?>>10 / page</option><option value="15" <?= $eventPerPage === 15 ? 'selected' : '' ?>>15 / page</option><option value="25" <?= $eventPerPage === 25 ? 'selected' : '' ?>>25 / page</option></select></label>
            <div class="ec-filter-actions"><button class="ec-primary-btn" type="submit" data-i18n="common.applyFilters">Apply filters</button><a class="ec-soft-btn" href="?action=admin" data-i18n="common.reset">Reset</a></div>
          </form>
        </section>

        <section class="ec-table-card">
          <div class="ec-table-head">
            <div>
              <h2 data-i18n="events.table.title">Event List</h2>
              <p><?= $eventTotalRows ?> <span data-i18n="events.table.matchCount">event(s) match the current filters.</span></p>
            </div>
          </div>
          <div id="ecResultsRegion" class="ec-results-region">
            <div class="ec-table-wrap">
              <table class="ec-table ec-events-table">
                <thead>
                  <tr>
                    <th class="ec-col-check"><input type="checkbox" aria-label="Select all events" data-i18n-aria-label="events.table.selectAll"></th>
                    <th data-i18n="events.table.image">Image</th>
                    <th onclick="sortTable(2)" style="cursor:pointer;"><span data-i18n="events.table.event">Event</span> <span class="sort-icon" id="sort-icon-2"></span></th>
                    <th onclick="sortTable(3)" style="cursor:pointer;"><span data-i18n="events.table.type">Type</span> <span class="sort-icon" id="sort-icon-3"></span></th>
                    <th onclick="sortTable(4)" style="cursor:pointer;"><span data-i18n="common.status">Status</span> <span class="sort-icon" id="sort-icon-4"></span></th>
                    <th onclick="sortTable(5)" style="cursor:pointer;"><span data-i18n="events.table.date">Date</span> <span class="sort-icon" id="sort-icon-5"></span></th>
                    <th onclick="sortTable(6)" style="cursor:pointer;"><span data-i18n="events.table.place">Place</span> <span class="sort-icon" id="sort-icon-6"></span></th>
                    <th onclick="sortTable(7)" style="cursor:pointer;"><span data-i18n="events.table.capacity">Capacity</span> <span class="sort-icon" id="sort-icon-7"></span></th>
                    <th data-i18n="common.actions">Actions</th>
                  </tr>
                </thead>
                <tbody id="tableBody">
                  <?php if (empty($pagedEvents)): ?>
                    <tr><td colspan="9"><div class="ec-empty-state"><span><i class="mdi mdi-calendar-remove"></i></span><strong data-i18n="events.empty.title">No event found</strong><p data-i18n="events.empty.subtitle">Try another filter or create a new event.</p></div></td></tr>
                  <?php else: ?>
                    <?php foreach ($pagedEvents as $event): ?>
                      <?php $percentage = ($event->getCapacite() > 0) ? ($event->getNbInscrits() / $event->getCapacite()) * 100 : 0; ?>
                      <tr data-event-id="<?= $event->getId() ?>">
                        <td><input type="checkbox" class="event-checkbox" aria-label="Select event"></td>
                        <td>
                          <?php if ($event->getImage()): ?>
                            <img class="ec-event-thumb" src="<?= $BASE ?>/<?= htmlspecialchars($event->getImage()) ?>" alt="">
                          <?php else: ?>
                            <span class="ec-event-thumb ec-event-thumb-empty"><i class="mdi mdi-calendar"></i></span>
                          <?php endif; ?>
                        </td>
                        <td><div class="ec-main-cell"><strong><?= htmlspecialchars($event->getTitre()) ?></strong><span>ID #<?= (int)$event->getId() ?></span></div></td>
                        <td><span class="ec-chip" data-i18n="events.type.<?= htmlspecialchars($event->getType()) ?>"><?= htmlspecialchars(ucfirst($event->getType())) ?></span></td>
                        <td><span class="ec-badge ec-status-<?= htmlspecialchars($event->getStatut()) ?>" data-i18n="events.status.<?= htmlspecialchars($event->getStatut()) ?>"><?= htmlspecialchars($event->getStatut()) ?></span></td>
                        <td class="ec-date-cell"><?= htmlspecialchars(date('Y-m-d', strtotime($event->getDateEvenement()))) ?></td>
                        <td><?php if ($event->getLieu()): ?><?= htmlspecialchars($event->getLieu()) ?><?php else: ?><span data-i18n="events.place.online">Online</span><?php endif; ?></td>
                        <td>
                          <div class="ec-progress-wrap"><div class="ec-progress-bar"><span style="width:<?= min(100, $percentage) ?>%"></span></div><small><?= (int)$event->getNbInscrits() ?>/<?= (int)$event->getCapacite() ?></small></div>
                        </td>
                        <td><div class="ec-actions"><button type="button" class="ec-action-btn ec-action-primary" onclick="editEvent(<?= (int)$event->getId() ?>)"><i class="mdi mdi-pencil me-1"></i><span data-i18n="common.edit">Edit</span></button><button type="button" class="ec-action-btn ec-action-danger" onclick="deleteEvent(<?= (int)$event->getId() ?>, '<?= htmlspecialchars(addslashes($event->getTitre())) ?>')"><i class="mdi mdi-delete me-1"></i><span data-i18n="common.delete">Delete</span></button></div></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
            <div class="ec-pagination">
              <p><span data-i18n="common.page">Page</span> <?= $eventPage ?> <span data-i18n="common.of">of</span> <?= $eventTotalPages ?> (<?= $eventTotalRows ?> <span data-i18n="events.pagination.events">events</span>)</p>
              <nav aria-label="Event pagination">
                <a class="ec-page-btn <?= $eventPage <= 1 ? 'is-disabled' : '' ?>" href="<?= htmlspecialchars($eventPageUrl(max(1, $eventPage - 1))) ?>">«</a>
                <?php for ($p = 1; $p <= $eventTotalPages; $p++): ?>
                  <?php if ($p === 1 || $p === $eventTotalPages || abs($p - $eventPage) <= 1): ?>
                    <a class="ec-page-btn <?= $p === $eventPage ? 'is-active' : '' ?>" href="<?= htmlspecialchars($eventPageUrl($p)) ?>"><?= $p ?></a>
                  <?php elseif (abs($p - $eventPage) === 2): ?>
                    <span class="ec-page-ellipsis">…</span>
                  <?php endif; ?>
                <?php endfor; ?>
                <a class="ec-page-btn <?= $eventPage >= $eventTotalPages ? 'is-disabled' : '' ?>" href="<?= htmlspecialchars($eventPageUrl(min($eventTotalPages, $eventPage + 1))) ?>">»</a>
              </nav>
            </div>
          </div>
        </section>

      

      </div>

      <!-- content-wrapper ends -->

      <!-- Footer -->
      <footer class="footer">
        <div class="d-sm-flex justify-content-center justify-content-sm-between">
          <span class="text-muted d-block text-center text-sm-left d-sm-inline-block">Copyright &copy; cre8connect 2024</span>
          <span class="float-none float-sm-right d-block mt-1 mt-sm-0 text-center" data-i18n="events.footer">Event management</span>
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
          <h5 class="modal-title" id="createModalLabel"><i class="mdi mdi-plus-circle me-2"></i><span data-i18n="events.modal.createTitle">New event</span></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold" data-i18n="events.form.titleRequired">Title *</label>
            <input type="text" name="titre" class="form-control" required/>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold" data-i18n="events.form.typeRequired">Type *</label>
              <select name="type" class="form-select">
                <option value="formation" data-i18n-opt="events.type.formation">Training</option>
                <option value="webinaire" data-i18n-opt="events.type.webinaire">Webinar</option>
                <option value="meetup" data-i18n-opt="events.type.meetup">Meetup</option>
                <option value="atelier" data-i18n-opt="events.type.atelier">Workshop</option>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold" data-i18n="events.form.statusRequired">Status *</label>
              <select name="statut" class="form-select">
                <option value="brouillon" data-i18n-opt="events.status.brouillon">Draft</option>
                <option value="en_attente" data-i18n-opt="events.status.en_attente">Pending</option>
                <option value="actif" data-i18n-opt="events.status.actif">Active</option>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold" data-i18n="events.form.description">Description</label>
            <textarea name="description" class="form-control" rows="3"></textarea>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold" data-i18n="events.form.dateRequired">Date *</label>
              <input type="date" name="date_evenement" class="form-control" required/>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold" data-i18n="events.form.durationHours">Duration (hours)</label>
              <input type="number" name="duree" class="form-control"/>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold" data-i18n="events.form.placeCity">Place (city)</label>
              <input type="text" name="lieu" class="form-control"/>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold" data-i18n="events.form.capacity">Capacity</label>
              <input type="number" name="capacite" class="form-control" value="50"/>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold" data-i18n="events.form.fullAddress">Full address</label>
            <input type="text" name="adresse_complete" class="form-control" placeholder="Ex: 45 Avenue Ahmed Tlili, Ariana" data-i18n-placeholder="events.form.addressPlaceholder"/>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold" data-i18n="events.form.poster">Poster</label>
            <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/jpg,image/webp" onchange="previewImage(this, 'createPreview')"/>
            <div id="createPreview" class="mt-2"></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-i18n="common.cancel">Cancel</button>
          <button type="submit" class="btn text-white" style="background: linear-gradient(135deg, #9B5DE0, #B771E5);" data-i18n="common.create">Create</button>
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
          <h5 class="modal-title" id="editModalLabel"><i class="mdi mdi-pencil me-2"></i><span data-i18n="events.modal.editTitle">Edit event</span></h5>
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
                <option value="formation" data-i18n-opt="events.type.formation">Training</option>
                <option value="webinaire" data-i18n-opt="events.type.webinaire">Webinar</option>
                <option value="meetup" data-i18n-opt="events.type.meetup">Meetup</option>
                <option value="atelier" data-i18n-opt="events.type.atelier">Workshop</option>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">Statut</label>
              <select name="statut" id="edit_statut" class="form-select">
                <option value="brouillon" data-i18n-opt="events.status.brouillon">Draft</option>
                <option value="en_attente" data-i18n-opt="events.status.en_attente">Pending</option>
                <option value="actif" data-i18n-opt="events.status.actif">Active</option>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold" data-i18n="events.form.description">Description</label>
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
              <label class="form-label fw-semibold" data-i18n="events.form.capacity">Capacity</label>
              <input type="number" name="capacite" id="edit_capacite" class="form-control"/>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold" data-i18n="events.form.fullAddress">Full address</label>
            <input type="text" name="adresse_complete" id="edit_adresse" class="form-control"/>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold" data-i18n="events.form.poster">Poster</label>
            <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/jpg,image/webp" onchange="previewImage(this, 'editPreview')"/>
            <div id="editPreview" class="mt-2"></div>
            <div id="currentImageInfo" class="mt-2 text-muted" style="font-size:0.8rem;"></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-i18n="common.cancel">Cancel</button>
          <button type="submit" class="btn text-white" style="background: linear-gradient(135deg, #9B5DE0, #B771E5);" data-i18n="common.update">Update</button>
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

<script>
window.cre8BackRegisterTranslations && window.cre8BackRegisterTranslations({
  en: {
    "eventCenter.kicker": "Event Center",
    "eventCenter.tab.events": "Events",
    "eventCenter.tab.eventsSub": "Planning and participation",
    "eventCenter.tab.forum": "Forum",
    "eventCenter.tab.forumSub": "Discussions and moderation",
    "events.title": "Event administration",
    "events.subtitle": "Track events, participation, forums, and moderation activity from one clean dashboard.",
    "events.action.new": "New event",
    "events.alert.deleted": "Event deleted successfully.",
    "events.alert.created": "Event created successfully.",
    "events.alert.updated": "Event updated successfully.",
    "events.alert.eventCount": "event(s)",
    "events.alert.pendingValidation": "need validation.",
    "events.stats.title": "Workspace statistics",
    "events.stats.subtitle": "Live indicators and charts for event activity.",
    "events.kpi.total": "Total events",
    "events.kpi.activeNow": "active now",
    "events.kpi.registrations": "Registrations",
    "events.kpi.totalParticipants": "Total participants",
    "events.kpi.fillRate": "Fill rate",
    "events.kpi.averageCapacity": "Average capacity usage",
    "events.kpi.upcoming": "Upcoming",
    "events.kpi.futureEvents": "Future events",
    "events.kpi.pending": "Pending",
    "events.kpi.needValidation": "Need validation",
    "events.kpi.finished": "Finished",
    "events.kpi.pastEvents": "Past events",
    "events.chart.participants": "Participants evolution",
    "events.chart.participantsSub": "Registration volume over the last months.",
    "events.chart.perMonth": "Events per month",
    "events.chart.perMonthSub": "Scheduled event volume.",
    "events.chart.typeDistribution": "Type distribution",
    "events.chart.typeDistributionSub": "Breakdown by event type.",
    "events.filter.title": "Admin filters",
    "events.filter.subtitle": "Filter by title, type, status, or location before reviewing the list.",
    "events.filter.searchPlaceholder": "Title, type, status, place...",
    "events.filter.allTypes": "All types",
    "events.table.title": "Event List",
    "events.table.matchCount": "event(s) match the current filters.",
    "events.table.selectAll": "Select all events",
    "events.table.image": "Image",
    "events.table.event": "Event",
    "events.table.type": "Type",
    "events.table.date": "Date",
    "events.table.place": "Place",
    "events.table.capacity": "Capacity",
    "events.empty.title": "No event found",
    "events.empty.subtitle": "Try another filter or create a new event.",
    "events.place.online": "Online",
    "events.pagination.events": "events",
    "events.modal.createTitle": "New event",
    "events.modal.editTitle": "Edit event",
    "events.form.titleRequired": "Title *",
    "events.form.typeRequired": "Type *",
    "events.form.statusRequired": "Status *",
    "events.form.description": "Description",
    "events.form.dateRequired": "Date *",
    "events.form.durationHours": "Duration (hours)",
    "events.form.placeCity": "Place (city)",
    "events.form.capacity": "Capacity",
    "events.form.fullAddress": "Full address",
    "events.form.addressPlaceholder": "Ex: 45 Avenue Ahmed Tlili, Ariana",
    "events.form.poster": "Poster",
    "events.footer": "Event management",
    "events.js.loadError": "Error while loading event details",
    "events.js.deleteConfirm": "Delete event",
    "events.status.actif": "Active",
    "events.status.en_attente": "Pending",
    "events.status.brouillon": "Draft",
    "events.status.archive": "Archived",
    "events.status.termine": "Finished",
    "events.type.formation": "Training",
    "events.type.webinaire": "Webinar",
    "events.type.meetup": "Meetup",
    "events.type.atelier": "Workshop",
    "common.allStatuses": "All statuses",
    "common.perPage": "Per page",
    "common.cancel": "Cancel",
    "common.create": "Create",
    "common.update": "Update"
  },
  fr: {
    "eventCenter.kicker": "Centre événements",
    "eventCenter.tab.events": "Événements",
    "eventCenter.tab.eventsSub": "Planification et participation",
    "eventCenter.tab.forum": "Forum",
    "eventCenter.tab.forumSub": "Discussions et modération",
    "events.title": "Administration des événements",
    "events.subtitle": "Suivez les événements, la participation, les forums et la modération depuis un seul tableau de bord.",
    "events.action.new": "Nouvel événement",
    "events.alert.deleted": "Événement supprimé avec succès.",
    "events.alert.created": "Événement créé avec succès.",
    "events.alert.updated": "Événement mis à jour avec succès.",
    "events.alert.eventCount": "événement(s)",
    "events.alert.pendingValidation": "en attente de validation.",
    "events.stats.title": "Statistiques de l'espace",
    "events.stats.subtitle": "Indicateurs et graphiques en direct pour l'activité des événements.",
    "events.kpi.total": "Total événements",
    "events.kpi.activeNow": "actifs maintenant",
    "events.kpi.registrations": "Inscriptions",
    "events.kpi.totalParticipants": "Total participants",
    "events.kpi.fillRate": "Taux de remplissage",
    "events.kpi.averageCapacity": "Utilisation moyenne de la capacité",
    "events.kpi.upcoming": "À venir",
    "events.kpi.futureEvents": "Événements futurs",
    "events.kpi.pending": "En attente",
    "events.kpi.needValidation": "À valider",
    "events.kpi.finished": "Terminés",
    "events.kpi.pastEvents": "Événements passés",
    "events.chart.participants": "Évolution des participants",
    "events.chart.participantsSub": "Volume des inscriptions sur les derniers mois.",
    "events.chart.perMonth": "Événements par mois",
    "events.chart.perMonthSub": "Volume des événements planifiés.",
    "events.chart.typeDistribution": "Répartition par type",
    "events.chart.typeDistributionSub": "Répartition selon le type d'événement.",
    "events.filter.title": "Filtres admin",
    "events.filter.subtitle": "Filtrez par titre, type, statut ou lieu avant de consulter la liste.",
    "events.filter.searchPlaceholder": "Titre, type, statut, lieu...",
    "events.filter.allTypes": "Tous les types",
    "events.table.title": "Liste des événements",
    "events.table.matchCount": "événement(s) correspondent aux filtres actuels.",
    "events.table.selectAll": "Sélectionner tous les événements",
    "events.table.image": "Image",
    "events.table.event": "Événement",
    "events.table.type": "Type",
    "events.table.date": "Date",
    "events.table.place": "Lieu",
    "events.table.capacity": "Capacité",
    "events.empty.title": "Aucun événement trouvé",
    "events.empty.subtitle": "Essayez un autre filtre ou créez un nouvel événement.",
    "events.place.online": "En ligne",
    "events.pagination.events": "événements",
    "events.modal.createTitle": "Nouvel événement",
    "events.modal.editTitle": "Modifier l'événement",
    "events.form.titleRequired": "Titre *",
    "events.form.typeRequired": "Type *",
    "events.form.statusRequired": "Statut *",
    "events.form.description": "Description",
    "events.form.dateRequired": "Date *",
    "events.form.durationHours": "Durée (heures)",
    "events.form.placeCity": "Lieu (ville)",
    "events.form.capacity": "Capacité",
    "events.form.fullAddress": "Adresse complète",
    "events.form.addressPlaceholder": "Ex: 45 Avenue Ahmed Tlili, Ariana",
    "events.form.poster": "Affiche",
    "events.footer": "Gestion des événements",
    "events.js.loadError": "Erreur lors du chargement des détails de l'événement",
    "events.js.deleteConfirm": "Supprimer l'événement",
    "events.status.actif": "Actif",
    "events.status.en_attente": "En attente",
    "events.status.brouillon": "Brouillon",
    "events.status.archive": "Archivé",
    "events.status.termine": "Terminé",
    "events.type.formation": "Formation",
    "events.type.webinaire": "Webinaire",
    "events.type.meetup": "Meetup",
    "events.type.atelier": "Atelier",
    "common.allStatuses": "Tous les statuts",
    "common.perPage": "Par page",
    "common.cancel": "Annuler",
    "common.create": "Créer",
    "common.update": "Mettre à jour"
  }
});
</script>

<script src="<?= $backBoRootWeb ?>/event-center-admin.js?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../event-center-admin.js')); ?>"></script>
<script src="<?= $backBoUtilisateurWeb ?>/assets/js/off-canvas.js"></script>
<script src="<?= $backBoUtilisateurWeb ?>/assets/js/hoverable-collapse.js"></script>
<script src="<?= $backBoUtilisateurWeb ?>/assets/js/misc.js"></script>
<script src="<?= $backBoUtilisateurWeb ?>/assets/js/settings.js"></script>
<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Chart.js CDN fallback -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
  const EVENT_APP_BASE = <?= json_encode($BASE, JSON_UNESCAPED_SLASHES) ?>;
  function eventAppUrl(path) {
    return EVENT_APP_BASE.replace(/\/$/, '') + '/' + String(path || '').replace(/^\/+/, '');
  }
  function normalizeEventDate(value) {
    const raw = String(value || '');
    if (!raw) return '';
    return raw.split('T')[0].split(' ')[0].slice(0, 10);
  }

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
    fetch(eventAppUrl('Controleur/evenementC.php?action=get&id=' + encodeURIComponent(id)))
      .then(r => r.json())
      .then(data => {
        document.getElementById('edit_id').value = data.id;
        document.getElementById('edit_titre').value = data.titre;
        document.getElementById('edit_description').value = data.description;
        document.getElementById('edit_duree').value = data.duree;
        document.getElementById('edit_date').value = normalizeEventDate(data.date_evenement);
        document.getElementById('edit_type').value = data.type;
        document.getElementById('edit_statut').value = data.statut;
        document.getElementById('edit_lieu').value = data.lieu;
        document.getElementById('edit_capacite').value = data.capacite;
        document.getElementById('edit_adresse').value = data.adresse_complete || '';
        if (data.image) {
          document.getElementById('currentImageInfo').innerHTML = '<strong>Current image:</strong><br><img src="' + eventAppUrl(data.image) + '" style="max-width:100px;border-radius:8px;margin-top:5px;">';
        } else {
          document.getElementById('currentImageInfo').innerHTML = '<strong>No image</strong>';
        }
        document.getElementById('editForm').action = eventAppUrl('Controleur/evenementC.php?action=edit&id=' + encodeURIComponent(id));
        document.getElementById('editPreview').innerHTML = '';
        const m = new bootstrap.Modal(document.getElementById('editModal'));
        m.show();
      })
      .catch(() => alert((window.cre8BackText && window.cre8BackText('events.js.loadError')) || 'Error while loading event details'));
  }

  function deleteEvent(id, titre) {
    const message = ((window.cre8BackText && window.cre8BackText('events.js.deleteConfirm')) || 'Delete event') + ' "' + titre + '" ?';
    if (confirm(message)) {
      window.location.href = eventAppUrl('Controleur/evenementC.php?action=delete&id=' + encodeURIComponent(id));
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
