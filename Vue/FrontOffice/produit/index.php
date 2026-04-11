<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../Controleur/produitC.php';

$controller = new ProduitC();

// ✅ BASE URL
$baseUrl = '/projet/Esprit-PW-2A22-2526-Devcore';

$id_marque = $_SESSION['user_id'] ?? 1;

$message = '';
$messageType = '';
$editProduit = null;

// Ajouter un produit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter') {
    $nomImage = $controller->gererUploadImage($_FILES['image'] ?? null);
    $produit = new Produit(
        null,
        htmlspecialchars($_POST['nom']),
        htmlspecialchars($_POST['description']),
        htmlspecialchars($_POST['caracteristiques']),
        floatval($_POST['prix']),
        $id_marque,
        $nomImage
    );
    $controller->ajouterProduit($produit);
    $message = "Produit ajouté avec succès !";
    $messageType = "success";
}

// Modifier un produit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'modifier') {
    $ancienProduit = $controller->recupererProduit(intval($_POST['id']));
    $nomImage = $controller->gererUploadImage($_FILES['image'] ?? null, $ancienProduit['image'] ?? null);
    $produit = new Produit(
        null,
        htmlspecialchars($_POST['nom']),
        htmlspecialchars($_POST['description']),
        htmlspecialchars($_POST['caracteristiques']),
        floatval($_POST['prix']),
        $id_marque,
        $nomImage
    );
    $controller->modifierProduit($produit, intval($_POST['id']));
    $message = "Produit modifié avec succès !";
    $messageType = "success";
}

// Supprimer un produit
if (isset($_GET['supprimer'])) {
    $controller->supprimerProduit(intval($_GET['supprimer']));
    $message = "Produit supprimé.";
    $messageType = "delete";
}

// Charger un produit pour édition
if (isset($_GET['modifier'])) {
    $editProduit = $controller->recupererProduit(intval($_GET['modifier']));
}

