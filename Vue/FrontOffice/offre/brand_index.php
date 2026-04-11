<?php
session_start();
// Simulate logged-in user (remove this when login system is ready)
if (!isset($_SESSION['utilisateur'])) {
    $_SESSION['utilisateur']['id'] = 1;  // Test with marque ID 1
}

require_once __DIR__ . '/../../../Controleur/offreC.php';
require_once __DIR__ . '/../../../Controleur/utilisateurC.php';

$brandId = $_SESSION['utilisateur']['id'];
$controller = new OffreC();
$userController = new UtilisateurC();
$offres = [];
$error = null;

function renderCreatorLabel($userController, $creatorId) {
    if (!$creatorId) {
        return 'Non défini';
    }
    $creator = $userController->getUserByIdAndRole($creatorId, 'createur');
    if ($creator) {
        return htmlspecialchars($creator->getNom() . ' (#' . $creator->getId() . ')');
    }
    return 'ID ' . htmlspecialchars($creatorId);
}

if ($brandId !== null) {
    $offres = $controller->getOffresByMarque($brandId);
} else {
    $error = 'Identifiant de marque manquant.';
}

$message = isset($_GET['message']) ? htmlspecialchars($_GET['message']) : null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes offres - Cre8Connect</title>
    <link rel="stylesheet" href="../css/frontoffice.css">
    <link rel="stylesheet" href="offre.css">
</head>
<body>
    <div class="container py-5">
        <div class="row mb-5">
            <div class="col-lg-8">
                <h1 class="display-5 fw-bold mb-2 gradient-title">Mes offres</h1>
                <p class="lead text-muted">Gérez vos offres de collaboration en tant que marque.</p>
            </div>
            <div class="col-lg-4 d-flex align-items-center justify-content-end">
                <a class="btn btn-primary btn-lg" href="brand_create.php">+ Créer une offre</a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($brandId !== null && count($offres) > 0): ?>
            <div class="table-responsive bg-white rounded-3 shadow-sm">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="fs-6 fw-semibold">Titre</th>
                            <th class="fs-6 fw-semibold">Créateur ciblé</th>
                            <th class="fs-6 fw-semibold">Objectif</th>
                            <th class="fs-6 fw-semibold text-center">Budget</th>
                            <th class="fs-6 fw-semibold text-center" style="width: 90px;">Publication</th>
                            <th class="fs-6 fw-semibold text-center" style="width: 90px;">Limite</th>
                            <th class="fs-6 fw-semibold text-center">Statut</th>
                            <th class="fs-6 fw-semibold text-center" style="width: 200px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($offres as $offre): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?php echo htmlspecialchars($offre->getTitre()); ?></div>
                            </td>
                            <td><?php echo renderCreatorLabel($userController, $offre->getIdCreateurCible()); ?></td>
                            <td><?php echo htmlspecialchars(substr($offre->getObjectif(), 0, 50)); ?></td>
                            <td class="text-center"><span class="badge bg-light text-dark"><?php echo htmlspecialchars($offre->getBudgetMin()); ?> - <?php echo htmlspecialchars($offre->getBudgetMax()); ?></span></td>
                            <td class="text-center text-muted small"><?php echo htmlspecialchars($offre->getDatePublication()); ?></td>
                            <td class="text-center text-muted small"><?php echo htmlspecialchars($offre->getDateLimite()); ?></td>
                            <td class="text-center"><span class="badge bg-info text-white"><?php echo htmlspecialchars($offre->getStatutOffre()); ?></span></td>
                            <td class="text-center" style="white-space: nowrap;">
                                <a class="btn btn-sm btn-outline-primary" href="brand_details.php?idOffre=<?php echo $offre->getIdOffre(); ?>">Détails</a>
                                <a class="btn btn-sm btn-outline-secondary" href="brand_edit.php?idOffre=<?php echo $offre->getIdOffre(); ?>">Modifier</a>
                                <form class="d-inline-block" method="post" action="brand_delete.php" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette offre ?');">
                                    <input type="hidden" name="idOffre" value="<?php echo $offre->getIdOffre(); ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($brandId !== null): ?>
            <div class="bg-white rounded-3 p-5 text-center shadow-sm">
                <svg class="mb-4" width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="text-muted">
                    <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9a1 1 0 11-2 0 1 1 0 012 0z"/>
                </svg>
                <h3 class="mb-2">Aucune offre créée</h3>
                <p class="text-muted mb-4">Commencez par créer votre première offre pour vos collaborateurs.</p>
                <a class="btn btn-primary" href="brand_create.php">Créer une offre</a>
            </div>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
</body>
</html>