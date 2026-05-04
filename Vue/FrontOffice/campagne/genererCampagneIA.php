<?php
/* ═══ MARQUE : Générer une campagne via IA ═══ */
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../../Controleur/campagneC.php';

$campagneC = new CampagneC();
$result    = null;
$error     = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $produit = trim($_POST['produit'] ?? '');
    $cible   = trim($_POST['cible'] ?? '');
    $budget  = floatval($_POST['budget'] ?? 0);
    if ($produit && $cible && $budget > 0) {
        $result = $campagneC->genererCampagneIA($produit, $cible, $budget);
        if (!$result) $error = "L'IA n'a pas pu générer la campagne. Réessayez.";
    } else {
        $error = "Veuillez remplir tous les champs.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>✨ Générer Campagne IA — Cre8Connect</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
:root{--bg:#f6f6fc;--white:#fff;--border:#e8eaf0;--accent:#5b4cf5;--accent-soft:rgba(91,76,245,.08);--text:#111827;--sub:#4b5563;--muted:#9ca3af;--danger:#dc2626;--radius:14px;--shadow:0 4px 16px rgba(0,0,0,.08);}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;}
nav{background:var(--white);border-bottom:1px solid var(--border);padding:0 40px;display:flex;align-items:center;justify-content:space-between;height:64px;position:sticky;top:0;z-index:50;}
.nav-logo{font-size:1.25rem;font-weight:800;color:var(--accent);text-decoration:none;}.nav-logo em{color:var(--text);font-style:normal;}
.nav-links{display:flex;gap:4px;}.nav-link{padding:8px 16px;border-radius:8px;color:var(--sub);text-decoration:none;font-size:.875rem;font-weight:600;transition:all .15s;}
.nav-link:hover,.nav-link.active{background:var(--accent-soft);color:var(--accent);}
.badge-role{background:var(--accent);color:#fff;border-radius:20px;padding:5px 14px;font-size:.75rem;font-weight:700;}
.container{max-width:800px;margin:0 auto;padding:40px 24px 80px;}
.page-title{font-size:1.6rem;font-weight:800;margin-bottom:6px;}.page-sub{color:var(--sub);font-size:.9rem;margin-bottom:32px;}
.card{background:var(--white);border:1px solid var(--border);border-radius:var(--radius);padding:28px;box-shadow:var(--shadow);margin-bottom:24px;}
.card-title{font-size:1.05rem;font-weight:700;margin-bottom:20px;display:flex;align-items:center;gap:10px;}
.card-title i{color:var(--accent);}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
.form-group{display:flex;flex-direction:column;gap:5px;margin-bottom:14px;}
.form-group.full{grid-column:1/-1;}
label{font-size:.82rem;font-weight:700;color:var(--sub);}
input,textarea{padding:10px 14px;border:1.5px solid var(--border);border-radius:10px;font-family:inherit;font-size:.875rem;background:var(--bg);transition:border .15s,box-shadow .15s;}
input:focus,textarea:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-soft);}
textarea{resize:vertical;min-height:70px;}
.btn-ia{display:inline-flex;align-items:center;gap:8px;padding:12px 28px;border-radius:12px;font-family:inherit;font-size:.9rem;font-weight:700;cursor:pointer;border:none;background:linear-gradient(135deg,#5b4cf5,#8b5cf6);color:#fff;box-shadow:0 4px 16px rgba(91,76,245,.3);transition:all .2s;}
.btn-ia:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(91,76,245,.4);}
.error-msg{background:rgba(220,38,38,.08);color:var(--danger);border:1px solid rgba(220,38,38,.2);border-radius:10px;padding:12px 16px;font-size:.85rem;font-weight:600;margin-bottom:20px;}
.result{background:linear-gradient(135deg,rgba(91,76,245,.03),rgba(139,92,246,.06));border:1.5px solid rgba(91,76,245,.15);border-radius:var(--radius);padding:28px;margin-top:20px;}
.result-title{font-size:1rem;font-weight:800;color:var(--accent);margin-bottom:16px;}
.r-field{margin-bottom:14px;}.r-label{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--muted);margin-bottom:4px;}
.r-value{font-size:.9rem;line-height:1.6;}.r-value.big{font-size:1.05rem;font-weight:700;color:var(--accent);}
.back-link{display:inline-flex;align-items:center;gap:6px;color:var(--sub);text-decoration:none;font-size:.85rem;font-weight:600;margin-bottom:20px;}
.back-link:hover{color:var(--accent);}
.loading{display:none;align-items:center;gap:10px;padding:16px;justify-content:center;color:var(--accent);font-weight:600;}
.loading.show{display:flex;}
.spinner{width:18px;height:18px;border:3px solid var(--accent-soft);border-top-color:var(--accent);border-radius:50%;animation:spin .6s linear infinite;}
@keyframes spin{to{transform:rotate(360deg)}}
@media(max-width:600px){.form-grid{grid-template-columns:1fr;}}
    </style>
</head>
<body>
<nav>
    <a class="nav-logo">Cre8<em>Connect</em></a>
    <div class="nav-links">
        <a href="../campagne/" class="nav-link">Campagnes</a>
        <a href="../produit/" class="nav-link">Produits</a>
        <a href="../contrat/" class="nav-link">Contrats</a>
        <a href="genererCampagneIA.php" class="nav-link active">🤖 IA Campagne</a>
    </div>
    <span class="badge-role">Marque</span>
</nav>

<div class="container">
    <a href="../campagne/" class="back-link"><i class="fas fa-arrow-left"></i> Retour aux campagnes</a>
    <h1 class="page-title">✨ Générer une Campagne avec l'IA</h1>
    <p class="page-sub">Décrivez votre produit et votre cible, l'IA créera une campagne optimisée.</p>

    <?php if ($error): ?>
    <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-title"><i class="fas fa-wand-magic-sparkles"></i> Paramètres de génération</div>
        <form method="POST" onsubmit="document.getElementById('load').classList.add('show')">
            <div class="form-grid">
                <div class="form-group">
                    <label>Produit à promouvoir *</label>
                    <input type="text" name="produit" required placeholder="Ex : Sneakers éco-responsables" value="<?= htmlspecialchars($_POST['produit'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Budget (€) *</label>
                    <input type="number" name="budget" required min="1" step="0.01" placeholder="5000" value="<?= htmlspecialchars($_POST['budget'] ?? '') ?>">
                </div>
                <div class="form-group full">
                    <label>Audience cible *</label>
                    <input type="text" name="cible" required placeholder="Ex : 18-30 ans, passionnés de mode durable" value="<?= htmlspecialchars($_POST['cible'] ?? '') ?>">
                </div>
            </div>
            <div class="loading" id="load"><div class="spinner"></div> L'IA génère votre campagne...</div>
            <button type="submit" class="btn-ia"><i class="fas fa-wand-magic-sparkles"></i> Générer avec IA</button>
        </form>

        <?php if ($result): ?>
        <div class="result">
            <div class="result-title">🎯 Campagne Générée par l'IA</div>
            <div class="r-field"><div class="r-label">Titre</div><div class="r-value big"><?= htmlspecialchars($result['titre'] ?? '') ?></div></div>
            <div class="r-field"><div class="r-label">Description</div><div class="r-value"><?= nl2br(htmlspecialchars($result['description'] ?? '')) ?></div></div>
            <div class="r-field"><div class="r-label">Objectif</div><div class="r-value"><?= htmlspecialchars($result['objectif'] ?? '') ?></div></div>
            <div class="r-field"><div class="r-label">Type de contenu recommandé</div><div class="r-value"><?= htmlspecialchars($result['type_contenu'] ?? '') ?></div></div>
        </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
