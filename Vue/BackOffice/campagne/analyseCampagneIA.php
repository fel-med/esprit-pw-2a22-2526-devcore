<?php
/* ═══ ADMIN : Analyse de campagne via IA ═══ */
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../../Controleur/campagneC.php';
$campagneC = new CampagneC();
$result = null; $error = '';
$campagnes = $campagneC->afficherToutesCampagnes();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id_campagne'] ?? 0);
    $camp = $campagneC->recupererCampagne($id);
    if ($camp) {
        $result = $campagneC->analyserCampagneIA(
            $camp['titreCampagne'], $camp['description'] ?? '',
            floatval($camp['budget']), $camp['statut']
        );
        if (!$result) $error = "Erreur IA. Réessayez.";
    } else { $error = "Campagne introuvable."; }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Analyse Campagne IA — Admin · Cre8Connect</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Syne:wght@400;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
:root{--bg:#0d0f14;--surface:#141720;--card:#1a1e2a;--hover:#202535;--border:#2a2f42;--accent:#6c63ff;--accent-soft:rgba(108,99,255,.15);--success:#22c55e;--success-soft:rgba(34,197,94,.15);--warn:#f59e0b;--warn-soft:rgba(245,158,11,.15);--danger:#ef4444;--danger-soft:rgba(239,68,68,.15);--text:#eef0f8;--sub:#9097b8;--muted:#5a6080;--radius:12px;}
*{margin:0;padding:0;box-sizing:border-box;}body{font-family:'Syne',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;}
.layout{display:flex;min-height:100vh;}
.sidebar{width:240px;background:var(--surface);border-right:1px solid var(--border);padding:24px 0;position:sticky;top:0;height:100vh;display:flex;flex-direction:column;}
.sidebar-logo{padding:0 24px 24px;font-size:1.3rem;font-weight:800;color:var(--accent);border-bottom:1px solid var(--border);}.sidebar-logo span{color:var(--text);}
.sidebar-nav{padding:16px 12px;flex:1;}
.nav-label{font-size:.65rem;font-weight:700;letter-spacing:2px;color:var(--muted);text-transform:uppercase;padding:0 12px;margin:16px 0 6px;}
.nav-item{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;color:var(--sub);text-decoration:none;font-size:.88rem;font-weight:600;transition:all .15s;}
.nav-item:hover,.nav-item.active{background:var(--accent-soft);color:var(--accent);}
.nav-item i{width:16px;text-align:center;}
.main{flex:1;display:flex;flex-direction:column;}
.topbar{background:var(--surface);border-bottom:1px solid var(--border);padding:16px 32px;display:flex;align-items:center;justify-content:space-between;}
.topbar-title{font-size:1.1rem;font-weight:700;}
.content{padding:32px;flex:1;}
.card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:24px;margin-bottom:24px;}
.ct{font-size:1rem;font-weight:700;margin-bottom:18px;color:var(--accent);}
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:14px;}
label{font-size:.8rem;font-weight:700;color:var(--sub);}
select{padding:10px 14px;border:1px solid var(--border);border-radius:10px;font-family:inherit;font-size:.85rem;background:var(--surface);color:var(--text);cursor:pointer;}
select:focus{outline:none;border-color:var(--accent);}
.btn{display:inline-flex;align-items:center;gap:8px;padding:10px 22px;border-radius:10px;font-family:inherit;font-size:.85rem;font-weight:700;cursor:pointer;border:none;background:var(--accent);color:#fff;transition:all .15s;}
.btn:hover{opacity:.9;}
.err{background:var(--danger-soft);color:var(--danger);border-radius:10px;padding:12px;font-size:.85rem;font-weight:600;margin-bottom:20px;}
.result{background:var(--surface);border:1.5px solid rgba(108,99,255,.2);border-radius:var(--radius);padding:24px;margin-top:20px;}
.rf{margin-bottom:14px;}.rl{font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:var(--muted);margin-bottom:4px;}
.rv{font-size:.88rem;line-height:1.6;color:var(--sub);}.rv.big{font-size:1.1rem;font-weight:700;color:var(--accent);}
.pill-list{display:flex;flex-wrap:wrap;gap:6px;}
.pill{padding:5px 12px;border-radius:20px;font-size:.78rem;font-weight:700;}
.pill-g{background:var(--success-soft);color:var(--success);}
.pill-r{background:var(--danger-soft);color:var(--danger);}
.pill-w{background:var(--warn-soft);color:var(--warn);}
.pill-a{background:var(--accent-soft);color:var(--accent);}
.loading{display:none;align-items:center;gap:10px;padding:16px;justify-content:center;color:var(--accent);font-weight:600;}.loading.show{display:flex;}
.spinner{width:18px;height:18px;border:3px solid var(--accent-soft);border-top-color:var(--accent);border-radius:50%;animation:spin .6s linear infinite;}
@keyframes spin{to{transform:rotate(360deg)}}
</style>
</head>
<body>
<div class="layout">
<aside class="sidebar">
<div class="sidebar-logo">Cre8<span>Connect</span></div>
<nav class="sidebar-nav">
<div class="nav-label">Modules</div>
<a href="../campagne/" class="nav-item"><i class="fas fa-rocket"></i> Campagnes</a>
<a href="analyseCampagneIA.php" class="nav-item active"><i class="fas fa-brain"></i> Analyse IA</a>
<a href="../contrat/" class="nav-item"><i class="fas fa-file-signature"></i> Contrats</a>
</nav>
</aside>
<div class="main">
<header class="topbar"><span class="topbar-title">🧠 Analyse Campagne IA</span><span style="color:var(--sub);font-size:.85rem">Admin</span></header>
<main class="content">
<?php if ($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<div class="card">
<div class="ct"><i class="fas fa-microscope"></i> Sélectionner une campagne à analyser</div>
<form method="POST" onsubmit="document.getElementById('ld').classList.add('show')">
<div class="fg"><label>Campagne *</label>
<select name="id_campagne" required>
<option value="">— Choisir —</option>
<?php foreach ($campagnes as $c): ?>
<option value="<?= $c['idCampagne'] ?>" <?= (intval($_POST['id_campagne'] ?? 0) === (int)$c['idCampagne']) ? 'selected' : '' ?>><?= htmlspecialchars($c['titreCampagne']) ?> (<?= $c['statut'] ?>)</option>
<?php endforeach; ?>
</select></div>
<div class="loading" id="ld"><div class="spinner"></div> Analyse en cours...</div>
<button type="submit" class="btn"><i class="fas fa-brain"></i> Analyser</button>
</form>
<?php if ($result): ?>
<div class="result">
<div class="ct">📊 Résultat de l'analyse</div>
<?php if (!empty($result['score_qualite'])): ?><div class="rf"><div class="rl">Score qualité</div><div class="rv big">⭐ <?= htmlspecialchars($result['score_qualite']) ?> / 10</div></div><?php endif; ?>
<?php if (!empty($result['points_forts'])): ?><div class="rf"><div class="rl">✅ Points forts</div><div class="pill-list"><?php foreach ($result['points_forts'] as $p): ?><span class="pill pill-g"><?= htmlspecialchars($p) ?></span><?php endforeach; ?></div></div><?php endif; ?>
<?php if (!empty($result['points_faibles'])): ?><div class="rf"><div class="rl">⚠️ Points faibles</div><div class="pill-list"><?php foreach ($result['points_faibles'] as $p): ?><span class="pill pill-w"><?= htmlspecialchars($p) ?></span><?php endforeach; ?></div></div><?php endif; ?>
<?php if (!empty($result['risques'])): ?><div class="rf"><div class="rl">🚨 Risques</div><div class="pill-list"><?php foreach ($result['risques'] as $r): ?><span class="pill pill-r"><?= htmlspecialchars($r) ?></span><?php endforeach; ?></div></div><?php endif; ?>
<?php if (!empty($result['recommandations'])): ?><div class="rf"><div class="rl">💡 Recommandations</div><div class="pill-list"><?php foreach ($result['recommandations'] as $r): ?><span class="pill pill-a"><?= htmlspecialchars($r) ?></span><?php endforeach; ?></div></div><?php endif; ?>
<?php if (!empty($result['budget_adequat'])): ?><div class="rf"><div class="rl">💰 Budget</div><div class="rv"><?= htmlspecialchars($result['budget_adequat']) ?></div></div><?php endif; ?>
</div>
<?php endif; ?>
</div>
</main>
</div>
</div>
</body>
</html>
