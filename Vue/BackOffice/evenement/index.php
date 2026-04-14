<?php
// This file can work both directly AND through the controller

// If accessed directly (for development/testing), fetch events from database
if (!isset($evenements)) {
    // Include config and fetch events directly
    require_once __DIR__ . '/../../../config.php';
    require_once __DIR__ . '/../../../Modele/evenement.php';
    
    try {
        $pdo = config::getConnexion();
        $stmt = $pdo->query("SELECT * FROM evenement ORDER BY idFormation DESC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Hydrate events
        $evenements = [];
        foreach ($rows as $row) {
            $evenements[] = new Evenement(
                (int)($row['idFormation'] ?? 0),
                $row['TitreFormation'] ?? '',
                $row['description'] ?? '',
                $row['type'] ?? '',
                $row['statut'] ?? '',
                $row['lieu'] ?? '',
                $row['DateFormation'] ?? '',
                (int)($row['capacité'] ?? 0),
                (int)($row['nb_inscrits'] ?? 0),
                (int)($row['Duree'] ?? 0),
                $row['created_at'] ?? '',
                $row['image'] ?? null
            );
        }
    } catch (Exception $e) {
        $evenements = [];
        echo "<!-- Error: " . $e->getMessage() . " -->";
    }
}

// Calculate statistics
$totalEvents = count($evenements);
$totalInscrits = array_sum(array_map(function($e) { return $e->getNbInscrits(); }, $evenements));
$pendingEvents = count(array_filter($evenements, function($e) { return $e->getStatut() === 'en_attente'; }));
$activeEvents = count(array_filter($evenements, function($e) { return $e->getStatut() === 'actif'; }));
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Gestion Événements – Admin Cre8Connect</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet"/>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --bg-base:      #0d1117;
      --bg-surface:   #161b22;
      --bg-elevated:  #21262d;
      --bg-hover:     #30363d;
      --border:       #30363d;
      --border-soft:  #21262d;
      --text-main:    #e6edf3;
      --text-soft:    #8b949e;
      --text-muted:   #484f58;
      --primary:      #58a6ff;
      --primary-dim:  rgba(88,166,255,.12);
      --accent:       #f78166;
      --success:      #3fb950;
      --success-dim:  rgba(63,185,80,.12);
      --warning:      #d29922;
      --warning-dim:  rgba(210,153,34,.12);
      --danger:       #f85149;
      --danger-dim:   rgba(248,81,73,.12);
      --purple:       #bc8cff;
      --purple-dim:   rgba(188,140,255,.12);
      --radius:       8px;
      --radius-lg:    12px;
      --shadow:       0 8px 32px rgba(0,0,0,.4);
    }

    body {
      font-family: 'Inter', sans-serif;
      background: var(--bg-base);
      color: var(--text-main);
      display: flex; min-height: 100vh;
    }

    /* Sidebar */
    .sidebar {
      width: 240px; flex-shrink: 0;
      background: var(--bg-surface);
      border-right: 1px solid var(--border);
      display: flex; flex-direction: column;
      position: fixed; top: 0; left: 0; height: 100vh;
      overflow-y: auto; z-index: 50;
    }
    .sidebar-logo {
      padding: 20px 20px 16px;
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .logo-img {
      width: 36px;
      height: 36px;
      border-radius: 8px;
      object-fit: cover;
    }
    .logo-text { 
      font-size: 1.1rem; 
      font-weight: 700; 
      color: var(--text-main);
      font-family: 'Inter', sans-serif;
    }

    .sidebar-section {
      padding: 20px 12px 8px;
    }
    .sidebar-section-label {
      font-size: .65rem; font-weight: 600; letter-spacing: .1em;
      color: var(--text-muted); text-transform: uppercase;
      padding: 0 8px; margin-bottom: 6px;
    }
    .nav-item {
      display: flex; align-items: center; gap: 10px;
      padding: 8px 10px; border-radius: var(--radius);
      font-size: .85rem; font-weight: 500; color: var(--text-soft);
      cursor: pointer; text-decoration: none;
      transition: background .15s, color .15s;
      margin-bottom: 2px;
    }
    .nav-item:hover { background: var(--bg-hover); color: var(--text-main); }
    .nav-item.active {
      background: var(--primary-dim); color: var(--primary);
    }
    .nav-badge {
      margin-left: auto; font-size: .7rem; font-weight: 600;
      background: var(--danger); color: #fff;
      padding: 1px 7px; border-radius: 50px;
    }
    .nav-badge.info { background: var(--primary-dim); color: var(--primary); }

    .sidebar-footer {
      margin-top: auto;
      padding: 16px 12px;
      border-top: 1px solid var(--border);
      display: none;
    }

    /* Main Wrap */
    .main-wrap {
      margin-left: 240px; flex: 1;
      display: flex; flex-direction: column;
    }

    /* Topbar */
    .topbar {
      background: var(--bg-surface);
      border-bottom: 1px solid var(--border);
      padding: 0 28px;
      height: 56px;
      display: flex; align-items: center; gap: 16px;
      position: sticky; top: 0; z-index: 40;
    }
    .breadcrumb {
      display: flex; align-items: center; gap: 8px;
      font-size: .83rem; color: var(--text-muted);
    }
    .breadcrumb span { color: var(--text-main); }
    .topbar-actions { margin-left: auto; display: flex; align-items: center; gap: 12px; }
    .topbar-search {
      display: flex; align-items: center; gap: 8px;
      background: var(--bg-base); border: 1px solid var(--border);
      border-radius: var(--radius); padding: 6px 12px;
    }
    .topbar-search input {
      background: none; border: none; outline: none;
      font-size: .83rem; color: var(--text-main); width: 180px;
      font-family: inherit;
    }
    .topbar-search input::placeholder { color: var(--text-muted); }

    /* Content */
    .content { padding: 28px; flex: 1; }

    /* Page header */
    .page-header {
      display: flex; align-items: flex-start; justify-content: space-between;
      margin-bottom: 24px;
    }
    .page-title-wrap h1 {
      font-size: 1.3rem; font-weight: 700; color: var(--text-main);
      margin-bottom: 4px;
    }
    .page-title-wrap p { font-size: .83rem; color: var(--text-soft); }
    .page-actions { display: flex; gap: 10px; }
    .btn-admin {
      display: inline-flex; align-items: center; gap: 7px;
      padding: 8px 18px; border-radius: var(--radius);
      font-size: .83rem; font-weight: 600; font-family: inherit;
      cursor: pointer; transition: all .15s; text-decoration: none; border: none;
    }
    .btn-primary-admin {
      background: var(--primary); color: #0d1117;
    }
    .btn-primary-admin:hover { background: #79c0ff; }
    .btn-ghost {
      background: var(--bg-elevated); color: var(--text-soft);
      border: 1px solid var(--border);
    }
    .btn-ghost:hover { background: var(--bg-hover); color: var(--text-main); }

    /* KPI cards */
    .kpi-grid {
      display: grid; grid-template-columns: repeat(4, 1fr);
      gap: 16px; margin-bottom: 24px;
    }
    .kpi-card {
      background: var(--bg-surface); border: 1px solid var(--border);
      border-radius: var(--radius-lg); padding: 20px;
    }
    .kpi-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
    .kpi-label { font-size: .75rem; font-weight: 500; color: var(--text-soft); }
    .kpi-icon {
      width: 30px; height: 30px; border-radius: 8px;
      display: flex; align-items: center; justify-content: center; font-size: .9rem;
    }
    .kpi-icon.blue { background: var(--primary-dim); }
    .kpi-icon.green { background: var(--success-dim); }
    .kpi-icon.yellow { background: var(--warning-dim); }
    .kpi-icon.purple { background: var(--purple-dim); }
    .kpi-value {
      font-size: 1.65rem; font-weight: 700; color: var(--text-main);
      font-family: 'JetBrains Mono', monospace; margin-bottom: 6px;
    }
    .kpi-delta { font-size: .73rem; font-weight: 500; color: var(--success); }

    /* Toolbar */
    .toolbar {
      background: var(--bg-surface); border: 1px solid var(--border);
      border-radius: var(--radius-lg) var(--radius-lg) 0 0;
      padding: 14px 20px;
      display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
    }
    .toolbar-search {
      display: flex; align-items: center; gap: 8px;
      background: var(--bg-base); border: 1px solid var(--border);
      border-radius: var(--radius); padding: 7px 12px; flex: 1; min-width: 200px;
    }
    .toolbar-search input {
      background: none; border: none; outline: none;
      font-size: .83rem; color: var(--text-main); width: 100%; font-family: inherit;
    }
    .toolbar-select {
      background: var(--bg-base); border: 1px solid var(--border);
      border-radius: var(--radius); padding: 7px 12px;
      font-size: .83rem; color: var(--text-main); font-family: inherit; cursor: pointer;
    }

    /* Table */
    .table-wrap {
      background: var(--bg-surface); border: 1px solid var(--border);
      border-top: none; border-radius: 0 0 var(--radius-lg) var(--radius-lg);
      overflow: hidden; margin-bottom: 24px;
    }
    table { width: 100%; border-collapse: collapse; }
    thead {
      background: var(--bg-base);
      border-bottom: 1px solid var(--border);
    }
    thead th {
      padding: 11px 16px;
      font-size: .72rem; font-weight: 600; letter-spacing: .06em;
      color: var(--text-muted); text-transform: uppercase;
      text-align: left;
    }
    tbody tr { border-bottom: 1px solid var(--border-soft); }
    tbody tr:hover { background: var(--bg-elevated); }
    tbody td { padding: 13px 16px; font-size: .83rem; color: var(--text-soft); vertical-align: middle; }

    .event-cell { display: flex; align-items: center; gap: 12px; }
    .event-thumb {
      width: 40px; height: 40px; border-radius: 8px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.2rem;
    }
    .thumb-purple { background: var(--purple-dim); }
    .event-name { font-size: .85rem; font-weight: 600; color: var(--text-main); margin-bottom: 2px; }
    .event-date { font-size: .75rem; color: var(--text-muted); }

    .badge {
      display: inline-flex; align-items: center; gap: 5px;
      font-size: .72rem; font-weight: 600; padding: 3px 10px;
      border-radius: 50px;
    }
    .badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
    .badge-active { background: var(--success-dim); color: var(--success); }
    .badge-en_attente { background: var(--warning-dim); color: var(--warning); }
    .badge-brouillon { background: var(--purple-dim); color: var(--purple); }

    .type-chip {
      font-size: .72rem; font-weight: 500;
      padding: 3px 9px; border-radius: 4px;
    }
    .type-formation { background: rgba(88,166,255,.1); color: var(--primary); }

    .progress-wrap { display: flex; align-items: center; gap: 8px; }
    .progress-bar {
      flex: 1; height: 6px; background: var(--bg-elevated);
      border-radius: 50px; overflow: hidden;
    }
    .progress-fill {
      height: 100%; border-radius: 50px;
      background: linear-gradient(90deg, var(--primary), var(--purple));
    }
    .progress-fill.full { background: var(--danger); }
    .progress-pct { font-size: .73rem; color: var(--text-muted); }

    .action-btn {
      padding: 5px 10px; border-radius: 6px;
      font-size: .75rem; font-weight: 500;
      cursor: pointer; border: none;
    }
    .action-edit { background: var(--primary-dim); color: var(--primary); }
    .action-view { background: var(--bg-elevated); color: var(--text-soft); border: 1px solid var(--border); }
    .action-delete { background: var(--danger-dim); color: var(--danger); }
    .actions-cell { display: flex; gap: 6px; }

    /* Modal */
    .modal-overlay {
      display: none; position: fixed; inset: 0; z-index: 200;
      background: rgba(0,0,0,.6); backdrop-filter: blur(4px);
      align-items: center; justify-content: center;
    }
    .modal-overlay.open { display: flex; }
    .modal {
      background: var(--bg-surface);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      width: 560px;
      max-width: 95vw;
      max-height: 90vh;
      overflow-y: auto;
    }
    .modal-header {
      padding: 20px 24px; border-bottom: 1px solid var(--border);
      display: flex; justify-content: space-between;
    }
    .modal-close {
      background: var(--bg-elevated); border: 1px solid var(--border);
      border-radius: 6px; width: 28px; height: 28px;
      cursor: pointer;
    }
    .modal-body {
      padding: 24px;
      max-height: calc(90vh - 120px);
    }
    .form-group { margin-bottom: 16px; }
    .form-label { font-size: .78rem; font-weight: 600; color: var(--text-soft); margin-bottom: 6px; display: block; }
    .form-control {
      width: 100%; padding: 9px 12px;
      background: var(--bg-base); border: 1px solid var(--border);
      border-radius: var(--radius); font-size: .85rem;
      color: var(--text-main);
    }
    .form-control:focus { outline: none; border-color: var(--primary); }
    .modal-footer {
      padding: 16px 24px; border-top: 1px solid var(--border);
      display: flex; justify-content: flex-end; gap: 10px;
    }

    .table-footer {
      display: flex; align-items: center; justify-content: space-between;
      background: var(--bg-surface); border: 1px solid var(--border);
      border-top: none; border-radius: 0 0 var(--radius-lg) var(--radius-lg);
      padding: 14px 20px;
    }
    .table-info { font-size: .8rem; color: var(--text-muted); }
    .pagination { display: flex; gap: 4px; }
    .page-btn {
      width: 32px; height: 32px; border-radius: 6px;
      display: flex; align-items: center; justify-content: center;
      background: var(--bg-elevated); border: 1px solid var(--border);
      color: var(--text-soft); cursor: pointer;
    }
    .page-btn.active { background: var(--primary); color: #0d1117; }

    .alert-banner {
      background: var(--warning-dim); border: 1px solid rgba(210,153,34,.3);
      border-radius: var(--radius); padding: 12px 16px;
      display: flex; align-items: center; gap: 10px;
      font-size: .83rem; color: var(--warning);
      margin-bottom: 20px;
    }

    /* Image preview */
    .image-preview {
      margin-top: 10px;
      max-width: 100px;
      border-radius: 8px;
      overflow: hidden;
    }
    .image-preview img {
      width: 100%;
      height: auto;
      display: block;
    }
    .current-image {
      margin-top: 10px;
      padding: 8px;
      background: var(--bg-elevated);
      border-radius: 8px;
      font-size: .75rem;
      color: var(--text-soft);
    }
  </style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar">
  <div class="sidebar-logo">
    <img src="/ProjetWeb/Esprit-PW-2A22-2526-Devcore/Vue/public/images/logo.png" alt="Logo" class="logo-img">
    <div class="logo-text">Cre8Connect</div>
  </div>
  <div class="sidebar-section">
    <div class="sidebar-section-label">Navigation</div>
    <a href="#" class="nav-item active">📅 Événements</a>
  </div>
  <div class="sidebar-footer">
    <!-- Admin info supprimé -->
  </div>
</aside>

<!-- Main Wrap -->
<div class="main-wrap">
  <header class="topbar">
    <div class="breadcrumb">Dashboard / Communauté / <span>Événements</span></div>
    <div class="topbar-actions">
      <div class="topbar-search">
        <input type="text" id="searchInput" placeholder="Recherche rapide…" onkeyup="filterTable()"/>
      </div>
    </div>
  </header>

  <div class="content">
    <div class="page-header">
      <div class="page-title-wrap">
        <h1>Gestion des Événements</h1>
        <p>Supervision, modération et administration de tous les événements</p>
      </div>
      <div class="page-actions">
        <button class="btn-admin btn-primary-admin" onclick="openModal()">+ Nouvel événement</button>
      </div>
    </div>

    <?php if ($pendingEvents > 0): ?>
    <div class="alert-banner">
      ⚠️ <strong><?= $pendingEvents ?> événement(s)</strong> en attente de validation
    </div>
    <?php endif; ?>

    <!-- KPI row -->
    <div class="kpi-grid">
      <div class="kpi-card">
        <div class="kpi-header"><span class="kpi-label">Total Événements</span><div class="kpi-icon blue">📅</div></div>
        <div class="kpi-value"><?= $totalEvents ?></div>
        <div class="kpi-delta">↑ <?= $activeEvents ?> actifs</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-header"><span class="kpi-label">Inscriptions</span><div class="kpi-icon green">👥</div></div>
        <div class="kpi-value"><?= $totalInscrits ?></div>
        <div class="kpi-delta">total participants</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-header"><span class="kpi-label">En attente</span><div class="kpi-icon yellow">⏳</div></div>
        <div class="kpi-value"><?= $pendingEvents ?></div>
        <div class="kpi-delta">à valider</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-header"><span class="kpi-label">Actifs</span><div class="kpi-icon purple">🚀</div></div>
        <div class="kpi-value"><?= $activeEvents ?></div>
        <div class="kpi-delta">en cours</div>
      </div>
    </div>

    <!-- Table -->
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th><input type="checkbox"/></th>
            <th>Image</th>
            <th>Événement</th>
            <th>Type</th>
            <th>Statut</th>
            <th>Date</th>
            <th>Lieu</th>
            <th>Inscriptions</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($evenements)): ?>
            <tr><td colspan="9" style="text-align:center; padding:40px;">Aucun événement trouvé</td></tr>
          <?php else: ?>
            <?php foreach ($evenements as $event): ?>
              <?php 
                $spotsLeft = $event->getCapacite() - $event->getNbInscrits();
                $percentage = ($event->getCapacite() > 0) ? ($event->getNbInscrits() / $event->getCapacite()) * 100 : 0;
              ?>
              <tr>
                <td><input type="checkbox"/></td>
                <td>
                  <?php if ($event->getImage()): ?>
                    <img src="/ProjetWeb/Esprit-PW-2A22-2526-Devcore/<?= $event->getImage() ?>" 
                         style="width: 40px; height: 40px; object-fit: cover; border-radius: 8px;">
                  <?php else: ?>
                    <div style="width: 40px; height: 40px; background: var(--bg-elevated); border-radius: 8px; display: flex; align-items: center; justify-content: center;">🎯</div>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="event-cell">
                    <div><div class="event-name"><?= htmlspecialchars($event->getTitre()) ?></div></div>
                  </div>
                </td>
                <td><span class="type-chip type-formation"><?= ucfirst($event->getType()) ?></span></td>
                <td><span class="badge badge-<?= $event->getStatut() ?>"><?= $event->getStatut() ?></span></td>
                <td><?= date('d M Y', strtotime($event->getDateEvenement())) ?></td>
                <td><?= htmlspecialchars($event->getLieu() ?: 'En ligne') ?></td>
                <td>
                  <div class="progress-wrap">
                    <div class="progress-bar"><div class="progress-fill <?= $percentage >= 100 ? 'full' : '' ?>" style="width: <?= min(100, $percentage) ?>%"></div></div>
                    <span class="progress-pct"><?= $event->getNbInscrits() ?>/<?= $event->getCapacite() ?></span>
                  </div>
                </td>
                <td class="actions-cell">
                  <button class="action-btn action-edit" onclick="editEvent(<?= $event->getId() ?>)">✏ Éditer</button>
                  <button class="action-btn action-delete" onclick="deleteEvent(<?= $event->getId() ?>, '<?= htmlspecialchars(addslashes($event->getTitre())) ?>')">🗑 Suppr</button>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <div class="table-footer">
      <span class="table-info">Total: <?= $totalEvents ?> événements</span>
    </div>
  </div>
</div>

<!-- Create Modal -->
<div class="modal-overlay" id="createModal">
  <div class="modal">
    <form method="POST" action="/ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/evenementC.php?action=create" enctype="multipart/form-data">
      <div class="modal-header"><h2>➕ Nouvel Événement</h2><button type="button" class="modal-close" onclick="closeModal()">✕</button></div>
      <div class="modal-body">
        <div class="form-group"><label class="form-label">Titre *</label><input type="text" name="titre" class="form-control" required/></div>
        <div class="form-group"><label class="form-label">Type *</label><select name="type" class="form-control"><option value="formation">Formation</option><option value="webinaire">Webinaire</option><option value="meetup">Meetup</option><option value="atelier">Atelier</option></select></div>
        <div class="form-group"><label class="form-label">Description</label><textarea name="description" class="form-control"></textarea></div>
        <div class="form-group"><label class="form-label">Date *</label><input type="date" name="date_evenement" class="form-control" required/></div>
        <div class="form-group"><label class="form-label">Durée (heures)</label><input type="number" name="duree" class="form-control"/></div>
        <div class="form-group"><label class="form-label">Lieu</label><input type="text" name="lieu" class="form-control"/></div>
        <div class="form-group"><label class="form-label">Capacité</label><input type="number" name="capacite" class="form-control" value="50"/></div>
        <div class="form-group"><label class="form-label">Statut</label><select name="statut" class="form-control"><option value="brouillon">Brouillon</option><option value="en_attente">En attente</option><option value="actif">Actif</option></select></div>
        <div class="form-group">
          <label class="form-label">Affiche de l'événement</label>
          <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/jpg,image/webp" onchange="previewImage(this, 'createPreview')"/>
          <div id="createPreview" class="image-preview"></div>
          <small style="color: var(--text-muted); font-size: .7rem;">Formats: JPG, PNG, WebP (max 2MB)</small>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn-admin btn-ghost" onclick="closeModal()">Annuler</button><button type="submit" class="btn-admin btn-primary-admin">Créer</button></div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
  <div class="modal">
    <form method="POST" id="editForm" enctype="multipart/form-data">
      <div class="modal-header"><h2>✏ Modifier</h2><button type="button" class="modal-close" onclick="closeEditModal()">✕</button></div>
      <div class="modal-body">
        <input type="hidden" name="id" id="edit_id"/>
        <div class="form-group"><label class="form-label">Titre</label><input type="text" name="titre" id="edit_titre" class="form-control" required/></div>
        <div class="form-group"><label class="form-label">Type</label><select name="type" id="edit_type" class="form-control"><option value="formation">Formation</option><option value="webinaire">Webinaire</option><option value="meetup">Meetup</option><option value="atelier">Atelier</option></select></div>
        <div class="form-group"><label class="form-label">Description</label><textarea name="description" id="edit_description" class="form-control"></textarea></div>
        <div class="form-group"><label class="form-label">Date</label><input type="date" name="date_evenement" id="edit_date" class="form-control" required/></div>
        <div class="form-group"><label class="form-label">Durée</label><input type="number" name="duree" id="edit_duree" class="form-control"/></div>
        <div class="form-group"><label class="form-label">Lieu</label><input type="text" name="lieu" id="edit_lieu" class="form-control"/></div>
        <div class="form-group"><label class="form-label">Capacité</label><input type="number" name="capacite" id="edit_capacite" class="form-control"/></div>
        <div class="form-group"><label class="form-label">Statut</label><select name="statut" id="edit_statut" class="form-control"><option value="brouillon">Brouillon</option><option value="en_attente">En attente</option><option value="actif">Actif</option></select></div>
        <div class="form-group">
          <label class="form-label">Affiche de l'événement</label>
            <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/jpg,image/webp" onchange="previewImage(this, 'editPreview')"/>
          <div id="editPreview" class="image-preview"></div>
          <div id="currentImageInfo" class="current-image"></div>
          <small style="color: var(--text-muted); font-size: .7rem;">Laissez vide pour garder l'image actuelle</small>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn-admin btn-ghost" onclick="closeEditModal()">Annuler</button><button type="submit" class="btn-admin btn-primary-admin">Mettre à jour</button></div>
    </form>
  </div>
</div>

<script>
  function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (input.files && input.files[0]) {
      const reader = new FileReader();
      reader.onload = function(e) {
        preview.innerHTML = '<img src="' + e.target.result + '" style="max-width: 100px; border-radius: 8px;">';
      }
      reader.readAsDataURL(input.files[0]);
    } else {
      preview.innerHTML = '';
    }
  }

  function openModal() { 
    document.getElementById('createModal').classList.add('open'); 
    document.getElementById('createPreview').innerHTML = '';
  }
  
  function closeModal() { 
    document.getElementById('createModal').classList.remove('open'); 
  }
  
  function closeEditModal() { 
    document.getElementById('editModal').classList.remove('open'); 
  }
  
  document.getElementById('createModal').addEventListener('click', function(e) { 
    if (e.target === this) closeModal(); 
  });
  
  document.getElementById('editModal').addEventListener('click', function(e) { 
    if (e.target === this) closeEditModal(); 
  });
  
  function editEvent(id) {
    console.log('Chargement événement ID:', id);
    
    fetch('/ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/evenementC.php?action=get&id=' + id)
        .then(response => {
            console.log('Status:', response.status);
            return response.text();
        })
        .then(text => {
            console.log('Réponse brute:', text);
            
            try {
                const data = JSON.parse(text);
                
                if (data.error) {
                    alert('Erreur: ' + data.error);
                    return;
                }
                
                document.getElementById('edit_id').value = data.id;
                document.getElementById('edit_titre').value = data.titre;
                document.getElementById('edit_description').value = data.description;
                document.getElementById('edit_duree').value = data.duree;
                document.getElementById('edit_date').value = data.date_evenement;
                document.getElementById('edit_type').value = data.type;
                document.getElementById('edit_statut').value = data.statut;
                document.getElementById('edit_lieu').value = data.lieu;
                document.getElementById('edit_capacite').value = data.capacite;
                
                if (data.image) {
                    document.getElementById('currentImageInfo').innerHTML = '<strong>Image actuelle:</strong><br><img src="/ProjetWeb/Esprit-PW-2A22-2526-Devcore/' + data.image + '" style="max-width: 100px; border-radius: 8px; margin-top: 5px;">';
                } else {
                    document.getElementById('currentImageInfo').innerHTML = '<strong>Aucune image</strong>';
                }
                
                document.getElementById('editForm').action = '/ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/evenementC.php?action=edit&id=' + id;
                document.getElementById('editModal').classList.add('open');
                document.getElementById('editPreview').innerHTML = '';
                
            } catch (e) {
                console.error('Erreur de parsing:', e);
                alert('Erreur: La réponse n\'est pas du JSON valide. Vérifie la console (F12)');
            }
        })
        .catch(error => {
            console.error('Erreur réseau:', error);
            alert('Erreur de connexion: ' + error.message);
        });
  }
  
  function deleteEvent(id, titre) {
    if (confirm(`Supprimer "${titre}" ?`)) {
      window.location.href = '/ProjetWeb/Esprit-PW-2A22-2526-Devcore/Controleur/evenementC.php?action=delete&id=' + id;
    }
  }
  
  function filterTable() {
    const input = document.getElementById('searchInput');
    const filter = input.value.toLowerCase();
    const rows = document.querySelectorAll('tbody tr');
    rows.forEach(row => {
      const titleCell = row.cells[2];
      if (titleCell) {
        row.style.display = titleCell.textContent.toLowerCase().includes(filter) ? '' : 'none';
      }
    });
  }
</script>
</body>
</html>