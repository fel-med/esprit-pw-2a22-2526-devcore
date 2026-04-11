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
    $message = 'Offre introuvable ou impossible à supprimer.';
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

function userLabel($controller, $id, $role) {
    if (empty($id)) {
        return '<span class="status-pill">ID non défini</span>';
    }
    $user = $controller->getUserByIdAndRole($id, $role);
    if ($user) {
        return htmlspecialchars($user->getNom() . ' (#' . $user->getId() . ')');
    }
    return 'ID #' . intval($id);
}

function userLabelText($controller, $id, $role) {
    if (empty($id)) {
        return 'ID non défini';
    }
    $user = $controller->getUserByIdAndRole($id, $role);
    if ($user) {
        return $user->getNom() . ' (#' . $user->getId() . ')';
    }
    return 'ID #' . intval($id);
}

function statutBadge($statut) {
    $class = 'status-pill';
    if ($statut === 'publiee') {
        $class .= ' publiee';
    } elseif ($statut === 'active') {
        $class .= ' active';
    } elseif ($statut === 'fermee' || $statut === 'closed') {
        $class .= ' closed';
    }
    return '<span class="badge-status ' . htmlspecialchars($statut) . '">' . htmlspecialchars($statut) . '</span>';
}

function formatPrice($value) {
    return number_format((float)$value, 2, ',', ' ') . ' €';
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
    <title>BackOffice - Offre Management</title>
    <link rel="stylesheet" href="../css/backoffice.css">
    <link rel="stylesheet" href="offre-admin.css">
</head>
<body>
    <div class="admin-shell">
        <header class="admin-header">
            <h1>Offre Administration</h1>
            <p>Gestion des offres ciblées, des marques, des créateurs et des candidatures.</p>
        </header>

        <?php if (!empty($message)): ?>
            <div class="admin-flash success"><?php echo htmlspecialchars($message); ?></div>
        <?php elseif (isset($_GET['deleted'])): ?>
            <div class="admin-flash success">Offre supprimée avec succès.</div>
        <?php endif; ?>

        <details class="search-panel" <?php echo $hasActiveFilters ? 'open' : ''; ?>>
            <summary class="search-panel-summary">
                <span class="search-panel-heading">
                    <span class="search-panel-title">Filtres de recherche</span>
                    <span class="search-panel-subtitle">
                        <?php echo $hasActiveFilters ? $activeFilterCount . ' critère' . ($activeFilterCount > 1 ? 's' : '') . ' appliqué' . ($activeFilterCount > 1 ? 's' : '') . ' à la liste actuelle.' : 'Affinez la liste par statut, marque, créateur, budget ou date limite.'; ?>
                    </span>
                </span>
                <span class="search-panel-status">
                    <?php if ($hasActiveFilters): ?>
                        <span class="search-panel-badge"><?php echo $activeFilterCount; ?> actif<?php echo $activeFilterCount > 1 ? 's' : ''; ?></span>
                    <?php endif; ?>
                    <span class="search-panel-toggle">
                        <span class="search-panel-toggle-label search-panel-toggle-label-closed">Ouvrir les filtres</span>
                        <span class="search-panel-toggle-label search-panel-toggle-label-open">Fermer les filtres</span>
                        <span class="search-panel-toggle-icon" aria-hidden="true"></span>
                    </span>
                </span>
            </summary>
            <form method="get" class="search-form">
                <div class="search-grid">
                    <div class="search-group">
                        <label for="keyword">Rechercher</label>
                        <input id="keyword" name="keyword" type="search" value="<?php echo htmlspecialchars($searchKeyword); ?>" placeholder="Titre, description, objectifs...">
                    </div>
                    <div class="search-group">
                        <label for="statut">Statut</label>
                        <select id="statut" name="statut">
                            <option value="">Tous</option>
                            <option value="publiee" <?php echo $searchStatut === 'publiee' ? 'selected' : ''; ?>>Publiée</option>
                            <option value="active" <?php echo $searchStatut === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="fermee" <?php echo $searchStatut === 'fermee' ? 'selected' : ''; ?>>Fermée</option>
                            <option value="closed" <?php echo $searchStatut === 'closed' ? 'selected' : ''; ?>>Closed</option>
                        </select>
                    </div>
                    <div class="search-group">
                        <label for="marque">Marque</label>
                        <select id="marque" name="marque">
                            <option value="">Toutes</option>
                            <?php foreach ($marqueOptions as $marqueId => $marqueLabel): ?>
                                <option value="<?php echo intval($marqueId); ?>" <?php echo $searchMarque === (string)$marqueId ? 'selected' : ''; ?>><?php echo htmlspecialchars($marqueLabel); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="search-group">
                        <label for="createur">Créateur cible</label>
                        <select id="createur" name="createur">
                            <option value="">Tous</option>
                            <?php foreach ($createurOptions as $createurId => $createurLabel): ?>
                                <option value="<?php echo intval($createurId); ?>" <?php echo $searchCreateur === (string)$createurId ? 'selected' : ''; ?>><?php echo htmlspecialchars($createurLabel); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="search-group">
                        <label for="budgetMin">Budget min</label>
                        <input id="budgetMin" name="budgetMin" type="number" min="0" step="0.01" value="<?php echo htmlspecialchars($searchBudgetMin); ?>" placeholder="0">
                    </div>
                    <div class="search-group">
                        <label for="budgetMax">Budget max</label>
                        <input id="budgetMax" name="budgetMax" type="number" min="0" step="0.01" value="<?php echo htmlspecialchars($searchBudgetMax); ?>" placeholder="0">
                    </div>
                    <div class="search-group">
                        <label for="dateLimite">Date limite</label>
                        <input id="dateLimite" name="dateLimite" type="date" value="<?php echo htmlspecialchars($searchDateLimite); ?>">
                    </div>
                </div>
                <div class="search-actions">
                    <button type="submit">Appliquer</button>
                    <a class="clear-link" href="index.php">Réinitialiser</a>
                </div>
            </form>
        </details>

        <div class="admin-summary">
            <div class="admin-card">
                <h3>Offres affichées</h3>
                <p><?php echo count($offres); ?></p>
            </div>
            <div class="admin-card">
                <h3>Offres ciblées</h3>
                <p><?php echo count(array_filter($offres, fn($offre) => $offre->getIdCreateurCible())); ?></p>
            </div>
            <div class="admin-card">
                <h3>Candidatures sélectionnées</h3>
                <p><?php echo $selectedOffer ? count($candidatures) : '-'; ?></p>
            </div>
        </div>

        <div class="admin-layout">
            <section class="admin-panel admin-table-panel">
                <div class="admin-panel-header">
                    <h2>Liste des offres</h2>
                </div>
                <div class="admin-panel-body">
                    <div class="admin-table-wrapper">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Offre</th>
                                    <th>Marque</th>
                                    <th>Créateur cible</th>
                                    <th>Budget</th>
                                    <th>Publication</th>
                                    <th>Statut</th>
                                    <th>Candidatures</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($offres) === 0): ?>
                                    <tr>
                                        <td colspan="8" style="padding: 1.5rem; text-align: center; color: #94a3b8;">Aucune offre disponible.</td>
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
                                                <a href="index.php?idOffre=<?php echo intval($offre->getIdOffre()); ?>&<?php echo http_build_query(['keyword' => $searchKeyword, 'statut' => $searchStatut, 'marque' => $searchMarque, 'createur' => $searchCreateur, 'budgetMin' => $searchBudgetMin, 'budgetMax' => $searchBudgetMax, 'dateLimite' => $searchDateLimite]); ?>">Sélectionner</a>
                                                <form method="post" class="inline-delete-form" onsubmit="return confirm('Confirmer la suppression de cette offre ?');">
                                                    <input type="hidden" name="idOffreToDelete" value="<?php echo intval($offre->getIdOffre()); ?>">
                                                    <button type="submit" name="deleteOffre" class="delete-btn">Supprimer</button>
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
