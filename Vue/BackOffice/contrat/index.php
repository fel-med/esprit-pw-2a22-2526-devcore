php

<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../../Controleur/contratC.php';

$controller = new ContratC();
$action     = $_GET['action'] ?? 'index';

if ($action === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    $controller->delete($id);
    header('Location: index.php?action=index');
    exit;
}

if ($action === 'updateStatut' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller->updateStatut((int)$_POST['id'], $_POST['statut'] ?? '');
    header('Location: index.php?action=index');
    exit;
}

$contrats = $controller->getAll();
$stats    = $controller->getStats();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Contrats — Admin · Cre8Connect</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Syne:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --bg-base:       #0d0f14;
            --bg-surface:    #141720;
            --bg-card:       #1a1e2a;
            --bg-hover:      #202535;
            --border:        #2a2f42;
            --border-light:  #323750;
            --accent:        #6c63ff;
            --accent-soft:   rgba(108,99,255,.15);
            --accent-hover:  #8b84ff;
            --success:       #22c55e;
            --success-soft:  rgba(34,197,94,.15);
            --warning:       #f59e0b;
            --warning-soft:  rgba(245,158,11,.15);
            --danger:        #ef4444;
            --danger-soft:   rgba(239,68,68,.15);
            --text-primary:  #eef0f8;
            --text-secondary:#9097b8;
            --text-muted:    #5a6080;
            --radius:        10px;
            --radius-lg:     16px;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Syne',sans-serif; background:var(--bg-base); color:var(--text-primary); min-height:100vh; }

        .layout { display:flex; min-height:100vh; }

        /* SIDEBAR */
        .sidebar {
            width:240px; background:var(--bg-surface);
            border-right:1px solid var(--border);
            display:flex; flex-direction:column;
            padding:24px 0; position:sticky; top:0; height:100vh;
        }
        .sidebar-logo {
            padding:0 24px 24px; font-size:1.3rem; font-weight:800;
            color:var(--accent); border-bottom:1px solid var(--border); letter-spacing:-.5px;
        }
        .sidebar-logo span { color:var(--text-primary); }
        .sidebar-nav { padding:16px 12px; flex:1; }
        .nav-label {
            font-size:.65rem; font-weight:700; letter-spacing:2px;
            color:var(--text-muted); text-transform:uppercase;
            padding:0 12px; margin:16px 0 6px;
        }
        .nav-item {
            display:flex; align-items:center; gap:10px;
            padding:10px 12px; border-radius:var(--radius);
            color:var(--text-secondary); text-decoration:none;
            font-size:.88rem; font-weight:600; transition:all .15s;
        }
        .nav-item:hover, .nav-item.active { background:var(--accent-soft); color:var(--accent-hover); }
        .nav-item i { width:16px; text-align:center; }

        /* MAIN */
        .main { flex:1; display:flex; flex-direction:column; }

        /* TOPBAR */
        .topbar {
            background:var(--bg-surface); border-bottom:1px solid var(--border);
            padding:16px 32px; display:flex; align-items:center; justify-content:space-between;
        }
        .topbar-title { font-size:1.1rem; font-weight:700; }
        .topbar-title span { color:var(--text-muted); font-weight:400; font-size:.85rem; margin-left:8px; }
        .topbar-user { display:flex; align-items:center; gap:10px; font-size:.85rem; color:var(--text-secondary); }
        .avatar {
            width:34px; height:34px; border-radius:50%;
            background:var(--accent-soft); border:2px solid var(--accent);
            display:flex; align-items:center; justify-content:center;
            color:var(--accent); font-size:.8rem; font-weight:700;
        }

        /* CONTENT */
        .content { padding:32px; flex:1; overflow-y:auto; }

        /* KPI */
        .kpi-grid {
            display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr));
            gap:16px; margin-bottom:32px;
        }
        .kpi-card {
            background:var(--bg-card); border:1px solid var(--border);
            border-radius:var(--radius-lg); padding:20px;
            display:flex; flex-direction:column; gap:8px;
        }
        .kpi-label { font-size:.72rem; font-weight:700; letter-spacing:1.5px; text-transform:uppercase; color:var(--text-muted); }
        .kpi-value { font-size:1.9rem; font-weight:800; line-height:1; }
        .kpi-card.total    .kpi-value { color:var(--text-primary); }
        .kpi-card.pending  .kpi-value { color:var(--warning); }
        .kpi-card.signed   .kpi-value { color:var(--success); }
        .kpi-card.resilied .kpi-value { color:var(--danger); }
        .kpi-card.expired  .kpi-value { color:var(--text-muted); }
        .kpi-card.amount   .kpi-value { color:var(--accent); font-size:1.4rem; }

        /* PANEL */
        .panel { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius-lg); }
        .panel-header {
            padding:20px 24px; border-bottom:1px solid var(--border);
            display:flex; align-items:center; justify-content:space-between;
        }
        .panel-title { font-size:1rem; font-weight:700; }
        .panel-meta { font-size:.8rem; color:var(--text-muted); }

        /* TABLE */
        .table-wrap { overflow-x:auto; }
        table { width:100%; border-collapse:collapse; }
        th {
            text-align:left; padding:12px 20px;
            font-size:.7rem; font-weight:700; letter-spacing:1.5px;
            text-transform:uppercase; color:var(--text-muted);
            border-bottom:1px solid var(--border);
        }
        td { padding:14px 20px; font-size:.85rem; border-bottom:1px solid var(--border); vertical-align:middle; }
        tr:last-child td { border-bottom:none; }
        tr:hover td { background:var(--bg-hover); }

        /* BADGE */
        .badge {
            display:inline-flex; align-items:center; gap:5px;
            padding:4px 10px; border-radius:20px;
            font-size:.72rem; font-weight:700; letter-spacing:.5px; text-transform:uppercase;
        }
        .badge-pending  { background:var(--warning-soft); color:var(--warning); }
        .badge-signed   { background:var(--success-soft); color:var(--success); }
        .badge-resilied { background:var(--danger-soft);  color:var(--danger);  }
        .badge-expired  { background:rgba(90,96,128,.2);  color:var(--text-muted); }

        /* BUTTONS */
        .btn {
            display:inline-flex; align-items:center; gap:6px;
            padding:7px 14px; border-radius:8px;
            font-family:inherit; font-size:.8rem; font-weight:700;
            cursor:pointer; text-decoration:none;
            border:1px solid transparent; transition:all .15s;
        }
        .btn-danger  { background:var(--danger-soft); color:var(--danger); border-color:var(--danger); }
        .btn-danger:hover  { background:var(--danger); color:#fff; }
        .btn-sm { padding:5px 10px; font-size:.75rem; }

        /* SELECT STATUT */
        .select-statut {
            background:var(--bg-surface); border:1px solid var(--border-light);
            color:var(--text-primary); font-family:inherit; font-size:.8rem;
            border-radius:8px; padding:5px 10px; cursor:pointer;
        }
        .select-statut:focus { outline:none; border-color:var(--accent); }

        .montant { font-family:'DM Mono',monospace; font-weight:500; color:var(--accent); }

        /* EMPTY */
        .empty-state { text-align:center; padding:60px 20px; color:var(--text-muted); }
        .empty-state i { font-size:2.5rem; margin-bottom:12px; opacity:.4; display:block; }

        /* MODAL */
        .modal-overlay {
            display:none; position:fixed; inset:0;
            background:rgba(0,0,0,.7); z-index:100;
            align-items:center; justify-content:center;
        }
        .modal-overlay.open { display:flex; }
        .modal {
            background:var(--bg-card); border:1px solid var(--border-light);
            border-radius:var(--radius-lg); padding:28px; width:100%; max-width:420px;
        }
        .modal h3 { font-size:1rem; margin-bottom:16px; }
        .modal p  { font-size:.875rem; color:var(--text-secondary); margin-bottom:20px; line-height:1.6; }
        .modal-actions { display:flex; gap:10px; justify-content:flex-end; }
        .btn-warning { background:var(--warning-soft); color:var(--warning); border:1px solid var(--warning); }
        .btn-warning:hover { background:var(--warning); color:#fff; }
    </style>
</head>
<body>
<div class="layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-logo">Cre8<span>Connect</span></div>
        <nav class="sidebar-nav">
            <div class="nav-label">Tableau de bord</div>
            <a href="#" class="nav-item"><i class="fas fa-chart-pie"></i> Aperçu</a>
            <div class="nav-label">Modules</div>
            <a href="#" class="nav-item"><i class="fas fa-users"></i> Utilisateurs</a>
            <a href="#" class="nav-item"><i class="fas fa-bullhorn"></i> Offres</a>
            <a href="#" class="nav-item"><i class="fas fa-rocket"></i> Campagnes</a>
            <a href="index.php?action=index" class="nav-item active"><i class="fas fa-file-signature"></i> Contrats</a>
            <a href="#" class="nav-item"><i class="fas fa-calendar-alt"></i> Événements</a>
            <a href="#" class="nav-item"><i class="fas fa-newspaper"></i> Posts</a>
            <a href="#" class="nav-item"><i class="fas fa-flag"></i> Réclamations</a>
        </nav>
    </aside>

    <!-- MAIN -->
    <div class="main">

        <!-- TOPBAR -->
        <header class="topbar">
            <div>
                <span class="topbar-title">Gestion des Contrats</span>
                <span>Supervision et modération</span>
            </div>
            <div class="topbar-user">
                <div class="avatar">A</div>
                Admin
            </div>
        </header>

        <!-- CONTENT -->
        <main class="content">

            <!-- KPI -->
            <div class="kpi-grid">
                <div class="kpi-card total">
                    <div class="kpi-label">Total</div>
                    <div class="kpi-value"><?= $stats['total'] ?? 0 ?></div>
                </div>
                <div class="kpi-card pending">
                    <div class="kpi-label">En attente</div>
                    <div class="kpi-value"><?= $stats['en_attente'] ?? 0 ?></div>
                </div>
                <div class="kpi-card signed">
                    <div class="kpi-label">Signés</div>
                    <div class="kpi-value"><?= $stats['signes'] ?? 0 ?></div>
                </div>
                <div class="kpi-card resilied">
                    <div class="kpi-label">Résiliés</div>
                    <div class="kpi-value"><?= $stats['resilies'] ?? 0 ?></div>
                </div>
                <div class="kpi-card expired">
                    <div class="kpi-label">Expirés</div>
                    <div class="kpi-value"><?= $stats['expires'] ?? 0 ?></div>
                </div>
                <div class="kpi-card amount">
                    <div class="kpi-label">Valeur totale</div>
                    <div class="kpi-value"><?= number_format($stats['montant_total'] ?? 0, 0, ',', ' ') ?> €</div>
                </div>
            </div>

            <!-- TABLE -->
            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title">
                        <i class="fas fa-file-signature" style="color:var(--accent);margin-right:8px"></i>
                        Tous les contrats
                    </div>
                    <div class="panel-meta"><?= count($contrats) ?> contrat(s)</div>
                </div>

                <div class="table-wrap">
                    <?php if (empty($contrats)): ?>
                        <div class="empty-state">
                            <i class="fas fa-file-circle-xmark"></i>
                            Aucun contrat enregistré pour le moment.
                        </div>
                    <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Titre</th>
                                <th>Campagne</th>
                                <th>Marque</th>
                                <th>Créateur</th>
                                <th>Montant</th>
                                <th>Période</th>
                                <th>Statut</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($contrats as $c): ?>
                            <tr>
                                <td style="color:var(--text-muted);font-family:'DM Mono',monospace;font-size:.78rem">#<?= $c['id'] ?></td>
                                <td style="font-weight:600"><?= htmlspecialchars($c['titre']) ?></td>
                                <td style="color:var(--text-secondary);font-size:.82rem"><?= htmlspecialchars($c['titreCampagne'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($c['nomMarque'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($c['nomCreateur'] ?? '—') ?></td>
                                <td class="montant"><?= number_format($c['montant'], 2, ',', ' ') ?> €</td>
                                <td style="font-size:.8rem;color:var(--text-secondary)">
                                    <?= date('d/m/Y', strtotime($c['date_debut'])) ?><br>
                                    <span style="color:var(--text-muted)">→ <?= date('d/m/Y', strtotime($c['date_fin'])) ?></span>
                                </td>
                                <td>
                                    <form method="POST" action="index.php?action=updateStatut">
                                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                        <select name="statut" class="select-statut" onchange="this.form.submit()">
                                            <option value="en_attente" <?= $c['statut'] === 'en_attente' ? 'selected' : '' ?>>⏳ En attente</option>
                                            <option value="signe"      <?= $c['statut'] === 'signe'      ? 'selected' : '' ?>>✅ Signé</option>
                                            <option value="resilie"    <?= $c['statut'] === 'resilie'    ? 'selected' : '' ?>>❌ Résilié</option>
                                            <option value="expire"     <?= $c['statut'] === 'expire'     ? 'selected' : '' ?>>🕐 Expiré</option>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <button class="btn btn-danger btn-sm"
                                            onclick="confirmDelete(<?= $c['id'] ?>, '<?= htmlspecialchars(addslashes($c['titre'])) ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>

        </main>
    </div>
</div>

<!-- MODAL SUPPRESSION -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal">
        <h3><i class="fas fa-exclamation-triangle" style="color:var(--danger);margin-right:8px"></i>Confirmer la suppression</h3>
        <p>Vous êtes sur le point de supprimer le contrat <strong id="deleteTitle"></strong>. Cette action est irréversible.</p>
        <div class="modal-actions">
            <button class="btn btn-warning" onclick="closeModal()">Annuler</button>
            <a id="deleteLink" href="#" class="btn btn-danger"><i class="fas fa-trash"></i> Supprimer</a>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, titre) {
    document.getElementById('deleteTitle').textContent = '"' + titre + '"';
    document.getElementById('deleteLink').href = 'index.php?action=delete&id=' + id;
    document.getElementById('deleteModal').classList.add('open');
}
function closeModal() {
    document.getElementById('deleteModal').classList.remove('open');
}
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>
</body>
</html>