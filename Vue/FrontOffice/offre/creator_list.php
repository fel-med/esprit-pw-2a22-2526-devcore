<?php
session_start();
// Simulate logged-in creator (remove this when login system is ready)
if (!isset($_SESSION['utilisateur'])) {
    $_SESSION['utilisateur']['id'] = 2;  // Test with creator ID 2
    $_SESSION['utilisateur']['role'] = 'createur';
}

require_once __DIR__ . '/../../../Controleur/offreC.php';

$controller = new OffreC();
$creatorId = $_SESSION['utilisateur']['id'];
$offres = [];
$error = null;

// Handle search/filter
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : null;
$budgetMin = isset($_GET['budgetMin']) && is_numeric($_GET['budgetMin']) ? floatval($_GET['budgetMin']) : null;
$budgetMax = isset($_GET['budgetMax']) && is_numeric($_GET['budgetMax']) ? floatval($_GET['budgetMax']) : null;
$dateLimite = isset($_GET['dateLimite']) && !empty($_GET['dateLimite']) ? $_GET['dateLimite'] : null;

try {
    $offres = $controller->searchOffers($creatorId, $keyword, $budgetMin, $budgetMax, $dateLimite);
} catch (Exception $e) {
    $error = 'Erreur lors de la recherche des offres.';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offres disponibles - Cre8Connect</title>
    <link rel="stylesheet" href="../css/frontoffice.css">
    <link rel="stylesheet" href="offre.css">
</head>
<body>
    <div class="container py-5">
        <div class="row mb-5">
            <div class="col-lg-8">
                <h1 class="display-5 fw-bold mb-2 gradient-title">Offres disponibles</h1>
                <p class="lead text-muted">Découvrez les offres de collaboration publiées par les marques.</p>
            </div>
        </div>

        <!-- Search/Filter Form -->
        <div class="bg-white rounded-3 p-4 shadow-sm mb-5">
            <h3 class="fw-semibold mb-4">Rechercher et filtrer</h3>
            <form method="get" action="creator_list.php" class="row g-3">
                <div class="col-md-4">
                    <label for="keyword" class="form-label">Mot-clé (titre, objectif, description)</label>
                    <input type="text" class="form-control" id="keyword" name="keyword" value="<?php echo htmlspecialchars($keyword ?? ''); ?>" placeholder="Ex: vidéo, photo...">
                </div>
                <div class="col-md-2">
                    <label for="budgetMin" class="form-label">Budget min (€)</label>
                    <input type="number" class="form-control" id="budgetMin" name="budgetMin" value="<?php echo htmlspecialchars($budgetMin ?? ''); ?>" min="0">
                </div>
                <div class="col-md-2">
                    <label for="budgetMax" class="form-label">Budget max (€)</label>
                    <input type="number" class="form-control" id="budgetMax" name="budgetMax" value="<?php echo htmlspecialchars($budgetMax ?? ''); ?>" min="0">
                </div>
                <div class="col-md-2">
                    <label for="dateLimite" class="form-label">Date limite (min)</label>
                    <input type="date" class="form-control" id="dateLimite" name="dateLimite" value="<?php echo htmlspecialchars($dateLimite ?? ''); ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Rechercher</button>
                </div>
            </form>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (count($offres) > 0): ?>
            <div class="row g-4">
                <?php foreach ($offres as $offre): ?>
                    <div class="col-lg-6 col-xl-4">
                        <div class="bg-white rounded-3 p-4 shadow-sm h-100 d-flex flex-column">
                            <h5 class="fw-semibold mb-2"><?php echo htmlspecialchars($offre->getTitre()); ?></h5>
                            <p class="text-muted mb-3"><?php echo htmlspecialchars(substr($offre->getObjectif(), 0, 100)); ?>...</p>
                            <div class="mb-3">
                                <span class="badge bg-light text-dark me-2">€<?php echo htmlspecialchars($offre->getBudgetMin()); ?> - €<?php echo htmlspecialchars($offre->getBudgetMax()); ?></span>
                                <span class="badge bg-info text-white"><?php echo htmlspecialchars($offre->getStatutOffre()); ?></span>
                            </div>
                            <p class="text-muted small mb-3">Limite: <?php echo htmlspecialchars($offre->getDateLimite()); ?></p>
                            <div class="mt-auto">
                                <a class="btn btn-primary w-100" href="creator_details.php?idOffre=<?php echo $offre->getIdOffre(); ?>&idCreateur=<?php echo $creatorId; ?>">Voir les détails</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-3 p-5 text-center shadow-sm">
                <svg class="mb-4" width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="text-muted">
                    <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <h3 class="mb-2">Aucune offre trouvée</h3>
                <p class="text-muted mb-4">Ajustez vos critères de recherche pour voir plus d'offres.</p>
            </div>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
</body>
</html>