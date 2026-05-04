<?php
/* ═══ CRÉATEUR : Comprendre un contrat via IA ═══ */
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../../Controleur/contratC.php';
$contratC = new ContratC();
$result = null; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim($_POST['titre'] ?? '');
    $desc  = trim($_POST['description'] ?? '');
    $mont  = floatval($_POST['montant'] ?? 0);
    $dd    = trim($_POST['date_debut'] ?? '');
    $df    = trim($_POST['date_fin'] ?? '');
    if ($titre && $desc && $mont > 0 && $dd && $df) {
        $result = $contratC->comprendreContratIA($titre, $desc, $mont, $dd, $df);
        if (!$result) $error = "Erreur IA. Réessayez.";
    } else { $error = "Remplissez tous les champs."; }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Comprendre Contrat IA — Cre8Connect</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
:root{--bg:#f6f6fc;--white:#fff;--border:#e8eaf0;--accent:#0ea370;--accent-soft:rgba(14,163,112,.08);--text:#111827;--sub:#4b5563;--muted:#9ca3af;--warn:#d97706;--warn-soft:rgba(217,119,6,.1);--radius:14px;}
*{margin:0;padding:0;box-sizing:border-box;}body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;}
nav{background:var(--white);border-bottom:1px solid var(--border);padding:0 40px;display:flex;align-items:center;justify-content:space-between;height:64px;position:sticky;top:0;z-index:50;}
.logo{font-size:1.25rem;font-weight:800;color:#5b4cf5;}.logo em{color:var(--text);font-style:normal;}
.badge{background:var(--accent);color:#fff;border-radius:20px;padding:5px 14px;font-size:.75rem;font-weight:700;}
.container{max-width:800px;margin:0 auto;padding:40px 24px 80px;}
h1{font-size:1.5rem;font-weight:800;margin-bottom:6px;}.sub-p{color:var(--sub);font-size:.9rem;margin-bottom:28px;}
.card{background:var(--white);border:1px solid var(--border);border-radius:var(--radius);padding:28px;box-shadow:0 4px 16px rgba(0,0,0,.08);}
.ct{font-size:1rem;font-weight:700;margin-bottom:18px;color:var(--accent);}
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:14px;}.fgrid{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
label{font-size:.82rem;font-weight:700;color:var(--sub);}
input,textarea{padding:10px 14px;border:1.5px solid var(--border);border-radius:10px;font-family:inherit;font-size:.875rem;background:var(--bg);}
input:focus,textarea:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-soft);}
textarea{resize:vertical;min-height:80px;}
.btn{display:inline-flex;align-items:center;gap:8px;padding:12px 24px;border-radius:12px;font-family:inherit;font-size:.9rem;font-weight:700;cursor:pointer;border:none;background:linear-gradient(135deg,#0ea370,#34d399);color:#fff;transition:all .2s;}
.btn:hover{transform:translateY(-2px);}
.err{background:rgba(220,38,38,.08);color:#dc2626;border-radius:10px;padding:12px;font-size:.85rem;font-weight:600;margin-bottom:20px;}
.result{background:linear-gradient(135deg,rgba(14,163,112,.04),rgba(52,211,153,.08));border:1.5px solid rgba(14,163,112,.2);border-radius:var(--radius);padding:24px;margin-top:20px;}
.rf{margin-bottom:14px;}.rl{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--muted);margin-bottom:4px;}
.rv{font-size:.9rem;line-height:1.6;}.rv.big{font-size:1.05rem;font-weight:700;color:var(--accent);}
.pill-list{display:flex;flex-wrap:wrap;gap:6px;}
.pill{padding:6px 14px;border-radius:20px;font-size:.82rem;font-weight:600;}
.pill-g{background:var(--accent-soft);color:var(--accent);}
.pill-w{background:var(--warn-soft);color:var(--warn);}
.verdict{margin-top:16px;padding:16px;background:rgba(14,163,112,.06);border-radius:12px;font-size:.92rem;line-height:1.6;font-weight:600;border-left:4px solid var(--accent);}
.back{display:inline-flex;align-items:center;gap:6px;color:var(--sub);text-decoration:none;font-size:.85rem;font-weight:600;margin-bottom:20px;}
.loading{display:none;align-items:center;gap:10px;padding:16px;justify-content:center;color:var(--accent);font-weight:600;}.loading.show{display:flex;}
.spinner{width:18px;height:18px;border:3px solid var(--accent-soft);border-top-color:var(--accent);border-radius:50%;animation:spin .6s linear infinite;}
@keyframes spin{to{transform:rotate(360deg)}}
@media(max-width:600px){.fgrid{grid-template-columns:1fr;}}
</style>
</head>
<body>
<nav><span class="logo">Cre8<em>Connect</em></span><span class="badge">Créateur</span></nav>
<div class="container">
<a href="../contrat/indexC.php" class="back"><i class="fas fa-arrow-left"></i> Retour</a>
<h1>🔍 Comprendre mon Contrat avec l'IA</h1>
<p class="sub-p">L'IA analyse votre contrat et vous l'explique en langage simple.</p>
<?php if ($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<div class="card">
<div class="ct"><i class="fas fa-search"></i> Informations du contrat</div>
<form method="POST" onsubmit="document.getElementById('ld').classList.add('show')">
<div class="fg"><label>Titre du contrat *</label><input type="text" name="titre" required placeholder="Ex : Partenariat Été 2025" value="<?= htmlspecialchars($_POST['titre'] ?? '') ?>"></div>
<div class="fg"><label>Description / clauses *</label><textarea name="description" required placeholder="Collez ici le contenu du contrat..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea></div>
<div class="fgrid">
<div class="fg"><label>Montant (€) *</label><input type="number" name="montant" required min="1" step="0.01" value="<?= htmlspecialchars($_POST['montant'] ?? '') ?>"></div>
<div class="fg"><label>Date début *</label><input type="date" name="date_debut" required value="<?= htmlspecialchars($_POST['date_debut'] ?? '') ?>"></div>
</div>
<div class="fg"><label>Date fin *</label><input type="date" name="date_fin" required value="<?= htmlspecialchars($_POST['date_fin'] ?? '') ?>"></div>
<div class="loading" id="ld"><div class="spinner"></div> Analyse en cours...</div>
<button type="submit" class="btn"><i class="fas fa-search"></i> Analyser mon contrat</button>
</form>
<?php if ($result): ?>
<div class="result">
<div class="ct">📖 Analyse simplifiée</div>
<?php if (!empty($result['resume_simple'])): ?><div class="rf"><div class="rl">Résumé</div><div class="rv"><?= nl2br(htmlspecialchars($result['resume_simple'])) ?></div></div><?php endif; ?>
<?php if (!empty($result['points_cles'])): ?><div class="rf"><div class="rl">Points clés</div><div class="pill-list"><?php foreach ($result['points_cles'] as $p): ?><span class="pill pill-g"><?= htmlspecialchars($p) ?></span><?php endforeach; ?></div></div><?php endif; ?>
<?php if (!empty($result['avantages'])): ?><div class="rf"><div class="rl">✅ Avantages</div><div class="pill-list"><?php foreach ($result['avantages'] as $a): ?><span class="pill pill-g"><?= htmlspecialchars($a) ?></span><?php endforeach; ?></div></div><?php endif; ?>
<?php if (!empty($result['points_vigilance'])): ?><div class="rf"><div class="rl">⚠️ Points de vigilance</div><div class="pill-list"><?php foreach ($result['points_vigilance'] as $v): ?><span class="pill pill-w"><?= htmlspecialchars($v) ?></span><?php endforeach; ?></div></div><?php endif; ?>
<?php if (!empty($result['estimation_travail'])): ?><div class="rf"><div class="rl">⏱ Estimation travail</div><div class="rv"><?= htmlspecialchars($result['estimation_travail']) ?></div></div><?php endif; ?>
<?php if (!empty($result['verdict'])): ?><div class="verdict">🎯 <?= htmlspecialchars($result['verdict']) ?></div><?php endif; ?>
</div>
<?php endif; ?>
</div>
</div>
</body>
</html>
