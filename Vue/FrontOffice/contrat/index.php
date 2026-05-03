<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// ── TEMPORAIRE : à supprimer après intégration de l'authentification ──
$_SESSION['id']   = 2;  // remplace par l'id réel d'une marque dans ta table utilisateur
$_SESSION['role'] = 'marque';
require_once __DIR__ . '/../../../Controleur/contratC.php';

$controller = new ContratC();
$action     = $_GET['action'] ?? 'index';
$idMarque   = 2;
$contrat    = null;

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $obj = new Contrat(
        null,
        (int)$_POST['id_campagne'],
         2, 
        (int)$_POST['id_createur'],
        trim($_POST['titre']),
        trim($_POST['description']),
        (float)$_POST['montant'],
        $_POST['date_debut'],
        $_POST['date_fin']
    );
    $controller->create($obj);
    header('Location: index.php?action=index');
    exit;
}

if ($action === 'edit') {
    $id      = (int)($_GET['id'] ?? 0);
    $contrat = $controller->getById($id);
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $contrat) {
        $obj = new Contrat(
            $id,
            $contrat['id_campagne'],
            $idMarque,
            $contrat['id_createur'],
            trim($_POST['titre']),
            trim($_POST['description']),
            (float)$_POST['montant'],
            $_POST['date_debut'],
            $_POST['date_fin'],
            $_POST['statut'] ?? $contrat['statut'],
            $contrat['date_creation']
        );
        $controller->update($obj);
        header('Location: index.php?action=index');
        exit;
    }
}

