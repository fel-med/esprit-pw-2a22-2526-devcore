<?php
// ── Compute backoffice asset paths before any HTML output ──────────────────
require_once __DIR__ . '/../layout/bo_paths.php';

if (!isset($forums))           { $forums = []; }
if (!isset($messages_signales)){ $messages_signales = []; }
if (!isset($stats)) {
    $stats = ['total_forums'=>0,'total_messages'=>0,'total_participants'=>0,'forums_actifs'=>0,'messages_signales'=>0,'months_labels'=>[],'messages_data'=>[],'top_forums'=>[],'top_contributeurs'=>[]];
}

// If accessed directly (not via controller), fetch data ourselves
if (empty($forums) && basename($_SERVER['SCRIPT_NAME']) !== 'forumC.php') {
    try {
        require_once __DIR__ . '/../../../config.php';
        $pdo = config::getConnexion();
        $stmt = $pdo->query("
            SELECT f.*,
                   COALESCE(e.TitreFormation,'Événement') as nom_evenement,
                   COALESCE(u.nom,'Admin') as nom_utilisateur,
                   (SELECT COUNT(*) FROM forum_messages WHERE idForum = f.idForum) as nb_messages
            FROM forum f
            LEFT JOIN evenement e ON f.idFormation = e.idFormation
            LEFT JOIN utilisateur u ON f.idUtilisateur = u.id
            ORDER BY f.dateCreation DESC
        ");
        $forums = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { $forums = []; }
}

$BASE               = rtrim(str_replace('\\','/',dirname(dirname($_SERVER['SCRIPT_NAME']))),'/');
$total_forums       = count($forums);
$total_messages     = array_sum(array_column($forums,'nb_messages'));
$total_participants = count(array_unique(array_column($forums,'idUtilisateur')));
$forums_actifs      = count(array_filter($forums, fn($f) => ($f['est_actif'] ?? 0) == 1));
$total_signales     = count($messages_signales);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <?php require_once __DIR__ . '/../layout/early-theme.php'; cre8_bo_early_theme_print_head_script(); ?>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>cre8connect Admin – Forums</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= $backBoUtilisateurWeb ?? '../utilisateur' ?>/assets/vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="<?= $backBoUtilisateurWeb ?? '../utilisateur' ?>/assets/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="<?= $backBoUtilisateurWeb ?? '../utilisateur' ?>/assets/css/style.css">
  <link rel="stylesheet" href="<?= ($backBoRootWeb ?? '..') ?>/layout/back-layout.css?v=<?php echo urlencode((string)filemtime(__DIR__.'/../layout/back-layout.css')); ?>">
    <link rel="stylesheet" href="<?= $backBoRootWeb ?>/event-center-admin.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../event-center-admin.css')); ?>">
  <?php if (file_exists(__DIR__ . '/../unified-table-admin.css')): ?>
  <link rel="stylesheet" href="<?= $backBoRootWeb ?>/unified-table-admin.css?v=<?= urlencode((string) filemtime(__DIR__ . '/../unified-table-admin.css')) ?>">
  <?php endif; ?>
<link rel="icon" type="image/png" sizes="16x16" href="<?= htmlspecialchars(dirname($backBoRootWeb) . '/public/images/favicon-16.png') ?>">
  <link rel="icon" type="image/png" sizes="32x32" href="<?= htmlspecialchars(dirname($backBoRootWeb) . '/public/images/favicon-32.png') ?>">
  <link rel="shortcut icon" type="image/png" href="<?= htmlspecialchars(dirname($backBoRootWeb) . '/public/images/favicon-32.png') ?>">
  <link rel="apple-touch-icon" sizes="180x180" href="<?= htmlspecialchars(dirname($backBoRootWeb) . '/public/images/apple-touch-icon.png') ?>">
  <style>
    body.light-mode { background-color:#f8fafc!important; color:#111827!important; }
    body.light-mode .container-scroller,body.light-mode .page-body-wrapper,body.light-mode .main-panel,body.light-mode .content-wrapper,body.light-mode .footer { background-color:#ffffff!important; color:#111827!important; border-color:#e2e8f0!important; }
    body.light-mode .card,body.light-mode .card-body,body.light-mode .table,body.light-mode .table-responsive,body.light-mode .modal-content,body.light-mode .form-control,body.light-mode .form-select { background-color:#ffffff!important; color:#111827!important; border-color:#d1d5db!important; }
    body.light-mode .table thead,body.light-mode .table thead th { background:#f3f0ff!important; color:#5b4fff!important; border-color:#e5e7eb!important; }
    body.light-mode .table tbody tr,body.light-mode .table tbody td { background:#ffffff!important; color:#111827!important; border-color:#e5e7eb!important; }
    body.light-mode .table tbody tr:hover,body.light-mode .table tbody tr:hover td { background:#f8fafc!important; }
    body.light-mode .modal-header { background-color:#f1f5f9!important; border-color:#d1d5db!important; }
    body.light-mode .card-title,.light-mode .page-title { color:#111827!important; }
    body:not(.light-mode) { background-color:#0f131d!important; color:#e7ebff!important; }
    body:not(.light-mode) .container-scroller,body:not(.light-mode) .page-body-wrapper,body:not(.light-mode) .main-panel,body:not(.light-mode) .content-wrapper { background-color:#101520!important; color:#e7ebff!important; }
    body:not(.light-mode) .card,body:not(.light-mode) .card-body,body:not(.light-mode) .table,body:not(.light-mode) .modal-content,body:not(.light-mode) .form-control,body:not(.light-mode) .form-select { background-color:rgba(18,24,41,0.96)!important; color:#e7ebff!important; border-color:rgba(255,255,255,0.08)!important; }
    body:not(.light-mode) .card { box-shadow:0 18px 45px rgba(0,0,0,0.18); }
    body:not(.light-mode) .card-title,body:not(.light-mode) .table thead th,body:not(.light-mode) .table td,body:not(.light-mode) .table th { color:#eef3ff!important; }
    body:not(.light-mode) .table thead { background-color:rgba(255,255,255,0.05)!important; }
    body:not(.light-mode) .page-header .page-title { color:#f3f7ff!important; }
    .table-action-btn { min-width:90px; height:36px; border-radius:14px!important; padding:6px 12px!important; font-weight:600; font-size:0.85rem; box-shadow:0 2px 8px rgba(0,0,0,0.12); transition:transform .2s,opacity .2s; display:inline-flex; justify-content:center; align-items:center; white-space:nowrap; }
    .table-action-btn:hover { transform:translateY(-1px); opacity:.95; }
    .sort-icon { display:inline-block; margin-left:4px; font-size:0.7rem; opacity:.5; }
    .sort-icon.active { opacity:1; color:#9B5DE0; }
    .forum-message-card { border-left:4px solid #9B5DE0; margin-bottom:16px; }
    /* Prevent content overlapping the sticky topbar */
    .content-wrapper { padding-top: 1.5rem !important; }
  </style>
</head>
<body class="cre8-admin-layout"><?php cre8_bo_early_theme_print_body_script(); ?>
<div class="container-scroller cre8-admin-page">
  <?php $backActive = 'forum'; require_once __DIR__ . '/../layout/sidebar.php'; ?>
  <div class="container-fluid page-body-wrapper cre8-admin-main">
    <?php require_once __DIR__ . '/../layout/header.php'; ?>
    <div class="main-panel">
      <div class="content-wrapper event-center-shell">
        
        

        <?php
          $ecActiveTab = 'forum';
          $forumSearch = trim((string)($_GET['search'] ?? ''));
          $forumStatusFilter = trim((string)($_GET['status'] ?? ''));
          $forumPerPage = max(5, min(25, (int)($_GET['per_page'] ?? 10)));
          $forumPage = max(1, (int)($_GET['page'] ?? 1));
          $filteredForums = array_values(array_filter($forums, function($forumItem) use ($forumSearch, $forumStatusFilter) {
              $title = (string)($forumItem['TitreForum'] ?? $forumItem['titreForum'] ?? '');
              $eventName = (string)($forumItem['nom_evenement'] ?? '');
              $author = (string)($forumItem['nom_utilisateur'] ?? '');
              $active = (string)($forumItem['est_actif'] ?? '0');
              $haystack = strtolower($title . ' ' . $eventName . ' ' . $author);
              if ($forumSearch !== '' && strpos($haystack, strtolower($forumSearch)) === false) { return false; }
              if ($forumStatusFilter !== '' && $active !== $forumStatusFilter) { return false; }
              return true;
          }));
          $forumTotalRows = count($filteredForums);
          $forumTotalPages = max(1, (int)ceil($forumTotalRows / $forumPerPage));
          $forumPage = min($forumPage, $forumTotalPages);
          $pagedForums = array_slice($filteredForums, ($forumPage - 1) * $forumPerPage, $forumPerPage);
          $forumQueryBase = $_GET;
          $forumQueryBase['action'] = $forumQueryBase['action'] ?? 'admin';
          unset($forumQueryBase['page']);
          $forumPageUrl = function($page) use ($forumQueryBase) {
              $q = $forumQueryBase;
              $q['page'] = $page;
              return '?' . http_build_query($q);
          };
          $forums_inactive = max(0, $total_forums - $forums_actifs);
        ?>

        <section class="ec-page-head">
          <div>
            <p class="ec-kicker" data-i18n="eventCenter.kicker">Event Center</p>
            <h1 data-i18n="forum.title">Forum administration</h1>
            <p data-i18n="forum.subtitle">Supervise forum discussions, reported messages, and community activity.</p>
          </div>
          <div class="ec-page-actions">
            <a href="<?= htmlspecialchars($BASE . '/Controleur/forumC.php?action=creer_forums_auto') ?>" class="ec-primary-btn">
              <i class="mdi mdi-refresh me-1"></i> <span data-i18n="forum.action.createToday">Generate missing forums</span>
            </a>
          </div>
        </section>

        <nav class="ec-entity-tabs" aria-label="Event Center sections">
          <a class="ec-entity-tab" href="<?= htmlspecialchars($BASE . '/Controleur/evenementC.php?action=admin') ?>">
            <span class="ec-tab-icon"><i class="mdi mdi-calendar-star"></i></span>
            <span><strong data-i18n="eventCenter.tab.events">Events</strong><small data-i18n="eventCenter.tab.eventsSub">Planning and participation</small></span>
          </a>
          <a class="ec-entity-tab is-active" href="<?= htmlspecialchars($BASE . '/Controleur/forumC.php?action=admin') ?>">
            <span class="ec-tab-icon"><i class="mdi mdi-forum"></i></span>
            <span><strong data-i18n="eventCenter.tab.forum">Forum</strong><small data-i18n="eventCenter.tab.forumSub">Discussions and moderation</small></span>
          </a>
        </nav>

        <?php if (!empty($_SESSION['forum_admin_flash']) && is_array($_SESSION['forum_admin_flash'])): ?>
          <?php
            $forumFlash = $_SESSION['forum_admin_flash'];
            unset($_SESSION['forum_admin_flash']);
            $forumFlashType = in_array(($forumFlash['type'] ?? ''), ['success', 'info', 'danger'], true) ? $forumFlash['type'] : 'info';
          ?>
          <div class="alert alert-<?= htmlspecialchars($forumFlashType) ?> mb-4" role="alert">
            <?= htmlspecialchars((string)($forumFlash['message'] ?? '')) ?>
          </div>
        <?php endif; ?>

        <section class="ec-statistics-panel" data-ec-stats>
          <div class="ec-section-head">
            <div>
              <h2 data-i18n="forum.stats.title">Workspace statistics</h2>
              <p data-i18n="forum.stats.subtitle">Forum health, messages, activity, and moderation overview.</p>
            </div>
            <button type="button" class="ec-secondary-btn" data-ec-stats-toggle data-i18n="common.hideStatistics">Hide statistics</button>
          </div>

          <div class="ec-kpi-grid">
            <article class="ec-kpi-card ec-kpi-purple"><span data-i18n="forum.kpi.total">Total forums</span><strong><?= $total_forums ?></strong><small data-i18n="forum.kpi.created">forums created</small></article>
            <article class="ec-kpi-card ec-kpi-pink"><span data-i18n="forum.kpi.messages">Messages</span><strong><?= $total_messages ?></strong><small data-i18n="forum.kpi.totalMessages">total messages</small></article>
            <article class="ec-kpi-card ec-kpi-green"><span data-i18n="forum.kpi.participants">Participants</span><strong><?= $total_participants ?></strong><small data-i18n="forum.kpi.activeMembers">active members</small></article>
            <article class="ec-kpi-card ec-kpi-magenta"><span data-i18n="forum.kpi.reported">Reported</span><strong><?= $total_signales ?></strong><small data-i18n="forum.kpi.flagged">messages flagged</small></article>
            <article class="ec-kpi-card ec-kpi-blue"><span data-i18n="forum.kpi.activeForums">Active forums</span><strong><?= $forums_actifs ?></strong><small data-i18n="forum.kpi.currentlyOpen">currently open</small></article>
            <article class="ec-kpi-card ec-kpi-yellow"><span data-i18n="forum.kpi.closed">Closed</span><strong><?= $forums_inactive ?></strong><small data-i18n="forum.kpi.inactiveForums">inactive forums</small></article>
          </div>

          <div class="ec-stats-body ec-stats-body-forum">
            <article class="ec-chart-card ec-chart-wide">
              <div class="ec-chart-head"><h3 data-i18n="forum.chart.messagesEvolution">Messages evolution</h3><p data-i18n="forum.chart.messagesEvolutionSub">Forum messages over the last months.</p></div>
              <div class="ec-chart-canvas"><canvas id="messagesChart"></canvas></div>
            </article>
            <article class="ec-chart-card">
              <div class="ec-chart-head"><h3 data-i18n="forum.chart.topForums">Top forums</h3><p data-i18n="forum.chart.topForumsSub">Most active forum spaces.</p></div>
              <div class="ec-mini-table-wrap">
                <table class="ec-mini-table"><thead><tr><th>#</th><th data-i18n="eventCenter.tab.forum">Forum</th><th data-i18n="forum.table.messages">Messages</th><th data-i18n="forum.table.activity">Activity</th></tr></thead><tbody>
                  <?php foreach (($stats['top_forums'] ?? []) as $i => $f): ?>
                    <tr><td><span class="ec-rank"><?= $i + 1 ?></span></td><td><strong><?= htmlspecialchars($f['TitreForum'] ?? 'Forum') ?></strong></td><td><?= (int)($f['nb_messages'] ?? 0) ?></td><td><span class="ec-chip" data-i18n="<?= ($f['nb_messages'] ?? 0) > 10 ? 'forum.status.active' : 'forum.status.calm' ?>"><?= ($f['nb_messages'] ?? 0) > 10 ? 'Active' : 'Calm' ?></span></td></tr>
                  <?php endforeach; ?>
                </tbody></table>
              </div>
            </article>
            <article class="ec-chart-card">
              <div class="ec-chart-head"><h3 data-i18n="forum.chart.topContributors">Top contributors</h3><p data-i18n="forum.chart.topContributorsSub">Most involved users.</p></div>
              <div class="ec-mini-table-wrap">
                <table class="ec-mini-table"><thead><tr><th>#</th><th data-i18n="forum.table.user">User</th><th data-i18n="forum.table.messages">Messages</th><th data-i18n="forum.table.impact">Impact</th></tr></thead><tbody>
                  <?php foreach (($stats['top_contributeurs'] ?? []) as $i => $u): ?>
                    <tr><td><span class="ec-rank"><?= $i + 1 ?></span></td><td><strong><?= htmlspecialchars($u['nom'] ?? 'Anonymous') ?></strong></td><td><?= (int)($u['nb_messages'] ?? 0) ?></td><td><span class="ec-chip" data-i18n="<?= ($u['nb_messages'] ?? 0) > 20 ? 'forum.status.expert' : 'forum.status.active' ?>"><?= ($u['nb_messages'] ?? 0) > 20 ? 'Expert' : 'Active' ?></span></td></tr>
                  <?php endforeach; ?>
                </tbody></table>
              </div>
            </article>
          </div>
        </section>

        <?php if (!empty($messages_signales)): ?>
          <section class="ec-table-card ec-moderation-card">
            <div class="ec-table-head"><div><h2 data-i18n="forum.reported.title">Reported messages</h2><p><?= count($messages_signales) ?> <span data-i18n="forum.reported.subtitle">message(s) require moderation.</span></p></div></div>
            <div class="ec-message-grid">
              <?php foreach (array_slice($messages_signales, 0, 4) as $msg): ?>
                <article class="ec-message-card"><div><strong><?= htmlspecialchars($msg['titreForum'] ?? '') ?></strong><span><?= htmlspecialchars($msg['nom_utilisateur'] ?? '') ?> · <?= htmlspecialchars(date('d/m/Y H:i', strtotime($msg['dateMessage']))) ?></span></div><p><?= nl2br(htmlspecialchars($msg['message'] ?? '')) ?></p><button type="button" class="ec-action-btn ec-action-danger" onclick="supprimerMessage(<?= (int)$msg['idMessage'] ?>)"><i class="mdi mdi-delete me-1"></i><span data-i18n="common.delete">Delete</span></button></article>
              <?php endforeach; ?>
            </div>
          </section>
        <?php endif; ?>

        <section class="ec-filter-card">
          <div class="ec-filter-head"><div><h2 data-i18n="forum.filter.title">Admin filters</h2><p data-i18n="forum.filter.subtitle">Filter by forum title, event, author, or activity status.</p></div></div>
          <form class="ec-filter-grid" method="get" action="<?= htmlspecialchars($_SERVER['PHP_SELF'] ?? '') ?>">
            <?php if (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/Controleur/') !== false): ?><input type="hidden" name="action" value="admin"><?php endif; ?>
            <label class="ec-filter-field"><span data-i18n="common.search">Search</span><input type="search" name="search" value="<?= htmlspecialchars($forumSearch) ?>" placeholder="Forum, event, author..." data-i18n-placeholder="forum.filter.searchPlaceholder"></label>
            <label class="ec-filter-field"><span data-i18n="common.status">Status</span><select name="status"><option value="" data-i18n-opt="common.allStatuses">All statuses</option><option value="1" <?= $forumStatusFilter === '1' ? 'selected' : '' ?> data-i18n-opt="forum.status.active">Active</option><option value="0" <?= $forumStatusFilter === '0' ? 'selected' : '' ?> data-i18n-opt="forum.status.closed">Closed</option></select></label>
            <label class="ec-filter-field"><span data-i18n="common.perPage">Per page</span><select name="per_page"><option value="10" <?= $forumPerPage === 10 ? 'selected' : '' ?>>10 / page</option><option value="15" <?= $forumPerPage === 15 ? 'selected' : '' ?>>15 / page</option><option value="25" <?= $forumPerPage === 25 ? 'selected' : '' ?>>25 / page</option></select></label>
            <div class="ec-filter-actions"><button type="submit" class="ec-primary-btn" data-i18n="common.applyFilters">Apply filters</button><a href="?action=admin" class="ec-soft-btn" data-i18n="common.reset">Reset</a></div>
          </form>
        </section>

        <section class="ec-table-card">
          <div class="ec-table-head"><div><h2 data-i18n="forum.table.title">Forum List</h2><p><?= $forumTotalRows ?> <span data-i18n="forum.table.matchCount">forum(s) match the current filters.</span></p></div></div>
          <div id="ecResultsRegion" class="ec-results-region">
            <div class="ec-table-wrap">
              <table class="ec-table ec-forum-table">
                <thead>
                  <tr>
                    <th onclick="sortTable(0)" style="cursor:pointer;">ID <span class="sort-icon" id="sort-icon-0"></span></th>
                    <th onclick="sortTable(1)" style="cursor:pointer;"><span data-i18n="forum.table.titleCol">Title</span> <span class="sort-icon" id="sort-icon-1"></span></th>
                    <th onclick="sortTable(2)" style="cursor:pointer;"><span data-i18n="events.table.event">Event</span> <span class="sort-icon" id="sort-icon-2"></span></th>
                    <th onclick="sortTable(3)" style="cursor:pointer;"><span data-i18n="forum.table.author">Author</span> <span class="sort-icon" id="sort-icon-3"></span></th>
                    <th onclick="sortTable(4)" style="cursor:pointer;"><span data-i18n="forum.table.messages">Messages</span> <span class="sort-icon" id="sort-icon-4"></span></th>
                    <th onclick="sortTable(5)" style="cursor:pointer;"><span data-i18n="events.table.date">Date</span> <span class="sort-icon" id="sort-icon-5"></span></th>
                    <th data-i18n="common.actions">Actions</th>
                  </tr>
                </thead>
                <tbody id="forumsTableBody">
                  <?php if (empty($pagedForums)): ?>
                    <tr><td colspan="7"><div class="ec-empty-state"><span><i class="mdi mdi-forum-remove"></i></span><strong data-i18n="forum.empty.title">No forum found</strong><p data-i18n="forum.empty.subtitle">Try another filter or create forums from today's events.</p></div></td></tr>
                  <?php else: ?>
                    <?php foreach ($pagedForums as $forum): ?>
                    <tr>
                      <td>#<?= (int)$forum['idForum'] ?></td>
                      <td><div class="ec-main-cell"><strong><?= htmlspecialchars($forum['TitreForum'] ?? $forum['titreForum'] ?? 'Discussion') ?></strong><span data-i18n="<?= ((int)($forum['est_actif'] ?? 0) === 1) ? 'forum.status.active' : 'forum.status.closed' ?>"><?= ((int)($forum['est_actif'] ?? 0) === 1) ? 'Active' : 'Closed' ?></span></div></td>
                      <td><?= htmlspecialchars($forum['nom_evenement'] ?? '') ?></td>
                      <td><?= htmlspecialchars($forum['nom_utilisateur'] ?? 'Admin') ?></td>
                      <td><span class="ec-chip"><?= (int)($forum['nb_messages'] ?? 0) ?> <span data-i18n="forum.table.messagesLower">messages</span></span></td>
                      <td class="ec-date-cell"><?= htmlspecialchars(date('Y-m-d', strtotime($forum['dateCreation']))) ?></td>
                      <td><div class="ec-actions"><a href="<?= htmlspecialchars($BASE . '/Controleur/forumC.php?action=voir&source=backoffice&id=' . (int)$forum['idForum']) ?>" class="ec-action-btn ec-action-primary"><i class="mdi mdi-eye me-1"></i><span data-i18n="common.view">View</span></a><button type="button" class="ec-action-btn ec-action-danger" onclick="supprimerForum(<?= (int)$forum['idForum'] ?>, '<?= htmlspecialchars(addslashes($forum['TitreForum'] ?? $forum['titreForum'] ?? '')) ?>')"><i class="mdi mdi-delete me-1"></i><span data-i18n="common.delete">Delete</span></button></div></td>
                    </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
            <div class="ec-pagination">
              <p><span data-i18n="common.page">Page</span> <?= $forumPage ?> <span data-i18n="common.of">of</span> <?= $forumTotalPages ?> (<?= $forumTotalRows ?> <span data-i18n="forum.pagination.forums">forums</span>)</p>
              <nav aria-label="Forum pagination">
                <a class="ec-page-btn <?= $forumPage <= 1 ? 'is-disabled' : '' ?>" href="<?= htmlspecialchars($forumPageUrl(max(1, $forumPage - 1))) ?>">«</a>
                <?php for ($p = 1; $p <= $forumTotalPages; $p++): ?>
                  <?php if ($p === 1 || $p === $forumTotalPages || abs($p - $forumPage) <= 1): ?>
                    <a class="ec-page-btn <?= $p === $forumPage ? 'is-active' : '' ?>" href="<?= htmlspecialchars($forumPageUrl($p)) ?>"><?= $p ?></a>
                  <?php elseif (abs($p - $forumPage) === 2): ?>
                    <span class="ec-page-ellipsis">…</span>
                  <?php endif; ?>
                <?php endfor; ?>
                <a class="ec-page-btn <?= $forumPage >= $forumTotalPages ? 'is-disabled' : '' ?>" href="<?= htmlspecialchars($forumPageUrl(min($forumTotalPages, $forumPage + 1))) ?>">»</a>
              </nav>
            </div>
          </div>
        </section>

      <?php require __DIR__ . '/../layout/footer.php'; ?>
      </div><!-- content-wrapper -->
    </div><!-- main-panel -->
  </div><!-- page-body-wrapper -->
</div><!-- container-scroller -->

<script src="<?= $backBoUtilisateurWeb ?? '../utilisateur' ?>/assets/vendors/js/vendor.bundle.base.js"></script>
<script src="<?= $backBoUtilisateurWeb ?? '../utilisateur' ?>/assets/vendors/chart.js/Chart.min.js"></script>
<script src="<?= ($backBoRootWeb ?? '..') ?>/layout/back-layout.js?v=<?php echo urlencode((string)filemtime(__DIR__.'/../layout/back-layout.js')); ?>"></script>

<script>
window.cre8BackRegisterTranslations && window.cre8BackRegisterTranslations({
  en: {
    "common.hideStatistics": "Hide statistics",
    "common.showStatistics": "Show statistics",
    "eventCenter.kicker": "Event Center",
    "eventCenter.tab.events": "Events",
    "eventCenter.tab.eventsSub": "Planning and participation",
    "eventCenter.tab.forum": "Forum",
    "eventCenter.tab.forumSub": "Discussions and moderation",
    "forum.title": "Forum administration",
    "forum.subtitle": "Supervise forum discussions, reported messages, and community activity.",
    "forum.action.createToday": "Generate missing forums",
    "forum.stats.title": "Workspace statistics",
    "forum.stats.subtitle": "Forum health, messages, activity, and moderation overview.",
    "forum.kpi.total": "Total forums",
    "forum.kpi.created": "forums created",
    "forum.kpi.messages": "Messages",
    "forum.kpi.totalMessages": "total messages",
    "forum.kpi.participants": "Participants",
    "forum.kpi.activeMembers": "active members",
    "forum.kpi.reported": "Reported",
    "forum.kpi.flagged": "messages flagged",
    "forum.kpi.activeForums": "Active forums",
    "forum.kpi.currentlyOpen": "currently open",
    "forum.kpi.closed": "Closed",
    "forum.kpi.inactiveForums": "inactive forums",
    "forum.chart.messagesEvolution": "Messages evolution",
    "forum.chart.messagesEvolutionSub": "Forum messages over the last months.",
    "forum.chart.topForums": "Top forums",
    "forum.chart.topForumsSub": "Most active forum spaces.",
    "forum.chart.topContributors": "Top contributors",
    "forum.chart.topContributorsSub": "Most involved users.",
    "forum.reported.title": "Reported messages",
    "forum.reported.subtitle": "message(s) require moderation.",
    "forum.filter.title": "Admin filters",
    "forum.filter.subtitle": "Filter by forum title, event, author, or activity status.",
    "forum.filter.searchPlaceholder": "Forum, event, author...",
    "forum.table.title": "Forum List",
    "forum.table.titleCol": "Title",
    "forum.table.author": "Author",
    "forum.table.messages": "Messages",
    "forum.table.messagesLower": "messages",
    "forum.table.activity": "Activity",
    "forum.table.user": "User",
    "forum.table.impact": "Impact",
    "forum.table.matchCount": "forum(s) match the current filters.",
    "forum.empty.title": "No forum found",
    "forum.empty.subtitle": "Try another filter or create forums from today's events.",
    "forum.pagination.forums": "forums",
    "forum.footer": "Forum management",
    "forum.js.deleteForum": "Delete forum",
    "forum.js.deleteMessage": "Delete this reported message?",
    "forum.status.active": "Active",
    "forum.status.closed": "Closed",
    "forum.status.calm": "Calm",
    "forum.status.expert": "Expert",
    "events.table.event": "Event",
    "events.table.date": "Date",
    "common.allStatuses": "All statuses",
    "common.perPage": "Per page"
  },
  fr: {
    "common.hideStatistics": "Masquer les statistiques",
    "common.showStatistics": "Afficher les statistiques",
    "eventCenter.kicker": "Centre événements",
    "eventCenter.tab.events": "Événements",
    "eventCenter.tab.eventsSub": "Planification et participation",
    "eventCenter.tab.forum": "Forum",
    "eventCenter.tab.forumSub": "Discussions et modération",
    "forum.title": "Administration du forum",
    "forum.subtitle": "Supervisez les discussions, les messages signalés et l'activité de la communauté.",
    "forum.action.createToday": "Générer les forums manquants",
    "forum.stats.title": "Statistiques de l'espace",
    "forum.stats.subtitle": "Santé des forums, messages, activité et vue de modération.",
    "forum.kpi.total": "Total forums",
    "forum.kpi.created": "forums créés",
    "forum.kpi.messages": "Messages",
    "forum.kpi.totalMessages": "messages au total",
    "forum.kpi.participants": "Participants",
    "forum.kpi.activeMembers": "membres actifs",
    "forum.kpi.reported": "Signalés",
    "forum.kpi.flagged": "messages signalés",
    "forum.kpi.activeForums": "Forums actifs",
    "forum.kpi.currentlyOpen": "ouverts actuellement",
    "forum.kpi.closed": "Fermés",
    "forum.kpi.inactiveForums": "forums inactifs",
    "forum.chart.messagesEvolution": "Évolution des messages",
    "forum.chart.messagesEvolutionSub": "Messages des forums sur les derniers mois.",
    "forum.chart.topForums": "Top forums",
    "forum.chart.topForumsSub": "Espaces de discussion les plus actifs.",
    "forum.chart.topContributors": "Top contributeurs",
    "forum.chart.topContributorsSub": "Utilisateurs les plus impliqués.",
    "forum.reported.title": "Messages signalés",
    "forum.reported.subtitle": "message(s) nécessitent une modération.",
    "forum.filter.title": "Filtres admin",
    "forum.filter.subtitle": "Filtrez par titre, événement, auteur ou statut d'activité.",
    "forum.filter.searchPlaceholder": "Forum, événement, auteur...",
    "forum.table.title": "Liste des forums",
    "forum.table.titleCol": "Titre",
    "forum.table.author": "Auteur",
    "forum.table.messages": "Messages",
    "forum.table.messagesLower": "messages",
    "forum.table.activity": "Activité",
    "forum.table.user": "Utilisateur",
    "forum.table.impact": "Impact",
    "forum.table.matchCount": "forum(s) correspondent aux filtres actuels.",
    "forum.empty.title": "Aucun forum trouvé",
    "forum.empty.subtitle": "Essayez un autre filtre ou créez les forums des événements du jour.",
    "forum.pagination.forums": "forums",
    "forum.footer": "Gestion des forums",
    "forum.js.deleteForum": "Supprimer le forum",
    "forum.js.deleteMessage": "Supprimer ce message signalé ?",
    "forum.status.active": "Actif",
    "forum.status.closed": "Fermé",
    "forum.status.calm": "Calme",
    "forum.status.expert": "Expert",
    "events.table.event": "Événement",
    "events.table.date": "Date",
    "common.allStatuses": "Tous les statuts",
    "common.perPage": "Par page"
  }
});
</script>

<script src="<?= $backBoRootWeb ?>/event-center-admin.js?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../event-center-admin.js')); ?>"></script>
<script src="<?= $backBoUtilisateurWeb ?? '../utilisateur' ?>/assets/js/off-canvas.js"></script>
<script src="<?= $backBoUtilisateurWeb ?? '../utilisateur' ?>/assets/js/hoverable-collapse.js"></script>
<script src="<?= $backBoUtilisateurWeb ?? '../utilisateur' ?>/assets/js/misc.js"></script>
<script src="<?= $backBoUtilisateurWeb ?? '../utilisateur' ?>/assets/js/settings.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
  let sortColumn = 0, sortDirection = 'asc', originalRows = [];

  function saveOriginalOrder() {
    const rows = document.querySelectorAll('#forumsTableBody tr');
    originalRows = Array.from(rows).map(r => r.outerHTML);
  }

  function resetTable() {
    const tbody = document.getElementById('forumsTableBody');
    if (originalRows.length) tbody.innerHTML = originalRows.join('');
    document.querySelectorAll('.sort-icon').forEach(i => { i.classList.remove('active','asc','desc'); i.textContent = ''; });
    sortColumn = 0; sortDirection = 'asc';
    const s = document.getElementById('tableSearchInput');
    if (s) { s.value = ''; filterTable(); }
    else document.getElementById('tableCount').textContent = document.querySelectorAll('#forumsTableBody tr').length;
  }

  function filterTable() {
    const filter = document.getElementById('tableSearchInput').value.toLowerCase();
    const rows = document.querySelectorAll('#forumsTableBody tr');
    let visible = 0;
    rows.forEach(row => {
      const show = row.innerText.toLowerCase().includes(filter);
      row.style.display = show ? '' : 'none';
      if (show) visible++;
    });
    document.getElementById('tableCount').textContent = visible;
  }

  function sortTable(col) {
    const tbody = document.getElementById('forumsTableBody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    document.querySelectorAll('.sort-icon').forEach(i => { i.classList.remove('active','asc','desc'); i.textContent = ''; });
    if (sortColumn === col) { sortDirection = sortDirection === 'asc' ? 'desc' : 'asc'; } else { sortColumn = col; sortDirection = 'asc'; }
    const icon = document.getElementById('sort-icon-' + col);
    if (icon) { icon.classList.add('active', sortDirection); icon.textContent = sortDirection === 'asc' ? '↑' : '↓'; }
    rows.sort((a, b) => {
      let av = a.cells[col]?.innerText.trim() || '', bv = b.cells[col]?.innerText.trim() || '';
      if (col === 0) return sortDirection === 'asc' ? parseInt(av) - parseInt(bv) : parseInt(bv) - parseInt(av);
      av = av.toLowerCase(); bv = bv.toLowerCase();
      return sortDirection === 'asc' ? av.localeCompare(bv) : bv.localeCompare(av);
    });
    rows.forEach(r => tbody.appendChild(r));
  }

  function supprimerForum(id, titre) {
    if (confirm(((window.cre8BackText && window.cre8BackText('forum.js.deleteForum')) || 'Delete forum') + ' "' + titre + '" ?'))
      window.location.href = '<?= $BASE ?>/Controleur/forumC.php?action=supprimer_forum&id=' + id;
  }

  function supprimerMessage(id) {
    if (confirm((window.cre8BackText && window.cre8BackText('forum.js.deleteMessage')) || 'Delete this reported message?'))
      window.location.href = '<?= $BASE ?>/Controleur/forumC.php?action=supprimer_message&id=' + id;
  }

  document.addEventListener('DOMContentLoaded', function() {
    setTimeout(saveOriginalOrder, 200);

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
