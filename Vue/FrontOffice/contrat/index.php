<?php
/**
 * Vue/FrontOffice/contrat/index.php
 * Rôle : MARQUE — gérer ses contrats + générer via IA
 */

require_once __DIR__ . '/../../../Controleur/contratC.php';
require_once __DIR__ . '/../../../Modele/contrat.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$controller = new ContratC();
$action     = $_GET['action'] ?? 'index';
$idMarque   = $_SESSION['user_id'] ?? 2;
$contrat    = null;
$iaResult   = null;
$iaError    = '';

// ── CRUD ──────────────────────────────────────────────────────────────────────
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $obj = new Contrat(
        null,
        (int)$_POST['id_campagne'],
        $idMarque,
        (int)$_POST['id_createur'],
        trim($_POST['titre']),
        trim($_POST['description']),
        (float)$_POST['montant'],
        $_POST['date_debut'],
        $_POST['date_fin']
    );
    $controller->create($obj);
    header('Location: index.php?action=index&success=1'); exit;
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
        header('Location: index.php?action=index&success=1'); exit;
    }
}

// ── IA : GÉNÉRATION CONTRAT ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action_ia'] ?? '') === 'generer') {
    $camp = trim($_POST['ia_campagne'] ?? '');
    $rem  = floatval($_POST['ia_remuneration'] ?? 0);
    $del  = trim($_POST['ia_delai'] ?? '');
    if ($camp && $rem > 0 && $del) {
        $iaResult = $controller->genererContratIA($camp, $rem, $del);
        if (!$iaResult) $iaError = "Erreur IA. Réessayez.";
    } else {
        $iaError = "Remplissez tous les champs IA.";
    }
}

$contrats = $controller->getByMarque($idMarque);
$isEdit   = ($action === 'edit') && isset($contrat);
$isCreate = ($action === 'create');

$total    = count($contrats);
$pending  = count(array_filter($contrats, fn($c) => $c['statut'] === 'en_attente'));
$signed   = count(array_filter($contrats, fn($c) => $c['statut'] === 'signe'));
$totalVal = array_sum(array_column($contrats, 'montant'));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mes Contrats — Cre8Connect</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
<style>
:root{
    --bg:#fafafa;--bg-white:#ffffff;--bg-soft:#f4f5f9;
    --border:#e8eaf0;--border-dark:#d1d5e0;
    --accent:#5b4cf5;--accent-soft:rgba(91,76,245,.08);--accent-hover:#7366f7;
    --success:#16a34a;--success-soft:rgba(22,163,74,.1);
    --warning:#d97706;--warning-soft:rgba(217,119,6,.1);
    --danger:#dc2626;--danger-soft:rgba(220,38,38,.1);
    --neutral:#6b7280;--neutral-soft:rgba(107,114,128,.1);
    --text-primary:#111827;--text-secondary:#4b5563;--text-muted:#9ca3af;
    --shadow-sm:0 1px 3px rgba(0,0,0,.06);--shadow-md:0 4px 16px rgba(0,0,0,.08);
    --radius:12px;--radius-lg:20px;
}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--bg);color:var(--text-primary);min-height:100vh;}

