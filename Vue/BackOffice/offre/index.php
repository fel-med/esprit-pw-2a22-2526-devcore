<?php
require_once __DIR__ . '/../../../Controleur/offreC.php';
require_once __DIR__ . '/../../../Controleur/utilisateurC.php';

$offreController = new OffreC();
$userController = new UtilisateurC();

$message = '';
$searchKeyword = trim($_GET['keyword'] ?? '');
$searchStatut = trim($_GET['statut'] ?? '');
$searchMarque = trim($_GET['marque'] ?? '');
$searchCreateur = trim($_GET['createur'] ?? '');
$searchBudgetMin = trim($_GET['budgetMin'] ?? '');
$searchBudgetMax = trim($_GET['budgetMax'] ?? '');
$searchDateLimite = trim($_GET['dateLimite'] ?? '');
$filterValues = [
    $searchKeyword,
    $searchStatut,
    $searchMarque,
    $searchCreateur,
    $searchBudgetMin,
    $searchBudgetMax,
    $searchDateLimite
];
$activeFilterCount = count(array_filter($filterValues, static fn($value) => $value !== '' && $value !== null));
$hasActiveFilters = $activeFilterCount > 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deleteOffre'], $_POST['idOffreToDelete']) && is_numeric($_POST['idOffreToDelete'])) {
    $deleteId = intval($_POST['idOffreToDelete']);
    $offerToDelete = $offreController->getOffreByIdAdmin($deleteId);
    if ($offerToDelete) {
        $offreController->deleteOffre($deleteId, $offerToDelete->getIdMarque());
        $query = $_GET;
        $query['deleted'] = 1;
        unset($query['idOffre']);
        header('Location: index.php?' . http_build_query($query));
        exit;
    }
    $message = 'Offer not found or impossible to delete.';
}

$allOffres = $offreController->getAllOffres();

$marqueOptions = [];
$createurOptions = [];
foreach ($allOffres as $offreOption) {
    if ($offreOption->getIdMarque()) {
        $marqueOptions[$offreOption->getIdMarque()] = userLabelText($userController, $offreOption->getIdMarque(), 'marque');
    }
    if ($offreOption->getIdCreateurCible()) {
        $createurOptions[$offreOption->getIdCreateurCible()] = userLabelText($userController, $offreOption->getIdCreateurCible(), 'createur');
    }
}

$offres = $offreController->searchOffresAdmin(
    $searchKeyword ?: null,
    $searchStatut ?: null,
    is_numeric($searchMarque) ? intval($searchMarque) : null,
    is_numeric($searchCreateur) ? intval($searchCreateur) : null,
    $searchBudgetMin !== '' ? $searchBudgetMin : null,
    $searchBudgetMax !== '' ? $searchBudgetMax : null,
    $searchDateLimite ?: null
);

$selectedOffer = null;
$candidatures = [];

if (isset($_GET['idOffre']) && is_numeric($_GET['idOffre'])) {
    $offerId = intval($_GET['idOffre']);
    $selectedOffer = $offreController->getOffreByIdAdmin($offerId);
    if ($selectedOffer) {
        $candidatures = $offreController->getCandidaturesByOffre($offerId);
    }
}

function translateOfferStatus($status) {
    $normalized = strtolower((string)$status);

    return match ($normalized) {
        'publiee' => 'Published',
        'active' => 'Active',
        'fermee', 'closed' => 'Closed',
        default => ucwords(str_replace(['_', '-'], ' ', (string)$status)),
    };
}

function userLabel($controller, $id, $role) {
    if (empty($id)) {
        return '<span class="status-pill">Not set</span>';
    }
    $user = $controller->getUserByIdAndRole($id, $role);
    if ($user) {
        return htmlspecialchars($user->getNom() . ' (#' . $user->getId() . ')');
    }
    return 'ID #' . intval($id);
}

function userLabelText($controller, $id, $role) {
    if (empty($id)) {
        return 'Not set';
    }
    $user = $controller->getUserByIdAndRole($id, $role);
    if ($user) {
        return $user->getNom() . ' (#' . $user->getId() . ')';
    }
    return 'ID #' . intval($id);
}

