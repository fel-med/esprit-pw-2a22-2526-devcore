<?php
/* ═══ MARQUE : Optimiser un produit via IA ═══ */
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../../Controleur/produitC.php';
$produitC = new ProduitC();
$result = null; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $cat = trim($_POST['categorie'] ?? 'Général');
    if ($nom && $desc) {
        $result = $produitC->optimiserProduitIA($nom, $desc, $cat);
        if (!$result) $error = "Erreur IA. Réessayez.";
    } else { $error = "Nom et description requis."; }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Optimiser Produit IA — Cre8Connect</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
:root{--bg:#f6f6fc;--white:#fff;--border:#e8eaf0;--accent:#5b4cf5;--accent-soft:rgba(91,76,245,.08);--text:#111827;--sub:#4b5563;--muted:#9ca3af;--success:#0ea370;--success-soft:rgba(14,163,112,.1);--radius:14px;}
*{margin:0;padding:0;box-sizing:border-box;}body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;}
nav{background:var(--white);border-bottom:1px solid var(--border);padding:0 40px;display:flex;align-items:center;justify-content:space-between;height:64px;position:sticky;top:0;z-index:50;}
.logo{font-size:1.25rem;font-weight:800;color:var(--accent);}.logo em{color:var(--text);font-style:normal;}
.badge{background:var(--accent);color:#fff;border-radius:20px;padding:5px 14px;font-size:.75rem;font-weight:700;}
.container{max-width:800px;margin:0 auto;padding:40px 24px 80px;}
h1{font-size:1.5rem;font-weight:800;margin-bottom:6px;}.sub{color:var(--sub);font-size:.9rem;margin-bottom:28px;}
.card{background:var(--white);border:1px solid var(--border);border-radius:var(--radius);padding:28px;box-shadow:0 4px 16px rgba(0,0,0,.08);margin-bottom:24px;}
.card-title{font-size:1rem;font-weight:700;margin-bottom:18px;color:var(--accent);}
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:14px;}.fg-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
label{font-size:.82rem;font-weight:700;color:var(--sub);}
input,textarea{padding:10px 14px;border:1.5px solid var(--border);border-radius:10px;font-family:inherit;font-size:.875rem;background:var(--bg);}
input:focus,textarea:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-soft);}
textarea{resize:vertical;min-height:80px;}
.btn{display:inline-flex;align-items:center;gap:8px;padding:12px 24px;border-radius:12px;font-family:inherit;font-size:.9rem;font-weight:700;cursor:pointer;border:none;background:linear-gradient(135deg,#5b4cf5,#8b5cf6);color:#fff;transition:all .2s;}
.btn:hover{transform:translateY(-2px);}
.err{background:rgba(220,38,38,.08);color:#dc2626;border-radius:10px;padding:12px;font-size:.85rem;font-weight:600;margin-bottom:20px;}
.result{background:linear-gradient(135deg,rgba(91,76,245,.03),rgba(139,92,246,.06));border:1.5px solid rgba(91,76,245,.15);border-radius:var(--radius);padding:24px;margin-top:20px;}
.rf{margin-bottom:14px;}.rl{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--muted);margin-bottom:4px;}
.rv{font-size:.9rem;line-height:1.6;}.rv.big{font-size:1.05rem;font-weight:700;color:var(--accent);}
.tags{display:flex;flex-wrap:wrap;gap:6px;}
.tag{display:inline-block;padding:4px 12px;border-radius:20px;font-size:.78rem;font-weight:700;}
.tag-b{background:var(--accent-soft);color:var(--accent);}.tag-g{background:var(--success-soft);color:var(--success);}
.back{display:inline-flex;align-items:center;gap:6px;color:var(--sub);text-decoration:none;font-size:.85rem;font-weight:600;margin-bottom:20px;}
.loading{display:none;align-items:center;gap:10px;padding:16px;justify-content:center;color:var(--accent);font-weight:600;}.loading.show{display:flex;}
.spinner{width:18px;height:18px;border:3px solid var(--accent-soft);border-top-color:var(--accent);border-radius:50%;animation:spin .6s linear infinite;}
@keyframes spin{to{transform:rotate(360deg)}}
@media(max-width:600px){.fg-grid{grid-template-columns:1fr;}}
</style>
</head>
<body>
<nav><span class="logo">Cre8<em>Connect</em></span><span class="badge">Marque</span></nav>
<div class="container">
<a href="../produit/" class="back"><i class="fas fa-arrow-left"></i> Retour aux produits</a>
<h1>📊 Optimiser un Produit avec l'IA</h1>
<p class="sub">Améliorez votre fiche produit grâce à l'intelligence artificielle.</p>
<?php if ($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<div class="card">
<div class="card-title"><i class="fas fa-chart-line"></i> Fiche produit</div>
<form method="POST" onsubmit="document.getElementById('ld').classList.add('show')">
<div class="fg-grid">
<div class="fg"><label>Nom du produit *</label><input type="text" name="nom" required placeholder="Ex : Sérum Vitamine C" value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>"></div>
<div class="fg"><label>Catégorie</label><input type="text" name="categorie" placeholder="Ex : Cosmétiques" value="<?= htmlspecialchars($_POST['categorie'] ?? '') ?>"></div>
</div>
<div class="fg"><label>Description actuelle *</label><textarea name="description" required placeholder="Décrivez votre produit..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea></div>
<div class="loading" id="ld"><div class="spinner"></div> Optimisation en cours...</div>
<button type="submit" class="btn"><i class="fas fa-chart-line"></i> 📊 Optimiser</button>
</form>
<?php if ($result): ?>
<div class="result">
<div class="card-title">✅ Produit Optimisé</div>
<div class="rf"><div class="rl">Description améliorée</div><div class="rv"><?= nl2br(htmlspecialchars($result['description_amelioree'] ?? '')) ?></div></div>
<?php if (!empty($result['mots_cles'])): ?><div class="rf"><div class="rl">Mots-clés</div><div class="tags"><?php foreach ($result['mots_cles'] as $k): ?><span class="tag tag-b"><?= htmlspecialchars($k) ?></span><?php endforeach; ?></div></div><?php endif; ?>
<?php if (!empty($result['hashtags'])): ?><div class="rf"><div class="rl">Hashtags</div><div class="tags"><?php foreach ($result['hashtags'] as $h): ?><span class="tag tag-g"><?= htmlspecialchars($h) ?></span><?php endforeach; ?></div></div><?php endif; ?>
<?php if (!empty($result['conseil_prix'])): ?><div class="rf"><div class="rl">Conseil prix</div><div class="rv"><?= htmlspecialchars($result['conseil_prix']) ?></div></div><?php endif; ?>
<?php if (!empty($result['score_attractivite'])): ?><div class="rf"><div class="rl">Score attractivité</div><div class="rv big">⭐ <?= htmlspecialchars($result['score_attractivite']) ?> / 10</div></div><?php endif; ?>
</div>
<?php endif; ?>
</div>
</div>
</body>
</html>
