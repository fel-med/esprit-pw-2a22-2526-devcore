<?php
require_once __DIR__ . '/../layout/bo_paths.php';

if (!isset($forum) || !isset($messages)) {
    header('Location: ' . ($BASE ?? '') . '/Controleur/forumC.php?action=admin');
    exit;
}

$backActive = 'forum';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <?php require_once __DIR__ . '/../layout/early-theme.php'; cre8_bo_early_theme_print_head_script(); ?>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Forum discussion - Cre8Connect BackOffice</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= $backBoUtilisateurWeb ?? '../utilisateur' ?>/assets/vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="<?= $backBoUtilisateurWeb ?? '../utilisateur' ?>/assets/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="<?= $backBoUtilisateurWeb ?? '../utilisateur' ?>/assets/css/style.css">
  <link rel="stylesheet" href="<?= ($backBoRootWeb ?? '..') ?>/layout/back-layout.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../layout/back-layout.css')); ?>">
  <style>
    .bo-forum-discussion { display: grid; gap: 1rem; }
    .bo-forum-card {
      border: 1px solid rgba(148, 163, 184, .18);
      border-radius: 14px;
      background: rgba(18, 24, 41, .96);
      color: #e7ebff;
      padding: 1rem;
    }
    .bo-forum-meta { color: #94a3b8; font-size: .86rem; display: flex; flex-wrap: wrap; gap: .75rem; }
    body.light-mode .bo-forum-card { background: #ffffff; color: #111827; border-color: #e2e8f0; }
    body.light-mode .bo-forum-meta { color: #64748b; }
  </style>
</head>
<body class="cre8-admin-layout"><?php cre8_bo_early_theme_print_body_script(); ?>
<div class="container-scroller cre8-admin-page">
  <?php require_once __DIR__ . '/../layout/sidebar.php'; ?>
  <div class="container-fluid page-body-wrapper cre8-admin-main">
    <?php require_once __DIR__ . '/../layout/header.php'; ?>
    <div class="main-panel">
      <div class="content-wrapper">
        <div class="bo-forum-discussion">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
              <h1 class="h3 mb-1"><?= htmlspecialchars($forum['TitreForum'] ?? 'Forum discussion') ?></h1>
              <div class="bo-forum-meta">
                <span>Event: <?= htmlspecialchars($forum['nom_evenement'] ?? 'Event') ?></span>
                <span>Views: <?= (int) ($forum['vues'] ?? 0) ?></span>
                <span>Messages: <?= count($messages) ?></span>
              </div>
            </div>
            <a class="btn btn-outline-primary" href="<?= htmlspecialchars(($BASE ?? '') . '/Controleur/forumC.php?action=admin') ?>">
              <i class="mdi mdi-arrow-left"></i> Back to forums
            </a>
          </div>

          <section class="bo-forum-card">
            <strong>Subject</strong>
            <p class="mb-0 mt-2"><?= nl2br(htmlspecialchars($forum['sujet'] ?? '')) ?></p>
          </section>

          <?php if (empty($messages)): ?>
            <section class="bo-forum-card">No messages yet.</section>
          <?php else: ?>
            <?php foreach ($messages as $message): ?>
              <article class="bo-forum-card">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                  <strong><?= htmlspecialchars($message['nom_utilisateur'] ?? 'Utilisateur') ?></strong>
                  <span class="bo-forum-meta"><?= htmlspecialchars($message['dateMessage'] ?? '') ?></span>
                </div>
                <p class="mt-3 mb-0"><?= nl2br(htmlspecialchars($message['message'] ?? '')) ?></p>
              </article>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <?php require __DIR__ . '/../layout/footer.php'; ?>
      </div>
    </div>
  </div>
</div>
<script src="<?= $backBoUtilisateurWeb ?? '../utilisateur' ?>/assets/vendors/js/vendor.bundle.base.js"></script>
<script src="<?= ($backBoRootWeb ?? '..') ?>/layout/back-layout.js?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../layout/back-layout.js')); ?>"></script>
</body>
</html>