function statutBadge($statut) {
    return '<span class="badge-status ' . htmlspecialchars((string)$statut) . '">' . htmlspecialchars(translateOfferStatus($statut)) . '</span>';
}

function formatPrice($value) {
    return number_format((float)$value, 2, ',', ' ') . ' &euro;';
}

function formatDate($date) {
    return $date ? htmlspecialchars($date) : '-';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Back Office - Offer Management</title>
    <link rel="stylesheet" href="../css/backoffice.css">
    <link rel="stylesheet" href="offre-admin.css">
</head>
<body>
    <div class="admin-shell">
        <header class="admin-header">
            <h1>Offer Administration</h1>
            <p>Manage targeted offers, brands, creators, and applications.</p>
        </header>

        <?php if (!empty($message)): ?>
            <div class="admin-flash success"><?php echo htmlspecialchars($message); ?></div>
        <?php elseif (isset($_GET['deleted'])): ?>
            <div class="admin-flash success">Offer deleted successfully.</div>
        <?php endif; ?>

        <details class="search-panel" <?php echo $hasActiveFilters ? 'open' : ''; ?>>
            <summary class="search-panel-summary">
                <span class="search-panel-heading">
                    <span class="search-panel-title">Search filters</span>
                    <span class="search-panel-subtitle">
                        <?php echo $hasActiveFilters ? $activeFilterCount . ' filter' . ($activeFilterCount > 1 ? 's' : '') . ' applied to the current list.' : 'Refine the list by status, brand, creator, budget, or deadline.'; ?>
                    </span>
                </span>
                <span class="search-panel-status">
                    <?php if ($hasActiveFilters): ?>
                        <span class="search-panel-badge"><?php echo $activeFilterCount; ?> active</span>
                    <?php endif; ?>
                    <span class="search-panel-toggle">
                        <span class="search-panel-toggle-label search-panel-toggle-label-closed">Open filters</span>
                        <span class="search-panel-toggle-label search-panel-toggle-label-open">Close filters</span>
                        <span class="search-panel-toggle-icon" aria-hidden="true"></span>
                    </span>
                </span>
            </summary>
            <form method="get" class="search-form">
                <div class="search-grid">
                    <div class="search-group">
                        <label for="keyword">Search</label>
                        <input id="keyword" name="keyword" type="search" value="<?php echo htmlspecialchars($searchKeyword); ?>" placeholder="Title, description, objectives...">
                    </div>
                    <div class="search-group">
                        <label for="statut">Status</label>
                        <select id="statut" name="statut">
                            <option value="">All</option>
                            <option value="publiee" <?php echo $searchStatut === 'publiee' ? 'selected' : ''; ?>>Published</option>
                            <option value="active" <?php echo $searchStatut === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="fermee" <?php echo $searchStatut === 'fermee' ? 'selected' : ''; ?>>Closed</option>
                            <option value="closed" <?php echo $searchStatut === 'closed' ? 'selected' : ''; ?>>Closed</option>
                        </select>
                    </div>
                    <div class="search-group">
                        <label for="marque">Brand</label>
                        <select id="marque" name="marque">
                            <option value="">All</option>
                            <?php foreach ($marqueOptions as $marqueId => $marqueLabel): ?>
                                <option value="<?php echo intval($marqueId); ?>" <?php echo $searchMarque === (string)$marqueId ? 'selected' : ''; ?>><?php echo htmlspecialchars($marqueLabel); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="search-group">
                        <label for="createur">Target creator</label>
                        <select id="createur" name="createur">
                            <option value="">All</option>
                            <?php foreach ($createurOptions as $createurId => $createurLabel): ?>
                                <option value="<?php echo intval($createurId); ?>" <?php echo $searchCreateur === (string)$createurId ? 'selected' : ''; ?>><?php echo htmlspecialchars($createurLabel); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="search-group">
                        <label for="budgetMin">Min budget</label>
                        <input id="budgetMin" name="budgetMin" type="number" min="0" step="0.01" value="<?php echo htmlspecialchars($searchBudgetMin); ?>" placeholder="0">
                    </div>
                    <div class="search-group">
                        <label for="budgetMax">Max budget</label>
                        <input id="budgetMax" name="budgetMax" type="number" min="0" step="0.01" value="<?php echo htmlspecialchars($searchBudgetMax); ?>" placeholder="0">
                    </div>
                    <div class="search-group">
                        <label for="dateLimite">Deadline</label>
                        <input id="dateLimite" name="dateLimite" type="date" value="<?php echo htmlspecialchars($searchDateLimite); ?>">
                    </div>
                </div>
                <div class="search-actions">
                    <button type="submit">Apply</button>
                    <a class="clear-link" href="index.php">Reset</a>
                </div>
            </form>
        </details>

        <div class="admin-summary">
            <div class="admin-card">
                <h3>Displayed offers</h3>
                <p><?php echo count($offres); ?></p>
            </div>
            <div class="admin-card">
                <h3>Targeted offers</h3>
                <p><?php echo count(array_filter($offres, fn($offre) => $offre->getIdCreateurCible())); ?></p>
            </div>
            <div class="admin-card">
                <h3>Selected applications</h3>
                <p><?php echo $selectedOffer ? count($candidatures) : '-'; ?></p>
            </div>
        </div>

        <div class="admin-layout">
            <section class="admin-panel admin-table-panel">
                <div class="admin-panel-header">
                    <h2>Offer list</h2>
                </div>
                <div class="admin-panel-body">
                    <div class="admin-table-wrapper">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Offer</th>
                                    <th>Brand</th>
                                    <th>Target creator</th>
                                    <th>Budget</th>
                                    <th>Published</th>
                                    <th>Status</th>
                                    <th>Applications</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($offres) === 0): ?>
                                    <tr>
                                        <td colspan="8" style="padding: 1.5rem; text-align: center; color: #94a3b8;">No offers available.</td>
                                    </tr>
                                <?php endif; ?>
                                <?php foreach ($offres as $offre): ?>
                                    <tr<?php echo $selectedOffer && $selectedOffer->getIdOffre() === $offre->getIdOffre() ? ' class="is-selected"' : ''; ?>>
                                        <td>
                                            <strong><?php echo htmlspecialchars($offre->getTitre()); ?></strong>
                                            <span class="row-label"><?php echo htmlspecialchars(substr($offre->getDescription(), 0, 70)); ?>...</span>
                                        </td>
                                        <td><?php echo userLabel($userController, $offre->getIdMarque(), 'marque'); ?></td>
                                        <td><?php echo userLabel($userController, $offre->getIdCreateurCible(), 'createur'); ?></td>
                                        <td><?php echo formatPrice($offre->getBudgetMin()); ?> - <?php echo formatPrice($offre->getBudgetMax()); ?></td>
                                        <td><?php echo formatDate($offre->getDatePublication()); ?></td>
                                        <td><?php echo statutBadge($offre->getStatutOffre()); ?></td>
                                        <td><?php echo $offreController->getCandidatureCountByOffre($offre->getIdOffre()); ?></td>
                                        <td class="admin-actions">
                                            <div class="admin-actions-stack">
                                                <a href="index.php?idOffre=<?php echo intval($offre->getIdOffre()); ?>&<?php echo http_build_query(['keyword' => $searchKeyword, 'statut' => $searchStatut, 'marque' => $searchMarque, 'createur' => $searchCreateur, 'budgetMin' => $searchBudgetMin, 'budgetMax' => $searchBudgetMax, 'dateLimite' => $searchDateLimite]); ?>">Select</a>
                                                <form method="post" class="inline-delete-form" onsubmit="return confirm('Confirm deletion of this offer?');">
                                                    <input type="hidden" name="idOffreToDelete" value="<?php echo intval($offre->getIdOffre()); ?>">
                                                    <button type="submit" name="deleteOffre" class="delete-btn">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

        </div>
    </div>
</body>
</html>
