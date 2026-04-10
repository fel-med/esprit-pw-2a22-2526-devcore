<?php
session_start();
// Simulate logged-in user (remove this when login system is ready)
if (!isset($_SESSION['utilisateur'])) {
    $_SESSION['utilisateur']['id'] = 1;  // Test with marque ID 1
}

require_once __DIR__ . '/../../../Controleur/offreC.php';

$controller = new OffreC();
$brandId = $_SESSION['utilisateur']['id'];
$errors = [];
$form = [
    'titre' => '',
    'description' => '',
    'objectif' => '',
    'budgetMin' => '',
    'budgetMax' => '',
    'datePublication' => '',
    'dateLimite' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = [
        'titre' => $_POST['titre'] ?? '',
        'description' => $_POST['description'] ?? '',
        'objectif' => $_POST['objectif'] ?? '',
        'budgetMin' => $_POST['budgetMin'] ?? '',
        'budgetMax' => $_POST['budgetMax'] ?? '',
        'datePublication' => $_POST['datePublication'] ?? '',
        'dateLimite' => $_POST['dateLimite'] ?? ''
    ];

    $errors = $controller->validateOffreData($form);

    if (empty($errors)) {
        $offre = new Offre(
            null,
            $brandId,
            trim($form['titre']),
            trim($form['description']),
            trim($form['objectif']),
            floatval($form['budgetMin']),
            floatval($form['budgetMax']),
            trim($form['datePublication']),
            trim($form['dateLimite']),
            'active'
        );

        if ($controller->createOffre($offre)) {
            header('Location: index.php?message=' . urlencode('Offre créée avec succès.'));
            exit;
        }
        $errors[] = 'Impossible de créer l\'offre. Réessayez plus tard.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer une offre - Cre8Connect</title>
    <link rel="stylesheet" href="../css/frontoffice.css">
    <link rel="stylesheet" href="offre.css">
</head>
<body>
    <div class="container py-5">
        <div class="row mb-5">
            <div class="col-lg-8">
                <h1 class="display-5 fw-bold mb-2 gradient-title">Créer une offre</h1>
                <p class="lead text-muted">Ajoutez une nouvelle offre pour attirer les collaborateurs.</p>
            </div>
        </div>

        <div class="row g-5">
            <div class="col-lg-8">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                        <h5 class="alert-heading">Erreurs à corriger</h5>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form method="post" action="create.php" class="needs-validation">
                    <input type="hidden" name="idMarque" value="<?php echo htmlspecialchars($brandId); ?>">

                    <div class="mb-4">
                        <label for="titre" class="form-label fw-semibold">Titre de l'offre</label>
                        <input type="text" class="form-control form-control-lg" id="titre" name="titre" value="<?php echo htmlspecialchars($form['titre']); ?>" placeholder="Ex: Créateur de contenu pour TikTok" required>
                    </div>

                    <div class="mb-4">
                        <label for="description" class="form-label fw-semibold">Description détaillée</label>
                        <textarea class="form-control" id="description" name="description" rows="5" placeholder="Décrivez l'offre, les attentes, les détails du projet..." required><?php echo htmlspecialchars($form['description']); ?></textarea>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="objectif" class="form-label fw-semibold">Objectif</label>
                            <input type="text" class="form-control" id="objectif" name="objectif" value="<?php echo htmlspecialchars($form['objectif']); ?>" placeholder="Ex: 10 vidéos" required>
                        </div>
                        <div class="col-md-6">
                            <label for="budgetMin" class="form-label fw-semibold">Budget minimum</label>
                            <div class="input-group">
                                <span class="input-group-text">€</span>
                                <input type="number" step="0.01" class="form-control" id="budgetMin" name="budgetMin" value="<?php echo htmlspecialchars($form['budgetMin']); ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="budgetMax" class="form-label fw-semibold">Budget maximum</label>
                            <div class="input-group">
                                <span class="input-group-text">€</span>
                                <input type="number" step="0.01" class="form-control" id="budgetMax" name="budgetMax" value="<?php echo htmlspecialchars($form['budgetMax']); ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-5">
                        <div class="col-md-6">
                            <label for="datePublication" class="form-label fw-semibold">Date de publication</label>
                            <input type="date" class="form-control" id="datePublication" name="datePublication" value="<?php echo htmlspecialchars($form['datePublication']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="dateLimite" class="form-label fw-semibold">Date limite</label>
                            <input type="date" class="form-control" id="dateLimite" name="dateLimite" value="<?php echo htmlspecialchars($form['dateLimite']); ?>" required>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">Créer l'offre</button>
                        <a class="btn btn-outline-secondary btn-lg" href="index.php">Annuler</a>
                    </div>
                </form>
            </div>

            <div class="col-lg-4">
                <div class="bg-light rounded-3 p-4 sticky-top" style="top: 20px;">
                    <h5 class="fw-semibold mb-3">💡 Conseils</h5>
                    <ul class="small text-muted list-unstyled">
                        <li class="mb-2"><strong>Titre clair:</strong> Décrivez le type de contenu en quelques mots.</li>
                        <li class="mb-2"><strong>Budget réaliste:</strong> Consultez les prix du marché pour votre type d'offre.</li>
                        <li class="mb-2"><strong>Dates cohérentes:</strong> Assurez-vous que la date limite est après la date de publication.</li>
                        <li class="mb-2"><strong>Description détaillée:</strong> Plus vous décrivez, mieux les créateurs comprendront.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
