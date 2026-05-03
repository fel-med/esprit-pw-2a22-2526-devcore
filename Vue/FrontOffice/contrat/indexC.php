<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// ── TEMPORAIRE : à supprimer après intégration de l'authentification ──
$_SESSION['id']   = 4;  // Sophie Martin — créateur
$_SESSION['role'] = 'createur';
// ─────────────────────────────────────────────────────────────────────

require_once __DIR__ . '/../../../Controleur/contratC.php';

$controller = new ContratC();
$action     = $_GET['action'] ?? 'index';
$idCreateur = 4;

if ($action === 'signer') {
    $id = (int)($_GET['id'] ?? 0);
    $controller->updateStatut($id, 'signe');
    header('Location: indexC.php?action=index');
    exit;
}

$contrats = $controller->getByCreateur($idCreateur);
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
            --bg:            #f7f8fc;
            --bg-white:      #ffffff;
            --bg-soft:       #eef0f8;
            --border:        #e2e5ef;
            --border-dark:   #cdd1e2;
            --accent:        #7c3aed;
            --accent-soft:   rgba(124,58,237,.08);
            --accent-hover:  #9154f5;
            --success:       #059669;
            --success-soft:  rgba(5,150,105,.1);
            --warning:       #d97706;
            --warning-soft:  rgba(217,119,6,.1);
            --danger:        #dc2626;
            --danger-soft:   rgba(220,38,38,.1);
            --neutral:       #6b7280;
            --neutral-soft:  rgba(107,114,128,.12);
            --text-primary:  #111827;
            --text-secondary:#374151;
            --text-muted:    #9ca3af;
            --shadow-sm:     0 1px 3px rgba(0,0,0,.05);
            --shadow-md:     0 4px 16px rgba(0,0,0,.07);
            --radius:        14px;
            --radius-lg:     20px;
        }

        * { margin:0; padding:0; box-sizing:border-box; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg);
            color: var(--text-primary);
            min-height: 100vh;
        }

        /* ── NAV ── */
        nav {
            background: var(--bg-white);
            border-bottom: 1px solid var(--border);
            padding: 0 40px;
            display:flex; align-items:center; justify-content:space-between;
            height:64px; position:sticky; top:0; z-index:50;
            box-shadow: var(--shadow-sm);
        }
        .nav-logo { font-size:1.25rem; font-weight:800; color:var(--accent); }
        .nav-logo em { color:var(--text-primary); font-style:normal; }
        .nav-links { display:flex; gap:4px; }
        .nav-link {
            padding:8px 16px; border-radius:8px;
            color:var(--text-secondary); text-decoration:none;
            font-size:.875rem; font-weight:600; transition:all .15s;
        }
        .nav-link:hover, .nav-link.active {
            background:var(--accent-soft); color:var(--accent);
        }
        .avatar-pill {
            display:flex; align-items:center; gap:8px;
            background:var(--bg-soft); border:1px solid var(--border);
            border-radius:30px; padding:6px 14px 6px 6px;
            font-size:.825rem; font-weight:600; color:var(--text-secondary);
        }
        .avatar-circle {
            width:28px; height:28px; border-radius:50%;
            background:linear-gradient(135deg,var(--accent),#a78bfa);
            color:#fff; display:flex; align-items:center; justify-content:center;
            font-size:.75rem; font-weight:800;
        }

        /* ── HERO ── */
        .hero {
            background: linear-gradient(135deg, #7c3aed 0%, #a78bfa 50%, #c4b5fd 100%);
            padding: 48px 40px;
            color: white;
            position: relative;
            overflow: hidden;
        }
        .hero::before {
            content:'';
            position:absolute; top:-60px; right:-60px;
            width:280px; height:280px; border-radius:50%;
            background:rgba(255,255,255,.07);
        }
        .hero::after {
            content:'';
            position:absolute; bottom:-80px; left:20%;
            width:200px; height:200px; border-radius:50%;
            background:rgba(255,255,255,.05);
        }
        .hero-inner {
            max-width:1100px; margin:0 auto;
            position:relative; z-index:1;
        }
        .hero h1 {
            font-family:'Instrument Serif', serif;
            font-size:2.1rem; font-weight:400;
            line-height:1.2; margin-bottom:8px;
        }
        .hero p { font-size:.9rem; opacity:.85; }

        /* ── CONTENT ── */
        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 40px;
        }

        /* Tabs de filtre */
        .filter-tabs {
            display:flex; gap:8px;
            margin-bottom:28px;
            flex-wrap:wrap;
        }
        .tab {
            padding:8px 18px; border-radius:30px;
            font-size:.825rem; font-weight:700;
            cursor:pointer; border:none;
            font-family:inherit; transition:all .15s;
            background:var(--bg-white);
            border:1px solid var(--border-dark);
            color:var(--text-secondary);
        }
        .tab.active, .tab:hover { background:var(--accent); color:#fff; border-color:var(--accent); }

        /* ── CONTRAT CARD (vue créateur) ── */
        .contract-item {
            background:var(--bg-white);
            border:1px solid var(--border);
            border-radius:var(--radius-lg);
            padding:0;
            box-shadow:var(--shadow-sm);
            margin-bottom:16px;
            overflow:hidden;
            transition:box-shadow .2s;
        }
        .contract-item:hover { box-shadow:var(--shadow-md); }

        .item-header {
            display:flex; align-items:center; justify-content:space-between;
            padding:20px 24px;
            border-bottom:1px solid var(--border);
            gap:16px; flex-wrap:wrap;
        }
        .item-title-block {}
        .item-title { font-size:1rem; font-weight:700; margin-bottom:4px; }
        .item-campaign {
            font-size:.8rem; color:var(--text-muted);
            display:flex; align-items:center; gap:4px;
        }
        .item-campaign i { color:var(--accent); font-size:.75rem; }

        .badge {
            display:inline-flex; align-items:center; gap:4px;
            padding:5px 12px; border-radius:20px;
            font-size:.75rem; font-weight:700;
            text-transform:uppercase; letter-spacing:.5px;
        }
        .badge-pending  { background:var(--warning-soft); color:var(--warning); }
        .badge-signed   { background:var(--success-soft); color:var(--success); }
        .badge-resilie  { background:var(--danger-soft);  color:var(--danger);  }
        .badge-expire   { background:var(--neutral-soft); color:var(--neutral); }

        .item-body {
            padding: 20px 24px;
            display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr));
            gap:20px;
        }
        .detail-block {}
        .detail-label {
            font-size:.7rem; font-weight:700; color:var(--text-muted);
            text-transform:uppercase; letter-spacing:1px; margin-bottom:4px;
        }
        .detail-value { font-size:.9rem; font-weight:600; }
        .detail-value.big { font-size:1.15rem; color:var(--accent); }

        .item-footer {
            padding:14px 24px;
            background:var(--bg-soft);
            border-top:1px solid var(--border);
            display:flex; align-items:center; justify-content:space-between;
            gap:12px; flex-wrap:wrap;
        }
        .item-footer-meta { font-size:.78rem; color:var(--text-muted); }

        /* ── BTN ── */
        .btn {
            display:inline-flex; align-items:center; gap:6px;
            padding:9px 18px; border-radius:10px;
            font-family:inherit; font-size:.85rem; font-weight:700;
            cursor:pointer; text-decoration:none;
            border:none; transition:all .15s;
        }
        .btn-sign {
            background:var(--success);
            color:#fff;
            box-shadow:0 3px 10px rgba(5,150,105,.25);
        }
        .btn-sign:hover { background:#047857; transform:translateY(-1px); }
        .btn-disabled {
            background:var(--neutral-soft); color:var(--neutral);
            cursor:not-allowed;
        }
        .btn-sm { padding:7px 14px; font-size:.78rem; }

        /* ── EMPTY ── */
        .empty-state {
            text-align:center; padding:80px 20px;
        }
        .empty-icon {
            width:80px; height:80px; border-radius:50%;
            background:var(--accent-soft);
            display:flex; align-items:center; justify-content:center;
            margin:0 auto 20px;
            font-size:2rem; color:var(--accent);
        }
        .empty-state h3 { font-size:1.1rem; color:var(--text-secondary); margin-bottom:8px; }
        .empty-state p  { font-size:.875rem; color:var(--text-muted); }

        /* ── TOAST ── */
        .toast {
            position:fixed; bottom:24px; right:24px;
            background:var(--success); color:#fff;
            padding:12px 20px; border-radius:10px;
            font-size:.85rem; font-weight:700;
            box-shadow:0 4px 16px rgba(0,0,0,.15);
            display:none; align-items:center; gap:8px;
            z-index:999;
        }
        .toast.show { display:flex; }
    </style>
</head>
<body>

<!-- NAV -->
<nav>
    <div class="nav-logo">Cre8<em>Connect</em></div>
    <div class="nav-links">
        <a href="?module=dashboard"  class="nav-link">Dashboard</a>
        <a href="?module=offre"      class="nav-link">Offres</a>
        <a href="?module=campagne"   class="nav-link">Campagnes</a>
        <a href="?module=contrat&action=index" class="nav-link active">Contrats</a>
        <a href="?module=post"       class="nav-link">Posts</a>
        <a href="?module=evenement"  class="nav-link">Événements</a>
    </div>
    <div class="avatar-pill">
        <div class="avatar-circle">C</div>
        Mon Profil
    </div>
</nav>

<!-- HERO -->
<div class="hero">
    <div class="hero-inner">
        <h1>Mes Contrats</h1>
        <p>Consultez et signez vos accords de collaboration avec les marques</p>
    </div>
</div>

<div class="container">

    <?php if (empty($contrats)): ?>
        <div class="empty-state">
            <div class="empty-icon"><i class="fas fa-file-contract"></i></div>
            <h3>Aucun contrat pour l'instant</h3>
            <p>Les marques avec lesquelles vous collaborez vous enverront des contrats ici.</p>
        </div>
    <?php else: ?>

        <!-- Filtres -->
        <div class="filter-tabs">
            <button class="tab active" onclick="filterCards('all', this)">Tous (<?= count($contrats) ?>)</button>
            <?php
                $en_attente = array_filter($contrats, fn($c) => $c['statut'] === 'en_attente');
                $signes     = array_filter($contrats, fn($c) => $c['statut'] === 'signe');
            ?>
            <?php if (count($en_attente) > 0): ?>
            <button class="tab" onclick="filterCards('en_attente', this)">⏳ En attente (<?= count($en_attente) ?>)</button>
            <?php endif; ?>
            <?php if (count($signes) > 0): ?>
            <button class="tab" onclick="filterCards('signe', this)">✅ Signés (<?= count($signes) ?>)</button>
            <?php endif; ?>
        </div>

        <!-- Liste -->
        <div id="contractsList">
        <?php foreach ($contrats as $c):
            $statut = $c['statut'];
            $badgeClass = match($statut) {
                'signe'   => 'badge-signed',
                'resilie' => 'badge-resilie',
                'expire'  => 'badge-expire',
                default   => 'badge-pending',
            };
            $badgeLabel = match($statut) {
                'signe'     => '✅ Signé',
                'resilie'   => '❌ Résilié',
                'expire'    => '🕐 Expiré',
                default     => '⏳ En attente de signature',
            };
            $canSign = ($statut === 'en_attente');
        ?>
        <div class="contract-item" data-statut="<?= $statut ?>">
            <div class="item-header">
                <div class="item-title-block">
                    <div class="item-title"><?= htmlspecialchars($c['titre']) ?></div>
                    <div class="item-campaign">
                        <i class="fas fa-rocket"></i>
                        <?= htmlspecialchars($c['titreCampagne'] ?? 'Campagne non renseignée') ?>
                        · <strong style="color:var(--text-secondary)"><?= htmlspecialchars($c['nomMarque'] ?? 'Marque') ?></strong>
                    </div>
                </div>
                <span class="badge <?= $badgeClass ?>"><?= $badgeLabel ?></span>
            </div>

            <div class="item-body">
                <div class="detail-block">
                    <div class="detail-label">Rémunération</div>
                    <div class="detail-value big"><?= number_format($c['montant'],2,',',' ') ?> €</div>
                </div>
                <div class="detail-block">
                    <div class="detail-label">Début</div>
                    <div class="detail-value"><?= date('d/m/Y', strtotime($c['date_debut'])) ?></div>
                </div>
                <div class="detail-block">
                    <div class="detail-label">Fin</div>
                    <div class="detail-value"><?= date('d/m/Y', strtotime($c['date_fin'])) ?></div>
                </div>
                <div class="detail-block">
                    <div class="detail-label">Description</div>
                    <div class="detail-value" style="font-size:.82rem;color:var(--text-secondary);font-weight:500">
                        <?= nl2br(htmlspecialchars(substr($c['description'], 0, 120))) ?>
                        <?= strlen($c['description']) > 120 ? '...' : '' ?>
                    </div>
                </div>
            </div>

            <div class="item-footer">
                <div class="item-footer-meta">
                    <i class="fas fa-calendar-alt" style="margin-right:4px"></i>
                    Reçu le <?= date('d/m/Y', strtotime($c['date_creation'])) ?>
                </div>
                <?php if ($canSign): ?>
                    <a href="?module=contrat&action=signer&id=<?= $c['id'] ?>" class="btn btn-sign btn-sm"
                       onclick="return confirm('Confirmez-vous la signature de ce contrat ?')">
                        <i class="fas fa-signature"></i> Signer le contrat
                    </a>
                <?php else: ?>
                    <button class="btn btn-disabled btn-sm" disabled>
                        <i class="fas fa-lock"></i> 
                        <?= match($statut) {
                            'signe'   => 'Déjà signé',
                            'resilie' => 'Contrat résilié',
                            'expire'  => 'Contrat expiré',
                            default   => 'Non disponible'
                        } ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Toast -->
<div class="toast" id="toast"><i class="fas fa-check-circle"></i> Action effectuée avec succès</div>

<script>
function filterCards(statut, btn) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');

    document.querySelectorAll('.contract-item').forEach(card => {
        if (statut === 'all' || card.dataset.statut === statut) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}

// Toast auto-show si paramètre success dans URL
const params = new URLSearchParams(window.location.search);
if (params.get('success') === '1') {
    const toast = document.getElementById('toast');
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3500);
}
</script>
</body>
</html>