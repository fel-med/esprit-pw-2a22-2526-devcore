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

// ── CRUD (HANDLER PHP) ────────────────────────────────────────────────────────

// ===== ADDED FEATURE: DELETE CONTRACT =====
if ($action === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id > 0) {
        $controller->delete($id);
        header('Location: index.php?action=index&success=1'); exit;
    }
}

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

/* ===== ADDED FEATURE: LIGHT / DARK MODE CSS ===== */
body.dark-mode {
    --bg:#111827;--bg-white:#1f2937;--bg-soft:#374151;
    --border:#374151;--border-dark:#4b5563;
    --text-primary:#f9fafb;--text-secondary:#d1d5db;--text-muted:#9ca3af;
    --shadow-sm:0 1px 3px rgba(0,0,0,.3);--shadow-md:0 4px 16px rgba(0,0,0,.4);
}

*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--bg);color:var(--text-primary);min-height:100vh;transition: 0.3s ease;}

nav{background:var(--bg-white);border-bottom:1px solid var(--border);padding:0 40px;display:flex;align-items:center;justify-content:space-between;height:64px;position:sticky;top:0;z-index:50;box-shadow:var(--shadow-sm);}
.nav-logo{font-size:1.25rem;font-weight:800;color:var(--accent);letter-spacing:-.5px;}
.nav-logo em{color:var(--text-primary);font-style:normal;}
.nav-links{display:flex;gap:4px;}
.nav-link{padding:8px 16px;border-radius:8px;color:var(--text-secondary);text-decoration:none;font-size:.875rem;font-weight:600;transition:all .15s;}
.nav-link:hover,.nav-link.active{background:var(--accent-soft);color:var(--accent);}

/* CONTROLS UI */
.nav-controls{display:flex;align-items:center;gap:12px;}
.control-btn{background:var(--bg-soft);border:1px solid var(--border);padding:6px 12px;border-radius:8px;cursor:pointer;color:var(--text-primary);font-weight:700;font-size:0.85rem;}