$produits = $controller->afficherProduits();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Produits – Cre8Connect</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Fraunces:wght@700;900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary: #4040f2;
            --primary-light: #eeeeff;
            --primary-hover: #2e2ed4;
            --text-main: #1a1a2e;
            --text-sub: #6b7280;
            --border: #e5e7eb;
            --bg: #f8f8fd;
            --white: #ffffff;
            --danger: #ef4444;
            --danger-light: #fef2f2;
            --success: #10b981;
            --success-light: #ecfdf5;
            --card-shadow: 0 2px 12px rgba(64,64,242,0.07);
            --radius: 12px;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--text-main);
            min-height: 100vh;
        }

        /* NAV */
        nav {
            background: var(--white);
            border-bottom: 1px solid var(--border);
            padding: 0 40px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        /* ✅ LOGO */
        .nav-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .nav-logo img {
            width: 36px;
            height: 36px;
            object-fit: contain;
            border-radius: 8px;
        }

        .nav-logo-text {
            font-family: 'Fraunces', serif;
            font-size: 20px;
            font-weight: 900;
            color: var(--primary);
            letter-spacing: -0.5px;
        }

        .nav-links {
            display: flex;
            gap: 32px;
            list-style: none;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text-sub);
            font-size: 14px;
            font-weight: 500;
            transition: color 0.2s;
        }

        .nav-links a:hover,
        .nav-links a.active { color: var(--primary); }

        /* MAIN LAYOUT */
        .page-wrapper {
            max-width: 1120px;
            margin: 0 auto;
            padding: 48px 24px;
        }

        /* PAGE HEADER */
        .page-header { margin-bottom: 40px; }

        .page-header h1 {
            font-family: 'Fraunces', serif;
            font-size: 36px;
            font-weight: 900;
            color: var(--primary);
            letter-spacing: -1px;
            line-height: 1.1;
        }

        .page-header p {
            color: var(--text-sub);
            font-size: 15px;
            margin-top: 6px;
            font-weight: 400;
        }

        /* FLASH MESSAGE */
        .flash {
            padding: 14px 20px;
            border-radius: var(--radius);
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 28px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .flash.success { background: var(--success-light); color: #065f46; border: 1px solid #a7f3d0; }
        .flash.delete  { background: var(--danger-light);  color: #991b1b; border: 1px solid #fecaca; }

        /* FORM + CONSEILS LAYOUT */
        .form-section {
            display: grid;
            grid-template-columns: 1fr 280px;
            gap: 28px;
            margin-bottom: 56px;
            align-items: start;
        }

        @media (max-width: 768px) {
            .form-section { grid-template-columns: 1fr; }
            .nav-links { display: none; }
        }

        .form-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 36px;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border);
        }

        .form-card.edit-mode { border: 2px solid var(--primary); }

        .form-card h2 {
            font-family: 'Fraunces', serif;
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 4px;
            letter-spacing: -0.5px;
        }

        .form-subtitle {
            font-size: 13px;
            color: var(--text-sub);
            margin-bottom: 28px;
        }

        .edit-banner {
            background: var(--primary);
            color: #fff;
            border-radius: 7px;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .edit-banner a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            font-size: 12px;
        }

        .form-group { margin-bottom: 20px; }

        label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 7px;
        }

        input[type="text"],
        input[type="number"],
        textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            font-family: 'DM Sans', sans-serif;
            color: var(--text-main);
            background: #fafafa;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }

        input[type="text"]:focus,
        input[type="number"]:focus,
        textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(64,64,242,0.1);
            background: var(--white);
        }

        input::placeholder,
        textarea::placeholder { color: #b0b4be; }

        textarea { resize: vertical; min-height: 100px; }

        .input-prefix { position: relative; }

        .input-prefix span {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-sub);
            font-size: 14px;
            font-weight: 600;
        }

        .input-prefix input { padding-left: 32px; }

        .upload-zone {
            border: 2px dashed var(--border);
            border-radius: 8px;
            padding: 24px 20px;
            text-align: center;
            background: #fafafa;
            cursor: pointer;
            transition: border-color 0.2s, background 0.2s;
            position: relative;
        }

        .upload-zone:hover {
            border-color: var(--primary);
            background: var(--primary-light);
        }

        .upload-zone input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }

        .upload-icon { font-size: 30px; margin-bottom: 8px; display: block; }

        .upload-zone p {
            font-size: 13px;
            color: var(--text-sub);
            margin: 0;
        }

        .upload-zone strong { color: var(--primary); font-weight: 600; }

        .upload-hint {
            font-size: 11px;
            color: var(--text-sub);
            margin-top: 6px;
            display: block;
        }

        .image-preview-current {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 12px;
            padding: 10px 14px;
            background: var(--primary-light);
            border-radius: 8px;
            border: 1px solid #d0d0fa;
        }

        .image-preview-current img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid var(--white);
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }

        .image-preview-current div { flex: 1; }

        .image-preview-current strong {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 2px;
        }

        .image-preview-current span {
            font-size: 12px;
            color: var(--text-sub);
        }

        .file-name-display {
            margin-top: 8px;
            font-size: 12px;
            color: var(--primary);
            font-weight: 500;
            display: none;
            align-items: center;
            gap: 6px;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 28px;
        }

        .btn-primary {
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 12px 28px;
            font-size: 14px;
            font-weight: 600;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
        }

        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
        }

        .btn-outline {
            background: transparent;
            color: var(--primary);
            border: 1.5px solid var(--primary);
            border-radius: 8px;
            padding: 11px 24px;
            font-size: 14px;
            font-weight: 600;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            transition: background 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }

        .btn-outline:hover { background: var(--primary-light); }

        .conseils-card {
            background: var(--primary-light);
            border-radius: var(--radius);
            padding: 24px;
            border: 1px solid #d0d0fa;
            position: sticky;
            top: 84px;
        }

        .conseils-card h3 {
            font-size: 15px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .conseil-item { margin-bottom: 14px; }
        .conseil-item:last-child { margin-bottom: 0; }

        .conseil-item strong {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 2px;
        }

        .conseil-item p {
            font-size: 12px;
            color: var(--text-sub);
            line-height: 1.5;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .section-header h2 {
            font-family: 'Fraunces', serif;
            font-size: 22px;
            font-weight: 700;
            color: var(--text-main);
            letter-spacing: -0.5px;
        }

        .badge {
            background: var(--primary-light);
            color: var(--primary);
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 12px;
            font-weight: 700;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .product-card {
            background: var(--white);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .product-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 28px rgba(64,64,242,0.12);
        }

        .product-img {
            width: 100%;
            height: 180px;
            overflow: hidden;
            background: var(--bg);
        }

        .product-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }

        .product-card:hover .product-img img { transform: scale(1.04); }

        .product-img--empty {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 42px;
            border-bottom: 1px solid var(--border);
            color: #c4c4e0;
        }

        .product-body { padding: 20px; }

        .product-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }

        .product-name {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-main);
            line-height: 1.3;
            flex: 1;
        }

        .product-price {
            background: var(--primary);
            color: #fff;
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 13px;
            font-weight: 700;
            white-space: nowrap;
            margin-left: 10px;
        }

        .product-desc {
            font-size: 13px;
            color: var(--text-sub);
            line-height: 1.6;
            margin-bottom: 12px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-carac {
            font-size: 12px;
            color: var(--primary);
            background: var(--primary-light);
            border-radius: 6px;
            padding: 6px 10px;
            margin-bottom: 16px;
            font-weight: 500;
        }

        .product-actions {
            display: flex;
            gap: 8px;
            border-top: 1px solid var(--border);
            padding-top: 16px;
        }

        .btn-edit {
            flex: 1;
            padding: 9px;
            background: var(--primary-light);
            color: var(--primary);
            border: none;
            border-radius: 7px;
            font-size: 13px;
            font-weight: 600;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: background 0.2s;
            display: inline-block;
        }

        .btn-edit:hover { background: #ddddf8; }

        .btn-delete {
            flex: 1;
            padding: 9px;
            background: var(--danger-light);
            color: var(--danger);
            border: none;
            border-radius: 7px;
            font-size: 13px;
            font-weight: 600;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: background 0.2s;
            display: inline-block;
        }

        .btn-delete:hover { background: #fecaca; }

        .empty-state {
            text-align: center;
            padding: 60px 24px;
            color: var(--text-sub);
            background: var(--white);
            border-radius: var(--radius);
            border: 1px solid var(--border);
        }

        .empty-icon { font-size: 52px; margin-bottom: 16px; display: block; }

        .empty-state h3 {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 8px;
        }

        .empty-state p { font-size: 14px; }
    </style>
</head>
<body>

<!-- NAVIGATION -->
<nav>
    <!-- ✅ LOGO intégré -->
    <a href="#" class="nav-logo">
        <img src="<?= $baseUrl ?>/Vue/public/images/logo.png" alt="Cre8Connect Logo">
        <span class="nav-logo-text">Cre8Connect</span>
    </a>
    <ul class="nav-links">
        <li><a href="#">Tableau de bord</a></li>
        <li><a href="#">Mes Offres</a></li>
        <li><a href="#" class="active">Mes Produits</a></li>
        <li><a href="#">Campagnes</a></li>
        <li><a href="#">Mon Profil</a></li>
    </ul>
</nav>

<div class="page-wrapper">

    <!-- PAGE HEADER -->
    <div class="page-header">
        <h1><?= $editProduit ? 'Modifier le produit' : 'Mes Produits' ?></h1>
        <p><?= $editProduit
            ? 'Mettez à jour les informations de votre produit.'
            : 'Gérez et promouvez vos produits auprès des créateurs de contenu.' ?>
        </p>
    </div>

    <!-- FLASH MESSAGE -->
    <?php if ($message): ?>
        <div class="flash <?= $messageType ?>">
            <?= $messageType === 'success' ? '✅' : '🗑️' ?>
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- FORM SECTION -->
    <div class="form-section">

        <div class="form-card <?= $editProduit ? 'edit-mode' : '' ?>">
            <h2><?= $editProduit ? '✏️ Modifier le produit' : 'Ajouter un produit' ?></h2>
            <p class="form-subtitle">
                <?= $editProduit
                    ? 'Modifiez les champs ci-dessous et sauvegardez.'
                    : 'Ajoutez un nouveau produit pour le promouvoir dans vos campagnes.' ?>
            </p>

            <?php if ($editProduit): ?>
                <div class="edit-banner">
                    <span>Mode édition : <?= htmlspecialchars($editProduit['nomProduit']) ?></span>
                    <a href="index.php">✕ Annuler</a>
                </div>
            <?php endif; ?>

            <form method="POST" action="index.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="<?= $editProduit ? 'modifier' : 'ajouter' ?>">
                <?php if ($editProduit): ?>
                    <input type="hidden" name="id" value="<?= $editProduit['idProduit'] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>Nom du produit</label>
                    <input type="text" name="nom"
                           placeholder="Ex: Crème hydratante premium"
                           value="<?= $editProduit ? htmlspecialchars($editProduit['nomProduit']) : '' ?>"
                           required>
                </div>

                <div class="form-group">
                    <label>Description détaillée</label>
                    <textarea name="description"
                              placeholder="Décrivez votre produit, ses atouts, son public cible..."
                              required><?= $editProduit ? htmlspecialchars($editProduit['description']) : '' ?></textarea>
                </div>

                <div class="form-group">
                    <label>Caractéristiques</label>
                    <input type="text" name="caracteristiques"
                           placeholder="Ex: Sans parabène, vegan, 200ml"
                           value="<?= $editProduit ? htmlspecialchars($editProduit['caracteristiques']) : '' ?>"
                           required>
                </div>

                <div class="form-group">
                    <label>Prix</label>
                    <div class="input-prefix">
                        <span>€</span>
                        <input type="number" name="prix" step="0.01" min="0"
                               placeholder="0.00"
                               value="<?= $editProduit ? htmlspecialchars($editProduit['prix']) : '' ?>"
                               required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Image du produit</label>

                    <?php if ($editProduit && !empty($editProduit['image'])): ?>
                        <div class="image-preview-current">
                            <img src="<?= $baseUrl ?>/Vue/public/produits/<?= htmlspecialchars($editProduit['image']) ?>"
                                 alt="Image actuelle">
                            <div>
                                <strong>Image actuelle</strong>
                                <span>Importez une nouvelle image pour la remplacer, ou laissez vide pour conserver celle-ci.</span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="upload-zone" id="uploadZone">
                        <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp" id="fileInput">
                        <span class="upload-icon">🖼️</span>
                        <p><strong>Cliquez pour importer</strong> ou glissez une image ici</p>
                        <span class="upload-hint">JPG, PNG, WEBP — 2 Mo maximum</span>
                    </div>

                    <div class="file-name-display" id="fileNameDisplay">
                        📎 <span id="fileNameText"></span>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        <?= $editProduit ? '💾 Sauvegarder' : '➕ Ajouter le produit' ?>
                    </button>
                    <?php if ($editProduit): ?>
                        <a href="index.php" class="btn-outline">Annuler</a>
                    <?php else: ?>
                        <button type="reset" class="btn-outline" onclick="resetFileDisplay()">Réinitialiser</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- CONSEILS -->
        <div class="conseils-card">
            <h3>💡 Conseils</h3>
            <div class="conseil-item">
                <strong>Nom clair</strong>
                <p>Choisissez un nom court, précis et mémorable pour votre produit.</p>
            </div>
            <div class="conseil-item">
                <strong>Description convaincante</strong>
                <p>Expliquez les bénéfices du produit. Les créateurs veulent comprendre ce qu'ils vont promouvoir.</p>
            </div>
            <div class="conseil-item">
                <strong>Caractéristiques précises</strong>
                <p>Listez les points forts : matériaux, formats, labels (bio, vegan, etc.).</p>
            </div>
            <div class="conseil-item">
                <strong>Prix cohérent</strong>
                <p>Un prix bien positionné aide les créateurs à estimer la valeur du produit.</p>
            </div>
            <div class="conseil-item">
                <strong>Belle image</strong>
                <p>Une photo nette et lumineuse augmente fortement l'attractivité du produit auprès des créateurs.</p>
            </div>
        </div>

    </div>

    <!-- PRODUCTS LIST -->
    <div class="section-header">
        <h2>Mes produits</h2>
        <span class="badge"><?= count($produits) ?> produit<?= count($produits) > 1 ? 's' : '' ?></span>
    </div>

    <?php if (empty($produits)): ?>
        <div class="empty-state">
            <span class="empty-icon">📦</span>
            <h3>Aucun produit pour l'instant</h3>
            <p>Ajoutez votre premier produit pour commencer à collaborer avec des créateurs.</p>
        </div>
    <?php else: ?>
        <div class="products-grid">
            <?php foreach ($produits as $p): ?>
                <div class="product-card">
                    <?php if (!empty($p['image'])): ?>
                        <div class="product-img">
                            <img src="<?= $baseUrl ?>/Vue/public/produits/<?= htmlspecialchars($p['image']) ?>"
                                 alt="<?= htmlspecialchars($p['nomProduit']) ?>">
                        </div>
                    <?php else: ?>
                        <div class="product-img product-img--empty">📦</div>
                    <?php endif; ?>

                    <div class="product-body">
                        <div class="product-card-header">
                            <div class="product-name"><?= htmlspecialchars($p['nomProduit']) ?></div>
                            <div class="product-price"><?= number_format($p['prix'], 2, ',', ' ') ?> €</div>
                        </div>
                        <p class="product-desc"><?= htmlspecialchars($p['description']) ?></p>
                        <div class="product-carac">🏷️ <?= htmlspecialchars($p['caracteristiques']) ?></div>
                        <div class="product-actions">
                            <a href="index.php?modifier=<?= $p['idProduit'] ?>" class="btn-edit">✏️ Modifier</a>
                            <a href="index.php?supprimer=<?= $p['idProduit'] ?>"
                               class="btn-delete"
                               onclick="return confirm('Supprimer ce produit définitivement ?')">🗑️ Supprimer</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<script>
    document.getElementById('fileInput').addEventListener('change', function () {
        const display = document.getElementById('fileNameDisplay');
        const nameText = document.getElementById('fileNameText');
        if (this.files && this.files[0]) {
            nameText.textContent = this.files[0].name;
            display.style.display = 'flex';
            document.getElementById('uploadZone').style.borderColor = 'var(--primary)';
            document.getElementById('uploadZone').style.background = 'var(--primary-light)';
        } else {
            display.style.display = 'none';
        }
    });

    function resetFileDisplay() {
        document.getElementById('fileNameDisplay').style.display = 'none';
        document.getElementById('uploadZone').style.borderColor = '';
        document.getElementById('uploadZone').style.background = '';
    }
</script>
</body>
</html>