nav{background:var(--bg-white);border-bottom:1px solid var(--border);padding:0 40px;display:flex;align-items:center;justify-content:space-between;height:64px;position:sticky;top:0;z-index:50;box-shadow:var(--shadow-sm);}
.nav-logo{font-size:1.25rem;font-weight:800;color:var(--accent);letter-spacing:-.5px;}
.nav-logo em{color:var(--text-primary);font-style:normal;}
.nav-links{display:flex;gap:4px;}
.nav-link{padding:8px 16px;border-radius:8px;color:var(--text-secondary);text-decoration:none;font-size:.875rem;font-weight:600;transition:all .15s;}
.nav-link:hover,.nav-link.active{background:var(--accent-soft);color:var(--accent);}
.avatar-pill{display:flex;align-items:center;gap:8px;background:var(--bg-soft);border:1px solid var(--border);border-radius:30px;padding:6px 14px 6px 6px;font-size:.825rem;font-weight:600;color:var(--text-secondary);}
.avatar-circle{width:28px;height:28px;border-radius:50%;background:var(--accent);color:#fff;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:800;}

.page-header{background:linear-gradient(135deg,var(--accent) 0%,#8b5cf6 100%);padding:40px;color:white;}
.page-header-inner{max-width:1200px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;}
.page-header h1{font-family:'Instrument Serif',serif;font-size:1.9rem;font-weight:400;line-height:1.2;margin-bottom:4px;}
.page-header p{font-size:.9rem;opacity:.85;}

.container{max-width:1200px;margin:0 auto;padding:36px 40px 80px;}

/* IA PANEL */
.ia-panel{background:var(--bg-white);border:1px solid var(--border);border-radius:var(--radius-lg);padding:26px;margin-bottom:32px;box-shadow:var(--shadow-sm);}
.ia-panel-header{display:flex;align-items:center;gap:10px;margin-bottom:18px;}
.ia-panel-header h2{font-size:1rem;font-weight:700;color:var(--accent);}
.ia-form-grid{display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:14px;align-items:end;}
.ia-fg{display:flex;flex-direction:column;gap:5px;}
.ia-fg label{font-size:.8rem;font-weight:700;color:var(--text-secondary);}
.ia-fg input{padding:9px 13px;border:1px solid var(--border-dark);border-radius:10px;font-family:inherit;font-size:.875rem;background:var(--bg);outline:none;transition:border-color .15s,box-shadow .15s;}
.ia-fg input:focus{border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-soft);}
.btn-ia{display:inline-flex;align-items:center;gap:7px;padding:10px 18px;border-radius:10px;font-family:inherit;font-size:.85rem;font-weight:700;cursor:pointer;border:none;background:linear-gradient(135deg,var(--accent),#8b5cf6);color:#fff;transition:all .2s;white-space:nowrap;}
.btn-ia:hover{transform:translateY(-1px);opacity:.9;}
.ia-result{background:var(--bg-soft);border:1.5px solid rgba(91,76,245,.15);border-radius:var(--radius);padding:20px;margin-top:16px;}
.ia-result-title{font-size:.95rem;font-weight:700;color:var(--accent);margin-bottom:14px;}
.ia-field{margin-bottom:12px;}
.ia-label{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--text-muted);margin-bottom:3px;}
.ia-value{font-size:.875rem;line-height:1.65;color:var(--text-secondary);}
.ia-value.big{font-size:1.05rem;font-weight:700;color:var(--accent);}
.ogrid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:12px;}
.osec h4{font-size:.85rem;font-weight:700;margin-bottom:8px;color:var(--text-secondary);}
.olist{list-style:none;display:flex;flex-direction:column;gap:6px;}
.olist li{font-size:.85rem;padding:8px 12px;background:var(--bg-white);border-radius:8px;border-left:3px solid var(--accent);line-height:1.5;}
.tl-item{display:flex;align-items:flex-start;gap:12px;padding:9px 13px;background:var(--bg-white);border:1px solid var(--border);border-radius:10px;margin-bottom:7px;}
.tl-badge{background:var(--accent);color:#fff;border-radius:8px;padding:3px 9px;font-size:.72rem;font-weight:700;white-space:nowrap;}
.tl-text{font-size:.85rem;color:var(--text-secondary);}
.ia-error{background:var(--danger-soft);color:var(--danger);border-radius:10px;padding:10px 14px;font-size:.85rem;font-weight:600;margin-top:12px;}
.spinner{width:16px;height:16px;border:2.5px solid var(--accent-soft);border-top-color:var(--accent);border-radius:50%;animation:spin .6s linear infinite;}
@keyframes spin{to{transform:rotate(360deg)}}
.ia-loading{display:none;align-items:center;gap:10px;padding:10px 0;color:var(--accent);font-weight:600;font-size:.85rem;}
.ia-loading.show{display:flex;}

/* STATS */
.stats-bar{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:16px;margin-bottom:32px;}
.stat-card{background:var(--bg-white);border:1px solid var(--border);border-radius:var(--radius);padding:18px;display:flex;flex-direction:column;gap:4px;box-shadow:var(--shadow-sm);}
.stat-label{font-size:.72rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px;}
.stat-value{font-size:1.8rem;font-weight:800;}
.stat-card.total .stat-value{color:var(--accent);}
.stat-card.pending .stat-value{color:var(--warning);}
.stat-card.signed .stat-value{color:var(--success);}
.stat-card.amount .stat-value{color:var(--accent);font-size:1.2rem;}

/* ACTIONS BAR */
.actions-bar{display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;gap:16px;flex-wrap:wrap;}
.section-title{font-size:1.05rem;font-weight:700;display:flex;align-items:center;gap:8px;}

/* BUTTONS */
.btn{display:inline-flex;align-items:center;gap:7px;padding:9px 18px;border-radius:10px;font-family:inherit;font-size:.875rem;font-weight:700;cursor:pointer;text-decoration:none;border:none;transition:all .15s;}
.btn-primary{background:var(--accent);color:#fff;box-shadow:0 4px 12px rgba(91,76,245,.3);}
.btn-primary:hover{background:var(--accent-hover);transform:translateY(-1px);}
.btn-outline{background:transparent;color:var(--text-secondary);border:1px solid var(--border-dark);}
.btn-outline:hover{background:var(--bg-soft);color:var(--text-primary);}
.btn-sm{padding:7px 14px;font-size:.8rem;}
.btn-danger{background:var(--danger-soft);color:var(--danger);border:1px solid var(--danger);}
.btn-danger:hover{background:var(--danger);color:#fff;}

/* CARDS GRID */
.cards-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:20px;}
.contract-card{background:var(--bg-white);border:1px solid var(--border);border-radius:var(--radius-lg);padding:22px;box-shadow:var(--shadow-sm);transition:box-shadow .2s,transform .2s;position:relative;overflow:hidden;}
.contract-card:hover{box-shadow:var(--shadow-md);transform:translateY(-2px);}
.contract-card::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;}
.contract-card.en_attente::before{background:var(--warning);}
.contract-card.signe::before{background:var(--success);}
.contract-card.resilie::before{background:var(--danger);}
.contract-card.expire::before{background:var(--neutral);}
.card-top{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:14px;}
.card-title{font-size:.98rem;font-weight:700;margin-bottom:3px;}
.card-campaign{font-size:.78rem;color:var(--text-muted);}
.badge{display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:20px;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;white-space:nowrap;}
.badge-pending{background:var(--warning-soft);color:var(--warning);}
.badge-signed{background:var(--success-soft);color:var(--success);}
.badge-resilie{background:var(--danger-soft);color:var(--danger);}
.badge-expire{background:var(--neutral-soft);color:var(--neutral);}
.card-info{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px;}
.info-label{font-size:.7rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:2px;}
.info-value{font-size:.875rem;font-weight:600;}
.info-value.montant{color:var(--accent);font-size:1.05rem;}
.card-creator{display:flex;align-items:center;gap:8px;padding:9px 13px;background:var(--bg-soft);border-radius:10px;margin-bottom:14px;font-size:.825rem;}
.creator-avatar{width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,var(--accent),#8b5cf6);color:#fff;font-size:.72rem;font-weight:800;display:flex;align-items:center;justify-content:center;}
.card-actions{display:flex;gap:8px;flex-wrap:wrap;}

/* FORM */
.form-card{background:var(--bg-white);border:1px solid var(--border);border-radius:var(--radius-lg);padding:30px;box-shadow:var(--shadow-md);max-width:700px;}
.form-title{font-size:1.15rem;font-weight:700;margin-bottom:22px;display:flex;align-items:center;gap:8px;}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px;}
.form-group{display:flex;flex-direction:column;gap:6px;}
.form-group.full{grid-column:1/-1;}
label{font-size:.825rem;font-weight:700;color:var(--text-secondary);}
input,select,textarea{padding:10px 13px;border:1px solid var(--border-dark);border-radius:10px;font-family:inherit;font-size:.875rem;color:var(--text-primary);background:var(--bg);transition:border-color .15s,box-shadow .15s;width:100%;}
input:focus,select:focus,textarea:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-soft);}
textarea{resize:vertical;min-height:90px;}
.form-actions{display:flex;gap:12px;margin-top:22px;}

/* EMPTY */
.empty-state{text-align:center;padding:70px 20px;color:var(--text-muted);}
.empty-icon{width:70px;height:70px;border-radius:50%;background:var(--accent-soft);display:flex;align-items:center;justify-content:center;margin:0 auto 18px;font-size:1.8rem;color:var(--accent);}

/* ALERT */
.alert{display:flex;align-items:center;gap:10px;padding:13px 18px;border-radius:10px;font-size:.875rem;font-weight:600;margin-bottom:22px;}
.alert-success{background:var(--success-soft);color:var(--success);}

/* TOAST */
.toast{position:fixed;bottom:24px;right:24px;background:var(--success);color:#fff;padding:12px 20px;border-radius:10px;font-size:.85rem;font-weight:700;box-shadow:0 4px 16px rgba(0,0,0,.15);display:none;align-items:center;gap:8px;z-index:999;}
.toast.show{display:flex;}

@media(max-width:700px){.ia-form-grid{grid-template-columns:1fr;}.form-grid{grid-template-columns:1fr;}.ogrid{grid-template-columns:1fr;}}
</style>
</head>
<body>

<nav>
    <div class="nav-logo">Cre8<em>Connect</em></div>
    <div class="nav-links">
        <a href="#" class="nav-link">Dashboard</a>
        <a href="../campagne/index.php" class="nav-link">Campagnes</a>
        <a href="../produit/index.php" class="nav-link">Produits</a>
        <a href="index.php?action=index" class="nav-link active">Contrats</a>
    </div>
    <div class="avatar-pill"><div class="avatar-circle">M</div> Ma Marque</div>
</nav>

<div class="page-header">
    <div class="page-header-inner">
        <div>
            <h1>Mes Contrats</h1>
            <p>Gérez vos accords de collaboration et générez des contrats avec l'IA</p>
        </div>
        <?php if (!$isEdit && !$isCreate): ?>
        <a href="index.php?action=create" class="btn" style="background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.3);color:#fff;">
            + Nouveau contrat
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="container">

<?php if (isset($_GET['success'])): ?>
<div class="alert alert-success">✅ Opération réalisée avec succès.</div>
<?php endif; ?>

<?php if (!$isEdit && !$isCreate): ?>

    <!-- IA GÉNÉRATION -->
    <div class="ia-panel">
        <div class="ia-panel-header">
            <span style="font-size:22px;">📄</span>
            <h2>Générer un contrat avec l'IA</h2>
        </div>
        <form method="POST">
            <input type="hidden" name="action_ia" value="generer">
            <div class="ia-form-grid">
                <div class="ia-fg">
                    <label>Campagne *</label>
                    <input type="text" name="ia_campagne" placeholder="Ex : Lancement Été 2025" value="<?= htmlspecialchars($_POST['ia_campagne'] ?? '') ?>">
                </div>
                <div class="ia-fg">
                    <label>Rémunération (€) *</label>
                    <input type="number" name="ia_remuneration" min="1" step="0.01" placeholder="2500" value="<?= htmlspecialchars($_POST['ia_remuneration'] ?? '') ?>">
                </div>
                <div class="ia-fg">
                    <label>Délai de livraison *</label>
                    <input type="text" name="ia_delai" placeholder="Ex : 30 jours" value="<?= htmlspecialchars($_POST['ia_delai'] ?? '') ?>">
                </div>
                <button type="submit" class="btn-ia" onclick="document.getElementById('iaLoading').classList.add('show')">
                    📄 Générer
                </button>
            </div>
        </form>
        <div class="ia-loading" id="iaLoading"><div class="spinner"></div> Rédaction IA en cours…</div>
        <?php if ($iaError): ?><div class="ia-error">⚠️ <?= htmlspecialchars($iaError) ?></div><?php endif; ?>
        <?php if ($iaResult): ?>
        <div class="ia-result">
            <div class="ia-result-title">📋 Contrat généré</div>
            <?php if (!empty($iaResult['titre_contrat'])): ?><div class="ia-field"><div class="ia-label">Titre</div><div class="ia-value big"><?= htmlspecialchars($iaResult['titre_contrat']) ?></div></div><?php endif; ?>
            <div class="ogrid">
                <?php if (!empty($iaResult['obligations_marque'])): ?>
                <div class="osec"><h4>🏢 Obligations Marque</h4><ul class="olist"><?php foreach ($iaResult['obligations_marque'] as $o): ?><li><?= htmlspecialchars($o) ?></li><?php endforeach; ?></ul></div>
                <?php endif; ?>
                <?php if (!empty($iaResult['obligations_createur'])): ?>
                <div class="osec"><h4>🎨 Obligations Créateur</h4><ul class="olist"><?php foreach ($iaResult['obligations_createur'] as $o): ?><li><?= htmlspecialchars($o) ?></li><?php endforeach; ?></ul></div>
                <?php endif; ?>
            </div>
            <?php if (!empty($iaResult['timeline'])): ?>
            <div class="ia-field">
                <div class="ia-label">Timeline</div>
                <?php foreach ($iaResult['timeline'] as $t): ?>
                <div class="tl-item"><span class="tl-badge"><?= htmlspecialchars($t['delai'] ?? '') ?></span><span class="tl-text"><?= htmlspecialchars($t['etape'] ?? '') ?></span></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($iaResult['conditions_paiement'])): ?><div class="ia-field"><div class="ia-label">💰 Paiement</div><div class="ia-value"><?= nl2br(htmlspecialchars($iaResult['conditions_paiement'])) ?></div></div><?php endif; ?>
            <?php if (!empty($iaResult['droits_utilisation'])): ?><div class="ia-field"><div class="ia-label">📋 Droits</div><div class="ia-value"><?= nl2br(htmlspecialchars($iaResult['droits_utilisation'])) ?></div></div><?php endif; ?>
            <?php if (!empty($iaResult['clause_resiliation'])): ?><div class="ia-field"><div class="ia-label">⚠️ Résiliation</div><div class="ia-value"><?= nl2br(htmlspecialchars($iaResult['clause_resiliation'])) ?></div></div><?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- STATS -->
    <div class="stats-bar">
        <div class="stat-card total"><div class="stat-label">Total</div><div class="stat-value"><?= $total ?></div></div>
        <div class="stat-card pending"><div class="stat-label">En attente</div><div class="stat-value"><?= $pending ?></div></div>
        <div class="stat-card signed"><div class="stat-label">Signés</div><div class="stat-value"><?= $signed ?></div></div>
        <div class="stat-card amount"><div class="stat-label">Valeur totale</div><div class="stat-value"><?= number_format($totalVal, 0, ',', ' ') ?> €</div></div>
    </div>

    <!-- LISTE -->
    <div class="actions-bar">
        <div class="section-title">📄 Tous mes contrats</div>
        <a href="index.php?action=create" class="btn btn-primary btn-sm">+ Créer</a>
    </div>

    <?php if (empty($contrats)): ?>
    <div class="empty-state">
        <div class="empty-icon">📄</div>
        <h3 style="font-size:1.1rem;color:var(--text-secondary);margin-bottom:6px;">Aucun contrat pour l'instant</h3>
        <p style="font-size:.875rem;margin-bottom:20px;">Créez votre premier contrat avec un créateur de contenu.</p>
        <a href="index.php?action=create" class="btn btn-primary">+ Créer un contrat</a>
    </div>
    <?php else: ?>
    <div class="cards-grid">
    <?php foreach ($contrats as $c):
        $statut = $c['statut'];
        $badgeClass = match($statut) { 'signe'=>'badge-signed','resilie'=>'badge-resilie','expire'=>'badge-expire',default=>'badge-pending' };
        $badgeLabel = match($statut) { 'signe'=>'✅ Signé','resilie'=>'❌ Résilié','expire'=>'🕐 Expiré',default=>'⏳ En attente' };
        $initiales  = strtoupper(substr($c['nomCreateur'] ?? 'C', 0, 1));
    ?>
    <div class="contract-card <?= $statut ?>">
        <div class="card-top">
            <div>
                <div class="card-title"><?= htmlspecialchars($c['titre']) ?></div>
                <div class="card-campaign">🚀 <?= htmlspecialchars($c['titreCampagne'] ?? 'N/A') ?></div>
            </div>
            <span class="badge <?= $badgeClass ?>"><?= $badgeLabel ?></span>
        </div>
        <div class="card-creator">
            <div class="creator-avatar"><?= $initiales ?></div>
            <div>
                <div style="font-weight:700;font-size:.85rem;"><?= htmlspecialchars($c['nomCreateur'] ?? '—') ?></div>
                <div style="font-size:.75rem;color:var(--text-muted);">Créateur de contenu</div>
            </div>
        </div>
        <div class="card-info">
            <div><div class="info-label">Montant</div><div class="info-value montant"><?= number_format($c['montant'], 2, ',', ' ') ?> €</div></div>
            <div><div class="info-label">Début</div><div class="info-value"><?= date('d/m/Y', strtotime($c['date_debut'])) ?></div></div>
            <div><div class="info-label">Fin</div><div class="info-value"><?= date('d/m/Y', strtotime($c['date_fin'])) ?></div></div>
            <div><div class="info-label">Créé le</div><div class="info-value" style="font-size:.82rem;color:var(--text-muted);"><?= date('d/m/Y', strtotime($c['date_creation'])) ?></div></div>
        </div>
        <?php if ($statut === 'en_attente'): ?>
        <div class="card-actions">
            <a href="index.php?action=edit&id=<?= $c['id'] ?>" class="btn btn-outline btn-sm">✏️ Modifier</a>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>

<?php else: ?>

    <!-- FORMULAIRE -->
    <a href="index.php?action=index" class="btn btn-outline btn-sm" style="margin-bottom:24px;">← Retour</a>
    <div class="form-card">
        <div class="form-title">
            <?= $isEdit ? '✏️ Modifier le contrat' : '+ Créer un nouveau contrat' ?>
        </div>
        <form method="POST" action="index.php?action=<?= $isEdit ? 'edit&id='.$contrat['id'] : 'create' ?>">
            <div class="form-grid">
                <div class="form-group full">
                    <label>Titre du contrat *</label>
                    <input type="text" name="titre" required value="<?= htmlspecialchars($contrat['titre'] ?? '') ?>" placeholder="Ex : Partenariat influencer Été 2025">
                </div>
                <div class="form-group full">
                    <label>Description *</label>
                    <textarea name="description" required placeholder="Détails des obligations et livrables…"><?= htmlspecialchars($contrat['description'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label>ID Campagne *</label>
                    <input type="number" name="id_campagne" required value="<?= $contrat['id_campagne'] ?? '' ?>">
                </div>
                <div class="form-group">
                    <label>ID Créateur *</label>
                    <input type="number" name="id_createur" required value="<?= $contrat['id_createur'] ?? '' ?>">
                </div>
                <div class="form-group">
                    <label>Montant (€) *</label>
                    <input type="number" name="montant" step="0.01" min="0" required value="<?= $contrat['montant'] ?? '' ?>" placeholder="0.00">
                </div>
                <?php if ($isEdit): ?>
                <div class="form-group">
                    <label>Statut</label>
                    <select name="statut">
                        <option value="en_attente" <?= ($contrat['statut'] ?? '') === 'en_attente' ? 'selected' : '' ?>>⏳ En attente</option>
                        <option value="signe"      <?= ($contrat['statut'] ?? '') === 'signe'      ? 'selected' : '' ?>>✅ Signé</option>
                        <option value="resilie"    <?= ($contrat['statut'] ?? '') === 'resilie'    ? 'selected' : '' ?>>❌ Résilié</option>
                        <option value="expire"     <?= ($contrat['statut'] ?? '') === 'expire'     ? 'selected' : '' ?>>🕐 Expiré</option>
                    </select>
                </div>
                <?php endif; ?>
                <div class="form-group">
                    <label>Date de début *</label>
                    <input type="date" name="date_debut" required value="<?= $contrat['date_debut'] ?? '' ?>">
                </div>
                <div class="form-group">
                    <label>Date de fin *</label>
                    <input type="date" name="date_fin" required value="<?= $contrat['date_fin'] ?? '' ?>">
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <?= $isEdit ? '💾 Enregistrer' : '📄 Créer le contrat' ?>
                </button>
                <a href="index.php?action=index" class="btn btn-outline">Annuler</a>
            </div>
        </form>
    </div>

<?php endif; ?>

</div><!-- /container -->

<div class="toast" id="toast">✅ Action réalisée avec succès</div>
<script>
<?php if (isset($_GET['success'])): ?>
const toast = document.getElementById('toast');
toast.classList.add('show');
setTimeout(() => toast.classList.remove('show'), 3500);
<?php endif; ?>
</script>
</body>
</html>