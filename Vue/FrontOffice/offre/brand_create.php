<?php
session_start();
// Simulate logged-in user (remove this when login system is ready)
if (!isset($_SESSION['utilisateur'])) {
    $_SESSION['utilisateur']['id'] = 1;  // Test with marque ID 1
}

require_once __DIR__ . '/../../../Controleur/offreC.php';
require_once __DIR__ . '/../../../Controleur/utilisateurC.php';

$controller = new OffreC();
$userController = new UtilisateurC();
$brandId = $_SESSION['utilisateur']['id'];
$errors = [];
$form = [
    'idCreateurCible' => '',
    'titre' => '',
    'description' => '',
    'objectif' => '',
    'budgetMin' => '',
    'budgetMax' => '',
    'datePublication' => '',
    'dateLimite' => ''
];

if (isset($_GET['validateCreatorId']) && isset($_GET['id'])) {
    header('Content-Type: application/json');
    $creatorId = intval($_GET['id']);
    $creator = $userController->getUserByIdAndRole($creatorId, 'createur');
    echo json_encode(['valid' => (bool)$creator, 'message' => $creator ? 'Créateur trouvé.' : 'ID invalide ou rôle non créateur.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = [
        'idCreateurCible' => $_POST['idCreateurCible'] ?? '',
        'titre' => $_POST['titre'] ?? '',
        'description' => $_POST['description'] ?? '',
        'objectif' => $_POST['objectif'] ?? '',
        'budgetMin' => $_POST['budgetMin'] ?? '',
        'budgetMax' => $_POST['budgetMax'] ?? '',
        'datePublication' => $_POST['datePublication'] ?? '',
        'dateLimite' => $_POST['dateLimite'] ?? ''
    ];

    $errors = $controller->validateOffreData($form);
    $creatorId = intval($form['idCreateurCible']);
    $creator = $userController->getUserByIdAndRole($creatorId, 'createur');
    if (!$creator) {
        $errors[] = 'Le créateur ciblé doit exister et avoir le rôle créateur.';
    }

    if (empty($errors)) {
        $offre = new Offre(
            null,
            $brandId,
            intval($form['idCreateurCible']),
            trim($form['titre']),
            trim($form['description']),
            trim($form['objectif']),
            floatval($form['budgetMin']),
            floatval($form['budgetMax']),
            trim($form['datePublication']),
            trim($form['dateLimite']),
            'publiee'
        );

        if ($controller->createOffre($offre)) {
            header('Location: brand_index.php?message=' . urlencode('Offre créée avec succès.'));
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

                <form method="post" action="brand_create.php" class="needs-validation">
                    <input type="hidden" name="idMarque" value="<?php echo htmlspecialchars($brandId); ?>">

                    <div class="mb-4">
                        <label for="titre" class="form-label fw-semibold">Titre de l'offre</label>
                        <input type="text" class="form-control form-control-lg" id="titre" name="titre" value="<?php echo htmlspecialchars($form['titre']); ?>" placeholder="Ex: Créateur de contenu pour TikTok" required>
                    </div>

                    <div class="mb-4">
                        <label for="idCreateurCible" class="form-label fw-semibold">Créateur ciblé (ID)</label>
                        <input type="number" class="form-control" id="idCreateurCible" name="idCreateurCible" value="<?php echo htmlspecialchars($form['idCreateurCible']); ?>" placeholder="ID du créateur ciblé" required>
                        <div id="creatorIdFeedback" class="invalid-feedback"></div>
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
                        <a class="btn btn-outline-secondary btn-lg" href="brand_index.php">Annuler</a>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const creatorInput = document.getElementById('idCreateurCible');
            const feedback = document.getElementById('creatorIdFeedback');
            const submitButton = document.querySelector('button[type="submit"]');
            let creatorValid = false;

            function updateValidation(valid, message) {
                if (valid) {
                    creatorInput.classList.remove('is-invalid');
                    creatorInput.classList.add('is-valid');
                    feedback.textContent = '';
                    submitButton.disabled = false;
                } else {
                    creatorInput.classList.add('is-invalid');
                    creatorInput.classList.remove('is-valid');
                    feedback.textContent = message;
                    submitButton.disabled = true;
                }
                creatorValid = valid;
            }

            function validateCreator() {
                const value = creatorInput.value.trim();
                if (!value || !/^[1-9]\d*$/.test(value)) {
                    updateValidation(false, 'Entrez un ID de créateur valide.');
                    return;
                }

                const url = new URL(window.location.href);
                url.search = '';
                url.searchParams.set('validateCreatorId', '1');
                url.searchParams.set('id', value);

                fetch(url.toString(), { cache: 'no-store' })
                    .then(response => response.json())
                    .then(data => {
                        if (data.valid) {
                            updateValidation(true, '');
                        } else {
                            updateValidation(false, data.message || 'Créateur introuvable.');
                        }
                    })
                    .catch(() => {
                        updateValidation(false, 'Impossible de vérifier cet ID actuellement.');
                    });
            }

            creatorInput.addEventListener('blur', validateCreator);
            creatorInput.addEventListener('input', function() {
                creatorInput.classList.remove('is-valid', 'is-invalid');
                feedback.textContent = '';
                submitButton.disabled = false;
                creatorValid = false;
            });

            const form = creatorInput.closest('form');
            if (form) {
                form.addEventListener('submit', function(event) {
                    if (!creatorValid) {
                        event.preventDefault();
                        updateValidation(false, 'Veuillez saisir un ID de créateur valide avant d’envoyer.');
                        creatorInput.focus();
                    }
                });
            }
        });
    </script>
</body>
</html>
