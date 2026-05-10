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
  <link rel="shortcut icon" href="<?= $backBoUtilisateurWeb ?? '../utilisateur' ?>/assets/images/favicon.png">
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
      <div class="content-wrapper">
        
        <!-- Page header -->
        <div class="row mb-3 align-items-center">
          <div class="col">
            <h4 class="page-title mb-0">Gestion des Forums</h4>
            <p class="text-muted mb-0" style="font-size:.85rem;">Supervision, modération et statistiques de la communauté</p>
          </div>
          <div class="col-auto">
            <a href="<?= $BASE ?>/Controleur/forumC.php?action=creer_forums_auto"
               class="btn text-white"
               style="background:linear-gradient(135deg,#9B5DE0,#B771E5);border-radius:10px;">
              <i class="mdi mdi-refresh me-1"></i> Créer les forums du jour
            </a>
          </div>
        </div>

        <!-- KPI Cards -->
        <div class="row mb-4 align-items-stretch">
          <div class="col-md-3 mb-3 d-flex">
            <div class="card shadow-sm text-center p-4 h-100 w-100" style="background:linear-gradient(135deg,#9B5DE0,#B771E5);color:white;border-radius:10px;">
              <i class="mdi mdi-forum" style="font-size:2rem;margin-bottom:10px;"></i>
              <h6 class="mb-2">Total Forums</h6>
              <h3 class="mb-0"><?= $total_forums ?></h3>
              <small class="mt-2 opacity-75">forums créés</small>
            </div>
          </div>
          <div class="col-md-3 mb-3 d-flex">
            <div class="card shadow-sm text-center p-4 h-100 w-100" style="background:linear-gradient(135deg,#E11D74,#D01565);color:white;border-radius:10px;">
              <i class="mdi mdi-message-text" style="font-size:2rem;margin-bottom:10px;"></i>
              <h6 class="mb-2">Messages</h6>
              <h3 class="mb-0"><?= $total_messages ?></h3>
              <small class="mt-2 opacity-75">total messages</small>
            </div>
          </div>
          <div class="col-md-3 mb-3 d-flex">
            <div class="card shadow-sm text-center p-4 h-100 w-100" style="background:linear-gradient(135deg,#AEEA94,#99D98E);color:#2d5016;border-radius:10px;">
              <i class="mdi mdi-account-group" style="font-size:2rem;margin-bottom:10px;"></i>
              <h6 class="mb-2">Participants</h6>
              <h3 class="mb-0"><?= $total_participants ?></h3>
              <small class="mt-2" style="opacity:.75;">membres actifs</small>
            </div>
          </div>
          <div class="col-md-3 mb-3 d-flex">
            <div class="card shadow-sm text-center p-4 h-100 w-100" style="background:linear-gradient(135deg,#D78FEE,#C96FE8);color:white;border-radius:10px;">
              <i class="mdi mdi-flag" style="font-size:2rem;margin-bottom:10px;"></i>
              <h6 class="mb-2">Signalés</h6>
              <h3 class="mb-0"><?= $total_signales ?></h3>
              <small class="mt-2 opacity-75">messages signalés</small>
            </div>
          </div>
        </div>

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-3" id="forumTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-forums-btn" data-bs-toggle="tab" data-bs-target="#tab-forums" type="button" role="tab">
              <i class="mdi mdi-forum me-1"></i> Forums (<?= $total_forums ?>)
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-signales-btn" data-bs-toggle="tab" data-bs-target="#tab-signales" type="button" role="tab">
              <i class="mdi mdi-flag me-1"></i> Messages signalés (<?= $total_signales ?>)
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-stats-btn" data-bs-toggle="tab" data-bs-target="#tab-stats" type="button" role="tab">
              <i class="mdi mdi-chart-bar me-1"></i> Statistiques
            </button>
          </li>
        </ul>

        <div class="tab-content" id="forumTabsContent">

          <!-- TAB 1: FORUMS -->
          <div class="tab-pane fade show active" id="tab-forums" role="tabpanel">
            <div class="card">
              <div class="card-body">
                <div class="row mb-3 align-items-center">
                  <div class="col-md-6">
                    <div class="input-group">
                      <span class="input-group-text"><i class="mdi mdi-magnify"></i></span>
                      <input type="text" id="tableSearchInput" class="form-control" placeholder="Rechercher..." onkeyup="filterTable()">
                    </div>
                  </div>
                  <div class="col-md-4">
                    <span class="text-muted" style="font-size:.85rem;"><span id="tableCount"><?= $total_forums ?></span> forums</span>
                  </div>
                  <div class="col-md-2 text-end">
                    <button class="btn btn-sm" style="background:rgba(155,93,224,.12);color:#9B5DE0;border:1px solid rgba(155,93,224,.3);" onclick="resetTable()">
                      <i class="mdi mdi-refresh me-1"></i> Réinitialiser
                    </button>
                  </div>
                </div>
                <div class="table-responsive">
                  <table class="table table-hover align-middle">
                    <thead>
                      <tr>
                        <th onclick="sortTable(0)" style="cursor:pointer;">ID <span class="sort-icon" id="sort-icon-0"></span></th>
                        <th onclick="sortTable(1)" style="cursor:pointer;">Titre <span class="sort-icon" id="sort-icon-1"></span></th>
                        <th onclick="sortTable(2)" style="cursor:pointer;">Événement <span class="sort-icon" id="sort-icon-2"></span></th>
                        <th onclick="sortTable(3)" style="cursor:pointer;">Auteur <span class="sort-icon" id="sort-icon-3"></span></th>
                        <th onclick="sortTable(4)" style="cursor:pointer;">Messages <span class="sort-icon" id="sort-icon-4"></span></th>
                        <th onclick="sortTable(5)" style="cursor:pointer;">Date <span class="sort-icon" id="sort-icon-5"></span></th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody id="forumsTableBody">
                      <?php if (empty($forums)): ?>
                        <tr><td colspan="7" class="text-center py-4">Aucun forum trouvé</td></tr>
                      <?php else: ?>
                        <?php foreach ($forums as $forum): ?>
                        <tr>
                          <td><?= $forum['idForum'] ?></td>
                          <td><strong><?= htmlspecialchars($forum['TitreForum'] ?? $forum['titreForum'] ?? 'Discussion') ?></strong></td>
                          <td><?= htmlspecialchars($forum['nom_evenement'] ?? '') ?></td>
                          <td><?= htmlspecialchars($forum['nom_utilisateur'] ?? 'Admin') ?></td>
                          <td><span class="badge" style="background:rgba(155,93,224,.15);color:#9B5DE0;"><?= $forum['nb_messages'] ?? 0 ?> messages</span></td>
                          <td><?= date('d/m/Y', strtotime($forum['dateCreation'])) ?></td>
                          <td>
                            <div class="d-flex gap-2">
                              <a href="<?= $BASE ?>/Controleur/forumC.php?action=voir&id=<?= $forum['idForum'] ?>"
                                 class="btn table-action-btn text-white" style="background:#9B5DE0;">
                                <i class="mdi mdi-eye me-1"></i> Voir
                              </a>
                              <button type="button" class="btn table-action-btn text-white" style="background:#D78FEE;"
                                onclick="supprimerForum(<?= $forum['idForum'] ?>, '<?= htmlspecialchars(addslashes($forum['TitreForum'] ?? $forum['titreForum'] ?? '')) ?>')">
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

          <!-- TAB 2: MESSAGES SIGNALÉS -->
          <div class="tab-pane fade" id="tab-signales" role="tabpanel">
            <?php if (empty($messages_signales)): ?>
              <div class="alert alert-success d-flex align-items-center" role="alert">
                <i class="mdi mdi-check-circle me-2" style="font-size:1.4rem;"></i>
                <div>Aucun message signalé — tous les messages sont conformes.</div>
              </div>
            <?php else: ?>
              <?php foreach ($messages_signales as $msg): ?>
              <div class="card forum-message-card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                  <span><i class="mdi mdi-pin me-1" style="color:#9B5DE0;"></i> Forum : <strong><?= htmlspecialchars($msg['titreForum'] ?? '') ?></strong></span>
                  <button type="button" class="btn btn-sm text-white" style="background:#D78FEE;border-radius:10px;"
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
          </div>

          <!-- TAB 3: STATISTIQUES -->
          <div class="tab-pane fade" id="tab-stats" role="tabpanel">
            <div class="row mb-4">
              <div class="col-lg-12 mb-3">
                <div class="card shadow-sm">
                  <div class="card-body">
                    <h5 class="card-title mb-3"><i class="mdi mdi-chart-line me-2" style="color:#9B5DE0;"></i>Évolution des messages (6 mois)</h5>
                    <canvas id="messagesChart" style="max-height:280px;"></canvas>
                  </div>
                </div>
              </div>
            </div>
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
                            <td><span class="badge" style="background:rgba(155,93,224,.15);color:#9B5DE0;"><?= ($f['nb_messages']??0)>10?'🔥 Actif':'🕰️ Calme' ?></span></td>
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
                            <td><span class="badge" style="background:rgba(225,29,116,.15);color:#E11D74;"><?= ($u['nb_messages']??0)>20?'🏆 Expert':'🌱 Actif' ?></span></td>
                          </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div><!-- tab-content -->

      </div><!-- content-wrapper -->
      <footer class="footer">
        <div class="d-sm-flex justify-content-center justify-content-sm-between">
          <span class="text-muted d-block text-center text-sm-left d-sm-inline-block">Copyright &copy; cre8connect 2024</span>
          <span class="float-none float-sm-right d-block mt-1 mt-sm-0 text-center">Gestion des Forums</span>
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
    if (confirm('Supprimer le forum "' + titre + '" ?'))
      window.location.href = '<?= $BASE ?>/Controleur/forumC.php?action=supprimer_forum&id=' + id;
  }

  function supprimerMessage(id) {
    if (confirm('Supprimer ce message signalé ?'))
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