.avatar-pill{display:flex;align-items:center;gap:8px;background:var(--bg-soft);border:1px solid var(--border);border-radius:30px;padding:6px 14px 6px 6px;font-size:.825rem;font-weight:600;color:var(--text-secondary);}
.avatar-circle{width:28px;height:28px;border-radius:50%;background:var(--accent);color:#fff;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:800;}

.page-header{background:linear-gradient(135deg,var(--accent) 0%,#8b5cf6 100%);padding:40px;color:white;}
.page-header-inner{max-width:1200px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;}
.page-header h1{font-family:'Instrument Serif',serif;font-size:1.9rem;font-weight:400;margin-bottom:4px;}
.page-header p{font-size:.9rem;opacity:.85;}

.container{max-width:1200px;margin:0 auto;padding:36px 40px 80px;}

/* IA PANEL */
.ia-panel{background:var(--bg-white);border:1px solid var(--border);border-radius:var(--radius-lg);padding:26px;margin-bottom:32px;box-shadow:var(--shadow-sm);}
.ia-panel-header{display:flex;align-items:center;gap:10px;margin-bottom:18px;}
.ia-panel-header h2{font-size:1rem;font-weight:700;color:var(--accent);}
.ia-form-grid{display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:14px;align-items:end;}
.ia-fg{display:flex;flex-direction:column;gap:5px;}
.ia-fg label{font-size:.8rem;font-weight:700;color:var(--text-secondary);}
.ia-fg input, .ia-fg select {padding:9px 13px;border:1px solid var(--border-dark);border-radius:10px;background:var(--bg);color:var(--text-primary);outline:none;}

.btn-ia{display:inline-flex;align-items:center;gap:7px;padding:10px 18px;border-radius:10px;font-weight:700;cursor:pointer;border:none;background:linear-gradient(135deg,var(--accent),#8b5cf6);color:#fff;transition:all .2s;}

/* STATS */
.stats-bar{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:16px;margin-bottom:32px;}
.stat-card{background:var(--bg-white);border:1px solid var(--border);border-radius:var(--radius);padding:18px;box-shadow:var(--shadow-sm);}
.stat-label{font-size:.72rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;}
.stat-value{font-size:1.8rem;font-weight:800;}
.stat-card.total .stat-value{color:var(--accent);}
.stat-card.pending .stat-value{color:var(--warning);}
.stat-card.signed .stat-value{color:var(--success);}

/* ===== ADDED FEATURE: FILTER BAR CSS ===== */
.filter-card {
    background: var(--bg-white); border: 1px solid var(--border); border-radius: var(--radius);
    padding: 20px; margin-bottom: 24px; display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;
}
.filter-group { display: flex; flex-direction: column; gap: 5px; }
.filter-group label { font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; }
.filter-input { padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border-dark); background: var(--bg); color: var(--text-primary); font-size: 0.85rem; }

/* PAGINATION CSS */
.pagination-container { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 40px; }
.page-btn { padding: 8px 14px; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-white); color: var(--text-primary); cursor: pointer; font-weight: 600; }
.page-btn.active { background: var(--accent); color: white; border-color: var(--accent); }

/* CARDS */
.cards-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:20px;}
.contract-card{background:var(--bg-white);border:1px solid var(--border);border-radius:var(--radius-lg);padding:22px;box-shadow:var(--shadow-sm);transition:0.2s;}
.card-top{display:flex;justify-content:space-between;margin-bottom:14px;}
.card-title{font-size:.98rem;font-weight:700;}
.card-campaign{font-size:.78rem;color:var(--text-muted);}
.card-info{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
.info-label{font-size:.7rem;color:var(--text-muted);text-transform:uppercase;}
.info-value{font-size:.875rem;font-weight:600;}
.badge{padding:4px 10px;border-radius:20px;font-size:.72rem;font-weight:700;text-transform:uppercase;}
.badge-pending{background:var(--warning-soft);color:var(--warning);}
.badge-signed{background:var(--success-soft);color:var(--success);}
.btn-sm { padding: 7px 12px; font-size: 0.8rem; }
.btn-danger { background: var(--danger-soft); color: var(--danger); border: 1px solid var(--danger); }
.btn-danger:hover { background: var(--danger); color: white; }

.alert{padding:13px 18px;border-radius:10px;font-size:.875rem;margin-bottom:22px;}
.alert-success{background:var(--success-soft);color:var(--success);}
.toast{position:fixed;bottom:24px;right:24px;background:var(--success);color:#fff;padding:12px 20px;border-radius:10px;display:none;z-index:999;}
.toast.show{display:flex;}
</style>
</head>
<body>

<nav>
    <div class="nav-logo">Cre8<em>Connect</em></div>
    <div class="nav-links">
        <a href="#" class="nav-link" data-i18n="dashboard">Dashboard</a>
        <a href="../campagne/index.php" class="nav-link" data-i18n="campaigns">Campagnes</a>
        <a href="../produit/index.php" class="nav-link" data-i18n="products">Produits</a>
        <a href="index.php?action=index" class="nav-link active" data-i18n="contracts">Contrats</a>
    </div>
    <div class="nav-controls">
        <select class="control-btn" id="langSwitcher" onchange="switchLanguage(this.value)">
            <option value="fr">FR</option>
            <option value="en">EN</option>
        </select>
        <button class="control-btn" id="themeToggle" onclick="toggleDarkMode()">🌙</button>
        <div class="avatar-pill"><div class="avatar-circle">M</div> <span data-i18n="my_brand">Ma Marque</span></div>
    </div>
</nav>

<div class="page-header">
    <div class="page-header-inner">
        <div>
            <h1 data-i18n="header_title">Mes Contrats</h1>
            <p data-i18n="header_subtitle">Gérez vos accords de collaboration et générez des contrats avec l'IA</p>
        </div>
        <?php if (!$isEdit && !$isCreate): ?>
        <a href="index.php?action=create" class="btn btn-sm" style="background:rgba(255,255,255,.2);border:1px solid #fff;color:#fff;" data-i18n="new_contract">
            + Nouveau contrat
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="container">

<?php if (isset($_GET['success'])): ?>
<div class="alert alert-success">✅ <span data-i18n="success_msg">Opération réalisée avec succès.</span></div>
<?php endif; ?>

<?php if (!$isEdit && !$isCreate): ?>

    <div class="ia-panel">
        <div class="ia-panel-header">
            <span style="font-size:22px;">📄</span>
            <h2 data-i18n="ia_title">Générer un contrat avec l'IA</h2>
        </div>
        <form method="POST">
            <input type="hidden" name="action_ia" value="generer">
            <div class="ia-form-grid">
                <div class="ia-fg">
                    <label data-i18n="label_campaign">Campagne *</label>
                    <input type="text" name="ia_campagne" placeholder="Ex : Lancement Été 2025" value="<?= htmlspecialchars($_POST['ia_campagne'] ?? '') ?>">
                </div>
                <div class="ia-fg">
                    <label data-i18n="label_reward">Rémunération (€) *</label>
                    <input type="number" name="ia_remuneration" min="1" step="0.01" placeholder="2500" value="<?= htmlspecialchars($_POST['ia_remuneration'] ?? '') ?>">
                </div>
                <div class="ia-fg">
                    <label data-i18n="label_deadline">Délai de livraison *</label>
                    <input type="text" name="ia_delai" placeholder="Ex : 30 jours" value="<?= htmlspecialchars($_POST['ia_delai'] ?? '') ?>">
                </div>
                <button type="submit" class="btn-ia" onclick="document.getElementById('iaLoading').classList.add('show')">
                    <span data-i18n="btn_generate">📄 Générer</span>
                </button>
            </div>
        </form>
        <div class="ia-loading" id="iaLoading"><div class="spinner"></div> <span data-i18n="ia_writing">Rédaction IA en cours…</span></div>
        
        <?php if ($iaResult): ?>
        <div class="ia-result">
            <div class="ia-result-title">📋 Contrat généré : <?= htmlspecialchars($iaResult['titre_contrat'] ?? '') ?></div>
            <p style="font-size: 0.85rem; color: var(--text-secondary);"><?= nl2br(htmlspecialchars($iaResult['conditions_paiement'] ?? '')) ?></p>
        </div>
        <?php endif; ?>
    </div>

    <div class="stats-bar">
        <div class="stat-card total"><div class="stat-label" data-i18n="total">Total</div><div class="stat-value"><?= $total ?></div></div>
        <div class="stat-card pending"><div class="stat-label" data-i18n="pending">En attente</div><div class="stat-value"><?= $pending ?></div></div>
        <div class="stat-card signed"><div class="stat-label" data-i18n="signed">Signés</div><div class="stat-value"><?= $signed ?></div></div>
        <div class="stat-card amount"><div class="stat-label" data-i18n="total_value">Valeur totale</div><div class="stat-value"><?= number_format($totalVal, 0, ',', ' ') ?> €</div></div>
    </div>

    <div class="filter-card">
        <div class="filter-group">
            <label data-i18n="f_search">Recherche</label>
            <input type="text" id="fSearch" class="filter-input" placeholder="Titre, créateur..." onkeyup="updateList()">
        </div>
        <div class="filter-group">
            <label data-i18n="f_status">Statut</label>
            <select id="fStatus" class="filter-input" onchange="updateList()">
                <option value="all" data-i18n="opt_all">Tous</option>
                <option value="en_attente" data-i18n="opt_pending">En attente</option>
                <option value="signe" data-i18n="opt_signed">Signé</option>
            </select>
        </div>
        <div class="filter-group">
            <label data-i18n="f_sort">Trier par</label>
            <select id="fSort" class="filter-input" onchange="updateList()">
                <option value="date" data-i18n="opt_date">Date</option>
                <option value="montant" data-i18n="opt_amount">Montant</option>
                <option value="titre" data-i18n="opt_title">Titre</option>
            </select>
        </div>
    </div>

    <div class="actions-bar">
        <div class="section-title" data-i18n="list_contracts">📄 Tous mes contrats</div>
    </div>

    <div class="cards-grid" id="contractsGrid">
    <?php foreach ($contrats as $c):
        $statut = $c['statut'];
        $badgeClass = ($statut === 'signe') ? 'badge-signed' : 'badge-pending';
        $badgeLabel = ($statut === 'signe') ? '✅ Signé' : '⏳ En attente';
    ?>
    <div class="contract-card" 
         data-status="<?= $statut ?>" 
         data-title="<?= strtolower(htmlspecialchars($c['titre'])) ?>"
         data-creator="<?= strtolower(htmlspecialchars($c['nomCreateur'] ?? '')) ?>"
         data-amount="<?= $c['montant'] ?>"
         data-date="<?= $c['date_creation'] ?>">
        <div class="card-top">
            <div class="card-title"><?= htmlspecialchars($c['titre']) ?></div>
            <span class="badge <?= $badgeClass ?>"><?= $badgeLabel ?></span>
        </div>
        <div class="card-campaign">🚀 <?= htmlspecialchars($c['titreCampagne'] ?? 'N/A') ?></div>
        <div class="card-info" style="margin-top:12px;">
            <div><div class="info-label" data-i18n="l_amount">Montant</div><div class="info-value"><?= number_format($c['montant'], 2) ?> €</div></div>
            <div><div class="info-label" data-i18n="l_creator">Créateur</div><div class="info-value"><?= htmlspecialchars($c['nomCreateur'] ?? '—') ?></div></div>
        </div>
        <div style="display:flex; gap:8px; margin-top:15px;">
            <a href="index.php?action=edit&id=<?= $c['id'] ?>" class="btn btn-outline btn-sm">✏️</a>
            <a href="index.php?action=delete&id=<?= $c['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Supprimer ce contrat ?')">🗑️</a>
        </div>
    </div>
    <?php endforeach; ?>
    </div>

    <div class="pagination-container" id="paginationControls"></div>

<?php else: ?>
    <div style="max-width: 800px; margin: 0 auto;">
        <div class="ia-panel">
            <div class="ia-panel-header">
                <span style="font-size:22px;"><?= $isEdit ? '✏️' : '➕' ?></span>
                <h2 data-i18n="<?= $isEdit ? 'form_edit' : 'form_create' ?>">
                    <?= $isEdit ? 'Modifier le contrat' : 'Créer un nouveau contrat' ?>
                </h2>
            </div>

            <form method="POST" action="index.php?action=<?= $isEdit ? 'edit&id='.$contrat['id'] : 'create' ?>">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="ia-fg" style="grid-column: span 2;">
                        <label data-i18n="l_title">Titre du contrat</label>
                        <input type="text" name="titre" required value="<?= $isEdit ? htmlspecialchars($contrat['titre']) : '' ?>" placeholder="ex: Collaboration 2026">
                    </div>
                    <div class="ia-fg">
                        <label>ID Campagne</label>
                        <input type="number" name="id_campagne" required value="<?= $isEdit ? $contrat['id_campagne'] : '' ?>">
                    </div>
                    <div class="ia-fg">
                        <label>ID Créateur</label>
                        <input type="number" name="id_createur" required value="<?= $isEdit ? $contrat['id_createur'] : '' ?>">
                    </div>
                    <div class="ia-fg">
                        <label data-i18n="l_amount">Montant (€)</label>
                        <input type="number" step="0.01" name="montant" required value="<?= $isEdit ? $contrat['montant'] : '' ?>">
                    </div>
                    <div class="ia-fg">
                        <label data-i18n="f_status">Statut</label>
                        <select name="statut">
                            <option value="en_attente" <?= ($isEdit && $contrat['statut'] == 'en_attente') ? 'selected' : '' ?>>En attente</option>
                            <option value="signe" <?= ($isEdit && $contrat['statut'] == 'signe') ? 'selected' : '' ?>>Signé</option>
                        </select>
                    </div>
                    <div class="ia-fg">
                        <label data-i18n="l_start">Date début</label>
                        <input type="date" name="date_debut" required value="<?= $isEdit ? $contrat['date_debut'] : '' ?>">
                    </div>
                    <div class="ia-fg">
                        <label data-i18n="l_end">Date fin</label>
                        <input type="date" name="date_fin" required value="<?= $isEdit ? $contrat['date_fin'] : '' ?>">
                    </div>
                    <div class="ia-fg" style="grid-column: span 2;">
                        <label>Description</label>
                        <textarea name="description" rows="4" style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border-dark); background:var(--bg); color:var(--text-primary);"><?= $isEdit ? htmlspecialchars($contrat['description']) : '' ?></textarea>
                    </div>
                </div>
                <div style="margin-top: 20px; display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-sm btn-primary" data-i18n="btn_save">Enregistrer</button>
                    <a href="index.php" class="btn btn-sm btn-outline" data-i18n="btn_cancel">Annuler</a>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

</div>

<script>
// ===== FEATURE 1: TRANSLATION SYSTEM =====
const langData = {
    fr: {
        dashboard: "Dashboard", campaigns: "Campagnes", products: "Produits", contracts: "Contrats",
        header_title: "Mes Contrats", header_subtitle: "Gérez vos accords et l'IA", new_contract: "+ Nouveau contrat",
        ia_title: "IA Génération", label_campaign: "Campagne *", label_reward: "Rémunération *", label_deadline: "Délai *",
        btn_generate: "Générer", ia_writing: "Rédaction IA...", total: "Total", pending: "Attente", signed: "Signés",
        total_value: "Valeur", f_search: "Recherche", f_status: "Statut", f_sort: "Trier", opt_all: "Tous",
        opt_pending: "Attente", opt_signed: "Signé", opt_date: "Date", opt_amount: "Montant", opt_title: "Titre",
        list_contracts: "📄 Liste des contrats", l_amount: "Montant", l_creator: "Créateur", my_brand: "Ma Marque",
        form_create: "Nouveau Contrat", form_edit: "Modifier Contrat", btn_save: "Enregistrer", btn_cancel: "Annuler",
        l_title: "Titre", l_start: "Début", l_end: "Fin", success_msg: "Opération réalisée avec succès."
    },
    en: {
        dashboard: "Dashboard", campaigns: "Campaigns", products: "Products", contracts: "Contracts",
        header_title: "My Contracts", header_subtitle: "Manage agreements and AI", new_contract: "+ New Contract",
        ia_title: "AI Generation", label_campaign: "Campaign *", label_reward: "Reward *", label_deadline: "Deadline *",
        btn_generate: "Generate", ia_writing: "AI Writing...", total: "Total", pending: "Pending", signed: "Signed",
        total_value: "Value", f_search: "Search", f_status: "Status", f_sort: "Sort", opt_all: "All",
        opt_pending: "Pending", opt_signed: "Signed", opt_date: "Date", opt_amount: "Amount", opt_title: "Title",
        list_contracts: "📄 Contract List", l_amount: "Amount", l_creator: "Creator", my_brand: "My Brand",
        form_create: "New Contract", form_edit: "Edit Contract", btn_save: "Save", btn_cancel: "Cancel",
        l_title: "Title", l_start: "Start", l_end: "End", success_msg: "Action successful."
    }
};

function switchLanguage(lang) {
    document.querySelectorAll('[data-i18n]').forEach(el => {
        const key = el.getAttribute('data-i18n');
        if(langData[lang][key]) el.innerText = langData[lang][key];
    });
    localStorage.setItem('cc_lang', lang);
}

// ===== FEATURE 2: DARK MODE =====
function toggleDarkMode() {
    const isDark = document.body.classList.toggle('dark-mode');
    document.getElementById('themeToggle').innerText = isDark ? '☀️' : '🌙';
    localStorage.setItem('cc_theme', isDark ? 'dark' : 'light');
}

// ===== FEATURE 3 & 4: PAGINATION, FILTERING, SORTING =====
const grid = document.getElementById('contractsGrid');
const cards = grid ? Array.from(grid.getElementsByClassName('contract-card')) : [];
const itemsPerPage = 6;
let currentPage = 1;

function updateList() {
    if(!grid) return;
    const search = document.getElementById('fSearch').value.toLowerCase();
    const status = document.getElementById('fStatus').value;
    const sort = document.getElementById('fSort').value;

    let filtered = cards.filter(c => {
        const matchesSearch = c.dataset.title.includes(search) || c.dataset.creator.includes(search);
        const matchesStatus = status === 'all' || c.dataset.status === status;
        return matchesSearch && matchesStatus;
    });

    filtered.sort((a, b) => {
        if(sort === 'montant') return b.dataset.amount - a.dataset.amount;
        if(sort === 'titre') return a.dataset.title.localeCompare(b.dataset.title);
        return new Date(b.dataset.date) - new Date(a.dataset.date);
    });

    cards.forEach(c => c.style.display = 'none');
    const start = (currentPage - 1) * itemsPerPage;
    const paginated = filtered.slice(start, start + itemsPerPage);
    paginated.forEach(c => c.style.display = 'block');

    renderPagination(Math.ceil(filtered.length / itemsPerPage));
}

function renderPagination(totalPages) {
    const container = document.getElementById('paginationControls');
    if(!container) return;
    container.innerHTML = '';
    for(let i=1; i<=totalPages; i++) {
        const b = document.createElement('button');
        b.innerText = i; b.className = `page-btn ${i===currentPage?'active':''}`;
        b.onclick = () => { currentPage=i; updateList(); };
        container.appendChild(b);
    }
}

// Init
window.onload = () => {
    if(localStorage.getItem('cc_theme') === 'dark') toggleDarkMode();
    const savedLang = localStorage.getItem('cc_lang') || 'fr';
    document.getElementById('langSwitcher').value = savedLang;
    switchLanguage(savedLang);
    updateList();
};
</script>
</body>
</html>