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
$idOffre = isset($_GET['idOffre']) && is_numeric($_GET['idOffre']) ? intval($_GET['idOffre']) : null;
$offre = null;
$error = null;
$creatorLabel = 'Non défini';

if ($idOffre !== null) {
    $offre = $controller->getOffreById($idOffre, $brandId);
    if ($offre) {
        $creatorId = $offre->getIdCreateurCible();
        if ($creatorId) {
            $creator = $userController->getUserByIdAndRole($creatorId, 'createur');
            $creatorLabel = $creator ? htmlspecialchars($creator->getNom() . ' (#' . $creator->getId() . ')') : 'ID ' . htmlspecialchars($creatorId);
        }
    }
    if (!$offre) {
        $error = 'Offre introuvable ou accès refusé.';
    }
} else {
    $error = 'Paramètres invalides pour afficher l\'offre.';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails de l'offre - Cre8Connect</title>
    <link rel="stylesheet" href="../css/frontoffice.css">
    <link rel="stylesheet" href="offre.css">
</head>
<body>
    <div class="container py-5">
        <?php if ($error): ?>
            <div class="row mb-5">
                <div class="col-lg-8 mx-auto">
                    <div class="bg-danger-subtle rounded-3 p-5 text-center">
                        <h2 class="text-danger">Erreur</h2>
                        <p class="text-muted mb-4"><?php echo htmlspecialchars($error); ?></p>
                        <a class="btn btn-primary" href="brand_index.php">Retour à mes offres</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="row mb-5 align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-5 fw-bold mb-2 gradient-title"><?php echo htmlspecialchars($offre->getTitre()); ?></h1>
                    <div class="d-flex gap-3 flex-wrap">
                        <span class="badge bg-info text-white fs-6"><?php echo htmlspecialchars($offre->getStatutOffre()); ?></span>
                        <span class="text-muted">Créée le <?php echo htmlspecialchars($offre->getDatePublication()); ?></span>
                    </div>
                </div>
            </div>

            <div class="row g-5">
                <div class="col-lg-8">
                    <div class="bg-white rounded-3 p-5 shadow-sm mb-5">
                        <h3 class="fw-semibold mb-3">Description</h3>
                        <p class="lead text-muted"><?php echo nl2br(htmlspecialchars($offre->getDescription())); ?></p>
                    </div>

                    <div class="bg-white rounded-3 p-5 shadow-sm">
                        <h3 class="fw-semibold mb-4">Détails de l'offre</h3>
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="d-flex align-items-start">
                                    <div class="flex-shrink-0">
                                        <div class="flex-shrink-0 bg-light rounded-circle p-3" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                                            🎯
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="fw-semibold mb-1">Objectif</h6>
                                        <p class="text-muted mb-0"><?php echo htmlspecialchars($offre->getObjectif()); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-start">
                                    <div class="flex-shrink-0">
                                        <div class="flex-shrink-0 bg-light rounded-circle p-3" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                                            💰
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="fw-semibold mb-1">Budget</h6>
                                        <p class="text-muted mb-0">€<?php echo htmlspecialchars($offre->getBudgetMin()); ?> - €<?php echo htmlspecialchars($offre->getBudgetMax()); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-start">
                                    <div class="flex-shrink-0">
                                        <div class="flex-shrink-0 bg-light rounded-circle p-3" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                                            📅
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="fw-semibold mb-1">Publiée</h6>
                                        <p class="text-muted mb-0"><?php echo htmlspecialchars($offre->getDatePublication()); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-start">
                                    <div class="flex-shrink-0">
                                        <div class="flex-shrink-0 bg-light rounded-circle p-3" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                                            ⏰
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="fw-semibold mb-1">Limite</h6>
                                        <p class="text-muted mb-0"><?php echo htmlspecialchars($offre->getDateLimite()); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-start">
                                    <div class="flex-shrink-0">
                                        <div class="flex-shrink-0 bg-light rounded-circle p-3" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                                            👤
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="fw-semibold mb-1">Créateur ciblé</h6>
                                        <p class="text-muted mb-0"><?php echo $creatorLabel; ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="bg-white rounded-3 p-4 shadow-sm sticky-top" style="top: 20px;">
                        <h5 class="fw-semibold mb-4">Actions</h5>
                        <div class="d-grid gap-2">
                            <a class="btn btn-primary" href="brand_edit.php?idOffre=<?php echo $offre->getIdOffre(); ?>">✏️ Modifier</a>
                            <form method="post" action="brand_delete.php" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette offre ?');">
                                <input type="hidden" name="idOffre" value="<?php echo $offre->getIdOffre(); ?>">
                                <button type="submit" class="btn btn-outline-danger w-100">🗑️ Supprimer</button>
                            </form>
                            <a class="btn btn-outline-secondary" href="brand_index.php">← Retour</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
