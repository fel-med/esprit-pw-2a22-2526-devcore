<?php
/* ═══ MARQUE : Générer un contrat via IA ═══ */
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../../Controleur/contratC.php';
$contratC = new ContratC();
$result = null; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $camp = trim($_POST['campagne'] ?? '');
    $rem  = floatval($_POST['remuneration'] ?? 0);
    $del  = trim($_POST['delai'] ?? '');
    if ($camp && $rem > 0 && $del) {
        $result = $contratC->genererContratIA($camp, $rem, $del);
        if (!$result) $error = "Erreur IA. Réessayez.";
    } else { $error = "Remplissez tous les champs."; }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Générer Contrat IA — Cre8Connect</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
:root{--bg:#f6f6fc;--white:#fff;--border:#e8eaf0;--accent:#5b4cf5;--accent-soft:rgba(91,76,245,.08);--text:#111827;--sub:#4b5563;--muted:#9ca3af;--radius:14px;}
*{margin:0;padding:0;box-sizing:border-box;}body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;}
nav{background:var(--white);border-bottom:1px solid var(--border);padding:0 40px;display:flex;align-items:center;justify-content:space-between;height:64px;position:sticky;top:0;z-index:50;}
.logo{font-size:1.25rem;font-weight:800;color:var(--accent);}.logo em{color:var(--text);font-style:normal;}
.badge{background:var(--accent);color:#fff;border-radius:20px;padding:5px 14px;font-size:.75rem;font-weight:700;}
.container{max-width:800px;margin:0 auto;padding:40px 24px 80px;}
h1{font-size:1.5rem;font-weight:800;margin-bottom:6px;}.sub-p{color:var(--sub);font-size:.9rem;margin-bottom:28px;}
.card{background:var(--white);border:1px solid var(--border);border-radius:var(--radius);padding:28px;box-shadow:0 4px 16px rgba(0,0,0,.08);}
.ct{font-size:1rem;font-weight:700;margin-bottom:18px;color:var(--accent);}
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:14px;}.fgrid{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
label{font-size:.82rem;font-weight:700;color:var(--sub);}
input{padding:10px 14px;border:1.5px solid var(--border);border-radius:10px;font-family:inherit;font-size:.875rem;background:var(--bg);}
input:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-soft);}
.btn{display:inline-flex;align-items:center;gap:8px;padding:12px 24px;border-radius:12px;font-family:inherit;font-size:.9rem;font-weight:700;cursor:pointer;border:none;background:linear-gradient(135deg,#5b4cf5,#8b5cf6);color:#fff;transition:all .2s;}
.btn:hover{transform:translateY(-2px);}
.err{background:rgba(220,38,38,.08);color:#dc2626;border-radius:10px;padding:12px;font-size:.85rem;font-weight:600;margin-bottom:20px;}
.result{background:linear-gradient(135deg,rgba(91,76,245,.03),rgba(139,92,246,.06));border:1.5px solid rgba(91,76,245,.15);border-radius:var(--radius);padding:24px;margin-top:20px;}
.rf{margin-bottom:14px;}.rl{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--muted);margin-bottom:4px;}
.rv{font-size:.9rem;line-height:1.6;}.rv.big{font-size:1.05rem;font-weight:700;color:var(--accent);}
.ogrid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:14px;}
.osec h4{font-size:.85rem;font-weight:700;margin-bottom:8px;color:var(--sub);}
.olist{list-style:none;display:flex;flex-direction:column;gap:6px;}
.olist li{font-size:.85rem;padding:8px 12px;background:var(--bg);border-radius:8px;border-left:3px solid var(--accent);line-height:1.5;}
.tl-item{display:flex;align-items:flex-start;gap:12px;padding:10px 14px;background:var(--white);border:1px solid var(--border);border-radius:10px;margin-bottom:8px;}
.tl-badge{background:var(--accent);color:#fff;border-radius:8px;padding:4px 10px;font-size:.75rem;font-weight:700;white-space:nowrap;}
.tl-text{font-size:.85rem;color:var(--sub);}
.back{display:inline-flex;align-items:center;gap:6px;color:var(--sub);text-decoration:none;font-size:.85rem;font-weight:600;margin-bottom:20px;}
.loading{display:none;align-items:center;gap:10px;padding:16px;justify-content:center;color:var(--accent);font-weight:600;}.loading.show{display:flex;}
.spinner{width:18px;height:18px;border:3px solid var(--accent-soft);border-top-color:var(--accent);border-radius:50%;animation:spin .6s linear infinite;}
@keyframes spin{to{transform:rotate(360deg)}}
@media(max-width:600px){.fgrid,.ogrid{grid-template-columns:1fr;}}
</style>
</head>
<body>
<nav><span class="logo">Cre8<em>Connect</em></span><span class="badge">Marque</span></nav>
<div class="container">
<a href="../contrat/" class="back"><i class="fas fa-arrow-left"></i> Retour aux contrats</a>
<h1>📄 Générer un Contrat avec l'IA</h1>
<p class="sub-p">Créez automatiquement les clauses d'un contrat de collaboration.</p>
<?php if ($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<div class="card">
<div class="ct"><i class="fas fa-file-contract"></i> Paramètres du contrat</div>
<form method="POST" onsubmit="document.getElementById('ld').classList.add('show')">
<div class="fgrid">
<div class="fg"><label>Campagne *</label><input type="text" name="campagne" required placeholder="Ex : Lancement Été 2025" value="<?= htmlspecialchars($_POST['campagne'] ?? '') ?>"></div>
<div class="fg"><label>Rémunération (€) *</label><input type="number" name="remuneration" required min="1" step="0.01" placeholder="2500" value="<?= htmlspecialchars($_POST['remuneration'] ?? '') ?>"></div>
</div>
<div class="fg"><label>Délai de livraison *</label><input type="text" name="delai" required placeholder="Ex : 30 jours" value="<?= htmlspecialchars($_POST['delai'] ?? '') ?>"></div>
<div class="loading" id="ld"><div class="spinner"></div> Rédaction en cours...</div>
<button type="submit" class="btn"><i class="fas fa-file-signature"></i> Générer contrat</button>
</form>
<?php if ($result): ?>
<div class="result">
<div class="ct">📋 Contrat Généré</div>
<?php if (!empty($result['titre_contrat'])): ?><div class="rf"><div class="rl">Titre</div><div class="rv big"><?= htmlspecialchars($result['titre_contrat']) ?></div></div><?php endif; ?>
<div class="ogrid">
<?php if (!empty($result['obligations_marque'])): ?><div class="osec"><h4>🏢 Obligations Marque</h4><ul class="olist"><?php foreach ($result['obligations_marque'] as $o): ?><li><?= htmlspecialchars($o) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<?php if (!empty($result['obligations_createur'])): ?><div class="osec"><h4>🎨 Obligations Créateur</h4><ul class="olist"><?php foreach ($result['obligations_createur'] as $o): ?><li><?= htmlspecialchars($o) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
</div>
<?php if (!empty($result['timeline'])): ?><div class="rf"><div class="rl">Timeline</div><?php foreach ($result['timeline'] as $t): ?><div class="tl-item"><span class="tl-badge"><?= htmlspecialchars($t['delai'] ?? '') ?></span><span class="tl-text"><?= htmlspecialchars($t['etape'] ?? '') ?></span></div><?php endforeach; ?></div><?php endif; ?>
<?php if (!empty($result['conditions_paiement'])): ?><div class="rf"><div class="rl">💰 Paiement</div><div class="rv"><?= nl2br(htmlspecialchars($result['conditions_paiement'])) ?></div></div><?php endif; ?>
<?php if (!empty($result['droits_utilisation'])): ?><div class="rf"><div class="rl">📋 Droits</div><div class="rv"><?= nl2br(htmlspecialchars($result['droits_utilisation'])) ?></div></div><?php endif; ?>
<?php if (!empty($result['clause_resiliation'])): ?><div class="rf"><div class="rl">⚠️ Résiliation</div><div class="rv"><?= nl2br(htmlspecialchars($result['clause_resiliation'])) ?></div></div><?php endif; ?>
</div>
<?php endif; ?>
</div>
</div>
</body>
</html>
