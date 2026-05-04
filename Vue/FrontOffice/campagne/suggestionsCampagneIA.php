<?php
/* ═══ CRÉATEUR : Suggestions de campagnes via IA ═══ */
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../../Controleur/campagneC.php';
$campagneC = new CampagneC();
$result = null; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $comp = trim($_POST['competences'] ?? '');
    $int  = trim($_POST['interets'] ?? '');
    $aud  = trim($_POST['audience'] ?? '');
    if ($comp && $int && $aud) {
        $result = $campagneC->suggererCampagnesIA($comp, $int, $aud);
        if (!$result) $error = "Erreur IA. Réessayez.";
    } else { $error = "Remplissez tous les champs."; }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Suggestions Campagnes IA — Cre8Connect</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
:root{--bg:#f6f6fc;--white:#fff;--border:#e8eaf0;--accent:#0ea370;--accent-soft:rgba(14,163,112,.08);--text:#111827;--sub:#4b5563;--muted:#9ca3af;--radius:14px;}
*{margin:0;padding:0;box-sizing:border-box;}body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;}
nav{background:var(--white);border-bottom:1px solid var(--border);padding:0 40px;display:flex;align-items:center;justify-content:space-between;height:64px;position:sticky;top:0;z-index:50;}
.logo{font-size:1.25rem;font-weight:800;color:#5b4cf5;}.logo em{color:var(--text);font-style:normal;}
.badge{background:var(--accent);color:#fff;border-radius:20px;padding:5px 14px;font-size:.75rem;font-weight:700;}
.container{max-width:800px;margin:0 auto;padding:40px 24px 80px;}
h1{font-size:1.5rem;font-weight:800;margin-bottom:6px;}
.sub{color:var(--sub);font-size:.9rem;margin-bottom:28px;}
.card{background:var(--white);border:1px solid var(--border);border-radius:var(--radius);padding:28px;box-shadow:0 4px 16px rgba(0,0,0,.08);margin-bottom:24px;}
.card-title{font-size:1rem;font-weight:700;margin-bottom:18px;color:var(--accent);}
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:14px;}
label{font-size:.82rem;font-weight:700;color:var(--sub);}
input{padding:10px 14px;border:1.5px solid var(--border);border-radius:10px;font-family:inherit;font-size:.875rem;background:var(--bg);}
input:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-soft);}
.btn{display:inline-flex;align-items:center;gap:8px;padding:12px 24px;border-radius:12px;font-family:inherit;font-size:.9rem;font-weight:700;cursor:pointer;border:none;background:linear-gradient(135deg,#0ea370,#34d399);color:#fff;transition:all .2s;}
.btn:hover{transform:translateY(-2px);}
.err{background:rgba(220,38,38,.08);color:#dc2626;border:1px solid rgba(220,38,38,.2);border-radius:10px;padding:12px;font-size:.85rem;font-weight:600;margin-bottom:20px;}
.result{background:linear-gradient(135deg,rgba(14,163,112,.04),rgba(52,211,153,.08));border:1.5px solid rgba(14,163,112,.2);border-radius:var(--radius);padding:24px;margin-top:20px;}
.sug{background:var(--white);border:1px solid var(--border);border-radius:12px;padding:16px;margin-bottom:12px;}
.sug-type{font-weight:700;color:var(--accent);margin-bottom:4px;}
.sug-text{font-size:.85rem;color:var(--sub);line-height:1.6;}
.sug-tip{font-size:.82rem;background:var(--accent-soft);padding:8px 12px;border-radius:8px;margin-top:6px;}
.conseil{margin-top:16px;padding:14px;background:rgba(14,163,112,.06);border-radius:10px;font-size:.88rem;line-height:1.6;}
.back{display:inline-flex;align-items:center;gap:6px;color:var(--sub);text-decoration:none;font-size:.85rem;font-weight:600;margin-bottom:20px;}
.loading{display:none;align-items:center;gap:10px;padding:16px;justify-content:center;color:var(--accent);font-weight:600;}.loading.show{display:flex;}
.spinner{width:18px;height:18px;border:3px solid var(--accent-soft);border-top-color:var(--accent);border-radius:50%;animation:spin .6s linear infinite;}
@keyframes spin{to{transform:rotate(360deg)}}
</style>
</head>
<body>
<nav><span class="logo">Cre8<em>Connect</em></span><span class="badge">Créateur</span></nav>
<div class="container">
<a href="../campagne/indexC.php" class="back"><i class="fas fa-arrow-left"></i> Retour</a>
<h1>💡 Suggestions de Campagnes IA</h1>
<p class="sub">Décrivez votre profil pour recevoir des recommandations personnalisées.</p>
<?php if ($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<div class="card">
<div class="card-title"><i class="fas fa-lightbulb"></i> Mon profil créateur</div>
<form method="POST" onsubmit="document.getElementById('ld').classList.add('show')">
<div class="fg"><label>Compétences *</label><input type="text" name="competences" required placeholder="Ex : Vidéo, photo, rédaction" value="<?= htmlspecialchars($_POST['competences'] ?? '') ?>"></div>
<div class="fg"><label>Centres d'intérêt *</label><input type="text" name="interets" required placeholder="Ex : Mode, tech, bien-être" value="<?= htmlspecialchars($_POST['interets'] ?? '') ?>"></div>
<div class="fg"><label>Mon audience *</label><input type="text" name="audience" required placeholder="Ex : 18-25 ans, Instagram, 10K abonnés" value="<?= htmlspecialchars($_POST['audience'] ?? '') ?>"></div>
<div class="loading" id="ld"><div class="spinner"></div> Analyse en cours...</div>
<button type="submit" class="btn"><i class="fas fa-lightbulb"></i> Obtenir suggestions</button>
</form>
<?php if ($result): ?>
<div class="result">
<div class="card-title">🎯 Campagnes recommandées</div>
<?php if (!empty($result['suggestions'])): foreach ($result['suggestions'] as $s): ?>
<div class="sug">
<div class="sug-type"><?= htmlspecialchars($s['type_campagne'] ?? '') ?></div>
<div class="sug-text"><strong>Pourquoi :</strong> <?= htmlspecialchars($s['raison'] ?? '') ?></div>
<div class="sug-tip">💡 <?= htmlspecialchars($s['conseil'] ?? '') ?></div>
</div>
<?php endforeach; endif; ?>
<?php if (!empty($result['conseil_general'])): ?>
<div class="conseil">📌 <strong>Conseil :</strong> <?= htmlspecialchars($result['conseil_general']) ?></div>
<?php endif; ?>
</div>
<?php endif; ?>
</div>
</div>
</body>
</html>