$contrats = $controller->getByMarque($idMarque);
$isEdit   = ($action === 'edit') && isset($contrat);
$isCreate = ($action === 'create');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Contrats — Cre8Connect</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --bg:            #fafafa;
            --bg-white:      #ffffff;
            --bg-soft:       #f4f5f9;
            --border:        #e8eaf0;
            --border-dark:   #d1d5e0;
            --accent:        #5b4cf5;
            --accent-soft:   rgba(91,76,245,.08);
            --accent-hover:  #7366f7;
            --success:       #16a34a;
            --success-soft:  rgba(22,163,74,.1);
            --warning:       #d97706;
            --warning-soft:  rgba(217,119,6,.1);
            --danger:        #dc2626;
            --danger-soft:   rgba(220,38,38,.1);
            --neutral:       #6b7280;
            --neutral-soft:  rgba(107,114,128,.1);
            --text-primary:  #111827;
            --text-secondary:#4b5563;
            --text-muted:    #9ca3af;
            --shadow-sm:     0 1px 3px rgba(0,0,0,.06);
            --shadow-md:     0 4px 16px rgba(0,0,0,.08);
            --radius:        12px;
            --radius-lg:     20px;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Plus Jakarta Sans',sans-serif; background:var(--bg); color:var(--text-primary); min-height:100vh; }

        /* NAV */
        nav {
            background:var(--bg-white); border-bottom:1px solid var(--border);
            padding:0 40px; display:flex; align-items:center;
            justify-content:space-between; height:64px;
            position:sticky; top:0; z-index:50; box-shadow:var(--shadow-sm);
        }
        .nav-logo { font-size:1.25rem; font-weight:800; color:var(--accent); letter-spacing:-.5px; }
        .nav-logo em { color:var(--text-primary); font-style:normal; }
        .nav-links { display:flex; gap:4px; }
        .nav-link {
            padding:8px 16px; border-radius:8px; color:var(--text-secondary);
            text-decoration:none; font-size:.875rem; font-weight:600; transition:all .15s;
        }
        .nav-link:hover, .nav-link.active { background:var(--accent-soft); color:var(--accent); }
        .avatar-pill {
            display:flex; align-items:center; gap:8px;
            background:var(--bg-soft); border:1px solid var(--border);
            border-radius:30px; padding:6px 14px 6px 6px;
            font-size:.825rem; font-weight:600; color:var(--text-secondary);
        }
        .avatar-circle {
            width:28px; height:28px; border-radius:50%;
            background:var(--accent); color:#fff;
            display:flex; align-items:center; justify-content:center;
            font-size:.75rem; font-weight:800;
        }

        /* HERO */
        .page-header {
            background:linear-gradient(135deg,var(--accent) 0%,#8b5cf6 100%);
            padding:48px 40px; color:white;
        }
        .page-header-inner {
            max-width:1200px; margin:0 auto;
            display:flex; align-items:center; justify-content:space-between;
        }
        .page-header h1 {
            font-family:'Instrument Serif',serif; font-size:2rem;
            font-weight:400; line-height:1.2; margin-bottom:6px;
        }
        .page-header p { font-size:.9rem; opacity:.85; }

        /* CONTAINER */
        .container { max-width:1200px; margin:0 auto; padding:40px; }

        /* STATS */
        .stats-bar {
            display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr));
            gap:16px; margin-bottom:36px;
        }
        .stat-card {
            background:var(--bg-white); border:1px solid var(--border);
            border-radius:var(--radius); padding:20px;
            display:flex; flex-direction:column; gap:4px; box-shadow:var(--shadow-sm);
        }
        .stat-label { font-size:.72rem; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:1px; }
        .stat-value { font-size:1.8rem; font-weight:800; }
        .stat-card.pending .stat-value { color:var(--warning); }
        .stat-card.signed  .stat-value { color:var(--success); }
        .stat-card.total   .stat-value { color:var(--accent); }

        /* ACTIONS BAR */
        .actions-bar {
            display:flex; align-items:center; justify-content:space-between;
            margin-bottom:24px; gap:16px; flex-wrap:wrap;
        }
        .section-title { font-size:1.1rem; font-weight:700; display:flex; align-items:center; gap:8px; }
        .section-title i { color:var(--accent); }

        /* BUTTONS */
        .btn {
            display:inline-flex; align-items:center; gap:7px;
            padding:10px 20px; border-radius:10px;
            font-family:inherit; font-size:.875rem; font-weight:700;
            cursor:pointer; text-decoration:none; border:none; transition:all .15s;
        }
        .btn-primary { background:var(--accent); color:#fff; box-shadow:0 4px 12px rgba(91,76,245,.3); }
        .btn-primary:hover { background:var(--accent-hover); transform:translateY(-1px); }
        .btn-outline { background:transparent; color:var(--text-secondary); border:1px solid var(--border-dark); }
        .btn-outline:hover { background:var(--bg-soft); color:var(--text-primary); }
        .btn-sm { padding:7px 14px; font-size:.8rem; }

        /* CARDS */
        .cards-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(340px,1fr)); gap:20px; }
        .contract-card {
            background:var(--bg-white); border:1px solid var(--border);
            border-radius:var(--radius-lg); padding:24px;
            box-shadow:var(--shadow-sm); transition:box-shadow .2s, transform .2s;
            position:relative; overflow:hidden;
        }
        .contract-card:hover { box-shadow:var(--shadow-md); transform:translateY(-2px); }
        .contract-card::before {
            content:''; position:absolute; top:0; left:0; right:0; height:4px;
        }
        .contract-card.en_attente::before { background:var(--warning); }
        .contract-card.signe::before      { background:var(--success); }
        .contract-card.resilie::before    { background:var(--danger);  }
        .contract-card.expire::before     { background:var(--neutral); }

        .card-top { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:16px; }
        .card-title { font-size:1rem; font-weight:700; margin-bottom:4px; }
        .card-campaign { font-size:.8rem; color:var(--text-muted); }

        .badge {
            display:inline-flex; align-items:center; gap:4px;
            padding:4px 10px; border-radius:20px;
            font-size:.72rem; font-weight:700; text-transform:uppercase;
            letter-spacing:.5px; white-space:nowrap;
        }
        .badge-pending  { background:var(--warning-soft); color:var(--warning); }
        .badge-signed   { background:var(--success-soft); color:var(--success); }
        .badge-resilie  { background:var(--danger-soft);  color:var(--danger);  }
        .badge-expire   { background:var(--neutral-soft); color:var(--neutral); }

        .card-info { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:16px; }
        .info-label { font-size:.72rem; color:var(--text-muted); font-weight:600; text-transform:uppercase; letter-spacing:.5px; margin-bottom:2px; }
        .info-value { font-size:.9rem; font-weight:600; }
        .info-value.montant { color:var(--accent); font-size:1.1rem; }

        .card-creator {
            display:flex; align-items:center; gap:8px;
            padding:10px 14px; background:var(--bg-soft);
            border-radius:10px; margin-bottom:16px; font-size:.825rem;
        }
        .creator-avatar {
            width:28px; height:28px; border-radius:50%;
            background:linear-gradient(135deg,var(--accent),#8b5cf6);
            color:#fff; font-size:.72rem; font-weight:800;
            display:flex; align-items:center; justify-content:center;
        }
        .card-actions { display:flex; gap:8px; flex-wrap:wrap; }

        /* FORM */
        .form-card {
            background:var(--bg-white); border:1px solid var(--border);
            border-radius:var(--radius-lg); padding:32px;
            box-shadow:var(--shadow-md); max-width:700px;
        }
        .form-title { font-size:1.2rem; font-weight:700; margin-bottom:24px; display:flex; align-items:center; gap:8px; }
        .form-title i { color:var(--accent); }
        .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
        .form-group { display:flex; flex-direction:column; gap:6px; }
        .form-group.full { grid-column:1/-1; }
        label { font-size:.825rem; font-weight:700; color:var(--text-secondary); }
        input, select, textarea {
            padding:10px 14px; border:1px solid var(--border-dark);
            border-radius:10px; font-family:inherit; font-size:.875rem;
            color:var(--text-primary); background:var(--bg); transition:border-color .15s, box-shadow .15s;
        }
        input:focus, select:focus, textarea:focus {
            outline:none; border-color:var(--accent); box-shadow:0 0 0 3px var(--accent-soft);
        }
        textarea { resize:vertical; min-height:100px; }
        .form-actions { display:flex; gap:12px; margin-top:24px; }

        /* EMPTY */
        .empty-state { text-align:center; padding:80px 20px; color:var(--text-muted); }
        .empty-state .icon {
            width:80px; height:80px; border-radius:50%;
            background:var(--accent-soft);
            display:flex; align-items:center; justify-content:center;
            margin:0 auto 20px; font-size:2rem; color:var(--accent);
        }
        .empty-state h3 { font-size:1.1rem; color:var(--text-secondary); margin-bottom:8px; }
        .empty-state p  { font-size:.875rem; margin-bottom:24px; }
    </style>
</head>
<body>

<!-- NAV -->
<nav>
    <div class="nav-logo">Cre8<em>Connect</em></div>
    <div class="nav-links">
        <a href="#" class="nav-link">Dashboard</a>
        <a href="#" class="nav-link">Mes Offres</a>
        <a href="#" class="nav-link">Campagnes</a>
        <a href="index.php?action=index" class="nav-link active">Contrats</a>
        <a href="#" class="nav-link">Événements</a>
    </div>
    <div class="avatar-pill">
        <div class="avatar-circle">M</div>
        Ma Marque
    </div>
</nav>

<!-- HERO -->
<div class="page-header">
    <div class="page-header-inner">
        <div>
            <h1>Mes Contrats</h1>
            <p>Gérez vos accords avec les créateurs de contenu</p>
        </div>
        <?php if (!$isEdit && !$isCreate): ?>
        <a href="index.php?action=create" class="btn" style="background:rgba(255,255,255,.2);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.3);color:#fff">
            <i class="fas fa-plus"></i> Nouveau Contrat
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="container">

<?php if (!$isEdit && !$isCreate): ?>

    <!-- STATS -->
    <?php
        $total    = count($contrats);
        $pending  = count(array_filter($contrats, fn($c) => $c['statut'] === 'en_attente'));
        $signed   = count(array_filter($contrats, fn($c) => $c['statut'] === 'signe'));
        $totalVal = array_sum(array_column($contrats, 'montant'));
    ?>
    <div class="stats-bar">
        <div class="stat-card total">
            <div class="stat-label">Contrats</div>
            <div class="stat-value"><?= $total ?></div>
        </div>
        <div class="stat-card pending">
            <div class="stat-label">En attente</div>
            <div class="stat-value"><?= $pending ?></div>
        </div>
        <div class="stat-card signed">
            <div class="stat-label">Signés</div>
            <div class="stat-value"><?= $signed ?></div>
        </div>
        <div class="stat-card total">
            <div class="stat-label">Valeur totale</div>
            <div class="stat-value" style="font-size:1.2rem"><?= number_format($totalVal,0,',',' ') ?> €</div>
        </div>
    </div>

    <!-- LISTE -->
    <div class="actions-bar">
        <div class="section-title"><i class="fas fa-file-signature"></i> Tous mes contrats</div>
        <a href="index.php?action=create" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Créer</a>
    </div>

    <?php if (empty($contrats)): ?>
        <div class="empty-state">
            <div class="icon"><i class="fas fa-file-signature"></i></div>
            <h3>Aucun contrat pour l'instant</h3>
            <p>Créez votre premier contrat avec un créateur de contenu.</p>
            <a href="index.php?action=create" class="btn btn-primary"><i class="fas fa-plus"></i> Créer un contrat</a>
        </div>
    <?php else: ?>
        <div class="cards-grid">
        <?php foreach ($contrats as $c):
            $statut = $c['statut'];
            $badgeClass = match($statut) {
                'signe'     => 'badge-signed',
                'resilie'   => 'badge-resilie',
                'expire'    => 'badge-expire',
                default     => 'badge-pending',
            };
            $badgeLabel = match($statut) {
                'signe'     => '✅ Signé',
                'resilie'   => '❌ Résilié',
                'expire'    => '🕐 Expiré',
                default     => '⏳ En attente',
            };
            $initiales = strtoupper(substr($c['nomCreateur'] ?? 'C', 0, 1));
        ?>
        <div class="contract-card <?= $statut ?>">
            <div class="card-top">
                <div>
                    <div class="card-title"><?= htmlspecialchars($c['titre']) ?></div>
                    <div class="card-campaign"><i class="fas fa-rocket" style="margin-right:4px;color:var(--accent)"></i><?= htmlspecialchars($c['titreCampagne'] ?? 'N/A') ?></div>
                </div>
                <span class="badge <?= $badgeClass ?>"><?= $badgeLabel ?></span>
            </div>

            <div class="card-creator">
                <div class="creator-avatar"><?= $initiales ?></div>
                <div>
                    <div style="font-weight:700;font-size:.85rem"><?= htmlspecialchars($c['nomCreateur'] ?? '—') ?></div>
                    <div style="font-size:.75rem;color:var(--text-muted)">Créateur de contenu</div>
                </div>
            </div>

            <div class="card-info">
                <div>
                    <div class="info-label">Montant</div>
                    <div class="info-value montant"><?= number_format($c['montant'],2,',',' ') ?> €</div>
                </div>
                <div>
                    <div class="info-label">Début</div>
                    <div class="info-value"><?= date('d/m/Y', strtotime($c['date_debut'])) ?></div>
                </div>
                <div>
                    <div class="info-label">Fin</div>
                    <div class="info-value"><?= date('d/m/Y', strtotime($c['date_fin'])) ?></div>
                </div>
                <div>
                    <div class="info-label">Créé le</div>
                    <div class="info-value" style="font-size:.82rem;color:var(--text-muted)"><?= date('d/m/Y', strtotime($c['date_creation'])) ?></div>
                </div>
            </div>

            <?php if ($statut === 'en_attente'): ?>
            <div class="card-actions">
                <a href="index.php?action=edit&id=<?= $c['id'] ?>" class="btn btn-outline btn-sm">
                    <i class="fas fa-pen"></i> Modifier
                </a>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php else: ?>

    <!-- FORMULAIRE CRÉATION / ÉDITION -->
    <a href="index.php?action=index" class="btn btn-outline btn-sm" style="margin-bottom:24px">
        <i class="fas fa-arrow-left"></i> Retour
    </a>

    <div class="form-card">
        <div class="form-title">
            <i class="fas fa-<?= $isEdit ? 'pen' : 'plus-circle' ?>"></i>
            <?= $isEdit ? 'Modifier le contrat' : 'Créer un nouveau contrat' ?>
        </div>

        <form method="POST" action="index.php?action=<?= $isEdit ? 'edit&id='.$contrat['id'] : 'create' ?>">
            <div class="form-grid">
                <div class="form-group full">
                    <label for="titre">Titre du contrat *</label>
                    <input type="text" id="titre" name="titre" required
                           value="<?= htmlspecialchars($contrat['titre'] ?? '') ?>"
                           placeholder="Ex : Partenariat influencer été 2025">
                </div>
                <div class="form-group full">
                    <label for="description">Description *</label>
                    <textarea id="description" name="description" required
                              placeholder="Détails des obligations et livrables..."><?= htmlspecialchars($contrat['description'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label for="id_campagne">ID Campagne *</label>
                    <input type="number" id="id_campagne" name="id_campagne" required
                           value="<?= $contrat['id_campagne'] ?? '' ?>">
                </div>
                <div class="form-group">
                    <label for="id_createur">ID Créateur *</label>
                    <input type="number" id="id_createur" name="id_createur" required
                           value="<?= $contrat['id_createur'] ?? '' ?>">
                </div>
                <div class="form-group">
                    <label for="montant">Montant (€) *</label>
                    <input type="number" id="montant" name="montant" step="0.01" min="0" required
                           value="<?= $contrat['montant'] ?? '' ?>" placeholder="0.00">
                </div>
                <?php if ($isEdit): ?>
                <div class="form-group">
                    <label for="statut">Statut</label>
                    <select id="statut" name="statut">
                        <option value="en_attente" <?= ($contrat['statut'] ?? '') === 'en_attente' ? 'selected' : '' ?>>⏳ En attente</option>
                        <option value="signe"      <?= ($contrat['statut'] ?? '') === 'signe'      ? 'selected' : '' ?>>✅ Signé</option>
                        <option value="resilie"    <?= ($contrat['statut'] ?? '') === 'resilie'    ? 'selected' : '' ?>>❌ Résilié</option>
                        <option value="expire"     <?= ($contrat['statut'] ?? '') === 'expire'     ? 'selected' : '' ?>>🕐 Expiré</option>
                    </select>
                </div>
                <?php endif; ?>
                <div class="form-group">
                    <label for="date_debut">Date de début *</label>
                    <input type="date" id="date_debut" name="date_debut" required
                           value="<?= $contrat['date_debut'] ?? '' ?>">
                </div>
                <div class="form-group">
                    <label for="date_fin">Date de fin *</label>
                    <input type="date" id="date_fin" name="date_fin" required
                           value="<?= $contrat['date_fin'] ?? '' ?>">
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-<?= $isEdit ? 'save' : 'paper-plane' ?>"></i>
                    <?= $isEdit ? 'Enregistrer' : 'Créer le contrat' ?>
                </button>
                <a href="index.php?action=index" class="btn btn-outline">Annuler</a>
            </div>
        </form>
    </div>

<?php endif; ?>
</div>
</body>
</html>